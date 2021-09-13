<?php

namespace App\EofficeApp\Flow\Repositories;

use App\EofficeApp\Flow\Entities\FlowRunProcessEntity;
use App\EofficeApp\Flow\Repositories\FlowRunRepository;
use Schema;
use App\EofficeApp\Flow\Entities\FlowRunStepEntity;
use App\EofficeApp\Base\BaseRepository;
use DB;

/**
 * 流程办理步骤表知识库
 *
 * @author 丁鹏
 *
 * @since  2015-10-16 创建
 */
class FlowRunStepRepository extends BaseRepository
{
    /**
     * @var \App\EofficeApp\Flow\Repositories\FlowRunRepository
     */
    private $flowRunRepository;

    /**
     * @var FlowRunProcessEntity
     */
    private $flowRunProcessEntity;

    public function __construct(
        FlowRunStepEntity $entity,
        FlowRunRepository $flowRunRepository,
        FlowRunProcessEntity $flowRunProcessEntity
    )
    {
        parent::__construct($entity);
        $this->flowRunRepository = $flowRunRepository;
        $this->flowRunProcessEntity = $flowRunProcessEntity;
    }

    /**
     * 一个基础的函数，用来根据各种条件获取flow_run_step表的数据
     *
     * @method getFlowRunStepList
     *
     * @param array $param [description]
     *
     * @return [type]                       [description]
     */
    function getFlowRunStepList($param = [])
    {
        $selectUser = isset($param['select_user']) ? $param['select_user']:  true;
        $default = [
            'fields' => ['*'],
            'page' => 0,
            'limit' => config('eoffice.pagesize'),
            'search' => [],
            'order_by' => ['flow_step_id' => 'asc'],
            'returntype' => 'object'
        ];
        $param = array_merge($default, array_filter($param));
        $query = $this->entity
            ->select($param['fields'])
            ->wheres($param['search']);
        if (empty($param['not_order_by'])) {
            $query = $query->orders($param['order_by']);
        }
        if ($param['returntype'] != 'count') {
            $query = $query->with(["flowRunStepHasOneUser" => function ($query) {
                $query->select("user_id", "user_name")->withTrashed();
            }]);
        }
        if (isset($param['run_id'])) {
            $query = $query->where('run_id', $param['run_id']);
        }
        // 解析原生 where
        if (isset($param['whereRaw'])) {
            foreach ($param['whereRaw'] as $key => $whereRaw) {
                $query = $query->whereRaw($whereRaw);
            }
        }
        // 解析原生 select
        if (isset($param['selectRaw'])) {
            foreach ($param['selectRaw'] as $key => $selectRaw) {
                $query = $query->selectRaw($selectRaw);
            }
        }
        // 返回值类型判断
        if ($param['returntype'] == 'array') {
            return $query->get()->toArray();
        } else if ($param['returntype'] == 'count') {
            if (isset($param['groupBy'])) {
               return $query->get()->count();
            } else {
               return $query->count();
            }
        } else if ($param['returntype'] == 'object') {
            return $query->get();
        } else if ($param['returntype'] == 'first') {
            if (isset($param['groupBy'])) {
               return $query->get()->first();
            } else {
               return $query->first();
            }
        }
    }

    /**
     * 更新 flow_run_Step 的数据，这里默认是多维的where条件。
     *
     * @method updateFlowRunStepData
     *
     * @param array $param [data:数据;wheres:可以批量解析的条件;whereRaw:原生解析的条件]
     *
     * @return [type]                          [description]
     */
    function updateFlowRunStepData($param = [])
    {
        $data = $param["data"];
        $query = $this->entity->wheres($param["wheres"]);
        // 解析原生 where
        if (isset($param['whereRaw'])) {
            foreach ($param['whereRaw'] as $key => $whereRaw) {
                $query = $query->whereRaw($whereRaw);
            }
        }
        return (bool)$query->update($data);
    }

