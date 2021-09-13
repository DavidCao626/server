<?php
namespace App\EofficeApp\ImportExport\Traits;
use Eoffice;
use Illuminate\Support\Facades\Redis;;


trait ImportTrait{
    public $emptyData = 'EMPTY_DATA';
    /**
     * 获取系统模块提供的模板
     * @param $params
     * @return mixed
     */
    public function getModuleHeader($params)
    {
        //不在导入配置里
        if (!$config = config('import.' . $params['module'])) {
            $config = [];
            $importParam['user_info'] = $params['user_info'];
            $importParam = isset($params['params']) ? $params['params'] : '';
            $importInfo = app('App\EofficeApp\FormModeling\Services\FormModelingService')->getImportFields($params['module'], $importParam);
            $config['module'] = $params['module'];
        } else {
            $configFieldsForm = $config['fieldsFrom'] ?? [];
            $configsParams = $params['params'] ?? [];
            list($className, $methodName) = array_values($config['fieldsFrom']);
            //这块涉及自定义字段权限需要把user_info传递过去
            if (count($configFieldsForm) >= 4) {
                $importInfo = app($className)->{$methodName}($params['user_info'], $configsParams);
            } else if (count($configFieldsForm) === 3) {
                $configsParams = $configFieldsForm[2] === 'userInfo' ? $params['user_info'] : ($configFieldsForm[2] === 'data' ? $configsParams : []);
                $importInfo = app($className)->{$methodName}($configsParams);
            } else {
                $importInfo = app($className)->{$methodName}($configFieldsForm);
            }
        }
        return $importInfo;
    }

    /**
     * 获取excel表格数据
     * @param $file
     * @param $startRow
     * @param $endRow
     * @param array $fileData
     * @param bool $header
     * @return array|string
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     */
    public function getExcelData($file, $startRow, $endRow, $fileData = [], $header = false)
    {

        $file = $this->fileTranscode($file);
        if (!is_file($file)) {
            return [];
        }
        $excelReader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader("Xlsx");
        $objPHPExcel = $excelReader->load($file);
        $objWorksheet = $objPHPExcel->getActiveSheet();

        $highestRow = $objWorksheet->getHighestRow();

        //获取合并行和合并列
        $merges = $objWorksheet->getMergeCells();
        $mergeCells = [];
        foreach ($merges as $key => $value) {
            $mergeInfo = $this->transferMergeData($value);
            //column 起始不一致时表示合并列
            if ($mergeInfo['begin']['column'] == $mergeInfo['end']['column']) {
                //合并多少行
                $column = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($mergeInfo['end']['column']);
                $mergeCells[$mergeInfo['begin']['row'] . '-' . $mergeInfo['end']['row']][] = $column;
            }
        }

        if (!$header) {
            //判断是否为空excel
            if (($startRow-1) == $highestRow) {
                return $this->emptyData;
            }
        }


        //获取总列数并转为数字 A=0 AA = 27
        $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($objWorksheet->getHighestColumn());
        $typeNumeric = \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC;
//        $endRow = $highestRow >= $endRow ? $endRow : $highestRow;
//        $endRow = $highestRow;

//        $endRow = 2;

        $data = [];
        $collect = [];
        for ($row = $startRow; $row <= $endRow; $row++) {
            for ($col = 1; $col <= $highestColumnIndex; $col++) {
                $cell = $objWorksheet->getCellByColumnAndRow($col, $row);
                $value = $cell->getValue();

                if ($cell->getDataType() == $typeNumeric) {
                    $formatcode = $cell->getStyle($cell->getCoordinate())->getNumberFormat()->getFormatCode();

                    if (strpos($formatcode, 'h') !== false) {
                        $formatDate = strpos($formatcode, 'y') !== false ? "Y-m-d H:i:s" : "H:i:s";
                        $value = getBeyondUnixDate($formatDate, \PhpOffice\PhpSpreadsheet\Shared\Date::excelToTimestamp($value));
                    } else if (strpos($formatcode, 'm') !== false) {
                        $value = getBeyondUnixDate("Y-m-d", \PhpOffice\PhpSpreadsheet\Shared\Date::excelToTimestamp($value));
                    } else {
                        $value = \PhpOffice\PhpSpreadsheet\Style\NumberFormat::toFormattedString($value, $formatcode);
                    }
                } else if (is_object($value)) {
                    $value = (string)$value;
                }

                $data[$row - 1][$col] = $value;
                if (!empty($mergeCells)) {
                    foreach ($mergeCells as $line => $columnInfo) {
                        $lineData = explode("-", $line);
                        $start = $lineData[0];
                        $end = $lineData[1];
                        if ($start > 1 && $row >= $start && $row <= $end && !in_array($col, $columnInfo)) {
                            $data[$row - 1][$col] = null;
                            $collect[$col . '-' . $start][] = $value;
                        }

                    }
                }
            }
        }
        if (!empty($collect)) {
            foreach ($collect as $key => $value) {
                $key = explode("-", $key);
                $col = $key[0];
                $start = $key[1];
                $data[$start - 1][$col] = $value;

            }
        }
        if (!empty($fileData)) {

            foreach ($fileData as $index => $d) {
                $attRow = $d['row'];
                $attColumn = $d['column'];
                if($attRow > $startRow && $attRow < $endRow){
                    if ($d['row'] == 1) {
                        continue;
                    }
                    if (!empty($collect) && $d['row'] == 2) {
                        continue;
                    }
                    if (!empty($mergeCells)) {
                        foreach ($mergeCells as $line => $columnInfo) {
                            $lineData = explode("-", $line);
                            $start = $lineData[0];
                            $end = $lineData[1];
                            if ($attRow >= $start && $attRow <= $end && !in_array($attColumn, $columnInfo)) {
                                $fileData[$index]['row'] = $start;
                                $fileData[$index]['index'] = $attRow - $start;
                                // $data[$start - 1][$d['column']] = [];
                            }

                        }
                    }
                    $data[$d['row'] - 1][$d['column']] = [];
                }
                //附件先置空

            }
            foreach ($fileData as $d) {
                if($d['row'] > $startRow && $d['row'] < $endRow){
                    if ($d['row'] == 1) {
                        continue;
                    }
                    if (!empty($collect) && $d['row'] == 2) {
                        continue;
                    }
                    if (!is_array($data[$d['row'] - 1][$d['column']])) {
                        $data[$d['row'] - 1][$d['column']] = [];
                    }
                    if (isset($d['index'])) {
                        //明细手机附件
                        $data[$d['row'] - 1][$d['column']][$d['index']] = $d['attachment_id'];
                    } else {
                        $data[$d['row'] - 1][$d['column']][] = $d['attachment_id'];
                    }
                }


            }


        }
        if (!$header) {
            // 去除最后的空行
            $data = $this->deleteEndEmptyRows($data);
        }

        //Excel5 $this->_phpExcel->disconnectWorksheets();
        if (method_exists($excelReader, 'destroy')) {
            $excelReader->destroy();
        }
        return $data;
    }

