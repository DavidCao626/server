<?php

namespace App\EofficeApp\Customer\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Customer\Entities\SeasGroupEntity;
use App\EofficeApp\Customer\Repositories\CustomerRepository;
use App\EofficeApp\Customer\Services\CustomerService;
use App\EofficeApp\User\Services\UserService;
use DB;
use Illuminate\Support\Facades\Redis;
use Eoffice;

class SeasGroupRepository extends BaseRepository
{

    const TABLE_NAME          = 'customer_seas_group';
    const TABLE_RULE          = 'customer_seas_rule';
    const TABLE_USER_RULE     = 'customer_seas_user';
    const TABLE_CUSTOMER      = 'customer';
    const TABLE_CUSTOMER_PICK = 'customer_user_pick';
    const TABLE_SEAS_PUBLIC   = 'customer_seas_public';

    // 规则分配方式
    const DISTRIBUTE_TYPE_AVERAGE    = 1;
    const DISTRIBUTE_TYPE_NUMBER     = 2;
    const DISTRIBUTE_TYPE_PROPORTION = 3;

    const REDIS_DISTRIBUTE_USER = 'customer:seas_rule_distribute_';

    // 客户默认公海
    const DEFAULT_SEAS = 1;

    // 捡起权限
    const PICK_NO_PERMISSION      = 0;
    const PICK_USER_PERMISSION    = 10;
    const PICK_MANAGER_PERMISSION = 20;

    // 分配方式
    const OPEND_AUTO_PICK = 2;

    // 用户可捡起总数为无限
    const CAN_PICK_INFINITE = 'infinite';

    // 用户可捡起的总数rediskey
    const USER_PICK_COUNT = 'customer:user_pick_number';
    // redis 分配队列最后一位
    const NERVER_USER = '-1';

    public function __construct(SeasGroupEntity $entity)
    {
        parent::__construct($entity);
    }

    /**
     * 获取列表
     * @param  array  $param
     * @return Array
     */
    public function getLists(array $param = [])
    {
        if (isset($param['order_by'])) {
            unset($param['order_by']);
        }
        $default = [
            'fields'   => ['id', 'name'],
            'page'     => 0,
            'limit'    => config('eoffice.pagesize'),
            'search'   => [],
            'order_by' => ['id' => 'asc'],
        ];
        $param = array_merge($default, $param);
        return $this->entity->select($param['fields'])->wheres($param['search'])->orders($param['order_by'])->parsePage($param['page'], $param['limit'])->get();
    }

    public static function validateInput(array &$input, $groupId = 0)
    {
        $groupData = isset($input['groupData']) ? $input['groupData'] : [];
        $rulesData = isset($input['rulesData']) ? $input['rulesData'] : [];
        if (empty($groupData)) {
            return ['code' => ['0x024002', 'customer']];
        }
        // 获取开放用户的id集合
        $openUserIds = self::getOpenUserIdsByData($groupData);
        $managerUserIds = $groupData['manager_ids'];
        // 获取公海分组下的管理员
        if($openUserIds != 'all' && $managerUserIds){
            $openUserIds = array_merge($openUserIds,$managerUserIds);
        }
        $rsGroupData = $rsRuleDatas = [];
        // 分组
        $rsGroupData['name']            = $groupData['name'] ?? '';
        $rsGroupData['open_type']       = $groupData['open_type'] ?? '';
        $rsGroupData['user_ids']        = isset($groupData['user_ids']) ? implode('|', (array) $groupData['user_ids']) : '';
        $rsGroupData['dept_ids']        = isset($groupData['dept_ids']) ? implode('|', (array) $groupData['dept_ids']) : '';
        $rsGroupData['role_ids']        = isset($groupData['role_ids']) ? implode('|', (array) $groupData['role_ids']) : '';
        $rsGroupData['manager_ids']     = isset($groupData['manager_ids']) ? implode('|', (array) $groupData['manager_ids']) : '';
        $distributeType                 = isset($groupData['distribute_type']) && is_array($groupData['distribute_type']) ? array_values($groupData['distribute_type']) : [0];
        $distributeTypeAuto = $distributeType[0] ?? 0;
        $distributeTypeSystem = $distributeType[1] ?? 0;
        $rsGroupData['distribute_type'] = $distributeTypeSystem ? $distributeTypeAuto | $distributeTypeSystem : $distributeTypeAuto;
        // 限制总数
        $limitCount                 = isset($groupData['limit_count']) ? abs($groupData['limit_count']) : 0;
        $rsGroupData['limit_count'] = isset($groupData['limit_count_check']) && $groupData['limit_count_check'] ? $limitCount : -$limitCount;
        // 限制重复捡起间隔天数
        $limitRepeat                 = isset($groupData['limit_repeat']) ? abs($groupData['limit_repeat']) : 0;
        $rsGroupData['limit_repeat'] = isset($groupData['limit_repeat_check']) && $groupData['limit_repeat_check'] ? $limitRepeat : -$limitRepeat;
        // 限制回收天数
        $recycleRecord                 = isset($groupData['recycle_record']) ? abs($groupData['recycle_record']) : 0;
        $rsGroupData['recycle_record'] = isset($groupData['recycle_record_check']) && $groupData['recycle_record_check'] ? $recycleRecord : -$recycleRecord;
        // 限制上次联系间隔天数
        $recycleFlow                        = isset($groupData['recycle_prev_follow']) ? abs($groupData['recycle_prev_follow']) : 0;
        $rsGroupData['recycle_prev_follow'] = isset($groupData['recycle_prev_follow_check']) && $groupData['recycle_prev_follow_check'] ? $recycleFlow : -$recycleFlow;
        // 提前提醒天数
        $rsGroupData['reminds'] = (isset($groupData['reminds']) && $groupData['reminds']) ? abs($groupData['reminds']) : 0;
        // 回收月个数
        $rsGroupData['limit_months'] = (isset($groupData['limit_months']) && $groupData['limit_months']) ? abs($groupData['limit_months']) : 0;
        // 规则
        foreach ($rulesData as $index => $item) {
            $tItem = [];
            if (isset($item['id'])) {
                $tItem['id'] = $item['id'];
            }
            $tItem['distribute_type'] = isset($item['distribute_type']) ? $item['distribute_type'] : 1;
            $tItem['user_ids']        = isset($item['user_ids']) && is_array($item['user_ids']) ? $item['user_ids'] : [];
            // 规则用户必须在开放人员里面
            $tItem['user_ids']  = $openUserIds !== CustomerService::LIST_TYPE_ALL ? array_intersect($openUserIds, $tItem['user_ids']) : $tItem['user_ids'];
            // 去除开发成员内重复id
            $tItem['user_ids'] = array_unique($tItem['user_ids']);


            $tItem['rule_text'] = $item['rule_text'] ?? '';
            // sql 语句是否有效
            if ($tItem['rule_text'] && !$validate = self::testSelectSql($tItem['rule_text'])) {
                return ['code' => ['0x024022', 'customer']];
            }
            // 按比例分配不得超过100%
            if ($tItem['distribute_type'] == self::DISTRIBUTE_TYPE_PROPORTION && !empty($tItem['user_ids'])) {
                if (array_sum(array_values($item['users'])) > 100) {
                    return ['code' => ['0x024023', 'customer']];
                }
            }
            $tItem['limit_date'] = isset($item['limit_date']) ? intval($item['limit_date']) : 0;
            if (isset($item['users']) && !empty($item['users'])) {
                $tItem['users'] = $item['users'];
            }
            $rsRuleDatas[] = $tItem;
        }
        return [$rsGroupData, $rsRuleDatas];
    }

