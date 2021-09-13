<?php

namespace App\EofficeApp\System\Board\Services;

use App\EofficeApp\Base\BaseService;
use DB;

class BoardService extends BaseService
{

    public function __construct()
    {
        parent::__construct();
        $this->userMenuService = 'App\EofficeApp\Menu\Services\UserMenuService';

    }

    public function getBoard($params)
    {
        $currentUserId = own('user_id');

        // 20190905-从菜单取看板信息
        $userMenuObject = app('App\EofficeApp\Menu\Services\UserMenuService');
        $menus = app($this->userMenuService)->getSystemBoardMenus($currentUserId, 'PC', '95');
        $menus = array_column($menus, NULL, 'menu_id');
        $result = [];
        $modelConfig = $this->getSystemMenuConfig();
        $result = [];
        // 处理方式-20200810-按照数据库查到的$menus的排序来；$modelConfig提供看板类别
        if(!empty($menus)) {
            foreach ($menus as $menuId => $menusInfo) {
                $configMenuInfo = $modelConfig[$menuId] ?? [];
                // 排除id-100
                if(!empty($menusInfo) && $menuId != '100') {
                    $menusInfo['board_classify'] = $configMenuInfo['classify'] ?? '';
                    array_push($result, $menusInfo);
                }
            }
        }
        if (!empty($params['search'])) {
            $search = json_decode($params['search'], true);
            $classify = $search['classify'];
            if (!empty($classify) && $classify != 'all') {
                $result = array_filter($result, function ($value) use ($classify) {
                    return $value['board_classify'] == $classify ? true : false;
                });
                $result = array_values($result); //重建索引
            }
        }

        return $result;
    }

    private function getSystemMenuConfig()
    {
        return [
            // 通用设置
            '117' => [
                'order' => '1',
                'classify' => 'data',
            ],
            // 内容模板
            '259' => [
                'order' => '2',
                'classify' => 'data',
            ],
            // 下拉框配置
            '800' => [
                'order' => '3',
                'classify' => 'data',
            ],
            // 菜单配置
            '113' => [
                'order' => '4',
                'classify' => 'data',
            ],
            // 提醒设置
            '500' => [
                'order' => '5',
                'classify' => 'data',
            ],
            // 导航栏设置
            '110' => [
                'order' => '6',
                'classify' => 'view',
            ],
            // 登录页设置
            '111' => [
                'order' => '7',
                'classify' => 'view',
            ],
            // 门户管理
            '101' => [
                'order' => '8',
                'classify' => 'view',
            ],
            // 日志中心
            '103' => [
                'order' => '9',
                'classify' => 'security',
            ],
            // 数据库修复
            '242' => [
                'order' => '10',
                'classify' => 'security',
            ]
        ];
    }

    public function checkPermission()
    {
        // 判断是否有用户管理,角色管理, 部门管理菜单权限
        $deptPermission  = app('App\EofficeApp\Menu\Services\UserMenuService')->judgeMenuPermission(98);
        $rolePermission  = app('App\EofficeApp\Menu\Services\UserMenuService')->judgeMenuPermission(99);
        $userPermission  = app('App\EofficeApp\Menu\Services\UserMenuService')->judgeMenuPermission(97);
        if ($deptPermission == 'true' && $rolePermission == 'true' && $userPermission == 'true') {
            return 'true';
        }
        return 'false';
    }
    public function parseData($message)
    {
        $message = $message['message'] ?? '';
        if (!$message) {
            return '';
        }
        $start = strrpos($message, '"_j_data_":');
        $messagestring = substr($message, $start, 57);
        $message = str_replace($messagestring, "", $message);
        return $message;
    }
}
