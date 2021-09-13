<?php

namespace App\EofficeApp\Charge\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Charge\Entities\ChargeListEntity;
use DB;

/**
 * 费用列表资源库
 *
 * @author:喻威
 *
 * @since：2015-10-19
 *
 */
class ChargeListRepository extends BaseRepository
{

    public function __construct(ChargeListEntity $entity)
    {
        parent::__construct($entity);
    }

    /**
     * 录入费用
     *
     * @param array  $data 费用参数
     *
     * @return  int
     *
     * @author 喻威
     *
     * @since 2015-10-20
     */
    public function addNewCharge($data)
    {

        return $this->entity->create($data)->charge_list_id;
    }

    /**
     * 通过ID 获取费用录入的详细
     *
     * @param     int $id
     *
     * @return arrary
     *
     * @author 喻威
     *
     * @since 2015-10-20
     */
    public function infoCharge($id)
    {
        $chargeResult = $this->entity->where('charge_list_id', $id)->get()->toArray();
        return $chargeResult;
    }

    /**
     * 编辑费用
     *
     * @param array $data
     *
     * @return bool
     *
     * @author 喻威
     *
     * @since 2015-10-20
     */
    public function editNewCharge($data)
    {
        foreach ($data as $k => $v) {
            if ($k == "charge_list_id") {
                continue;
            }
            $dataFinal[$k] = $v;
        }
        $chargeResult = $this->entity->where('charge_list_id', $data['charge_list_id'])
            ->update($dataFinal);

        return $chargeResult;
    }

