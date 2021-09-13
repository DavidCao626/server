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
class WebmailMailTagEntity extends BaseEntity
{

    /** @var string 邮件标签表 */
    public $table = 'webmail_mail_tag';

    /**
     * 是否使用created_at和updated_at
     *
     * @var bool
     */
    public $timestamps = false;
}
