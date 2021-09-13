<?php
namespace App\EofficeApp\Customer\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Customer\Entities\CustomerEntity;
use App\EofficeApp\Customer\Repositories\AttentionRepository;
use App\EofficeApp\Customer\Repositories\PermissionGroupRepository;
use App\EofficeApp\Customer\Repositories\SeasGroupRepository;
use App\EofficeApp\Customer\Repositories\ShareCustomerRepository;
use App\EofficeApp\Customer\Repositories\VisitRepository;
use App\EofficeApp\System\Address\Services\AddressService;
use App\EofficeApp\User\Services\UserService;
use DB;
use Eoffice;
use Illuminate\Support\Facades\Redis;
use App\EofficeApp\LogCenter\Facades\LogCenter;

ini_set('memory_limit', '2048M');

class CustomerRepository extends BaseRepository
{

    // 客户的菜单
    const CURRENT_MENU_CODE = 44;

    // 客户表名
    const TABLE_NAME = 'customer';

    // redis 客户编号
    const RS_NUMBER_PREFIX = 'customer:number';

    // 客户编号前缀
    const NUMBER_PREFIX = 'C-NO-';

    // 权限
    const VIEW_MARK = 1;
    const UPDATE_MARK = 2;
    const DELETE_MARK = 4;
    const ATTEND_MARK = 8;
    const VISIT_MARK = 16;
    const RECYCLE_MARK = 32;

    // customer where in 查询超出多大以后需要创建临时表
    const MAX_WHERE_IN = 5000;

    // 软删除客户id数据
    const REDIS_SOFT_CUSTOEMR_IDS = 'customer:had_soft_customers';

    // 具备删除权限的用户id
    public static $hasDeletePermissionUserIds = ['admin'];

    // 所有客户
    const ALL_CUSTOMER = 'all';
    // 回收站标识
    const RECYCLE_STATUS = 0;

    // 客户关注表名
    const ATTENTION_TABLE = 'customer_attention';

    // 用户表名
    const USER_TABLE = 'user';

    // 导入方式
    const IMPORT_TYPE_INSERT = 1;
    const IMPORT_TYPE_UPDATE = 2;

    const CUSTOMER_RECYCLE = 'customer-recycle';

    const CUSTOMER_DISTRIBUTE = 'customer-distribute';
    // 日志关注的需要普通字段修改
    private static $focusFields = ['customer_name', 'customer_number', 'phone_number', 'fax_no', 'website', 'email', 'address', 'zip_code', 'legal_person', 'customer_introduce', 'customer_annual_sales'];

    // 下拉框字段
    private static $customerSelectFieldValue = [
        'customer_type' => 17,
        'customer_from' => 16,
        'customer_status' => 8,
        'scale' => 9,
        'customer_industry' => 10,
        'customer_attribute' => 41,
    ];

    // 日志关注的用户字段
    private static $focusUserFields = ['customer_manager', 'customer_service_manager'];

    public function __construct(CustomerEntity $entity)
    {
        parent::__construct($entity);
        $this->userRepository = 'App\EofficeApp\User\Repositories\UserRepository';
        $this->permissionGroupRepository = 'App\EofficeApp\Customer\Repositories\PermissionGroupRepository';
        $this->permissionGroupRoleRepository = 'App\EofficeApp\Customer\Repositories\PermissionGroupRoleRepository';
        $this->systemComboboxService = 'App\EofficeApp\System\Combobox\Services\SystemComboboxService';
        $this->formModelingService = 'App\EofficeApp\FormModeling\Services\FormModelingService';
    }

    // 保存日志
    public static function saveLogs($id, $content, $userId,$identify,$title)
    {
        logCenter::info($identify,[
            'creator' => $userId,
            'content' => $content,
            'relation_id' => $id,
            'relation_table' => 'customer',
            'relation_title' => $title,
        ]);
//        $data = [
//            'log_creator' => $userId,
//            'log_type' => 'customer',
//            'log_relation_table' => 'customer',
//            'log_relation_id' => $id,
//            'log_content' => $content,
//        ];
//        add_system_log($data);
    }

    public function show($customerId, $params = [])
    {
        $result = $this->entity->select('*')->where('customer_id', $customerId)->with(['customerHasManyAttention.hasOneUser' => function ($query) {
            $query->select(['user_id', 'user_name']);
        }])->first();
        if (empty($result)) {
            return $result;
        }
        $result = $result->toArray();
        $logUserId = self::getLastUpdateLogUserId($customerId);
        list($result['manager_name'],
            $result['service_manager_name'],
            $result['creator_name'],
            $result['updated_user_name']) = self::getUserFieldValueByIds([
            $result['customer_manager'],
            $result['customer_service_manager'],
            $result['customer_creator'],
            $logUserId,
        ]);
        return $result;
    }

    /**
     * 获取用户名，位置一一对应
     * @return array
     */
    public static function getUserFieldValueByIds(array $ids)
    {
        $tCount = count($ids);
        $result = $tempResult = [];
        $lists = DB::table(self::USER_TABLE)->select(['user_id', 'user_name'])->whereIn('user_id', $ids)->get();
        if (!$lists->isEmpty()) {
            foreach ($lists as $index => $item) {
                $tempResult[$item->user_id] = $item->user_name;
            }
            while (($userId = array_shift($ids)) !== null) {
                $result[] = $tempResult[$userId] ?? '';
            }
        }
        return array_pad($result, $tCount, '');
    }

    private function getCustomerChangedLog($field, $from, $changedTo)
    {
        $from = $from ?: trans('customer.empty');
        $changedTo = $changedTo ?: trans('customer.empty');
        $params['search'] = ['field_code' => [[$field], 'in']];
        $fieldLists = app($this->formModelingService)->listCustomFields($params, self::TABLE_NAME);
        $langName = "";
        if ($fieldLists) {
            $fields = array_column($fieldLists, 'field_name');
            $langName = $fields[0] ?? '';
        }
        return $langName . " " . $from . "->" . $changedTo ;
    }

    // 记录客户修改的详细日志
    public function saveUpdateLogs(array $originData, array $inputData, $userId)
    {
        $customerId = $originData['customer_id'] ?? '';
        if (!$customerId) {
            return true;
        }
        $words = trans('customer.changed') . "： ".$originData['customer_name']. " ";
        // 字段修改
        foreach (self::$focusFields as $field) {
            if (!isset($inputData[$field])) {
                continue;
            }
            if ($originData[$field] != $inputData[$field]) {
                $words .= $this->getCustomerChangedLog($field, $originData[$field], $inputData[$field]) . ', ';
            }
        }
        // 下拉框字段修改
        foreach (self::$customerSelectFieldValue as $field => $value) {
            if (!isset($inputData[$field])) {
                continue;
            }
            if ($originData[$field] != $inputData[$field]) {
                $oName = app($this->systemComboboxService)->getComboboxFieldsNameById($value, $originData[$field]);
                $nName = app($this->systemComboboxService)->getComboboxFieldsNameById($value, $inputData[$field]);
                $words .= $this->getCustomerChangedLog($field, $oName, $nName) . ', ';
            }
        }
        // 用户相关字段
        foreach (self::$focusUserFields as $field) {
            if (!isset($inputData[$field])) {
                continue;
            }
            if ($originData[$field] != $inputData[$field]) {
                $oName = app($this->userRepository)->getUserName($originData[$field]);
                $nName = app($this->userRepository)->getUserName($inputData[$field]);
                $words .= $this->getCustomerChangedLog($field, $oName, $nName) . ', ';
            }
        }
        $newProvince = $newCity = '';
        if (isset($inputData['customer_area']) && !empty($inputData['customer_area'])) {
            if(!is_array($inputData['customer_area'])){
                $inputData['customer_area'] = explode(',',$inputData['customer_area']);
            }
            if(count($inputData['customer_area']) === 2){
                list($newProvince, $newCity) = array_values($inputData['customer_area']);
            }
        }
        if (isset($inputData['customer_area']) && $originData['province'] != $newProvince) {
            $oName = AddressService::getProvinceName($originData['province']);
            $nName = AddressService::getProvinceName($newProvince);
            $words .= $this->getCustomerChangedLog('province', $oName, $nName) . ', ';
        }

        if (isset($inputData['customer_area']) && $originData['city'] != $newCity) {
            $oName = AddressService::getCityName($originData['city']);
            $nName = AddressService::getCityName($newCity);
            $words .= $this->getCustomerChangedLog('city', $oName, $nName) . ', ';
        }
        $words = rtrim($words, ', ');
        $identify = 'customer.customer_info.edit';
        return self::saveLogs($customerId, $words, $userId,$identify,$originData['customer_name']);
    }

