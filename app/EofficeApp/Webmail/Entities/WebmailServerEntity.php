<?php

namespace App\EofficeApp\Webmail\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * 邮件服务器Entity类:提供邮件服务器实体。
 *
 * @author qishaobo
 *
 * @since  2016-07-28 创建
 */
class WebmailServerEntity extends BaseEntity
{

    /** @var string 邮件服务器表 */
	public $table = 'webmail_server';

    /** @var string 主键 */
    public $primaryKey = 'server_id';


}