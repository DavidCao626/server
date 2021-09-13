<?php
namespace App\EofficeApp\Contract\Controllers;

use App\EofficeApp\Base\Controller;
use App\EofficeApp\Contract\Services\ContractService;
use App\EofficeApp\Contract\Services\ContractTypeService;
use Illuminate\Http\Request;

/**
 * 合同信息
 * @author linlm
 * @since  2017-12-13
 */
class ContractController extends Controller
{

    private $server;
    private $request;
    protected $bookRequest;

    public function __construct(
        Request $request,
        ContractService $contractService,
        ContractTypeService $contractTypeService
    ) {
        parent::__construct();
        $this->server  = $contractService;
        $this->contractTypeService  = $contractTypeService;
        $this->request = $request;
        $userInfo      = $this->own;
        $this->userId  = $userInfo['user_id'];
    }


    /**
     * 合同列表接口
     *
     *  @apiTitle 获取合同列表
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
     *          "project_id":[81]      // 项目id查询
     *          "customer_id":[81]      // 客户id查询
     *          "status":[1],          // 合同状态(0-草稿，1-正式合同)
     *          "recycle_status":[0]   // 是否删除(0-未删除，1-已删除)
     *          "title":['111','like'] // 合同标题
     *          "main_id": [[32],'in'] // 主合同查询,
     *          "a_user": ['上海科技','like'] // 甲方信息查询,
     *          "b_user": ['上海科技','like'] // 乙方信息查询,
     *          "a_linkman": ['许三亩','like'] // 甲方联系人信息查询,
     *          "b_linkman": ['许三亩','like'] // 乙方联系人信息查询,
     *    }
     * }
     *
     * @success {boolean} status(1) 接入成功
     *
     * @successExample {json} Success-Response:
     * {
     *      "status": 1,
     *      "data": [
     *          [
     *          "number" : 'HT202022222',  // 合同编号
                "title" : '合同标题',       // 合同标题
     *          "type_id": 1,              // 合同分类
     *          "main_id" : 1,              // 主合同
     *          "target_name" : 'xxxx',     // 合同对象
     *          "money" : '1000',           // 合同金额
     *          "money_cp" : '一千',         // 合同大写
     *          "a_user" : 'xxxxx',         // 甲方
     *          "b_user" : 'admin',         // 乙方
     *          "a_address" : 'xxxx',       // 甲方地址
     *          "b_address" : 'xxx',        // 乙方地址
     *          "a_linkman" : 'xxx',        // 甲方联系人
     *          "b_linkman" : 'xxx',        // 乙方联系人
     *          "a_phone" : '18752525252',  // 甲方电话
     *          "b_phone" : '18752525252',  // 乙方电话
     *          "a_sign" : 'XXXX',          // 甲方签字
     *          "b_sign" : 'XXXX',          // 乙方签字
     *          "a_sign_time" : 'XXXX',     // 甲方签字时间
     *          "b_sign_time" : 'XXXX',     // 乙方签字时间
     *          "remarks" : 'XXXX',         // 备注
     *          "user_id" : 'admin',        // 跟进人
     *          "content" : 'xxxxx',        // 正文
     *          "status" : 0,               // 状态(0-草稿，1-正式合同)
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
    public function lists()
    {
        $input  = $this->request->all();
        $result = $this->server->contractLists($input, $this->own);
        return $this->returnResult($result);
    }
    public function myLists()
    {
        $input  = $this->request->all();
        $result = $this->server->contractMyLists($input, $this->own);
        return $this->returnResult($result);
    }

    /**
     * 合同新建
     *
     * @param {string} contract 合同主体信息

     *
     * @paramExample {string} 参数示例
     * {
     *     contract : {
     *      number: "HT202008100001",  合同编号
     *      title: "xxx",                 合同标题(必填)
     *      type_id: "1",               合同类型(必填)
     *      main_id: "12",              主合同
     *      target_name: "合同对象",     合同对象
     *      money: 1500,                合同金额
     *      a_user: 'xxx',               甲方
     *      b_user: 'xxx',               乙方
     *      a_address: 'xxx',            甲方地址
     *      b_address: 'xxx',            乙方地址
     *      b_address: 'xxx',            乙方地址
     *      a_linkman: 'xxx',            甲方联系人
     *      b_linkman: 'xxx',            乙方联系人
     *      a_phone: 'xxx',              甲方电话
     *      b_phone: 'xxx',              乙方电话
     *      a_sign: 'xxx',              甲方签字
     *      b_sign: 'xxx',              乙方签字
     *      a_sign_time: '2019-10-10',   甲方签字时间
     *      b_sign_time: '2019-10-10',   乙方签字时间
     *      remarks: 'sssssssssssssxx',  备注
     *      user_id: 'admin',            跟进人
     *      content: 'xxxxx',            正文
     *      outsourceForEdit : 1,
     *  }
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

    public function store()
    {
        $input  = $this->request->all();
        $result = $this->server->storeContract($input, $this->userId);
        return $this->returnResult($result);
    }

    public function storeProject($table)
    {
        $input  = $this->request->all();
        $result = $this->server->storeProject($table,$input, $this->own);
        return $this->returnResult($result);
    }

    /**
     * 更新合同信息
     *
     * @apiTitle 更新合同信息
     * @param {string} contract 合同主体信息
     *
     * @paramExample {string} 参数示例
     * {
     *   contract :{
     *      number: "HT202008100001",  合同编号
     *      title: "xxx",                 合同标题
     *      type_id: "1",               合同类型
     *      main_id: "12",              主合同
     *      target_name: "合同对象",     合同对象
     *      money: 1500,                合同金额
     *      a_user: 'xxx',               甲方
     *      b_user: 'xxx',               乙方
     *      a_address: 'xxx',            甲方地址
     *      b_address: 'xxx',            乙方地址
     *      b_address: 'xxx',            乙方地址
     *      a_linkman: 'xxx',            甲方联系人
     *      b_linkman: 'xxx',            乙方联系人
     *      a_phone: 'xxx',              甲方电话
     *      b_phone: 'xxx',              乙方电话
     *      a_sign: 'xxx',              甲方签字
     *      b_sign: 'xxx',              乙方签字
     *      a_sign_time: '2019-10-10',   甲方签字时间
     *      b_sign_time: '2019-10-10',   乙方签字时间
     *      remarks: 'sssssssssssssxx',  备注
     *      user_id: 'admin',            跟进人
     *      content: 'xxxxx',            正文
     *      outsourceForEdit : 1,
     *      ........
     *  }
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

    public function update($id)
    {
        $input  = $this->request->all();
        $result = $this->server->updateContract($id, $input, $this->own);
        return $this->returnResult($result);
    }

    public function destory($id)
    {
        $result = $this->server->deleteContract($id, $this->own);
        return $this->returnResult($result);
    }

    /**
     * 删除合同信息
     *
     *
     * @apiTitle 删除合同信息
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
    public function recycle($id)
    {
        $result = $this->server->recycleContract($id, $this->own);
        return $this->returnResult($result);
    }

    public function recover($id)
    {
        $result = $this->server->recoverContract($id,$this->request->all());
        return $this->returnResult($result);
    }

    public function modifyRelation($id)
    {
        $input = $this->request->all();
        $result = $this->server->modifyRelation($id, $input, $this->own);
        return $this->returnResult($result);
    }

    public function show($id)
    {
        $result = $this->server->showContract($id, $this->own,$this->request->all());
        return $this->returnResult($result);
    }

    public function number()
    {
        $result = $this->server->getNumber();
        return $this->returnResult($result);
    }

    public function typeLists()
    {
        $input  = $this->request->all();
        $result = $this->server->typeLists($input);
        return $this->returnResult($result);
    }

    public function myTypeLists()
    {
        $input  = $this->request->all();
        $result = $this->server->myTypeLists($input, $this->own);
        return $this->returnResult($result);
    }

    public function typeStore()
    {
        $input  = $this->request->all();
        $result = $this->server->typeStore($input);
        return $this->returnResult($result);
    }

    public function typeShow($id)
    {
        $result = $this->server->typeShow($id);
        return $this->returnResult($result);
    }

    public function typeUpdate($id)
    {
        $input  = $this->request->all();
        $result = $this->server->typeUpdate($id, $input);
        return $this->returnResult($result);
    }

    public function typeDestory($id)
    {
        $result = $this->server->typeDestory($id);
        return $this->returnResult($result);
    }

    public function childProjects($id)
    {
        $result = $this->server->childProjects($id);
        return $this->returnResult($result);
    }

    public function menus($id)
    {
        $result = $this->server->menus($id, $this->own);
        return $this->returnResult($result);
    }

    public function allMenus(){
        $result = $this->server->allMenus($this->request->all(),$this->own);
        return $this->returnResult($result);
    }

    // 切换合同详情菜单显示
    public function toggleCustomerMenus($key)
    {
        $params = $this->request->all();
        $result = $this->server->toggleCustomerMenus($key, $this->own, $params);
        return $this->returnResult($result);
    }

    // 分享合同
    public function shareContract($id)
    {
        $result = $this->server->shareContract($id, $this->request->all(), $this->own);
        return $this->returnResult($result);
    }

    // 获取合同分享人员
    public function getShare($id)
    {
        $result = $this->server->getShare($id, $this->request->all(), $this->own);
        return $this->returnResult($result);
    }

    // 获取当前人员分享的合同
    public function getShareListId()
    {
        $result = $this->server->getShareListId($this->own);
        return $this->returnResult($result);
    }


    public function typeList()
    {
        $result = $this->server->typeList($this->own);
        return $this->returnResult($result);
    }

    public function contractStatistics($id){
        $result = $this->server->contractStatistics($this->request->all(),$id, $this->own);
        return $this->returnResult($result);
    }

    public function contractOrder($id){
        $result = $this->server->contractOrder($this->request->all(),$id, $this->own);
        return $this->returnResult($result);
    }

    public function contractRemind($id){
        $result = $this->server->contractRemind($this->request->all(),$id, $this->own);
        return $this->returnResult($result);
    }

    public function contractLogLists($id){
        $result = $this->server->logLists($id,$this->request->all(), $this->own);
        return $this->returnResult($result);
    }

    // 统计各个分类下的合同数量报表
    public function typeReport($type){
        $result = $this->server->typeReport($type,$this->request->all(), $this->own);
        return $this->returnResult($result);
    }

    // 所有合同结算统计的总支出，，总收入款
    public function projectReport($type){
        $result = $this->server->projectReport($type, $this->request->all(), $this->own);
        return $this->returnResult($result);
    }

    // 合同金额统计
    public function contractMoney(){
        $result = $this->server->contractMoney($this->request->all(), $this->own);
        return $this->returnResult($result);
    }

    public function userSelector(){
        $result = $this->server->userSelector($this->request->all(),$this->own);
        return $this->returnResult($result);
    }

    public function setRelationFields($id){
        $result = $this->contractTypeService->setRelationFields($id,$this->request->all(),$this->own);
        return $this->returnResult($result);
    }

    public function setDataPermission($id){
        $result = $this->contractTypeService->setDataPermission($id,$this->request->all(),$this->own);
        return $this->returnResult($result);
    }

    public function addGroup(){
        $result = $this->contractTypeService->addGroup($this->request->all(), $this->own);
        return $this->returnResult($result);
    }

    public function groupList(){
        $result = $this->contractTypeService->groupList($this->request->all(), $this->own);
        return $this->returnResult($result);
    }

    public function groupDetail($id){
        $result = $this->contractTypeService->groupDetail($id,$this->request->all(), $this->own);
        return $this->returnResult($result);
    }

    public function groupEdit($id){
        $result = $this->contractTypeService->groupEdit($id,$this->request->all(), $this->own);
        return $this->returnResult($result);
    }

    public function groupDelete($id){
        $result = $this->contractTypeService->groupDelete($id,$this->request->all(), $this->own);
        return $this->returnResult($result);
    }

    public function monitorGroupList(){
        $result = $this->contractTypeService->monitorGroupList($this->request->all(), $this->own);
        return $this->returnResult($result);
    }

    public function monitorGroupDetail($id){
        $result = $this->contractTypeService->monitorGroupDetail($id,$this->request->all(), $this->own);
        return $this->returnResult($result);
    }

    public function monitorGroupEdit($id){
        $result = $this->contractTypeService->monitorGroupEdit($id,$this->request->all(), $this->own);
        return $this->returnResult($result);
    }

    public function monitorAddGroup(){
        $result = $this->contractTypeService->monitorAddGroup($this->request->all(), $this->own);
        return $this->returnResult($result);
    }

    public function monitorGroupDelete($id){
        $result = $this->contractTypeService->monitorGroupDelete($id,$this->request->all(), $this->own);
        return $this->returnResult($result);
    }

    public function projectList(){
        $input  = $this->request->all();
        $result = $this->server->projectList($input, $this->own);
        return $this->returnResult($result);
    }
}
