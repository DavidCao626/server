<?php
namespace App\EofficeApp\User\Controllers;

use Illuminate\Http\Request;
use App\EofficeApp\Base\Controller;
use App\EofficeApp\User\Services\UserService;
use App\EofficeApp\User\Requests\UserRequest;
use Illuminate\Support\Facades\DB;

/**
 * 用户 controller ，实现用户管理菜单的所有内容，以及所有和用户模块相关的功能实现
 * 这个类，用来：1、验证request；2、组织数据；3、调用service实现功能；[4、组织返回值]
 *
 * @author 丁鹏
 *
 * @since  2015-10-16 创建
 */
class UserController extends Controller
{
    public function __construct(
        Request $request,
        UserService $userService,
        UserRequest $userRequest
    ) {
        parent::__construct();
        $this->userService = $userService;
        $this->userRequest = $userRequest;
        $this->formFilter($request, $userRequest);
        $this->request = $request;
    }

    /**
     * 用户状态管理--获取用户状态列表，不带查询
     *
     * @author 丁鹏
     *
     * @since  2015-10-16 创建此类
     *
     * @return json
     */
    public function getUserStatus() {
        $result = $this->userService->userStatusList($this->request->all());
        return $this->returnResult($result);
    }
    public function getUserStatusTotal() 
    {
        $result = $this->userService->getUserStatusTotal($this->request->all());
        return $this->returnResult($result);
    }

    /**
     * 用户状态管理--新建用户状态
     *
     * @author 丁鹏
     *
     * @since  2015-10-16 创建此类
     *
     * @return status_id
     */
    public function postUserStatusCreate() {
        $result = $this->userService->userStatusCreate($this->request->all());
        return $this->returnResult($result);
    }

    /**
     * 用户状态管理--编辑用户状态
     *
     * @author 丁鹏
     *
     * @since  2015-10-16 创建此类
     *
     * @return [type] [description]
     */
    public function postUserStatusEdit($statusId) {
        $input = $this->request->all();
        $result = $this->userService->userStatusEdit($input, $statusId);
        return $this->returnResult($result);
    }

    /**
     * 用户状态管理--删除用户状态
     *
     * @author 丁鹏
     *
     * @since  2015-10-16 创建此类
     *
     * @return [type] [description]
     */
    public function getUserStatusDelete($statusId) {
        $result = $this->userService->userStatusDelete($statusId);
        return $this->returnResult($result);
    }

    /**
     * 用户状态管理--获取用户状态详情
     *
     * @author 丁鹏
     *
     * @since  2015-10-16 创建此类
     *
     * @return [type] [description]
     */
    public function getUserStatusDetail($statusId) {
        $result = $this->userService->userStatusDetail(["status_id" => $statusId]);
        return $this->returnResult($result);
    }

