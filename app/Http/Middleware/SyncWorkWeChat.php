<?php

namespace App\Http\Middleware;

use App\Jobs\syncWorkWeChatJob;
use Closure;
use Queue;
use Request;

class SyncWorkWeChat
{
    private $workWeChatService;

    public function __construct()
    {
        $this->workWeChatService = 'App\EofficeApp\WorkWechat\Services\WorkWechatService';
    }

    public function handle($request, Closure $next)
    {

        $response = $next($request);
        $this->checkPerform($response);
        //$this->openApiService->openLog($response);
        //echo 'controller-end';
        // 执行动作
        return $response;
    }

    public function checkPerform($response)
    {
        $routes = Request::route();
        // $param = Request::all();
        // $param = $param ?? [];
        $deptId = $routes[2]['dept_id'] ?? '';
        $userId = $routes[2]['user_id'] ?? '';
        $route = $routes[1]['uses'];
        $controller = $this->getRouteWithoutNameSpace($route);
        $config = [
            'DepartmentController@addDepartment',
            'DepartmentController@editDepartment',
            'DepartmentController@deleteDepartment',
            'UserController@userSystemCreate',
            'UserController@userSystemEdit',
            'UserController@userSystemDelete',
        ];
        if (in_array($controller, $config)) {
            if (isset($response->original['status']) && $response->original['status'] == 1) {
                $allParam = [
                    'controller' => $controller,
                    'response' => $response,
                    'dept_id' => $deptId,
                    'user_id' => $userId,
                    //'param' => $param
                ];
                Queue::push(new syncWorkWeChatJob(1, 1, 1, 1, 'oa_to_work_wechat', $allParam, 1));
                //app($this->workWeChatService)->autoSyncToWorkWeChat($controller,$response,$allParam);
            }

        }

    }

    private function getRouteWithoutNameSpace($route)
    {
        $parse = explode('@', $route);
        $controller = explode('\\', $parse[0]);
        return array_pop($controller) . '@' . $parse[1];
    }
}
