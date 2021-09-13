<?php
namespace App\EofficeApp\User\Repositories;

use App\EofficeApp\User\Entities\UserSystemInfoEntity;
use App\EofficeApp\Base\BaseRepository;

/**
 * 用户Systeminfo表知识库
 *
 * @author 丁鹏
 *
 * @since  2015-10-16 创建
 */
class UserSystemInfoRepository extends BaseRepository
{
    public function __construct(UserSystemInfoEntity $entity) 
    {
        parent::__construct($entity);
    }
    
    public function getInfoByWhere($where,$field=["*"])
    {
        return $this->entity->select($field)->wheres($where)->get()->toArray();
    }
    public function getOneUserSystemInfo($where,$field=["*"])
    {
        return $this->entity->select($field)->wheres($where)->first();
    }
    /**
     * 删除用户系统信息
     *
     * @param  int $userId 用户id
     *
     * @return integer
     *
     * @author miaochenchen
     *
     * @since  2016-05-17
     */
    public function deleteUserSystemInfoByUserId($userId)
    {
    	return $this->entity->where('user_id',$userId)->delete();
    }

    public function getUserCountByDeptId($deptId) {
        return $this->entity->where('dept_id',$deptId)->whereNotIn('user_status', [0,2])->count();
    }
    public function getUserIdDeptMap($userId)
    {
        $users = $this->entity->select(['dept_id','user_id'])->whereIn('user_id',$userId)->get();
        
        $map = [];
        
        $deptId = [];
        
        foreach($users as $user){
            $map[$user->user_id] = $user->dept_id;
            
            $deptId[] = $user->dept_id;
        }
        
        return [$map, array_unique($deptId)];
    }
    /**
     * 获取手机用户列表
     *
     * @author 缪晨晨
     *
     * @param  
     *
     * @since  2017-06-02 创建
     *
     * @return array  返回手机用户列表和总数
     */
    public function getMobileUserList() {
        $mobileUserlist = $this->entity->select(['user_id'])->where('wap_allow', '1')->get();
        if(!empty($mobileUserlist)) {
            $mobileUserlist = $mobileUserlist->toArray();
        }
        return $mobileUserlist;
    } 

    /**
     * 设置手机用户
     *
     * @author 缪晨晨
     *
     * @param  
     *
     * @since  2017-06-02 创建
     *
     * @return boolean
     */
    public function setMobileUser($param) {
        $this->entity->where('user_id', '!=', '')->withTrashed()->update(["wap_allow" => 0]);
        if(!empty($param)) {
            return $this->entity->whereIn('user_id', $param)->update(["wap_allow" => 1]);
        }
        return true;
    }
    public function setMobileUserById($userId, $param) {
        return $this->entity->where('user_id', $userId)->update($param);
    }

    /**
     * 查询手机已使用用户数
     *
     * @author 缪晨晨
     *
     * @param  
     *
     * @since  2017-06-04 创建
     *
     * @return string
     */
    public function getMobileHasUsedNumber() {
        $search = [
            'user_status' => [0, '>'],
            'user_status' => [2, '!='],
            'wap_allow'   => [1]
        ];
        return $this->entity->wheres($search)->get()->count();
    }

    /**
     * 查询是否允许手机访问
     *
     * @author 缪晨晨
     *
     * @param string $userId
     *
     * @since  2017-06-04 创建
     *
     * @return string
     */
    public function checkWapAllow($userId) {
        $result = $this->entity->select(['wap_allow'])->where('user_id', $userId)->first();
        if(!empty($result)) {
            if($result->wap_allow == '1') {
                return true;
            }else{
                return false;
            }
        }else{
            return false;
        }
    } 

    /**
     * 检查是否存在离职用户
     *
     * @author 缪晨晨
     *
     * @param 
     *
     * @since  2017-06-09 创建
     *
     * @return string
     */
    public function checkExistleaveOffUser() {
        return $this->entity->where('user_status', '2')->get()->count();
    }

    
    public function getUserStatus($userId) {
    	$result = $this->entity->select(['user_status'])->where('user_id', $userId)->first();
    	if (!empty($result)) {
            return $result->user_status;
        } else {
            return 0;
        }
    }