    /**
     * 获取用户列表数据
     * @apiTitle 获取用户列表
     * @param {int} autoFixPage 起始页
     * @param {int} limit 每页显示数量
     * @param {json} order_by 排序
     * @param {int} page 页码
     * @param {json} search 查询条件
     *
     * @paramExample {string} 参数示例
     * {
     *  autoFixPage: 1
     *  limit: 10
     *  order_by: {"list_number":"desc"}
     *  page: 2
     *  search: {"user_name":["系统","like"],"phone_number":["123123","like"]}
     * }
     *
     * @success {boolean} status(1) 接入成功
     * @successExample {json} Success-Response:
     * {
     *      "status": 1,
     *      "data": {
     *          "total": 669, // 用户数量
     *          "list": [ // 用户列表
     *              {
     *                  "user_id": "admin", // 用户ID 非admin用户时WV开头的
     *                  "user_accounts": "admin", // 用户名
     *                  "user_name": "系统管理员", // 真实姓名
     *                  "user_name_py": "xitongguanliyuan",
     *                  "user_name_zm": "xtgly",
     *                  "user_job_number": "2058", // 工号
     *                  "list_number": null, // 序号
     *                  "user_position": 4, // 用户职位ID
     *                  "user_has_many_role": [
     *                      {
     *                          "user_id": "admin",
     *                          "role_id": 1,
     *                          "created_at": "2018-05-11 16:13:17",
     *                          "updated_at": "2018-05-11 16:13:17",
     *                          "deleted_at": null,
     *                          "has_one_role": {
     *                              "role_id": 1,
     *                              "role_name": "OA管理员", // 用户角色，如果有多个角色在user_has_many_role里面会有多个角色
     *                              "role_no": 0,
     *                              "role_name_py": "OAguanliyuan",
     *                              "role_name_zm": "OAgly",
     *                              "created_at": "2015-11-18 05:21:37",
     *                              "updated_at": "2017-03-05 15:35:54",
     *                              "deleted_at": null
     *                          }
     *                      }
     *                  ],
     *                  "user_has_one_info": {
     *                      "user_id": "admin",
     *                      "sex": "1", // 性别 1、男 0、女
     *                      "birthday": "0000-00-00", // 生日
     *                      "dept_phone_number": "", // 部门电话
     *                      "faxes": "", // 传真
     *                      "home_address": "", // 家庭住址
     *                      "home_zip_code": "", // 邮编
     *                      "home_phone_number": "", // 家庭电话
     *                      "phone_number": "", // 手机号码
     *                      "weixin": "", // 微信
     *                      "email": "", // 邮箱
     *                      "oicq_no": "", // QQ
     *                      "msn": "msn", // msn
     *                      "notes": "",
     *                      "theme": "theme7",
     *                      "sms_on": "0",
     *                      "menu_hide": "2",
     *                      "avatar_source": "",
     *                      "avatar_thumb": "",
     *                      "signature_picture": "",
     *                      "created_at": "2015-08-25 04:05:45",
     *                      "updated_at": "2018-05-29 15:53:02",
     *                      "deleted_at": null
     *                  },
     *                  "user_has_one_system_info": {
     *                      "user_id": "admin",
     *                      "dept_id": 2, // 用户所在部门ID
     *                      "max_role_no": 0,
     *                      "post_priv": "1",
     *                      "post_dept": "",
     *                      "duty_type": 1,
     *                      "last_login_time": "2018-06-15 14:13:14",
     *                      "last_pass_time": "2018-04-24 10:54:15",
     *                      "shortcut": "2,3,57,98,252,5,",
     *                      "sms_login": "0",
     *                      "wap_allow": 1,
     *                      "login_usbkey": "0",
     *                      "usbkey_pin": "",
     *                      "user_status": 1, // 用户状态，这里获取到的数据都是非离职用户
     *                      "is_autohrms": 1,
     *                      "created_at": "2015-08-25 04:05:45",
     *                      "updated_at": "2018-06-15 14:13:14",
     *                      "deleted_at": null,
     *                      "user_system_info_belongs_to_department": {
     *                          "dept_id": 2,
     *                          "dept_name": "研发部", // 用户所在部门名称
     *                          "tel_no": "",
     *                          "fax_no": "",
     *                          "parent_id": 59,
     *                          "dept_name_py": "yanfabu",
     *                          "dept_name_zm": "yfb",
     *                          "arr_parent_id": "0,59",
     *                          "has_children": 0,
     *                          "dept_sort": 1,
     *                          "deleted_at": null,
     *                          "created_at": "2015-11-10 11:22:22",
     *                          "updated_at": "2017-05-16 02:40:34"
     *                      }
     *                  },
     *                  "user_has_one_attendance_scheduling_info": {
     *                      "scheduling_id": 1,
     *                      "user_id": "admin"
     *                  },
     *                  "user_position_name": "人事行政主管" // 用户职位名称
     *              },
     *          ...
     *      }
     * }
     *
     * @error {boolean} status(0) 接入失败
     * @error {array} errors 接入失败错误信息
     *
     * @errorExample {json} Error-Response:
     * { "status": 0,"errors":[{"code":"0x000003","message":"未知错误"}] }
     */
    public function getUserSystemList() {
        $result = $this->userService->userSystemList($this->request->all(), $this->own);
        return $this->returnResult($result);
    }
    
