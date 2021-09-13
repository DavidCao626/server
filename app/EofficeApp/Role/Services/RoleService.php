<?php

namespace App\EofficeApp\Role\Services;

use App\EofficeApp\Base\BaseService;
use App\EofficeApp\Menu\Repositories\RoleMenuRepository;
use App\EofficeApp\Role\Repositories\PermissionRepository;
use App\EofficeApp\Role\Repositories\RoleCommunicateRepository;
use App\EofficeApp\Role\Repositories\RolePermissionRepository;
use App\EofficeApp\Role\Repositories\RoleRepository;
use App\EofficeApp\Role\Repositories\UserRoleRepository;
use App\EofficeApp\Role\Repositories\UserSuperiorRepository;
use App\EofficeApp\User\Repositories\UserRepository;
use App\EofficeApp\User\Repositories\UserSystemInfoRepository;
use Cache;

/**
 * 角色管理Service类:提供角色管理相关服务
 *
 * @author qishaobo
 *
 * @since  2015-10-20 创建
 */
class RoleService extends BaseService
{

    /**
     * 用户资源库
     * @var object
     */
    private $userRepository;

    /**
     * 角色资源库
     * @var object
     */
    private $roleRepository;

    /**
     * 权限角色资源库
     * @var object
     */
    private $permissionRepository;

    /**
     * 用户角色资源库
     * @var object
     */
    private $userRoleRepository;

    /**
     * 角色权限资源库
     * @var object
     */
    private $rolePermissionRepository;

    /**
     * 用户上级级实体
     * @var object
     */
    private $userSuperiorRepository;

    /**
     * 角色通信
     * @var object
     */
    private $roleCommunicateRepository;
    private $roleMenuRepository;
    private $userSystemInfoRepository;

    public function __construct() {
        parent::__construct();
        $this->userRepository            = 'App\EofficeApp\User\Repositories\UserRepository';
        $this->roleRepository            = 'App\EofficeApp\Role\Repositories\RoleRepository';
        $this->permissionRepository      = 'App\EofficeApp\Role\Repositories\PermissionRepository';
        $this->userRoleRepository        = 'App\EofficeApp\Role\Repositories\UserRoleRepository';
        $this->rolePermissionRepository  = 'App\EofficeApp\Role\Repositories\RolePermissionRepository';
        $this->userSuperiorRepository    = 'App\EofficeApp\Role\Repositories\UserSuperiorRepository';
        $this->roleCommunicateRepository = 'App\EofficeApp\Role\Repositories\RoleCommunicateRepository';
        $this->roleMenuRepository        = 'App\EofficeApp\Menu\Repositories\RoleMenuRepository';
        $this->userSystemInfoRepository  = 'App\EofficeApp\User\Repositories\UserSystemInfoRepository';
    }

    /**
     * 获取角色列表数据
     *
     * @param  array  $param 查询条件
     *
     * @return array  查询结果或错误码
     *
     * @author qishaobo
     *
     * @since  2015-10-20 创建
     */
    public function getRoleList($param = [], $loginUserInfo = [])
    {
        //获取菜单角色的的统计用户总数
        $param = $this->parseParams($param);
        if (isset($param['search']['user_id'])) {
            unset($param['search']['user_id']);
        }
        // 筛选只显示比当前登录用户权限级别低的角色，admin除外
        if (isset($loginUserInfo['user_id']) && $loginUserInfo['user_id'] != 'admin' && isset($param['filter_role']) && $param['filter_role']) {
            $userPermissionData = app($this->userRepository)->getUserPermissionData($loginUserInfo['user_id'])->toArray();
            $userRoleNo         = [];
            foreach ($userPermissionData['user_role'] as $key => $value) {
                $userRoleNo[] = $value['role_no'];
            }
            $userMaxRolePriv            = min($userRoleNo);
            $param['search']['role_no'] = [$userMaxRolePriv, '>'];
        }

        $roleData = $this->response(app($this->roleRepository), 'getRoleTotal', 'getRoleList', $param);

        if (!isset($param['response']) || $param['response'] == 'data') {
            $userIdFilterData = [];
            if (isset($param['dataFilter']) && !empty($param['dataFilter'])) {
                $config = config('dataFilter.' . $param['dataFilter']);
                if (!empty($config)) {
                    $method                 = $config['dataFrom'][1];
                    $param['loginUserInfo'] = $loginUserInfo;
                    $userIdFilterData       = app($config['dataFrom'][0])->$method($param);
                }
            }
            $count = app($this->userRepository)->getUserCountByRole($userIdFilterData);

            $roles_count = [];
            foreach ($count as $v) {
                $roles_count[$v["role_id"]] = $v["user_count"];
            }

            $list = $roleData["list"];
            foreach ($list as $key => $value) {
                $role_id                    = $value["role_id"];
                $list[$key]['has_children'] = 0;
                if (isset($roles_count[$role_id])) {
                    $list[$key]['user_total']   = $roles_count[$role_id];
                    $list[$key]['has_children'] = $roles_count[$role_id] > 0 ? 1 : 0;
                } else {
                    $list[$key]['user_total'] = 0;
                }
            }
            $roleData["list"] = $list;
        }

        return $roleData;
    }

