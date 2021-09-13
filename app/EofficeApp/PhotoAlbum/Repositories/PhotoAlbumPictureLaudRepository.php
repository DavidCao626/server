<?php

namespace App\EofficeApp\PhotoAlbum\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\PhotoAlbum\Entities\PhotoAlbumPictureLaudEntity;

class PhotoAlbumPictureLaudRepository extends BaseRepository
{
    public function __construct(PhotoAlbumPictureLaudEntity $entity)
    {
        parent::__construct($entity);
    }
    public function laudExists($userId,$pictureId)
    {
        return $this->entity->where('user_id',$userId)->where('picture_id',$pictureId)->count();
    }
    public function isLaud($userId,$pictureId)
    {
        return $this->entity->where('user_id',$userId)->where('picture_id',$pictureId)->where('type',1)->count();
    }
    public function getAllLaudCount($pictureId)
    {
        return $this->entity->where('picture_id',$pictureId)->where('type',1)->count();
    }
}