<?php

namespace App\EofficeApp\Customer\Controllers;

use App\EofficeApp\Base\Controller;
use App\EofficeApp\Customer\Services\BusinessChanceService;
use App\EofficeApp\Customer\Services\ContactRecordService;
use App\EofficeApp\Customer\Services\ContractService;
use App\EofficeApp\Customer\Services\CustomerService;
use App\EofficeApp\Customer\Services\LinkmanService;
use App\EofficeApp\Customer\Services\PermissionGroupService;
use App\EofficeApp\Customer\Services\ProductService;
use App\EofficeApp\Customer\Services\ReportService;
use App\EofficeApp\Customer\Services\SaleRecordService;
use App\EofficeApp\Customer\Services\SupplierService;
use Illuminate\Http\Request;
use DB;

class CustomerController extends Controller
{

    private $customerService;

    public function __construct(
        Request $request,
        CustomerService $customerService,
        LinkmanService $linkmanService,
        ContactRecordService $contactRecordService,
        ContractService $contractService,
        BusinessChanceService $businessChanceService,
        SaleRecordService $saleRecordService,
        ProductService $productService,
        SupplierService $supplierService,
        ReportService $reportService,
        PermissionGroupService $permissionGroupService
    ) {
        parent::__construct();
        $this->request                = $request;
        $this->customerService        = $customerService;
        $this->linkmanService         = $linkmanService;
        $this->contactRecordService   = $contactRecordService;
        $this->contractService        = $contractService;
        $this->businessChanceService  = $businessChanceService;
        $this->saleRecordService      = $saleRecordService;
        $this->productService         = $productService;
        $this->supplierService        = $supplierService;
        $this->reportService          = $reportService;
        $this->permissionGroupService = $permissionGroupService;
    }

    /**
     * 客户列表接口
     *
     * @apiTitle 获取客户列表
     * @param {int} autoFixPage 起始页
     * @param {int} limit 每页显示数量
     * @param {json} order_by 排序
     * @param {int} page 页码
     * @param {json} search 查询条件
     *
     * @paramExample {string} 参数示例
     * {
     *	autoFixPage: 1
     *	limit: 10
     *	order_by: {"created_at":"desc"}
     *	page: 2
     *	search: {
     *      "project_id":[81,"="]
     *      "province":[3],   // 省查询
     *      "city":[3]        // 市查询
     * }
     * }
     *
     * @success {boolean} status(1) 接入成功
     *
     * @successExample {json} Success-Response:
     * {
     *      "status": 1,
     *      "data": [
     *          [
     *          "customer_id": '100', //客户id
     *			"SMS_remind_birthday": "2020-01-10", //生日
     *          "address": '上海xxx', //客户地址
     *          "created_at": '2020-09-10 10:05:52', //创建时间
     *          "customer_annual_sales": 100, //年销售额
     *          "customer_area": "", // 所在地区
     *          "customer_attribute": 'xxxx', // 客户属性
     *          "customer_creator": 'admin', // 创建人
     *          "customer_from": 1, // 客户来源
     *          "customer_industry": 计算机, // 客户行业
     *          "customer_introduce": 1, // 客户介绍
     *          "customer_logo": '', // 客户logo
     *          "customer_status": "11", // 客户状态
     *          "customer_type": '1', // 客户类型
     *          "phone_number": 1, // 客户电话
     *          "fax_no": 1, // 传真号码
     *          "website": 1, // 公司网址
     *          "legal_person": 1, // 企业法人
     *          "zip_code": 1, // 邮政编码
     *          "email": 1, // 电子邮箱
     *          "address": 1, // 公司地址
     *          "scale": 1, // 公司规模,
     *          "province" : 1 // 省
     *          "city" : 1 // 市
     *			],
     *			...
     *      ]
     * }
     *
     * @error {boolean} status(0) 接入失败
     * @error {array} errors 接入失败错误信息
     *
     * @errorExample {json} Error-Response:
     * { "status": 0,"errors":[{"code":"0x000003","message":"未知错误"}] }
     */
    public function customerLists()
    {
        $input  = $this->request->all();
        $result = $this->customerService->lists($input, $this->own);
        return $this->returnResult($result);
    }

    public function customerMergeLists()
    {
        $input  = $this->request->all();
        $result = $this->customerService->mergeLists($input, $this->own);
        return $this->returnResult($result);
    }

    public function customerTransferLists()
    {
        $input  = $this->request->all();
        $result = $this->customerService->transferLists($input, $this->own);
        return $this->returnResult($result);
    }

    public function showCustomer($customerId)
    {
        $input  = $this->request->all();
        $result = $this->customerService->show($customerId, $input, $this->own);
        return $this->returnResult($result);
    }

    public function customerMenus()
    {
        $customerId = $this->request->get('id');
        $result     = $this->customerService->menus($customerId, $this->own);
        return $this->returnResult($result);
    }

