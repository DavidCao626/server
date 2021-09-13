<?php

namespace App\EofficeApp\OpenApi\Services;

use App\EofficeApp\Base\BaseService;
use App\Jobs\OpenApiJob;
use App\Jobs\SendMessageJob;
use Queue;
use Illuminate\Support\Facades\Redis;
use Request;
use Cache;

class OpenApiService extends BaseService
{
    private $openApplicationRepository;
    private $openApplicationLogRepository;
    private $openCaseRepository;
    private $userRepository;
    private $userInfoRepository;
    private $authService;
    private $userService;
    private $invoiceService;
    private static $refreshTokenCheck = false;

    public function __construct()
    {
        $this->openApplicationRepository = 'App\EofficeApp\OpenApi\Repositories\OpenApplicationRepository';
        $this->openApplicationLogRepository = 'App\EofficeApp\OpenApi\Repositories\OpenApplicationLogRepository';
        $this->openCaseRepository = 'App\EofficeApp\OpenApi\Repositories\OpenCaseRepository';
        $this->userRepository = 'App\EofficeApp\User\Repositories\UserRepository';
        $this->userInfoRepository = 'App\EofficeApp\User\Repositories\UserInfoRepository';
        $this->authService = 'App\EofficeApp\Auth\Services\AuthService';
        $this->userService = 'App\EofficeApp\User\Services\UserService';
        $this->invoiceService = 'App\EofficeApp\Invoice\Services\InvoiceService';
    }

    /**
     * 获取应用Secret
     * @return string
     * @author [dosy]
     */
    public function getApplicationSecret()
    {
        $len = 32;
        $secret = getRandomStr($len);
        return $secret;
    }

    /**
     *  刷新秘钥，记得刷新后要保存
     * @param $id
     * @param $userId
     * @return bool|string
     * @author [dosy]
     */
    public function refreshApplicationSecret($id, $userId)
    {
        $len = 32;
        $secret = getRandomStr($len);
        $updateParam = [
            'secret' => $secret
        ];
        $result = app($this->openApplicationRepository)->updateData($updateParam, ['id' => $id]);

        if ($result) {
            $agentId = (int)$id + 100000;
            $this->cancelToken($agentId);
            return $secret;
        } else {
            return false;
        }
    }

    /**
     * 注销一个应用下的所有token和refresh_token
     * @param $agentId
     * @author [dosy]
     */
    public function cancelToken($agentId)
    {
        /*        Redis::hSet('open_token_' . $basicUserInfo->user_id, $registerInfo['token'], $param['agent_id']);
                Redis::hSet('open_refresh_token_' . $basicUserInfo->user_id, $registerInfo['refresh_token'], $param['agent_id']);
                Redis::hSet('open_application_token_' . $application['agent_id'], $registerInfo['token'], $basicUserInfo->user_id);
                Redis::hSet('open_application_refresh_token_' . $application['agent_id'], $registerInfo['refresh_token'], $basicUserInfo->user_id);
                Cache::forget($token);
                Cache::forget('refresh_token_' . $token);*/
        $agentIdAllTokenAllUser = Redis::hGetAll('open_application_token_' . $agentId);
        $agentIdAllRefreshTokenAllUser = Redis::hGetAll('open_application_refresh_token_' . $agentId);
        foreach ($agentIdAllTokenAllUser as $token => $userId) {
            Redis::hDel('open_token_' . $userId, $token);
            Cache::forget($token);
            ecache('Auth:RefreshToken')->clear($token);
        }
        foreach ($agentIdAllRefreshTokenAllUser as $refreshToken => $userId) {
            Redis::hDel('open_refresh_token_' . $userId, $refreshToken);
        }
        Redis::del('open_application_token_' . $agentId);
        Redis::del('open_application_refresh_token_' . $agentId);
    }

