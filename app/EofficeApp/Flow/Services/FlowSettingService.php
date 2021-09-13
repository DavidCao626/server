<?php

namespace App\EofficeApp\Flow\Services;

/**
 * Description of FlowSettingService
 *
 * @author lizhijun
 */

use App\EofficeApp\Flow\Services\FlowBaseService;
use Cache;
use DB;

class FlowSettingService extends FlowBaseService
{
    public function __construct()
    {
        parent::__construct();
    }
    /**
     * 获取所有的紧急程度选项
     *
     * @return array
     */
    public function getInstancyOptions($params, $loginUserId = '')
    {
        //如果是设置选项的地方调用则需要调用翻译服务。
        $langService = (isset($params['setting']) && $params['setting']) ? app($this->langService) : null;
        $search = (isset($params['search']) && $params['search']) ? $params['search'] : null;
        // 缓存紧急程度，注意多语言
        if (!$langService && empty($search)) {
            $langType = app($this->langService)->getUserLocale($loginUserId);
            $cacheInstancyOptionsKey = 'flow_instancys_options_' . $langType;
            if (Cache::has($cacheInstancyOptionsKey)) {
                return Cache::get($cacheInstancyOptionsKey);
            }
        }
        $instancyOptionsList = app($this->flowInstancysRepository)->getAllOptions($this->handleInstancyOptionsSearch($this->parseParams($params)))
            ->map(function ($option) use ($langService) {
                $option->instancy_name = mulit_trans_dynamic('flow_instancys.instancy_name.instancy_id_' . $option->instancy_id);
                if ($langService) {
                    $option->instancy_name_lang = $langService->transEffectLangs('flow_instancys.instancy_name.instancy_id_' . $option->instancy_id, true);
                }
                return $option;
            });
        if (!$langService && empty($search)) {
            Cache::forever($cacheInstancyOptionsKey, $instancyOptionsList);
        }
        return $instancyOptionsList;
    }
    /**
     * 获取流程紧急程度选项ID和名称对应关系
     *
     * @return array
     */
    public function getInstancyIdNameRelation()
    {
        $instancyOptionsList = app($this->flowInstancysRepository)
            ->getAllOptions($this->handleInstancyOptionsSearch([]))
            ->map(function ($option) {
                $option->instancy_name = mulit_trans_dynamic('flow_instancys.instancy_name.instancy_id_' . $option->instancy_id);
                return $option;
            });
        if (empty($instancyOptionsList)) {
            return [
                [
                    'instancy_id' => 'instancy_0',
                    'instancy_name' => trans('flow.0x030026')
                ],
                [
                    'instancy_id' => 'instancy_1',
                    'instancy_name' => trans('flow.0x030027')
                ],
                [
                    'instancy_id' => 'instancy_2',
                    'instancy_name' => trans('flow.0x030028')
                ],
            ];
        }
        $instancyIdNameRelation = [];
        foreach ($instancyOptionsList as $key => $value) {
            if (isset($value['instancy_id']) && isset($value['instancy_name'])) {
                $instancyIdNameRelation[] = [
                    'instancy_id' => 'instancy_' . $value['instancy_id'],
                    'instancy_name' => $value['instancy_name']
                ];
            }
        }
        return $instancyIdNameRelation;
    }
    /**
     * 获取紧急程度选项名称
     *
     * @param int $instancyId
     *
     * @return string
     */
    public function getInstancyName($instancyId)
    {
        $options = $this->getInstancyMapOptions();

        return $options[$instancyId] ?? '';
    }
    /**
     * 获取所有的紧急程度选项的id => name 映射
     *
     * @return array
     */
    public function getInstancyMapOptions()
    {
        if (Cache::has('flow_instancys_maps')) {
            return Cache::get('flow_instancys_maps');
        }

        $options = $this->getDynamicInstancyMapOptions();

        Cache::forever('flow_instancys_maps', $options);

        return $options;
    }
    /**
     * 动态获取所有的紧急程度选项的id => name 映射
     *
     * @return array
     */
    public function getDynamicInstancyMapOptions()
    {
        return app($this->flowInstancysRepository)->getAllOptions([])->mapWithKeys(function ($option) {
            $instancyId = $option->instancy_id;

            $instancyName = mulit_trans_dynamic('flow_instancys.instancy_name.instancy_id_' . $instancyId);

            return [$instancyId => $instancyName];
        });
    }
    /**
     * 刷新紧急程度选项缓存
     *
     * @return boolean
     */
    private function instancyMapOptionsCacheFlush()
    {
        $langPackagesList = app($this->langService)->getEffectLangPackages(['response' => 'data']);
        if (!empty($langPackagesList['list'])) {
            foreach ($langPackagesList['list'] as $key => $value) {
                if (!empty($value->lang_code)) {
                    $cacheInstancyOptionsKey = 'flow_instancys_options_' . $value->lang_code;
                    Cache::forget($cacheInstancyOptionsKey);
                }
            }
        }
        $options = $this->getDynamicInstancyMapOptions();
        return Cache::forever('flow_instancys_maps', $options);
    }
    /**
     * 获取默认选中的紧急程度选项ID
     *
     * @staticvar int $instancyId
     *
     * @return int
     */
    public function getDefaultSelectedInstancyId()
    {
        static $instancyId = null;

        if ($instancyId !== null) {
            return $instancyId;
        }

        if (!$instancy = $this->getDefaultSelectedInstancy()) {
            return $instancyId = 0;
        }

        return $instancyId = $instancy->instancy_id;
    }
    /**
     * 获取默认的紧急程度选项
     *
     * @return object
     */
    public function getDefaultSelectedInstancy()
    {
        return app($this->flowInstancysRepository)->getDefaultSelectOption();
    }
    /**
     * 处理获取紧急程度选项的查询条件
     *
     * @param array $params
     * @param string $userId
     *
     * @return array
     */
    private function handleInstancyOptionsSearch($params)
    {
        $search = [];

        if (isset($params['search'])) {
            $search = $params['search'];

            if (isset($search['instancy_type'])) {
                $search['instancy_id'] = $search['instancy_type'];

                unset($search['instancy_type']);
            }

            if (isset($search['instancy_name'])) {
                if (isset($search['instancy_name'][0]) && $search['instancy_name'][0]) {
                    $instancyIds = app($this->langService)
                        ->getEntityIdsLikeColumnName('flow_instancys', 'instancy_name', 'instancy_name', $search['instancy_name'][0], function ($item) {
                            list($null, $id) = explode('instancy_id_', $item);

                            return $id;
                        });

                    $search['instancy_id'] = [$instancyIds, 'in'];
                }
                unset($search['instancy_name']);
            }
        }

        return $search;
    }
    /**
     * 保存紧急程度选项
     *
     * @param type $data
     * @param type $own
     *
     * @return boolean
     */
    public function saveInstancyOptions($data, $own)
    {
        if (empty($data)) {
            return ['code' => ['0x030148', 'flow']];
        }
        /**
        | -------------------------------------------------------------------
        | 处理重组紧急程度选项数组
        | -------------------------------------------------------------------
        | 将需要保存的数组，拆分为添加数组和更新数组
         */
        if (!$data = $this->handleInstancysSaveData($data, $own['user_id'])) {
            return ['code' => ['0x030147', 'flow']];
        }
        list($insert, $update) = $data;
        /**
        | -------------------------------------------------------------------
        | 创建引用类对象
        | -------------------------------------------------------------------
        | 创建紧急程度选项资源库对象，创建多语言服务对象
         */
        $flowInstancysRepository = app($this->flowInstancysRepository);
        $langService = app($this->langService);
        $locale = $langService->getUserLocale($own['user_id']);
        /**
        | -------------------------------------------------------------------
        | 新增紧急程度选项
        | -------------------------------------------------------------------
         */
        array_map(function ($data) use ($flowInstancysRepository, $langService, $locale) {
            //获取当前选项的ID，获取数据库最后一个id+1
            $lastInstancy = $flowInstancysRepository->getLastId();
            if ($lastInstancy) {
                $instancyId = $lastInstancy->instancy_id + 1;
            } else {
                $instancyId = 1;
            }

            //将原名称存在临时变量中
            $instancyName = $data['instancy_name'];
            //重新拼装选项数组
            $data['instancy_id'] = $instancyId;
            $data['instancy_name'] = 'instancy_id_' . $instancyId;
            //将紧急程度选项插入数据库
            $flowInstancysRepository->insertData($data);
            //添加名称多语言
            $this->addInstancyNameLang($langService, $data, $instancyId, $instancyName, $locale);
        }, $insert);
        /**
        | -------------------------------------------------------------------
        | 更新紧急程度选项
        | -------------------------------------------------------------------
         */
        array_walk($update, function ($item, $instancyId) use ($flowInstancysRepository, $langService, $locale) {
            //将原名称存在临时变量中
            $instancyName = $item['instancy_name'];
            //重新拼装选项名称
            $item['instancy_name'] = 'instancy_id_' . $instancyId;
            //将紧急程度选项更新到数据库
            if ($flowInstancysRepository->optionExists($instancyId)) {
                $flowInstancysRepository->updateData($item, ['instancy_id' => $instancyId]);
            } else {
                $flowInstancysRepository->insertData($item);
            }
            //添加名称多语言
            $this->addInstancyNameLang($langService, $item, $instancyId, $instancyName, $locale);
        });
        //刷新缓存
        $this->instancyMapOptionsCacheFlush();

        return true;
    }
    /**
     * 删除紧急程度选项
     *
     * @param int $instancyId
     *
     * @return boolean
     */
    public function deleteInstancyOption($instancyId)
    {
        if (app($this->flowRunRepository)->instancyIsEffect($instancyId)) {
            return ['code' => ['0x030145', 'flow']];
        }

        $result = app($this->flowInstancysRepository)->deleteInstancy($instancyId);

        //刷新缓存
        if ($result) {
            remove_dynamic_langs('flow_instancys.instancy_name.instancy_id_' . $instancyId);
            $this->instancyMapOptionsCacheFlush();
        }

        return $result;
    }
    /**
     * 处理重组紧急程度选项数组
     *
     * @param type $data
     * @param type $userId
     *
     * @return boolean | array
     */
    private function handleInstancysSaveData($data, $userId)
    {
        $update = $insert = [];

        foreach ($data as $key => $option) {
            if (!$option['instancy_name']) {
                return false;
            }

            $option['creator'] = $userId;
            $option['sort'] = $key + 1;

            if ($option['instancy_id'] !== '') {
                $update[$option['instancy_id']] = $option;
            } else {
                $insert[] = $option;
            }
        }

        return [$insert, $update];
    }
    /**
     * 添加选项名称多语言
     *
     * @param type $langService
     * @param type $data
     * @param type $instancyId
     * @param type $langValue
     *
     * @return boolean
     */
    private function addInstancyNameLang($langService, $data, $instancyId, $instancyName = '', $locale = 'zh-CN')
    {
        $langKey = 'instancy_id_' . $instancyId;

        if (isset($data['instancy_name_lang']) && !empty($data['instancy_name_lang'])) {
            foreach ($data['instancy_name_lang'] as $_locale => $langValue) {
                $langValue = $locale == $_locale ? $instancyName : $langValue;

                $langService->addDynamicLang(['table' => 'flow_instancys', 'column' => 'instancy_name', 'lang_key' => $langKey, 'lang_value' => $langValue], $_locale);
            }
        } else {
            $langService->addDynamicLang(['table' => 'flow_instancys', 'column' => 'instancy_name', 'lang_key' => $langKey, 'lang_value' => $instancyName]);
        }

        return true;
    }

