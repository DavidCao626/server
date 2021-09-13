<?php

namespace App\EofficeApp\Charge\Services;

use App\EofficeApp\Base\BaseService;
use DB;
use Exception;
use Cache;
use App\EofficeApp\Project\NewServices\ProjectService;
use Illuminate\Support\Arr;
use App\EofficeApp\LogCenter\Facades\LogCenter;
/**
 * 费用服务
 *
 * @author: 喻威

 * * @since：2015-10-19
 */
class ChargeService extends BaseService
{

    private $chargeTypeRepository;
    private $chargeSettingRepository;
    private $chargeListRepository;
    private $userRoleRepository;
    private $userSystemInfoRepository;
    private $userRepository;
    private $departmentRepository; //部门资源库对象
    private $departmentService;
    private $companyRepository;
    private $langService;
    private $projectManagerRepository;
    private $projectService;
    private $chargePermissionRepository;
    private $userService;
    private $roleService;

    public function __construct()
    {
        $this->chargeTypeRepository           = 'App\EofficeApp\Charge\Repositories\ChargeTypeRepository';
        $this->chargeSettingRepository        = 'App\EofficeApp\Charge\Repositories\ChargeSettingRepository';
        $this->chargeListRepository           = 'App\EofficeApp\Charge\Repositories\ChargeListRepository';
        $this->userRoleRepository             = 'App\EofficeApp\Role\Repositories\UserRoleRepository';
        $this->userSystemInfoRepository       = 'App\EofficeApp\User\Repositories\UserSystemInfoRepository';
        $this->userRepository                 = 'App\EofficeApp\User\Repositories\UserRepository';
        $this->departmentRepository           = 'App\EofficeApp\System\Department\Repositories\DepartmentRepository';
        $this->departmentService              = 'App\EofficeApp\System\Department\Services\DepartmentService';
        $this->companyRepository              = 'App\EofficeApp\System\Company\Repositories\CompanyRepository';
        $this->langService                    = 'App\EofficeApp\Lang\Services\LangService';
        $this->projectManagerRepository       = 'App\EofficeApp\Project\Repositories\ProjectManagerRepository';
        $this->projectService                 = 'App\EofficeApp\Project\Services\ProjectService';
        $this->chargePermissionRepository     = 'App\EofficeApp\Charge\Repositories\ChargePermissionRepository';
        $this->userService                    = 'App\EofficeApp\User\Services\UserService';
        $this->roleService                    = 'App\EofficeApp\Role\Services\RoleService';
    }

    public function addChargeType($data)
    {
        if (!isset($data['sub_names'])) {
            return true;
        }
        $temt = json_decode($data['sub_names'], true);
        //增加
        $chargeData = [
            "charge_type_name"   => $data['parent_name'],
            "charge_type_parent" => 0,
            "charge_type_order"  => $data['charge_type_order'],
        ];
        $parentCharge = app($this->chargeTypeRepository)->getOneByTypeName($data['parent_name']);
        if (!empty($parentCharge)) {
            return ['code' => ['0x020017', 'charge']];
        }

        $resultData = app($this->chargeTypeRepository)->insertData($chargeData);
        $parentId   = $resultData->charge_type_id;

        foreach ($temt as $v) {
            if (trim($v['charge_type_name']) != '') {
                $childCharge = app($this->chargeTypeRepository)->isUniqueNameByParent($v['charge_type_name'], $parentId);
                if (!empty($childCharge)) {
                    return ['code' => ['0x020017', 'charge']];
                }
                $chargeData = [
                    "charge_type_name"   => $v['charge_type_name'],
                    "charge_type_parent" => $parentId,
                    "charge_type_order"  => isset($v['charge_type_order']) ? $v['charge_type_order'] : 0,
                ];
                app($this->chargeTypeRepository)->insertData($chargeData);
            }
        }

        return $resultData;
    }

    /**
     * 某个费用类别的详细
     *
     */
    public function getChargeTypeById($chargeTypeId, $data)
    {

        $chargeTypeData = app($this->chargeTypeRepository)->getDetail($chargeTypeId);

        if ($chargeTypeData["charge_type_parent"] == 0) {

            $where = [
                "charge_type_parent" => [$chargeTypeData['charge_type_id'], "="],
            ];
            $item = app($this->chargeTypeRepository)->getDataBywhere($where, true);

            $params = [];
            foreach ($item as $v) {
                $dataParam["charge_type_id"]    = $v["charge_type_id"];
                $dataParam["charge_type_name"]  = $v["charge_type_name"];
                $dataParam['charge_type_order'] = $v["charge_type_order"];

                array_push($params, $dataParam);
            }

            $chargeTypeData['item'] = json_encode($params);
        }

        return $chargeTypeData;
    }

    /**
     * 费用类型展示
     *
     * @return array
     */
    public function chargeTypeList($param)
    {
        $param = $this->parseParams($param);

        return app($this->chargeTypeRepository)->getChargeTypeList($param);
    }
    // 所有科目
    public function chargeTypeLists($params)
    {
        $params         = $this->parseParams($params);
        $chargeTypeTemp = app($this->chargeTypeRepository)->chargeTypeList($params);
        return $chargeTypeTemp;
    }
    // 费用类型搜索，返回完整的科目路径
    public function chargeTypeSearch($param) {
        $params = $this->parseParams($param);
//        if(isset($params['search']) && isset($params['search']['charge_type_name'])){
//            $params['search']['has_children'] = [0];
//        }
        $types = app($this->chargeTypeRepository)->getChargeTypeList($params);

        $allTypes = $this->chargeTypeList([]);
        if (!$allTypes->isEmpty()) {
            $allTypesArray = array_column($allTypes->toArray(), 'charge_type_name', 'charge_type_id');
            foreach ($types as &$value) {
                $value->charge_type_name = $this->getChargeTypeLevelName($value, $allTypesArray);
                $value->type_level_name = $value->charge_type_name;
            }
        }
        return $types;
    }

    private function getChargeTypeLevelName($type, $allTypes) {
        $typeName = '';
        if (isset($type->type_level) && isset($type->charge_type_name)) {
            $typeLevel = explode(',', $type->type_level);
            foreach ($typeLevel as $v) {
                if (isset($allTypes[$v])) {
                    $typeName = $typeName . $allTypes[$v] . '/';
                }
            }
        }
        return $typeName == '' ? $type->charge_type_name : $typeName . $type->charge_type_name;
    }

    /**
     * 费用科目设置
     *
     * @return array
     */
    public function chargeSubjectList($params)
    {
        $list = [];
        $chargeType = app($this->chargeTypeRepository)->chargeTypeList(['is_order' => 1]);
        foreach ($chargeType as $k => $v) {
            $parentId = $v['charge_type_parent'];
            $typeId = $v['charge_type_id'];

            if ($parentId == 0) {
                if (!isset($list[$typeId])) {
                    $list[$typeId] = $v;
                    $list[$typeId]["items"] = [];
                } else {
                    $list[$typeId] = array_merge($list[$typeId], $v);
                }
            } else {
                if (!isset($list[$parentId])) {
                    $list[$parentId] = [];
                    $list[$parentId]["items"] = [];
                }
                $v['charge_subject_value'] = 0;

                $list[$parentId]["items"][] = $v;
            }
        }
        return array_merge([], $list);
    }

    /**
     * 费用设置
     *
     * @param array $data
     *
     * @return bool
     *
     * @todo 同一角色 同一部门获取的用户
     *
     */
    public function chargeSet($data)
    {
        if ($data['alert_method'] == "custom" && $data['set_type'] != 5) {
            if (!($data['alert_data_start'] && $data['alert_data_end'])) {
                return ['code' => ['0x020001', 'charge']];
            }
            if ($data['alert_data_end'] < $data['alert_data_start']) {
                return ['code' => ['0x020020', 'charge']];
            }
        } else {
            $data['alert_data_start'] = $data['alert_data_end'] = "";
        }

        $type = '';
        if ($data['set_type'] == 2) {
            // 设置对象为部门
            $data['user_id'] = "";
            $type = 'dept';
        } elseif ($data['set_type'] == 1) {
            // 设置对象为公司
            $data['dept_id'] = 0;
            $type = 'company';
        } elseif ($data['set_type'] == 5) {
            // 设置对象为项目
            $data['dept_id'] = 0;
            $type = 'project';
        } else {
            // 其他设置对象的方式，本质为设置用户
            $userCommon = [];
            if ($data['set_type'] == 3) { 
                if ($data["user_id"]) {
                    if (is_array($data['user_id'])) {
                        $userCommon = $data['user_id'];
                    } else {
                        if ($data['user_id'] == 'all') {
                            $userCommon = app($this->userRepository)->getAllUserIdString(['return_type' => 'array']);
                        } else {
                            $userCommon = [$data['user_id']];
                        }
                    }
                }
            } else {
                $data['set_type'] = 3;
                if ($data["role_id"]) {
                    $userCommon = app($this->userRepository)->getAllUserIdString([
                        'search' => [
                            'role_id' => [$data["role_id"]]
                        ],
                        'return_type' => 'array'
                    ]);
                }
            } 
            $data["users"] = $userCommon;
            $data['dept_id'] = isset($data["dept_id"]) ? $data["dept_id"] : 0;
            $type = 'user';
        }

        if ($data['subject_check'] == 1) {
            if (!isset($data['flowout'])) {
                if (isset($data['subject_values']) && !empty($data['subject_values'])) {
                    $temp = [];
                    foreach ($data['subject_values'] as $value) {
                        if (!empty($value)) {
                            foreach ($value as $v) {
                                $temp[] = [
                                    "type_id"    => $v['charge_type_id'],
                                    'type_value' => $v['type_value']
                                ];
                            }
                        }
                    }
                    $data['subject_values'] = json_encode($temp);
                } else {
                    $data['subject_values'] = '';
                }
            }
        } else {
            $data['subject_values'] = '';
        }

        $result = $this->setCharge($data, $type);

        return $result;
    }

    public function setCharge($data, $type)
    {
        if ($type == 'user') {
            $result = [];
            foreach ($data["users"] as $user) {
                if ($user) {
                    try {
                        $where = ["user_id" => [$user]];
                        $data["user_id"] = $user;
                        if (isset($data['dept_id'])) {
                            unset($data['dept_id']);
                        }
                        $tempResult = $this->setChargeRealize($data, $where);
                        if (isset($tempResult['code'])) {
                            return $tempResult;
                        }
                        $result[] = $tempResult;
                    } catch (Exception $ex) {
                        return ['code' => ['0x020008', 'charge']];
                    }
                }
            }
            return $result;
        } else if ($type == "dept") {
            $where = [
                "dept_id" => [$data['dept_id']],
                "user_id" => [""],
            ];
            if (isset($data['user_id'])) {
                unset($data['user_id']);
            }
            return $this->setChargeRealize($data, $where);
        } else if ($type == "company") {
            $where        = ["set_type" => [1]];
            $data         = $this->unsetUnuseData($data);
            return $this->setChargeRealize($data, $where);
        } else if ($type == "project") {
            $where = ["set_type" => [5], 'project_id' => [$data['project_id']]];
            if (isset($data['alert_method'])) {
                $data['alert_method'] = '';
            }
            $data         = $this->unsetUnuseData($data);
            return $this->setChargeRealize($data, $where);
        }

        return true;
    }
    private function unsetUnuseData($data)
    {
        if (isset($data['user_id'])) {
            unset($data['user_id']);
        }
        if (isset($data['dept_id'])) {
            unset($data['dept_id']);
        }
        return $data;
    }

    //分离出设置费用的实现方法
    public function setChargeRealize($data, $where)
    {
        $chargeData = array_intersect_key($data, array_flip(app($this->chargeSettingRepository)->getTableColumns()));
        if (isset($chargeData['charge_setting_id'])) {
            unset($chargeData['charge_setting_id']);
        }
        $result = true;
        // 如果当前设置的预警方式是周期，要先判断之前的预警方式是不是周期
        if (isset($data['charge_setting_id'])) {
            // 编辑
            $setInfo = app($this->chargeSettingRepository)->getSettingByWhere(['charge_setting_id' => [$data['charge_setting_id']]], false);
            if (empty($setInfo) || !isset($setInfo->alert_method)) {
                return ['code' => ['0x020008', 'charge']];
            }
            if ($setInfo->alert_method == 'custom') {
                if ($data['alert_method'] == 'custom') {
                    // 如果之前设置的是周期，现在的也是周期，要判断是否有冲突
                    $userResult = app($this->chargeSettingRepository)->getSettingByWhere($where, true, $data['alert_data_start'], $data['alert_data_end']); // 判断是否有周期冲突
                    if (!empty($userResult)) {
                        foreach ($userResult as $key => $value) {
                            if ($value->charge_setting_id != $data['charge_setting_id']) {
                                // 编辑的预警周期与该预警对象的另一条预警周期冲突
                                return ['code' => ['0x020019', 'charge']];
                            }
                        }
                    }
                    $result = app($this->chargeSettingRepository)->updateData($chargeData, [
                        'charge_setting_id' => $data['charge_setting_id']
                    ]);
                } else {
                    // 如果之前是周期，当前保存的不是周期，要删除以前的所有周期预警，插入当前设置
                    app($this->chargeSettingRepository)->deleteByWhere($where);
                    $chargeData['updated_at'] = date('Y-m-d H:i:s');
                    $result = app($this->chargeSettingRepository)->insertData($chargeData);
                }
            } else {
                // 如果以前设置的不是周期，直接更新
                $result = app($this->chargeSettingRepository)->updateData($chargeData, ['charge_setting_id' => $data['charge_setting_id']]);
            }
        } else {
            // 新建
            $setInfo = app($this->chargeSettingRepository)->getSettingByWhere($where, false);

            if ($data['alert_method'] == 'custom') {
                // 如果新建的是周期，判断是否有周期冲突
                $userResult = app($this->chargeSettingRepository)->getSettingByWhere($where, true, $data['alert_data_start'], $data['alert_data_end']);
                if (!$userResult->isEmpty()) {
                    // 新建的预警周期与该预警对象的另一条预警周期冲突
                    return ['code' => ['0x020019', 'charge']];
                }

                if (empty($setInfo)) {
                    $result = app($this->chargeSettingRepository)->insertData($chargeData);
                } else {
                    if ($setInfo->alert_method == 'custom') {
                        // 如果之前的是周期，插入
                        $result = app($this->chargeSettingRepository)->insertData($chargeData);
                    } else {
                        // 如果之前不是周期，直接更新
                        $result = app($this->chargeSettingRepository)->updateData($chargeData, ['charge_setting_id' => $setInfo->charge_setting_id]);
                    }
                }
            } else {
                if (empty($setInfo)) {
                    $result = app($this->chargeSettingRepository)->insertData($chargeData);
                } else {
                    if ($setInfo->alert_method == 'custom') {
                        // 如果之前的是周期，删除之前的插入
                        app($this->chargeSettingRepository)->deleteByWhere($where);

                        $result = app($this->chargeSettingRepository)->insertData($chargeData);
                    } else {
                        // 如果之前不是周期，直接更新
                        $result = app($this->chargeSettingRepository)->updateData($chargeData, ['charge_setting_id' => $setInfo->charge_setting_id]);
                    }
                }
            }
        }

        if (!$result) {
            return [];
            // return ['code' => ['0x020008', 'charge']];
        }
        return isset($result['charge_setting_id'])
            ? ['charge_setting_id' => $result['charge_setting_id']] 
            : (isset($data['charge_setting_id']) ? ['charge_setting_id' => $data['charge_setting_id']] : true);
    }

    public function getChargeTypeListByParentId($pid, $params)
    {
        $params = $this->parseParams($params);

        $children = app($this->chargeTypeRepository)->getChargeTypeChildren($pid, $params);

        $result = [];

        if (isset($params['full_path'])) {
            $allTypes = $this->chargeTypeList([]);
            $allTypesArray = array_column($allTypes->toArray(), 'charge_type_name', 'charge_type_id');
        }

        if (!$children->isEmpty()) {
            foreach ($children as $key => $value) {
                if (isset($params['has_charge'])) {
                    //判断该分类及子分类是否有录入费用
                    $listData = app($this->chargeListRepository)->hasChargeRecord($value->charge_type_id);
                    $value->has_charge = 0;
                    if (!$listData->isEmpty()) {
                        $value->has_charge = 1;
                    }
                }

                // 获取完整路径
                if (isset($params['full_path'])) {
                    $value->type_level_name = empty($allTypesArray) ? $value->charge_type_name : $this->getChargeTypeLevelName($value, $allTypesArray);
                }

                if (isset($params['with_charge'])) {
                    if ($value->has_children == 1) {
                        $where = [ 
                            "charge_type_parent" => [$value->charge_type_id] 
                        ];
                        $value['charge_cost'] = (float) app($this->chargeListRepository)->getChargeCostByWhere($data, $where);
                        
                        $value['items'] = $this->getChildrenTree($data, $value->charge_type_id, $total);
                    } else {
                        $where = [
                            "charge_list.charge_type_id" => [$value->charge_type_id],
                        ];
                        $value["charge_cost"] = (float) app($this->chargeListRepository)->getChargeCostByWhere($data, $where);
                    }
                }

                $result[] = $value;
            }
        }

        return $result;
    }

    /**
     * 录入费用
     *
     * @param array $data
     *
     * @return int
     *
     * @author 喻威
     *
     * @since 2015-10-20
     */
    public function addNewCharge($data)
    {
        if (!isset($data['charge_undertaker']) || empty($data['charge_undertaker'])) {
            return ['code' => ['0x020023', 'charge']];
        }

        if (!isset($data["charge_type_id"]) || empty($data["charge_type_id"])) {
            return ['code' => ['0x020022', 'charge']];
        }

        if (!isset($data["reason"]) || empty($data["reason"])) {
            return ['code' => ['0x020013', 'charge']];
        }

        if (!isset($data["user_id"]) || empty($data["user_id"])) {
            return ['code' => ['0x020012', 'charge']];
        }

        if (!isset($data['undertake_user']) || empty($data['undertake_user'])) {
            $data['undertake_user'] = $data["user_id"];
        }

        if (!isset($data["undertake_dept"]) && $data['charge_undertaker'] == 2) {
            return ['code' => ['0x020039', 'charge']];
        }

        if (!isset($data["payment_date"]) || empty($data["payment_date"])) {
            $data["payment_date"] = date('Y-m-d');
        }

        $chargeData = array_intersect_key($data, array_flip(app($this->chargeListRepository)->getTableColumns()));

        $result = app($this->chargeListRepository)->insertData($chargeData);

        return $result;
    }

    //多条提交
    public function addMutiCharge($data, $own)
    {
        foreach ($data as $val) {
            if (!isset($val["reason"])) {
                return ['code' => ['0x020013', 'charge']];
            }
            $val["charge_cost"] = isset($val["charge_cost"]) ? $val["charge_cost"] : 0;
            $status             = $val["charge_type_id"] && $val["charge_undertaker"] && $val["payment_date"] && isset($val["reason"]) && $val["reason"] && $val["user_id"];
            if ($status) {
                if (!isset($val["creator"])) {
                    $val["creator"] = isset($data[0]["creator"]) ? $data[0]["creator"] : $own['user_id'];
                }
                $this->addNewCharge($val);
            }
        }

        return true;
    }

    /**
     * 编辑费用类型
     *
     * @param array $data 费用类型
     *
     * @return bool
     *
     * @author 喻威
     *
     * @since 2015-10-20
     */
    public function editChargeType($data)
    {
        try {
            // 上级科目
            if (!isset($data['id'])) {
                return ['code' => ['0x020041', 'charge']];
            }
            if (!isset($data['name']) || empty($data['name'])) {
                return ['code' => ['0x020041', 'charge']];
            }
            $parentId = $data['id'];
            if ($parentId != 0) {
                // 更新父级，判断是否重名
                $chargeParent = app($this->chargeTypeRepository)->getDetail($parentId);
                if (empty($chargeParent)) {
                    return ['code' => ['0x020004', 'charge']]; 
                }
                $tempParent = app($this->chargeTypeRepository)->isUniqueNameByParent($data['name'], $chargeParent->charge_type_parent);
                if (empty($tempParent)) {
                    app($this->chargeTypeRepository)->updateData(['charge_type_name' => $data['name']], ['charge_type_id' => $data['id']]); 
                } else {
                    if ($tempParent->charge_type_id != $data['id']) {
                        return ['code' => ['0x020017', 'charge']];
                    }
                }
            }
            if (!isset($data['sub_types'])) {
                return true;
            }
            //获取当前父ID为$parentId的二级科目
            $preIds  = app($this->chargeTypeRepository)->getDataBywhere(['charge_type_parent' => [$parentId]], true);
            $preIds = array_column($preIds, 'charge_type_id');

            if (!empty($data['sub_types'])) {
                $typeNames = array_column($data['sub_types'], 'charge_type_name');
                // 同一级科目不能重复
                if(count($typeNames) != count(array_unique($typeNames))){
                    return ['code' => ['0x020017', 'charge']];
                }
            
                foreach ($data['sub_types'] as $key => $value) {
                    if (!empty($value['charge_type_id'])) {
                        $updateData = [
                            "charge_type_name"  => $value['charge_type_name'],
                            "charge_type_order" => $value['charge_type_order'],
                        ];
                        app($this->chargeTypeRepository)->updateData($updateData, ['charge_type_id' => $value['charge_type_id']]);
                    } else {
                        //插入
                        $chargeData = [
                            "charge_type_name"   => $value['charge_type_name'],
                            "charge_type_parent" => $parentId,
                            "type_level"         => $parentId == 0 ? 0 : $chargeParent->type_level . ',' . $parentId,
                            "has_children"       => 0,
                            "charge_type_order"  => $value['charge_type_order'],
                            "level"              => $parentId == 0 ? 1 : $chargeParent->level + 1
                        ];

                        app($this->chargeTypeRepository)->insertData($chargeData);

                        // 如果该分类没有子分类，则更新has_children字段
                        if ($parentId != 0 && $chargeParent->has_children == 0) {
                            app($this->chargeTypeRepository)->updateData(['has_children' => 1], ['charge_type_id' => $parentId]);
                        }

                    }
                }
            }
            
            $countSubList = count($preIds);
            if (count($preIds) > 0) {
                //取两个ID的差，删除
                $deleteIds = !empty($data['sub_types']) ? array_diff($preIds, array_filter(array_column($data['sub_types'], 'charge_type_id'))) : $preIds;
                //判断该分类及子分类是否有录入费用
                if (!empty($deleteIds)) {
                    foreach ($deleteIds as $typeId) {
                        $listData = app($this->chargeListRepository)->hasChargeRecord($typeId);
                        if (!$listData->isEmpty()) {
                            return ['code' => ['0x020007', 'charge']];
                        }
                    }

                    foreach ($deleteIds as $typeId) {
                        // 更新预警设置并删除分类
                        if ( !$this->updateSetAndDeleteType($typeId) ) {
                            return ['code' => ['0x020003', 'charge']];
                        }
                    }
                }
            }

            $typeChildren = app($this->chargeTypeRepository)->getChargeTypeChildren($parentId);
            if ($typeChildren->isEmpty() && $chargeParent->has_children == 1 && $parentId != 0) {
                // 如果子分类删除完了，更新has_children字段
                app($this->chargeTypeRepository)->updateData(['has_children' => 0], ['charge_type_id' => $parentId]);
            }

            return true;
        } catch (Exception $exc) {
            return $exc->getTraceAsString();
        }
    }

    public function getChargeSetByUserId($user_id)
    {

        //已知用户，根据用户调取对应的预警值 及部门预警值
        //获取当前用户所在的部门 并获取部门
        $userAll = app($this->userRepository)->getUserAllData($user_id);

        $userInfos            = $userAll->toArray();
        $set_name             = $userInfos["user_name"];
        $dept_id              = $userInfos['user_has_one_system_info']['dept_id'];
        $chargeAlertValueDept = app($this->chargeSettingRepository)->getDataByWhere(["dept_id" => [$dept_id], "user_id" => [""]]);
        $chargeAlertValueUser = app($this->chargeSettingRepository)->getDataByWhere(["user_id" => [$user_id]]);
        $dept_type            = $dept_values            = $self_type            = $self_values            = $companyType            = $companyValue            = "";

        if (count($chargeAlertValueDept) >= 1) {

            $tempCharge  = $this->chargeConfig($chargeAlertValueDept[0]);
            $dept_type   = $tempCharge["type"];
            $dept_values = $tempCharge["values"];
        }

        if (count($chargeAlertValueUser) >= 1) {

            $tempCharge  = $this->chargeConfig($chargeAlertValueUser[0]);
            $self_type   = $tempCharge["type"];
            $self_values = $tempCharge["values"];
        }
        // 获取公司预警值
        $company = app($this->chargeSettingRepository)->getDataByWhere(["set_type" => [1]]);

        if (count($company) >= 1) {
            $tempCharge   = $this->chargeConfig($company[0]);
            $companyType  = $tempCharge["type"];
            $companyValue = $tempCharge["values"];
        }

        $chargeAlertValue = [
            "dept_type"     => $dept_type,
            "dept_values"   => $dept_values,
            "self_type"     => $self_type,
            "self_values"   => $self_values,
            "set_name"      => $set_name,
            "company_type"  => $companyType,
            "company_value" => $companyValue,
        ];

        return $chargeAlertValue;
    }

