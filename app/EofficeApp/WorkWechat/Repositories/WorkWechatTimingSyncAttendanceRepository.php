<?php

namespace App\EofficeApp\WorkWechat\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\WorkWechat\Entities\WorkWechatTimingSyncAttendanceEntity;

class WorkWechatTimingSyncAttendanceRepository extends BaseRepository
{

    public function __construct(WorkWechatTimingSyncAttendanceEntity$entity)
    {
        parent::__construct($entity);
    }
    //清空表
    public function truncateWechat()
    {
        return $this->entity->truncate();
    }
}
