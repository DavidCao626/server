<?php

namespace App\EofficeApp\User\Repositories;

use App\EofficeApp\User\Entities\UserSuperiorEntity;
use App\EofficeApp\Base\BaseRepository;

class UserSuperiorRepository extends BaseRepository
{
    public function __construct(UserSuperiorEntity $entity) 
    {
        parent::__construct($entity);
    }

    
    public function getSuperiorUsers($userId)
    {
    	if(is_array($userId)) {
    		 return $this->entity->whereIn('superior_user_id', $userId)->get()->toArray();
    	} 
        return $this->entity->where('superior_user_id', $userId)->get()->toArray();
    }
}
