<?php
namespace App\EofficeApp\Attendance\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Attendance\Entities\AttendanceOvertimeStatEntity;
use App\EofficeApp\Attendance\Traits\AttendanceTrait;
/**
 * 班次排班映射资源库类
 *
 * @author 李志军
 *
 * @since 2017-06-26
 */
class AttendanceOvertimeStatRepository extends BaseRepository
{

    use AttendanceTrait;
    public function __construct(AttendanceOvertimeStatEntity $entity)
    {
            parent::__construct($entity);
    }
    public function getOnlySalaryOvertimeStat($year, $month, $userId)
    {
        return $this->entity->select(['overtime_days','overtime_hours', 'ratio'])->where('year', $year)->where('month', $month)->where('user_id', $userId)->where('to', 2)->get();
    }
    public function getAttendOvertimeStatByMonth($year, $month, $userId)
    {
        return $this->entity->where('year', $year)->where('month', $month)->where('user_id', $userId)->sum('overtime_days');
    }
    public function getAttendOvertimeHoursStatByMonth($year, $month, $userId)
    {
        return $this->entity->where('year', $year)->where('month', $month)->where('user_id', $userId)->sum('overtime_hours');
    }
    public function getMoreUserAttendOvertimeStatByMonth($year, $month, $userId)
    {
        $stat = $this->entity->selectRaw('user_id,sum(overtime_days) as days')->where('year', $year)->where('month', $month)->whereIn('user_id', $userId)->groupBy('user_id')->get();

        return $this->arrayGroupWithKeys($stat);
    }
    public function getMoreUserAttendOvertimeStatByDate($startDate, $endDate, $userId)
    {
        $stat = $this->entity->selectRaw('user_id,sum(overtime_days) as days')->where('date', '>=', $startDate)->where('date','<=',$endDate)->whereIn('user_id', $userId)->groupBy('user_id')->get();

        return $this->arrayGroupWithKeys($stat);
    }
    public function getMoreUserAttendOvertimeHoursStatByMonth($year, $month, $userId)
    {
        $stat = $this->entity->selectRaw('user_id,sum(overtime_hours) as hours')->where('year', $year)->where('month', $month)->whereIn('user_id', $userId)->groupBy('user_id')->get();

        return $this->arrayGroupWithKeys($stat);
    }

    public function getMoreUserAttendOvertimeHoursStatByDate($startDate, $endDate, $userId)
    {
        $stat = $this->entity->selectRaw('user_id,sum(overtime_hours) as hours')->where('date', '>=', $startDate)->where('date','<=',$endDate)->whereIn('user_id', $userId)->groupBy('user_id')->get();

        return $this->arrayGroupWithKeys($stat);
    }

    public function getMoreUserAttendOvertimeRecordsByMonth($year, $month, $userIds)
    {
        $stat = $this->entity->where('year', $year)->where('month', $month)->whereIn('user_id', $userIds)->get();

        return $this->arrayGroupWithKeys($stat);
    }
    public function getMoreUserAttendOvertimeRecordsByDate($startDate, $endDate, $userIds, $to = null)
    {
        $query = $this->entity->where('date', '>=', $startDate)->where('date','<=',$endDate)->whereIn('user_id', $userIds);
        if ($to) {
            $query = $query->where('to', $to);
        }
        $stat = $query->get();
        return $this->arrayGroupWithKeys($stat);
    }
    public function hasDataInDay($year, $month, $day)
    {
        return $this->entity->where('year', $year)->where('month', $month)->where('day', $day)->count() > 0;
    }
    public function hasOvertimeDataInDay($year, $month, $day, $userId)
    {
        return $this->entity->where('year', $year)->where('month', $month)->where('day', $day)->where('user_id', $day)->count() > 0;
    }
    public function getMoreUserOneDayStatsByDate($userIds, $date)
    {
        $stat = $this->entity->where('date',$date)->whereIn('user_id', $userIds)->get();

        return $this->arrayGroupWithKeys($stat);
    }

    public function getUserEffectDays($startDate, $endDate, $userId)
    {
        $data = $this->entity->where('date', '>=', $startDate)->where('date', '<=', $endDate)->where('user_id', $userId)->get()->toArray();
        $map = [];
        if (count($data) > 0) {
            foreach ($data as $item) {
                $map[$item['date']] = [$item['overtime_days'], $item['overtime_hours']];
            }
        }
        return $map;
    }

    public function getOneRecord($wheres)
    {
        return $this->entity->wheres($wheres)->first();
    }

    public function getOvertimeStatByDate($startDate, $endDate, $userId)
    {
        return $this->entity->where('date', '>=', $startDate)->where('date', '<=', $endDate)->where('user_id', $userId)->get()->toArray();
    }

    public function getOvertimeTo($date,$userId){
        $data=$this->entity->where('user_id',$userId)->where('date',$date)->first();
        if(!$data){
            return $data;
        }
        return $data->to;
    }
}