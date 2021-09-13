<?php

namespace App\EofficeApp\PersonnelFiles\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\PersonnelFiles\Entities\PersonnelFilesEntity;
use DB;
/**
 * personnel_files资源库
 *
 * @author  朱从玺
 *
 * @since  2015-10-28
 */
class PersonnelFilesRepository extends BaseRepository
{
	public function __construct(PersonnelFilesEntity $personnelFilesEntity)
	{
		parent::__construct($personnelFilesEntity);
	}

	/**
	 * [getPersonnelFileCount 获取列表数据的数量]
	 *
	 * @author 朱从玺
	 *
	 * @param  [array] $params [查询条件]
	 *
	 * @since 2015-10-28 创建
	 *
	 * @return [int]           [查询结果]
	 */
	public function getPersonnelFilesCount($params)
	{
		$search = isset($params['search']) ? $params['search'] : [];
		$query = $this->entity;
					// ->leftjoin('department','department.dept_id' ,'=', 'personnel_files.dept_id')
		if(isset($params['search']['birthday'])) {
			$start = $params['search']['birthday'][0] ?? '';
			$end = $params['search']['birthday'][1] ?? '';
			$query = $query->whereRaw("date_format(`birthday`, '%m-%d')>=?", [$start])
			      ->whereRaw("date_format(`birthday`, '%m-%d')<= ?", [$end]);
			unset($search['birthday']);
		}
		$query = $query->multiWheres($search);

		if(isset($params['sub_search'])) {
			$query = $query->whereHas('personnelFilesHasOneSub', function($query) use ($params)
			{
				$query->wheres($params['sub_search']);
			});
		}
		$query->whereNull('deleted_at');
		return $query->count();
	}

	/**
	 * [getPersonnelFilesList 获取人事档案列表]
	 *
	 * @author 朱从玺
	 *
	 * @param  [array]                $params [查询条件]
	 *
	 * @since  2015-10-28 创建
	 *
	 * @return [array]                        [查询结果]
	 */
	public function getPersonnelFilesList($params)
	{
		$defaults = array(
			'fields'	=> ['*'],
			'page'		=> 0,
			'limit'		=> config('eoffice.pagesize'),
			'order_by'	=> ['no' => 'asc'],
			'search'	=> [],
		);
		$params = array_merge($defaults, $params);
		foreach ($params['order_by'] as $key => $value){
		    $orders['personnel_files.'.$key] = $value;
        }
		$query = $this->entity
					->select($params['fields'])
					->leftjoin('department','department.dept_id' ,'=', 'personnel_files.dept_id');
        // 关联user表，获取user_accounts信息，用with的方法，可以平滑升级(丁鹏-20210519)
        // (user表信息包在personnel_files_to_user里面，之前指定了fields的请求也不会报错)
        if (isset($params['with_user_table']) && $params['with_user_table'] == '1') {
            $query = $query->with(['personnelFilesToUser' => function($query) {
                        $query->select('user_id', 'user_accounts');
                    }]);
        }
		$query = $query->multiWheres($params['search'])
					->parsePage($params['page'],$params['limit'])
					->orders($orders);
		if (isset($params['include_leave']) && !$params['include_leave']) {
			$query = $query->where('status', '!=', 2);
		}
		$query->whereNull('personnel_files.deleted_at');
		$result = $query->get()->toArray();
		if ($result) {
			foreach ($result as $k => &$v) {
				if (isset($v['status'])) {
					$v['status_name'] = mulit_trans_dynamic("user_status.status_name.user_status_" .$v['status']);
				}
			}
		}
		return $result;

		// $query = $this->entity
		// 			->select($params['fields'])
		// 			->with(['department' => function($query)
		// 			{
		// 				$query->select('dept_id', 'dept_name');
		// 			}]);
		// if(isset($params['search']['birthday'])) {
		// 	$query = $query->whereRaw("substring_index(birthday, '-', -2) between '".$params['search']['birthday'][0]."' and '".$params['search']['birthday'][1]."'");
		// 	unset($params['search']['birthday']);
		// }
		// $query = $query->wheres($params['search'])
		// 			->parsePage($params['page'], $params['limit'])
		// 			->orders($params['order_by']);
		// if(isset($params['sub_search'])) {
		// 	$query = $query->whereHas('personnelFilesHasOneSub', function($query) use ($params)
		// 	{
		// 		$query->wheres($params['sub_search']);
		// 	});
		// }

		// if(isset($params['sub_fields'])) {
		// 	$query = $query->with(['personnelFilesHasOneSub' => function($query) use ($params)
		// 	{
		// 		$query->select($params['sub_fields']);
		// 	}]);
		// }
		// $query->whereNull('deleted_at');
		return $query->get()->toArray();
	}

