<?php
namespace App\EofficeApp\Vehicles\Repositories;

use App\EofficeApp\Vehicles\Entities\VehiclesSortMemberRoleEntity;
use App\EofficeApp\Base\BaseRepository;

/**
 * 协作区分类表表知识库
 *
 * @author 丁鹏
 *
 * @since  2015-10-16 创建
 */
class VehiclesSortMemberRoleRepository extends BaseRepository
{
    public function __construct(VehiclesSortMemberRoleEntity $entity)
    {
        parent::__construct($entity);
    }
}
