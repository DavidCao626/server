<?php

namespace App\EofficeApp\ElectronicSign\Services;

use App\EofficeApp\Base\BaseService;
use App\Jobs\SyncQiyuesuoJob;
use Illuminate\Support\Facades\Cache;
use Queue;
use Eoffice;

/**
 * 电子签署 service
 */
class ElectronicSignService extends BaseService
{
	public function __construct()
	{
		parent::__construct();
		$this->qiyuesuoServerRepository = 'App\EofficeApp\ElectronicSign\Repositories\QiyuesuoServerRepository';
		$this->qiyuesuoSettingRepository = 'App\EofficeApp\ElectronicSign\Repositories\QiyuesuoSettingRepository';
		$this->qiyuesuoSettingOperationInfoRepository = 'App\EofficeApp\ElectronicSign\Repositories\QiyuesuoSettingOperationInfoRepository';
		$this->qiyuesuoSettingSignInfoRepository = 'App\EofficeApp\ElectronicSign\Repositories\QiyuesuoSettingSignInfoRepository';
		$this->qiyuesuoService = 'App\EofficeApp\ElectronicSign\Services\QiyuesuoService';
		$this->flowRunRelationQysContractRepository = 'App\EofficeApp\ElectronicSign\Repositories\FlowRunRelationQysContractRepository';
		$this->flowRunService = 'App\EofficeApp\Flow\Services\FlowRunService';
		$this->attachmentService = 'App\EofficeApp\Attachment\Services\AttachmentService';
		$this->userService = 'App\EofficeApp\User\Services\UserService';
		$this->qiyuesuoConfigRepository = 'App\EofficeApp\ElectronicSign\Repositories\QiyuesuoConfigRepository';
		$this->flowOthersRepository = 'App\EofficeApp\Flow\Repositories\FlowOthersRepository';
		$this->flowRunRelationQysContractInfoRepository = 'App\EofficeApp\ElectronicSign\Repositories\FlowRunRelationQysContractInfoRepository';
		$this->flowRunRelationQysContractSignInfoRepository = 'App\EofficeApp\ElectronicSign\Repositories\FlowRunRelationQysContractSignInfoRepository';
		$this->companyService = 'App\EofficeApp\System\Company\Services\CompanyService';
		// 契约锁相关资源任务
		$this->qiyuesuoRelatedResourceTaskRepository = 'App\EofficeApp\ElectronicSign\Repositories\QiyuesuoRelatedResourceTaskRepository';
		$this->qiyuesuoSealApplyAuthLogRepository = 'App\EofficeApp\ElectronicSign\Repositories\QiyuesuoSealApplyAuthLogRepository';
		$this->qiyuesuoSealApplyCreateDocLogRepository = 'App\EofficeApp\ElectronicSign\Repositories\QiyuesuoSealApplyCreateDocLogRepository';
		$this->qiyuesuoSealApplyLogRepository = 'App\EofficeApp\ElectronicSign\Repositories\QiyuesuoSealApplyLogRepository';
		$this->qiyuesuoSealApplySettingRepository = 'App\EofficeApp\ElectronicSign\Repositories\QiyuesuoSealApplySettingRepository';
		// 契约锁物理用印集成流程触发节点设置
		$this->qiyuesuoSealApplySettingOutsendInfoRepository = 'App\EofficeApp\ElectronicSign\Repositories\QiyuesuoSealApplySettingOutsendInfoRepository';
		// 契约锁物理用印集成用印后文件附件关联表
		$this->attachmentRelataionQiyuesuoSealApplyImageRepository = 'App\EofficeApp\ElectronicSign\Repositories\AttachmentRelataionQiyuesuoSealApplyImageRepository';
		// 公有云接口服务
		$this->qiyuesuoPublicCloudService = 'App\EofficeApp\ElectronicSign\Services\QiyuesuoPublicCloudService';
		// 契约锁电子合同相关日志
		$this->qiyuesuoContractLogRepository = 'App\EofficeApp\ElectronicSign\Repositories\QiyuesuoContractLogRepository';
		// 流程信息，flow_表
		$this->flowFormService = 'App\EofficeApp\Flow\Services\FlowFormService';
        $this->flowRunRepository = 'App\EofficeApp\Flow\Repositories\FlowRunRepository';
        $this->flowPermissionService = 'App\EofficeApp\Flow\Services\FlowPermissionService';
	}

	/**
	 * 获取契约锁服务列表
	 *
	 * @param [type] $params
	 * @return array
	 */
	public function getServerList($params)
	{
		$params = $this->parseParams($params);

		$response = isset($params['response']) ? $params['response'] : 'both';
		$list = [];

		if ($response == 'both' || $response == 'count') {
			$count = app($this->qiyuesuoServerRepository)->getServersCount($params);
		}

		if (($response == 'both' && $count > 0) || $response == 'data') {
			foreach (app($this->qiyuesuoServerRepository)->getServersList($params) as $new) {
				$list[] = $new;
			}
		}

		return $response == 'both' ? ['total' => $count, 'list' => $list] : ($response == 'data' ? $list : $count);
	}

	/**
	 * 契约锁新建服务
	 *
	 * @param [type] $param
	 * @return array
	 */
	public function addServer($params)
	{
		$server_data = [
			'serverName' => $params['serverName'],
			'serverUrl' => $params['serverUrl'],
			'accessKey' => $params['accessKey'],
			'accessSecret' => $params['accessSecret'],
			'serverType' => $params['serverType'],
			'goPage' => $params['goPage'] ?? '',
			'encryptKey' => $params['encryptKey'] ?? '',
			'integratedLoginId' => $this->defaultValue('integratedLoginId', $params, ""),
			'isQYSLogin' => $this->defaultValue('isQYSLogin', $params, ""),
			'create_source' => $params['create_source'] ?? 0,
		];
		if (!app($this->qiyuesuoSettingRepository)->checkUrl($params['serverUrl'])) {
			return ['code' => ['0x093516', 'electronicsign']];
		}
		if ($result = app($this->qiyuesuoServerRepository)->insertDataBatch($server_data)) {
			return ['serverId' => $result->serverId];
		}
		return ['code' => ['0x093501', 'electronicsign']];
	}

    /**
     * 修改服务设置
     *
     * @param [type] $serverId
     * @param [type] $param
     * @return array
     */
    public function editServer($serverId, $params)
    {
        $server = app($this->qiyuesuoServerRepository)->getDetail($serverId);
        if (!$server) {
            return ['code' => ['0x093502', 'electronicsign']];
        }
        if (!app($this->qiyuesuoSettingRepository)->checkUrl($params['serverUrl'])) {
            return ['code' => ['0x093516', 'electronicsign']];
        }
        $server_data = [
            'serverName' => $params['serverName'],
            'serverUrl' => $params['serverUrl'],
            'accessKey' => $params['accessKey'],
            'accessSecret' => $params['accessSecret'],
            'serverType' => $params['serverType'],
            'goPage' => $params['goPage'] ?? '',
            'encryptKey' => $params['encryptKey'] ?? '',
            'integratedLoginId' => $this->defaultValue('integratedLoginId', $params, ""),
            'isQYSLogin' => $this->defaultValue('isQYSLogin', $params, 0),
            'fileMaxSize' => $params['fileMaxSize'] ?? 10,
        ];
        if (app($this->qiyuesuoServerRepository)->updateData($server_data, ['serverId' => $serverId])) {
            if ($server_data['isQYSLogin'] == 1) {
                app($this->qiyuesuoServerRepository)->updateData(['isQYSLogin' => 0], ['isQYSLogin' => [1], 'serverId' => [$serverId, '<>']]);
            }
            return true;
        }
        return ['code' => ['0x093503', 'electronicsign']];
    }

    /**
     * 获取服务设置详情
     *
     * @param [type] $serverId
     * @return array
     */
    public function getServerDetail($serverId, $where = [])
    {
        if ($serverId) {
            $server = app($this->qiyuesuoServerRepository)->getDetail($serverId);
        } else {
            $server = new \stdClass;
            if (!$where) {
                $server = app($this->qiyuesuoServerRepository)->getOneFieldInfo([]);
            } else {
                $server = app($this->qiyuesuoServerRepository)->getOneFieldInfo($where);
            }
        }
        if (!$server) {
            return ['code' => ['0x093502', 'electronicsign']];
        }
        return $server;
    }

	/**
	 * 删除服务设置
	 *
	 * @param [type] $serverId
	 * @return array
	 */
	public function deleteServer($serverId)
	{
		$server = app($this->qiyuesuoServerRepository)->getDetail($serverId);
		if (!$server) {
			return ['code' => ['0x093502', 'electronicsign']];
		}
		//todo  已被集成设置使用是否可删除的判断
		if (app($this->qiyuesuoSettingRepository)->bindServerCount($serverId)) {
			return ['code' => ['0x093505', 'electronicsign']];
		}
		if ($server->delete()) {
			return true;
		}
		return ['code' => ['0x093504', 'electronicsign']];
	}

	/**
	 * 【契约锁服务设置】 获取开关&加密串
	 *
	 * @return
	 */
	public function getServerBaseInfo($params = [])
	{
		$result = app($this->qiyuesuoConfigRepository)->getItem(['qys_on_off', 'encrypt_key']);
		if ($result) {
			$return = [];
			foreach ($result as $key => $val) {
				$return[$val['paramKey']] = $val['paramValue'];
			}
			return $return;
		}
		return [];
	}

	/**
	 * 【契约锁服务设置】 保存开关&加密串
	 *
	 * @param [type] $params [encrypt_key ; qys_on_off]
	 * @return
	 */
	public function editServerBaseInfo($params)
	{
		// qys_on_off 会传 on off
		// encrypt_key 要验证非空(前端也会验证) --- 公有云无需加密秘钥
		if (!$params) {
			return ['code' => ['0x093517', 'electronicsign']];
		}
		if (!isset($params['qys_on_off']) || !in_array($params['qys_on_off'], ['on', 'off'])) {
			return ['code' => ['0x093525', 'electronicsign']];
		}
		// if (!isset($params['encrypt_key']) || empty($params['encrypt_key'])) {
		//     return ['code' => ['0x093526', 'electronicsign']];
		// }
		$data = [
			'qys_on_off' => $params['qys_on_off'] == 'on' ? 1 : 0,
			'encrypt_key' => $params['encrypt_key'] ?? '',
		];
		foreach ($data as $key => $val) {
			$config = app($this->qiyuesuoConfigRepository)->getItem($key);
			if ($config && !empty($config->toArray())) {
				$res = app($this->qiyuesuoConfigRepository)->updateData(['paramKey' => $key, 'paramValue' => $val], ['paramKey' => $key]);
			} else {
				$res = app($this->qiyuesuoConfigRepository)->insertData(['paramKey' => $key, 'paramValue' => $val]);
			}
			// if (!$res) {
			//     return ['code' => ['0x093527', 'electronicsign']];
			// }
		}
		return true;
	}

	/**
	 * 获取契约锁集成设置列表
	 *
	 * @param [type] $params
	 * @return array
	 */
	public function getIntegrationList($params)
	{
		$params = $this->parseParams($params);

		$response = isset($params['response']) ? $params['response'] : 'both';
		$list = [];

		if ($response == 'both' || $response == 'count') {
			$count = app($this->qiyuesuoSettingRepository)->getSettingCount($params);
		}

		if (($response == 'both' && $count > 0) || $response == 'data') {
			foreach (app($this->qiyuesuoSettingRepository)->getSettingList($params) as $new) {
				$list[] = $new;
			}
		}

		return $response == 'both' ? ['total' => $count, 'list' => $list] : ($response == 'data' ? $list : $count);
	}

	/**
	 * 新建契约锁集成设置
	 *
	 * @param [type] $data
	 * @return array
	 */
	public function addIntegration($params)
	{
		$data = $this->integrationHandleData($params);
		if (isset($data['code'])) {
			return $data;
		}
		if ($setting = app($this->qiyuesuoSettingRepository)->insertData($data['setting_data'])) {
			//保存签署信息
			if ($data['sign_info']) {
				$data['sign_info'] = $this->addSettingId($data['sign_info'], $setting->settingId);
				app($this->qiyuesuoSettingSignInfoRepository)->insertMultipleData($data['sign_info']);
			}
			//保存节点操作权限
			if ($data['operation_info']) {
				$data['operation_info'] = $this->addSettingId($data['operation_info'], $setting->settingId);
				app($this->qiyuesuoSettingOperationInfoRepository)->insertMultipleData($data['operation_info']);
			}

            if ($data['outsend_info']) {
                $data['outsend_info'] = $this->addSettingId($data['outsend_info'], $setting->settingId);
                foreach ($data['outsend_info'] as $key => $datum){
                    $data['outsend_info'][$key]['type'] = 'contract';
                }
                app($this->qiyuesuoSealApplySettingOutsendInfoRepository)->insertMultipleData($data['outsend_info']);
            }
            if (isset($params['workflowId'])) {
                $this->saveIntegrationChangeFlowLable($params['workflowId']);
                Cache::put('qiyuesuo_integration_'.$params['workflowId'], true, 24*60*60);;
            }
            return ['settingId' => $setting->settingId];
        } else {
            return ['code' => ['0x093507', 'electronicsign']];
        }
    }

