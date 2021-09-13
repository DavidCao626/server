<?php
namespace App\EofficeApp\Customer\Repositories;

use App\EofficeApp\Base\BaseRepository;
use DB;
use Illuminate\Support\Facades\Redis;
use App\EofficeApp\Customer\Repositories\CustomerRepository;

class ShareCustomerRepository extends BaseRepository
{

    const TABLE_USER_SHARE = 'customer_permission_user';
    const TABLE_DEPT_SHARE = 'customer_permission_department';
    const TABLE_ROLE_SHARE = 'customer_permission_role';

    const TABLE_CUSTOMER = 'customer';
    const REDIS_SOFT_CUSTOEMR_IDS = 'customer:had_soft_customers';

    const MAX_WHERE_IN = 2000;

    const SHARE_CUSTOMER_KEY = 'customer:share';
    const SHARE_CUSTOMER_USER_KEY = 'customer:share_user';
    const SHARE_CUSTOMER_DEPT_KEY = 'customer:share_dept';
    const SHARE_CUSTOMER_ROLE_KEY = 'customer:share_role';

    // 获取分享的客户
    public static function getShareCustomerIds($own, &$params = [])
    {
        $result  = $userCustomerIds = $deptCustomerIds = $roleCustomerIds = [];
        $userId  = $own['user_id'] ?? '';
        $deptId  = $own['dept_id'] ?? '';
        $roleIds = $own['role_id'] ?? [];
        if ($userId) {
            $userCustomerIds = json_decode(Redis::hget(self::SHARE_CUSTOMER_USER_KEY,$userId),true);
            if(empty($userCustomerIds)){
                if($userCustomerIds = self::getCustomerIdsByUserId($userId)){
                    Redis::hset(self::SHARE_CUSTOMER_USER_KEY,$userId,json_encode($userCustomerIds));
                    Redis::expire(self::SHARE_CUSTOMER_USER_KEY,3600*5);
                }
            }
        }
        if ($deptId) {
            $deptCustomerIds = json_decode(Redis::hget(self::SHARE_CUSTOMER_DEPT_KEY,$deptId),true);
            if(empty($deptCustomerIds)){
                if($deptCustomerIds = self::getCustomerIdsByDeptId($deptId)){
                    Redis::hset(self::SHARE_CUSTOMER_DEPT_KEY,$deptId,json_encode($deptCustomerIds));
                    Redis::expire(self::SHARE_CUSTOMER_USER_KEY,3600*5);
                };
            }
        }
        if ($roleIds) {
            $roleKey = implode(',',$roleIds);
            $roleCustomerIds = json_decode(Redis::hget(self::SHARE_CUSTOMER_ROLE_KEY,$roleKey),true);
            if(empty($roleCustomerIds)){
                if($roleCustomerIds = self::getCustomerIdsByRoleIds($roleIds)){
                    Redis::hset(self::SHARE_CUSTOMER_ROLE_KEY,$roleKey,json_encode($roleCustomerIds));
                    Redis::expire(self::SHARE_CUSTOMER_USER_KEY,3600*5);
                };
            }
        }
        $result = array_merge($userCustomerIds,$deptCustomerIds,$roleCustomerIds);
        $result = array_unique($result);
        if (isset($params['search']) && !empty($params['search'])) {
            // 如果是customer_id检索，则直接取交集即可
            if(isset($params['search']['customer_id'])){
                $customer_id = $params['search']['customer_id'][0] ?? [];
                $customer_id = is_array($customer_id) ? $customer_id : [$customer_id];
                if($customer_id){
                    // 取交集
                    $result = array_intersect($customer_id, $result);
                }
            }else{
                if($result){
                    $query  = DB::table('customer')->select(['customer_id']);
                    $query = self::tempTableJoin($query, $result);
                    $query  = app('App\EofficeApp\System\CustomFields\Repositories\FieldsRepository')->wheres($query, $params['search']);
                    $lists  = $query->whereNull('deleted_at')->pluck('customer_id')->toArray();
                    $result = $lists ? $lists :[];
                }
            }
            unset($params['search']);
        }
        // 去除软删除的客户
        return self::clearSoftCustomers($result);
    }

