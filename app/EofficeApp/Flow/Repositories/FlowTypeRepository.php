<?php

namespace App\EofficeApp\Flow\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Flow\Entities\FlowTypeEntity;

/**
 * 定义流程表知识库
 *
 * @author 丁鹏
 *
 * @since  2015-10-16 创建
 */
class FlowTypeRepository extends BaseRepository
{
    public function __construct(FlowTypeEntity $entity)
    {
        parent::__construct($entity);
    }

    /**
     * 一个基础的函数，用来根据各种条件获取flow_type表的数据
     * 现在没有地方用到这个函数
     * @method getFlowRunProcessList
     *
     * @param array $param [description]
     *
     * @return [type]                       [description]
     */
    public function getFlowTypeList($param = [])
    {
        $default = [
            'fields' => ['*'],
            'page' => 0,
            'limit' => config('eoffice.pagesize'),
            'search' => [],
            'order_by' => ['flow_noorder' => 'asc', 'flow_id' => 'asc'],
            'returntype' => 'array',
        ];
        $param = array_merge($default, array_filter($param));
        $query = $this->entity
            ->select($param['fields'])
            ->wheres($param['search'])
            ->orders($param['order_by'])
            ->with(["flowTypeBelongsToFlowSort" => function ($query) {
                $query->select("id", "title");
            }]);
        // 分组参数
        if (isset($param['groupBy'])) {
            $query = $query->groupBy($param['groupBy']);
        }
        // 解析原生 where
        if (isset($param['whereRaw'])) {
            foreach ($param['whereRaw'] as $key => $whereRaw) {
                $query = $query->whereRaw($whereRaw);
            }
        }
        // 解析原生 select
        if (isset($param['selectRaw'])) {
            foreach ($param['selectRaw'] as $key => $selectRaw) {
                $query = $query->selectRaw($selectRaw);
            }
        }
        $query = $query->parsePage($param['page'], $param['limit']);
        // 返回值类型判断
        if ($param["returntype"] == "array") {
            return $query->get()->toArray();
        } else if ($param["returntype"] == "count") {
            return $query->count();
        } else if ($param["returntype"] == "object") {
            return $query->get();
        }
    }

    /**
     * 获取定义流程列表
     *
     * @method getFlowRunProcessList
     *
     * @param array $param [description]
     *
     * @return [type]                       [description]
     */
    public function getFlowDefineList($param = [], array $with=['all'])
    {
        // 传递的表单名称查询参数
        $formName = "";
        if (isset($param["search"]["form_name"])) {
            $formName = $param["search"]["form_name"][0];
            unset($param["search"]["form_name"]);
        }
        $default = [
            'fields' => ['*'],
            'page' => 0,
            'limit' => config('eoffice.pagesize'),
            'search' => [],
            'order_by' => ['is_using' => 'desc', 'flow_noorder' => 'asc', 'flow_id' => 'desc'],
            'returntype' => 'array',
        ];
        $param = array_merge($default, array_filter($param));
        if (! in_array('is_using', $param['fields'])) {
            array_push($param['fields'], 'is_using');
        }
        if (!empty($param['request_from'])) {
            $with = [];
        }
        $query = $this->entity
            ->select($param['fields'])
            ->wheres($param['search'])
            ->orders($param['order_by']);
        if ($with == ['all'] || in_array('flow_sort', $with)) {
            $query = $query->with(["flowTypeBelongsToFlowSort" => function ($query) {
                $query->select("id", "title");
            }]);
        }
        if ($with == ['all'] || in_array('flow_form_type', $with)) {
            $query = $query->with(["flowTypeHasOneFlowFormType" => function ($query) {
                $query->select("form_id", "form_name");
            }]);
        }
        if ($with == ['all'] || in_array('flow_run', $with)) {
            $query = $query->with(['flowTypeHasManyFlowRun' => function ($query) {
                $query->select('flow_id')
                    ->selectRaw("count(*) as total")
                    ->groupBy('flow_id')
                    ->where("is_effect", "1");
            }]);
        }
        if (isset($param['flow_sort'])) {
            $query = $query->whereIn('flow_sort', $param['flow_sort']);
        }
        if (isset($param['flow_id']) && !empty($param['flow_id'])) {
            $query = $query->whereIn('flow_id', $param['flow_id']);
        }
        if (isset($param['flow_type']) && !empty($param['flow_type'])) {
            $query = $query->where('flow_type', $param['flow_type']);
        }
        if (isset($param['is_using'])) {
            $query = $query->where('is_using', $param['is_using']);
        }
        if (isset($param['hide_running'])) {
            $query = $query->where('hide_running', $param['hide_running']);
        }
        if (isset($param['module']) && $param['module'] == 'entrust') {
            $query->where(function ($query) {
                $query->where('hide_running', 0)->orWhere('is_using', 1);
            });
        }
        if ($formName) {
            $query->whereHas("flowTypeHasOneFlowFormType", function ($query) use ($formName) {
                $query->where("form_name", 'like', '%' . $formName . '%');
            });
        }
        // ip控制
        if (isset($param['controlFlows']) && !empty($param['controlFlows'])) {
             $query = $query->whereNotIn('flow_id' , $param['controlFlows']);
        }
        // 翻页判断
        $query = $query->parsePage($param['page'], $param['limit']);
        // 返回值类型判断
        if ($param["returntype"] == "array") {
            return $query->get()->toArray();
        } else if ($param["returntype"] == "count") {
            return $query->count();
        } else if ($param["returntype"] == "object") {
            return $query->get();
        } else if ($param["returntype"] == "first") {
            return $query->first();
        }
    }

