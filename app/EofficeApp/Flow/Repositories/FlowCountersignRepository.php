<?php
namespace App\EofficeApp\Flow\Repositories;

use App\EofficeApp\Flow\Entities\FlowCountersignEntity;
use App\EofficeApp\Base\BaseRepository;

/**
 * 流程会签表知识库
 *
 * @author 丁鹏
 *
 * @since  2015-10-16 创建
 */
class FlowCountersignRepository extends BaseRepository
{
    public function __construct(FlowCountersignEntity $entity) {
        parent::__construct($entity);
    }

    /**
     * 获取流程会签list
     *
     * @method getCountersign
     *
     * @param  array                 $param [description]
     *
     * @return [type]                       [description]
     */
    function getCountersign($param = [])
    {
        $default = [
            'fields'     => ['flow_countersign.*','user.user_name','user.user_id','user.list_number'],
            'page'       => 0,
            'limit'      => config('eoffice.pagesize'),
            'search'     => [],
            'order_by'   => ['countersign_time'=>'desc'],
            'returntype' => 'array',
        ];
        $param = array_merge($default, array_filter($param));
        $query = $this->entity;
        $query = $query->leftJoin('user', 'user.user_id', '=', 'flow_countersign.countersign_user_id');
        $query = $query->wheres($param['search'])
                        ->orders($param['order_by'])
                        ->with(["countersignUser" => function($query) {
                              $query->select("user_id","user_name","list_number")
                              // 20180522-获取部门相关信息
                              ->with(["userHasOneSystemInfo" => function($query) {
                                    $query->select("user_id","dept_id")
                                    ->with(["userSystemInfoBelongsToDepartment" => function($query) {
                                          $query->select("dept_id","dept_name");
                                    }]);
                              }])
                              // 20180522-暂时不需要角色相关信息，先注释，需要的时候再放开
                              ->with(["userHasManyRole" => function($query) {
                                    $query->select("user_id","role_id")
                                    ->with(["hasOneRole" => function($query) {
                                          $query->select("role_id","role_name");
                                    }]);
                              }])
                              ->withTrashed();
                        }]);
        // 解析原生 select
        if(isset($param['selectRaw'])) {
            foreach ($param['selectRaw'] as $key => $selectRaw) {
                $query = $query->selectRaw($selectRaw);
            }
        } else {
            $query = $query->select($param['fields']);
        }
        // 解析原生 where
        if(isset($param['whereRaw'])) {
            foreach ($param['whereRaw'] as $key => $whereRaw) {
                $query = $query->whereRaw($whereRaw);
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
        } else if($param["returntype"] == "first") {
            return $query->first();
        }
    }

    /**
     * 获取流程会签list
     *
     * @method getCountersignTotal
     *
     * @param  array                 $param [description]
     *
     * @return [type]                       [description]
     */
    function getCountersignTotal($param = [])
    {
        $param["page"]       = "0";
        $param["returntype"] = "count";
        return $this->getCountersign($param);
    }
     /**
     * 更新FlowCountersign的数据，这里默认是多维的where条件。
     *
     * @method FlowCountersign
     *
     * @param  array                    $param [data:数据;wheres:可以批量解析的条件;whereRaw:原生解析的条件]
     *
     * @return [type]                          [description]
     */
    function updateFlowCountersignData($param = [])
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