    /**
     * 设置应用
     * @param $param
     * @return mixed
     * @author [dosy]
     */
    public function setApplication($param)
    {
        //dd($param);
        $updateParam = [
            'is_use' => $param['is_use'],
            'application_name' => $param['application_name'],
            'user_basis_field' => $param['user_basis_field']
        ];
        $result = app($this->openApplicationRepository)->updateData($updateParam, ['id' => $param['id']]);
        if ($result) {
            if ($param['is_use'] == 0) {
                $this->cancelToken($param['agent_id']);
            }
        }
        return $result;
    }

    /**
     * 获取应用列表
     * @param $params
     * @param $userId
     * @return array
     * @author [dosy]
     */
    public function getApplicationList($params, $userId)
    {
        $data = $this->response(app($this->openApplicationRepository), 'getOpenApplicationTotal', 'getOpenApplicationList', $this->parseParams($params));
        return $data;
    }

    /**
     * 注册应用
     * @param $params
     * @param $userId
     * @return array
     * @author [dosy]
     */
    public function registerApplication($params, $userId)
    {
        //去空格
        foreach ($params as $key => $value) {
            if (is_string($value)) {
                $params[$key] = trim($value);
            } else {
                continue;
            }
        }
        if (!isset($params['application_name']) || empty($params['application_name'])) {
            return ['code' => ['application_name_error', 'openApi']];
        }
        if (!isset($params['user_basis_field']) || empty($params['user_basis_field'])) {
            return ['code' => ['user_identification_field_error', 'openApi']];
        }
        $secret = $this->getApplicationSecret();
        $param = [
            'secret' => $secret,
            'is_use' => 1,
            'application_name' => $params['application_name'],
            'user_basis_field' => $params['user_basis_field']

        ];
        $data = array_intersect_key($param, array_flip(app($this->openApplicationRepository)->getTableColumns()));
        $result = app($this->openApplicationRepository)->insertData($data);
        if ($result) {
            $id = $result['id'];
            $agentId = 100000 + $id;
            $updateParam = [
                'agent_id' => $agentId
            ];
            app($this->openApplicationRepository)->updateData($updateParam, ['id' => $id]);
            //Redis::hSet('open_application',$agentId,1);
        }
        return $result;
    }

    /**
     * 删除应用
     * @param $id
     * @param $userId
     * @return mixed
     * @author [dosy]
     */
    public function deleteApplication($id, $userId)
    {
        //删除应用
        $result = app($this->openApplicationRepository)->deleteById($id);
        if ($result) {
            $agentId = 100000 + (int)$id;
            $this->cancelToken($agentId);
        }
        return $result;
    }

    /**
     * 获取应用详情
     * @param $id
     * @param $userId
     * @return mixed
     * @author [dosy]
     */
    public function getApplicationDetail($id, $userId)
    {
        $where = ['id' => $id];
        $result = app($this->openApplicationRepository)->getData($where);
        if (app($this->invoiceService)->checkInvoiceByOpenApiApplicationId($id)){
            $result->invoice_cloud = 1;
        } else {
            $result->invoice_cloud = 0;
        }
        return $result;
    }

    /**
     * 获取单应用日志列表
     * @param $params
     * @return array
     * @author [dosy]
     */
    public function getApplicationLogList($params)
    {
        $data = $this->response(app($this->openApplicationLogRepository), 'getOpenApplicationLogTotal', 'getOpenApplicationLogList', $this->parseParams($params));
        return $data;
    }


    /**
     * 添加案例
     * @param $caseKey 案例识别键
     * @param $caseName 案例名称
     * @param $caseUrl  案例文件地址（建议放在/server/public/api-doc-case/下）
     * @param string $caseIcon 案例图标
     * @author [dosy]
     */
    public function addOpenCase($caseKey, $caseName, $caseUrl, $caseIcon = '')
    {
        if (is_string($caseKey) && is_string($caseName) && is_string($caseUrl) && is_string($caseIcon)) {
            $where = [
                'case_key' => $caseKey
            ];
            $check = app($this->openCaseRepository)->getData($where);
            if (!$check) {
                $data = [
                    'case_key' => $caseKey,
                    'case_name' => $caseName,
                    'case_url' => $caseUrl,
                    'case_icon' => $caseIcon,
                ];
                app($this->openCaseRepository)->insertData($data);
            }
        }
    }

