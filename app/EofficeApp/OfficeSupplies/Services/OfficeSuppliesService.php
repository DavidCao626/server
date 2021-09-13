<?php

namespace App\EofficeApp\OfficeSupplies\Services;

use App\EofficeApp\EofficeCase\Services\Redis;
use App\EofficeApp\OfficeSupplies\Permissions\OfficeSuppliesCode;
use DB;
use Eoffice;
use Illuminate\Support\Facades\Cache;
use App\EofficeApp\Base\BaseService;
use Illuminate\Support\Collection;


/**
 * 办公用品service
 *
 * @author 朱从玺
 *
 * @since  2015-11-03 创建
 */
class OfficeSuppliesService extends BaseService
{
    protected $repository;
    protected $formModelingService;
    protected $typeRepository;
    protected $typePermissionRepository;
    protected $storageRepository;
    protected $applyRepository;
    protected $userSystemInfoRepository;
    protected $systemComboboxService;
    protected $attachmentService;
    protected $menuService;
    protected $langService;
    protected $userService;

    public function __construct()
    {
        parent::__construct();

        $this->officeSuppliesSelectField = [
            'apply_type' => 'USE_TYPE',
            'receive_way' => 'GET_TYPE'
        ];

        $this->repository = 'App\EofficeApp\OfficeSupplies\Repositories\OfficeSuppliesRepository';
        $this->typeRepository = 'App\EofficeApp\OfficeSupplies\Repositories\OfficeSuppliesTypeRepository';
        $this->typePermissionRepository = 'App\EofficeApp\OfficeSupplies\Repositories\OfficeSuppliesPermissionRepository';
        $this->storageRepository = 'App\EofficeApp\OfficeSupplies\Repositories\OfficeSuppliesStorageRepository';
        $this->applyRepository = 'App\EofficeApp\OfficeSupplies\Repositories\OfficeSuppliesApplyRepository';
        $this->userSystemInfoRepository = 'App\EofficeApp\User\Repositories\UserSystemInfoRepository';
        $this->systemComboboxService = 'App\EofficeApp\System\Combobox\Services\SystemComboboxService';
        $this->attachmentService = 'App\EofficeApp\Attachment\Services\AttachmentService';
        $this->menuService = 'App\EofficeApp\Menu\Services\UserMenuService';
        $this->langService = 'App\EofficeApp\Lang\Services\LangService';
        $this->userService = 'App\EofficeApp\User\Services\UserService';
        $this->formModelingService = 'App\EofficeApp\FormModeling\Services\FormModelingService';
    }

    /**
     * [createOfficeSuppliesType 创建办公用品类型]
     *
     * @param  [array]                   $typeData [创建数据]
     *
     * @return [bool]                              [创建结果]
     * @since  2015-11-05 创建
     *
     * @author 朱从玺
     *
     */
    public function createOfficeSuppliesType($typeData)
    {
        if (isset($typeData['parent_id']) && is_numeric($typeData['parent_id'])) {
            $parent = app($this->typeRepository)->hasTypeById($typeData['parent_id']);
            if (!$parent) {
                return ['code' => ['0x043013', 'officesupplies']];
            }
        } else {
            $typeData['parent_id'] = null;
        }

        $result = app($this->typeRepository)->insertData($typeData);

        if (!$result) {
            return ['code' => ['0x000003', 'common']];
        }
        //创建type成功
        $type_id = $result['id'];

        if (isset($typeData['manager']) && !empty($typeData['manager'])) {
            //增加至权限表

            $res = $this->createTypePermmission($typeData['manager'], $type_id);
        }
        return $result;
    }

    /**
     * [createTypePermission 创建办公用品类型权限表]
     *
     * @auther Jason
     *
     *
     */
    public function createTypePermmission($permission_info, $type_id)
    {
        $apply_manager_all = 0;
        $check_manager_all = 0;
        $storage_manager_all = 0;
        $permission_init = [
            'apply_manager_all' => 0,
            'check_manager_all' => 0,
            'storage_manager_all' => 0,
            'apply' => [],
            'check' => [],
            'storage' => [],
        ];
        $permission_info = array_merge($permission_init, $permission_info);
        if (isset($permission_info['apply_manager_all'])) {
            $apply_manager_all = $permission_info['apply_manager_all'];
            unset($permission_info['apply_manager_all']);
        }
        if (isset($permission_info['check_manager_all'])) {
            $check_manager_all = $permission_info['check_manager_all'];
            unset($permission_info['check_manager_all']);
        }
        if (isset($permission_info['storage_manager_all'])) {
            $storage_manager_all = $permission_info['storage_manager_all'];
            unset($permission_info['storage_manager_all']);
        }
        $result = null;
        foreach ($permission_info as $key => $value) {
            $info = [];
            $info['office_supplies_type_id'] = $type_id;
            if ($key == 'apply') {
                $info['permission_type'] = 0;
                $info['manager_all'] = $apply_manager_all;
            }
            if ($key == 'check') {
                $info['permission_type'] = 1;
                $info['manager_all'] = $check_manager_all;
            }
            if ($key == 'storage') {
                $info['manager_all'] = $storage_manager_all;
                $info['permission_type'] = 2;
            }
            if (isset($info['permission_type'])) {
                if (isset($info['manager_all']) && $info['manager_all'] == 1) {
                    $value['dept_id'] = null;
                    $value['role_id'] = null;
                    $value['user_id'] = null;
                } else {
                    $info['manager_all'] = 0;
                }
                $info['manager_dept'] = isset($value['dept_id']) && !empty($value['dept_id']) ? json_encode($value['dept_id']) : json_encode([]);
                $info['manager_role'] = isset($value['role_id']) && !empty($value['role_id']) ? json_encode($value['role_id']) : json_encode([]);
                $info['manager_user'] = isset($value['user_id']) && !empty($value['user_id']) ? json_encode($value['user_id']) : json_encode([]);
                $result = app($this->typePermissionRepository)->modifyPermission($info);
            }
        }
        return $result;
    }

    /**
     * [modifyOfficeSuppliesType 编辑办公用品类型]
     *
     * @param  [int]                     $typeId      [类型ID]
     * @param  [array]                   $newTypeData [编辑数据]
     *
     * @return [bool]                                 [编辑结果]
     * @since  2015-11-05 创建
     *
     * @author 朱从玺
     *
     */
    public function modifyOfficeSuppliesType($typeId, $newTypeData)
    {
        $where = ['id' => [$typeId]];

        $result = app($this->typeRepository)->updateData($newTypeData, $where);
        //编辑type成功
        if (isset($newTypeData['manager']) && !empty($newTypeData['manager'])) {
            //增加至权限表
            $res = $this->createTypePermmission($newTypeData['manager'], $typeId);
        }
        return $result;
    }

    /**
     * [getOfficeSuppliesType 获取办公用品类型数据]
     *
     * @param  [int]                  $typeId [类型ID]
     *
     * @return [array]                        [查询结果]
     * @since  2015-11-05 创建
     *
     * @author 朱从玺
     *
     */
    public function getOfficeSuppliesType($typeId)
    {
        $type = app($this->typeRepository)->getTypeInfoByID($typeId);
        $result = [];
        if ($type) {
            if (count($type['type_has_many_permission']) > 0) {
                $default_permission = [
                    'dept_id' => [],
                    'role_id' => [],
                    'user_id' => [],
                ];
                $result['manager']['apply'] = $default_permission;
                $result['manager']['check'] = $default_permission;
                $result['manager']['storage'] = $default_permission;

                foreach ($type['type_has_many_permission'] as $key => $value) {
                    if ($value['permission_type'] == 0) {
                        $result['manager']['apply'] = [
                            'dept_id' => json_decode($value['manager_dept'], true),
                            'role_id' => json_decode($value['manager_role'], true),
                            'user_id' => json_decode($value['manager_user'], true),
                        ];
                        $result['manager']['apply_manager_all'] = $value['manager_all'];
                    }
                    if ($value['permission_type'] == 1) {
                        $result['manager']['check'] = [
                            'dept_id' => json_decode($value['manager_dept'], true),
                            'role_id' => json_decode($value['manager_role'], true),
                            'user_id' => json_decode($value['manager_user'], true),
                        ];
                        $result['manager']['check_manager_all'] = $value['manager_all'];
                    }
                    if ($value['permission_type'] == 2) {
                        $result['manager']['storage'] = [
                            'dept_id' => json_decode($value['manager_dept'], true),
                            'role_id' => json_decode($value['manager_role'], true),
                            'user_id' => json_decode($value['manager_user'], true),
                        ];
                        $result['manager']['storage_manager_all'] = $value['manager_all'];
                    }
                }
            }
        }
        if (isset($type['type_has_many_permission'])) unset($type['type_has_many_permission']);
        $result = array_merge($result, $type);
        return $result;
//        return app($this->typeRepository)->getDetail($typeId);
    }


    /**
     * [getOfficeSuppliesTypeList 获取办公用品类型列表]
     *
     * @param  [array]                  $params [查询参数]
     *
     * @return [array]                          [查询结果]
     * @since  2015-11-05 创建
     *
     * @author 朱从玺
     *
     */
    public function getOfficeSuppliesTypeList($params)
    {
        $params = $this->parseParams($params);
        $typeList = app($this->typeRepository)->getTypeList($params);
        $parent = [];
        $parent_ids = [];
        if ($typeList) {
            $typeList = $typeList->toArray();
            foreach ($typeList as $key => $value) {
                if (!empty($value['type_has_many_supplies_count'])) {
                    $typeList[$key]['has_children'] = 1;
                } else {
                    $typeList[$key]['has_children'] = 0;
                }
                if (!is_numeric($value['parent_id'])) {
                    array_push($parent, $typeList[$key]);
                    array_push($parent_ids, $typeList[$key]['id']);
                    unset($typeList[$key]);
                }
            }
            if (isset($params['last_type']) && $params['last_type'] == 1) {
                //对父级进行过滤，如果没有权限则删除
                // 0 申请 1 审批 2入库
                $permissions = $parent_ids;
                if (isset($params['type_from'])) {
                    $permissions = app($this->typePermissionRepository)->getPermission($parent_ids, $params['type_from'], $params['own']);
                    // var_dump($permissions);
                }
                //$permission_apply = app($this->typePermissionRepository)->getPermission($parent_ids,0,$params['own']);
                //$permission_storage = app($this->typePermissionRepository)->getPermission($parent_ids,2,$params['own']);
                //$permissions = array_merge($permission_apply,$permission_storage);

                foreach ($parent as $k => $v) {
                    $parent[$k]['children'] = [];
                    if (isset($v['id']) && in_array($v['id'], $permissions)) {
                        foreach ($typeList as $key => $val) {
                            if ($v['id'] == $typeList[$key]['parent_id']) {
                                //$typeList[$key]['type_name'] = $v['type_name'].' > '.$typeList[$key]['type_name'];
                                //$list[] = $typeList[$key];
                                $parent[$k]['children'][] = $typeList[$key];
                            }
                        }
                    } else {
                        unset($parent[$k]);
                    }
                }
                return array_values($parent);
            } else {
                //二级列表
                foreach ($parent as $key => $value) {
                    $parent[$key]['type_has_many_children'] = [];
                    foreach ($typeList as $k => $v) {
                        if ($v['parent_id'] == $value['id']) {
                            array_push($parent[$key]['type_has_many_children'], $v);
                            unset($typeList[$k]);
                        }
                    }
                    if (count($parent[$key]['type_has_many_children']) > 0) {
                        $parent[$key]['has_children'] = 1;
                    }
                    // 搜索时如果没有子类不显示父级分类
                    if (isset($params['search']) && isset($params['search']['type_name']) && $parent[$key]['has_children'] == 0) {
                        unset($parent[$key]);
                    }
                }
                return array_values($parent);
            }
        }
        return [];
    }

    /**
     * [getOfficeSuppliesAllTypeList获取所有的分类信息，仅提供两个ImportExportFliter::suppliesType 和 importOfficeSuppliesFilter方法和接口使用]
     * @param $params
     * @return mixed
     */
    function getOfficeSuppliesAllTypeList($params)
    {
        $params = $this->parseParams($params);
        $typeList = app($this->typeRepository)->getTypeList($params);
        if ($typeList) {
            $typeList = $typeList->toArray();
            foreach ($typeList as $key => $value) {
                if (!empty($value['type_has_many_supplies_count'])) {
                    $typeList[$key]['has_children'] = 1;
                } else {
                    $typeList[$key]['has_children'] = 0;
                }
            }
        }
        return $typeList;
    }

    /**
     * [getOfficeSuppliesSecondTypeList 获取二级分类列表]
     * @param $params
     * @return array
     */
    function getOfficeSuppliesSecondTypeList($params, $own, $typeFrom = null)
    {
        $params = $this->parseParams($params);
        $typeList = app($this->typeRepository)->getTypeList($params);
        $parent = [];
        $parent_ids = [];
        $list = [];
        if ($typeList) {
            $typeList = $typeList->toArray();
            foreach ($typeList as $key => $value) {
                if (!empty($value['type_has_many_supplies_count'])) {
                    $typeList[$key]['has_children'] = 1;
                } else {
                    $typeList[$key]['has_children'] = 0;
                }
                if (!is_numeric($value['parent_id'])) {
                    array_push($parent, $typeList[$key]);
                    array_push($parent_ids, $typeList[$key]['id']);
                    unset($typeList[$key]);
                }
            }
            //对父级进行过滤，如果没有权限则删除
            //选择二级分类，使用该接口必须要有入库权限
            // 0 申请 1 审批 2入库
            //全部
            $permissions = $parent_ids;
            if (isset($typeFrom)) {
                //有权限过滤
                $permissions = app($this->typePermissionRepository)->getPermission($parent_ids, $typeFrom, $own);
            }
            foreach ($parent as $k => $v) {
                $parent[$k]['children'] = [];
                if (isset($v['id']) && in_array($v['id'], $permissions)) {
                    foreach ($typeList as $key => $val) {
                        if ($v['id'] == $typeList[$key]['parent_id']) {
                            //$typeList[$key]['type_name'] = $v['type_name'].' > '.$typeList[$key]['type_name'];
                            $list[] = $typeList[$key];
                        }
                    }
                } else {
                    unset($parent[$k]);
                }
            }
        }
        return array_values($list);
    }

