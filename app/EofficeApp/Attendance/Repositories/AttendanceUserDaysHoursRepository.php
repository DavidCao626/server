<?php
namespace App\EofficeApp\Attendance\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Attendance\Entities\AttendanceUserDaysHoursEntity;
/**
 * 已废弃
 * 
 * 班次管理资源库类
 * 
 * @author 李志军
 * 
 * @since 2017-06-26
 */
class AttendanceUserDaysHoursRepository extends BaseRepository
{
	public function __construct(AttendanceUserDaysHoursEntity $entity)
	{
		parent::__construct($entity);
	}
    
    public function getOneStat($year, $month, $userId)
    {
        return $this->entity->where('year',$year)->where('month', $month)->where('user_id', $userId)->first();
    }
    public function getMoreStat($year, $month, $userId)
    {
        $stat = $this->entity->where('year',$year)->where('month', $month)->whereIn('user_id', $userId)->get();
        $map = [];
        if(count($stat) > 0) {
            foreach($stat as $item){
                $map[$item->user_id] = $item;
            }
        }
        
        return $map;
    }
}
