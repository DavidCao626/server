<?php

namespace App\EofficeApp\HtmlSignature\Services;

use App;
use App\EofficeApp\Base\BaseService;

/**
 * 签章插件相关配置
 *
 * @author dingp
 *
 * @since  2018-01-12 创建
 */
class SignatureConfigService extends BaseService
{
	public function __construct()
	{
		parent::__construct();
		$this->signaturePlugInConfigRepository = 'App\EofficeApp\HtmlSignature\Repositories\SignaturePlugInConfigRepository';
		$this->signaturePlugInQiyuesuoSettingRepository = 'App\EofficeApp\HtmlSignature\Repositories\SignaturePlugInQiyuesuoSettingRepository';
		$this->qiyuesuoService = 'App\EofficeApp\ElectronicSign\Services\QiyuesuoService';
        $this->qiyuesuoSignatureLogsRepository = 'App\EofficeApp\HtmlSignature\Repositories\QiyuesuoSignatureLogsRepository';
        $this->qiyuesuoSignatureFlowConfigRepository = 'App\EofficeApp\HtmlSignature\Repositories\QiyuesuoSignatureFlowConfigRepository';
        $this->htmlSignatureRepository = 'App\EofficeApp\HtmlSignature\Repositories\HtmlSignatureRepository';
        $this->htmlSignatureService = 'App\EofficeApp\HtmlSignature\Services\HtmlSignatureService';
        $this->flowFormService = 'App\EofficeApp\Flow\Services\FlowFormService';
        $this->flowService = 'App\EofficeApp\Flow\Services\FlowService';
        $this->flowFormControlStructureRepository = 'App\EofficeApp\Flow\Repositories\FlowFormControlStructureRepository';
	}

	public function getSignatureConfig()
	{
		$list = app($this->signaturePlugInConfigRepository)->getList();
		return $list;
	}

	public function getQiyuesuoSignatureConfig($params)
	{
        $runId = $params['run_id'] ?? '';
        $flowId = $params['flow_id'] ?? '';
        $info = app($this->signaturePlugInQiyuesuoSettingRepository)->getOneFieldInfo(['setting_id' => 1]);
        if ($flowId) {
            // 获取对应流程的数据保护是否开启
            $config = app($this->qiyuesuoSignatureFlowConfigRepository)->getOneFieldInfo(['flow_id' => $flowId]);
            if ($info->data_protect == 1) {
                $info->data_protect = $config && $config->data_protect ? $config->data_protect : 0;
            }
        }
		return $info ? $info->toArray() : [];
	}

	public function saveQiyuesuoSignatureConfig($params, $settingId)
	{
		$params = array_intersect_key($params, array_flip(app($this->signaturePlugInQiyuesuoSettingRepository)->getTableColumns()));
		return app($this->signaturePlugInQiyuesuoSettingRepository)->updateData($params, ['setting_id' => $settingId]);
	}

	public function saveSignatureConfig($param)
	{
		// 先将之前启用的关闭，在将选中的启用
		app($this->signaturePlugInConfigRepository)->updateData(['is_use' => 0], ['is_use' => 1]);
		return app($this->signaturePlugInConfigRepository)->updateData(['is_use' => 1], ['config_id' => $param['config_id']]);
	}

