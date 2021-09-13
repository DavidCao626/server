<?php

namespace App\EofficeApp\Vacation\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Vacation\Entities\VacationMemberEntity;

class VacationMemberRepository extends BaseRepository
{
    public function __construct(VacationMemberEntity $vacationEntity)
    {
        parent::__construct($vacationEntity);
    }

    public function getDetailByVacationId($vacationId)
    {
        return $this->entity->where('vacation_id', $vacationId)->first();
    }

    public function getOwnVacationIds($userId, $deptId, $roleIds)
    {
        $records = $this->entity
            ->where(function ($query) use ($userId, $deptId, $roleIds) {
                $query->where(function ($query) use ($userId, $deptId, $roleIds) {
                    $query->orWhereRaw("FIND_IN_SET(?, user_id)", [$userId])->orWhereRaw("FIND_IN_SET(?, dept_id)", [$deptId]);
                    foreach ($roleIds as $roleId) {
                        $query->orWhereRaw("FIND_IN_SET(?, role_id)", [$roleId]);
                    }
                })->orWhere('all_member', 1);
            })->get()->toArray();
        return array_column($records, 'vacation_id');
    }
}