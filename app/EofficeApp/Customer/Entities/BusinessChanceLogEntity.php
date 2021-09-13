<?php

namespace App\EofficeApp\Customer\Entities;

use App\EofficeApp\Base\BaseEntity;

class BusinessChanceLogEntity extends BaseEntity
{
    /** @var string 业务机会记录表 */
    public $table = 'customer_business_chance_log';

    /** @var string 主键 */
    public $primaryKey = 'chance_log_id';

    public function hasOneUser()
    {
        return $this->HasOne('App\EofficeApp\User\Entities\UserEntity', 'user_id', 'chance_log_creator');
    }
}