    // 判断客户名称是否重复
    public static function checkNameRepeat($name, $id = '')
    {
        return self::existNameList($name, $id);
    }

    public static function existNameList($name, $id = '')
    {
        $query = DB::table(self::TABLE_NAME)->where('customer_name', $name);
        if ($id) {
            $query = $query->where('customer_id', '!=', $id);
        }
        return $query->value('customer_id');
    }

    // 判断客户编号是否重复
    public static function checkNumberRepeat($number, $id = '')
    {
        if(!$number){
            return 1;
        };
        $query = DB::table(self::TABLE_NAME)->where('customer_number', $number);
        if ($id) {
            $query = $query->where('customer_id', '!=', $id);
        }
        return $query->value('customer_id');
    }

    // 创建一个新的客户编号
    public static function createNumber()
    {
        $result = 1;
        if (Redis::exists(self::RS_NUMBER_PREFIX)) {
            $result = Redis::incr(self::RS_NUMBER_PREFIX);
        } else {
            $max = DB::table(self::TABLE_NAME)->max('customer_id');
            $max = $max ?? 0;
            Redis::setnx(self::RS_NUMBER_PREFIX, $max);
            $result = Redis::incr(self::RS_NUMBER_PREFIX);
        }
        return str_pad($result, 10, "0", STR_PAD_LEFT);
    }

    // 验证客户数据
    public static function validateInput(array &$input, int $id = 0)
    {
        if (isset($input['last_contact_time'])) {
            unset($input['last_contact_time']);
        }

        // 客户名称重复
        if(isset($input['customer_name']) || array_key_exists('customer_name',$input)){
            $name = $input['customer_name'] ?? '';
            if (!$name) {
                return ['code' => ['0x024028', 'customer']];
            }
            // 客户名称重复验证，由自定义字段完成
//        if ($validate = self::checkNameRepeat($name, $id)) {
//            return ['code' => ['0x024020', 'customer']];
//        }
            if ($name) {
                list($input['customer_name_py'], $input['customer_name_zm']) = convert_pinyin($name);
            }
        }

        if(isset($input['customer_number']) || array_key_exists('customer_number',$input)){
            // 客户编号重复
            $number = $input['customer_number'] ?? '';
            if(!$check = self::checkExists()){
                // 检测是否是第一次注册,生成对应编号
                $number = self::NUMBER_PREFIX . self::createNumber();
            }else{
                if (!$number) {
                    while (!$id && $validate = self::checkNumberRepeat($number)) {
                        $number = self::NUMBER_PREFIX . self::createNumber();
                    }
                }

                if ($number && $validate = self::checkNumberRepeat($number, $id)) {
                    return ['code' => ['0x024021', 'customer']];
                }
            }

            $input['customer_number'] = $number;
        }
        return true;
    }

    public static function checkExists(){
        return DB::table('customer')->first();
    }

    // 验证导入客户数据
    public static function validateImportInput($own, array &$input, int $importType, $primaryKey)
    {
        global $hasImportNumber;
        // 新增
        if ($importType === self::IMPORT_TYPE_UPDATE && !in_array($primaryKey, ['customer_name', 'customer_number'])) {
            return trans('customer.unknown_error');
        }
        $name = $input['customer_name'] ?? '';
        $number = $input['customer_number'] ?? '';
        if ($importType === self::IMPORT_TYPE_INSERT) {
            // 客户名称重复(唯一性验证由自定义字段完成)
//            if ($validate = self::checkNameRepeat($name)) {
//                return trans("customer.customer_name_repeat");
//            }
            if ($name) {
                list($input['customer_name_py'], $input['customer_name_zm']) = convert_pinyin($name);
            }
            // 客户编号重复
//            if (!$number) {
//                $input['customer_number'] = self::NUMBER_PREFIX . self::createNumber();
//            }

            if (!$number) {
                while ($validate = self::checkNumberRepeat($number)) {
                    $number = self::NUMBER_PREFIX . self::createNumber();
                }
            }

            if ($number && $validate = self::checkNumberRepeat($number) || in_array($number, $hasImportNumber)) {
                return trans("customer.customer_number_repeat");
            }
            $input['customer_number'] = $number;
            if (isset($input['created_at']) && !$input['created_at']) {
                $input['created_at'] = date('Y-m-d H:i:s', time());
            }
            $hasImportNumber[] = $number;
        } else {
            // 需要控制权限
            if ($primaryKey == 'customer_name') {
                if (!$customerId = self::existNameList($name)) {
                    return trans("customer.customer_name_no_exist");
                }
                $hasUpdateIds = CustomerRepository::getUpdateIds($own, [$customerId]);
                if (empty($hasUpdateIds)) {
                    return trans("customer.0x024003");
                }
                if (!$number) {
                    $input['customer_number'] = self::NUMBER_PREFIX . self::createNumber();
                }
                if ($number && $validate = self::checkNumberRepeat($number, $customerId) || in_array($number, $hasImportNumber)) {
                    return trans("customer.customer_number_repeat");
                }
                $hasImportNumber[] = $number;
            }
            if ($primaryKey == 'customer_number') {
                if(!$number){
                    return trans("customer.customer_number_empty");
                }
                if (!$customerId = self::checkNumberRepeat($number)) {
                    return trans("customer.customer_number_no_exist");
                }
                $hasUpdateIds = CustomerRepository::getUpdateIds($own, [$customerId]);
                if (empty($hasUpdateIds)) {
                    return trans("customer.0x024003");
                }
//                if ($validate = self::checkNameRepeat($name, $customerId)) {
//                    return trans("customer.customer_name_repeat");
//                }
                if ($name) {
                    list($input['customer_name_py'], $input['customer_name_zm']) = convert_pinyin($name);
                }
            }
        }
        return true;
    }

    public function inMyPermissionGroup($id, $own)
    {
        return true;
    }

    public function sendUpdateMessage($originData, $data, $customerId)
    {
        if (isset($data['customer_manager']) && $originData['customer_manager'] !== $data['customer_manager']) {
            $sendData['remindMark'] = 'customer-change';
            $sendData['toUser'] = $data['customer_manager'];
        }
        if (isset($data['customer_service_manager']) && $originData['customer_service_manager'] !== $data['customer_service_manager']) {
            $sendData['remindMark'] = 'customer-service';
            $sendData['toUser'] = $data['customer_service_manager'];
        }
        if (!isset($sendData['toUser'])) {
            return true;
        }
        $sendData['contentParam'] = ['customerName' => $data['customer_name']];
        $sendData['stateParams'] = ['customer_id' => $customerId];
        Eoffice::sendMessage($sendData);
    }

    public function validateShareCustomers(array &$input)
    {
        $ids = isset($input['ids']) ? (array) $input['ids'] : [];
        $depts = isset($input['depts']) ? (array) $input['depts'] : [];
        $roleIds = isset($input['roles']) ? (array) $input['roles'] : [];
        $userIds = isset($input['users']) ? (array) $input['users'] : [];
        if (empty($ids)) {
            return false;
        }
        $input = [$ids, $depts, $roleIds, $userIds];
        return true;
    }

    public static function getCustomerTabMenus()
    {
        $result[] = [
            'key' => 'customer',
            'isShow' => true,
            'fixed' => true,
            'view' => 'view',
        ];
        $result[] = [
            'key' => 'customer_linkman',
            'isShow' => true,
            'view' => 'linkmans.list',
            'count' => 'customer_has_many_linkman_count',
        ];
        $result[] = [
            'key' => 'customer_business_chance',
            'isShow' => true,
            'view' => 'business-opportunities.list',
            'count' => '',
        ];
        $result[] = [
            'key' => 'customer_contract',
            'isShow' => true,
            'view' => 'contracts.list',
            'count' => 'customer_has_many_contract_count',
        ];
        $result[] = [
            'key' => 'will-visits',
            'isShow' => true,
            'view' => 'will-visits.list',
            'count' => '',
        ];
        $result[] = [
            "key" => "customer_share",
            "isShow" => true,
            "view" => "share.edit",
        ];
        return $result;
    }

    public function getCustomerReportByDate($byDate, $field, array $search = [])
    {
        $query = $this->entity;

        $format = "";

        if ($byDate == 'month') {
            $format = "%Y" . "年" . "%m" . "月";
        } else if ($byDate == 'week') {
            $format = "%Y" . "年" . "%u" . "周";
        } else if ($byDate == 'date') {
            $format = "%Y" . "年" . "%m" . "月" . "%d" . "日";
        }

        $select = "DATE_FORMAT(created_at,'{$format}') AS customer_date";

        $groupBy = ["customer_date"];

        if ($field != 'total') {
            $select .= ",CONCAT(DATE_FORMAT(created_at,'{$format}'),'|',{$field}) AS customer";
            $groupBy[] = $field;
        }

        $select .= ",COUNT(*) AS customer_num";

        $query = $query->selectRaw($select);
        $query = $this->getCustomerParseWhere($query, $search);

        return $query->groupBy($groupBy)->get()->toArray();
    }

