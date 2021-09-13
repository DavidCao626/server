<?php

namespace App\EofficeApp\Weixin\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Weixin\Entities\WeixinUserEntity;

/**
 * Weixin用户资源库
 *
 * @author:喻威
 *
 * @since：2015-10-19
 *
 */
class WeixinUserRepository extends BaseRepository {

    public function __construct(WeixinUserEntity $entity) {
        parent::__construct($entity);
    }
    
    public function getInfoByWhere($where){
        return $this->entity->wheres($where)->first();
    }
    
    public function exchangeOpenid($where){
        return $this->entity->select("openid")->wheres($where)->get();
    }

}
