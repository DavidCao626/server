<?php
namespace App\EofficeApp\Attendance\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Attendance\Entities\AttendanceCommonPurviewGroupEntity;
/**
 * 班次排班映射资源库类
 * 
 * @author 李志军
 * 
 * @since 2017-06-26
 */
class AttendanceCommonPurviewGroupRepository extends BaseRepository
{
	
    public function __construct(AttendanceCommonPurviewGroupEntity $entity)
    {
            parent::__construct($entity);
    }
    
    public function purviewGroupExists($type)
    {
        return $this->entity->where('type', $type)->count();
    }
    public function updatePurviewGroup($data, $type = 1)
    {
        return $this->entity->where('type', $type)->update($data);
    }
    public function getOnePurviewGroup($type)
    {
        return $this->entity->where('type', $type)->first();
    }
    
    public function isInPurviewGroup($userId,$deptId,$roleIds)
    {
        return $this->entity
                        ->where(function ($query) use($userId, $deptId, $roleIds) {
                            $query->where(function($query) use($userId, $deptId, $roleIds) {
                                $query->orWhereRaw('find_in_set(?,user_id)', [$userId])->orWhereRaw('find_in_set(?,dept_id)', [$deptId]);
                                foreach ($roleIds as $roleId) {
                                    $query->orWhereRaw('find_in_set(?,role_id)', [$roleId]);
                                }
                            })->orWhere('all_member', 1);
                        })->count();
    }
}