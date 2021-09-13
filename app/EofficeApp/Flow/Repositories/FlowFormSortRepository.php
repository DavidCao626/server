<?php
namespace App\EofficeApp\Flow\Repositories;

use App\EofficeApp\Flow\Entities\FlowFormSortEntity;
use App\EofficeApp\Base\BaseRepository;

/**
 * 流程表单分类权限部门表
 *
 * @author 丁鹏
 *
 * @since  2015-10-16 创建
 */
class FlowFormSortRepository extends BaseRepository
{
    public function __construct(FlowFormSortEntity $entity)
    {
        parent::__construct($entity);
    }
    /**
     * 获取流程类别列表
     *
     * @method getFlowCopySortListRepository
     *
     * @param  array                         $param [description]
     *
     * @return [type]                               [description]
     */
    public function getFlowFormSortList($param = [])
    {
        $query = $this->entity->select('id', 'title','noorder');

        if (isset($param["title"])) {
            $query = $query->where("title", 'like', '%' . $param["title"] . '%');
        }
        if (isset($param["getDataType"]) && $param["getDataType"] == "grid") {
            $default = [
                'fields'     => ['*'],
                'page'       => 0,
                'limit'      => config('eoffice.pagesize'),
                'search'     => [],
                'order_by'   => ['noorder'=>'asc','id'=>'asc'],
                'returntype' => 'array',
            ];
            $param = array_merge($default, array_filter($param));
            $query = $query->with(['flowFormSortHasManyFlowForm' => function ($query) {
                                $query->select('form_id', 'form_name', 'form_sort');
                            }])->wheres($param["search"])->orders($param['order_by']);
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
        } else {
            $query = $query->with(['flowFormSortHasManyFlowForm' => function ($query) {
                 $query->select('form_id', 'form_name', 'form_sort');
            }]);
            if(isset($param["search"]) && !empty($param["search"])) {
                $query = $query->wheres($param["search"]);
            }
            $query = $query->orders(['noorder'=>'asc','id'=>'asc']);
            return $query->get();
        }
    }
    /**
     * 获取流程类别列表数量
     *
     */
    public function getFlowFormSortListTotal($param = [])
    {
        $query = $this->entity->select('id', 'title','noorder');

        if (isset($param["title"])) {
            $query = $query->where("title", 'like', '%' . $param["title"] . '%');
        }

        $default = [
            'fields'     => ['*'],
            'page'       => 0,
            'limit'      => config('eoffice.pagesize'),
            'search'     => [],
            'order_by'   => ['noorder'=>'asc','id'=>'asc'],
            'returntype' => 'array',
        ];
        $param = array_merge($default, array_filter($param));
        $query = $query->wheres($param["search"])
                        ->orders($param['order_by']);

        return $query->count();
    }
     /**
     * 获取单条表单分类数据
     *
     */
    public function getFlowFormSortDetail($sortId)
    {
        $query = $this->entity->select('id', 'title','noorder','priv_scope');

        $query = $query->where('id',$sortId)
        ->with(['flowFormSortHasManyMnamgeUser'=> function($query){
            $query->orderBy('id');
        }])
        ->with('flowFormSortHasManyMnamgeRole')
        ->with('flowFormSortHasManyMnamgeDeptarment');
        return $query->first();
    }
    /**
     * 获取表单类别最大序号
     *
     */
    public function getMaxFlowFormSort()
    {
        $query = $this->entity;

        return $query->max('noorder');
    }
    /**
     * 获取有权限的表单类别列表
     */
    function getPermissionFlowFormSortList($param)
    {
        $userId = isset($param["user_id"]) ? $param["user_id"]:"";
        $roleId = isset($param["role_id"]) ? $param["role_id"]:"";
        $deptId = isset($param["dept_id"]) ? $param["dept_id"]:"";
        $query = $this->entity;

        $query = $query->where('priv_scope', 1)
                        ->orWhereHas('flowFormSortHasManyMnamgeDeptarment', function ($query) use ($deptId) {
                            $query->wheres(['dept_id' => [explode(",", trim($deptId,",")), 'in']]);
                        })
                        ->orWhereHas('flowFormSortHasManyMnamgeRole', function ($query) use ($roleId) {
                            $query->wheres(['role_id' => [$roleId, 'in']]);
                        })
                        ->orWhereHas('flowFormSortHasManyMnamgeUser', function ($query) use ($userId) {
                            $query->wheres(['user_id' => [$userId]]);
                        })
                        ->orders(['noorder'=>'ASC','id'=>'ASC']);
        return $query->get();
    }
}
