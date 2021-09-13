<?php
namespace App\EofficeApp\Address\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Address\Entities\AddressPublicGroupEntity;
use DB;
/**
 * @公共通讯录组资源库类
 *
 * @author 李志军
 */
class AddressPublicGroupRepository extends BaseRepository
{
	private $primaryKey = 'group_id';
	private $table		= 'address_public_group';
	/**
	 * @注册公共通讯录组实体
	 * @param \App\EofficeApp\Entities\AddressPublicGroupEntity $entity
	 */
	public function __construct(AddressPublicGroupEntity $entity) {
		parent::__construct($entity);
	}
	/**
	 * @获取有权限的通讯录组ID
	 * @return array 通讯录组ID列表
	 */
	public function getAuthGroupId($own, $field=['group_id'])
	{
		return $this->entity
			->select($field)
			->where(function ($query) use($own){
				$query->where('priv_scope',1)
					// ->orWhere('user_id', $own['user_id'])
					->orWhere(function ($query) use ($own){
						$query->orWhereRaw('find_in_set(?,user_priv)' , [$own['user_id']])->orWhereRaw('find_in_set(?,dept_priv)' , $own['dept_id']);
						foreach($own['role_id'] as $roleId){
                            $query->orWhereRaw('find_in_set(?,role_priv)' , [$roleId]);
                        }
					});
			})->get();
	}
	public function getChildrenGroup($parentId,$fields = ['*'])
	{
		return $this->entity
			->select($fields)
            ->where('parent_id',$parentId)->get();
	}
	/**
	 * @获取有权限的子通讯录组ID
	 * @param type $parentId
	 * @return array 通讯录组ID列表
	 */
	public function getAuthChildrenId($parentId,$own)
	{
		return $this->entity
			->select(['group_id','has_children'])
			->where('parent_id',$parentId)
			->where(function ($query) use($own){
				$query->where('priv_scope',1)
					->orWhere(function ($query) use ($own){
						$query->orWhereRaw('find_in_set(?,user_priv)' , [$own['user_id']])->orWhereRaw('find_in_set(?,dept_priv)' , $own['dept_id']);
                        foreach($own['role_id'] as $roleId){
                            $query->orWhereRaw('find_in_set(?,role_priv)' , [$roleId]);
                        }
					});
			})->get();
	}

	public function getViewChildrenId($param, $own)
	{
    	$query = $this->entity->select(['group_id','group_name','has_children']);
        if(isset($param['search'])){
            $query = $query->wheres($param['search']);
        }
        $query =  $query->where(function ($query) use($own){
            $query->where('priv_scope',1)
                ->orWhere(function ($query) use ($own){
                    $query->orWhereRaw('find_in_set(?,user_priv)' , [$own['user_id']])->orWhereRaw('find_in_set(?,dept_priv)' , [$own['dept_id']]);
                    foreach($own['role_id'] as $roleId){
                        $query->orWhereRaw('find_in_set(?,role_priv)' , [$roleId]);
                    }
                });
        });
        if(isset($param['page']) && isset($param['limit'])){
            $query->parsePage($param['page'], $param['limit']);
        }

        return $query->get();
	}

