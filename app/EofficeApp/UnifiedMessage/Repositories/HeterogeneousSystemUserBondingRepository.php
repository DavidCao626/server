<?php

namespace App\EofficeApp\UnifiedMessage\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\UnifiedMessage\Entities\HeterogeneousSystemUserBondingEntities;

/**
 * Class WechatReplyRepository
 * @package App\EofficeApp\Weixin\Repositories
 */
class HeterogeneousSystemUserBondingRepository extends BaseRepository
{

    public function __construct(HeterogeneousSystemUserBondingEntities $entity)
    {
        parent::__construct($entity);
    }

    public function getHeterogeneousSystemUserBondingTotal($params)
    {
        $default = [
            'search' => [],
        ];

        $param = array_merge($default, array_filter($params));

        return $this->entity
            ->wheres($param['search'])
            ->count();
    }

    public function getHeterogeneousSystemUserBondingList($params)
    {
        $default = [
            'fields' => ['*'],
            'page' => 0,
            'limit' => config('eoffice.pagesize'),
            'search' => [],
            'order_by' => ['heterogeneous_system_code' => 'desc'],
        ];

        $param = array_merge($default, array_filter($params));
        return $this->entity
            ->select($param['fields'])
            ->with(['heterogeneousSystemHasOne'=>function ($query){
                $query->select('id','system_code');
            }])
            ->wheres($param['search'])
            ->orders($param['order_by'])
            ->parsePage($param['page'], $param['limit'])
            ->get()
            ->toArray();
    }

    //获取一条内容
    public function getData($where)
    {
        $result = $this->entity->where($where)->first();
        return $result;
    }
    //获取多条内容
    public function getList($where)
    {
        $result = $this->entity->where($where)->get()->toArray();
        return $result;
    }

    //清空表
    public function truncateTable()
    {
        $this->entity->truncate();
        return true;
    }
}
