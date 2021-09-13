<?php
namespace App\EofficeApp\Flow\Repositories;

use App\EofficeApp\Flow\Entities\FlowAgencyDetailEntity;
use App\EofficeApp\Base\BaseRepository;

/**
 * 流程委托表知识库
 *
 * @author 丁鹏
 *
 * @since  2015-10-16 创建
 */
class FlowAgencyDetailRepository extends BaseRepository
{
    public function __construct(FlowAgencyDetailEntity $entity) {
        parent::__construct($entity);
    }

    /**
     * 一个基础的函数，用来根据各种条件获取flow_agency_detail表的数据
     *
     * @method getFlowRunProcessList
     *
     * @param  array                 $param [description]
     *
     * @return [type]                       [description]
     */
    function getFlowAgencyDetailList($param = [])
    {
        $query    = $this->entity;
        $flowName = isset($param["search"]["flow_name"]) ? $param["search"]["flow_name"][0]:false;
        $flowSort = isset($param["search"]["flow_sort"]) ? $param["search"]["flow_sort"][0]:false;
        $flowId   = isset($param["search"]["flow_id"]) ? $param["search"]["flow_id"][0]:false;
        $default = [
            'fields'    => ['*'],
            'page'      => 0,
            'limit'     => config('eoffice.pagesize'),
            'search'    => [],
            'order_by'  => ['agency_detail_id'=>'asc'],
            'returntype' => 'object',
        ];
        if (isset($param["controlFlows"]) && !empty($param["controlFlows"])) {
             $query = $query->whereNotIn('flow_id' , $param['controlFlows']);
        }
        $param = array_merge($default, array_filter($param));
        $query = $query
                      ->select($param['fields'])
                      // 预防查询了固定字段报错
                      ->addSelect("flow_agency.start_time","flow_agency.end_time")
                      ->orders($param['order_by'])
                      ->leftJoin('flow_agency', 'flow_agency.flow_agency_id', '=', 'flow_agency_detail.flow_agency_id')
                      // ->wheres($param['search'])
                      ->whereHas("flowAgencyDetailBelongsToFlowAgency", function ($query) use($flowName,$flowSort,$flowId,$param) {
                            if($flowName !== false) {
                                unset($param["search"]["flow_name"]);
                            }
                            if($flowSort !== false) {
                                unset($param["search"]["flow_sort"]);
                            }
                            if($flowId !== false) {
                                unset($param["search"]["flow_id"]);
                            }
                            // 正常查询参数
                            $query->wheres($param['search']);
                            // 列表的逻辑默认参数，从service里传来的
                            if(isset($param['init_search'])){
                                $query->wheres($param['init_search']);
                            }
                            if(isset($param['whereRaw'])) {
                                $query = $query->whereRaw($param['whereRaw']);
                            }
                      })
                      ->with(["flowAgencyDetailBelongsToFlowAgency" => function ($query) {
                            $query->with(["flowAgencyHasOneAgentUser" => function ($query) {
                                $query->select("user_id","user_name")->withTrashed();;
                            }])
                            ->with(["flowAgencyHasOneByAgentUser" => function ($query) {
                                $query->select("user_id","user_name")->withTrashed();;
                            }]);
                      }])
                      ->whereHas("flowAgencyDetailHasOneFlowType", function ($query) use($flowName,$flowSort,$flowId,$param) {
                            if($flowName !== false) {
                                $query->where('flow_name', 'like' ,'%'.$flowName.'%');
                            }
                            if($flowId !== false) {
                                $query->where('flow_id', $flowId);
                            }
                            if($flowSort !== false) {
                                $query->where('flow_sort', $flowSort);
                            }
                            $query->where('hide_running',0);
                      })
                      ->with(["flowAgencyDetailHasOneFlowType" => function ($query) {
                            $query->select("flow_id","flow_name","flow_sort")
                                ->with(["flowTypeBelongsToFlowSort" => function ($query) {
                                    $query->select("id","title");
                                }]);
                                // ->where('is_using','1');
                      }]);
        // 翻页判断
        $query = $query->parsePage($param['page'], $param['limit']);
        // 返回值类型判断
        if($param["returntype"] == "array") {
            return $query->get()->toArray();
        } else if($param["returntype"] == "count") {
            return $query->count();
        } else if($param["returntype"] == "object") {
            return $query->get();
        }
    }

    /**
     * 委托规则的列表数量
     *
     * @method getFlowRunProcessList
     *
     * @param  array                 $param [description]
     *
     * @return [type]                       [description]
     */
    function getFlowAgencyDetailListTotal($param = [])
    {
        $param["page"]       = "0";
        $param["returntype"] = "count";
        return $this->getFlowAgencyDetailList($param);
    }
    /**
     * 获取节点默认主办人
     *
     */
    function getFlowAgencyInfo($flowId) {
        $query = $this->entity;
        if (is_array($flowId) && count($flowId) > 1000) {
            $chunks = array_chunk($flowId, 1000);
            $query = $query->where(function ($query) use ($chunks) {
                foreach ($chunks as $ch) {
                    $query = $query->orWhereIn('flow_id',$ch);
                }
            });
            unset($chunks);
            unset($flowId);
        }else{
            $query = $query->whereIn('flow_id',$flowId);
        }
        return $query->get();
    }
}