    public function getCustomerReportByType($field, array $search = [])
    {
        $sql = $field . ", count(*) as customer_num";

        $query = $this->entity->selectRaw($sql);
        $query = $this->getCustomerParseWhere($query, $search);

        return $query->groupBy($field)->get()->toArray();
    }

    public function getCustomerReportByManager($field, array $search = [])
    {
        $query = $this->entity;

        if ($field == 'total') {
            $sql = "customer_manager AS customer";
            $groupBy = ["customer_manager"];
        } else {
            $sql = "CONCAT(customer_manager,'|',{$field}) AS customer";
            $groupBy = ["customer_manager", $field];
        }

        $sql .= ", COUNT(*) AS customer_num";

        $query = $query->selectRaw($sql);
        $query = $this->getCustomerParseWhere($query, $search);

        return $query->groupBy($groupBy)->get()->toArray();
    }

    public function getCustomerParseWhere($query, array $where = [])
    {
        if (isset($where['permission'])) {
            $query = $query->customerPermission($where['permission']['users'], $where['permission']['userInfo']);
            unset($where['permission']);
        }

        if (isset($where['myAttention'])) {
            $myAttention = $where['myAttention'];
            $query = $query->whereHas('customerHasManyAttention', function ($query) use ($myAttention) {
                $query->wheres($myAttention);
            });

            unset($where['myAttention']);
        }

        if (isset($where['linkman']) || isset($where['linkman_phone'])) {
            $whereLinkman = [];
            if (isset($where['linkman'])) {
                $whereLinkman['linkman_name'] = [$where['linkman'][0], 'like'];
                unset($where['linkman']);
            }

            if (isset($where['linkman_phone'])) {
                $whereLinkman['mobile_phone_number'] = [$where['linkman_phone'][0], 'like'];
                unset($where['linkman_phone']);
            }

            $query = $query->whereHas('customerHasManyLinkman', function ($query) use ($whereLinkman) {
                $query->wheres($whereLinkman);
            });
        }

        if (isset($where['customer_manager_name'])) {
            $query = $query->leftJoin('user', 'user.user_id', '=', 'customer_manager')
                ->wheres(['user.user_name' => $where['customer_manager_name']]);

            unset($where['customer_manager_name']);
        }

        if (isset($where['customer_service_manager_name'])) {
            $query = $query->leftJoin('user as us', 'us.user_id', '=', 'customer_service_manager')
                ->wheres(['us.user_name' => $where['customer_service_manager_name']]);

            unset($where['customer_service_manager_name']);
        }

        if (!empty($where)) {
            foreach ($where as $field => $v) {
                if (strpos($field, 'sub_') !== false) {
                    $subWhere[$field] = $v;
                    unset($where[$field]);
                }
            }
        }

        if (!empty($subWhere) && isset($this->customerSubEntity)) {
            $query = $query->whereHas('subFields', function ($query) use ($subWhere) {
                $query->wheres($subWhere);
            });
        }

        if (isset($where['multiSearch'])) {
            $query = $query->multiWheres($where);
            unset($where['multiSearch']);
        }

        return $query = $query->parseWhere($where, $this->entity->relationFields);
    }

    public function multiSearchIds(array $searchs, $customerIds)
    {
        $query = $this->entity->multiWheres($searchs);
        if (is_array($customerIds)) {
            $query = ShareCustomerRepository::tempTableJoin($query,$customerIds);
        }
        return $query->pluck('customer_id')->toArray();
    }

    public function managerCustomerList(array $param = [])
    {
        $default = [
            'search' => [],
            'page' => 0,
            'limit' => config('eoffice.pagesize'),
            'order_by' => ['customer_manager' => 'DESC'],
        ];

        $param = array_merge($default, array_filter($param));

        $query = $this->entity->select(['customer_manager'])->selectRaw("count('customer_id') as num")->with(['userManager' => function ($query) {
            $query->select(['user_id', 'user_name'])->withTrashed();
        }])->where('customer_manager', '!=', '');
        $query = $this->getManagerCustomerParseWhere($query, $param['search']);
        return $query->groupBy('customer_manager')
            ->orders($param['order_by'])
            ->forPage($param['page'], $param['limit'])
            ->get()
            ->toArray();
    }

    public function managerCustomerTotal(array $param = [])
    {
        $where = isset($param['search']) ? $param['search'] : [];
        return $this->getManagerCustomerParseWhere($this->entity, $where)->where('customer_manager', '!=', '')->distinct('customer_manager')->count('customer_manager');
    }

    public function getManagerCustomerParseWhere($query, array $where = [])
    {
        if (isset($where['manager'])) {
            $searchManager['user_name'] = $where['manager'];
            $query = $query->whereHas('userManager', function ($query) use ($searchManager) {
                $query->wheres($searchManager);
            });
            unset($where['manager']);
        }
        return $query->wheres($where);
    }

    public function getLastUpdateLog($customerId)
    {
        $maxId = DB::table('system_log')->where('log_relation_table', 'customer')->where('log_relation_id', $customerId)->max('log_id');
        return DB::table('system_log')->where('log_id', $maxId)->first();
    }

    public static function getLastUpdateLogUserId($customerId)
    {
        $maxId = DB::table('eo_log_customer')->where('relation_table', 'customer')->where('relation_id', strval($customerId))->max('log_id');
        return DB::table('eo_log_customer')->where('log_id', $maxId)->value('creator');
    }

    public static function updateCustomer($customerId, $data)
    {
        return DB::table(self::TABLE_NAME)->where('customer_id', $customerId)->update($data);
    }

    public static function getCustomerIdsByManagerIds(array $managerIds)
    {
        $result = [];
        $lists = DB::table(self::TABLE_NAME)->select(['customer_id'])->whereIn('customer_manager', $managerIds)->whereNull('deleted_at')->get();
        if (!$lists->isEmpty()) {
            $result = array_column($lists->toArray(), 'customer_id');
        }
        return $result;
    }

    public static function getLists(array $customerIds, array $fields = [])
    {
        $query = DB::table(self::TABLE_NAME);
        if (!empty($fields)) {
            $query = $query->select($fields);
        }
        return $query->whereIn('customer_id', $customerIds)->whereNull('deleted_at')->get();
    }

    public static function validateMergeInput(array &$input, array $own = [])
    {
        $primaryId = $input['primary_customer_id'] ?? '';
        $teamIds = isset($input['sub_customer_id']) && $input['sub_customer_id'] ? explode(',', $input['sub_customer_id']) : [];
        $teamIds = array_filter(array_diff($teamIds, [$primaryId]));
        if (!$primaryId || empty($teamIds)) {
            return ['code' => ['0x024030', 'customer']];
        }
        // 只查找自己是具有编辑权限的客户
        $customerIds = array_merge($teamIds, [$primaryId]);
        $lastCustoemrIds = self::getUpdateIds($own, $customerIds);
        if (count($customerIds) !== count($lastCustoemrIds)) {
            return ['code' => ['0x024003', 'customer']];
        }
        return [$primaryId, $teamIds];
    }

    // 根据标识获取对应的客户id集合
    public static function getPermissionIds(array $marks, array $own, array $customerIds = [], array $params = [])
    {
        $result = [];
        array_map(function ($type) use (&$result, $own, $customerIds, $params) {
            switch ($type) {
                case self::VIEW_MARK:
                    if($params && (isset($params['seasGroupId']) || isset($params['withSimilar']) || isset($params['withApply']))){
                        $result[self::VIEW_MARK] = self::getViewIds($own, $customerIds);
                        break;
                    }
                    // 客户信息列表返回即是有权限查看，直接返回即可
                    $result[self::VIEW_MARK] = $customerIds;
//                    $result[self::VIEW_MARK] = self::getViewIds($own, $customerIds, $params);
                    break;

                case self::UPDATE_MARK:
                    $result[self::UPDATE_MARK] = self::getUpdateIds($own, $customerIds, $params);
                    break;

                case self::ATTEND_MARK:
                    $userId = $own['user_id'] ?? '';
                    $result[self::ATTEND_MARK] = self::getAttentionIds($userId, $customerIds, $params);
                    break;

                case self::VISIT_MARK:
                    $userId = $own['user_id'] ?? '';
                    $result[self::VISIT_MARK] = self::getHasVisitIds($userId, $customerIds, $params);
                    break;
                default:
                    break;
            }
        }, $marks);
        return array_merge($result);
    }

