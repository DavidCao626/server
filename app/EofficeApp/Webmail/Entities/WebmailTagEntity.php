<?php

namespace App\EofficeApp\Webmail\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * 邮件标签Entity类:提供邮件实体。
 *
 * @since  2019-08-30 创建
 */
class WebmailTagEntity extends BaseEntity
{

    /** @var string 邮件标签表 */
    public $table = 'webmail_tag';

    /** @var string 主键 */
    public $primaryKey = 'tag_id';

    /**
     * 邮件标签创建人和用户信息一对一
     *
     * @return object
     *
     * @since  2019-08-30
     */
    public function creatorName()
    {
        return $this->HasOne('App\EofficeApp\User\Entities\UserEntity', 'user_id', 'tag_creator');
    }

    /**
     * 邮件标签和邮件一对多
     *
     * @return object
     *
     * @author qishaobo
     *
     * @since  2016-07-28
     */
    public function manyMails()
    {
        return  $this->HasMany('App\EofficeApp\Webmail\Entities\WebmailMailTagEntity', 'tag_id', 'tag_id');
    }
}
