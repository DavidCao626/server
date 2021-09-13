<?php

namespace App\EofficeApp\Directive\Controllers;

use Illuminate\Http\Request;
use App\EofficeApp\Base\Controller;
use App\EofficeApp\Directive\Services\DirectiveService;

/**
 * 指令控制器
 *
 * @author liun
 *
 * @since  2016-06-20 新建
 */
class DirectiveController extends Controller
{
	/**
	 * 指令服务
	 * @var object
	 */
	private $directiveService;

	public function __construct(
		Request $request, 
		DirectiveService $directiveService
	) 
	{
		parent::__construct();
		$this->request = $request;
		$this->directiveService = $directiveService;
	}

	/**
	 * 获取用户相关信息
	 * @return [type] [description]
	 */
	public function getUserRelation() {
		$result = $this->directiveService->getUserRelation($this->request->all(), $this->own);
		return $this->returnResult($result); 
	}

	/**
	 * 通过ID获取用户相关信息
	 * @return [type] [description]
	 */
	public function getUserRelationById() {
		$result = $this->directiveService->getUserRelationById($this->request->all(), $this->own);
		return $this->returnResult($result); 
	}

	/**
	 * 根据部门ID获取子部门和所属部门的用户
	 * @return [type] [description]
	 */
	public function getOrganizationMembers($deptId) {
		$result = $this->directiveService->getOrganizationMembers($deptId, $this->request->all(), $this->own);
		return $this->returnResult($result); 
	}

	/**
	 * 根据部门、角色、公共组及个人组
	 * @return [type] [description]
	 */
	public function getUserIdByGroup() {
		$result = $this->directiveService->getUserIdByGroup($this->request->all());
		return $this->returnResult($result); 
	}
}