    /**
     * 判断客户查看、编辑、删除权限
     * 返回客户权限位运算
     * @return int
     */
    public static function validatePermission(array $types, array $customerIds, array $own)
    {
        $result = 0;
        if(count($customerIds) == 0){
            return ['code' => ['0x024028', 'customer']];
        }
        array_map(function ($type) use (&$result, $customerIds, $own) {
            switch ($type) {
                case self::VIEW_MARK:
                    $permissionIds = self::getViewIds($own, $customerIds);
                    if (!empty($permissionIds) && empty(array_diff($customerIds, $permissionIds))) {
                        $result = $result | self::VIEW_MARK;
                    }
                    break;

                case self::UPDATE_MARK:
                    $permissionIds = self::getUpdateIds($own, $customerIds);
                    if (!empty($permissionIds) && empty(array_diff($customerIds, $permissionIds))) {
                        $result = $result | self::UPDATE_MARK;
                    }
                    break;

                case self::DELETE_MARK:
                    if (isset($own['user_id']) && in_array($own['user_id'], self::$hasDeletePermissionUserIds)) {
                        $result = $result | self::DELETE_MARK;
                    }
                    break;

                default:
                    # code...
                    break;
            }
        }, $types);
        return $result;
    }

    // 获取可见的客户id
    public static function getViewIds(array $own, array $customerIds = [], array &$params = [],$flag = null)
    {
        $result = $customerIds;
        $userId = $own['user_id'] ?? '';
        $deptId  = $own['dept_id'] ?? '';
        list($managerAllFlag, $serviveAllFlag, $managerIds, $serviceIds) = PermissionGroupRepository::parseGroupLists(self::VIEW_MARK, $own);
        // 表示对所有客户都具有查看权限
        $flagAll = $managerAllFlag && $serviveAllFlag ? true : false;
        $filterParams = [];
        if (isset($params['filter_info'])) {
            $filterParams = json_decode($params['filter_info'], true);
        }
        $exFlag = false;
        if (isset($params['search']) && !empty($params['search'])) {
            list($exFlag, $exCustomerIds) = self::parseFieldSearch($params['search']);
        }
        if (!$flagAll) {
            $managerIds = implode("','", $managerIds);
            $serviceIds = implode("','", $serviceIds);
            $query = DB::table('customer')->select('customer_id');
            $querySql = "";
            $querySql .= $managerAllFlag ? " (customer_manager != '' OR" : "(customer_manager IN ('{$managerIds}') OR";
            $querySql .= $serviveAllFlag ? " customer_service_manager != '')" : " customer_service_manager IN ('{$serviceIds}')) ";
            $queryCustomerIds = $tempIds = [];
            if (!empty($filterParams)) {
                // 先过滤取出部分更利于区分减少结果集的filter
                $needDeleteKeys = ['myAttention', 'visit_time', 'last_contact_time', 'tag_id', 'customer_manager',
                    'customer_service_manager', 'customer_creator', 'created_at', 'z_customer_type'];
                if (!empty(array_intersect(array_keys($filterParams), $needDeleteKeys))) {
                    $queryCustomerIds = self::filterCustomerParam($filterParams, $userId, []);
                    $filterCustomerIds = implode("','", $queryCustomerIds);
//                    $querySql .= " AND customer_id IN ('{$filterCustomerIds}')";
                }
            }
            // 暂时去掉 whereNull查询,等待确认
            $query = $query->whereRaw($querySql);
            if($flag){
                $query = $query->whereNull('deleted_at');
            }
//            $query = $query->whereRaw($querySql)->whereNull('deleted_at');
            if (isset($params['search']) && !empty($params['search'])) {
                $query = app('App\EofficeApp\System\CustomFields\Repositories\FieldsRepository')->wheres($query, $params['search']);
                $tempIds = $query->pluck('customer_id')->toArray();
            } else {
                $lists = DB::select($query->toSql());
                $tempIds = array_column((array) $lists, 'customer_id');
                $queryCustomerIds && $tempIds = array_intersect($tempIds,$queryCustomerIds);
            }
            // 获取共享客户id
            $tempIds = array_merge($tempIds, ShareCustomerRepository::getShareCustomerIds($own, $params));
            $tempIds = array_flip($tempIds);
            $tempIds = array_flip($tempIds);
            $result = empty($result) ? $tempIds : array_intersect($result, $tempIds);
            if (empty($result)) {
                return array_merge($result);
            }
        }

        // 过滤filter_info条件
        if (!empty($filterParams)) {
            $filterCustomerIds = self::filterCustomerParam($filterParams, $userId, $result);
            $result = $flagAll ? $filterCustomerIds : array_merge(array_intersect($result, $filterCustomerIds));
        } else if ($flagAll) {
            $result = !empty($customerIds) ? array_merge($result) : self::ALL_CUSTOMER;
        }
        if ($exFlag) {
            $result = $result === self::ALL_CUSTOMER ? $exCustomerIds : array_intersect($result, $exCustomerIds);
        }
        return $result === self::ALL_CUSTOMER ? self::ALL_CUSTOMER : array_merge($result);
    }

    public static function getViewIdsByCustomer(array $own, array $customerIds = []){
        $result = $customerIds;
        $customerIds = implode(',',$customerIds);
        $userId = $own['user_id'] ?? '';
        list($managerAllFlag, $serviveAllFlag, $managerIds, $serviceIds) = PermissionGroupRepository::parseGroupLists(self::VIEW_MARK, $own);
        // 表示对所有客户都具有查看权限
        $flagAll = $managerAllFlag && $serviveAllFlag ? true : false;
        if (!$flagAll) {
            $managerIds = implode("','", $managerIds);
            $serviceIds = implode("','", $serviceIds);
            $query = DB::table('customer')->select('customer_id');
            $querySql = "";
            $querySql .= $managerAllFlag ? " (customer_manager != '' OR" : "(customer_manager IN ('{$managerIds}') OR";
            $querySql .= $serviveAllFlag ? " customer_service_manager != '')" : " customer_service_manager IN ('{$serviceIds}')) ";$tempIds = [];
            $querySql .= " AND customer_id IN (' {$customerIds}')";
            $query = $query->whereRaw($querySql);
            $tempIds = DB::select($query->toSql());
            $tempIds = array_column((array) $tempIds, 'customer_id');
            // 获取共享客户id
            $tempShareIds = ShareCustomerRepository::getShareIdByCustomerIds(ShareCustomerRepository::TABLE_USER_SHARE, $result);
            $tempShareIds = array_merge($tempShareIds,ShareCustomerRepository::getShareIdByCustomerIds(ShareCustomerRepository::TABLE_DEPT_SHARE, $result));
            $tempShareIds = array_merge($tempShareIds,ShareCustomerRepository::getShareIdByCustomerIds(ShareCustomerRepository::TABLE_ROLE_SHARE, $result));
            $tempShareIds = array_unique($tempShareIds);
            return  array_merge($tempIds,$tempShareIds);
        }
    }

