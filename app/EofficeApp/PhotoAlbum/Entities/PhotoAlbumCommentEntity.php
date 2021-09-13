<?php

namespace App\EofficeApp\PhotoAlbum\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * 相册评论Entity类:提供相册评论表实体
 *
 * @author qishaobo
 *
 * @since  2015-11-3 创建
 */
class PhotoAlbumCommentEntity extends BaseEntity
{

	/** @var string 相册评论表 */
	protected $table = 'photo_album_comment';

	/** @var string 主键 */
	public $primaryKey = 'comment_id';

    /** @var bool 表明模型是否应该被打上时间戳 */
    public $timestamps = false;

    /**
     * 评论人和用户一对一
     *
     * @return object
     *
     * @author qishaobo
     *
     * @since  2015-11-04
     */
    public function creatorWithUser()
    {
        return  $this->HasOne('App\EofficeApp\User\Entities\UserEntity','user_id','user_id');
    }

    /**
     * 评论和相册一对一
     *
     * @return object
     *
     * @author qishaobo
     *
     * @since  2016-05-19
     */
    public function commentHasOneComment()
    {
        return  $this->HasOne('App\EofficeApp\PhotoAlbum\Entities\PhotoAlbumCommentEntity', 'comment_id', 'comment_id');
    }
}