<?php

namespace App\EofficeApp\System\Permission\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\System\Permission\Entities\PermissionDeptEntity;
use DB;
use Schema;

class PermissionDeptRepository extends BaseRepository
{
    public function __construct(PermissionDeptEntity $entity)
    {
        parent::__construct($entity);
    }

    public function getOnePurview($search) {
    	return $this->entity->wheres($search)->first();
    }

    public function getPurviewByWhere($search) {
    	return $this->entity->wheres($search)->get();
    }
}
