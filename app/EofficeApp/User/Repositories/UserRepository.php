<?php

namespace App\EofficeApp\User\Repositories;

use App\EofficeApp\User\Entities\UserEntity;
use App\EofficeApp\User\Entities\UserSystemInfoEntity;
use App\EofficeApp\Role\Entities\UserRoleEntity;
use App\EofficeApp\Base\BaseRepository;
use DB;
use Illuminate\Database\Eloquent\Collection;

/**
 * 用户表知识库
 *
 * @author 丁鹏
 *
 * @since  2015-10-16 创建
 */
class UserRepository extends BaseRepository {

    const TABLE_USER_INFO = 'user_system_info';
    const TABLE_USER_ROLE = 'user_role';
    private $UserRoleEntity;
    private $UserSystemInfoEntity;
    const PIN_YIN = [
        'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n',
        'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z'
    ];

    public function __construct(UserEntity $entity ,UserRoleEntity $UserRoleEntity,UserSystemInfoEntity $UserSystemInfoEntity ) {
        parent::__construct($entity);
        $this->UserSystemInfoEntity = $UserSystemInfoEntity;
        $this->UserRoleEntity = $UserRoleEntity;
    }

    /**
     * 获取用户名
     * @param  string $userId 必填，用户id
     * @return string         this user name
     */
    public function getUserName($userId) {
        if ($user = $this->entity->select('user_name')->find($userId)) {
            return $user->user_name;
        }
        return '';
    }
    /**
     * 获取用户名
     * @param  string $userId 必填，用户id
     * @return string         this user name
     */
    public function getUserNames($userId) {
        return $this->entity->select('user_id', 'user_name')
                            ->whereIn('user_id', $userId)
                            ->get();
    }

    /**
     * 过滤掉用户ID数组中离职和删除的用户，返回非离职和删除的用户ID
     *
     * @author 缪晨晨
     *
     * @param array $userIdArray
     *
     * @return array 过滤后的用户ID
     */
    public function filterLeaveOffAndDeletedUserId($userIdArray) {
        if (empty($userIdArray) || !is_array($userIdArray)) {
            return [];
        }
        $result = $this->entity->select(['user_id'])
                               ->whereIn('user_id', $userIdArray)
                               ->where('user_accounts', '!=', '')
                               ->get()
                               ->pluck("user_id")->toArray();
        if (empty($result)) {
            return [];
        } else {
            return $result;
        }
    }

    /**
     * @根据条件判断该用户在user表是否存在
     *
     * @author miaochenchen
     *
     * @param array $where
     *
     * @return boolean
     */
    public function judgeUserExists($where) {
        $result = $this->entity->select(['user_id'])
                               ->multiWheres($where)
                               ->whereHas('userHasOneSystemInfo', function($query) {
                                    $query = $query->where('user_status', '>', '0')->where('user_status', '!=', '2');
                               })
                               ->first();
        if (empty($result)) {
            return false;
        } else {
            return $result['user_id'];
        }
    }

    /**
     * @根据账号获取用户信息
     *
     * @author 李志军
     *
     * @param string $userAccount
     *
     * @return object 用户对象
     */
    public function getUserByAccount($userAccount) {
        return $this->entity->where('user_accounts', $userAccount)->first();
    }
    public function getSimpleUserTotal($param, $includeLeaveUser = false)
    {
        return $this->handleSimpleUserQuery($param, $includeLeaveUser, function() {
            return $this->entity->leftJoin('user_system_info', 'user_system_info.user_id', '=', 'user.user_id');
        }, function($query) {
            return $query->count();
        });
    }
    private function handleSimpleUserQuery($param, $includeLeaveUser, $before, $terminal)
    {
        $query = $before($param);

        $query->where('user_system_info.user_status', '!=', 0);

        if (!$includeLeaveUser) {
            $query->where('user_system_info.user_status', '!=', 2); //包含离职人员
        }
        if (isset($param['dept_id']) && $param['dept_id']) {
            if (is_array($param['dept_id'])) {
                $query->whereIn('user_system_info.dept_id', $param['dept_id']);
            } else {
                $query->where('user_system_info.dept_id', $param['dept_id']);
            }
        }
        if (isset($param['user_name']) && $param['user_name'] != '') {
            $query->where('user.user_name', 'like', '%' . $param['user_name'] . '%');
        }

        if (isset($param['user_id']) && $param['user_id']) {
            if (is_array($param['user_id'])) {
                $query->whereIn('user.user_id', $param['user_id']);
            } else {
                $query->where('user.user_id', $param['user_id']);
            }
        }

        return $terminal($query);
    }
    public function getSimpleUserList($param,$includeLeaveUser = false)
    {
        return $this->handleSimpleUserQuery($param, $includeLeaveUser, function(){
            return $this->entity->select(['user_accounts','user.user_id', 'user.user_name_zm', 'user.user_name_py', 'user.user_name', 'department.dept_id', 'department.dept_name', 'user.user_position'])
                    ->leftJoin('user_system_info', 'user_system_info.user_id', '=', 'user.user_id')
                    ->leftJoin('user_info', 'user.user_id', '=', 'user_info.user_id')
                    ->leftJoin('department', 'department.dept_id', '=', 'user_system_info.dept_id');
        }, function($query) use($param){
            $limit		= (isset($param['limit']) && $param['limit']) ? $param['limit'] : config('eoffice.pagesize');

            $page		= (isset($param['page']) && $param['page']) ? $param['page'] : 1;

            $orderBy	= (isset($param['order_by']) && !empty($param['order_by'])) ? $param['order_by'] : ['user_id' => 'asc'];

            $query->orders($orderBy);
            if(!isset($param['noPage'])){
                $query->parsePage($page, $limit);
            }

            if (isset($param["returntype"]) && $param["returntype"] == "count") {
                return $query->count();
            }
            return $query->get()->toArray();
        });
    }
    public function getAllSimpleUsers($fields) {
        return $this->entity->select($fields)->get();
    }

    public function getUserIdByFields($fields) {
        return $this->entity->select($fields)->get()->toArray();
    }
    public function getNoLeaveUsersByUserId($userIds)
    {
        return $this->entity->select(['*'])->where('user_accounts', '!=', '')->whereIn('user_id', $userIds)->get();
    }
    /**
     * @根据部门获取用户信息
     *
     * @author 李志军
     *
     * @param int $deptId
     *
     * @return Collection 用户对象数组
     */
    public function getUserByDepartment($deptId, $where = []) {
        return $this->entity->leftJoin('user_system_info', 'user_system_info.user_id', '=', 'user.user_id')
                        ->whereIn('user_system_info.dept_id', explode(',', trim($deptId,',')))
                        ->wheres($where)
                        ->get();
    }
    /**
     * @根据部门获取假删除和离职用户信息
     *
     * @author 李旭
     *
     * @param int $deptId
     *
     * @return array 用户对象数组
     */
    public function getNotLeaveUserByDepartment($deptId, $where = []) {
        return $this->entity->leftJoin('user_system_info', 'user_system_info.user_id', '=', 'user.user_id')
                        ->where('user_system_info.dept_id', $deptId)
                        ->whereNotIn("user_system_info.user_status", [0, 2])
                        ->get()
                        ->toArray();
    }

    /**
     * @根据部门及子部门id获取用户信息
     *
     * @author 牛晓克
     *
     * @param array $deptId
     *
     * @return Collection 用户对象数组
     */
    public function getUserByAllDepartment($deptId, $where = []) {
        return $this->entity->leftJoin('user_system_info', 'user_system_info.user_id', '=', 'user.user_id')
            ->whereIn('user_system_info.dept_id', $deptId)
            ->wheres($where)
            ->get();
    }

    /**
     * 获取多个用户名
     *
     * @param  array $userId 用户id
     *
     * @return array 用户名列表
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    public function getUsersNameByIds(array $userId) {
        return $this->entity->whereIn('user_id', $userId)->pluck('user_name')->toArray();
    }

    /**
     * 获取用户手机号码
     *
     * @param  array $userId 用户id
     *
     * @return array 用户名列表
     *
     * @author qishaobo
     *
     * @since  2017-03-07
     */
    public function getUserWithInfoByIds(array $userId, $userField = ['*'], $userInfoField = ['*']) {
        $query = $this->entity
                ->whereIn('user_id', $userId)
                ->select($userField);

        if (!empty($userInfoField)) {
            $query = $query->with(['userHasOneInfo' => function($query) use ($userInfoField) {
                    $query->select($userInfoField);
                }]);
        }

        return $query->withTrashed()->get()->toArray();
    }

