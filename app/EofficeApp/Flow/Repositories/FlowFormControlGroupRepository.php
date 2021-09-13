<?php
namespace App\EofficeApp\Flow\Repositories;

use App\EofficeApp\Flow\Entities\FlowFormControlGroupEntity;
use App\EofficeApp\Base\BaseRepository;

/**
 * 流程控件的“控件分组”表知识库
 *
 * @author 丁鹏
 *
 * @since  2015-10-16 创建
 */
class FlowFormControlGroupRepository extends BaseRepository
{
    public function __construct(FlowFormControlGroupEntity $entity) {
        parent::__construct($entity);
    }

    /**
     * 获取流程表单列表
     *
     * @method getFlowFormControlGroupList
     *
     * @param  array                 $param [description]
     *
     * @return [type]                       [description]
     */
    function getFlowFormControlGroupList($param = [])
    {
        $default = [
            'fields'     => ['*'],
            'page'       => 0,
            'limit'      => config('eoffice.pagesize'),
            'search'     => [],
            'order_by'   => ['group_sort_id'=>'asc'],
            'returntype' => 'array',
        ];
        $param = array_merge($default, array_filter($param));
        $query = $this->entity
                        ->select($param['fields'])
                        ->wheres($param['search'])
                        ->orders($param['order_by'])
                        ->with(['flowFormControlGroupHasManyFormControl' => function($query) {
                            $query->orderBy('control_sort_id','asc');
                        }]);
        // 翻页判断
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
