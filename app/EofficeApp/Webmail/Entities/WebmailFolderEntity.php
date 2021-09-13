<?php

namespace App\EofficeApp\Webmail\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * 邮件文件夹Entity类:提供邮件文件夹实体。
 *
 * @author qishaobo
 *
 * @since  2016-07-28 创建
 */
class WebmailFolderEntity extends BaseEntity
{

    /** @var string 邮件文件夹表 */
	public $table = 'webmail_folder';

    /** @var string 主键 */
    public $primaryKey = 'folder_id';

    /**
     * 是否使用created_at和updated_at
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * 邮件文件夹创建人和用户信息一对一
     *
     * @return object
     *
     * @author qishaobo
     *
     * @since  2016-07-28
     */
    public function creatorName()
    {
        return  $this->HasOne('App\EofficeApp\User\Entities\UserEntity', 'user_id', 'folder_creator');
    }

    /**
     * 邮件文件夹创建人和邮件一对多
     *
     * @return object
     *
     * @author qishaobo
     *
     * @since  2016-07-28
     */
    public function manyMails()
    {
        return  $this->HasMany('App\EofficeApp\Webmail\Entities\WebmailMailEntity', 'folder', 'folder_id');
    }
}