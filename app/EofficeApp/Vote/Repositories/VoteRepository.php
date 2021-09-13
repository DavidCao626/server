<?php

namespace App\EofficeApp\Vote\Repositories;

use App\EofficeApp\Vote\Entities\VoteEntity;
use App\EofficeApp\Vote\Entities\VoteModeEntity;
use App\EofficeApp\User\Entities\UserSystemInfoEntity;
use App\EofficeApp\Role\Entities\UserRoleEntity;
use App\EofficeApp\User\Entities\UserEntity;
use App\EofficeApp\System\Department\Entities\DepartmentEntity;
use App\EofficeApp\Base\BaseRepository;
use DB;
use Schema;

/**
 * 调查表知识库
 *
 * @author shiyao
 *
 * @since  2017-06-21
 */
class VoteRepository extends BaseRepository {

    private $userSystemInfoEntity;
    private $userEntity;

    public function __construct(
        VoteEntity $entity,
        UserSystemInfoEntity $userSystemInfoEntity,
        UserEntity $userEntity,
        DepartmentEntity $departmentEntity
        ) {
        parent::__construct($entity);
        $this->userSystemInfoEntity = $userSystemInfoEntity;
        $this->userEntity = $userEntity;
        $this->departmentEntity = $departmentEntity;

    }
    /** @var int 默认列表条数 */
    private $limit      = 10;

    /** @var int 默认列表页 */
    private $page       = 0;

    /** @var array  默认排序 */
    private $orderBy    = ['created_at' => 'desc'];

    /**
     * 获取调查表管理列表数量
     *
     */
    public function getVoteManageTotal($param = [])
    {
        $default = [
            'page'      => 0,
            'order_by'  => ['created_at' => 'desc'],
            'limit'     => 10,
            'fields'    => ['*']
        ];

        $param = array_merge($default, array_filter($param));

        $query = $this->entity
        ->with(['voteCreateInfo'=>function($query) {
            $query->select('user_id','user_name');
        }])
        ->select($param['fields']);

        //$query = $query->where('creator',$userInfo['user_id']);

        if(isset($param['search'])) {
            $query = $query->wheres($param['search']);
        }

        return $query->count();
    }

