<?php

namespace App\EofficeApp\PhotoAlbum\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\PhotoAlbum\Entities\PhotoAlbumLaudEntity;

class PhotoAlbumLaudRepository extends BaseRepository
{
    public function __construct(PhotoAlbumLaudEntity $entity)
    {
        parent::__construct($entity);
    }

    public function laudExists($userId,$photoAlbumId)
    {
        return $this->entity->where('user_id',$userId)->where('photo_album_id',$photoAlbumId)->count();
    }
    public function isLaud($userId,$photoAlbumId)
    {
        return $this->entity->where('user_id',$userId)->where('photo_album_id',$photoAlbumId)->where('type',1)->count();
    }
    public function getAllLaudCount($photoAlbumId)
    {
        return $this->entity->where('photo_album_id',$photoAlbumId)->where('type',1)->count();
    }
}