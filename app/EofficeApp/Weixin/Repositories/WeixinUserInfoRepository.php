<?php

namespace App\EofficeApp\Weixin\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Weixin\Entities\WeixinUserInfoEntity;

/**
 * Weixin用户信息资源库
 *
 * @author:喻威
 *
 * @since：2015-10-19
 *
 */
class WeixinUserInfoRepository extends BaseRepository {

    public function __construct(WeixinUserInfoEntity $entity) {
        parent::__construct($entity);
    }
    
    
    public function getWeixinUserFollowList($data) {

        $default = [
            'fields' => ['*'],
            'page' => 0,
            'limit' => config('eoffice.pagesize'),
            'search' => [],
            'order_by' => ['subscribe_time' => 'desc'],
        ];

        $param = array_merge($default, array_filter($data));

        return $this->entity
                        ->select($param['fields'])
                        ->wheres($param['search'])
                        ->orders($param['order_by'])
                        ->parsePage($param['page'], $param['limit'])
                        ->get()
                        ->toArray();
    }
    
    public function getWeixinUserFollowTotal($data){
        $default = [
            'search' => [],
        ];

        $param = array_merge($default, array_filter($data));

        return $this->entity
                        ->wheres($param['search'])
                        ->count();
    }
    
    public function truncateTable(){
       return  $this->entity->truncate();
    }

    
     public function getInfoByWhere($where){
        return $this->entity->wheres($where)->first();
    }
    

}
