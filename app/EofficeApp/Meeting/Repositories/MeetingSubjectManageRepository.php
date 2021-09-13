<?php
namespace App\EofficeApp\Meeting\Repositories;

use App\EofficeApp\Meeting\Entities\MeetingSubjectManageEntity;
use App\EofficeApp\Base\BaseRepository;

class MeetingSubjectManageRepository extends BaseRepository
{
    public function __construct(MeetingSubjectManageEntity $entity)
    {
        parent::__construct($entity);
    }
}
