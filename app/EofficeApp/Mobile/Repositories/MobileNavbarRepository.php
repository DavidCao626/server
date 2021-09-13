<?php

namespace App\EofficeApp\Mobile\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Mobile\Entities\MobileNavbarEntity;

class MobileNavbarRepository extends BaseRepository
{
   public function __construct(MobileNavbarEntity $entity)
    {
        parent::__construct($entity);
    }
    
    public function getNavbarChildren($parentId = 0, $all = false) {
        if($all) {
            return $this->entity->where('parent_id', $parentId)->orderBy('sort','asc')->get();
        }
        return $this->entity->where('parent_id', $parentId)->where('is_open', 1)->orderBy('sort','asc')->get();
    }
    
    public function navbarNameExists($navbarName, $parentId = 0, $navbarId = null) 
    {
        $query = $this->entity->where('navbar_name', $navbarName)->where('parent_id', $parentId);
        if($navbarId) {
            $query->where('navbar_id', '!=', $navbarId);
        }
        return $query->count();
    }
    public function navbarUrlExists($navbarUrl, $navbarId = null) 
    {
        $query = $this->entity->where('navbar_url', $navbarUrl);
        if($navbarId) {
            $query->where('navbar_id', '!=', $navbarId);
        }
        return $query->count();
    }
}
