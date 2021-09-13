<?php
namespace App\EofficeApp\UnifiedMessage\Entities;

use App\EofficeApp\Base\BaseEntity;

class HeterogeneousSystemIntegrationLogEntities extends BaseEntity
{
    public $table = 'heterogeneous_system_integration_log';
    public function userHasOne()
    {
        return $this->hasOne('App\EofficeApp\User\Entities\UserEntity', 'user_id', 'operator'); // foreignKey heterogeneous_system_message_type表字段 localKey当前表关联字段
    }
}
