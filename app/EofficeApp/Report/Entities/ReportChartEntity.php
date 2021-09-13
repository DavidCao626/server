<?php

namespace app\EofficeApp\Report\Entities;

use App\EofficeApp\Base\BaseEntity;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReportChartEntity extends BaseEntity {

    use SoftDeletes;

	
    public $table = 'report_chart';

   
    public $primaryKey = 'chart_id';
	
	
    protected $dates = ['deleted_at'];

}