    /**
     * [getOfficeSuppliesTypeList 获取父级办公用品类型列表]
     *
     * @param  [array]                  $params [查询参数]
     *
     * @return [array]                          [查询结果]
     * @since  2018-08-29 创建
     *
     * @author Jason
     *
     */
    public function getOfficeSuppliesTypeParentList($params)
    {
        $params = $this->parseParams($params);
        //$parent = ["search" => ['parent_id' => null]];
        $parent = ["search" => ['parent_id' => [null]]];
        $typeList = app($this->typeRepository)->getTypeList($parent);
        $result = [];
        if ($typeList) {
            $typeList = $typeList->toArray();
            foreach ($typeList as $key => $value) {
                // 有传入typeID时 为编辑模式，删除当前typeID
                if (isset($params['typeId']) && $params['typeId'] == $value['id']) {
                    //如果为一级分类 返回空
                    if ($value['parent_id'] == null) {
                        return [];
                    }
                    unset($typeList[$key]);
                    continue;
                }
                if (!empty($value['type_has_many_supplies_count'])) {
                    $typeList[$key]['has_children'] = 1;
                } else {
                    $typeList[$key]['has_children'] = 0;
                }
                array_push($result, $typeList[$key]);
            }
        }
        return $result;
    }

    /**
     * [deleteOfficeSuppliesType 删除办公用品类型数据]
     *
     * @param  [int]                 $typeId [类型ID]
     *
     * @return [bool]                        [删除结果]
     * @since  2015-11-05 创建
     *
     * @author 朱从玺
     *
     */
    public function deleteOfficeSuppliesType($typeId)
    {
        //判断该类型下是否存在已创建办公用品
        $where = ['type_id' => [$typeId]];
        $param = ['search' => $where];
        $count = app($this->repository)->getTotal($param);

        if ($count > 0) {
            return ['code' => ['0x043001', 'officesupplies']];
        }
        //判断该类型下是否存在
        $has_child = app($this->typeRepository)->hasTypeByParentId($typeId);
        if ($has_child) {
            return ['code' => ['0x043014', 'officesupplies']];
        }

        $result = app($this->typeRepository)->deleteById($typeId);

        if (!$result) {
            return ['code' => ['0x000003', 'common']];
        }
        //删除权限数据
        app($this->typePermissionRepository)->deletePermissionByTypeId($typeId);

        return $result;
    }

    /**
     * [createOfficeSupplies 创建办公用品]
     *
     * @param  [array]               $data [创建数据]
     *
     * @return [bool]                      [创建结果]
     * @since  2015-11-05 创建
     *
     * @author 朱从玺
     *
     */
    public function createOfficeSupplies($data)
    {
        //启动库存警示
        if (isset($data['stock_remind']) && $data['stock_remind'] == 1) {
            if (!isset($data['remind_min']) || empty($data['remind_min'])) {
                $data['remind_min'] = 0;
            }
            if (!isset($data['remind_max']) || empty($data['remind_max'])) {
                $data['remind_max'] = 0;
            }
        }

        if (isset($data['attachment_id'])) {
            $attachmentId = $data['attachment_id'];
            unset($data['attachment_id']);
        }
        $result = app($this->repository)->insertData($data);
        if (isset($attachmentId) && $attachmentId) {
            app($this->attachmentService)->attachmentRelation("office_supplies", $result->id, $attachmentId);
        }
        if (!$result) {
            return ['code' => ['0x000003', 'common']];
        }

        return $result;
    }

    /**
     * [modifyOfficeSupplies 编辑办公用品]
     *
     * @param  [int]                $officeSuppliesId [办公用品ID]
     *
     * @param  [array]              $newData          [编辑数据]
     *
     * @return [bool]                                 [编辑结果]
     * @since  2015-11-05 创建
     *
     * @author 朱从玺
     *
     */
    public function modifyOfficeSupplies($officeSuppliesId, $newData)
    {
        $where = ['id' => [$officeSuppliesId]];
        if (isset($newData['attachment_id'])) {
            app($this->attachmentService)->attachmentRelation("office_supplies", $newData['id'], $newData['attachment_id']);
        }
        unset($newData['attachment_id']);

        //启动库存警示
        if (isset($newData['stock_remind']) && $newData['stock_remind'] == 1) {
            if (!isset($newData['remind_min']) || empty($newData['remind_min'])) {
                $newData['remind_min'] = 0;
            }
            if (!isset($newData['remind_max']) || empty($newData['remind_max'])) {
                $newData['remind_max'] = 0;
            }
        }
        $result = app($this->repository)->updateDataBatch($newData, $where);
        if (!$result) {
            return ['code' => ['0x000003', 'common']];
        }
        return $result;
    }

    /**
     * [getOfficeSupplies 获取办公用品数据]
     *
     * @param  [int]             $officeSuppliesId [办公用品ID]
     *
     * @return [json]                              [查询结果]
     * @since  2015-11-05 创建
     *
     * @author 朱从玺
     *
     */
    public function getOfficeSupplies($officeSuppliesId)
    {
        $officeSuppliesInfo = app($this->repository)->getDetail($officeSuppliesId);
        if (empty($officeSuppliesInfo)) {
            return [];
        }
        if (!$officeSuppliesInfo->stock_remind) {
            $officeSuppliesInfo->remind_max = '';
            $officeSuppliesInfo->remind_min = '';
        }
        if ($officeSuppliesInfo->usage == '1') {
            $officeSuppliesInfo->usage_name = trans("officesupplies.borrow");
        } else {
            $officeSuppliesInfo->usage_name = trans("officesupplies.use");
        }
        $officeSuppliesInfo->attachment_id = app($this->attachmentService)->getAttachmentIdsByEntityId(['entity_table' => 'office_supplies', 'entity_id' => $officeSuppliesId]);
        // 获取最新入库的办公用品价格数据源
//        $price = DB::Table('office_supplies_storage')->select('price')->where('office_supplies_id', $officeSuppliesId)->orderBy('id', 'desc')->first();
//        if (isset($price)) {
//            $price = $price->price;
//        } else {
//            $price = trans("officesupplies.non_storage_record");
//        }
//        $officeSuppliesInfo->price = $price;
        $officeSuppliesInfo = $officeSuppliesInfo->toArray();
        $officeSuppliesInfo['current_stock'] = $officeSuppliesInfo['stock_total'] ?? 0;

        return $officeSuppliesInfo;
    }

    /**
     * [getOfficeSuppliesList 获取办公用品列表]
     *
     * @return [array]                       [查询结果]
     * @since  2015-11-05 创建
     *
     * @author 朱从玺
     *
     */
    public function getOfficeSuppliesList($param, $own, $typeFrom = null)
    {
        $param = $this->parseParams($param);
        $allSuppliesList = app($this->typeRepository)->getOfficeSuppliesList($param);
        if (count($allSuppliesList) > 0) {
            $allSuppliesList = $allSuppliesList->toArray();
        } else {
            $allSuppliesList = [];
        }
        $type_ids = [];
        foreach ($allSuppliesList as $key => $val) {
            //$parent_name = isset($val['type_has_one_parent']['type_name']) ? $val['type_has_one_parent']['type_name']." > ":"";
            //$allSuppliesList[$key]['type_name'] = $parent_name.$allSuppliesList[$key]['type_name'];
            if (!in_array($val['parent_id'], $type_ids)) {
                $type_ids[] = $val['parent_id'];
            }
        }

        // 0 申请 1 审批 2入库
        $list = [];
        if (count($type_ids) > 0 && isset($typeFrom)) {
            $permission_type = $typeFrom;
            $permission = app($this->typePermissionRepository)->getPermission($type_ids, $permission_type, $own);
            if (count($permission) > 0) {
                foreach ($allSuppliesList as $key => $val) {
                    if (in_array($val['parent_id'], $permission)) {
                        $list[] = $allSuppliesList[$key];
                    }
                }
                return $list;
            } else {
                return $list;
            }
        }
        return $allSuppliesList;
    }

    /**
     * [getSuppliesNormalList 获取正常办公用品列表/即没有type]
     *
     * @method 朱从玺
     *
     * @return [object]                [查询结果]
     */
    public function getSuppliesNormalList($params, $own, $type_from = null)
    {
        // 0 申请 1 审批 2入库
        $params = $this->parseParams($params);

        if (isset($type_from)) {
            $office_supplies_id_arr = $this->getAllowSuppliesId($own, $type_from);
            $params['extra']['search']['id'] = [$office_supplies_id_arr, 'in'];
        }

        //$list = $this->response(app($this->repository), 'getTotal', 'getSuppliesList', $params);
        $list = $this->response(app($this->repository), 'getSuppliesTotal', 'getSuppliesList', $params);
        // 处理附件
        if (!$list['list']->isEmpty()) {
            $list['list'] = $list['list']->toArray();
            foreach ($list['list'] as $key => $value) {
                $attachments = [];
                foreach ($value['attachments'] as $v) {
                    $attachments[] = $v['attachment_id'];
                }
                $list['list'][$key]['attachments'] = $attachments;
            }
        }
        return $list;
    }

    /**
     * [deleteOfficeSupplies 删除办公用品]
     *
     * @param  [int]                $officeSuppliesId [办公用品ID]
     *
     * @return [json]                                 [删除结果]
     * @since  2015-11-05 创建
     *
     * @author 朱从玺
     *
     */
    public function deleteOfficeSupplies($officeSuppliesId)
    {
        $where = ['office_supplies_id' => [$officeSuppliesId]];
        $param = ['search' => $where];
        $storageTotal = app($this->storageRepository)->getTotal($param);
        $applyTotal = app($this->applyRepository)->getTotal($param);

        if ($storageTotal > 0) {
            return ['code' => ['0x043009', 'officesupplies']];
        } elseif ($applyTotal > 0) {
            return ['code' => ['0x043009', 'officesupplies']];
        }

        $result = app($this->repository)->deleteById($officeSuppliesId);

        if (!$result) {
            return ['code' => ['0x000003', 'common']];
        }

        $officeSuppliesAttachmentData = ['entity_table' => 'office_supplies', 'entity_id' => $officeSuppliesId];
        app($this->attachmentService)->deleteAttachmentByEntityId($officeSuppliesAttachmentData);

        return $result;
    }

    /**
     * [getCreateNo 获取入库编号]
     *
     * @param  [array]       $param [查询条件]
     *
     * @return [array]              [查询结果]
     * @since  2015-11-05 创建
     *
     * @author 朱从玺
     *
     */
    public function getCreateNo($param)
    {
        $result = [];

        //如果有办公用品ID
        if (isset($param['office_supplies_id']) && $param['office_supplies_id']) {
            $officeSupplies = app($this->repository)->getDetail($param['office_supplies_id']);
            $officeSuppliesType = app($this->typeRepository)->getDetail($officeSupplies['type_id']);
            $typeNo = $officeSuppliesType['type_no'];
            $officeSuppliesNo = $officeSupplies['office_supplies_no'];
            //入库单据号前缀 类别编号+办公用品编号
            $prefixBill = $typeNo . $officeSuppliesNo;

            $result['office_supplies_id'] = $param['office_supplies_id'];
            $result['office_supplies_name'] = $officeSupplies->office_supplies_name;
            $result['stock_surplus'] = $officeSupplies->stock_surplus;
        } else {
            $result['office_supplies_id'] = '';
            $result['office_supplies_name'] = '';
            $result['stock_surplus'] = '';
        }

        if ($param['no_type'] == 'storage') {
            //获取入库编号
            $storageParams['search'] = [
                'created_at' => [[date('Y-m-d 00:00:00'), date('Y-m-d 23:59:59')], 'between'],
            ];
            $storageParams['withTrashed'] = true;
            $count = app($this->storageRepository)->getStorageCount($storageParams);
            $count = $count ? ++$count : 1;
            $count = str_pad($count, 3, 0, STR_PAD_LEFT);

            $result['storage_bill'] = date('Ymd') . $count;

            if (isset($param['office_supplies_id']) && $param['office_supplies_id']) {
                $result['storage_bill'] = $prefixBill . $result['storage_bill'];
            }
        } elseif ($param['no_type'] == 'apply') {
            $applyNo = Cache::get('office_supplies_apply_max_no');
            if ($applyNo) {
                $diff = (int)date('Ymd') - (int)mb_substr($applyNo, 0, 8);
                if ($diff > 0) {
                    $result['apply_bill'] = date('Ymd') . '001';
                } else {
                    $result['apply_bill'] = $applyNo + 1;
                }
            } else {
                $apply = app($this->applyRepository)->getApplyMaxNo();
                if ($apply) {
                    $maxNo = $apply->apply_bill;
                    $diff = (int)date('Ymd') - (int)mb_substr($maxNo, 0, 8);
                    if ($diff > 0) {
                        $result['apply_bill'] = date('Ymd') . '001';
                    } else {
                        $result['apply_bill'] = $maxNo + 1;
                    }
//                    if (strpos($maxNo, date('Ymd')) === false) {
//                        $result['apply_bill'] = date('Ymd') . '001';
//                    } else {
//                        $result['apply_bill'] = $maxNo + 1;
//                    }

                } else {
                    $result['apply_bill'] = date('Ymd') . '001';
                }
            }
            Cache::forever('office_supplies_apply_max_no', $result['apply_bill']);
            //  Cache::forget('office_supplies_apply_max_no');
        }

        return $result;
    }

    /**
     * [createStorageRecord 创建入库记录]
     *
     * @param  [array]              $storageRecord [创建数据]
     *
     * @return [bool]                              [创建结果]
     * @since  2015-11-05 创建
     *
     * @author 朱从玺
     *
     */
    public function createStorageRecord($storageRecord)
    {
        $storage = [];
        // $storageRecord['money'] = $storageRecord['price'] * $storageRecord['storage_amount'];
        if (isset($storageRecord['id']) && !empty($storageRecord['id'])) {
            $id = $storageRecord['id'];
        } else {
            return $this->createOfficeStorage($storageRecord);
        }
        if (isset($storageRecord['office_supplies_id']) && !empty($storageRecord['office_supplies_id'])) {
            $storage['type_id'] = app($this->repository)->getDetail($storageRecord['office_supplies_id'])->type_id;
        } else {
            return false;
        }
        if (isset($storageRecord['operator']) && !empty($storageRecord['operator'])) {
            $storage['operator'] = $storageRecord['operator'];
        } else {
            $storage['operator'] = '';
        }
//        $result = app($this->storageRepository)->insertData($storageRecord);
        // 更新办公用品种类字段，(因为先调用公共的自定义字段方法，再调用自己的方法)
        $result = DB::Table('office_supplies_storage')->where('id', $id)->update($storage);
        if (!$result) {
            return ['code' => ['0x000003', 'common']];
        }
        // 如果$storageRecord['storage_amount']没有设置，则为空
        $storageRecord['storage_amount'] = isset($storageRecord['storage_amount']) ? $storageRecord['storage_amount'] : '';
        //更新办公用品表
        $officeSupplies = app($this->repository)->getDetail($storageRecord['office_supplies_id']);
        $storageRecord['arithmetic'] = isset($storageRecord['arithmetic']) ? $storageRecord['arithmetic'] : 'storage_amount';
        //如果没有设置加减符号，则arithmetic默认为1，(因为负数直接可以减)
        if ($storageRecord['arithmetic'] === 'storage_amount') {
            $storageRecord['arithmetic'] = 1;
        }
        if ($storageRecord['arithmetic']) {
            $officeSupplies->stock_total = $officeSupplies->stock_total + $storageRecord['storage_amount'];
            $officeSupplies->stock_surplus = $officeSupplies->stock_surplus + $storageRecord['storage_amount'];
        } else {
            $officeSupplies->stock_total = $officeSupplies->stock_total - $storageRecord['storage_amount'];
            $officeSupplies->stock_surplus = $officeSupplies->stock_surplus - $storageRecord['storage_amount'];
        }

        $modifyOfficeSupplies = $officeSupplies->save();
        return true;
    }

