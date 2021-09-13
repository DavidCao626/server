<?php

namespace App\EofficeApp\Dgwork\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Dgwork\Entities\DgworkZjPointEntity;
use DB;

class DgworkZjPointRepository extends BaseRepository
{

    public function __construct(DgworkZjPointEntity $entity)
    {
        parent::__construct($entity);
    }

    //清空表
    public function truncateDgworkZjPoint()
    {
        return $this->entity->truncate();
    }

    public function getDgworkZjPoint($where = [])
    {
        $result = $this->entity->wheres($where)->first();
        if($result){
            return $result->toArray();
        }
        return $result;
    }

    // public function update($data)
    // {
    //     $result = $this->entity->where('dgwork_zj_point_id',1)->update($data);

    // }

    public function addDgworkZjPoint($data)
    {
        return $result = $this->entity->create($data);
    }

}
