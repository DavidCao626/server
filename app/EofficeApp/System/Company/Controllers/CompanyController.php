<?php

namespace App\EofficeApp\System\Company\Controllers;

use Request;
use App\EofficeApp\Base\Controller;
use App\EofficeApp\System\Company\Services\CompanyService;

/**
 * 公司信息控制器:提供公司信息相关外部请求并提供返回值
 *
 * @author qishaobo
 *
 * @since  2015-10-20 创建
 */
class CompanyController extends Controller
{
    /**
     * 公司信息service
     *
     * @var object
     */
    private $companyService;

    public function __construct(CompanyService $companyService)
    {
        parent::__construct();
        $this->companyService = $companyService;
    }

	/**
	 * 获取公司信息
     *
	 * @return array 查询结果或错误码
     *
     * @author qishaobo
     *
     * @since  2015-10-20 创建
	 */
	public function getIndexCompany()
	{
        $result = $this->companyService->getCompanyDetail();
        return $this->returnResult($result);
	}

    /**
     * 新建公司信息
     *
     * @return int|array 成功状态码|错误码
     *
     * @author qishaobo
     *
     * @since  2015-10-20 创建
     */
    public function createCompany()
    {
        $result = $this->companyService->createCompany(Request::all());
        return $this->returnResult($result);
    }

    /**
     * 修改公司信息
     *
     * @return int|array 成功状态码|错误码
     *
     * @author qishaobo
     *
     * @since  2015-10-20 创建
     */
    public function editCompany()
    {
        $result = $this->companyService->editCompany(Request::all());
        return $this->returnResult($result);
    }

    /**
     * 访问不存在方法处理
     *
     * @return string 提示信息
     *
     * @author: qishaobo
     *
     * @since：2015-10-21
     */
    public function __call($name, $param)
    {
        return 'function '.$name.' not exist';
    }
}