    /**
     * 获取用户列表
     *
     * @param  array $param
     * @return array
     */
    public function getUserList(array $param) {
        // 解析传过来的角色id
        $roleId = isset($param["search"]["role_id"]) ? $param["search"]["role_id"][0] : false;
        // 解析传过来的部门id
        $deptId = isset($param["search"]["dept_id"]) ? $param["search"]["dept_id"][0] : false;
        // 解析传过来的用户id
        $userId = isset($param["search"]["user_id"]) ? $param["search"]["user_id"][0] : false;
        // 用户列表的高级查询条件
        $userSex     = isset($param["search"]["user_sex"]) ? $param["search"]["user_sex"][0] : false; // user_info
        $userStatus  = isset($param["search"]["user_status"]) ? $param["search"]["user_status"][0] : false; // user_system_info
        $dutyType    = isset($param["search"]["attendance_scheduling"]) ? $param["search"]["attendance_scheduling"][0] : false; // user_system_info
        $postPriv    = isset($param["search"]["post_priv"]) ? $param["search"]["post_priv"][0] : false; // user_system_info
        $deptName    = isset($param['search']['dept_name']) ? $param['search']['dept_name'][0] : false;
        $jobNunber   = isset($param['search']['user_job_number']) ? $param['search']['user_job_number'][0] : false;
        $email       = isset($param['search']['email']) ? $param['search']['email'][0] : false;
        $phoneNumber = isset($param['search']['phone_number']) ? $param['search']['phone_number'][0] : false;
        $created    = isset($param['search']['created_at']) ? $param['search']['created_at'][0] : false;
        // 首字母查询
        $firstZm     = isset($param['search']['first_zm']) ? $param['search']['first_zm'][0] : false;
        // 是否进行关联查询
        $withAble = isset($param["fields"]) ? true : false;
        $default = [
            'fields' => ["user.user_id", "user_accounts", "user_name", "user_name_py", "user_name_zm", "user_job_number", "list_number", "user_position", "user_area", "user_city", "user_workplace", "user_job_category", "user_info.*"],
            'page' => 0,
            'limit' => config('eoffice.pagesize'),
            'search' => [],
            'order_by' => ['list_number' => 'ASC', 'user.user_id' => 'ASC'],
            'returntype' => 'array',
        ];

        $param = array_merge($default, $param);

        // user和user_info表联查 处理user_id字段
        if (is_array($param['fields'])) {
            $userIdKey = array_search("user_id",$param['fields']);
            if ($userIdKey >= 0) {
                $param['fields'][$userIdKey] = 'user.user_id';
            }
        }
        if (isset($param['order_by']['user_id'])) {
            $param['order_by']['user.user_id'] = $param['order_by']['user_id'];
            unset($param['order_by']['user_id']);
        }
        // $param['selector'] = 'selector';
        $query = $this->entity;

        if ($roleId !== false) {
            $query = $query->whereHas("userHasManyRole", function ($query) use($roleId, $param) {
                if (is_array($roleId) || is_object($roleId)) {
                    if(isset($param["search"]["role_id"][1]) && $param["search"]["role_id"][1] == 'not_in') {
                        $query->whereNotIn('role_id', $roleId);
                    }else{
                        $query->whereIn('role_id', $roleId);
                    }
                } else {
                    if(isset($param["search"]["role_id"][1]) && $param["search"]["role_id"][1] == 'not_in') {
                        $query->whereNotIn('role_id', explode(",", trim($roleId, ",")));
                    }else{
                        $query->whereIn('role_id', explode(",", trim($roleId, ",")));
                    }
                }
            });
            unset($param["search"]["role_id"]);
        }
        if ($deptId !== false) {
            unset($param["search"]["dept_id"]);
            $query = $query->whereHas("userHasOneSystemInfo.userSystemInfoBelongsToDepartment", function ($query) use($deptId) {
                if (is_array($deptId) || is_object($deptId)) {
                    $query->whereIn('dept_id', $deptId);
                } else {
                    $query->whereIn('dept_id', explode(",", trim($deptId, ",")));
                }
            });
        }
        if ($userId !== false) {
            if (!(is_array($userId) || is_object($userId))) {
                $param["search"]["user_id"][0] = explode(",", trim($param["search"]["user_id"][0], ","));
            }
            if (isset($param["search"]["user_id"][1]) && $param["search"]["user_id"][1] == 'not_in') {
                $query = $query->whereNotIn('user.user_id', $param["search"]["user_id"][0]);
            } else {
                $query = $query->whereIn('user.user_id', $param["search"]["user_id"][0]);
            }
            unset($param["search"]["user_id"]);
        }
        if ($userSex !== false) {
            unset($param["search"]["user_sex"]);
            $query = $query->whereHas("userHasOneInfo", function ($query) use($userSex) {
                $query->where('sex', $userSex);
            });
        }
        if ($userStatus !== false) {
            unset($param["search"]["user_status"]);
            $query = $query->whereHas("userHasOneSystemInfo", function ($query) use($userStatus) {
                $query->where('user_status', $userStatus);
            });
        }
        if ($dutyType !== false) {
            unset($param["search"]["attendance_scheduling"]);
            $query = $query->whereHas("userHasOneAttendanceSchedulingInfo", function ($query) use($dutyType) {
                $query->where('scheduling_id', $dutyType);
            });
        }
        if ($postPriv !== false) {
            unset($param["search"]["post_priv"]);
            $query = $query->whereHas("userHasOneSystemInfo", function ($query) use($postPriv) {
                $query->where('post_priv', $postPriv);
            });
        }
        if ($deptName !== false) {
            unset($param["search"]["dept_name"]);
            $query = $query->whereHas("userHasOneSystemInfo.userSystemInfoBelongsToDepartment", function ($query) use($deptName) {
                $query->where('dept_name', 'like', '%' . $deptName . '%');
            });
        }
        if ($jobNunber !== false) {
            unset($param["search"]["user_job_number"]);
            $query->where('user_job_number', 'like', '%' . $jobNunber . '%');
        }
        if ($email !== false) {
            $tempWhere = [
                'search' => [
                    'email' => $param["search"]["email"]
                ]
            ];
            unset($param["search"]["email"]);
            $query = $query->whereHas("userHasOneInfo", function ($query) use($tempWhere) {
                $query->wheres($tempWhere['search']);
            });
        }
        if ($phoneNumber !== false) {
            // 手机号模糊匹配 只要搜索输入的内容中数字顺序一致就可以模糊匹配
            if (isset($param["search"]["phone_number"][1]) && $param["search"]["phone_number"][1] == 'like') {
                if (is_string($param["search"]["phone_number"][0])) {
                    $param["search"]["phone_number"][0] = trim(chunk_split($param["search"]["phone_number"][0],1,"%"), '%');
                }
            }
            $tempWhere = [
                'search' => [
                    'phone_number' => $param["search"]["phone_number"]
                ]
            ];
            unset($param["search"]["phone_number"]);
            $query = $query->whereHas("userHasOneInfo", function ($query) use($tempWhere) {
                $query->wheres($tempWhere['search']);
            });
        }
        if ($created !== false) {
            $start = isset($created['startDate']) ? $created['startDate'] : '';
            $end = isset($created['endDate']) ? $created['endDate'] : '';
            $query = $query->whereBetween('user.created_at',[$start, $end]);
            unset($param['search']['created_at']);
        }
        // 手机号模糊匹配 只要搜索输入的内容中数字顺序一致就可以模糊匹配
        if (isset($param["search"]['multiSearch']["phone_number"][1]) && $param["search"]['multiSearch']["phone_number"][1] == 'like') {
            if (is_string($param["search"]['multiSearch']["phone_number"][0])) {
                $param["search"]['multiSearch']["phone_number"][0] = trim(chunk_split($param["search"]['multiSearch']["phone_number"][0],1,"%"), '%');
            }
        }
        if ($firstZm !== false) {
            if ($firstZm == '#') {
                $query = $query->where(function ($query) {
                    $query->orWhereRaw("left(user_name_zm,1) > 'z'")
                        ->orWhereRaw("left(user_name_zm,1) < 'a'");
                });
            } else {
                // 通过白名单控制前端传递的字符都在该拼音字母数组内
                if(in_array(strtolower($firstZm), self::PIN_YIN)) {
                    $query = $query->whereRaw("left(user_name_zm,1) = '".$firstZm."'");
                }
            }
            unset($param["search"]["first_zm"]);
        }
        // 是否包含离职人员
        if (!(isset($param['include_leave']) && $param['include_leave'])) {
            $query = $query->whereHas("userHasOneSystemInfo", function ($query) use($param){
                if(isset($param['with_trashed']) && $param['with_trashed']) {
                    $query->where('user_status', '!=', '2')->withTrashed();
                }else{
                    $query->where('user_status', '!=', '2');
                }
            });
        }
        // 解析原生 where
        if (isset($param['whereRaw'])) {
            foreach ($param['whereRaw'] as $key => $whereRaw) {
                $query = $query->whereRaw($whereRaw);
            }
        }
        // 解析原生 select
        if (isset($param['selectRaw'])) {
            foreach ($param['selectRaw'] as $key => $selectRaw) {
                $query = $query->selectRaw($selectRaw);
            }
        } else if (isset($param['selector']) && $param['selector']) {
            $query = $query->select(['user.user_id', 'user.user_name', 'user.user_name_py', 'user.user_name_zm']);
        }
        else {
            $query = $query->select($param['fields']);
        }

        $query = $query->multiWheres($param['search'])
                ->orders($param['order_by']);

        if(isset($param['with_trashed']) && $param['with_trashed'] && !$withAble) {
            $query = $query->with(['userHasManyRole' => function($query) {
            }, 'userHasManyRole.hasOneRole' => function($query) {
            }, 'userHasOneInfo' => function($query) {
                $query->withTrashed();
            }, 'userHasOneSystemInfo' => function($query) {
                $query->withTrashed();
            }, 'userHasOneSystemInfo.userSystemInfoBelongsToDepartment' => function($query) {
            }, 'userHasOneAttendanceSchedulingInfo'])->withTrashed();
        } else if (isset($param['selector']) && $param['selector'] && !$withAble) {
            $query = $query->with(['userHasManyRole' => function($query) {
                $query->select(['role_id', 'user_id']);
            }, 'userHasManyRole.hasOneRole' => function($query) {
                $query->select(['role_id', 'role_name']);
            }, 'userHasOneInfo' => function($query) {
                $query->select('user_info.user_id');
            }, 'userHasOneSystemInfo' => function($query){
                $query->select('user_status');
            }, 'userHasOneSystemInfo' => function($query){
                $query->select(['user_id', 'dept_id', 'user_status']);
            }, 'userHasOneSystemInfo.userSystemInfoBelongsToDepartment' => function($query) {
                $query->select(['dept_id', 'dept_name']);
            }]);
        } else if (!$withAble){
            $query = $query->with("userHasManyRole", "userHasManyRole.hasOneRole", 'userHasOneInfo', 'userHasOneSystemInfo', 'userHasOneSystemInfo.userSystemInfoBelongsToDepartment', 'userHasOneAttendanceSchedulingInfo');
        }

        if(isset($param['include_supervisor']) && $param['include_supervisor']) {
            $query = $query->with(['userHasManySuperior.superiorHasOneUser' => function ($query) {
                // 上级
                $query->select('user_id', 'user_name');
            }, 'userHasManySubordinate.subordinateHasOneUser' => function ($query) {
                // 下级
                $query->select('user_id', 'user_name');
            }]);
        }

        // 关联user_info表查询，手机号码查询
        $query = $query->join('user_info', 'user.user_id', '=', 'user_info.user_id');

        $query = $query->parsePage($param['page'], $param['limit']);

        // 返回值类型判断
        if ($param["returntype"] == "array") {
            return $query->get()->toArray();
        } else if ($param["returntype"] == "count") {
            return $query->count();
        } else if ($param["returntype"] == "object") {
            return $query->get();
        } else if ($param["returntype"] == 'pinyin') {
            $userListResult = $query->get()->toArray();
            // a-z
            for ($i = 65; $i < 91; $i++) {
                $userList[chr($i)] = array();
            }
            // 非a-z
            $numberList = array();
            if (!empty($userListResult)) {
                foreach ($userListResult as $key => $value) {
                    $userNameZm = $value['user_name_zm'];
                    $userNameFirstZm = strtoupper(substr($userNameZm, 0, 1));
                    if (array_key_exists($userNameFirstZm, $userList)) {
                        array_push($userList[$userNameFirstZm], $value);
                    } else {
                        array_push($numberList, $value);
                    }
                }
            }
            $userList["#"] = $numberList;
            return $userList;
        }
    }
    /**
     * 获取组织架构用户列表
     *
     * @param array $own
     * @param array $param
     *
     * @return array
     */
    public function getOrgUserList(array $own, array $param)
    {
        $orderBy = (isset($param['order_by']) && !empty($param['order_by']))
                ? $param['order_by']
                : ['user.list_number' => 'ASC', 'department.dept_sort' => 'ASC', 'user.user_name' => 'ASC'];

        return $this->handleOrgUser($own, $param, function($own, $param){
            $fields = (isset($param['fields']) && !empty($param['fields']))
                    ? $param['fields']
                    : ["user.user_id", "user_accounts", "user_name", "user_name_py", "user_name_zm", "user_job_number", "list_number", "user_position","department.dept_name","user_info.sex",'user_info.email','user_info.phone_number','user_system_info.user_status'];

            $query = $this->entity->select($fields)
                    ->leftJoin('user_system_info', 'user.user_id','=','user_system_info.user_id')
                    ->leftJoin('user_info', 'user.user_id', '=', 'user_info.user_id')
                    ->leftJoin('department', 'user_system_info.dept_id', '=', 'department.dept_id');

            return $query;
        }, function($query) use($orderBy){
            return $query->orders($orderBy)->get();
        });
    }
    public function getUserCountGroupByDeptId(array $own, array $param, array $deptIds)
    {
        return $this->handleOrgUser($own, $param, function($own, $param) {
            $query = $this->entity->selectRaw('count(user.user_id) as count, user_system_info.dept_id')
                    ->leftJoin('user_system_info', 'user.user_id', '=', 'user_system_info.user_id');
            if (isset($param['filter'])) {
                $query->leftJoin('user_info', 'user.user_id', '=', 'user_info.user_id');
            }
            return $query;
        }, function($query) use($deptIds) {
            return $query->whereIn('user_system_info.dept_id', $deptIds)->groupBy('user_system_info.dept_id')->get();
        });
    }
    /**
     * 处理组织架构用户查询语句
     *
     * @param array $own
     * @param array $param
     * @param \Closure $before
     * @param \Closure $terminal
     *
     * @return array|int
     */
    public function handleOrgUser(array $own, array $param, $before, $terminal)
    {
        $query = $before($own, $param);

        if(isset($param['search']) && !empty($param['search'])) {
            if(isset($param['search']['user_id'])){
                $param['search']['user.user_id'] =  $param['search']['user_id'];
                unset($param['search']['user_id']);
            }
            $query->wheres($param['search']);
        }

        if(isset($param['filter'])){
            if($param['filter'] == 'email'){
                $query->where('user_info.email', '!=', '')->where('user_info.email', '!=', null);
            } else if($param['filter'] == 'phone_number'){
                $query->where('user_info.phone_number', '!=', '')->where('user_info.phone_number', '!=', null);
            }
        }

        if((isset($param['manage'])  && $param['manage'] == 1) && $own['user_id'] != 'admin'){
            $query->whereNotIn('user.user_id', [$own['user_id'], 'admin'])->where(function($query) use($own){
                return $query->orWhere('user_system_info.max_role_no', '>', $own['max_role_no'])->orWhereNull('user_system_info.max_role_no', '');
            });
        }

        if (!(isset($param['include_leave']) && $param['include_leave'])) {
            $query->where('user_system_info.user_status', '!=', '2');
        }

        return $terminal($query);
    }
    /**
     * 获取组织架构用户个数
     *
     * @param array $own
     * @param array $param
     *
     * @return int
     */
    public function getOrgUserCount(array $own, array $param)
    {
        return $this->handleOrgUser($own, $param, function($own, $param){
            $query = $this->entity->leftJoin('user_system_info', 'user.user_id','=','user_system_info.user_id');
            if (isset($param['filter'])) {
                $query->leftJoin('user_info', 'user.user_id', '=', 'user_info.user_id');
            }
            return $query;
        }, function($query){
            return $query->count();
        });
    }
    /**
     * 获取用户管理的列表(仅用于用户管理处调用)
     *
     * @param  array $param
     * @return array
     */
    public function getUserManageList(array $param) {
        $default = [
            'fields' => ["user.user_id", "user_accounts", "user_name", "user_name_py", "user_name_zm", "user_job_number", "list_number", "user_position"],
            'page' => 0,
            'limit' => config('eoffice.pagesize'),
            'search' => [],
            'order_by' => ['list_number' => 'ASC', 'dept_sort' => 'ASC', 'user_name' => 'ASC'],
            'returntype' => 'array',
        ];
        $deptId = $param['dept_id'] ?? [];
        // 多字段排序处理
        if (isset($param['order_by']) && is_array($param['order_by']) && count($param['order_by']) > 1) {
            $orderByParam = array();
            foreach ($param['order_by'] as $key => $value) {
                if (!empty($value) && is_array($value)) {
                    $orderByParam[key($value)] = current($value);
                }
            }
            $param['order_by'] = $orderByParam;
        }

        $param = array_merge($default, $param);

        // user和user_info表联查 处理user_id字段
        if (is_array($param['fields'])) {
            $userIdKey = array_search("user_id",$param['fields']);
            if ($userIdKey >= 0) {
                $param['fields'][$userIdKey] = 'user.user_id';
            }
        }
        if (isset($param['order_by']['user_id'])) {
            $param['order_by']['user.user_id'] = $param['order_by']['user_id'];
            unset($param['order_by']['user_id']);
        }
        // dd($param);
        //解析查询参数
        $query = $this->parseSearchParams($param);
        // 是否包含离职人员
        if (!(isset($param['include_leave']) && $param['include_leave'])) {
            // 不需要使用exists方式，因为已经join了user_system_info表
            $query->where('user_system_info.user_status', '!=', '2');
//            $query = $query->whereHas("userHasOneSystemInfo", function ($query) {
//                $query->where('user_status', '!=', '2');
//            });
        }
        // 用户列表中不包含自己和admin，除admin外
        if (isset($param['loginUserInfo']['user_id']) && $param['loginUserInfo']['user_id'] != 'admin') {
            $query->whereNotIn('user.user_id', [$param['loginUserInfo']['user_id'], 'admin']);
        }

        $query = $query->select($param['fields'])
                ->join('user_system_info', 'user_system_info.user_id', '=', 'user.user_id')
                ->leftJoin('department', 'user_system_info.dept_id', '=', 'department.dept_id')
                ->join('user_info', 'user.user_id', '=', 'user_info.user_id')
                ->orders($param['order_by'])
                ->with("userHasManyRole", "userHasManyRole.hasOneRole", 'userHasOneInfo', 'userHasOneSystemInfo', 'userHasOneSystemInfo.userSystemInfoBelongsToDepartment', 'userHasOneAttendanceSchedulingInfo');
        if (isset($param['loginUserInfo']['user_id']) && $param['loginUserInfo']['user_id'] != 'admin') {
            $query = $query->whereIn('user_system_info.dept_id', $deptId);
        }

        $query = $query->parsePage($param['page'], $param['limit']);
;
        // 返回值类型判断
        if ($param["returntype"] == "array") {
            return $query->get()->toArray();
        } else if ($param["returntype"] == "count") {
            return $query->count(); // yww
        } else if ($param["returntype"] == "object") {
            return $query->get();
        }
    }

