<?php

namespace App\EofficeApp\System\Webhook\Services;
use App\EofficeApp\Base\BaseService;

/**
 * webhook Service类:提供webhook相关服务
 *
 * @author qishaobo
 *
 * @since  2016-07-11 创建
 */
class WebhookService extends BaseService
{
    /**
     * 系统下拉表资源
     * @var object
     */
    private $systemComboboxRepository;

    /**
     * 下拉表标签资源
     * @var object
     */
	private $systemComboboxTagRepository;

    public function __construct(
    ) {
        $this->client = 'GuzzleHttp\Client';
        $this->webhookRepository = 'App\EofficeApp\System\Webhook\Repositories\WebhookRepository';
    }

    /**
     * 获取菜单列表
     *
     * @param  array $param 查询条件
     *
     * @return array        查询结果
     *
     * @author qishaobo
     *
     * @since  2016-07-11 创建
     */
    public function getMenus()
    {
        $menus = config('webhook.webhook');
        $langs = trans('webhook');
        if (empty($menus) || empty($langs)) {
            return [];
        }

        $data = [];

        foreach ($menus as $k => $v) {
            $m = [
                'menu_id'   => $k,
                'menu_name' => isset($langs[$k]) ? $langs[$k] : [],
                'son'       => []
            ];

            foreach ($v as $key => $val) {
                $handles = [];
                foreach ($val as $handle => $function) {
                    $functionName = $k.'-'.$key.'-'.$handle;
                    $handles[$handle] = [
                        'function'          => $function,
                        'function_name'     => isset($langs[$functionName]) ? $langs[$functionName] : '',
                    ];
                }

                $key = $k.'-'.$key;
                $m['son'][] = [
                    'menu_id'   => $key,
                    'menu_name' => isset($langs[$key]) ? $langs[$key] : '',
                    'handle'    => $handles
                ];
            }

            $data[] = $m;
        }

        return $data;
    }

    /**
     * 设置webhook
     *
     * @param  array $data 设置数据
     *
     * @return array        查询结果
     *
     * @author qishaobo
     *
     * @since  2016-07-12 创建
     */
    public function setWebhook($data)
    {
        if (!empty($data)) {
            foreach ($data as $k => $v) {
                $where = ['webhook_function' => [$v['webhook_function']]];

                if (empty($v['webhook_url'])) {
                    app($this->webhookRepository)->deleteByWhere($where);
                    continue;
                }

                $webhook = app($this->webhookRepository)->getWebhook($where)->toArray();

                if (!empty($webhook)) {
                    if ($webhook[0]['webhook_url'] != $v['webhook_url']) {
                        app($this->webhookRepository)->updateData($v, $where);
                    }

                    continue;
                }

                $insertData = [
                    'webhook_menu'      => $v['webhook_menu'],
                    'webhook_function'  => $v['webhook_function'],
                    'webhook_url'       => $v['webhook_url']
                ];

                app($this->webhookRepository)->insertData($insertData);

            }

            return true;
        }

        return false;
    }

    /**
     * 获取webhook
     *
     * @param  string $webhookMenu webhook菜单
     *
     * @return array        查询结果
     *
     * @author qishaobo
     *
     * @since  2016-07-12 创建
     */
    public function getWebhook($webhookMenu = '', $webhookFunction = '')
    {
        $where = [];

        if ($webhookMenu) {
            $where['webhook_menu'] = [$webhookMenu];
        }

        if ($webhookFunction) {
            $where['webhook_function'] = [$webhookFunction];
        }

        return app($this->webhookRepository)->getWebhook($where)->toArray();
    }

    /**
     * 测试webhook
     *
     * @param  array $param 参数
     *
     * @return array        查询结果
     *
     * @author qishaobo
     *
     * @since  2016-07-12 创建
     */
    public function testWebhook($param)
    {
        $data = [
            'url_params'    => ['test' => true],
            'request'       => ['test' => true],
            'response'      => ['test' => true],
            'user_info'     => ['test' => true]
        ];
        $requestUrl = $param['url'] ?? '';
        $requestUrl = trim(trim($requestUrl),'/');
        // 处理url以支持相对路径
        if ($requestUrl && strpos($requestUrl, 'http') === false) {
            $http = $_SERVER['REQUEST_SCHEME']."://".$_SERVER['SERVER_NAME'];
            if ($_SERVER["SERVER_PORT"] != '80') {
                $http .= ':'.$_SERVER["SERVER_PORT"];
            }
            $requestUrl = $http.'/'.$requestUrl;
        }
        if(!check_white_list($requestUrl)) {
            return ['code' => ['0x000025', 'common']];
        }
        try {
            $guzzleResponse = app($this->client)->request('POST', $requestUrl, ['form_params' => $data]);
            $status = $guzzleResponse->getStatusCode();
        } catch (\Exception $e) {
            $status = 0;
        }

        if ($status == '200') {
            return ['test' => true];
        }

        return ['code' => ['0x000012', 'common']];
    }

}