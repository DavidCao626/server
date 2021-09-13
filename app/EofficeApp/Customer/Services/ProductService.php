<?php

namespace App\EofficeApp\Customer\Services;

use App\EofficeApp\Base\BaseService;
use App\EofficeApp\Customer\Repositories\CustomerRepository;
use App\EofficeApp\Customer\Repositories\ProductRepository;
use App\EofficeApp\Customer\Repositories\SupplierRepository;
use DB;

class ProductService extends BaseService
{
    const CUSTOM_TABLE_KEY = 'customer_product';

    public function __construct()
    {
        $this->repository    = 'App\EofficeApp\Customer\Repositories\ProductRepository';
        $this->formModelingService = 'App\EofficeApp\FormModeling\Services\FormModelingService';
    }

    public function lists($params, $own)
    {
        $params = $this->parseParams($params);
        $result = app($this->formModelingService)->getCustomDataLists($params, self::CUSTOM_TABLE_KEY, $own);
        if(isset($result['list']) && $result['list']){
            foreach ($result['list'] as $key => $vo){
                // 被销售记录占用也不可删除
                $result['list'][$key]->canDelete = true;
                if ($validate = ProductRepository::validateDeletes([$vo->product_id])) {
                    $result['list'][$key]->canDelete = false;
                }
            }
        }
        return $result;
    }

    public function store(array $data)
    {
        if(!$result = app($this->formModelingService)->addCustomData($data, self::CUSTOM_TABLE_KEY)){
            return ['code' => ['0x024004', 'customer']];
        }
        if(isset($result['code'])){
            return $result;
        }
        self::updatePinYin($data,$result);
        return $result;
    }

    // 外发创建产品
    public function flowStore(array $params)
    {
        $data = $params['data'] ?? [];
        $data['product_creator'] = (isset($data['product_creator']) && $data['product_creator']) ? $data['product_creator'] : $params['current_user_id'];
        $result = $this->store($data);
        if(isset($result['code'])){
            return $result;
        }
        return [
            'status' => 1,
            'dataForLog' => [
                [
                    'table_to' => 'customer_product',
                    'field_to' => 'product_id',
                    'id_to' => $result
                ]
            ]
        ];
    }

    public function update($id, $data, $own)
    {
        if (!$validate = ProductRepository::validatePermission([CustomerRepository::UPDATE_MARK], $id, $own)) {
            return ['code' => ['0x024003', 'customer']];
        }
        $data['outsourceForEdit'] = true;
        if (!$result = app($this->formModelingService)->editCustomData($data, self::CUSTOM_TABLE_KEY, $id)) {
            return $result;
        }
        unset($data['outsourceForEdit']);
        if(isset($result['code'])){
            return $result;
        }
        self::updatePinYin($data,$id);
        return true;
    }

    public function delete(array $ids, $own)
    {
        list($deleteIds) = ProductRepository::getPermissionIds([CustomerRepository::DELETE_MARK], $own, $ids);
        if (count($ids) !== count($deleteIds)) {
            return ['code' => ['0x024003', 'customer']];
        }
        // 被销售记录占用也不可删除
        if ($validate = ProductRepository::validateDeletes($ids)) {
            return ['code' => ['0x024013', 'customer']];
        }
        return app($this->repository)->deleteById($ids);
    }

    public function updatePinYin($data,$id){
        list($product_name_py,$product_name_zm)= convert_pinyin(trim($data['product_name']));
        app($this->repository)->updateData(['product_name_py' => $product_name_py,'product_name_zm'=> $product_name_zm], ['product_id' => $id]);
    }

    public function exportProduct($param){
        $own = $param['user_info']['user_info'];
        return app($this->formModelingService)->exportFields('customer_product', $param, $own, trans('customer.customer_product'));
    }

    // 获取需要导入的字段
    public function getProductFields($param)
    {
        return app($this->formModelingService)->getImportFields(self::CUSTOM_TABLE_KEY, $param, trans("customer.customer_product_import_template"));
    }

    // 产品数据导入过滤
    public function importFilter($data, $param){
        if($data && is_array($data)){
            $model = app($this->formModelingService);
            $supplier_id = SupplierRepository::getSupplierSingleFields('supplier_id',[]);
            foreach ($data as $key => $vo){
                // 自定义字段验证
                $result = $model->importDataFilter(self::CUSTOM_TABLE_KEY, $vo, $param);
                if (!empty($result)) {
                    $data[$key]['importResult'] = importDataFail();
                    $data[$key]['importReason'] = importDataFail($result);
                    continue;
                }
                // 供应商id验证
                if(key_exists('supplier_id',$vo) && $vo['supplier_id']){
                    if(!in_array($vo['supplier_id'],$supplier_id)){
                        $data[$key]['importResult'] = importDataFail();
                        $data[$key]['importReason'] = importDataFail(trans('customer.no_supplier'));
                        continue;
                    }
                }
                $data[$key]['product_creator'] = $param['user_info']['user_id'];
                $data[$key]['importResult'] = importDataSuccess();
            }
        }
        return $data;
    }
    // 产品数据导入
    public function importProduct($data, $params){
        app($this->formModelingService)->importCustomData(self::CUSTOM_TABLE_KEY, $data, $params);
        return ['data' => $data];

    }

    public function afterImport($importData){
        $data = $importData['data'] ?? [];
        $id   = $importData['id'] ?? 0;
        self::updatePinYin($data,$id);
        return true;
    }

    // 产品外发更新
    public function flowOutUpdate($data){
        if (empty($data) || empty($data['unique_id'])){
            return ['code' => ['0x024002','customer']];
        }
        $own = own();
        $user_id = $own ? $own['user_id'] : $data['current_user_id'];
        $updateData = $data['data'] ?? [];
        if(isset($updateData['product_creator'])){
            return ['code' => ['contact_record_creator','customer']];
        }
        unset($updateData['current_user_id']);
        $dataDetail = app($this->formModelingService)->getCustomDataDetail(self::CUSTOM_TABLE_KEY, $data['unique_id']);
        if(!$dataDetail){
            return ['code' => ['0x024011','customer']];
        }
        if(!isset($updateData['product_name'])){
            $updateData['product_name'] = $dataDetail['product_name'];
        }
        if(!isset($updateData['supplier_id'])){
            $updateData['supplier_id'] = $dataDetail['supplier_id'];
        }
        $result = $this->update($data['unique_id'],$updateData,['user_id' => $user_id]);
        if(isset($result['code'])){
            return $result;
        }
        return [
            'status' => 1,
            'dataForLog' => [
                [
                    'table_to' => self::CUSTOM_TABLE_KEY,
                    'field_to' => 'product_id',
                    'id_to'    => $data['unique_id']
                ]
            ]
        ];
        return ['code' => ['0x000021','common']];
    }

    // 产品外发删除
    public function flowOutDelete($data){
        if (empty($data) || empty($data['unique_id'])){
            return ['code' => ['0x024002','customer']];
        }
        $own = own();
        $productId = explode(',',$data['unique_id']);
        $ids  = DB::table(self::CUSTOM_TABLE_KEY)->select(['product_id'])->whereIn('product_id', $productId)->count();
        if(count($productId) != $ids){
            return ['code' => ['0x024011','customer']];
        }
        $result = $this->delete($productId,$own);
        if(isset($result['code'])){
            return $result;
        }
        return [
            'status' => 1,
            'dataForLog' => [
                [
                    'table_to' => self::CUSTOM_TABLE_KEY,
                    'field_to' => 'product_id',
                    'id_to'    => $data['unique_id']
                ]
            ]
        ];
    }
}