    /**
     * 编辑契约锁集成设置
     *
     * @param [type] $settingId
     * @param [type] $data
     * @return array
     */
    public function editIntegration($settingId, $params)
    {
        $data = $this->integrationHandleData($params, $settingId);
        if (isset($data['code'])) {
            return $data;
        }
        if (app($this->qiyuesuoSettingRepository)->updateData($data['setting_data'], ['settingId' => $settingId])) {
            //TODO 删除之前的签署信息和节点操作权限 触发操作
            app($this->qiyuesuoSettingSignInfoRepository)->deleteByWhere(['settingId' => [$settingId]]);
            app($this->qiyuesuoSettingOperationInfoRepository)->deleteByWhere(['settingId' => [$settingId]]);
            app($this->qiyuesuoSealApplySettingOutsendInfoRepository)->deleteByWhere(['settingId' => [$settingId], 'type' => ['contract']]);
            //保存签署信息
            if ($data['sign_info']) {
                $data['sign_info'] = $this->addSettingId($data['sign_info'], $settingId);
                app($this->qiyuesuoSettingSignInfoRepository)->insertMultipleData($data['sign_info']);
            }
            //保存节点操作权限
            if ($data['operation_info']) {
                $data['operation_info'] = $this->addSettingId($data['operation_info'], $settingId);
                app($this->qiyuesuoSettingOperationInfoRepository)->insertMultipleData($data['operation_info']);
            }
            if ($data['outsend_info']) {
                $data['outsend_info'] = $this->addSettingId($data['outsend_info'], $settingId);
                foreach ($data['outsend_info'] as $key => $datum){
                    $data['outsend_info'][$key]['type'] = 'contract';
                }
                app($this->qiyuesuoSealApplySettingOutsendInfoRepository)->insertMultipleData($data['outsend_info']);
            }
            if (isset($params['workflowId'])) {
                $this->saveIntegrationChangeFlowLable($params['workflowId']);
            }
            return ['settingId' => $settingId];
        } else {
            return ['code' => ['0x093512', 'electronicsign']];
        }
    }

	/**
	 * 获取契约锁集成设置详情
	 *
	 * @param [type] $settingId
	 * @return array
	 */
	public function getIntegrationDetail($settingId)
	{
		$setting = app($this->qiyuesuoSettingRepository)->getSettingDetail($settingId);
		if (!$setting) {
			return ['code' => ['0x093506', 'electronicsign']];
		}
		return $setting;
	}

	/**
	 * 根据定义流程ID获取契约锁集成或物理用印集成设置详情
	 *
	 * @param [type] $settingId
	 * @return array
	 */
	public function getIntegrationDetailByFlowId($flowId)
	{
		$setting = app($this->qiyuesuoSettingRepository)->getSettingDetailByFlowId($flowId);
		$sealSetting = app($this->qiyuesuoSealApplySettingRepository)->getSettingByFlowId($flowId);
		if (!$setting && !$sealSetting) {
			return;
		}
		return $setting ?? $sealSetting;
	}

    /**
     * 删除某个契约锁集成配置
     *
     * @param [type] $settingId
     * @return array
     */
    public function deleteIntegration($settingId)
    {
        $setting = app($this->qiyuesuoSettingRepository)->getDetail($settingId);
        if (!$setting) {
            return ['code' => ['0x093506', 'electronicsign']];
        }
        //TODO  判断流程是否有使用当前契约锁集成配置
        // if (app($this->qiyuesuoSettingRepository)->getSettingRelatedFlowUsing($settingId)) {
        //     return ['code' => ['0x093508', 'electronicsign']];
        // }
        if ($setting->delete()) {
            //删除流程设置中默认的选中标签
            $this->deleteIntegrationChangeFlowLable($setting->workflowId);
            //TODO 删除签署信息和节点操作权限
            app($this->qiyuesuoSettingSignInfoRepository)->deleteByWhere(['settingId' => [$settingId]]);
            app($this->qiyuesuoSettingOperationInfoRepository)->deleteByWhere(['settingId' => [$settingId]]);
            app($this->qiyuesuoSealApplySettingOutsendInfoRepository)->deleteByWhere(['settingId' => [$settingId], 'type' => ['contract']]);
            Cache::forget('qiyuesuo_integration_'.$setting->workflowId);
            return true;
        }
        return ['code' => ['0x093509', 'electronicsign']];
    }

	/**
	 * 查询运行流程id对应合同id
	 *
	 * @param $runId
	 * @return mixed
	 */
	public function getContractIdbyFlowRunId($runId)
	{
		// 没查到直接返回空，前端处理返回值
		$result = app($this->flowRunRelationQysContractRepository)->getFieldValue('contractId', ['runId' => $runId]);
		return $result;
	}

	/**
	 * 处理数据 没有传值的默认值
	 *
	 * @param [type] $key
	 * @param [type] $data
	 * @param [type] $default
	 * @return
	 */
	private function defaultValue($key, $data, $default)
	{
		return isset($data[$key]) ? $data[$key] : $default;
	}
	/**
	 * 检测流程是否已集成电子合同和物理用印
	 *
	 * @param [type] $params
	 * @param integer $settingId
	 *
	 * @return void
	 * @author yml
	 */
	public function checkFlowRelation($flowId, $settingId = 0, $data)
	{
		$type = $data['type'] ?? 'add';
		$integrationType = $data['integration_type'] ?? '';
		if ($type == 'edit') {
			return true;
		}
		//当前定义流程ID已集成设置  一对一关系
		if ($integrationType == 'contract' && $result = app($this->qiyuesuoSettingRepository)->getSettingDetailByFlowId($flowId, false, $settingId)) {
			return ['code' => ['has_integrate_contract', 'electronicsign']];
		}
		// 验证当前流程是否已集成契约锁物理用印
		if ($integrationType == 'seal_apply' && app($this->qiyuesuoSealApplySettingRepository)->getFieldInfo(['workflowId' => $flowId])) {
			return ['code' => ['has_integrate_seal_apply', 'electronicsign']];
		}
		return true;
	}

	/**
	 * 处理集成设置的数据
	 *
	 * @param array $params
	 * @param integer $settingId
	 * @return array
	 */
	private function integrationHandleData($params, $settingId = 0)
	{
		//当前定义流程ID已集成设置  一对一关系
		if ($result = app($this->qiyuesuoSettingRepository)->getSettingDetailByFlowId($params['workflowId'], false, $settingId)) {
			return ['code' => ['0x093514', 'electronicsign']];
		}
		// 验证当前流程是否已集成契约锁物理用印
		//        $sealSetting = app($this->qiyuesuoSealApplySettingRepository)->getFieldInfo(['workflowId' => $params['workflowId']]);
		//        if ($sealSetting) {
		//            return ['code' => ['has_integrate_seal_apply', 'electronicsign']];
		//        }
		$data['setting_data'] = array_intersect_key($params, array_flip(app($this->qiyuesuoSettingRepository)->getTableColumns()));
		$data['sign_info'] = $data['operation_info'] = $data['outsend_info'] = [];
		$signInfo = isset($params['signInfo']) ? $params['signInfo'] : [];
		$operationInfos = isset($params['operationInfo']) ? $params['operationInfo'] : [];
		if (!$signInfo) {
			return ['code' => ['0x093510', 'electronicsign']];
		}
		if (!$operationInfos) {
			return ['code' => ['0x093511', 'electronicsign']];
		}
		if (isset($signInfo)) {
			if (!is_array($signInfo)) {
				$signInfo = json_decode($signInfo, 1);
			}
			//平台企业 serialNo值来自于数据键值排序
			$serialNo = 0;
			foreach ($signInfo as $sk => $sv) {
				if (in_array($sv['tenantType'], ['PERSONAL'])) {
					if (!isset($sv['tenantName']) || !$sv['tenantName'] || !isset($sv['contact']) || !$sv['contact']) {
						return ['code' => ['0x093531', 'electronicsign']];
					}
				}
				if (in_array($sv['tenantType'], ['CORPORATE', 'COMPANY'])) {
					//集成设置签署信息为发起方时签署动作类型必填  签署动作为个人时签署人联系方式必填
					if (!isset($sv['type'])) {
						$sv['type'] = 'CORPORATE';
					}
					// 签署动作为法人签字时是否法人签章字段为空
					if ($sv['type'] == 'LP' && isset($sv['canLpSign']) && !$sv['canLpSign']) {
						$sv['canLpSign'] = '';
					}
				} else {
					$sv['type'] = '';
				}
				//签署顺序设置
				$serialNo += 1;
				$signInfo[$sk]['serialNo'] = $serialNo;
				//平台企业 签署方法为个人时 联系方式要填
				if ($sv['tenantType'] == 'CORPORATE' && $sv['type'] == 'PERSONAL' && !$sv['contact']) {
					return ['code' => ['0x093532', 'electronicsign']];
				}
			}
			unset($serialNo);
			$data['sign_info'] = $settingId ? $this->addSettingId($signInfo, $settingId) : $signInfo;
		}
		if (!empty($operationInfos)) {
			if (!is_array($operationInfos)) {
				$operationInfos = json_decode($operationInfos, 1);
			}
			foreach ($operationInfos as $key => $operationInfo) {
				foreach (['set_sign_position', 'sign', 'view', 'download', 'print'] as $v) {
					if (array_key_exists($v, $operationInfo) && $operationInfo[$v]) {
						$operationInfos[$key]['operation'][] = $v;
					}
				}
				$data['operation_info'][$key]['operation'] = isset($operationInfos[$key]['operation']) ? implode(',', $operationInfos[$key]['operation']) : '';
				$data['operation_info'][$key]['nodeId'] = $operationInfo['node_id'];
				$data['operation_info'][$key]['settingId'] = $settingId ?: 0;
				// 外发触发事件
				$actionArray = ['create_contract', 'company_sign', 'download_contract', 'delete_contract', 'recall_contract', 'cancel_contract'];
				$outsendInfo = $operationInfo['outsend'] ?? [];
				if ($outsendInfo) {
					$outsendInfo = array_column($outsendInfo, null, 'action');
					foreach ($actionArray as $v) {
						if (array_key_exists($v, $outsendInfo) && $outsendInfo[$v]) {
							if (isset($outsendInfo[$v]['use']) && $outsendInfo[$v]['use']) {
								$data['outsend_info'][] = [
									'nodeId' => $operationInfo['node_id'],
									'flowId' => $params['workflowId'],
									'action' => $v,
									'flowOutsendTimingArrival' => $outsendInfo[$v]['flowOutsendTimingArrival'], // 2 仅退回触发
									'flowOutsendTimingSubmit' => $outsendInfo[$v]['flowOutsendTimingArrival'] != 1 ? 1 : 0,
									'back' => $outsendInfo[$v]['flowOutsendTimingArrival'] == 2 ? 0 : $outsendInfo[$v]['back'], // 退回不触发 1 触发 0
								];
							}
						}
					}
				}
			}
		}
		return $data;
	}

	/**
	 * 将设置ID添加签署信息和节点操作权限数组
	 *
	 * @param [type] $data
	 * @param [type] $settingId
	 * @return array
	 */
	private function addSettingId($data, $settingId)
	{
		foreach ($data as $key => $val) {
			if (array_key_exists('signInfoId', $val)) {
				unset($data[$key]['signInfoId']);
			}
			if (array_key_exists('signNode', $val)) {
				unset($data[$key]['signNode']);
			}
			if (array_key_exists('operationInfoId', $val)) {
				unset($data[$key]['operationInfoId']);
			}
			$data[$key]['settingId'] = $settingId;
		}
		return $data;
	}

	/**
	 * 判断合同是否为草稿，是发起合同
	 *
	 * @param [type] $contractId
	 * @param [type] $flowId
	 *
	 * @return void
	 * @author yuanmenglin
	 * @since
	 */
	public function checkContractStatus($contractId, $flowId)
	{
		$sendRes = app($this->qiyuesuoService)->checkContractStatus($contractId, $flowId);
		if (isset($sendRes['code'])) {
			return $sendRes;
		}
		$contract = app($this->qiyuesuoService)->getContract($flowId, ['contractId' => $contractId]);
		if (isset($contract['contract']) && isset($contract['contract']['status']) && $contract['contract']['status'] == 'DRAFT') {
			app($this->qiyuesuoService)->sendContract($flowId, $contractId);
			// 更改本地合同状态 为签署中
			app($this->flowRunRelationQysContractInfoRepository)->updateData(['contractStatus' => 'SIGNING'], ['contractId' => $contractId]);
		}
		return true;
	}

