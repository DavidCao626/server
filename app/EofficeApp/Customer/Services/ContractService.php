<?php

namespace App\EofficeApp\Customer\Services;

use App\EofficeApp\Base\BaseService;
use App\EofficeApp\Customer\Repositories\ContractRepository;
use App\EofficeApp\Customer\Repositories\CustomerRepository;
use App\EofficeApp\Elastic\Services\MessageQueue\ElasticsearchProducer;
use Illuminate\Support\Facades\Log;

class ContractService extends BaseService
{

    const CUSTOM_TABLE_KEY = 'customer_contract';
    // 合同类型标识id
    const CONTRACT_TYPE_ID = 14;
    // 附件标识
    const ATTACHMENT_INDEX = 'customer_contract';

    const REMIND_CONTRACT_COLLECTION_MARK = 'customer-payable';
    const REMIND_CONTRACT_EXPIRT_MARK     = 'customer-end';

    public function __construct()
    {
        $this->repository            = 'App\EofficeApp\Customer\Repositories\ContractRepository';
        $this->customerRepository    = 'App\EofficeApp\Customer\Repositories\CustomerRepository';
        $this->userRepository        = 'App\EofficeApp\User\Repositories\UserRepository';
        $this->systemComboboxService = 'App\EofficeApp\System\Combobox\Services\SystemComboboxService';
        $this->attachmentService     = 'App\EofficeApp\Attachment\Services\AttachmentService';
        $this->customerService       = 'App\EofficeApp\Customer\Services\CustomerService';
        $this->formModelingService   = 'App\EofficeApp\FormModeling\Services\FormModelingService';
        $this->calendarService       = 'App\EofficeApp\Calendar\Services\CalendarService';
    }

    public function lists(array $input, array $own)
    {
        $result = app($this->formModelingService)->getCustomDataLists($input, self::CUSTOM_TABLE_KEY, $own);
        if (!isset($result['list']) || empty($result['list'])) {
            return $result;
        }
        $ids                       = array_column((array) $result['list'], 'chance_id');
        list($result['updateIds']) = ContractRepository::getPermissionIds([CustomerRepository::UPDATE_MARK], $own, $ids);
        return $result;
    }

    public function filterContractLists(&$params,$own)
    {
        $result = $multiSearchs = $contractIds = [];
        if (isset($params['search']['multiSearch'])) {
            $multiSearchs = $params['search']['multiSearch'];
            unset($params['search']['multiSearch']);
        }
        $ids = CustomerRepository::getViewIds($own);
        if (!empty($multiSearchs)) {
            $contractIds = app($this->repository)->multiSearchIds($multiSearchs, $ids);
            $result      = ['customer_contract.contract_id' => [$contractIds, 'in']];
        }
        if ($ids === CustomerRepository::ALL_CUSTOMER) {
            return $result;
        }
        $result = array_merge($result, ['customer_contract.customer_id' => [$ids, 'in']]);
        return $result;
    }

    public function show(int $id, array $own)
    {
        $validate = ContractRepository::validatePermission([CustomerRepository::VIEW_MARK], $id, $own);
        if(!$validate){
            return ['code' => ['0x024003', 'customer']];
        }
        if($validate && isset($validate['code'])){
            return $validate;
        }
        if($result = app($this->formModelingService)->getCustomDataDetail(self::CUSTOM_TABLE_KEY, $id)){
            if(!empty($result['remind'])){
                foreach ($result['remind'] as $key => $vo){
                    if($vo->remind_date == '0000-00-00'){
                        $result['remind'][$key]->remind_date = '';
                    }
                }
            }
        };
        return $result;
    }

