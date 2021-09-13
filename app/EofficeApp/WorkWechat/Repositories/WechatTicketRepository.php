<?php

namespace App\EofficeApp\WorkWechat\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\WorkWechat\Entities\WechatTicketEntity;

class WechatTicketRepository extends BaseRepository
{

    public function __construct(WechatTicketEntity $entity)
    {
        parent::__construct($entity);
    }

    //清空表
    public function truncateWechat()
    {
        return $this->entity->truncate();
    }

    public function getTickt()
    {
        $result = $this->entity->first();
        return $result;
    }

}
