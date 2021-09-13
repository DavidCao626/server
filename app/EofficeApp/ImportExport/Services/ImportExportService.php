<?php
namespace App\EofficeApp\ImportExport\Services;

set_time_limit(0);

use App\EofficeApp\Attachment\Services\AttachmentService;
use App\EofficeApp\Base\BaseService;
use App\Jobs\ImportExportJob;
use Illuminate\Support\Facades\DB;
use App\EofficeApp\ImportExport\Repositories\ExportLogRepository;
use Cache;

ini_set('memory_limit', '1024M');

use Eoffice;
use \PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\Redis;
use ZipArchive;

/**
 * 导入导出
 *
 * @author 齐少博
 *
 * @since 2016-01-06
 */
class ImportExportService extends BaseService
{
    private $exportLogRepository;

    private $attachmentService;

    public function __construct(
        ExportLogRepository $exportLogRepository,
        AttachmentService $attachmentService
    ) {
        parent::__construct();
        $this->exportLogRepository = $exportLogRepository;
        $this->attachmentService = $attachmentService;
    }

    /**
     * 获取导入模板数据
     *
     * @param  string  $from 查询条件
     *
     * @return array  查询结果或错误码
     *
     * @author qishaobo
     *
     * @since  2016-06-06
     */
    public function getImportTemplateData($from, $userInfo, $param = [])
    {
        $config = config('import.' . $from);
        if (empty($config)) {
            $importParam['user_info'] = $userInfo;
            $data = app('App\EofficeApp\FormModeling\Services\FormModelingService')->getImportFields($from, $param);
            $con  = [
                "title" => trans("import.Customize_page") . trans('import.template'),
            ];
            if(isset($data[0]['sheetName'])){
                $con['title'] = str_replace(['/', '\\'], ['_', '_'], $data[0]['sheetName']);
            }
        } else {
            if (!isset($config['fieldsFrom'])) {
                return '';
            }
            if (!isset($config['fieldsFrom'][2]) && !isset($config['fieldsFrom'][3])) {
                $data = app($config['fieldsFrom'][0])->{$config['fieldsFrom'][1]}($param);
            } else if (isset($config['fieldsFrom'][2]) && isset($config['fieldsFrom'][3])) {
                $data = app($config['fieldsFrom'][0])->{$config['fieldsFrom'][1]}($userInfo, $param);
            } else {
                if (isset($config['fieldsFrom'][2]) && $config['fieldsFrom'][2] == 'userInfo') {
                    $data = app($config['fieldsFrom'][0])->{$config['fieldsFrom'][1]}($userInfo);
                }
                if (isset($config['fieldsFrom'][2]) && $config['fieldsFrom'][2] == 'data') {
                    $data = app($config['fieldsFrom'][0])->{$config['fieldsFrom'][1]}($param);
                }
            }
            $con = [
                "title" => trans('import.' . $from) . trans('import.template'),
            ];
            if(isset($param['custom_detail']) && !empty($param['custom_detail'])){
                $con['title'] = isset($data[0]['sheetName'])?$data[0]['sheetName']:$con['title'];
            }

        }
        if (isset($config['startRow'][0])) {
            $con['startRow'] = $config['startRow'][0];
        }
        $file = app('App\Jobs\Export\Excel')->export($con, $data);
        return 'export/'. $file['file_name'] . '.' . $file['file_type'];
    }
    public function getMatchFields($module, $params, $own)
    {
        if (isset($params['file'])) {
            $file       = $params['file'];
            $config     = config('import.' . $module);
            $mustFields = $this->getTemplateFields($config, $params, $own);
            if (!$mustFields && !isset($mustFields['header'])) {
                return false;
            }
            try {
                $header = $this->getExcelData($file, 1, 1);
                $importFields = [];
                if(isset($header[0]) && !empty($header[0])){
                    foreach($header[0] as  $v){
                        $importFields[]= $v;
                    }
                }
            } catch (\Exception $e) {
                return ['code' => ['0x000011', 'common']];
            }
            $consultFieldKeys = [];
            foreach ($mustFields['header'] as $key => $value) {
                $consultFieldKeys[] = $key;
            }
            return ['consultFields' => $mustFields['header'], 'consultFieldKeys' => $consultFieldKeys, 'importFields' => $importFields];
        }

        return false;
    }
    private function getTemplateFields($config, $param, $own)
    {
        if (!isset($config['fieldsFrom'][2]) && !isset($config['fieldsFrom'][3])) {
            $data = app($config['fieldsFrom'][0])->{$config['fieldsFrom'][1]}($param);
        } else if (isset($config['fieldsFrom'][2]) && isset($config['fieldsFrom'][3])) {
            $data = app($config['fieldsFrom'][0])->{$config['fieldsFrom'][1]}($own, $param);
        } else {
            if (isset($config['fieldsFrom'][2]) && $config['fieldsFrom'][2] == 'userInfo') {
                $data = app($config['fieldsFrom'][0])->{$config['fieldsFrom'][1]}($own);
            } else if (isset($config['fieldsFrom'][2]) && $config['fieldsFrom'][2] == 'data') {
                $data = app($config['fieldsFrom'][0])->{$config['fieldsFrom'][1]}($param);
            }
        }
        return $data;
    }
    /**
     * 查询导入字段
     *
     * @param  string  $from 查询条件
     *
     * @return array  查询结果或错误码
     *
     * @author qishaobo
     *
     * @since  2016-06-16
     */
    public function getImportFields($from, $userInfo, $filter = [], $param = [])
    {
        $config = config('import.' . $from);

        if (empty($config) || !isset($config['fieldsFrom'])) {
            $data = app('App\EofficeApp\FormModeling\Services\FormModelingService')->getImportFields($from, $param);
        } else {
            if (!isset($config['fieldsFrom'][2]) && !isset($config['fieldsFrom'][3])) {
                $data = app($config['fieldsFrom'][0])->{$config['fieldsFrom'][1]}([]);
            } else if (isset($config['fieldsFrom'][2]) && isset($config['fieldsFrom'][3])) {
                $data = app($config['fieldsFrom'][0])->{$config['fieldsFrom'][1]}($userInfo, $param);
            } else {
                if (isset($config['fieldsFrom'][2]) && $config['fieldsFrom'][2] == 'userInfo') {
                    $data = app($config['fieldsFrom'][0])->{$config['fieldsFrom'][1]}($userInfo);
                }
                if (isset($config['fieldsFrom'][2]) && $config['fieldsFrom'][2] == 'data') {
                    $data = app($config['fieldsFrom'][0])->{$config['fieldsFrom'][1]}($param);
                }
            }
        }
        // $userInfo = isset($config['fieldsFrom'][2]) ? $userInfo : [];
        // $data     = app($config['fieldsFrom'][0])->{$config['fieldsFrom'][1]}($userInfo);

        $fields = [];
        $header = isset($data['header']) ? $data['header'] : (isset($data[0]['header']) ? $data[0]['header'] : []);

        foreach ($header as $k => $v) {
            if (empty($filter) || in_array($k, $filter)) {
                $fields[] = [
                    'field'     => $k,
                    'fieldName' => isset($v['data']) ? $v['data'] : $v,
                ];
            }
        }

        return $fields;
    }

