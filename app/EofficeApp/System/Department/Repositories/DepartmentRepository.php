<?php

namespace App\EofficeApp\System\Department\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\System\Department\Entities\DepartmentEntity;
use DB;

/**
 * @部门资源库类
 *
 * @author 李志军
 */
class DepartmentRepository extends BaseRepository {

    private $defaultOrder = 'dept_sort'; //默认排序
    private $primaryKey = 'dept_id'; //主键
    /** @var int 默认列表条数 */
    private $limit;

    /** @var int 默认列表页 */
    private $page = 0;

    /** @var array  默认排序 */
    private $orderBy = ['dept_sort' => 'asc'];

    /**
     *
     * @param \App\EofficeApp\Entities\DepartmentEntity $departmentEntity
     */
    public function __construct(DepartmentEntity $departmentEntity) {
        parent::__construct($departmentEntity);

        $this->limit = config('eoffice.pagesize');
    }

    /**
     * @获取子部门
     * @param type $deptId
     * @return array 子部门列表
     */
    public function getChildren($deptId, $param = '', $fields = ['*'], $own = []) {
        $permission = $param['permission'] ?? false;
        $systemPermission = $param['system_permission'] ?? 0;
        $query = $this->entity;
        if (isset($param['user_total']) && $param['user_total']) {
            $query = $query->select(DB::raw("count(user.user_id) as user_total, department.*"))
                           ->leftJoin('user_system_info', 'user_system_info.dept_id', '=', 'department.dept_id')
                           ->leftJoin('user', 'user_system_info.user_id', '=', 'user.user_id');
            if (!$deptId && $permission && $systemPermission) {
                $query = $query->where(function($query) use($own) {
                    $query->whereExists(function ($query) use($own) {
                                $query->select(['department_user.dept_id'])
                                        ->from('department_user')
                                        ->where('department_user.user_id', $own["user_id"])
                                        ->whereRaw('department_user.dept_id=department.dept_id');
                            })->orWhereExists(function ($query) use($own) {
                                $query->select(['department_role.dept_id'])
                                        ->from('department_role')
                                        ->whereIn('department_role.role_id', $own["role_id"])
                                        ->whereRaw('department_role.dept_id=department.dept_id');
                            })->orWhereExists(function ($query) use($own) {
                                $query->select(['department_dept.dept_id'])
                                        ->from('department_dept')
                                        ->where('department_dept.re_dept_id', $own["dept_id"])
                                        ->whereRaw('department_dept.dept_id=department.dept_id');
                            });
                });
            }
            if (isset($param['search']['dept_id'])) {
                $tempSearch = [
                    'dept_id' => $param['search']['dept_id']
                ];
                $query = $query->wheres($tempSearch);
            }
            return $query->byParent($deptId)
                         ->groupBy('department.dept_id')
                         ->orderBy($this->defaultOrder)->get();
        } else {
            $query = $query->select($fields);
            if (!$deptId && $permission && $systemPermission) {
                $query = $query->where(function($query) use($own) {
                    $query->whereExists(function ($query) use($own) {
                                $query->select(['department_user.dept_id'])
                                        ->from('department_user')
                                        ->where('department_user.user_id', $own["user_id"])
                                        ->whereRaw('department_user.dept_id=department.dept_id');
                            })->orWhereExists(function ($query) use($own) {
                                $query->select(['department_role.dept_id'])
                                        ->from('department_role')
                                        ->whereIn('department_role.role_id', $own["role_id"])
                                        ->whereRaw('department_role.dept_id=department.dept_id');
                            })->orWhereExists(function ($query) use($own) {
                                $query->select(['department_dept.dept_id'])
                                        ->from('department_dept')
                                        ->where('department_dept.re_dept_id', $own["dept_id"])
                                        ->whereRaw('department_dept.dept_id=department.dept_id');
                            });
                });
            }
            if (isset($param['search']['dept_id'])) {
                $tempSearch = [
                    'dept_id' => $param['search']['dept_id']
                ];
                $query = $query->wheres($tempSearch);
            }
            return $query->byParent($deptId)->orderBy($this->defaultOrder)->get();
        }
    }