    /**
     * 获取用户列表数量
     *
     * @param  array $param
     * @return array
     */
    public function getUserListTotal(array $param) {
        $param["page"] = 0;
        $param["returntype"] = "count";
        return $this->getUserList($param);
    }

    /**
     * 获取用户管理列表数量
     *
     * @param  array $param
     * @return array
     */
    public function getUserManageListTotal(array $param) {
        $param["page"] = 0;
        $param["returntype"] = "count";
        return $this->getUserManageList($param);
    }

    /**
     * 获取用户下一个序号
     *
     * @param
     *
     * @return string $nextListNumber
     *
     * @author miaochenchen
     *
     * @since  2017-04-12
     */
    public function getUserNextListNumber() {
        $result = $this->entity->selectRaw("MAX(list_number) max_list_number")->first()->toArray();
        $nextListNumber = $result['max_list_number'];
        if($nextListNumber) {
            return $nextListNumber+1;
        }else{
            return '';
        }
    }

    /**
     * [getUserListByOr 获取用户列表,查询条件关系为or]
     *
     * @author 朱从玺
     *
     * @param  [array]           $param [查询条件]
     *
     * @since  2015-10-26 创建
     *
     * @return [array]                  [查询结果]
     */
    public function getUserListByOr(array $param = []) {
        $default = [
            'fields' => ['*'],
            'page' => 0,
            'limit' => config('eoffice.pagesize'),
            'search' => [],
            'order_by' => ['user_id' => 'ASC'],
        ];

        $param = array_merge($default, $param);

        return $this->entity
                        ->select($param['fields'])
                        ->orWheres($param['search'])
                        ->with(['userHasOneSystemInfo' => function ($query) {
                            $query->select(['user_id', 'user_status']);
                        }])
                        ->whereHas("userHasOneSystemInfo", function ($query) {
                            $query->where('user_status', '!=', '0');
                        })
                        ->orders($param['order_by'])
                        ->parsePage($param['page'], $param['limit'])
                        ->get()
                        ->toArray();
    }