    /**
     * 费用清单
     *
     * @param array $data
     * @param array $own
     *
     * @return array
     * 
     * @author nxk
     *
     * @since 2019-08-13
     */
    public function chargeListData($data, $own) {
        // 获取参数
        $filter  = $data['filter'] ?? "year";
        $year    = $data['year'] ?? '';
        $month   = $data['month'] ?? "";
        $day     = $data['day'] ?? "";
        $quarter = $data['quarter'] ?? "";
        $flag    = $data['flag'] ?? "list";
        $setId   = isset($data['set_id']) && !empty($data['set_id']) ? json_decode($data['set_id'], true) : "0";
        $setType = $data['set_type'] ?? 0;
        $hasDepts = $data['has_depts'] ?? 0;
        $filterType = isset($data['filter_type']) && !empty($data['filter_type']) ? $data['filter_type'] : 1;
        // 费用权限
        $viewUser = $this->getViewUser($own);
        $queryUser = $this->getQueryUser($setType, $setId, $hasDepts);
        // 科目预警
        $subjectCheck = 0;
        $subjectValues = [];
        // 获取预警值
        $chargeAlertValue = array_merge($this->getChargeAlert($setId, $setType, $subjectCheck, $subjectValues), [
            "year"          => $year,
            "month"         => $month,
        ]);
        // 处理费用查询参数
        $dataFinal = [
            'filter' => $filter,
            'year'   => $year,
            'month'  => $month,
            'flag'   => $flag,
            'power'  => isset($data['power']) ? $data['power'] : ($setType == 0 ? 1 : 0),
            'filter_type' => $filterType,
            'set_type' => $setType,
            'set_id' => $setId,
            'has_depts' => $hasDepts,
            'viewUser' => $viewUser,
            'users' => $queryUser
        ];

        $projectIds = ProjectService::thirdMineProjectId($own);
        $dataFinal['projects'] = $projectIds;

        if ($setType == 5) {
            if (empty($setId)) {
                $projectIds = ProjectService::thirdMineProjectId($own);
                $dataFinal['project_id'] = ['in', $projectIds];
            } else {
                $dataFinal['project_id'] = ['=', $setId];
            }
            $dataFinal['power'] = 1;
        }
        // 一二级科目及其费用
        $detailTypeList = [];
        $parentType = app($this->chargeTypeRepository)->chargeTypeList([
            'is_order' => 1,
            'search'   => ['charge_type_parent' => [0]]
        ]);
        $maxLevel = app($this->chargeTypeRepository)->getMaxLevel();
        
        $detailStatis = [];
        switch ($filter) {
            case 'year':
                for ($i = 1; $i <= 12; $i++) {
                    $detailStatis[$i] = 0;
                }

                break;
            case 'quarter':
                for ($i = 1; $i <= 4; $i++) {
                    $detailStatis[$i] = 0;
                }

                break;
            case 'month':
                $year  = $data['year'];
                $month = $data['month'];
                $count = date("t", strtotime("$year-$month"));

                for ($i = 1; $i <= $count; $i++) {
                    $detailStatis[$i] = 0;
                }
                break;
        }

        $detailStatisCount = 0;
        $monthCombile = [];
        $result = [
            'type_list' => [],
            'charge_list' => [],
            'charge_total' => []
        ];
        $temp = [];
        $tempTotal = [];
        $subjectValues = !empty($subjectValues) ? array_column($subjectValues, 'type_value', 'type_id') : [];
        // 获取费用数据
        $chargeDetail = $this->getChildrenDetail(0, $maxLevel, $dataFinal, $setType, $setId, $queryUser, $subjectCheck, $subjectValues, $detailStatis, $detailStatisCount, $monthCombile, $result, $temp, $tempTotal);

        $monthCombile = array_unique($monthCombile);
        $monthTemp = date("t", strtotime("$year-$month"));
        $monthArr  = [];
        for ($i = 1; $i <= $monthTemp; $i++) {
            $monthArr[] = $i;
        }

        return [
            'monthCount'   => $monthArr, //该月天数，1,2,3,4,......
            "set_list"     => $chargeAlertValue, //预警详情
            "detail_list"  => $chargeDetail, //二级科目及每月费用
            "detailStatis" => $detailStatis, //每月合计
            "detailCount"  => $detailStatisCount, //总计
            "monthCombile" => $monthCombile, //有费用录入的月份
            "max_level"    => $maxLevel
        ];
    }

    private function getChargeAlert($setId, $setType, &$subjectCheck, &$subjectValues) {
        if ($setType != 1 && is_array($setId) && !empty($setId)) {
            if (count($setId) == 1) {
                $setId = $setId[0];
            } else {
                return [];
            }
        }
        // 预警值
        $deptType     = trans('charge.not_set');
        $deptValues   = trans('charge.not_set');
        $selfType     = trans('charge.not_set');
        $selfValues   = trans('charge.not_set');
        $companyType  = trans('charge.not_set');
        $companyValue = trans('charge.not_set');
        $projectValue = trans('charge.not_set'); 
        $alertMethod  = trans('charge.not_set');
        $setName      = '';

        $where = [
            'alert_data_start' => [date('Y-m-d'), '<='],
            'alert_data_end'   => [date('Y-m-d'), '>='],
        ];
        // 获取费用预警
        if ($setType == 1 || (($setType == 2 || $setType == 3) && empty($setId))) {
            // 公司
            $tempDept     = app($this->companyRepository)->getCompanyDetail();
            $setName      = $tempDept->company_name; //"所有部门";
            $companySet   = app($this->chargeSettingRepository)->getSettingByWhere(["set_type" => [1]], false);
            $subjectCheck = $companySet->subject_check ?? 0;
            $alertMethod  = $companySet->alert_method ?? trans('charge.not_set');
            $subjectValues = $companySet && $companySet->subject_values ? json_decode($companySet->subject_values, true) : [];
            
            // 公司预警
            $tempWhere = [ 'set_type' => [1] ];
            $tempAlert = $this->getAlertTypeAndValue($where, $tempWhere);
            $companyType  = $tempAlert["type"];
            $companyValue = $tempAlert["values"];
        } elseif ($setType == 2) {
            // 部门
            $deptAll = app($this->departmentRepository)->getDetail($setId);
            $setName = $deptAll->dept_name;
            $chargeAlertDept = app($this->chargeSettingRepository)->getSettingByWhere(["dept_id" => [$setId], "set_type" => [2]], false);
            $subjectCheck = $chargeAlertDept->subject_check ?? 0;
            $alertMethod  = $chargeAlertDept->alert_method ?? trans('charge.not_set');
            $subjectValues = $chargeAlertDept && $chargeAlertDept->subject_values ? json_decode($chargeAlertDept->subject_values, true) : [];

            // 部门预警值
            $tempWhere = [
                'dept_id' => [$setId],
                'user_id' => [''],
            ];
            $tempAlert = $this->getAlertTypeAndValue($where, $tempWhere);
            $deptType  = $tempAlert["type"];
            $deptValues = $tempAlert["values"];
        } elseif ($setType == 3) {
            // 用户
            $chargeAlertUser = app($this->chargeSettingRepository)->getSettingByWhere(["user_id" => [$setId], "set_type" => [3]], false);

            $subjectCheck = $chargeAlertUser->subject_check ?? 0;
            $alertMethod  = $chargeAlertUser->alert_method ?? trans('charge.not_set');
            $subjectValues = $chargeAlertUser && $chargeAlertUser->subject_values ? json_decode($chargeAlertUser->subject_values, true) : [];

            // 公司预警
            $tempWhere = [ 'set_type' => [1] ];
            $tempAlert = $this->getAlertTypeAndValue($where, $tempWhere);
            $companyType  = $tempAlert["type"];
            $companyValue = $tempAlert["values"];
            //获取当前用户所在的部门
            $setName = app($this->userService)->getUserName($setId);
            $deptId = app($this->userService)->getUserDeptIdAndRoleIdByUserId($setId)['dept_id'];
            // 部门预警
            $tempWhereDept = [
                'dept_id' => [$deptId],
                'user_id' => [''],
            ];
            $tempAlertDept = $this->getAlertTypeAndValue($where, $tempWhereDept);
            $deptType  = $tempAlertDept["type"];
            $deptValues = $tempAlertDept["values"];
            // 用户预警
            $tempWhereUser = [ 'user_id' => [$setId] ];
            $tempAlertUser = $this->getAlertTypeAndValue($where, $tempWhereUser);
            $selfType = $tempAlertUser["type"];
            $selfValues = $tempAlertUser["values"];
        } elseif ($setType == 5) {
            $chargeAlertValue = !empty($setId) ? app($this->chargeSettingRepository)->getSettingByWhere(["project_id" => [$setId]], false) : [];
            $subjectCheck     = $chargeAlertValue->subject_check ?? 0;
            $projectValue     = $chargeAlertValue->alert_value ?? trans('charge.not_set');
            $subjectValues    = $chargeAlertValue && $chargeAlertValue->subject_values ? json_decode($chargeAlertValue->subject_values, true) : [];
        }

        return [
            "dept_type"     => $deptType,
            "dept_values"   => $deptValues,
            "self_type"     => $selfType,
            "self_values"   => $selfValues,
            "company_type"  => $companyType,
            "company_value" => $companyValue,
            "project_value" => $projectValue,
            "check"         => $subjectCheck,
            "alert_method"  => $alertMethod,
            "type"          => $setType,
            "set_name"      => $setName
        ];
    }

    private function getQueryUser($setType, $setId, $hasDepts) {
        $userStr = [];
        if (empty($setId)) {
            return [];
        }
        if ($setType == 1) {
            $userStr = [];
        } elseif ($setType == 2) {
            $deptIds = $setId;
            if ($hasDepts == 1) {
                //获取当前部门下所有部门
                $deptIds = $setId;
                if (!empty($setId)) {
                    foreach ($setId as $deptId) {
                        $deptIds = array_merge($deptIds, app($this->departmentService)->getTreeIds($setId));
                    }
                }
            }
            
            //获取所有可查看的用户
            $users = app($this->userSystemInfoRepository)->getInfoByWhere([
                "dept_id" => [$deptIds, "in"],
            ], ["user_id"]);
            $userStr = array_column($users, 'user_id');
        } elseif ($setType == 3) {
            // 用户
            $userStr = is_array($setId) ? $setId : [$setId];
        }
        return $userStr;
    }
    
    private function getChildrenDetail($parentId, $maxLevel, $dataFinal, $setType, $setId, $queryUser, $subjectCheck, $subjectValues, &$detailStatis, &$detailStatisCount, &$monthCombile, &$result, &$temp, &$tempTotal) {
        $parent = app($this->chargeTypeRepository)->getChargeTypeChildren($parentId);
        if (!$parent->isEmpty()) {
            foreach ($parent as $key => $value) {
                // 按筛选，展示月/季度/天的费用
                $dataFinal['chargeTypeID'] = $value->charge_type_id;
                
                $dataFinal["chargeTypeParent"] = $value->charge_type_id;
                if ($value->has_children == 1) {
                    $dataFinal['has_children'] = 1;
                }
                // 含有子项的需要统计所有子项的费用
                $chargeTotal = app($this->chargeListRepository)->getChargeTotal($dataFinal);
                if ($value->level == 1) {
                    // 因为有子项的费用类型会把子项费用统计进去，所以所有一级费用的合计值相加得出年度总费用
                    $detailStatisCount = $detailStatisCount + $chargeTotal;
                }

                if ($value->has_children == 1) {
                    // 获取该分类有几个最小分类，决定合并行数
                    $rowspan = app($this->chargeTypeRepository)->getBottomChildrenCount($value->charge_type_id);
                    $colspan = 1;

                    // 每个费用分类合计
                    array_unshift($tempTotal, [
                        'charge_total' => $chargeTotal,
                        'rowspan' => $rowspan,
                        'colspan' => $colspan,
                        'charge_type_id' => $value->charge_type_id
                    ]);
                    
                    $temp[] = [
                        'charge_type_name' => $value->charge_type_name,
                        'has_children'     => 1,
                        'level'            => $value->level,
                        'colspan'          => $colspan,
                        'rowspan'          => $rowspan
                    ];
                    // 不是最小级时递归
                    $this->getChildrenDetail($value->charge_type_id, $maxLevel, $dataFinal, $setType, $setId, $queryUser, $subjectCheck, $subjectValues, $detailStatis, $detailStatisCount, $monthCombile, $result, $temp, $tempTotal);
                } else {
                    $rowspan = 1;
                    $colspan = $maxLevel - $value->level + 1;

                    $detailData = app($this->chargeListRepository)->getChargeDetail($dataFinal);

                    // 按筛选，展示每月/每季度/每天的费用合计，在最后一行展示
                    if (!empty($detailData[0])) {
                        foreach ($detailData[0] as $k => $v) {
                            if ($v != 0) {
                                $monthCombile[] = $k;
                            }

                            if (isset($detailStatis[$k])) {
                                $detailStatis[$k] = $detailStatis[$k] + $v;
                            }
                        }
                    }
                    // 每个费用分类合计
                    array_unshift($tempTotal, [
                        'charge_total' => $chargeTotal,
                        'rowspan' => $rowspan,
                        'colspan' => $colspan,
                        'charge_type_id' => $value->charge_type_id
                    ]);
                    $chargeTotal = 0;
                    $result['charge_total'][] = $tempTotal;
                    $tempTotal = [];

                    $detailData[0]['charge_type_id'] = $value->charge_type_id;
                    $result['charge_list'][] = $detailData[0];
                    
                    $typeAlertValue = $subjectCheck == 1 ? ($subjectValues[$value->charge_type_id] ?? 0) : 0;

                    $temp[] = [
                        'charge_type_name' => $value->charge_type_name,
                        'has_children'     => 0,
                        'level'            => $value->level,
                        'colspan'          => $colspan,
                        'rowspan'          => $rowspan,
                        'type_alert_value' => $typeAlertValue
                    ];
                    $result['type_list'][] = $temp;
                    $temp = [];
                }
            }
        }
        
        return $result;
    }

    /**
     * 图表视图
     */
    public function chargeCharts($data, $own)
    {
        $filter  = $data['filter'] ?? "year";
        $year    = $data['year'] ?? '';
        $month   = $data['month'] ?? "";
        $day     = $data['day'] ?? "";
        $quarter = $data['quarter'] ?? "";
        $flag    = $data['flag'] ?? "";
        $setId   = isset($data['set_id']) && !empty($data['set_id']) ? json_decode($data['set_id'], true) : "0";
        $setType = $data['set_type'] ?? 0;
        $hasDepts = $data['has_depts'] ?? 0;
        $typeId = $data['type_id'] ?? 0;
        $filterType = $data['filter_type'] ?? 0;

        // 费用权限
        $viewUser = $this->getViewUser($own);
        $queryUser  = $this->getQueryUser($setType, $setId, $hasDepts);;
        // 科目预警
        static $subjectCheck = 0;
        static $subjectWhere = [];
        // 获取预警值
        $chargeAlertValue = $this->getChargeAlert($setId, $setType, $subjectCheck, $subjectWhere);

        $dataFinal = [
            'filter'  => $filter,
            'year'    => $year,
            'month'   => $month,
            'day'     => $day,
            'quarter' => $quarter,
            'flag'    => $flag,
            'power'   => isset($data['power']) ? $data['power'] : 0,
            'filter_type' => $filterType,
            'set_type' => $setType,
            'set_id' => $setId,
            'users' => $queryUser,
            'viewUser' => $viewUser,
            'has_depts' => $hasDepts
        ];

        if (isset($data['flag']) && $data['flag'] == 'my') {
            $projectIds = ProjectService::thirdMineProjectId($own);
            $dataFinal['projects'] = $projectIds;
        } else {
            $dataFinal['power'] = 1;
        }

        if ($setType == 5) {
            if (empty($setId)) {
                $projectIds = ProjectService::thirdMineProjectId($own);
                $dataFinal['project_id'] = ['in', $projectIds];
            } else {
                $dataFinal['project_id'] = ['=', $setId];
            }
        }

        $name = [];
        $total = [];
        $types = [];

        if ($typeId != 0) {
            $chargeTypeParent =  app($this->chargeTypeRepository)->getChargeTypeInfo($typeId);
        }
        // 图表视图，有子类型的时候展示各子类型费用图表，没有子类型时展示展示各月/季度/天明细
        if ($typeId == 0 || $chargeTypeParent->has_children == 1) {
            $chargeTypes = app($this->chargeTypeRepository)->getChargeTypeChildren($typeId);
            foreach ($chargeTypes as $key => $value) {
                $name[] = $value->charge_type_name;
                $types[] = $value->charge_type_id;
                $dataFinal['chargeTypeID'] = $value->charge_type_id;
                $dataFinal['has_children'] = $value->has_children;
                $detailData = app($this->chargeListRepository)->getChargeTotal($dataFinal);

                $total[] = $detailData;
            }

            $mm    = date("t", strtotime("$year-$month"));
            $month = [];
            for ($i = 1; $i <= $mm; $i++) {
                $month[] = $i;
            }

            return [
                'chargeInfo' => $chargeAlertValue,
                'name'       => $name,
                "total"      => $total,
                'month'      => $month,
                'quarter'    => $quarter,
                'types'      => $types 
            ];
        } else {
            $dataFinal['chargeTypeID'] = $chargeTypeParent->charge_type_id;
            $detailData = app($this->chargeListRepository)->getChargeDetail($dataFinal);
            return [
                'chargeInfo' => $chargeAlertValue,
                'detail' => $filter == 'quarter' ? array_values($detailData[1]) : array_values($detailData[0])
            ];
        }
    }

    // 删除科目时更新 charge_subjects_setting 和 charge_setting
    public function updateSetAndDeleteType($typeId)
    {
        // 判断科目预警表中有没有该科目记录，先判断删除的是不是最小级科目
        $chargeType = app($this->chargeTypeRepository)->getDetail($typeId);
        if (!isset($chargeType->charge_type_id)) {
            return false;
        }
        if ($chargeType->has_children == 1) {
            // 如果不是最小级科目，意味着要删除该分类下所有最小科目的预警，先查出所有的最小科目
            $deleteIds = app($this->chargeTypeRepository)->getBottomChildren($typeId)->pluck('charge_type_id')->toArray();
            app($this->chargeTypeRepository)->deleteChargeType($typeId);
        } else {
            $deleteIds = [$typeId];
            app($this->chargeTypeRepository)->deleteById($typeId);
        }
        // 删除科目预警
        DB::table('charge_setting')->where('subject_check', 1)->whereNull('deleted_at')->orderBy('charge_setting_id')->chunk(100, function ($lists) use ($deleteIds) {
            if (!empty($lists)) {
                foreach ($lists as $key => $value) {
                    if ($value->subject_values) {
                        $subjectValues = json_decode($value->subject_values, true);
                        $newValues = [];
                        $total = 0;
                        $deleteFlag = false;
                        if (!empty($subjectValues)) {
                            $subjectValuesArray = array_column($subjectValues, 'type_value', 'type_id');
                            if (!empty($subjectValuesArray)) {
                                foreach ($subjectValues as $k => $v) {
                                    if (isset($v['type_id']) && isset($v['type_value'])){
                                        if (in_array($v['type_id'], $deleteIds)) {
                                            $deleteFlag = true;
                                        } else {
                                            $newValues[] = $v;
                                            $total += $v['type_value'];
                                        }
                                    }
                                }
                                if ($deleteFlag) {
                                    $newSubjectValues = $newValues ? json_encode($newValues) : '';
                                    app($this->chargeSettingRepository)->updateData([
                                        'alert_value' => $total,
                                        'subject_values' => $newSubjectValues
                                    ], ['charge_setting_id' => $value->charge_setting_id]);
                                }
                            }
                        }
                    }
                }
            }
        });

        return true;
    }
    /**
     * 修改录入的费用
     *
     * @param array $data 修改录入费用
     *
     * @return bool
     *
     * @author 喻威
     *
     * @since 2015-10-20
     */
    public function editNewCharge($chargeListId, $data, $own)
    {
        // 验证数据是否为空
        $chargeInfo = app($this->chargeListRepository)->infoCharge($chargeListId);
        if (count($chargeInfo) == 0) {
            return ['code' => ['0x020003', 'charge']]; // 系统异常
        }
        // 验证是否可以操作
        if ($chargeInfo[0]['charge_form'] != 1) { //数据来自外发
            return ['code' => ['0x020005', 'charge']];
        }

        $status = $data["charge_type_id"] && $data["charge_undertaker"] && $data["payment_date"] && $data["reason"] && $data["user_id"];
        if (!$status) {
            return ['code' => ['0x020004', 'charge']];
        }
        $viewUser = $this->getViewUser($own);
        if ($chargeInfo[0]['user_id'] != $own['user_id']) {
            if ($viewUser != 'all' && !in_array($chargeInfo[0]['user_id'], $viewUser)) {
                return ['code' => ['0x020006', 'charge']];
            }
        }

        if (!isset($data['undertake_user']) || empty($data['undertake_user'])) {
            $data['undertake_user'] = $data["user_id"];
        }

        if (!isset($data["undertake_dept"]) && $data['charge_undertaker'] == 2) {
            return ['code' => ['0x020039', 'charge']];
        }

        $chargeData = array_intersect_key($data, array_flip(app($this->chargeListRepository)->getTableColumns()));

        $result = app($this->chargeListRepository)->updateData($chargeData, ["charge_list_id" => $chargeListId]);

        return $result;
    }

    /**
     * 查看费用详情
     *
     */
    public function getNewCharge($chargeListId, $data, $own)
    {
        $result = app($this->chargeListRepository)->getNewCharge($chargeListId);
        if (isset($result->charge_undertaker)) {
            if ($result->user_id != $own['user_id'] && 
                (!isset($data['undertake_self']) || $data['undertake_self'] == 0)) {
                // 我的费用菜单下不能编辑其他人的费用
                // if (isset($data['menu']) && $data['menu'] == 'my') {
                //     return ['code' => ['0x020006', 'charge']];
                // }
                $viewUser = $this->getViewUser($own);
                if ($viewUser != 'all' && !in_array($result->user_id, $viewUser)) {
                    return ['code' => ['0x020006', 'charge']];
                }
            }
            $object       = '';
            if ($result->charge_undertaker == 1) {
                $object = $result->undertake_user;
                $result->undertake_user_name = app($this->userService)->getUserName($result->undertake_user) ?? '';
            } elseif ($result->charge_undertaker == 2) {
                $object = $result->undertake_dept;
            } elseif ($result->charge_undertaker == 3) {
                $object = '';
            } elseif ($result->charge_undertaker == 4) {
                $object = $result->project_id;
            }

            // 科目预警
            $typeValue = $this->chargeSubjectValue([
                'charge_undertake' => $result->charge_undertaker,
                'subject_type'      => $result->charge_type_id,
                'object'            => $object,
                'payment_date'      => $result->payment_date,
            ]);
            $result->type_value = !empty($typeValue) && isset($typeValue['subjectValue']) ? $typeValue['subjectValue'] : 0;
            // 科目
            $types = $this->getChargeTypeParent($result->charge_type_id);
            $result->types = $types ?? [];
            $result->type_path = !empty($types) ? implode(' / ', array_column($types, 'charge_type_name')) : '';
            // 关联项目
            $result->project_name = '';
            if ($result->project_id != '') {
                $tempData = app('App\EofficeApp\Project\Repositories\ProjectManagerRepository')->getDetail($result->project_id);
                $result->project_name = $tempData['manager_name'] ?? '';
            }
            // 关联流程
            $result->run_name = '';
            if ($result->charge_form == 2 && !empty($result->charge_extra)) {
                $chargeExtra = json_decode($result->charge_extra);
                $result->run_name = isset($chargeExtra->run_name) ? $chargeExtra->run_name : '';
            }
            $result->dept_name = $result->dept_name?$result->dept_name:trans('charge.Deleted');
            $result->undertake_user_name = $result->undertake_user_name?$result->undertake_user_name:trans('charge.Deleted');
            $result->user_name = $result->user_name?$result->user_name:trans('charge.Deleted');
            return $result;
        }

        return [];
    }
    // 获取所有父级科目
    public function getChargeTypeParent($typeId) {
        static $parent = [];

        $type = app($this->chargeTypeRepository)->getChargeTypeInfo($typeId, ['fields' => ['charge_type_id', 'charge_type_name', 'charge_type_parent', 'has_children']]);
        
        if ($type->charge_type_parent != 0) {
             $this->getChargeTypeParent($type->charge_type_parent);
        }

        $parent[] = $type;

        return $parent;
    }

