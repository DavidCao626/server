<?php

namespace App\EofficeApp\Menu\Repositories;

use DB;
use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Menu\Entities\RoleMenuEntity;

/**
 * 系统功能菜单资源库
 *
 * @author:喻威
 *
 * @since：2015-10-19
 *
 */
class RoleMenuRepository extends BaseRepository {

    public function __construct(
    RoleMenuEntity $entity
    ) {
        parent::__construct($entity);
    }

    public function getMenuDetail($where) {
        return $this->entity->wheres($where)->get()->toArray();
    }

    //获取菜单对应菜单组
    public function getMenusGroupByMenuId($where) {
        return $this->entity->wheres($where)->get()->toArray();
    }

    //获取菜单对应的角色组
    public function getMenusGroupByRoleId($where) {
        return $this->entity->wheres($where)->groupBy('role_id')->get()->toArray();
    }

    //获取用户
    public function getUsersByMenuId($menuId) {
        //select user_id from role_menu left join user_menu on user_menu.menu_id = role_menu.menu_id where role_menu.menu_id = 1 and is_show = 1 group by user_id
        $result = DB::table('user_menu')->select(["user_menu.user_id"])
                        ->Join('user', 'user_menu.user_id', '=', 'user.user_id')
                        ->where("user_menu.is_show", 1)
                        ->where("user_menu.menu_id",$menuId)
                        ->where("user.user_accounts", "!=", "")
                        ->groupBy("user_menu.user_id")->get();
        $result = json_decode(json_encode($result),true);
        return $result;
        // return $this->entity->select(["user_menu.user_id"])
        //                 ->leftJoin('user_menu', 'user_menu.menu_id', '=', 'role_menu.menu_id')
        //                 ->leftJoin('user', 'user_menu.user_id', '=', 'user.user_id')
        //                 ->where("role_menu.menu_id", $menuId)
        //                 ->where("user_menu.is_show", 1)
        //                 ->where("user.user_accounts", "!=", "")
        //                 ->groupBy("user_menu.user_id")->get()->toArray();
    }

    public function batchInsert($insertData) {
        return $this->entity->insert($insertData);
    }

}
