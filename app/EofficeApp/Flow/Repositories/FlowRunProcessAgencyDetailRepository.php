<?php
namespace App\EofficeApp\Flow\Repositories;
use DB;
use Schema;
use App\EofficeApp\Flow\Entities\FlowRunProcessAgencyDetailEntity;
use App\EofficeApp\Base\BaseRepository;

/**
 * 流程运行步骤表知识库
 *
 * @author lixuanxuan
 *
 * @since  2018-12-17 创建
 */
class FlowRunProcessAgencyDetailRepository extends BaseRepository
{
    public function __construct(FlowRunProcessAgencyDetailEntity $entity) {
        parent::__construct($entity);
    }

}
