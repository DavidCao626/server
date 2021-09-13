<?php
namespace App\EofficeApp\Customer\Services;

use App\EofficeApp\Base\BaseService;
use App\EofficeApp\Customer\Repositories\BusinessChanceRepository;
use App\EofficeApp\Customer\Repositories\ContactRecordRepository;
use App\EofficeApp\Customer\Repositories\ContractRepository;
use App\EofficeApp\Customer\Repositories\CustomerRepository;
use App\EofficeApp\Customer\Repositories\LinkmanRepository;
use App\EofficeApp\Customer\Repositories\PermissionRepository;
use App\EofficeApp\Customer\Repositories\SaleRecordRepository;
use App\EofficeApp\Customer\Repositories\SeasGroupRepository;
use App\EofficeApp\Customer\Repositories\ShareCustomerRepository;
use App\EofficeApp\Customer\Repositories\VisitRepository;
use App\EofficeApp\Elastic\Services\MessageQueue\ElasticsearchProducer;
use App\EofficeApp\User\Repositories\UserRepository;
use App\EofficeApp\FormModeling\Repositories\FormModelingRepository;
use DB;
use Eoffice;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Lang;
use App\EofficeApp\LogCenter\Facades\LogCenter;
use Illuminate\Support\Arr;
class CustomerService extends BaseService
{

    const CUSTOM_TABLE_KEY = 'customer';

    // 客户菜单文件名
    const MENUS_FILE = 'menus.txt';

    // 展示全部客户数据
    const LIST_TYPE_LISTS = 'lists';
    // 展示全部客户数据
    const LIST_TYPE_ALL = 'all';
    // 展示具有编辑权限的数据
    const LIST_TYPE_UPDATE = 'update';
    // 展示软删除数据
    const LIST_TYPE_RECYCLE = 'recycle';

    // 客户公海用户开放捡起权限
    const SEAS_GROUP_USER_INITIATIVE = 2;
    // 客户公海用户自动分配权限
    const SEAS_GROUP_AUTO_DISTRIBUTE = 1;

    // 申请权限
    const APPLY_SHARE           = 1;
    const APPLY_SERVICE_MANAGER = 2;
    const APPLY_MANAGER         = 3;

    // 客户公海分配与未分配的客户redis key
    const SEAS_DISTRIBUTE_OVER = 'customer:seas_distribute_over';
    const SEAS_DISTRIBUTE_NEW  = 'customer:seas_distribute_new';
    const SEAS_DISTRIBUTE_WAIT = 'customer:seas_distribute_wait';

    // 权限审批批准还是拒绝
    const APPLY_STATUS_ORIGIN  = 1;
    const APPLY_STATUS_APPROVE = 2;
    const APPLY_STATUS_REFUSE  = 3;

    // 表示对整个客户表都具有查看权限
    const ALL_CUSTOMER_MARK = -1;

    // 来源网站标识值
    const FROM_WEBSITE = 2;

    // 系统设置提醒客户提醒
    const REMIND_CUSTOMER_VISIT_MARK = 'customer-visit';
    const APPLY_PERMISSION_MARK      = 'customer-apply';

    // 删除权限用户
    private static $hasDeletePermissionIds = ['admin'];

    public function __construct()
    {
        $this->repository                    = 'App\EofficeApp\Customer\Repositories\CustomerRepository';
        $this->attentionRepository           = 'App\EofficeApp\Customer\Repositories\AttentionRepository';
        $this->linkmanRepository             = 'App\EofficeApp\Customer\Repositories\LinkmanRepository';
        $this->contractRepository            = 'App\EofficeApp\Customer\Repositories\ContractRepository';
        $this->visitRepository               = 'App\EofficeApp\Customer\Repositories\VisitRepository';
        $this->saleRecordRepository          = 'App\EofficeApp\Customer\Repositories\SaleRecordRepository';
        $this->shareCustomerRepository       = 'App\EofficeApp\Customer\Repositories\ShareCustomerRepository';
        $this->contactRecordRepository       = 'App\EofficeApp\Customer\Repositories\ContactRecordRepository';
        $this->businessChanceRepository      = 'App\EofficeApp\Customer\Repositories\BusinessChanceRepository';
        $this->logService                    = 'App\EofficeApp\System\Log\Services\LogService';
        $this->attachmentService             = 'App\EofficeApp\Attachment\Services\AttachmentService';
        $this->permissionRepository          = 'App\EofficeApp\Customer\Repositories\PermissionRepository';
        $this->seasGroupRepository           = 'App\EofficeApp\Customer\Repositories\SeasGroupRepository';
        $this->permissionGroupRepository     = 'App\EofficeApp\Customer\Repositories\PermissionGroupRepository';
        $this->permissionGroupRoleRepository = 'App\EofficeApp\Customer\Repositories\PermissionGroupRoleRepository';
        $this->userRepository                = 'App\EofficeApp\User\Repositories\UserRepository';
        $this->userService                   = 'App\EofficeApp\User\Services\UserService';
        $this->systemRemindService           = 'App\EofficeApp\System\Remind\Services\SystemRemindService';
        $this->systemComboboxService         = 'App\EofficeApp\System\Combobox\Services\SystemComboboxService';
        $this->formModelingService           = 'App\EofficeApp\FormModeling\Services\FormModelingService';
        $this->calendarService               = 'App\EofficeApp\Calendar\Services\CalendarService';
        $this->calendarRepository		     = 'App\EofficeApp\Calendar\Repositories\CalendarRepository';
        $this->workWechatService             = 'App\EofficeApp\WorkWechat\Services\WorkWechatService';
        $this->labelRepository               = 'App\EofficeApp\Customer\Repositories\LabelRepository';
        $this->labelRelationRepository       = 'App\EofficeApp\Customer\Repositories\LabelRelationRepository';
        $this->logRecordService                    = 'App\EofficeApp\LogCenter\Services\LogRecordsService';
    }

    // 客户信息列表
    public function lists($input, $own, $type = 'lists')
    {
        $result = app($this->formModelingService)->getCustomDataLists($input, self::CUSTOM_TABLE_KEY, $own);
        if (!isset($result['list']) || empty($result['list'])) {
            return $result;
        }
        $ids     = [];
        $nowTime = time();
        $isShow = 1;
        if($res = $menuLists = FormModelingRepository::getCustomTabMenus(self::CUSTOM_TABLE_KEY, function() {
            return CustomerRepository::getCustomerTabMenus();
        }, '')){
            foreach ($res as $vo){
                if(isset($vo['key']) && $vo['key'] == 'customer_share'){
                    $isShow = $vo['isShow'] ? 1 : 0;
                }
            }

        }
        foreach ($result['list'] as $key => $item) {
            $ids[] = $item->customer_id;
//            if (isset($item->last_contact_time)) {
//                $secondTime = strtotime($item->last_contact_time);
//                if ($secondTime <= 0) {
//                    $secondTime = isset($item->created_at) ? strtotime($item->created_at) : 0;
//                }
//                $item->last_contact_time = $this->parseTimeDate($nowTime - $secondTime);
//            }
            if(isset($item->customer_logo)){
                $item->customer_logo = $item->customer_logo ? $item->customer_logo : '';
            }
            $result['list'][$key] = $item;
            $result['list'][$key]->isShow = $isShow;
        }
        switch ($type) {
            case self::LIST_TYPE_LISTS:
                list($result['viewIds'], $result['updateIds'], $result['attendIds'], $result['visitIds']) = CustomerRepository::getPermissionIds([CustomerRepository::VIEW_MARK, CustomerRepository::UPDATE_MARK, CustomerRepository::ATTEND_MARK, CustomerRepository::VISIT_MARK], $own, $ids,$input);
                foreach ($result['list'] as $index => $item) {
                    if (in_array($item->customer_id, $result['updateIds'])) {
                        $item->hasUpdatePermission = true;
                    }
                }
                break;
            case self::LIST_TYPE_ALL:
                list($result['viewIds'])                                                               = CustomerRepository::getPermissionIds([CustomerRepository::VIEW_MARK], $own, $ids,$input);
                $userId                                                                                = $own['user_id'] ?? '';
                list($result['applyShareIds'], $result['applyManagerIds'], $result['applyServiceIds']) = PermissionRepository::getAlreadyApplyIds($userId, $ids);
                break;
            default:
                break;
        }
        list($managerSeasIds, $joinSeasIds) = SeasGroupRepository::getUserGroupSeasIds($own);
        $result['customerSortIds'] = array_column($result['list'], 'customer_id');
        $result['managerSeasIds'] = $managerSeasIds;
        $result['joinSeasIds'] = $joinSeasIds;
        return $result;
    }

    private function parseTimeDate($seconds)
    {
        $result = (!$seconds || $seconds <= 86400) ? '0' : floor($seconds / 86400);
        return $result . trans('customer.day_ss');
    }

    // 合并客户列表，具有编辑权限的客户
    public function mergeLists($input, $own)
    {
        $input['__TYPE__'] = self::LIST_TYPE_UPDATE;
        return $this->lists($input, $own, self::LIST_TYPE_UPDATE);
    }

    // 转移客户列表，具有编辑权限的客户
    public function transferLists($input, $own)
    {
        $input['__TYPE__'] = self::LIST_TYPE_UPDATE;
        return $this->lists($input, $own, self::LIST_TYPE_UPDATE);
    }

    // 更新客户头像
    public function updateFace(int $customerId, $face, $own)
    {
        if (!$validate = CustomerRepository::validatePermission([CustomerRepository::UPDATE_MARK], [$customerId], $own)) {
            return ['code' => ['0x024003', 'customer']];
        }
        $result = app($this->repository)->updateData(['customer_logo' => $face], ['customer_id' => $customerId]);
        if (!$result) {
            return ['code' => ['0x024004', 'customer']];
        }
        $userId = $own['user_id'] ?? '';
        $identify = 'customer.customer_info.edit';

        CustomerRepository::saveLogs($customerId, trans('customer.modify_customer_face'), $userId,$identify,trans('customer.modify_customer_face'));
        return $result;
    }

    /**
     * 自定义字段过滤方法
     * @return array
     */
    public function filterCustomerLists(&$params,$own)
    {
        $multiSearchs = [];
        if (isset($params['search']['multiSearch'])) {
            $multiSearchs = $params['search']['multiSearch'];
            unset($params['search']['multiSearch']);
        }
        $params['__TYPE__'] = $params['__TYPE__'] ?? '';
        switch ($params['__TYPE__']) {
            case self::LIST_TYPE_ALL:
                $ids = CustomerRepository::ALL_CUSTOMER;
                break;

            case self::LIST_TYPE_UPDATE:
                $ids = CustomerRepository::getUpdateIds($own, [], $params);
                break;

            case self::LIST_TYPE_RECYCLE:
                $ids = CustomerRepository::ALL_CUSTOMER;
                if (isset($params['search']) && !empty($params['search'])) {
                    list($exFlag, $exIds) = CustomerRepository::parseFieldSearch($params['search']);
                    if ($exFlag) {
                        $ids = $exIds;
                    }
                }
                $params['withTrashed']          = true;
                $params['search']['deleted_at'] = ['', '!='];
                break;

            default:
                // 客户公海的信息列表
                $groupId = $params['seasGroupId'] ?? '';
                if ($groupId) {
                    switch ($groupId){
                        // 新增大公海信息列表
                        case 'all':
                            $ids = CustomerRepository::getAllSeasViewIds($own, [], $params);
                            unset($params['seasGroupId']);
                            break;
                        default:
                            $ids = CustomerRepository::getSeasViewIds($groupId, $own, [], $params);
                            break;
                    }
                } else {
                    $ids = CustomerRepository::getViewIds($own, [], $params);
                }
                break;
        }
        unset($params['__TYPE__']);
        // 客户选择器的multisearch特殊处理
        if (!empty($multiSearchs)) {
            if($ids){
                $ids = app($this->repository)->multiSearchIds($multiSearchs, $ids);
            }
        }
        if ($ids === CustomerRepository::ALL_CUSTOMER) {
            // 回收站列表展示客服经理为空客户经理为空客户
            if((isset($params['withTrashed']) && $params['withTrashed']) || isset($params['withSimilar'])){
                return [];
            }
            return isset($params['seasGroupId']) ? [] : ['whereRaw' => "(customer_manager != '' or customer_service_manager != '')"];
        }
        return ['customer_id' => [$ids, 'in']];
    }

    /**
     * 自定义字段配置自己获取total
     * 并且改变params
     */
    public function getCustomerTotal(array &$params)
    {
        $result = null;
        if (isset($params['search']) && !empty($params['search'])) {
            return $result;
        }
        if (!isset($params['filter']['customer_id']) || count($params['filter']['customer_id']) != 2) {
            return $result;
        }
        $result = count($params['filter']['customer_id'][0]);
        $flag   = false;
        if (!isset($params['order_by']) || empty($params['order_by'])) {
            rsort($params['filter']['customer_id'][0]);
            $flag = true;
        } else {
            $sortKey  = array_keys($params['order_by'])[0];
            $sortType = array_values($params['order_by'])[0];
            if (strtolower($sortKey) == 'created_at') {
                if (strtolower($sortType) == 'desc') {
                    rsort($params['filter']['customer_id'][0]);
                } else {
                    sort($params['filter']['customer_id'][0]);
                }
                $flag = true;
            }
        }
        if (!empty($flag)) {
            $limit = $params['limit'] ?? 10;
            $page  = (isset($params['page']) && $params['page']) ? $params['page'] : 1;
            // 取出2倍的id，目的是避免脏数据
            $filterIds                       = array_slice($params['filter']['customer_id'][0], (($page - 1) * $limit), $limit);
            $params['page']                  = 1;
            $params['filter']['customer_id'] = [$filterIds, $params['filter']['customer_id'][1]];
        }
        return $result;
    }

    // 客户经理拥有的客户数量
    public function managerCustomers(array $input, array $own = [])
    {
        $params           = $this->parseParams($input);
        $params['fields'] = ['user_id', 'user_name'];
        $params['include_leave'] = 1;
        $result           = $this->response(app($this->userRepository), 'getUserListTotal', 'getUserList', $params);
        if (!isset($result['total']) || !$result['total'] || !isset($result['list'])) {
            return $result;
        }
        $userIds     = array_column($result['list'], 'user_id');
        $customerIds = CustomerRepository::getUpdateIds($own);
        if ($customerIds === CustomerRepository::ALL_CUSTOMER) {
            $customerIds = null;
        }
        $managerCustomerCounts = SeasGroupRepository::getCustomerCounts($userIds, 0, $customerIds);
        foreach ($result['list'] as $key => $item) {
            $result['list'][$key]['customerCount'] = $managerCustomerCounts[$item['user_id']] ?? 0;
        }
        return $result;
    }

    // 所有的客户
    public function allLists($input, $own)
    {
        $input['__TYPE__'] = self::LIST_TYPE_ALL;
        return $this->lists($input, $own, self::LIST_TYPE_ALL);
    }

