<?php

namespace App\EofficeApp\Project\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Project\Entities\ProjectManagerEntity;
use DB;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
/**
 * 项目管理 资源库
 *
 * @author:喻威
 *
 * @since：2015-10-19
 *
 */
class ProjectManagerRepository extends BaseRepository {

    private $user_id;
    private $team_project;

    public function __construct(ProjectManagerEntity $entity) {
        parent::__construct($entity);
    }


    /**
     * 一个基础的函数，用来根据各种条件获取 project_manager 表的数据
     * @method getFlowRunProcessList
     *
     * @param  array                 $param [description]
     *
     * @return [type]                       [description]
     */
    function getProjectManagerList($param = [])
    {
        $default = [
            'fields'    => ['*'],
            'page'      => 0,
            'limit'     => config('eoffice.pagesize'),
            'search'    => [],
            'order_by'  => ['manager_id'=>'asc'],
            'returntype' => 'array',
        ];
        $param = array_merge($default, array_filter($param));
        $query = $this->entity
                        ->select($param['fields'])
                        ->wheres($param['search'])
                        ->orders($param['order_by']);
        if(isset($param["distinct_flag"]) && $param["distinct_flag"] == "1") {
            $query->distinct();
        }
        // 分组参数
        if(isset($param['groupBy'])) {
            $query = $query->groupBy($param['groupBy']);
        }
        // 解析原生 where
        if(isset($param['whereRaw'])) {
            foreach ($param['whereRaw'] as $key => $whereRaw) {
                $query = $query->whereRaw($whereRaw);
            }
        }
        // 解析原生 select
        if(isset($param['selectRaw'])) {
            foreach ($param['selectRaw'] as $key => $selectRaw) {
                $query = $query->selectRaw($selectRaw);
            }
        }
        $query = $query->parsePage($param['page'], $param['limit']);
        // 返回值类型判断
        if($param["returntype"] == "array") {
            return $query->get()->toArray();
        } else if($param["returntype"] == "count") {
            return $query->count();
        } else if($param["returntype"] == "object") {
            return $query->get();
        }
    }

    /**
     * 项目管理列表（无分页）
     * @param $param
     * @param array $order_by_field
     * @return array
     */
    public function getProjectAll($param, $order_by_field = [])
    {
        $query = $this->getProjectAllQuery($param, $order_by_field);

        return $query->get()->toArray();
    }

    /**
     * 项目管理列表（分页）,兼容过去无分页的查询条件
     * @param $param
     * @param array $order_by_field
     * @return array
     */
    public function getProjectLists($param, $order_by_field = [])
    {
        $query = $this->getProjectAllQuery($param, $order_by_field);//兼容getProjectAll的查询条件
        $page = Arr::get($param, 'page');
        $res = paginate($query, 'array', $page);

        return $res;
    }

