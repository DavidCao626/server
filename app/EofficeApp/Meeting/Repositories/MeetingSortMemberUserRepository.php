<?php
namespace App\EofficeApp\Meeting\Repositories;

use App\EofficeApp\Meeting\Entities\MeetingSortMemberUserEntity;
use App\EofficeApp\Base\BaseRepository;

class MeetingSortMemberUserRepository extends BaseRepository
{
    public function __construct(MeetingSortMemberUserEntity $entity)
    {
        parent::__construct($entity);
    }
}