    /**
     * 获取定义流程列表数量
     *
     * @method getFlowRunProcessList
     *
     * @param array $param [description]
     *
     * @return [type]                       [description]
     */
    public function getFlowDefineListTotal($param = [])
    {
        $param["page"] = "0";
        $param["returntype"] = "count";
        return $this->getFlowDefineList($param);
    }

    /**
     * 根据设置，展示流程基本信息
     *
     * @param array $param [description]
     *
     * @return [type]                                  [description]
     * @author 丁鹏
     *
     */
    public function getFlowTypeInfoRepository($param = [], array $with=['all'])
    {
        $param['fields'] = $param['fields'] ?? ['*'];
        $query = $this->entity->select($param['fields'])->where('flow_id', $param["flow_id"]);
        if ($with == ['all'] || in_array('flow_others', $with)) {
            $query = $query->with('flowTypeHasOneFlowOthers');
        }
        if ($with == ['all'] || in_array('flow_process', $with)) {
            $query = $query->with('flowTypeHasManyFlowProcess');
        }
        if ($with == ['all'] || in_array('flow_sort', $with)) {
            $query = $query->with('flowTypeBelongsToFlowSort');
        }
        if ($with == ['all'] || in_array('flow_term', $with)) {
            $query = $query->with('flowTypeHasManyFlowTerm');
        }
        if ($with == ['all'] || in_array('create_user', $with)) {
            $query = $query->with(['flowTypeHasManyCreateUser' => function($query
                        ) {
                        $query->orderBy("auto_id","asc");
                    }]);
        }
        if ($with == ['all'] || in_array('create_role', $with)) {
            $query = $query->with(['flowTypeHasManyCreateRole' => function($query
                        ) {
                        $query->orderBy("auto_id","asc");
                    }]);
        }
        if ($with == ['all'] || in_array('create_dept', $with)) {
            $query = $query->with(['flowTypeHasManyCreateDept' => function($query
                        ) {
                        $query->orderBy("auto_id","asc");
                    }]);
        }
        if ($with == ['all'] || in_array('over_time_remind', $with)) {
            $query = $query->with('flowProcessHasManyOverTimeRemind');
        }
        if ($with == ['all'] || in_array('schedule', $with)) {
            $query = $query->with(['flowTypeHasOneSchedule' => function ($query) {
                $query->select('id', 'flow_id', 'type', 'month', 'day', 'week', 'trigger_time', 'attention_content');
            }]);
        }
        if ($with == ['all'] || in_array('manage_rule', $with)) {
            $query = $query->with(['flowTypeHasManyManageRule' => function ($query) {
                // 监控规则需要正序排列
                $query->with('hasManyManageUser')
                    ->with('hasManyManageRole')
                    ->with('hasManyManageScopeUser')
                    ->with('hasManyManageScopeDept')->orderBy('rule_id', 'asc');
            }]);
        }
        if ($with == ['all'] || in_array('flow_form_type', $with)) {
            $query = $query->with(['flowTypeHasOneFlowFormType' => function ($query) {
                $query->select(['form_id', 'form_name', 'form_sort', 'form_type']);
            }]);
        }
        if ($with == ['all'] || in_array('flow_run', $with)) {
            $query = $query->with(['flowTypeHasManyFlowRun' => function ($query) {
                $query->select('flow_id')
                    ->selectRaw("count(*) as total")
                    ->groupBy('flow_run.flow_id');
            }]);
        }

        return $query->first();
    }

