<?php

namespace App\EofficeApp\OpenApi\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\OpenApi\Entities\OpenApplicationEntity;

/**
 * Class OpenApplicationRepository
 * @package App\EofficeApp\OpenApi\Repositories
 */
class OpenApplicationRepository extends BaseRepository
{

    public function __construct(OpenApplicationEntity $entity)
    {
        parent::__construct($entity);
    }

    public function getOpenApplicationTotal($params)
    {
        $default = [
            'search' => [],
        ];

        $param = array_merge($default, array_filter($params));

        return $this->entity
            ->wheres($param['search'])
            ->count();
    }

    public function getOpenApplicationList($params)
    {
        $default = [
            'fields' => ['*'],
            'page' => 0,
            'limit' => config('eoffice.pagesize'),
            'search' => [],
            'order_by' => ['created_at' => 'desc'],
        ];

        $param = array_merge($default, array_filter($params));

        return $this->entity
            ->select($param['fields'])
            ->wheres($param['search'])
            ->orders($param['order_by'])
            ->parsePage($param['page'], $param['limit'])
            ->get()
            ->toArray();
    }

    //获取内容
    public function getData($where)
    {
        $result = $this->entity->where($where)->first();
        return $result;
    }

}
