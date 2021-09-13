<?php 

namespace App\EofficeApp\PhotoAlbum\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * 相册共享用户Entity类:提供相册共享用户实体。
 *
 * @author qishaobo
 *
 * @since  2015-11-19 创建
 */
class PhotoAlbumUserEntity extends BaseEntity
{

    /** @var string 相册共享用户表 */
    public $table = 'photo_album_user';

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
}