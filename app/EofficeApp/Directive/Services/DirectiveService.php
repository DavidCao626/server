<?php

namespace App\EofficeApp\Directive\Services;

use App\EofficeApp\Base\BaseService;
use Schema;
use Cache;

class DirectiveService extends BaseService
{

	private $userService;
	private $deptService;
	private $roleService;
	private $publicGroupService;
    private $scopeDeptIds = null;
	public function __construct()
	{
        parent::__construct();
		$this->userService = 'App\EofficeApp\User\Services\UserService';
		$this->deptService = 'App\EofficeApp\System\Department\Services\DepartmentService';
		$this->roleService = 'App\EofficeApp\Role\Services\RoleService';
		$this->publicGroupService = 'App\EofficeApp\PublicGroup\Services\PublicGroupService';
		$this->personalGroupService = 'App\EofficeApp\PersonalSet\Services\PersonalSetService';
		$this->userSystemInfoRepository = 'App\EofficeApp\User\Repositories\UserSystemInfoRepository';
        $this->userRepository = 'App\EofficeApp\User\Repositories\UserRepository';
        $this->systemComboboxService = 'App\EofficeApp\System\Combobox\Services\SystemComboboxService';
	}

	public function getUserRelation($params, $loginUser)
	{
            
		$search = [];
                
		//只获取列表
		$params['response'] = 'data';
		if(isset($params['search'])) {
			//$search = json_decode($params['search'], true);
			$search = $params['search'];
		}
		//获取匹配的用户列表
		$params['search'] = $this->getSearchByType($search, 'user');
		$userParams = $this->filterSearchParamByTableColumn('user', $params);
		$users = app($this->userService)->userSystemList($userParams, $loginUser)['list'];

		if (isset($params['multiple'])) {
			//获取匹配的部门列表
			$params['search'] = $this->getSearchByType($search, 'dept');
			$deptParams = $this->filterSearchParamByTableColumn('department', $params);
			$depts = app($this->deptService)->listDept($deptParams, $loginUser)['list'];
			//获取匹配的角色列表
			$params['search'] = $this->getSearchByType($search, 'role');
			$roleParams = $this->filterSearchParamByTableColumn('role', $params);
			$roles = app($this->roleService)->getRoleList($roleParams)['list'];
			// //获取匹配的公共组列表
			// $params['search'] = $this->getSearchByType($search, 'public_group');
			// $publicGroupParams = $this->filterSearchParamByTableColumn('public_group', $params);
			// $public_groups = app($this->publicGroupService)->
			// 	getPublicGroupList($loginUser['user_id'], $loginUser['dept_id'], $loginUser['role_id'], $publicGroupParams)['list'];
			// //获取匹配的自定义组列表
			// $params['search'] = $this->getSearchByType($search, 'personal_group');
			// $userGroupParams = $this->filterSearchParamByTableColumn('user_group', $params);
			// $personal_groups = app($this->personalGroupService)->listUserGroup($userGroupParams, $loginUser['user_id']);

			return [
				'user'=> $users,
				'dept' => $depts,
				'role' => $roles,
				//'public_group' => $public_groups,
				//'personal_group' => $personal_groups
			];
		} else {
			return [
				'user'=> $users
			];
		}
	}

	public function filterSearchParamByTableColumn($tableName, $params)
	{
		$tableColumn = Schema::getColumnListing($tableName);
		if (isset($params['search']) && !empty($params['search'])) {
			$params = $this->parseParams($params);
			$tempParams = $params;
			foreach ($params['search'] as $key => $value) {
				if ($key != 'multiSearch' && $key != 'multiSearch0' && $key != 'multiSearch1' && !in_array($key, $tableColumn)) {
					unset($tempParams['search'][$key]);
				}
			}
			return $tempParams;
		} else {
			return $params;
		}
	}