    /**
     * 根据设置，展示流程标题基本信息
     *
     * @author 丁鹏
     *
     * @param  array                            $param [description]
     *
     * @return [type]                                  [description]
     */
    public function getFlowTypeInfoSortAndForm($param = [])
    {
        $query = $this->entity
            ->where('flow_id', $param["flow_id"])
            ->with('flowTypeBelongsToFlowSort')
            ->with('flowTypeHasOneFlowFormType');
        return $query->first();
    }

    /**
     * 根据设置，展示流程新建部分数据
     *
     * @author wz
     *
     * @param  array                            $param [description]
     *
     * @return [type]                                  [description]
     */
    public function getFlowTypeInfoByNew($param = [])
    {

        
        $query = $this->entity
            ->where('flow_id', $param["flow_id"])
            ->with('flowTypeHasOneFlowOthers')
            ->with('flowTypeHasManyFlowProcess')
            ->with('flowTypeBelongsToFlowSort')
            ->with('flowTypeHasOneFlowFormType');
        return $query->first();
    }
    /**
     * 根据设置，展示流程基本信息
     *
     * @author wz
     *
     * @param  array                            $param [description]
     *
     * @return [type]                                  [description]
     */
    public function getFlowTypeAndOthersInfo($param = [])
    {
        $query = $this->entity
            ->where('flow_id', $param["flow_id"])
            ->with('flowProcessHasManyOverTimeRemind')
            ->with('flowTypeHasOneFlowOthers');
        return $query->first();
    }
	
    public function getFlowTypeInfoListRepository($param = [])
    {
        $query = $this->entity
            ->where('flow_id', $param["flow_id"])
            ->with('flowTypeHasManyFlowProcess')
            ->with('flowTypeHasManyFlowTerm');
        return $query->first();
    }

    public function getFlowTypeAndSortInfo($param = [])
    {
        $query = $this->entity
            ->where('flow_id', $param["flow_id"])
            ->with('flowTypeBelongsToFlowSort');
        return $query->first();
    }

