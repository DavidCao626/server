<?php

namespace App\EofficeApp\Customer\Entities;

use App\EofficeApp\Base\BaseEntity;


class VisitReminderEntity extends BaseEntity
{
    /** @var string 客户访问计划表 */
	public $table = 'customer_will_visit_reminder';

    public $timestamps = false;

    public function hasOneUser()
    {
        return  $this->HasOne('App\EofficeApp\User\Entities\UserEntity','user_id','user_id');
    }
}