	public function getUserRelationById($params, $loginUser)
	{
		$search = [];
		$users = [];
		$depts = [];
		$roles = [];
		$public_groups = [];
		$personal_groups = [];
		//只获取列表
		$params['response'] = 'data';
		if(isset($params['search'])) {
			$search = json_decode($params['search'], true);
		}
		//获取匹配的用户列表
		if (isset($params['user_id']) && strlen($params['user_id']) > 0) {
			$temp_search = $search;
			$temp_search['user_id'] = [explode(',', $params['user_id']), 'in'];
			$params['search'] = json_encode($temp_search);
			$users = app($this->userService)->userSystemList($params, $loginUser)['list'];
		}
		//获取匹配的部门列表
		if (isset($params['dept_id']) && strlen($params['dept_id']) > 0) {
			$temp_search = $search;
			$temp_search['dept_id'] = [explode(',', $params['dept_id']), 'in'];
			$params['search'] = json_encode($temp_search);
			$depts = app($this->deptService)->listDept($params, $loginUser)['list'];
		}
		//获取匹配的角色列表
		if (isset($params['role_id']) && strlen($params['role_id']) > 0) {
			$temp_search = $search;
			$temp_search['role_id'] = [explode(',', $params['role_id']), 'in'];
			$params['search'] = json_encode($temp_search);
			$roles = app($this->roleService)->getRoleList($params)['list'];
		}
		// //获取匹配的公共组列表
		// if (isset($params['public_group_id']) && strlen($params['public_group_id']) > 0) {
		// 	$temp_search = $search;
		// 	$temp_search['group_id'] = [explode(',', $params['public_group_id']), 'in'];
		// 	$params['search'] = json_encode($temp_search);
		// 	$public_groups = app($this->publicGroupService)->
		// 		getPublicGroupList($loginUser['user_id'], $loginUser['dept_id'], $loginUser['role_id'], $params)['list'];
		// }
		// //获取匹配的自定义组列表
		// if (isset($params['personal_group_id']) && strlen($params['personal_group_id']) > 0) {
		// 	$temp_search = $search;
		// 	$temp_search['group_id'] = [explode(',', $params['personal_group_id']), 'in'];
		// 	$params['search'] = json_encode($temp_search);
		// 	$personal_groups = app($this->personalGroupService)->
		// 		listUserGroup($params, $loginUser['user_id']);
		// }
		return [
			'dept' => $depts,
			'role' => $roles,
			//'public_group' => $public_groups,
			//'personal_group' => $personal_groups,
			'user'=> $users
		];
	}
    
