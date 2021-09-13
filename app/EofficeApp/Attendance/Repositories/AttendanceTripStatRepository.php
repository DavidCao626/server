<?php
namespace App\EofficeApp\Attendance\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Attendance\Entities\AttendanceTripStatEntity;
use App\EofficeApp\Attendance\Traits\AttendanceTrait;
/**
 * 班次排班映射资源库类
 *
 * @author 李志军
 *
 * @since 2017-06-26
 */
class AttendanceTripStatRepository extends BaseRepository
{
    use AttendanceTrait;
    public function __construct(AttendanceTripStatEntity $entity)
    {
            parent::__construct($entity);
    }
    public function getAttendTripStatByMonth($year, $month, $userId)
    {
        return $this->entity->where('year', $year)->where('month', $month)->where('user_id', $userId)->sum('trip_days');
    }
    public function getAttendTripHoursStatByMonth($year, $month, $userId)
    {
        return $this->entity->where('year', $year)->where('month', $month)->where('user_id', $userId)->sum('trip_hours');
    }
    public function getMoreUserAttendTripStatByMonth($year, $month, $userId)
    {
        $stat = $this->entity->selectRaw('user_id,sum(trip_days) as days')->where('year', $year)->where('month', $month)->whereIn('user_id', $userId)->groupBy('user_id')->get();

        return $this->arrayGroupWithKeys($stat);
    }

    public function getMoreUserAttendTripStatByDate($startDate, $endDate, $userId)
    {
        $stat = $this->entity->selectRaw('user_id,sum(trip_days) as days')->where('date', '>=', $startDate)->where('date','<=',$endDate)->whereIn('user_id', $userId)->groupBy('user_id')->get();

        return $this->arrayGroupWithKeys($stat);
    }

     public function getMoreUserAttendTripHoursStatByMonth($year, $month, $userId)
    {
        $stat = $this->entity->selectRaw('user_id,sum(trip_hours) as hours')->where('year', $year)->where('month', $month)->whereIn('user_id', $userId)->groupBy('user_id')->get();

        return $this->arrayGroupWithKeys($stat);
    }

    public function getMoreUserAttendTripHoursStatByDate($startDate, $endDate, $userId)
    {
        $stat = $this->entity->selectRaw('user_id,sum(trip_hours) as hours')->where('date', '>=', $startDate)->where('date','<=',$endDate)->whereIn('user_id', $userId)->groupBy('user_id')->get();

        return $this->arrayGroupWithKeys($stat);
    }

    public function getMoreUserAttendTripRecordsByMonth($year, $month, $userIds)
    {
        $stat = $this->entity->where('year', $year)->where('month', $month)->whereIn('user_id', $userIds)->get();

        return $this->arrayGroupWithKeys($stat);
    }

    public function getMoreUserAttendTripRecordsByDate($startDate, $endDate, $userIds)
    {
        $stat = $this->entity->where('date', '>=', $startDate)->where('date','<=',$endDate)->whereIn('user_id', $userIds)->get();

        return $this->arrayGroupWithKeys($stat);
    }

    public function hasDataInDay($year, $month, $day)
    {
        return $this->entity->where('year', $year)->where('month', $month)->where('day', $day)->count() > 0;
    }

    public function getTripDataInDay($year, $month, $day, $userId)
    {
        return $this->entity->where('year', $year)->where('month', $month)->where('day', $day)->where('user_id', $userId)->first();
    }
    public function getDayByMonth($year, $month, $userId)
    {
        return $this->entity->select(['day','trip_days'])->where('year', $year)->where('month', $month)->where('user_id', $userId)->get();
    }
    public function getDayByDays($year, $month, $days, $userId)
    {
        return $this->entity->select(['day', 'trip_days'])->where('year', $year)->where('month', $month)->whereIn('day', $days)->where('user_id', $userId)->get()->toArray();
    }
    public function getDaysByDay($year, $month, $day, $userId)
    {
        return $this->entity->select(['day','trip_days'])->where('year', $year)->where('month', $month)->where('day', $day)->where('user_id', $userId)->first();
    }
    public function getMoreUserOneDayStatsByDate($userIds, $date)
    {
        $stat = $this->entity->where('date',$date)->whereIn('user_id', $userIds)->get();

        return $this->arrayGroupWithKeys($stat);
    }

    public function getOneRecord($wheres)
    {
        return $this->entity->wheres($wheres)->first();
    }
}