    /**
     * 获取用户或者部门列表
     *
     * @param array $param
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-20
     */
    public function chargeListDetail($data)
    {
        $default = [
            'fields'   => ['charge_list.*', 'user.user_name', "charge_type_name", "dept_name", "user_status","undertake.user_name as undertake_user_name"],
            'page'     => 0,
            'limit'    => config('eoffice.pagesize'),
            'search'   => [],
            'order_by' => ['payment_date' => 'desc', 'charge_list.created_at' => 'desc'],
        ];
        if(isset($data['order_by']) && !isset($data['order_by']['charge_list.created_at'])){
            $data['order_by']['charge_list.created_at'] = isset($data['order_by']['payment_date']) ? $data['order_by']['payment_date'] : 'desc';
        }

        $param = array_merge($default, $data);
        if(isset($param['search']) && isset($param['search']['user_name'])){
            $param['search']['user.user_name'] = $param['search']['user_name'];
            unset($param['search']['user_name']);
        }

        $query = $this->entity->select($param['fields'])->leftJoin('user', function($join) {
            $join->on("charge_list.user_id", '=', 'user.user_id');
        })->leftJoin('user_system_info', function($join) use($param)  {
            $join->on("charge_list.undertake_user", '=', 'user_system_info.user_id');
        })->leftJoin('charge_type', function ($join) {
            $join->on("charge_type.charge_type_id", '=', 'charge_list.charge_type_id');
        })->leftJoin('department', function ($join) {
            $join->on("department.dept_id", '=', 'charge_list.undertake_dept');
        })->leftJoin('user as undertake', function ($join) {
            $join->on("charge_list.undertake_user", '=', 'undertake.user_id');
        });

        switch ($param["filter"]) {
            case 'year':
                if (isset($param['year']) && $param['year'] > 0) {
                    switch ($param["charge_fiter"]) {
                        case "T":
                            $query = $query->whereRaw("DATE_FORMAT(charge_list.payment_date, '%Y') = ?", [$param['year']]);
                            break;
                        case "S":
                            $query = $query->where("charge_list.charge_type_id", $param["charge_type"]);
                            break;
                        case "M":
                            if ($param['has_children']) {
                                // (无限级分类改动) 有子类的费用类型，需要查一下该类型本身有没有历史录入数据
                                $query = $query->where(function($query)use($param){
                                    $query->whereRaw('find_in_set(\''.intval($param["charge_type"]).'\',charge_type.type_level)')->orWhere("charge_list.charge_type_id", $param["charge_type"]);
                                });
                            } else {
                                $query = $query->where("charge_list.charge_type_id", $param["charge_type"]);
                            }
                            $query->whereRaw("DATE_FORMAT(charge_list.payment_date, '%Y') = ?", [$param['year']]);
                            break;
                        default:
                            $query = $query->whereRaw("DATE_FORMAT(charge_list.payment_date, '%Y-%m') = ?", [$param['year'] . "-" . $this->complementZero($param["charge_fiter"])])->where("charge_list.charge_type_id", $param["charge_type"]);
                            break;
                    }
                }
                break;
            case 'quarter':

                switch ($param["charge_fiter"]) {
                    case "T":
                        $query = $query->whereRaw("DATE_FORMAT(charge_list.payment_date, '%Y') = ?", [$param['year']]);
                        break;
                    case "M":
                        $query = $query->whereRaw("DATE_FORMAT(charge_list.payment_date, '%Y') = ?", [$param['year']]);
                        if ($param['has_children']) {
                            $query = $query->where(function($query)use($param){
                                $query->whereRaw('find_in_set(\''.intval($param["charge_type"]).'\',charge_type.type_level)')->orWhere("charge_list.charge_type_id", $param["charge_type"]);
                            });
                        } else {
                            $query = $query->where("charge_list.charge_type_id", $param["charge_type"]);
                        }
                        break;
                    default:
                        if ($param["charge_fiter"] < 5) {
                            $minMonth  = $param["charge_fiter"] * 3 - 2;
                            $maxMonth  = $param["charge_fiter"] * 3;
                            $startDate = $param['year'] . "-" . $this->complementZero($minMonth);
                            $endDate   = $param['year'] . "-" . $this->complementZero($maxMonth);
                            $query     = $query->whereRaw("DATE_FORMAT(charge_list.payment_date,  '%Y-%m') >= ?", [$startDate])
                                ->whereRaw("DATE_FORMAT(charge_list.payment_date, '%Y-%m') <= ?", [$endDate])
                                ->where("charge_list.charge_type_id", $param["charge_type"]);
                        } else if ($param["charge_fiter"] == 5) {
                            $query = $query->where("charge_list.charge_type_id", $param["charge_type"]);
                        }
                        break;
                }
                break;
            case 'month':
                $ym    = $param['year'] . "-" . $this->complementZero($param['month']);
                $query = $query->whereRaw("DATE_FORMAT(charge_list.payment_date, '%Y-%m') = ?", [$ym]);
                switch ($param["charge_fiter"]) {
                    case "T":
                        $query = $query->whereRaw("DATE_FORMAT(charge_list.payment_date, '%Y') = ?", [$param['year']]);
                        break;
                    case "M":
                        if ($param['has_children']) {
                            $query = $query->where(function($query)use($param){
                                $query->whereRaw('find_in_set(\''.intval($param["charge_type"]).'\',charge_type.type_level)')->orWhere("charge_list.charge_type_id", $param["charge_type"]);
                            });
                        } else {
                            $query = $query->where("charge_list.charge_type_id", $param["charge_type"]);
                        }
                        break;
                    default:
                        $ym1   = $param['year'] . "-" . $param['month'];
                        $month = date("t", strtotime("$ym1"));
                        if ($param["charge_fiter"] <= $month) {
                            $ymd   = $ym . "-" . $param["charge_fiter"];
                            $query = $query->whereRaw("DATE_FORMAT(charge_list.payment_date, '%Y-%m-%e') = ?", [$ymd])
                                ->where("charge_list.charge_type_id", $param["charge_type"]);
                        } else if ($param["charge_fiter"] > $month) {
                            $query = $query->where("charge_list.charge_type_id", $param["charge_type"]);
                        }
                        break;
                }

                break;
        }

        if(isset($data['viewUser']) && !empty($data['viewUser']) && $data['viewUser'] != 'all'  &&
            !(isset($data['flag']) && $data['flag'] == 'my' && $data['filter_type'] == 2)){
            $query = $query->whereIn('charge_list.user_id', $data['viewUser']);
        }

        if ($param['filter_type'] == 1) {
            if (isset($param['users']) && $param['users']) {
                if ($param['users'] != 'all') {
                    $query->whereIn('charge_list.user_id', $param['users']);
                }
            }
        } else {
            if ($param['set_type'] == 1) {
                $query = $query->where('charge_undertaker', 3);
            } elseif ($param['set_type'] == 2) {
                $query = $query->where('charge_undertaker', 2);
                if (!empty($param['set_id'])) {
                    $deptIds = $param['set_id'];
                    if (isset($param['has_depts']) && $param['has_depts'] == 1) {
                        //获取当前部门下所有部门
                        foreach ($param['set_id'] as $deptId) {
                            $deptIds = array_merge($deptIds, app('App\EofficeApp\System\Department\Services\DepartmentService')->getTreeIds($param['set_id']));
                        }
                    }
                    $query = $query->whereIn('undertake_dept', $deptIds);
                }
            } elseif ($param['set_type'] == 3) {
                $query = $query->where('charge_undertaker', 1);
                if (!empty($param['set_id'])) {
                    $query = $query->whereIn('undertake_user', $param['set_id']);
                }
            } elseif ($param['set_type'] == 5) {
                $query = $query->where('charge_undertaker', 4);
                if(isset($data['viewUser']) && !empty($data['viewUser']) && $data['viewUser'] != 'all'){
                    $query = $query->whereIn('charge_list.user_id', $data['viewUser']);
                }
                if (!empty($param['set_id'])) {
                    $query = $query->whereIn('project_id', $param['set_id']);
                }
            }
        }
         // 是否有项目权限
        if(!isset($param['power']) || $param['power'] == 0){
            $query = $query->whereNull('charge_list.project_id');
        } else {
            if (isset($param['projects']) && !empty($param['projects'])) {
                $query = $this->handleProjectQuery($query, $data);
            }
        }
        //承担者选择器搜索
        if (isset($param['name'])) {
            $name = $param['name'];
            $query = $query->where(function($query)use($name){
                $query->where('undertake.user_name', 'like', '%'.$name.'%')->orWhere('dept_name', 'like', '%'.$name.'%');
            });
        }

        //关联费用类型表 用户表 获取当前用户和费用科目
        return $query->wheres($param['search'])
            ->orders($param['order_by'])
            ->parsePage($param['page'], $param['limit'])
            ->get();
    }

    //月份之前自动补0
    public function complementZero($num)
    {
        return str_pad($num, 2, 0, STR_PAD_LEFT);
    }

