<?php
namespace App\EofficeApp\System\Department\Services;

use App\EofficeApp\Base\BaseService;
use App\Utils\Utils;
use Schema;
use Cache;
use DB;
use App\EofficeApp\LogCenter\Facades\LogCenter;
/**
 * @部门服务类
 *
 * @author 李志军
 */
class DepartmentService extends BaseService
{
	public function __construct() {
		parent::__construct();
		$this->userRepository				= 'App\EofficeApp\User\Repositories\UserRepository';
		$this->userService				= 'App\EofficeApp\User\Services\UserService';
		$this->departmentRepository			= 'App\EofficeApp\System\Department\Repositories\DepartmentRepository';
		$this->departmentDirectorRepository = 'App\EofficeApp\System\Department\Repositories\DepartmentDirectorRepository';
		$this->departmentUserRepository = 'App\EofficeApp\System\Department\Repositories\DepartmentUserRepository';
		$this->departmentRoleRepository = 'App\EofficeApp\System\Department\Repositories\DepartmentRoleRepository';
		$this->departmentDeptRepository = 'App\EofficeApp\System\Department\Repositories\DepartmentDeptRepository';
	}
	/**
	 * @获取权限部门树
	 * @return array 部门树
	 */
	public function authTree($own)
	{
		switch ($own['post_priv']) {
			case 0://本部门
				$currentDept		= app($this->departmentRepository)->getDepartmentInfo($own['dept_id']);

				$currentDept->level = 0;

				$tree = $this->tree($own['dept_id'], 1);

				array_unshift($tree, $currentDept);

				return $tree;
			case 1://全部
				return $this->tree();
			case 2://选取的部门
				return $this->tree2(app($this->departmentRepository)->getDepartmentByIds(explode(',', $own['post_dept'])));
		}
	}
	/**
	 * @获取除了当前部门和其所有子部门外的整棵有权限部门树
	 * @param type $deptId
	 * @return array 部门树
	 */
	public function authTree2($deptId,$own)
	{
		if ($deptId == 0) {
			return ['code' => ['0x002001', 'department']];
		}

		$treeId = $this->getTreeIds($deptId);

		$tree = [];

		switch ($own['post_priv']) {
			case 0://本部门
				$otherTree		= $this->tree($own['dept_id'], 1);

				array_unshift($otherTree, app($this->departmentRepository)->getDepartmentInfo($own['dept_id']));

				foreach ($otherTree as $department) {
					if (!in_array($department->dept_id, $treeId)) {
						array_push($tree, $department);
					}
				}
				break;
			case 1://全部
				if ($departments = app($this->departmentRepository)->getOtherDepartment($treeId)) {
					$tree = $this->tree2($departments);
				}
				break;
			case 2://选取的部门
				foreach ($this->tree2(app($this->departmentRepository)->getDepartmentByIds(explode(',',  $own['post_dept']))) as $department) {
					if (!in_array($department->dept_id, $treeId)) {
						array_push($tree, $department);
					}
				}
				break;
		}

		return $tree;
	}
	/**
	 * @新建部门
	 * @param type $data
	 * @param type $director
	 * @return boolean | dept_id
	 */
	public function addDepartment($data, $loginUserId)
	{
		$deptName		= $data['dept_name'];

		$parentId		= $this->defaultValue('parent_id', $data, 0);

		if ($this->deptNameIsRepeat($deptName, $parentId)) {
			return ['code' => ['0x002007', 'department']];
		}
		// 和父级名字判断
		$department = app($this->departmentRepository)->getDepartmentInfo($parentId); //获取更新前的部门信息
		if ($department && $department->dept_name == $deptName) {
			return ['code'=> ['0x002011', 'department']];
		}

		$deptNamePinyin = Utils::convertPy($deptName);

        //来自企业微信同步组织架构，填加设定好的arr_parent_id
        if (isset($data['arr_parent_id'])&&!empty($data['dept_id'])){
            $arrParentId = $data['arr_parent_id'];
        }else{
            $arrParentId	= $parentId == 0 ? 0 : $this->getArrParentId($parentId);
        }


		$deptData = [
			'dept_name'     => $deptName,
			'tel_no'        => $this->defaultValue('tel_no', $data, ''),
			'fax_no'        => $this->defaultValue('fax_no', $data, ''),
			'parent_id'     => $parentId,
			'dept_name_py'  => $deptNamePinyin[0] ,
			'dept_name_zm'  => $deptNamePinyin[1],
			'arr_parent_id' => $arrParentId,
			'has_children'      => 0,
			'dept_sort'     => $this->defaultValue('dept_sort', $data, 0)
		];
		//来自企业微信同步组织架构，填加设定好的部门id
        if (isset($data['dept_id'])&&is_numeric($data['dept_id'])&&!empty($data['dept_id'])){
            $deptData['dept_id'] = $data['dept_id'];
        }

		if (!$result = app($this->departmentRepository)->addDepartment($deptData)) {
			return ['code' => ['0x000003', 'common']];
		}
		//更新父部门的children字段
		if ($parentId != 0) {
			app($this->departmentRepository)->updateDepartment(['has_children' => 1], $parentId);
		}
		//添加部门负责人
		$director = $this->defaultValue('director', $data, []);

		if (!empty($director)) {
			app($this->departmentDirectorRepository)->addDirector($result->dept_id, $director);
		}

		// 添加日志
		$logData = [
		    'log_content'        => trans('department.create_department').':'.$data['dept_name'],
		    'log_type'           => 'add',
		    'log_creator'        => $loginUserId,
		    'log_ip'             => getClientIp(),
		    'log_relation_table' => 'department',
		    'log_relation_id'    => $result->dept_id,
            'module'             => 'department'
		];
//        add_system_log($logData);
        $identifier  = "system.department.add";
        $logParams = $this->handleLogParams($loginUserId, $logData['log_content'], $result->dept_id, 'department', $data['dept_name']);
        logCenter::info($identifier , $logParams);
        if($arrParentId != '0') {
	        $pathStr   = trim(substr($arrParentId, 2), ',').','.$result->dept_id;
	        $pathArray = explode(',', $pathStr);
        }else{
        	$pathArray = [$result->dept_id];
        }

		return ['dept_id' => $result->dept_id, 'dept_path' => $pathArray, 'dept_arr_parent_id' => $arrParentId];

    }
     /**
	 * @批量新建部门
	 * @param type $data
	 * @param type $deptId
	 * @param type $director
	 * @return boolean
	 */
    public function addMultipleDepartment($data, $loginUserId)
	{
        $returnArray = [];

        foreach ($data as $value) {
            if (!isset($value['dept_name']) || empty($value['dept_name'])) {
                return ['code' => ['0x002006', 'department']];
            }
        }

        foreach ($data as $value) {
            $parentId = $this->defaultValue('parent_id', $value, 0);

            if ($this->deptNameIsRepeat($value['dept_name'], $parentId)) {
                return ['code' => ['0x002007', 'department'], 'dynamic' => ['【'.$value['dept_name'].'】'.trans('department.0x002007')]];
            }

            $result = $this->addDepartment($value, $loginUserId);

            if (isset($result['code'])) {
                return $result;
            }
            $result['dept_name'] = $value['dept_name'];
            $returnArray[] = $result;
        }

        return empty($returnArray) ? false : $returnArray;
    }
	/**
	 * @编辑部门
	 * @param type $data
	 * @param type $deptId
	 * @param type $director
	 * @return boolean
	 */
	public function updateDepartment($data, $deptId, $loginUserId)
	{
		if ($deptId == 0) {
			return ['code' => ['0x002001', 'department']];
		}
		if(isset($data['parent_id']) && !empty($data['parent_id']) && $data['parent_id'] == $deptId) {
			return ['code' => ['0x002009', 'department']];
		}
		// 父级信息
		$parentDeptInfo = $this->getParentDeptInfoByDeptId($deptId);
		$departments = app($this->departmentRepository)->getChildren($deptId);
		$departmentsArr = [];
		if ($departments) {
			$departmentsArr = $departments->toArray();
		}
		$parentName = '';
		// 获取上级部门的名称
		if (isset($data['parent_id']) && !empty($data['parent_id'])) {
			$departDetail = app($this->departmentRepository)->getDetail($data['parent_id']);
			$parentName = $departDetail->dept_name ?? '';
			// 删除权限
			app($this->departmentUserRepository)->deleteByWhere(['dept_id' => [$deptId]]);
			app($this->departmentRoleRepository)->deleteByWhere(['dept_id' => [$deptId]]);
			app($this->departmentDeptRepository)->deleteByWhere(['dept_id' => [$deptId]]);
		}

		$childDept = array_column($departmentsArr, 'dept_name');
		// 判断父级和子级名称一样
		if (isset($parentDeptInfo['dept_name'])) {
			array_push($childDept, $parentDeptInfo['dept_name']);
			array_push($childDept, $parentName);
			if (isset($data['dept_name']) && in_array($data['dept_name'], $childDept)) {
				return ['code'=> ['0x002011', 'department']];
			}
		}
        //企业微信同步组织架构添加 -- 获取部门信息，判断部门是否存在
        $deptInfo = app($this->departmentRepository)->getDepartmentByIds([$deptId])->isEmpty();
        if ($deptInfo){
            return ['code'=> ['0x002010', 'department']];
        }
		// 不能设置子部门为上级部门
		$allChildrenDept = app($this->departmentRepository)->getALLChlidrenByDeptId($deptId);
		if(!empty($allChildrenDept)) {
			$deptIdArr = array();
			foreach($allChildrenDept as $key => $value) {
				$deptIdArr[] = $value['dept_id'];
			}
			if(in_array($data['parent_id'], $deptIdArr)) {
				return ['code' => ['0x002008', 'department']];;
			}
		}

		$deptName = $data['dept_name'];
		$parentId = $this->defaultValue('parent_id', $data, 0);
		if ($this->deptNameIsRepeat($deptName, $parentId, $deptId)) {
			return ['code' => ['0x002007', 'department']];
		}
		$deptNamePinyin = Utils::convertPy($deptName);
		$arrParentId	= $parentId == 0 ? 0 : $this->getArrParentId($parentId);
        $deptData = [
			'dept_name'     => $deptName,
			'tel_no'        => $this->defaultValue('tel_no', $data, ''),
			'fax_no'        => $this->defaultValue('fax_no', $data, ''),
			'parent_id'     => $parentId,
			'dept_name_py'  => $deptNamePinyin[0] ,
			'dept_name_zm'  => $deptNamePinyin[1],
			'arr_parent_id' => $arrParentId,
			'dept_sort'     => $this->defaultValue('dept_sort', $data, 0)
		];
		$oldDept = app($this->departmentRepository)->getDepartmentInfo($deptId); //获取更新前的部门信息
		app($this->departmentRepository)->updateDepartment($deptData, $deptId); //更新部门主表信息

		$newDept = app($this->departmentRepository)->getDepartmentInfo($deptId);
		//更新编辑前的部门父id的children字段。
		if ($parentId != $oldDept->parent_id) {
			//判断更新前的父部门是否有子部门，没有怎更新其children字段
			if (app($this->departmentRepository)->getChildrenCount($oldDept->parent_id) == 0) {
				app($this->departmentRepository)->updateDepartment(['has_children' => 0], $oldDept->parent_id);
			}
			//更新改进后的父部门的children字段
			if ($parentId != 0) {
				app($this->departmentRepository)->updateDepartment(['has_children' => 1], $parentId);
			}
			//更新该部门所有子部门的arr_parent_id字段;
			if (app($this->departmentRepository)->getChildrenCount($deptId) > 0) {
				app($this->departmentRepository)->updateTreeChildren($newDept->arr_parent_id.','.$deptId, $oldDept->arr_parent_id.','.$deptId, $deptId);
			}
		}
		//更新部门负责人子表
		$directorarr = $this->defaultValue('director', $data, []);
		if (!empty($directorarr)) {
			if (!app($this->departmentDirectorRepository)->updateDirector($deptId, $directorarr)) {
				return ['code' => ['0x000003', 'common']];
			}
		}else{
			// 如果为空删除 负责人
			app($this->departmentDirectorRepository)->deleteDirector($deptId);
		}
		// 添加日志
		$logData = [
		    'log_content'        => trans('department.edit_department').':'.$oldDept->dept_name.'->'.$data['dept_name'],
		    'log_type'           => 'edit',
		    'log_creator'        => $loginUserId,
		    'log_ip'             => getClientIp(),
		    'log_relation_table' => 'department',
		    'log_relation_id'    => $deptId,
            'module'             => 'department'
		];
//        add_system_log($logData);
        $identifier  = "system.department.edit";
        $logParams = $this->handleLogParams($loginUserId, $logData['log_content'], $deptId, 'department', $data['dept_name']);
        logCenter::info($identifier , $logParams);
		if(!empty($newDept)) {
			if($newDept['arr_parent_id'] == '0') {
				$deptPath = [$newDept->dept_id];
			}else{
		        $pathStr = trim(substr($newDept->arr_parent_id, 2), ',').','.$newDept->dept_id;
		        $deptPath = explode(',', $pathStr);
			}
			return $deptPath;
		}
		return true;
	}
	/**
	 * @判断部门名称是否已使用
	 * @param type $deptName
	 * @param type $parentId
	 * @return boolean
	 */
    public function deptNameIsRepeat($deptName, $parentId, $deptId = 0)
	{
		if ($departments = app($this->departmentRepository)->getChildren($parentId)) {
			foreach ($departments as $key => $value) {
				if ($value->dept_name == $deptName && $value->dept_id != $deptId) {
					return true;
				}
			}
		}
		if ($deptId != 0) {
			$department = app($this->departmentRepository)->getDepartmentInfo($deptId); //获取更新前的部门信息
			if ($department->dept_name == $deptName) {
				return false;
			}
		}

		return false;
	}
	/**
	 * @获取所有父级部门id
	 * @param type $parentId
	 * @return string 所有父部门id
	 */
	public function getArrParentId($parentId)
	{
		$parentDepartment	= app($this->departmentRepository)->getDepartmentInfo($parentId);

		return $parentDepartment->arr_parent_id . ',' . $parentId;
	}
	 /**
	 * @获取某部门下的一级子部门
	 * @param type $deptId
	 * @return array 子部门树
	 */
    public function children($deptId, $param = '', $own=[])
    {
    	$param = $this->parseParams($param);
    	$param['permission'] = $param['permission'] ?? 1;
    	$param['system_permission'] = get_system_param('permission_organization');
    	// 开启了权限, 穿了参数
    	$depts = app($this->departmentRepository)->getChildren($deptId, $param, ['*'], $own);

    	if (isset($param['user_total']) && $param['user_total']) {
    		if ($depts) {
    			foreach ($depts as $value) {
    				$subDept = $this->allChildren($value['dept_id']);
    				$userCount = app($this->userRepository)->getUserCountGroupByDeptId($own, $param, explode(',', $subDept))->pluck('count')->toArray();
    				$value['user_total'] = array_sum($userCount);
    			}
    		}
    	}
    	if (isset($param['fullPath']) && $param['fullPath']) {
			$depts = $this->parseDeptPath($depts);
		}

        $encrypt = ecache('Auth:EncryptOrganization')->get($own['user_id']) ?? 0;
    	if ($encrypt && !$depts->isEmpty()) {
    		foreach ($depts as &$value) {
    			$value->dept_name = '*****';
    		}
    	}
    	return $depts;
    }
    private function parseDeptPath($depts)
    {
    	if (!$depts) {
    		return $depts;
    	}
    	foreach ($depts as $key => $value) {
    		$depts[$key]->arr_parent_name = $this->getDeptPathByDeptInfo($value->toArray());
    	}
    	return $depts;
    }