    public static function storeSeasGroup(array $groupData)
    {
        return DB::table(self::TABLE_NAME)->insertGetId($groupData);
    }

    public static function updateSeasGroup(int $groupId, array $groupData)
    {
        Redis::del(self::USER_PICK_COUNT . $groupId);
        return DB::table(self::TABLE_NAME)->where('id', $groupId)->update($groupData);
    }

    public static function storeSeasGroupRules(int $groupId, array $rulesData)
    {
        if (empty($rulesData)) {
            return true;
        }
        $iUserData = [];
        $nowTime   = date('Y-m-d H:i:s', time());
        foreach ($rulesData as $key => $item) {
            $tempUsers = $tempUserIds = $tempRuleUsers = [];
            if (isset($item['users'])) {
                $tempUsers = $item['users'];
                unset($item['users']);
            }
            if (isset($item['user_ids'])) {
                $tempUserIds = $item['user_ids'];
                unset($item['user_ids']);
            }
            $item['created_at']    = $nowTime;
            $item['updated_at']    = $nowTime;
            $item['seas_group_id'] = $groupId;
            $ruleId                = DB::table(self::TABLE_RULE)->insertGetId($item);
            if (empty($tempUserIds) || !$ruleId) {
                continue;
            }
            foreach ($tempUserIds as $userId) {
                $tempRuleUsers['user_id']      = $userId;
                $tempRuleUsers['limit_number'] = isset($tempUsers[$userId]) ? intval($tempUsers[$userId]) : 0;
                $tempRuleUsers['seas_rule_id'] = $ruleId;
                $iUserData[]                   = $tempRuleUsers;
            }
        }
        if (empty($iUserData)) {
            return true;
        }
        return DB::table(self::TABLE_USER_RULE)->insert($iUserData);
    }

    public static function updateSeasGroupRules(int $groupId, array $rulesData)
    {
        $nowTime        = date('Y-m-d H:i:s', time());
        $noDeletRuleIds = array_column($rulesData, 'id');
        self::deleteSeasGroupRules($groupId, $noDeletRuleIds);
        if (empty($rulesData)) {
            return true;
        }
        // 更新已有数据，新增不存在的数据
        $iData = [];
        foreach ($rulesData as $index => $item) {
            $tData = [];
            if (!isset($item['id'])) {
                $iData[] = $item;
                continue;
            }
            $tData['rule_text']       = $item['rule_text'];
            $tData['distribute_type'] = $item['distribute_type'];
            $tData['limit_date']      = $item['limit_date'];
            $tData['updated_at']      = $nowTime;
            DB::table(self::TABLE_RULE)->where('id', $item['id'])->update($tData);
            // 删除分配队列缓存
            Redis::del(self::REDIS_DISTRIBUTE_USER . $item['id']);
            // 删除规则分配的所有用户
            if (empty($item['user_ids'])) {
                DB::table(self::TABLE_USER_RULE)->where('seas_rule_id', $item['id'])->delete();
                continue;
            }
            $userRuleLists    = DB::table(self::TABLE_USER_RULE)->select(['user_id'])->where('seas_rule_id', $item['id'])->get();
            $tempExistUserIds = array_column($userRuleLists->toArray(), 'user_id');
            $tempDiffUserIds  = array_diff($tempExistUserIds, $item['user_ids']);
            // 删除规则用户
            if (!empty($tempDiffUserIds)) {
                DB::table(self::TABLE_USER_RULE)->where('seas_rule_id', $item['id'])->whereIn('user_id', $tempDiffUserIds)->delete();
            }
            $iUserData = [];
            foreach ($item['user_ids'] as $userId) {
                // 需要更新
                $tempLimitNumber = isset($item['users'][$userId]) ? $item['users'][$userId] : 0;
                if (in_array($userId, $tempExistUserIds)) {
                    DB::table(self::TABLE_USER_RULE)->where('seas_rule_id', $item['id'])->where('user_id', $userId)->update([
                        'limit_number' => intval($tempLimitNumber),
                    ]);
                } else {
                    // 表示新增的
                    $iUserData[] = [
                        'user_id'      => $userId,
                        'limit_number' => intval($tempLimitNumber),
                        'seas_rule_id' => $item['id'],
                    ];
                }
            }
            if (!empty($iUserData)) {
                DB::table(self::TABLE_USER_RULE)->insert($iUserData);
            }
        }

        if (!empty($iData)) {
            self::storeSeasGroupRules($groupId, $iData);
        }
        return true;
    }

    public static function deleteSeasGroup(int $groupId)
    {
        DB::table(self::TABLE_NAME)->where('id', $groupId)->delete();
        Redis::del(self::USER_PICK_COUNT . $groupId);
        return self::deleteSeasGroupRules($groupId);
    }

    // 删除公海组规则和规则用户表
    public static function deleteSeasGroupRules(int $groupId, array $noDeleteRules = [])
    {
        if (!empty($noDeleteRules)) {
            $ruleIds = DB::table(self::TABLE_RULE)->select('id')->where('seas_group_id', $groupId)->whereNotIn('id', $noDeleteRules)->pluck('id')->toArray();
            if (empty($ruleIds)) {
                return true;
            }
            DB::table(self::TABLE_RULE)->whereIn('id', $ruleIds)->delete();
            DB::table(self::TABLE_USER_RULE)->whereIn('seas_rule_id', $ruleIds)->delete();
            return true;
        }
        $ruleIds = DB::table(self::TABLE_RULE)->select('id')->where('seas_group_id', $groupId)->get()->pluck('id')->toArray();
        if (empty($ruleIds)) {
            return true;
        }
        DB::table(self::TABLE_RULE)->where('seas_group_id', $groupId)->delete();
        DB::table(self::TABLE_USER_RULE)->whereIn('seas_rule_id', $ruleIds)->delete();
        return true;
    }

    /**
     * 根据公海组获取用户可捡起的客户总数，null表示不限制
     * @param  int $groupId 分组id
     * @param  int $userId
     * @return null || int
     */
    public static function getUserCanPickUpNumber($groupId, $userId)
    {
        $result = null;
        if (($number = Redis::hget(self::USER_PICK_COUNT . $groupId, $userId)) === null) {
            return self::updateUserCanPickUps($groupId, $userId);
        }
        return $number;
    }

