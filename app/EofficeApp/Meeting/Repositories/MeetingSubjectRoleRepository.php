<?php
namespace App\EofficeApp\Meeting\Repositories;

use App\EofficeApp\Meeting\Entities\MeetingSubjectRoleEntity;
use App\EofficeApp\Base\BaseRepository;

class MeetingSubjectRoleRepository extends BaseRepository
{
    public function __construct(MeetingSubjectRoleEntity $entity)
    {
        parent::__construct($entity);
    }
}