    /**
     * 删除录入费用(单条)
     *
     * @param array $data
     *
     * @return bool
     *
     * @author 喻威
     *
     * @since 2015-10-20
     */
    public function deleteNewCharge($chargeListId, $own, $fromFlow = false)
    {
        $chargeIds = explode(",", $chargeListId);

        $creator = app($this->userService)->getUserName($own['user_id']);

        // 添加删除日志
        foreach ($chargeIds as $key => $value) {
            $logData = [
                'log_content'        => trans("charge.delete_cost_record"),
                'log_type'           => 'charge',
                'log_creator'        => $own['user_id'],
                'log_time'           => date('Y-m-d H:i:s'),
                'log_ip'             => getClientIp(),
                'log_relation_table' => 'charge_list',
                'log_relation_id'    => $value,
            ];

            $chargeInfo = app($this->chargeListRepository)->getDetail($value);
            $userName   = $chargeType   = $chargeCost   = $date   = $underTaker   = $chargeFrom   = '';

            if (!empty($chargeInfo)) {
                $viewUser = $this->getViewUser($own);
                if ($chargeInfo->user_id != $own['user_id']) {
                    if ($viewUser != 'all' && !in_array($chargeInfo->user_id, $viewUser)) {
                        return ['code' => ['0x020006', 'charge']];
                    }
                }

                if ($chargeInfo->charge_form == 2 && $own['user_id'] != 'admin' && !$fromFlow) {
                    return ['code' => ['0x020006', 'charge']];
                }

                $userName   = isset($chargeInfo->user_id) ? app($this->userService)->getUserName($chargeInfo->user_id) : '';
                $chargeType = isset($chargeInfo->charge_type_id) ? app($this->chargeTypeRepository)->getChargeTypeNameById($chargeInfo->charge_type_id) : '';
                $chargeCost = $chargeInfo->charge_cost ?? '';
                $date       = $chargeInfo->payment_date ?? '';
                $underTaker = isset($chargeInfo->charge_undertaker) ? $this->getSimpleUnderTaker($chargeInfo) : '';
                $chargeFrom = isset($chargeInfo->charge_form) && $chargeInfo->charge_form == 1 ? trans("charge.add_charge") : trans("charge.outsend_flow");
            }

//            $logData['log_content'] .= "<strong>" . trans('charge.operator') . "</strong>" . $creator . "， " .
//                "<strong>" . trans('charge.reimburser') . "：</strong>" . $userName . "， " .
//                "<strong>" . trans('charge.subject') . "：</strong>" . $chargeType . "， " .
//                "<strong>" . trans('charge.charge_cost') . "：</strong>" . $chargeCost . "， " .
//                "<strong>" . trans('charge.payment_date') . "：</strong>" . $date . "， " .
//                "<strong>" . trans('charge.charge_undertaker') . "：</strong>" . $underTaker . "， " .
//                "<strong>" . trans('charge.charge_from') . "：</strong>" . $chargeFrom;
//            if (!empty($chargeInfo) && isset($chargeInfo->charge_form) && $chargeInfo->charge_form == 2 && isset($chargeInfo->charge_extra) && !empty($chargeInfo->charge_extra)) {
//                $chargeExtra = json_decode($chargeInfo->charge_extra);
//                $flowName    = isset($chargeExtra->run_name) ? $chargeExtra->run_name : '';
//                if ($flowName != '') {
//                    $logData['log_content'] .= "， <strong>" . trans('charge.association_process') . "：</strong>" . $flowName;
//                }
//            }
//            add_system_log($logData);
            $logData['log_content'] .=  trans('charge.operator') .  $creator . "， " .
                 trans('charge.reimburser') . "：" . $userName . "， " .
                 trans('charge.subject') . "：" . $chargeType . "， " .
                 trans('charge.charge_cost') . "：" . $chargeCost . "， " .
                "" . trans('charge.payment_date') . "：" . $date . "， " .
                trans('charge.charge_undertaker') . "：" . $underTaker . "， " .
               trans('charge.charge_from') . "：" . $chargeFrom;
            $logData['relation_title'] = $chargeType.'（'.$chargeCost.'）';
            if (!empty($chargeInfo) && isset($chargeInfo->charge_form) && $chargeInfo->charge_form == 2 && isset($chargeInfo->charge_extra) && !empty($chargeInfo->charge_extra)) {
                $chargeExtra = json_decode($chargeInfo->charge_extra);
                $flowName    = isset($chargeExtra->run_name) ? $chargeExtra->run_name : '';
                if ($flowName != '') {
                    $logData['log_content'] .= "，" . trans('charge.association_process') . "：" . $flowName;
                }
            }
            $identifier  = "charge.charge.delete";
            $logParams = $this->handleLogParams($own['user_id'], $logData['log_content'], $logData['log_relation_id'], $logData['log_relation_table'], $logData['relation_title']);
            logCenter::info($identifier , $logParams);
        }
        $delete_status = app($this->chargeListRepository)->deleteByWhere(['charge_list_id' => [$chargeIds, 'in']]);
        return $delete_status;
    }

    private function getSimpleUnderTaker($chargeInfo)
    {
        $result = '';
        switch ($chargeInfo->charge_undertaker) {
            case '1':
                $result = trans('charge.user');
                break;
            case '2':
                $result = trans('charge.department');
                break;
            case '3':
                $result = trans('charge.company');
                break;
            case '4':
                $projectName = '';
                if ($chargeInfo->project_id != '') {
                    $tempData    = app('App\EofficeApp\Project\Repositories\ProjectManagerRepository')->getDetail($chargeInfo->project_id);
                    $projectName = $tempData['manager_name'];
                }
                $result = '[' . trans('portal.project') . ']' . $projectName;
                break;
            default:
                break;
        }
        return $result;
    }

    /**
     * 获取费用设置详情
     * @param array $data 费用检索
     *
     * @return array
     *
     * @todo 获取当前用户的权限，没有权限查看别人的费用设置
     *
     * @author 喻威
     *
     * @since 2015-10-20
     */
    public function getOneChargeSetting($setId)
    {
        if ($result = app($this->chargeSettingRepository)->getDetail($setId)) {
            if (isset($result->subject_check) && $result->subject_check == 1) {
                $subjectValues = json_decode($result->subject_values, true);
                $subjectValues = !empty($subjectValues) ? array_column($subjectValues, 'type_value', 'type_id') : [];
                //重组
                $result->subject_values = $this->getSubjectValues($subjectValues, 0);
            }
            return $result;
        } else {
            return ['code' => ['0x020004', 'charge']];
        }
    }

    public function getSubjectValues($subjectValues, $parentId) {
        static $list = [];
        $children = app($this->chargeTypeRepository)->getChargeTypeChildren($parentId, [
            'fields' => ['charge_type_id', 'charge_type_name', 'charge_type_parent', 'has_children']
        ]);
        if (!$children->isEmpty()) {
            foreach ($children as $value) {
                if (!isset($list[$value->charge_type_parent])) {
                    $list[$value->charge_type_parent] = [];
                }

                $value->type_value = $subjectValues[$value->charge_type_id] ?? 0;
                
                if ($value->has_children == 1) {
                    $this->getSubjectValues($subjectValues, $value->charge_type_id);
                } else {
                    $list[$value->charge_type_parent][] = $value;
                }
                
            }
        }
        return $list;
    }
    
    // 获取费用预警设置选择器
    public function getChargeSetSelectorData($param, $own) {
        $param   = $this->parseParams($param);
        // 预警设置选择器搜索过滤
        if (isset($param['search']['name'][0])) {
            if ($param['search']['name'][0] == trans('charge.company')) {
                $param['search']['set_type'] = [1];
                $param['hasProject'] = 'false';
            } else {
                $param['name'] = $param['search']['name'][0];
            }
            unset($param['search']['name']);
        }
        if (!isset($param['search']['charge_setting_id'])) {
            $projectIds = ProjectService::thirdMineProjectId($own);
            if (count($projectIds) > 0) {
                $param['search']['project_id'] = [$projectIds, 'in'];
            }
        }

        $data = $this->response(app($this->chargeSettingRepository), 'getChargeSetSelectorTotal', 'getChargeSetSelectorList', $param);
        if (isset($data['list']) && !empty($data['list'])) {
            foreach ($data['list'] as $key => $value) {
                $data['list'][$key]['name'] = $this->parseChargeSettingData($value);
                $data['list'][$key]['user_name'] = $data['list'][$key]['user_name']?$data['list'][$key]['user_name']:trans('charge.Deleted');
                if($data['list'][$key]['user_status'] == 0){
                    $data['list'][$key]['user_name'] = trans('charge.Deleted');
                }
                $data['list'][$key]['dept_name'] = $data['list'][$key]['dept_name']?$data['list'][$key]['dept_name']:trans('charge.Deleted');
                $data['list'][$key]['manager_name'] = $data['list'][$key]['manager_name']?$data['list'][$key]['manager_name']:trans('charge.Deleted');
            }
        }
        if (!isset($data['total'])) {
            $data['total'] = '0';
        }
        return $data;
    }
    private function parseChargeSettingData($data) {
        $name = '';
        switch ($data['set_type']) {
            case 1:
                $name = trans('charge.company');
                break;
            case 2:
                $deptName = $data['dept_name']?$data['dept_name']:trans('charge.Deleted');
                $name = trans('charge.department') . ' - ' . $deptName;
                break;
            case 3:
                $userName = $data['user_name']?$data['user_name']:trans('charge.Deleted');
                $status = $data['user_status'] && $data['user_status'] == 2 ? '[' . trans('charge.dismissed') . ']' : '';
                $name = trans('charge.personal') . ' - ' . $data['user_name'] . $status;
                break;
            case 5:
                $managerName = $data['manager_name']?$data['manager_name']:trans('charge.Deleted');
                $name = trans('charge.project') . ' - ' . $managerName;
                break;
        }
        if ($data['set_type'] != 5 && isset($data['alert_method']) && $data['alert_method'] == 'custom') {
            $name .= '('.$data['alert_data_start'].'-'.$data['alert_data_end'].')';
        }
        return $name;
    }
    public function getCustomChargeSetting($id, $own) {
        // 判断是否有项目模块授权
        $projectEmpower = app('App\EofficeApp\Empower\Services\EmpowerService')->checkModuleWhetherExpired(160);
        // 判断当前用户是否有项目模块
        $projectMenu  = app('App\EofficeApp\Menu\Services\UserMenuService')->judgeMenuPermission(160);
        $param = [
            'hasProject' => 'true',
            'order_by' => [ 'charge_setting.updated_at' => 'desc' ],
            'search' => ['charge_setting_id' => [$id]]
        ];
        if (!($projectEmpower && $projectMenu == 'true')) {
            $param['hasProject'] = 'false';
        }
        $data =  $this->getChargeSetSelectorData($param, $own);
        return isset($data['list']) && !empty($data['list']) ? $data['list']->toArray() : [];
    }
    /**
     * 删除费用设置项
     * @param array $data 费用检索
     *
     * @return array
     *
     * @author 牛晓克
     *
     * @since 2017-12-25
     */
    public function deleteChargeSetItem($setId)
    {
        $setIds = explode(',', trim($setId, ","));
        $where  = ["charge_setting_id" => [$setIds, "in"]];

        return app($this->chargeSettingRepository)->deleteByWhere($where);
    }
    // 获取门户我的费用元素数据
    public function getChargePortal($data, $own)
    {
        return $this->chargeListDetail($data, $own);
    }
    /**
     * 获取部门或用户 列表数据
     *
     * @param array $input
     *
     * @return array
     *
     * @todo 获取用户权限
     * @todo 获取某一个部门的所有用户
     *
     * @author 喻威
     *
     * @since 2015-10-20
     */
    public function chargeListDetail($data, $own)
    {
        $data = $this->parseParams($data);
        $setType    = $data["set_type"] ?? 0;
        $data["filter_type"] = $data["filter_type"] ?? 1;
        $flag       = $data['flag'] ?? 'list';
        $power      = $data["power"] ?? 0;
        $data["charge_type"]      = $data["charge_type"] ?? 0;
        $data["filter"]      = $data["filter"] ?? 'year';
        $from = isset($data['from']) && $data['from'] == 'selector' ? true : false;
        $setId = $data['set_id'] = isset($data['set_id']) && !empty($data['set_id']) ? json_decode($data['set_id'], true) : [];
        $viewUser = $this->getViewUser($own);
        if (empty($viewUser)) {
            if ($flag == 'my') {
                $viewUser = [$own['user_id']];
            } else {
                return [];
            }
        }
        if($from && isset($data['search'])){
            $viewUser = 'all';
        }
        if (isset($data['search']['name'][0])) {
            if ($data['search']['name'][0] == trans('charge.company')) {
                $data['search']['charge_undertaker'] = [3];
            } else {
                $data['name'] = $data['search']['name'][0];
            }
            unset($data['search']['name']);
        }
        $data['viewUser'] = $viewUser;

        if (empty($setId)) {
            $data['users'] = [];
        } else {
            if($setType == 2) {
                // 部门
                if (isset($data['has_depts']) && $data['has_depts'] == 1) {
                    //获取当前部门下所有的用户
                    $deptIds = $setId;
                    if (!empty($setId)) {
                        foreach ($setId as $deptId) {
                            $deptIds = array_merge($deptIds, app($this->departmentService)->getTreeIds($setId));
                        }
                    }
                } else {
                    $deptIds = $setId;
                }
                $where = [
                    "dept_id" => [$deptIds, "in"],
                ];
                
                $users= app($this->userSystemInfoRepository)->getInfoByWhere($where, ["user_id"]);
                $data["users"] = array_column($users, 'user_id');
                
                $data['power'] = 0;
            } else {
                // 个人
                if ($flag == 'my') {
                    $data["users"] = $setId;
                } else {
                    $data["users"] = $viewUser == 'all' ? $setId : array_intersect($setId, $viewUser);;
                }
            }
        }

        if ($power == 1) {
            $projectIds = ProjectService::thirdMineProjectId($own);
            $data['projects'] = $projectIds;
        }

        $chargeType = app($this->chargeTypeRepository)->getChargeTypeInfo($data['charge_type'], ['fields' => ['has_children']]);
        $data['has_children'] = $chargeType->has_children ?? 0;

        $allUser = app($this->userRepository)->getUserList([
            'fields' => ['user_id', 'user_name'],
            'include_leave' => 1,
            'with_trashed' => 1,
            'return_type' => 'array'
        ]);
        $allUserArr = array_column($allUser, 'user_name', 'user_id');
        $list = app($this->chargeListRepository)->chargeListDetail($data);
        if (!empty($list)) {
            foreach ($list as $key => $value) {
                $list[$key]['purview'] = $viewUser == 'all' ? 1 : (in_array($value['user_id'], $viewUser) ? 1 : 0);
                $list[$key]['user_name'] = $value['user_id'] ? ($allUserArr[$value['user_id']] ?? '') : trans('charge.Deleted');
                $list[$key]['undertake_user_name'] = $value['undertake_user_name'] ?$value['undertake_user_name'] : trans('charge.Deleted');
                if ($from) {
                    $list[$key]['new_name'] = $this->parseChargeListSelectorData($value);
                }
                $list[$key]['user_name'] = $list[$key]['user_name']?$list[$key]['user_name']:trans('charge.Deleted');
                $list[$key]['dept_name'] = $list[$key]['dept_name']?$list[$key]['dept_name']:trans('charge.Deleted');
            }
        }
        $total = app($this->chargeListRepository)->getchargeListTotal($data);

        return ['list' => $list, 'total' => $total];
    }

    private function parseChargeListSelectorData($data) {
        $undertaker = '';
        switch ($data['charge_undertaker']) {
            case 1:
                $undertaker = $data['undertake_user_name'];
                if ($data['user_status'] && $data['user_status'] == 2) {
                    $undertaker .= '[' . trans('charge.dismissed') . ']';
                }
                break;
            case 2:
                $undertaker = $data['dept_name'];
                break;
            case 3:
                $undertaker = trans('charge.company');
                break;
            case 4:
                $undertaker = trans('charge.project');
                break;
            default:
                break;
        }
        return $data['user_name'] . $data['payment_date'] . ' ' . trans('charge.expenses') . ' ' . $undertaker . ' ' . trans('charge.charge');
    }

    public function getCustomCharge($id, $own) {
        $data =  $this->chargeListDetail([
            'charge_type' => 0,
            'filter' => 'year',
            'filter_type' => 1,
            'power' => 1,
            'order_by' => ['payment_date' => 'desc'],
            'from' => 'selector',
            'search' => ['charge_list_id' => [$id]]
        ], $own);
        return isset($data['list']) && !empty($data['list']) ? $data['list']->toArray() : [];
    }

    public function chargeListTree($parent_id, $data, $own)
    {
        // 1 获取这个部门下所有的子部门及直属人员
        $temp     = [];
        $result   = [];
        $viewUser = $this->getViewUser($own);
        //当前这个部门下的用户
        $treeUser = app($this->userRepository)->getUserByDepartment($parent_id)->toArray();

        foreach ($treeUser as $user) {
            if ($viewUser == 'all') {
                $temp["set_id"]       = $user["user_id"];
                $temp["set_name"]     = $user["user_name"];
                $temp["user_status"]  = $user["user_status"];
                $temp["has_children"] = 0;
                $temp["set_count"]    = (float) $this->getStatistic($user["user_id"], "user", $viewUser);
                array_push($result, $temp);
            } else {
                if (in_array($user["user_id"], $viewUser)) {
                    $temp["set_id"]       = $user["user_id"];
                    $temp["set_name"]     = $user["user_name"];
                    $temp["user_status"]  = $user["user_status"];
                    $temp["has_children"] = 0;
                    $temp["set_count"]    = (float) $this->getStatistic($user["user_id"], "user", $viewUser);
                    array_push($result, $temp);
                }
            }
        }

        //当前这个部门的树
        $treesDept = app($this->departmentRepository)->getChildren($parent_id)->toArray();
        
        foreach ($treesDept as $dept) {
            $temp["set_id"]       = $dept["dept_id"];
            $temp["set_name"]     = $dept["dept_name"];
            $temp["has_children"] = 1;

            if ($viewUser == 'all') {
                $temp["set_count"] = (float) $this->getStatistic($dept["dept_id"], "dept", $viewUser);
                array_push($result, $temp);
            } else {
                $depts     = app($this->departmentRepository)->getALLChlidrenByDeptId($dept["dept_id"]);
                $deptArr   = array_column($depts, 'dept_id');
                $deptArr[] = $dept["dept_id"];
                $users     = app($this->userRepository)->getUserByAllDepartment($deptArr)->pluck('user_id')->toArray();
                if (!empty(array_intersect($viewUser, $users))) {
                    $temp["set_count"] = (float) $this->getStatistic($dept["dept_id"], "dept", $viewUser);
                    array_push($result, $temp);
                }
            }
        }

        return $result;
    }

    //获取当前部门|用户的报销值
    public function getStatistic($setId, $type, $viewUser)
    {
        $user_str = [];
        if ($type == "dept") {
            $deptIds = app($this->departmentService)->getTreeIds($setId);
            //获取当前的部门下所有的人
            $where    = ["dept_id" => [$deptIds, "in"]];
            $users    = app($this->userSystemInfoRepository)->getInfoByWhere($where, ["user_id"]);
            $user_str = array_column($users, 'user_id');
        } else {
            $user_str[] = $setId;
        }
        if ($viewUser != 'all') {
            $user_str = array_intersect($user_str, $viewUser);
        }
        if (count($user_str) == 0) {
            return 0;
        } else {
            //获取当前用户整合
            $where = [
                "user_id"    => [$user_str, "in"],
                "project_id" => [null],
            ];

            return app($this->chargeListRepository)->getStatistic($where);
        }
    }

    public function getChargeTreeTotal($own)
    {
        //返回列表中顶级部门及部门汇总值
        //获取部门的名称
        $user_str = [];
        $deptIds  = app($this->departmentService)->getTreeIds(0);
        //获取当前的部门下所有的人
        $where    = ["dept_id" => [$deptIds, "in"]];
        $users    = app($this->userSystemInfoRepository)->getInfoByWhere($where, ["user_id"]);
        $viewUser = $this->getViewUser($own);
        if ($viewUser == 'all') {
            $user_str = array_column($users, 'user_id');
        } else {
            $user_str = array_intersect(array_column($users, 'user_id'), $viewUser);
        }

        $where = [
            "user_id"    => [$user_str, "in"],
            "project_id" => [null],
        ];

        $statisic = app($this->chargeListRepository)->getStatistic($where);

        $tempDept = app($this->companyRepository)->getCompanyDetail();
        $set_name = $tempDept->company_name; //"所有部门";
        return [
            "set_name"  => $set_name,
            "set_count" => (float) $statisic,
        ];
    }
    private function getAlertDate($method, $date=false) {
        if (!$date) {
            $date = time();
        }
        $year = date("Y", $date);
        $start = $end ='';
        switch ($method) {
            case 'year':
                $start = $year . '-01-01';
                $end = $year . '-12-31';
            break;
            case 'quarter':
                $chargeMonth = ceil(date('m', $date) / 3);
                $minM = $chargeMonth * 3 - 2;
                $maxM = $chargeMonth * 3;
                $start = $year . '-' . $minM . '-01';
                $end = date('Y-m-t', mktime(0, 0, 0, $maxM, 1, date('Y')));
            break;
            case 'month':
                $month = date("m", $date);
                $start = $year . '-' . $month . '-01';
                $end = date('Y-m-t', mktime(0, 0, 0, $month, 1, date('Y')));
            break;
            default:
            break;
        }
        return [$start, $end];
    }
    //费用统计 详细列表页面
    public function chargeDetails($data, $own, $purview = true)
    {
        $data = $this->parseParams($data);
        // 获取数据源时，不需要判断权限，返回真实的数据
        if ($purview) {
            $viewUser = $this->getViewUser($own);
            $data['viewUser'] = $viewUser;
        }
        
        if (isset($data["type"]) && isset($data["id"])) {
            $field        = "";
            $tempWhere    = [];
            if ($data["type"] == "dept") {
                $field     = "dept_id";
                $tempWhere = ['undertake_dept' => [$data["id"]], "charge_undertaker" => [2]];
            } elseif ($data["type"] == "user") {
                $field     = "user_id";
                $tempWhere = ['undertake_user' => [$data["id"]], "charge_undertaker" => [1]];
            } elseif ($data["type"] == "company") {
                $tempWhere = ["charge_undertaker" => [3]];
            } elseif ($data["type"] == "project") {
                $field     = "project_id";
                $tempWhere = ['project_id' => [$data["id"]], "charge_undertaker" => [4]];
            }
            if (isset($data['subject_type']) && !empty($data['subject_type'])) {
                $tempWhere['charge_type_id'] = [$data['subject_type']];
            }
            $allUser = app($this->userRepository)->getUserList([
                'fields' => ['user_id', 'user_name'],
                'include_leave' => 1,
                'with_trashed' => 1,
                'return_type' => 'array'
            ]);
            $allUserArr = array_column($allUser, 'user_name', 'user_id');
            if (isset($data['setting_id']) && !empty($data['setting_id'])) {
                // 周期内预警值
                if ($chargeSet = app($this->chargeSettingRepository)->getDetail($data['setting_id'])) {
                    $startDate = $endDate = '';
                    if ($chargeSet->alert_method == 'custom') {
                        $startDate = $chargeSet->alert_data_start;
                        $endDate = $chargeSet->alert_data_end;
                    } else {
                        $alertDate = $this->getAlertDate($chargeSet->alert_method);
                        list($startDate, $endDate) = $alertDate;
                    }
                    if (!isset($data['search'])) {
                        $data['search'] = [];
                    }
                    if (isset($data['search']['payment_date'][0]) && is_array($data['search']['payment_date'][0])) {
                        $start = $data['search']['payment_date'][0][0] ?? $startDate;
                        if ($start >= $startDate && $start <= $endDate) {
                            $startDate = $start;
                        }

                        $end = $data['search']['payment_date'][0][1] ?? $endDate;
                        if ($end >= $startDate && $end <= $endDate) {
                            $endDate = $end;
                        }
                        if ($start || $end) {
                            $data['search']['payment_date'] = [[$startDate, $endDate], 'between'];
                        }
                    } else {
                        if ($startDate && $endDate) {
                            $data['search']['payment_date'] = [[$startDate, $endDate], 'between'];
                        }
                    }
                    $result = $this->response(app($this->chargeListRepository), 'chargeDetailsTotal', 'chargeDetails', $data);
                    if ($chargeSet->subject_check == 1) {
                        $subjectValues = json_decode($chargeSet->subject_values, true);
                        $subject = !empty($subjectValues) ? array_column($subjectValues, 'type_value', 'type_id') : [];
                        foreach ($result["list"] as $key => $value) {
                            $value['subject_check'] = 1;
                            $alertValue = isset($subject[$value["charge_type_id"]]) ? $subject[$value["charge_type_id"]] : 0;
                            $id = $data["type"] == "company" ? "" : $chargeSet->$field;
                            $res = $this->getOtherData($data["type"], $id, $chargeSet->alert_method, $alertValue, $chargeSet->alert_data_start, $chargeSet->alert_data_end, $value['charge_type_id']);
                            $value['creator_name'] = $value['user_id'] ? ($allUserArr[$value['user_id']] ?? trans('charge.Deleted')) : trans('charge.Deleted');
                            $value['undertake_user_name'] = $value['undertake_user'] ? ($allUserArr[$value['undertake_user']] ?? trans('charge.Deleted')) : trans('charge.Deleted');
                            $result["list"][$key]= array_merge($value, $res);
                        }
                        return $result;
                    } else {
                        foreach ($result['list'] as $key => $value) {
                            $result['list'][$key]["creator_name"] = $value['user_id'] ? ($allUserArr[$value['user_id']] ?? trans('charge.Deleted')) : trans('charge.Deleted');
                            $result['list'][$key]["undertake"]     = app($this->chargeListRepository)->getStatistic($tempWhere);
                            $result['list'][$key]["noundertake"]   = number_format(0, 2);
                            $result['list'][$key]["subject_check"] = 0;
                            $result['list'][$key]['undertake_user_name'] = $value['undertake_user'] ? ($allUserArr[$value['undertake_user']] ?? trans('charge.Deleted')) : trans('charge.Deleted');
                        }
                        return $result;
                    }
                }else{
                    return ['code' => ['0x020004', 'charge']];
                }
            } else {
                $result = $this->response(app($this->chargeListRepository), 'chargeDetailsTotal', 'chargeDetails', $data);
                foreach ($result['list'] as $key => $value) {
                    $result['list'][$key]["creator_name"] = $value['user_id'] ? ($allUserArr[$value['user_id']] ?? trans('charge.Deleted')) : trans('charge.Deleted');
                    $result['list'][$key]["undertake"]     = app($this->chargeListRepository)->getStatistic($tempWhere);
                    $result['list'][$key]["noundertake"]   = number_format(0, 2);
                    $result['list'][$key]["subject_check"] = 0;
                    $result['list'][$key]['undertake_user_name'] = $value['undertake_user'] ? ($allUserArr[$value['undertake_user']] ?? trans('charge.Deleted')) : trans('charge.Deleted');
                }
                return $result;
            }
        } else {
            return ['code' => ['0x020004', 'charge']];
        }
    }