    public function getLists($param = [], $loginUserInfo = [])
    {

        //获取菜单角色的的统计用户总数
        $param = $this->parseParams($param);
        // 筛选只显示比当前登录用户权限级别低的角色，admin除外
        if (isset($loginUserInfo['user_id']) && $loginUserInfo['user_id'] != 'admin' && isset($param['filter_role']) && $param['filter_role']) {
            $userPermissionData = app($this->userRepository)->getUserPermissionData($loginUserInfo['user_id'])->toArray();
            $userRoleNo         = [];
            foreach ($userPermissionData['user_role'] as $key => $value) {
                $userRoleNo[] = $value['role_no'];
            }
            $userMaxRolePriv            = min($userRoleNo);
            $param['search']['role_no'] = [$userMaxRolePriv, '>'];
        }

        $roleData = $this->response(app($this->roleRepository), 'getTotal', 'getRoleList', $param);
        if (isset($roleData['list']) && $roleData['list']) {
            foreach ($roleData['list'] as $key => $value) {
                $roleData['list'][$key]['user_id'] = array_column($value['has_many_role'], 'user_id');
            }
        }
        return $roleData;
    }

    /**
     * 保存角色数据
     *
     * @param  array  $input  保存数据
     * @param  string $inheritRole 继承角色id
     *
     * @return int|array 新添加的角色id|错误码
     *
     * @author qishaobo
     *
     * @since  2015-10-20 创建
     */
    public function createRole($input)
    {
        $data = [
            'role_name'    => $input['role_name'],
            'role_no'      => (int) $input['role_no'],
            'role_name_py' => convert_pinyin($input['role_name'])[0],
            'role_name_zm' => convert_pinyin($input['role_name'])[1],
        ];

        if ($roleObj = app($this->roleRepository)->insertData($data)) {
            if (!empty($input['inherit_role'])) {
                $this->addRolePermission($input['inherit_role'], $roleObj->role_id);
            }
            return $roleObj->role_id;
        }

        return ['code' => ['0x000003', 'common']];
    }

    /**
     * 添加角色权限
     *
     * @param  integer $role_id  角色id
     * @param  string $inheritRole 继承角色id
     *
     * @return bool
     *
     * @author qishaobo
     *
     * @since  2015-10-20 创建
     */
    private function addRolePermission($role_id, $to_role_id)
    {
        try {
            $role_id = array_filter(explode(',', $role_id));
            //此时更新role_menu表 把菜单并给 $to_role_id
            //继承组
            $inheritRoles = app($this->roleMenuRepository)->getMenuDetail(["role_id" => [$role_id, "in"]]);
            //被继承组
            $ancestorRole = app($this->roleMenuRepository)->getMenuDetail(["role_id" => [$to_role_id, "="]]);
            $inheritMenu  = $ancestorMenu  = [];
            if (!empty($inheritRoles)) {
                foreach ($inheritRoles as $inherit) {
                    $inheritMenu[$inherit["menu_id"]] = $inherit["menu_id"];
                }
            }

            if (!empty($ancestorRole)) {
                foreach ($ancestorRole as $ancestor) {
                    $ancestorMenu[$ancestor["menu_id"]] = $ancestor["menu_id"];
                }
            }

            //取差集合
            $roleMenuData = array_diff($inheritMenu, $ancestorMenu);

            //插入到role_id中
            if (!empty($roleMenuData)) {
                $menu = [];
                foreach ($roleMenuData as $k => $v) {
                    $menu[$k]['role_id'] = $to_role_id;
                    $menu[$k]['menu_id'] = $v;
                }

                if (count($menu)) {
                    app($this->roleMenuRepository)->insertMultipleData($menu);
                }
            }

            //取出 to_id中 已经继承的
            $role_func_id = app($this->rolePermissionRepository)->getRolePermission([$to_role_id]);

            //删除
            $func_del_filter = array_diff($role_func_id, $role_id);
            app($this->rolePermissionRepository)->deleteByWhere(["function_id" => [$func_del_filter, "in"]]);

            //增加
            $func_add_filter = array_diff($role_id, $role_func_id);
            $data            = [];
            $date            = date("Y-m-d H:i:s");
            foreach ($func_add_filter as $k => $v) {
                $data[$k]['role_id']     = $to_role_id;
                $data[$k]['function_id'] = $v;
                $data[$k]['updated_at']  = $data[$k]['created_at']  = $date;
            }

            if (count($data)) {
                app($this->rolePermissionRepository)->insertMultipleData($data);
            }
            $this->clearCache();
            return true;
        } catch (Exception $exc) {
            return ['code' => ['0x000003', 'common']];
        }
    }