    /**
     * 获取当前人员可以新建的流程;带查询
     *
     * @param array $param 传入的参数
     *
     * @return array            流程数据
     * @since  2015-10-16       创建
     *
     * @author 丁鹏
     *
     */
    public function flowNewPermissionListRepository($param = [])
    {
        $userId = isset($param["user_id"]) ? $param["user_id"] : "";
        $roleId = isset($param["role_id"]) ? $param["role_id"] : "";
        $deptId = isset($param["dept_id"]) ? $param["dept_id"] : "";
        $fixedFlowTypeInfo = isset($param["fixedFlowTypeInfo"]) ? $param["fixedFlowTypeInfo"] : "";
        $default = [
            'fields' => ['*'],
            'page' => 0,
            'limit' => config('eoffice.pagesize'),
            'search' => [],
            'order_by' => ['flow_sort.noorder' => 'ASC', 'flow_noorder' => 'ASC', 'flow_type.flow_id' => 'ASC'],
            'returntype' => 'object',
        ];

        $param = array_merge($default, array_filter($param));

        // 权限字段为空处理
        if ($userId == "" && $roleId == "" && $deptId == "") {
            if ($param["returntype"] == "array") {
                return [];
            } else if ($param["returntype"] == "count") {
                return 0;
            } else if ($param["returntype"] == "object") {
                return $this->entity;
            }
        }
        $query = $this->entity
            // ->select($param['fields'])
            ->select("flow_type.*", "flow_favorite.favorite_id")
            // ->select("flow_favorite.favorite_id")
            ->orders($param['order_by'])
            ->where('is_using', '1')
            // ->wheres($param["search"])
            ->with(['flowTypeBelongsToFlowSort' => function ($query) {
                $query->select('id', 'title', 'noorder');
            }])
            ->leftJoin('flow_favorite', function ($join) use ($userId) {
                $join->on('flow_favorite.flow_id', '=', 'flow_type.flow_id')
                    ->where('flow_favorite.user_id', $userId);
            })
            // ->with(['flowTypeHasManyFlowRun' => function ($query) use ($userId) {
            //     $query->select('flow_id', 'run_id', "create_time", "creator")
            //         ->where("creator", $userId)
            //         ->orderBy("create_time", "desc");
            // }])
            ->leftJoin('flow_sort', function ($join) {
                $join->on('flow_sort.id', '=', 'flow_type.flow_sort');
            });
        // 手机版-【常用】筛选
        if (isset($param["search"]["favorite"])) {
            if ($param["search"]["favorite"][0] == "1") {
                $query = $query->where("flow_favorite.user_id", $userId);
            } else if ($param["search"]["favorite"][0] == "0") {
                $query = $query->where("flow_favorite.favorite_id", null);
            }
            unset($param["search"]["favorite"]);
        }
        // 流程分类查询
        if (isset($param["search"]["flow_sort"])) {
            $flowSort = $param["search"]["flow_sort"][0];
            if ($flowSort > 0) {
                $query = $query->where('flow_sort', $flowSort);
            }
            unset($param["search"]["flow_sort"]);
        }
        // 如果有查询
        if (isset($param["search"]["flow_name"][0])) {
            $flowNameValue = $param["search"]["flow_name"][0];
            $query = $query->where(function ($query) use ($flowNameValue) {
                $query->where('flow_name', 'like', '%' . $flowNameValue . '%')
                    ->orWhere('flow_name_py', 'like', '%' . $flowNameValue . '%')
                    ->orWhere('flow_name_zm', 'like', '%' . $flowNameValue . '%');
            });
            unset($param["search"]["flow_name"]);
        }
        // 如果有ip控制
        if (isset($param['controlFlows']) && !empty($param['controlFlows'])) {
            $query = $query->whereNotIn('flow_type.flow_id' , $param['controlFlows']);
        }
        // 如果有流程id
        if (isset($param["flow_id"])) {
            $query = $query->where("flow_type.flow_id", $param['flow_id']);
        }
        if (isset($param["search"]["flow_id"])) {
            $tempFlowIdSearch = ['flow_type.flow_id' => $param["search"]["flow_id"]];
            $query = $query->wheres($tempFlowIdSearch);
            unset($param["search"]["flow_id"]);
        }
        $query->where(function ($query) use ($userId, $roleId, $deptId, $fixedFlowTypeInfo) {
            $query = $query->where(function ($query) use ($userId, $roleId, $deptId, $fixedFlowTypeInfo) {
                // 固定流程
                $query = $query->where('flow_type', '1')
                    ->whereIn('flow_type.flow_id', $fixedFlowTypeInfo);
                // $query = $query->whereHas('flowTypeHasManyFlowProcess', function ($query) use($userId,$roleId,$deptId){
                //     $query = $query->where('head_node_toggle','1');
                //     $query = $query->where(function ($query) use($userId,$roleId,$deptId){
                //         $query->where('process_user','ALL');
                //         $query->orWhere('process_role','ALL');
                //         $query->orWhere('process_dept','ALL');
                //         if($userId) {
                //             $query->orWhereHas('FlowProcessHasManyUser', function ($query) use ($userId) {
                //                 $query->wheres(['user_id' => [$userId]]);
                //             });
                //         }
                //         if($roleId) {
                //             $query->orWhereHas('FlowProcessHasManyRole', function ($query) use ($roleId) {
                //                 $query->wheres(['role_id' => [explode(",", trim($roleId,",")), 'in']]);
                //             });
                //         }
                //         if($deptId) {
                //             $query->orWhereHas('FlowProcessHasManyDept', function ($query) use ($deptId) {
                //                 $query->wheres(['dept_id' => [explode(",", trim($deptId,",")), 'in']]);
                //             });
                //         }
                //     });
                // });
            });
            $query = $query->orWhere(function ($query) use ($userId, $roleId, $deptId) {
                // 自由流程
                $query = $query->where('flow_type', '2');
                $query = $query->where(function ($query) use ($userId, $roleId, $deptId) {
                    $query->where('create_user', 'ALL');
                    $query->orWhere('create_role', 'ALL');
                    $query->orWhere('create_dept', 'ALL');
                    if ($userId) {
                        $query->orWhereHas('flowTypeHasManyCreateUser', function ($query) use ($userId) {
                            $query->wheres(['user_id' => [$userId]]);
                        });
                    }
                    if ($roleId) {
                        $query->orWhereHas('flowTypeHasManyCreateRole', function ($query) use ($roleId) {
                            $query->wheres(['role_id' => [explode(",", trim($roleId, ",")), 'in']]);
                        });
                    }
                    if ($deptId) {
                        $query->orWhereHas('flowTypeHasManyCreateDept', function ($query) use ($deptId) {
                            $query->wheres(['dept_id' => [explode(",", trim($deptId, ",")), 'in']]);
                        });
                    }
                });
            });
        });
        $query = $query->parsePage($param['page'], $param['limit']);
        // 返回值类型判断
        if ($param["returntype"] == "array") {
            return $query->get()->toArray();
        } else if ($param["returntype"] == "count") {
            return $query->count();
        } else if ($param["returntype"] == "object") {
            return $query->get();
        }
    }

