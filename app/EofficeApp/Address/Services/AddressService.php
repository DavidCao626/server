<?php
namespace App\EofficeApp\Address\Services;

use App\EofficeApp\Address\Repositories\AddressPrivateRepository;
use App\EofficeApp\Attachment\Services\AttachmentService;
use App\EofficeApp\Base\BaseService;
use Illuminate\Support\Arr;
use DB;
use Schema;
use App\EofficeApp\ImportExport\Facades\Export;
/**
 * @通讯录服务类
 *
 * @author  李志军
 */
class AddressService extends BaseService
{
    private $addressRepository; //通讯录资源库对象
    private $addressPublicGroupRepository; //公共通讯录组资源库对象
    private $addressPersonGroupRepository; //个人通讯录组资源库对象
    private $addressPublicRepository; //公共通讯录资源库对象
    private $addressPrivateRepository; //个人通讯录资源库对象
    private $attachmentService;
    private $formModelingService;
    private $userService;
    const EMPTY_CREATOR = ['code' => ['0x004019', 'address']];


    /**
     * @注册各个资源库对象，服务对象.
     */
    public function __construct()
    {
        parent::__construct();

        $this->addressRepository = 'App\EofficeApp\Address\Repositories\AddressRepository';
        $this->addressPublicGroupRepository = 'App\EofficeApp\Address\Repositories\AddressPublicGroupRepository';
        $this->addressPersonGroupRepository = 'App\EofficeApp\Address\Repositories\AddressPersonGroupRepository';
        $this->addressPublicRepository = 'App\EofficeApp\Address\Repositories\AddressPublicRepository';
        $this->addressPrivateRepository = 'App\EofficeApp\Address\Repositories\AddressPrivateRepository';
        $this->attachmentService = 'App\EofficeApp\Attachment\Services\AttachmentService';
        $this->formModelingService = 'App\EofficeApp\FormModeling\Services\FormModelingService';
        $this->userService = 'App\EofficeApp\User\Services\UserService';
    }
    /**
     * @获取子通讯录组
     * @param type $groupType
     * @param type $parentId
     * @param type $fields
     * @return json 通讯录组列表
     */
    public function getViewChildren($groupType, $parentId, $fields, $own)
    {
        if (!in_array($groupType, [1, 2])) {
            return ['code' => ['0x004002', 'address']];
        }

        if (!empty($fields)) {
            $fields = array_unique(array_merge(['group_id', 'parent_id', 'group_name'], $fields));
        }

        return $groupType == 1
        ? $this->getAllViewGroups($fields, $parentId, $own)
        : app($this->addressPersonGroupRepository)->getChildren($fields, $parentId, $own);
    }
    public function getViewChildrenBySearch($groupType, $param, $own)
    {
        $param = $this->parseParams($param);
        if (!in_array($groupType, [1, 2])) {
            return ['code' => ['0x004002', 'address']];
        }

        $fields = ['group_id', 'group_name', 'has_children'];

        if (!empty($param['fields'])) {
            $fields = $param['fields'];
        }

        if ($groupType == 1) {
            $data = app($this->addressPublicGroupRepository)->getViewChildrenId($param, $own);
            if (count($data) > 0) {
                return ['list' => $data, 'total' => count($data)];
            }

            return [];
        } else {
            // 个人通讯录转移选择器改后不走这个api
            return app($this->addressPersonGroupRepository)->getChildren($fields, 0, $own);
        }
    }
    public function getAddressFamily($groupType, $parentId, $fields, $filter, $own)
    {
        if (!in_array($groupType, [1, 2])) {
            return ['code' => ['0x004002', 'address']];
        }

        $tableKey = $groupType == 1 ? 'addressPublic' : 'addressPrivate';

        if (!empty($fields)) {
            $fields = array_unique(array_merge(['group_id', 'parent_id', 'group_name'], $fields));
        }
        $familay = $groupType == 1 ? $this->getAllViewGroups($fields, $parentId, $own) : app($this->addressPersonGroupRepository)->getChildren($fields, $parentId, $own);
        $returnFamily = app($this->{$tableKey . 'Repository'})->getAddressByGroupId($groupType, $parentId, $filter, $own);
        if (count($familay) > 0) {
            
            foreach ($familay as $group) {
                if (!$group->has_children) {
                    if (app($this->{$tableKey . 'Repository'})->isHasAddressByGroup($groupType, $group->group_id)) {
                        $group->has_children = 1;
                    }
                }

                $returnFamily[] = $group;
            }
        }

        return $returnFamily;
    }
    public function listGroup($groupType, $param, $own)
    {
        if (!in_array($groupType, [1, 2])) {
            return ['code' => ['0x004002', 'address']];
        }
        $param = $this->parseParams($param);

        if ($groupType == 1) {
            return $this->response(app($this->addressPublicGroupRepository), 'getTotal', 'listGroup', $param);
        } else {
            $param['search']['user_id'] = [$own['user_id']];
            return $this->response(app($this->addressPersonGroupRepository), 'getTotal', 'listGroup', $param);
        }
    }
    public function getChildren($groupType, $parentId, $fields, $own)
    {
        if (!in_array($groupType, [1, 2])) {
            return ['code' => ['0x004002', 'address']];
        }

        if (!empty($fields)) {
            $fields = array_unique(array_merge(['group_id', 'parent_id', 'group_name'], $fields));
        }

        return $groupType == 1
        ? app($this->addressPublicGroupRepository)->getAllChidlren($fields, $parentId)
        : app($this->addressPersonGroupRepository)->getChildren($fields, $parentId, $own);
    }
    /**
     * @添加通讯录组
     * @param type $data
     * @param type $groupType
     * @return $groupId
     */
    public function addGroup($data, $groupType, $currentUserId)
    {
        if (!in_array($groupType, [1, 2])) {
            return ['code' => ['0x004002', 'address']];
        }

        $commonData = [
            'group_name' => $data['group_name'],
            'parent_id' => $this->defaultValue('parent_id', $data, 0),
            'group_sort' => $this->defaultValue('group_sort', $data, 0),
            'user_id' => $currentUserId,
            'has_children' => 0,
        ];
        $a = $this->groupNameExists($currentUserId, $commonData['parent_id'], $commonData['group_name'], $groupType);
        if ($this->groupNameExists($currentUserId, $commonData['parent_id'], $commonData['group_name'], $groupType)) {
            return ['code' => ['0x004015', 'address']];
        }
        if ($groupType == 1) {
            $otherData = [
                'priv_scope' => $this->defaultValue('priv_scope', $data, 1),
                'user_priv' => implode(',', $this->defaultValue('user_priv', $data, [])),
                'role_priv' => implode(',', $this->defaultValue('role_priv', $data, [])),
                'dept_priv' => implode(',', $this->defaultValue('dept_priv', $data, [])),
                'allow_extends' => $this->defaultValue('allow_extends', $data, 0)
            ];
            if ($commonData['parent_id'] != 0) {
                $parentGroup = app($this->addressPublicGroupRepository)->showGroup($commonData['parent_id'], ['allow_extends', 'priv_scope', 'user_priv', 'role_priv', 'dept_priv']);
                if ($parentGroup->allow_extends == 1) {
                    $otherData = [
                        'priv_scope' => $parentGroup->priv_scope,
                        'user_priv' => implode(',', $parentGroup->user_priv),
                        'role_priv' => implode(',', $parentGroup->role_priv),
                        'dept_priv' => implode(',', $parentGroup->dept_priv),
                        'allow_extends' => 0,
                    ];
                }
            }
        } else {
            $otherData = ['undefined_group' => 0];
        }

        $addressData = array_merge($commonData, $otherData);

        $addressGroupRepository = $this->getAddressGroupRepository($groupType);

        if ($groupType == 1) {
            $addressData['arr_parent_id'] = $this->getParentArrId($addressData['parent_id']);
        }

        if (!$result = $addressGroupRepository->addGroup($addressData)) {
            return ['code' => ['0x000003', 'common']];
        }
        $this->afterAddGroup($addressGroupRepository, $addressData['parent_id']);

        return ['group_id' => $result->group_id];
    }
    private function groupNameExists($user_id, $parentId, $groupName, $groupType, $groupId = false)
    {

        return $groupType == 1
        ? app($this->addressPublicGroupRepository)->groupNameExists($user_id, $parentId, $groupName, $groupId)
        : app($this->addressPersonGroupRepository)->groupNameExists($user_id, $parentId, $groupName, $groupId);
    }
    /**
     * @编辑通讯录组
     * @param type $data
     * @param type $groupId
     * @param type $groupType
     * @return json 正确与否信息
     */
    public function editGroup($data, $groupId, $groupType, $user_id)
    {
        if ($groupId == 0) {
            return ['code' => ['0x004003', 'address']];
        }

        if (!in_array($groupType, [1, 2])) {
            return ['code' => ['0x004002', 'address']];
        }

        $commonData = [
            'group_name' => $data['group_name'],
            'parent_id' => $this->defaultValue('parent_id', $data, 0),
            'group_sort' => $this->defaultValue('group_sort', $data, 0),
        ];
        if ($this->groupNameExists($user_id, $commonData['parent_id'], $commonData['group_name'], $groupType, $groupId)) {
            return ['code' => ['0x004015', 'address']];
        }
        $otherData = [];

        if ($groupType == 1) {
            $otherData = [
                'priv_scope' => $this->defaultValue('priv_scope', $data, 1),
                'user_priv' => implode(',', $this->defaultValue('user_priv', $data, [])),
                'role_priv' => implode(',', $this->defaultValue('role_priv', $data, [])),
                'dept_priv' => implode(',', $this->defaultValue('dept_priv', $data, [])),
                'allow_extends' => $this->defaultValue('allow_extends', $data, 0)
            ];
        }
        $addressData = array_merge($commonData, $otherData);

        $addressGroupRepository = $this->getAddressGroupRepository($groupType);

        $oldGroup = $addressGroupRepository->showGroup($groupId);

        if ($groupType == 2 && $addressData['parent_id'] && $oldGroup->parent_id != $addressData['parent_id']) {
            $newParentGroup = $addressGroupRepository->showGroup($addressData['parent_id']);
            if ($newParentGroup->user_id != own('user_id')) {
                return ['code' => ['0x000006', 'common']];
            }
        }
        if ($groupType == 1 && $oldGroup->parent_id != $addressData['parent_id']) {
            $addressData['arr_parent_id'] = $this->getParentArrId($addressData['parent_id']);
        }
        $resultStatus = $addressGroupRepository->editGroup($addressData, $groupId);
        // if (!$addressGroupRepository->editGroup($addressData, $groupId)) {
        //     return ['code' => ['0x000003', 'common']];
        // }

        if (!isset($addressData['arr_parent_id'])) {
            $addressData['arr_parent_id'] = '';
        }

        $this->afterEditGroup($addressGroupRepository, $oldGroup->parent_id, $addressData['parent_id'], $groupType, $groupId, $oldGroup->arr_parent_id, $addressData['arr_parent_id']);

        return $resultStatus;

    }
    /**
     * @获取通讯录组详情
     * @param type $groupId
     * @param type $groupType
     * @return object 通讯录组详情
     */
    public function showGroup($groupId, $groupType, $own)
    {
        if (!in_array($groupType, [1, 2])) {
            return ['code' => ['0x004002', 'address']];
        }

        if ($groupId == 0) {
            return ['code' => ['0x004003', 'address']];
        }
        return $groupType == 1
        ? app($this->addressPublicGroupRepository)->showGroup($groupId, [], true, $own)
        : app($this->addressPersonGroupRepository)->showGroup($groupId);
    }
    public function showManageGroup($groupId, $groupType, $own)
    {
        if (!in_array($groupType, [1, 2])) {
            return ['code' => ['0x004002', 'address']];
        }

        if ($groupId == 0) {
            return ['code' => ['0x004003', 'address']];
        }
        return $groupType == 1
        ? app($this->addressPublicGroupRepository)->showGroup($groupId, [], false, $own)
        : app($this->addressPersonGroupRepository)->showGroup($groupId);
    }
    public function migrateGroup($groupType, $fromId, $toId)
    {
        if (!in_array($groupType, [1, 2])) {
            return ['code' => ['0x004002', 'address']];
        }

        if ($fromId == 0) {
            return ['code' => ['0x004008', 'address']];
        }
        $addressGroupRepository = $this->getAddressGroupRepository($groupType);
        // 获取源通讯录组
        $oldGroup = $addressGroupRepository->showGroup($fromId);

        if ($groupType == 1 && $oldGroup->parent_id != $toId) {
            $addressData['arr_parent_id'] = $this->getParentArrId($toId);
            $parentGroupArray = explode(',', $addressData['arr_parent_id']);
        } else {
            // 获取该通讯组所有上级id集合
            $parentGroupArray = $this->getParentGroupId($toId);
        }

        if (in_array($fromId, $parentGroupArray)) {
            return ['code' => ['0x004016', 'address']];
        }

        $addressData['parent_id'] = $toId;

        if (!$addressGroupRepository->editGroup($addressData, $fromId)) {
            return ['code' => ['0x000003', 'common']];
        }
        if (!isset($addressData['arr_parent_id'])) {
            $addressData['arr_parent_id'] = '';
        }
        $this->afterEditGroup($addressGroupRepository, $oldGroup->parent_id, $addressData['parent_id'], $groupType, $fromId, $oldGroup->arr_parent_id, $addressData['arr_parent_id']);

        return true;
    }
    // 递归获取个人通讯录组上级id
    public function getParentGroupId($groupId)
    {
        static $array = [];

        if ($groupId !== 0) {
            $data = app($this->addressPersonGroupRepository)->getDetail($groupId);
            $parentId = $data->parent_id;
            $array[] = $parentId;
            $this->getParentGroupId($parentId);
        }

        return $array;
    }
    /**
     * @删除通讯录组
     * @param type $groupId
     * @param type $groupType
     * @return boolean
     */
    public function deleteGroup($groupId, $groupType)
    {
        if (!in_array($groupType, [1, 2])) {
            return ['code' => ['0x004002', 'address']];
        }

        $repository = $groupType == 1 ? 'addressPublicRepository' : 'addressPrivateRepository';

        if ($groupId == 0) {
            return ['code' => ['0x004003', 'address']];
        }

        $addressGroupRepository = $this->getAddressGroupRepository($groupType);

        if ($addressGroupRepository->countChildrenGroup($groupId) > 0) {
            return ['code' => ['0x004005', 'address']];
        }

        if (app($this->{$repository})->isHasAddressByGroup($groupType, $groupId)) {
            return ['code' => ['0x004013', 'address']];
        }

        $oldGroup = $addressGroupRepository->showGroup($groupId);

        if (!$addressGroupRepository->deleteGroup($groupId)) {
            return ['code' => ['0x000003', 'common']];
        }

        $this->afterDeleteGroup($addressGroupRepository, $oldGroup->parent_id);

        return true;
    }
    /**
     * @通讯录组排序
     * @param type $data
     * @param type $groupType
     * @return boolean
     */
    public function sortGroup($data, $groupType)
    {
        if (!in_array($groupType, [1, 2])) {
            return ['code' => ['0x004002', 'address']];
        }

        if (empty($data)) {
            return ['code' => ['0x004004', 'address']];
        }

        $addressGroupRepository = $this->getAddressGroupRepository($groupType);

        $addressGroupRepository->sortGroup($data);

        return true;
    }
    /**
     * @获取通讯录列表
     * @param type $param
     * @param type $groupType
     * @return array | 获取通讯录列表
     */
    public function listAddress($param, $groupType, $own, $pages = true, $recursion = true)
    {
        if (!in_array($groupType, [1, 2])) {
            return ['code' => ['0x004002', 'address']];
        }

        $tableKey = $groupType == 1 ? 'address_public' : 'address_private';

        $param = $this->parseParams($param);

        $response = $this->defaultValue('response', $param, 'both');

//        if (!isset($param['order_by']) || (isset($param['order_by']) && empty($param['order_by']))) {
//            $param['order_by'] = ['primary_7' => 'asc', 'created_at' => 'desc'];
//        }

        if ($groupType == 1) {
            $allGroupIds = app($this->addressPublicGroupRepository)->listGroup(['fields' => ['group_id']])->pluck('group_id')->toArray();
            $groupId = $this->getAllAuthGroupId($own);
            $noAuth = array_diff($allGroupIds, $groupId);
            if (!isset($param['search'])) {
                $param['search'] = [];
            }

            if (empty($groupId)) {
                $param['search']['primary_4'] = [$noAuth, 'not_in'];
            } else {
                //按通讯录组筛选，获取该组下的所有子孙组id
                if (isset($param['search']['primary_4'])) {
                    $searchParam = $param['search']['primary_4'];
                    if (is_array($searchParam[0]) && isset($searchParam[0][0])) {
                        // 高级查询中查询所属组使用的是in，不需下级组
                        $groupIds = array_intersect($searchParam[0], $groupId);
                        $param['search']['primary_4'] = [$groupIds, 'in'];
                    } else {
                        // 点击分组树的情况，需要递归获取下级
                        if ($recursion) {
                            $groupIds = $this->getFamilyGroupIds(1, $searchParam[0], $own);
                        } else {
                            $groupIds = [$searchParam[0]];
                        }

                        $groupId = array_intersect($groupId, $groupIds);
                        if (sizeof($groupId) > 0) {
                            if (!is_array($param['search']['primary_4'][0]) && $param['search']['primary_4'][0] == 0) {
                                $groupId[] = '';
                            }
                            $param['search']['primary_4'] = [$groupId, 'in'];

                        }
                    }
                } else {
                    $param['search']['primary_4'] = [$noAuth, 'not_in'];
                }
            }
        } else {
            if (!$recursion) {
                if (isset($param['search']['primary_4'])) {
                    $searchParam = $param['search']['primary_4'];
                    if (isset($searchParam[0]) && !is_array($searchParam[0])) {
                        $param['search']['primary_4'] = [[$searchParam[0]], 'in'];
                    }
                }
            }
        }

        if ($pages) {
            $param['pages'] = 1;
        }
        if (isset($param['filter'])) {
            if ($param['filter'] == 'phone') {
                if (!isset($param['search']['primary_3'])) {
                    $param['search']['primary_3'] = ['', '!='];
                }
            } else if ($param['filter'] == 'email') {
                if (!isset($param['search']['primary_9'])) {
                    $param['search']['primary_9'] = ['', '!='];
                }
            }
        }
        $data = app($this->formModelingService)->getCustomDataLists($param, $tableKey, $own);
        return $data;
    }
    /**
     * @获取当前分组的列表，不递归
     * @param type $data
     * @return 通讯录id
     */
    public function listAddressByGroupId($param, $groupType, $own)
    {
        return $this->listAddress($param, $groupType, $own, true, false);
    }