    /**
     * 获取当前用户|部门总共条数
     *
     * @param array $where
     *
     * @return int
     *
     * @author 喻威
     *
     * @since 2015-10-20
     */
    public function getchargeListTotal($data)
    {

        $default = [
            'search' => [],
        ];

        $param = array_merge($default, $data);
        if(isset($param['search']) && isset($param['search']['user_name'])){
            $param['search']['user.user_name'] = $param['search']['user_name'];
            unset($param['search']['user_name']);
        }
        $query = $this->entity->leftJoin('user', function($join) {
            $join->on("charge_list.user_id", '=', 'user.user_id');
        })->leftJoin('user_system_info', function($join) use($param)  {
            $join->on("charge_list.undertake_user", '=', 'user_system_info.user_id');
        })->leftJoin('charge_type', function ($join) {
            $join->on("charge_type.charge_type_id", '=', 'charge_list.charge_type_id');
        })->leftJoin('department', function ($join) {
            $join->on("department.dept_id", '=', 'charge_list.undertake_dept');
        })->leftJoin('user as undertake', function ($join) {
            $join->on("charge_list.undertake_user", '=', 'undertake.user_id');
        });

        switch ($param["filter"]) {
            case 'year':
                if (isset($param['year']) && $param['year'] > 0) {
                    switch ($param["charge_fiter"]) {
                        case "T":
                            $query = $query->whereRaw("DATE_FORMAT(charge_list.payment_date, '%Y') = ?", [$param['year']]);
                            break;
                        case "M":
                            if ($param['has_children']) {
                                $query = $query->where(function($query)use($param){
                                    $query->whereRaw('find_in_set(\''.intval($param["charge_type"]).'\',charge_type.type_level)')->orWhere("charge_list.charge_type_id", $param["charge_type"]);
                                });
                            } else {
                                $query = $query->where("charge_list.charge_type_id", $param["charge_type"]);
                            }
                            $query->whereRaw("DATE_FORMAT(charge_list.payment_date, '%Y') = ?", [$param['year']]);
                            break;
                        default:
                            $query = $query->whereRaw("DATE_FORMAT(charge_list.payment_date, '%Y-%m') = ?", [$param['year'] . "-" . $this->complementZero($param["charge_fiter"])])->where("charge_list.charge_type_id", $param["charge_type"]);
                            break;
                    }
                }
                break;
            case 'quarter':

                switch ($param["charge_fiter"]) {
                    case "T":
                        $query = $query->whereRaw("DATE_FORMAT(charge_list.payment_date, '%Y') = ?", [$param['year']]);
                        break;
                    case "M":
                        $query = $query->whereRaw("DATE_FORMAT(charge_list.payment_date, '%Y') = ?", [$param['year']]);
                        if ($param['has_children']) {
                            $query = $query->where(function($query)use($param){
                                $query->whereRaw('find_in_set(\''.intval($param["charge_type"]).'\',charge_type.type_level)')->orWhere("charge_list.charge_type_id", $param["charge_type"]);
                            });
                        } else {
                            $query = $query->where("charge_list.charge_type_id", $param["charge_type"]);
                        }
                        break;
                    default:
                        if ($param["charge_fiter"] < 5) {
                            $minMonth  = $param["charge_fiter"] * 3 - 2;
                            $maxMonth  = $param["charge_fiter"] * 3;
                            $startDate = $param['year'] . "-" . $this->complementZero($minMonth);
                            $endDate   = $param['year'] . "-" . $this->complementZero($maxMonth);
                            $query     = $query->whereRaw("DATE_FORMAT(charge_list.payment_date,  '%Y-%m') >= ?", [$startDate])
                                ->whereRaw("DATE_FORMAT(charge_list.payment_date, '%Y-%m') <= ?", [$endDate])
                                ->where("charge_list.charge_type_id", $param["charge_type"]);
                        } else if ($param["charge_fiter"] == 5) {
                            $query = $query->where("charge_list.charge_type_id", $param["charge_type"]);
                        }
                        break;
                }
                break;
            case 'month':
                $ym    = $param['year'] . "-" . $this->complementZero($param['month']);
                $query = $query->whereRaw("DATE_FORMAT(charge_list.payment_date, '%Y-%m') = ?", [$ym]);
                switch ($param["charge_fiter"]) {
                    case "T":
                        $query = $query->whereRaw("DATE_FORMAT(charge_list.payment_date, '%Y') = ?", [$param['year']]);
                        break;
                    case "M":
                        if ($param['has_children']) {
                            $query = $query->where(function($query)use($param){
                                $query->whereRaw('find_in_set(\''.intval($param["charge_type"]).'\',charge_type.type_level)')->orWhere("charge_list.charge_type_id", $param["charge_type"]);
                            });
                        } else {
                            $query = $query->where("charge_list.charge_type_id", $param["charge_type"]);
                        }
                        break;
                    default:
                        $ym1   = $param['year'] . "-" . $param['month'];
                        $month = date("t", strtotime("$ym1"));
                        if ($param["charge_fiter"] <= $month) {
                            $ymd   = $ym . "-" . $param["charge_fiter"];
                            $query = $query->whereRaw("DATE_FORMAT(charge_list.payment_date, '%Y-%m-%e') = ?", [$ymd])
                                ->where("charge_list.charge_type_id", $param["charge_type"]);
                        } else if ($param["charge_fiter"] > $month) {
                            $query = $query->where("charge_list.charge_type_id", $param["charge_type"]);
                        }
                        break;
                }

                break;
        }

        if(isset($data['viewUser']) && !empty($data['viewUser']) && $data['viewUser'] != 'all' && 
            !(isset($data['flag']) && $data['flag'] == 'my' && $data['filter_type'] == 2)){
            $query = $query->whereIn('charge_list.user_id', $data['viewUser']);
        }

        if ($param['filter_type'] == 1) {
            if (isset($param['users']) && $param['users']) {
                if ($param['users'] != 'all') {
                    $query->whereIn('charge_list.user_id', $param['users']);
                }
            }
        } else {
            if ($param['set_type'] == 1) {
                $query = $query->where('charge_undertaker', 3);
            } elseif ($param['set_type'] == 2) {
                $query = $query->where('charge_undertaker', 2);
                if (!empty($param['set_id'])) {
                    $deptIds = $param['set_id'];
                    if (isset($param['has_depts']) && $param['has_depts'] == 1) {
                        //获取当前部门下所有部门
                        foreach ($param['set_id'] as $deptId) {
                            $deptIds = array_merge($deptIds, app('App\EofficeApp\System\Department\Services\DepartmentService')->getTreeIds($param['set_id']));
                        }
                    }
                    $query = $query->whereIn('undertake_dept', $deptIds);
                }
            } elseif ($param['set_type'] == 3) {
                $query = $query->where('charge_undertaker', 1);
                if (!empty($param['set_id'])) {
                    $query = $query->whereIn('undertake_user', $param['set_id']);
                }
            } elseif ($param['set_type'] == 5) {
                $query = $query->where('charge_undertaker', 4);
                if (!empty($param['set_id'])) {
                    $query = $query->whereIn('project_id', $param['set_id']);
                }
            }
        }
        // 是否有项目权限
        if(!isset($param['power']) || $param['power'] == 0){
            $query = $query->whereNull('charge_list.project_id');
        } else {
            if (isset($param['projects']) && !empty($param['projects'])) {
                $query = $this->handleProjectQuery($query, $data);
            }
        }
        //承担者选择器搜索
        if (isset($param['name'])) {
            $name = $param['name'];
            $query = $query->where(function($query)use($name){
                $query->where('undertake.user_name', 'like', '%'.$name.'%')->orWhere('dept_name', 'like', '%'.$name.'%');
            });
        }

        return $query->wheres($param['search'])->count();
    }