    /**
     * 判断当前用户是否在自由流程首节点的办理人范围内
     *
     * @param string $flowId ; array $userInfo
     *
     * @return array
     * @since  2017-11-03       创建
     *
     * @author 缪晨晨
     *
     */
    public function checkFreeFlowHeadNodeTransactUser($flowId, $userInfo)
    {
        $userId = isset($userInfo["user_id"]) ? $userInfo["user_id"] : "";
        $roleId = isset($userInfo["role_id"]) ? $userInfo["role_id"] : "";
        $deptId = isset($userInfo["dept_id"]) ? $userInfo["dept_id"] : "";
        return $query = $this->entity->select("flow_type.*")
            ->where('flow_id', $flowId)
            ->where(function ($query) use ($userId, $roleId, $deptId) {
                $query = $query->where(function ($query) use ($userId, $roleId, $deptId) {
                    $query->where('create_user', 'ALL');
                    $query->orWhere('create_role', 'ALL');
                    $query->orWhere('create_dept', 'ALL');
                    if ($userId) {
                        $query->orWhereHas('flowTypeHasManyCreateUser', function ($query) use ($userId) {
                            $query->wheres(['user_id' => [$userId]]);
                        });
                    }
                    if ($roleId) {
                        $query->orWhereHas('flowTypeHasManyCreateRole', function ($query) use ($roleId) {
                            $query->wheres(['role_id' => [$roleId, 'in']]);
                        });
                    }
                    if ($deptId) {
                        $query->orWhereHas('flowTypeHasManyCreateDept', function ($query) use ($deptId) {
                            $query->wheres(['dept_id' => [[$deptId], 'in']]);
                        });
                    }
                });
            })->first();
    }

    /**
     * 获取当前人员可以新建的流程的数量;带查询
     *
     * @param array $param 传入的参数
     *
     * @return array            流程数据
     * @since  2015-10-16       创建
     *
     * @author 丁鹏
     *
     */
    public function flowNewPermissionListTotalRepository($param = [])
    {
        $param["page"] = "0";
        $param["returntype"] = "count";
        return $this->flowNewPermissionListRepository($param);
    }

    /**
     * 判断流程监控人权限
     * @method getFlowTypeRelateManageUser
     *
     * @param array $param [description]
     *
     * @return [type]                       [description]
     */
    public function getFlowTypeRelateManageUser($param = [])
    {
        $userId = $param["user_id"];
        $query = $this->entity
            ->select("*")
            ->where("flow_id", $param['flow_id'])
            ->whereHas("flowTypeHasManyManageUser", function ($query) use ($userId) {
                $query->where("user_id", $userId);
            });

        // 返回值类型判断
        return $query->get();
    }

