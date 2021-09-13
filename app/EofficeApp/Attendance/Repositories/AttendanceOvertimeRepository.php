<?php
namespace App\EofficeApp\Attendance\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Attendance\Entities\AttendanceOvertimeEntity;
use App\EofficeApp\Attendance\Traits\AttendanceTrait;
/**
 * 班次排班映射资源库类
 *
 * @author 李志军
 *
 * @since 2017-06-26
 */
class AttendanceOvertimeRepository extends BaseRepository
{
    use AttendanceTrait;
    public function __construct(AttendanceOvertimeEntity $entity)
    {
        parent::__construct($entity);
        $this->orderBy = ['overtime_start_time' => 'desc'];
    }
    public function getUserIdByDateRange($startDate, $endDate)
    {
        return $this->entity->select(['user_id'])->distinct('user_id')->where('overtime_start_time','<=',$endDate)->where('overtime_end_time','>=',$startDate)->get()->toArray();
    }
    public function getAttendOvertimes($startDate, $endDate, $userId)
    {
        return $this->entity->where('overtime_start_time','<=',$endDate)->where('overtime_end_time','>=',$startDate)->where('user_id', $userId)->get();
    }
    public function getOvertimeRecordsByDateScopeAndUserIds($startDate, $endDate, $userIds)
    {
        return $this->entity->where('overtime_start_time','<=',$endDate)->where('overtime_end_time','>=',$startDate)->whereIn('user_id', $userIds)->get();
	}

    public function getAttendOvertime($startTime, $endTime, $userId)
    {
        return $this->entity
            ->where('overtime_start_time', '<=', $endTime)
            ->where('overtime_end_time', '>=', $startTime)
            ->where('user_id', $userId)
            ->orderBy('overtime_start_time','asc')
            ->get();
    }
}