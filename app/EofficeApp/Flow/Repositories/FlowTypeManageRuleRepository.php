<?php
namespace App\EofficeApp\Flow\Repositories;

use App\EofficeApp\Flow\Entities\FlowTypeManageRuleEntity;
use App\EofficeApp\Base\BaseRepository;
use DB;
/**
 * 流程分表知识库 监控规则表
 *
 * @author 缪晨晨
 *
 * @since  2018-11-02 创建
 */
class FlowTypeManageRuleRepository extends BaseRepository
{
    public function __construct(FlowTypeManageRuleEntity $entity)
    {
        parent::__construct($entity);
    }

    /**
     * 获取数据
     *
     * @method getList
     *
     * @param  [type]  $flowId [description]
     *
     * @return [type]          [description]
     */
    function getList($flowId)
    {
        return $this->entity
                    ->where("flow_id",$flowId)
                    ->orderBy("rule_id", "asc")
                    ->get();
    }

}