    public function getAddressBookUsers() {
        $result = $this->userService->getAddressBookUsers($this->request->all(), $this->own);
        return $this->returnResult($result);
    }
    public function getMyDepartmentUsers()
    {
        $result = $this->userService->getMyDepartmentUsers($this->own['dept_id'], $this->request->all(), $this->own);
        return $this->returnResult($result);
    }
    /**
     * 用户管理--获取用户列表数据(仅用于用户管理处调用)
     *
     * @author 丁鹏
     *
     * @since  2015-10-16 创建此类
     *
     * @return array 用户主表的信息数组
     */
    public function getUserManageList() {
        $result = $this->userService->userManageSystemList($this->own, $this->request->all());
        return $this->returnResult($result);
    }

    /**
     * 用户管理--获取用户所有信息
     *
     * @author 丁鹏
     *
     * @since  2015-10-16 创建此类
     *
     * @return array 用户所有信息数据，子数组: usersysteminfo 里的是用户系统信息， userinfo 里的是基础信息
     */
    public function getUserAllData($userId)
    {
        $result = $this->userService->getUserAllData($userId, $this->own, $this->request->all());
        return $this->returnResult($result);
    }

    /**
     * 新建用戶
     *
     * @apiTitle 新建用戶
     * @param {string} user_accounts 用户名
     * @param {string} user_name 真实姓名
     * @param {string} phone_number 手机号码
     * @param {string} user_password 密码
     * @param {string} user_job_number 工号
     * @param {int} sex 性别
     * @param {int} user_status 用户状态
     * @param {int} attendance_scheduling 考勤排班类型
     * @param {int} is_autohrms 同步人事档案
     * @param {string} list_number 序号
     * @param {int} dept_id 部门
     * @param {string} role_id_init 角色
     * @param {int} wap_allow 是否允许手机访问
     * @param {int} post_priv 管理范围
     * @param {string} post_dept 管理范围指定部门
     * @param {date} birthday 生日
     * @param {string} dept_phone_number 部门电话
     * @param {string} faxes 部门传真
     * @param {string} email 邮箱
     * @param {string} home_address 家庭住址
     * @param {string} home_phone_number 家庭电话
     * @param {string} home_zip_code 家庭邮编
     * @param {string} oicq_no QQ
     * @param {string} weixin 微信
     *
     * @paramExample {string} 参数示例
     * {
     *  user_accounts: "aaa",
     *  user_name: "aaa",
     *  phone_number: "18711111111",
     *  user_password: "123456",
     *  user_job_number: "100001",
     *  sex: 1,
     *  user_status: 1,
     *  attendance_scheduling: 1,
     *  is_autohrms: 1,
     *  list_number: "001",
     *  dept_id: 59,
     *  role_id_init: "1,2",
     *  wap_allow: 1,
     *  post_priv: 0,
     *  post_dept: "59,61",
     *  birthday: "2018-06-01",
     *  dept_phone_number: "02166335544",
     *  faxes: "02166335544",
     *  email: "731210011@qq.com",
     *  home_address: "上海市xxxxxx",
     *  home_phone_number: "02166334445",
     *  home_zip_code: "002233",
     *  oicq_no: "731210011",
     *  weixin: "731210011"
     * }
     *
     * @success {boolean} status(1) 接入成功
     * @successExample {json} Success-Response:
     * {
     *      "status": 1,
     *      "data": {
     *          created_at: "2018-06-24 16:35:02",
     *          list_number: 428,
     *          password: "$1$6MowpIHK$9v4M1au3Aq98plAqIt5bq1",
     *          updated_at: "2018-06-24 16:35:02",
     *          user_accounts: "aaa",
     *          user_id: "WV00000693",
     *          user_job_number: "1000101",
     *          user_name: "aaa",
     *          user_name_py: "aaa",
     *          user_name_zm: "aaa",
     *          user_position: 1
     *      }
     * }
     *
     * @error {boolean} status(0) 接入失败
     * @error {array} errors 接入失败错误信息
     *
     * @errorExample {json} Error-Response:
     * { "status": 0,"errors":[{"code":"0x000003","message":"未知错误"}] }
     */
    public function userSystemCreate()
    {
        $result = $this->userService->userSystemCreate($this->request->all(), $this->own);
        return $this->returnResult($result);
    }
    // 批量新建用户
    public function mutipleUserSystemCreate()
    {
        $result = $this->userService->mutipleUserSystemCreate($this->request->all(), $this->own);
        if (isset($result['errors'])) {
            return $result;
        } else {
            return $this->returnResult($result);
        }
    }
    public function addDeptUser()
    {
        $result = $this->userService->addDeptUser($this->request->all(), $this->own);
        return $this->returnResult($result);
    }
    public function editDeptUser($userId)
    {
        $result = $this->userService->editDeptUser($userId, $this->request->all(), $this->own);
        return $this->returnResult($result);
    }