    public static function updateUserCanPickUps($groupId, $userId, int $num = null)
    {
        $canNum = 0;
        if ($num === null || !$number = Redis::hget(self::USER_PICK_COUNT . $groupId, $userId)) {
            $count = DB::table(self::TABLE_NAME)->where('id', $groupId)->value('limit_count');
            if ($count <= 0) {
                Redis::hset(self::USER_PICK_COUNT . $groupId, $userId, self::CAN_PICK_INFINITE);
                return self::CAN_PICK_INFINITE;
            }
            $hasPickNum = DB::table(self::TABLE_CUSTOMER_PICK)->where('user_id', $userId)->where('seas_group_id', $groupId)->count();
            $canNum     = $count > $hasPickNum ? $count - $hasPickNum : 0;
            Redis::hset(self::USER_PICK_COUNT . $groupId, $userId, $canNum);
        } else {
            $originNum = Redis::hget(self::USER_PICK_COUNT . $groupId, $userId);
            if ($originNum === self::CAN_PICK_INFINITE) {
                return $originNum;
            }
            $canNum    = $originNum + $num;
            Redis::hset(self::USER_PICK_COUNT . $groupId, $userId, $canNum);
        }
        return $canNum;
    }

    /**
     * 更新用户已经分配到的客户总数
     * @param  int $groupId
     * @param  int $userId
     * @param  int $pickUpCount
     * @return bool
     */
    public function updateUserHasDistributeNumber($groupId, $userId, $pickUpCount)
    {
        $distributeCount = DB::table('customer_seas_group_user')->where('seas_group_id', $groupId)->where('user_id', $userId)->value('distribute_count');
        // 表示第一次
        if ($distributeCount === null) {
            return DB::table('customer_seas_group_user')->insert([
                'seas_group_id'    => $groupId,
                'user_id'          => $userId,
                'distribute_count' => $pickUpCount,
                'created_at'       => date('Y-m-d H:i:s', time()),
            ]);
        }
        return DB::update("UPDATE customer_seas_group_user SET distribute_count = distribute_count + {$pickUpCount} WHERE seas_group_id = '{$groupId}' AND user_id = '{$userId}'");
    }

    /**
     * 更改客户的分组
     */
    public static function changeSeas($oldSeasId, $newSeasGroupId, $customerIds)
    {
        $result = DB::table(self::TABLE_CUSTOMER)->whereIn('customer_id', $customerIds)->update([
            'seas_group_id'        => $newSeasGroupId,
            'customer_manager'     => '',
            'last_distribute_time' => 0,
        ]);
        if (!$result) {
            return false;
        }
        // 刷新两个公海分组的待分配缓存等
        self::refreshWaitDistribute($oldSeasId);
        self::refreshWaitDistribute($newSeasGroupId);
        return $result;
    }

    /**
     * 判断客户能否被捡起
     * 只有客户经理为空才可以
     */
    public static function validateCustomerPickUp(array $customerIds, $groupId)
    {
        // 处理客户经理被删除的情况(若被删除，则把客户经理清空，以便后续捡起操作)
        $customerDatas = DB::table(self::TABLE_CUSTOMER)->select(['customer_id','customer_manager'])
            ->whereIn('customer_id', $customerIds)
            ->where('seas_group_id', $groupId)
            ->where('customer_manager','!=','')
            ->get()->toArray();

        if($customerDatas){
            foreach ($customerDatas as $vo){
                $user_status = DB::table('user_system_info')->where('user_id', $vo->customer_manager)->value('user_status');
                if($user_status == 0){
                    DB::table(self::TABLE_CUSTOMER)->where('customer_id',$vo->customer_id)->update(['customer_manager'=>'']);
                }
                if($vo->customer_manager){
                    return ['code' => ['not_pick_up','customer']];
                }
            }
        }
        $targetCount = DB::table(self::TABLE_CUSTOMER)->whereIn('customer_id', $customerIds)->where('customer_manager', '')->where('seas_group_id', $groupId)->count();
        if ($targetCount !== count($customerIds)) {
            return ['code' => ['0x024003','customer']];
        }
        return true;
    }

    /**
     * 判断用户捡起的权限
     * @param  int $groupId
     * @param  int $userId
     * @return bool
     */
    public static function validatePickUps($groupId, $userId)
    {
        $list = DB::table(self::TABLE_NAME)->select(['open_type', 'manager_ids', 'user_ids', 'dept_ids', 'role_ids', 'distribute_type'])->find($groupId);
        if (empty($list)) {
            return self::PICK_NO_PERMISSION;
        }
        if (in_array($userId, explode('|', $list->manager_ids))) {
            return self::PICK_MANAGER_PERMISSION;
        }
        if (!($list->distribute_type & self::OPEND_AUTO_PICK)) {
            return self::PICK_NO_PERMISSION;
        }
        if ($list->open_type || in_array($userId, explode('|', $list->user_ids))) {
            return self::PICK_USER_PERMISSION;
        }
        $userList = app('App\EofficeApp\User\Services\UserService')->getUserDeptIdAndRoleIdByUserId($userId);
        if (empty($userList)) {
            return self::PICK_NO_PERMISSION;
        }
        if ($userList['dept_id'] && $list->dept_ids && in_array($userList['dept_id'], explode('|', $list->dept_ids))) {
            return self::PICK_USER_PERMISSION;
        }
        if (!$userList['role_id']) {
            return self::PICK_NO_PERMISSION;
        }
        $userRoleIds = explode(',', $userList['role_id']);
        if (!empty($userRoleIds) && $list->role_ids && !empty(array_intersect($userRoleIds, explode('|', $list->role_ids)))) {
            return self::PICK_USER_PERMISSION;
        }
        return self::PICK_NO_PERMISSION;
    }

    /**
     * 判断客户回收期内不可再次分配给同一个用户
     * @param  array $customerIds
     * @param  int $groupId
     * @param  int $userId
     * @return bool
     */
    public static function validateRecycles($customerIds, $groupId, $userId)
    {
        $list = DB::table(self::TABLE_NAME)->select('limit_repeat')->find($groupId);
        if (empty($list) || $list->limit_repeat <= 0) {
            return true;
        }
        $recycleLists = DB::table('customer_seas_recycle')->select('recycle_time')->whereIn('customer_id', $customerIds)->where('user_id', $userId)->get();
        if ($recycleLists->isEmpty()) {
            return true;
        }
        $nowTime = time();
        $result  = true;
        foreach ($recycleLists as $key => $item) {
            if ($item->recycle_time + $list->limit_repeat * 3600 * 24 >= $nowTime) {
                $result = false;
                break;
            }
        }
        return $result;
    }

    public static function insertRecycles(array $customerIds,$type = null)
    {
        $nowTime       = time();
        $customerLists = DB::table(self::TABLE_CUSTOMER)->select(['customer_id', 'customer_manager','seas_group_id','customer_name'])->whereIn('customer_id', $customerIds)->get();
        switch ($type){
            case 1:$mark = trans('customer.pick_down_customer');
                break;
            case 2:
                $mark = trans('customer.pick_customer');
                break;
            default:
                $mark = trans('customer.system_recycle_customer');
                break;
        }
        // 记录回收表
        foreach ($customerLists as $key => $item) {
            $recycleDatas[] = [
                'customer_id'  => $item->customer_id,
                'user_id'      => $item->customer_manager ? $item->customer_manager : own()['user_id'],
                'recycle_time' => $nowTime,
                'remark'       => $type ? ($mark.':'.$item->customer_name) : '',
            ];
        }
        return DB::table('customer_seas_recycle')->insert($recycleDatas);
    }

