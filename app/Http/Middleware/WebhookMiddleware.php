<?php

namespace App\Http\Middleware;

use Cache;
use Queue;
use Closure;
use Request;
use App\Jobs\webhookSendJob;
use App\EofficeApp\System\Webhook\Services\WebhookService;

class WebhookMiddleware
{
    public function __construct(
        WebhookService $webhookService
    ) {

        $this->webhookService = $webhookService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        return $next($request);
    }

    /**
     * 终结中间件
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Http\Response  $response
     *
     * @return mixed
     */
    public function terminate($request, $response)
    {
        if ($request->method() == 'GET') {
            return ;
        }

        $responseData  = $response->original;

        if (empty($responseData['status']) || $responseData['status'] != 1) {
            return ;
        }

        $routes = $request->route();
        $route = $routes[1]['uses'];
        $controller = $this->getRouteWithoutNameSpace($route);

        $menus = config('webhook.webhook');
        $webhookMenus = [];
        foreach ($menus as $val) {
            foreach ($val as $v) {
                $webhookMenus = array_merge($webhookMenus, array_values($v));
            }
        };

        if (!in_array($controller, $webhookMenus)) {
            return ;
        }

        $webhooks = $this->webhookService->getWebhook('', $controller);

        if (empty($webhooks) || empty($webhooks[0]['webhook_url'])) {
            return ;
        }

        $webhook = $webhooks[0];
        $webhookUrl = $webhook['webhook_url'] ?? '';
        $webhookUrl = trim(trim($webhookUrl),'/');
        // 处理url以支持相对路径
        if ($webhookUrl && strpos($webhookUrl, 'http') === false) {
            $http = $_SERVER['REQUEST_SCHEME']."://".$_SERVER['SERVER_NAME'];
            if ($_SERVER["SERVER_PORT"] != '80') {
                $http .= ':'.$_SERVER["SERVER_PORT"];
            }
            $webhookUrl = $http.'/'.$webhookUrl;
        }

        $userInfoObj = $this->getAuthInfo($request);
        $userInfo = [
            'user_id'       => $userInfoObj->user_id,
            'user_name'     => $userInfoObj->user_name,
            'user_accounts' => $userInfoObj->user_accounts,
            'dept_id'                => $userInfoObj->userHasOneSystemInfo->dept_id ?? ($userInfoObj->dept_id ?? null),
            'dept_name'              => $userInfoObj->userHasOneSystemInfo->userSystemInfoBelongsToDepartment->dept_name ?? ($userInfoObj->dept_name ?? null),
            'role_id'                => isset($userInfoObj->roles) ? array_column($userInfoObj->roles,'role_id') : $this->parseRoles($userInfoObj['userHasManyRole'], 'role_id'),
            'role_name'                => isset($userInfoObj->roles) ? array_column($userInfoObj->roles,'role_name') : $this->parseRoles($userInfoObj['userHasManyRole'], 'role_name'),
        ];

        if(isset($responseData['data']) && !empty($responseData['data'])) {
            $responseSendData = json_decode(json_encode($responseData['data']), true);
        } else {
            $responseSendData = true;
        }
        $data = [
            'url_params'    => isset($routes[2]) ? $routes[2] : '',
            'request'       => $request->all(),
            'response'      => $responseSendData,
            'user_info'     => $userInfo
        ];

        $log = [
            'log_creator'        => $userInfo['user_id'],
            'log_type'           => 'add',
            'log_ip'             => getClientIp(),
            'log_relation_table' => $webhookUrl,
            'log_relation_id'    => '',
            'log_content'        => json_encode($data),
        ];

        Queue::push(new webhookSendJob(['url' => $webhookUrl, 'data' => $data, 'log' => $log]));
        return ;
    }

    public function getAuthInfo($request)
    {
        $token = $request->input('api_token');

        if (empty($token)) {
            $token = $request->bearerToken();
        }

        if (empty($token)) {
            $token = $request->getPassword();
        }

        if($token) {
            return Cache::get($token);
        }

        return false;
    }

    // 格式化返回角色ID和名称
    public function parseRoles($roles, $type)
    {
        if(empty($roles)) {
            return [];
        }
        static $parseRole = array();

        if(!empty($parseRole)){
            return $parseRole[$type];
        }
        $roleId = $roleName = $roleArray = [];

        foreach ($roles as $role) {
            $roleId[]   = $role['role_id'];
            $roleName[] = $roleArray[$role['role_id']] = $role['hasOneRole']['role_name'];
        }

        $parseRole = ['role_id' => $roleId,'role_name' => $roleName,'role_array' => $roleArray];

        return $parseRole[$type];
    }

    /**
     * @param $route
     * @return string
     */
    private function getRouteWithoutNameSpace($route)
    {
        $parse = explode('@', $route);
        $controller = explode('\\', $parse[0]);
        return array_pop($controller) . '@' . $parse[1];
    }

}
