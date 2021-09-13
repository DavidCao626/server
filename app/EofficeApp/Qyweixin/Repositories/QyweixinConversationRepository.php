<?php

namespace App\EofficeApp\Qyweixin\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Qyweixin\Entities\QyweixinConversationEntity;
use DB;

/**
 * 企业号token资源库
 *
 * @author:喻威
 *
 * @since：2015-10-19
 *
 */
class QyweixinConversationRepository extends BaseRepository {

    public function __construct(QyweixinConversationEntity $entity) {
        parent::__construct($entity);
    }
    
    public function getUser($where = []) {
        return $this->entity->wheres($where)->first();
    }

  
    public function transferUsers($where) {
        return $this->entity->select(["userid","oa_id"])->wheres($where)->get()->toArray();
    }
    
    public function getDingTalkUserIdById($user_id) {
        return $this->entity->select(["userid"])->where("oa_id",$user_id)->first();
    }
    

}
