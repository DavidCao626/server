<?php

namespace App\EofficeApp\System\Permission\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\System\Permission\Entities\PermissionRoleEntity;
use DB;
use Schema;

class PermissionRoleRepository extends BaseRepository
{
    public function __construct(PermissionRoleEntity $entity)
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
