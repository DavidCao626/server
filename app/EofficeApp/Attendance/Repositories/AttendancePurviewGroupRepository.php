<?php
namespace App\EofficeApp\Attendance\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Attendance\Entities\AttendancePurviewGroupEntity;
use App\EofficeApp\Attendance\Traits\AttendanceTrait;
class AttendancePurviewGroupRepository extends BaseRepository
{
    use AttendanceTrait;
    public function __construct(AttendancePurviewGroupEntity $entity)
    {
            parent::__construct($entity);
    }

    public function getPurviewGroupTotal($param)
    {
        $query = $this->entity;

        if(isset($param['search']) && !empty($param['search'])){
            $query->wheres($param['search']);
        }

        return $query->count();
    }
    public function getPurviewGroupList($param)
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
    public function getGroupInfo($groupId) {
        return $this->entity->where('group_id', $groupId)->first();
    }
    public function insertGetId($data = []) {
        return $this->entity->insertGetId($data);
    }
}