    /**
     * 【流程设置】获取流程设置某个参数的值
     *
     * @method 缪晨晨
     *
     * @param  [string]        $paramKey [要查询的参数key]
     *
     * @return [object]                   [查询结果]
     */
    public function getFlowSettingsParamValueByParamKey($paramKey)
    {
        return app($this->flowSettingsRepository)->getFlowSettingsParamValueByParamKey($paramKey);
    }

    /**
     * 【流程设置】设置流程设置参数
     *
     * @method 缪晨晨
     *
     * @param  [string]        $data [要更新或插入的数据]
     *
     * @return [object]                   [查询结果]
     */
    public function setFlowSettingsParamValue($data)
    {
        $data = $this->parseParams($data);
        if (!empty($data)) {
            return app($this->flowSettingsRepository)->setFlowSettingsParamValueByWhere($data);
        } else {
            return false;
        }
    }

    /**
     * 全部流程定时触发及提醒设置格式化
     */
    public function getAllFlowScheduleReminds()
    {
        // 获取所有的流程定时触发设置
        $scheduleConfigs = app($this->flowScheduleRepository)->getAllFlowScheduleInfo([]);
        // $scheduleConfigs = DB::table('flow_schedule')->get();
        $returnConfig = [];
        if (!$scheduleConfigs) {
            return [];
        }
        // 多语言环境，获取admin的作为标准
        $locale = app($this->langService)->getUserLocale('admin');

        foreach ($scheduleConfigs as $key => $scheduleConfig) {
            if (is_object($scheduleConfig)) {
                $scheduleConfig = json_decode(json_encode($scheduleConfig), TRUE);
            }
            // 过滤已停用的流程
            if (!isset($scheduleConfig['flow_schedule_has_one_type']['is_using']) || !$scheduleConfig['flow_schedule_has_one_type']['is_using']) {
                continue;
            }
            $returnConfig[$key]['id'] = $scheduleConfig['id'];
            $returnConfig[$key]['type'] = $scheduleConfig['type']; // 定时任务触发时间类型
            $returnConfig[$key]['flow_id'] = $scheduleConfig['flow_id']; // 流程ID
            $returnConfig[$key]['flow_type'] = $scheduleConfig['flow_schedule_has_one_type']['flow_type'] ?? 0; // 固定流程或自由流程
            $returnConfig[$key]['flow_sort'] = $scheduleConfig['flow_schedule_has_one_type']['flow_sort'] ?? 0; // 流程类型id
            $returnConfig[$key]['attention_content'] = mulit_trans_dynamic('flow_schedule.attention_content.attention_content_' . $scheduleConfig['id'], [], $locale); // 提示文字多语言
            $returnConfig[$key]['month'] = $scheduleConfig['month'];
            $returnConfig[$key]['day'] = $scheduleConfig['day'];
            $returnConfig[$key]['week'] = $scheduleConfig['week'];
            $returnConfig[$key]['trigger_time'] = $scheduleConfig['trigger_time'];
        }
        return $returnConfig;
    }

