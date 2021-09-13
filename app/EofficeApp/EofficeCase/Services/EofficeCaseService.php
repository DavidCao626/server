<?php

namespace App\EofficeApp\EofficeCase\Services;

use App\EofficeApp\Base\BaseService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\EofficeApp\EofficeCase\Services\lib\Utils;

class EofficeCaseService  extends BaseService
{
    private $attachment;
    private $im;
    private $eofficeConfigIni;
    private $redis;
    private $mysql;
    private $access;
    private $eofficeCaseList;
    // 工具类
    private $utils;
    // 导出url
    private $exportUrl;
    // $config
    private $Config;

    // 生成的案例存放的位置
    private $zipTmpPath;
    // Attachment模块attachmentService
    private $attachmentService = "App\EofficeApp\Attachment\Services\AttachmentService";
    public function __construct(
        Redis $redis,
        IM $im,
        Mysql $mysql,
        Attachment $attachment,
        Access $access,
        EofficeConfigIni $eofficeConfigIni,
        EofficeCaseList $eofficeCaseList
    ) {
        $this->utils = new Utils();
        $this->redis = $redis;
        $this->mysql = $mysql;
        $this->attachment = $attachment;
        $this->access = $access;
        $this->eofficeConfigIni = $eofficeConfigIni;
        $this->eofficeCaseList = $eofficeCaseList;
        $this->im = $im;

        $config = include(__DIR__ . '/conf/config.php');
        $this->zipTmpPath = $config['eoffice_install_dir'] . $config['export_path'];
        $this->exportUrl = $config['export_url'];
        $this->Config = $config;
    }

    /**
     * 导入案例
     */
    public function importEofficeCase($params)
    {
        $attachmentId = $params['attachment_id'] ?? false;
        $caseIdPrefix = $params['case_id_prefix'] ?? '';
        $customCaseId = $params['custom_case_id'] ?? '';
        if (!is_string($attachmentId) || $attachmentId == '') {
            // 缺少必填项
            return ['code' => ['0x000001', 'common']];
        }
        // 解压附件
        $extractDir = $this->extractCaseZip($attachmentId);
        if ($extractDir && isset($extractDir['dir'])) {
            // 分配配置
            $allocConfig = $this->allocEofficeCaseConfig($caseIdPrefix, $customCaseId);
            if ($allocConfig) {
                $this->moveCase($allocConfig, $extractDir['dir']);
                return $allocConfig;
            }
        }
        // 导入失败
        return ['code' => ['import_fail', 'eoffice_case']];
    }

    /**
     * 分配案例配置
     */
    public function allocEofficeCaseConfig($caseIdPrefix = '', $customCaseId = '')
    {
        $allocCacheKey = 'alloc_eoffice_case_lock';
        while (Cache::has($allocCacheKey)) {
            usleep(10000);
        }
        Cache::put($allocCacheKey, '', 5);
        $config = NULL;
        try {
            $allCaseList = $this->eofficeCaseList->getCaseList();
            // 分配案例id
            $caseId = $this->eofficeCaseList->allocEofficeCaseId($caseIdPrefix, $customCaseId);
            if ($caseId === false) {
                // 分配失败
                return false;
            }
            // 分配redis库
            $redisDatabase = $this->redis->allocRedisDatabase($allCaseList);
            $imNewPort     = $this->im->allocIMPort($allCaseList);
            if ($caseId && $redisDatabase && $imNewPort) {
                $config = [
                    'case_id'        => $caseId,
                    'attachment_dir' => $this->attachment->allocAttachmentName($caseId),
                    'db_database'    => $this->mysql->allocDatabaseName($caseId),
                    'access_path'    => $this->access->allocAccessName($caseId),
                    'redis_database' => $redisDatabase,
                    'im_new_port'    => $imNewPort,
                ];
                // 写入本地配置
                $this->eofficeConfigIni->addConfigIni($config);
                $this->im->addIMConfigFile($caseId, $imNewPort);
            }
        } catch (\Exception $e) {
            Log::info($e->getMessage());
        }
        Cache::forget($allocCacheKey);
        return $config;
    }