    // 获取用户所有信息
    public function getUserAllData($userId, $params = []) {
        $query = $this->entity
                ->select("*")
                ->with('userHasOneSystemInfo.userSystemInfoBelongsToDepartment', "userHasManyRole", "userHasManyRole.hasOneRole", "userHasOneSystemInfo.userSystemInfoBelongsToUserStatus", "userHasOneSecextInfo", "userHasOneAttendanceSchedulingInfo")
                ->with(['userHasManySuperior.superiorHasOneUser' => function ($query) {
                        // 上级
                        $query->select('user_id', 'user_name')->where('user_accounts', '!=', '');
                    }, 'userHasManySubordinate.subordinateHasOneUser' => function ($query) {
                        // 下级
                        $query->select('user_id', 'user_name')->where('user_accounts', '!=', '');
                    }, 'userHasOneSystemInfo' => function ($query) {
                        $query->withTrashed();
                    }, 'userHasOneInfo' => function ($query) {
                        $query->withTrashed();
                    }]);

        if (!isset($params['no_deleted'])) {
            $query = $query->withTrashed();
        }

        return $query->find($userId);
    }

    /**
     * [getUserDeptName 获取用户及用户部门]
     *
     * @author 朱从玺
     *
     * @param  [array]           $param [用户ID数组]
     *
     * @since  2015-10-26 创建
     *
     * @return [object]                 [查询结果]
     */
    public function getUserDeptName($param) {
        $default = [
            'fields' => ['*'],
            'page' => 0,
            'limit' => config('eoffice.pagesize'),
            'search' => [],
            'order_by' => ['user_id' => 'ASC'],
        ];

        $param = array_merge($default, $param);

        $query = $this->entity
                ->select('user.user_id', 'user_name')
                ->leftJoin('user_system_info', 'user.user_id', '=', 'user_system_info.user_id')
                ->with(['userHasOneSystemInfo' => function ($query) {
                        $query->select('user_id', 'dept_id');
                    }, 'userHasOneSystemInfo.userSystemInfoBelongsToDepartment' => function ($query) {
                        $query->select('dept_id', 'dept_name');
                    }])
                ->orders($param['order_by'])
                ->parsePage($param['page'], $param['limit']);

        if (isset($param['search']['dept_id']) && $param['search']['dept_id'][0]) {
            $deptSearch = ['dept_id' => $param['search']['dept_id']];
            $query = $query->whereHas('userHasOneSystemInfo', function ($query) use ($deptSearch) {
                $query->wheres($deptSearch);
            });
        }

        if (isset($param['search']['user_id']) && $param['search']['user_id'][0]) {
            if (is_array($param['search']['user_id'][0])) {
                $query = $query->whereIn('user.user_id', $param['search']['user_id'][0]);
            } else {
                $query = $query->where('user.user_id', $param['search']['user_id'][0]);
            }
        }

        if (isset($param['search']['user_array']) && $param['search']['user_array'][0]) {
            $query = $query->whereIn('user.user_id', $param['search']['user_array'][0]);
        }

        if (isset($param['search']['dept_name']) && $param['search']['dept_name'][0]) {
            $query = $query->whereHas('userHasOneSystemInfo.userSystemInfoBelongsToDepartment', function($query) use ($param) {
                $query->where('dept_name', 'like', '%' . $param['search']['dept_name'][0] . '%');
            });
        }

        if (isset($param['search']['user_name']) && $param['search']['user_name'][0]) {
            $query = $query->where('user_name', 'like', '%' . $param['search']['user_name'][0] . '%');
        }

        // 是否包含离职人员
        if (!(isset($param['include_leave']) && $param['include_leave'])) {
            $query = $query->whereHas("userHasOneSystemInfo", function ($query) {
                $query->where('user_status', '!=', '2');
            });
        }

        return $query->get();
    }

    /**
     * [getSynchroUserList 考勤账号同步数据]
     *
     * @method 朱从玺
     *
     * @param  [array]              $param [查询条件]
     *
     * @return [object]                    [查询结果]
     */
    public function getSynchroUserList($param) {
        $default = [
            'page' => 0,
            'limit' => config('eoffice.pagesize'),
            'order_by' => ['user_id' => 'ASC'],
        ];

        $param = array_merge($default, $param);

        return $this->entity->select('user.user_id', 'user.user_name', 'user.user_accounts', 'attendance_user_synchro.synchro_accounts')
                        ->whereHas("userHasOneSystemInfo", function ($query) {
                            $query->where('user_status', '!=', '2');
                        })
                        ->leftJoin('attendance_user_synchro', 'user.user_accounts', '=', 'attendance_user_synchro.user_accounts')
                        ->parsePage($param['page'], $param['limit'])
                        ->orderBy('attendance_user_synchro.user_accounts', 'asc')
                        ->get();
    }

    /**
     * 用户管理--生成下一个 user_id
     *
     * @method getNextUserIdBeforeCreate
     *
     * @return [type]                    [description]
     */
    public function getNextUserIdBeforeCreate() {
        $nextUserIdObject = $this->entity->withTrashed()
                ->select('user_id')
                ->whereRaw('LENGTH(user_id) = 10')
                ->whereRaw("LEFT(user_id,2) = 'WV'")
                ->withTrashed()
                ->orderBy('user_id', 'desc')
                ->first();
        $nextUserId = "WV00000001";
        if (isset($nextUserIdObject["user_id"])) {
            $id_max = abs(str_replace('WV', '', $nextUserIdObject["user_id"]));
            $id_max++;
            $nextUserId = 'WV' . str_pad($id_max, 8, '0', STR_PAD_LEFT);
        }
        return $nextUserId;
    }

    /**
     * 获取微博日志报表用户名
     * @param array $userId
     * @return object
     */
    public function getUserDiaryReport($param) {
        $users          = $param["users"];
        $diaryDateScope = $param["diary_date_scope"];
        $planKind       = $param["plan_kind"];
        return $this->entity
                        ->select(['user_id', 'user_name'])
                        ->whereIn('user_id', $users)
                        ->with(['userToDept' => function ($query) {
                                $query->select('dept_name');
                            }])
                        ->with(['diary' => function($query) use ($diaryDateScope,$planKind) {
                                //增加过滤 plan_status为2，1暂存 2发布
                                $query->select(['user_id', 'diary_date', 'created_at','plan_scope_date_start','plan_scope_date_end','plan_status']);
                                $query -> where('plan_status',2);
                                if(is_array($planKind)) {
                                    $query->whereIn("plan_kind",$planKind);
                                } else {
                                    $query->where("plan_kind",$planKind);
                                }
                                if(count($diaryDateScope)) {
                                    $query->whereBetween('diary_date', $diaryDateScope);
                                }
                            }])
                                ->get();
    }

    /**
     * @获取用户权限相关信息
     *
     * @author qishaobo
     *
     * @param string $userId
     *
     * @return array 用户对象数组
     */
    public function getUserPermissionData($userId) {
        return $this->entity
                        ->select(['user_id', 'user_name'])
                        ->with(['userHasOneSystemInfo' => function ($query) {
                                $query->select(['user_id', 'post_priv', 'post_dept', 'dept_id']);
                            }])
                                ->with(['userRole' => function ($query) {
                                        $query->select('role_name', 'role_no', 'role.role_id');
                                    }])
                                ->find($userId);
    }

    /**
     * @获取用户下级
     *
     * @author qishaobo
     *
     * @param array $userInfo
     *
     * @return array 用户对象数组
     */
    public function getUserSubordinate($userInfo) {
        /*        $query = $this->entity;

          $roleNo = $userInfo['role_no'];
          $query = $query->whereHas('userRole', function ($query) use ($roleNo) {
          $query->where('role_no', '>', $roleNo);
          });

          if ($userInfo['post_priv'] == 0) { //同部门
          $deptId = $userInfo['dept_id'];
          $query = $query->whereHas('userHasOneSystemInfo', function ($query) use ($deptId) {
          $query->where('dept_id', $deptId);
          });
          }

          if ($userInfo['post_priv'] == 2) { //指定部门
          $postDept = $userInfo['post_dept'];
          $query = $query->whereHas('userHasOneSystemInfo', function ($query) use ($postDept) {
          $query->whereRaw("FIND_IN_SET(dept_id, '$postDept') AND dept_id > 0");
          });
          }

          return $query->pluck('user_id'); */
    }


    /**
     * 批量获取用户信息。
     * user_id 可以是连接的字符串；可以是对象；可以是一维数组。
     * inservice:在职;leaveoffice:离职;deleted:已删除
     *
     * @method getBatchUserInfoRepository
     *
     * @param  array            $param []
     *
     * @return [type]                  [description]
     */
    function getBatchUserInfoRepository($param = []) {
        $default = [
            'fields' => ["user_id", "user_accounts", "user_name", "user_name_py", "user_name_zm", "updated_at"],
            'page' => 0,
            'limit' => config('eoffice.pagesize'),
            'search' => [],
            'order_by' => ['user_id' => 'ASC'],
            'returntype' => 'object',
        ];
        $param = array_merge($default, $param);
        $query = $this->entity
                ->select($param['fields'])
                ->wheres($param['search'])
                ->with('userHasOneSystemInfo', 'userHasOneInfo', 'userHasOneSystemInfo.userSystemInfoBelongsToDepartment', "userHasManyRole", "userHasManyRole.hasOneRole");
        // user_id可选，如果没有，返回全部
        $userId = isset($param["user_id"]) ? $param["user_id"] : "";
        if ($userId) {
            if (is_string($userId)) {
                $userId = explode(',', trim($userId, ','));
            }
            $query = $query->whereIn("user_id", $userId);
        }
        // 排序可选
        if (isset($param['order_by'])) {
            $query = $query->orders($param['order_by']);
        }
        $getDataType = isset($param["getDataType"]) ? $param["getDataType"] : "";
        // 在职条件
        if ($getDataType == "inservice") {
            $query = $query->whereHas('userHasOneSystemInfo', function($query) {
                $query->where("user_status", '>', '0')->where("user_status", '!=', '2');
            });
        }
        // 离职条件
        if ($getDataType == "leaveoffice") {
            $query = $query->whereHas('userHasOneSystemInfo', function($query) {
                $query->where("user_status", '2');
            });
        }
        // 删除人员条件
        if ($getDataType == "deleted") {
            $query = $query->onlyTrashed();
        }

        $query = $query->parsePage($param['page'], $param['limit']);

        // 返回值类型判断
        if ($param["returntype"] == "array") {
            return $query->get()->toArray();
        } else if ($param["returntype"] == "count") {
            return $query->get()->count();
        } else if ($param["returntype"] == "object") {
            return $query->get();
        }
    }

