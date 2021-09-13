<?php

namespace app\EofficeApp\Report\Entities;

use App\EofficeApp\Base\BaseEntity;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReportDatasourceEntity extends BaseEntity {

    use SoftDeletes;

	
    public $table = 'report_datasource';

   
    public $primaryKey = 'datasource_id';
	
	
    protected $dates = ['deleted_at'];
}