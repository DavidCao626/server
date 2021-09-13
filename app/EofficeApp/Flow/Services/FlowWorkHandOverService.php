<?php
namespace App\EofficeApp\Flow\Services;

use App\EofficeApp\Flow\Services\FlowBaseService;
use DB;
use Eoffice;
use Illuminate\Support\Facades\Redis;
use Carbon\Carbon;

/**
 * 流程交办service类
 *
 * @since  2020-08-07 创建
 */
class FlowWorkHandOverService extends FlowBaseService
{
    public function __construct()
    {
        parent::__construct();
    }

    public function getFlowOutUser($param, $userInfo)
    {
        $flowId = '';
        if (isset($param['flow_id'])) {
            $flowId = $param['flow_id'];
            if (!is_array($flowId) && $flowId != 'all') {
                $flowId = explode(',', $flowId);
            }
        }
        if (empty($flowId)) {
            return [];
        }
        $all_quit_user_ids  = [];
        $all_quit_user_info = [];

        //获取所有离职人员
        $all_quit_user = $this->getAllOutUserList();
        foreach ($all_quit_user as $key => $value) {
            $all_quit_user_ids[]                   = $value['user_id'];
            $all_quit_user_info[$value['user_id']] = $value['user_name'] ? $value['user_name'] : trans('flow.deleted_user');
        }
        if (!Redis::exists('out_user_infos_' . serialize($param['flow_id']))) {
            $result_user_info = [];
            if ($flowId) {
                //查找相关节点id
                $allNodeId = app($this->flowProcessRepository)->getAllNodeByFlowId($flowId);
                if ($allNodeId) {
                    $allNodeIds = $allNodeId->pluck('node_id')->toArray();
                    $allNodeId  = $allNodeId->toArray();
                    //1、节点中的默认主办人
                    $processDefaultManageUserInfo = app($this->flowProcessRepository)->getProcessDefaultManageUserInfo($allNodeIds, $all_quit_user_ids);
                    if ($processDefaultManageUserInfo) {
                        $processDefaultManageUserInfo = $processDefaultManageUserInfo->toArray();
                        foreach ($processDefaultManageUserInfo as $key => $value) {
                            if (isset($all_quit_user_info[$value['process_default_manage']])) {
                                if (!isset($result_user_info[$value['process_default_manage']])) {
                                    $result_user_info[$value['process_default_manage']]['user_name'] = $all_quit_user_info[$value['process_default_manage']];
                                    $result_user_info[$value['process_default_manage']]['user_id']   = $value['process_default_manage'];
                                    $result_user_info[$value['process_default_manage']]['total']     = 0;
                                    $result_user_info[$value['process_default_manage']]['is_run']    = 0;
                                }
                                $result_user_info[$value['process_default_manage']]['total']++;
                            }
                        }
                    }
                    //2、节点设置中的默认办理人
                    $processDefaultUserInfo = app($this->flowProcessDefaultUserRepository)->getProcessDefaultUserInfo($allNodeIds, $all_quit_user_ids);
                    if ($processDefaultUserInfo) {
                        $processDefaultUserInfo = $processDefaultUserInfo->toArray();
                        foreach ($processDefaultUserInfo as $key => $value) {

                            if (isset($all_quit_user_info[$value['user_id']])) {
                                if (!isset($result_user_info[$value['user_id']])) {
                                    $result_user_info[$value['user_id']]['user_name'] = $all_quit_user_info[$value['user_id']];
                                    $result_user_info[$value['user_id']]['user_id']   = $value['user_id'];
                                    $result_user_info[$value['user_id']]['total']     = 0;
                                    $result_user_info[$value['user_id']]['is_run']    = 0;
                                }
                                $result_user_info[$value['user_id']]['total']++;
                            }
                        }
                    }
                    //3、节点设置中的经办人
                    $processUserInfo = app($this->flowProcessUserRepository)->getProcessDefaultUserInfo($allNodeIds, $all_quit_user_ids);
                    if ($processUserInfo) {
                        $processUserInfo = $processUserInfo->toArray();
                        foreach ($processUserInfo as $key => $value) {

                            if (isset($all_quit_user_info[$value['user_id']])) {
                                if (!isset($result_user_info[$value['user_id']])) {
                                    $result_user_info[$value['user_id']]['user_name'] = $all_quit_user_info[$value['user_id']];
                                    $result_user_info[$value['user_id']]['user_id']   = $value['user_id'];
                                    $result_user_info[$value['user_id']]['total']     = 0;
                                    $result_user_info[$value['user_id']]['is_run']    = 0;
                                }
                                $result_user_info[$value['user_id']]['total']++;
                            }
                        }
                    }
                    //4、节点中智能获取办理人规则中的人员
                    if ($flowId == 'all') {
                        $processAutoUserInfo = app($this->flowProcessRepository)->getFlowProcessAutoGetUserInfo(['search' => ['process_auto_get_user' => ['', '!=']]]);
                    } else {
                        $processAutoUserInfo = app($this->flowProcessRepository)->getFlowProcessAutoGetUserInfo(['search' => ['flow_id' => [$flowId, 'in'], 'process_auto_get_user' => ['', '!=']]]);
                    }
                    if ($processAutoUserInfo) {
                        $processAutoUserInfo = $processAutoUserInfo->toArray();
                        foreach ($processAutoUserInfo as $key => $value) {
                            //匹配只能获取办理人规则中的人
                            $matches = [];
                            if (preg_match('/WV\d{8}|admin/', $value['process_auto_get_user'], $matches)) {
                                $auto_user_str = $matches[0];

                                if (isset($all_quit_user_info[$auto_user_str])) {
                                    if (!isset($result_user_info[$auto_user_str])) {
                                        $result_user_info[$auto_user_str]['user_name'] = $all_quit_user_info[$auto_user_str];
                                        $result_user_info[$auto_user_str]['user_id']   = $auto_user_str;
                                        $result_user_info[$auto_user_str]['total']     = 0;
                                        $result_user_info[$auto_user_str]['is_run']    = 0;
                                    }
                                    $result_user_info[$auto_user_str]['total']++;
                                }
                            }

                        }
                    }
                    //5、自由流程经办人
                    $freeCreateUserInfo = app($this->flowTypeCreateUserRepository)->getfreeCreateList($flowId, $all_quit_user_ids);
                    if ($freeCreateUserInfo) {
                        $freeCreateUserInfo = $freeCreateUserInfo->toArray();
                        foreach ($freeCreateUserInfo as $key => $value) {

                            if (isset($all_quit_user_info[$value['user_id']])) {
                                if (!isset($result_user_info[$value['user_id']])) {
                                    $result_user_info[$value['user_id']]['user_name'] = $all_quit_user_info[$value['user_id']];
                                    $result_user_info[$value['user_id']]['user_id']   = $value['user_id'];
                                    $result_user_info[$value['user_id']]['total']     = 0;
                                    $result_user_info[$value['user_id']]['is_run']    = 0;
                                }
                                $result_user_info[$value['user_id']]['total']++;
                            }
                        }
                    }
                    //6、流程监控人设置中的监控人
                    $manageUserInfo = app($this->flowTypeManageUserRepository)->getManageUserList($flowId, $all_quit_user_ids);
                    if ($manageUserInfo) {
                        $manageUserInfo = $manageUserInfo->toArray();
                        foreach ($manageUserInfo as $key => $value) {

                            if (isset($all_quit_user_info[$value['user_id']])) {
                                if (!isset($result_user_info[$value['user_id']])) {
                                    $result_user_info[$value['user_id']]['user_name'] = $all_quit_user_info[$value['user_id']];
                                    $result_user_info[$value['user_id']]['user_id']   = $value['user_id'];
                                    $result_user_info[$value['user_id']]['total']     = 0;
                                    $result_user_info[$value['user_id']]['is_run']    = 0;
                                }
                                $result_user_info[$value['user_id']]['total']++;
                            }
                        }
                    }
                    //7、流程监控人设置中的监控人监控范围
                    $manageScopeUserInfo = app($this->flowTypeManageScopeUserRepository)->getManageUserList($flowId, $all_quit_user_ids);
                    if ($manageScopeUserInfo) {
                        $manageScopeUserInfo = $manageScopeUserInfo->toArray();
                        foreach ($manageScopeUserInfo as $key => $value) {

                            if (isset($all_quit_user_info[$value['user_id']])) {
                                if (!isset($result_user_info[$value['user_id']])) {
                                    $result_user_info[$value['user_id']]['user_name'] = $all_quit_user_info[$value['user_id']];
                                    $result_user_info[$value['user_id']]['user_id']   = $value['user_id'];
                                    $result_user_info[$value['user_id']]['total']     = 0;
                                    $result_user_info[$value['user_id']]['is_run']    = 0;
                                }
                                $result_user_info[$value['user_id']]['total']++;
                            }
                        }
                    }
                    //8、流程抄送人设置中的人
                    $processCopyUserInfo = app($this->flowProcessCopyUserRepository)->getProcessCopyUserInfo($allNodeIds, $all_quit_user_ids);
                    if ($processCopyUserInfo) {
                        $processCopyUserInfo = $processCopyUserInfo->toArray();
                        foreach ($processCopyUserInfo as $key => $value) {

                            if (isset($all_quit_user_info[$value['user_id']])) {
                                if (!isset($result_user_info[$value['user_id']])) {
                                    $result_user_info[$value['user_id']]['user_name'] = $all_quit_user_info[$value['user_id']];
                                    $result_user_info[$value['user_id']]['user_id']   = $value['user_id'];
                                    $result_user_info[$value['user_id']]['total']     = 0;
                                    $result_user_info[$value['user_id']]['is_run']    = 0;
                                }
                                $result_user_info[$value['user_id']]['total']++;
                            }
                        }
                    }
                    //9、流程抄送人设置智能获取抄送人中的人
                    if ($flowId == 'all') {
                        $processCopyAutoUserInfo = app($this->flowProcessRepository)->getFlowProcessAutoGetUserInfo(['search' => ['process_auto_get_copy_user' => ['', '!=']]]);
                    } else {
                        $processCopyAutoUserInfo = app($this->flowProcessRepository)->getFlowProcessAutoGetUserInfo(['search' => ['flow_id' => [$flowId, 'in'], 'process_auto_get_copy_user' => ['', '!=']]]);
                    }
                    if ($processCopyAutoUserInfo) {
                        $processCopyAutoUserInfo = $processCopyAutoUserInfo->toArray();
                        foreach ($processCopyAutoUserInfo as $key => $value) {
                            //匹配只能获取办理人规则中的人
                            $matches = [];
                            if (preg_match('/WV\d{8}|admin/', $value['process_auto_get_copy_user'], $matches)) {
                                $auto_user_str = $matches[0];

                                if (isset($all_quit_user_info[$auto_user_str])) {
                                    if (!isset($result_user_info[$auto_user_str])) {
                                        $result_user_info[$auto_user_str]['user_name'] = $all_quit_user_info[$auto_user_str];
                                        $result_user_info[$auto_user_str]['user_id']   = $auto_user_str;
                                        $result_user_info[$auto_user_str]['total']     = 0;
                                        $result_user_info[$auto_user_str]['is_run']    = 0;
                                    }
                                    $result_user_info[$auto_user_str]['total']++;
                                }
                            }

                        }
                    }
                    //10、流程委托设置中的人
                    if ($flowId == 'all') {
                        $flowAgencyUserInfo = app($this->flowAgencyRepository)->getFlowAgencyUserInfo(['user_id' => $all_quit_user_ids]);
                    } else {
                        $flow_agency_id    = '';
                        $getFlowAgencyInfo = app($this->flowAgencyDetailRepository)->getFlowAgencyInfo($flowId);
                        if ($getFlowAgencyInfo) {
                            $flow_agency_id = $getFlowAgencyInfo->pluck('flow_agency_id')->toArray();
                        }
                        if (!empty($flow_agency_id)) {
                            $flowAgencyUserInfo = app($this->flowAgencyRepository)->getFlowAgencyUserInfo(['flow_agency_id' => $flow_agency_id, 'user_id' => $all_quit_user_ids]);
                        } else {
                            $flowAgencyUserInfo = '';
                        }

                    }
                    if ($flowAgencyUserInfo) {
                        $flowAgencyUserInfo = $flowAgencyUserInfo->toArray();
                        foreach ($flowAgencyUserInfo as $key => $value) {

                            if (isset($all_quit_user_info[$value['agent_id']])) {
                                if (!isset($result_user_info[$value['agent_id']])) {
                                    $result_user_info[$value['agent_id']]['user_name'] = $all_quit_user_info[$value['agent_id']];
                                    $result_user_info[$value['agent_id']]['user_id']   = $value['agent_id'];
                                    $result_user_info[$value['agent_id']]['total']     = 0;
                                    $result_user_info[$value['agent_id']]['is_run']    = 0;
                                }
                                $result_user_info[$value['agent_id']]['total']++;
                            }
                        }
                    }
                    //12、流程分类分权设置的管理人员
                    $flowSortUserInfo = app($this->flowSortUserRepository)->getManageUserList($all_quit_user_ids, $flowId);
                    if ($flowSortUserInfo) {
                        $flowSortUserInfo = $flowSortUserInfo->toArray();
                        foreach ($flowSortUserInfo as $key => $value) {

                            if (isset($all_quit_user_info[$value['user_id']])) {
                                if (!isset($result_user_info[$value['user_id']])) {
                                    $result_user_info[$value['user_id']]['user_name'] = $all_quit_user_info[$value['user_id']];
                                    $result_user_info[$value['user_id']]['user_id']   = $value['user_id'];
                                    $result_user_info[$value['user_id']]['total']     = 0;
                                    $result_user_info[$value['user_id']]['is_run']    = 0;
                                }
                                $result_user_info[$value['user_id']]['total']++;
                            }
                        }
                    }
                    //13、表单分类分权设置的管理人员
                    $flowFormSortUserInfo = app($this->flowFormSortUserRepository)->getManageUserList($all_quit_user_ids, $flowId);
                    if ($flowFormSortUserInfo) {
                        $flowFormSortUserInfo = $flowFormSortUserInfo->toArray();
                        foreach ($flowFormSortUserInfo as $key => $value) {

                            if (isset($all_quit_user_info[$value['user_id']])) {
                                if (!isset($result_user_info[$value['user_id']])) {
                                    $result_user_info[$value['user_id']]['user_name'] = $all_quit_user_info[$value['user_id']];
                                    $result_user_info[$value['user_id']]['user_id']   = $value['user_id'];
                                    $result_user_info[$value['user_id']]['total']     = 0;
                                    $result_user_info[$value['user_id']]['is_run']    = 0;
                                }
                                $result_user_info[$value['user_id']]['total']++;
                            }
                        }
                    }
                    //14、表单模板规则设置的人员
                    $flowFormTemplateUserInfo = app($this->flowFormTemplateRuleUserRepository)->getRunManageUserList($all_quit_user_ids, $allNodeIds);
                    if ($flowFormTemplateUserInfo) {
                        $flowFormTemplateUserInfo = $flowFormTemplateUserInfo->toArray();
                        foreach ($flowFormTemplateUserInfo as $key => $value) {

                            if (isset($all_quit_user_info[$value['user_id']])) {
                                if (!isset($result_user_info[$value['user_id']])) {
                                    $result_user_info[$value['user_id']]['user_name'] = $all_quit_user_info[$value['user_id']];
                                    $result_user_info[$value['user_id']]['user_id']   = $value['user_id'];
                                    $result_user_info[$value['user_id']]['total']     = 0;
                                    $result_user_info[$value['user_id']]['is_run']    = 0;
                                }
                                $result_user_info[$value['user_id']]['total']++;
                            }
                        }
                    }
                    //15、归档模板规则设置的人员
                    $flowFormTemplateUserInfo = app($this->flowFormTemplateRuleUserRepository)->getfilingManageUserList($all_quit_user_ids, $flowId);
                    if ($flowFormTemplateUserInfo) {
                        $flowFormTemplateUserInfo = $flowFormTemplateUserInfo->toArray();
                        foreach ($flowFormTemplateUserInfo as $key => $value) {

                            if (isset($all_quit_user_info[$value['user_id']])) {
                                if (!isset($result_user_info[$value['user_id']])) {
                                    $result_user_info[$value['user_id']]['user_name'] = $all_quit_user_info[$value['user_id']];
                                    $result_user_info[$value['user_id']]['user_id']   = $value['user_id'];
                                    $result_user_info[$value['user_id']]['total']     = 0;
                                    $result_user_info[$value['user_id']]['is_run']    = 0;
                                }
                                $result_user_info[$value['user_id']]['total']++;
                            }
                        }
                    }
                    //15、打印模板规则设置的人员
                    $flowFormTemplateUserInfo = app($this->flowFormTemplateRuleUserRepository)->getPrintManageUserList($all_quit_user_ids, $flowId);
                    if ($flowFormTemplateUserInfo) {
                        $flowFormTemplateUserInfo = $flowFormTemplateUserInfo->toArray();
                        foreach ($flowFormTemplateUserInfo as $key => $value) {

                            if (isset($all_quit_user_info[$value['user_id']])) {
                                if (!isset($result_user_info[$value['user_id']])) {
                                    $result_user_info[$value['user_id']]['user_name'] = $all_quit_user_info[$value['user_id']];
                                    $result_user_info[$value['user_id']]['user_id']   = $value['user_id'];
                                    $result_user_info[$value['user_id']]['total']     = 0;
                                    $result_user_info[$value['user_id']]['is_run']    = 0;
                                }
                                $result_user_info[$value['user_id']]['total']++;
                            }
                        }
                    }
                    //15、运行中流程中的办理人
                    $flowRunUserInfo = app($this->flowRunProcessRepository)->getFlowRunUserList($flowId, $all_quit_user_ids);
                    if ($flowRunUserInfo) {
                        $flowRunUserInfo = $flowRunUserInfo->toArray();
                        foreach ($flowRunUserInfo as $key => $value) {

                            if (isset($all_quit_user_info[$value['user_id']])) {
                                if (!isset($result_user_info[$value['user_id']])) {
                                    $result_user_info[$value['user_id']]['user_name'] = $all_quit_user_info[$value['user_id']];
                                    $result_user_info[$value['user_id']]['user_id']   = $value['user_id'];
                                    $result_user_info[$value['user_id']]['total']     = 0;
                                    $result_user_info[$value['user_id']]['is_run']    = 0;
                                }
                                $result_user_info[$value['user_id']]['total']++;
                                $result_user_info[$value['user_id']]['is_run']++;
                            }
                        }
                    }
                    //16、已结束的流程中的办理人
                    $flowRunDoneUserInfo = app($this->flowRunProcessRepository)->getFlowRunDoneUserList($flowId, $all_quit_user_ids);
                    if ($flowRunDoneUserInfo) {
                        $flowRunDoneUserInfo = $flowRunDoneUserInfo->toArray();
                        foreach ($flowRunDoneUserInfo as $key => $value) {

                            if (isset($all_quit_user_info[$value['user_id']])) {
                                if (!isset($result_user_info[$value['user_id']])) {
                                    $result_user_info[$value['user_id']]['user_name'] = $all_quit_user_info[$value['user_id']];
                                    $result_user_info[$value['user_id']]['user_id']   = $value['user_id'];
                                    $result_user_info[$value['user_id']]['total']     = 0;
                                    $result_user_info[$value['user_id']]['is_run']    = 0;
                                }
                                $result_user_info[$value['user_id']]['total']++;
                            }
                        }
                    }
                }
            }
            ksort($result_user_info);
            Redis::set('out_user_infos_' . serialize($param['flow_id']), serialize($result_user_info));
        }
        $replaceType = [
            [
                'id'    => 'flow_seting',
                'title' => trans('flow.flow_seting'),
            ],
            [
                'id'    => 'flow_sort_user',
                'title' => trans('flow.flow_type_seting'),
            ],
            [
                'id'    => 'flow_form_sort_user',
                'title' => trans('flow.flow_form_type_seting'),
            ],
            [
                'id'    => 'flow_run_process',
                'title' => trans('flow.flow_run_user'),
            ],
            [
                'id'    => 'flow_run_process_done',
                'title' => trans('flow.flow_run_user_done'),
            ],
        ];
        $result_user_info = unserialize(Redis::get('out_user_infos_' . serialize($param['flow_id'])));

        //缓存、分页

        $total   = count($result_user_info);
        $default = [
            'page'  => 1,
            'limit' => 10,
        ];
        $param = array_merge($default, $param);
        $start = $param['page'] * $param['limit'] - $param['limit'];
        return ['list' => array_values(array_slice($result_user_info, $start, $param['limit'])), 'total' => $total, 'type' => $replaceType];
    }
    /**
     * 查询离职人员列表
     */
    public function getAllOutUserList()
    {
        if (!Redis::exists('out_users_array')) {
            $all_quit_user = app($this->userService)->getLeaveOfficeUser([], []);
            if ($all_quit_user) {
                $all_quit_user = $all_quit_user->toArray();
                Redis::set('out_users_array', serialize($all_quit_user));
            }
        }
        return unserialize(Redis::get('out_users_array'));
    }
    /**
     *  查询一条或者多条流程中的离职人员
     *  查询一个或者多个人所参与的所有流程相关信息
     *  查找范围：
     *  1、固定流程办理人设置中的经办人、默认办理人、默认主办人、智能获取办理人规则中的人员
     *  2、自由流程设置的经办人员
     *  3、流程监控人设置中的监控人
     *  4、设置的抄送人
     *  5、设置的委托人
     *      a、规则中的委托人
     *      b、已经产生委托的流程
     *  6、流程分权设置的管理人员
     *  7、表单模板规则中设置的人员
     *  8、运行中流程中的流程办理人
     *      a、运行中的流程，且此人不在最新办理步骤（赋予新人查看此条流程的权限）
     *      b、运行中的流程，且此人在最新办理步骤（赋予新人与离职人员同等的办理权限）
     *  9、已结束流程中的流程办理人（赋予新人查看此条流程的权限）
     */
    public function getFlowUserInfo($param, $userInfo)
    {
        ini_set('memory_limit', '800M');
        $flowId      = '';
        $result_info = [];
        if (isset($param['flow_id'])) {
            $flowId = $param['flow_id'];
            if (!is_array($flowId) && $flowId != 'all') {
                $flowId = [$flowId];
            }
        }
        if (empty($flowId)) {
            return [];
        }
        $all_quit_user_ids  = [];
        $all_quit_user_info = [];
        if (isset($param['user_id']) && !empty($param['user_id'])) {
            foreach ($param['user_id'] as $value) {
                $all_quit_user_ids[]        = $value;
                $userName                   = app($this->userService)->getUserName($value);
                $all_quit_user_info[$value] = $userName ? $userName : trans('flow.deleted_user');
            }
        }
        if (!Redis::exists('one_user_infos_' . serialize($param['user_id']) . '_' . serialize($param['flow_id']))) {
            $result_user_info = [];
            if ($flowId) {
                //查找流程相关信息
                $flowTypeInfo  = [];
                $flowTypeInfos = app($this->flowTypeRepository)->getFlowTypeList(['fields' => ['flow_id', 'flow_name']]);
                if ($flowTypeInfos) {
                    foreach ($flowTypeInfos as $key => $value) {
                        $flowTypeInfo[$value['flow_id']] = $value['flow_name'];
                    }
                }

                //查找运行流程id
                $flowRunInfos = [];
                if ($flowId == 'all') {
                    $flowRunInfos = app($this->flowRunRepository)->getFlowRunList(['fields' => ['flow_id', 'run_id', 'run_name']]);
                } else {
                    $flowRunInfos = app($this->flowRunRepository)->getFlowRunList(['fields' => ['flow_id', 'run_id', 'run_name'], 'search' => ['flow_id' => [$flowId, 'in']]]);
                }

                if ($flowRunInfos) {
                    $flowRunInfos = $flowRunInfos->toArray();
                    foreach ($flowRunInfos as $key => $value) {
                        $flowRunInfos[$value['run_id']] = $value['run_name'];
                    }
                }

                //查找相关节点id
                $allNodeId     = app($this->flowProcessRepository)->getAllNodeByFlowId($flowId);
                $allNodeIdInfo = [];
                if ($allNodeId) {
                    $allNodeIds = $allNodeId->pluck('node_id')->toArray();
                    $allNodeId  = $allNodeId->toArray();
                    foreach ($allNodeId as $key => $value) {
                        if (!isset($allNodeIdInfo[$value['node_id']])) {
                            $allNodeIdInfo[$value['node_id']] = [];
                        }
                        $allNodeIdInfo[$value['node_id']]['node_id']      = $value['node_id'];
                        $allNodeIdInfo[$value['node_id']]['process_name'] = $value['process_name'];
                        $allNodeIdInfo[$value['node_id']]['flow_id']      = $value['flow_id'];
                        $allNodeIdInfo[$value['node_id']]['flow_name']    = isset($flowTypeInfo[$value['flow_id']]) ? $flowTypeInfo[$value['flow_id']] : trans('flow.undefined_flow');
                    }
                    //1、节点中的默认主办人
                    $processDefaultManageUserInfo = app($this->flowProcessRepository)->getProcessDefaultManageUserInfo($allNodeIds, $all_quit_user_ids);
                    if ($processDefaultManageUserInfo) {
                        $processDefaultManageUserInfo = $processDefaultManageUserInfo->toArray();
                        foreach ($processDefaultManageUserInfo as $key => $value) {
                            if (isset($all_quit_user_info[$value['process_default_manage']])) {
                                if (!isset($result_user_info[$value['process_default_manage']])) {
                                    $result_user_info[$value['process_default_manage']]['user_name'] = $all_quit_user_info[$value['process_default_manage']];
                                    $result_user_info[$value['process_default_manage']]['user_id']   = $value['process_default_manage'];
                                    $result_user_info[$value['process_default_manage']]['info']      = [];
                                }
                                $result_user_info[$value['process_default_manage']]['info'][] = [
                                    'user_name'    => $all_quit_user_info[$value['process_default_manage']],
                                    'user_id'      => $value['process_default_manage'],
                                    'process_name' => $value['process_name'],
                                    'node_id'      => $value['node_id'],
                                    'flow_id'      => $value['flow_id'],
                                    'flow_name'    => isset($flowTypeInfo[$value['flow_id']]) ? $flowTypeInfo[$value['flow_id']] : trans('flow.undefined_flow'),
                                    'type'         => 'process_default_manage',
                                    'table'        => 'flow_process',
                                    'primary'      => 'node_id',
                                    'update_field' => 'process_default_manage',
                                    'id'           => $value['node_id'],
                                ];
                            }
                        }
                    }
                    //2、节点设置中的默认办理人
                    $processDefaultUserInfo = app($this->flowProcessDefaultUserRepository)->getProcessDefaultUserInfo($allNodeIds, $all_quit_user_ids);
                    if ($processDefaultUserInfo) {
                        $processDefaultUserInfo = $processDefaultUserInfo->toArray();
                        foreach ($processDefaultUserInfo as $key => $value) {

                            if (isset($all_quit_user_info[$value['user_id']])) {
                                if (!isset($result_user_info[$value['user_id']])) {
                                    $result_user_info[$value['user_id']]['info']      = [];
                                    $result_user_info[$value['user_id']]['user_name'] = $all_quit_user_info[$value['user_id']];
                                    $result_user_info[$value['user_id']]['user_id']   = $value['user_id'];
                                }
                                $result_user_info[$value['user_id']]['info'][] = [
                                    'user_name'    => $all_quit_user_info[$value['user_id']],
                                    'user_id'      => $value['user_id'],
                                    'process_name' => isset($allNodeIdInfo[$value['id']]) ? $allNodeIdInfo[$value['id']]['process_name'] : trans('flow.undefined'),
                                    'node_id'      => $value['id'],
                                    'flow_id'      => isset($allNodeIdInfo[$value['id']]['flow_id']) ? $allNodeIdInfo[$value['id']]['flow_id'] : '',
                                    'flow_name'    => isset($allNodeIdInfo[$value['id']]['flow_name']) ? $allNodeIdInfo[$value['id']]['flow_name'] : trans('flow.undefined_flow'),
                                    'type'         => 'process_default_user',
                                    'table'        => 'flow_process_default_user',
                                    'primary'      => 'auto_id',
                                    'update_field' => 'user_id',
                                    'id'           => $value['auto_id'],
                                ];
                            }
                        }
                    }
                    //3、节点设置中的经办人
                    $processUserInfo = app($this->flowProcessUserRepository)->getProcessDefaultUserInfo($allNodeIds, $all_quit_user_ids);
                    if ($processUserInfo) {
                        $processUserInfo = $processUserInfo->toArray();
                        foreach ($processUserInfo as $key => $value) {

                            if (isset($all_quit_user_info[$value['user_id']])) {
                                if (!isset($result_user_info[$value['user_id']])) {
                                    $result_user_info[$value['user_id']]['info']      = [];
                                    $result_user_info[$value['user_id']]['user_name'] = $all_quit_user_info[$value['user_id']];
                                    $result_user_info[$value['user_id']]['user_id']   = $value['user_id'];
                                }
                                $result_user_info[$value['user_id']]['info'][] = [
                                    'user_name'    => $all_quit_user_info[$value['user_id']],
                                    'user_id'      => $value['user_id'],
                                    'process_name' => isset($allNodeIdInfo[$value['id']]) ? $allNodeIdInfo[$value['id']]['process_name'] : trans('flow.undefined'),
                                    'node_id'      => $value['id'],
                                    'flow_id'      => isset($allNodeIdInfo[$value['id']]['flow_id']) ? $allNodeIdInfo[$value['id']]['flow_id'] : '',
                                    'flow_name'    => isset($allNodeIdInfo[$value['id']]['flow_name']) ? $allNodeIdInfo[$value['id']]['flow_name'] : trans('flow.undefined_flow'),
                                    'type'         => 'process_handle_user',
                                    'table'        => 'flow_process_user',
                                    'primary'      => 'auto_id',
                                    'update_field' => 'user_id',
                                    'id'           => $value['auto_id'],
                                ];
                            }
                        }
                    }
                    //4、节点中智能获取办理人规则中的人员
                    if ($flowId == 'all') {
                        $processAutoUserInfo = app($this->flowProcessRepository)->getFlowProcessAutoGetUserInfo(['search' => ['process_auto_get_user' => ['', '!=']]]);
                    } else {
                        $processAutoUserInfo = app($this->flowProcessRepository)->getFlowProcessAutoGetUserInfo(['search' => ['flow_id' => [$flowId, 'in'], 'process_auto_get_user' => ['', '!=']]]);
                    }
                    if ($processAutoUserInfo) {
                        $processAutoUserInfo = $processAutoUserInfo->toArray();
                        foreach ($processAutoUserInfo as $key => $value) {
                            //匹配只能获取办理人规则中的人
                            $matches = [];
                            if (preg_match('/WV\d{8}|admin/', $value['process_auto_get_user'], $matches)) {
                                $auto_user_str = $matches[0];

                                if (isset($all_quit_user_info[$auto_user_str])) {
                                    if (!isset($result_user_info[$auto_user_str])) {
                                        $result_user_info[$auto_user_str]['info']      = [];
                                        $result_user_info[$auto_user_str]['user_name'] = $all_quit_user_info[$auto_user_str];
                                        $result_user_info[$auto_user_str]['user_id']   = $auto_user_str;
                                    }
                                    $result_user_info[$auto_user_str]['info'][] = [
                                        'user_name'    => $all_quit_user_info[$auto_user_str],
                                        'user_id'      => $auto_user_str,
                                        'process_name' => $value['process_name'],
                                        'node_id'      => $value['node_id'],
                                        'flow_id'      => $value['flow_id'],
                                        'flow_name'    => isset($flowTypeInfo[$value['flow_id']]) ? $flowTypeInfo[$value['flow_id']] : trans('flow.undefined_flow'),
                                        'type'         => 'process_auto_get_user',
                                        'rules'        => $value['process_auto_get_user'],
                                        'table'        => 'flow_process',
                                        'primary'      => 'node_id',
                                        'update_field' => 'process_auto_get_user',
                                        'id'           => $value['node_id'],
                                    ];
                                }
                            }

                        }
                    }
                    //5、自由流程经办人
                    $freeCreateUserInfo = app($this->flowTypeCreateUserRepository)->getfreeCreateList($flowId, $all_quit_user_ids);
                    if ($freeCreateUserInfo) {
                        $freeCreateUserInfo = $freeCreateUserInfo->toArray();
                        foreach ($freeCreateUserInfo as $key => $value) {

                            if (isset($all_quit_user_info[$value['user_id']])) {
                                if (!isset($result_user_info[$value['user_id']])) {
                                    $result_user_info[$value['user_id']]['info']      = [];
                                    $result_user_info[$value['user_id']]['user_name'] = $all_quit_user_info[$value['user_id']];
                                    $result_user_info[$value['user_id']]['user_id']   = $value['user_id'];
                                }
                                $result_user_info[$value['user_id']]['info'][] = [
                                    'user_name'    => $all_quit_user_info[$value['user_id']],
                                    'user_id'      => $value['user_id'],
                                    'process_name' => '',
                                    'node_id'      => '',
                                    'flow_id'      => $value['flow_id'],
                                    'flow_name'    => isset($flowTypeInfo[$value['flow_id']]) ? $flowTypeInfo[$value['flow_id']] : trans('flow.undefined_flow'),
                                    'type'         => 'free_create_user',
                                    'table'        => 'flow_type_create_user',
                                    'primary'      => 'flow_id',
                                    'update_field' => 'user_id',
                                    'id'           => $value['flow_id'],
                                ];
                            }
                        }
                    }
                    //6、流程监控人设置中的监控人
                    $manageUserInfo = app($this->flowTypeManageUserRepository)->getManageUserList($flowId, $all_quit_user_ids);
                    if ($manageUserInfo) {
                        $manageUserInfo = $manageUserInfo->toArray();
                        foreach ($manageUserInfo as $key => $value) {

                            if (isset($all_quit_user_info[$value['user_id']])) {
                                if (!isset($result_user_info[$value['user_id']])) {
                                    $result_user_info[$value['user_id']]['info']      = [];
                                    $result_user_info[$value['user_id']]['user_name'] = $all_quit_user_info[$value['user_id']];
                                    $result_user_info[$value['user_id']]['user_id']   = $value['user_id'];
                                }
                                $result_user_info[$value['user_id']]['info'][] = [
                                    'user_name'    => $all_quit_user_info[$value['user_id']],
                                    'user_id'      => $value['user_id'],
                                    'process_name' => '',
                                    'node_id'      => '',
                                    'flow_id'      => $value['flow_id'],
                                    'flow_name'    => isset($flowTypeInfo[$value['flow_id']]) ? $flowTypeInfo[$value['flow_id']] : trans('flow.undefined_flow'),
                                    'type'         => 'flow_manage_user',
                                    'table'        => 'flow_type_manage_user',
                                    'primary'      => 'flow_id',
                                    'update_field' => 'user_id',
                                    'id'           => $value['flow_id'],
                                ];
                            }
                        }
                    }
                    //7、流程监控人设置中的监控人监控范围
                    $manageScopeUserInfo = app($this->flowTypeManageScopeUserRepository)->getManageUserList($flowId, $all_quit_user_ids);
                    if ($manageScopeUserInfo) {
                        $manageScopeUserInfo = $manageScopeUserInfo->toArray();
                        foreach ($manageScopeUserInfo as $key => $value) {

                            if (isset($all_quit_user_info[$value['user_id']])) {
                                if (!isset($result_user_info[$value['user_id']])) {
                                    $result_user_info[$value['user_id']]['info']      = [];
                                    $result_user_info[$value['user_id']]['user_name'] = $all_quit_user_info[$value['user_id']];
                                    $result_user_info[$value['user_id']]['user_id']   = $value['user_id'];
                                }
                                $result_user_info[$value['user_id']]['info'][] = [
                                    'user_id'      => $value['user_id'],
                                    'user_name'    => $all_quit_user_info[$value['user_id']],
                                    'process_name' => '',
                                    'node_id'      => '',
                                    'flow_id'      => $value['flow_id'],
                                    'flow_name'    => isset($flowTypeInfo[$value['flow_id']]) ? $flowTypeInfo[$value['flow_id']] : trans('flow.undefined_flow'),
                                    'type'         => 'flow_manage_scope_user',
                                    'table'        => 'flow_type_manage_scope_user',
                                    'primary'      => 'flow_id',
                                    'update_field' => 'user_id',
                                    'id'           => $value['flow_id'],
                                ];
                            }
                        }
                    }
                    //8、流程抄送人设置中的人
                    $processCopyUserInfo = app($this->flowProcessCopyUserRepository)->getProcessCopyUserInfo($allNodeIds, $all_quit_user_ids);
                    if ($processCopyUserInfo) {
                        $processCopyUserInfo = $processCopyUserInfo->toArray();
                        foreach ($processCopyUserInfo as $key => $value) {

                            if (isset($all_quit_user_info[$value['user_id']])) {
                                if (!isset($result_user_info[$value['user_id']])) {
                                    $result_user_info[$value['user_id']]['info']      = [];
                                    $result_user_info[$value['user_id']]['user_name'] = $all_quit_user_info[$value['user_id']];
                                    $result_user_info[$value['user_id']]['user_id']   = $value['user_id'];
                                }
                                $result_user_info[$value['user_id']]['info'][] = [
                                    'user_id'      => $value['user_id'],
                                    'user_name'    => $all_quit_user_info[$value['user_id']],
                                    'process_name' => isset($allNodeIdInfo[$value['id']]) ? $allNodeIdInfo[$value['id']]['process_name'] : trans('flow.undefined'),
                                    'node_id'      => $value['id'],
                                    'flow_id'      => isset($allNodeIdInfo[$value['id']]['flow_id']) ? $allNodeIdInfo[$value['id']]['flow_id'] : '',
                                    'flow_name'    => isset($allNodeIdInfo[$value['id']]['flow_name']) ? $allNodeIdInfo[$value['id']]['flow_name'] : trans('flow.undefined_flow'),
                                    'type'         => 'process_copy_user',
                                    'table'        => 'flow_process_copy_user',
                                    'primary'      => 'auto_id',
                                    'update_field' => 'user_id',
                                    'id'           => $value['auto_id'],
                                ];
                            }
                        }
                    }
                    //9、流程抄送人设置智能获取抄送人中的人
                    if ($flowId == 'all') {
                        $processCopyAutoUserInfo = app($this->flowProcessRepository)->getFlowProcessAutoGetUserInfo(['search' => ['process_auto_get_copy_user' => ['', '!=']]]);
                    } else {
                        $processCopyAutoUserInfo = app($this->flowProcessRepository)->getFlowProcessAutoGetUserInfo(['search' => ['flow_id' => [$flowId, 'in'], 'process_auto_get_copy_user' => ['', '!=']]]);
                    }
                    if ($processCopyAutoUserInfo) {
                        $processCopyAutoUserInfo = $processCopyAutoUserInfo->toArray();
                        foreach ($processCopyAutoUserInfo as $key => $value) {
                            //匹配只能获取办理人规则中的人
                            $matches = [];
                            if (preg_match('/WV\d{8}|admin/', $value['process_auto_get_copy_user'], $matches)) {
                                $auto_user_str = $matches[0];

                                if (isset($all_quit_user_info[$auto_user_str])) {
                                    if (!isset($result_user_info[$auto_user_str])) {
                                        $result_user_info[$auto_user_str]['info']      = [];
                                        $result_user_info[$auto_user_str]['user_name'] = $all_quit_user_info[$auto_user_str];
                                        $result_user_info[$auto_user_str]['user_id']   = $auto_user_str;
                                    }
                                    $result_user_info[$auto_user_str]['info'][] = [
                                        'user_name'    => $all_quit_user_info[$auto_user_str],
                                        'user_id'      => $auto_user_str,
                                        'process_name' => $value['process_name'],
                                        'node_id'      => $value['node_id'],
                                        'flow_name'    => isset($flowTypeInfo[$value['flow_id']]) ? $flowTypeInfo[$value['flow_id']] : trans('flow.undefined_flow'),
                                        'flow_id'      => $value['flow_id'],
                                        'type'         => 'process_auto_get_copy_user',
                                        'rules'        => $value['process_auto_get_copy_user'],
                                        'table'        => 'flow_process',
                                        'primary'      => 'node_id',
                                        'update_field' => 'process_auto_get_copy_user',
                                        'id'           => $value['node_id'],
                                    ];
                                }
                            }

                        }
                    }
                    //10、流程委托设置中的人
                    if ($flowId == 'all') {
                        $flowAgencyUserInfo = app($this->flowAgencyRepository)->getFlowAgencyUserInfo(['user_id' => $all_quit_user_ids]);
                    } else {
                        $flow_agency_id    = '';
                        $getFlowAgencyInfo = app($this->flowAgencyDetailRepository)->getFlowAgencyInfo($flowId);
                        if ($getFlowAgencyInfo) {
                            $flow_agency_id = $getFlowAgencyInfo->pluck('flow_agency_id')->toArray();
                        }
                        if (!empty($flow_agency_id)) {
                            $flowAgencyUserInfo = app($this->flowAgencyRepository)->getFlowAgencyUserInfo(['flow_agency_id' => $flow_agency_id, 'user_id' => $all_quit_user_ids]);
                        } else {
                            $flowAgencyUserInfo = [];
                        }
                    }
                    if ($flowAgencyUserInfo) {
                        $flowAgencyUserInfo = $flowAgencyUserInfo->toArray();
                        foreach ($flowAgencyUserInfo as $key => $value) {

                            if (isset($all_quit_user_info[$value['agent_id']])) {
                                if (!isset($result_user_info[$value['agent_id']])) {
                                    $result_user_info[$value['agent_id']]['info']      = [];
                                    $result_user_info[$value['agent_id']]['user_name'] = $all_quit_user_info[$value['agent_id']];
                                    $result_user_info[$value['agent_id']]['user_id']   = $value['agent_id'];
                                }
                                $flowIdString = explode(',', $value['flow_id_string']);
                                $flow_name    = '';
                                $flow_id      = '';
                                foreach ($flowIdString as $_value) {
                                    if ($_value && isset($flowTypeInfo[$_value])) {
                                        $flow_name .= ($flow_name ? ',' : '') . ' ' . $flowTypeInfo[$_value];
                                        $flow_id .= ($flow_id ? ',' : '') . $_value;
                                    }
                                }
                                if (!empty($flow_name)) {
                                    $result_user_info[$value['agent_id']]['info'][] = [
                                        'user_id'      => $value['agent_id'],
                                        'user_name'    => $all_quit_user_info[$value['agent_id']],
                                        'flow_name'    => $flow_name,
                                        'flow_id'      => $flow_id,
                                        'type'         => 'flow_agency_user',
                                        'table'        => 'flow_agency',
                                        'primary'      => 'flow_agency_id',
                                        'update_field' => 'agent_id',
                                        'id'           => $value['flow_agency_id'],
                                    ];
                                }

                            }
                        }
                    }
                    //12、流程分类分权设置的管理人员
                    $flowSortUserInfo = app($this->flowSortUserRepository)->getManageUserList($all_quit_user_ids, $flowId);
                    if ($flowSortUserInfo) {
                        $flowSortUserInfo = $flowSortUserInfo->toArray();
                        foreach ($flowSortUserInfo as $key => $value) {

                            if (isset($all_quit_user_info[$value['user_id']])) {
                                if (!isset($result_user_info[$value['user_id']])) {
                                    $result_user_info[$value['user_id']]['info']      = [];
                                    $result_user_info[$value['user_id']]['user_name'] = $all_quit_user_info[$value['user_id']];
                                    $result_user_info[$value['user_id']]['user_id']   = $value['user_id'];
                                }
                                $result_user_info[$value['user_id']]['info'][] = [
                                    'user_id'      => $value['user_id'],
                                    'user_name'    => $all_quit_user_info[$value['user_id']],
                                    'type_id'      => $value['type_id'],
                                    'type_name'    => $value['title'],
                                    'type'         => 'flow_sort_user',
                                    'table'        => 'flow_sort_user',
                                    'primary'      => 'id',
                                    'update_field' => 'user_id',
                                    'id'           => $value['id'],
                                ];
                            }
                        }
                    }
                    //13、表单分类分权设置的管理人员
                    $flowFormSortUserInfo = app($this->flowFormSortUserRepository)->getManageUserList($all_quit_user_ids, $flowId);
                    if ($flowFormSortUserInfo) {
                        $flowFormSortUserInfo = $flowFormSortUserInfo->toArray();
                        foreach ($flowFormSortUserInfo as $key => $value) {

                            if (isset($all_quit_user_info[$value['user_id']])) {
                                if (!isset($result_user_info[$value['user_id']])) {
                                    $result_user_info[$value['user_id']]['info']      = [];
                                    $result_user_info[$value['user_id']]['user_name'] = $all_quit_user_info[$value['user_id']];
                                    $result_user_info[$value['user_id']]['user_id']   = $value['user_id'];
                                }
                                $result_user_info[$value['user_id']]['info'][] = [
                                    'user_id'      => $value['user_id'],
                                    'user_name'    => $all_quit_user_info[$value['user_id']],
                                    'type_id'      => $value['type_id'],
                                    'type_name'    => $value['title'],
                                    'type'         => 'flow_form_sort_user',
                                    'table'        => 'flow_form_sort_user',
                                    'primary'      => 'id',
                                    'update_field' => 'user_id',
                                    'id'           => $value['id'],
                                ];
                            }
                        }
                    }
                    //14、表单模板规则设置的人员
                    $flowFormTemplateUserInfo = app($this->flowFormTemplateRuleUserRepository)->getRunManageUserList($all_quit_user_ids, $allNodeIds);
                    if ($flowFormTemplateUserInfo) {
                        $flowFormTemplateUserInfo = $flowFormTemplateUserInfo->toArray();
                        foreach ($flowFormTemplateUserInfo as $key => $value) {

                            if (isset($all_quit_user_info[$value['user_id']])) {
                                if (!isset($result_user_info[$value['user_id']])) {
                                    $result_user_info[$value['user_id']]['info']      = [];
                                    $result_user_info[$value['user_id']]['user_name'] = $all_quit_user_info[$value['user_id']];
                                    $result_user_info[$value['user_id']]['user_id']   = $value['user_id'];
                                }
                                $result_user_info[$value['user_id']]['info'][] = [
                                    'user_id'      => $value['user_id'],
                                    'user_name'    => $all_quit_user_info[$value['user_id']],
                                    'process_name' => isset($allNodeIdInfo[$value['node_id']]['process_name']) ? $allNodeIdInfo[$value['node_id']]['process_name'] : '',
                                    'node_id'      => $value['node_id'] ? $value['node_id'] : '',
                                    'flow_id'      => $value['flow_id'],
                                    'flow_name'    => isset($flowTypeInfo[$value['flow_id']]) ? $flowTypeInfo[$value['flow_id']] : trans('flow.undefined_flow'),
                                    'type'         => 'flow_form_template_rule_user',
                                    'table'        => 'flow_form_template_rule_user',
                                    'primary'      => 'auto_id',
                                    'update_field' => 'user_id',
                                    'id'           => $value['auto_id'],
                                ];
                            }
                        }
                    }
                    //15、归档模板规则设置的人员
                    $flowFormTemplateUserInfo = app($this->flowFormTemplateRuleUserRepository)->getfilingManageUserList($all_quit_user_ids, $flowId);
                    if ($flowFormTemplateUserInfo) {
                        $flowFormTemplateUserInfo = $flowFormTemplateUserInfo->toArray();
                        foreach ($flowFormTemplateUserInfo as $key => $value) {

                            if (isset($all_quit_user_info[$value['user_id']])) {
                                if (!isset($result_user_info[$value['user_id']])) {
                                    $result_user_info[$value['user_id']]['info']      = [];
                                    $result_user_info[$value['user_id']]['user_name'] = $all_quit_user_info[$value['user_id']];
                                    $result_user_info[$value['user_id']]['user_id']   = $value['user_id'];
                                }
                                $result_user_info[$value['user_id']]['info'][] = [
                                    'user_id'      => $value['user_id'],
                                    'user_name'    => $all_quit_user_info[$value['user_id']],
                                    'process_name' => '',
                                    'node_id'      => '',
                                    'flow_id'      => $value['flow_id'],
                                    'flow_name'    => isset($flowTypeInfo[$value['flow_id']]) ? $flowTypeInfo[$value['flow_id']] : trans('flow.undefined_flow'),
                                    'type'         => 'flow_filing_template_rule_user',
                                    'table'        => 'flow_form_template_rule_user',
                                    'primary'      => 'auto_id',
                                    'update_field' => 'user_id',
                                    'id'           => $value['auto_id'],
                                ];
                            }
                        }
                    }
                    //15、打印模板规则设置的人员
                    $flowFormTemplateUserInfo = app($this->flowFormTemplateRuleUserRepository)->getPrintManageUserList($all_quit_user_ids, $flowId);
                    if ($flowFormTemplateUserInfo) {
                        $flowFormTemplateUserInfo = $flowFormTemplateUserInfo->toArray();
                        foreach ($flowFormTemplateUserInfo as $key => $value) {

                            if (isset($all_quit_user_info[$value['user_id']])) {
                                if (!isset($result_user_info[$value['user_id']])) {
                                    $result_user_info[$value['user_id']]['info']      = [];
                                    $result_user_info[$value['user_id']]['user_name'] = $all_quit_user_info[$value['user_id']];
                                    $result_user_info[$value['user_id']]['user_id']   = $value['user_id'];
                                }
                                $result_user_info[$value['user_id']]['info'][] = [
                                    'user_id'      => $value['user_id'],
                                    'user_name'    => $all_quit_user_info[$value['user_id']],
                                    'process_name' => '',
                                    'node_id'      => '',
                                    'flow_id'      => $value['flow_id'],
                                    'flow_name'    => isset($flowTypeInfo[$value['flow_id']]) ? $flowTypeInfo[$value['flow_id']] : trans('flow.undefined_flow'),
                                    'type'         => 'flow_print_template_rule_user',
                                    'table'        => 'flow_form_template_rule_user',
                                    'primary'      => 'auto_id',
                                    'update_field' => 'user_id',
                                    'id'           => $value['auto_id'],
                                ];
                            }
                        }
                    }
                    //15、运行中流程中的办理人(包含当前最新节点的待办流程和未提交的之前节点的经办人流程)
                    $flowRunUserInfo = app($this->flowRunProcessRepository)->getFlowRunUserList($flowId, $all_quit_user_ids);
                    if ($flowRunUserInfo) {
                        $flowRunUserInfo = $flowRunUserInfo->toArray();
                        foreach ($flowRunUserInfo as $key => $value) {
                            if (isset($all_quit_user_info[$value['user_id']])) {
                                if (!isset($result_user_info[$value['user_id']])) {
                                    $result_user_info[$value['user_id']]['info']      = [];
                                    $result_user_info[$value['user_id']]['user_name'] = $all_quit_user_info[$value['user_id']];
                                    $result_user_info[$value['user_id']]['user_id']   = $value['user_id'];
                                }
                                $result_user_info[$value['user_id']]['info'][] = [
                                    'user_id'             => $value['user_id'],
                                    'user_name'           => $all_quit_user_info[$value['user_id']],
                                    'process_name'        => isset($allNodeIdInfo[$value['flow_process']]) ? $allNodeIdInfo[$value['flow_process']]['process_name'] : trans('flow.undefined'),
                                    'flow_run_process_id' => $value['flow_run_process_id'],
                                    'node_id'             => $value['flow_process'], // 节点id
                                    'process_id'          => $value['process_id'], // 步骤id
                                    'flow_id'             => $value['flow_id'],
                                    'flow_name'           => isset($flowTypeInfo[$value['flow_id']]) ? $flowTypeInfo[$value['flow_id']] : trans('flow.undefined_flow'),
                                    'run_name'            => isset($flowRunInfos[$value['run_id']]) ? $flowRunInfos[$value['run_id']] : '',
                                    'run_id'              => $value['run_id'],
                                    'type'                => 'flow_run_process',
                                    'table'               => 'flow_run_process',
                                    'primary'             => 'run_id',
                                    'update_field'        => 'user_id',
                                    'id'                  => $value['run_id'],
                                    'flow_run_process_id' => $value['flow_run_process_id'] ?? 0,
                                ];
                            }
                        }
                    }
                    //16、已结束的流程中的办理人
                    $flowRunDoneUserInfo = app($this->flowRunProcessRepository)->getFlowRunDoneUserList($flowId, $all_quit_user_ids);
                    if ($flowRunDoneUserInfo) {
                        $flowRunDoneUserInfo = $flowRunDoneUserInfo->toArray();
                        foreach ($flowRunDoneUserInfo as $key => $value) {

                            if (isset($all_quit_user_info[$value['user_id']])) {
                                if (!isset($result_user_info[$value['user_id']])) {
                                    $result_user_info[$value['user_id']]['info']      = [];
                                    $result_user_info[$value['user_id']]['user_name'] = $all_quit_user_info[$value['user_id']];
                                    $result_user_info[$value['user_id']]['user_id']   = $value['user_id'];
                                }
                                // 已结束未办理的，增加标识进行区分
                                $isEnd = 0;
                                if (isset($value['user_run_type']) && $value['user_run_type'] == 1) {
                                    $isEnd = 1;
                                }
                                $result_user_info[$value['user_id']]['info'][] = [
                                    'user_id'      => $value['user_id'],
                                    'user_name'    => $all_quit_user_info[$value['user_id']],
                                    'process_name' => isset($allNodeIdInfo[$value['flow_process']]) ? $allNodeIdInfo[$value['flow_process']]['process_name'] : trans('flow.undefined'),
                                    'node_id'      => $value['flow_process'],
                                    'process_id'   => $value['process_id'],
                                    'flow_id'      => $value['flow_id'],
                                    'flow_name'    => isset($flowTypeInfo[$value['flow_id']]) ? $flowTypeInfo[$value['flow_id']] : trans('flow.undefined_flow'),
                                    'run_name'     => isset($flowRunInfos[$value['run_id']]) ? $flowRunInfos[$value['run_id']].($isEnd?'【'.trans('flow.no_handle').'】':'') : '',
                                    'run_id'       => $value['run_id'],
                                    'type'         => 'flow_run_process_done',
                                    'table'        => 'flow_run_process',
                                    'primary'      => 'run_id',
                                    'update_field' => 'user_id',
                                    'id'           => $value['run_id'],
                                    'flow_run_process_id' => $value['flow_run_process_id'] ?? 0,

                                ];
                            }
                        }
                    }
                }
            }
            $result_type = [];
            $result_flow = [];
            $result_user = [];
            $replaceType = [];
            foreach ($result_user_info as $key => $value) {
                $userId = $key;
                foreach ($value['info'] as $_value) {
                    $is_run = false;
                    //按功能分类
                    $type = $_value['type'];
                    $id   = $_value['id'];

                    $userName      = $_value['user_name'];
                    $info          = '';
                    $position_type = '';
                    $position_flow = '';

                    switch ($type) {
                        case 'process_default_manage':
                            $info          = trans('flow.default_user');
                            $position_type = '[' . trans('flow.flow') . '] ' . $_value['flow_name'] . ' [' . trans('flow.node') . '] ' . $_value['process_name'];
                            $position_flow = '[' . trans('flow.node') . '] ' . $_value['process_name'];
                            break;
                        case 'process_default_user':
                            $info          = trans('flow.default_hander');
                            $position_type = '[' . trans('flow.flow') . '] ' . $_value['flow_name'] . ' [' . trans('flow.node') . '] ' . $_value['process_name'];
                            $position_flow = '[' . trans('flow.node') . '] ' . $_value['process_name'];
                            break;
                        case 'process_handle_user':
                            $info          = trans('flow.hander_user');
                            $position_type = '[' . trans('flow.flow') . '] ' . $_value['flow_name'] . ' [' . trans('flow.node') . '] ' . $_value['process_name'];
                            $position_flow = '[' . trans('flow.node') . '] ' . $_value['process_name'];
                            break;
                        case 'process_auto_get_user':
                            $info          = trans('flow.auto_rule_user');
                            $position_type = '[' . trans('flow.flow') . '] ' . $_value['flow_name'] . ' [' . trans('flow.node') . '] ' . $_value['process_name'];
                            $position_flow = '[' . trans('flow.node') . '] ' . $_value['process_name'];
                            break;
                        case 'free_create_user':
                            $info          = trans('flow.free_flow_user');
                            $position_type = '[' . trans('flow.flow') . '] ' . $_value['flow_name'];
                            $position_flow = '';
                            break;
                        case 'flow_manage_user':
                            $info          = trans('flow.manage_user');
                            $position_type = '[' . trans('flow.flow') . '] ' . $_value['flow_name'];
                            $position_flow = '';
                            break;
                        case 'flow_manage_scope_user':
                            $info          = trans('flow.manage_scope_user');
                            $position_type = '[' . trans('flow.flow') . '] ' . $_value['flow_name'];
                            $position_flow = '';
                            break;
                        case 'process_copy_user':
                            $info          = trans('flow.cope_user');
                            $position_type = '[' . trans('flow.flow') . '] ' . $_value['flow_name'] . ' [' . trans('flow.node') . '] ' . $_value['process_name'];
                            $position_flow = '[' . trans('flow.node') . '] ' . $_value['process_name'];
                            break;
                        case 'process_auto_get_copy_user':
                            $info          = trans('flow.auto_cope_user');
                            $position_type = '[' . trans('flow.flow') . '] ' . $_value['flow_name'] . ' [' . trans('flow.node') . '] ' . $_value['process_name'];
                            $position_flow = '[' . trans('flow.node') . '] ' . $_value['process_name'];
                            break;
                        case 'flow_agency_user':
                            $info          = trans('flow.agency_user');
                            $position_type = '[' . trans('flow.flow') . '] ' . $_value['flow_name'];
                            $position_flow = '[' . trans('flow.flow') . '] ' . $_value['flow_name'];
                            break;
                        case 'flow_sort_user':
                            $info          = trans('flow.type_user');
                            $position_type = '[' . trans('flow.flow_type') . '] ' . $_value['type_name'];
                            $position_flow = '[' . trans('flow.flow_type') . '] ' . $_value['type_name'];
                            break;
                        case 'flow_form_sort_user':
                            $info          = trans('flow.form_user');
                            $position_type = '[' . trans('flow.flow_form_type') . '] ' . $_value['type_name'];
                            $position_flow = '[' . trans('flow.flow_form_type') . '] ' . $_value['type_name'];
                            break;
                        case 'flow_form_template_rule_user':
                            $info          = trans('flow.template_user');
                            $position_type = '[' . trans('flow.flow') . '] ' . $_value['flow_name'] . ' [' . trans('flow.node') . '] ' . $_value['process_name'];
                            $position_flow = '[' . trans('flow.node') . '] ' . $_value['process_name'];
                            break;
                        case 'flow_filing_template_rule_user':
                            $info          = trans('flow.filing_template_user');
                            $position_type = '[' . trans('flow.flow') . '] ' . $_value['flow_name'];
                            $position_flow = '[' . trans('flow.flow') . '] ' . $_value['flow_name'];
                            break;
                        case 'flow_print_template_rule_user':
                            $info          = trans('flow.print_template_user');
                            $position_type = '[' . trans('flow.flow') . '] ' . $_value['flow_name'];
                            $position_flow = '[' . trans('flow.flow') . '] ' . $_value['flow_name'];
                            break;
                        case 'flow_run_process':
                            $info          = trans('flow.run_user');
                            $position_type = '[' . trans('flow.flow') . '] ' . $_value['flow_name'] . ' [' . trans('flow.run_flow') . '] ' . $_value['run_name'];
                            $position_flow = '[' . trans('flow.run_flow') . '] ' . $_value['run_name'];
                            $is_run        = true;
                            break;
                        case 'flow_run_process_done':
                            $info          = trans('flow.run_user_done');
                            $position_type = '[' . trans('flow.flow') . '] ' . $_value['flow_name'] . ' [' . trans('flow.run_flow') . '] ' . $_value['run_name'];
                            $position_flow = '[' . trans('flow.run_flow') . '] ' . $_value['run_name'];
                            break;
                        default:
                            $info          = trans('flow.undefined');
                            $position_type = trans('flow.undefined');
                            $position_flow = trans('flow.undefined');
                            break;
                    }
                    $replaceType = [
                        [
                            'id'    => 'flow_seting',
                            'title' => trans('flow.flow_seting'),
                        ],
                        [
                            'id'    => 'flow_sort_user',
                            'title' => trans('flow.flow_type_seting'),
                        ],
                        [
                            'id'    => 'flow_form_sort_user',
                            'title' => trans('flow.flow_form_type_seting'),
                        ],
                        [
                            'id'    => 'flow_run_process',
                            'title' => trans('flow.flow_run_user'),
                        ],
                        [
                            'id'    => 'flow_run_process_done',
                            'title' => trans('flow.flow_run_user_done'),
                        ],
                    ];

                    if (!isset($result_type[$type])) {
                        $result_type[$type]          = [];
                        $result_type[$type]['title'] = $info;
                        $result_type[$type]['info']  = [];
                    }
                    //按流程分类
                    $flowIdType = isset($_value['flow_id']) ? $_value['flow_id'] : 'other';
                    if ($_value['type'] == 'flow_agency_user') {
                        // || $_value['type'] == 'flow_agency_user_done'
                        $flowIdType = 'other';
                    }
                    if (!isset($result_flow[$flowIdType])) {
                        $result_flow[$flowIdType]          = [];
                        $result_flow[$flowIdType]['title'] = isset($_value['flow_name']) ? $_value['flow_name'] : trans('flow.0x030116');
                        if ($_value['type'] == 'flow_agency_user') {
                            // || $_value['type'] == 'flow_agency_user_done'
                            $result_flow[$flowIdType]['title'] = trans('flow.0x030116');
                        }
                        $result_flow[$flowIdType]['info'] = [];
                    }
                    //按人分类
                    if (!isset($result_user[$userId])) {
                        $result_user[$userId]            = [];
                        $result_user[$userId]['title']   = $userName;
                        $result_user[$userId]['user_id'] = $userId;
                        $result_user[$userId]['info']    = [];
                    }
                    $addInfo = true;
                    // 表单分类、流程分类不重复添加
                    if (($type == 'flow_form_sort_user' || $type == 'flow_sort_user') && count($result_type[$type]['info']) > 0) {
                        foreach ($result_type[$type]['info'] as $value) {
                            if ($value['id'] == $id) {
                                $addInfo = false;
                                break;
                            }
                        }
                    }
                    if ($addInfo) {
                        $result_type[$type]['info'][] = [
                            'is_run'              => $is_run,
                            'position'            => $position_type,
                            'user_id'             => $userId,
                            'flow_id'             => isset($_value['flow_id']) ? $_value['flow_id'] : trans('flow.0x030116'),
                            'process_id'          => $_value['process_id'] ?? 0,
                            'flow_run_process_id' => $_value['flow_run_process_id'] ?? 0,
                            'user_name'           => $userName,
                            'id'                  => $id,
                            'table'               => $_value['table'],
                            'primary'             => $_value['primary'],
                            'update_field'        => $_value['update_field'],
                            'type'                => $type,
                        ];
                    }
                    $result_flow[$flowIdType]['info'][] = [
                        'is_run'              => $is_run,
                        'user_id'             => $userId,
                        'user_name'           => $userName,
                        'flow_id'             => isset($_value['flow_id']) ? $_value['flow_id'] : trans('flow.0x030116'),
                        'process_id'          => $_value['process_id'] ?? 0,
                        'flow_run_process_id' => $_value['flow_run_process_id'] ?? 0,
                        'position'            => !$position_flow ? $info : ($position_flow . '   ' . $info),
                        'id'                  => $id,
                        'table'               => $_value['table'],
                        'primary'             => $_value['primary'],
                        'update_field'        => $_value['update_field'],
                        'type'                => $type,
                    ];

                    $result_user[$userId]['info'][] = [
                        'is_run'              => $is_run,
                        'user_id'             => $userId,
                        'user_name'           => $userName,
                        'flow_id'             => isset($_value['flow_id']) ? $_value['flow_id'] : trans('flow.0x030116'),
                        'process_id'          => $_value['process_id'] ?? 0,
                        'flow_run_process_id' => $_value['flow_run_process_id'] ?? 0,
                        'position'            => $position_type . '   ' . $info,
                        'id'                  => $id,
                        'table'               => $_value['table'],
                        'primary'             => $_value['primary'],
                        'update_field'        => $_value['update_field'],
                        'type'                => $type,
                    ];
                }
            }
            //前段按流程展示时，判断是否展示按钮
            foreach ($result_flow as $_flow => $flowInfo) {
                $result_flow[$_flow]['show'] = true;
                foreach ($flowInfo['info'] as $_info) {
                    if ($_info['is_run'] == false) {
                        $result_flow[$_flow]['show'] = false;
                    }
                }
            }
            //前段按人员展示时，判断是否展示按钮
            foreach ($result_user as $_userId => $flowInfo) {
                $result_user[$_userId]['show'] = true;
                foreach ($flowInfo['info'] as $_info) {
                    if ($_info['is_run'] == false) {
                        $result_user[$_userId]['show'] = false;
                    }
                }
            }
            $result_info = [
                'list_type' => $result_type,
                'list_flow' => $result_flow,
                'list_user' => $result_user,
                'source'    => $result_user_info,
                'type'      => $replaceType,
            ];
            $all_quit_user_list  = $this->getAllOutUserList();
            $all_quit_user_lists = [];
            foreach ($all_quit_user_list as $key => $value) {
                $all_quit_user_lists[] = $value['user_id'];
            }
            if (isset($param['user_id']) && !empty($param['user_id']) && in_array($param['user_id'][0], $all_quit_user_lists)) {
                Redis::set('one_user_infos_' . serialize($param['user_id']) . '_' . serialize($param['flow_id']), serialize($result_info));
            }
        }
        $all_quit_user_list  = $this->getAllOutUserList();
        $all_quit_user_lists = [];
        foreach ($all_quit_user_list as $key => $value) {
            $all_quit_user_lists[] = $value['user_id'];
        }
        if (isset($param['user_id']) && !empty($param['user_id']) && in_array($param['user_id'][0], $all_quit_user_lists)) {
            $result_info = unserialize(Redis::get('one_user_infos_' . serialize($param['user_id']) . '_' . serialize($param['flow_id'])));
        }
        return $result_info;
    }
    /**
     *
     */
    public function updataRedisData($params, $userInfo)
    {
        if (!empty(Redis::keys('one_user_infos_*'))) {
            Redis::del(Redis::keys('one_user_infos_*'));
        }
        if (!empty(Redis::keys('out_user_infos_*'))) {
            Redis::del(Redis::keys('out_user_infos_*'));
        }
        if (isset($params['select_mode']) && $params['select_mode'] == 1) {
            if (isset($params['flow_id'])) {
                $this->getFlowOutUser(['flow_id' => $params['flow_id']], $userInfo);
            }
        }
        $all_quit_user_list  = $this->getAllOutUserList();
        $all_quit_user_lists = [];
        foreach ($all_quit_user_list as $key => $value) {
            $all_quit_user_lists[] = $value['user_id'];
        }

        if (isset($params['user_id']) && isset($params['flow_id'])) {
            if (!is_array($params['user_id'])) {
                $params['user_id'] = [$params['user_id']];
            }
            if (in_array($params['user_id'][0], $all_quit_user_lists)) {
                $this->getFlowUserInfo(['user_id' => $params['user_id'], 'flow_id' => $params['flow_id']], $userInfo);
            }
        }
        return true;
    }
    /**
     * 人员全局替换
     */
    public function replaceFlowUser($params, $userInfo)
    {
        if (empty($params) || !isset($params['source']) || empty($params['source'])) {
            return false;
        }
        if (isset($params['type_selected']) && $params['type_selected'] != 'all' && is_array($params['type_selected'])) {
            $flowSeting = ['process_default_manage', 'process_default_user', 'process_handle_user', 'process_auto_get_user', 'free_create_user', 'flow_manage_user', 'flow_manage_scope_user', 'process_copy_user', 'process_auto_get_copy_user', 'flow_agency_user', 'flow_form_template_rule_user', 'flow_filing_template_rule_user', 'flow_print_template_rule_user'];
            if (in_array('flow_seting', $params['type_selected'])) {
                $params['type_selected'] = array_merge($flowSeting, $params['type_selected']);
            }
        }
        foreach ($params['source'] as $key => $value) {
            //获取替换人
            if (isset($value['user']) && !empty($value['user'])) {
                $replaceUser = $value['user'];
                $user        = $value['user_id'];
                if (isset($params['select_mode']) && $params['select_mode'] == 1) {
                    $info          = $this->getFlowUserInfo(['user_id' => [$user], 'flow_id' => $params['flow_id']], $userInfo);
                    $value['info'] = $info['list_user'][$user]['info'];
                }
                if (!empty($value['info'])) {
                    foreach ($value['info'] as $_value) {
                        if (isset($params['type_selected']) && $params['type_selected'] != 'all' && is_array($params['type_selected'])) {
                            if (!in_array($_value['type'], $params['type_selected'])) {
                                continue;
                            }
                        }
                        $this->addReplaceUser($_value, $user, $replaceUser, $userInfo);
                    }
                }
            }
        }
        //更新缓存数据
        $this->updataRedisData($params, $userInfo);
        return true;
    }
    /**
     * 人员单个替换
     */
    public function replaceOneFlowUser($params, $userInfo)
    {
        if (empty($params)) {
            return false;
        }
        if ($params['replace'] == 'one') {
            if (!isset($params['user']) || !isset($params['info']) || empty($params['info'])) {
                return false;
            }
            $user        = $params['info']['user_id'];
            $replaceUser = $params['user'];
            $this->addReplaceUser($params['info'], $user, $replaceUser, $userInfo);
        } else if ($params['replace'] == 'all') {
            if (isset($params['replace_user']) && !empty($params['replace_user'])) {
                if (isset($params['info'][$params['replace_user']]['info'])) {
                    foreach ($params['info'][$params['replace_user']]['info'] as $_value) {
                        $user        = $_value['user_id'];
                        $replaceUser = $params['user'];
                        $this->addReplaceUser($_value, $user, $replaceUser, $userInfo);
                    }
                }

            }
        } else {
            return false;
        }
        //更新缓存数据
        $this->updataRedisData($params, $userInfo);
        return true;
    }
    /**
     * 人员单个替换
     */
    public function replaceFlowUserByType($params, $userInfo)
    {
        if (empty($params) || !isset($params['info']) || !isset($params['user']) || empty($params['info']) || empty($params['user'])) {
            return false;
        }
        //获取替换关系
        $userReplaceInfo = [];
        foreach ($params['user'] as $value) {
            if (isset($value['user']) && !empty($value['user'])) {
                $userReplaceInfo[$value['user_id']] = $value['user'];
            }
        }

        foreach ($params['info'] as $_value) {
            $user = $_value['user_id'];
            //未选择替换人的，跳过
            if (!isset($userReplaceInfo[$_value['user_id']]) || empty($userReplaceInfo[$_value['user_id']])) {
                continue;
            }
            $replaceUser = $userReplaceInfo[$_value['user_id']];
            $this->addReplaceUser($_value, $user, $replaceUser, $userInfo);
        }
        //更新缓存数据
        $this->updataRedisData($params, $userInfo);
        return true;
    }
    /**
     * 人员单个替换
     */
    public function replaceFlowUserByGrid($params, $userInfo)
    {
        if (empty($params) || !isset($params['search']) || !isset($params['user']) || empty($params['user_id']) || !isset($params['user_id']) || empty($params['search']) || empty($params['user'])) {
            return false;
        }
        if (isset($params['type_selected']) && $params['type_selected'] != 'all' && is_array($params['type_selected'])) {
            $flowSeting = ['process_default_manage', 'process_default_user', 'process_handle_user', 'process_auto_get_user', 'free_create_user', 'flow_manage_user', 'flow_manage_scope_user', 'process_copy_user', 'process_auto_get_copy_user', 'flow_agency_user', 'flow_form_template_rule_user', 'flow_filing_template_rule_user', 'flow_print_template_rule_user'];
            if (in_array('flow_seting', $params['type_selected'])) {
                $params['type_selected'] = array_merge($flowSeting, $params['type_selected']);
            }
        }
        if (!is_array($params['user_id'])) {
            $params['user_id'] = [$params['user_id']];
        }
        $info = $this->getFlowUserInfo(['user_id' => $params['user_id'], 'flow_id' => $params['search']['flow_id']], $userInfo);
        if (isset($info['list_user'][$params['user_id'][0]]) && !empty($info['list_user'][$params['user_id'][0]])) {
            foreach ($info['list_user'][$params['user_id'][0]]['info'] as $_value) {
                $user        = $_value['user_id'];
                $replaceUser = $params['user'];
                //跳过未选择的类型
                if (isset($params['type_selected']) && $params['type_selected'] != 'all' && is_array($params['type_selected'])) {
                    if (!in_array($_value['type'], $params['type_selected'])) {
                        continue;
                    }
                }

                $this->addReplaceUser($_value, $user, $replaceUser, $userInfo);
            }
        }
        //更新缓存数据
        $this->updataRedisData(['user_id' => $params['user_id'], 'flow_id' => $params['search']['flow_id'], 'select_mode' => $params['select_mode']], $userInfo);
        return true;
    }
    /**
     * 人员单个替换
     */
    public function replaceFlowUserByUser($params, $userInfo)
    {
        if (empty($params) || !isset($params['info']) || !isset($params['user']) || empty($params['info']) || empty($params['user'])) {
            return false;
        }
        if (isset($params['type_selected']) && $params['type_selected'] != 'all' && is_array($params['type_selected'])) {
            $flowSeting = ['process_default_manage', 'process_default_user', 'process_handle_user', 'process_auto_get_user', 'free_create_user', 'flow_manage_user', 'flow_manage_scope_user', 'process_copy_user', 'process_auto_get_copy_user', 'flow_agency_user', 'flow_form_template_rule_user', 'flow_filing_template_rule_user', 'flow_print_template_rule_user'];
            if (in_array('flow_seting', $params['type_selected'])) {
                $params['type_selected'] = array_merge($flowSeting, $params['type_selected']);
            }
        }
        foreach ($params['info'] as $_value) {
            $user        = $_value['user_id'];
            $replaceUser = $params['user'];
            //跳过未选择的类型
            if (isset($params['type_selected']) && $params['type_selected'] != 'all' && is_array($params['type_selected'])) {
                if (!in_array($_value['type'], $params['type_selected'])) {
                    continue;
                }
            }

            $this->addReplaceUser($_value, $user, $replaceUser, $userInfo);
        }
        //更新缓存数据
        $this->updataRedisData($params, $userInfo);
        return true;
    }
    /**
     * 增加办理人
     */
    public function addRunInfo($runId, $user, $replaceUser, $userInfo, $flowRunProcessId)
    {
        $flowRunProcessDetail = app($this->flowRunProcessRepository)->getFlowRunProcessDetail($flowRunProcessId);
        // 如果被替换人未提交,则走委托的形式进行交办
        if ($flowRunProcessDetail->user_run_type == 1) {
            $this->replaceUserWhenFlowProcessIsRun($replaceUser, $user, $flowRunProcessDetail->flow_id, $flowRunProcessId, $runId);
            return true;
        }
        //查看替换人是否在要替换的运行流程中，在的话，不处理 -并发增加分支判断
        $oldRunInfo = app($this->flowRunProcessRepository)->getFlowRunProcessList(['search' => ['user_id' => [$replaceUser],'branch_serial'=>[$flowRunProcessDetail->branch_serial]], 'run_id' => $runId]);
        if (count($oldRunInfo) > 0) {
            return true;
        }
        //在被替换人所在的最新步骤 添加替换人为此步骤的办理人-最新数据为当前分支上的
        // $newRunInfo               = app($this->flowRunProcessRepository)->getFlowRunProcessList(['run_id' => $runId, 'order_by' => ['process_id' => 'desc'], 'returntype' => 'first','search'=>['process_serial'=>[$flowRunProcessDetail->process_serial]]]);

        $processId                = $flowRunProcessDetail->process_id;
        $flowProcess              = $flowRunProcessDetail->flow_process;
        $flowId                   = $flowRunProcessDetail->flow_id;
        $concurrentNodeId             = $flowRunProcessDetail->concurrent_node_id ?? 0;
        $branchSerial             = $flowRunProcessDetail->branch_serial ?? 0;
        $processSerial             = $flowRunProcessDetail->process_serial ?? 0;
        $flowSerial             = $flowRunProcessDetail->flow_serial ?? 0;
        $originProcessId             = $flowRunProcessDetail->origin_process_id ?? 0;
        $concurrentNodeId             = $flowRunProcessDetail->concurrent_node_id ?? 0;
        $originProcess             = $flowRunProcessDetail->origin_process ?? 0;
        $originUser             = $flowRunProcessDetail->origin_user ?? '';
        $outflowUser             = $flowRunProcessDetail->outflow_user ?? '';
        $outflowProcess             = $flowRunProcessDetail->outflow_process ?? 0;
        $insertFlowRunProcessData = [
            "run_id"            => $runId,
            "process_id"        => $processId,
            "receive_time"      => date('Y-m-d H:i:s'),
            "process_time"      => date('Y-m-d H:i:s'),
            "deliver_time"      => null,
            "last_visited_time" => date('Y-m-d H:i:s'),
            "saveform_time"     => date('Y-m-d H:i:s'),
            "transact_time"     => date('Y-m-d H:i:s'),
            "user_id"           => $replaceUser,
            "process_flag"      => "4",
            "flow_process"      => $flowProcess,
            "host_flag"         => "0",
            "replace_flag"      => "replace",
            "flow_id"           => $flowId,
            "concurrent_node_id"=> $concurrentNodeId,
            "branch_serial"     => $branchSerial,
            "process_serial"    => $processSerial,
            "flow_serial"       => $flowSerial,
            "origin_process_id" => $originProcessId,
            "concurrent_node_id"=> $concurrentNodeId,
            "origin_process"    => $originProcess,
            "origin_user"       => $originUser,
            "outflow_user"      => $outflowUser,
            "outflow_process"   => $outflowProcess,
            "user_run_type"     => 3,
        ];
        app($this->flowRunProcessRepository)->insertData($insertFlowRunProcessData);
        // 更新flow_run_step表的数据
        // app($this->flowRunService)->rebuildFlowRunStepDataServiceRealize(["run_id" => $runId]);
        app($this->flowParseService)->updateUserLastStepsFlag($runId, [$replaceUser,$user]);
        // 更新流程状态
        // app($this->flowRunStepRepository)->updateFlowRunStepData(["data" => ["user_run_type" => '3'], "wheres" => ["run_id" => [$runId], "process_id" => [$processId], "user_id" => [$replaceUser]]]);
        // 更新归档权限
        $documentInfo = app($this->documentContentRepository)->listNoPurDoc(['search' => ['source_id' => [$runId]]]);
        if ($documentInfo) {
            $documentInfo = $documentInfo->first();
            if ($documentInfo) {
                $shareInfo = app($this->documentService)->getDocumentShareMember($documentInfo->document_id);
                if (isset($shareInfo['share_user']) && !empty($shareInfo['share_user'])) {
                    $shareArray = $shareInfo['share_user'];
                    if (!in_array($replaceUser, $shareArray)) {
                        $shareArray[] = $replaceUser;
                    }
                    $shareUser = implode(',', $shareArray);
                } else {
                    $shareUser = $replaceUser;
                }
                if (isset($shareInfo['flow_manager']) && !empty($shareInfo['flow_manager'])) {
                    $managerArray = $shareInfo['flow_manager'];
                    if (!in_array($replaceUser, $managerArray)) {
                        $managerArray[] = $replaceUser;
                    }
                    $manager = implode(',', $managerArray);
                } else {
                    $manager = $replaceUser;
                }

                app($this->documentShareRepository)->updateData(['share_user' => $shareUser], ['document_id' => $documentInfo->document_id]);
                app($this->documentContentRepository)->updateData(['flow_manager' => $manager], ['document_id' => $documentInfo->document_id]);
            }
        }

        return true;
    }

