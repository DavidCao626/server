<?php

namespace App\EofficeApp\System\Log\Entities;

use App\EofficeApp\Base\BaseEntity;

class SystemFlowLogEntity extends BaseEntity
{
    public $table 	    = 'system_flow_log';
    public $primaryKey = 'log_id';
    public $timestamps = false;

}