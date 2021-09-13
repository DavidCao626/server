<?php


namespace App\EofficeApp\Invoice\Entities;


use App\EofficeApp\Base\BaseEntity;

class InvoiceFlowNodeActionSettingEntities extends BaseEntity
{
    // 表名
    public $table 			= 'invoice_flow_node_action_setting';

    public $primaryKey		= 'action_setting_id';

    /**
     * 不可被批量赋值的属性。
     *
     * @var array
     */
    protected $guarded = ['action_setting_id'];
}