    public function store(array $data, array $own)
    {
        $userId = $own['user_id'] ?? '';
        $validate = ContractRepository::validateInput($data, $userId, false, $own);
        if (isset($validate['code'])) {
            return $validate;
        }
        // 收付款提醒
        $reminds = $data['remind'] ?? [];
        /*
        if (isset($data['remind'])) {
            $reminds = $data['remind'];
            unset($data['remind']);
        }
        */
        if(isset($data['time'])){
            $data['contract_start'] = isset($data['time']['startDate']) ? $data['time']['startDate'] : '';
            $data['contract_end']   = isset($data['time']['endDate']) ? $data['time']['endDate'] :'' ;
        }
        // 验证合同金额字段长度
        if(isset($data['contract_amount']) && $data['contract_amount']){
            if($length = $this->sctonum($data['contract_amount'],2)){
                if($nums = explode('.',$length)){
                    if(strlen($nums[0]) > 18){
                        return ['code' => ['error_moneys', 'customer']];
                    };
                };
            };
        }
        if (!$result = app($this->formModelingService)->addCustomData($data, self::CUSTOM_TABLE_KEY)) {
            return ['code' => ['0x024004', 'customer']];
        }
        if(isset($result['code'])){
            return $result;
        }
        // 提醒
        if (isset($data['contract_remind']) && $data['contract_remind']) {
            app($this->repository)->sendRemindMessage($data, $result, $userId);
        }
        // 收付款提醒
        if (!empty($reminds)) {
            // 兼容移动端 contract_remind_id = null
            foreach ($reminds as $key => $vo){
                if(array_key_exists('contract_remind_id',$vo) && is_null($vo['contract_remind_id'])){
                    unset($vo['contract_remind_id']);
                }
                $reminds[$key] = $vo;
            }

            $customerData = CustomerRepository::getCustomerById($data['customer_id'],['customer_manager']);
            if($customerData->customer_manager){
                /*关联日程提醒*/
                app($this->repository)->addCalendar($customerData->customer_manager,$reminds,$result,$data);
            }
        }

        // 全站搜索消息队列更新数据
        $this->updateGlobalSearchDataByQueue($result);

        return $result;
    }

    /**
     * @param $num        科学计数法字符串  如 2.1E-5
     * @param int $double 小数点保留位数 默认5位
     * @return string
     */
    private  function sctonum($num, $double = 5){
        if(false !== stripos($num, "e")){
            $a = explode("e",strtolower($num));
            return bcmul($a[0], bcpow(10, $a[1], $double), $double);
        }
        return $num;
    }

    // 外发创建合同
    public function flowStore(array $params)
    {
        $data   = $params['data'] ?? [];
        $own = own() ? own() : [];
//        $user_id = $own ? $own['user_id'] : $data['current_user_id'];
        $current_user_id = (isset($params['current_user_id']) && $params['current_user_id']) ? $params['current_user_id'] : $own['user_id'];
        $userId = (isset($data['contract_creator']) && $data['contract_creator']) ? $data['contract_creator'] : $current_user_id;
        if(empty($data['customer_id']) || !isset($data['customer_id'])){
            return ['code' => ['0x024026', 'customer']];
        }

        $info = app($this->userRepository)->getUserAllData($userId)->toArray();
        if($info){
            $role_ids = [];
            foreach ($info['user_has_many_role'] as $key => $vo) {
                $role_ids[] = $vo['role_id'];
            }
            $own = [
                'user_id' => $userId,
                'dept_id' => $info['user_has_one_system_info']['dept_id'],
                'role_id' => $role_ids,
            ];
        }
        $validate = CustomerRepository::validatePermission([CustomerRepository::VIEW_MARK], [$data['customer_id']], $own);
        if(isset($validate['code'])){
            return $validate;
        }
        if(!$validate){
            return ['code' => ['0x024003', 'customer']];
        }
        $data['contract_creator'] = $userId;
        $result = $this->store($data, $own);
        if(isset($result['code'])){
            return $result;
        }
        return [
            'status' => 1,
            'dataForLog' => [
                [
                    'table_to' => 'customer_contract',
                    'field_to' => 'contract_id',
                    'id_to' => $result
                ]
            ]
        ];

//        $userId = $params['contract_creator'] ?? ($params['current_user_id'] ?? 0);
//        unset($params['current_user_id']);
//        $params['attachments'] = $params['attachment_ids'] ?? [];
//        if (isset($params['attachment_ids'])) {
//            unset($params['attachment_ids']);
//        }
//        if (isset($params['remindDate'])) {
//            $params['contract_remind'] = $params['remindDate'];
//            unset($params['remindDate']);
//        }
//        $own    = own();
//        return $this->store($params, $own);
    }