	public function getOrganizationMembers1($deptId, $params, $loginUser) {
		// 获取部门信息
		$params['response'] = 'data';
		$params['page'] = 0;
		$depts = app($this->deptService)->children($deptId, $params)->toArray();
		$result = array('dept' => $depts,'user' => []);
		// 查询条件
		$search = [];
		if(isset($params['search'])) {
			$search = json_decode($params['search'], true);
		}
		//是否追加了联系人筛选条件
		if (isset($params['filter'])) {
			$search[$params['filter']] = ['', '!='];
		}
		// 查询当前展开的父节点下的人员列表
		if($deptId != '0') {
			if(isset($search['dept_id']) && !empty($search['dept_id'])) {
				if(in_array($deptId, $search['dept_id']['0'])) {
					$users = $this->getOrganizationUserMember($deptId, $params, $search, $loginUser);
				}
			}else{
				$users = $this->getOrganizationUserMember($deptId, $params, $search, $loginUser);
			}
			if(!empty($users)) {
				$result['user'] = $users;
			}
		}
		// 判断一级子部门下有没有子部门或人员的时候给出标识
		if (!empty($depts)) {
			// 查询人员数目
			foreach($depts as $key => $value) {
				if ($value['has_children'] == 1) {
					$result['dept'][$key]['has_children'] = 1;
				} else {
					if (isset($search['dept_id']) && !empty($search['dept_id'])) {
						if (in_array($value['dept_id'], $search['dept_id']['0'])) {
							$childrenDeptUsersCount = $this->getOrganizationUserMember($value['dept_id'], $params, $search, $loginUser, 'count');
						} else {
							$childrenDeptUsersCount = 0;
						}
					} else {
						$childrenDeptUsersCount = $this->getOrganizationUserMember($value['dept_id'], $params, $search, $loginUser, 'count');
					}
					if ($childrenDeptUsersCount > 0) {
						$result['dept'][$key]['has_children'] = 1;
					} else {
						$result['dept'][$key]['has_children'] = 0;
					}
				}
			}
		}
		//合并部门和用户
		return $result;
	}
    /**
     * 获取组织架构树成员
     * 
     * @param type $deptId
     * @param type $params
     * @param type $own
     * 
     * @return array
     */
    public function getOrganizationMembers($deptId, $params, $own)
    {
        $departments = app($this->deptService)->children($deptId, $params, $own);
        $params = $this->parseParams($params);
        if (isset($params['dataFilter']) && !empty($params['dataFilter'])) {
            $config = config('dataFilter.' . $params['dataFilter']);
            if (!empty($config)) {
                $method = $config['dataFrom'][1];
                $params['loginUserInfo'] = $own;
                $data = app($config['dataFrom'][0])->$method($params);
                unset($params['loginUserInfo']);
                if (isset($data['user_id'])) {
                    if (!empty($data['user_id'])) {
                            // 默认包含获取到的user_id
                            if (isset($params['search']['user_id']) && !empty($params['search']['user_id'])) {
                                $searchUserId = $params['search']['user_id'][0];
                                if (!(is_array($searchUserId) || is_object($searchUserId))) {
                                    $params["search"]["user_id"][0] = explode(",", trim($params["search"]["user_id"][0], ","));
                                }
                                $data['user_id'] = array_intersect($params["search"]["user_id"][0], $data['user_id']);
                            } 
                             
                    } 
                    $params['search']['user_id'] = [$data['user_id'], 'in'];
                }
            }
        }
        /**
        | ---------------------------------------------------
        | 将param里的search单独拿出来使用，没有附初始值为空数组
        | ---------------------------------------------------
        */
        $search = $params['search'] ?? [];
        $params['search'] = $search;
        $encrypt = ecache('Auth:EncryptOrganization')->get($own['user_id']) ?? 0;

        /**
        | ---------------------------------------------------
        | 查询当前展开的父节点下的人员列表
        | ---------------------------------------------------
        */
        $users = [];
		if ($deptId != 0) {
            if (isset($search['dept_id']) && !empty($search['dept_id'])) {
                if (in_array($deptId, $search['dept_id'][0])) {
                    $users = $this->getUserList($deptId, $own, $params);
                }
            } else {
                $users = $this->getUserList($deptId, $own, $params);
            }
            if (!empty($users) && $encrypt) {
                foreach ($users as &$value) {
                    $value['dept_name'] = '*****';
                }
            }
        }
        /**
        | ---------------------------------------------------
        | 处理组织架构部门，判断该部门下是否有用户或子部门。 
        | ---------------------------------------------------
        */
        $depts = [];
        if (!$departments->isEmpty()) {
            $noCheckDeptIds = $deptsTemp = $deptGroups = [];

            foreach ($departments as $dept) {
                $temp = $this->handleOrganizationDepartment($dept, $params, $search, $own, function($dept, $params, $own) {
                    if (!$this->handleHasPrivOrg($dept->dept_id, $own, $params)) {
                        $dept->prv_check = 1;
                        $dept->has_children = 0;
                    }

                    return $dept;
                });
                if (!$temp->prv_check) {
                    $noCheckDeptIds[] = $temp->dept_id;
                }
                if ($encrypt) {
                    $temp->dept_name = '*****';
                }
                $deptsTemp[] = $temp;
            }
            
            unset($params['search']['dept_id']);
            if (!empty($noCheckDeptIds)) {
                $deptGroups = app($this->userRepository)->getUserCountGroupByDeptId($own, $params, $noCheckDeptIds)
                        ->mapWithKeys(function ($item) {
                            return [$item->dept_id => $item->count];
                        });
            }

            if (empty($deptGroups)) {
                $depts = $deptsTemp;
            } else {
                $depts = array_map(function($dept) use($deptGroups, $encrypt) {
                    if (isset($deptGroups[$dept->dept_id]) && $deptGroups[$dept->dept_id] > 0) {
                        $dept->has_children = 1;
                    }
                    if ($encrypt) {
                        $dept->dept_name = '*****';
                    }
                    return $dept;
                }, $deptsTemp);
            }
        }

        return ['dept' => $depts, 'user' => $users];
    }
    /**
     * 处理组织架构部门
     * 
     * @param type $dept
     * @param type $params
     * @param type $search
     * @param type $own
     * @param type $handle
     * 
     * @return object
     */
    private function handleOrganizationDepartment($dept, $params, $search, $own, $handle)
    {
        if($dept->has_children == 1) {
            $dept->prv_check = 1;
            
            return $dept;
        }
        
        if (isset($search['dept_id']) && !empty($search['dept_id'])) {
            if (in_array($dept->dept_id, $search['dept_id'][0])) {
                return $handle($dept, $params, $own);
            } 
            
            return $dept;
        }
        
        return $handle($dept, $params, $own);
    }
    /**
     * 处理带权限的组织架构
     * 
     * @param type $deptId
     * @param array $own
     * @param array $params
     * 
     * @return boolean
     */
    private function handleHasPrivOrg($deptId, array $own, array $params)
    {
        if (isset($params['manage']) && $params['manage'] == 1) {
            if ($own['user_id'] == 'admin') {
                if (isset($params['search']['dept_id'][0])) {
                    $searchDeptId = $params['search']['dept_id'][0];

                    $manageDeptIds = is_array($searchDeptId) ? $searchDeptId : explode(',', rtrim($searchDeptId, ','));
                    if (!empty($manageDeptIds) && !in_array($deptId, $manageDeptIds)) {
                        return false;
                    }
                }
            } else {
                $deptIds = $this->getScopeDeptIds($params, $own);
                if($deptIds == 'all'){
                    return true;
                }
                if (empty($deptIds) || !in_array($deptId, $deptIds)) {
                    return false;
                }
            }
        }

        return true;
    }
    /**
     * 获取用列表
     * 
     * @param type $deptId
     * @param array $own
     * @param array $params
     * 
     * @return array
     */
    public function getUserList($deptId, array $own, array $params)
    {
        if (!$this->handleHasPrivOrg($deptId, $own, $params)) {
            return [];
        }

        $params['search']['user_system_info.dept_id'] = [$deptId, '='];
        
        $users = app($this->userRepository)->getOrgUserList($own, $params);
        
        if (!$users->isEmpty()) {
            /**
            | ---------------------------------------------------
            | 获取用户角色信息 
            | ---------------------------------------------------
            */
            $userIds = array_column($users->toArray(), 'user_id');
            $roles = app('App\EofficeApp\Role\Repositories\UserRoleRepository')->getUserRole(['user_id' => [$userIds, 'in']], 1);
            $allRoleInfos = app('App\EofficeApp\Role\Repositories\RoleRepository')->getAllRoles(['fields' => ['role_id', 'role_name']]);
            $map = $allRoleInfos->mapWithKeys(function ($item) {
                return [$item->role_id => $item->role_name];
            });
            $rolesMap = [];
            foreach ($roles as $role) {
                $rolesMap[$role['user_id']][] = ['role_id' => $role['role_id'], 'role_name' => $map[$role['role_id']] ?? ''];
            }
            /**
            | ---------------------------------------------------
            | 获取用户岗位信息
            | ---------------------------------------------------
            */
            $userPositions = app($this->systemComboboxService)->getComboboxFieldByIdentify('USER_POSITION');
            /**
            | ---------------------------------------------------
            | 将用户角色和岗位信息拼接到用户列表中 
            | ---------------------------------------------------
            */
            foreach ($users as $key => $user) {
                $users[$key]['user_position_name'] = isset($userPositions[$user->user_position]) ? $userPositions[$user->user_position] : '';
                $users[$key]['roles'] = isset($rolesMap[$user->user_id]) ? $rolesMap[$user->user_id] : '';
            }
        }
        
        return $users;
    }
    /**
     * 获取管理范围内的部门id
     * 
     * @param type $param
     * @param type $own
     * 
     * @return array
     */
    private function getScopeDeptIds($param, $own)
    {
        if($this->scopeDeptIds){
            return $this->scopeDeptIds;
        }

        $manageDeptIds = $this->getPrivManageDeptId($param['search'], $own);

        return $this->scopeDeptIds = $manageDeptIds;
        
    }
    /**
     * 获取用管理权限的部门id
     * 
     * @param type $search
     * @param type $own
     * 
     * @return array
     */
    private function getPrivManageDeptId($search, $own)
    {
        $manageDeptIds = app($this->userService)->getUserCanManageDepartmentId($own);
        $deptIds = [];
        if (!empty($manageDeptIds)) {
            //查询参数与固定的管理范围和角色权限级别取交集
            if (isset($search['dept_id'][0]) && $manageDeptIds != 'all') {
                $searchDeptId = $search['dept_id'][0];
                if (is_array($searchDeptId)) {
                    $deptIds = array_intersect($searchDeptId, $manageDeptIds);
                } else {
                    $deptIds = $searchDeptId == 0 ? $manageDeptIds : array_intersect(explode(',', rtrim($searchDeptId, ',')), $manageDeptIds);
                }
            } else {
                return $manageDeptIds;
            }
        }
        
        return $deptIds;
    }
    
