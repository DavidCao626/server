<?php
namespace App\EofficeApp\Flow\Repositories;

use App\EofficeApp\Flow\Entities\FlowReportFieldEntity;
use App\EofficeApp\Base\BaseRepository;

/**
 * 流程报表字段表知识库
 *
 * @author 丁鹏
 *
 * @since  2015-10-16 创建
 */
class FlowReportFieldRepository extends BaseRepository
{
    public function __construct(FlowReportFieldEntity $entity) {
        parent::__construct($entity);
    }

}
