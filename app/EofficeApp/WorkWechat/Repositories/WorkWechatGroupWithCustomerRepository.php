<?php

namespace App\EofficeApp\WorkWechat\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\WorkWechat\Entities\WorkWechatGroupWithCustomerEntity;


class WorkWechatGroupWithCustomerRepository extends BaseRepository
{

    public function __construct(WorkWechatGroupWithCustomerEntity $entity)
    {
        parent::__construct($entity);
    }

    //清空表
    public function truncateWechat()
    {
        return $this->entity->truncate();
    }
    public function getDataByCustomerId($customerId)
    {
        return $this->entity->where("customer_id", $customerId)->first();
    }
    public function getCustomer($groupChatId)
    {
        return $this->entity->where("group_chat_id", $groupChatId)->first();
    }

}
