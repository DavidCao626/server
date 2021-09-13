<?php
namespace App\EofficeApp\User\Repositories;

use App\EofficeApp\User\Entities\UserSocketEntity;
use App\EofficeApp\Base\BaseRepository;

/**
 * 用户即时通讯已登录信息实体
 */
class UserSocketRepository extends BaseRepository
{
    public function __construct(UserSocketEntity $entity) 
    {
        parent::__construct($entity);
    }
}