	/**
	 * @获取资通讯录组
	 * @param type $fields
	 * @param type $parentId
	 * @return array 通讯录组列表
	 */
	public function getAllChidlren($fields, $parentId) {
		if(!empty($fields)) {
			$query = $this->entity->select($fields);
		} else {
			$query = $this->entity->select(['address_public_group.*','user.user_name']);
		}

		return $query->leftJoin('user', 'user.user_id', '=', $this->table . '.user_id')
			->where('address_public_group.parent_id',$parentId)
			->orderBy('group_sort','asc')->orderBy('group_id','asc')->get();
	}
	public function listGroup($param)
	{
		$fields = isset($param['fields']) ? $param['fields'] : ['*'];

		$query = $this->entity->select($fields);

		if(isset($param['search']) && !empty($param['search'])) {
			$query->wheres($param['search']);
		}

		$query = $query->orderBy('group_sort','asc')->orderBy('group_id','asc');

		if(isset($param['page']) && isset($param['limit'])){
			$query->parsePage($param['page'], $param['limit']);
		}

		return $query->get();
	}
	/**
	 * @判断该组是否可以显示
	 * @param type $groupId
	 * @return 数量 > 0 可以显示
	 */
	public function isViewGroup($groupId,$own) {
		return $this->entity
			->whereRaw('find_in_set(?,arr_parent_id)' , [$groupId])
			->where(function ($query) use($own){
				$query->where('priv_scope',1)
					// ->orWhere('address_public_group.user_id', $own['user_id'])
					->orWhere(function ($query) use($own) {
						$query->orWhereRaw('find_in_set(?,user_priv)' , [$own['user_id']])->orWhereRaw('find_in_set(?,dept_priv)' , [$own['dept_id']]);
                        foreach($own['role_id'] as $roleId){
                            $query->orWhereRaw('find_in_set(?,role_priv)' , [$roleId]);
                        }
					});
			})->count();
	}
	// 判断改通讯录组是否有权限
	public function isAuthGroup($groupId, $own) {
		return $this->entity
			->where('group_id', $groupId)
			->where(function ($query) use($own){
				$query->where('priv_scope',1)
					->orWhere(function ($query) use($own) {
						$query->orWhereRaw('find_in_set(?,user_priv)' , [$own['user_id']])->orWhereRaw('find_in_set(?,dept_priv)' , [$own['dept_id']]);
                        foreach($own['role_id'] as $roleId){
                            $query->orWhereRaw('find_in_set(?,role_priv)' , [$roleId]);
                        }
					});
			})->count();
	}
	/**
	 * @添加通讯录组
	 * @param type $data
	 * @return id
	 */
	public function addGroup($data)
	{
		return $this->entity->create($data);
	}
	/**
	 * @编辑通讯录组
	 * @param type $data
	 * @param type $groupId
	 * @return boolean
	 */
	public function editGroup($data, $groupId)
    {
        return  $this->entity->where($this->primaryKey,$groupId)->update($data);
    }
	/**
	 * 获取通讯录组详情
	 * @param type $groupId
	 * @param type $fields
	 * @param type $detail
	 * @return object 通讯录组详情
	 */
	public function showGroup($groupId, $fields = [],$detail=false,$own = [])
	{
		if(!empty($fields)) {
			$query = $this->entity->select($fields);
		} else {
			$query = $this->entity->select(['address_public_group.*','user.user_name']);
		}

		$query = $query->leftJoin('user', 'user.user_id', '=', $this->table . '.user_id')
			->where('address_public_group.group_id',$groupId);

		if($detail) {
			$query = $query->where(function($query) use($own){
						$query->where('priv_scope',1)
							->orWhere($this->table . '.user_id',$own['user_id'])
							->orWhere(function ($query) use($own){
                                $query->orWhereRaw('find_in_set(?,user_priv)' , [$own['user_id']])->orWhereRaw('find_in_set(?,dept_priv)' , [$own['dept_id']]);
                                foreach($own['role_id'] as $roleId){
                                    $query->orWhereRaw('find_in_set(?,role_priv)' , [$roleId]);
                                }
							});
					});
		}

		$groupInfo = $query->first();
		if($groupInfo) {
			$groupInfo->user_priv = $groupInfo->user_priv == '' ? [] : explode(',', $groupInfo->user_priv);
			$groupInfo->role_priv = $groupInfo->role_priv == '' ? [] : $this->StringArrayInteger(explode(',', $groupInfo->role_priv));
			$groupInfo->dept_priv = $groupInfo->dept_priv == '' ? [] : $this->StringArrayInteger(explode(',', $groupInfo->dept_priv));
		}
		return $groupInfo;
	}
	private function StringArrayInteger($data)
	{
		for($i = 0; $i < count($data); $i++){

			$data[$i] = intval($data[$i]);
		}

		return $data;
	}
	/**
	 * @获取父级通讯录组
	 * @param type $groupId
	 * @param type $fields
	 * @return object 通讯录组
	 */
	public function getParentGroup($groupId,$fields = [])
	{
		if(!empty($fields)) {
			$query = $this->entity->select($fields);
		} else {
			$query = $this->entity->select(['address_public_group.*','user.user_name']);
		}

		return $query->leftJoin('user', 'user.user_id', '=', $this->table . '.user_id')
			->where('group_id',$groupId)
			->first();
	}
	/**
	 * @删除通讯录组
	 * @param type $groupId
	 * @return boolean
	 */
	public function deleteGroup($groupId)
	{
		return  $this->entity->destroy($groupId);
	}
	/**
	 * @通讯录组排序
	 * @param type $data
	 * @return boolean
	 */
	public function sortGroup($data)
	{
		foreach ($data as $value) {
			$group = $this->entity->find($value[0]);

			$group->group_sort = $value[1];

			if(!$group->save()) {
				return false;
			}
		}
		return true;
	}
	/**
	 * @获取子通讯录组个数
	 * @param type $groupId
	 * @return 数量
	 */
	public function countChildrenGroup($groupId)
	{
		return $this->entity->where('parent_id',$groupId)->count();
	}

	/**
	 * @更新子分组
	 * @param type $newArrParentId
	 * @param type $oldArrParentId
	 * @param type $deptId
	 * @return boolean
	 */
    public function updateTreeChildren($oldArrParentId, $newArrParentId,$groupId)
    {
        return DB::update("update " . $this->entity->table . " set arr_parent_id = replace(arr_parent_id,'$oldArrParentId','$newArrParentId') where find_in_set('$groupId',arr_parent_id)");
	}

    //获取有权限的group，不分层
    public function getAuthFimalyGroupId($parentId, $own, $params = [])
    {
        $fields = $params['fields'] ?? ['group_id'];
        $query =  $this->entity
            ->select($fields)
            ->whereRaw('find_in_set(?,arr_parent_id)' , [$parentId])
            ->where(function ($query) use($own){
                $query->where('priv_scope',1)
//                    ->orWhere('user_id', $own['user_id'])
                    ->orWhere(function ($query) use ($own){
                        $query->orWhereRaw('find_in_set(?,user_priv)' , [$own['user_id']])->orWhereRaw('find_in_set(?,dept_priv)' , [$own['dept_id']]);
                        foreach($own['role_id'] as $roleId){
                            $query->orWhereRaw('find_in_set(?,role_priv)' , [$roleId]);
                        }
                    });
            });
        if(isset($params['search']) && !empty($params['search'])){
            $query = $query->wheres($params['search']);
        }
        return $query->orderBy('group_sort', 'asc')
                    ->orderBy('created_at', 'asc')
                    ->get();
    }

    // 获取所有group,不分层
    public function getAllGroupInFlat($fields=['group_id'])
    {
        return $this->entity
            ->select($fields)
            ->orderBy('group_sort', 'asc')
            ->orderBy('group_id', 'asc')
            ->get();
    }

    public function groupNameExists($user_id,$parentId,$groupName,$groupId = false)
    {

        $query = $this->entity->where('parent_id',$parentId)->where('group_name',$groupName);
        if($groupId){
            $query = $query->where('group_id','!=',$groupId);
        }
        return $query->count();
    }
}