    // 新建客户
    public function store($data, $userId)
    {
        $validate = CustomerRepository::validateInput($data);
        if (isset($validate['code'])) {
            return $validate;
        }
        $groupId = (isset($data['seas_group_id']) && $data['seas_group_id']) ? intval($data['seas_group_id']) : SeasGroupRepository::DEFAULT_SEAS;
        if ($groupId !== SeasGroupRepository::DEFAULT_SEAS && !$validate = SeasGroupRepository::checkGroupPermission($groupId, $userId)) {
            return ['code' => ['0x024003', 'customer']];
        }
        $data['seas_group_id'] = $groupId;
        $data['created_at'] = (isset($data['created_at']) && $data['created_at']) ? $data['created_at'] : date("Y-m-d H:i:s");
        if (!$customerId = app($this->formModelingService)->addCustomData($data, self::CUSTOM_TABLE_KEY)) {
            return ['code' => ['0x024004', 'customer']];
        }
        if(is_array($customerId)){
            return $customerId;
        }
        if ($groupId !== SeasGroupRepository::DEFAULT_SEAS) {
            CustomerRepository::updateCustomer($customerId, ['seas_group_id' => $data['seas_group_id']]);
        }
        // 默认共享创建人
        ShareCustomerRepository::storeShareUsers($customerId, [$userId]);
        // 新建客户也统计到今日已分配公海内
        SeasGroupRepository::updateNewDistributeCount($groupId);
        $identify = 'customer.customer_info.add';
        CustomerRepository::saveLogs($customerId, trans('customer.create_customer').'：'.$data['customer_name'], $userId,$identify,$data['customer_name']);
        // 根据公海规则进行分配，或者客户公海待分配+1
        if (!isset($data['customer_manager']) || !$data['customer_manager']) {
            if (!$validate = SeasGroupRepository::distributeCustomer($groupId, [$customerId])) {
                SeasGroupRepository::updateWaitDistributeCount($groupId);
            }
        }

//        // 默认共享创建人(如果客户经理不是自己，则默认分享给自己)
//        if($result = app($this->repository)->getDetail($customerId)){
//            if($result->customer_manager && $result->customer_manager != $userId){
//                ShareCustomerRepository::storeShareUsers($customerId, [$userId]);
//            }else if($result->customer_manager == '' && $result->customer_service_manager == ''){
//                ShareCustomerRepository::storeShareUsers($customerId, [$userId]);
//            }
//        }

        // 全站搜索消息队列更新数据
        $this->updateGlobalSearchDataByQueue($customerId);

        return $customerId;
    }

    // 外发创建客户
    public function flowStore(array $params)
    {
        $data   = $params['data'] ?? [];
        $userId = (isset($data['customer_creator']) && $data['customer_creator']) ? $data['customer_creator'] : $params['current_user_id'];
        $data['customer_creator'] = $userId;
        $data['customer_number'] = (isset($data['customer_number']) && $data['customer_number']) ? $data['customer_number'] : '';
        $result = $this->store($data, $userId);
        if(isset($result['code'])){
            return $result;
        }
        return [
            'status' => 1,
            'dataForLog' => [
                [
                    'table_to' => 'customer',
                    'field_to' => 'customer_id',
                    'id_to' => $result
                ]
            ]
        ];
    }


    /**
     * 网站来源数据
     */
    public function storeWebCustomer($data)
    {
        $customerData = isset($data['customer']) ? (array) $data['customer'] : [];
        $linkmanData  = isset($data['linkman']) ? (array) $data['linkman'] : [];
        if (empty($customerData) || empty($linkmanData)) {
            return ['code' => ['0x000001', 'common']];
        }
        // 根据城市查找对应省和市的id
        if (isset($customerData['city'])) {
            list($customerData['province'], $customerData['city']) = \App\EofficeApp\System\Address\Services\AddressService::listIdByFullName($customerData['city']);
        }
        $customerData['customer_from'] = (isset($customerData['customer_from']) && $customerData['customer_from']) ? $customerData['customer_from'] : self::FROM_WEBSITE;
        $validate                      = CustomerRepository::validateInput($customerData);
        if (isset($validate['code'])) {
            return $validate;
        }
        // 处理最后分配时间 last_distribute_time 字段
        $customerData['last_distribute_time'] = time();
        if (!$result = app($this->repository)->insertData($customerData)) {
            return false;
        }
        // 公海规则分配，或者客户公海待分配+1
        if (!isset($customerData['customer_manager']) || !$customerData['customer_manager']) {
            if (!$validate = SeasGroupRepository::distributeCustomer(SeasGroupRepository::DEFAULT_SEAS, [$result->customer_id])) {
                SeasGroupRepository::updateWaitDistributeCount(SeasGroupRepository::DEFAULT_SEAS);
            }
        }
        if($linkmanData){
            $linkmanData['customer_id'] = $result->customer_id;
            app($this->linkmanRepository)->insertData($linkmanData);
        }
        return true;
    }

    /**
     * 网站来源数据
     */
    public function updateWebLinkman($data)
    {
        $phone = isset($data['mobile']) ? strval($data['mobile']) : '';
        if (!$phone) {
            return false;
        }
        $where = ['mobile_phone_number' => $phone];
        return app($this->linkmanRepository)->updateData(['linkman_remarks' => trans("customer.verified")], $where);
    }

    // 编辑客户
    public function update($customerId, $data, $own,$flowUpdate = false)
    {

        $originData = app($this->repository)->getDetail($customerId);
        if(!$originData){
            return ['code' => ['0x024011','customer']];
        }
        $originData = $originData->toArray();
        $validate = CustomerRepository::validatePermission([CustomerRepository::UPDATE_MARK], [$customerId], $own);
        if(isset($validate['code'])){
            return $validate;
        }
        if(!$validate){
            return ['code' => ['0x024003', 'customer']];
        }

        // 处理客户外发更新
        if($flowUpdate){
            self::parseFlowUpdate($data,$originData);
        }

        $validate = CustomerRepository::validateInput($data, $customerId);
        if (isset($validate['code'])) {
            return $validate;
        }
        // 解决编辑客户时删除公海分组导致公海内无法找到该客户 -- 20210722  ---此处不判断公海的权限 20210804
        if (isset($data['seas_group_id'])) {
            $groupId = (isset($data['seas_group_id']) && $data['seas_group_id']) ? intval($data['seas_group_id']) : SeasGroupRepository::DEFAULT_SEAS;
    //        if ($groupId !== SeasGroupRepository::DEFAULT_SEAS && !$validate = SeasGroupRepository::checkGroupPermission($groupId, $own['user_id'])) {
    //            return ['code' => ['0x024003', 'customer']];
    //        }
            $data['seas_group_id'] = $groupId;
        }
        if (!$result = app($this->formModelingService)->editCustomData($data, self::CUSTOM_TABLE_KEY, $customerId)) {
            return $result;
        }
        if(isset($result['code'])){
            return $result;
        }
        if (isset($data['customer_manager']) && $originData['customer_manager'] != $data['customer_manager']) {
            CustomerRepository::updateCustomer($customerId, ['last_distribute_time' => time()]);
        }
        // 变更客户名称，拼音及简写同步修改
        if(isset($data['customer_name']) && $originData['customer_name'] != $data['customer_name']){
            list($customer_name_py, $customer_name_zm) = convert_pinyin($data['customer_name']);
            $updateData = [
                'customer_name_py' => $customer_name_py,
                'customer_name_zm' => $customer_name_zm,
            ];
            CustomerRepository::updateCustomer($customerId, $updateData);
        }
        // 客户、客服经理变动，发送消息
        app($this->repository)->sendUpdateMessage($originData, $data, $customerId);
        // 客户更改了哪些字段记录日志
        $userId = $own['user_id'] ?? '';
        app($this->repository)->saveUpdateLogs($originData, $data, $userId);
        if(($originData['customer_manager'] == '' && (isset($data['customer_manager']) && $data['customer_manager'])) || ($originData['customer_manager'] && (isset($data['customer_manager']) && $data['customer_manager'] == ''))){
            SeasGroupRepository::updateWaitDistributeCount($data['seas_group_id'], 1);
            SeasGroupRepository::updateNewDistributeCount($data['seas_group_id'], 1);
            SeasGroupRepository::refreshUserPickUps($data['seas_group_id'], [$customerId], $own['user_id']);
            SeasGroupRepository::updateUserCanPickUps($data['seas_group_id'], $own['user_id'], -1);
        }

        if($finalData = app($this->repository)->getDetail($customerId)){
            if(!$finalData['customer_manager']){
                // 退回公海的客户，同时删除掉分享的数据
                ShareCustomerRepository::deleteAllShares([$customerId]);
            }
        };
        
        // 全站搜索消息队列更新数据
        $this->updateGlobalSearchDataByQueue($customerId);

        return true;
    }

    // 客户详情
    public function show($customerId, $params, $own)
    {
        if (!$validate = CustomerRepository::validatePermission([CustomerRepository::VIEW_MARK, CustomerRepository::UPDATE_MARK], [$customerId], $own)) {
            return ['code' => ['0x024003', 'customer']];
        }
        $userId = $own['user_id'] ?? 0;

        if (!$result = app($this->repository)->show($customerId)) {
            return ['code' => ['0x024005', 'customer']];
        }
        // 写入查看日志 --- 新建完成跳转后面会请求接口增加相关处理
        if (!isset($params['log']) || (isset($params['log']) && intval($params['log']) !== 0)) {
            $identify = 'customer.customer_info.view';
            CustomerRepository::saveLogs($customerId, trans('customer.view_customer').'：'.$result['customer_name'], $userId,$identify,$result['customer_name']);
        }
        // 客户头像
        if ($result['customer_logo']) {
            $result['thumb_attachment_name'] = app($this->attachmentService)->getCustomerFace($result['customer_logo']);
        }
        if (isset($params['isMobile'])) {
            $result['customer_type_name'] = app($this->systemComboboxService)->getComboboxFieldsNameByIdentify('CUSTOMER_TYPE', $result['customer_type']);
            if ($linkmanList = LinkmanRepository::getLinkmanByCustomerId($customerId)) {
                $result['linkman_name'] = $linkmanList->linkman_name;
                $result['phone_number'] = $linkmanList->mobile_phone_number ?? ($linkmanList->company_phone_number ?? $linkmanList->home_phone_number);
            }
        }
        if ($validate & CustomerRepository::UPDATE_MARK) {
            $result['hasUpdatePermission'] = true;
            // 具有分享权限，获取分享对象
            if (isset($params['type']) && $params['type'] == 'share') {
                list($result['permission_user'], $result['permission_dept'], $result['permission_role']) = ShareCustomerRepository::getCustomerShareIds($customerId);
            }
        }

        return $result;
    }

    /**
     * 软删除，客户信息列表删除，加入回收站
     * 并且更新软删除的缓存
     */
    public function delete(array $customerIds, $own)
    {
        if (!$validate = CustomerRepository::validatePermission([CustomerRepository::DELETE_MARK], $customerIds, $own)) {
            return ['code' => ['0x024003', 'customer']];
        }
        $userId = $own['user_id'] ?? '';
        $lists  = CustomerRepository::getLists($customerIds, ['customer_manager', 'seas_group_id','customer_name','customer_id']);
        if ($lists->isEmpty()) {
            return ['code' => ['0x024005', 'customer']];
        }
        app($this->repository)->deleteById($customerIds);
        foreach ($lists as $list) {
            $identify = 'customer.customer_info.delete';
            CustomerRepository::saveLogs($list->customer_id, trans('customer.delete_customer').'：'.$list->customer_name, $userId,$identify,$list->customer_name);
        }
        // 更新公海分配和未分配缓存
        foreach ($lists as $index => $item) {
            if (!$item->customer_manager) {
                SeasGroupRepository::updateWaitDistributeCount($item->seas_group_id, -1);
            }
        }
        // 更新软删除数据缓存
        CustomerRepository::refreshRedisSoftCustomers();

        // 全站搜索消息队列更新数据
        $this->updateGlobalSearchDataByQueue($customerIds);

        return true;
    }

    // 物理删除，回收站中删除
    public function deleteRecycleCustomers(array $customerIds)
    {
        // 删除前先查询出客户名称，方便记录日志
        $customerNames = CustomerRepository::getCustomerById($customerIds,['customer_name','customer_id']);

        $where = ['customer_id' => [$customerIds, 'in']];
        app($this->repository)->deleteSoftDelete($where, false);
        app($this->linkmanRepository)->deleteByWhere($where, false);
        app($this->contractRepository)->deleteByWhere($where, false);
        app($this->saleRecordRepository)->deleteByWhere($where, false);
        app($this->contactRecordRepository)->deleteByWhere($where, false);
        app($this->businessChanceRepository)->deleteByWhere($where, false);
        app($this->permissionRepository)->deleteByWhere($where, false);
        ShareCustomerRepository::deleteAllShares($customerIds);

        $prefixLogContent = trans('customer.recycle_delete_customer');
        // 记录日志删除
        if($customerNames){
            foreach ($customerNames as $key => $customerName){
                $identify = 'customer.customer_info.delete';
                CustomerRepository::saveLogs($customerName->customer_id, $prefixLogContent.$customerName->customer_name, own()['user_id'],$identify,$customerName->customer_name);
            }
        }

        return true;
    }

    // 获取客户详情菜单
    public function menus(int $customerId, array $own)
    {
        $menuLists = FormModelingRepository::getCustomTabMenus(self::CUSTOM_TABLE_KEY, function() {
            return CustomerRepository::getCustomerTabMenus();
        }, $customerId);
        if (empty($menuLists)) {
            return $menuLists;
        }
        $local = Lang::getLocale();
        $labelTitle = '';
        foreach ($menuLists as $key => $item) {
            if (!isset($item['key'])) {
                continue;
            }
            // 标签名称替换
            // 获取标签名称
            $tLang = '';
            if(isset($item['foreign_key'])){
                $tLang = mulit_trans_dynamic("custom_fields_table.field_name." . $item['menu_code'] . "_" . $item['foreign_key']);
                $labelTitle = DB::table('customer_label_translation')->where(['table_key'=>self::CUSTOM_TABLE_KEY,'key'=>$item['key'],'foreign_key'=>$item['foreign_key']])->first();
                if($item['key'] == self::CUSTOM_TABLE_KEY){
                    $item['view'] = [
                        "custom/list",
                        ['menu_code' => $item['key'], 'primary_key' => FormModelingRepository::getPrimaryKey($item['menu_code'])],
                    ];
                    $item['count'] = '';
                }
            } else if (isset($item['key'])) {
                $labelTitle = DB::table('customer_label_translation')->where(['table_key'=>self::CUSTOM_TABLE_KEY,'key'=>$item['key'],'foreign_key'=>''])->first();
                $tLang = mulit_trans_dynamic("custom_menu_config.menu_name.custom_config_" . $item['key']);
                if(!$tLang){
                    $tLang = trans(self::CUSTOM_TABLE_KEY . '.' . $item['key']);
                }
            }

            $item['title_zh'] = ($labelTitle && $labelTitle->title_zh) ? $labelTitle->title_zh : $tLang;
            $item['title'] = ($local != 'zh-CN') ? $tLang : $item['title_zh'];
            if (isset($item['count'])) {
                $item['count'] = $this->getCustomerMenuCount($item['key'], $customerId, $own);
            }

            $menuLists[$key] = $item;
        }
        return $menuLists;
    }

    // 获取客户详情页面菜单的数据总数
    private function getCustomerMenuCount(string $key, int $customerId, array $own = [])
    {
        $result = 0;
        switch (strtolower($key)) {
            case 'customer_linkman':
                $result = LinkmanRepository::customerDetailMenuCount($customerId,$own);
                break;

            case 'customer_business_chance':
                $result = BusinessChanceRepository::customerDetailMenuCount($customerId);
                break;

            case 'customer_contract':
                $result = ContractRepository::customerDetailMenuCount($customerId);
                break;

            case 'will-visits':
                $result = VisitRepository::customerDetailMenuCount($customerId);
                break;

            default:
                $menuParams = [
                    'foreign_key' => $customerId,
                    'response' => 'count',
                ];
                $count   = app('App\EofficeApp\FormModeling\Services\FormModelingService')->getCustomDataLists($menuParams, $key, $own);
                $result = is_numeric($count) ? $count : 0;
        }
        return $result;
    }

    /**
     * 关注和取消关注
     * @param $flag 默认表示关注
     */
    public function attention($customerId, $own, $flag = false)
    {
        $userId = $own['user_id'] ?? '';
        // 暂时去除权限验证
//        if ($customerId && !$validate = CustomerRepository::validatePermission([CustomerRepository::VIEW_MARK], [$customerId], $own)) {
//            return ['code' => ['0x024003', 'customer']];
//        }
        if(!$flag){
            if($returnData = app($this->attentionRepository)->getAttentionIds($own['user_id'],[$customerId])){
                if($returnData){
                    return ['code' => ['repeat_attention', 'customer']];
                }
            }
        }
        if (!$result = app($this->attentionRepository)->attention($customerId, $userId, $flag)) {
            return ['code' => '0x024004', 'customer'];
        }
        $customerNames = CustomerRepository::getCustomerById($customerId,['customer_name','customer_id']);
        $logContent = !$flag ? trans('customer.add_attend').'：'. $customerNames->customer_name: trans('customer.remove_attend').'：'. $customerNames->customer_name;
        $identify = 'customer.customer_info.attention';
        CustomerRepository::saveLogs($customerId, $logContent, $userId,$identify,$customerNames->customer_name);
        return $result;
    }

