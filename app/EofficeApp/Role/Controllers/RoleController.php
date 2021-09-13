<?php

namespace App\EofficeApp\Role\Controllers;

use Illuminate\Http\Request;
use App\EofficeApp\Base\Controller;
use App\EofficeApp\Role\Requests\RoleRequest;
use App\EofficeApp\Role\Services\RoleService;
use App\EofficeApp\User\Services\UserService;
/**
 * 角色管理控制器:提供角色管理相关外部请求并提供返回值
 *
 * @author qishaobo
 *
 * @since  2015-10-20 创建
 */
class RoleController extends Controller
{
    /**
     * role服务对象
     *
     * @var object
     */
    private $roleService;
    private $userService;
    public function __construct(
        RoleService $roleService,
        Request $request,
        RoleRequest $roleRequest,
        UserService $userService
    ) {
        parent::__construct();
        $this->request = $request;
        $this->roleService = $roleService;
        $this->formFilter($request, $roleRequest);
        $this->userService = $userService;
    }

	/**
	 * 获取角色列表数据
     * @apiTitle 获取角色列表
     * @success {boolean} status(1) 接入成功
	 * @successExample {json} Success-Response:
     * {
     *      "status": 1,
     *      "data": {
     *           "total": 22,
     *           "list": [
     *              {
     *                  "role_id": 1,
     *                  "role_name": "OA管理员",
     *                  "role_no": 0,
     *                  "role_name_zm": "OAgly",
     *                  "role_name_py": "OAguanliyuan",
     *                  "has_many_role": [
     *                      {
     *                      "user_id": "admin",
     *                       "role_id": 1,
     *                      "created_at": "2019-10-10 11:32:25",
     *                       "updated_at": "2019-10-10 11:32:25",
     *                       "deleted_at": null
     *                       },
     *                       ...
     *                   ],
     *                   "has_children": 1,
     *                   "user_total": 15
     *               },
     *               ...
     *           ]
     *       },
     *   "runtime": "0.151"
     *   }
     *
     * @error {boolean} status(0) 接入失败
     * @error {array} errors 接入失败错误信息
     * @errorExample {json} Error-Response:
     * { "status": 0,"errors":[{"code":"0x000003","message":"未知错误"}] }
	 */
	public function getIndexRoles()
	{
        $result = $this->roleService->getRoleList($this->request->all(), $this->own);
        return $this->returnResult($result);
	}

    public function getRolesList(){
         $result = $this->roleService->getLists($this->request->all(), $this->own);
         return $this->returnResult($result);
    }

    /**
     * 保存角色数据
     * @apiTitle 保存角色
     * @param {string} role_name 角色名称
     * @param {string} role_no 角色权限级别，只能是数字
     * @param {string} inherit_role 要继承其权限的角色的ID，逗号拼接
     * @paramExample {json} 参数示例
     * {
     *   "role_name": "新角色" // 角色名称
     *   "role_no": "10" // 角色权限级别，只能是数字
     *  "inherit_role": "" //要继承其权限的角色的ID，逗号拼接
     * }
     * @success {boolean} status(1) 接入成功
     * @successExample {json} Success-Response:
     * {
     *  "status": 1,
     *   "data": 108 // 新创建的角色的ID
     * }
     * @error {boolean} status(0) 接入失败
     * @error {array} errors 接入失败错误信息
     *
     * @errorExample {json} Error-Response:
     * { "status": 0,"errors":[{"code":"0x000003","message":"未知错误"}] }
     */
    public function createRoles()
    {
        $result = $this->roleService->createRole($this->request->all());
        return $this->returnResult($result);
    }

    /**
     * 删除角色
     *
     * @param int|string $roleId 删除角色id,多个用逗号隔开
     *
     * @return bool是否删除
     *
     * @author qishaobo
     *
     * @since  2015-10-20 创建
     */
    public function deleteRoles($roleId)
    {
        $result = $this->roleService->deleteRole($roleId);
        return $this->returnResult($result);
    }

    /**
     * 获取角色详情
     *
     * @param int $roleId 角色id
     *
     * @return array 角色详情
     *
     * @author qishaobo
     *
     * @since  2015-10-20 创建
     */
    public function getRoles($roleId)
    {
        $result = $this->roleService->getRolesDetail($roleId);
        return $this->returnResult($result);
    }

    /**
     * 编辑角色数据
     * @apiTitle 编辑角色
     * @param {int} role_name 角色名称
     * @param {int} role_no 角色权限级别，只能是数字
     * @param {int} inherit_role 要继承其权限的角色的ID，逗号拼接
     * @paramExample {json} 参数示例
     * api/role/1
     * {
     *  "role_name": "新角色", // 角色名称
     *  "role_no": "10", // 角色权限级别，只能是数字
     *  "inherit_role": "" // 要继承其权限的角色的ID，逗号拼接
     * }
     * @success {boolean} status(1) 编辑成功
     * @successExample {json} Success-Response:
     * {
     *  "status": 1,
     * }
     * @error {boolean} status(0) 接入失败
     * @errorExample {json} Error-Response:
     * { "status": 0,"errors":[{"code":"0x000003","message":"未知错误"}] }
     */
    public function editRoles($roleId)
    {
        $result = $this->roleService->updateRole($roleId, $this->request->all());
        return $this->returnResult($result);
    }

