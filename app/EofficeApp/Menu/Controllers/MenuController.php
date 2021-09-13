<?php

namespace App\EofficeApp\Menu\Controllers;

use Illuminate\Http\Request;
use App\EofficeApp\Base\Controller;
use App\EofficeApp\Menu\Requests\MenuRequest;
use App\EofficeApp\Menu\Services\UserMenuService;

/**
 * 系统菜单控制:接受参数并返回结果
 *
 * @author: 喻威
 *
 * @since：2015-10-19
 *
 */
class MenuController extends Controller {

    public function __construct(
    Request $request, UserMenuService $userMenuService, MenuRequest $menuRequest
    ) {
        parent::__construct();
        $this->userMenuService = $userMenuService;
        $this->menuRequest = $request;
        $this->formFilter($request, $menuRequest);
    }

    //获取系统菜单树
    public function getMenuTree($menu_parent) {
        $result = $this->userMenuService->getMenuTree($menu_parent, $this->menuRequest->all(), $this->own['menus']);
        return $this->returnResult($result);
    }
    /**
     * 获取菜单列表数据
     *
     * @apiTitle 获取菜单列表
     * @param {json} search 查询条件
     *
     * @paramExample {string} 参数示例
     * {
     *  search: {"menu_from":['2',"!="]}
     * }
     *
     * @success {boolean} status(1) 接入成功
     * @successExample {json} Success-Response:
     * {
     *      "status": 1,
     *      "data": [{
     *       "menu_id": 1, //菜单id
     *       "menu_name": "流程审批", //菜单名字
     *       "menu_name_zm": "lcgl", //菜单简拼
     *       "is_follow": 0, //1 继承 0 不继承 
     *       "menu_from": 1, //1 系统菜单 0 非系统菜单     
     *       "menu_order": 2, //排序 
     *       "menu_type": "favoritesMenu", //菜单类型
     *       "menu_param": "a:2:{s:3:\"url\";s:0:\"\";s:6:\"method\";s:6:\"_blank\";}", //菜单属性  
     *       "menu_parent": 0, //菜单父节点 0表示顶级
     *       "menu_path": "0", //菜单节点
     *       "temp_menu": 0, //升级确定关系字段
     *       "menu_sort": 0, //菜单拖拽排序
     *       "state_url": "", //自定义菜单参数
     *       "role_status": 0, //设置菜单，全部 1  非全部 0
     *       "has_children": 1, //是否有子菜单
     *       "category_id": 0,
     *       "deleted_at": null,
     *       "created_at": "-0001-11-30 00:00:00",
     *       "updated_at": "2018-03-30 10:10:01",
     *       "list": [] //子菜单列表 内容详情同上
     *       }]
     * }
     * @error {boolean} status(0) 接入失败
     * @error {array} errors 接入失败错误信息
     *
     * @errorExample {json} Error-Response:
     * { "status": 0,"errors":[{"code":"0x000003","message":"未知错误"}] }
     */
    public function searchMenuTree(){
        $result = $this->userMenuService->searchMenuTree($this->menuRequest->all());
        return $this->returnResult($result);
    }

    //getMenuInfoByMenuId
    //获取某个菜单具体
    public function getMenuInfoByMenuId($menu_id) {
        $result = $this->userMenuService->getMenuInfoByMenuId($menu_id);
        return $this->returnResult($result);
    }

    //设置菜单权限
    public function setMenu() {
        $result = $this->userMenuService->setMenu($this->menuRequest->all());
        return $this->returnResult($result);
    }

    //编辑菜单
    public function editMenu($menu_id) {
        $result = $this->userMenuService->editMenu($menu_id, $this->menuRequest->all());
        return $this->returnResult($result);
    }

    //删除菜单
    public function deleteMenu($menu_id) {
        if($menu_id < 1000){
            return $this->returnResult(['code' => ['0x009002', 'menu']]);
        }
        $result = $this->userMenuService->deleteMenu($menu_id);
        return $this->returnResult($result);
    }

     //删除个性设置菜单
     public function deleteCustomMenu($menu_id) {
        if($menu_id < 1000){
            return $this->returnResult(['code' => ['0x009002', 'menu']]);
        }
        $result = $this->userMenuService->deleteMenu($menu_id);
        return $this->returnResult($result);
    }

    //个性设置菜单获取
    public function getMenuList($menu_parent) {

        $result = $this->userMenuService->getMenuList($menu_parent, $this->menuRequest->all());
        return $this->returnResult($result);
    }

    //个性菜单设置
    public function setUserMenu() {
        $result = $this->userMenuService->setUserMenu($this->menuRequest->all());
        return $this->returnResult($result);
    }
     /**
     * 获取某个用户有权限的菜单
     *
      * @apiTitle 获取用户有权限的菜单
     * @success {boolean} status(1) 接入成功
     * @successExample {json} Success-Response:
     * {
     *      "status": 1,
     *      "data": {
     *       "module": [{
     *           "menu_id": 122, //菜单id
     *           "menu_name": "仓储管理", //菜单名字
     *           "menu_name_zm": "ccgl", //菜单简拼
     *           "has_children": 1, //是否有子菜单
     *           "item": [{ //子菜单
     *               "menu_id": 1015, //菜单id
     *               "menu_name": "定时菜单", //菜单名字
     *               "menu_name_zm": "dscd", //菜单简拼
     *               "has_children": 0, //是否有子菜单
     *               "menu_from": 0, //是否是系统菜单
     *               "menu_type": "customize", //菜单类型
     *               "menu_parent": 122, //菜单父节点
     *           }]
     *       }]
     *       "menu":[] 有权限的菜单id数组
     * }
     * @error {boolean} status(0) 接入失败
     * @error {array} errors 接入失败错误信息
     *
     * @errorExample {json} Error-Response:
     * { "status": 0,"errors":[{"code":"0x000003","message":"未知错误"}] }
     */
    //获取用户有权限的模块
    public function getUserMenus($user_id) {
        $result = $this->userMenuService->getUserMenus($user_id);
        return $this->returnResult($result);
    }