    /**
     * 查询依据字段
     *
     * @param  string  $from 查询条件
     *
     * @return array  查询结果或错误码
     *
     * @author qishaobo
     *
     * @since  2016-06-16
     */
    public function getImportPrimarys($from, $userInfo, $param = [])
    {
        $config = config('import.' . $from);

        if (empty($config)) {
            $filter = [];
            $search = [];
            $search['search']['field_directive']   = [['upload','area','detail-layout'], 'not_in'];
            $param['show_header'] = true;
            $lists  = app('App\EofficeApp\FormModeling\Services\FormModelingService')->listCustomFields($search, $from);
            foreach ($lists as $key => $value) {
                $filter[] = $value->field_code;
            }
        } else {
            $filter = empty($config['primarys']) ? [] : $config['primarys'];
        }
        if (!empty($param)) {
            $param['params'] = $param;
        }
        return $this->getImportFields($from, $userInfo, $filter, $param);
    }

    /**
     * 下载文件
     *
     * @param  string $key 下载标识
     * @param  string $token token信息
     *
     * @return string 附件地址
     *
     * @author qishaobo
     *
     * @since  2017-02-21
     */
    public function download($key, $token)
    {
        if (empty($key)) {
            return '';
        }

        $userInfo = Cache::get($token);

        if(empty($userInfo->user_id)){
        	return '';
        }

        $userCacheId     = 'export_dowanload_' . $userInfo->user_id;
        $commonCacheId   = 'export_dowanload_common';
        $userDownloads   = Cache::get($userCacheId);
        $commonDownloads = Cache::get($commonCacheId);

        if (!empty($userDownloads) && !empty($userDownloads[$key])) {
            return $userDownloads[$key];
        }

        if (!empty($commonDownloads) && !empty($commonDownloads[$key])) {
            return $commonDownloads[$key];
        }

        if ($data = $this->exportLogRepository->getExportLog(['export_key' => [$key]])) {
            return $data->export_file;
        }

        return '';
    }