    /**
     * 获取人员的角色
     *
     * @param string $userId 用户id
     *
     * @return array 人员角色
     *
     * @author qishaobo
     *
     * @since  2015-10-20 创建
     */
    public function getUserRole($userId)
    {
        if ($userId) {
           $result = $this->roleService->getUserRole($userId);
           return $this->returnResult($result);
        }
    }

    /**
     * 保存人员角色
     *
     * @return bool 添加成功或失败
     *
     * @author qishaobo
     *
     * @since  2015-10-20 创建
     */
    public function createUserRole()
    {
        $result = $this->roleService->addUserRole($this->request->all(), $this->request->has('edit'));
        return $this->returnResult($result);
    }

   /**
     * 删除角色
     *
     * @param string $userId 用户id
     *
     * @return bool 删除是否成功
     *
     * @author qishaobo
     *
     * @since  2015-10-20 创建
     */
    public function deleteUserRole($userId)
    {
        if ($userId) {
            $result = $this->roleService->deleteUserRole($userId);
            return $this->returnResult($result);
        }
    }

    /**
     * 获取角色通信列表
     *
     * @return array 角色通信列表
     *
     * @author qishaobo
     *
     * @since  2015-10-20 创建
     */
    public function getIndexRoleCommunicate()
    {
        $result = $this->roleService->getRoleCommunicate($this->request->all());
        return $this->returnResult($result);
    }

    /**
     * 添加角色通信
     *
     * @return array 添加成功码
     *
     * @author qishaobo
     *
     * @since  2015-10-20 创建
     */
    public function createRoleCommunicate()
    {
        $result = $this->roleService->addRoleCommunicate($this->request->all());
        return $this->returnResult($result);
    }

    /**
     * 获取角色通信列表
     *
     * @param integer $id
     *
     * @return array 角色通信列表
     *
     * @author qishaobo
     *
     * @since  2015-10-20 创建
     */
    public function getRoleCommunicate($id)
    {
        if ($id) {
            $result = $this->roleService->getRoleCommunicateDetail($id);
             return $this->returnResult($result);
        }
    }

    /**
     * 删除角色
     *
     * @param integer $id 角色通信id
     *
     * @return array|bool 错误码|删除是否成功
     *
     * @author qishaobo
     *
     * @since  2015-10-20 创建
     */
    public function deleteRoleCommunicate($id)
    {
        if ($id) {
            $result = $this->roleService->deleteCommunicate($id);
            return $this->returnResult($result);
        }
    }

    /**
     * 编辑角色通信列表
     *
     * @return array 正确码或错误码
     *
     * @author qishaobo
     *
     * @since  2015-10-20 创建
     */
    public function editRoleCommunicate($id)
    {
        $result = $this->roleService->editCommunicate($this->request->all(), $id);
        return $this->returnResult($result);
    }

    /**
     * 获取用户上下级
     *
     * @param string $userId
     *
     * @return array 用户上下级列表
     *
     * @author qishaobo
     *
     * @since  2015-10-20 创建
     */
    public function getUserSuperior($userId)
    {
        if ($userId) {
            $result = $this->roleService->getUserSuperior($userId);
            return $this->returnResult($result);
        }
    }

    /**
     * 获取用户上下级列表
     *
     * @param string $userId
     *
     * @return array 用户上下级列表
     *
     * @author qishaobo
     *
     * @since  2015-12-24 创建
     */
    public function getIndexUserSuperior()
    {
        $result = $this->roleService->getUserSuperiorList($this->request->all());
        return $this->returnResult($result);
    }

    /**
     * 保存用户上下级
     *
     * @return array  正确码或错误码
     *
     * @author qishaobo
     *
     * @since  2015-10-20 创建
     */
    public function createUserSuperior()
    {
        $result = $this->roleService->addUserSuperior($this->request->all());
        return $this->returnResult($result);
    }
    public function getRoleLevel()
    {
        $result = $this->roleService->getRoleLevel();
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

    public function communicateRoles(){
        $result = $this->roleService->communicateCount($this->request->all());
        return $this->returnResult($result);
    }

    /**
     * 获取角色ID集合中的最大角色级别的角色
     *
     * @author 缪晨晨
     *
     * @param  array or string $data [description]
     *
     * @since  2017-06-07 创建
     *
     * @return string 返回最大角色级别的角色ID
     */
    public function getMaxRoleNoFromData($data) {
        $result = $this->roleService->getMaxRoleNoFromData($data);
        return $this->returnResult($result);
    }   


    public function getAllUserSuperior($userId)
    {   
        $result = $this->userService->getAllUserSuperior($userId,$this->request->all());
        return $this->returnResult($result);
    }
    //获取某个部门下所有角色
    public function getDeptRole()
    {   
        $result = $this->roleService->getDeptRole($this->request->all());
        return $this->returnResult($result);
    }

    public function roleControlFields()
    {
        $result = $this->roleService->roleControlFields();
        return $this->returnResult($result);
    }
}
