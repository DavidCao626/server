<?php

namespace App\EofficeApp\Project\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Project\Entities\ProjectTemplateEntity;

/**
 * 项目模板 资源库
 *
 * @author:喻威
 *
 * @since：2015-10-19
 *
 */
class ProjectTemplateRepository extends BaseRepository {

    public function __construct(ProjectTemplateEntity $entity) {
        parent::__construct($entity);
    }

     /**
     * 获取项目模板
     * 
     * @param array $param 去掉分页
     * 
     * @author 喻威
     * 
     * @since 2015-10-19
     */
    public function getAllProjectTemplate($param) {
        $default = [
            'fields' => ['*'],
            'page' => 0,
            'limit' => config('eoffice.pagesize'),
            'search' => [],
            'order_by' => ['template_type' => 'asc'],
        ];

        $param = array_merge($default, array_filter($param));
 
        return $this->entity
                        ->select($param['fields'])
                        ->wheres($param['search'])
                        ->orders($param['order_by'])
                        ->parsePage($param['page'], $param['limit'])
                        ->get()->toArray();
    }
    
    public function  getAll($param){
        
         $default = [
            
            'search' => []
        ];

        $param = array_merge($default, array_filter($param));
        
        return $this->entity->wheres($param['search'])->get()->toArray();
    }


     /**
     * 获取项目模板
     * 
     * @param array
     * 
     * @return array
     * 
     * @author 喻威
     * 
     * @since 2015-10-21
     */
 
    public function infoProjectTemplatebyWhere($where) {
        $result = $this->entity->wheres($where)->get()->toArray();
        return $result;
    }
    
   

}
