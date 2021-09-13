<?php
namespace App\EofficeApp\Flow\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Flow\Entities\FlowCopyEntity;
use App\EofficeApp\Flow\Repositories\FlowRunRepository;
use Schema;

/**
 * 流程抄送表知识库
 *
 * @author 丁鹏
 *
 * @since  2015-10-16 创建
 */
class FlowCopyRepository extends BaseRepository
{
    public function __construct(
        FlowCopyEntity $entity,
        FlowRunRepository $flowRunRepository
    )
    {
        parent::__construct($entity);
        $this->flowRunRepository = $flowRunRepository;
    }

    /**
     * 一个基础的函数，用来根据各种条件获取flow_copy表的数据
     *
     * @method getFlowCopyList
     *
     * @param  array                 $param [description]
     *
     * @return [type]                       [description]
     */
    public function getFlowCopyList($param = [])
    {
        $query           = $this->entity;
        $runSeq          = isset($param["search"]["run_seq"]) ? $param["search"]["run_seq"][0] : false;
        $runName         = isset($param["search"]["run_name"]) ? $param["search"]["run_name"][0] : false;
        $receiveTime     = isset($param["search"]["receive_time"]) ? $param["search"]["receive_time"][0] : false;
        $currentStep     = isset($param["search"]["current_step"]) ? $param["search"]["current_step"][0] : false;
        $craeteTime      = isset($param["search"]["create_time"]) ? $param["search"]["create_time"] : false;
        $instancyType    = isset($param["search"]["instancy_type"]) ? $param["search"]["instancy_type"][0] : false;
        $flowCreatorId   = isset($param["search"]["flow_creator_id"]) ? $param["search"]["flow_creator_id"][0] : false;
        $flowCreatorDept = isset($param["search"]["flow_creator_dept"]) ? $param["search"]["flow_creator_dept"][0] : false;
        $flowSort        = isset($param["search"]["flow_sort"]) ? $param["search"]["flow_sort"][0] : false;
        $attachmentName  = isset($param["search"]["attachment_name"]) ? $param["search"]["attachment_name"][0] : false;

        if ($runSeq !== false) {
            unset($param["search"]["run_seq"]);
            $runSeq = str_replace(' ', '%', $runSeq);
            $query = $query->whereHas("flowCopyHasOneFlowRun", function ($query) use ($runSeq) {
                $query->where('run_seq_strip_tags', 'like', '%' . $runSeq . '%');
            });
        }
        if ($runName !== false) {
            unset($param["search"]["run_name"]);
            $query = $query->whereHas("flowCopyHasOneFlowRun", function ($query) use ($runName) {
                $runNameSql = $this->flowRunRepository->getBlankSpaceRunName($runName);
                $query->whereRaw($runNameSql);
            });
        }
        if ($receiveTime !== false) {
            unset($param["search"]["receive_time"]);
            if ($receiveTime == "unread") {
                $query = $query->whereNull('receive_time');
            } else if ($receiveTime == "read") {
                $query = $query->whereNotNull('receive_time');
            }
        }
        if ($currentStep !== false) {
            $query = $query->whereHas("flowCopyHasOneFlowRun", function ($query) use ($currentStep, $param) {
                if (isset($param["search"]["current_step"]["1"]) && $param["search"]["current_step"]["1"] == "!=") {
                    $query->where('current_step', "!=", $currentStep);
                } else {
                    $query->where('current_step', $currentStep);
                }
            });
            unset($param["search"]["current_step"]);
        }
        if ($craeteTime !== false) {
            unset($param["search"]["create_time"]);
            $query = $query->whereHas("flowCopyHasOneFlowRun", function ($query) use ($craeteTime) {
                $query->wheres(["create_time" => $craeteTime]);
            });
        }
        if ($instancyType !== false) {
            unset($param["search"]["instancy_type"]);
            $query = $query->whereHas("flowCopyHasOneFlowRun", function ($query) use ($instancyType) {
                $query->where('instancy_type', $instancyType);
            });
        }
        if ($flowCreatorId !== false) {
            unset($param["search"]["flow_creator_id"]);
            $query = $query->whereHas("flowCopyHasOneFlowRun", function ($query) use ($flowCreatorId) {
                $query->whereIn('creator', $flowCreatorId);
            });
        }
        if ($flowCreatorDept !== false) {
            unset($param["search"]["flow_creator_dept"]);
            $query = $query->whereHas("flowCopyHasOneFlowRun.flowRunHasOneUserSystemInfo", function ($query) use ($flowCreatorDept) {
                $query->whereIn('dept_id', $flowCreatorDept);
            });
        }
        if ($flowSort !== false) {
            $query = $query->whereHas("flowCopyBelongsToFlowType", function ($query) use ($flowSort, $param) {
                $query->where('flow_sort', $flowSort);
            });
            unset($param["search"]["flow_sort"]);
        }
        if ($attachmentName !== false) {
            unset($param["search"]["attachment_name"]);
        }
        if (isset($param['controlFlows']) && !empty($param['controlFlows'])) {
            $query = $query->whereNotIn('flow_copy.flow_id' , $param['controlFlows']);
        }

        $query = $query->leftJoin('flow_run', 'flow_run.run_id', '=', 'flow_copy.run_id');
        $default = [
            'fields'     => ['flow_copy.*'],
            'page'       => 0,
            'limit'      => config('eoffice.pagesize'),
            'search'     => [],
            'order_by'   => ['copy_id' => 'desc'],
            'returntype' => 'array',
        ];
        $param = array_merge($default, array_filter($param));
        // 按紧急程度排序
        if(isset($param['order_by']['instancy_type'])){
            $param['order_by']['flow_instancys.sort'] = $param['order_by']['instancy_type'];
            unset($param['order_by']['instancy_type']);
            $query = $query->leftJoin('flow_instancys', 'flow_run.instancy_type', '=', 'flow_instancys.instancy_id');
        }
        // 手机版创建时间排序问题
        if(isset($param['order_by']['create_time'])){
            $param['order_by']['flow_run.create_time'] = $param['order_by']['create_time'];
            unset($param['order_by']['create_time']);
        }
        if(isset($param['search']['run_id'])){
            $searchRunId = ['flow_copy.run_id' => $param['search']['run_id']];
            $query = $query->wheres($searchRunId);
            unset($param['search']['run_id']);
        }
        // 解决关联flow_run表之后找不到flow_id报错问题
        if(isset($param['search']['flow_id'])) {
            $param['search']['flow_copy.flow_id'] = $param['search']['flow_id'];
            unset($param['search']['flow_id']);
        }
        $query = $query
            ->select($param['fields'])
            ->multiWheres($param['search'])
            ->orders($param['order_by'])
            // ->with(["flowCopyBelongsToFlowType.flowTypeHasOneFlowOthers" => function($query){
            //     $query->select("flow_id","lable_show_default");
            // }])
            ->with(["flowCopyBelongsToFlowType" => function ($query) {
                $query->select('flow_id', 'flow_type', 'flow_name', 'form_id')->where("hide_running", "=", '0');
            }])
            ->whereHas('flowCopyBelongsToFlowType', function ($query) {
                $query->where("hide_running", "=", '0');
            })
            ->with(["flowCopyHasOneFlowRun" => function ($query) {
                $query->select('max_process_id', 'flow_id', 'current_step', 'run_id', 'run_name', 'run_seq', 'run_seq_strip_tags' , 'instancy_type','creator' ,'create_time');
            }])
            // ->with("flowCopyHasOneFlowRun.flowRunHasManyFlowRunProcess")
            ->with(["flowCopyHasOneUser" => function ($query) {
                $query->select("user_id", "user_name")->withTrashed();
            }]);
        if (isset($param['run_id'])) {
            $query = $query->where('run_id', $param['run_id']);
        }
        // 解析被抄送人
        if (isset($param['by_user_id'])) {
            $query = $query->where('by_user_id', $param['by_user_id']);
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
        // 附件查询处理
        if ($attachmentName !== false) {
            // 判断`attachment_relataion_flow_run`是否存在
            if (Schema::hasTable("attachment_relataion_flow_run")) {
                $query->whereHas("flowCopyHasOneFlowRun", function ($query) use ($attachmentName) {
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
     * 获取抄送列表的抄送流程数量
     *
     * @method getFlowCopyTotal
     *
     * @param  array            $param [description]
     *
     * @return [type]                  [description]
     */
    public function getFlowCopyTotal($param = [])
    {
        $param["page"]       = "0";
        $param["returntype"] = "count";
        return $this->getFlowCopyList($param);
    }

    /**
     * 更新flow_copy的数据，这里默认是多维的where条件。
     *
     * @method updateFlowCopyData
     *
     * @param  array                    $param [data:数据;wheres:可以批量解析的条件;whereRaw:原生解析的条件]
     *
     * @return [type]                          [description]
     */
    public function updateFlowCopyData($param = [])
    {
        $data  = $param["data"];
        $query = $this->entity->wheres($param["wheres"]);
        // 解析原生 where
        if (isset($param['whereRaw'])) {
            foreach ($param['whereRaw'] as $key => $whereRaw) {
                $query = $query->whereRaw($whereRaw);
            }
        }
        // 解析 whereIn
        if (isset($param['whereIn'])) {
            foreach ($param['whereIn'] as $key => $whereIn) {
                $query = $query->whereIn("run_id", $whereIn);
            }
        }
        return (bool) $query->update($data);
    }

    /**
     * 更新flow_copy的数据，这里默认是多维的where条件。
     *
     * @method updateFlowCopyData
     *
     * @param  array                    $param [data:数据;wheres:可以批量解析的条件;whereRaw:原生解析的条件]
     *
     * @return [type]                          [description]
     */
    public function getFlowCopyFlowProcess($param = [])
    {
        $query           = $this->entity;
        $default = [
            'fields'     => ['*'],
            'page'       => 0,
            'limit'      => config('eoffice.pagesize'),
            'search'     => [],
            'order_by'   => ['flow_copy.process_id' => 'desc'],
        ];
        $param = array_merge($default, array_filter($param));
        if (count($param['fields'])>0) {
        	foreach($param['fields'] as $k => $v){
        		if ($v == 'flow_process') {
        			unset($param['fields'][$k]);
        			$param['fields'][$k] = 'flow_copy.flow_process';
        		}
        	}
        }
        $query = $query
            ->select($param['fields'])
            ->wheres($param['search'])
            ->orders($param['order_by'])
            ->leftJoin('flow_run_process' , function($join) {
                   $join->on('flow_run_process.run_id' , '=' ,'flow_copy.run_id' );
                   $join->on('flow_run_process.process_id' , '=' ,'flow_copy.process_id' );
            });

        return $query->first();
    }



}
