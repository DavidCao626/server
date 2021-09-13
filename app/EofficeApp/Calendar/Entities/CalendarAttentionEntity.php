<?php

namespace App\EofficeApp\Calendar\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * 日程关注Entity类:提供日程关注实体。
 *
 */
class CalendarAttentionEntity extends BaseEntity
{
    /**
     * 日程关注人数据表
     *
     * @var string
     */
	public $table = 'calendar_attention';

    /**
     * 主键
     *
     * @var string
     */
    public $primaryKey = 'attention_id';

    /**
     * 日程关注人和用户表一对一关系
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
     * 日程被关注人和用户表一对一关系
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