    // 获取日志列表
    public function logLists($customerId, $input, $own)
    {
        $params                                 = $this->parseParams($input);
        $params['search']['log_relation_table'] = [isset($params['relation_table']) ? $params['relation_table'] : self::CUSTOM_TABLE_KEY];
        $params['search']['log_relation_id']    = [$customerId];
        // 去除回收站查看日志限制
        // if ($customerId && !$validate = CustomerRepository::validatePermission([CustomerRepository::VIEW_MARK], [$customerId], $own)) {
        //     return ['code' => ['0x024003', 'customer']];
        // }
        return app($this->logService)->getSystemLogList($params);
    }

    // 获取日志列表(新)
    public function newLogLists($customerId)
    {
        $params = [
            'limit' => 15,
            'page' => 1,
            'module_key' => 'customer',
            'category' => 'customer_info',
            'data_id' => $customerId,
            'data_table' => isset($params['relation_table']) ? $params['relation_table'] : self::CUSTOM_TABLE_KEY,
        ];
        return  app($this->logRecordService)->getOneDataLogs($params);
    }

    // 获取申请权限列表
    public function applyPermissionLists($param, $userId)
    {
        $param           = $this->parseParams($param);
        $param['fields'] = [
            'apply_id', 'apply_permission',
            'apply_status', 'created_at',
            'customer_id', 'proposer',
            'customer_name',
            'customer_manager_name',
            'view_permission',
            'apply_permission_to_user_name',
        ];
        $param['search']['proposer']        = [$userId];
        $param['search']['softCustomerIds'] = CustomerRepository::getSoftCustomerIds();
        return $this->response(app($this->permissionRepository), 'total', 'lists', $param);
    }

    public function showApplyPermissions(int $applyId, array $own)
    {
        return app($this->permissionRepository)->show($applyId);
    }

    // 恢复回收站客户
    public function recoverRecycleCustomer($customerIds, $userId)
    {
        $customerIds = array_filter(explode(',', $customerIds));
        $wheres      = ['customer_id' => [$customerIds, 'in']];
        app($this->repository)->restoreSoftDelete($wheres);
        $preLogContent = trans('customer.recover_customer').'：';
        foreach ($customerIds as $customerId) {
            $customerNames = CustomerRepository::getCustomerById($customerId,['customer_name']);
            $identify = 'customer.customer_info.customer_recover';
            CustomerRepository::saveLogs($customerId, $preLogContent.$customerNames->customer_name, $userId,$identify,$customerNames->customer_name);
        }
        // 更新软删除数据缓存
        CustomerRepository::refreshRedisSoftCustomers();
        return true;
    }

    // 回收站列表
    public function recycleCustomerLists(array $input, array $own)
    {
        $input['__TYPE__'] = self::LIST_TYPE_RECYCLE;
        $result            = app($this->formModelingService)->getCustomDataLists($input, self::CUSTOM_TABLE_KEY, $own);
        if (!isset($result['total']) || !$result['total']) {
            return $result;
        }
        $nowTime = time();
        foreach ($result['list'] as $key => $item) {
            $ids[] = $item->customer_id;
//            if (isset($item->last_contact_time)) {
//                $secondTime = strtotime($item->last_contact_time);
//                if ($secondTime <= 0) {
//                    $secondTime = isset($item->created_at) ? strtotime($item->created_at) : 0;
//                }
//                $item->last_contact_time = $this->parseTimeDate($nowTime - $secondTime);
//            }
            $result['list'][$key] = $item;
        }
        return $result;
    }

    // 公海列表
    public function seasLists($own, $input)
    {
        $result                             = [];
        list($managerSeasIds, $joinSeasIds) = SeasGroupRepository::getUserGroupSeasIds($own);
        $allGroupIds                        = array_unique(array_merge($managerSeasIds, $joinSeasIds));
        if (empty($allGroupIds)) {
            return $result;
        }
        $params                 = $this->parseParams($input);
        if(!isset($params['search']['id'])){
            if(isset($params['filter_info'])){
                if($params['filter_info'] == 'hasJoinPermission'){
                    $params['search']['id'] = [$joinSeasIds, 'in'];
                }
                if($params['filter_info'] == 'hasManagePermission'){
                    $params['search']['id'] = [$managerSeasIds, 'in'];
                }
            }else{
                $params['search']['id'] = [$allGroupIds, 'in'];
            }
        }
        if(isset($params['fields'])){
            if(!in_array('id',$params['fields'])){
                array_push($params['fields'],'id');
            }
        }

        // 处理大公海搜索
        if(isset($input['platform']) && $input['platform'] == 'mobile' && isset($params['filter_info']) && $params['filter_info'] == 'allSeas'){
            if(isset($params['search']) && isset($params['search']['name'])){
                $searchString = $params['search']['name'][0];
                if (strpos('大公海', $searchString) !== false) {
                    unset($params['search']['name']);
                }else{
                    return [];
                }
            }
        }
        $result                 = app($this->seasGroupRepository)->getLists($params);
        if ($result->isEmpty()) {
            return $result;
        }
        foreach ($result as $index => $item) {
            if (in_array($item->id, $managerSeasIds)) {
                $item->hasManagePermission = true;
            }
            if (in_array($item->id, $joinSeasIds)) {
                $item->hasJoinPermission = true;
            }
            $item->newDistribute  = SeasGroupRepository::newDistribute($item->id);
            $item->waitDistribute = SeasGroupRepository::waitDistribute($item->id);
            $result[$index]       = $item;
        }
        if(isset($input['platform']) && $input['platform'] == 'mobile' && isset($input['seas_list']))
        {
            $returnData = [];
            $newDistribute = $waitDistribute = 0;
            if($result){
                array_map(function ($result) use($input,&$returnData,&$newDistribute,&$waitDistribute){
                        if(isset($input['filter_info']) && $input['filter_info'] == 'allSeas'){
                            $newDistribute  += $result['newDistribute'];
                            $waitDistribute += $result['waitDistribute'];
                        }else{
                            if(isset($result[$input['filter_info']])){
                                return $returnData[]= $result;
                            }
                        }
                        
                    },$result->toArray());
                    if($input['filter_info'] == 'allSeas'){
                        $returnData[] = [
                            'id' => 'all',
                            'name' => trans('customer.all_seas'),
                            'newDistribute' => $newDistribute,
                            'waitDistribute'=> $waitDistribute,
                            'hasManagePermission' => 1,
                            'hasJoinPermission' => 1
                        ];
                    }
            }
            $total = 0;
            if(isset($input['filter_info']) && $input['filter_info'] == 'hasManagePermission'){
                $total = count($managerSeasIds);
            }else if(isset($input['filter_info']) && $input['filter_info'] == 'hasJoinPermission'){
                $total = count($joinSeasIds);
            }
            return [
                'list' => $returnData,
                'total' => $total ?? count($returnData),
            ];
        }
        return $result;
    }

    // 获取公海分组详情
    public function showSeasGroup(int $groupId)
    {
        $result                                          = [];
        $result['groupData']                             = SeasGroupRepository::showGroup($groupId);
        list($result['rulesData'], $result['userLists']) = SeasGroupRepository::showGroupRules($groupId);
        return $result;
    }

    // 删除公海分组
    public function deleteSeasGroup(int $groupId)
    {
        if (intval($groupId) === 1) {
            return ['code' => ['0x024006', 'customer']];
        }
        if (!$validate = SeasGroupRepository::validateDeletes($groupId)) {
            return ['code' => ['0x024024', 'customer']];
        }
        return SeasGroupRepository::deleteSeasGroup($groupId);
    }

    // 新增客户公海分组
    public function storeSeasGroup(array $input)
    {
        $validate = SeasGroupRepository::validateInput($input);
        if (isset($validate['code'])) {
            return $validate;
        }
        list($groupData, $rulesData) = $validate;
        if (!$groupId = SeasGroupRepository::storeSeasGroup($groupData)) {
            return ['code' => ['0x024004', 'customer']];
        }
        return SeasGroupRepository::storeSeasGroupRules($groupId, $rulesData);
    }

    // 更新客户公海分组
    public function updateSeasGroup(int $groupId, array $input)
    {
        $validate = SeasGroupRepository::validateInput($input, $groupId);
        if (isset($validate['code'])) {
            return $validate;
        }
        list($groupData, $rulesData) = $validate;
        SeasGroupRepository::updateSeasGroup($groupId, $groupData);
        return SeasGroupRepository::updateSeasGroupRules($groupId, $rulesData);
    }

    /**
     * 捡起客户
     */
    public function pickUpCustomers($input)
    {
        $userId      = isset($input['userId']) ? $input['userId'] : '';
        $seasGroupId = isset($input['seasGroupId']) ? $input['seasGroupId'] : '';
        $customerIds = isset($input['customerIds']) ? $input['customerIds'] : [];
        if ($seasGroupId === '' || $userId === '' || empty($customerIds)) {
            return ['code' => ['0x024002', 'customer']];
        }
        // 判断客户能否被捡起
        $validate = SeasGroupRepository::validateCustomerPickUp($customerIds, $seasGroupId);
        if(isset($validate['code'])){
            return $validate;
        }

        // 判断用户捡起权限
        $validatePermission = SeasGroupRepository::validatePickUps($seasGroupId, $userId);
        if ($validatePermission === SeasGroupRepository::PICK_NO_PERMISSION) {
            return ['code' => ['not_pick_up_permission', 'customer']];
        }
        // 管理员权限不受限制
        if ($validatePermission === SeasGroupRepository::PICK_USER_PERMISSION) {
            // 回收期内不可再次分配同一成员
            if (!$validate = SeasGroupRepository::validateRecycles($customerIds, $seasGroupId, $userId)) {
                return ['code' => ['0x024007', 'customer']];
            }
            // 可捡起的总数判断
            $canPickNum = SeasGroupRepository::getUserCanPickUpNumber($seasGroupId, $userId);
            if ($canPickNum !== null && $canPickNum !== SeasGroupRepository::CAN_PICK_INFINITE && count($customerIds) > $canPickNum) {
                return ['code' => ['0x024008', 'customer']];
            }
        }
        // 写入回收表
        if (!$validate = SeasGroupRepository::insertRecycles($customerIds,2)) {
            return ['code' => ['0x024004', 'customer']];
        }
        if (!$result = SeasGroupRepository::pickUps($customerIds, $seasGroupId, $userId)) {
            return ['code' => ['0x024004', 'customer']];
        }
        $preLogContent = trans('customer.pick_customer').'：';
        foreach ($customerIds as $customerId) {
            $customerNames = CustomerRepository::getCustomerById($customerId,['customer_name']);
            $identify = 'customer.customer_info.customer_pick';
            CustomerRepository::saveLogs($customerId, $preLogContent.$customerNames->customer_name, $userId,$identify,$customerNames->customer_name);
        }
        return $result;
    }

    /**
     * 退回公海
     */
    public function pickDownCustomers($userId, array $input)
    {
        $customerIds = isset($input['customerIds']) ? (array) $input['customerIds'] : '';
        $seasGroupId = isset($input['seasGroupId']) ? $input['seasGroupId'] : '';
        $remark      = isset($input['remark']) ? $input['remark'] : '';
        if ($userId === '' || empty($customerIds)) {
            return ['code' => ['0x024002', 'customer']];
        }
        // 判断退回权限
        $validate = SeasGroupRepository::validatePickDowns($customerIds, $userId);
        if(isset($validate['code'])){
            return $validate;
        }
        // 写入回收表
        if (!$validate = SeasGroupRepository::insertRecycles($customerIds,true)) {
            return ['code' => ['0x024004', 'customer']];
        }
        if (!$result = SeasGroupRepository::pickDowns($customerIds, $seasGroupId, $userId)) {
            return ['code' => ['0x024004', 'customer']];
        }

        $preLogContent = trans('customer.pick_down_customer').'：';
        $remark = $remark ? (trans('customer.come_back_reason').'：'.$remark) : '';
        foreach ($customerIds as $customerId) {
            $customerData = CustomerRepository::getCustomerById($customerId,['customer_name']);
            $identify = 'customer.customer_info.customer_back';
            CustomerRepository::saveLogs($customerId, $preLogContent.$customerData->customer_name.'。'.$remark, $userId,$identify,$customerData->customer_name);
	    // 退回公海的客户，同时删除掉分享的数据
            ShareCustomerRepository::deleteAllShares([$customerId]);
        }
        return $result;
    }

    /**
     * 批量更改公海客户经理,只有管理用户能操作
     * @param  int $userId
     * @param  array $input
     * @return bool
     */
    public function changeSeasCustomerManager($userId, $input)
    {
        $seasGroupId       = isset($input['seasGroupId']) ? $input['seasGroupId'] : '';
        $customerIds       = isset($input['customerIds']) ? $input['customerIds'] : [];
        $customerManagerId = isset($input['customerManager']) ? $input['customerManager'] : '';
        if ($seasGroupId === '' || $userId === '' || empty($customerIds)) {
            return ['code' => ['0x024002', 'customer']];
        }
        if (!$validate = SeasGroupRepository::validateIsManager($seasGroupId, $userId)) {
            return ['code' => ['0x024003', 'customer']];
        }
        // 判断用户是否属于这个公海
        if (!in_array($seasGroupId, SeasGroupRepository::getUserSeasGroupIds($customerManagerId))) {
            return ['code' => ['0x024002', 'customer']];
        }
        $user_name = DB::table('user')->where('user_id',$customerManagerId)->value('user_name');

        $remark = trans('customer.customer_manager').'->'.$user_name;
        if(count($customerIds) == 1){
            $customerData = CustomerRepository::getCustomerById($customerIds[0],['customer_manager']);
            if($customerData && $customerData->customer_manager == ''){
                $remark = trans('customer.customer_manager').' '.trans('customer.empty').'->'.$user_name;
            }
        }

        SeasGroupRepository::updateCustomerManager($customerIds, $seasGroupId, $customerManagerId);

        $preLogContent = trans('customer.designated_customer').'：';

        $identify = 'customer.customer_info.customer_appoint';
        $customerCount = count($customerIds);
        if($customerCount == 1){

            $customerData = CustomerRepository::getCustomerById($customerIds[0],['customer_name']);

            CustomerRepository::saveLogs($customerIds[0], $preLogContent.$customerData->customer_name.'。'.$remark, $userId,$identify,$customerData->customer_name);
            //推送给新客户经理
            $customerData = CustomerRepository::getCustomerById($customerIds[0],['customer_name']);

            $sendData['remindMark'] = $customerCount == 1 ? 'customer-change' : 'customer-transfer';
            $sendData['toUser'] = $customerManagerId;
            $sendData['contentParam'] = ['customerName' => $customerData->customer_name];
            if ($customerCount > 1) {
                $sendData['contentParam']['newCustomerNumber'] = $customerCount;
            }
            $sendData['stateParams'] = ['customer_id' => $customerIds[0]];
            Eoffice::sendMessage($sendData);
        }else{
            foreach ($customerIds as $customerId) {
                $customerData = CustomerRepository::getCustomerById($customerId,['customer_name']);
            	CustomerRepository::saveLogs($customerId, $preLogContent.$customerData->customer_name.'。'.$remark, $userId,$identify,$customerData->customer_name);
            }
            //推送给新客户经理
            $customerData = CustomerRepository::getCustomerById($customerIds[0],['customer_name']);
            $sendData['remindMark'] = 'customer-transfer';
            $sendData['toUser'] = $customerManagerId;
            $sendData['contentParam'] = ['customerName' => $customerData->customer_name, 'newCustomerNumber' => $customerCount];
            $sendData['stateParams'] = ['customer_id' => $customerIds[0]];
            Eoffice::sendMessage($sendData);
        }

    }