    /**
     * 添加导出日志
     *
     * @param  array  $data 新建数据
     *
     * @return array  查询结果或错误码
     *
     * @author qishaobo
     *
     * @since  2017-04-17
     */
    public function addExportLog($data)
    {
        return $this->exportLogRepository->insertData($data);
    }

    /**
     * 修改导出日志
     *
     * @param  array  $data 新建数据
     *
     * @return array  查询结果或错误码
     *
     * @author qishaobo
     *
     * @since  2017-04-17
     */
    public function updateExportLog($exportId, $data)
    {
        return $this->exportLogRepository->updateData($data, ['export_id' => $exportId]);
    }

    /**
     * 查询导出日志
     *
     * @param  array  $data 新建数据
     *
     * @return array  查询结果或错误码
     *
     * @author qishaobo
     *
     * @since  2017-04-17
     */
    public function getExportLogs($param = [])
    {
        $param = $this->parseParams($param);
        return $this->exportLogRepository->getExportLogs($param);
    }

    /**
     * 验证当前系统关于异步下载的三个服务是否都启动了，有一个启动失败，则返回 sync ，进行同步下载。
     * 返回 async ，进行异步下载
     * @return [type] [description]
     */
    public function verifyExportType()
    {
    	//判断系统是否强制异步导出
    	$compelResult = DB::table('system_params')->where('param_key','async_export')->get()->toArray();
    	if(isset($compelResult[0])&&$compelResult[0]->param_value=='1'){
			return "async";
    	}
        //如果eoffice_redis没有启动则系统都不能使用，所以不判断eoffice_redis的服务了
        //eoffice_im、eoffice_queue这两个服务都是启动的，则异步导出，否则同步导出
        $basePath    = base_path();
        $installPath = dirname(dirname(dirname($basePath)));
        $exePath     = $installPath . DS . "bin" . DS . "systemservice". DS . "systemservice.exe";
        if (file_exists($exePath)) {
            //返回的string中包含\0，需要替换掉
            // exec("$exePath -p eoffice_import_export_queue", $queueStatusOutput);
            $imStatus = "";
            // exec($exePath." -p eoffice_im -n -1", $imStatusOutput);
            // 改为system函数调用底层程序
            system($exePath. ' ' . '-u' . ' '.'eoffice_im'. ' '. '-n'. ' '. '-1', $imStatusOutput);
            
            system($exePath. ' ' . '-u' . ' '.'eoffice_import_export_queue', $queueStatusOutput);
           
            if ($imStatusOutput > 0 && $queueStatusOutput > 0) {
                return "async";
            }
        } else {
            // linux 平台上通过ps -aux | grep queue 判断是否启动队列服务
            system('ps aux |grep queue > /tmp/temp_run_queue.txt');
            if (file_exists('/tmp/temp_run_queue.txt')) {
                $contentArr = file('/tmp/temp_run_queue.txt');
                if (count($contentArr) > 2) {
                    return "async";
                }
            }
        }
        return "sync";
    }

