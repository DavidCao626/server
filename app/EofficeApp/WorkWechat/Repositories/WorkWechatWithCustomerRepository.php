<?php

namespace App\EofficeApp\WorkWechat\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\WorkWechat\Entities\WorkWechatWithCustomerEntity;


class WorkWechatWithCustomerRepository extends BaseRepository
{

    public function __construct(WorkWechatWithCustomerEntity $entity)
    {
        parent::__construct($entity);
    }

    //清空表
    public function truncateWechat()
    {
        return $this->entity->truncate();
    }
    public function getCustomerLinkman($userid)
    {
        return $this->entity->where("external_contact_user_id", $userid)->first();
    }
    public function getDataByCustomerLinkmanId($customerLinkmanId)
    {
        return $this->entity->where("customer_linkman_id", $customerLinkmanId)->first();
    }
}
