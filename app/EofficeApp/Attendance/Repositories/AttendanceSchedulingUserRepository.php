<?php
namespace App\EofficeApp\Attendance\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Attendance\Entities\AttendanceSchedulingUserEntity;
/**
 * 排班用户资源库类
 * 
 * @author 李志军
 * 
 * @since 2017-06-26
 */
class AttendanceSchedulingUserRepository extends BaseRepository
{
	
	public function __construct(AttendanceSchedulingUserEntity $entity)
	{
		parent::__construct($entity);
	}
    
    public function getSchedulingUser($where = false, $fields = ['*'])
    {
        if($where){
            return $this->entity->select($fields)->wheres($where)->get();
        }
        
        return false;
    }
    public function getSchedulingIdByUser($userId)
    {
        return $this->entity->select(['scheduling_id'])->where('user_id', $userId)->first();
    }
    public function getSchedulingIdsByUserIds($userIds)
    {
        return $this->entity->select(['user_id','scheduling_id'])->whereIn('user_id', $userIds)->get()->toArray();
    }
    public function getUserCountGroupByScheduling()
    {
        return $this->entity->selectRaw('count(*) as count, scheduling_id')->groupBy('scheduling_id')->get();
    }
    public function getUserCountBySchedulingId($schedulingId)
    {
        return $this->entity->where('scheduling_id', $schedulingId)->distinct('user_id')->count('user_id');
    }
}
