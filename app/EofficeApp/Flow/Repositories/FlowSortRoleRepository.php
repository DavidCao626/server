<?php
namespace App\EofficeApp\Flow\Repositories;

use App\EofficeApp\Flow\Entities\FlowSortRoleEntity;
use App\EofficeApp\Base\BaseRepository;

/**
 * 流程表单分类权限部门表
 *
 * @author 丁鹏
 *
 * @since  2015-10-16 创建
 */
class FlowSortRoleRepository extends BaseRepository
{
    public function __construct(FlowSortRoleEntity $entity)
    {
        parent::__construct($entity);
    }

}