    public function clearCache()
    {
        $where['search'] = [
            "user_accounts" => ["", "!="],
        ];
        $userDatas = app($this->userRepository)->getAllUsers($where);
        foreach ($userDatas as $v) {
            if (Cache::has("custom_menus_" . $v['user_id'])) {
                Cache::forget('custom_menus_' . $v['user_id']);
            }
        }

        $role = app($this->roleRepository)->getAllRoles([]);
        foreach ($role as $v) {
            if (Cache::has("role_menus_" . $v['role_id'])) {
                Cache::forget('role_menus_' . $v['role_id']);
            }
        }

    }

    /**
     * 删除角色
     *
     * @param int|string $roleId 删除角色id
     *
     * @return array|bool
     *
     * @author qishaobo
     *
     * @since  2015-10-20 创建
     */
    public function deleteRole($roleId)
    {
        $roleIds = array_filter(explode(',', $roleId));

        if (empty($roleIds)) {
            return 0;
        }

        $where['role_id'] = [$roleIds, 'in'];

        // if (app($this->userRoleRepository)->getTotal(['search' => $where])) {
        //     return ['code' => ['0x006004', 'role']];
        // }

        app($this->rolePermissionRepository)->deleteByWhere($where);

        if (app($this->roleRepository)->deleteById($roleIds)) {
            return true;
        }
        return ['code' => ['0x000003', 'common']];
    }

    /**
     * 获取角色详情
     *
     *
     * @param int $roleId 角色id
     *
     * @return array
     *
     * @author qishaobo
     *
     * @since  2015-10-20 创建
     */
    public function getRolesDetail($roleId)
    {
        if ($result = app($this->roleRepository)->getDetail($roleId)) {
            return $result->toArray() ?: ['code' => ['0x000003', 'common']];
        }
    }

    /**
     * 编辑角色数据
     *
     * @param array $data 编辑数据
     * @param int $roleId 角色id
     *
     * @return string
     *
     * @author qishaobo
     *
     * @since  2015-10-20 创建
     */
    public function updateRole($roleId, $input)
    {
        $roleDetail      = $this->getRolesDetail($roleId);
        $roleNamePyAndZm = convert_pinyin($input['role_name']);
        $data            = [
            'role_name'    => $input['role_name'],
            'role_no'      => (int) $input['role_no'],
            'role_name_py' => isset($roleNamePyAndZm[0]) ? $roleNamePyAndZm[0] : '',
            'role_name_zm' => isset($roleNamePyAndZm[1]) ? $roleNamePyAndZm[1] : '',
        ];

        if (app($this->roleRepository)->updateData($data, ['role_id' => $roleId])) {
            if (!empty($roleDetail) && isset($roleDetail['role_no']) && ($roleDetail['role_no'] != $input['role_no'])) {
                // 如果变更了角色级别，则更新所有用户的最大角色级别
                app($this->roleRepository)->updateAllUserMaxRoleNo();
            }
            if (!empty($input['inherit_role'])) {
                $this->addRolePermission($input['inherit_role'], $roleId);
            }
            return true;
        }

        return ['code' => ['0x000003', 'common']];
    }

    /**
     * 获取人员的角色
     *
     * @param string $userId 用户id
     *
     * @return array
     *
     * @author qishaobo
     *
     * @since  2015-10-20 创建
     */
    public function getUserRole($userId)
    {
        if (is_array($userId)) {
            $where = ['user_id' => [$userId, 'in']];
        } else {
            $where = ['user_id' => [$userId]];
        }

        return app($this->userRoleRepository)->getUserRole($where, 1);
    }

