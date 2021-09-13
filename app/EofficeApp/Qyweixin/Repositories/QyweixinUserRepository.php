<?php

namespace App\EofficeApp\Qyweixin\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Qyweixin\Entities\QyweixinUserEntity;
use DB;
 
/**
 * 企业号token资源库
 *
 * @author:喻威
 *
 * @since：2015-10-19
 *
 */
class QyweixinUserRepository extends BaseRepository {

    public function __construct(QyweixinUserEntity $entity) {
        parent::__construct($entity);
    }

    //清空表
    public function truncateTable() {
        return $this->entity->truncate();
    }

    public function batchInsert($insertData) {
        return $this->entity->insert($insertData);
    }

    public function getUserListShowTotal($param) {
        $default = [
            'search' => [],
        ];
        $param = array_merge($default, array_filter($param));
        return $this->entity
                        ->wheres($param['search'])
                        ->count();
    }

    public function getUserListShow($param) {
        $default = [
            'fields' => ['*'],
            'page' => 0,
            'limit' => config('eoffice.pagesize'),
            'search' => [],
            'order_by' => ['userid' => 'asc'],
        ];


        $param = array_merge($default, array_filter($param));

        return $this->entity
                        ->wheres($param['search'])
                        ->orders($param['order_by'])->parsePage($param['page'], $param['limit'])->get()->toArray();
    }
    
    
    public function getUserWechat(){
      return  $this->entity->select(DB::raw('count(*) as count, status'))
                     ->groupBy('status')
                     ->get()->toArray();
        
    }

}
