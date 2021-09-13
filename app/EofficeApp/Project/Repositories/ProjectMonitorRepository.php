<?php

namespace App\EofficeApp\Project\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Project\Entities\ProjectMonitorEntity;

/**
 * 监控权限  资源库
 *
 * @author:喻威
 *
 * @since：2015-10-19
 *
 */
class ProjectMonitorRepository extends BaseRepository {

    public function __construct(ProjectMonitorEntity $entity) {
        parent::__construct($entity);
    }
 
    /**
     * 获取监控信息
     * 
     * @param type $id
     * 
     * @return array
     * 
     * @author 喻威
     * 
     * @since 2015-10-21
     */
    public function infoProjectMonitorbyWhere($where) {
        $result = $this->entity->wheres($where)->get()->toArray();
        return $result;
    }
    
    

}
