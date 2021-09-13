<?php

namespace App\EofficeApp\PublicGroup\Services;

use App\EofficeApp\Base\BaseService;
use App\EofficeApp\User\Services\UserService;
use App\EofficeApp\PublicGroup\Repositories\PublicGroupRepository;

/**
 * 公共用户组服务
 *
 * @author: 喻威
 *
 * @since：2015-10-19
 */
class PublicGroupService extends BaseService
{

    /** @var object 公共用户组资源库变量 */
    private $publicGroupRepository;

    public function __construct(
        UserService $userService,
        PublicGroupRepository $publicGroupRepository
    )
    {
//        $this->userService           = $userService;
//        $this->publicGroupRepository = $publicGroupRepository;

        $this->userService           = 'App\EofficeApp\User\Services\UserService';
        $this->userSystemInfoRepository           = 'App\EofficeApp\User\Repositories\UserSystemInfoRepository';
        $this->publicGroupRepository = 'App\EofficeApp\PublicGroup\Repositories\PublicGroupRepository';
        $this->publicGroupUserRepository = 'App\EofficeApp\PublicGroup\Repositories\PublicGroupUserRepository';
        $this->publicGroupRoleRepository = 'App\EofficeApp\PublicGroup\Repositories\PublicGroupRoleRepository';
        $this->publicGroupDeptRepository = 'App\EofficeApp\PublicGroup\Repositories\PublicGroupDeptRepository';
        $this->publicGroupMemberRepository = 'App\EofficeApp\PublicGroup\Repositories\PublicGroupMemberRepository';
    }

    /**
     * 访问公共用户组列表
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-19
     */
    public function getPublicGroupList($user, $dept, $role, $data)
    {
        $data["auth_user"] = $user;
        $data["auth_dept"] = $dept;
        $data["auth_role"] = $role;
        $params = $this->parseParams($data);
        if (isset($params['search']['user_id'])) {
            unset($params['search']['user_id']);
        }
        $result = $this->response(app($this->publicGroupRepository), 'getPublicGroupListTotal', 'getPublicGroupList', $params);
        $temp = [];
        $resultReturn = [];
        if(isset($params['dataFilter']) && !empty($params['dataFilter'])) {
            $config = config('dataFilter.'.$params['dataFilter']);
            if (!empty($config)) {
                $method = $config['dataFrom'][1];
                $params['loginUserInfo'] = ['user_id' => $user];
                $userIdFilterData = app($config['dataFrom'][0])->$method($params);
            }
        }
        $invalidUserId = app($this->userSystemInfoRepository)->getInvalidUserId();
        if (isset($result['list'])) {
            foreach ($result['list'] as $key => $v) {
                $v['group_member'] = implode(',', array_column($v['group_has_many_member'], 'user_id'));
                foreach ($v as $k1 => $v1) {
                    $temp[$k1] = $v1;
                }
                $group_priv = "";
                if ($v['group_type'] == 0) {
                    $group_priv = trans("public_group.all_people");
                } else {
                    $group_priv = trans("public_group.specified_range");
                    if (!empty($v['group_dept_id'])) {
                        $group_priv .= trans("public_group.dept");
                    }
                    if (!empty($v['group_role_id'])) {
                        $group_priv .= trans("public_group.role");
                    }
                    if (!empty($v['group_user_id'])) {
                        $group_priv .= trans("public_group.user");
                    }
                }
                $temp['group_priv'] = $group_priv;
                if (isset($userIdFilterData) && !empty($userIdFilterData) && isset($userIdFilterData['user_id']) && !empty($userIdFilterData['user_id'])) {
                    $groupMemberArray = array_column($v['group_has_many_member'], 'user_id');
                    foreach ($groupMemberArray as $gKey => $gValue) {
                        if (isset($userIdFilterData['isNotIn']) && !$userIdFilterData['isNotIn']) {
                            if (in_array($gValue, $userIdFilterData['user_id'])) {
                                unset($groupMemberArray[$gKey]);
                            }
                        } else {
                            if (!in_array($gValue, $userIdFilterData['user_id'])) {
                                unset($groupMemberArray[$gKey]);
                            }
                        }
                    }
                    $v['group_member'] = !empty($groupMemberArray) ? implode(',', $groupMemberArray) : '';
                    $temp['group_member'] = $v['group_member'];
                }

                if (empty($v['group_member'])) {
                    $temp['group_member_count'] = 0;
                } else {
                    if (isset($data['platform']) && $data['platform'] == 'mobile') {
                        $members = explode(",", trim($v['group_member'], ","));
                        $members = array_diff($members, $invalidUserId);
                        $temp['group_member_count'] = count($members);
                        $temp['group_member'] = implode(',', $members);
                    } else {
                        $temp['group_member_count'] = count(explode(",", trim($v['group_member'], ",")));
                    }
                    
                }
                if (isset($data["user_total"])) {
                    $temp['user_total'] = $temp['group_member_count'];
                }
                $temp['has_children'] = $temp['group_member_count'] ? 1 : 0;
                array_push($resultReturn, $temp);
            }
        }

        $finalData = [
            "list" => $resultReturn,
        ];

        if (isset($result['total'])) {
            $finalData['total'] = $result['total'];
        }
        return $finalData;
    }