    //获取某个模块的菜单
    public function getSubMenu($menu_parent, $user_id) {
        $result = $this->userMenuService->getSubMenu($menu_parent, $user_id);
        return $this->returnResult($result);
    }

    //获取用户角色模块
    public function getRoleMenu($role_id) {
        $result = $this->userMenuService->getRoleMenu($role_id, 0);
        return $this->returnResult($result);
    }

    public function getRoleMenuByType($role_id, $type) {
        $result = $this->userMenuService->getRoleMenuTree($role_id, 0, $type);
        return $this->returnResult($result);
    }

    //设置用户角色
    public function setRoleMenu($role_id) {
        $result = $this->userMenuService->setRoleMenu($role_id, $this->menuRequest->all());
        return $this->returnResult($result);
    }

    //获取某个菜单下 子菜单的信息
    public function getUserMenuByMenuParent($menu_parent, $user_id) {
        $result = $this->userMenuService->getUserMenuByMenuParent($menu_parent, $user_id, $this->own['menus']);
        return $this->returnResult($result);
    }

    //获取某个菜单下兄弟菜单信息
    public function getMenuSibling($menu_id, $user_id) {
        $result = $this->userMenuService->getMenuSibling($menu_id, $user_id, $this->own['menus']);
        return $this->returnResult($result);
    }

    //获取某个菜单的拥有角色和用户
    public function getMenuRoleUserbyMenuId($menu_id) {
        $result = $this->userMenuService->getMenuRoleUserbyMenuId($menu_id);
        return $this->returnResult($result);
    }

    // 设置统一排序
    public function setUserMenuOrder() {
        $result = $this->userMenuService->setUserMenuOrder();
        return $this->returnResult($result);
    }

    public function addSystemMenu() {
        $result = $this->userMenuService->addSystemMenu();
        return $this->returnResult($result);
    }

    //获取用户有菜单的列表（all）
    public function getUseMenuList($user_id) {
        $result = $this->userMenuService->getUseMenuList($user_id, $this->menuRequest->all());
        return $this->returnResult($result);
    }

    //获取用户新建类的菜单
    public function getCreateMenuList($user_id) {
        $result = $this->userMenuService->getCreateMenuList($user_id, $this->menuRequest->all());
        return $this->returnResult($result);
    }

    //删除用户的菜单配置信息
    public function deleteUserMenuByUserId($userId) {
        $result = $this->userMenuService->deleteUserMenuByUserId($userId);
        return $this->returnResult($result);
    }

    //个性自定义菜单增加
    public function setCustomMenu() {
        $result = $this->userMenuService->setCustomMenu($this->menuRequest->all());
        return $this->returnResult($result);
    }

    public function getAllMenuList($menu_parent) {
        $result = $this->userMenuService->getAllMenuList($menu_parent, $this->menuRequest->all());
        return $this->returnResult($result);
    }

    //编辑自定义菜单
    public function editCustomMenu() {
        $result = $this->userMenuService->editCustomMenu($this->menuRequest->all());
        return $this->returnResult($result);
    }

    public function defaultMenu($user_id) {
        $result = $this->userMenuService->defaultMenu($user_id);
        return $this->returnResult($result);
    }

    //yonghu  paixu
    public function sortMenu($user_id) {
        $result = $this->userMenuService->sortMenu($this->menuRequest->all(), $user_id);
        return $this->returnResult($result);
    }

    //菜单子类配置
    public function getMenuConfig() {
        $result = $this->userMenuService->getMenuConfig($this->menuRequest->all());
        return $this->returnResult($result);
    }

    public function getMenuInfo() {
        $result = $this->userMenuService->getMenuInfo($this->menuRequest->all(), $this->own['menus']);
        return $this->returnResult($result);
    }

    public function globalSetMenu() {
        $result = $this->userMenuService->globalSetMenu();
        return $this->returnResult($result);
    }

    public function setReportMenu(){
    	return $this->returnResult($this->userMenuService->setReportMenu());
    }

    public function getMenuByIdArray(){
        return $this->returnResult($this->userMenuService->getMenuByIdArray($this->menuRequest->all()));
    }
    public function judgeMenuPermission($menu_id){
        return $this->returnResult($this->userMenuService->judgeMenuPermission($menu_id));
    }
    public function setMenuSort()
    {
       return $this->returnResult($this->userMenuService->setMenuSort($this->menuRequest->all()));
    }
    public function getMenuSort()
    {
        return $this->returnResult($this->userMenuService->getMenuSort($this->menuRequest->all()));
    }
    public function deleteMenuSort($id)
    {   
        return $this->returnResult($this->userMenuService->deleteMenuSort($id));
    }
    public function getMenuSortTree()
    {
        return $this->returnResult($this->userMenuService->getMenuSortTree($this->menuRequest->all()));
    }
    public function exportMenu()
    {   
        return $this->returnResult($this->userMenuService->exportMenu());
    }

}
