<?php 

namespace App\EofficeApp\PhotoAlbum\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * 相册共享部门Entity类:提供相册共享部门实体。
 *
 * @author qishaobo
 *
 * @since  2015-11-19 创建
 */
class PhotoAlbumDepartmentEntity extends BaseEntity
{

    /** @var string 相册共享部门表 */
	public $table = 'photo_album_department';

    /**
     * 一个部门详情
     * 
     * @return object
     *
     * @author qishaobo
     *
     * @since  2015-11-19
     */     
    public function hasOneDept() 
    {
        return  $this->HasOne('App\EofficeApp\System\Department\Entities\DepartmentEntity','dept_id','dept_id');
    }  	
}