    /**
     * 新增客户
     *
     * @apiTitle 新增客户信息
     * @param {string} customer_name 客户名称(必填)
     * @param {string} customer_number 客户编号
     * @param {string} customer_manager 客户经理
     * @param {string} customer_service_manager 客服经理
     * @param {int} customer_status 客户状态
     * @param {int} customer_type 客户类型
     * @param {int} customer_from 客户来源
     * @param {int} customer_attribute 客户属性
     * @param {int} customer_industry 客户行业
     * @param {string} phone_number 客户电话
     * @param {string} fax_no 传真号码
     * @param {string} website 公司网址
     * @param {date} legal_person 企业法人
     * @param {string} zip_code 邮政编码
     * @param {string} email 电子邮箱
     * @param {string} address 客户地址
     * @param {int} scale 公司规模
     * @param {string} customer_annual_sales 年销售额
     * @param {string} customer_introduce 客户介绍
     * @param {string} customer_area 所在地区
     * @param {int} seas_group_id 公海分组
     * @param {date} created_at 创建时间
     * @param {string} customer_creator 创建人
     * @paramExample {string} 参数示例
     * {
     *  customer_name: "上海",
     *  customer_manager: "admin",
     *  customer_service_manager: "admin",
     *  customer_from: "11",
     *  customer_status: 2,
     *  customer_from: 1,
     *  customer_area: {
                province : 7,
     *          city:31
     *      }
     * }
     *
     * @success {boolean} status(1) 接入成功
     * @successExample {json} Success-Response:
     * {
     *      "status": 1,
     *      "data": 1160   // 新增返回客户id
     * }
     *
     * @error {boolean} status(0) 接入失败
     * @error {array} errors 接入失败错误信息
     *
     * @errorExample {json} Error-Response:
     * { "status": 0,"errors":[{"code":"0x000003","message":"未知错误"}] }
     */

    public function storeCustomer()
    {
        $input  = $this->request->all();
        $userId = $this->own['user_id'] ?? '';
        $result = $this->customerService->store($input, $userId);
        return $this->returnResult($result);
    }

    // 网站数据新增客户
    public function storeWebCustomer()
    {
        $input = $this->request->all();
        $result = $this->customerService->storeWebCustomer($input);
        return $this->returnResult($result);
    }

    public function applyPermissionLists()
    {
        $userId = $this->own['user_id'] ?? '';
        $result = $this->customerService->applyPermissionLists($this->request->all(), $userId);
        return $this->returnResult($result);
    }

    public function showApplyPermissions($id)
    {
        $result = $this->customerService->showApplyPermissions($id, $this->own);
        return $this->returnResult($result);
    }

    public function applyPermissions($id)
    {
        $input  = $this->request->all();
        $result = $this->customerService->applyCustomerPermissions($id, $input, $this->own);
        return $this->returnResult($result);
    }

    public function deleteApplyPermissions($ids)
    {
        $applyIds = array_filter(explode(',', $ids));
        $result   = $this->customerService->deleteApplyPermissions($applyIds, $this->own);
        return $this->returnResult($result);
    }

    public function applyAuditLists()
    {
        $result = $this->customerService->applyAuditLists($this->request->all(), $this->own);
        return $this->returnResult($result);
    }

    public function deleteCustomerRecycles($customerIds)
    {
        $customerIds = array_filter(explode(',', $customerIds));
        $result      = $this->customerService->deleteRecycleCustomers($customerIds);
        return $this->returnResult($result);
    }

    public function recoverRecycleCustomer($id)
    {
        $userId = $this->own['user_id'] ?? '';
        $result = $this->customerService->recoverRecycleCustomer($id, $userId);
        return $this->returnResult($result);
    }

    public function showRecycleCustomer($id)
    {
        $result = $this->customerService->showRecycleCustomer($id);
        return $this->returnResult($result);
    }

    public function recycleCustomerLists()
    {
        $result = $this->customerService->recycleCustomerLists($this->request->all(), $this->own);
        return $this->returnResult($result);
    }

    public function updateCustomerFace($customerId)
    {
        $face   = $this->request->get('customer_logo');
        $result = $this->customerService->updateFace($customerId, $face, $this->own);
        return $this->returnResult($result);
    }

    /**
     * 编辑客户
     *
     * @apiTitle 更新客户信息
     * @param {date} SMS_remind_birthday 生日
     * @param {string} address 客户地址
     * @param {string} customer_area 所在地区
     * @param {string} customer_annual_sales 年销售额
     * @param {int} customer_attribute 客户属性
     * @param {int} customer_from 客户来源
     * @param {int} customer_industry 计算机
     * @param {string} customer_introduce 客户介绍
     * @param {int} customer_status 客户状态
     * @param {int} customer_type 客户类型
     * @param {string} phone_number 客户电话
     * @param {string} fax_no 传真号码
     * @param {string} website 公司网址
     * @param {date} legal_person 企业法人
     * @param {string} zip_code 邮政编码
     * @param {string} email 电子邮箱
     * @param {string} address 公司地址
     * @param {int} scale 公司规模
     * @param {int} outsourceForEdit 编辑标识

     *
     * @paramExample {string} 参数示例
     * {
     *  SMS_remind_birthday: "2020-01-01",
     *  address: "上海",
     *  customer_attribute: "222",
     *  customer_from: "11",
     *  customer_industry: "2",
     *  outsourceForEdit : 1
     * }
     *
     * @success {boolean} status(1) 接入成功
     * @successExample {json} Success-Response:
     * {
     *      "status": 1,
     * }
     *
     * @error {boolean} status(0) 接入失败
     * @error {array} errors 接入失败错误信息
     *
     * @errorExample {json} Error-Response:
     * { "status": 0,"errors":[{"code":"0x000003","message":"未知错误"}] }
     */
    public function updateCustomer($customerId)
    {
        $input  = $this->request->all();
        $result = $this->customerService->update($customerId, $input, $this->own);
        return $this->returnResult($result);
    }

