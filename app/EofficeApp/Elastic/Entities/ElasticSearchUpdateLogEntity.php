<?php


namespace App\EofficeApp\Elastic\Entities;


use App\EofficeApp\Base\BaseEntity;
use App\EofficeApp\Elastic\Configurations\ElasticTables;

class ElasticSearchUpdateLogEntity extends BaseEntity
{
    public $primaryKey = 'id';

    public $table = ElasticTables::ELASTIC_UPDATE_LOG_TABLE;

    public $timestamps = false;

    public function user()
    {
        return  $this->HasOne('App\EofficeApp\User\Entities\UserEntity','user_id','operator');
    }
}