    /**
     * 获取调查表管理列表
     *
     */
    public function getVoteManageList($param)
    {
        $default = [
            'page'      => 0,
            'order_by'  => ['active'=>'desc','created_at' => 'desc'],
            'limit'     => 10,
            'fields'    => ['*']
        ];
        if(isset($param['searchField']) && !empty($param['searchField'])) {
            $param['fields'] = explode(',',$param['searchField']);
            unset($param['searchField']);
        }
        $param = array_merge($default, array_filter($param));

        $query = $this->entity
        ->with(['voteCreateInfo'=>function($query) {
            $query->select('user_id','user_name');
        }])
        ->select($param['fields']);

        //$query = $query->where('creator',$userInfo['user_id']);

        if(isset($param['search'])) {
            $query = $query->wheres($param['search']);
        }

        $query =  $query->orders($param['order_by']);
        if($param['page'] != 0) {
            $query =  $query->forPage($param['page'], $param['limit']);
        }

        return $query->get()->toArray();
    }
    /**
     * 获取我的调查表列表
     *
     */
    public function getMineVoteList($param,$userInfo)
    {
        $default = [
            'page'      => 0,
            'order_by'  => ['created_at' => 'desc'],
            'limit'     => 10,
            'fields'    => ['*']
        ];
        $currentTime = date('Y-m-d H:i:s');
        $param = array_merge($default, array_filter($param));

        $query = $this->entity->select($param['fields']);

        $query = $query->where('active',1)
        ->where('start_time','<=',$currentTime)
        //->where('end_time','>=',$currentTime)
        ->with(['voteCreateInfo'=>function($query) {
            $query->select('user_id','user_name');
        }]);
        if(isset($param['search'])) {
            $query = $query->wheres($param['search']);
        }
        $query = $query->where(function($query) use($param) {
            $query = $query->where('all_user',1);
            if(isset($param['vote_ids'])) {
                $query = $query->orWhere(function($query) use ($param){
                    $query = $query->whereIn('id',$param['vote_ids']);
                });
            }
        });
        if(isset($param['read_vote_ids'])) {
            $query = $query->where(function($query) use ($param){
                $query = $query->whereNotIn('id',$param['read_vote_ids']);
            });
        }
        if(isset($param['unreadId'])) {
            $query = $query->whereNotIn('id',$param['unreadId']);
        }else if(isset($param['readId'])) {
            $query = $query->whereIn('id',$param['readId']);
        }

        return $query->orders($param['order_by'])
                ->forPage($param['page'], $param['limit'])
                ->get()->toArray();
    }
    /**
     * 获取我的调查表数量
     *
     */
    public function getMineVoteTotal($param,$userInfo)
    {
        $default = [
            'page'      => 0,
            'order_by'  => ['created_at' => 'desc'],
            'limit'     => 10,
            'fields'    => ['*']
        ];
        $currentTime = date('Y-m-d H:i:s');
        $param = array_merge($default, array_filter($param));


        $query = $this->entity->where('active',1)
        ->where('start_time','<=',$currentTime)
        //->where('end_time','>=',$currentTime)
        ->with(['voteCreateInfo'=>function($query) {
            $query->select('user_id','user_name');
        }]);
        if(isset($param['search'])) {
            $query = $query->wheres($param['search']);
        }
        $query = $query->where(function($query) use($param) {
           $query = $query->where('all_user',1);
           if(isset($param['vote_ids'])) {
               $query = $query->orWhere(function($query) use ($param){
                   $query = $query->whereIn('id',$param['vote_ids']);
               });
           }
        });
        if(isset($param['unreadId'])) {
            $query = $query->whereNotIn('id',$param['unreadId']);
        }else if(isset($param['readId'])) {
            $query = $query->whereIn('id',$param['readId']);
        }

        return $query->count();
    }
    /**
     * 调查表详情
     *
     */
    public function showVote($voteId)
    {
        $query = $this->entity
            ->where('id',$voteId)
            ->with('voteHasManyUser')
            ->with('voteHasManyRole')
            ->with('voteHasManyDept')
            ->with(['voteCreateInfo'=>function($query) {
                $query->select('user_id','user_name');
            }])
            ->with('voteHasManyControl')
            ->with(['voteHasManyControl'=>function($query) {
                $query->orderBy('position','asc');
            }])
            ->with('voteHasOneTemplate');
        return $query->first();
    }
    /**
     * 获取参与情况
     *
     */
    public function getVoteUsers($voteId, $voteInfo)
    {
        $tableName = "vote_data_" . $voteId;
        $voteInUserIds = $anonymousVoteUserIds = [];
        if (Schema::hasTable($tableName)) {
            if (Schema::hasColumn($tableName, 'user_id')) {
                $voteInUserIds = DB::table($tableName)->where(['vote_id' => $voteId,'anonymous'=>0])->pluck('user_id')->unique()->toArray();
                $anonymousVoteUserIds = DB::table($tableName)->where(['vote_id' => $voteId,'anonymous'=>1])->pluck('user_id')->unique()->toArray();
            }
        }

        $vote_priv_scope= $voteInfo['all_user'];
        $vote_dept_id= $voteInfo['vote_has_many_dept'];
        $vote_role_id= $voteInfo['vote_has_many_role'];
        $vote_user_id= $voteInfo['vote_has_many_user'];

        // 获取所有范围内的投票用户
        $users = $this->userEntity
            ->select(['user.user_name','user.user_id','user_system_info.dept_id','department.dept_name','user_system_info.user_status'])
            ->leftJoin('user_system_info','user.user_id','=','user_system_info.user_id')
            ->leftJoin('department','department.dept_id','=','user_system_info.dept_id')
            ->leftJoin('user_role','user.user_id','=','user_role.user_id')
            ->whereIn('user_system_info.user_status', [1, 2]);
        if($vote_priv_scope!=1){
            $users->whereIn('user_system_info.dept_id',$vote_dept_id)
            ->orWhere(function ($users) use($vote_role_id){
                $users->whereIn('user_role.role_id',$vote_role_id)
                ->groupBy('user.user_id');
            })
            ->orWhere(function ($users) use($vote_user_id){
                $users->whereIn('user_system_info.user_id',$vote_user_id);
            });
        }
        $users = $users->groupBy('user.user_id')->get();
        $anonymousVoteCount = count(array_intersect($anonymousVoteUserIds, $users->pluck('user_id')->toArray())); // 过滤非范围内的用户

        $tempUsers = [];
        $voteInUserIds = array_flip($voteInUserIds);
        $langLeave = trans('flow.resignation');
        $voteCount = 0;
        foreach ($users as $key => $value) {
            $deptId = $value['dept_id'];
            $isLeave = $value['user_status'] == 2;
            $isVoteUser = array_key_exists($value['user_id'], $voteInUserIds);
            // 离职的必须投票并且存在部门，才会计入总人数，否则删除
            if ($isLeave && (!$deptId || !$isVoteUser)) {
                $users->forget($key);
                continue;
            }
            if(!isset($tempUsers[$deptId])) {
                $tempUsers[$deptId]['dept_id'] = $deptId;
                $tempUsers[$deptId]['dept_name'] = $value['dept_name'];
                $tempUsers[$deptId]['not_vote_in'] = [];
                $tempUsers[$deptId]['vote_in'] = [];
            }
            $isVoteUser && $voteCount++;
            $tempUsers[$deptId][$isVoteUser ? 'vote_in' : 'not_vote_in'][] = [
                'is_leave' => $isLeave,
                'user_id' => $value['user_id'],
                'user_name' => $value['user_name'] . ($isLeave ? "【{$langLeave}】" : ''),
            ];
        }
        $userCount = $users->count();
        foreach ($tempUsers as $key => $item) {
            $tempUsers[$key]['vote_in_count'] = count($item['vote_in']);
            $tempUsers[$key]['not_vote_in_count'] = count($item['not_vote_in']);
        }
        $tempUsers = array_values($tempUsers);
        return [
            'dept_info' => $tempUsers,
            'total' => $userCount,
            'vote_count' => $voteCount,
            'anonymous_vote_count' => $anonymousVoteCount,
        ];
    }

