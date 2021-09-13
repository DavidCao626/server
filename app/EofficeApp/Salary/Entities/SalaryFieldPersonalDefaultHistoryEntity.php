<?php
namespace App\EofficeApp\Salary\Entities;

use App\EofficeApp\Base\BaseEntity;

class SalaryFieldPersonalDefaultHistoryEntity extends BaseEntity
{
    public $table = 'salary_field_personal_default_history';

    public $primaryKey = 'id';

    public $timestamps = false;

    function hasPersonnel()
    {
        return $this->hasOne('App\EofficeApp\PersonnelFiles\Entities\PersonnelFilesEntity','id','user_id');
    }
}
