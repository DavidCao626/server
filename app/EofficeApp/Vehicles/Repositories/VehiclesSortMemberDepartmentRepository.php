<?php
namespace App\EofficeApp\Vehicles\Repositories;

use App\EofficeApp\Vehicles\Entities\VehiclesSortMemberDepartmentEntity;
use App\EofficeApp\Base\BaseRepository;

/**
 * 协作区分类表表知识库
 *
 * @author 丁鹏
 *
 * @since  2015-10-16 创建
 */
class VehiclesSortMemberDepartmentRepository extends BaseRepository
{
    public function __construct(VehiclesSortMemberDepartmentEntity $entity)
    {
        parent::__construct($entity);
    }
}
