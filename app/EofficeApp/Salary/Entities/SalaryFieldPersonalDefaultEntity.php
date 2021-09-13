<?php
namespace App\EofficeApp\Salary\Entities;

use App\EofficeApp\Base\BaseEntity;

class SalaryFieldPersonalDefaultEntity extends BaseEntity
{
    public $table = 'salary_field_personal_default';

    function hasPersonnel()
    {
        return $this->hasOne('App\EofficeApp\PersonnelFiles\Entities\PersonnelFilesEntity','id','user_id');
    }
}
