<?php

namespace App\Http\Middleware;

use Closure;
use App\EofficeApp\UnifiedMessage\Services\HeterogeneousSystemService;
use Cache;
use Lang;
use Illuminate\Http\JsonResponse;

// 统一消息接受接受第三方数据
class AccessUnifiedMessage
{
    private $heterogeneousSystemService;

    public function __construct(
        HeterogeneousSystemService $heterogeneousSystemService
    ) {
        $this->heterogeneousSystemService = $heterogeneousSystemService;
    }

    public function handle($request, Closure $next)
    {
        $input = $request->all();
        $this->logWrite($input);
        $apiToken = $this->getApiToken($request);
        if (!$apiToken) {
            return new JsonResponse(error_response('0x000025', 'unifiedMessage'), 200);
        }
        $tokenNews = authCode($apiToken);
        if (empty($tokenNews)) {
            return new JsonResponse(error_response('0x000025', 'unifiedMessage'), 200);
        }
        if (empty($request->input('operator'))) {
            return new JsonResponse(error_response('0x000014', 'unifiedMessage'), 200);
        }

        $this->setLocale($request, $apiToken);
        //系统标识固定32位
        $systemCode = substr($tokenNews, 0, 32);
        $systemSecret = substr($tokenNews, 32);
        $request['system_code'] = $systemCode;
        $request['system_secret'] = $systemSecret;
        return $next($request);
    }

    /**
     * 设置本地化语言
     *
     * @return
     *
     * @author qishaobo
     *
     * @modify lizhijun
     *
     * @since  2016-02-17 创建
     */
    private function setLocale($request, $token = null)
    {
        if ($token) {
            if (ecache('Lang:Local')->get($token)) {
                Lang::setLocale(ecache('Lang:Local')->get($token));
            }
        } else {
            if ($request->has('local')) {
                Lang::setLocale($request->input('local'));
            }
        }
        return true;
    }

    private function getApiToken($request)
    {
        static $apiToken = null;

        if ($apiToken != null) {
            return $apiToken;
        } else {
            $token = $request->input('api_token');
            if (empty($token)) {
                $token = $request->bearerToken();
            }

            if (empty($token)) {
                $token = $request->getPassword();
            }
            if ($token) {
                return $apiToken = $token;
            }

            return $apiToken = false;
        }
    }

    //记录数据日志
    public function logWrite($data)
    {
        $requestData = date('Y-m-d H:i:s') . '***:' . json_encode($data, JSON_UNESCAPED_UNICODE) . "\r\n";
        $fileName = storage_path() . '/logs/unifiedMessage.log';
        file_put_contents($fileName, $requestData, FILE_APPEND);
    }

    public function getIP()
    {
        $realip = '';
        if (isset($_SERVER)) {
            if (isset($_SERVER["HTTP_X_FORWARDED_FOR"])) {
                $realip = $_SERVER["HTTP_X_FORWARDED_FOR"];
            } else {
                if (isset($_SERVER["HTTP_CLIENT_IP"])) {
                    $realip = $_SERVER["HTTP_CLIENT_IP"];
                } else {
                    $realip = $_SERVER["REMOTE_ADDR"];
                }
            }
        } else {
            if (getenv("HTTP_X_FORWARDED_FOR")) {
                $realip = getenv("HTTP_X_FORWARDED_FOR");
            } else {
                if (getenv("HTTP_CLIENT_IP")) {
                    $realip = getenv("HTTP_CLIENT_IP");
                } else {
                    $realip = getenv("REMOTE_ADDR");
                }
            }
        }
        return $realip;
    }
}