	 /**
	 * @获取某部门下的所有子部门包括本部门
	 * @param type $deptId
	 * @return string 子部门字符串包括本部门
	 */
    public function allChildren($deptId)
    {
    	if($deptId == '0') {
    		return '';
    	}
        $deptIdList = app($this->departmentRepository)->getALLChlidrenByDeptId($deptId);
        $deptIdStr = $deptId;
        if(!empty($deptIdList)) {
        	foreach($deptIdList as $key => $value) {
        		$deptIdStr .= ','.$value['dept_id'];
        	}
        }
        return $deptIdStr;
    }

	public function listDept($param, $own=[])
    {
    	if (!$own) {
    		$own = own();
    	}
    	$param = $this->parseParams($param);
    	$param['permission'] = $param['permission'] ?? 1;
    	
    	$param['system_permission'] = get_system_param('permission_organization');
    	if ($param['system_permission'] && $param['permission']) {
    		$param['permission'] = 0;
    		$deptId = app($this->departmentRepository)->getPermissionDeptIds($own);
	    	// 查找所有的子部门
	    	$allDeptId = array_column($deptId, 'dept_id');
	    	$allDept = $this->getALLChlidrenByDeptId($allDeptId);

	    	$allIds = array_reduce($allDept, function($carry, $item) {

	    		$uIds = array_column($item, 'dept_id');
	    		return array_merge($carry, $uIds);
	    	}, []);

	    	$allDeptId = array_filter(array_merge($allDeptId, $allIds));
	    	if (!isset($param['search']['dept_id'])) {
	    		$param['search']['dept_id'] = [$allDeptId, 'in'];
	    	} else if (isset($param['search']['dept_id']) && isset($param['search']['dept_id'][0])) {
	    		if ($param['search']['dept_id'][0] != 0) {
	    			$searchDept = is_array($param['search']['dept_id'][0]) ? $param['search']['dept_id'][0] : [$param['search']['dept_id'][0]];

		    		$insect = array_intersect($allDeptId, $searchDept);

		    		if ($insect) {
		    			if (isset($param['search']['tree'])) {
		    				$currentDeptIds = $this->getALLChlidrenByDeptId($insect);

					    	$currentAllIds = array_reduce($currentDeptIds, function($carry, $item) {

					    		$uIds = array_column($item, 'dept_id');
					    		return array_merge($carry, $uIds);
					    	}, []);
					    	$param['search']['dept_id'] = [$currentAllIds, 'in'];
		    			} else {
		    				$param['search']['dept_id'] = [$insect, 'in'];
		    			}
		    			
				    	// $currentAllIds = array_unique(array_merge($currentAllIds, $searchDept));
		    			
		    		} else {
		    			return ['list' => [], 'total' => 0];
		    		}
	    		} else {
	    			$allDeptId = array_merge($allDeptId, array_column($deptId, 'dept_id'));
	    			$param['search']['dept_id'] = [$allDeptId, 'in'];
	    		}
	    	}
	    	if (!$allDeptId) {
	    		return ['list' => [], 'total' => 0];
	    	}

    	} else {
    		$param['user_id'] = $own['user_id'] ?? '';
		    $param['role_id'] = $own['role_id'] ?? [];
		    $param['dept_id'] = $own['dept_id'] ?? '';
    	}
		$data = $this->response(app($this->departmentRepository), 'getDeptCount', 'listDept', $param);
		if (isset($param['fullPath']) && $param['fullPath']) {
			if (isset($data['list']) && !empty($data['list'])) {
				foreach ($data['list'] as $key => $value) {
					$data['list'][$key]['dept_name'] = $this->getDeptPathByDeptInfo($value);
					$data['list'][$key]['arr_parent_name'] = $data['list'][$key]['dept_name'];
				}
			}
		}
		return $data;
	}
	/**
	 * @获取部门详情
	 * @param type $deptId
	 * @return object 部门信息
	 */
	public function getDeptDetail($deptId)
	{
		if ($deptId == 0) {
			return ['code' => ['0x002001', 'department']];
		}
		$deptInfo = app($this->departmentRepository)->getDepartmentInfo($deptId);
		if(!empty($deptInfo)) {
			if($deptInfo['arr_parent_id'] == '0') {
				$deptInfo->dept_path = [$deptInfo->dept_id];
			}else{
		        $pathStr = trim(substr($deptInfo->arr_parent_id, 2), ',').','.$deptInfo->dept_id;
		        $deptInfo->dept_path = explode(',', $pathStr);
			}
		}
		return $deptInfo;
	}

