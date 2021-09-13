<?php
namespace App\EofficeApp\Flow\Repositories;

use App\EofficeApp\Flow\Entities\FlowRunOverTimeEntity;
use App\EofficeApp\Base\BaseRepository;

/**
 * 流程报表表知识库
 *
 * @author wz
 *
 * @since  2019-9-30 创建
 */
class FlowRunOverTimeRepository extends BaseRepository
{
    public function __construct(FlowRunOverTimeEntity $entity) {
        parent::__construct($entity);
    }

    /**
     * 一个基础的函数，用来根据各种条件获取flow_run_overtime表的数据
     *
     * @method getFlowRunOvertimeList
     *
     * @param  array                 $param [description]
     *
     * @return [type]                       [description]
     */
    function getFlowRunOvertimeList($param = [])
    {
        $default = [
            'fields'     => ['*'],
            'page'       => 0,
            'limit'      => config('eoffice.pagesize'),
            'search'     => [],
            'order_by'   => ['process_id'=>'asc'],
            'returntype' => 'object',
        ];
        $param = array_merge($default, array_filter($param));
        $query = $this->entity
                      ->wheres($param['search'])
                      ->with(['FlowRunOverTimeBelongsToFlowRun' => function($query){
                             $query->select("run_id","run_name" , 'is_effect');
                      }])
                      ->with(["FlowRunOverTimeHasOneFlowProcess" => function($query){
                               $query->select('limit_skip_holiday_toggle','node_id');
                      }])
                      ->with(["FlowRunOverTimeBelongsToFlowType" => function($query){
                               $query->select('limit_skip_holiday_toggle','flow_id' , 'flow_type');
                      }])
                      ->with(['FlowRunOverTimeHasManyFlowRunProcess' => function($query){
                            $raw = " user_run_type = 1 and user_last_step_flag = 1";
                            $query->whereRaw( $raw );
                            $query->orderBy('host_flag', "desc");
                            $query->select("run_id", "user_id", "process_id", "flow_process", "host_flag", "process_flag", "process_time", "saveform_time", "deliver_time" , 'flow_id' , 'limit_date' , 'flow_run_process_id');

                      }]);
        // 分组参数
        if(isset($param['groupBy'])) {
            $query = $query->groupBy($param['groupBy']);
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
        // 关联用户 systeminfo 表
        if(isset($param["relationUserSystemInfo"])) {
            $query = $query->with(["flowRunProcessHasOneUserSystemInfo" => function($query){
                                $query->select("user_id","user_status")->withTrashed();
                            }]);
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
     * 更新flow_run_Overtime的数据，这里默认是多维的where条件。
     *
     * @method updateFlowRunProcessData
     *
     * @param  array                    $param [data:数据;wheres:可以批量解析的条件;whereRaw:原生解析的条件]
     *
     * @return [type]                          [description]
     */
    function updateFlowRunOvertimeData($param = [])
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

}
