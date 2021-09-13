<?php

namespace App\EofficeApp\Diary\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * 微博关注Entity类:提供微博关注实体。
 *
 * @author qishaobo
 *
 * @since  2015-10-20 创建
 */
class DiaryAttentionEntity extends BaseEntity
{
    /**
     * 微博日志关注人数据表
     *
     * @var string
     */
	public $table = 'diary_attention';

    /**
     * 主键
     *
     * @var string
     */
    public $primaryKey = 'attention_id';

    /**
     * 微博关注人和用户表一对一关系
     *
     * @return object
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    public function userAttention()
    {
        return  $this->hasOne('App\EofficeApp\User\Entities\UserEntity', 'user_id','attention_person');
    }

    /**
     * 微博被关注人和用户表一对一关系
     *
     * @return object
     *
     * @author qishaobo
     *
     * @since  2015-12-21
     */
    public function userAttentionToPerson()
    {
        return  $this->hasOne('App\EofficeApp\User\Entities\UserEntity', 'user_id','attention_to_person');
    }
}
