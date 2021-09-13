<?php
namespace App\EofficeApp\Attendance\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Attendance\Entities\AttendanceShiftsEntity;
use App\EofficeApp\Attendance\Traits\AttendanceTrait;
/**
 * 班次管理资源库类
 * 
 * @author 李志军
 * 
 * @since 2017-06-26
 */
class AttendanceShiftsRepository extends BaseRepository
{
	use AttendanceTrait;
    
	public function __construct(AttendanceShiftsEntity $entity)
	{
		parent::__construct($entity);
        $this->orderBy = ['created_at' => 'desc'];
	}
    /**
     * 按查询条件获取一个班次
     * 
     * @param array $where
     * @param array $fields
     * 
     * @return boolean | object
     */
    public function getOneShift($where = false,$fields = ['*'])
    {
        if($where){
            return $this->entity->select($fields)->wheres($where)->first();
        }
        
        return false;
    }
    /**
     * 获取班次列表
     * 
     * @param array $param
     * 
     * @return array
     */
    public function getShiftList($param)
    {
        $param = $this->filterParam($param);
        
        $query = $this->entity->select($param['fields'])->where('shift_status',1);
        
        if(isset($param['search']) && !empty($param['search'])){
            $query->wheres($param['search']);
        }
        
        $query->orders($param['order_by']);
        
        if($param['page'] == 0){
            return $query->get();
        }
        
        return $query->parsePage($param['page'], $param['limit'])->get();
    }
    /**
     * 获取班次总数
     * 
     * @param array $param
     * 
     * @return int
     */
    public function getShiftTotal($param)
    {
        $query = $this->entity->where('shift_status',1);
        
        if(isset($param['search']) && !empty($param['search'])){
            $query->wheres($param['search']);
        }
        
        return $query->count();
    }
    /**
     * 判断班次名称是否重复
     * 
     * @param string $shiftName
     * @param int $shiftId
     * 
     * @return int
     */
    public function shiftNameIsRepeat($shiftName, $shiftId = false)
    {
        $query = $this->entity->where('shift_status',1)->where('shift_name', $shiftName);
        
        if($shiftId){
            $query->where('shift_id','!=', $shiftId);
        }
        
         return $query->count();
    }
    
    public function getAllShifts($fields = ['*'])
    {
        return $this->entity->select($fields)->get();
    }
    
    public function getShiftsById(array $ids, array $fields = ['*'], $diffDelete = false)
    {
        if(empty($ids)){
            return [];
        }
        $query = $this->entity->select($fields)->whereIn('shift_id', $ids);
        if ($diffDelete) {
            $query = $query->where('shift_status',1);
        }
        return $query->get();
    }
}