    /**
     * 获取项目管理列表查询对象
     * @param $param
     * @param array $order_by_field
     * @return mixed
     */
    private function getProjectAllQuery($param, $order_by_field = []) :Builder
    {


        $default = [
            'search' => [],
            'order_by' => ['manager_id' => 'desc'],
        ];
        //isset read
        $param = array_merge($default, array_filter($param));

        $this->user_id = $param['user_id'];
        if (isset($param['user_team_project'])) {
            $this->team_project = $param['user_team_project'];
        } else {
            $this->team_project = "";
        }


        $query = $this->entity->newQuery()
                ->multiWheres($param['search']);

        // 将特定的id排在前面
        $prefixManagerId = Arr::get($param, 'prefixId');
        if ($prefixManagerId) {
            $query->orderByRaw("manager_id={$prefixManagerId} desc");
        }
        if (isset($param['project_type']) && $param['project_type']) {
            switch ($param['project_type']) {
                case "join":
                    //默认当前列表 能看到的所有列表即参与项目
                    break;
                case "manager":
                    //manager_person
                    $query = $query->whereRaw('(find_in_set(\'' . $this->user_id . '\',manager_person))');
                    break;
                case "monitor":
                    //manager_monitor
                    $query = $query->whereRaw('(find_in_set(\'' . $this->user_id . '\',manager_monitor))');
                    break;
                case "approval":
                    //manager_examine
                    $query = $query->whereRaw('(find_in_set(\'' . $this->user_id . '\',manager_examine))');
                    break;
                case "create":
                    //manager_creater
                    $query = $query->where("manager_creater", $this->user_id);
                    break;
                default:
                    break;
            }
        }

        $query = $query->where(function ($query) {
                    $query->where(function ($query) {
                        $query->whereRaw('(find_in_set(\'' . $this->user_id . '\',manager_person))')
                                ->whereIn('manager_state', [1, 2, 3]);
                    })->orWhere(function ($query) {
                        $query->where('manager_creater', $this->user_id)
                                ->whereIn('manager_state', [1, 3]);
                    })->orWhere(function ($query) {
                        $query->whereRaw('(find_in_set(\'' . $this->user_id . '\',manager_examine))')
                                ->whereNotIn('manager_state', [1]);
                    })->orWhere(function ($query) {

                        $query->whereIn('manager_state', [4, 5])
                                ->where(function ($query) {
                                    $query->whereRaw('(find_in_set(\'' . $this->user_id . '\',manager_monitor))')
                                    ->orWhere(function ($query) {
                                        $query->whereRaw('(find_in_set(\'' . $this->user_id . '\',manager_person))');
                                    })
                                    ->orWhere(function ($query) {
                                        $query->whereRaw('(find_in_set(\'' . $this->user_id . '\',manager_examine))');
                                    })
                                    ->orWhere(function ($query) {

                                        $team_project = explode(",", $this->team_project);
                                        $query->whereIn('manager_id', $team_project);
                                    });
                                });
                    });
                });
        if (!empty($order_by_field)) {
            $query = $query->orderBy(DB::raw('FIELD(manager_type, ' . implode(',', $order_by_field) . ')'), 'ASC');
        }

        $query->orders($param['order_by']);

        return $query;
    }