    /**
     * 获取案例列表
     * @param $params
     * @return array
     * @author [dosy]
     */
    public function getOpenCase($params)
    {
        $data = $this->response(app($this->openCaseRepository), 'getOpenCaseTotal', 'getOpenCaseList', $this->parseParams($params));
        return $data;
    }

    /**
     * 获取单个案例
     * @param $id
     * @return mixed
     * @author [dosy]
     */
    public function getOneCase($id)
    {
        //dd(is_file('api-doc-case/index.html'));
        $where = [
            'id' => $id
        ];
        $data = app($this->openCaseRepository)->getData($where);
        if (!is_file($data['case_url'])) {
            return ['code' => ["empty_file", 'openApi']];
        }
        $data['srcUrl'] = '/server/public/' . $data['case_url'];
        return $data;
    }


    /**
     * 生成open token
     * @param $param
     * @return array
     * @author [dosy]
     */
    public function openApiToken($param)
    {
        // 时间戳 精确到秒
        // $time = time();
        if (!isset($param['user']) || empty($param['user']) || !isset($param['agent_id']) || empty($param['agent_id']) || !isset($param['secret']) || empty($param['secret'])) {
            return ['code' => ['0x500001', 'openApi']]; //参数缺失不存在
        }

        $where = [
            'agent_id' => $param['agent_id'],
            'secret' => $param['secret'],
            'is_use' => 1,
        ];
        $application = app($this->openApplicationRepository)->getData($where);

        if (!$application) {
            return ['code' => ['0x500002', 'openApi']]; //应用不存在
        }
        $userBasisField = $application['user_basis_field'];
        if ($userBasisField == "phone_number") {
            $userWhere = [
                $userBasisField => $param['user']
            ];
            $basicUserInfo = app($this->userInfoRepository)->getOneFieldInfo($userWhere);
            //$basicUserInfo = DB::table('user')->where($userBasisField, $param['user'])->first();
        } else {
            $userWhere = [
                $userBasisField => $param['user']
            ];
            $basicUserInfo = app($this->userRepository)->getOneFieldInfo($userWhere);
            //$basicUserInfo = DB::table('user_info')->where($userBasisField, $param['user'])->first();
        }
        //检查平台授权
        $res = app('App\EofficeApp\Empower\Services\EmpowerService')->checkPcEmpower();
        if (isset($res['code'])) {
            return $res; //软件没有PC授权
        }

        //访问控制
        $user = app($this->userService)->getLoginUserInfo($basicUserInfo->user_id);
        if (!app($this->authService)->ipControl($user)) {
           // app($this->authService)->addLoginLog($user->user_id, 'ilip', trans('auth.illegal_ip'));
            return  ['code' => ['0x000009', 'common']];
        }

        // 注册用户token
        $registerInfo = app('App\EofficeApp\Auth\Services\AuthService')->casRegisterInfo($basicUserInfo->user_id);

        if (!isset($registerInfo['token'])) {
            return ['code' => ['0x500003', 'openApi']]; //获取token失败，请重试
        }
        Redis::hSet('open_token_' . $basicUserInfo->user_id, $registerInfo['token'], $param['agent_id']);
        Redis::hSet('open_refresh_token_' . $basicUserInfo->user_id, $registerInfo['refresh_token'], $param['agent_id']);
        Redis::hSet('open_application_token_' . $application['agent_id'], $registerInfo['token'], $basicUserInfo->user_id);
        Redis::hSet('open_application_refresh_token_' . $application['agent_id'], $registerInfo['refresh_token'], $basicUserInfo->user_id);
        $tokenTtl = config('auth.web_token_ttl');
        $refreshTokenTtl = config('auth.web_refresh_token_ttl');
        $expiredIn = $tokenTtl*60;
        $RefreshTokenExpiredIn = $refreshTokenTtl*60;
        return ['token' => $registerInfo['token'], 'user' => $param['user'], 'refresh_token' => $registerInfo['refresh_token'], 'agent_id' => $param['agent_id'], 'expired_in'=> $expiredIn, 'refresh_token_expired_in'=> $RefreshTokenExpiredIn];
    }