    /**
     * 获取发布范围人员
     *
     */
    public function getVoteInUsers($voteId,$voteInfo)
    {

        $vote_priv_scope= $voteInfo['all_user'];
        $vote_dept_id= $voteInfo['vote_has_many_dept'];
        $vote_role_id= $voteInfo['vote_has_many_role'];
        $vote_user_id= $voteInfo['vote_has_many_user'];
        $users = $this->userEntity
            ->select(['user.user_id'])
            ->leftJoin('user_system_info','user.user_id','=','user_system_info.user_id')
            ->leftJoin('user_role','user.user_id','=','user_role.user_id');
        if($vote_priv_scope!=1){
            $users =$users
            ->whereIn('user_system_info.dept_id',$vote_dept_id)
            ->orWhere(function ($users) use($vote_role_id){
                $users=$users->whereIn('user_role.role_id',$vote_role_id)->groupBy('user.user_id');
            })
            ->orWhere(function ($users) use($vote_user_id){
                $users=$users->whereIn('user_system_info.user_id',$vote_user_id);
            });
        }
        $users=$users->groupBy('user.user_id')->get()->toArray();
        $userId=[];
        foreach ($users as $value) {
            $userId[]=$value['user_id'];
        }
        return $userId;
    }
    /**
     * 调查表数据列表
     *
     */
    public function getVoteDataList($tableName,$data,$voteId)
    {

        $default = [
            'page'      => 0,
            'order_by'  => ['created_at' => 'desc'],
            'limit'     => 10,
        ];
        $data = array_merge($default, array_filter($data));
        $result = DB::table($tableName)
        ->join('user_system_info','user_system_info.user_id',$tableName.'.user_id')
        ->join('department','user_system_info.dept_id','department.dept_id')
        ->join('user','user_system_info.user_id','user.user_id')
        ->select([$tableName.'.user_id',$tableName.'.vote_id',$tableName.'.id',$tableName.'.created_at','department.dept_name','user.user_name',$tableName.'.anonymous']);
        if(isset($data['user_name']) && !empty($data['user_name'])) {
           $result = $result->where('user.user_name', 'like', '%'.$data['user_name'].'%')->where('anonymous','0');
        }
        if(isset($data['anonymous']) && $data['anonymous']) {
           $result = $result->where('anonymous','1');
        }
        $result = $result->orderBy('created_at', $data['order_by']['created_at'])
                       ->forPage($data['page'], $data['limit']);
        $result = $result->get();
        $this->filterAUserInfo($result);
        return $result;

    }
    /**
     * 调查表数据列表
     *
     */
    public function getVoteDataListForExport($tableName,$data,$voteId)
    {

        $result = DB::table($tableName)
        ->join('user_system_info','user_system_info.user_id',$tableName.'.user_id')
        ->join('department','user_system_info.dept_id','department.dept_id');
        $result = $result->join('user',function($result) use($data){
            $result->on('user_system_info.user_id','=','user.user_id');
            if(isset($data['user_name']) && !empty($data['user_name'])) {
                unset($data['isanonymous']);
                $result->where('user.user_name','like','%'.$data['user_name'].'%');
            }
        });
        $result = $result->select([$tableName.'.*','department.dept_name','user.user_name']);

        if(isset($data['isanonymous'])) {
            $result = $result->where($tableName.'.anonymous', 1);
        }
        if(isset($data['user_name'])) {
            $result = $result->where($tableName.'.anonymous', 0);
        }
        $result = $result->where($tableName.'.vote_id',$voteId)->orderBy($tableName.'.created_at','desc')->get();
        $this->filterAUserInfo($result);
        return $result;

    }

