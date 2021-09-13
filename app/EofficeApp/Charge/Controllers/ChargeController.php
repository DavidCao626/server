<?php
namespace App\EofficeApp\Charge\Controllers;

use App\EofficeApp\Base\Controller;
use App\EofficeApp\Charge\Requests\ChargeRequest;
use App\EofficeApp\Charge\Services\ChargeService;
use App\EofficeApp\Charge\Services\ChargeSettingService;
use Illuminate\Http\Request;

/**
 * 费用控制
 *
 * @author: 喻威
 *
 * @since：2015-10-19
 *
 */
class ChargeController extends Controller
{

    public function __construct(
        Request $request, 
        ChargeRequest $chargeRequest,
        ChargeService $chargeService,
        ChargeSettingService $chargeSettingService
    ) {
        parent::__construct();
        $this->chargeRequest = $request;
        $this->chargeService = $chargeService;
        $this->chargeSettingService = $chargeSettingService;
        $this->formFilter($request, $chargeRequest);
    }

    //*** 费用类型增加 ***
    public function addChargeType()
    {
        $result = $this->chargeService->addChargeType($this->chargeRequest->all());
        return $this->returnResult($result);
    }

    /**
     * 费用类型详细 sub 某个类别 main 含子类别的所有集合
     */
    public function getChargeTypeById($chargeTypeId)
    {
        $result = $this->chargeService->getChargeTypeById($chargeTypeId, $this->chargeRequest->all());
        return $this->returnResult($result);
    }

    /**
     * 预警设置
     *
     * @apiTitle 预警设置
     * @param {int} alert_data_end 预警开始时间
     * @param {int} alert_data_start 预警开始时间
     * @param {int} alert_method 预警方式，年-year，季度-quarter，月-month，自定义周期-custom
     * @param {int} alert_value 预警金额
     * @param {int} dept_id 预警部门id
     * @param {int} hide_subject 是否隐藏无预警值科目
     * @param {int} mode 操作类型，新增-“add”，编辑-“edit”
     * @param {int} project_id 预警项目id
     * @param {int} role_id 预警角色id
     * @param {int} set_type 预警对象类型，公司-1，部门-2，用户-3，角色-4，项目-5
     * @param {int} subject_check 预警金额类型，1-各科目值，0-总值
     * @param {int} subject_values 各科目值
     * @param {int} user_id 预警用户id
     * @return array
     *
     * @paramExample {string} 参数示例
     * {
     *     "alert_data_end": "0000-00-00",
     *     "alert_data_start": "0000-00-00",
     *     "alert_method": "year",
     *     "alert_value": "0",
     *     "charge_setting_id": 2,
     *     "created_at": "2020-09-17 17:47:56",
     *     "deleted_at": null,
     *     "dept_id": 0,
     *     "hide_subject": 0,
     *     "mode": "edit",
     *     "project_id": null,
     *     "set_type": 1,
     *     "subject_check": 0,
     *     "subject_values": "",
     *     "updated_at": "2020-09-17 17:53:33",
     *     "user_id": ""
     * }
     * 
     * @success {boolean} status(1) 接入成功
     * @successExample {json} Success-Response:
     * "status": 1,
     * "data": {
     *      charge_setting_id: 2 // 预警id
     * }
     * 
     * @error {boolean} status(0) 接入失败
     * @error {array} errors 接入失败错误信息
     *
     * @errorExample {json} Error-Response:
     * { "status": 0,"errors":[{"code":"0x000003","message":"未知错误"}] }
     */
    public function chargeSet()
    {

        $result = $this->chargeService->chargeSet($this->chargeRequest->all());

        return $this->returnResult($result);
    }