    public function delete(array $ids, array $own)
    {
        $customerIds = ContractRepository::getCustomerIds($ids);
        if (!$validate = CustomerRepository::validatePermission([CustomerRepository::DELETE_MARK], $customerIds, $own)) {
            return ['code' => ['0x024003', 'customer']];
        }
        // 删除日程
        if($ids){
            foreach ($ids as $id){
                if($customer_id = ContractRepository::getCustomerIds([$id])){
                    $customerData = CustomerRepository::getCustomerById($customer_id[0],['customer_manager']);
                    // 编辑收付款，先删除同步日程内的关联数据
                    if($customerData->customer_manager){
                        app($this->calendarService)->emitDelete([
                            'source_id'        => $id,
                            'source_from'      => 'customer-payable'
                        ], $customerData->customer_manager);
                    }
                };
            }
        }

        app($this->repository)->deleteById($ids);
        // 全站搜索消息队列更新数据
        $this->updateGlobalSearchDataByQueue($ids);

        return true;
    }

    public function update(int $id, array $data, array $own)
    {
        if (!$validate = ContractRepository::validatePermission([CustomerRepository::UPDATE_MARK], $id, $own)) {
            return ['code' => ['0x024003', 'customer']];
        }
        $userId   = $own['user_id'] ?? '';
        $validate = ContractRepository::validateInput($data, $userId, true, $own);
        if (isset($validate['code'])) {
            return $validate;
        }
        // 收付款提醒
        $reminds = $data['remind'] ?? [];
        /*
        if (isset($data['remind'])) {
            $reminds = $data['remind'];
            unset($data['remind']);
        }
        */
        // 验证合同金额字段长度
        if(isset($data['contract_amount']) && $data['contract_amount']){
            if($length = $this->sctonum($data['contract_amount'],2)){
                if($nums = explode('.',$length)){
                    if(strlen($nums[0]) > 18){
                        return ['code' => ['error_moneys', 'customer']];
                    };
                };
            };
        }
        if (!$result = app($this->formModelingService)->editCustomData($data, self::CUSTOM_TABLE_KEY, $id)) {
            return ['code' => ['0x024004', 'customer']];
        }
        if(isset($result['code'])){
            return $result;
        }
        if (isset($data['contract_remind']) && !empty($data['contract_remind'])) {
            app($this->repository)->sendRemindMessage($data, $id, $userId);
        }

        // 收付款提醒
        if($reminds && is_array($reminds)){
            $customerData = CustomerRepository::getCustomerById($data['customer_id'],['customer_manager']);
            // 编辑收付款，先删除同步日程内的关联数据
            app($this->calendarService)->emitDelete([
                'source_id'        => $id,
                'source_from'      => 'customer-payable'
            ], $customerData->customer_manager);
            // 兼容移动端 contract_remind_id = null
            foreach ($reminds as $key => $vo){
                if(array_key_exists('contract_remind_id',$vo) && is_null($vo['contract_remind_id'])){
                    unset($vo['contract_remind_id']);
                }
                $reminds[$key] = $vo;
            }
            if($customerData->customer_manager){
                /*关联日程提醒*/
                app($this->repository)->addCalendar($customerData->customer_manager,$reminds,$id,$data);
            }
        }else{
            // 编辑时收付款明显被全部删除
            if($customer_id = ContractRepository::getCustomerIds([$id])){
                $customerData = CustomerRepository::getCustomerById($customer_id[0],['customer_manager']);
                // 编辑收付款，先删除同步日程内的关联数据
                if($customerData->customer_manager){
                    app($this->calendarService)->emitDelete([
                        'source_id'        => $id,
                        'source_from'      => 'customer-payable'
                    ], $customerData->customer_manager);
                }
            };
        }

//        app($this->repository)->refreshReminds($reminds, $id, $userId, true);

        // 全站搜索消息队列更新数据
        $this->updateGlobalSearchDataByQueue($id);

        return true;
    }