    // 没有权限控制的客户列表
    public function customerAllLists()
    {
        $input  = $this->request->all();
        $result = $this->customerService->allLists($input, $this->own);
        return $this->returnResult($result);
    }

    // 删除
    /**
     * 删除客户信息
     *
     * @apiTitle 删除客户信息
     * @success {boolean} status(1) 接入成功
     * @successExample {json} Success-Response:
     * {
     *      "status": 1,
     * }
     *
     * @error {boolean} status(0) 接入失败
     * @error {array} errors 接入失败错误信息
     *
     * @errorExample {json} Error-Response:
     * { "status": 0,"errors":[{"code":"0x000003","message":"未知错误"}] }
     */
    public function deleteCustomer($customerIds)
    {
        $customerIds = array_filter(explode(',', $customerIds));
        $result      = $this->customerService->delete($customerIds, $this->own);
        return $this->returnResult($result);
    }

    // 添加关注
    public function addAttention($customerId)
    {
        $result = $this->customerService->attention($customerId, $this->own);
        return $this->returnResult($result);
    }

    // 取消关注
    public function cancelAttention($customerId)
    {
        $result = $this->customerService->attention($customerId, $this->own, true);
        return $this->returnResult($result);
    }

    // 获取日志
    public function customerLogLists($customerId)
    {
        $input  = $this->request->all();
        $result = $this->customerService->logLists($customerId, $input, $this->own);
        return $this->returnResult($result);
    }

    // 获取日志(新)
    public function customerNewLogLists($customerId)
    {
        $result = $this->customerService->newLogLists($customerId);
        return $this->returnResult($result);
    }

    // 添加客户分享
    public function shareCustomers()
    {
        $input  = $this->request->all();
        $result = $this->customerService->shareCustomers($input, $this->own);
        return $this->returnResult($result);
    }

    // 客户详情分享
    public function shareCustomer($customerId)
    {
        $input  = $this->request->all();
        $result = $this->customerService->shareCustomer($customerId, $input, $this->own);
        return $this->returnResult($result);
    }

    // 用户拥有客户经理的数量
    public function managerCustomers()
    {
        $input  = $this->request->all();
        $result = $this->customerService->managerCustomers($input, $this->own);
        return $this->returnResult($result);
    }

    // 删除回收站中的客户
    public function deleteRecycleCustomers($ids)
    {
        $customerIds = array_filter(explode(',', $ids));
        $result      = $this->customerService->deleteRecycleCustomers($customerIds);
        return $this->returnResult($result);
    }

    // 权限申请详情
    public function showApplyAudit($id)
    {
        $result = $this->customerService->showApplyAudit($id, $this->own);
        return $this->returnResult($result);
    }

    // 更新权限申请
    public function updateApplyAudit($ids)
    {
        $input  = $this->request->all();
        $result = $this->customerService->updateApplyAudit($ids, $input, $this->own);
        return $this->returnResult($result);
    }

    // 更新权限申请
    public function deleteApplyAudits($applyIds)
    {
        $input  = $this->request->all();
        $result = $this->customerService->deleteApplyAudits($applyIds, $input, $this->own);
        return $this->returnResult($result);
    }

    // 转移客户
    public function transferCustomer()
    {
        $input  = $this->request->all();
        $result = $this->customerService->transferCustomer($input, $this->own);
        return $this->returnResult($result);
    }

    // 查看需要合并的客户详情
    public function showCustomerMerge($customerIds)
    {
        $customerIds = array_filter(explode(',', $customerIds));
        $result      = $this->customerService->showCustomerMerge($customerIds, $this->own);
        return $this->returnResult($result);
    }

    // 合并客户
    public function mergeCustomer()
    {
        $input  = $this->request->all();
        $result = $this->customerService->mergeCustomer($input, $this->own);
        return $this->returnResult($result);
    }

    // 切换客户详情菜单显示
    public function toggleCustomerMenus($key)
    {
        $userId = $this->own['user_id'] ?? '';
        $params = $this->request->all();
        $result = $this->customerService->toggleCustomerMenus($key, $userId, $params);
        return $this->returnResult($result);
    }

    /**
     * 提醒计划
     */
    public function userVisitLists($customerId)
    {
        $input  = $this->request->all();
        $result = $this->customerService->userVisitLists($customerId, $input, $this->own);
        return $this->returnResult($result);
    }

    // 客户详情提醒列表
    public function visitLists()
    {
        $input  = $this->request->all();
        $result = $this->customerService->visitLists($input, $this->own);
        return $this->returnResult($result);
    }

