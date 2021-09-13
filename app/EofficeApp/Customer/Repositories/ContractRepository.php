<?php
namespace App\EofficeApp\Customer\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Customer\Entities\ContractEntity;
use App\EofficeApp\Customer\Repositories\CustomerRepository;
use DB;
use Eoffice;

class ContractRepository extends BaseRepository
{

    const TABLE_REMIND = 'customer_contract_remind';

    const DEFAULT_REMIND_DATE = 1;
    // 表名
    const TABLE_NAME = 'customer_contract';

    private $customerRepository;

    public function __construct(ContractEntity $entity)
    {
        parent::__construct($entity);
        $this->customerRepository = 'App\EofficeApp\Customer\Repositories\CustomerRepository';
        $this->calendarService    = 'App\EofficeApp\Calendar\Services\CalendarService';
    }

    public function lists(array $params)
    {
        $default = [
            'fields'   => ['contract_id', 'contract_name', 'contract_number', 'customer_id', 'contract_creator', 'first_party_signature_date', 'second_party_signature_date', 'contract_amount', 'contract_end', 'created_at'],
            'search'   => [],
            'page'     => 0,
            'limit'    => config('eoffice.pagesize'),
            'order_by' => ['contract_id' => 'desc'],
        ];
        $params = array_merge($default, array_filter($params));
        $query  = $this->entity->select($params['fields'])->with(['customer' => function ($query) {
            $query->select(['customer_id', 'customer_name', 'customer_manager']);
        }]);
        $query = $this->tempTableJoin($query, $params['search']);
        return $query->wheres($params['search'])->orders($params['order_by'])->parsePage($params['page'], $params['limit'])->get()->toArray();
    }

    public function total(array $params)
    {
        $search = isset($params['search']) ? $params['search'] : [];
        $query = $this->entity;
        $query = $this->tempTableJoin($query, $search);
        return $query->wheres($search)->count();
    }

    // sql 太长导致mysql gone away
    public function tempTableJoin($query, &$searchs)
    {
        $whereValues = $searchs['customer_id'][0] ?? [];
        if (!empty($whereValues) && is_array($whereValues) && count($whereValues) > CustomerRepository::MAX_WHERE_IN) {
            $tableName = 'contract_remind'.rand() . uniqid();
            DB::statement("CREATE TEMPORARY TABLE if not exists {$tableName} (`data_id` int(6) NOT NULL,PRIMARY KEY (`data_id`))");
            $tempIds = array_chunk($whereValues, CustomerRepository::MAX_WHERE_IN, true);
            foreach ($tempIds as $key => $item) {
                $ids      = implode("),(", $item);
                $tSql = "insert into {$tableName} (data_id) values ({$ids});";
                DB::insert($tSql);
            }
            $query = $query->join("$tableName", $tableName . ".data_id", '=', 'customer_id');
            unset($searchs['customer_id']);
        }
        return $query;
    }

    public function show($id)
    {
        return $this->entity->with(['remind' => function ($query) {
            $query->select(['contract_remind_id', 'contract_id', 'remind_content', 'remind_date', 'payment_amount']);
        }])->with(['customer' => function ($query) {
            $query->select(['customer_id', 'customer_name', 'customer_manager']);
        }])->with(['supplier' => function ($query) {
            $query->select(['supplier_id', 'supplier_name']);
        }])->with(['user' => function ($query) {
            $query->select(['user_id', 'user_name']);
        }])->find($id);
    }

    /**
     * 新增和修改得数据验证
     * @param $flag 默认新增
     */
    public static function validateInput(array &$input, string $userId, bool $flag = false, array $own = [])
    {
        $input['contract_creator'] = $input['contract_creator'] ?? $userId;
        if (!isset($input['contract_name']) || !$input['contract_name']) {
            return ['code' => ['0x024025', 'customer']];
        }
        $customerId = $input['customer_id'] ?? '';
        if ($customerId && !$validate = CustomerRepository::validatePermission([CustomerRepository::VIEW_MARK], [$customerId], $own)) {
            return ['code' => ['0x024003', 'customer']];
        }
        // list($input['contract_name_py'], $input['contract_name_zm']) = convert_pinyin($input['contract_name']);
        // if (!isset($input['customer_id']) || !$input['customer_id']) {
        //     return ['code' => ['0x024026', 'customer']];
        // }
        // if (isset($input['time'])) {
        //     if (count($input['time']) === 2) {
        //         list($input['contract_start'], $input['contract_end']) = array_values($input['time']);
        //     }
        //     unset($input['time']);
        // }
        // unset($input['contract_creator_name']);
        // // 编辑
        // if ($flag) {
        //     unset($input['customer']);
        //     unset($input['user']);
        //     unset($input['supplier']);
        // }
        return true;
    }

