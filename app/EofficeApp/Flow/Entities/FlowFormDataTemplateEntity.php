<?php

namespace app\EofficeApp\Flow\Entities;

use App\EofficeApp\Base\BaseEntity;
use Illuminate\Database\Eloquent\SoftDeletes;

class FlowFormDataTemplateEntity extends BaseEntity {

    use SoftDeletes;


    public $table = 'flow_form_data_template';


    public $primaryKey = 'id';


    protected $dates = ['deleted_at'];

}