    public function getChargeTypeListByParentId($parentId)
    {
        $result = $this->chargeService->getChargeTypeListByParentId($parentId, $this->chargeRequest->all());
        return $this->returnResult($result);
    }
    /**
     * 新建、编辑费用类型
     *
     * @apiTitle 新建、编辑费用类型
     * @param {int} id 科目id
     * @param {string} name 科目名称
     * @param {json} sub_types 下级科目
     * @return array
     *
     * @paramExample {string} 参数示例
     * {
     *     "id": "0",
     *     "name": "所有分类",
     *     "sub_types": [ // 下级科目
     *          {
     *              "charge_type_id": 1 // 科目id
     *              "charge_type_name": "1" // 科目名称
     *              "charge_type_order": 0 // 科目序号
     *              "has_charge": 1 // 科目是否有录入费用
     *          }
     *      ]
     * }
     * 
     * @success {boolean} status(1) 接入成功
     * @successExample {json} Success-Response:
     * "status": 1,
     * 
     * @error {boolean} status(0) 接入失败
     * @error {array} errors 接入失败错误信息
     *
     * @errorExample {json} Error-Response:
     * { "status": 0,"errors":[{"code":"0x000003","message":"未知错误"}] }
     */
    public function editChargeType()
    {
        $result = $this->chargeService->editChargeType($this->chargeRequest->all());
        return $this->returnResult($result);
    }

    /**
     * 费用录入
     *
     * @apiTitle 费用录入
     * @param {string} charge_cost 费用金额
     * @param {int} charge_type_id 科目id
     * @param {int} charge_undertaker 费用承担者，1-用户，2-部门，3-公司，4-项目
     * @param {string} creator 创建人id
     * @param {string} payment_date 报销日期
     * @param {int} project_id 承担项目id，非项目填null
     * @param {string} reason 报销事由
     * @param {int} undertake_dept 承担部门id
     * @param {string} undertake_user 承担用户id
     * @param {string} user_id 报销人id
     * @return array
     *
     * @paramExample {string} 参数示例
     * {
     *     "charge_cost": "10"
     *     "charge_type_id": 3
     *     "charge_undertaker": 1
     *     "creator": "admin"
     *     "payment_date": "2020-09-17"
     *     "project_id": null
     *     "reason": "<p>1</p>"
     *     "undertake_dept": 2
     *     "undertake_user": "admin"
     *     "user_id": "admin"
     * }
     * 
     * @success {boolean} status(1) 接入成功
     * @successExample {json} Success-Response:
     * "status": 1,
     * "data": {
     *     "charge_list_id": "4", // 主键id
     *     "charge_cost": "10",
     *     "charge_type_id": 3,
     *     "charge_undertaker": 1,
     *     "creator": "admin",
     *     "payment_date": "2020-09-17",
     *     "project_id": null,
     *     "reason": "<p>1</p>",
     *     "undertake_dept": 2,
     *     "undertake_user": "admin",
     *     "user_id": "admin",
     * }
     * @error {boolean} status(0) 接入失败
     * @error {array} errors 接入失败错误信息
     *
     * @errorExample {json} Error-Response:
     * { "status": 0,"errors":[{"code":"0x000003","message":"未知错误"}] }
     */
    public function addNewCharge()
    {

        $result = $this->chargeService->addNewCharge($this->chargeRequest->all());
        return $this->returnResult($result);
    }

    /**
     * 根据用户获取对应的预警值 和 部分预警值
     */

    public function getChargeSetByUserId($userId)
    {
        $result = $this->chargeService->getChargeSetByUserId($userId);
        return $this->returnResult($result);
    }

    /**
     *
     * 获取顶级部门的名称和费用值
     */
    public function getChargeTreeTotal()
    {
        $result = $this->chargeService->getChargeTreeTotal($this->own);
        return $this->returnResult($result);
    }

    /**
     * 图表总体
     */

    public function chargeCharts()
    {
        $result = $this->chargeService->chargeCharts($this->chargeRequest->all(), $this->own);
        return $this->returnResult($result);
    }

    /**
     * 图表详细
     */

    /**
     *
     * 删除费用类型
     *
     * @return bool
     *
     */
    public function deleteChargeType($chargeTypeId)
    {
        $result = $this->chargeService->deleteChargeType($chargeTypeId);
        return $this->returnResult($result);
    }

