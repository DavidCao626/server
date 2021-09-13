<?php
namespace App\EofficeApp\Vehicles\Repositories;

use App\EofficeApp\Vehicles\Entities\VehiclesSortMemberUserEntity;
use App\EofficeApp\Base\BaseRepository;

/**
 * 协作区分类表表知识库
 *
 * @author 丁鹏
 *
 * @since  2015-10-16 创建
 */
class VehiclesSortMemberUserRepository extends BaseRepository
{
    public function __construct(VehiclesSortMemberUserEntity $entity)
    {
        parent::__construct($entity);
    }
}
