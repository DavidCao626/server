<?php
namespace App\EofficeApp\System\SystemMailbox\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * 系统邮箱表
 *
 * @author 丁鹏
 *
 * @since  2015-10-16 创建
 */
class WebmailEmailboxEntity extends BaseEntity
{
    /**
     * 系统邮箱表
     *
     * @var string
     */
    public $table = 'webmail_emailbox';

    /**
     * 主键
     *
     * @var string
     */
    public $primaryKey = 'emailbox_id';

    /**
     * 默认排序
     *
     * @var string
     */
    public $sort = 'desc';

    /**
     * 默认每页条数
     *
     * @var int
     */
    public $perPage = 10;

}
