<?php
namespace App\EofficeApp\Meeting\Repositories;

use App\EofficeApp\Meeting\Entities\MeetingSortMemberRoleEntity;
use App\EofficeApp\Base\BaseRepository;

class MeetingSortMemberRoleRepository extends BaseRepository
{
    public function __construct(MeetingSortMemberRoleEntity $entity)
    {
        parent::__construct($entity);
    }
}
