<?php

namespace App\EofficeApp\Dingtalk\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Dingtalk\Entities\DingtalkTicketEntity;

/**
 * 企业号token资源库
 *
 * @author:喻威
 *
 * @since：2015-10-19
 *
 */
class DingtalkTicketRepository extends BaseRepository {

    public function __construct(DingtalkTicketEntity $entity) {
        parent::__construct($entity);
    }

    public function getTickt($where = []) {
        return $this->entity->wheres($where)->first();
    }

    //清空表
    public function truncateDingtalkTicket() {
        return $this->entity->truncate();
    }

}