	/**
	 * 【契约锁集成功能】 获取签署合同的url，传合同号，流程id，返回完整的url
	 *
	 * @author dingpeng
	 * @param   [type]  $contractId  [$contractId description]
	 *
	 * @return  [type]               [return description]
	 */
	public function getContractSignUrl($contractId, $params, $userInfo)
	{
		$runId = isset($params['runId']) ? $params['runId'] : '';
		$userId = isset($userInfo['user_id']) ? $userInfo['user_id'] : '';
		$platform = $params['platform'] ?? '';
		// 根据runid取flowid取配置，解析server
		// $flowId = $params['flowId'] ?? '';
		$flowId = app($this->flowRunService)->getFlowIdByRunId($runId);
		// 获取使用的契约锁服务类型
		if ($flowId && $settingInfo = app($this->qiyuesuoService)->getFlowRunRelationQysSetting($flowId)) {
			// 服务信息
			$serverInfo = isset($settingInfo['settinghas_one_server']) ? $settingInfo['settinghas_one_server'] : '';
			$serverType = $serverInfo ? $serverInfo['serverType'] : '';
			if ($serverType == 'private') {
				// 验证合同是否需要重新发起
				$contract = app($this->qiyuesuoService)->checkRecreated($contractId, $params, $userInfo);
				if (isset($contract['contractId'])) {
					$contractId = $contract['contractId'];
				} else {
					return '';
				}
				// 如果合同状态为草稿 就发起合同 -- 私有云
				$this->checkContractStatus($contractId, $flowId);
				$goPage = isset($serverInfo['goPage']) ? $serverInfo['goPage'] : '';
				// 去右侧斜线 空格
				$goPage = trim(rtrim($goPage, '/'));
				$log = [
					'flowId' => $flowId,
					'runId' => $runId,
					'userid' => $userInfo['user_id'],
					'ipaddress' => getClientIp(),
					'createTime' => date('Y-m-d H:i:s'),
					'requestJson' => json_encode(['runId' => $runId, 'flowId' => $flowId, 'contractId' => $contractId]),
					'action' => 'sign_contract',
					'serverId' => $serverInfo['serverId'],
					'contractId' => $contractId,
				];
				// 取加密串
				if (isset($serverInfo['encryptKey']) && !empty($serverInfo['encryptKey'])) {
					$qystoken = $this->getQysSignUserTokenStringByUserId($userId, $platform, $serverInfo);
					if (is_array($qystoken)) {
						// 出错了
						$transKey = isset($qystoken['code']) && isset($qystoken['code'][0]) ? $qystoken['code'][0] : 'get_qys_token_failed';
						$log['responseJson'] = json_encode(['message' => trans('electronicsign.' . $transKey)]);
						app($this->qiyuesuoService)->addLog($this->qiyuesuoContractLogRepository, $log);
						return $qystoken;
					} else {
						if ($qystoken) {
							$signUrl = $goPage . "/" . $contractId . "?qystoken=" . $qystoken;
							// 20190604-单点登录跳转过来的时候，后面再跟一个platform=EOFFICE的固定值，用来在ec-契约锁中标识是来自eoffice
							$signUrl .= "&platform=EOFFICE";
							// &hide_menu=true 隐藏签署页面的菜单
							$signUrl .= "&hide_menu=true";
							$log['responseJson'] = json_encode(['message' => trans('electronicsign.operate_contract_success')]);
							app($this->qiyuesuoService)->addLog($this->qiyuesuoContractLogRepository, $log);
							return $signUrl;
						}
					}
				} else {
					// 无单点登录加密秘钥
					$signUrl = app($this->qiyuesuoService)->getSignUrlWithoutEncryptKey(compact('runId', 'contractId', 'platform', 'userId', 'flowId'));
					if (is_array($signUrl)) {
						// 出错了
						$transKey = isset($qystoken['code']) && isset($signUrl['code'][0]) ? $signUrl['code'][0] : 'get_qys_token_failed';
						$log['responseJson'] = json_encode(['message' => trans('electronicsign.' . $transKey)]);
						app($this->qiyuesuoService)->addLog($this->qiyuesuoContractLogRepository, $log);
						return $signUrl;
					} else {
						return $signUrl;
					}
				}
			} else {
				// 获取签署页面链接 --- 公有云
				$params['type'] = 'sign';
				$params['flowId'] = $flowId;
				$params['runId'] = $runId;
				return app($this->qiyuesuoPublicCloudService)->getContractUrl($contractId, $params, $userInfo);
			}
		}
		return '';
	}
    /**
     * 生成签署加密串
     *
     * 1、qystoken 参数值是用户身份信息加密的密文，其明文信息格式： 手机号|，例如：134****1093|;
     * 2、对明文进行加密，加密算法采用 AES ，加密密钥为长度16 位的随机字符，该密钥需双方系统保持一致并需要被妥善保管。
     * @param   [type]  $userId  [$userId description]
     *
     * @return  [type]           [return description]
     */
    public function getQysSignUserTokenStringByUserId($userId, $platform = '', $serverInfo = [])
    {
        $userInfo = app($this->userService)->getUserAllData($userId);
        if (!empty($userInfo)) {
            $userInfo = $userInfo->toArray();
            $userOneInfo = isset($userInfo['user_has_one_info']) ? $userInfo['user_has_one_info'] : '';
            $phoneNumber = isset($userOneInfo['phone_number']) ? $userOneInfo['phone_number'] : '';
            if (!$phoneNumber) {
                // 获取用户手机号码失败，无法进行签署
                return ['code' => ['0x093520', 'electronicsign']];
            }
            $data = trim($phoneNumber) . '|';
            $key = isset($serverInfo['encryptKey']) && $serverInfo['encryptKey'] ? $serverInfo['encryptKey'] : '';
            //                $baseInfo = $this->getServerBaseInfo();
            //                $key = isset($baseInfo['encrypt_key']) ? $baseInfo['encrypt_key'] : '';
            if ($key) {
                $cryptograph = openssl_encrypt($data, 'AES-128-ECB', $key);
                // 移动端访问外部url会进行urlencode 此处不进行urlcode
                if (!$platform) {
                    return urlencode($cryptograph);
                } else {
                    return $cryptograph;
                }
            } else {
                // 未设置单点登录的加密密钥Key，无法进行签署
                return ['code' => ['0x093529', 'electronicsign']];
            }
        }
        // 获取用户信息失败，无法进行签署
        return ['code' => ['0x093521', 'electronicsign']];
    }

    /** 外部访问
     * @param $userId
     * @param string $platform
     * @param array $serverInfo
     * @param string $token
     * @return array|string
     */
	public function getQysSignUserTokenString($userId, $platform = '', $serverInfo = [], $token = '')
	{
	    if ($token) {
            $userInfo = Cache::get($token);
            $tokenUserId = $userInfo->user_id ?? '';
            if ($tokenUserId != $userId) {
                return ['code' => ['user_error', 'electronicsign']];
            }
        } else {
            // 取用户信息
            return ['code' => ['user_error', 'electronicsign']];
        }
	    return $this->getQysSignUserTokenStringByUserId($userId, $platform, $serverInfo);
	}

	/**
	 * 删除集成设置时如果流程选中系统标签为契约锁则换位显示标签的第一个为选中
	 *
	 * @param [type] $flowId 流程id
	 *
	 * @return void
	 * @author yuanmenglin
	 * @since 2019-5-22
	 */
	public function deleteIntegrationChangeFlowLable($flowId)
	{
		$lableMatch = [
			'feedback' => 1,
			'attachment' => 2,
			'document' => 3,
			'map' => 5,
			'step' => 6,
			'sunflow' => 4,
			'qyssign' => 7,
		];
		$fields = [
			// 'lable_show_default' => 0,
			'flow_detail_page_choice_other_tabs' => 0,
		];
		$flowOthers = app($this->flowOthersRepository)->getColumns(array_keys($fields), ['flow_id' => $flowId]);
		// if ($flowOthers[$fields['lable_show_default']] == $lableMatch['qyssign'] && !empty($flowOthers[$fields['flow_detail_page_choice_other_tabs']])) {  // 选中标签功能移除 --- 相应修改
		if (!empty($flowOthers[$fields['flow_detail_page_choice_other_tabs']])) {
			$lables = explode(',', $flowOthers[$fields['flow_detail_page_choice_other_tabs']]);
			if ($lables && isset($lables[0]) && isset($lableMatch[$lables[0]])) {
				//流程设置系统标签选中值修改
				// $data['lable_show_default'] = $lableMatch[$lables[0]];
				//流程设置系统标签删除契约锁的勾选状态
				if (in_array('qyssign', $lables)) {
					foreach ($lables as $k => $v) {
						if ($v == 'qyssign') {
							unset($lables[$k]);
							break;
						}
					}
					$data['flow_detail_page_choice_other_tabs'] = implode(',', $lables);
					return app($this->flowOthersRepository)->updateData($data, ['flow_id' => $flowId]);
				}
			}
			return true;
		} else {
			return true;
		}
	}

	/**
	 * 保存集成设置时修改对应流程系统标签 增加契约锁标签
	 *
	 * @param [type] $flowId
	 *
	 * @return void
	 * @author yuanmenglin
	 * @since
	 */
	public function saveIntegrationChangeFlowLable($flowId)
	{
		$fields = [
			// 'lable_show_default' => 0,
			'flow_detail_page_choice_other_tabs' => 0,
		];
		$flowOthers = app($this->flowOthersRepository)->getColumns(array_keys($fields), ['flow_id' => $flowId]);
		if (!empty($flowOthers[$fields['flow_detail_page_choice_other_tabs']])) {
			$lables = explode(',', $flowOthers[$fields['flow_detail_page_choice_other_tabs']]);
			if ($lables && !in_array('qyssign', $lables)) {
				$lables[] = 'qyssign';
				$data['flow_detail_page_choice_other_tabs'] = implode(',', $lables);
				// $data['lable_show_default'] = 7; // 默认显示契约锁签署
				return app($this->flowOthersRepository)->updateData($data, ['flow_id' => $flowId]);
			}
			return true;
		} else {
			return true;
		}
	}

