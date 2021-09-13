<?php

namespace App\EofficeApp\System\Navbar\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\System\Navbar\Entities\NavbarEntity;


class NavbarRepository extends BaseRepository
{
    public function __construct(NavbarEntity $entity)
    {
        parent::__construct($entity);
    }

    
    public function getAllheaders($where = [])
    {
        return $this->entity->wheres($where)->get()->toArray();
    }

}