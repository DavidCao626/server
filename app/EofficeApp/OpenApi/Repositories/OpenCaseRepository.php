<?php

namespace App\EofficeApp\OpenApi\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\OpenApi\Entities\OpenCaseEntity;

/**
 * Class OpenCaseRepository
 * @package App\EofficeApp\OpenApi\Repositories
 */
class OpenCaseRepository extends BaseRepository
{

    public function __construct(OpenCaseEntity $entity)
    {
        parent::__construct($entity);
    }

    public function getOpenCaseTotal($params)
    {
        $default = [
            'search' => [],
        ];

        $param = array_merge($default, array_filter($params));

        return $this->entity
            ->wheres($param['search'])
            ->count();
    }

    public function getOpenCaseList($params)
    {
        $default = [
            'fields' => ['*'],
            'page' => 0,
            'limit' => config('eoffice.pagesize'),
            'search' => [],
            'order_by' => ['id' => 'asc'],
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
