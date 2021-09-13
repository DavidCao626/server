<?php
namespace App\EofficeApp\Flow\Repositories;

use App\EofficeApp\Flow\Entities\FlowOthersEntity;
use App\EofficeApp\Base\BaseRepository;

/**
 * 流程其他设表知识库
 *
 * @author 丁鹏
 *
 * @since  2015-10-16 创建
 */
class FlowOthersRepository extends BaseRepository
{
    public function __construct(FlowOthersEntity $entity) {
        parent::__construct($entity);
    }

    /**
     * 根据flow_id获取流程其他设置的信息
     *
     * @method getFlowOthersInfo
     *
     * @param  [type]            $flowId [description]
     *
     * @return [type]                    [description]
     */
    function getFlowOthersInfo($flowId , $fields=['*'])
    {
        return $this->entity
                    ->where("flow_id",$flowId)
                    ->select($fields)
                    ->get();
    }
    /**
     * 获取某个流程的flow_others
     * @param  [type] $flowId [description]
     * @return [type] $sortId [description]
     */
    function findFlowOthers($flowId,$field="*")
    {
        $query = $this->entity
                    ->select($field)
                    ->where('flow_id',$flowId)->first();
        $query = $query ? $query->toArray() : [];
        if($field == "*" || is_array($field)) {
            return $query;
        } else if(is_string($field)) {
            return isset($query[$field]) ? $query[$field] : "";
        }
    }
}