    public static function parseFieldSearch(&$params)
    {
        $result = [];
        $exFlag = false;
        if (isset($params['customer_manager_name'])) {
            $customerName = isset($params['customer_manager_name'][0]) ? $params['customer_manager_name'][0] : '';
            unset($params['customer_manager_name']);
            if ($customerName !== '') {
                $exFlag = true;
                $userLists = DB::table('user')->select(['user_id'])->where('user_name', 'like', '%' . $customerName . '%')->get();
                $tempUserIdArr = [];
                if (!$userLists->isEmpty()) {
                    foreach ($userLists as $key => $item) {
                        $tempUserIdArr[] = $item->user_id;
                    }
                    $tempCustomerLists = DB::table('customer')->select(['customer_id'])->whereIn('customer_manager', $tempUserIdArr)->get();
                    foreach ($tempCustomerLists as $key => $item) {
                        $result[] = $item->customer_id;
                    }
                }
            }
        }
        if (isset($params['customer_service_manager_name'])) {
            $customerName = isset($params['customer_service_manager_name'][0]) ? $params['customer_service_manager_name'][0] : '';
            unset($params['customer_service_manager_name']);
            if ($customerName !== '') {
                $exFlag = true;
                $userLists = DB::table('user')->select(['user_id'])->where('user_name', 'like', '%' . $customerName . '%')->get();
                $tempUserIdArr = [];
                if (!$userLists->isEmpty()) {
                    foreach ($userLists as $key => $item) {
                        $tempUserIdArr[] = $item->user_id;
                    }
                    $tempCustomerLists = DB::table('customer')->select(['customer_id'])->whereIn('customer_service_manager', $tempUserIdArr)->get();
                    foreach ($tempCustomerLists as $key => $item) {
                        $result[] = $item->customer_id;
                    }
                }
            }
        }
        if (isset($params['linkman_name'])) {
            $linkmanName = isset($params['linkman_name'][0]) ? $params['linkman_name'][0] : '';
            unset($params['linkman_name']);
            $own = own();
            $query = DB::table('customer_linkman');
            if ($linkmanName !== '') {
                $exFlag = true;
                if($own['dept_id'] || $own['user_id'] || $own['role_id']){
                    $query = $query->where(function ($query) use($own,$linkmanName){
                        $query->where('linkman_name', 'like', '%' . $linkmanName . '%');
                        $query->where(function ($query) use($own,$linkmanName){
                            $query->orWhereRaw('FIND_IN_SET(?, user_ids)', [$own['user_id']])->orWhereRaw('FIND_IN_SET(?,dept_ids)', [$own['dept_id']]);
                            if($own['role_id'] && is_array($own['role_id'])){
                                foreach($own['role_id'] as $roleId){
                                    $query->orWhereRaw('FIND_IN_SET(? ,role_ids)',[$roleId]);
                                }
                            }
                        });
                    });
                }
                $query = $query->orWhere(function ($query) use($own,$linkmanName){
                    $query->where('linkman_name', 'like', '%' . $linkmanName . '%');
                    $query->where(function ($query) use($own,$linkmanName){
                        $query->orWhere(['is_all'=>1,'linkman_creator'=>$own['user_id']]);
                    });
                });
                $linkmanLists = $query->pluck('customer_id')->toArray();
                if($linkmanLists){
                    $result = array_unique($linkmanLists);
                    sort($result);
                }
//                $linkmanLists = DB::table('customer_linkman')->select(['customer_id'])->where('linkman_name', 'like', '%' . $linkmanName . '%')->get();
//                if (!$linkmanLists->isEmpty()) {
//                    foreach ($linkmanLists as $key => $item) {
//                        $result[] = $item->customer_id;
//                    }
//                    $result = array_unique($result);
//                    sort($result);
//                }
            }
        }
        if (isset($params['linkman_phone'])) {
            $linkmanPhone = isset($params['linkman_phone'][0]) ? $params['linkman_phone'][0] : '';
            unset($params['linkman_phone']);

            $own = own();
            $query = DB::table('customer_linkman');
            if ($linkmanPhone !== '') {
                $exFlag = true;
                if ($own['dept_id'] || $own['user_id'] || $own['role_id']) {
                    $query = $query->where(function ($query) use ($own, $linkmanPhone) {
                        $query->where('mobile_phone_number', 'like', '%' . $linkmanPhone . '%');
                        $query->where(function ($query) use ($own, $linkmanPhone) {
                            $query->orWhereRaw('FIND_IN_SET(?,user_ids)', [$own['user_id']])->orWhereRaw('FIND_IN_SET(?,dept_ids)', [$own['dept_id']]);
                            if ($own['role_id'] && is_array($own['role_id'])) {
                                foreach ($own['role_id'] as $roleId) {
                                    $query->orWhereRaw('FIND_IN_SET(?,role_ids)', [$roleId]);
                                }
                            }
                        });
                    });
                }
                $query = $query->orWhere(function ($query) use ($own, $linkmanPhone) {
                    $query->where('mobile_phone_number', 'like', '%' . $linkmanPhone . '%');
                    $query->where(function ($query) use ($own, $linkmanPhone) {
                        $query->orWhere(['is_all' => 1, 'linkman_creator' => $own['user_id']]);
                    });
                });
                $linkmanLists = $query->pluck('customer_id')->toArray();
                if ($linkmanLists) {
                    $result = array_unique($linkmanLists);
                    sort($result);
                }
            }
//                $linkmanLists = DB::table('customer_linkman')->select(['customer_id'])->where('mobile_phone_number', 'like', '%' . $linkmanPhone . '%')->get();
//                if (!$linkmanLists->isEmpty()) {
//                    foreach ($linkmanLists as $key => $item) {
//                        $result[] = $item->customer_id;
//                    }
//                    $result = array_unique($result);
//                    sort($result);
//                }
        }
        if(isset($params['customer_creator'])){
            $customerCreator = (isset($params['customer_creator'][0]) && (isset($params['customer_creator'][1]) && $params['customer_creator'][1] != 'in')) ? $params['customer_creator'][0] : '';
            if($customerCreator !== ''){
                unset($params['customer_creator']);
                $exFlag = true;
                if($userLists = DB::table('user')->where('user_name', 'like', '%' . $customerCreator . '%')->pluck('user_id')->toArray()){
                    $result = DB::table('customer')->whereIn('customer_creator', $userLists)->pluck('customer_id')->toArray();
                }
            }
        }
        return [$exFlag, $result];
    }

    // 获取客户公海的客户列表可见的客户id集合
    public static function getSeasViewIds($groupId, array $own, array $customerIds = [], array &$params = [])
    {
        $result = $customerIds;
        $userId = $own['user_id'] ?? '';
        if (!$isManager = SeasGroupRepository::hasManagerPermission($groupId, $userId)) {
            // 如果不是管理员，那就查找出客户经理为空或客户经理是自己的客户id集合
            $lists = DB::table('customer')->select(['customer_id'])->where('seas_group_id', $groupId)->whereIn('customer_manager', [$userId, ''])->whereNull('deleted_at')->get();
            $result = array_column($lists->toArray(), 'customer_id');
        }
        $exFlag = false;
        if (isset($params['search']) && !empty($params['search'])) {
            list($exFlag, $exCustomerIds) = self::parseFieldSearch($params['search']);
        }
        // 过滤filter_info条件
        if (isset($params['filter_info'])) {
            $filterParams = json_decode($params['filter_info'], true);
            $filterCustomerIds = self::filterCustomerParam($filterParams, $userId, $result, $groupId);
            $result = $isManager ? $filterCustomerIds : array_intersect($result, $filterCustomerIds);
        } else if ($isManager) {
            $result = !empty($customerIds) ? $result : self::ALL_CUSTOMER;
        }
        if ($exFlag) {
            $result = $result === self::ALL_CUSTOMER ? $exCustomerIds : array_intersect($result, $exCustomerIds);
        }
        return $result;
    }

    // 获取大公海的客户列表可见的客户id集合
    public static function getAllSeasViewIds(array $own, array $customerIds = [], array &$params = []){
        $result = $customerIds;
        $userId = $own['user_id'] ?? '';
        // 后端验证大公海权限
        if(self::checkPublicSeas($own)){
            return ['code'=>['no_private_public_seas','customer']];
        }
        list($managerSeasIds, $joinSeasIds) = SeasGroupRepository::getUserGroupSeasIds($own);
        $allGroupIds                        = array_unique(array_merge($managerSeasIds, $joinSeasIds));
        if (empty($allGroupIds)) {
            return $result;
        }
        $result = DB::table('customer')->whereNull('deleted_at')->whereIn('seas_group_id',$allGroupIds)->pluck('customer_id')->toArray();
        $exFlag = false;
        if (isset($params['search']) && !empty($params['search'])) {
            list($exFlag, $exCustomerIds) = self::parseFieldSearch($params['search']);
        }
        // 过滤filter_info条件
        if (isset($params['filter_info'])) {
            $filterParams = json_decode($params['filter_info'], true);
            $filterCustomerIds = self::filterCustomerParam($filterParams, $userId, $result);
            $result = array_intersect($result, $filterCustomerIds);
        }
        if ($exFlag) {
            $result = $result === self::ALL_CUSTOMER ? $exCustomerIds : array_intersect($result, $exCustomerIds);
        }
        return $result;
    }

    public static function checkPublicSeas($own){
        $flagUsers = $flagDepts = $flagRoles = true;
        if(!$private = SeasGroupRepository::getSetting()){
            return ['code'=>['no_private_public_seas','customer']];
        }
        $userIds = $private->user_ids ? explode(',',$private->user_ids) : [];
        $deptIds = $private->dept_ids ? explode(',',$private->dept_ids) : [];
        $roleIds = $private->role_ids ? explode(',',$private->role_ids) : [];
        if($private->open_type == 0){
            if($userIds && !in_array($own['user_id'],$userIds)){
                $flagUsers = false;
            }
            if($deptIds && !in_array($own['dept_id'],$deptIds)){
                $flagDepts = false;
            }
            if($roleIds && !array_intersect($roleIds,$own['role_id'])){
                $flagRoles = false;
            }
            if(!$flagUsers && !$flagDepts && !$flagRoles){
                return ['code'=>['no_private_public_seas','customer']];
            }
        }

    }

