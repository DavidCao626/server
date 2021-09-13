<?php

namespace App\EofficeApp\Weixin\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Weixin\Entities\WeixinTokenEntity;

/**
 * Weixintoken资源库
 *
 * @author:喻威
 *
 * @since：2015-10-19
 *
 */
class WeixinTokenRepository extends BaseRepository {

    public function __construct(WeixinTokenEntity $entity) {
        parent::__construct($entity);
    }
    
    //appid获取当前微信信息
    public function getWeixinInfoByAppid($appid){
        $result = $this->entity->where('appid', $appid)->get()->toArray();
        return $result;
    }
    
    //清空表
    public function clearWeixinToken(){
        $this->entity->truncate();
        return true;
    }
    
    public function getWeixinToken($where = []){
        $result = $this->entity->wheres($where)->first();
        return $result;
    }
    
    
    

}
