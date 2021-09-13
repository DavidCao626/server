<?php
namespace App\EofficeApp\Attendance\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Attendance\Entities\AttendanceOutEntity;
use App\EofficeApp\Attendance\Traits\AttendanceTrait;
/**
 * 班次排班映射资源库类
 * 
 * @author 李志军
 * 
 * @since 2017-06-26
 */
class AttendanceOutRepository extends BaseRepository
{
    use AttendanceTrait;
    public function __construct(AttendanceOutEntity $entity)
    {
            parent::__construct($entity);
    $this->orderBy = ['out_start_time' => 'desc'];
    }
    public function getUserIdByDateRange($startDate, $endDate)
    {
        return $this->entity->select(['user_id'])->distinct('user_id')->where('out_start_time','<=',$endDate)->where('out_end_time','>=',$startDate)->get()->toArray();
    }
    public function getAttendOut($startDate, $endDate, $userId)
    {
        return $this->entity->where('out_start_time','<=',$endDate)->where('out_end_time','>=',$startDate)->where('user_id', $userId)->first();
    }
    public function getAttendOuts($startDate, $endDate, $userId)
    {
        return $this->entity->where('out_start_time','<=',$endDate)->where('out_end_time','>=',$startDate)->where('user_id', $userId)->get();
    }
    public function getOutRecordsByDateScopeAndUserIds($startDate, $endDate, $userIds)
    {
        return $this->entity->where('out_start_time','<=',$endDate)->where('out_end_time','>=',$startDate)->whereIn('user_id', $userIds)->get();
    }
}