    // 合同收款提醒
    public function contractRemimds()
    {
        $result = [];
        $lists  = ContractRepository::getTodayRemindlists();
        if ($lists->isEmpty()) {
            return $result;
        }
        foreach ($lists as $index => $item) {
            if (!$item->customer_id || !$customerManager = CustomerRepository::getManagerId($item->customer_id)) {
                continue;
            }
            $result[] = [
                'remindMark'   => self::REMIND_CONTRACT_COLLECTION_MARK,
                'toUser'       => $customerManager,
                'contentParam' => [
                    'remindContent' => $item->remind_content,
                ],
                'dataId'       => $item->contract_id,
                'stateParams'  => ['contract_id' => $item->contract_id],
            ];
        }
        return $result;
    }

    // 合同到期提醒
    public function expireContractReminds()
    {
        $result = [];
        $params = [
            'page'   => false,
            'fields' => ['contract_id', 'contract_name', 'contract_end', 'contract_creator', 'customer_id'],
            'search' => [
                'contract_remind' => [1],
            ],
        ];
        $lists = app($this->repository)->lists($params);
        if (empty($lists)) {
            return $result;
        }
        $remindDate = ContractRepository::getConfigRemindDate();
        $sendDate   = date('Y-m-d', strtotime("+ $remindDate day"));
        foreach ($lists as $index => $item) {
            if (!isset($item['customer']) || empty($item['customer'])) {
                continue;
            }
            if ($item['contract_end'] != $sendDate) {
                continue;
            }
            $toUser   = implode(',', array_filter([$item['customer']['customer_manager'], $item['contract_creator']]));
            $result[] = [
                'remindMark'   => self::REMIND_CONTRACT_EXPIRT_MARK,
                'toUser'       => $toUser,
                'contentParam' => [
                    'customerName'    => $item['customer']['customer_name'],
                    'contractName'    => $item['contract_name'],
                    'contractEndTime' => $item['contract_end'],
                ],
                'stateParams'  => ['contract_id' => $item['contract_id']],
            ];
        }
        return $result;
    }

    /**
     * 流程数据源配置
     */
    public function dataSourceByCustomerSupplierId($data)
    {
        $customer_id = isset($data["customer_id"]) ? intval($data["customer_id"]) : 0;
        $supplier_id = isset($data["supplier_id"]) ? intval($data["supplier_id"]) : 0;
        if (!$customer_id && !$supplier_id) {
            return ["total" => 0, "list" => []];
        }
        $paramE = [
            'fields' => ["contract_id", "contract_name"],
            'search' => [],
            'limit'  => 100,
        ];
        $param = $this->parseParams($data);
        $param += $paramE;
        if ($customer_id) {
            $param['search']['customer_id'] = [$customer_id];
        }
        if ($supplier_id) {
            $param['search'] = array_merge($param['search'], ["supplier_id" => [$supplier_id]]);
        }
        return $this->response(app($this->repository), 'total', 'lists', $param);
    }

    public function customerContractAfterAdd($data){
        // 提醒
        if (isset($data['contract_remind']) && $data['contract_remind']) {
            app($this->repository)->sendRemindMessage($data, $data['id'], own()['user_id']);
        }
        // 收付款提醒
        if (!empty($data['remind'])) {
            app($this->repository)->refreshReminds($data['remind'], $data['id'], own()['user_id']);
        }
    }