	public function getDeptPathByDeptId($deptId)
    {
        $deptInfo = app($this->departmentRepository)->getDepartmentInfo($deptId);
        if ($deptInfo) {
            $deptInfo = $deptInfo->toArray();
        }

        return $this->getDeptPathByDeptInfo($deptInfo);
    }

    public function getDeptPathByDeptInfo($deptInfo)
    {
        $deptDetail = array();
        if ($deptInfo['arr_parent_id'] == '0') {
            // 如果是顶级部门
            $deptDetail['dept_path'] = $deptInfo['dept_name'];
        } else {
            // 如果是子部门
            $deptParentId = explode(',', $deptInfo['arr_parent_id']);
            $deptPathArr = array();
            foreach ($deptParentId as $key => $val) {
                if ($key == '0') continue;
                $parentDeptInfo = app($this->departmentRepository)->getDepartmentInfo($val);
                if ($parentDeptInfo) {
                	$parentDeptInfo = $parentDeptInfo->toArray();
                	$deptPathArr[] = $parentDeptInfo['dept_name'] ?? '';
                }
            }
            $deptPathArr[] = $deptInfo['dept_name'];
            $deptDetail['dept_path'] = implode('/', $deptPathArr);
        }
        return $deptDetail['dept_path'];
    }
	/**
	 * @检查是否有部门用户
	 * @param type $deptId
	 * @return array 部门用户
	 */
	public function checkDeptUsers($deptId)
	{
		$users = app($this->userRepository)->getNotLeaveUserByDepartment($deptId);

		if(count($users) > 0) {
			return true;
		}

		return false;
	}
	/**
	 * @检查是否有子部门
	 * @param type $deptId
	 * @return boolean
	 */
	public function checkSonDept($deptId)
	{
		if (app($this->departmentRepository)->getChildrenCount($deptId) > 0) {
			return true;
		}

		return false;
	}
	/**
	 * @删除部门
	 * @param type $deptId
	 * @return boolean
	 */
	public function delete($deptId, $loginUserId)
	{
		if ($this->checkDeptUsers($deptId)) {
			return ['code' => ['0x002004', 'department']];
		}

		if ($this->checkSonDept($deptId)) {
			return ['code' => ['0x002005', 'department']];
		}

        $oldDept = app($this->departmentRepository)->getDepartmentInfo($deptId); //获取更新前的部门信息
		if(!empty($oldDept)) {
			// 返回父部门的路径
			if($oldDept['arr_parent_id'] == '0') {
				$oldDept->dept_parent_path = 0;
			}else{
		        $pathStr = trim(substr($oldDept->arr_parent_id, 2), ',');
		        $oldDept->dept_parent_path = explode(',', $pathStr);
			}
		}

		if (app($this->departmentRepository)->deleteDepartment($deptId)) {
			app($this->departmentDirectorRepository)->deleteDirector($deptId); //删除部门负责人
			$this->deleteDomainDept($deptId); //删除中间表该部门
			//判断删除前的父部门是否还有子部门，没有怎更新其children字段
			if (app($this->departmentRepository)->getChildrenCount($oldDept->parent_id) == 0) {
				app($this->departmentRepository)->updateDepartment(['has_children' => 0], $oldDept->parent_id);
			}
			// 添加日志
			$logData = [
			    'log_content'        => trans('department.delete_department').':'.$oldDept->dept_name,
			    'log_type'           => 'delete',
			    'log_creator'        => $loginUserId,
			    'log_ip'             => getClientIp(),
			    'log_relation_table' => 'department',
			    'log_relation_id'    => $deptId,
                'module'             => 'department'
			];
//	        add_system_log($logData);
            $identifier  = "system.department.delete";
            $logParams = $this->handleLogParams($loginUserId, $logData['log_content'], $deptId, 'department', $oldDept->dept_name);
            logCenter::info($identifier , $logParams);
			return $oldDept;
		}

		return false;
	}
	//删除域同步中间表该部门
	public function deleteDomainDept($deptId)
	{
		if(Schema::hasTable('ad_sync_contrast')){
            $record = DB::table('ad_sync_contrast')->where('dept_id', $deptId)->first();
            if(!empty($record)){
                return DB::table('ad_sync_contrast')->where('dept_id', $deptId)->delete();
            }
        }

        return true;
	}
	/**
	 * @获取部门树ID
	 * @param type $deptId
	 * @return array 部门树ID
	 */
	public function getTreeIds($deptId)
	{
		$treeId = [];

		$trees	= $this->tree($deptId);

		array_push($treeId, intval($deptId));

		if ($trees) {
			foreach ($trees as $dept) {
				array_push($treeId, $dept->dept_id);
			}
		}

		return $treeId;
	}
	/**
	 * @获取一维数组部门树递归函数
	 * @param type $parentId
	 * @param type $level
	 * @return array|object
	 */
    public function tree($parentId = 0, $level = 0)
    {
        $tree = [];

		if ($departments = app($this->departmentRepository)->getChildren($parentId)) {
			foreach ($departments as $department) {
				$department->level = $level;

				$tree[] = $department;

				if ($department->has_children == 1) {
					$tree = array_merge($tree, $this->tree($department->dept_id, $level + 1));
				}
			}
		}

		return $tree;
	}
	public function mulitTree($parentId = 0)
	{
		$tree = [];

		if ($departments = app($this->departmentRepository)->getChildren($parentId)) {
			foreach ($departments as $department) {


				if ($department->has_children == 1) {
					$department->sub_dept =  $this->tree($department->dept_id);
				}
				$tree[] = $department;
			}
		}

		return $tree;
	}
    /**
	 * @将所有部门转为树的递归函数
	 * @param type $arr
	 * @param type $id
	 * @param type $wt
	 * @param type $pid
	 * @param type $num
	 * @return array
	 */
    public function tree2($arr, $id = 'dept_id', $wt = 'parent_id', $pid = 0, $num = 0)
    {
        $num++;

		$newArr = array();

		foreach ($arr as $k => $v) {
			if ($v->$wt == $pid) {
				$v->level = $num;

				$newArr[] = $v;

				$newArr = array_merge($newArr, $this->tree2($arr, $id, $wt, $v->$id, $num));
			}
		}

		return $newArr;
	}

