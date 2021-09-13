<?php

namespace App\EofficeApp\Weixin\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Weixin\Entities\WeixinTicketEntity;

/**
 * 企业号token资源库
 *
 * @author:喻威
 *
 * @since：2015-10-19
 *
 */
class WeixinTicketRepository extends BaseRepository {

    public function __construct(WeixinTicketEntity $entity) {
        parent::__construct($entity);
    }

    public function getTickt($where = []) {
        return $this->entity->wheres($where)->first();
    }

    //清空表
    public function truncateWechat() {
        return $this->entity->truncate();
    }

}
