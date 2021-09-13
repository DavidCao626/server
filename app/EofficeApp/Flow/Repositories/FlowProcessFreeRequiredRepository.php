<?php
namespace App\EofficeApp\Flow\Repositories;

use App\EofficeApp\Flow\Entities\FlowProcessFreeRequiredEntity;
use App\EofficeApp\Base\BaseRepository;
use DB;
/**
 * 定义流程自由节点表知识库
 *
 */
class FlowProcessFreeRequiredRepository extends BaseRepository
{
    public function __construct(FlowProcessFreeRequiredEntity $entity) {
        parent::__construct($entity);
    }
}