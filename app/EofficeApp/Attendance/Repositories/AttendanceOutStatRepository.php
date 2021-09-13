<?php
namespace App\EofficeApp\Attendance\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Attendance\Entities\AttendanceOutStatEntity;
use App\EofficeApp\Attendance\Traits\AttendanceTrait;
/**
 * 班次排班映射资源库类
 *
 * @author 李志军
 *
 * @since 2017-06-26
 */
class AttendanceOutStatRepository extends BaseRepository
{
    use AttendanceTrait;
    public function __construct(AttendanceOutStatEntity $entity)
    {
            parent::__construct($entity);
    }
    public function getAttendOutStatByMonth($year, $month, $userId)
    {
        return $this->entity->where('year', $year)->where('month', $month)->where('user_id', $userId)->sum('out_days');
    }
    public function getAttendOutHoursStatByMonth($year, $month, $userId)
    {
        return $this->entity->where('year', $year)->where('month', $month)->where('user_id', $userId)->sum('out_hours');
    }
    public function getMoreUserAttendOutStatByMonth($year, $month, $userId)
    {
        $stat = $this->entity->selectRaw('user_id,sum(out_days) as days')->where('year', $year)->where('month', $month)->whereIn('user_id', $userId)->groupBy('user_id')->get();

        return $this->arrayGroupWithKeys($stat);
    }

    public function getMoreUserAttendOutStatByDate($startDate, $endDate, $userId)
    {
        $stat = $this->entity->selectRaw('user_id,sum(out_days) as days')->where('date', '>=', $startDate)->where('date','<=',$endDate)->whereIn('user_id', $userId)->groupBy('user_id')->get();

        return $this->arrayGroupWithKeys($stat);
    }

    public function getMoreUserAttendOutHoursStatByMonth($year, $month, $userId)
    {
        $stat = $this->entity->selectRaw('user_id,sum(out_hours) as hours')->where('year', $year)->where('month', $month)->whereIn('user_id', $userId)->groupBy('user_id')->get();

        return $this->arrayGroupWithKeys($stat);
    }

    public function getMoreUserAttendOutHoursStatByDate($startDate, $endDate, $userId)
    {
        $stat = $this->entity->selectRaw('user_id,sum(out_hours) as hours')->where('date', '>=', $startDate)->where('date','<=',$endDate)->whereIn('user_id', $userId)->groupBy('user_id')->get();

        return $this->arrayGroupWithKeys($stat);
    }

    public function getMoreUserAttendOutRecordsByMonth($year, $month, $userIds)
    {
        $stat = $this->entity->where('year', $year)->where('month', $month)->whereIn('user_id', $userIds)->get();

        return $this->arrayGroupWithKeys($stat);
    }

    public function getMoreUserAttendOutRecordsByDate($startDate, $endDate, $userIds)
    {
        $stat = $this->entity->where('date', '>=', $startDate)->where('date','<=',$endDate)->whereIn('user_id', $userIds)->get();

        return $this->arrayGroupWithKeys($stat);
    }

    public function hasDataInDay($year, $month, $day)
    {
        return $this->entity->where('year', $year)->where('month', $month)->where('day', $day)->count() > 0;
    }
    public function getOutDataInDay($year, $month, $day, $userId)
    {
        return $this->entity->where('year', $year)->where('month', $month)->where('day', $day)->where('user_id', $userId)->first();
    }
    public function getDayByDays($year, $month, $days, $userId)
    {
        return $this->entity->select(['day','out_days'])->where('year', $year)->where('month', $month)->whereIn('day', $days)->where('user_id', $userId)->get()->toArray();
    }
    public function getDayByMonth($year, $month, $userId)
    {
        return $this->entity->select(['day','out_days'])->where('year', $year)->where('month', $month)->where('user_id', $userId)->get();
    }
    public function getDaysByDay($year, $month, $day, $userId)
    {
        return $this->entity->select(['day','out_days'])->where('year', $year)->where('month', $month)->where('day', $day)->where('user_id', $userId)->first();
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