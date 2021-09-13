<?php
namespace App\EofficeApp\Flow\Repositories;

use App\EofficeApp\Flow\Entities\FlowRequiredForFreeFlowEntity;
use App\EofficeApp\Base\BaseRepository;

/**
 * 自由流程必填
 */
class FlowRequiredForFreeFlowRepository extends BaseRepository
{
    public function __construct(FlowRequiredForFreeFlowEntity $entity)
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
    function getList($id)
    {
        return $this->entity
                    ->where("flow_id",$id)
                    ->get();
    }
}