    // 费用清单/我的费用列表页面 导出
    public function chargeListExport($param)
    {
        $own      = $param['user_info']['user_info'];
        $langType = isset($param['lang_type']) ? $param['lang_type'] : null;
        $header   = [
            'user_name'         => ['data' => trans('charge.reimburser', [], $langType), 'style' => ['width' => 15]],
            'charge_type_name'  => ['data' => trans('charge.subject', [], $langType), 'style' => ['width' => 30]],
            'charge_cost'       => ['data' => trans('charge.charge', [], $langType), 'style' => ['width' => 15]],
            'payment_date'      => ['data' => trans('charge.payment_date', [], $langType), 'style' => ['width' => 15]],
            'reason'            => ['data' => trans('charge.reason', [], $langType), 'style' => ['width' => 20]],
            'charge_undertaker' => ['data' => trans('charge.charge_undertaker', [], $langType), 'style' => ['width' => 20]],
            'charge_form'       => ['data' => trans('charge.charge_from', [], $langType), 'style' => ['width' => 15]],
        ];

        $projectArr = $this->getProjectArray();

        $reportData = $this->chargeListDetail($param, $own)['list'];
        // 所有科目路径
        $allType = $this->getChargeTypeFullPath(true);

        $data       = [];
        if (count($reportData) > 0) {
            $reportData = $reportData->toArray();
            foreach ($reportData as $value) {
                $projectName = '';
                if ($value['charge_undertaker'] == 4) {
                    $projectName = $projectArr[$value['project_id']] ?? '';
                }
                $value['charge_undertaker'] = $this->getChargeUndertaker($value['charge_undertaker'], $langType, $value);
                if (!empty($projectName)) {
                    $value['charge_undertaker'] .= '： ' . $projectName;
                }
                $value['reason']            = strip_tags($value['reason']);
                $value['reason']            = str_replace('&nbsp;', ' ', $value['reason']);
                $value['charge_type_name']  = isset($allType[$value['charge_type_id']]) ? $allType[$value['charge_type_id']] : $value['charge_type_name'];
                $value['charge_form']       = $value['charge_form'] == '1' ? trans('charge.add_charge', [], $langType) : trans('charge.outsend_flow', [], $langType);
                $data[]                     = Arr::dot($value);
            }
        }

        return compact('header', 'data');
    }

    private function getProjectArray(){
        $projects = app($this->projectManagerRepository)->getProjectManagerList(['fields' => ['manager_id', 'manager_name']]);
        $projectArr = [];
        if (!empty($projects)) {
            foreach ($projects as $value) {
                if (isset($value['manager_id']) && isset($value['manager_name'])) {
                    $projectArr[$value['manager_id']] = $value['manager_name'];
                }
            }
        }
        return $projectArr;
    }

    private function getChargeUndertaker($undertaker, $langType, $value)
    {
        $result = '';
        switch ($undertaker) {
            case '1':
                $result = trans('charge.user', [], $langType) . '：' . $value['undertake_user_name'];
                break;
            case '2':
                $result = trans('charge.department', [], $langType) . '：' . $value['dept_name'];
                break;
            case '3':
                $result = trans('charge.company', [], $langType);
                break;
            case '4':
                $result = trans('portal.project', [], $langType);
                break;
            default:
                break;
        }
        return $result;
    }

    //导出费用清单
    public function chargeHtmlExport($param)
    {
        $langType = isset($param['lang_type']) ? $param['lang_type'] : null;
        $style    = " <head>
            <style>
                .statistics-table td{
                    border: thin solid #000;
                    height: 30px;
                    text-align: center;
                    min-width: 25px;
                }
                .statistics-table td.title-td{
                    background: #EAEAEA;
                    width:120px;
                }
                .statistics-table td.highlight-td{
                    background:#FBD5B6;
                    width:220px;
                }
            </style>
        </head> ";
        $lists = $this->chargeListData($param, $param['user_info'] ?? own());
        // 首行展示预警值
        $colspan = $param['colspan'] ? ($param['colspan'] + $lists['max_level'] * 2) : 16;
        $thead   = "<tr><td colspan=" . $colspan . ">";
        if (isset($param['filter'])) {
            switch ($param['filter']) {
                case 'year':
                    $year = $param['year'] ?? date('Y');
                    $suffix = isset($param['lang_type']) && $param['lang_type'] == 'zh-CN' ? trans("charge.year") : ' ';
                    $thead .= $year . $suffix . ' ';
                    break;
                case 'quarter':
                    $year = $param['year'] ?? date('Y');
                    $suffix = isset($param['lang_type']) && $param['lang_type'] == 'zh-CN' ? trans("charge.year") : ' ';
                    $thead .= $year . $suffix . ' ';
                    break;
                case 'month':
                    $year  = $param['year'] ?? date('Y');
                    $month = $param['month'] ?? date('m');
                    $suffix = isset($param['lang_type']) && $param['lang_type'] == 'zh-CN' ? trans("charge.year") : ' ';
                    if (isset($param['lang_type']) && $param['lang_type'] == 'zh-CN') {
                        $thead .= $year . trans("charge.year") . $month . trans("charge.month") . ' ';
                    } else {
                        $thead .= $year . '.' . $month . ' ';
                    }
                    break;
                default:
                    break;
            }
        }

        if (isset($param['set_type'])) {
            switch ($param['set_type']) {
                case 3:
                    if (isset($param['set_id'])) {
                        $param['set_id'] = json_decode($param['set_id']);
                        if (count($param['set_id']) == 1 && isset($param['set_id'][0])) {
                            $userName = app($this->userRepository)->getUserName($param['set_id'][0]);
                            $thead .= $userName . ' ';

                            $userAlarmMode  = $lists['set_list']['self_type'] ?? trans("charge.not_set");
                            $userAlarmValue = $lists['set_list']['self_values'] ?? trans("charge.not_set");
                            $thead .= trans("charge.user") . trans("charge.method") . ':' . $userAlarmMode . ' ';
                            $thead .= trans("charge.user") . trans("charge.value") . ':' . $userAlarmValue . ' ';
                            if (isset($param['flag']) && $param['flag'] == 'my') {
                                $userDept = app($this->userRepository)->getUserDeptIdAndRoleIdByUserId($param['set_id'][0]);

                                $deptAll  = app($this->departmentRepository)->getDetail($userDept['dept_id']);
                                $deptName = $deptAll->dept_name;
                                $thead .= $deptName . ' ';
                                $deptAlarmMode  = $lists['set_list']['dept_type'] ?? trans("charge.not_set");
                                $deptAlarmValue = $lists['set_list']['dept_values'] ?? trans("charge.not_set");
                                $thead .= trans("charge.department") . trans("charge.method") . ':' . $deptAlarmMode . ' ';
                                $thead .= trans("charge.department") . trans("charge.value") . ':' . $deptAlarmValue . ' ';
                            }
                        }
                    }
                    break;
                case 2:
                    if (isset($param['set_id'])) {
                        $param['set_id'] = json_decode($param['set_id']);
                        if (count($param['set_id']) == 1 && isset($param['set_id'][0])) {
                            if ($param['set_id'][0] == 0) {
                                $temp    = app($this->companyRepository)->getCompanyDetail();
                                $company = $temp->company_name; //"所有部门";
                                $thead .= $company . ' ';
                            } else {
                                $deptAll  = app($this->departmentRepository)->getDetail($param['set_id'][0]);
                                $deptName = $deptAll->dept_name;
                                $thead .= $deptName . ' ';
                                if (isset($param['has_depts']) && $param['has_depts'] == 1) {
                                    $thead .= '('.trans('charge.subdivision').') ';
                                }
                                $deptAlarmMode  = $lists['set_list']['dept_type'] ?? trans("charge.not_set");
                                $deptAlarmValue = $lists['set_list']['dept_values'] ?? trans("charge.not_set");
                                $thead .= trans("charge.department") . trans("charge.method") . ':' . $deptAlarmMode . ' ';
                                $thead .= trans("charge.department") . trans("charge.value") . ':' . $deptAlarmValue . ' ';
                            }
                        }
                    }
                    break;
                case 5:
                    if (isset($param['set_id']) && !empty($param['set_id'])) {
                        $param['set_id'] = json_decode($param['set_id']);
                        if (count($param['set_id']) == 1 && isset($param['set_id'][0])) {
                            $project     = app($this->projectManagerRepository)->getDetail($param['set_id'][0]);
                            $projectName = $project->manager_name ?? '';
                            $projectNumber = $project->manager_number ?? '';
                            $thead .= $projectName .' ' .$projectNumber .' ';

                            $projectAlarmValue = $lists['set_list']['project_value'] ?? trans("charge.value");
                            $thead .= trans("charge.value") . ':' . $projectAlarmValue . ' ';
                        }
                    } else {
                        $thead .= trans("charge.project_cost_lists") . ' ';
                    }
                    break;
                default:
                    break;
            }
            if ((isset($param['flag']) && $param['flag'] == 'my' && $param['set_type'] != 5) || $param['set_type'] == 1) {
                $companyAlarmMode  = $lists['set_list']['company_type'] ?? trans("charge.not_set");
                $companyAlarmValue = $lists['set_list']['company_value'] ?? trans("charge.not_set");
                $thead .= trans("charge.company") . trans("charge.method") . ':' . $companyAlarmMode . ' ';
                $thead .= trans("charge.company") . trans("charge.value") . ':' . $companyAlarmValue . ' ';
            }
        } else {
            if (isset($param['flag']) && $param['flag'] == 'my') {
                $companyAlarmMode  = $lists['set_list']['company_type'] ?? trans("charge.not_set");
                $companyAlarmValue = $lists['set_list']['company_value'] ?? trans("charge.not_set");
                $thead .= trans("charge.company") . trans("charge.method") . ':' . $companyAlarmMode . ' ';
                $thead .= trans("charge.company") . trans("charge.value") . ':' . $companyAlarmValue . ' ';
            }
        }
        $thead .= '</tr>';

        $tbody = '';
        $filter = $param['filter'] ?? 'year';
        
        $typeList = $lists['detail_list']['type_list'];
        $chargeList = $lists['detail_list']['charge_list'];
        $chargeTotal = $lists['detail_list']['charge_total'];

        // 第二行时间维度
        $tbody .= '<tr><td colspan="' . $lists['max_level'] . '">' . trans('charge.subject') . '</td>';
        if ($filter == 'year') {
            $monthArray = [
                trans("charge.jan"),
                trans("charge.feb"),
                trans("charge.mar"),
                trans("charge.apr"),
                trans("charge.may"),
                trans("charge.june"),
                trans("charge.july"),
                trans("charge.aug"),
                trans("charge.sept"),
                trans("charge.oct"),
                trans("charge.nov"),
                trans("charge.dec"),
            ];
            foreach ($monthArray as $key => $value) {
                $tbody .= '<td>' . $value . '</td>';
            }
        } elseif ($filter == 'quarter') {
            $quarterArr = [
                trans("charge.q1"),
                trans("charge.q2"),
                trans("charge.q3"),
                trans("charge.q4"),
            ];
            foreach ($quarterArr as $key => $value) {
                $tbody .= '<td>' . $value . '</td>';
            }
        } elseif ($filter == 'month') {
            $headers = [];
            $startDay = $endDay = '';
            $startDay = $lists['monthCount'][0];
            foreach ($lists['monthCount'] as $value) {
                if (!in_array($value, $lists['monthCombile'])) {
                    if ($startDay == '') {
                        $startDay = $value;
                    }
                    $endDay = $value;
                } else {
                    if ($endDay) {
                        if ($startDay != $endDay) {
                            $headers[] = ['name' => $startDay . '~' . $endDay];
                            $tbody .= '<td>' . $startDay . '~' . $endDay . '</td>';
                        } else {
                            $headers[] = ['name' => $endDay];
                            $tbody .= '<td>' . $endDay . '</td>';
                        }
                    }
                    $headers[] = ['name' => $value, 'hasData' => 1];
                    $tbody .= '<td>' . $value . '</td>';
                    $startDay = $endDay = '';
                }
            }
            if ($startDay != $endDay) {
                $headers[] = ['name' => $startDay . '~' . $endDay];
                $tbody .= '<td>' . $startDay . '~' . $endDay . '</td>';
            } else {
                $headers[] = ['name' => $endDay];
                $tbody .= '<td>' . $endDay . '</td>';
            }
        }
        $tbody .= '<td colspan="' . $lists['max_level'] . '">' . trans('charge.total') . '</td></tr>';
        // 第三行开始按时间统计
        foreach ($typeList as $key => $value) {
            $tr = '<tr>';
            // 费用类型
            foreach ($value as $v) {
                $tr .= '<td colspan="' . $v['colspan'] . '" rowspan="' . $v['rowspan'] . '">' . $v['charge_type_name'] . '</td>';
            }
            // 费用按时间统计值
            if ($filter == 'year' || $filter == 'quarter') {
                foreach ($chargeList[$key] as $k => $v) {
                    if ($k != 'charge_type_id') {
                        $temp = $v ?? '';
                        // $tr .= '<td style="mso-number-format:\'\\@\';"> ' . $temp . ' </td>';
                        $tr .= '<td> ' . $temp . ' </td>';
                    }
                }
            } elseif ($filter == 'month') {
                if (!empty($headers)) {
                    foreach ($headers as $header) {
                        if (isset($header['hasData']) && $chargeList[$key][$header['name']] != 0) {
                            $tr .= '<td> ' . $chargeList[$key][$header['name']] . ' </td>';
                        } else {
                            $tr .= '<td></td>';
                        }
                    }
                }
            }
            // 按科目合计
            foreach ($chargeTotal[$key] as $v) {
                $tr .= '<td colspan="' . $v['colspan'] . '" rowspan="' . $v['rowspan'] . '"> ' . $v['charge_total'] . ' </td>';
            }
            $tr .= '</td>';
            $tbody .= $tr;
        }
        // 最后一行合计
        $tbody .= '<tr><td colspan="' . $lists['max_level'] . '">' . trans('charge.total') . '</td>';
        if ($filter == 'year' || $filter == 'quarter') {
            foreach ($lists['detailStatis'] as $key => $value) {
                $temp = $value ?? '';
                $tbody .= '<td> ' . $temp . ' </td>';
            }
        } elseif ($filter == 'month') {
            foreach ($headers as $header) {
                if (isset($header['hasData'])) {
                    $cellValue = isset($lists['detailStatis'][$header['name']]) ? $lists['detailStatis'][$header['name']] : 0;
                    $tbody .= '<td> '.$cellValue.' </td>';
                } else {
                    $tbody .= '<td>0</td>';
                }
            }
        }
        
        $tbody .= '<td colspan="' . $lists['max_level'] . '"> ' . $lists['detailCount'] . ' </td></tr>';

        return [
            'export_title' => trans('charge.charge', [], $langType), 
            'export_data' => $style . '<table class="statistics-table">' . $thead . $tbody . '<table>'
        ];
    }

    private function getDeptSet($thead, $deptId, $flag = true)
    {
        if ($flag) {
            $deptAll  = app($this->departmentRepository)->getDetail($deptId);
            $deptName = $deptAll->dept_name;
            $thead .= $deptName . ' ';
        }

        $deptSet = $this->deptAlarmModel($deptId);

        $deptAlarmMode  = $deptSet['deptAlarmMode'] == '' ? trans("charge.not_set") : $deptSet['deptAlarmMode'];
        $deptAlarmValue = $deptSet['deptAlarmMode'] == '' ? trans("charge.not_set") : $deptSet['deptAlarmValue'];
        $thead .= trans("charge.department") . trans("charge.method") . ':' . $deptAlarmMode . ' ';
        $thead .= trans("charge.department") . trans("charge.value") . ':' . $deptAlarmValue . ' ';
        return $thead;
    }

    //费用统计
    public function chargeStatisticsExport($param)
    {
        $langType = isset($param['lang_type']) ? $param['lang_type'] : null;
        $own      = $param['user_info']['user_info'];
        $header   = [
            'name'        => '',
            'method'      => trans('charge.method', [], $langType),
            'value'       => trans('charge.value', [], $langType),
            'date'        => trans('charge.date', [], $langType),
            'undertake'   => trans('charge.undertake', [], $langType),
            'noundertake' => trans('charge.noundertake', [], $langType),
            'total'       => trans('charge.total', [], $langType),
        ];
        if (isset($param['type']) && $param['type'] == 'user') {
            $header['name'] = trans('charge.reimburser', [], $langType);
        } elseif (isset($param['type']) && $param['type'] == 'dept') {
            $header['name'] = trans('charge.department_name', [], $langType);
        } elseif (isset($param['type']) && $param['type'] == 'company') {
            $header['name'] = trans('charge.charge_undertaker', [], $langType);
        } elseif (isset($param['type']) && $param['type'] == 'project') {
            $header['name'] = trans('system.projectName', [], $langType);
            unset($header['method']);
            unset($header['date']);
        }

        if (isset($param['search'])) {
            $param = $this->parseParams($param);
        }

        $reportData = $this->chargeStatistics($param, $own)['list'];

        $data = [];
        foreach ($reportData as $value) {
            $data[] = Arr::dot($value);
        }

        return compact('header', 'data');
    }

    //费用统计详情列表明细
    public function chargeDetailsExport($param)
    {
        $langType = isset($param['lang_type']) ? $param['lang_type'] : null;
        if (isset($param['date']) && !empty($param['date'])) {
            $header = [
                'creator_name'      => ['data' => trans('charge.reimburser', [], $langType), 'style' => ['width' => 15]],
                'charge_type_name'  => ['data' => trans('charge.subject', [], $langType), 'style' => ['width' => 30]],
                'type_value'        => ['data' => trans('charge.type_value', [], $langType), 'style' => ['width' => 15]],
                'charge_cost'       => ['data' => trans('charge.charge_value', [], $langType), 'style' => ['width' => 15]],
                'noundertake'       => ['data' => trans('charge.subject_noundertake', [], $langType), 'style' => ['width' => 15]],
                'payment_date'      => ['data' => trans('charge.payment_date', [], $langType), 'style' => ['width' => 15]],
                'charge_undertaker' => ['data' => trans('charge.charge_undertaker', [], $langType), 'style' => ['width' => 20]],
                'charge_form'       => ['data' => trans('charge.charge_from', [], $langType), 'style' => ['width' => 15]],
            ];
        } else {
            $header = [
                'creator_name'      => ['data' => trans('charge.reimburser', [], $langType), 'style' => ['width' => 15]],
                'charge_type_name'  => ['data' => trans('charge.subject', [], $langType), 'style' => ['width' => 30]],
                'charge_cost'       => ['data' => trans('charge.charge_value', [], $langType), 'style' => ['width' => 15]],
                'payment_date'      => ['data' => trans('charge.payment_date', [], $langType), 'style' => ['width' => 15]],
                'charge_undertaker' => ['data' => trans('charge.charge_undertaker', [], $langType), 'style' => ['width' => 20]],
                'charge_form'       => ['data' => trans('charge.charge_from', [], $langType), 'style' => ['width' => 15]],
            ];
        }

        if (isset($param['search'])) {
            $param['search'] = json_encode($param['search']);
        }
        $own        = $param['user_info']['user_info'];
        $reportData = $this->chargeDetails($param, $own)['list'];
        $projectArr = $this->getProjectArray();
        $data = [];
        // 所有科目路径
        $allType = $this->getChargeTypeFullPath(true);

        foreach ($reportData as $value) {
            if (empty($value['noundertake'])) {
                $value['noundertake'] = 0.00;
            }
            $value['type_value']        = empty($value['value']) ? 0.00 : $this->getChargeTypeValueStr($value['charge_undertaker'], $langType, $value);
            $projectName = '';
            if ($value['charge_undertaker'] == 4) {
                $projectName = $projectArr[$value['project_id']] ?? '';
            }
            $value['charge_undertaker'] = $this->getChargeUndertaker($value['charge_undertaker'], $langType, $value);
            if (!empty($projectName)) {
                $value['charge_undertaker'] .= '： ' . $projectName;
            }
            $value['charge_cost']       = number_format($value['charge_cost'], 2, '.', '');
            $value['charge_form']       = $value['charge_form'] == '1' ? trans('charge.add_charge', [], $langType) : trans('charge.outsend_flow', [], $langType);
            $value['charge_type_name']  = isset($allType[$value['charge_type_id']]) ? $allType[$value['charge_type_id']] : $value['charge_type_name'];
            $data[]                     = Arr::dot($value);
        }

        return compact('header', 'data');
    }

    private function getChargeTypeValueStr($undertaker, $langType, $value)
    {
        $result = '';
        switch ($undertaker) {
            case '1':
                $result = trans('charge.user', [], $langType) . '：' . $value['value'];
                break;
            case '2':
                $result = trans('charge.department', [], $langType) . "：" . $value['value'];
                break;
            case '3':
                $result = trans('charge.company', [], $langType) . "：" . $value['value'];
                break;
            case '4':
                $result = trans('portal.project', [], $langType) . "：" . $value['value'];
                break;
            default:
                break;
        }
        return $result;
    }

    public function createFile($html)
    {

        $tempDir = getAttachmentDir();

        $html_file = $tempDir . time() . rand(1000, 9999) . ".xls";

        $tpl_file = $this->defindTemplate();
        $content  = str_replace("{content}", $html, $tpl_file);
        $fp       = fopen($html_file, "w");
        fwrite($fp, $content);
        fclose($fp);

        return $html_file;
    }

    public function defindTemplate()
    {
        $tpl = <<<EOF
<html xmlns:o="urn:schemas-microsoft-com:office:office"
      xmlns:x="urn:schemas-microsoft-com:office:excel"
      xmlns="http://www.w3.org/TR/REC-html40">
        <head>
            <meta http-equiv="Content-type" content="text/html;charset=UTF-8" />
        </head>
        <body>
            <div id="Classeur1_16681" align=center x:publishsource="Excel">
                <table class="eui-table eui-table-bordered">
                {content}
                </table>
             </div>
        </body>
</html>
EOF;

        return $tpl;
    }

