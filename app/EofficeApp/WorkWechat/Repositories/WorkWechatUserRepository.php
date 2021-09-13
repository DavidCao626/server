<?php

namespace App\EofficeApp\WorkWechat\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\WorkWechat\Entities\WorkWechatUserEntity;

/**
 * 企业微信用户
 *
 * @author:白锦
 *
 * @since：2015-10-19
 *
 */
class WorkWechatUserRepository extends BaseRepository
{

    public function __construct(WorkWechatUserEntity $entity)
    {
        parent::__construct($entity);
    }

    //清空表
    public function truncateWechat()
    {
        return $this->entity->truncate();
    }
    public function getWorkWechatUserIdById($userId)
    {
        return $this->entity->where("oa_id", $userId)->first();
    }

    public function getWorkWechatUserIdByIds($userIds)
    {
        return $this->entity->select(['userid'])->whereIn("oa_id", $userIds)->get()->map(function ($value) {
            return $value->userid;
        })->toArray();
    }
    public function getAllInfo(){
        return $this->entity->get()->toArray();
    }
}
