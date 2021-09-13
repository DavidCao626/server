<?php 

namespace App\EofficeApp\PhotoAlbum\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * 。
 *
 * @author qishaobo
 *
 * @since  2015-11-19 创建
 */
class PhotoAlbumManageUserEntity extends BaseEntity
{

    public $table = 'photo_album_manage_user';

    /**
     * 一个用户详情
     * 
     * @return object
     *
     * @author qishaobo
     *
     * @since  2015-11-19
     */     
    public function hasOneUser() 
    {
        return  $this->HasOne('App\EofficeApp\User\Entities\UserEntity','user_id','user_id');
    }  
    public function hasManyManageUser()
    {
        return  $this->HasMany('App\EofficeApp\PhotoAlbum\Entities\PhotoAlbumManageUserEntity','photo_album_id','photo_album_id');
    }      
}