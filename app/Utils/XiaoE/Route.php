<?php

namespace App\Utils\XiaoE;

use Illuminate\Support\Facades\URL;

/**
 * 生成前端访问url
 * Class Route
 */
class  Route
{
    private static $baseUrl;

    /**
     * 后端定义与前端路由的映射，方便后期修改前端路由统一维护
     * @var array
     */
    private static $route = [
        '/flow/handle' => '/flow/handle/{flow_id};run_id={run_id};flow_run_process_id={flow_run_process_id}',//流程办理页面
        '/flow/view' => '/flow/view/{flow_id}/{run_id}',//流程查看页面
        '/flow/create' => '/flow/handle/{flow_id}',//新建流程页面
        '/meet/detail' => '/meeting/detail/{meeting_apply_id};type=mine',//会议详情页面
        '/calendar/detail' => '/calendar/detail/{calendar_id};type=calendar',//日程详情页面
        '/document/detail' => '/document/detail/{document_id}',//文档详情
        '/chat' => '/home/message/chat/{user_id};content={content}?from=xiaoe',//聊天
        '/salary/detail' => '/salary/detail/{report_id}',
        '/customer/detail' => '/customer/{customer_id}',
        '/customer/add' => '/customer/add',
        '/project/detail' => '/project/mine/tasks;manager_id={manager_id}',
        '/project/add' => '/project/add',
        '/news' => '/news/{news_id}',
        '/contract' => '/contract/mine/{id}',
        '/meeting/add' => '/meeting/add',
    ];

    /**
     * 获取前端路由之前的url
     */
    public static function getClientRouteBaseUrl()
    {
        if (!self::$baseUrl) {
            $url = explode('server', URL::current())[0] . 'client/mobile';
            self::$baseUrl = envOverload('XIAOE_CLIENT_BASE_URL', $url);
        }
        return self::$baseUrl;
    }

    public static function navigate($state, $params = false)
    {
        if (substr($state, 0, 1) != '/') {
            $state = '/' . $state;
        }
        if (!isset(self::$route[$state])) {
            return '';
        }
        $base = self::getClientRouteBaseUrl();
        $state = self::$route[$state];
        if (!$params) {
            return $base . $state;
        }
        foreach ($params as $key => $value) {
            $state = str_replace('{' . $key . '}', "$value", $state);
        }
        return $base . $state;
    }
}