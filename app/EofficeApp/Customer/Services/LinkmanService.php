<?php

namespace App\EofficeApp\Customer\Services;

use App\EofficeApp\Base\BaseService;
use App\EofficeApp\Customer\Repositories\CustomerRepository;
use App\EofficeApp\Customer\Repositories\LinkmanRepository;
use App\EofficeApp\Customer\Services\CustomerService;
use App\EofficeApp\Elastic\Services\MessageQueue\ElasticsearchProducer;
use Illuminate\Support\Facades\Log;

class LinkmanService extends BaseService
{

    const CUSTOM_TABLE_KEY = 'customer_linkman';
    const TABLE_NAME       = 'customer_linkman';

    // 联系人生日提醒设置唯一标识
    const REMIND_BIRTHDAY_MARK = 'customer-birthday';

    private static $hasDeletePermissionIds = ['admin'];

    // 日志关注的需要普通字段修改
    private static $focusFields = ['linkman_name','birthday','department','position','address','hobby',
        'company_phone_number','home_phone_number','fax_number','mobile_phone_number','email','qq_number','weixin','linkman_remarks'];

    public function __construct()
    {
        $this->repository              = 'App\EofficeApp\Customer\Repositories\LinkmanRepository';
        $this->contactRecordRepository = 'App\EofficeApp\Customer\Repositories\ContactRecordRepository';
        $this->customerService         = 'App\EofficeApp\Customer\Services\CustomerService';
        $this->systemRemindService     = 'App\EofficeApp\System\Remind\Services\SystemRemindService';
        $this->attachmentService       = 'App\EofficeApp\Attachment\Services\AttachmentService';
        $this->userRepository          = 'App\EofficeApp\User\Repositories\UserRepository';
        $this->formModelingService     = 'App\EofficeApp\FormModeling\Services\FormModelingService';
        $this->formModelingRepository  = 'App\EofficeApp\FormModeling\Repositories\FormModelingRepository';
        $this->workWechatService       = 'App\EofficeApp\WorkWechat\Services\WorkWechatService';
        $this->customerRepository      = 'App\EofficeApp\Customer\Repositories\CustomerRepository';
    }

    public function lists($input, $own)
    {
        $result = app($this->formModelingService)->getCustomDataLists($input, self::CUSTOM_TABLE_KEY, $own);
        if (!isset($result['list']) || empty($result['list'])) {
            return $result;
        }
        $ids                       = array_column((array) $result['list'], 'linkman_id');
        list($result['updateIds']) = LinkmanRepository::getPermissionIds([CustomerRepository::UPDATE_MARK], $own, $ids);
        // 客户详情页面联系人关联记录数获取
        if (isset($input['withRecord'])) {
            $result['recordCountLists'] = app($this->contactRecordRepository)->getCountIds($ids);
        }
        return $result;
    }
    public function filterLinkmanLists($params,$own)
    {
        $ids = CustomerRepository::getViewIds($own);
        if ($ids === CustomerRepository::ALL_CUSTOMER) {
//            return [];
            $params = $this->parseCustomOrder($params,'customer_linkman');
            $linkman_ids = LinkmanRepository::getViewsLinkmanIds($params,$own);

            return ['linkman_id' => [$linkman_ids, 'in']];
        }
        if(empty($ids)){
            // 如果为空，则表示该用户下没有能查看到客户，此时返回一个查询为空到ID
            return ['linkman_id' => [[0], 'in']];
        }
        if(isset($params['flag'])){
            $customerId = $params['flag'];
            if(($ids != CustomerRepository::ALL_CUSTOMER) && in_array($customerId,$ids)){
                $linkmanIds = LinkmanRepository::getCustomerDetailLinkMan($customerId,$own);
                return ['linkman_id' => [$linkmanIds, 'in']];
            }
            return [];
        }
        // 获取对应权限下的联系人
        $linkman_ids = LinkmanRepository::getViewsLinkmanIds($params,$own,$ids);
        return ['linkman_id' => [$linkman_ids, 'in']];
    }

