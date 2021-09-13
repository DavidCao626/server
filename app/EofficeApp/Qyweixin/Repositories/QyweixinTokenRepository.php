<?php

namespace App\EofficeApp\Qyweixin\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Qyweixin\Entities\QyweixinTokenEntity;

/**
 * 企业号token资源库
 *
 * @author:喻威
 *
 * @since：2015-10-19
 *
 */
class QyweixinTokenRepository extends BaseRepository {

    public function __construct(QyweixinTokenEntity $entity) {
        parent::__construct($entity);
    }

   
    //清空表
    public function truncateWechat() {
        return $this->entity->truncate();
    }

    public function getWechat($where = []) {
        $result = $this->entity->wheres($where)->first();
        return $result;
    }
    
    

}
