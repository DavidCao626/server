<?php

namespace App\EofficeApp\PublicGroup\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\PublicGroup\Entities\PublicGroupEntity;

/**
 * 公共用户组 资源库
 *
 * @author:喻威
 *
 * @since：2015-10-19
 *
 */
class PublicGroupRepository extends BaseRepository {

    private $user_id;
    private $role_id;
    private $dept_id;

    public function __construct(PublicGroupEntity $entity) {
        parent::__construct($entity);
    }

    /**
     * 获取公共用户组列表
     *
     * @param array $param
     *
     * @author 喻威
     *
     * @since 2015-10-19
     */
    public function getPublicGroupManageList($param) {
        $default = [
            'fields' => ['*'],
            'page' => 0,
            'limit' => config('eoffice.pagesize'),
            'search' => [],
            'order_by' => ['group_number' => 'asc' ,'group_id' => 'asc'],
        ];
        $param = array_merge($default, array_filter($param));
        $param['order_by'] = array_merge($param['order_by'], ['group_id' => 'asc']);
        $query = $this->entity->select($param['fields'])->with('groupHasManyMember');
        return $query->wheres($param['search'])
                        ->orders($param['order_by'])
                        ->forPage($param['page'], $param['limit'])
                        ->get()->toArray();
    }

    public function getPublicGroupManageListTotal($param) {
        $default = [
            'search' => []
        ];
        $param = array_merge($default, array_filter($param));
        $query = $this->entity;
        return $query->wheres($param['search'])->count();
    }
    /**
     * 获取公共用户组列表
     *
     * @param array $param
     *
     * @author 喻威
     *
     */
    public function getPublicGroupList($param) {
        if (isset($param['search']['user_accounts'])) {
            unset($param['search']['user_accounts']);
        }
        $default = [
            'fields' => ['*'],
            'page' => 0,
            'limit' => config('eoffice.pagesize'),
            'search' => [],
            'order_by' => ['group_number' => 'asc' ,'group_id' => 'asc'],
        ];
        $param = array_merge($default, array_filter($param));
        //当前用户 要有权限
        $this->user_id = $param["auth_user"];
        $this->dept_id = $param["auth_dept"];
        $this->role_id = $param["auth_role"];

        $query = $this->entity->select($param['fields']);

        if (isset($param["group_member"]) && $param["group_member"]) {
            $query =  $query->WhereExists(function ($query) use($param) {
                            $query->select(['public_group_member.group_id'])
                                    ->from('public_group_member')
                                    ->where('public_group_member.user_id', $param["group_member"])
                                    ->whereRaw('public_group_member.group_id=public_group.group_id');
                        });
        }else {
            $query =  $query->with('groupHasManyMember');
        }
        $query = $query->where(function($query) use($param) {
            $query->where('group_type', 0)->orWhereExists(function ($query) use($param) {
                            $query->select(['public_group_user.group_id'])
                                    ->from('public_group_user')
                                    ->where('public_group_user.user_id', $param["auth_user"])
                                    ->whereRaw('public_group_user.group_id=public_group.group_id');
                        })->orWhereExists(function ($query) use($param) {
                            $query->select(['public_group_role.group_id'])
                                    ->from('public_group_role')
                                    ->whereIn('public_group_role.role_id', $param["auth_role"])
                                    ->whereRaw('public_group_role.group_id=public_group.group_id');
                        })->orWhereExists(function ($query) use($param) {
                            $query->select(['public_group_dept.group_id'])
                                    ->from('public_group_dept')
                                    ->where('public_group_dept.dept_id', $param["auth_dept"])
                                    ->whereRaw('public_group_dept.group_id=public_group.group_id');
                        });
            });

        return $query->multiWheres($param['search'])
                        ->orders($param['order_by'])
                        ->parsePage($param['page'], $param['limit'])
                        ->get()->toArray();
    }

    public function getPublicGroupListTotal($param) {

        if (isset($param['search']['user_accounts'])) {
            unset($param['search']['user_accounts']);
        }
        $default = [
            'search' => []
        ];
        
        $param = array_merge($default, array_filter($param));
        $this->user_id = $param["auth_user"];
        $this->dept_id = $param["auth_dept"];
        $this->role_id = $param["auth_role"];
        $query = $this->entity;
        if (isset($param["group_member"]) && $param["group_member"]) {
            $query =  $query->WhereExists(function ($query) use($param) {
                            $query->select(['public_group_member.group_id'])
                                    ->from('public_group_member')
                                    ->where('public_group_member.user_id', $param["group_member"])
                                    ->whereRaw('public_group_member.group_id=public_group.group_id');
                        });
        }
        $query = $query->where(function($query) use($param) {
            $query->where('group_type', 0)->orWhereExists(function ($query) use($param) {
                            $query->select(['public_group_user.group_id'])
                                    ->from('public_group_user')
                                    ->where('public_group_user.user_id', $param["auth_user"])
                                    ->whereRaw('public_group_user.group_id=public_group.group_id');
                        })->orWhereExists(function ($query) use($param) {
                            $query->select(['public_group_role.group_id'])
                                    ->from('public_group_role')
                                    ->whereIn('public_group_role.role_id', $param["auth_role"])
                                    ->whereRaw('public_group_role.group_id=public_group.group_id');
                        })->orWhereExists(function ($query) use($param) {
                            $query->select(['public_group_dept.group_id'])
                                    ->from('public_group_dept')
                                    ->where('public_group_dept.dept_id', $param["auth_dept"])
                                    ->whereRaw('public_group_dept.group_id=public_group.group_id');
                        });
            });
        return $query->multiWheres($param['search'])->count();
    }

    public function getGroups($param){

        //当前用户 要有权限
        $this->user_id = $param["user_id"];
        $this->role_id = trim($param["role_id"],",");
        $this->dept_id = $param["dept_id"];



        $query = $this->entity;

        $query =  $query->WhereExists(function ($query) use($param) {
                        $query->select(['public_group_member.group_id'])
                                ->from('public_group_member')
                                ->where('public_group_member.user_id', $param["user_id"])
                                ->whereRaw('public_group_member.group_id=public_group.group_id');
                    });
        $query = $query->where(function($query) use($param) {
            $query->where('group_type', 0)->orWhereExists(function ($query) use($param) {
                            $query->select(['public_group_user.group_id'])
                                    ->from('public_group_user')
                                    ->where('public_group_user.user_id', $param["user_id"])
                                    ->whereRaw('public_group_user.group_id=public_group.group_id');
                        })->orWhereExists(function ($query) use($param) {
                            $query->select(['public_group_role.group_id'])
                                    ->from('public_group_role')
                                    ->whereIn('public_group_role.role_id', explode(',', $param["role_id"]))
                                    ->whereRaw('public_group_role.group_id=public_group.group_id');
                        })->orWhereExists(function ($query) use($param) {
                            $query->select(['public_group_dept.group_id'])
                                    ->from('public_group_dept')
                                    ->where('public_group_dept.dept_id', $param["dept_id"])
                                    ->whereRaw('public_group_dept.group_id=public_group.group_id');
                        });
            });

        return $query->get()->toArray();
    }

    /**
     * 获取具体的控制规则
     *
     * @param type $id
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function infoPublicGroup($id) {
        $result = $this->entity->where('group_id', $id)->get()->toArray();
        return $result;
    }
    public function getGroupDetail($groupId)
    {
        return $this->entity
                    ->with('groupHasManyUser')
                    ->with('groupHasManyDept')
                    ->with('groupHasManyRole')
                    ->with('groupHasManyMember')
                    ->where('group_id', $groupId)
                    ->get()->toArray();
    }

}