    /**
     * 获取待办已办办结流程的列表
     *
     * @method getFlowRunStepList
     *
     * @param array $param [description]
     *
     * @return [type]                       [description]
     */
    function getFlowRunStepFlowList($param)
    {
        $userId = isset($param["user_id"]) ? $param["user_id"] : "";
        if ($userId == "") {
            return [];
        }
        $query = $this->entity;
        $runSeq = isset($param["search"]["run_seq"]) ? $param["search"]["run_seq"][0] : false;
        $runName = isset($param["search"]["run_name"]) ? $param["search"]["run_name"][0] : false;
        $processTime = isset($param["search"]["process_time"]) ? $param["search"]["process_time"][0] : false;
        $instancyType = isset($param["search"]["instancy_type"]) ? $param["search"]["instancy_type"][0] : false;
        $craeteTime = isset($param["search"]["create_time"]) ? $param["search"]["create_time"] : false;
        $limitDate = isset($param["search"]["limit_date"]) ? $param["search"]["limit_date"] : false;
        $acceptDate = isset($param["search"]["acceptdate"]) ? $param["search"]["acceptdate"] : false;
        $flowCreatorId = isset($param["search"]["flow_creator_id"]) ? $param["search"]["flow_creator_id"][0] : false;
        $flowCreatorDept = isset($param["search"]["flow_creator_dept"]) ? $param["search"]["flow_creator_dept"][0] : false;
        $currentStep = isset($param["search"]["current_step"]) ? $param["search"]["current_step"][0] : false;
        $title = isset($param["search"]["title"]) ? $param["search"]["title"][0] : false;
        $currentUser = isset($param["search"]["current_user"]) ? $param["search"]["current_user"][0] : false;
        $attachmentName = isset($param["search"]["attachment_name"]) ? $param["search"]["attachment_name"][0] : false;
        $hangup = isset($param["search"]["hangup"]) ? $param["search"]["hangup"][0] : false;

        if ($runSeq !== false) {
            unset($param["search"]["run_seq"]);
            $runSeq = str_replace(' ', '%', $runSeq);
            $query = $query->whereHas("flowRunStepHasOneFlowRun", function ($query) use ($runSeq) {
                $query->where('flow_run.run_seq_strip_tags', 'like', '%' . $runSeq . '%');
            });
        }
        if ($runName !== false) {
            unset($param["search"]["run_name"]);
            $query = $query->whereHas("flowRunStepHasOneFlowRun", function ($query) use ($runName) {
                $runNameSql = $this->flowRunRepository->getBlankSpaceRunName($runName);
                $query->whereRaw($runNameSql);
            });
        }
        if ($processTime !== false) {
            unset($param["search"]["process_time"]);
            if ($processTime == "unread") {
                $query = $query->whereNull('flow_run_step.process_time');
            } else if ($processTime == "read") {
                $query = $query->whereNotNull('flow_run_step.process_time');
            }
        }
        if ($instancyType !== false) {
            unset($param["search"]["instancy_type"]);
            $query = $query->whereHas("flowRunStepHasOneFlowRun", function ($query) use ($instancyType) {
                $query->where('instancy_type', $instancyType);
            });
        }
        if ($craeteTime !== false) {
            unset($param["search"]["create_time"]);
            $query = $query->whereHas("flowRunStepHasOneFlowRun", function ($query) use ($craeteTime) {
                $query->wheres(["create_time" => $craeteTime]);
            });
        }
        if ($limitDate !== false) {
            unset($param["search"]["limit_date"]);
            $query = $query->whereHas("flowRunStepHasOneFlowRun", function ($query) use ($limitDate) {
                $query->wheres(["limit_date" => $limitDate]);
            });
        }
        if ($acceptDate !== false) {
            unset($param["search"]["acceptdate"]);
            $query = $query->where(function ($query) use ($acceptDate) {
                $query->wheres(["flow_run_step.last_transact_time" => $acceptDate])
                    ->whereNotNull("flow_run_step.last_transact_time");
            });
        }
        // 判断是否按紧急程度排序，如果有则关联紧急程度表。
        if (isset($param['order_by']['instancy_type'])) {
            $param['order_by']['flow_instancys.sort'] = $param['order_by']['instancy_type'];
			$param["order_by"]['flow_run_step.flow_step_id'] = 'desc';
            unset($param['order_by']['instancy_type']);
            $query = $query->leftJoin('flow_run', 'flow_run_step.run_id', '=', 'flow_run.run_id')
                ->leftJoin('flow_instancys', 'flow_run.instancy_type', '=', 'flow_instancys.instancy_id');
        }
        if ($flowCreatorId !== false) {
            unset($param["search"]["flow_creator_id"]);
            $query = $query->whereHas("flowRunStepHasOneFlowRun", function ($query) use ($flowCreatorId) {
                $query->whereIn('flow_run.creator', $flowCreatorId);
            });
        }
        if ($flowCreatorDept !== false) {
            unset($param["search"]["flow_creator_dept"]);
            $query = $query->whereHas("flowRunStepHasOneFlowRun.flowRunHasOneUserSystemInfo", function ($query) use ($flowCreatorDept) {
                $query->whereIn('dept_id', $flowCreatorDept);
            });
        }

        if ($currentStep !== false) {
            $query = $query->whereHas("flowRunStepHasOneFlowRun", function ($query) use ($currentStep, $param) {
                if (isset($param["search"]["current_step"]["1"]) && $param["search"]["current_step"]["1"] == "!=") {
                    $query->where('current_step', "!=", $currentStep);
                } else {
                    $query->where('current_step', $currentStep);
                }
            });
            unset($param["search"]["current_step"]);
        }
        if ($title !== false) {
            $query = $query->whereHas("flowRunStepBelongsToFlowType", function ($query) use ($title, $param) {
                $query->where('flow_sort', $title);
            });
            unset($param["search"]["title"]);
        }
        if ($currentUser !== false) {
            unset($param["search"]["current_user"]);
        }
        if ($attachmentName !== false) {
            unset($param["search"]["attachment_name"]);
        }
        if (isset($param["search"]["hangup"][0]) && $param["search"]["hangup"][0] == '0') {
            unset($param["search"]["hangup"]);
        }

        if (isset($param['controlFlows']) && !empty($param['controlFlows'])) {
             $query = $query->whereNotIn('flow_run_step.flow_id' , $param['controlFlows']);
        }
        $default = [
            'fields' => ['flow_run_step.*'],
            'page' => 0,
            'limit' => config('eoffice.pagesize'),
            'search' => [],
            'order_by' => ['flow_run_step.transact_time' => 'desc'],
            'returntype' => 'array',
        ];
        $param = array_merge($default, array_filter($param));
        $flowRunStepTableColumns = array_flip($this->getTableColumns());
        if (count($param["search"])) {
            $dataSearchParams = $this->flowRunRepository->addTablePrefixByTableColumns(["data" => $param["search"], "table" => "flow_run_step", "tableColumnsFlip" => $flowRunStepTableColumns]);
            $param["search"] = $dataSearchParams;
        }

        if (isset($param['flow_module_factory']) && $param['flow_module_factory'] && isset($param['form_id'])) {
            // 关联zzzz表的数据，用于模块工厂的模块的列表字段
            if ($param['form_id'] && Schema::hasTable('zzzz_flow_data_' . $param['form_id']) && $param['returntype'] != 'count') {
                $param['fields'][] = 'zzzz_flow_data_' . $param['form_id'] . '.*';
                $query = $query->join('zzzz_flow_data_' . $param['form_id'], 'flow_run_step.run_id', '=', 'zzzz_flow_data_' . $param['form_id'] . '.run_id');
            }
        }

        $query = $query->select($param['fields'])
            ->wheres($param['search'])
            ->with(["flowRunStepHasOneUser" => function ($query) {
                $query->select("user_id", "user_name")->withTrashed();
            }])
            ->where("flow_run_step.user_id", $userId)
            ->where("flow_run_step.is_effect", '1')
            ->with(['flowRunStepHasOneFlowRun' => function ($query) {
                $query->select('run_id', 'flow_id', 'run_name', 'run_seq', 'run_seq_strip_tags', 'creator', 'max_process_id', 'current_step', 'create_time', 'transact_time', 'instancy_type')
                    ->with(['FlowRunHasOneFlowType' => function ($query) {
                        $query->select('flow_id', 'flow_name', 'flow_sort', 'flow_type', 'form_id', 'countersign', 'handle_way','press_add_hour' , 'overtime_except_nonwork')
                                ->with(['flowTypeHasOneFlowOthers' => function ($query) {
                                    $query->select('flow_id', 'first_node_delete_flow', 'flow_to_doc', 'file_folder_id', 'flow_submit_hand_remind_toggle','flow_submit_hand_overtime_toggle');
                            }])
                            ->with(["flowTypeBelongsToFlowSort" => function ($query) {
                                $query->select("title", "id");
                            }]);
                    }])
                    // 流程创建人
                    ->with(['flowRunHasOneUser' => function ($query) {
                        $query->select("user_id", "user_name")->withTrashed();
                    }])
                    // 办理步骤信息
                    ->with(['flowRunHasManyFlowRunProcess' => function ($query) {
                        $query->select("flow_run_process_id", 'run_id', 'process_id', 'sub_flow_run_ids', 'process_type', 'free_process_step', 'flow_process', 'is_back', "flow_serial", "branch_serial", "process_serial", "process_flag", "host_flag", "origin_process")
                            ->with(['flowRunProcessHasOneFlowProcess' => function ($query) {
                                $query->select('node_id', 'process_name', 'trigger_son_flow_back');
                            }])
                            ->orderBy('process_id', 'desc');
                    }]);
            }])
            // 流程所在节点的信息--暂时只有待办里面这个字段有意义
            ->with(["flowRunStepHasOneFlowProcess" => function ($query) {
                $query->select("node_id", "flow_id", "process_id", "process_name", "head_node_toggle", "end_workflow", "process_transact_type", "press_add_hour", "process_concourse", "process_copy", "process_entrust","overtime_except_nonwork");
            }]);
        // 已办事宜--高级查询--当前人员，处理
        if ($currentUser !== false) {
            $query->whereHas("flowRunStepHasOneFlowRun", function ($query) use ($currentUser) {
                $query->leftJoin('flow_run_process', function ($join) {
                    $join->on('flow_run_process.run_id', '=', 'flow_run.run_id')
                        ->on('flow_run_process.process_id', '=', 'flow_run.max_process_id');
                });
                $query->whereIn("flow_run_process.user_id", $currentUser);
            });
        }
        // 排序处理
        if (!isset($param['order_by']['create_time'])) {
            if (count($param["order_by"])) {
                if (isset($param['order_by']['flow_run.transact_time'])) {
                    $query->leftJoin('flow_run', 'flow_run_step.run_id', '=', 'flow_run.run_id')
                        ->orderBy('flow_run.transact_time', $param['order_by']['flow_run.transact_time']);
                } else {
                    $dataSearchParams = $this->flowRunRepository->addTablePrefixByTableColumns(["data" => $param["order_by"], "table" => "flow_run_step", "tableColumnsFlip" => $flowRunStepTableColumns]);
                    $param["order_by"] = $dataSearchParams;
                    // 20200316,zyx,列表上默认排序为上一节点提交时间，增加run_id逆序，保证子流程始终在主流程后面显示
                    if (!in_array('flow_run_step.run_id', $param["order_by"]) && isset($param["order_by"]['flow_run_step.last_transact_time'])) {
                        $param["order_by"]['flow_run_step.run_id'] = 'desc';
                    }
                    $query->orders($param['order_by']);
                }
            }
        } else {
            $query->addSelect('flow_run_step.transact_time as transact_time');
            $query->leftJoin('flow_run', 'flow_run_step.run_id', '=', 'flow_run.run_id')
                ->orderBy('flow_run.create_time', $param['order_by']['create_time']);
        }
        // 待办、已办、办结处理
        if (isset($param['getListType'])) {
            $getListType = $param['getListType'];
            if ($getListType == "todo") {
                $query->where("user_run_type", "1");
                if ($hangup === false) {
                    $query->where("hangup", "0");
                }
            } else if ($getListType == "already") {
                $query->where("user_run_type", "2");
            } else if ($getListType == "finished") {
                $query->where("user_run_type", "3");
            }
        }
        // 附件查询处理
        if ($attachmentName !== false) {
            // 判断`attachment_relataion_flow_run`是否存在
            if (Schema::hasTable("attachment_relataion_flow_run")) {
                $query->whereHas("flowRunStepHasOneFlowRun", function ($query) use ($attachmentName) {
                    $query->leftJoin('attachment_relataion_flow_run', 'attachment_relataion_flow_run.run_id', '=', 'flow_run.run_id')
                        ->leftJoin('attachment_rel', 'attachment_rel.attachment_id', '=', 'attachment_relataion_flow_run.attachment_id')
                        ->leftJoin('attachment_rel_search', 'attachment_rel_search.rel_id', '=', 'attachment_rel.rel_id')
                        ->where("attachment_rel_search.attachment_name", "like", '%' . $attachmentName . '%');
                });
            } else {
                return [];
            }
        }
        // 翻页判断
        $query = $query->parsePage($param['page'], $param['limit']);
        // 返回值类型判断
        if ($param["returntype"] == "array") {
            return $query->get()->toArray();
        } else if ($param["returntype"] == "count") {
            return $query->count();
        } else if ($param["returntype"] == "object") {
            return $query->get();
        } else if ($param["returntype"] == "first") {
            return $query->first();
        }
    }

