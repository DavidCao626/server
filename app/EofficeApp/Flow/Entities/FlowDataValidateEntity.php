<?php

namespace app\EofficeApp\Flow\Entities;

use App\EofficeApp\Base\BaseEntity;
use Illuminate\Database\Eloquent\SoftDeletes;

class FlowDataValidateEntity extends BaseEntity {

    use SoftDeletes;


    public $table = 'flow_data_validate';


    public $primaryKey = 'id';


    protected $dates = ['deleted_at'];

    protected $hidden = [
        'updated_at','created_at','deleted_at'
    ];

}