	public function getPersonnelFilesDetail($filesId)
	{
		return $this->entity->with(['department' => function($query)
					{
						$query->select('dept_id', 'dept_name');
					}])
					->find($filesId);
	}

	public function getPersonnelFilesByWhere($where)
	{
		return $this->entity->wheres($where)->first();
	}

	public function getPersonnelFilesIdByUserId($userId, $userName='')
	{
		if(empty($userName)) {
			return $this->entity->select('id')->where('user_id',$userId)->get()->toArray();
		}else{
			return $this->entity->select('id')->where('user_id',$userId)->orWhere('user_name',$userName)->get()->toArray();
		}

	}

	//获取人事档案性别
	public function getPersonnelFileSex($id)
	{
		return $this->entity->select('sex')->where('id',$id)->first();
	}

    public function allUserId()
    {
        $userId = $this->entity->newQuery()
            ->where('user_id', '!=', '')
            ->pluck('user_id')
            ->filter()->unique()->toArray();

        return $userId;
    }

    public function getPersonnelFilesTreeList($params)
	{
		$defaults = array(
			'fields'	=> ['*'],
			'page'		=> 0,
			'limit'		=> config('eoffice.pagesize'),
			'order_by'	=> ['no' => 'asc'],
			'search'	=> [],
		);
		$params = array_merge($defaults, $params);
		foreach ($params['order_by'] as $key => $value){
		    $orders['personnel_files.'.$key] = $value;
        }
		$query = $this->entity
					->select($params['fields']);

		$query = $query->wheres($params['search'])
					->parsePage($params['page'],$params['limit'])
					->orders($orders);
		$query->whereNull('personnel_files.deleted_at');
		$result = $query->get()->toArray();
		if ($result) {
			foreach ($result as $k => &$v) {
				if (isset($v['status'])) {
					$v['status_name'] = mulit_trans_dynamic("user_status.status_name.user_status_" .$v['status']);
				}
			}
		}
		return $result;
	}
	public function getUserCountGroupByDeptId(array $own, array $param, array $deptIds) {
        return $this->handleOrgUser($own, $param, function($own, $param) {
            $query = $this->entity->selectRaw('count(personnel_files.user_id) as count, personnel_files.dept_id');
            return $query;
        }, function($query) use($deptIds) {
            return $query->whereIn('personnel_files.dept_id', $deptIds)->groupBy('personnel_files.dept_id')->get();
        });
	}
	public function handleOrgUser(array $own, array $param, $before, $terminal)
    {
        $query = $before($own, $param);

        if(isset($param['search']) && !empty($param['search'])) {
            if(isset($param['search']['user_id'])){
                $param['search']['personnel_files.user_id'] =  $param['search']['user_id'];
                unset($param['search']['user_id']);
            }
            $query->wheres($param['search']);
        }
        return $terminal($query);
    }
    public function getPersonnelFilesExpire($fileIds, $type)
    {
    	$query = $this->entity->select(['id', 'personnel_files.user_id', 'user_system_info.user_id as uid', 'user_system_info.user_status'])
    	            ->leftjoin('user_system_info', 'personnel_files.user_id', '=', 'user_system_info.user_id');
        if ($type == 'user') {
        	$query = $query->whereIn('personnel_files.user_id', $fileIds);
        } else {
        	$query = $query->whereIn('id', $fileIds);
        }

        return $query->get()->toArray();
    }
    public function multiRemoveDept($userIds, $deptId)
    {
    	return $this->entity->whereIn('user_id', $userIds)->update(['dept_id' => $deptId]);
    }
}
