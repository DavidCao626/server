<?php
namespace App\EofficeApp\Contract\Repositories;

use App\EofficeApp\Base\BaseRepository;
use DB;

/**
 * 合同分享
 * @author linlm
 * @since  2017-12-13
 */
class ContractShareRepository extends BaseRepository
{

    const TABLE_SHARE_USER = 'contract_t_share_user';
    const TABLE_SHARE_ROLE = 'contract_t_share_role';
    const TABLE_SHARE_DEPT = 'contract_t_share_department';



    /**
     * 插入分享数据
     *
     * @param type $contractIds
     * @param type $fields
     * @param type $withTrashed
     * @return type
     */
    public static function insertShareIds($data,$table){

        return DB::table($table)->insert($data);

    }

    /**
     * 删除分享数据
     *
     * @param type $contractIds
     * @param type $fields
     * @param type $withTrashed
     * @return type
     */
    public static function deleteShareIds($ids,$table){
        return DB::table($table)->whereIn('contract_t_id',$ids)->delete();
    }

    /**
     * 获取部门分享id
     *
     * @param type $contractIds
     * @param type $fields
     * @param type $withTrashed
     * @return type
     */
    public static function getShareDeptIds($id){
        return DB::table(self::TABLE_SHARE_DEPT)->where('contract_t_id',$id)->get()->toArray();
    }

    /**
     * 获取角色分享id
     *
     * @param type $contractIds
     * @param type $fields
     * @param type $withTrashed
     * @return type
     */
    public static function getShareRoleIds($id){
        return DB::table(self::TABLE_SHARE_ROLE)->where('contract_t_id',$id)->get()->toArray();
    }

    /**
     * 获取用户分享id
     *
     * @param type $contractIds
     * @param type $fields
     * @param type $withTrashed
     * @return type
     */
    public static function getShareUserIds($id){
        return DB::table(self::TABLE_SHARE_USER)->where('contract_t_id',$id)->get()->toArray();
    }

    /**
     * 获取当前部门下分享的合同id
     *
     * @param type $contractIds
     * @param type $fields
     * @param type $withTrashed
     * @return type
     */
    public static function getShareDeptContractIds($dept_id){
        return DB::table(self::TABLE_SHARE_DEPT)->where('dept_id',$dept_id)->pluck('contract_t_id')->toArray();
    }

    /**
     * 获取当前角色下分享的合同id
     *
     * @param type $contractIds
     * @param type $fields
     * @param type $withTrashed
     * @return type
     */
    public static function getShareRoleContractIds($role_id){
        return DB::table(self::TABLE_SHARE_ROLE)->whereIn('role_id',$role_id)->pluck('contract_t_id')->toArray();
    }


    /**
     * 获取当前用户下分享的合同id
     *
     * @param type $contractIds
     * @param type $fields
     * @param type $withTrashed
     * @return type
     */
    public static function getShareUserContractIds($user_id){
        return DB::table(self::TABLE_SHARE_USER)->where('user_id',$user_id)->pluck('contract_t_id')->toArray();
    }


}
