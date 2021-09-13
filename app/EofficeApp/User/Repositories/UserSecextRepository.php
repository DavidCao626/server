<?php
namespace App\EofficeApp\User\Repositories;

use App\EofficeApp\User\Entities\UserSecextEntity;
use App\EofficeApp\Base\BaseRepository;

/**
 * 用户Systeminfo表知识库
 *
 * @author 丁鹏
 *
 * @since  2015-10-16 创建
 */
class UserSecextRepository extends BaseRepository
{
    public function __construct(UserSecextEntity $entity) 
    {
        parent::__construct($entity);
    }
    public function getOneDataByUserId($userId)
    {
        return $this->entity->where('user_id', $userId)->first();
    }
}
