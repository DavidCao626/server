<?php
namespace App\EofficeApp\Flow\Repositories;

use App\EofficeApp\Flow\Entities\FlowProcessFreeEntity;
use App\EofficeApp\Base\BaseRepository;
use DB;
/**
 * 定义流程自由节点表知识库
 *
 */
class FlowProcessFreeRepository extends BaseRepository
{
    public function __construct(FlowProcessFreeEntity $entity) {
        parent::__construct($entity);
    }
    /**
     * 获取流程节点详情
     *
     * @method getFlowNodeDetail
     *
     * @param  [type]            $nodeId [description]
     *
     * @return [type]                    [description]
     */
    function getFlowNodeDetail($nodeId)
    {
        $query = $this->entity;
        $query = $query->with(["flowProcessFreeHasManyPreset" => function ($query)
        {
            $query->orderBy('id');
        }])
        ->where('node_id',$nodeId);
        return $query->first();
    }
}