    /**
     * 快速入库
     * @param  [array] $storageRecord 入库信息
     * @return array
     */
    public function createOfficeStorage($storageRecord)
    {
        $storage = [];
        $storageRecord['money'] = $storageRecord['price'] * $storageRecord['storage_amount'];

        $storageRecord['type_id'] = app($this->repository)->getDetail($storageRecord['office_supplies_id'])->type_id;

        if (isset($storageRecord['operator']) && !empty($storageRecord['operator'])) {
            $storage['operator'] = $storageRecord['operator'];
        } else {
            return false;
        }
        $result = app($this->storageRepository)->insertData($storageRecord);
        if (!$result) {
            return ['code' => ['0x000003', 'common']];
        }

        //更新办公用品表
        $officeSupplies = app($this->repository)->getDetail($storageRecord['office_supplies_id']);
        if ($storageRecord['arithmetic']) {
            $officeSupplies->stock_total = $officeSupplies->stock_total + $storageRecord['storage_amount'];
            $officeSupplies->stock_surplus = $officeSupplies->stock_surplus + $storageRecord['storage_amount'];
        } else {
            $officeSupplies->stock_total = $officeSupplies->stock_total - $storageRecord['storage_amount'];
            $officeSupplies->stock_surplus = $officeSupplies->stock_surplus - $storageRecord['storage_amount'];
        }

        $modifyOfficeSupplies = $officeSupplies->save();

        return [
            'status' => 1,
            'dataForLog' => [
                [
                    'table_to' => 'office_supplies_storage',
                    'field_to' => 'id',
                    'id_to' => $result['id']
                ]
            ]
        ];
    }

    /**
     * [flowOutSendCreateStorageRecord 创建入库记录]
     *
     * @param  [array]              $storageRecord [创建数据]
     *
     * @return [bool]                              [创建结果]
     * @since  2017-07-28 创建
     *
     * @author 缪晨晨
     *
     */
    public function flowOutSendCreateStorageRecord($storageRecord)
    {
        // if (empty($storageRecord['operator']) || empty($storageRecord['office_supplies_id']) || empty($storageRecord['price']) || empty($storageRecord['storage_amount']) || empty($storageRecord['storage_date'])) {
        //     return array('code' => array('0x000003', 'common'));
        // }

        // 如果没有传入办公用品名称，就直接执行插入操作
        if (!isset($storageRecord['office_supplies_id']) || empty($storageRecord['office_supplies_id']) || !isset($storageRecord['price']) || !isset($storageRecord['storage_amount'])) {
            if (isset($storageRecord['storage_amount']) && !empty($storageRecord['storage_amount'])) {
                $isPlus = $storageRecord['storage_amount'];
                // 判断入库数量正 负
                if ($isPlus > 0) {
                    $storageRecord['arithmetic'] = 1;
                } else {
                    $storageRecord['arithmetic'] = 0;
                }
            }
            $result = app($this->storageRepository)->insertData($storageRecord);
            if (!$result) {
                return ['code' => ['0x000003', 'common']];
            }
            return true;
        }

        // 获取入库单号
        $storageBillResult = $this->getCreateNo(['no_type' => 'storage', 'office_supplies_id' => $storageRecord['office_supplies_id']]);

        if (!empty($storageBillResult)) {
            $storageRecord['storage_bill'] = $storageBillResult['storage_bill'];
        } else {
            return ['code' => ['0x000003', 'common']];
        }

        $isPlus = $storageRecord['storage_amount'];
        $storageRecord['storage_amount'] = abs($storageRecord['storage_amount']);

        $storageRecord['money'] = $storageRecord['price'] * abs($storageRecord['storage_amount']);

        $storageRecord['type_id'] = app($this->repository)->getDetail($storageRecord['office_supplies_id'])->type_id;

        //更新办公用品表
        $officeSupplies = app($this->repository)->getDetail($storageRecord['office_supplies_id']);

        if ($isPlus > 0) {
            $storageRecord['arithmetic'] = 1;
            $officeSupplies->stock_total = $officeSupplies->stock_total + $storageRecord['storage_amount'];
            $officeSupplies->stock_surplus = $officeSupplies->stock_surplus + $storageRecord['storage_amount'];
        } else {
            $storageRecord['arithmetic'] = 0;
            $officeSupplies->stock_total = $officeSupplies->stock_total - $storageRecord['storage_amount'];
            $officeSupplies->stock_surplus = $officeSupplies->stock_surplus - $storageRecord['storage_amount'];
        }

        $result = app($this->storageRepository)->insertData($storageRecord);

        if (!$result) {
            return ['code' => ['0x000003', 'common']];
        }

        $modifyOfficeSupplies = $officeSupplies->save();
        return true;
    }

    /**
     * 流程外发删除入库记录
     * @param $data
     */
    public function flowOutSendDeleteStorage($data)
    {
        if (empty($data['unique_id'])) {
            return ['code' => ['0x043018', 'officesupplies']];
        }

        $result = $this->deleteStorageRecord($data['unique_id']);

        if (isset($result['code'])) {
            return $result;
        }

        return [
            'status' => 1,
            'dataForLog' => [
                [
                    'table_to' => 'office_supplies_storage',
                    'field_to' => 'id',
                    'id_to' => $data['unique_id']
                ]
            ]
        ];
    }

    /**
     * [getStorageRecord 获取入库记录]
     *
     * @param  [int]             $storageId [入库记录ID]
     *
     * @return [array]                      [查询结果]
     * @since  2015-11-05 创建
     *
     * @author 朱从玺
     *
     */
    public function getStorageRecord($storageId, $loginUserInfo)
    {
        $storageRecord = app($this->storageRepository)->getStorageDetail($storageId);
//        $storageRecord = DB::Table('office_supplies_storage')
//                            ->select('*')
//                            ->where('id',$storageId)
//                            ->get();
        // 判断是否有入库管理权限，如果有可查看所有入库管理详情
        $managePermissions = in_array('267', $loginUserInfo['menus']['menu']);
        if (!$managePermissions || empty($storageRecord)) {
            return ['code' => ['0x000006', 'common']];
        }
        return $storageRecord;
    }

    /**
     * [getStorageList 获取入库记录列表]
     *
     * @param  [array]         $param [查询条件]
     *
     * @return [array]                [查询结果]
     * @since  2015-11-05 创建
     *
     * @author 朱从玺
     *
     */
    public function getStorageList($param, $own)
    {
        $tableKey = 'office_supplies_storage';
        $supplyIds = [];
        $param = $this->parseParams($param);
        if (isset($param['search']['office_supplies_name'])) {
            $searchName = '%' . $param['search']['office_supplies_name'][0] . '%';
            $supplyIds = DB::table('office_supplies')->where('office_supplies_name', 'like', $searchName)->pluck('id')->all();
            if (empty($supplyIds)) {
                return [];
            }
            unset($param['search']['office_supplies_name']);
            $param['search']['office_supplies_id'] = [$supplyIds, 'in'];
        }
        if (isset($param['search']['type_id'])) {
            if (!is_array($param['search']['type_id'])) {
                $param['search']['type_id'] = explode(',', $param['search']['type_id']);
            }
        }
        $result = app($this->formModelingService)->getCustomDataLists($param, $tableKey, $own);
        if (!isset($result['list'])) {
            return $result;
        }
        foreach ($result['list'] as $value) {
            $supplyIds[] = $value->raw_office_supplies_id;
        }
        $supplyIds = array_unique($supplyIds);
        $specifications = DB::table('office_supplies')->select('id', 'specifications')->whereIn('id', $supplyIds)->get();
        foreach ($specifications as $value) {
            if ($value->specifications) {
                $supplySpecifications[$value->id] = '(' . $value->specifications . ')';
            }
        }
        foreach ($result['list'] as $key => $value) {
            if (isset($supplySpecifications[$value->raw_office_supplies_id])) {
                $result['list'][$key]->office_supplies_id .= $supplySpecifications[$value->raw_office_supplies_id];
            }
        }
        return $result;
    }

    public function transferFieldOptions($data, $tableKey, $flag = false)
    {
        $patten = '/\$(\w+)\$/';
        preg_match_all($patten, $data->field_options, $variableName);

        if (!empty($variableName) && isset($variableName[0]) && isset($variableName[1])) {
            $field_options_lang = [];
            foreach ($variableName[1] as $k => $v) {
                if ($flag) {
                    $transContent = mulit_trans_dynamic("custom_fields_table.field_options.$tableKey" . '_' . "$data->field_code.$v", [], 'zh-CN');
                } else {
                    $transContent = mulit_trans_dynamic("custom_fields_table.field_options.$tableKey" . '_' . "$data->field_code.$v");
                }
                $data->field_options = str_replace($variableName[0][$k], $transContent, $data->field_options);
                $field_options_lang[$v] = app($this->langService)->transEffectLangs("custom_fields_table.field_options.$tableKey" . '_' . "$data->field_code.$v", true);
            }
            if (!empty($field_options_lang)) {
                $data->field_options_lang = $field_options_lang;
            }
        }
        return $data;
    }

    /**
     * [deleteStorageRecord 删除入库记录]
     *
     * @param  [int]               $storageId [入库记录ID]
     *
     * @return [bool]                         [删除结果s]
     * @since  2015-11-05 创建
     *
     * @author 朱从玺
     *
     */
    public function deleteStorageRecord($storageId)
    {
        $storageRecord = app($this->storageRepository)->getDetail($storageId);
        $officeSupplies = app($this->repository)->getDetail($storageRecord['office_supplies_id']);
        if (empty($storageRecord)) {
            return ['code' => ['0x016035', 'fields']];
        }
        $result = app($this->storageRepository)->deleteById($storageId);

        if (!$result) {
            return ['code' => ['0x043018', 'officesupplies']];
        }

        if (!$officeSupplies) {
            return true;
        }

        //更新办公用品表
        if ($storageRecord->arithmetic) {
            $officeSupplies->stock_surplus = $officeSupplies->stock_surplus - $storageRecord->storage_amount;
            $officeSupplies->stock_total = $officeSupplies->stock_total - $storageRecord->storage_amount;
        } else {
            $officeSupplies->stock_surplus = $officeSupplies->stock_surplus + $storageRecord->storage_amount;
            $officeSupplies->stock_total = $officeSupplies->stock_total + $storageRecord->storage_amount;
        }
        app($this->storageRepository)->entity->where('office_supplies_id', $storageRecord['office_supplies_id'])->update(['current_stock' => (string)$officeSupplies->stock_surplus]);

        $modifyOfficeSupplies = $officeSupplies->save();

        return true;
    }

    /**
     * [emptySuppliesData 清空办公用品数据]
     *
     * @return [json]            [清空结果]
     * @author 朱从玺
     *
     */
    public function emptySuppliesData()
    {
        //办公用品表,总量及剩余数量归零
        app($this->repository)->emptySuppliesStock();
        //入库表清空
        app($this->storageRepository)->emptyStorageTable();
        //申请表清空
        app($this->applyRepository)->emptyApplyTable();

        return true;
    }

    /**
     * [createApplyRecord 创建申请记录]
     *
     * @return [bool]            [创建结果]
     * @since  2015-11-05 创建
     *
     * @author 朱从玺
     *
     */
    public function createApplyRecord($applyData, $loginUserInfo = [])
    {
        $applyData['apply_user'] = $loginUserInfo['user_id'];
        $applyData['apply_user_name'] = $loginUserInfo['user_name'];
        if (!isset($applyData['apply_number'])|| !is_numeric(trim($applyData['apply_number']))){
            return ['code' => ['apply_number_error', 'officesupplies']];
        }
        $suppliesInfo = app($this->repository)->getDetail($applyData['office_supplies_id']);
        //判断物品使用方式,如果是借用,则归还日期必填
        if ($suppliesInfo->usage == 1) {
            if (!isset($applyData['return_date']) || !$applyData['return_date']) {
                return ['code' => ['0x043010', 'officesupplies']];
            } elseif (isset($applyData['receive_date']) && $applyData['receive_date']) {
                if ($applyData['return_date'] < $applyData['receive_date']) {
                    return ['code' => ['0x043011', 'officesupplies']];
                }
            }
        } elseif ($suppliesInfo->usage == 0) {
            unset($applyData['return_date']);
        }
        //负库存禁止申请
        if (isset($suppliesInfo->apply_controller) && $suppliesInfo->apply_controller == 1){
            if ($suppliesInfo->stock_surplus<=0){
                return ['code' => ['stock_surplus_empty', 'officesupplies']];
            }
            if ($suppliesInfo->stock_surplus>0&&$suppliesInfo->stock_surplus-$applyData['apply_number']<0){
                //return ['code' => ['ls_stock_surplus', 'officesupplies'],'dynamic'=>[trans('officesupplies.ls_stock_surplus').$suppliesInfo->stock_surplus]];
                return ['code' => ['ls_stock_surplus', 'officesupplies'],'dynamic'=>['【'.$suppliesInfo->office_supplies_name.'】'.trans('officesupplies.ls_stock_surplus').$suppliesInfo->stock_surplus]];
            }
        }
        if(isset($applyData['unit'])){
            unset($applyData['unit']);
        }

        $result = app($this->applyRepository)->insertData($applyData);
        if (!$result) {
            return ['code' => ['0x000003', 'common']];
        }
        //发送消息提醒
        $officeSuppliesName = isset($suppliesInfo->office_supplies_name) ? $suppliesInfo->office_supplies_name : '';
        $userName = isset($applyData['apply_user_name']) ? $applyData['apply_user_name'] : '';
        $toUser = implode(',',app($this->menuService)->getMenuRoleUserbyMenuId(269));
        // 0 申请 1 审批 2入库
        $permissionType = 1;
        $toUser = $this->filterCheckUser($toUser, $suppliesInfo, $permissionType);
        if ($toUser != "") {
            $sendData['remindMark'] = 'office_supplies-submit';
            $sendData['toUser'] = $toUser;
            $sendData['contentParam'] = ['officeSuppliesName' => $officeSuppliesName, 'userName' => $userName];
            $sendData['stateParams'] = ['applyId' => $result->id];
            Eoffice::sendMessage($sendData);
        }
        return $result;
    }

