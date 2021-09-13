<?php

namespace App\EofficeApp\Webmail\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * 邮件Entity类:提供邮件实体。
 *
 * @author qishaobo
 *
 * @since  2016-07-28 创建
 */
class WebmailMailEntity extends BaseEntity
{

    /** @var string 邮件表 */
    public $table = 'webmail_mail';

    /** @var string 主键 */
    public $primaryKey = 'mail_id';

    /**
     * 是否使用created_at和updated_at
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * 邮件创建人和用户信息一对一
     *
     * @return object
     *
     * @author qishaobo
     *
     * @since  2016-07-28
     */
    public function creatorName()
    {
        return $this->HasOne('App\EofficeApp\User\Entities\UserEntity', 'user_id', 'mail_creator');
    }

    /**
     * 获得邮件的标签。
     */
    public function tags()
    {
        return $this->belongsToMany('App\EofficeApp\Webmail\Entities\WebmailTagEntity', 'webmail_mail_tag', 'mail_id', 'tag_id');
    }

    public function outbox()
    {
        return $this->HasOne('App\EofficeApp\Webmail\Entities\WebmailOutboxEntity', 'outbox_id', 'outbox_id');
    }
}
