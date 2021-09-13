<?php

namespace App\EofficeApp\ServerManage\Services;

use App\EofficeApp\Base\BaseService;
use OSS\OssClient;
use OSS\Core\OssException;
use OSS\Http\RequestCore;
use OSS\Http\ResponseCore;
use OSS\Http\RequestCore_Exception;
use Cache;

/**
 * 服务器管理平台
 */
class ServerManageService extends BaseService {

    /** 
    * 阿里云主账号AccessKey拥有所有API的访问权限，风险很高。
    * 强烈建议您创建并使用RAM账号进行API访问或日常运维。
    * 请登录 https://ram.console.aliyun.com 创建RAM账号。
    */
    private $accessKeyId;
    private $accessKeySecret;
    // Endpoint以杭州为例，其它Region请按实际情况填写。
    private $endpoint;
    // 存储空间
    private $bucket;
    // 设置URL的有效期为3600秒。
    private $timeout;

    const PACK_UPDATE_SIGN = 'eoffice_pack_update';

    public function __construct( ) { 
        $this->accessKeyId = "Gu8wVysNVglNzqq9";
        $this->accessKeySecret = "a2uCnaqcKmT3vm2cNDRxgnPuSa0ci6";
        $this->endpoint = "http://oss-cn-hangzhou.aliyuncs.com";
        $this->bucket = "e-officefiles";
        $this->timeout = 3600;
    }

    /**
     * 获取服务状态
     * int $type 操作exe文件， 1 - 获取服务状态，2 - 更新操作，3 - 获取更新时间
     */
    public function operateExe($type, $own, $data = []) {
        // 如果没有服务器管理平台程序，不提醒升级
        if (!file_exists(base_path('../../../bin/ServerManagement.exe'))) {
            return 0;
        }
        $adminupgrade = '../../../bin/adminupgrade/adminupgrade.exe';
        $exe = base_path($adminupgrade);
        if (!file_exists($exe)) {
            return 0;
        }
        
        $param = [];
        if ($type == 2) {
            $time = isset($data['time']) ? $data['time'] : '0';
            $param = ['status' => $type, 'time' => $time];
        } else {
            $param = ['status' => $type];
        }
        // 记录升级开始日志
        $this->updateLogger($param, $own['user_id'], $type);
        // 执行升级脚本
        $param = base64_encode(json_encode($param));
        system($exe . ' ' . $param, $info);
        // 记录升级结束日志
        $this->updateLogger($info, $own['user_id'], $type, 'end');
        /* 
         * 查看服务状态时返回状态码
         * 1 - 已启动
         * 2 - 未收到消息
         * 3 - 未启动
        */
        return $info;
    }
    private function updateLogger($content, $userId, $status, $type = 'start')
    {
        if ($status != 2) {
            return;
        }
        file_put_contents(storage_path('/logs/system-update.log'), date('Y-m-d H:i:s') . ' [' . $userId . '] [' . $type . '-update] ' . getClientIp() . ' ' . json_encode($content) . "\n", FILE_APPEND);
    }
    /**
     * 获取新版本信息
     */
    public function getNewVersionInfo($param, $own) {
        // 服务管理平台屏蔽升级
        if (envOverload('OA_UPDATE', false) == 'false') {
            return ['isUpdate' => 0];
        }
        // 获取服务信息
        $serverStatus = $this->operateExe(1, $own);
        if ($serverStatus != 1) {
            return ['isUpdate' => 0];
        }
        // 获取当前版本信息
        if(file_exists(base_path('../version.json'))){
            $vesionArray = json_decode(file_get_contents(base_path('../version.json')), true);
            if (empty($vesionArray)) {
                return ['isUpdate' => 0];
            }
            $currentVersion = $vesionArray['version'];
            $currentPackage = $vesionArray['package'];
        } else {
            return ['isUpdate' => 0];
        }
        try{
            $ossClient = new OssClient($this->accessKeyId, $this->accessKeySecret, $this->endpoint);
        } catch(OssException $e) {
            return ['isUpdate' => 0];
        }
        // 获取最新版本号信息
        $versionInfo = $this->getNewVersion($ossClient, $this->bucket, $this->timeout);
        if (empty($versionInfo)) {
            return ['isUpdate' => 0];
        }
        $newPackage = isset($versionInfo['package']) ? $versionInfo['package'] : '';
        $newVersion = isset($versionInfo['version']) ? $versionInfo['version'] : '';
        if (empty($newPackage) || empty($newVersion)) {
            return ['isUpdate' => 0];
        }
        
        // 大版本号不对，不能升级
        if ((int)$currentVersion != $newVersion) {
            return ['isUpdate' => 0];
        }
        // 判断补丁包版本是否升级
        if ($currentPackage >= $newPackage) {
            return ['isUpdate' => 0];
        }
        $config = $this->getUpdateConfig();
        // 是否显示升级提醒弹框
        $isRemind = $this->isRemind($config, $own, $newPackage);
        // 获取版本描述信息
        $description = $this->getDescription($ossClient, $this->bucket, $this->timeout, $newPackage);
        // 是否屏蔽弹窗
        $isModal  = 1;
        if (envOverload('SHOW_MODAL', false) == 'false') {
            $isModal = 0;
        }
        return [
            'isUpdate' => $config['updating'] == 1 ? 0 : 1, 
            'version' => sprintf("%.1f", $currentVersion).'_'.$newPackage, 
            'description' => $description,
            'updateTime' => $this->getUpdateTime($config),
            'isUpdateLater' => (int) $this->getUpdateLaterOrNot($config),
            'isAutoUpdate' => (int)$config['autoUpdate'],
            'is_remind' => $isRemind ?? 0,
            'show_modal' => $isModal
        ];
    }
    /* 
    * @Title 是否显示升级弹窗
    *
    **/
    private function isRemind($config=[], $own, $newPackage) {
        // 是否自动更新
        $isAutoUpdate = $this->getAutoUpdateOrNot($config);
        // 是否稍后更新
        $isLaterUpdate = $this->getUpdateLaterOrNot($config);
        if ($isAutoUpdate || $isLaterUpdate) {
            // 开启了自动更新或稍后更新，管理员提醒一次，其他人不提醒
            if ($own['user_id'] == 'admin') {
                if (Cache::has(self::PACK_UPDATE_SIGN.'_'.$newPackage.'_admin')) {
                    return 0;
                }
                Cache::forever(self::PACK_UPDATE_SIGN.'_'.$newPackage.'_admin', 1);
                return 1;
            }
            return 0;
        } else {
            // 没有设置自动更新和稍后更新，就一直提醒
            return 1;
        }
    }

