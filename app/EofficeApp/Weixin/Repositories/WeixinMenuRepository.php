<?php

namespace App\EofficeApp\Weixin\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Weixin\Entities\WeixinMenuEntity;

/**
 * Weixin资源库
 *
 * @author:喻威
 *
 * @since：2015-10-19
 *
 */
class WeixinMenuRepository extends BaseRepository {

    public function __construct(WeixinMenuEntity $entity) {
        parent::__construct($entity);
    }
    //根据条件获取内容
    public function getDataByWhere($where,$field = ["*"]) {
        $result = $this->entity->select($field)->wheres($where)->get()->toArray();
        return $result;
    }
    //删除菜单
    public function deleteWeixinMenu($id) {
        return $this->entity
                        ->where("id", $id)
                        ->orWhere("node", $id)
                        ->delete();
    }
    //获取菜单满足个数
    public function getCount($where){
       return $this->entity->wheres($where)->count();
    }
    
    
 

}
