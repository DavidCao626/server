<?php


namespace App\EofficeApp\Invoice\Services;

use App\EofficeApp\Base\BaseService;
use Cache;

/**
 * Class InvoiceManageService 发票管理服务 --- 相关配置及流程集成配置
 * @package App\EofficeApp\Invoice\Services
 */
class InvoiceManageService extends BaseService
{
    public function __construct(
    ) {
        parent::__construct();
        $this->invoiceManageParamsRepositories = 'App\EofficeApp\Invoice\Repositories\InvoiceManageParamsRepositories';
        $this->invoiceFlowSettingRepositories = 'App\EofficeApp\Invoice\Repositories\InvoiceFlowSettingRepositories';
        $this->invoiceFlowNodeActionSettingRepositories = 'App\EofficeApp\Invoice\Repositories\InvoiceFlowNodeActionSettingRepositories';
    }

    public function getInvoiceManageParams()
    {
        $params =  app($this->invoiceManageParamsRepositories)->getParam();
        if (is_object($params)) {
            $params = $params->toArray();
        }
        return array_column($params, 'param_value', 'param_key');
    }

    public function saveInvoiceManageParams($params)
    {
        if (count($params) != count($params, 1)) {
            $params = [$params];
        }
        foreach ($params as $key => $val) {
            app($this->invoiceManageParamsRepositories)->setParams($key, $val);
        }
        return true;
    }

    /** 获取发票流程集成配置列表
     * @param $params
     * @return array
     */
    public function getFlowSettings($params)
    {
        $data = $this->parseParams($params);
        $returnData = $this->response(app($this->invoiceFlowSettingRepositories), 'getCount', 'getList', $data);
        return $returnData;
    }

    public function getFlowSetting($settingId, $param)
    {
        $returnData = app($this->invoiceFlowSettingRepositories)->getOneSetting($settingId, $param);
        return $returnData ? $returnData->toArray() : [];
    }

    public function addFlowSetting($params, $user)
    {
        $params = $this->handleFlowSettingParams($params);
        $workflow = $params['workflow'] ?? [];
        $nodeAction = $params['nodeAction'] ?? [];
        if (!$workflow) {
            return ['code' => ['get_flow_config_data_failed', 'invoice']];
        }
        $workflow['creator'] = $user['user_id'];
        // 唯一性验证
        $exist = app($this->invoiceFlowSettingRepositories)->getOneSetting(0, ['workflow_id' => [$params['workflow']['workflow_id']]]);
        if ($exist) {
            return ['code' => ['already_integrate', 'invoice']];
        }
        $workflowSetting = app($this->invoiceFlowSettingRepositories)->insertData($workflow);
        if ($workflowSetting && $workflowSetting->setting_id) {
            $nodeAction = $this->addSettingId($nodeAction, $workflowSetting->setting_id, $workflowSetting->workflow_id);
            if ($nodeAction) {
                app($this->invoiceFlowNodeActionSettingRepositories)->insertMultipleData($nodeAction);
            }
            return true;
        }
        return ['code' => ['create_failed', 'invoice']];
    }

    public function editFlowSetting($params, $settingId, $user)
    {
        $params = $this->handleFlowSettingParams($params, $settingId);
        $workflow = $params['workflow'] ?? [];
        $nodeAction = $params['nodeAction'] ?? [];
        if (!$workflow) {
            return ['code' => ['get_flow_config_data_failed', 'invoice']];
        }
//        $workflow['creator'] = $user['user_id'];
        $workflowSetting = app($this->invoiceFlowSettingRepositories)->updateData($workflow, ['setting_id' => [$settingId]]);
        if ($workflowSetting) {
            // 删除之前配置的 新增加
            if ($nodeAction) {
                $nodeAction = $this->addSettingId($nodeAction, $settingId, $workflow['workflow_id']);
                app($this->invoiceFlowNodeActionSettingRepositories)->deleteByWhere(['setting_id' => [$settingId]]);
                app($this->invoiceFlowNodeActionSettingRepositories)->insertMultipleData($nodeAction);
            }
            // 如果设置默认发票报销流程
            if (isset($workflow['is_default'])) {
                // 将之前的默认设置0 并重新默认配置缓存
                if ($workflow['is_default'] == 1) {
                    $workflowSetting = app($this->invoiceFlowSettingRepositories)->updateData(['is_default' => 0], ['setting_id' => [$settingId, '!='], 'is_default' => [1]]);
                    Cache::set('invoice_flow_default_setting', app($this->invoiceFlowSettingRepositories)->getDetail($settingId));
                } else {
                    Cache::forget('invoice_flow_default_setting');
                }
            }
            return true;
        }
        return ['code' => ['edit_failed', 'invoice']];

    }