    // 转移解压的文件到相关配置的位置
    public function moveCase($config, $name)
    {
        $extractDir = $this->getExtractPath($name);
        $this->mysql->addDatabase($config['db_database'], $extractDir);
        $this->attachment->addAttachment($config['attachment_dir'], $extractDir);
        $this->access->addAccess($config['access_path'], $extractDir);
        // 创建文件夹
        // mkdir ($attachmentPathTo,0777,true);
        // mkdir ($databasePathTo,0777,true);
        // 转移附件到对应目录
        // $this->utils->dir_copy($extractDir.'attachment',$attachmentPathTo);
        // 转移数据库到对应目录
        // $this->utils->dir_copy($extractDir.'database',$databasePathTo);
        // exec("mv {$extractDir}".$caseName."/attachment {$attachmentPathTo}");
        // exec("mv {$extractDir}".$caseName."/database {$databasePathTo}");
        // 删除解压文件
        $this->utils->dir_del($extractDir);
        return true;
    }

    // 解压案例包
    public function extractCaseZip($attachmentId)
    {
        if (!is_string($attachmentId) || $attachmentId == '') {
            return false;
        }
        // 获取案例文件信息
        $attachmentInfo = $this->getAttachmentInfo($attachmentId);
        if ($attachmentInfo && isset($attachmentInfo['file_path'])) {
            $extractDirName = md5($attachmentId) . time() . mt_rand(0, 1000);
            // 临时解压目录
            $extractDir = $this->getExtractPath($extractDirName);
            if (file_exists($extractDir)) {
                $this->utils->dir_del($extractDir);
            }
            if (!is_dir($extractDir)) {
                // 创建临时目录
                mkdir($extractDir, 0777, true);
            }
            // 案例文件地址
            $caseFile = $attachmentInfo['file_path'];
            // 解压到临时目录,zipper解压有问题
            // $zipper = new Zipper;
            // $zipper->make($caseFile)->extractTo($extractDir);
            $this->utils->extractZip($caseFile, $extractDir);
            // 验证是否存在database
            if (file_exists($extractDir . DIRECTORY_SEPARATOR . 'database')) {
                return [
                    "dir"  => $extractDirName,
                ];
            } else {
                $this->utils->dir_del($extractDir);
            }
        }
        return false;
    }

    /**
     * 获取案例解压路径
     */
    private function getExtractPath($name)
    {
        return  $this->zipTmpPath . DIRECTORY_SEPARATOR . $name . DIRECTORY_SEPARATOR;
    }

    /**
     * 开启im server
     */
    public function openIMServer($request)
    {
        $caseId = getCurrentOaCaseId();
        $isCasePlatform = envOverload('CASE_PLATFORM', false);
        if ($isCasePlatform && $caseId != '') {
            $this->im->addPM2Config($caseId);
        }
        return true;
    }

    /**
     * 导出案例
     */
    public function exportEofficeCase($caseId, $param)
    {
        if ($caseId != '') {
            ini_set('max_execute_time', 0);
            // apache_reset_timeout();
            set_time_limit(0);
            $projectName = $param['project_name'] ?? '';
            $zipFile = $this->createCaseZip($caseId, $projectName);
            return $zipFile;
        }
        return [];
    }