    // 获取可编辑的客户id
    public static function getUpdateIds($own, array $customerIds = [], array &$params = [])
    {
        $lists = $result = [];
        list($managerAllFlag, $serviveAllFlag, $managerIds, $serviceIds) = PermissionGroupRepository::parseGroupLists(self::UPDATE_MARK, $own);
        // 表示对所有客户都具有权限
        $flagAll = $managerAllFlag && $serviveAllFlag ? true : false;
        $exFlag = false;
        if (isset($params['search']) && !empty($params['search'])) {
            list($exFlag, $exCustomerIds) = self::parseFieldSearch($params['search']);
        }
        // 传入的customer_id不为空
        if (!empty($customerIds)) {
            
//            $customerIds = implode("','", $customerIds);
//            $sql = "SELECT customer_id, customer_manager, customer_service_manager  FROM customer WHERE customer_id IN ('{$customerIds}') AND deleted_at is null";
//            $lists = DB::select($sql);
            // 上文代码会被sql注入，修改为以下sql调用方式。
            $lists = DB::table('customer')->select(['customer_id', 'customer_manager', 'customer_service_manager'])->whereIn('customer_id', $customerIds)->get();

            if (empty($lists)) {
                return $result;
            }
            foreach ($lists as $index => $item) {
                if (($managerAllFlag && $item->customer_manager) || ($serviveAllFlag && $item->customer_service_manager)) {
                    $result[] = $item->customer_id;
                    continue;
                }
                if (in_array($item->customer_manager, $managerIds) || in_array($item->customer_service_manager, $serviceIds)) {
                    $result[] = $item->customer_id;
                    continue;
                }
            }
            return $result;
        }
        $managerIds = implode("','", $managerIds);
        $serviceIds = implode("','", $serviceIds);
        $sql = "SELECT customer_id FROM customer WHERE (customer_manager";
        $sql .= $managerAllFlag ? " != '' OR customer_service_manager" : " IN ('{$managerIds}') OR customer_service_manager";
        $sql .= $serviveAllFlag ? " != '')" : " IN ('{$serviceIds}'))";
        $sql .= " AND deleted_at is null";
        $lists = DB::select($sql);
        $result = array_column((array) $lists, 'customer_id');
        // 过滤filter_info条件
        if (isset($params['filter_info'])) {
            $filterParams = json_decode($params['filter_info'], true);
            $filterCustomerIds = self::filterCustomerParam($filterParams, $userId, $result);
            $result = $flagAll ? $filterCustomerIds : array_intersect($result, $filterCustomerIds);
        } else if ($flagAll) {
            $result = self::ALL_CUSTOMER;
        }
        if ($exFlag) {
            $result = $result === self::ALL_CUSTOMER ? $exCustomerIds : array_intersect($result, $exCustomerIds);
        }
        return $result;
    }

    // 获取已经关注的客户id集合
    public static function getAttentionIds($userId, array $customerIds = [], array $params = [])
    {
        return AttentionRepository::getAttentionIds($userId, $customerIds);
    }

    // 获取已经设置的提醒的客户id集合
    public static function getHasVisitIds($userId, array $customerIds = [], array $params = [])
    {
        return VisitRepository::getHasVisitIds($userId, $customerIds);
    }

    // 客户列表方法filter过滤
    public static function filterCustomerParam(array &$filter, $userId, $customerIds, $groupId = null)
    {
        if (empty($filter) || empty($userId)) {
            return [];
        }
        /**
         * 以下将所有的原始sql查询模式修改为sql查询构造器模式。
         * 用于防止sql注入，未变动原来的业务逻辑。
         */
        // static $sql = '';
        $sql = null;
        if (isset($filter['myAttention']) && !$sql) {
            $sql = DB::table('customer_attention')->select(['customer_id'])->where('user_id', $userId);
            // $sql = "SELECT customer_id FROM customer_attention WHERE user_id = '{$userId}'";
        }
        if (isset($filter['z_customer_type'][0])) {
            $tarValue = $filter['z_customer_type'][0];
            $sql = DB::table('customer')->select(['customer_id'])->where('customer_type', $tarValue);
            if ($groupId !== null) {
                $sql = $sql->where('seas_group_id', $groupId);
            }
//            $sql = "SELECT customer_id FROM customer WHERE customer_type = '{$tarValue}' AND deleted_at is null";
//            if ($groupId !== null) {
//                $sql .= " AND seas_group_id = '{$groupId}'";
//            }
        }
        if (isset($filter['visit_time'][0]) && count($filter['visit_time'][0]) === 2 && !$sql) {
            $sql = DB::table('customer_will_visit')->select(['customer_id'])->whereBetween('visit_time', $filter['visit_time'][0]);
//            list($tarValueA, $tarValueB) = $filter['visit_time'][0];
//            $sql = "SELECT customer_id FROM customer_will_visit WHERE visit_time BETWEEN '{$tarValueA}' AND '{$tarValueB}'";
        }
        
        if (!empty($filter['last_contact_time'][1]) && !$sql) {
            list($tarValueA, $tarValueB) = $filter['last_contact_time'];
            if ($tarValueB == '>=') {
                $sql = DB::table('customer_contact_record')->select(['customer_id'])->where('record_start', '>=', $tarValueA);
                //$sql = "SELECT customer_id FROM customer_contact_record WHERE record_start >= '{$tarValueA}'";
            } else {
                // 包含了子查询
                $sql = DB::table('customer')->select(['customer_id'])->whereNotIn('customer_id', function($query) use($tarValueA) {
                    $query->select('customer_id') 
                    ->from('customer_contact_record') 
                    ->where('record_start', '>=', $tarValueA); 
                });
                if ($groupId !== null) {
                    $sql = $sql->where('seas_group_id', $groupId);
                }
//                $sql = "SELECT customer_id FROM customer WHERE customer_id NOT IN (SELECT customer_id FROM customer_contact_record WHERE record_start >= '{$tarValueA}') AND deleted_at is null";
//                if ($groupId !== null) {
//                    $sql .= " AND seas_group_id = '{$groupId}'";
//                }
            }
        }
        if (isset($filter['created_at'][0]) && !$sql) {
            $tarValue = $filter['created_at'][0];
            $sql = DB::table('customer')->select(['customer_id'])->where('created_at', '>=', $tarValue);
            if ($groupId !== null) {
                $sql = $sql->where('seas_group_id', $groupId);
            }
//            $sql = "SELECT customer_id FROM customer WHERE created_at >= '{$tarValue}' AND deleted_at is null";
//            if ($groupId !== null) {
//                $sql .= " AND seas_group_id = '{$groupId}'";
//            }
        }
        if (isset($filter['tag_id'][0]) && !$sql) {
            $tarValue = $filter['tag_id'][0];
            $sql = DB::table('customer_tag')->select(['customer_id'])->where('tag_id', $tarValue)->where('user_id', $userId);
//            $sql = "SELECT customer_id FROM customer_tag WHERE tag_id = '{$tarValue}' AND user_id = '{$userId}'";
        }
        if (array_intersect(['customer_manager', 'customer_service_manager', 'customer_creator'], array_keys($filter)) && !$sql) {
            $tarValueA = array_keys($filter)[0];
            $tarValueB = $filter[$tarValueA][0] ?? '';
            $sql = DB::table('customer')->select(['customer_id'])->where($tarValueA, $tarValueB);
            if ($groupId !== null) {
                $sql = $sql->where('seas_group_id', $groupId);
            }
//            $sql = "SELECT customer_id FROM customer WHERE {$tarValueA} = '{$tarValueB}' AND deleted_at is null";
//            if ($groupId !== null) {
//                $sql .= " AND seas_group_id = '{$groupId}'";
//            }
        }
        // 我的下属
        if (isset($filter['customer_subordinates']) && !$sql) {
            $userIds = UserService::getUserSubordinateIds([$userId], false);
            $userIds = array_diff($userIds, [$userId]);
            if (!empty($userIds)) {
                $sql = DB::table('customer')->select(['customer_id'])->where(function($query) use($userIds) {
                    $query->whereIn('customer_manager', $userIds)->orWhereIn('customer_service_manager',$userIds);
                });
                if ($groupId !== null) {
                    $sql = $sql->where('seas_group_id', $groupId);
                }
//                $sUserIds = implode("','", array_filter($userIds));
//                $sql = "SELECT customer_id FROM customer WHERE (customer_manager IN ('{$sUserIds}') OR customer_service_manager IN ('{$sUserIds}')) AND deleted_at is null";
//                if ($groupId !== null) {
//                    $sql .= " AND seas_group_id = '{$groupId}'";
//                }
            }
        }
        if (isset($filter['i_pick']) && !$sql) {
            $sql = DB::table('customer_user_pick')->select(['customer_id'])->where('user_id', $userId);
//            $sql = "SELECT customer_id FROM customer_user_pick WHERE user_id = '{$userId}'";
        }

        if(isset($filter['id']) && !$sql){
            $sql = DB::table('customer_label_relation')->select(['customer_id'])->where('label_id', $filter['id'][0]);
//            $sql = "SELECT customer_id FROM customer_label_relation WHERE label_id = '{$filter['id'][0]}' AND deleted_at is null";
        }

        if ($sql) {
            if (!empty($customerIds) && count($customerIds) <= self::MAX_WHERE_IN) {
                $sql = $sql->whereIn('customer_id', $customerIds);
//                $customerIds = implode("','", $customerIds);
//                $sql .= " AND customer_id IN ('{$customerIds}')";
            }
            $lists = $sql->get()->toArray();
            if(count($lists) > 0) {
                return array_column($lists, 'customer_id');
            }
//            $lists = (array) DB::select($sql);
//            $result = array_column($lists, 'customer_id');
        }
        return [];
    }

