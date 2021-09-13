<?php
namespace App\EofficeApp\System\Department\Controllers;

use Illuminate\Http\Request;
use App\EofficeApp\Base\Controller;
use App\EofficeApp\System\Department\Requests\DepartmentRequest;
use App\EofficeApp\System\Department\Services\DepartmentService;
/**
 * @部门管理控制器
 *
 * @author 李志军
 */
class DepartmentController extends Controller
{
    private $departmentService; // DepartmentService 对象

    /**
     * @注册 DepartmentService 对象
     * @param \App\EofficeApp\Services\DepartmentService $departmentService
     */
    public function __construct(
            DepartmentService $departmentService,
            Request $request,
            DepartmentRequest $departmentRequest
            )
    {
        parent::__construct();

        $this->departmentService = $departmentService;
        $this->request = $request;
        $this->formFilter($request, $departmentRequest);
    }

    /**
     * 部门列表
     *
     * @apiTitle 获取部门列表
     * @success {boolean} status(1) 接入成功
     * @successExample {json} Success-Response:
     * {
     *      "status": 1,
     *      "data": {
     *          {
     *              "dept_id": 63, // 部门ID
     *              "dept_name": "支撑服务部", // 部门名称
     *              "tel_no": "", // 部门电话
     *              "fax_no": "", // 部门传真
     *              "parent_id": 0, // 父级部门ID，顶级部门为0
     *              "dept_name_py": "zhichengfuwubu",
     *              "dept_name_zm": "zcfwb",
     *              "arr_parent_id": "0", // 部门路径 顶级部门为0
     *              "has_children": 1, // 是否有子部门 1、有 0、没有
     *              "dept_sort": 0, // 部门序号
     *              "deleted_at": null,
     *              "created_at": "2017-04-19 19:14:13",
     *              "updated_at": "2018-05-23 11:07:21",
     *              "level": 0
     *          },......
     *      }
     * }
     *
     * @error {boolean} status(0) 接入失败
     * @error {array} errors 接入失败错误信息
     *
     * @errorExample {json} Error-Response:
     * { "status": 0,"errors":[{"code":"0x000003","message":"未知错误"}] }
     */
    public function allTree()
    {
        return $this->returnResult($this->departmentService->tree());
    }
    /**
     * @获取整棵有权限部门树
     * @return json 部门树列表
     */
    public function tree()
    {
        return $this->returnResult($this->departmentService->authTree($this->own));
    }
    /**
     * @获取除了当前部门和其所有子部门外的整棵有权限部门树
     * @param type $deptId 部门Id
     * @return json 部门树列表
     */
    public function exceptTree($deptId)
    {
        return $this->returnResult($this->departmentService->authTree2($deptId,$this->own));
    }
    /**
     * @获取某部门下子孙部门
     * @param type $deptId 部门Id
     * @return json 部门树列表
     */
    public function family($deptId)
    {
        return $this->returnResult($this->departmentService->tree($deptId));
    }
    /**
     * @获取某部门下的子部门
     * @param type $deptId
     * @return json 部门列表
     */
    public function children($deptId)
    {
        return $this->returnResult($this->departmentService->children($deptId, $this->request->all(), $this->own));
    }
    /**
     * @获取某部门下的子部门
     * @param type $deptId
     * @return json 部门列表
     */
    public function listDept()
    {
        return $this->returnResult($this->departmentService->listDept($this->request->all(), $this->own));
    }

    /**
     * 新建部门
     *
     * @apiTitle 新建部门
     * @param {string} dept_name 部门名称
     * @param {string} dept_sort 序号
     * @param {string} tel_no 部门电话
     * @param {string} fax_no 部门传真
     * @param {string} parent_id 父级部门ID
     * @param {array} director 部门负责人
     *
     * @paramExample {string} 参数示例
     * {
     *   dept_name: "测试部门名称",
     *   dept_sort: "1",
     *   director: ["WV00000001"],
     *   fax_no: "02166334455",
     *   parent_id: 63,
     *   tel_no: "02166334455"
     * }
     *
     * @success {boolean} status(1) 接入成功
     * @successExample {json} Success-Response:
     * {
     *      "status": 1,
     *      "data": {
     *          dept_arr_parent_id: "0,63" // 部门路径
     *          dept_id: 84 // 部门ID
     *          dept_path: ["63", "84"] // 部门路径数组
     *      }
     * }
     *
     * @error {boolean} status(0) 接入失败
     * @error {array} errors 接入失败错误信息
     *
     * @errorExample {json} Error-Response:
     * { "status": 0,"errors":[{"code":"0x000003","message":"未知错误"}] }
     */
    public function addDepartment()
    {
        return $this->returnResult($this->departmentService->addDepartment($this->request->all(), $this->own['user_id']));
    }