    /**
     * 编辑用戶
     *
     * @apiTitle 编辑用戶
     * @param {string} user_accounts 用户名
     * @param {string} user_name 真实姓名
     * @param {string} phone_number 手机号码
     * @param {string} user_password 密码
     * @param {string} user_job_number 工号
     * @param {int} sex 性别
     * @param {int} user_status 用户状态
     * @param {int} attendance_scheduling 考勤排班类型
     * @param {int} is_autohrms 同步人事档案
     * @param {string} list_number 序号
     * @param {int} dept_id 部门
     * @param {string} role_id_init 角色
     * @param {int} wap_allow 是否允许手机访问
     * @param {int} post_priv 管理范围
     * @param {string} post_dept 管理范围指定部门
     * @param {date} birthday 生日
     * @param {string} dept_phone_number 部门电话
     * @param {string} faxes 部门传真
     * @param {string} email 邮箱
     * @param {string} home_address 家庭住址
     * @param {string} home_phone_number 家庭电话
     * @param {string} home_zip_code 家庭邮编
     * @param {string} oicq_no QQ
     * @param {string} weixin 微信
     *
     * @paramExample {string} 参数示例
     * {
     *  user_accounts: "aaa",
     *  user_name: "aaa",
     *  phone_number: "18711111111",
     *  user_password: "123456",
     *  user_job_number: "100001",
     *  sex: 1,
     *  user_status: 1,
     *  attendance_scheduling: 1,
     *  is_autohrms: 1,
     *  list_number: "001",
     *  dept_id: 59,
     *  role_id_init: "1,2",
     *  wap_allow: 1,
     *  post_priv: 0,
     *  post_dept: "59,61",
     *  birthday: "2018-06-01",
     *  dept_phone_number: "02166335544",
     *  faxes: "02166335544",
     *  email: "731210011@qq.com",
     *  home_address: "上海市xxxxxx",
     *  home_phone_number: "02166334445",
     *  home_zip_code: "002233",
     *  oicq_no: "731210011",
     *  weixin: "731210011"
     * }
     *
     * @success {boolean} status(1) 接入成功
     * @successExample {json} Success-Response:
     * {
     *      "status": 1,
     *      "data": {
     *          created_at: "2018-06-24 16:35:02",
     *          list_number: 428,
     *          password: "$1$6MowpIHK$9v4M1au3Aq98plAqIt5bq1",
     *          updated_at: "2018-06-24 16:35:02",
     *          user_accounts: "aaa",
     *          user_id: "WV00000693",
     *          user_job_number: "1000101",
     *          user_name: "aaa",
     *          user_name_py: "aaa",
     *          user_name_zm: "aaa",
     *          user_position: 1
     *      }
     * }
     *
     * @error {boolean} status(0) 接入失败
     * @error {array} errors 接入失败错误信息
     *
     * @errorExample {json} Error-Response:
     * { "status": 0,"errors":[{"code":"0x000003","message":"未知错误"}] }
     */
    public function userSystemEdit()
    {
        $data   = $this->request->all();
        $result = $this->userService->userSystemEdit($data, $this->own);
        return $this->returnResult($result);
    }

