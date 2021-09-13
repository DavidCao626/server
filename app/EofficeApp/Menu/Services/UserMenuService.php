<?php

namespace App\EofficeApp\Menu\Services;

use App\EofficeApp\Base\BaseService;
use App\EofficeApp\FormModeling\Traits\FieldsRedisTrait;
use App\EofficeApp\FormModeling\Repositories\FormModelingRepository;
use App\Utils\Utils;
use Cache;
use DB;
use Lang;
use Illuminate\Support\Facades\Redis;
use Schema;

/**
 * 系统菜单服务-传递参数给资源库，返回结果
 *
 * @author: 喻威
 *
 * @since：2015-10-19
 */
class UserMenuService extends BaseService
{
    use FieldsRedisTrait;
    const CUSTOM_REMINDS = 'custom:reminds';
    const CUSTOM_TABLE_FIELDS = 'custom:table_fields_';
    /** @var object $userMenuRepository 用户菜单资源库 */
    private $roleMenuRepository;
    private $menuRepository;
    private $userMenuRepository;
    private $userRoleRepository;
    private $roleRepository;
    private $fieldsRepository;
    private $userRepository;
    private $ssoService;
    private $empowerService;
    private $menuModifyFlag = false;
    private $publicPath;
    private $menuIconPath;
    protected $currentData = [
        'date' => '当前日期20170817',
        'time' => '当前时间151830',
        'timestamp' => '当前时间戳23131123',
        'userId' => '当前用户ID',
        'userName' => '当前用户姓名',
        'userAccount' => '当前用户账号',
        'userRole' => '当前用户角色名称',
        'userRoleLeavel' => '当前用户角色权限级别',
        'userDeptName' => '当前用户部门名称',
        'userDeptId' => '当前用户部门ID',
        'userJobNumber' => '当前用户工号',
    ];

    public function __construct()
    {
        $this->roleMenuRepository = 'App\EofficeApp\Menu\Repositories\RoleMenuRepository';
        $this->menuRepository = 'App\EofficeApp\Menu\Repositories\MenuRepository';
        $this->userMenuRepository = 'App\EofficeApp\Menu\Repositories\UserMenuRepository';
        $this->userRoleRepository = 'App\EofficeApp\Role\Repositories\UserRoleRepository';
        $this->flowModelingRepository = 'App\EofficeApp\FlowModeling\Repositories\FlowModelingRepository';
        $this->roleRepository = 'App\EofficeApp\Role\Repositories\RoleRepository';
        $this->userRepository = 'App\EofficeApp\User\Repositories\UserRepository';
        $this->formModelingRepository = 'App\EofficeApp\FormModeling\Repositories\FormModelingRepository';
        $this->ssoService = 'App\EofficeApp\Sso\Services\SsoService';
        $this->empowerService = 'App\EofficeApp\Empower\Services\EmpowerService';
        $this->langService = 'App\EofficeApp\Lang\Services\LangService';
        $this->attachmentService = 'App\EofficeApp\Attachment\Services\AttachmentService';
        $this->publicPath = $publicPath = str_replace('\\', '/', base_path()) . '/public/';
        $this->menuClassRepository = 'App\EofficeApp\Menu\Repositories\MenuClassRepository';
        $this->menuIconPath = access_path('images/menu/icons/');
    }

    //获取菜单树
    public function getMenuTree($menu_parent, $data, $ownMenus)
    {
        // $menus = $ownMenus['menu'];
        $menu_parent = intval($menu_parent);
        $where = [
            "menu_parent" => [$menu_parent],
            "menu_from" => [2, '<'], //用在菜单配置处
            // "menu.menu_id" => [$menus, "in"]
        ];

        if (isset($data["type"]) && $data["type"] == "all") {
            $where = [
                "menu_parent" => [$menu_parent],
                "menu_from" => [2, '<'], //用在菜单配置处
            ];
        } else if (isset($data["type"]) && $data["type"] == "system") {
            $where = [
                "menu_parent" => [$menu_parent],
                "menu_from" => [1, '='], //用在菜单配置处
            ];
        } else {
            $menus = $ownMenus['menu'];
            $where = [
                "menu_parent" => [$menu_parent],
                "menu_from" => [2, '<'], //用在菜单配置处
                "menu.menu_id" => [$menus, "in"],
            ];
        }

        //选择器时需要
        $temp = $this->parseParams($data);
        //系统菜单配置处 根据菜单设置序号进行排序 20170209
        if (Cache::has('no_permission_menus')) {
            $excludeMenuId = Cache::get('no_permission_menus');
        } else {
            $excludeMenuId = $this->getPermissionModules();
        }
        $customMenus = $this->getCustomMenuPower();
        $excludeMenuId = array_merge($customMenus,$excludeMenuId);
        $trees = app($this->menuRepository)->getMenuByMenuOrder($where, array_filter($temp));
        $menus = [];
        foreach ($trees as $key => $value) {
            if (!in_array($value['menu_id'], $excludeMenuId)) {
                $menus[] = $value;
            }
        }
        return $this->arrayMenuLang($menus);
        // return $trees;
    }

