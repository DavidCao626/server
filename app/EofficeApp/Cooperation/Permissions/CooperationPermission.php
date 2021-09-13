<?php
namespace App\EofficeApp\Cooperation\Permissions;
use DB;

class CooperationPermission
{
    private $cooperationSubjectRepository;
    private $cooperationRevertRepository;
    private $cooperationService;
    public function __construct() {
        $this->cooperationSubjectRepository = 'App\EofficeApp\Cooperation\Repositories\CooperationSubjectRepository';
        $this->cooperationRevertRepository = 'App\EofficeApp\Cooperation\Repositories\CooperationRevertRepository';
        $this->cooperationSortRepository = 'App\EofficeApp\Cooperation\Repositories\CooperationSortRepository';
        $this->cooperationService = 'App\EofficeApp\Cooperation\Services\CooperationService';
    }

    /**
     * 获取协作详情
     * @param  [type] $own
     * @param  [type] $data
     * @param  [type] $urlData
     * @return [type] boolean
     */
    public function getCooperationSubjectDetail($own, $data, $urlData) {
        $param = [];

        $param['user_id'] = $own['user_id'];
        $param['dept_id'] = $own['dept_id'];
        $param['role_id'] = implode(',', $own['role_id']);
        if (!isset($urlData['subjectId']) && empty($urlData['subjectId'])) {
            return ['code' => ['0x007001', 'cooperation']];
        }
        $cooperationId = $urlData['subjectId'];
        if(count(app($this->cooperationSubjectRepository)->verifySubjectPurview($cooperationId, $param))) {
            return true;
        }
        return false;
    }

    /**
     * 编辑协作回复权限判定
     * @param  [type] $own
     * @param  [type] $data
     * @param  [type] $urlData
     * @return [type] boolean
     */
    public function editCooperationRevertFirst($own, $data, $urlData) {
        if (!isset($data['subject_id']) && empty($data['subject_id'])) {
            return ['code' => ['0x007001', 'cooperation']];
        }
        $currentUserId = $own['user_id'];
        $urlData['subjectId'] = $data['subject_id'];
        if (!isset($urlData['revertId']) && empty($urlData['revertId'])) {
            return ['code' => ['0x007012', 'cooperation']];
        }
        $result = $this->getCooperationSubjectDetail($own, $data, $urlData);
        $detail = app($this->cooperationSubjectRepository)->getDetail($data['subject_id']);
        $manageUser = explode(',', $detail->subject_manage);
        $revertDetail = app($this->cooperationRevertRepository)->getDetail($urlData['revertId']);
        $revertUser = $revertDetail->revert_user;
        $revertTime = $revertDetail->revert_time;
        $currentTime = date('Y-m-d H:i:s', time());
        $lastTime = date("Y-m-d H:i:s", (strtotime($revertTime)+600));

        if ($lastTime <= $currentTime) {
            return false;
        }
        if ($result && ($currentUserId == $revertUser)) {
            return true;
        }
        return false;
    }

