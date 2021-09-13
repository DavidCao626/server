<?php
namespace App\EofficeApp\Flow\Repositories;

use App\EofficeApp\Flow\Entities\FlowOverTimeRemindEntity;
use App\EofficeApp\Base\BaseRepository;

/**
 * 流程报表表知识库
 *
 * @author 丁鹏
 *
 * @since  2015-10-16 创建
 */
class FlowOverTimeRemindRepository extends BaseRepository
{
    public function __construct(FlowOverTimeRemindEntity $entity) {
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
    function getList($id)
    {
        return $this->entity
                    ->where("node_id",$id)
                    ->orders(['id' => 'asc'])
                    ->get();
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
    function getFlowList($flow_id)
    {
        return $this->entity
                    ->where("flow_id",$flow_id)
                    ->orders(['id' => 'asc'])
                    ->get();
    }
}