    public function parseCustomOrder($param,$tableKey){
        $platform = (isset($param['platform']) && !empty($param['platform'])) ? $param['platform'] : 'pc';
        $listParam = [
            'terminal' => $platform,
            'bind_type' => 1,
        ];
        $listLayout = app($this->formModelingRepository)->getBindTemplate($tableKey, $listParam);
        if (!empty($listLayout) && isset($listLayout->extra)) {
            $extra = json_decode($listLayout->extra, true);
            if (isset($extra['defaultOrder']) && !empty($extra['defaultOrder'])) {
                $param['defaultOrder'] = $extra['defaultOrder'];
            }
        }
        return $param;
    }

    public function show($id, $own)
    {
        if (!$validate = LinkmanRepository::validatePermission([CustomerRepository::VIEW_MARK], $id, $own)) {
            return ['code' => ['0x024003', 'customer']];
        }
        $result                          = app($this->formModelingService)->getCustomDataDetail(self::CUSTOM_TABLE_KEY, $id, $own);
        $attachmentList                  = app($this->attachmentService)->getAttachments(['entity_table' => self::CUSTOM_TABLE_KEY, 'entity_id' => $id]);
        $result = (array) $result;
        $result['thumb_attachment_name'] = '';
        $result['attachment_name']       = '';
        $result['sex_name']              = '';
        if($field_sex = app($this->formModelingService)->listCustomFields(['search'=>['field_code'=>['sex']]],'customer_linkman')){
            if(isset($field_sex[0])){
                $field_options = json_decode($field_sex[0]->field_options,1);
                if($field_options){
                    foreach ($field_options['datasource'] as $key => $vo){
                        if($vo['id'] == $result['sex']){
                            $result['sex_name']  = $vo['title'];
                        }
                    }
                }
            }
        }
        if (!empty($attachmentList) && isset($attachmentList[0])) {
            $result['thumb_attachment_name'] = isset($attachmentList[0]['thumb_attachment_name']) ? $attachmentList[0]['thumb_attachment_name'] : '';
            $result['attachment_name']       = isset($attachmentList[0]['attachment_name']) ? $attachmentList[0]['attachment_name'] : '';
        }

        $result['dept_ids'] = $result['dept_ids'] ? explode(',',$result['dept_ids']) : '';
        $result['role_ids'] = $result['role_ids'] ? explode(',',$result['role_ids']) : '';
        $result['user_ids'] = $result['user_ids'] ? explode(',',$result['user_ids']) : '';

        return $result;
    }

    public function store($data, $own = [])
    {
        $validate = LinkmanRepository::validateInput($data, $own);
        if (isset($validate['code'])) {
            return $validate;
        }
        // 部门,角色,人员存储形式转换
        self::handleJurisdict($data);
        if (!$id = app($this->formModelingService)->addCustomData($data, self::CUSTOM_TABLE_KEY)) {
            return ['code' => ['0x024004', 'common']];
        }
        if(isset($id['code'])){
            return $id;
        }

        // 全站搜索消息队列更新数据
        $this->updateGlobalSearchDataByQueue($id);

        return $id;
    }

    public static  function handleJurisdict(&$data){
        if(!isset($data['is_all'])){
            $data['is_all'] = 1;
        }

        if(isset($data['outsource'])){
            if(!isset($data['is_all'])){
                $data['is_all'] = 1;
            }
            unset($data['outsource']);
        }
        if($data['is_all'] == 1){
            $data['user_ids'] = '';
            $data['role_ids'] = '';
            $data['dept_ids'] = '';
        }
        if(isset($data['user_ids']) && $data['user_ids'] && is_array($data['user_ids'])){
            $data['user_ids'] = implode(',',$data['user_ids']);
        }
        if(isset($data['role_ids']) && $data['role_ids'] && is_array($data['role_ids'])){
            $data['role_ids'] = implode(',',$data['role_ids']);
        }
        if(isset($data['dept_ids']) && $data['dept_ids'] && is_array($data['dept_ids'])){
            $data['dept_ids'] = implode(',',$data['dept_ids']);
        }

        if($data['is_all'] == 0 ){
            $data['is_all'] = '';
        }

    }

