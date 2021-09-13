<?php

namespace App\EofficeApp\Flow\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Flow\Entities\FlowRunEntity;
use App\EofficeApp\Flow\Repositories\FlowTypeRepository;
// use App\EofficeApp\Flow\Repositories\FlowRunProcessAgencyDetailRepository;
// use App\EofficeApp\Flow\Repositories\FlowRunProcessRepository;
// use App\EofficeApp\Flow\Repositories\FlowCopyRepository;
use DB;
use Schema;

/**
 * 流程运行表知识库
 *
 * @author 丁鹏
 *
 * @since  2015-10-16 创建
 */
class FlowRunRepository extends BaseRepository
{
    public function __construct(
        FlowRunEntity $entity,
        FlowTypeRepository $flowTypeRepository
        // FlowRunProcessAgencyDetailRepository $flowRunProcessAgencyDetailRepository,
        // FlowRunProcessRepository $flowRunProcessRepository,
        // FlowCopyRepository $flowCopyRepository
    )
    {
        parent::__construct($entity);
        $this->flowTypeRepository = $flowTypeRepository;
        // $this->flowRunProcessAgencyDetailRepository = $flowRunProcessAgencyDetailRepository;
        // $this->flowRunProcessRepository = $flowRunProcessRepository;
        // $this->flowCopyRepository = $flowCopyRepository;
    }

