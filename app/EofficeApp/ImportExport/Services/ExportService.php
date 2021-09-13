<?php
namespace App\EofficeApp\ImportExport\Services;
use App\EofficeApp\Base\BaseService;
use App\Jobs\ExportJob;
use Queue;
use Cache;
use App\EofficeApp\ImportExport\Repositories\ExportLogRepository;
use App\EofficeApp\ImportExport\Traits\ExportTrait;
use Eoffice;
use Illuminate\Support\Facades\Redis;
/**
 * Description of ExportService
 *
 * @author lizhijun
 */
class ExportService extends BaseService
{
    use ExportTrait;
    private $builderMap = [
        'xls' => 'Excel',
        'xlsx' => 'Excel',
        'eml' => 'Eml',
    ];
    private $exportLogRepository;
    public function __construct(ExportLogRepository $exportLogRepository) 
    {
        parent::__construct();
        $this->exportLogRepository = $exportLogRepository;
    }
    /**
     * 将多个文件合并为zip文件导出，需要配合导出api一起使用
     * 
     * @param type $files
     * @param string $fileName
     * 
     * @return type
     */
    public function saveAsZip($files, $fileName = '')
    {
        $fileName = $this->makeExportFileName($fileName);
        $fileZip = $this->createExportFile($fileName, 'zip');
        $zip = new \ZipArchive;
        if ($zip->open($fileZip, \ZIPARCHIVE::CREATE)) {
            foreach ($files as $key => $file) {
                if (is_array($file)) {
                    foreach($file as $k => $f) {
                        $zip->addFile($f, $k);
                    }
                } else {
                    $zip->addFile($file);
                }
            }
            $zip->close();
        }
        return [$fileName, $fileZip];
    }
    /**
     * 导出入口（支持同步、异步导出）
     * 
     * @param type $params
     * 
     * @return array
     */
    public function export($params)
    {
        $sync = $params['sync'] ?? false;
        if ($sync) {
            return $this->handleExport($params, false);
        }
        if($this->canAsyncExport()) {
            // 异步导出
            Queue::push(new ExportJob($params), null, 'eoffice_import_export_queue');
            return ['type' => 'async'];
        } else {
            // 同步导出
            return $this->handleExport($params, false);
        }
    }
    /**
     * 处理数据导出函数
     * 
     * @param type $params
     * @param type $async
     * 
     * @return boolean
     */
    public function handleExport($params, $async = true)
    {
        $module = $params['module'] ?? '';
        if (!$module) {
            return false;
        }
        // 获取配置文件和默认标题
        if ($config = config('extra_export.' . $module)) {
            $langModule = $config['module'] ?? $module;
            $title = trans($langModule . '.' . $module);
            $title = $title ? $title : trans('export.' . $module);
        } else {
            $config = config('export.' . $module);
            $title = trans('export.' . $module);
        }
        // 文件后缀
        $suffix = $this->getFileSuffix($config);
        // 创建导出组件构建器
        $builder = $this->generateBuilder($suffix);
        // 设置默认后缀和标题
        $builder->setSuffix($suffix)->setTitle($title);
        
        if (isset($config['dataFrom'])) {
            $from = $config['dataFrom'];//数据处理回调
            
            $result = app($from[0])->{$from[1]}($builder, $params['param'] ?? null);
            if (isset($result['code'])) {
                return $result;
            }
            list($fileName, $filePath) = $result;
        } else {
            list($fileName, $filePath) = $builder->setData($params['param'])->generate();
        }
        // 获取用户ID，默认system
        $userId = $params['param']['user_info']["user_id"] ?? "system";
        // 生成导出文件唯一标识码
        $exportKey = md5($userId . $fileName . time());
        // 缓存导出文件
        $this->cacheExportFile($userId, $exportKey, $filePath);
        // 记录日志
        $logData = [
            'export_name' => $fileName,
            'export_key'  => $exportKey,
            'export_file' => $filePath,
            'user_id'     => $userId,
            'export_type' => isset($config['importData']) ? 2 : 1,
            'is_read'     => 0,
        ];
        $this->exportLogRepository->insertData($logData);
        
        if ($async) {
            // 异步导出处理
           return $this->asyncExportTerminal($fileName, $userId, $exportKey);
        } 
        
        return ["type" => 'sync', "key" => $exportKey];
    }
    /**
     * 生成导出文件生成器
     * 
     * @param type $suffix
     * @return \App\EofficeApp\ImportExport\Services\class
     * @throws \Exception
     */
    private function generateBuilder($suffix)
    {
        $builderName = isset($this->builderMap[$suffix]) ? $this->builderMap[$suffix] . 'Builder' : 'OtherBuilder';
        $class = 'App\EofficeApp\ImportExport\Builders\\' . $builderName;
        if (!class_exists($class)) {
            throw new \Exception('Class ['.$builderName.'] not exists!');
        }
        return new $class;
    }
    private function getFileSuffix($config)
    {
        $suffix = isset($config['fileType']) ? strrchr($config['fileType'],'.') : '.xlsx';
        if($suffix === '.php') {
            throw new \Exception('File with current suffix cannot be exported!');
	}
	// 后缀名只允许：数字、字母、下划线
	if(!preg_match('/^\.\w+$/', $suffix)){
            throw new \Exception('File with current suffix cannot be exported!');
	}
        return ltrim($suffix, '.');
    }
    /**
     * 处理异步导出
     * 
     * @param type $fileName
     * @param type $userId
     * @param type $key
     * 
     * @return array
     */
    private function asyncExportTerminal($fileName, $userId, $key)
    {
         //发送消息提醒参数
        $sendData = [
            'toUser' => $userId,
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
            'userId' => $userId,
            'downloadKey' => $key,
        ];
        // OA实例模式下，发送REDIS_DATABASE参数
        if (envOverload('CASE_PLATFORM', false)) {
            $exportChannelParams['REDIS_DATABASE'] = envOverload('REDIS_DATABASE', 0);
        }
        Redis::publish('eoffice.export-channel', json_encode($exportChannelParams));

        return ['type' => 'async'];
    }
    /**
     * 缓存导出文件
     * 
     * @param type $userId
     * @param type $key
     * @param type $value
     * 
     * @return string
     */
    private function cacheExportFile($userId, $key, $value = null)
    {
        $userCacheId = 'export_download_' . $userId;
        $userDownloads = [];
        if (Cache::has($userCacheId)) {
            $userDownloads = Cache::get($userCacheId);
        }
        if ($value) {
            $userDownloads[$key] = $value;
            Cache::put($userCacheId, $userDownloads, 1440);
        } else {
            if (isset($userDownloads[$key]) && !empty($userDownloads[$key])) {
                return $userDownloads[$key];
            }
            if ($data = $this->exportLogRepository->getExportLog(['export_key' => [$key]])) {
                $userDownloads[$key] = $data->export_file;
                Cache::put($userCacheId, $userDownloads, 1440);
                return $data->export_file;
            }
            return '';
        }
    }
    /**
     * 下载导出文件
     * 
     * @param type $key
     * @param type $own
     * 
     * @return string
     */
    public function download($key, $own)
    {
        $userId = $own['user_id'] ?? 'system';
        if (empty($key)) {
            return '';
        }
        
        $file = $this->cacheExportFile($userId, $key);
        
        if ($file && file_exists($file)) {
            mb_detect_order("UTF-8,GB2312");
            
            return response()->download($file, basename($file));
        }
        
        return '';
    }
    /**
     * 验证当前系统关于异步下载的三个服务是否都启动了，有一个启动失败，则返回 false ，进行同步下载,否则返回 true ，进行异步下载
     * 
     * @return boolean
     */
    private function canAsyncExport()
    {
        //判断系统是否强制异步导出
        $asyncExport = get_system_param('async_export');
        if ($asyncExport == '1') {
            return true;
        }
        //如果eoffice_redis没有启动则系统都不能使用，所以不判断eoffice_redis的服务了
        //eoffice_im、eoffice_queue这两个服务都是启动的，则异步导出，否则同步导出
        $installPath = dirname(dirname(dirname(base_path())));
        $exePath = $installPath . DS . "bin" . DS . "EofficeServiceRegister.exe";
        if (file_exists($exePath)) {
            //返回的string中包含\0，需要替换掉
            $imStatus = "";
            exec("$exePath status eoffice_im", $imStatusOutput);
            if (is_array($imStatusOutput) && count($imStatusOutput) > 0) {
                $imStatus = str_replace("\0", "", $imStatusOutput[0]);
            }
            $queueStatus = "";
            exec("$exePath status eoffice_queue", $queueStatusOutput);
            if (is_array($queueStatusOutput) && count($queueStatusOutput) > 0) {
                $queueStatus = str_replace("\0", "", $queueStatusOutput[0]);
            }
            if (strpos($imStatus, "RUNNING") && strpos($queueStatus, "RUNNING")) {
                return true;
            }
        } else {
            // linux 平台上通过ps -aux | grep queue 判断是否启动队列服务
            system('ps aux |grep queue > /tmp/temp_run_queue.txt');
            if (file_exists('/tmp/temp_run_queue.txt')) {
                $contentArr = file('/tmp/temp_run_queue.txt');
                if (count($contentArr) > 2) {
                    return true;
                }
            }
        }
        return false;
    }
}