    /**
     * 批量创建申请记录
     * @param $applyData
     * @param array $loginUserInfo
     * @return array|bool
     * @creatTime 2021/1/15 18:33
     * @author [dosy]
     */
    public function batchCreateApplyRecord($applyData, $loginUserInfo = [])
    {
        if (empty($applyData)){
            return ['code' => ['0x043024', 'officesupplies']];
        }
        $officeSupplyIds = array_column($applyData, 'office_supplies_id');
        $suppliesInfos = app($this->repository)->getDetail($officeSupplyIds);
        if (!$suppliesInfos) {
            return ['code' => ['0x043016', 'officesupplies']];
        }
        $newSuppliesInfos = [];
        foreach ($suppliesInfos as $suppliesInfo) {
            $newSuppliesInfos[$suppliesInfo['id']] = $suppliesInfo;
        }
        $toUser = implode( ',',app($this->menuService)->getMenuRoleUserbyMenuId(269));
        $userName = $loginUserInfo['user_name'];
        $applyNum = [];
        foreach ($applyData as $key => $value) {
            if (!isset($value['apply_bill']) || empty($value['apply_bill'])) {
                return ['code' => ['office_supplies_apply_not_apply_bill', 'officesupplies']];
            }
            if (!isset($newSuppliesInfos[$value['office_supplies_id']]) || !$newSuppliesInfos[$value['office_supplies_id']]) {
                return ['code' => ['0x043016', 'officesupplies']];
            }
            if (empty($value['office_supplies_id'])) {
                return ['code' => ['office_supplies_apply_not_office_supplies_id', 'officesupplies']];
            }
            if (!isset($value['apply_type']) || empty($value['apply_type'])) {
                return ['code' => ['apply_type_empty_data', 'officesupplies'],'dynamic'=>['【'.$newSuppliesInfos[$value['office_supplies_id']]->office_supplies_name.'】'.trans('officesupplies.apply_type_empty_data')]];
            }
            if (!isset($value['receive_way']) || empty($value['receive_way'])) {
                return ['code' => ['receive_way_empty_data', 'officesupplies'],'dynamic'=>['【'.$newSuppliesInfos[$value['office_supplies_id']]->office_supplies_name.'】'.trans('officesupplies.receive_way_empty_data')]];
            }
            if (empty($value['apply_number'])) {
                return ['code' => ['office_supplies_apply_not_apply_number', 'officesupplies'],'dynamic'=>['【'.$newSuppliesInfos[$value['office_supplies_id']]->office_supplies_name.'】'.trans('officesupplies.office_supplies_apply_not_apply_number')]];
            }
            if (!isset($value['apply_number'])|| !is_numeric(trim($value['apply_number']))|| $value['apply_number'] <= 0) {
                return ['code' => ['office_supplies_apply_number_error', 'officesupplies'],'dynamic'=>['【'.$newSuppliesInfos[$value['office_supplies_id']]->office_supplies_name.'】'.trans('officesupplies.office_supplies_apply_number_error')]];
            }

            //负库存禁止申请
            //dd($newSuppliesInfos[$value['office_supplies_id']]->apply_controller);
            if (isset($newSuppliesInfos[$value['office_supplies_id']]->apply_controller) && $newSuppliesInfos[$value['office_supplies_id']]->apply_controller == 1){
                $applyNum[$value['office_supplies_id']]=$applyNum[$value['office_supplies_id']]??0;
                $applyNum[$value['office_supplies_id']]+=$value['apply_number'];
                //库存数小于0
                if ($newSuppliesInfos[$value['office_supplies_id']]->stock_surplus<=0){
                    return ['code' => ['stock_surplus_empty', 'officesupplies'],'dynamic'=>['【'.$newSuppliesInfos[$value['office_supplies_id']]->office_supplies_name.'】'.trans('officesupplies.stock_surplus_empty')]];
                }
                //库存大于0，申请数-库存小于0
                if ($newSuppliesInfos[$value['office_supplies_id']]->stock_surplus>0&&$newSuppliesInfos[$value['office_supplies_id']]->stock_surplus-$value['apply_number']<0){
                    return ['code' => ['ls_stock_surplus', 'officesupplies'],'dynamic'=>['【'.$newSuppliesInfos[$value['office_supplies_id']]->office_supplies_name.'】'.trans('officesupplies.ls_stock_surplus').$newSuppliesInfos[$value['office_supplies_id']]->stock_surplus]];
                }
                //申请多次，累加验证库存是否够
                if ($newSuppliesInfos[$value['office_supplies_id']]->stock_surplus - $applyNum[$value['office_supplies_id']] < 0) {
                    return ['code' => ['ls_stock_surplus', 'officesupplies'],'dynamic'=>['【'.$newSuppliesInfos[$value['office_supplies_id']]->office_supplies_name.'】'.trans('officesupplies.ls_stock_surplus').$newSuppliesInfos[$value['office_supplies_id']]->stock_surplus]];
                }
            }
            if (!isset($value['receive_date']) || empty($value['receive_date']) ){
                return ['code' => ['0x043023', 'officesupplies'],'dynamic'=>['【'.$newSuppliesInfos[$value['office_supplies_id']]->office_supplies_name.'】'.trans('officesupplies.0x043023')]];
            }
            if ($newSuppliesInfos[$value['office_supplies_id']]->usage == 1) {
                if (!isset($value['return_date']) || !$value['return_date']) {
                    return ['code' => ['0x043010', 'officesupplies']];
                }
                if (isset($value['receive_date']) && $value['receive_date']) {
                    if ($value['return_date'] < $value['receive_date']) {
                        return ['code' => ['0x043011', 'officesupplies']];
                    }
                } else {
                    return ['code' => ['0x043023', 'officesupplies']];
                }
            }
        }

        foreach ($applyData as $key => $value) {
            unset($value['attachment_id']);
            unset($value['stock_surplus']);
            if (isset($value['supplies_name'])) {
                unset($value['supplies_name']);
            }
            if (isset($value['unit'])) {
                unset($value['unit']);
            }
            if ($newSuppliesInfos[$value['office_supplies_id']]->usage == 0) {
                $value['return_date'] = null;
            }
            $value['apply_user'] = $loginUserInfo['user_id'];
            $result = app($this->applyRepository)->insertData($value);
            if (!$result) {
                return ['code' => ['0x000003', 'common']];
            }
            //发送消息提醒
            $officeSuppliesName = isset($newSuppliesInfos[$value['office_supplies_id']]->office_supplies_name) ? $newSuppliesInfos[$value['office_supplies_id']]->office_supplies_name : '';
            // 0 申请 1 审批 2入库
            $permissionType = 1;
            $toUser = $this->filterCheckUser($toUser, $newSuppliesInfos[$value['office_supplies_id']], $permissionType);
            if ($toUser != "") {
                $sendData['remindMark'] = 'office_supplies-submit';
                $sendData['toUser'] = $toUser;
                $sendData['contentParam'] = ['officeSuppliesName' => $officeSuppliesName, 'userName' => $userName];
                $sendData['stateParams'] = ['applyId' => $result->id];
                Eoffice::sendMessage($sendData);
            }

        }
        return true;
        /*$officeSupplyApplyInfo = [];
            $officeSupplyIds = [];
            $officeSupplyIds = array_column($applyData,'office_supplies_id');

            foreach ($applyData as $key => $value) {
                if (!isset($value['apply_bill']) || empty($value['apply_bill']) ){
                    return ['code'=>['office_supplies_apply_not_apply_bill','officesupplies']];
                }
                if ( empty($value['office_supplies_id'])){
                    return ['code'=>['office_supplies_apply_not_office_supplies_id','officesupplies']];
                }
                if ( empty($value['apply_number'])){
                    return ['code'=>['office_supplies_apply_not_apply_number','officesupplies']];
                }
                if ( $value['apply_number'] <= 0){
                    return ['code'=>['office_supplies_applyapply_number_error','officesupplies']];
                }
                unset($value['attachment_id']);
                unset($value['stock_surplus']);
                if (isset($value['supplies_name'])){
                    unset($value['supplies_name']);
                }
                if (!empty($value['office_supplies_id'])) {
                    $officeSupplyIds[] = $value['office_supplies_id'];
                    $officeSupplyApplyInfo[$value['office_supplies_id']] = $value;
                }
            }
            $suppliesInfos = app($this->repository)->getDetail($officeSupplyIds);
            $toUser = implode(app($this->menuService)->getMenuRoleUserbyMenuId(269), ',');
            $userName = $loginUserInfo['user_name'];
            if ($suppliesInfos){
                $newSuppliesInfos=[];
                foreach ($suppliesInfos as $suppliesInfo){
                    $newSuppliesInfos[$suppliesInfo['id']]=$suppliesInfo;
                }
                foreach (){

                }
                dd($newSuppliesInfos);
                foreach ($suppliesInfos as $suppliesInfo){
                    if ($suppliesInfo->usage == 1) {
                        if (!isset($officeSupplyApplyInfo[$suppliesInfo->id]['return_date']) || !$officeSupplyApplyInfo[$suppliesInfo->id]['return_date']) {
                            return ['code' => ['0x043010', 'officesupplies']];
                        } elseif (isset($officeSupplyApplyInfo[$suppliesInfo->id]['receive_date']) && $officeSupplyApplyInfo[$suppliesInfo->id]['receive_date']) {
                            if ($officeSupplyApplyInfo[$suppliesInfo->id]['return_date'] < $officeSupplyApplyInfo[$suppliesInfo->id]['receive_date']) {
                                return ['code' => ['0x043011', 'officesupplies']];
                            }
                        }
                    } elseif ($suppliesInfo->usage == 0) {
                        $officeSupplyApplyInfo[$suppliesInfo->id]['return_date']=null;
                    }
                    if (isset($suppliesInfo->apply_controller)&&$suppliesInfo->apply_controller==1){
                        if ($suppliesInfo->stock_surplus<0||$suppliesInfo->stock_surplus-(int)$officeSupplyApplyInfo[$suppliesInfo->id]['apply_number']<0){
                            return ['code' => ['库存为0，', 'officesupplies']];
                        }

                    }
                    $officeSupplyApplyInfo[$suppliesInfo->id]['apply_user']=  $loginUserInfo['user_id'];
                    $result = app($this->applyRepository)->insertData($officeSupplyApplyInfo[$suppliesInfo->id]);
                    if (!$result) {
                        return ['code' => ['0x000003', 'common']];
                    }
                    //发送消息提醒
                    $officeSuppliesName = isset($suppliesInfo->office_supplies_name) ? $suppliesInfo->office_supplies_name: '';
            //                if ($suppliesInfo->stock_remind==1){
            //                    if ($suppliesInfo->stock_surplus-(int)$officeSupplyApplyInfo[$suppliesInfo->id]['apply_number']<$suppliesInfo->remind_min){
            //                        $putPermissionType = 2;
            //                        $putToUser = $this->filterCheckUser($toUser, $suppliesInfo, $putPermissionType);
            //                        if ($putToUser != "") {
            //                            $sendData['remindMark'] = 'office_supplies-stock_warning';
            //                            $sendData['toUser'] = $putToUser;
            //                            $sendData['contentParam'] = ['officeSuppliesName' => $officeSuppliesName];
            //                          //  $sendData['stateParams'] = ['applyId' => $result->id];
            //                            Eoffice::sendMessage($sendData);
            //                        }
            //                    }
            //                }
                    // 0 申请 1 审批 2入库
                    $permissionType = 1;
                    $toUser = $this->filterCheckUser($toUser, $suppliesInfo, $permissionType);
                    if ($toUser != "") {
                        $sendData['remindMark'] = 'office_supplies-submit';
                        $sendData['toUser'] = $toUser;
                        $sendData['contentParam'] = ['officeSuppliesName' => $officeSuppliesName, 'userName' => $userName];
                        $sendData['stateParams'] = ['applyId' => $result->id];
                        Eoffice::sendMessage($sendData);
                    }

                }
                return true;
            }else{
                return ['code'=>['0x043016', 'officesupplies']];
            }*/

        //$result = app($this->applyRepository)->insertMultipleData($officeSupplyApplyInfo); 批量加

    }

    /**
     * 过滤审批人
     * @param $toUser
     * @param $suppliesInfo
     * @param $permissionType
     * @return array
     */
    public function filterCheckUser($toUser, $suppliesInfo, $permissionType)
    {
        $suppliesInfo = $suppliesInfo->toArray();
        if (count($suppliesInfo) > 0) {
            if (isset($suppliesInfo['type_id'])) {
                $type_id = $suppliesInfo['type_id'];
                $type = app($this->typeRepository)->getTypeInfoByID($type_id);
                if (isset($type['parent_id']) && $type['parent_id'] > 0) {
                    // 0 申请 1 审批 2入库
                    $usersInfo = [];

                    //优化方案
//                    $usersInfos[] =  app($this->userService)->getUserListByUserIdString(['user_id'=>$toUser]);
//                    $usersInfos = $usersInfos[0]['list']->toArray();
                    $usersInfos =  app($this->userService)->getUserDeptAndRoleByIds(['search'=>['user_system_info.user_id'=>[explode(',',$toUser),'in']]]);
                    foreach ($usersInfos as $user){
                        if(isset($usersInfo[$user['user_id']])){
                            $usersInfo[$user['user_id']]['role_id'] = $usersInfo[$user['user_id']]['role_id'].','.$user['role_id'];
                        }else{
                            $usersInfo[$user['user_id']] = $user;
                        }
                    }
//                    foreach ($usersInfos as $user){
//                        $roleIds = array_column($user['user_has_many_role'],'role_id');
//                        $usersInfo[] = [
//                            'dept_id' => $user['user_has_one_system_info']['dept_id']??'',
//                            'role_id' => implode(',',$roleIds),
//                            'user_id' => $user['user_id']
//                        ];
//                    }
                    //原方案
/*                    $users_id_arr = explode(',', $toUser);
                    foreach ($users_id_arr as $user_id_tmp) {
                        $user_tmp = app($this->userService)->getUserDeptIdAndRoleIdByUserId($user_id_tmp);
                        $user_tmp['user_id'] = $user_id_tmp;
                        $usersInfo[] = $user_tmp;
                    }*/

                    $toUser = app($this->typePermissionRepository)->getPermissionByUserInfo($usersInfo, $permissionType, $type['parent_id']);
                    if (count($toUser) == 0) {
                        return "";
                    } else {
                        return implode(',', $toUser);
                    }
                }
            }
        }
    }

