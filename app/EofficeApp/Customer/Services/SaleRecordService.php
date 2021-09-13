<?php

namespace App\EofficeApp\Customer\Services;

use App\EofficeApp\Base\BaseService;
use App\EofficeApp\Customer\Repositories\CustomerRepository;
use App\EofficeApp\Customer\Repositories\SaleRecordRepository;

class SaleRecordService extends BaseService
{

    private static $hasDeletePermissionIds = ['admin'];

    public function __construct()
    {
        $this->repository              = 'App\EofficeApp\Customer\Repositories\SaleRecordRepository';
        $this->userRepository          = 'App\EofficeApp\User\Repositories\UserRepository';
        $this->contactRecordRepository = 'App\EofficeApp\Customer\Repositories\ContactRecordRepository';
    }

    public function lists($params, $own)
    {
        $params = $this->parseParams($params);
        $response = $params['response'] ?? '';
        $customerIds   = CustomerRepository::getViewIds($own);
        $originSearchs = $params['search'] ?? [];
        if ($customerIds !== CustomerRepository::ALL_CUSTOMER && !isset($originSearchs['customer_id'])) {
            $originSearchs['customer_id'] = [$customerIds, 'in'];
        }
        $params['search'] = $originSearchs;
        $result = $this->response(app($this->repository), 'total', 'lists', $params);
        if (!isset($result['list']) || empty($result['list'])) {
            return $result;
        }
        $ids             = array_column((array) $result['list'], 'sales_id');
        list($updateIds) = SaleRecordRepository::getPermissionIds([CustomerRepository::UPDATE_MARK], $own, $ids);
        foreach ($result['list'] as $index => $item) {
            $item['product_name'] = $item['sales_record_to_product'] ? $item['sales_record_to_product']['product_name'] : '';
            if($updateIds){
                if (in_array($item['sales_id'], $updateIds)) {
                    $item['hasUpdatePermission'] = true;
                }
            }
            $result['list'][$index] = $item;
        }
        if($response){
            return  $result['list'];
        }
        return $result;
    }

    public function store($data, $own)
    {
        $validate = SaleRecordRepository::validateInput($data, 0, $own);
        if (isset($validate['code'])) {
            return $validate;
        }
        return app($this->repository)->insertData($data);
    }

    // 外发创建合同
    public function flowStore(array $params)
    {
        $userId = (isset($params['sales_creator']) && $params['sales_creator']) ? $params['sales_creator'] : $params['current_user_id'];
        $params['sales_creator'] = $userId;
        unset($params['current_user_id']);
        $own    = own();
        if(!empty($params['discount'])){
            $params['discount'] = trim($params['discount'],'%');
        }
        $result = $this->store($params, $own);
        if(isset($result['code'])){
            return $result;
        }
        return [
            'status' => 1,
            'dataForLog' => [
                [
                    'table_to' => 'customer_sales_record',
                    'field_to' => 'sales_id',
                    'id_to' => $result['sales_id']
                ]
            ]
        ];
    }

    public function show(int $id, array $own)
    {
        if (!$validate = SaleRecordRepository::validatePermission([CustomerRepository::VIEW_MARK], $id, $own)) {
            return ['code' => ['0x024003', 'customer']];
        }
        return app($this->repository)->show($id);
    }

    public function delete(array $ids, $own)
    {
        list($updateIds) = SaleRecordRepository::getPermissionIds([CustomerRepository::UPDATE_MARK], $own, $ids);
        if (count($ids) !== count($updateIds)) {
            return ['code' => ['0x024003', 'customer']];
        }
        return app($this->repository)->deleteById($ids);
    }

    // 编辑权限，只有创建人和具有客户编辑权限
    public function update(int $id, array $data, array $own)
    {
        $validate = SaleRecordRepository::validateInput($data, $id, $own);
        if (isset($validate['code'])) {
            return $validate;
        }
        if (!$validate = SaleRecordRepository::validatePermission([CustomerRepository::UPDATE_MARK], $id, $own)) {
            return ['code' => ['0x024003', 'customer']];
        }
        return app($this->repository)->updateData($data, ['sales_id' => $id]);
    }

    // 销售记录外发更新
    public function flowOutUpdate($data){
        if (empty($data) || empty($data['unique_id'])){
            return ['code' => ['0x024002','customer']];
        }
        $own = own();
        $updateData = $data['data'] ?? [];
        $user_id = $own ? $own['user_id'] : $updateData['current_user_id'];

        if(isset($updateData['product_creator'])){
            return ['code' => ['contact_record_creator','customer']];
        }
        unset($updateData['current_user_id']);

        $dataDetail = app($this->repository)->show($data['unique_id']);

        if(!$dataDetail){
            return ['code' => ['0x024011','customer']];
        };
        if(isset($updateData['product_id']) && $updateData['product_id'] == ''){
            return ['code' => ['0x024029','customer']];
        }
        if(!isset($updateData['product_id'])){
            $updateData['product_id'] = $dataDetail['product_id'];
        }
        if(isset($updateData['customer_id']) && $updateData['customer_id'] == ''){
            return ['code' => ['0x024026','customer']];
        }
        if(!isset($updateData['customer_id'])){
            $updateData['customer_id'] = $dataDetail['customer_id'];
        }
        if(isset($updateData['discount']) && $updateData['discount']){
            $updateData['discount'] = trim($updateData['discount'],'%');
        }
        $info = app($this->userRepository)->getUserAllData($user_id)->toArray();
        if($info){
            $role_ids = [];
            foreach ($info['user_has_many_role'] as $key => $vo) {
                $role_ids[] = $vo['role_id'];
            }
            $own = [
                'user_id' => $user_id,
                'dept_id' => $info['user_has_one_system_info']['dept_id'],
                'role_id' => $role_ids,
            ];
        }
        $result = $this->update($data['unique_id'],$updateData,$own);
        if(isset($result['code'])){
            return $result;
        }
        return [
            'status' => 1,
            'dataForLog' => [
                [
                    'table_to' => SaleRecordRepository::TABLE_NAME,
                    'field_to' => 'sales_id',
                    'id_to'    => $data['unique_id']
                ]
            ]
        ];
    }

    // 销售记录外发删除
    public function flowOutDelete($data){
        if (empty($data) || empty($data['unique_id'])){
            return ['code' => ['0x024002','customer']];
        }
        $params = $data['data'] ?? [];
        $own = own();
        $user_id = $own ? $own['user_id'] : $params['current_user_id'];
        $dataDetail = app($this->repository)->show($data['unique_id']);
        if(!$dataDetail){
            return ['code' => ['0x024011','customer']];
        };
        $salesId = explode(',',$data['unique_id']);
        if($result = $this->delete($salesId,['user_id' => $user_id])){
            if(isset($result['code'])){
                return $result;
            }
            return [
                'status' => 1,
                'dataForLog' => [
                    [
                        'table_to' => SaleRecordRepository::TABLE_NAME,
                        'field_to' => 'sales_id',
                        'id_to'    => $data['unique_id']
                    ]
                ]
            ];
        };
        return ['code' => ['0x024001','customer']];
    }

    public function getSaleRecord($salesId){
        $idArray = [];
        if($salesId){
            $idArray = explode(',', $salesId);
        }
        return SaleRecordRepository::getProductName($idArray);
    }
}