    /**
     * 获取某个流程的定时触发配置
     *
     * @param [type] $flow_id
     * @return void
     */
    public function getFlowSchedules($flow_id)
    {
        $scheduleRes = app($this->flowScheduleRepository)->getFlowSchedulesByWhere(['flow_id' => $flow_id]);
        if ($scheduleRes) {
            foreach ($scheduleRes as $key => $val) {
                //获取多语言提示文字
                $scheduleRes[$key]['attention_content'] = trans_dynamic("flow_schedule.attention_content.attention_content_" . $val['id']);
                $scheduleRes[$key]['attention_content_lang'] = app($this->langService)->transEffectLangs("flow_schedule.attention_content.attention_content_" . $val['id']);
            }
        }
        return $scheduleRes;
    }

    /**
     * 编辑流程的定时触发配置
     *
     * @param [type] $param
     *
     * @since zyx 20200414
     * @return void
     */
    public function editFlowSchedules($param, $own = [])
    {
        $flowId = $param['flow_id'];
        $triggerSchedule = $param['trigger_schedule'];
        $scheduleConfigs = $param['schedule_configs'] ?? [];

        $historyInfos = [];
        // 获取原记录
        $oldSchedules = app($this->flowScheduleRepository)->getFieldInfo(['flow_id' => $flowId]);
        if ($oldSchedules) {
            foreach ($oldSchedules as $oldSchedule) {
                $oldSchedule['deleted_at'] = date('Y-m-d H:i:s');
                $oldSchedule['operator'] = $own['user_id'];
                $historyInfos[] = $oldSchedule;
            }
        }
        // 删除原记录
        $res = app($this->flowScheduleRepository)->deleteByWhere(['flow_id' => [$flowId]]);

        // 如果是取消定时触发
        if (!$triggerSchedule) {
            // 更新others表trigger_schedule
            app($this->flowOthersRepository)->updateData(['trigger_schedule' => 0], ['flow_id' => $flowId]);
            return true;
        }

        $newInfos = [];
        // 遍历并格式化定时触发参数
        foreach ($scheduleConfigs as $key => $v) {
            // 格式化定时触发参数
            $insertData = $newInfos[$key] = app($this->flowParseService)->parseScheduleConfig($v);
            // 兼容复制流程时的flow_id变化
            $insertData['flow_id'] = $flowId;
            // 插入数据
            $insertRes = app($this->flowScheduleRepository)->insertData($insertData);

            //添加多语言提示文字
            if (!empty($v['attention_content_lang'])) {
                //循环处理多语言提示文字
                foreach ($v['attention_content_lang'] as $k => $langVal) {
                    $langData = [
                        'table'      => 'flow_schedule',
                        'column'     => 'attention_content',
                        'lang_key'   => "attention_content_" . $insertRes->id,
                        'lang_value' => $langVal,
                    ];
                    app($this->langService)->addDynamicLang($langData, $k);
                }
            }

            $newInfos[$key]['id'] = $insertRes->id;
        }
        // 更新others表trigger_schedule
        app($this->flowOthersRepository)->updateData(['trigger_schedule' => 1], ['flow_id' => $flowId]);

        $paramLog = [
            'history_info' => ['schedule_trigger' => json_encode(array_values($historyInfos))],
            'new_info' => ['schedule_trigger' => json_encode(array_values($newInfos))],
        ];
        // 记录修改日志
        app($this->flowLogService)->logFlowDefinedModify($paramLog, 'flow_others&flow_id', $flowId, 'editOtherSchedule', $own, 'flow');

        return 1;
    }