    /**
     * 保存人员角色
     *
     * @param array $input 保存数据
     * @param bool $edit 是否编辑
     *
     * @return array|bool
     *
     * @author qishaobo
     *
     * @since  2015-10-20 创建
     */
    public function addUserRole($input, $edit = false)
    {
        $date = date("Y-m-d H:i:s");
        $data = [];
        if (!empty($input['role_id'])) {
            $roleId = array_filter(explode(',', $input['role_id']));
            foreach ($roleId as $v) {
                $data[] = [
                    'user_id'    => $input['user_id'],
                    'role_id'    => $v,
                    'created_at' => $date,
                    'updated_at' => $date,
                ];
            }
        }

        if ($edit && $input['role_id']) {
            $where['user_id'] = [$data[0]['user_id']];
            app($this->userRoleRepository)->deleteByWhere($where);
        }

        $result = app($this->userRoleRepository)->insertMultipleData($data);

        return $result ? 1 : ['code' => ['0x000003', 'common']];
    }

    /**
     * 删除角色
     *
     * @param int|string $userId 用户id
     *
     * @return int|array  操作成功|错误码
     *
     * @author qishaobo
     *
     * @since  2015-10-20 创建
     */
    public function deleteUserRole($userId)
    {
        $where['user_id'] = [$userId];
        if ($result = app($this->userRoleRepository)->deleteByWhere($where)) {
            return $result ? 1 : ['code' => ['0x000003', 'common']];
        }
    }

    /**
     * 获取角色通信列表
     *
     * @param array $param 查询条件
     *
     * @return array    查询结果
     *
     * @author qishaobo
     *
     * @since  2015-10-20 创建
     */
    public function getRoleCommunicate($param = [])
    {
        $param = $this->parseParams($param);
        $data  = [];

        if (!empty($param['role_from'])) {
            $result = app($this->roleCommunicateRepository)->getRoleTo(json_decode($param['role_from']));

            foreach ($result as $v) {
                $types = array_filter(explode(',', $v['communicate_type']));
                foreach ($types as $type) {
                    $roleTos = array_filter(explode(',', $v['role_to']));
                    if (!isset($data[$type])) {
                        $data[$type] = [];
                    }

                    $data[$type] = array_merge($data[$type], $roleTos);
                }
            }

            foreach ($data as $k => $v) {
                $data[$k] = array_unique($data[$k]);
            }

            return $data;
        }

        $data    = $this->response(app($this->roleCommunicateRepository), 'getTotal', 'getRoleCommunicate', $param);
        $user_id = own('user_id');
        if ($remindsObj = app('App\EofficeApp\System\Remind\Services\SystemRemindService')->getReminds($user_id)) {
            $reminds         = $remindsObj->toArray();
            $communicateType = array_column($reminds, 'reminds_name', 'id');
        }

        if ($data['total'] > 0) {
            foreach ($data['list'] as $k => $v) {
                $data['list'][$k]['role_from_name'] = implode(',', app($this->roleRepository)->getRolesNameByIds(array_filter(explode(',', $v['role_from']))));
                $data['list'][$k]['role_to_name']   = implode(',', app($this->roleRepository)->getRolesNameByIds(array_filter(explode(',', $v['role_to']))));
                $type                               = array_filter(explode(',', $v['communicate_type']));
                foreach ($type as $key => $val) {
                    if (!empty($communicateType[$val])) {
                        $type[$key] = $communicateType[$val];
                    } else {
                        unset($type[$key]);
                    }
                }
                $data['list'][$k]['communicate_type_name'] = implode(',', array_filter($type));
            }
        }

        return $data;
    }

    /**
     * 添加角色通信
     *
     * @param array $input 角色通讯数据
     *
     * @return int|array 新建数据id|错误码
     *
     * @author qishaobo
     *
     * @since  2015-10-20 创建
     */
    public function addRoleCommunicate($input)
    {
        $data = [
            'role_from'        => implode(',', $input['role_from']),
            'role_to'          => implode(',', $input['role_to']),
            'communicate_type' => rtrim($input['communicate_type'], ','),
            'control_fields'   => isset($input['control_fields']) ? json_encode($input['control_fields']) : '',
        ];
        $result = app($this->roleCommunicateRepository)->insertData($data);
        return $result->id ? $result->id : ['code' => ['0x000003', 'common']];
    }