    /**
     * 获取所有的费用类型
     *
     * @return array
     */
    public function chargeTypeList()
    {
        $result = $this->chargeService->chargeTypeList($this->chargeRequest->all());
        return $this->returnResult($result);
    }
    public function chargeTypeLists()
    {
        $result = $this->chargeService->chargeTypeLists($this->chargeRequest->all());
        return $this->returnResult($result);
    }

    /**
     * 查看费用详情
     */

    public function getNewCharge($chargeListId)
    {
        $result = $this->chargeService->getNewCharge($chargeListId, $this->chargeRequest->all(), $this->own);
        return $this->returnResult($result);
    }

    /**
     * 编辑录入的费用
     *
     * @return bool
     */
    public function editNewCharge($chargeListId)
    {
        $result = $this->chargeService->editNewCharge($chargeListId, $this->chargeRequest->all(), $this->own);
        return $this->returnResult($result);
    }

    /**
     *
     * 删除录入的费用记录
     *
     * @return bool
     *
     * @author 喻威
     *
     * @since 2015-10-20
     */
    public function deleteNewCharge($chargeListId)
    {
        $result = $this->chargeService->deleteNewCharge($chargeListId, $this->own);
        return $this->returnResult($result);
    }

    /**
     * 获取费用设置详细
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-20
     *
     */
    public function getOneChargeSetting($setId)
    {

        $result = $this->chargeService->getOneChargeSetting($setId);
        return $this->returnResult($result);
    }

    /**
     * 费用用户|部门总和
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-20
     */
    public function chargeListData()
    {

        $result = $this->chargeService->chargeListData($this->chargeRequest->all(), $this->own);
        return $this->returnResult($result);
    }

    /**
     * 我的费用、费用清单列表
     *
     * @apiTitle 费用清单列表
     * @param {int} autoFixPage 起始页
     * @param {int} limit 每页显示数量
     * @param {json} order_by 排序
     * @param {int} page 页码
     * @param {json} search 查询条件
     * @param {json} set_id 查询对象（部门id\用户id\项目id，可为空，我的费用传当前用户id）
     * @param {int} set_type 查询对象类型（公司-1\部门-2\个人-3\项目-5, 可为空）
     * @param {int} filter_type 筛选类型（按报销人筛选-1\按承担者筛选-2）
     * @param {string} filter 时间筛选类型（年-'year'\季度-'quarter'\月-'month'）
     * @param {string} flag 接口区分标识（我的费用-'my'\费用清单-'list'）
     * @param {int} year 年份
     * @param {int} month 月份（时间筛选类型为月时必填）
     * @param {int} has_depts 是否包含子部门（包含-1\不包含-0，非必填）
     * @param {int} power 是否有项目模块权限（是-1，否-0）
     * @param {int} charge_type 科目（是-1，否-0）
     * @param {string} charge_filter 查询时间筛选（默认值'T'）
     * @return array
     *
     * @paramExample {string} 参数示例
     * {
     *     limit: 10
     *     page: 1
     *     autoFixPage: 1
     *     set_id: []
     *     filter_type: 1
     *     set_type: 0
     *     filter: year
     *     year: 2020
     *     month: 1
     *     charge_type: 3
     *     charge_fiter: M
     *     search: {
     *          "charge_type.charge_type_id":[1,"="], // 查询科目
     *          "reason":["111","like"], // 查询事由
     *          "payment_date":[["2020-09-24","2020-09-26"],"between"],  // 查询日期区间
     *          "charge_list.user_id":[["admin"],"in"] // 查询报销人
     *     }
     *     has_depts: 
     *     power: 1
     *     order_by: {"payment_date":"desc"}
     * }
     * 
     * @success {boolean} status(1) 接入成功
     * @successExample {json} Success-Response:
     * "status": 1,
     * "data": {
     *      "list": [ //查询结果列表
     *          { 
     *               "charge_list_id": 1, // 主键
     *               "charge_cost": "10", //费用金额
     *               "charge_extra": null, // 费用外发流程参数
     *               "charge_form": 1, // 费用来源，1-模块录入，2-外发
     *               "charge_type_id": 3, //费用类型
     *               "charge_type_name": "1-1", // 科目名称
     *               "charge_undertaker": "1", // 费用承担者，1-用户，2-部门，3-公司，4-项目
     *               "create_date": "0000-00-00 00:00:00", // 创建日期
     *               "created_at": "2020-08-18 12:04:27", // 创建时间
     *               "creator": "admin", // 创建人
     *               "deleted_at": null, // 删除时间
     *               "dept_name": "e-office研发部", // 承担部门名称
     *               "payment_date": "2020-08-18", // 报销日期
     *               "project_id": null, // 承担项目id
     *               "purview": 1, // 查看权限
     *               "reason": "<p>111</p>", // 报销事由
     *               "undertake_dept": 2, // 承担部门id
     *               "undertake_user": "admin", // 承担用户id
     *               "undertake_user_name": "系统管理员", // 承担用户名称
     *               "updated_at": "2020-08-18 12:04:27", // 更新时间
     *               "user_id": "admin", // 报销人id
     *               "user_name": "系统管理员", // 报销人
     *               "user_status": 1, // 报销人用户状态
     *          }
     *      ],
     *      "total": 1 //查询结果数量
     * }
     * @error {boolean} status(0) 接入失败
     * @error {array} errors 接入失败错误信息
     *
     * @errorExample {json} Error-Response:
     * { "status": 0,"errors":[{"code":"0x000003","message":"未知错误"}] }
     */
    public function chargeListDetail()
    {

        $result = $this->chargeService->chargeListDetail($this->chargeRequest->all(), $this->own);
        return $this->returnResult($result);
    }