    /**
     * 获取待办已办办结流程的列表数量
     *
     * @method getFlowRunStepList
     *
     * @param array $param [description]
     *
     * @return [type]                       [description]
     */
    function getFlowRunStepFlowListTotal($param = [])
    {
        $userId = isset($param["user_id"]) ? $param["user_id"] : "";
        if ($userId == "") {
            return 0;
        }
        $param["page"] = "0";
        $param["returntype"] = "count";
        return $this->getFlowRunStepFlowList($param);
    }

    /**
     * 获取流程运转步骤中离职人员数据
     *
     *
     * @param  [type]  $flowId [description]
     *
     * @return [type]          [description]
     */
    function getFlowRunQuitUserList($id)
    {
        $query = $this->entity;
        $query = $query->leftJoin('user_system_info', 'user_system_info.user_id', '=', 'flow_run_step.user_id');
        $query = $query->leftJoin('flow_run', 'flow_run.run_id', '=', 'flow_run_step.run_id');
        $query = $query->leftJoin('user', 'user.user_id', '=', 'user_system_info.user_id')
            ->select(['flow_run_step.flow_id', 'user.user_name','flow_run_step.user_run_type', 'flow_run_step.user_id', DB::raw('COUNT(flow_run_step.run_id) as count_run_process'),DB::raw('GROUP_CONCAT(flow_run.run_name) as count_run_process_all')])
            ->where("flow_run_step.flow_id", $id)->where('flow_run_step.user_run_type', '<>', 2)
            ->where("user_system_info.user_status", 2)
            ->groupBy('flow_run_step.user_id');
        return $query->get()->toArray();
    }