    /**
     * 创建案例zip
     */
    public function createCaseZip($caseId, $projectName = '')
    {
        if (empty($caseId)) {
            return [];
        }
        $config = $this->eofficeConfigIni->getCaseConfigInfo($caseId);
        if ($config) {
            // 导出文件夹
            $exportDir = $this->zipTmpPath;
            $projectName = is_string($projectName) && $projectName != '' ? $projectName : $caseId;
            $caseDirName = $projectName . '_' . date('Ymd-His');
            // 生成的案例目录
            $caseDirPathTo = $exportDir . DIRECTORY_SEPARATOR . $caseDirName . DIRECTORY_SEPARATOR;
            while (file_exists($caseDirPathTo)) {
                $caseDirName .= '_' . rand(1000, 9999);
                $caseDirPathTo = $exportDir . DIRECTORY_SEPARATOR . $caseDirName . DIRECTORY_SEPARATOR;
            }
            // zip 文件名
            $caseZipName = $caseDirName . '.zip';
            // zip 文件路径
            $caseZipPathTo = $exportDir . $caseZipName;
            // 创建案例文件夹
            $this->utils->dir_mkdir($caseDirPathTo);
            // 复制附件、数据库文件到临时文件夹
            $this->mysql->copyDatabase($config['db_database'], $caseDirPathTo . '/database');
            // 导出数据
            $this->mysql->copyData($config['db_database'], $caseDirPathTo . '/db');
            $this->access->copyAccess($config['access_path'], $caseDirPathTo . '/access');
            $this->attachment->copyAttachment($config['attachment_dir'], $caseDirPathTo . '/attachment');
            // 多语言
            $langs = new Langs();
            $langs->copyLangs($caseDirPathTo . '/langs');

            // 复制version.json
            $versionJsonFilePath = $this->Config['eoffice_install_dir'] . DIRECTORY_SEPARATOR . 'www' . DIRECTORY_SEPARATOR . 'eoffice10' . DIRECTORY_SEPARATOR . 'version.json';
            if (file_exists($versionJsonFilePath)) {
                copy($versionJsonFilePath, $caseDirPathTo . '/version.json');
            }
            // 压缩临时文件夹
            // $zipper = new Zipper;
            // $zipper->make($caseZipPathTo)->add($caseDirPathTo)->close(); //生成压缩文件
            $this->utils->zipDir($caseDirPathTo, $caseZipPathTo);

            // 删除临时文件夹
            $this->utils->dir_del($caseDirPathTo);
            // 返回临时文件夹url供下载
            if (file_exists($caseZipPathTo)) {
                return [
                    // 返回给导入导出的字段
                    'file_name'    => $caseDirName,
                    'export_url'   => $this->exportUrl . $caseDirName . '.zip',
                    'file_type'    => 'zip',
                ];
            }
        }
        return [];
    }

    /**
     * 清除case
     * 
     */
    public function deleteEofficeCase($request)
    {
        $caseId = $request['case_id'] ?? '';
        if (empty($caseId)) {
            // 缺少必填项
            return ['code' => ['0x000001', 'common']];
        }
        try {
            $currentDB = envOverload('DB_DATABASE', '', true);
            $defaultDB = envOverload('DB_DATABASE', '', false);
            // 只有登入主服务才可以清理
            if ($currentDB != $defaultDB) {
                return false;
            }
            $this->mysql->deleteDatabase($caseId);
            $this->attachment->deleteAttachment($caseId);
            $this->access->deleteAccess($caseId);
            $this->im->deleteIMConfigFile($caseId);
            $hostInfo = $this->eofficeConfigIni->getCaseConfigInfo($caseId);
            if ($hostInfo && isset($hostInfo['redis_database']) && $hostInfo['redis_database'] > 0) {
                $this->redis->flushRedis($hostInfo['redis_database']);
            }
            // 最后删除实例配置文件
            $this->eofficeConfigIni->deleteConfigIni($caseId);
        } catch (\Exception $e) {
            Log::info($e->getMessage());
        }
        return true;
    }

    /**
     * 获取实例占用情况
     */
    public function getEofficeCaseSize($caseId)
    {
        $caseInfo       = $this->eofficeConfigIni->getCaseConfigInfo($caseId);
        $attachmentSize = 0;
        $accessSize     = 0;
        $databaseSize   = 0;
        if ($caseInfo && isset($caseInfo['attachment_dir'])) {
            $attachmentSize = $this->attachment->getSize($caseInfo['attachment_dir']);
            $databaseSize   = $this->mysql->getSize($caseInfo['db_database']);
            $accessSize   = $this->access->getSize($caseInfo['access_path']);
        }
        return [
            'attachment_size' => $attachmentSize,
            'access_size'     => $accessSize,
            'database_size'   => $databaseSize,
        ];
    }

    /**
     * 获取附件信息
     * @param $attachmentId string|array 附件id
     * @return array
     */
    private function getAttachmentInfo($attachmentId)
    {
        $attachmentFile = app($this->attachmentService)->getAttachmentInfo($attachmentId, []);
        if (isset($attachmentFile['url']) && isset($attachmentFile['name'])) {
            return [
                'name'      => $attachmentFile['name'],
                'file_path' => $attachmentFile['url']
            ];
        }
        return false;
    }
}
