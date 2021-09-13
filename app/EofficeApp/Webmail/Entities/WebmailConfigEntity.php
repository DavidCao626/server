<?php

namespace App\EofficeApp\Webmail\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * 邮件设置Entity类:提供邮件设置实体。
 *
 * @since  2016-07-28 创建
 */
class WebmailConfigEntity extends BaseEntity
{

    /** @var string 邮件设置表 */
    public $table = 'webmail_config';

    /** @var string 主键 */
    public $primaryKey = 'config_id';
    /**
     * 设置创建人和用户信息一对一
     *
     * @return object
     *
     * @since  2016-07-28
     */
    public function creatorName()
    {
        return $this->HasOne('App\EofficeApp\User\Entities\UserEntity', 'user_id', 'config_creator');
    }
}