    /**
     * 删除用戶
     *
     * @apiTitle 删除用戶
     * @param {string} user_id 用户ID
     *
     * @paramExample {string} 参数示例
     * api/user/WV00000001
     *
     * @success {boolean} status(1) 接入成功
     * @successExample {json} Success-Response:
     * {
     *      "status": 1,
     *      "data": {
     *          user: 1
     *          user_info: 1
     *          user_menu: 1
     *          user_role: 1
     *          user_superior: 0
     *          user_system_info: 1
     *      }
     * }
     *
     * @error {boolean} status(0) 接入失败
     * @error {array} errors 接入失败错误信息
     *
     * @errorExample {json} Error-Response:
     * { "status": 0,"errors":[{"code":"0x000003","message":"未知错误"}] }
     */
    public function userSystemDelete($userId)
    {
        $result = $this->userService->userSystemDelete($userId, $this->own['user_id']);
        return $this->returnResult($result);
    }

    /**
     * 用户管理--清空密码
     *
     * @author 丁鹏
     *
     * @since  2015-10-16 创建此类
     *
     * @return [type]        [description]
     */
    public function userSystemEmptyPassword($userId)
    {
        $result = $this->userService->userSystemEmptyPassword(["user_id" => $userId], $this->own['user_id']);
        return $this->returnResult($result);
    }

    /**
     * 开放的一个可以根据用户id字符串获取用户列表的函数，可以传参getDataType获取在职离职已删除用户
     *
     * @method getUserListByUserIdString
     *
     * @return [type]                    [description]
     */
    public function getUserListByUserIdString()
    {
        $result = $this->userService->getUserListByUserIdString($this->request->all());
        return $this->returnResult($result);
    }

    /**
     * 获取离职用户列表
     *
     * @method getLeaveOfficeUser
     *
     * @return [type]                    [description]
     */
    public function getLeaveOfficeUser()
    {
        $result = $this->userService->getLeaveOfficeUser($this->request->all(), $this->own);
        return $this->returnResult($result);
    }

    /**
     * 通过部门id获取所有本部门包括子部门的人员列表
     *
     * @method getUserListByDeptId
     *
     * @return [type]                    [description]
     */
    public function getUserListByDeptId($deptId)
    {
        $result = $this->userService->getUserListByDeptId($deptId, $this->request->all(), $this->own);
        return $this->returnResult($result);
    }

    /**
     * 根据用户id获取用户所有上级
     *
     * @author miaochenchen
     *
     * @since  2016-07-26 创建此类
     *
     * @return [type]        [description]
     */
    public function getSuperiorArrayByUserId($userId)
    {
        $result = $this->userService->getSuperiorArrayByUserId($userId, $this->request->all());
        return $this->returnResult($result);
    }

    public function getSuperiorArrayByUserIdArr()
    {
        $result = $this->userService->getSuperiorArrayByUserIdArr($this->request->all());
        return $this->returnResult($result);
    }
    public function getSubordinateArrayByUserIdArr()
    {
        $result = $this->userService->getSubordinateArrayByUserIdArr($this->request->all());
        return $this->returnResult($result);
    }

    /**
     * 根据用户id获取用户所有下级
     *
     * @author miaochenchen
     *
     * @since  2016-07-26 创建此类
     *
     * @return [type]        [description]
     */
    public function getSubordinateArrayByUserId($userId)
    {
        $result = $this->userService->getSubordinateArrayByUserId($userId, $this->request->all(), $this->own);
        return $this->returnResult($result);
    }
    public function getMyAllSubordinate() 
    {
        $result = $this->userService->getMyAllSubordinate($this->own['user_id'], $this->request->all(), $this->own);
        return $this->returnResult($result);
    }
    /**
     * 根据用户id获取用户角色级别信息
     *
     * @author miaochenchen
     *
     * @since  2016-09-06
     *
     * @return [type]        [description]
     */
    public function getUserRoleLeavel($userId)
    {
        $result = $this->userService->getUserRoleLeavel($userId);
        return $this->returnResult($result);
    }