	/**
	 * 【流程办理页面调用，契约锁签署标签，获取跟契约锁关联的信息】
	 *  ---1101 增加契约锁物理用印申请的相关信息-授权码
	 * @param  [type] $params [flow_id; run_id]
	 * @return [type] array   [contract_info-合同相关信息-array;sign_list-签署信息-array]
	 */
	public function getFlowRelationQysSignInfo($params, $userInfo)
	{
		$flowInfo = isset($params['flowInfo']) ? $params['flowInfo'] : [];
		$formData = isset($params['formData']) ? $params['formData'] : [];
		$flowId = isset($flowInfo['flow_id']) ? $flowInfo['flow_id'] : '';
		$runId = isset($flowInfo['run_id']) ? $flowInfo['run_id'] : '';
		$result = [
			'contract' => false,
			'seal_apply' => false,
			'contract_detail' => [], //契约锁合同详情
			'contract_info' => [], //流程表单合同信息
			'sign_list' => [], //签署信息
			'seal_apply_auth_code' => [], // 物理用印授权码
			'seal_apply_images' => [], // 用印后图片附件
            'contract_file' => '',  // 电子合同文件
		];
		// 根据定义流程id判断集成的电子合同还是物理用印
		$contractSetting = app($this->qiyuesuoSettingRepository)->getSettingDetailByFlowId($flowId);
		$SealApplySetting = app($this->qiyuesuoSealApplySettingRepository)->getSettingByFlowId($flowId);
		if ($contractSetting) {
			$server = app($this->qiyuesuoServerRepository)->getDetail($contractSetting->serverId);
			$result['contract'] = true;
			// 多语言
			$tenantTypes = [
				'CORPORATE' => trans('electronicsign.corporate_company'),
				'COMPANY' => trans('electronicsign.company'),
				'PERSONAL' => trans('electronicsign.personal'),
			];
			$types = [
				'CORPORATE' => trans('electronicsign.signature_of_enterprise'),
				'PERSONAL' => trans('electronicsign.signature_of_legal_personal'),
				'LP' => trans('electronicsign.signature_of_legal_representative'),
				'COMPANY' => trans('electronicsign.signature_of_company'),
				'OPERATOR' => trans('electronicsign.signature_of_operator'),
				'AUDIT' => trans('electronicsign.signature_of_audit'),
			];
			// status：文件状态（DRAFT：草稿，FILLING：拟定中，SIGNING：签署中，COMPLETE：已完成，REJECTED：已退回，RECALLED：已撤回，EXPIRED：已过期，TERMINATING：作废确认中，TERMINATED：已作废，DELETE：已删除，FINISHED：强制完成）
			$contractStatus = [
				'DRAFT' => trans('electronicsign.draft'),
				'SIGNING' => trans('electronicsign.signing'),
				'COMPLETE' => trans('electronicsign.complete'),
				'REJECTED' => trans('electronicsign.rejected'),
				'RECALLED' => trans('electronicsign.recalled'),
				'EXPIRED' => trans('electronicsign.expired'),
				'TERMINATED' => trans('electronicsign.terminated'),
				'TERMINATING' => trans('electronicsign.terminating'),
				'INVALIDED' => trans('electronicsign.terminated'),
				'INVALIDING' => trans('electronicsign.terminating'),
				'FINISHED' => trans('electronicsign.finished'),
				'END' => trans('electronicsign.end'),
				'FORCE_END' => trans('electronicsign.force_end')
			];
			if ($runId) {
				$contractInfo = app($this->flowRunRelationQysContractInfoRepository)->getByRunId($runId);
				if ($contractInfo) {
					$contractId = $this->getContractIdbyFlowRunId($runId);
					$contractDetail = [];
					if ($contractId) {
						//通过开放接口获取合同
						if ($server->serverType == 'public') {
							$contractDetail = app($this->qiyuesuoPublicCloudService)->getContract($flowId, ['contractId' => $contractId]);
							$result['contract_detail'] = $contractDetail['result'] ?? [];
						} else {
							$contractDetail = app($this->qiyuesuoService)->getContract($flowId, ['contractId' => $contractId]);
							$result['contract_detail'] = $contractDetail['contract'] ?? [];
							$result['contract_info']['categoryName'] = $result['contract_detail']['categoryName'] ?? '';
						}
						//合同id存在 但访问开放接口失败 设置合同状态为签署中
						// if (!isset($contractDetail['detail'])) {
						//     $contractDetailStatus = ['statusDesc' => '获取合同状态失败'];
						// }
					}
					$initiateurField = $contractInfo['initiateurField'];
					//已生成合同 从关联信息表获取合同信息
					$result['contract_info'] = $contractInfo;
					$result['contract_info']['signOrdinalField'] = $contractInfo->signOrdinalField == false ? trans('electronicsign.unordered') : trans('electronicsign.ordinal');
					$result['contract_info']['contractStatus'] = $contractInfo['contractStatus'] && $result['contract_detail'] ? ($contractStatus[$result['contract_detail']['status']] ?? '') : '';
					$contractSignInfo = app($this->flowRunRelationQysContractSignInfoRepository)->getByRunId($runId);
					if ($contractSignInfo) {
						foreach ($contractSignInfo as $key => $val) {
							$result['sign_list'][$key] = [
								'tenantType' => $val->tenantType,
								'tenantTypeName' => isset($tenantTypes[$val->tenantType]) ? $tenantTypes[$val->tenantType] : '',
								'tenantName' => $val->tenantName,
								'contact' => $val->contact,
								'canLpSign' => $val->canLpSign ? $val->canLpSign : '',
								'type' => $val->type ? $val->type : '',
								'typeName' => $val->tenantType == 'PERSONAL' ? $types['PERSONAL'] : (isset($types[$val->type]) ? $types[$val->type] : ''),
							];
							if ($server->serverType == 'public' && $val->tenantType == 'COMPANY') {
								if ($initiateurField == $val->tenantName) {
									$result['sign_list'][$key]['tenantType'] = 'CORPORATE';
									$result['sign_list'][$key]['tenantTypeName'] = $tenantTypes['CORPORATE'];
								}
							}
						}
					}
					// 获取合同文件
                    // 先判断合同id对应附件文件是否存在
                    $attachmentId = app($this->flowRunRelationQysContractInfoRepository)->getFieldValue('attachmentId', ['contractId' => $contractId]);
					if ($attachmentId) {
                        $result['contract_file'] = $attachmentId;
                    }
				} else {
					//未生成合同从表单获取  获取契约锁设置对应字段名 获取表单数据 匹配
					if ($flowId) {
						$formId = $flowInfo['form_id'] ?? 0;
						$formControlData = app($this->flowFormService)->getParseForm($formId);
						$formControlData = array_column($formControlData, NULL, 'control_id');
						$setting = app($this->qiyuesuoSettingRepository)->getSettingDetailByFlowId($flowId);
						$runInfo = app($this->flowRunService)->getFlowRunDetail($runId);
						if ($setting) {
							$setting = $setting->toArray();
							$signList = app($this->qiyuesuoService)->parseContractSignInfo($formData, $formControlData, $setting['settinghas_many_sign_info'], false);
							$company = app($this->companyService)->getCompanyDetail();
							foreach ($signList as $key => $val) {
								$signList[$key]['tenantTypeName'] = isset($tenantTypes[$val['tenantType']]) ? $tenantTypes[$val['tenantType']] : '';
								$signList[$key]['typeName'] = isset($val['type']) && isset($types[$val['type']]) ? $types[$val['type']] : '';
								if ($val['tenantType'] == 'CORPORATE') {
									$signList[$key]['tenantName'] = $val['tenantName'] ?? (isset($company['company_name']) ? trim($company['company_name']) : '');
								} else {
									$signList[$key]['tenantName'] = $val['tenantName'] ?? '';
								}
							}
							$result['sign_list'] = $signList;
							// 合同名称
							$subjectField = $setting['subjectField'] ?? '';
							$subject = $formData[$subjectField] ?? '';
							$categoryField = $setting['categoryField'] ?? '';
							$categoryValue = $categoryField ? ($formData[$categoryField . '_TEXT'] ?? '') : '';
							$templateField = $setting['templateField'] ?? '';
							$templateValue = $templateField ? ($formData[$templateField . '_TEXT'] ?? '') : '';
							$documentField = $setting['documentField'] ?? '';
							$initiateurField = $setting['initiateurField'] ?? '';
							$creatorName = app($this->qiyuesuoService)->parseFlowDataOutSend($formData, $formControlData, 'creatorNameField', $setting, -1);
							$creatorContact = app($this->qiyuesuoService)->parseFlowDataOutSend($formData, $formControlData, 'creatorContactField', $setting, -1);
							$creatorField = $setting['creatorField'] ?? '';
							// 未设置创建人手机号 获取当前用户的 -- 兼容之前设置为流程创建者和当前节点操作人
							if (!$creatorContact && $creatorField) {
								if ($creatorField == 'flow_creator' && isset($runInfo['creator'])) {
									$userInfo = app($this->userService)->getUserAllData($runInfo['creator']);
								} else {
									$userInfo = app($this->userService)->getUserAllData($userInfo['user_id']);
								}
								if (!empty($userInfo)) {
									$userInfo = $userInfo->toArray();
									if (!$creatorName) {
										$creatorName = isset($userInfo['user_name']) ? $userInfo['user_name'] : '';
									}
									$userOneInfo = isset($userInfo['user_has_one_info']) ? $userInfo['user_has_one_info'] : '';
									$creatorContact = isset($userOneInfo['phone_number']) ? $userOneInfo['phone_number'] : '';
								}
							}
							$signValidityField = $setting['signValidityField'] ?? '';
							$signOrdinalField = $setting['signOrdinalField'] ?? '';
							if ($documentField && isset($formData[$documentField])) {
								$attachmentUrls = app($this->attachmentService)->getMoreAttachmentById($formData[$documentField]);
								$documents = implode(',', array_column($attachmentUrls, 'attachment_name'));
							}
							$result['contract_info'] = [
								'subjectField' => $subject,
								'templateField' => $templateValue,
								'documentField' => $documents ?? '',
								'categoryName' => $categoryValue,
								'categoryField' => $categoryValue,
								'initiateurField' => $formData[$initiateurField] ?? '',
								'creatorField' => ($creatorName && $creatorContact) ? $creatorName . '-' . $creatorContact : (($creatorName ?? '') . $creatorContact ?? ''),
								'signValidityField' => $formData[$signValidityField] ?? '',
								'signOrdinalField' => $signOrdinalField == 'unordered' ? trans('electronicsign.unordered') : trans('electronicsign.ordinal'),
								'contractStatus' => trans('electronicsign.contract_not_generate'),
							];
						}
					}
				}
			}
		}
		if ($SealApplySetting) {
			$result['seal_apply'] = true;
			// 运行流程id存在获取授权码及用印后文件
			if ($runId) {
				// 授权码
				$authCodes = app($this->qiyuesuoSealApplyAuthLogRepository)->getFieldInfo(['runId' => $runId]);
				if ($authCodes) {
					// 授权码信息数据进行处理 -- 只能看到自己的授权码
					$phoneNumber = $userInfo['phone_number'] ?? '';
					foreach ($authCodes as $authCodeKey => $authCode) {
						if ($phoneNumber != $authCode['contact']) {
							$authCodes[$authCodeKey]['vertifyCode'] = '******';
							// unset($authCodes[$authCodeKey]);
						}
					}
					$result['seal_apply_auth_code'] = $authCodes;
				}
				// 用印后文件
				$authCodeImages = app($this->attachmentRelataionQiyuesuoSealApplyImageRepository)->getFieldInfo(['run_id' => $runId]);
				if ($authCodeImages) {
					$authCodeImages = array_column($authCodeImages, 'attachment_id');
					$result['seal_apply_images'] = $authCodeImages;
				}
			}
		}

		return $result;
	}

	/**
	 * 【生成契约锁合同时使用流程表单数据填充契约锁合同相关信息和签署信息】
	 *
	 * @param [type] $params
	 *
	 * @return void
	 * @author yuanmenglin
	 * @since 2019-05-24
	 */
	public function setFlowRelationQysSignInfo($params)
	{
		$runId = isset($params['runId']) && !empty($params['runId']) ? $params['runId'] : '';
		$contract = isset($params['contractparams']) && !empty($params['contractparams']) ? $params['contractparams'] : [];
		$now = time();
		$date = date('Y-m-d H:i:s', $now);
		if ($runId) {
			// 兼容公有云和私有云
			$creatorName = $contract['creatorName'] ?? (isset($contract['creator']) && isset($contract['creator']['name']) ? $contract['creator']['name'] : '');
			$creatorContact = $contract['creatorContact'] ?? (isset($contract['creator']) && isset($contract['creator']['contact']) ? $contract['creator']['contact'] : '');
			if ($creatorName && $creatorContact) {
				$creator = $creatorName . '-' . $creatorContact;
			} else {
				$creator = $creatorName . $creatorContact;
			}
			$contractInfo = [
				'runId' => $runId,
				'contractId' => $contract['contractId'] ?? '',
				'subjectField' => $contract['subject'] ?? '',
				'templateField' => isset($contract['templateField']) ? (implode(',', $contract['templateField'])) : '',
				'documentField' => isset($contract['document_names']) ? (is_array($contract['document_names']) ? implode(',', $contract['document_names']) : $contract['document_names']) : '',
				'categoryField' => isset($contract['categoryName']) && is_string($contract['categoryName']) ? $contract['categoryName'] : (isset($contract['categoryId']) && isset($contract['categoryId']['name']) ? $contract['categoryId']['name'] : ''),
				'initiateurField' => $contract['tenantName'] ?? '',
				'creatorField' => $creator,
				'creatorId' => $contract['creatorId'] ?? '',
				'signValidityField' => $contract['expireTime'] ?? '',
				'signOrdinalField' => $contract['ordinal'],
				'created_at' => $date,
				'updated_at' => $date,
				'contractStatus' => $contract['contractStatus'] ?? '',
			];
			$signInfo = isset($contract['signatories']) ? $contract['signatories'] : [];
			if ($signInfo) {
				$contractSignInfo = [];
				$company = app($this->companyService)->getCompanyDetail();
				foreach ($signInfo as $key => $val) {
					if ($val['tenantType'] == 'CORPORATE' && !$val['tenantName']) {
						$val['tenantName'] = isset($company['company_name']) ? trim($company['company_name']) : '';
					}
					if (in_array($val['tenantType'], ['CORPORATE', 'COMPANY'])) {
						if (isset($val['actions']) && !empty($val['actions'])) {
							foreach ($val['actions'] as $actKey => $actVal) {
								if ($actVal['type'] == 'LP' && isset($actVal['canLpSign']) && $actVal['canLpSign'] == 1) {
									continue;
								}
								if (isset($actVal['actionOperators'])) {
									$operatorContacts = array_column($actVal['actionOperators'], 'operatorContact') ?? [];
								} else {
									// 公有云其他企业接收方不能设置操作人
									$operators = $actVal['operators'] ?? [];
									if (!$operators && isset($val['receiver']) && $val['receiver']) {
										$operators[] = $val['receiver'];
									}
									$operatorContacts = array_column($operators, 'contact') ?? [];
								}
								$contractSignInfo[] = [
									'runId' => $runId,
									'contractId' => $contract['contractId'] ?? '',
									'tenantType' => $val['tenantType'] ?? '',
									'tenantName' => $val['tenantName'] ?? '',
									'contact' => implode(',', $operatorContacts),
									'canLpSign' => $actVal['canLpSign'] ?? '',
									'type' => $actVal['type'] ?? '',
									'serialNo' => $val['serialNo'] ?? 0,
									'created_at' => $date,
									'updated_at' => $date,
									'keyword' => $actVal['keyword'] ?? '',
									'seal' => $actVal['sealId'] ?? ($actVal['seal'] ?? ''),
									'actionId' => $actVal['id'] ?? '',
									'signatoryId' => $val['id'] ?? '',
								];
							}
						} else {
							// $contractSignInfo[] = [
							//     'runId' => $runId,
							//     'contractId' => $contract['contractId'] ?? '',
							//     'tenantType' => $val['tenantType'] ?? '',
							//     'tenantName' => $val['tenantName'] ?? '',
							//     'contact' => $val['contact'] ?? '',
							//     'canLpSign' => isset($val['canLpSign']) ? $val['canLpSign'] : '',
							//     'type' => $actVal['type'] ?? '',
							//     'serialNo' => $actVal['serialNo'] ?? 0,
							//     'created_at' => $date,
							//     'updated_at' => $date,
							//     'keyword' => $actVal['keyword'] ?? '',
							//     'seal' => $actVal['seal'] ?? '',
							// ];
						}
					} else {
						// 公有云传递的是receiver数组
						if (isset($val['receiver']) && is_array($val['receiver'])) {
							// $contacts = array_column($val['receiver'], 'contact') ?? [];
							// $val['contact'] = implode(',', $contacts);
							$val['contact'] = $val['receiver']['contact'] ?? '';
						}
						$contractSignInfo[] = [
							'runId' => $runId,
							'contractId' => $contract['contractId'] ?? '',
							'tenantType' => $val['tenantType'] ?? '',
							'tenantName' => $val['tenantName'] ?? '',
							'contact' => $val['contact'] ?? '',
							'canLpSign' => isset($val['canLpSign']) ? $val['canLpSign'] : '',
							'type' => '',
							'serialNo' => $val['serialNo'] ?? 0,
							'created_at' => $date,
							'updated_at' => $date,
							'keyword' => $val['keyword'] ?? '',
							'seal' => $val['sealId'] ?? ($val['seal'] ?? ''),
							'actionId' => '',
							'signatoryId' => $val['id'] ?? '',
						];
					}
				}
			}
			if ($contractInfo) {
				app($this->flowRunRelationQysContractInfoRepository)->deleteByWhere(['runId' => [$runId]]);
				$res = app($this->flowRunRelationQysContractInfoRepository)->insertData($contractInfo);
			}
			if ($contractSignInfo) {
				$contractSignInfo = mult_array_sort($contractSignInfo, 'serialNo');
				app($this->flowRunRelationQysContractSignInfoRepository)->deleteByWhere(['runId' => [$runId]]);
				app($this->flowRunRelationQysContractSignInfoRepository)->insertMultipleData($contractSignInfo);
			}
		}
		return true;
	}

	public function getRelatedResourceTaskList($params)
	{
		$params = $this->parseParams($params);

		$response = isset($params['response']) ? $params['response'] : 'both';
		$list = [];

		if ($response == 'both' || $response == 'count') {
			$count = app($this->qiyuesuoRelatedResourceTaskRepository)->getCount($params);
		}

		if (($response == 'both' && $count > 0) || $response == 'data') {
			foreach (app($this->qiyuesuoRelatedResourceTaskRepository)->getList($params) as $new) {
				$list[] = $new;
			}
		}

		return $response == 'both' ? ['total' => $count, 'list' => $list] : ($response == 'data' ? $list : $count);
	}

	/**
	 * 工作流契约锁物理用印集成设置--列表
	 * @param $params
	 * @return array|int
	 * @author [dosy]
	 */
	public function getSealApplyList($params)
	{
		$params = $this->parseParams($params);

		$response = isset($params['response']) ? $params['response'] : 'both';
		$list = [];
		$count = 0;
		if (!in_array($response, ['both', 'count', 'data'])) {
			return ['code' => ['0x093517', 'electronicsign']];
		}
		if ($response == 'both' || $response == 'count') {
			$count = app($this->qiyuesuoSealApplySettingRepository)->getCount($params);
		}

		if (($response == 'both' && $count > 0) || $response == 'data') {
			foreach (app($this->qiyuesuoSealApplySettingRepository)->getList($params) as $new) {
				$list[] = $new;
			}
		}

		return $response == 'both' ? ['total' => $count, 'list' => $list] : ($response == 'data' ? $list : $count);
	}