    /**
     * @获取子部门数量
     * @param type $deptId
     * @return int 部门数量
     */
    public function getChildrenCount($deptId) {
        return $this->entity->byParent($deptId)->count();
    }
    public function getPermissionDeptIds($user)
    {
        $userId = $user["user_id"] ?? '';
        $roleId = $user["role_id"] ?? [];
        $deptId = $user["dept_id"] ?? '';
        $query = $this->entity->select(['department.dept_id']);
            $query = $query->where(function($query) use($userId, $roleId, $deptId) {
            
                $query->whereExists(function ($query) use($userId) {
                            $query->select(['department_user.dept_id'])
                                    ->from('department_user')
                                    ->where('department_user.user_id', $userId)
                                    ->whereRaw('department_user.dept_id=department.dept_id');
                        })->orWhereExists(function ($query) use($roleId) {
                            $query->select(['department_role.dept_id'])
                                    ->from('department_role')
                                    ->whereIn('department_role.role_id', $roleId)
                                    ->whereRaw('department_role.dept_id=department.dept_id');
                        })->orWhereExists(function ($query) use($deptId) {
                            $query->select(['department_dept.dept_id'])
                                    ->from('department_dept')
                                    ->where('department_dept.re_dept_id', $deptId)
                                    ->whereRaw('department_dept.dept_id=department.dept_id');
                        });
            });
        return $query->get()->toArray();
    }

    /**
     * @获取所有部门
     * @return array 部门列表
     */
    public function getAllDepartment() {
        return $this->entity->orderBy($this->defaultOrder)->get();
    }

      public function getDepartmentBySort() {
        return $this->entity->orderBy($this->defaultOrder,"desc")->get();
    }

    /**
     * @获取其他部门列表
     * @param type $deptIds
     * @return array 部门列表
     */
    public function getOtherDepartment($deptIds) {
        return $this->entity->whereNotIn($this->primaryKey, $deptIds)->orderBy($this->defaultOrder)->get();
    }

    /**
     * @根据部门id获取部门列表
     * @param type $deptIds
     * @return array 部门列表
     */
    public function getDepartmentByIds($deptIds) {
        return $this->entity->whereIn($this->primaryKey, $deptIds)->orderBy($this->defaultOrder)->get();
    }

    /**
     * @新建部门
     * @param type $data
     * @return int 部门id
     */
    public function addDepartment($data) {
        return $this->entity->create($data);
    }

