<?php
namespace App\EofficeApp\Flow\Repositories;

use App\EofficeApp\Flow\Entities\FlowFormControlSortEntity;
use App\EofficeApp\Base\BaseRepository;

/**
 * 流程表单内的控件序号表知识库
 *
 * @author 丁鹏
 *
 * @since  2015-10-16 创建
 */
class FlowFormControlSortRepository extends BaseRepository
{
    public function __construct(FlowFormControlSortEntity $entity) {
        parent::__construct($entity);
    }

    /**
     * 获取流程表单内的控件列表
     *
     * @method getFlowFormControlSortList
     *
     * @param  array                 $param [description]
     *
     * @return [type]                       [description]
     */
    function getFlowFormControlSortList($param = [])
    {
        $default = [
            'fields'     => ['*'],
            'page'       => 0,
            'limit'      => config('eoffice.pagesize'),
            'search'     => [],
            'order_by'   => ['control_sort_id'=>'asc'],
            'returntype' => 'array',
        ];
        $param = array_merge($default, array_filter($param));
        $query = $this->entity
                        ->select($param['fields'])
                        ->wheres($param['search'])
                        ->orders($param['order_by'])
                        ->with("flowFormControlBelongsToGroup");
        // 翻页判断，不翻页
        // $query = $query->parsePage($param['page'], $param['limit']);
        // 返回值类型判断
        if($param["returntype"] == "array") {
            return $query->get()->toArray();
        } else if($param["returntype"] == "count") {
            return $query->count();
        } else if($param["returntype"] == "object") {
            return $query->get();
        } else if($param["returntype"] == "first") {
            return $query->first();
        }
    }

}
