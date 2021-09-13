<?php

namespace App\EofficeApp\Empower\Controllers;

use Illuminate\Http\Request;
use App\EofficeApp\Base\Controller;
use App\EofficeApp\Empower\Services\EmpowerService;

/**
 * 授权控制器:提供授权模块相关请求
 *
 * @author qishaobo
 *
 * @since  2015-10-20 创建
 */
class EmpowerController extends Controller
{
    /**
     * 授权数据对象
     * @var object
     */
    private $diaryService;

    public function __construct(
        Request $request,
        EmpowerService $empowerService
        )
    {
        parent::__construct();
        $this->request = $request;
        $this->empowerService = $empowerService;
    }

	/**
	 * 查询PC端授权
     *
	 * @return array 查询结果
     *
     * @author qishaobo
     *
     * @since  2017-03-16
	 */
	public function getPcEmpower()
	{
        $result = $this->empowerService->getPcEmpower($this->request->input('type', 0));
        return $this->returnResult($result);
	}

    /**
     * 查询手机端授权
     *
     * @return array 查询结果
     *
     * @author qishaobo
     *
     * @since  2017-03-16
     */
    public function getMobileEmpower()
    {
        $result = $this->empowerService->getMobileEmpower($this->request->input('type', 0));
        return $this->returnResult($result);
    }

    /**
     * 导出授权
     *
     * @return array 查询结果
     *
     * @author qishaobo
     *
     * @since  2017-03-16
     */
    public function exportEmpower()
    {
        return $this->empowerService->exportEmpower($this->request->all(), $this->own);
    }

    /**
     * 导入授权
     *
     * @return array 查询结果
     *
     * @author qishaobo
     *
     * @since  2017-03-16
     */
    public function importEmpower()
    {
        $result = $this->empowerService->importEmpower($this->request->all());
        return $this->returnResult($result);
    }

    /**
     * 获取系统模块
     *
     * @return array 查询结果
     *
     * @author qishaobo
     *
     * @since  2017-03-16
     */
    public function getModules()
    {
        $result = $this->empowerService->getModules();
        return $this->returnResult($result);
    }

    /**
     * 获取模块授权信息
     *
     * @return array 查询结果
     *
     * @author qishaobo
     *
     * @since  2017-03-16
     */
    public function getModuleEmpower()
    {
        $result = $this->empowerService->getModuleEmpower();
        return $this->returnResult($result);
    }

    /**
     * 添加模块授权信息
     *
     * @return array 查询结果
     *
     * @author qishaobo
     *
     * @since  2017-03-16
     */
    public function addModuleEmpower()
    {
        $result = $this->empowerService->addModuleEmpower($this->request->all());
        return $this->returnResult($result);
    }

    /**
     * 检查手机授权和手机访问是否允许
     *
     * @return boolean
     *
     * @author 缪晨晨
     *
     * @since  2017-06-06
     */
    public function checkMobileEmpowerAndWapAllow($userId)
    {
        $result = $this->empowerService->checkMobileEmpowerAndWapAllow($userId);
        return $this->returnResult($result);
    }
    public function getEmpowerInfo($userId)
    {
        return $this->returnResult($this->empowerService->getEmpowerInfo($userId));
    }
    /**
     * 获取系统版本
     *
     * @return string
     *
     * @author 缪晨晨
     *
     * @since  2017-06-07
     */
    public function getSystemVersion()
    {
        $result = $this->empowerService->getSystemVersion();
        return $this->returnResult($result);
    }

    /**
     * 获取机器码
     *
     * @return string
     *
     * @author 缪晨晨
     *
     * @since  2017-10-23
     */
    public function getMachineCode()
    {
        $result = $this->empowerService->getMachineCode();
        return $this->returnResult($result);
    }
    // 检查是否是案例平台
    public function getEmpowerPlatform() {
        $result = $this->empowerService->getEmpowerPlatform();
        return $this->returnResult($result);
    }
}