    // 新建提醒
    public function storeVisit()
    {
        $input  = $this->request->all();
        $result = $this->customerService->storeVisit($input, $this->own);
        return $this->returnResult($result);
    }

    // 完成提醒
    public function doneWillVisit($visitId)
    {
        $input  = $this->request->all();
        $result = $this->customerService->doneWillVisit($input, $this->own);
        return $this->returnResult($result);
    }

    public function updateWillVisit($visitId)
    {
        $input  = $this->request->all();
        $result = $this->customerService->updateWillVisit($visitId, $input, $this->own);
        return $this->returnResult($result);
    }

    // 删除提醒
    public function deleteWillVisit($visitIds)
    {
        $result = $this->customerService->deleteWillVisit($visitIds, $this->own);
        return $this->returnResult($result);
    }

    // 获取可以查看客户的所有用户id
    public function visitCustomerUserIds($customerId)
    {
        $result = $this->customerService->visitCustomerUserIds($customerId);
        return $this->returnResult($result);
    }

    // 客户详情
    public function showVisit($visitId)
    {
        $result = $this->customerService->showVisit($visitId, $this->own);
        return $this->returnResult($result);
    }

    /**
     * 联系人操作
     */

    /**
     * 联系人列表接口
     *
     * @apiTitle 获取联系人列表
     * @param {int} autoFixPage 起始页
     * @param {int} limit 每页显示数量
     * @param {json} order_by 排序
     * @param {int} page 页码
     * @param {json} search 查询条件
     *
     * @paramExample {string} 参数示例
     * {
     *	autoFixPage: 1
     *	limit: 10
     *	order_by: {"created_at":"desc"}
     *	page: 2
     *	search: {
     *      "project_id":[81,"="]  // 项目id
     *      "linkman_name":['王炸', 'like'],   //
     * }
     * }
     *
     * @success {boolean} status(1) 接入成功
     *
     * @successExample {json} Success-Response:
     * {
     *      "status": 1,
     *      "data": [
     *          [
     *          "linkman_id": '100', //联系人id
     *			"linkman_name": "2020-01-10", //联系人姓名
     *          "sex": '男士', //称谓
     *          "customer_id": '122', //所属客户
     *          "main_linkman": 11, //主联系人
     *          "mobile_phone_number": "", // 手机号码
     *          "email": 'xxxx', // 电子邮箱
     *          "company_phone_number": '1100-55-55', // company_phone_number
     *          "department": '技术部', // 部门名称
     *          "position": 'xxx', // 职位名称
     *          "fax_number": '111-11-11', // 传真号码
     *          "weixin": '', // 微信号码
     *          "home_phone_number": "118-88-88", // 家庭电话
     *          "qq_number": '10001', // QQ号码
     *          "birthday": 1, // 联系人生日
     *          "fax_no": '2020-02-22', // 传真号码
     *          "hobby": 'ddd', // 兴趣爱好
     *          "address": 'xxx', // 家庭住址
     *          "zip_code": 1, // 家庭邮编
     *          "linkman_remarks": 'xxx', // 备注
     *			],
     *			...
     *      ]
     * }
     *
     * @error {boolean} status(0) 接入失败
     * @error {array} errors 接入失败错误信息
     *
     * @errorExample {json} Error-Response:
     * { "status": 0,"errors":[{"code":"0x000003","message":"未知错误"}] }
     */

    public function linkmanLists()
    {
        $input  = $this->request->all();
        $result = $this->linkmanService->lists($input, $this->own);
        return $this->returnResult($result);
    }

    public function showLinkman(int $id)
    {
        $result = $this->linkmanService->show($id, $this->own);
        return $this->returnResult($result);
    }

    public function storeLinkman()
    {
        $input  = $this->request->all();
        $result = $this->linkmanService->store($input, $this->own);
        return $this->returnResult($result);
    }

    /**
     * 编辑客户
     *
     * @apiTitle 更新联系人信息
     * @param {int} sex 称谓
     * @param {int} customer_id 所属客户
     * @param {int} main_linkman 主联系人
     * @param {string} mobile_phone_number 手机号码
     * @param {string} email 电子邮箱
     * @param {string} company_phone_number 公司电话
     * @param {string} department 部门名称
     * @param {string} position 职位名称
     * @param {string} fax_number 传真号码
     * @param {string} weixin 微信号码
     * @param {string} home_phone_number 家庭电话
     * @param {string} qq_number QQ号码
     * @param {date} birthday 联系人生日
     * @param {string} fax_no 传真号码
     * @param {string} hobby 兴趣爱好
     * @param {string} address 家庭住址
     * @param {string} zip_code 家庭邮编
     * @param {string} linkman_remarks 家庭邮编
     * @param {int} outsourceForEdit 编辑标识

     *
     * @paramExample {string} 参数示例
     * {
     *  sex: "1",
     *  customer_id: "1",
     *  main_linkman: "1",
     *  mobile_phone_number: "18752525252",
     *  customer_industry: "2",
     *  outsourceForEdit : 1,
     * ........
     * }
     *
     * @success {boolean} status(1) 接入成功
     * @successExample {json} Success-Response:
     * {
     *      "status": 1,
     * }
     *
     * @error {boolean} status(0) 接入失败
     * @error {array} errors 接入失败错误信息
     *
     * @errorExample {json} Error-Response:
     * { "status": 0,"errors":[{"code":"0x000003","message":"未知错误"}] }
     */
    public function updateLinkman($id)
    {
        $input  = $this->request->all();
        $result = $this->linkmanService->update($id, $input, $this->own);
        return $this->returnResult($result);
    }