    /**
     * 批量转移公海客户经理,只有管理用户能操作
     * @param  int $userId
     * @param  array $input
     * @return bool
     */
    public function transferSeasCustomerManager($userId, $input)
    {
        $seasGroupId       = isset($input['seasGroupId']) ? $input['seasGroupId'] : '';
        $userIds           = isset($input['userIds']) ? $input['userIds'] : [];
        $customerManagerId = isset($input['customerManager']) ? $input['customerManager'] : '';
        if ($seasGroupId === '' || $userId === '' || empty($userIds)) {
            return ['code' => ['0x024002', 'customer']];
        }
        if (!$validate = SeasGroupRepository::validateIsManager($seasGroupId, $userId)) {
            return ['code' => ['0x024003', 'customer']];
        }
        // 判断用户是否属于这个公海
        if (!in_array($seasGroupId, SeasGroupRepository::getUserSeasGroupIds($customerManagerId))) {
            return ['code' => ['0x024002', 'customer']];
        }
        $updateCustomerNumber = SeasGroupRepository::transferCustomerManager($userIds, $seasGroupId, $customerManagerId);
        //推送给新客户经理
        $sendData['remindMark'] = 'customer-transfer';
        $sendData['toUser'] = $customerManagerId;
        $sendData['contentParam'] = ['customerName' => '', 'newCustomerNumber' => $updateCustomerNumber];
        $sendData['stateParams'] = ['customer_id' => ''];
        Eoffice::sendMessage($sendData);
        return true;
    }

    /**
     * 批量更改公海组,只有管理用户能操作，清空客户经理
     * @param  int $userId
     * @param  array $input
     * @return bool
     */
    public function changeSeas($userId, $input)
    {
        $groupId        = isset($input['seasGroupId']) ? $input['seasGroupId'] : '';
        $customerIds    = isset($input['customerIds']) ? $input['customerIds'] : [];
        $newSeasGroupId = isset($input['newSeasGroupId']) ? $input['newSeasGroupId'] : '';
        if ($groupId === '' || $userId === '') {
            return ['code' => ['0x024002', 'customer']];
        }
        if (!$validate = SeasGroupRepository::validateIsManager($groupId, $userId)) {
            return ['code' => ['0x024003', 'customer']];
        }
        if (!$result = SeasGroupRepository::changeSeas($groupId, $newSeasGroupId, $customerIds)) {
            return ['code' => ['0x024004', 'customer']];
        }
        if (!$validate = SeasGroupRepository::distributeCustomer($newSeasGroupId, $customerIds)) {
            SeasGroupRepository::updateWaitDistributeCount($newSeasGroupId, count($customerIds));
        }
        // 退回公海的客户，同时删除掉分享的数据
        ShareCustomerRepository::deleteAllShares($customerIds);
        return $result;
    }

    public function seasCustomerManagerLists($groupId, $userId, $input)
    {
        $params          = $this->parseParams($input);
        $seasOpenUserIds = SeasGroupRepository::getOpenUserIds($groupId);
        if ($seasOpenUserIds !== self::LIST_TYPE_ALL) {
            $params['search']['user_id'] = [$seasOpenUserIds, 'in'];
        }
        $result = $this->response(app($this->userRepository), 'getUserListTotal', 'getUserList', $params);
        if (!isset($result['total']) || !$result['total'] || !isset($result['list'])) {
            return $result;
        }
        $userIds               = array_column($result['list'], 'user_id');
        $managerCustomerCounts = SeasGroupRepository::getCustomerCounts($userIds, $groupId);
        foreach ($result['list'] as $key => $item) {
            $result['list'][$key]['customerCount'] = $managerCustomerCounts[$item['user_id']] ?? 0;
        }
        return $result;
    }

    // 公海配置列表
    public function seasGroupLists($input)
    {
        $params  = $this->parseParams($input);
        $seasObj = app($this->seasGroupRepository);
        return $this->response($seasObj, 'getTotal', 'getLists', $params);
    }

    // 每天定时客户回收任务
    public function recycleCustomers()
    {
        $recycleRules = SeasGroupRepository::getRecycleDatas();
        if (!empty($recycleRules)) {
            CustomerRepository::recycleCustomers($recycleRules);
        }
        // 刷新缓存
        SeasGroupRepository::refreshWaitDistribute();
        SeasGroupRepository::refreshNewDistribute();
        return true;
    }

    // 添加客户分享
    public function shareCustomers(array $input, array $own)
    {
        $customerIds = isset($input['customer_ids']) ? $input['customer_ids'] : [];
        $deptIds     = isset($input['permission_dept']) ? (array) $input['permission_dept'] : [];
        $roleIds     = isset($input['permission_role']) ? (array) $input['permission_role'] : [];
        $userIds     = isset($input['permission_user']) ? (array) $input['permission_user'] : [];
        if (empty($customerIds)) {
            return ['code' => ['0x024002', 'customer']];
        }
        if (!$validate = CustomerRepository::validatePermission([CustomerRepository::VIEW_MARK], $customerIds, $own)) {
            return ['code' => ['0x024003', 'customer']];
        }
        if (empty($deptIds) && empty($roleIds) && empty($userIds)) {
            return true;
        }
        // 具有特定范围
        if (!empty($deptIds)) {
            ShareCustomerRepository::diffInsertDeptIds($customerIds, $deptIds);
        }
        if (!empty($roleIds)) {
            ShareCustomerRepository::diffInsertRoleIds($customerIds, $roleIds);
        }
        if (!empty($userIds)) {
            ShareCustomerRepository::diffInsertUserIds($customerIds, $userIds);
        }
        ShareCustomerRepository::parseTempShareIds($own);
        return true;
    }

    // 客户详情添加客户分享
    public function shareCustomer(int $customerId, array $input, array $own)
    {
        $deptIds = isset($input['permission_dept']) ? (array) $input['permission_dept'] : [];
        $roleIds = isset($input['permission_role']) ? (array) $input['permission_role'] : [];
        $userIds = isset($input['permission_user']) ? (array) $input['permission_user'] : [];
        if (!$validate = CustomerRepository::validatePermission([CustomerRepository::UPDATE_MARK], [$customerId], $own)) {
            return ['code' => ['0x024003', 'customer']];
        }
        ShareCustomerRepository::diffInsertUserIds([$customerId], $userIds, true);
        ShareCustomerRepository::diffInsertDeptIds([$customerId], $deptIds, true);
        ShareCustomerRepository::diffInsertRoleIds([$customerId], $roleIds, true);

        ShareCustomerRepository::parseTempShareIds($own);
        return true;
    }

    public function storeVisit(array $data, array $own = [])
    {
        $validate = VisitRepository::validateInput($data, 0, $own);
        if (isset($validate['code'])) {
            return $validate;
        }
        $reminder = (array) $data['reminder'];
        unset($data['reminder']);

        if (!$list = app($this->visitRepository)->insertData($data)) {
            return ['code' => ['0x024004', 'customer']];
        }
        $this->createReminder($list->visit_id, $reminder);
        /*关联日程提醒*/
        // if(isset($data['remind']) && $data['remind'] && $reminder){
            
        // }
        self::addCalendar($reminder,$list->toArray(), null, $own);

        // 全站搜索消息队列更新数据
        $this->updateGlobalSearchWillVisitDataByQueue($list->visit_id);
    }

    public function addCalendar($reminder,$list,$mark = null, $own = []){
        $list = (array) $list;
        // 外发到日程模块 --开始--
        $title = trans('customer.contact_customer').':'.(CustomerRepository::getCustomerName($list['customer_id']));
        $calendarData = [
            'calendar_content' => $title,
            'handle_user'      => $reminder,
            'calendar_begin'   => $list['visit_time'],
            'calendar_end'     => date('Y-m-d H:i:s',strtotime('+1 hour ',strtotime($list['visit_time']))),
            'calendar_remark'   => preg_replace('/\[emoji.*?\]/', '', str_replace('&nbsp;','',strip_tags($list['visit_content'])))
        ];
        $relationData = [
            'source_id'        => $list['visit_id'],
            'source_from'      => 'customer-visit',
            'source_title'     => $title,
            'source_params'    => ['visit_id' => $list['visit_id'], 'customer_id' => $list['customer_id']]
        ];
        app($this->calendarService)->emit($calendarData, $relationData, $own['user_id']);
        // 外发到日程模块 --结束--
    }

    public function createReminder(int $visitId, array $reminder)
    {
        if (empty($reminder)) {
            return true;
        }
        $iData = [];
        foreach ($reminder as $userId) {
            $iData[] = [
                'visit_id' => $visitId,
                'user_id'  => $userId,
            ];
        }
        return VisitRepository::insertReminder($iData);
    }

    public function visitLists($params, $own)
    {
        $params = $this->parseParams($params);
        $customerIds = $params['search']['customer_id'] ?? [];
        if (empty($customerIds) || !$validate = CustomerRepository::validatePermission([CustomerRepository::VIEW_MARK], $customerIds, $own)) {
            return ['code' => ['0x024003', 'customer']];
        }
        return $this->response(app($this->visitRepository), 'total', 'lists', $params);
    }

    // 客户列表详情按钮，只展示提醒人是自己的提醒
    public function userVisitLists($customerId, $params, $own)
    {
        $userId = $own['user_id'] ?? 0;
        $params           = $this->parseParams($params);
        if (!$customerId || !$validate = CustomerRepository::validatePermission([CustomerRepository::VIEW_MARK], [$customerId], $own)) {
            return ['code' => ['0x024003', 'customer']];
        }
        $targetVisitIds   = VisitRepository::getVisitIds($customerId, $userId);
        $params['search'] = [
            'customer_id' => [$customerId],
            'visit_id'    => [$targetVisitIds, 'in'],
        ];
        return $this->response(app($this->visitRepository), 'total', 'lists', $params);
    }

    public function showVisit($visitId, $own)
    {
        $customerIds = VisitRepository::getCustomerIds([$visitId]);
        if (!empty($customerIds) && !$validate = CustomerRepository::validatePermission([CustomerRepository::VIEW_MARK], $customerIds, $own)) {
            return ['code' => ['0x024003', 'customer']];
        }
        if ($list = app($this->visitRepository)->getWillVisitDetail($visitId)) {
            $list->visit_type_name = app($this->systemComboboxService)->getComboboxFieldsNameById(VisitRepository::VISIT_TYPE_MARK, $list->visit_type);
            return $list;
        }
        return ['code' => ['already_deleted_visit', 'customer']];
    }

    public function doneWillVisit($data, $own)
    {
        $visit = [
            'customer_id'    => $data['customer_id'] ?? 0,
            'linkman_id'     => $data['linkman_id'] ?? 0,
            'record_start'   => $data['create_time'] ?? date("Y-m-d H:i:s"),
            'record_end'     => date("Y-m-d H:i:s"),
            'record_type'    => $data['visit_type'] ?? 0,
            'record_creator' => $own['user_id'] ?? 0,
            'record_content' => $data['visit_content'] ?? '',
        ];
        $customerId = $data['customer_id'] ?? '';
        if ($customerId && !$validate = CustomerRepository::validatePermission([CustomerRepository::VIEW_MARK], [$customerId], $own)) {
            return ['code' => ['0x024003', 'customer']];
        }
        $visitIds = isset($data['visit_id']) ? (array) $data['visit_id'] : [];
        app($this->contactRecordRepository)->insertData($visit);

        $this->emitCalendarComplete($data['visit_id'], $own['user_id'], 'complete');
        $result = VisitRepository::deleteVisits($visitIds);
        // 全站搜索消息队列更新数据
        $this->updateGlobalSearchWillVisitDataByQueue($visitIds);

        return $result;
    }

    private function emitCalendarComplete($sourceId, $userId, $type = 'update') 
    {
        $relationData = [
            'source_id'        => $sourceId,
            'source_from'      => 'customer-visit'
        ];
       if ($type == 'complete') {
            return app($this->calendarService)->emitComplete($relationData);
        } else if ($type == 'delete') {
            return app($this->calendarService)->emitDelete($relationData, $userId);
        }
        
    }
    public function emitCalendarUpdate($reminder, $data, $userId){
        $data = (array) $data;
        // 外发到日程模块 --开始--
        $title = trans('customer.contact_customer').':'.(CustomerRepository::getCustomerName($data['customer_id']));
        $calendarData = [
            'calendar_content' => $title,
            'handle_user'      => $reminder,
            'calendar_begin'   => $data['visit_time'],
            'calendar_end'     => date('Y-m-d H:i:s',strtotime('+1 hour ',strtotime($data['visit_time']))),
            'calendar_remark'   => preg_replace('/\[emoji.*?\]/', '', str_replace('&nbsp;','',strip_tags($data['visit_content'])))
        ];
        $relationData = [
            'source_id'        => $data['visit_id'],
            'source_from'      => 'customer-visit',
            'source_title'     => $title,
            'source_params'    => ['visit_id' => $data['visit_id'], 'customer_id' => $data['customer_id']]
        ];
        return app($this->calendarService)->emitUpdate($calendarData, $relationData, $userId);
    }

    public function updateWillVisit($visitId, $data, $own)
    {
        $validate = VisitRepository::validateInput($data, $visitId, $own);
        if (isset($validate['code'])) {
            return $validate;
        }
        $reminder = (array) $data['reminder'];
        unset($data['reminder']);
        VisitRepository::deleteReminds([$visitId]);
        $this->createReminder($visitId, $reminder);

        $this->emitCalendarUpdate($reminder, $data, $own['user_id']);
        $result = (bool) app($this->visitRepository)->updateData($data, ['visit_id' => [$visitId]]);
        // 全站搜索消息队列更新数据
        $this->updateGlobalSearchWillVisitDataByQueue($visitId);

        return $result;
    }

    public function updateCalendar($data,$reminder){
        $upData = [
            'calendar_content' => trans('customer.contact_customer').':'.(CustomerRepository::getCustomerName($data['customer_id'])),
            'calendar_begin'   => $data['visit_time'],
            'calendar_end'     => date('Y-m-d H:i:s',strtotime('+1 hour ',strtotime($data['visit_time']))),
            'reminder_time'    => $data['visit_time'],
            'user_id'          => implode(',',$reminder),
            'calendar_remark'  => preg_replace('/\[emoji.*?\]/', '', str_replace('&nbsp;','',strip_tags($data['visit_content']))),
            'repeat'           => 0,
            'remind'           => 1,
            'reminder_timing'  => 5,
            'create_id'        => $data['visit_creator'],
            'repeat_type'      => 1,
        ];
        return app($this->calendarService)->editAllCalendar($data['calendar_id'],$upData,own()['user_id']);
    }

    public function deleteWillVisit($visitIds, $own)
    {
        $visitIds = array_filter(explode(',', $visitIds));
        if (empty($visitIds)) {
            return true;
        }

        $customerIds = VisitRepository::getCustomerIds($visitIds);
        if (!empty($customerIds) && !$validate = CustomerRepository::validatePermission([CustomerRepository::VIEW_MARK], $customerIds, $own)) {
            return ['code' => ['0x024003', 'customer']];
        }

        foreach ($visitIds as $key => $value) {
            $this->emitCalendarComplete($value, $own['user_id'], 'delete');
        }

        $result =  VisitRepository::deleteVisits($visitIds);;

        // 全站搜索消息队列更新数据
        $this->updateGlobalSearchWillVisitDataByQueue($visitIds);

        return $result;
    }

    /**
     * 可以访问该客户的所有用户id
     * @return array
     */
    public function visitCustomerUserIds(int $customerId)
    {
        list($managerId, $serviceId) = app($this->repository)->getColumns(['customer_manager', 'customer_service_manager'], ['customer_id' => $customerId]);
        $result                      = [$managerId, $serviceId];

        $superiors = app($this->userService)->getSuperiorArrayByUserId($managerId);
        if (!empty($superiors) && isset($superiors['id'])) {
            $result = array_merge($result, $superiors['id']);
        }
        list($userIds, $deptIds, $roleIds) = ShareCustomerRepository::getCustomerShareIds($customerId);
        if (!empty($userIds)) {
            $result = array_merge($result, $userIds);
        }
        if (!empty($deptIds)) {
            $deptUserIds = UserRepository::getUserIdsByDeptIds($deptIds);
            $result      = array_merge($result, $deptUserIds);
        }
        if (!empty($roleIds)) {
            $roleUserIds = UserRepository::getUserIdsByRoleIds($deptIds);
            $result      = array_merge($result, $roleUserIds);
        }
        return array_merge(array_unique($result));
    }

