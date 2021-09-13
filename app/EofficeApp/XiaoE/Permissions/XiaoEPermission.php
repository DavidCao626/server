<?php

namespace App\EofficeApp\XiaoE\Permissions;


class XiaoEPermission
{
    public $config = [
        //流程
        'App\EofficeApp\Flow\Services\FlowService' => [
            //对应的控制器,不是一对应需要指定控制器
            'controller' => '',
            //对应的控制器中的方法，如果不是一致需要在这里配置别名
            'methods' => [
                'getMyRequestList' => 'myRequestList',//我发起的流程
                'newPageSaveFlowInfo' => 'newPageSaveFlow',//新建流程
                'getTeedToDoList' => 'teedToDoList',//我的待办
                'getAlreadyDoList' => 'alreadyDoList',//我的已办
            ],
            //自定义验证规则
            'validate' => [
                'newPageSaveFlowInfo' => 'validateCreateFlow'
            ],
            //对应的控制器中的请求参数以及路由参数，用户数据权限验证传参
            'params' => [

            ]
        ],
        //微博
        'App\EofficeApp\Diary\Services\DiaryService' => [
            'controller' => '',
            'methods' => [
                'getDiaryList' => 'getIndexDiarys',//我的微博
            ],
        ],
        //小e模块权限控制
        'App\EofficeApp\XiaoE\Services\SystemService' => [
            'controller' => 'App\EofficeApp\XiaoE\Controllers\XiaoEController',
        ],
        //客户
        'App\EofficeApp\Customer\Services\CustomerService' => [
            'methods' => [
                'lists' => 'customerLists'
            ]
        ],
        //考勤
        'App\EofficeApp\Attendance\Services\AttendanceService' => [
            'methods' => [
                'getUserSchedulingDate' => 'getMySchedulingDate'
            ]
        ]
    ];

    /**
     * 创建流程前需要验证下是否有权限
     * @param $request
     * @param $arguments
     */
    public function validateCreateFlow($request, $arguments, $own)
    {
        $flowId = $arguments[0]['flow_id'] ?? false;
        if (!$flowId) {
            return false;
        }
        $permissionParams = ["own" => $own, "flow_id" => $flowId];
        if (!app('App\EofficeApp\Flow\Services\FlowPermissionService')->verifyFlowNewPermission($permissionParams)) {
            return false;
        }
        return true;
    }
}