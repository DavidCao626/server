<?php

namespace App\EofficeApp\Dgwork\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Dgwork\Entities\DgworkConfigEntity;
use DB;

class DgworkConfigRepository extends BaseRepository
{

    public function __construct(DgworkConfigEntity $entity)
    {
        parent::__construct($entity);
    }

    //清空表
    public function truncateDgwork()
    {
        return $this->entity->truncate();
    }

    public function getDgworkConfig($where = [])
    {
        $result = $this->entity->wheres($where)->first();
        if($result){
            return $result->toArray();
        }
        return $result;
    }

    public function update($data)
    {
        $result = $this->entity->where('dgwork_config_id',1)->update($data);

    }

    public function addDgworkConfig($data)
    {
        return $result = $this->entity->create($data);
    }

}