    // 申请权限
    public function applyCustomerPermissions(int $customerId, array $data, array $own)
    {
        $userId = $own['user_id'] ?? '';
        if (!$validate = PermissionRepository::validateApplyPermissionInput($data, $customerId, $userId)) {
            return ['code' => ['0x024002', 'customer']];
        }
        $list = app($this->repository)->getDetail($customerId);
        switch ($data['apply_permission']) {
            case self::APPLY_SHARE:
                if ($validate = CustomerRepository::validatePermission([CustomerRepository::UPDATE_MARK], [$customerId], $own)) {
                    return ['code' => ['0x024003', 'customer']];
                }
                break;

            case self::APPLY_SERVICE_MANAGER:
                if ($list->customer_service_manager == $userId) {
                    return ['code' => ['0x024002', 'customer']];
                }
                break;

            case self::APPLY_MANAGER:
                if ($list->customer_manager == $userId) {
                    return ['code' => ['0x024002', 'customer']];
                }
                break;

            default:
                break;
        }
        // 判断是否已经申请
        if ($validate = app($this->permissionRepository)->hasApply($data['apply_permission'], $customerId, $userId)) {
            return ['code' => ['0x024010', 'customer']];
        }
        if (!$result = app($this->permissionRepository)->insertData($data)) {
            return ['code' => ['0x024004', 'customer']];
        }
        // 发送提醒给客户经理
        if ($list->customer_manager) {
            $sendData['remindMark']   = self::APPLY_PERMISSION_MARK;
            $sendData['toUser']       = $list->customer_manager;
            $sendData['stateParams']  = ["apply_id" => $result['apply_id']];
            $sendData['contentParam'] = [
                'customerName' => $list->customer_name,
                'userName'     => $own['user_name'] ?? '',
            ];
            Eoffice::sendMessage($sendData);
        }

        return true;
    }

    public function deleteApplyPermissions(array $applyIds, $own)
    {
        $where = [
            'apply_id' => [$applyIds, 'in'],
//            'apply_status' => [[2, 3], 'in']
        ];
        $lists       = app($this->permissionRepository)->getColumnLists($applyIds);
        if ($lists->isEmpty()) {
            return ['code' => ['0x024011', 'customer']];
        }
        // 只查找自己是具有编辑权限的客户
        $customerIds = CustomerRepository::getUpdateIds($own);
        if ($customerIds !== CustomerRepository::ALL_CUSTOMER) {
            foreach ($lists as $index => $item) {
                if (!in_array($item->customer_id, $customerIds) && $item->proposer != $own['user_id']) {
                    return ['code' => ['0x024003', 'customer']];
                }
            }
        }
        return app($this->permissionRepository)->deleteByWhere($where);
    }

    public function applyAuditLists($params, $own)
    {
        $params = $this->parseParams($params);
        // 只查找自己是具有编辑权限的客户
        $customerIds = CustomerRepository::getUpdateIds($own);
        if ($customerIds !== CustomerRepository::ALL_CUSTOMER) {
            $params['search']['customer_id'] = [$customerIds, 'in'];
        } else {
            // 不查找软删除数据
            $params['search']['softCustomerIds'] = CustomerRepository::getSoftCustomerIds();
        }
        return $this->response(app($this->permissionRepository), 'total', 'lists', $params);
    }

    public function showApplyAudit($applyId, $own)
    {
        // 审核过了就退出
        $result = app($this->permissionRepository)->show($applyId);
        if (empty($result)) {
            return ['code' => ['0x024003', 'customer']];
        }
        $customerIds = CustomerRepository::getUpdateIds($own, [$result->customer_id]);
        if (empty($customerIds)) {
            return ['code' => ['0x024003', 'customer']];
        }
        return $result;
    }

    public function updateApplyAudit($applyIds, $data, $own)
    {
        // 拒绝还是批准
        $status      = isset($data['status']) && $data['status'] ? self::APPLY_STATUS_APPROVE : self::APPLY_STATUS_REFUSE;
        $reason      = $data['audit_reason'] ?? '';
        $messageFlag = isset($data['audit_type']) && $data['audit_type'] ? true : false;
        $applyIds    = array_filter(explode(',', $applyIds));
        $lists       = app($this->permissionRepository)->getColumnLists($applyIds);
        if ($lists->isEmpty()) {
            return ['code' => ['0x024011', 'customer']];
        }
        // 只查找自己是具有编辑权限的客户
        $customerIds = CustomerRepository::getUpdateIds($own);
        if ($customerIds !== CustomerRepository::ALL_CUSTOMER) {
            foreach ($lists as $index => $item) {
                if (!in_array($item->customer_id, $customerIds)) {
                    return ['code' => ['0x024003', 'customer']];
                }
            }
        }
        if (!$result = app($this->permissionRepository)->audit($status, $applyIds, $reason)) {
            return ['code' => ['0x024004', 'customer']];
        }
        // 更改客户资料和分享信息，发送消息
        $sendData = [];
        foreach ($lists as $index => $item) {
            if (!$item->customer_id || !$item->proposer) {
                continue;
            }
            if ($status === self::APPLY_STATUS_APPROVE) {
                switch (intval($item->apply_permission)) {
                    case self::APPLY_SHARE:
                        ShareCustomerRepository::storeShareUsers($item->customer_id, [$item->proposer]);
                        break;

                    case self::APPLY_SERVICE_MANAGER:
                        app($this->repository)->updateData(['customer_service_manager' => $item->proposer], ['customer_id' => [$item->customer_id]]);
                        break;

                    case self::APPLY_MANAGER:
                        app($this->repository)->updateData(['customer_manager' => $item->proposer], ['customer_id' => [$item->customer_id]]);
                        CustomerRepository::updateCustomer($item->customer_id, ['last_distribute_time' => time()]);
                        break;

                    default:
                        # code...
                        break;
                }
            }
            if ($messageFlag) {
                $username                 = $own['user_name'] ?? '';
                $sendData['remindMark']   = $status === self::APPLY_STATUS_APPROVE ? 'customer-pass' : 'customer-refuse';
                $sendData['toUser']       = $item->proposer;
                $sendData['contentParam'] = [
                    'customerName' => $item->applyPermissionToCustomer['customer_name'],
                    'userName'     => $username,
                ];
                Eoffice::sendMessage($sendData);
            }
        }
        return $result;
    }

    /**
     * 转移客户，需要客户编辑权限
     */
    public function transferCustomer(array $input, array $own)
    {
        $customerIds = isset($input['customer_ids']) && $input['customer_ids'] ? array_filter(explode(',', $input['customer_ids'])) : [];
        if (empty($customerIds)) {
            $managerIds  = isset($input['customer_managers']) && $input['customer_managers'] ? array_filter(explode(',', $input['customer_managers'])) : [];
            $customerIds = CustomerRepository::getCustomerIdsByManagerIds($managerIds);
            if (!empty($customerIds)) {
                $customerIds = CustomerRepository::getUpdateIds($own, $customerIds);
            }
        } else {
            if(isset($customerIds[0]) && $customerIds[0] == 'all'){
                $customerIds = [];
                // 处理全部批量更换操作
                $customerIds = CustomerRepository::getUpdateIds($own, $customerIds);
            }else{
                $oldCount = count($customerIds);
                $customerIds = CustomerRepository::getUpdateIds($own, $customerIds);
                if ($oldCount !== count($customerIds)) {
                    return ['code' => ['0x024002', 'customer']];
                }
            }
        }
        $where = ['customer_id' => [$customerIds, 'in']];
        $data  = $sendData  = [];
        $customerCount = count($customerIds);
        if ($customerCount == 1) {
            $customerId = $customerIds[0] ?? '';
            if (!$customerId) {
                return ['code' => ['0x024002', 'customer']];
            }
            list($tCustomerName)      = app($this->repository)->getColumns(['customer_name'], ['customer_id' => $customerIds[0]]);
            $sendData['contentParam'] = [
                'customerName' => $tCustomerName,
            ];
            $sendData['stateParams'] = ['customer_id' => $customerIds[0]];
        }
        if (isset($input['customer_manager'])) {
            $data['customer_manager']     = $input['customer_manager'];
            $data['last_distribute_time'] = time();
            $sendData['remindMark']       = $customerCount == 1 ? 'customer-change' : 'customer-transfer';
            if ($customerCount > 1) {
                $sendData['contentParam'] = ['newCustomerNumber' => $customerCount];
            }
            $sendData['toUser']           = $data['customer_manager'];
            if($customerIds){
                Eoffice::sendMessage($sendData);
            }
        } else if (isset($input['service_manager'])) {
            $data['customer_service_manager'] = $input['service_manager'];
            $sendData['remindMark']           = $customerCount == 1 ? 'customer-service' : 'customer-transfer';
            if ($customerCount > 1) {
                $sendData['contentParam'] = ['newCustomerNumber' => $customerCount];
            }
            $sendData['toUser']               = $data['customer_service_manager'];
            if($customerIds){
                Eoffice::sendMessage($sendData);
            }
        } else if (isset($input['view_permission'])) {
            $deptIds    = isset($input['permission_dept']) ? (array) $input['permission_dept'] : [];
            $userIds    = isset($input['permission_user']) ? (array) $input['permission_user'] : [];
            $roleIds    = isset($input['permission_role']) ? (array) $input['permission_role'] : [];
            $deleteFlag = isset($input['deleteOld']) ? (bool) $input['deleteOld'] : false;
            ShareCustomerRepository::diffInsertDeptIds($customerIds, $deptIds, $deleteFlag);
            ShareCustomerRepository::diffInsertRoleIds($customerIds, $roleIds, $deleteFlag);
            ShareCustomerRepository::diffInsertUserIds($customerIds, $userIds, $deleteFlag);
            // 清理redis缓存
            ShareCustomerRepository::parseTempShareIds($own);
            return true;
        }
        if (empty($data)) {
            return ['code' => ['0x024007', 'customer']];
        }

        $prefixLogContent = trans('customer.designated_customer').'：';
        $customerDatas = CustomerRepository::getCustomerById($customerIds,['customer_name','customer_id','customer_manager','customer_service_manager']);
        // 日志记录
        if($customerDatas){
            foreach ($customerDatas as $key => $customerData){
                $content = $prefixLogContent.$customerData->customer_name.' ';
                if(isset($input['customer_manager'])){
                    $content .= trans('customer.customer_manager').' ';
                    $oName = app($this->userRepository)->getUserName($customerData->customer_manager);
                    $oName = $oName ?: trans('customer.empty');
                    $nName = app($this->userRepository)->getUserName($input['customer_manager']);
                    $nName = $nName ?: trans('customer.empty');
                    $content .= $oName.'->'.$nName;
                }
                if(isset($input['service_manager'])){
                    $content .= trans('customer.customer_service_manager').' ';
                    $oName = app($this->userRepository)->getUserName($customerData->customer_service_manager);
                    $oName = $oName ?: trans('customer.empty');
                    $nName = app($this->userRepository)->getUserName($input['service_manager']);
                    $nName = $nName ?: trans('customer.empty');
                    $content .= $oName.'->'.$nName;
                }
                $identify = 'customer.customer_info.customer_appoint';
                CustomerRepository::saveLogs($customerData->customer_id, $content, $own['user_id'],$identify,$customerData->customer_name);
            }
        }
        return app($this->repository)->updateData($data, $where);
    }

    /**
     * 合并客户详情
     */
    public function showCustomerMerge(array $customerIds, $own)
    {
        $result = [];
        if (empty($customerIds)) {
            $result['list'] = [];
            return $result;
        }
        // 只查找自己是具有编辑权限的客户
        $lastCustoemrIds = CustomerRepository::getUpdateIds($own, $customerIds);
        if (count($customerIds) !== count($lastCustoemrIds)) {
            return ['code' => ['0x024003', 'customer']];
        }
        $input = [
            'search'   => ['customer_id' => [$customerIds, 'in']],
        ];
        $result = app($this->formModelingService)->getCustomDataLists($input, self::CUSTOM_TABLE_KEY, $own);
        return $result;
    }

    /**
     * 合并客户
     */
    public function mergeCustomer($data, $own)
    {
        $userId = $own['user_id'] ?? '';
        $validate = CustomerRepository::validateMergeInput($data, $own);
        if (isset($validate['code'])) {
            return $validate;
        }
        list($primaryId, $teamIds) = $validate;
        $customerIds               = array_merge($teamIds, [$primaryId]);
//        $lists                     = CustomerRepository::getLists($customerIds);
        foreach ($customerIds as $k => $vo){
            $lists[] = app($this->formModelingService)->getCustomDataDetail(self::CUSTOM_TABLE_KEY,$vo,$own);
        }
        if (!$lists) {
            return ['code' => ['0x024011', 'customer']];
        }
        $updateData    = $teamCustomerName    = [];
        $customerName  = '';
        $noMergeFields = ['seas_group_id', 'deleted_at', 'created_at', 'updated_at', 'from_website', 'last_contact_time', 'last_distribute_time', 'customer_creator', 'customer_logo', 'customer_id'];
        foreach ($lists as $index => $item) {
            $item = (array) $item;
            if ($item['customer_id'] == $primaryId) {
//                $item         = array_filter($item);
                $item = self::checkFilterArray($item);
                $item         = array_filter($item);
                $updateData   = array_merge($updateData, $item);
                $customerName = $item['customer_name'] ?? '';
            } else {
                $updateData         = $updateData + $item;
                $teamCustomerName[] = $item['customer_name'] ?? '';
            }
        }
        foreach ($updateData as $key => $value) {
            if (in_array($key, $noMergeFields)) {
                unset($updateData[$key]);
            }
//            $updateData[$key] =  json_decode(json_encode($value), true);
            if(is_object($value)){
                $updateData[$key] =  json_decode(json_encode($value), true);
            }
        }
        // 更新最新的资料
//        CustomerRepository::updateCustomer($primaryId, $updateData);
        app($this->formModelingService)->editCustomData($updateData, self::CUSTOM_TABLE_KEY, $primaryId);
        // 将被合并客户的所有相关联系人等也进行归并
        LinkmanRepository::mergeToCustomer($primaryId, $teamIds);
        ContractRepository::mergeToCustomer($primaryId, $teamIds);
        VisitRepository::mergeToCustomer($primaryId, $teamIds);
        ContactRecordRepository::mergeToCustomer($primaryId, $teamIds);
        SaleRecordRepository::mergeToCustomer($primaryId, $teamIds);
        BusinessChanceRepository::mergeToCustomer($primaryId, $teamIds);
        // 写入日志
        $teamCustomerName = implode(',', $teamCustomerName);
        $identify = 'customer.customer_info.customer_merge';
        CustomerRepository::saveLogs($primaryId, trans('customer.merge') . ':' . $teamCustomerName, $userId,$identify,$teamCustomerName);
        app($this->logService)->updateLog(['log_relation_id' => $primaryId], ['log_relation_table' => 'customer', 'log_relation_id' => [$teamIds, 'in']]);
        foreach ($teamIds as $v) {

            CustomerRepository::saveLogs($v, trans('customer.merge_to') . ':' . $customerName, $userId,$identify,$customerName);
        }
        // 将被合并客户直接物理删除
        $this->deleteRecycleCustomers($teamIds);
        return true;
    }

    private function checkFilterArray(array &$item){
        foreach ($item as $k => $value){
            if(is_array($value)){
                $item[$k] = $this->checkFilterArray($value);
            }
            if((is_null($item[$k]) && $item[$k] == '') ){
                unset($item[$k]);
            }
            if($k == 'customer_area'){
                if(isset($item[$k]['province']) && isset($item[$k]['city']) && $item[$k]['province'] == 0 && $item[$k]['city'] == 0){
                    unset($item[$k]);
                }
            }
            if(is_object($value) && !$value->toArray()){
                unset($item[$k]);
            }
        }
        return $item;
    }

    // 导出客户
    public function exportCustomer($params)
    {
        $own = $params['user_info'];
        return app($this->formModelingService)->exportFields(self::CUSTOM_TABLE_KEY, $params, $own, trans('customer.customer_export_template'));
    }