    public static function clearSoftCustomers(array $customerIds)
    {
        $allSoftCustomerIds = CustomerRepository::getSoftCustomerIds();
        return array_unique(array_diff($customerIds, $allSoftCustomerIds));
    }
    
    public static function getCustomerIdsByRoleIds(array $roleIds)
    {
        if (empty($roleIds)) {
            return [];
        }
        $lists = DB::table(self::TABLE_ROLE_SHARE)->whereIn('role_id', $roleIds)->pluck('customer_id');
        return $lists->toArray();
    }

    public static function getCustomerIdsByUserId($userId)
    {
        if (!$userId) {
            return [];
        }
        $lists = DB::table(self::TABLE_USER_SHARE)->where(['user_id' => $userId])->pluck('customer_id');
        return $lists->toArray();
    }

    public static function getCustomerIdsByDeptId($deptId)
    {
        if (!$deptId) {
            return [];
        }
        $lists = DB::table(self::TABLE_DEPT_SHARE)->where(['dept_id' => $deptId])->pluck('customer_id');
        return $lists->toArray();
    }

    public static function storeShareUsers(int $customerId, array $userIds)
    {
        $insertData = [];
        $lists      = DB::table(self::TABLE_USER_SHARE)->select(['user_id'])->where('customer_id', $customerId)->whereIn('user_id', $userIds)->get();
        if (!$lists->isEmpty()) {
            $existUserIds = array_unique(array_column($lists->toArray(), 'user_id'));
            $userIds      = array_diff($userIds, $existUserIds);
        }
        if (!empty($userIds)) {
            foreach ($userIds as $userId) {
                $insertData[] = [
                    'user_id'     => $userId,
                    'customer_id' => $customerId,
                ];
            }
            DB::table(self::TABLE_USER_SHARE)->insert($insertData);
            ShareCustomerRepository::parseTempShareIds(own());
        }
        return true;
    }

    public static function parseTempShareIds($own = null){
      
        Redis::del(self::SHARE_CUSTOMER_USER_KEY);
        Redis::del(self::SHARE_CUSTOMER_DEPT_KEY);
        Redis::del(self::SHARE_CUSTOMER_ROLE_KEY);
    }

    public static function getUserByData($table,$column,$deptIds){
        return DB::table($table)->whereIn($column,$deptIds)->pluck('user_id')->toArray();
    }

    // 差异化插入部门分享客户
    public static function diffInsertDeptIds(array $customerIds, array $deptIds, bool $deleteFlag = false)
    {
        $iLists = $dLists = [];
        if (!empty($deptIds)) {
            foreach ($customerIds as $iCustomerId) {
                $iLists[$iCustomerId] = $deptIds;
            }
        }

        // 需要覆盖之前的分享范围
        if ($deleteFlag) {
            DB::table(self::TABLE_DEPT_SHARE)->whereIn('customer_id', $customerIds)->whereNotIn('dept_id', $deptIds)->delete();
        }
        // 获取已经分享的部门
        $lists = DB::table(self::TABLE_DEPT_SHARE)->whereIn('customer_id', $customerIds)->get();
        if (!$lists->isEmpty()) {
            $dLists = [];
            foreach ($lists as $key => $value) {
                if (in_array($value->dept_id, $iLists[$value->customer_id])) {
                    if (!isset($dLists[$value->customer_id])) {
                        $dLists[$value->customer_id] = [];
                    }
                    $dLists[$value->customer_id][] = $value->dept_id;
                }
            }
            if (!empty($dLists)) {
                foreach ($dLists as $dCustomerId => $dIds) {
                    if (!isset($iLists[$dCustomerId]) || empty($iLists[$dCustomerId])) {
                        continue;
                    }
                    $iLists[$dCustomerId] = array_diff($iLists[$dCustomerId], $dIds);
                }
            }
        }
        // 插入分享部门
        if (!empty($iLists)) {
            foreach ($iLists as $iCustomerId => $iIds) {
                if (empty($iIds)) {
                    continue;
                }
                foreach ($iIds as $iId) {
                    $insertData[] = ['customer_id' => $iCustomerId, 'dept_id' => $iId];
                }
            }
            if (!empty($insertData)) {
                DB::table(self::TABLE_DEPT_SHARE)->insert($insertData);
            }
        }
        return true;
    }