    //获取费用统计 明细列表字段
    public function chargeDetails($data)
    {
        $default = [
            'fields'   => ['charge_list.*', 'user_name', "charge_type_name", 'dept_name'],
            'page'     => 0,
            'limit'    => config('eoffice.pagesize'),
            'search'   => [],
            'order_by' => ['payment_date' => 'desc', 'charge_list.created_at' => 'desc'],
        ];
        if(isset($data['order_by']) && !isset($data['order_by']['charge_list.created_at'])){
            $data['order_by']['charge_list.created_at'] = isset($data['order_by']['payment_date']) ? $data['order_by']['payment_date'] : 'desc';
        }

        $param = array_merge($default, array_filter($data));

        $query = $this->entity;

        if ($param["type"] == "user") {
            $query = $query->where("charge_list.undertake_user", $param["id"])
                ->where("charge_list.charge_undertaker", "1");
        } elseif ($param["type"] == "company") {
            $query = $query->where("charge_list.charge_undertaker", "3");
        } elseif ($param["type"] == "project") {
            $query = $query->where("charge_list.project_id", $param["id"]);
        } else {
            $query = $query->where("charge_list.undertake_dept", $param["id"])
                ->where("charge_list.charge_undertaker", "2");
        }

        if(isset($data['viewUser']) && !empty($data['viewUser']) && $data['viewUser'] != 'all'){
            $query = $query->whereIn('charge_list.undertake_user', $data['viewUser']);
        }

        //关联费用类型表 用户表 获取当前用户和费用科目
        return $query->select($param['fields'])->leftJoin('user', function ($join) {
            $join->on("charge_list.undertake_user", '=', 'user.user_id');
        })->leftJoin('charge_type', function ($join) {
            $join->on("charge_type.charge_type_id", '=', 'charge_list.charge_type_id');
        })->leftJoin('department', function ($join) {
            $join->on("department.dept_id", '=', 'charge_list.undertake_dept');
        })->wheres($param['search'])
            ->orders($param['order_by'])
            ->parsePage($param['page'], $param['limit'])
            ->get()
            ->toArray();
    }

    public function chargeDetailsTotal($data)
    {
        $default = [
            'search' => [],
        ];

        $param = array_merge($default, array_filter($data));

        $query = $this->entity->leftJoin('user', function ($join) {
            $join->on("charge_list.undertake_user", '=', 'user.user_id');
        });

        if ($param["type"] == "user") {
            $query = $query->where("charge_list.undertake_user", $param["id"])
                ->where("charge_list.charge_undertaker", "1");
        } elseif ($param["type"] == "company") {
            $query = $query->where("charge_list.charge_undertaker", "3");
        } elseif ($param["type"] == "project") {
            $query = $query->where("charge_list.project_id", $param["id"]);
        } else {
            $query = $query->where("charge_list.undertake_dept", $param["id"])
                ->where("charge_list.charge_undertaker", "2");
        }

        if(isset($data['viewUser']) && !empty($data['viewUser']) && $data['viewUser'] != 'all'){
            $query = $query->whereIn('charge_list.undertake_user', $data['viewUser']);
        }

        //关联费用类型表 用户表 获取当前用户和费用科目
        return $query->wheres($param['search'])
            ->count();
    }