    /**
     * @添加通讯录
     * @param type $data
     * @return 通讯录id
     */
    public function addAddress($data, $currentUserId)
    {
        $attachmentId = $data['photo'];
        unset($data['photo']);
        $data['primary_5'] = $currentUserId;
        $data['primary_6'] = date('Y-m-d H:i:s');
        $pinyin = convert_pinyin($data['primary_1']);
        $data['name_pinyin'] = $pinyin[0];
        $data['name_index'] = substr($pinyin[0], 0, 1);
        if (!$addressId = app($this->addressRepository)->addAddress($data)) {
            return ['code' => ['0x000003', 'common']];
        }
        app($this->attachmentService)->attachmentRelation("address", $addressId, $attachmentId);
        return ['address_id' => $addressId];
    }
    /**
     * @编辑通讯录
     * @param type $data
     * @param type $addressId
     * @return 成功与否
     */
    public function editAddress($data, $addressId)
    {
        if ($addressId == 0) {
            return ['code' => ['0x004007', 'address']];
        }
        $attachmentId = '';
        if (isset($data['photo'])) {
            $attachmentId = $data['photo'];
            unset($data['photo']);
        }

        $pinyin = convert_pinyin($data['primary_1']);
        $data['name_pinyin'] = $pinyin[0];
        $data['name_index'] = substr($pinyin[0], 0, 1);

        if (!app($this->addressRepository)->editAddress($data, $addressId)) {
            return ['code' => ['0x000003', 'common']];
        }
        if ($attachmentId) {
            app($this->attachmentService)->attachmentRelation("address", $addressId, $attachmentId);
        }

        return true;
    }
    /**
     * @获取通讯录详情
     * @param type $groupType
     * @param type $addressId
     * @return object 通讯录详情
     */
    public function showAddress($groupType, $addressId, $own)
    {
        if (!in_array($groupType, [1, 2])) {
            return ['code' => ['0x004002', 'address']];
        }

        if ($addressId == 0) {
            return ['code' => ['0x004007', 'address']];
        }

        $addressInfo = app($this->addressRepository)->showAddress($groupType, $addressId);

        if ($groupType == 1 && !app($this->addressPublicGroupRepository)->showGroup($addressInfo->primary_4, [], true, $own)) {
            return ['code' => ['0x004012', 'address']];
        } else if ($groupType == 2 && $addressInfo->primary_5 != $own['user_id']) {
            return ['code' => ['0x004012', 'address']];
        }

        if ($addressInfo) {
            $addressInfo['photo'] = app($this->attachmentService)->getAttachmentIdsByEntityId(['entity_table' => 'address', 'entity_id' => $addressId]);
        }

        if ($addressInfo->primary_4 == 0) {
            $addressInfo->primary_4 = trans('address.no_group');
        }

        return $addressInfo;
    }
    /**
     * @删除通讯录
     * @param type $addressId
     * @return 成功与否
     */
    public function deleteAddress($addressId)
    {
        if ($addressId == 0) {
            return ['code' => ['0x004007', 'address']];
        }

        if (!app($this->addressRepository)->deleteAddress(explode(',', $addressId))) {
            return ['code' => ['0x000003', 'common']];
        }

        return true;
    }
    /**
     * @通讯录迁移
     * @param type $groupId
     * @param type $addressId
     * @return 成功与否
     */
    public function migrateAddress($groupId, $addressId, $tableKey, $own)
    {
        if ($addressId == '0') {
            return ['code' => ['0x004007', 'address']];
        }

        if ($groupId == 0) {
            return ['code' => ['0x004003', 'address']];
        }

        if ($tableKey == 'addressPrivate') {
            $data = app($this->addressPersonGroupRepository)->listGroup(['search' => ['user_id' => [$own['user_id']]]]);
        } else {
            $data = app($this->addressPublicGroupRepository)->getViewChildrenId([], $own);
        }

        if (is_object($data) && count($data) > 0) {
            $data = $data->toArray();
        } else {
            return ['code' => ['0x004017', 'address']];
        }
        $groupIds = array_column($data, 'group_id');

        if (!in_array($groupId, $groupIds)) {
            return ['code' => ['0x004017', 'address']];
        }

        if (!app($this->{$tableKey . 'Repository'})->migrateAddress(['primary_4' => $groupId], explode(',', $addressId))) {
            return ['code' => ['0x000003', 'common']];
        }

        return true;
    }
    public function copyAddress($groupId, $addressId, $own)
    {
        if ($addressId == '0') {
            return ['code' => ['0x004007', 'address']];
        }

        if ($groupId == 0) {
            return ['code' => ['0x004003', 'address']];
        }

        $addressId = explode(',', $addressId);

        $privateColumn = Schema::getColumnListing('address_private');
        $publicColumn = Schema::getColumnListing('address_public');
        $exceptFields = ['address_id', 'deleted_at', 'created_at', 'updated_at'];
        $fields = array_diff(array_intersect($privateColumn, $publicColumn), $exceptFields);
        $search = [
            'address_id' => [$addressId, 'in']
        ];
        $params = compact('search', 'fields');
        $all = app($this->addressPublicRepository)->listAddressesWithAvatar($params);
        foreach ($all as $one) {
            $primaryData = $one->toArray();
            unset($primaryData['address_id']);
            unset($primaryData['deleted_at']);
            unset($primaryData['created_at']);
            unset($primaryData['updated_at']);
            $primaryData['primary_5'] = $own['user_id'];
            $primaryData['primary_6'] = date('Y-m-d H:i:s');
            $primaryData['primary_4'] = $groupId;
            $copyData = $primaryData;
            unset($copyData['attachment_id']);
            $result = app($this->addressPrivateRepository)->entity->create($copyData);
            if ($primaryData['attachment_id']){
                $data = [[
                    'source_attachment_id' => $primaryData['attachment_id'],
                    'attachment_table' => 'address_private'
                    ]];
                $attachmentCopy = app(AttachmentService::class)->attachmentCopy($data, $own);
                if(isset($attachmentCopy[0]['code'])){
                    continue;
                }
                $insertData = [
                    'entity_id' => $result->address_id,
                    'entity_column' => 'primary_10',
                    'attachment_id' => $attachmentCopy[0]['attachment_id']
                ];
                DB::table('attachment_relataion_address_private')->insert($insertData);
            }
        }

        return true;
    }

