<?php
namespace App\EofficeApp\Flow\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Flow\Entities\FlowProcessControlOperationEntity;

/**
 * 流程分表知识库
 *
 * @author 丁鹏
 *
 * @since  2015-10-16 创建
 */
class FlowProcessControlOperationRepository extends BaseRepository
{
    public function __construct(FlowProcessControlOperationEntity $entity)
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
    public function getList($param)
    {
        $default = [
            'fields'   => ['*'],
            'page'     => 0,
            'limit'    => config('eoffice.pagesize'),
            'search'   => [],
            'order_by' => ['operation_id' => 'asc'],
        ];
        $param = array_merge($default, array_filter($param));
        $query = $this->entity
            ->select($param['fields'])
            ->wheres($param['search']);
        if (isset($param['searchEdit']) && $param['searchEdit']) {
            $query = $query->with(["controlOperationDetail" => function($query) {
                $query = $query->where(function ($query) {
                    $query = $query->where('operation_type','edit')->orWhere('operation_type','attachmentEdit');
                });
            }]);
            unset($param['searchEdit']);
        }else {
            $query = $query->with("controlOperationDetail");
        }

        $query = $query->orders($param['order_by']);
        return $query->get();
    }

    // 通过节点ID和控件ID检查这个控件是否有哪些字段权限，需要查询哪些字段权限在$searchOperationTypeParams传条件
    public function checkControlOperationTypeByNodeIdAndControlId($nodeId, $controlId, $searchOperationTypeParams = [])
    {
        return $this->entity->where('node_id', $nodeId)
                            ->where('control_id', $controlId)
                            ->whereHas('controlOperationDetail', function ($query) use($searchOperationTypeParams) {
                                if (!empty($searchOperationTypeParams)) {
                                    $query = $query->wheres($searchOperationTypeParams);
                                }
                            })->count();
    }

    function insertGetId($param = [])
    {
        $query = $this->entity->insertGetId($param);
        return $query;
    }

    /**
     * 获取有编辑权限的控件ID
     *
     * @method getList
     *
     * @param  [type]  $flowId [description]
     *
     * @return [type]          [description]
     */
    public function getHasEditControlId($param = [])
    {
        $query = $this->entity->whereHas('controlOperationDetail', function ($query) {
                        $query = $query->where('operation_type', 'edit');
                     });
        if (isset($param['search'])) {
            $query = $query->wheres($param['search']);
        }
        return $query->get();
    }
}