    /**
     * 获取某一个用户年|季度|月|时间|消费总额
     *
     * @param array $data
     *
     * @return type
     *
     * @author 喻威
     *
     * @since 2015-10-20
     */
    public function getChargeDetail($data)
    {
        $query = $this->entity;
        $query = $query->select(['charge_list.charge_type_id', 'charge_list.payment_date', 'charge_list.charge_cost', 'charge_type.charge_type_parent']);
        $query = $query->leftJoin('charge_type', function ($join) {
            $join->on("charge_list.charge_type_id", '=', 'charge_type.charge_type_id');
        });

        $query = $query->where("charge_list.charge_type_id", "=", $data['chargeTypeID']);

        if(isset($data['viewUser']) && $data['viewUser'] != 'all' && 
            !(isset($data['flag']) && $data['flag'] == 'my' && $data['filter_type'] == 2)){
            if(count($data['viewUser']) > 1000){
                $query = $this->useTempTable($query, $data['viewUser'], 'charge_list.user_id');
            } else {
                $query->whereIn("charge_list.user_id", $data['viewUser']);
            }
        }

        if ($data['filter_type'] == 1) {
            if(isset($data['users']) && !empty($data['users'])){
                $query->whereIn("charge_list.user_id", $data['users']);

                // if(isset($data['flag']) && $data['flag'] == 'my'){
                //     $query = $query->where(function($query)use($data){
                //         $query->whereNull('charge_list.project_id')->orWhereIn('charge_list.project_id', $data['projects']);
                //     });
                // }
            }

            if(isset($data['project_id'])){
                if($data['project_id'][0] == 'in'){
                    $query = $query->whereIn("charge_list.project_id", $data['project_id'][1]);
                }else{
                    $query = $query->where("charge_list.project_id", $data['project_id'][0], $data['project_id'][1]);
                }
            }
        } else {
            if ($data['set_type'] == 1) {
                $query = $query->where('charge_undertaker', 3);
            } elseif ($data['set_type'] == 2) {
                $query = $query->where('charge_undertaker', 2);
                if (!empty($data['set_id'])) {
                    $deptIds = $data['set_id'];
                    if (isset($data['has_depts']) && $data['has_depts'] == 1) {
                        //获取当前部门下所有部门
                        foreach ($data['set_id'] as $deptId) {
                            $deptIds = array_merge($deptIds, app('App\EofficeApp\System\Department\Services\DepartmentService')->getTreeIds($data['set_id']));
                        }
                    }
                    $query = $query->whereIn('undertake_dept', $deptIds);
                }
            } elseif ($data['set_type'] == 3) {
                $query = $query->where('charge_undertaker', 1);
                if (!empty($data['set_id'])) {
                    $query = $query->whereIn('undertake_user', $data['set_id']);
                }
            } elseif ($data['set_type'] == 5) {
                $query = $query->where('charge_undertaker', 4);
                if (!empty($data['set_id'])) {
                    $query = $query->whereIn('project_id', $data['set_id']);
                }
            }
        }

        if(isset($data['power']) && $data['power'] == 0){
            $query = $query->whereNull('charge_list.project_id');
        } else {
            if (isset($data['projects']) && !empty($data['projects'])) {
                $query = $this->handleProjectQuery($query, $data);
            }
        }

        $chargeDetailArr = [];
        switch ($data['filter']) {
            case 'year':
                for ($i = 1; $i <= 12; $i++) {
                    $chargeDetailArr[$i] = 0;
                }
                if (!empty($data['year'])) {
                    if (isset($data["month"]) && $data["month"]) {
                        $query = $query->whereRaw("DATE_FORMAT(charge_list.payment_date, '%Y-%m') = ?", [$data['year'] . "-" . $this->complementZero($data['month'])]);
                    } else {
                        $query = $query->whereRaw("DATE_FORMAT(charge_list.payment_date, '%Y') = ?", [$data['year']]);
                    }
                }

                break;
            case 'quarter':
                for ($i = 1; $i <= 4; $i++) {
                    $chargeDetailArr[$i] = 0;
                }
                if (!empty($data['year'])) {
                    if (isset($data["quarter"]) && $data["quarter"]) {
                        $minMonth = $data["quarter"] * 3 - 2;
                        $maxMonth = $data["quarter"] * 3;

                        $startDate = $data['year'] . "-" . $this->complementZero($minMonth);
                        $endDate   = $data['year'] . "-" . $this->complementZero($maxMonth);
                        $query     = $query->whereRaw("DATE_FORMAT(charge_list.payment_date,  '%Y-%m') >= ?", [$startDate])
                            ->whereRaw("DATE_FORMAT(charge_list.payment_date, '%Y-%m') <= ?", [$endDate]);
                    } else {
                        $query = $query->whereRaw("DATE_FORMAT(charge_list.payment_date, '%Y') = ?", [$data['year']]);
                    } 
                }

                break;
            case 'month':
                $year  = $data['year'];
                $month = $data['month'];
                $count = date("t", strtotime("$year-$month"));

                for ($i = 1; $i <= $count; $i++) {
                    $chargeDetailArr[$i] = 0;
                }
                if (!empty($data['year'])) {
                    if (isset($data["day"]) && $data["day"]) {
                        $ymd   = $year . "-" . $this->complementZero($month) . "-" . $data["day"];
                        $query = $query->whereRaw("DATE_FORMAT(charge_list.payment_date, '%Y-%m-%e') = ?", [$ymd]);
                    } else {
                        $query = $query->whereRaw("DATE_FORMAT(charge_list.payment_date, '%Y-%m') = ?", [$year . "-" . $this->complementZero($month)]);
                    }
                }

                break;
        }

        $result = $query->get();

        $quarter = [];
        $month1 = $month2 = $month3 = 0;
        foreach ($result as $k => $v) {

            switch ($data['filter']) {
                case 'year':
                    $index = date('n', strtotime($v->payment_date));
                    break;
                case 'quarter':
                    $index = ceil(date('n', strtotime($v->payment_date)) / 3);
                    if(isset($data['quarter'])){
                        $payMonth = date('n', strtotime($v->payment_date));
                        switch($payMonth){
                            case $index * 3 - 2:
                                $month1 += (float)$v->charge_cost;
                                break;
                            case $index * 3 - 1:
                                $month2 += (float)$v->charge_cost;
                                break;
                            case $index * 3:
                                $month3 += (float)$v->charge_cost;
                                break;
                        }
                    }
                    break;
                case 'month':
                    $index = date('j', strtotime($v->payment_date));
                    break;
            }

            $chargeDetailArr[$index] += (float) $v->charge_cost;
        }
        $quarter = [$month1, $month2, $month3];

        return [$chargeDetailArr, $quarter];
    }