    /**
     * [flowOutSendCreateApplyRecord 流程外发创建申请记录]
     *
     * @param $applyData
     * @return array [bool]            [创建结果]
     * @author 缪晨晨
     *
     * @since  2017-07-28 创建
     *
     */
    public function flowOutSendCreateApplyRecord($applyData)
    {
        //验证必填数据
        $validKeys = ['apply_user', 'office_supplies_id', 'apply_number', 'receive_date', 'apply_type', 'receive_way'];
        foreach ($validKeys as $key) {
            if (!isset($applyData[$key]) || empty($applyData[$key])) {
                $code = $key . '_empty';
                return ['code' => [$code, 'officesupplies']];
            }
        }

        $suppliesInfo = app($this->repository)->entity
            ->with([
                'suppliesBelongsToType' => function ($query) {
                    $query->select('id', 'parent_id');
                }
            ])
            ->find($applyData['office_supplies_id']);
        if (!$suppliesInfo) {
            return $this->getLangCode(OfficeSuppliesCode::SUPPLY_NOT_EXIST);
        }
        // 判断是否有该办公用品申请权限
        $type = $suppliesInfo->suppliesBelongsToType->parent_id;
        $userInfo = app($this->userService)->getUserDeptAndRoleInfoByUserId($applyData['apply_user']);
        if (!$this->judgeTypePermission($type, 0, $userInfo)) {
            return $this->getLangCode(OfficeSuppliesCode::NO_PERMISSION);
        }
        //判断物品使用方式,如果是借用,则归还日期必填
        if ($suppliesInfo->usage == 1) {
            if (!isset($applyData['return_date']) || !$applyData['return_date']) {
                return ['code' => ['0x043010', 'officesupplies']];
            } elseif (isset($applyData['receive_date']) && $applyData['receive_date']) {
                if ($applyData['return_date'] < $applyData['receive_date']) {
                    return ['code' => ['0x043011', 'officesupplies']];
                }
            }
        } elseif ($suppliesInfo->usage == 0) {
            unset($applyData['return_date']);
        }

        // 数据外发的转换数据 先这样做
        if (!isset($applyData['apply_bill'])) {
            $applyBillResult = $this->getCreateNo(['no_type' => 'apply']);
            $applyData['apply_bill'] = $applyBillResult['apply_bill'];
            // 默认批准才外发
            $applyData['apply_status'] = 1;
        }
        if (isset($applyData['apply_type'])) {
            $applyType = app($this->systemComboboxService)->getValueByComboboxIdentify('USE_TYPE', $applyData['apply_type']);
            if ($applyType) {
                $applyData['apply_type'] = $applyType;
            }
        }
        if (isset($applyData['receive_way'])) {
            $applyType = app($this->systemComboboxService)->getValueByComboboxIdentify('GET_TYPE', $applyData['receive_way']);
            if ($applyType) {
                $applyData['receive_way'] = $applyType;
            }
        }
        //负库存禁止申请
        if (isset($suppliesInfo->apply_controller) && $suppliesInfo->apply_controller == 1){
            if ($suppliesInfo->stock_surplus<=0){
                //return ['code' => ['stock_surplus_empty', 'officesupplies']];
                return ['code' => ['stock_surplus_empty', 'officesupplies'],'dynamic'=>['【'.$suppliesInfo->office_supplies_name.'】'.trans('officesupplies.stock_surplus_empty')]];

            }
            if ($suppliesInfo->stock_surplus>0&&$suppliesInfo->stock_surplus-$applyData['apply_number']<0){
               // return ['code' => ['ls_stock_surplus', 'officesupplies'],'dynamic'=>[trans('officesupplies.ls_stock_surplus').$suppliesInfo->stock_surplus]];
                return ['code' => ['ls_stock_surplus', 'officesupplies'],'dynamic'=>['【'.$suppliesInfo->office_supplies_name.'】'.trans('officesupplies.ls_stock_surplus').$suppliesInfo->stock_surplus]];

            }
        }
        $result = app($this->applyRepository)->insertData($applyData);

        // 更新库存
        $suppliesInfo->stock_surplus = $suppliesInfo->stock_surplus - $applyData['apply_number'];
        $suppliesInfo->save();

        app($this->storageRepository)->entity->where('office_supplies_id', $applyData['office_supplies_id'])->update(['current_stock' => (string)$suppliesInfo->stock_surplus]);

        if (!$result) {
            return ['code' => ['0x000003', 'common']];
        }
        //库存数低于最低警示数量后提醒入库管理者
        if ($suppliesInfo->stock_remind == 1) {
            if ($suppliesInfo->stock_surplus < $suppliesInfo->remind_min) {
                $putPermissionType = 2;
                $toUser = implode(',', app($this->menuService)->getMenuRoleUserbyMenuId(269));
                $putToUser = $this->filterCheckUser($toUser, $suppliesInfo, $putPermissionType);
                if ($putToUser != "") {
                    $sendData['remindMark'] = 'office_supplies-stock_warning';
                    $sendData['toUser'] = $putToUser;
                    $sendData['contentParam'] = ['officeSuppliesName' => $suppliesInfo->office_supplies_name];
                    //  $sendData['stateParams'] = ['applyId' => $result->id];
                    Eoffice::sendMessage($sendData);
                }
            }
        }
        return [
            'status' => 1,
            'dataForLog' => [
                [
                    'table_to' => 'office_supplies_apply',
                    'field_to' => 'id',
                    'id_to' => $result['id']
                ]
            ]
        ];
    }

    /**
     * 外发删除申请记录
     * @param $data
     * @return array|bool
     */
    public function flowOutSendDeleteApplyRecord($data)
    {
        if (empty($data['unique_id'])) {
            return ['code' => ['0x043005', 'officesupplies']];
        }
        $apply = app($this->applyRepository)->getDetail($data['unique_id']);
        if (empty($apply)) {
            return ['code' => ['0x016035', 'fields']];
        }
        if (isset($apply->apply_status) && $apply->apply_status == 1) {
            return ['code' => ['0x043004', 'officesupplies']];
        }
        $result = $this->deleteApplyRecord($data['unique_id']);
        if (isset($result['code'])) {
            return $result;
        }

        return [
            'status' => 1,
            'dataForLog' => [
                [
                    'table_to' => 'office_supplies_apply',
                    'field_to' => 'id',
                    'id_to' => $data['unique_id']
                ]
            ]
        ];
    }

    /**
     * [getApplyRecord 获取申请记录]
     *
     * @param  [int]           $applyId [申请ID]
     *
     * @return [array]                  [查询结果]
     * @since  2015-11-05 创建
     *
     * @author 朱从玺
     *
     */
    public function getApplyRecord($applyId, $loginUserInfo)
    {
        $applyRecord = app($this->applyRepository)->getApplyDetail($applyId);
        if (!empty($applyRecord)) {
            // 判断是否有审批权限，如果有可查看申请记录的详情,已前置除理
//            $approvePermissions = in_array('269', $loginUserInfo['menus']['menu']);
//            // 判断详情页查看权限
//            if (!$approvePermissions) {
//                if (empty($applyRecord) || ($loginUserInfo['user_id'] != $applyRecord['apply_user'])) {
//                    return ['code' => ['0x000006', 'common']];
//                }
//            }
            switch ($applyRecord['apply_status']) {
                case '0':
                    $applyRecord['apply_status_name'] = trans("officesupplies.examination_and_approval");
                    break;
                case '1':
                    $applyRecord['apply_status_name'] = trans("officesupplies.already_passed");
                    break;
                default:
                    $applyRecord['apply_status_name'] = trans("officesupplies.not_through");
                    break;
            }
            if ($applyRecord['return_status'] == 1) {
                $applyRecord['apply_status_name'] = trans("officesupplies.restitution");
            }
            $selectField = $this->officeSuppliesSelectField;
            foreach ($selectField as $field => $id) {
                $applyRecord[$field] = app($this->systemComboboxService)->getComboboxFieldsNameByIdentify($id, $applyRecord[$field]);
            }
            return $applyRecord;
        } else {
            return ['code' => ['0x000006', 'common']];
        }
    }

    /**
     * 获取有权限的办公用品列表
     * @param $own
     * @param $permission_type
     */
    public function getAllowSuppliesId($own, $permission_type)
    {
        $allow_types_parent_id_arr = app($this->typePermissionRepository)->getAllowTypeByOwn($own, $permission_type);
        $allow_types_arr = app($this->typeRepository)->getTypeInfoByParentID($allow_types_parent_id_arr);
        $allow_types_id_arr = [];
        foreach ($allow_types_arr as $val) {
            $allow_types_id_arr[] = $val['id'];
        }
        $office_supplies_arr = app($this->repository)->getAllSuppliesListByTypeIds($allow_types_id_arr);
        $office_supplies_id_arr = [];
        foreach ($office_supplies_arr as $val) {
            $office_supplies_id_arr[] = $val['id'];
        }
        return $office_supplies_id_arr;
    }

    /**
     * [getApplyList 获取申请记录列表]
     *
     * @param  [array]       $param [查询条件]
     *
     * @return [array]              [查询结果]
     * @since  2015-11-05 创建
     *
     * @author 朱从玺
     *
     */
    public function getApplyList($param)
    {
        // 0 申请 1 审批 2入库
        $permission_type = isset($param['from_type']) ? $param['from_type'] : 1;
        $office_supplies_id_arr = $this->getAllowSuppliesId($param['own'], $permission_type);

        $param = $this->parseParams($param);
        if (isset($param['search']) && isset($param['search']['office_supplies_id']) && $param['search']['office_supplies_id']) {
            $param['office_supplies_id'] = $param['search']['office_supplies_id'];
        }
        $param['search']['office_supplies_id'] = [$office_supplies_id_arr, 'in'];


        //如果查询字段中有办公用品表字段
        $suppliesSearchFields = ['office_supplies_name', 'usage'];
        //如果查询字段中有办公用品类型表字段
        $typeFields = ['type_name', 'type_id'];


        if (isset($param['search']) && $param['search']) {
            $suppliesSearch = [];
            $typeSearch = [];

            foreach ($param['search'] as $key => $value) {
                if (in_array($key, $suppliesSearchFields)) {
                    $suppliesSearch[$key] = $value;
                    unset($param['search'][$key]);
                }

                if (in_array($key, $typeFields)) {
                    $typeSearch[$key] = $value;
                    unset($param['search'][$key]);
                }
            }

            $param['supplies_search'] = $suppliesSearch;
            $param['type_search'] = $typeSearch;
        }
        $applyList = $this->response(app($this->applyRepository), 'getApplyCount', 'getApplyList', $param);

        $selectField = $this->officeSuppliesSelectField;
        foreach ($selectField as $field => $id) {
            foreach ($applyList['list'] as $key => $value) {
                switch ($value['apply_status']) {
                    case '0':
                        $applyList['list'][$key]['apply_status_name'] = trans("officesupplies.examination_and_approval");
                        break;
                    case '1':
                        $applyList['list'][$key]['apply_status_name'] = trans("officesupplies.already_passed");
//                        if ($value['return_status'] == 0&&!empty($value['return_date'])) {
//                            $applyList['list'][$key]['apply_status_name'] = trans("officesupplies.not_restitution");
//                        }
                        break;
                    default:
                        $applyList['list'][$key]['apply_status_name'] = trans("officesupplies.not_through");
                        break;
                }
                if ($value['return_status'] == 1) {
                    $applyList['list'][$key]['apply_status_name'] = trans("officesupplies.restitution");
                }
                $applyList['list'][$key][$field] = app($this->systemComboboxService)->getComboboxFieldsNameByIdentify($id, $value[$field]);
            }
        }
        return $applyList;
    }


    /**
     * [deleteApplyRecord 删除申请记录]
     *
     * @param  [int]             $applyId [申请ID]
     *
     * @return [bool]                     [删除结果]
     * @since  2015-11-05 创建
     *
     * @author 朱从玺
     *
     */
    public function deleteApplyRecord($applyId)
    {
        $apply = app($this->applyRepository)->getDetail($applyId);
        if (empty($apply)) {
            return ['code' => ['0x016035', 'fields']];
        }
        if ($apply) {
            $result = app($this->applyRepository)->deleteById($applyId);
            if (!$result) {
                return ['code' => ['0x043005', 'officesupplies']];
            }
        }
        return true;
    }

    /**
     * [modifyApplyRecord 申请管理,审批或者归还]
     *
     * @param  [int]         $applyId      [申请ID]
     * @param  [array]       $approvalData [管理数据]
     *
     * @return [bool]                      [编辑结果]
     * @since  2015-11-05 创建
     *
     * @author 朱从玺
     *
     */
    public function modifyApplyRecord($applyId, $approvalData, $loginUserId = "")
    {
        $applyData = app($this->applyRepository)->getDetail($applyId);

        if (!$applyData) {
            return ['code' => ['0x043005', 'officesupplies']];
        }

        $officeSupplies = app($this->repository)->getDetail($applyData->office_supplies_id);

        $where = ['id' => [$applyId]];

        //审批动作
        if (isset($approvalData['apply_status'])) {
            if ($applyData['apply_status'] != 0) {
                return ['code' => ['0x043008', 'officesupplies']];
            }

            $applyData->apply_status = $approvalData['apply_status'];
            $applyData->approval_opinion = isset($approvalData['approval_opinion']) ? $approvalData['approval_opinion'] : '';
            //归还动作
        } elseif (isset($approvalData['return_status'])) {
            //判断办公用品使用方式
            if ($officeSupplies['usage'] != 1) {
                return ['code' => ['0x043006', 'officesupplies']];
            }

            //判断审批是否通过,是否已归还
            if ($applyData['apply_status'] != 1 || $applyData['return_status'] == 1) {
                return ['code' => ['0x043007', 'officesupplies']];
            }

            $applyData->return_status = 1;
            $applyData->real_return_date = date('Y-m-d');
        } else {
            return ['code' => ['0x000003', 'common']];
        }

        $result = $applyData->save();

        $officeSuppliesName = isset($officeSupplies['office_supplies_name']) ? $officeSupplies['office_supplies_name'] : '';
        if (isset($approvalData['apply_status']) && $approvalData['apply_status'] == 1) {
            // 批准
            // 更新办公用品表
            $officeSupplies->stock_surplus = $officeSupplies->stock_surplus - $applyData['apply_number'];
            app($this->storageRepository)->entity->where('office_supplies_id', $applyData->office_supplies_id)->update(['current_stock' => (string)$officeSupplies->stock_surplus]);
            $userName = get_user_simple_attr($loginUserId);
            $sendData['remindMark'] = 'office_supplies-pass';
            $sendData['toUser'] = $applyData->apply_user;
            $sendData['contentParam'] = ['officeSuppliesName' => $officeSuppliesName, 'userName' => $userName];
            $sendData['stateParams'] = ['applyId' => $applyData['id']];
            Eoffice::sendMessage($sendData);

            //库存数低于最低警示数量后提醒入库管理者
            if ($officeSupplies->stock_remind == 1) {
                if ($officeSupplies->stock_surplus < $officeSupplies->remind_min) {
                    $putPermissionType = 2;
                    $toUser = implode(',', app($this->menuService)->getMenuRoleUserbyMenuId(269));
                    $putToUser = $this->filterCheckUser($toUser, $officeSupplies, $putPermissionType);
                    if ($putToUser != "") {
                        $sendData['remindMark'] = 'office_supplies-stock_warning';
                        $sendData['toUser'] = $putToUser;
                        $sendData['contentParam'] = ['officeSuppliesName' => $officeSuppliesName];
                        //  $sendData['stateParams'] = ['applyId' => $result->id];
                        Eoffice::sendMessage($sendData);
                    }
                }
            }

        } elseif (isset($approvalData['apply_status']) && $approvalData['apply_status'] == 2) {
            // 不批准
            $userName = get_user_simple_attr($loginUserId);
            $sendData['remindMark'] = 'office_supplies-refuse';
            $sendData['toUser'] = $applyData->apply_user;
            $sendData['contentParam'] = ['officeSuppliesName' => $officeSuppliesName, 'userName' => $userName];
            $sendData['stateParams'] = ['applyId' => $applyData['id']];
            Eoffice::sendMessage($sendData);
        } elseif (isset($approvalData['return_status'])) {
            // 归还
            // 更新办公用品表
            $officeSupplies->stock_surplus = $officeSupplies->stock_surplus + $applyData['apply_number'];
            app($this->storageRepository)->entity->where('office_supplies_id', $applyData->office_supplies_id)->update(['current_stock' => (string)$officeSupplies->stock_surplus]);
            $userName = get_user_simple_attr($applyData['apply_user']);
            $sendData['remindMark'] = 'office_supplies-return';
//          $sendData['toUser'] = $toUser;
            $sendData['toUser'] = $applyData->apply_user;
            $sendData['contentParam'] = ['officeSuppliesName' => $officeSuppliesName, 'userName' => $userName];
            $sendData['stateParams'] = ['applyId' => $applyData['id']];
            Eoffice::sendMessage($sendData);
        }

        $modifyOfficeSupplies = $officeSupplies->save();

        return true;
    }

