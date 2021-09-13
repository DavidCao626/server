<?php

namespace App\EofficeApp\WorkWechat\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\WorkWechat\Entities\WorkWechatSyncLogEntity;


class WorkWechatSyncLogRepository extends BaseRepository
{
    public $entity;
    public function __construct(WorkWechatSyncLogEntity $entity)
    {
        parent::__construct($entity);
    }
    public function getCount($param = [])
    {
        $query = $this->entity;
        $query = $this->getParseWhere($query, $param);
        return $query->count();
    }
    public function getList($data=[]) {
        $default = [
            'fields' => ['work_wechat_sync_log.*','user.user_name'],
            'page' => 0,
            'limit' => config('eoffice.pagesize'),
            'search' => [],
            'order_by' => ['sync_start_time' => 'desc'],
        ];

        $param = array_merge($default, $data);

        $data = $this->entity
            ->select($param['fields'])
            ->leftJoin('user', function($join) {
                $join->on("work_wechat_sync_log.operator", '=', 'user.user_id');
            })
            ->wheres($param['search'])
            ->orders($param['order_by'])
            ->parsePage($param['page'], $param['limit'])
            ->get()
           ->toArray();

        return $data;
    }
    /**
     * 查询条件解析 where条件解析
     *
     * @param  array $where  查询条件
     *
     * @return object
     *
     * @author dosy
     */
    public function getParseWhere($query, $param)
    {
        $search = $param['search'] ?? [];
        if ($search){
            $query = $query->wheres($search);
        }
        return $query;
    }
}