    // 外发创建联系人
    public function flowStore(array $params)
    {
        $data   = $params['data'] ?? [];
//        $userId = $data['linkman_creator'] ?? ($params['current_user_id'] ?? 0);
        if (isset($data['main_linkman']) && $data['main_linkman'] !== '') {
            $data['main_linkman'] = 1;
        }
        $own    = own() ? own() : [];

        $user_id = (isset($data['linkman_creator']) && $data['linkman_creator']) ? $data['linkman_creator'] :  $params['current_user_id'];
        if(!$user_id){
            $user_id =  $params['current_user_id'];
        }
        $data['linkman_creator'] = $user_id;
        if(empty($data['customer_id'])){
            return ['code' => ['0x024028','customer']];
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
        if(!$validate = CustomerRepository::validatePermission([CustomerRepository::VIEW_MARK], [$data['customer_id']], $own)){
            return ['code' => ['0x024003', 'customer']];
        }else{
            $result = $this->store($data, $own);
            if (isset($result['code'])) {
                return $result;
            }
            return [
                'status' => 1,
                'dataForLog' => [
                    [
                        'table_to' => 'customer_linkman',
                        'field_to' => 'linkman_id',
                        'id_to' => $result
                    ]
                ]
            ];
        }

    }

    public function update(int $id, $data, $own)
    {
        $originData = app($this->repository)->getDetail($id);
        if(!$originData){
            return ['code' => ['0x024011','customer']];
        }
        $originData = $originData->toArray();
        $validate = LinkmanRepository::validateInput($data, $own);
        if (isset($validate['code'])) {
            return $validate;
        }

        if (!$validate = LinkmanRepository::validatePermission([CustomerRepository::UPDATE_MARK], $id, $own)) {
            return ['code' => ['0x024003', 'customer']];
        }
        // 部门,角色,人员存储形式转换
        self::handleJurisdict($data);
        if (!$result = app($this->formModelingService)->editCustomData($data, self::CUSTOM_TABLE_KEY, $id)) {
            return $result;
        }
        if(isset($result['code'])){
            return $result;
        }

        $this->saveUpdateLogs($originData, $data, $own['user_id']);
        // 全站搜索消息队列更新数据
        $this->updateGlobalSearchDataByQueue($id);

        return true;
    }

    public function delete(array $ids, $own)
    {
        if (!$validate = CustomerRepository::validatePermission([CustomerRepository::DELETE_MARK], [], $own)) {
            return ['code' => ['0x024003', 'customer']];
        }

        $result = LinkmanRepository::getLists($ids,['linkman_id','linkman_name','customer_id']);
        if(app($this->repository)->deleteById($ids)){
            foreach ($result as $item){
                $logContent = trans('customer.delete_linkman').'：'.$item->linkman_name.'，'.trans('customer.belong_to_customer').'：';
                if($item->customer_id){
                    $customerData = CustomerRepository::getCustomerById($item->customer_id,['customer_name']);
                    $logContent .= $customerData->customer_name;
                }
                $identify = 'customer.customer_linkman.delete';
                LinkmanRepository::saveLogs($item->linkman_id, $logContent, $own['user_id'],$identify,$item->linkman_name);
            }
	    // 全站搜索消息队列更新数据
            $this->updateGlobalSearchDataByQueue($ids);
            return true;
        };
        return false;
    }

    public function customerLinkmans(int $customerId)
    {
        return LinkmanRepository::customerLinkmans($customerId);
    }

    // 获取需要导入的字段
    public function getImportFields($param)
    {
        return app($this->formModelingService)->getImportFields(self::CUSTOM_TABLE_KEY, $param, trans("customer.customer_contacts_import_template"));
    }

    // 导入联系人
    public function importLinkman($data, $params)
    {
        app($this->formModelingService)->importCustomData(self::CUSTOM_TABLE_KEY, $data, $params);
        return ['data' => $data];
    }

    // 导入联系人过滤
    public function importFilter($importDatas, $params = [])
    {
        $own         = $params['user_info'] ?? [];
        $tempCustomerData = [];
        $customerIds = CustomerRepository::getViewIds($own);
        $model = app($this->formModelingService);
        foreach ($importDatas as $index => $data) {
            if (is_array($customerIds)) {
                if (isset($data['customer_id']) && !in_array($data['customer_id'], $customerIds)) {
                    $importDatas[$index]['importResult'] = importDataFail();
                    $importDatas[$index]['importReason'] = importDataFail(trans("customer.customer_no_permission"));
                    continue;
                }
            }

            $validate = LinkmanRepository::validateInput($data, $own);
            if(isset($validate['code'])){
                $notify = $validate['code'][1].'.'.$validate['code'][0];
                $importDatas[$index]['importResult'] = importDataFail();
                $importDatas[$index]['importReason'] = importDataFail(trans($notify));
                continue;
            }
            $importDatas[$index]['importResult'] = importDataFail();
            // 自定义字段验证
            $result = $model->importDataFilter(self::CUSTOM_TABLE_KEY, $data, $params);
            if (!empty($result)) {
                $importDatas[$index]['importResult'] = importDataFail();
                $importDatas[$index]['importReason'] = importDataFail($result);
                continue;
            }
            if($tempCustomerData){
                // 判断excel里相同客户下有相同手机号
                if(in_array($data['customer_id'].','.$data['mobile_phone_number'],$tempCustomerData)){
                    $importDatas[$index]['importResult'] = importDataFail();
                    $importDatas[$index]['importReason'] = importDataFail(trans("customer.repeat_phone"));
                    continue;
                }
            }
            if(isset($data['is_all']) && $data['is_all'] == 1){
                $importDatas[$index]['dept_ids'] = '';
                $importDatas[$index]['role_ids'] = '';
                $importDatas[$index]['user_ids'] = '';
            }

            $importDatas[$index]['linkman_creator'] = (isset($data['linkman_creator']) && $data['linkman_creator']) ? $data['linkman_creator'] : $own['user_id'];
            // 搜集导入excel里的客户id跟联系人手机号(当手机号存在的时候才收集)
            if($data['mobile_phone_number']){
                $tempCustomerData[] = $data['customer_id'].','.$data['mobile_phone_number'];
            }
            $importDatas[$index]['importResult'] = importDataSuccess();
        }
        return $importDatas;
    }

    // 导入联系人之后操作
    public function afterImport($importData)
    {
        $updateData  = [];
        $linkmanData = $importData['data'] ?? [];
        $isMain      = $linkmanData['main_linkman'] ?? 0;
        $customerId  = $linkmanData['customer_id'] ?? 0;
        $linkmanId   = $importData['id'] ?? 0;
        $userId      = isset($linkmanData['linkman_creator']) && !empty($linkmanData['linkman_creator']) ? $linkmanData['linkman_creator'] : ($importData['param']['user_info']['user_id'] ?? '');
        if (!$customerId || !$linkmanId) {
            return true;
        }
        $updateData                    = [];
        $updateData['linkman_creator'] = $userId;
        $updateData['created_at']      = date('Y-m-d H:i:s');
        if (isset($linkmanData['linkman_name'])) {
            list($updateData['linkman_name_py'], $updateData['linkman_name_zm']) = convert_pinyin($linkmanData['linkman_name']);
        }
        if ($isMain) {
            LinkmanRepository::setMainLinkman($customerId, $linkmanId);
        }
        // 写入日志
        $customerName = '';
        if($linkmanData['customer_id']){
            $customerData = CustomerRepository::getCustomerById($linkmanData['customer_id'],['customer_name','customer_id']);
            $customerName = $customerData->customer_name;
        }
        $logContent = trans('customer.import_linkman').'：'.$linkmanData['linkman_name'].'，'.trans('customer.belong_to_customer').'：'.$customerName;
        $identify = 'customer.customer_linkman.import';
        LinkmanRepository::saveLogs($linkmanId, $logContent, $userId,$identify,$linkmanData['linkman_name']);
        return LinkmanRepository::updateLinkman($linkmanId, $updateData);
    }

    // 定时任务，客户生日提醒
    public function customerBirthdayReminds()
    {
        $result = [];
        if (!$validate = app($this->systemRemindService)->checkRemindIsOpen(self::REMIND_BIRTHDAY_MARK)) {
            return $result;
        }
        $lists = app($this->repository)->customerBirthdayReminds();
        if ($lists->isEmpty()) {
            return $result;
        }
        foreach ($lists as $index => $item) {
            $linkType = $item->mobile_phone_number ?? $item['company_phone_number'];
            $result[] = [
                'remindMark'   => self::REMIND_BIRTHDAY_MARK,
                'toUser'       => $item->linkmanCustomer['customer_manager'],
                'contentParam' => [
                    'linkman'      => $item->linkman_name . ' ' . $linkType,
                    'customerName' => $item->linkmanCustomer['customer_name'],
                ],
                'stateParams'  => ['customer_id' => $item->linkmanCustomer['customer_id']],
            ];
        }
        return $result;
    }

    /**
     * 流程数据源配置
     */
    public function dataSourceByCustomerId($data, $own)
    {
        $customer_id = isset($data["customer_id"]) ? $data["customer_id"] : "";
        if (empty($customer_id)) {
            return ["total" => 0, "list" => []];
        }
        $data = $this->parseParams($data);
        $params = [
            'fields' => ["linkman_id", "linkman_name"],
            'search' => ["customer_id" => [$customer_id]],
            'limit'  => 100,
            'flag'   => $customer_id
        ];
        isset($data['search']) && $params['search'] = array_merge($params['search'],$data['search']);
        return $this->lists($params, $own);
    }

    public function exportLinkman($param){
        $own = $param['user_info']['user_info'];
        return app($this->formModelingService)->exportFields('customer_linkman', $param, $own, trans('customer.customer_linkman_manager'));
    }


    /**
     * 使用消息队列更新全站搜索数据
     *
     * @param   string|int  $id
     */
    public function updateGlobalSearchDataByQueue($ids)
    {
        try {
            ElasticsearchProducer::sendGlobalSearchCustomerLinkmanMessage($ids);
        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
        }
    }

    // 联系人外发更新
    public function flowOutUpdate($data){
        if (empty($data) || empty($data['unique_id'])){
            return ['code' => ['0x024002','customer']];
        }
        $own = own();
        $updateData = $data['data'] ?? [];
        $result = $this->update($data['unique_id'], $updateData, $own);
        if(isset($result['code'])){
            return $result;
        }
        return [
            'status' => 1,
            'dataForLog' => [
                [
                    'table_to' => self::CUSTOM_TABLE_KEY,
                    'field_to' => 'linkman_id',
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
        $linkmanId = explode(',',$data['unique_id']);
        if($this->delete($linkmanId,$own)){
            return [
                'status' => 1,
                'dataForLog' => [
                    [
                        'table_to' => self::CUSTOM_TABLE_KEY,
                        'field_to' => 'linkman_id',
                        'id_to'    => $data['unique_id']
                    ]
                ]
            ];
        }else{
            return ['code' => ['0x000003', 'common']];
        }
    }

    // 客户联系人查看加入日志
    public function linkmanAfterDetail($data){
        $customerName = '';
        if($data['customer_id']){
            $customerData = CustomerRepository::getCustomerById($data['customer_id'],['customer_name','customer_id']);
            $customerName = $customerData->customer_name;
        }
        $logContent = trans('customer.view_linkman').'：'.$data['linkman_name'].'，'.trans('customer.belong_to_customer').'：'.$customerName;

        $identify = 'customer.customer_linkman.view';
        LinkmanRepository::saveLogs($data['linkman_id'], $logContent, own()['user_id'],$identify,$data['linkman_name']);
    }

    // 客户联系人新建加入日志
    public function linkmanAfterAdd($LinkmanData){
        $customerName = '';
        if($LinkmanData['customer_id']){
            $customerData = CustomerRepository::getCustomerById($LinkmanData['customer_id'],['customer_name','customer_id']);
            $customerName = $customerData->customer_name;
        }
        $user_id = (isset($LinkmanData['linkman_creator']) && $LinkmanData['linkman_creator']) ? $LinkmanData['linkman_creator'] : own()['user_id'];
        $logContent = trans('customer.created_linkman').'：'.$LinkmanData['linkman_name'].'，'.trans('customer.belong_to_customer').'：'.$customerName;
	    $identify = 'customer.customer_linkman.add';
        LinkmanRepository::saveLogs($LinkmanData['id'], $logContent, $user_id,$identify,$LinkmanData['linkman_name']);
    }

    public function saveUpdateLogs(array $originData, array $inputData, $userId){
        $linkmanId = $originData['linkman_id'] ?? '';
        if (!$linkmanId) {
            return true;
        }
        if(isset($originData['birthday']) && $originData['birthday'] == '0000-00-00'){
            $originData['birthday'] = '';
        }
        $words = trans('customer.changed') . "： ".$originData['linkman_name']. " ";
        // 字段修改
        foreach (self::$focusFields as $field) {
            if(isset($inputData[$field])){
                if ($originData[$field] != $inputData[$field]) {
                    $words .= $this->getCustomerChangedLog($field, $originData[$field], $inputData[$field]) . ', ';
                }
            }

        }
        // 所属客户修改
        if(isset($inputData['customer_id']) && $inputData['customer_id'] != $originData['customer_id']){
            $oName = $nName = '';
            if($originData['customer_id']){
                $customerData = CustomerRepository::getCustomerById($originData['customer_id'],['customer_name','customer_id']);
                $oName = $customerData->customer_name;
            }
            if($inputData['customer_id']){
                $customerData = CustomerRepository::getCustomerById($inputData['customer_id'],['customer_name','customer_id']);
                $nName = $customerData->customer_name;
            }
            $words .= $this->getCustomerChangedLog('customer_id', $oName, $nName) . ', ';
        }

        // 主联系人修改(复选框)
        if(isset($inputData['main_linkman']) && $inputData['main_linkman'] != $originData['main_linkman']){
            $params['search'] = ['field_code' => ['main_linkman', '=']];
            $fieldLists = app($this->formModelingService)->listCustomFields($params, self::TABLE_NAME);
            $field_options = array_column($fieldLists, 'field_options');
            $field_name = array_column($fieldLists, 'field_name');
            $langName = $field_name[0] ?? '';
            $fieldOptions = json_decode($field_options[0],1);
            $oMani = $nMani = '';
            if(isset($fieldOptions['datasource']) && $fieldOptions['datasource']){
                foreach ($fieldOptions['datasource'] as $key => $vo){
                    if($vo['id'] == $originData['main_linkman']){
                        $oMani = $vo['title'];
                    }
                    if($vo['id'] == $inputData['main_linkman']){
                        $nMani = $vo['title'];
                    }
                }
            }
            $words .= $this->getCustomerChangedLog('sex', $oMani, $nMani,$langName) . ', ';
        }
        // 性别修改（单选框）
        if(isset($inputData['sex']) && $inputData['sex'] != $originData['sex']){
            $params['search'] = ['field_code' => ['sex', '=']];
            $fieldLists = app($this->formModelingService)->listCustomFields($params, self::TABLE_NAME);
            $field_options = array_column($fieldLists, 'field_options');
            $field_name = array_column($fieldLists, 'field_name');
            $langName = $field_name[0] ?? '';
            $fieldOptions = json_decode($field_options[0],1);
            $oSexName = $nSexName = '';
            if(isset($fieldOptions['datasource']) && $fieldOptions['datasource']){
                foreach ($fieldOptions['datasource'] as $key => $vo){
                    if($vo['id'] == $originData['sex']){
                        $oSexName = $vo['title'];
                    }
                    if($vo['id'] == $inputData['sex']){
                        $nSexName = $vo['title'];
                    }
                }
            }
            $words .= $this->getCustomerChangedLog('sex', $oSexName, $nSexName,$langName) . ', ';
        }

        $words = rtrim($words, ', ');
        $identify = 'customer.customer_linkman.edit';
        return LinkmanRepository::saveLogs($linkmanId, $words, $userId,$identify,$originData['linkman_name']);
    }


    public function getCustomerChangedLog($field, $from, $changedTo,$langName = null){
        $from = $from ?: trans('customer.empty');
        $changedTo = $changedTo ?: trans('customer.empty');
//        if (in_array($field, ['linkman_remarks'])) {
//            $from = $from ? strip_tags($from): trans('customer.empty');
//            $changedTo = $changedTo ? strip_tags($changedTo): trans('customer.empty');
//        }
        if(!$langName){
            $params['search'] = ['field_code' => [[$field], 'in']];
            $fieldLists = app($this->formModelingService)->listCustomFields($params, self::TABLE_NAME);
            $langName = "";
            if ($fieldLists) {
                $fields = array_column($fieldLists, 'field_name');
                $langName = $fields[0] ?? '';
            }
        }
        return $langName . " " . $from . "->" . $changedTo;
    }

    public function bindingLinkman($input, $id,$own){
        if(empty($input['external_contact_user_id']) || !$id){
            return ['code' => ['0x000001','common']];
        }
        // 检测是否已绑定
        $input['linkman_id'] = $input['customer_linkman_id'];
        unset($input['customer_linkman_id']);
        $result = LinkmanRepository::checkBinding($input);
        if($result && $result->external_contact_user_id){
            return ['code' => ['already_binding','customer']];
        }
        return LinkmanRepository::bindData($input);
    }

    public function cancelBinding($id,$own){
        if(!$id){
            return ['code' => ['0x000001','common']];
        }
        LinkmanRepository::cancelBinding(['linkman_id'=>$id]);
    }

    public function bindingList($input,$own){

//        $result = '[[{"external_contact":{"external_userid":"woAJ2GCAAAXtWyujaWJHDDGi0mACHAAA","name":"\u674e\u56db"},"follow_info":{"remark":"\u674e\u56db"}}],[{"external_contact":{"external_userid":"woAJ2GCAAAXtWyujaWJHDDGi0mACHBBB","name":"\u738b\u4e94\u95ee\u6211"},"follow_info":{"remark":"\u738b\u4e94\u95ee\u6211"}}],[{"external_contact":{"external_userid":"woAJ2GCAAAXtWyujaWJHDDGi0mACHccc","name":"\u54c8\u54c8"},"follow_info":{"remark":"\u674e\u54c8\u54c8\u56db"}}],[{"external_contact":{"external_userid":"woAJ2GCAAAXtWyujaWJHDDGi0mACHDDD","name":"\u563f\u563f"},"follow_info":{"remark":"\u674e\u563f\u563f\u56db"}}],[{"external_contact":{"external_userid":"woAJ2GCAAAXtWyujaWJHDDGi0mACHfff","name":"\u5f20\u4e09"},"follow_info":{"remark":"\u674e\u5f20\u4e09\u56db"}}],[{"external_contact":{"external_userid":"woAJ2GCAAAXtWyujaWJHDDGi0mACHyyy","name":"\u6731\u516d"},"follow_info":{"remark":"\u674e\u56db\u6731\u516d"}}],[{"external_contact":{"external_userid":"woAJ2GCAAAXtWyujaWJHDDGi0mACHttt","name":"\u5f20\u4e09"},"follow_info":{"remark":"\u674e\u56db\u5f20\u4e09"}}],[{"external_contact":{"external_userid":"woAJ2GCAAAXtWyujaWJHDDGi0mACHDDD","name":"\u6731\u516d"},"follow_info":{"remark":"\u674e\u6731\u516d\u56db"}}],[{"external_contact":{"external_userid":"woAJ2GCAAAXtWyujaWJHDDGi0mACHccc","name":"\u5f20\u4e09"},"follow_info":{"remark":"\u5f20\u4e09"}}],[{"external_contact":{"external_userid":"woAJ2GCAAAXtWyujaWJHDDGi0mACHDDD","name":"\u6731\u516d"},"follow_info":{"remark":"\u6731\u516d"}}],[{"external_contact":{"external_userid":"woAJ2GCAAAXtWyujaWJHDDGi0mACHccc","name":"\u5f20\u4e09"},"follow_info":{"remark":"\u5f20\u4e09"}}],[{"external_contact":{"external_userid":"woAJ2GCAAAXtWyujaWJHDDGi0mACHDDD","name":"\u6731\u516d"},"follow_info":{"remark":"\u6731\u516d"}}],[{"external_contact":{"external_userid":"woAJ2GCAAAXtWyujaWJHDDGi0mACHccc","name":"\u5f20\u4e09"},"follow_info":{"remark":"\u5f20\u4e09"}}],[{"external_contact":{"external_userid":"woAJ2GCAAAXtWyujaWJHDDGi0mACHDDD","name":"\u6731\u516d"},"follow_info":{"remark":"\u674e\u6731\u516d\u56db"}}]]';
//        $result = json_decode($result,1);

        $result = app($this->workWechatService)->getWorkWeChatExternalContactList($input,$own);
        if(isset($result['code']) || empty($result)){
            return $result;
        }

        if($input['search']){
            $input['search'] = json_decode($input['search'],1);
            if($input['search']){
                $customer_id = [];
                if(isset($input['search']['customer_name'])){
                    $customer_id = app($this->customerRepository)->getCustomerIdByName(['customer_name'=>[$input['search']['customer_name'],'like']]);
                }
                $external_id = LinkmanRepository::getList($input['search'],$customer_id);
                if(!$external_id){
                    return [];
                }
                foreach ($result as $key => $item){
                    if(!in_array($item['external_contact']['external_userid'],$external_id)){
                        unset($result[$key]);
                    }
                }
                sort($result);
            }
        }

        $chunkData = array_chunk($result,$input['limit'])[$input['page'] -1];

        $external_userid = $relationdata = [];
        // 处理数据格式
        array_map(function ($vo) use (&$external_userid){
            $external_userid[] = $vo['external_contact']['external_userid'];
        },$chunkData);
        $data = LinkmanRepository::getRelationData($external_userid);
        if($data){
            foreach ($data as $key => $value){
                $relationdata[$value->external_contact_user_id] = $value;
            }
        }
        $returnData = [];
        if($result && !isset($result['code'])){
            foreach ($chunkData as $key => $vo){
//                $res = $this->getBindData($vo);
                $returnData[$key]['external_userid'] = $vo['external_contact']['external_userid'];
                $returnData[$key]['name'] = $vo['follow_info']['remark']  ? $vo['follow_info']['remark']  : $vo['external_contact']['name'];
                if(isset($relationdata[$vo['external_contact']['external_userid']])){
                    $returnData[$key]['customer_id'] = $relationdata[$vo['external_contact']['external_userid']]->customer_id;
                    $returnData[$key]['linkman_id'] = $relationdata[$vo['external_contact']['external_userid']]->linkman_id;
                    $returnData[$key]['mobile_phone_number'] = $relationdata[$vo['external_contact']['external_userid']]->mobile_phone_number;
                    $returnData[$key]['is_binged'] = 1;
                }else{
                    $returnData[$key]['customer_id'] = '';
                    $returnData[$key]['linkman_id'] = '';
                    $returnData[$key]['mobile_phone_number'] = '';
                    $returnData[$key]['is_binged'] = 0;
                }

            }
        }
        return ['list'=>$returnData,'total'=>count($result)];
    }
}
