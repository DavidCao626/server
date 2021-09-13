<?php

namespace App\EofficeApp\Role\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Role\Entities\RoleEntity;
use DB;

/**
 * 角色Repository类:提供角色表操作资源
 *
 * @author qishaobo
 *
 * @since  2015-10-20 创建
 */
class RoleRepository extends BaseRepository
{
    public function __construct(RoleEntity $entity)
    {
        parent::__construct($entity);
    }

	/**
	 * 获取角色列表
	 *
	 * @param  array $param
	 *
	 * @return array
	 *
	 * @author qishaobo
	 *
	 * @since  2015-10-20 创建
	 */
	public function getRoleList(array $param = [])
	{
		$default = [
			'fields'   => ['role_id', 'role_name', 'role_no','role_name_zm','role_name_py'],
			'page'     => 0,
			'limit'	   => config('eoffice.pagesize'),
			'search'   => [],
			'order_by' => ['role_no' => 'asc', 'role_id' => 'asc'],
		];
        if (empty($param['order_by'])) {
            unset($param['order_by']);
        }
        if (isset($param['search']['user_accounts'])) {
        	unset($param['search']['user_accounts']);
        }
		$param = array_merge($default, $param);
		$param['order_by'] = array_merge($param['order_by'], ['role_id' => 'asc']);
		return $this->entity
			->select($param['fields'])
			->with(['hasManyRole' => function($query) {
				$query->select(['user_role.*'])->leftJoin('user_system_info', 'user_role.user_id', '=', 'user_system_info.user_id')->whereNotIn("user_system_info.user_status",  [0, 2]);
			}])
			->multiWheres($param['search'])
			->orders($param['order_by'])
			->parsePage($param['page'], $param['limit'])
			->get()
			->toArray();
	}

    public function getRoleTotal(array $param = [])
    {
        $default = [
            'search'   => [],
        ];
        if (isset($param['search']['user_accounts'])) {
            unset($param['search']['user_accounts']);
        }
        $param = array_merge($default, $param);

        return $this->entity
            ->multiWheres($param['search'])
            ->count();
    }

	/**
	 * 获取所有角色
	 *
	 * @param array $params
	 *
	 * @return array
	 *
	 * @author lizhijun
	 *
	 * @since 2015-11-25
	 */
	public function getAllRoles($params)
	{
		$fields = isset($params['fields']) ? $params['fields'] : ['*'];

		$query = $this->entity->select($fields);

		if(isset($params['search']) && !empty($params['search'])) {
			$query->wheres($params['search']);
		}

		return $query->orderBy('role_no', 'asc')->get();
	}
	/**
	 * 条件查询角色
	 *
	 * @param  array $where
	 * @param  int $role_id
	 *
	 * @return array
	 *
	 * @author qishaobo
	 *
	 * @since  2015-10-20 创建
	 */
	public function getRole($where, $roleId = 0)
	{
		if ($roleId > 0) {
			return $this->entity->where($where)->where('role_id', '!=', $roleId)->get();
		}
		return $this->entity->where($where)->get();
	}

    /**
    * 获取角色名称
    *
    * @param  array $roleId 角色id
    *
    * @return array
    *
    * @author qishaobo
    *
    * @since  2015-10-21
    */
	public function getRolesNameByIds(array $roleId)
	{
		return $this->entity->whereIn('role_id', $roleId)->pluck('role_name')->toArray();
	}
	/**
	 * 获取角色属性值
	 *
	 * @param array $roleId
	 * @param array $fields
	 *
	 * @return array
	 *
	 * @author lizhijun
	 *
	 * @since 2015-11-06
	 */
	public function getRoleAttrByIds(array $roleId, array $fields = ['*'])
	{
		return $this->entity->select($fields)->whereIn('role_id', $roleId)->get();
	}
	/**
	 * 获取角色所有单个主表信息
	 *
	 * @param array $fields
	 *
	 * @return array 角色所有单个主表信息
	 *
	 * @author lizhijun
	 *
	 * @since 2015-11-06
	 */
	public function getAllSimpltRoles(array $fields = ['*'])
	{
		return $this->entity->select($fields)->orderBy('role_no', 'asc')->get();
	}

    /**
     * 获取角色ID集合中的最大角色级别
     *
     * @author 缪晨晨
     *
     * @param  array or string $data [description]
     *
     * @since  2017-06-07 创建
     *
     * @return string 返回最大角色级别(角色级别数字越小的级别越大)
     */
    public function getMaxRoleNoFromData($where)
    {
    	if(empty($where)) {
    		$result = $this->entity->min('role_no');
    	}else{
    		$result = $this->entity->whereIn('role_id', $where)->min('role_no');
    	}
    	return $result;
    }

    /**
     * 更新所有用户的最大角色级别
     *
     * @author 缪晨晨
     *
     * @param
     *
     * @since  2017-11-08 创建
     *
     * @return
     */
    public function updateAllUserMaxRoleNo()
    {
        DB::statement("UPDATE user_system_info SET max_role_no = (
						SELECT role_no FROM (
						SELECT * FROM (SELECT user_role.user_id,user_role.role_id,role.role_no FROM user_role LEFT JOIN role ON user_role.role_id = role.role_id) tb
						WHERE tb.role_no = (SELECT MIN(ta.role_no) FROM (SELECT user_role.user_id,user_role.role_id,role.role_no FROM user_role LEFT JOIN role ON user_role.role_id = role.role_id) ta WHERE tb.user_id = ta.user_id)
						GROUP BY tb.user_id)
						tc WHERE user_system_info.user_id = tc.user_id)"
		);
    }
    /**
     * 获取某个部门下所有角色
     */
    public function getDeptRole($dept)
    {
        $query = $this->entity->leftJoin('user_role', 'user_role.role_id', '=', 'role.role_id')
        ->leftJoin('user_system_info', 'user_role.user_id', '=', 'user_system_info.user_id')
        ->leftJoin('department', 'department.dept_id', '=', 'user_system_info.dept_id')
        ->select('role.role_id','role.role_name')
        ->where('department.dept_id',$dept)->whereNotIn('user_system_info.user_status',['0','2'])->groupBy('role.role_id');
        return $query->get();
    }

    public function getOneRole($wheres, $orders = ['role_no' => 'desc', 'role_id' => 'desc']) {
        return $this->entity->wheres($wheres)->orders($orders)->first();
    }

    public function getRoleIdByName($roleName) {
    	if (is_array($roleName)) {
    		return $this->entity->select(['role_id'])->whereIn('role_name', $roleName)->get()->toArray();
    	}
    	return $this->entity->select(['role_id'])->where('role_name', $roleName)->get()->toArray();
    }
    public function getAllRoleIds()
    {
    	return $this->entity->pluck('role_id')->toArray();
    }
}