    public function exportAddress($builder, $param)
    {
        $own = $param['user_info'];
        // if (!isset($param['order_by'])) {
        //     $param['order_by'] = ['primary_7' => 'asc', 'created_at' => 'desc'];
        // }

        if (!isset($param['response'])) {
            $param['response'] = 'data';
        }

        $groupType = $param['group_type'];

        $tableKey = $groupType == 1 ? 'address_public' : 'address_private';
        if ($groupType != 1) {
            $param['search']['primary_5'] = [$own['user_id']];
        }

        $listData = $this->listAddress($param, $groupType, $own, false);

        $listData = json_decode(json_encode($listData), true);

        $title = $groupType == 1 ? trans('address.public_address') : trans('address.private_address');

        $fieldTableKey = $groupType == 1 ? 'address_public' : 'address_private';

        $tableName = $groupType == 1 ? 'addressPublic' : 'addressPrivate';

        $header = [];

        foreach (app($this->formModelingService)->listFields('{}', $fieldTableKey) as $field) {
//            if ($field->field_list_show !== 1) {
//                continue;
//            }
            if ($field->field_code == 'primary_5' || $field->field_code == 'primary_6') {
                continue;
            }

            if ($field->field_code == 'primary_4') {
                $header['group_name'] = ['data' => mulit_trans_dynamic("custom_fields_table.field_name." . $fieldTableKey . "_" . $field->field_code), 'style' => ['width' => '100']];
            } else {
                if (in_array($field->field_code, ['primary_2', 'primary_7'])) {
                    $header[$field->field_code] = ['data' => mulit_trans_dynamic("custom_fields_table.field_name." . $fieldTableKey . "_" . $field->field_code), 'style' => ['width' => '100']];
                } else {
                    $header[$field->field_code] = ['data' => mulit_trans_dynamic("custom_fields_table.field_name." . $fieldTableKey . "_" . $field->field_code), 'style' => ['width' => '150']];
                }

            }
            if($field->field_directive == 'upload'){
                $header[$field->field_code]['type'] =  'attachement';
            }
        }

        $groups = $groupType == 1 ? $this->getPublicGroupTree(0) : $this->getPersonalGroupTree(0, $own['user_id']);

        $mapGroup = [];

        foreach ($groups as $group) {
            $mapGroup[$group->group_id] = $group->group_name;
        }

        $data = [];
        $attachments = [];
        $attachmentService = app('App\EofficeApp\Attachment\Services\AttachmentService');
        foreach ($listData as $value) {
            $temp = [];

            foreach ($header as $k => $v) {
                if (isset($v['type']) && $v['type'] == "attachement") {
                    if (!empty($value[$k])) {
                        $attachment = $attachmentService->getOneAttachmentById($value[$k][0]);
                        $attachmentPath = './attachment/'.$attachment['attachment_id'].'/' . $attachment['attachment_name'];
                        $temp[$k]['data'] = $attachment['attachment_name'];
                        $temp[$k]['url'] = $attachmentPath;
                        $attachments[] = [$attachmentPath => $attachment['temp_src_file']];

                    }
                    continue;
                } 
                if ($k == 'group_name') {
                    $temp[$k] =['data' => isset($mapGroup[$value['primary_4']]) ? $mapGroup[$value['primary_4']] : ($value['primary_4'] == '' ? trans("address.no_group") : $value['primary_4'])];
                } else {
                    $temp[$k] =['data' => (!isset($value[$k]) || $value[$k] === null) ? '' : $value[$k]];
                }
                
            }

            $data[] = $temp;
        }
        //生成Excel文件
        list($fileName, $filePath) = $builder->setTitle($title)->setHeader($header)->setData($data)->generate();
        //存在附件才需要走if语句的逻辑生成压缩包

        if(!empty($attachments)){
            $attachments[] = [$fileName . '.' . $builder->getSuffix() => $filePath];
            return Export::saveAsZip($attachments, $title);
        }
        return [$fileName,$filePath];
    }