    /**
     * [getPurchaseList 获取采购清单]
     *
     * @param  [array]                $param [查询条件]
     *
     * @return [array]                       [查询结果]
     * @since  2015-11-05 创建
     *
     * @author 朱从玺
     *
     */
    public function getPurchaseList($param)
    {
        $param = $this->parseParams($param);
        $result = $this->response(app($this->repository), 'getPurchaseCount', 'getPurchaseList', $param);

        foreach ($result['list'] as $key => $value) {
            if ($value['stock_remind'] == 1) {
                $result['list'][$key]['purchase_mininum'] = $value['remind_min'] - $value['stock_surplus'];
            } else {
                $result['list'][$key]['purchase_mininum'] = 0 - $value['stock_surplus'];
            }
        }

        return $result;
    }

    /**
     * [exportOfficeSuppliesPurchase 采购清单导出]
     *
     * @param
     *
     * @return
     * @since  2016-06-20 创建
     *
     * @author miaochenchen
     *
     */
    public function exportOfficeSuppliesPurchase($param)
    {
        $officeSuppliesPurchaseList = $this->getPurchaseList($param);
        $typeData = app($this->typeRepository)->getFieldInfo([]);
        $typeData = array_column($typeData,null,'id');
        $header = [
            'office_supplies_name' => ['data' => trans("officesupplies.item_name"), 'style' => ['width' => '30']],
            //'type_id|suppliesType' => ['data' => trans("officesupplies.item_category"), 'style' => ['width' => '30']],
            'top_type_name' => ['data' => trans("officesupplies.top_type_name"), 'style' => ['width' => '30']],
            'suppliesType' => ['data' => trans("officesupplies.item_category"), 'style' => ['width' => '30']],
            'stock_surplus' => ['data' => trans("officesupplies.current_stock"), 'style' => ['width' => '15']],
            'purchase_mininum' => ['data' => trans("officesupplies.minimum_purchase_quantity"), 'style' => ['width' => '15']],
            'remind_min' => ['data' => trans("officesupplies.minimum_warning_number"), 'style' => ['width' => '15']],
            'remind_max' => ['data' => trans("officesupplies.maximum_warning_number"), 'style' => ['width' => '15']],
            'min_price' => ['data' => trans("officesupplies.minimum_unit_price"), 'style' => ['width' => '15']],
            'max_price' => ['data' => trans("officesupplies.highest_unit_price"), 'style' => ['width' => '15']],
            'unit' => ['data' => trans("officesupplies.measurement_unit"), 'style' => ['width' => '15']],
            'specifications' => ['data' => trans("officesupplies.specifications"), 'style' => ['width' => '30']]
        ];
//        echo json_encode($officeSuppliesPurchaseList['list']);die;
        foreach ($officeSuppliesPurchaseList['list'] as $k => $v) {
            $data[$k]['office_supplies_name'] = $v['office_supplies_name'];
//            $data[$k]['type_id'] = $v['type_id'];
            if (isset($typeData[$v['type_id']])&&isset($typeData[$typeData[$v['type_id']]['parent_id']])){
                $data[$k]['top_type_name'] = $typeData[$typeData[$v['type_id']]['parent_id']]['type_name']??'';
            }else{
                $data[$k]['top_type_name'] = '';
            }
            $data[$k]['suppliesType'] = $v['supplies_belongs_to_type']['type_name'];
            $data[$k]['stock_surplus'] = $v['stock_surplus'];
            $data[$k]['purchase_mininum'] = $v['purchase_mininum'];
            $data[$k]['remind_min'] = $v['remind_min'];
            $data[$k]['remind_max'] = $v['remind_max'];
            if ($v['supplies_has_many_storage']) {
                $data[$k]['min_price'] = $v['supplies_has_many_storage']['0']['min_price'];
                $data[$k]['max_price'] = $v['supplies_has_many_storage']['0']['max_price'];
            }
            $data[$k]['unit'] = $v['unit'];
            $data[$k]['specifications'] = $v['specifications'];
        }
        return compact('header', 'data');
    }

    /**
     * [exportOfficeSuppliesApply 办公用品申请审批记录导出]
     *
     * @param
     *
     * @return
     * @since  2016-06-20 创建
     *
     * @author miaochenchen
     *
     */
    public function exportOfficeSuppliesApply($param)
    {
        $param = $this->parseParams($param);
        $param['own'] = $param['user_info'];
        $param['from_type'] = $param['user_info'];
        // 0 申请 1 审批 2入库
        $param['from_type'] = 1;
        $officeSuppliesApplyList = $this->getApplyList($param);
        $typeData = app($this->typeRepository)->getFieldInfo([]);
        $typeData = array_column($typeData,null,'id');
        $header = [
            'top_type_name' => ['data' => trans('officesupplies.top_type_name'), 'style' => ['width' => '15']],
            'type_name' => ['data' => trans('officesupplies.category'), 'style' => ['width' => '30']],
            'office_supplies_name' => ['data' => trans('officesupplies.office_supplies_name'), 'style' => ['width' => '30']],
            'specifications' => ['data' => trans('officesupplies.specifications'), 'style' => ['width' => '15']],
            'stock_surplus' => ['data' => trans('officesupplies.inventory_quantity'), 'style' => ['width' => '15']],
            'apply_number' => ['data' => trans('officesupplies.number_of_applications'), 'style' => ['width' => '15']],
            'unit' => ['data' => trans('officesupplies.measurement_unit'), 'style' => ['width' => '15']],
            'receive_date' => ['data' => trans('officesupplies.date_of_use'), 'style' => ['width' => '15']],
            'return_date' => ['data' => trans('officesupplies.return_date'), 'style' => ['width' => '15']],
            'real_return_date' => ['data' => trans('officesupplies.actual_return_date'), 'style' => ['width' => '15']],
            'usage|suppliesUsage' => ['data' => trans('officesupplies.mode_of_use'), 'style' => ['width' => '15']],
            'apply_type' => ['data' => trans('officesupplies.use_category'), 'style' => ['width' => '15']],
            'receive_way' => ['data' => trans('officesupplies.payment'), 'style' => ['width' => '15']],
            'apply_user' => ['data' => trans('officesupplies.applicant'), 'style' => ['width' => '15']],
            'dept_name' => ['data' => trans('officesupplies.subordinate_department'), 'style' => ['width' => '20']],
            'explain' => ['data' => trans('officesupplies.apply_explain'), 'style' => ['width' => '20']],
            'apply_bill' => ['data' => trans('officesupplies.application_document_number'), 'style' => ['width' => '15']],
            'apply_status_name' => ['data' => trans('officesupplies.current_situation'), 'style' => ['width' => '15']],
            'approval_opinion' => ['data' => trans('officesupplies.examination_and_approval_opinion'), 'style' => ['width' => '50']]
        ];
        foreach ($officeSuppliesApplyList['list'] as $k => $v) {
            $data[$k]['top_type_name'] = $typeData[$v['apply_belongs_to_supplies']['supplies_belongs_to_type']['parent_id']]['type_name']??'';
            $data[$k]['type_name'] = $v['apply_belongs_to_supplies']['supplies_belongs_to_type']['type_name'];
            $data[$k]['office_supplies_name'] = $v['apply_belongs_to_supplies']['office_supplies_name'];
            $data[$k]['specifications'] = $v['apply_belongs_to_supplies']['specifications'];
            $data[$k]['stock_surplus'] = $v['apply_belongs_to_supplies']['stock_surplus'];
            $data[$k]['apply_number'] = $v['apply_number'];
            $data[$k]['unit'] = $v['apply_belongs_to_supplies']['unit'];
            $data[$k]['receive_date'] = $v['receive_date'];
            $data[$k]['return_date'] = $v['return_date'];
            $data[$k]['real_return_date'] = $v['real_return_date'];
            $data[$k]['usage'] = $v['apply_belongs_to_supplies']['usage'];
            $data[$k]['apply_type'] = $v['apply_type'];
            $data[$k]['receive_way'] = $v['receive_way'];
            if(isset($v['apply_belongs_to_user'])){
                if(isset($v['apply_belongs_to_user']['user_name'])){
                    $data[$k]['apply_user'] = $v['apply_belongs_to_user']['user_name'];
                }else{
                    $data[$k]['apply_user'] = '';
                }
            }else{
                $data[$k]['apply_user'] = '';
            }
           // $data[$k]['apply_user'] = $v['apply_belongs_to_user']['user_name'];
            $data[$k]['dept_name'] = $v['apply_belongs_to_user_system_info']['user_system_info_belongs_to_department']['dept_name']??'';
            $data[$k]['explain'] = $v['explan'];
            $data[$k]['apply_bill'] = $v['apply_bill'];
            $data[$k]['apply_status_name'] = $v['apply_status_name'];
            $data[$k]['approval_opinion'] = $v['approval_opinion'];
        }
        return compact('header', 'data');
    }

    /**
     * [exportOfficeSuppliesApply 办公用品我使用申请记录导出]
     *
     * @param
     *
     * @return
     * @since  2016-11-28 创建
     *
     * @author miaochenchen
     *
     */
    public function exportOfficeSuppliesMyApply($param)
    {
        $param = $this->parseParams($param);
        $param['apply_user'] = [$param['user_info']['user_id']];
        $param['own'] = $param['user_info'];
        // 0 申请 1 审批 2入库
        $param['from_type'] = 0;
        $officeSuppliesApplyList = $this->getApplyList($param);
        $typeData = app($this->typeRepository)->getFieldInfo([]);
        $typeData = array_column($typeData,null,'id');
        $header = [
            'apply_bill' => ['data' => trans('officesupplies.application_document_number'), 'style' => ['width' => '15']],
            'top_type_name' => ['data' => trans('officesupplies.top_type_name'), 'style' => ['width' => '15']],
            'type_name' => ['data' => trans('officesupplies.item_category'), 'style' => ['width' => '15']],
            'office_supplies_name' => ['data' => trans('officesupplies.item_name'), 'style' => ['width' => '15']],
            'specifications' => ['data' => trans('officesupplies.specifications'), 'style' => ['width' => '15']],
            'apply_number' => ['data' => trans('officesupplies.number_of_applications'), 'style' => ['width' => '15']],
            'unit' => ['data' => trans('officesupplies.measurement_unit'), 'style' => ['width' => '15']],
            'receive_date' => ['data' => trans('officesupplies.date_of_use'), 'style' => ['width' => '15']],
            'usage|suppliesUsage' => ['data' => trans('officesupplies.mode_of_use'), 'style' => ['width' => '15']],
            'explain' => ['data' => trans('officesupplies.apply_explain'), 'style' => ['width' => '15']],
            'apply_status_name' => ['data' => trans('officesupplies.current_situation'), 'style' => ['width' => '15']],
            'approval_opinion' => ['data' => trans('officesupplies.examination_and_approval_opinion'), 'style' => ['width' => '50']]
        ];
        foreach ($officeSuppliesApplyList['list'] as $k => $v) {
            $data[$k]['apply_bill'] = $v['apply_bill'];
            $data[$k]['type_name'] = $v['apply_belongs_to_supplies']['supplies_belongs_to_type']['type_name'];
            $data[$k]['top_type_name'] = $typeData[$v['apply_belongs_to_supplies']['supplies_belongs_to_type']['parent_id']]['type_name']??'';
            $data[$k]['office_supplies_name'] = $v['apply_belongs_to_supplies']['office_supplies_name'];
            $data[$k]['specifications'] = $v['apply_belongs_to_supplies']['specifications'];
            $data[$k]['apply_number'] = $v['apply_number'];
            $data[$k]['unit'] = $v['apply_belongs_to_supplies']['unit'];
            $data[$k]['receive_date'] = $v['receive_date'];
            $data[$k]['usage'] = $v['apply_belongs_to_supplies']['usage'];
            $data[$k]['explain'] = $v['explan'];
            $data[$k]['apply_status_name'] = $v['apply_status_name'];
            $data[$k]['approval_opinion'] = $v['approval_opinion'];
        }
        return compact('header', 'data');
    }

