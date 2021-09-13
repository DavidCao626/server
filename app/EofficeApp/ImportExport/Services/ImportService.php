<?php
namespace App\EofficeApp\ImportExport\Services;
use App\EofficeApp\Base\BaseService;
use App\EofficeApp\ImportExport\Traits\ImportTrait;
use App\EofficeApp\ImportExport\Repositories\ExportLogRepository;
use App\Jobs\ImportJob;
use Queue;
use Eoffice;
use Lang;
use Illuminate\Support\Facades\Redis;;
use Cache;
ini_set('memory_limit', '1024M');
class ImportService extends BaseService
{
    use ImportTrait;
    // excel导入每次处理条数
    const PER_NUM = 2000;
    //表单建模数据导入起始
    const START = 2;
    private $parserMap = [
        'Xls' => 'ExcelXls',
        'Xlsx' => 'ExcelXlsx',
    ];
    private $attachmentService;
    private $exportLogRepository;
    public function __construct(ExportLogRepository $exportLogRepository)
    {
        $this->attachmentService = 'App\EofficeApp\Attachment\Services\AttachmentService';
        $this->exportLogRepository = $exportLogRepository;
        parent::__construct();
    }


    /**
     * 导入入口，支持同步和异步
     * @param $params
     * @return array|bool|mixed
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     */
    public function import($params)
    {
        //todo 根据前端参数 reponse 决定下方的返回值
        $params['importMethod'] = 'sync';
        if(isset($params['importMethod']) && $params['importMethod'] == 'sync') {
            $result = $this->handleImport($params);

        }else{
            $result = Queue::push(new ImportJob($params));
        }
        return $result;
    }

    /**
     * 处理数据导入函数
     * @param array $params
     * @return array|bool|mixed
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     */
    public function handleImport(array $params)
    {
        $module = $params['module'] ?? null;
        $config = config('import.' . $module);
        //处理多个sheet导入问题
        if (isset($config['sheets']) && $config['sheets'] == true) {
            if (isset($config['sheetsFields'])) {
                $sheetFields = app($config['sheetsFields'][0])->{$config['sheetsFields'][1]}([]);
            } else {
                $sheetFields = [];
            }
            $allData = $this->getMulitSheetsData($params['file'], $sheetFields);
            $importInfo = app($config['dataSubmit'][0])->{$config['dataSubmit'][1]}($allData, $params['user_info'], $params);
            return $importInfo;
        }
        //处理表单字段
        if (isset($params['params']) && isset($params['params']['custom_detail'])) {
            return $this->formModelingDetail($params['module'],$params);
        }


        $attachmentInfo = app($this->attachmentService)->getOneAttachmentById($params['file']);
        if (!isset($attachmentInfo['temp_src_file'])) {
            //文件找不到
            return ['code' => ['0x010001', 'import']];
        }
        $params['file'] = $attachmentInfo['temp_src_file'];
        $class = $this->getClassByFile($params['file']);
        if(isset($class['code'])){
            return false;
        }
        if (!class_exists($class)) {
            return false;
        }
        $parser = new $class;
        list($reportFile, $reportConfig, $userId) = $parser->importUploadData($params);
        $this->exportResult($reportFile, $reportConfig, $userId);
        return true;
    }
    public function exportResult($reportFile, $reportConfig, $uid)
    {
        $filePath = transEncoding($reportFile, 'UTF-8');
        $reportConfig['file'] = $reportFile;
        $files = explode('/', str_replace('\\', '/', $filePath));
        $filesInfo = explode('.', end($files));
        foreach ($filesInfo as $key => $value) {
            $filesInfo[$key] = transEncoding($value, 'UTF-8');
        }
        $fileName = $filesInfo[0];
        $key = md5($uid . $fileName . time());
        //todo这个exportJobExportPublish要迁移过来
        $this->exportJobExportPublish($reportConfig, $filePath, $fileName, $key);
        return true;
    }

