<?php 

namespace App\EofficeApp\PhotoAlbum\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\PhotoAlbum\Entities\PhotoAlbumManageUserEntity;

class PhotoAlbumManageUserRepository extends BaseRepository
{
    public function __construct(PhotoAlbumManageUserEntity $entity)
    {
        parent::__construct($entity);
    }   

    public function hasManagePur($userId,$photoAlbumId)
    {
    	return $this->entity->where('user_id',$userId)->where('photo_album_id',$photoAlbumId)->count();
    }
    public function getManageAlbumIdsByUserId($userId)
    {
    	$result = $this->entity->where('user_id',$userId)->get();

    	$albumId = [];

    	if(count($result)){
    		foreach($result as $album){
    			$albumId[] = $album->photo_album_id;
    		}
    	}

    	return $albumId;
    }
}