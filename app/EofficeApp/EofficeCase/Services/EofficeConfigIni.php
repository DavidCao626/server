<?php

namespace App\EofficeApp\EofficeCase\Services;

class EofficeConfigIni
{
    // config.ini位置
    private $configIniDir;
    // 配置模板
    private $ConfigIniTemplate = __DIR__ . '/conf/case_demo_config.ini';

    public function __construct()
    {
        $config = include(__DIR__ . '/conf/config.php');
        $this->configIniDir = $config['eoffice_install_dir'] . '/bin/';
    }

    /**
     * 新增config.ini
     */
    public function addConfigIni($data)
    {
        $databaseName = $data['db_database'];
        $attachmentPathTo = $data['attachment_dir'];
        $redisDatabase = $data['redis_database'];
        $caseId = $data['case_id'];
        $accessName = $data['access_path'];
        $imNewPort = $data['im_new_port'];
        $newConfigFile = $this->configIniDir . 'config_' . $caseId . '.ini';
        $demoContent = file_get_contents($this->ConfigIniTemplate);
        $newConfigContent = sprintf($demoContent, $databaseName, $attachmentPathTo, $redisDatabase, $accessName, $imNewPort);
        file_put_contents($newConfigFile, $newConfigContent);
    }

    /**
     * 新增config_CASEID.ini
     */
    public function deleteConfigIni($caseId)
    {
        $configFile = $this->configIniDir . 'config_' . $caseId . '.ini';
        if (file_exists($configFile)) {
            @unlink($configFile);
        }
        return true;
    }

    /**
     * config.ini 是否存在
     */
    public function configIniExists($caseId)
    {
        $configFile = $this->configIniDir . 'config_' . $caseId . '.ini';
        if (file_exists($configFile)) {
            return true;
        }
        return false;
    }

    /**
     * 获取config.ini中的配置
     */
    public function getCaseConfigInfo($caseId)
    {
        $config = $this->getConfigIni($caseId);
        if ($config && isset($config['DB_DATABASE'])) {
            $configExists = $this->configIniExists($caseId);
            return [
                'case_id'         => $caseId,
                'db_database'     => $config['DB_DATABASE'],
                'attachment_dir'  => $config['ATTACHMENT_DIR'] ?? 'attachment',
                'access_path'     => $config['ACCESS_PATH'] ?? 'access',
                'redis_database'  => $config['REDIS_DATABASE'] ?? 0,
                'im_new_port'     => $config['IM_NEW_PORT'] ?? 0,
                'config_exists'   => $configExists,
                'case_exists'     => $configExists,
            ];
        }
        return [];
    }

    /**
     * 获取配置信息
     */
    private function getConfigIni($caseId)
    {
        $defaultConfig = getConfigIniData('');
        $currentConfig = getConfigIniData($caseId);
        return array_merge($defaultConfig, $currentConfig);
    }

    /**
     * 获取config_*.ini文件
     */
    public function getCaseIdList()
    {
        $caseIdList = [];
        $data = scandir($this->configIniDir);
        foreach ($data as $value) {
            if ($value != '.' && $value != '..') {
                if (strpos($value, 'config_') !== false) {
                    $caseId = str_replace('config_', '', $value);
                    $caseId = explode('.', $caseId);
                    if ($caseId && isset($caseId[0])) {
                        $caseIdList[] = $caseId[0];
                    }
                }
            }
        }
        return $caseIdList;
    }
}