    /**
     * 发送合同提醒
     */
    public function sendRemindMessage(array $data, int $contractId, string $userId)
    {
        $remindDate = self::getConfigRemindDate();
        $sendTime   = isset($data['contract_end']) && strtotime($data['contract_end']) > 0 ? strtotime($data['contract_end']) - 3600 * 24 * $remindDate : 0;
        if (!$sendTime || $sendTime > time()) {
            return false;
        }
        $customerId = $data['customer_id'] ?? '';
        if (!$customerId) {
            return false;
        }
        list($cId, $cName, $cManagerId) = app($this->customerRepository)->getColumns(['customer_id', 'customer_name', 'customer_manager'], ['customer_id' => $customerId]);
        $sendData                       = [
            'toUser'       => $cManagerId === $userId ? $userId : $userId . ',' . $cManagerId,
            'remindMark'   => 'customer-end',
            'contentParam' => [
                'customerName'    => $cName,
                'contractName'    => $data['contract_name'],
                'contractEndTime' => $data['contract_end'],
            ],
            'stateParams'  => ['contract_id' => $contractId],
        ];
        Eoffice::sendMessage($sendData);
    }

    private function truncateReminds(int $contractId)
    {
        return DB::table(self::TABLE_REMIND)->where('contract_id', $contractId)->delete();
    }

    public static function getPermissionIds(array $types, $own, $ids = [])
    {
        $result = [];
        $query  = DB::table(self::TABLE_NAME)->select(['contract_id', 'customer_id', 'contract_creator']);
        if (!empty($ids)) {
            $query = $query->whereIn('contract_id', $ids);
        }
        $lists = $query->get();
        if ($lists->isEmpty()) {
            return array_pad($result, count($types), []);
        }
        $userId = $own['user_id'] ?? '';
        array_map(function ($type) use (&$result, $own, $lists, $userId) {
            switch ($type) {
                case CustomerRepository::UPDATE_MARK:
                    $result[$type] = [];
                    // 编辑权限,对客户具有编辑权限 + 创建人
                    $customerIds = [];
                    foreach ($lists as $index => $item) {
                        // 创建人
                        if ($item->contract_creator == $userId) {
                            $result[$type][] = $item->contract_id;
                            continue;
                        }
                        if (!isset($customerIds[$item->customer_id])) {
                            $customerIds[$item->customer_id] = [];
                        }
                        $customerIds[$item->customer_id][] = $item->contract_id;
                    }
                    if (!empty($customerIds)) {
                        $updateCustomerIds = CustomerRepository::getUpdateIds($own, array_keys($customerIds));
                        if (!empty($updateCustomerIds)) {
                            foreach ($customerIds as $iCustomerId => $iContractIds) {
                                if (in_array($iCustomerId, $updateCustomerIds)) {
                                    $result[$type] = array_merge($result[$type], $iContractIds);
                                }
                            }
                        }
                    }
                    break;

                default:
                    break;
            }
        }, $types);
        return array_merge($result);
    }

    /**
     * 添加收付款提醒
     * @param $flag 默认新增
     */
    public function refreshReminds(array $data, int $contractId, string $userId, bool $flag = false)
    {
        $nowTime = date('Y-m-d H:i:s');
        if (!$flag) {
            foreach ($data as $index => $item) {
                unset($item['isShowButton']);
                if (empty($item)) {
                    unset($data[$index]);
                    continue;
                }
                $item = [
                    'remind_date'    => $item['remind_date'] ?? '',
                    'remind_content' => $item['remind_content'] ?? '',
                    'payment_amount' => $item['payment_amount'] ?? '',
                ];
                list($item['contract_id'], $item['remind_creator'], $item['created_at'], $item['updated_at']) = [$contractId, $userId, $nowTime, $nowTime];
                $data[$index]                                                                                 = $item;
            }
            return DB::table(self::TABLE_REMIND)->insert($data);
        }
        // 编辑
        // 如果为空，直接删除所有
        if (empty($data)) {
            return $this->truncateReminds($contractId);
        }
        $deleteIds   = $insertData   = $originIds   = [];
        $originLists = DB::table(self::TABLE_REMIND)->select(['*'])->where('contract_id', $contractId)->get()->toArray();
        $deleteIds   = array_column($originLists, 'contract_remind_id');
        foreach ($data as $index => $item) {
            unset($item['isShowButton']);
            if (empty($item)) {
                continue;
            }
            // 新增
            $itemId = $item['contract_remind_id'] ?? '';
            if (!$itemId || !in_array($itemId, $deleteIds)) {
                $insertData[] = $item;
                continue;
            }
            // 更新
            $deleteIds = array_diff($deleteIds, [$itemId]);
            DB::table(self::TABLE_REMIND)->where('detail_data_id', $itemId)->update([
                'remind_date'    => $item['remind_date'] ?? '',
                'remind_content' => $item['remind_content'] ?? '',
                'payment_amount' => $item['payment_amount'] ?? '',
            ]);
        }
        if (!empty($deleteIds)) {
            $this->deleteReminds($deleteIds);
        }
        if (!empty($insertData)) {
            $this->refreshReminds($insertData, $contractId, $userId);
            if($result = DB::table(self::TABLE_NAME)->where('contract_id',$contractId)->first()){
                $this->addCalendar($userId,$insertData,$contractId,$result);
            };
        }
        return true;
    }

