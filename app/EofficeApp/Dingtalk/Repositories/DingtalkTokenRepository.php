<?php

namespace App\EofficeApp\Dingtalk\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Dingtalk\Entities\DingtalkTokenEntity;
use DB;

/**
 * 企业号token资源库
 *
 * @author:喻威
 *
 * @since：2015-10-19
 *
 */
class DingtalkTokenRepository extends BaseRepository
{

    public function __construct(DingtalkTokenEntity $entity)
    {
        parent::__construct($entity);
    }

    //清空表
    public function truncateDingTalk()
    {
        return $this->entity->truncate();
    }

    public function getDingTalk($where = [])
    {
        $result = $this->entity->wheres($where)->first();
        return $result;
    }

    public function update($data)
    {
        DB::table("dingtalk_token")->update($data);

    }

}