    /**
     * 判断流程监控角色权限
     * @method getFlowTypeRelateManageRole
     *
     * @param array $param [description]
     *
     * @return [type]                       [description]
     */
    public function getFlowTypeRelateManageRole($param = [])
    {
        $roleId = isset($param["role_id"]) ? $param["role_id"] : [];
        if (empty($roleId) || !isset($param['flow_id'])) {
            return [];
        }
        $query = $this->entity
            ->select("*")
            ->where("flow_id", $param['flow_id'])
            ->whereHas("flowTypeHasManyManageRole", function ($query) use ($roleId) {
                $query->whereIn("role_id", $roleId);
            });

        // 返回值类型判断
        return $query->get();
    }

    /**
     * 获取使用某个表单的所有流程
     * @param  [type] $formId [description]
     * @return [type]         [description]
     */
    public function getFlowListForFormId($formId)
    {
        $query = $this->entity
            ->select('flow_id')
            ->where('form_id', $formId);
        return $query->get();
    }

    /**
     * 获取某个流程的类型id
     * @param  [type] $flowId [description]
     * @return [type] $sortId [description]
     */
    public function getFlowSortByFlowId($flowId)
    {
        $query = $this->entity
            ->select('flow_sort')
            ->where('flow_id', $flowId)->first();
        return $query->flow_sort;
    }

    /**
     * 获取某个流程的name
     * @param  [type] $flowId [description]
     * @return [type] $sortId [description]
     */
    public function findFlowType($flowId, $field = "flow_name")
    {
        $query = $this->entity
            ->select($field)
            ->where('flow_id', $flowId)->first();
        $query = $query ? $query->toArray() : [];
        if ($field == "*" || is_array($field)) {
            return $query;
        } else if (is_string($field)) {
            return isset($query[$field]) ? $query[$field] : "";
        }
    }

    /**
     * 获取flow_type表的数据
     */
    function getFlowTypeData($param = [])
    {
        $default = [
            'fields' => ['*'],
            'search' => [],
        ];
        $param = array_merge($default, array_filter($param));
        $query = $this->entity
            ->select($param['fields'])
            ->wheres($param['search']);
        return $query->first();
    }


    /**
     * 报表-流程报表-获取流程标题中待办流程总数量
     *
     *
     * @param  $datasourceGroupBy 分组依据; $where 数据过滤
     *
     *
     * @return array
     */
    public function getFlowPathCountGroupByCustomType($datasourceGroupBy, $where = "")
    {
        $search = array();
        $query = $this->entity;
        // 根据流程类型查询
        $query = $query->select(['flow_id', 'flow_name'])
            ->with(['flowTypeHasManyFlowRunStep' => function ($query) use ($where) {
                $query->select(['flow_run_step.flow_id', 'flow_run_step.run_id'])
                    ->where('flow_run_step.user_run_type', 1)
                    ->where('flow_run_step.is_effect', 1)
                    ->selectRaw('count(1) as run_count')
                    ->groupby('flow_run_step.run_id');
                if (isset($where['date_range'])) {
                    $query->leftJoin('flow_run', function ($join) {
                        $join->on('flow_run.run_id', '=', 'flow_run_step.run_id');
                    });
                    $dateRange = explode(',', $where['date_range']);
                    if (isset($dateRange[0]) && !empty($dateRange[0])) {
                        $query->whereRaw("flow_run.created_at >= '" . ($dateRange[0] . " 00:00:00'"));
                    }
                    if (isset($dateRange[1]) && !empty($dateRange[1])) {
                        $query->whereRaw("flow_run.created_at <= '" . ($dateRange[1] . " 23:59:59'"));
                    }
                }

                //用户搜索条件不为空
                if (isset($where['userIds']) && is_array($where['userIds'])) {
                    $query->whereIn('user_id', $where['userIds']);
                }
            }]);
        if (isset($where['flowID'])) {
            $query = $query->whereIn('flow_id', explode(',', $where['flowID']));
        }
        if (isset($where['flowsortId']) && !empty($where['flowsortId'])) {
            $query = $query->whereIn('flow_sort', $where['flowsortId']);
        }
        $result = $query->get();
        if (!empty($result)) {
            return $result->toArray();
        } else {
            return array();
        }
    }

