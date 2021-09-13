<?php

namespace App\EofficeApp\System\Cas\Controllers;

use Illuminate\Http\Request;
use App\EofficeApp\Base\Controller;
use App\EofficeApp\System\Cas\Requests\CasRequest;
use App\EofficeApp\System\Cas\Services\CasService;

/**
 * cas控制器
 *
 * @author  缪晨晨
 *
 * @since  2018-01-29 创建
 */
class CasController extends Controller
{

	public function __construct(
		Request $request,
		CasService $casService,
		CasRequest $casRequest
	) {
		parent::__construct();
		$this->request = $request;
		$this->casService = $casService;
        $this->casRequest = $casRequest;
        $this->formFilter($request, $casRequest);
	}

	/**
	 * 【组织架构同步】 获取用户中间表字段列表
	 *
	 * @author 缪晨晨
	 *
	 * @since  2018-01-29 创建
	 *
	 * @return json 查询结果
	 */
	public function getUserAssocFieldsList()
	{
		$result = $this->casService->getUserAssocFieldsList();
		return $this->returnResult($result);
	}

	/**
	 * 【组织架构同步】 获取部门中间表字段列表
	 *
	 * @author 缪晨晨
	 *
	 * @since  2018-01-29 创建
	 *
	 * @return json 查询结果
	 */
	public function getDepartmentAssocFieldsList()
	{
		$result = $this->casService->getDepartmentAssocFieldsList();
		return $this->returnResult($result);
	}

	/**
	 * 【组织架构同步】 获取人事档案中间表字段列表
	 *
	 * @author 缪晨晨
	 *
	 * @since  2018-08-02 创建
	 *
	 * @return json 查询结果
	 */
	public function getPersonnelFileAssocFieldsList()
	{
		$result = $this->casService->getPersonnelFileAssocFieldsList();
		return $this->returnResult($result);
	}

	/**
	 * 【组织架构同步】 保存cas认证配置参数
	 *
	 * @author 缪晨晨
	 *
	 * @since  2018-01-29 创建
	 *
	 * @return boolean
	 */
	public function saveCasParams()
	{
		$result = $this->casService->saveCasParams($this->request->all());
		return $this->returnResult($result);
	}

	/**
	 * 【组织架构同步】 获取cas认证配置参数
	 *
	 * @author 缪晨晨
	 *
	 * @since  2018-01-29 创建
	 *
	 * @return boolean
	 */
	public function getCasParams()
	{
		$result = $this->casService->getCasParams();
		return $this->returnResult($result);
	}

	/**
	 * 【组织架构同步】 同步组织架构数据
	 *
	 * @author 缪晨晨
	 *
	 * @since  2018-01-29 创建
	 *
	 * @return boolean
	 */
	public function syncOrganizationData()
	{
		$params = !empty($this->own) ? $this->own : ['user_id' => 'admin'];
		$result = $this->casService->syncOrganizationData($params);
		return $this->returnResult($result);
	}

    /**
     * 【组织架构同步】 获取同步日志（用户和部门）
     *
     * @param
     *
     * @return array
     *
     * @author 缪晨晨
     *
     * @since  2018-01-29 创建
     */
    public function getCasSyncLog()
    {
		$result = $this->casService->getCasSyncLog($this->request->all());
		return $this->returnResult($result);
    }

}