    /**
     * 访问公共用户组管理列表
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-19
     */
    public function getPublicGroupManageList($data)
    {
        $result = $this->response(app($this->publicGroupRepository), 'getPublicGroupManageListTotal', 'getPublicGroupManageList', $this->parseParams($data));
        $temp = [];
        $resultReturn = [];
        $invalidUserId = app($this->userSystemInfoRepository)->getInvalidUserId();
        if (isset($result['list'])) {
            foreach ($result['list'] as $v) {
                $v['group_member'] = implode(',', array_column($v['group_has_many_member'], 'user_id'));
                foreach ($v as $k1 => $v1) {
                    $temp[$k1] = $v1;
                }
                $group_priv = "";
                if ($v['group_type'] === 0) {
                    $group_priv = trans("public_group.all_people");
                } else {
                    $group_priv = trans("public_group.specified_range");
                }
                $temp['group_priv'] = $group_priv;
                if (empty($v['group_member'])) {
                    $temp['group_member_count'] = 0;
                } else {
                    $members = explode(",", trim($v['group_member'], ","));
                    $members = array_diff($members, $invalidUserId);
                    $temp['group_member_count'] = count($members);
                }
                if (isset($data["user_total"])) {
                    $temp['user_total'] = $temp['group_member_count'];
                }
                $temp['has_children'] = $temp['group_member_count'] ? 1 : 0;
                array_push($resultReturn, $temp);

            }
        }


        $finalData = [
            "list" => $resultReturn,
        ];

        if (isset($result['total'])) {
            $finalData['total'] = $result['total'];
        }
        return $finalData;
    }