    public function importPersonalAddress($data, $param)
    {
        $param['search'] = [
            'primary_5' => [$param['user_info']['user_id']]
        ];

        return $this->importCommon($data, $param, 'address_private');
    }

    public function importPublicAddress($data, $param)
    {
        return $this->importCommon($data, $param, 'address_public');
    }

    private function importCommon($data, $param, $tableKey)
    {
        $groupIds = $this->getFamilyGroupIds($tableKey == 'address_public', 0, $param['user_info']);
        $param['search']['primary_4'] = [$groupIds, 'in'];

        app($this->formModelingService)->importCustomData($tableKey, $data, $param);

        return ['data' => $data];
    }

    public function importPublicAddressFilter(&$data, $param = [])
    {
        return $this->importAddressFilter($data, 'address_public', $param);
    }
    public function importPersonalAddressFilter(&$data, $param = [])
    {
        return $this->importAddressFilter($data, 'address_private', $param);
    }
    /**
     * [importAddressFilter 筛选导入数据,添加错误信息]
     *
     * @method
     *
     * @param  [array]                     $data [导入数据]
     *
     * @return [array]                           [添加错误信息后的导入数据]
     */
    private function importAddressFilter(&$data, $tableKey, $param = [])
    {
        $userInfo = $param['user_info'];
        $type = $tableKey == 'address_public' ? 1 : 0;
        $groupIds = $this->getFamilyGroupIds($type, 0, $userInfo);
        // 为确定创建人做准备
        $param['type'] = $param['type'] ?? 1;
        $repository = $type == 1 ? 'addressPublicRepository' : 'addressPrivateRepository';
        $formModelingService = app($this->formModelingService);
        foreach ($data as $key => $value) {
            $data[$key]['primary_5'] = $param['user_info']['user_id'];
            if (!isset($value['primary_6']) || $value['primary_6'] == '') {
                $data[$key]['primary_6'] = date('Y-m-d H:i:s');
            }
            $result = $formModelingService->importDataFilter($tableKey, $data[$key], $param);
            if (!empty($result)) {
                $data[$key]['importResult'] = importDataFail();
                $data[$key]['importReason'] = importDataFail($result);
                continue;
            } else {
                $data[$key]['importResult'] = importDataSuccess();
            }
            if( $tableKey == 'address_public' && !empty($value['primary_4'])  && !is_numeric($value['primary_4'])){
                $data[$key]['importResult'] = importDataFail();
                $data[$key]['importReason'] = importDataFail(trans("address.user_has_no_team"));
                continue;
            }
            // 验证分组
            if (!empty($value['primary_4']) && !in_array($value['primary_4'], $groupIds)) {
                $data[$key]['importResult'] = importDataFail();
                $data[$key]['importReason'] = importDataFail(trans("address.user_has_no_team"));
                continue;
            }
            // 验证手机号
//            if (isset($value['primary_3']) && !empty($value['primary_3']) && !preg_match("/^1\d{10}$/", $value['primary_3'])) {
//                $data[$key]['importResult'] = importDataFail();
//                $data[$key]['importReason'] = importDataFail(trans("address.phone_number_format_is_not_correct"));
//                continue;
//            }
            // 验证姓名
            if (!isset($value['primary_1']) || $value['primary_1'] == '') {
                $data[$key]['importResult'] = importDataFail();
                $data[$key]['importReason'] = importDataFail(trans("address.name_cannot_be_empty"));
                continue;
            }
            $pinyin = convert_pinyin($value['primary_1']);
            if (!isset($value['name_pinyin']) || $value['name_pinyin'] == '') {
                $data[$key]['name_pinyin'] = $pinyin[0];
            }
            if (!isset($value['name_pinyin']) || $value['name_pinyin'] == '') {
                $data[$key]['name_index'] = substr($pinyin[0], 0, 1);
            }

            if(! in_array($param['type'], [1, 3])){
                $primaryKey = $param['primaryKey'] ?? 'primary_1';
                $isPrivate = $type == 0;
                $oldData = app($this->$repository)->entity
                    ->select(['primary_4', 'primary_5'])
                    ->when($isPrivate, function ($query) use ($userInfo) {
                        $query->where('primary_5', $userInfo['user_id']);
                    })
                    ->where($primaryKey, $value[$primaryKey])
                    ->first();

                // 更新记录不存在
                if($param['type'] == 2 && !$oldData){
                    $data[$key]['importResult'] = importDataFail();
                    $data[$key]['importReason'] = importDataFail(trans("address.name_not_exist"));
                    continue;
                }
                if($oldData) {
                    // 验证原分组权限
                    $oldGroupId = $oldData->primary_4;
                    if($oldGroupId && !in_array($oldGroupId, $groupIds)){
                        $data[$key]['importResult'] = importDataFail();
                        $data[$key]['importReason'] = importDataFail(trans("address.user_has_no_team"));
                        continue;
                    }
                    // 更新操作时创建人保持不变
                    $data[$key]['primary_5'] = $oldData->primary_5;
                }
            }
        }
        return $data;
    }

