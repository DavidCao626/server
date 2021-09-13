<?php
namespace App\EofficeApp\Flow\Repositories;

use App\EofficeApp\Flow\Entities\FlowReportEntity;
use App\EofficeApp\Base\BaseRepository;

/**
 * 流程报表表知识库
 *
 * @author 丁鹏
 *
 * @since  2015-10-16 创建
 */
class FlowReportRepository extends BaseRepository
{
    public function __construct(FlowReportEntity $entity) {
        parent::__construct($entity);
    }

}
