<?php

namespace App\EofficeApp\Diary\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * 微博回复Entity类:提供微博回复实体。
 *
 * @author qishaobo
 *
 * @since  2015-10-20 创建
 */
class DiaryTemplateUserModalEntity extends BaseEntity
{
    /**
     * 微博日志回复数据表
     *
     * @var string
     */
	public $table = 'diary_template_user_modal';

    /**
     * 软删除,应该被调整为日期的属性
     *
     * @var array
     */
    public $dates = ['deleted_at'];

}