    /**
     * 获取流程节点办理人，包括固定流程和自由流程
     *
     * 20200416，简化版本，首版只支持获取首节点办理人，需求来自流程定时触发任务
     * 20200804,兼容参数较少的请求，支持获取指定节点的办理人员
     *
     * @param [type] $param
     *
     * @author zyx
     */
    public function getBothFreeAndFixedFlowHandlers($param)
    {
        $flowId = $param['flow_id'];

        $res['scope']['user_id'] = [];

        // 区分流程是固定流程还是自由流程,为其他地方调用做兼容
        if (isset($param['flow_type'])) {
            $flowType = $param['flow_type'];
        } else {
            $flowType = app($this->flowTypeRepository)->getFieldValue('flow_type', ['flow_id' => $flowId]);
        }

        // 固定流程获取首节点node_id
        if (isset($param['node_id'])) {
            $nodeId = $param['node_id'];
        } else {
            $nodeId = app($this->flowProcessRepository)->getFieldValue('node_id', ['flow_id' => $flowId, 'head_node_toggle' => 1]);
        }

        $shortScopeName = ($flowType == 1) ? 'process_' : 'create_'; // 全部范围时
        $longScopeName = ($flowType == 1) ? 'flow_process_has_many_' : 'flow_type_has_many_create_'; // 指定范围时

        // 先取节点信息
        if ($flowType == 1) { // 固定流程
            $flowInfo = app($this->flowProcessRepository)->getNodeInfoSimple($nodeId);
        } else { // 自由流程
            $flowInfo = app($this->flowService)->getFlowDefineInfoService(['is_search' => true], $flowId);
        }

        // 所有人员
        if (in_array('ALL', [$flowInfo[$shortScopeName . 'user'], $flowInfo[$shortScopeName . 'dept'], $flowInfo[$shortScopeName . 'role']])) {
            $res['scope']['user_id'] = 'ALL';
            return $res;
        }

        // 指定范围，将办理人员范围转成userID
        // 查询条件
        $getUserParam = [
            "fields" => ["user_id"],
            "page" => "0",
            "returntype" => "array",
        ];
        // 指定范围id
        $processUserId = array_column($flowInfo[$longScopeName . 'user'], "user_id");
        $processRoleId = array_column($flowInfo[$longScopeName . 'role'], "role_id");
        $processDeptId = array_column($flowInfo[$longScopeName . 'dept'], "dept_id");
        $getUserParam["search"] = [
            "user_id" => $processUserId,
            "role_id" => $processRoleId,
            "dept_id" => $processDeptId,
        ];
        // 获取user_id
        $userInfo = app($this->userRepository)->getConformScopeUserList($getUserParam);
        if ($userInfo) {
            $res['scope']['user_id'] = array_column($userInfo, "user_id");
        }
        return $res;
    }
     /**
     * 编辑是否开启调试模式
     *
     * @param [type] $param
     *
     * @since wz
     * @return void
     */
    public function editFlowDefineDebug($param, $own = [])
    {
        $flowId = $param['flow_id'];
        $res = app($this->flowTypeRepository)->updateData(['open_debug' => $param['open_debug']], ['flow_id' => $flowId]);
        if ($res) {
            // 调用日志函数
            $logParam = [];
            // 历史数据
            $logParam["new_info"] = ['open_debug' =>$param['open_debug'] ];
            $history_data = $param['open_debug'] == 1 ? 0 : 1;
            $logParam["history_info"] = [ 'open_debug' =>$history_data ];
            app($this->flowLogService)->logFlowDefinedModify($logParam, "flow_type&flow_id", $flowId, "basicInfo", $own, '');
            return true;
        } else {
            return false;
        }
    }