	/**
	 * 工作流契约锁物理用印集成设置--新建
	 * @param $params
	 * @return bool
	 * @author [dosy]
	 */
	public function addSealApply($params)
	{
		$insertData = array_intersect_key($params, array_flip(app($this->qiyuesuoSealApplySettingRepository)->getTableColumns()));
		if (empty($insertData)) {
			return ['code' => ['add_failed', 'electronicsign']];
		}
		// 添加限制 -- 一条流程只能配置一个集成【物理用印和电子合同】
		$flowId = $insertData['workflowId'] ?? '';
		$checkStatus = $this->checkSettingExist($flowId, 'seal_apply');
		if ($checkStatus !== true) {
			return $checkStatus;
		}
		$resAdd = app($this->qiyuesuoSealApplySettingRepository)->insertData($insertData);

		if ($resAdd) {
			// 触发节点设置 保存
			$outsendInfos = $params['outsendInfo'] ?? [];
			$this->handleOutsendInfo($outsendInfos, $resAdd->workflowId, $resAdd->settingId, 'add');
			// 流程选中契约锁标签
			if (isset($insertData['workflowId'])) {
				$this->saveIntegrationChangeFlowLable($insertData['workflowId']);
			}
            Cache::put('qiyuesuo_integration_'.$resAdd->workflowId, true, 24*60*60);
		}
		return $resAdd;
	}

	/**
	 * 工作流契约锁物理用印集成设置--删除
	 * @param $id
	 * @return mixed
	 * @author [dosy]
	 */
	public function deleteSealApply($id)
	{
		$where = ['settingId' => [$id]];
		$setting = app($this->qiyuesuoSealApplySettingRepository)->getDetail($id);
		$result = app($this->qiyuesuoSealApplySettingRepository)->deleteByWhere($where);
		if (!$result) {
			return ['code' => ['delete_failed', 'electronicsign']];
		}
		// 删除对应的流程触发节点设置
		app($this->qiyuesuoSealApplySettingOutsendInfoRepository)->deleteByWhere(['settingId' => [$id]]);
		//删除流程设置中默认的选中标签
		$this->deleteIntegrationChangeFlowLable($setting->workflowId);
        Cache::forget('qiyuesuo_integration_'.$setting->workflowId);
		return $result;
	}

	/**
	 * 工作流契约锁物理用印集成设置--编辑
	 * @param $id
	 * @param $params
	 * @return bool
	 * @author [dosy]
	 */
	public function editSealApply($id, $params)
	{
		$insertData = array_intersect_key($params, array_flip(app($this->qiyuesuoSealApplySettingRepository)->getTableColumns()));
		if (empty($insertData)) {
			return ['code' => ['edit_failed', 'electronicsign']];
		}
		$SealApplyData = $this->getSealApply($id);
		// id错误
		if (!$SealApplyData) {
			return ['code' => ['edit_failed', 'electronicsign']];
		}
		$where = ['settingId' => $id];
		$result = app($this->qiyuesuoSealApplySettingRepository)->updateData($insertData, $where, ['settingId']);
		if (!$result) {
			return ['code' => ['edit_failed', 'electronicsign']];
		}
		// 触发节点设置 保存
		$outsendInfos = $params['outsendInfo'] ?? [];
		$this->handleOutsendInfo($outsendInfos, $insertData['workflowId'], $id, 'edit');
		// 流程选中契约锁标签
		if (isset($insertData['workflowId'])) {
			$this->saveIntegrationChangeFlowLable($insertData['workflowId']);
		}
		return $result;
	}
	/**
	 * 处理流程节点触发配置
	 *
	 * @param [type] $outsendInfos
	 * @param [type] $settingId  0 为新增物理用印集成配置
	 * @param [type] $flowId
	 *
	 * @return void
	 * @author yuanmenglin
	 * @since
	 */
	public function handleOutsendInfo($outsendInfos, $flowId, $settingId, $type = 'add')
	{
		if ($type == 'edit') {
			app($this->qiyuesuoSealApplySettingOutsendInfoRepository)->deleteByWhere(['settingId' => [$settingId]]);
		}
		if ($outsendInfos) {
			foreach ($outsendInfos as $outsendKey => $outsendInfo) {
				$outsendInfos[$outsendKey] = array_intersect_key($outsendInfo, array_flip(app($this->qiyuesuoSealApplySettingOutsendInfoRepository)->getTableColumns()));
				$outsendInfos[$outsendKey]['settingId'] = $settingId;
				$outsendInfos[$outsendKey]['flowId'] = $flowId;
                $outsendInfos[$outsendKey]['type'] = 'physical_seal';
			}
			app($this->qiyuesuoSealApplySettingOutsendInfoRepository)->insertMultipleData($outsendInfos);
		}
		return true;
	}

