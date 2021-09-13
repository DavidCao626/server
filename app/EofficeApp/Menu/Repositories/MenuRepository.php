<?php

namespace App\EofficeApp\Menu\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Lang\Services\LangService;
use App\EofficeApp\Menu\Entities\MenuEntity;
use DB;
use Lang;
use Schema;

/**
 * 系统功能菜单资源库
 *
 * @author:喻威
 *
 * @since：2015-10-19
 *
 */
class MenuRepository extends BaseRepository
{

    public function __construct(
        MenuEntity $entity, LangService $langService
    ) {
        parent::__construct($entity);
        $this->langService = $langService;
    }

    public function getMenuData($where, $field = ["*"], $from = '')
    {
        if (isset($where['menu_name']) && isset($where['menu_name'][0]) && $where['menu_name'][1] == "like") {
            $where = $this->transferMenuName($where);
        }
        $query = $this->entity->select($field)->wheres($where);
        if ($from == 'search') {
            $query = $query->where('menu_parent', '!=', 0)->where('has_children', '=', 0);
        }
        return $query->orderBy('menu_parent', 'asc')->orderBy('has_children', 'desc')->orderBy('menu_order', 'asc')->get()->toArray();
    }
    public function getAllMenus()
    {
        return $this->entity->where('menu_from',1)->orderBy('menu_id', 'asc')->get()->toArray();
    }
    //获取下级菜单
    public function getSubordinateMenu($menu_id)
    {
        return $this->entity->whereRaw("FIND_IN_SET(?, menu_path )", [$menu_id])->get()->toArray();
    }

    //根据排序
    public function getMenuByMenuOrder($where, $param, $field = ["*"])
    {
        if (isset($param['search']['menu_name']) && isset($param['search']['menu_name'][0]) && $param['search']['menu_name'][1] == "like") {
            $param = $this->transferMenuName($param);
        }
        $con = [];
        if (isset($param["search"])) {
            $con = $param["search"];
        }

        return $this->entity->select($field)->wheres($where)->wheres($con)->orderBy('menu_order', 'asc')->orderBy('menu_id', 'asc')->get()->toArray();
    }

    public function searchMenuTree($param, $noPerimission = [])
    {
        $default = [
            'search' => [],
            'page' => 0,
            'limit' => config('eoffice.pagesize'),
        ];

        $param = array_merge($default, array_filter($param));
        if (isset($param['search']['menu_name']) && isset($param['search']['menu_name'][0]) && $param['search']['menu_name'][1] == "like") {
            $param = $this->transferMenuName($param);
        }

        $query = $this->entity
            ->wheres($param['search'])
            ->where("menu_from", "!=", 2);
        if (isset($param['type']) && $param['type'] == "parent") {
            $query = $query->where('menu_parent', 0);
        }
        if (!empty($noPerimission)) {
            $query = $query->whereNotIn('menu_id', $noPerimission);
        }
        return $query->parsePage($param['page'], $param['limit'])
            ->get()->toArray();
    }
    public function searchMenuTreeTotal($param, $noPerimission = [])
    {
        $default = [
            'search' => [],
        ];

        $param = array_merge($default, array_filter($param));
        if (isset($param['search']['menu_name']) && isset($param['search']['menu_name'][0]) && $param['search']['menu_name'][1] == "like") {
            $param = $this->transferMenuName($param);
        }
        $query = $this->entity
            ->wheres($param['search'])
            ->where("menu_from", "!=", 2);
        if (isset($param['type']) && $param['type'] == "parent") {
            $query = $query->where('menu_parent', 0);
        }
        if (!empty($noPerimission)) {
            $query = $query->whereNotIn('menu_id', $noPerimission);
        }
        return $query->count();
    }

    public function transferMenuName($param)
    {
        if (isset($param['search'])) {
            $menu_name = $param['search']['menu_name'][0];
            $local = Lang::getLocale();
            $langTable = $this->langService->getLangTable($local);
            $menus = DB::table($langTable)->Where('lang_value', 'like', '%' . $menu_name . '%')->where("table", "menu")->get();
            $menu_name = [];
            foreach ($menus as $menu) {
                $menu_name[] = $menu->lang_key;
            }
            $param['search']['menu_name'] = [$menu_name, 'in'];
        } else {
            $menu_name = $param['menu_name'][0];
            $local = Lang::getLocale();
            $langTable = $this->langService->getLangTable($local);
            $menus = DB::table($langTable)->Where('lang_value', 'like', '%' . $menu_name . '%')->where("table", "menu")->get();
            $menu_name = [];
            foreach ($menus as $menu) {
                $menu_name[] = $menu->lang_key;
            }
            $param['menu_name'] = [$menu_name, 'in'];
        }

        return $param;

    }

    //获取菜单
    public function getMenuList($where, $flag, $take = 0)
    {

        $query = $this->entity;

        if ($flag == 1) {
            $query = $query->select(['menu.*', 'user_menu_id', 'user_menu.menu_order as order', 'user_menu.is_show'])->leftJoin('user_menu', function ($join) {
                $join->on("menu.menu_id", '=', 'user_menu.menu_id');
            })->wheres($where)->orderBy('user_menu.menu_order', 'asc')->orderBy('menu.menu_id', 'asc');
        } else if ($flag == 0) {
            $query = $query->select(['menu.menu_id', 'menu.menu_name', 'menu.menu_name_zm', 'user_menu.menu_frequency'])->leftJoin('user_menu', function ($join) {
                $join->on("menu.menu_id", '=', 'user_menu.menu_id');
            })->wheres($where)->orderBy('menu.menu_sort', 'desc'); //模块排序
        } else if ($flag == 2) {
            $query = $query->select(['menu.menu_id', 'menu.menu_name'])->leftJoin('user_menu', function ($join) {
                $join->on("menu.menu_id", '=', 'user_menu.menu_id');
            })->wheres($where)->orderBy('user_menu.menu_order', 'asc')->orderBy('menu.menu_id', 'asc')->take($take);
        }
        return $query->get()->toArray();
    }