    public function getPublicAddressTemplate($own,$param)
    {
        $param['user_info']=$own;
        return app($this->formModelingService)->getImportFields('address_public', $param, trans("address.public_address_book_import_template"));
    }
    public function getPersonalAddressTemplate($own,$param)
    {
        $param['user_info']=$own;
        return app($this->formModelingService)->getImportFields('address_private', $param, trans("address.personal_address_book_import_template"));
    }
    public function getFamilyGroupIds($groupType, $groupId, $own)
    {
        if (is_array($groupId)) {
            $result = [];
            foreach ($groupId as $key => $value) {
                $groupIdObj = $groupType == 1 ? app($this->addressPublicGroupRepository)->getAuthFimalyGroupId($value, $own) : $this->tree($value, $own['user_id']);

                $groupIds = [$value];

                if ($groupIdObj) {
                    foreach ($groupIdObj as $v) {
                        $groupIds[] = $v->group_id;
                    }
                }
                $result = array_merge($result, $groupIds);
            }
            return $result;
        } else {
            $groupIdObj = $groupType == 1 ? app($this->addressPublicGroupRepository)->getAuthFimalyGroupId($groupId, $own) : $this->tree($groupId, $own['user_id']);

            $groupIds = [$groupId];

            if ($groupIdObj) {
                foreach ($groupIdObj as $v) {
                    $groupIds[] = $v->group_id;
                }
            }

            return $groupIds;
        }
    }
    private function getPublicGroupTree($parentId, $parentName = '')
    {
        $tree = [];

        if ($groups = app($this->addressPublicGroupRepository)->getChildrenGroup($parentId, ['group_id', 'group_name', 'has_children'])) {
            foreach ($groups as $group) {
                $group->group_name = $parentName == '' ? $group->group_name : $parentName . '/' . $group->group_name;

                $tree[] = $group;

                if ($group->has_children == 1) {
                    $tree = array_merge($tree, $this->getPublicGroupTree($group->group_id, $group->group_name));
                }
            }
        }

        return $tree;
    }
    private function getPersonalGroupTree($parentId, $userId, $parentName = '')
    {
        $tree = [];

        if ($groups = app($this->addressPersonGroupRepository)->getChildrenGroup($parentId, $userId, ['group_id', 'group_name', 'has_children'])) {
            foreach ($groups as $group) {
                $group->group_name = $parentName == '' ? $group->group_name : $parentName . '/' . $group->group_name;

                $tree[] = $group;

                if ($group->has_children == 1) {
                    $tree = array_merge($tree, $this->getPersonalGroupTree($group->group_id, $userId, $group->group_name));
                }
            }
        }
        return $tree;
    }