    public function getUpdateTime($config=[]) {
        if (empty($config)) {
            $config = $this->getUpdateConfig();
        }
        
        $updateHour = $config['updateLaterTime'];
        if ($updateHour == 0) {
            return date("Y-m-d", strtotime("+1 day")).' 00:00';
        } else {
            $hour = (int)$updateHour < 10 ? '0'.$updateHour.':00' : $updateHour . ':00';
            $date = $hour < date('H:i') ? date("Y-m-d", strtotime("+1 day")) : date('Y-m-d');
            return $date.' '.$hour;
        }
    }

    private function getUpdateConfig() {
        $documentRoot = dirname(getenv('DOCUMENT_ROOT'));
        if (empty($documentRoot)) {
            return ['laterUpdate' => 0, 'updateLaterTime' => 0, 'updating' => 0, 'autoUpdate' => 0];
        }
        $updateFilePath = $documentRoot . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'updateconfig.ini';
        if (!file_exists($updateFilePath)) {
            return ['laterUpdate' => 0, 'updateLaterTime' => 0, 'updating' => 0, 'autoUpdate' => 0];
        } else {
            $config = readLinesFromFile($updateFilePath);
            return [
                'laterUpdate' => $config['laterUpdate'] ?? 0, 
                'updateLaterTime' => $config['updateLaterTime'] ?? 0,
                'updating' => $config['updating'] ?? 0,
                'autoUpdate' => $config['autoUpdate'] ?? 0,
            ];
        }
    }

    public function getNewVersion($ossClient, $bucket, $timeout) {
        $packageFile = "10pack/version.json";
        try{
            $objectUrl = $ossClient->signUrl($bucket, $packageFile, $timeout);
        } catch(OssException $e) {
            return '';
        }

        // 可以使用代码来访问签名的URL，也可以输入到浏览器中进行访问。
        try{
            $request = new RequestCore($objectUrl);
        } catch(RequestCore_Exception $e) {
            \log::info($e->getMessage());
            return '';
        }
        
        // 生成的URL默认以GET方式访问。
        $request->set_method('GET');
        $request->add_header('Content-Type', '');
        try{
            $request->send_request();
        } catch (RequestCore_Exception $e) {
            \log::info($e->getMessage());
        }
        try{
            $res = new ResponseCore($request->get_response_header(), $request->get_response_body(), $request->get_response_code());
        } catch (OssException $e) {
            \log::info($e->getMessage());
        }
        

        if ($res->isOK()) {
            if (isset($res->body) && !empty($res->body)) {
                return json_decode($res->body, true);
            }
        }

        return '';
    }

    // 获取功能描述文件内容
    public function getDescription($ossClient, $bucket, $timeout, $newPackage) {
        $descriptionFile = "10pack/$newPackage/description.json";
        try{
            $exist = $ossClient->doesObjectExist($bucket, $descriptionFile);
        } catch(OssException $e) {
            return [];
        }
        if ($exist == false) {
            return [];
        }
        try{
            $objectUrl = $ossClient->signUrl($bucket, $descriptionFile, $timeout);
        } catch(OssException $e) {
            return [];
        }
        // 可以使用代码来访问签名的URL，也可以输入到浏览器中进行访问。
        $request = new RequestCore($objectUrl);
        // 生成的URL默认以GET方式访问。
        $request->set_method('GET');
        $request->add_header('Content-Type', '');
        try{
            $request->send_request();
        } catch (RequestCore_Exception $e) {
            \log::info($e->getMessage());
        }
        
        try{
            $res = new ResponseCore($request->get_response_header(), $request->get_response_body(), $request->get_response_code());
        } catch (OssException $e) {
            \log::info($e->getMessage());
        }
        // $request->send_request();
        // $res = new ResponseCore($request->get_response_header(), $request->get_response_body(), $request->get_response_code());
        if ($res->isOK()) {
            if (isset($res->body) && !empty($res->body)) {
                $body = json_decode($res->body, true);
                return $body ?? [];
            }
        }

        return [];
    }

    // 是否稍后更新
    public function getUpdateLaterOrNot($config=[]) {
        if (empty($config)) {
            $config = $this->getUpdateConfig();
        }
        
        return $config['laterUpdate'];
    }
    // 是否自动更新
    private function getAutoUpdateOrNot($config=[]) {
        if (empty($config)) {
            $config = $this->getUpdateConfig();
        }
        
        return $config['autoUpdate'];
    }
}