    /** 服务签署获取印章及个人签字列表
     * @param $params
     * @param $user
     * @return array
     */
	public function serverSignatures($params, $user)
	{
	    $runId = $params['run_id'] ?? '';
        $nodeId = $params['node_id'] ?? '';
        $flowId = $params['flow_id'] ?? '';
        $formData = $params['form_data'] ?? [];
	    $userId = $user['user_id'];
	    $host = $params['host'] ?? '';
	    $currentTime = time();
		// 获取选择的契约锁服务id
		$settingId = $params['settingId'] ?? 1;
		$server = app($this->signaturePlugInQiyuesuoSettingRepository)->getDetail($settingId);
		if (!$server || !isset($server['server_id'])) {
            return [
                'code' => ['not_choose_qys_server', 'integrationCenter']
            ];
        }
		if (!$host || $server['oa_url'] != $host) {
		    return ['code' => ['oa_url_error', 'integrationCenter']];
        }
		$serverId = $server['server_id'] ?? 0;
        // 先根据流程查找有无设置 没有时取配置的默认企业名
        $company = $this->getCompanyName($flowId, $formData);
        if (!$company) {
            $company = $server['company_name'] ?? '';
        }
        if (!$company) {
            return [
                'code' => ['get_company_name_failed', 'integrationCenter']
            ];
        }
        $userPhoneNumber = $user['phone_number'] ? trim($user['phone_number']) : '';
        if (!$userPhoneNumber) {
            return [
                'code' => ['get_phone_number_failed', 'integrationCenter']
            ];
        }
		$signatory = [
		    'bizId' => $userId . '_' . $runId . '_' . $nodeId . '_'. $currentTime,
            'company' => ['name' => $company],
            'operator' => ['contact' => $userPhoneNumber]
        ];
        $filePath = public_path('integrationCenter/qiyuesuo/signature_' . $currentTime. '_' . $userId . rand(0, 99999) . '.txt');
        $myfile = fopen($filePath, "w");
        fwrite($myfile, json_encode($params));
        fclose($myfile);
        $file = curl_file_create(realpath($filePath));
		$params = [
			'data' => $file,
			'signatory' => json_encode($signatory),
            'businessData' => json_encode(['run_id' => $runId, 'node_id' =>$nodeId, 'user_id' => $userId, 'time' => $currentTime]),
            'oaUrl' => $server['oa_url'] ?? ($_SERVER['SERVER_ADDR'].':'.$_SERVER['SERVER_PORT'] ?? 'http://127.0.0.1:8010')
		];
		$url = app($this->qiyuesuoService)->serverSignatures($params, $serverId);
        unlink($filePath);
        // 业务id
        $url['bizId'] = $signatory['bizId'];
		return $url;
	}

    /** 保存签署信息
     * @param $params
     * $param = [
     *    'run_id' => '流程id',
     *    'node_id' => '节点id',
     *    'control_type' => '控件类型',
     *    'control_id' => '控件id',
     *    'seal_id' => '印章id',
     *    'seal_base64' => '印章base64',
     *    'seal_address' => '印章位置信息',
     *    'protect_data' => '保护数据',
     *    'signature' => '证书信息'
     * ];
     * @param $user
     * @return mixed
     */
    public function saveQiyuesuoSignatureLog($params, $user)
	{
        $insertData = array_intersect_key($params, array_flip(app($this->htmlSignatureRepository)->getTableColumns()));
        $insertData['user_id'] = $user['user_id'];
        $result = app($this->htmlSignatureRepository)->insertData($insertData);
        // $status = app($this->htmlSignatureRepository)->updateData(['signatureid' => 'qys_' .$result->id], ['id' => $result->id]);
        // if ($status) {
        //     $result->signatureid = 'qys_' .$result->id;
        // }
        return $result;
	}

    /** 编辑签章签署信息
     * @param $params
     * @param $user
     * @param $htmlSignatureId  日志id
     * @return mixed
     */
    public function updateQiyuesuoSignatureLog($params, $user, $htmlSignatureId)
    {
        $updateData = array_intersect_key($params, array_flip(app($this->htmlSignatureRepository)->getTableColumns()));
        return app($this->htmlSignatureRepository)->updateData($updateData, ['signatureid' => $htmlSignatureId]);
    }

    public function deleteQiyuesuoSignatureLog($htmlSignatureId)
    {
        return app($this->htmlSignatureRepository)->deleteByWhere(['signatureid' => [$htmlSignatureId]]);
    }

    public function getSignatureLog($htmlSignatureId)
    {
//        $params = $this->parseParams($params);
        $detail = app($this->htmlSignatureRepository)->getOneFieldInfo(['signatureid' => $htmlSignatureId]);
        return $detail ? $detail->toArray() : [];
    }

