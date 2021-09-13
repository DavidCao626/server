<?php

namespace App\EofficeApp\System\Log\Controllers;

use Illuminate\Http\Request;
use App\EofficeApp\Base\Controller;
use App\EofficeApp\System\Log\Services\LogService;

/**
 * 系统日志控制器
 *
 * @author  齐少博
 *
 * @since  2016-07-01 创建
 */
class LogController extends Controller
{

	public function __construct(
		Request $request,
		LogService $logService
	) {	
		parent::__construct();
		$userInfo      = $this->own;
		$this->request = $request;
		$this->logService = $logService;
	}

	/**
	 * 获取系统日志列表
	 *
	 * @author 齐少博
	 *
	 * @since  2016-07-01 创建
	 *
	 * @return json 查询结果
	 */
	public function getLogList()
	{

		$param = $this->request->all();
		$result = $this->logService->getLogList($param);
		return $this->returnResult($result);
	}

	/**
	 * 获取系统日志类别列表
	 *
	 * @author 齐少博
	 *
	 * @since  2016-07-01 创建
	 *
	 * @return json 查询结果
	 */
	public function getLogTypeList()
	{
		$result = $this->logService->getLogTypeList();
		return $this->returnResult($result);
	}

	/**
	 * 删除系统日志
	 *
	 * @param  int|string $id 日志id,多个用逗号隔开
	 *
	 * @author 齐少博
	 *
	 * @since  2016-07-01 创建
	 *
	 * @return json 查询结果
	 */
	public function deleteLog()
	{	
		$param = $this->request->all();
		$result = $this->logService->deleteLog($param);
		return $this->returnResult($result);
	}

	/**
	 * 获取系统日志统计
	 *
	 * @author 齐少博
	 *
	 * @since  2016-07-07 创建
	 *
	 * @return json 查询结果
	 */
	public function getLogStatistics()
	{
		$param = $this->request->all();
		$result = $this->logService->getLogStatistics($param);
		return $this->returnResult($result);
	}

    /**
     * 获取系统访问人数
     * @return array
     */
    public function getSystemVisitors()
    {
        $param = $this->request->all();
        $result = $this->logService->getSystemVisitors($param);
        return $this->returnResult($result);
    }
	
	public function getAddressFromIpUrl()
	{
		$result = $this->logService->getAddressFromIpUrl();
		return $this->returnResult($result);
	}

	

}