    private function handleProjectQuery($query, $data) {
        if(count($data['projects']) > 1000){
            static $tableName;
            if(empty($tableName)){
                $tableName = 'charge_'.rand() . uniqid();
                $res = DB::statement("CREATE TEMPORARY TABLE if not exists {$tableName} (`data_id` char(36) NOT NULL,PRIMARY KEY (`data_id`))");
                $tempIds = array_chunk($data['projects'], 1000);
                foreach ($tempIds as $key => $item) {
                    $ids = implode('"),("', $item);
                    $sql = "insert into {$tableName} (data_id) values (\"{$ids}\");";
                    DB::insert($sql);
                }
            }
            $query = $query->where(function($query)use($data, $tableName){
                $query = $query->whereExists(function($query)use($data, $tableName){
                    $query->select(DB::raw(1))
                        ->from($tableName)
                        ->whereRaw('charge_list.project_id = '.$tableName.'.data_id');
                })->orWhereNull('charge_list.project_id');
            });
            // $query = $this->useTempTable($query, $data['projects'], 'charge_list.project_id')->orWhereNull('charge_list.project_id');
        } else {
            $query = $query->where(function($query)use($data){
                $query->whereNull('charge_list.project_id')->orWhereIn('charge_list.project_id', $data['projects']);
            });
        }

        return $query;
        // $query = $query->where(function($query)use($data){
        //     $query->whereNull('charge_list.project_id')->orWhereIn('charge_list.project_id', $data['projects']);
        // });
    }

    /**
     * 获取某一个主类的总额
     */
    public function getChargeTotal($data)
    {
        $query = $this->entity;
        $query = $query->leftJoin('charge_type', function ($join) {
            $join->on("charge_list.charge_type_id", '=', 'charge_type.charge_type_id');
        });
        if (isset($data['has_children'])) {
            $query = $query->where(function($query)use($data) {
                $query->whereRaw('find_in_set(\''.intval($data["chargeTypeID"]).'\',charge_type.type_level)')
                            ->orWhere('charge_list.charge_type_id', $data["chargeTypeID"]);
            });
        } else {
            $query = $query->where('charge_list.charge_type_id', $data["chargeTypeID"]);
        }

        if(isset($data['viewUser']) && $data['viewUser'] != 'all' && 
            !(isset($data['flag']) && $data['flag'] == 'my' && $data['filter_type'] == 2)){
            if(count($data['viewUser']) > 1000){
                $query = $this->useTempTable($query, $data['viewUser'], 'charge_list.user_id');
            } else {
                $query->whereIn("charge_list.user_id", $data['viewUser']);
            }
        }  

        if ($data['filter_type'] == 1) {
            if(isset($data['users']) && !empty($data['users'])){
                $query->whereIn("charge_list.user_id", $data['users']);

                // if(isset($data['flag']) && $data['flag'] == 'my'){
                //     $query = $query->where(function($query)use($data){
                //         $query->whereNull('charge_list.project_id')->orWhereIn('charge_list.project_id', $data['projects']);
                //     });
                // }
            }

            if(isset($data['project_id'])){
                if($data['project_id'][0] == 'in'){
                    $query = $query->whereIn("charge_list.project_id", $data['project_id'][1]);
                }else{
                    $query = $query->where("charge_list.project_id", $data['project_id'][0], $data['project_id'][1]);
                }
            }
        } else {
            if ($data['set_type'] == 1) {
                $query = $query->where('charge_undertaker', 3);
            } elseif ($data['set_type'] == 2) {
                $query = $query->where('charge_undertaker', 2);
                if (!empty($data['set_id'])) {
                    $deptIds = $data['set_id'];
                    if (isset($data['has_depts']) && $data['has_depts'] == 1) {
                        //获取当前部门下所有部门
                        foreach ($data['set_id'] as $deptId) {
                            $deptIds = array_merge($deptIds, app('App\EofficeApp\System\Department\Services\DepartmentService')->getTreeIds($data['set_id']));
                        }
                    }
                    $query = $query->whereIn('undertake_dept', $deptIds);
                }
            } elseif ($data['set_type'] == 3) {
                $query = $query->where('charge_undertaker', 1);
                if (!empty($data['set_id'])) {
                    $query = $query->whereIn('undertake_user', $data['set_id']);
                }
            } elseif ($data['set_type'] == 5) {
                $query = $query->where('charge_undertaker', 4);
                if (!empty($data['set_id'])) {
                    $query = $query->whereIn('project_id', $data['set_id']);
                }
            }
        }

        if(isset($data['power']) && $data['power'] == 0){
            $query = $query->whereNull('charge_list.project_id');
        } else {
            if (isset($data['projects']) && !empty($data['projects'])) {
                $query = $this->handleProjectQuery($query, $data);
            }
        }

        switch ($data['filter']) {
            case 'year':
                if (!empty($data['year'])) {
                    if (isset($data["month"]) && $data["month"]) {
                        $query = $query->whereRaw("DATE_FORMAT(charge_list.payment_date, '%Y-%m') = ?", [$data['year'] . "-" . $this->complementZero($data['month'])]);
                    } else {
                        $query = $query->whereRaw("DATE_FORMAT(charge_list.payment_date, '%Y') = ?", [$data['year']]);
                    }
                }
                break;
            case 'quarter':
                if (!empty($data['year'])) {
                    if (isset($data["quarter"]) && $data["quarter"]) {
                        $minMonth = $data["quarter"] * 3 - 2;
                        $maxMonth = $data["quarter"] * 3;

                        $startDate = $data['year'] . "-" . $this->complementZero($minMonth);
                        $endDate   = $data['year'] . "-" . $this->complementZero($maxMonth);
                        $query     = $query->whereRaw("DATE_FORMAT(charge_list.payment_date,  '%Y-%m') >= ?", [$startDate])
                            ->whereRaw("DATE_FORMAT(charge_list.payment_date, '%Y-%m') <= ?", [$endDate]);
                    } else {
                        $query = $query->whereRaw("DATE_FORMAT(charge_list.payment_date, '%Y') = ?", [$data['year']]);
                    }
                }

                break;
            case 'month':
                if (!empty($data['year'])) {
                    if (isset($data["day"]) && $data["day"]) {
                        $ymd   = $data['year'] . "-" . $this->complementZero($data['month']) . "-" . $data["day"];
                        $query = $query->whereRaw("DATE_FORMAT(charge_list.payment_date, '%Y-%m-%e') = ?", [$ymd]);
                    } else {
                        $query = $query->whereRaw("DATE_FORMAT(charge_list.payment_date, '%Y-%m') = ?", [$data['year'] . "-" . $this->complementZero($data['month'])]);
                    }
                }
        }
        
        return $query->sum("charge_cost");
    }