    private function tree($parentId = 0, $userId)
    {
        $tree = [];

        if ($groups = app($this->addressPersonGroupRepository)->getChildrenGroupId($parentId, $userId)) {
            foreach ($groups as $group) {

                $tree[] = $group;

                if ($group->has_children == 1) {
                    $tree = array_merge($tree, $this->tree($group->group_id, $userId));
                }
            }
        }

        return $tree;
    }
    /**
     * @处理通讯字段
     * @param type $fields
     * @return array 新闻字段
     */
    private function handleFields($fields, $groupType)
    {
        $fieldCode = ['address_id', 'name_pinyin', 'name_index'];

        if ($groupType == 1) {
            $showFields = DB::table('custom_fields_table')->where('field_table_key', 'address_public')
//                ->where('field_list_show', 1)
                ->get();
        } else {
            $showFields = DB::table('custom_fields_table')->where('field_table_key', 'address_private')
//                ->where('field_list_show', 1)
                ->get();
        }

        if ($showFields) {
            foreach ($showFields as $field) {
                if ($field->field_directive == 'area') {
                    $fieldCode[] = $field->field_code . '_1';
                    $fieldCode[] = $field->field_code . '_2';
                    continue;
                }
                $fieldCode[] = $field->field_code;
            }
        }

        $fields = empty($fields) ? $fieldCode : array_intersect($fields, $fieldCode);

        return $this->joinFieldPrefix($fields, $groupType);
    }
    /**
     * @为字段加前缀
     * @param type $fields
     * @param type $groupType
     * @return array | 字段数组
     */
    private function joinFieldPrefix($fields, $groupType)
    {
        $groupPrefix = $groupType == 1 ? 'address_public_group.' : 'address_person_group.';

        $_fields = [];

        foreach ($fields as $value) {
            if ($value == 'primary_4') {
                $_fields[] = $groupPrefix . 'group_name';
            } else if ($value == 'primary_5') {
                $_fields[] = 'user.user_id';
            }
            if ($groupType == 1) {
                $_fields[] = 'address_public.' . $value;
            } else {
                $_fields[] = 'address_private.' . $value;
            }
        }

        return $_fields;
    }
    /**
     * @处理通讯录查询条件
     * @param type $search
     * @return array 查询条件
     */
    private function handleSearch($search, $groupType)
    {
        if (!$search) {
            return [];
        }

        $addressSearch = [];

        foreach ($search as $key => $value) {
            if ($groupType == 1) {
                $addressSearch['address_public.' . $key] = $value;
            } else {
                $addressSearch['address_private.' . $key] = $value;
            }
        }

        return $addressSearch;
    }
    /**
     * @获取所有父级通讯录ID
     * @param type $parentId
     * @return string
     */
    private function getParentArrId($parentId)
    {
        if ($parentId == 0) {
            return $parentId;
        }

        $parentGroup = app($this->addressPublicGroupRepository)->getParentGroup($parentId, ['group_id', 'arr_parent_id']);

        $arrParentId = $parentGroup['arr_parent_id'] . ',' . $parentId;

        return $arrParentId;
    }
    /**
     * @获取有权限的子通讯录组ID
     * @param type $parentId
     * return array | 通讯录组ID
     */
    private function getAuthChildrenId($parentId, $own)
    {
        if (!$authGroup = app($this->addressPublicGroupRepository)->getAuthChildrenId($parentId, $own)) {
            return [];
        }

        $authGroupId = [];

        foreach ($authGroup as $group) {
            $authGroupId[] = $group->group_id;
        }

        return $authGroupId;
    }
    /**
     * @获取所有可以查看的通讯录组
     * @param type $fields
     * @param type $parentId
     * @return array 通讯录组
     */
    private function getAllViewGroups($fields, $parentId, $own)
    {
        if (!$allGroup = app($this->addressPublicGroupRepository)->getAllChidlren($fields, $parentId)) {
            return [];
        }

        $allViewGroup = [];

        $authGroupId = $this->getAuthChildrenId($parentId, $own);
        foreach ($allGroup as $group) {
            if (in_array($group->group_id, $authGroupId)) {
                $allViewGroup[] = $group;
            } else if (app($this->addressPublicGroupRepository)->isViewGroup($group->group_id, $own)) {
                $group->no_auth = 1;

                $allViewGroup[] = $group;
            }
        }

        return $allViewGroup;
    }
    /**
     * @获取所有有权限的通讯录组id
     * @return array 通讯录组id
     */
    public function getAllAuthGroupId($own)
    {
        if (!$allAuthGroup = app($this->addressPublicGroupRepository)->getAuthGroupId($own)) {
            return [];
        }

        $groupId = [];

        foreach ($allAuthGroup as $group) {
            $groupId[] = $group->group_id;
        }

        return $groupId;
    }
    public function getAllAuthGroup($own)
    {
        if (!$allAuthGroup = app($this->addressPublicGroupRepository)->getAuthGroupId($own, ['group_id', 'group_name'])) {
            return [];
        }

        return $allAuthGroup;
    }
    /**
     * @编辑通讯录组后置操作
     * @param type $addressGroupRepository
     * @param type $oldParentId
     * @param type $newParentId
     */
    private function afterEditGroup($addressGroupRepository, $oldParentId, $newParentId, $groupType, $groupId, $oldArrParentId, $newArrParentId)
    {
        if ($oldParentId != $newParentId) {
            if ($addressGroupRepository->countChildrenGroup($oldParentId) == 0) {
                $addressGroupRepository->editGroup(['has_children' => 0], $oldParentId);
            }

            if ($newParentId > 0) {
                $addressGroupRepository->editGroup(['has_children' => 1], $newParentId);
            }

            if ($groupType == 1) {
                $addressGroupRepository->updateTreeChildren($oldArrParentId, $newArrParentId, $groupId);
            }
        }
    }
    /**
     * @新建通讯录组后置操作
     * @param type $addressGroupRepository
     * @param type $newParentId
     */
    private function afterAddGroup($addressGroupRepository, $newParentId)
    {
        if ($newParentId > 0) {
            $addressGroupRepository->editGroup(['has_children' => 1], $newParentId);
        }
    }
    /**
     * @删除通讯录组后置操作
     * @param type $addressGroupRepository
     * @param type $oldParentId
     */
    private function afterDeleteGroup($addressGroupRepository, $oldParentId)
    {
        if ($addressGroupRepository->countChildrenGroup($oldParentId) == 0) {
            $addressGroupRepository->editGroup(['has_children' => 0], $oldParentId);
        }
    }

