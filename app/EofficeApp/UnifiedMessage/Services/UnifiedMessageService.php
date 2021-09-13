<?php

namespace App\EofficeApp\UnifiedMessage\Services;

use App\EofficeApp\Base\BaseService;
use Cache;

/**
 * 统一消息 -- 服务
 * Class UnifiedMessageService
 * @package App\EofficeApp\UnifiedMessage\Services
 */
class UnifiedMessageService extends BaseService
{
    private $heterogeneousSystemIntegrationLogRepository;
    private $heterogeneousSystemRepository;
    private $heterogeneousSystemService;


    public function __construct()
    {
        $this->heterogeneousSystemIntegrationLogRepository = 'App\EofficeApp\UnifiedMessage\Repositories\HeterogeneousSystemIntegrationLogRepository';
        $this->heterogeneousSystemRepository = 'App\EofficeApp\UnifiedMessage\Repositories\HeterogeneousSystemRepository';
        $this->heterogeneousSystemService = 'App\EofficeApp\UnifiedMessage\Services\HeterogeneousSystemService';
    }

    /**
     * 获取token
     * @param $params
     * @return type|array
     * @author [dosy]
     */
    public function registerToken($params)
    {
        if (!isset($params['system_code'])) {
            return ['code' => ['0x000002', 'unifiedMessage']];
        }
        if (!isset($params['system_secret'])) {
            return ['code' => ['0x000008', 'unifiedMessage']];
        }
        $where = ['system_code' => $params['system_code'], 'system_secret' => $params['system_secret']];
        $result = app($this->heterogeneousSystemRepository)->getData($where);
        if ($result) {
            if (isset($result['is_receive_data']) && $result['is_receive_data'] == 1) {
                if (isset($result['security_ip']) && !empty($result['security_ip'])) {
                    $securityIp = explode(',', $result['security_ip']);
                    $ip = $this->getIP();
                    if (in_array($ip, $securityIp)) {
                        $tokenParam = $params['system_code'] . $params['system_secret'];
                        $apiToken = $this->generateToken($tokenParam);
                        if (isset($params['local'])) {
                            if ($params['local'] == 'en') {
                                $params['local'] = 'en';
                            } else {
                                $params['local'] = 'zh-CN';
                            }
                        }
                        ecache('Lang:Local')->set($apiToken, ($params['local'] ?? 'zh-CN'));
                        return ['api_token' => $apiToken];
                    } else {
                        return ['code' => ['0x000015', 'unifiedMessage']];
                    }
                } else {
                    $tokenParam = $params['system_code'] . $params['system_secret'];
                    $apiToken = $this->generateToken($tokenParam);
                    if (isset($params['local'])) {
                        if ($params['local'] == 'en') {
                            $params['local'] = 'en';
                        } else {
                            $params['local'] = 'zh-CN';
                        }
                    }
                    ecache('Lang:Local')->set($apiToken, ($params['local'] ?? 'zh-CN'));
                    return ['api_token' => $apiToken];
                }
            } else {
                return ['code' => ['0x000016', 'unifiedMessage']];
            }
        } else {
            return ['code' => ['0x000027', 'unifiedMessage']];
        }
    }

    /**
     * 检查异构系统
     * @param $params
     * @return mixed
     * @author [dosy]
     */
    public function acceptSystemCheck($params)
    {
        $where = ['system_code' => $params['system_code'], 'system_secret' => $params['system_secret']];
        $result = app($this->heterogeneousSystemRepository)->getData($where);
        if ($result) {
            if (isset($result['is_receive_data']) && $result['is_receive_data'] == 1) {
                if (isset($result['security_ip']) && !empty($result['security_ip'])) {
                    $securityIp = explode(',', $result['security_ip']);
                    $ip = $this->getIP();
                    if (in_array($ip, $securityIp)) {
                        return ['id' => $result['id'], 'system_name' => $result['system_name'],'pc_domain'=>$result['pc_domain'],'app_domain'=>$result['app_domain']];
                    } else {
                        return ['code' => ['0x000015', 'unifiedMessage']];
                    }
                }
                return ['id' => $result['id'], 'system_name' => $result['system_name'],'pc_domain'=>$result['pc_domain'],'app_domain'=>$result['app_domain']];
            } else {
                return ['code' => ['0x000016', 'unifiedMessage']];
            }
        } else {
            return ['code' => ['0x000024', 'unifiedMessage']];
        }
    }

    /**
     * 获取IP
     * @return array|false|mixed|string
     * @author [dosy]
     */
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

    /**
     * 加密方式生成token
     * @param $tokenParam
     * @return bool|string
     * @author [dosy]
     */
    private function generateToken($tokenParam)
    {
//        $tokenSecret = config('auth.token_secret');
//        $tokenAlgo = config('auth.token_algo');
//return hash($tokenAlgo, $tokenParam . time() . $tokenSecret, false);
        return authCode($tokenParam, 'ENCODE', 'eoffice9731', 7200);
    }


    /**
     * 添加日志
     * @author [dosy]
     */
    public function addLog()
    {
        return '';
    }

    /**
     * 日志详情ById
     * @author [dosy]
     */
    public function getLogById($id)
    {
        $where = ['id' => $id];
        $result = app($this->heterogeneousSystemIntegrationLogRepository)->getData($where);
        return $result;
    }

    /**
     * 日志列表
     * @author [dosy]
     */
    public function getLogList($params)
    {
        $data = $this->response(app($this->heterogeneousSystemIntegrationLogRepository), 'getHeterogeneousSystemIntegrationLogTotal', 'getHeterogeneousSystemIntegrationLogList',
            $this->parseParams($params));
        return $data;
    }

    /**
     * 微信公众号、企业微信、钉钉消息推送 --- 异构系统统一处理
     * @param $json
     * @param $user_id
     * @author [dosy]
     */
    public function unifiedMessageLocation($json,$user_id){
        if (isset($json['module']) && is_string($json['module'])) {
            $heterogeneous = strpos($json['module'], 'heterogeneous_');
            if ($heterogeneous === 0) {
                $params = isset($json['params']) ? $json['params'] : '';
                $param['system_id'] = $params['heterogeneous_system_id'] ? $params['heterogeneous_system_id'] : '';
                $param['message_id'] = $params['message_id'] ? $params['message_id'] : '';
                $heterogeneousSystemInfo = app($this->heterogeneousSystemService)->getDomainReadMessage($param, $user_id);
                if (isset($heterogeneousSystemInfo['code'])) {
                    $message = urlencode(trans("unifiedMessage.0x000028"));
                    $errorUrl = integratedErrorUrl($message);
                    header("Location: $errorUrl");
                    exit;
                }
                $domain = $heterogeneousSystemInfo['app_domain'];
                $address = isset($heterogeneousSystemInfo['message_data']['app_address']) ? $heterogeneousSystemInfo['message_data']['app_address'] : '';
                $url = $domain . $address;
                header("Location: $url");
                exit();
            }
        }
    }
}




