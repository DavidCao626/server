<?php
namespace App\EofficeApp\Menu\Permissions;
class MenuPermission
{
    public function __construct()
    {
        $this->userMenuService = 'App\EofficeApp\Menu\Services\UserMenuService';
        $this->menuRepository         = 'App\EofficeApp\Menu\Repositories\MenuRepository';
        $this->userMenuRepository     = 'App\EofficeApp\Menu\Repositories\UserMenuRepository';
    }
     /**
     * 验证编辑个性菜单权限
     * @param type $own // 当前用户信息
     * @param type $data // 前端提交的数据或参数
     * @param type $urlData // restful api 上带的参数
     * @return boolean
     */
    public function editCustomMenu($own, $data, $urlData)
    {
        if(!isset($data['menu_id']) || empty($data['menu_id'])) {
            return ['code' => ['0x009003', 'menu']];
        }
        $menu_id = $data['menu_id'];
        $menus =  app($this->userMenuService)->getMenusIncludeHide($own['user_id']);
        if(!in_array($menu_id,$menus['menu'])){
            return ['code' => ['0x009002', 'menu']];
        }
        return true;
    }
    /**
     * 验证删除个性菜单权限
     * @param type $own // 当前用户信息
     * @param type $data // 前端提交的数据或参数
     * @param type $urlData // restful api 上带的参数
     * @return boolean
     */
    public function deleteCustomMenu($own, $data, $urlData)
    {
        if(!isset($urlData['menu_id']) || empty($urlData['menu_id'])) {
            return ['code' => ['0x009003', 'menu']];
        }
        $menu_id = $urlData['menu_id'];
        $menus =  app($this->userMenuService)->getMenusIncludeHide($own['user_id']);
        $menu = app($this->menuRepository)->getSingleMenu($menu_id);
        if(!in_array($menu_id,$menus['menu']) || $menu->menu_from != 2){
            return ['code' => ['0x009002', 'menu']];
        }
        return true;
    }
    /**
     * 验证获取菜单信息
     * @param type $own // 当前用户信息
     * @param type $data // 前端提交的数据或参数
     * @param type $urlData // restful api 上带的参数
     * @return boolean
     */
    public function getMenuInfoByMenuId($own, $data, $urlData)
    {
        if(!isset($urlData['menu_id']) || empty($urlData['menu_id'])) {
            return ['code' => ['0x009003', 'menu']];
        }
        $menu_id = $urlData['menu_id'];
        $menus =  app($this->userMenuService)->getMenusIncludeHide($own['user_id']);
        $menu = app($this->menuRepository)->getSingleMenu($menu_id);
        if($menu->menu_from == 2){
            if(!in_array($menu_id,$menus['menu'])){
                return ['code' => ['0x009002', 'menu']];
            }
        }
        return true;
    }
     /**
     * 验证隐藏菜单操作权限
     * @param type $own // 当前用户信息
     * @param type $data // 前端提交的数据或参数
     * @param type $urlData // restful api 上带的参数
     * @return boolean
     */
    public function setUserMenu($own, $data, $urlData)
    {
        if(!isset($data['menu_id']) || empty($data['menu_id'])) {
            return ['code' => ['0x009003', 'menu']];
        }
        $menu_id = $data['menu_id'];
        $menus =  app($this->userMenuService)->getMenusIncludeHide($own['user_id']);
        if(!in_array($menu_id,$menus['menu'])){
            return ['code' => ['0x009002', 'menu']];
        }
        return true;
    }

     /**
     * 验证删除菜单操作权限
     * @param type $own // 当前用户信息
     * @param type $data // 前端提交的数据或参数
     * @param type $urlData // restful api 上带的参数
     * @return boolean
     */
    public function deleteMenu($own, $data, $urlData)
    {
        if(!isset($urlData['menu_id']) || empty($urlData['menu_id'])) {
            return ['code' => ['0x009003', 'menu']];
        }
        $menu_id = $urlData['menu_id'];
        $menu = app($this->menuRepository)->getSingleMenu($menu_id);
        if($menu->menu_from == 2){
            return ['code' => ['0x009002', 'menu']];
        }
        return true;
    }

     /**
     * 验证排序菜单操作权限
     * @param type $own // 当前用户信息
     * @param type $data // 前端提交的数据或参数
     * @param type $urlData // restful api 上带的参数
     * @return boolean
     */
    public function sortMenu($own, $data, $urlData)
    {
        if(!isset($urlData['user_id']) || empty($urlData['user_id'])) {
            return ['code' => ['0x009002', 'menu']];
        }
        $userId = $urlData['user_id'];
        if($userId != $own['user_id']){
            return ['code' => ['0x009002', 'menu']];
        }
        return true;
    }
    
}
