<?php

namespace App\EofficeApp\Vacation\Entities;

use App\EofficeApp\Base\BaseEntity;

class VacationMemberEntity extends BaseEntity
{

    protected $primaryKey = 'id';

    public $table = 'vacation_member';

    protected $fillable = [
        'vacation_id',
        'all_member',
        'dept_id',
        'role_id',
        'user_id'
    ];
}