    /**
     * 获取费用其他数据
     */
    public function getOtherData($type, $id, $method, $value, $startDate, $endDate, $chargeTypeId = "")
    {
        //user WV00000005 year 1000.00  0000-00-00  0000-00-00
        $tempValue  = $value;
        $year       = date("Y", time());
        $monthArray = [
            trans("charge.jan"),
            trans("charge.feb"),
            trans("charge.mar"),
            trans("charge.apr"),
            trans("charge.may"),
            trans("charge.june"),
            trans("charge.july"),
            trans("charge.aug"),
            trans("charge.sept"),
            trans("charge.oct"),
            trans("charge.nov"),
            trans("charge.dec"),
        ];
        $quarterArr = [
            trans("charge.q1"),
            trans("charge.q2"),
            trans("charge.q3"),
            trans("charge.q4"),
        ];
        switch ($method) {
            case 'year':
                $methodName = trans('common.year');
                $date       = $year . trans("charge.year");
                //部门报销了多少

                $where1 = [
                    "id"           => $id,
                    "payment_date" => $year,
                    'type'         => $type,
                    'filter'       => "year",
                ];
                if ($chargeTypeId != "") {
                    $where1['charge_type_id'] = $chargeTypeId;
                }
                $haveUnderTake = app($this->chargeListRepository)->getChargeCost($where1);

                $noUnderTake = $value - $haveUnderTake;
                $where2      = [
                    "id"     => $id,
                    'type'   => $type,
                    'filter' => "year",
                ];
                if ($chargeTypeId != "") {
                    $where2['charge_type_id'] = $chargeTypeId;
                }

                $totalUnderTake = app($this->chargeListRepository)->getChargeCost($where2);

                break;
            case 'quarter':
                $methodName = trans('common.quarter');

                $chargeMonth = ceil(date('m', time()) / 3);

                $minM = $chargeMonth * 3 - 2;
                $maxM = $chargeMonth * 3;

                $date       = $monthArray[$minM - 1] . "-" . $monthArray[$maxM - 1] . "（" . $quarterArr[$chargeMonth - 1] . "）";
                //部门报销了多少
                if ($minM < 10) {
                    $tempMinDate = $year . "-0" . $minM;
                } else {
                    $tempMinDate = $year . "-" . $minM;
                }

                if ($maxM < 10) {
                    $tempMaxDate = $year . "-0" . $maxM;
                } else {
                    $tempMaxDate = $year . "-" . $maxM;
                }

                $where1 = [
                    "id"           => $id,
                    "payment_date" => $tempMinDate . "|" . $tempMaxDate,
                    'type'         => $type,
                    'filter'       => $method,
                ];
                if ($chargeTypeId != "") {
                    $where1['charge_type_id'] = $chargeTypeId;
                }

                $haveUnderTake = app($this->chargeListRepository)->getChargeCost($where1);

                $noUnderTake = $value - $haveUnderTake;

                $where2 = [
                    "id"     => $id,
                    'type'   => $type,
                    'filter' => $method,
                ];
                if ($chargeTypeId != "") {
                    $where2['charge_type_id'] = $chargeTypeId;
                }

                $totalUnderTake = app($this->chargeListRepository)->getChargeCost($where2);

                break;
            case 'month':
                $methodName = trans('common.month');

                $month      = date("m", time());
                $date       = $year . trans("charge.year") . "-" . $month . trans("charge.month");

                $where1 = [
                    "id"           => $id,
                    "payment_date" => $year . "-" . $month,
                    'type'         => $type,
                    'filter'       => $method,
                ];
                if ($chargeTypeId != "") {
                    $where1['charge_type_id'] = $chargeTypeId;
                }

                $haveUnderTake = app($this->chargeListRepository)->getChargeCost($where1);
                $noUnderTake   = $value - $haveUnderTake;

                $where2 = [
                    "id"     => $id,
                    'type'   => $type,
                    'filter' => $method,
                ];
                if ($chargeTypeId != "") {
                    $where2['charge_type_id'] = $chargeTypeId;
                }

                $totalUnderTake = app($this->chargeListRepository)->getChargeCost($where2);

                break;
            case 'custom':

                $methodName = trans('charge.customize');
                $date       = $startDate . "~" . $endDate;

                $where1 = [
                    "id"           => $id,
                    "payment_date" => $startDate . "|" . $endDate,
                    'type'         => $type,
                    'filter'       => $method,
                ];
                if ($chargeTypeId != "") {
                    $where1['charge_type_id'] = $chargeTypeId;
                }

                $haveUnderTake = app($this->chargeListRepository)->getChargeCost($where1);
                $noUnderTake   = $value - $haveUnderTake;

                $where2 = [
                    "id"     => $id,
                    'type'   => $type,
                    'filter' => $method,
                ];
                if ($chargeTypeId != "") {
                    $where2['charge_type_id'] = $chargeTypeId;
                }

                $totalUnderTake = app($this->chargeListRepository)->getChargeCost($where2);

                break;
            default:
                $methodName = $value = $date = $haveUnderTake = $noUnderTake = $totalUnderTake = "";
                $where2     = [
                    "id"     => $id,
                    'type'   => $type,
                    'filter' => $method,
                ];
                if ($chargeTypeId != "") {
                    $where2['charge_type_id'] = $chargeTypeId;
                }

                $totalUnderTake = app($this->chargeListRepository)->getChargeCost($where2);
                break;
        }

        if ($type == "project") {
            $value      = $tempValue;
            $methodName = $date = '';
            $where1     = [
                'id'     => $id,
                'type'   => $type,
                'filter' => '',
            ];
            if ($chargeTypeId != "") {
                $where1['charge_type_id'] = $chargeTypeId;
            }
            $totalUnderTake = $haveUnderTake = app($this->chargeListRepository)->getChargeCost($where1);
            $noUnderTake    = (float) $value - $haveUnderTake;
        }

        return [
            'method'      => $methodName,
            'value'       => $value,
            'date'        => $date,
            'undertake'   => $haveUnderTake,
            'noundertake' => number_format((float) $noUnderTake, 2, ".", ""),
            'total'       => $totalUnderTake
        ];
    }

    /**
     * 费用统计 根据type进行检索
     */
    public function chargeStatistics($data, $own)
    {
        $data     = $this->parseParams($data);
        $type     = isset($data["type"]) ? $data["type"] : "dept";
        $viewUser = $this->getViewUser($own);

        if ($type == "company") {
            $chargeStatisticsData = [];
            if (empty($viewUser)) {
                $chargeStatisticsData = ['list' => [], 'total' => 0];
            } else {
                $where                        = ["set_type" => [1]];
                $chargeStatisticsData['list'] = app($this->chargeSettingRepository)->getDataBywhere($where);
                if (empty($chargeStatisticsData['list'])) {
                    $chargeStatisticsData['list'] = [
                        [
                            'charge_setting_id' => '',
                            'alert_method'      => '',
                            'alert_value'       => 0,
                            'alert_data_start'  => "0000-00-00",
                            'alert_data_end'    => "0000-00-00",
                            'subject_check'     => 0,
                            'set_type'          => 1,
                        ],
                    ];
                }
                $chargeStatisticsData['total'] = 1;
            }
        } elseif ($type == "user") {
            if ($viewUser != 'all') {
                if (isset($data['search']['user.user_id']) && !empty($data['search']['user.user_id'])) {
                    if (!array_intersect($data['search']['user.user_id'], $viewUser)) {
                        return ['list' => [], 'total' => 0];
                    }
                } else {
                    $data['search']['user.user_id'] = [$viewUser, 'in'];
                }
            }
            $chargeStatisticsData = $this->response(app($this->userRepository), 'userChargeStatisticsTotal', 'userChargeStatistics', $data);
        } elseif ($type == "project") {
            $projectIds = ProjectService::thirdMineProjectId($own);
            $data['projects'] = $projectIds;
            $chargeStatisticsData = $this->response(app($this->projectManagerRepository), 'proejctChargeStatisticsTotal', 'projectChargeStatistics', $data);
        } else {
            if ($viewUser != 'all') {
                $param = [
                    'fields' => ['dept_id'],
                    'include_leave' => true,
                    'search' => [
                        'user_id' => [$viewUser, 'in'],
                    ],
                ];
                $depts   = app($this->userRepository)->getUserDeptName($param)->pluck('userHasOneSystemInfo')->toArray();
                $deptIds = array_unique(array_column($depts, 'dept_id'));
                if (isset($data['search']['department.dept_id']) && !empty($data['search']['department.dept_id'])) {
                    if (!array_intersect($data['search']['department.dept_id'], $deptIds)) {
                        return ['list' => [], 'total' => 0];
                    }
                } else {
                    $data['search']['department.dept_id'] = [$deptIds, 'in'];
                }
            }

            if (empty($viewUser)) {
                $chargeStatisticsData = ['list' => [], 'total' => 0];
            } else {
                $chargeStatisticsData = $this->response(app($this->departmentRepository), 'deptChargeStatisticsTotal', 'deptChargeStatistics', $data);
            }
        }

        $result = [];
        $temp   = [];
        foreach ($chargeStatisticsData['list'] as $chargeStatistics) {
            $temp['id']   = isset($chargeStatistics['id']) ? $chargeStatistics['id'] : "";
            $temp['name'] = isset($chargeStatistics['name']) ? $chargeStatistics['name'] : "";
            if ($chargeStatistics['set_type'] == 1) {
                $temp['name'] = trans("charge.company");
            }

            $method    = $chargeStatistics["alert_method"];
            $startDate = $chargeStatistics["alert_data_start"];
            $endDate   = $chargeStatistics["alert_data_end"];
            $value     = $chargeStatistics["alert_value"];

            $otherData = $this->getOtherData($type, $temp['id'], $method, $value, $startDate, $endDate, '', []);

            foreach ($otherData as $key => $val) {
                $temp[$key] = $val;
            }

            $temp['type']              = $type;
            $temp['subject_check']     = $chargeStatistics['subject_check'];
            $temp['charge_setting_id'] = $chargeStatistics['charge_setting_id'] ?? '';
            array_push($result, $temp);
        }

        return [
            "total" => $chargeStatisticsData['total'],
            "list"  => $result,
        ];
    }

    //根据用户获取用户的相关配置信息
    public function chargeDataSource($user_id, $data = null)
    {
        if (!$user_id) {
            return ['code' => ['0x020009', 'charge']];
        }
        $content = "";

        $userAll = app($this->userRepository)->getUserAllData($user_id);

        if (!$userAll) {
            return ['code' => ['0x020010', 'charge']];
        }
        $userInfos = $userAll->toArray();

        $dept_id = $userInfos['user_has_one_system_info']['dept_id'];

        $deptAlarmModel = $this->deptAlarmModel($dept_id, "user");
        $userAlarmModel = $this->userAlarmModel($user_id);

        return array_merge($deptAlarmModel, $userAlarmModel);
    }

    public function chargeDataSourceByCompany()
    {
        $result = [
            "companyAlarmMode"  => "",
            "companyAlarmValue" => "0.00",
        ];

        $row = app($this->chargeSettingRepository)->getDataByWhere(["set_type" => [1]]);

        if (isset($row[0]) && $row[0]) {
            if ($row[0]["alert_method"] == 'custom') {
                $tempWhere = [
                    'set_type'         => [1],
                    'alert_data_start' => [date('Y-m-d'), '<='],
                    'alert_data_end'   => [date('Y-m-d'), '>='],
                ];
                $record = app($this->chargeSettingRepository)->getSettingByWhere($tempWhere, false);
                if (empty($record)) {
                    $result["companyReportAmount"] = app($this->chargeListRepository)->getReportAmount(["alert_method" => ""], ["charge_undertaker" => [3]]);
                } else {
                    $temp = [
                        "alert_method"     => $record->alert_method,
                        "alert_data_start" => $record->alert_data_start,
                        "alert_data_end"   => $record->alert_data_end,
                    ];
                    $result = [
                        "companyAlarmMode"    => $this->chargeTypeReplace($temp),
                        "companyAlarmValue"   => isset($record->alert_value) ? $record->alert_value : "0.00",
                        "companyReportAmount" => app($this->chargeListRepository)->getReportAmount(["alert_method" => ""], ["charge_undertaker" => [3]]),
                    ];
                }
            } else {
                $temp = [
                    "alert_method"     => $row[0]["alert_method"],
                    "alert_data_start" => $row[0]["alert_data_start"],
                    "alert_data_end"   => $row[0]["alert_data_end"],
                ];
                $result = [
                    "companyAlarmMode"    => $this->chargeTypeReplace($temp),
                    "companyAlarmValue"   => isset($row[0]["alert_value"]) ? $row[0]["alert_value"] : "0.00",
                    "companyReportAmount" => app($this->chargeListRepository)->getReportAmount(["alert_method" => ""], ["charge_undertaker" => [3]]),
                ];
            }
        } else {
            // 已报销总额
            $result["companyReportAmount"] = app($this->chargeListRepository)->getReportAmount(["alert_method" => ""], ["charge_undertaker" => [3]]);
        }

        return $result;
    }

    private function deptAlarmModel($dept_id, $flag = "")
    {

        $deptAlarmMode  = "";
        $deptAlarmValue = "0.00";
        $row            = app($this->chargeSettingRepository)->getDataByWhere(["dept_id" => [$dept_id], "user_id" => [""]]);
        if (isset($row[0]) && $row[0]) {
            if ($row[0]["alert_method"] == 'custom') {
                $tempWhere = [
                    'dept_id'          => [$dept_id],
                    'alert_data_start' => [date('Y-m-d'), '<='],
                    'alert_data_end'   => [date('Y-m-d'), '>='],
                ];
                $record = app($this->chargeSettingRepository)->getSettingByWhere($tempWhere, false);
                if (empty($record)) {
                    $deptReportAmount = app($this->chargeListRepository)->getReportAmount(["alert_method" => ""], ["undertake_dept" => [$dept_id], "charge_undertaker" => [2]]);
                } else {
                    $temp = [
                        "alert_method"     => $record->alert_method,
                        "alert_data_start" => $record->alert_data_start,
                        "alert_data_end"   => $record->alert_data_end,
                    ];
                    $deptAlarmMode    = $this->chargeTypeReplace($temp);
                    $deptAlarmValue   = isset($record->alert_value) ? $record->alert_value : "0.00";
                    $deptReportAmount = app($this->chargeListRepository)->getReportAmount(["alert_method" => ""], [
                        "undertake_dept" => [$dept_id], "charge_undertaker" => [2],
                    ]);
                }
            } else {
                $temp = [
                    "alert_method"     => $row[0]["alert_method"],
                    "alert_data_start" => $row[0]["alert_data_start"],
                    "alert_data_end"   => $row[0]["alert_data_end"],
                ];
                $deptAlarmMode    = $this->chargeTypeReplace($temp);
                $deptAlarmValue   = isset($row[0]["alert_value"]) ? $row[0]["alert_value"] : "0.00";
                $deptReportAmount = app($this->chargeListRepository)->getReportAmount(["alert_method" => ""], ["undertake_dept" => [$dept_id], "charge_undertaker" => [2]]);
            }
        } else {
            $deptReportAmount = app($this->chargeListRepository)->getReportAmount(["alert_method" => ""], ["undertake_dept" => [$dept_id], "charge_undertaker" => [2]]);
        }

        if ($flag == "user") {
            $result = [
                "userDeptAlarmMode"    => $deptAlarmMode,
                "userDeptAlarmValue"   => $deptAlarmValue,
                "userDeptReportAmount" => $deptReportAmount,
            ];
        } else {
            $result = [
                "deptAlarmMode"    => $deptAlarmMode,
                "deptAlarmValue"   => $deptAlarmValue,
                "deptReportAmount" => $deptReportAmount,
            ];
        }

        return $result;
    }

    private function userAlarmModel($user_id)
    {

        $result = [
            "userAlarmMode"  => "",
            "userAlarmValue" => "0.00",
        ];

        $row = app($this->chargeSettingRepository)->getDataByWhere(["user_id" => [$user_id]]);

        if (isset($row[0]) && $row[0]) {
            if ($row[0]["alert_method"] == 'custom') {
                $tempWhere = [
                    "user_id"          => [$user_id],
                    'alert_data_start' => [date('Y-m-d'), '<='],
                    'alert_data_end'   => [date('Y-m-d'), '>='],
                ];
                $record = app($this->chargeSettingRepository)->getSettingByWhere($tempWhere, false);
                if (empty($record)) {
                    $result["userReportAmount"] = app($this->chargeListRepository)->getReportAmount(["alert_method" => ""], ["undertake_user" => [$user_id], "charge_undertaker" => [1]]);
                } else {
                    $temp = [
                        "alert_method"     => $record->alert_method,
                        "alert_data_start" => $record->alert_data_start,
                        "alert_data_end"   => $record->alert_data_end,
                    ];
                    $result = [
                        "userAlarmMode"    => $this->chargeTypeReplace($temp),
                        "userAlarmValue"   => isset($record->alert_value) ? $record->alert_value : "0.00",
                        "userReportAmount" => app($this->chargeListRepository)->getReportAmount(["alert_method" => ""], ["undertake_user" => [$user_id], "charge_undertaker" => [1]]),
                    ];
                }
            } else {
                $temp = [
                    "alert_method"     => $row[0]["alert_method"],
                    "alert_data_start" => $row[0]["alert_data_start"],
                    "alert_data_end"   => $row[0]["alert_data_end"],
                ];
                $result = [
                    "userAlarmMode"    => $this->chargeTypeReplace($temp),
                    "userAlarmValue"   => isset($row[0]["alert_value"]) ? $row[0]["alert_value"] : "0.00",
                    "userReportAmount" => app($this->chargeListRepository)->getReportAmount(["alert_method" => ""], ["undertake_user" => [$user_id], "charge_undertaker" => [1]]),
                ];
            }
        } else {
            $result["userReportAmount"] = app($this->chargeListRepository)->getReportAmount(["alert_method" => ""], ["undertake_user" => [$user_id], "charge_undertaker" => [1]]);
        }

        return $result;
    }

    //根据部门获取当前配置信息
    public function chargeDataSourceByDeptId($dept_id, $data = null)
    {

        if (!$dept_id) {
            return ['code' => ['0x020011', 'charge']];
        }

        return $this->deptAlarmModel($dept_id);
    }

    //通过主科目名称获取次科目集合
    public function chargeDataSourceByChargeName($data)
    {
        $data = $this->parseParams($data);
        $pid  = isset($data["parent_id"]) ? $data["parent_id"] : "";

        if ($pid == "{parent_id}" || $pid == "") {
            return [];
        }

        $param = [
            'fields' => ["charge_type_id", "charge_type_name"],
            'search' => ["charge_type_parent" => [$pid]],
        ];
        if (isset($data['search']['charge_type_name'])) {
            $param['search']['charge_type_name'] = $data['search']['charge_type_name'];
        }
        if (isset($data['search']['charge_type_id'])) {
            $param['search']['charge_type_id'] = $data['search']['charge_type_id'];
        }
        if(!isset($param['is_order'])){
            $param['is_order'] = 1;
        }

        return $this->response(app($this->chargeTypeRepository), 'getChargeTypeListTotal', 'getChargeTypeList', $param);
    }

    public function chargeTypeReplace($row)
    {
        $content = "";
        switch ($row["alert_method"]) {
            case 'year':
                $content = trans('common.year');
                break;
            case 'quarter':
                $content = trans('common.quarter');
                break;
            case 'month':
                $content = trans('common.month');
                break;
            case 'custom':
                $content = $row['alert_data_start'] . '/' . $row['alert_data_end'];
                break;
            default:
                $content = '';
                break;
        }

        return $content;
    }

    public function chargeConfig($charge)
    {
        $type   = trans('charge.not_set');
        $values = 0;

        switch ($charge["alert_method"]) {
            case 'year':
                $type   = trans('common.year');
                $values = $charge["alert_value"];
                break;
            case 'quarter':
                $type   = trans('common.quarter');
                $values = $charge["alert_value"];
                break;
            case 'custom':
                $type   = trans('common.period') . " " . $charge["alert_data_start"] . "-" . $charge["alert_data_end"];
                $values = $charge["alert_value"];
                break;
            case 'month':
                $type   = trans('common.month');
                $values = $charge["alert_value"];
                break;
            default:
                $type   = isset($charge['set_type']) && $charge['set_type'] == 5 ? '' : trans('charge.not_set');
                $values = $charge["alert_value"] ?? 0;
                break;
        }

        return [
            "type"   => $type,
            "values" => $values,
        ];
    }
    // 移动端我的费用页面各预警值展示
    public function chargeAppAlert($data) {
        $setType = $data["setType"] ?? '';
        $setId = isset($data["setId"]) && !empty($data["setId"]) ? json_decode($data["setId"], true) : [];
        if (empty($setId)) {
            return [];
        }
        $subjectCheck = 0;
        $subjectWhere = [];

        return $this->getChargeAlert($setId, $setType, $subjectCheck, $subjectWhere);
    }
    // 移动端我的费用页面费用树
    public function chargeAppTree($data, $parentId, $own) {
        $setType = isset($data["setType"]) ? $data["setType"] : '';
        $setId = isset($data["setId"]) && !empty($data["setId"]) ? json_decode($data["setId"], true) : [];
        if (empty($setId)) {
            return [];
        }
        $viewUser = $this->getViewUser($own);
        $queryUser = $this->getQueryUser($setType, $setId, 0);

        $projectIds = ProjectService::thirdMineProjectId($own);

        $chargeWhere = [
            "year"       => $data["year"] ?? "",
            "filter"     => $data["filter"] ?? "",
            "users"      => $queryUser,
            "month"      => $data["month"] ?? "",
            "quarter"    => $data["quarter"] ?? "",
            "day"        => $data["day"] ?? "",
            "type"       => $setType,
            "set_id"     => $setId[0],
            "projects"   => $projectIds,
            "power"      => $data['power'] ?? 0,
            "filter_type" => $data['filter_type'] ?? 1,
        ];
        if ($setType == 5) {
            $chargeWhere['project_id'] = ['=', $setId[0]];
        }else{
            $chargeWhere['viewUser'] = $viewUser;
        }

        $chargeTypes = [];

        $type = app($this->chargeTypeRepository)->getChargeTypeChildren($parentId);
        if (!$type->isEmpty()) {
            foreach ($type as $key => $value) {
                $chargeWhere['chargeTypeID'] = $value->charge_type_id;
                if ($value->has_children == 1) {
                    $chargeWhere['has_children'] = 1;
                }
                $value->charge_cost = (float) app($this->chargeListRepository)->getChargeTotal($chargeWhere);
                $chargeTypes[] = $value;
            }
        }

        return $chargeTypes;
    }

    public function chargeAppTotal($data, $own) {
        $setType = isset($data["setType"]) ? $data["setType"] : '';
        $setId = isset($data["setId"]) && !empty($data["setId"]) ? json_decode($data["setId"], true) : [];
        if (empty($setId)) {
            return [];
        }
        $viewUser = $this->getViewUser($own);
        $queryUser = $this->getQueryUser($setType, $setId, 0);

        $projectIds = ProjectService::thirdMineProjectId($own);

        $chargeWhere = [
            "year"       => $data["year"] ?? "",
            "filter"     => $data["filter"] ?? "",
            "users"      => $queryUser,
            "month"      => $data["month"] ?? "",
            "quarter"    => $data["quarter"] ?? "",
            "day"        => $data["day"] ?? "",
            "type"       => $setType,
            "set_id"     => $setId[0],
            "projects"   => $projectIds,
            "power"      => $data['power'] ?? 0,
            "filter_type" => $data['filter_type'] ?? 1,
        ];
        if ($setType == 5) {
            $chargeWhere['project_id'] = ['=', $setId[0]];
        }else{
            $chargeWhere['viewUser'] = $viewUser;
        }

        $total = 0;
        $type = app($this->chargeTypeRepository)->getChargeTypeChildren(0);
        if (!$type->isEmpty()) {
            foreach ($type as $key => $value) {
                $chargeWhere['chargeTypeID'] = $value->charge_type_id;
                if ($value->has_children == 1) {
                    $chargeWhere['has_children'] = 1;
                }
                $cost = (float) app($this->chargeListRepository)->getChargeTotal($chargeWhere);
                $total += $cost;
            }
        }

        return $total;
    }

    public function getChargeSubType()
    {
        $where = [
            'search' => [
                "charge_type_parent" => [[0], ">"],
            ],
            'is_charge_list' => 1
        ];
        return app($this->chargeTypeRepository)->chargeTypeList($where);
    }