    /**
     * 【定义流程】 获取某条流程的基本定义流程信息
     *
     * @author miaochenchen
     *
     * @return [type]          [description]
     */
    public function getFlowDefineBasicInfo($flowId, $params)
    {
        $params = $this->parseParams($params);
        $params['search']['flow_id'] = [$flowId];
        return app($this->flowTypeRepository)->getFlowTypeData($params);
    }

    /**
     * 通过节点ID获取节点办理人范围
     */
    public function getNodeTransactUserScope($params)
    {
        $nodeId = $params['node_id'] ?? 0;
        if ($targetNodeInfo = app($this->flowProcessRepository)->getFlowNodeUserDetail($nodeId)) {
            // 智能获取办理人规则
            $processAutoGetUser = $targetNodeInfo->process_auto_get_user;
            if ($processAutoGetUser) {
                $userInfo = $this->getAutoGetUser($params);
                return $userInfo;
            } else {
                if ($targetNodeInfo->process_user == "ALL" || $targetNodeInfo->process_role == "ALL" || $targetNodeInfo->process_dept == "ALL") {
                    return "ALL";
                } else {
                    // 获取符合范围的人员
                    $getUserParam = [
                        "fields" => ["user_id"],
                        "page" => "0",
                        "returntype" => "object",
                    ];
                    $processUserId = $targetNodeInfo->flowProcessHasManyUser->pluck("user_id");
                    $processRoleId = $targetNodeInfo->flowProcessHasManyRole->pluck("role_id");
                    $processDeptId = $targetNodeInfo->flowProcessHasManyDept->pluck("dept_id");
                    $getUserParam["search"] = [
                        "user_id" => $processUserId,
                        "role_id" => $processRoleId,
                        "dept_id" => $processDeptId,
                    ];
                    $userInfo = app($this->userRepository)->getConformScopeUserList($getUserParam)->pluck('user_id')->toArray();
                    return $userInfo;
                }
            }
        }

    }

