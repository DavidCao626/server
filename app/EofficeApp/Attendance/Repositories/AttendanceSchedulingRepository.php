<?php
namespace App\EofficeApp\Attendance\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Attendance\Entities\AttendanceSchedulingEntity;
use App\EofficeApp\Attendance\Traits\AttendanceTrait;
/**
 * 排班资源库类
 *
 * @author 李志军
 *
 * @since 2017-06-26
 */
class AttendanceSchedulingRepository extends BaseRepository
{
	use AttendanceTrait;

	public function __construct(AttendanceSchedulingEntity $entity)
	{
		parent::__construct($entity);
	}

    public function getSchedulingTotal($param)
    {
        $query = $this->entity;

        if(isset($param['search']) && !empty($param['search'])){
            $query->wheres($param['search']);
        }

        return $query->count();
    }

    public function getSchedulingList($param)
    {
        $param = $this->filterParam($param);

        $query = $this->entity->select($param['fields']);

        if(isset($param['search']) && !empty($param['search'])){
            $query->wheres($param['search']);
        }

        $query->orders($param['order_by']);

        if($param['page'] == 0){
            return $query->get();
        }

        return $query->parsePage($param['page'], $param['limit'])->get();
    }

    public function getOneScheduling($where = false,$fields = ['*'])
    {
        if($where){
            return $this->entity->select($fields)->wheres($where)->first();
        }

        return false;
    }

    public function getSchedulingBySchedulingIds($schedulingIds)
    {
        return $this->entity->whereIn('scheduling_id', $schedulingIds)->get();
    }
    public function getAllSchedulings()
    {
        return $this->entity->get();
    }
}