    public function exportJobExportPublish($config, $filePath, $fileName = [], $key)
    {
        $userCacheId   = 'export_dowanload_' . $config['user_id'];
        $userDownloads = [];

        if (Cache::has($userCacheId)) {
            $userDownloads = Cache::get($userCacheId);
        }

        $userDownloads[$key] = $filePath;
        Cache::put($userCacheId, $userDownloads, 1440);

        $logData = [
            'export_name' => $fileName,
            'export_key'  => $key,
            'export_file' => $filePath,
            'user_id'     => $config['user_id'],
            'export_type' => isset($config['importData']) ? 2 : 1,
            'is_read'     => 0,
        ];

        //记录日志
        $this->addExportLog($logData);

        if (isset($config["export_type"]) && $config["export_type"] == "async") {
            //发送消息提醒参数
            $sendData = [
                'toUser'       => $config['user_id'],
                'remindState'  => 'export.download',
                'remindMark'   => 'export-download',
                'sendMethod'   => ['sms'],
                'isHand'       => true,
                'content'      => $fileName,
                'stateParams'  => ['key' => $key],
                'contentParam' => $fileName,
            ];
            Eoffice::sendMessage($sendData);
            $exportChannelParams = [
                'userId'      => $config['user_id'],
                'downloadKey' => $key,
            ];
            // OA实例模式下，发送REDIS_DATABASE参数
            if (envOverload('CASE_PLATFORM', false)) {
                $exportChannelParams['REDIS_DATABASE'] = envOverload('REDIS_DATABASE', 0);
            }
            Redis::publish('eoffice.export-channel', json_encode($exportChannelParams));
        }
    }
    public function addExportLog($data)
    {
        return $this->exportLogRepository->insertData($data);
    }

    /**
     * 校验模板的合法性
     * @param $params
     * @param $own
     * @param $files
     * @return array|bool|string
     */
    public function importUpload($params, $own, $files)
    {
        $upload = app($this->attachmentService)->upload($params, $own, $files);
        if(isset($upload['code'])){
            return $upload;
        }
        // 检验模板
        if(! (isset($params['template']) && $params['template'] == 'false')){
            $params['user_info'] = $own;
            if(isset($params['params'])){
                $params['params'] = json_decode($params['params'], true);
            }
            $attachmentId = $upload['attachment_id'];
            $attachmentInfo = app($this->attachmentService)->getOneAttachmentById($attachmentId);
            if (!isset($attachmentInfo['temp_src_file'])) {
                return ['code' => ['0x010001', 'import']];
            }
            $params['file'] = $attachmentInfo['temp_src_file'];
            $class = $this->getClassByFile($params['file']);
            if(isset($class['code'])){
                return $class;
            }
            if (!class_exists($class)) {
                return false;
            }
            $builder = new $class;
            if(!$builder->checkUploadMatchTemplate($params)){
                return ['code' => ['0x010001', 'import']];
            }

        }
        return $upload;
    }


    public function getClassByFile($file){

        try{
            $fileType = \PhpOffice\PhpSpreadsheet\IOFactory::identify($file);
        }catch (\Exception $e){
            return ['code' => ['0x010001', 'import']];
        }
        $parserName = isset($this->parserMap[$fileType]) ? $this->parserMap[$fileType] . 'Parser' : 'OtherParser';
        $class = 'App\EofficeApp\ImportExport\Parsers\\' . $parserName;
        return $class;
    }

