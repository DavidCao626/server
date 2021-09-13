<?php 

namespace App\EofficeApp\PhotoAlbum\Entities;

use App\EofficeApp\Base\BaseEntity;

class PhotoAlbumVisitorEntity extends BaseEntity
{

    public $table = 'photo_album_visitor';
    public $timestamps      = false;
 
    public function creatorWithUser() 
    {
        return  $this->HasOne('App\EofficeApp\User\Entities\UserEntity','user_id','user_id');
    }
}