<?php
namespace App\EofficeApp\Attendance\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Attendance\Entities\AttendancePurviewBaseEntity;

class AttendancePurviewBaseRepository extends BaseRepository
{
    public function __construct(AttendancePurviewBaseEntity $entity)
    {
            parent::__construct($entity);
    }

    public function getOnePurview($search) {
    	return $this->entity->wheres($search)->first();
    }
}