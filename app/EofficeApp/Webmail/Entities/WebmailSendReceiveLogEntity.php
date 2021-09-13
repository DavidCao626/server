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
class WebmailSendReceiveLogEntity extends BaseEntity
{

    /** @var string 邮件表 */
    public $table = 'webmail_send_receive_log';

    /** @var string 主键 */
    public $primaryKey = 'log_id';

    /**
     * 是否使用created_at和updated_at
     *
     * @var bool
     */
//    public $timestamps = false;

    /**
     * 邮件标签和邮件一对多
     *
     * @return object
     *
     * @since  2020-01-22
     */
    public function Mail()
    {
        return  $this->HasOne('App\EofficeApp\Webmail\Entities\WebmailMailEntity', 'mail_id', 'mail_id');
    }

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
        return $this->HasOne('App\EofficeApp\User\Entities\UserEntity', 'user_id', 'creator');
    }

    public function customerRecords()
    {
        return $this->HasMany('App\EofficeApp\Webmail\Entities\WebmailCustomerRecordLogEntity', 'mail_id', 'mail_id');
    }
}
