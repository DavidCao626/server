<?php

namespace App\EofficeApp\Api\Controllers;

use Illuminate\Http\Request;
use App\EofficeApp\Base\Controller;
use App\EofficeApp\Api\Services\ApiService;

/**
 * Api控制器
 *
 * @author  齐少博
 *
 * @since  2016-08-23 创建
 */
class ApiController extends Controller
{

	public function __construct(
		Request $request,
		ApiService $apiService
	) {
        parent::__construct();
		$this->request = $request;
		$this->apiService = $apiService;
	}

	/**
	 * 获取Api返回值
	 *
	 * @author 齐少博
	 *
	 * @since  2016-08-23 创建
	 *
	 * @return json 查询结果
	 */
	public function getApis()
	{
		$params = $this->request->all();

		if (empty($params)) {
			$this->returnResult([]);
		}

		$method = 'GET';
		$routeBase = config("app.route_base");
        $app = $this->apiService->createApp();

		$data = [];
		foreach ($params as $v) {
			$param = json_decode($v, true);
        	$pathInfo = $routeBase.'/'.$param['api'];
			$parameters = !empty($param['param']) ? $param['param'] : [];
			$key = empty($param['key']) ? $param['api'] : $param['key'];

			$request = Request::create($pathInfo, $method, $parameters);
			$response = $app->handle($request)->getOriginalContent();

			if (isset($response['code'])) {
				$data[$key] = $this->returnResult($response);
			} else {
				$data[$key] = $response;
			}
		}

		return $this->returnResult($data);
	}

	/**
	 * 测试sql
	 *
	 * @author 齐少博
	 *
	 * @since  2016-09-13 创建
	 *
	 * @return json 测试结果
	 */
	function testSql()
	{
		$handle = $this->request->input('handle', 'execute'); //'execute', 'examine'
		$param = $this->request->all();
		$result = $this->apiService->testSql($param, $handle);
		if($handle == 'examine') {
			if($result !== 'success' && $result == 'error') {
				$result = ['code' => ['0x015030', 'system']];
			}
		}
		return $this->returnResult($result);
	}

	/**
	 * 获取sql字段信息
	 *
	 * @author 齐少博
	 *
	 * @since  2016-11-18 创建
	 *
	 * @return json 测试结果
	 */
	function getSqlFields()
	{
		$sql = $this->request->input('sql');
		$result = $this->apiService->getSqlFields($sql);

		return $this->returnResult($result);
	}

	/**
	 * 测试url是否可以访问
	 *
	 * @author 齐少博
	 *
	 * @since  2016-12-05 创建
	 *
	 * @return json 测试结果
	 */
	function testUrl()
	{
		$result = $this->apiService->testUrl($this->request->all());

		return $this->returnResult($result);
	}

	/**
	 * 获取url返回值
	 *
	 * @author 齐少博
	 *
	 * @since  2016-12-05 创建
	 *
	 * @return json 测试结果
	 */
	function getUrlData()
	{
		$result = $this->apiService->getUrlData($this->request->all(), $this->own);

		return $this->returnResult($result);
	}
	/**
	 * 获取grid设置
	 */
	function getWebGridSet($key)
	{
		dd($key);
		$result = $this->apiService->getWebGridSet($key);

		return $this->returnResult($result);
	}
	/**
	 * 保存grid设置
	 */
	function saveWebGridSet()
	{
		$result = $this->apiService->saveWebGridSet($this->request->all());

		return $this->returnResult($result);
	}

}