    /**
     * 根据部门ID获取它的根部门信息
     * 流程宏控件获值的时候，调用了此函数，修改请注意。
     *
     * @author 缪晨晨
     *
     * @param  string $deptId
     *
     * @since  2018-06-19 创建
     *
     * @return array   返回结果
     */
    public function getRootDeptInfoByDeptId($deptId)
    {
    	$deptInfo = $this->getDeptDetail($deptId);
    	$rootDeptInfo = [
    		'dept_name' => '',
    		'dept_id' => ''
    	];
		if(!empty($deptInfo) && !isset($deptInfo['code'])) {
			if($deptInfo->arr_parent_id == '0') {
				$rootDeptInfo = [
					'dept_name' => isset($deptInfo->dept_name) ? $deptInfo->dept_name : '',
					'dept_id'   => isset($deptInfo->dept_id) ? $deptInfo->dept_id : ''
				];
			}else{
		        $pathStr = trim(substr($deptInfo->arr_parent_id, 2), ',');
		        $deptInfo->dept_path = explode(',', $pathStr);
		        if (isset($deptInfo->dept_path[0]) && !empty($deptInfo->dept_path[0])) {
					$tempDeptInfo = $this->getDeptDetail($deptInfo->dept_path[0]);
					if (!empty($tempDeptInfo)) {
						$rootDeptInfo = [
							'dept_name' => isset($tempDeptInfo->dept_name) ? $tempDeptInfo->dept_name : '',
							'dept_id'   => isset($tempDeptInfo->dept_id) ? $tempDeptInfo->dept_id : ''
						];
					}
		        }
			}
		}
		return $rootDeptInfo;
    }

