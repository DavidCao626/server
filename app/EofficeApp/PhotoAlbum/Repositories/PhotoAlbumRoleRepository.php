<?php 

namespace App\EofficeApp\PhotoAlbum\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\PhotoAlbum\Entities\PhotoAlbumRoleEntity;

/**
 * 相册共享角色Repository类:提供相册共享角色表操作资源
 *
 * @author qishaobo
 *
 * @since  2015-11-19 创建
 */
class PhotoAlbumRoleRepository extends BaseRepository
{
    public function __construct(PhotoAlbumRoleEntity $entity)
    {
        parent::__construct($entity);
    }   
    
    public function hasRolePurviewOfAlbum($roleId, $albumId)
    {
        return $this->entity->whereIn('role_id', $roleId)->where('photo_album_id', $albumId)->count() > 0;
    }
}