	/**
	 * 工作流契约锁物理用印集成设置--查看
	 * @param $id
	 * @return mixed
	 * @author [dosy]
	 */
	public function getSealApply($id)
	{
		$result = app($this->qiyuesuoSealApplySettingRepository)->getDataById($id);
		return $result;
	}
	/**
	 * 检测定义流程是否有集成契约锁---电子合同/物理用印
	 *
	 * @param [type] $flowId
	 * @param [type] $type
	 *
  	 * @return array|boolean
	 * @author yuanmenglin
	 * @since
	 */
	public function checkSettingExist($flowId, $type)
	{
		if (!$flowId) {
			return ['code' => ['get_flow_id_failed', 'electronicsign']];
		}
		// 验证当前流程是否已集成契约锁电子合同
		// $contractSetting = app($this->qiyuesuoSettingRepository)->getSettingDetailByFlowId($flowId);
		if ($type == 'contract') {
			$contractSetting = app($this->qiyuesuoSettingRepository)->getFieldInfo(['workflowId' => $flowId]);
			if ($contractSetting) {
				return ['code' => ['has_integrate_contract', 'electronicsign']];
			}
		}
		// 验证当前流程是否已集成契约锁物理用印
		if ($type == 'seal_apply') {
			$sealSetting = app($this->qiyuesuoSealApplySettingRepository)->getFieldInfo(['workflowId' => $flowId]);
			if ($sealSetting) {
				return ['code' => ['has_integrate_seal_apply', 'electronicsign']];
			}
		}
        // 用于流程提交触发验证当前流程是否集成契约锁
		if ($type == 'all') {
	            if ($return = Cache::get('qiyuesuo_integration_'.$flowId, null) != null){
	                return $return;
	            } else {
	                $contractSetting = app($this->qiyuesuoSettingRepository)->getFieldInfo(['workflowId' => $flowId]);
	                $sealSetting = app($this->qiyuesuoSealApplySettingRepository)->getFieldInfo(['workflowId' => $flowId]);
	                if ($sealSetting || $contractSetting) {
	                    Cache::put('qiyuesuo_integration_'.$flowId, true, 24*60*60);
	                    return true;
	                } else {
	                    Cache::put('qiyuesuo_integration_'.$flowId, false, 24*60*60);
	                    return false;
	                }
			}
		}
		return true;
	}
	/**
	 * 发起合同 --- 两种平台 公有云 私有云
	 *
	 * @param [type] $data
	 * @param [type] $userInfo
	 *
	 * @return void
	 * @author yml
	 */
	public function createContractV1($data, $userInfo)
	{
		$flowId = isset($data['flowInfo']) && isset($data['flowInfo']['flowId']) ? $data['flowInfo']['flowId'] : 0;
		$runId = isset($data['flowInfo']) && isset($data['flowInfo']['runId']) ? $data['flowInfo']['runId'] : 0;
		$formId = isset($data['flowInfo']) && isset($data['flowInfo']['formId']) ? $data['flowInfo']['formId'] : 0;
		$formData = isset($data) && isset($data['formData']) ? $data['formData'] : 0;
		$log = [
			'flowId' => $flowId,
			'runId' => $runId,
			'userid' => $userInfo['user_id'],
			'ipaddress' => getClientIp(),
			'createTime' => date('Y-m-d H:i:s'),
			'requestJson' => json_encode($data),
			'action' => 'create_contract',
		];
		if (!isset($data['flowInfo']) || !$flowId || !$runId || !isset($data['formData'])) {
			//提示：参数流程信息和表单数据未找到
			$log['responseJson'] = json_encode(['message' => trans('electronicsign.0x093517')]);
			app($this->qiyuesuoService)->addLog($this->qiyuesuoContractLogRepository, $log);
			return ['code' => ['0x093517', 'electronicsign']];
		}
		$config = app($this->qiyuesuoConfigRepository)->getItem('qys_on_off');
		if ($config && isset($config[0]) && isset($config[0]['paramValue']) && $config[0]['paramValue'] == 0) {
			//提示：配置项契约锁服务未开启
			$log['responseJson'] = json_encode(['message' => trans('electronicsign.0x093533')]);
			app($this->qiyuesuoService)->addLog($this->qiyuesuoContractLogRepository, $log);
			return ['code' => ['0x093533', 'electronicsign']];
		}
		// 通过流程id获取服务  根据服务类型请求服务
		$server = app($this->qiyuesuoServerRepository)->getServerByFlowId($data['flowInfo']['flowId']);
		if (!$server) {
			// 提示：未获取到契约锁服务
			$log['responseJson'] = json_encode(['message' => trans('electronicsign.get_service_failed')]);
			app($this->qiyuesuoService)->addLog($this->qiyuesuoContractLogRepository, $log);
			return ['code' => ['get_service_failed', 'electronicsign']];
		}
		$log['serverId'] = $server->serverId ?? '';
		// 查询当前流程是否已有合同 --- 有合同时走是否重新发起的逻辑  --- 没有合同直接创建合同
		$contractId = $this->getContractIdbyFlowRunId($runId);
		$contractData = compact('flowId', 'runId', 'formId', 'formData');
		if ($server->serverType == 'public') {
			// 公有云
			if ($contractId) {
				$result = app($this->qiyuesuoPublicCloudService)->checkRecreated($contractId, $contractData, $userInfo);
			} else {
				$result = app($this->qiyuesuoPublicCloudService)->createContractV1($data, $userInfo);
			}
		} else {
			// 私有云
			if ($contractId) {
				$result = app($this->qiyuesuoService)->checkRecreated($contractId, $contractData, $userInfo);
			} else {
				$result = app($this->qiyuesuoService)->createContractV1($data, $userInfo);
			}
		}
		if (isset($result['contractStatus']) && !empty($result['contractStatus'])) {
			if (in_array($result['contractStatus'], ['SIGNING', 'COMPLETE'])) {
				// 签署中和已完成 不能重新创建合同
				$status = [
					'SIGNING' => trans('electronicsign.signing'),
					'COMPLETE' => trans('electronicsign.complete'),
				];
				$message = trans('electronicsign.current_contract_status') . ($status[$result['contractStatus']] ?? $result['contractStatus']) . trans('electronicsign.can_not_create_contract');
				$log['responseJson'] = json_encode(['message' => $message]);
				app($this->qiyuesuoService)->addLog($this->qiyuesuoContractLogRepository, $log);
				return ['code' => ['', $message]];
			}
		}
		return $result;
	}
	/**
	 * 获取合同链接   --- 两种平台 公有云 私有云
	 *
	 * @param [type] $contractId
	 * @param [type] $data
	 * @param [type] $own
	 *
	 * @return void
	 * @author yml
	 */
    public function getContractUrl($contractId, $data, $own)
    {
        $flowId = isset($data['flowId']) ? $data['flowId'] : 0;
        $runId = isset($data['runId']) ? $data['runId'] : 0;
        $log = [
            'flowId' => $flowId,
            'runId' => $runId,
            'userid' => $own['user_id'],
            'ipaddress' => getClientIp(),
            'createTime' => date('Y-m-d H:i:s'),
            'requestJson' => json_encode($data),
            'action' => 'download_contract',
            'contractId' => $contractId
        ];
        if (!isset($flowId) || !isset($contractId) || !isset($data['type'])) {
            // 参数验证
            $log['responseJson'] = json_encode(['message' => trans('electronicsign.0x093517')]);
            app($this->qiyuesuoService)->addLog($this->qiyuesuoContractLogRepository, $log);
            return ['code' => ['0x093517', 'electronicsign']];
        }
        // 通过流程id获取服务  根据服务类型请求服务
        $server = app($this->qiyuesuoServerRepository)->getServerByFlowId($flowId);
        if (!$server) {
            // 提示：未获取到契约锁服务
            $log['responseJson'] = json_encode(['message' => trans('electronicsign.get_service_failed')]);
            app($this->qiyuesuoService)->addLog($this->qiyuesuoContractLogRepository, $log);
            return ['code' => ['get_service_failed', 'electronicsign']];
        }
        if ($server->serverType == 'public') {
            // 公有云
            $result = app($this->qiyuesuoPublicCloudService)->getContractUrl($contractId, $data, $own, $server);
        } else {
            // 私有云
            $result = app($this->qiyuesuoService)->getContractUrl($contractId, $data, $own, $server);
        }
        return $result;
    }
    /**
     * 检测契约锁服务
     *
     * @param [type] $data
     * @param [type] $userInfo
     *
     * @return void
     * @author yml
     */
    public function checkServer($data, $userInfo)
    {
        if (!$data || !isset($data['serverType'])) {
            // 提示：未获取到契约锁服务
            return ['code' => ['get_service_failed', 'electronicsign']];
        }
        if ($data['serverType'] == 'public') {
            // 公有云
            $qiyuesuo = app($this->qiyuesuoPublicCloudService)->initByServer($data);
        } else {
            // 私有云
            $qiyuesuo = app($this->qiyuesuoService)->initByServer($data);
        }
        $result = $qiyuesuo->checkServer();
        if (isset($result['qys_code']) && $result['qys_code'] == 0) {
             return true;
        } else {
            return $result;
            return ['code' => ['check_server_failed', 'electronicsign']];
        }
    }
    /**
     * 获取电子相关日志
     *
     * @param [type] $params
     *
     * @return void
     * @author yml
     */
    public function getContractLogsList($params)
    {
        $params = $this->parseParams($params);

        $response = isset($params['response']) ? $params['response'] : 'both';
        $list = [];
        $count = 0;
        if (!in_array($response, ['both', 'count', 'data'])) {
            return ['code' => ['0x093517', 'electronicsign']];
        }
        if ($response == 'both' || $response == 'count') {
            $count = app($this->qiyuesuoContractLogRepository)->getCount($params);
        }

        if (($response == 'both' && $count > 0) || $response == 'data') {
            foreach (app($this->qiyuesuoContractLogRepository)->getList($params) as $new) {
                $list[] = $new;
            }
        }

		return $response == 'both' ? ['total' => $count, 'list' => $list] : ($response == 'data' ? $list : $count);
	}
	/**
	 * 公章签署
	 *
	 * @param [type] $data
	 * @param [type] $userInfo
	 * @param string $type
	 *
	 * @return void
	 * @author yml
	 */
	public function signByCompany($data, $userInfo)
	{
		$flowId = isset($data['flowInfo']) && isset($data['flowInfo']['flowId']) ? $data['flowInfo']['flowId'] : 0;
		$runId = isset($data['flowInfo']) && isset($data['flowInfo']['runId']) ? $data['flowInfo']['runId'] : 0;
		$log = [
			'flowId' => $flowId,
			'runId' => $runId,
			'userid' => $userInfo['user_id'],
			'ipaddress' => getClientIp(),
			'createTime' => date('Y-m-d H:i:s'),
			'requestJson' => json_encode($data),
			'action' => 'company_sign_contract',
		];
		if (!isset($data['flowInfo']) || !$flowId || !$runId || !isset($data['formData'])) {
			//提示：参数流程信息和表单数据未找到
			$log['responseJson'] = json_encode(['message' => trans('electronicsign.0x093517')]);
			app($this->qiyuesuoService)->addLog($this->qiyuesuoContractLogRepository, $log);
			return ['code' => ['0x093517', 'electronicsign']];
		}
		$config = app($this->qiyuesuoConfigRepository)->getItem('qys_on_off');
		if ($config && isset($config[0]) && isset($config[0]['paramValue']) && $config[0]['paramValue'] == 0) {
			//提示：配置项契约锁服务未开启
			$log['responseJson'] = json_encode(['message' => trans('electronicsign.0x093533')]);
			app($this->qiyuesuoService)->addLog($this->qiyuesuoContractLogRepository, $log);
			return ['code' => ['0x093533', 'electronicsign']];
		}
		// 通过流程id获取服务  根据服务类型请求服务
		$server = app($this->qiyuesuoServerRepository)->getServerByFlowId($data['flowInfo']['flowId']);
		if (!$server) {
			// 提示：未获取到契约锁服务
			$log['responseJson'] = json_encode(['message' => trans('electronicsign.get_service_failed')]);
			app($this->qiyuesuoService)->addLog($this->qiyuesuoContractLogRepository, $log);
			return ['code' => ['get_service_failed', 'electronicsign']];
		}
		$log['serverId'] = $server->serverId;
		$serverType = $server->serverType;
		// 解析签署章信息 -- 根据runId获取合同相关信息
		$contract = app($this->flowRunRelationQysContractInfoRepository)->getByRunId($runId);
		if (!$contract) {
			// 获取合同信息失败
			$log['responseJson'] = json_encode(['message' => trans('electronicsign.get_contract_failed')]);
			app($this->qiyuesuoService)->addLog($this->qiyuesuoContractLogRepository, $log);
			return ['code' => ['get_contract_failed', 'electronicsign']];
		}
		$contractId = $contract->contractId ?? 0;
		$tenantName = $contract->initiateurField ?? '';
		if (!$contractId || !$tenantName) {
			// 获取合同编号或签署方名称失败
			$log['responseJson'] = json_encode(['message' => trans('electronicsign.company_sign_get_contractId_or_tenantName_failed')]);
			app($this->qiyuesuoService)->addLog($this->qiyuesuoContractLogRepository, $log);
			return ['code' => ['company_sign_get_contractId_or_tenantName_failed', 'electronicsign']];
		}
		// 获取合同文档id
		$documents = app($this->flowRunRelationQysContractRepository)->getDocumentIdByRunId($runId);
		if (!$documents) {
			// 获取合同文档信息失败
			$log['responseJson'] = json_encode(['message' => trans('electronicsign.company_sign_get_documents_failed')]);
			app($this->qiyuesuoService)->addLog($this->qiyuesuoContractLogRepository, $log);
			return ['code' => ['company_sign_get_documents_failed', 'electronicsign']];
		}
		if ($serverType == 'public') {
			// 公有云
			$qiyuesuo = app($this->qiyuesuoPublicCloudService)->initByServer($server);
		} else {
			// 私有云
			$qiyuesuo = app($this->qiyuesuoService)->initByServer($server);
		}
		// 获取合同状态 --- 草稿的发起合同
		$checkStatus = $qiyuesuo->checkContractStatus($flowId, $contractId);
		if (isset($checkStatus['code'])) {
			// 合同草稿发起合同失败
			$message = $checkStatus['message'] ?? trans('electronicsign.draft_send_contract_failed');
			$log['responseJson'] = json_encode(['message' => $message]); //trans('electronicsign.get_contract_failed')
			app($this->qiyuesuoService)->addLog($this->qiyuesuoContractLogRepository, $log);
			return $checkStatus;
		}
		// 获取平台方公章签署
		$signInfo = [];
		if ($serverType == 'public') {
			$companySignType = 'COMPANY';
			$signInfo = app($this->flowRunRelationQysContractSignInfoRepository)->getFieldInfo(['runId' => $runId, 'tenantName' => $tenantName, 'type' => 'COMPANY']);
		} else {
			$companySignType = 'SEAL_CORPORATE';
			$signInfo = app($this->flowRunRelationQysContractSignInfoRepository)->getFieldInfo(['runId' => $runId, 'tenantName' => $tenantName, 'type' => 'CORPORATE']);
		}
		if (!$signInfo) {
			// 解析公章签署信息失败
			$log['responseJson'] = json_encode(['message' => trans('electronicsign.company_sign_parse_sign_info_failed')]);
			app($this->qiyuesuoService)->addLog($this->qiyuesuoContractLogRepository, $log);
			return ['code' => ['company_sign_parse_sign_info_failed', 'electronicsign']];
		} else {
			$signInfo = $signInfo[0];
		}
		// 公章id
		$sealId = $signInfo['seal'] ?? '';
		if (!$sealId) {
			$log['responseJson'] = json_encode(['message' => trans('electronicsign.company_sign_parse_seal_failed')]);
			app($this->qiyuesuoService)->addLog($this->qiyuesuoContractLogRepository, $log);
			return ['code' => ['company_sign_parse_seal_failed', 'electronicsign']];
		}
		$keyword = $signInfo['keyword'] ?? '';
		if (!$keyword && $serverType == 'public') {
			$log['responseJson'] = json_encode(['message' => trans('electronicsign.company_sign_public_cloud_need_keyword')]);
			app($this->qiyuesuoService)->addLog($this->qiyuesuoContractLogRepository, $log);
			return ['code' => ['company_sign_public_cloud_need_keyword', 'electronicsign']];
		}
		$stampers = [];
		foreach ($documents as $documentKey => $documentVal) {
			$companyStamper = [
				'documentId' => $documentVal,
				'type' => $companySignType,
				'sealId' => $sealId,
			];
			if ($keyword) {
				$companyStamper['keyword'] = $keyword;
				$companyStamper['keywordIndex'] = 0;
			}
			array_unshift($stampers, $companyStamper);
		}
		if (!$contractId || !$tenantName || !$stampers) {
			// 确认签署公章所需的必填信息：合同号、签署公司名、签署印章信息
			$log['responseJson'] = json_encode(['message' => trans('electronicsign.company_sign_required_fields')]);
			app($this->qiyuesuoService)->addLog($this->qiyuesuoContractLogRepository, $log);
			return ['code' => ['company_sign_required_fields', 'electronicsign']];
		}
		$params = [
			'contractId' => $contractId, // 合同ID
			'tenantName' => $tenantName, // 公司名称
			'stampers' => $stampers, // 签署位置，为空时签署不可见签名
		];
		$result = $qiyuesuo->signByCompany($params);
		if (isset($result['code'])) {
			$log['responseJson'] = json_encode(['message' => isset($result['code'][1]) ? $result['code'][1] : trans('electronicsign.get_contract_failed')]);
		} else {
			$log['responseJson'] = json_encode(['message' => trans('electronicsign.operate_success')]);
		}
		app($this->qiyuesuoService)->addLog($this->qiyuesuoContractLogRepository, $log);
		return $result;
	}
	/**
	 * 电子合同的操作-草稿合同的删除、签署中合同的撤回、已完成合同作废
	 *
	 * @param [type] $data ['flowInfo' => '流程相关信息', 'type' => '操作类型：delete 删除，recall 撤回，cancel 作废']
	 * @param [type] $userInfo 用户信息
	 *
	 * @return void
	 * @author yml
	 */
	public function invalidContract($data, $userInfo)
	{
		$flowId = isset($data['flowInfo']) && isset($data['flowInfo']['flowId']) ? $data['flowInfo']['flowId'] : 0;
		$runId = isset($data['flowInfo']) && isset($data['flowInfo']['runId']) ? $data['flowInfo']['runId'] : 0;
		$type = $data['type'] ?? '';
		$log = [
			'flowId' => $flowId,
			'runId' => $runId,
			'userid' => $userInfo['user_id'],
			'ipaddress' => getClientIp(),
			'createTime' => date('Y-m-d H:i:s'),
			'requestJson' => json_encode($data),
			'action' => $type . '_contract',
		];
		// 通过流程id获取服务  根据服务类型请求服务
		$server = app($this->qiyuesuoServerRepository)->getServerByFlowId($data['flowInfo']['flowId']);
		if (!$server) {
			// 提示：未获取到契约锁服务
			$log['responseJson'] = json_encode(['message' => trans('electronicsign.get_service_failed')]);
			app($this->qiyuesuoService)->addLog($this->qiyuesuoContractLogRepository, $log);
			return ['code' => ['get_service_failed', 'electronicsign']];
		}
		$log['serverId'] = $server->serverId;
		if (!isset($data['flowInfo']) || !$flowId || !$runId) {
			//提示：参数流程信息和表单数据未找到
			$log['responseJson'] = json_encode(['message' => trans('electronicsign.0x093517')]);
			app($this->qiyuesuoService)->addLog($this->qiyuesuoContractLogRepository, $log);
			return ['code' => ['0x093517', 'electronicsign']];
		}
		// 1.判断流程对应合同存在与否
		$checkExist = app($this->flowRunRelationQysContractInfoRepository)->getFieldInfo(['runId' => $runId]);
		if (!$checkExist) {
			// 未查到流程有关联合同，请确认后在执行此操作
			$log['responseJson'] = json_encode(['message' => trans('electronicsign.contract_not_found')]);
			app($this->qiyuesuoService)->addLog($this->qiyuesuoContractLogRepository, $log);
			return ['code' => ['contract_not_found', 'electronicsign']];
		}
		$contractId = isset($checkExist[0]) && isset($checkExist[0]['contractId']) ? $checkExist[0]['contractId'] : 0;
		if (!$contractId) {
			// 未查到流程有关联合同，请确认后在执行此操作
			$log['responseJson'] = json_encode(['message' => trans('electronicsign.contract_not_found')]);
			app($this->qiyuesuoService)->addLog($this->qiyuesuoContractLogRepository, $log);
			return ['code' => ['contract_not_found', 'electronicsign']];
		}
		$config = app($this->qiyuesuoConfigRepository)->getItem('qys_on_off');
		if ($config && isset($config[0]) && isset($config[0]['paramValue']) && $config[0]['paramValue'] == 0) {
			//提示：配置项契约锁服务未开启
			$log['responseJson'] = json_encode(['message' => trans('electronicsign.0x093533')]);
			app($this->qiyuesuoService)->addLog($this->qiyuesuoContractLogRepository, $log);
			return ['code' => ['0x093533', 'electronicsign']];
		}
		$serverType = $server->serverType;
		if ($serverType == 'public') {
			// 公有云
			$qiyuesuo = app($this->qiyuesuoPublicCloudService)->initByServer($server);
		} else {
			// 私有云
			$qiyuesuo = app($this->qiyuesuoService)->initByServer($server);
		}
		// 2.判断合同状态是否是草稿    ---- 在对应私有或公有service处理
		// 3.调用删除接口   ---- 返回格式  true 或者['code'=>['','']]
		$deleteResponse = $qiyuesuo->invalidContract($contractId, $type);
		// 4.删除数据库相关数据  --- 作废/撤回是否执行此操作待定
		if (isset($deleteResponse['code'])) {
			// 记录日志，返回错误信息
			$codeKey = $deleteResponse['code'][0] ?? '';
			$codeVal = $deleteResponse['code'][1] ?? '';
			$message = $codeKey && $codeVal ? trans('electronicsign.' . $codeKey) : (!$codeKey ? $codeVal : trans('electronicsign.operate_failed'));
			$log['responseJson'] = json_encode(['message' => $message]);
			app($this->qiyuesuoService)->addLog($this->qiyuesuoContractLogRepository, $log);
			return $deleteResponse;
		} else {
			// 删除相关数据 --- 考虑软删除
			if ($type == 'delete') {
				// 删除上传的合同文档信息
				$res1 = app($this->flowRunRelationQysContractRepository)->deleteByWhere(['contractId' => [$contractId]]);
				// 删除合同相关信息
				$res2 = app($this->flowRunRelationQysContractInfoRepository)->deleteByWhere(['contractId' => [$contractId]]);
				// 删除签署相关信息
				$res3 = app($this->flowRunRelationQysContractSignInfoRepository)->deleteByWhere(['contractId' => [$contractId]]);
			}
			$log['responseJson'] = json_encode(['message' => trans('electronicsign.operate_success')]);
			app($this->qiyuesuoService)->addLog($this->qiyuesuoContractLogRepository, $log);
			return true;
		}
	}
	/**
	 * 流程流转触发 --- 放队列执行
	 *
	 * @param [type] $flowTurnType
	 * @param [type] $flowId
	 * @param [type] $runId
	 * @param [type] $flowProcess
	 * @param [type] $processId
	 * @param [type] $next_flow_process
	 * @param $flowData
	 * @param $flowFormDataParam
	 * @param [array] $userInfo
	 *
	 * @return void
	 * @author yml
	 */
	public function syncFlowOutsend($flowTurnType, $flowId, $runId, $flowProcess, $processId, $next_flow_process, $flowData, $flowFormDataParam, $userInfo)
	{
		$type = 'flow';
		$params = compact('flowTurnType', 'flowId', 'runId', 'flowProcess', 'processId', 'next_flow_process', 'flowData', 'flowFormDataParam', 'userInfo', 'type');
		if ($this->checkSettingExist($flowId, 'all')) {
			Queue::push(new SyncQiyuesuoJob($params));
		}
	}