    public function customerContractAddBefore($data){
        $current_user_id = isset($data['current_user_id']) ? $data['current_user_id'] : '';
        $userId = (isset($data['contract_creator']) && $data['contract_creator']) ? $data['contract_creator'] : $current_user_id;
        $own = own();
        if($userId != $own['user_id']){
            $result = app($this->userRepository)->getUserAllData($userId)->toArray();
            if($result){
                $role_ids = [];
                foreach ($result['user_has_many_role'] as $key => $vo) {
                    $role_ids[] = $vo['role_id'];
                }
                $own = [
                    'user_id' => $userId,
                    'dept_id' => $result['user_has_one_system_info']['dept_id'],
                    'role_id' => $role_ids,
                ];
            }
        }
        if(!$validate = CustomerRepository::validatePermission([CustomerRepository::VIEW_MARK], [$data['customer_id']], $own)){
            return ['code' => ['0x024003', 'customer']];
        }else{
            return true;
        }
    }

    public function exportContract($param){
        $own = $param['user_info']['user_info'];
        return app($this->formModelingService)->exportFields('customer_contract', $param, $own, trans("customer.customer_contract"));
    }

    /**
     * 使用消息队列更新全站搜索数据
     *
     * @param   string|int  $id
     */
    public function updateGlobalSearchDataByQueue($ids)
    {
        try {
            ElasticsearchProducer::sendGlobalSearchCustomerContractMessage($ids);
        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
        }
    }

    // 客户合同外发更新
    public function flowOutUpdate($data){
        if (empty($data) || empty($data['unique_id'])){
            return ['code' => ['0x024002','customer']];
        }
        $own = own() ? own() : [];
        $updateData = $data['data'] ?? [];
        $user_id = (isset($data['current_user_id']) && $data['current_user_id']) ? $data['current_user_id'] : $own['user_id'];
        unset($updateData['current_user_id']);
        $dataDetail = app($this->formModelingService)->getCustomDataDetail(self::CUSTOM_TABLE_KEY, $data['unique_id']);
        if(!$dataDetail){
            return ['code' => ['0x024011','customer']];
        }
        if(isset($updateData['contract_creator']) && $updateData['contract_creator'] === ''){
            $updateData['contract_creator'] = $dataDetail['contract_creator'];
        }
        if(!isset($updateData['contract_creator'])){
            $updateData['contract_creator'] = $dataDetail['contract_creator'];
        }
        if(isset($updateData['contract_name']) && $updateData['contract_name'] == ''){
            return ['code' => ['0x024025','customer']];
        }
        if(!isset($updateData['contract_name'])){
            $updateData['contract_name'] = $dataDetail['contract_name'];
        }
        if(isset($updateData['customer_id']) && $updateData['customer_id'] == ''){
            return ['code' => ['0x024028','customer']];
        }
        if(!isset($updateData['customer_id'])){
            $updateData['customer_id'] = $dataDetail['customer_id'];
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
        $result = $this->update($data['unique_id'], $updateData, $own);
        if(isset($result['code'])){
            return $result;
        }
        return [
            'status' => 1,
            'dataForLog' => [
                [
                    'table_to' => self::CUSTOM_TABLE_KEY,
                    'field_to' => 'contract_id',
                    'id_to'    => $data['unique_id']
                ]
            ]
        ];
    }

    // 联系人外发删除
    public function flowOutDelete($data){
        if (empty($data) || empty($data['unique_id'])){
            return ['code' => ['0x024002','customer']];
        }
        $own = own();
        $contractId = explode(',',$data['unique_id']);
        if($result = $this->delete($contractId,$own)){
            if(isset($result['code'])){
                return $result;
            }
            return [
                'status' => 1,
                'dataForLog' => [
                    [
                        'table_to' => self::CUSTOM_TABLE_KEY,
                        'field_to' => 'contract_id',
                        'id_to'    => $data['unique_id']
                    ]
                ]
            ];
        }else{
            return ['code' => ['0x000003', 'common']];
        }
    }
}