    /**
     * @param $user
     * @return array 获取当前签章插件
     */
    public function getCurrentService($user, $params)
    {
        $service = app($this->signaturePlugInConfigRepository)->getOneFieldInfo(['is_use' => 1]);
        if ($service && isset($service->type)) {
            $service->keysn = null;
            $service->qiyuesuo = null;
            switch ($service->type) {
                case 1:
                    // 金格 获取对应keysn
                    $service->keysn = app($this->htmlSignatureService)->getUserGoldgridSignatureKeysn($user['user_id']);
                    break;
                case 2:
                    //契约锁 获取契约锁签章插件类型
                    $service->qiyuesuo = $this->getQiyuesuoSignatureConfig($params);
                    break;
            }
            return $service;
        } else {
            return ['code' => ['get_signature_interface_failed', 'integrationCenter']];
        }
    }

    public function getSignatureLogList($params, $user, $token)
    {
        $params = $this->parseParams($params);
        $data  = $this->response(app($this->htmlSignatureRepository), 'getNum', 'getLogs', $params);
        // 校验数据保护的数据是否被篡改
        if (isset($data['list']) && !empty($data['list'])) {
            $runId = $params['run_id'] ?? '';
            $formId = $params['form_id'] ?? '';
            $formData = isset($params['formData']) ? json_decode($params['formData'], 1) : [];
            $data['list'] = $this->checkVerify($data['list'], $formId, $runId, $formData, $token);
        }
        return $data;
    }
    public function getFlowConfig($params)
    {
        $params = $this->parseParams($params);
        $data  = $this->response(app($this->qiyuesuoSignatureFlowConfigRepository), 'getListTotal', 'getList', $params);
        return $data;
    }
    public function getOneFlowConfig($configId)
    {
        return app($this->qiyuesuoSignatureFlowConfigRepository)->getDetail($configId);
    }

    public function saveFlowConfig($params)
    {
        $params = array_intersect_key($params, array_flip(app($this->qiyuesuoSignatureFlowConfigRepository)->getTableColumns()));
        if (app($this->qiyuesuoSignatureFlowConfigRepository)->getOneFieldInfo(['flow_id' => $params['flow_id']])) {
            app($this->qiyuesuoSignatureFlowConfigRepository)->updateData($params, ['flow_id' => [$params['flow_id']]]);
        } else {
            return app($this->qiyuesuoSignatureFlowConfigRepository)->insertData($params);
        }
    }

    public function updateFlowConfig($params, $configId)
    {
        $params = array_intersect_key($params, array_flip(app($this->qiyuesuoSignatureFlowConfigRepository)->getTableColumns()));
        return app($this->qiyuesuoSignatureFlowConfigRepository)->updateData($params, ['config_id' => [$configId]]);
    }

    public function getCertDetail($params)
    {
        $needProtectData = isset($params['need_protect_data']) ? $params['need_protect_data'] : 1;
        if (!isset($params['signature_id']) || empty($params['signature_id']) && $needProtectData) {
            return ['code' => ['get_signatures_failed', 'integrationCenter']];
        }
        // 获取选择的契约锁服务id
        $certificate = [];
        if (isset($params['bizId']) && !empty($params['bizId'])) {
            $settingId = $params['settingId'] ?? 1;;
            $server = app($this->signaturePlugInQiyuesuoSettingRepository)->getDetail($settingId);
            if (!$server || !isset($server['server_id'])) {
                return [
                    'code' => ['not_choose_qys_server', 'integrationCenter']
                ];
            }
            $serverId = $server['server_id'] ?? 0;
            $data = app($this->qiyuesuoService)->getCertDetail(['bizId' => $params['bizId']], $serverId);
            if (isset($data['qys_code']) && $data['qys_code'] == 0) {
                $certificate = $data['result'] ?? [];
                if ($needProtectData) {
                    $verifyProtectData = $this->verifyProtectData($params['signature_id'], json_decode($params['formData'], 1), $params['form_id']);
                } else {
                    $verifyProtectData = [];
                }
            } else {
                return $data;
            }
        } else {
            if ($needProtectData) {
                $verifyProtectData = $this->verifyProtectData($params['signature_id'], json_decode($params['formData'], 1), $params['form_id']);
            } else {
                $verifyProtectData = [];
            }
        }
        if ($needProtectData && $verifyProtectData && isset($verifyProtectData['code'])) {
            return $verifyProtectData;
        }
        return compact('certificate', 'verifyProtectData');
    }
    
