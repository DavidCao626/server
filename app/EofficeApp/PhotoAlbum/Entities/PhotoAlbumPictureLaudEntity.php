<?php

namespace App\EofficeApp\PhotoAlbum\Entities;

use App\EofficeApp\Base\BaseEntity;

class PhotoAlbumPictureLaudEntity extends BaseEntity
{

	/** @var string 照片关注表 */
	protected $table = 'photo_album_picture_laud';
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

}