<?php

namespace App\EofficeApp\XiaoE\Services;
/**
 * 对接小e助手服务类
 *
 * @author lizhijun
 */

use App\EofficeApp\XiaoE\Repositories\XiaoESystemParamsRepository;
use App\Jobs\SyncDictJob;
use Illuminate\Support\Facades\Log;
use Queue;
use App\EofficeApp\Base\BaseService;
use App\EofficeApp\XiaoE\Traits\XiaoETrait;
use Illuminate\Support\Facades\URL;

class SystemService extends BaseService
{

    use XiaoETrait;
    /**
     * 小e二次开发扩展目录
     * @var string
     */
    private $extendDir = '../ext/xiaoe/extend/';
    /**
     * 拉取且同步字典
     * @var array
     */
    private $dict = [
        'calendar_level',
        'meet_type',
        'flow_type',
        'city',
        'get_vacation_type',
        'get_user_list',
        'get_leave_days',
        'project_status',
        'get_all_dept'
    ];
    /**
     * 应用参数仓库
     * @var string
     */
    private $xiaoEAppParamsRepository;
    /**
     * 系统参数仓库
     * @var string
     */
    private $xiaoESystemParamsRepository;
    /**
     * 小e日志仓库
     * @var string
     */
    private $xiaoELogRepository;

    public function __construct()
    {
        $this->xiaoEAppParamsRepository = 'App\EofficeApp\XiaoE\Repositories\XiaoEAppParamsRepository';
        $this->xiaoESystemParamsRepository = 'App\EofficeApp\XiaoE\Repositories\XiaoESystemParamsRepository';
        $this->xiaoELogRepository = 'App\EofficeApp\XiaoE\Repositories\XiaoELogRepository';
    }

    /**
     * 授权生成app_id和app_secret
     * @param $params
     */
    public function authorise($params, $own)
    {
        $params = $this->getApplicantInfo($params);
        $company = $params['company'];
        $domain = trim($params['domain']);
        $lastIndex = strlen($domain) - 1;
        if ($domain[$lastIndex] == '/') {
            $domain = substr($domain, 0, $lastIndex);
        }
        //外网域名
        if (!$this->checkDomain($domain)) {
            return ['code' => ['0x011004', 'xiaoe']];
        }
        $testApi = $domain . '/api/xiao-e/system/test';
        $response = $this->sendRequest('get', $testApi);
        //地址不正确
        if (!isset($response['data']) || $response['data'] !== 'success') {
            return ['code' => ['0x011002', 'xiaoe']];
        }
        $appName = $company;
        //申请失败
        $response = $this->createApp($appName, $domain);
        if (!$response) {
            return ['code' => ['0x011003', 'xiaoe']];
        }
        app($this->xiaoESystemParamsRepository)->set('appId', $response['appId']);
        app($this->xiaoESystemParamsRepository)->set('appSecret', $response['appSecret']);
        app($this->xiaoESystemParamsRepository)->set('domain', $domain);
        //同步字典
        Queue::push(new SyncDictJob());
        return true;
    }

    /**
     * 获取申请人信息，目前只有公司和所要绑定的外网地址
     * @param $params
     */
    private function getApplicantInfo($params)
    {
        //用户填写了就不会去自定获取
        if (isset($params['company']) && !empty($params['company']) && isset($params['domain']) && !empty($params['domain'])) {
            return $params;
        }
        $company = app('App\EofficeApp\System\Company\Services\CompanyService')->getCompanyDetail();
        if (!isset($company['company_name']) || empty($company['company_name'])) {
            return false;
        }
        $params['company'] = $company['company_name'];
        $fullUrl = URL::full();
        $params['domain'] = str_replace('/api/xiao-e/system/authorise', '', $fullUrl);
        return $params;
    }

