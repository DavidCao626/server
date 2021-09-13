<?php

namespace App\EofficeApp\OfficeSupplies\Permissions;


use App\EofficeApp\OfficeSupplies\Repositories\OfficeSuppliesPermissionRepository;
use App\EofficeApp\OfficeSupplies\Repositories\OfficeSuppliesApplyRepository;
use App\EofficeApp\OfficeSupplies\Repositories\OfficeSuppliesRepository;
use App\EofficeApp\OfficeSupplies\Repositories\OfficeSuppliesStorageRepository;
use App\EofficeApp\OfficeSupplies\Repositories\OfficeSuppliesTypeRepository;
use App\EofficeApp\OfficeSupplies\Services\OfficeSuppliesService;

class OfficeSuppliesPermission
{
    private $service;
    private $repository;
    private $applyRepository;
    private $storageRepository;
    private $typeRepository;
    private $permissionRepository;
    public $rules = [
        'modifyOfficeSupplies' => 'modifyOrDeleteOfficeSupplies',
        'deleteOfficeSupplies' => 'modifyOrDeleteOfficeSupplies',
        'createStorageRecord' => 'createStorage',
        'createQuickStorageRecord' => 'createStorage',
        'getOfficeSuppliesList' => 'judgeTypeFrom',
    ];

    public function __construct()
    {
        $this->service = OfficeSuppliesService::class;
        $this->repository = OfficeSuppliesRepository::class;
        $this->applyRepository = OfficeSuppliesApplyRepository::class;
        $this->typeRepository = OfficeSuppliesTypeRepository::class;
        $this->permissionRepository = OfficeSuppliesPermissionRepository::class;
        $this->storageRepository = OfficeSuppliesStorageRepository::class;
    }

    // 获取申请或者审批详情，from_type:0为申请，1为审批
    // 有申请菜单权限且申请人为本人或者有审批菜单权限且有该分类审批权限
    public function getApplyRecord($own, $data, $urlData)
    {
        $applyId = $urlData['applyId'] ?? 0;
        if (!$applyId) {
            return false;
        }
        $applyInfo = app($this->applyRepository)->getApplyDetailForCheck($applyId);
        if (!$applyInfo) {
            return $this->getLangCode(OfficeSuppliesCode::APPLY_NOT_EXIST);
        }
        $applyUser = $applyInfo->apply_user;
        $type = $applyInfo->applyBelongsToSupplies->suppliesBelongsToType->parent_id ?? 0;
        if (in_array(268, $own['menus']['menu']) && $applyUser == $own['user_id']) {
            if (app($this->service)->judgeTypePermission($type, 0, $own)) {
                return true;
            }
        }
        if (in_array(269, $own['menus']['menu'])) {
            if (app($this->service)->judgeTypePermission($type, 1, $own)) {
                return true;
            }
        }
        return false;
    }

    //删除申请记录权限
    public function deleteApplyRecord($own, $data, $urlData)
    {
        $applyId = $urlData['applyId'] ?? 0;
        if (!$applyId) {
            return false;
        }
        $applyInfo = app($this->applyRepository)->getApplyDetailForCheck($applyId);
        if (!$applyInfo) {
            return $this->getLangCode(OfficeSuppliesCode::APPLY_NOT_EXIST);
        }
        $applyUser = $applyInfo->apply_user;
        $applyStatus = $applyInfo->apply_status;
        $returnStatus = $applyInfo->return_status;
        $type = $applyInfo->applyBelongsToSupplies->suppliesBelongsToType->parent_id ?? 0;
        $usage = $applyInfo->applyBelongsToSupplies->usage;
        // 有申请菜单权限且申请人为自己且审批中或未通过且有该分类申请权限
        if (
            in_array(268, $own['menus']['menu']) &&
            $applyUser == $own['user_id'] &&
            ($applyStatus == 0 || $applyStatus == 2)
        ) {
            if (app($this->service)->judgeTypePermission($type, 0, $own)) {
                return true;
            }
        }
        // 有审批菜单权限且 (未通过 或者（使用已通过或借用已归还）)且有该分类审批权限
        if (
            in_array(269, $own['menus']['menu']) &&
            ($applyStatus == 2 || (($usage == 0 && $applyStatus == 1) || ($usage == 1 && $returnStatus == 1)))
        ) {
            if (app($this->service)->judgeTypePermission($type, 1, $own)) {
                return true;
            }
        }
        return false;
    }

