<?php

namespace App\EofficeApp\UnifiedMessage\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\UnifiedMessage\Entities\HeterogeneousSystemMessageTypeEntities;


class HeterogeneousSystemMessageTypeRepository extends BaseRepository
{

    public function __construct(HeterogeneousSystemMessageTypeEntities $entity)
    {
        parent::__construct($entity);
    }

    public function getHeterogeneousSystemMessageTypeTotal($params)
    {
        $default = [
            'search' => [],
        ];

        $param = array_merge($default, array_filter($params));

        return $this->entity
            ->wheres($param['search'])
            ->count();
    }

    public function getHeterogeneousSystemMessageTypeList($params)
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

    //清空表
    public function clearWechatReply()
    {
        $this->entity->truncate();
        return true;
    }
    public function getAllMessagedata() {
        $result = $this->entity->leftJoin('heterogeneous_system', 'heterogeneous_system.id','=', 'heterogeneous_system_message_type.heterogeneous_system_id')->groupBy('heterogeneous_system_message_type.heterogeneous_system_id')->get()->toArray();
        return $result;
    }
}
