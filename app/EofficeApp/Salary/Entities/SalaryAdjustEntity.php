<?php
namespace App\EofficeApp\Salary\Entities;

use App\EofficeApp\Base\BaseEntity;

class SalaryAdjustEntity  extends BaseEntity
{
    public $table 			= 'salary_adjust';
    public $primaryKey      = 'adjust_id';
    public $timestamps      = false;

    function hasPersonnel()
    {
        return $this->hasOne('App\EofficeApp\PersonnelFiles\Entities\PersonnelFilesEntity','id','user_id');
    }
}