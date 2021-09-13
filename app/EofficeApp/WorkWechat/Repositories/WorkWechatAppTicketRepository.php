<?php

namespace App\EofficeApp\WorkWechat\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\WorkWechat\Entities\WorkWechatAppTicketEntity;


class WorkWechatAppTicketRepository extends BaseRepository
{

    public function __construct(WorkWechatAppTicketEntity $entity)
    {
        parent::__construct($entity);
    }

    //清空表
    public function truncateWechat()
    {
        return $this->entity->truncate();
    }

    public function getTickt($agentid)
    {
        return $this->entity->where("agentid", $agentid)->first();
    }

}
