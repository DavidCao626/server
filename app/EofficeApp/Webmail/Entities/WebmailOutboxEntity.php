<?php

namespace App\EofficeApp\Webmail\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * 发件箱Entity类:提供发件箱实体。
 *
 * @author qishaobo
 *
 * @since  2016-07-28 创建
 */
class WebmailOutboxEntity extends BaseEntity
{

    /** @var string 发件箱表 */
	public $table = 'webmail_outbox';

    /** @var string 主键 */
    public $primaryKey = 'outbox_id';

    /**
     * 是否使用created_at和updated_at
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * 发件箱和邮件一对多
     *
     * @return object
     */
    public function hasManyMails()
    {
        return  $this->hasMany('App\EofficeApp\Webmail\Entities\WebmailMailEntity', 'outbox_id', 'outbox_id');
    }

    /**
     * 发件箱和邮件统计一对一
     *
     * @return object
     */
    public function hasOneMailCount()
    {
        return  $this->hasOne('App\EofficeApp\Webmail\Entities\WebmailMailEntity', 'outbox_id', 'outbox_id');
    }

    /**
     * 发件箱创建人和用户信息一对一
     *
     * @return object
     *
     * @author qishaobo
     *
     * @since  2016-07-28
     */
    public function creatorName()
    {
        return  $this->HasOne('App\EofficeApp\User\Entities\UserEntity', 'user_id', 'outbox_creator');
    }

    /**
     * 发件箱创建人跟用户状态一对一
     *
     * @return object
     *
     * @author qishaobo
     *
     * @since  2016-07-28
     */
    public function userStatus()
    {
        return  $this->HasOne('App\EofficeApp\User\Entities\UserSystemInfoEntity', 'user_id', 'outbox_creator');
    }

}