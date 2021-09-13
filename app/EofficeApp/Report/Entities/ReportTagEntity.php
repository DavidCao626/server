<?php

namespace app\EofficeApp\Report\Entities;

use App\EofficeApp\Base\BaseEntity;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReportTagEntity extends BaseEntity {

    use SoftDeletes;

	
    public $table = 'report_tag';

   
    public $primaryKey = 'tag_id';
	
	
    protected $dates = ['deleted_at'];

}