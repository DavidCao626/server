<?php

namespace App\EofficeApp\Dingtalk\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Dingtalk\Entities\DingtalkSyncLogEntity;

class DingtalkSyncLogRepository extends BaseRepository
{

    public function __construct(DingtalkSyncLogEntity $entity)
    {
        parent::__construct($entity);
    }

    //清空表
    public function truncateDingtalkLog()
    {
        return $this->entity->truncate();
    }

    public function addSyncLog($data)
    {
        $resAdd = $this->insertData($data);
        if ($resAdd) {
            return true;
        }
        return false;
    }

    public function getDingtalkSyncLogList($param)
    {
        $result = $this->entity->select('dingtalk_organization_sync_log.*', 'user.user_id', 'user.user_name')->forPage($param['page'], $param['limit'])->leftJoin('user', 'user.user_id', '=', 'dingtalk_organization_sync_log.operator')->orderBy('dingtalk_organization_sync_log.created_at', 'desc')->get()->toArray();
        return $result;
    }

    public function getDingtalkSyncdetail($id)
    {
        return $this->entity->where(['id' => $id])->first();
    }
    public function dingtalkSyncLogsCount()
    {
        $result = $this->entity->count();
        return $result;
    }

}
