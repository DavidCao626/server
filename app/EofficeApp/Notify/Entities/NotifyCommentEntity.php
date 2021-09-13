<?php


namespace App\EofficeApp\Notify\Entities;


use App\EofficeApp\Base\BaseEntity;

/**
 * 公告评论Entity类:提供公告评论实体
 *
 * @author nitianhua
 *
 * @since  2018-11-26 创建
 */
class NotifyCommentEntity extends BaseEntity
{
    /**
     * 公告评论数据表
     *
     * @var string
     */
    protected $table = 'notify_comments';

    /**
     * 主键
     *
     * @var string
     */
    public $primaryKey = 'comment_id';
    /**
     * 关联引用回复
     *
     * @return object
     */
    public function revertHasOneBlockquote()
    {
        return $this->hasOne('App\EofficeApp\Notify\Entities\NotifyCommentEntity','comment_id','blockquote');
    }
    /**
     * 回复对应人员
     *
     * @method revertHasOneUser
     *
     * @return boolean    [description]
     */
    public function revertHasOneUser()
    {
        return  $this->hasOne('App\EofficeApp\User\Entities\UserEntity','user_id','user_id');
    }
}
