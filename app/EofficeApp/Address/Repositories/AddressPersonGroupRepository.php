<?php

namespace App\EofficeApp\Address\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Address\Entities\AddressPersonGroupEntity;

/**
 * @个人通讯录组资源库类
 *
 * @author 李志军
 */
class AddressPersonGroupRepository extends BaseRepository
{
    private $primaryKey = 'group_id';

    private $table = 'address_person_group';

    /**
     * @注册个人通讯录实体
     * @param \App\EofficeApp\Entities\AddressPersonGroupEntity $entity
     */
    public function __construct(AddressPersonGroupEntity $entity)
    {
        parent::__construct($entity);
    }

    /**
     * @获取子通讯录组
     * @param type $fields
     * @param type $parentId
     * @return array | 通讯录组列表
     */
    public function getChildren($fields, $parentId, $own)
    {
        if (!empty($fields)) {
            $query = $this->entity->select($fields);
        } else {
            $query = $this->entity->select([$this->table . '.*', 'user.user_name']);
        }
        return $query->leftJoin('user', 'user.user_id', '=', $this->table . '.user_id')
            ->where('parent_id', $parentId)
            ->where($this->table . '.user_id', $own['user_id'])
            ->orderBy('group_sort', 'asc')
            ->orderBy('group_id', 'asc')
            ->get();
    }

    public function listGroup($param)
    {
        $fields = isset($param['fields']) ? $param['fields'] : ['*'];

        $query = $this->entity->select($fields);

        if (isset($param['search']) && !empty($param['search'])) {
            $query->wheres($param['search']);
        }

        $query = $query->orderBy('group_sort', 'asc')->orderBy('group_id', 'asc');

        if (isset($param['page']) && isset($param['limit'])) {
            $query->parsePage($param['page'], $param['limit']);
        }

        return $query->get();
    }

    /**
     * @新建通讯录组
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
        return $this->entity->where($this->primaryKey, $groupId)->update($data);
    }

    /**
     * @获取通讯录组详情
     * @param type $groupId
     * @param type $fields
     * @return object | 通讯录组详情
     */
    public function showGroup($groupId, $fields = [])
    {
        if (!empty($fields)) {
            $query = $this->entity->select($fields);
        } else {
            $query = $this->entity->select([$this->table . '.*', 'user.user_name']);
        }

        return $query->leftJoin('user', 'user.user_id', '=', $this->table . '.user_id')
            ->where('group_id', $groupId)
            ->first();
    }

    /**
     * @删除通讯录组
     * @param type $groupId
     * @return boolean
     */
    public function deleteGroup($groupId)
    {
        return $this->entity->destroy($groupId);
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

            if (!$group->save()) {
                return false;
            }
        }

        return true;
    }

    /**
     * @获取子通讯录组个数
     * @param type $groupId
     * @return type
     */
    public function countChildrenGroup($groupId)
    {
        return $this->entity->where('parent_id', $groupId)->count();
    }

    public function getChildrenGroup($parentId, $userId, $fields = ['*'])
    {
        return $this->entity
            ->select($fields)
            ->where('user_id', $userId)
            ->where('parent_id', $parentId)->get();
    }

    public function getChildrenGroupId($parentId, $userId)
    {
        return $this->entity
            ->select(['group_id', 'group_name', 'has_children'])
            ->where('user_id', $userId)
            ->where('parent_id', $parentId)->get();
    }

    public function groupNameExists($user_id, $parentId, $groupName, $groupId = false)
    {
        $query = $this->entity->where('parent_id', $parentId)->where('group_name', $groupName)->where("user_id", $user_id);
        if ($groupId) {
            $query = $query->where('group_id', '!=', $groupId);
        }

        return $query->count();
    }

    // 获取用户所有拥有的组
    public function getAllOwnGroupIds($userId)
    {
        $query = $this->entity->where('user_id', $userId)->pluck('group_id');
        return $query;
    }
}