    /**
     * 编辑部门
     * @apiTitle 编辑部门
     * @param {int} dept_id 部门ID
     * @param {string} dept_name 部门名称
     * @param {string} dept_sort 序号
     * @param {string} tel_no 部门电话
     * @param {string} fax_no 部门传真
     * @param {string} parent_id 父级部门ID
     * @param {array} director 部门负责人
     *
     * @paramExample {string} 参数示例
     * {
     *   dept_name: "测试部门名称",
     *   dept_sort: "1",
     *   director: ["WV00000001"],
     *   fax_no: "02166334455",
     *   parent_id: 63,
     *   tel_no: "02166334455"
     * }
     *
     * @success {boolean} status(1) 接入成功
     * @successExample {json} Success-Response:
     * {
     *      "status": 1,
     *      "data": {
     *          dept_arr_parent_id: "0,63" // 部门路径
     *          dept_id: 84 // 部门ID
     *          dept_path: ["63", "84"] // 部门路径数组
     *      }
     * }
     *
     * @error {boolean} status(0) 接入失败
     * @error {array} errors 接入失败错误信息
     *
     * @errorExample {json} Error-Response:
     * { "status": 0,"errors":[{"code":"0x000003","message":"未知错误"}] }
     */
    public function editDepartment($deptId)
    {
        return $this->returnResult($this->departmentService->updateDepartment($this->request->all(), $deptId, $this->own['user_id']));
    }
    /**
     * 获取部门详情
     *
     * @apiTitle 获取部门详情
     * @param {int} deptId 部门id
     *
     * @paramExample {string} 参数示例
     * {
     *   api/system/department/1572
     * }
     *
     * @success {boolean} status(1) 接入成功
     * @successExample {json} Success-Response:
     * {
     *      "status": 1,
     *      "data": {
     *          dept_id: 1572
                dept_name: "运营中心"
                tel_no: ""
                fax_no: ""
                parent_id: 0
                dept_name_py: "yunyingzhongxin"
                dept_name_zm: "yyzx"
                arr_parent_id: "0"
                has_children: 1
                dept_sort: 0
                deleted_at: null
                created_at: "2020-09-11 12:08:35"
                updated_at: "2020-09-11 12:08:35"
                director: []
                dept_path: [1572]
                directors: []
     *      }
     * }
     *
     * @error {boolean} status(0) 接入失败
     * @error {array} errors 接入失败错误信息
     *
     * @errorExample {json} Error-Response:
     * { "status": 0,"errors":[{"code":"0x000003","message":"未知错误"}] }
     */
    public function getDeptDetail($deptId)
    {
        return $this->returnResult($this->departmentService->getDeptDetail($deptId)); //获取更新前的部门信息
    }

    /**
     * 删除部门
     *
     * @apiTitle 删除部门
     * @param {int} dept_id 部门ID
     *
     * @paramExample {string} 参数示例
     * api/dept/84
     *
     * @success {boolean} status(1) 接入成功
     * @successExample {json} Success-Response:
     * {
     *      "status": 1,
     *      "data": {
     *          arr_parent_id: "0,63"
     *          created_at: "2018-06-24 17:11:58"
     *          deleted_at: null
     *          dept_id: 84
     *          dept_name: "测试部门名称"
     *          dept_name_py: "ceshibumenmingcheng"
     *          dept_name_zm: "csbmmc"
     *          dept_parent_path: ["63"]
     *          dept_sort: 1
     *          director: ["WV00000001"]
     *          fax_no: "02166334455"
     *          has_children: 0
     *          parent_id: 63
     *          tel_no: "02166334455"
     *          updated_at: "2018-06-24 17:11:58"
     *      }
     * }
     *
     * @error {boolean} status(0) 接入失败
     * @error {array} errors 接入失败错误信息
     *
     * @errorExample {json} Error-Response:
     * { "status": 0,"errors":[{"code":"0x000003","message":"未知错误"}] }
     */
    public function deleteDepartment($deptId)
    {
        return $this->returnResult($this->departmentService->delete($deptId, $this->own['user_id']));
    }

    /**
     * 根据部门ID获取它的根部门信息
     *
     * @param {int} dept_id 部门ID
     *
     * @paramExample {string} 参数示例
     * api/dept/get-root-dept-info/84
     *
     * @success {boolean} status(1) 接入成功
     * @successExample {json} Success-Response:
     * {
     *      "status": 1,
     *      "data": {
     *          dept_id: 84,
     *          dept_name: "测试部门名称"
     *      }
     * }
     *
     * @error {boolean} status(0) 接入失败
     * @error {array} errors 接入失败错误信息
     *
     * @errorExample {json} Error-Response:
     * { "status": 0,"errors":[{"code":"0x000003","message":"未知错误"}] }
     */
    public function getRootDeptInfoByDeptId($deptId)
    {
        return $this->returnResult($this->departmentService->getRootDeptInfoByDeptId($deptId));
    }

    /**
     * 根据部门ID获取它的上级部门信息
     *
     * @param {int} dept_id 部门ID
     *
     * @paramExample {string} 参数示例
     * api/dept/get-parent-dept-info/84
     *
     * @success {boolean} status(1) 接入成功
     * @successExample {json} Success-Response:
     * {
     *      "status": 1,
     *      "data": {
     *          dept_id: 84,
     *          dept_name: "测试部门名称"
     *      }
     * }
     *
     * @error {boolean} status(0) 接入失败
     * @error {array} errors 接入失败错误信息
     *
     * @errorExample {json} Error-Response:
     * { "status": 0,"errors":[{"code":"0x000003","message":"未知错误"}] }
     */
    public function getParentDeptInfoByDeptId($deptId)
    {
        return $this->returnResult($this->departmentService->getParentDeptInfoByDeptId($deptId));
    }

    public function deptTreeSearch()
    {
        return $this->returnResult($this->departmentService->deptTreeSearch($this->request->all(), $this->own));
    }

    public function getDeptUserArr() {
        return $this->returnResult($this->departmentService->getDeptUserArr($this->request->all(), $this->own));
    }
    public function addMultipleDepartment() {
        return $this->returnResult($this->departmentService->addMultipleDepartment($this->request->all(), $this->own['user_id']));
    }
    public function getTotalDepartment()
    {
        return $this->returnResult($this->departmentService->getTotalDepartment());
    }
    public function setDeptPermission($deptId)
    {
        return $this->returnResult($this->departmentService->setDeptPermission($deptId, $this->request->all()));
    }
    public function getDeptPermission($deptId)
    {
        return $this->returnResult($this->departmentService->getDeptPermission($deptId, $this->request->all()));
    }
    public function clearDeptPermission()
    {
        return $this->returnResult($this->departmentService->clearDeptPermission());
    }
}