    public function listDept($param) {
        $param['fields'] = isset($param['fields']) ? $param['fields'] : ['*'];
        $param['limit'] = isset($param['limit']) ? $param['limit'] : $this->limit;
        $param['page'] = isset($param['page']) ? $param['page'] : $this->page;
        $param['order_by'] = isset($param['order_by']) ? $param['order_by'] : $this->orderBy;
        $permission = $param['permission'] ?? 1;
        $systemPermission = $param['system_permission'] ?? 0;
        if (isset($param['search']['user_id'])) {
            unset($param['search']['user_id']);
        }
        if (isset($param['search']['user_accounts'])) {
            unset($param['search']['user_accounts']);
        }
        $query = $this->entity->select($param['fields']);
        if ($permission && $systemPermission) {
            $query = $query->where(function($query) use($param) {
                $query->whereExists(function ($query) use($param) {
                            $query->select(['department_user.dept_id'])
                                    ->from('department_user')
                                    ->where('department_user.user_id', $param["user_id"])
                                    ->whereRaw('department_user.dept_id=department.dept_id');
                        })->orWhereExists(function ($query) use($param) {
                            $query->select(['department_role.dept_id'])
                                    ->from('department_role')
                                    ->whereIn('department_role.role_id', $param["role_id"])
                                    ->whereRaw('department_role.dept_id=department.dept_id');
                        })->orWhereExists(function ($query) use($param) {
                            $query->select(['department_dept.dept_id'])
                                    ->from('department_dept')
                                    ->where('department_dept.re_dept_id', $param["dept_id"])
                                    ->whereRaw('department_dept.dept_id=department.dept_id');
                        });
            });
        }
        if (isset($param['search']) && !empty($param['search'])) {
            if (isset($param['search']['tree']) && isset($param['permission']) && $param['permission']) {
                if ($param['search']['tree'][0] == 1 && isset($param['search']['dept_id'])) {
                    $query->whereRaw('find_in_set(? ,arr_parent_id)', [$param['search']['dept_id'][0]]);
                } else if ($param['search']['tree'][0] == 0 && isset($param['search']['dept_id'])) {
                    $query->where('parent_id', $param['search']['dept_id'][0]);
                } 
                unset($param['search']['dept_id']);
            } else if (isset($param['search']['tree']) && $param['search']['tree'][0] == 1 && isset($param['org_setting'])) {
                $query->whereRaw('find_in_set(? ,arr_parent_id)', [$param['search']['dept_id'][0]]);
                unset($param['search']['dept_id']);
            }
            unset($param['search']['tree']);
            $query->multiWheres($param['search']);
        }
        
        $departmentList = $query->orderBy('arr_parent_id', 'asc')
                        ->orderBy('dept_sort', 'asc')
                        ->parsePage($param['page'], $param['limit'])
                        ->get()->toArray();
        // 统计部门人数
        if (isset($param['user_total']) && $param['user_total']) {
            $userTotalList = $this->entity->select(DB::raw("count(user_system_info.user_id) as user_total, department.*"))
                            ->leftJoin('user_system_info', 'user_system_info.dept_id', '=', 'department.dept_id')
                            ->groupBy('department.dept_id')->get()->toArray();
            foreach ($departmentList as $key => $value) {
                foreach ($userTotalList as $uKey => $uValue) {
                    if ($departmentList[$key]['dept_id'] == $userTotalList[$uKey]['dept_id']) {
                        $departmentList[$key]['user_total'] = $userTotalList[$uKey]['user_total'];
                    }
                }
            }
        }

        return $departmentList;
    }

    public function getDeptCount($param) {
        /*$query = $this->entity->select(['dept_id']);

        if (isset($param['search']) && !empty($param['search'])) {
            if (isset($param['search']['tree'])) {
                if ($param['search']['tree'][0] == 1 && isset($param['search']['dept_id'])) {
                    $query->whereRaw('find_in_set(\'' . $param['search']['dept_id'][0] . '\',arr_parent_id)');
                } else if ($param['search']['tree'][0] == 0 && isset($param['search']['dept_id'])) {
                    $query->where('parent_id', $param['search']['dept_id'][0]);
                }
                unset($param['search']['tree']);
                unset($param['search']['dept_id']);
            }
            $query->wheres($param['search']);
        }
        return $query->count();*/
        $permission = $param['permission'] ?? false;
        $systemPermission = $param['system_permission'] ?? 0;
        $param['fields'] = isset($param['fields']) ? $param['fields'] : ['*'];
        $param['limit'] = isset($param['limit']) ? $param['limit'] : $this->limit;
        $param['page'] = isset($param['page']) ? $param['page'] : $this->page;
        $param['order_by'] = isset($param['order_by']) ? $param['order_by'] : $this->orderBy;
        if (isset($param['search']['user_id'])) {
            unset($param['search']['user_id']);
        }
        if (isset($param['search']['user_accounts'])) {
            unset($param['search']['user_accounts']);
        }
        $query = $this->entity->select($param['fields']);
        if ($permission && $systemPermission) {
            $query = $query->where(function($query) use($param) {
                $query->whereExists(function ($query) use($param) {
                            $query->select(['department_user.dept_id'])
                                    ->from('department_user')
                                    ->where('department_user.user_id', $param["user_id"])
                                    ->whereRaw('department_user.dept_id=department.dept_id');
                        })->orWhereExists(function ($query) use($param) {
                            $query->select(['department_role.dept_id'])
                                    ->from('department_role')
                                    ->whereIn('department_role.role_id', $param["role_id"])
                                    ->whereRaw('department_role.dept_id=department.dept_id');
                        })->orWhereExists(function ($query) use($param) {
                            $query->select(['department_dept.dept_id'])
                                    ->from('department_dept')
                                    ->where('department_dept.re_dept_id', $param["dept_id"])
                                    ->whereRaw('department_dept.dept_id=department.dept_id');
                        });
            });
        }
        if (isset($param['search']) && !empty($param['search'])) {
            if (isset($param['search']['tree']) && isset($param['permission']) && $param['permission']) {
                if ($param['search']['tree'][0] == 1 && isset($param['search']['dept_id'])) {
                    $query->whereRaw('find_in_set(?,arr_parent_id)', [$param['search']['dept_id'][0]]);
                } else if ($param['search']['tree'][0] == 0 && isset($param['search']['dept_id'])) {
                    $query->where('parent_id', $param['search']['dept_id'][0]);
                }
                unset($param['search']['dept_id']);
            }
            unset($param['search']['tree']);
            $query->multiWheres($param['search']);
        }
        

        return $query->orderBy('arr_parent_id', 'asc')
                            ->orderBy('dept_sort', 'asc')
                            ->count();
    }

