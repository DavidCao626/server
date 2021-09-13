<?php
namespace App\EofficeApp\Attendance\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Attendance\Entities\AttendanceSchedulingShiftEntity;
/**
 * 班次排班映射资源库类
 * 
 * @author 李志军
 * 
 * @since 2017-06-26
 */
class AttendanceSchedulingShiftRepository extends BaseRepository
{
	
	public function __construct(AttendanceSchedulingShiftEntity $entity)
	{
		parent::__construct($entity);
	}
    
    public function getSchedulingByShiftId($shiftId, $fileds=['*'])
    {
        return $this->select($fileds)->where('shift_id', $shiftId)->get();
    }
}
