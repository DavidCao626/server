<?php

namespace App\EofficeApp\Webmail\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * 邮件共享权限设置Entity类。
 */
class WebmailShareConfigEntity extends BaseEntity
{
	public $table = 'webmail_share_config';

    public function outbox()
    {
        return $this->HasMany('App\EofficeApp\Webmail\Entities\WebmailShareConfigRelationOutboxEntity', 'config_id', 'id');
    }
}