	public function flowOutsend($params)
	{
		extract($params);
		if (!isset($flowTurnType) || $flowTurnType != "back") {
			// 查询契约锁物理印章集成外发配置 -- 发起用印申请授权 --- 默认退回不触发
			// 当前节点提交触发
			$currentProcessSetInfo = app($this->qiyuesuoService)->checkOutsend($flowId, $flowProcess, $runId, 'seal_apply');
			// 下个节点到达触发
			$nestProcessSetInfo = app($this->qiyuesuoService)->checkOutsend($flowId, $next_flow_process, $runId, 'seal_apply');
			if ((isset($currentProcessSetInfo['status']) && $currentProcessSetInfo['status'] == 1 && isset($currentProcessSetInfo['flowOutsendTimingSubmit']) && $currentProcessSetInfo['flowOutsendTimingSubmit'] == 1) || (isset($nestProcessSetInfo['status']) && $nestProcessSetInfo['status'] == 1 && isset($nestProcessSetInfo['flowOutsendTimingArrival']) && $nestProcessSetInfo['flowOutsendTimingArrival'] == 1)) {
				app($this->qiyuesuoService)->createSealApplyV1(array_merge($flowData, $flowFormDataParam), $userInfo);
			}
			// 查询契约锁物理印章集成外发配置 -- 下载用印后文件
			$currentProcessSetInfo = app($this->qiyuesuoService)->checkOutsend($flowId, $flowProcess, $runId, 'seal_apply_images_download');
			// 下个节点到达触发
			$nestProcessSetInfo = app($this->qiyuesuoService)->checkOutsend($flowId, $next_flow_process, $runId, 'seal_apply_images_download');
			if ((isset($currentProcessSetInfo['status']) && $currentProcessSetInfo['status'] == 1 && isset($currentProcessSetInfo['flowOutsendTimingSubmit']) && $currentProcessSetInfo['flowOutsendTimingSubmit'] == 1) || (isset($nestProcessSetInfo['status']) && $nestProcessSetInfo['status'] == 1 && isset($nestProcessSetInfo['flowOutsendTimingArrival']) && $nestProcessSetInfo['flowOutsendTimingArrival'] == 1)) {
				app($this->qiyuesuoService)->sealApplyImagesDownload(array_merge($flowData, $flowFormDataParam), $userInfo);
			}
		}
		// 可外发的情况 1.提交不管是否退回 2.提交退回不触发 3.到达不管是否退回 4.到达退回不触发 5.仅退回
		// 查询契约锁电子合同 --- 创建合同
		$contractData = [
			'flowInfo' => $flowFormDataParam,
			'formData' => $flowData,
		];
		$currentProcessSetInfo = app($this->qiyuesuoService)->checkOutsend($flowId, $flowProcess, $runId, 'create_contract');
		// 下个节点到达触发
		$nestProcessSetInfo = app($this->qiyuesuoService)->checkOutsend($flowId, $next_flow_process, $runId, 'create_contract');
		// 当前节点提交【考虑退回】下个节点到达
		if ((isset($currentProcessSetInfo['status']) && $currentProcessSetInfo['status'] == 1 && isset($currentProcessSetInfo['flowOutsendTimingArrival'])) && (($currentProcessSetInfo['flowOutsendTimingArrival'] == 0 && ($flowTurnType != "back" || ($flowTurnType == "back" && isset($currentProcessSetInfo['back']) && $currentProcessSetInfo['back'] == 0))) || ($currentProcessSetInfo['flowOutsendTimingArrival'] == 2 && $flowTurnType == "back")) || (isset($nestProcessSetInfo['status']) && $nestProcessSetInfo['status'] == 1 && isset($nestProcessSetInfo['flowOutsendTimingArrival']) && ($nestProcessSetInfo['flowOutsendTimingArrival'] == 1 && ($flowTurnType != "back" || ($flowTurnType == "back" && isset($nestProcessSetInfo['back']) && $nestProcessSetInfo['back'] == 0))))) {
			$this->createContractV1($contractData, $userInfo, 'flow');
		}

        // 查询企业签章动作
        $currentProcessSetInfo = app($this->qiyuesuoService)->checkOutsend($flowId, $flowProcess, $runId, 'company_sign');
        // 下个节点到达触发
        $nestProcessSetInfo = app($this->qiyuesuoService)->checkOutsend($flowId, $next_flow_process, $runId, 'company_sign');
        if ((isset($currentProcessSetInfo['status']) && $currentProcessSetInfo['status'] == 1 && isset($currentProcessSetInfo['flowOutsendTimingArrival'])) && (($currentProcessSetInfo['flowOutsendTimingArrival'] == 0 && ($flowTurnType != "back" || ($flowTurnType == "back" && isset($currentProcessSetInfo['back']) && $currentProcessSetInfo['back'] == 0))) || ($currentProcessSetInfo['flowOutsendTimingArrival'] == 2 && $flowTurnType == "back")) || (isset($nestProcessSetInfo['status']) && $nestProcessSetInfo['status'] == 1 && isset($nestProcessSetInfo['flowOutsendTimingArrival']) && ($nestProcessSetInfo['flowOutsendTimingArrival'] == 1 && ($flowTurnType != "back" || ($flowTurnType == "back" && isset($nestProcessSetInfo['back']) && $nestProcessSetInfo['back'] == 0))))) {
             $this->signByCompany($contractData, $userInfo);
        }
        // 查询契约锁电子合同 --- 下载合同
        $currentProcessSetInfo = app($this->qiyuesuoService)->checkOutsend($flowId, $flowProcess, $runId, 'download_contract');
        // 下个节点到达触发
        $nestProcessSetInfo = app($this->qiyuesuoService)->checkOutsend($flowId, $next_flow_process, $runId, 'download_contract');
        if ((isset($currentProcessSetInfo['status']) && $currentProcessSetInfo['status'] == 1 && isset($currentProcessSetInfo['flowOutsendTimingArrival'])) && (($currentProcessSetInfo['flowOutsendTimingArrival'] == 0 && ($flowTurnType != "back" || ($flowTurnType == "back" && isset($currentProcessSetInfo['back']) && $currentProcessSetInfo['back'] == 0))) || ($currentProcessSetInfo['flowOutsendTimingArrival'] == 2 && $flowTurnType == "back")) || (isset($nestProcessSetInfo['status']) && $nestProcessSetInfo['status'] == 1 && isset($nestProcessSetInfo['flowOutsendTimingArrival']) && ($nestProcessSetInfo['flowOutsendTimingArrival'] == 1 && ($flowTurnType != "back" || ($flowTurnType == "back" && isset($nestProcessSetInfo['back']) && $nestProcessSetInfo['back'] == 0))))) {
            // 获取合同id
           $contractId =  $this->getContractIdbyFlowRunId($runId);
           $downloadContractData = $flowFormDataParam;
           $downloadContractData['type'] = 'download';
           $downloadContractData['processId'] = $processId;
           $downloadContractData['flowProcess'] = $flowProcess;
            $this->getContractUrl($contractId, $downloadContractData, $userInfo, 'flow');
        }

        //查询契约锁电子合同是否配置了节点流转-删除合同、撤回合同、作废合同
        foreach (['delete', 'recall', 'cancel'] as $typeVal) {
            $currentProcessSetInfo = app($this->qiyuesuoService)->checkOutsend($flowId, $flowProcess, $runId, $typeVal . '_contract');
            // 下个节点到达触发
            $nestProcessSetInfo = app($this->qiyuesuoService)->checkOutsend($flowId, $next_flow_process, $runId, $typeVal . '_contract');
            if ((isset($currentProcessSetInfo['status']) && $currentProcessSetInfo['status'] == 1 && isset($currentProcessSetInfo['flowOutsendTimingArrival'])) && (($currentProcessSetInfo['flowOutsendTimingArrival'] == 0 && ($flowTurnType != "back" || ($flowTurnType == "back" && isset($currentProcessSetInfo['back']) && $currentProcessSetInfo['back'] == 0))) || ($currentProcessSetInfo['flowOutsendTimingArrival'] == 2 && $flowTurnType == "back")) || (isset($nestProcessSetInfo['status']) && $nestProcessSetInfo['status'] == 1 && isset($nestProcessSetInfo['flowOutsendTimingArrival']) && ($nestProcessSetInfo['flowOutsendTimingArrival'] == 1 && ($flowTurnType != "back" || ($flowTurnType == "back" && isset($nestProcessSetInfo['back']) && $nestProcessSetInfo['back'] == 0))))) {
                $deleteContractData = $contractData;
                $deleteContractData['type'] = $typeVal;
                 $this->invalidContract($deleteContractData, $userInfo);
            }
        }
        return true;
    }

    public function autoCheckContractFormConfig($params)
    {
        $flowId = $params['flow_id'] ?? '';
        $formId = $params['form_id'] ?? '';
        $config = $params['config'] ?? [];
        $type = $params['type'] ?? 'contract';
        if (!$flowId) {
            return ['code' => ['not_found_workflow_info', 'electronicsign']];
        }
        if (!$formId) {
            // 获取formid
        }
        $requiredField = [];
        $formData = app($this->flowFormService)->getParseForm($formId);
        if ($formData) {
            $formData = array_column($formData, 'control_id', 'control_title');
            foreach ($formData as $formKey => $formVal) {
                if (preg_match('/.*】/', $formKey, $matches) !== false) {
                    if (isset($matches[0])) {
                        $newKey = str_replace($matches[0], '', $formKey);
                        $formData[$newKey] = $formVal;
                        unset($formData[$formKey]);
                    }
                }
            }
        }
        // 获取表单数据 --- 解析为需要的格式
        // 配置字段对应数组  --- 注意多语言
        if ($type == 'contract') {
            $configFields = [
                'subjectField' => trans('electronicsign.subject_field_name'),
                'templateField' => trans('electronicsign.template_field_name'),
                'documentField' => trans('electronicsign.document_field_name'),
                'categoryField' => trans('electronicsign.category_field_name'),
                'initiateurField' => trans('electronicsign.initiateur_field_name'),
                'signValidityField' => trans('electronicsign.signValidity_field_name'),
                'creatorNameField' => trans('electronicsign.creatorName_field_name'),
                'creatorContactField' => trans('electronicsign.creatorContact_field_name'),
            ];
        } else {
            $configFields = [
                'subjectField' => trans('electronicsign.subject_field'),
                'descriptionField' => trans('electronicsign.description_field'),
                'applyerNameField' => trans('electronicsign.applyer_name_field'),
                'applyerContactField' => trans('electronicsign.applyer_contact_field'),
                'deviceNoField' => trans('electronicsign.device_no_field'),
                'countField' => trans('electronicsign.count_field'),
                'mobileField' => trans('electronicsign.seal_used_person_mobile_field'),
                'startTimeField' => trans('electronicsign.start_time_field'),
                'endTimeField' => trans('electronicsign.end_time_field'),
                'tenantNameField' => trans('electronicsign.tenant_name_field'),
                'sealCategoryIdField' => trans('electronicsign.seal_category_name_field'),
                'sealDocumentField' => trans('electronicsign.seal_document_field'),
            ];
        }
        // 主表数据解析
        foreach ($configFields as $mainKey => $mainVal) {
            if (isset($formData[$mainVal]) && !empty($formData[$mainVal])) {
                $config[$mainKey] = $formData[$mainVal];
            } else {
                if ($type == 'contract' && in_array($mainKey, ['subjectField'])) {
                    $requiredField[$mainKey] = $mainVal;
                }
                if ($type == 'seal_apply' && in_array($mainKey, ['subjectField', 'tenantNameField', 'applyerContactField', 'deviceNoField', 'countField', 'mobileField'])) {
                    $requiredField[$mainKey] = $mainVal;
                }
            }
        }
        if (!$config) {
            return ['code' => ['parse_error_not_found_related_field', 'electronicsign']];
        }
        return compact('config', 'requiredField');
    }
    /**
     * 获取流程集成的契约锁相关配置并返回
     *
     * @param [type] $flowId
     *
     * @return void
     * @author yml
     */
    public function exportQiyuesuoFlowConfig($flowId)
    {
        $return = [];
        // 电子合同
        $setting = app($this->qiyuesuoSettingRepository)->getSettingDetailByFlowId($flowId);
        if ($setting) {
            $setting['type'] = 'contract';
            $return[] = $setting;
        }
        // 物理用印
        $sealSetting = app($this->qiyuesuoSealApplySettingRepository)->getSettingByFlowId($flowId);
        if ($sealSetting) {
            $sealSetting = $sealSetting->toArray();
            $sealSetting['type'] = 'physical_seal';
            $sealSetting['settinghas_one_server'] = $sealSetting['has_one_qiyuesuo_server'];
            unset($sealSetting['has_one_qiyuesuo_server']);
            $return[] = $sealSetting;
        }
        return $return;
    }
    /**
     * 导入契约锁相关流程时同步相关配置
     *
     * @param integer $newFlowId
     * @param array $flowData
     * @param array $nodeIdMap
     * @param [type] $user
     *
     * @return void
     * @author yml
     */
    public function importQiyuesuoFlowConfig(int $newFlowId, array $flowData, array $nodeIdMap, $user)
    {
        $version = $flowData['version'] ?? '';
        $qiyuesuoConfigs = $flowData['qiyuesuo'] ?? [];
        $server = app($this->qiyuesuoServerRepository)->getLastServer([], ['serverId' => 'desc']);
        if($qiyuesuoConfigs) {
            foreach($qiyuesuoConfigs as $config) {
                $type = $config['type'] ?? '';
                if ($type == 'contract') {
                    $this->importQiyuesuoContractFlowConfig($newFlowId, $config, $nodeIdMap, $user['user_id'], $server);
                } else if ($type == 'physical_seal') {
                    $this->importQiyuesuoSealApplyFlowConfig($newFlowId, $config, $nodeIdMap, $user['user_id'], $server);
                }
            }
        }
        return true;
    }

