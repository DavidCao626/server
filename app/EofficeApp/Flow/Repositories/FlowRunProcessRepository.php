<?php
namespace App\EofficeApp\Flow\Repositories;
use DB;
use Schema;
use App\EofficeApp\Flow\Entities\FlowRunProcessEntity;
use App\EofficeApp\Flow\Repositories\FlowRunRepository;
use App\EofficeApp\Flow\Repositories\FlowTypeRepository;
use App\EofficeApp\Base\BaseRepository;

/**
 * 流程运行步骤表知识库
 *
 * @author 丁鹏
 *
 * @since  2015-10-16 创建
 */
class FlowRunProcessRepository extends BaseRepository
{
    public function __construct(
      FlowRunProcessEntity $entity,
      FlowRunRepository $flowRunRepository,
      FlowTypeRepository $flowTypeRepository
      ) {
        parent::__construct($entity);
        $this->flowRunRepository = $flowRunRepository;
        $this->flowTypeRepository = $flowTypeRepository;
    }

    /**
     * 验证流程签办反馈是否被查看过
     *
     * @method checkFeedbackIsReadRepository
     *
     * @param  array                         $param [description]
     *
     * @return [type]                               [description]
     */
    function checkFeedbackIsReadRepository($param,$userId)
    {
        $query = $this->entity
                      ->select('process_time')
                      ->where('run_id',$param['run_id'])
                      ->where('process_id',$param['process_id'])
                      ->where('user_id',$userId)
                      ->where('last_visited_time','>',$param["edit_time"]);
        return $query->count();
    }

    /**
     * 一个基础的函数，用来根据各种条件获取flow_run_process表的数据
     *
     * @method getFlowRunProcessList
     *
     * @param  array                 $param [description]
     *
     * @return [type]                       [description]
     */
    function getFlowRunProcessList($param = [])
    {
        $selectUser = isset($param['select_user']) ? $param['select_user']:  true;
        $default = [
            'fields'     => ['*'],
            'page'       => 0,
            'limit'      => config('eoffice.pagesize'),
            'search'     => [],
            'order_by'   => ['process_id'=>'asc'],
            'returntype' => 'object',
        ];
        $param = array_merge($default, array_filter($param));
        if (isset($param['search']['run_id'])) {
            $param['search']['flow_run_process.run_id'] = $param['search']['run_id'];
            unset($param['search']['run_id']);
        }
        $query = $this->entity
                      ->wheres($param['search'])
                      ->orders($param['order_by']);
        if ($param['returntype'] != 'count') {
            $query = $query->with(["flowRunProcessHasOneUser" => function($query){
                            $query->select("user_id","user_name")->withTrashed();
                      }]);
        }
        $done_date  = isset($param["done_date"])  ? $param["done_date"] : '';
        $user_id    = isset($param["user_id"]) ? security_filter($param["user_id"]) : '';

        if ($selectUser) {
            $query = $query->with(["flowRunProcessHasOneUser" => function($query){
                            $query->select("user_id","user_name")->withTrashed();
                      }]);
        }
        // 分组参数
        if(isset($param['groupBy'])) {
            $query = $query->groupBy($param['groupBy']);
        }
        if (isset($param['flow_id']) && isset($param['from']) && $param['from'] == 'recycleFlowAgencyRule'){
           $query = $query->where("flow_run_process.flow_id" , $param['flow_id']);
        } else if(isset($param['flow_id']) ) {
            $query = $query->with('flowRunProcessBelongsToFlowRun')->where("flow_run_process.flow_id" , $param['flow_id']);
                           // ->whereHas("flowRunProcessBelongsToFlowRun", function ($query) use($param) {
                           //      $query->where("flow_id",$param['flow_id']);
                           // });
        }
        if(isset($param['sendBackInfo'])) {
            $query = $query->with(["flowRunProcessHasOneSendBackUser" => function($query){
                                $query->select("user_id","user_name")->withTrashed();
                            }]);
        }
        // 委托详情
        if(isset($param['agencyDetailInfo'])) {
            $param['fields'] = array_merge($param['fields'], ['fields' => 'flow_run_process_id']);
            $query = $query->with(["flowRunProcessHasManyAgencyDetail" => function($query){
                $query->select(['*'])->orderby('sort');
                $query->with(["flowRunProcessAgencyDetailHasOneAgentUser" => function($query){
                    $query->select("user_id","user_name")->withTrashed();
                }]);
                $query->with(["flowRunProcessAgencyDetailHasOneUser" => function($query){
                    $query->select("user_id","user_name")->withTrashed();
                }]);
            }]);
        }

        if(isset($param['run_id']) && is_array($param['run_id'])) {
            $query = $query->whereIn('flow_run_process.run_id',$param['run_id']);
        } else if (isset($param['run_id'])){
           $query = $query->where('flow_run_process.run_id',$param['run_id']);
        }
        // 解析原生 where
        if(isset($param['whereRaw'])) {
            foreach ($param['whereRaw'] as $key => $whereRaw) {
                $query = $query->whereRaw($whereRaw);
            }
        }
        // 解析原生 select
        if(isset($param['selectRaw'])) {
            foreach ($param['selectRaw'] as $key => $selectRaw) {
                $query = $query->selectRaw($selectRaw);
            }
        } else {
            $query = $query->select($param['fields']);
        }
        // 关联定义流程节点
        if(isset($param["relationNodeInfo"])) {
            $query = $query->with("flowRunProcessHasOneFlowProcess");
            $query = $query->with("flowRunProcessBelongsToFlowType");
        }
        // 关联定义流程节点
        if(isset($param["relationNodeName"])) {
            $query = $query->with(["flowRunProcessHasOneFlowProcess" => function($query){
                    $query->select("process_name")->withTrashed();
                }]);
        }
        // 关联用户 systeminfo 表
        if(isset($param["relationUserSystemInfo"])) {
            $query = $query->with(["flowRunProcessHasOneUserSystemInfo" => function($query){
                                $query->select("user_id","user_status")->withTrashed();
                            }]);
        }
        // 关联监控提交人信息
        if (isset($param["relationMonitorSubmitUserInfo"])) {
            $query = $query->with(["flowRunProcessHasOneMonitorSubmitUser" => function($query) {
                $query->select("user_id","user_name")->withTrashed();
            }]);
        }
        //处理时间
        if (!empty($done_date) && !empty($user_id)) {
                $done_date = strtotime($param["done_date"]) ? $param["done_date"] : date('Y-m-d');
                $startdone_date = $done_date." 00:00:00";
                $enddone_date   = $done_date." 23:59:59";
                $query->whereRaw(" (flow_run_process.user_id='".$user_id."'  and   ((flow_run_process.deliver_time between '" . $startdone_date . "' and '" . $enddone_date . "') or (flow_run_process.process_time between '" . $startdone_date . "' and '" . $enddone_date . "') or  (flow_run_process.saveform_time between '" . $startdone_date . "' and '" . $enddone_date . "'))) or  (flow_run_process.by_agent_id='".$user_id."' and   ((flow_run_process.created_at between '" . $startdone_date . "' and '" . $enddone_date . "')  or  (flow_run_process.receive_time between '" . $startdone_date . "' and '" . $enddone_date . "'))) ");
                $query->select("flow_run_process.*","flow_run.run_name")->withTrashed();
                $query->join('flow_run', 'flow_run.run_id', '=', 'flow_run_process.run_id');
        }
        //关联flow_run
        if ( isset($param['relationFlowRunInfo']) && $param['relationFlowRunInfo']==1 ) {
            //   $query->join('flow_run', 'flow_run.run_id', '=', 'flow_run_process.run_id');
            $query->join('flow_others', 'flow_others.flow_id', '=', 'flow_run_process.flow_id');
             //   $query->whereRaw('flow_run.current_step != 0  and flow_run.max_process_id=flow_run_process.process_id and flow_run.is_effect=1 and  flow_others.submit_without_dialog = 1');
            $query->whereRaw(' flow_run_process.is_effect=1 and  flow_others.submit_without_dialog = 1');
        }
        //查询flow_run_name
        if ( isset($param['relationFlowRunInfo']) && $param['relationFlowRunInfo']==2 ) {
            $query->join('flow_run', 'flow_run.run_id', '=', 'flow_run_process.run_id');
            $query->select('flow_run.run_name','flow_run_process.*');
        }
        // 返回值类型判断
        if($param["returntype"] == "array") {
            return $query->get()->toArray();
        } else if($param["returntype"] == "count") {
            if (isset($param['groupBy'])) {
              return $query->get()->count();
            } else {
              return $query->count();
            }
        } else if($param["returntype"] == "object") {
            return $query->get();
        } else if($param["returntype"] == "first") {
            if (isset($param['groupBy'])) {
              return $query->get()->first();
            } else {
              return $query->first();
            }
        } else if($param["returntype"] == "limit20") {
            $query = $query->offset(0)->limit(20);
            return $query->get();
        }
    }

    /**
     * 更新flow_run_process的数据，这里默认是多维的where条件。
     *
     * @method updateFlowRunProcessData
     *
     * @param  array                    $param [data:数据;wheres:可以批量解析的条件;whereRaw:原生解析的条件]
     *
     * @return [type]                          [description]
     */
    function updateFlowRunProcessData($param = [])
    {
        $data  = $param["data"];
        $query = $this->entity->wheres($param["wheres"]);
        // 解析原生 where
        if(isset($param['whereRaw'])) {
            foreach ($param['whereRaw'] as $key => $whereRaw) {
                $query = $query->whereRaw($whereRaw);
            }
        }
        // 解析 whereIn
        if(isset($param['whereIn'])) {
            foreach ($param['whereIn'] as $key => $whereIn) {
                $query = $query->whereIn("run_id",$whereIn);
            }
        }
        return (bool) $query->update($data);
    }

    /**
     * 获取自由流程的已办理节点
     *
     * @method getFreeFlowTransactProcess
     *
     * @param  array                      $param [description]
     *
     * @return [type]                            [description]
     */
    function getFreeFlowTransactProcess($param = [])
    {
        $query = $this->entity
                      ->select("run_id","process_id")
                      ->selectRaw("GROUP_CONCAT( DISTINCT user_id) deal_user_id")
                      ->selectRaw("CONCAT('".trans("flow.0x030014")."') process_name")
                      ->where('run_id',$param['run_id'])
                      ->orderBy('process_id','asc')
                      ->groupBy('process_id');
        return $query->get();
    }

