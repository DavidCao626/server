<?php
namespace App\EofficeApp\Attendance\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Attendance\Entities\AttendanceShiftsRestTimeEntity;
/**
 * 班次休息时间资源库类
 * 
 * @author 李志军
 * 
 * @since 2017-06-26
 */
class AttendanceShiftsRestTimeRepository extends BaseRepository
{
	
	public function __construct(AttendanceShiftsRestTimeEntity $entity)
	{
		parent::__construct($entity);
	}
    public function getRestTime($shiftId, $fields = ['*']) 
    {
        return $this->entity->select($fields)->where('shift_id',$shiftId)->orderBy('rest_begin', 'asc')->get();
    }
}
