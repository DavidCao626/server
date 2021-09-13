<?php

namespace App\EofficeApp\System\ShortMessage\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * 手机短信Entity类:提供手机短信数据表实体
 *
 * @author qishaobo
 *
 * @since  2017-03-06 创建
 */
class ShortMessageEntity extends BaseEntity
{
    /**
     * 手机短信数据表
     *
     * @var string
     */
	public $table = 'short_message';

    /**
     * 主键
     *
     * @var string
     */
    public $primaryKey = 'sms_send_id';

    public $timestamps = false;

    /**
     * 短息发送人和用户一对一
     *
     * @return object
     *
     * @author qishaobo
     *
     * @since  2017-03-07
     */
    public function fromOneUser()
    {
        return  $this->HasOne('App\EofficeApp\User\Entities\UserEntity', 'user_id', 'user_from');
    }

    /**
     * 短息接收人和用户一对一
     *
     * @return object
     *
     * @author qishaobo
     *
     * @since  2017-03-07
     */
    public function toOneUser()
    {
        return  $this->HasOne('App\EofficeApp\User\Entities\UserEntity', 'user_id', 'user_to');
    }
}