    /**
     * 报表-流程报表-流程效率分析
     *
     *
     * @param  $datasourceGroupBy 分组依据; $where 数据过滤
     *
     *
     * @return array
     */
    public function getFlowEfficiencyCountGroupByCustomType($datasourceGroupBy, $where = "")
    {
        $search = array();
        $query = $this->entity;
        // 根据流程类型查询
        $query = $query->select(['flow_id', 'flow_name'])
            ->with(['flowTypeHasManyFlowRun' => function ($query) use ($where) {
                //创建时间过滤
                if (isset($where['date_range'])) {
                    $dateRange = explode(',', $where['date_range']);
                    if (isset($dateRange[0]) && !empty($dateRange[0])) {
                        $query->whereRaw("flow_run.created_at >= '" . ($dateRange[0] . " 00:00:00'"));
                    }
                    if (isset($dateRange[1]) && !empty($dateRange[1])) {
                        $query->whereRaw("flow_run.created_at <= '" . ($dateRange[1] . " 23:59:59'"));
                    }
                }
                $query->with(['flowRunHasManyFlowRunProcess' => function ($query) use ($where) {
                    //用户搜索条件不为空
                    if (isset($where['userIds']) && is_array($where['userIds'])) {
                        $query->whereIn('flow_run_process.user_id', $where['userIds']);
                    }
                }]);
            }]);
        if (isset($where['flowID'])) {
            $query = $query->whereIn('flow_id', explode(',', $where['flowID']));
        }
        if (isset($where['flowsortId']) && !empty($where['flowsortId'])) {
            $query = $query->whereIn('flow_sort', $where['flowsortId']);
        }
        $result = $query->get();
        if (!empty($result)) {
            return $result->toArray();
        } else {
            return array();
        }
    }


    /**
     * 报表-流程报表-流程节点效率分析
     *
     *
     * @param  $datasourceGroupBy 分组依据; $where 数据过滤
     *
     *
     * @return array
     */
    public function getFlowNodeEfficiencyCountGroupByCustomType($datasourceGroupBy, $where = "")
    {
        $search = array();
        $query = $this->entity;
        // 根据流程类型查询
        $query = $query->select(['flow_id', 'flow_name', 'flow_type'])
            ->with(['flowTypeHasManyFlowProcess' => function ($query) use ($where) {
                $query = $query->select(['node_id', 'process_name', 'flow_id'])->orderBy('sort' , 'asc');
            }])
            ->with(['flowTypeHasManyFlowRun' => function ($query) use ($where) {
                $query = $query->select(['run_id', 'flow_id']);
                //创建时间过滤
                if (isset($where['date_range'])) {
                    $dateRange = explode(',', $where['date_range']);
                    if (isset($dateRange[0]) && !empty($dateRange[0])) {
                        $query->whereRaw("flow_run.created_at >= '" . ($dateRange[0] . " 00:00:00'"));
                    }
                    if (isset($dateRange[1]) && !empty($dateRange[1])) {
                        $query->whereRaw("flow_run.created_at <= '" . ($dateRange[1] . " 23:59:59'"));
                    }
                }
                $query->with(['flowRunHasManyFlowRunProcess' => function ($query) use ($where) {
                    $query = $query->select(['host_flag', 'user_id', 'receive_time', 'process_time', 'deliver_time', 'saveform_time', 'flow_process', 'flow_id', 'is_effect', 'run_id'])->where('free_process_step' , 0);
                    //用户搜索条件不为空
                    if (isset($where['userIds']) && is_array($where['userIds'])) {
                        $query->whereIn('flow_run_process.user_id', $where['userIds']);
                    }
                }]);
            }]);
        // ->with(['flowTypeHasManyFlowRunProcess' => function ($query) use($where){
        //     $query = $query->select(['host_flag' , 'user_id' , 'receive_time','process_time','deliver_time' , 'saveform_time' ,'flow_process' , 'flow_id','is_effect']);
        //     //用户搜索条件不为空
        //     if (isset($where['userIds']) && is_array($where['userIds']) ) {
        //         $query->whereIn('flow_run_process.user_id', $where['userIds']);
        //     }
        // }]);
        if (isset($where['flowID'])) {
            $query = $query->whereIn('flow_id', explode(',', $where['flowID']));
        }
        if (isset($where['flowsortId']) && !empty($where['flowsortId'])) {
            $query = $query->whereIn('flow_sort', $where['flowsortId']);
        }
        $result = $query->get();
        if (!empty($result)) {
            return $result->toArray();
        } else {
            return array();
        }
    }

