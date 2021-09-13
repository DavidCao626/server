<?php 

namespace App\EofficeApp\PhotoAlbum\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\PhotoAlbum\Entities\PhotoAlbumUserEntity;

/**
 * 相册共享用户Repository类:提供相册共享用户表操作资源
 *
 * @author qishaobo
 *
 * @since  2015-11-19 创建
 */
class PhotoAlbumUserRepository extends BaseRepository
{
    public function __construct(PhotoAlbumUserEntity $entity)
    {
        parent::__construct($entity);
    }   
    public function hasUserPurviewOfAlbum($userId, $albumId)
    {
        return $this->entity->where('user_id', $userId)->where('photo_album_id', $albumId)->count() > 0;
    }
}