    /**
     * 获取当前步骤，所有的未办理人员
     *
     * @method getHaveNotTransactPersonRepository
     *
     * @param  [type]                             $param [description]
     *
     * @return [type]                                    [description]
     */
    function getHaveNotTransactPersonRepository($param)
    {
        $query = $this->entity
                      ->where('run_id',$param['run_id'])
                      ->where('process_id',$param['process_id']);
           if(!empty($param['flow_process'])){
                $query->where('flow_process',$param['flow_process']);
           }
           $query->where('host_flag','<>','1')
                      ->where(function ($query) {
                        $query->where('process_flag','1')
                              ->orWhere(function ($query) {
                                $query->where('process_flag','2')
                                      ->whereRaw('saveform_time IS NULL');
                              });
                      })
                      ->with(["flowRunProcessHasOneUser" => function($query){
                          $query->select("user_id","user_name")->withTrashed();
                    }]);
        if (isset($param['search'])) {
          $query = $query->wheres($param['search']);
        }
        return $query->get();
    }

    /**
     * 获取流程某步骤办理人、未办理人
     *
     * @method getFlowTransactUserRepository
     *
     * @param  array                         $param [description]
     *
     * @return [type]                        返回数组，里面有办理过的，未办理的，主办人(如果有)
     */
    function getFlowTransactUserRepository($param = [])
    {
        $query = $this->entity
                      ->select("flow_run_process.run_id","user.user_name")
                      ->selectRaw("
                                CASE
                                WHEN
                                    (flow_run_process.host_flag=1 AND flow_run_process.deliver_time<>'NULL')
                                    OR
                                    (flow_run_process.host_flag=0 AND flow_run_process.saveform_time<>'NULL') THEN user.user_id
                                END HAVE_DEAL,
                                CASE
                                WHEN
                                    (flow_run_process.host_flag=1 AND flow_run_process.deliver_time IS NULL)
                                    OR
                                    (flow_run_process.host_flag=0 AND flow_run_process.saveform_time IS NULL) THEN user.user_id
                                END NOT_DEAL,
                                CASE
                                WHEN
                                    host_flag THEN user.user_id
                                END OP_FLAG_USER
                                ");
        return $query->leftJoin('user', 'user.user_id', '=', 'flow_run_process.user_id')
                    ->where('process_id',$param["process_id"])
                    ->where('run_id',$param["run_id"])
                    ->get();
    }

    /**
     * 获取流程委托记录和被委托记录
     *
     * @method getFlowRunProcessList
     *
     * @param  array                 $param [description]
     *
     * @return [type]                       [description]
     */
    function getFlowAgencyRecordList($param = [])
    {
        $userId = isset($param["user_id"]) ? $param["user_id"]:"";
        if($userId == "") {
            return $this->entity;
        }
        $query    = $this->entity;
        $runName  = isset($param["search"]["run_name"]) ? $param["search"]["run_name"][0]:false;
        $runSeq   = isset($param["search"]["run_seq"]) ? $param["search"]["run_seq"][0]:false;
        $flowSort = isset($param["search"]["flow_sort"]) ? $param["search"]["flow_sort"][0]:false;
        $flowId   = isset($param["search"]["flow_id"]) ? $param["search"]["flow_id"][0]:false;
        if($runName !== false) {
            unset($param["search"]["run_name"]);
            $query = $query->whereHas("flowRunProcessBelongsToFlowRun", function ($query) use($runName) {
                $runNameSql = $this->flowRunRepository->getBlankSpaceRunName($runName);
                $query->whereRaw($runNameSql);
            });
        }
        if($runSeq !== false) {
            unset($param["search"]["run_seq"]);
            $query = $query->whereHas("flowRunProcessBelongsToFlowRun", function ($query) use($runSeq) {
                $query->where('run_seq', 'like' ,'%'.$runSeq.'%');
            });
        }
        if($flowSort !== false) {
            unset($param["search"]["flow_sort"]);
            $query = $query->whereHas("flowRunProcessBelongsToFlowType", function ($query) use($flowSort) {
                $query->where('flow_sort', $flowSort);
            });
        }
        if($flowId !== false) {
            unset($param["search"]["flow_id"]);
            $query = $query->whereIn('flow_run.flow_id', is_array($flowId) ? $flowId : [$flowId]);
        }
        if (isset($param["controlFlows"]) && !empty($param["controlFlows"])) {
            $query = $query->whereNotIn('flow_run_process.flow_id' , $param['controlFlows']);
        }
        $default = [
            'fields'     => ['*'],
            'page'       => 0,
            'limit'      => config('eoffice.pagesize'),
            'search'     => [],
            'order_by'   => ['flow_run.run_id'=>'desc'],
            'returntype' => 'array',
        ];
        $param = array_merge($default, array_filter($param));

        $param = $this->handleInstanysSortParam($param);
        if (isset($param['order_by']['transact_time'])) {
            $param['order_by']['flow_run.transact_time'] = $param['order_by']['transact_time'];
            unset($param['order_by']['transact_time']);
        }
        $query = $query
                    ->select($param['fields'])
                    ->orders($param['order_by'])
                    ->leftJoin('flow_run', 'flow_run.run_id', '=', 'flow_run_process.run_id')
                    // ->wheres($param['search'])
                    ->where("flow_run_process.is_effect",'1')
                    ->with(["flowRunProcessHasOneFlowProcess" => function($query){
                        $query->select("process_name","process_id","flow_id","node_id");
                    }])
                    ->with(["flowRunProcessHasOneUser" => function($query){
                        $query->select("user_id","user_name")->withTrashed();
                    }])
                    ->with(["flowRunProcessHasOneAgentUser" => function($query){
                        $query->select("user_id","user_name")->withTrashed();
                    }])
                    ->with(["flowRunProcessBelongsToFlowRun" => function ($query) {
                        $query->select("run_id","run_name","run_seq","max_process_id","current_step","create_time","transact_time");
                    }]);
        // 我的委托记录
        if($param["agencyType"] == "myAgency") {
            $query->with(["flowRunProcessHasManyAgencyDetail" => function($query) use ($userId) {
                $query->select(['*'])->where('by_agency_id', $userId)->where('type', 0);
                $query->with(["flowRunProcessAgencyDetailHasOneAgentUser" => function($query){
                    $query->select("user_id","user_name")->withTrashed();
                }]);
                $query->with(["flowRunProcessAgencyDetailHasOneUser" => function($query){
                    $query->select("user_id","user_name")->withTrashed();
                }]);
            }])->leftJoin('flow_run_process_agency_detail', 'flow_run_process_agency_detail.flow_run_process_id', '=', 'flow_run_process.flow_run_process_id');
            // ->whereHas('flowRunProcessHasManyAgencyDetail', function ($query) use ($userId, $param) {
                $agentId = isset($param["search"]["agent_id"]) ? $param["search"]["agent_id"][0]:false;
                if($agentId !== false) {
                    $query->where('flow_run_process_agency_detail.by_agency_id', $userId)->where('type', 0)->whereIn("flow_run_process_agency_detail.user_id", $agentId);
                } else {
                    $query->where('flow_run_process_agency_detail.by_agency_id', $userId)->where('type', 0);
                }
            // });

            // 被委托记录
        } else if($param["agencyType"] == "byAgency"){
            $query->with(["flowRunProcessHasManyAgencyDetail" => function($query) use ($userId) {
                $query->select(['*'])->where('user_id', $userId)->where('type', 0);
                $query->with(["flowRunProcessAgencyDetailHasOneAgentUser" => function($query){
                    $query->select("user_id","user_name")->withTrashed();
                }]);
                $query->with(["flowRunProcessAgencyDetailHasOneUser" => function($query){
                    $query->select("user_id","user_name")->withTrashed();
                }]);
            }])->leftJoin('flow_run_process_agency_detail', 'flow_run_process_agency_detail.flow_run_process_id', '=', 'flow_run_process.flow_run_process_id');
            // ->whereHas('flowRunProcessHasManyAgencyDetail', function ($query) use ($userId, $param) {
                $byAgentId = isset($param["search"]["by_agent_id"]) ? $param["search"]["by_agent_id"][0]:false;
                if($byAgentId !== false) {
                    $query->where('flow_run_process_agency_detail.user_id', $userId)->where('type', 0)->whereIn("flow_run_process_agency_detail.by_agency_id", $byAgentId);
                } else {
                    $query->where('flow_run_process_agency_detail.user_id', $userId)->where('type', 0);
                }
            // });

        }
        //为了按紧急程度查询而连表
        $query = $this->joinFlowInstancysQuery($query, $param);
        // 翻页判断
        // $query = $query->parsePage($param['page'], $param['limit']);
        // 返回值类型判断
        if($param["returntype"] == "array") {
            return $query->get()->toArray();
        } else if($param["returntype"] == "count") {
            return $query->count();
        } else if($param["returntype"] == "object") {
            return $query->get();
        }
    }
     private function handleInstanysSortParam($param)
    {
        if(count($param["order_by"])) {
            if(isset($param['order_by']['instancy_type'])){
                $param['order_by']['flow_instancys.sort'] = $param['order_by']['instancy_type'];
                unset($param['order_by']['instancy_type']);
            } else if(isset($param['order_by']['flow_run.instancy_type'])){
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
        if(count($param['order_by']) && isset($param['order_by']['flow_instancys.sort'])){
            $query = $query->leftJoin('flow_instancys', 'flow_run.instancy_type', '=', 'flow_instancys.instancy_id');
        }

        return $query;
    }
    /**
     * 获取流程委托记录和被委托记录数量
     *
     * @method getFlowRunProcessList
     *
     * @param  array                 $param [description]
     *
     * @return [type]                       [description]
     */
    function getFlowAgencyRecordListTotal($param = [])
    {
        $userId = isset($param["user_id"]) ? $param["user_id"]:"";
        if($userId == "") {
            return 0;
        }
        $param["page"]       = "0";
        $param["returntype"] = "count";
        return $this->getFlowAgencyRecordList($param);
    }


    /**
     * 获取流程【超时查询】的列表
     *
     * @method getFlowRunProcessMyRequestList
     *
     * @param  array                          $param [description]
     *
     * @return [type]                                [description]
     */
    function getFlowRunProcessOvertimeList($param = [])
    {
        $query           = $this->entity;
        $runSeq          = isset($param["search"]["run_seq"]) ? $param["search"]["run_seq"][0]:false;
        $runName         = isset($param["search"]["run_name"]) ? $param["search"]["run_name"][0]:false;
        $currentStep     = isset($param["search"]["current_step"]) ? $param["search"]["current_step"][0]:false;
        $craeteTime      = isset($param["search"]["create_time"]) ? $param["search"]["create_time"]:false;
        $instancyType    = isset($param["search"]["instancy_type"]) ? $param["search"]["instancy_type"][0]:false;
        $flowCreatorId   = isset($param["search"]["flow_creator_id"]) ? $param["search"]["flow_creator_id"][0]:false;
        $processStatus   = isset($param["search"]["process_status"]) ? $param["search"]["process_status"][0]:false;
        $flowSort        = isset($param["search"]["flow_sort"]) ? $param["search"]["flow_sort"][0]:false;
        $flowCreatorDept = isset($param["search"]["flow_creator_dept"]) ? $param["search"]["flow_creator_dept"][0]:false;
        $attachmentName  = isset($param["search"]["attachment_name"]) ? $param["search"]["attachment_name"][0]:false;
        $currentTime     = date('Y-m-d H:i:s');
        $monitorParams   = isset($param['monitor_params']) && !empty($param['monitor_params']) ? $param['monitor_params'] : false;

        if ( (isset($param["search"]) && !empty($param["search"])) || isset($param['order_by']['instancy_type'])) {
              // 如果仅仅只有flow_id搜索则不连表，减少查询
              if (count($param["search"]) == 1 &&  isset($param['search']['flow_id'])){
              } else{
               $query = $query->leftJoin('flow_run', 'flow_run.run_id', '=', 'flow_run_process.run_id');
              }
        }
        if($runSeq !== false) {
            unset($param["search"]["run_seq"]);
            $runSeq = str_replace(' ', '%', $runSeq);
            $query->where('flow_run.run_seq_strip_tags', 'like' ,'%'.$runSeq.'%');
        }
        if($runName !== false) {
            unset($param["search"]["run_name"]);
            $runNameSql = $this->flowRunRepository->getBlankSpaceRunName($runName);
            $query->whereRaw($runNameSql);
        }
        if($currentStep !== false) {
            if(isset($param["search"]["current_step"]["1"]) && $param["search"]["current_step"]["1"] == "!=") {
                   //  $query->where('flow_run.current_step', "!=", $currentStep); //此时不会走索引查询会很慢
                  $query->whereRaw("flow_run.run_id not in (select run_id from flow_run where current_step = 0)");
                } else {
                    $query->where('flow_run.current_step', $currentStep);
                }
            unset($param["search"]["current_step"]);
        }
        if($craeteTime !== false) {
            unset($param["search"]["create_time"]);
            $tempSearch['create_time'] = $craeteTime;
            $query = $query->whereHas("flowRunProcessBelongsToFlowRun", function ($query) use($craeteTime) {
                $tempSearch['create_time'] = $craeteTime;
                 $query->wheres($tempSearch);
            });
        }
        if($instancyType !== false) {
            unset($param["search"]["instancy_type"]);
            $query->where('instancy_type', $instancyType);
        }
        if($flowCreatorId !== false) {
            unset($param["search"]["flow_creator_id"]);
            $query->whereIn('flow_run.creator', $flowCreatorId);
        }
        if($processStatus !== false) {
            unset($param["search"]["process_status"]);
            switch ($processStatus) {
                case 'noRead':
                    $query = $query->whereRaw("(process_time IS NULL  and flow_run_process.transact_time is null)");
                    break;
                case 'haveRead':
                    $query = $query->whereRaw("(
                                         (host_flag != 1 AND process_flag != 1 AND saveform_time IS NULL AND process_time IS NOT NULL)
                                         OR
                                         (host_flag = 1 AND process_flag = 2)
                                        )");
                    break;
                case 'haveManage':
                    $query = $query->whereRaw("(host_flag != 1 and saveform_time is not null and process_time is not null)");
                    break;
                case 'haveSubmit':
                    $query = $query->whereRaw("((process_flag = 3 or process_flag = 4) and host_flag = 1)");
                    break;
            }
        }
        if($flowSort !== false) {
            unset($param["search"]["flow_sort"]);
            $flow_ids = $this->flowTypeRepository->entity->where('flow_sort', $flowSort)->pluck('flow_id');
            $query->whereIn('flow_run_process.flow_id', $flow_ids);
        }
        if($flowCreatorDept !== false) {
            unset($param["search"]["flow_creator_dept"]);
            $query = $query->whereHas("flowRunProcessBelongsToFlowRun.flowRunHasOneUserSystemInfo", function ($query) use($flowCreatorDept) {
                $query->whereIn('dept_id', $flowCreatorDept);
            });
        }
        if($attachmentName !== false) {
            unset($param["search"]["attachment_name"]);
        }
        if (isset($param["controlFlows"]) && !empty($param["controlFlows"])) {
             $query = $query->whereNotIn('flow_run_process.flow_id' , $param['controlFlows']);
        }
        // 按紧急程度排序，则关联紧急程度表。
        if(isset($param['order_by']['instancy_type'])){
            $query = $query->leftJoin('flow_instancys', 'flow_run.instancy_type', '=', 'flow_instancys.instancy_id');
        }
        $default = [
            'fields'     => ['*'],
            'page'       => 0,
            'limit'      => config('eoffice.pagesize'),
            'search'     => [],
            'order_by'   => ['flow_run_process.run_id' => 'desc', 'process_time'=>'desc'],
            'returntype' => 'array',
        ];
        $param = array_merge($default, array_filter($param));
        if(isset($param['order_by']['instancy_type'])){
            $param['order_by']['flow_instancys.sort'] = $param['order_by']['instancy_type'];
            unset($param['order_by']['instancy_type']);
        }
        // 手机版创建时间排序问题
        if(isset($param['order_by']['create_time'])){
            $param['order_by']['created_at'] = $param['order_by']['create_time'];
            unset($param['order_by']['create_time']);
        }
        if(isset($param['search']['flow_id'])) {
                $param['search']['flow_run_process.flow_id'] = $param['search']['flow_id'];
                unset($param['search']['flow_id']);
        }
        $query = $query
                        ->orders($param['order_by'])
                        ->select($param['fields'])
                        ->wheres($param['search'])
                        ->where('flow_run_process.is_effect','1')
                        ->with("flowRunProcessBelongsToFlowRun.flowRunHasManyFlowRunProcess")
                        ->with(["flowRunProcessHasOneUser" => function($query){
                            $query->select("user_id","user_name")->withTrashed();
                        }])
                        ->with(["flowRunProcessHasOneUserSystemInfo" => function($query){
                            $query->select("user_id","user_status")->withTrashed();
                        }])
                        ->with(["flowRunProcessHasOneFlowProcess" => function($query){
                            $query->select("process_name","node_id");
                        }])
                        ->with(["flowRunProcessBelongsToFlowType" => function($query){
                            $query->select("flow_id","flow_type" , "flow_name");
                        }])
                        // ->with(["flowRunProcessHasManyFlowRunProcess"])
                        // ->with(["flowRunProcessBelongsToFlowType.flowTypeHasOneFlowOthers" => function($query){
                        //     $query->select("flow_id","lable_show_default");
                        // }])
                        // 开始拼接超时的条件
                        ->whereNotNull("flow_run_process.limit_date")
                        ->where("flow_run_process.limit_date","!=","0000-00-00 00:00:00")
                        ->where(function($query) use($currentTime){
                            $query->where(function($query) use($currentTime){
                                $query->where("flow_run_process.host_flag","1")
                                    ->where(function($query) use($currentTime){
                                        $query->where(function($query){
                                                $query->whereNotNull("flow_run_process.deliver_time")
                                                    ->whereRaw("flow_run_process.deliver_time > flow_run_process.limit_date");
                                                })
                                            ->orWhere(function($query) use($currentTime){
                                                $query->whereNull("flow_run_process.deliver_time")
                                                    ->where("flow_run_process.limit_date", '<', $currentTime);
                                                });
                                    });
                            })
                            ->orWhere(function($query) use($currentTime){
                                $query->where("flow_run_process.host_flag","0")
                                    ->where(function($query) use($currentTime){
                                        $query->where(function($query){
                                                $query->whereNotNull("flow_run_process.saveform_time")
                                                    ->whereRaw("flow_run_process.saveform_time > flow_run_process.limit_date");
                                                })
                                            ->orWhere(function($query) use($currentTime){
                                                $query->whereNull("flow_run_process.saveform_time")
                                                    ->where("flow_run_process.limit_date", '<', $currentTime);
                                                });
                                    });
                            });
                        })
                        ;
        if(isset($param['user_id']) && $param['user_id'] != 'admin') {
            // 非admin用户 有流程监控权限或是流程创建人或是流程办理人可查看到相应的超时步骤
            $userId = $param['user_id'];
           $run_ids =  isset($param['monitor_data']['run_ids']) ? $param['monitor_data']['run_ids'] : [];
           $flow_ids =  isset($param['monitor_data']['flow_ids']) ? $param['monitor_data']['flow_ids'] : [];
            $query = $query->where(function($query) use($userId  ,$monitorParams , $run_ids ,$flow_ids) {
                  $query = $query->orWhere('flow_run_process.user_id', $userId);
                if (!empty($run_ids)) {
                   $query = $query->orWhereIn('flow_run_process.run_id',  $run_ids );
                }
                if (!empty($flow_ids)) {
                   $query = $query->orWhereIn('flow_run_process.flow_id',  $flow_ids );
                }
                        //20190812之前流程查询时当监控对象为角色时会查询不到监控数据
                        // if ($monitorParams !== false) {
                        //         if (!empty($monitorParams)) {
                        //             $query = $query->orWhere(function ($query) use ($userId , $monitorParams) {
                        //                 $flowIdArray = [];
                        //                 foreach ($monitorParams as $key => $value) {
                        //                     if (isset($value['user_id']) && $value['user_id'] == 'all') {
                        //                         $flowIdArray[] = $value['flow_id'];
                        //                     } else {
                        //                         $query = $query->orWhere(function ($query) use ($value) {
                        //                           if (isset($value['flow_id']) && !empty($value['flow_id'])) {
                        //                               if (isset($value['user_id']) && !empty($value['user_id'])) {
                        //                                   if ($value['user_id'] != 'all') {
                        //                                       $query = $query->where('flow_run_process.flow_id', $value['flow_id'])
                        //                                           ->whereIn('flow_run.creator', $value['user_id']);
                        //                                   }
                        //                               }
                        //                           }
                        //                       });
                        //                     }
                        //                 }
                        //                 if (!empty($flowIdArray)) {
                        //                     $qeury = $query->orWhereIn('flow_run_process.flow_id', $flowIdArray);
                        //                 }
                        //             });
                        //         }
                        // }
            });
            unset($userId);
        }
        // 附件查询处理
        if($attachmentName !== false) {
            // 判断`attachment_relataion_flow_run`是否存在
            if(Schema::hasTable("attachment_relataion_flow_run")) {
                $query->whereHas("flowRunProcessBelongsToFlowRun", function ($query) use($attachmentName) {
                    $query->leftJoin('attachment_relataion_flow_run', 'attachment_relataion_flow_run.run_id', '=', 'flow_run.run_id')
                          ->where("attachment_rel_search.attachment_name", "like", '%' . $attachmentName . '%')
                          ->leftJoin('attachment_rel', 'attachment_rel.attachment_id', '=', 'attachment_relataion_flow_run.attachment_id')
                          ->leftJoin('attachment_rel_search', 'attachment_rel.rel_id', '=', 'attachment_rel_search.rel_id')
                    ;
                });
            } else {
                return [];
            }
        }
        // 翻页判断
        $query = $query->parsePage($param['page'], $param['limit']);
        // 返回值类型判断
        if($param["returntype"] == "array") {
            return $query->get()->toArray();
        } else if($param["returntype"] == "count") {
            return $query->count();
        } else if($param["returntype"] == "object") {
            return $query->get();
        } else if($param["returntype"] == "onlyFlowId") {
            return $query->distinct()->pluck('flow_id');
        }
    }

    /**
     * 获取流程【超时查询】的列表数量
     *
     * @method getFlowRunProcessMyRequestList
     *
     * @param  array                          $param [description]
     *
     * @return [type]                                [description]
     */
    function getFlowRunProcessOvertimeListTotal($param = [])
    {
        $param["page"]       = "0";
        $param["returntype"] = "count";
        return $this->getFlowRunProcessOvertimeList($param);
    }

    /**
     * 【流程运行】 获取某条流程，某节点办理人总数，如果只有一个办理人，监控提交等直接跳过选择主办人
     * 参数：run_id:流程id;process_id:节点id
     *
     * @author miaochenchen
     *
     * @param  [type]              $data [description]
     *
     * @return [type]                    [description]
     */
    function getFlowMaxProcessUserCount($data)
    {
        if(isset($data['run_id']) && !empty($data['run_id']) && isset($data['process_id']) && !empty($data['process_id'])) {
            return $this->entity->select(['user_id'])->where('run_id', $data['run_id'])->where('process_id', $data['process_id'])->count();
        }else{
            return 0;
        }
    }

    /**
     * 用来从flow_run_process表里，获取某几条流程的参与人的最新步骤信息，用来更新flow_run_process表
     *
     * @method getFormatFlowRunStepNewData
     *
     * @param  [type]                      $param [description]
     *
     * @return [type]                             [description]
     */
    function getFormatFlowRunStepNewData($param)
    {
        // 必填，不填返回空
        $runIdString = isset($param["run_id"]) ? $param["run_id"]:0;
        $sql = DB::select("SET @rownum=0,@rank = 0,@pa = NULL;");
        $sql = DB::select("
                SELECT result.run_id, result.user_id,result.process_type, result.free_process_step, result.process_id, result.flow_process, result.user_run_type, result.host_flag, result.rank_field,result.process_time,result.transact_time,result.last_transact_time,result.limit_date,result.is_effect,result.flow_id
                FROM (
                    SELECT ff.run_id,ff.process_id, ff.process_type, ff.free_process_step,ff.user_id,ff.flow_process,ff.host_flag,ff.user_run_type,ff.process_time,ff.transact_time,ff.last_transact_time,ff.limit_date,ff.is_effect,ff.flow_id,@rownum:=@rownum+1 rownum ,IF(@pa=CONCAT_WS(',',run_id,user_id),@rank:=@rank+1,@rank:=1) AS rank_field
                    ,@pa:=CONCAT_WS(',',run_id,user_id)
                    FROM
                        (
                            SELECT
                            frp.run_id, frp.user_id, frp.process_type, frp.free_process_step, frp.process_id, frp.flow_process, frp.host_flag,frp.process_time,UNIX_TIMESTAMP(frp.receive_time) last_transact_time,frp.limit_date,frp.is_effect,frp.flow_id,
                            CASE
                            WHEN (
                                    ( frp.saveform_time IS NULL AND frp.host_flag = 0)
                                    OR
                                    (frp.process_flag = '2' AND frp.host_flag = 1)
                                )
                                OR frp.process_flag = '1'
                            THEN '1'
                            WHEN    fr.current_step <> 0
                                AND
                                (
                                    (frp.host_flag=1 AND frp.deliver_time IS NOT NULL)
                                    OR
                                    (frp.host_flag=0 AND frp.saveform_time IS NOT NULL)
                                )
                            THEN '2'
                            WHEN    fr.current_step = '0'
                                AND
                                (
                                    (frp.saveform_time IS NOT NULL AND frp.saveform_time <> '0000-00-00 00:00:00' AND frp.host_flag ='0')
                                    OR
                                    ( frp.deliver_time IS NOT NULL AND frp.deliver_time <> '0000-00-00 00:00:00' AND frp.host_flag ='1')
                                )
                            THEN '3'
                            ELSE '' END user_run_type,
                            CASE
                            WHEN frp.host_flag = 1
                            THEN frp.deliver_time
                            WHEN frp.host_flag = 0
                            THEN frp.saveform_time
                            ELSE '' END transact_time
                            FROM flow_run_process frp
                            INNER JOIN flow_run fr ON frp.run_id = fr.run_id
                            WHERE frp.run_id in ($runIdString)
                            -- GROUP BY run_id,user_id,process_id
                            -- ORDER BY run_id ASC,user_id DESC,process_id DESC,process_time ASC
                        ) ff
                        ,(SELECT @rank:=0,@rownum:=0,@pa=NULL) tt
                      ) result
                      ORDER BY run_id ASC,user_id DESC,user_run_type asc,host_flag desc,process_id DESC,process_time ASC,flow_process ASC
        ");
        return $sql;
    }
    // function getFormatFlowRunStepNewDataBack($param)
    // {
    //     // 必填，不填返回空
    //     $runIdString = isset($param["run_id"]) ? $param["run_id"]:0;
    //     $sql = DB::select("SET @rownum=0,@rank = 0,@pa = NULL;");
    //     $sql = DB::select("
    //         SELECT a.run_id, a.user_id, a.process_id, a.flow_process, a.user_run_type, a.host_flag ,a.process_time,a.transact_time,a.limit_date,a.is_effect,a.flow_id
    //         FROM (
    //             SELECT result.run_id, result.user_id, result.process_id, result.flow_process, result.user_run_type, result.host_flag, result.rank,result.process_time,result.transact_time,result.limit_date,result.is_effect,result.flow_id
    //             FROM (
    //                 SELECT ff.run_id,ff.process_id,ff.user_id,ff.flow_process,ff.host_flag,ff.user_run_type,ff.process_time,ff.transact_time,ff.limit_date,ff.is_effect,ff.flow_id,@rownum:=@rownum+1 rownum ,IF(@pa=CONCAT_WS(',',run_id,user_id),@rank:=@rank+1,@rank:=1) AS rank
    //                 ,@pa:=CONCAT_WS(',',run_id,user_id)
    //                 FROM
    //                     (
    //                         SELECT
    //                         frp.run_id, frp.user_id, frp.process_id, frp.flow_process, frp.host_flag,frp.process_time,UNIX_TIMESTAMP(fr.transact_time) transact_time,frp.limit_date,frp.is_effect,frp.flow_id,
    //                         CASE
    //                         WHEN (
    //                                 ( frp.saveform_time IS NULL AND frp.host_flag = 0)
    //                                 OR
    //                                 (frp.process_flag = '2' AND frp.host_flag = 1)
    //                             )
    //                             OR frp.process_flag = '1'
    //                         THEN '1'
    //                         WHEN    fr.current_step <> 0
    //                             AND
    //                             (
    //                                 (frp.host_flag=1 AND frp.deliver_time<>'NULL')
    //                                 OR
    //                                 (frp.host_flag=0 AND frp.saveform_time<>'NULL')
    //                             )
    //                         THEN '2'
    //                         WHEN    fr.current_step = '0'
    //                             AND
    //                             (
    //                                 (frp.saveform_time IS NOT NULL AND frp.saveform_time <> '0000-00-00 00:00:00' AND frp.host_flag ='0')
    //                                 OR
    //                                 ( frp.deliver_time IS NOT NULL AND frp.deliver_time <> '0000-00-00 00:00:00' AND frp.host_flag ='1')
    //                             )
    //                         THEN '3'
    //                         ELSE '' END user_run_type
    //                         FROM flow_run_process frp
    //                         INNER JOIN flow_run fr ON frp.run_id = fr.run_id
    //                         WHERE frp.run_id in ($runIdString)
    //                         -- GROUP BY run_id,user_id,process_id
    //                         ORDER BY run_id ASC,user_id DESC,process_id DESC,process_time ASC
    //                     ) ff
    //                     ,(SELECT @rank:=0,@rownum:=0,@pa=NULL) tt
    //                   ) result
    //             HAVING result.rank <=1
    //         ) a
    //     ");
    //     return $sql;
    // }

    /**
     * 用来从flow_run_process表里，获取某个流程某个步骤的主办人ID
     *
     * @method getHostUserIdByRunIdAndProcessId
     *
     * @param  [string]                      $runId，$processId [流程运行ID，步骤ID]
     *
     * @return [type]                             [description]
     */
    function getHostUserIdByRunIdAndProcessId($runId, $processId, $flowProcess = null)
    {
        if(empty($flowProcess)){
            $query = $this->entity->select(['user_id'])->where('run_id', $runId)
                                                   ->where('process_id', $processId)
                                                   ->where('host_flag', 1)
                                                   ->first();
        } else {
            $query = $this->entity->select(['user_id'])->where('run_id', $runId)
                                                   ->where('flow_process', $$flowProcess)
                                                   //->where('process_id', $processId)
                                                   ->where('host_flag', 1)
                                                   ->first();
        }
        if (!empty($query) && $query->user_id) {
            return $query->user_id;
        } else {
            return '';
        }
    }
     /**
     * 用来从flow_run_process表里，获取某个流程某个步骤的主办人ID
     *
     * @method getHostUserIdByRunIdAndProcessId
     *
     * @param  [string]                      $runId，$processId [流程运行ID，步骤ID]
     *
     * @return [type]                             [description]
     */
    function getHostInfoByRunIdAndProcessId($runId, $processId, $flowProcess = null)
    {
        if(empty($flowProcess)){
            return $query = $this->entity->select(['user_id'])->where('run_id', $runId)
                                                   ->where('process_id', $processId)
                                                   ->where('host_flag', 1)
                                                   ->first();
        } else {
            return $query = $this->entity->select(['user_id'])->where('run_id', $runId)
                                                   ->where('flow_process', $$flowProcess)
                                                   //->where('process_id', $processId)
                                                   ->where('host_flag', 1)
                                                   ->first();
        }
    }
    /**
     * 获取委托数据
     *
     */
    function getFlowDoneAgencyUserInfo($runId,$user) {
        $query = $this->entity->whereIn('run_id',$runId)->whereNotNull('by_agent_id')->whereIn('user_id',$user);
        return $query->get();
    }
    /**
     * 获取运行中流程办理人
     *
     */
    function getFlowRunUserList($flowId,$user) {
        $query = $this->entity->leftJoin('flow_run', 'flow_run.run_id', '=', 'flow_run_process.run_id')->select(['flow_run_process.flow_run_process_id', 'flow_run_process.user_id','flow_run_process.run_id','flow_run_process.flow_id','flow_run_process.flow_process', 'flow_run_process.process_id', 'flow_run_process.flow_run_process_id']);
        if (is_array($flowId) && count($flowId) > 1000) {
            $chunks = array_chunk($flowId, 1000);
            $query = $query->where(function ($query) use ($chunks) {
                foreach ($chunks as $ch) {
                    $query = $query->orWhereIn('flow_run_process.flow_id',$ch);
                }
            });
            unset($chunks);
            unset($flowId);
        }else{
            if($flowId !== 'all') {
                $query = $query->whereIn('flow_run_process.flow_id',$flowId);
            }
        }
        if (is_array($user) && count($user) > 1000) {
            $_chunks = array_chunk($user, 1000);
            $query = $query->where(function ($query) use ($_chunks) {
                foreach ($_chunks as $ch) {
                    $query = $query->orWhereIn('flow_run_process.user_id',$ch);
                }
            });
            unset($_chunks);
            unset($user);
        }else{
            $query = $query->whereIn('flow_run_process.user_id',$user);
        }
        $query = $query->where('flow_run.current_step','!=',0)->whereRaw("CASE WHEN flow_run_process.host_flag = 1
                                THEN flow_run_process.deliver_time IS NULL ELSE flow_run_process.saveform_time IS NULL END");
        return $query->get();
    }
    /**
     * 获取已结束流程办理人
     *
     */
    function getFlowRunDoneUserList($flowId,$user) {
        $query = $this->entity->leftJoin('flow_run', 'flow_run.run_id', '=', 'flow_run_process.run_id')->select(['flow_run_process.user_id','flow_run_process.run_id','flow_run_process.flow_id','flow_run_process.flow_process', 'flow_run_process.process_id', 'flow_run_process.flow_run_process_id', 'flow_run_process.user_run_type']);
        if (is_array($flowId) && count($flowId) > 1000) {
            $chunks = array_chunk($flowId, 1000);
            $query = $query->where(function ($query) use ($chunks) {
                foreach ($chunks as $ch) {
                    $query = $query->orWhereIn('flow_run_process.flow_id',$ch);
                }
            });
            unset($chunks);
            unset($flowId);
        }else{
            if($flowId !== 'all') {
                $query = $query->whereIn('flow_run_process.flow_id',$flowId);
            }
        }
        if (is_array($user) && count($user) > 1000) {
            $_chunks = array_chunk($user, 1000);
            $query = $query->where(function ($query) use ($_chunks) {
                foreach ($_chunks as $ch) {
                    $query = $query->orWhereIn('flow_run_process.user_id',$ch);
                }
            });
            unset($_chunks);
            unset($user);
        }else{
            $query = $query->whereIn('flow_run_process.user_id',$user);
        }
        $query = $query->where('flow_run.current_step','=',0)->where('flow_run_process.replace_flag','!=','replace');
        return $query->get();
    }
    // 获取最新自由节点流转步骤信息
    public function getRunCurrentFreeNodeStep($runId,$nodeId) {
        $query =  $this->entity;
        return $query->select('free_process_step')->where('run_id',$runId)->where('flow_process',$nodeId)->where('process_type','free')->orderBy('process_id','desc')->first();
    }
    // 获取自由节点流转步骤信息
    public function getRunFreeNodeStep($runId,$nodeId,$stepId) {
        $query =  $this->entity;
        return $query->where('run_id',$runId)->where('flow_process',$nodeId)->where('process_type','free')->where('host_flag',1)->orderBy('process_id','desc')->first();
    }
    // 获取运行步骤表最大的步骤ID
    public function getFlowRunProcessMaxProcessIdByRunId($runId)
    {
        return $this->entity->where('run_id', $runId)->max('process_id');
    }


     /**
     * 报表-流程报表-流程办理效率分析
     *
     *
     * @param  $datasourceGroupBy 分组依据; $where 数据过滤
     *
     *
     * @return array
     */
     public function getFlowHandleEfficiencyGroupByCustomType($datasourceGroupBy, $where="")
     {
        if ($datasourceGroupBy == 'user') {
                $query  = app('App\EofficeApp\User\Entities\UserEntity');
                $query  = $query->select(['user.user_name as name' , 'user.user_id'])
                                ->with(['userHasManyFlowRunProcess' => function($query) use ($where) {
                                    $query->select([ 'user_id','receive_time'  ,'process_time','deliver_time' , 'saveform_time' ,'host_flag','flow_run_process.is_effect' ]);
                                    //流程选择器过滤
                                    if (isset($where['flowID'])) {
                                       $query->whereIn('flow_run_process.flow_id' , explode(',', $where['flowID']));
                                    }
                                    //流程类型过滤
                                    if ( isset($where['flowsortId']) && !empty($where['flowsortId'])) {
                                            $query->leftJoin('flow_type' , function($join) use($where) {
                                                  $join->on('flow_run_process.flow_id' , '=' ,'flow_type.flow_id' );
                                            });
                                            $query->whereIn('flow_type.flow_sort',$where['flowsortId']);
                                    }
                                    //流程创建时间过滤
                                    if (isset($where['date_range'])) {
                                           $query->leftJoin('flow_run' , function($join) {
                                                  $join->on('flow_run.run_id' , '=' ,'flow_run_process.run_id' );
                                            });
                                            $dateRange = explode(',', $where['date_range']);
                                            if (isset($dateRange[0]) && !empty($dateRange[0])) {
                                                $query->whereRaw("flow_run.created_at >= '" . ($dateRange[0] . " 00:00:00'"));
                                            }
                                            if (isset($dateRange[1]) && !empty($dateRange[1])) {
                                                $query->whereRaw("flow_run.created_at <= '" . ($dateRange[1] . " 23:59:59'"));
                                            }
                                    }
                                }]);
                if (isset($where['userIds']) &&  is_array($where['userIds']) ) {
                    $query->whereIn('user.user_id', $where['userIds']);
                }
                $result =  $query->get();
        } else if($datasourceGroupBy == 'userDept') {
                $query  = app('App\EofficeApp\System\Department\Entities\DepartmentEntity');
                $query  = $query->select(['department.dept_name as name'  ,'department.dept_id as dept_id' ])
                                ->with(['departmentHasManyUser' => function($query) use($where){
                                   $query->select(['user_id' , 'dept_id']);
                                   $query->with(['userHasManyFlowRunProcess' => function($query) use($where){
                                        $query->select([ 'user_id','receive_time'  ,'process_time','deliver_time' , 'saveform_time' ,'host_flag' ,'flow_run_process.is_effect']);
                                        //流程选择器过滤
                                        if (isset($where['flowID'])) {
                                           $query->whereIn('flow_run_process.flow_id' , explode(',', $where['flowID']));
                                        }
                                        //流程类型过滤
                                    if ( isset($where['flowsortId']) && !empty($where['flowsortId'])) {
                                            $query->leftJoin('flow_type' , function($join) use($where) {
                                                  $join->on('flow_run_process.flow_id' , '=' ,'flow_type.flow_id' );
                                            });
                                            $query->whereIn('flow_type.flow_sort',$where['flowsortId']);
                                    }
                                         //创建时间过滤
                                         if (isset($where['date_range'])) {
                                              $query->leftJoin('flow_run' , function($join) {
                                                    $join->on('flow_run.run_id' , '=' ,'flow_run_process.run_id' );
                                              });
                                              $dateRange = explode(',', $where['date_range']);
                                              if (isset($dateRange[0]) && !empty($dateRange[0])) {
                                                  $query->whereRaw("flow_run.created_at >= '" . ($dateRange[0] . " 00:00:00'"));
                                              }
                                              if (isset($dateRange[1]) && !empty($dateRange[1])) {
                                                  $query->whereRaw("flow_run.created_at <= '" . ($dateRange[1] . " 23:59:59'"));
                                              }
                                        }
                                        if (isset($where['userIds']) &&  is_array($where['userIds']) ) {
                                            $query->whereIn('flow_run_process.user_id', $where['userIds']);
                                        }
                                   }]);
                                }]);
                if (isset($where['dept_id']) && !empty($where['dept_id'])) {
                    $query->whereIn('department.dept_id', explode(',', $where['dept_id']));
                }
                $result =  $query->get();

        }else if($datasourceGroupBy == 'userRole') {
                $query  = app('App\EofficeApp\Role\Entities\RoleEntity');
                $query  = $query->select(['role_name as name' , 'role_id'])
                                ->with(['roleHasManyUser' => function($query) use($where){
                                    $query->select(['user_id' , 'role_id']);
                                    $query->with(['userHasManyFlowRunProcess' => function($query) use($where){
                                          $query->select([ 'user_id','receive_time'  ,'process_time','deliver_time' , 'saveform_time' ,'host_flag' ,'flow_run_process.is_effect']);
                                          //流程选择器过滤
                                          if (isset($where['flowID'])) {
                                             $query->whereIn('flow_run_process.flow_id' , explode(',', $where['flowID']));
                                          }
                                          //流程类型过滤
                                          if ( isset($where['flowsortId']) && !empty($where['flowsortId'])) {
                                                  $query->leftJoin('flow_type' , function($join) use($where) {
                                                        $join->on('flow_run_process.flow_id' , '=' ,'flow_type.flow_id' );
                                                  });
                                                  $query->whereIn('flow_type.flow_sort',$where['flowsortId']);
                                          }
                                           //创建时间过滤
                                           if (isset($where['date_range'])) {
                                                $query->leftJoin('flow_run' , function($join) {
                                                       $join->on('flow_run.run_id' , '=' ,'flow_run_process.run_id' );
                                                 });
                                                 $dateRange = explode(',', $where['date_range']);
                                                 if (isset($dateRange[0]) && !empty($dateRange[0])) {
                                                     $query->whereRaw("flow_run.created_at >= '" . ($dateRange[0] . " 00:00:00'"));
                                                 }
                                                 if (isset($dateRange[1]) && !empty($dateRange[1])) {
                                                     $query->whereRaw("flow_run.created_at <= '" . ($dateRange[1] . " 23:59:59'"));
                                                 }
                                            }
                                          if (isset($where['userIds']) &&  is_array($where['userIds']) ) {
                                            $query->whereIn('flow_run_process.user_id', $where['userIds']);
                                          }
                                    }]);
                                }]);

                if (isset($where['role_id']) && !empty($where['role_id'])) {
                    $query->whereIn('role.role_id',  explode(',', $where['role_id']));
                }
                $result =  $query->get();
        } else {
             $result = [];
        }

        if(!empty($result)) {
                return $result->toArray();
        } else {
                return array();
        }
     }

     /**
     * 报表-流程报表-流程办理超期分析(分组依据：用户，部门，角色)
     *
     *
     * @param  $datasourceGroupBy 分组依据; $where 数据过滤
     *
     *
     * @return array
     */
     public function getFlowHandleLimitCountGroupByCustomType($datasourceGroupBy, $where="")
     {
        if ($datasourceGroupBy == 'user') {
                $query  = app('App\EofficeApp\User\Entities\UserEntity');
                $query  = $query->select(['user.user_name as name' , 'user.user_id'])
                                ->with(['userHasManyFlowRunProcess' => function($query) use ($where) {
                                    $query->select([ 'user_id','flow_run_process.run_id' ,'host_flag' , 'saveform_time' , 'limit_date' , 'deliver_time' , 'flow_run_process.is_effect'])
                                                  ->whereRaw('limit_date is not null and limit_date<>"0000-00-00 00:00:00"');
                                    //流程选择器过滤
                                    if (isset($where['flowID'])) {
                                       $query->whereIn('flow_run_process.flow_id' , explode(',', $where['flowID']));
                                    }
                                    //流程类型过滤
                                    if ( isset($where['flowsortId']) && !empty($where['flowsortId'])) {
                                            $query->leftJoin('flow_type' , function($join) use($where) {
                                                  $join->on('flow_run_process.flow_id' , '=' ,'flow_type.flow_id' );
                                            });
                                            $query->whereIn('flow_type.flow_sort',$where['flowsortId']);
                                    }
                                    //流程创建时间过滤
                                    if (isset($where['date_range'])) {
                                           $query->leftJoin('flow_run' , function($join) {
                                                  $join->on('flow_run.run_id' , '=' ,'flow_run_process.run_id' );
                                            });
                                            $dateRange = explode(',', $where['date_range']);
                                            if (isset($dateRange[0]) && !empty($dateRange[0])) {
                                                $query->whereRaw("flow_run.created_at >= '" . ($dateRange[0] . " 00:00:00'"));
                                            }
                                            if (isset($dateRange[1]) && !empty($dateRange[1])) {
                                                $query->whereRaw("flow_run.created_at <= '" . ($dateRange[1] . " 23:59:59'"));
                                            }
                                    }
                                }]);
                if (isset($where['userIds']) &&  is_array($where['userIds']) ) {
                    $query->whereIn('user.user_id', $where['userIds']);
                }
                $result =  $query->get();
        } else if($datasourceGroupBy == 'userDept') {
                $query  = app('App\EofficeApp\System\Department\Entities\DepartmentEntity');
                $query  = $query->select(['department.dept_name as name'  ,'department.dept_id as dept_id' ])
                                ->with(['departmentHasManyUser' => function($query) use($where){
                                   $query->select(['user_id' , 'dept_id']);
                                   $query->with(['userHasManyFlowRunProcess' => function($query) use($where){
                                            $query->select([ 'user_id','flow_run_process.run_id' ,'host_flag' , 'saveform_time' , 'limit_date' , 'deliver_time','flow_run_process.is_effect'])
                                                  ->whereRaw('limit_date is not null and limit_date<>"0000-00-00 00:00:00"');

                                        //流程选择器过滤
                                        if (isset($where['flowID'])) {
                                           $query->whereIn('flow_run_process.flow_id' , explode(',', $where['flowID']));
                                        }
                                        //流程类型过滤
                                        if ( isset($where['flowsortId']) && !empty($where['flowsortId'])) {
                                                $query->leftJoin('flow_type' , function($join) use($where) {
                                                      $join->on('flow_run_process.flow_id' , '=' ,'flow_type.flow_id' );
                                                });
                                                $query->whereIn('flow_type.flow_sort',$where['flowsortId']);
                                        }
                                        //创建时间过滤
                                         if (isset($where['date_range'])) {
                                              $query->leftJoin('flow_run' , function($join) {
                                                    $join->on('flow_run.run_id' , '=' ,'flow_run_process.run_id' );
                                              });
                                              $dateRange = explode(',', $where['date_range']);
                                              if (isset($dateRange[0]) && !empty($dateRange[0])) {
                                                  $query->whereRaw("flow_run.created_at >= '" . ($dateRange[0] . " 00:00:00'"));
                                              }
                                              if (isset($dateRange[1]) && !empty($dateRange[1])) {
                                                  $query->whereRaw("flow_run.created_at <= '" . ($dateRange[1] . " 23:59:59'"));
                                              }
                                        }
                                        if (isset($where['userIds']) &&  is_array($where['userIds']) ) {
                                            $query->whereIn('flow_run_process.user_id', $where['userIds']);
                                        }
                                   }]);
                                }]);
                if (isset($where['dept_id']) && !empty($where['dept_id'])) {
                    $query->whereIn('department.dept_id', explode(',', $where['dept_id']));
                }
                $result =  $query->get();

        }else if($datasourceGroupBy == 'userRole') {
                $query  = app('App\EofficeApp\Role\Entities\RoleEntity');
                $query  = $query->select(['role_name as name' , 'role_id'])
                                ->with(['roleHasManyUser' => function($query) use($where){
                                    $query->select(['user_id' , 'role_id']);
                                    $query->with(['userHasManyFlowRunProcess' => function($query) use($where){
                                            $query->select([ 'user_id','flow_run_process.run_id' ,'host_flag' , 'saveform_time' , 'limit_date' , 'deliver_time' , 'flow_run_process.is_effect'])
                                                  ->whereRaw('limit_date is not null and limit_date<>"0000-00-00 00:00:00"');
                                            //流程选择器过滤
                                              if (isset($where['flowID'])) {
                                                 $query->whereIn('flow_run_process.flow_id' , explode(',', $where['flowID']));
                                              }
                                              //流程类型过滤
                                              if ( isset($where['flowsortId']) && !empty($where['flowsortId'])) {
                                                      $query->leftJoin('flow_type' , function($join) use($where) {
                                                            $join->on('flow_run_process.flow_id' , '=' ,'flow_type.flow_id' );
                                                      });
                                                      $query->whereIn('flow_type.flow_sort',$where['flowsortId']);
                                              }
                                           //创建时间过滤
                                            if (isset($where['date_range'])) {
                                                $query->leftJoin('flow_run' , function($join) {
                                                       $join->on('flow_run.run_id' , '=' ,'flow_run_process.run_id' );
                                                 });
                                                 $dateRange = explode(',', $where['date_range']);
                                                 if (isset($dateRange[0]) && !empty($dateRange[0])) {
                                                     $query->whereRaw("flow_run.created_at >= '" . ($dateRange[0] . " 00:00:00'"));
                                                 }
                                                 if (isset($dateRange[1]) && !empty($dateRange[1])) {
                                                     $query->whereRaw("flow_run.created_at <= '" . ($dateRange[1] . " 23:59:59'"));
                                                 }
                                            }
                                          if (isset($where['userIds']) &&  is_array($where['userIds']) ) {
                                            $query->whereIn('flow_run_process.user_id', $where['userIds']);
                                          }
                                    }]);
                                }]);

                if (isset($where['role_id']) && !empty($where['role_id'])) {
                    $query->whereIn('role.role_id',  explode(',', $where['role_id']));
                }
                $result =  $query->get();
        } else {
             $result = [];
        }

        if(!empty($result)) {
                return $result->toArray();
        } else {
                return array();
        }
     }


    /**
     * 获取具体的一条 flow_run_process 数据
     * @param $flow_run_process_id
     * @return mixed
     */
     public function getFlowRunProcessDetail($flow_run_process_id)
     {
         return $this->entity->find($flow_run_process_id);
     }

     public function getNeedSubmitProcess($runId,$processId,$flowProcess){
        $result = [];
        $where = ['run_id'=>$runId,'process_id'=>$processId,'host_flag'=>1];
        $dbData = $this->entity->select('flow_process','origin_process','origin_user','process_id','flow_run_process_id','user_id','host_flag','is_effect','flow_id','deliver_time')->where($where)->get()->toArray();
        if (count($dbData) >0) {
            $originProcess = 0;
            foreach($dbData as $v){
                if ($v['flow_process'] == $flowProcess){
                    $originProcess = $v['origin_process'];
                    break;
                }
            }
            if (!empty($originProcess)) {
                foreach($dbData as $k => $v){
                    if (empty($v['deliver_time']) || $v['deliver_time'] == '0000-00-00 00:00:00') {
                        if ($v['origin_process'] == $originProcess && $v['flow_process'] != $flowProcess) {
                            if (!in_array($v['flow_process'],$result)){
                                $result[$v['flow_process']] =  $v['flow_process'];
                            }
                        }
                    }
                }
            }
        }
        return $result;
    }

    public function getFlowRunProcessInfo($field,$where,$with = null){
    	if (empty($with)) {
            $flowRunProcess = $this->entity->select($field)->where($where)->get()->toArray();
    	} else {
    		$flowRunProcess = $this->entity->select($field)->with($with)->where($where)->get()->toArray();
    	}
    	return $flowRunProcess;
    }
    /**
     * 根据流程 id 和用户 id 获取某个用户某个流程处于待办的 flow_run_process 数据
     * @param $flowId
     * @param $userId
     * @param bool $overFlag 是否已结束
     * @return mixed
     */
    public function getTodoDataByUserId($flowId, $userId, $overFlag = true)
    {
        $builder = $this->entity->where([
            ['flow_run_process.flow_id', '=', $flowId],
            ['flow_run_process.user_id', '=', $userId]
        ]);
        $whereRaw = "CASE WHEN flow_run_process.host_flag = 1
        THEN flow_run_process.deliver_time IS NULL ELSE flow_run_process.saveform_time IS NULL END";
        if ($overFlag) { // 为 true ,则返回所有的待办记录，经办人和主办人，不管是否已完成
           return $builder->whereRaw($whereRaw)->get();
        }
        return $builder->leftJoin('flow_run', 'flow_run.run_id', '=', 'flow_run_process.run_id')
        ->where('flow_run.current_step', '<>', 0)->whereRaw($whereRaw)->get();
    }

    /**
     * 根据 flow_id 、run_id 获取未办理的 user 信息
     * @param $flowId
     * @param $runId
     */
    public function getToDoUserInfo($flowId, $runId)
    {
        return $this->entity->select(['flow_run_process.user_id', 'user.user_name', 'user_system_info.user_status'])->leftJoin('user', 'user.user_id', '=', 'flow_run_process.user_id')
            ->leftJoin('user_system_info', 'user_system_info.user_id', '=', 'flow_run_process.user_id')
            ->where([
            ['run_id', '=', $runId],
            ['flow_id', '=', $flowId]
        ])->where('user_run_type', 1)->get();
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
    function getFlowRunProcessId($flowRunProcessInfo)
    {
        $flowRunProcessId = 0;
        $where = ["run_id" => $flowRunProcessInfo['run_id'],"user_id" => $flowRunProcessInfo['user_id'],"flow_process" => $flowRunProcessInfo['flow_process'],"process_id" => $flowRunProcessInfo['process_id'],"host_flag" => $flowRunProcessInfo['host_flag']];
        $flowRunInfo = $this->entity->where($where)->get();
        if (!empty($flowRunInfo)){
            if (isset($flowRunInfo[0])) {
                $flowRunInfo = $flowRunInfo[0];
                $flowRunProcessId = $flowRunInfo['flow_run_process_id'];
            }
        }
        return $flowRunProcessId;
    }

    /**
     * 获取待办已办办结流程的列表
     * @param $param
     * @return mixed
     */
    function getFlowRunHandleFlowList($param)
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

        $query = $query->with(["flowRunProcessHasOneSendBackUser" => function($query){
            $query->select("user_id","user_name")->withTrashed();
        }]);
        // 委托详情
        $query = $query->with(["flowRunProcessHasManyAgencyDetail" => function($query){
            $query->select(['*'])->orderby('sort');
            $query->with(["flowRunProcessAgencyDetailHasOneAgentUser" => function($query){
                $query->select("user_id","user_name")->withTrashed();
            }]);
            $query->with(["flowRunProcessAgencyDetailHasOneUser" => function($query){
                $query->select("user_id","user_name")->withTrashed();
            }]);
        }]);
        // 关联监控提交人信息
        $query = $query->with(["flowRunProcessHasOneMonitorSubmitUser" => function($query) {
            $query->select("user_id","user_name")->withTrashed();
        }]);
        if ($runSeq !== false) {
            unset($param["search"]["run_seq"]);
            $runSeq = str_replace(' ', '%', $runSeq);
            $query = $query->whereHas("flowRunProcessBelongsToFlowRun", function ($query) use ($runSeq) {
                $query->where('flow_run.run_seq_strip_tags', 'like', '%' . $runSeq . '%');
            });
        }
        if ($runName !== false) {
            unset($param["search"]["run_name"]);
            $query = $query->whereHas("flowRunProcessBelongsToFlowRun", function ($query) use ($runName) {
                $runNameSql = $this->flowRunRepository->getBlankSpaceRunName($runName);
                $query->whereRaw($runNameSql);
            });
        }
        if ($processTime !== false) {
            unset($param["search"]["process_time"]);
            if ($processTime == "unread") {
                $query = $query->whereNull('flow_run_process.process_time');
            } else if ($processTime == "read") {
                $query = $query->whereNotNull('flow_run_process.process_time');
            }
        }
        if ($instancyType !== false) {
            unset($param["search"]["instancy_type"]);
            $query = $query->whereHas("flowRunProcessBelongsToFlowRun", function ($query) use ($instancyType) {
                $query->where('instancy_type', $instancyType);
            });
        }
        if ($craeteTime !== false) {
            unset($param["search"]["create_time"]);
            $query = $query->whereHas("flowRunProcessBelongsToFlowRun", function ($query) use ($craeteTime) {
                $query->wheres(["create_time" => $craeteTime]);
            });
        }
        if ($limitDate !== false) {
            unset($param["search"]["limit_date"]);
            $query = $query->whereHas("flowRunProcessBelongsToFlowRun", function ($query) use ($limitDate) {
                $query->wheres(["limit_date" => $limitDate]);
            });
        }
        if ($acceptDate !== false) {
            unset($param["search"]["acceptdate"]);
            $query = $query->where(function ($query) use ($acceptDate) {
                $query->wheres(["flow_run_process.receive_time" => $acceptDate])
                    ->whereNotNull("flow_run_process.receive_time");
            });
        }
        // 判断是否按紧急程度排序，如果有则关联紧急程度表。
        if (isset($param['order_by']['instancy_type'])) {
            $param['order_by']['flow_instancys.sort'] = $param['order_by']['instancy_type'];
            unset($param['order_by']['instancy_type']);
            $query = $query->leftJoin('flow_run', 'flow_run_process.run_id', '=', 'flow_run.run_id')
                ->leftJoin('flow_instancys', 'flow_run.instancy_type', '=', 'flow_instancys.instancy_id');
        }
        if ($flowCreatorId !== false) {
            unset($param["search"]["flow_creator_id"]);
            $query = $query->whereHas("flowRunProcessBelongsToFlowRun", function ($query) use ($flowCreatorId) {
                $query->whereIn('flow_run.creator', $flowCreatorId);
            });
        }
        if ($flowCreatorDept !== false) {
            unset($param["search"]["flow_creator_dept"]);
            $query = $query->whereHas("flowRunProcessBelongsToFlowRun.flowRunHasOneUserSystemInfo", function ($query) use ($flowCreatorDept) {
                $query->whereIn('dept_id', $flowCreatorDept);
            });
        }

        if ($currentStep !== false) {
            $query = $query->whereHas("flowRunProcessBelongsToFlowRun", function ($query) use ($currentStep, $param) {
                if (isset($param["search"]["current_step"]["1"]) && $param["search"]["current_step"]["1"] == "!=") {
                    $query->where('current_step', "!=", $currentStep);
                } else {
                    $query->where('current_step', $currentStep);
                }
            });
            unset($param["search"]["current_step"]);
        }
        if ($title !== false) {
            $query = $query->whereHas("flowRunProcessBelongsToFlowType", function ($query) use ($title, $param) {
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
             $query = $query->whereNotIn('flow_run_process.flow_id' , $param['controlFlows']);
        }
        $default = [
            'fields' => ['flow_run_process.*'],
            'page' => 0,
            'limit' => config('eoffice.pagesize'),
            'search' => [],
            'order_by' => ['flow_run_process.transact_time' => 'desc'],
            'returntype' => 'array',
        ];
        $param = array_merge($default, array_filter($param));
        $flowRunStepTableColumns = array_flip($this->getTableColumns());
        if (count($param["search"])) {
            $dataSearchParams = $this->flowRunRepository->addTablePrefixByTableColumns(["data" => $param["search"], "table" => "flow_run_process", "tableColumnsFlip" => $flowRunStepTableColumns]);
            $param["search"] = $dataSearchParams;
        }

        if (isset($param['flow_module_factory']) && $param['flow_module_factory'] && isset($param['form_id'])) {
            // 关联zzzz表的数据，用于模块工厂的模块的列表字段
            if ($param['form_id'] && Schema::hasTable('zzzz_flow_data_' . $param['form_id']) && $param['returntype'] != 'count') {
                $param['fields'][] = 'zzzz_flow_data_' . $param['form_id'] . '.*';
                $query = $query->join('zzzz_flow_data_' . $param['form_id'], 'flow_run_process.run_id', '=', 'zzzz_flow_data_' . $param['form_id'] . '.run_id');
            }
        }

        $query = $query->select($param['fields'])
            ->wheres($param['search'])
            ->with(["flowRunProcessHasOneUser" => function ($query) {
                $query->select("user_id", "user_name")->withTrashed();
            }])
            ->where("flow_run_process.user_id", $userId)
            ->where("flow_run_process.is_effect", '1')
            ->with(['flowRunProcessBelongsToFlowRun' => function ($query) use ($param) {
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
                    ->with(['flowRunHasManyFlowRunProcess' => function ($query) use ($param) {
                        $query->select("flow_run_process_id", 'run_id', 'process_id', 'sub_flow_run_ids', 'process_type', 'free_process_step', 'flow_process', 'is_back',
                            "flow_serial", "branch_serial", "process_serial", "process_flag", "host_flag", "by_agent_id", "origin_process", "user_id", "flow_id", 'user_run_type', "outflow_process", "origin_process_id", 'saveform_time')
                            ->with(['flowRunProcessHasOneFlowProcess' => function ($query) {
                                $query->select('node_id', 'process_name', 'trigger_son_flow_back', 'merge', "concurrent");
                            }])->orders(['process_id' => 'desc', 'host_flag' => 'desc']);;
                            if (!isset($param['getListType']) || $param['getListType'] != 'todo') {
                              $query->with(['flowRunProcessHasOneUser' => function ($query) {
                                $query->select('user_id', 'user_name')->with(['userHasOneSystemInfo' => function ($query) {
                                    $query->select('user_id', 'user_status')->withTrashed();
                                }])->withTrashed();
                              }]);
                            }
                    }]);
            }])
            // 流程所在节点的信息--暂时只有待办里面这个字段有意义
            ->with(["flowRunProcessHasOneFlowProcess" => function ($query) {
                $query->select("node_id", "flow_id", 'merge', "process_id", "process_name", "head_node_toggle", "end_workflow", "process_transact_type", "press_add_hour", "process_concourse", "process_copy", "process_entrust", "overtime_except_nonwork", "concurrent");
            }]);
        // 已办事宜--高级查询--当前人员，处理
        if ($currentUser !== false) {
            $currentStepHandleUserJoinRunIdList = $this->entity->leftJoin('flow_run', function ($join) {
                    $join->on('flow_run_process.run_id', '=', 'flow_run.run_id')
                        ->on('flow_run_process.process_id', '=', 'flow_run.max_process_id');
                })->whereIn("flow_run_process.user_id", $currentUser)->get()->pluck('run_id')->toArray();
            if (empty($currentStepHandleUserJoinRunIdList)) {
                return [];
            } else {
                $query = $query->whereIn('flow_run_process.run_id', $currentStepHandleUserJoinRunIdList);
            }
            // $query->whereHas("flowRunProcessBelongsToFlowRun", function ($query) use ($currentUser) {
            //     $query->leftJoin('flow_run_process', function ($join) {
            //         $join->on('flow_run_process.run_id', '=', 'flow_run.run_id')
            //             ->on('flow_run_process.process_id', '=', 'flow_run.max_process_id');
            //     });
            //     $query->whereIn("flow_run_process.user_id", $currentUser);
            // });
        }
        // 排序处理
        if (!isset($param['order_by']['create_time'])) {
            if (count($param["order_by"])) {
                if (isset($param['order_by']['transact_time'])) {
                    $query->orderBy('flow_run_process.transact_time', $param['order_by']['transact_time']);
                }else if (isset($param['order_by']['flow_run.transact_time'])) {
                    $query->leftJoin('flow_run', 'flow_run_process.run_id', '=', 'flow_run.run_id')
                        ->orderBy('flow_run.transact_time', $param['order_by']['flow_run.transact_time']);
                } else {
                    $dataSearchParams = $this->flowRunRepository->addTablePrefixByTableColumns(["data" => $param["order_by"], "table" => "flow_run_process", "tableColumnsFlip" => $flowRunStepTableColumns]);
                    $param["order_by"] = $dataSearchParams;
                    // 20200316,zyx,列表上默认排序为上一节点提交时间，增加run_id逆序，保证子流程始终在主流程后面显示
                    if (!in_array('flow_run_process.run_id', $param["order_by"]) && isset($param["order_by"]['receive_time'])) {
                        $param["order_by"]['flow_run_process.run_id'] = 'desc';
                    }
                    $query->orders($param['order_by']);
                }
            }
        } else {
            $query->addSelect('flow_run_process.transact_time as transact_time');
            $query->leftJoin('flow_run', 'flow_run_process.run_id', '=', 'flow_run.run_id')
                ->orderBy('flow_run.create_time', $param['order_by']['create_time']);
        }
        $query->where("user_last_step_flag", "1");
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
                $query->whereHas("flowRunProcessBelongsToFlowRun", function ($query) use ($attachmentName) {
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
     * @param array $param
     * @return array|int|mixed
     */
    function getFlowRunHandleFlowListTotal($param = [])
    {
        $userId = isset($param["user_id"]) ? $param["user_id"] : "";
        if ($userId == "") {
            return 0;
        }
        $param["page"] = "0";
        $param["returntype"] = "count";
        return $this->getFlowRunHandleFlowList($param);
    }
    function getHangupList($userId)
    {
        return $this->entity->select(['flow_run_process_id', 'flow_id', 'run_id', 'user_id', 'cancel_hangup_time', 'process_time', 'process_id'])->where(["user_id" => $userId, "hangup" => 1])->get()->toArray();
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
                               ->selectRaw("count(flow_run_process.flow_step_id) as y")
                               // ->leftJoin('flow_run_process', 'user.user_id', '=', 'flow_run_process.user_id')
                               ->leftJoin('flow_run_process' , function($join) use($where){
                                   $join->on('user.user_id' , '=' ,'flow_run_process.user_id' )
                                       ->where('flow_run_process.user_run_type' , 1)
                                       ->where('flow_run_process.is_effect' , 1)
                                       ->where('flow_run_process.user_last_step_flag' , 1)
                                       ->whereRaw('flow_run_process.deleted_at is null');
                                   //流程名称过滤
                                   if (isset($where['flowID'])) {
                                           $join->whereIn('flow_run_process.flow_id',explode(',', $where['flowID']));
                                   }
                                   //流程类型过滤
                                   if ( isset($where['flowsortId'])  && !empty($where['flowsortId'])) {
                                           $join->leftJoin('flow_type' , function($join) use($where) {
                                                 $join->on('flow_run_process.flow_id' , '=' ,'flow_type.flow_id' );
                                           });
                                           $join->whereIn('flow_type.flow_sort',$where['flowsortId']);
                                   }
                                   //创建时间过滤
                                   if (isset($where['date_range'])) {
                                          $join->leftJoin('flow_run' , function($join) {
                                                 $join->on('flow_run.run_id' , '=' ,'flow_run_process.run_id' );
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
                               ->selectRaw("count(flow_run_process.flow_step_id) as y")
                               ->leftJoin('user_system_info' , function($join) {
                                   $join->on('user_system_info.dept_id' , '=' ,'department.dept_id' );
                               })
                               ->leftJoin('flow_run_process' , function($join) use($where){
                                   $join->on('user_system_info.user_id' , '=' ,'flow_run_process.user_id' )
                                       ->where('flow_run_process.user_run_type' , 1)
                                       ->where('flow_run_process.is_effect' , 1)
                                       ->where('flow_run_process.user_last_step_flag' , 1)
                                       ->whereRaw('flow_run_process.deleted_at is null');
                                   //流程名称过滤
                                   if (isset($where['flowID']) ) {
                                           $join->whereIn('flow_run_process.flow_id',explode(',', $where['flowID']));
                                   }
                                   if (isset($where['date_range'])) {
                                          $join->leftJoin('flow_run' , function($join) {
                                                 $join->on('flow_run.run_id' , '=' ,'flow_run_process.run_id' );
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
                                       $join->whereIn('flow_run_process.user_id', $where['userIds']);
                                   }
                                   //流程类型过滤
                                   if ( isset($where['flowsortId'])  && !empty($where['flowsortId'])) {
                                           $join->leftJoin('flow_type' , function($join) use($where) {
                                                 $join->on('flow_run_process.flow_id' , '=' ,'flow_type.flow_id' );
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
                               ->selectRaw("count(flow_run_process.flow_step_id) as y")
                               ->leftJoin('user_role' , function($join) {
                                   $join->on('user_role.role_id' , '=' ,'role.role_id' );
                               })
                               ->leftJoin('flow_run_process' , function($join) use($where) {
                                   $join->on('user_role.user_id' , '=' ,'flow_run_process.user_id' )
                                       ->where('flow_run_process.user_run_type' , 1)
                                       ->where('flow_run_process.is_effect' , 1)
                                       ->where('flow_run_process.user_last_step_flag' , 1)
                                       ->whereRaw('flow_run_process.deleted_at is null');
                                   //流程名称过滤
                                   if (isset($where['flowID'])  ) {
                                           $join->whereIn('flow_run_process.flow_id',explode(',', $where['flowID']));
                                   }
                                   if (isset($where['date_range'])) {
                                          $join->leftJoin('flow_run' , function($join) {
                                                 $join->on('flow_run.run_id' , '=' ,'flow_run_process.run_id' );
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
                                       $join->whereIn('flow_run_process.user_id', $where['userIds']);
                                   }
                                   //流程类型过滤
                                   if ( isset($where['flowsortId']) && !empty($where['flowsortId'])  ) {
                                           $join->leftJoin('flow_type' , function($join) use($where) {
                                                 $join->on('flow_run_process.flow_id' , '=' ,'flow_type.flow_id' );
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
    function getFlowRunStepInfo($flowRunProcessId)
    {
        return $this->entity->where(["flow_run_process_id" => $flowRunProcessId])->where(["user_last_step_flag" => 1])->first();
	}
	/*
     * 通过run_id、flow_id获取流程运行节点外发记录
     *
     * @author  zyx 20200807重写
     * @param   mixed   $param
     * @return  array
     */
    public function getFlowOutsendList($param)
    {
        $run_id = $param['run_id'];
        $flow_id = $param['flow_id'];

        $query = $this->entity;
        // 获取各节点信息
        $result = $query
            ->select('flow_run_process_id', 'process_id', 'run_id', 'flow_process', 'flow_serial', 'branch_serial', 'process_serial')
            ->with(['flowRunProcessHasOneFlowProcess' => function ($query) {
                $query->select('node_id', 'sort', 'process_name')
                    ->with(['flowProcessHasManyOutsend' => function ($query) {
                        $query->select('id', 'node_id', 'custom_module_menu')
                              ->orderBy('id', 'asc');
                    }]);
            }])
            // ->with('flowRunProcessHasManyFlowOutsendLog')
            ->where('flow_id', '=', $flow_id)
            ->where('run_id', '=', $run_id)
            ->orderBy('flow_run_process_id', 'asc')
            ->get()
            ->toArray();

        return $result;
    }

    /**
     * 获取强制合并节点前未办理的分支（或节点）运行信息
     * @param $param
     * @return mixed
     */
    public function getFlowRunForceMergeInfo($param)
    {
        return $this->entity->
        where('flow_serial' , $param['flow_serial'])->
        where('run_id', $param['run_id'])->
        with(["flowRunProcessHasOneUser" => function ($query) {
            $query->select("user_id", "user_name")->withTrashed()->with(['userHasOneSystemInfo' => function ($query) {
                $query->select('user_id', 'user_status');
            }]);
        }])->where("flow_run_process.is_effect", '1')->where('user_run_type', 1)
            ->with(['flowRunProcessBelongsToFlowRun' => function ($query) {
                $query->select('run_id', 'flow_id', 'run_name', 'run_seq', 'run_seq_strip_tags', 'creator', 'max_process_id', 'current_step', 'create_time', 'transact_time', 'instancy_type')
                    ->with(['FlowRunHasOneFlowType' => function ($query) {
                        $query->select('flow_id', 'flow_name', 'flow_sort', 'flow_type', 'form_id', 'countersign', 'handle_way','press_add_hour' , 'overtime_except_nonwork')
                            ->with(['flowTypeHasOneFlowOthers' => function ($query) {
                                $query->select('flow_id', 'first_node_delete_flow', 'flow_to_doc', 'file_folder_id', 'flow_submit_hand_remind_toggle','flow_submit_hand_overtime_toggle');
                            }])
                            ->with(["flowTypeBelongsToFlowSort" => function ($query) {
                                $query->select("title", "id");
                            }]);
                    }]);
            }])->with(['flowRunProcessHasOneFlowProcess' => function ($query) {
                $query->select('node_id', 'sort', 'process_name', 'merge', 'process_to', 'process_transact_type', 'branch', 'origin_node', 'merge_node' , 'concurrent');
            }])->get();
    }

    /**
     * 根据步骤id,节点,流程号，分支号获取已经存在的办理人
     * @param $runId
     * @param $processId
     * @param $flowProcess
     * @param $flowSerial
     * @param $branchSerial
     * @return mixed
     */
    public function getExistHandleUsers($runId, $processId, $flowProcess, $flowSerial, $branchSerial)
    {
        return $this->entity->where([
            'run_id' => $runId,
            'process_id' => $processId,
            'flow_process' => $flowProcess,
            'flow_serial' => $flowSerial,
            'branch_serial' => $branchSerial
        ])->pluck('flow_run_process_id','user_id')->all();
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
        $query = $query->leftJoin('user_system_info', 'user_system_info.user_id', '=', 'flow_run_process.user_id');
        $query = $query->leftJoin('flow_run', 'flow_run.run_id', '=', 'flow_run_process.run_id');
        $query = $query->leftJoin('user', 'user.user_id', '=', 'user_system_info.user_id')
            ->select(['flow_run_process.flow_id', 'user.user_name','flow_run_process.user_run_type', 'flow_run_process.user_id', DB::raw('COUNT(flow_run_process.run_id) as count_run_process'),DB::raw('GROUP_CONCAT(flow_run.run_name) as count_run_process_all')])
            ->where("flow_run_process.flow_id", $id)
            ->where('flow_run_process.user_run_type', '<>', 2)
            ->where('flow_run_process.user_last_step_flag', 1)
            ->where("user_system_info.user_status", 2)
            ->groupBy('flow_run_process.user_id');
        return $query->get()->toArray();
    }
}