	// 数据保护校验
	public function verifyProtectData($signatureId, $formData, $formId)
	{
        $signature = app($this->htmlSignatureRepository)->getOneFieldInfo(['signatureid' => [$signatureId]]);
        if (!$signature) {
            return ['code' => ['get_signatures_failed', 'integrationCenter']];
        }
        $protectData = $signature->protect_data ? json_decode($signature->protect_data, 1) : [];
        $protectDataArray = array_column($protectData, NULL, 'control_id');
        if (!$formData) {
            $formData = [];
        }
        $updateData = [];
        foreach ($protectDataArray as $key => $value) {
            if ($value['control_type'] == 'detail-layout') {
                $detailHead = $this->getDetailHead($formId, $key);
                $detailUpdate = [];
                $detail = [];
                $change = false;
                $detailArray = isset($formData[$value['control_id']]) ? $formData[$value['control_id']] : [];
                $formatDetail = [];
                for ($k = 0; $k < count($detailArray); $k++) {
                    foreach ($detailArray[$k] as $j => $detailvalue) {
                        if (isset($detailArray[$k][$j]) && (is_string($detailArray[$k][$j]) && $detailArray[$k][$j] != '' || (is_array($detailArray[$k][$j]) && count($detailArray[$k][$j]) > 0)) || is_numeric($detailArray[$k][$j])) {
                            $formatDetail[] = $detailArray[$k];
                            break;
                        }
                    }
                }
                $valueCount = count($value['value']);
                $formDataCount = count($formatDetail);
                if ($valueCount == $formDataCount) {
                    if ($valueCount == 0) {
                        continue;
                    }
                    for ($i = 0; $i < count($value['value']); $i++) {
                        $detailUpdate[$i]['change_type'] = 'update';
                        foreach($value['value'][$i] as $valKey => $valValue) {
                            if (strpos($valKey, '_TEXT') !== false) {
                                continue;
                            }
                            $showValue = $value['value'][$i][$valKey. '_TEXT'] ?? $valValue;
                            $showValue = $this->changeValue($showValue);
                            $detail[$i][$valKey] = $showValue;
                            $detailUpdate[$i][$valKey]['old'] = $showValue ?? '';
                            $detailUpdate[$i][$valKey]['new'] = '';
                            $formDataShowValue = isset($formData[$value['control_id']]) && isset($formData[$value['control_id']][$i]) ? ($formData[$value['control_id']][$i][$valKey . '_TEXT'] ?? ($formData[$value['control_id']][$i][$valKey] ?? '')) : '';
                            $formDataShowValue = $this->changeValue($formDataShowValue);
                            if ($showValue != $formDataShowValue) {
                                $change = true;
                                $detailUpdate[$i][$valKey]['new'] = $formDataShowValue;
                            }
                        }
                    }
                } else {
                    $change = true;
                    if ($valueCount > $formDataCount) {
                        // 修改的数据
                        for ($i = 0; $i < $formDataCount; $i++) {
                            $detailUpdate[$i]['change_type'] = '';
                            foreach($value['value'][$i] as $valKey => $valValue) {
                                $showValue = $value['value'][$i][$valKey. '_TEXT'] ?? ($valValue ?? '');
                                $showValue = $this->changeValue($showValue);
                                $detail[$i][$valKey] = $showValue;
                                if (strpos($valKey, '_TEXT') !== false) {
                                    continue;
                                }
                                $detailUpdate[$i][$valKey]['old'] = $showValue ?? '';
                                $detailUpdate[$i][$valKey]['new'] = '';
                                $formDataShowValue = isset($formData[$value['control_id']]) && isset($formData[$value['control_id']][$i]) ? ($formData[$value['control_id']][$i][$valKey . '_TEXT'] ?? ($formData[$value['control_id']][$i][$valKey] ?? '')) : '';
                                $formDataShowValue = $this->changeValue($formDataShowValue);
                                if ($showValue != $formDataShowValue) {
                                    $detailUpdate[$i]['change_type'] = 'update';
                                    $detailUpdate[$i][$valKey]['new'] = $formDataShowValue;
                                }
                            }
                        }
                        // 删除的数据
                        for ($i = $formDataCount; $i < $valueCount; $i++) {
                            $detailUpdate[$i]['change_type'] = 'delete';
                            foreach($value['value'][$i] as $valKey => $valValue) {
                                $showValue = $value['value'][$i][$valKey. '_TEXT'] ?? ($valValue ?? '');
                                $detail[$i][$valKey] = $showValue;
                                if (strpos($valKey, '_TEXT') !== false) {
                                    continue;
                                }
                                $detailUpdate[$i][$valKey]['old'] = $showValue ?? '';
                                $detailUpdate[$i][$valKey]['new'] = '';
                            }
                        }
                    } else {
                        // 修改的数据
                        for ($i = 0; $i < $valueCount; $i++) {
                            $detailUpdate[$i]['change_type'] = '';
                            foreach($value['value'][$i] as $valKey => $valValue) {
                                $showValue = $value['value'][$i][$valKey. '_TEXT'] ?? ($valValue ?? '');
                                $showValue = $this->changeValue($showValue);
                                $detail[$i][$valKey] = $showValue ?? '';
                                if (strpos($valKey, '_TEXT') !== false) {
                                    continue;
                                }
                                $detailUpdate[$i][$valKey]['old'] = $showValue ?? '';
                                $detailUpdate[$i][$valKey]['new'] = '';
                                $formDataShowValue = isset($formData[$value['control_id']][$i]) ? ($formData[$value['control_id']][$i][$valKey . '_TEXT'] ?? ($formData[$value['control_id']][$i][$valKey] ?? '')) : '';
                                $formDataShowValue = $this->changeValue($formDataShowValue);
                                if ($showValue != $formDataShowValue) {
                                    $detailUpdate[$i]['change_type'] = 'update';
                                    $detailUpdate[$i][$valKey]['new'] =  $formDataShowValue;
                                }
                            }
                        }
                        // 新增的数据
                        for ($i = $valueCount; $i < $formDataCount; $i++) {
                            $detailUpdate[$i]['change_type'] = 'create';
                            foreach($formData[$value['control_id']][$i] as $valKey => $valValue) {
                                if (strpos($valKey, '_TEXT') !== false) {
                                    continue;
                                }
                                $detailUpdate[$i][$valKey]['old'] = '';
                                $detailUpdate[$i][$valKey]['new'] = isset($formData[$value['control_id']]) && isset($formData[$value['control_id']][$i]) ? ($formData[$value['control_id']][$i][$valKey . '_TEXT'] ?? ($formData[$value['control_id']][$i][$valKey] ?? '')) : '';
                            }
                        }
                    }
                }
                
                if ($change) {
                    $updateData[] = [
                        'control_id' => $key,
                        'control_type' => $value['control_type'],
                        'control_title' => $value['control_title'],
                        'old' => $value['value'],
                        'new' => $formData[$key],
                        'detail' => $detailUpdate,
                        'detail_head' => $detailHead
                    ];
                } else {
                    $protectDataArray[$key]['detail_head'] = $detailHead;
                    $protectDataArray[$key]['detail'] = $detail;
                }
            } else {
                $showValue = is_array($value['value']) ? implode(',', $value['value']) : $value['value'];
                $formDataShowValue = $formData[$key . '_TEXT'] ?? ($formData[$key] ?? '');
                $formDataShowValue = is_array($formDataShowValue) ? implode(',', $formDataShowValue) : $formDataShowValue;
                if (($value['control_type'] != 'editor' && $formDataShowValue != $showValue) || ($value['control_type'] == 'editor' && $this->changeEditorValue($formDataShowValue) != $this->changeEditorValue($showValue))) {
                    $updateData[] = [
                        'control_id' => $key,
                        'control_type' => $value['control_type'],
                        'control_title' => $value['control_title'],
                        'old' => $showValue,
                        'new' => $formData[$key . '_TEXT'] ?? $formDataShowValue
                    ];
                }
                if (isset($formData[$key . '_TEXT'])) {
                    $protectDataArray[$key]['value'] = $formData[$key . '_TEXT'] ?? $formDataShowValue;
                }
            }            
        }
        
        return ['protect_data' => array_values($protectDataArray), 'update_data' => $updateData];
    }

