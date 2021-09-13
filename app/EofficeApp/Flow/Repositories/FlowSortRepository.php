<?php

namespace App\EofficeApp\Flow\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Flow\Entities\FlowSortEntity;
use App\EofficeApp\Flow\Entities\FlowTypeEntity;
use DB;

/**
 * 流程分类表知识库
 *
 * @author 丁鹏
 *
 * @since  2015-10-16 创建
 */
class FlowSortRepository extends BaseRepository
{
    public function __construct(FlowSortEntity $entity)
    {
        parent::__construct($entity);
    }

    /**
     * 获取当前人员可以新建的流程类别;带查询
     *
     * @param array $param 传入的参数
     *
     * @return array            流程数据
     * @since  2015-10-16       创建
     *
     * @author 丁鹏
     *
     */
    function flowNewIndexCreateListRepository($param = [])
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
            'order_by' => ['noorder' => 'ASC', 'id' => 'ASC'],
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
            ->select($param['fields'])
            ->orders($param['order_by'])
            ->whereHas('flowSortHasManyFlowType', function ($query) use ($param, $userId, $roleId, $deptId, $fixedFlowTypeInfo) {
                $query->where('is_using', '1');
                // 如果有查询
                if (isset($param["search"]["flow_name"])) {
                    $flowNameValue = $param["search"]["flow_name"][0];
                    $query = $query->where(function ($query) use ($flowNameValue) {
                        $query->where('flow_name', 'like', '%' . $flowNameValue . '%')
                            ->orWhere('flow_name_py', 'like', '%' . $flowNameValue . '%')
                            ->orWhere('flow_name_zm', 'like', '%' . $flowNameValue . '%');
                    });
                }
                if (isset($param['controlFlows']) && !empty($param['controlFlows'])) {
                     $query->whereNotIn('flow_type.flow_id' , $param['controlFlows']);
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
            })
            ->with(['flowSortHasManyFlowType' => function ($query) use ($param, $userId, $roleId, $deptId, $fixedFlowTypeInfo) {
                $query->orders(['flow_noorder' => 'ASC', 'flow_type.flow_id' => 'ASC']);
                // ip控制
                if (isset($param['controlFlows']) && !empty($param['controlFlows'])) {
                     $query->whereNotIn('flow_type.flow_id' , $param['controlFlows']);
                }
                $query->select("flow_type.flow_id", "flow_type.flow_type", "flow_name", "flow_sort", "flow_name_zm", "flow_name_py", "flow_favorite.favorite_id")
                    ->where('is_using', '1')
                    // 关联查询是否被添加到了常用流程中
                    ->leftJoin('flow_favorite', function ($join) use ($userId) {
                        $join->on('flow_favorite.flow_id', '=', 'flow_type.flow_id')
                            ->where('flow_favorite.user_id', $userId);
                    });
                if (isset($param["search"]["flow_name"])) {
                    $flowNameValue = $param["search"]["flow_name"][0];
                    $query = $query->where(function ($query) use ($flowNameValue) {
                        $query->where('flow_name', 'like', '%' . $flowNameValue . '%')
                            ->orWhere('flow_name_py', 'like', '%' . $flowNameValue . '%')
                            ->orWhere('flow_name_zm', 'like', '%' . $flowNameValue . '%');
                    });
                }
                // 手机版-【常用】筛选
                if (isset($param["search"]["favorite"])) {
                    if ($param["search"]["favorite"][0] == "1") {
                        $query = $query->where("flow_favorite.user_id", $userId);
                    } else if ($param["search"]["favorite"][0] == "0") {
                        $query = $query->where("flow_favorite.favorite_id", null);
                    }
                }
                $query->where(function ($query) use ($userId, $roleId, $deptId, $fixedFlowTypeInfo) {
                    $query = $query->where(function ($query) use ($userId, $roleId, $deptId, $fixedFlowTypeInfo) {
                        // 固定流程
                        $query = $query->where('flow_type', '1')->whereIn('flow_type.flow_id', $fixedFlowTypeInfo);
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
            }])
            // ->with(['flowSortHasManyFlowType.flowTypeHasManyFlowFavorite' => function($query) use ($userId) {
            //     $query->where("user_id",$userId);
            // }])
        ;
        // 翻页判断
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
    function flowNewIndexCreateTotalRepository($param = [])
    {
        $param["page"] = "0";
        $param["returntype"] = "count";
        return $this->flowNewIndexCreateListRepository($param);
    }

    /**
     * 获取流程抄送的流程类别&流程名称列表，拼接每个流程下有多少流程抄送
     * @param array $param
     * @return \Illuminate\Database\Eloquent\Model|object
     */
    public function getFlowCopySortListRepository($param = [])
    {
        $userId = isset($param["user_id"]) ? $param["user_id"] : "";
        if ($userId == "") {
            return $this->entity;
        }
        $query = $this->entity
            ->select('id', 'title')
            ->whereHas('flowSortHasManyFlowType', function ($query) use ($param) {
                if (isset($param["flow_name"])) {
                    $query->where('flow_name', 'like', '%' . $param["flow_name"] . '%');
                }
            })
            ->orderBy('noorder', 'asc')
            ->with(['flowSortHasManyFlowType' => function ($query) use ($param) {
                $query->select('flow_id', 'flow_name', 'flow_sort')
                    ->whereHas('flowTypeHasManyFlowCopy', function ($query) use ($param) {
                        $query->where('by_user_id', $param["user_id"]);
                    })
                    ->with(['flowTypeHasManyFlowCopy' => function ($query) use ($param) {
                        $query->select('flow_id')
                            ->selectRaw("count(*) as total")
                            ->where('by_user_id', $param["user_id"])
                            ->groupBy('flow_id');
                    }])->orders(['flow_noorder' => 'asc', 'flow_type.flow_id' => 'asc']);
                if (isset($param["flow_name"])) {
                    $query->where('flow_name', 'like', '%' . $param["flow_name"] . '%');
                }
                if (isset($param['controlFlows']) && !empty($param['controlFlows'])) {
                      $query->whereNotIn('flow_type.flow_id' , $param['controlFlows']);
                }
                $query->where("hide_running", "=", '0');
            }]);
        // ->with(['flowSortHasManyFlowTypeCount' => function ($query) use ($param) {
        //     $query->select('flow_id', 'flow_sort')
        //         ->whereHas('flowTypeHasManyFlowCopy', function ($query) use ($param) {
        //             $query->where('by_user_id',$param["user_id"]);
        //         })
        //         ->selectRaw("count(*) as total")
        //         ->groupBy('flow_sort');
        //     if (isset($param["flow_name"])) {
        //         $query->where('flow_name', 'like', '%' . $param["flow_name"] . '%');
        //     }
        // }])
        $builder = FlowTypeEntity::select('flow_id', 'flow_name', 'flow_sort')->where('flow_sort', 0)
            ->whereHas('flowTypeHasManyFlowCopy', function ($query) use ($param) {
                $query->where('by_user_id', $param["user_id"]);
            })
            ->with(['flowTypeHasManyFlowCopy' => function ($query) use ($param) {
                $query->select('flow_id')
                    ->selectRaw("count(*) as total")
                    ->where('by_user_id', $param["user_id"])
                    ->groupBy('flow_id');
            }])->orders(['flow_noorder' => 'asc', 'flow_type.flow_id' => 'asc']);
        if (isset($param['controlFlows']) && !empty($param['controlFlows'])) {
            $builder->whereNotIn('flow_type.flow_id' , $param['controlFlows']);
        }
        if (isset($param["flow_name"])) {
            $builder->where('flow_name', 'like', '%' . $param["flow_name"] . '%');
        }
        $builder->where("hide_running", "=", '0');
        $noSortFlow = collect([
            'id' => 0,
            'title' => trans('flow.unclassified'),
            'flow_sort_has_many_flow_type' => $builder->get()
        ]);
        return $query->get()->push($noSortFlow);
    }

    /**
     * 获取流程类别列表，关联展示每个类别下面的已定义流程
     *
     * @method getFlowCopySortListRepository
     *
     * @param array $param [description]
     *
     * @return [type]                               [description]
     */
    public function getFlowSortListRelateFlowType($param = [])
    {
        $default = [
            'fields' => ['*'],
            'page' => 0,
            'limit' => config('eoffice.pagesize'),
            'search' => [],
            'order_by' => ['noorder' => 'asc'],
            'returntype' => 'object',
        ];
        $param = array_merge($default, array_filter($param));
        $query = $this->entity
            ->select($param['fields'])
            ->wheres($param['search'])
            ->orders($param['order_by'])
            ->whereHas('flowSortHasManyFlowType', function ($query) use ($param) {
                if (isset($param["flow_name"])) {
                    $query->where('flow_name', 'like', '%' . $param["flow_name"] . '%');
                }
            })
            ->with(['flowSortHasManyFlowType' => function ($query) use ($param) {
                $query->select('flow_id', 'flow_name', 'flow_sort', 'is_using')
                    ->orders(['flow_noorder' => 'asc', 'flow_type.flow_id' => 'asc']);
                if (isset($param["flow_name"])) {
                    $query->where('flow_name', 'like', '%' . $param["flow_name"] . '%');
                }
            }]);
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
     * 获取流程类别列表，计算下属定义流程的数量
     *
     * @method getFlowCopySortListRepository
     *
     * @param array $param [description]
     *
     * @return [type]                               [description]
     */
    public function getFlowSortListRelateFlowTypeCount($param = [])
    {
        $query = $this->entity
            ->select('id', 'title', 'noorder')
            ->with(['flowSortHasManyFlowTypeCount' => function ($query) use ($param) {
                $query->select('flow_id', 'flow_sort')
                    ->selectRaw("count(*) as total")
                    ->groupBy('flow_sort');
                if (isset($param["flow_name"])) {
                    $query->where('flow_name', 'like', '%' . $param["flow_name"] . '%');
                }
            }]);
        if (isset($param["title"])) {
            $query = $query->where("title", 'like', '%' . $param["title"] . '%');
        }
        if (isset($param["id"])) {
            $query = $query->where("id", $param["id"]);
        }
        if (isset($param["getDataType"]) && ($param["getDataType"] == "allData")) {
            $query = $query->orders(['noorder' => 'asc', 'id' => 'asc']);
            return $query->get()->toArray();
        } else {
            $default = [
                'fields' => ['*'],
                'page' => 0,
                'limit' => config('eoffice.pagesize'),
                'search' => [],
                'order_by' => ['noorder' => 'asc', 'id' => 'asc'],
                'returntype' => 'array',
            ];
            $param = array_merge($default, array_filter($param));

            if (!(isset($param["getDataType"]) && $param["getDataType"] == "grid")) {
                if (isset($param['flow_sort']) && !empty($param['flow_sort'])) {
                    $query = $query->whereIn('id', $param['flow_sort']);
                }

                $query->whereHas('flowSortHasManyFlowType', function ($query) use ($param) {
                    if (isset($param["flow_name"])) {
                        $query->where('flow_name', 'like', '%' . $param["flow_name"] . '%');
                    }
                    if (isset($param["flow_type"])) {
                        $query->where('flow_type', $param["flow_type"]);
                    }
                });
            }

            $query = $query->wheres($param["search"])
                ->orders($param['order_by'])
                ->parsePage($param['page'], $param['limit']);
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
    }

    /**
     * 获取流程类别列表,附带权限
     *
     * @method getFlowCopySortListRepository
     *
     * @param array $param [description]
     *
     * @return [type]                               [description]
     */
    public function getFlowSortListForMiddle($param = [])
    {
        $query = $this->entity
            ->select('id', 'title', 'noorder');
        if (isset($param["title"])) {
            $query = $query->where("title", 'like', '%' . $param["title"] . '%');
        }
        if (isset($param["id"])) {
            $query = $query->where("id", $param["id"]);
        }
        if (isset($param['flow_sort']) && !empty($param['flow_sort'])) {
            $query = $query->whereIn('id', $param['flow_sort']);
        }

        $query = $query->orders(['noorder' => 'asc', 'id' => 'asc']);
        return $query->get();
    }

    /**
     * 获取待办已办办结流程的列表数量
     *
     * @method getFlowRunStepList
     *
     * @param array $param [description]
     *
     * @return [type]                       [description]
     */
    function getFlowSortListRelateFlowTypeCountTotal($param = [])
    {
        $param["page"] = "0";
        $param["returntype"] = "count";
        return $this->getFlowSortListRelateFlowTypeCount($param);
    }

    /**
     * 获取流程类别列表，下面分别展示各类别的定义流程
     *
     * @method getFlowCopySortListRepository
     *
     * @param array $param [description]
     *
     * @return [type]                               [description]
     */
    public function getFlowDefineRelateFlowSort($param = [])
    {
        $query = $this->entity
            ->select('id', 'title');
        if (isset($param["search"]["id"])) {
            $query->wheres($param["search"]);
        }
        $query->whereHas('flowSortHasManyFlowType', function ($query) use ($param) {
            if (isset($param["flow_name"])) {
                $query->where('flow_name', 'like', '%' . $param["flow_name"] . '%');
            }
        })
            ->orderBy('noorder', 'asc')
            ->with(['flowSortHasManyFlowType' => function ($query) use ($param) {
                $query->select('flow_id', 'flow_name', 'flow_sort')
                    ->orders(['flow_noorder' => 'asc', 'flow_type.flow_id' => 'asc']);
                if (isset($param["flow_name"])) {
                    $query->where('flow_name', 'like', '%' . $param["flow_name"] . '%');
                }
            }])
            ->with(['flowSortHasManyFlowTypeCount' => function ($query) use ($param) {
                $query->select('flow_id', 'flow_sort')
                    ->selectRaw("count(*) as total")
                    ->groupBy('flow_sort');
                if (isset($param["flow_name"])) {
                    $query->where('flow_name', 'like', '%' . $param["flow_name"] . '%');
                }
            }]);
        return $query->get();
    }

    /**
     * 解析待办已办办结的参数
     *
     * @method parseFlowRunStepParamForSortList
     *
     * @param  [type]                           $query [description]
     * @param  [type]                           $param [description]
     *
     * @return [type]                                  [description]
     */
    public function parseFlowRunStepParamForSortList($query, $param)
    {
        $userId = isset($param["user_id"]) ? $param["user_id"] : "";
        $query = $query
            ->where("is_effect", '1')
            ->where("user_id", $userId);
        if (isset($param['getListType'])) {
            if ($param['getListType'] == "todo") {
                $query = $query->where("user_run_type", "1");
            } else if ($param['getListType'] == "already") {
                $query = $query->where("user_run_type", "2");
            } else if ($param['getListType'] == "finished") {
                $query = $query->where("user_run_type", "3");
            }
        }
        $query = $query->where('user_last_step_flag', 1);
        return $query;
    }

    /**
     *获取待办已办办结-流程类别列表
     *
     * @method getFlowCopySortListRepository
     *
     * @param array $param [description]
     *
     * @return [type]                               [description]
     */
    public function getFlowRunStepSortListRepository($param)
    {
        $userId = isset($param["user_id"]) ? $param["user_id"] : "";
        if ($userId == "") {
            return $this->entity;
        }
        $query = $this->entity
            ->select('id', 'title')
            // ->whereHas('flowSortHasManyFlowType', function ($query) use ($param) {
            //     if (isset($param["flow_name"])) {
            //         $query->where('flow_name', 'like', '%' . $param["flow_name"] . '%');
            //     }
            //     $query->whereHas('flowTypeHasManyFlowRunStep', function ($query) use ($param) {
            //         $query = $this->parseFlowRunStepParamForSortList($query, $param);
            //     });
            // })
            ->orderBy('noorder', 'asc')
            ->with(['flowSortHasManyFlowType' => function ($query) use ($param) {
                $query->select('flow_id', 'flow_name', 'flow_sort', 'is_using')
                    // ->whereHas('flowTypeHasManyFlowRunStep', function ($query) use ($param) {
                    //     $query = $this->parseFlowRunStepParamForSortList($query, $param);
                    // })
                    ->with(['flowTypeHasManyFlowRunProcess' => function ($query) use ($param) {
                        $query->select('flow_id')
                            ->selectRaw("count(*) as total")
                            ->groupBy('flow_id');
                        $query = $this->parseFlowRunStepParamForSortList($query, $param);
                        $query->where("hangup", "0");
                    }])->orders(['flow_noorder' => 'asc', 'flow_type.flow_id' => 'asc']);
                // 待办事宜需要计算未读数量
                if (isset($param['getListType']) && $param['getListType'] == "todo") {
                    $query->with(['flowTypeHasManyUnReadFlowRunProcess' => function ($query) use ($param) {
                        $query->select('flow_id')
                            ->selectRaw("count(*) as total")
                            ->groupBy('flow_id')
                            ->whereNull("process_time");
                        $query = $this->parseFlowRunStepParamForSortList($query, $param);
                        $query->where("hangup", "0");
                    }]);
                }
                if (isset($param["flow_name"])) {
                    $query->where('flow_name', 'like', '%' . $param["flow_name"] . '%');
                }
            }]);
        // ->with(['flowSortHasManyFlowTypeCount' => function ($query) use ($param) {
        //     $query->select('flow_id', 'flow_sort')
        //         ->whereHas('flowTypeHasManyFlowRunStep', function ($query) use ($param) {
        //             $query = $this->parseFlowRunStepParamForSortList($query, $param);
        //         })
        //         ->selectRaw("count(*) as total")
        //         ->groupBy('flow_sort');
        //     if (isset($param["flow_name"])) {
        //         $query->where('flow_name', 'like', '%' . $param["flow_name"] . '%');
        //     }
        // }])

        // 处理未分类的flow_type数据
        $builder = FlowTypeEntity::select('flow_id', 'flow_name', 'flow_sort', 'is_using')->where('flow_sort', 0)
            ->with(['flowTypeHasManyFlowRunProcess' => function ($query) use ($param) {
                $query->select('flow_id')
                    ->selectRaw("count(*) as total")
                    ->groupBy('flow_id');
                $query = $this->parseFlowRunStepParamForSortList($query, $param);
                $query->where("hangup", "0");
            }])->orders(['flow_noorder' => 'asc', 'flow_type.flow_id' => 'asc']);
        // 待办事宜需要计算未读数量
        if (isset($param['getListType']) && $param['getListType'] == "todo") {
            $builder->with(['flowTypeHasManyUnReadFlowRunProcess' => function ($query) use ($param) {
                $query->select('flow_id')
                    ->selectRaw("count(*) as total")
                    ->groupBy('flow_id')
                    ->whereNull("process_time");
                $query = $this->parseFlowRunStepParamForSortList($query, $param);
                $query->where("hangup", "0");
            }]);
        }
        if (isset($param["flow_name"])) {
            $builder->where('flow_name', 'like', '%' . $param["flow_name"] . '%');
        }
        $noSortFlow = collect([
            'id' => 0,
            'title' => trans('flow.unclassified'),
            'flow_sort_has_many_flow_type' => $builder->get()
        ]);
        return $query->get()->push($noSortFlow);

    }

    /**
     *获取【我的请求】-流程类别列表
     *
     * @method getFlowCopySortListRepository
     *
     * @param array $param [description]
     *
     * @return [type]                               [description]
     */
    public function getMyRequestSortListRepository($param = [])
    {
        $userId = isset($param["user_id"]) ? $param["user_id"] : "";
        if ($userId == "") {
            return $this->entity;
        }
        $query = $this->entity
            ->select('id', 'title')
            // ->whereHas('flowSortHasManyFlowType', function ($query) use ($param, $userId) {
            //     if (isset($param["flow_name"])) {
            //         $query->where('flow_name', 'like', '%' . $param["flow_name"] . '%');
            //     }
            //     $query->whereHas('flowTypeHasManyFlowRun', function ($query) use ($param, $userId) {
            //         $query->where("creator", $userId)->where("is_effect", '1');
            //     });
            // })
            ->orderBy('noorder', 'asc')
            ->with(['flowSortHasManyFlowType' => function ($query) use ($param, $userId) {
                $query->select('flow_id', 'flow_name', 'flow_sort')
                    // ->whereHas('flowTypeHasManyFlowRun', function ($query) use ($param, $userId) {
                    //     $query->where("creator", $userId)->where("is_effect", '1');
                    // })
                    ->with(['flowTypeHasManyFlowRun' => function ($query) use ($param, $userId) {
                        $query->select('flow_id')
                            ->selectRaw("count(*) as total")
                            ->groupBy('flow_id')
                            ->where("creator", $userId)
                            ->where("is_effect", '1');
                    }])->orders(['flow_noorder' => 'asc', 'flow_type.flow_id' => 'asc']);
                if (isset($param["flow_name"])) {
                    $query->where('flow_name', 'like', '%' . $param["flow_name"] . '%');
                }
            }]);
        // ->with(['flowSortHasManyFlowTypeCount' => function ($query) use ($param, $userId) {
        //     $query->select('flow_id', 'flow_sort')
        //         ->whereHas('flowTypeHasManyFlowRun', function ($query) use ($param, $userId) {
        //             $query->where("creator", $userId)->where("is_effect", '1');
        //         })
        //         ->selectRaw("count(*) as total")
        //         ->groupBy('flow_sort');
        //     if (isset($param["flow_name"])) {
        //         $query->where('flow_name', 'like', '%' . $param["flow_name"] . '%');
        //     }
        // }])

        // 处理未分类的flow_type数据
        $builder = FlowTypeEntity::select('flow_id', 'flow_name', 'flow_sort', 'is_using')->where('flow_sort', 0)
            ->with(['flowTypeHasManyFlowRun' => function ($query) use ($param, $userId) {
                $query->select('flow_id')
                    ->selectRaw("count(*) as total")
                    ->groupBy('flow_id')
                    ->where("creator", $userId)
                    ->where("is_effect", '1');
            }])->orders(['flow_noorder' => 'asc', 'flow_type.flow_id' => 'asc']);
        if (isset($param["flow_name"])) {
            $builder->where('flow_name', 'like', '%' . $param["flow_name"] . '%');
        }
        $noSortFlow = collect([
            'id' => 0,
            'title' => trans('flow.unclassified'),
            'flow_sort_has_many_flow_type' => $builder->get()
        ]);
        return $query->get()->push($noSortFlow);
    }

    /**
     *获取【流程监控】-流程类别列表
     *
     * @method getFlowCopySortListRepository
     *
     * @param array $param [description]
     *
     * @return [type]                               [description]
     */
    public function getMonitorSortListRepository($param = [])
    {
        $userId = isset($param["user_id"]) ? $param["user_id"] : "";
        if ($userId == "") {
            return $this->entity;
        }

        $monitorParams = isset($param['monitor_params']) ? $param['monitor_params'] : false;
        $monitorFlowIds = isset($param['monitor_flow_id']) ? $param['monitor_flow_id'] : [];
        if (empty($monitorFlowIds)) {
            return $this->entity;
        }

        $query = $this->entity
            ->select('id', 'title')
            ->orderBy('noorder', 'asc')
            ->whereHas('flowSortHasManyFlowType', function ($query) use ($param, $userId, $monitorFlowIds) {
                if (isset($param["flow_name"])) {
                    $query->where('flow_name', 'like', '%' . $param["flow_name"] . '%');
                }
                $query->whereIn('flow_id', $monitorFlowIds);
                $query->whereHas('flowTypeHasManyFlowRun', function ($query) use ($param) {
                    $query->select('flow_id')->where("current_step", "!=", "0")->where("is_effect", '1');
                });
            })
            ->with(['flowSortHasManyFlowType' => function ($query) use ($param, $userId, $monitorParams, $monitorFlowIds) {
                $query->select('flow_id', 'flow_name', 'flow_sort')
                    ->whereHas('flowTypeHasManyFlowRun', function ($query) use ($param) {
                        $query->select('flow_id')->where("current_step", "!=", "0")->where("is_effect", '1');
                    })
                    ->with(['flowTypeHasManyFlowRun' => function ($query) use ($param, $monitorParams) {
                        $query->select('flow_id')
                            ->selectRaw("count(*) as total")
                            ->groupBy('flow_id');
                        $query->where("current_step", "!=", "0")->where("is_effect", '1');
                        if ($monitorParams !== false) {
                            unset($param['monitor_params']);
                            if (!empty($monitorParams)) {
                                $query = $query->where(function ($query) use ($monitorParams) {
                                    $flowIdArray = [];
                                    foreach ($monitorParams as $key => $value) {
                                        if (isset($value['user_id']) && $value['user_id'] == 'all') {
                                            $flowIdArray[] = $value['flow_id'];
                                        }
                                        $query = $query->orWhere(function ($query) use ($value) {
                                            if (isset($value['flow_id']) && !empty($value['flow_id'])) {
                                                if (isset($value['user_id']) && !empty($value['user_id'])) {
                                                    if ($value['user_id'] != 'all') {
                                                        $query = $query->where('flow_id', $value['flow_id'])
                                                            ->whereIn('creator', $value['user_id']);
                                                    }
                                                } else {
                                                    $query = $query->where('flow_id', $value['flow_id'])
                                                        ->whereNull('creator');
                                                }
                                            }
                                        });
                                    }
                                    if (!empty($flowIdArray)) {
                                        $qeury = $query->orWhereIn('flow_id', $flowIdArray);
                                    }
                                });
                            }
                        }
                    }])->orders(['flow_noorder' => 'asc', 'flow_type.flow_id' => 'asc']);
                if (isset($param["flow_name"])) {
                    $query->where('flow_name', 'like', '%' . $param["flow_name"] . '%');
                }
                if (isset($param["controlFlows"]) && !empty($param["controlFlows"])) {
                    $query->whereNotIn('flow_id' , $param['controlFlows']);
                }
                $query->whereIn('flow_id', $monitorFlowIds);
                $query->where("hide_running", "=", '0');
            }])
            // 20170809.dp.速度优化，检查前端，没有地方使用这个值，注释掉
            // ->with(['flowSortHasManyFlowTypeCount' => function ($query) use ($param, $userId) {
            //     $query->select('flow_id', 'flow_sort')
            //         ->whereHas('flowTypeHasManyFlowRun', function ($query) use ($param) {
            //             $query->where("current_step", "!=", "0");
            //         })
            //         ->selectRaw("count(*) as total")
            //         ->groupBy('flow_sort');
            //     if (isset($param["flow_name"])) {
            //         $query->where('flow_name', 'like', '%' . $param["flow_name"] . '%');
            //     }
            //     $query->whereHas('flowTypeHasManyManageUser', function ($query) use ($userId) {
            //         $query->wheres(['user_id' => [$userId]]);
            //     });
            // }])
        ;

        // 合并未分类的数据

        $builder = FlowTypeEntity::select('flow_id', 'flow_name', 'flow_sort')->where('flow_sort', 0)
            ->whereHas('flowTypeHasManyFlowRun', function ($query) use ($param) {
                $query->select('flow_id')->where("current_step", "!=", "0")->where("is_effect", '1');
            })
            ->with(['flowTypeHasManyFlowRun' => function ($query) use ($param, $monitorParams) {
                $query->select('flow_id')
                    ->selectRaw("count(*) as total")
                    ->groupBy('flow_id');
                $query->where("current_step", "!=", "0")->where("is_effect", '1');
                if ($monitorParams !== false) {
                    unset($param['monitor_params']);
                    if (!empty($monitorParams)) {
                        $query = $query->where(function ($query) use ($monitorParams) {
                            $flowIdArray = [];
                            foreach ($monitorParams as $key => $value) {
                                if (isset($value['user_id']) && $value['user_id'] == 'all') {
                                    $flowIdArray[] = $value['flow_id'];
                                }
                                $query = $query->orWhere(function ($query) use ($value) {
                                    if (isset($value['flow_id']) && !empty($value['flow_id'])) {
                                        if (isset($value['user_id']) && !empty($value['user_id'])) {
                                            if ($value['user_id'] != 'all') {
                                                $query = $query->where('flow_id', $value['flow_id'])
                                                    ->whereIn('creator', $value['user_id']);
                                            }
                                        } else {
                                            $query = $query->where('flow_id', $value['flow_id'])
                                                ->whereNull('creator');
                                        }
                                    }
                                });
                            }
                            if (!empty($flowIdArray)) {
                                $qeury = $query->orWhereIn('flow_id', $flowIdArray);
                            }
                        });
                    }
                }
            }])->orders(['flow_noorder' => 'asc', 'flow_type.flow_id' => 'asc']);
        if (isset($param["flow_name"])) {
            $builder->where('flow_name', 'like', '%' . $param["flow_name"] . '%');
        }
        if (isset($param["controlFlows"]) && !empty($param["controlFlows"])) {
            $builder->whereNotIn('flow_id' , $param['controlFlows']);
        }
        $builder->whereIn('flow_id', $monitorFlowIds);
        $builder->where("hide_running", "=", '0');

        $noSortFlow = collect([
            'id' => 0,
            'title' => trans('flow.unclassified'),
            'flow_sort_has_many_flow_type' => $builder->get()
        ]);

        return $query->get()->push($noSortFlow);
    }

    /**
     * 解析超时查询的参数
     *
     * @method parseFlowRunProcessOvertimeParamForSortList
     *
     * @param  [type]                           $query [description]
     * @param  [type]                           $param [description]
     *
     * @return [type]                                  [description]
     */
    public function parseFlowRunProcessOvertimeParamForSortList($query, $param, $currentTime)
    {
        $query = $query
            ->whereNotNull("limit_date")
            ->where("limit_date", "!=", "0000-00-00 00:00:00")
            ->where(function ($query) use ($currentTime) {
                $query->where(function ($query) use ($currentTime) {
                    $query->where("host_flag", "1")
                        ->where(function ($query) use ($currentTime) {
                            $query->where(function ($query) use ($currentTime) {
                                $query->whereNotNull("deliver_time")
                                    ->whereColumn("deliver_time", '>', 'limit_date');
                            })
                                ->orWhere(function ($query) use ($currentTime) {
                                    $query->whereNull("deliver_time")
                                        ->where("limit_date", '<', $currentTime);
                                });
                        });
                })
                    ->orWhere(function ($query) use ($currentTime) {
                        $query->where("host_flag", "0")
                            ->where(function ($query) use ($currentTime) {
                                $query->where(function ($query) use ($currentTime) {
                                    $query->whereNotNull("saveform_time")
                                        ->whereColumn("saveform_time", '>', 'limit_date');
                                })
                                    ->orWhere(function ($query) use ($currentTime) {
                                        $query->whereNull("saveform_time")
                                            ->where("limit_date", '<', $currentTime);
                                    });
                            });
                    });
            });
        return $query;
    }

    /**
     *获取【超时查询】-流程类别列表
     *
     * @method getFlowCopySortListRepository
     *
     * @param array $param [description]
     *
     * @return [type]                               [description]
     */
    public function getOvertimeSortListRepository($param = [])
    {
            $monitorParams   = isset($param['monitor_params']) && !empty($param['monitor_params']) ? $param['monitor_params'] : false;
            $currentTime = date('Y-m-d H:i:s');
            $query = $this->entity
                ->select('id', 'title')
                ->orderBy('noorder', 'asc')
                ->with(['flowSortHasManyFlowType' => function ($query) use ($param, $currentTime ,$monitorParams) {
                    $query->select('flow_id', 'flow_name', 'flow_sort')
                        ->with(['flowTypeHasManyFlowRunProcess' => function ($query) use ($param, $currentTime ,$monitorParams) {
                            $query->select('flow_run_process.flow_id')
                               // ->leftJoin('flow_run', 'flow_run_process.run_id', '=', 'flow_run.run_id')
                                ->selectRaw("count(*) as total")
                                ->groupBy('flow_run_process.flow_id');
                            $query = $this->parseFlowRunProcessOvertimeParamForSortList($query, $param, $currentTime);
                            if (isset($param['user_id']) && $param['user_id'] != 'admin') {
                                $run_ids =  isset($param['monitor_data']['run_ids']) ? $param['monitor_data']['run_ids'] : [];
                                $flow_ids =  isset($param['monitor_data']['flow_ids']) ? $param['monitor_data']['flow_ids'] : [];
                                $query = $query->where(function ($query) use ($param ,$monitorParams ,   $run_ids , $flow_ids) {
                                        $query = $query->orWhere('flow_run_process.user_id', $param['user_id']);
                                        if (!empty($run_ids)) {
                                           $query = $query->orWhereIn('flow_run_process.run_id',  $run_ids );
                                        }
                                        if (!empty($flow_ids)) {
                                           $query = $query->orWhereIn('flow_run_process.flow_id',  $flow_ids );
                                        }
                                        // ->orWhere('flow_run.creator', $param['user_id']);
                                        // $userId = $param['user_id'];
                                        // if ($monitorParams !== false) {
                                        //             if (!empty($monitorParams)) {
                                        //                 $query = $query->orWhere(function ($query) use ($userId , $monitorParams) {
                                        //                     $flowIdArray = [];
                                        //                     foreach ($monitorParams as $key => $value) {
                                        //                         if (isset($value['user_id']) && $value['user_id'] == 'all') {
                                        //                             $flowIdArray[] = $value['flow_id'];
                                        //                         } else {
                                        //                             $query = $query->orWhere(function ($query) use ($value) {
                                        //                               if (isset($value['flow_id']) && !empty($value['flow_id'])) {
                                        //                                   if (isset($value['user_id']) && !empty($value['user_id'])) {
                                        //                                       if ($value['user_id'] != 'all') {
                                        //                                           $query = $query->where('flow_run_process.flow_id', $value['flow_id'])
                                        //                                               ->whereIn('flow_run.creator', $value['user_id']);
                                        //                                       }
                                        //                                   } else {
                                        //                                       $query = $query->where('flow_run.flow_id', $value['flow_id'])
                                        //                                           ->whereNull('flow_run.creator');
                                        //                                   }
                                        //                               }
                                        //                           });
                                        //                         }
                                        //                     }
                                        //                     if (!empty($flowIdArray)) {
                                        //                         $qeury = $query->orWhereIn('flow_run_process.flow_id', $flowIdArray);
                                        //                     }
                                        //                 });
                                        //             }
                                        // }
                                });
                            }
                        }])->orders(['flow_noorder' => 'asc', 'flow_type.flow_id' => 'asc']);
                    if (isset($param["flow_name"])) {
                        $query->where('flow_name', 'like', '%' . $param["flow_name"] . '%');
                    }
                    if (isset($param["controlFlows"]) && !empty($param["controlFlows"])) {
                         $query->whereNotIn('flow_type.flow_id' , $param['controlFlows']);
                    }
                    $query->where("hide_running", "=", '0');
                }]);
            // 处理未分类的数据
            $builder1 = FlowTypeEntity::select('flow_id', 'flow_name', 'flow_sort')->where('flow_sort', 0)
                ->with(['flowTypeHasManyFlowRunProcess' => function ($query) use ($param, $currentTime ,$monitorParams) {
                    $query->select('flow_run_process.flow_id')
                       // ->leftJoin('flow_run', 'flow_run_process.run_id', '=', 'flow_run.run_id')
                        ->selectRaw("count(*) as total")
                        ->groupBy('flow_run_process.flow_id');
                    $query = $this->parseFlowRunProcessOvertimeParamForSortList($query, $param, $currentTime);
                    if (isset($param['user_id']) && $param['user_id'] != 'admin') {
                         $run_ids =  isset($param['monitor_data']['run_ids']) ? $param['monitor_data']['run_ids'] : [];
                         $flow_ids =  isset($param['monitor_data']['flow_ids']) ? $param['monitor_data']['flow_ids'] : [];
                         $query = $query->where(function ($query) use ($param ,$monitorParams ,$run_ids , $flow_ids) {
                                        $query = $query->orWhere('flow_run_process.user_id', $param['user_id']);
                                        if (!empty($run_ids)) {
                                           $query = $query->orWhereIn('flow_run_process.run_id',  $run_ids );
                                        }
                                        if (!empty($flow_ids)) {
                                           $query = $query->orWhereIn('flow_run_process.flow_id',  $flow_ids );
                                        }
                                        // $userId = $param['user_id'];
                                        // if ($monitorParams !== false) {
                                        //             if (!empty($monitorParams)) {
                                        //                 $query = $query->orWhere(function ($query) use ($userId , $monitorParams) {
                                        //                     $flowIdArray = [];
                                        //                     foreach ($monitorParams as $key => $value) {
                                        //                         if (isset($value['user_id']) && $value['user_id'] == 'all') {
                                        //                             $flowIdArray[] = $value['flow_id'];
                                        //                         } else {
                                        //                             $query = $query->orWhere(function ($query) use ($value) {
                                        //                               if (isset($value['flow_id']) && !empty($value['flow_id'])) {
                                        //                                   if (isset($value['user_id']) && !empty($value['user_id'])) {
                                        //                                       if ($value['user_id'] != 'all') {
                                        //                                           $query = $query->where('flow_run_process.flow_id', $value['flow_id'])
                                        //                                               ->whereIn('flow_run.creator', $value['user_id']);
                                        //                                       }
                                        //                                   } else {
                                        //                                       $query = $query->where('flow_run_process.flow_id', $value['flow_id'])
                                        //                                           ->whereNull('flow_run.creator');
                                        //                                   }
                                        //                               }
                                        //                           });
                                        //                         }
                                        //                     }
                                        //                     if (!empty($flowIdArray)) {
                                        //                         $qeury = $query->orWhereIn('flow_run_process.flow_id', $flowIdArray);
                                        //                     }
                                        //                 });
                                        //             }
                                        // }
                                });
                    }
                }])->orders(['flow_noorder' => 'asc', 'flow_type.flow_id' => 'asc']);

            if (isset($param["flow_name"])) {
                $builder1->where('flow_name', 'like', '%' . $param["flow_name"] . '%');
            }
            if (isset($param["controlFlows"]) && !empty($param["controlFlows"])) {
                         $builder1->whereNotIn('flow_type.flow_id' , $param['controlFlows']);
             }
            $builder1->where("hide_running", "=", '0');

            $noSortFlow = collect([
                'id' => 0,
                'title' => trans('flow.unclassified'),
                'flow_sort_has_many_flow_type' => $builder1->get(),
                // 'flow_sort_has_many_flow_type_count' => $builder2->get()
            ]);
            return $query->get()->push($noSortFlow);

    }

    /**
     * 获取流程类别最大序号
     *
     */
    public function getMaxFlowSort()
    {
        $query = $this->entity;

        return $query->max('noorder');
    }

    /**
     * 获取单条流程分类数据
     *
     */
    public function getFlowSortDetail($sortId)
    {
        $query = $this->entity->select('id', 'title', 'noorder', 'priv_scope');

        $query = $query->where('id', $sortId)
            ->with(['flowSortHasManyMnamgeUser' => function ($query) {
                $query->orderBy('id');
            }])
            ->with('flowSortHasManyMnamgeRole')
            ->with('flowSortHasManyMnamgeDeptarment');

        // 兼容处理 flow_sort id为 0 的流程数据，拼接未分类的数据
        if ($sortId == 0) {
            $result = [
                'id' => 0,
                'title' => trans('flow.unclassified'),
                'noorder' => 0,
                'priv_scope' => 1, // 1 权限为所有人
                'flowSortHasManyMnamgeRole' => [],
                'flowSortHasManyMnamgeDeptarment' => [],
                'flowSortHasManyMnamgeUser' => []
            ];
            return new FlowSortEntity($result);
        }
        return $query->first();
    }

    /**
     * 继承扩展父类的 getDetail 方法，兼容处理分类未0的流程数据
     * @param int $id
     * @param bool $withTrashed
     * @param array $fields
     * @return array|object
     */
    public function getDetail($id, $withTrashed = false, array $fields = ['*'])
    {
        if ($id == 0) {
            $result = [
                'id' => 0,
                'title' => trans('flow.unclassified'),
                'noorder' => 0,
                'creator' => null,
                'created_at' => null,
                'updated_at' => null,
                'deleted_at' => null,
                'priv_scope' => 1,
            ];
            $result = new FlowSortEntity($result);
            return $result;
        }
        return parent::getDetail($id, $withTrashed, $fields);
    }

    /**
     * 获取有权限的表单类别列表
     */
    function getPermissionFlowSortList($param)
    {
        $userId = isset($param["user_id"]) ? $param["user_id"] : "";
        $roleId = isset($param["role_id"]) ? $param["role_id"] : "";
        $deptId = isset($param["dept_id"]) ? $param["dept_id"] : "";
        $query = $this->entity;

        $query = $query->where('priv_scope', 1)
            ->orWhereHas('flowSortHasManyMnamgeDeptarment', function ($query) use ($deptId) {
                $query->wheres(['dept_id' => [explode(",", trim($deptId, ",")), 'in']]);
            })
            ->orWhereHas('flowSortHasManyMnamgeRole', function ($query) use ($roleId) {
                $query->wheres(['role_id' => [$roleId, 'in']]);
            })
            ->orWhereHas('flowSortHasManyMnamgeUser', function ($query) use ($userId) {
                $query->wheres(['user_id' => [$userId]]);
            })
            ->orders(['noorder' => 'ASC', 'id' => 'ASC']);
        return $query->get();
    }

    /**
     * 报表-流程报表-获取流程分类进行中和已办的流程数量
     *
     *
     * @param  $datasourceGroupBy 分组依据; $where 数据过滤
     *
     *
     * @return array
     */
    public function getFlowCountGroupByCustomType($datasourceGroupBy, $where = "")
    {
        $search = array();
        $query = $this->entity;
        // 根据流程类型查询
        $query = $query->select(['id', 'title'])
            ->with(['flowSortHasManyFlowType' => function ($query) use ($where) {
                if (isset($where['flowID'])) {
                    $query->whereIn('flow_id', explode(',', $where['flowID']));
                }
                $query->select('flow_id', 'flow_name', 'flow_sort')
                    ->with(['flowTypeHasManyFlowRun' => function ($query) use ($where) {
                        $query->select('flow_id')
                            ->selectRaw('count(1) as run_count,current_step,is_effect')
                            ->groupby(['flow_id', 'current_step']);
                        if (isset($where['date_range'])) {
                            $dateRange = explode(',', $where['date_range']);
                            if (isset($dateRange[0]) && !empty($dateRange[0])) {
                                $query->whereRaw("created_at >= '" . $dateRange[0] . " 00:00:00'");
                            }
                            if (isset($dateRange[1]) && !empty($dateRange[1])) {
                                $query->whereRaw("created_at <= '" . $dateRange[1] . " 23:59:59'");
                            }
                        }
                        //用户搜索条件不为空
                        if (isset($where['userIds']) && is_array($where['userIds'])) {
                            $query->whereIn('creator', $where['userIds']);
                        }
                    }])->orders(['flow_noorder' => 'asc', 'flow_type.flow_id' => 'asc']);
            }]);
        if (isset($where['flowsortId']) && !empty($where['flowsortId'])) {
            $query = $query->whereIn('id', $where['flowsortId']);
        }

        // 获取未分类
        if ((!isset($where['flowsortId']) || empty($where['flowsortId'])) ||   (isset($where['flowsortId']) && !empty($where['flowsortId']) && in_array('0', $where['flowsortId']))) {
                $noSortFlow = $this->getNoSortByCustomTypeData($where);
                $result = $query->get()->push($noSortFlow);
        } else {
            $result = $query->get();
        }
        if (!empty($result)) {
            return $result->toArray();
        } else {
            return array();
        }
    }

    /**
     * 报表-流程报表-获取流程分类流程超期的数量
     *
     *
     * @param  $datasourceGroupBy 分组依据; $where 数据过滤
     *
     *
     * @return array
     */
    public function getFlowLimitCountGroupByCustomType($datasourceGroupBy, $where = "")
    {
        $search = array();
        $query = $this->entity;
        // 根据流程类型查询
        $query = $query->select(['id', 'title'])
            ->with(['flowSortHasManyFlowType' => function ($query) use ($where) {
                if (isset($where['flowID'])) {
                    $query = $query->whereIn('flow_id', explode(',', $where['flowID']));
                }
                $query->select('flow_id', 'flow_name', 'flow_sort')
                    ->with(['flowTypeHasManyFlowRun' => function ($query) use ($where) {
                        $query->select(['run_id', 'run_name', 'flow_id']);

                        if (isset($where['date_range'])) {
                            $dateRange = explode(',', $where['date_range']);
                            if (isset($dateRange[0]) && !empty($dateRange[0])) {
                                $query->whereRaw("created_at >= '" . $dateRange[0] . " 00:00:00'");
                            }
                            if (isset($dateRange[1]) && !empty($dateRange[1])) {
                                $query->whereRaw("created_at <= '" . $dateRange[1] . " 23:59:59'");
                            }
                        }
                        $query->with(['flowRunHasManyFlowRunProcess' => function ($query) use ($where) {
                            //用户搜索条件不为空
                            if (isset($where['userIds']) && is_array($where['userIds'])) {
                                $query->whereIn('user_id', $where['userIds']);
                            }
                            $query->select(['run_id', 'host_flag', 'saveform_time', 'limit_date', 'deliver_time', 'is_effect'])
                                ->whereRaw('limit_date is not null and limit_date<>"0000-00-00 00:00:00"');
                        }]);

                    }])->orders(['flow_noorder' => 'asc', 'flow_type.flow_id' => 'asc']);
            }]);
        if (isset($where['flowsortId']) && !empty($where['flowsortId'])) {
            $query = $query->whereIn('id', $where['flowsortId']);
        }
        // 获取未分类
         // 获取未分类
        if ((!isset($where['flowsortId']) || empty($where['flowsortId'])) ||   (isset($where['flowsortId']) && !empty($where['flowsortId']) && in_array('0', $where['flowsortId']))) {
                $noSortFlow = $this->getNoSortData($where);
                $result = $query->get()->push($noSortFlow);
        } else {
            $result = $query->get();
        }
        if (!empty($result)) {
            return $result->toArray();
        } else {
            return array();
        }
    }
    public function getNoSortData($where) {
        // 获取未分类
        $builder = FlowTypeEntity::select('*')->where('flow_sort', 0);
        if (isset($where['flowID'])) {
                $builder = $builder->whereIn('flow_id', explode(',', $where['flowID']));
        }
        if (isset($where['flowsortId']) && !empty($where['flowsortId'])) {
                $builder = $builder->whereIn('flow_sort', $where['flowsortId']);
        }
        $builder =  $builder->select('flow_id', 'flow_name', 'flow_sort')
                    ->with(['flowTypeHasManyFlowRun' => function ($query) use ($where) {
                        $query->select(['run_id', 'run_name', 'flow_id']);

                        if (isset($where['date_range'])) {
                            $dateRange = explode(',', $where['date_range']);
                            if (isset($dateRange[0]) && !empty($dateRange[0])) {
                                $query->whereRaw("created_at >= '" . $dateRange[0] . " 00:00:00'");
                            }
                            if (isset($dateRange[1]) && !empty($dateRange[1])) {
                                $query->whereRaw("created_at <= '" . $dateRange[1] . " 23:59:59'");
                            }
                        }
                        $query->with(['flowRunHasManyFlowRunProcess' => function ($query) use ($where) {
                            //用户搜索条件不为空
                            if (isset($where['userIds']) && is_array($where['userIds'])) {
                                $query->whereIn('user_id', $where['userIds']);
                            }
                            $query->select(['run_id', 'host_flag', 'saveform_time', 'limit_date', 'deliver_time', 'is_effect'])
                                ->whereRaw('limit_date is not null and limit_date<>"0000-00-00 00:00:00"');
                        }]);

                    }])->orders(['flow_noorder' => 'asc', 'flow_type.flow_id' => 'asc']);
        return collect([
            'id' => 0,
            'title' => trans('flow.unclassified'),
            'flow_sort_has_many_flow_type' => $builder->get(),
        ]);

    }
    public function getNoSortByCustomTypeData($where) {
        // 获取未分类
        $builder = FlowTypeEntity::select('*')->where('flow_sort', 0);
        if (isset($where['flowID'])) {
                $builder = $builder->whereIn('flow_id', explode(',', $where['flowID']));
        }
        if (isset($where['flowsortId']) && !empty($where['flowsortId'])) {
                $builder = $builder->whereIn('flow_sort', $where['flowsortId']);
        }
        $builder->select('flow_id', 'flow_name', 'flow_sort')
                    ->with(['flowTypeHasManyFlowRun' => function ($query) use ($where) {
                        $query->select('flow_id')
                            ->selectRaw('count(1) as run_count,current_step,is_effect')
                            ->groupby(['flow_id', 'current_step']);
                        if (isset($where['date_range'])) {
                            $dateRange = explode(',', $where['date_range']);
                            if (isset($dateRange[0]) && !empty($dateRange[0])) {
                                $query->whereRaw("created_at >= '" . $dateRange[0] . " 00:00:00'");
                            }
                            if (isset($dateRange[1]) && !empty($dateRange[1])) {
                                $query->whereRaw("created_at <= '" . $dateRange[1] . " 23:59:59'");
                            }
                        }
                        //用户搜索条件不为空
                        if (isset($where['userIds']) && is_array($where['userIds'])) {
                            $query->whereIn('creator', $where['userIds']);
                        }
                    }])->orders(['flow_noorder' => 'asc', 'flow_type.flow_id' => 'asc']);
        return collect([
            'id' => 0,
            'title' => trans('flow.unclassified'),
            'flow_sort_has_many_flow_type' => $builder->get(),
        ]);

    }
}