    // 获取客户导入字段
    public function getImportFields($param)
    {
        return app($this->formModelingService)->getImportFields(self::CUSTOM_TABLE_KEY, $param, trans("customer.customer_import_template"));
    }

    // 导入客户
    public function importCustomer($data, $params)
    {
        app($this->formModelingService)->importCustomData(self::CUSTOM_TABLE_KEY, $data, $params);
        return ['data' => $data];
    }

    // 导入客户过滤，只调用一次
    public function importFilter($importDatas, $params = [])
    {
        global $hasImportName;
        global $hasImportNumber;
        $hasImportName = $hasImportNumber = [];
        // 导入方式，更新或者新增
        $importType = $params['type'] ?? null;
        // 导入更新方式，依赖的主键
        $primaryKey = $params['primaryKey'] ?? null;
        $own        = $params['user_info'] ?? [];
        $customer_name = [];
        // 获取全部的公海
        $seasGroupId = isset($params['params']['seasGroupId']) ? $params['params']['seasGroupId'] : 0;
        $groupIds = DB::table('customer_seas_group')->pluck('id')->toArray();
        // 获取有权限的公海
        list($managerSeasIds, $joinSeasIds) = SeasGroupRepository::getUserGroupSeasIds($own);
        $allGroupIds                        = array_unique(array_merge($managerSeasIds, $joinSeasIds));
        $model = app($this->formModelingService);
        foreach ($importDatas as $index => $data) {
            // 如果是更新，公海id不存在的话则不修改
            if($primaryKey && empty($data['seas_group_id'])){
                unset($importDatas[$index]['seas_group_id']);
            }
            // 如果更新，根据编号更新,优先判断编号是否为空
            if($importType && $importType  == 2){
                if($primaryKey && $primaryKey == 'customer_number'){
                    $number = $data['customer_number'] ?? '';
                    if(!$number){
                        $importDatas[$index]['importReason'] = importDataFail(trans("customer.customer_number_empty"));
                        continue;
                    }
                }
            }
            $importDatas[$index]['customer_creator'] = $params['user_info']['user_id'];
            $importDatas[$index]['importResult'] = importDataFail();
            // 自定义字段验证
            // 导入到公海，判断当前操作人是否有管理 or 参与公海权限
            // 如果是在公海内导入，则不需要判断填写的公海权限
            if(!$seasGroupId){
                if(isset($data['seas_group_id']) && $data['seas_group_id']){
                    if(!in_array($data['seas_group_id'],$groupIds)){
                        $importDatas[$index]['importReason'] = importDataFail(trans('customer.not_seas_group'));
                        continue;
                    }
                    if(!in_array($data['seas_group_id'],$allGroupIds)){
                        $importDatas[$index]['importReason'] = importDataFail(trans('customer.not_import_customer'));
                        continue;
                    }
                }
            }

            if(array_key_exists('seas_group_id',$data) && ($data['seas_group_id'] == '' || $data['seas_group_id'] == null)){
                $importDatas[$index]['seas_group_id'] = 1;
            }
            if($seasGroupId){
                $importDatas[$index]['seas_group_id'] = $seasGroupId;
            }

            // 客户名称编号等验证
            if (($validate = CustomerRepository::validateImportInput($own, $data, $importType, $primaryKey)) !== true) {
                $importDatas[$index]['importReason'] = importDataFail($validate);
                continue;
            }
            $importDatas[$index]['customer_name_py'] = isset($data['customer_name_py']) ? $data['customer_name_py'] : '';
            $importDatas[$index]['customer_name_zm'] = isset($data['customer_name_zm']) ? $data['customer_name_zm'] : '';
            $importDatas[$index]['customer_number'] = isset($data['customer_number']) ? $data['customer_number'] : '';
            $result = $model->importDataFilter(self::CUSTOM_TABLE_KEY, $data, $params);
            if (!empty($result)) {
                $importDatas[$index]['importReason'] = importDataFail($result);
                continue;
            }
            // 导入数据重复验证(未导入之前判断客户名称是否重复)
            // 注释，导入唯一验证走自定义字段
//            if(in_array($data['customer_name'],$customer_name)){
//                $importDatas[$index]['importReason'] = importDataFail(trans('customer.customer_name_repeat'));
//                continue;
//            }
//            $customer_name[] = $data['customer_name'];
            $importDatas[$index]['importResult'] = importDataSuccess();
        }
        return $importDatas;
    }

    /**
     * 导入客户后，一条一条调用
     * 分享创建人，指定客户公海
     */
    public function afterImport($importData)
    {
        $customerData = $importData['data'] ?? [];
        $customerId   = $importData['id'] ?? 0;
        $userId       = isset($customerData['customer_creator']) && !empty($customerData['customer_creator']) ? $customerData['customer_creator'] : ($importData['param']['user_info']['user_id'] ?? '');
        $groupId      = 1;
        if(isset($importData['param']['params']['seasGroupId']) && $importData['param']['params']['seasGroupId']){
            // 公海内导入客户
            $groupId = $importData['param']['params']['seasGroupId'];
        }else{
            if(isset($customerData['seas_group_id']) && $customerData['seas_group_id']){
                // 导入菜单导入客户
                $groupId = $customerData['seas_group_id'];
            }
        }
        $updateData   = [];
        if (!$customerId) {
            return true;
        }
        $userId = $userId ? $userId : own()['user_id'];
        $log_creator = $importData['param']['user_info']['user_id'] ?? '';
        ShareCustomerRepository::storeShareUsers($customerId, [$userId]);
//        $updateData['customer_creator']     = $userId;
        $updateData['created_at']           = date('Y-m-d H:i:s');
        $updateData['seas_group_id']        = $groupId;
        $updateData['last_distribute_time'] = time();
        CustomerRepository::updateCustomer($customerId, $updateData);
        $customerName = isset($importData['data']['customer_name']) ? $importData['data']['customer_name'] : '';

        $content = trans('customer.import');
        $identify = 'customer.customer_info.import';
        if(isset($importData['param']['type']) && $importData['param']['type'] == 2){
            $content = trans('customer.import_update');
        }
        CustomerRepository::saveLogs($customerId, $content.'：'.$customerName, $log_creator,$identify,$customerName);
        // 公海规则分配
        if (!isset($customerData['customer_manager']) || !$customerData['customer_manager']) {
            if (!$validate = SeasGroupRepository::distributeCustomer($groupId, [$customerId])) {
                SeasGroupRepository::updateWaitDistributeCount($groupId);
            }
        }
        return true;
    }

    /**
     * 客户公海用户选择器过滤方法
     * @return array
     */
    public function UserSelectorFilter($params)
    {
        $result = [];
        if (!isset($params['withCustomer']) || (isset($params['withCustomer']['all']) && $params['withCustomer']['all'])) {
            return $result;
        }
        $result  = (array) $params['withCustomer']['user_id'] ?? [];
        $deptIds = (array) $params['withCustomer']['dept_id'] ?? [];
        $roleIds = (array) $params['withCustomer']['role_id'] ?? [];
        if (!empty($deptIds)) {
            $result = array_merge($result, UserRepository::getUserIdsByDeptIds($deptIds));
        }
        if (!empty($roleIds)) {
            $result = array_merge($result, UserRepository::getUserIdsByRoleIds($roleIds));
        }
        return ['user_id' => array_merge(array_unique($result))];
    }

    /**
     * 客户提醒
     */
    public function willVisitReminds($interval)
    {
        $result = [];
        $interval = 1;
        if (!$validate = app($this->systemRemindService)->checkRemindIsOpen(self::REMIND_CUSTOMER_VISIT_MARK)) {
            return $result;
        }
        $lists = app($this->visitRepository)->willVisitRemindLists($interval);
        if (empty($lists)) {
            return $result;
        }
        foreach ($lists as $index => $item) {
            $customerData = $item['will_visit_customer'] ?? [];
            $linkmanData  = $item['will_visit_linkman'] ?? [];
            $remindUser   = $item['has_many_reminder'] ?? [];
            if (empty($customerData) || empty($remindUser)) {
                continue;
            }
            $linkman      = $linkmanData['linkman_name'] ?? '';
            $linkmanPhone = $linkmanData['mobile_phone_number'] ?? '';
            if ($linkmanPhone) {
                $linkman .= '，' . trans('customer.mobile') . '：' . $linkmanPhone;
            }
            $result[] = [
                'remindMark'   => self::REMIND_CUSTOMER_VISIT_MARK,
                'toUser'       => implode(',', array_column($remindUser, 'user_id')),
                'contentParam' => [
                    'customerName' => $customerData['customer_name'],
                    'linkman'      => $linkman,
                    'phone'        => $linkmanPhone,

                ],
                'stateParams'  => [
                    'customer_id' => $customerData['customer_id'],
                    'visit_id'    => $item['visit_id'],
                ],
            ];
        }
        return $result;
    }

    /**
     * 根据公海组id，自动分配
     * @param  array $customerIds 需要分配的客户id,
     * @return [type]              [description]
     */
    public function autoDistribute($seasGroupId, $customerIds, $userId)
    {
        if (!$validate = SeasGroupRepository::validateIsManager($seasGroupId, $userId)) {
            return ['code' => ['0x024003', 'customer']];
        }
        return SeasGroupRepository::distributeCustomer($seasGroupId, $customerIds);
    }

    //项目模块共用了此api，后续可支持通用化
    public function toggleCustomerMenus(string $menuKey, $userId, array $params = [])
    {
        if (!in_array($userId, self::$hasDeletePermissionIds)) {
            return ['code' => ['0x024003', 'customer']];
        }
        if($menuKey && $menuKey == 'customer_share'){
            if($userId != 'admin'){
                return ['code' => ['0x024003', 'customer']];
            }
        }
        $fieldTableKey = Arr::get($params, 'field_table_key', self::CUSTOM_TABLE_KEY);

        return FormModelingRepository::toggleCustomTabMenus($fieldTableKey, $menuKey);
    }

    /**
     * 客户报表相关
     * 未更改，以后考虑重构
     */
    public function getCustomerReportData($sGroupBy, $sAnalysis, $sSearchs)
    {
        $result        = $reportData        = $whereRaw        = [];
        $reportGroupBy = "";
        //创建时间
        if ($sGroupBy == "customerCreateTime") {
            $sDateType                      = $sSearchs['dateType'] ?? '';
            $sDateValue                     = $sSearchs['dateValue'] ?? '';
            $reportData                     = getDatexAxis($sDateType, $sDateValue);
            list($reportGroupBy, $whereRaw) = getDateQuery($sDateType, $sDateValue, 'created_at');
        } else {
            $showFields = [];
            //获得横轴字段
            switch ($sGroupBy) {
                case 'customerType': //客户类型

                    $showFields    = app($this->systemComboboxService)->getComboboxFieldByIdentify('CUSTOMER_TYPE');
                    $reportGroupBy = "customer_type";
                    break;
                case 'customerIndustry': //行业
                    $filterIndustry = !empty($sSearchs['customerIndustry']) ? explode(',', $sSearchs['customerIndustry']) : null;
                    $showFields     = app($this->systemComboboxService)->getComboboxFieldByIdentify('CUSTOMER_TRADE');
                    if (!is_null($filterIndustry)) {
                        foreach ($showFields as $k => $v) {
                            if (!in_array($k, $filterIndustry)) {
                                unset($showFields[$k]);
                            }
                        }
                    }
                    $reportGroupBy = "customer_industry";
                    break;
                case 'customerManager': //客户经理
                    $filterManager = !empty($sSearchs['customerManager']) ? explode(',', $sSearchs['customerManager']) : null;
                    if (!empty($filterManager)) {
                        foreach ($filterManager as $v) {
                            $name           = $this->getRelationName('user', 'user_id', 'user_name', $v);
                            $reportData[$v] = ['name' => $name, 'y' => 0];
                        }
                    }
                    $db_query = DB::select('select customer_manager,user_name from customer,user where customer_manager = user_id group by customer_manager;');
                    foreach ($db_query as $v) {
                        if (!is_null($filterManager) && !in_array($v->customer_manager, $filterManager)) {
                            continue;
                        }
                        $reportData[$v->customer_manager] = ['name' => $v->user_name, 'y' => 0];
                    }
                    $reportGroupBy = "customer_manager";
                    break;
                case 'customerProvince': //省份
                    $filter_province = !empty($sSearchs['customerProvince']) ? explode(',', $sSearchs['customerProvince']) : null;
                    if (is_array($filter_province)) {
                        foreach ($filter_province as $v) {
                            $name           = $this->getRelationName('province', 'province_id', 'province_name', $v);
                            $reportData[$v] = ['name' => $name, 'y' => 0];
                        }
                    }
                    $db_query = DB::select('select province,province_name from  customer,province  where  province  =  province_id   group by province;');
                    foreach ($db_query as $v) {
                        if (!is_null($filter_province) && !in_array($v->province, $filter_province)) {
                            continue;
                        }
                        $reportData[$v->province] = ['name' => mulit_trans_dynamic("province.province_name." . $v->province_name), 'y' => 0];
                    }
                    $reportGroupBy = "province";
                    break;
                case 'customerCity': //城市
                    $filter_city = !empty($sSearchs['customerCity']) ? explode(',', $sSearchs['customerCity']) : null;
                    if (is_array($filter_city)) {
                        foreach ($filter_city as $v) {
                            $name           = $this->getRelationName('city', 'city_id', 'city_name', $v);
                            $reportData[$v] = ['name' => $name, 'y' => 0];
                        }
                    }
                    $db_query = DB::select('select city,city_name from  customer,city  where  city  =  city_id   group by city;');
                    foreach ($db_query as $v) {
                        if (!is_null($filter_city) && !in_array($v->city, $filter_city)) {
                            continue;
                        }
                        $reportData[$v->city] = ['name' => mulit_trans_dynamic("city.city_name." . $v->city_name), 'y' => 0];
                    }
                    $reportGroupBy = "city";
                    break;
                case 'customer_service_manager': //客服经理
                    $filter_service = !empty($sSearchs['customer_service_manager']) ? explode(',', $sSearchs['customer_service_manager']) : null;
                    if (is_array($filter_service)) {
                        foreach ($filter_service as $v) {
                            $name           = $this->getRelationName('user', 'user_id', 'user_name', $v);
                            $reportData[$v] = ['name' => $name, 'y' => 0];
                        }
                    }
                    $db_query = DB::select('select   customer_service_manager,user_name  from  customer,user  where  customer_service_manager  =  user_id   group by customer_service_manager;');
                    foreach ($db_query as $v) {
                        if (!is_null($filter_service) && !in_array($v->customer_service_manager, $filter_service)) {
                            continue;
                        }

                        $reportData[$v->customer_service_manager] = ['name' => $v->user_name, 'y' => 0];
                    }
                    $reportGroupBy = "customer_service_manager";
                    break;
                case 'customer_status': //客户状态
                    $showFields    = app($this->systemComboboxService)->getComboboxFieldByIdentify('COMPANY_SCALE');
                    $reportGroupBy = "customer_status";
                    break;
                case 'customer_from': //客户来源
                    $showFields    = app($this->systemComboboxService)->getComboboxFieldByIdentify('CUSTOMER_SOURCE');
                    $reportGroupBy = "customer_from";
                    break;
                case 'customer_attribute': //客户属性
                    $showFields    = app($this->systemComboboxService)->getComboboxFieldByIdentify('KHSX');
                    $reportGroupBy = "customer_attribute";
                    break;
                case 'scale': //公司规模
                    $showFields    = app($this->systemComboboxService)->getComboboxFieldByIdentify('COMPANY_SCALE1');
                    $reportGroupBy = "scale";
                    break;
                default:
                    break;
            }
            //初始化横轴
            foreach ($showFields as $k => $v) {
                $reportData[$k] = ['name' => $v, 'y' => 0];
            }
            if ($sGroupBy != "customerManager" && $sGroupBy != "customer_service_manager") {
                $reportData['else'] = array("name" => trans("customer.other"), "y" => 0);
            }
        }
        $analysis        = [];
        $find            = "";
        $analysis_origin = ['count' => 'count'];
        //多字段，一次查询，一次完成分析
        foreach ($sAnalysis as $k => $v) {
            $index            = isset($analysis_origin[$k]) ? $analysis_origin[$k] : $k;
            $analysis[$index] = $reportData;
            $find .= ($k == 'count') ? " count(*) as count ," : " sum({$index}) as {$index} ,";
        }
        $db_obj = app($this->repository)->entity->select(DB::raw($find . "" . $reportGroupBy . " as group_by"));
        if (!empty($whereRaw)) {
            $db_obj->whereRaw($whereRaw);
        }

        if (!empty($sSearchs['customerManager'])) {
            $db_obj->whereIn('customer_manager', explode(',', $sSearchs['customerManager']));
        }

        if (!empty($sSearchs['customer_service_manager'])) {
            $db_obj->whereIn('customer_service_manager', explode(',', $sSearchs['customer_service_manager']));
        }

        if (!empty($sSearchs['customerProvince'])) {
            $db_obj->whereIn('province', explode(',', $sSearchs['customerProvince']));
        }

        if (!empty($sSearchs['customerCity'])) {
            $db_obj->whereIn('city', explode(',', $sSearchs['customerCity']));
        }

        if (!empty($sSearchs['customerIndustry'])) {
            $db_obj->whereIn('customer_industry', explode(',', $sSearchs['customerIndustry']));
        }

        if (!empty($sSearchs['customerCreator'])) {
            $db_obj->whereIn('customer_creator', explode(',', $sSearchs['customerCreator']));
        }

        if (!empty($sSearchs['created_at'])) {
            $created_at = explode(',', $sSearchs['created_at']);
            if (isset($created_at[0]) && !empty($created_at[0])) {
                $db_obj->whereRaw("created_at >= '" . $created_at[0] . " 00:00:00'");
            }
            if (isset($created_at[1]) && !empty($created_at[1])) {
                $db_obj->whereRaw("created_at <= '" . $created_at[1] . " 23:59:59'");
            }
        }
        $db_res = $db_obj->groupBy('group_by')->get()->toArray();
        //分析结果
        parseDbRes($db_res, $analysis);
        $name       = ['count' => trans("customer.number")];
        $group_name = ['customerType' => trans("customer.customer_type"),
            'customer_status'             => trans("customer.customer_status"),
            'customer_from'               => trans("customer.customer_source"),
            'customer_attribute'          => trans("customer.client_property"),
            'scale'                       => trans("customer.company_size")];
        $group_name['customerManager']          = trans("customer.customer_manager");
        $group_name['customer_service_manager'] = trans("customer.customer_service_manager");
        $group_name['customerProvince']         = trans("customer.province");
        $group_name['customerCity']             = trans("customer.city");
        $group_name['customerIndustry']         = trans("customer.industry");
        $group_name['customerCreateTime']       = trans("customer.creation_time");
        //返回结果
        foreach ($analysis as $k => $v) {
            $group_by_name = isset($group_name[$sGroupBy]) ? $group_name[$sGroupBy] : "";
            $row           = ['data' => $v, 'name' => $name[$k], 'group_by' => $group_by_name];
            $result[]      = $row;
        }
        return $result;
    }