    //外发
    public function flowOutSendToCharge($data)
    {
        $user = app($this->userRepository)->getAllUserIdString();
        $user = explode(',', $user);
        if (!(isset($data["user_id"]) || isset($data["current_user_id"]))) {
            return ['code' => ['0x020015', 'charge']];
        }
        if (empty($data["user_id"])) {
            return ['code' => ['0x020012', 'charge']];
        }
        if (isset($data["current_user_id"]) && !in_array($data["current_user_id"], $user)) {
            return ['code' => ['0x020014', 'charge']];
        }
        if (!isset($data["charge_type_id"])) {
            return ['code' => ['0x020021', 'charge']];
        }
        if (empty($data["charge_type_id"])) {
            return ['code' => ['0x020022', 'charge']];
        }
        $typeInfo = app($this->chargeTypeRepository)->getChargeTypeInfo($data["charge_type_id"]);
        if (empty($typeInfo) || !isset($typeInfo->charge_type_id)) {
            return ['code' => ['0x020022', 'charge']];
        }
        if ($typeInfo->has_children != 0) {
            return ['code' => ['0x020040', 'charge']];
        }
        if (!isset($data["reason"]) || empty($data["reason"])) {
            return ['code' => ['0x020013', 'charge']];
        }
        if (!isset($data['charge_undertaker'])) {
            return ['code' => ['0x020016', 'charge']];
        }
        if (empty($data['charge_undertaker'])) {
            return ['code' => ['0x020023', 'charge']];
        }
        if (!isset($data['charge_cost'])) {
            return ['code' => ['0x020025', 'charge']];
        }
        if (empty($data['charge_cost'])) {
            $data['charge_cost'] = 0;
        }
        if (!is_numeric($data['charge_cost'])) {
            return ['code' => ['0x020024', 'charge']];
        }
        $filter = [1, 2, 3, 4];
        if (!in_array($data['charge_undertaker'], $filter)) {
            return ['code' => ['0x020016', 'charge']];
        }
        // 外发数据
        $extra = $this->getExtraDataFromFlow($data);

        $insertData = [
            'user_id'              => $data['user_id'],// 报销人
            'charge_type_id'       => $data["charge_type_id"],
            'reason'               => $data["reason"],
            'payment_date'         => isset($data["payment_date"]) && !empty($data["payment_date"]) ? $data["payment_date"] : date("Y-m-d"),
            'charge_cost'          => $data["charge_cost"],
            'create_date'          => date("Y-m-d H:i:s"),
            'charge_form'          => 2,
            'charge_extra'         => $extra,
            'charge_undertaker'    => isset($data['charge_undertaker']) ? $data['charge_undertaker'] : 2,
            'undertake_user'       => $data['undertake_user'] ?? $data['user_id']
        ];
        // 外发到部门，没有设置费用承担部门时，默认承担者为报销人所在部门
        if ($insertData['charge_undertaker'] == 2) {
            if (isset($data['dept_id']) && !empty($data['dept_id'])) {
                $insertData['undertake_dept'] = $data['dept_id'];
            } else {
                $userData = app($this->userRepository)->getUserDeptIdAndRoleIdByUserId($data["user_id"]);
                $insertData["undertake_dept"] = $userData['dept_id'];
            }
        }

        if ($insertData['charge_undertaker'] == 4) {
            if (isset($data['project_id']) && !empty($data['project_id'])) {
                $insertData['project_id'] = $data['project_id'];
            } else {
                return ['code' => ['0x020018', 'charge']];
            }
        }

        $result = $this->addNewCharge($insertData);
        if(isset($result['code'])){
            return $result;
        }

        return [
            'status' => 1,
            'dataForLog' => [
                [
                    'table_to' => 'charge_list',
                    'field_to' => 'charge_list_id',
                    'id_to' => $result['charge_list_id']
                ]
            ]
        ];
    }
    // 费用外发更新
    public function flowOutSendToChargeUpdate($data) {
        if (empty($data)) {
            return ['code' => ['0x000003', 'common']];
        }
        $updateData = $data['data'] ?? [];
        unset($updateData['current_user_id']);
        if (!isset($data['unique_id']) || empty($data['unique_id'])) {
            return ['code' => ['0x000006', 'common']];
        }
        if (!app($this->chargeListRepository)->getTotal(['search' => ['charge_list_id' => [ $data['unique_id'] ] ]])) {
            return ['code' => ['0x000021', 'common']];
        }
        if (isset($updateData['user_id'])) {
            unset($updateData['user_id']);
        }
        if (isset($updateData['charge_type_id']) && empty($updateData['charge_type_id'])) {
            return ['code' => ['0x020036', 'charge']];
        }
        if (isset($updateData['charge_cost'])) {
            if (empty($updateData['charge_cost'])) {
                return ['code' => ['0x020025', 'charge']];
            }
            if (!is_numeric($updateData['charge_cost'])) {
                return ['code' => ['0x020024', 'charge']];
            }
        }
        if (isset($updateData["reason"]) && empty($updateData["reason"])) {
            return ['code' => ['0x020013', 'charge']];
        }
        
        if (isset($updateData['charge_undertaker'])) {
            if (empty($updateData['charge_undertaker'])) {
                return ['code' => ['0x020037', 'charge']];
            }
            switch($updateData['charge_undertaker']) {
                case 1:
                    if (isset($updateData['dept_id'])) unset($updateData['dept_id']);
                    if (isset($updateData['project_id'])) unset($updateData['project_id']);
                    if (!isset($updateData['undertake_user']) || empty($updateData['undertake_user'])) return ['code' => ['0x020038', 'charge']];
                    $result = app($this->userRepository)->judgeUserExists(['user_id' => [$updateData['undertake_user']]]);
                    if (!$result) {
                        return ['code' => ['0x020010', 'charge']];
                    }
                    break;
                case 2:
                    if (isset($updateData['undertake_user'])) unset($updateData['undertake_user']);
                    if (isset($updateData['project_id'])) unset($updateData['project_id']);
                    if (!isset($updateData['dept_id']) || empty($updateData['dept_id'])) return ['code' => ['0x020039', 'charge']];
                    break;
                case 3:
                    if (isset($updateData['undertake_user'])) unset($updateData['undertake_user']);
                    if (isset($updateData['project_id'])) unset($updateData['project_id']);
                    if (isset($updateData['dept_id'])) unset($updateData['dept_id']);
                    break;
                case 4:
                    if (isset($updateData['undertake_user'])) unset($updateData['undertake_user']);
                    if (isset($updateData['dept_id'])) unset($updateData['dept_id']);
                    if (!isset($updateData['project_id']) || empty($updateData['project_id'])) return ['code' => ['0x020047', 'charge']];
                    break;
                default:     
                    if (isset($updateData['undertake_user'])) unset($updateData['undertake_user']);
                    if (isset($updateData['project_id'])) unset($updateData['project_id']);
                    if (isset($updateData['dept_id'])) unset($updateData['dept_id']);
                    break;
            }
        } else {
            if (isset($updateData['undertake_user']) || isset($updateData['dept_id']) || isset($updateData['project_id'])) {
                // 请设置费用承担者类型（个人/部门/公司/项目）
                return ['code' => ['0x020042', 'charge']];
            }
        }
        
        $chargeInfo = app($this->chargeListRepository)->getDetail($data['unique_id']);
        foreach ($updateData as $key => $value) {
            if (isset($chargeInfo->{$key}) && empty($value)) {
                $updateData[$key] = $chargeInfo->{$key};
            }
        }

        if (isset($updateData['charge_undertaker']) && $updateData['charge_undertaker'] != 4) {
            $updateData['project_id'] = NULL;
        }
        
        $extra = $this->getExtraDataFromFlow($updateData);
        if (!empty($extra)) {
            $updateData['charge_extra'] = $extra;
        }

        if (isset($updateData['dept_id'])) {
            $updateData['undertake_dept'] = $updateData['dept_id'];
            unset($updateData['dept_id']);
        }
        // 报销人不能更新，与模块一致
        if (isset($updateData['user_id'])) {
            unset($updateData['user_id']);
        }
        app($this->chargeListRepository)->updateData($updateData, ["charge_list_id" => $data['unique_id']]);
        return [
            'status' => 1,
            'dataForLog' => [
                [
                    'table_to' => 'charge_list',
                    'field_to' => 'charge_list_id',
                    'id_to' => $data['unique_id']
                ]
            ]
        ];
    }
    public function flowOutSendToChargeDelete($data) {
        if (empty($data)) {
            return ['code' => ['0x000003', 'common']];
        }
        if (!isset($data['unique_id']) || empty($data['unique_id'])) {
            return ['code' => ['0x000006', 'common']];
        }
        if (!app($this->chargeListRepository)->getTotal(['search' => ['charge_list_id' => [ $data['unique_id'] ] ]])) {
            return ['code' => ['0x000021', 'common']];
        }
        $result = $this->deleteNewCharge($data['unique_id'], own(), true);
        if (isset($result['code'])) {
            return $result;
        }
        return [
            'status' => 1,
            'dataForLog' => [
                [
                    'table_to' => 'charge_list',
                    'field_to' => 'charge_list_id',
                    'id_to' => $data['unique_id']
                ]
            ]
        ];
    }
    // 费用预警外发
    public function chargeAlertFromFlow($data) {
        // 预警对象类型判断
        if (!isset($data['set_type'])) {
            return ['code' => ['0x020026', 'charge']];
        }
        if (empty($data['set_type'])) {
            return ['code' => ['0x020044', 'charge']];
        }
        if (!in_array($data['set_type'], [1,2,3,4,5])) {
            return ['code' => ['0x020027', 'charge']];
        }
        // 预警对象判断
        $object = [
            2 => ['dept_id', trans('charge.department')], 
            3 => ['user_id', trans('charge.user')], 
            4 => ['role_id', trans('charge.role')], 
            5 => ['project_id', trans('charge.project')]
        ];
        foreach ($object as $key => $value) {
            if ($data['set_type'] == $key) {
                if (!isset($data[$value[0]]) || empty($data[$value[0]])) {
                    return [
                        'code' => ['0x020028', 'charge'],
                        'dynamic' => trans('charge.not_set').$value[1]
                    ];
                }
            }
        }
        if (isset($data['alert_subject']) && !empty($data['alert_subject'])) {
            $typeInfo = app($this->chargeTypeRepository)->getChargeTypeInfo($data['alert_subject']);
            if (empty($typeInfo) || !isset($typeInfo->charge_type_id)) {
                return ['code' => ['0x020022', 'charge']];
            }
            if ($typeInfo->has_children != 0) {
                return ['code' => ['0x020040', 'charge']];
            }
        }
        if ($data['set_type'] != 5) {
            $data['project_id'] = null;

            if (!isset($data['alert_method'])) {
                return ['code' => ['0x020029', 'charge']];
            }
            if (empty($data['alert_method'])) {
                return ['code' => ['0x020043', 'charge']];
            }
            if (!in_array($data['alert_method'], [1,2,3,4])) {
                return ['code' => ['0x020030', 'charge']];
            }

            $method = [1 => 'year', 2 => 'quarter', 3 => 'month', 4 => 'custom'];
            $data['alert_method'] = $method[$data['alert_method']]  ?? '';
        } else {
            $data['alert_method'] = '';
        }
        
        if ($data['alert_method'] == 'custom') {
            $start = $data['alert_data_start'] ?? false;
            if (!$start) {
                return ['code' => ['0x020031', 'charge']];
            }
            $end = $data['alert_data_end'] ?? false;
            if (!$end) {
                return ['code' => ['0x020032', 'charge']];
            }
        }
        
        if (!isset($data['alert_value'])) {
            return ['code' => ['0x020033', 'charge']];
        }
//        if (isset($data['alert_value']) && ($data['alert_value'] < 0)) {
//            return ['code' => ['0x020048', 'charge']];
//        }
        
        $subjectCheck = $data['subject_check'] ?? 0;
        
        $tempWhere = [
            'set_type' => [$data['set_type']]
        ];
        if ($data['alert_method'] == 'custom' && $data['set_type'] != 5) {
            $tempWhere['alert_data_start'] = [$data['alert_data_start']];
            $tempWhere['alert_data_end'] = [$data['alert_data_end']];
        }
        $field = $object[$data['set_type']][0] ?? '';
        if ($data['set_type'] != 1 && $data['set_type'] != 4 && !empty($field)) {
            $tempWhere[$field] = [$data[$field]];
        }

        $result = ['charge_setting_id' => []];
        if ($data['set_type'] != 4) {
            $data = $this->handleAlertData($data, $tempWhere);
            if (array_key_exists('project_id', $data) && empty($data['project_id'])) {
                unset($data['project_id']);
            }
            $res = $this->chargeSet($data);
            if (isset($res['code'])) {
                return $res;
            }
            if (isset($res[0]['charge_setting_id'])) {
                $result['charge_setting_id'][] = $res[0]['charge_setting_id'];
            } elseif (isset($res['charge_setting_id'])) {
                $result['charge_setting_id'][] = $res['charge_setting_id'];
            }
        } else {
            // 角色
            $userIds = app($this->userRepository)->getAllUserIdString([
                'search' => [
                    'role_id' => [$data["role_id"]]
                ],
                'return_type' => 'array'
            ]);
            if (!empty($userIds)) {
                foreach ($userIds as $userId) {
                    $tempWhere['set_type'] = [3];
                    $tempWhere['user_id'] = [$userId];

                    $tempData = $this->handleAlertData($data, $tempWhere);
                    $tempData['set_type'] = 3;
                    $tempData['user_id'] = $userId;
                    if (array_key_exists('project_id', $data) && empty($tempData['project_id'])) {
                        unset($tempData['project_id']);
                    }
                    $res = $this->chargeSet($tempData);
                    if (isset($res['code'])) {
                        return $res;
                    }
                    if (isset($res[0]['charge_setting_id'])) {
                        $result['charge_setting_id'][] = $res[0]['charge_setting_id'];
                    }
                }
            }
        }
        return [
            'status' => 1,
            'dataForLog' => [
                [
                    'table_to' => 'charge_setting',
                    'field_to' => 'charge_setting_id',
                    'id_to' => $result['charge_setting_id']
                ]
            ]
        ];
    }

    private function handleAlertData($data, $tempWhere) {
        $chargeSet = app($this->chargeSettingRepository)->getSettingByWhere($tempWhere, false);
        if (isset($chargeSet->charge_setting_id)) {
            $data['charge_setting_id'] = $chargeSet->charge_setting_id;
            if ($data['subject_check'] == 1) {
                // 如果预警方式不同，科目更新
                if ($chargeSet->alert_method != $data['alert_method']) {
                    $subjectValues = [];
                    $subjectValues[] = [
                        'type_id' => $data['alert_subject'],
                        'type_value' => $data['alert_value'],
                    ];
                    $data['subject_values'] = json_encode($subjectValues);
                } else {
                    if (empty($chargeSet->subject_values)) {
                        $subjectValues = [];
                        $subjectValues[] = [
                            'type_id' => $data['alert_subject'],
                            'type_value' => $data['alert_value'],
                        ];
                        $data['subject_values'] = json_encode($subjectValues);
                    } else {
                        $subjectValues = json_decode($chargeSet->subject_values, true);
                        $total = 0;
                        $flag = false;
                        foreach ($subjectValues as $key => $value) {
                            if ($value['type_id'] == $data['alert_subject']) {
                                $flag = true;
                                $value['type_value'] = $data['alert_value'];
                                $subjectValues[$key]['type_value'] = $data['alert_value'];
                            }
                            $total += $value['type_value'];
                        }
                        if (!$flag) {
                            $subjectValues[] = [
                                'type_id' => $data['alert_subject'],
                                'type_value' => $data['alert_value'],
                            ];
                            $total += $data['alert_value'];
                        }
                        $data['alert_value'] = $total;
                        $data['subject_values'] = json_encode($subjectValues);
                    }
                }
            }
        } else {
            if ($data['subject_check'] == 1) {
                $subjectValues = [];
                $subjectValues[] = [
                    'type_id' => $data['alert_subject'],
                    'type_value' => $data['alert_value'],
                ];
                $data['subject_values'] = json_encode($subjectValues);
            }
        }
        $data['flowout'] = 1;
        return $data;
    }

    public function flowOutSendToChargeSettingDelete($data) {

        if (empty($data)) {
            return ['code' => ['0x000003', 'common']];
        }
        if (!isset($data['unique_id']) || empty($data['unique_id'])) {
            return ['code' => ['0x000006', 'common']];
        }
        if (!app($this->chargeSettingRepository)->getTotal(['search' => ['charge_setting_id' => [ $data['unique_id'] ] ]])) {
            return ['code' => ['0x000021', 'common']];
        }
        $result = $this->deleteChargeSetItem($data['unique_id']);
        if (isset($result['code'])) {
            return $result;
        }
        return [
            'status' => 1,
            'dataForLog' => [
                [
                    'table_to' => 'charge_setting',
                    'field_to' => 'charge_setting_id',
                    'id_to' => $data['unique_id']
                ]
            ]
        ];

    }
    // 预警对象类型数据源
    public function getChargeSetType($params) {
        return [
            'list' => [
                [
                    'set_type_id' => 1,
                    'set_type_name' => trans('charge.company')
                ],
                [
                    'set_type_id' => 2,
                    'set_type_name' => trans('charge.department')
                ],
                [
                    'set_type_id' => 3,
                    'set_type_name' => trans('charge.user')
                ],
                [
                    'set_type_id' => 4,
                    'set_type_name' => trans('charge.role')
                ],
                [
                    'set_type_id' => 5,
                    'set_type_name' => trans('charge.project')
                ],
            ],
            'total' => 5
        ];
    }
    // 预警方式数据源
    public function getChargeAlertMethod($params) {
        return [
            'list' => [
                [
                    'alert_method_id' => 1,
                    'alert_method_name' => trans('charge.year')
                ],
                [
                    'alert_method_id' => 2,
                    'alert_method_name' => trans('charge.quarter')
                ],
                [
                    'alert_method_id' => 3,
                    'alert_method_name' => trans('charge.month')
                ],
                [
                    'alert_method_id' => 4,
                    'alert_method_name' => trans('charge.customize')
                ]
            ],
            'total' => 4
        ];
    }
    /**
     * 获取外发额外字段
     *
     * @param array $data
     * @param array $extraFields
     *
     * @return json
     */
    private function getExtraDataFromFlow($data, $extraFields = ['run_id', 'flow_id', 'run_name'])
    {
        $extra = [];

        foreach ($extraFields as $field) {
            if (isset($data[$field])) {
                $extra[$field] = $data[$field];
            }
        }

        if (!empty($extra)) {
            return json_encode($extra);
        } else {
            return '';
        }
    }

    public function chargeDataSourceByUndertake($param, $own)
    {
        // 判断是否有项目模块授权
        $projectEmpower = app('App\EofficeApp\Empower\Services\EmpowerService')->checkModuleWhetherExpired(160);
        // 判断当前用户是否有项目模块
        $projectMenu  = app('App\EofficeApp\Menu\Services\UserMenuService')->judgeMenuPermission(160);

        $param = $this->parseParams($param);
        $where = [
            ['table', '=', 'charge_undertake'],
        ];
        if (!($projectEmpower && $projectMenu == 'true')) {
            $where[] = ['lang_key', '!=', 'charge_undertake_4'];
        }
        $langTable = app($this->langService)->getLangTable(null);

        if (!isset($param['search'])) {
            $param['search'] = [];
        }

        if (isset($param['search']['charge_undertake_name'][0])) {
            $where[] = ['lang_value', 'like', '%' . $param['search']['charge_undertake_name'][0] . '%'];
        }

        if (isset($param['search']['charge_undertake_id'])) {
            $where[] = ['charge_undertake_id', '=', $param['search']['charge_undertake_id']];
        }

        $data       = DB::table($langTable)->leftJoin('charge_undertake', 'charge_undertake.charge_undertake_name', '=', $langTable . '.lang_key')->where($where)->get();
        $returnData = ['list' => [], 'total' => count($data)];

        if (count($data) > 0) {
            foreach ($data as $key => $value) {
                $returnData['list'][$key] = [
                    'charge_undertake_id'   => $value->charge_undertake_id,
                    'charge_undertake_name' => $value->lang_value,
                ];
            }
        }

        return $returnData;
    }

    public function chargeSubjectValue($data)
    {
        $filter = $this->chargeDataFilter($data);
        if (!$filter) {
            return '';
        }
        if (!isset($data['payment_date']) || empty($data['payment_date']) || strpos($data['payment_date'], '{') !== false) {
            $data['payment_date'] = date("Y-m-d");
        }

        $userId    = '';
        $deptId    = 0;
        $projectId = 0;
        $undertake = $data['charge_undertake'] ?? 0;
        if ($undertake == 0) {
            return '';
        }
        $where = [];
        if ($undertake == 1) {
            // 个人
            $userId = $data['object'];
            $where = ['user_id' => [$userId], "set_type" => [3]];
        } elseif ($undertake == 2) {
            // 部门
            $deptId = $data['object'];
            $where = ['dept_id' => [$deptId], "set_type" => [2]];
        } elseif ($undertake == 3) {
            // 公司
            $where = ["set_type" => [1]];
        } elseif ($undertake == 4) {
            $projectId = $data['object'];
            $where = ['project_id' => [$projectId]];
        }

        if (!is_numeric($deptId)) {
            // 承担者选部门，查询对象选择用户时，查询该用户所在部门
            $userId   = $deptId;
            $userInfo = app($this->userRepository)->getUserDeptName(['search' => ['user_id' => [$userId]]]);
            if (isset($userInfo[0]) && isset($userInfo[0]['userHasOneSystemInfo']) && isset($userInfo[0]['userHasOneSystemInfo']['dept_id'])) {
                $deptId = $userInfo[0]['userHasOneSystemInfo']['dept_id'];
                $where = ['dept_id' => [$deptId], "set_type" => [2]];
            } else {
                return '';
            }
        }

        $type   = app($this->chargeTypeRepository)->getDetail($data['subject_type']);
        $typeId = isset($type['charge_type_id']) ? $type['charge_type_id'] : '';

        $typeValue = '';
        $chargeSet = app($this->chargeSettingRepository)->getSettingByWhere($where, false);

        if ($undertake != 4) {
            // 判断是否是多周期
            if (isset($chargeSet->alert_method) && $chargeSet->alert_method == 'custom') {
                $where['alert_data_start'] = [$data['payment_date'], '<='];
                $where['alert_data_end'] = [$data['payment_date'], '>='];
                $chargeSet = app($this->chargeSettingRepository)->getSettingByWhere($where, false);
            }
        }

        if (isset($chargeSet->subject_values) && !empty($chargeSet->subject_values)) {
            $typeValue = $this->getTypeValue($chargeSet->subject_values, $typeId);
        }

        return $typeValue ? ['subjectValue' => $typeValue] : '';
    }

    public function chargeSubjectAllUse($data)
    {
        $filter = $this->chargeDataFilter($data);
        if (!$filter) {
            return '';
        }
        if (!isset($data['payment_date']) || empty($data['payment_date']) || strpos($data['payment_date'], '{') !== false) {
            $data['payment_date'] = date("Y-m-d");
        }
        $type   = app($this->chargeTypeRepository)->getDetail($data['subject_type']);
        if (!isset($type['charge_type_id']) || empty($type['charge_type_id'])) {
            return '';
        }
        $statisticWhere = [
            'charge_undertaker' => [$data['charge_undertake']],
            'charge_type_id' => [$data['subject_type']]
        ];

        switch ($data['charge_undertake']) {
            case 1:
                $statisticWhere['undertake_user'] = [$data['object']];
                break;
            case 2:
                if (!is_numeric($data['object'])) {
                    // 承担者选部门，查询对象选择用户时，查询该用户所在部门
                    $userInfo = app($this->userRepository)->getUserDeptName(['search' => ['user_id' => [$data['object']]]]);
                    if (isset($userInfo[0]) && isset($userInfo[0]['userHasOneSystemInfo']) && isset($userInfo[0]['userHasOneSystemInfo']['dept_id'])) {
                        $data['object'] = $userInfo[0]['userHasOneSystemInfo']['dept_id'];
                    } else {
                        return ['subjectAllUseValue' => 0];
                    }
                }
                $statisticWhere['undertake_dept'] = [$data['object']];
                break;
            case 3:
                break;
            case 4:
                $statisticWhere['project_id'] = [$data['object']];
                break;
            default:
                return ['subjectAllUseValue' => 0];
                break;
        }

        $statistic = app($this->chargeListRepository)->getStatistic($statisticWhere);
        return ['subjectAllUseValue' => $statistic];
    }

    public function getTypeValue($subjectValues, $typeId){
        if (empty($typeId)) {
            return '';
        }
        $typeValue = '';
        if (!empty($subjectValues)) {
            $subjectValues = json_decode($subjectValues, true);

            $values = array_column($subjectValues, 'type_value', 'type_id');
            $typeValue = $values[$typeId] ?? '';
        }
        return $typeValue;
    }

    public function chargeSubjectUseValue($data, $own)
    {
        $filter = $this->chargeDataFilter($data);
        if (!$filter) {
            return '';
        }
        if (!isset($data['payment_date']) || empty($data['payment_date']) || strpos($data['payment_date'], '{') !== false) {
            $data['payment_date'] = date("Y-m-d");
        }
        $type   = app($this->chargeTypeRepository)->getDetail($data['subject_type']);
        if (!isset($type['charge_type_id']) || empty($type['charge_type_id'])) {
            return '';
        }

        $where = [];
        $statisticWhere = [
            'charge_undertaker' => [$data['charge_undertake']],
            'charge_type_id' => [$data['subject_type']]
        ];
        switch ($data['charge_undertake']) {
            case 1:
                $where = ['user_id' => [$data['object']], "set_type" => [3]];
                $statisticWhere['undertake_user'] = [$data['object']];
                break;
            case 2:
                if (!is_numeric($data['object'])) {
                    // 承担者选部门，查询对象选择用户时，查询该用户所在部门
                    $userInfo = app($this->userRepository)->getUserDeptName(['search' => ['user_id' => [$data['object']]]]);
                    if (isset($userInfo[0]) && isset($userInfo[0]['userHasOneSystemInfo']) && isset($userInfo[0]['userHasOneSystemInfo']['dept_id'])) {
                        $data['object'] = $userInfo[0]['userHasOneSystemInfo']['dept_id'];
                    } else {
                        return ['subjectUseValue' => 0];
                    }
                }
                $where = ['dept_id' => [$data['object']], "set_type" => [2]];
                $statisticWhere['undertake_dept'] = [$data['object']];
                break;
            case 3:
                $where = ["set_type" => [1]];
                break;
            case 4:
                $where = ['project_id' => [$data['object']]];
                $statisticWhere['project_id'] = [$data['object']];
                break;
            default:
                return ['subjectUseValue' => 0];
                break;
        }
        // 判断是否是多周期，所周期时，取对应周期内报销的数据
        $chargeSet = app($this->chargeSettingRepository)->getSettingByWhere($where, false);
        $typeValue = 0;
        if (isset($chargeSet->alert_method)) {
            if ($chargeSet->alert_method == 'custom') {
                $where['alert_data_start'] = [$data['payment_date'], '<='];
                $where['alert_data_end']   = [$data['payment_date'], '>='];
                $record = app($this->chargeSettingRepository)->getSettingByWhere($where, false);
                if (!empty($record) && isset($record->charge_setting_id)) {
                    $typeValue = $this->getTypeValue($record->subject_values, $data['subject_type']);
                    $statisticWhere['payment_date'] = [[$record->alert_data_start, $record->alert_data_end], 'between'];
                }
            } else {
                $dataRange = $this->getAlertDate($chargeSet->alert_method, strtotime($data['payment_date']));
                if ($dataRange[0] && $dataRange[1]) {
                    $statisticWhere['payment_date'] = [$dataRange, 'between'];
                }
                $typeValue = $this->getTypeValue($chargeSet->subject_values, $data['subject_type']);
            }
        } 
        $statistic = app($this->chargeListRepository)->getStatistic($statisticWhere);

        return ['subjectValue'=> $typeValue, 'subjectUseValue' => $statistic ?? 0];
    }

