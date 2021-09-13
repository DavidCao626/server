<?php


namespace App\EofficeApp\Elastic\Entities;


use App\EofficeApp\Base\BaseEntity;
use App\EofficeApp\Elastic\Configurations\ElasticTables;

class ElasticSearchOperationLogEntity  extends BaseEntity
{
    public $primaryKey = 'id';

    public $table = ElasticTables::ELASTIC_OPERATION_LOG_TABLE;

    public $timestamps = false;

    public function user()
    {
        return  $this->HasOne('App\EofficeApp\User\Entities\UserEntity','user_id','operator');
    }
}