    /**
     * @获取部门信息
     * @param type $deptId
     * @return array 部门信息
     */
    public function getDepartmentInfo($deptId) {
        $department = $this->entity;
        $department = $department->with(['directors' => function($department) {
            $department->leftJoin('user', function($join) {
                $join->on("user.user_id", '=', 'department_director.user_id');
            })->whereHas('directorHasOneUser', function ($department) {
                // 排除离职的负责人
                $department->where('user_accounts', '!=', '');
            })->orders(['user.list_number' => 'asc', 'user.user_id' => 'asc']);
        }, 'directors.directorHasOneUser'])->find($deptId);
        if (empty($department)) {
            return $department;
        }
        $department->director = [];
        if (!empty($department->directors)) {
            $department->director = $department->directors->pluck('user_id')->toArray();
        }
        return $department;
    }

    /**
     * @更新部门信息
     * @param type $data
     * @param type $deptId
     * @return Boolean
     */
    public function updateDepartment($data, $deptId) {
        return $this->entity->where($this->primaryKey, $deptId)->update($data);
    }

    /**
     * @更新子部门
     * @param type $newArrParentId
     * @param type $oldArrParentId
     * @param type $deptId
     * @return boolean
     */
    public function updateTreeChildren($newArrParentId, $oldArrParentId, $deptId) {
        $search = [
            'multiSearch' => [
                'dept_id'       => [$deptId],
                'arr_parent_id' => [$oldArrParentId, 'like'],
                '__relation__'  => 'or'
            ]
        ];
        return $this->entity->multiWheres($search)->update(['arr_parent_id' => DB::raw("replace(arr_parent_id, '$oldArrParentId', '$newArrParentId')")]);
    }

    /**
     * @删除部门
     * @param type $deptId
     * @return Boolean
     */
    public function deleteDepartment($deptId) {
        return $this->entity->where('dept_id', $deptId)->delete();
    }

    /**
     * 获取部门名称
     *
     * @param  array $deptId 部门id
     *
     * @return array
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    public function getRolesNameByIds(array $deptId) {
        return $this->entity->whereIn('dept_id', $deptId)->pluck('dept_name')->toArray();
    }

    /**
     * 费用统计 yww
     */
    public function deptChargeStatistics($data) {
        $default = [
            'fields' => ['charge_setting.*', 'department.dept_name as name', 'department.dept_id as id'],
            'page' => 0,
            'limit' => config('eoffice.pagesize'),
            'search' => [],
            'order_by' => ['charge_setting_id' => 'desc', 'department.dept_id' => 'asc']
        ];
        $param  = array_merge($default, array_filter($data));
        $result = $this->entity->select($param['fields'])
                        ->leftJoin('charge_setting', function($join) {
                            $join->on("charge_setting.dept_id", '=', 'department.dept_id')->where("set_type", 2)->whereNull('charge_setting.deleted_at');
                        })
                        ->wheres($param['search'])
                        ->orders($param['order_by'])
                        ->parsePage($param['page'], $param['limit'])
                        ->get()
                        ->toArray();

        if (isset($param['search']['department.dept_id'][0]) && !empty($param['search']['department.dept_id'][0]) && empty($result)) {
                $result   = [];
                $result[] = [
                    'user_id'          => $param['search']['department.dept_id'][0],
                    'alert_method'     => '',
                    'alert_value'      => 0,
                    'alert_data_start' => "0000-00-00",
                    'alert_data_end'   => "0000-00-00",
                    'subject_check'    => 0,
                    'set_type'         => 2,
                    'id'               => $param['search']['department.dept_id'][0]
                ];
                $deptInfo = $this->entity->select('dept_name')->where('dept_id', $param['search']['department.dept_id'][0])->first();
                $result[0]['name'] = isset($deptInfo->dept_name) ? $deptInfo->dept_name : '';
        }

        return $result;
    }