    // 过滤匿名用户信息
    private function filterAUserInfo(&$data) {
        foreach ($data as $key => $item) {
            if ($item->anonymous == 1) {
                $data[$key]->dept_name = $data[$key]->user_id = $data[$key]->user_name = trans("vote.anonymous");
            }
        }
    }
    /**
     * 调查表数据列表
     *
     */
    public function getVoteDataCount($tableName,$data,$voteId)
    {

       $count = DB::table($tableName)
       ->join('user_system_info','user_system_info.user_id',$tableName.'.user_id')
       ->join('department','user_system_info.dept_id','department.dept_id')
       ->join('user','user_system_info.user_id','user.user_id')
       ->select([$tableName.'.user_id',$tableName.'.id',$tableName.'.created_at','department.dept_name','user.user_name']);
       if(isset($data['user_name']) && !empty($data['user_name'])) {
           $count = $count->where('user.user_name', 'like', '%'.$data['user_name'].'%');
       }
       $count = $count->count();
       return $count;
    }
    /**
     * 调查表数据列表
     *
     */
    public function getVoteDataDetail($tableName,$Id)
    {

       $result = DB::table($tableName)
       ->join('user_system_info','user_system_info.user_id',$tableName.'.user_id')
       ->join('department','user_system_info.dept_id','department.dept_id')
       ->join('user','user_system_info.user_id','user.user_id')
       ->select([$tableName.'.*','department.dept_name','user.user_name']);

       $result = $result->where([$tableName.'.id'=>$Id])->get()->first();
       return $result;
    }
    /**
     * 调查表数据列表
     *
     */
    public function getVoteModeUsedList()
    {
        $query = $this->entity->select(['template']);
        return $query->groupBy('template')->get();
    }
    /**
     * 调查表数据列表
     *
     */
    public function closeOutTimeVotes()
    {
        $currentTime = date("Y-m-d H:i:s");
        $query = $this->entity;
        $query = $query->where('active',[1])->where('end_time','<',$currentTime);
        return $query = $query->update(['active'=>0]);
    }
}