    public function deleteLinkman($ids)
    {
        $ids    = array_filter(explode(',', $ids));
        $result = $this->linkmanService->delete($ids, $this->own);
        return $this->returnResult($result);
    }

    public function customerLinkmans($id)
    {
        $result = $this->linkmanService->customerLinkmans((int)$id);
        return $this->returnResult($result);
    }

    public function customersLinkmans($customerId)
    {
        $input = $this->request->all();
        $originSearchs = isset($input['search']) ? json_decode($input['search'], true) : ['customer_id' => [$customerId]];
        // 联系人已经改变了客户，但是仍然需要找出对应的联系人
        // 添加includeLeave
        if (!empty($originSearchs) && !isset($input['includeLeave'])) {
            $originSearchs = array_merge($originSearchs, ['customer_id' => [$customerId]]);
        }
        $input['search'] = json_encode($originSearchs);
        $input['flag']   = $customerId;
        $result          = $this->linkmanService->lists($input, $this->own);
        return $this->returnResult($result);
    }

    /**
     * 联系记录操作
     */
    public function showContactRecord(int $id)
    {
        $result = $this->contactRecordService->show($id, $this->own);
        return $this->returnResult($result);
    }

    /**
     * 添加联系记录
     *
     * @apiTitle 添加联系记录
     * @param {int} customer_id 客户id
     * @param {int} linkman_id 联系人id
     * @param {date} record_start 联系开始时间
     * @param {date} record_end 联系结束时间
     * @param {string} record_content 联系内容
     * @param {string} address 外勤签到地址
     * @param {int} record_type 联系类型（1-电话联系，2-邮件联系，3-短信联系，4-在线联系，5-上门拜访）
     *
     * @paramExample {string} 参数示例
     * {
     *  customer_id: 11,
     *  linkman_id: 12,
     *  record_start: "2020-09-15 10:08",
     *  record_end: "2020-09-20 10:08",
     *  record_content: "xxxxx",
     *  record_type : 1 (自定义下拉框，数据value值需要自己去oa自己查看)
     * }
     *
     * @success {boolean} status(1) 接入成功
     * @successExample {json} Success-Response:
     * {
     *      "status": 1,
     *      "data": {
                "address" : '上海',
                "attachment_id" : [],
     *          "contact_record_creator": {},
     *          "contact_record_customer" : {},
     *          "contact_record_linkman" : 12,
     *          "created_at" : '2020-09-15 09:38:58',
     *          "customer_id" : '11',
     *          "record_content" : 'xxxxx',
     *          "record_creator" : 'admin',
     *          "record_start" : '2020-09-15 10:08:00',
     *          "record_end" : '2020-09-20 10:08:00',
     *          "record_id" : 100,
     *          "record_type" : 1,
     *          "record_type_name" : '上门拜访',
     *
     *      }
     * }
     *
     * @error {boolean} status(0) 接入失败
     * @error {array} errors 接入失败错误信息
     *
     * @errorExample {json} Error-Response:
     * { "status": 0,"errors":[{"code":"0x000003","message":"未知错误"}] }
     */
    public function storeContactRecord()
    {
        $input                   = $this->request->all();
        $input['record_creator'] = $this->own['user_id'] ?? '';
        $result                  = $this->contactRecordService->store($input, $this->own);
        return $this->returnResult($result);
    }

    public function customerContactRecords(int $id)
    {
        $currentPage = $this->request->get('page');
        $result      = $this->contactRecordService->customerContactRecords($id, $currentPage,$this->own);
        return $this->returnResult($result);
    }

    /**
     * 客户列表接口
     *
     * @apiTitle 获取联系记录列表
     * @param {int} autoFixPage 起始页
     * @param {int} limit 每页显示数量
     * @param {json} order_by 排序
     * @param {int} page 页码
     * @param {json} search 查询条件
     *
     * @paramExample {string} 参数示例
     * {
     *	autoFixPage: 1
     *	limit: 10
     *	order_by: {"created_at":"desc"}
     *	page: 2
     *	search: {
     *      "project_id":[81]     // 项目id查询
     *      "customer_id":[3],   // 所属客户查询
     *      "record_type":[2]        // 联系类型查询
     *      "record_content":['111','like']        // 联系记录内容查询
     * }
     * }
     *
     * @success {boolean} status(1) 接入成功
     *
     * @successExample {json} Success-Response:
     * {
     *      "status": 1,
     *      "data": [
     *          [
     *          "address" : '上海',
                "attachment_id" : [],
     *          "contact_record_creator": {},
     *          "contact_record_customer" : {},
     *          "contact_record_linkman" : 12,
     *          "created_at" : '2020-09-15 09:38:58',
     *          "customer_id" : '11',
     *          "record_content" : 'xxxxx',
     *          "record_creator" : 'admin',
     *          "record_start" : '2020-09-15 10:08:00',
     *          "record_end" : '2020-09-20 10:08:00',
     *          "record_id" : 100,
     *          "record_type" : 1,
     *          "record_type_name" : '上门拜访',
     *			],
     *			...
     *      ]
     * }
     *
     * @error {boolean} status(0) 接入失败
     * @error {array} errors 接入失败错误信息
     *
     * @errorExample {json} Error-Response:
     * { "status": 0,"errors":[{"code":"0x000003","message":"未知错误"}] }
     */
    public function contactRecordLists()
    {
        $input  = $this->request->all();
        $result = $this->contactRecordService->lists($input, $this->own);
        return $this->returnResult($result);
    }

