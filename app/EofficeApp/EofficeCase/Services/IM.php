<?php

namespace App\EofficeApp\EofficeCase\Services;

class IM
{
    public $Config;
    private $imBasePort = 12000;
    private $eofficeCaseList = 'App\EofficeApp\EofficeCase\Services\EofficeCaseList';
    // 配置模板
    private $imNginxConfigFile = __DIR__ . '/conf/im_config.conf';
    private $pm2ConfigFile = __DIR__ . '/conf/pm2.conf.json.format';
    public function __construct()
    {
        $this->Config = include(__DIR__ . '/conf/config.php');
    }

    // 分配数据库文件夹,如果数据库存在则删除
    public function allocIMPort($config)
    {
        if (!is_array($config)) {
            return false;
        }
        $imMaxPort = $this->imBasePort + count($config) + 1;
        $allIMPort = [];
        for ($i = $this->imBasePort; $i <= $imMaxPort; $i++) {
            $allIMPort[] = $i;
        }
        $usedPort = [];
        if (count($config) > 0) {
            foreach ($config as $value) {
                $imNewPort = $value['im_new_port'] ?? 0;
                if (is_numeric($imNewPort) && $imNewPort > 0) {
                    $usedPort[] = intval($imNewPort);
                }
            }
        }

        $canUsePort = array_diff($allIMPort, $usedPort);
        return array_shift($canUsePort);
    }

    /**
     * 分配即时通讯相关配置文件
     */
    public function addIMConfigFile($caseId, $imNewPort)
    {
        try {
            $imConfigDir = $this->Config['im_proxy_config_dir'];
            $imConfigFileName = $caseId . '.conf';
            $imConfigFileContentFormat = file_get_contents($this->imNginxConfigFile);
            $imConfigFileContent = sprintf($imConfigFileContentFormat, $caseId, $imNewPort);
            file_put_contents($imConfigDir . $imConfigFileName, $imConfigFileContent);
        } catch(\Exception $e) {

        }
        return true;
    }

    /**
     * 删除即时通讯相关配置文件
     */
    public function deleteIMConfigFile($caseId)
    {
        $imConfigDir = $this->Config['im_proxy_config_dir'];
        $imConfigFileName = $caseId . '.conf';
        if (file_exists($imConfigDir . $imConfigFileName)) {
            @unlink($imConfigDir . $imConfigFileName);
        }
        return true;
    }

    /**
     * 创建pm2配置
     */
    public function addPM2Config($caseId)
    {
        $oldPm2ConfigFile = [];
        if (file_exists($this->Config['pm2_config_file'])) {
            $oldPm2ConfigFile = json_decode(file_get_contents($this->Config['pm2_config_file']), true);
        }
        $oldPm2Apps = $oldPm2ConfigFile['apps'] ?? [];
        $allCaseList = app($this->eofficeCaseList)->getCaseList();
        if (is_array($allCaseList) && count($allCaseList) > 0) {
            foreach ($allCaseList as $case) {
                if (isset($case['im_new_port']) && $case['im_new_port'] > 0) {
                    if ($caseId == $case['case_id']) {
                        $add = true;
                        // 确保不重复添加
                        if (is_array($oldPm2Apps) && count($oldPm2Apps) > 0) {
                            foreach ($oldPm2Apps as $old) {
                                if ($old['name'] == $caseId) {
                                    $add = false;
                                }
                            }
                        }
                        if ($add) {
                            $this->addIMConfigFile($caseId, $case['im_new_port']);
                            $oldPm2Apps[] = [
                                'name'   => $case['case_id'],
                                'script' => $this->Config['eoffice_install_dir'] . '/www/eoffice10/server/nodejs/server.js',
                                'args'   => $case['case_id'],
                            ];
                            try {
                                $fileContent = sprintf(file_get_contents($this->pm2ConfigFile), json_encode($oldPm2Apps));
                                file_put_contents($this->Config['pm2_config_file'], $fileContent);
                            } catch (\Exception $e) {
                            }
                        }
                    }
                }
            }
        }
        return true;
    }
}
