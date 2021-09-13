<?php

namespace App\EofficeApp\Project\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Project\Entities\ProjectExamineEntity;

/**
 * 审批权限 资源库
 *
 * @author:喻威
 *
 * @since：2015-10-19
 *
 */
class ProjectExamineRepository extends BaseRepository {

    public function __construct(ProjectExamineEntity $entity) {
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
    public function infoProjectExaminebyWhere($where) {
        $result = $this->entity->wheres($where)->get()->toArray();
        return $result;
    }
 
    /**
     * 获取审批用户组
     * 
     * @param type $id
     * 
     * @return array
     * 
     * @author 喻威
     * 
     * @since 2015-10-21
     */
    public function getProjectExamineUsers($deptId) {
 
        $result = $this->entity->select(['exam_person'])->WhereRaw('(find_in_set(\'' . $deptId . '\',exam_manager))')->get()->toArray();
        return $result;
    }
   

}
