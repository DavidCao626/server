<?php
namespace App\EofficeApp\Meeting\Repositories;

use App\EofficeApp\Meeting\Entities\MeetingSubjectDepartmentEntity;
use App\EofficeApp\Base\BaseRepository;


class MeetingSubjectDepartmentRepository extends BaseRepository
{
    public function __construct(MeetingSubjectDepartmentEntity $entity)
    {
        parent::__construct($entity);
    }
}
