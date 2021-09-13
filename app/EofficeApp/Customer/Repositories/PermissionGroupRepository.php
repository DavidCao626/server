<?php

namespace App\EofficeApp\Customer\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Customer\Entities\PermissionGroupEntity;
use App\EofficeApp\Customer\Repositories\CustomerRepository;
use App\EofficeApp\User\Services\UserService;
use DB;
use Illuminate\Support\Facades\Redis;

class PermissionGroupRepository extends BaseRepository
{
    // 客户经理标识
    const GROUP_MANAGER_TEXT = 'customer_manager';
    // 客服经理标识
    const GROUP_SERVICE_TEXT = 'customer_service_manager';
    // 穿透无限
    const THROUGH_ALL = 2;
    // 穿透一级
    const THROUGH_ONW = 1;

    const TABLE_NAME = 'customer_permission_group';

    // 权限组redis hash ， key 为 角色id
    const PERMISSION_GROUP = 'customer:permission_group';

    public function __construct(PermissionGroupEntity $entity)
    {
        parent::__construct($entity);
    }

    public function lists(array $param = [])
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
        return $this->entity->select($param['fields'])->multiWheres($param['search'])->orders($param['order_by'])->parsePage($param['page'], $param['limit'])->get()->toArray();
    }

    /**
     * 获取所有的权限组
     * @param  array  $param
     * @return Array
     */
    public function allLists(array $param = [])
    {
        $default = [
            'fields'   => ['id', 'name'],
            'page'     => 0,
            'search'   => [],
            'order_by' => ['id' => 'asc'],
        ];
        $param = array_merge($default, $param);

        return $this->entity->select($param['fields'])->multiWheres($param['search'])->orders($param['order_by'])->get()->toArray();
    }

    /**
     * 获取权限组总数
     * @param  array  $param
     * @return Int
     */
    public function allTotal(array $param = [])
    {
        $default = [
            'fields'   => ['id', 'name'],
            'page'     => 0,
            'search'   => [],
            'order_by' => ['id' => 'asc'],
        ];
        $param = array_merge($default, $param);
        return $this->entity->multiWheres($param['search'])->count();
    }

    /**
     * 获取权限组详情
     * @param  Int $group_id 权限组id
     * @return Array
     */
    public function show($group_id)
    {
        $list = $this->entity->find($group_id);
        if (empty($list)) {
            return [];
        }
        if ($list->user_ids) {
            $list->user_ids = explode(',', $list->user_ids);
        }
        if ($list->dept_ids) {
            $list->dept_ids = explode(',', $list->dept_ids);
        }
        if ($list->role_ids) {
            $list->role_ids = explode(',', $list->role_ids);
        }
        if ($list->type_text) {
            $list->type_text = explode(',', $list->type_text);
            $temp_arr        = [];
            foreach ($list->type_text as $item) {
                $temp_arr[$item] = 1;
            }
            $list->type_text = $temp_arr;
        }
        if ($list->own_type_text) {
            $list->own_type_text = explode(',', $list->own_type_text);
            $temp_arr            = [];
            foreach ($list->own_type_text as $item) {
                $temp_arr[$item] = 1;
            }
            $list->own_type_text = $temp_arr;
        }
        if ($list->detail_permission) {
            $list->detail_permission = explode(',', $list->detail_permission);
            $temp_arr                = [];
            foreach ($list->detail_permission as $item) {
                $temp_arr[$item] = 1;
            }
            $list->detail_permission = $temp_arr;
        }
        if ($list->own_detail_permission) {
            $list->own_detail_permission = explode(',', $list->own_detail_permission);
            $temp_arr                    = [];
            foreach ($list->own_detail_permission as $item) {
                $temp_arr[$item] = 1;
            }
            $list->own_detail_permission = $temp_arr;
        }
        return $list;
    }

    /**
     * 通过用户角色ids集合获取权限组
     * @param  Array $ids
     * @return Object
     */
    public static function getListsByRoleIds(array $roleIds)
    {
        $groupIds = self::getGroupIdsByRoleIds($roleIds);
        return DB::table('customer_permission_group')->whereIn('id', $groupIds)->get();
    }

    // 通过用户角色ids集合获取权限id集合
    public static function getGroupIdsByRoleIds(array $roleIds)
    {
        $result = [];
        if (empty($roleIds)) {
            return $result;
        }
        if (!$exist = Redis::hlen(self::PERMISSION_GROUP)) {
            self::refreshPermissionGroup();
        }
        $result = Redis::hmget(self::PERMISSION_GROUP, $roleIds);
        return $result;
    }

    // 刷新权限组
    public static function refreshPermissionGroup()
    {
        $lists = DB::table('customer_permission_group_role')->select('role_id', 'group_id')->get();
        if ($lists->isEmpty()) {
            return true;
        }
        $result = [];
        foreach ($lists as $index => $item) {
            $result[$item->role_id] = $item->group_id;
        }
        if (!empty($result)) {
            Redis::hmset(self::PERMISSION_GROUP, $result);
        }
        return true;
    }

    /**
     * 根据当前用户获取权限组的具体信息
     * 返回四个参数分别表示全体和具体id
     * @return [$managerAllFlag, $serviveAllFlag, $managerIds, $serviceIds]
     */
    public static function parseGroupLists(int $type, array $own)
    {
        $userId  = $own['user_id'] ?? '';
        $deptId  = $own['dept_id'] ?? '';
        $roleIds = $own['role_id'] ?? [];
        // 客户经理和客服经理是否全体成员
        $managerAllFlag = $serviveAllFlag = false;
        $managerIds     = $serviceIds     = [$userId];
        $groupLists     = self::getListsByRoleIds($roleIds);
        $userSubordinateIds = [];
        if (!$groupLists->isEmpty()) {
            foreach ($groupLists as $index => $iGroup) {
                if ($managerAllFlag && $serviveAllFlag) {
                    break;
                }
                // 关联用户权限
                $tempTypeTexts = explode(',', $iGroup->type_text);
                if (!empty($tempTypeTexts) && ($type === CustomerRepository::VIEW_MARK || $iGroup->detail_permission)) {
                    // $userIds 已经计算了是否穿透用户上下级
                    list($tempManagerAllFlag, $tempServiveAllFlag, $userIds) = self::parseGroup($iGroup, $own);
                    // 勾选了全体直接跳过此次循环
                    $managerAllFlag = $managerAllFlag || $tempManagerAllFlag;
                    $serviveAllFlag = $serviveAllFlag || $tempServiveAllFlag;
                    if ($managerAllFlag && $serviveAllFlag) {
                        break;
                    }
                    if (!$managerAllFlag && in_array(self::GROUP_MANAGER_TEXT, $tempTypeTexts)) {
                        $managerIds = array_merge($managerIds, $userIds);
                    }
                    if (!$serviveAllFlag && in_array(self::GROUP_SERVICE_TEXT, $tempTypeTexts)) {
                        $serviceIds = array_merge($serviceIds, $userIds);
                    }
                }
                // 关联角色权限
                $tempTypeTexts = explode(',', $iGroup->own_type_text);
                if (!empty($tempTypeTexts) && ($type === CustomerRepository::VIEW_MARK || $iGroup->own_detail_permission)) {
                    $userIds = [$userId];
                    // 穿透
                    if ($iGroup->own_through_type) {
                        $flag    = $iGroup->own_through_type == self::THROUGH_ALL ? true : false;
                        if (!isset($userSubordinateIds[$iGroup->own_through_type])) {
                            $userIds = UserService::getUserSubordinateIds($userIds, $flag);
                            $userSubordinateIds[$iGroup->own_through_type] = $userIds;
                        }
                        $userIds = $userSubordinateIds[$iGroup->own_through_type];
                    }
                    if (in_array(self::GROUP_MANAGER_TEXT, $tempTypeTexts)) {
                        $managerIds = array_merge($managerIds, $userIds);
                    }
                    if (in_array(self::GROUP_SERVICE_TEXT, $tempTypeTexts)) {
                        $serviceIds = array_merge($serviceIds, $userIds);
                    }
                }
            }
        } else {
            $userIds = UserService::getUserSubordinateIds([$userId]);
            $managerIds = array_merge($managerIds, $userIds);
        }
        return [$managerAllFlag, $serviveAllFlag, array_unique($managerIds), array_unique($serviceIds)];
    }

    /**
     * 解析权限组
     */
    public static function parseGroup($groupList, $own)
    {
        $managerAllFlag = $serviveAllFlag = false;
        $userIds        = [];
        $typeTexts      = explode(',', $groupList->type_text);
        if (empty(array_intersect($typeTexts, [self::GROUP_MANAGER_TEXT, self::GROUP_SERVICE_TEXT]))) {
            return [$managerAllFlag, $serviveAllFlag, $userIds];
        }
        if ($groupList->all_user) {
            $managerAllFlag = !!in_array(self::GROUP_MANAGER_TEXT, $typeTexts);
            $serviveAllFlag = !!in_array(self::GROUP_SERVICE_TEXT, $typeTexts);
        }
        $userIds = $groupList->user_ids ? explode(',', $groupList->user_ids) : [];
        $deptIds = $groupList->dept_ids ? explode(',', $groupList->dept_ids) : [];
        $roleIds = $groupList->role_ids ? explode(',', $groupList->role_ids) : [];
        if (!empty($deptIds)) {
            $userIds = array_merge($userIds, UserService::getUserIdsByDeptIds($deptIds));
        }
        if (!empty($roleIds)) {
            $userIds = array_merge($userIds, UserService::getUserIdsByRoleIds($roleIds));
        }
        if (!empty($userIds) && $groupList->through_type) {
            $flag = $groupList->through_type == self::THROUGH_ALL ? true : false;
            $userIds = UserService::getUserSubordinateIds($userIds, $flag);
        }
        return [$managerAllFlag, $serviveAllFlag, $userIds];
    }

    public static function validateInput(array &$input, $id = 0)
    {
        if (!isset($input['name']) || !$input['name']) {
            return false;
        }
        $typeText = $ownTypeText = $detailPermission = $ownDetailPermission = []; 
        if (isset($input['type_text']) && !empty($input['type_text'])) {
            foreach ($input['type_text'] as $key => $value) {
                if ($value) {
                    $typeText[] = $key;
                }
            }
            $typeText = implode(',', $typeText);
        }
        if (isset($input['own_type_text']) && !empty($input['own_type_text'])) {
            foreach ($input['own_type_text'] as $key => $value) {
                if ($value) {
                    $ownTypeText[] = $key;
                }
            }
            $ownTypeText = implode(',', $ownTypeText);
        }
        if (isset($input['detail_permission']) && !empty($input['detail_permission'])) {
            foreach ($input['detail_permission'] as $key => $value) {
                if ($value) {
                    $detailPermission[] = $key;
                }
            }
            $detailPermission = implode(',', $detailPermission);
        }
        if (isset($input['own_detail_permission']) && !empty($input['own_detail_permission'])) {
            foreach ($input['own_detail_permission'] as $key => $value) {
                if ($value) {
                    $ownDetailPermission[] = $key;
                }
            }
            $ownDetailPermission = implode(',', $ownDetailPermission);
        }
        $data = [
            'name'                  => $input['name'],
            'all_user'              => $input['all_user'] ?? 0,
            'dept_ids'              => isset($input['dept_ids']) ? implode(',', (array) $input['dept_ids']) : '',
            'user_ids'              => isset($input['user_ids']) ? implode(',', (array) $input['user_ids']) : '',
            'role_ids'              => isset($input['role_ids']) ? implode(',', (array) $input['role_ids']) : '',
            'type_text'             => empty($typeText) ? '' : $typeText,
            'detail_permission'     => empty($detailPermission) ? '' : $detailPermission,
            'through_type'          => $input['through_type'] ?? '',
            'own_type_text'         => empty($ownTypeText) ? '' : $ownTypeText,
            'own_through_type'      => $input['own_through_type'] ?? '',
            'own_detail_permission' => empty($ownDetailPermission) ? '' : $ownDetailPermission,
            'status'                => $input['status'] ?? '',
        ];
        $input = $data;
        return $input;
    }
}
