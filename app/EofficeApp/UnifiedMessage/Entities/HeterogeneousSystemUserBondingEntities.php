<?php
namespace App\EofficeApp\UnifiedMessage\Entities;

use App\EofficeApp\Base\BaseEntity;

class HeterogeneousSystemUserBondingEntities extends BaseEntity
{
    public $table = 'heterogeneous_system_user_bonding';
    public function userInfoHasOne()
    {
        return $this->hasOne('App\EofficeApp\User\Entities\UserInfoEntity', 'user_id', 'oa_user_id'); // foreignKey heterogeneous_system_message_type表字段 localKey当前表关联字段
    }
    public function heterogeneousSystemHasOne()
    {
        return $this->hasOne('App\EofficeApp\UnifiedMessage\Entities\HeterogeneousSystemEntities', 'system_code', 'heterogeneous_system_code'); // foreignKey heterogeneous_system_message_type表字段 localKey当前表关联字段
    }
}
