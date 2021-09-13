<?php

namespace App\EofficeApp\Weixin\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Weixin\Entities\WeixinReplyEntity;

/**
 * Class WechatReplyRepository
 * @package App\EofficeApp\Weixin\Repositories
 */
class WeixinReplyRepository extends BaseRepository
{

    public function __construct(WeixinReplyEntity $entity)
    {
        parent::__construct($entity);
    }

    //获取内容
    public function getData()
    {
        $result = $this->entity->first();
        return $result;
    }

    //清空表
    public function clearWechatReply()
    {
        $this->entity->truncate();
        return true;
    }
}
