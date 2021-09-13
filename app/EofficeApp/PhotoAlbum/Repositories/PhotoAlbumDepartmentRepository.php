<?php 

namespace App\EofficeApp\PhotoAlbum\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\PhotoAlbum\Entities\PhotoAlbumDepartmentEntity;

/**
 * 相册共享部门Repository类:提供相册共享部门表操作资源
 *
 * @author qishaobo
 *
 * @since  2015-11-19 创建
 */
class PhotoAlbumDepartmentRepository extends BaseRepository
{
    public function __construct(PhotoAlbumDepartmentEntity $entity)
    {
        parent::__construct($entity);
    }	
    public function hasDeptPurviewOfAlbum($deptId, $albumId)
    {
        return $this->entity->where('dept_id', $deptId)->where('photo_album_id', $albumId)->count() > 0;
    }
}