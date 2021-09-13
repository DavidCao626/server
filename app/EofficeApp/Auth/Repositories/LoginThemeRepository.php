<?php
namespace App\EofficeApp\Auth\Repositories;

use App\EofficeApp\Auth\Entities\LoginThemeEntity;
use App\EofficeApp\Base\BaseRepository;

class LoginThemeRepository extends BaseRepository
{
    public function __construct(LoginThemeEntity $entity)
    {
        parent::__construct($entity);
    }
}