    public function deleteContactRecords(string $ids)
    {
        $ids    = array_filter(explode(',', $ids));
        $result = $this->contactRecordService->delete($ids, $this->own);
        return $this->returnResult($result);
    }

    public function deleteContactRecordComment($recordId, $commentId)
    {
        $result = $this->contactRecordService->deleteContactRecordComment($commentId);
        return $this->returnResult($result);
    }

    public function storeContactRecordComment()
    {
        $input  = $this->request->all();
        $result = $this->contactRecordService->storeComment($input);
        return $this->returnResult($result);
    }

    public function contactRecordComments(int $id)
    {
        $input  = $this->request->all();
        $result = $this->contactRecordService->commentLists($id, $input);
        return $this->returnResult($result);
    }

    /**
     * 合同信息
     */

    public function showContract($id)
    {
        $result = $this->contractService->show($id, $this->own);
        return $this->returnResult($result);
    }

    public function contractLists()
    {
        $input  = $this->request->all();
        $result = $this->contractService->lists($input, $this->own);
        return $this->returnResult($result);
    }

    public function storeContract()
    {
        $input  = $this->request->all();
        $result = $this->contractService->store($input, $this->own);
        return $this->returnResult($result);
    }

    public function deleteContract(string $ids)
    {
        $ids    = array_filter(explode(',', $ids));
        $result = $this->contractService->delete($ids, $this->own);
        return $this->returnResult($result);
    }

    public function updateContract(int $id)
    {
        $input  = $this->request->all();
        $result = $this->contractService->update($id, $input, $this->own);
        return $this->returnResult($result);
    }

    /**
     * 业务机会
     */
    public function businessChanceLists()
    {
        $input  = $this->request->all();
        $result = $this->businessChanceService->lists($input, $this->own);
        return $this->returnResult($result);
    }

    public function showBusinessChance($chanceId)
    {
        $result = $this->businessChanceService->show($chanceId, $this->own);
        return $this->returnResult($result);
    }

    public function customersBusinessChances($customerId)
    {
        $input = $this->request->all();
        if (!isset($input['search'])) {
            $input['search'] = [];
        }
        $input['search'] = ['customer_id' => [$customerId]];
        $result          = $this->businessChanceService->lists($input, $this->own);
        return $this->returnResult($result);
    }

    public function updateBusinessChance($chanceId)
    {
        $input  = $this->request->all();
        $result = $this->businessChanceService->update($chanceId, $input, $this->own);
        return $this->returnResult($result);
    }

    public function storeBusinessChance()
    {
        $input                   = $this->request->all();
        $input['chance_creator'] = $this->own['user_id'] ?? '';
        $result                  = $this->businessChanceService->store($input, $this->own);
        return $this->returnResult($result);
    }

    public function deleteBusinessChance(string $chanceIds)
    {
        $ids    = array_filter(explode(',', $chanceIds));
        $result = $this->businessChanceService->delete($ids, $this->own);
        return $this->returnResult($result);
    }

    public function businessChanceLogLists()
    {
        $input  = $this->request->all();
        $result = $this->businessChanceService->LogLists($input);
        return $this->returnResult($result);
    }

    public function storeBusinessChanceLog($chanceId)
    {
        $input  = $this->request->all();
        $result = $this->businessChanceService->storeLog($chanceId, $input, $this->own);
        return $this->returnResult($result);
    }

    /**
     * 销售记录
     */
    public function saleRecordLists()
    {
        $input  = $this->request->all();
        $result = $this->saleRecordService->lists($input, $this->own);
        return $this->returnResult($result);
    }

    public function storeSaleRecord()
    {
        $input                  = $this->request->all();
        $input['sales_creator'] = $this->own['user_id'] ?? '';
        $result                 = $this->saleRecordService->store($input, $this->own);
        return $this->returnResult($result);
    }

    public function deleteSaleRecords($salesIds)
    {
        $salesIds = array_filter(explode(',', $salesIds));
        $result   = $this->saleRecordService->delete($salesIds, $this->own);
        return $this->returnResult($result);
    }

    public function updateSaleRecord($salesId)
    {
        $input  = $this->request->all();
        $result = $this->saleRecordService->update($salesId, $input, $this->own);
        return $this->returnResult($result);
    }

    public function showSaleRecord($salesId)
    {
        $result = $this->saleRecordService->show($salesId, $this->own);
        return $this->returnResult($result);
    }

