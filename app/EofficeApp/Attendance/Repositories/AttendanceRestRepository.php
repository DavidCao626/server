<?php
namespace App\EofficeApp\Attendance\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Attendance\Entities\AttendanceRestEntity;
/**
 * 排班资源库类
 * 
 * @author 李志军
 * 
 * @since 2017-06-26
 */
class AttendanceRestRepository extends BaseRepository
{
	public function __construct(AttendanceRestEntity $entity)
	{
		parent::__construct($entity);
	}

    /**
     * 获取所有方案
     */
    public function getAllRests($withTrashed = false)
    {
        $query = $this->entity;
        if ($withTrashed) {
            $query = $query->withTrashed();
        }
        return $query->get();
    }

    /**
     * 通过scheme_id获取某个方案的所有节假日
     * @param $scheme_id
     * @param array $fields
     * @return object
     */
    public function getSchemeDetailById($scheme_id, $fields = ['*'])
    {
        if ($scheme_id) {
            return $this->entity->select($fields)->where('scheme_id', $scheme_id)->get();
        }
        return false;
    }
    
    public function getRestsByRestIds($restIds)
    {
        return $this->entity->whereIn('rest_id', $restIds)->get();
    }
}
