<?php
namespace App\EofficeApp\Flow\Repositories;

use App\EofficeApp\Flow\Entities\FlowFormTemplateRuleRoleEntity;
use App\EofficeApp\Base\BaseRepository;

/**
 * 流程分表知识库
 *
 * @author 丁鹏
 *
 * @since  2015-10-16 创建
 */
class FlowFormTemplateRuleRoleRepository extends BaseRepository
{
    public function __construct(FlowFormTemplateRuleRoleEntity $entity)
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
                    ->get();
    }
}