    /**
     * 检测url中的ip是否是外网的
     * @param $url
     */
    private function checkDomain($url)
    {
        $url = parse_url($url);
        if (!isset($url['host']) || empty($url['host'])) {
            return false;
        }
        $host = $url['host'];
        if ($host == 'localhost') {
            return false;
        }
        //ip的话判断下是不是公网ip
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            if (!(filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE))) {
                return false;
            }
        }
        return true;
    }

    /**
     * 测试环境和正式环境配置有区别
     * @return array
     */
    private function getDeveloperConfig()
    {
        $debug = envOverload('XIAOE_DEBUG', 0);
        if ($debug > 0) {
            $appId = 'cmcm5gy9';
            $appSecret = 'd0dccf7565782278c5dcb881f39e7985';
            $authDomain = 'https://ai.m-portal.cn/dev/';
            $productId = 2;
        } else {
            $appId = 'cmcm5gy9';
            $appSecret = 'd0dccf7565782278c5dcb881f39e7985';
            $authDomain = 'https://ai.easst.cn/dev/';
            $productId = 2;
        }
        return [$appId, $appSecret, $authDomain, $productId];
    }

    /**
     * 在小e后台创建应用
     */
    private function createApp($appName, $doMain)
    {
        list($appId, $appSecret, $authDomain, $productId) = $this->getDeveloperConfig();
        $timestamp = time() . '000';
        $params = [
            'timestamp' => $timestamp,
            'appId' => $appId,
            'appName' => $appName,
            'productId' => $productId,
            'systemDomain' => $doMain
        ];
        $params['sign'] = $this->generateSignature($appSecret, $params);
        $response = $this->sendRequest('post', $authDomain . 'createApp', [
            'json' => $params
        ]);
        if (!isset($response['data']['appId']) || !isset($response['data']['appSecret'])) {
            return false;
        }
        return $response['data'];
    }

    /**
     * 同步数据字典(暂时只在定时任务中发现使用, 前端未发现调用)
     *  因请求并发量过大，为减少小E后台压力，做以下调整
     *  1. 无效customerAppId 不请求
     *  2. 记录上次同步词典内容，若无变化也不请求
     */
    public function syncDictData()
    {
        list($appId, $appSecret, $authDomain, $productId) = $this->getDeveloperConfig();
        $timestamp = time() . '000';
        $basicDictionary = $this->dict;
        $extendDictionary = $this->getExtendSyncDict();
        $changed = $this->isDictChanged($extendDictionary);

        if ($changed) {
            $dict = array_merge($basicDictionary, $extendDictionary);
            $customerAppId = app($this->xiaoESystemParamsRepository)->get('appId');
            $invalid = $this->isCustomerAppIdInvalid($customerAppId);

            if ($invalid) {
                return [];
            }

            $dicNames = implode(',', $dict);
            $extendDicName = implode(',', $extendDictionary);

            $params = [
                'timestamp' => $timestamp,
                'appId' => $appId,
                'customerAppId' => $customerAppId,
                'dicNames' => $dicNames,
            ];
            $params['sign'] = $this->generateSignature($appSecret, $params);
            $response = $this->sendRequest('post', $authDomain . 'pullAndPushDictionary', [
                'json' => $params
            ]);

            if (!$response || !isset($response['data']['successDicNames']) || count($response['data']['successDicNames']) != count($dict)) {
                return ['code' => ['0x011005', 'xiaoe']];
            }

            // 如果更新成功则更新数据库
            app($this->xiaoESystemParamsRepository)->set(XiaoESystemParamsRepository::EXTEND_DICTIONARY, $extendDicName);

            $record = app($this->xiaoESystemParamsRepository)->set('syncDictTime', date('Y-m-d H:i:s'));

            return $record;
        }

        return [];
    }

    /**
     * 判断customerAppId是否为非法
     *
     * @param string $customerAppId
     * @return bool
     */
    private function isCustomerAppIdInvalid($customerAppId)
    {
        if (!$customerAppId) {
            return true;
        }

        // 根据职能办公研发部 这两种id较多
        if (in_array($customerAppId, ['1111', '111', '0'])) {
            return true;
        }

        return false;
    }

    /**
     * 判断词典是否有变化
     *
     * @param array $extendDictionary
     * @return bool
     */
    private function isDictChanged($extendDictionary)
    {
        /** @var XiaoESystemParamsRepository $repository */
        $repository =  app($this->xiaoESystemParamsRepository);
        $oldExtendDictionary = $repository->get(XiaoESystemParamsRepository::EXTEND_DICTIONARY);
        // 若未设置过则更新一次
        if ($oldExtendDictionary === null) {

            return true;
        } else {
            if (!$oldExtendDictionary) {
                $oldExtendDictionaryArr = [];
            } else {
                $oldExtendDictionaryArr = explode(',', $oldExtendDictionary);
            }

            if (array_diff($oldExtendDictionaryArr, $extendDictionary)) {

                return true;
            }
        }

        return false;
    }

    /**
     * 同步字典加入二次开发需要同步的字典
     *
     * @return array
     */
    private function getExtendSyncDict()
    {
        $dict = array();
        try {
            if (!file_exists($this->extendDir)) {
                return $dict;
            }
            $extendDirs = glob($this->extendDir . '*', GLOB_ONLYDIR);
            if (!$extendDirs) {
                return $dict;
            }
            $serviceName = 'DictService';
            foreach ($extendDirs as $dir) {
                $dictService = $dir . DIRECTORY_SEPARATOR . 'server' . DIRECTORY_SEPARATOR . $serviceName . '.php';
                if (!file_exists($dictService)) {
                    continue;
                }
                require_once $dictService;
                if (!class_exists('DictService')) {
                    continue;
                }
                $service = new $serviceName();
                if (isset($service->dict)) {
                    $dict = array_merge($dict, $service->dict);
                }
            }
            return $dict;
        } catch (Exception $exception) {
            return $dict;
        }
    }

    /**
     * 生成签名
     * @param $params
     * @return string
     */
    private function generateSignature($appsecret, $params)
    {
        ksort($params);
        $sign = $appsecret;
        foreach ($params as $key => $value) {
            $sign .= $key . $value;
        }
        $sign .= $appsecret;
        return md5($sign);
    }

    /**
     * 获取appid和appsecret
     * @return array
     */
    public function getSecretInfo()
    {
        return [
            'appId' => app($this->xiaoESystemParamsRepository)->get('appId'),
            'appSecret' => app($this->xiaoESystemParamsRepository)->get('appSecret'),
            'domain' => app($this->xiaoESystemParamsRepository)->get('domain'),
        ];
    }

    /**
     * 前端是否展示小e入口
     * @return bool
     */
    public function canUse()
    {
        return app($this->xiaoESystemParamsRepository)->get('appId') && app($this->xiaoESystemParamsRepository)->get('appSecret');
    }

    /**
     * 更新appid和appsecret
     * @param $params
     * @return bool
     */
    public function updateSecretInfo($params)
    {
        app($this->xiaoESystemParamsRepository)->set('appId', $params['appId']);
        app($this->xiaoESystemParamsRepository)->set('appSecret', $params['appSecret']);
        return true;
    }

    /**
     * 获取意图配置
     */
    public function getConfigIntention($params)
    {
        $params = $this->parseParams($params);
        $intentions = app($this->xiaoEAppParamsRepository)->getIntentionConfig($params)->toArray();
        $count = app($this->xiaoEAppParamsRepository)->getIntentionConfigTotal($params);
        $intentions = array_map(function ($row) {
            $params = json_decode($row['params'], true);
            //判断是否关联流程
            $row['is_relation_flow'] = isset($params['flow_id']) && !empty($params['flow_id']) ? 1 : 0;
            return $row;
        }, $intentions);
        return ['list' => $intentions, 'total' => $count];
    }

    /**
     * 根据意图键获取该意图的详细信息
     * param $key module.intention_key
     */
    public function getIntentionDetail($key)
    {
        //一些可能用到的通用的信息
        $commonFields = [
            ['key' => 'dept', 'value' => '所在部门'],
            ['key' => 'role', 'value' => '所属角色'],
            ['key' => 'now', 'value' => '当前时间']
        ];
        $intention = app($this->xiaoEAppParamsRepository)->getIntentionDetail($key)->toArray();
        if (!$intention) {
            return $intention;
        }
        $intention['fields'] = json_decode($intention['fields'], true);
        $intention['params'] = json_decode($intention['params'], true);
        $intention['fields'] = array_merge($intention['fields'], $commonFields);
        return $intention;
    }

    /**
     * 更新意图参数
     * @param $params
     */
    public function updateIntentionParams($params)
    {
        $key = $params['intention'];
        $relations = $params['relations'];
        //未关联流程
        if (!isset($params['flow_id']) || !$params['flow_id']) {
            $params = null;
        } else {
            $params = [
                'flow_id' => $params['flow_id'],
                'form_data' => [],
                'form_data_type' => []
            ];
            foreach ($relations as $relation) {
                $params['form_data'][$relation['field']] = $relation['refer'];
                $params['form_data_type'][$relation['field']] = $relation['type'];
            }
            $params = json_encode($params);
        }
        return app($this->xiaoEAppParamsRepository)->updateData([
            'params' => $params
        ], [
            'intention_key' => $key
        ]);
    }

    /**
     * 获取意图关联的流程的配置信息
     * @param $key
     * @return array|mixed
     */
    public function getIntentionParams($key)
    {
        $default = [
            'flow_id' => '',
            'form_data' => [],
        ];
        $intention = app($this->xiaoEAppParamsRepository)->getIntentionDetail($key)->toArray();
        if (!$intention || !$intention['params']) {
            return $default;
        }
        $params = json_decode($intention['params'], true);
        return $params;
    }

    /**
     * 查询监控的意图
     */
    public function getMonitoringList($param)
    {
        $response = $this->response(app($this->xiaoELogRepository), 'getMonitoringCount', 'getMonitoringList', $this->parseParams($param));
        return $response;
    }

    /**
     * 监控报表配置
     * @param $param
     */
    public function getMonitoringChartConfig($param)
    {
        $param = $this->parseParams($param);
        if (!isset($param['type'])) {
            return [];
        }
        $data = array();
        switch ($param['type']) {
            case 'count':
                //意图使用排名
                $count = app($this->xiaoELogRepository)->countIntenttionUsed($param);
                $countConfig['category'] = $count ? array_column($count, 'intention_name') : [];
                $countConfig['series'] = $count ? array_column($count, 'count') : [];
                $data = $countConfig;
                break;
            case 'platform':
                //访问平台统计
                $count = app($this->xiaoELogRepository)->countFromPlatfrom($param);
                $platformConfig['legend'] = $count ? array_column($count, 'platform') : [];
                $platformConfig['series'] = array();
                if ($count) {
                    foreach ($count as $row) {
                        $platformConfig['series'][] = [
                            'name' => $row['platform'],
                            'value' => $row['count']
                        ];
                    }
                }
                $data = $platformConfig;
                break;
            case  'query':
                //访问按天统计
                $date = date('Y-m-d', strtotime('-30 days'));
                $param['search']['date'] = [$date, '>'];
                $count = app($this->xiaoELogRepository)->countUsedByDay($param);
                $queryConfig['legend'] = array();
                $queryConfig['date'] = array();
                $queryConfig['series'] = array();
                if ($count) {
                    $queryConfig['sub_title'] = $date . '~' . date('Y-m-d');
                    $queryConfig['legend'] = array_values(array_unique(array_column($count, 'intention_name')));
                    $queryConfig['date'] = array_values(array_unique(array_column($count, 'date')));
                    sort($queryConfig['date']);
                    $intentionArray = array();
                    foreach ($count as $item) {
                        $intentionArray[$item['intention_name']][$item['date']] = $item['count'];
                    }
                    foreach ($intentionArray as $intention => $dateAndCount) {
                        $intentionDate = array_keys($dateAndCount);
                        $diffDate = array_diff($queryConfig['date'], $intentionDate);
                        if ($diffDate) {
                            foreach ($diffDate as $date) {
                                $dateAndCount[$date] = null;
                            }
                            ksort($dateAndCount);
                        }
                        $series = [
                            'name' => $intention,
                            'type' => 'bar',
                            'stack' => '总量',
                            'barMaxWidth' => 120,
                            'data' => array_values($dateAndCount)
                        ];
                        $queryConfig['series'][] = $series;
                    }
                }
                $data = $queryConfig;
                break;
            default:
        }
        return $data;
    }
}