    /**
     * 列表树 -- 含统计值
     */

    public function chargeListTree($parentId)
    {
        $result = $this->chargeService->chargeListTree($parentId, $this->chargeRequest->all(), $this->own);
        return $this->returnResult($result);
    }

     /**
     * 费用统计
     *
     * @apiTitle 费用统计
     * @param {int} autoFixPage 起始页
     * @param {int} limit 每页显示数量
     * @param {int} page 页码
     * @param {string} type 统计类型（部门-"dept"，用户-"user"，公司-"company"，项目-"project"）
     * @param {json} search 查询条件
     * @return array
     *
     * @paramExample {string} 参数示例
     * {
     *     "autoFixPage": 1,
     *     "limit": 10,
     *     "page": 1,
     *     "type": "dept",
     *     "search": {"department.dept_id":[2],"set_type":[2]} // 查询部门时
     *     "search": {"user.user_id":["admin"],"set_type":[3]} // 查询用户时
     *     "search": {"manager_id":[1]} // 查询项目时
     * }
     * 
     * @success {boolean} status(1) 接入成功
     * @successExample {json} Success-Response:
     * "status": 1,
     * "data": {
     *      "list": [ //查询结果列表
     *          { 
     *               "charge_setting_id": 1, // 预警id
     *               "date": "2020年", // 预警期
     *               "id": "admin", // 预警对象id
     *               "method": "年", // 预警方式
     *               "name": "系统管理员", // 预警对象
     *               "noundertake": "260.00", // 剩余报销
     *               "subject_check": 1, // 是否设置科目预警，1-各科目值，0-总值
     *               "total": 40, // 总计报销
     *               "type": "user", // 统计类型
     *               "undertake": 40, // 已报销
     *               "value": "300", // 预警值
     *          }
     *      ],
     *      "total": 1 //查询结果数量
     * }
     * @error {boolean} status(0) 接入失败
     * @error {array} errors 接入失败错误信息
     *
     * @errorExample {json} Error-Response:
     * { "status": 0,"errors":[{"code":"0x000003","message":"未知错误"}] }
     */
    public function chargeStatistics()
    {
        $result = $this->chargeService->chargeStatistics($this->chargeRequest->all(), $this->own);
        return $this->returnResult($result);
    }

    //费用统计 明细列表
    public function chargeDetails()
    {
        $result = $this->chargeService->chargeDetails($this->chargeRequest->all(), $this->own);
        return $this->returnResult($result);
    }

