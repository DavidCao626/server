<?php

namespace App\EofficeApp\Menu\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Menu\Entities\UserMenuEntity;
use DB;

/**
 * 用户资源库
 *
 * @author:喻威
 *
 * @since：2015-10-19
 *
 */
class UserMenuRepository extends BaseRepository
{

    public function __construct(
        UserMenuEntity $entity
    ) {
        parent::__construct($entity);
    }

    public function getMenuByWhere($where)
    {
        return $this->entity->wheres($where)->orderBy("menu_order", "asc")->get()->toArray();
    }
    //获取用户个性菜单
    public function getCustomMenu($where)
    {
        return $this->entity->select("user_menu.menu_id")
            ->leftJoin('menu', 'user_menu.menu_id', '=', 'menu.menu_id')
            ->where("user_menu.user_id", $where)->where("menu.menu_from", 2)->get()->toArray();
    }

    public function deleteCustomMenu($user_id)
    {
        return $this->entity->leftJoin('menu', 'user_menu.menu_id', '=', 'menu.menu_id')
            ->where("user_menu.user_id", $user_id)->where("menu.menu_from", 2)->delete();
    }

    //获取用户分组下的ID
    public function getUserByGroup($where)
    {
        return $this->entity->select(['user_id'])->wheres($where)->groupBy("user_id")->get()->toArray();
    }

    public function batchInsert($insertData)
    {
        return $this->entity->insert($insertData);
    }

    public function getCustomMenuId($user_id)
    {
        return $this->entity->select(['user_menu.menu_id'])
            ->leftJoin('menu', 'user_menu.menu_id', '=', 'menu.menu_id')
            ->where("user_menu.user_id", $user_id)
            ->where("menu.menu_from", "=", "2")->get()->toArray();
    }

    public function getUserMenuByMenuId($menuId, $fields)
    {
        return $this->entity->select($fields)->where("menu_id", $menuId)->get();
    }
    /**
     * 统一常用菜单
     * @param array $menuIds
     */
    public function unityCommonMenu($menuIds = [])
    {
        $result = DB::table("user_menu")->update(['is_favorite' => 0]);
        
        if (is_array($menuIds) && !empty($menuIds)) {
            return $this->entity->whereIn("menu_id", $menuIds)->update(['is_favorite' => 1]);
        }
        
        return $result;
    }
    /**
     * 可能是废弃函数，后期可以删除
     * @param type $data
     * @return boolean
     */
    public function setMenuPortal($data)
    {
        $index_model = DB::table('system_params')->where("param_key", "index_model")->first();
        if (empty($index_model)) {
            DB::table('system_params')->insert(['param_key' => "index_model", 'param_value' => "portal"]);
        }
        $user_set_model = DB::table('system_params')->where("param_key", "user_set_model")->first();
        if (empty($index_model)) {
            DB::table('system_params')->insert(['param_key' => "user_set_model", 'param_value' => "0"]);
        }

        if (isset($data['index_model'])) {
            DB::table('system_params')->where("param_key", "index_model")->update(['param_value' => $data['index_model']]);
        }

        if (isset($data['user_set_model'])) {
            DB::table('system_params')->where("param_key", "user_set_model")->update(['param_value' => $data['user_set_model']]);
        }

        if (isset($data['menus'])) {

            if (!is_array($data['menus'])) {
                $data['menus'] = json_decode($data['menus']);
            }
            $menus = $data['menus'];

            DB::table("user_menu")->update(['is_favorite' => 0]);
            $this->entity->whereIn("menu_id", $menus)->update(['is_favorite' => 1]);

        }

        return true;

    }
    public function getUserCommonMenu($allMenuIds)
    {
        $menus = $this->entity->where("is_favorite", 1)->where("user_id", own('user_id'))->get()->toArray();
        $commonMenuIds = array_unique(array_column($menus, 'menu_id'));
        $realCommonMenuIds = array_intersect($commonMenuIds, $allMenuIds);
        $lastMenuId = [];
        if(!empty($realCommonMenuIds)){
            $lastMenuId = array_values($realCommonMenuIds);
        }
        return [
            'user_set_model' => get_system_param('user_set_model', 0),
            'menu_ids' => $lastMenuId
        ];
    }
    /**
     * 可能是废弃的函数
     * @param type $menu
     * @return type
     */
    public function getMenuPortal($menu)
    {
        $user_set_model         = DB::table('system_params')->where("param_key", "user_set_model")->first();
        $index_model            = DB::table('system_params')->where("param_key", "index_model")->first();
        $data['user_set_model'] = isset($user_set_model->param_value) ? $user_set_model->param_value : 0;
        $data['index_model']    = isset($index_model->param_value) ? $index_model->param_value : 0;
        $menus                  = $this->entity->where("is_favorite", 1)->where("user_id", own('user_id'))->get()->toArray();

        $data['menus']          = array_column($menus, 'menu_id');
        $data['menus']          = array_unique($data['menus']);
        foreach ($data['menus'] as $key => $value) {
            if (!in_array($value, $menu)) {
                    unset($data['menus'][$key]);
            }
        }
        $data['menus'] = array_values($data['menus']);
        return $data;
    }
    public function setFavorite($menu_id)
    {
        return $this->entity->where("user_id", own('user_id'))->where('menu_id', $menu_id)->update(['is_favorite' => 1]);
    }
    public function cancelFavorite($menu_id)
    {
        return $this->entity->where("user_id", own('user_id'))->where('menu_id', $menu_id)->update(['is_favorite' => 0]);
    }
    public function setUserCommonMenu($menuId, $loginUserId)
    {
        $this->entity->where("user_id", $loginUserId)->update(['is_favorite' => 0]);
        if(!empty($menuId)) {
            return $this->entity->where("user_id", $loginUserId)->whereIn('menu_id', $menuId)->update(['is_favorite' => 1]);
        }
        return true;
    }
}
