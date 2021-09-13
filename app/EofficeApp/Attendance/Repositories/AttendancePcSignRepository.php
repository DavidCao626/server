<?php
namespace App\EofficeApp\Attendance\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Attendance\Entities\AttendancePcSignEntity;

class AttendancePcSignRepository extends BaseRepository
{
	
	public function __construct(AttendancePcSignEntity $entity)
	{
		parent::__construct($entity);
	}
    
    public function dataIsExists()
    {
        return $this->entity->count();
    }
    public function updateScope($data)
    {
        return $this->entity->where('id','>',0)->update($data);
    }
    public function getOneScopeInfo()
    {
        return $this->entity->first();
    }
    
    public function isInScope($userId,$deptId,$roleIds)
    {
        return $this->entity->where(function ($query) use($userId, $deptId, $roleIds) {
                    $query->where(function($query) use($userId, $deptId, $roleIds) {
                        $query->orWhereRaw('find_in_set(?,user_id)', [$userId])->orWhereRaw('find_in_set(?,dept_id)',[$deptId]);
                        foreach ($roleIds as $roleId) {
                            $query->orWhereRaw('find_in_set(?,role_id)', [$roleId]);
                        }
                    })->orWhere('all_member', 1);
                })->count();
    }
}