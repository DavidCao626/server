<?php

namespace App\EofficeApp\UnifiedMessage\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\UnifiedMessage\Entities\HeterogeneousSystemIntegrationLogEntities;

/**
 * Class WechatReplyRepository
 * @package App\EofficeApp\Weixin\Repositories
 */
class HeterogeneousSystemIntegrationLogRepository extends BaseRepository
{

    public function __construct(HeterogeneousSystemIntegrationLogEntities $entity)
    {
        parent::__construct($entity);
    }

    public function getHeterogeneousSystemIntegrationLogTotal($params)
    {
        $default = [
            'search' => [],
        ];

        $param = array_merge($default, array_filter($params));

        return $this->entity
            ->wheres($param['search'])
            ->count();
    }

    public function getHeterogeneousSystemIntegrationLogList($params)
    {
        $default = [
            'fields' => ['*'],
            'page' => 0,
            'limit' => config('eoffice.pagesize'),
            'search' => [],
            'order_by' => ['operation_time' => 'desc'],
        ];

        $param = array_merge($default, array_filter($params));

        return $this->entity
            ->select($param['fields'])
            ->with(['userHasOne'=>function ($query){
                $query->select('user_id','user_accounts');
            }])
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
}