    /**
     * 通过节点ID获取节点办理人范围（智能获取部分）
     */
    public function getAutoGetUser($data)
    {
        $runId = $data["run_id"];
        $nodeId = $data["node_id"];
        $flowId = $data["flow_id"];
        $formId = $data["form_id"];
        if (empty($nodeId)) {
            return [];
        }
        if (empty($flowId)) {
            return [];
        }
        $autoGetUserInfo = [];
        if ($currentNodeInfo = app($this->flowProcessRepository)->getDetail($nodeId, false, ['process_auto_get_user', 'process_auto_get_copy_user', 'get_agency', 'process_name'])->toArray()) {
            $processAutoGetUser = $currentNodeInfo['process_auto_get_user'] ?? '';
            if (empty($processAutoGetUser)) {
                return [];
            }
            $processAutoGetUser = explode("|", $processAutoGetUser);
            switch ($processAutoGetUser[0]) {
                case '0': //关联人 流程创建人
                    break;
                case '1': //关联人 某个节点主办人
                    break;
                case '2': //关联人 人力资源
                    break;
                case '3': //关联人 流程表单中的某个字段，前端不能设置[明细字段]作为关联
                    $autoGetUserRelationFormControl = $processAutoGetUser[1];
                    //判断控件类型
                    $formControlStructure = app($this->flowFormControlStructureRepository)->getFlowFormControlStructure(['search' => ["form_id" => [$formId], 'control_id' => [$autoGetUserRelationFormControl]]]);
                    if ($formControlStructure && isset($formControlStructure[0])) {
                        if ($formControlStructure[0]['control_type'] == 'select') {
                            $autoGetUserRelationFormControl = $processAutoGetUser[1] . '_TEXT';
                        }
                    }

                    $flowRunFormDataParams = [
                        "run_id" => $runId,
                        "form_id" => $formId,
                        "fields" => [$autoGetUserRelationFormControl],
                    ];
                    $flowRunFormData = $data['form_data'] ?? app($this->flowRunService)->getFlowRunFormData($flowRunFormDataParams);
                    $autoUserStr = $flowRunFormData[$autoGetUserRelationFormControl] ?? '';

                    if (!empty($autoUserStr)) {
                        $autoUserStr = strip_tags($autoUserStr);
                        if (strpos($autoUserStr, "WV") || strpos($autoUserStr, "WV") === 0 || strpos($autoUserStr, "admin") || strpos($autoUserStr, "admin") === 0) {
                            $autoUserStr = str_replace(['[', ']', '"'], '', $autoUserStr);
                            $autoUserStr = explode(',', $autoUserStr);
                            foreach ($autoUserStr as $key => $value) {
                                $autoGetUserInfo[$key] = $value;
                            }
                            if (empty($autoGetUserInfo)) {
                                break;
                            }
                            // 过滤掉离职和删除的用户
                            $newAutoGetUserInfo = app($this->userRepository)->filterLeaveOffAndDeletedUserId($autoGetUserInfo);
                            if (empty($newAutoGetUserInfo)) {
                                $autoGetUserInfo = [];
                                break;
                            }
                            foreach ($autoGetUserInfo as $key => $value) {
                                if (!in_array($value, $newAutoGetUserInfo)) {
                                    unset($autoGetUserInfo[$key]);
                                }
                            }
                        } else {
                            //其余的内容，需要查询数据库
                            $autoUserStr = str_replace("'", '"', $autoUserStr);
                            $userConvert = trim(str_replace("，", ",", $autoUserStr));
                            $userConvert = trim(str_replace(" ", ",", $autoUserStr));
                            $autoGetUserInfo = app($this->userRepository)->getUserList(['search' => ['user_name' => [explode(',', $userConvert), 'in']], 'returntype' => 'object'])->pluck('user_id')->toArray();
                        }
                    }
                    break;
                default:
                    $autoGetUserInfo = [];
                    break;
            }
        }
        return $autoGetUserInfo;
    }