    /**
     * [exportOfficeSuppliesStorage 办公用品入库记录导出]
     *
     * @param
     *
     * @return
     * @since  2016-06-20 创建
     *
     * @author miaochenchen
     *
     */
    public function exportOfficeSuppliesStorage($param)
    {
        $own = $param['user_info'];
        $search = [];
        $search['order_by'] = ['id' => 'desc'];
        if (!isset($param['order_by']) || empty($param['order_by'])) {
            $param['order_by'] = ['id' => 'desc'];
        }
        $tableKey = 'office_supplies_storage';
        $export = app($this->formModelingService)->exportFields($tableKey, $param, $own, trans('officesupplies.office_supplies_storage_export'));
        //头加入规格
        $newHeader = [];
        $newHeader['top_type_name'] = ['data' => trans('officesupplies.top_type_name'), 'style' => ['width' => '15']];
        $newHeader['type_name'] = ['data' => trans('officesupplies.item_category'), 'style' => ['width' => '15']];
        foreach ($export['header'] as $key => $value) {
            $newHeader[$key] = $value;
            if ($key == 'office_supplies_id') {
                $newHeader['specifications'] = ['data' => trans('officesupplies.specifications'), 'style' => ['width' => '15']];
            }
        }
        $export['header'] = $newHeader;
        $typeData = app($this->typeRepository)->getFieldInfo([]);
        $typeData = array_column($typeData,null,'id');
        $officeSupplies = app($this->storageRepository)->getFieldInfo([]);
        $officeSuppliesData = array_column($officeSupplies,null,'id');
        //dd($officeSuppliesData);
        //数据加入规格
        $list = app($this->formModelingService)->getCustomDataLists($param, $tableKey, $param['user_info']);
        foreach ($list['list'] as $value) {
            $supplyIds[] = $value->raw_office_supplies_id;
        }

        $specifications = DB::table('office_supplies')->select('id','type_id', 'specifications')->whereIn('id', $supplyIds)->get();
        foreach ($specifications as $value) {
            if ($value->specifications) {
                $supplySpecifications[$value->id] = $value->specifications;
            }
        }

        foreach ($supplyIds as $key => $value) {
            $specificationsArray[] = $supplySpecifications[$value] ?? '';
        }
        foreach ($export['data'] as $key => $value) {
            $export['data'][$key]['specifications'] = $specificationsArray[$key] ?? '';
            if (isset($officeSuppliesData[$value['id']])&&isset($typeData[$officeSuppliesData[$value['id']]['type_id']])&&isset($typeData[$typeData[$officeSuppliesData[$value['id']]['type_id']]['parent_id']])){
                $export['data'][$key]['top_type_name'] = $typeData[$typeData[$officeSuppliesData[$value['id']]['type_id']]['parent_id']]['type_name']??'';
                $export['data'][$key]['type_name'] = $typeData[$officeSuppliesData[$value['id']]['type_id']]['type_name']??'';
            }else{
                $export['data'][$key]['top_type_name'] = '';
                $export['data'][$key]['type_name'] = '';
            }
        }
        return $export;
    }

    /**
     * [getImportOfficeSuppliesFields 获取导入办公用品字段]
     *
     * @param
     *
     * @return
     * @since  2016-06-28 创建
     *
     * @author miaochenchen
     *
     */
    public function getImportOfficeSuppliesFields($userInfo)
    {
        $typeList = $this->getImportAllowedSecondTypes(2, $userInfo);
        $allExportData = [
            '0' => [
                'sheetName' => trans('officesupplies.office_supplies_import_template'),
                'header' => [
                    'office_supplies_no' => ['data' => trans('officesupplies.office_supplies_no'), 'style' => ['width' => '20']],
                    'office_supplies_name' => ['data' => trans('officesupplies.item_name') . trans('officesupplies.required'), 'style' => ['width' => '30']],
                    'type_id' => ['data' => trans('officesupplies.item_category_id') . trans('officesupplies.required'), 'style' => ['width' => '20']],
                    'specifications' => ['data' => trans('officesupplies.specifications'), 'style' => ['width' => '15']],
                    'unit' => ['data' => trans('officesupplies.measurement_unit'), 'style' => ['width' => '15']],
                    'reference_price' => ['data' => trans('officesupplies.reference_price'), 'style' => ['width' => '20']],
                    'usage' => ['data' => trans('officesupplies.mode_of_use_id') . trans('officesupplies.required'), 'style' => ['width' => '20']],
                    'apply_controller' => ['data' => trans('officesupplies.apply_judge') . trans('officesupplies.required'), 'style' => ['width' => '20']],
                ]
            ],
            '1' => [
                'sheetName' => trans('officesupplies.item_category'),
                'header' => [
                    'id' => ['data' => trans('officesupplies.item_category_id'), 'style' => ['width' => '15']],
                    'type_name' => ['data' => trans('officesupplies.item_category_name'), 'style' => ['width' => '30']]
                ],
                'data' => !empty($typeList) ? $typeList : []
            ],
            '2' => [
                'sheetName' => trans('officesupplies.mode_of_use'),
                'header' => [
                    'id' => ['data' => trans('officesupplies.mode_of_use_id'), 'style' => ['width' => '15']],
                    'usage' => ['data' => trans('officesupplies.mode_of_use'), 'style' => ['width' => '15']]
                ],
                'data' => [
                    '0' => [
                        'id' => '0',
                        'usage' => trans('officesupplies.use')
                    ],
                    '1' => [
                        'id' => '1',
                        'usage' => trans('officesupplies.borrow')
                    ]
                ]
            ],
            '3'=>[
                'sheetName' => trans('officesupplies.apply_judge'),
                'header' => [
                    'id' => ['data' =>'ID', 'style' => ['width' => '15']],
                    'apply_controller' => ['data' => trans('officesupplies.apply_judge'), 'style' => ['width' => '15']]
                ],
                'data' => [
                    '0' => [
                        'id' => '0',
                        'apply_controller' => trans('officesupplies.allow_apply')
                    ],
                    '1' => [
                        'id' => '1',
                        'apply_controller' => trans('officesupplies.forbid_apply')
                    ]
                ]
            ]
        ];
        return $allExportData;
    }

