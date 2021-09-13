<?php
namespace App\EofficeApp\Attendance\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Attendance\Entities\AttendanceTripEntity;
use App\EofficeApp\Attendance\Traits\AttendanceTrait;
/**
 * 班次排班映射资源库类
 * 
 * @author 李志军
 * 
 * @since 2017-06-26
 */
class AttendanceTripRepository extends BaseRepository
{
    use AttendanceTrait;
    public function __construct(AttendanceTripEntity $entity)
    {
            parent::__construct($entity);
    $this->orderBy = ['trip_start_date' => 'desc'];
    }
    public function getUserIdByDateRange($startDate, $endDate)
    {
        return $this->entity->select(['user_id'])->distinct('user_id')->where('trip_start_date','<=',$endDate)->where('trip_end_date','>=',$startDate)->get()->toArray();
    }
    public function getAttendTrip($startDate, $endDate, $userId)
    {
        return $this->entity->where('trip_start_date','<=',$endDate)->where('trip_end_date','>=',$startDate)->where('user_id', $userId)->first();
    }
    public function getAttendTrips($startDate, $endDate, $userId)
    {
        return $this->entity->where('trip_start_date','<=',$endDate)->where('trip_end_date','>=',$startDate)->where('user_id', $userId)->get();
    }
    public function getTripRecordsByDateScopeAndUserIds($startDate, $endDate, $userIds)
    {
        return $this->entity->where('trip_start_date','<=',$endDate)->where('trip_end_date','>=',$startDate)->whereIn('user_id', $userIds)->get();
    }
}