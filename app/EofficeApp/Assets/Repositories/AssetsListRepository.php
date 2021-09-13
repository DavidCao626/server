<?php

namespace App\EofficeApp\Assets\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Assets\Entities\AssetsListEntity;

/**
 * 资产类型列表
 *
 * @author zw
 *
 * @since  2018-03-30 创建
 */
class AssetsListRepository extends BaseRepository
{

    public function __construct(AssetsListEntity $entity)
    {
        parent::__construct($entity);
    }



    public function assetsList($data){
        $default = [
            'fields'   => ['id', 'company', 'manager_id', 'storage', 'operator_id', 'assets_name', 'assets_code', 'sn','price','supplier','insert_time'],
            'page'     => 0,
            'limit'    => config('eoffice.pagesize'),
            'search'   => [],
            'order_by' => ['insert_time' => 'desc'],
        ];
        $params = array_merge($default, array_filter($data));
        return $this->entity->select($params['fields'])
            ->where($data['search'])
            ->orderBy('insert_time','desc')->parsePage($params['page'], $params['limit'])->get()->toArray();
    }



}