    /**
     * 获取excel里的文件
     * @param $file
     * @param $userId
     * @return array
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     */
    public function getExcelFile($file,$userId)
    {

        $excelReader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xlsx');
        $objPHPExcel = $excelReader->load($file);
        $objWorksheet = $objPHPExcel->getActiveSheet();

//        $params = $this->param;
//        $userId = isset($params['param']) ? $params['param']['user_info']['user_id'] : $params['user_info']['user_id'];
        $result = [];
        foreach ($objWorksheet->getDrawingCollection() as $img) {
            list($startImgColumn, $startImgRow) = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::coordinateFromString($img->getCoordinates());
            $imageFileName = $img->getCoordinates() . mt_rand(100, 999);
            $fileName = $imageFileName;
            $md5FileName = app("App\EofficeApp\Attachment\Services\AttachmentService")->getMd5FileName($imageFileName);
            $attachmentId = app("App\EofficeApp\Attachment\Services\AttachmentService")->makeAttachmentId($userId);
            $attachmentPath = app("App\EofficeApp\Attachment\Services\AttachmentService")->createCustomDir($attachmentId);
            $extension = getimagesize($img->getPath())['mime'];
            $extension = explode("/", $extension)[1];
            $extension = strtolower($extension);
            switch ($extension) {
                case 'jpg':
                case 'jpeg':
                case 'jfif':
                    $md5FileName .= '.jpg';
                    $imageFileName .= '.jpg';
                    $source = imagecreatefromjpeg($img->getPath());
                    imagejpeg($source, $attachmentPath . $md5FileName);
                    break;
                case 'gif':
                    $md5FileName .= '.gif';
                    $imageFileName .= '.gif';
                    $source = imagecreatefromgif($img->getPath());
                    imagegif($source, $attachmentPath . $md5FileName);
                    break;
                case 'png':
                    $md5FileName .= '.png';
                    $imageFileName .= '.png';
                    $source = imagecreatefrompng($img->getPath());
                    imagepng($source, $attachmentPath . $md5FileName);
                    break;
                case 'bmp':
                case 'x-ms-bmp':
                    $md5FileName .= '.bmp';
                    $imageFileName .= '.bmp';
                    copy($img->getPath(), $attachmentPath . $md5FileName);
                    break;
                default:
                    $extension .= $img->getExtension();
                    $md5FileName .= '.' . $extension;
                    $imageFileName .= '.' . $extension;
                    copy($img->getPath(), $attachmentPath . $md5FileName);
                    break;
            }
            $imageFilePath = $attachmentPath . $md5FileName;
            $res = app("App\EofficeApp\Attachment\Services\AttachmentService")->uploadThen(1, 0, $userId, $imageFileName, $imageFilePath, $imageFileName, []);
            @rmdir($attachmentPath);
            if (isset($res['attachment_id'])) {
                $columnNum = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($startImgColumn);
                $result[] = [
                    'column' => $columnNum,
                    'columnNum' => $startImgColumn,
                    'row' => $startImgRow,
                    'attachment_id' => $res['attachment_id'],
                ];
            }

        }
        return $result;

    }

