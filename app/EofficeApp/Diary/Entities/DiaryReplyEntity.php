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
class DiaryReplyEntity extends BaseEntity
{
    /**
     * 微博日志回复数据表
     *
     * @var string
     */
	public $table = 'diary_reply';

    /**
     * 主键
     *
     * @var string
     */
    public $primaryKey = 'diary_reply_id';

    /**
     * 软删除,应该被调整为日期的属性
     *
     * @var array
     */
    public $dates = ['deleted_at'];


    /**
     * 与用户表一对一关系
     *
     * @return object
     *
     * @author qishaobo
     *
     * @since  2016-01-27
     */
    public function hasOneUser()
    {
        return  $this->hasOne('App\EofficeApp\User\Entities\UserEntity', 'user_id','user_id');
    }
}