    public static function getUserGroupSeasIds($own)
    {
        $groupIds   = $managerSeasIds   = [];
        $groupLists = DB::table('customer_seas_group')->select(['id', 'open_type', 'manager_ids', 'user_ids', 'dept_ids', 'role_ids'])->get();
        if ($groupLists->isEmpty()) {
            return [$managerSeasIds, $groupIds];
        }
        foreach ($groupLists as $key => $item) {
            if ($item->manager_ids) {
                $managerIds = explode('|', $item->manager_ids);
                if (in_array($own['user_id'], $managerIds)) {
                    $managerSeasIds[] = $item->id;
                }
            }
            if ($item->open_type) {
                $groupIds[] = $item->id;
                continue;
            }
            if ($item->user_ids) {
                $userIds = explode('|', $item->user_ids);
                if (in_array($own['user_id'], $userIds)) {
                    $groupIds[] = $item->id;
                    continue;
                }
            }
            if ($item->dept_ids) {
                $deptIds = explode('|', $item->dept_ids);
                if (in_array($own['dept_id'], $deptIds)) {
                    $groupIds[] = $item->id;
                    continue;
                }
            }
            if ($item->role_ids) {
                $roleIds = explode('|', $item->role_ids);
                if (!empty(array_intersect($own['role_id'], $roleIds))) {
                    $groupIds[] = $item->id;
                    continue;
                }
            }
        }
        return [$managerSeasIds, $groupIds];
    }

    public function existCustomerSeasGroup($id)
    {
        return DB::table('customer')->where('seas_group_id', $id)->first();
    }

    // 判断用户对分组是否具有管理权限
    public static function hasManagerPermission(int $seasId, $userId)
    {
        $result     = false;
        $managerIds = DB::table(self::TABLE_NAME)->where('id', $seasId)->value('manager_ids');
        $managerIds = $managerIds ? explode('|', $managerIds) : [];
        if (!empty($managerIds) && in_array($userId, $managerIds)) {
            $result = true;
        }
        return $result;
    }

    // 获取今日新分配的客户总数
    public static function newDistribute($seasId)
    {
        if (($result = Redis::hget(CustomerService::SEAS_DISTRIBUTE_NEW, $seasId)) === null) {
            self::refreshNewDistribute($seasId);
        }
        return Redis::hget(CustomerService::SEAS_DISTRIBUTE_NEW, $seasId);
    }

    // 获取待分配的客户总数
    public static function waitDistribute($seasId)
    {
        if (($result = Redis::hget(CustomerService::SEAS_DISTRIBUTE_WAIT, $seasId)) === null) {
            self::refreshWaitDistribute($seasId);
        }
        return Redis::hget(CustomerService::SEAS_DISTRIBUTE_WAIT, $seasId);
    }

    public static function refreshNewDistribute(int $seasId = 0)
    {
        $nowTime        = time();
        $todayStartTime = strtotime(date('Y-m-d', $nowTime));
        $todayEndTime   = strtotime("+1 day") - $nowTime;
        if ($seasId) {
            $count = DB::table('customer')->where('seas_group_id', $seasId)->where('customer_manager', '!=', '')->where('last_distribute_time', '>=', $todayStartTime)->whereNull('deleted_at')->count();
            Redis::hset(CustomerService::SEAS_DISTRIBUTE_NEW, $seasId, $count);
            Redis::expire(CustomerService::SEAS_DISTRIBUTE_NEW, $todayEndTime);
        } else {
            // 更新全部
            $seasCustomersCount = [];
            DB::table('customer')->select(['seas_group_id', 'last_distribute_time'])->where('customer_manager', '!=', '')->where('last_distribute_time', '>=', $todayStartTime)->whereNull('deleted_at')->orderBy('customer_id')->chunk(2000, function ($lists) use (&$seasCustomersCount) {
                if (!$lists->isEmpty()) {
                    foreach ($lists as $index => $item) {
                        if (!isset($seasCustomersCount[$item->seas_group_id])) {
                            $seasCustomersCount[$item->seas_group_id] = 0;
                        }
                        ++$seasCustomersCount[$item->seas_group_id];
                    }
                }
            });
            if (!empty($seasCustomersCount)) {
                Redis::hmset(CustomerService::SEAS_DISTRIBUTE_NEW, $seasCustomersCount);
                Redis::expire(CustomerService::SEAS_DISTRIBUTE_NEW, $todayEndTime);
            }
        }
        return true;
    }

    public static function refreshWaitDistribute(int $seasId = 0)
    {
        if ($seasId) {
            $count = DB::table(self::TABLE_CUSTOMER)->where('seas_group_id', $seasId)->where('customer_manager', '')->whereNull('deleted_at')->count();
            Redis::hset(CustomerService::SEAS_DISTRIBUTE_WAIT, $seasId, $count);
        } else {
            // 更新全部
            $seasCustomersCount = [];
            DB::table(self::TABLE_CUSTOMER)->select(['seas_group_id', 'last_distribute_time'])->where('customer_manager', '')->whereNull('deleted_at')->orderBy('customer_id')->chunk(2000, function ($lists) use (&$seasCustomersCount) {
                if (!$lists->isEmpty()) {
                    foreach ($lists as $index => $item) {
                        if (!isset($seasCustomersCount[$item->seas_group_id])) {
                            $seasCustomersCount[$item->seas_group_id] = 0;
                        }
                        ++$seasCustomersCount[$item->seas_group_id];
                    }
                }
            });
            if (!empty($seasCustomersCount)) {
                Redis::hmset(CustomerService::SEAS_DISTRIBUTE_WAIT, $seasCustomersCount);
            }
        }
        return true;
    }

    // 验证退回公海权限
    public static function validatePickDowns(array $customerIds, $userId)
    {
        $result        = true;
        $customerLists = DB::table(self::TABLE_CUSTOMER)->select(['seas_group_id', 'customer_manager'])->whereIn('customer_id', $customerIds)->where('customer_manager', '!=', '')->get();
        if ($customerLists->isEmpty()) {
            return ['code' => ['not_pick_down','customer']];
        }
        // 判断是否为公海管理员 或者 客户经理
        $seasResult = [];
        foreach ($customerLists as $index => $item) {
            if ($item->customer_manager == $userId) {
                continue;
            }
            if (in_array($item->seas_group_id, $seasResult)) {
                continue;
            }
            $managerIds = DB::table(self::TABLE_NAME)->where('id', $item->seas_group_id)->value('manager_ids');
            if ($managerIds && in_array($userId, explode('|', $managerIds))) {
                $seasResult[] = $item->seas_group_id;
                continue;
            }
            return ['code' => ['0x024003','customer']];
            break;
        }
        return $result;
    }

