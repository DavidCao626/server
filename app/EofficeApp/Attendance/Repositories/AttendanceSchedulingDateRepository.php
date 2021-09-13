<?php
namespace App\EofficeApp\Attendance\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Attendance\Entities\AttendanceSchedulingDateEntity;
/**
 * 班次排班映射资源库类
 * 
 * @author 李志军
 * 
 * @since 2017-06-26
 */
class AttendanceSchedulingDateRepository extends BaseRepository
{
	
	public function __construct(AttendanceSchedulingDateEntity $entity)
	{
		parent::__construct($entity);
	}
    
    public function getOneSchedulingDate($where = false, $fields = ['*'])
    {
        if($where){
            return $this->entity->select($fields)->wheres($where)->first();
        }
        
        return false;
    }
    public function getSchedulingDateBySchedulingId($schedulingId,$year)
    {
        return $this->entity->where('scheduling_id', $schedulingId)->where('year', $year)->get();
    }
    public function getMonthSchedulingDateBySchedulingId($schedulingId,$year, $month)
    {
        return $this->entity->select(['scheduling_date','shift_id'])->where('scheduling_id', $schedulingId)->where('year', $year)->where('month', $month)->orderBy('scheduling_date','asc')->get();
    }
    public function getSchedulingDatesBySchedulingIds($schedulingId, $signDate)
    {
        return $this->entity->select(['scheduling_id','shift_id'])->whereIn('scheduling_id', $schedulingId)->where('scheduling_date', $signDate)->get();
    }
    public function getSchedulingShiftBySchedulingId($schedulingId, $signDate)
    {
        return $this->entity->select(['scheduling_id','shift_id'])->where('scheduling_id', $schedulingId)->where('scheduling_date', $signDate)->first();
    }
    public function getSchedulingDateByDateScope($schedulingId, $startDate, $endDate)
    {
        return $this->entity->select(['scheduling_date','shift_id'])->where('scheduling_id', $schedulingId)->where('scheduling_date','>=',$startDate)->where('scheduling_date', '<=',$endDate)->orderBy('scheduling_date','asc')->get();
    }
    public function getAllShiftIdBySchedulingId($schedulingId) 
    {
        return $this->entity->select(['shift_id'])->where('scheduling_id', $schedulingId)->groupBy('shift_id')->get()->toArray();
    }
    public function getSchedulingDateById($schedulingId)
    {
        return $this->entity->select(['scheduling_date','shift_id'])->where('scheduling_id', $schedulingId)->get();
    }
}
