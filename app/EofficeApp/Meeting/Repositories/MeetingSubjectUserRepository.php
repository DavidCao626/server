<?php
namespace App\EofficeApp\Meeting\Repositories;

use App\EofficeApp\Meeting\Entities\MeetingSubjectUserEntity;
use App\EofficeApp\Base\BaseRepository;

class MeetingSubjectUserRepository extends BaseRepository
{
    public function __construct(MeetingSubjectUserEntity $entity)
    {
        parent::__construct($entity);
    }
}
