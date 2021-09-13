<?php
namespace App\EofficeApp\Attendance\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Attendance\Entities\AttendanceSchedulingModifyRecordEntity;
use App\EofficeApp\Attendance\Traits\AttendanceTrait;
/**
 * 排班资源库类
 * 
 * @author 李志军
 * 
 * @since 2017-06-26
 */
class AttendanceSchedulingModifyRecordRepository extends BaseRepository
{
    use AttendanceTrait;

    public function __construct(AttendanceSchedulingModifyRecordEntity $entity)
    {
            parent::__construct($entity);
    }

    public function getCrurentDateModifyRecords($fields = ['*'])
    {
        return $this->entity->select($fields)->where('year',date('Y'))->where('month', date('m'))->where('day', date('d'))->get();
    }
    public function deleteCrurentDateModifyRecords($userId)
    {
        if(empty($userId)){
            return true;
        }
        return $this->entity->where('year',date('Y'))->where('month', date('m'))->where('day', date('d'))->whereIn('user_id', $userId)->delete();
    }
    public function getModifyRecordByMonth($year,$month,$userId)
    {
        return $this->entity->where('year',$year)->where('month',$month)->where('user_id', $userId)->orderBy('day','asc')->get();
    }
    
    public function getModifyRecordsByDateScopeAndUserIds($startDate, $endDate, $userIds)
    {
        return $this->entity->where('modify_date', '>=', $startDate)->where('modify_date', '<', $endDate)->whereIn('user_id', $userIds)->orderBy('modify_date', 'asc')->get();
    }
    public function getNearestModifyRecordByDateAndUserIds($date, $userIds) 
    {
        return $this->entity->where('modify_date', '>=', $date)->whereIn('user_id', $userIds)->orderBy('modify_date', 'asc')->groupBy('user_id')->get();
    }
    
    public function getNearestModifyRecord($year, $month, $userId)
    {
        //同年修改
        $ret = $this->entity->where('year', $year)->where('month', '>', $month)->where('user_id', $userId)->orderBy('month', 'asc')->orderBy('day', 'asc')->first();
        if ($ret) {
            return $ret;
        }
        //可能是跨年修改
        return $this->entity->where('year', '>', $year)->where('user_id', $userId)->orderBy('year', 'asc')->orderBy('month', 'asc')->orderBy('day', 'asc')->first();
    }

    public function getNearestModifyRecordByDate($signDate, $userId)
    {
        $signDateArray = explode('-', $signDate);
        $year = $signDateArray[0];
        $month = intval($signDateArray[1]);
        $day = intval($signDateArray[2]);
        //可能是在本月请假后的某天修改
        $ret = $this->entity->where('year', $year)->where('month', $month)->where('day', '>=', $day)->where('user_id', $userId)->orderBy('day', 'asc')->first();
        if ($ret) {
            return $ret;
        }
        //可能跨月
        return $this->getNearestModifyRecord($year, $month, $userId);
    }
}
