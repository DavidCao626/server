<?php

namespace App\EofficeApp\OfficeSupplies\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\OfficeSupplies\Entities\OfficeSuppliesPermissionEntity;

/**
 * office_supplies_permission资源库
 *
 * @author  lixuanxuan
 *
 * @since   2018-08-29
 */
class OfficeSuppliesPermissionRepository extends BaseRepository
{
    public function __construct(OfficeSuppliesPermissionEntity $entity)
    {
        parent::__construct($entity);
    }

    /**
     * 编辑权限表
     * @param $permission_info
     * @return bool
     */
    public function modifyPermission($permission_info){
        $where = [
            'office_supplies_type_id'=>$permission_info['office_supplies_type_id'],
            'permission_type'        =>$permission_info['permission_type'],
        ];
        $permission = $this->entity->where($where)->get()->toArray();
        if(count($permission) == 0){
            //new
            $result = $this->insertData($permission_info);
        }else{
            //edit
            $result = $this->updateData($permission_info,$where);
        }
        return $result;
    }

    /**
     *
     * 通过type_id删除Permission
     */
    public function deletePermissionByTypeId($type_id){
        $where = [
            'office_supplies_type_id'        =>$type_id,
        ];
        return $this->entity->where($where)->delete();
    }

    /**
     * 获取有权限的分类类型id数组
     * @param $type_ids
     * @param $permission_type
     * @param $own
     * @return array
     */
    public function getPermission($type_ids,$permission_type,$own){
        $where = [
            'permission_type'=>$permission_type,
        ];
        $type_permission_allow = [];
        $permissions = $this->entity->where($where)->get()->toArray();
        foreach($permissions as $val){
            $type_id = $val['office_supplies_type_id'];
            if(!in_array($type_id,$type_ids)){
                continue;
            }
            if(in_array($type_id,$type_permission_allow)){
                continue;
            }
            if($val['manager_all'] == 1){
                //允许所有人
                $type_permission_allow[] = $type_id;
                continue;
            }
            $manager_dept = json_decode($val['manager_dept'],true);
            $manager_role = json_decode($val['manager_role'],true);
            $manager_user = json_decode($val['manager_user'],true);
            $own_dept_id = $own['dept_id'];
            $own_role_id_arr = $own['role_id'];
            $own_user_id = $own['user_id'];

            if(in_array($own_dept_id,$manager_dept) || in_array($own_user_id,$manager_user)  ){
                $type_permission_allow[] = $type_id;
                continue;
            }
            foreach($own_role_id_arr as $val){
                $role_id = $val;
                if(in_array($role_id,$manager_role)){
                    $type_permission_allow[] = $type_id;
                    break;
                }
            }
        }
        return $type_permission_allow;
    }

    /**
     * 获取有权限的用户
     * @param $usersInfo
     * @param $permission_type
     * @param $parent_type_id
     * @return array
     */
    public function getPermissionByUserInfo($usersInfo,$permission_type,$parent_type_id)
    {
        $where = [
            'permission_type'=>$permission_type,
        ];
        $allow_user_id = [];
        $permissions = $this->entity->where($where)->get()->toArray();

        foreach($permissions as $val){
            if($parent_type_id != $val['office_supplies_type_id']){
                continue;
            }
            if($val['manager_all'] == 1){
                //允许所有人
                foreach($usersInfo as $val){
                    $allow_user_id[] = $val['user_id'];
                }
                return $allow_user_id;
            }
            $manager_dept = json_decode($val['manager_dept'],true);
            $manager_role = json_decode($val['manager_role'],true);
            $manager_user = json_decode($val['manager_user'],true);

            foreach($usersInfo as $userInfo){
                $own_dept_id = $userInfo['dept_id'];
                $own_role_id_arr = explode(',',$userInfo['role_id']);
                $own_user_id = $userInfo['user_id'];
                if(in_array($own_dept_id,$manager_dept) || in_array($own_user_id,$manager_user)  ){
                    $allow_user_id[] = $own_user_id;
                    continue;
                }
                foreach($own_role_id_arr as $val){
                    $role_id = $val;
                    if(in_array($role_id,$manager_role)){
                        $allow_user_id[] = $own_user_id;
                        break;
                    }
                }

            }
        }
        return $allow_user_id;
    }

    /**
     * 获取有权限的type_id
     * @param $own
     * @param $permission_type
     * @return array
     */
    public function getAllowTypeByOwn($own,$permission_type)
    {
        $where = [
            'permission_type'=>$permission_type,
        ];
        $allow_types = [];
        $permissions = $this->entity->where($where)->get()->toArray();

        foreach($permissions as $val){
            $type = $val['office_supplies_type_id'];
            if($val['manager_all'] == 1){
                $allow_types[] = $type;
                continue;
            }
            $manager_dept = json_decode($val['manager_dept'],true);
            $manager_role = json_decode($val['manager_role'],true);
            $manager_user = json_decode($val['manager_user'],true);

            $own_dept_id = $own['dept_id'];
            $own_role_id_arr = $own['role_id'];
            $own_user_id = $own['user_id'];
            if(in_array($own_dept_id,$manager_dept) || in_array($own_user_id,$manager_user)  ){
                $allow_types[] = $type;
                continue;
            }
            foreach($own_role_id_arr as $role_id){
                if(in_array($role_id,$manager_role)){
                    $allow_types[] = $type;
                    break;
                }
            }

        }
        return $allow_types;

    }

}
