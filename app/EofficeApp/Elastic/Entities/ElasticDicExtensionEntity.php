<?php


namespace App\EofficeApp\Elastic\Entities;


use App\EofficeApp\Base\BaseEntity;
use App\EofficeApp\Elastic\Configurations\ElasticTables;
use Illuminate\Database\Eloquent\SoftDeletes;

class ElasticDicExtensionEntity  extends BaseEntity
{
    use SoftDeletes;

    public $primaryKey = 'id';

    public $table = ElasticTables::ELASTIC_DIC_EXTENSION_TABLE;

    public $timestamps = false;

    public function user()
    {
        return  $this->HasOne('App\EofficeApp\User\Entities\UserEntity','user_id','operator');
    }
}