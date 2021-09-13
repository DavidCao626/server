<?php

namespace App\EofficeApp\WorkWechat\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\WorkWechat\Entities\WorkWechatSyncAttendanceLogEntity;

class WorkWechatSyncAttendanceLogRepository extends BaseRepository
{

    public function __construct(WorkWechatSyncAttendanceLogEntity$entity)
    {
        parent::__construct($entity);
    }

    public function getCount($param = [])
    {
        $query = $this->entity;
        return $query->count();
    }
    public function getList($data=[]) {
        $default = [
            'fields' => ['work_wechat_sync_attendance_log.*','user.user_name'],
            'page' => 0,
            'limit' => config('eoffice.pagesize'),
            'search' => [],
            'order_by' => ['sync_start_time' => 'desc'],
        ];

        $param = array_merge($default, $data);

        $data = $this->entity
            ->select($param['fields'])
            ->leftJoin('user', function($join) {
                $join->on("work_wechat_sync_attendance_log.sync_user", '=', 'user.user_id');
            })
            ->wheres($param['search'])
            ->orders($param['order_by'])
            ->parsePage($param['page'], $param['limit'])
            ->get()
            ->toArray();

        return $data;
    }
}
