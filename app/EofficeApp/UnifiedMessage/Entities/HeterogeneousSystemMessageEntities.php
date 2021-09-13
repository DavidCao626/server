<?php
namespace App\EofficeApp\UnifiedMessage\Entities;

use App\EofficeApp\Base\BaseEntity;
use Illuminate\Database\Eloquent\SoftDeletes;

class HeterogeneousSystemMessageEntities extends BaseEntity
{
    use SoftDeletes;
    public $table = 'heterogeneous_system_message';
    public function systemHasOne()
    {
        return $this->hasOne('App\EofficeApp\UnifiedMessage\Entities\HeterogeneousSystemEntities', 'id', 'heterogeneous_system_id');
    }
    public function messageTypeHasOne()
    {
        return $this->hasOne('App\EofficeApp\UnifiedMessage\Entities\HeterogeneousSystemMessageTypeEntities', 'id', 'message_type_id'); // foreignKey heterogeneous_system_message_type表字段 localKey当前表关联字段
    }
}