    //获取数据源
    public function chargeDataSource($userId)
    {
        $result = $this->chargeService->chargeDataSource($userId, $this->chargeRequest->all());
        return $this->returnResult($result);
    }

    public function chargeDataSourceByDeptId($deptId)
    {
        $result = $this->chargeService->chargeDataSourceByDeptId($deptId, $this->chargeRequest->all());
        return $this->returnResult($result);
    }

    public function chargeDataSourceByCompany()
    {
        $result = $this->chargeService->chargeDataSourceByCompany();
        return $this->returnResult($result);
    }

    public function chargeDataSourceByChargeName()
    {
        $result = $this->chargeService->chargeDataSourceByChargeName($this->chargeRequest->all());
        return $this->returnResult($result);
    }

    //手机APP费用面板数据
    public function chargeAppList()
    {
        $result = $this->chargeService->chargeAppList($this->chargeRequest->all(), $this->own);
        return $this->returnResult($result);
    }

    //多条提交
    public function addMutiCharge()
    {
        $result = $this->chargeService->addMutiCharge($this->chargeRequest->all(), $this->own);
        return $this->returnResult($result);
    }

    public function getChargeSubType()
    {
        $result = $this->chargeService->getChargeSubType($this->chargeRequest->all());
        return $this->returnResult($result);
    }
    // 科目设置
    public function chargeSubjectList()
    {
        $result = $this->chargeService->chargeSubjectList($this->chargeRequest->all());
        return $this->returnResult($result);
    }
    /**
     * 费用预警设置列表
     *
     * @apiTitle 费用预警设置列表
     * @param {int} autoFixPage 起始页
     * @param {int} limit 每页显示数量
     * @param {int} page 页码
     * @param {int} hasProject 是否有项目模块权限
     * @param {json} order_by 排序
     * @param {json} search 查询条件
     * @return array
     *
     * @paramExample {string} 参数示例
     * {
     *     "autoFixPage": 1,
     *     "limit": 10,
     *     "page": 1,
     *     "hasProject": 1,
     *     "order_by": {"charge_setting.updated_at":"desc"}
     *     "search": {"set_type":[1]}, // set_type 查询对象类型（公司-1\部门-2\个人-3\项目-5, 可为空）
     *     "search": {"project_id":[1],"set_type":[5]}, // 查询项目
     *     "search": {"charge_setting.dept_id":[2],"set_type":[2]}, // 查询部门
     *     "search": {"charge_setting.user_id":["admin"],"set_type":[3]}, // 查询用户
     * }
     * 
     * @success {boolean} status(1) 接入成功
     * @successExample {json} Success-Response:
     * "status": 1,
     * "data": {
     *      "list": [ //查询结果列表
     *          { 
     *              "alert_data_start": "0000-00-00", // 预警开始时间
     *              "alert_data_end": "0000-00-00", // 预警结束时间
     *              "alert_method": "year", // 预警方式
     *              "alert_value": "300", // 预警金额
     *              "charge_setting_id": 1, // 预警id
     *              "created_at": "2020-09-04 11:54:00",
     *              "deleted_at": null,
     *              "dept_id": 0, // 预警部门id
     *              "hide_subject": 0, // 隐藏预警值为0的科目
     *              "project_id": null, // 预警项目id
     *              "set_type": 3, // 预警对象类型
     *              "subject_check": 1, // 预警金额类型， 1-各科目值，0-总值
     *              "subject_values": "[// 科目预警值
     *                  {
     *                      "type_id":3, // 科目id
     *                      "type_value":"100" // 科目预警值
     *                  },
     *                  {"type_id":4,"type_value":"200"},
     *                   ...
     *              ]", 
     *              "updated_at": "2020-09-04 11:54:00",
     *              "user_id": "admin", // 预警用户id
     *              "user_name": "系统管理员", // 预警用户名称
     *              "user_status": 1, // 预警用户状态
     *          }
     *      ],
     *      "total": 1 //查询结果数量
     * }
     * @error {boolean} status(0) 接入失败
     * @error {array} errors 接入失败错误信息
     *
     * @errorExample {json} Error-Response:
     * { "status": 0,"errors":[{"code":"0x000003","message":"未知错误"}] }
     */
    public function getChargeSetList()
    {
        $result = $this->chargeSettingService->getChargeSetList($this->chargeRequest->all(), $this->own);
        return $this->returnResult($result);
    }
    /**
     * 删除费用预警设置
     *
     * @apiTitle 删除费用预警设置
     * @param {int} charge_setting_id 预警设置主键id
     * @return array
     * 
     * @success {boolean} status(1) 接入成功
     * @successExample {json} Success-Response:
     * "status": 1
     * 
     * @error {boolean} status(0) 接入失败
     * @error {array} errors 接入失败错误信息
     *
     * @errorExample {json} Error-Response:
     * { "status": 0,"errors":[{"code":"0x000003","message":"未知错误"}] }
     */
    public function deleteChargeSetItem($setId)
    {
        $result = $this->chargeService->deleteChargeSetItem($setId);
        return $this->returnResult($result);
    }
    public function chargeDataSourceByUndertake()
    {
        $result = $this->chargeService->chargeDataSourceByUndertake($this->chargeRequest->all(), $this->own);
        return $this->returnResult($result);
    }
    public function chargeSubjectAllUse()
    {
        $result = $this->chargeService->chargeSubjectAllUse($this->chargeRequest->all(), $this->own);
        return $this->returnResult($result);
    }
    public function chargeSubjectValue()
    {
        $result = $this->chargeService->chargeSubjectValue($this->chargeRequest->all());
        return $this->returnResult($result);
    }
    public function chargeSubjectUseValue()
    {
        $result = $this->chargeService->chargeSubjectUseValue($this->chargeRequest->all(), $this->own);
        return $this->returnResult($result);
    }
    public function chargeSubjectUnuseValue()
    {
        $result = $this->chargeService->chargeSubjectUnuseValue($this->chargeRequest->all(), $this->own);
        return $this->returnResult($result);
    }
    public function chargeDataSourceByProject($projectId) {
        $result = $this->chargeService->chargeDataSourceByProject($projectId);
        return $this->returnResult($result);
    }
    /**
     * 费用权限列表
     *
     * @apiTitle 费用权限列表
     * @param {int} autoFixPage 起始页
     * @param {int} limit 每页显示数量
     * @param {int} page 页码
     * @param {json} search 查询条件
     * @return array
     *
     * @paramExample {string} 参数示例
     * {
     *     limit: 10,
     *     page: 1,
     *     autoFixPage: 1,
     *     search: {"dept_name":["1","like"]}, // 查询部门名称
     *     search: {"role_name":["1","like"]}, // 查询角色名称
     *     search: {"user_name":["1","like"]}, // 查询用户名称
     *     search: {"manager_type":[1],"charge_permission.manager_value":[59]}, // 查询部门id
     *     search: {"manager_type":[2],"charge_permission.manager_value":[2]}, // 查询角色id
     *     search: {"manager_type":[3],"charge_permission.manager_value":["admin"]}, // 查询用户id
     * }
     * 
     * @success {boolean} status(1) 接入成功
     * @successExample {json} Success-Response:
     * "status": 1,
     * "data": {
     *      "list": [ //查询结果列表
     *          { 
     *              "arr_parent_id": null
     *              "change_pwd": null,
     *              "created_at": null,
     *              "deleted_at": null,
     *              "dept_id": null,
     *              "dept_name": null, // 部门名称
     *              "dept_name_py": null,
     *              "dept_name_zm": null,
     *              "dept_sort": null,
     *              "fax_no": null,
     *              "has_children": null,
     *              "id": 3,
     *              "list_number": null,
     *              "manager_type": 2, // 管理者类型，1-部门，2-角色，3-用户
     *              "manager_value": "1", // 管理者
     *              "parent_id": null,
     *              "password": null,
     *              "role_id": 1,
     *              "role_name": "OA管理员",
     *              "role_name_py": "OAguanliyuan",
     *              "role_name_zm": "OAgly",
     *              "role_no": 0,
     *              "set_type": 1, // 管理范围类型，1-全公司，2-部门，3-角色，4-用户，5-直接下属，6-所有下属
     *              "set_value": "", // 管理范围
     *              "tel_no": null,
     *              "updated_at": null,
     *              "user_accounts": null,
     *              "user_area": null,
     *              "user_city": null,
     *              "user_id": null,
     *              "user_job_category": null,
     *              "user_job_number": null,
     *              "user_job_number_seq": null,
     *              "user_name": null,
     *              "user_name_py": null,
     *              "user_name_zm": null,
     *              "user_position": null,
     *              "user_workplace": null,
     *          }
     *      ],
     *      "total": 1 //查询结果数量
     * }
     * @error {boolean} status(0) 接入失败
     * @error {array} errors 接入失败错误信息
     *
     * @errorExample {json} Error-Response:
     * { "status": 0,"errors":[{"code":"0x000003","message":"未知错误"}] }
     */
    public function getChargePermission() {
        $result = $this->chargeService->getChargePermission($this->chargeRequest->all());
        return $this->returnResult($result);
    }
    /**
     * 添加权限设置
     *
     * @apiTitle 添加权限设置
     * @param {array} manager 管理者
     * @param {int} set_type 权限设置方式，1-全公司，2-指定部门，3-指定角色，4-指定用户，5-直接下属，6-所有下属
     * @param {array} dept_id 指定管理部门
     * @param {int} has_children 是否包含子部门，1-包含，0-不包含
     * @param {array} role_id 指定管理角色
     * @param {array} user_id 指定管理用户
     * @return array
     *
     * @paramExample {string} 参数示例
     * {
     *     "manager": {
     *          "user_id": ["admin", "WV00000001"], // 管理者用户id
     *          "dept_id": [2, 15], // 管理者部门id
     *          "role_id": [1, 3, 4], // 管理者角色id
     *      },
     *     "set_type": 1,
     *     "dept_id": [16, 17, 20], // 管理范围部门
     *     "has_children": "1",
     *     "role_id": [7, 8, 10],// 管理范围角色
     *     "user_id": ["WV00000002", "WV00000003", ...] // 管理范围用户
     * }
     * 
     * @success {boolean} status(1) 接入成功
     * @successExample {json} Success-Response:
     * "status": 1,
     * 
     * @error {boolean} status(0) 接入失败
     * @error {array} errors 接入失败错误信息
     *
     * @errorExample {json} Error-Response:
     * { "status": 0,"errors":[{"code":"0x000003","message":"未知错误"}] }
     */
    public function addChargePermission() {
        $result = $this->chargeService->addChargePermission($this->chargeRequest->all());
        return $this->returnResult($result);
    }
    /**
     * 编辑权限设置
     *
     * @apiTitle 编辑权限设置
     * @param {int} id 编辑设置记录主键id
     * @param {array} manager 管理者
     * @param {int} set_type 权限设置方式，1-全公司，2-指定部门，3-指定角色，4-指定用户，5-直接下属，6-所有下属
     * @param {array} dept_id 指定管理部门
     * @param {int} has_children 是否包含子部门，1-包含，0-不包含
     * @param {array} role_id 指定管理角色
     * @param {array} user_id 指定管理用户
     * @return array
     *
     * @paramExample {string} 参数示例
     * {
     *     "id": 4,
     *     "manager": {
     *          "user_id": ["admin", "WV00000001"], // 管理者用户id
     *          "dept_id": [2, 15], // 管理者部门id
     *          "role_id": [1, 3, 4], // 管理者角色id
     *      },
     *     "set_type": 1,
     *     "dept_id": [16, 17, 20], // 管理范围部门
     *     "has_children": "1",
     *     "role_id": [7, 8, 10],// 管理范围角色
     *     "user_id": ["WV00000002", "WV00000003", ...] // 管理范围用户
     * }
     * 
     * @success {boolean} status(1) 接入成功
     * @successExample {json} Success-Response:
     * "status": 1,
     * 
     * @error {boolean} status(0) 接入失败
     * @error {array} errors 接入失败错误信息
     *
     * @errorExample {json} Error-Response:
     * { "status": 0,"errors":[{"code":"0x000003","message":"未知错误"}] }
     */
    public function editChargePermission($id) {
        $result = $this->chargeService->editChargePermission($id, $this->chargeRequest->all());
        return $this->returnResult($result);
    }
    // 获取权限详情
    public function getChargePermissionById($id) {
        $result = $this->chargeService->getChargePermissionById($id);
        return $this->returnResult($result);
    }
    // 删除权限设置
    public function deleteChargePermission($id) {
        $result = $this->chargeService->deleteChargePermission($id);
        return $this->returnResult($result);
    }
    public function getChargePortal() {
        $result = $this->chargeService->getChargePortal($this->chargeRequest->all(), $this->own);
        return $this->returnResult($result);
    }
    public function getChargeSetByDate() {
        $result = $this->chargeService->getChargeSetByDate($this->chargeRequest->all());
        return $this->returnResult($result);
    }
     public function getChargeSetType() {
        $result = $this->chargeService->getChargeSetType($this->chargeRequest->all());
        return $this->returnResult($result);
    }
    public function getChargeAlertMethod() {
        $result = $this->chargeService->getChargeAlertMethod($this->chargeRequest->all());
        return $this->returnResult($result);
    }
    public function chargeAppAlert() {
        $result = $this->chargeService->chargeAppAlert($this->chargeRequest->all());
        return $this->returnResult($result);
    }
    public function chargeAppTree($parentId) {
        $result = $this->chargeService->chargeAppTree($this->chargeRequest->all(), $parentId, $this->own);
        return $this->returnResult($result);
    }
    public function chargeAppTotal() {
        $result = $this->chargeService->chargeAppTotal($this->chargeRequest->all(), $this->own);
        return $this->returnResult($result);
    }
    public function chargeMobileType() {
        $result = $this->chargeService->chargeMobileType($this->chargeRequest->all());
        return $this->returnResult($result);
    }
    public function getChargeSubjectWarning() {
        $result = $this->chargeService->getChargeSubjectWarning($this->own);
        return $this->returnResult($result);
    }
    /**
     * 查询费用类型
     *
     * @apiTitle 查询费用类型
     * @param {json} search 查询条件
     * @return array
     *
     * @paramExample {string} 参数示例
     * {
     *     search: {"charge_type_name":["差旅费","like"]} // 查询费用类型名称
     * }
     * 
     * @success {boolean} status(1) 接入成功
     * @successExample {json} Success-Response:
     * "status": 1,
     * "data": [ //查询结果列表
     *     { 
     *          "charge_type_id": 157, // id
     *          "charge_type_name": "差旅费", // 名称
     *          "charge_type_order": 1, // 序号
     *          "charge_type_parent": 0, // 父级id
     *          "created_at": "-0001-11-30 00:00:00", // 创建时间
     *          "deleted_at": null, // 删除时间
     *          "has_children": 1, // 是否有子级
     *          "level": 1, // 层级
     *          "type_level": "0", // 层级id路径
     *          "type_level_name": "差旅费", // 层级名称路径
     *          "updated_at": "2020-09-17 17:20:56", // 更新时间
     *     }, {
     *          ...
     *     }
     * ]
     * 
     * @error {boolean} status(0) 接入失败
     * @error {array} errors 接入失败错误信息
     *
     * @errorExample {json} Error-Response:
     * { "status": 0,"errors":[{"code":"0x000003","message":"未知错误"}] }
     */
    public function chargeTypeSearch() {
        return $this->returnResult($this->chargeService->chargeTypeSearch($this->chargeRequest->all()));
    }
    // 指定时间范围内已报销额度
    public function getDataSourceInDateRange($type) {
        return $this->returnResult($this->chargeService->getDataSourceInDateRange($this->chargeRequest->all(), $type));
    }
    public function getChargeSetSelectorData() {
        return $this->returnResult($this->chargeService->getChargeSetSelectorData($this->chargeRequest->all(), $this->own));
    }
}