    /**
     * 根据部门ID获取它的上级部门信息
     *
     * @author 缪晨晨
     *
     * @param  string $deptId
     *
     * @since  2018-07-17 创建
     *
     * @return array   返回结果
     */
    public function getParentDeptInfoByDeptId($deptId)
    {
    	$parentDepartmentInfo = [
    		'dept_name' => '',
    		'dept_id' => ''
    	];
    	if (empty($deptId)) {
    		return $parentDepartmentInfo;
    	}
    	$deptInfo = $this->getDeptDetail($deptId);
        $parentId = isset($deptInfo["parent_id"]) ? $deptInfo["parent_id"] : "";
        if(!empty($parentId)) {
            $parentDepartmentResult = $this->getDeptDetail($parentId);
			if (!empty($parentDepartmentResult)) {
				$parentDepartmentInfo = [
					'dept_name' => isset($parentDepartmentResult->dept_name) ? $parentDepartmentResult->dept_name : '',
					'dept_id'   => isset($parentDepartmentResult->dept_id) ? $parentDepartmentResult->dept_id : ''
				];
			}
        }
        return $parentDepartmentInfo;
    }

	private function defaultValue($key, $data, $default)
	{
		return isset($data[$key]) ? $data[$key] : $default;
	}

	public function deptTreeSearch($param, $own = []) {
		if (!$own) {
    		$own = own();
    	}
		$param = $this->parseParams($param);
		$param['user_id'] = $own['user_id'] ?? '';
    	$param['role_id'] = $own['role_id'] ?? [];
    	$param['dept_id'] = $own['dept_id'] ?? '';
		$param['permission'] = $param['permission'] ?? true;
    	$param['system_permission'] = get_system_param('permission_organization');
		$result = app($this->departmentRepository)->listDept($param);
		return $result;
	}
	public function getAllDeptId() {
		$result = app($this->departmentRepository)->getAllDepartmentId();
		return $result;
	}