    //获取带条件的菜单
    public function getMenuByWhere($where, $fields = ["*"], $withFlowModuleFactory = true)
    {
        $query = $this->entity;
        $query = $query->select($fields)
            ->multiWheres($where);
        if ($withFlowModuleFactory) {
            $query = $query->with(['menuHasOneFlowModuleFactory' => function ($query) {
                $query->select(['module_id', 'flow_module_factory.state_url']);
            }]);
        }
        $query = $query->get()->toArray();
        return $query;
    }
    public function getSingleMenu($id)
    {
        $query = $this->entity;
        $query = $query->where("menu_id", $id)
            ->with(['menuHasOneFlowModuleFactory' => function ($query) {
                $query->select(['module_id', 'flow_module_factory.state_url']);
            }])
            ->first();
        return $query;
    }

    //获取间断的ID
    public function getInterruptID($start)
    {
        //范围1000-1999
        //使用DB
        //select * from menu_count where menu_id > $start and menu_id not in (select menu_id from menu ) limit 1
        $sql = "select * from menu_count where menu_id >= $start and menu_id not in (select menu_id from menu ) ";
        $menuData = DB::select($sql);
        return $menuData[0]->menu_id;
    }

    //获取某个菜单的所有下级（含本身）
    public function getJunior($menuID)
    {
        return $this->entity->WhereRaw('(find_in_set(?,menu_path))', [$menuID])
            ->orWhere('menu_id', $menuID)->get()->toArray();
    }

    //获取2000之外最大的菜单ID
    public function getMaxMenuId()
    {
        return $this->entity->max('menu_id');
    }

    public function getAllMenuOrderByUserMenuOrder($menu, $user_id)
    {
        if (!$menu) {
            return [];
        }
        return $this->entity->select(DB::raw('LENGTH(menu.menu_path) as orderBy, menu.*'))->leftJoin('user_menu', function ($join) {
            $join->on("menu.menu_id", '=', 'user_menu.menu_id');
        })
            ->where("user_menu.user_id", $user_id)
            ->with(['menuHasOneFlowModuleFactory' => function ($query) {
                $query->select(['module_id', 'flow_module_factory.state_url']);
            }])
        //->whereIn("menu.menu_id", $menu)
            ->orderBy('orderBy', 'asc')->orderBy('user_menu.menu_order', 'asc')->orderBy('menu_id', 'asc')->groupBy('user_menu.menu_id')->get()->toArray();
    }

    //自定义菜单
    public function getCustomByUserId($menu, $user_id, $menuParent)
    {
        if (!$menu) {
            return [];
        }

        return $this->entity->select(["menu.*", "user_menu.is_show"])->leftJoin('user_menu', function ($join) {
            $join->on("menu.menu_id", '=', 'user_menu.menu_id');
        })
            ->where("user_menu.user_id", $user_id)
            ->where("menu.menu_parent", $menuParent)
            ->with(['menuHasOneFlowModuleFactory' => function ($query) {
                $query->select(['module_id', 'flow_module_factory.state_url']);
            }])
        //->whereIn("menu.menu_id", $menu)
            ->orderBy('user_menu.menu_order', 'asc')->orderBy('menu.menu_id', 'asc')->get()->toArray();
    }

    //获取没有拼音的数据
    public function getMenuPy()
    {
        return $this->entity->select(["menu_id", "menu_name"])->get()->toArray();
    }
    //删除菜单时删除自定义字段菜单相关信息
    public function deleteCustomFields($menus)
    {
        if ($menus && is_array($menus)) {
            DB::table("custom_menu_config")->whereIn("menu_code", $menus)->delete();
            DB::table("custom_reminds")->whereIn('field_table_key', $menus)->delete();
            if (Schema::hasTable('custom_template')) {
                DB::table("custom_template")->whereIn('table_key', $menus)->delete();
                DB::table("custom_layout_setting")->whereIn('table_key', $menus)->delete();
            }
            $fields = DB::table("custom_fields_table")->whereIn("field_table_key", $menus)->get();
            if (!empty($fields)) {
                foreach ($fields as $k => $v) {
                    $field_table_key = $v->field_table_key;
                    $field_options = json_decode($v->field_options);
                    if (!empty($field_options) && !empty($field_options->parentField)) {
                        $subName = "custom_data_" . $field_table_key . "_" . $field_options->parentField;
                        Schema::dropIfExists($subName);
                    }
                    $tableName = "custom_data_" . $field_table_key;
                    Schema::dropIfExists($tableName);
                }
                DB::table("custom_fields_table")->whereIn("field_table_key", $menus)->delete();
            }
        }
    }

    public function getChildrensCount($menu_id)
    {
        return $this->entity->where("menu_parent", $menu_id)->count();
    }
}