    /**
     * 增加公共用户组
     *
     * @param array $data
     *
     * @return  int
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function addPublicGroup($data)
    {
        $data['group_create_user'] = $data['user_id'];
        //当为全体成员时，部门角色用户隐藏值为空
        if ($data['group_type'] == trans("public_group.all_people")) {
            $data["group_user_id"] = $data["group_role_id"] = $data["group_dept_id"] = "";
            $data['group_type'] = 0;
        } else {
            $data['group_type'] = '1';
        }

        $publicGroupPyArray = convert_pinyin($data["group_name"]);
        $data["group_name_py"] = $publicGroupPyArray[0];
        $data["group_name_zm"] = $publicGroupPyArray[1];

        $publicGroupData = array_intersect_key($data, array_flip(app($this->publicGroupRepository)->getTableColumns()));
        $result = app($this->publicGroupRepository)->insertData($publicGroupData);
        // 用户部门角色和组成员添加
        $this->addPublicGroupUser($result->group_id, $data["group_user_id"]);
        $this->addPublicGroupDept($result->group_id, $data["group_dept_id"]);
        $this->addPublicGroupRole($result->group_id, $data["group_role_id"]);
        $this->addPublicGroupMember($result->group_id, $data["group_member"]);
        return $result->group_id;
    }

    private function addPublicGroupUser($groupId, $groupUser)
    {
        if (!$groupUser) {
            return true;
        }
        if (!is_array($groupUser)) {
            $groupUser = array_filter(explode(',', $groupUser));
        }
        $insertData = [];
        foreach ($groupUser as $key => $value) {
           $insertData[] = ['group_id' => $groupId, 'user_id' => $value];
        }
        return app($this->publicGroupUserRepository)->insertMultipleData($insertData);
    }
    private function addPublicGroupDept($groupId, $groupDept)
    {
        if (!$groupDept) {
            return true;
        }
        if (!is_array($groupDept)) {
            $groupDept = array_filter(explode(',', $groupDept));
        }
        $insertData = [];
        foreach ($groupDept as $key => $value) {
           $insertData[] = ['group_id' => $groupId, 'dept_id' => $value];
        }
        return app($this->publicGroupDeptRepository)->insertMultipleData($insertData);
    }
    private function addPublicGroupRole($groupId, $groupRole)
    {
        if (!$groupRole) {
            return true;
        }
        if (!is_array($groupRole)) {
            $groupRole = array_filter(explode(',', $groupRole));
        }
        $insertData = [];
        foreach ($groupRole as $key => $value) {
           $insertData[] = ['group_id' => $groupId, 'role_id' => $value];
        }
        return app($this->publicGroupRoleRepository)->insertMultipleData($insertData);
    }
    private function addPublicGroupMember($groupId, $groupMember)
    {
        if (!$groupMember) {
            return true;
        }
        if (!is_array($groupMember)) {
            $groupMember = array_filter(explode(',', $groupMember));
        }
        $insertData = [];
        foreach ($groupMember as $key => $value) {
           $insertData[] = ['group_id' => $groupId, 'user_id' => $value];
        }
        return app($this->publicGroupMemberRepository)->insertMultipleData($insertData);
    }

    /**
     * 编辑公共用户组
     *
     * @param array $data
     *
     * @return bool
     *
     * @author 喻威
     *
     * @since 2015-10-20
     */
    public function editPublicGroup($data)
    {
        $publicGroupData = app($this->publicGroupRepository)->infoPublicGroup($data['group_id']);
        if (count($publicGroupData) == 0) {
            return ['code' => ['0x032002', 'public_group']];
        }

        if ($data['group_type'] == trans("public_group.all_people")) {
            $data["group_user_id"] = $data["group_role_id"] = $data["group_dept_id"] = "";
        } else {
            $data['group_type'] = '1';
        }
        $publicGroupPyArray = convert_pinyin($data["group_name"]);
        $data["group_name_py"] = $publicGroupPyArray[0];
        $data["group_name_zm"] = $publicGroupPyArray[1];
        $data['group_number'] = $data['group_number'] ?? 0;
        $publicGroupData = array_intersect_key($data, array_flip(app($this->publicGroupRepository)->getTableColumns()));
        // 数据先删再增加
        $this->updatePublicGroupRelationData($data);
        return app($this->publicGroupRepository)->updateData($publicGroupData, ['group_id' => $data['group_id']]);
    }
    private function updatePublicGroupRelationData($data)
    {
        app($this->publicGroupUserRepository)->deleteByWhere(['group_id' => [$data['group_id']]]);
        app($this->publicGroupDeptRepository)->deleteByWhere(['group_id' => [$data['group_id']]]);
        app($this->publicGroupRoleRepository)->deleteByWhere(['group_id' => [$data['group_id']]]);
        app($this->publicGroupMemberRepository)->deleteByWhere(['group_id' => [$data['group_id']]]);
        // 用户部门角色和组成员添加
        $this->addPublicGroupUser($data['group_id'], $data["group_user_id"]);
        $this->addPublicGroupRole($data['group_id'], $data["group_role_id"]);
        $this->addPublicGroupDept($data['group_id'], $data["group_dept_id"]);
        $this->addPublicGroupMember($data['group_id'], $data["group_member"]);
        return true;
    }
    /**
     * 删除公共用户组
     *
     * @param array $data
     *
     * @return bool
     *
     * @author 喻威
     *
     * @since 2015-10-20
     */
    public function deletePublicGroup($data)
    {

        $destroyIds = explode(",", $data['group_id']);
        $where = [
            'group_id' => [$destroyIds, 'in']
        ];
        app($this->publicGroupUserRepository)->deleteByWhere($where);
        app($this->publicGroupDeptRepository)->deleteByWhere($where);
        app($this->publicGroupRoleRepository)->deleteByWhere($where);
        app($this->publicGroupMemberRepository)->deleteByWhere($where);
        return app($this->publicGroupRepository)->deleteByWhere($where);
    }

