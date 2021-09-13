<?php

namespace App\EofficeApp\OfficeSupplies\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Base\ModelTrait;
use App\EofficeApp\OfficeSupplies\Entities\OfficeSuppliesTypeEntity;

/**
 * office_supplies_type资源库
 *
 * @author  朱从玺
 *
 * @since   2015-11-03
 */
class OfficeSuppliesTypeRepository extends BaseRepository
{
    use ModelTrait;

    public function __construct(OfficeSuppliesTypeEntity $entity)
    {
        parent::__construct($entity);
    }

    /**
     * [getTypeList 获取类型列表]
     *
     * @return [object]              [查询结果]
     * @since  2015-11-05 创建
     *
     * @author 朱从玺
     *
     */
    public function getTypeList($params)
    {
        $defaultParams = ['search' => []];

        $params = array_merge($defaultParams, $params);

        $query = $this->entity->orderBy('type_sort', 'asc');
        if (!empty($params['search'])){
            if(isset($params['search']['type_name'])){
                // 模糊查询用
                $query->wheres(['type_name' => $params['search']['type_name']])
                    ->orWhereNull('parent_id');
            } elseif (isset($params['search']['id'])){
                // 下拉框用
                $query->wheres(['id' => $params['search']['id']])
                    ->orWhereNull('parent_id');
            } else {
                $query->wheres($params['search']);
            }
        }
//            ->orWhere(function($query){
//                $query->whereNull('parent_id');
//            })
        return $query->with([
            'typeHasManySuppliesCount' => function ($query) {
                $query->select('type_id')->selectRaw("count(*) as total")->groupBy('type_id');
            }
        ])
            ->get();
    }

    /**
     * 获取类型
     * @param $typeId
     */
    public function getTypeInfoByID($typeId)
    {
        $info = $this->entity->where("id", $typeId)
            ->with('typeHasManyPermission')
            ->first();
        if ($info && isset($info->id)) {
            return $info->toArray();
        }
        return [];

    }

    /**
     * 获取类型
     * @param $typeId
     */
    public function getTypeInfoByParentID($parent_id_arr)
    {
        $where = [
            'parent_id' => [$parent_id_arr, 'in']
        ];
        $info = $this->entity->wheres($where)->get();
        if (count($info) > 0) {
            return $info->toArray();
        }
        return [];
    }

    /**
     * 判断某id 的type是否存在
     * @param $id
     * @return bool
     */
    public function hasTypeById($id)
    {
        if (is_numeric($id)) {
            $result = $this->entity->where("id", $id)->get()->toArray();
            if (count($result) == 1) {
                return true;
            }
        }
        return false;
    }

    /**
     * 判断某parent_id 的type是否存在
     * @param $id
     * @return bool
     */
    public function hasTypeByParentId($parent_id)
    {
        if (is_numeric($parent_id)) {
            $result = $this->entity->where("parent_id", $parent_id)->get()->toArray();
            if (count($result) > 0) {
                return true;
            }
        }
        return false;
    }

    /**
     * [getOfficeSuppliesList 获取办公用品列表,简单左侧列表]
     *
     * @return [object]                [查询结果]
     * @since  2015-11-05 创建
     *
     * @author 朱从玺
     *
     */
    public function getOfficeSuppliesList($param = null)
    {
        $query = $this->entity->select('id', 'type_name', 'parent_id', 'type_sort')
            ->has('typeHasManySupplies')
            ->orderBy('parent_id', 'asc')
            ->orderBy('type_sort', 'asc')
            ->with([
                'typeHasManySupplies' => function ($query) use ($param) {
                    $query = $query->select('id', 'type_id', 'unit', 'usage','apply_controller', 'reference_price', 'office_supplies_no', 'office_supplies_name', 'stock_surplus', 'specifications');
                    if (isset($param['search']) && array_key_exists('office_supplies_name', $param['search'])) {
                        $query = $this->wheres($query, ['office_supplies_name' => $param['search']['office_supplies_name']]);
                    }
                    $query->with([
                        'suppliesHasManyStorage' => function ($query) {
                            $query->select('office_supplies_id')->selectRaw("count(*) as total")->groupBy('office_supplies_id');
                        }
                    ])
                        ->with([
                            'suppliesHasManyApply' => function ($query) {
                                $query->select('office_supplies_id')->selectRaw("count(*) as total")->groupBy('office_supplies_id');
                            }
                        ]);
                }
            ])
            ->with([
                'typeHasManySuppliesCount' => function ($query) {
                    $query->select(['type_id'])->selectRaw("count(*) as total")->groupBy('type_id');
                }
            ])
            ->with([
                'typeHasOneParent' => function ($query) {
                    $query->select('id', 'type_name');
                }
            ])
            ->get();
        return $query;
    }


}