    // 差异化插入部门分享客户
    public static function diffInsertRoleIds(array $customerIds, array $roleIds, bool $deleteFlag = false)
    {
        $iLists = $dLists = [];
        if (!empty($roleIds)) {
            foreach ($customerIds as $iCustomerId) {
                $iLists[$iCustomerId] = $roleIds;
            }
        }

        // 需要覆盖之前的分享范围
        if ($deleteFlag) {
            DB::table(self::TABLE_ROLE_SHARE)->whereIn('customer_id', $customerIds)->whereNotIn('role_id', $roleIds)->delete();
        }
        // 获取已经分享的部门
        $lists = DB::table(self::TABLE_ROLE_SHARE)->whereIn('customer_id', $customerIds)->get();
        if (!$lists->isEmpty()) {
            $dLists = [];
            foreach ($lists as $key => $value) {
                if (in_array($value->role_id, $iLists[$value->customer_id])) {
                    if (!isset($dLists[$value->customer_id])) {
                        $dLists[$value->customer_id] = [];
                    }
                    $dLists[$value->customer_id][] = $value->role_id;
                }
            }
            if (!empty($dLists)) {
                foreach ($dLists as $dCustomerId => $dIds) {
                    if (!isset($iLists[$dCustomerId]) || empty($iLists[$dCustomerId])) {
                        continue;
                    }
                    $iLists[$dCustomerId] = array_diff($iLists[$dCustomerId], $dIds);
                }
            }
        }
        // 插入分享部门
        if (!empty($iLists)) {
            foreach ($iLists as $iCustomerId => $iIds) {
                if (empty($iIds)) {
                    continue;
                }
                foreach ($iIds as $iId) {
                    $insertData[] = ['customer_id' => $iCustomerId, 'role_id' => $iId];
                }
            }
            if (!empty($insertData)) {
                DB::table(self::TABLE_ROLE_SHARE)->insert($insertData);
            }
        }
        return true;
    }

    // 差异化插入用户分享客户
    public static function diffInsertUserIds(array $customerIds, array $userIds, bool $deleteFlag = false)
    {
        $iLists = $dLists = [];
        if (!empty($userIds)) {
            foreach ($customerIds as $iCustomerId) {
                $iLists[$iCustomerId] = $userIds;
            }
        }

        // 需要覆盖之前的分享范围
        if ($deleteFlag) {
            DB::table(self::TABLE_USER_SHARE)->whereIn('customer_id', $customerIds)->whereNotIn('user_id', $userIds)->delete();
        }
        // 获取已经分享的部门，移除不需要新增的用户
        $lists = DB::table(self::TABLE_USER_SHARE)->whereIn('customer_id', $customerIds)->get();
        if (!$lists->isEmpty()) {
            $dLists = [];
            foreach ($lists as $key => $value) {
                if (in_array($value->user_id, $iLists[$value->customer_id])) {
                    if (!isset($dLists[$value->customer_id])) {
                        $dLists[$value->customer_id] = [];
                    }
                    $dLists[$value->customer_id][] = $value->user_id;
                }
            }
            if (!empty($dLists)) {
                foreach ($dLists as $dCustomerId => $dIds) {
                    if (!isset($iLists[$dCustomerId]) || empty($iLists[$dCustomerId])) {
                        continue;
                    }
                    $iLists[$dCustomerId] = array_diff($iLists[$dCustomerId], $dIds);
                }
            }
        }
        // 插入分享部门
        if (!empty($iLists)) {
            foreach ($iLists as $iCustomerId => $iIds) {
                if (empty($iIds)) {
                    continue;
                }
                foreach ($iIds as $iId) {
                    $insertData[] = ['customer_id' => $iCustomerId, 'user_id' => $iId];
                }
            }
            if (!empty($insertData)) {
                DB::table(self::TABLE_USER_SHARE)->insert($insertData);
            }
        }
        return true;
    }

