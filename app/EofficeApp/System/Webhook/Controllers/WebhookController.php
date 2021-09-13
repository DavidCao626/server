<?php

namespace App\EofficeApp\System\Webhook\Controllers;

use Illuminate\Http\Request;
use App\EofficeApp\Base\Controller;
use App\EofficeApp\System\Webhook\Services\WebhookService;

/**
 * webhook控制器
 *
 * @author  齐少博
 *
 * @since  2016-07-11 创建
 */
class WebhookController extends Controller
{

	public function __construct(
		Request $request,
		WebhookService $webhookService
	) {
        parent::__construct();
		$this->request = $request;
		$this->webhookService = $webhookService;
	}

	/**
	 * 获取webhook菜单列表
	 *
	 * @author 齐少博
	 *
	 * @since  2016-07-11 创建
	 *
	 * @return json 查询结果
	 */
	public function getMenus()
	{
		$result = $this->webhookService->getMenus();
		return $this->returnResult($result);
	}

	/**
	 * 设置webhook菜单列表
	 *
	 * @author 齐少博
	 *
	 * @since  2016-07-11 创建
	 *
	 * @return json 查询结果
	 */
	public function setWebhook()
	{
		$data = $this->request->all();
		$result = $this->webhookService->setWebhook($data);
		return $this->returnResult($result);
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
    public function getWebhook($webhookMenu)
    {
        $result = $this->webhookService->getWebhook($webhookMenu);
        return $this->returnResult($result);
    }

    /**
     * 测试webhook
     *
     * @param  string $webhookMenu webhook菜单
     *
     * @return array        查询结果
     *
     * @author qishaobo
     *
     * @since  2016-07-12 创建
     */
    public function testWebhook()
    {
    	$data = $this->request->all();
        $result = $this->webhookService->testWebhook($data);
        return $this->returnResult($result);
    }

}