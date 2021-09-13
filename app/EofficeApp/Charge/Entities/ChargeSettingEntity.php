<?php

namespace app\EofficeApp\Charge\Entities;

use App\EofficeApp\Base\BaseEntity;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 费用设置实体
 * 
 * @author:喻威
 * 
 * @since：2015-10-19
 * 
 */
class ChargeSettingEntity extends BaseEntity {

    use SoftDeletes;

    public $table = 'charge_setting';
    public $primaryKey = 'charge_setting_id';
    protected $dates = ['deleted_at'];

}