    private function deleteEndEmptyRows($data)
    {
        $indexes = array_reverse(array_keys($data));
        foreach ($indexes as $index) {
            $i = 0;
            foreach ($data[$index] as $value) {
                if (is_array($value) && !empty($value)) {
                    ++$i;
                }
                if (!is_array($value) && trim($value)) {
                    ++$i;
                }
            }
            if ($i == 0) {
                unset($data[$index]);
            }
        }

        return $data;
    }

    public function getArrayDim($vDim)
    {
//        $count = 0;
        if (!is_array($vDim)) return 0;
        else {
            $max1 = 0;
            foreach ($vDim as $item1) {
//                $count = count($item1);
                $t = $this->getArrayDim($item1);
                if ($t > $max1) {
                    $max1 = $t;
                }

            }
            return $max1 + 1;
        }
    }

    public function getArrayMaxLength($arr)
    {
        $count = 0;
        if (!empty($arr)) {
            foreach ($arr as $key => $value) {
                if ($key == 'importReason' || $key == 'importResult') {
                    continue;
                }
                if(is_array($value) && count($value) > $count){
                    $count = count($value);
                }

            }
        }
        return $count;

    }

    public function transferMergeData($value)
    {
        $value = explode(':', $value);
        $begin = $value[0];
        $preg = '#([a-zA-Z+]+)(\d+)#';
        preg_match_all($preg, $begin, $m);
        $begin = [
            'row' => $m[2][0],
            'column' => $m[1][0]
        ];
        $end = $value[1];
        preg_match_all($preg, $end, $m);
        $end = [
            'row' => $m[2][0],
            'column' => $m[1][0]
        ];
        $mergeCells = [
            'begin' => $begin,
            'end' => $end
        ];
        return $mergeCells;
    }

    public function fileTranscode($file, $from = "UTF-8", $to = "GBK")
    {
        if (empty($file)) {
            return '';
        }

        $files = explode('/', str_replace('\\', '/', $file));
        $fileNames = explode('.', end($files));
        if (count($fileNames) == 2) {
            return dirname($file) . '/' . iconv($from, $to, $fileNames[0]) . '.' . $fileNames[1];
        } else {
            return "";
        }
    }