    /**
     * 产品信息
     */
    public function productLists()
    {
        $input  = $this->request->all();
        $result = $this->productService->lists($input, $this->own);
        return $this->returnResult($result);
    }

    public function storeProduct()
    {
        $input                    = $this->request->all();
        $input['product_creator'] = $this->own['user_id'] ?? '';
        $result                   = $this->productService->store($input);
        return $this->returnResult($result);
    }

    public function updateProduct($id)
    {
        $input  = $this->request->all();
        $result = $this->productService->update($id, $input, $this->own);
        return $this->returnResult($result);
    }

    public function deleteProducts($ids)
    {
        $productIds = array_filter(explode(',', $ids));
        $result     = $this->productService->delete($productIds, $this->own);
        return $this->returnResult($result);
    }

    public function showProduct($id)
    {
        $result = $this->productService->show($id, $this->own);
        return $this->returnResult($result);
    }

    /**
     * 供应商
     */
    public function supplierLists()
    {
        $input  = $this->request->all();
        $result = $this->supplierService->lists($input, $this->own);
        return $this->returnResult($result);
    }

    public function deleteSuppliers($ids)
    {
        $ids    = array_filter(explode(',', $ids));
        $result = $this->supplierService->delete($ids, $this->own);
        return $this->returnResult($result);
    }

    public function updateSupplier($id)
    {
        $input  = $this->request->all();
        $result = $this->supplierService->update($id, $input, $this->own);
        return $this->returnResult($result);
    }

    public function storeSupplier()
    {
        $input  = $this->request->all();
        $result = $this->supplierService->store($input, $this->own);
        return $this->returnResult($result);
    }

    public function showSupplier($id)
    {
        $result = $this->supplierService->show($id, $this->own);
        return $this->returnResult($result);
    }

    /**
     * 客户报表
     */
    public function getCustomerReportByTypes($types)
    {
        $result = $this->reportService->getCustomerReportByTypes($types, $this->request->all());
        return $this->returnResult($result);
    }

    /**
     * 客户权限组
     */
    public function permissionGroupList()
    {
        $input  = $this->request->all();
        $result = $this->permissionGroupService->lists($input);
        return $this->returnResult($result);
    }

    public function permissionGroupAllLists()
    {
        $input  = $this->request->all();
        $result = $this->permissionGroupService->allLists($input);
        return $this->returnResult($result);
    }

    public function storePermissionGroup()
    {
        $input  = $this->request->all();
        $result = $this->permissionGroupService->store($input);
        return $this->returnResult($result);
    }

    public function updatePermissionGroup($groupId)
    {
        $input  = $this->request->all();
        $result = $this->permissionGroupService->update($groupId, $input);
        return $this->returnResult($result);
    }

    public function showPermissionGroup($groupId)
    {
        $result = $this->permissionGroupService->show($groupId);
        return $this->returnResult($result);
    }

    public function deletePermissionGroup($groupId)
    {
        $result = $this->permissionGroupService->delete($groupId);
        return $this->returnResult($result);
    }

    public function showPermissionGroupRole($roleId)
    {
        $result = $this->permissionGroupService->showRole($roleId);
        return $this->returnResult($result);
    }

    public function updatePermissionGroupRole($roleId)
    {
        $data   = $this->request->all();
        $result = $this->permissionGroupService->updateRole($roleId, $data);
        return $this->returnResult($result);
    }

    /**
     * 客户公海
     */
    public function seasLists()
    {
        $input  = $this->request->all();
        $result = $this->customerService->seasLists($this->own, $input);
        return $this->returnResult($result);
    }

    public function storeSeasGroup()
    {
        $input  = $this->request->all();
        $result = $this->customerService->storeSeasGroup($input);
        return $this->returnResult($result);
    }

    public function showSeasGroup($id)
    {
        $result = $this->customerService->showSeasGroup($id);
        return $this->returnResult($result);
    }

    public function updateSeasGroup($id)
    {
        $input  = $this->request->all();
        $result = $this->customerService->updateSeasGroup($id, $input);
        return $this->returnResult($result);
    }

    public function deleteSeasGroup($id)
    {
        $result = $this->customerService->deleteSeasGroup($id);
        return $this->returnResult($result);
    }

    public function modifyCustomer(){
        $input  = $this->request->all();
        $result = $this->customerService->modifyCustomer($input,$this->own);
        return $this->returnResult($result);
    }

    public function seasGroupLists()
    {
        $input  = $this->request->all();
        $result = $this->customerService->seasGroupLists($input);
        return $this->returnResult($result);
    }

    public function pickUpCustomers()
    {
        $input  = $this->request->all();
        $result = $this->customerService->pickUpCustomers($input);
        return $this->returnResult($result);
    }

    public function pickDownCustomers()
    {
        $input  = $this->request->all();
        $userId = $this->own['user_id'] ?? '';
        $result = $this->customerService->pickDownCustomers($userId, $input);
        return $this->returnResult($result);
    }

