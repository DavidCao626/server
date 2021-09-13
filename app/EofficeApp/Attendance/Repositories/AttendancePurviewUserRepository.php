<?php
namespace App\EofficeApp\Attendance\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Attendance\Entities\AttendancePurviewUserEntity;

class AttendancePurviewUserRepository extends BaseRepository
{
    public function __construct(AttendancePurviewUserEntity $entity)
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