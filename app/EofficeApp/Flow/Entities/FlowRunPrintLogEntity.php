<?php
namespace App\EofficeApp\Flow\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * 流程打印日志表
 *
 * @author zyx
 *
 * @since  20200506
 */
class FlowRunPrintLogEntity extends BaseEntity
{
	public $table = 'flow_run_print_log';
    public $timestamps = false;
    
    // 打印人
    function FlowRunPrintLogHasOneUser() {
        return $this->hasOne('App\EofficeApp\User\Entities\UserEntity', 'user_id', 'print_user_id');
    }
}