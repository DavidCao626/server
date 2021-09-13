<?php

namespace App\EofficeApp\EofficeCase\Services;

use App\EofficeApp\EofficeCase\Services\EofficeConfigIni;
use App\EofficeApp\EofficeCase\Services\lib\Utils;

class EofficeCaseList
{
    private $Config;
    private $eofficeCaseConfigIniDir;
    private $utils;

    public function __construct()
    {
        $this->Config = include(__DIR__ . '/conf/config.php');
        $this->utils = new Utils();
        $this->eofficeCaseConfigIniDir = $this->Config['eoffice_install_dir'] . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR;
    }

    /**
     * 分配案例id
     */
    public function allocEofficeCaseId($caseIdPrefix = '', $customCaseId = '')
    {
        if (is_string($customCaseId) && $customCaseId != '') {
            $patternCount = preg_match("/^[a-zA-Z]{1}[a-zA-Z0-9\-]{0,18}[a-zA-Z0-9]{1}$/", $customCaseId);
            if ($patternCount === 1) {
                // 符合、文件不存在
                if (!file_exists($this->eofficeCaseConfigIniDir . $customCaseId)) {
                    return $customCaseId;
                }
            }
            return false;
        }
        $caseId = $this->createCaseId($caseIdPrefix);
        $configName = 'config_' . $caseId . '.ini';
        while (file_exists($this->eofficeCaseConfigIniDir . $configName)) {
            $caseId = $this->createCaseId($caseIdPrefix);
            $configName = 'config_' . $caseId . '.ini';
        }
        // 此处是否应该直接生成config.ini文件？
        return $caseId;
    }

    /**
     * 创建一个案例id
     * */
    public function createCaseId($prefix = '')
    {
        $caseId = $this->utils->getDigitStr(4);
        if (is_string($prefix) && $prefix != '') {
            $caseId = $prefix . $caseId;
        }
        return $caseId;
    }

    /**
     * 获取案例列表
     * */
    public function getCaseList()
    {
        $eofficeConfigIni = new EofficeConfigIni();
        $caseIdList       = $eofficeConfigIni->getCaseIdList();
        $list = [];
        if (is_array($caseIdList) && count($caseIdList) > 0) {
            foreach ($caseIdList as $caseId) {
                if (!is_string($caseId) ||  $caseId == '') {
                    continue;
                }
                $configInfo = $eofficeConfigIni->getCaseConfigInfo($caseId);
                if (is_array($configInfo) && isset($configInfo['case_id'])) {
                    $list[] = $configInfo;
                }
            }
        }
        return $list;
    }
}