    // 回收客户
    public static function recycleCustomers(array $recycleRules)
    {
        $groupIds = array_keys($recycleRules);
        $updatePickUserIds = [];
        DB::table('customer')->select(['customer_id', 'seas_group_id', 'customer_manager', 'last_distribute_time','created_at','customer_name'])
            ->where('customer_manager', '!=', '')->whereIn('seas_group_id', $groupIds)
            ->where('last_distribute_time', '>', 0)
            ->whereNull('deleted_at')->orderBy('customer_id')
            ->chunk(2000, function ($lists) use ($recycleRules, &$updatePickUserIds) {
            $nowTime = time();
            // 需要回收的客户id
            $targetIds = [];
            $collectData = [];
            // 需要回收的客户公海id
            foreach ($lists as $key => $item) {
                // 获取客户后没新增联系记录，回收客户
                $tempRTime = $recycleRules[$item->seas_group_id]['recycle_record'];
                $limitMonths = $recycleRules[$item->seas_group_id]['limit_months'];
                // 不满足回收限制的月份则跳出循环
                if($limitMonths && $item->created_at){
                    if($limitMonths > $item->created_at){
                        continue;
                    }
                }
                if ($tempRTime > 0) {
                    $exist = DB::table('customer_contact_record')->where([
                        'customer_id' => $item->customer_id,
                        'record_creator' => $item->customer_manager,
                    ])->exists();
                    // 符合回收规则
                    if (!$exist && ($item->last_distribute_time + $tempRTime) < $nowTime) {
                        $targetIds[$item->customer_id] = $item->customer_manager;
                        $collectData[] = [
                            'customer_id'     => $item->customer_id,
                            'customer_manager'=> $item->customer_manager,
                            'customer_name'   => $item->customer_name,
                            'seas_group_id'   => $item->seas_group_id,
                            'created_at'      => $item->created_at,
                        ];
                        if (!isset($updatePickUserIds[$item->seas_group_id][$item->customer_manager])) {
                            $updatePickUserIds[$item->seas_group_id][$item->customer_manager] = [];
                        }
                        $updatePickUserIds[$item->seas_group_id][$item->customer_manager][] = $item->customer_id;
                        continue;
                    }
                }
                // 设置了没新增联系记录，回收客户
                $tempFTime = $recycleRules[$item->seas_group_id]['recycle_prev_follow'];
                if ($tempFTime > 0) {
                    $maxCTime = DB::table('customer_contact_record')->where([
                        'customer_id' => $item->customer_id,
                        'record_creator' => $item->customer_manager,
                    ])->max('created_at');
                    $maxCTime = (strtotime($maxCTime) > $item->last_distribute_time) ? strtotime($maxCTime) : $item->last_distribute_time;
                    if (!$maxCTime || ($maxCTime + $tempFTime) > $nowTime) {
                        continue;
                    }
                    $targetIds[$item->customer_id] = $item->customer_manager;
                    $collectData[] = [
                        'customer_id'     => $item->customer_id,
                        'customer_manager'=> $item->customer_manager,
                        'customer_name'   => $item->customer_name,
                        'seas_group_id'   => $item->seas_group_id,
                        'created_at'      => $item->created_at,
                    ];
                    if (!isset($updatePickUserIds[$item->seas_group_id][$item->customer_manager])) {
                        $updatePickUserIds[$item->seas_group_id][$item->customer_manager] = [];
                    }
                    $updatePickUserIds[$item->seas_group_id][$item->customer_manager][] = $item->customer_id;
                }
            }
            if (empty($targetIds)) {
                return true;
            }
            $recycleDatas = [];
                // 记录回收表
                foreach ($collectData as $key => $value){

                    $recycleDatas[] = [
                        'customer_id'   => $value['customer_id'],
                        'user_id'       => $value['customer_manager'],
                        'recycle_time'  => $nowTime,
                        'remark'        => trans('customer.system_recycle_customer').':'.$value['customer_name'],
                    ];
                    // 记录日志
                    $identify = 'customer.customer_info.customer_auto_recover';
                    CustomerRepository::saveLogs($value['customer_id'], trans('customer.responsible_customers').':'.$value['customer_name'], $value['customer_manager'],$identify,$value['customer_name']);
                    // 回收时发生提醒给客户经理，提示已被回收
                    $sendMessages = [
                        'remindMark'   => self::CUSTOMER_RECYCLE,
                        'toUser'       => $value['customer_manager'],
                        'contentParam' => [
                            'customer_name' => $value['customer_name'].','.trans('customer.has_been_recycled'),
                        ],
                        'stateParams'  => [
                            'customer_id' => $value['customer_id'],
                        ],
                    ];
                    Eoffice::sendMessage($sendMessages);
            }
            DB::table('customer_seas_recycle')->insert($recycleDatas);
            $customerIds = array_keys($targetIds);
            DB::table(self::TABLE_NAME)->whereIn('customer_id', $customerIds)->update(['customer_manager' => '', 'last_distribute_time' => 0]);
            // 退回公海的客户，同时删除掉分享的数据
            ShareCustomerRepository::deleteAllShares($customerIds);
        });
        // 被回收之后可以继续捡起
        if (!empty($updatePickUserIds)) {
            foreach ($updatePickUserIds as $groupId => $userCustomers) {
                if (empty($userCustomers)) {
                    continue;
                }
                foreach ($userCustomers as $userId => $customerIds) {
                    if (empty($customerIds)) {
                        continue;
                    }
                    SeasGroupRepository::refreshUserPickUps($groupId, $customerIds);
                    SeasGroupRepository::updateUserCanPickUps($groupId, $userId);
                }
            }
        }
        return true;
    }

    public static function refreshLastContactTime(int $customerId, $lastTime)
    {
        return DB::table(self::TABLE_NAME)->where('customer_id', $customerId)->update(['last_contact_time' => $lastTime]);
    }

    public static function getSoftCustomerIds()
    {
        if (!Redis::exists(self::REDIS_SOFT_CUSTOEMR_IDS)) {
            self::refreshRedisSoftCustomers();
        }
        return explode(',', Redis::get(self::REDIS_SOFT_CUSTOEMR_IDS));
    }

    public static function refreshRedisSoftCustomers()
    {
        $customerIds = [];
        $softLists = DB::table(self::TABLE_NAME)->whereNotNull('deleted_at')->pluck('customer_id');
        if ($softLists->isEmpty()) {
            array_push($customerIds, 0);
        } else {
            $customerIds = $softLists->toArray();
        }
        $saveIds = implode(',', $customerIds);
        Redis::set(self::REDIS_SOFT_CUSTOEMR_IDS, $saveIds);
    }

    public static function getManagerId(int $customerId)
    {
        return DB::table(self::TABLE_NAME)->where('customer_id', $customerId)->value('customer_manager');
    }