    /**
     * 通过refresh_token刷新token
     * @param $userId
     * @param $token
     * @param $newToken
     * @param $refreshToken
     * @author [dosy]
     */
    public function openRefreshToken($userId, $token, $newToken, $refreshToken)
    {
        if (self::$refreshTokenCheck){
            $agentId = Redis::hGet('open_refresh_token_' . $userId, $refreshToken);
            if ($agentId) {
                Redis::hSet('open_token_' . $userId, $newToken, $agentId);
                Redis::hSet('open_application_token_' . $agentId, $newToken, $userId);
                Redis::hDel('open_token_' . $userId, $token);
                Redis::hDel('open_application_token_' . $agentId, $token);
            }else{
                self::$refreshTokenCheck = false;
            }
        }
    }

    /**
     * refresh_token 获取 token
     * @param $param
     * @return mixed
     * @author [dosy]
     */
    public function openApiRefreshToken($param)
    {
        if (isset($param['refresh_token'])&&!empty($param['refresh_token'])&&isset($param['token'])&&!empty($param['token'])){
            self::$refreshTokenCheck = true;
            $registerInfo = app('App\EofficeApp\Auth\Services\AuthService')->refresh($param['refresh_token'], $param['token'], 0);
            if (self::$refreshTokenCheck){
                self::$refreshTokenCheck = false;
                if (isset($registerInfo['code'])){
                    return $registerInfo;
                }else{
                    $tokenTtl = config('auth.web_token_ttl');
                    $data = [
                        'token'=> $registerInfo,
                        "expired_in"=> $tokenTtl*60
                    ];
                    return $data;
                }
            }else{
                self::$refreshTokenCheck = false;
                return ['code' => ['0x500003', 'openApi']];
            }
        }else{
            return ['code' => ['0x500003', 'openApi']];
        }
    }

    /**
     * 属于openApi的token检查应用情况
     * @param $userId
     * @param $token
     * @return bool
     * @author [dosy]
     */
    public function openCheck($userId, $token)
    {
        return true;
    }

    /**
     * 接入成功，token验证通过的情况下返回时，返回获取一次外部请求的请求体及返回体、token验证不通过，不记录日志
     * @param $response
     * @author [dosy]
     */
    public function openLog($response)
    {
        if ($token = app($this->authService)->getTokenForRequest()) {
            if ($user = Cache::get($token)) {
                $agentId = Redis::hGet('open_token_' . $user->user_id, $token);
                if ($agentId) {
                    // $url = Request::getRequestUri();
                    $requestMethod = Request::getMethod();
                    $ip = Request::ip();
                    $param = Request::all();
                    // $path = Request::path();
                    $fullurl = Request::fullUrl();
                    $header = Request::header();
                    $data = [
                        'agent_id' => $agentId,
                        'client_ip' => $ip,
                        //'user_id' => $user->user_id,
                        'user_accounts' => $user->user_accounts,
                        'method' => $requestMethod,
                        'path' => $fullurl,
                        'header' => json_encode($header, JSON_UNESCAPED_UNICODE),
                        'request_time' => date("Y-m-d H:i:s"),
                        'request_body' => json_encode($param, JSON_UNESCAPED_UNICODE),
                        'return_body' => json_encode($response->original ?? '', JSON_UNESCAPED_UNICODE)
                    ];
                    Queue::push(new OpenApiJob($data));
                    //Queue::push($data);
                    // $this->recordLog($data);
                }
            }
        }
    }

    /**
     * 日志写入mysql
     * @param $log
     * @author [dosy]
     */
    public function recordLog($log)
    {
        app($this->openApplicationLogRepository)->insertData($log);
    }

    public function getData($where)
    {
        return app($this->openApplicationRepository)->getData($where);
    }

}
