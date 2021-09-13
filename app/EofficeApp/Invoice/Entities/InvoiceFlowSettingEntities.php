<?php


namespace App\EofficeApp\Invoice\Entities;


use App\EofficeApp\Base\BaseEntity;

class InvoiceFlowSettingEntities extends BaseEntity
{
    // 表名
    public $table 			= 'invoice_flow_setting';

    public $primaryKey		= 'setting_id';

    public function user()
    {
        return $this->belongsTo('App\EofficeApp\User\Entities\UserEntity', 'creator', 'user_id');
    }

    public function actions()
    {
        return $this->hasMany('App\EofficeApp\Invoice\Entities\InvoiceFlowNodeActionSettingEntities', 'setting_id', 'setting_id');
    }

    public function flow()
    {
        return $this->hasOne('App\EofficeApp\Flow\Entities\FlowTypeEntity', 'flow_id', 'workflow_id');
    }

}