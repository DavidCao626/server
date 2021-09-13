<?php
namespace App\EofficeApp\Flow\Repositories;

use App\EofficeApp\Flow\Entities\FlowTermEntity;
use App\EofficeApp\Base\BaseRepository;

/**
 * 流程出口表知识库
 *
 * @author 丁鹏
 *
 * @since  2015-10-16 创建
 */
class FlowTermRepository extends BaseRepository
{
    public function __construct(FlowTermEntity $entity) {
        parent::__construct($entity);
    }

    /**
     * 获取出口条件详情
     *
     * @method getFlowNodeOutletDetail
     *
     * @param  [type]                  $termId [description]
     *
     * @return [type]                          [description]
     */
    function getFlowNodeOutletDetail($termId)
    {
        $query = $this->entity;
        return $query->find($termId);
    }

    /**
     * 获取出口条件列表
     *
     * @method getFlowDefineProcessList
     *
     * @param  [type]                   $param [description]
     *
     * @return [type]                          [description]
     */
    function getFlowNodeOutletList($param)
    {
        $flowId = $param["flow_id"];
        $default = [
            'fields'     => ['*'],
            'page'       => 0,
            'limit'      => config('eoffice.pagesize'),
            'search'     => [],
            'order_by'   => ['term_id'=>'asc'],
            'returntype' => 'array',
        ];
        $param = array_merge($default, array_filter($param));
        $query = $this->entity
                        ->orders($param['order_by'])
                        ->select("term_id","flow_id","source_id","target_id","condition")
                        ->wheres($param['search'])
                        ->where("flow_id",$flowId);
        // 返回值类型判断
        if($param["returntype"] == "array") {
            return $query->get()->toArray();
        } else if($param["returntype"] == "count") {
            return $query->count();
        } else if($param["returntype"] == "object") {
            return $query->get();
        }
    }

    /**
     * 获取出口条件列表
     *
     * @method getFlowDefineProcessList
     *
     * @param  [type]                   $param [description]
     *
     * @return [type]                          [description]
     */
    function getFlowNodeList($flowId)
    {
        $query = $this->entity
                        ->select("term_id","condition","flow_id","source_id","target_id")
                        ->whereIn('flow_id',[$flowId]);
        return $query->get()->toArray();
    }
    function getOneOutNode($param, $flowId) {
        if (!$flowId) {
            return ['code' => ['0x000003', 'common']];
        }
        return $this->entity->where('flow_id',$flowId)->where("source_id", '=', $param['source_id'])->where("target_id", '=', $param['target_id'])->get();
    }
    /**
     * 根据流程两个节点ID , 获取出口条件详情
     *
     */
    function getOneNodeCondition($sourceId, $targetId) {
        if (!$sourceId) {
            return ['code' => ['0x000003', 'common']];
        }
        return $this->entity->where('source_id',$sourceId)->where("target_id", $targetId)->get();
    }

}
