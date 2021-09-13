<?php
namespace App\EofficeApp\Meeting\Repositories;

use App\EofficeApp\Meeting\Entities\MeetingSortMemberDepartmentEntity;
use App\EofficeApp\Base\BaseRepository;

class MeetingSortMemberDepartmentRepository extends BaseRepository
{
    public function __construct(MeetingSortMemberDepartmentEntity $entity)
    {
        parent::__construct($entity);
    }
}
