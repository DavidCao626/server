<?php

namespace App\EofficeApp\Assets\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Assets\Entities\AssetsApplyEntity;
use App\EofficeApp\Assets\Entities\AssetsInventoryEntity;
use DB;

/**
 * 资产类型列表
 *
 * @author zw
 *
 * @since  2018-03-30 创建
 */
class AssetsInventoryRepository extends BaseRepository
{

    public function __construct(AssetsInventoryEntity $entity)
    {
        parent::__construct($entity);
    }



    /**
     * Total
     *
     * @param  array
     *
     * @return bool|object
     *
     * @author zhangwei
     *
     * @since  2018-03-29
     */
    public function listsTotal($params){
        return $this->entity->wheres($params['search'])->count();
    }


    /**
     * 盘点列表
     *
     * @param  array
     *
     * @return bool|object
     *
     * @author zhangwei
     *
     * @since  2018-03-29
     */
    public function lists($params){
        $default = [
            'fields'   => ['*'],
            'page'     => 0,
            'limit'    => config('eoffice.pagesize'),
            'search'   => [],
            'order_by' => ['assets_inventory.created_at' => 'desc'],
        ];
        $params = array_merge($default, array_filter($params));
        $query = $this->entity->select($params['fields'])->wheres($params['search']);
        $query = $query->with(['hasOneUser'=>function($query){
            $query->select('user_name','user_id');
        }]);
        $query = $query->orders($params['order_by'])->parsePage($params['page'], $params['limit'])->get()->toArray();
        return $query;
    }


    /**
     * 申请记录详情
     *
     * @param  array
     *
     * @return bool|object
     *
     * @author zhangwei
     *
     * @since  2018-06-06
     */
    public function ApplyDetail($id){
        $query = $this->entity->select('*');
        $query = $query->where('assets_apply.id',$id)->with(['assets'=>function($query){
            $query->select('id','assets_name');
        }]);
        $query = $query->with(['assets'=>function($query){
            $query->select('id','assets_name','managers');
        }]);
        $query->with(['applyBelongsToUserSystemInfo' => function($query) {
            $query->with(['userSystemInfoBelongsToDepartment' => function($query) {
                $query->select('dept_id', 'dept_name');
            }]);
        }]);
        $query->with(['applyBelongsToUser' => function($query) {
            $query->select('*');
        }]);
        return $query->get()->first();
    }





}