    /**
     * 替换待办中的用户
     * @param $replaceUser
     * @param $user
     * @param $flowId
     * @param $flowRunProcessId
     * @param $runId
     */
    public function replaceUserWhenFlowProcessIsRun($replaceUser, $user, $flowId, $flowRunProcessId, $runId)
    {
        $flowRunProcessDetail = app($this->flowRunProcessRepository)->getFlowRunProcessDetail($flowRunProcessId);
        // 找到此人所有待办，一起交办
        $result = app($this->flowRunProcessRepository)->getFlowRunProcessInfo(['process_id', 'flow_run_process_id'], ['run_id'=> $runId, 'user_run_type' => 1, 'user_id' => $user]);
        $processId = array_values(array_unique(array_column($result, 'process_id'))); // 查询出多个 process_id
        $flowRunProcessId = array_values(array_unique(array_column($result, 'flow_run_process_id'))); // 查询出多个 flow_run_process_id
        $processUpdateData = [
            'user_id' => $replaceUser,
            // 'receive_time' => Carbon::now(),
            'process_time' => null,
            'process_flag' => 1,
            'agent_way' => 1
        ];
        if (! $flowRunProcessDetail->by_agent_id) {
            $processUpdateData['by_agent_id'] = $user;
        }
        app($this->flowRunProcessRepository)->updateFlowRunProcessData([
            "data" => $processUpdateData,
            "wheres" => [ "flow_run_process_id" => [$flowRunProcessId, 'in']],
        ]);

        foreach ($flowRunProcessId as $k => $v) {
            $oneData = app($this->flowRunProcessAgencyDetailRepository)->getOneFieldInfo(['flow_run_process_id' => [$v]]);
            $sort    = 0;
            if ($oneData) {
                $sort = app($this->flowRunProcessAgencyDetailRepository)->getFieldMaxValue('sort', ['flow_run_process_id' => [$v]]) + 1;
            }
            app($this->flowRunProcessAgencyDetailRepository)->insertData([
                'flow_run_process_id' => $v,
                'user_id'             => $replaceUser,
                'by_agency_id'        => $user,
                'sort'                => $sort,
                'type'                => 1,
            ]);
        }

        // 更新flow_run_step，删除flow_run_step表的此流程数据，重新添加
        // app($this->flowRunService)->rebuildFlowRunStepDataServiceRealize(["run_id" => $runId]);
        app($this->flowParseService)->updateUserLastStepsFlag($runId, [$replaceUser,$user]);
        // 发送提醒
        $runObject                = app($this->flowRunRepository)->getDetail($runId);
        $userName                 = app($this->userService)->getUserName($user);
        $sendData['remindMark']   = 'flow-assigned';
        $sendData['toUser']       = $replaceUser;
        $sendData['contentParam'] = ['flowTitle' => $runObject->run_name, 'userName' => $userName];
        $flowRunProcessId = current($flowRunProcessId);
        $sendData['stateParams']  = ["flow_id" => intval($flowId), "run_id" => intval($runId), 'flow_run_process_id' => intval($flowRunProcessId)];
        $sendData['module_type']  = app($this->flowTypeRepository)->getFlowSortByFlowId($flowId);
        Eoffice::sendMessage($sendData);
    }