	public function getDeptUserArr($param, $own) {
		$param = $this->parseParams($param);
		
    	$param['system_permission'] = get_system_param('permission_organization');
    	
    	if ($param['system_permission']) {
    		$param['permission'] = 0;
    		$deptId = app($this->departmentRepository)->getPermissionDeptIds($own);
    	
	    	// 查找所有的子部门
	    	$allDeptId = array_column($deptId, 'dept_id');
	    	$allDept = $this->getALLChlidrenByDeptId($allDeptId);
	    	$allIds = array_reduce($allDept, function($carry, $item) {

	    		$uIds = array_column($item, 'dept_id');
	    		return array_merge($carry, $uIds);
	    	}, []);
	    	$allDeptId = array_filter(array_merge($allDeptId, $allIds));
    		$param['search']['dept_id'] = [$allDeptId, 'in'];
    	}
        $dept = app($this->departmentRepository)->listDept($param);
        
        $encrypt = ecache('Auth:EncryptOrganization')->get($own['user_id']) ?? 0;
    	if ($encrypt && !empty($dept)) {
    		foreach ($dept as $key => $value) {
    			$dept[$key]['dept_name'] = '*****';
    		}
        }
		$user = app($this->userService)->userSystemList($param);
		$result = ['dept' => $dept, 'user' => $user['list']];
		return $result;
	}
	private function getALLChlidrenByDeptId($deptIdArr)
	{
		$deptIdList = [];
		foreach ($deptIdArr as $key => $value) {
			$deptIdList[] = app($this->departmentRepository)->getALLChlidrenByDeptId($value);
		}
		return $deptIdList;
		
	}

