<?php
namespace App\EofficeApp\Flow\Repositories;

use App\EofficeApp\Flow\Entities\FlowWarehouseTypeEntity;
use App\EofficeApp\Base\BaseRepository;

/**
 * 流程表单模板分类表知识库
 *
 * @author 丁鹏
 *
 * @since  2015-10-16 创建
 */
class FlowWarehouseTypeRepository extends BaseRepository
{
    public function __construct(FlowWarehouseTypeEntity $entity) {
        parent::__construct($entity);
    }

}