    // 退回公海
    public static function pickDowns(array $customerIds, $groupId, $userId)
    {
        $query  = DB::table(self::TABLE_CUSTOMER)->whereIn('customer_id', $customerIds)->where('customer_manager', '!=', '');
        $count  = $query->count();
        $result = $query->update(['customer_manager' => '']);
        if (!$result) {
            return false;
        }
        self::updateWaitDistributeCount($groupId, $count);
        self::updateNewDistributeCount($groupId, $count);
        // 如果是用户捡起的，那么可以另外捡起数量+
        $originPickNum = DB::table(self::TABLE_CUSTOMER_PICK)->whereIn('customer_id', $customerIds)->where('user_id', $userId)->count();
        self::refreshUserPickUps($groupId, $customerIds);
        self::updateUserCanPickUps($groupId, $userId, $originPickNum);
        return true;
    }

    // 捡起公海
    public static function pickUps(array $customerIds, $groupId, $userId)
    {
        $query  = DB::table(self::TABLE_CUSTOMER)->whereIn('customer_id', $customerIds)->where('customer_manager', '');
        $count  = $query->count();
        $result = $query->update(['customer_manager' => $userId, 'last_distribute_time' => time()]);
        if (!$result) {
            return false;
        }
        self::updateWaitDistributeCount($groupId, -$count);
        self::updateNewDistributeCount($groupId, $count);
        self::refreshUserPickUps($groupId, $customerIds, $userId);
        self::updateUserCanPickUps($groupId, $userId, -count($customerIds));
        return true;
    }

    /**
     * 刷新用户捡起客户表
     * @param $userId 表示需要新增
     */
    public static function refreshUserPickUps($groupId, $customerIds, $userId = null)
    {
        DB::table(self::TABLE_CUSTOMER_PICK)->whereIn('customer_id', $customerIds)->delete();
        if ($userId !== null) {
            $data    = [];
            $nowTime = time();
            foreach ($customerIds as $customerId) {
                $data[] = [
                    'customer_id'   => $customerId,
                    'user_id'       => $userId,
                    'seas_group_id' => $groupId,
                    'create_time'   => $nowTime,
                ];
            }
            DB::table(self::TABLE_CUSTOMER_PICK)->insert($data);
        }
        return true;
    }

    // 更改客户经理
    public static function updateCustomerManager(array $customerIds, $groupId, $userId)
    {
        // 今日新分配的数量
        $count = DB::table(self::TABLE_CUSTOMER)->whereIn('customer_id', $customerIds)->count();
        // 今日待分配数量更新
        $waitCount = DB::table(self::TABLE_CUSTOMER)->whereIn('customer_id', $customerIds)->where('customer_manager', '')->count();
        $result    = DB::table(self::TABLE_CUSTOMER)->whereIn('customer_id', $customerIds)->update(['customer_manager' => $userId, 'last_distribute_time' => time()]);
        if (!$result) {
            return false;
        }
        self::updateWaitDistributeCount($groupId, -$waitCount);
        return self::updateNewDistributeCount($groupId, $count);
    }

    // 转移客户经理
    public static function transferCustomerManager(array $userIds, $groupId, $userId)
    {
        return DB::table(self::TABLE_CUSTOMER)->whereIn('customer_manager', $userIds)->where('seas_group_id', $groupId)->update(['customer_manager' => $userId, 'last_distribute_time' => time()]);
    }

    public static function updateNewDistributeCount($groupId, int $number = 1)
    {
        // 同一客户捡起，退回，重复操作导致捡起数量重复统计(捡起后直接统计)
        return self::refreshNewDistribute($groupId);
        /*
        if (($result = Redis::hget(CustomerService::SEAS_DISTRIBUTE_NEW, $groupId)) === null) {
            return self::refreshNewDistribute($groupId);
        }
        $oldNumber = Redis::hget(CustomerService::SEAS_DISTRIBUTE_NEW, $groupId);
        $newNumber = $oldNumber + $number;
        Redis::hset(CustomerService::SEAS_DISTRIBUTE_NEW, $groupId, $newNumber);
        return true;
        */
    }

    public static function updateWaitDistributeCount($groupId, int $number = 1)
    {
        // 刷新未分配客户直接数据库实时操作
        return self::refreshWaitDistribute($groupId);
        /*
        if (($result = Redis::hget(CustomerService::SEAS_DISTRIBUTE_WAIT, $groupId)) === null) {
            return self::refreshNewDistribute($groupId);
        }
        $oldNumber = Redis::hget(CustomerService::SEAS_DISTRIBUTE_WAIT, $groupId);
        $newNumber = $oldNumber + $number;
        Redis::hset(CustomerService::SEAS_DISTRIBUTE_WAIT, $groupId, $newNumber);
        return true;
        */
    }

    // 判断是否为管理员
    public static function validateIsManager(int $groupId, $userId)
    {
        $mamangerIds = DB::table(self::TABLE_NAME)->where('id', $groupId)->value('manager_ids');
        if (!$mamangerIds || !in_array($userId, explode('|', $mamangerIds))) {
            return false;
        }
        return true;
    }

    /**
     * 获取用户所有的公海组id
     * @param  int $userId
     * @return array
     */
    public static function getUserSeasGroupIds($userId)
    {
        $result   = [];
        $userList = app('App\EofficeApp\User\Repositories\UserRepository')->getUserDeptIdAndRoleIdByUserId($userId);
        if (empty($userList)) {
            return $result;
        }
        $groupLists = DB::table(self::TABLE_NAME)->select(['id', 'manager_ids', 'user_ids', 'dept_ids', 'role_ids', 'open_type'])->get();
        if ($groupLists->isEmpty()) {
            return $result;
        }
        foreach ($groupLists as $key => $item) {
            if ($item->open_type) {
                $result[] = $item->id;
                continue;
            }
            if ($item->manager_ids) {
                $managerIds = explode('|', $item->manager_ids);
                if (in_array($userId, $managerIds)) {
                    $result[] = $item->id;
                    continue;
                }
            }
            if ($item->user_ids) {
                $userIds = explode('|', $item->user_ids);
                if (in_array($userId, $userIds)) {
                    $result[] = $item->id;
                    continue;
                }
            }
            if ($item->dept_ids && $userList['dept_id']) {
                $deptIds = explode('|', $item->dept_ids);
                if (in_array($userList['dept_id'], $deptIds)) {
                    $result[] = $item->id;
                    continue;
                }
            }
            if ($item->role_ids && $userList['role_id']) {
                $roleIds = explode('|', $item->role_ids);
                if (!empty(array_intersect(explode(',', $userList['role_id']), $roleIds))) {
                    $result[] = $item->id;
                    continue;
                }
            }
        }
        return $result;
    }

