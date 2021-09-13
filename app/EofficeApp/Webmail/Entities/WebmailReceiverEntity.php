<?php

namespace App\EofficeApp\Webmail\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * 邮件接收人Entity类:提供邮件接收人实体。
 *
 * @author qishaobo
 *
 * @since  2016-08-18 创建
 */
class WebmailReceiverEntity extends BaseEntity
{

    /** @var string 邮件接收人表 */
	public $table = 'webmail_receiver';

    /** @var string 主键 */
    public $primaryKey = 'receiver_id';

    /**
     * 是否使用created_at和updated_at
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * 邮件接收人和标签一对一
     *
     * @return object
     *
     * @author qishaobo
     *
     * @since  2016-08-18
     */
    public function hasOneTag()
    {
        return  $this->HasOne('App\EofficeApp\System\Tag\Entities\TagEntity', 'tag_id', 'tag_id');
    }
}