    public function chargeSubjectUnuseValue($data, $own)
    {
        $use = $this->chargeSubjectUseValue($data, $own);
        if (isset($use['subjectValue']) && isset($use['subjectUseValue'])) {
            $unUse = $use['subjectValue'] - $use['subjectUseValue'];
            return ['subjectUnuseValue' => $unUse];
        } else {
            return '';
        }
    }

    public function chargeDataFilter($data)
    {
        if (!isset($data['charge_undertake']) || $data['charge_undertake'] == '') {
            return false;
        }
        if ($data['charge_undertake'] != '3' && (!isset($data['object']) || $data['object'] == '')) {
            return false;
        }
        if (!isset($data['subject_type']) || $data['subject_type'] == '') {
            return false;
        }

        return true;
    }

    // 获取项目的费用统计值
    public function getStatisticByProject($projectIds, $own)
    {
        $data     = [];
        $viewUser = $this->getViewUser($own);
        if (!empty($projectIds)) {
            foreach ($projectIds as $projectId) {
                $where = [
                    'project_id' => [$projectId],
                ];
                if ($viewUser != 'all') {
                    $where['user_id'] = [$viewUser, 'in'];
                }
                $temp             = app($this->chargeListRepository)->getStatistic($where);
                $data[$projectId] = $temp;
            }
        }

        return $data;
    }
    // 项目费用数据源
    public function chargeDataSourceByProject($projectId)
    {
        $result = [
            "projectAlarmValue"   => "0.00",
            "projectReportAmount" => "0.00",
        ];

        $row = app($this->chargeSettingRepository)->getDataByWhere(["project_id" => [$projectId]]);

        if (isset($row[0]) && $row[0]) {
            $result["projectAlarmValue"] = isset($row[0]["alert_value"]) ? $row[0]["alert_value"] : "0.00";
        }

        $result["projectReportAmount"] = app($this->chargeListRepository)->getReportAmount(["alert_method" => ""], ["project_id" => [$projectId]]);

        return $result;
    }
    // 获取权限设置列表
    public function getChargePermission($params)
    {
        $params = $this->parseParams($params);
        return $this->response(app($this->chargePermissionRepository), 'getChargePermissionTotal', 'getChargePermission', $params);
    }
    // 添加权限设置
    public function addChargePermission($data)
    {
        $this->chargePermissionObj = app($this->chargePermissionRepository);
        return $this->handlePermissionData($data);
    }
    // 编辑权限
    public function editChargePermission($id, $data)
    {
        $this->chargePermissionObj = app($this->chargePermissionRepository);
        return $this->handlePermissionData($data);
    }
    private function handlePermissionData($data)
    {
        $handleData = [
            'manager_type'  => '',
            'manager_value' => '',
            'set_type'      => $data['set_type'],
        ];
        $tempData = [];
        switch ($data['set_type']) {
            case 2: //部门
                $tempData = [
                    'set_value'    => is_array($data['dept_id']) ? implode(',', $data['dept_id']) : $data['dept_id'],
                    'has_children' => isset($data['has_children']) ? $data['has_children'] : 0,
                ];
                break;
            case 3: //角色
                $tempData = [
                    'set_value'    => is_array($data['role_id']) ? implode(',', $data['role_id']) : $data['role_id'],
                    'has_children' => 0,
                ];
                break;
            case 4: //用户
                $tempData = [
                    'set_value'    => is_array($data['user_id']) ? implode(',', $data['user_id']) : $data['user_id'],
                    'has_children' => 0,
                ];
                break;
            default:
                $tempData = [
                    'set_value'    => '',
                    'has_children' => 0,
                ];
                break;
        }

        $handleData = array_merge($handleData, $tempData);

        return $this->executePermissionData($data, $handleData);
    }

    private function executePermissionData($data, $handleData)
    {
        if (isset($data['manager']['dept_id']) && !empty($data['manager']['dept_id'])) {
            foreach ($data['manager']['dept_id'] as $key => $value) {
                $temp                  = $handleData;
                $temp['manager_type']  = 1;
                $temp['manager_value'] = $value;
                $where                 = [
                    'search' => ['manager_type' => [1], 'manager_value' => [$value]],
                ];
                $count = $this->chargePermissionObj->getTotal($where);
                if ($count > 0) {
                    $this->chargePermissionObj->updateData($temp, ['manager_type' => 1, 'manager_value' => $value]);
                } else {
                    $this->chargePermissionObj->insertData($temp);
                }
            }
        }
        if (isset($data['manager']['role_id']) && !empty($data['manager']['role_id'])) {
            foreach ($data['manager']['role_id'] as $key => $value) {
                $temp                  = $handleData;
                $temp['manager_type']  = 2;
                $temp['manager_value'] = $value;
                $where                 = [
                    'search' => ['manager_type' => [2], 'manager_value' => [$value]],
                ];
                $count = $this->chargePermissionObj->getTotal($where);
                if ($count > 0) {
                    $this->chargePermissionObj->updateData($temp, ['manager_type' => 2, 'manager_value' => $value]);
                } else {
                    $this->chargePermissionObj->insertData($temp);
                }
            }
        }
        if (isset($data['manager']['user_id']) && !empty($data['manager']['user_id'])) {
            foreach ($data['manager']['user_id'] as $key => $value) {
                $temp                  = $handleData;
                $temp['manager_type']  = 3;
                $temp['manager_value'] = $value;
                $where                 = [
                    'search' => ['manager_type' => [3], 'manager_value' => [$value]],
                ];
                $count = $this->chargePermissionObj->getTotal($where);
                if ($count > 0) {
                    $this->chargePermissionObj->updateData($temp, ['manager_type' => 3, 'manager_value' => $value]);
                } else {
                    $this->chargePermissionObj->insertData($temp);
                }
            }
        }

        return true;
    }
    // 获取权限详情
    public function getChargePermissionById($id)
    {
        if ($data = app($this->chargePermissionRepository)->getDetail($id)) {
            $data            = $data->toArray();
            $data['manager'] = [];
            $managerValue    = explode(',', $data['manager_value']);
            $setValue        = explode(',', $data['set_value']);

            if ($data['manager_type'] == 1) {
                $data['manager']['dept_id'] = $managerValue;
            }
            if ($data['manager_type'] == 2) {
                $data['manager']['role_id'] = $managerValue;
            }
            if ($data['manager_type'] == 3) {
                $data['manager']['user_id'] = $managerValue;
            }
            if ($data['set_type'] == 2) {
                $data['dept_id'] = $setValue;
            }
            if ($data['set_type'] == 3) {
                $data['role_id'] = $setValue;
            }
            if ($data['set_type'] == 4) {
                $data['user_id'] = $setValue;
            }
            return $data;
        }

        return ['code' => ['0x000003', 'common']];
    }
    // 删除权限设置
    public function deleteChargePermission($id)
    {
        $ids = explode(',', $id);
        return app($this->chargePermissionRepository)->deleteById($ids);
    }
    // 获取当前用户可查看的用户集合
    public function getViewUser($own, $param = [])
    {
        $this->chargePermissionObj = app($this->chargePermissionRepository);
        $user                      = [];
        $userId                    = $own['user_id'];
        $userDept                  = $own['dept_id'];
        $userRole                  = $own['role_id'];
        // 用户所在部门权限设置
        $deptWhere = [
            'search' => [
                'manager_value' => [$userDept],
                'manager_type'  => [1],
            ],
        ];
        $record = $this->chargePermissionObj->getSimpleData($deptWhere);
        if (isset($record[0]) && !empty($record[0])) {
            $user = $this->getViewUserByType($record[0], $userId, $param);
            if ($user == 'all') {
                return $user;
            }
        }
        // 用户所属角色权限设置
        $roleWhere = [
            'search' => [
                'manager_value' => [$userRole, 'in'],
                'manager_type'  => [2],
            ],
        ];
        $record = $this->chargePermissionObj->getSimpleData($roleWhere);
        if (count($record) > 0) {
            foreach ($record as $key => $value) {
                $tempUser = $this->getViewUserByType($value, $userId, $param);
                if ($tempUser == 'all') {
                    return $tempUser;
                }
                if (!empty($user)) {
                    if (!empty($tempUser)) {
                        $user = array_unique(array_merge($user, $tempUser));
                    }
                } else {
                    if (!empty($tempUser)) {
                        $user = $tempUser;
                    }
                }
            }
        }
        // 用户自身权限设置
        $userWhere = [
            'search' => [
                'manager_value' => [$userId],
                'manager_type'  => [3],
            ],
        ];
        $record = $this->chargePermissionObj->getSimpleData($userWhere);
        if (isset($record[0]) && !empty($record[0])) {
            $tempUser = $this->getViewUserByType($record[0], $userId, $param);
            if ($tempUser == 'all') {
                return $tempUser;
            }
            if (!empty($user)) {
                if (!empty($tempUser)) {
                    $user = array_unique(array_merge($user, $tempUser));
                }
            } else {
                if (!empty($tempUser)) {
                    $user = $tempUser;
                }
            }
        }
        // 默认包含自己，2019-12-12
        if (is_array($user) && !in_array($userId, $user)) {
            $user[] = $userId;
        }
        return $user;
    }
    public function getViewUserByType($data, $userId, $param = [])
    {
        $user = [];

        switch ($data['set_type']) {
            case 1:
                $user = 'all';
                break;
            case 2:
                $deptId = explode(',', $data['set_value']);
                if ($data['has_children'] == 1) {
                    //获取部门包含子部门的userid
                    $depts = DB::table('department')->select('dept_id')->whereIn('parent_id', $deptId)->pluck('dept_id')->toArray();
                    $depts = array_merge($depts, $deptId);
                    $user  = DB::table('user_system_info')->whereIn('dept_id', $depts)->whereNull('deleted_at')->pluck('user_id')->toArray();
                } else {
                    $user = DB::table('user_system_info')->whereIn('dept_id', $deptId)->whereNull('deleted_at')->pluck('user_id')->toArray();
                }
                break;
            case 3:
                $roleIds = is_array($data['set_value']) ? $data['set_value'] : explode(',', $data['set_value']);
                $user    = app($this->userRepository)->getUserListByRoleId($roleIds)->pluck("user_id")->toArray();
                break;
            case 4:
                $user = empty($data['set_value']) ? [] : explode(',', $data['set_value']);
                break;
            case 5:
                $subordinate = app($this->userService)->getSubordinateArrayByUserId($userId);
                $user        = isset($subordinate['id']) ? $subordinate['id'] : [];
                break;
            case 6:
                $subordinate = app($this->userService)->getSubordinateArrayByUserId($userId, ['all_subordinate' => 1]);
                $user        = isset($subordinate['id']) ? $subordinate['id'] : [];
                break;
            default:
                break;
        }

        return $user == 'all' ? 'all' : array_unique($user);
    }
    public function getChargeSetByDate($data)
    {
        $where = [
            'alert_data_start' => [$data['payment_date'], '<='],
            'alert_data_end'   => [$data['payment_date'], '>='],
        ];
        $projectValue = "";
        //个人
        $tempWhere  = ['user_id' => [$data['undertake_user']]];
        $tempAlert  = $this->getAlertTypeAndValue($where, $tempWhere);
        $selfType   = $tempAlert["type"];
        $selfValues = $tempAlert["values"];
        //部门
        $deptType = $deptValues = '';
        if (!empty($data['undertake_dept'])) {
            $tempWhere      = ["dept_id" => [$data['undertake_dept']], "user_id" => [""]];
            $tempAlert      = $this->getAlertTypeAndValue($where, $tempWhere);
            $deptType       = $tempAlert["type"];
            $deptValues     = $tempAlert["values"];
        }
        
        //公司
        $tempWhere    = ["set_type" => [1]];
        $tempAlert    = $this->getAlertTypeAndValue($where, $tempWhere);
        $companyType  = $tempAlert["type"];
        $companyValue = $tempAlert["values"];
        //项目
        if ($data['project_id'] != '') {
            $tempWhere    = ["project_id" => [$data['project_id']]];
            $tempAlert    = $this->getAlertTypeAndValue($where, $tempWhere);
            $projectValue = $tempAlert["values"];
        }

        $chargeAlertValue = [
            "dept_type"     => $deptType,
            "dept_values"   => $deptValues,
            "self_type"     => $selfType,
            "self_values"   => $selfValues,
            "company_type"  => $companyType,
            "company_value" => $companyValue,
            "project_value" => $projectValue,
        ];

        return $chargeAlertValue;
    }

    private function getAlertTypeAndValue($where, $tempWhere)
    {
        // 先判断是否是周期设置
        $tempSet = app($this->chargeSettingRepository)->getSettingByWhere($tempWhere, false);
        if (!empty($tempSet) && isset($tempSet->alert_method)) {
            if ($tempSet->alert_method == 'custom') {
                $where = array_merge($where, $tempWhere);
                // 获取当前时间所在的预警周期
                $chargeSet = app($this->chargeSettingRepository)->getSettingByWhere($where, false);
                if (!empty($chargeSet)) {
                    return $this->chargeConfig($chargeSet->toArray());
                }
            } else {
                return $this->chargeConfig($tempSet->toArray());
            }
        }

        return ['type' => trans('charge.not_set'), 'values' => 0];
    }

    public function chargeMobileType($params) {
        $params = $this->parseParams($params);

        return $this->response(app($this->chargeTypeRepository), 'chargeTypeMobileTotal', 'chargeTypeMobile', $params);
    }
    // 导入费用科目
    public function getChargeTypeFields() {
        $template = [
            [
                'sheetName' => trans('import.chargeType'),
                'header' => [
                    'charge_type_name'  => [
                        'data'  => trans('charge.subject_name'),
                        'style' => ['width' => 30]
                    ],
                    'charge_type_id'     => [
                        'data'  => trans('charge.import_charge_type_subject_id_or_number'),
                        'style' => ['width' => 60]
                    ],
                    'charge_type_parent' => [
                        'data'  => trans('charge.import_charge_type_parent_subject_id_or_number'),
                        'style' => ['width' => 60]
                    ],
                ],
                'data' => [
                    [
                        'charge_type_name'   => trans('charge.subject_1'),
                        'charge_type_id'     => 'type01',
                        'charge_type_parent' => 0,
                    ],
                    [
                        'charge_type_name'   => trans('charge.sub_subject_1_2'),
                        'charge_type_id'     => 'type02'.trans('charge.no_sub_subject'),
                        'charge_type_parent' => 'type01',
                    ],
                    [
                        'charge_type_name'   => trans('charge.sub_subject_1_3'),
                        'charge_type_id'     => 'type03',
                        'charge_type_parent' => 'type01',
                    ],
                    [
                        'charge_type_name'   => trans('charge.sub_subject_3_4'),
                        'charge_type_id'     => '',
                        'charge_type_parent' => 'type03',
                    ],
                ]
            ],
            [
                'sheetName' => trans('charge.reference_information'),
                'header' => [
                    'charge_type_name'   => [
                        'data'  => trans('charge.subject_name'),
                        'style' => ['width' => 50]
                    ],
                    'charge_type_id'     => [
                        'data'  => trans('charge.type_id'),
                        'style' => ['width' => 30]
                    ],
                    'charge_type_parent' => [
                        'data'  => trans('charge.parent_id'),
                        'style' => ['width' => 30]
                    ],
                ],
                'data' => []
            ]
        ];

        $typeArray = [];
        $types = app($this->chargeTypeRepository)->getChargeTypeList([]);
        if (!$types->isEmpty()) {
            foreach ($types as $value) {
                if (!isset($typeArray[$value->charge_type_parent])) {
                    $typeArray[$value->charge_type_parent] = [];
                } 
                $typeArray[$value->charge_type_parent][] = $value->toArray();
            }
        }

        if (!empty($typeArray)) {
            foreach ($typeArray as $value) {
                foreach ($value as $v) {
                    $template[1]['data'][] = [
                        'charge_type_name'   => $v['charge_type_name'],
                        'charge_type_id'     => $v['charge_type_id'],
                        'charge_type_parent' => $v['charge_type_parent']
                    ];
                }
            }
        }
        
        return $template;
    }

