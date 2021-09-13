<?php 

namespace App\EofficeApp\PhotoAlbum\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * 相册类型Entity类:提供相册类型表实体
 *
 * @author qishaobo
 *
 * @since  2015-11-3 创建
 */
class PhotoAlbumTypeEntity extends BaseEntity
{

	/** @var string 相册类型表 */
	protected $table = 'photo_album_type';

	/** @var string 主键 */
	public $primaryKey = 'type_id';

    /**
     * 相册分类创建人和用户一对一
     * 
     * @return object
     *
     * @author qishaobo
     *
     * @since  2015-11-03
     */     
    public function creatorWithUser() 
    {
        return  $this->HasOne('App\EofficeApp\User\Entities\UserEntity','user_id','creator');
    }

    /**
     * 相册分类和相册一对多
     * 
     * @return object
     *
     * @author qishaobo
     *
     * @since  2015-11-04
     */     
    public function withPhotoAlbums() 
    {
        return  $this->HasMany('App\EofficeApp\PhotoAlbum\Entities\PhotoAlbumEntity','type_id','type_id');
    } 

    /**
     * 多个相册分类共享部门
     * 
     * @return object
     *
     * @author qishaobo
     *
     * @since  2015-11-19
     */     
    public function hasManyDept() 
    {
        return  $this->HasMany('App\EofficeApp\PhotoAlbum\Entities\PhotoAlbumTypeDepartmentEntity','type_id','type_id');
    }

    /**
     * 多个相册分类共享角色
     * 
     * @return object
     *
     * @author qishaobo
     *
     * @since  2015-11-19
     */     
    public function hasManyRole() 
    {
        return  $this->HasMany('App\EofficeApp\PhotoAlbum\Entities\PhotoAlbumTypeRoleEntity','type_id','type_id');
    }

    /**
     * 多个相册分类共享用户
     * 
     * @return object
     *
     * @author qishaobo
     *
     * @since  2015-11-19
     */     
    public function hasManyUser() 
    {
        return  $this->HasMany('App\EofficeApp\PhotoAlbum\Entities\PhotoAlbumTypeUserEntity','type_id','type_id');
    }

    /**
     * 多个相册分类管理员
     * 
     * @return object
     *
     * @author qishaobo
     *
     * @since  2015-11-19
     */     
    public function hasManyAdminUser() 
    {
        return  $this->HasMany('App\EofficeApp\PhotoAlbum\Entities\PhotoAlbumTypeAdminEntity','type_id','type_id');
    }    
}