	public function getOrganizationUserMember($deptId, $params, $search, $loginUser, $type="array") {
		$search['dept_id'] = [$deptId, '='];
		$search = json_encode($search);
		$params['search'] = $search;
		if (isset($params['manage']) && $params['manage'] == '1') {
			// 用户管理里的组织树，需要只显示查看权限范围内的用户
			unset($params['manage']);
			$params = $this->parseParams($params);
			if($params['search']['dept_id'][0] == '0') {
				return $users = [];
			}else{
				if($type == 'count') {
					// 此处需要查权限范围内的用户数
					$tempParam = $params;
					$tempParam['response'] = 'count';
					return $users = app($this->userService)->userManageSystemList($loginUser, $tempParam)['total'];
				}
				$params['response'] = 'data';
				return $users = app($this->userService)->userManageSystemList($loginUser, $params)['list'];
			}
		}else{
			if($type == 'count') {
				$tempParam = $params;
				$tempParam['response'] = 'count';
				return $users = app($this->userService)->userSystemList($tempParam, $loginUser)['total'];
			}
			$params['response'] = 'data';
			return $users = app($this->userService)->userSystemList($params, $loginUser)['list'];
		}
	}
	public function getUserIdByGroup($params) {
		return app($this->personalGroupService)->getGroupsUserid($params);
	}

	private function getSearchByType($search, $type) {
		if (isset($search)) {
			switch($type) {
				case "dept":
					$result = str_replace('item_name', 'dept_name', $search);
					break;
				case "role":
					$result = str_replace('item_name', 'role_name', $search);
					break;
				// case "personal_group":
				// case "public_group":
				// 	$result = str_replace('item_name', 'group_name', $search);
				// 	break;
				default:
					$result = str_replace('item_name', 'user_name', $search);
					break;
			}
		}
		return $result;
	}
}
