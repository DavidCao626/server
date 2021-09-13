<?php
namespace App\EofficeApp\Attendance\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Attendance\Entities\AttendanceStatEntity;
/**
 * 班次排班映射资源库类
 * 
 * @author 李志军
 * 
 * @since 2017-06-26
 */
class AttendanceStatRepository extends BaseRepository
{
	
	public function __construct(AttendanceStatEntity $entity)
	{
		parent::__construct($entity);
	}
    
    public function getOneMonthStatByUser($userId,$year,$month)
    {
        return $this->entity->where('user_id', $userId)->where('year', $year)->where('month', $month)->first();
    }
}