    /**
     * 获取当前公共用户组的明细
     *
     * @param array $data
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-20
     */
    public function getOnePublicGroup($group_id)
    {
        $result = app($this->publicGroupRepository)->getGroupDetail($group_id);
        $result = $result[0] ?? [];

        if (isset($result['group_has_many_role'])) {
            $result['group_role_id'] = array_column($result['group_has_many_role'], 'role_id');
        }
        if ($result['group_type'] == 0) {
            $result['group_type'] = trans("public_group.all_people");
        } else {
            $result['group_type'] = trans("public_group.specified_range");
        }
        if (isset($result['group_has_many_dept'])) {
            $result['group_dept_id'] = array_column($result['group_has_many_dept'], 'dept_id');
        }
        $result['group_member'] = isset($result['group_has_many_member']) ? array_column($result['group_has_many_member'], 'user_id') : '';
        $result['group_user_id'] = isset($result['group_has_many_user']) ? array_column($result['group_has_many_user'], 'user_id') : '';
        array_multisort($result['group_member'], SORT_ASC, SORT_STRING);
        array_multisort($result['group_user_id'], SORT_ASC, SORT_STRING);

        return $result;
    }

    /**
     * 获取公共用户组的成员列表
     *
     * @return array
     *
     * @author 缪晨晨
     *
     * @since 2017-10-24
     */
    public function getOnePublicGroupUserList($groupId, $params = array()){
        $params = $this->parseParams($params);
        if($groupId == 0) {
            return ['code' => ['0x032003', 'public_group']];
        }
        $publicGroupDetail = app($this->publicGroupRepository)->getGroupDetail($groupId);
        if (isset($publicGroupDetail['group_has_many_member']) && !empty($publicGroupDetail['group_has_many_member'])) {
            $userIdArray = array_column($publicGroupDetail['group_has_many_member'], 'user_id');
            if (isset($params['search']['user_id'][0]) && !empty($params['search']['user_id'][0])) {
                if (isset($params['search']['user_id'][1]) && $params['search']['user_id'][1] == 'not_in') {
                    if (is_array($params['search']['user_id'][0])) {
                        $userIdNewArray = array_diff($userIdArray, $params['search']['user_id'][0]);
                    } else {
                        $userIdNewArray = array_diff(explode(',', $userIdArray), $params['search']['user_id'][0]);
                    }
                } else {
                    if (is_array($params['search']['user_id'][0])) {
                        $userIdNewArray = array_intersect($userIdArray, $params['search']['user_id'][0]);
                    } else {
                        $userIdNewArray = array_intersect(explode(',', $userIdArray), $params['search']['user_id'][0]);
                    }
                }
                $params['search']['user_id'][0] = $userIdNewArray;
                $params['search']['user_id'][1] = 'in';
            } else {
                $params['search']['user_id'][0] = $userIdArray;
                $params['search']['user_id'][1] = 'in';
            }

            return app($this->userService)->userSystemList($params);
        } else {
            return ['list' => [], 'total' => 0];
        }
    }

    //外部使用
    public function getGroups($own)
    {
        return app($this->publicGroupRepository)->getGroups($own);
    }

}