    /**
     * 获取角色通信列表
     *
     * @param int $id 通讯id
     *
     * @return array 角色通讯数据|错误码
     *
     * @author qishaobo
     *
     * @since  2015-10-20 创建
     */
    public function getRoleCommunicateDetail($id)
    {
        if ($resultObj = app($this->roleCommunicateRepository)->getDetail($id)) {
            $result = $resultObj->toArray();

            $result['role_from'] = array_filter(explode(',', $result['role_from']));
            array_walk($result['role_from'], function (&$num) {
                $num = (int) $num;
            });

            $result['role_to'] = array_filter(explode(',', $result['role_to']));
            array_walk($result['role_to'], function (&$num) {
                $num = (int) $num;
            });
            $result['control_fields'] = $result['control_fields'] ? json_decode($result['control_fields'], true) : [];
            return $result;
        }

        return ['code' => ['0x000003', 'common']];
    }

    /**
     * 删除角色通信列表
     *
     * @param integer $id
     *
     * @return array|integer
     *
     * @author qishaobo
     *
     * @since  2015-10-20 创建
     */
    public function deleteCommunicate($id)
    {
        $ids = array_filter(explode(',', $id));
        if ($result = app($this->roleCommunicateRepository)->deleteById($ids)) {
            return $result ?: ['code' => '0x000003'];
        }
    }

    /**
     * 编辑角色通信列表
     *
     * @param array $input
     * @param integer $id
     *
     * @return array
     *
     * @author qishaobo
     *
     * @since  2015-10-20 创建
     */
    public function editCommunicate($input, $id)
    {
        $data = [
            'role_from'        => is_array($input['role_from']) ? implode(',', $input['role_from']) : $input['role_from'],
            'role_to'          => is_array($input['role_to']) ? implode(',', $input['role_to']) : $input['role_to'],
            'communicate_type' => $input['communicate_type'],
            'control_fields'   => json_encode($input['control_fields']),
        ];
        return app($this->roleCommunicateRepository)->updateData($data, ['id' => $id]);
    }

    /**
     * 获取用户上下级
     *
     * @param string $userId
     *
     * @return array
     *
     * @author qishaobo
     *
     * @since  2015-10-20 创建
     */
    public function getUserSuperior($userId)
    {
        $data = [];
        if ($result = app($this->userSuperiorRepository)->getUserSuperior($userId)) {
            $data['user_superior_id']    = array_diff(array_column($result, 'superior_user_id'), [$userId]);
            $data['user_subordinate_id'] = array_diff(array_column($result, 'user_id'), [$userId]);
        }
        return $data;
    }

    /**
     * 获取用户下级
     *
     * @param array $param 查询条件
     *
     * @return array
     *
     * @author qishaobo
     *
     * @since  2015-10-20 创建
     */
    public function getUserSuperiorList($param)
    {
        $param = $this->parseParams($param);
        return app($this->userSuperiorRepository)->getUserSuperiorList($param);
    }

    /**
     * 保存用户上下级
     *
     * @param  array  $input  提交数据
     *
     * @return array
     *
     * @author qishaobo
     *
     * @since  2015-10-20 创建
     */
    public function addUserSuperior($input)
    {
        $data = $userSuperiorId = $userSubordinateId = [];
        if (isset($input['user_superior_id'])) {
            //是否为全部
            if (isset($input['is_all']) && $input['is_all']) {
                // 删除当前用户作为上级的数据
                app($this->userSuperiorRepository)->deleteUserSubordinate($input['user_id']);
            }
            // 判断交集
            $userSuperiorId = array_filter(explode(',', $input['user_superior_id']));
            foreach ($userSuperiorId as $k => $v) {
                if ($input['user_id'] != $v) {
                    $data[] = [
                        'user_id'          => $input['user_id'],
                        'superior_user_id' => $v,
                    ];
                }
            }
        }
        
        if (isset($input['user_subordinate_id'])) {
            $userSubordinateId = array_filter(explode(',', $input['user_subordinate_id']));
            foreach ($userSubordinateId as $v) {
                if ($input['user_id'] != $v) {
                    $data[] = [
                        'user_id'          => $v,
                        'superior_user_id' => $input['user_id'],
                    ];
                }
            }
            // 删除之前我的下级的
            app($this->userSuperiorRepository)->deleteUserSubordinate($input['user_id']);
        }
        if (!empty($userSuperiorId) && !empty($userSubordinateId) && array_intersect($userSuperiorId, $userSubordinateId)) {
            return ['code' => ['0x006006', 'role']];
        }

        // if (isset($input['edit']) && $input['edit'] == 1) {
        //     app($this->userSuperiorRepository)->deleteUserSuperior($input['user_id']);
        // }
        if (isset($input['edit']) && $input['edit'] == 1) {
            app($this->userSuperiorRepository)->deleteUserSuperiorarr($input['user_id']);
        }
        if (!empty($data)) {
            $result = app($this->userSuperiorRepository)->insertMultipleData($data);
            return $result ? 1 : ['code' => ['0x000003', 'common']];
        }

        return true;
    }

