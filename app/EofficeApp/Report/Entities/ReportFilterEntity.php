<?php

namespace app\EofficeApp\Report\Entities;

use App\EofficeApp\Base\BaseEntity;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReportFilterEntity extends BaseEntity {

    public $table = 'report_filter';

    public $primaryKey = 'filter_id';

    public $timestamps = false;

}