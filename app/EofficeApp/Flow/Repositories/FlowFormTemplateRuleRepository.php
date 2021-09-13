<?php
namespace App\EofficeApp\Flow\Repositories;

use App\EofficeApp\Flow\Entities\FlowFormTemplateRuleEntity;
use App\EofficeApp\Base\BaseRepository;

/**
 * 流程表单控件结构表
 *
 * @author 丁鹏
 *
 * @since  2015-10-16 创建
 */
class FlowFormTemplateRuleRepository extends BaseRepository
{
    public function __construct(FlowFormTemplateRuleEntity $entity) {
        parent::__construct($entity);
    }

    /**
     * 获取基本信息
     *
     * @method FlowFormTemplateRuleRepository
     *
     * @param  [type]                             $param [description]
     *
     * @return [type]                                    [description]
     */
    function getFlowFormTemplateRule($param = [])
    {
        $default = [
            'fields'     => ['*'],
            'page'       => 0,
            'limit'      => config('eoffice.pagesize'),
            'search'     => [],
            'order_by'   => ['other_rule_flag'=>'asc','rule_order'=>'asc',],
            'returntype' => 'array',
        ];
        $param = array_merge($default, array_filter($param));
        $query = $this->entity
                        ->wheres($param['search'])
                        ->orders($param['order_by'])
                        ->with(["ruleListHasManyUser" => function($query) {
                            $query->orderBy("auto_id","asc");
                            $query->select("rule_id","user_id");
                        }])
                        ->with(["ruleListHasManyRole" => function($query) {
                            $query->orderBy("auto_id","asc");
                            $query->select("rule_id","role_id");
                        }])
                        ->with(["ruleListHasManyDept" => function($query) {
                            $query->orderBy("auto_id","asc");
                            $query->select("rule_id","dept_id");
                        }])
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
}