    /**
     * 批量申请权限检查
     * @param $own
     * @param $data
     * @param $urlData
     * @return array|bool
     * @creatTime 2021/1/15 18:32
     * @author [dosy]
     */
    public function batchCreateApplyRecord($own, $data, $urlData){
        $officeSupplyIds = [];
        foreach ($data as $key => $value) {
            if (!isset($value['apply_bill']) || empty($value['apply_bill']) || empty($value['office_supplies_id']) || empty($value['apply_number']) || $value['apply_number'] <= 0) {
                unset($data[$key]);
                continue;
            }
            if (!empty($value['office_supplies_id'])) {
                $officeSupplyIds[] = $value['office_supplies_id'];
            }
        }
        $officeSupplyIds = array_unique($officeSupplyIds);
        $officeSupply = app($this->repository)->entity
            ->select('id', 'office_supplies_name', 'type_id')
            ->with([
                'suppliesBelongsToType' => function ($query) {
                    $query->select('id', 'parent_id');
                }
            ])->find($officeSupplyIds)->toArray();
        if (count($officeSupplyIds) > count($officeSupply)) {
            return $this->getLangCode(OfficeSuppliesCode::SUPPLY_NOT_EXIST);
        }
        foreach ($officeSupply as $value) {
            if (!app($this->service)->judgeTypePermission($value['supplies_belongs_to_type']['parent_id'], 0, $own)) {
                return  ['code' => ['0x000003', 'common'], 'dynamic' => $value['office_supplies_name'].trans("officesupplies.0x043021")];
            }
        }
        return true;
    }

    /**
     * 新建申请：必须要有该物品的申请权限
     * @param $own
     * @param $data
     * @param $urlData
     * @return array|bool
     * @creatTime 2021/1/11 17:59
     * @author [dosy]
     */
    public function createApplyRecord($own, $data, $urlData)
    {
         $officeSupplyId = $data['office_supplies_id'] ?? 0;
         if (!$officeSupplyId) {
             return false;
         }
         $officeSupply = app($this->repository)->entity
             ->select('id', 'type_id')
             ->with([
                 'suppliesBelongsToType' => function ($query) {
                     $query->select('id', 'parent_id');
                 }
             ])->find($officeSupplyId);
         if (!$officeSupply) {
             return $this->getLangCode(OfficeSuppliesCode::SUPPLY_NOT_EXIST);
         }
         $type = $officeSupply->suppliesBelongsToType->parent_id;
         if (app($this->service)->judgeTypePermission($type, 0, $own)) {
             return true;
         }
         return false;
    }

    //审批/归还权限,service中已对状态之类进行判断，此处仅判断该物品审批权限
    public function modifyApplyRecord($own, $data, $urlData)
    {
        $applyId = $urlData['applyId'] ?? 0;
        if (!$applyId) {
            return false;
        }
        $applyInfo = app($this->applyRepository)->getApplyDetailForCheck($applyId);
        if (!$applyInfo) {
            return $this->getLangCode(OfficeSuppliesCode::APPLY_NOT_EXIST);
        }
        $type = $applyInfo->applyBelongsToSupplies->suppliesBelongsToType->parent_id ?? 0;
        if (app($this->service)->judgeTypePermission($type, 1, $own)) {
            return true;
        }
        return false;
    }

    // 创建办公用品：必须有该类别的入库权限
    public function createOfficeSupplies($own, $data, $urlData)
    {
        $typeId = $data['type_id'] ?? 0;
        if (!$typeId) {
            return $this->getLangCode(OfficeSuppliesCode::ILLEGAL);
        }
        $typeInfo = app($this->typeRepository)->entity->find($typeId);
        if (!$typeInfo) {
            return false;
        }
        $parentType = $typeInfo->parent_id;
        if (app($this->service)->judgeTypePermission($parentType, 2, $own)) {
            return true;
        }
        return false;
    }