    /**
     * 获取可以创建权限的flow_sort为0的流程数据
     * @param $param
     * @return mixed
     */
    public function getAllowCreateFlowTypeNoSortData($param)
    {
        $userId = $param['user_id'];
        $roleId = $param['role_id'];
        $deptId = $param['dept_id'];
        $fixedFlowTypeInfo = $param['fixedFlowTypeInfo'];
        $builder = $this->entity->select("flow_type.flow_id", "flow_type.flow_type", "flow_type.flow_name", "flow_type.flow_sort", "flow_type.flow_name_zm", "flow_type.flow_name_py", "flow_favorite.favorite_id")
            ->where('flow_sort', 0)->where('is_using', '1')->orders(['flow_noorder' => 'ASC', 'flow_id' => 'ASC'])
            ->leftJoin('flow_favorite', function ($join) use ($userId, $param) {
                $join->on('flow_favorite.flow_id', '=', 'flow_type.flow_id')->where('flow_favorite.user_id', $userId);
            });
        if (isset($param['search']['favorite'])) { // 手机版常用流程
            if ($param["search"]["favorite"][0] == "1") {
                $builder->where('flow_favorite.favorite_id', '!=', null);
            } else if ($param["search"]["favorite"][0] == "0") {
                $builder->where('flow_favorite.favorite_id', null);
            }
        }
        if (isset($param['controlFlows']) && !empty($param['controlFlows'])) {
            $builder->whereNotIn('flow_type.flow_id' , $param['controlFlows']);
        }
        if (isset($param["search"]["flow_name"][0])) {
            $flowNameValue = $param["search"]["flow_name"][0];
            $builder->where(function ($query) use ($flowNameValue) {
                $query->where('flow_name', 'like', '%' . $flowNameValue . '%')
                    ->orWhere('flow_name_py', 'like', '%' . $flowNameValue . '%')
                    ->orWhere('flow_name_zm', 'like', '%' . $flowNameValue . '%');
            });
            unset($param["search"]["flow_name"]);
        }
        $builder->where(function ($query) use ($userId, $roleId, $deptId, $fixedFlowTypeInfo) {
            $query->where(function ($query) use ($userId, $roleId, $deptId, $fixedFlowTypeInfo) {
                // 固定流程
                $query->where('flow_type', '1')
                    ->whereIn('flow_type.flow_id', $fixedFlowTypeInfo);
            });
            $query->orWhere(function ($query) use ($userId, $roleId, $deptId) {
                // 自由流程
                $query->where('flow_type', '2')->where(function ($query) use ($userId, $roleId, $deptId) {
                    $query->where('create_user', 'ALL');
                    $query->orWhere('create_role', 'ALL');
                    $query->orWhere('create_dept', 'ALL');
                    if ($userId) {
                        $query->orWhereHas('flowTypeHasManyCreateUser', function ($query) use ($userId) {
                            $query->wheres(['user_id' => [$userId]]);
                        });
                    }
                    if ($roleId) {
                        $query->orWhereHas('flowTypeHasManyCreateRole', function ($query) use ($roleId) {
                            $query->wheres(['role_id' => [explode(",", trim($roleId, ",")), 'in']]);
                        });
                    }
                    if ($deptId) {
                        $query->orWhereHas('flowTypeHasManyCreateDept', function ($query) use ($deptId) {
                            $query->wheres(['dept_id' => [explode(",", trim($deptId, ",")), 'in']]);
                        });
                    }
                });
            });
        });
        if ($param["returntype"] == "array") {
            return $builder->get()->toArray();
        } else if ($param["returntype"] == "count") {
            return $builder->count();
        } else if ($param["returntype"] == "object") {
            return $builder->get();
        }
        return $builder->get();
    }


    /**
     * 获取流程未分类的数量
     *
     * @param array $param 传入的参数
     *
     * @return array            流程数据
     * @since  2015-10-16       创建
     *
     * @author wz
     *
     */
    public function getflowNoSortCount($param = [])
    {
        return  $this->entity->where('flow_sort' , 0)->count();
    }
}