    /**
     * 根据用户id获取用户部门完整路径
     *
     * @author miaochenchen
     *
     * @since  2016-09-06
     *
     * @return [type]        [description]
     */
    public function getUserDeptPath($userId)
    {
        $result = $this->userService->getUserDeptPath($userId);
        return $this->returnResult($result);
    }

    /**
     * 根据用户id获取用户当前部门负责人
     *
     * @author miaochenchen
     *
     * @since  2016-09-06
     *
     * @return [type]        [description]
     */
    public function getUserOwnDeptDirector($userId)
    {
        $result = $this->userService->getUserOwnDeptDirector($userId);
        return $this->returnResult($result);
    }

    /**
     * 根据用户id获取用户上级部门的负责人
     *
     * @author miaochenchen
     *
     * @since  2016-09-06
     *
     * @return [type]        [description]
     */
    public function getUserSuperiorDeptDirector($userId)
    {
        $result = $this->userService->getUserSuperiorDeptDirector($userId);
        return $this->returnResult($result);
    }

    /**
     * 获取用户授权信息
     *
     * @author miaochenchen
     *
     * @since  2016-09-09
     *
     * @return [type]        [description]
     */
    public function getUserAuthorizationInfo() {
        $result = $this->userService->getUserAuthorizationInfo();
        return $this->returnResult($result);
    }

    /**
     * 获取所有用户ID集合
     *
     * @author 缪晨晨
     *
     * @param  $param array $param['includeLeave']是否包含离职，默认不包含；$param['returnType']返回类型，默认返回string类型
     *
     * @since  2016-10-10 创建
     *
     * @return string or array  返回所有用户ID集合
     */
    public function getAllUserIdString() {
        $result = $this->userService->getAllUserIdString($this->request->all());
        return $this->returnResult($result);
    }

    /**
     * 获取手机用户列表
     *
     * @author 缪晨晨
     *
     * @param
     *
     * @since  2017-06-02 创建
     *
     * @return array  返回手机用户列表和总数
     */
    public function getMobileUserList() {
        $result = $this->userService->getMobileUserList();
        return $this->returnResult($result);
    }

    /**
     * 设置手机用户
     *
     * @author 缪晨晨
     *
     * @param
     *
     * @since  2017-06-02 创建
     *
     * @return boolean
     */
    public function setMobileUser() {
        $result = $this->userService->setMobileUser($this->request->all());
        return $this->returnResult($result);
    }

    public function setMobileUserById($userId) {
        $result = $this->userService->setMobileUserById($userId, $this->request->all());
        return $this->returnResult($result);
    }

    /**
     * 检查是否存在离职用户
     *
     * @author 缪晨晨
     *
     * @param
     *
     * @since  2017-06-09 创建
     *
     * @return string
     */
    public function checkExistleaveOffUser() {
        $result = $this->userService->checkExistleaveOffUser();
        return $this->returnResult($result);
    }

    /**
     * 表单系统数据获取用户相关数据
     *
     */
    public function getUserDataForFormDatasource($useraccounts)
    {
        $result = $this->userService->getUserDataForFormDatasource($useraccounts, $this->request->all());
        return $this->returnResult($result);
    }

    public function unlockUserAccount($userId)
    {
        $result = $this->userService->unlockUserAccount($userId);
        return $this->returnResult($result);
    }
    public function leaveUserAccount($userId)
    {
        $result = $this->userService->leaveUserAccount($userId, $this->request->all(), $this->own);
        return $this->returnResult($result);
    }

