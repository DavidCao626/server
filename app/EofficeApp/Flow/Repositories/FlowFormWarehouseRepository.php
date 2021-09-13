<?php
namespace App\EofficeApp\Flow\Repositories;

use App\EofficeApp\Flow\Entities\FlowFormWarehouseEntity;
use App\EofficeApp\Base\BaseRepository;

/**
 * 流程表单模板表知识库
 *
 * @author 丁鹏
 *
 * @since  2015-10-16 创建
 */
class FlowFormWarehouseRepository extends BaseRepository
{
    public function __construct(FlowFormWarehouseEntity $entity) {
        parent::__construct($entity);
    }

}
