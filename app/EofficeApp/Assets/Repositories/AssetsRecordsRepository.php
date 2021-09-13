<?php

namespace App\EofficeApp\Assets\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Assets\Entities\AssetsRecordsEntity;
use DB;

/**
 * 资产类型列表
 *
 * @author zw
 *
 * @since  2018-03-30 创建
 */
class AssetsRecordsRepository extends BaseRepository
{

    public function __construct(AssetsRecordsEntity $entity)
    {
        parent::__construct($entity);
    }


    /**
     * 履历total
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
     * 履历清单
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
            'order_by' => ['create_time' => 'desc'],
        ];
        $params = array_merge($default, array_filter($params));
        $query = $this->entity->select($params['fields']);
        return $query->wheres($params['search'])->orders($params['order_by'])->parsePage($params['page'], $params['limit'])->get()->toArray();
    }

}