    public function getRelationName($table, $field, $field_name, $value)
    {
        if (empty($value)) {
            return "";
        }

        $db_query = DB::select("select " . $field_name . " from  " . $table . "  where " . $field . "  = '" . $value . "'");
        $item     = isset($db_query[0]) ? $db_query[0] : array();
        $result   = (is_object($item) && isset($item->$field_name)) ? $item->$field_name : "";
        return $result;
    }

    public function getContractData($sGroupBy, $sAnalysis, $sSearchs)
    {
        $result        = [];
        $analysis      = [];
        $whereRaw      = [];
        $reportData    = [];
        $find          = "";
        $where         = "";
        $reportGroupBy = "";
        $union         = false;
        //合同类型
        if ($sGroupBy == "contractType") {
            $field = app($this->systemComboboxService)->getComboboxFieldByIdentify("AGREEMENT_TYPE");
            foreach ($field as $k => $v) {
                $reportData[$k] = ['name' => $v, 'y' => 0];
            }
            $reportData['else'] = array("name" => trans("customer.other"), "y" => 0);
            $reportGroupBy      = "contract_type";
            $field_str          = "contract_type";
        }
        //合同开始时间
        if ($sGroupBy == "contractStartTime") {
            $reportData    = getDatexAxis($sSearchs['dateType'], $sSearchs['dateValue']);
            $query         = getDateQuery($sSearchs['dateType'], $sSearchs['dateValue'], 'contract_start');
            $reportGroupBy = $query[0];
            $whereRaw      = $query[1];
            $field_str     = "contract_start";
        }
        //合同结束时间
        if ($sGroupBy == "contractEndTime") {
            $reportData    = getDatexAxis($sSearchs['dateType'], $sSearchs['dateValue']);
            $query         = getDateQuery($sSearchs['dateType'], $sSearchs['dateValue'], 'contract_end');
            $reportGroupBy = $query[0];
            $whereRaw      = $query[1];
            $field_str     = "contract_end";
        }
        //合同收付款时间
        if ($sGroupBy == "remind_date") {
            $reportData    = getDatexAxis($sSearchs['dateType'], $sSearchs['dateValue']);
            $query         = getDateQuery($sSearchs['dateType'], $sSearchs['dateValue'], 'remind_date');
            $reportGroupBy = $query[0];
            $whereRaw      = $query[1];
            $field_str     = "remind_date";
            $union         = true;
        }
        //数量
        if (isset($sAnalysis['count'])) {
            $find .= " count(*) as count ,";
            $analysis['count'] = $reportData;
        }
        //合同金额
        if (isset($sAnalysis['contractAmount'])) {
            $find .= " sum(customer_contract.contract_amount) as contract_amount ,";
            $analysis['contract_amount'] = $reportData;
        }
        $table    = "customer_contract";
        $finder   = " select " . $find . $reportGroupBy . " as group_by from ";
        $join     = " ,customer_contract_remind where customer_contract.contract_id=customer_contract_remind.contract_id ";
        $group_by = "  group by group_by order by customer_contract.contract_id desc";
        //filter
        if (!empty($whereRaw)) {
            $where .= ' and ' . $whereRaw;
        }
        // 创建人
        if (!empty($sSearchs['contractCreator'])) {
            // 用户id拼接成字符串
            $sSearchs['contractCreator'] = implode('","', explode(',', $sSearchs['contractCreator']));

            $where .= ' and contract_creator in ("' . $sSearchs['contractCreator'] . '") ';
        }
        if (!empty($sSearchs['customerName'])) {
            $where .= ' and customer_id in (' . $sSearchs['customerName'] . ') ';
        }
        if (!empty($sSearchs['contractDate'])) {
            $contractDate = explode(',', $sSearchs['contractDate']);
            if (isset($contractDate[0]) && !empty($contractDate[0])) {
                $where .= " and contract_start >= '" . $contractDate[0] . "' ";
            }
            if (isset($contractDate[1]) && !empty($contractDate[1])) {
                $where .= " and contract_end <= '" . $contractDate[1] . "' ";
            }
        }
        if (!empty($sSearchs['remind_date'])) {
            $remind_date = explode(',', $sSearchs['remind_date']);
            if (isset($remind_date[0]) && !empty($remind_date[0])) {
                $where .= " and remind_date >= '" . $remind_date[0] . "' ";
            }
            if (isset($remind_date[1]) && !empty($remind_date[1])) {
                $where .= " and remind_date <= '" . $remind_date[1] . "' ";
            }
            if ($sGroupBy != "remind_date") {
                $table = " (select distinct customer_contract.contract_id ,customer_contract.contract_amount ," . $field_str . " from  customer_contract" . $join;
            }
        }
        $finder .= " " . $table . " ";
        if ($union) {
            $finder .= $join;
        }
        if ($sGroupBy == "remind_date") {
            if (isset($analysis['contract_amount'])) {
                unset($analysis['contract_amount']);
            }

            if (isset($analysis['count'])) {
                unset($analysis['count']);
            }

            if (!isset($sAnalysis['count'])) {
                $sAnalysis['payment_amount'] = 'payment_amount';
            } else {
                $sAnalysis['count'] = 'count';
            }
        }
        if (!empty($where)) {
            $finder .= (strstr($finder, "where") === false) ? " where " : " and ";
            $where = trim($where, " and");
            $finder .= $where;
        }
        if ($table != "customer_contract" && $sGroupBy != "remind_date") {
            $finder .= " ) as customer_contract ";
        }

        $finder .= $group_by;
        if ($sGroupBy != "remind_date") {
            $db_res = DB::select($finder);
            parseDbRes($db_res, $analysis);
        }
        //收付款金额
        if (isset($sAnalysis['payment_amount'])) {
            $analysis['payment_amount'] = $reportData;
            $payment                    = "select sum(customer_contract_remind.payment_amount) as payment_amount, " . $reportGroupBy . " as group_by from customer_contract";
            $payment .= $join;
            if (!empty($where)) {
                $payment .= (strstr($payment, "where") === false) ? " where " : " and ";
            }

            $payment .= $where . $group_by;
            $db_res = DB::select($payment);
            parseDbRes($db_res, $analysis);
        } else {
            if ($sGroupBy == "remind_date" && isset($sAnalysis['count'])) {
                $analysis['c_count'] = $reportData;
                $payment             = "select count(distinct(customer_contract.contract_id)) as c_count, " . $reportGroupBy . " as group_by from customer_contract ";
                $payment .= $join;
                if (!empty($where)) {
                    $payment .= (strstr($payment, "where") === false) ? " where " : " and ";
                }

                $payment .= $where . $group_by;
                $db_res = DB::select($payment);
                parseDbRes($db_res, $analysis);
            }
        }
        $analysis_name = ['count' => trans("customer.number"), 'contract_amount' => trans("customer.contract_amount"), 'payment_amount' => trans("customer.amount_of_payment"), 'c_count' => trans("customer.number_of_contracts")];
        $group_name    = ['contractType' => trans("customer.type_of_contract"), 'contractStartTime' => trans("customer.start_of_the_contract"), 'contractEndTime' => trans("customer.contract_end_time"), 'remind_date' => trans("customer.payment_time")];
        foreach ($analysis as $k => $v) {
            $row      = ['data' => $v, 'name' => $analysis_name[$k], 'group_by' => $group_name[$sGroupBy]];
            $result[] = $row;
        }
        return $result;
    }

    public function getBusinessReportData($sGroupBy, $sAnalysis, $sSearchs)
    {
        $result        = [];
        $reportData    = [];
        $whereRaw      = [];
        $reportGroupBy = "";
        //商机结束时间
        if ($sGroupBy == "businessEndTime") {
            $dateFieldName = "deadline";
            $reportData    = getDatexAxis($sSearchs['dateType'], $sSearchs['dateValue']);
            $query         = getDateQuery($sSearchs['dateType'], $sSearchs['dateValue'], $dateFieldName);
            $whereRaw      = $query[1];
            $reportGroupBy = $query[0];
        } else {
            //获得名称
            $field = [];
            //获得横轴字段
            switch ($sGroupBy) {
                //商机类型
                case 'businessType':
                    $field         = app($this->systemComboboxService)->getComboboxFieldByIdentify("BUSINESS_TYPE");
                    $reportGroupBy = "chance_type";
                    break;
                //商机来源
                case 'businessSource':
                    $field         = app($this->systemComboboxService)->getComboboxFieldByIdentify('BUSINESS_SOURCE');
                    $reportGroupBy = "chance_from";
                    break;
                //商机阶段
                case 'businessStep':
                    $field         = app($this->systemComboboxService)->getComboboxFieldByIdentify('BUSINESS_STAGE');
                    $reportGroupBy = "chance_step";
                    break;
                default:
                    break;
            }
            //初始化横轴
            foreach ($field as $k => $v) {
                $reportData[$k] = ['name' => $v, 'y' => 0];
            }
            $reportData['else'] = array("name" => trans("customer.other"), "y" => 0);
        }
        $analysis_origin = ['quote' => 'quoted_price'];
        $analysis        = [];
        $find            = "";
        //多字段，一次查询，一次完成分析
        foreach ($sAnalysis as $k => $v) {
            $index            = isset($analysis_origin[$k]) ? $analysis_origin[$k] : $k;
            $analysis[$index] = $reportData;
            $find .= ($k == 'count') ? " count(*) as count ," : " sum({$index}) as {$index} ,";
        }
        $db_obj = app($this->businessChanceRepository)->entity->select(DB::raw($find . "" . $reportGroupBy . " as group_by"));
        //日期区间
        if (!empty($whereRaw)) {
            $db_obj->whereRaw($whereRaw);
        }

        //所属客户
        //$model = DB::table("customer_contract");
        if (!empty($sSearchs['customerName'])) {
            $db_obj->whereIn('customer_id', explode(',', $sSearchs['customerName']));
        }
        // 商机创建人
        if (!empty($sSearchs['customerCreator'])) {
            $db_obj->whereIn('chance_creator', explode(',', $sSearchs['customerCreator']));
        }
        if (!empty($sSearchs['created_at'])) {
            $created_at = explode(',', $sSearchs['created_at']);
            if (isset($created_at[0]) && !empty($created_at[0])) {
                $db_obj->whereRaw("created_at >= '" . $created_at[0] . " 00:00:00'");
            }
            if (isset($created_at[1]) && !empty($created_at[1])) {
                $db_obj->whereRaw("created_at <= '" . $created_at[1] . " 23:59:59'");
            }
        }
        if (isset($sSearchs['chance_possibility']) && $sSearchs['chance_possibility'] !== "") {
            $tmp = explode(",", $sSearchs['chance_possibility']);
            if (isset($tmp[0]) && $tmp[0] !== "" && round($tmp[0]) >= 0) {
                $db_obj->whereRaw("chance_possibility >='" . round($tmp[0]) . "'");
            }

            if (isset($tmp[1]) && $tmp[1] !== "" && round($tmp[1]) >= 0) {
                $db_obj->whereRaw("chance_possibility <='" . round($tmp[1]) . "'");
            }

        }
        $db_res = $db_obj->groupBy('group_by')->get()->toArray();
        //分析结果
        parseDbRes($db_res, $analysis);
        $name       = ['quoted_price' => trans("customer.suggested_quotation"), 'count' => trans("customer.number")];
        $group_name = ['businessType' => trans("customer.business_opportunity_type"), 'businessSource' => trans("customer.source_of_business_opportunities"), 'businessStep' => trans("customer.business_opportunity_stage"), 'businessEndTime' => trans("customer.end_of_business_opportunity")];
        //返回结果
        foreach ($analysis as $k => $v) {
            $group_by_name = isset($group_name[$sGroupBy]) ? $group_name[$sGroupBy] : "";
            $row           = ['data' => $v, 'name' => $name[$k], 'group_by' => $group_by_name];
            $result[]      = $row;
        }
        return $result;
    }

    public function filterCustomerDetail($data)
    {
        $data = (array)$data;
        if (isset($data['last_contact_time'])) {
            $secondTime = strtotime($data['last_contact_time']);
            if ($secondTime <= 0) {
                $secondTime = isset($data['created_at']) ? strtotime($data['created_at']) : 0;
            }
            $data['last_contact_time'] = $this->parseTimeDate(time() - $secondTime);
        }
        if(isset($data['customer_area']) && $data['customer_area']['province'] == 0 && $data['customer_area']['city'] == 0){
            $data['customer_area']['province'] = null;
            $data['customer_area']['city'] = null;
        }
        return $data;
    }

    // 处理回收站无法查看详情/客户详情权限判断共用
    public function filterBeforeCustomerDetail($customerId){
        $own = own();
        $params = [];
        $customerIds = explode(',',$customerId);
        if($customerIds){
            $ids = CustomerRepository::getViewIds($own, $customerIds, $params);
            // 获取分享客户id
            $own['user_id'] && $ids = array_merge($ids, ShareCustomerRepository::getCustomerIdsByUserId($own['user_id']));
            $own['dept_id'] && $ids = array_merge($ids, ShareCustomerRepository::getCustomerIdsByDeptId($own['dept_id']));
            $own['role_id'] && $ids = array_merge($ids, ShareCustomerRepository::getCustomerIdsByRoleIds($own['role_id']));
            $ids = array_unique($ids);
            if(!in_array($customerId,$ids)){
                return false;
            }
        }
        $param = ['withTrashed' => 1];
        return $param;
    }