    function getBatchUserInfoRepositoryTotal($param) {
        $param["page"] = 0;
        $param["returntype"] = "count";
        return $this->getBatchUserInfoRepository($param);
    }

    /**
     * 获取所有用户
     *
     * @param array $params
     *
     * @return array
     *
     * @author lizhijun
     *
     * @since 2015-11-24
     */
    public function getAllUsers($params) {


        $fields = isset($params['fields']) ? $params['fields'] : ['*'];

        $query = $this->entity->select($fields);

        if (isset($params['search']) && !empty($params['search'])) {
            $query->wheres($params['search']);
        }
        //yww
        if (isset($params['page']) && $params['page']) {
            $params['limit'] = isset($params['limit']) && $params['limit'] ? $params['limit'] : config('eoffice.pagesize');
            $query->parsePage($params['page'], $params['limit']);
        }

        return $query->orderBy('user_id', 'asc')->get();
    }

    /**
     * 获取用户列表函数
     * 说明：此函数为了获取满足条件的流程办理人员/抄送人员/协作有权限人员而写的。暂不作他用，以后修改请修改此说明。
     *
     * @method getUserList
     *
     * @param  array       $param [description]
     *
     * @return [type]             [description]
     */
    public function getConformScopeUserList(array $param) {
        // 解析传过来的角色id
        $roleId = isset($param["search"]["role_id"]) ? $param["search"]["role_id"] : false;
        // 解析传过来的部门id
        $deptId = isset($param["search"]["dept_id"]) ? $param["search"]["dept_id"] : false;
        // 解析传过来的用户id
        $userId = isset($param["search"]["user_id"]) ? $param["search"]["user_id"] : false;
        // 是否进行关联查询
        $withAble = isset($param["fields"]) ? true : false;
        $default = [
            'fields' => ["user_id", "user_accounts", "user_name", "user_name_py", "user_name_zm"],
            'page' => 0,
            'limit' => config('eoffice.pagesize'),
            'search' => [],
            'order_by' => ['list_number' => 'ASC', 'user_id' => 'ASC'],
            'returntype' => 'object',
        ];
        $mergeUsers = [];
        $hasUserValue = false;
        $param = array_merge($default, $param);
        if ($roleId !== false) {
            unset($param["search"]["role_id"]);
            if (is_array($roleId) || is_object($roleId)) {
                $roleUsers = $this->UserRoleEntity->select('user_id')->whereIn('role_id',$roleId)->get();
            } else {
                $roleUsers = $this->UserRoleEntity->select('user_id')->whereIn('role_id', explode(",", trim($roleId, ",")))->get();
            }
            $roleUsers = $roleUsers->pluck('user_id')->toArray();
            $mergeUsers = array_merge($roleUsers , $mergeUsers);
             $hasUserValue = true;
        }
        if ($deptId !== false) {
            unset($param["search"]["dept_id"]);
            if (is_array($deptId) || is_object($deptId)) {
                $deptUsers = $this->UserSystemInfoEntity->select('user_id')->whereIn('dept_id',$deptId)->get();
            } else {
                $deptUsers = $this->UserSystemInfoEntity->select('user_id')->whereIn('dept_id', explode(",", trim($deptId, ",")))->get();
            }
            $deptUsers = $deptUsers->pluck('user_id')->toArray();
            $mergeUsers = array_merge($deptUsers , $mergeUsers);
            $hasUserValue = true;
        }
        if ($userId !== false) {
            if (is_object($userId)) {
                  $userId =   $userId->toArray();
            } else if (is_string($userId)){
                  $userId = explode(",", trim($userId, ","));
            }
            $mergeUsers = array_merge($userId , $mergeUsers);
            unset($param["search"]["user_id"]);
            $hasUserValue = true;
        }
        // var_dump( $mergeUsers );die;
        $query = $this->entity;
        $query = $query->where(function($query) use($param, $mergeUsers ,$hasUserValue) {
            //  此处sql查询过慢
            // if ($roleId !== false) {
            //     $query->orWhereHas("userHasManyRole", function ($query) use($roleId) {
            //         if (is_array($roleId) || is_object($roleId)) {
            //             $query->whereIn('role_id', $roleId);
            //         } else {
            //             $query->whereIn('role_id', explode(",", trim($roleId, ",")));
            //         }
            //     });
            // }
            // if ($deptId !== false) {
            //     $query->orWhereHas("userHasOneSystemInfo.userSystemInfoBelongsToDepartment", function ($query) use($deptId) {
            //         if (is_array($deptId) || is_object($deptId)) {
            //             $query->whereIn('dept_id', $deptId);
            //         } else {
            //             $query->whereIn('dept_id', explode(",", trim($deptId, ",")));
            //         }
            //     });
            // }
            if ($hasUserValue !== false) {
                //if (is_array($userId) || is_object($userId)) {
                    $query = $query->orWhereIn('user_id', array_unique($mergeUsers));
                // } else {
                //     $query = $query->orWhereIn('user_id', explode(",", trim($userId, ",")));
                // }
            }
        });
        $query = $query->select($param['fields'])
                ->wheres($param['search'])
                ->orders($param['order_by']);
        if (!$withAble) {
            $query = $query->with("userHasManyRole", "userHasManyRole.hasOneRole", 'userHasOneInfo', 'userHasOneSystemInfo', 'userHasOneSystemInfo.userSystemInfoBelongsToDepartment');
        }
        // 默认在职
        $getDataType = (isset($param["getDataType"]) && $param["getDataType"]) ? $param["getDataType"] : "inservice";
        // 在职条件
        if ($getDataType == "inservice") {
            $query = $query->whereHas('userHasOneSystemInfo', function($query) {
                $query->where("user_status", '>', '0')->where("user_status", '!=', '2');
            });
        }
        // 离职条件
        if ($getDataType == "leaveoffice") {
            $query = $query->whereHas('userHasOneSystemInfo', function($query) {
                $query->where("user_status", '2');
            });
        }
        // 删除人员条件
        if ($getDataType == "deleted") {
            $query = $query->onlyTrashed();
        }
        $query = $query->parsePage($param['page'], $param['limit']);

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
     * 通过角色ID获取用户列表
     *
     * @author 缪晨晨
     *
     * @param  string or array $roleId
     *
     * @since  2017-06-06 创建
     *
     * @return Collection | string 用户列表
     */
    public function getUserListByRoleId($roleId) {
        if(!empty($roleId)) {
            if(is_array($roleId)) {
                $where = ['user_role.role_id' => [$roleId, 'in']];
            }else{
                $where = ['user_role.role_id' => $roleId];
            }
            $userList = $this->entity->select(['user.user_id','user.user_name','user.user_accounts','user.password'])
                                     ->leftJoin('user_role', 'user.user_id', '=', 'user_role.user_id')
                                     ->wheres($where)
                                     ->get();
            return $userList;
        }else{
            return '';
        }
    }

    /**
     * 删除用户
     *
     * @param  int $userId 用户id
     *
     * @return integer
     *
     * @author miaochenchen
     *
     * @since  2016-05-17
     */
    public function deleteUserByUserId($userId) {
        return $this->entity->where('user_id', $userId)->delete();
    }

    /**
     * 获取满足条件下的用户[yuwei]
     */
    public function getInfoByWhere($param) {
        return $this->entity->select(["user.*"])
                        ->whereHas('userHasOneSystemInfo', function ($query) use ($param) {
                            $query->wheres($param);
                        })->where("user.user_accounts", "!=", "")->get()->toArray();
    }

    /**
     * 解析查询用户的参数
     *
     * @param  array $param
     *
     * @return object
     *
     * @author
     *
     * @since
     */
    public function parseSearchParams($param) {
        $search = $param['search'];
        $fixedSearch = isset($param['fixedSearch']) ? $param['fixedSearch'] : '';
        if (!empty($fixedSearch)) {
            //查询参数与固定的管理范围和角色权限级别取交集
            if (isset($search['dept_id'][0]) && $fixedSearch['dept_id'][0] != 'all') {
                if (is_array($search['dept_id'][0])) {
                    $search['dept_id'][0] = array_intersect($search['dept_id'][0], $fixedSearch['dept_id'][0]);
                } else {
                    if ($search['dept_id'][0] == '0') {
                        $search['dept_id'][0] = $fixedSearch['dept_id'][0];
                    } else {
                        $search['dept_id'][0] = array_intersect(explode(',', $search['dept_id'][0]), $fixedSearch['dept_id'][0]);
                    }
                }
            }
        }

        $correlation = [
            "role_id"               => "userHasManyRole",
            "dept_id"               => "userHasOneSystemInfo.userSystemInfoBelongsToDepartment",
            "user_sex"              => "userHasOneInfo",
            "user_status"           => "userHasOneSystemInfo",
            "attendance_scheduling" => "userHasOneAttendanceSchedulingInfo",
            "post_priv"             => "userHasOneSystemInfo",
            "dept_name"             => "userHasOneSystemInfo.userSystemInfoBelongsToDepartment",
            "max_role_no"           => "userHasOneSystemInfo",
            "phone_number"          => "userHasOneInfo"
        ];
        $query = $this->entity;
        foreach ($search as $key => $value) {
            if ($value) {
                $value = $value[0];
                switch ($key) {
                    case 'max_role_no':
                        $query = $query->whereHas($correlation[$key], function ($query) use($value, $key) {
                            if(trim($value) != "") {
                                $query->where(function($query) use($value, $key) {
                                    $query->orWhere($key, '>',$value)->orWhereNull($key, '');
                                });
                            }
                        });
                        break;
                    case 'role_id':
                        $query = $query->whereHas($correlation[$key], function ($query) use($value, $key) {
                            if (is_array($value) || is_object($value)) {
                                $query->whereIn($key, $value);
                            } elseif (empty($value)) {

                            } else {
                                $query->whereIn($key, explode(",", trim($value, ",")));
                            }
                        });
                        break;
                    case 'dept_id':
                        $query = $query->whereHas($correlation[$key], function ($query) use($value, $key) {
                            // ->orWhere('user_system_info.dept_id', '0')为了兼容9.0的离职人员部门为0的情况
                            if (is_array($value) || is_object($value)) {
                                $query->whereIn($key, $value)->orWhere('user_system_info.dept_id', '0');
                            } elseif (empty($value)) {
                                $query->orWhere('user_system_info.dept_id', '0');
                            } else {
                                $query->whereIn($key, explode(",", trim($value, ",")))->orWhere('user_system_info.dept_id', '0');
                            }
                        });
                        break;
                    case 'user_id':
                        if (is_array($value) || is_object($value)) {
                            $query = $query->whereIn('user.user_id', $value);
                        } elseif (empty($value)) {

                        } else {
                            $query = $query->whereIn('user.user_id', explode(",", trim($value, ",")));
                        }
                        break;
                    case 'user_sex':
                        $query = $query->whereHas($correlation[$key], function ($query) use($value, $key) {
                            $query->where('sex', $value);
                        });
                        break;
                    case 'dept_name':
                        // ->orWhere('user_system_info.dept_id', '0')为了兼容9.0的离职人员部门为0的情况
                        $query = $query->whereHas($correlation[$key], function ($query) use($value, $key) {
                            $query->where('dept_name', 'like', '%' . $value . '%')->orWhere('user_system_info.dept_id', '0');
                        });
                        break;
                    case 'user_name':case 'user_accounts':
                        $query = $query->where(function ($query) use($key, $value) {
                            $query->orWhere($key, 'like', '%' . $value . '%')
                                  ->orWhere('user_name_zm', 'like', '%' . $value . '%')
                                  ->orWhere('user_name_py', 'like', '%' . $value . '%');
                        });
                        break;
                    case 'user_job_number':
                        $query = $query->where($key, 'like', '%' . $value . '%');
                        break;
                    case 'attendance_scheduling':
                        $query = $query->whereHas('userHasOneAttendanceSchedulingInfo', function ($query) use($value, $key) {
                            $query = $query->where('scheduling_id', $value);
                        });
                        break;
                    case 'phone_number':
                        if (is_string($value)) {
                            // 手机号模糊匹配 只要搜索输入的内容中数字顺序一致就可以模糊匹配
                            $value = trim(chunk_split($value,1,"%"), '%');
                        }
                        $query = $query->whereHas($correlation[$key], function ($query) use($value, $key) {
                            $query->where($key, 'like', '%' . $value . '%');
                        });
                        break;
                    case 'created_at':
                        $start = $value['startDate'] ?? '';
                        $end = $value['endDate'] ?? '';
                        $query = $query->whereBetween('user.created_at',[$start, $end]);
                        break;
                    case 'birthday':
                        $start = $value['startDate'] ?? '';
                        $end = $value['endDate'] ?? '';
                        $birthdayStart = date('m-d', strtotime($start));
                        $birthdayEnd = date('m-d', strtotime($end));
                        // $query = $query->whereBetween('user_info.birthday',[$start, $end]);
                        $query = $query->whereRaw("date_format(`birthday`, '%m-%d')>=?", [$birthdayStart])->whereRaw("date_format(`birthday`, '%m-%d')<= ?", [$birthdayEnd])->where('user_info.birthday', '!=', '0000-00-00');
                        break;
                    default:
                        $query = $query->whereHas($correlation[$key], function ($query) use($value, $key) {
                            $query = $query->where($key, $value);
                        });
                        break;
                }
                unset($value);
                unset($search[$key]);
            }
        }

        return $query;
    }

    /**
     * 获取某个角色下的用户数量
     */
    public function getUserCountByRole($param) {
        $query = $this->entity->select(DB::raw('count(*) as user_count, role_id'))
                              ->leftJoin('user_role', 'user.user_id', '=', 'user_role.user_id')
                              ->where('user.user_accounts', "!=", "");
        if (isset($param['user_id']) && !empty($param['user_id'])) {
            if (isset($param['isNotIn']) && !$param['isNotIn']) {
                $query = $query->whereNotIn('user.user_id', $param['user_id']);
            } else {
                $query = $query->whereIn('user.user_id', $param['user_id']);
            }
        }
        return $query->groupBy("role_id")->get()->toArray();
    }

    /**
     * 获取某个角色下的用户id
     */
    public function getUserIdByRole($role_id) {
        return $this->entity->select('user.user_id')
                        ->leftJoin('user_role', 'user.user_id', '=', 'user_role.user_id')
                        ->where('user.user_accounts', "!=", "")
                        ->wheres(['user_role.role_id' => $role_id])
                        ->get()->toArray();
    }

    /**
     * 获取条件下的用户信息
     */
    public function getUserByWhere($where) {
        return $this->entity->select(["user.*", "user_role.*"])
                        ->leftJoin('user_role', 'user.user_id', '=', 'user_role.user_id')
                        ->where('user.user_accounts', "!=", "")
                        ->wheres($where)->get()->toArray();
    }

    //复杂查询测试
    //朱从玺
    public function getListByMultiSearch($param) {
        // return $this->entity->select($param['fields'])->multiWheres($param['search'])->first();
        return $this->entity->parseSelect($param['fields'], $this->entity)->wheres($param['search'])->first();
    }

    /**
     * 费用统计 yww
     */
    public function userChargeStatistics($data) {
        $default = [
            'fields' => ['charge_setting.*', 'user.user_name as name', 'user.user_id as id'],
            'page' => 0,
            'limit' => config('eoffice.pagesize'),
            'search' => [],
            'order_by' => ['charge_setting_id' => 'desc', 'user.user_id' => 'asc']
        ];
        $param  = array_merge($default, array_filter($data));
        $result = $this->entity->select($param['fields'])
                        ->leftJoin('charge_setting', function($join) {
                            $join->on("charge_setting.user_id", '=', 'user.user_id')->whereNull('charge_setting.deleted_at');
                        })
                        ->wheres($param['search'])
                        ->orders($param['order_by'])
                        ->parsePage($param['page'], $param['limit'])
                        ->get()
                        ->toArray();

        if (isset($param['search']['user.user_id'][0]) && !empty($param['search']['user.user_id'][0]) && empty($result)) {
            $result   = [];
            $result[] = [
                'user_id'          => $param['search']['user.user_id'][0],
                'alert_method'     => '',
                'alert_value'      => 0,
                'alert_data_start' => "0000-00-00",
                'alert_data_end'   => "0000-00-00",
                'subject_check'    => 0,
                'set_type'         => 3,
                'id'               => $param['search']['user.user_id'][0]
            ];
            $userInfo = $this->entity->select('user_name')->where('user_id', $param['search']['user.user_id'][0])->first();
            $result[0]['name'] = isset($userInfo->user_name) ? $userInfo->user_name : '';
        }

        return $result;
    }

    public function userChargeStatisticsTotal($data) {
        $default = [
            'search' => []
        ];
        $param = array_merge($default, array_filter($data));
        $count = $this->entity->leftJoin('charge_setting', function($join) {
                            $join->on("charge_setting.user_id", '=', 'user.user_id')->whereNull('charge_setting.deleted_at');
                        })
                        ->wheres($param['search'])
                        ->count();

        if (isset($param['search']['user.user_id']) && $count == 0) {
            $count = 1;
        }

        return $count;
    }

    /**
     * 获取所有用户ID集合
     *
     * @author 缪晨晨
     *
     * @param  $param array
     *
     * @since  2016-10-10 创建
     *
     * @return string or array  返回所有用户ID集合
     */
    public function getAllUserIdString($param = []) {
        $default = [
            'fields'   => ['user.user_id'],
            'search'   => [],
            'order_by' => ['list_number' => 'ASC', 'user_id' => 'ASC'],
        ];
        $param = array_merge($default, $param);
        $query = $this->entity->select($param['fields']);
        // 角色查询
        if(isset($param['search']['role_id'][0]) && !empty($param['search']['role_id'][0])) {
            $roleId = $param['search']['role_id'][0];
            $query = $query->whereHas("userHasManyRole", function ($query) use($roleId, $param) {
                if (is_array($roleId) || is_object($roleId)) {
                    if(isset($param["search"]["role_id"][1]) && $param["search"]["role_id"][1] == 'not_in') {
                        $query->whereNotIn('role_id', $roleId);
                    }else{
                        $query->whereIn('role_id', $roleId);
                    }
                } else {
                    if(isset($param["search"]["role_id"][1]) && $param["search"]["role_id"][1] == 'not_in') {
                        $query->whereNotIn('role_id', explode(",", trim($roleId, ",")));
                    }else{
                        $query->whereIn('role_id', explode(",", trim($roleId, ",")));
                    }
                }
            });
            unset($param['search']['role_id']);
        }
        // 部门查询
        if(isset($param['search']['dept_id'][0]) && !empty($param['search']['dept_id'][0])) {
            $deptId = $param['search']['dept_id'][0];
            $query = $query->whereHas("userHasOneSystemInfo", function ($query) use($deptId, $param) {
                if (is_array($deptId) || is_object($deptId)) {
                    if(isset($param["search"]["dept_id"][1]) && $param["search"]["dept_id"][1] == 'not_in') {
                        $query->whereNotIn('dept_id', $deptId);
                    }else{
                        $query->whereIn('dept_id', $deptId);
                    }
                } else {
                    if(isset($param["search"]["dept_id"][1]) && $param["search"]["dept_id"][1] == 'not_in') {
                        $query->whereNotIn('dept_id', explode(",", trim($deptId, ",")));
                    }else{
                        $query->whereIn('dept_id', explode(",", trim($deptId, ",")));
                    }
                }
            });
            unset($param['search']['dept_id']);
        }
        // 是否包含离职，默认不包含
        if (!(isset($param['include_leave']) && $param['include_leave'])) {
            $query = $query->whereHas("userHasOneSystemInfo", function ($query) use($param){
                $query->where('user_status', '!=', '2');
            });
        }
        // user_id过滤
        if (isset($param['search']['user_id'][0]) && !empty($param['search']['user_id'][0])) {
            if (!(is_array($param['search']['user_id'][0]) || is_object($param['search']['user_id'][0]))) {
                $param["search"]["user_id"][0] = explode(",", trim($param["search"]["user_id"][0], ","));
            }
            if (isset($param["search"]["user_id"][1]) && $param["search"]["user_id"][1] == 'not_in') {
                $query = $query->whereNotIn('user_id', $param["search"]["user_id"][0]);
            } else {
                $query = $query->whereIn('user_id', $param["search"]["user_id"][0]);
            }
            unset($param["search"]["user_id"]);
        }
        // 包含删除的用户
        if(isset($param['with_trashed']) && $param['with_trashed']) {
            $query->withTrashed();
        }
        // 其他查询条件
        if(isset($param['search']) && !empty($param['search'])) {
            $query = $query->wheres($param['search']);
        }
        $query = $query->orders($param['order_by'])->get()->toArray();
        $userIdArray = array();
        if($query) {
            foreach ($query as $key => $value) {
                $userIdArray[] = $value['user_id'];
            }
        }
        if(isset($param['return_type']) && $param['return_type'] == 'array') {
            return $userIdArray;
        }else{
            $userIdString = join(',', $userIdArray);
            return $userIdString;
        }
    }

    //** weixin 用户
    public function getUserInfoByOpenid($openid) {

        return $this->entity->select(["user.user_id", "user.user_name", "weixin_user.openid"])->leftJoin('weixin_user', function($join) {
                            $join->on("user.user_id", '=', 'weixin_user.user_id');
                        })->where("openid", $openid)
                        ->where("user.user_accounts", "!=", "")
                        ->first(); //获取一条
    }

    //** 获取用户
    public function getWeixinUserTotal($data) {
        $default = [
            'search' => [],
        ];

        $param = array_merge($default, array_filter($data));



        $query = $this->entity->leftJoin('weixin_user', function($join) {
                    $join->on("user.user_id", '=', 'weixin_user.user_id');
                })
                ->where("user.user_accounts", "!=", "")
                ->wheres($param['search']);

        if (isset($data["status"])) {
            if ($data["status"] == 0) {
                $query = $query->whereNull("weixin_user.user_id");
            } else if ($data["status"] == 1) {
                $query = $query->whereNotNull("weixin_user.user_id");
            }
        }
        return $query->count();
    }

    public function getWeixinUserList($data) {
        $default = [
            'fields' => ['user.user_id', 'user.user_accounts', 'user.user_name', 'weixin_user.user_id as status'],
            'page' => 0,
            'limit' => config('eoffice.pagesize'),
            'search' => [],
            'order_by' => ['user.user_id' => 'asc'],
        ];

        $param = array_merge($default, array_filter($data));

        $query = $this->entity->select($param['fields'])->leftJoin('weixin_user', function($join) {
                    $join->on("user.user_id", '=', 'weixin_user.user_id');
                })
                ->wheres($param['search'])
                ->where("user.user_accounts", "!=", "");

        if (isset($data["status"])) {
            if ($data["status"] == 0) {
                $query = $query->whereNull("weixin_user.user_id");
            } else if ($data["status"] == 1) {
                $query = $query->whereNotNull("weixin_user.user_id");
            }
        }


        return $query->orders($param['order_by'])
                        ->parsePage($param['page'], $param['limit'])
                        ->get()
                        ->toArray();
    }

    public function checkUserByWhere($where, $fields = ["user.user_id"]) {
        return $this->entity->select($fields)->leftJoin('user_info', function($join) {
                    $join->on("user.user_id", '=', 'user_info.user_id');
                })->wheres($where)->where("user.user_accounts", "!=", "")->first();
    }

    //获取用户数量
    public function getUserNumber($where) {
        $userWhere = !empty($where['user']) ? $where['user'] : [];
        $userSystemInfoWhere = !empty($where['user_system_info']) ? $where['user_system_info'] : [];

        $query = $this->entity->wheres($userWhere);

        if (!empty($userSystemInfoWhere)) {
            $query->whereHas("userHasOneSystemInfo", function ($query) use($userSystemInfoWhere) {
                $query->wheres($userSystemInfoWhere);
            });
        }

        return $query->count();
    }

    /**
     * 通过用户ID获取其部门ID和角色ID
     *
     * @author 缪晨晨
     *
     * @param  string $userId
     *
     * @since  2017-03-30 创建
     *
     * @return array  返回用户的部门ID和角色ID
     */
    public function getUserDeptIdAndRoleIdByUserId($userId) {
        $result = $this->entity->select(['user_id'])->where('user_id', $userId)
                               ->with(['userHasManyRole' => function($query) use ($userId) {
                                   $query->select(['user_id','role_id']);
                               }])
                               ->with(['userHasOneSystemInfo' => function($query) use ($userId) {
                                   $query->select(['user_id','dept_id']);
                               }])->first();
        $userDeptRoleIdInfo = array(
            'dept_id' => '',
            'role_id' => ''
        );
        if(!empty($result)) {
            $result = $result->toArray();
            $userDeptRoleIdInfo['dept_id'] = $result['user_has_one_system_info']['dept_id'];
            if(!empty($result['user_has_many_role'])) {
                foreach($result['user_has_many_role'] as $value) {
                    $userDeptRoleIdInfo['role_id'] .= $value['role_id'].',';
                }
                if(!empty($userDeptRoleIdInfo['role_id'])) {
                    $userDeptRoleIdInfo['role_id'] = rtrim($userDeptRoleIdInfo['role_id'], ',');
                }
            }
        }
        return $userDeptRoleIdInfo;
    }

    /**
     * 用户管理报表数据（状态、部门、角色）
     *
     * @author 缪晨晨
     *
     * @param  $datasourceGroupBy 分组依据; $where 数据过滤
     *
     * @since  2017-07-13 创建
     *
     * @return array
     */
    public function getUserCountGroupByCustomType($datasourceGroupBy, $where="") {
        $search = array();
        $query = $this->entity;
        if(!empty($where)) {
            if (isset($where['role_id']) && !empty($where['role_id']) && $datasourceGroupBy != 'userRole') {
                $roleId = $where['role_id'];
                if (!(is_array($roleId) || is_object($roleId))) {
                    $roleId = explode(",", trim($roleId, ","));
                }
                $query = $query->whereHas("userHasManyRole", function ($query) use($roleId) {
                    $query->whereIn('role_id', $roleId);
                });
            }
            if (isset($where['dept_id']) && !empty($where['dept_id'])) {
                $deptId = $where['dept_id'];
                if (!(is_array($deptId) || is_object($deptId))) {
                    $deptId = explode(",", trim($deptId, ","));
                }
                $query = $query->whereHas("userHasOneSystemInfo.userSystemInfoBelongsToDepartment", function ($query) use($deptId) {
                    $query->whereIn('dept_id', $deptId);
                });
            }
            if (isset($where['user_status']) && !empty($where['user_status'])) {
                $userStatus = $where['user_status'];
                if (!(is_array($userStatus) || is_object($userStatus))) {
                    $userStatus = explode(",", trim($userStatus, ","));
                }
                $query = $query->whereHas("userHasOneSystemInfo.userSystemInfoBelongsToUserStatus", function ($query) use($userStatus) {
                    $query = $query->whereIn('status_id', $userStatus);
                });
            }
        }
        if($datasourceGroupBy == 'userStatus') {
            $result = $query->select(['user_system_info.user_status'])
                            ->selectRaw('count(1) as user_count')
                            ->leftJoin('user_system_info', 'user.user_id', '=', 'user_system_info.user_id')
                            ->groupby('user_status')
                            ->withTrashed()
                            ->get();
        }elseif($datasourceGroupBy == 'userDept') {
            $result = $query->select(['department.dept_name', 'department.dept_id'])
                            ->selectRaw('COUNT(user_system_info.dept_id) AS user_count')
                            ->leftJoin('user_system_info', 'user.user_id', '=', 'user_system_info.user_id')
                            ->leftJoin('department', 'user_system_info.dept_id', '=', 'department.dept_id')
                            ->where('user_system_info.user_status', '>', '0')
                            ->groupby('user_system_info.dept_id')
                            ->get();
        }elseif($datasourceGroupBy == 'userRole') {
            $query = $query->select(['role.role_name'])
                            ->selectRaw('COUNT(user_role.role_id) AS user_count')
                            ->rightJoin('user_role', 'user_role.user_id', '=', 'user.user_id')
                            ->rightJoin('role', 'user_role.role_id', '=', 'role.role_id')
                            ->groupby('user_role.role_id');
            if (isset($where['role_id']) && !empty($where['role_id'])) {
                $roleId = $where['role_id'];
                if (is_array($roleId) || is_object($roleId)) {
                    $query->whereIn('user_role.role_id', $roleId);
                } else {
                    $query->whereIn('user_role.role_id', explode(",", trim($roleId, ",")));
                }
            }
            $result = $query->get();
        }else{
            $result = array();
        }

        if(!empty($result)) {
            return $result->toArray();
        }else{
            return array();
        }
    }

    /**
     * 获取以用户ID为key用户姓名为value的数组
     *
     * @author 缪晨晨
     *
     * @param
     *
     * @since  2017-07-26 创建
     *
     * @return array
     */
    public function getUserIdAsKeyAndNameAsValueArray() {
        $result = $this->entity->select(['user_id', 'user_name'])->get();
        $userNameArray = array();
        if(!empty($result)) {
            foreach($result->toArray() as $key => $value) {
                $userNameArray[$value['user_id']] = $value['user_name'];
            }
        }
        return $userNameArray;
    }

    //yuwei 验证用户是否正常状态
    public function isVaildUserStatus($userId){
       return $this->entity->where("user_accounts","!=","")->where("user_id",$userId)->count();

    }
    // 获取用户所有信息根据user_accounts
    public function getUserAllDataByAccount($useraccounts) {
        $query = $this->entity
                ->select("*")
                ->with('userHasOneSystemInfo.userSystemInfoBelongsToDepartment', "userHasManyRole", "userHasManyRole.hasOneRole", "userHasOneSystemInfo.userSystemInfoBelongsToUserStatus")
                ->with(['userHasManySuperior.superiorHasOneUser' => function ($query) {
                        // 上级
                        $query->select('user_id', 'user_name')->where('user_accounts', '!=', '');
                    }, 'userHasManySubordinate.subordinateHasOneUser' => function ($query) {
                        // 下级
                        $query->select('user_id', 'user_name')->where('user_accounts', '!=', '');
                    }, 'userHasOneSystemInfo' => function ($query) {
                        $query->withTrashed();
                    }, 'userHasOneInfo' => function ($query) {
                        $query->withTrashed();
                    }])
                ->where(function($query) use ($useraccounts) {
                    $query->orWhere('user_accounts',$useraccounts)
                          ->orWhere('user_id',$useraccounts)
                          ->orWhere('user_name',$useraccounts);
                })
                ->where('user_accounts', '!=', '')
                ->withTrashed();
        return $query->first();
    }

    /**
     * 根据用户姓名获取用户id数组
     * @param  string $userName 用户姓名
     * @return array          用户id数组
     */
    public function getUserAllUserId($userName)
    {
        $userId = $this->entity->select('user_id')->where("user_name","like","%".$userName."%")->get()->toArray();
        $IdArray = [];
        if (isset($userId) && !empty($userId)) {
            foreach ($userId as $k => $v) {
                $IdArray[] = $v['user_id'];
            }
        }
        return $IdArray;
    }

    public function getAttendanceUser(){
        $param["page"] = 0;
        $param["fields"] = ['user_id', 'user_accounts', 'user_name'];
        $user = $this->getUserInfoAll($param);
        $data = [];

        $matchUser = DB::table("attendance_macth_user")->get()->mapWithKeys(function($item) {
            return [$item->user_id => $item->attendance_id];
        });

        $data = array_map(function($userInfo) use($matchUser) {
            $handleGroup = ['user_id' => $userInfo['user_id'], 'user_accounts' => $userInfo['user_accounts'], 'user_name' => $userInfo['user_name']];
            $handleGroup['attendance_id'] = (isset($matchUser[$handleGroup['user_id']]) && $matchUser[$handleGroup['user_id']]) ? $matchUser[$handleGroup['user_id']] : '';
            return $handleGroup;
        }, $user);

        return $data;
    }


    /**
     * 根据用户部门id数组获取所有的用户id
     * @return array
     */
    public static function getUserIdsByDeptIds(array $deptIds)
    {
        $lists = DB::table(self::TABLE_USER_INFO)->select(['user_id'])->whereIn('dept_id', $deptIds)->get();
        if ($lists->isEmpty()) {
            return [];
        }
        return array_unique(array_column($lists->toArray(), 'user_id'));
    }

    /**
     * 根据用户角色id数组获取所有的用户id
     * @return array
     */
    public static function getUserIdsByRoleIds(array $roleIds)
    {
        $lists = DB::table(self::TABLE_USER_ROLE)->select(['user_id'])->whereIn('role_id', $roleIds)->get();
        if ($lists->isEmpty()) {
            return [];
        }
        return array_unique(array_column($lists->toArray(), 'user_id'));
    }

    public function updateFields($data) {
        return DB::table('user')->update($data);
    }

    public function getUserSystemInfo($userId) {
        return $this->entity->select(['user_id', 'user_accounts', 'password','user_name', 'change_pwd', 'user_position', 'user_job_number', 'user_area', 'user_city', 'user_workplace', 'user_job_category'])
                    ->with('userHasOneSystemInfo.userSystemInfoBelongsToDepartment', 'userHasManyRole', 'userHasOneInfo')
                    ->find($userId);
    }
    /**
     * 获取用户管理非admin获取的离职人员列表列表(仅用于用户管理处调用)
     *
     * @param  array $param
     * @return array
     */
    public function getLeaveUserManageList(array $param) {
        $default = [
            'fields' => ["user.user_id", "user_accounts", "user_name", "user_name_py", "user_name_zm", "user_job_number", "list_number", "user_position", 'user.updated_at'],
            'page' => 0,
            'limit' => config('eoffice.pagesize'),
            'search' => [],
            'order_by' => ['list_number' => 'ASC', 'dept_sort' => 'ASC', 'user_name' => 'ASC'],
            'returntype' => 'array',
        ];
        $deptId = $param['dept_id'] ?? [];
        // 多字段排序处理
        if (isset($param['order_by']) && is_array($param['order_by']) && count($param['order_by']) > 1) {
            $orderByParam = array();
            foreach ($param['order_by'] as $key => $value) {
                if (!empty($value) && is_array($value)) {
                    $orderByParam[key($value)] = current($value);
                }
            }
            $param['order_by'] = $orderByParam;
        }

        $param = array_merge($default, $param);

        // user和user_info表联查 处理user_id字段
        if (is_array($param['fields'])) {
            $userIdKey = array_search("user_id",$param['fields']);
            if ($userIdKey >= 0) {
                $param['fields'][$userIdKey] = 'user.user_id';
            }
        }
        if (isset($param['order_by']['user_id'])) {
            $param['order_by']['user.user_id'] = $param['order_by']['user_id'];
            unset($param['order_by']['user_id']);
        }

        //解析查询参数
        $query = $this->parseSearchParams($param);
        $query->where('user_system_info.user_status', '=', '2');
        // 用户列表中不包含自己和admin，除admin外
        if (isset($param['loginUserInfo']['user_id']) && $param['loginUserInfo']['user_id'] != 'admin') {
            $query->whereNotIn('user.user_id', [$param['loginUserInfo']['user_id'], 'admin']);
        }

        $query = $query->select($param['fields'])
                ->join('user_system_info', 'user_system_info.user_id', '=', 'user.user_id')
                ->leftJoin('department', 'user_system_info.dept_id', '=', 'department.dept_id')
                ->join('user_info', 'user.user_id', '=', 'user_info.user_id')
                ->orders($param['order_by'])
                ->with("userHasManyRole", "userHasManyRole.hasOneRole", 'userHasOneInfo', 'userHasOneSystemInfo', 'userHasOneSystemInfo.userSystemInfoBelongsToDepartment', 'userHasOneAttendanceSchedulingInfo');
        $query = $query->whereIn('user_system_info.dept_id', $deptId);
        $query = $query->parsePage($param['page'], $param['limit']);
;
        // 返回值类型判断
        if ($param["returntype"] == "array") {
            return $query->get()->toArray();
        } else if ($param["returntype"] == "count") {
            return $query->count(); // yww
        } else if ($param["returntype"] == "object") {
            return $query->get();
        }
}
/**
     * 获取当天生日用户
     */
    function getThisDateBrithday($date) {
        $query= $this->entity;
        $query = $query->select(['user.user_id', 'user.user_accounts', 'user.user_name', 'user_position', 'user_area', 'user_city', 'user_workplace', 'user_job_category'])
        ->with('userHasOneSystemInfo.userSystemInfoBelongsToDepartment', 'userHasOneInfo')
        ->leftJoin('user_info', 'user.user_id', '=', 'user_info.user_id');
        $query = $query->where('user_accounts', '!=', '');
        $query = $query->whereRaw("substring_index(birthday, '-', -2) = '".$date."'");
        return $query->get()->toArray();
    }

    /**
     * 获取用户名
     * @param  string $userId 必填，用户id
     * @return string         this user name
     */
    public function getUserNamesByPage($userId,$param) {
        $page = isset($param['page'])?$param['page']:0;
        $limit = isset($param['limit'])?$param['limit']:config('eoffice.pagesize');
        $query = $this->entity->select('user.user_id', 'user.user_name','department.dept_name','user_system_info.user_status')
                            ->leftJoin('user_system_info', 'user.user_id','=','user_system_info.user_id')
                            ->leftJoin('department', 'user_system_info.dept_id', '=', 'department.dept_id')
                            ->whereIn('user.user_id', $userId);
        if(isset($param['search'])){
            $query->wheres($param['search']);
        }
        $query = $query->parsePage($page, $limit);
        return $query->get()->toArray();

    }
    public function getUserNamesByPageTotal($userId,$param) {
        $page = isset($param['page'])?$param['page']:0;
        $limit = isset($param['limit'])?$param['limit']:config('eoffice.pagesize');
        $query = $this->entity->select('user.user_id', 'user.user_name','department.dept_name','user_system_info.user_status')
                            ->leftJoin('user_system_info', 'user.user_id','=','user_system_info.user_id')
                            ->leftJoin('department', 'user_system_info.dept_id', '=', 'department.dept_id')
                            ->whereIn('user.user_id', $userId);
        if(isset($param['search'])){
            $query->wheres($param['search']);
        }
        return $query->count();

    }

    /**
     * 获取用户名
     * @param  string $userId 必填，用户id
     * @return string         this user name
     */
    public function getUserIds($userName) {
        return $this->entity->select('user_id', 'user_name')
                            ->whereIn('user_name', $userName)
                            ->where('user_accounts', '!=', '')
                            ->get()->toArray();
    }

    /** 通过手机号获取用户
     * @param $phoneNumbers
     * @param array $fields
     * @return mixed
     */
    public function getUserByPhoneNumber($phoneNumbers, $fields = [], $include_leave = true)
    {
        if ($fields) {
            $query = $this->entity->select($fields);
        } else {
            $query = $this->entity;
        }
        $query = $query->leftJoin('user_info', 'user.user_id', '=', 'user_info.user_id');
        if (!$include_leave) {
            $query = $query->leftJoin('user_system_info', 'user.user_id', '=', 'user_system_info.user_id')
                           ->whereNotIn('user_system_info.user_status', [0, 2]);
        }
        return $query->whereIn('phone_number', $phoneNumbers)->get()->toArray();
    }
    public function getUsersInfoByIds($userId, $param) {
        $query = $this->entity->whereIn('user_id', $userId);
        if ($param) {
            $query = $query->wheres($param);
        }
        return $query->with('userHasOneInfo')->with('userHasOneSystemInfo')
                     ->with(['userToDept' => function ($query) {
                                $query->select('dept_name');
                            }])->get()->toArray();
    }
    public function getUserAccountCache()
    {
        $query = $this->entity
                ->select(['user.user_id', 'user_accounts', 'password'])
                ->leftJoin('user_system_info', 'user.user_id', '=', 'user_system_info.user_id')
                ->where('user_accounts', '!=', '')
                ->whereNotIn('user_status', [0, 2]);
        return $query->get();
    }
    public function getUserDataByAccount($userAccount)
    {
        return $this->entity->where('user_accounts', $userAccount)->first();
    }

    public function getUserInfoAll($params)
    {
        $fields = $params['fields'] ?? ['*'];
        return $this->entity->select($fields)->get()->toArray();
    }
}
