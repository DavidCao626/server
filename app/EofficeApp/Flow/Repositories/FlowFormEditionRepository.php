<?php
namespace App\EofficeApp\Flow\Repositories;

use App\EofficeApp\Flow\Entities\FlowFormEditionEntity;
use App\EofficeApp\Base\BaseRepository;

/**
 * 流程表单版本表知识库
 *
 * @author 丁鹏
 *
 * @since  2015-10-16 创建
 */
class FlowFormEditionRepository extends BaseRepository
{
    public function __construct(FlowFormEditionEntity $entity) {
        parent::__construct($entity);
    }

    /**
     * 获取流程表单版本表信息
     *
     * @method getFlowFormEdition
     *
     * @param  array                 $param [description]
     *
     * @return [type]                       [description]
     */
    function getFlowFormEdition($param = [])
    {
        $default = [
            // 默认查询除 PRINT_MODEL 以外的字段。
            'fields'     => ['id','form_id','form_name','edit_time','form_type'],
            'page'       => 0,
            'limit'      => config('eoffice.pagesize'),
            'search'     => [],
            'order_by'   => ['edit_time'=>'desc'],
            'returntype' => 'array',
        ];
        $param = array_merge($default, array_filter($param));
        $query = $this->entity
                        ->wheres($param['search'])
                        ->orders($param['order_by'])
                        ;
        // 解析原生 select
        if(isset($param['selectRaw'])) {
            foreach ($param['selectRaw'] as $key => $selectRaw) {
                $query = $query->selectRaw($selectRaw);
            }
        } else {
            $query = $query->select($param['fields']);
        }
        // 解析原生 where
        if(isset($param['whereRaw'])) {
            foreach ($param['whereRaw'] as $key => $whereRaw) {
                $query = $query->whereRaw($whereRaw);
            }
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
     * 获取流程表单版本表信息数量
     *
     * @method getFlowFormEditionTotal
     *
     * @param  array                 $param [description]
     *
     * @return [type]                       [description]
     */
    function getFlowFormEditionTotal($param = [])
    {
        $param["page"]       = "0";
        $param["returntype"] = "count";
        return $this->getFlowFormEdition($param);
    }

}
