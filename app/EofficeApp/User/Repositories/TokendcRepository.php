<?php
namespace App\EofficeApp\User\Repositories;

use App\EofficeApp\User\Entities\TokendcEntity;
use App\EofficeApp\Base\BaseRepository;

/**
 * tokendc表知识库
 *
 * @author 缪晨晨
 *
 * @since  2017-11-10 创建
 */
class TokendcRepository extends BaseRepository
{
    public function __construct(TokendcEntity $entity)
    {
        parent::__construct($entity);
    }
    public function getOneDataByTokenKey($tokenKey)
    {
        return $this->entity->where('token_key', $tokenKey)->first();
    }
}