    public function getMulitSheetsData($file, $sheetFields)
    {
        $file = $this->fileTranscode($file);

        if (!is_file($file)) {
            return [];
        }
        // 20190918-U8集成，上传科目xlsx/csv/xls修改
        $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        if($extension == 'xlsx') {
            $excelReader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xlsx');
        } else if($extension == 'xls') {
            $excelReader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xls');
        } else if($extension == 'csv') {
            $excelReader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Csv');
            $excelReader->setInputEncoding('GBK');
            $excelReader->setDelimiter(',');
        }

        $PHPExcel = $excelReader->load($file);

        $sheetCount = $PHPExcel->getSheetCount();
        $sheetNames = $PHPExcel->getSheetNames();
        $allData    = [];
        for ($i = 0; $i < $sheetCount; $i++) {
            $currentSheet   = $PHPExcel->getSheet($i);
            $sheetName      = $sheetNames[$i];
            $oneSheetFields = $this->getOneSheetFields($sheetFields, $sheetName, $currentSheet);
            $data           = $this->getDataFromSheet($currentSheet, 2);
            $buildData      = [];
            if (!empty($data)) {
                foreach ($data as $value) {
                    $buildData[] = array_combine($oneSheetFields, $value);
                }
            }
            $allData[] = ['sheetName' => $sheetName, 'sheetFields' => $oneSheetFields, 'sheetData' => $buildData];
        }
        return $allData;
    }
    private function getOneSheetFields($sheetFields, $sheetName, $sheet)
    {
        if (empty($sheetFields)) {
            $oneSheetFields = $this->getDataFromSheet($sheet, 1, 1);
            $oneSheetFields = $oneSheetFields[0];
        } else {
            $oneSheetFields = array_values($sheetFields[$sheetName]);
        }
        return $oneSheetFields;
    }
    private function getDataFromSheet($sheet, $start = 1, $end = false)
    {
        $allRow        = $sheet->getHighestRow(); //获取Excel中信息的行数
        $allColumn     = $sheet->getHighestColumn(); //获取Excel的列数
        $highestRow    = intval($allRow);
        $end           = $end ? $end : $highestRow;
        $highestColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($allColumn); //有效总列数
        $data          = [];
        for ($row = $start; $row <= $end; $row++) {
            $cell = [];
            // 20200914-dp-在用了PhpSpreadsheet之后，循环从1开始，参考了此页面内 getExcelData 函数进行了修改
            // for ($col = 0; $col < $highestColumn; $col++) {
            for ($col = 1; $col <=$highestColumn; $col++) {
                $cellObj = $sheet->getCellByColumnAndRow($col, $row);
                $value   = $cellObj->getValue();
                if ($cellObj->getDataType() == \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC) {
                    $formatcode = $cellObj->getStyle($cellObj->getCoordinate())->getNumberFormat()->getFormatCode();

                    if (strpos($formatcode, 'h') !== false) {
                        $value = gmdate("Y-m-d H:i:s", \PhpOffice\PhpSpreadsheet\Shared\Date::excelToTimestamp($value));
                    } else if (strpos($formatcode, 'm') !== false) {
                        $value = gmdate("Y-m-d", (int) \PhpOffice\PhpSpreadsheet\Shared\Date::excelToTimestamp($value));
                    } else {
                        $value = \PhpOffice\PhpSpreadsheet\Style\NumberFormat::toFormattedString($value, $formatcode);
                    }
                } else if (is_object($value)) {
                    $value = (string) $value;
                }
                $cell[] = $value;
            }
            $data[] = $cell;
        }
        return $data;
    }
//    public function fileTranscode($file, $from = "UTF-8", $to = "GBK")
//    {
//        if (empty($file)) {
//            return '';
//        }
//
//        $files = explode('/', str_replace('\\', '/', $file));
//        $fileNames = explode('.', end($files));
//        if (count($fileNames) == 2) {
//            return dirname($file) . '/' . iconv($from, $to, $fileNames[0]) . '.' . $fileNames[1];
//        } else {
//            return "";
//        }
//    }

    /**
     * 表单字段
     * @param $module
     * @param $param
     * @return mixed
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     */
    public function formModelingDetail($module,$param)
    {
        $file =  $param['file'];
        $attachmentInfo = app($this->attachmentService)->getOneAttachmentById($file);
        if (!isset($attachmentInfo['temp_src_file'])) {
            return ['code' => ['0x010001', 'import']];
        }
        $file = $attachmentInfo['temp_src_file'];
        $end    = self::START + self::PER_NUM;
        $data = $this->getExcelData($file, self::START, $end, $fileData = []);
        $header = $this->getModuleHeader($param);
        $moduleHeader = $header[0]['header'] ?? [];
        $dataFilter = $this->parseImportData($moduleHeader, $data);
        $res = array_merge($dataFilter);;
        foreach ($res as $key => $value) {
            $res[$key] = app('App\EofficeApp\FormModeling\Services\FormModelingService')->parseOutsourceData($value,$module);
        }
        return $res;
    }

}
