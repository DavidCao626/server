<?php
namespace App\EofficeApp\Attendance\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Attendance\Entities\AttendanceMobileRecordsEntity;
/**
 * 班次排班映射资源库类
 * 
 * @author 李志军
 * 
 * @since 2017-06-26
 */
class AttendanceMobileRecordsRepository extends BaseRepository
{
	
	public function __construct(AttendanceMobileRecordsEntity $entity)
	{
		parent::__construct($entity);
	}
    public function hasDataInDay($date)
    {
        return $this->entity->where('sign_date', $date)->count();
    }
    public function getAllRecordByOneUserOneDay($userId, $signDate, $fields = ['*'])
    {
        return $this->entity->select($fields)->where('sign_category', 1)->where('user_id', $userId)->where('sign_date', $signDate)->get();
    }
    
    public function getOneUserRecords($userId, $search, $fields = ['*'], $orderBy = [])
    {
        return $this->entity->select($fields)->where('sign_category', 1)->where('user_id', $userId)->wheres($search)->orders($orderBy)->get();
    }
    
    public function getRecords($search, $fields = ['*'])
    {
        return $this->entity->select($fields)->wheres($search)->get();
    }
    public function getRecordsTotal($search)
    {
        return $this->entity->wheres($search)->count();
    }
}