    /**
     * 根据公海id获取当前开放的所有用户id
     * @return array | all
     */
    public static function getOpenUserIds($groupId)
    {
        $list = DB::table(self::TABLE_NAME)->select(['open_type', 'user_ids', 'dept_ids', 'role_ids','manager_ids'])->where('id', $groupId)->first();
        if ($list->open_type) {
            // 表示全体开放
            return CustomerService::LIST_TYPE_ALL;
        }
        $result  = $list->user_ids ? explode('|', $list->user_ids) : [];
        $deptIds = $list->dept_ids ? explode('|', $list->dept_ids) : [];
        $roleIds = $list->role_ids ? explode('|', $list->role_ids) : [];
        $managerIds = $list->manager_ids ? explode('|', $list->manager_ids) : [];
        if (!empty($deptIds)) {
            $deptLists = DB::table('user_system_info')->select('user_id')->whereIn('dept_id', $deptIds)->get();
            if (!$deptLists->isEmpty()) {
                $result = array_merge($result, array_column($deptLists->toArray(), 'user_id'));
            }
        }
        if (!empty($roleIds)) {
            $roleLists = DB::table('user_role')->select('user_id')->whereIn('role_id', $roleIds)->get()->toArray();
            if (!empty($roleLists)) {
                $result = array_merge($result, array_column($roleLists, 'user_id'));
            }
        }
        if(!empty($managerIds)){
            $result = array_merge($result,$managerIds);
        }
        return $result;
    }

    /**
     * 根据公海信息获取当前开放的所有用户id
     * @return array | all
     */
    public static function getOpenUserIdsByData($groupData)
    {
        if (isset($groupData['open_type']) && $groupData['open_type']) {
            return CustomerService::LIST_TYPE_ALL;
        }
        $result  = isset($groupData['user_ids']) && is_array($groupData['user_ids']) ? $groupData['user_ids'] : [];
        $deptIds = isset($groupData['dept_ids']) && is_array($groupData['dept_ids']) ? $groupData['dept_ids'] : [];
        $roleIds = isset($groupData['role_ids']) && is_array($groupData['role_ids']) ? $groupData['role_ids'] : [];
        if (!empty($deptIds)) {
            $deptLists = DB::table('user_system_info')->select('user_id')->whereIn('dept_id', $deptIds)->get();
            if (!$deptLists->isEmpty()) {
                $result = array_merge($result, array_column($deptLists->toArray(), 'user_id'));
            }
        }
        if (!empty($roleIds)) {
            $roleLists = DB::table('user_role')->select('user_id')->whereIn('role_id', $roleIds)->get();
            if (!empty($roleLists)) {
                $result = array_merge($result, array_column($roleLists->toArray(), 'user_id'));
            }
        }
        return $result;
    }

    /**
     * 根据用户id集合，获取用户的客户经理总数
     */
    public static function getCustomerCounts($userIds, int $groupId = 0, $customerIds = null)
    {
        $result = [];
        $query  = DB::table('customer')->select(['customer_manager'])->whereIn('customer_manager', $userIds)->whereNull('deleted_at');
        if ($groupId) {
            $query = $query->where('seas_group_id', $groupId);
        }
        if ($customerIds !== null) {
            $query = self::tempTableJoin($query, $customerIds);
            // $query = $query->whereIn('customer_id', $customerIds);
        }
        $lists = $query->pluck('customer_manager')->toArray();
        return array_count_values($lists);
    }

    // sql 太长导致mysql gone away
    public static function tempTableJoin($query, $customerIds)
    {
        if (!empty($customerIds) && count($customerIds) > CustomerRepository::MAX_WHERE_IN) {
            $tableName = 'seas_group'.rand() . uniqid();
            DB::statement("CREATE TEMPORARY TABLE if not exists {$tableName} (`data_id` int(6) NOT NULL,PRIMARY KEY (`data_id`))");
            $tempIds = array_chunk($customerIds, CustomerRepository::MAX_WHERE_IN, true);
            foreach ($tempIds as $key => $item) {
                $ids  = implode("),(", $item);
                $tSql = "insert into {$tableName} (data_id) values ({$ids});";
                DB::insert($tSql);
            }
            $query = $query->join($tableName, $tableName . ".data_id", '=', 'customer_id');
        } else {
            $query = $query->whereIn('customer_id', $customerIds);
        }
        return $query;
    }

    public static function showGroup(int $groupId)
    {
        $result = (array) DB::table('customer_seas_group')->find($groupId);
        if (empty($result)) {
            return $result;
        }
        $distributeType = $result['distribute_type'] ?? 0;
        if ($distributeType) {
            unset($result['distribute_type']);
        }
        $result['distribute_type'] = [
            'auto'   => $distributeType & CustomerService::SEAS_GROUP_USER_INITIATIVE ? CustomerService::SEAS_GROUP_USER_INITIATIVE : 0,
            'system' => $distributeType & CustomerService::SEAS_GROUP_AUTO_DISTRIBUTE ? CustomerService::SEAS_GROUP_AUTO_DISTRIBUTE : 0,
        ];
        if (isset($result['manager_ids']) && !empty($result['manager_ids'])) {
            $result['manager_ids'] = explode('|', $result['manager_ids']);
        }
        if (isset($result['user_ids']) && !empty($result['user_ids'])) {
            $result['user_ids'] = explode('|', $result['user_ids']);
        }
        if (isset($result['dept_ids']) && !empty($result['dept_ids'])) {
            $result['dept_ids'] = explode('|', $result['dept_ids']);
        }
        if (isset($result['role_ids']) && !empty($result['role_ids'])) {
            $result['role_ids'] = explode('|', $result['role_ids']);
        }
        return $result;
    }

    public static function showGroupRules(int $groupId)
    {
        $rsRuleDatas = $rsUserDatas = [];
        $ruleLists   = DB::table('customer_seas_rule')->where('seas_group_id', $groupId)->orderBy('id', 'asc')->get();
        if ($ruleLists->isEmpty()) {
            return [$rsRuleDatas, $rsUserDatas];
        }
        $ruleIds = [];
        foreach ($ruleLists as $key => $item) {
            $ruleIds[] = $item->id;
        }
        $ruleUserLists = DB::table('customer_seas_user')->whereIn('seas_rule_id', $ruleIds)->get();
        $userIds       = [];
        foreach ($ruleLists as $ruleKey => $ruleItem) {
            $tempItem = (array) $ruleItem;
            if (!empty($ruleUserLists)) {
                foreach ($ruleUserLists as $userKey => $userItem) {
                    if ($userItem->seas_rule_id == $tempItem['id']) {
                        $userIds[]                             = $userItem->user_id;
                        $tempItem['user_ids'][]                = $userItem->user_id;
                        $tempItem['users'][$userItem->user_id] = $userItem->limit_number;
                        unset($ruleUserLists[$userKey]);
                    }
                }
            }
            $rsRuleDatas[] = $tempItem;
        }
        // 获取用户名称
        $userNameLists = DB::table('user')->select(['user_id', 'user_name'])->whereIn('user_id', $userIds)->get();
        if (!$userNameLists->isEmpty()) {
            foreach ($userNameLists as $key => $item) {
                $userIdNameLists[$item->user_id] = $item->user_name;
            }
            $rsUserDatas = $userIdNameLists;
        }
        return [$rsRuleDatas, $rsUserDatas];
    }

