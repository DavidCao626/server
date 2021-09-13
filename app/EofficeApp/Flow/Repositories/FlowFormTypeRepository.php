<?php
namespace App\EofficeApp\Flow\Repositories;

use App\EofficeApp\Flow\Entities\FlowFormTypeEntity;
use App\EofficeApp\Base\BaseRepository;

/**
 * 流程表单表知识库
 *
 * @author 丁鹏
 *
 * @since  2015-10-16 创建
 */
class FlowFormTypeRepository extends BaseRepository
{
    public function __construct(FlowFormTypeEntity $entity) {
        parent::__construct($entity);
    }

    /**
     * 获取流程表单列表
     *
     * @method getFlowRunProcessList
     *
     * @param  array                 $param [description]
     *
     * @return [type]                       [description]
     */
    function getFlowForm($param = [])
    {
        $default = [
            'fields'     => ['form_id','form_name' , 'form_type' , 'form_sort' ,'field_counter'],
            'page'       => 0,
            'limit'      => config('eoffice.pagesize'),
            'search'     => [],
            'order_by'   => ['form_id'=>'desc'],
            'returntype' => 'array',
        ];
        $param = array_merge($default, array_filter($param));
        $query = $this->entity
                        ->select($param['fields'])
                        ->wheres($param['search'])
                        ->orders($param['order_by'])
                        ->with(['flowFormHasOneFlowFormSort'=>function($query) {
                            $query->select('title','id');
                        }])
                        ->with(['flowFormHasManyFlowType' => function($query) {
                            $query->select('form_id')
                                    ->selectRaw("count(*) as total")
                                    ->groupBy('form_id');
                        }])
                        ->with(['flowFormHasManyChildForm' => function($query) {
                            $query->select('form_id','form_name','parent_id');
                        }]);
        if(isset($param['form_sort']) && !empty($param['form_sort'])) {
            $query = $query->whereIn('form_sort',$param['form_sort']);
        }
        // 翻页判断
        $query = $query->parsePage($param['page'], $param['limit']);
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

    /**
     * 获取流程表单列表数量
     *
     * @method getFlowRunProcessList
     *
     * @param  array                 $param [description]
     *
     * @return [type]                       [description]
     */
    function getFlowFormTotal($param = [])
    {
        $param["page"]       = "0";
        $param["returntype"] = "count";
        return $this->getFlowForm($param);
    }
}