    /**
     * 封装导出Job里面的函数--导出为excel
     * @param  [type] $param [description]
     * @return [type]        [description]
     */
    public function exportJobHandleExport($param)
    {
        $module = $param['module'] ?? '';
        if (!$module) {
            return false;
        }
        if (isset($param['id'])) {
			unset($param['id']);
        }

        $title = '';
        if ($config = config('extra_export.' . $module)) {
            $langModule = $config['module'] ?? $module;
            $title      = trans($langModule . '.' . $module);
        } else {
            $config     = config('export.' . $module);
        }

        if (!$title) {
            $title = trans('export.' . $module);
        }

        $config = $this->getCustomerConfig($config,$module);
        $userId = isset($param['param']['user_info']) ? $param['param']['user_info']["user_id"] : "";
        // 获取是否压缩的配置信息
        $configCompress = (isset($config['compress']) && $config['compress'] === true) ? true : false;
        // 如果配置了需要压缩，去自定义函数里验证一下最终配置
        if($configCompress) {
            if(isset($config["compressVerify"]) && !empty($config["compressVerify"]) && count($config["compressVerify"]) == 2) {
                $configCompressVerify = app($config["compressVerify"][0])->{$config["compressVerify"][1]}($param['param']);
                $configCompress = $configCompressVerify === true ? true : false;
            }
        }
        // 生成一个时间戳，传入到数据生成函数里面，然后在外部，可以关联的获取数据(暂时用在流程查询导出那里，走redis获取附件信息)
        $getDataTimestamp = md5($userId . time());
        $param['param']["getDataTimestamp"] = $getDataTimestamp;
        $param['param']["compress"] = $configCompress;
        $data = [];
        if (empty($config)) {
            $sheetName = mulit_trans_dynamic("custom_menu_config.menu_name.custom_config_" . $module);
            $title = $sheetName ? $sheetName : trans("export.Customize_page_export");
            $data  = app('App\EofficeApp\FormModeling\Services\FormModelingService')->exportFields($module, $param['param'], $param['param']['user_info']);
        } else {
            if(isset($config['dataFrom'])){
                $data = app($config['dataFrom'][0])->{$config['dataFrom'][1]}($param['param']);
            }else{
                $data = $param['param'];
            }
        }
        $config['from'] = $module;
        if (!$data instanceof \Generator) {
            if (!empty($data['export_title'])) {
                $title = $data['export_title'];
            }
            if (isset($data['sheets'])) {
                $data = $data['sheets'];
            }
            if (isset($data['export_data'])) {
                $data = $data['export_data'];
            }
        }
        $fileType = $this->getFileSuffix($config);
        $config   = [
            // 文件名不能包括\/:*?<>|
            'title'          => preg_replace("#[\\\/\:\*\?\<\>\|]#",'_',$title),
            'fileType'       => $fileType,
            'user_id'        => $userId,
            'customCreateFile' => isset($config['customCreateFile']) ? $config['customCreateFile'] : false,
            "export_type"    => isset($param["export_type"]) ? $param["export_type"] : "async",
            "generator_type" => isset($config['generator_type']) ? $config['generator_type'] : 1,
            'compress'       => $configCompress,
            'customCompress' => isset($config['customCompress']) ? $config['customCompress'] : "",
            'getDataTimestamp' => $getDataTimestamp,
        ];

        return $this->exportJobExportRealize($config, $data);
    }

    /**
     * 数据导出，数据源是一段已知数据
     * @param  [type] $param [description]
     * @return [type]        [description]
     */
    public function exportJobHandleExportString($param)
    {
        $config   = [
            'title'       => $param['title'] ?? '',
            'fileType'    => $this->getFileSuffix($param),
            'user_id'     => $param['user_id'],
            "export_type" => isset($param["export_type"]) ? $param["export_type"] : "async",
        ];

        return $this->exportJobExportRealize($config, $param['data']);
    }
    private function getFileSuffix($param)
    {
        $fileType = !empty($param['fileType']) ? strrchr(strtolower($param['fileType']), '.') : '.xlsx';
        if($fileType === '.php') {
            throw new \Exception('File with current suffix cannot be exported!');
	}
	// 后缀名只允许：数字、字母、下划线
	if(!preg_match('/^\.\w+$/', $fileType)){
            throw new \Exception('File with current suffix cannot be exported!');
	}
        return ltrim($fileType, '.');
    }
    public function exportJobExportRealize($config, $data)
    {
        $handleFiles = [
            'xls' => 'Excel',
            'xlsx' => 'Excel',
            'eml' => 'Eml',
        ];

        $factory = isset($handleFiles[$config['fileType']]) ? $handleFiles[$config['fileType']] : 'Other';
        $handle  = 'App\Jobs\Export\\' . $factory;
        if (isset($config['customCreateFile']) && $config['customCreateFile']) {
            $filesInfo = app($config['customCreateFile'][0])->{$config['customCreateFile'][1]}($config, $data);
        } else {
            $filesInfo = app($handle)->export($config, $data);
        }
        $dir = createExportDir();
        foreach ($filesInfo as $key => $value) {
            $filesInfo[$key] = transEncoding($value, 'UTF-8');
        }
        $fileType = isset($filesInfo['file_type']) ? $filesInfo['file_type'] : $config['fileType'];
        // 设置了对导出的文件进行压缩(压缩为zip)
        if(isset($config["compress"]) && $config["compress"] === true) {
            $compressResult = $this->compressExportFile($config,$filesInfo,$data);
            // zip生成成功，改变导出文件后缀(推送消息&下载等都会下载zip文件)
            if($compressResult) {
                $fileType = "zip";
            }
        }
        $getDataTimestamp = isset($config['getDataTimestamp']) ? $config['getDataTimestamp'] : "";
        Cache::forget('flow_search_export_attachment_info_'.$getDataTimestamp);
        $filePath = $dir . $filesInfo['file_name'] . '.' . $fileType;
        if (empty($filesInfo)) {
            $files     = explode('/', str_replace('\\', '/', $filePath));
            $filesInfo = explode('.', end($files));
            $fileName  = $filesInfo[0];
        } else {
            $fileName = $filesInfo['file_name'];
        }
        $key = md5($config['user_id'] . $fileName . time());
        if (!isset($config['file'])) {
            // $this->exportJobExportPublish($config, $filePath, $filesInfo);
            $this->exportJobExportPublish($config, $filePath, $fileName, $key);
        }

        if (!is_file($filePath)) {
            $filePath = $this->exportJobFileTranscode($filePath);
        }

        if (isset($config["export_type"]) && $config["export_type"] == "async") {
            return !empty($config['file']) ? $config['file'] : $filePath;
        } else {
            return $key;
        }
    }

