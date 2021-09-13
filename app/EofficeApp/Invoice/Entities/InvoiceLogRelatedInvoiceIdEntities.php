<?php


namespace App\EofficeApp\Invoice\Entities;


use App\EofficeApp\Base\BaseEntity;

class InvoiceLogRelatedInvoiceIdEntities extends BaseEntity
{
    // 表名
    public $table 			= 'invoice_log_related_invoice_id';
    public $primaryKey		= 'related_id';
    public $timestamps = false;
}