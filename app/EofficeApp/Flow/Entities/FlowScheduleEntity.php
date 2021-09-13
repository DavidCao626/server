<?php
namespace App\EofficeApp\Flow\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * 流程定时触发设置
 *
 * @author zyx
 *
 * @since  20200408
 */
class FlowScheduleEntity extends BaseEntity
{
    /**
     * 表名
     *
     * @var string
     */
    public $table = 'flow_schedule';

    public $timestamps = false;

    function flowScheduleHasOneType() {
        return $this->hasOne('App\EofficeApp\Flow\Entities\FlowTypeEntity', 'flow_id', 'flow_id');
    }
}