    /**
     * 增加替换人员
     */
    public function addReplaceUser($param, $user, $replaceUser, $userInfo)
    {
        if ($replaceUser == $user) {
            return true;
        }
        if ($param['type'] == 'flow_run_process') {
            app($this->flowParseService)->markUnreadMessagesAsRead($param['flow_id'], $param['id'], $user);
            $this->replaceUserWhenFlowProcessIsRun($replaceUser, $user, $param['flow_id'], $param['flow_run_process_id'], $param['id']);
            return true;
        }
        if ($param['type'] == 'flow_run_process_done') {
            if (!isset($param['id'])) {
                return true;
            }
            //插入办理人
            $this->addRunInfo($param['id'], $user, $replaceUser, $userInfo, $param['flow_run_process_id']);
            $this->addReplaceUserLog($param, $user, $replaceUser, $userInfo['user_id']);
            return true;
        }
        //主办人
        if ($param['type'] == 'process_default_manage') {
            $_node_id = DB::table($param['table'])->where($param['primary'], $param['id'])->first();
            if ($_node_id && $_node_id->node_id) {
                $_node_id = $_node_id->node_id;
            } else {
                return true;
            }

            //替换
            DB::table($param['table'])->where($param['primary'], $param['id'])->update([$param['update_field'] => $replaceUser]);
            $this->addReplaceUserLog($param, $user, $replaceUser, $userInfo['user_id']);
            //添加办理人
            if (count(DB::table('flow_process_user')->where('id', $_node_id)->where('user_id', $replaceUser)->get()) == 0) {
                DB::table('flow_process_user')->insert(['id' => $_node_id, 'user_id' => $replaceUser]);
                $params                 = [];
                $params['table']        = 'flow_process_user';
                $params['type']         = 'process_handle_user';
                $params['update_field'] = 'user_id';
                $params['node_id']      = $_node_id;
                $this->addReplaceUserLog($params, $user, $replaceUser, $userInfo['user_id']);
            }
            //增加新人为默认办理人
            if (count(DB::table('flow_process_default_user')->where('id', $_node_id)->where('user_id', $replaceUser)->get()) == 0) {
                DB::table('flow_process_default_user')->insert(['id' => $_node_id, 'user_id' => $replaceUser]);
                $params                 = [];
                $params['table']        = 'flow_process_default_user';
                $params['type']         = 'process_default_user';
                $params['update_field'] = 'user_id';
                $params['node_id']      = $_node_id;
                $this->addReplaceUserLog($params, $user, $replaceUser, $userInfo['user_id']);
            }
            return true;
        }
        //默认办理人
        if ($param['type'] == 'process_default_user') {
            //替换
            $oldInfo = DB::table($param['table'])->where($param['primary'], $param['id'])->first();
            if ($oldInfo && $oldInfo->id) {
                $_node_id = $oldInfo->id;
            } else {
                return true;
            }

            if (count(DB::table($param['table'])->where('id', $oldInfo->id)->where('user_id', $replaceUser)->get()) == 0) {
                DB::table($param['table'])->where($param['primary'], $param['id'])->update([$param['update_field'] => $replaceUser]);
            } else {
                DB::table($param['table'])->where($param['primary'], $param['id'])->delete();
            }
            $this->addReplaceUserLog($param, $user, $replaceUser, $userInfo['user_id']);
            //添加办理
            if (count(DB::table('flow_process_user')->where('id', $_node_id)->where('user_id', $replaceUser)->get()) == 0) {
                DB::table('flow_process_user')->insert(['id' => $_node_id, 'user_id' => $replaceUser]);
                $params                 = [];
                $params['table']        = 'flow_process_user';
                $params['type']         = 'process_handle_user';
                $params['update_field'] = 'user_id';
                $params['node_id']      = $_node_id;
                $this->addReplaceUserLog($params, $user, $replaceUser, $userInfo['user_id']);
            }
            //修改默认主办人
            if (count(DB::table('flow_process')->where('node_id', $_node_id)->where('process_default_manage', $user)->get()) > 0) {
                DB::table('flow_process')->where('node_id', $_node_id)->update(['process_default_manage' => $replaceUser]);
                $params                 = [];
                $params['table']        = 'flow_process';
                $params['type']         = 'process_default_manage';
                $params['update_field'] = 'process_default_manage';
                $params['node_id']      = $_node_id;
                $this->addReplaceUserLog($params, $user, $replaceUser, $userInfo['user_id']);
            }
            return true;
        }
        //办理人
        if ($param['type'] == 'process_handle_user') {
            //替换
            $oldInfo = DB::table($param['table'])->where($param['primary'], $param['id'])->first();
            if ($oldInfo && $oldInfo->id) {
                $_node_id = $oldInfo->id;
            } else {
                return true;
            }
            if (count(DB::table($param['table'])->where('id', $oldInfo->id)->where('user_id', $replaceUser)->get()) == 0) {
                DB::table($param['table'])->where($param['primary'], $param['id'])->update([$param['update_field'] => $replaceUser]);
            } else {
                DB::table($param['table'])->where($param['primary'], $param['id'])->delete();
            }
            $this->addReplaceUserLog($param, $user, $replaceUser, $userInfo['user_id']);
            //如果是主办人，替换
            if (count(DB::table('flow_process')->where('node_id', $_node_id)->where('process_default_manage', $user)->get()) > 0) {
                DB::table('flow_process')->where('node_id', $_node_id)->update(['process_default_manage' => $replaceUser]);
                $params                 = [];
                $params['table']        = 'flow_process';
                $params['type']         = 'process_default_manage';
                $params['update_field'] = 'process_default_manage';
                $params['node_id']      = $_node_id;
                $this->addReplaceUserLog($params, $user, $replaceUser, $userInfo['user_id']);
            }
            //如果是默认办理人，替换
            if (count(DB::table('flow_process_default_user')->where('id', $_node_id)->where('user_id', $user)->get()) > 0 && count(DB::table('flow_process_default_user')->where('id', $_node_id)->where('user_id', $replaceUser)->get()) == 0) {
                DB::table('flow_process_default_user')->where('id', $_node_id)->where('user_id', $user)->update(['user_id' => $replaceUser]);
                $params                 = [];
                $params['table']        = 'flow_process_default_user';
                $params['type']         = 'process_default_user';
                $params['update_field'] = 'user_id';
                $params['node_id']      = $_node_id;
                $this->addReplaceUserLog($params, $user, $replaceUser, $userInfo['user_id']);
            }
            return true;
        }
        if ($param['type'] == 'process_copy_user') {
            //替换
            $oldInfo = DB::table($param['table'])->where($param['primary'], $param['id'])->first();
            if ($oldInfo && $oldInfo->id) {
                $_node_id = $oldInfo->id;
            } else {
                return true;
            }
            if (count(DB::table($param['table'])->where('id', $_node_id)->where('user_id', $replaceUser)->get()) == 0) {
                DB::table($param['table'])->where($param['primary'], $param['id'])->update([$param['update_field'] => $replaceUser]);
            } else {
                DB::table($param['table'])->where($param['primary'], $param['id'])->delete();
            }
            $this->addReplaceUserLog($param, $user, $replaceUser, $userInfo['user_id']);
            return true;
        }
        if ($param['type'] == 'free_create_user' || $param['type'] == 'flow_manage_user' || $param['type'] == 'flow_manage_scope_user') {
            //替换
            if (count(DB::table($param['table'])->where($param['primary'], $param['id'])->where($param['update_field'], $replaceUser)->get()) == 0) {
                DB::table($param['table'])->where($param['primary'], $param['id'])->where($param['update_field'], $user)->update([$param['update_field'] => $replaceUser]);

            } else {
                DB::table($param['table'])->where($param['primary'], $param['id'])->where($param['update_field'], $user)->delete();
            }
            $this->addReplaceUserLog($param, $user, $replaceUser, $userInfo['user_id']);
            return true;
        }
        if ($param['type'] == 'flow_sort_user' || $param['type'] == 'flow_form_sort_user') {
            //替换
            $oldInfo = DB::table($param['table'])->where($param['primary'], $param['id'])->first();
            if ($oldInfo && $oldInfo->type_id) {
                $_node_id = $oldInfo->type_id;
            } else {
                return true;
            }
            if (count(DB::table($param['table'])->where('type_id', $_node_id)->where('user_id', $replaceUser)->get()) == 0) {
                DB::table($param['table'])->where($param['primary'], $param['id'])->update([$param['update_field'] => $replaceUser]);
            } else {
                DB::table($param['table'])->where($param['primary'], $param['id'])->delete();
            }
            $this->addReplaceUserLog($param, $user, $replaceUser, $userInfo['user_id']);
            return true;
        }
        if ($param['type'] == 'flow_form_template_rule_user' || $param['type'] == 'flow_filing_template_rule_user' || $param['type'] == 'flow_print_template_rule_user') {
            //替换
            $oldInfo = DB::table($param['table'])->where($param['primary'], $param['id'])->first();
            if ($oldInfo && $oldInfo->rule_id) {
                $_node_id = $oldInfo->rule_id;
            } else {
                return true;
            }
            if (count(DB::table($param['table'])->where('rule_id', $_node_id)->where('user_id', $replaceUser)->get()) == 0) {
                DB::table($param['table'])->where($param['primary'], $param['id'])->update([$param['update_field'] => $replaceUser]);
            } else {
                DB::table($param['table'])->where($param['primary'], $param['id'])->delete();
            }
            $this->addReplaceUserLog($param, $user, $replaceUser, $userInfo['user_id']);
            return true;
        }
        if ($param['type'] == 'process_auto_get_user' || $param['type'] == 'process_auto_get_copy_user') {
            $replaceUser = str_replace($user, $replaceUser, $param['rules']);
        }
        //替换
        DB::table($param['table'])->where($param['primary'], $param['id'])->update([$param['update_field'] => $replaceUser]);
        //增加日志
        $this->addReplaceUserLog($param, $user, $replaceUser, $userInfo['user_id']);
        return true;
    }
    /**
     * 增加替换日志
     */
    public function addReplaceUserLog($data, $user, $replaceUser, $operator)
    {
        $insetData['table']        = $data['table'];
        $insetData['type']         = $data['type'];
        $insetData['user']         = $user;
        $insetData['replace_user'] = $replaceUser;
        $insetData['field']        = $data['update_field'];
        $insetData['operator']     = $operator;
        $insetData['create_time']  = date('Y-m-d H:i:s');
        if (isset($data['type_id']) && !empty($data['type_id'])) {
            $insetData['type_id'] = $data['type_id'];
        }
        if (isset($data['node_id']) && !empty($data['node_id'])) {
            $insetData['node_id'] = $data['node_id'];
        }
        if (isset($data['flow_id']) && !empty($data['flow_id'])) {
            $insetData['flow_id'] = $data['flow_id'];
        }
        if (isset($data['run_id']) && !empty($data['run_id'])) {
            $insetData['run_id'] = $data['run_id'];
        }
        DB::table('flow_user_replace_log')->insert($insetData);
        return true;
    }

}