    public static function getRecycleDatas()
    {
        $result = [];
        $lists  = DB::table(self::TABLE_NAME)->select(['id', 'recycle_record', 'recycle_prev_follow','reminds','limit_months'])->where('recycle_record', '>', 0)->orWhere('recycle_prev_follow', '>', 0)->get();
        if (!$lists->isEmpty()) {
            foreach ($lists as $index => $item) {
                $result[$item->id] = [
                    'recycle_record'      => $item->recycle_record * 86400,
                    'recycle_prev_follow' => $item->recycle_prev_follow * 86400,
                    'reminds'             => $item->reminds * 86400,
                    'limit_months'        => $item->limit_months ? date('Y-m-d H:i:s',strtotime((-$item->limit_months)." month")) : 0,
                ];
            }
        }
        return $result;
    }

    /**
     * 根据公海规则分配客户
     * @return bool
     */
    public static function distributeCustomer(int $groupId, array $customerIds = [])
    {
        if (!$data = self::getDistributeRuleDatas($groupId, $customerIds)) {
            return false;
        }
        foreach ($data as $ruleId => $item) {
            if (empty($item)) {
                continue;
            }
            foreach ($item as $tUserId => $tCustomerIds) {
                self::distributeCustomerToUser($groupId, $ruleId, $tCustomerIds, $tUserId);
                foreach ($tCustomerIds as $ids => $vo){
                    $customer_name = DB::table(self::TABLE_CUSTOMER)->where(['customer_id' => $vo])->value('customer_name');
                    $sendData['remindMark']           = 'customer-change';
                    $sendData['toUser']               = $tUserId;
                    $sendData['contentParam']         = ['customerName' => $customer_name];
                    $sendData['stateParams']          = ['customer_id' => $vo];
                    Eoffice::sendMessage($sendData);
                    // 写入查看日志
                    $identify = 'customer.customer_info.distribute';
                    CustomerRepository::saveLogs($vo, trans('customer.auto_distribute').'：'.$customer_name, own()['user_id'] ?? $tUserId,$identify,$customer_name);
                }
                // 写入回收表
                SeasGroupRepository::insertRecycles($tCustomerIds,3);
            }
        }
        return true;
    }

    // 分配客户给用户
    public static function distributeCustomerToUser($groupId, $ruleId, array $customerIds, $userId)
    {
        if (empty($customerIds)) {
            return true;
        }
        $query  = DB::table(self::TABLE_CUSTOMER)->whereIn('customer_id', $customerIds)->where('customer_manager', '');
        $count  = $query->count();
        $result = $query->update(['customer_manager' => $userId, 'last_distribute_time' => time()]);
        if (!$result) {
            return false;
        }
        // 用户的已分配数量增加
        DB::table(self::TABLE_USER_RULE)->where('seas_rule_id', $ruleId)->where('user_id', $userId)->increment('distribute_count', count($customerIds));
        self::updateWaitDistributeCount($groupId, -$count);
        self::updateNewDistributeCount($groupId, $count);
        return true;
    }

    private static function allWaitDistributeCustomerIds($groupId, array $customerIds = [])
    {
        $result = [];
        $query  = DB::table(self::TABLE_CUSTOMER)->where('customer_manager', '')->where('seas_group_id', $groupId);
        if (!empty($customerIds)) {
            $query = $query->whereIn('customer_id', $customerIds);
        }
        $lists = $query->pluck('customer_id');
        if (!$lists->isEmpty()) {
            $result = $lists->toArray();
        }
        return $result;
    }

    /**
     * 获取客户匹配的公海规则id
     * @return array
     * [ruleId => [user_id => [customer_id1, 2, 3], xxx]]
     */
    public static function getDistributeRuleDatas(int $groupId, array $waitCustomerIds)
    {
        $lists = DB::table(self::TABLE_RULE)->select(['id', 'rule_text'])->where('seas_group_id', $groupId)->get();
        if ($lists->isEmpty()) {
            return false;
        }
        // 只有客户经理为空的客户才可以进行分配
        $customerIds = self::allWaitDistributeCustomerIds($groupId, $waitCustomerIds);
        if(!$customerIds){
            return false;
        }
//        if (!empty($waitCustomerIds) && count($waitCustomerIds) !== count($customerIds)) {
//            return false;
//        }
        try {
            $result = [];
            $hasLeaveUserIds = null;
            foreach ($lists as $index => $item) {
                if (!$validate = self::testSelectSql($item->rule_text)) {
                    continue;
                }
                if (empty($customerIds)) {
                    break;
                }
                $sCustomerIds = implode("','", $customerIds);
                $sql          = preg_replace('/where/iU', "WHERE customer.customer_id IN ('{$sCustomerIds}') AND (", $item->rule_text, 1);
                $sql = trim($sql);
                if (!strpos($sql, 'WHERE')) {
                    if (!strpos($sql, ';')) {
                        $sql .= ";";
                    }
                    $sql = preg_replace('/;$/U', " WHERE customer.customer_id IN ('{$sCustomerIds}');", $sql, 1);
                } else {
                    if (($lastChr = mb_substr($sql, -1)) === ';') {
                        $sql = preg_replace('/;$/U', ");", $sql, 1);
                    } else {
                        $sql .= ");";
                    }
                }
                $sSql       = "SELECT customer_id FROM customer " . mb_substr($sql, mb_stripos($sql, 'WHERE'));
                $tCustomers = DB::select($sSql);
                if (empty($tCustomers)) {
                    continue;
                }
                foreach ($tCustomers as $icIndex => $icItem) {
                    if ($hasLeaveUserIds === null) {
                        $hasLeaveUserIds = UserService::getInvalidUserIds();
                    }
                    // 用户离职,不分配
                    $number = 0;
                    do {
                        $userId = self::getNextDistributeUserId($item->id);
                        $number++;
                    } while ($userId && in_array($userId, $hasLeaveUserIds) && $number < 10);
                    if (!$userId || $number >= 10) {
                        break;
                    }
                    if (!isset($result[$item->id][$userId])) {
                        $result[$item->id][$userId] = [];
                    }
                    $result[$item->id][$userId][] = $icItem->customer_id;
                    // 已经分配完毕，删除待分配的客户
                    $customerIds = array_diff($customerIds, [$icItem->customer_id]);
                }
            }
        } catch (\Exception $e) {
            return false;
        }
        return $result;
    }

    /**
     * 根据规则id获取规则下一个需要分配给的用户
     * @return userId
     */
    private static function getNextDistributeUserId($ruleId)
    {
        static $doneFlag = [];
        // 按比例分配，没有达到100%，暂时也都按100%为基数
        if (!Redis::exists(self::REDIS_DISTRIBUTE_USER . $ruleId)) {
            if (in_array($ruleId, $doneFlag)) {
                return '';
            }
            $doneFlag[] = $ruleId;
            if (!$validate = self::refreshDisbuteRedis($ruleId)) {
                return '';
            }
        }
        // 如果里面只有一位了，那就是没有可以分配的用户了
        if (($tlen = Redis::llen(self::REDIS_DISTRIBUTE_USER . $ruleId)) === 1) {
            return '';
        }
        // 移除并返回第一个头元素
        if (!$userId = Redis::lpop(self::REDIS_DISTRIBUTE_USER . $ruleId)) {
            return '';
        }
        // 如果没有周期，就需要重新加入lists

        if ($userId && Redis::ttl(self::REDIS_DISTRIBUTE_USER . $ruleId) == -1) {
            Redis::rpop(self::REDIS_DISTRIBUTE_USER . $ruleId);
            Redis::rpush(self::REDIS_DISTRIBUTE_USER . $ruleId, $userId);// 在尾部添加元素
            Redis::rpush(self::REDIS_DISTRIBUTE_USER . $ruleId, self::NERVER_USER);// 在尾部添加元素(最尾部添加 index 0 value -1)
        }
        return $userId;
    }

