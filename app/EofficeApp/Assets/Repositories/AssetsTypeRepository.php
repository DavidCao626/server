<?php

namespace App\EofficeApp\Assets\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Assets\Entities\AssetsTypeEntity;

/**
 * 资产类型列表
 *
 * @author zw
 *
 * @since  2018-03-30 创建
 */
class AssetsTypeRepository extends BaseRepository
{

    public function __construct(AssetsTypeEntity $entity)
    {
        parent::__construct($entity);
    }

    /**
     * 获取资产类型列表
     *
     * @param  array
     *
     * @return bool|object
     *
     * @author zhangwei
     *
     * @since  2018-03-29
     */
    public function getType($params = null)
    {
        $query = $this->entity->select('*')->where(['deleted_at'=>null]);
        if(isset($params['search']) && $params['search']){
            $query->wheres($params['search']);
        }
        return $query->get()->toArray();

    }

    /**
     * 新建资产类型
     *
     * @param  array
     *
     * @return bool|object
     *
     * @author zhangwei
     *
     * @since  2018-03-29
     */
    public function creatType($params)
    {
        return $this->entity->insertGetId($params);

    }
    /**
     * 资产类型total
     *
     * @param  array
     *
     * @return bool|object
     *
     * @author zhangwei
     *
     * @since  2018-03-29
     */
    public function typeListTotal($params){
        $query = $this->entity->where(['deleted_at'=>null]);
        if(isset($params['search']) && $params['search']){
            return $query->wheres($params['search'])->count();
        }
        return $query->get()->count();
    }

    /**
     * 资产类型list
     *
     * @param  array
     *
     * @return bool|object
     *
     * @author zhangwei
     *
     * @since  2018-03-29
     */
    public function typeList($params){
        $default = [
            'fields'   => ['*'],
            'page'     => 0,
            'limit'    => config('eoffice.pagesize'),
            'search'   => [],
            'order_by' => ['id' => 'asc'],
        ];
        $params = array_merge($default, array_filter($params));
        $query = $this->entity->select($params['fields'])->wheres($params['search'])->where(['deleted_at'=>null]);
        return $query->orders($params['order_by'])->parsePage($params['page'], $params['limit'])->get()->toArray();
    }

    /**
     * 资产类型详情
     *
     * @param  array
     *
     * @return bool|object
     *
     * @author zhangwei
     *
     * @since  2018-03-29
     */
    public function getTypeData($type){
        return $this->entity->where('type',$type)->first();
    }


}