    /**
     * @根据部门及子部门id获取用户ID
     *
     * @author 牛晓克
     *
     * @param array $deptId
     *
     * @return array 用户对象数组
     */
    public function getUserIdByDeptId($deptId, $where = []) {
        return $this->entity->select('user_id')
            ->whereIn('dept_id', $deptId)
            ->wheres($where)
            ->get()
            ->toArray();
    }
    public function getDeptIdByUserId($userId, $where = []) {
        return $this->entity->select('user_system_info.dept_id', 'department.dept_id', 'department.dept_name')
            ->leftJoin('department', 'department.dept_id', '=', 'user_system_info.dept_id')
            ->where('user_id', $userId)
            ->wheres($where)
            ->get()
            ->toArray();
    }

    /**
     * 获取离职或被删除的用户的id
     * @return array
     */
    public function getInvalidUserId()
    {
        $userIds = $this->entity->newQuery()
            ->where('user_status', 2)
            ->orWhere('deleted_at', '!=', null)
            ->withTrashed()
            ->pluck('user_id')
            ->toArray();

        return $userIds;
    }
     public function getUserListsWithDept() {
        return $this->entity->select('user_system_info.user_id', 'department.dept_id', 'department.dept_name','user.user_name')
            ->leftJoin('department', 'department.dept_id', '=', 'user_system_info.dept_id')
            ->leftJoin('user', 'user.user_id', '=', 'user_system_info.user_id')
            ->whereNotIn('user_status', [0])
            ->get()
            ->toArray();
    }
    public function getUserStatusTotal($param)
    {
        return $this->entity->select(['user_system_info.user_status'])
                            ->selectRaw('count(1) as user_count')
                            ->addSelect('user_status' )
                            ->groupby('user_status')
                            ->withTrashed()
                            ->get()->toArray();
    }

    public function getUserStatusYearTotal($time, $type)
    {
        $start = $time['stime'] ?? date('Y'). '01-01 00:00:00';
        $end = $time['etime'] ?? date('Y'). '01-01 00:00:00';
        $query =  $this->entity->select(['user_system_info.user_status'])
                            ->selectRaw('count(1) as user_count');
        $query = $query->where('created_at', '<', $end)->orWhere('updated_at', '<', $end);
        // if ($type == 1) {
        //     $query = $query->where('created_at', '<', $end)->orWhere('updated_at', '<', $end);
        // } else {
        //     $query = $query->whereBetween('created_at', $time)->orWhereBetween('updated_at', $time);
        // }
                            
        return $query->groupby('user_status')
            ->withTrashed()
            ->get()->toArray();
    }

    public function multiRemoveDept($userId, $deptId) {
        
        return $this->entity->whereIn('user_id', $userId)->update(['dept_id' => $deptId]);
    }
    public function getUserStatusDelete($where) {
        return $this->entity->wheres($where)->withTrashed()->count();
    }

    public function judgeUserManageScope($param)
    {
        $query = $this->entity->select(['user_system_info.user_id']);
        if (isset($param['loginUserInfo']['user_id']) && $param['loginUserInfo']['user_id'] != 'admin') {
            $query = $query->whereNotIn('user.user_id', [$param['loginUserInfo']['user_id'], 'admin']);
           
        }
        if (!isset($param['include_leave']) || !$param['include_leave']) {
            $query = $query->where('user_system_info.user_status', '!=', '2');
        } 
        
        if (isset($param['dept_id']) && !empty($param['dept_id'])) {
            $query = $query->whereIn('user_system_info.dept_id', $param['dept_id']);
        }
        return $query->pluck('user_id')->toArray();
    }
    public function getUsersNameByIds($userId, $param) {
        if (!is_array($userId)) {
            $userId = explode(',', $userId);
        }
        $query = $this->entity->whereIn('user_system_info.user_id', $userId)->leftJoin('user', 'user.user_id', '=', 'user_system_info.user_id');
        if (isset($param['include_leave']) && !$param['include_leave']) {
            $query->where('user_status', '>', 0)->where('user_status', '!=', 2);
        }
        return $query->pluck('user.user_name')->toArray();
    }

    public function getUserDeptAndRoleByIds($param)
    {
        $query = $this->entity->select(['user_system_info.user_id', 'user_system_info.dept_id', 'user_role.role_id'])
                      ->leftJoin('user_role', 'user_role.user_id', '=', 'user_system_info.user_id');
        if (isset($param['search'])) {
            $query = $query->wheres($param['search']);
        }
        return $query->get()->toArray();
    }
}
