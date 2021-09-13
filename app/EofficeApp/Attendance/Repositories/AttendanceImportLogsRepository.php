<?php
namespace App\EofficeApp\Attendance\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Attendance\Entities\AttendanceImportLogsEntity;
use App\EofficeApp\Attendance\Traits\AttendanceTrait;
class AttendanceImportLogsRepository extends BaseRepository
{
    use AttendanceTrait;
	public function __construct(AttendanceImportLogsEntity $entity)
	{
		parent::__construct($entity);
        $this->orderBy = ['import_datetime' => 'desc'];
	}
    
    public function getImportLogsCount($param)
    {
        $query = $this->entity;
        
        if(isset($param['search']) && !empty($param['search'])){
            $query->wheres($param['search']);
        }
        
        return $query->count();
    }
    public function getImportLogsList($param)
    {
        $param = $this->filterParam($param);
        $param['fields'][] = 'user.user_name as creator_name';
        $query = $this->entity->select($param['fields'])->leftJoin('user','user.user_id', '=', 'attend_import_logs.creator');
        
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
