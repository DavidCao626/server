<?php 

namespace App\EofficeApp\PhotoAlbum\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * 相册共享角色Entity类:提供相册共享角色实体。
 *
 * @author qishaobo
 *
 * @since  2015-11-19 创建
 */
class PhotoAlbumRoleEntity extends BaseEntity
{

    /** @var string 相册共享角色表 */
    public $table = 'photo_album_role';

    /**
     * 一个部门详情
     * 
     * @return object
     *
     * @author qishaobo
     *
     * @since  2015-11-19
     */     
    public function hasOneRole() 
    {
        return  $this->HasOne('App\EofficeApp\Role\Entities\RoleEntity','role_id','role_id');
    }      
}