    /**
     * 删除协作回复权限控制
     * @param  [type] $own
     * @param  [type] $data
     * @param  [type] $urlData
     * @return [type] boolean
     */
    public function deleteCooperationRevertFirst($own, $data, $urlData) {
        if (!isset($data['subject_id']) && empty($data['subject_id'])) {
            return ['code' => ['0x007001', 'cooperation']];
        }
        $urlData['subjectId'] = $data['subject_id'];
        if (!isset($urlData['revertId']) && empty($urlData['revertId'])) {
            return ['code' => ['0x007012', 'cooperation']];
        }
        $currentUserId = $own['user_id'];
        $urlData['subjectId'] = $data['subject_id'];
        $result = $this->getCooperationSubjectDetail($own, $data, $urlData);
        $detail = app($this->cooperationSubjectRepository)->getDetail($data['subject_id']);
        $manageUser = explode(',', $detail->subject_manage);
        $revertDetail = app($this->cooperationRevertRepository)->getDetail($urlData['revertId']);
        $revertTime = $revertDetail->revert_time;
        $currentTime = date('Y-m-d H:i:s', time());
        $lastTime = date("Y-m-d H:i:s", (strtotime($revertTime)+600));
        if (in_array($currentUserId, $manageUser)) {
            return true;
        }
        if ($lastTime <= $currentTime) {
            return false;
        }

        $revertUser = $revertDetail->revert_user;
        if ($result && ($currentUserId == $revertUser || in_array($currentUserId, $manageUser))) {
            return true;
        }
        return false;
    }
    /**
     * 权限判定公共方法
     * @param  [type] $own
     * @param  [type] $data
     * @param  [type] $urlData
     * @return [type] boolean
     */
    public function cooperationRevertPermissions($own, $data, $urlData) {
        if (!isset($data['subject_id']) && empty($data['subject_id'])) {
            return ['code' => ['0x007001', 'cooperation']];
        }
        if (!isset($urlData['revertId']) && empty($urlData['revertId'])) {
            return ['code' => ['0x007012', 'cooperation']];
        }
        $currentUserId = $own['user_id'];
        $urlData['subjectId'] = $data['subject_id'];
        $result = $this->getCooperationSubjectDetail($own, $data, $urlData);
        $detail = app($this->cooperationSubjectRepository)->getDetail($data['subject_id']);
        $manageUser = explode(',', $detail->subject_manage);
        $revertDetail = app($this->cooperationRevertRepository)->getDetail($urlData['revertId']);
        $revertTime = $revertDetail->revert_time;
        $currentTime = date('Y-m-d H:i:s', time());
        $lastTime = date("Y-m-d H:i:s", (strtotime($revertTime)+600));
        if ($lastTime <= $currentTime) {
            return false;
        }

        $revertUser = $revertDetail->revert_user;
        if ($result && ($currentUserId == $revertUser || in_array($currentUserId, $manageUser))) {
            return true;
        }
        return false;
    }

    /**
     * 创建协作时进行分类权限判定
     * @param  [type] $own
     * @param  [type] $data
     * @param  [type] $urlData
     * @return [type]
     */
    public function createCooperationSubject($own, $data, $urlData) {

        $currentUserId = $own['user_id'];
        $currentRoleId = $own['role_id'];

        if (!isset($data['cooperation_sort_id']) && empty($data['cooperation_sort_id'])) {
            return ['code' => ['0x007003', 'cooperation']];
        }
        $param = [];
        $param['user_id'] = $currentUserId;
        $param['role_id'] = implode(',', $own['role_id']);
        $param['dept_id'] = $own['dept_id'];
        $param['search'] = '{"cooperation_sort_id":'. $data["cooperation_sort_id"].'}';
        $result = app($this->cooperationSortRepository)->getSortDetail($data['cooperation_sort_id']);

        $userId = array_unique(array_column($result, 'user_id'));
        $roleId = array_unique(array_column($result, 'role_id'));
        $deptId = array_unique(array_column($result, 'dept_id'));
        $role = array_intersect($currentRoleId, $roleId);
        $results = $result[0] ?? '';
        $subject_dept = $results['member_dept'];
        $subject_role = $results['member_user'];
        $subject_user = $results['member_role'];
        if ($subject_dept == 'all' || $subject_role == 'all' || $subject_user == 'all') {
            return true;
        }
        if (in_array($currentUserId, $userId) || $role || in_array($own['dept_id'], $deptId)) {
            return true;
        }
        return false;
    }