    /**
     * 获取用户工号自动生成规则
     *
     * @author 缪晨晨
     *
     * @param
     *
     * @since  2018-10-09 创建
     *
     * @return array
     */
    public function getUserJobNumberRule()
    {
        $result = $this->userService->getUserJobNumberRule();
        return $this->returnResult($result);
    }

    /**
     * 获取用户其他设置
     *
     * @author 缪晨晨
     *
     * @param
     *
     * @since  2018-10-09 创建
     *
     * @return array
     */
    public function getUserOtherSettings()
    {
        $result = $this->userService->getUserOtherSettings();
        return $this->returnResult($result);
    }

    /**
     * 编辑用户其他设置
     *
     * @author 缪晨晨
     *
     * @param
     *
     * @since  2018-10-09 创建
     *
     * @return array
     */
    public function editUserOtherSettings()
    {
        $result = $this->userService->editUserOtherSettings($this->request->all());
        return $this->returnResult($result);
    }

    /**
     * 获取用户群组信息
     * @return [array] [description]
     */
    public function getChatGroupInfo()
    {
        $result = $this->userService->getChatGroupInfo($this->request->all(), $this->own);
        return $this->returnResult($result);
    }

    /**
     * 获取用户群组信息
     * @return [array] [description]
     */
    public function getAllUserFields()
    {
        $result = $this->userService->getAllUserFields($this->own);
        return $this->returnResult($result);
    }
    // 快速注册二维码
    public function getUserRegisterQrcode() {
        $result = $this->userService->getUserRegisterQrcode();
        return $this->returnResult($result);
    }
    // 下载快速注册二维码
    public function downloadQrcode() {
        $result = $this->userService->downloadQrcode($this->own);
        return $this->returnResult($result);
    }
    // 快速注册二维码有效期检测
    public function checkRegisterQrcode($sign) {
        $result = $this->userService->checkRegisterQrcode($sign);
        return $this->returnResult($result);
    }
    // 快速注册用户列表
    public function getRegisterUser() {
        $result = $this->userService->getRegisterUser($this->request->all());
        return $this->returnResult($result);
    }
    // 快速注册用户列表
    public function getRegisterUserPageData() {
        $result = $this->userService->getRegisterUserPageData($this->request->all());
        return $this->returnResult($result);
    }
    // 用户信息录入
    public function userShareRegister() {
        $result = $this->userService->userShareRegister($this->request->all());
        return $this->returnResult($result);
    }
    public function getUserSystemInfoNumber() {
        $result = $this->userService->getUserSystemInfoNumber($this->own);
        return $this->returnResult($result);
    }
    // 用户审核同意
    public function userRegisterCheck($id, $type) {
        $result = $this->userService->userRegisterCheck($id, $type, $this->own);
        return $this->returnResult($result);
    }
    // 批量用户审核
    public function batchCheckRegisterUser($type) {
        $result = $this->userService->batchCheckRegisterUser($type, $this->request->all(), $this->own);
        return $this->returnResult($result);
    }
    // 设置部门后审核用户
    public function setDeptAndCheckUser() {
        $result = $this->userService->setDeptAndCheckUser($this->request->all(), $this->own);
        return $this->returnResult($result);
    }
    public function getRecentFourYear() {
        $result = $this->userService->getRecentFourYear();
        return $this->returnResult($result);
    }
    public function getrecentUserStatusTotal() {
        $result = $this->userService->getrecentUserStatusTotal($this->request->all());
        return $this->returnResult($result);
    }
    public function getUserSocket() {
        return $this->returnResult($this->userService->getUserSocket($this->request->all()));
    }
    public function putUserSocket()
    {
        return $this->returnResult($this->userService->putUserSocket($this->request->all()));
    }
    public function multiRemoveDept()
    {
        return $this->returnResult($this->userService->multiRemoveDept($this->request->all()));
    }
    public function multiSetRole()
    {
        return $this->returnResult($this->userService->multiSetRole($this->request->all()));
    }
    public function multipleSyncPersonnelFiles()
    {
        return $this->returnResult($this->userService->multipleSyncPersonnelFiles($this->request->all()));
    }
}