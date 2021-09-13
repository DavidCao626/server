<?php

namespace App\EofficeApp\Customer\Services;

use App\EofficeApp\Base\BaseService;
use App\EofficeApp\Customer\Repositories\CustomerRepository;
use App\EofficeApp\Customer\Repositories\SupplierRepository;

class SupplierService extends BaseService
{

    const CUSTOM_TABLE_KEY = 'customer_supplier';

    public function __construct()
    {
        $this->repository              = 'App\EofficeApp\Customer\Repositories\SupplierRepository';
        $this->contactRecordRepository = 'App\EofficeApp\Customer\Repositories\ContactRecordRepository';
        $this->formModelingService = 'App\EofficeApp\FormModeling\Services\FormModelingService';
    }

    public function lists(array $params, array $own)
    {
        $params = $this->parseParams($params);
        $result = app($this->formModelingService)->getCustomDataLists($params, self::CUSTOM_TABLE_KEY, $own);
        if(isset($result['list']) && $result['list'] && is_array($result['list'])){
            foreach ($result['list'] as $key => $vo){
                list($deleteIds) = SupplierRepository::getPermissionIds([CustomerRepository::DELETE_MARK], $own,[$vo->supplier_id]);
                $result['list'][$key]->canDelete = true;
                if (count([$vo->supplier_id]) !== count($deleteIds) && $own['user_id'] != 'admin') {
                    $result['list'][$key]->canDelete = false;
                }
                // 被产品占用也不可删除
                if ($validate = SupplierRepository::validateDeletes([$vo->supplier_id])) {
                    $result['list'][$key]->canDelete = false;
                }
            }
        }
        return $result;
    }

    // 编辑权限，创建人
    public function update(int $id, array $data, array $own)
    {
        if (!$validate = SupplierRepository::validatePermission([CustomerRepository::UPDATE_MARK], $id, $own)) {
            if($own['user_id'] != 'admin'){
                return ['code' => ['0x024003', 'customer']];
            }
        }
        if(SupplierRepository::checkRepeatName(trim($data['supplier_name']),$id)){
            return ['code' => ['repeat_supplier_name', 'customer']];
        }
        if (!$result = app($this->formModelingService)->editCustomData($data, self::CUSTOM_TABLE_KEY, $id)) {
            return $result;
        }
        if(isset($result['code'])){
            return $result;
        }
        self::updatePinYin($data,$id);
        return true;
    }

    public function store(array $data, array $own)
    {
        if($data && isset($data['supplier_name']) && $data['supplier_name']){
            if($result = SupplierRepository::checkRepeatName(trim($data['supplier_name']))){
                return ['code' => ['repeat_supplier_name', 'customer']];
            }
        }
        if (!$id = app($this->formModelingService)->addCustomData($data, self::CUSTOM_TABLE_KEY)) {
            return ['code' => ['0x024004', 'common']];
        }
        if(isset($id['code'])){
            return $id;
        }
        self::updatePinYin($data,$id);
        return true;
    }

    // 删除权限，创建人
    public function delete(array $ids, $own)
    {
        list($deleteIds) = SupplierRepository::getPermissionIds([CustomerRepository::DELETE_MARK], $own, $ids);
        if (count($ids) !== count($deleteIds) && $own['user_id'] != 'admin') {
            return ['code' => ['0x024003', 'customer']];
        }
        // 被产品占用也不可删除
        if ($validate = SupplierRepository::validateDeletes($ids)) {
            return ['code' => ['0x024014', 'customer']];
        }
        return app($this->repository)->deleteById($ids);
    }

    // 获取需要导入的字段
    public function getImportFields($param)
    {
        return app($this->formModelingService)->getImportFields(self::CUSTOM_TABLE_KEY, $param, trans("customer.customer_supplier_import_template"));
    }

    // 导入
    public function importSupplier($data, $params)
    {
        app($this->formModelingService)->importCustomData(self::CUSTOM_TABLE_KEY, $data, $params);
        return ['data' => $data];
    }

    // 导入过滤
    public function importFilter($importDatas, $params = [])
    {
        $own         = $params['user_info'] ?? [];
        $tempSupplierName = [];
        $model = app($this->formModelingService);
        foreach ($importDatas as $index => $data) {
            $importDatas[$index]['importResult'] = importDataFail();
            // 自定义字段验证
            $result = $model->importDataFilter(self::CUSTOM_TABLE_KEY, $data, $params);
            if (!empty($result)) {
                $importDatas[$index]['importReason'] = importDataFail($result);
                continue;
            }
            // 列表重名验证
            if(isset($data['supplier_name']) && $data['supplier_name']){
                // 导入excel行重名验证
                if($tempSupplierName && in_array($data['supplier_name'],$tempSupplierName)){
                    $importDatas[$index]['importResult'] = importDataFail();
                    $importDatas[$index]['importReason'] = importDataFail(trans('customer.repeat_supplier_name'));
                    continue;
                }
                if(SupplierRepository::checkRepeatName(trim($data['supplier_name']))){
                    $importDatas[$index]['importResult'] = importDataFail();
                    $importDatas[$index]['importReason'] = importDataFail(trans('customer.repeat_supplier_name'));
                    continue;
                }
            }
            $tempSupplierName[] = $data['supplier_name'];
            $importDatas[$index]['importResult'] = importDataSuccess();
        }
        return $importDatas;
    }

    // 导入之后操作
    public function afterImport($importData)
    {
        $updateData  = [];
        $data = $importData['data'] ?? [];
        $id   = $importData['id'] ?? 0;
        $userId      = isset($data['supplier_creator']) && !empty($data['supplier_creator']) ? $data['supplier_creator'] : ($importData['param']['user_info']['user_id'] ?? '');
        if (!$id) {
            return true;
        }
        $updateData                    = [];
        $updateData['supplier_creator'] = $userId;
        $updateData['created_at']      = date('Y-m-d H:i:s');
        list($updateData['supplier_name_py'],$updateData['supplier_name_zm'])= convert_pinyin($data['supplier_name']);
        return SupplierRepository::updateSupplier($id, $updateData);
    }

    public function addCustomerSupplierPurview($data){
        if($data && isset($data['supplier_name']) && $data['supplier_name']){
            if(SupplierRepository::checkRepeatName(trim($data['supplier_name']))){
                return ['code' => ['repeat_supplier_name', 'customer']];
            }
            return true;
        }
        return ['code' => ['0x024003', 'customer']];
    }

    public function exportSuppliers($param){
        $own = $param['user_info']['user_info'];
        return app($this->formModelingService)->exportFields('customer_supplier', $param, $own, trans("customer.customer_supplier_manager"));
    }

    // 更新名称拼音
    public function updatePinYin($data,$id){
        list($supplier_name_py,$supplier_name_zm)= convert_pinyin(trim($data['supplier_name']));
        app($this->repository)->updateData(['supplier_name_py' => $supplier_name_py,'supplier_name_zm'=> $supplier_name_zm], ['supplier_id' => $id]);
    }
}