    // 编辑/删除办公用品：必须有该类别的入库权限
    public function modifyOrDeleteOfficeSupplies($own, $data, $urlData)
    {
        $supplyId = $urlData['officeSuppliesId'] ?? 0;
        if (!$supplyId) {
            return $this->getLangCode(OfficeSuppliesCode::ILLEGAL);
        }
        $officeSupply = app($this->repository)->entity
            ->select('id', 'type_id')
            ->with([
                'suppliesBelongsToType' => function ($query) {
                    $query->select('id', 'parent_id');
                }
            ])->find($supplyId);
        if (!$officeSupply) {
            return $this->getLangCode(OfficeSuppliesCode::SUPPLY_NOT_EXIST);
        }
        $type = $officeSupply->suppliesBelongsToType->parent_id;
        if (app($this->service)->judgeTypePermission($type, 2, $own)) {
            return true;
        }
        return false;
    }

    // 新建入库
    public function createStorage($own, $data, $urlData)
    {
        $supplyId = $data['office_supplies_id'] ?? 0;
        if (!$supplyId) {
            return false;
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
        if (!app($this->service)->judgeTypePermission($typeId, 2, $own)) {
            return false;
        }
        return true;
    }

    // 删除入库记录权限
    public function deleteStorageRecord($own, $data, $urlData)
    {
        $storageId = $urlData['storageId'];
        $storage = app($this->storageRepository)->entity
            ->select('id', 'type_id')
            ->with([
                'storageBelongsToType' => function ($query) {
                    $query->select('id', 'parent_id');
                }
            ])
            ->find($storageId);
        if (!$storage) {
            return false;
        }
        $parentId = $storage->storageBelongsToType->parent_id ?? 0;
        if (!app($this->service)->judgeTypePermission($parentId, 2, $own)) {
            return false;
        }
        return true;
    }

    //office-supplies/{office_supplies_id}
    //    //    266: 基本资料
    //    //    267: 入库管理
    //    //    268: 使用申请
    public function getOfficeSupplies($own, $data, $urlData)
    {
        $id = $urlData['officeSuppliesId'];
        $supply = app($this->repository)->entity
            ->select('id', 'type_id')
            ->with(['suppliesBelongsToType' => function ($query) {
                $query->select('id', 'parent_id');
            }])
            ->find($id);
        if (!$supply) {
            return $this->getLangCode(OfficeSuppliesCode::SUPPLY_NOT_EXIST);
        }
        $typeId = $supply->suppliesBelongsToType->parent_id;
        //有基本资料菜单或入库菜单
        if (in_array(266, $own['menus']['menu']) || in_array(267, $own['menus']['menu'])) {
            if (app($this->service)->judgeTypePermission($typeId, 2, $own)) {
                return true;
            }
        }
        // 有申请菜单
        if (in_array(268, $own['menus']['menu'])) {
            if (app($this->service)->judgeTypePermission($typeId, 0, $own)) {
                return true;
            }
        }
        return false;
    }

    public function getOfficeSuppliesSecondTypeList($own, $data, $urlData)
    {
        return $this->judgeTypeFrom($own, $data, $urlData, 1);
    }

    // 判断有type_from的对应的菜单权限,$key 0:266或267，1：仅266(基本资料)，2：仅267(入库管理)
    public function judgeTypeFrom($own, $data, $urlData, $key = 0)
    {
        $typeFrom = $urlData['typeFrom'];
        if (!in_array($typeFrom, [0, 1, 2])) {
            return false;
        }
        if (!isset($own['menus']['menu'])) {
            return false;
        }
        $ownMenus = $own['menus']['menu'];
        switch ($typeFrom) {
            case 0 :
                return in_array(268, $ownMenus);
            case 1 :
                return in_array(269, $ownMenus);
            case 2 :
                if ($key === 0) {
                    return in_array(267, $ownMenus) || in_array(266, $ownMenus);
                } elseif ($key === 1) {
                    return in_array(266, $ownMenus);
                } elseif ($key === 2) {
                    return in_array(267, $ownMenus);
                }
        }
    }

    public function getLangCode($code)
    {
        return ['code' => [$code, 'officesupplies']];
    }


}