    private function useTempTable($query, $data, $column) {
        static $tableName;
        if(empty($tableName)){
            $tableName = 'charge_'.rand() . uniqid();
            $res = DB::statement("CREATE TEMPORARY TABLE if not exists {$tableName} (`data_id` char(36) NOT NULL,PRIMARY KEY (`data_id`))");
            $tempIds = array_chunk($data, 1000);
            foreach ($tempIds as $key => $item) {
                $ids = implode('"),("', $item);
                $sql = "insert into {$tableName} (data_id) values (\"{$ids}\");";
                DB::insert($sql);
            }
        }
        
        $query->join("$tableName", $tableName . ".data_id", '=', $column);

        return $query;
    }

    /**
     * 获取某个集合用户的总额 -- 清单列表处使用
     */
    public function getStatistic($where)
    {
        return $this->entity->wheres($where)->sum("charge_cost");
    }

    /**
     * applist 根据条件获取对应的统计值
     * 总计统计值使用getStatistic
     */
    public function getChargeCostByWhere($data, $wheres = [])
    {
        //其他条件 科目一统计值 子科目一统计值 年（带月） 季度（带月） 日（带日）
        $query = $this->entity->leftJoin('charge_type', 'charge_list.charge_type_id', '=', 'charge_type.charge_type_id');
        if($data['type'] == 'project'){
            $query = $query->where('project_id', $data['set_id'])->wheres($wheres);
        }else{
            $query = $query->where(function($query) use($data) {
                $query->whereIn("charge_list.user_id", $data['users'])->whereNull('charge_list.project_id');
            });
            if(isset($data['hasProject']) && $data['hasProject']){
                $query = $query->orWhere(function($query) use($data) {
                    $query->whereIn("charge_list.user_id", $data['users'])->whereIn('charge_list.project_id', $data['project']);
                });
            }
            $query = $query->wheres($wheres); //子科目 主科目
        }

        switch ($data['filter']) {
            case 'year':
                if (isset($data["month"]) && $data["month"]) {
                    $query = $query->whereRaw("DATE_FORMAT(charge_list.payment_date, '%Y-%m') = ?", [$data['year'] . "-" . $this->complementZero($data['month'])]);
                } else {
                    $query = $query->whereRaw("DATE_FORMAT(charge_list.payment_date, '%Y') = ?", [$data['year']]);
                }
                break;
            case 'quarter':
                if (isset($data["quarter"]) && $data["quarter"]) {
                    $minMonth = $data["quarter"] * 3 - 2;
                    $maxMonth = $data["quarter"] * 3;

                    $startDate = $data['year'] . "-" . $this->complementZero($minMonth);
                    $endDate   = $data['year'] . "-" . $this->complementZero($maxMonth);
                    $query     = $query->whereRaw("DATE_FORMAT(charge_list.payment_date,  '%Y-%m') >= ?", [$startDate])
                        ->whereRaw("DATE_FORMAT(charge_list.payment_date, '%Y-%m') <= ?", [$endDate]);
                } else {
                    $query = $query->whereRaw("DATE_FORMAT(charge_list.payment_date, '%Y') = ?", [$data['year']]);
                }

                break;
            case 'month':

                if (isset($data["day"]) && $data["day"]) {
                    $ymd   = $data['year'] . "-" . $this->complementZero($data['month']) . "-" . $data["day"];
                    $query = $query->whereRaw("DATE_FORMAT(charge_list.payment_date, '%Y-%m-%e') = ?", [$ymd]);
                } else {
                    $query = $query->whereRaw("DATE_FORMAT(charge_list.payment_date, '%Y-%m') = ?", [$data['year'] . "-" . $this->complementZero($data['month'])]);
                }

                break;
        }

        return $query->sum("charge_cost");
    }