    /**
     * 获取人员的角色
     *
     * @param  string $userId 用户id
     *
     * @return array
     *
     * @author qishaobo
     *
     * @since  2015-10-30 创建
     */
    public function getUserPermissions($userId = '')
    {
        $userId     = $userId ?: $this->loginUserId;
        $result     = $userPermissions     = app($this->userRoleRepository)->getUserPermissions($userId);
        $permission = [];

        if (!empty($result)) {
            foreach ($result as $k => $v) {
                $permission = array_merge($permission, array_column($v['has_many_permission'], 'function_id'));
            }
            $permission = array_unique($permission);
        }

        return $permission;
    }

    /**
     * 获取所有权限级别
     *
     *
     * @return array
     *
     * @author shiyao
     *
     * @since  2016-11-29 创建
     */
    public function getRoleLevel()
    {
        $roleList = app($this->roleRepository)->getAllRoles(['fields' => ["role_no", "role_name", "role_id"]]);
        $level    = [];
        foreach ($roleList as $key => $value) {
            $level[$value['role_no']]['role_no'] = $value['role_no'];
            if (isset($level[$value['role_no']]['role_name'])) {
                $level[$value['role_no']]['role_name'] = substr($level[$value['role_no']]['role_name'], 0, -1);
                $level[$value['role_no']]['role_name'] .= ',' . $value['role_name'] . ")";
            } else {
                $level[$value['role_no']]['role_name'] = $value['role_no'] . "(" . $value['role_name'] . ")";
            }
            $level[$value['role_no']]['role_id'] = $value['role_id'];
        }

        return array_values($level);
    }

    public function communicateCount($data)
    {
        if (!isset($data['role_from']) || !isset($data['role_to']) || !isset($data['communicateTypes'])) {
            return [];
        }
        $role_from        = is_array($data['role_from']) ? $data['role_from'] : array($data['role_from']);
        $role_to          = is_array($data['role_to']) ? $data['role_to'] : array($data['role_to']);
        $communicateTypes = is_array($data['communicateTypes']) ? $data['communicateTypes'] : array($data['communicateTypes']);
        $roleList         = app($this->roleCommunicateRepository)->communicateCount($role_from, $role_to, $communicateTypes);
        return $roleList;
    }

    /**
     * 获取角色ID集合中的最大角色级别
     *
     * @author 缪晨晨
     *
     * @param  array or string $data [description]
     *
     * @since  2017-06-07 创建
     *
     * @return string 返回最大角色级别(角色级别数字越小的级别越大)
     */
    public function getMaxRoleNoFromData($data)
    {
        if (empty($data)) {
            return '';
        } else {
            if ($data == 'all') {
                $where = '';
            } else {
                if (!is_array($data)) {
                    $data = explode(',', trim($data, ','));
                }
                $where = $data;
            }
            return app($this->roleRepository)->getMaxRoleNoFromData($where);
        }
    }

    /**
     * 获取某个部门下所有角色
     *
     */
    public function getDeptRole($param)
    {
        if (!isset($param['dept_id']) || empty($param['dept_id'])) {
            return [];
        }
        $deptId   = $param['dept_id'];
        $roleList = app($this->roleRepository)->getDeptRole($deptId);
        return $roleList;
    }

    public function roleControlFields()
    {
        $control_table = config('rolecontrolfields');
        return ['control_table' => $this->transSystemArray($control_table)];
    }

    // 递归翻译系统配置数组
    public function transSystemArray($data)
    {
        if(is_string($data)){
            return trans('system.'.$data);
        }
        $new = [];
        foreach ($data as $key => $value){
            if(is_string($value)){
                $new[$key] = trans('system.'.$value);
            }elseif (is_array($value)){
                $new[$key] = $this->transSystemArray($value);
            }
        }
        return $new;
    }
    public function getAllRoleIds()
    {
        return app($this->roleRepository)->getAllRoleIds();
    }
}