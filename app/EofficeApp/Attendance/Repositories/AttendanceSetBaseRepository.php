<?php
namespace App\EofficeApp\Attendance\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Attendance\Entities\AttendanceSetBaseEntity;
class AttendanceSetBaseRepository extends BaseRepository
{
	public function __construct(AttendanceSetBaseEntity $entity)
	{
		parent::__construct($entity);
	}
    public function paramExists($paramKey)
    {
        return $this->entity->where('param_key', $paramKey)->count() > 0 ;
    }
    public function getAllParams()
    {
        return $this->entity->get();
    }
    public function getParamValue($paramKey)
    {
        $info = $this->entity->select(['param_value'])->where('param_key', $paramKey)->first();
        
        return $info ? $info->param_value : false;
    }
    public function getParamsByKeys($paramKeys)
    {
        return $this->entity->select(['param_key', 'param_value'])->whereIn('param_key', $paramKeys)->get();
    }
}