    /**
     * [importOfficeSupplies 导入办公用品]
     *
     * @param $data
     * @param $param
     * @return array
     * @author miaochenchen
     *
     * @since  2016-06-28 创建
     *
     */
    public function importOfficeSupplies($data, $param)
    {
        if ($param['type'] == 3) {
            //新增数据并清除原有数据 先清除原有关联数据 申请表 入库表
            DB::table('office_supplies_apply')->delete();
            DB::table('office_supplies_storage')->delete();
        }
        $defaultFields = [
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        $tables = [
            'table' => 'office_supplies'
        ];
        return $this->importData($tables, $data, $param, $defaultFields);
    }

    /**
     * 导入办公用品过滤
     *
     * @param array $data 导入数据
     *
     * @return array 导入结果
     *
     * @author miaochenchen
     *
     * @since  2017-01-16
     */
    public function importOfficeSuppliesFilter($data, $param = [])
    {
        $typeList = $this->getOfficeSuppliesAllTypeList([]);
        $tempData = [];
        if (!empty($typeList)) {
            foreach ($typeList as $key => $value) {
                $tempData[$value['type_name']] = $value['id'];
            }
        }
        $allowTypes = $this->getImportAllowedSecondTypes(2, $param['user_info']);
        $allowTypesId = array_column($allowTypes, 'id');

        /** @var Collection $existSupplies */
        $existSupplies = app($this->repository)->entity
            ->select(['office_supplies_no', 'office_supplies_name'])->get();
        $existNo = $existSupplies->pluck('office_supplies_no')->all();
        if ($param['type'] == '2') {
            $existPrimaryKey = $existSupplies->pluck($param['primaryKey'])->all();
        }

        $tempNo = [];
        foreach ($data as $k => $v) {
            if ($param['type'] == '1') {
                // 判断数据中单号是否自身重复
                if (in_array($v['office_supplies_no'], $tempNo) && $v['office_supplies_no']) {
                    $data[$k]['importResult'] = importDataFail();
                    $data[$k]['importReason'] = importDataFail(trans("officesupplies.office_supplies_no_repeat"));
                    continue;
                } else {
                    $tempNo[] = $v['office_supplies_no'];
                }
                // 判断是否与数据库中的单号重复
                if (in_array($v['office_supplies_no'], $existNo) && $v['office_supplies_no']) {
                    $data[$k]['importResult'] = importDataFail();
                    $data[$k]['importReason'] = importDataFail(trans("officesupplies.office_supplies_no_repeat"));
                    continue;
                }
            }

            if ($param['type'] == '2') {
                $primaryKey = $param['primaryKey'];
                if (!in_array($v[$primaryKey], $existPrimaryKey)) {
                    $data[$k]['importResult'] = importDataFail();
                    $data[$k]['importReason'] = importDataFail(trans("officesupplies.0x043016"));
                    continue;
                }
            }
            if (empty($v['office_supplies_name'])) {
                $data[$k]['importResult'] = importDataFail();
                $data[$k]['importReason'] = importDataFail(trans("officesupplies.office_supplies_name_empty"));
            } elseif (empty($v['type_id'])) {
                $data[$k]['importResult'] = importDataFail();
                $data[$k]['importReason'] = importDataFail(trans("officesupplies.office_supplies_type_id_empty"));
            } elseif ($v['usage'] !== 0 && $v['usage'] !== 1) {
                $data[$k]['importResult'] = importDataFail();
                $data[$k]['importReason'] = importDataFail(trans("officesupplies.office_supplies_usage_inexistence"));
            } elseif (empty($tempData) || !in_array($v['type_id'], $tempData)) {
                $data[$k]['importResult'] = importDataFail();
                $data[$k]['importReason'] = importDataFail(trans("officesupplies.office_supplies_type_id_inexistence"));
            } elseif (!in_array($v['type_id'], $allowTypesId)) {
                $data[$k]['importResult'] = importDataFail();
                $data[$k]['importReason'] = importDataFail(trans("officesupplies.no_permission_for_this_type"));
            }elseif ($v['apply_controller']!==0&&$v['apply_controller']!==1) {
                $data[$k]['importResult'] = importDataFail();
                $data[$k]['importReason'] = importDataFail(trans("officesupplies.apply_controller_is_error"));
            }
        }

        return $data;
    }

    /**
     * [getPurchaseDetailById 获取采购详情]
     *
     * @param
     *
     * @return
     * @since  2016-11-01 创建
     *
     * @author miaochenchen
     *
     */
    public function getPurchaseDetailById($id)
    {
        $param = ['search' => ['office_supplies.id' => [$id]]];
        $result = $this->getPurchaseList($param);
        if (!empty($result)) {
            if (!empty($result['list'])) {
                return $result['list'][0];
            } else {
                return [];
            }
        } else {
            return ['code' => ['0x000003', 'common']];
        }
    }

    /**
     * 办公用品归还到期提醒
     *
     * @param
     *
     * @return array 处理后的消息数组
     *
     * @author miaochenchen
     *
     * @since 2016-11-04
     */
    public function officeSuppliesReturnExpireRemind()
    {
        $list = app($this->applyRepository)->officeSuppliesReturnExpireList();
        $messages = [];
        if (!empty($list)) {
            foreach ($list as $value) {
                $messages[] = [
                    'remindMark' => 'office_supplies-expire',
                    'toUser' => $value['apply_user'],
                    'contentParam' => ['officeSuppliesName' => $value['office_supplies_name'], 'returnDate' => $value['return_date']],
                    'stateParams' => []
                ];
            }
        }
        return $messages;
    }

    /**
     * 过滤函数过滤传入的查询条件，办公用品名称，把传入的名称换成传入id
     *
     * @return array office_supplies_name
     */
    public function filterStorageNameList(&$param, &$own)
    {
        $id = [];
        if (!isset($param['search']) || !isset($param['search']['office_supplies_name']) || empty($param['search']['office_supplies_name'])) {
            return [];
        }
        $id = DB::table('office_supplies')->select('id')->where('office_supplies_name', $param['search']['office_supplies_name']['1'], '%' . $param['search']['office_supplies_name'][0] . '%')->first();
        if ($id) {
            $id = $id->id;
            unset($param['search']['office_supplies_name']);
            return ['office_supplies_id' => [$id, 'like']];
        } else {
            unset($param['search']['office_supplies_name']);
            return ['office_supplies_id' => [[], 'in']];
        }
    }

    /**
     *
     * 过滤入库查询
     */
    public function filterStorageLists($param, $own)
    {
        // 0 申请 1 审批 2入库
        $permission_type = 2;
        $office_supplies_id_arr = $this->getAllowSuppliesId($own, $permission_type);
        if (empty($param['office_supplies_id'])) {
            if (count($office_supplies_id_arr) == 0) {
                return [
                    'office_supplies_storage.office_supplies_id' => [0, '=']
                ];
            }
            return [
                'office_supplies_storage.office_supplies_id' => [$office_supplies_id_arr, 'in']
            ];
        } else {
            //查询某个办公用品
            if (in_array($param['office_supplies_id'], $office_supplies_id_arr)) {
                return [
                    'office_supplies_storage.office_supplies_id' => [$param['office_supplies_id'], '=']
                ];
            } else {
                return [
                    'office_supplies_storage.office_supplies_id' => [0, '=']
                ];
            }
        }

    }

    /**
     * [createOfficeInfo 自定义字段创建办公用品信息（入库外发），为了外发写的方法]
     *
     * @param
     * @return
     * @since 2018-9-30
     * @author lixuanxuan
     *
     */
    public function createOfficeInfo($officeInfoData)
    {
        if (isset($officeInfoData['data']) && isset($officeInfoData['tableKey'])) {
            //如果数据为空，则unset这个字段，因为自定义字段需要判断必填非必填，（自定义字段定义的规则是isset判断）
            $isMultiply = false;
            foreach ($officeInfoData['data'] as $k => $v) {
                if (is_array($v)) {
                    // 明细外发
                    $isMultiply = true;
                }
                break;
            }
            if ($isMultiply) {
                $outsource = false;
                if (isset($officeInfoData['data']['outsource'])) {
                    $outsource = $officeInfoData['data']['outsource'];
                    unset($officeInfoData['data']['outsource']);
                }
                $tableKey = $officeInfoData['tableKey'] ?? "";
                $currentUserId = $officeInfoData['current_user_id'] ?? "";
                if (empty($tableKey) || empty($currentUserId)) {
                    return ['code' => ['0x023002', 'common']];//返回提交数据异常
                }

                $dataNum = 0;
                $success = 0;
                $dataForLog = [];
                $failedInfoArr = [];

                foreach ($officeInfoData['data'] as $k => $v) {
                    $dataNum++;
                    if (is_array($v)) {
                        $v = $this->fillStorageForeignData($v);
                        if (is_array($v) && isset($v['code'])) {
                            $failedInfoArr[$dataNum] = $dataNum . ': ' . $this->handleCustomInsertError($v);
                            continue;
                        }
                        $v['outsource'] = $outsource;
                        $data = [
                            'data' => $v,
                            'tableKey' => $tableKey,
                            'current_user_id' => $currentUserId
                        ];

                        $result = $this->insertOfficeInfoData($data);
                        if (isset($result['code'])) {
                            $failedInfoArr[$dataNum] = $dataNum . ': ' . $this->handleCustomInsertError($result);
                        } else {
                            $success++;
                            $dataForLog[] = $this->getLogPiece($result);
                        }
                    }
                }

                if (!empty($failedInfoArr)) {
                    // 有失败的
                    $errorNums = implode(', ', array_keys($failedInfoArr));
                    $errorDetail = implode(', ', array_values($failedInfoArr));
                    $errorInfo = trans('flow.0x030172', ['pieces' => $errorNums]) . ', ' . $errorDetail;
                    if ($success) {
                        // 有成功的
                        return [
                            'status' => 1,
                            'dynamic' => $errorInfo,
                            'dataForLog' => $dataForLog
                        ];
                    }
                    // 全都失败
                    return [
                        'code' => $errorInfo
                    ];
                }
                // 全都成功
                return [
                    'status' => 1,
                    'dataForLog' => $dataForLog
                ];
            } else {
                $officeInfoData['data'] = $data = $this->fillStorageForeignData($officeInfoData['data']);
                if (is_array($data) && isset($required['code'])) {
                    return $data;
                }
                //单外发
                $result = $this->insertOfficeInfoData($officeInfoData);
                if (isset($result['code'])) {
                    return ['code' => $this->handleCustomInsertError($result)];
                }
                return [
                    'status' => 1,
                    'dataForLog' => [$this->getLogPiece($result)]
                ];
            }
        }
        //返回提交数据异常
        return ['code' => ['0x023002', 'common']];
    }

    private function getLogPiece($id)
    {
        return [
            'table_to' => 'office_supplies_storage',
            'field_to' => 'id',
            'id_to' => $id
        ];
    }

    /***
     * 最先的验证外发必填字段
     * @param array $data 外发数据
     * @return array|bool
     */
    protected function hasAllRequiredOutSendFields($data)
    {
        if (empty($data['office_supplies_id'])) {
            return $this->getLangCode(OfficeSuppliesCode::EMPTY_OFFICE_SUPPLIES_ID);
        }
        return true;
    }

    /***
     * 补全办公用品入库时的外键信息
     * @param $data
     * @return mixed
     */
    protected function fillStorageForeignData($data)
    {
        $validate = $this->hasAllRequiredOutSendFields($data);
        if (is_array($validate) && isset($validate['code'])) {
            return $validate;
        }
        $supplyInfo = app($this->repository)->getDetail($data['office_supplies_id']);
        if (!$supplyInfo) {
            return $this->getLangCode(OfficeSuppliesCode::SUPPLY_NOT_EXIST);
        }
        $data['storage_date'] = !empty($data['storage_date']) ? $data['storage_date'] : date('Y-m-d');
        $data['storage_bill'] = !empty($data['storage_bill']) ? $data['storage_bill'] : $this->handleOutSendStorageBill($data);

        $data['arithmetic'] = $data['arithmetic'] ?? 1;

        $data['specifications'] = $supplyInfo->specifications;
        $data['stock_remind'] = $supplyInfo->stock_remind;
        $data['storage_remind_max'] = $supplyInfo->stock_remind ? $supplyInfo->remind_max : null;
        $data['storage_remind_min'] = $supplyInfo->stock_remind ? $supplyInfo->remind_min : null;

        return $data;
    }

    /***
     * 处理外发入库单号
     * @param $data
     * @return mixed|string
     */
    protected function handleOutSendStorageBill($data)
    {
        $createNoParam = [
            'no_type' => 'storage',
            'office_supplies_id' => $data['office_supplies_id']
        ];
        $noResult = $this->getCreateNo($createNoParam);
        $storageBill = empty($data['storage_bill']) ? '' : trim($data['storage_bill']);
        //为空生成默认入库单号
        if (empty($storageBill) || app($this->storageRepository)->storageBillExists($storageBill)) {
            $storageBill = $noResult['storage_bill'];
        }
        return $storageBill;
    }

    /***
     * 处理自定义字段插入返回错误，拼接错误信息
     * @param $result
     * @return string
     */
    public function handleCustomInsertError($result)
    {
        $info = '';
        if (isset($result['code'])) {
            if (is_array($result['code'])) {
                $info .= trans(implode('.', array_reverse($result['code'])));
            } elseif (is_string($result['code'])) {
                $info .= $result['code'];
            }
            if (isset($result['dynamic']) && is_string($result['dynamic'])) {
                $info .= ', ' . $result['dynamic'];
            }
        }
        return $info;
    }

    /**
     * 插入办公用品数据
     * @param $officeInfoData
     * @return bool
     */
    private function insertOfficeInfoData($officeInfoData)
    {
        $result = app($this->formModelingService)->addCustomData($officeInfoData['data'], $officeInfoData['tableKey']);
        return $result;
    }

    // 判断有没有$typeId类型的$fromId权限，0申请1审批2入库
    public function judgeTypePermission($typeId, $fromId, $own)
    {
        $permission = app($this->typePermissionRepository)->entity
            ->where('office_supplies_type_id', $typeId)
            ->where('permission_type', $fromId)
            ->first();
        if (!$permission) {
            return false;
        }
        if ($permission->manager_all == 1) {
            return true;
        }
        $managerDept = \GuzzleHttp\json_decode($permission->manager_dept, true);
        $managerRole = \GuzzleHttp\json_decode($permission->manager_role, true);
        $managerUser = \GuzzleHttp\json_decode($permission->manager_user, true);
        $own_dept_id = $own['dept_id'];
        $own_role_id_arr = $own['role_id'];
        $own_user_id = $own['user_id'];
        if (
            in_array($own_dept_id, $managerDept)
            || in_array($own_user_id, $managerUser)
            || array_intersect($own_role_id_arr, $managerRole)
        ) {
            return true;
        }
        return false;
    }

    /**
     * 入库详情权限
     */
    public function storageDetailPower($storageId)
    {
        $own = own();
        $storage = app($this->storageRepository)->entity
            ->with([
                'storageBelongsToType' => function ($query) {
                    $query->select('id', 'type_name', 'parent_id');
                }
            ])
            ->find($storageId);
        if (!$storage) {
            return ['code' => ['0x043018', 'officesupplies']];
        }
        $type = $storage->storageBelongsToType->parent_id ?? 0;
        if ($this->judgeTypePermission($type, 2, $own)) {
            return true;
        }
        return false;

    }

    public function officeSuppliesStorageAddPurview($data)
    {
        $supplyId = $data['office_supplies_id'] ?? 0;
        if (!$supplyId) {
            return $this->getLangCode(OfficeSuppliesCode::EMPTY_OFFICE_SUPPLIES_ID);
        }
        if (isset($data['storage_bill'])) {
            $exist = app($this->storageRepository)->entity->where('storage_bill', $data['storage_bill'])->count();
            if ($exist > 0) {
                return ['code' => ['0x043020', 'officesupplies']];
            }
        }
        $supplyInfo = app($this->repository)->entity
            ->select('id', 'type_id')
            ->with([
                'suppliesBelongsToType' => function ($query) {
                    $query->select('id', 'parent_id');
                }
            ])
            ->find($supplyId);
        $typeId = $supplyInfo->suppliesBelongsToType->parent_id ?? 0;
        $operator = $data['operator'] ?? '';
        if (!$operator) {
            return $this->getLangCode(OfficeSuppliesCode::EMPTY_OPERATOR);
        }
        $userInfo = app($this->userService)->getUserDeptAndRoleInfoByUserId($operator);
        if (!$this->judgeTypePermission($typeId, 2, $userInfo)) {
            return false;
        }
        return true;
    }

    // 添加入库记录后的操作
    public function officeSuppliesStorageAfterAdd($data)
    {
        // 更新基本信息表库存
        $supply = app($this->repository)->getDetail($data['office_supplies_id']);
        //如果没有设置加减符号，则arithmetic默认为1，(因为负数直接可以减)
        $data['operator'] = $data['operator'] ?? own('user_id');
        $data['money'] = $data['storage_amount'] * $data['price'];
        $data['arithmetic'] = $data['arithmetic'] ?? 1;
        if ($data['arithmetic']) {
            $supply->stock_total += $data['storage_amount'];
            $supply->stock_surplus += $data['storage_amount'];
        } else {
            $supply->stock_total -= $data['storage_amount'];
            $supply->stock_surplus -= $data['storage_amount'];
        }
        $supply->save();
        // 更新入库表，stock_surplus和type_id
        $update = [
            'stock_surplus' => (string)$supply->stock_surplus,
            'type_id' => $supply->type_id,
            'operator' => $data['operator'],
            'money' => (string)$data['money'],
            'stock_remind' => $supply->stock_remind,
            'storage_remind_max' => $supply->stock_remind ? $supply->remind_max : null,
            'storage_remind_min' => $supply->stock_remind ? $supply->remind_min : null,
            'current_stock' => (string)$supply->stock_surplus,
        ];
        app($this->storageRepository)->entity->where('id', $data['id'])->update($update);
        app($this->storageRepository)->entity->where('office_supplies_id', $data['office_supplies_id'])->update(['current_stock' => (string)$supply->stock_surplus]);
        return true;
    }

    // 获取有权限的二级分类,$type 0申请1审批2入库
    public function getImportAllowedSecondTypes($type, $own)
    {
        //先获取有权限的一级分类
        $parentTypes = app($this->typePermissionRepository)->getAllowTypeByOwn($own, $type);
        if (empty($parentTypes)) {
            return [];
        }
        $types = app($this->typeRepository)->getTypeInfoByParentID($parentTypes);
        return $types;
    }

    /***
     * code码转换code数组
     *
     * @param $code
     * @return array
     */
    public function getLangCode($code)
    {
        return ['code' => [$code, 'officesupplies']];
    }

    public function getOfficeSuppliesApplyName($id)
    {
        $idArray = explode(',', $id);

        return app($this->applyRepository)->getOfficeSuppliesApplyName($idArray);
    }

    public function getofficeSuppliesStorageName($id)
    {
        $idArray = explode(',', $id);
        return app($this->storageRepository)->getofficeSuppliesStorageName($idArray);
    }

    /**
     * 办公用品资本资料导出
     * @param  [type] $param [description]
     * @return [type]        [description]
     */
    public function exportOfficeSuppliesInfo($param)
    {

        $own = isset($param['user_info']) ? $param['user_info'] : own();
        $officeSuppliesList = $this->getOfficeSuppliesList($param, $own, 2);
        $typeData = app($this->typeRepository)->getFieldInfo([]);
        $typeData = array_column($typeData,null,'id');
        $header = [
            'office_supplies_no' => ['data' => trans('officesupplies.office_supplies_no'), 'style' => ['width' => '20']],
            'office_supplies_name' => ['data' => trans('officesupplies.item_name'), 'style' => ['width' => '30']],
            'top_type_name' => ['data' => trans('officesupplies.top_type_name'), 'style' => ['width' => '15']],
            'type_name' => ['data' => trans('officesupplies.item_category'), 'style' => ['width' => '20']],
            'specifications' => ['data' => trans('officesupplies.specifications'), 'style' => ['width' => '15']],
            'unit' => ['data' => trans('officesupplies.measurement_unit'), 'style' => ['width' => '15']],
            'reference_price' => ['data' => trans('officesupplies.reference_price'), 'style' => ['width' => '20']],
            'usage' => ['data' => trans('officesupplies.mode_of_use'), 'style' => ['width' => '20']],
            'stock_surplus' => ['data' => trans('officesupplies.current_stock'), 'style' => ['width' => '20']],
            'apply_controller' => ['data' => trans('officesupplies.apply_judge'), 'style' => ['width' => '20']]
        ];
        $data = [];
        $alldata = array_column($officeSuppliesList, 'type_has_many_supplies');
        $result = [];
        $result = array_reduce($alldata, 'array_merge', array());
        foreach ($result as $key => $value) {
            $data[$key]['office_supplies_no'] = $value['office_supplies_no'];
            $data[$key]['office_supplies_name'] = $value['office_supplies_name'];
            $data[$key]['stock_surplus'] = $value['stock_surplus'];
            $data[$key]['top_type_name'] = $typeData[$typeData[$value['type_id']]['parent_id']]['type_name']??'';
            $data[$key]['type_name'] = $this->parseTypeName($value['type_id']);
            $data[$key]['reference_price'] = $value['reference_price'] ?? 0;
            $data[$key]['unit'] = $value['unit'] ?? '';
            $data[$key]['usage'] = $this->parseUsageName($value['usage']);
            $data[$key]['specifications'] = $value['specifications'];
            $data[$key]['apply_controller'] = $value['apply_controller']==1?trans('officesupplies.forbid_apply'):trans('officesupplies.allow_apply');
        }
        return compact('header', 'data');
    }

    private function parseTypeName($typeId)
    {
        $detail = app($this->typeRepository)->getDetail($typeId);
        return $detail->type_name ?? '';
    }

    private function parseUsageName($id)
    {
        $usage = [trans('officesupplies.use'), trans('officesupplies.borrow')];

        return $usage[$id];
    }

    public function getPermissionOfficeSuppliesTypeList($params)
    {
        $params = $this->parseParams($params);
        $typeList = app($this->typeRepository)->getTypeList($params);
        $parent = [];
        $typeIds = [];
        $parent_ids = [];
        if ($typeList) {
            $typeList = $typeList->toArray();
            foreach ($typeList as $key => $value) {
                if (!empty($value['type_has_many_supplies_count'])) {
                    $typeList[$key]['has_children'] = 1;
                } else {
                    $typeList[$key]['has_children'] = 0;
                }
                if (!is_numeric($value['parent_id'])) {
                    array_push($parent, $typeList[$key]);
                    array_push($parent_ids, $typeList[$key]['id']);
                    unset($typeList[$key]);
                }
            }
            if (isset($params['last_type']) && $params['last_type'] == 1) {
                //对父级进行过滤，如果没有权限则删除
                // 0 申请 1 审批 2入库
                $permissions = $parent_ids;
                if (isset($params['type_from'])) {
                    $permissions = app($this->typePermissionRepository)->getPermission($parent_ids, $params['type_from'], $params['own']);
                }
                foreach ($parent as $k => $v) {
                    if (isset($v['id']) && in_array($v['id'], $permissions)) {
                        foreach ($typeList as $key => $val) {
                            if ($v['id'] == $typeList[$key]['parent_id']) {
                                array_push($typeIds, $typeList[$key]);
                            }
                        }
                    } else {
                        unset($parent[$k]);
                    }
                }
                return array_values($typeIds);
            }
        }
        return [];
    }

    /**
     * 办公用品库存导出
     * @param $param
     * @return mixed
     * @creatTime 2021/1/6 18:39
     * @author [dosy]
     */
    public function exportOfficeSuppliesStatistical($param)
    {
        $own = $param['user_info'];
        $data = $this->getOfficeSuppliesList([], $own);

        $export['sheetName'] = trans('officesupplies.office_supplies_statistics');
        $export['header'] = [
            'office_supplies_name' => trans('officesupplies.item_name'),
            'type_name' => trans('officesupplies.item_category'),
            'stock_surplus' => trans('officesupplies.current_stock'),
            'office_supplies_no' => trans('officesupplies.office_supplies_no'),
            'unit' => trans('officesupplies.measurement_unit'),
            'specifications' => trans('officesupplies.specifications'),
            'reference_price' => trans('officesupplies.reference_price'),
        ];
        foreach ($data as $type) {
            if (isset($type['type_has_many_supplies'])) {
                foreach ($type['type_has_many_supplies'] as $value) {
                    $export['data'][] = [
                        'office_supplies_name' => $value['office_supplies_name'],
                        'type_name' => $type['type_name'],
                        'stock_surplus' => $value['stock_surplus'],
                        'office_supplies_no' => $value['office_supplies_no'],
                        'unit' => $value['unit'],
                        'specifications' => $value['specifications'],
                        'reference_price' => $value['reference_price'],
                    ];
                }
            }
        }
        return $export;
    }
}
