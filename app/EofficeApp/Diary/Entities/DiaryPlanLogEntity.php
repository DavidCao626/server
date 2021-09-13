<?php

namespace App\EofficeApp\Diary\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * 工作计划的日志
 *
 * @author dp
 *
 * @since  2017-06-20 创建
 */
class DiaryPlanLogEntity extends BaseEntity
{
    /**
     * 计划日志
     *
     * @var string
     */
	public $table = 'diary_plan_log';

    /**
     * 主键
     *
     * @var string
     */
    public $primaryKey = 'plan_log_id';

    /**
     * 软删除,应该被调整为日期的属性
     *
     * @var array
     */
    public $dates = ['deleted_at'];

}
