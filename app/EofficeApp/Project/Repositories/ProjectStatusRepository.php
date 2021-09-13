<?php

namespace App\EofficeApp\Project\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Project\Entities\ProjectStatusEntity;

/**
 * 项目讨论 资源库
 *
 * @author:喻威
 *
 * @since：2015-10-19
 *
 */
class ProjectStatusRepository extends BaseRepository {

    public function __construct(ProjectStatusEntity $entity) {
        parent::__construct($entity);
    }

    public function insert($data) {
        return $this->entity->insert($data);
    }

    public function getDataByWhere($where, $fields = ["relation_id"]) {
        return $this->entity->select($fields)->wheres($where)->get()->toArray();
    }

}