    private function changeValue($value)
    {
        if (empty($value)) {
            return '';
        }
        if (is_array($value)) {
            // 20210714 二级明细的问题 先不处理二级明细
            if (count($value) == count($value, 1)) {
                return implode(',',$value);
            } else {
                //todo二级明细数据的获取校验
                return '';
            }
        } else {
            return $value;
        }
        return is_array($value) ? implode(',',$value) : $value;
    }
    /**
     * 初始化时验证数据保护的字段是被篡改
     *
     * @param [type] $signatures
     * @param [type] $formId
     * @param [type] $runId
     * @param array $formData
     * @param array $token
     *
     * @return void
     * @author yml
     */
    public function checkVerify($signatures, $formId, $runId, $formData = [], $token = '', $type ='electronic-signature')
    {
        if (!$type) {
            $type = 'electronic-signature';
        }
        // 前端未传值，后端解析数据
        if (!$formData) {
            //主流程结构
            $formControlStructure = app($this->flowFormControlStructureRepository)->getFlowFormControlStructure(['search' => ["form_id" => [$formId]]]);
            //主流程数据
            $formData = [];
            if (count($formControlStructure)) {
                $formData = app($this->flowService)->getParseFormDataFlowRunDatabaseData($formControlStructure, $runId, $formId);
            }
            foreach($signatures as $signatureKey => $signature) {
                $protectData = $signature['protect_data'] ? json_decode($signature['protect_data'], 1) : [];
                $protectDataArray = array_column($protectData, NULL, 'control_id');
                $signatures[$signatureKey]['qysEditChange'] = false;
                foreach ($protectDataArray as $key => $value) {
                    if ($value['control_type'] == 'detail-layout') {
                        $change = false;
                        $detailArray = $value['value'];
                        for ($k = 0; $k < count($detailArray); $k++) {
                            foreach ($detailArray[$k] as $j => $detailvalue) {
                                if (isset($detailArray[$k][$j]) && (is_string($detailArray[$k][$j]) && $detailArray[$k][$j] != '' || (is_array($detailArray[$k][$j]) && count($detailArray[$k][$j]) > 0)) || is_numeric($detailArray[$k][$j])) {
                                    $detail[] = $detailArray[$k];
                                    break;
                                }
                            }
                        }
                        $valueCount = count($value['value']);
                        $formDataValue = $formData[$value['control_id']] ?? [];
                        $formDataCount = isset($formDataValue['id']) ? count($formDataValue['id']) : 0;
                        if ($valueCount == $formDataCount) {
                            for ($i = 0; $i < count($value['value']); $i++) {
                                foreach($value['value'][$i] as $valKey => $valValue) {
                                    if (strpos($valKey, '_TEXT') !== false) {
                                        continue;
                                    }
                                    $showValue = $value['value'][$i][$valKey. '_TEXT'] ?? $valValue;
                                    $showValue = $this->changeValue($showValue);
                                    $formDataShowValue = isset($formDataValue[$valKey]) && isset($formDataValue[$valKey][$i]) ? $formDataValue[$valKey][$i] : '';
                                    if (isset($formDataValue[$valKey . '_TEXT']) && isset($formDataValue[$valKey . '_TEXT'][$i])) {
                                        $formDataShowValue = $formDataValue[$valKey . '_TEXT'][$i];
                                    }
                                    $formDataShowValue = $this->changeValue($formDataShowValue);
                                    $showValue = $this->changeEditorValue($showValue);
                                    $formDataShowValue = $this->changeEditorValue($formDataShowValue);
                                    if ($showValue != $formDataShowValue) {
                                        $change = true;
                                    }
                                }
                            }
                        } else {
                            $change = true;
                        }
                        if ($change) {
                            $signatures[$signatureKey]['qysEditChange'] = true;
                        }
                    } else {
                        $showValue = $this->changeValue($value['value']);
                        $showFormData = $this->changeValue($formData[$key. '_TEXT'] ?? ($formData[$key]));
                        if ($value['control_type'] == 'editor') {
                            $showValue = $this->changeEditorValue($showValue);
                            $showFormData = $this->changeEditorValue($showFormData);
                        }
                        if ($showValue != $showFormData) {
                            $signatures[$signatureKey]['qysEditChange'] = true;
                        }
                    }            
                }
            }
        } else {
            // formData前端传值
            foreach($signatures as $signatureKey => $signature) {
                $protectData = $signature['protect_data'] ? json_decode($signature['protect_data'], 1) : [];
                $protectDataArray = array_column($protectData, NULL, 'control_id');
                $signatures[$signatureKey]['qysEditChange'] = false;
                foreach ($protectDataArray as $key => $value) {
                    if ($value['control_type'] == 'detail-layout') {
                        $change = false;
                        $valueCount = count($value['value']);
                        $detailArray = $formData[$value['control_id']] ?? [];
                        $detail = [];
                        for ($k = 0; $k < count($detailArray); $k++) {
                            foreach ($detailArray[$k] as $j => $detailvalue) {
                                if (isset($detailArray[$k][$j]) && (is_string($detailArray[$k][$j]) && $detailArray[$k][$j] != '' || (is_array($detailArray[$k][$j]) && count($detailArray[$k][$j]) > 0)) || is_numeric($detailArray[$k][$j])) {
                                    $detail[] = $detailArray[$k];
                                    break;
                                }
                            }
                        }
                        $formData[$value['control_id']] = $detail;
                        $formDataCount = isset($formData[$value['control_id']]) ? count($formData[$value['control_id']]) : 0;
                        if ($valueCount == $formDataCount) {
                            for ($i = 0; $i < count($value['value']); $i++) {
                                foreach($value['value'][$i] as $valKey => $valValue) {
                                    if (strpos($valKey, '_TEXT') !== false) {
                                        continue;
                                    }
                                    $showValue = $value['value'][$i][$valKey. '_TEXT'] ?? $valValue;
                                    $showValue = $this->changeValue($showValue);
                                    $showValue = $this->changeEditorValue($showValue);
                                    $formDataShowValue = isset($formData[$value['control_id']]) && isset($formData[$value['control_id']][$i]) && isset($formData[$value['control_id']][$i][$valKey]) ? $formData[$value['control_id']][$i][$valKey] : '';
                                    if (isset($formData[$value['control_id']]) && $formData[$value['control_id']][$i] && isset($formData[$value['control_id']][$i][$valKey . '_TEXT'])) {
                                        $formDataShowValue = $formData[$value['control_id']][$i][$valKey . '_TEXT'];
                                    }
                                    $formDataShowValue = $this->changeValue($formDataShowValue);
                                    $formDataShowValue = $this->changeEditorValue($formDataShowValue);
                                    if ($showValue != $formDataShowValue) {
                                        $change = true;
                                    }
                                }
                            }
                        } else {
                            $change = true;
                        }
                        if ($change) {
                            $signatures[$signatureKey]['qysEditChange'] = true;
                        }
                    } else {
                        $showValue = $this->changeValue($value['value']);
                        $showFormData = $this->changeValue($formData[$key.'_TEXT'] ?? ($formData[$key] ?? ''));
                        if ($value['control_type'] == 'editor') {
                            $showValue = $this->changeEditorValue($showValue);
                            $showFormData = $this->changeEditorValue($showFormData);
                        }
                        if ($showValue != $showFormData) {
                            $signatures[$signatureKey]['qysEditChange'] = true;
                        }
                    }            
                }
            }
        }
        return $signatures;
    }

