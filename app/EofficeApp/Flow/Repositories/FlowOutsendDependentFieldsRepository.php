<?php
namespace App\EofficeApp\Flow\Repositories;

use App\EofficeApp\Flow\Entities\FlowOutsendDependentFieldsEntity;
use App\EofficeApp\Base\BaseRepository;

/**
 * 流程数据外发数据表知识库
 *
 * @author zyx
 *
 * @since  20200225
 */
class FlowOutsendDependentFieldsRepository extends BaseRepository
{
    public function __construct(FlowOutsendDependentFieldsEntity $entity)
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
                    ->where("flow_outsend_id", $id)
                    ->get();
    }
}