    public function deleteFlowSetting($settingId, $user)
    {
        $setting = app($this->invoiceFlowSettingRepositories)->getDetail($settingId);
        if (!$setting) {
            return ['code' => ['get_config_failed', 'invoice']];
        }
        //TODO  判断流程是否有使用当前发票集成配置
//        if (app($this->qiyuesuoSettingRepository)->getSettingRelatedFlowUsing($settingId)) {
////            return ['code' => ['0x093508', 'electronicsign']];
////        }
        if ($setting->delete()) {
            //TODO 删除签署信息和节点操作权限
            app($this->invoiceFlowNodeActionSettingRepositories)->deleteByWhere(['setting_id' => [$settingId]]);
            return true;
        }
        return ['code' => ['delete_failed', 'invoice']];
    }

    public function handleFlowSettingParams($params, $settingId = 0)
    {
        $workflow = array_intersect_key($params, array_flip(app($this->invoiceFlowSettingRepositories)->getTableColumns()));
        $flowId = $workflow['workflow_id'] ?? 0;
        $nodeAction = $params['actions'] ?? [];
        if ($settingId && $nodeAction) {
            $nodeAction = $this->addSettingId($nodeAction, $settingId, $flowId);
        }
        return compact('workflow', 'nodeAction');
    }

    private function addSettingId($array, $settingId, $flowId = 0)
    {
        foreach ($array as $key => $value) {
            if (array_key_exists('action_setting_id', $value)) {
                unset($array[$key]['action_setting_id']);
            }
            if ($value['node_id'] && $value['action']) {
                $array[$key]['trigger_time'] = $array[$key]['trigger_time'] ?? 0;
                $array[$key]['back'] = $array[$key]['back'] ?? 0;
                $array[$key]['setting_id'] = $settingId;
                if ($flowId) {
                    $array[$key]['workflow_id'] = $flowId;
                }
            } else {
                unset($array[$key]);
            }
        }
        return $array;
    }

    public function getUserSyncTime()
    {
        $params = $this->getInvoiceManageParams();
        if (isset($params['timed_sync']) && isset($params['sync_time']) && $params['timed_sync'] && $params['sync_time']) {
            return $params['sync_time'];
        }
        return false;
    }

    public function checkFlowRelation($param)
    {
        $flowId = $param['flowId'] ?? '';
        $settingId = $param['settingId'] ?? '';
        $type = $param['type'] ?? 'add';
        if ($type == 'edit') {
            return true;
        }
        //当前定义流程ID已集成设置  一对一关系
        if ($result = app($this->invoiceFlowSettingRepositories)->getOneSettingByFlowId($settingId, $flowId)) {
            return ['code' => ['has_integrate', 'invoice']];
        }
        return true;
    }
    /**
     * 根据流程id返回流程集成的发票云配置
     *
     * @param [type] $flowId
     *
     * @return void
     * @author yml
     */
    public function exportInvoiceFlowConfig($flowId)
    {
        $result = app($this->invoiceFlowSettingRepositories)->getOneSetting(0, ['workflow_id' => [$flowId]]);
        return $result ? $result->toArray() : [];
    }
    /**
     * 导入发票管理相关流程时同步相关配置信息
     *
     * @param integer $newFlowId
     * @param array $flowData
     * @param array $nodeIdMap
     * @param [type] $user
     *
     * @return void
     * @author yml
     */
    public function importInvoiceFlowConfig(int $newFlowId, array $flowData, array $nodeIdMap, $user)
    {
        $version = $flowData['version'] ?? '';
        $invoiceConfig = $flowData['invoice'] ?? [];
        if ($invoiceConfig) {
            // 流程字段配置
            $invoiceConfig['workflow_id'] = $newFlowId;
            $params = $this->handleFlowSettingParams($invoiceConfig);
            $workflow = $params['workflow'] ?? [];
            if (!$workflow) {
                return false;
            }
            unset($workflow['setting_id']);
            unset($workflow['created_at']);
            unset($workflow['updated_at']);
            unset($workflow['is_default']);
            // 流程节点配置
            $nodeAction = $params['nodeAction'] ?? [];
            if ($nodeAction) {
                foreach($nodeAction as $actionKey => $action){
                    $nodeAction[$actionKey]['node_id'] = $nodeIdMap[$action['node_id']];
                }
            }
            $workflow['creator'] = $user['user_id'];
            $workflowSetting = app($this->invoiceFlowSettingRepositories)->insertData($workflow);
            if ($workflowSetting && $workflowSetting->setting_id) {
                if ($nodeAction) {
                    $nodeAction = $this->addSettingId($nodeAction, $workflowSetting->setting_id, $workflowSetting->workflow_id);
                    app($this->invoiceFlowNodeActionSettingRepositories)->insertMultipleData($nodeAction);
                }
            }
        }
        return true;
    }

    public function getSettingsOnlyIdName($params) 
    {
        return app($this->invoiceFlowSettingRepositories)->getSettingsOnlyIdName();
    }

    public function getDefaultSetting()
    {
        if ($default = Cache::get('invoice_flow_default_setting')) {
        } else {
            $default = app($this->invoiceFlowSettingRepositories)->getOneFieldInfo(['is_default' => [1]]);
            if ($default) {
                Cache::set('invoice_flow_default_setting', $default);
            }
        }
        return $default;
    }
}