    public function updateCooperationSubjectViewTime($own, $data, $urlData) {
        $currentUserId = $own['user_id'];
        $currentRoleId = $own['role_id'];

        if (!isset($urlData['subjectId']) && empty($urlData['subjectId'])) {
            return ['code' => ['0x007001', 'cooperation']];
        }
        $detail = app($this->cooperationSubjectRepository)->getDetail($urlData['subjectId']);
        $manage = app($this->cooperationService)->getCooperationManageService('', $urlData['subjectId']);
        $subjectManage = explode(',', $manage['subject_manage']);
        if ($detail) {
            $manageUser = array_unique(array_merge($subjectManage, explode(',', $detail->subject_manage)));
            $createUser = $detail->subject_creater;
            $param = [];
            $param['user_id'] = $currentUserId;
            $param['role_id'] = implode(',', $own['role_id']);
            $param['dept_id'] = $own['dept_id'];
            $result = app($this->cooperationSubjectRepository)->getSortDetail($urlData['subjectId']);
            $sortResult = app($this->cooperationSortRepository)->getSortDetail($detail->cooperation_sort_id);
            // 协作分类判断
            $sortResults = $sortResult[0] ?? '';
            $sortRole = isset($sortResults['member_role']) ? $sortResults['member_role'] :'';
            $sortUser = isset($sortResults['member_user']) ? $sortResults['member_user'] : '';
            $sortDept = isset($sortResults['member_dept']) ? $sortResults['member_dept'] : '';
            $userId = array_unique(array_column($result, 'user_id'));
            $roleId = array_unique(array_column($result, 'role_id'));
            $deptId = array_unique(array_column($result, 'dept_id'));
            $role = array_intersect($currentRoleId, $roleId);
            $results = $result[0] ?? '';
            $subject_dept = $results['subject_dept'];
            $subject_role = $results['subject_role'];
            $subject_user = $results['subject_user'];
            if ($subject_dept == 'all' || $subject_role == 'all' || $subject_user == 'all') {
                return true;
            }
            if (($sortDept == 'all' || $sortRole == 'all' || $sortUser == 'all') && (in_array($currentUserId, $userId) || $role || in_array($own['dept_id'], $deptId) || in_array($currentUserId, $manageUser) || $currentUserId == $createUser)) {
                return true;
            }
            if (in_array($currentUserId, $userId) || $role || in_array($own['dept_id'], $deptId) || in_array($currentUserId, $manageUser) || $currentUserId == $createUser) {
                return true;
            }
        }

        return false;
    }

    public function createCooperationRevert($own, $data, $urlData) {
        if (!isset($data['subject_id']) && empty($data['subject_id'])) {
            return ['code' => ['0x007001', 'cooperation']];
        }
        $urlData['subjectId'] = $data['subject_id'];
        return $this->updateCooperationSubjectViewTime($own, $data, $urlData);
    }

    public function getCooperationManage($own, $data, $urlData) {
        if (!isset($urlData['revertId']) && empty($urlData['revertId'])) {
            return ['code' => ['0x007001', 'cooperation']];
        }
        $urlData['subjectId'] = $urlData['revertId'];
        return $this->updateCooperationSubjectViewTime($own, $data, $urlData);
    }

    public function editCooperationSubject($own, $data, $urlData) {
        return $this->deleteCooperationSubject($own, $data, $urlData);
    }

    /**
     * 删除协作
     * @param  [type] $own
     * @param  [type] $data
     * @param  [type] $urlData
     * @return [type]
     */
    public function deleteCooperationSubject($own, $data, $urlData) {

        if (!isset($urlData['subjectId']) && empty($urlData['subjectId'])) {
            return ['code' => ['0x007001', 'cooperation']];
        }
        $currentUserId = $own['user_id'];
        $result = $this->getCooperationSubjectDetail($own, $data, $urlData);
        $detail = app($this->cooperationSubjectRepository)->getDetail($urlData['subjectId']);
        $manageUser = explode(',', $detail->subject_manage);
        $createUser = $detail->subject_creater;
        $manageResult = DB::table('cooperation_subject_manage')->where('subject_id', $urlData['subjectId'])->get()->toArray();
        $manageResults = array_column($manageResult, 'user_id');
        $manageUserLast = array_unique(array_merge($manageUser, $manageResults));
        if ($result && ($currentUserId == $createUser || in_array($currentUserId, $manageUserLast))) {
            return true;
        }
        return false;
    }
}