    public function changeSeasCustomerManager()
    {
        $input  = $this->request->all();
        $userId = $this->own['user_id'] ?? '';
        $result = $this->customerService->changeSeasCustomerManager($userId, $input);
        return $this->returnResult($result);
    }

    public function transferSeasCustomerManager()
    {
        $input  = $this->request->all();
        $userId = $this->own['user_id'] ?? '';
        $result = $this->customerService->transferSeasCustomerManager($userId, $input);
        return $this->returnResult($result);
    }

    public function changeSeasCustomerGroup()
    {
        $input  = $this->request->all();
        $userId = $this->own['user_id'] ?? '';
        $result = $this->customerService->changeSeas($userId, $input);
        return $this->returnResult($result);
    }

    public function seasCustomerManagerLists($id)
    {
        $input  = $this->request->all();
        $userId = $this->own['user_id'] ?? '';
        $result = $this->customerService->seasCustomerManagerLists($id, $userId, $input);
        return $this->returnResult($result);
    }

    public function autoDistribute($id)
    {
        $customerIds = (array) $this->request->post('customer_id');
        $userId      = $this->own['user_id'] ?? '';
        $result      = $this->customerService->autoDistribute($id, $customerIds, $userId);
        return $this->returnResult($result);
    }

    // 流程数据源配置
    public function dataSourceByCustomerId()
    {
        $input  = $this->request->all();
        $result = $this->linkmanService->dataSourceByCustomerId($input, $this->own);
        return $this->returnResult($result);
    }

    public function dataSourceByCustomerSupplierId()
    {
        $input  = $this->request->all();
        $result = $this->contractService->dataSourceByCustomerSupplierId($input);
        return $this->returnResult($result);
    }

    public function showPreCustomer($customerId){
        $input  = $this->request->all();
        $result = $this->customerService->showPreCustomer($input,$customerId, $this->own);
        return $this->returnResult($result);
    }

    public function showNextCustomer($customerId){
        $input  = $this->request->all();
        $result = $this->customerService->showNextCustomer($input,$customerId, $this->own);
        return $this->returnResult($result);
    }

    // 设置大公海
    public function seasSetting(){
        $input  = $this->request->all();
        $result = $this->customerService->seasSetting($input, $this->own);
        return $this->returnResult($result);
    }

    public function getSetting(){
        $input  = $this->request->all();
        $result = $this->customerService->getSetting($input, $this->own);
        return $this->returnResult($result);
    }

    public function updateSetting($id){
        $input  = $this->request->all();
        $result = $this->customerService->updateSetting($input, $id,$this->own);
        return $this->returnResult($result);
    }

    public function cancelShare($customerId){
        $customerIds = array_filter(explode(',', $customerId));
        $result = $this->customerService->cancelShare($customerIds, $input  = $this->request->all(),$this->own);
        return $this->returnResult($result);
    }

    public function getSecurityOption($params){
        $result = app('App\EofficeApp\System\Security\Services\SystemSecurityService')->getSecurityOption($params);
        return $this->returnResult($result);
    }

    public function modifySecurityOption($params){
        $result = app('App\EofficeApp\System\Security\Services\SystemSecurityService')->modifySecurityOption($params,$this->request->all());
        return $this->returnResult($result);
    }

    public function translateTitle(){
        $result = $this->customerService->translateTitle($input  = $this->request->all(), $this->own);
        return $this->returnResult($result);
    }

    // 联系人绑定
    public function bindingLinkman($id){
        $result = $this->linkmanService->bindingLinkman($this->request->all(), $id,$this->own);
        return $this->returnResult($result);
    }

    // 联系人解绑
    public function cancelBinding($id){
        $result = $this->linkmanService->cancelBinding($id,$this->own);
        return $this->returnResult($result);
    }

    public function bindingList(){
        $result = $this->linkmanService->bindingList($this->request->all(), $this->own);
        return $this->returnResult($result);
    }

    public function weChatList(){
        $result = $this->customerService->weChatList($this->request->all(), $this->own);
        return $this->returnResult($result);
    }

    public function chatBinding($id){
        $result = $this->customerService->chatBinding($this->request->all(), $id, $this->own);
        return $this->returnResult($result);
    }

    public function cancelChatBinding($id){
        $result = $this->customerService->cancelChatBinding($id, $this->own);
        return $this->returnResult($result);
    }

    public function mapCustomer(){
        $input  = $this->request->all();
        $result = $this->customerService->mapCustomer($input, $this->own);
        return $this->returnResult($result);
    }

    public function labelList(){
        $result = $this->customerService->labelList($this->request->all(), $this->own);
        return $this->returnResult($result);
    }

    public function storeLabel(){
        $result = $this->customerService->storeLabel($this->request->all(), $this->own);
        return $this->returnResult($result);
    }

    public function deleteLabel($id){
        $result = $this->customerService->deleteLabel($id,$this->request->all(), $this->own);
        return $this->returnResult($result);
    }

    public function editLabel($id){
        $result = $this->customerService->editLabel($id,$this->request->all(), $this->own);
        return $this->returnResult($result);
    }

    public function relationLabel($customerIds){
        $result = $this->customerService->relationLabel($customerIds,$this->request->all(), $this->own);
        return $this->returnResult($result);
    }

}