    /**
     * 一个基础的函数，用来根据各种条件获取 flow_run 表的数据
     *
     * @method getFlowRunProcessList
     *
     * @param array $param [description]
     *
     * @return [type]                       [description]
     */
    public function getFlowRunList($param = [])
    {
        $selectUser = isset($param['select_user']) ? $param['select_user']:  true;
        $default = [
            'fields' => ['*'],
            'page' => 0,
            'limit' => config('eoffice.pagesize'),
            'search' => [],
            'order_by' => ['run_id' => 'desc'],
            'returntype' => 'object',
        ];
        $param = array_merge($default, array_filter($param));
        $query = $this->entity
            ->select($param['fields'])
            ->wheres($param['search'])
            ->orders($param['order_by'])
            ->with("FlowRunHasOneFlowType");
        if ($selectUser) {
            $query = $query->with(["flowRunHasOneUser" => function($query){
                            $query->select("user_id","user_name")->withTrashed();
                      }]);
        }
        // 分组参数
        if (isset($param['groupBy'])) {
            $query = $query->groupBy($param['groupBy']);
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
         // ip控制
        if (isset($param['controlFlows']) && !empty($param['controlFlows'])) {
             $query = $query->whereNotIn('flow_id' , $param['controlFlows']);
        }
        if (isset($param['withTrashed']) && $param['withTrashed']) {
            $query->withTrashed();
        }
        // 翻页判断
        $query = $query->parsePage($param['page'], $param['limit']);
        // 返回值类型判断
        if ($param["returntype"] == "array") {
            return $query->get()->toArray();
        } else if ($param["returntype"] == "count") {
            if (isset($param['groupBy'])) {
               return $query->get()->count();
            } else {
               return $query->count();
            }
        } else if ($param["returntype"] == "object") {
            return $query->get();
        } else if ($param["returntype"] == "first") {
            if (isset($param['groupBy'])) {
               return $query->get()->first();
            } else {
               return $query->first();
            }
        }
    }

    /**
     * 获取流程【流程查询】的列表数量
     *
     * @method getFlowRunListTotal
     *
     * @param array $param [description]
     *
     * @return [type]                                [description]
     */
    public function getFlowRunListTotal($param = [])
    {
        $param["page"] = 0;
        $param["returntype"] = "count";
        return $this->getFlowRunList($param);
    }

    /**
     * 获取某个流程的当前用户创建的最新的5条流程，供历史数据导入;带查询
     *
     * @param array $param 传入的参数
     *
     * @return array            流程数据
     * @since  2015-10-16       创建
     *
     * @author 丁鹏
     *
     */
    public function flowNewIndexCreateHistoryListRepository($param)
    {
        $userId = isset($param["user_id"]) ? $param["user_id"] : "";
        if ($userId == "") {
            return $this->entity;
        }
        $runName = isset($param["run_name"])? $param["run_name"] : false;
        $query = $this->entity
            ->select('*')
            ->where('flow_id', $param['flow_id']);
        if (isset($param['search'])) {
            // 手机端搜索历史流程
            $param["search"] = json_decode($param['search'] , true);
            $runName = isset($param["search"]["run_name"]) ? $param["search"]["run_name"][0] : false;
        }
        if ( $runName !== false) {
            $runName = str_replace(' ', '%', $runName);
            $query = $query->where('run_name', 'like', '%' . $runName . '%');
        }
        $query = $query->where('creator', $userId)
            ->orderBy('run_id', 'desc')
            ->forPage(0, 20);
        return $query->get()->toArray();
    }

    /**
     * 查flow_run里，当前flow_id的，最大run_id的流程信息
     *
     * @method getMaxRunIdFlowData
     *
     * @param array $param [description]
     *
     * @return [type]                     [description]
     */
    public function getMaxRunIdFlowDataRepository($param = [])
    {
        $query = $this->entity
            ->select('*')
            ->where('flow_id', $param['flow_id'])
            ->orderBy('run_id', 'desc')
            // 20171101，修改新建流水号规则
            // ->orders(['run_seq_strip_tags'=>'desc','run_id'=>'desc'])
            ->where('run_seq', '!=', '')
            // 20171101，修改新建流水号规则--end
            ->with(['flowRunHasOneUser' => function ($query) {
                $query->select('user_id', 'user_name')->withTrashed();
            }]);
        return $query->first();
    }

    /**
     * 根据子流程id获取相关运行流程信息
     *
     * @method getFlowSubflowRunList
     *
     * @param array $param [description]
     *
     * @return [type]                       [description]
     */
    public function getFlowSubflowRunList($param = [])
    {
        $query = $this->entity
            ->select('*')
            ->whereIn('run_id', $param)
            ->with(['flowRunHasOneUser' => function ($query) {
                $query->select('user_id', 'user_name');
            }]);
        return $query->get();
    }

    /**
     * 获取流程【我的请求】的列表
     *
     * @method getFlowRunProcessMyRequestList
     *
     * @param array $param [description]
     *
     * @return [type]                                [description]
     */
    public function getFlowRunMyRequestList($param = [])
    {
        $userId = isset($param["user_id"]) ? $param["user_id"] : "";
        if ($userId == "") {
            return $this->entity;
        }
        $query = $this->entity;
        $attachmentName = isset($param["search"]["attachment_name"]) ? $param["search"]["attachment_name"][0] : false;
        $flowSort = isset($param["search"]["flow_sort"]) ? $param["search"]["flow_sort"][0] : false;
        if ($attachmentName !== false) {
            unset($param["search"]["attachment_name"]);
        }
        if ($flowSort !== false) {
            unset($param["search"]["flow_sort"]);
            $query = $query->whereHas("FlowRunHasOneFlowType", function ($query) use ($flowSort) {
                $query->where('flow_sort', $flowSort);
            });
        }
        $default = [
            'fields' => ['*'],
            'page' => 0,
            'limit' => config('eoffice.pagesize'),
            'search' => [],
            'order_by' => ['flow_run.transact_time' => 'desc'],
            'returntype' => 'array',
        ];
        $param = array_merge($default, array_filter($param));
        // 查询条件处理
        $flowRunTableColumns = array_flip($this->getTableColumns());
        if (count($param["search"])) {
            $dataSearchParams = $this->addTablePrefixByTableColumns(["data" => $param["search"], "table" => "flow_run", "tableColumnsFlip" => $flowRunTableColumns]);
            $param["search"] = $dataSearchParams;
            if (isset($param["search"]["flow_run.run_name"]) &&  is_array($param["search"]["flow_run.run_name"]) && !empty($param["search"]["flow_run.run_name"])) {
                $runNameSql = $this->getBlankSpaceRunName($param["search"]["flow_run.run_name"][0]);
                $query = $query->whereRaw($runNameSql);
                unset($param["search"]["flow_run.run_name"]);
            }
        }

        if (!empty($param["search"]["flow_run.run_seq"])) {
            $param['search']['run_seq_strip_tags'] = $param["search"]["flow_run.run_seq"];
            unset($param["search"]["flow_run.run_seq"]);
        } else if (!empty($param["search"]["run_seq"])) {
            $param['search']['run_seq_strip_tags'] = $param["search"]["run_seq"];
            unset($param["search"]["run_seq"]);
        }
        if (isset($param['search']['run_seq_strip_tags'][0])) {
                $param['search']['run_seq_strip_tags'][0] = str_replace(' ', '%', $param['search']['run_seq_strip_tags'][0]);
        }
        if (isset($param['controlFlows']) && !empty($param['controlFlows'])) {
             $query = $query->whereNotIn('flow_run.flow_id' , $param['controlFlows']);
        }

        // 排序处理
        if (count($param["order_by"])) {
            $dataSearchParams = $this->addTablePrefixByTableColumns(["data" => $param["order_by"], "table" => "flow_run", "tableColumnsFlip" => $flowRunTableColumns]);
            $param["order_by"] = $dataSearchParams;

            //处理紧急程度排序
            $param = $this->handleInstanysSortParam($param);
        }
        $query = $query
            ->orders($param['order_by'])
            ->select($param['fields'])
            ->wheres($param['search'])
            ->where('is_effect', '1')
            ->where("flow_run.creator", $userId)
            // ->with(["FlowRunHasOneFlowType.flowTypeHasOneFlowOthers" => function($query){
            //     $query->select("flow_id","lable_show_default");
            // }])
            // ->has("flowRunHasManyFlowRunProcess")
            ->with("FlowRunHasOneFlowType")
            ->with(["flowRunHasManyFlowRunProcess" => function ($query) {
                $query->select("flow_run_process_id", "run_id", "user_id", "process_id",
                    "flow_process", "host_flag", "process_flag", "process_time", "saveform_time", "deliver_time", "by_agent_id", "sub_flow_run_ids", 'process_type', 'free_process_step', 'flow_process', 'is_back', "flow_serial", "branch_serial", "process_serial", "origin_process", 'user_run_type', 'user_last_step_flag', "flow_id", "outflow_process", "origin_process_id")
                    ->orders(['process_id' => 'desc', 'host_flag' => 'desc'])
                    ->with(['flowRunProcessHasOneUser' => function ($query) {
                        $query->select('user_id', 'user_name')->with([
                            'userHasOneSystemInfo' => function ($query) {
                                $query->select('user_id', 'user_status')->withTrashed();
                            }
                        ])->withTrashed();
                    }])->with(["flowRunProcessHasOneFlowProcess" => function ($query) {
                        $query->select('node_id', 'process_name', 'head_node_toggle', 'process_entrust', 'trigger_son_flow_back', 'merge');
                    }]);
            }])
            ->with("flowRunHasManyFlowRunStep")
            // ->with(["flowRunHasManyFlowProcess" => function($query){
            //       $query->select("process_name","process_id","flow_id");
            //   }])
            ->with(["flowRunHasManyFlowRunProcessRelateCurrentUser" => function ($query) use ($userId) {
                $query->where("user_id", $userId);
            }]);
        // 附件查询处理
        if ($attachmentName !== false) {
            // 判断`attachment_relataion_flow_run`是否存在
            if (Schema::hasTable("attachment_relataion_flow_run")) {
                $query = $query->whereExists(function ($query) use ($attachmentName) {
                    $query->from('attachment_relataion_flow_run')
                        ->whereRaw('attachment_relataion_flow_run.run_id = flow_run.run_id')
                        ->where("attachment_rel_search.attachment_name", "like", '%' . $attachmentName . '%')
                        ->leftJoin('attachment_rel', 'attachment_rel.attachment_id', '=', 'attachment_relataion_flow_run.attachment_id')
                        ->leftJoin('attachment_rel_search', 'attachment_rel.rel_id', '=', 'attachment_rel_search.rel_id');
                });
            } else {
                return [];
            }
        }
        if (isset($param['flow_module_factory']) && $param['flow_module_factory'] && isset($param['form_id'])) {
            // 关联zzzz表的数据，用于模块工厂的模块的列表字段
            if ($param['form_id'] && Schema::hasTable('zzzz_flow_data_' . $param['form_id'])) {
                $query = $query->join('zzzz_flow_data_' . $param['form_id'], 'flow_run.run_id', '=', 'zzzz_flow_data_' . $param['form_id'] . '.run_id');
            }
        }
        //紧急程度排序
        $query = $this->joinFlowInstancysQuery($query, $param);
        // 翻页判断
        $query = $query->parsePage($param['page'], $param['limit']);
        // 返回值类型判断
        if ($param["returntype"] == "array") {
            return $query->get()->toArray();
        } else if ($param["returntype"] == "count") {
            return $query->count();
        } else if ($param["returntype"] == "object") {
            return $query->get();
        }
    }

    /**
     * 获取流程【我的请求】的列表数量
     *
     * @method getFlowRunProcessMyRequestList
     *
     * @param array $param [description]
     *
     * @return [type]                                [description]
     */
    public function getFlowRunMyRequestListTotal($param = [])
    {
        $userId = isset($param["user_id"]) ? $param["user_id"] : "";
        if ($userId == "") {
            return 0;
        }
        $param["page"] = "0";
        $param["returntype"] = "count";
        return $this->getFlowRunMyRequestList($param);
    }

    /**
     * 获取流程【流程监控】的列表
     *
     * @method getFlowRunProcessMyRequestList
     *
     * @param array $param [description]
     *
     * @return [type]                                [description]
     */
    public function getFlowRunMonitorList($param = [])
    {
        $userId = isset($param["user_id"]) ? $param["user_id"] : "";
        if ($userId == "") {
            return $this->entity;
        }
        $query = $this->entity;
        $flowSort = isset($param["search"]["flow_sort"]) ? $param["search"]["flow_sort"][0] : false;
        $runName = isset($param["search"]["run_name"]) ? $param["search"]["run_name"][0] : false;
        $flowCreatorDept = isset($param["search"]["flow_creator_dept"]) ? $param["search"]["flow_creator_dept"][0] : false;
        $unhandleUser = isset($param["search"]["unhandle_user"]) ? $param["search"]["unhandle_user"][0] : false;
        $attachmentName = isset($param["search"]["attachment_name"]) ? $param["search"]["attachment_name"][0] : false;
        $monitorParams = isset($param['monitor_params']) ? $param['monitor_params'] : false;

        if ($monitorParams !== false) {
            unset($param['monitor_params']);
            if (!empty($monitorParams)) {
                $query = $query->where(function ($query) use ($monitorParams) {
                    $flowIdArray = [];
                    foreach ($monitorParams as $key => $value) {
                        if (isset($value['user_id']) && $value['user_id'] == 'all') {
                            $flowIdArray[] = $value['flow_id'];
                        }
                        $query = $query->orWhere(function ($query) use ($value) {
                            if (isset($value['flow_id']) && !empty($value['flow_id'])) {
                                if (isset($value['user_id']) && !empty($value['user_id'])) {
                                    if ($value['user_id'] != 'all') {
                                        $query = $query->where('flow_run.flow_id', $value['flow_id'])
                                            ->whereIn('flow_run.creator', $value['user_id']);
                                    }
                                } else {
                                    $query = $query->where('flow_run.flow_id', $value['flow_id'])
                                        ->whereNull('flow_run.creator');
                                }
                            }
                        });
                    }
                    if (!empty($flowIdArray)) {
                        $qeury = $query->orWhereIn('flow_run.flow_id', $flowIdArray);
                    }
                });
            }
        }

        if (isset($param["search"]["flow_id"])) {
            // 连表flow_run_process查询，flow_id两表都存在，这里特殊处理下
            $param["search"]["flow_run.flow_id"] = $param["search"]["flow_id"];
            unset($param["search"]["flow_id"]);
        }
        if ($flowSort !== false) {
            unset($param["search"]["flow_sort"]);
            $query = $query->whereHas("FlowRunHasOneFlowType", function ($query) use ($flowSort) {
                $query->where('flow_sort', $flowSort);
            });
        }
        if ($runName !== false) {
            $runNameSql = $this->getBlankSpaceRunName($runName);
            $query = $query->whereRaw($runNameSql);
            unset($param['search']['run_name']);
        }
        if ($flowCreatorDept !== false) {
            unset($param["search"]["flow_creator_dept"]);
            $query = $query->whereHas("flowRunHasOneUserSystemInfo", function ($query) use ($flowCreatorDept) {
                $query->whereIn('dept_id', $flowCreatorDept);
            });
        }
        if (isset($param['search']['creator'])) {
            $param['search']['flow_run.creator'] = $param['search']['creator'];
            unset($param['search']['creator']);
        }
        if (isset($param['controlFlows']) && !empty($param['controlFlows'])) {
            $query->whereNotIn('flow_run.flow_id' , $param['controlFlows']);
        }
        if ($unhandleUser !== false) {
            unset($param["search"]["unhandle_user"]);
        }
        if ($attachmentName !== false) {
            unset($param["search"]["attachment_name"]);
        }
        $default = [
            'fields' => ['flow_run.*'],
            'page' => 0,
            'limit' => config('eoffice.pagesize'),
            'search' => [],
            'order_by' => ['flow_run.transact_time' => 'desc'],
            'returntype' => 'array',
        ];
        $param = array_merge($default, array_filter($param));
        $param = $this->handleInstanysSortParam($param);

        if (!empty($param["search"]["flow_run.run_seq"])) {
            $param['search']['run_seq_strip_tags'] = $param["search"]["flow_run.run_seq"];
            unset($param["search"]["flow_run.run_seq"]);
        } else if (!empty($param["search"]["run_seq"])) {
            $param['search']['run_seq_strip_tags'] = $param["search"]["run_seq"];
            unset($param["search"]["run_seq"]);
        }
        if (isset($param['search']['run_seq_strip_tags'][0])) {
            $param['search']['run_seq_strip_tags'][0] = str_replace(' ', '%', $param['search']['run_seq_strip_tags'][0]);
        }

        $query = $query
            ->orders($param['order_by'])
            ->select($param['fields'])
            ->wheres($param['search'])
            ->where('flow_run.is_effect', '1')
            ->where("current_step", "!=", "0")
            // ->has("flowRunHasManyFlowRunProcess")
            ->with(["flowRunHasManyFlowRunProcess" => function ($query) {
                $query->select('flow_run_process_id', 'process_id', 'run_id', 'flow_id',
                    'branch_serial', 'flow_serial', 'process_serial', 'process_type',
                    'user_id', 'by_agent_id', 'host_flag', 'process_time', 'free_process_step',
                    'flow_process', 'is_back', 'send_back_user', 'send_back_process', 'send_back_free_step', 'sub_flow_run_ids', 'process_flag', 'origin_process',
                    'user_run_type', 'outflow_process', 'user_last_step_flag', "origin_process_id", 'saveform_time')->with(["flowRunProcessHasOneUser" => function ($query) {
                        $query->select("user_id", "user_name")->with([
                            "userHasOneSystemInfo" => function ($query) {
                                $query->select("user_id", "user_status")->withTrashed();
                            }
                        ])->withTrashed();
                    }])
                    // ->with(["flowRunProcessHasOneUserSystemInfo" => function ($query) {
                    //     $query->select("user_id", "user_status")->withTrashed();
                    // }])
                    ->orders(['process_id' => 'desc', 'host_flag' => 'desc'])
                    ->with(["flowRunProcessHasOneFlowProcess" => function ($query) {
                        $query->select('node_id', 'process_name', "end_workflow", "head_node_toggle", "trigger_son_flow_back", "concurrent", "merge" , "branch");
                    }])->with("flowRunProcessHasOneFlowProcessFree")->with(["flowRunProcessHasManyAgencyDetail" => function ($query) {
                        $query->select('flow_run_process_id', 'user_id', 'by_agency_id');
                    }]);

            }])
            ->with(["FlowRunHasOneFlowType" => function ($query) {
                $query->select('flow_id', 'flow_name', 'form_id', 'flow_type', 'allow_monitor')->where("hide_running", "=", '0');
            }, "FlowRunHasOneFlowType.flowTypeHasOneFlowOthers" => function ($query) {
                $query->select("flow_id", "flow_to_doc", "file_folder_id", "flow_send_back_submit_method", "alow_select_handle", "flow_send_back_required", "first_node_delete_flow", "flow_submit_hand_remind_toggle");
            }])
            ->with(["flowRunHasOneUser" => function ($query) {
                $query->select("user_id", "user_name")->withTrashed();
            }]);
        // 附件查询处理
        if ($attachmentName !== false) {
            // 判断`attachment_relataion_flow_run`是否存在
            if (Schema::hasTable("attachment_relataion_flow_run")) {
                $query = $query->whereExists(function ($query) use ($attachmentName) {
                    $query->from('attachment_relataion_flow_run')
                        ->whereRaw('attachment_relataion_flow_run.run_id = flow_run.run_id')
                        ->where("attachment_rel_search.attachment_name", "like", '%' . $attachmentName . '%')
                        ->leftJoin('attachment_rel', 'attachment_rel.attachment_id', '=', 'attachment_relataion_flow_run.attachment_id')
                        ->leftJoin('attachment_rel_search', 'attachment_rel.rel_id', '=', 'attachment_rel_search.rel_id');
                });
            } else {
                return [];
            }
        }
        // 流程监控--高级查询--未办理人员，处理
        if ($unhandleUser !== false) {
            $query->leftJoin('flow_run_process', function ($join) {
                $join->on('flow_run_process.run_id', '=', 'flow_run.run_id')
                    ->on('flow_run_process.process_id', '=', 'flow_run.max_process_id');
            });
            $query->whereIn("flow_run_process.user_id", $unhandleUser)
                ->where(function ($query) {
                    $query->where(function ($query) {
                        $query->orWhere("deliver_time", null)->orWhere("deliver_time", '0000-00-00 00:00:00');
                    })
                        ->where(function ($query) {
                            $query->orWhere("saveform_time", null)->orWhere("saveform_time", '0000-00-00 00:00:00');
                        });
                });
        }
        $query = $this->joinFlowInstancysQuery($query, $param);
        // 翻页判断
        $query = $query->parsePage($param['page'], $param['limit']);
        // 返回值类型判断
        if ($param["returntype"] == "array") {
            return $query->get()->toArray();
        } else if ($param["returntype"] == "count") {
            return $query->count();
        } else if ($param["returntype"] == "object") {
            return $query->get();
        }
    }

    /**
     * 获取流程【流程监控】的列表数量
     *
     * @method getFlowRunProcessMyRequestList
     *
     * @param array $param [description]
     *
     * @return [type]                                [description]
     */
    public function getFlowRunMonitorListTotal($param = [])
    {
        $userId = isset($param["user_id"]) ? $param["user_id"] : "";
        if ($userId == "") {
            return 0;
        }
        $param["page"] = "0";
        $param["returntype"] = "count";
        return $this->getFlowRunMonitorList($param);
    }

    /**
     * 获取流程【流程查询】的列表
     *
     * @method getFlowRunProcessMyRequestList
     *
     * @param array $param [description]
     *
     * @return [type]                                [description]
     */
    public function getFlowRunFlowSearchList($param = [])
    {
        $userId = isset($param["user_id"]) ? $param["user_id"] : "";
        if ($userId == "") {
            return null;
        }
        $query = $this->entity;
        $flowId = isset($param['search']['flow_id']) && !empty($param['search']['flow_id']) ? $param['search']['flow_id'][0] : false;
        $runName = isset($param['search']['run_name']) && !empty($param['search']['run_name']) ? $param['search']['run_name'] : false;
        $userId = isset($param['search']['user_id']) && !empty($param['search']['user_id']) ? $param['search']['user_id'] : $userId;
        $createTime      = isset($param["search"]["create_time"]) ? $param["search"]["create_time"] : false;
        $createDate1     = isset($param["search"]["create_date1"]) ? $param["search"]["create_date1"][0] : false;//增加流程创建时间条件
        $createDate2     = isset($param["search"]["create_date2"]) ? $param["search"]["create_date2"][0] : false;//增加流程创建时间条件
        $flowName = isset($param["search"]["flow_name"]) ? $param["search"]["flow_name"][0] : false;
        $flowSort = isset($param["search"]["flow_sort"]) ? $param["search"]["flow_sort"][0] : false;
        $startDate1 = isset($param["search"]["start_date1"]) ? $param["search"]["start_date1"][0] : false;
        $startDate2 = isset($param["search"]["start_date2"]) ? $param["search"]["start_date2"][0] : false;
        $endDate1 = isset($param["search"]["end_date1"]) ? $param["search"]["end_date1"][0] : false;
        $endDate2 = isset($param["search"]["end_date2"]) ? $param["search"]["end_date2"][0] : false;
        $done_date = isset($param["search"]["done_date"]) ? $param["search"]["done_date"] : false;
        $flowCreatorId = isset($param["search"]["flow_creator_id"]) ? $param["search"]["flow_creator_id"][0] : false;
        $flowCreatorDept = isset($param["search"]["flow_creator_dept"]) ? $param["search"]["flow_creator_dept"][0] : false;
        $hostFlag = isset($param["search"]["host_flag"]) ? $param["search"]["host_flag"][0] : false;
        $transactFlag = isset($param["search"]["transact_flag"]) ? $param["search"]["transact_flag"][0] : false;
        $runId = isset($param["search"]["run_id"]) ? $param["search"]["run_id"][0] : false;
        $nodeId = isset($param['search']['node_id']) ? $param['search']['node_id'][0] : false;
        $monitorParams = isset($param['monitor_params']) && !empty($param['monitor_params']) ? $param['monitor_params'] : false;

        if ($createTime !== false) {//（mobile端）
            unset($param["search"]["create_time"]);
            $query = $query->wheres(["flow_run.create_time" => $createTime]);
        }
        if ($flowId) {
            $param['search']['flow_run.flow_id'] = $param['search']['flow_id'];
            unset($param['search']['flow_id']);
        }
        if ($runName) {
            $runNameSql = $this->getBlankSpaceRunName($runName[0]);
            $query = $query->whereRaw($runNameSql);
            unset($param['search']['run_name']);
        }
        //20190918,zyx,增加create_date参数（web端）
        if ($createDate1 !== false) {
            unset($param["search"]["create_date1"]);
            $query = $query->where("flow_run.create_time" , '>=' , $createDate1);
        }
        if ($createDate2 !== false) {
            unset($param["search"]["create_date2"]);
            $time_stamp = strtotime($createDate2);
            $create_time = date("Y-m-d", mktime(0, 0, 0, date("m", $time_stamp), date("d", $time_stamp) + 1, date("Y", $time_stamp)));
            $query = $query->where("flow_run.create_time" , '<' , $create_time);
        }

        if ($nodeId !== false) {
            unset($param['search']['node_id']);
            array_push($nodeId, 0); // hack 方法，2019/11/04 已结束的流程会将current_step置为0，导致按节点查询搜索不到已结束的方法（此处建议重构）
            $query = $query->whereIn('flow_run.current_step', $nodeId);
        }
        if ($flowName !== false) {
            unset($param["search"]["flow_name"]);
            $query = $query->whereHas("FlowRunHasOneFlowType", function ($query) use ($flowName) {
                $query->where('flow_name', 'like', '%' . $flowName . '%');
            });
        }
        if ($flowSort !== false) {
            unset($param["search"]["flow_sort"]);
            $query = $query->whereHas("FlowRunHasOneFlowType", function ($query) use ($flowSort) {
                $query->where('flow_sort', $flowSort);
            });
        }
        //20190918,此处的start时间其实是过滤的流程创建时间,新的逻辑将其与create_time流程创建时间区分开
        if ($startDate1 !== false) {
            unset($param["search"]["start_date1"]);
//            $query = $query->whereRaw("flow_run.create_time >= '" . $startDate1 . "'");
            $query = $query->where('flow_run.first_transact_time' , '>=' , $startDate1);// 有流程开始时间条件的，则表明流程已经提交，节点号至少为2
        }
        if ($startDate2 !== false) {
            unset($param["search"]["start_date2"]);
            $time_stamp = strtotime($startDate2);
            $start_time = date("Y-m-d", mktime(0, 0, 0, date("m", $time_stamp), date("d", $time_stamp) + 1, date("Y", $time_stamp)));
//            $query      = $query->whereRaw("flow_run.create_time < '" . $start_time . "'");
            $query = $query->whereRaw("(flow_run.first_transact_time < '$start_time')");// 有流程开始时间条件的，则表明流程已经提交，节点号至少为2
        }
        if ($endDate1 !== false) {
            unset($param["search"]["end_date1"]);
            $query = $query->where('flow_run.current_step'  , 0)->where( "flow_run.transact_time" , ">=", $endDate1);
        }
        if ($endDate2 !== false) {
            unset($param["search"]["end_date2"]);
            $time_stamp_end = strtotime($endDate2);
            $start_time_end = date("Y-m-d", mktime(0, 0, 0, date("m", $time_stamp_end), date("d", $time_stamp_end) + 1, date("Y", $time_stamp_end)));
            $query = $query->whereRaw("(flow_run.current_step = 0 AND flow_run.transact_time < '" . $start_time_end . "')");
        }
        if ($done_date !== false) {
            unset($param["search"]["done_date"]);
            $query = $query->whereHas("flowRunHasManyFlowRunProcess", function ($query) use ($done_date, $userId) {
                $startdone_date = $done_date . " 00:00:00";
                $enddone_date = $done_date . " 23:59:59";
                $query->whereBetween('flow_run_process.transact_time',[$startdone_date,$enddone_date])->whereRaw(" (flow_run_process.user_id='" . $userId . "'  or  flow_run_process.by_agent_id='" . $userId . "') ");
            });
        }
        if ($flowCreatorId !== false) {
            unset($param["search"]["flow_creator_id"]);
            $query = $query->whereIn('flow_run.creator', $flowCreatorId);
        }
        if (isset($param['search']['creator'])) {
            $param['search']['flow_run.creator'] = $param['search']['creator'];
            unset($param['search']['creator']);
        }
        if ($flowCreatorDept !== false) {
            unset($param["search"]["flow_creator_dept"]);
            $query = $query->whereHas("flowRunHasOneUserSystemInfo", function ($query) use ($flowCreatorDept) {
                $query->whereIn('dept_id', $flowCreatorDept);
            });
        }
        if ($runId !== false) {
            unset($param["search"]["run_id"]);
        } else {
            unset($param['mobile_selector']);
        }
        if ($hostFlag !== false) {
            unset($param["search"]["host_flag"]);
        }
        if ($transactFlag !== false) {
            unset($param["search"]["transact_flag"]);
        }
        if ($monitorParams != false) {
            unset($param['monitor_params']);
        }
        if (!empty($param['search']['flow_run.run_seq'])) {
            $param['search']['run_seq_strip_tags'] = $param['search']['flow_run.run_seq'];
            unset($param['search']['flow_run.run_seq']);
        } else if (!empty($param['search']['run_seq'])) {
            $param['search']['run_seq_strip_tags'] = $param['search']['run_seq'];
            unset($param['search']['run_seq']);
        }
        if (isset($param['search']['run_seq_strip_tags'][0])) {
            $param['search']['run_seq_strip_tags'][0] = str_replace(' ', '%', $param['search']['run_seq_strip_tags'][0]);
        }
        if (isset($param['controlFlows']) && !empty($param['controlFlows'])) {
            $query = $query->whereNotIn('flow_run.flow_id' , $param['controlFlows']);
        }
        $formId = isset($param["formId"]) ? $param["formId"] : "";
        $default = [
            'fields' => ['flow_run.*'],
            'page' => 0,
            'limit' => config('eoffice.pagesize'),
            'search' => [],
            'order_by' => ['flow_run.transact_time' => 'desc'],
            'returntype' => 'array',
        ];
        $param = array_merge($default, array_filter($param));
        /*
        merge参数之后，判断查询类型，三种：
        1、默认类型，普通流程查询结果列表，不需要带"表单数据"
        2、表单字段查询类型，用来获取带表单字段查询的时候的数据，如果有表单字段查询字段，1、加flow_run前缀；2、需要带"表单数据"
        3、流程数据导出类型，流程id和表单id必填，不一定有表单字段查询字段，1、加flow_run前缀；2、需要带"表单数据"
         */
        if ($formId) {
            // 处理order/search里面的参数，匹配flow_run里的字段，匹配的加flow_run前缀。
            $flowRunTableColumns = array_flip($this->getTableColumns());
            if (count($param["search"])) {
                $dataSearchParams = $this->addTablePrefixByTableColumns(["data" => $param["search"], "table" => "flow_run", "tableColumnsFlip" => $flowRunTableColumns]);
                $param["search"] = $dataSearchParams;
            }
            if (count($param["order_by"])) {
                $dataSearchParams = $this->addTablePrefixByTableColumns(["data" => $param["order_by"], "table" => "flow_run", "tableColumnsFlip" => $flowRunTableColumns]);
                $param["order_by"] = $dataSearchParams;
            }
        }

        $param = $this->handleInstanysSortParam($param);

        if (isset($param["search"]['multiSearch'])) {
            if ( isset($param["search"]['multiSearch']['run_name'][0])) {
                $param["search"]['multiSearch']['run_name'][0] = str_replace(' ', "%", $param["search"]['multiSearch']['run_name'][0]);
            }
            $query = $query->multiWheres($param["search"]);
            unset($param["search"]['multiSearch']);
        }
        $query = $query
            ->orders($param['order_by'])
            ->select($param['fields'])
            ->wheres($param['search'])
            ->where('flow_run.is_effect', '1')
            // ->has("flowRunHasManyFlowRunProcess")
            // ->with("flowRunHasManyFlowRunProcess")
            ->with(["flowRunHasManyFlowRunProcess" => function ($query) {
                $query->select("flow_run_process_id", "run_id", "user_id", "process_id", "flow_process", "host_flag", "process_flag", "process_time", "saveform_time", "deliver_time", "by_agent_id", "sub_flow_run_ids", 'process_type', 'free_process_step', 'flow_process', 'is_back', "flow_serial", "branch_serial", "process_serial", "origin_process", 'user_run_type', 'user_last_step_flag', "flow_id", "outflow_process", "origin_process_id")
                    ->orders(['process_id' => 'desc', 'host_flag' => 'desc'])
                    ->with(['flowRunProcessHasOneUser' => function ($query) {
                        $query->select('user_id', 'user_name')->with([
                            'userHasOneSystemInfo' => function ($query) {
                                $query->select('user_id', 'user_status')->withTrashed();
                            }
                        ])->withTrashed();
                    }])->with(["flowRunProcessHasOneFlowProcess" => function ($query) {
                        $query->select('node_id', 'process_name', 'head_node_toggle', 'process_entrust', 'trigger_son_flow_back', 'merge', 'concurrent');
                    }])->with('flowRunProcessHasManyAgencyDetail');
            }])
            // ->with(["flowRunHasManyFlowProcess" => function($query){
            //     $query->select("process_name","process_id","flow_id");
            // }])
            ->with(["flowRunHasOneUser" => function ($query) {
                $query->select("user_id", "user_name")->withTrashed();
            }])
            ->with(["FlowRunHasOneFlowType" => function ($query) {
                $query->select("flow_id", "flow_name", "allow_monitor", "flow_type", "form_id")
                ->with(['flowTypeHasManyManageUser' => function ($query) {
                    $query->select("flow_id", "user_id");
                }])->with(["flowTypeHasOneFlowOthers" => function ($query) {
                    $query->select("flow_id", "first_node_delete_flow");
                }]);
            }]);
        if ($userId != "admin" && !isset($param['mobile_selector'])) {
            $query = $query->where(function ($query) use ($userId, $monitorParams) {
                if (($monitorParams !== false) && (!empty($monitorParams))) {
                    $query = $query->orWhere(function ($query) use ($monitorParams) {
                        $flowIdArray = [];
                        foreach ($monitorParams as $key => $value) {
                            $tempFlowId = $value['flow_id'] ?? 0;
                            $tempUserId = $value['user_id'] ?? 0;
                            if ($tempFlowId && $tempUserId) {
                                if ($tempUserId == 'all') { // 被监控人是所有人的流程放在一起
                                    $flowIdArray[] = $value['flow_id'];
                                } else { // 被监控人是部分人的用orWhere直接查
                                    $query = $query->orWhere(function ($query) use ($value) {
                                        $query = $query->where('flow_run.flow_id', $value['flow_id'])->whereIn('flow_run.creator', $value['user_id']);
                                    });
                                }
                            }
                        }
                        if (!empty($flowIdArray)) {
                            $query = $query->orWhereIn('flow_run.flow_id', $flowIdArray);
                        }
                    });
                }
                // 关联flow_run_step表查询，两表数据量差别较大，直接whereHas关联查询非常慢，先用这种方式查询试试看，注释掉的为优化方案还有些问题，再研究下
                // $query = $query->orWhereHasIn("flowRunHasManyFlowRunStep", ['user_id' => [$userId]]);
                // $query = $query->orWhere("flow_run.creator",$userId);
                $query = $query->orWhereHas("flowRunHasManyFlowRunProcess", function ($query) use ($userId) {
                    // 查询委托人的数据
                    $query->where('user_id', $userId)->orWhereHas("flowRunProcessHasManyAgencyDetail", function ($query) use ($userId) {
                            $query->where('user_id', $userId)->orWhere('by_agency_id', $userId);
                        });
                })
                ->orWhereHas("flowRunHasManyFlowCopy", function ($query) use ($userId) {
                    // 查询是抄送人的数据
                    $query->select(['run_id'])->where("by_user_id", $userId);
                });
                // ->orWhere("flow_run.creator", $userId);
            });
        }
        if ($hostFlag) {
            // $query = $query->whereHasIn("flowRunHasManyFlowRunStep", ['user_id' => [$hostFlag, 'in'], 'host_flag' => [1]]);
            $query = $query->where(function ($query) use ($hostFlag) {
                $query->whereHas("flowRunHasManyFlowRunProcess", function ($query) use ($hostFlag) {
                    $query->whereIn("user_id", $hostFlag)->where("host_flag", 1);
                });
            });
        }
        if ($transactFlag !== false) {
            // $query = $query->whereHasIn("flowRunHasManyFlowRunStep", ['user_id' => [$transactFlag, 'in'], 'host_flag' => [0]]);
            $query = $query->where(function ($query) use ($transactFlag) {
                $query->whereHas("flowRunHasManyFlowRunProcess", function ($query) use ($transactFlag) {
                    $query->whereIn("user_id", $transactFlag)->where("host_flag", 0);
                });
            });
        }
        if ($runId !== false) {
            $query = $query->whereIn('flow_run.run_id', (array)$runId);
        }
        // 主表查询条件
        $mainTableWhereString = isset($param["mainTableWhereString"]) ? $param["mainTableWhereString"] : "";
        if ($mainTableWhereString && $formId && Schema::hasTable("zzzz_flow_data_" . $formId)) {
            // 处理流程表单关联
            $query = $query->join("zzzz_flow_data_" . $formId, "zzzz_flow_data_" . $formId . ".run_id", "=", "flow_run.run_id")
                ->selectRaw("zzzz_flow_data_" . $formId . ".*");
            $query = $query->whereRaw($mainTableWhereString);
        } else if (isset($param['flow_module_factory']) && $param['flow_module_factory'] && isset($param['form_id'])) {
            // 关联zzzz表的数据，用于模块工厂的模块的列表字段
            if ($param['form_id'] && Schema::hasTable('zzzz_flow_data_' . $param['form_id'])) {
                $query = $query->join('zzzz_flow_data_' . $param['form_id'], 'flow_run.run_id', '=', 'zzzz_flow_data_' . $param['form_id'] . '.run_id')
                    ->select('zzzz_flow_data_' . $param['form_id'] . '.*', 'flow_run.*');
            }
        }

        // 子表查询条件
        // $detailTableWhereString = isset($param["detailTableWhereString"]) ? $param["detailTableWhereString"]:"";
        // $detailControlParentId  = isset($param["detailControlParentId"]) ? $param["detailControlParentId"]:[];
        // if($detailTableWhereString && count($detailControlParentId) > 0) {
        //     $detailControlParentId = array_unique($detailControlParentId);
        //     foreach ($detailControlParentId as $detailTableKey => $detailTableValue) {
        //         $detailTableId = str_replace("DATA_", $formId."_", $detailTableValue);
        //         if($detailTableId && Schema::hasTable("zzzz_flow_data_".$detailTableId)) {
        //             $query = $query->leftJoin("zzzz_flow_data_".$detailTableId, "zzzz_flow_data_".$detailTableId.".run_id", "=", "flow_run.run_id")
        //                     ->selectRaw("zzzz_flow_data_".$detailTableId.".*");
        //         }
        //     }
        //     $query = $query->whereRaw($detailTableWhereString);
        // }
        //
        //为了按紧急程度查询而连表
        $query = $this->joinFlowInstancysQuery($query, $param);
        // 翻页判断
        $query = $query->parsePage($param['page'], $param['limit']);
        // 返回值类型判断
        if ($param["returntype"] == "array") {
            return $query->get()->toArray();
        } else if ($param["returntype"] == "count") {
            return $query->count();
        } else if ($param["returntype"] == "object") {
            return $query->get();
        }
    }

    private function handleInstanysSortParam($param)
    {
        if (count($param["order_by"])) {
            if (isset($param['order_by']['instancy_type'])) {
                $param['order_by']['flow_instancys.sort'] = $param['order_by']['instancy_type'];
                unset($param['order_by']['instancy_type']);
            } else if (isset($param['order_by']['flow_run.instancy_type'])) {
                $param['order_by']['flow_instancys.sort'] = $param['order_by']['flow_run.instancy_type'];
                unset($param['order_by']['flow_run.instancy_type']);
            }
        }

        return $param;
    }

    /**
     * 连接紧急程度选项表来排序
     *
     * @param object $query
     * @param array $param
     *
     * @return $query
     */
    private function joinFlowInstancysQuery($query, $param)
    {
        if (count($param['order_by']) && isset($param['order_by']['flow_instancys.sort'])) {
            $query = $query->leftJoin('flow_instancys', 'flow_run.instancy_type', '=', 'flow_instancys.instancy_id');
        }

        return $query;
    }

    /**
     * 获取流程【流程查询】的列表数量
     *
     * @method getFlowRunProcessMyRequestList
     *
     * @param array $param [description]
     *
     * @return [type]                                [description]
     */
    public function getFlowRunFlowSearchListTotal($param = [])
    {
        $userId = isset($param["user_id"]) ? $param["user_id"] : "";
        if ($userId == "") {
            return 0;
        }
        $param["page"] = "0";
        $param["returntype"] = "count";
        return $this->getFlowRunFlowSearchList($param);
    }

    // 流程查询获取数据，以flow_run为主表，查询流程的明细表的数据，用来获取run_id
    public function getFlowZzzzFlowDataSearchList($param = [])
    {
        $query = $this->entity;
        $formId = isset($param["formId"]) ? $param["formId"] : "";
        $default = [
            'fields' => ['flow_run.run_id'],
            'page' => 0,
            'limit' => config('eoffice.pagesize'),
            'search' => [],
            'order_by' => ['flow_run.transact_time' => 'desc'],
            'returntype' => 'object',
        ];
        $param = array_merge($default, array_filter($param));
        $query = $query
            // ->orders($param['order_by'])
            ->select($param['fields'])
            // ->wheres($param['search'])
            // ->distinct()
        ;
        // 子表查询条件
        $detailTableWhereString = isset($param["detailTableWhereString"]) ? $param["detailTableWhereString"] : "";
        $detailControlParentId = isset($param["detailControlParentId"]) ? $param["detailControlParentId"] : [];
        if ($detailTableWhereString && count($detailControlParentId) > 0) {
            $detailControlParentId = array_unique($detailControlParentId);
            foreach ($detailControlParentId as $detailTableKey => $detailTableValue) {
                $detailTableId = str_replace("DATA_", $formId . "_", $detailTableValue);
                if ($detailTableId && Schema::hasTable("zzzz_flow_data_" . $detailTableId)) {
                    $query = $query->leftJoin("zzzz_flow_data_" . $detailTableId, "zzzz_flow_data_" . $detailTableId . ".run_id", "=", "flow_run.run_id")// ->selectRaw("zzzz_flow_data_".$detailTableId.".*")
                    ;
                }
            }
            $query = $query->whereRaw($detailTableWhereString);
        }
        if (isset($param["flowId"]) && !empty($param['flowId'])) {
            $query = $query->where('flow_run.flow_id', $param['flowId']);
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
        }
    }

    /**
     * 【流程运行】 获取流程flow_run为主表的所有相关流程运行信息
     * 参数 getType ，如果是 simple 的时候，只关联传入人员有关的步骤信息
     *
     * @method getFlowRunningInfo
     *
     * @param  [type]             $param [description]
     *
     * @return [type]                    [description]
     */
    public function getFlowRunningInfo($runId, $param)
    {
        $userId = isset($param["user_id"]) ? $param["user_id"] : "";
        $getType = isset($param["getType"]) ? $param["getType"] : "";
        $default = [
            'fields' => ['*'],
            'page' => 0,
            'limit' => config('eoffice.pagesize'),
            'search' => [],
            'order_by' => ['run_id' => 'desc'],
        ];
        $param = array_merge($default, array_filter($param));
        $query = $this->entity
            ->select($param['fields'])
            ->wheres($param['search'])
            ->where("run_id", $runId)
            ->orders($param['order_by'])
            ->with(["flowRunHasOneUser" => function ($query) {
                $query->select("user_id", "user_name")->withTrashed();
            }])
            // ->with("FlowRunHasOneFlowType.flowTypeHasOneFlowOthers","FlowRunHasOneFlowType.flowTypeHasManyFlowProcess")
            // 备注：20160929，修改flow_process之后，不需要再从flowType关联flowPorcess了，直接用flow_run_step表的flow_process关联node_id
            ->with("FlowRunHasOneFlowType.flowTypeHasOneFlowOthers", "FlowRunHasOneFlowType.flowTypeHasOneFlowFormType")
            ->with(['FlowRunHasOneFlowType.flowTypeBelongsToFlowSort' => function ($query) {
                $query->select(['id', 'title']);
            }]);
        if ($getType == "simple") {
            $query = $query->with(["flowRunHasManyFlowRunProcess" => function ($query) use ($userId) {
                if ($userId) {
                    $query->where('user_id', $userId);
                }
                $query->with(['flowRunProcessHasManyAgencyDetail' => function ($query) {
                    $query->select(['*'])->orderby('sort');
                }]);
                $query->with('flowRunProcessHasOneFlowProcess');
            }])
                ->with(["flowRunHasManyFlowRunStep" => function ($query) use ($userId) {
                    if ($userId) {
                        $query->where('user_id', $userId);
                    }
                    $query->with("flowRunStepHasOneFlowProcess");
                }]);
        } else {
            $query = $query
                ->with(["flowRunHasManyFlowRunProcess.flowRunProcessHasOneMonitorSubmitUser" => function ($query) {
                    $query->select("user_id", "user_name")->withTrashed();
                }])
                ->with(["flowRunHasManyFlowRunProcess.flowRunProcessHasOneAgentUser" => function ($query) {
                    $query->select("user_id", "user_name")->withTrashed();
                }])
                ->with(["flowRunHasManyFlowRunProcess.flowRunProcessHasOneForwardUser" => function ($query) {
                    $query->select("user_id", "user_name")->withTrashed();
                }])
                ->with(["flowRunHasManyFlowRunProcess.flowRunProcessHasOneUser" => function ($query) {
                    $query->select("user_id", "user_name", "deleted_at")->with([
                        'userHasOneSystemInfo' => function ($query) {
                            $query->select('user_id', 'user_status');
                        }
                    ])->withTrashed();
                }])
                ->with(["flowRunHasManyFlowRunProcess" => function ($query) use ($param) {
                    $query->orders(['flow_serial' => 'asc', 'branch_serial' => 'asc', 'process_serial' => 'asc' ,'process_id' => 'asc', 'host_flag' => 'desc'])
                    ->with("flowRunProcessHasOneFlowProcess");
                    $query->with(['flowRunProcessHasManyAgencyDetail' => function ($query) {
                        $query->select(['*'])->orderby('sort');
                        $query->with(["flowRunProcessAgencyDetailHasOneAgentUser" => function ($query) {
                            $query->select("user_id", "user_name")->withTrashed();
                        }]);
                        $query->with(["flowRunProcessAgencyDetailHasOneUser" => function ($query) {
                            $query->select("user_id", "user_name")->withTrashed();
                        }]);

                    }]);
                }])
                ->with(["flowRunHasManyFlowRunStep" => function ($query) use ($userId) {
                    if ($userId) {
                        $query->where('user_id', $userId);
                    }
                    $query->with("flowRunStepHasOneFlowProcess");
                }]);
            $query = $query->with(["flowRunHasManyFlowCopy.flowCopyHasOneUser" => function ($query) {
                $query->select("user_id", "user_name")->withTrashed();
            }])
                ->with(["flowRunHasManyFlowCopy.flowCopyHasOneByCopyUser" => function ($query) {
                    $query->select("user_id", "user_name")->withTrashed();
                }]);
        }
        return $query->first();
    }

    /**
     * 处理order/search里面的参数，匹配flow_run里的字段，匹配的加flow_run前缀。
     * @param [array] $param [data：要处理的参数数组，table：要添加的表名，tableColumnsFlip：表结构字段反转之后的数组]
     */
    public function addTablePrefixByTableColumns($param)
    {
        $dataSearchParams = $param["data"];
        if (count($dataSearchParams)) {
            foreach ($dataSearchParams as $dataSearchKey => $dataSearchValue) {
                if (count(array_intersect_key([$dataSearchKey => ""], $param["tableColumnsFlip"]))) {
                    $dataSearchParams[$param["table"] . "." . $dataSearchKey] = $dataSearchParams[$dataSearchKey];
                    unset($dataSearchParams[$dataSearchKey]);
                }
            }
            return $dataSearchParams;
        }
    }

    /**
     * 获取流程运转步骤中离职人员数据
     *
     *
     * @param  [type]  $flowId [description]
     *
     * @return [type]          [description]
     */
    public function getFlowRunQuitUserList($id)
    {
        $query = $this->entity;
        $query = $query->leftJoin('user_system_info', 'user_system_info.user_id', '=', 'flow_run.creator');
        $query = $query->leftJoin('user', 'user.user_id', '=', 'user_system_info.user_id')
            ->select(['user.user_name', 'flow_run.creator as user_id'])
            ->where("flow_run.flow_id", $id)
            ->where("user_system_info.user_status", 2)
            ->groupBy('flow_run.creator');
        return $query->get()->toArray();
    }

    /**
     * 获取流程【报表】的数据
     *
     * @method getFlowRunProcessMyRequestList
     *
     * @param array $param [description]
     *
     * @return [type]                                [description]
     */
    public function getFlowRunReportData($param)
    {
        if (isset($param['table']) && !empty($param['table'])) {
            $query = DB::table($param['table'])->where('amount', '!=', 'amount');
        } else {
            $query = $this->entity;
        }

        $formId = isset($param["formId"]) ? $param["formId"] : "";
        $default = [
            'fields' => [],
            'page' => 0,
            'limit' => config('eoffice.pagesize'),
            'search' => [],
            'returntype' => 'array',
        ];
        $param = array_merge($default, array_filter($param));
        /*
        merge参数之后，判断查询类型，三种：
        1、默认类型，普通流程查询结果列表，不需要带"表单数据"
        2、表单字段查询类型，用来获取带表单字段查询的时候的数据，如果有表单字段查询字段，1、加flow_run前缀；2、需要带"表单数据"
        3、流程数据导出类型，流程id和表单id必填，不一定有表单字段查询字段，1、加flow_run前缀；2、需要带"表单数据"
         */
        if ($formId) {
            // 处理order/search里面的参数，匹配flow_run里的字段，匹配的加flow_run前缀。
            $flowRunTableColumns = array_flip($this->getTableColumns());
            if (count($param["search"])) {
                $dataSearchParams = $this->addTablePrefixByTableColumns(["data" => $param["search"], "table" => "flow_run", "tableColumnsFlip" => $flowRunTableColumns]);
                $param["search"] = $dataSearchParams;
            }
        }
        $query = $query->select($param['fields']);
        $operators = [
            'between' => 'whereBetween',
            'not_between' => 'whereNotBetween',
            'in' => 'whereIn',
            'not_in' => 'whereNotIn',
        ];
        if (empty($param['search'])) {
            return $query;
        } else {
            foreach ($param['search'] as $field => $where) {
                $operator = isset($where[1]) ? $where[1] : '=';
                $operator = strtolower($operator);
                if (isset($operators[$operator])) {
                    $whereOp = $operators[$operator];
                    $query = $query->$whereOp($field, $where[0]);
                } else {
                    $value = $operator != 'like' ? $where[0] : '%' . $where[0] . '%';
                    $query = $query->where($field, $operator, $value);
                }
            }
        }

        if (!isset($param['table']) || empty($param['table'])) {
            $query = $query->with(["flowRunHasOneUser" => function ($query) {
                $query->select("user_id", "user_name")->withTrashed();
            }]);
        }
        // 处理group by之后，查询的那个字段
        if (isset($param["group_select_fields"]) && $param["group_select_fields"]) {
            if (is_array($param["group_select_fields"]) && count($param["group_select_fields"])) {
                foreach ($param["group_select_fields"] as $fields_key => $fields_value) {
                    $query->addSelect(DB::raw($fields_value));
                }
            } else {
                $query->addSelect(DB::raw($param["group_select_fields"]));
            }
        }
        // 解析原生 select
        if (isset($param['selectRaw'])) {
            foreach ($param['selectRaw'] as $key => $selectRaw) {
                $query = $query->selectRaw($selectRaw);
            }
        }
        // 解析原生 where
        if (isset($param['whereRaw'])) {
            foreach ($param['whereRaw'] as $key => $whereRaw) {
                $query = $query->whereRaw($whereRaw);
            }
        }
        // 主表查询条件
        $mainTableWhereString = isset($param["mainTableWhereString"]) ? $param["mainTableWhereString"] : "";
        $formDateSearchString = isset($param["formDateSearchString"]) ? $param["formDateSearchString"] : "";
        $detailTableWhereString = isset($param["detailTableWhereString"]) ? $param["detailTableWhereString"] : "";
        if (isset($param['table']) && !empty($param['table'])) {
            $query = $query->join('flow_run', function ($query) use ($param) {
                // 报表统计排除已删除流程的数据
                $query = $query->on("flow_run.run_id", "=", $param['table'] . ".run_id")
                    ->whereNull("flow_run.deleted_at");
            });
            $query = $query->join("zzzz_flow_data_" . $formId, "zzzz_flow_data_" . $formId . ".run_id", "=", $param['table'] . ".run_id");
            if ($detailTableWhereString) {
                $query = $query->whereRaw($detailTableWhereString);
            }
        } elseif ($formId && Schema::hasTable("zzzz_flow_data_" . $formId)) {
            // 处理流程表单关联
            $query = $query->join("zzzz_flow_data_" . $formId, "zzzz_flow_data_" . $formId . ".run_id", "=", "flow_run.run_id");
        }
        if ($mainTableWhereString && $formId && Schema::hasTable("zzzz_flow_data_" . $formId)) {
            $query = $query->whereRaw($mainTableWhereString);
        }
        if ($formDateSearchString && $formId && Schema::hasTable("zzzz_flow_data_" . $formId)) {
            $query = $query->whereRaw($formDateSearchString);
        }
        // 分组参数
        if (isset($param['groupBy'])) {
            $query = $query->groupBy($param['groupBy'])->orderBy($param['groupBy'], 'DESC');
        }
        // 返回值类型判断
        if ($param["returntype"] == "array") {
            return $query->get()->toArray();
        } else if ($param["returntype"] == "count") {
            return $query->count();
        } else if ($param["returntype"] == "object") {
            return $query->get();
        }
    }

    /**
     * 判断紧急程度选项是否生效，即是否已经有流程使用它
     *
     * @param int $instancyId
     *
     * @return boolean
     */
    public function instancyIsEffect($instancyId)
    {
        return $this->entity->where('instancy_type', $instancyId)->count() > 0 ? true : false;
    }

    /**
     *
     * @param  [type] $run_name [description]
     * @return [type]        [description]
     */
    public function getBlankSpaceRunName($run_name)
    {
        if (empty($run_name) || !is_string($run_name)) {
            return  " flow_run.run_name like '%'";
        }
        $run_name = str_replace("'", "\'", $run_name);
        if (strpos($run_name, ' ') !== false) {
            $runArr = explode(' ', $run_name);
            $runstr = '';
            foreach ($runArr as $runkey => $runvalue) {
                if (!empty($runvalue)) {
                    $runstr .= " flow_run.run_name like '%".$runvalue."%' and";
                }
            }
            return rtrim($runstr , 'and');
        } else {
            return  " flow_run.run_name like '%".$run_name."%'";
        }

    }

    /**
     * 【流程列表】 获取流程动态信息控件历史流程列表
     *
     * @author miaochenchen
     *
     * @since 2019-12-10
     *
     * @return [type]       [description]
     */
    public function getFlowDynamicInfoHistoryList($params)
    {
        $default = [
            'fields'     => ['run_name', 'run_seq_strip_tags'],
            'page'       => 0,
            'limit'      => config('eoffice.pagesize'),
            'search'     => [],
            'returntype' => 'array',
            'order_by'   => ['flow_run.run_id' => 'desc'],
        ];
        $params = array_merge($default, array_filter($params));
        // 加上run_id和flow_id用于列表点击跳转
        array_push($params['fields'], 'flow_run.run_id', "flow_run.flow_id");
        $zzzzTableName = $params['form_table'] ?? '';
        if (empty($zzzzTableName) || !Schema::hasTable($zzzzTableName)) {
            if ($params["returntype"] == "array") {
                return [];
            } else if ($params["returntype"] == "count") {
                return 0;
            }
        }
        $tableFields = Schema::getColumnListing($zzzzTableName);
        if (empty($tableFields)) {
            if ($params["returntype"] == "array") {
                return [];
            } else if ($params["returntype"] == "count") {
                return 0;
            }
        }
        foreach ($params['fields'] as $key => $value) {
            if ($value == 'flow_run.run_id' || $value == 'flow_run.flow_id' || $value == 'run_name' || $value == 'run_seq_strip_tags') {
                continue;
            }
            if (!in_array($value, $tableFields)) {
                unset($params['fields'][$key]);
            }
        }
        $query  = $this->entity->select($params['fields']);
        $query  = $query->leftJoin($zzzzTableName, $zzzzTableName.'.run_id', '=', 'flow_run.run_id')
                        ->wheres($params['search'])->orders($params['order_by']);
        // 翻页判断
        $query = $query->parsePage($params['page'], $params['limit']);
        // 返回值类型判断
        if ($params["returntype"] == "array") {
            return $query->get()->toArray();
        } else if ($params["returntype"] == "count") {
            return $query->count();
        } else if ($params["returntype"] == "object") {
            return $query->get();
        }
    }

    /**
     * 【流程列表】 获取流程动态信息控件历史流程数量
     *
     * @author miaochenchen
     *
     * @since 2019-12-10
     *
     * @return [type]       [description]
     */
    public function getFlowDynamicInfoHistoryTotal($params)
    {
        $params["page"] = "0";
        $params["returntype"] = "count";
        return $this->getFlowDynamicInfoHistoryList($params);
    }

    /**
     * 获取关联流程信息
     *
     * @method getRelationRunList
     *
     * @param array $param [description]
     *
     * @return [type]                       [description]
     */
    function getRelationRunList($param = [])
    {
        $default = [
            'search' => [],
            'order_by' => ['run_id' => 'asc'],
        ];
        $param = array_merge($default, array_filter($param));
        $query = $this->entity
            ->wheres($param['search'])
            ->orders($param['order_by'])
            ->with(["flowRunHasManyFlowRunProcess" => function ($query) {
                $query->select("user_id" , "flow_process" , "run_id" , "user_last_step_flag" ,"user_run_type" ,"flow_run_process_id")
                      ->with(["flowRunProcessHasOneFlowProcess" => function ($query) {
                          $query->select("process_name" , "node_id")->withTrashed();
                      }]);
            }])
            ->with(["flowRunHasOneUser" => function ($query) {
                $query->select("user_id", "user_name")->withTrashed();
            }]);

        // 解析原生 select
        if (isset($param['selectRaw'])) {
            foreach ($param['selectRaw'] as $key => $selectRaw) {
                $query = $query->selectRaw($selectRaw);
            }
        }
        return $query->get()->toArray();

    }

    /**
     * 功能函数，根据流程run_id，获取form_id
     * @param  [type] $runId [description]
     * @return [type]        [description]
     */
    public function getFormIdByRunId($runId, $withTrashed = false)
    {
        $query = $this->entity->select('run_id','flow_id')
                    ->where('run_id', $runId)
                    ->with(['FlowRunHasOneFlowType' => function($query) {
                        $query->select('flow_id', 'form_id');
                    }]);
        if ($withTrashed) {
            $query->withTrashed();
        }
        $flowRunInfo = $query->first();
        return $flowRunInfo->FlowRunHasOneFlowType->form_id ?? 0;
    }

    /**
     * 监控规则转化为run_id
     * @param $
     * @return mixed
     */
     public function getFlowRunIdsByMonitorParams( $monitorParams , $userId)
     {
        if (!empty($monitorParams)) {
            $query = $this->entity;
            $flowIdArray = [];
            $query = $query->Where(function ($query) use ( $userId, $monitorParams ,&$flowIdArray) {
                $query->orWhere('creator' ,$userId );
                    foreach ($monitorParams as $key => $value) {
                        if (isset($value['user_id']) && $value['user_id'] == 'all') {
                            $flowIdArray[] = $value['flow_id'];
                        } else {
                            $query = $query->orWhere(function ($query) use ($value) {
                              if (isset($value['flow_id']) && !empty($value['flow_id'])) {
                                  if (isset($value['user_id']) && !empty($value['user_id'])) {
                                      if ($value['user_id'] != 'all') {
                                          $query = $query->where('flow_id', $value['flow_id'])
                                              ->whereIn('creator', $value['user_id']);
                                      }
                                  }
                              }
                          });
                        }
                    }
                    // if (!empty($flowIdArray)) {
                    //     $qeury = $query->orWhereIn('flow_id', $flowIdArray);
                    // }
            });
            $res =  $query->distinct()->pluck('run_id')->toArray();
             return ['run_ids' =>$res , 'flow_ids' => $flowIdArray ];
        }
        return [];

     }
}
