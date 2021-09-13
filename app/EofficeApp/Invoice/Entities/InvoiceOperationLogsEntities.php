<?php


namespace App\EofficeApp\Invoice\Entities;


use App\EofficeApp\Base\BaseEntity;

class InvoiceOperationLogsEntities extends BaseEntity
{
    // 表名
    public $table 			= 'invoice_operation_logs';

    public $primaryKey		= 'log_id';

    public function user()
    {
        return $this->belongsTo('App\EofficeApp\User\Entities\UserEntity', 'creator', 'user_id');
    }

    public function flow()
    {
        return $this->belongsTo('App\EofficeApp\Flow\Entities\FlowRunEntity', 'run_id', 'run_id');
    }
}