    private function getAddressGroupRepository($groupType)
    {
        return $groupType == 1 ? app($this->addressPublicGroupRepository) : app($this->addressPersonGroupRepository);
    }

    private function defaultValue($key, $data, $default)
    {
        return isset($data[$key]) ? $data[$key] : $default;
    }
    public function filterAddressPrivateList(&$param,$own)
    {
        if (isset($param['search']['primary_4'])) {
            $searchParam = $param['search']['primary_4'];

            if (is_array($searchParam[0]) && isset($searchParam[0][0])) {
                // 高级查询中查询所属组使用的是in，不需下级组
                $groupIds = $this->getFamilyGroupIds(1, $searchParam[0], $own);
                return ['primary_5' => [$own['user_id']]];
            } else {
                // 点击分组树的情况，需要递归获取下级
                unset($param['search']['primary_4']);
                $groupIds = $this->getFamilyGroupIds(2, $searchParam[0], $own);
                return ['primary_5' => [$own['user_id']], 'primary_4' => [$groupIds, 'in']];
            }
        } else {
            return ['primary_5' => [$own['user_id']]];
        }
    }
    // public function filterAddressPublicList(&$param, &$own)
    // {
    //     $allGroupIds = app($this->addressPublicGroupRepository)->listGroup(['fields' => ['group_id']])->pluck('group_id')->toArray();
    //     $groupId = $this->getAllAuthGroupId($own);
    //     $noAuth = array_diff($allGroupIds, $groupId);
    //     if(empty($groupId)){
    //         return ['primary_4' => [[''], 'in']];
    //     }
    //     if (!$groupId = $this->getAllAuthGroupId($own)) {
    //         return ['primary_4' => [[''], 'in']];
    //     }

    //     //按通讯录组筛选，获取该组下的所有子孙组id
    //     if (isset($param['search']['primary_4'])) {
    //         $searchParam = $param['search']['primary_4'];
    //         unset($param['search']['primary_4']);
    //         if(is_array($searchParam[0]) && isset($searchParam[0][0])){
    //             // 高级查询中查询所属组使用的是in，不需下级组
    //             $groupIds = array_intersect($searchParam[0], $groupId);
    //             return ['primary_4' => [$groupIds, 'in']];
    //         }else{
    //             // 点击分组树的情况，需要递归获取下级
    //             $groupIds = $this->getFamilyGroupIds(1, $searchParam[0], $own);
    //             $groupId = array_intersect($groupId, $groupIds);

    //             if(sizeof($groupId) > 0){
    //                 return ['primary_4' => [$groupId, 'in']];
    //             }
    //         }
    //     }else{
    //         if(!empty($groupId)){
    //             $groupId = array_merge($groupId, ['', null]);
    //             dd($groupId);
    //             return ['primary_4' => [$groupId, 'in']];
    //         }
    //     }
    //     return [];
    // }
    // 判断公共通讯录详情的查看权限
    public function addressPublicDetailPurview($addressId)
    {
        $own = own();
        $viewGroup = $this->getAllAuthGroupId($own);
        $addressInfo = app($this->addressPublicRepository)->getAddressInfo(['address_id' => [$addressId]]);

        if (!empty($addressInfo) && isset($addressInfo->primary_4)) {
            if ($addressInfo->primary_4 == 0 || $addressInfo->primary_4 == '') {
                return true;
            }
            if (in_array($addressInfo->primary_4, $viewGroup)) {
                return true;
            }
        }
        return false;
    }
    // 判断个人通讯录详情的查看权限
    public function addressPrivateDetailPurview($addressId)
    {
        $own = own();
        $where = [
            'address_id' => [$addressId],
            'primary_5' => [$own['user_id']],
        ];
        $addressInfo = app($this->addressPrivateRepository)->getAddressInfo($where);
        return !empty($addressInfo);
    }

    // 递归获取所有可查看的公共通讯组
    public function getAllViewGroupsByRecursion($own, $params) {
        $params = $this->parseParams($params);
        $params['fields'] = ['group_id'];
        $authGroups = app($this->addressPublicGroupRepository)->getAuthFimalyGroupId(0, $own, $params)->toArray();
        $authGroupsIds = Arr::pluck($authGroups, 'group_id');
        $allGroups = app($this->addressPublicGroupRepository)->getAllGroupInFlat(['group_id', 'group_name', 'parent_id', 'has_children'])->toArray();
        $allGroupInTreeOrder = $this->getChildrenGroup($allGroups);
        $result = [];
        foreach ($allGroupInTreeOrder as $key => $value){
            if(in_array($value['group_id'], $authGroupsIds)){
                $result[] = [
                    'group_id' => $value['group_id'],
                    'group_name' => $value['group_name']
                ];
            }
        }
        return $result;
    }


    // 递归获取所有个人通讯组
    public function getPrivateGroupsOrderByTree($own, $param = [])
    {
        $params['search']['user_id'] = [$own['user_id']];
        $params['fields'] = ['group_id', 'group_name', 'parent_id', 'has_children'];
        $groups = app($this->addressPersonGroupRepository)->listGroup($params);
        $groupsInOrder = $this->getChildrenGroup($groups);
        if(isset($param['search']) && !empty($param['search'])){
            $param = $this->parseParams($param);
            $param['search']['user_id'] = [$own['user_id']];
            $param['fields'] = ['group_id', 'group_name', 'parent_id', 'has_children'];
            $searchGroups = app($this->addressPersonGroupRepository)->listGroup($param);
            $ids = Arr::pluck($searchGroups, 'group_id');
            $result = [];
            foreach($groupsInOrder as $group){
                if(in_array($group['group_id'], $ids)){
                    $result[] = $group;
                }
            }
            return $result;
        }
        return $groupsInOrder;
    }

    // 递归排序获取子组并放在一层
    public function getChildrenGroup($groups, $parent_id = 0)
    {
        $new = [];
        foreach($groups as $key => $value){
            if($value['parent_id'] == $parent_id){
                $new[] = $value;
                unset($groups[$key]);
                if ($value['has_children']) {
                    $new = array_merge($new, $this->getChildrenGroup($groups, $value['group_id']));
                }
            }
        }
        return $new;
    }