    private function importQiyuesuoContractFlowConfig($newFlowId, $config, $nodeIdMap, $user_id, $server)
    {
        $data['setting_data'] = array_intersect_key($config, array_flip(app($this->qiyuesuoSettingRepository)->getTableColumns()));
        $data['sign_info'] = $config['settinghas_many_sign_info'] ?? [];
        $data['operation_info'] = $config['settinghas_many_operation_info'] ?? [];
        $data['outsend_info'] = $config['settinghas_many_outsend_info'] ?? [];
        unset($data['setting_data']['settingId']);
        unset($data['setting_data']['created_at']);
        unset($data['setting_data']['updated_at']);
        $data['setting_data']['serverId'] = $this->getServerId($config, $server);
        $data['setting_data']['workflowId'] = $newFlowId;
        if ($setting = app($this->qiyuesuoSettingRepository)->insertData($data['setting_data'])) {
            //保存签署信息
            if ($data['sign_info']) {
                foreach ($data['sign_info'] as $key => $val) {
                    unset($data['sign_info'][$key]['signInfoId']);
                    $data['sign_info'][$key]['settingId'] = $setting->settingId;
                }
                app($this->qiyuesuoSettingSignInfoRepository)->insertMultipleData($data['sign_info']);
            }
            //保存节点操作权限
            if ($data['operation_info']) {
                foreach ($data['operation_info'] as $key => $val) {
                    unset($data['operation_info'][$key]['operationId']);
                    $data['operation_info'][$key]['nodeId'] = $nodeIdMap[$val['nodeId']] ?? $val['nodeId'];
                    $data['operation_info'][$key]['settingId'] = $setting->settingId;
                }
                app($this->qiyuesuoSettingOperationInfoRepository)->insertMultipleData($data['operation_info']);
            }

            if ($data['outsend_info']) {
                foreach ($data['outsend_info'] as $key => $val) {
                    unset($data['outsend_info'][$key]['outsendInfoId']);
                    $data['outsend_info'][$key]['nodeId'] = $nodeIdMap[$val['nodeId']] ?? $val['nodeId'];
                    $data['outsend_info'][$key]['settingId'] = $setting->settingId;
                    $data['outsend_info'][$key]['flowId'] = $newFlowId;
                    $data['outsend_info'][$key]['type'] = 'contract';
                }
                app($this->qiyuesuoSealApplySettingOutsendInfoRepository)->insertMultipleData($data['outsend_info']);
            }
            $this->saveIntegrationChangeFlowLable($newFlowId);
            Cache::put('qiyuesuo_integration_'.$newFlowId, true, 24*60*60);;
            return true;
        } else {
            return false;
        }
    }

    private function importQiyuesuoSealApplyFlowConfig($newFlowId, $config, $nodeIdMap, $user_id, $server)
    {
        $config['workflowId'] = $newFlowId;
        unset($config['created_at']);
        unset($config['updated_at']);
        unset($config['settingId']);  
        $data['setting_data']['serverId'] = $this->getServerId($config, $server);
        $config['serverId'] = $server && $server->serverId ? $server->serverId : 0;
        $insertData = array_intersect_key($config, array_flip(app($this->qiyuesuoSealApplySettingRepository)->getTableColumns()));
        $resAdd = app($this->qiyuesuoSealApplySettingRepository)->insertData($insertData);
        if ($resAdd) {
            // 触发节点设置 保存
            $outsendInfos = $config['has_many_outsend_info'] ?? [];
            if ($outsendInfos) {
                foreach ($outsendInfos as $key => $val) {
                    unset($outsendInfos[$key]['outsendInfoId']);
                    $outsendInfos[$key]['nodeId'] = $nodeIdMap[$val['nodeId']] ?? $val['nodeId'];
                    $outsendInfos[$key]['settingId'] = $resAdd->settingId;
                    $outsendInfos[$key]['flowId'] = $newFlowId;
                }
                $this->handleOutsendInfo($outsendInfos, $resAdd->workflowId, $resAdd->settingId, 'add');
            }
            // 流程选中契约锁标签
            $this->saveIntegrationChangeFlowLable($newFlowId);
            Cache::put('qiyuesuo_integration_'.$resAdd->workflowId, true, 24*60*60);
        }
    }

    private function getServerId($config, $server)
    {
        $serverType = isset($config['settinghas_one_server']) && isset($config['settinghas_one_server']['serverType']) ? $config['settinghas_one_server']['serverType'] : 'private';
        if ($serverType && $server && $serverType == $server->serverType) {
            return $server->serverId ? $server->serverId : 0;
        } else {
            $server = app($this->qiyuesuoServerRepository)->getLastServer(['serverType' => $serverType], ['serverId' => 'desc']);
            return $server && $server->serverId ? $server->serverId : 0;
        }
    }

    /**
     * @param $contractId   /电子合同id/物理用印申请businessId
     * @param $contractStatus /电子合同状态值/物理用印申请状态值
     * @param $type
     * @return bool
     */
    public function sendMessage($params, $type)
    {
        // 电子合同通知
        if ($type == 'contract') {
            $contractId = $params['contractId'];
            $data = $params['data'];
            $contractStatus = $params['status'];
            $action = $data['type'];
            $contract = app($this->flowRunRelationQysContractInfoRepository)->getOneFieldInfo(['contractId' => $contractId]);
            if ($contract) {
                $runId = $contract->runId;
                $flowRunInfo = app($this->flowRunRepository)->getDetail($runId);
                $contractStatusNames = [
                    'DRAFT' => trans('electronicSign.draft'),
                    'FILLING' => trans('electronicSign.filling'),
                    'SIGNING' => trans('electronicSign.signing'),
                    'COMPLETE' => trans('electronicSign.complete'),
                    'REJECTED' => trans('electronicSign.rejected'),
                    'RECALLED' => trans('electronicSign.recalled'),
                    'EXPIRED' => trans('electronicSign.expired'),
                    'TERMINATING' => trans('electronicSign.terminating'),
                    'TERMINATED' => trans('electronicSign.terminated'),
                    'DELETE' => trans('electronicSign.deleted'),
                    'FINISHED' => trans('electronicSign.finished'),
                ];
                $contractActionNames = [
                    'SAVE_CONTRACT_DRAFT_RECT' => trans('electronicSign.save_draft_in_signed_location'),// '保存草稿-签署位置保存',
                    'CONTRACT_SEND' => trans('electronicSign.contract_send'),// '发起合同',
                    'CONTRACT_COMPLETE' => trans('electronicSign.contract_complete'),// '文件全部签署完成',
                    'CONTRACT_RECALL' => trans('electronicSign.contract_recall'),// '撤回合同',
                    'CONTRACT_SIGN' => trans('electronicSign.contract_sign'),// '签署合同',
                    'CONTRACT_REJECTED' => trans('electronicSign.contract_rejected'),// '退回合同',
                    'CONTRACT_EXPIRED' => trans('electronicSign.contract_expired'),// '合同已过期',
                    'CONTRACT_TERMINATE' => trans('electronicSign.contract_terminate'),// '作废合同',
                    'CONTRACT_TERMINATE_REJECTED' => trans('electronicSign.contract_terminate_rejected'),// '拒绝作废合同',
                ];
                $contentParam = [
                    'flowTitle' => $flowRunInfo->run_name,
                    'contractName' => $contract->subjectField,
                    'contractStatus' => $contractStatusNames[$contractStatus] ?? $contractStatus,
                    'contractAction' => $contractActionNames[$action] ?? $action
                ];
                if ($action == 'CONTRACT_SIGN' && isset($data['operatorName']) && isset($data['operatorMobile'])) {
                    $contentParam['contractAction'] .= trans('electronicSign.signer') . $data['operatorName'] . $data['operatorMobile'];
                }
                $stateParams = [
                    'run_id' => $flowRunInfo->run_id,
                    'flow_id' => $flowRunInfo->flow_id
                ];
                // 解析创建人和签署人手机号和OA系统手机号匹配
                $creatorMobile = [];
                if ($contract->creatorField) {
                    if (strpos($contract->creatorField, '-') !== false) {
                        $creatorField = explode('-', $contract->creatorField);
                        if (preg_match("/^1[23456789]\d{9}$/", trim($creatorField[1])) > 0) {
                            $creatorMobile[] = $creatorField[1];
                        }
                    } else {
                        if (preg_match("/^1[23456789]\d{9}$/", trim($contract->creatorField)) > 0) {
                            $creatorMobile[] = $contract->creatorField;
                        }
                    }
                }
                // 解析签署信息的手机号
                $signInfos = app($this->flowRunRelationQysContractSignInfoRepository)->getFieldInfo(['contractId' => $contractId]);
                $signMobile = array_column($signInfos, 'contact');
                $mobiles = array_merge($creatorMobile, $signMobile);
                if ($mobiles) {
                    $users = app($this->userService)->getUserByPhoneNumber($mobiles, ['user.user_id', 'user_info.phone_number']);
                    if ($users) {
                        $userIds = array_column($users, 'user_id');
                        $userIds = $this->checkUserPermission($userIds, $runId);
                        if ($userIds) {
                            Eoffice::sendMessage([
                                'remindMark' => 'qiyuesuo-contract_change',
                                'toUser' => $userIds,
                                'contentParam' => $contentParam,
                                'stateParams' => $stateParams
                            ]);
                        }
                    }
                }
                return true;
            }
        } else {
            // 申请成功直接提醒  用印状态变化 USING:表示用印中,COMPLETE:表示正常结束
            $businessId = $params['businessId'] ?? '';
            $data = $params['data'] ?? [];
            if ($businessId && $data['status']) {
                $sealApplyStatusName = [
                    'USING' => trans('electronicSign.seal_using'),// '用印中',
                    'COMPLETE' => trans('electronicSign.seal_end'),// '用印结束',
                    'CREATE' => trans('electronicSign.seal_apply_success'),// '用印授权申请成功'
                ];
                $businessinfo = app($this->qiyuesuoSealApplyLogRepository)->getOneFieldInfo(['businessId' => [$businessId]]);
                $subjectField = app($this->qiyuesuoSealApplySettingRepository)->getFieldValue('subjectField', ['workflowId' => $businessinfo['flowId']]);
                $flowRunInfo = app($this->flowRunRepository)->getDetail($businessinfo['runId']);
                $signInfos = app($this->qiyuesuoSealApplyAuthLogRepository)->getFieldInfo(['businessId' => [$businessId]]);
                $requestData = json_decode($businessinfo['requestJson'], 1);
                $subject = $requestData[$subjectField] ?? '';
                $mobiles = array_column($signInfos, 'contact');
                $contentParam = [
                    'flowTitle' => $flowRunInfo->run_name,
                    'sealApplyName' => $subject,
                    'sealApplyStatus' => $sealApplyStatusName[$data['status']] ?? ''
                ];
                $stateParams = [
                    'run_id' => $flowRunInfo->run_id,
                    'flow_id' => $flowRunInfo->flow_id
                ];
                if ($mobiles) {
                    $users = app($this->userService)->getUserByPhoneNumber($mobiles, ['user.user_id', 'user_info.phone_number']);
                    if ($users) {
                        $userIds = array_column($users, 'user_id');
                        $userIds = $this->checkUserPermission($userIds, $flowRunInfo->run_id);
                        if ($userIds) {
                            Eoffice::sendMessage([
                                'remindMark' => 'qiyuesuo-seal_apply',
                                'toUser' => $userIds,
                                'contentParam' => $contentParam,
                                'stateParams' => $stateParams
                            ]);
                        }
                    }
                }
            }
        }
    }

    public function checkUserPermission($userIds, $runId)
    {
        $okUserIds = [];
        foreach ($userIds as $userId) {
            if ($userId) {
                $status = app($this->flowPermissionService)->verifyFlowHandleViewPermission(['type' => 'view', 'run_id' => $runId, 'user_id' => $userId]);
                if ($status) {
                    $okUserIds[] = $userId;
                }
            }
        }
        return $okUserIds;
    }
}
