<?php
namespace App\EofficeApp\Charge\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Charge\Entities\ChargeTypeEntity;

/**
 * 费用类别资源库
 *
 * @author:喻威
 *
 * @since：2015-10-19
 *
 */
class ChargeTypeRepository extends BaseRepository {

    public function __construct(ChargeTypeEntity $entity) {
        parent::__construct($entity);
    }

    /**
     *
     * 获取所有类别
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-20k
     */
    public function chargeTypeList($param=[]){
        $default = ['search' => []];
        $params = array_merge($default, $param);
        $fields = isset($param['fields']) ? $param['fields'] : ["charge_type_id","charge_type_name","charge_type_parent"];

        if (isset($param['filter_type'])) {
            return $this->entity->select($fields)->wheres($params['search'])
                                                ->orderBy('charge_type_order','asc')
                                                ->orderBy('charge_type_id','asc')
                                                ->get()->toArray();
        }

        if(isset($param['is_order'])){
            $fields[] = "charge_type_order";
            return $this->entity->select($fields)->wheres($params['search'])
                                                ->orderBy('charge_type_order','asc')
                                                ->orderBy('charge_type_id','asc')
                                                ->get()->toArray();
        }

        if(isset($param['is_charge_list'])){
            $fields[] = "charge_type_order";
            return $this->entity->select($fields)->wheres($params['search'])
                                                ->orderBy('charge_type_parent','asc')
                                                ->orderBy('charge_type_order','asc')
                                                ->orderBy('charge_type_id','asc')
                                                ->get()->toArray();
        }

        return $this->entity->select($fields)->wheres($params['search'])
                                            ->orderBy('charge_type_parent','asc')
                                            ->get()->toArray();
    }
    // 返回完整的科目名称
    public function chargeTypeFullList(){
        $childArray = $this->entity->select("charge_type_id","charge_type_name","charge_type_parent")
                                ->where('charge_type_parent','!=',0)
                                ->orderBy('charge_type_parent','asc')
                                ->orderBy('charge_type_order','asc')
                                ->get()->toArray();

        $parentArray = $this->entity->select("charge_type_id","charge_type_name","charge_type_parent")
                                ->where('charge_type_parent', 0)
                                ->orderBy('charge_type_order','asc')
                                ->get()->toArray();

        $data = [];

        if(!empty($parentArray)){
            foreach ($parentArray as $key => $value) {
                foreach ($childArray as $k => $v) {
                    if($v['charge_type_parent'] == $value['charge_type_id']){
                        $v["charge_type_name"] = $value["charge_type_name"].' - '.$v["charge_type_name"];
                        $data[] = $v;
                    }
                }
            }
        }

        return $data;
    }

    public function getChargeTypeListTotal($param){

        return $this->entity->select($param['fields'])->wheres($param['search'])->count();
    }

    public function getChargeTypeList($param){
        $default = [
            'fields' => ['*'],
            'page' => 0,
            'limit' => config('eoffice.pagesize'),
            'search' => [],
            'order_by' => ['charge_type_id' => 'asc'],
        ];

        $param = array_merge($default, array_filter($param));

        return $this->entity->select($param['fields'])->wheres($param['search'])->get();
    }


    public function getDataBywhere($where, $isOrder=false){
        if($isOrder){
            return $this->entity->wheres($where)->orderBy('charge_type_order','asc')->orderBy('charge_type_id','asc')->get()->toArray();
        }
        return $this->entity->wheres($where)->get()->toArray();

    }

    // 获取费用类型的详细信息
    public function getChargeTypeInfo($typeId, $param = []) {
        $query = $this->entity;

        if (isset($param['fields'])) {
            $query = $query->select($param['fields']);
        }

        if (isset($param['search'])) {
            $query = $query->wheres($param['search']);
        }

        return $query->where('charge_type_id', $typeId)->first();
    }

    public function getOneByTypeName($name){
        return $this->entity->where("charge_type_name",$name)->where("charge_type_parent",0)->first();
    }
    public function getOneByName($name){
        return $this->entity->where("charge_type_name",$name)->where("charge_type_parent", '!=',0)->first();
    }
    public function getChargeTypeId($name,$parentId = 0)
    {
        $type = $this->entity->select(['charge_type_id'])->where("charge_type_name",$name)->where("charge_type_parent",$parentId)->first();

        return $type->charge_type_id;
    }
    public function chargeTypeMobile($data){
         $default = [
            'fields' => ['*'],
            'page' => 0,
            'limit' => config('eoffice.pagesize'),
            'search' => [],
            'order_by' => ['charge_type_id' => 'asc'],
        ];



        $param = array_merge($default, array_filter($data));


        $query = $this->entity->where('charge_type_parent','>',0);
        return $query->select($param['fields'])->wheres($param['search'])
                        ->orders($param['order_by'])
                        ->parsePage($param['page'], $param['limit'])
                        ->get();
    }

    public function chargeTypeMobileTotal($data){
         $default = [
            'search' => []
        ];

        $param = array_merge($default, array_filter($data));

        $query = $this->entity->where('charge_type_parent','>',0);
        return $query->wheres($param['search'])->count();
    }

    public function getChargeTypeNameById($typeId) {
        if ($type = $this->entity->find($typeId)) {
            return $type->charge_type_name;
        }
        return '';
    }
    public function isUniqueNameByParent($name, $parentId) {
        return $this->entity->where("charge_type_name",$name)->where("charge_type_parent", $parentId)->first();
    }

    public function getChargeTypeChildren($parentId, $param=[]) {
        $fields = $param['fields'] ?? ["*"];

        $query = $this->entity->select($fields)->where('charge_type_parent', $parentId);

        if (isset($param['search'])) {
            $query->wheres($param['search']);
        }

        return $query->orderBy('charge_type_order', 'asc')->get();
    }

    public function getBottomChildren($parentId) {
        return $this->entity->whereRaw('find_in_set(\''.intval($parentId).'\',type_level)')
                            ->where('has_children', 0)
                            ->get();
    }

    public function getBottomChildrenCount($parentId) {
        return $this->entity->whereRaw('find_in_set(\''.intval($parentId).'\',type_level)')
                            ->where('has_children', 0)
                            ->count();
    }

    public function deleteChargeType($typeId) {
        return $this->entity->whereRaw('find_in_set(\'' .intval($typeId) . '\',type_level)')
                            ->orWhere('charge_type_id', $typeId)
                            ->delete();
    }

    public function getChargeTypeGroupByParent() {
        return $this->entity->where('has_children', 0)->groupBy('charge_type_parent')->get();
    }

    public function getMaxLevel() {
        return $this->entity->max('level');
    }
}
