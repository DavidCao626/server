<?php

namespace app\EofficeApp\Charge\Entities;

use App\EofficeApp\Base\BaseEntity;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 费用类别
 * 
 * @author:喻威
 * 
 * @since：2015-10-19
 * 
 */
class ChargeTypeEntity extends BaseEntity {

    use SoftDeletes;

    public $table = 'charge_type';
    public $primaryKey = 'charge_type_id';
    protected $dates = ['deleted_at'];

}
