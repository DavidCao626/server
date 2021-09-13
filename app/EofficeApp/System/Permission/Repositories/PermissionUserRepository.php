<?php

namespace App\EofficeApp\System\Permission\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\System\Permission\Entities\PermissionUserEntity;
use DB;
use Schema;

class PermissionUserRepository extends BaseRepository
{
    public function __construct(PermissionUserEntity $entity)
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