    function getHangupList($userId)
    {
        return $this->entity->where(["user_id" => $userId, "hangup" => 1])->get()->toArray();
    }

    function getHangupListByRunId($run_id)
    {
        $hangupList = $this->entity->where(["hangup" => 1])->where(["run_id" => $run_id])->get()->toArray();
        return $hangupList;
    }

     /**
     * 报表-流程报表-获取流程待办数量
     *
     *
     * @param  $datasourceGroupBy 分组依据; $where 数据过滤
     *
     *
     * @return array
     */
     public function getFlowToDoCountGroupByCustomType($datasourceGroupBy, $where="")
     {
        if ($datasourceGroupBy == 'user') {
                $query  = app('App\EofficeApp\User\Entities\UserEntity');
                $query  = $query->select(['user.user_name as name' , 'user.user_id'])
                                ->selectRaw("count(flow_run_step.flow_step_id) as y")
                                // ->leftJoin('flow_run_step', 'user.user_id', '=', 'flow_run_step.user_id')
                                ->leftJoin('flow_run_step' , function($join) use($where){
                                    $join->on('user.user_id' , '=' ,'flow_run_step.user_id' )
                                        ->where('flow_run_step.user_run_type' , 1)
                                        ->where('flow_run_step.is_effect' , 1)
                                        ->whereRaw('flow_run_step.deleted_at is null');
                                    //流程名称过滤
                                    if (isset($where['flowID'])) {
                                            $join->whereIn('flow_run_step.flow_id',explode(',', $where['flowID']));
                                    }
                                    //流程类型过滤
                                    if ( isset($where['flowsortId'])  && !empty($where['flowsortId'])) {
                                            $join->leftJoin('flow_type' , function($join) use($where) {
                                                  $join->on('flow_run_step.flow_id' , '=' ,'flow_type.flow_id' );
                                            });
                                            $join->whereIn('flow_type.flow_sort',$where['flowsortId']);
                                    }
                                    //创建时间过滤
                                    if (isset($where['date_range'])) {
                                           $join->leftJoin('flow_run' , function($join) {
                                                  $join->on('flow_run.run_id' , '=' ,'flow_run_step.run_id' );
                                            });
                                           //时间过滤
                                            if (isset($where['date_range'])) {
                                                $dateRange = explode(',', $where['date_range']);
                                                if (isset($dateRange[0]) && !empty($dateRange[0])) {
                                                    $join->whereRaw("flow_run.created_at >= '" . ($dateRange[0] . " 00:00:00'"));
                                                }
                                                if (isset($dateRange[1]) && !empty($dateRange[1])) {
                                                    $join->whereRaw("flow_run.created_at <= '" . ($dateRange[1] . " 23:59:59'"));
                                                }
                                            }
                                    }

                                });
                if (isset($where['userIds']) &&  is_array($where['userIds']) ) {
                    $query->whereIn('user.user_id', $where['userIds']);
                }
                $result =  $query ->orders(['y' => 'desc'])
                                  ->groupBy('user.user_id')
                                  ->get();


        } else if($datasourceGroupBy == 'userDept') {
                $query  = app('App\EofficeApp\System\Department\Entities\DepartmentEntity');
                $query  = $query->select(['department.dept_name as name' , 'user_system_info.user_id' ,'department.dept_id as dept_id'])
                                ->selectRaw("count(flow_run_step.flow_step_id) as y")
                                ->leftJoin('user_system_info' , function($join) {
                                    $join->on('user_system_info.dept_id' , '=' ,'department.dept_id' );
                                })
                                ->leftJoin('flow_run_step' , function($join) use($where){
                                    $join->on('user_system_info.user_id' , '=' ,'flow_run_step.user_id' )
                                        ->where('flow_run_step.user_run_type' , 1)
                                        ->where('flow_run_step.is_effect' , 1)
                                        ->whereRaw('flow_run_step.deleted_at is null');
                                    //流程名称过滤
                                    if (isset($where['flowID']) ) {
                                            $join->whereIn('flow_run_step.flow_id',explode(',', $where['flowID']));
                                    }
                                    if (isset($where['date_range'])) {
                                           $join->leftJoin('flow_run' , function($join) {
                                                  $join->on('flow_run.run_id' , '=' ,'flow_run_step.run_id' );
                                            });
                                            $dateRange = explode(',', $where['date_range']);
                                            if (isset($dateRange[0]) && !empty($dateRange[0])) {
                                                $join->whereRaw("flow_run.created_at >= '" . ($dateRange[0] . " 00:00:00'"));
                                            }
                                            if (isset($dateRange[1]) && !empty($dateRange[1])) {
                                                $join->whereRaw("flow_run.created_at <= '" . ($dateRange[1] . " 23:59:59'"));
                                            }
                                    }
                                    if (isset($where['userIds']) &&  is_array($where['userIds']) ) {
                                        $join->whereIn('flow_run_step.user_id', $where['userIds']);
                                    }
                                    //流程类型过滤
                                    if ( isset($where['flowsortId'])  && !empty($where['flowsortId'])) {
                                            $join->leftJoin('flow_type' , function($join) use($where) {
                                                  $join->on('flow_run_step.flow_id' , '=' ,'flow_type.flow_id' );
                                            });
                                            $join->whereIn('flow_type.flow_sort',$where['flowsortId']);
                                    }
                                });
                if (isset($where['dept_id']) && !empty($where['dept_id'])) {
                    $query->whereIn('department.dept_id', explode(',', $where['dept_id']));
                }
                $result =  $query ->orders(['y' => 'desc'])
                                  ->groupBy('department.dept_id')
                                  ->get();

        }else if($datasourceGroupBy == 'userRole') {
                $query  = app('App\EofficeApp\Role\Entities\RoleEntity');
                $query  = $query->select(['role.role_name as name' , 'user_role.user_id'])
                                ->selectRaw("count(flow_run_step.flow_step_id) as y")
                                ->leftJoin('user_role' , function($join) {
                                    $join->on('user_role.role_id' , '=' ,'role.role_id' );
                                })
                                ->leftJoin('flow_run_step' , function($join) use($where) {
                                    $join->on('user_role.user_id' , '=' ,'flow_run_step.user_id' )
                                        ->where('flow_run_step.user_run_type' , 1)
                                        ->where('flow_run_step.is_effect' , 1)
                                        ->whereRaw('flow_run_step.deleted_at is null');
                                    //流程名称过滤
                                    if (isset($where['flowID'])  ) {
                                            $join->whereIn('flow_run_step.flow_id',explode(',', $where['flowID']));
                                    }
                                    if (isset($where['date_range'])) {
                                           $join->leftJoin('flow_run' , function($join) {
                                                  $join->on('flow_run.run_id' , '=' ,'flow_run_step.run_id' );
                                            });
                                            $dateRange = explode(',', $where['date_range']);
                                            if (isset($dateRange[0]) && !empty($dateRange[0])) {
                                                $join->whereRaw("flow_run.created_at >= '" . ($dateRange[0] . " 00:00:00'"));
                                            }
                                            if (isset($dateRange[1]) && !empty($dateRange[1])) {
                                                $join->whereRaw("flow_run.created_at <= '" . ($dateRange[1] . " 23:59:59'"));
                                            }
                                    }
                                    if (isset($where['userIds']) &&  is_array($where['userIds'])  ) {
                                        $join->whereIn('flow_run_step.user_id', $where['userIds']);
                                    }
                                    //流程类型过滤
                                    if ( isset($where['flowsortId']) && !empty($where['flowsortId'])  ) {
                                            $join->leftJoin('flow_type' , function($join) use($where) {
                                                  $join->on('flow_run_step.flow_id' , '=' ,'flow_type.flow_id' );
                                            });
                                            $join->whereIn('flow_type.flow_sort',$where['flowsortId']);
                                    }
                                });
                if (isset($where['role_id']) && !empty($where['role_id'])) {
                    $query->whereIn('role.role_id',  explode(',', $where['role_id']));
                }
                $result =  $query ->orders(['y' => 'desc'])
                                  ->groupBy('role.role_id')
                                  ->get();
        } else {
             $result = [];
        }

        if(!empty($result)) {
                return $result->toArray();
        } else {
                return array();
        }
     }