    /**
     * 加权轮询算法
     * 将一整套循环完整的push到redis的lists中
     */
    private static function refreshDisbuteRedis($ruleId)
    {
        $list = DB::table(self::TABLE_RULE)->select(['id', 'distribute_type', 'limit_date', 'updated_at'])->where('id', $ruleId)->first();
        if (empty($list)) {
            return false;
        }
        $userLists = DB::table(self::TABLE_USER_RULE)->select(['user_id', 'limit_number', 'distribute_count'])->where('seas_rule_id', $ruleId)->get();
        if ($userLists->isEmpty()) {
            return false;
        }
        $pushData   = [];
        $expireTime = '';
        switch ($list->distribute_type) {
            case self::DISTRIBUTE_TYPE_AVERAGE:
                foreach ($userLists as $index => $item) {
                    $pushData[$item->user_id] = $item->distribute_count;
                }
                asort($pushData);
                $pushData = array_keys($pushData);
                break;

            case self::DISTRIBUTE_TYPE_NUMBER:
                $count = array_sum(array_column($userLists->toArray(), 'limit_number'));
                // 用户可分配当前权重，原始权重，已分配数量
                $userMachines = $userLimits = $userHasDistribute = [];
                foreach ($userLists as $index => $item) {
                    $userMachines[$item->user_id] = $item->limit_number;
                    $userLimits[$item->user_id]   = $item->limit_number;
                    // 获取用户已经分配到的总量
                    $userHasDistribute[$item->user_id] = $item->distribute_count;
                }
                do {
                    $nextUserId = array_search(max($userMachines), $userMachines);
                    // 超过可分配上限
                    if (++$userHasDistribute[$nextUserId] > $userLimits[$nextUserId]) {
                        unset($userMachines[$nextUserId]);
                        continue;
                    }
                    $pushData[] = $nextUserId;
                    $userMachines[$nextUserId] -= $count;
                    $flagArr = array_count_values(array_values($userMachines));
                    foreach ($userMachines as $iUserId => $iValue) {
                        $userMachines[$iUserId] += $userLimits[$iUserId];
                    }
                } while (!empty($userMachines));
                $expireTime = self::getRedisExpireTime($list->updated_at, $list->limit_date);
                break;

            case self::DISTRIBUTE_TYPE_PROPORTION:
                $count        = array_sum(array_column($userLists->toArray(), 'limit_number'));
                $userMachines = $userLimits = [];
                foreach ($userLists as $index => $item) {
                    $userMachines[$item->user_id] = $item->limit_number;
                    $userLimits[$item->user_id]   = $item->limit_number;
                }
                do {
                    $nextUserId = array_search(max($userMachines), $userMachines);
                    $pushData[] = $nextUserId;
                    $userMachines[$nextUserId] -= $count;
                    $flagArr = array_count_values(array_values($userMachines));
                    foreach ($userMachines as $iUserId => $iValue) {
                        $userMachines[$iUserId] += $userLimits[$iUserId];
                    }
                } while (!isset($flagArr[0]) || count($flagArr) > 1);
                break;

            default:
                # code...
                break;
        }
        Redis::del(self::REDIS_DISTRIBUTE_USER . $ruleId);
        while ($nextUserId = array_pop($pushData)) {
            Redis::rpush(self::REDIS_DISTRIBUTE_USER . $ruleId, $nextUserId);
        }
        Redis::rpush(self::REDIS_DISTRIBUTE_USER . $ruleId, self::NERVER_USER);
        if ($expireTime) {
            Redis::expire(self::REDIS_DISTRIBUTE_USER . $ruleId, $expireTime);
        }
        return true;
    }

    /**
     * 获取规则周期的过期时间
     */
    private static function getRedisExpireTime($updatedAt, $limitDate)
    {
        $result = '';
        if (!$limitDate) {
            return $result;
        }
        $nowTime     = time();
        $updatedTime = strtotime($updatedAt);
        $n           = 1;
        while (($lastExpireTime = $updatedTime + $n * $limitDate * 86400) < $nowTime) {
            ++$n;
        }
        return $lastExpireTime - $nowTime;
    }

    private static function testSelectSql(string $sql)
    {
        $sql = trim($sql);
        if (!$sql) {
            return false;
        }
        if (preg_match('/insert\s+|update\s+|delete\s+|union|into|load_file|outfile/', $sql)) {
            return false;
        }
        try {
            $sql = preg_replace('/where/iU', "WHERE customer_id = 1 AND ", $sql, 1);
            if (!strpos($sql, 'WHERE')) {
                if (strpos($sql, ';')) {
                    $sql = preg_replace('/;/U', " WHERE customer_id = 1;", $sql, 1);
                } else {
                    $sql .= " WHERE customer_id = 1;";
                }
            }
            DB::select($sql);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 公海存在客户，不可删除
     */
    public static function validateDeletes(int $groupId)
    {
        $result = DB::table(self::TABLE_CUSTOMER)->where('seas_group_id', $groupId)->whereNull('deleted_at')->exists();
        return !$result;
    }

    public static function checkGroupPermission(int $groupId, $userId)
    {
        $list = DB::table(self::TABLE_NAME)->select(['open_type', 'manager_ids', 'user_ids', 'dept_ids', 'role_ids', 'distribute_type'])->find($groupId);
        if (empty($list)) {
            return false;
        }
        if (in_array($userId, explode('|', $list->manager_ids))) {
            return true;
        }
        // if (!($list->distribute_type & self::OPEND_AUTO_PICK)) {
        //     return false;
        // }
        if ($list->open_type || in_array($userId, explode('|', $list->user_ids))) {
            return true;
        }
        $userList = app('App\EofficeApp\User\Services\UserService')->getUserDeptIdAndRoleIdByUserId($userId);
        if (empty($userList)) {
            return false;
        }
        if ($userList['dept_id'] && $list->dept_ids && in_array($userList['dept_id'], explode('|', $list->dept_ids))) {
            return true;
        }
        if (!$userList['role_id']) {
            return false;
        }
        $userRoleIds = explode(',', $userList['role_id']);
        if (!empty($userRoleIds) && $list->role_ids && !empty(array_intersect($userRoleIds, explode('|', $list->role_ids)))) {
            return true;
        }
        return false;
    }

    public static function seasSetting($data,$own){

        return DB::table(self::TABLE_SEAS_PUBLIC)->insert($data);
    }

    public static function getSetting(){

        return DB::table(self::TABLE_SEAS_PUBLIC)->first();
    }

    public static function updateSetting($data,$id){
        return DB::table(self::TABLE_SEAS_PUBLIC)->where('id',$id)->update($data);
    }
}
