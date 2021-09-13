<?php
namespace App\EofficeApp\Attendance\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Attendance\Entities\AttendanceLeaveStatEntity;
use App\EofficeApp\Attendance\Traits\AttendanceTrait;
/**
 * 班次排班映射资源库类
 * 
 * @author 李志军
 * 
 * @since 2017-06-26
 */
class AttendanceLeaveStatRepository extends BaseRepository
{
	use AttendanceTrait;
	public function __construct(AttendanceLeaveStatEntity $entity)
	{
		parent::__construct($entity);
	}
    public function getAttendLeaveStatByMonth($year, $month, $userId, $hasMoney = false)
    {
        $query = $this->entity->where('year', $year)->where('month', $month)->where('user_id', $userId);
        
        if($hasMoney){
            return $query->where('has_money', 1)->sum('leave_days');
        }
        
        return $query->sum('leave_days');
    }
    public function getAttendLeaveHoursStatByMonth($year, $month, $userId, $hasMoney = false)
    {
        $query = $this->entity->where('year', $year)->where('month', $month)->where('user_id', $userId);
        
        if($hasMoney){
            return $query->where('has_money', 1)->sum('leave_hours');
        }
        
        return $query->sum('leave_hours');
    }
    public function getMoreUserAttendLeaveStatByMonth($year, $month, $userId, $hasMoney = false)
    {
        $query = $this->entity->selectRaw('user_id,sum(leave_days) as days')->where('year', $year)->where('month', $month)->whereIn('user_id', $userId);
        
        if($hasMoney){
            $query->where('has_money', 1);
        }
        
        $stat = $query->groupBy('user_id')->get();
        
        return $this->arrayGroupWithKeys($stat);
    }
    public function getMoreUserAttendLeaveHoursStatByMonth($year, $month, $userId, $hasMoney = false)
    {
        $query = $this->entity->selectRaw('user_id,sum(leave_hours) as hours')->where('year', $year)->where('month', $month)->whereIn('user_id', $userId);
        
        if($hasMoney){
            $query->where('has_money', 1);
        }
        
        $stat = $query->groupBy('user_id')->get();
        
        return $this->arrayGroupWithKeys($stat);
    }
    
    public function getMoreUserAttendLeaveRecordsByMonth($year, $month, $userIds, $hasMoney = false)
    {
        $query = $this->entity->where('year', $year)->where('month', $month)->whereIn('user_id', $userIds);
        
        if($hasMoney){
            $query->where('has_money', 1);
        }
        
        $stat = $query->get();
        
        return $this->arrayGroupWithKeys($stat);
    }

    public function getMoreUserAttendLeaveRecordsByDate($startDate, $endDate, $userIds, $hasMoney = false)
    {
        $query = $this->entity->where('date', '>=', $startDate)->where('date','<=',$endDate)->whereIn('user_id', $userIds);

        if($hasMoney){
            $query->where('has_money', 1);
        }

        $stat = $query->get();
        
        return $this->arrayGroupWithKeys($stat);
    }
    public function getMoreUserOneDayStatsByDate($userIds, $date)
    {
        $stat = $this->entity->where('date',$date)->whereIn('user_id', $userIds)->get();
        
        return $this->arrayGroupWithKeys($stat);
    }
    public function hasDataInDay($year, $month, $day)
    {
        return $this->entity->where('year', $year)->where('month', $month)->where('day', $day)->count() > 0;
    }
    public function getHasMoneyDataInDay($year, $month, $day, $userId, $hasMoney = false)
    {
        $query = $this->entity->where('year', $year)->where('month', $month)->where('day', $day)->where('user_id', $userId);
        
        if($hasMoney){
            $query->where('has_money',1);
        } else {
            $query->where('has_money',0);
        }
        
        return $query->first();
    }
    public function getDayByMonth($year, $month, $userId)
    {
        return $this->entity->select(['day','leave_days'])->where('year', $year)->where('month', $month)->where('user_id', $userId)->get();
    }
    public function getDayByDays($year, $month, $days, $userId)
    {
        return $this->entity->select(['day','leave_days'])->where('year', $year)->where('month', $month)->whereIn('day', $days)->where('user_id', $userId)->get()->toArray();
    }
    public function getDaysByDay($year, $month, $day, $userId)
    {
        return $this->entity->select(['day','leave_days'])->where('year', $year)->where('month', $month)->where('day', $day)->where('user_id', $userId)->first();
    }

    public function getOneRecord($wheres)
    {
        return $this->entity->wheres($wheres)->first();
    }
}