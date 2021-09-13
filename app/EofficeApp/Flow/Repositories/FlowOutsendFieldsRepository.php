<?php
namespace App\EofficeApp\Flow\Repositories;

use App\EofficeApp\Flow\Entities\FlowOutsendFieldsEntity;
use App\EofficeApp\Base\BaseRepository;

/**
 * 流程数据外发数据表知识库
 *
 * @author 史瑶
 *
 * @since  2016-01-11 创建
 */
class FlowOutsendFieldsRepository extends BaseRepository
{
    public function __construct(FlowOutsendFieldsEntity $entity)
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
                    ->orderBy('id', 'asc')
                    ->get();
    }
}