    /**
     * 导出的时候，如果配置了进行压缩，那么在导出的文件生成之后，创建压缩文件(如果配置了自定义压缩，会调用自定义压缩)
     * @param  [type] $config    [导出基本配置等，从函数[exportJobHandleExport]里返回的]
     * @param  [type] $filesInfo [生成的导出文件的信息]
     * @param  [type] $data      [导出的数据]
     * @return [type]            [description]
     */
    public function compressExportFile($config,$filesInfo,$data)
    {
        try {
            $dir      = createExportDir();
            $fileType = isset($filesInfo['file_type']) ? $filesInfo['file_type'] : "xlsx";
            $customArchiveArray = [];
            if(isset($config["customCompress"]) && !empty($config["customCompress"]) && count($config["customCompress"]) == 2) {
                $customArchiveArray = app($config["customCompress"][0])->{$config["customCompress"][1]}($config);
            }
            // 上一步生成好的excel
            $exportBaseArchiveArray = [$filesInfo['file_name'].'.'.$fileType => $dir.$filesInfo['file_name'].'.'.$fileType];
            $zipArchiveConcat = $exportBaseArchiveArray + $customArchiveArray;
            // 把本体excel和自定义函数返回的文件，压入压缩文件
            $zipArchiveArray = [];
            if(!empty($zipArchiveConcat)) {
                foreach ($zipArchiveConcat as $key => $zipArchiveItem) {
                    if(is_file($zipArchiveItem)) {
                        $zipArchiveArray[$key] = $zipArchiveItem;
                    }
                }
            }
            // file_put_contents(base_path('storage/logs/zipArchiveConcat.txt'),json_encode($zipArchiveConcat));
            // file_put_contents(base_path('storage/logs/zipArchiveArray.txt'),json_encode($zipArchiveArray));
            $zipName = $dir.$filesInfo['file_name'].'.zip'; // 压缩文件名称
            $zipper = new ZipArchive;
            if ($zipper->open($zipName, ZipArchive::CREATE) === TRUE) {
                foreach ($zipArchiveArray as $fileName => $filePath) {
                    $fileDirName = dirname($fileName);
                    if ($fileDirName != '.') {
                        $zipper->addEmptyDir($fileDirName);
                    }
                    $zipper->addFile($filePath, $fileName);
                }
                $zipper->close();
            }
            return true;
        } catch (\Exception $e) {
            return ['code' => ['0x000003', 'common']];
        }
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
        $logObj = app("App\EofficeApp\ImportExport\Services\ImportExportService")->addExportLog($logData);

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

    public function exportJobFileTranscode($file, $from = "UTF-8", $to = "GBK")
    {
        if (empty($file)) {
            return '';
        }

        $files     = explode('/', str_replace('\\', '/', $file));
        $fileNames = explode('.', end($files));
        // return dirname($file).'/'.iconv($from, $to, $fileNames[0]).'.'.$fileNames[1];
        if (count($fileNames) == 2) {
            return dirname($file) . '/' . iconv($from, $to, $fileNames[0]) . '.' . $fileNames[1];
        } else {
            return "";
        }
    }
    public function syncImportData($param)
    {
        if (!isset($param['file']) || empty($param['file'])) {
            return ['code' => ['0x000022', 'common']];
        }
        if (!isset($param['module']) || empty($param['module'])) {
            return ['code' => ['0x000010', 'common']];
        }
        $config = config('import.' . $param['module']);

        if (!isset($param['user_info'])) {
            $param['user_info'] = [];
        }
        //表单建模明细处理
        if (isset($param['params']) && isset($param['params']['custom_detail'])) {
            return $this->formModelingDetail($param['module'],$param);
       }
        //处理多个sheet导入问题
        if (isset($config['sheets']) && $config['sheets'] == true) {
            if (isset($config['sheetsFields'])) {
                $sheetFields = app($config['sheetsFields'][0])->{$config['sheetsFields'][1]}([]);
            } else {
                $sheetFields = [];
            }
            $allData = $this->getMulitSheetsData($param['file'], $sheetFields);

            $importInfo = app($config['dataSubmit'][0])->{$config['dataSubmit'][1]}($allData, $param['user_info'], $param);

            return $importInfo;
        }

        //获取可导出字段
        if (!isset($config['fieldsFrom'][2]) && !isset($config['fieldsFrom'][3])) {
            $importInfo = app($config['fieldsFrom'][0])->{$config['fieldsFrom'][1]}([]);
        } else if (isset($config['fieldsFrom'][2]) && isset($config['fieldsFrom'][3])) {
            $importInfo = app($config['fieldsFrom'][0])->{$config['fieldsFrom'][1]}($param['user_info'], $param);
        } else {
            if (isset($config['fieldsFrom'][2]) && $config['fieldsFrom'][2] == 'userInfo') {
                $importInfo = app($config['fieldsFrom'][0])->{$config['fieldsFrom'][1]}($param['user_info']);
            } else if (isset($config['fieldsFrom'][2]) && $config['fieldsFrom'][2] == 'data') {
                $importInfo = app($config['fieldsFrom'][0])->{$config['fieldsFrom'][1]}($param);
            }
        }
        if (isset($param['match_fields']) && $param['match_fields']) {
            $header = $param['match_fields'];
        } else {
            $header = isset($importInfo['header']) ? $importInfo['header'] : $importInfo[0]['header'];
        }

        $fields = array();
        if (!empty($header)) {
            foreach ($header as $key => $value) {
                if (isset($value['data']) && !empty($value['data'])) {
                    $fields[$value['data']] = $key;
                } else {
                    $fields[$value] = $key;
                }
            }
        }

        $file = $param['file'];

        try {
            $firstLine   = $this->getExcelData($file, 1, 1);
            $excelHeader = $firstLine[0] ?? [];
        } catch (\Exception $e) {
            return ['code' => ['0x000011', 'common']];
        }
        if (empty($excelHeader) || empty($fields)) {
            return ['code' => ['0x000011', 'common']];
        }
        $excelHeader = array_filter($excelHeader);
        $kfields = array_keys($fields);
        $vheader = array_values($excelHeader);
        if (isset($param['match_fields']) && $param['match_fields']) {
            foreach ($kfields as $field) {
                if (!in_array($field, $vheader)) {
                    return ['code' => ['0x000011', 'common']];
                }
            }
        } else {
            sort($kfields);
            sort($vheader);

            if ($kfields != $vheader) {
                return ['code' => ['0x000011', 'common']];
            }
            $header = [];
            foreach ($excelHeader as $k => $v) {
                $header[$k] = $fields[$v];
            }
        }

        $start  = 2;
        $perNum = 2000;
        $end    = $start + $perNum;

        $info = [];
        $importJob = new ImportExportJob($param);
        $fileData = $importJob->getExcelFile($file);
        while ($data = $this->getExcelData($file, $start, $end,$fileData)) {
            if (empty($data)) {
                break;
            }
            $info  = array_merge($info, $data);
            $start = ++$end;
            $end   = $start + $perNum;
        }

        $importData = ['header' => $header, 'data' => $info];

        if (!isset($config['dataSubmit'][2]) && !isset($config['dataSubmit'][3])) {
            $importInfo = app($config['dataSubmit'][0])->{$config['dataSubmit'][1]}($importData, []);
        } else if (isset($config['dataSubmit'][2]) && isset($config['dataSubmit'][3])) {
            $importInfo = app($config['dataSubmit'][0])->{$config['dataSubmit'][1]}($importData, $param['user_info'], $param);
        } else {
            if (isset($config['dataSubmit'][2]) && $config['dataSubmit'][2] == 'userInfo') {
                $importInfo = app($config['dataSubmit'][0])->{$config['dataSubmit'][1]}($importData, $param['user_info']);
            }
            if (isset($config['dataSubmit'][2]) && $config['dataSubmit'][2] == 'data') {
                $importInfo = app($config['dataSubmit'][0])->{$config['dataSubmit'][1]}($importData, $param);
            }
        }
        return $importInfo;
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
    public function getExcelData($file, $startRow, $endRow,$fileData=[])
    {
        $file = $this->fileTranscode($file);

        if (!is_file($file)) {
            return [];
        }
        //$excelReader->setReadDataOnly(true);
        $inputFileType = \PhpOffice\PhpSpreadsheet\IOFactory::identify($file);
        $excelReader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader($inputFileType);
        if ($inputFileType == 'CSV') {
            $excelReader->setInputEncoding('GBK');
            $excelReader->setDelimiter(',');
        }

        $excelReader  = $this->ExcelFilter($excelReader, $startRow, $endRow);
        $objPHPExcel  = $excelReader->load($file);
        $objWorksheet = $objPHPExcel->getActiveSheet();

        $highestRow = $objWorksheet->getHighestRow();

        if ($startRow > $highestRow) {
            return [];
        }

        $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($objWorksheet->getHighestColumn());
        $typeNumeric        = \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC;

        $endRow = $highestRow >= $endRow ? $endRow : $highestRow;
        $data   = [];
        for ($row = $startRow; $row <= $endRow; $row++) {
            for ($col = 1; $col <=$highestColumnIndex; $col++) {
                $cell  = $objWorksheet->getCellByColumnAndRow($col, $row);
                $value2 =
                $value = $cell->getValue();
                if (strpos($value , '=') === 0 ) {
                    $value = $cell->getFormattedValue();
                } else {
                    if ($cell->getDataType() == $typeNumeric) {
                        $formatcode = $cell->getStyle($cell->getCoordinate())->getNumberFormat()->getFormatCode();
                        if (strpos($formatcode, 'h') !== false) {
                            if(strpos($formatcode, 'y') !== false){
                                $value = gmdate("Y-m-d H:i:s", (int) \PhpOffice\PhpSpreadsheet\Shared\Date::excelToTimestamp($value));
                            }else{
                                $value = gmdate("H:i:s", (int) \PhpOffice\PhpSpreadsheet\Shared\Date::excelToTimestamp($value));
                            }
                        } else if (strpos($formatcode, 'm') !== false) {
                            $value = gmdate("Y-m-d", (int) \PhpOffice\PhpSpreadsheet\Shared\Date::excelToTimestamp($value));
                        } else {
                            $value = \PhpOffice\PhpSpreadsheet\Style\NumberFormat::toFormattedString($value, $formatcode);
                        }
                    } else if (is_object($value)) {
                        $value = (string) $value;
                    }
                }

                $data[$row - 1][$col] = $value;
            }
        }
         if (!empty($fileData)) {
            foreach ($fileData as $d) {
                if(!isset($data[$d['row'] - 1][$d['column']]) || !is_array($data[$d['row'] - 1][$d['column']])){
                    $data[$d['row'] - 1][$d['column']] = [];
                }
                $data[$d['row'] - 1][$d['column']][] = $d['attachment_id'];
            }
        }
        //Excel5 $this->_phpExcel->disconnectWorksheets();
        if (method_exists($excelReader, 'destroy')) {
            $excelReader->destroy();
        }
        return $data;
    }

    public function ExcelFilter($excelReader, $startRow, $endRow)
    {
        if ($startRow && $endRow) {
            $filter           = new MyReadFilter();
            $filter->startRow = $startRow;
            $filter->endRow   = $endRow;
            $excelReader->setReadFilter($filter);
        }

        return $excelReader;
    }

    public function fileTranscode($file, $from = "UTF-8", $to = "GBK")
    {
        if (empty($file)) {
            return '';
        }

        $files     = explode('/', str_replace('\\', '/', $file));
        $fileNames = explode('.', end($files));
        if (count($fileNames) == 2) {
            return dirname($file) . '/' . iconv($from, $to, $fileNames[0]) . '.' . $fileNames[1];
        } else {
            return "";
        }
    }
    public function importData($header, $data, $config, $param)
    {
        if (empty($data)) {
            return [];
        }

        $dataFilter         = [];
        $importExportFliter = app("App\Utils\ImportExportFliter");

        foreach ($data as $k => $v) {
            foreach ($header as $key => $value) {
                if (strpos($value, '|') !== false) {
                    [$fieldName, $filter] = explode('|', $value);
                    $excelValue               = isset($v[$key]) ? $v[$key] : '';
                    $fieldValue               = $importExportFliter->{$filter}($excelValue);
                } else {
                    $fieldName  = $value;
                    $fieldValue = isset($v[$key]) ? $v[$key] : '';
                }
                $dataFilter[$k][$fieldName] = $fieldValue;
            }
        }

        if (isset($config['after'])) {
            $param['after'] = $config['after'];
        }

        if (isset($config['filter'])) {
            $dataFilter = app($config['filter'][0])->{$config['filter'][1]}($dataFilter, $param);
        }
        return $dataFilter;
        return app($config['dataSubmit'][0])->{$config['dataSubmit'][1]}($dataFilter, $param);
    }

    public function getCustomerConfig($config,$module){
    	if($module=='flowSearch'){
    		$customerResult = DB::table('system_params')->where('param_key','customer_config')->get()->toArray();
			if(isset($customerResult[0])&&$customerResult[0]->param_value=='1'){
				$config['compress'] = false;
				$config['generator_type'] = 0;
				$config['dataFrom'][0] = 'App\EofficeApp\Flow\Services\FlowService';
			}
    	}
		return $config;
    }

    /**
     * @param $params
     * @param $own
     * @param $files
     * @return array
     */
    public function importUpload($params, $own, $files)
    {
        // 上传
        $upload = $this->attachmentService->upload($params, $own, $files);
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
            $attachmentList = $this->attachmentService->getOneAttachmentById($attachmentId);
            if (!isset($attachmentList['temp_src_file'])) {
                return ['code' => ['0x010001', 'import']];
            }

            $params['file'] = $attachmentList['temp_src_file'];

            if(!$this->isExcel2007($params['file']) || !$this->checkUploadMatchTemplate($params)){
                return ['code' => ['0x010001', 'import']];
            }
        }

        return $upload;
    }

    /**
     * 是否为excel2007
     * @param $filePath
     * @return bool
     */
    private function isExcel2007($filePath)
    {
        try{
            $fileType = \PhpOffice\PhpSpreadsheet\IOFactory::identify($filePath);
            if ($fileType != 'Xlsx'){
                return false;
            }
        }catch (\Exception $e){
            return false;
        }

        return true;
    }

    /**
     * 校验模板是否匹配
     * @param $params
     * @return bool
     */
    public function checkUploadMatchTemplate($params)
    {
        $importJob = new ImportExportJob($params);
        $err = $importJob->validateImportExcel($params)[0];
        if ($err) {
            return false;
        }
        $err = $importJob->getImportExcelInfo($params)[0];
        if ($err) {
            return false;
        }

        return true;
    }
    public function formModelingDetail($module,$param)
    {
        $file =  $param['file'];
        $start  = 2;
        $perNum = 2000;
        $end    = $start + $perNum;
        $data = $this->getExcelData($file, $start, $end);
        $dataFilter         = [];
        $importJob = new ImportExportJob($param);
        list($err, $header, $configs, $title) = $importJob->getImportExcelInfo($param);
        foreach ($data as $k => $v) {
            $i = 1;
            foreach ($header as $key => $value) {
                if (strpos($key, '|') !== false) {
                    list($fieldName, $filter) = explode('|', $key);
                    $excelValue               = key_exists($i, $v) ? $v[$i] : (key_exists($value, $v) ? $v[$value] : '');
                    $fieldValue               = $this->newWriteContent($filter, $excelValue);
                } else {
                    $fieldName  = $key;
                    $fieldValue = key_exists($i, $v) ? $v[$i] : (key_exists($value, $v) ? $v[$value] : '');
                }
                $dataFilter[$k][$fieldName] = $fieldValue;
                ++$i;
            }
        }
        $res = array_merge($dataFilter);
        foreach ($res as $key => $value) {
            $res[$key] = app('App\EofficeApp\FormModeling\Services\FormModelingService')->parseOutsourceData($value,$module);
        }
        return $res;
    }

}



class MyReadFilter implements \PhpOffice\PhpSpreadsheet\Reader\IReadFilter
{
    public $startRow = 1;
    public $endRow;

    public function readCell($column, $row, $worksheetName = '')
    {
        if (!$this->endRow) {
            return true;
        }

        if ($row >= $this->startRow && $row <= $this->endRow) {
            return true;
        }

        return false;
    }
}