    public function deptChargeStatisticsTotal($data) {
        $default = [
            'search' => []
        ];
        $param = array_merge($default, array_filter($data));
        $count = $this->entity->leftJoin('charge_setting', function($join) {
                            $join->on("charge_setting.dept_id", '=', 'department.dept_id')->where("set_type", 2)->whereNull('charge_setting.deleted_at');
                        })
                        ->wheres($param['search'])
                        ->get()->count();

        if (isset($param['search']['department.dept_id']) && $count == 0) {
            $count = 1;
        }

        return $count;
    }

    /**
     * 根据部门ID获取该部门所有子部门
     *
     * @param  Int $deptId 部门id
     *
     * @return array
     *
     * @author miaochenchen
     *
     * @since  2016-09-08
     */
    public function getALLChlidrenByDeptId($deptId) {
        $deptInfo = $this->getDetail($deptId);
        if ($deptInfo) {
            $deptInfo = $deptInfo->toArray();
        }
        $deptPath = (isset($deptInfo['arr_parent_id']) ? $deptInfo['arr_parent_id'] : '0') . ',' . (isset($deptInfo['dept_id']) ? $deptInfo['dept_id'] : 0);
        $childrenDeptList = $this->entity->select('dept_id')->orWhere('arr_parent_id', 'like', $deptPath.',%')->orWhere('arr_parent_id', $deptPath)->get()->toArray();
        return $childrenDeptList;
    }

    //获取所有部门id
    public function getAllDepartmentId()
    {
        return $this->entity->select('dept_id')->get()->toArray();
    }
    // 获取最大序号
    public function getMaxSortByParent($parentId) {
        return $this->entity->where('parent_id', $parentId)->max('dept_sort');
    }
    public function getDepartmentTotal() {
        return $this->entity->count();
    }

    public function getDeptIdByName($depatName) {
        $deptInfo = $this->entity->select(['dept_id', 'arr_parent_id'])->where('dept_name', $depatName)->get()->toArray();
        return isset($deptInfo[0]) ? $deptInfo[0] : [];
    }
    public function getDeptIdByNameParent($depatName, $parentId) {
        $deptInfo = $this->entity->select(['dept_id', 'arr_parent_id'])->where('dept_name', $depatName)->where('parent_id', $parentId)->get()->toArray();
        return isset($deptInfo[0]) ? $deptInfo[0] : [];
    }
    public function getDeptIdByParentIdAndDeptName($depatName, $parentId) {
        $deptInfo = $this->entity->select(['dept_id', 'arr_parent_id'])->where('dept_name', $depatName)->where('parent_id', $parentId)->get()->toArray();
        return isset($deptInfo[0]) ? $deptInfo[0] : [];
    }
    public function getDeptNameByIds(array $deptIds)
    {
        return $this->entity->whereIn('dept_id', $deptIds)->pluck('dept_name')->toArray();
    }
    public function getDeptPermission($deptId)
    {
        return $this->entity->select(['dept_id'])
                ->with('departmentHasManyPermissionUser')
                ->with('departmentHasManyRole')
                ->with('departmentHasManyDept')
                ->where('dept_id', $deptId)->get()->toArray();
    }
}