    /**
     * 获取流出节点线路，预测可能会经过的节点
     */
    public function getOutFlowNodeLine($params)
    {
        // 定义流程节点线路
        static $lineNodeArray = [];

        $nodeId = $params['node_id'] ?? 0;
        $runId = $params['run_id'] ?? 0;
        $flowId = $params['flow_id'] ?? 0;
        $userId = $params['user_id'] ?? '';
        $formId = $params['form_id'] ?? 0;
        // 这里为了获取表单数据，参数不支持下划线，先重新拼装参数，后续参数格式统一
        $getDataParams = [
            'flowId' => $flowId,
            'runId' => $runId,
            'nodeId' => $nodeId,
            'status' => 'view',
            'formId' => $formId
        ];
        $formDataResult = app($this->flowService)->getFlowFormParseData($getDataParams, ['user_id' => $userId]);
        $formData = $params['form_data'] ?? ($formDataResult['parseData'] ?? []);
        $formControlStructure = $formDataResult['parseFormStructure'] ?? [];
        // 当前节点出口
        $nodeInfo = app($this->flowProcessRepository)->getDetail($nodeId, false, ['sort', 'process_to'])->toArray();
        $outNodeIdString = $nodeInfo['process_to'] ?? '';
        if (empty($outNodeIdString)) {
            return [];
        }
        $outNodeIdArray = explode(',', trim($outNodeIdString, ','));
        // 获取比当前序号大的节点
        $searchProcessListParams = [
            'search' => [
                'flow_id' => [$flowId],
                'sort' => [$nodeInfo['sort'], '>']
            ],
            'fields' => ['node_id']
        ];
        $nodeList = app($this->flowProcessRepository)->getFlowProcessList($searchProcessListParams)->pluck('node_id')->toArray();
        // 可能流出的节点
        $canOutNodeId = array_intersect($outNodeIdArray, $nodeList);
        if (empty($canOutNodeId)) {
            return [];
        }
        // 通过出口条件验证，取唯一出口
        $flowEdges = app($this->flowTermRepository)->getFlowNodeOutletList(["flow_id" => $flowId]);
        if (empty($flowEdges) && count($canOutNodeId) > 1) {
            return [];
        }
        $newFlowEdges = [];
        foreach ($flowEdges as $key => $value) {
            $newFlowEdges[$value['source_id'].'_'.$value['target_id']] = $value;
        }
        // 计算当前节点满足条件的出口
        $canOutNodeIdList = [];
        foreach ($canOutNodeId as $key => $value) {
            $conditionKey = $nodeId.'_'. $value;
            if (empty($newFlowEdges[$conditionKey]) || empty($newFlowEdges[$conditionKey]['condition'])) {
                $canOutNodeIdList[] = $value;
                continue;
            }
            $verifyConditionParams = [
                'form_structure' => $formControlStructure,
                // 'user_id'        => $currentUser,
                // 'process_id'     => $processId,
            ];
            $verifyConditionResult = app($this->flowRunService)->verifyFlowFormOutletCondition($newFlowEdges[$conditionKey]['condition'], $formData, $verifyConditionParams);
            if ($verifyConditionResult) {
                $canOutNodeIdList[] = $value;
            }
        }
        // 如果满足条件的出口唯一，流转线路追加，然后继续查找下一个节点，直到查到有分叉的时候结束
        if (count($canOutNodeIdList) == 1) {
            $lineNodeArray[] = $canOutNodeIdList[0];
            // 继续查询下一个出口
            $params['node_id'] = $canOutNodeIdList[0];
            $this->getOutFlowNodeLine($params);
        }
        return $lineNodeArray;
    }
}