    /**
     * 同步执行过程中的部门数据回滚
     * @param $data
     * @param $loginUserId
     * @return dept_id|bool|string
     */
	public function addDepartmentForWorkWechat($data, $loginUserId) {

		if (Schema::hasTable('department') && !Schema::hasTable('department_copy')) {
			Schema::rename('department', 'department_copy');
		}
		if (Schema::hasTable('department_director') && !Schema::hasTable('department_director_copy')) {
			Schema::rename('department_director', 'department_director_copy');
		}
		$table='department';
        if (Schema::hasTable('department_copy') && !Schema::hasTable($table)){
            DB::update("create table $table like department_copy");
        }
        $directorTable = 'department_director';
        if (Schema::hasTable('department_director_copy') && !Schema::hasTable($directorTable)){
            DB::update("create table $directorTable like department_director_copy");
        }

        DB::table('department')->truncate();
        try{
            foreach ($data as $dept_id => $dept){
                $return = $this->addDepartment($dept, $loginUserId);
                if (isset($return['code'])) {
                    // 同步失败删除表,恢复数据
                    Schema::dropIfExists('department');
                    Schema::dropIfExists('department_director');
                    if (Schema::hasTable('department_copy')) {
                        Schema::rename('department_copy', 'department');
                    }
                    if (Schema::hasTable('department_director_copy')) {
                        Schema::rename('department_director_copy', 'department_director');
                    }
                    return $return;
                }

            }

        } catch (\Exception $e) {
        	Schema::dropIfExists('department');
        	Schema::dropIfExists('department_director');
        	if (Schema::hasTable('department_copy')) {
        		Schema::rename('department_copy', 'department');
        	}
        	if (Schema::hasTable('department_director_copy')) {
        		Schema::rename('department_director_copy', 'department_director');
        	}
        	return $e->getMessage();
        }
	}

    /**
     * 同步前部门数据备份
     */
	public function syncDeptDataBackup(){
        $table = ['department', 'department_director'];
        $prefix = '_sync_backup';
        foreach ($table as $key => $value) {
            if(Schema::hasTable($value)){
                $temTableName = $value . $prefix;
                Schema::dropIfExists($temTableName);
                DB::update("create table $temTableName like $value");
                DB::update("insert into $temTableName select * from $value");
            }
        }
    }

