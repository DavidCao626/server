<?php
namespace App\EofficeApp\Flow\Repositories;

use App\EofficeApp\Flow\Entities\FlowFavoriteEntity;
use App\EofficeApp\Base\BaseRepository;

/**
 * 流程控件的“控件分组”表知识库
 *
 * @author 丁鹏
 *
 * @since  2015-10-16 创建
 */
class FlowFavoriteRepository extends BaseRepository
{
    public function __construct(FlowFavoriteEntity $entity) {
        parent::__construct($entity);
    }

    /**
     * 获取流程收藏列表
     *
     * @method getFlowFavoriteList
     *
     * @param  array                 $param [description]
     *
     * @return [type]                       [description]
     */
    function getFlowFavoriteList($param = [])
    {
        $user_id = isset($param["search"]["user_id"]) ? $param["search"]["user_id"][0]:false;
        $default = [
            'fields'     => ['*'],
            'page'       => 0,
            'limit'      => config('eoffice.pagesize'),
            'search'     => [],
            'order_by'   => ['favorite_id'=>'asc'],
            'returntype' => 'object',
        ];
        $param = array_merge($default, array_filter($param));
        $query = $this->entity
                        ->select($param['fields'])
                        ->wheres($param['search'])
                        ->orders($param['order_by'])
                        ->with(['flowFavoriteHasOneFormType' => function($query) {
                            $query->select('flow_id','flow_name');
                        }]);
                        // ->with(['flowFavoriteHasManyFlowRun' => function($query) use($user_id) {
                        //     $query->select('flow_id','run_id',"create_time","creator")
                        //     ->where("creator",$user_id)
                        //     ->orderBy("create_time","desc")
                        //     // ->limit("1")
                        //     ;
                        // }]);
        if (isset($param['controlFlows']) && !empty($param['controlFlows'])) {
            $query = $query->whereNotIn('flow_id' , $param['controlFlows']);
        }
        // 翻页判断
        // $query = $query->parsePage($param['page'], $param['limit']);
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
}