    // 根据客户id获取客户表某个字段
    public static function getCustomerById($customerId,$fields)
    {
        if(is_array($customerId)){
            return DB::table(self::TABLE_NAME)->select($fields)->whereIn('customer_id',$customerId)->get();
        }else{
            return DB::table(self::TABLE_NAME)->select($fields)->where('customer_id',$customerId)->first();
        }

    }
    // 回收客户提醒
    public static function recycleCustomersRemind($recycleRules){
        $groupIds = array_keys($recycleRules);
        $collectData =  $sendMessages = [];
        $nowTime = time();
        DB::table('customer')->select(['customer_id','customer_name', 'seas_group_id', 'customer_manager', 'last_distribute_time','created_at','last_contact_time'])
            ->where('customer_manager', '!=', '')->whereIn('seas_group_id', $groupIds)
            ->where('last_distribute_time', '>', 0)
            ->whereNull('deleted_at')->orderBy('customer_id')
            ->chunk(2000,function ($lists) use($recycleRules,$nowTime,&$collectData,&$sendMessages){
                if($lists){
                    foreach ($lists as $key => $vo){
                        $tempRecord   = $recycleRules[$vo->seas_group_id]['recycle_record'];
                        $remindsTimes = $recycleRules[$vo->seas_group_id]['reminds'];
                        $limitMonths  = $recycleRules[$vo->seas_group_id]['limit_months'];
                        // 收集已分配，但是没有联系的客户给客户经理进行推送
//                        self::collectUncontactedData($vo);
                        
                        // 设置了提前提醒天数
                        if($remindsTimes > 0){
                            // 不满足回收限制的月份则跳出循环
                            if($limitMonths && $vo->created_at){
                                if($limitMonths > $vo->created_at){
                                    continue;
                                }
                            }
                            if($tempRecord > 0){
                                $exist = DB::table('customer_contact_record')->where([
                                    'customer_id' => $vo->customer_id,
                                    'record_creator' => $vo->customer_manager,
                                ])->exists();
                                if(!$exist){
                                    if($tempRecord > $remindsTimes){
                                        if(($nowTime - $vo->last_distribute_time) > $tempRecord - $remindsTimes){
                                            self::getContentParam($vo,$nowTime,$tempRecord);
                                        }
                                        continue;
                                    }
                                }
                            }
                            $tempFollowRecord = $recycleRules[$vo->seas_group_id]['recycle_prev_follow'];
                            if($tempFollowRecord){
                                $maxCTime = DB::table('customer_contact_record')->where([
                                    'customer_id' => $vo->customer_id,
                                    'record_creator' => $vo->customer_manager,
                                ])->max('created_at');
                                if($maxCTime){
                                    $maxCTime = (strtotime($maxCTime) > $vo->last_distribute_time) ? strtotime($maxCTime) : $vo->last_distribute_time;
                                    if($tempFollowRecord > $remindsTimes){
                                        if($nowTime - $maxCTime > $tempFollowRecord - $remindsTimes){
                                            self::getContentParam($vo,$nowTime,$tempFollowRecord);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            });
    }

    public static function collectUncontactedData($vo){
        $linkMans = '';
        $contentParam = $vo->customer_name;
        $exist = DB::table('customer_contact_record')->where([
            'customer_id' => $vo->customer_id,
            'record_creator' => $vo->customer_manager,
        ])->exists();
        if(!$exist){
            $linkData = DB::table('customer_linkman')->where(['customer_id'=>$vo->customer_id,'main_linkman'=>1])->first();

            if($linkData){
                $linkMans = $linkData->mobile_phone_number ? ($linkData->linkman_name.','.$linkData->mobile_phone_number) : $linkData->linkman_name;

            }else{
                $linkData = DB::table('customer_linkman')->where('customer_id',$vo->customer_id)->where('main_linkman','!=',1)->first();
                if($linkData){
                    $linkMans = $linkData->mobile_phone_number ? ($linkData->linkman_name.','.$linkData->mobile_phone_number) : $linkData->linkman_name;
                }
            }
            $contentParam .= $linkMans ? ','.$linkMans : '';
            $contentParam .= trans('customer.distribute_no_contract');
            $returnData = [
                'remindMark'   => self::CUSTOMER_DISTRIBUTE,
                'toUser'       => $vo->customer_manager,
                'contentParam' => [
                    'customer_name' => $contentParam,
                ],
                'stateParams'  => [
                    'customer_id' => $vo->customer_id,
                ],
            ];
            Eoffice::sendMessage($returnData);
        }
    }

    public static function getContentParam($vo,$nowTime,$tempRecord){
        $lastDays = $linkMans = $noDayContactTime = '';
        $willDays = 0;
        $noContactMonths = 0;
        $contentParam = $vo->customer_name;

        // 计算未联系天数/ 月数
        if(isset($vo->last_contact_time)){
            $secondTime = strtotime($vo->last_contact_time);
            if ($secondTime <= 0) {
                $secondTime = isset($vo->created_at) ? strtotime($vo->created_at) : 0;
            }
            $time = $nowTime - $secondTime;
            // 计算未联系月份
            $noContactMonths = $time / (30*24*60*60);
            // 如果没超过一个月
            $lastDays = (!$time || $time <= 86400) ? '0' : floor($time / 86400);
                $lastDays = ($lastDays > 0) ? $lastDays : 0;
                // 将要回收天数
                $days = $tempRecord/86400;
                if($lastDays < $days){
                    $willDays = $days - $lastDays;
                }
        }
        $linkData = DB::table('customer_linkman')->where(['customer_id'=>$vo->customer_id,'main_linkman'=>1])->first();
        if($linkData){
            $linkMans = $linkData->linkman_name.','.$linkData->mobile_phone_number;
        }else{
            $linkData = DB::table('customer_linkman')->select(['linkman_name','mobile_phone_number','main_linkman'])->where('customer_id',$vo->customer_id)->where('main_linkman','!=',1)
                ->get()->toArray();
            if($linkData && count($linkData) > 0){
                $linkMans = $linkData[0]->linkman_name.','.$linkData[0]->mobile_phone_number;
            }
        }
        if($noContactMonths > 1){
            $contentParam .= ','.trans('customer.beyond').intval($noContactMonths).trans('customer.month_no_contract');
            $contentParam .= $willDays ? (','.$willDays.trans('customer.system_auto_recycle')) : ','.trans('customer.system_recycle');
        }else{
            $contentParam .= ','.$lastDays.trans('customer.one_month_no_contract');
            $contentParam .= $willDays ? (','.$willDays.trans('customer.system_auto_recycle')) : ','.trans('customer.system_recycle');
        }

        $contentParam .= $linkMans ? ','.$linkMans : '';
        $sendMessages = [
            'remindMark'   => self::CUSTOMER_RECYCLE,
            'toUser'       => $vo->customer_manager,
            'contentParam' => [
                'customer_name' => $contentParam,
            ],
            'stateParams'  => [
                'customer_id' => $vo->customer_id,
            ],
        ];
        Eoffice::sendMessage($sendMessages);
    }

    public static function getCustomerIdList($params){
        $query = DB::table(self::TABLE_NAME);
        $querySql =  "(customer_manager != '' or customer_service_manager != '')";
        $query = $query->whereRaw($querySql);
        return app('App\EofficeApp\System\CustomFields\Repositories\FieldsRepository')->wheres($query, $params['search'])->offset(0)->limit(1000)->pluck('customer_id')->toArray();
    }

    public static function getCustomerName($customer_id){
        return DB::table(self::TABLE_NAME)->where('customer_id',$customer_id)->value('customer_name');
    }

    public static function updateDataByOther($where, $data)
    {
        return DB::table(self::TABLE_NAME)->where($where)->update($data);
    }

    public static function weChatDataDetail($where){
        return DB::table('customer_relation_wechat')->where($where)->first();
    }

    public static function chatBindCustomer($data){
        return DB::table('customer_relation_wechat')->insert($data);
    }

    public function getCustomerIdByName($params){
        return $this->entity->wheres($params)->pluck('customer_id')->toArray();
    }

    public function getRelationCustomerId($customer_id){
        $query = DB::table('customer_relation_wechat');
        if (count($customer_id) > 2000) {
            $tableName = 'customer_'.rand() . uniqid();
            DB::statement("CREATE TEMPORARY TABLE if not exists {$tableName} (`data_id` int(6) NOT NULL,PRIMARY KEY (`data_id`))");
            $tempIds = array_chunk($customer_id, 2000, true);
            foreach ($tempIds as $key => $item) {
                $ids      = implode("),(", $item);
                $tSql = "insert into {$tableName} (data_id) values ({$ids});";
                DB::insert($tSql);
            }
            $query = $query->join("$tableName", $tableName . ".data_id", '=', 'customer_id');
        }else {
            $query = $query->whereIn('customer_id',$customer_id);
        }
        return $query->pluck('group_chat_id')->toArray();
    }
    
    public static function checkLabel($search){
        return DB::table('customer_label_translation')->where($search)->first();
    }


    public function customerLists($ids,$params){
        $default = [
            'fields'   => ['*'],
            'page'     => 0,
            'limit'    => config('eoffice.pagesize'),
            'search'   => [],
            'order_by' => ['assets_applys.created_at' => 'desc'],
        ];
        $params = array_merge($default, array_filter($params));

        $query = $this->entity->select($params['fields'])->whereBetween('lng',[$params['lng_start'],$params['lng_end']])->whereBetween('lat',[$params['lat_start'],$params['lat_end']]);
        if(is_array($ids)){
            $query = $query->whereIn('customer_id',$ids);
        }
        return $query->parsePage($params['page'], $params['limit'])->get()->toArray();
    }

}