    private function changeEditorValue($value)
    {
        if ($value && preg_match_all('/attachment_id=(.*)?" alt/u', $value, $matches) !== false) {
            if ($matches && isset($matches[1])) {
                foreach($matches[1] as $match) {
                    $value = str_replace($match, '', $value);
                }
            }
        }
        $matches = [];
        return $value;
    }

    /**
     * 
     *
     * @param [type] $formId
     * @param [type] $controlId
     *
     * @return void
     * @author yml
     */
    public function getDetailHead($formId, $controlId)
    {
        $formControlData = app($this->flowFormService)->getParseForm($formId);
        $formControlData = array_column($formControlData, NULL, 'control_id');
        $head = [];
        foreach ($formControlData as $key =>$value) {
            if ($value['control_parent_id'] == $controlId && !in_array($value['control_type'], ['electronic-signature', 'countersign'])) {
                $controlTypeName = app($this->flowFormService)->getControlDataFormat($value['control_type']);
                $head[] = [
                    'control_id' => $value['control_id'],
                    'control_type' => $value['control_type'],
                    'control_type_name' => $controlTypeName['title'] ?? '',
                    'control_title' => $value['control_attribute']['title'] ?? $value['control_title'],
                ];
            }
        }
        return $head;
    }
    /**
     * 获取当前流程表单控件的列表
     *
     * @param [type] $param
     *
     * @return void
     * @author yml
     */
    public function getFlowControlFilterInfo($param)
    {
        $type = $param['type'] ?? '';
        if ($type == 'all') {
            $commonControls = app($this->flowService)->getFlowNodeFieldControlFilterInfo(array_merge($param, ['type' => 'common']));
            $moreControls = app($this->flowService)->getFlowNodeFieldControlFilterInfo(array_merge($param, ['type' => 'more']));
            $controls = array_merge($commonControls, $moreControls);
        } else {
            $controls = app($this->flowService)->getFlowNodeFieldControlFilterInfo($param);
        }
        // 保护字段需要排除的控件类型
        foreach ($controls as $controlKey => $control) {
            if (in_array($control['fieldKey'], ['electronic-signature', 'countersign', 'signature-picture'])) {
                unset($controls[$controlKey]);
            } else {
                if ($control['type'] != 'common') {
                    $controls[$controlKey]['title'] = preg_replace('/\(.*\)/', '', $control['title']);
                }
            }
        }
        return array_values($controls);
    }
    /**
     * 根据最新的会签信息，解析处理删掉页面上删掉的印章记录
     *
     * @param [type] $countsigns
     * @param [type] $runId
     *
     * @return void
     * @author yml
     */
    public function handleFlowQiyuesuoSignature($runId, $countsigns, $processId, $userId)
    {
        $signatures = app($this->htmlSignatureRepository)->getFieldInfo(['documentid' => $runId, 'process_id' => $processId, 'user_id' => $userId]);
        if ($signatures) {
            // 解析所有的signature_id
            $signatureIds = [];
            foreach($countsigns as $countsign) {
                if (preg_match_all('/id="(.*)" class="electronic_signature_editor mceNonEditable"/U', $countsign['countersign_content'], $matches) !== false) {
                    if ($matches && $matches[1]) {
                        if (is_array($matches[1])) {
                            $signatureIds = array_merge($signatureIds, $matches[1]);
                        } else {
                            $signatureIds[] =  $matches[1];
                        }
                    }
                }
            }
            $dataSignatureIds = array_column($signatures, 'signatureid');
            if ($signatureIds) {
                $diff = array_diff($dataSignatureIds, $signatureIds);
            } else {
                $diff = $dataSignatureIds;
            }
            if ($diff) {
                app($this->htmlSignatureRepository)->deleteByWhere(['signatureid' => [$diff, 'in']]);
            }
        }
        return true;
    }

    public function countsignCheckVerify($params, $token)
    {
        $signatureIds = $params['signatureIds'] ?? [];
        $formId = $params['formId'] ?? '';
        $runId = $params['runId'] ?? '';
        $formData = $params['formData'] ?? [];
        $signatures = app($this->htmlSignatureRepository)->getFieldInfo([], null, ['signatureid' => [$signatureIds, 'in']]);
        $type = $params['type'] ?? 'electronic-signature';
        return $this->checkVerify($signatures, $formId, $runId, $formData, $token, $type);
    }

    private function getCompanyName($flowId, $formData)
    {
        if (!$flowId || !$formData) 
            return '';
        $field = app($this->qiyuesuoSignatureFlowConfigRepository)->getFieldValue('company_field', ['flow_id' => $flowId]);
        $formData = json_decode($formData, 1);
        return isset($formData[$field . '_TEXT']) ? $formData[$field . '_TEXT'] : ($formData[$field] ?? '');
    }
}