    /**
     * 获取项目信息（占用的角色，占用类型ID 等等 ）
     *
     * @param type $id
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function infoProjectManagerbyWhere($where) {
        $result = $this->entity->select(['project_manager.*', 'project_team.team_person'])->leftJoin('project_team', 'project_team.team_project', '=', 'project_manager.manager_id')->wheres($where)->get()->toArray();
        return $result;
    }

    public function getProjectSystemData($param) {

        $default = [
            'fields' => ['*'],
            'page' => 0,
            'limit' => config('eoffice.pagesize'),
            'search' => [],
            'order_by' => [ 'manager_id' => 'desc'],
        ];


        //isset read
        $param = array_merge($default, array_filter($param));

        $order_by_field = isset($param["order_by_field"]) ? $param["order_by_field"] : [];

        $this->user_id = $param['user_id'];
        if (isset($param['user_team_project'])) {
            $this->team_project = $param['user_team_project'];
        } else {
            $this->team_project = "";
        }


        $query = $this->entity
                ->wheres($param['search']);

        if (isset($param['project_type']) && $param['project_type']) {
            switch ($param['project_type']) {
                case "join":
                    //默认当前列表 能看到的所有列表即参与项目
                    break;
                case "manager":
                    //manager_person
                    $query = $query->whereRaw('(find_in_set(\'' . $this->user_id . '\',manager_person))');
                    break;
                case "monitor":
                    //manager_monitor
                    $query = $query->whereRaw('(find_in_set(\'' . $this->user_id . '\',manager_monitor))');
                    break;
                case "approval":
                    //manager_examine
                    $query = $query->whereRaw('(find_in_set(\'' . $this->user_id . '\',manager_examine))');
                    break;
                case "create":
                    //manager_creater
                    $query = $query->where("manager_creater", $this->user_id);
                    break;
                default:
                    break;
            }
        }

        $query = $query->where(function ($query) {
                    $query->where(function ($query) {
                        $query->whereRaw('(find_in_set(\'' . $this->user_id . '\',manager_person))')
                                ->whereIn('manager_state', [1, 3]);
                    })->orWhere(function ($query) {
                        $query->where('manager_creater', $this->user_id)
                                ->whereIn('manager_state', [1, 3]);
                    })->orWhere(function ($query) {
                        $query->whereRaw('(find_in_set(\'' . $this->user_id . '\',manager_examine))')
                                ->whereNotIn('manager_state', [1]);
                    })->orWhere(function ($query) {

                        $query->whereIn('manager_state', [4, 5])
                                ->where(function ($query) {
                                    $query->whereRaw('(find_in_set(\'' . $this->user_id . '\',manager_monitor))')
                                    ->orWhere(function ($query) {
                                        $query->whereRaw('(find_in_set(\'' . $this->user_id . '\',manager_person))');
                                    })
                                    ->orWhere(function ($query) {
                                        $query->whereRaw('(find_in_set(\'' . $this->user_id . '\',manager_examine))');
                                    })
                                    ->orWhere(function ($query) {

                                        $team_project = explode(",", $this->team_project);
                                        $query->whereIn('manager_id', $team_project);
                                    });
                                });
                    });
                });
        if (!empty($order_by_field)) {
            $query = $query->orderBy(DB::raw('FIELD(manager_type, ' . implode(',', $order_by_field) . ')'), 'ASC');
        }


        return $query->orders($param['order_by'])->parsePage($param['page'], $param['limit'])->get()->toArray();
    }

    public function getProjectSystemDataTotal($param) {



        $default = [
            'search' => []
        ];
        //isset read
        $param = array_merge($default, array_filter($param));

        $this->user_id = $param['user_id'];
        if (isset($param['user_team_project'])) {
            $this->team_project = $param['user_team_project'];
        } else {
            $this->team_project = "";
        }


        $query = $this->entity
                ->wheres($param['search']);

        if (isset($param['project_type']) && $param['project_type']) {
            switch ($param['project_type']) {
                case "join":
                    //默认当前列表 能看到的所有列表即参与项目
                    break;
                case "manager":
                    //manager_person
                    $query = $query->whereRaw('(find_in_set(\'' . $this->user_id . '\',manager_person))');
                    break;
                case "monitor":
                    //manager_monitor
                    $query = $query->whereRaw('(find_in_set(\'' . $this->user_id . '\',manager_monitor))');
                    break;
                case "approval":
                    //manager_examine
                    $query = $query->whereRaw('(find_in_set(\'' . $this->user_id . '\',manager_examine))');
                    break;
                case "create":
                    //manager_creater
                    $query = $query->where("manager_creater", $this->user_id);
                    break;
                default:
                    break;
            }
        }


        return $query->where(function ($query) {
                    $query->where(function ($query) {
                        $query->whereRaw('(find_in_set(\'' . $this->user_id . '\',manager_person))')
                                ->whereIn('manager_state', [1, 3]);
                    })->orWhere(function ($query) {
                        $query->where('manager_creater', $this->user_id)
                                ->whereIn('manager_state', [1, 3]);
                    })->orWhere(function ($query) {
                        $query->whereRaw('(find_in_set(\'' . $this->user_id . '\',manager_examine))')
                                ->whereNotIn('manager_state', [1]);
                    })->orWhere(function ($query) {

                        $query->whereIn('manager_state', [4, 5])
                                ->where(function ($query) {
                                    $query->whereRaw('(find_in_set(\'' . $this->user_id . '\',manager_monitor))')
                                    ->orWhere(function ($query) {
                                        $query->whereRaw('(find_in_set(\'' . $this->user_id . '\',manager_person))');
                                    })
                                    ->orWhere(function ($query) {
                                        $query->whereRaw('(find_in_set(\'' . $this->user_id . '\',manager_examine))');
                                    })
                                    ->orWhere(function ($query) {

                                        $team_project = explode(",", $this->team_project);
                                        $query->whereIn('manager_id', $team_project);
                                    });
                                });
                    });
                })->count();
    }

    //手机版列表
    public function getProjectAllTotal($param) {



        $default = [
            'search' => [],
        ];
        //isset read
        $param = array_merge($default, array_filter($param));



        $this->user_id = $param['user_id'];
        if (isset($param['user_team_project'])) {
            $this->team_project = $param['user_team_project'];
        } else {
            $this->team_project = "";
        }



        $query = $this->entity
                ->multiWheres($param['search']);

        if (isset($param['project_type']) && $param['project_type']) {
            switch ($param['project_type']) {
                case "join":
                    //默认当前列表 能看到的所有列表即参与项目
                    break;
                case "manager":
                    //manager_person
                    $query = $query->whereRaw('(find_in_set(\'' . $this->user_id . '\',manager_person))');
                    break;
                case "monitor":
                    //manager_monitor
                    $query = $query->whereRaw('(find_in_set(\'' . $this->user_id . '\',manager_monitor))');
                    break;
                case "approval":
                    //manager_examine
                    $query = $query->whereRaw('(find_in_set(\'' . $this->user_id . '\',manager_examine))');
                    break;
                case "create":
                    //manager_creater
                    $query = $query->where("manager_creater", $this->user_id);
                    break;
                default:
                    break;
            }
        }




        return $query->where(function ($query) {
                    $query->where(function ($query) {
                        $query->whereRaw('(find_in_set(\'' . $this->user_id . '\',manager_person))')
                                ->whereIn('manager_state', [1, 2, 3]);
                    })->orWhere(function ($query) {
                        $query->where('manager_creater', $this->user_id)
                                ->whereIn('manager_state', [1, 3]);
                    })->orWhere(function ($query) {
                        $query->whereRaw('(find_in_set(\'' . $this->user_id . '\',manager_examine))')
                                ->whereNotIn('manager_state', [1]);
                    })->orWhere(function ($query) {

                        $query->whereIn('manager_state', [4, 5])
                                ->where(function ($query) {
                                    $query->whereRaw('(find_in_set(\'' . $this->user_id . '\',manager_monitor))')
                                    ->orWhere(function ($query) {
                                        $query->whereRaw('(find_in_set(\'' . $this->user_id . '\',manager_person))');
                                    })
                                    ->orWhere(function ($query) {
                                        $query->whereRaw('(find_in_set(\'' . $this->user_id . '\',manager_examine))');
                                    })
                                    ->orWhere(function ($query) {

                                        $team_project = explode(",", $this->team_project);
                                        $query->whereIn('manager_id', $team_project);
                                    });
                                });
                    });
                })->count();
    }

    public function getProjectAllList($param) {
        $default = [
            'fields' => ['*'],
            'page' => 0,
            'limit' => config('eoffice.pagesize'),
            'search' => [],
            'order_by' => ['manager_id' => 'desc'],
        ];
        //isset read
        $param = array_merge($default, array_filter($param));

        $this->user_id = $param['user_id'];
        if (isset($param['user_team_project'])) {
            $this->team_project = $param['user_team_project'];
        } else {
            $this->team_project = "";
        }

        $order_by_field = isset($param["order_by_field"]) ? $param["order_by_field"] : [];

        $query = $this->entity
                ->multiWheres($param['search']);

        if (isset($param['project_type']) && $param['project_type']) {
            switch ($param['project_type']) {
                case "join":
                    //默认当前列表 能看到的所有列表即参与项目
                    break;
                case "manager":
                    //manager_person
                    $query = $query->whereRaw('(find_in_set(\'' . $this->user_id . '\',manager_person))');
                    break;
                case "monitor":
                    //manager_monitor
                    $query = $query->whereRaw('(find_in_set(\'' . $this->user_id . '\',manager_monitor))');
                    break;
                case "approval":
                    //manager_examine
                    $query = $query->whereRaw('(find_in_set(\'' . $this->user_id . '\',manager_examine))');
                    break;
                case "create":
                    //manager_creater
                    $query = $query->where("manager_creater", $this->user_id);
                    break;
                default:
                    break;
            }
        }
        $query = $query->where(function ($query) {
                    $query->where(function ($query) {
                        $query->whereRaw('(find_in_set(\'' . $this->user_id . '\',manager_person))')
                                ->whereIn('manager_state', [1, 2, 3]);
                    })->orWhere(function ($query) {
                        $query->where('manager_creater', $this->user_id)
                                ->whereIn('manager_state', [1, 3]);
                    })->orWhere(function ($query) {
                        $query->whereRaw('(find_in_set(\'' . $this->user_id . '\',manager_examine))')
                                ->whereNotIn('manager_state', [1]);
                    })->orWhere(function ($query) {

                        $query->whereIn('manager_state', [4, 5])
                                ->where(function ($query) {
                                    $query->whereRaw('(find_in_set(\'' . $this->user_id . '\',manager_monitor))')
                                    ->orWhere(function ($query) {
                                        $query->whereRaw('(find_in_set(\'' . $this->user_id . '\',manager_person))');
                                    })
                                    ->orWhere(function ($query) {
                                        $query->whereRaw('(find_in_set(\'' . $this->user_id . '\',manager_examine))');
                                    })
                                    ->orWhere(function ($query) {

                                        $team_project = explode(",", $this->team_project);
                                        $query->whereIn('manager_id', $team_project);
                                    });
                                });
                    });
                })->parsePage($param['page'], $param['limit']);
        if (!empty($order_by_field)) {
            $query = $query->orderBy(DB::raw('FIELD(manager_type, ' . implode(',', $order_by_field) . ')'), 'ASC');
        }
        return $query->orders($param['order_by'])->get()->toArray();
    }

    public function getProjectParticipants($manager_id){

    }
    // 根据manager_type获取project_manager表中的数据
    public function getProjectsByFieldValue($field_value) {
        return $this->entity->Where('manager_type',$field_value)->get()->toArray();
    }

    public function projectChargeStatistics($param) {
        $default = [
            'fields'   => ['charge_setting.*', 'manager_name as name', 'project_manager.manager_id as id'],
            'page'     => 0,
            'limit'    => config('eoffice.pagesize'),
            'search'   => [],
            'order_by' => ['charge_setting_id' => 'desc', 'project_manager.manager_id' => 'asc']
        ];
        $param = array_merge($default, array_filter($param));
        $query = $this->entity->select($param['fields'])
            ->leftJoin('charge_setting', function($join) {
                $join->on("project_manager.manager_id", '=', 'charge_setting.project_id')->whereNull('charge_setting.deleted_at');
            });
        $query = $this->handleProjectQueryUseTempTable($query, $param['projects'] ?? [], 'manager_id');
        return $query->wheres($param['search'])
                    ->orders($param['order_by'])
                    ->parsePage($param['page'], $param['limit'])
                    ->get();
    }

    public function proejctChargeStatisticsTotal($data) {
        $default = [
            'search' => []
        ];
        $param = array_merge($default, array_filter($data));
        $query = $this->entity->leftJoin('charge_setting', function($join) {
            $join->on("project_manager.manager_id", '=', 'charge_setting.project_id')->whereNull('charge_setting.deleted_at');
        });
        $query = $this->handleProjectQueryUseTempTable($query, $param['projects'] ?? [], 'manager_id');
        return $query->wheres($param['search'])
                    ->count();
    }

    private function handleProjectQueryUseTempTable($query, $data, $field) {
        if (empty($data)) {
            return $query;
        }

        if(count($data) > 1000){
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
            
            $query->join("$tableName", $tableName . ".data_id", '=', $field);
        } else {
            $query = $query->whereIn($field, $data);
        }

        return $query;
    }
}