     function getFlowRunStepInfo($stepId,$flowRunProcessId = null)
     {
     	$result = [];
     	if (empty($flowRunProcessId)) {
     		if (empty($stepId)) {
     			return [];
     		}
     		$flowRunStepInfo = $this->entity->where(["flow_step_id" => $stepId])->get();
     		if (!empty($flowRunStepInfo)){
     			$flowRunStepInfo = $flowRunStepInfo->toArray();
     			if (isset($flowRunStepInfo[0])) {
     				$flowRunStepInfo = $flowRunStepInfo[0];
     			}
     		} else {
     			$flowRunStepInfo = [];
     		}
     		return $flowRunStepInfo;
     	} else {
			$flowRunProcessInfo = $this->flowRunProcessEntity->find($flowRunProcessId);
			if (!empty($flowRunProcessInfo)) {
				$flowRunProcessInfo = $flowRunProcessInfo->toArray();
				$where = ["run_id" => $flowRunProcessInfo['run_id'],"user_id" => $flowRunProcessInfo['user_id'],"flow_process" => $flowRunProcessInfo['flow_process'],"process_id" => $flowRunProcessInfo['process_id'],"host_flag" => $flowRunProcessInfo['host_flag']];
				$flowRunStepInfo = $this->entity->where($where)->get();
				if (!empty($flowRunStepInfo)){
					$flowRunStepInfo = $flowRunStepInfo->toArray();
					if (isset($flowRunStepInfo[0])) {
						$flowRunStepInfo = $flowRunStepInfo[0];
					}
				} else {
					$flowRunStepInfo = [];
				}
				return $flowRunStepInfo;
			}
     	}
     	return [];
     }
}
