<?php
namespace App\EofficeApp\Flow\Repositories;

use App\EofficeApp\Flow\Entities\FlowChildFormTypeEntity;
use App\EofficeApp\Base\BaseRepository;

/**
 *
 */
class FlowChildFormTypeRepository extends BaseRepository
{
    public function __construct(FlowChildFormTypeEntity $entity) {
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
            'fields'     => ['*'],
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
                        ->orders($param['order_by']);
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

    function insertGetId($param = [])
    {
        $query = $this->entity->insertGetId($param);
        return $query;
    }
}