    public function arrayMenuLang($data)
    {
        if (!empty($data) && is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key]['menu_name'] = mulit_trans_dynamic("menu.menu_name.menu_" . $value['menu_id']);
            }
        }
        return $data;
    }

    public function searchMenuTree($data)
    {
        $trees = app($this->menuRepository)->searchMenuTree($this->parseParams($data));
        $parent = [];
        foreach ($trees as $tree) {
            if ($tree['menu_parent'] == 0) {
                array_push($parent, $tree['menu_id']);
            } else {
                array_push($parent, $tree['menu_parent']);
            }
        }
        $parent = array_unique($parent);
        $resultList = [];
        foreach ($parent as $k => $v) {
            $where = [
                'search' => ["menu_id" => [$v]],
            ];
            $results = app($this->menuRepository)->searchMenuTree($where);
            foreach ($results as $result) {
                $resultList[$k] = $result;
                $wheres = [
                    'search' => ["menu_parent" => [$v]],
                ];
                $resultList[$k]["list"] = app($this->menuRepository)->searchMenuTree($wheres);
                foreach ($resultList[$k]["list"] as $key => $value) {
                    $resultList[$k]["list"][$key]['menu_name'] = mulit_trans_dynamic("menu.menu_name.menu_" . $value['menu_id']);
                }
                $resultList[$k]["menu_name"] = mulit_trans_dynamic("menu.menu_name.menu_" . $v);
            }
        }
        if (Cache::has('no_permission_menus')) {
            $excludeMenuId = Cache::get('no_permission_menus');
        } else {
            $excludeMenuId = $this->getPermissionModules();
        }
        $customMenus = $this->getCustomMenuPower();
        $excludeMenuId = array_merge($customMenus,$excludeMenuId);
        $menus = [];
        foreach ($resultList as $key => $value) {
            $flag = 1;
            if (in_array($value['menu_id'], $excludeMenuId)) {
                $flag = 0;
            }
            if(isset($value['list']) && !empty($value['list'])){
                foreach ($value['list'] as $child) {
                    if (in_array($child['menu_id'], $excludeMenuId)) {
                        $flag = 0;
                    }
                }
            }
            if($flag == 1){
                $menus[] = $value;
            }
        }
        return array_merge([], $menus);
    }

    public function getMenuInfo($data, $ownMenus)
    {
        $temp = $this->parseParams($data);
        $con = [];
        $from = '';
        if (isset($temp['from'])) {
            $from = $temp['from'];
        }
        if (isset($temp["search"])) {
            $con = $temp["search"];
        }
        if (isset($data["type"]) && $data["type"] == "all") {
            $otherCondition = [
                "menu_from" => [2, '<'], //用在菜单配置处
            ];
        } else if (isset($data["type"]) && $data["type"] == "system") {
            $otherCondition = [
                "menu_from" => [1, '='], //用在菜单配置处
            ];
        } else {
            $menus = $ownMenus['menu'];
            $otherCondition = [
                "menu_from" => [2, '<'], //用在菜单配置处
                "menu.menu_id" => [$menus, "in"],
            ];
        }

        $con = array_merge($otherCondition, $con);

        $info = app($this->menuRepository)->getMenuData(array_filter($con), '*', $from);
        if (Cache::has('no_permission_menus')) {
            $excludeMenuId = Cache::get('no_permission_menus');
        } else {
            $excludeMenuId = $this->getPermissionModules();
        }
        $customMenus = $this->getCustomMenuPower();
        $excludeMenuId = array_merge($customMenus,$excludeMenuId);
        $result = [];
        foreach ($info as $key => $value) {
            if(!in_array($value['menu_id'],$excludeMenuId)){
                $value['menu_name'] = mulit_trans_dynamic("menu.menu_name.menu_" . $value['menu_id']);
                $result[] = $value;
            }

        }
        return $result;
    }
    public function clearCache()
    {
        $where['search'] = [
            "user_accounts" => ["", "!="],
        ];
        $userDatas = app($this->userRepository)->getAllUsers($where);
        foreach ($userDatas as $v) {
            if (Cache::has("custom_menus_" . $v->user_id)) {
                Cache::forget('custom_menus_' . $v->user_id);
            }
            if (Cache::has("user_role_" . $v->user_id)) {
                Cache::forget('user_role_' . $v->user_id);
            }
        }

        $role = app($this->roleRepository)->getAllRoles([]);
        foreach ($role as $v) {
            if (Cache::has("role_menus_" . $v->role_id)) {
                Cache::forget('role_menus_' . $v->role_id);
            }
        }
        // 清除模块授权缓存
        Cache::forget('no_permission_menus');
        ecache('Empower:EmpowerModuleInfo')->clear();

        $locale = Lang::getLocale();
        Redis::del('lang_' . $locale);
        Redis::del('mulitlang_' . $locale);

    }

    public function getMenuByIdArray($data)
    {

        if (isset($data['menus'])) {
            if (!is_array($data['menus'])) {
                $data['menus'] = json_decode($data['menus']);
            }
            if (is_array($data['menus'])) {
                $condition = [
                    "menu_id" => [$data['menus'], "in"],
                ];
                $info = app($this->menuRepository)->getMenuByWhere($condition);
            } else {
                $info = app($this->menuRepository)->getSingleMenu($data['menus']);
            }

            foreach ($info as $k => $t) {
                $menuID = $t['menu_id'];
                $menu_type = $t["menu_type"];
                $state_url = $t["state_url"];
                $info[$k]['menu_name'] = trans_dynamic("menu.menu_name.menu_" . $menuID);
                if ($menuID > 999) {
                    if ($menu_type == "customize") {
                        $info[$k]["menu_code"] = $menuID;
                    }
                    if ($menu_type == "singleSignOn") {
                        $tempMenuParam = unserialize($t["menu_param"]);
                        $t["menu_extras"] = [];
                        if ($tempMenuParam) {
                            $sso_id = $tempMenuParam["url"];
                            //获取sso配置信息
                            $t["menu_extras"] = [
                                "type" => 'singleSignOn',
                                "sso_id" => $sso_id,
                            ];
                        }
                    } elseif ($menu_type == 'flow') {
                        $t["menu_extras"] = json_decode($t['state_url'], true);
                    } elseif ($menu_type == 'flowModeling') {
                        if (isset($t['menu_has_one_flow_module_factory']['state_url'])) {
                            $t["menu_extras"] = json_decode($t['menu_has_one_flow_module_factory']['state_url'], 1);
                        } else {
                            $t["menu_extras"] = [];
                        }
                    } else {
                        $t["menu_extras"] = json_decode($state_url, 1);
                    }
                    $info[$k]["menu_extras"] = $t["menu_extras"];
                }
            }
            return $info;
        }

    }

    //获取某个菜单的设置详情 --- menu_id
    public function getMenuInfoByMenuId($menu_id)
    {
        $trees = app($this->menuRepository)->getDetail($menu_id);

        $where = [
            "menu_id" => [$menu_id],
        ];
        $roles = app($this->roleMenuRepository)->getMenusGroupByRoleId($where);
        $systemRoles = app($this->roleRepository)->getAllRoles([]);
        $roleArray = [];
        foreach ($systemRoles as $v) {
            $roleArray[] = $v['role_id'];
        }
        $roleStr = [];
        foreach ($roles as $role) {
            if (in_array($role['role_id'], $roleArray)) {
                $roleStr[] = $role['role_id'];
            }

        }

        $trees['menu_role'] = $roleStr;

        if ($trees['menu_param']) {
            $state_url = json_decode($trees['state_url']);
            if ($trees['menu_type'] != 'flowModeling') {
                $unserialize = unserialize($trees['menu_param']);
            }
            switch ($trees['menu_type']) {
                case "web":
                    $trees['param_url'] = $unserialize["url"];
                    $trees['param_style'] = $unserialize["method"];
                    $trees['params'] = '';
                    if (isset($state_url->params)) {
                        $trees['params'] = $state_url->params;
                    }
                    break;
                case "flow":
                    $trees['flow_url'] = $unserialize["url"];
                    $trees['flow_type'] = $unserialize["method"];
                    break;
                case "flowModeling":
                    $flowModuleInfo = app($this->flowModelingRepository)->getDetail($trees['menu_param']);
                    if (!empty($flowModuleInfo)) {
                        $unserialize = unserialize($flowModuleInfo->module_param);
                        $trees['flow_url'] = $unserialize["url"];
                        $trees['flow_type'] = $unserialize["method"];
                        $trees['flowModuleInfo'] = isset($unserialize["flowModuleInfo"]) ? $unserialize["flowModuleInfo"] : "";
                    }
                    $trees['flow_module_id'] = $trees['menu_param'];
                    break;
                case "document":
                    $trees['doc_url'] = $unserialize["url"];
                    $trees['doc_type'] = $unserialize["method"];

                    break;
                case "systemMenu":

                    $trees['sysmenu_url'] = $unserialize["url"];

                    break;
                case "singleSignOn":

                    $trees['sso_url'] = $unserialize["url"];

                    break;
                case "unifiedMessage":

                    $trees['message_type_id'] = $unserialize["url"];

                    break;
            }
        }

        $path = substr_count($trees['menu_path'], ","); //统计层级

        if ($trees['menu_type'] == 'customize') {
            $trees["add_status"] = $path < 1 ? true : false;
        } else {
            if ($trees['menu_from'] == 0) {
                $trees["add_status"] = $path < 2 ? true : false;
            } else {
                $trees["add_status"] = $path < 1 ? true : false;
            }

        }
        if (isset($trees['icon_type']) && $trees['icon_type'] == 2) {
            $trees['menu_icon'] = app($this->attachmentService)->getAttachmentIdsByEntityId(['entity_table' => 'menu', 'entity_id' => $menu_id]);
        }
        $trees['menu_name'] = trans_dynamic("menu.menu_name.menu_" . $menu_id);
        $trees['menu_name_lang'] = app($this->langService)->transEffectLangs("menu.menu_name.menu_" . $menu_id);

        $con = [
            "has_children" => [1, '='], //用在菜单配置处
            "menu_parent" => [0, "!="],
        ];
        //移动端不显示菜单 禁用
        // $trees["add_status"] = ($trees['is_system'] == 0) && $path < 2 ? true : false;
        return $trees;
    }

    //获取某一个菜单的设置权限
    public function getMenuRole($menu_id)
    {
        $where = [
            "menu_id" => [$menu_id],
        ];
        $roles = app($this->roleMenuRepository)->getMenusGroupByRoleId($where);
        $roleStr = [];
        foreach ($roles as $role) {
            $roleStr[] = $role['role_id'];
        }
        return implode(",", array_unique($roleStr));
        // return $roleStr;
    }

    //设置菜单
    public function setMenu($data)
    {
        $data['menu_from'] = isset($data['menu_from']) && empty($data['menu_from']) ? $data['menu_from'] : 0;
        $menuId = isset($data["menu_id"]) ? $data["menu_id"] : 0; //如果存在 就是父ID

        $insertMenuId = app($this->menuRepository)->getInterruptID(1000); //插入的menu_id

        $menuPy = Utils::convertPy($data['menu_name']);
        $data['menu_name_zm'] = $menuPy[1];
        $params = isset($data['params']) ? $data['params'] : "";
        $data['state_url'] = isset($data['state_url']) && $data['state_url'] ? json_encode($data['state_url']) : "";
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

        if (!$menuId) {
            //一级菜单没有menuId
            $data['menu_path'] = 0;
            $data['menu_type'] = "";
            $data['state_url'] = "";
            $data['menu_param'] = "";
        } else {
            //更新父级节点为has_child = 1 menu_type
            $menuParentData = app($this->menuRepository)->getDetail($menuId);

            $data['menu_parent'] = $menuId;
            $data['menu_path'] = $menuParentData['menu_path'] . "," . $menuId;
            //所有带下级菜单的的菜单 都是菜单夹形式 在页面中不可以不能单独操作
            app($this->menuRepository)->updateData(["has_children" => 1, "menu_param" => "", "menu_type" => "favoritesMenu", "state_url" => ""], ['menu_id' => $menuId]);

            switch ($data['menu_type']) {
                case "web":

                    $url = isset($data['param_url']) ? $data['param_url'] : "";
                    $method = isset($data['param_style']) ? $data['param_style'] : "_blank";
                    $data['menu_param'] = serialize([
                        'url' => $url,
                        'method' => $method,
                        'params' => $params,
                    ]);
                    break;
                case "flow":
                    $url = isset($data['flow_url']) ? $data['flow_url'] : "";
                    $method = isset($data['flow_type']) ? $data['flow_type'] : 1;
                    $data['menu_param'] = serialize([
                        'url' => $url,
                        'method' => $method,
                    ]);
                    break;
                case "flowModeling":
                    $data['menu_param'] = isset($data['flow_module_id']) ? $data['flow_module_id'] : "";
                    break;
                case "document":

                    $url = isset($data['doc_url']) ? $data['doc_url'] : "";
                    $method = isset($data['doc_type']) ? $data['doc_type'] : "1";
                    $data['menu_param'] = serialize([
                        'url' => $url,
                        'method' => $method,
                    ]);

                    break;
                case "systemMenu":

                    $url = isset($data['sysmenu_url']) ? $data['sysmenu_url'] : "";

                    $data['menu_param'] = serialize([
                        'url' => $url,
                    ]);

                    break;
                case "unifiedMessage":

                    $url = isset($data['message_type_id']) ? $data['message_type_id'] : "";

                    $data['menu_param'] = serialize([
                        'url' => $url,
                    ]);

                    break;
                case "singleSignOn":

                    $url = isset($data['sso_url']) ? $data['sso_url'] : "";

                    $data['menu_param'] = serialize([
                        'url' => $url,
                    ]);

                    break;
                case "favoritesMenu":
                    $data["has_children"] = 1;
                    $data['menu_param'] = "";
                    $data['state_url'] = "";
                    break;
            }
        }

        //插入到菜单表
        $data['menu_id'] = $insertMenuId;
        $data['role_status'] = $data['menu_role'] == "all" ? 1 : 0;

        $menu_name_lang = isset($data['menu_name_lang']) ? $data['menu_name_lang'] : '';
        if (!empty($menu_name_lang) && is_array($menu_name_lang)) {
            foreach ($menu_name_lang as $key => $value) {
                $langData = [
                    'table' => 'menu',
                    'column' => 'menu_name',
                    'lang_key' => "menu_" . $insertMenuId,
                    'lang_value' => $value,
                ];
                $configData = [
                    'table' => 'custom_menu_config',
                    'column' => 'menu_name',
                    'lang_key' => "custom_config_" . $insertMenuId,
                    'lang_value' => $value,
                ];
                $local = $key; //可选
                app($this->langService)->addDynamicLang($langData, $local);
                app($this->langService)->addDynamicLang($configData, $local);

            }
        } else {
            $langData = [
                'table' => 'menu',
                'column' => 'menu_name',
                'lang_key' => "menu_" . $insertMenuId,
                'lang_value' => $data['menu_name'],
            ];
            $configData = [
                'table' => 'custom_menu_config',
                'column' => 'menu_name',
                'lang_key' => "custom_config_" . $insertMenuId,
                'lang_value' => $data['menu_name'],
            ];

            app($this->langService)->addDynamicLang($langData);
            app($this->langService)->addDynamicLang($configData);
        }
        $data['menu_name'] = "menu_" . $insertMenuId;
        if ($data['menu_from'] == 0) {
            $attachment_id = isset($data['menu_icon'][0]) ? $data['menu_icon'][0] : '';
            if (!empty($data['menu_icon']) && $data['icon_type'] == 2) {
                app($this->attachmentService)->attachmentRelation("menu", $insertMenuId, $attachment_id);
                $move = ['attachment_id' => $attachment_id, 'menu_id' => $insertMenuId];
                $data['menu_icon'] = $this->uploadMenuIcon($move);
            } else {
                $data['menu_icon'] = $data['menu_icon'];
            }
        }
        $menuData = array_intersect_key($data, array_flip(app($this->menuRepository)->getTableColumns()));
        app($this->menuRepository)->insertData($menuData);
        if ($data['menu_type'] == "customize") {
            $originalValue = json_encode(['all' => 1]);
            $update = [
                'menu_name' => "custom_config_" . $insertMenuId,
                'menu_code' => $insertMenuId,
                'menu_parent' => $data['menu_parent'],
                'is_dynamic' => 2,
                'import_permission' => $originalValue,
                'export_permission' => $originalValue,
                'add_permission' => $originalValue,
                'view_permission' => $originalValue,
                'edit_permission' => $originalValue,
                'delete_permission' => $originalValue,
            ];
            DB::table("custom_menu_config")->insert($update);
        }

        //更新角色 当下级菜单 比 上级菜单角色多 更新上级菜单

        if (isset($data['is_follow']) && $data['is_follow'] == 1) {
            //继承的时候
            $adds = $this->getCurrentRoles($menuId);
        } else {
            if ($data['menu_role'] == "all") {
                $data['menu_role'] = Utils::getRoleIds();
            }

            $adds = explode(",", $data['menu_role']);
        }

        if ($adds) {
            $menus = [];
            $parents = explode(",", $data['menu_path']);
            foreach ($parents as $p) {
                if ($p == 0) {
                    continue;
                }
                $menus[] = $p;
            }

            //获取这个menus下面的 所有继承的
            $where = [
                "menu_parent" => [$menus, "in"],
                "is_follow" => [1],
            ];

            $menuTemp = app($this->menuRepository)->getMenuByWhere($where);
            $temp = [];
            foreach ($menuTemp as $temp) {
                $menus[] = $temp["menu_id"];
            }
            //更新上级菜单 $menus $adds
            foreach ($menus as $menu) {
                $oldRoles = $this->getCurrentRoles($menu);
                $add = array_diff($adds, $oldRoles);
                $this->insertDataMenu($add, $menu);
            }
            //添加本次菜单
            $this->insertDataMenu($adds, $insertMenuId);
        }

        //更新用户 user_menu
        $this->insertUserMenuByMenuId($insertMenuId);

        return $insertMenuId;
    }

    //重复利用代码 -- 给某一个菜单新增角色
    public function insertDataMenu($adds, $insertMenuId)
    {
        if ($adds) {
            foreach ($adds as $add) {
                if ($insertMenuId == 0 || $add == 0) {
                    continue;
                }
                $insertdata = [
                    "role_id" => $add,
                    "menu_id" => $insertMenuId,
                ];
                app($this->roleMenuRepository)->insertData($insertdata);
            }
        }
    }

    public function deleteDataMenu($dels, $menu_id)
    {
        if ($dels) {
            $delWhere = [
                "role_id" => [$dels, "in"],
                "menu_id" => [$menu_id],
            ];
            app($this->roleMenuRepository)->deleteByWhere($delWhere);
        }
    }

    public function editMenu($menuId, $data)
    {
        $historyInfo = app($this->menuRepository)->getDetail($menuId);
        $data['is_follow'] = isset($data['is_follow']) ? $data['is_follow'] : 0;
        //更新本源数据 -- 跟角色无关的东西
        $menuPy = Utils::convertPy($data['menu_name']);
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

        // $finalData["menu_name"]    = $data['menu_name'];
        $finalData["menu_name"] = "menu_" . $menuId;
        $finalData["menu_name_zm"] = $menuPy[1];
        $finalData["menu_order"] = isset($data['menu_order']) ? $data['menu_order'] : 0;
        $finalData["is_follow"] = $data['is_follow'];
        $params = isset($data['params']) ? $data['params'] : "";
        $finalData['state_url'] = isset($data['state_url']) && $data['state_url'] ? json_encode($data['state_url']) : "";
        $finalData["menu_class"] = isset($data['menu_class']) ? $data['menu_class'] : 0;
        $finalData["in_pc"] = isset($data['in_pc']) ? $data['in_pc'] : 0;
        $finalData["in_mobile"] = isset($data['in_mobile']) ? $data['in_mobile'] : 0;
        $finalData["icon_type"] = isset($data['icon_type']) ? $data['icon_type'] : 1;
        $menu_name_lang = isset($data['menu_name_lang']) ? $data['menu_name_lang'] : '';
        if (!empty($menu_name_lang) && is_array($menu_name_lang)) {
            foreach ($menu_name_lang as $key => $value) {
                $langData = [
                    'table' => 'menu',
                    'column' => 'menu_name',
                    'lang_key' => $finalData["menu_name"],
                    'lang_value' => $value,
                ];
                $configData = [
                    'table' => 'custom_menu_config',
                    'column' => 'menu_name',
                    'lang_key' => "custom_config_" . $menuId,
                    'lang_value' => $value,
                ];
                $local = $key; //可选
                app($this->langService)->addDynamicLang($langData, $local);
                app($this->langService)->addDynamicLang($configData, $local);

            }
        } else {
            $langData = [
                'table' => 'menu',
                'column' => 'menu_name',
                'lang_key' => "menu_" . $menuId,
                'lang_value' => $data['menu_name'],
            ];
            $configData = [
                'table' => 'custom_menu_config',
                'column' => 'menu_name',
                'lang_key' => "custom_config_" . $menuId,
                'lang_value' => $data['menu_name'],
            ];
            app($this->langService)->addDynamicLang($langData);
            app($this->langService)->addDynamicLang($configData);
        }

        //数据格式化  当改下级菜单没有child的时候 更改has_child=0 否则has_child=1 menu_type=fav
        $where = [
            "menu_parent" => [$menuId],
        ];

        $menuTemp = app($this->menuRepository)->getMenuByWhere($where);
        if ($menuTemp) {
            //存在下级
            $finalData["has_children"] = 1;
            $finalData["menu_type"] = "favoritesMenu";
            $finalData['menu_param'] = "";
            $finalData['state_url'] = "";
        } else {
            //不存在下级菜单 根据需要指定成需要的类型 当menu_type=fav时 has_children 会重新赋值
            $finalData["menu_type"] = $data['menu_type'];
            $finalData["has_children"] = 0;
        }

        switch ($finalData["menu_type"]) {
            case "web":
                $finalData["has_children"] = 0;
                $url = isset($data['param_url']) ? $data['param_url'] : "";
                $method = isset($data['param_style']) ? $data['param_style'] : "_blank";
                $finalData['menu_param'] = serialize([
                    'url' => $url,
                    'method' => $method,
                    'params' => $params,
                ]);
                break;
            case "flow":
                $url = isset($data['flow_url']) ? $data['flow_url'] : "";
                $method = isset($data['flow_type']) ? $data['flow_type'] : "1";
                $finalData['menu_param'] = serialize([
                    'url' => $url,
                    'method' => $method,
                ]);
                break;
            case "flowModeling":
                $finalData['menu_param'] = isset($data['flow_module_id']) ? $data['flow_module_id'] : "";
                break;
            case "document":
                $finalData["has_children"] = 0;
                $url = isset($data['doc_url']) ? $data['doc_url'] : "";
                $method = isset($data['doc_type']) ? $data['doc_type'] : "1";
                $finalData['menu_param'] = serialize([
                    'url' => $url,
                    'method' => $method,
                ]);

                break;
            case "systemMenu":

                $url = isset($data['sysmenu_url']) ? $data['sysmenu_url'] : "";
                $finalData["has_children"] = 0;
                $finalData['menu_param'] = serialize([
                    'url' => $url,
                ]);

                break;
            case "singleSignOn":

                $url = isset($data['sso_url']) ? $data['sso_url'] : "";
                $finalData["has_children"] = 0;
                $finalData['menu_param'] = serialize([
                    'url' => $url,
                ]);

                break;
            case "unifiedMessage":
                $url = isset($data['message_type_id']) ? $data['message_type_id'] : "";
                $finalData["has_children"] = 0;
                $finalData['menu_param'] = serialize([
                    'url' => $url,
                ]);
                break;
            case "favoritesMenu":
                $finalData["has_children"] = 1;
                $finalData['menu_param'] = "";
                $finalData['state_url'] = "";
                break;
        }
        if ($data['menu_type'] == 'customize' && $historyInfo->menu_type != $data['menu_type']) {
            $finalData['menu_param'] = "";
        }
        $attachment_id = isset($data['menu_icon'][0]) ? $data['menu_icon'][0] : '';
        if (!empty($attachment_id) && $data['icon_type'] == 2) {
            app($this->attachmentService)->attachmentRelation("menu", $menuId, $attachment_id);
            $move = ['attachment_id' => $attachment_id, 'menu_id' => $menuId];
            $finalData['menu_icon'] = $this->uploadMenuIcon($move);
        } else {
            $finalData['menu_icon'] = $data['menu_icon'];
            $menuAttachmentData = ['entity_table' => 'menu', 'entity_id' => $menuId];
            app($this->attachmentService)->deleteAttachmentByEntityId($menuAttachmentData);
        }

        $finalData['role_status'] = $data['menu_role'] == "all" ? 1 : 0;
        $return = app($this->menuRepository)->updateData($finalData, ['menu_id' => $menuId]);
        if ($data['menu_type'] == "customize") {
            $customMenu = DB::table("custom_menu_config")->where("menu_code", $data['menu_id'])->first();
            $originalValue = json_encode(['all' => 1]);
            $config['menu_name'] = "custom_config_" . $data['menu_id'];
            $config['import_permission'] = $originalValue;
            $config['export_permission'] = $originalValue;
            $config['add_permission'] = $originalValue;
            $config['view_permission'] = $originalValue;
            $config['edit_permission'] = $originalValue;
            $config['delete_permission'] = $originalValue;
            if (empty($customMenu)) {
                $config['menu_code'] = $data['menu_id'];
                $config['menu_parent'] = $data['menu_parent'];
                $config['is_dynamic'] = 2;
                DB::table("custom_menu_config")->insert($config);
            } 
            // 编辑的时候无需修改自定义字段权限
//            else {
//                $menu_name = "custom_config_" . $data['menu_id'];
//                DB::table("custom_menu_config")->where('menu_code', $data['menu_id'])->update($config);
//            }
        } else {
            if ($historyInfo->menu_type == "customize") {
                if (Schema::hasTable('custom_reminds')) {
                    app($this->menuRepository)->deleteCustomFields([$data['menu_id']]);
                    $this->refreshReminds();
                }
            }
        }
        $newRoles = explode(",", $data['menu_role']);
        //更新角色
        if ($data['menu_role'] == "all") {
            $data['menu_role'] = Utils::getRoleIds();
            $newRoles = explode(",", $data['menu_role']);
        }

        if ($data['is_follow'] == 1) {
            //获取父集的角色组
            $newRoles = $this->getCurrentRoles($data["menu_parent"]);
        }

        // menu_path  上级 / 继承的下级 - add
        // 下级及继承的下级 本级  - add del
        //
        //上级及继承的  只关心增加的部分
        $menusTop = [];
        $parents = explode(",", $data['menu_path']);
        foreach ($parents as $p) {
            if ($p == 0) {
                continue;
            }
            $menusTop[] = $p;
        }

        //获取这个menus下面的 所有继承的
        $where = [
            "menu_parent" => [$menusTop, "in"],
            "is_follow" => [1],
        ];

        $menuTemp = app($this->menuRepository)->getMenuByWhere($where);
        $temp = [];
        foreach ($menuTemp as $temp) {
            $menusTop[] = $temp["menu_id"];
        }
        foreach ($menusTop as $menu) {
            $oldRoles = $this->getCurrentRoles($menu);
            $add = array_diff($newRoles, $oldRoles);
            $this->insertDataMenu($add, $menu);
        }

        //更新本级及下级--20210408 这里不应该更新下级菜单的权限. 仅更新当前这个菜单的权限
        $oldParentRoles = $this->getCurrentRoles($menuId);
        $diff = array_diff($oldParentRoles, $newRoles);
        
        $juniors = app($this->menuRepository)->getJunior($menuId);
        foreach ($juniors as $junior) {
            $oldRoles = $this->getCurrentRoles($junior['menu_id']);
            $add = array_diff($newRoles, $oldRoles);
            $dels = array_intersect($oldRoles,$diff);
            if ($junior['menu_id'] == $menuId || ($junior['menu_parent'] == $menuId && $junior['is_follow'] == 1)) {
                 $this->insertDataMenu($add, $junior['menu_id']);
            }
            $this->deleteDataMenu($dels, $junior['menu_id']);
        }
        //继承
        return $return ? 1 : false;
    }

    public function uploadMenuIcon($data)
    {
        $attachment = app($this->attachmentService)->getOneAttachmentById($data['attachment_id']);
        if (!empty($attachment)) {
            $attachmentId = $attachment['attachment_id'];

            $suffix = $attachment['attachment_type'];

            $source = $attachment['temp_src_file'];

            if (file_exists($source)) {
                if(!is_dir($this->menuIconPath)){
                    @mkdir($this->menuIconPath, 0777);
                } else {
                    // D:\e-office10\www\eoffice10\server/public/access/images/menu/icons/
                    // @chmod($this->menuIconPath, 0777);
                    // 替换为会报错的dir验证
                    $dirPermission = verify_dir_permission($this->menuIconPath);
                    if(is_array($dirPermission) && isset($dirPermission['code'])) {
                        return $dirPermission;
                    }
                }
                $theme = $this->menuIconPath . 'menu_' . $data['attachment_id'] . '_' . $data['menu_id'] . '.' . $suffix;
                copy($source, $theme);
                return 'menu_'. $data['attachment_id'] . '_' . $data['menu_id'] . '.' . $suffix;
            }
        }

        return ['code' => ['0x003011', 'auth']];
    }

    private function makeDir($path)
    {
        if (!$path || $path == '/' || $path == '\\') {
            return $this->publicPath;
        }

        $dir = $this->publicPath;

        $dirNames = explode('/', trim(str_replace('\\', '/', $path), '/'));

        foreach ($dirNames as $dirName) {
            $dir .= $dirName . '/';

            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);

                chmod($dir, 0777);
            }
        }

        return $dir;
    }

    //获取当前角色组
    public function getCurrentRoles($menu_id)
    {
        $roleList = app($this->roleMenuRepository)->getMenusGroupByRoleId(['menu_id' => [$menu_id]]);
        $roleIds = [];
        foreach ($roleList as $role) {
            $roleIds[] = $role['role_id'];
        }
        return $roleIds;
    }

    public function deleteMenu($menuId)
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
        //获取下级 一并删除
        $juniors = app($this->menuRepository)->getJunior($menuId);
        $delMenus = [];
        foreach ($juniors as $junior) {
            $delMenus[] = $junior['menu_id'];
        }

        $delWhere = [
            "menu_id" => [$delMenus, "in"],
        ];
        app($this->menuRepository)->deleteByWhere($delWhere);
        app($this->roleMenuRepository)->deleteByWhere($delWhere);
        app($this->userMenuRepository)->deleteByWhere($delWhere);
        if (Schema::hasTable('custom_reminds')) {
            app($this->menuRepository)->deleteCustomFields($delMenus);
            $this->refreshReminds();
        }
        FormModelingRepository::destroyCustomTabMenus();
        $this->refreshFields();
        return true;
    }

    //获取个性设置菜单 -- menus
    public function getMenuList($menu_parent, $data)
    {
        //获取用户权限的菜单
        $excludeMenu = $this->getPermissionModules();
        $customMenus = $this->getCustomMenuPower();
        $excludeMenu = array_merge($customMenus,$excludeMenu);
        $user_id = own('user_id');
        $roleData = app($this->userRoleRepository)->getUserRole(['user_id' => $user_id]);

        $roleObj = [];
        foreach ($roleData as $role) {
            $roleObj[] = $role['role_id'];
        }

        //获取用户隐藏的菜单ID
        $menus = $this->getMenus($roleObj);
        $menuUp2000 = $this->getMenuUp2000($user_id);

        $menus = array_merge($menus, $menuUp2000);
        $menus = array_diff($menus, $excludeMenu);
        //>2000
        $menuAll = app($this->menuRepository)->getCustomByUserId($menus, $user_id, $menu_parent);
        $menuAll = $this->arrayMenuLang($menuAll);
        $res = [];
        if (!empty($menuAll)) {
            foreach ($menuAll as $t) {
                $menuID = $t['menu_id'];
                if (!in_array($menuID, $menus)) {
                    continue;
                }

                $res[] = $t;
            }
        }
        return $res;
    }

    private function getMenuUp2000($userId)
    {
        $whereMenu = [
            "user_id" => [$userId],
        ];
        $userMenu = app($this->userMenuRepository)->getCustomMenu($userId);
        $menuIds = [];
        foreach ($userMenu as $id) {
            $menuIds[] = $id["menu_id"];
        }

        return $menuIds;
    }

    //设置个性菜单{隐藏|显示}
    public function setUserMenu($data)
    {
        $updateData = [
            "is_show" => $data['is_show'],
        ];
        if (Cache::has('hide_menus_' . $data['user_id'])) {
            Cache::forget('hide_menus_' . $data['user_id']);
        }
        return app($this->userMenuRepository)->updateData($updateData, ['user_id' => [$data['user_id']], 'menu_id' => [$data['menu_id']]]);
    }
    /**
     * 获取用户菜单
     *
     * modify by lizhijun
     *
     * @param string $userId
     *
     * @return array
     */
    public function getUserMenus($userId, $terminal = 'PC' , $from ='')
    {
        $terminal = strtolower($terminal);
        $roleIds = $this->getUserRoleIds($userId);
        $excludeMenu = $this->getExcludeMenuId($userId); //获取该用户没有权限的菜单ID
        $menus = $this->mergeSystemManagerMenus($this->getMenus($roleIds), $userId); //按角色获取用户菜单，并处理系统管理员用户权限菜单
        $customMenus = $this->getCustomMenuId($userId); //获取用户自定义菜单

        $menus = array_values(array_diff(array_merge($menus, $customMenus), $excludeMenu));
        if ($from == 'flow_user_menu') {
            return $menus;
        }
        //获取模块的时候排除平台不展示的菜单
        $excludePlatformMenu = $this->getExcludePlatformMenus($terminal);
        $excludePlatformMenus = array_values(array_diff($menus, $excludePlatformMenu));
        $filterConfig = config('eoffice.exceptIntegrationMenu');
        if ($filterConfig) {
            $excludePlatformMenus = array_values(array_diff($excludePlatformMenus, $filterConfig));
        }
        $module = $this->handleMenuGroupByModule($excludePlatformMenus, $userId, $terminal);
        return [
            'module' => isset($module['module']) ? $module['module'] : [], //包括了自定义菜单
            'menu' => $menus,
            'moduleId' => isset($module['moduleId']) ? $module['moduleId'] : [],
        ];
    }
    public function getExcludePlatformMenus($terminal)
    {
        //获取平台不展示的菜单
        if ($terminal == "mobile") {
            $where = ["in_mobile" => [1, "!="]];
        } else {
            $where = ["in_pc" => [1, "!="]];
        }
        $platformMenu = app($this->menuRepository)->getMenuByWhere($where);
        $platformMenus = array_column($platformMenu, 'menu_id');
        return $platformMenus;
    }
    /**
     * 获取角色ID
     * @param type $userId
     * @return type
     */
    private function getUserRoleIds($userId)
    {
        if (Cache::has('user_role_' . $userId)) {
            $roleIds = Cache::get('user_role_' . $userId);
        } else {
            $this->menuModifyFlag = true;

            $roles = app($this->userRoleRepository)->getUserRole(['user_id' => $userId]);

            $roleIds = array_column($roles, 'role_id');

            Cache::forever('user_role_' . $userId, $roleIds);
        }
        return $roleIds;
    }
    /**
     * 按角色获取菜单
     *
     * modify by lizhijun
     *
     * @param type $roleIds
     *
     * @return array
     */
    public function getMenus($roleIds)
    {
        $roleMenuIds = [];

        foreach ($roleIds as $roleId) {
            if (Cache::has('role_menus_' . $roleId)) {
                $roleMenuId = Cache::get('role_menus_' . $roleId);
            } else {
                $this->menuModifyFlag = true;
                $result = app($this->roleMenuRepository)->getMenusGroupByMenuId(['role_id' => [$roleId]]);
                $roleMenuId = array_column($result, 'menu_id');
                Cache::forever('role_menus_' . $roleId, $roleMenuId);
            }
            $roleMenuIds = array_merge($roleMenuIds, $roleMenuId);
        }

        return array_unique($roleMenuIds);
    }
    /**
     * 获取排除的菜单ID
     *
     * author lizhijun
     *
     * @param type $userId
     */
    private function getExcludeMenuId($userId)
    {
        //获取无授权模块菜单
        $excludeMenuId = $this->getPermissionModules();
        if (Cache::has('no_permission_menus')) {
            $cacheExcludeMenuId = Cache::get('no_permission_menus');
            if ($excludeMenuId != $cacheExcludeMenuId) {
                $this->menuModifyFlag = true;
                Cache::forever('no_permission_menus', $excludeMenuId);
            }
        } else {
            $this->menuModifyFlag = true;
            Cache::forever('no_permission_menus', $excludeMenuId);
        }
       $customMenus = $this->getCustomMenuPower();
        //获取隐藏菜单
        if (Cache::has('hide_menus_' . $userId)) {
            $hiddenMenuIds = Cache::get('hide_menus_' . $userId);
        } else {
            $this->menuModifyFlag = true;
            $hiddenMenus = app($this->userMenuRepository)->getMenuByWhere(["is_show" => [0], "user_id" => [$userId]]);
            $hiddenMenuIds = array_column($hiddenMenus, 'menu_id');
            Cache::forever('hide_menus_' . $userId, $hiddenMenuIds);
        }
        return array_merge($excludeMenuId, $hiddenMenuIds,$customMenus);
    }
    private function getCustomMenuPower()
    {
        $customMenus = [];
        $auhtMenus = app($this->empowerService)->getPermissionModules();
        if (!empty($auhtMenus) && !in_array(156, $auhtMenus)) {
            $customMenus = app($this->menuRepository)->getMenuData(["menu_type" => ['customize']]);
            $customMenus = array_column($customMenus, 'menu_id');
        }
        //获取只有一个菜单是自定义字段的
        $parent = [];
        foreach ($customMenus as $menuId) {
            $info  = app($this->menuRepository)->getDetail($menuId);
            $child =  app($this->menuRepository)->getChildrensCount($info['menu_parent']);
            if($child == 1){
                $parent[] = $info['menu_parent'];
            }
        }
        return array_merge($customMenus,$parent);
    }
    private function mergeSystemManagerMenus($menus, $userId)
    {
        return $menus;
        if ($userId == 'admin') {
            // 管理员默认具有系统管理菜单及其子菜单的所有权限
            $systemMenus = config('eoffice.systemManageMenu');
            foreach ($systemMenus as $value) {
                if (!in_array($value, $menus)) {
                    $menus[] = $value;
                }
            }
        }

        return $menus;
    }
    /**
     * 按模块分组处理菜单
     *
     * @param type $menus
     * @param type $userId
     */
    private function handleMenuGroupByModule($menus, $userId, $terminal)
    {
        $locale = Lang::getLocale() ?? 'zh-CN';
        $modules = [];
        if ($this->menuModifyFlag) {
            $modules = $this->allUserMens($menus, $userId);
            $this->clearAllEffectLocalesMenus($userId, $terminal);
            Cache::forever('user_module_' . $locale . '_' . $terminal . '_' . $userId, $modules);
        } else {
            if (Cache::has('user_module_' . $locale . '_' . $terminal . '_' . $userId)) {
                $modules = Cache::get('user_module_' . $locale . '_' . $terminal . '_' . $userId);
            } else {
                $modules = $this->allUserMens($menus, $userId);
                Cache::forever('user_module_' . $locale . '_' . $terminal . '_' . $userId, $modules);
            }
        }
        return $modules;
    }
    private function clearAllEffectLocalesMenus($userId)
    {
        $effectLocales = $this->getEffectLocales();
        if (!empty($effectLocales)) {
            foreach ($effectLocales as $effectLocale) {
                Cache::forget('user_module_' . $effectLocale . '_pc_' . $userId);
                Cache::forget('user_module_' . $effectLocale . '_mobile_' . $userId);
            }
        }
        return true;
    }
    private function getEffectLocales()
    {
        $packages = app('App\EofficeApp\Lang\Services\LangService')->getLangPackages(['page' => 0, 'search' => ['effect' => [1]]]);

        $locales = [];
        if ($packages['total'] > 0) {
            foreach ($packages['list'] as $item) {
                $locales[] = $item->lang_code;
            }
        }

        return $locales;
    }
    //一次返回所有的菜单并排序
    public function allUserMens($menus, $user_id)
    {
        //获取的菜单menu中
        $menuAll = app($this->menuRepository)->getAllMenuOrderByUserMenuOrder($menus, $user_id);
        $menuIds = array_column($menuAll, 'menu_id');
        $menuList = [];
        foreach ($menuAll as $t) {
            $filter = [];
            $menuID = $t['menu_id'];
            if (!in_array($menuID, $menus)) {
                continue;
            }
            if ($t["has_children"] == 1) {
                $childCount = app($this->menuRepository)->getChildrensCount($menuID);
                if ($childCount == 0) {
                    continue;
                }
            }
            $path = explode(",", $t['menu_path']);
            $countPath = count($path);
            $menu_type = $t["menu_type"];
            $state_url = $t["state_url"];

            $filter["menu_id"] = $t["menu_id"];
            $filter["menu_name"] = mulit_trans_dynamic("menu.menu_name.menu_" . $menuID);

            $filter["menu_name_zm"] = $t["menu_name_zm"];
            $filter["has_children"] = $t["has_children"];
            if (isset($t["menu_class"])) {
                $filter["menu_class"] = $t["menu_class"];
            }
            if (isset($t["menu_from"]) && $t["menu_from"] != 1) {
                $filter["menu_from"] = $t["menu_from"];
            }
            if (isset($t["menu_from"]) && $t["menu_from"] == 0) {
                $filter["menu_icon"] = isset($t['menu_icon']) ? $t['menu_icon'] : '';
                $filter["icon_type"] = isset($t['icon_type']) ? $t['icon_type'] : 1;
            }

            if ($t["menu_type"] == "customize") {
                $filter["menu_type"] = $t["menu_type"];

            }
            if ($menuID > 999) {
                if ($menu_type == "singleSignOn") {
                    $tempMenuParam = unserialize($t["menu_param"]);
                    $t["menu_extras"] = [];
                    if ($tempMenuParam) {
                        $sso_id = $tempMenuParam["url"];
                        //获取sso配置信息
                        // $data["sso_id"] = $sso_id;
                        $t["menu_extras"] = [
                            "type" => 'singleSignOn',
                            "sso_id" => $sso_id,
                        ];
                    }
                } elseif ($menu_type == 'flow') {
                    $t["menu_extras"] = json_decode($t['state_url'], true);
                } elseif ($menu_type == 'flowModeling') {
                    if (isset($t['menu_has_one_flow_module_factory']['state_url'])) {
                        $t["menu_extras"] = json_decode($t['menu_has_one_flow_module_factory']['state_url'], 1);
                    } else {
                        $t["menu_extras"] = [];
                    }
                } else {
                    $t["menu_extras"] = json_decode($state_url, 1);
                }
                $filter["menu_type"] = $t["menu_type"];
                $filter["menu_parent"] = $t["menu_parent"];
                // if ($t["menu_from"] != 1 && $filter["menu_type"] != "customize") {

                // }

            }else{
                 $t["menu_extras"] = json_decode($state_url, 1);
            }
            if(isset($t["menu_extras"]) && !empty($t["menu_extras"])){
                $filter["menu_extras"] = $t["menu_extras"];
            }
            //sso 单独处理
            if ($countPath == 1) {
                //一级菜单
                $menuList[$menuID] = $filter;
            } else if ($countPath == 2) {
                //二级菜单
                $topMenuID = $path[1];
                if (!in_array($topMenuID, $menuIds) || !in_array($topMenuID, $menus)) {
                    //上级隐藏了 下级不进行重组
                    continue;
                }
                if ($t["has_children"] == 1) {
                    //把三级菜单插入

                    $juniorsStr = $this->getLimitsMenuIdsByMenuId($menuID);
                    $juniors = explode(",", $juniorsStr);

                    $resMenus = array_intersect($juniors, $menus);

                    $filter["item"] = $this->handleMenu($resMenus, $user_id, $menuID);
                }
                if ($t["menu_type"] == "customize") {

                    $filter["menu_code"] = $t["menu_id"];
                }
                $menuList[$topMenuID]["item"][] = $filter;
            } else if ($countPath == 3) {
                continue;
            }
        }
        $menuClass = $this->getMenuSort([]);
        $menuArray = [];
        $lists = [];
        $unItemList = [];
        $moduleId = [];
        foreach ($menuClass['list'] as $class) {
            foreach ($menuList as $menu) {
                if (isset($menu['item']) && !empty($menu['item']) && isset($menu['menu_class']) && !empty($menu['menu_class'])) {
                    if ($class['id'] == $menu['menu_class']) {
                        $lists[$class['id']]['name'] = $class['class_name'];
                        $lists[$class['id']]['id'] = $class['id'];
                        // $lists[$class['id']]['has_children']    = 1;
                        $lists[$class['id']]['item'][] = $menu;
                        $menuArray[] = $menu['menu_id'];
                    }
                }
            }
        }
        foreach ($menuList as $key => $value) {
            $moduleId[] = $value['menu_id'];
            if (isset($value['item']) && !empty($value['item']) && !in_array($value['menu_id'], $menuArray)) {
                $unItemList[] = $value;
            }
        }
        if (!empty($unItemList)) {
            $unsort = ['name' => trans('menu.not_classified'), 'id' => 0, 'item' => $unItemList];
            array_push($lists, $unsort);
        }
        return ['module' => array_merge($lists), 'moduleId' => $moduleId];
    }

    //获取某个模块下的菜单
    //
    public function handleMenu($menu, $user_id, $menuParent)
    {
        $menus = app($this->menuRepository)->getCustomByUserId($menu, $user_id, $menuParent);
        $temp = [];
        $sso;

        foreach ($menus as $t) {
            $filter = [];
            $menuID = $t['menu_id'];
            if (!in_array($menuID, $menu)) {
                continue;
            }
            $path = explode(",", $t['menu_path']);
            $countPath = count($path);
            $menu_type = $t["menu_type"];
            $state_url = $t["state_url"];

            $filter["menu_id"] = $t["menu_id"];
            $filter["menu_name"] = mulit_trans_dynamic("menu.menu_name.menu_" . $menuID);
            $filter["menu_name_zm"] = $t["menu_name_zm"];
            $filter["has_children"] = $t["has_children"];
            if (isset($t["menu_from"]) && $t["menu_from"] != 1) {
                $filter["menu_from"] = $t["menu_from"];
            }
            if ($menuID > 999) {
                if ($menu_type == "singleSignOn") {
                    $tempMenuParam = unserialize($t["menu_param"]);
                    $t["menu_extras"] = [];
                    if ($tempMenuParam) {
                        $sso_id = $tempMenuParam["url"];
                        //获取sso配置信息
                        $t["menu_extras"] = [
                            "type" => 'singleSignOn',
                            "sso_id" => $sso_id,
                        ];
                    }
                } elseif ($menu_type == 'flow') {
                    $t["menu_extras"] = json_decode($t['state_url'], true);
                } elseif ($menu_type == 'flowModeling') {
                    if (isset($t['menu_has_one_flow_module_factory']['state_url'])) {
                        $t["menu_extras"] = json_decode($t['menu_has_one_flow_module_factory']['state_url'], 1);
                    } else {
                        $t["menu_extras"] = [];
                    }
                } else {
                    $t["menu_extras"] = json_decode($state_url, 1);
                }
                $filter["menu_type"] = $t["menu_type"];
                $filter["menu_parent"] = $t["menu_parent"];
                $filter["menu_extras"] = $t["menu_extras"];
                if (isset($t["menu_from"]) && $t["menu_from"] == 0) {
                    $filter["menu_icon"] = isset($t['menu_icon']) ? $t['menu_icon'] : '';
                    $filter["icon_type"] = isset($t['icon_type']) ? $t['icon_type'] : 1;
                }
            }else{
                $filter["menu_extras"] = json_decode($state_url, 1);
            }
            $temp[] = $filter;
        }

        return $temp;
    }

    /**
     * 获取用户自定义菜单ｉｄ　》２０００
     *
     * modify by lizhijun
     */
    public function getCustomMenuId($userId)
    {
        if (Cache::has('custom_menus_' . $userId)) {
            $menuIds = Cache::get('custom_menus_' . $userId);
        } else {
            $this->menuModifyFlag = true;
            $customMenu = app($this->userMenuRepository)->getCustomMenuId($userId);
            $menuIds = array_column($customMenu, 'menu_id');
            Cache::forever('custom_menus_' . $userId, $menuIds);
        }

        return $menuIds;
    }

    //
    //获取角色

    public function getSubMenu($menu_parent, $user_id)
    {
        $roleData = app($this->userRoleRepository)->getUserRole(['user_id' => $user_id]);
        $roleObj = [];
        foreach ($roleData as $role) {
            $roleObj[] = $role['role_id'];
        }
        $menus = $this->getMenus($roleObj);

        $item = $this->recursionMenus($menu_parent, $menus);

        return $item;
    }

    // 得到菜单
    public function recursionMenus($menu_parent, $menus)
    {

        $where = [
            "menu_parent" => [$menu_parent],
            "menu_id" => [$menus, "in"],
        ];
        $Top = app($this->menuRepository)->getMenuData($where);
        $item = [];
        foreach ($Top as $t) {
            if ($t['has_children'] == 1) {

                $t['item'] = $this->recursionMenus($t['menu_id'], $menus);
            }

            $item[] = $t;
        }
        return $item;
    }
    //获取角色
    public function getRoleMenu($roleId, $parent = 0)
    {
        $roleIds = explode(',', $roleId);
        $roleMenuIds = [];
        if (count($roleIds) == 1) {
            $roleMenuIds = $this->getMenus([$roleId]);
        }
        //获取role
        $role = app($this->roleRepository)->getDetail($roleId);
        $where = [
            "menu_from" => [2, "!="],
        ];
        //获取所有的菜单menu中
        $menus = $this->arrayMenuLang(app($this->menuRepository)->getMenuByWhere($where));
        $permissionModules = $this->getPermissionModules(); //返回限制模块id
        //获取当前有角色权限的菜单
        $roleMenuList = $this->handleRoleMenuList($menus, $permissionModules, $roleMenuIds);
        if (count($roleIds) > 1) {
            return [
                "menu_list" => $roleMenuList,
                "role_name" => '',
            ];
        }
        return [
            "menu_list" => $roleMenuList,
            "role_name" => $role->role_name,
        ];
    }

    //获取角色菜单树
    public function getRoleMenuTree($roleId, $parent = 0, $type = 3)
    {
        $roleIds = explode(',', $roleId);
        $roleMenuIds = [];
        if ($type == 3 && count($roleIds) == 1) {
            $roleMenuIds = $this->getMenus([$roleId]);
        }
        //获取role
        $role = app($this->roleRepository)->getDetail($roleId);
        $where = [
            "menu_from" => [2, "!="],
        ];
        //获取所有的菜单menu中
        $menus = $this->arrayMenuLang(app($this->menuRepository)->getMenuByWhere($where));
        $permissionModules = $this->getPermissionModules(); //返回限制模块id
        //获取当前有角色权限的菜单
        $roleMenuList = $this->handleRoleMenuTreeList($menus, $permissionModules, $roleMenuIds);
        if (count($roleIds) > 1) {
            return [
                "menu_list" => $roleMenuList,
                "role_name" => '',
            ];
        }
        return [
            "menu_list" => $roleMenuList,
            "role_name" => $role->role_name,
        ];
    }
    private function handleRoleMenuList($menus, $permissionModules, $roleMenuId)
    {
        $modules = $this->getChildrenMenusFromMenuArray($menus, 0);
        $menuList = [];
        foreach ($modules as $module) {
            $menuId = $module['menu_id'];
            if (!in_array($menuId, $permissionModules)) {
                $module['has_priv'] = in_array($menuId, $roleMenuId) ? 1 : 0;
                if ($module['has_children'] == 1) {
                    $module['item'] = $this->handleRoleChildrenMenu($menus, $menuId, $roleMenuId);
                }
                $menuList[] = $module;
            }
        }

        return $menuList;
    }

    private function handleRoleMenuTreeList($menus, $permissionModules, $roleMenuId)
    {
        $modules = $this->getChildrenMenusFromMenuArray($menus, 0);
        $menuList = [];
        foreach ($modules as $module) {
            $menuId = $module['menu_id'];
            if (!in_array($menuId, $permissionModules)) {
                $module['has_priv'] = in_array($menuId, $roleMenuId) ? 1 : 0;
                if ($module['has_children'] == 1) {
                    $module['children'] = $this->handleRoleChildrenMenuTree($menus, $menuId, $roleMenuId);
                }
                $menuList[] = $module;
            }
        }

        return $menuList;
    }

    private function handleRoleChildrenMenu(&$menus, $parentId = 0, $roleMenuId, $handleMenus = [])
    {
        $childrenMenus = $this->getChildrenMenusFromMenuArray($menus, $parentId);
        $customMenus = $this->getCustomMenuPower();
        $auhtMenus = app($this->empowerService)->getPermissionModules();
        if (!empty($childrenMenus)) {
            foreach ($childrenMenus as $menu) {
                $menuId = $menu['menu_id'];
                if ($menu['menu_parent'] == 280 && !in_array($menuId, $auhtMenus)) {
                    continue;
                }
                if(!in_array($menuId,$customMenus)){
                    $menu['has_priv'] = in_array($menuId, $roleMenuId) ? 1 : 0;
                    if ($menu['has_children'] == 1) {
                        $menu['item'] = $this->handleRoleChildrenMenu($menus, $menuId, $roleMenuId, []);
                    }
                    $handleMenus[] = $menu;
                }
            }
        }

        return $handleMenus;
    }

    private function handleRoleChildrenMenuTree(&$menus, $parentId = 0, $roleMenuId, $handleMenus = [])
    {
        $childrenMenus = $this->getChildrenMenusFromMenuArray($menus, $parentId);
        $customMenus = $this->getCustomMenuPower();
        if (!empty($childrenMenus)) {
            foreach ($childrenMenus as $menu) {
                $menuId = $menu['menu_id'];
                if(!in_array($menuId,$customMenus)){
                    $menu['has_priv'] = in_array($menuId, $roleMenuId) ? 1 : 0;
                    if ($menu['has_children'] == 1) {
                        $menu['children'] = $this->handleRoleChildrenMenu($menus, $menuId, $roleMenuId, []);
                    }
                    $handleMenus[] = $menu;
                }
            }
        }

        return $handleMenus;
    }
    private function getChildrenMenusFromMenuArray(&$menus, $parentId = 0, $orderField = 'menu_order', $orderDir = 'asc')
    {
        $childrenMenus = [];

        foreach ($menus as $key => $menu) {
            if ($menu['menu_parent'] == $parentId) {
                $childrenMenus[] = $menu;
                unset($menus[$key]);
            }
        }

        if ($orderDir == 'asc') {
            array_multisort(array_column($childrenMenus, $orderField), SORT_ASC, $childrenMenus);
        } else {
            array_multisort(array_column($childrenMenus, $orderField), SORT_DESC, $childrenMenus);
        }

        return $childrenMenus;
    }
    //设置角色
    public function setRoleMenu($role_id, $data)
    {

        $roleId = explode(',', $role_id);
        if (count($roleId) == 1) {
            return $this->unifiedSettingRole($role_id, $data);
        } else if ($roleId) {
            foreach ($roleId as $key => $value) {
                $this->unifiedSettingRole($value, $data);
            }
            return true;
        }
    }

    public function unifiedSettingRole($role_id, $data)
    {
        $delMenu = isset($data["noCheckIds"]) && !empty($data["noCheckIds"]) ? trim($data["noCheckIds"], ",") : "";
        $addMenu = isset($data["checkIds"]) && !empty($data["checkIds"]) ? trim($data["checkIds"], ",") : "";
        $type = isset($data["type"]) && !empty($data["type"]) ? $data["type"] : 3;

        $delMenus = explode(",", $delMenu);
        $addMenus = explode(",", $addMenu);
        try {

            //获取旧的role_id
            $roleList = app($this->roleMenuRepository)->getMenusGroupByMenuId(['role_id' => [$role_id]]);

            $oldMenu = [];
            foreach ($roleList as $role) {
                $oldMenu[] = $role['menu_id'];
            }

            if($type == 1){
                $adds = array_diff($addMenus, $oldMenu);
                foreach ($adds as $add) {
                    $insertdata = [
                        "role_id" => $role_id,
                        "menu_id" => $add,
                    ];
                    app($this->roleMenuRepository)->insertData($insertdata);
                }
            }else if($type == 2){
                $delWhere = [
                    "menu_id" => [$addMenus, "in"],
                    "role_id" => [$role_id],
                ];
                app($this->roleMenuRepository)->deleteByWhere($delWhere);
            }else{
                $adds = array_diff($addMenus, $oldMenu);
                foreach ($adds as $add) {
                    $insertdata = [
                        "role_id" => $role_id,
                        "menu_id" => $add,
                    ];
                    app($this->roleMenuRepository)->insertData($insertdata);
                }
                $delWhere = [
                    "menu_id" => [$delMenus, "in"],
                    "role_id" => [$role_id],
                ];
                app($this->roleMenuRepository)->deleteByWhere($delWhere);
            }
            $this->clearCache();
            return true;
        } catch (Exception $exc) {
            //echo $exc->getTraceAsString();
            return ['code' => ['0x009001', 'menu']];
        }
    }
    //强制插入用户  -- 参数是用户 ---
    public function insertUserMenu($user)
    {
        //如果是用户ID
        //获取所有的菜单ID
        $menuIds = app($this->menuRepository)->getMenuData(["menu_from" => [2, '<']]);
        $allMenus = [];
        foreach ($menuIds as $menu) {
            $allMenus[] = $menu["menu_id"];
        }

        //获取当前用户的菜单ID -- 已存在的
        $userMenuIds = app($this->userMenuRepository)->getMenuByWhere(["user_id" => [$user]]);
        $oldUserIds = [];
        foreach ($userMenuIds as $userMenuId) {
            $oldUserIds[] = $userMenuId["menu_id"];
        }

        $inUserMenuIds = array_diff($allMenus, $oldUserIds);
        $insertData = [];
        foreach ($inUserMenuIds as $menuId) {
            $insertData[] = [
                'menu_id' => $menuId,
                'user_id' => $user,
            ];
        }

        app($this->userMenuRepository)->batchInsert($insertData);
        return true;
    }

    //强制插入菜单 -- 创建菜单时使用 ----
    public function insertUserMenuByMenuId($menu_id, $sort = 0)
    {
        //获取所有的用户
        $where['search'] = [
            "user_accounts" => ["", "!="],
        ];
        $userDatas = app($this->userRepository)->getAllUsers($where);

        foreach ($userDatas as $user) {
            $insertData[] = [
                'menu_id' => $menu_id,
                'user_id' => $user["user_id"],
                'menu_order' => $sort,
            ];
        }
        app($this->userMenuRepository)->batchInsert($insertData);
        return true;
    }

    //获取某个菜单下 子菜单的信息
    public function getUserMenuByMenuParent($menu_parent, $user_id, $ownMenus)
    {

        $menus = $ownMenus['menu'];

        $where = [
            "menu.menu_parent" => [$menu_parent],
            "user_menu.is_show" => [1],
            "user_menu.user_id" => [$user_id],
            "menu.menu_id" => [$menus, "in"],
        ];
        $Top = app($this->menuRepository)->getMenuList($where, 1);

        $item = [];
        foreach ($Top as $t) {
            if ($t['has_children'] == 1) {
                $t['item'] = $this->recursionMenus($t['menu_id'], $menus);

                foreach ($t['item'] as $k => $t1) {

                    if ($t1["menu_id"] >= 1000) {
                        if ($t1["menu_type"] != 'flowModeling') {
                            $tempMenuParam = unserialize($t1["menu_param"]);
                        }
                        switch ($t1["menu_type"]) {

                            case "web":
                                if ($tempMenuParam) {
                                    $t['item'][$k]["url"] = $tempMenuParam["url"];
                                    $t['item'][$k]["method"] = $tempMenuParam["method"];
                                }

                                break;
                            case "flow":
                                if ($tempMenuParam) {
                                    $t['item'][$k]["url"] = $tempMenuParam["url"];
                                    $t['item'][$k]["method"] = $tempMenuParam["method"];
                                }
                                break;
                            case "flowModeling":
                                if (isset($t1['menu_param'])) {
                                    $flowModuleInfo = app($this->flowModelingRepository)->getDetail($t1['menu_param']);
                                    if (!empty($flowModuleInfo)) {
                                        $unserialize = unserialize($flowModuleInfo->module_param);
                                        $t['item'][$k]['flow_url'] = $unserialize["url"];
                                        $t['item'][$k]['flow_type'] = $unserialize["method"];
                                        $t['item'][$k]['flowModuleInfo'] = isset($unserialize["flowModuleInfo"]) ? $unserialize["flowModuleInfo"] : "";
                                    }
                                    $t['item'][$k]['flow_module_id'] = $t1['menu_param'];
                                }
                                break;

                            case "document":

                                if ($tempMenuParam) {
                                    $t['item'][$k]["url"] = $tempMenuParam["url"];
                                    $t['item'][$k]["method"] = $tempMenuParam["method"];
                                }

                                break;
                            case "systemMenu":

                                if ($tempMenuParam) {
                                    $t['item'][$k]["url"] = $tempMenuParam["url"];
                                }

                                break;
                            case "singleSignOn":

                                if ($tempMenuParam) {
                                    $t['item'][$k]["url"] = $tempMenuParam["url"];
                                }

                                break;
                            default:

                                break;
                        }
                    }
                }
            } else {
                //增加自定义菜单的路由 以及 原菜单ID
                if ($t["menu_id"] >= 1000) {
                    if ($t["menu_type"] != 'flowModeling') {
                        $tempMenuParam = unserialize($t["menu_param"]);
                    }
                    switch ($t["menu_type"]) {

                        case "web":
                            if ($tempMenuParam) {
                                $t["url"] = $tempMenuParam["url"];
                                $t["method"] = $tempMenuParam["method"];
                            }

                            break;
                        case "flow":
                            if ($tempMenuParam) {
                                $t["url"] = $tempMenuParam["url"];
                                $t["method"] = $tempMenuParam["method"];
                            }
                            break;
                        case "flowModeling":
                            if (isset($t['menu_param'])) {
                                $flowModuleInfo = app($this->flowModelingRepository)->getDetail($t['menu_param']);
                                if (!empty($flowModuleInfo)) {
                                    $unserialize = unserialize($flowModuleInfo->module_param);
                                    $t['flow_url'] = $unserialize["url"];
                                    $t['flow_type'] = $unserialize["method"];
                                    $t['flowModuleInfo'] = isset($unserialize["flowModuleInfo"]) ? $unserialize["flowModuleInfo"] : "";
                                }
                                $t['flow_module_id'] = $t['menu_param'];
                            }
                            break;
                        case "document":

                            if ($tempMenuParam) {
                                $t["url"] = $tempMenuParam["url"];
                                $t["method"] = $tempMenuParam["method"];
                            }

                            break;
                        case "systemMenu":
                            if ($tempMenuParam) {
                                $t["url"] = $tempMenuParam["url"];
                            }
                            break;
                        case "singleSignOn":

                            if ($tempMenuParam) {
                                $t["url"] = $tempMenuParam["url"];
                            }

                            break;
                        default:

                            break;
                    }

                    $t["state"] = json_decode($t["state_url"], true);
                }
            }
            $item[] = $t;
        }
        return $item;
    }

    //获取某个菜单下兄弟菜单信息
    public function getMenuSibling($menu_id, $user_id, $ownMenus)
    {

        //获取当前菜单的父节点
        $parentNode = app($this->menuRepository)->getDetail($menu_id);
        $menuPath = explode(",", $parentNode['menu_path']);
        if (count($menuPath) > 2) {
            //三级菜单
            $parentNode = app($this->menuRepository)->getDetail($parentNode['menu_parent']);
        }

        $menus = $this->getUserMenuByMenuParent($parentNode['menu_parent'], $user_id, $ownMenus);
        return [
            "parent_menu_id" => $parentNode['menu_parent'],
            "menus" => $menus,
        ];
    }

    public function getMenuRoleUserbyMenuId($menu_id)
    {
        if (!$menu_id) {
            return [];
        }
        $menu_detail = app($this->menuRepository)->getDetail($menu_id);
        if ($menu_detail->menu_from == 2) {
            //个性自定义菜单,返回菜单拥有者

            $menuData = app($this->userMenuRepository)->getMenuByWhere(["menu_id" => [$menu_id], "is_show" => [1]]);

            if (isset($menuData[0]["user_id"])) {
                return [
                    $menuData[0]["user_id"],
                ];
            } else {
                return [];
            }
        } else {
            //系统下的角色菜单
            //获取当前菜单所有的角色ID
            $userData = app($this->roleMenuRepository)->getUsersByMenuId($menu_id);
            $users = [];
            foreach ($userData as $user) {
                $users[] = $user["user_id"];
            }

            //满足某些角色的用户
            $roleData = $this->getCurrentRoles($menu_id);
            $roleUsers = [];
            $roleResult = app($this->userRoleRepository)->getUserRole(["role_id" => [$roleData, "in"]], 1);
            foreach ($roleResult as $role) {
                $roleUsers[] = $role["user_id"];
            }

            return array_intersect($users, $roleUsers);

            // return $users;
        }
    }

    public function setUserMenuOrder()
    {
        //根据menu的menu_order字段值 批量修改user_menu的 menu_order 的值
        $role = app($this->roleRepository)->getAllRoles([]);
        foreach ($role as $v) {
            if (Cache::has("role_menus_" . $v['role_id'])) {
                Cache::forget('role_menus_' . $v['role_id']);
            }
        }
        //
        try {
            $menuData = app($this->menuRepository)->getMenuData([]);
            $display_order = [];
            foreach ($menuData as $v) {

                $display_order[$v["menu_id"]] = $v["menu_order"];
            }

            $ids = implode(',', array_keys($display_order));
            $sql = "UPDATE user_menu SET menu_order = CASE menu_id ";
            foreach ($display_order as $id => $ordinal) {
                $sql .= sprintf("WHEN %d THEN %d ", $id, $ordinal);
            }
            $sql .= "END WHERE menu_id IN ($ids)";

            //执行SQL
            DB::statement($sql);
            return 1;
        } catch (Exception $exc) {

            return ['code' => ['0x009001', 'menu']];
        }
    }

    //获取有权限的菜单 --- 用户 手机版
    public function getUseMenuList($user_id, $data)
    {
        // 授权的[]
        // 有权限的【role_id】
        // 自定义创建的 【>2000的 and user_id】
        // 非隐藏的[is_show =1]
        $excludeMenu = $this->getPermissionModules();
        //获取用户隐藏的菜单ID
        $whereMenu = [
            "is_show" => [0],
            "user_id" => [$user_id],
        ];
        $hiddenMenu = app($this->userMenuRepository)->getMenuByWhere($whereMenu);
        $hiddenMenuIds = [];
        foreach ($hiddenMenu as $hidden) {
            $hiddenMenuIds[] = $hidden["menu_id"];
        }
        $excludeMenu = array_merge($excludeMenu, $hiddenMenuIds);
        //获取当前角色 -[多角色，找到有权限的菜单]
        $roleObjResult = app($this->userRoleRepository)->getUserRole(["user_id" => [$user_id]]);

        $roleObj = [];
        foreach ($roleObjResult as $role) {
            $roleObj[] = $role["role_id"];
        }

        $menus = $this->getMenus($roleObj);
        $customMenus = $this->getCustomMenuId($user_id); //获取用户自定义菜单

        $menus = array_values(array_diff(array_merge($menus, $customMenus), $excludeMenu));

        $excludePlatformMenu = $this->getExcludePlatformMenus('mobile');
        $excludePlatformMenus = array_values(array_diff($menus, $excludePlatformMenu));

        //获取当前这个人 的菜单
        //获取当前有角色权限的菜单
        $module = $this->allUserMens($excludePlatformMenus, $user_id);
        return $module['module'];
    }

    //新建类菜单
    public function getCreateMenuList($user_id, $data)
    {

        $excludeMenus = $this->getPermissionModules();
        //获取用户隐藏的菜单ID
        $whereMenu = [
            "is_show" => [0],
            "user_id" => [$user_id],
        ];
        $hiddenMenu = app($this->userMenuRepository)->getMenuByWhere($whereMenu);
        $hiddenMenuIds = [];
        foreach ($hiddenMenu as $hidden) {
            $hiddenMenuIds[] = $hidden["menu_id"];
        }

        $excludeMenu = array_merge($excludeMenus, $hiddenMenuIds);
        //获取当前角色 -[多角色，找到有权限的菜单]
        $roleObjResult = app($this->userRoleRepository)->getUserRole(["user_id" => [$user_id]]);

        $roleObj = [];
        foreach ($roleObjResult as $role) {
            $roleObj[] = $role["role_id"];
        }

        $menus = $this->getMenus($roleObj);

        $menus = array_diff($menus, $excludeMenu);

        // 新建类菜单ID
        $createMenus = config('eoffice.createMenus');
        $menus = array_intersect($createMenus, $menus);

        //获取当前有角色权限的菜单
        $wheres = [
            "menu_id" => [$menus, "in"],
        ];

        //获取的菜单menu中
        $field = [
            "menu_id",
            "menu_name",
            "menu_name_zm",
            "menu_parent",
        ];
        $menuAll = app($this->menuRepository)->getMenuData($wheres, $field);
        return $this->arrayMenuLang($menuAll);
        // return $menuAll;
    }

    //返回有权限的
    public function getPermissionModules()
    {
        // $temp = app($this->menuRepository)->getMenuByWhere(["menu_parent" => [0], "menu_id" => [1000, "<"]], ["menu_id"]);
        $search = [
            'menu_id' => ['1000', '<'],
            'multiSearch' => [
                'menu_parent' => ['0'],
                'multiSearch' => [
                    'menu_parent' => ['280'],
                    'menu_id' => ['281', '!='],
                ],
                '__relation__' => 'or',
            ],
            '__relation__' => 'and',
        ];

        $temp = app($this->menuRepository)->getMenuByWhere($search, ['menu_id'], false);

        $sysAllMenuId = [];
        foreach ($temp as $v) {
            $sysAllMenuId[] = $v["menu_id"];
        }

        $auhtMenus = app($this->empowerService)->getPermissionModules();
        if (isset($auhtMenus["code"])) {
            return $sysAllMenuId;
        }
        $limitMenus = array_diff($sysAllMenuId, $auhtMenus);

        //连带这些ID的子集也收回权限
        $limitTotal = "";
        $resultTemp = "";
        foreach ($limitMenus as $limit) {
            $limitTotal .= $this->getLimitsMenuIdsByMenuId($limit) . ",";
        }

        return explode(",", trim($limitTotal, ","));
    }

    public function getLimitsMenuIdsByMenuId($menuId)
    {
        $juniors = app($this->menuRepository)->getJunior($menuId);
        $tempMenus = "";
        foreach ($juniors as $junior) {
            $tempMenus .= $junior['menu_id'] . ",";
        }

        return trim($tempMenus, ",");
    }
    public function delSystemMenu($menuId)
    {
        app($this->menuRepository)->deleteByWhere(['menu_id' => [$menuId]]);
        app($this->userMenuRepository)->deleteByWhere(['menu_id' => [$menuId]]);
        app($this->roleMenuRepository)->deleteByWhere(['menu_id' => [$menuId]]);

        return true;
    }
    //新建菜单没有父级菜单时(除0以外)$role_menu参数传113，有父级菜单则传父菜单id
    //$sort为菜单的排序，默认是0可以更改排序
    //$this->addSystemMenuFunc(1, "流程审批", 1, 0, 1, 1); // $role_menu 某个菜单ID对应的role组 方便插入的时候就有权限
    public function addSystemMenuFunc($menu_id, $menu_name, $menu_from, $menu_parent, $has_children, $role_menu, $sort = 0)
    {
        //把menu_id的值清空掉 保证数据库数据没有垃圾数据
        app($this->menuRepository)->deleteByWhere(['menu_id' => [$menu_id]]);
        app($this->userMenuRepository)->deleteByWhere(['menu_id' => [$menu_id]]);
        app($this->roleMenuRepository)->deleteByWhere(['menu_id' => [$menu_id]]);

        if ($menu_parent == 0) {
            $menu_path = 0;
        } else {
            $menuData = app($this->menuRepository)->getDetail($menu_parent);
            $menu_path = $menuData['menu_path'] . "," . $menu_parent;
        }
        $allRoles = app($this->roleRepository)->getAllRoles([]);
        foreach ($allRoles as $v) {
            if (Cache::has("role_menus_" . $v['role_id'])) {
                Cache::forget('role_menus_' . $v['role_id']);
            }
        }
        $menuPy = Utils::convertPy($menu_name);
        $menu_name_zm = $menuPy[1];
        $menuData = [
            "menu_id" => $menu_id,
            "menu_name" => $menu_name,
            "menu_name_zm" => $menu_name_zm,
            "menu_parent" => $menu_parent,
            "menu_path" => $menu_path,
            "has_children" => $has_children,
            "menu_order" => $sort,
        ];
        if (Schema::hasColumn('menu', 'is_system')) {
            $menuData['is_system'] = $menu_from;
        } else {
            $menuData['menu_from'] = $menu_from;
        }
        if (Schema::hasColumn('menu', 'in_pc')) {
            $menuData['in_pc'] = 1;
            $menuData['in_mobile'] = 1;
        }
        app($this->menuRepository)->insertData($menuData);
        $this->insertUserMenuByMenuId($menu_id, $sort);
        //分配角色
        //获取某个节点的roles
        $roles = $this->getRoleIdArrayByMenuId($role_menu);
        $insertData = [];
        foreach ($roles as $role) {
            $insertData[] = [
                'menu_id' => $menu_id,
                'role_id' => $role,
            ];
        }
        app($this->roleMenuRepository)->batchInsert($insertData);

        return true;
    }

    //删除用户的菜单配置信息
    public function deleteUserMenuByUserId($userId)
    {
        $where['user_id'] = [$userId];
        if ($result = app($this->userMenuRepository)->deleteByWhere($where)) {
            return $result ? 1 : ['code' => ['0x000003', 'common']];
        }
    }

    //根据菜单ID获取有此菜单ID的角色ID数组
    public function getRoleIdArrayByMenuId($menuId)
    {
        // 20190815-dp-考虑到$menuId可能会传逗号拼接字符串的情况，修改这里的where
        $menuIdArray = explode(",", trim($menuId, ","));
        $where['menu_id'] = [$menuIdArray, 'in'];
        $roleList = app($this->roleMenuRepository)->getMenusGroupByRoleId($where);
        $roleIdArray = array();
        if (!empty($roleList)) {
            foreach ($roleList as $key => $value) {
                $roleIdArray[] = $value['role_id'];
            }
        }
        return $roleIdArray;
    }

    //个性自定义菜单
    public function setCustomMenu($data)
    {
        $data['user_id'] = own('user_id');
        //  user_menu:user_id  menu_id is_show = 1 menu_frequency = 0
        $data["is_follow"] = 0;
        $data["menu_from"] = 0;
        $menuId = isset($data["menu_id"]) ? $data["menu_id"] : 0; // 就是父ID
        $tempMenuId = app($this->menuRepository)->getMaxMenuId(); //插入的menu_id
        $insertMenuId = $tempMenuId < 1000 ? 1000 : $tempMenuId + 1;
        $menuPy = Utils::convertPy($data['menu_name']);

        $data['menu_name_zm'] = $menuPy[1];
        $params = isset($data['params']) ? $data['params'] : "";

        $data['state_url'] = isset($data['state_url']) && $data['state_url'] ? json_encode($data['state_url']) : "";
        if (Cache::has("custom_menus_" . own('user_id'))) {
            Cache::forget('custom_menus_' . own('user_id'));
        }
        if (!$menuId) {
            $data['menu_path'] = 0;
            $data['menu_type'] = "";
            $data["menu_parent"] = 0;
            $data["has_children"] = 1; //主菜单一定是1
            $data['state_url'] = "";
        } else {
            //更新父级节点为has_child = 1 menu_type
            $menuParentData = app($this->menuRepository)->getDetail($menuId);

            $data['menu_parent'] = $menuId;
            $data['menu_path'] = $menuParentData['menu_path'] . "," . $menuId;
            //所有带下级菜单的的菜单 都是菜单夹形式 在页面中不可以不能单独操作
            app($this->menuRepository)->updateData(["has_children" => 1, "menu_param" => "", "menu_type" => "favoritesMenu", "state_url" => ""], ['menu_id' => $menuId]);

            switch ($data['menu_type']) {
                case "web":
                    $url = isset($data['param_url']) ? $data['param_url'] : "";
                    $method = isset($data['param_style']) ? $data['param_style'] : "_self";
                    $data['menu_param'] = serialize([
                        'url' => $url,
                        'method' => $method,
                        'params' => $params,
                    ]);
                    break;
                case "flow":
                    $url = isset($data['flow_url']) ? $data['flow_url'] : "";
                    $method = isset($data['flow_type']) ? $data['flow_type'] : "1";
                    $data['menu_param'] = serialize([
                        'url' => $url,
                        'method' => $method,
                    ]);
                    break;
                case "flowModeling":
                    $data['menu_param'] = isset($data['flow_module_id']) ? $data['flow_module_id'] : "";
                    break;
                case "document":

                    $url = isset($data['doc_url']) ? $data['doc_url'] : "";
                    $method = isset($data['doc_type']) ? $data['doc_type'] : "1";
                    $data['menu_param'] = serialize([
                        'url' => $url,
                        'method' => $method,
                    ]);

                    break;
                case "systemMenu":

                    $url = isset($data['sysmenu_url']) ? $data['sysmenu_url'] : "";

                    $data['menu_param'] = serialize([
                        'url' => $url,
                    ]);

                    break;
                case "unifiedMessage":

                    $url = isset($data['message_type_id']) ? $data['message_type_id'] : "";

                    $data['menu_param'] = serialize([
                        'url' => $url,
                    ]);

                    break;
                case "singleSignOn":

                    $url = isset($data['sso_url']) ? $data['sso_url'] : "";

                    $data['menu_param'] = serialize([
                        'url' => $url,
                    ]);
                    break;
                case "favoritesMenu":
                    $path = substr_count($data['menu_path'], ","); //统计层级
                    if ($path >= 2) {
                        $data["has_children"] = 0;
                    } else {
                        $data["has_children"] = 1;
                    }
                    $data['state_url'] = "";

                    break;
            }
        }

        $menu_name_lang = isset($data['menu_name_lang']) ? $data['menu_name_lang'] : '';
        if (!empty($menu_name_lang) && is_array($menu_name_lang)) {
            foreach ($menu_name_lang as $key => $value) {
                $langData = [
                    'table' => 'menu',
                    'column' => 'menu_name',
                    'lang_key' => "menu_" . $insertMenuId,
                    'lang_value' => $value,
                ];
                $local = $key; //可选
                app($this->langService)->addDynamicLang($langData, $local);

            }
        } else {
            $langData = [
                'table' => 'menu',
                'column' => 'menu_name',
                'lang_key' => "menu_" . $insertMenuId,
                'lang_value' => $data['menu_name'],
            ];
            app($this->langService)->addDynamicLang($langData);
        }

        //插入到菜单表
        $data['menu_id'] = $insertMenuId;
        $data['menu_from'] = 2;
        $data['menu_name'] = "menu_" . $insertMenuId;
        $menuData = array_intersect_key($data, array_flip(app($this->menuRepository)->getTableColumns()));
        app($this->menuRepository)->insertData($menuData);

        //更新用户 user_menu
        $data["is_show"] = 1;
        $userMenuData = array_intersect_key($data, array_flip(app($this->userMenuRepository)->getTableColumns()));
        app($this->userMenuRepository)->insertData($userMenuData);

        return $insertMenuId;
    }

    public function editCustomMenu($data)
    {
        $data['user_id'] = own('user_id');
        if (isset($data["menu_id"]) && isset($data["menu_name"])) {
            $menuPy = Utils::convertPy($data['menu_name']);
            $data['menu_name_zm'] = $menuPy[1];

            $params = isset($data['params']) ? $data['params'] : "";

            $data['state_url'] = isset($data['state_url']) && $data['state_url'] ? json_encode($data['state_url']) : "";
            if (Cache::has("custom_menus_" . own('user_id'))) {
                Cache::forget('custom_menus_' . own('user_id'));
            }
            //-----
            if (isset($data["menu_parent"]) && $data["menu_parent"] > 0) {
                switch ($data['menu_type']) {
                    case "web":
                        $url = isset($data['param_url']) ? $data['param_url'] : "";
                        $method = isset($data['param_style']) ? $data['param_style'] : "_self";
                        $data['menu_param'] = serialize([
                            'url' => $url,
                            'method' => $method,
                            'params' => $params,
                        ]);
                        break;
                    case "flow":
                        $url = isset($data['flow_url']) ? $data['flow_url'] : "";
                        $method = isset($data['flow_type']) ? $data['flow_type'] : "1";
                        $data['menu_param'] = serialize([
                            'url' => $url,
                            'method' => $method,
                        ]);
                        break;
                    case "flowModeling":
                        $data['menu_param'] = isset($data['flow_module_id']) ? $data['flow_module_id'] : "";
                        break;
                    case "document":
                        $url = isset($data['doc_url']) ? $data['doc_url'] : "";
                        $method = isset($data['doc_type']) ? $data['doc_type'] : "1";
                        $data['menu_param'] = serialize([
                            'url' => $url,
                            'method' => $method,
                        ]);
                        break;
                    case "systemMenu":

                        $url = isset($data['sysmenu_url']) ? $data['sysmenu_url'] : "";

                        $data['menu_param'] = serialize([
                            'url' => $url,
                        ]);

                        break;
                    case "unifiedMessage":

                        $url = isset($data['message_type_id']) ? $data['message_type_id'] : "";

                        $data['menu_param'] = serialize([
                            'url' => $url,
                        ]);

                        break;
                    case "singleSignOn":

                        $url = isset($data['sso_url']) ? $data['sso_url'] : "";

                        $data['menu_param'] = serialize([
                            'url' => $url,
                        ]);
                        break;
                    case "favoritesMenu":
                        $path = substr_count($data['menu_path'], ","); //统计层级
                        $data['menu_param'] = "";
                        if ($path >= 2) {
                            $data["has_children"] = 0;
                        } else {
                            $data["has_children"] = 1;
                        }
                        $data['state_url'] = "";

                        break;
                }
            }
            $menu_name_lang = isset($data['menu_name_lang']) ? $data['menu_name_lang'] : '';
            if (!empty($menu_name_lang) && is_array($menu_name_lang)) {
                foreach ($menu_name_lang as $key => $value) {
                    $langData = [
                        'table' => 'menu',
                        'column' => 'menu_name',
                        'lang_key' => "menu_" . $data["menu_id"],
                        'lang_value' => $value,
                    ];
                    $local = $key; //可选
                    app($this->langService)->addDynamicLang($langData, $local);

                }
            } else {
                $langData = [
                    'table' => 'menu',
                    'column' => 'menu_name',
                    'lang_key' => "menu_" . $data["menu_id"],
                    'lang_value' => $data['menu_name'],
                ];

                app($this->langService)->addDynamicLang($langData);
            }
            $data['menu_name'] = "menu_" . $data["menu_id"];
            $tempData = array_intersect_key($data, array_flip(app($this->menuRepository)->getTableColumns()));
            app($this->menuRepository)->updateData($tempData, ['menu_id' => $data["menu_id"]]);
        } else {
            return ['code' => ['0x000003', 'common']];
        }
    }

    public function defaultMenu($user_id)
    {
        $user_id = own('user_id');
        if (!$user_id) {
            return ['code' => ['0x000003', 'common']];
        }

        $userMenu = app($this->userMenuRepository)->getCustomMenu($user_id);
        $deleteMenuIds = [];
        foreach ($userMenu as $id) {
            $deleteMenuIds[] = $id["menu_id"];
        }
        app($this->userMenuRepository)->deleteCustomMenu($user_id);
        //删除当前用户的大于2000的自定义菜单
        app($this->menuRepository)->deleteByWhere(["menu_id" => [$deleteMenuIds, "in"]]);
        return true;
    }

    public function sortMenu($data, $userId)
    {
        $userId = own('user_id');
        if (Cache::has("custom_menus_" . own('user_id'))) {
            Cache::forget('custom_menus_' . own('user_id'));
        }
        foreach ($data as $val) {
            $menu_order = $val["menu_order"];
            $menu_id = $val["menu_id"];
            // app($this->menuRepository)->updateData(["menu_order" => $menu_order], ["menu_id" => [$menu_id]]);
            app($this->userMenuRepository)->updateData(["menu_order" => $menu_order], ["user_id" => [$userId], "menu_id" => [$menu_id]]);
        }
        return true;
    }

    public function getMenuConfig($menu_id)
    {

        $customMenuType = config('eoffice.customMenuType');
        //获取菜单的名称
        if (isset($menu_id['menu_id']) && !empty($menu_id['menu_id'])) {
            $type = isset($menu_id['type']) ? $menu_id['type'] : '';
            $menu_id = $menu_id['menu_id'];

            $menu_detail = app($this->menuRepository)->getDetail($menu_id);
            $menu_from = $menu_detail->menu_from;
            $menu_type = $menu_detail->menu_type;
            $path = explode(",", $menu_detail->menu_path);
            $countPath = count($path);
            $customMenus = [];
            $customTypes = [];
            foreach ($customMenuType as $val) {
                switch ($val) {
                    case "web":
                        $customMenus["flag"] = $val;
                        $customMenus["name"] = trans("menu.website");
                        $customTypes[] = $customMenus;
                        break;
                    case "flow":
                        $temp = app($this->menuRepository)->getDetail(1); //流程菜单ID = 1
                        $customMenus["flag"] = $val;
                        $customMenus["name"] = mulit_trans_dynamic("menu.menu_name.menu_" . 1);
                        $customTypes[] = $customMenus;
                        break;
                    case "flowModeling":
                        $temp = app($this->menuRepository)->getDetail(203); //流程建模菜单ID = 203
                        $customMenus["flag"] = $val;
                        $customMenus["name"] = mulit_trans_dynamic("menu.menu_name.menu_" . 203);
                        $customTypes[] = $customMenus;
                        break;
                    case "document":
                        $temp = app($this->menuRepository)->getDetail(6); //文档菜单ID = 1
                        $customMenus["flag"] = $val;
                        $customMenus["name"] = mulit_trans_dynamic("menu.menu_name.menu_" . 6);
                        $customTypes[] = $customMenus;
                        break;
                    case "systemMenu":
                        $customMenus["flag"] = $val;
                        $customMenus["name"] = trans("menu.system_menu");
                        $customTypes[] = $customMenus;
                        break;
                    case "singleSignOn":
                        $customMenus["flag"] = $val;
                        $customMenus["name"] = trans("menu.sso_login");
                        $customTypes[] = $customMenus;
                        break;
                    case "favoritesMenu":
                        if ($countPath < 2 || ($countPath == 2 && $type != "add")) {
                            $customMenus["flag"] = $val;
                            $customMenus["name"] = trans("menu.menu_folder");
                            $customTypes[] = $customMenus;
                        }

                        break;
                    case "customize":
                        if ($menu_from != 2) {
                            $customMenus["flag"] = $val;
                            $customMenus["name"] = trans("menu.custom_page");
                            $customTypes[] = $customMenus;
                        }
                    break;
                    case "unifiedMessage":
                        $customMenus["flag"] = $val;
                        $customMenus["name"] = trans("menu.unifiedMessage");
                        $customTypes[] = $customMenus;
                        break;
                }
            }

        } else {
            $customMenus = [];
            $customTypes = [];
            foreach ($customMenuType as $val) {
                switch ($val) {
                    case "web":
                        $customMenus["flag"] = $val;
                        $customMenus["name"] = trans("menu.website");
                        $customTypes[] = $customMenus;
                        break;
                    case "flow":
                        $temp = app($this->menuRepository)->getDetail(1); //流程菜单ID = 1
                        $customMenus["flag"] = $val;
                        $customMenus["name"] = mulit_trans_dynamic("menu.menu_name.menu_" . 1);
                        $customTypes[] = $customMenus;
                        break;
                    case "flowModeling":
                        $temp = app($this->menuRepository)->getDetail(203); //流程建模菜单ID = 203
                        $customMenus["flag"] = $val;
                        $customMenus["name"] = mulit_trans_dynamic("menu.menu_name.menu_" . 203);
                        $customTypes[] = $customMenus;
                        break;
                    case "document":
                        $temp = app($this->menuRepository)->getDetail(6); //文档菜单ID = 1
                        $customMenus["flag"] = $val;
                        $customMenus["name"] = mulit_trans_dynamic("menu.menu_name.menu_" . 6);
                        $customTypes[] = $customMenus;
                        break;
                    case "systemMenu":
                        $customMenus["flag"] = $val;
                        $customMenus["name"] = trans("menu.system_menu");
                        $customTypes[] = $customMenus;
                        break;
                    case "singleSignOn":
                        $customMenus["flag"] = $val;
                        $customMenus["name"] = trans("menu.sso_login");
                        $customTypes[] = $customMenus;
                        break;
                    case "favoritesMenu":
                        $customMenus["flag"] = $val;
                        $customMenus["name"] = trans("menu.menu_folder");
                        $customTypes[] = $customMenus;
                        break;
                    case "unifiedMessage":
                        $customMenus["flag"] = $val;
                        $customMenus["name"] = trans("menu.unifiedMessage");
                        $customTypes[] = $customMenus;
                        break;
                }
            }
        }

        $customDocumentSubMenuId = config('eoffice.customDocumentSubMenuId');
        $customDocuments = [];
        $docFirst = "";
        if ($customDocumentSubMenuId) {
            $docFirst = $customDocumentSubMenuId[0];
            $where = [
                "menu_id" => [$customDocumentSubMenuId, "in"],
            ];
            $customDocuments = app($this->menuRepository)->getMenuByWhere($where, ["menu_id", "menu_name"]);
            foreach ($customDocuments as $key => $value) {
                $customDocuments[$key]['menu_name'] = mulit_trans_dynamic("menu.menu_name.menu_" . $value['menu_id']);
            }
        }

        $customFlowSubMenuId = config('eoffice.customFlowSubMenuId');
        $customFlows = [];
        $flowFirst = "";
        if ($customFlowSubMenuId) {
            $flowFirst = $customFlowSubMenuId[0];
            $where = [
                "menu_id" => [$customFlowSubMenuId, "in"],
            ];
            $customFlows = app($this->menuRepository)->getMenuByWhere($where, ["menu_id", "menu_name"]);
            foreach ($customFlows as $key => $value) {
                $customFlows[$key]['menu_name'] = mulit_trans_dynamic("menu.menu_name.menu_" . $value['menu_id']);
            }
        }

        return [
            "menuTypes" => $customTypes,
            "menuFlows" => $customFlows,
            "menuDocs" => $customDocuments,
            "flowFirst" => $flowFirst,
            "docFirst" => $docFirst,
            // "current" => $currentData
        ];
    }

    //只返回用户有权限的菜单汇总  不含自定义菜单
    public function getMenusByUserId($user_id)
    {
        $roleData = app($this->userRoleRepository)->getUserRole(['user_id' => $user_id]);
        $roleObj = [];
        foreach ($roleData as $role) {
            $roleObj[] = $role['role_id'];
        }
        $excludeMenu = $this->getPermissionModules();
        //获取用户隐藏的菜单ID
        $whereMenu = [
            "is_show" => [0],
            "user_id" => [$user_id],
        ];
        $hiddenMenu = app($this->userMenuRepository)->getMenuByWhere($whereMenu);
        $hiddenMenuIds = [];
        foreach ($hiddenMenu as $hidden) {
            $hiddenMenuIds[] = $hidden["menu_id"];
        }
        $excludeMenu = array_merge($excludeMenu, $hiddenMenuIds);
        $menus = $this->getMenus($roleObj);
        return array_values(array_diff($menus, $excludeMenu));
    }

    public function judgeMenuPermission($menuId, $userId = '')
    {
        if (!$userId) {
            $userId = own('user_id');
        }
        if (!is_array($menuId)) {
            $menuId = explode(',', $menuId);
        }
        $menus = app($this->menuRepository)->getMenuByWhere(['menu_id' => [$menuId, 'in']]);
        if (empty($menus)) {
            return "false";
        }
        $permissionMenus = $this->getUserMenus($userId);
        if (isset($permissionMenus['menu'])) {
            if (!array_diff($menuId, $permissionMenus['menu'])) {
                return "true";
            } else {
                return "false";
            }
        } else {
            return "false";
        }
    }

    //外部增设菜单  执行API后使用
    public function globalSetMenu()
    {

//        $this->addSystemMenuFunc(11, "内部邮件", 1, 0, 1, 1);
        //        $this->addSystemMenuFunc(16, "新建邮件", 1, 11, 0, 11);
        //        $this->addSystemMenuFunc(24, "我的邮件", 1, 11, 0, 11);
        //        $this->addSystemMenuFunc(29, "查询邮件", 1, 11, 0, 11);
        //        $this->addSystemMenuFunc(31, "我的文件夹", 1, 11, 0, 11);
        //        //流程
        //        $this->addSystemMenuFunc(33, "委托记录", 1, 1, 0, 1);
        //        //系统管理
        //        $this->addSystemMenuFunc(34, "系统标题设置", 1, 95, 0, 95);
        //        $this->addSystemMenuFunc(109, "提示语设置", 1, 95, 0, 95);
        //        $this->addSystemMenuFunc(114, "Webhook管理", 1, 95, 0, 95);
        //        // 外部邮件
        //        $this->addSystemMenuFunc(14, "外部邮件", 1, 0, 1, 1);
        //        $this->addSystemMenuFunc(145, "新建邮件", 1, 14, 0, 14);
        //        $this->addSystemMenuFunc(142, "我的账号", 1, 14, 0, 14);
        //        $this->addSystemMenuFunc(143, "文件夹", 1, 14, 0, 14);
        //        $this->addSystemMenuFunc(144, "账号管理", 1, 14, 0, 14);
        //        // 公告
        //        $this->addSystemMenuFunc(320, "公告管理", 1, 0, 1, 237);
        //        $this->addSystemMenuFunc(234, "公告审核", 1, 320, 0, 321);
        //        //考勤管理
        //        //$this->addSystemMenuFunc(249, "校验管理", 1, 32, 0, 32); //20170214加入 不用新增
        //        //客户管理
        //        $this->addSystemMenuFunc(518, "客户报表", 1, 44, 0, 44);
        //        //任务
        //        $this->addSystemMenuFunc(534, "下属任务", 1, 530, 0, 530);
        //        $this->addSystemMenuFunc(535, "任务分析", 1, 530, 0, 530);
        //        //用车
        //        $this->addSystemMenuFunc(606, "车辆使用情况", 1, 600, 0, 600);
        //        //会议
        //        $this->addSystemMenuFunc(706, "会议室使用情况", 1, 700, 0, 700);
        //        //假期
        //        $this->addSystemMenuFunc(434, "假期管理", 1, 0, 1, 95);
        //        $this->addSystemMenuFunc(435, "假期类型管理", 1, 434, 0, 95);
        //        $this->addSystemMenuFunc(436, "用户假期管理", 1, 434, 0, 95);
        //报表
        //$this->addSystemMenuFunc(520, "报表管理", 1, 0, 1, 23);
        //$this->addSystemMenuFunc(521, "查看报表", 1, 520, 0, 23);
        //$this->addSystemMenuFunc(522, "新建报表", 1, 520, 0, 23);
        //$this->addSystemMenuFunc(523, "报表管理", 1, 520, 0, 23);
        //$this->addSystemMenuFunc(524, "数据源管理", 1, 520, 0, 23);
        //$this->addSystemMenuFunc(525, "标签管理", 1, 520, 0, 23);

        //大绝招

        //$this->addSystemMenuFunc(801, "标签设置", 1, 117, 0, 117);

//        $this->addSystemMenuFunc(913, "企业微信", 1, 0, 1, 107);
        //        $this->addSystemMenuFunc(916, "应用列表", 1, 913, 0, 107);
        //        $this->addSystemMenuFunc(915, "添加应用", 1, 913, 0, 107);
        //        $this->addSystemMenuFunc(914, "微信设置", 1, 913, 0, 107);
        $this->addSystemMenuFunc(69, "考勤导出", 1, 32, 0, 32);

        return $this->checkUserMenu();
    }

    public function setReportMenu()
    {
        //报表
        $this->addSystemMenuFunc(520, "报表管理", 1, 0, 1, 23);
        $this->addSystemMenuFunc(521, "查看报表", 1, 520, 0, 23);
        $this->addSystemMenuFunc(522, "新建报表", 1, 520, 0, 23);
        $this->addSystemMenuFunc(523, "报表管理", 1, 520, 0, 23);
        $this->addSystemMenuFunc(524, "数据源管理", 1, 520, 0, 23);
        //标签使用系统标签，不单独设置
        //$this->addSystemMenuFunc(525, "标签管理", 1, 520, 0, 23);
        //return $this->checkUserMenu();
    }

    public function checkUserMenu()
    {
        //获取有效用户 //获取所有的菜单
        $usersStr = app($this->userRepository)->getAllUserIdString([]);
        $users = explode(",", $usersStr);
        $param = ["menu_from" => [2, "!="]];
        $menusObj = app($this->menuRepository)->getMenuByWhere($param, "menu_id", false);

        foreach ($menusObj as $menu) {
            $menuId = $menu["menu_id"];
            $this->getUserMenuByMenuId($users, $menu["menu_id"]);
        }
    }

    public function getUserMenuByMenuId($users, $menuId, $fields = "user_id")
    {

        $userMenus = app($this->userMenuRepository)->getUserMenuByMenuId($menuId, $fields);
        $res = [];
        foreach ($userMenus as $user) {
            $res[] = $user->user_id;
        }
        $diffUser = array_diff($users, $res);
        $insert = [];
        foreach ($diffUser as $diff) {
            $insert[] = [
                "menu_id" => $menuId,
                "user_id" => $diff,
            ];
        }
        app($this->userMenuRepository)->insertMultipleData($insert);
    }

    //升级菜单
    public function updateMenuFunc()
    {
        // 删除菜单同步删除menu_role menu user_menu
        $deleteMenu = [12, 13, 207, 208, 210, 203, 212, 104, 405, 167, 168, 169, 170, 171, 172, 533, 520, 521, 522, 523, 524, 525, 69, 180, 181, 30, 906, 517, 20, 21, 22, 215, 245, 250, 251, 360, 36, 73, 74, 232, 275, 370];
        $where = ["menu_id" => [$deleteMenu, "in"]];
        app($this->menuRepository)->deleteByWhere($where);
        app($this->roleMenuRepository)->deleteByWhere($where);
        app($this->userMenuRepository)->deleteByWhere($where);
        //更改菜单信息
        app($this->menuRepository)->updateData(["menu_name" => "内部邮件"], ["menu_id" => 11]);
        app($this->menuRepository)->updateData(["menu_name" => "新闻管理"], ["menu_id" => 237]);
        //增加菜单信息 权限同模块权限
        //addSystemMenuFunc($menu_id, $menu_name, $is_system, $menu_parent, $has_children, $role_menu)
        //我的邮件 拆分菜单
        $this->addSystemMenuFunc(16, "新建邮件", 1, 11, 0, 11);
        $this->addSystemMenuFunc(24, "我的邮件", 1, 11, 0, 11);
        $this->addSystemMenuFunc(29, "查询邮件", 1, 11, 0, 11);
        $this->addSystemMenuFunc(31, "我的文件夹", 1, 11, 0, 11);
        //流程
        $this->addSystemMenuFunc(33, "委托记录", 1, 1, 0, 1);
        // 系统管理
        $this->addSystemMenuFunc(34, "系统标题设置", 1, 95, 0, 95);
        $this->addSystemMenuFunc(109, "提示语设置", 1, 95, 0, 95);
        $this->addSystemMenuFunc(114, "Webhook管理", 1, 95, 0, 95);
        // 外部邮件
        $this->addSystemMenuFunc(145, "新建邮件", 1, 14, 0, 14);
        $this->addSystemMenuFunc(142, "我的账号", 1, 14, 0, 14);
        $this->addSystemMenuFunc(143, "文件夹", 1, 14, 0, 14);
        $this->addSystemMenuFunc(144, "账号管理", 1, 14, 0, 14);
        // 公告
        $this->addSystemMenuFunc(320, "公告管理", 1, 0, 1, 237);
        $this->addSystemMenuFunc(234, "公告审核", 1, 320, 0, 321);
        //考勤管理
        //$this->addSystemMenuFunc(249, "校验管理", 1, 32, 0, 32); //20170214加入 不用新增
        //客户管理
        $this->addSystemMenuFunc(518, "客户报表", 1, 44, 0, 44);
        //任务
        $this->addSystemMenuFunc(534, "下属任务", 1, 530, 0, 530);
        $this->addSystemMenuFunc(535, "任务分析", 1, 530, 0, 530);
        //用车
        $this->addSystemMenuFunc(606, "车辆使用情况", 1, 600, 0, 600);
        //会议
        $this->addSystemMenuFunc(706, "会议室使用情况", 1, 700, 0, 700);
        //假期
        $this->addSystemMenuFunc(434, "假期管理", 1, 0, 1, 95);
        $this->addSystemMenuFunc(435, "假期类型管理", 1, 434, 0, 95);
        $this->addSystemMenuFunc(436, "用户假期管理", 1, 434, 0, 95);
        //更新菜单节点关系
        $this->updateMenuNodeRelation();
        //更改拼音menu_name_zm
        $this->updateMenuPY();
    }

    //更改菜单节点关系
    public function updateMenuNodeRelation()
    {
        //有下子集的菜单ID组
        $hasChildMenus = [1, 6, 11, 14, 26, 32, 37, 43, 44, 56, 82, 95, 107, 117, 118, 120, 126, 132, 160, 189, 216, 233, 237, 240, 241, 264, 320, 362, 366, 415, 434, 499, 530, 600, 700, 900, 904, 905];
        app($this->menuRepository)->updateData(["has_children" => 1], ["menu_id" => [$hasChildMenus, "in"]]);
        //更新节点集
        $zeroHas = [1, 6, 12, 11, 14, 126, 26, 32, 37, 43, 44, 82, 95, 107, 120, 132, 160, 189, 216, 233, 237, 264, 320, 362, 366, 415, 434, 499, 530, 600, 700, 900];
        app($this->menuRepository)->updateData(["menu_parent" => 0, "menu_path" => "0"], ["menu_id" => [$zeroHas, "in"]]);

        $flowHas = [2, 3, 4, 5, 33, 56, 252, 323, 375, 376, 377, 420];
        app($this->menuRepository)->updateData(["menu_parent" => 1, "menu_path" => "0,1"], ["menu_id" => [$flowHas, "in"]]);
        $flowSubHas = [57, 201, 202];
        app($this->menuRepository)->updateData(["menu_parent" => 56, "menu_path" => "0,1,56"], ["menu_id" => [$flowSubHas, "in"]]);

        $docHas = [7, 8, 59, 314, 318];
        app($this->menuRepository)->updateData(["menu_parent" => 6, "menu_path" => "0,6"], ["menu_id" => [$docHas, "in"]]);

        $emailHas = [16, 24, 29, 31];
        app($this->menuRepository)->updateData(["menu_parent" => 11, "menu_path" => "0,11"], ["menu_id" => [$emailHas, "in"]]);

        $outemailHas = [142, 143, 144, 145];
        app($this->menuRepository)->updateData(["menu_parent" => 14, "menu_path" => "0,14"], ["menu_id" => [$outemailHas, "in"]]);

        $scheduleHas = [27, 140, 141];
        app($this->menuRepository)->updateData(["menu_parent" => 26, "menu_path" => "0,26"], ["menu_id" => [$scheduleHas, "in"]]);

        $attendanceHas = [17, 66, 68, 246, 247, 248, 249, 281];
        app($this->menuRepository)->updateData(["menu_parent" => 32, "menu_path" => "0,32"], ["menu_id" => [$attendanceHas, "in"]]);

        $chargeHas = [38, 39, 40, 41, 42, 90];
        app($this->menuRepository)->updateData(["menu_parent" => 37, "menu_path" => "0,37"], ["menu_id" => [$chargeHas, "in"]]);

        $messageHas = [255, 256, 258];
        app($this->menuRepository)->updateData(["menu_parent" => 43, "menu_path" => "0,43"], ["menu_id" => [$messageHas, "in"]]);

        $customerHas = [501, 502, 503, 505, 506, 507, 508, 509, 510, 511, 512, 513, 514, 515, 516, 518];
        app($this->menuRepository)->updateData(["menu_parent" => 44, "menu_path" => "0,44"], ["menu_id" => [$customerHas, "in"]]);

        $examineHas = [83, 86, 87, 88, 89];
        app($this->menuRepository)->updateData(["menu_parent" => 82, "menu_path" => "0,82"], ["menu_id" => [$examineHas, "in"]]);

        $systemHas = [34, 99, 101, 102, 105, 106, 108, 109, 257, 113, 114, 117, 118, 240, 241, 361, 402, 403, 500, 801];
        app($this->menuRepository)->updateData(["menu_parent" => 95, "menu_path" => "0,95"], ["menu_id" => [$systemHas, "in"]]);
        $systemSubHas = [800];
        app($this->menuRepository)->updateData(["menu_parent" => 117, "menu_path" => "0,95,117"], ["menu_id" => [$systemSubHas, "in"]]);
        $systemOtherHas = [112, 259, 304];
        app($this->menuRepository)->updateData(["menu_parent" => 118, "menu_path" => "0,95,118"], ["menu_id" => [$systemOtherHas, "in"]]);
        $managerHas = [96, 97, 98];
        app($this->menuRepository)->updateData(["menu_parent" => 240, "menu_path" => "0,95,240"], ["menu_id" => [$managerHas, "in"]]);
        $tipHas = [103, 242];
        app($this->menuRepository)->updateData(["menu_parent" => 241, "menu_path" => "0,95,241"], ["menu_id" => [$tipHas, "in"]]);

        $enterpriseWeChatHas = [211, 213, 214];
        app($this->menuRepository)->updateData(["menu_parent" => 107, "menu_path" => "0,107"], ["menu_id" => [$enterpriseWeChatHas, "in"]]);

        $dingTalkHas = [121];
        app($this->menuRepository)->updateData(["menu_parent" => 120, "menu_path" => "0,120"], ["menu_id" => [$dingTalkHas, "in"]]);

        $payHas = [65, 19, 319];
        app($this->menuRepository)->updateData(["menu_parent" => 126, "menu_path" => "0,126"], ["menu_id" => [$payHas, "in"]]);

        $incomeExpensesHas = [133, 134, 135, 136, 137, 138, 139];
        app($this->menuRepository)->updateData(["menu_parent" => 132, "menu_path" => "0,132"], ["menu_id" => [$incomeExpensesHas, "in"]]);

        $projcetHas = [161, 162, 165];
        app($this->menuRepository)->updateData(["menu_parent" => 160, "menu_path" => "0,160"], ["menu_id" => [$projcetHas, "in"]]);

        $weiboHas = [28, 182, 183, 184, 190];
        app($this->menuRepository)->updateData(["menu_parent" => 189, "menu_path" => "0,189"], ["menu_id" => [$weiboHas, "in"]]);

        $contactsHas = [23, 25, 67];
        app($this->menuRepository)->updateData(["menu_parent" => 216, "menu_path" => "0,216"], ["menu_id" => [$contactsHas, "in"]]);

        $booksHas = [35, 78, 79, 80];
        app($this->menuRepository)->updateData(["menu_parent" => 233, "menu_path" => "0,233"], ["menu_id" => [$booksHas, "in"]]);

        $newsHas = [130, 235, 238, 321];
        app($this->menuRepository)->updateData(["menu_parent" => 237, "menu_path" => "0,237"], ["menu_id" => [$newsHas, "in"]]);

        $officeSuppliesHas = [265, 266, 267, 268, 269, 270];
        app($this->menuRepository)->updateData(["menu_parent" => 264, "menu_path" => "0,264"], ["menu_id" => [$officeSuppliesHas, "in"]]);

        $noticeHas = [131, 234, 236, 239];
        app($this->menuRepository)->updateData(["menu_parent" => 320, "menu_path" => "0,320"], ["menu_id" => [$noticeHas, "in"]]);

        $coopHas = [363, 364, 365];
        app($this->menuRepository)->updateData(["menu_parent" => 362, "menu_path" => "0,362"], ["menu_id" => [$coopHas, "in"]]);

        $albumHas = [367, 368, 369, 370];
        app($this->menuRepository)->updateData(["menu_parent" => 366, "menu_path" => "0,366"], ["menu_id" => [$albumHas, "in"]]);

        $recordHas = [416, 417, 418];
        app($this->menuRepository)->updateData(["menu_parent" => 415, "menu_path" => "0,415"], ["menu_id" => [$recordHas, "in"]]);

        $holidayHas = [435, 436];
        app($this->menuRepository)->updateData(["menu_parent" => 434, "menu_path" => "0,434"], ["menu_id" => [$holidayHas, "in"]]);

        $weixinHas = [430, 431, 432, 433];
        app($this->menuRepository)->updateData(["menu_parent" => 499, "menu_path" => "0,499"], ["menu_id" => [$weixinHas, "in"]]);

        $taskHas = [531, 532, 534, 535];
        app($this->menuRepository)->updateData(["menu_parent" => 530, "menu_path" => "0,530"], ["menu_id" => [$taskHas, "in"]]);
        $carHas = [601, 602, 603, 604, 605, 606];
        app($this->menuRepository)->updateData(["menu_parent" => 600, "menu_path" => "0,600"], ["menu_id" => [$carHas, "in"]]);
        $meetingHas = [701, 702, 703, 704, 705, 706];
        app($this->menuRepository)->updateData(["menu_parent" => 700, "menu_path" => "0,700"], ["menu_id" => [$meetingHas, "in"]]);

        $libraryHas = [901, 902, 903, 904, 905];
        app($this->menuRepository)->updateData(["menu_parent" => 900, "menu_path" => "0,900"], ["menu_id" => [$libraryHas, "in"]]);

        $libraryBorrowHas = [907, 908, 909, 910];
        app($this->menuRepository)->updateData(["menu_parent" => 904, "menu_path" => "0,900,904"], ["menu_id" => [$libraryBorrowHas, "in"]]);

        $libraryDocHas = [911, 912];
        app($this->menuRepository)->updateData(["menu_parent" => 905, "menu_path" => "0,900,905"], ["menu_id" => [$libraryDocHas, "in"]]);
    }

    public function updateMenuPY()
    {
        $menus = app($this->menuRepository)->getMenuPy();
        foreach ($menus as $menu) {
            $menuPy = Utils::convertPy($menu['menu_name']);
            $menu_name_zm = $menuPy[1];
            app($this->menuRepository)->updateData(["menu_name_zm" => $menu_name_zm], ["menu_id" => $menu["menu_id"]]);
        }

        return true;
    }
    //获取菜单下面的用户
    public function getMenuUser($menu_id)
    {
        $where = [
            "menu_id" => [$menu_id],
        ];
        $roles = app($this->roleMenuRepository)->getMenusGroupByMenuId($where);
        $role_id = array_column($roles, "role_id");
        $param['search'] = ["role_id" => [$role_id, "in"]];
        $res = app($this->userRepository)->getAllUserIdString($param);
        $role_user = explode(",", $res);

        $userMenus = app($this->userMenuRepository)->getMenuByWhere(["menu_id" => [$menu_id], "is_show" => [0]]);
        $hidden_users = array_column($userMenus, "user_id");
        $users = array_values(array_diff($role_user, $hidden_users));
        return $users;
    }

    public function setMenuSort($data)
    {
        $menuData = [];
        $menuData['class_name'] = isset($data['class_name']) ? $data['class_name'] : "";
        $menuData['sort'] = isset($data['sort']) ? $data['sort'] : 0;
        $menu = isset($data['menu']) ? $data['menu'] : [];
        $id = isset($data['id']) ? $data['id'] : 0;
        if (!empty($id)) {
            $insertId = $id;
            app($this->menuRepository)->updateData(['menu_class' => ''], ['menu_class' => $id]);
            app($this->menuClassRepository)->updateData($menuData, ['id' => $id]);
        } else {
            $insertId = app($this->menuClassRepository)->insertGetId($menuData);
        }
        //多语言
        $class_name_lang = isset($data['class_name_lang']) ? $data['class_name_lang'] : '';
        if (!empty($class_name_lang) && is_array($class_name_lang)) {
            foreach ($class_name_lang as $key => $value) {
                $langData = [
                    'table' => 'menu_class',
                    'column' => 'class_name',
                    'lang_key' => "menu_class_" . $insertId,
                    'lang_value' => $value,
                ];
                $local = $key; //可选
                app($this->langService)->addDynamicLang($langData, $local);
            }
        } else {
            $langData = [
                'table' => 'menu_class',
                'column' => 'class_name',
                'lang_key' => "menu_class_" . $insertId,
                'lang_value' => $menuData['class_name'],
            ];
            app($this->langService)->addDynamicLang($langData);
        }
        $menu_class['class_name'] = "menu_class_" . $insertId;
        app($this->menuClassRepository)->updateData($menu_class, ['id' => $insertId]);

        if (!empty($menu)) {
            $updatClass = ['menu_class' => $insertId];
            app($this->menuRepository)->updateData($updatClass, ['menu_id' => [$menu, 'in']]);
        }
        $this->clearCache();
        return true;
    }
    public function getMenuSort($param)
    {
        $lists = app($this->menuClassRepository)->menuSortLists($this->parseParams($param));
        if (!empty($lists)) {
            foreach ($lists as $key => $value) {
                $menu_name = [];
                $menu_id = array_column($value['menu'], 'menu_id');
                $lists[$key]['menu'] = $menu_id;
                if (!empty($menu_id)) {
                    foreach ($menu_id as $v) {
                        $menu_name[] = mulit_trans_dynamic("menu.menu_name.menu_" . $v);
                    }
                }
                $lists[$key]['class_name'] = mulit_trans_dynamic("menu_class.class_name.menu_class_" . $value['id']);
                $lists[$key]['menu_name'] = implode(",", $menu_name);
                $lists[$key]['class_name_lang'] = app($this->langService)->transEffectLangs("menu_class.class_name.menu_class_" . $value['id']);

            }
        }
        $count = app($this->menuClassRepository)->menuSortTotal();
        if (isset($param['type']) && $param['type'] == 'tree') {
            $unsort = ['class_name' => trans('menu.not_classified'), 'id' => 0];
            array_push($lists, $unsort);
            $count = $count + 1;
        }
        return ['total' => $count, 'list' => $lists];

    }
    public function getMenuSortTree($data)
    {
        $data['type'] = 'parent';
        $noPerimission = $this->getExcludeMenuId(own('user_id'));
        $results = app($this->menuRepository)->searchMenuTree($this->parseParams($data), $noPerimission);
        foreach ($results as $key => $value) {
            $results[$key]['menu_name'] = mulit_trans_dynamic("menu.menu_name.menu_" . $value['menu_id']);
        }
        $count = app($this->menuRepository)->searchMenuTreeTotal($this->parseParams($data), $noPerimission);
        return ['total' => $count, 'list' => $results];
    }
    public function deleteMenuSort($id)
    {
        if ($id) {
            app($this->menuClassRepository)->deleteByWhere(['id' => [$id]]);
            app($this->menuRepository)->updateData(['menu_class' => ''], ['menu_class' => $id]);
            $this->clearCache();
            return true;
        }
        return false;
    }
    /**
     * 获取用户菜单不包括隐藏菜单
     *
     * modify by baijin
     *
     * @param string $userId
     *
     * @return array
     */
    public function getMenusIncludeHide($userId)
    {

        $excludeMenu = $this->getPermissionModules();
        $user_id = own('user_id');
        $roleData = app($this->userRoleRepository)->getUserRole(['user_id' => $user_id]);
        $roleObj = [];
        foreach ($roleData as $role) {
            $roleObj[] = $role['role_id'];
        }
        //获取用户隐藏的菜单ID
        $menus = $this->getMenus($roleObj);
        $menuUp2000 = $this->getMenuUp2000($user_id);
        $menus = array_merge($menus, $menuUp2000);
        $menus = array_diff($menus, $excludeMenu);
        return [
            'menu' => $menus,
        ];
    }
    /**
     * 获取无授权模块菜单
     *
     * author 白锦
     *
     * @param type $userId
     */
    private function getNoPermissions($userId)
    {
        //获取无授权模块菜单
        $excludeMenuId = $this->getPermissionModules();
        if (Cache::has('no_permission_menus')) {
            $cacheExcludeMenuId = Cache::get('no_permission_menus');
            if ($excludeMenuId != $cacheExcludeMenuId) {
                $this->menuModifyFlag = true;
                Cache::forever('no_permission_menus', $excludeMenuId);
            }
        } else {
            $this->menuModifyFlag = true;
            Cache::forever('no_permission_menus', $excludeMenuId);
        }

        return $excludeMenuId;
    }
    /**
     * 导出菜单
     */
    public function exportMenu()
    {
        $newPath = createCustomDir("menu");
        $file = $newPath . "menu.xls";
        fopen($file, 'w');
        $objExcel = \PhpOffice\PhpSpreadsheet\IOFactory::load($file);
        $objActSheet = $objExcel->getActiveSheet();
        $menuList = app($this->menuRepository)->getAllMenus();
        $menuList = $this->arrayMenuLang($menuList);
        $objActSheet->setCellValue('A' . 1, '菜单id');
        $objActSheet->setCellValue('B' . 1, '菜单名字');
        foreach ($menuList as $key => $value) {
            $k = $key + 2;
            $objActSheet->setCellValue('A' . $k, $value['menu_id']);
            $objActSheet->setCellValue('B' . $k, $value['menu_name']);
        }
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="menu.xlsx"');
        header('Cache-Control: max-age=0');
        $objWriter = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($objExcel, 'Xlsx');
        $objWriter->save('php://output');
        $objWriter->save($file);
        exit;
    }

    /**
     * 增加一个公共的service函数，用来操作menu表数据，避免在外部引入menuRepository
     * @author 丁鹏
     * @param  [type] $param [menu_id(string);menu_info(array)]
     * @param  [type] $type [menu(默认，操作menu表);user_menu(操作user_menu表)]
     * @return [type]        [description]
     */
    public function updateMenuInfoCommon($param, $type = "menu")
    {
        $menuId = isset($param['menu_id']) ? $param['menu_id'] : '';
        if (!$menuId) {
            return;
        }
        $menuInfo = isset($param['menu_info']) ? $param['menu_info'] : [];
        if ($type == "menu") {
            $result = app($this->menuRepository)->updateData($menuInfo, ['menu_id' => $menuId]);
        } else if ($type == "user_menu") {
            $result = app($this->userMenuRepository)->updateData($menuInfo, ['menu_id' => $menuId]);
        }
        return $result;
    }

    /**
     * 集成中心，获取二级菜单信息，组成看板列表，用user_menu表的menu_order字段asc排序
     * @param  [type] $userId     [description]
     * @param  string $terminal   [description]
     * @param  string $parentMenu [description]
     * @return [type]             [description]
     */
    public function getIntegrationBoardMenus($userId, $terminal = 'PC', $parentMenu)
    {
        $terminal = strtolower($terminal);
        $roleIds = $this->getUserRoleIds($userId);

        $menus = $this->getMenus($roleIds);
        $excludeMenu = $this->getExcludeMenuId($userId); //获取该用户没有权限的菜单ID
        $menus = $this->mergeSystemManagerMenus($menus, $userId); //按角色获取用户菜单，并处理系统管理员用户权限菜单

        $menus = array_values(array_diff($menus, $excludeMenu));
        //获取模块的时候排除平台不展示的菜单
        $excludePlatformMenu = $this->getExcludePlatformMenus($terminal);
        $excludePlatformMenus = array_values(array_diff($menus, $excludePlatformMenu));

        // $filterConfig = config('eoffice.exceptIntegrationMenu');
        // if ($filterConfig) {
        //     $excludePlatformMenus =  array_values(array_diff($excludePlatformMenus, $filterConfig));
        // }

        $menuAll = app($this->menuRepository)->getCustomByUserId($excludePlatformMenus, $userId, $parentMenu);
        $menuAll = $this->arrayMenuLang($menuAll);
        $res = [];
        if (!empty($menuAll)) {
            foreach ($menuAll as $t) {
                $menuID = $t['menu_id'];
                if (!in_array($menuID, $excludePlatformMenus)) {
                    continue;
                }
                $res[] = $t;
            }
        }
        return $res;
    }

    /**
     * 20200602-丁鹏-增加此函数，包裹menuRepository的getDetail，避免外部获取菜单详情的时候，引用menuRepository
     * 现在外部调userMenuService是用：$this->getService 处理的
     * @param  [type] $menuId [description]
     * @return [type]         [description]
     */
    public function getMenuDetail($menuId) {
        return app($this->menuRepository)->getDetail($menuId);
    }

    /**
     * 菜单迁移文件夹
     * @param  [type] $parent [description]
     * @param  [type] $children [description]
     * @return [type]         [description]
     */
    public function menuMigration($parent,$children)
    {
        $parentMenu = app($this->menuRepository)->getDetail($parent);
        $path = $parentMenu->menu_path;
        app($this->menuRepository)->updateData(["has_children" => 1, "menu_param" => "", "menu_type" => "favoritesMenu", "state_url" => ""], ['menu_id' => $parent]);

        if(!empty($children) && is_array($children)){
            foreach ($children as  $child) {
                $menuPath = $path.",".$parent;
                app($this->menuRepository)->updateData(["has_children" => 0,'menu_path' =>$menuPath,'menu_parent'=>$parent], ['menu_id' => $child]);
            }
        }
        $parentRoles = $this->getCurrentRoles($parent);
        $where = [
            "menu_parent" => [$parent],
        ];
        $menuTemp = app($this->menuRepository)->getMenuByWhere($where);
        $roles = [];
        foreach ($menuTemp as $k => $v) {
            $oldRoles = $this->getCurrentRoles($v['menu_id']);
            $roles = array_merge($roles,$oldRoles);
            if($v['is_follow'] == 1){
                $this->insertDataMenu($parentRoles, $v['menu_id']);
                app($this->menuRepository)->updateData(["is_follow" => 0], ['menu_id' => $v['menu_id']]);
            }
        }
        $roles = array_unique($roles);
        $this->deleteDataMenu($parentRoles, $parent);
        return $this->insertDataMenu($roles, $parent);


    }
    /**
     * 系统管理管理主页api
     * @param  [type] $userId     [description]
     * @param  string $terminal   [description]
     * @param  string $parentMenu [description]
     * @return [type]             [description]
     */
    public function getSystemBoardMenus($userId, $terminal = 'PC', $parentMenu)
    {
        $terminal = strtolower($terminal);
        $roleIds = $this->getUserRoleIds($userId);

        $menus = $this->getMenus($roleIds);
        $menus = config('eoffice.systemManageMenu');

        //获取模块的时候排除平台不展示的菜单
        $excludePlatformMenu = $this->getExcludePlatformMenus($terminal);
        $excludePlatformMenus = array_unique(array_merge($menus, $excludePlatformMenu));

        $menuAll = app($this->menuRepository)->getCustomByUserId($excludePlatformMenus, $userId, $parentMenu);
        $menuAll = $this->arrayMenuLang($menuAll);
        $res = [];
        if (!empty($menuAll)) {
            foreach ($menuAll as $t) {
                $res[] = $t;
            }
        }
        return $res;
    }
}
