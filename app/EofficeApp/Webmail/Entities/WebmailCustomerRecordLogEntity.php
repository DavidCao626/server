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
class WebmailCustomerRecordLogEntity extends BaseEntity
{
    /** @var string 邮件服务器表 */
	public $table = 'webmail_customer_record_log';

    public function customer()
    {
        return $this->HasOne('App\EofficeApp\Customer\Entities\CustomerEntity', 'customer_id', 'customer_id');
    }

    public function linkman()
    {
        return $this->HasOne('App\EofficeApp\Customer\Entities\LinkmanEntity', 'linkman_id', 'linkman_id');
    }
}