    private function deleteReminds(array $ids)
    {
        return DB::table(self::TABLE_REMIND)->whereIn('contract_remind_id', $ids)->delete();
    }

    // 获取当前系统配置的提醒时间
    public static function getConfigRemindDate()
    {
        $result = DB::table('system_params')->where('param_key', 'commerce_contract_period')->value('param_value');
        if (empty($result)) {
            return self::DEFAULT_REMIND_DATE;
        }
        return $result;
    }

    public function customerContactRecords(int $customerId)
    {
        return $this->entity->select(['id', 'content', 'start_time', 'end_time', 'linkman_id', 'type_id'])->where('customer_id', $customerId)->get();
    }

    public static function validatePermission(array $types, $id, array $own)
    {
        $result = 0;
        $list   = DB::table(self::TABLE_NAME)->select(['contract_creator', 'customer_id'])->where('contract_id', $id)->first();
        if(!$list){
            return ['code' =>['0x024011','customer']];
        }
        array_map(function ($type) use (&$result, $list, $own) {
            switch ($type) {
                case CustomerRepository::VIEW_MARK:
                    if ($list->contract_creator == $own['user_id']) {
                        $result = $result | $type;
                    } elseif ($list->customer_id && $validate = CustomerRepository::validatePermission([$type], [$list->customer_id], $own)) {
                        $result = $result | $type;
                    }
                    break;

                case CustomerRepository::UPDATE_MARK:
                    if ($list->contract_creator == $own['user_id']) {
                        $result = $result | $type;
                    } elseif ($list->customer_id && $validate = CustomerRepository::validatePermission([$type], [$list->customer_id], $own)) {
                        $result = $result | $type;
                    }
                    break;

                case CustomerRepository::DELETE_MARK:
                    if ($validate = CustomerRepository::validatePermission([$type], [$list->customer_id], $own)) {
                        $result = $result | $type;
                    }
                    break;

                default:
                    # code...
                    break;
            }
        }, $types);
        return $result;
    }

    public static function customerDetailMenuCount($customerId)
    {
        return DB::table(self::TABLE_NAME)->where('customer_id', $customerId)->count();
    }

    // 客户合并
    public static function mergeToCustomer($targetCustomerId, $customerIds)
    {
        return DB::table(self::TABLE_NAME)->whereIn('customer_id', $customerIds)->update([
            'customer_id' => $targetCustomerId,
        ]);
    }

    // 获取今天提醒的合同
    public static function getTodayRemindlists()
    {
        $today = date('Y-m-d', time());
        return DB::table(self::TABLE_REMIND)->where('remind_date', $today)->join('customer_contract', 'customer_contract.contract_id', '=', self::TABLE_REMIND . '.contract_id')->get();
    }

    public function multiSearchIds(array $searchs, $customerIds)
    {
        $query = $this->entity->multiWheres($searchs);
        if (is_array($customerIds)) {
            $query->whereIn('customer_id', $customerIds);
        }
        return $query->pluck('contract_id')->toArray();
    }

    public static function getContractReminds(int $contractId)
    {
        return DB::table(self::TABLE_REMIND)->select(['contract_remind_id', 'contract_id', 'remind_content', 'remind_date', 'payment_amount'])->where('contract_id', $contractId)->get()->toArray();
    }

    public static function getCustomerIds(array $contractIds)
    {
        $result = [];
        $lists = DB::table(self::TABLE_NAME)->select(['customer_id'])->whereIn('contract_id', $contractIds)->get();
        if (!$lists->isEmpty()) {
            $result = array_column($lists->toArray(), 'customer_id');
        }
        return $result;
    }

    public function addCalendar($reminder,$list,$contract_id,$data){
        $list = (array) $list;
        $data = (array) $data;
        // 外发到日程模块 --开始--
        if($list){
            foreach ($list as $key => $vo){
                if($vo && is_array($vo) && isset($vo['remind_date']) && $vo['remind_date']){
                    $remind_content = $vo['remind_content'] ??  '';
                    $title = trans('customer.contract_name').'：'.$data['contract_name'].'，';
                    $title.= trans('customer.contract_reminds').':'.$remind_content;
                    $calendarData = [
                        'calendar_content' => $data['contract_name'].'，'.trans('customer.contract_reminds'),
                        'handle_user'      => [$reminder],
                        'calendar_begin'   => $vo['remind_date'],
                        'calendar_end'     => date('Y-m-d 23:59:59',strtotime($vo['remind_date'])),
                        'calendar_remark'  => preg_replace('/\[emoji.*?\]/', '', str_replace('&nbsp;','',strip_tags($remind_content)))
                    ];
                    $relationData = [
                        'source_id'        => $contract_id,
                        'source_from'      => 'customer-payable',
                        'source_title'     => $title,
                        'source_params'    => ['contract_id' => $contract_id]
                    ];
                    app($this->calendarService)->emit($calendarData, $relationData, $reminder);
                }
            }
        }
        // 外发到日程模块 --结束--
    }
}