    public function getAddressList($groupType, $groupId, $own, $params)
    {
        if (!isset($params['search'])) {
            $params['search'] = [];
        }
        if ($groupType == 1) {
            $params['search'] = [
                'primary_4' => [$groupId],
            ];
        } else {
            $params['search'] = [
                'primary_5' => [$own['user_id']],
                'primary_4' => [$groupId],
            ];

        }
        if ($groupId == 0 && isset($params['search']['primary_4'])) {
            unset($params['search']['primary_4']);
        }
        return $this->listAddress($params, $groupType, $own);
    }
    public function getAddressTree($groupType, $groupId, $own)
    {
        $data = $this->getViewChildren($groupType, $groupId, [], $own);
        if (count($data) > 0) {
            foreach ($data as $key => $value) {
                if ($value['has_children'] == 0) {
                    $data[$key]['has_children'] = 1;
                }
            }
        }

        $address = $groupType == 1 ? app($this->addressPublicRepository)->listAddress([], [$groupId], $own['user_id']) : app($this->addressPrivateRepository)->listAddress([], [$groupId], $own['user_id']);
        if (count($address) > 0) {
            foreach ($address as $key => $value) {
                $data[] = [
                    "group_id" => '',
                    "group_name" => $value['primary_1'],
                    "parent_id" => $groupId,
                    "has_children" => 0,
                ];
            }
        }
        $res = [];
        if($groupType == 1){
            foreach ($data as $key => $value) {
                if(isset($value['has_children']) && $value['has_children']){
                    $res[] = $value;
                }
            }
            $data = $res;
        }
        return $data;
    }

    // 添加个人通讯录权限
    public function addressPrivateAddPurview($data)
    {
        $userId = $data['current_user_id'] ?? '';
        if (!$userId) {
            $userId = own('user_id');
        }
        //检验创建人
        $creator = $data['primary_5'] ?? '';
        if (!$creator) {
            return self::EMPTY_CREATOR;
        }
        if(!(isset($data['outsource']) && $data['outsource']) && $creator != own('user_id')){
            return false;
        }
        //检验所属组
        $groupId = $data['primary_4'] ?? 0;
        if (!$groupId) {
            return true;
        }
        $groupInfo = app($this->addressPersonGroupRepository)->entity->find($groupId);
        if (!$groupInfo || $groupInfo->user_id != $userId) {
            return false;
        }
        return true;
    }

    // 添加公共通讯录权限
    public function addressPublicAddPurview($data)
    {
        $userId = $data['current_user_id'] ?? '';
        if (!$userId) {
            $userInfo = own();
        }else{
            $userInfo = app($this->userService)->getUserDeptAndRoleInfoByUserId($userId);
        }
        // 检验创建人
        $creator = $data['primary_5'] ?? '';
        if (!$creator) {
            return self::EMPTY_CREATOR;
        }
        if(!(isset($data['outsource']) && $data['outsource']) && $creator != $userInfo['user_id']){
            return false;
        }
        // 检验所属组
        $groupId = $data['primary_4'] ?? 0;
        if (!$groupId) {
            return true;
        }
        $viewGroup = $this->getAllAuthGroupId($userInfo);
        if (!in_array($groupId, $viewGroup)) {
            return false;
        }
        return true;
    }

    // 编辑个人通讯录权限
    public function addressPrivateEditPurview($data, $id)
    {
        $userId = $data['current_user_id'] ?? '';
        if (!$userId) {
            $userId = own('user_id');
        }
        // 是否为本人修改
        $origin = app($this->addressPrivateRepository)->entity->find($id);
        if (!$origin || $origin->primary_5 != $userId) {
            return false;
        }
        // 检验提交的创建人
        $creator = $data['primary_5'] ?? '';
        if(!$creator){
            $creator = isset($origin->primary_5)?$origin->primary_5:'';
        }
        if (!$creator || $creator != $userId) {
            return false;
        }
        if (!isset($data['primary_4'])) {
            $groupId = isset($origin->primary_4)?$origin->primary_4:'';
        }else{
            $groupId = $data['primary_4'];
        }
        if ($groupId) {
            $groupInfo = app($this->addressPersonGroupRepository)->entity->find($groupId);
            if (!$groupInfo || $groupInfo->user_id != $userId) {
                return false;
            }
        }
        return true;
    }

    // 编辑公共通讯录权限
    public function addressPublicEditPurview($data, $id)
    {
        $userId = $data['current_user_id'] ?? '';
        if (!$userId) {
            $userInfo = own();
        }else{
            $userInfo = app($this->userService)->getUserDeptAndRoleInfoByUserId($userId);
        }
        $viewGroup = $this->getAllAuthGroupId($userInfo);
        //是否拥有该通讯录权限
        $origin = app($this->addressPublicRepository)->entity->find($id);
        if (!$origin || ($origin->primary_4 && !in_array($origin->primary_4, $viewGroup))) {
            return false;
        }
        // 检验创建人
        $creator = $data['primary_5'] ?? '';
        if(!$creator){
            $creator = isset($origin->primary_5)?$origin->primary_5:'';
        }
        if ($creator != $origin->primary_5) {
            return false;
        }
        // 检验修改后的组是否有权限
        if (!isset($data['primary_4'])) {
            $groupId = isset($origin->primary_4)?$origin->primary_4:'';
        }else{
            $groupId = $data['primary_4'];
        }

        if ($groupId && !in_array($groupId, $viewGroup)) {
            return false;
        }
        return true;
    }

    /**
     * 删除个人通讯录权限判断
     *
     * @param string|array $dataId
     *
     * @return array
     */
    public function addressPrivateDeletePurview($dataId)
    {
        $userId = $data['current_user_id'] ?? '';
        if (!$userId) {
            $userId = own('user_id');
        }

        if (is_string($dataId)) {
            $ids = explode(',', $dataId);
        } else {
            $ids = $dataId;
        }

        $addresses = app($this->addressPrivateRepository)->entity->find($ids);
        foreach ($addresses as $address) {
            if ($address->primary_5 != $userId) {
                return ['code' => ['0x000006', 'common']];
            }
        }

        return [];
    }

    /**
     * 删除公共通讯录权限判断
     *
     * @param string|array $dataId
     *
     * @return array
     */
    public function addressPublicDeletePurview($dataId)
    {

        $userId = $data['current_user_id'] ?? '';
        if (!$userId) {
            $userInfo = own();
        }else{
            $userInfo = app($this->userService)->getUserDeptAndRoleInfoByUserId($userId);
        }
        $viewGroup = $this->getAllAuthGroupId($userInfo);

        if (is_string($dataId)) {
            $ids = explode(',', $dataId);
        } else {
            $ids = $dataId;
        }
        $addresses = app($this->addressPublicRepository)->entity->find($ids);

        foreach ($addresses as $address) {
            if ($address->primary_4 && !in_array($address->primary_4, $viewGroup)) {
                return ['code' => ['0x000006', 'common']];
            }
        }

        return [];
    }

    public function checkPrivateUnique($params)
    {
        $field = $params['field'];
        $value = $params['value'];
        $creator = isset($params['data']['primary_5'])?$params['data']['primary_5']:'';

        /** @var AddressPrivateRepository $repository */
        $repository = app($this->addressPrivateRepository);
        if (!isset($params['primaryValue']) || emptyWithoutZero($params['primaryValue'])){
            // 新增
            return $repository->isUniqueOnCreate($field, $value, $creator);
        }
        // 编辑
        $primaryKey = $params['primaryKey'];
        $primaryValue = $params['primaryValue'];
        if(empty($creator)){
            $id = isset($params['data']['id']) ? $params['data']['id'] : '';  
            if($id){
                $info = app($this->addressPrivateRepository)->entity->find($id);
                $creator = isset($info->primary_5)?$info->primary_5:'';
            }          
        }
        return $repository->isUniqueOnUpdate($field, $value, $creator, $primaryKey, $primaryValue);
    }


}