    public function importChargeType($data, $param) {
        $info = [
            'total'   => count($data),
            'success' => 0,
            'error'   => 0,
        ];
        $typeParents = [];
        foreach ($data as $key => $value) {
            $data[$key]['key'] = $key;
            if ($value['charge_type_parent'] === 0 || $value['charge_type_parent'] === null) {
                continue;
            }
            if (!in_array($value['charge_type_parent'], $typeParents)) {
                $typeParents[] = $value['charge_type_parent'];
            }
        }
        $keys = [];
        // 组合费用路径数组
        $hasParentData = $data;
        $treeArray = $this->getTypeTree($data, $data, $hasParentData, $typeParents, $keys);
        foreach ($hasParentData as $key => $value) {
            if  (isset($value['has_parent'])) {
                $treeArray[$key]['has_parent'] = 1;
            }
        }
        // 导入费用数据
        $this->importChargeTypeData($treeArray, false, $data, $info, $keys);

        return ['data' => $data, 'info' => $info];
    }
    // 处理导入分类的结构
    private function getTypeTree($tree, $oldData, &$hasParentData, $typeParents, &$keys) {
        foreach ($tree as $key => $value) {
            if (in_array($value['charge_type_id'], $typeParents)) {
                foreach ($oldData as $k => $v) {
                    if ($value['charge_type_id'] == (string)$v['charge_type_parent']) {
                        if (!in_array($k, $keys)) {
                            $keys[] = $k;
                        }
                        $hasParentData[$k]['has_parent'] = 1;
                        $tree[$key]['sub'][] = $v;
                    }
                }
                if (isset($tree[$key]['sub']) && !empty($tree[$key]['sub'])) {
                    $tree[$key]['sub'] = $this->getTypeTree($tree[$key]['sub'], $oldData, $hasParentData, $typeParents, $keys);
                }
            }
        }
        return $tree;
    }
    // 处理科目数据导入
    private function importChargeTypeData($treeArray, $parentId=false, &$data, &$info, $keys) {
        foreach ($treeArray as $value) {
            if  (isset($value['has_parent'])) {
                continue;
            }
            $valid = true;
            // 科目名称为空
            if ($value['charge_type_name'] === '' || $value['charge_type_name'] === null) {
                $info['error']++;
                $data[$value['key']]['importResult'] = importDataFail();
                $data[$value['key']]['importReason'] = importDataFail(trans('charge.subject_name_is_empty'));
                $valid = false;
            }
            // 科目父级为空
            if ($valid && ($value['charge_type_parent'] === '' || $value['charge_type_parent'] === null)) {
                $info['error']++;
                $data[$value['key']]['importResult'] = importDataFail();
                $data[$value['key']]['importReason'] = importDataFail(trans('charge.parent_subject_is_empty'));
                $valid = false;
            }

            if ($parentId === false) {
                $parent = app($this->chargeTypeRepository)->getOneFieldInfo(['charge_type_id' => $value['charge_type_parent']]);
            } else {
                $parent = app($this->chargeTypeRepository)->getDetail($parentId);
            }
            if ($parent) {
                if ($valid) {
                    // 检查同一父级下是否已存在
                    $isUnique = app($this->chargeTypeRepository)->isUniqueNameByParent($value['charge_type_name'], $parent->charge_type_id);
                    if (!empty($isUnique)) {
                        $info['error']++;
                        $data[$value['key']]['importResult'] = importDataFail();
                        $data[$value['key']]['importReason'] = importDataFail(trans('charge.0x020017'));
                        $valid = false;
                    }
                }
                $typeId = false;
                if ($valid) {
                    $chargeData = [
                        "charge_type_name"   => $value['charge_type_name'],
                        "charge_type_parent" => $parent->charge_type_id,
                        "type_level"         => $parent->charge_type_id == 0 ? 0 : $parent->type_level . ',' . $parent->charge_type_id,
                        "has_children"       => isset($value['sub']) && !empty($value['sub']) ? 1 : 0,
                        "charge_type_order"  => 0,
                        "level"              => $parent->charge_type_id == 0 ? 1 : $parent->level + 1
                    ];
                    $typeId = app($this->chargeTypeRepository)->entity->insertGetId($chargeData);
                    // 更新has_children字段
                    if ($parent->charge_type_id != 0 && $parent->has_children == 0) {
                        app($this->chargeTypeRepository)->updateData(['has_children' => 1], ['charge_type_id' => $parent->charge_type_id]);
                    }
                    $info['success']++;
                    $data[$value['key']]['importResult'] = importDataSuccess();
                }
                if (isset($value['sub']) && !empty($value['sub'])) {
                    $this->importChargeTypeData($value['sub'], $typeId, $data, $info, $keys);
                }
            } else {
                if ($value['charge_type_parent'] == '0') {
                    if ($valid) {
                        // 科目重名
                        $isUnique = app($this->chargeTypeRepository)->isUniqueNameByParent($value['charge_type_name'], 0);
                        if (!empty($isUnique)) {
                            $info['error']++;
                            $data[$value['key']]['importResult'] = importDataFail();
                            $data[$value['key']]['importReason'] = importDataFail(trans('charge.0x020017'));
                            $valid = false;
                        }
                    }
                    $typeId = false;
                    if ($valid) {
                        $chargeData = [
                            "charge_type_name"   => $value['charge_type_name'],
                            "charge_type_parent" => 0,
                            "type_level"         => 0,
                            "has_children"       => isset($value['sub']) && !empty($value['sub']) ? 1 : 0,
                            "charge_type_order"  => 0,
                            "level"              => 1
                        ];
                        $typeId = app($this->chargeTypeRepository)->entity->insertGetId($chargeData);
                        $info['success']++;
                        $data[$value['key']]['importResult'] = importDataSuccess();
                    }
                    if (isset($value['sub']) && !empty($value['sub'])) {
                        $this->importChargeTypeData($value['sub'], $typeId, $data, $info, $keys);
                    }
                } else {
                    if ($valid) {
                        // 编号不存在
                        $info['error']++;
                        $data[$value['key']]['importResult'] = importDataFail();
                        $data[$value['key']]['importReason'] = importDataFail(trans('charge.parent_subject_is_not_exist'));
                        $valid = false;
                    }
                    if (isset($value['sub']) && !empty($value['sub'])) {
                        $this->importChargeTypeData($value['sub'], false, $data, $info, $keys);
                    }
                }
            }
        }
        return true;
    }
    public function getImportTypeLevel($all, $one, &$level) {
        $parentId = empty($one['charge_type_parent']) ? (int)$one['charge_type_parent'] : $one['charge_type_parent'];
        
        if (isset($all[$parentId])) {
            // 父级不是系统已有科目时 需要递归
            if (strpos($parentId, 't') === 0) {
                $level = empty($level) ? $parentId : $parentId.','.$level;
                return $this->getImportTypeLevel($all, $all[$parentId], $level);
            } else {
                $parentType = app($this->chargeTypeRepository)->getChargeTypeInfo($parentId);
                if (empty($parentType)) {
                    return ['code' => ['0x020021', 'charge']];
                }
                return $parentType->type_level.','.$parentId.','.$level;
            }
        } else {
            if ($parentId == 0) {
                return '0,'.$level;
            }
            return ['code' => ['0x020021', 'charge']];
        }
    }
    // 导入费用预警
    public function getChargeWarningFields($param) {
        // 判断是否有项目模块授权
        $projectEmpower = app('App\EofficeApp\Empower\Services\EmpowerService')->checkModuleWhetherExpired(160);
        // 判断当前用户是否有项目模块
        $projectMenu  = app('App\EofficeApp\Menu\Services\UserMenuService')->judgeMenuPermission(160, $param['user_id']);
        $alertObjectTypeText = $projectEmpower && $projectMenu == 'true' ? trans('charge.alert_object_type_option_with_project') : trans('charge.alert_object_type_option');
        $alertObjectIdText = $projectEmpower && $projectMenu == 'true' ? trans('charge.required_when_alert_object_with_project') : trans('charge.required_when_alert_object');
        $template = [
            [
                'sheetName' => trans('import.chargeType'),
                'header' => [
                    'set_type'  => [
                        'data'  => trans('charge.alert_object_type').'('.$alertObjectTypeText.')',
                        'style' => ['width' => 20]
                    ],
                    'id'     => [
                        'data'  => trans('charge.alert_object_id').'('.$alertObjectIdText.')',
                        'style' => ['width' => 55]
                    ],
                    'alert_method' => [
                        'data'  => trans('charge.method').'('.trans('charge.alert_method_option').')',
                        'style' => ['width' => 15]
                    ],
                    'start_date' => [
                        'data'  => trans('charge.alert_start_date').'('.trans('charge.required_for_custom_cycle').')',
                        'style' => ['width' => 35]
                    ],
                    'end_date' => [
                        'data'  => trans('charge.alert_end_date').'('.trans('charge.required_for_custom_cycle').')',
                        'style' => ['width' => 35]
                    ],
                    'subject_check' => [
                        'data'  => trans('charge.alert_amount_type').'('.trans('charge.total_or_each').')',
                        'style' => ['width' => 35]
                    ],
                    'total' => [
                        'data'  => trans('charge.total_value_of_early_warning').'('.trans('charge.alert_amount_type_required_by_total_value').')',
                        'style' => ['width' => 40]
                    ]
                ],
                'data' => [
                    [
                        'set_type' => trans('charge.alert_company'),
                        'alert_method' => trans('charge.alert_customize'),
                        'start_date' => date('Y-m-d'),
                        'end_date' => date('Y-m-d'),
                        'subject_check' => 0,
                        'total' => 10000
                    ]
                ]
            ]
        ];
        $allType = $this->getChargeTypeFullPath(false);
        foreach ($allType as $key => $value) {
            $typeKey = 'type_' . $key;
            $template[0]['header'][$typeKey] = [ 'data'  => $value, 'style' => ['width' => 15] ];
        }
        // 部门信息
        $deptTempData = [];
        $deptData = app($this->departmentService)->listDept([]);
        if ($deptData) {
            foreach ($deptData['list'] as $k => $v) {
                $deptTempData[$k]['dept_id'] = $v['dept_id'];
                // 优化，本身存在dept_name,不需要单独查询获取
                if (isset($v['dept_name'])) {
                    $deptTempData[$k]['dept_name'] = $v['dept_name'];
                } else {
                    $deptTempData[$k]['dept_name'] = app($this->userService)->getDeptPathByDeptId($v['dept_id']);
                }
            }
        }
        $template[] = [
            'sheetName' => trans('charge.dept_id_reference'),
            'header' => [
                'dept_name'   => ['data'  => trans('charge.department_name')],
                'dept_id'     => ['data'  => 'ID']
            ],
            'data' => $deptTempData
        ];
        // 用户信息
        $userTempData = [];
        $userList = app($this->userRepository)->getUserList([
            'fields' => ['user_id', 'user_accounts', 'user_name']
        ]);
        if ($userList) {
            foreach ($userList as $key => $value) {
                $userTempData[$key]['user_name'] = $value['user_name'];
                $userTempData[$key]['user_id'] = $value['user_id'];
            }
        }
        $template[] = [
            'sheetName' => trans('charge.user_id_reference'),
            'header' => [
                'user_name'   => ['data'  => trans('charge.user_name')],
                'user_id'     => ['data'  => 'ID']
            ],
            'data' => $userTempData
        ];
        // 角色信息
        $roleTempData = [];
        $roleData = app($this->roleService)->getRoleList();
        if ($roleData) {
            foreach ($roleData['list'] as $k => $v) {
                $roleTempData[$k]['role_id'] = $v['role_id'];
                $roleTempData[$k]['role_name'] = $v['role_name'];
            }
        }
        $template[] = [
            'sheetName' => trans('charge.role_id_reference'),
            'header' => [
                'role_name'   => ['data'  => trans('charge.role_name')],
                'role_id'     => ['data'  => 'ID']
            ],
            'data' => $roleTempData
        ];
        
        if ($projectEmpower && $projectMenu == 'true') {
            // 项目信息
            $projectTempData = [];
            $projectData = app($this->projectService)->getProjectAllByUserId($param['user_id']);
            if ($projectData) {
                foreach ($projectData as $k => $v) {
                    $projectTempData[$k]['manager_id'] = $v['manager_id'];
                    $projectTempData[$k]['manager_name'] = $v['manager_name'];
                }
            }
            $template[] = [
                'sheetName' => trans('charge.project_id_reference'),
                'header' => [
                    'manager_name'   => ['data'  => trans('charge.project_name')],
                    'manager_id'     => ['data'  => 'ID']
                ],
                'data' => $projectTempData
            ];
        }

        return $template;
    }
    private function getChargeTypeFullPath($isAll = true) {
        // 科目信息
        $allType = app($this->chargeTypeRepository)->getChargeTypeList([
            'fields' => ['charge_type_id', 'charge_type_name', 'type_level', 'has_children'],
            'page' => 0
        ]);
        $allTypeArray = array_column($allType->toArray(), 'charge_type_name', 'charge_type_id');
        $fullArray = [];
        if (!$allType->isEmpty()) {
            foreach ($allType as $value) {
                if (!$isAll) {
                    // 只取最小级的科目
                    if ($value->has_children == 1) {
                        continue;
                    }
                }
                
                if ($value->type_level == '0') {
                    $typeName = $value->charge_type_name;
                } else {
                    $level = [];
                    $types = array_filter(explode(',', $value->type_level));
                    foreach ($types as $v) {
                        $level[] = $allTypeArray[$v] ?? '';
                    }
                    $typeName = implode('/', $level).'/'.$value->charge_type_name;
                }
                $fullArray[$value->charge_type_id] = $typeName;
            }
        }
        
        return $fullArray;
    }
    public function importChargeWarning($data, $param) {
        $info = [
            'total'   => count($data),
            'success' => 0,
            'error'   => 0,
        ];
        // 判断是否有项目模块授权
        $projectEmpower = app('App\EofficeApp\Empower\Services\EmpowerService')->checkModuleWhetherExpired(160);
        // 判断当前用户是否有项目模块
        $projectMenu  = app('App\EofficeApp\Menu\Services\UserMenuService')->judgeMenuPermission(160, $param['user_info']['user_id']);
        $alertObjectType = ['', trans('charge.alert_company'), trans('charge.alert_department'), trans('charge.alert_user'), trans('charge.alert_role'), trans('charge.alert_project')];
        $methods = ['year' => trans('charge.alert_year'), 'quarter' => trans('charge.alert_quarter'), 'month' => trans('charge.alert_month'), 'custom' => trans('charge.alert_customize')];
        foreach ($data as $key => $value) {
            if (empty($value['set_type']) || !in_array($value['set_type'], $alertObjectType)) {
                $info['error']++;
                $data[$key]['importResult'] = importDataFail();
                $data[$key]['importReason'] = importDataFail(trans('charge.incorrect_alert_object_type'));
                continue;
            }
            if ($value['set_type'] == trans('charge.alert_project') && !($projectEmpower && $projectMenu == 'true')) {
                $info['error']++;
                $data[$key]['importResult'] = importDataFail();
                $data[$key]['importReason'] = importDataFail(trans('charge.no_project_module_permissions'));
                continue;
            }
            if ($value['set_type'] != trans('charge.alert_company') && empty($value['id'])) {
                $info['error']++;
                $data[$key]['importResult'] = importDataFail();
                $data[$key]['importReason'] = importDataFail(trans('charge.missing_alert_object_id'));
                continue;
            }
            if ($value['set_type'] != trans('charge.alert_project') && (empty($value['alert_method']) || !in_array($value['alert_method'], $methods))) {
                $info['error']++;
                $data[$key]['importResult'] = importDataFail();
                $data[$key]['importReason'] = importDataFail(trans('charge.incorrect_warning_mode'));
                continue;
            }
            if ($value['alert_method'] == trans('charge.alert_customize')) {
                if (empty($value['start_date'])) {
                    $info['error']++;
                    $data[$key]['importResult'] = importDataFail();
                    $data[$key]['importReason'] = importDataFail(trans('charge.0x020031'));
                    continue;
                }
                if (empty($value['end_date'])) {
                    $info['error']++;
                    $data[$key]['importResult'] = importDataFail();
                    $data[$key]['importReason'] = importDataFail(trans('charge.0x020032'));
                    continue;
                }
            }
            if (!in_array( (int)$value['subject_check'], [0, 1])) {
                $info['error']++;
                $data[$key]['importResult'] = importDataFail();
                $data[$key]['importReason'] = importDataFail(trans('charge.incorrect_alert_amount_type'));
                continue;
            }
            if ($value['subject_check'] == 0 && empty($value['total'])) {
                $info['error']++;
                $data[$key]['importResult'] = importDataFail();
                $data[$key]['importReason'] = importDataFail(trans('charge.alert_value_is_empty'));
                continue;
            }
            $subjectValues = [];
            $total = 0;
            if ( (int)$value['subject_check'] == 1) {
                foreach ($value as $k => $v) {
                    if (strpos($k, 'type_') !== false) {
                        if (!empty($v)) {
                            $typeId = substr($k, 5);
                            // 兼容千位分隔符
                            $subjectValues[] = ['type_id' => $typeId, 'type_value' => (float) str_replace(',', '', trim($v))];
                            $total += (float) str_replace(',', '', trim($v));
                        }
                    }
                }
            }
            $setType = array_search($value['set_type'], $alertObjectType);
            $method = array_search($value['alert_method'], $methods);
            $setData = [
                'alert_data_end' => $method == 'custom' ? $value['end_date'] : date('Y-m-d'),
                'alert_data_start' => $method == 'custom' ? $value['start_date'] : date('Y-m-d'),
                'alert_method' => $method,
                'alert_value' => (int)$value['subject_check'] == 0 ? (!empty($value['total']) ? $value['total'] : 0) : $total,
                'dept_id' => $setType == 2 ? (int)$value['id'] : '',
                'project_id' => $setType == 5 ? (int)$value['id'] : null,
                'role_id' => $setType == 4 ? (int)$value['id'] : '',
                'set_type' => $setType,
                'subject_check' => (int)$value['subject_check'],
                'user_id' => $setType == 3 ? $value['id'] : '',
                'subject_values' => empty($subjectValues) ? '' : json_encode($subjectValues),
                'flowout' => 1
            ];
            $result = $this->chargeSet($setData);
            if (isset($result['code'])) {
                $info['error']++;
                $data[$key]['importResult'] = importDataFail();
                $data[$key]['importReason'] = importDataFail(trans($result['code'][1].'.'.$result['code'][0]));
                continue;
            }
            $info['success']++;
            $data[$key]['importResult'] = importDataSuccess();
        }

        return ['data' => $data, 'info' => $info];
    }
    // 导入科目预警值
    public function getchargeWarningSubjectFields() {
        // 科目
        $typeArray = [];
        $types = app($this->chargeTypeRepository)->getChargeTypeList([]);
        if (!$types->isEmpty()) {
            foreach ($types as $value) {
                $typeArray[] = [
                    'charge_type_name' => $value->charge_type_name,
                    'charge_type_id' => $value->charge_type_id,
                    'type_value' => ''
                ];
            }
        }
        return [
            'sheetName' => '费用科目信息',
            'header' => [
                'charge_type_id' => ['data'  => '科目id'],
                'charge_type_name' => ['data'  => '科目名称'],
                'type_value' => ['data'  => '科目预警值']
            ],
            'data' => $typeArray
        ];
    } 
    public function importChargeWarningSubject($data, $param) {
        $info = [
            'total'   => count($data),
            'success' => 0,
            'error'   => 0,
        ];

        $array = [];
        foreach ($data as $key => $value) {
            $id = trim($value['charge_type_id']);
            $array[$id] = $value['type_value'];
        }
        
        $tempTypes = [];
        $types = app($this->chargeTypeRepository)->getChargeTypeList([]);
        if (!$types->isEmpty()) {
            foreach ($types as $key => $value) {
                if ($value['has_children'] == 0) {
                    $temp = [
                        'charge_type_name' => $value->charge_type_name,
                        'charge_type_id' => $value->charge_type_id,
                        'type_value' => $array[$value->charge_type_id] ?? 0
                    ];
                    $tempTypes[$value['charge_type_parent']][] = $temp;
                }
            }
        }
        Cache::forever($param['user_info']['user_id'].'_charge_subject_warning', $tempTypes);

        return compact('data', 'info');
    }
    public function getChargeSubjectWarning($own) {
        return Cache::get($own['user_id'].'_charge_subject_warning') ?? [];
    }
    public function getChargeAddFields($param) {
        $template = [
            [
                'sheetName' => trans('charge.add_charge'),
                'header' => [
                    'user_id'  => [
                        'data'  => trans('charge.reimburser'),
                        'style' => ['width' => 15]
                    ],
                    'reason'     => [
                        'data'  => trans('charge.reason').'('.trans('charge.required').')',
                        'style' => ['width' => 40]
                    ],
                    'payment_date' => [
                        'data'  => trans('charge.payment_date').'('.trans('charge.required').')',
                        'style' => ['width' => 15]
                    ],
                    'charge_cost' => [
                        'data'  => trans('charge.charge_value').'('.trans('charge.required').')',
                        'style' => ['width' => 15]
                    ],
                    'charge_type_id' => [
                        'data'  => trans('charge.charge_subject').'('.trans('charge.required').')',
                        'style' => ['width' => 15]
                    ],
                    'charge_undertaker' => [
                        'data'  => trans('charge.charge_undertaker').'('.trans('charge.required').')',
                        'style' => ['width' => 20]
                    ],
                    'undertake_user' => [
                        'data'  => '费用承担用户',
                        'style' => ['width' => 15]
                    ],
                    'undertake_dept' => [
                        'data'  => '费用承担部门',
                        'style' => ['width' => 15]
                    ],
                    'project_id' => [
                        'data'  => '费用承担项目',
                        'style' => ['width' => 15]
                    ],
                ]
            ],
            [
                'sheetName' => trans('charge.charge_undertaker'),
                'header' => [
                    'set_type'   => ['data'  => trans('charge.charge_undertaker'), 'style' => ['width' => 20]],
                    'value'     => ['data'  => '值']
                ],
                'data' => [
                    ['set_type' => '用户', 'value' => 1],
                    ['set_type' => '部门', 'value' => 2],
                    ['set_type' => '公司', 'value' => 3],
                    ['set_type' => '项目', 'value' => 4]
                ]
            ]
        ];
        // 科目信息
        $typeArray = [];
        $types = app($this->chargeTypeRepository)->getChargeTypeList([]);
        if (!$types->isEmpty()) {
            $typesArr = array_column($types->toArray(), 'charge_type_name', 'charge_type_id');
            foreach ($types as $value) {
                if ($value->has_children == 0) {
                    $temp = [];
                    $name = '';
                    $level = explode(',', $value->type_level);
                    foreach ($level as $v) {
                        if (!empty($v)) {
                            $name .= $typesArr[$v] . '--';
                        }
                    }
                    $name .= $value->charge_type_name;
                    $temp['charge_type_name'] = $name;
                    $temp['charge_type_id'] = $value->charge_type_id;
                    $typeArray[] = $temp;
                }
            }
        }
        $template[] = [
            'sheetName' => trans('charge.subject'),
            'header' => [
                'charge_type_name'   => ['data'  => trans('charge.subject'), 'style' => ['width' => 40]],
                'charge_type_id'     => ['data'  => trans('charge.type_id')]
            ],
            'data' => $typeArray
        ];
        // 用户信息
        $userTempData = [];
        $userList = app($this->userRepository)->getUserList([
            'fields' => ['user_id', 'user_accounts', 'user_name']
        ]);
        if ($userList) {
            foreach ($userList as $key => $value) {
                $userTempData[$key]['user_name'] = $value['user_name'];
                $userTempData[$key]['user_id'] = $value['user_id'];
            }
        }
        $template[] = [
            'sheetName' => '用户信息',
            'header' => [
                'user_name'   => ['data'  => '用户名', 'style' => ['width' => 20]],
                'user_id'     => ['data'  => '用户id']
            ],
            'data' => $userTempData
        ];
        // 部门信息
        $deptTempData = [];
        $deptData = app($this->departmentService)->listDept([]);
        if ($deptData) {
            foreach ($deptData['list'] as $k => $v) {
                $deptTempData[$k]['dept_id'] = $v['dept_id'];
                // 优化，本身存在dept_name,不需要单独查询获取
                if (isset($v['dept_name'])) {
                    $deptTempData[$k]['dept_name'] = $v['dept_name'];
                } else {
                    $deptTempData[$k]['dept_name'] = app($this->userService)->getDeptPathByDeptId($v['dept_id']);
                }
            }
        }
        $template[] = [
            'sheetName' => '部门信息',
            'header' => [
                'dept_name'   => ['data'  => '部门名称', 'style' => ['width' => 20]],
                'dept_id'     => ['data'  => '部门id']
            ],
            'data' => $deptTempData
        ];
        // 项目信息
        $projectTempData = [];
        $projectData = app($this->projectService)->getProjectAllByUserId($param['user_id']);
        if ($projectData) {
            foreach ($projectData as $k => $v) {
                $projectTempData[$k]['manager_id'] = $v['manager_id'];
                $projectTempData[$k]['manager_name'] = $v['manager_name'];
            }
        }
        $template[] = [
            'sheetName' => '项目信息',
            'header' => [
                'manager_name'   => ['data'  => '项目名称', 'style' => ['width' => 20]],
                'manager_id'     => ['data'  => '项目id']
            ],
            'data' => $projectTempData
        ];

        return $template;
    }

    public function importCharge($data, $param) {
        $info = [
            'total'   => count($data),
            'success' => 0,
            'error'   => 0,
        ];

        foreach ($data as $key => $value) {
            if (!isset($value['user_id']) || empty($value['user_id'])) {
                $value['user_id'] = $param['user_info']['user_id'];
            }
            if (!isset($value['reason']) || empty($value['reason'])) {
                $info['error']++;
                $data[$key]['importResult'] = importDataFail();
                $data[$key]['importReason'] = importDataFail(trans('charge.0x020013'));
                continue;
            }
            if (!isset($value['payment_date']) || empty($value['payment_date'])) {
                $info['error']++;
                $data[$key]['importResult'] = importDataFail();
                $data[$key]['importReason'] = importDataFail(trans('charge.0x020035'));
                continue;
            }
            if (!isset($value['charge_cost']) || empty($value['charge_cost'])) {
                $info['error']++;
                $data[$key]['importResult'] = importDataFail();
                $data[$key]['importReason'] = importDataFail(trans('charge.0x020025'));
                continue;
            }
            if (!preg_match("/^(\+|-)?([1-9][0-9]*(\.\d+)?|(0\.(?!0+$)\d+))$/", $value['charge_cost'])) {
                $info['error']++;
                $data[$key]['importResult'] = importDataFail();
                $data[$key]['importReason'] = importDataFail(trans('charge.0x020024'));
                continue;
            }
            if (!isset($value['charge_type_id']) || empty($value['charge_type_id'])) {
                $info['error']++;
                $data[$key]['importResult'] = importDataFail();
                $data[$key]['importReason'] = importDataFail(trans('charge.0x020036'));
                continue;
            }
            if (!isset($value['charge_undertaker']) || empty($value['charge_undertaker'])) {
                $info['error']++;
                $data[$key]['importResult'] = importDataFail();
                $data[$key]['importReason'] = importDataFail(trans('charge.0x020037'));
                continue;
            }
            if (!in_array($value['charge_undertaker'], [1, 2, 3, 4])) {
                $info['error']++;
                $data[$key]['importResult'] = importDataFail();
                $data[$key]['importReason'] = importDataFail(trans('charge.0x020016'));
                continue;
            }
            if ($value['charge_undertaker'] == 1) {
                if (!isset($value['undertake_user']) || empty($value['undertake_user'])) {
                    $info['error']++;
                    $data[$key]['importResult'] = importDataFail();
                    $data[$key]['importReason'] = importDataFail(trans('charge.0x020038'));
                    continue;
                }
            }
            if ($value['charge_undertaker'] == 2) {
                if (!isset($value['undertake_dept']) || empty($value['undertake_dept'])) {
                    $info['error']++;
                    $data[$key]['importResult'] = importDataFail();
                    $data[$key]['importReason'] = importDataFail(trans('charge.0x020039'));
                    continue;
                }
            }
            if ($value['charge_undertaker'] == 4) {
                if (!isset($value['project_id']) || empty($value['project_id'])) {
                    $info['error']++;
                    $data[$key]['importResult'] = importDataFail();
                    $data[$key]['importReason'] = importDataFail(trans('charge.0x020018'));
                    continue;
                }
            }
            $result = $this->addNewCharge($value);
            if (isset($result['code'])) {
                $info['error']++;
                $data[$key]['importResult'] = importDataFail();
                $data[$key]['importReason'] = importDataFail(trans($result['code'][1].'.'.$result['code'][0]));
                continue;
            }
            $info['success']++;
            $data[$key]['importResult'] = importDataSuccess();
        }

        return ['data' => $data, 'info' => $info];
    }
    // 预警方式下拉数据源，自定义字段解析使用
    public function getChargeAlertMethods($data = []) {
        $data = $this->parseParams($data);
        $resultData = [
            ['alert_method' => 1, 'alert_method_name' => trans('charge.year')],
            ['alert_method' => 2, 'alert_method_name' => trans('charge.quarter')],
            ['alert_method' => 3, 'alert_method_name' => trans('charge.month')],
            ['alert_method' => 4, 'alert_method_name' => trans('charge.customize')]
        ];
        return $this->handleFieldsSearch($data, $resultData, 'alert_method', 'alert_method_name');
    }
    public function getSetTypes($data = []) {
        $data = $this->parseParams($data);
        $resultData = [
            ['set_type' => 1, 'set_type_name' => trans('charge.company')],
            ['set_type' => 2, 'set_type_name' => trans('charge.department')],
            ['set_type' => 3, 'set_type_name' => trans('charge.user')],
            ['set_type' => 4, 'set_type_name' => trans('charge.role')],
            ['set_type' => 5, 'set_type_name' => trans('charge.project')]
        ];
        return $this->handleFieldsSearch($data, $resultData, 'set_type', 'set_type_name');
    }
    private function handleFieldsSearch($data, $resultData, $idField, $nameField) {
        // 传search，要查询
        if(isset($data["search"])) {
            $stateIdArray = isset($data["search"][$idField]) ? $data["search"][$idField] : [];
            $stateNameArray = isset($data["search"][$nameField]) ? $data["search"][$nameField] : [];
            $searchResultData = [];
            if(count($stateIdArray)) {
                if (isset($stateIdArray[1])) {
                    if ($stateIdArray[1] == "in") {
                        $stateIdArray = $stateIdArray[0] ?? [];
                    } else if ($stateIdArray[1] == "=") {
                        $stateIdArray = [$stateIdArray[0]];
                    }
                }
                foreach ($stateIdArray as $idKey => $idValue) {
                    foreach ($resultData as $key => $value) {
                        if($value[$idField] == $idValue) {
                            array_push($searchResultData, $value);
                        }
                    }
                }
            } else if(count($stateNameArray) == 2) {
                // 查询字段值
                $nameSearchString = $stateNameArray[0];
                // 查询标识
                $nameSearchSign = $stateNameArray[1];
                if($nameSearchSign == 'like') {
                    foreach ($resultData as $key => $value) {
                        $stateName = $value[$nameField];
                        if(stripos($stateName, $nameSearchString) !== false) {
                            array_push($searchResultData, $value);
                        }
                    }
                }
            }
            return $searchResultData;
        } else {
            return $resultData;
        }
    }
    // 指定时间范围内已报销额度
    public function getDataSourceInDateRange($data, $type) {
        $id = $data['parent_id'] ?? '';
        $startTime = $data['start_time'] ?? '';
        $endTime = $data['end_time'] ?? '';
        $date = [];
        if (empty($startTime) && !empty($endTime)) {
            $date = [$endTime, '<='];
        }
        if (!empty($startTime) && empty($endTime)) {
            $date = [$startTime, '>='];
        }
        if (!empty($startTime) && !empty($endTime)) {
            $date = [[$startTime, $endTime], 'between'];
        }

        $param = [];
        if (!empty($date)) {
            $param['payment_date'] = $date;
        }
        
        switch($type) {
            case 'user':
                if (empty($id)) return '';
                $param['undertake_user'] = [$id];
                $param['charge_undertaker'] = [1];
                $userAmount = app($this->chargeListRepository)->getStatistic($param);
                return ['userAmountInDate' => $userAmount];
            case 'dept':
                if (empty($id)) return '';
                $param['undertake_dept'] = [$id];
                $param['charge_undertaker'] = [2];
                $deptAmount = app($this->chargeListRepository)->getStatistic($param);
                return ['deptAmountInDate' => $deptAmount];
            case 'company':
                $param['charge_undertaker'] = [3];
                $companyAmount = app($this->chargeListRepository)->getStatistic($param);
                return ['companyAmountInDate' => $companyAmount];
            case 'project':
                if (empty($id)) return '';
                $param['project_id'] = [$id];
                $param['charge_undertaker'] = [4];
                $projectAmount = app($this->chargeListRepository)->getStatistic($param);
                return ['projectAmountInDate' => $projectAmount];
            break;
            default:
                return '';
        }
    }

    public function handleLogParams($user , $content , $relation_id = '' ,$relation_table = '', $relation_title='')
    {
        $data = [
            'creator' => $user,
            'content' => $content,
            'relation_table' => $relation_table,
            'relation_id' => $relation_id,
            'relation_title' => $relation_title
        ];
        return $data;
    }
}
