<?php
namespace App\EofficeApp\Attendance\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Attendance\Entities\AttendanceRecordsEntity;
use App\EofficeApp\Attendance\Traits\AttendanceTrait;
use DB;
/**
 * 班次排班映射资源库类
 * 
 * @author 李志军
 * 
 * @since 2017-06-26
 */
class AttendanceRecordsRepository extends BaseRepository
{
	use AttendanceTrait;
	public function __construct(AttendanceRecordsEntity $entity)
	{
		parent::__construct($entity);
        $this->orderBy = ['created_at' => 'desc'];
	}
    
    public function recordIsExists($where = false)
    {
        if($where){
            return $this->entity->wheres($where)->count();
        }
        
        return false;
    }
    
    public function getOneAttendRecord($wheres, $fields = ['*'])
    {
        return $this->entity->select($fields)->wheres($wheres)->first();
    }
    public function getRecordsCount($wheres)
    {
        $query = $this->entity->wheres($wheres);
        return $query->count();
    }
    public function getRecords($wheres, $fields = ['*'],$orderBy = false)
    {
        $allCalibration = false;
        if(isset($wheres['calibration_status']) && $wheres['calibration_status'][0] == 'all'){
            $allCalibration = true;
            unset($wheres['calibration_status']);
        }
        
        $query = $this->entity->select($fields)->wheres($wheres);
        if($allCalibration) {
            $query = $query->where(function ($query){
                $query->where(function($query){
                    $query->where('is_lag', 1)->orWhere('is_leave_early',1)->orWhere('sign_out_time','');
                })->orWhereIn('calibration_status',[1,2,3]);
            });
        }
        if($orderBy){
            $query = $query->orders($orderBy);
        }
        return $query->get();
    }
    
    public function getRecordsTotal($param)
    {
        $query = $this->entity;
        
        if(isset($param['search']) && !empty($param['search'])){
            $allCalibration = false;
            if(isset($param['search']['calibration_status']) && $param['search']['calibration_status'][0] === 'all') {
                $allCalibration = true;
                unset($param['search']['calibration_status']);
            }
            $query = $query->wheres($param['search']);
            if(isset($param['search']['sign_out_time']) && $param['search']['sign_out_time'][0] == 'sign_out_normal'){
                $query = $query->where('sign_out_time','!=','');
            }
            if(isset($param['search']['calibration_status']) && $param['search']['calibration_status'][0] === 0){
                $query = $query->where('attend_type',1)->where(function ($query){
                    $query->where('is_lag', 1)->orWhere('is_leave_early',1)->orWhere('sign_out_time','');
                });
            } else if($allCalibration) {
                $query = $query->where('attend_type',1)->where(function ($query){
                    $query->where(function($query){
                        $query->where('is_lag', 1)->orWhere('is_leave_early',1)->orWhere('sign_out_time','');
                    })->orWhereIn('calibration_status',[1,2,3]);
                });
            }
        }
        
        return $query->count();
    }
    
    public function getRecordsLists($param)
    {
        $param = $this->filterParam($param);
        
        $query = $this->entity->select($param['fields']);
        
        if(isset($param['search']) && !empty($param['search'])){
            $allCalibration = false;
            
            if(isset($param['search']['calibration_status']) && $param['search']['calibration_status'][0] === 'all') {
                $allCalibration = true;
                unset($param['search']['calibration_status']);
            }
            $query->wheres($param['search']);
            if(isset($param['search']['sign_out_time']) && $param['search']['sign_out_time'][0] == 'sign_out_normal'){
                $query->where('sign_out_time','!=','');
            }
        }
        
        $query->orders($param['order_by']);
        
        if($param['page'] == 0){
            return $query->get();
        }
        
        return $query->parsePage($param['page'], $param['limit'])->get();
    }
    public function addAttendanceRecord($datas){
        foreach ($datas as $data) {
            $user_id = !empty($data['user_id'])?$data['user_id']:'';
            $sign_date = !empty($data['sign_date'])?$data['sign_date']:'';
            $sign_in_time = !empty($data['sign_in_time'])?$data['sign_in_time']:'';
            $record = DB::table("attendance_machine_records")->where("user_id",$user_id)->where("sign_date",$sign_date)->where("sign_in_time",$sign_in_time)->first();
            if (empty($record)) {
               DB::table("attendance_machine_records")->insert($data);
            }
        }
        
    }

    public function getAttendanceRecord($user_id,$sign_date,$type="get"){
        if ($type == "count") {
            return DB::table("attendance_machine_records")->where("user_id",$user_id)->where("sign_date",$sign_date)->count();
        }else{
            return DB::table("attendance_machine_records")->where("user_id",$user_id)->where("sign_date",$sign_date)->get();
        }
        
        
    }
}