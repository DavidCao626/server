<?php
namespace App\EofficeApp\User\Repositories;

use App\EofficeApp\User\Entities\UserStatusEntity;
use App\EofficeApp\Base\BaseRepository;
use DB;

/**
 * 用户状态表知识库
 *
 * @author 丁鹏
 *
 * @since  2015-10-16 创建
 */
class UserStatusRepository extends BaseRepository
{
    public function __construct(UserStatusEntity $entity) {
        parent::__construct($entity);
    }

    /**
     * 用户状态管理--获取用户状态列表
     * @param  [array] $param 查询条件等其他参数
     * @return [array or string]
     */
    public function userStatusListRepository(array $param=[]) {
        $default = [
            'fields'     => ['*'],
            'page'       => 0,
            'limit'      => config('eoffice.pagesize'),
            'search'     => [],
            'order_by'   => ['status_id'=>'ASC'],
            'returntype' => 'array'
        ];

        $param = array_merge($default, $param);
        // 自定义字段的只传了status_name字段，后端要翻译必须要查出status_id
        $param['fields'] = ['*'];

        // 多语言查询支持 对status_name字段的查询转换为lang表lang_value字段的查询
        if (isset($param['lang_table']) && !empty($param['lang_table']) && isset($param['search']['status_name']) && !empty($param['search']['status_name'])) {
            $tempSearchParam = [
                'lang_value' => $param['search']['status_name'],
                'table' => ['user_status']
            ];
            $tempQuery = DB::table($param['lang_table']);
            $tempQuery = $this->parseWheres($tempQuery, $tempSearchParam);
            $langKeys  = $tempQuery->get()->pluck('lang_key')->toArray();
            if (!empty($langKeys)) {
                $param['search']['status_name'] = [$langKeys, 'in'];
            } else {
                return [];
            }
        }

        $query = $this->entity
                    ->select($param['fields'])
                    ->wheres($param['search'])
                    ->orders($param['order_by'])
                    ->parsePage($param['page'], $param['limit'])
                    ->with(["userStatusHasManySystemInfo" => function ($query) {
                        $query->select("user_id","user_status");
                    }]);

        // 返回值类型判断
        if ($param["returntype"] == "array") {
            return $query->get()->toArray();
        } else if ($param["returntype"] == "count") {
            return $query->get()->count();
        } else if ($param["returntype"] == "object") {
            return $query->get();
        }
    }

    /**
     * 用户状态管理--获取用户状态列表数量
     * @param  [array] $param 查询条件等其他参数
     * @return [array or string]
     */
    public function userStatusTotalRepository(array $param=[])
    {
        $param["page"] = 0;
        $param["returntype"] = "count";
        return $this->userStatusListRepository($param);
    }

    public function getUserStatusIdsArray() {
        $userStatusIdsArray = $this->entity->select(['status_id'])->get();
        $tempStatusIdsArray = array();
        if(!empty($userStatusIdsArray)) {
            foreach($userStatusIdsArray->toArray() as $id) {
                $tempStatusIdsArray[] = $id['status_id'];
            }
        }
        return $tempStatusIdsArray;
    }

    /**
     * 用户状态管理--验证用户状态占用，返回查询到的数据
     * @param  [int] $statusId 用户状态id
     * @return [obj]
     */
    public function userCountByUserStatusRepository($statusId)
    {
        return $this->entity->find($statusId)->userStatusHasManySystemInfo();
    }

    // 解析DB模式的多条件查询
    public function parseWheres($query, $wheres)
    {
        $operators = [
            'between'       => 'whereBetween',
            'not_between'   => 'whereNotBetween',
            'in'            => 'whereIn',
            'not_in'        => 'whereNotIn'
        ];

        if (empty($wheres)) {
            return $query;
        }

        foreach ($wheres as $field=>$where) {
            $operator = isset($where[1]) ? $where[1] : '=';
            $operator = strtolower($operator);
            if (isset($operators[$operator])) {
                $whereOp = $operators[$operator]; //兼容PHP7写法
                $query = $query->$whereOp($field, $where[0]);
            } else {
                $value = $operator != 'like' ? $where[0] : '%'.$where[0].'%';
                $query = $query->where($field, $operator, $value);
            }
        }
        return $query;
    }
    public function getStatusIdByStatusName($statusName) {
        return $this->entity->select('status_id')->where('status_name', $statusName)->get()->toArray();
    }
}
