<?php
namespace App\EofficeApp\Attendance\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Attendance\Entities\AttendanceShiftsSignTimeEntity;
/**
 * 班次考勤时间资源库类
 * 
 * @author 李志军
 * 
 * @since 2017-06-26
 */
class AttendanceShiftsSignTimeRepository extends BaseRepository
{
	
	public function __construct(AttendanceShiftsSignTimeEntity $entity)
	{
		parent::__construct($entity);
	}
    
    public function getSignTime($shiftId, $fields = ['*']) 
    {
        return $this->entity->select($fields)->where('shift_id',$shiftId)->orderBy('sign_in_time', 'asc')->get();
    }
}
