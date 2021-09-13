<?php
namespace App\EofficeApp\System\Log\Entities;
use App\EofficeApp\Base\BaseEntity;
class SystemLoginLogEntity  extends BaseEntity
{
    public $table 	    = 'eo_log_system';
    public $primaryKey = 'log_id';
    public $timestamps = false;
    public function hasOneUser()
    {
        return  $this->HasOne('App\EofficeApp\User\Entities\UserEntity', 'user_id', 'log_creator');
    }
}