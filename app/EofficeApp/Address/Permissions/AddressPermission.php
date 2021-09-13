<?php

namespace App\EofficeApp\Address\Permissions;


use App\EofficeApp\Address\Repositories\AddressPersonGroupRepository;
use App\EofficeApp\Address\Repositories\AddressPrivateRepository;
use App\EofficeApp\Address\Repositories\AddressPublicRepository;
use App\EofficeApp\Address\Services\AddressService;

class AddressPermission
{
    const GROUP_NOT_EXIST = ['code' => ['0x004018', 'address']];
    const GROUP_NOT_ALLOWED = ['code' => ['0x004017', 'address']];

    private $personGroupRepository;
    private $service;
    private $publicRepository;
    private $privateRepository;

    public function __construct()
    {
        $this->personGroupRepository = AddressPersonGroupRepository::class;
        $this->publicRepository = AddressPublicRepository::class;
        $this->privateRepository = AddressPrivateRepository::class;
        $this->service = AddressService::class;
    }

    // 复制通讯录权限
    public function copyAddress($own, $data, $urlData)
    {
        $groupId = $urlData['groupId'];
        $addressIdArray = explode(',', $urlData['addressId']);
        // 判断被转移分组是否是本人的
        $group = app($this->personGroupRepository)->entity->select('group_id', 'user_id')->find($groupId);
        if (!$group) {
            return self::GROUP_NOT_EXIST;
        }
        if ($group->user_id != $own['user_id']) {
            return self::GROUP_NOT_ALLOWED;
        }
        // 判断被转移通讯录是否有权限
        $allAllowedIds = app($this->service)->getAllAuthGroupId($own);
        $groups = app($this->publicRepository)->entity->distinct()->select('primary_4')->find($addressIdArray);
        foreach ($groups as $group) {
            if (isset($group->primary_4) && $group->primary_4 && !in_array($group->primary_4, $allAllowedIds)) {
                return false;
            }
        }
        return true;
    }

    // 新建子组
    public function addGroup($own, $data, $urlData)
    {
        $groupType = $data['group_type'] ?? 0;
        //对应菜单权限
        if (!$this->menuHasGroupType($groupType, $own)) {
            return false;
        }
        if ($groupType == 2) {
            // 个人通讯录父级必须为自己拥有的或者顶级
            $parentId = $data['parent_id'] ?? 0;
            if ($parentId && !$this->ownPrivateGroup($parentId, $own)) {
                return false;
            }
        }
        return true;
    }

    //编辑分组基本设置
    public function editGroup($own, $data, $urlData)
    {
        $groupType = $data['group_type'] ?? 0;
        //对应菜单权限
        if (!$this->menuHasGroupType($groupType, $own)) {
            return false;
        }
        if ($groupType == 2) {
            // 个人通讯录父级必须为自己拥有的
            $groupId = $urlData['groupId'];
            if (!$this->ownPrivateGroup($groupId, $own)) {
                return false;
            }
        }
        return true;
    }

    // 分组排序
    public function sortGroup($own, $data, $urlData)
    {
        $groupType = $urlData['groupType'] ?? 0;
        //对应菜单权限
        if (!$this->menuHasGroupType($groupType, $own)) {
            return false;
        }
        if ($groupType == 2) {
            $sortData = $data['sort_data'] ?? [];
            if (!$sortData) {
                return false;
            }
            $ownIds = app($this->personGroupRepository)->getAllOwnGroupIds($own['user_id'])->toArray();
            foreach ($sortData as $value) {
                if (!in_array($value[0], $ownIds)) {
                    return false;
                }
            }
        }
        return true;
    }

    // 删除分组
    public function deleteGroup($own, $data, $urlData)
    {
        //对应菜单权限
        $groupType = $urlData['groupType'] ?? 0;
        if (!$this->menuHasGroupType($groupType, $own)) {
            return false;
        }
        if ($groupType == 2) {
            // 不能删除不是自己的分组
            $groupId = $urlData['groupId'];
            if (!$this->ownPrivateGroup($groupId, $own)) {
                return false;
            }
        }
        return true;
    }

    // 转移分组
    public function migrateGroup($own, $data, $urlData)
    {
        //对应菜单权限
        $groupType = $urlData['groupType'] ?? 0;
        if (!$this->menuHasGroupType($groupType, $own)) {
            return false;
        }
        // 不能转移或转移到不是自己的分组
        if ($groupType == 2) {
            $ownIds = app($this->personGroupRepository)->getAllOwnGroupIds($own['user_id'])->toArray();
            $from = $urlData['fromId'] ?? 0;
            $to = $urlData['toId'] ?? 0;
            if (!in_array($from, $ownIds) || !in_array($to, $ownIds)) {
                return false;
            }
        }
        return true;
    }

    // 分组管理获取基本设置
    public function showManageGroup($own, $data, $urlData)
    {
        //对应菜单权限
        $groupType = $urlData['groupType'] ?? 0;
        if (!$this->menuHasGroupType($groupType, $own)) {
            return false;
        }
        if ($groupType == 2) {
            $groupId = $urlData['groupId'] ?? 0;
            if (!$this->ownPrivateGroup($groupId, $own)) {
                return false;
            }
        }
        return true;
    }

    // 转移通讯录权限
    public function migrateAddress($own, $data, $urlData)
    {
        $tableKey = $urlData['tableKey'] ?? '';
        $addressId = $urlData['addressId'] ? explode(',', $urlData['addressId']) : 0;
        if (!$addressId) {
            return false;
        }
        $groupId = $urlData['groupId'] ?? 0;
        if (!$groupId) {
            return false;
        }
        if ($tableKey == 'addressPrivate') {
            // 判断菜单
            if (!in_array(23, $own['menus']['menu'])) {
                return false;
            }
            // 判断是否拥有转移组,service已做处理
//            if ($this->ownPrivateGroup($groupId, $own)) {
//                return false;
//            }
            // 判断是否存在不是本人的通讯录
            $addresses = app($this->privateRepository)->entity
                ->select('address_id', 'primary_5')
                ->find($addressId);
            $count = $addresses->count();
            if($count != count($addressId)){
                return false;
            }
            foreach ($addresses as $address){
                if($address->primary_5 != $own['user_id']){
                    return false;
                }
            }
        } elseif ($tableKey == 'addressPublic') {
            if (!in_array(67, $own['menus']['menu'])) {
                return false;
            }
            // 被转移通讯录有权限
            $addresses = app($this->publicRepository)->entity->find($addressId);
            $count = $addresses->count();
            if($count != count($addressId)){
                return false;
            }
            $viewGroup = app($this->service)->getAllAuthGroupId($own);
            foreach ($addresses as $address){
                if($address->primary_4 && !in_array($address->primary_4, $viewGroup)){
                    return false;
                }
            }
        } else {
            return false;
        }
        return true;
    }

    // 判断group_type不同时是否有对应的菜单
    public function menuHasGroupType($groupType, $own)
    {
        if (!in_array($groupType, [1, 2])) {
            return false;
        }
        $menu = $own['menus']['menu'] ?? [];
        if ($groupType == 1) {
            return in_array(67, $menu);
        }
        if ($groupType == 2) {
            return in_array(23, $menu);
        }
        return false;
    }

    // 判断是否为本人的组
    public function ownPrivateGroup($groupId, $own)
    {
        $group = app($this->personGroupRepository)->entity->select('group_id', 'user_id')->find($groupId);
        if (!$group) {
            return false;
        }
        if ($group->user_id != $own['user_id']) {
            return false;
        }
        return true;
    }



}