    /**
     * 错误处理
     * @param $uid
     * @param $file
     * @param $module
     * @param string $errorType
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    private function importExcelErrorCallback($uid, $file, $module, $errorType = '')
    {
        $dir = createExportDir();
        $suffix = mb_substr($file, strrpos($file, '.') + 1);
        if (!$config = config('import.' . $module)) {
            $fileName = app('App\EofficeApp\FormModeling\Services\FormModelingService')->getImportResultTitle($module);
            $fileName .= date('YmdHis');
        } else {
            $fileName = trans('import.' . $module) . "_" . trans("import.import_report") . date('YmdHis');
        }
        $savefilePath = $dir . $fileName . "." . $suffix;
        copy($file, $savefilePath);
        $objPHPExcel = \PhpOffice\PhpSpreadsheet\IOFactory::load($savefilePath);
        $sheet = $objPHPExcel->getSheet(0);
        $highestRow = $sheet->getHighestRow(); // 取得总行数
        $highestColumm = $sheet->getHighestColumn();
        $highestColumm = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumm);
        $transMessage = '';
        if ($errorType === '') {
            $transMessage = trans("import.import_report_fail_excel");
        } else if ($errorType === $this->emptyData) {
            $transMessage = trans("import.import_report_empty_excel");
        }
        $sheet->setCellValueByColumnAndRow(0, ++$highestRow, $transMessage);
        $sheet->getStyleByColumnAndRow(0, $highestRow)->getFont()->getColor()->setRGB('DC143C');
        $objWriter = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($objPHPExcel, 'Xlsx');
        $objWriter->save($savefilePath);

        if ($objPHPExcel instanceof \PhpOffice\PhpSpreadsheet\Spreadsheet) {
            $objPHPExcel->disconnectWorksheets();
        }
        $key = md5($uid . $fileName . time());
        $logData = [
            'export_name' => $fileName,
            'export_key' => $key,
            'export_file' => $savefilePath,
            'user_id' => $uid,
            'export_type' => 2,
            'is_read' => 0,
        ];

        //记录日志
        $logObj = app("App\EofficeApp\ImportExport\Services\ImportExportService")->addExportLog($logData);

        //发送消息提醒参数
        $sendData = [
            'toUser' => $uid,
            'remindState' => 'export.download',
            'remindMark' => 'export-download',
            'sendMethod' => ['sms'],
            'isHand' => true,
            'content' => $fileName,
            'stateParams' => ['key' => $key],
            'contentParam' => $fileName,
        ];
        Eoffice::sendMessage($sendData);
        $exportChannelParams = [
            'userId' => $uid,
            'downloadKey' => $key,
        ];
        // OA实例模式下，发送REDIS_DATABASE参数
        if (envOverload('CASE_PLATFORM', false)) {
            $exportChannelParams['REDIS_DATABASE'] = envOverload('REDIS_DATABASE', 0);
        }
        Redis::publish('eoffice.export-channel', json_encode($exportChannelParams));

    }

    /**
     * 处理系统提供的头部验证头部的时候使用
     * @param $moduleHeaders
     * @return array
     */
    public function parseModuleHeader($moduleHeaders)
    {
        $systemHeader = [];
        $headerLength = 1;
        foreach ($moduleHeaders as $fieldKey => $fieldValue) {
            //todo 格式不正确会报错,带children那种这块各个模块给的格式要一致,
            if (array_key_exists("children", $fieldValue)) {
                $headerLength = 2;
                foreach ($fieldValue['children'] as $key => $value) {
                    $systemHeader [] = $value['data'];
                }
            } else {
                $systemHeader [] = $fieldValue['data'];
            }
        }

        return [$headerLength, $systemHeader];
    }

    /**
     * 把头部信息和导入数据拼装
     * @param $header
     * @param $data
     * @return array
     */
    public function parseImportData($header, $data)
    {

        $mergeData = [];
        $arr = [];
        $mergeHeader = array_flip(array_keys($header));
        foreach ($mergeHeader as $k => &$v) {
            $v = null;
        }
        unset($v);

        foreach ($header as $key => $value) {
            if (array_key_exists("children", $value)) {
                foreach ($value['children'] as $childrenKey => $childrenValue) {
                    $mergeHeader[$key][$childrenKey] = null;
                }
            }
        }

        foreach ($data as $k => $v) {
            $i = 0;
            foreach ($mergeHeader as $key => $value) {
                if (is_array($value)) {

                    foreach ($value as $childrenKey => $childrenValue) {
                        $i++;
                        $arr[$childrenKey] = $v[$i];
                        $mergeData[$k][$key] = [$arr];
                    }
                    $arr = [];
                } else {
                    $i++;
                    $mergeData[$k][$key] = $v[$i];
                }
            }
        }
        $bigArr = [];
        foreach ($mergeData as $key => &$value){
            foreach ($value as $k => &$v){
                if(is_array($v)){
                    //todo 后期处理这边0不一定存在
                        foreach ($v[0] as $fieldKey => $fieldValue){
                            $bigArr = $v[0];
                            $sign = false;
                            if(is_array($fieldValue)){
                                $bigArr = [];
                                $arrKey = array_flip(array_keys($v[0]));
                                for($i = 0;$i < count($fieldValue);$i++){
                                    $bigArr[]= $arrKey;
                                }
                                foreach ($v[0] as $sonKey => $sonValue){
                                    for($i = 0;$i < count($fieldValue);$i++){
                                        $bigArr[$i][$sonKey]= $sonValue[$i];
                                    }
                                }
                                $sign = true;
                                break;
                            }
                        }
                        $v = $sign ? $bigArr:[$bigArr];
                }
            }
        }

        return $mergeData;

    }

    protected function throwException($error, $dynamic = null)
    {
        if (isset($error['code'])) {
            echo json_encode(error_response($error['code'][0], $error['code'][1], $dynamic), 200);
            exit;
        }
    }
}