    /**
     * 部门数据还原
     * @return mixed
     * @author [dosy]
     */
    public function syncDeptDataReduction(){
        $table = ['department', 'department_director'];
        $prefix = '_sync_backup';
        foreach ($table as $key => $value) {
            if (Schema::hasTable($value . $prefix)) {
                Schema::dropIfExists($value);
                $temTableName = $value . $prefix;
                DB::update("create table $value like $temTableName");
                DB::update("insert into $value select * from $temTableName");
              //  DB::update("create table $value select * from $temTableName");
                //Schema::rename($value . $prefix, $value);
            }
        }
    }

	// 同步成功之后调用删除备份数据
	public function addDepartmentSuccessForWorkWechat() {
		// 同步成功删除备份表,恢复数据
    	Schema::dropIfExists('department_copy');
    	Schema::dropIfExists('department_director_copy');
        return true;
    }

	public function getTotalDepartment()
	{
		$total = app($this->departmentRepository)->getDepartmentTotal();

		return ['total' => $total];
	}
    public function handleLogParams($user , $content , $relation_id = '' ,$relation_table = '', $relation_title='')
    {
        $data = [
            'creator' => $user,
            'content' => $content,
            'relation_table' => $relation_table,
            'relation_id' => $relation_id,
            'relation_title' => $relation_title
        ];
        return $data;
    }
    public function setDeptPermission($deptId, $data)
    {
    	if (!$deptId) {
    		return ['code' => ['0x000003', 'common']];
    	}
    	// 先删除后新增
    	$this->deleteDepartmentRelation($deptId);

    	return $this->addDepartmentRelation($deptId, $data);
    }
    private function deleteDepartmentRelation($deptId)
    {
    	$where = ['dept_id' => [$deptId]];
    	app($this->departmentUserRepository)->deleteByWhere($where);
    	app($this->departmentDeptRepository)->deleteByWhere($where);
    	app($this->departmentRoleRepository)->deleteByWhere($where);
    	return true;
    }
    private function addDepartmentRelation($deptId, $data)
    {
    	$userIds = $data['user_id'] ?? [];
    	$deptIds = $data['dept_id'] ?? [];
    	$roleIds = $data['role_id'] ?? [];
    	$this->addDepartmentUserRelation($deptId, $userIds);
    	$this->addDepartmentDeptRelation($deptId, $deptIds);
    	$this->addDepartmentRoleRelation($deptId, $roleIds);
    	return true;
    }
    private function addDepartmentUserRelation($deptId, $userIds)
    {
    	$insertData = [];
    	foreach ($userIds as $key => $value) {
    		$insertData[] = [
    			'dept_id' => $deptId,
    			'user_id' => $value
    		];
    	}
    	return app($this->departmentUserRepository)->insertMultipleData($insertData);
    }
    private function addDepartmentDeptRelation($deptId, $deptIds)
    {
    	$insertData = [];
    	foreach ($deptIds as $key => $value) {
    		$insertData[] = [
    			'dept_id' => $deptId,
    			're_dept_id' => $value
    		];
    	}
    	return app($this->departmentDeptRepository)->insertMultipleData($insertData);
    }
    private function addDepartmentRoleRelation($deptId, $roleIds)
    {
    	$insertData = [];
    	foreach ($roleIds as $key => $value) {
    		$insertData[] = [
    			'dept_id' => $deptId,
    			'role_id' => $value
    		];
    	}
    	return app($this->departmentRoleRepository)->insertMultipleData($insertData);
    }
    public function getDeptPermission($deptId, $param)
    {
    	$data = app($this->departmentRepository)->getDeptPermission($deptId);
    	$data = $data[0] ?? [];
    	$result = [];
    	if ($data) {
    		$result = [
    			'dept_id' => array_column($data['department_has_many_dept'], 're_dept_id'),
    			'user_id' => array_column($data['department_has_many_permission_user'], 'user_id'),
    			'role_id' => array_column($data['department_has_many_role'], 'role_id'),

    		];
    	}
    	return $result;
    }
    public function getPermissionDeptIds($userInfo)
    {
    	$datas = app($this->departmentRepository)->getPermissionDeptIds($userInfo);
    	return array_column($datas, 'dept_id') ?? [];
    }
    public function clearDeptPermission()
    {
    	app($this->departmentRoleRepository)->truncateRole();
    	app($this->departmentUserRepository)->truncateUser();
    	app($this->departmentDeptRepository)->truncateDept();
    	return true;
    }
    public function getPermissionDept($own)
    {
    	if (!$own) {
    		$own = own();
    	}
    	$deptId = app($this->departmentRepository)->getPermissionDeptIds($own);    	// 查找所有的子部门
    	$allDeptId = array_column($deptId, 'dept_id');
    	$allDept = $this->getALLChlidrenByDeptId($allDeptId);
    	$allIds = array_reduce($allDept, function($carry, $item) {

    		$uIds = array_column($item, 'dept_id');
    		return array_merge($carry, $uIds);
    	}, []);
    	return array_filter(array_merge($allDeptId, $allIds));
    }
}
