<?php
namespace App\EofficeApp\Contract\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Contract\Entities\ContractTypeEntity;
use DB;
/**
 * 合同信息
 * @author linlm
 * @since  2017-12-13
 */
class ContractTypeRepository extends BaseRepository
{

    const TABLE_NAME = 'contract_t_type';

    const TABLE_PERMISSION_NAME = 'contract_t_type_permissions';

    public function __construct(ContractTypeEntity $entity)
    {
        parent::__construct($entity);
    }

    /**
     * 列表
     * @param  array $param 查找条件
     * @return object
     */
    public function getLists($param)
    {
        $default = array(
            'fields'   => ['*'],
            'page'     => 0,
            'limit'    => config('eoffice.pagesize'),
            'order_by' => ['number' => 'asc', 'id' => 'asc'],
            'search'   => [],
        );

        $param = array_merge($default, $param);

        $query = $this->entity->wheres($param['search'])
            ->select($param['fields'])
            ->parsePage($param['page'], $param['limit'])
            ->orders($param['order_by']);

        return $query->get();
    }

    public function getHasPermissionIdLists($own)
    {
        $own = $own['user_info'] ?? $own;
        $result = [];
        $lists = $this->entity->select(['*'])->get();
        if ($lists->isEmpty()) {
            return $result;
        }
        $userId = isset($own['user_id']) ? strval($own['user_id']) : 0;
        $deptId = isset($own['dept_id']) ? intval($own['dept_id']) : 0;
        $roleIdArr = isset($own['role_id']) ? (array) $own['role_id'] : [];
        foreach ($lists as $key => $list) {
            if ($list->all_user) {
                $result[] = $list->id;
                continue;
            }
            $userIds = $list->user_ids ? explode(',', $list->user_ids) : [];
            if (!empty($userIds) && in_array($userId, $userIds)) {
                $result[] = $list->id;
                continue;
            }
            $deptIds = $list->dept_ids ? explode(',', $list->dept_ids) : [];
            if (!empty($deptIds) && in_array($deptId, $deptIds)) {
                $result[] = $list->id;
                continue;
            }
            $roleIds = $list->role_ids ? explode(',', $list->role_ids) : [];
            $intersects = array_intersect($roleIdArr, $roleIds);
            if (!empty($intersects)) {
                $result[] = $list->id;
                continue;
            }
        }
        return [$result];
    }

    public static function getTypeNameById($typeId = null)
    {
        $query = DB::table(self::TABLE_NAME);
        if(!$typeId){
            return $query->select('*')->get()->toArray();
        }
        if(is_array($typeId)){
            return $query->whereIn('id', $typeId)->pluck('name')->toArray();
        }else{
            return $query->where('id', $typeId)->first();
        }

    }

    public static function getValidate(){
        return DB::table(self::TABLE_NAME)->select('*')->get()->toArray();
    }

    public function getTypeLists($param)
    {
        $default = array(
            'fields'   => ['*'],
            'page'     => 0,
            'limit'    => config('eoffice.pagesize'),
            'order_by' => ['number' => 'asc', 'id' => 'asc'],
            'search'   => [],
        );

        $param = array_merge($default, $param);

        $query = $this->entity
            ->select($param['fields'])
            ->with(['permissions'=>function($query){
                $query->select('*');
            }])
            ->orders($param['order_by']);

        return $query->get();
    }

    public static function setRelationFields($id,$data){
        DB::table(self::TABLE_NAME)->where('id', $id)->update($data);
    }

    public static function getPrivilegeDetail($id){
        return DB::table(self::TABLE_PERMISSION_NAME)->where('type_id', $id)->first();
    }

    public static function upDataPermission($id,$data){
        $query = DB::table(self::TABLE_PERMISSION_NAME)->where('type_id', $id)->update($data);
        return $query;
    }

}
