<?php

namespace App\EofficeApp\Assets\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Assets\Entities\AssetsInventoryRecordsEntity;
use DB;

/**
 * 资产类型列表
 *
 * @author zw
 *
 * @since  2018-03-30 创建
 */
class AssetsInventoryRecordsRepository extends BaseRepository
{

    public function __construct(AssetsInventoryRecordsEntity $entity)
    {
        parent::__construct($entity);
    }


    /**
     * 盘点记录total
     *
     * @param  array
     *
     * @return bool|object
     *
     * @author zhangwei
     *
     * @since  2018-08-28
     */
    public function recordDataTotal($data){
       return $this->entity->wheres($data['search'])->get()->count();
    }

    /**
     * 盘点记录
     *
     * @param  array
     *
     * @return bool|object
     *
     * @author zhangwei
     *
     * @since  2018-08-28
     */
    public function recordData($data){
        $default = [
            'fields'   => ['*'],
            'page'     => 0,
            'limit'    => config('eoffice.pagesize'),
            'search'   => [],
            'order_by' => ['created_at' => 'desc'],
        ];
        $params = array_merge($default, array_filter($data));
        $query = $this->entity->select($params['fields'])->wheres($params['search']);
        $query = $query->with(['assets'=>function($query){
            $query->select('id','assets_name','assets_code','managers');
        }]);
        $query = $query->with(['assets_type'=>function($query){
            $query->select('id','type_name');
        }]);
        return $query->parsePage($params['page'], $params['limit'])->get()->toArray();
    }

    /**
     * 获取盘点内资产
     *
     * @param  array
     *
     * @return bool|object
     *
     * @author zhangwei
     *
     * @since  2018-08-28
     */
    public function getInventory($where){
        return $this->entity->where($where)->first();
    }



    /**
     * 删除盘点内资产
     *
     * @param  array
     *
     * @return bool|object
     *
     * @author zhangwei
     *
     * @since  2018-08-28
     */

    public function deleteRecords($inventory_id){
        return $this->entity->where('inventory_id',$inventory_id)->delete();
    }



}