    // 自定义字段新增插入数据回调(处理最后分配时间 last_distribute_time 字段)
    public function customerAfterAdd($customerData){
        if($customerData){
            list($customer_name_py, $customer_name_zm) = convert_pinyin($customerData['customer_name']);
            $updateData = [
                'customer_name_py' => $customer_name_py,
                'customer_name_zm' => $customer_name_zm,
                'last_distribute_time' => time()
            ];
            CustomerRepository::updateCustomer($customerData['id'], $updateData);
        }
    }

    public function showPreCustomer($params,$customerId, $own){
        // 获取可见数组id
        $params = $this->parseParams($params);
        $permissionIds = CustomerRepository::getViewIds($own, [], $params);
        if($permissionIds == self::LIST_TYPE_ALL){
            $permissionIds = CustomerRepository::getCustomerIdList($params);
        }
        if(count($permissionIds) > 1){
            sort($permissionIds);
            $key = array_search($customerId,$permissionIds);
            return ['customer_id'=> ($key == 0) ? '' : $permissionIds[$key - 1]];
        }else{
            return ['customer_id'=> ''];
        }

    }

    public function showNextCustomer($params,$customerId, $own){
        // 获取可见数组id
        $params = $this->parseParams($params);
        $permissionIds = CustomerRepository::getViewIds($own, [], $params);
        if($permissionIds == self::LIST_TYPE_ALL){
            $permissionIds = CustomerRepository::getCustomerIdList($params);
        }
        if(count($permissionIds) >1){
            sort($permissionIds);
            $endCustomerId = end($permissionIds);
            $key = array_search($customerId,$permissionIds);
            return ['customer_id'=> ($customerId == $endCustomerId) ? '' : $permissionIds[$key + 1]];
        }else{
            return ['customer_id'=> ''];
        }
    }

    public function recycleCustomersRemind(){
        $recycleRules = SeasGroupRepository::getRecycleDatas();
        if (!empty($recycleRules)) {
            CustomerRepository::recycleCustomersRemind($recycleRules);
        }
        return true;
    }

    public function seasSetting($data,$own){
        if($own['user_id'] != 'admin'){
            return ['code' => ['0x024003', 'customer']];
        }
        $data['user_ids'] = (isset($data['user_ids']) && $data['user_ids']) ? implode(',',$data['user_ids']) : '';
        $data['dept_ids'] = (isset($data['dept_ids']) && $data['dept_ids']) ? implode(',',$data['dept_ids']) : '';
        $data['role_ids'] = (isset($data['role_ids']) && $data['role_ids']) ? implode(',',$data['role_ids']) : '';
        $data['created_at'] = date('Y-m-d H:i:s');
        return SeasGroupRepository::seasSetting($data, $own);
    }

    public function getSetting($params){
        if($data = SeasGroupRepository::getSetting()){
            return $data;
        }
        return [
            'user_ids' => [],
            'dept_ids' => [],
            'role_ids' => [],
            'open_type'=> 0
        ];
    }

    public function updateSetting($data,$id,$own){
        if($own['user_id'] != 'admin'){
            return ['code' => ['0x024003', 'customer']];
        }
        $data['user_ids'] = (isset($data['user_ids']) && $data['user_ids']) ? implode(',',$data['user_ids']) : '';
        $data['dept_ids'] = (isset($data['dept_ids']) && $data['dept_ids']) ? implode(',',$data['dept_ids']) : '';
        $data['role_ids'] = (isset($data['role_ids']) && $data['role_ids']) ? implode(',',$data['role_ids']) : '';
        $data['updated_at'] = date('Y-m-d H:i:s');
        return SeasGroupRepository::updateSetting($data,$id);
    }

    public function cancelShare($customerIds,$input,$own){
        list($result['updateIds'] ) = CustomerRepository::getPermissionIds([CustomerRepository::UPDATE_MARK,], $own, $customerIds);
        foreach ($customerIds as $index => $customer_id) {
            if (!in_array($customer_id, $result['updateIds'])) {
                return ['code' => ['0x024003', 'customer']];
            }
        }
        // 全部撤回
        if(isset($input['all']) && $input['all']){
            ShareCustomerRepository::deleteAllShares($customerIds);
        }
        // 部门指定撤回
        if(isset($input['permission_dept']) && $input['permission_dept']){
            $permission_dept = $input['permission_dept'];
            ShareCustomerRepository::deleteSharesByParams($customerIds,$permission_dept,ShareCustomerRepository::TABLE_DEPT_SHARE);
        }
        // 角色指定撤回
        if(isset($input['permission_role']) && $input['permission_role']){
            $permission_role = $input['permission_role'];
            ShareCustomerRepository::deleteSharesByParams($customerIds,$permission_role,ShareCustomerRepository::TABLE_ROLE_SHARE);
        }
        // 用户指定撤回
        if(isset($input['permission_user']) && $input['permission_user']){
            $permission_user = $input['permission_user'];
            ShareCustomerRepository::deleteSharesByParams($customerIds,$permission_user,ShareCustomerRepository::TABLE_USER_SHARE);
        }
        // 清除缓存
        ShareCustomerRepository::parseTempShareIds($own);
        return true;
    }

    /**
     * 使用消息队列更新全站搜索数据
     *
     * @param   string|int  $id
     */
    public function updateGlobalSearchDataByQueue($ids)
    {
        try {
            ElasticsearchProducer::sendGlobalSearchCustomerMessage($ids);
        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
        }
    }

    /**
     * 使用消息队列更新全站搜索客户提醒数据
     *
     * @param   string|int  $id
     */
    public function updateGlobalSearchWillVisitDataByQueue($ids)
    {
        try {
            ElasticsearchProducer::sendGlobalSearchCustomerWillVisitMessage($ids);
        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
        }
    }

    public function modifyCustomer($input,$own){
        $where = isset($input['search']) ? $input['search'] : [];
        $updateData= isset($input['upData']) ? $input['upData'] : [];
        if($where && $updateData){
            return CustomerRepository::updateDataByOther($where,$updateData);
        }
        return ['code' => ['0x000001','common']];
    }

    // 客户信息外发更新
    public function flowOutUpdate($data){
        if (empty($data) || empty($data['unique_id'])){
            return ['code' => ['0x024002','customer']];
        }
        $own = own();
        $user_id = (isset($data['current_user_id']) && $data['current_user_id']) ? $data['current_user_id'] : $own['user_id'];
        $updateData = $data['data'] ?? [];
        if(isset($updateData['customer_creator']) || isset($updateData['created_at'])){
            return ['code' => ['creator_time_or_creator_not_update','customer']];
        }
        unset($updateData['current_user_id']);
        // 客户编号为空，直接不更新客户编号
        if(empty($updateData['customer_number'])){
            unset($updateData['customer_number']);
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
        $result = $this->update($data['unique_id'], $updateData, $own,true);
        if(isset($result['code'])){
            return $result;
        }
        return [
            'status' => 1,
            'dataForLog' => [
                [
                    'table_to' => CustomerRepository::TABLE_NAME,
                    'field_to' => 'customer_id',
                    'id_to'    => $data['unique_id']
                ]
            ]
        ];
    }

    // 客户信息外发删除
    public function flowOutDelete($data){
        if (empty($data) || empty($data['unique_id'])){
            return ['code' => ['0x024002','customer']];
        }
        $own = own();
        $user_id = $own ? $own['user_id'] : $data['current_user_id'];
        $customerId = explode(',',$data['unique_id']);
        if (!$dataDetail = app($this->repository)->show($data['unique_id'])) {
            return ['code' => ['0x024011', 'customer']];
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

        $result = $this->delete($customerId,$own);
        if(isset($result['code'])){
            return $result;
        }
        return [
            'status' => 1,
            'dataForLog' => [
                [
                    'table_to' => CustomerRepository::TABLE_NAME,
                    'field_to' => 'customer_id',
                    'id_to'    => $data['unique_id']
                ]
            ]
        ];
    }
    
    public function filterLinkmanDetail($linkman_id)
    {
        if (!$validate = LinkmanRepository::validatePermission([CustomerRepository::VIEW_MARK], $linkman_id, own())) {
            return ['code' => ['0x024003', 'customer']];

        }
    }

    // 外发更新数据单独处理
    public static function parseFlowUpdate(&$data,$searchData) : void {
        // 需要处理的字段
        $fields  = ['customer_name','customer_number','seas_group_id','customer_manager','customer_service_manager'];
        array_map(function ($vo) use(&$data,$searchData){
            if(!isset($data[$vo])){
                $data[$vo] = $searchData[$vo];
            }
        },$fields);
    }

    public function weChatList($input,$own){
        $result = app($this->workWechatService)->getWorkWeChatGroupChatListDetail($own);
//        $result ='[{"chat_id":"wrza5cDQAAHArkYiZEJ5pDcP1bP1RvUA","name":"","owner":"DongSiYao","create_time":1603260603,"member_list":[{"userid":"DongSiYao","type":1,"join_time":1603260603,"join_scene":1},{"userid":"wmza5cDQAAXP930PjIbbdwGWo8LWL4lQ","type":2,"join_time":1603260603,"join_scene":1},{"userid":"wmza5cDQAA5IWgEwD3Dqhoz-k_7ry8jQ","type":2,"join_time":1603260603,"join_scene":1}]},{"chat_id":"wrza5cDQAA9uHEfVj3iJaEuTLVLRrmew","name":"aaa","owner":"DongSiYao","create_time":1603099098,"member_list":[{"userid":"DongSiYao","type":1,"join_time":1603099099,"join_scene":1},{"userid":"wmza5cDQAAXP930PjIbbdwGWo8LWL4lQ","type":2,"join_time":1603099099,"join_scene":1},{"userid":"wmza5cDQAA5IWgEwD3Dqhoz-k_7ry8jQ","type":2,"join_time":1603099099,"join_scene":1}]},{"chat_id":"wrza5cDQAAqtAR_I9nEc4arvfhbUg2jg","name":"\u5b89\u500d","owner":"DongSiYao","create_time":1599728680,"member_list":[{"userid":"DongSiYao","type":1,"join_time":1599728680,"join_scene":1},{"userid":"wmza5cDQAA5IWgEwD3Dqhoz-k_7ry8jQ","type":2,"join_time":1599728755,"join_scene":3}]}]';
//        $result = json_decode($result,1);

        if(isset($result['code']) || empty($result)){
            return $result;
        }
        if(isset($input['search'])){
            $input['search'] = json_decode($input['search'],1);
            if(isset($input['search']['customer_name'])){
                $customer_id = app($this->repository)->getCustomerIdByName(['customer_name'=>[$input['search']['customer_name'],'like']]);
                if(!$customer_id){
                    return [];
                }
                $group_chat_id = app($this->repository)->getRelationCustomerId($customer_id);
                if(!$group_chat_id){
                    return [];
                }
                foreach ($result as $key => $vo){
                    if(!in_array($vo['chat_id'],$group_chat_id)){
                        unset($result[$key]);
                    }
                }
            }
            sort($result);
        }
        foreach ($result as $key => $vo){
            $customer_id = CustomerRepository::weChatDataDetail(['group_chat_id'=>$vo['chat_id']]);
            $result[$key]['name'] = $vo['name'] ? $vo['name']  : trans('workwechat.group_chat');
            $result[$key]['customer_id'] = $customer_id ? $customer_id->customer_id : '';
            $result[$key]['is_binged'] = $customer_id ? 1 : 0;
        }
        return ['list'=>$result, 'total' => count($result)];
    }


    public function chatBinding($input, $id, $own){
        if(empty($input['group_chat_id']) || !$id){
            return ['code' => ['0x000001','common']];
        }

        // 检测是否重复绑定
        if(CustomerRepository::weChatDataDetail($input)){
            return ['code' => ['customer_already_binding','customer']];
        };
        return CustomerRepository::chatBindCustomer($input);
    }

    public function cancelChatBinding($where, $own){
        if(!$where){
            return ['code' => ['0x000001','common']];
        }
        return DB::table('customer_relation_wechat')->where($where)->delete();
    }
    public function translateTitle($input,$own){
        $data = [
            'table_key' => $input['tab_key'],
            'key' => $input['key'],
            'foreign_key' => $input['foreign_key'] ? $input['foreign_key'] : '',
            'title_zh' => $input['title_zh'],
        ];
        $search = ['table_key'=>$input['tab_key'],'key'=>$input['key']];
        if(CustomerRepository::checkLabel($search)){
            DB::table('customer_label_translation')->where($search)->update(['title_zh'=>$input['title_zh']]);
        }else{
            DB::table('customer_label_translation')->insert($data);
        }
        return true;
    }

    public function filterCustomerList($data){
        if(isset($data['list']) && $data['list']){
            $nowTime = time();
            foreach ($data['list'] as $key => $item){
                if (isset($item->last_contact_time)) {
                    $secondTime = strtotime($item->last_contact_time);
                    if ($secondTime <= 0) {
                        $secondTime = isset($item->created_at) ? strtotime($item->created_at) : 0;
                    }
                    $item->last_contact_time = $this->parseTimeDate($nowTime - $secondTime);
                }
                $data['list'][$key] = $item;
            }
        }
        return $data;
    }

    public function mapCustomer($input,$own){
        list($km,$customerId) = [$input['km'],$input['customer_id']];
        $result = $originData = app($this->repository)->getDetail($customerId);
        if($km == 0){
            return [$result];
        }
        $params = $this->location_range($result['lng'],$result['lat'],$km);
        $ids = CustomerRepository::getViewIds($own, []);
        return app($this->repository)->customerLists($ids,$params);
    }

    public function labelList($input,$own){
        $params           = $this->parseParams($input);
        $params['search']['creator'] = [$own['user_id']];
        return $this->response(app($this->labelRepository), 'labelListsTotal', 'labelLists', $params);
    }


    public function storeLabel($input,$own){
        $input['creator'] = $own['user_id'];
        return app($this->labelRepository)->insertData($input);
    }


    public function deleteLabel($id,$input,$own){
        if(!$result = app($this->labelRepository)->getDetail($id)){
            return ['code' => ['0x000006', 'common']];
        };
        if($result['creator'] != $own['user_id']){
            return ['code' => ['0x000006', 'common']];
        }
        return app($this->labelRepository)->deleteById($id);
    }

    public function editLabel($id,$input,$own){
        if(!$result = app($this->labelRepository)->getDetail($id)){
            return ['code' => ['0x000006', 'common']];
        };
        if($result['creator'] != $own['user_id']){
            return ['code' => ['0x000006', 'common']];
        }
        return app($this->labelRepository)->updateData($input,['id' => $id]);
    }


    public function relationLabel($customerIds,$input,$own){
        $customerIds = array_filter(explode(',', $customerIds));
        $result = app($this->labelRelationRepository)->getCustomerRelation($customerIds,$input['id']);
        if($result){
            $ids = array_column($result,'customer_id');
            $customerIds = array_diff($customerIds,$ids);
            sort($customerIds);
        }
        if($customerIds){
            $insertData = [];
            foreach ($customerIds as $key => $vo){
                $insertData[] = [
                    'customer_id' => $vo,
                    'label_id' => $input['id'],
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ];
            }
            return app($this->labelRelationRepository)->insertMultipleData($insertData);
        }
        return true;
    }


    /**
     * @param $lng
     * @param $lat
     * @param float $distance 单位：km
     * @return array
     * 根据传入的经纬度，和距离范围，返回所有在距离范围内的经纬度的取值范围
     */
    function location_range($lng, $lat,$distance = 2){
        $earthRadius = 6378.137;//单位km
        $d_lng =  2 * asin(sin($distance / (2 * $earthRadius)) / cos(deg2rad($lat)));
        $d_lng = rad2deg($d_lng);
        $d_lat = $distance/$earthRadius;
        $d_lat = rad2deg($d_lat);
        return [
            'lat_start' => $lat - $d_lat,//纬度开始
            'lat_end' => $lat + $d_lat,//纬度结束
            'lng_start' => $lng-$d_lng,//纬度开始
            'lng_end' => $lng + $d_lng//纬度结束
        ];
    }



}