    /**
     * 根据条件获取响应的结构集
     */
    public function getDataBywhere($where)
    {

        return $this->entity->wheres($where)->get()->toArray();
    }

    /**
     * 查看详情 -- user chargy_type转换
     */
    public function getNewCharge($chargeListId)
    {
        $query = $this->entity;
        //关联费用类型表 用户表 获取当前用户和费用科目
        return $query->select(["charge_list.*", "charge_type_parent", "user_name", "charge_type_name", "dept_name", "type_level"])->leftJoin('user', function ($join) {
            $join->on("charge_list.user_id", '=', 'user.user_id');
        })->leftJoin('department', function ($join) {
            $join->on("department.dept_id", '=', 'charge_list.undertake_dept');
        })->leftJoin('charge_type', function ($join) {
            $join->on("charge_type.charge_type_id", '=', 'charge_list.charge_type_id');
        })->where("charge_list_id", $chargeListId)
            ->first();
    }

    /**
     * 根据条件获取费用和 --- 费用统计 yww
     */
    public function getChargeCost($param)
    {
        $charge_undertaker = isset($param["type"]) && $param["type"] == "dept" ? 2 : 1;
        $key               = isset($param["type"]) && $param["type"] == "dept" ? "undertake_dept" : "undertake_user";

        $query = $this->entity;

        if(isset($param["type"]) && $param["type"] == "company"){
            $query = $query->where("charge_list.charge_undertaker", 3);
        } elseif ($param["type"] == "project") {
            $query = $query->where("charge_list.project_id", $param['id']);
        } else {
            $query = $query->where($key, $param["id"])
                            ->where("charge_list.charge_undertaker", $charge_undertaker);
        }

        if(isset($param['charge_type_id']) && $param['charge_type_id'] != ""){
            $query->where("charge_list.charge_type_id", $param['charge_type_id']);
        }

        switch ($param["filter"]) {
            case 'year':
                if (isset($param['payment_date']) && $param['payment_date']) {
                    $query = $query->whereRaw("DATE_FORMAT(charge_list.payment_date, '%Y') = ?", [$param['payment_date']]);
                }
                break;
            case 'quarter':

                if (isset($param['payment_date']) && $param['payment_date']) {

                    $temp = explode("|", $param['payment_date']);

                    $query = $query->whereRaw("DATE_FORMAT(charge_list.payment_date,  '%Y-%m') >= ?", [$temp[0]])
                        ->whereRaw("DATE_FORMAT(charge_list.payment_date, '%Y-%m') <= ?", [$temp[1]]);
                }

                break;
            case 'month':
                if (isset($param['payment_date']) && $param['payment_date']) {
                    $query = $query->whereRaw("DATE_FORMAT(charge_list.payment_date, '%Y-%m') = ?", [$param['payment_date']]);
                }
                break;
            case 'custom':
                if (isset($param['payment_date']) && $param['payment_date']) {
                    $temp  = explode("|", $param['payment_date']);
                    $query = $query->where("charge_list.payment_date", ">=", $temp[0])->where("charge_list.payment_date", "<=", $temp[1]);
                }
                break;
            default:
                break;
        }

        return $query->sum("charge_list.charge_cost");
    }

    /**
     * 配置数据源 获取已报销字段 条件 fiter where = [user_id dept_id or understarnd]
     *
     */
    public function getReportAmount($row, $where)
    {

        $query = $this->entity->wheres($where);

        switch ($row["alert_method"]) {
            case 'year':
                $year  = date("Y", time());
                $query = $query->whereRaw("DATE_FORMAT(charge_list.payment_date, '%Y') = ?", [$year]);

                break;
            case 'quarter':

                $chargeMonth = ceil(date('m', time()) / 3);
                $year        = date("Y", time());
                $minMonth    = $chargeMonth * 3 - 2;
                $maxMonth    = $chargeMonth * 3;

                $startDate = $year . "-" . $this->complementZero($minMonth);
                $endDate   = $year . "-" . $this->complementZero($maxMonth);

                $query = $query->whereRaw("DATE_FORMAT(charge_list.payment_date,  '%Y-%m') >= ?", [$startDate])
                    ->whereRaw("DATE_FORMAT(charge_list.payment_date, '%Y-%m') <= ?", [$endDate]);

                break;
            case 'month':
                $year      = date("Y", time());
                $month     = date("m", time());
                $monthDate = $year . "-" . $this->complementZero($month);
                $query     = $query->whereRaw("DATE_FORMAT(charge_list.payment_date, '%Y-%m') = ?", [$monthDate]);

                break;
            case 'custom':
                $startDate = $row["alert_data_start"];
                $endDate   = $row["alert_data_end"];
                $query     = $query->where("charge_list.payment_date", ">=", $startDate)->where("charge_list.payment_date", "<=", $endDate);

                break;
            default:
                break;
        }

        return $query->sum("charge_list.charge_cost");
    }

    public function hasChargeRecord($typeId) {
        return $this->entity->leftJoin('charge_type', function ($join) {
                    $join->on("charge_type.charge_type_id", '=', 'charge_list.charge_type_id');
                })->whereRaw('find_in_set(\''.intval($typeId).'\',type_level)')
                ->orWhere('charge_list.charge_type_id', $typeId)
                ->get();
    }

}