    /**
     * 获取客户的所有分享的用户，部门，角色二维数组
     * @return array
     */
    public static function getCustomerShareIds(int $customerId)
    {
        $lists   = DB::table(self::TABLE_USER_SHARE)->select(['user_id'])->where('customer_id', $customerId)->get();
        $userIds = array_unique(array_column($lists->toArray(), 'user_id'));

        $lists   = DB::table(self::TABLE_DEPT_SHARE)->select(['dept_id'])->where('customer_id', $customerId)->get();
        $deptIds = array_unique(array_column($lists->toArray(), 'dept_id'));

        $lists   = DB::table(self::TABLE_ROLE_SHARE)->select(['role_id'])->where('customer_id', $customerId)->get();
        $roleIds = array_unique(array_column($lists->toArray(), 'role_id'));
        return [array_merge($userIds), array_merge($deptIds), array_merge($roleIds)];
    }

    public static function deleteSharesByParams(array $customerIds,$params,$table) : void {
        switch ($table){
            case self::TABLE_USER_SHARE:
                DB::table(self::TABLE_USER_SHARE)->whereIn('customer_id', $customerIds)->whereIn('user_id',$params)->delete();
                break;
            case self::TABLE_DEPT_SHARE:
                DB::table(self::TABLE_DEPT_SHARE)->whereIn('customer_id', $customerIds)->whereIn('dept_id',$params)->delete();
                break;
            case self::TABLE_ROLE_SHARE:
                DB::table(self::TABLE_ROLE_SHARE)->whereIn('customer_id', $customerIds)->whereIn('role_id',$params)->delete();
                break;
        }
    }

    public static function deleteAllShares(array $customerIds)
    {
        // 获取用户分享表对应user_id
//        $userIds = DB::table(self::TABLE_USER_SHARE)->whereIn('customer_id', $customerIds)->pluck('user_id')->toArray();
        // 获取部门分享表对应dept_id
//        $deptIds = DB::table(self::TABLE_DEPT_SHARE)->whereIn('customer_id', $customerIds)->pluck('dept_id')->toArray();
        // 获取角色分享表对应role_id
//        $roleIds = DB::table(self::TABLE_ROLE_SHARE)->whereIn('customer_id', $customerIds)->pluck('role_id')->toArray();

        DB::table(self::TABLE_USER_SHARE)->whereIn('customer_id', $customerIds)->delete();
        DB::table(self::TABLE_DEPT_SHARE)->whereIn('customer_id', $customerIds)->delete();
        DB::table(self::TABLE_ROLE_SHARE)->whereIn('customer_id', $customerIds)->delete();
        self::parseTempShareIds(own());
        return true;
    }

    // sql 太长导致mysql gone away
    public static function tempTableJoin($query, &$searchs)
    {
        $whereValues = $searchs ?? [];
        if (!empty($whereValues) && count($whereValues) > self::MAX_WHERE_IN) {
            $tableName = 'customer_'.rand() . uniqid();
            DB::statement("CREATE TEMPORARY TABLE if not exists {$tableName} (`data_id` int(6) NOT NULL,PRIMARY KEY (`data_id`))");
            $tempIds = array_chunk($whereValues, self::MAX_WHERE_IN, true);
            foreach ($tempIds as $key => $item) {
                $ids      = implode("),(", $item);
                $tSql = "insert into {$tableName} (data_id) values ({$ids});";
                DB::insert($tSql);
            }
            $query = $query->join("$tableName", $tableName . ".data_id", '=', 'customer_id');
            unset($searchs['customer_id']);
        }else if($whereValues){
            $query = $query->whereIn('customer_id',$whereValues);
        }
        return $query;
    }

    public static function getShareIdByCustomerIds($table,$ids){
        return DB::table($table)->whereIn('customer_id',$ids)->pluck('customer_id')->toArray();
    }
}
