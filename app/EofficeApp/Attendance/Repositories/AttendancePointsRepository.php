<?php
namespace App\EofficeApp\Attendance\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Attendance\Entities\AttendancePointsEntity;
use App\EofficeApp\Attendance\Traits\AttendanceTrait;
/**
 * 班次排班映射资源库类
 * 
 * @author 李志军
 * 
 * @since 2017-06-26
 */
class AttendancePointsRepository extends BaseRepository
{
	use AttendanceTrait;
	public function __construct(AttendancePointsEntity $entity)
	{
		parent::__construct($entity);
        $this->orderBy = ['created_at' => 'desc'];
	}
    
    public function pointIsExists($id)
    {
        return $this->entity->where('id', $id)->count();
    }
    public function getPurviewPoints($userId,$deptId,$roleIds, $fields = ['*'])
    {
        return $this->entity->select($fields)
			->where(function ($query) use($userId,$deptId,$roleIds){
				$query->where(function($query)  use($userId,$deptId,$roleIds){
					$query->orWhereRaw('find_in_set(?,user_id)', [$userId])->orWhereRaw('find_in_set(?,dept_id)', [$deptId]);
	                foreach($roleIds as $roleId){
						$query->orWhereRaw('find_in_set(?,role_id)', [$roleId]);
	                }
                })->orWhere('all_member',1);
			})->get();
    }
    public function pointNameIsExists($pointName, $id = false)
    {
        $query = $this->entity->where('point_name', $pointName);
        
        if($id){
            $query->where('id','!=', $id);
        }
        
        return $query->count();
    }
    
    public function getPointsTotal($param)
    {
        $query = $this->entity;
        
        if(isset($param['search']) && !empty($param['search'])){
            $query->wheres($param['search']);
        }
        
        return $query->count();
    }
    
    public function getPointsList($param)
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
}