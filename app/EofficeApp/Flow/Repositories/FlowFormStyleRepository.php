<?php
namespace App\EofficeApp\Flow\Repositories;

use App\EofficeApp\Flow\Entities\FlowFormStyleEntity;
use App\EofficeApp\Base\BaseRepository;

/**
 * 流程表单样式表知识库
 *
 * @author 丁鹏
 *
 * @since  2015-10-16 创建
 */
class FlowFormStyleRepository extends BaseRepository
{
    public function __construct(FlowFormStyleEntity $entity) {
        parent::__construct($entity);
    }

}
