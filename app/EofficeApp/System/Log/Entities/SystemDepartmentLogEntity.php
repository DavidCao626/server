<?php


namespace App\EofficeApp\System\Log\Entities;


use App\EofficeApp\Base\BaseEntity;

class SystemDepartmentLogEntity extends BaseEntity
{
    public $table = 'system_department_log';
    public $primaryKey = 'log_id';
    public $timestamps = false;

    public function hasOneUser()
    {
        return $this->HasOne('App\EofficeApp\User\Entities\UserEntity', 'user_id', 'log_creator');
    }
}