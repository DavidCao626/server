<?php

namespace App\EofficeApp\EofficeCase\Services;

use App\EofficeApp\EofficeCase\Services\lib\Utils;

class Access
{
    // access资源路径
    private $accessDir;
    // 工具类
    private $utils;

    public function __construct()
    {
        $this->utils = new Utils();
        $config = include(__DIR__ . '/conf/config.php');
        $this->accessDir = $config['eoffice_install_dir'] . "/www/eoffice10/server/public/";
    }

    // 分配public资源文件夹
    public function allocAccessName($caseId)
    {
        $caseId = str_replace('-', '_', $caseId);
        return 'access_' . $caseId;
    }

    /**
     * 拷贝access 
     * */
    public function copyAccess($accessName, $copyToPath)
    {
        $this->utils->dir_mkdir($copyToPath);
        $accessDir = $this->accessDir . DIRECTORY_SEPARATOR . $accessName;
        if (file_exists($accessDir)) {
            $this->utils->dir_copy($accessDir, $copyToPath);
        }
        return true;
    }

    /**
     * 新增access
     * */
    public function addAccess($newAccessName, $extractDir)
    {
        $accessPathTo = $this->accessDir . DIRECTORY_SEPARATOR . $newAccessName;
        // win
        @exec("move /y {$extractDir}access {$accessPathTo}");
        // linux
        // @exec("mv {$extractDir}access {$accessPathTo}");

        // 修改权限
        // @exec("chmod -R 777 {$accessPathTo}");
        return true;
    }

    // 删除
    public function deleteAccess($caseId)
    {
        $eofficeConfigIni = new EofficeConfigIni();
        $caseInfo         = $eofficeConfigIni->getCaseConfigInfo($caseId);
        $accessName       = $caseInfo['access_path'] ?? '';

        if (!is_string($accessName) || empty($accessName)) {
            return false;
        }

        $access = $this->accessDir . DIRECTORY_SEPARATOR . $accessName;
        if (file_exists($access)) {
            $this->utils->dir_del($access);
        }
        return true;
    }

    /**
     * 获取大小
     */
    public function getSize($accessName)
    {
        $access = $this->accessDir . DIRECTORY_SEPARATOR . $accessName;
        if (file_exists($access)) {
            return $this->utils->dirSize($access);
        }
        return 0;
    }
}
