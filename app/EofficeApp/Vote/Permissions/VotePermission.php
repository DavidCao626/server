<?php
namespace App\EofficeApp\Vote\Permissions;
use DB;
class VotePermission
{
    // 验证引擎会优先调用类里拥有的方法，如果没有则从该数组匹配找到对应的方法调用。
    public $rules = [
        'saveVoteData' => 'getVoteManageInfo', // 提交投票调查，检查查看权限
    ];

    public function __construct() 
    {
        $this->voteRepository  = 'App\EofficeApp\Vote\Repositories\VoteRepository';

    }

    /**
     * 验证查看投票结果权限
     * @param type $own     // 当前用户信息
     * @param type $data    // 前端提交的数据或参数
     * @param type $urlData // restful api 上带的参数
     * @return boolean
     */
    public function getVoteResult($own, $data, $urlData){
        $vote = $this->getVote($urlData['voteId']);
        if(!$vote || ($vote->open_result != 1) && !in_array(251,$own['menus']['menu'])){
            return false;
        }
        if ($this->checkVoteViewPermission($vote, $own)) {
            return true;
        }
        return false;
    }

    /**
     * 验证查看投票调查表权限(查看投票状态)
     * @param type $own     // 当前用户信息
     * @param type $data    // 前端提交的数据或参数
     * @param type $urlData // restful api 上带的参数
     * @return boolean
     */
    public function getVoteInUser($own, $data, $urlData){
        $vote = $this->getVote($urlData['voteId']);
        if($vote && $vote->active == 0){
            return false;
        }
        if(!$vote || ($vote->open_result != 1) && !in_array(251,$own['menus']['menu'])){
            return false;
        }
        if ($this->checkVoteViewPermission($vote, $own)) {
            return true;
        }
        return false;
    }

    /**
     * 验证获取调查表设置详情权限
     * @param type $own     // 当前用户信息
     * @param type $data    // 前端提交的数据或参数
     * @param type $urlData // restful api 上带的参数
     * @return boolean
     */
    public function getVoteManageInfo($own, $data, $urlData){
        $vote = $this->getVote($urlData['voteId']);
        if ($this->checkVoteViewPermission($vote, $own)) {
            return true;
        }
        return false;
    }

    private function checkVoteViewPermission($vote, $own) {
        if (!$vote) {
            return false;
        }
        if (in_array(251, $own['menus']['menu'])) {
            return true;
        }
        $voteId = $vote->id;
        if ($vote->creator === $own['user_id']) {
            return true;
        }
        if($vote->all_user == 1){
            return true;
        } else {
            // 验证部门权限
            $deptData = DB::table('vote_dept')->select('dept_id')->where(['vote_id' => $voteId,'dept_id'=>$own['dept_id']])->first();
            if ($deptData) return true;
            // 验证用户权限
            $userData = DB::table('vote_user')->select('user_id')->where(['vote_id' => $voteId,'user_id'=>$own['user_id']])->first();
            if ($userData) return true;
            // 验证角色权限
            $role = DB::table('vote_role')->select('role_id')->where('vote_id', $voteId)->whereIn('role_id',$own['role_id'])->first();
            if ($role) return true;
            return false;
        }
    }

    private function getVote($voteId)
    {
        return app($this->voteRepository)->entity->select('open_result','all_user','active', 'creator', 'id')->where('id', $voteId)->first();
    }
}
