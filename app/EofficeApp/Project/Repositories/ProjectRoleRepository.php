<?php

namespace App\EofficeApp\Project\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Project\Entities\ProjectRoleEntity;

/**
 * 项目角色 资源库
 *
 * @author:喻威
 *
 * @since：2015-10-19
 *
 */
class ProjectRoleRepository extends BaseRepository {

    public function __construct(ProjectRoleEntity $entity) {
        parent::__construct($entity);
    }

    /**
     * 获取项目角色列表
     *
     * @param array $param
     *
     * @author 喻威
     *
     * @since 2015-10-19
     */
    public function getAllProjectRole($param) {

        $default = [
            'fields' => ['*'],
            'page' => 0,
            'limit' => config('eoffice.pagesize'),
            'search' => [],
            'order_by' => ['role_order' => 'asc'],
        ];

        $param = array_merge($default, array_filter($param));

        return $this->entity
                        ->select($param['fields'])
                        ->wheres($param['search'])
                        ->orders($param['order_by'])
                       // ->parsePage($param['page'], $param['limit'])
                        ->get()->toArray();
    }

    //获取所有的类型 含分页 项目团队
    public function getAllTeamProjectRole($param){
        $default = [
            'fields' => ['*'],
            'page' => 0,
            'limit' => config('eoffice.pagesize'),
            'search' => [],
            'order_by' => ['role_order' => 'asc'],
        ];
        $param = array_merge($default, array_filter($param));
        return $this->entity
                        ->select($param['fields'])
                        ->wheres($param['search'])
                        ->orders($param['order_by'])
                        ->parsePage($param['page'], $param['limit'])
                        ->get()->toArray();
    }


    public function getAllTeamProjectTotal($param){
       $default = [
            'search' => []
        ];
        $param = array_merge($default, array_filter($param));
        return $this->entity->wheres($param['search'])
                        ->count();
    }
    //不分页，直接展示
    public function getCreateProjectRole(){

        return $this->entity->select(['*'])->orderBy("role_order","asc")->get()->toArray();
    }


    /**
     * 获
     *
     * @param type $id
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function infoProjectRolebyWhere($where) {
        $result = $this->entity->wheres($where)->get()->toArray();
        return $result;
    }

    //获取role

    public function getMaxOrder(){
        return $this->entity->orderBy("role_order",'desc')->get()->toArray();
    }


}
