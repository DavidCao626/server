<?php

namespace App\EofficeApp\EofficeCase\Services;

use Illuminate\Support\Facades\DB;
use App\EofficeApp\EofficeCase\Services\lib\Utils;

class Langs
{
    // eoffice10目录
    private $eoffice10Dir;
    // 工具类
    private $utils;

    public function __construct()
    {
        $this->utils = new Utils();
        $config = include(__DIR__ . '/conf/config.php');
        $this->eoffice10Dir = $config['eoffice_install_dir'] . DS . 'www' . DS . 'eoffice10';
    }

    // 拷贝数据库文件
    public function copyLangs($copyToPath)
    {
        $clientWebLangsDir    = 'client' . DS . 'web' . DS . 'assets' . DS . 'langs';
        $clientMobileLangsDir = 'client' . DS . 'mobile' . DS . 'assets' . DS . 'langs';
        $serverLangsDir       = 'server' . DS . 'resources' . DS . 'lang';

        $this->utils->dir_copy($this->eoffice10Dir . DS . $clientWebLangsDir, $copyToPath . DS . $clientWebLangsDir);
        $this->utils->dir_copy($this->eoffice10Dir . DS . $clientMobileLangsDir, $copyToPath . DS . $clientMobileLangsDir);
        $this->utils->dir_copy($this->eoffice10Dir . DS . $serverLangsDir, $copyToPath . DS . $serverLangsDir);
        return true;
    }
}
