<?php

namespace App\EofficeApp\Customer\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * 联系记录评论Entity类:提供联系记录评论实体。
 *
 * @author qishaobo
 *
 * @since  2016-03-25 创建
 */
class ContactRecordCommentEntity extends BaseEntity
{
    /** @var string 客户联系记录表 */
	public $table = 'customer_contact_record_comment';

    /** @var string 主键 */
    public $primaryKey = 'comment_id';

    /**
     * 评论人和用户信息一对一
     *
     * @return object
     *
     * @author qishaobo
     *
     * @since  2016-4-11
     */
    public function commentHasOneUser()
    {
        return  $this->HasOne('App\EofficeApp\User\Entities\UserEntity','user_id','comment_creator');
    }

    /**
     * 评论和父级评论一对一
     *
     * @return object
     *
     * @author qishaobo
     *
     * @since  2016-4-11
     */
    public function commentHasOneParent()
    {
        return  $this->HasOne('App\EofficeApp\Customer\Entities\ContactRecordCommentEntity','parent_id','comment_id');
    }

}