<?php

namespace App\EofficeApp\Diary\Services;

use Eoffice;
use Cache;
use App\EofficeApp\Base\BaseService;
use DB;
use Illuminate\Support\Facades\Redis;
use Schema;
/**
 * 微博Service类:提供微博模块相关服务
 *
 * @author qishaobo
 *
 * @since  2015-10-20 创建
 */
class DiaryService extends BaseService
{
    public function __construct()
    {
        parent::__construct();

        $this->userService                           = 'App\EofficeApp\User\Services\UserService';
        $this->roleService                           = 'App\EofficeApp\Role\Services\RoleService';
        $this->userRepository                        = 'App\EofficeApp\User\Repositories\UserRepository';
        $this->diaryRepository                       = 'App\EofficeApp\Diary\Repositories\DiaryRepository';
        $this->diaryPermissionRepository             = 'App\EofficeApp\Diary\Repositories\DiaryPermissionRepository';
        $this->customerService                       = 'App\EofficeApp\Customer\Services\CustomerService';
        $this->attachmentService                     = 'App\EofficeApp\Attachment\Services\AttachmentService';
        $this->diaryMemoRepository                   = 'App\EofficeApp\Diary\Repositories\DiaryMemoRepository';
        $this->diaryReplyRepository                  = 'App\EofficeApp\Diary\Repositories\DiaryReplyRepository';
        $this->diaryAttentionRepository              = 'App\EofficeApp\Diary\Repositories\DiaryAttentionRepository';
        $this->diaryVisitRecordRepository            = 'App\EofficeApp\Diary\Repositories\DiaryVisitRecordRepository';
        $this->diaryTemplateSetRepository            = 'App\EofficeApp\Diary\Repositories\DiaryTemplateSetRepository';
        $this->diaryTemplateSetUserRepository        = 'App\EofficeApp\Diary\Repositories\DiaryTemplateSetUserRepository';
        $this->diaryTemplateUserModalRepository      = 'App\EofficeApp\Diary\Repositories\DiaryTemplateUserModalRepository';
        $this->diaryPlanContentBlockRepository       = 'App\EofficeApp\Diary\Repositories\DiaryPlanContentBlockRepository';
        $this->diaryPlanContentBlockDetailRepository = 'App\EofficeApp\Diary\Repositories\DiaryPlanContentBlockDetailRepository';
        $this->customerContactRecordRepository       = 'App\EofficeApp\Customer\Repositories\ContactRecordRepository';
        $this->customerContactRecordService          = 'App\EofficeApp\Customer\Services\ContactRecordService';
        $this->diaryLikePeopleRepository             = 'App\EofficeApp\Diary\Repositories\DiaryLikePeopleRepository';
        $this->attendanceService                     = 'App\EofficeApp\Attendance\Services\AttendanceService';
        $this->diaryAttentionGroupRepository         = 'App\EofficeApp\Diary\Repositories\DiaryAttentionGroupRepository';
        $this->diaryAttentionGroupUsersRepository    = 'App\EofficeApp\Diary\Repositories\DiaryAttentionGroupUsersRepository';
        $this->diaryTemplateContentRepository    = 'App\EofficeApp\Diary\Repositories\DiaryTemplateContentRepository';
        $this->diaryPurviewRepository             = 'App\EofficeApp\Diary\Repositories\DiaryPurviewRepository';
        $this->departmentDirectorRepository     = "App\EofficeApp\System\Department\Repositories\DepartmentDirectorRepository";
        $this->departmentService                = "App\EofficeApp\System\Department\Services\DepartmentService";
        $this->userRepository = 'App\EofficeApp\User\Repositories\UserRepository';
        $this->userMenuService                          = 'App\EofficeApp\Menu\Services\UserMenuService';
        $this->systemComboboxFieldRepository = 'App\EofficeApp\System\Combobox\Repositories\SystemComboboxFieldRepository';
    }

    /**
     * 查询日志关注人
     *
     * @param  array  $param 查询条件
     *
     * @return array  查询结果或错误码
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    public function getDiaryAttentionList($param = [],$own)
    {
        $param = $this->parseParams($param);
        if(isset($param['search']['attention_person'])){
            $param['search']['attention_person'] = [$own['user_id']];
        }
        if(isset($param['search']['attention_to_person'])){
            $param['search']['attention_to_person'] = [$own['user_id']];
        }
        $param['diarySet'] = $this->getPermission();
        $data= [];
        $data = $this->response(app($this->diaryAttentionRepository), 'getAttentionTotal', 'diaryAttentionList', $param);
        // 如果有下级参数，则选出下级
        if (!empty($param['withSuperior'])) {
            $userId = $param['search']['attention_person'][0];

            // 获取用户所有下级,和关注合并起来
            // $defaultUserIds = app($this->userService)->getAllUserSuperior($userId);
            // $defaultAttentionList = isset($defaultUserIds['list']) ? $defaultUserIds['list'] : [];
            $defaultAttentionList = $this->getDefaultAttention($own);

            foreach ($defaultAttentionList as $v) {
                $data['list'][] = [
                    'attention_person' => $userId,
                    'attention_to_person' => $v['user_id'],
                    'attention_status' => 2
                ];
            }
        }
        if(isset($param['search']['attention_person']) && count($data['list']) > 0  && !isset($param['searchList'])){
            foreach($data['list'] as $key=>$val){
                $attentionToPerson  = $val['attention_to_person'];
                $groupsInfo = app($this->diaryAttentionGroupRepository)->getAttentionGroupByUserId($param['search']['attention_person'],$attentionToPerson);
                $data['list'][$key]['groupsInfo'] = $groupsInfo;
            }

        }
        return $data;
    }

    /**
     * 添加日志关注人
     *
     * @param  array  $input 要添加的数据
     *
     * @return int|array  返回结果或错误码
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    public function createDiaryAttention($input, $own)
    {
        $userId = $own['user_id'];
        if (!is_array($input['attention_to_person'])) {
            $input['attention_to_person'] = explode(',', $input['attention_to_person']);
        }

        $attToPersons = array_filter($input['attention_to_person']);
        $status = $userId == 'admin' ? 2 : 1;
        $data = [];

        if (!empty($attToPersons)) {
            $num = $total = 0;
            $data = [
                'attention_person'    => $userId,
                'attention_status'    => $status,
            ];

            foreach ($attToPersons as $attToPerson) {
                $total++;
                $data['attention_to_person'] = $attToPerson;
                $where = [
                    'attention_person'      => [$data['attention_person']],
                    'attention_to_person'   => [$data['attention_to_person']]
                ];

                if ($obj = app($this->diaryAttentionRepository)->getAttentionDetail($where)) {
                    if ($obj->attention_status == 3) {
                        app($this->diaryAttentionRepository)->updateData($data, $where);
                        $num++;
                    }
                    continue;
                }
                // 验证是否在我的关注、下属中
                if($this->userInAttention($attToPerson,$own)) {
                    continue;
                }
                // 正常添加
                if (app($this->diaryAttentionRepository)->insertData($data)) {
                    $num++;
                }
            }

            if ($num > 0) {
                return $total == $num ? 1 : ['code' => ['0x008004','diary']];
            }
        }

        return ['code' => ['0x008003','diary']];

    }

    /**
     * 更新日志关注人
     *
     * @param  int|array $attentionIds    关注表id,多个用逗号隔开
     * @param  int       $attentionStatus 关注状态
     *
     * @return int|array              返回结果或错误码
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    public function updateDiaryAttention($attentionIds, $attentionStatus, $userId)
    {
        $data = ['attention_status'  => $attentionStatus];
        $where = [
            'attention_to_person' => [$userId],
            'attention_status'  => [1]
        ];
        if ($attentionIds == 'all') {
            // 不做任何事情
        } else {
            $where['attention_id'] = array_filter(explode(',', $attentionIds));
        }
        $num = app($this->diaryAttentionRepository)->getTotal(['search' => $where]);
        if ($num == 0) {
            // 没有未批准申请
            return ['code' => ['0x008005','diary']];
        }else{
            // 仅修改状态为1的
            if (app($this->diaryAttentionRepository)->updateData($data, $where)) {
                return true;
            }
            return ['code' => ['0x000003','common']];
        }

    }

    /**
     * 删除日志关注人(主键)
     *
     * @param  int|array  $attentionId 关注表id,多个用逗号隔开
     *
     * @return int|array  返回结果或错误码
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    public function deleteDiaryAttention($attentionId, $userId)
    {
        $attentionIds = array_filter(explode(',', $attentionId));
        // 微博主页使用了 传递被关注人id 取消关注
        if (!is_numeric($attentionIds[0])) {
            // 删除关注人
            $where = [
                'attention_to_person' => [$attentionIds[0]],
                'attention_person' => [$userId],
            ];
            if (app($this->diaryAttentionRepository)->deleteByWhere($where)) {
                //删除分组中该人
                $this->deleteAttentionGroupUser($attentionIds[0],$userId);
                return true;
            }
        }else{
            // 删除关注id
            $attentionInfo = app($this->diaryAttentionRepository)->getAttentionDetail(['attention_id'=>[$attentionIds]]);
            if($attentionInfo){
                $attentionInfo = $attentionInfo->toArray();
                if(isset($attentionInfo['attention_to_person']) && isset($attentionInfo['attention_person'])){
                    if($attentionInfo['attention_to_person'] == $userId || $attentionInfo['attention_person'] == $userId ){
                        if (app($this->diaryAttentionRepository)->deleteById($attentionIds)) {
                            //删除 关注人的分组中的被关注人信息
                            $this->deleteAttentionGroupUser($attentionInfo['attention_to_person'],$attentionInfo['attention_person']);
                            return true;
                        }
                    }else{
                        // 该条关注信息不是我的关注 或关注我的 无权限
                        return ['code' => ['0x000006','common']];
                    }
                }
            }
        }
        // 提交数据异常
        return ['code' => ['0x008002','diary']];
    }

    /**
     * 查询日志浏览记录
     *
     * @param  array  $param 查询条件
     *
     * @return array  查询结果或错误码
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    public function getDiaryVisitList($param = [],$own)
    {
        $param = $this->parseParams($param);
        if(isset($param['search']['visit_person'])){
            $param['search']['visit_person'] = [$own['user_id']];
        }
        if(isset($param['search']['visit_to_person'])){
            $param['search']['visit_to_person'] = [$own['user_id']];
        }
        //$param['diarySet'] = app($this->systemSecurityService)->getSecurityOption('log');
        $param['diarySet'] = $this->getPermission();

        $visitSort = '';
        if (isset($param['search']['visit_sort'])) {
            $visitSort = $param['search']['visit_sort'];
            unset($param['search']['visit_sort']);
        }

        return $this->response(app($this->diaryVisitRecordRepository), 'getVisitTotal', 'diaryVisitList', $param);
    }

    /**
     * 更新日志浏览记录
     *
     * @param  string $visitToPerson 微博作者
     *
     * @return int|array             返回结果或错误码
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    public function saveDiaryVisit($visitToPerson, $userId)
    {
        if ($visitToPerson == $userId) {
            return 1;
        }

        $data = [
            'visit_person'    => $userId,
            'visit_to_person' => $visitToPerson,
        ];

        $where = [
            'visit_person'    => [$data['visit_person']],
            'visit_to_person' => [$data['visit_to_person']]
        ];

        $total = app($this->diaryVisitRecordRepository)->getTotal($where);

        if ($total > 0) {
            //增加访问量统一由查询结果成功后，增加
            //$result = app($this->diaryVisitRecordRepository)->diaryVisitIncrement($where);
        } else {
            $data['visit_num'] = 1;
            $result = app($this->diaryVisitRecordRepository)->insertData($data);
        }

        return 1;
    }

    /**
     * 查询日志
     *
     * @param  array  $param 查询条件
     *
     * @return array  查询结果或错误码
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    public function getDiaryList($param = [], $userId)
    {
        if (is_array($userId)) {
            $userInfo = $userId;
            $userId = $userInfo['user_id'];
        }
        $param = $this->parseParams($param);
        $diarySet = $this->getPermission();
        $param['diarySet'] = $diarySet;

        // 增加报告种类查询，默认为1|日报
        if(isset($param['search'])) {
            if(!isset($param['search']['plan_kind'])){
                $param["search"]["plan_kind"] = [1];
            }
        }

        if (isset($param['getTotal'])) {
            $total = app($this->diaryRepository)->getTotal($param);
            return ['total' => $total];
        }

        if (isset($param['search']) && isset($param['search']['user_id'])) {
            $this->saveDiaryVisit($param['search']['user_id'], $userId);
        }
        $weeks = $this->getWeeksTrans();
        $result = $resultByDate = [];
        if (isset($param['search']) && isset($param['search']['firstLoad'])) {
            $attentionPage = isset($param['page']) && !empty($param['page']) ? $param['page'] : '';
            // 我的微博页面不需要获取所有关注用户
            if (isset($param['lookType']) && $param['lookType'] == 'mine') {
                $canReadUserIds = [$userId];
            } else {
                $attentionUserids = $this->getMyAttention($userId, 1 ,$attentionPage); // 获取关注用户,包括默认和主动关注的
                $canReadUserIds = array_unique(array_merge([$userId], array_column($attentionUserids[0]['data'], 'user_id'), array_column($attentionUserids[1]['data'], 'user_id')));
            }
            // 如果设置了这个参数，则查询出所有关注用户的微博列表
            if (isset($param['withAttentions'])) {
                //过滤高级查询的部门和用户
                if(isset($param['highLevelSearch'])){
                    $deptIdArr = isset($param['search']['dept_id']) ? $param['search']['dept_id']:[];
                    $userIdArr = isset($param['search']['user_id']) ? $param['search']['user_id']:[];
                    $canReadUserIds = $this->filterSelectUserIdAndDeptId($canReadUserIds,$userIdArr,$deptIdArr);
                    unset($param['search']['dept_id']);
                }
                //查询关注分组
                if(isset($param['groupId']) && is_numeric($param['groupId'])){
                    $groupInfo = $this->getAttentionGroupInfo($param['groupId'],$userId);
                    $groupUsers = [];
                    if(isset($groupInfo[0]['users']) &&  count($groupInfo[0]['users']) > 0){
                        foreach($groupInfo[0]['users'] as $val){
                            if(isset($val['user_id']) && in_array($val['user_id'],$canReadUserIds)){
                                $groupUsers[] = $val['user_id'];
                            }
                        }
                    }
                    $canReadUserIds = $groupUsers;
                    unset($param['groupId']);
                }
                $param['search']['user_id'] = [$canReadUserIds, 'in'];
            } else {
                if (!in_array($param['search']['user_id'], $canReadUserIds)) {
                    // return ['code' => ['0x008006', 'diary']];
                    // 没有关注，返回关注状态和用户信息
                    $notFollowUserId = $param['search']['user_id'];
                    $followParams = [
                        'search' => [
                            'attention_person' => [$userId],
                            'attention_to_person' => [$notFollowUserId],
                        ],
                        'diarySet' => $diarySet,
                        "returntype" => "first",
                    ];
                    $followInfo = app($this->diaryAttentionRepository)->diaryAttentionList($followParams); // 获取我的关注
                    $followStatus = isset($followInfo->attention_status) ? $followInfo->attention_status : "";
                    //获取用户信息 供前端展示
                    $allUserInfo = app($this->userRepository)->getUserAllData($notFollowUserId)->toArray();
                    $depts = isset($allUserInfo['user_has_one_system_info']['user_system_info_belongs_to_department']) ? $allUserInfo['user_has_one_system_info']['user_system_info_belongs_to_department'] :[];
                    $roles = isset($allUserInfo['user_has_many_role']) ? $allUserInfo['user_has_many_role'] : [];
                    return [
                        "result_error" => "notFollow",
                        "user_info" => [
                            "user_id" => $notFollowUserId,
                            "user_name"=>$allUserInfo['user_name'],
                            "depts"=> [$depts],
                            "roles"=> $roles,
                            "dimission" => isset($allUserInfo['user_accounts']) && $allUserInfo['user_accounts'] == ""
                        ],
                        "attention_status" => $followStatus
                    ];
                }
                $param['search']['user_id'] = [$param['search']['user_id']];
            }
            unset($param['search']['firstLoad']);
        }

        if (isset($param['byDate'])) {
            // 我的微博菜单，获取数据，这种情况下，会传日期范围，日志内容会合并到对应的日期里面去
            unset($param['page']);
        }

        if (isset($param['byDate']) || isset($param['mobile'])) {
            $nowTime = time();
            $canEditTime = time() - ($diarySet['show_day']*24 + $diarySet['show_hour'])*3600;
        }
        // 默认只获取已经发布的报告
        // $param["search"]["plan_status"] = [2];
        if(isset($param["search"]) && isset($param["search"]["plan_status"])) {

        } else {
            $param["search"]["plan_status"] = [2];
        }
        $data = app($this->diaryRepository)->diaryList($param);
        //获取当前用户的所有下级用于系统工作记录
        $ancestor = app($this->userService)->getSubordinateArrayByUserId(own('user_id'), ['all_subordinate' => 1, 'include_leave' => true]);
        $ancestor = isset($ancestor['id']) ? $ancestor['id'] : [];
        array_push($ancestor,own('user_id'));
        if (!empty($data)){
            $added_visit_times_user = [];
            foreach ($data as $k => $v) {
                $week = date('w', strtotime($v['diary_date']));
                $v['diary_date_simple'] = $this->dateToSimpleStyle($v['diary_date']);
                $v['diary_mobile_date_simple'] = $this->mobileDateToSimpleStyle($v['diary_date']);
                $v['diary_week'] = $weeks[$week];
                // 系统工作记录权限
                $v['system_work_power'] = in_array($v['user_id'],$ancestor)?1:0;
                $v['attachments'] = app($this->attachmentService)->getAttachmentIdsByEntityId(['entity_table'=>'diary', 'entity_id'=>$v['diary_id']]);
                if (!empty($v['replys'])) {
                    foreach ($v['replys'] as $key => $val) {
                        $v['replys'][$key]['attachments'] = app($this->attachmentService)->getAttachmentIdsByEntityId(['entity_table'=>'diary_reply', 'entity_id' => $val['diary_reply_id']]);
                    }
                }
                if($v['plan_kind'] == 2) {
                    if (isset($v['plan_scope_date_start'])) {
                        $v['plan_scope_date_start_simple'] = $this->dateToSimpleStyle($v['plan_scope_date_start']);
                    }
                    if (isset($v['plan_scope_date_end'])) {
                        $v['plan_scope_date_end_simple'] = $this->dateToSimpleStyle($v['plan_scope_date_end']);
                    }
                }

                // 客户联系记录,手机端改为不显示，点击才显示
                // if (isset($param['withContactRecord']) && $v["plan_kind"] == "1" && isset($param['mobile']) && $param['mobile'] == 1) {
                if (isset($param['withContactRecord']) && isset($param['mobile']) && $param['mobile'] == 1) {
                    // 获取当前日志用户的id
                    $paramContactRecord = [
                        'search' => [
                            // 'permission' => app($this->customerService)->getUserTemporaryPermission($userInfo), // 递归获取所有下级用户，很慢? 临时客户模块穿透方法，以后修改
                            'permission' => app($this->customerService)->getUserPermission($userInfo), // 递归获取所有下级用户，很慢? 临时客户模块穿透方法，以后修改
                            'record_creator' => [$v['user_id']],
                            'created_at'   => [[$v['diary_date'].' 00:00:00', $v['diary_date'].' 23:59:59'], 'between'],
                        ]
                    ];
                    $contactRecords = app($this->customerContactRecordRepository)->getContactRecordList($paramContactRecord);
                    if ($param['withContactRecord'] == 'num') {
                        $v['contact_records'] = count($contactRecords);
                    } else {
                        if (!empty($contactRecords)) {
                            foreach ($contactRecords as $key => $val) {
                                $contactRecords[$key]['attachment_id'] = app($this->attachmentService)->getAttachmentIdsByEntityId(['entity_table' => 'customer_contact_record', 'entity_id' => $val['record_id']]);
                            }
                        }
                        $v['contact_records'] = $contactRecords;
                    }
                }
                // 添加访问记录,排除访问自己的情况
                // 每次获取到不是自己的微博，按人，每人增加一个访问量
                if (isset($param['addVisitRecord']) && $param['addVisitRecord'] == 1) {
                    if ($userId !== $v['user']['user_id']) {
                        if(! in_array($v['user']['user_id'],$added_visit_times_user)){
                            $recorded = false;
                            if(isset($param['record_users']) ){
                                $record_users = json_decode($param['record_users'],true);
                                if(count($record_users) > 0 && in_array($v['user']['user_id'],$record_users)){
                                    $recorded = true;
                                }
                            }
                            //没有记录过的，增加访问量
                            if(! $recorded){
                                array_push($added_visit_times_user,$v['user']['user_id']);
                                $where = [
                                    "visit_person"      => [$userId],
                                    "visit_to_person"   => [$v['user']['user_id']],
                                ];
                                $hasVisit = app($this->diaryVisitRecordRepository)->diaryHasVisit($where);
                                if ($hasVisit) {
                                    app($this->diaryVisitRecordRepository)->diaryVisitIncrement($where);
                                } else {
                                    $data = [
                                        "visit_person"      => $userId,
                                        "visit_to_person"   => $v['user_id'],
                                        "visit_num"         => 1,
                                    ];
                                    app($this->diaryVisitRecordRepository)->insertData($data);
                                }
                            }
                        }
                    }
                }

                if (isset($param['byDate']) || isset($param['mobile'])) {
                    //可以编辑和删除
                    $v['can_edit'] = strtotime($v['created_at']) > $canEditTime ? 1 : 0;
                }
                if (isset($param['byDate'])) {
                    $key = $v['diary_date'];
                } else {
                    $key = $k;
                    // $key = $v['diary_id'];
                    // $key = $v['diary_date'].''.$v['user_id'];
                }
                // 部门
                if(isset($v["user_system_info"])) {
                    $userDept = $v["user_system_info"];
                    $userDeptInfo = isset($userDept["user_system_info_belongs_to_department"]) ? $userDept["user_system_info_belongs_to_department"] : [];
                    $v["dept_name"] = isset($userDeptInfo["dept_name"]) ? $userDeptInfo["dept_name"] : "";
                    unset($v["user_system_info"]);
                } else {
                    $v["dept_name"] = "";
                }
                // 角色
                if(isset($v["user_has_many_role"])) {
                    $userRoleInfo = $v["user_has_many_role"];
                    $userRoleInfo = collect($userRoleInfo)->pluck("has_one_role");
                    $userRoleInfo = $userRoleInfo->pluck("role_name")->toArray();
                    $v["role_name"] = implode(",", $userRoleInfo);
                    unset($v["user_has_many_role"]);
                } else {
                    $v["role_name"] = "";
                }
                /**性能问题，暂时不获取
                if(is_array($userInfo)){
                    //获取客户联系信息
                    $v['contactRecord'] = $this->getContactRecord($userInfo,$v['diary_date'],$v['user']['user_id']);
                }
                 */
                $result[$key] = $v;
            }
        }
        if (isset($param['byDate'])) {
            $dataArray = $this->getDateArray($param['search']['diary_date'][0][0], $param['search']['diary_date'][0][1], $diarySet, $userId);
            $resultByDate = array_merge_recursive($dataArray, $result);
            return $resultByDate;
        }
        return $result;
    }

    /**
    * 将日期转换为页面显示的日期
    * @param string 日期
    * @return string 如：今天、昨天、6-16、2018-12-29
    */
    private function dateToSimpleStyle($date) {
        if(is_string($date)) {
            $currentYear = date("Y",time());
            $today = date("Y-m-d",time());
            $yesterday = date("Y-m-d",strtotime("-1 day"));
            $dateYmd = date("Y-m-d" , strtotime($date));
            $dateYear = date("Y" , strtotime($date));
            if($today == $dateYmd) {
                return trans("diary.today");
            }else if ($yesterday == $dateYmd) {
                return trans("diary.yesterday");
            }else if($dateYear == $currentYear) {
                return date("m-d" , strtotime($date));
            }else{
                return $dateYmd;
            }
        }
    }

    /**
     * 过滤高级查询的用户选择和部门选择
     * @param array 关注人列表
     * @param array 选择的人员列表
     * @param array 选择的部门列表
     * @return array 过滤后的人员列表
     */
    private function filterSelectUserIdAndDeptId($attentionList,$selectUserList,$selectDeptList){
        $selectDeptUserIds = [];
        $selectUserIds = [];
        // 处理非数组的传参
        if(is_string($selectUserList)){
            $selectUserList = [[$selectUserList],'in'];
        }
        if(count($selectUserList) == 0 && count($selectDeptList) == 0) return $attentionList;
        //用户查询
        if(count($selectUserList) > 0 ){
            $selectUserIds = $selectUserList[0];
        }
        //部门查询
        if(count($selectDeptList) > 0 ){
            $paramTmp = ['search'=>['dept_id'=>$selectDeptList]];
            $diarySet = $this->getPermission();
            //包含离职人员
            if (isset($diarySet['dimission']) && $diarySet['dimission'] == 1) {
                $paramTmp['include_leave'] = 1;
            }
            $selectDeptUsers = app($this->userRepository)->getUserList($paramTmp);
            $searchUsers = [];
            if(count($selectDeptUsers) > 0){
                foreach($selectDeptUsers as $val){
                    $searchUsers[] = $val['user_id'];
                }
            }
            $selectDeptUserIds = $searchUsers;
        }
        $users = [];
        //用户选择为空，返回部门选择
        //部门选择为空，返回用户选择
        if(count($selectUserIds) == 0 || count($selectDeptUserIds) == 0){
            if(count($selectUserIds) == 0)
                $searchUserIds = $selectDeptUserIds;
            if(count($selectDeptUserIds) == 0)
                $searchUserIds = $selectUserIds;
        }else{
            //两者都不为空 取并
            $searchUserIds = array_intersect($selectUserIds,$selectDeptUserIds);
        }
        foreach($attentionList as $key=>$val){
            if(!in_array($val,$searchUserIds)){
                //删除没有选择的关注人
                unset($attentionList[$key]);
            }
        }
        return $attentionList;
    }
    /**
    * 将日期转换为页面显示的日期
    * @param string 日期
    * @return string 如：今天、昨天、6-16、2018-12-29
    */
    private function mobileDateToSimpleStyle($date) {
        if(is_string($date)) {
            $currentYear = date("Y",time());
            $today = date("Y-m-d",time());
            $yesterday = date("Y-m-d",strtotime("-1 day"));
            $dateYmd = date("Y-m-d" , strtotime($date));
            $dateYear = date("Y" , strtotime($date));

            $first=1;
            //获取当前周的第几天 周日是 0 周一到周六是 1 - 6
            $w=date('w',strtotime($today));
            //获取本周开始日期，如果$w是0，则表示周日，减去 6 天
            $week_start=date('Y-m-d',strtotime("$today -".($w ? $w - $first : 6).' days'));
            //本周结束日期
            $week_end=date('Y-m-d',strtotime("$week_start +6 days"));
            $week=date("w",strtotime($date));
            $weekArr = ["周日","周一","周二","周三","周四","周五","周六"];
            if($today == $dateYmd) {
                return trans("diary.today");
            }else if ($yesterday == $dateYmd) {
                return trans("diary.yesterday");
            }else if($dateYmd <= $week_end && $dateYmd >= $week_start){
                return $weekArr[$week];
            }else if($dateYear == $currentYear) {
                return date("m-d" , strtotime($date));
            }else{
                return $dateYmd;
            }
        }
    }

    /**
     * 获取日期段
     *
     * @param  array  $start 开始日期
     * @param  array  $end 结束日期
     *
     * @return array  日期段
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    function getDateArray($start, $end, $diarySet, $userId)
    {
        $weeks = $this->getWeeksTrans();
        $dateArray = [];
        for ($i = $end; $i >= $start; $i = date('Y-m-d', strtotime("$i -1 day"))) {
            $time = strtotime("$i");
            $week = date('w', $time);
            // $dataItem = [
            //     'week' => $weeks[$week],
            //     'date' => $i,
            //     'diary_supplement' => $diarySet['diary_supplement'],
            // ];
            $dateArray[$i]['week'] = $weeks[$week];
            $dateArray[$i]['date'] = $i;
            $dateArray[$i]['date_simple'] = $this->dateToSimpleStyle($i);
            $dateArray[$i]['mobile_date_simple'] = $this->mobileDateToSimpleStyle($i);
            $canMakeUp = $this->canMakeUpBlog($i,$diarySet);
            $dateArray[$i]['diary_supplement'] = $canMakeUp ? 1 : 0;
            // $dateArray[] = $dataItem;
        }
        //判断是否开启[是否展示节假日微博]配置，如果没开启，考虑排班
        if (isset($diarySet['display_holiday_microblog']) && $diarySet['display_holiday_microblog'] == 0) {
            $month =  date('m', strtotime($start));  // 获取月份和年份，用来获取排班信息
            $year = date('Y', strtotime($start));
            $monthEnd = date('m', strtotime($end));
            $yearEnd = date('Y', strtotime($end));
            //判断开始时间和结束时间是否是同一月份或者年份，如果不是同一年份，则需要判断两个
            $resultStart = [];
            $resultEnd = [];
            if ($month != $monthEnd) {
                $resultEnd = $this->getUserWorkDateByMonth($yearEnd, $monthEnd, $userId);
            }
            $resultStart = $this->getUserWorkDateByMonth($year, $month, $userId);
            $result = array_merge($resultStart, $resultEnd);
            // 如果键名相同，则当天是工作日，需要写微博
            foreach ($dateArray as $k => $v) {
                if (isset($result[$k])) {
                    $dateArray[$k]['workDay']= true;
                } else {
                    $dateArray[$k]['workDay'] = false;
                }
            }
        }
        return $dateArray;
    }

    /**
     * 添加日志
     *
     * @param  array  $input 要添加的数据
     *
     * @return int|array  返回结果或错误码
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    public function createDiary($input, $userId)
    {
        $search = [
            'diary_date' => [$input['diary_date']],
            'user_id'  => [$userId],
            "plan_kind" => [1]
        ];
        $diary = app($this->diaryRepository)->getDiaryDetail($search);
        $diaryId = 0;

        if ($diary) {
            $diaryContent = isset($input['main_add']) ? $diary['diary_content'].$input['diary_content'] : $input['diary_content'];
            $data = ['diary_content'  => $diaryContent];
            $where = ['diary_date' => [$input['diary_date']], 'user_id' => [$userId], "plan_kind" => [1]];

            if (app($this->diaryRepository)->updateData($data, $where)) {
                $diaryId = $diary['diary_id'];
            }
        } else {
            $data = [
                'diary_content'  => $input['diary_content'],
                'user_id'        => $userId,
                'diary_date'     => $input['diary_date'],
            ];

            if ($result = app($this->diaryRepository)->insertData($data)) {
               $diaryId = $result->diary_id;
            }
        }

        if ($diaryId > 0) {
            if (isset($input['main_add'])) {
                if (!empty($input['attachments'])) {
                    app($this->attachmentService)->attachmentRelation("diary", $diaryId, $input['attachments'], 'add');
                }

                return $diaryId;
            }

            if (isset($input['attachments'])) {
                app($this->attachmentService)->attachmentRelation("diary", $diaryId, $input['attachments']);
            }

            return $diaryId;
        }

        return ['code' => ['0x000003','common']];
    }

     /**
     * 更新日志
     *
     * @param  int    $diaryId 日志id
     * @param  string $content 日志内容
     *
     * @return int|array       返回结果或错误码
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    public function updateDiary($diaryId, $content)
    {
        $data = ['diary_content'  => $content];
        $where = ['diary_id' => $diaryId];

        if (app($this->diaryRepository)->updateData($data, $where)) {
            return true;
        }
        return ['code' => ['0x000003','common']];
    }

     /**
     * 删除日志(主键)
     *
     * @param  int|array  $diaryId 微博日志id,多个用逗号隔开
     *
     * @return int|array  返回结果或错误码
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    public function deleteDiarys($diaryId,$own=[])
    {
        $diaryIds = array_filter(explode(',', $diaryId));
        if(count($diaryIds) > 0){
            $userId = $own['user_id'] ?? "";
            // 验证
            foreach($diaryIds as $diaryId){
                if($diaryDetail = app($this->diaryRepository)->getDetail($diaryId)){
                    $diaryDetail = $diaryDetail->toArray();
                    // 验证是否是自己的微博
                    if(isset($diaryDetail['user_id']) && $diaryDetail['user_id'] != $userId){
                        // 无权限
                        return ['code' => ['0x000006','common']];
                    }
                    // 验证是否是可删除
                    $diarySet = $this->getPermission();
                    $canEditTime = time() - ($diarySet['show_day']*24 + $diarySet['show_hour'])*3600;
                    if(isset($diaryDetail['created_at']) && strtotime($diaryDetail['created_at']) < $canEditTime){
                        // 无法删除
                        return ['code' => ['0x008008','diary']];
                    }
                }
            }
            if (app($this->diaryRepository)->deleteById($diaryIds)) {
                $where['diary_id'] = [$diaryIds,'in'];
                app($this->diaryReplyRepository)->deleteByWhere($where);
                // 删除日志点赞记录
                app($this->diaryLikePeopleRepository)->deleteByWhere($where);
                return true;
            }
        }
        return ['code' => ['0x000003','common']];
    }

     /**
     * 查询日志详情
     *
     * @param  int  $diaryId 日志id
     *
     * @return array  返回结果或错误码
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    public function getDiaryDetail($diaryId, $params,$own)
    {
        if ($result = app($this->diaryRepository)->getDetail($diaryId)) {
            $data = $result->toArray();
            $attentionUserids = $this->getMyAttention($own['user_id'], 1 ); // 获取关注用户,包括默认和主动关注的
            $canReadUserIds = array_unique(array_merge([$own['user_id']], array_column($attentionUserids[0]['data'], 'user_id'), array_column($attentionUserids[1]['data'], 'user_id')));
            if(isset($data['user_id']) && in_array($data['user_id'],$canReadUserIds)){
                if (isset($params['attachments'])) {
                    $data['attachments'] = app($this->attachmentService)->getAttachmentIdsByEntityId(['entity_table' => 'diary', 'entity_id' => $data['diary_id']]);
                }
                return $data;
            }else{
                return ['code' => ['0x000006','common']];
            }
        }

        return ['code' => ['0x008001','diary']];
    }

    /**
     * 微博报表--导出
     *
     * @param  array  $param 查询条件
     *
     * @return array  查询结果或错误码
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    public function getDiaryReport($param = [], $userId = '')
    {
        if (isset($param['fromExport']) && $param['fromExport'] == 1) {
            $userId = $param['user_info']['user_id'];
        }
        $param = $this->parseParams($param);
        if(isset($param['search']['attention_person'])){
            $param['search']['attention_person'] = $userId;
        }
        $diarySet = $this->getPermission();
        $param['diarySet'] = $diarySet;
        $userArr = $this->getDiaryReportUsers($userId, $param);
        // 获取所有用户下级，返回用户数组
        if (isset($param['getAllUser'])) {
            return $userArr;
        }
        // 日期时间
        $time = isset($param['search']['year_month']) ? $param['search']['year_month'] : date("Y-m");
        $time = date('Y-m', strtotime($time));
        $timeArray = explode('-', $time);
        $year = $timeArray[0];
        $month =$timeArray[1];
        // 报告种类：1日报,2周报，3月报，4半年报，5年报，默认1
        $planKind = isset($param['search']['plan_kind']) ? $param['search']['plan_kind'] : "1";

        $list = [];
        $fromExport = isset($param['fromExport']) ? isset($param['fromExport']) : 0;
        //$fromExport = 1;

        if (isset($param['search']) && isset($param['search']['diary_type']) && !empty($userArr)) {
            // 日报筛选参数，是个数组，三位，index：0|正常 1|补交 2|未提交，没选中，值为0，选中，对应的index的值：1|正常，2|补交，3|未提交
            $type   = array_filter($param['search']['diary_type']);
            $status = array_sum($type);
            $list   = $this->getDiaryInfoByUsersForReport($userArr, $time, $fromExport,$planKind);
            $days   = date('t', strtotime($time."-1"));
            if (count($type) == 0) {
                $list = $userArr = [];
            } else if (count($type) == 1) {
                foreach ($list as $k => $v) {
                    //未提交，清除全部提交的
                    if ($status == 3) {
                        if (count($v['diary']) == $days) {
                            unset($list[$k]);
                        }
                        continue;
                    }else{
                        //非未提交 清空 无微博的数组
                        if(count($v['diary']) == 0){
                            unset($list[$k]);
                            continue;
                        }
                    }
                    //正常，有一天正常写微博就算正常
                    if ($status == 1) {
                        $punctuality = FALSE;
                        foreach ($v['diary'] as $key => $val) {
                            //状态为1 或者 导出且提交日期在同一天
                            if ($val == 1 || ($fromExport == 1 && strpos($val, $key) !== false) ) {
                                $punctuality = TRUE;
                                break;
                            }
                        }
                        //删除没有一天是正常的。
                        if (!$punctuality) {
                            unset($list[$k]);
                        }
                    }
                    //补交
                    if ($status == 2) {
                        $later = FALSE;
                        //补交 有一天补交写微博就算补交
                        foreach ($v['diary'] as $key => $val) {
                            //状态为2 或者 导出且提交日期没在同一天
                            if ($val == 2 || ($fromExport == 1 && strpos($val, $key)  === false) ) {
                                $later = TRUE;
                                break;
                            }
                        }
                        //删除没有一天补交的
                        if (!$later) {
                            unset($list[$k]);
                        }
                    }
                }
                $userArr = array_column($list, 'user_id');
            } else if (count($type) == 2) {
                foreach ($list as $k => $v) {
                    // 正常 或 补交
                    if ($status == 3) {
                        if (count($v['diary']) == 0) {
                            unset($list[$k]);
                        }
                        continue;
                    } else if ($status == 4) {
                        // 正常 或 未提交
                        // 有 并满天，存在假期问题，后面处理除掉假期 所有正常提交
                        if(count($v['diary']) ==  $days){
                            $punctuality = FALSE;
                            foreach ($v['diary'] as $key => $val) {
                                //全部是补交的，删掉，即有一个是补交的，就不删掉
                                if ($val != 2 || ($fromExport == 1 && strpos($val, $key)  !== false) ) {
                                    $punctuality = TRUE;
                                    break;
                                }
                            }
                            // 删除全部是补交的
                            if (!$punctuality) {
                                unset($list[$k]);
                            }
                        }
                        continue;
                    }
                    // 补交或未提交
                    if ($status == 5) {
                        // 删除全部是正常的
                        if($planKind == "1" || $planKind == 1 ){
                            //日报
                            if(count($v['diary'])  == $days){
                                $later = FALSE;
                                foreach ($v['diary'] as $key => $val) {
                                    //除非全部是正常才删,即有一个不正常就不删
                                    if ($val != 1 || ($fromExport == 1 && strpos($val, $key)  === false) ) {
                                        $later = TRUE;
                                        break;
                                    }
                                }
                                if (!$later) {
                                    unset($list[$k]);
                                }
                            }
                        }else{
                            //周报
                            if($planKind == "2"||$planKind == 2){
                                //获取要查询月有多少天
                                $searchMonthDays = date('t',strtotime($year.'-'.$month.'-'.'01'));
                                //获取1号是哪一周
                                $startWeek = date('W',strtotime($year.'-'.$month.'-'.'01'));
                                $endWeek = date('W',strtotime($year.'-'.$month.'-'.$searchMonthDays));
                                $searchMonthWeeks = $endWeek - $startWeek + 1;
                                // 删除全部提交的
                                if (count($v['diary']) == $searchMonthWeeks) {
                                    unset($list[$k]);
                                }
                            }else{
                                //月报
                                //删除全部提交的,16 = 12个月+4季度
                                if (count($v['diary']) >= 16) {
                                    unset($list[$k]);
                                }
                            }
                            continue;
                        }
                    }
                }
                $userArr = array_column($list, 'user_id');
            }
        }

        $total = count($userArr);
        $limit = isset($param['limit']) ? $param['limit'] : config('eoffice.pagesize');

        if (isset($param['page']) && $param['page'] > 0) {
            $offSet = (isset($param['page']) ? $param['page'] - 1 : 0) * $limit;
            $users = array_slice($userArr, $offSet, $limit);
        } else {
            $users = $userArr;
        }
        // 不知道这儿要干什么
        if (isset($param['search']) && isset($param['search']['diary_type'])) {
            foreach ($list as $k => $v) {
                if (!in_array($v['user_id'], $users)) {
                    unset($list[$k]);
                }
            }
            $list = array_values($list);
        } else {
            if (!empty($users)) {
                $list =  $this->getDiaryInfoByUsersForReport($users, $time, $fromExport,$planKind);
            }
        }
        // 如果设置了根据排班显示微博，则需要根据考勤模块展示数据
        if (isset($diarySet['display_holiday_microblog']) && $diarySet['display_holiday_microblog'] == 0 && ($planKind == "1"||$planKind == 1)) {
            //修改为每个人 根据设置的 排版 处理结果
            foreach($list as $listKey => &$listValue){
                $currentUserId = $listValue['user_id'];
                //获取休假的日期
                $allRestDays = $this->getUserRestDateByMonth($year, $month, $currentUserId);
                //判断是否是假期，如果是，添加状态1，表示报表不需要填写，不需要展示为红色状态
                foreach ($allRestDays as $key => $value) {
                    if (isset($listValue['diary'])) {
                        if (!isset($listValue['diary'][$value])) {
                            $listValue['diary'][$value] = trans("diary.holiday");
                        }
                    }
                }
            }
        }
        // 为了导出
        if (isset($param['fromExport']) && $param['fromExport'] == 1) {
            $exportParam = [
                "time"     => $time,
                // 查询结果数据
                "list"     => $list,
                "planKind" => $planKind,
            ];
            if($planKind == "1") {
                return $this->diaryPlanInfoExportForDays($exportParam);
            } else {
                return $this->diaryPlanInfoExportForWeeks($exportParam);
            }
        } else {
            return compact('total', 'list');
        }
    }

    /**
     * 获取用户所有假期
     * @param $year
     * @param $month
     * @param $currentUserId
     * @return array
     */
    function getUserRestDateByMonth($year, $month, $currentUserId){
        //获取排班的日期
        $workList = app($this->attendanceService)->getUserSchedulingDateByMonth($year, $month, $currentUserId);
        // 获取月的所有天数
        $time = date('Y-m', strtotime($year.'-'.$month));
        $allMonthDays = $this->getMonthDays($time);

        // 根据排班日期和当月所有天数，去反，获得休假日期
        foreach ($allMonthDays as $k => $day) {
            foreach($workList as $workListKey => $workListValue){
                if(isset($workListValue['scheduling_date']) && $workListValue['scheduling_date'] == $day){
                    unset($allMonthDays[$k]);
                }
            }
        }
        return $allMonthDays;
    }

    /**
     * 获取排班的日期
     * @param $year
     * @param $month
     * @param $currentUserId
     * @return array|null
     */
    function getUserWorkDateByMonth($year, $month, $currentUserId){
        $workList = app($this->attendanceService)->getUserSchedulingDateByMonth($year, $month, $currentUserId);
        $allWorkDay = [];
        // 获取所有的排班日期
        foreach($workList as $workListKey => $workListValue){
            if(isset($workListValue['scheduling_date'])){
                $allWorkDay[] = $workListValue['scheduling_date'];
            }
        }
        return array_flip($allWorkDay);
    }

    function getMonthDays($month = "this month", $format = "Y-m-d")
    {
        $j = date("t", strtotime($month)); //获取当前月份天数
        $start_time = strtotime(date('Y-m-01', strtotime($month)));  //获取本月第一天时间戳
        $array = array();
        for($i=0;$i<$j;$i++){
             $array[] = date($format,$start_time+$i*86400); //每隔一天赋值给数组
        }
        return $array;
    }

    /**
     * 解析&拼接导出数据，(日报数据)
     * @param  [type] $param [description]
     * @return [type]        [description]
     */
    function diaryPlanInfoExportForDays($param) {
        $time = $param["time"];
        $list = $param["list"];
        $header = [
            // "dept_name" => "部门",
            // "user_name" => "人员",
            "dept_name" => ['data' => trans("diary.department"), 'style' => ['width' => '15']],
            "user_name" => ['data' => trans("diary.personnel"), 'style' => ['width' => '15']],
        ];
        $days = date("t", strtotime($time));
        for($i=1; $i <= $days; $i++){
            $date = str_pad($i, 2, "0", STR_PAD_LEFT);
            $ymd = $time.'-'.$date;
            $week = date('w', strtotime($ymd));
            $header[$ymd] = $date."(".$this->getWeeksTrans()[$week].")";
        }
        $data = [];
        foreach ($list as $k => $v) {
            $data[$k]['dept_name'] = isset($v['user_to_dept']) && isset($v['user_to_dept'][0]['dept_name']) ? $v['user_to_dept'][0]['dept_name'] : '';
            $data[$k]['user_name'] = $v['user_name'];
             for($i=1; $i <= $days; $i++){
                $date = str_pad($i, 2, "0", STR_PAD_LEFT);
                $ymd = $time.'-'.$date;
                if (isset($v['diary'][$ymd])) {
                    if (strpos($v['diary'][$ymd], $ymd) === false) {
                        $data[$k][$ymd] = [
                            'data' => mb_substr($v['diary'][$ymd], 0, 10),
                            'style' => [
                                'backgroundColor' => 'F6BF27',
                                'width' => '12'
                            ]
                        ];
                    } else {
                        $data[$k][$ymd] = [
                            'data' => $ymd,
                            'style' => [
                                'width' => 'autosize'
                            ]
                        ];
                    }
                } else {
                    $data[$k][$ymd] = '';
                }
            }
        }
        $sheetName = str_replace('-', '', $time).trans("diary.monthly_micro-blog_report");
        return compact('sheetName', 'header', 'data');
    }

    private function getMonthTrans(int $month)
    {
        $monthArr = [
            trans("diary.january"),
            trans("diary.february"),
            trans("diary.march"),
            trans("diary.april"),
            trans("diary.may"),
            trans("diary.june"),
            trans("diary.july"),
            trans("diary.august"),
            trans("diary.september"),
            trans("diary.october"),
            trans("diary.november"),
            trans("diary.december")
        ];
        return isset($monthArr[$month -1]) ? $monthArr[$month -1] : '';
    }

    /**
     * 解析&拼接导出数据，(周报、月报数据)
     * @param  [type] $param [description]
     * @return [type]        [description]
     */
    function diaryPlanInfoExportForWeeks($param) {
        $time = $param["time"];
        $list = $param["list"];
        // 报告种类
        $planKind    = $param["planKind"];
        $timeExplode = explode('-', $time);
        $year        = $timeExplode[0];
        $month       = $timeExplode[1];
        // $days        = date("t", strtotime($time));

        // echo "<pre>";
        // print_r($param);
        // exit();
        // 处理头部信息
        $header = [
            "dept_name" => ['data' => trans("diary.department"), 'style' => ['width' => '15']],
            "user_name" => ['data' => trans("diary.personnel"), 'style' => ['width' => '15']],
        ];

        if($planKind == "2") {
            // 某月第一天和最后一天
            $monthFirstDay     = date('Y-m-01', strtotime(date($year."-".$month)));
            $monthLastDay      = date('Y-m-d', strtotime("$monthFirstDay +1 month -1 day"));
            $monthFirstDayWeek = date('W', strtotime($monthFirstDay));
            $monthLastDayWeek  = date('W', strtotime($monthLastDay));

            // 12月份的周数不显示问题
            // 如果12月份的最后一周为下一年第一周，则获取上个月最后一周的星期一， 再用12-31号减去这一天，得到的天数算出几周，在加上去
            if ( $month == 12 && $monthLastDayWeek == 1) {
                //12月1日是星期几
                $weekDay = date('w',strtotime($year.'-12-1'));
                //为0是 就是 星期日
                $weekDay = $weekDay?$weekDay:7;
                $firstDayTime = strtotime($year.'-12-01');
                //上月最后一周
                $firstDay = $firstDayTime - ($weekDay-1)*3600*24;
                $lastDay =  strtotime($year.'-12-31');

                $monthWeeks = intval(($lastDay - $firstDay) / (1 * 3600 * 24 * 7));
                $monthLastDayWeek = intval($monthFirstDayWeek + $monthWeeks - 1);
            }
            $monthFirstDayWeek = $monthFirstDayWeek >= 51 ? 1 : $monthFirstDayWeek;

            for ($week = $monthFirstDayWeek; $week < $monthLastDayWeek+1; $week++) {
                // 某周的开始/结束日期
                $weeksInfo = $this->weeks($year,$week);
                if(count($weeksInfo) == 2) {
                    // $header[$weeksInfo["start"]."-".$weeksInfo["end"]] = "第".$week."周(".$weeksInfo["start"]."-".$weeksInfo["end"].")";
                    $header[$weeksInfo["start"]."-".$weeksInfo["end"]] = ['data' => trans("diary.week") . $week."(".$weeksInfo["start"]."-".$weeksInfo["end"].")", 'style' => ['width' => 'autosize']];
                }
            }
        } else {
            for ($month = 1; $month < 13; $month++) {
                // 某一月的开始/结束日期
                $monthDaysStart = date('Y-m-01', strtotime(date($year."-".$month)));
                $monthDaysEnd = date('Y-m-d', strtotime("$monthDaysStart +1 month -1 day"));
                $header[$monthDaysStart."-".$monthDaysEnd] = $this->getMonthTrans($month);
                // var monthDaysStart = moment(month+" "+self.year,"MM YYYY").startOf('month').format('YYYY-MM-DD');
                // var monthDaysEnd = moment(month+" "+self.year,"MM YYYY").endOf('month').format('YYYY-MM-DD');
                // monthInfo[monthDaysStart+"-"+monthDaysEnd] = moment(month+" "+self.year,"MM YYYY").format('MM')+"月";
                if($month == 6) {
                    $header[$year."-01-01"."-".$year."-06-30"] = trans("diary.half_a_year");
                } else if($month == 12) {
                    $header[$year."-01-01"."-".$year."-12-31"] = $year;
                } else if($month == 3) {
                    $header[$year."-01-01"."-".$year."-03-31"] = trans("diary.first_quarter");
                } else if($month == 9) {
                    $header[$year."-07-01"."-".$year."-09-30"] = trans("diary.third_quarter");
                }
            }
        }
        // 处理列表主体
        $data = [];
        foreach ($list as $k => $v) {
            $data[$k]['dept_name'] = isset($v['user_to_dept']) ? $v['user_to_dept'][0]['dept_name'] : '';
            $data[$k]['user_name'] = $v['user_name'];
            foreach ($header as $key => $value) {
                if($key != "dept_name" && $key != "user_name") {
                    if(isset($v['diary'][$key])) {
                        if (strpos($v['diary'][$key], $key) === false) {
                            $data[$k][$key] = [
                                'data' => substr($v['diary'][$key], 0, 10),
                                'style' => [
                                    // 'backgroundColor' => 'F6BF27',
                                    'width' => 'autosize'
                                ]
                            ];
                        } else {
                            $data[$k][$key] = [
                                'data' => $key,
                                'style' => [
                                    'width' => 'autosize'
                                ]
                            ];
                        }
                    } else {
                        $data[$k][$key] = '';
                    }
                }
            }
        }
        // 报告名称
        if($planKind == "2") {
            $sheetName = str_replace('-', '', $time) . trans("diary.weekly_report");
        } else {
            $sheetName = $year . trans("diary.monthly_report");
        }
        // echo "<pre>";
        // print_r(compact('sheetName', 'header', 'data'));
        // exit();
        return compact('sheetName', 'header', 'data');
    }

    /**
     * php函数，获取某个自然周的开始和结束时间
     * @param  [type] $year [description]
     * @param  [type] $week [description]
     * @return [type]       [description]
     */
    function weeks($year, $week){
        $year = intval($year);
        $week = intval($week);
        if ($week < 10)
        {
            $week = '0' . $week; // 注意：一定要转为 2位数，否则计算出错
        }
        $weekday = [];
        $weekday['start'] = strtotime($year . 'W' . $week);
        $weekday['end'] = strtotime('+1 week -1 day', $weekday['start']);
        /* 该段代码计算 2019年的周截至日期相差 1周
        $year_start = mktime(0,0,0,1,1,$year);
        $year_end = mktime(0,0,0,12,31,$year);
        $weekday = [];
        if (intval(date('w',$year_start)) == 1){
            $start = $year_start;
        }else{
            $start = strtotime('+1 monday',$year_start);
        }

        if ($week == 1){
            $weekday['start'] = $start;
        }else{
            $weekday['start'] = strtotime('+'.($week-0).' monday',$start);
        }

        $weekday['end'] = strtotime('+1 sunday',$weekday['start']);
        if (date('Y',$weekday['end']) != $year){
            $weekday['end'] = $year_end;
        }
        */
        return array_map(function($s){
            return date('Y-m-d',$s);
        },$weekday);
    }

    /**
     * 日志报表人员
     *
     * @param  array  $userInfo 用户信息
     *
     * @return array  查询结果或错误码
     *
     * @author qishaobo
     *
     * @since  2016-11-24
     */
    public function getDiaryInfoByUsersForReport($users, $time, $fromExport,$planKind)
    {
        $diaryDateScope = [];
        if($planKind == "1") {
            $diaryDateScope = [$time.'-01', $time.'-31'];
        }
        $timeArray = explode('-',$time);
        $year = isset($timeArray[0]) ? $timeArray[0] : date('Y');
        $month = isset($timeArray[1]) ? $timeArray[1] : date('m');
        $param = [
            "users" => $users,
            "diary_date_scope" => $diaryDateScope,
            "plan_kind" => $planKind,
        ];
        // $list = app($this->userRepository)->getUserDiaryReport($users, [$time.'-01', $time.'-31']);
        $list = app($this->userRepository)->getUserDiaryReport($param);
        foreach ($list as $k => $v) {
            if (!empty($v['diary'])) {
                // 日报类型，格式化数据
                if($planKind == "1") {
                    foreach ($v['diary'] as $key => $val) {
                        unset($v['diary'][$key]);
                        if ($fromExport == 1) {
                            $v['diary'][$val['diary_date']] = (string)$val['created_at'];
                        } else {
                            // 判断按时提交还是补交
                            $v['diary'][$val['diary_date']] = strpos($val['created_at'], $val['diary_date']) === false ? 2 : 1;
                        }
                    }
                } elseif($planKind == "2") {
                    foreach ($v['diary'] as $key => $val) {
                        unset($v['diary'][$key]);
                        $planStart = explode('-',$val['plan_scope_date_start']);
                        $planEnd = explode('-',$val['plan_scope_date_end']);
                        $planStart = $planStart[0]."-".$planStart[1];
                        $planEnd = $planEnd[0]."-".$planEnd[1];
                        // 筛选 开始或者结束都在当周
                        if ($fromExport == 1) {
                            if ($planStart == $time || $planEnd == $time) {
                                $v['diary'][$val['plan_scope_date_start'] . "-" . $val['plan_scope_date_end']] = (string)$val['created_at'];
                            }
                        } else {
                            if ($planStart == $time || $planEnd == $time) {
                                $v['diary'][$val['plan_scope_date_start']."-".$val['plan_scope_date_end']] = $val['created_at'] ? "1" : "";
                            }
                        }
                    }
                } else {
                    foreach ($v['diary'] as $key => $val) {
                        unset($v['diary'][$key]);
                        $diaryYear = strstr($val['plan_scope_date_start'],'-',true);
                        //筛选出仅今年的微博
                        if ($fromExport == 1) {
                            if ($year == $diaryYear) {
                                $v['diary'][$val['plan_scope_date_start'] . "-" . $val['plan_scope_date_end']] = (string)$val['created_at'];
                            }
                        } else {
                            // 此处没有判断按时提交还是补交
                            // 判断是否是筛选的年份
                            if ($year == $diaryYear ) {
                                $v['diary'][$val['plan_scope_date_start']."-".$val['plan_scope_date_end']] = $val['created_at'] ? "1" : "";
                            }
                        }
                    }
                }
            }
        }
        return $list->toArray();
    }

    /**
     * 日志报表人员
     *
     * @param  array  $userInfo 用户信息
     *
     * @return array  查询结果或错误码
     *
     * @author qishaobo
     *
     * @since  2016-11-24
     */
    public function getDiaryReportUsers($userId, $param = [])
    {
        $diarySet = $this->getPermission();
        $search = $defaultAttention = $my = [];
        if (isset($param['search']) && isset($param['search']['user_id'])) {
            return $param['search']['user_id'];
        }

        if (isset($param['search']) && isset($param['search']['dept_id'])) {
            $userIds = $this->getDiaryReportUsers($userId);
            $param = [
                'search' => [
                    'user_id' => [$userIds, 'in'],
                    'dept_id' => [$param['search']['dept_id']]
                ],
                'page' => 0
            ];
            if(isset($diarySet['dimission']) && $diarySet['dimission']){
                $param['include_leave'] = 1;
            }
            $users = app($this->userRepository)->getUserList($param);

            if (empty($users)) {
                return [];
            }

            return array_column($users, 'user_id');
        }

        if (isset($param['search']) && isset($param['search']['user_name'])) {
            $search['user_name'] = $param['search']['user_name'];

            if (isset($param['search']['attention_person'])) {
                $ownSearch = [
                    'search' => [
                        'user_name' => $param['search']['user_name'],
                        'user_id' => [$param['search']['attention_person']]
                    ]
                ];

                $usersOwn = app($this->userRepository)->getAllUsers($ownSearch);

                if ($usersOwn->toArray()) {
                    $my = [$userId];
                }
            }


        } else {
            $my = [$userId];
        }
        // 获取微博系统配置信息
        //$diarySet = app($this->systemSecurityService)->getSecurityOption('log');
       
        $paramAttention = ['attention_person' => [$userId]];
        $paramAttention['diarySet'] = $diarySet;

        // 排除申请状态,保留关注
        // if (isset($param['search']) && isset($param['search']['attention_status'])) {
            // $paramAttention['attention_status'] = $param['search']['attention_status'];
        // }
        $paramAttention['attention_status'] = '2';
        $attentionToPerson = app($this->diaryAttentionRepository)->diaryAttentionToPerson($paramAttention, $search);
        $search['search'] = json_encode(['superior_user_id' => [$userId]]);
        $search['diarySet'] = $diarySet;

        // 获取默认关注,查询所有下级 以及有权限的用户
        // if (!Cache::has('default_attention_list_'.$userId)) {
        //     $defaultAttentionList = app($this->userService)->getAllUserSuperior($userId,$param); // 递归获取下级所有用户，放入数组中
        //     Cache::add('default_attention_list_'.$userId,$defaultAttentionList,60);
        // } else {
        //     $defaultAttentionList = Cache::get('default_attention_list_'.$userId);
        // }
        // $defaultAttentionUserIds = [];
        // if ($defaultAttentionList) {
        //     $defaultAttentionList = $defaultAttentionList['list'];
        //     foreach ($defaultAttentionList as $k => $v) {
        //         $defaultAttentionUserIds[] = $v['user_id'];
        //     }
        // }
        // $defaultAttentionList = $defaultAttentionUserIds;
        $own['user_id'] = $userId;
        $defaultAttentionList = $this->getDefaultAttention($own);
        $defaultAttentionList = array_column($defaultAttentionList,'user_id');
        // 获取模糊查询的用户名
        if (isset($param['search']['user_name']) && !empty($param['search']['user_name']) && isset($param['search']['user_name'][0]) && !empty($param['search']['user_name'][0])) {
            $userName = $param['search']['user_name'][0];
            $searchUserId = app($this->userRepository)->getUserAllUserId($userName);
            // 判断搜索的id是否属于关注用户，不是，则返回空数组
            $searchDefaultUserId = array_intersect($defaultAttentionList,$searchUserId);
            $searchAttentionToUserId = array_intersect($attentionToPerson,$searchUserId);
            return array_unique(array_merge($searchDefaultUserId, $searchAttentionToUserId, $my));
        }
        return array_unique(array_merge($attentionToPerson, $defaultAttentionList, $my));
    }

    /**
     * 查询日志回复
     *
     * @param  int  $diaryId 日志id
     * @param  array  $param 查询条件
     *
     * @return array  返回结果或错误码
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    public function getDiaryReplysList($diaryId, $param = [],$own)
    {
        $param = $this->parseParams($param);
        $param['user_id'] = $own['user_id'] ?? "";
        $param['search']['diary_id'] = [$diaryId];
        $data = $this->response(app($this->diaryReplyRepository), 'getTotal', 'diaryReplysList', $param);

        if (!empty($data['list'])) {
            foreach ($data['list'] as $key => $val) {
                $data['list'][$key]['attachments'] = app($this->attachmentService)->getAttachmentIdsByEntityId(['entity_table'=>'diary_reply', 'entity_id' => $val['diary_reply_id']]);
            }
        }

        return $data;
    }

    /**
     * 添加日志回复
     *
     * @param  int  $diaryId 日志id
     * @param  string  $diaryReplyContent 回复id
     *
     * @return int|array    返回结果或错误码
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    public function createDiaryReply($diaryId, $input, $userInfo)
    {
        $data = [
            'diary_id'            => $diaryId,
            'user_id'             => $userInfo['user_id'],
            'diary_reply_content' => $input['diary_reply_content'],
        ];
        if($diary = app($this->diaryRepository)->getDetail($diaryId)){
            $diary = $diary->toArray();
            // 没有微博主页,只能回复自己的日报
            if(isset($userInfo['menus']) && isset($userInfo['menus']['menu']) && !in_array('28', $userInfo['menus']['menu']) ){
                // 不能非日报
                if(isset($diary['plan_kind']) && $diary['plan_kind'] != 1) {
                    // 无权限
                    return ['code' => ['0x000006','common']];
                }
                if(isset($diary['user_id']) && $diary['user_id'] != $userInfo['user_id'] ){
                    // 不是自己，无权限
                    return ['code' => ['0x000006','common']];
                }
            }
            // 回复其他人时，该人必须是关注的人
            if(isset($diary['user_id']) && $diary['user_id'] != $userInfo['user_id']){
                // 不是自己的情况下，必须是关注的人，否则无权限回复
                // 验证是否在我的关注中
                $inAttention = $this->userInAttention($diary['user_id'],$userInfo);
                if(!$inAttention) {
                    // 不是关注人，无权限回复
                    return ['code' => ['0x000006','common']];
                }
            }
            if ($result = app($this->diaryReplyRepository)->insertData($data)) {
                $diaryReplyId = $result->diary_reply_id;
                if (isset($input['attachments'])) {
                    app($this->attachmentService)->attachmentRelation("diary_reply", $diaryReplyId, $input['attachments']);
                }

                $diary = app($this->diaryRepository)->getDetail($diaryId)->toArray();
                $sendData['remindMark']     = 'diary-reply';
                $sendData['toUser']         = $diary['user_id'];
                // $sendData['stateParams']    = [];
                $sendData['stateParams']    = ['userId' => $diary['user_id']];

                $sendData['contentParam']   = [
                    'replyUser' => $userInfo['user_name'],
                    'diaryDate' => $diary['diary_date'],
                ];

                Eoffice::sendMessage($sendData);

                return $result->diary_reply_id;
            }

        }

        return ['code' => ['0x000003', 'common']];
    }

    /**
     * 删除日志回复(主键)
     *
     * @param  int|array  $ids 要添加的数据
     *
     * @return int|array  返回结果或错误码
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    public function deleteReplys($diaryId,$replyId,$own)
    {
        // 验证是否是自己的微博或是自己的回复
        $diaryDetail = app($this->diaryRepository)->getDetail($diaryId);
        $replyDetail = app($this->diaryReplyRepository)->getDetail($replyId);
        if( $diaryDetail && $replyDetail ){
            $diaryDetail = $diaryDetail->toArray();
            $replyDetail = $replyDetail->toArray();
            $userId = $own['user_id'] ?? "";
            // 既不是自己的微博 也不是自己的回复
            if(isset($diaryDetail['user_id']) && isset($diaryDetail['user_id']) && $diaryDetail['user_id'] != $userId && $replyDetail['user_id'] != $userId){
                return ['code' => ['0x000006','common']];
            }
            if (app($this->diaryReplyRepository)->deleteById($replyId)) {
                return true;
            }
        }
        return ['code' => ['0x000003', 'common']];
    }

    /**
     * 判断用户是否在关注列表中
     * @param $userId 判断人id
     * @param $own own
     * @return bool
     */
    private function userInAttention($userId,$own){
        // 不是自己的情况下，必须是关注的人，否则无权限回复
        $attentionList = $this->getMyAttention($own['user_id'], []);
        $inAttention = false;
        // 验证是否在我的关注中
        if(isset($attentionList[0]) && isset($attentionList[0]['data']) && count($attentionList[0]['data']) > 0){
            foreach($attentionList[0]['data'] as $attentionValue){
                if(isset($attentionValue['user_id']) && $attentionValue['user_id'] == $userId){
                    $inAttention = true;
                    break;
                }
            }
        }
        if(!$inAttention){
            // 验证是否在下属中
            if(isset($attentionList[1]) && isset($attentionList[1]['data']) && count($attentionList[1]['data']) > 0){
                foreach($attentionList[1]['data'] as $attentionValue){
                    if(isset($attentionValue['user_id']) && $attentionValue['user_id'] == $userId){
                        $inAttention = true;
                        break;
                    }
                }
            }
        }
        return $inAttention;
    }

    /**
     * 获取我的关注
     *
     * @param  string  $userId 用户id
     *
     * @return array  返回结果或错误码
     *
     * @author longmiao
     *
     * @since  2017-10-23
     */
    public function getMyAttention($userId, $params ,$attentionPage = '')
    {
        $params = $this->parseParams($params);
        $getList = isset($params['getList']) ? $params['getList'] : 1;
        $diarySet = $this->getPermission();
        $param = [
            'search' => [
                'attention_person' => [$userId],
                'attention_status' => [2]
            ],
            'diarySet' => $diarySet
        ];

        $userParams = [
            'search' => [
                'superior_user_id' => [$userId],
            ],
            'diarySet' => $diarySet
        ];

        if (isset($params['search'])) {
            $search = $params['search'];
            $param['search'] = array_merge($param['search'], $search);
            $userParams['search'] = array_merge($userParams['search'], $search);
        }

        $param['withDept'] = 1;

        // 获取我的关注
        $myAttentions = app($this->diaryAttentionRepository)->diaryAttentionList($param);
        $myAttention = [];
        if (!empty($myAttentions)) {
            foreach ($myAttentions as $v) {
                $myAttention[$v['attention_to_person']] = [
                    'user_id' => $v['attention_to_person'],
                    'user_name' => $v['user_attention_to_person'] ? $v['user_attention_to_person']['user_name'] : '',
                    'dept_name'  => !empty($v['user_attention_to_person']['user_to_dept']) ? $v['user_attention_to_person']['user_to_dept'][0]['dept_name'] : '',
                    'from' => '2',
                    'attention_id' => $v['attention_id'],
                    'status'  => !empty($v['user_attention_to_person']['user_has_one_system_info']) ? $v['user_attention_to_person']['user_has_one_system_info']['user_status'] : '',
                ];
            }
        }
        // 获取我的默认关注
        $userParams['withUserDept'] = 1;
        $userParams['search'] = json_encode($userParams['search']);
        // if (!Cache::has('default_attention_list_'.$userId)) {
        //     $defaultAttentionList = app($this->userService)->getAllUserSuperior($userId,$userParams); // 递归获取下级所有用户，放入数组中
        // } else {
        //     $defaultAttentionList = Cache::get('default_attention_list_'.$userId);
        // }
        $own['user_id'] = $userId;
        $defaultAttentionList = $this->getDefaultAttention($own,$userParams);
        $defaultAttention = [];
        if (!empty($defaultAttentionList)) {
            foreach ($defaultAttentionList as $v) {
                $userName = !empty($v['user_name'])? $v['user_name'] : '';
                if(isset($params['search']) && isset($params['search']['user_name']) ){
                    $searchUserName = is_array($params['search']['user_name']) && isset($params['search']['user_name'][0]) ?  $params['search']['user_name'][0] : '';
                    $pattern = '/'.$searchUserName .'/';
                    if(!preg_match($pattern, $userName)){
                        // 没有匹配上 继续
                        continue;
                    }
                }
                $defaultAttention[$v['user_id']] = [
                    'user_id'    => $v['user_id'],
                    'user_name'  => $userName,
                    'dept_name'  => !empty($v['dept_name']) ? $v['dept_name'] : '',
                    'from' => '1',
                    'status'  => !empty($v['user_status']) ? $v['user_status'] : '',
                ];
            }
        }
        // 添加访问记录
        // if (isset($params['addVisitRecord'])) {
        //     $attentions = array_values(array_merge($myAttention, $defaultAttention));
        //     foreach ($attentions as $k => $v) {
        //             $where = [
        //             "visit_person"      => [$userId],
        //             "visit_to_person"   => [$v['user_id']],
        //             ];
        //             $hasVisit = app($this->diaryVisitRecordRepository)->diaryHasVisit($where);
        //             if ($hasVisit) {
        //                 app($this->diaryVisitRecordRepository)->diaryVisitIncrement($where);
        //             } else {
        //                 $data = [
        //                     "visit_person"      => $userId,
        //                     "visit_to_person"   => $v['user_id'],
        //                     "visit_num"         => 1,
        //                 ];
        //                 app($this->diaryVisitRecordRepository)->insertData($data);
        //             }
        //     }
        // }
        if (isset($params['mobile'])) {
            if ($params['mobile'] == 'mine') {
                return array_values($myAttention);
            } else {
                return array_values($defaultAttention);
            }
        }

        if ($getList == 1) {
            return $data = [
                [
                    'title' => trans("diary.my_attention"),
                    'data' => array_values($myAttention)
                ],
                [
                    'title' => trans("diary.default_attention"),
                    'data' =>  array_values($defaultAttention)
                ]
            ];
        }

        return array_merge($myAttention, $defaultAttention);
    }

    /**
     * 获取访问记录
     *
     * @param  string  $userId 用户id
     *
     * @return array  返回结果或错误码
     *
     * @author qishaobo
     *
     * @since  2015-12-21
     */
    public function getVisitRecord($userId,$search = [])
    {
        //我关注的
        //$diarySet = app($this->systemSecurityService)->getSecurityOption('log');
        $diarySet = $this->getPermission();
        $myAttentionsUser = [];
        $myapplyUser = [];
        $param = [
            'search' => [
                'attention_person' => [$userId],
            ],
            'diarySet' => $diarySet
        ];
        $myAttentions = app($this->diaryAttentionRepository)->diaryAttentionList($param);
        if (!empty($myAttentions)) {
            foreach ($myAttentions as $v) {
                if($v['attention_status'] == 2){
                    $myAttentionsUser[] = $v['attention_to_person'];
                }else if($v['attention_status'] == 1){
                    $myapplyUser[] = $v['attention_to_person'];
                }
            }
        }
        //访问我的
        $param = [
            'fields'    => ['visit_to_person', 'visit_num', 'updated_at'],
            'search' => [
                'visit_person' => [$userId],
            ],
            'diarySet' => $diarySet
        ];
        $visitPerson= app($this->diaryVisitRecordRepository)->diaryVisitList($param);
        $visitPersonNew = [];
        if (!empty($visitPerson)) {
            foreach ($visitPerson as $k => $v) {
                $userVistToPersion = $v['user_visit_to_person'];
                $dimission = false;
                if(isset($userVistToPersion['user_accounts']) && $userVistToPersion['user_accounts'] == "") {
                    $dimission = true;
                }
                if (isset($search['user_name']) && !strstr($v['user_visit_to_person']['user_name'], $search['user_name'])) {
                    continue;
                }
                $visitPersonNew[] = [
                    'id'        => $v['user_visit_to_person']['user_id']."_visitPerson",
                    'user_id'   => $v['user_visit_to_person']['user_id'],
                    'user_name' => $v['user_visit_to_person']['user_name'],
                    'dimission' => $dimission,
                    'visit_num' => $v['visit_num'],
                    'updated_at'=> $v['updated_at'],
                    'attention' => in_array($v['user_visit_to_person']['user_id'], $myAttentionsUser) ? 1 : 0,
                    'apply' => in_array($v['user_visit_to_person']['user_id'], $myapplyUser) ? 1 : 0,
                ];
            }
        }

        //我访问的
        $param = [
            'fields'    => ['visit_person', 'visit_num', 'updated_at'],
            'search' => [
                'visit_to_person' => [$userId],
            ],
            'diarySet' => $diarySet
        ];
        $visitToPerson = app($this->diaryVisitRecordRepository)->diaryVisitList($param);
        $visitToPersonNew = [];
        if (!empty($visitToPerson)) {
            foreach ($visitToPerson as $k => $v) {
                $userVistPersion = $v['user_visit_person'];
                $dimission = false;
                if(isset($userVistPersion['user_accounts']) && $userVistPersion['user_accounts'] == "") {
                    $dimission = true;
                }
                if (isset($search['user_name']) && !strstr($v['user_visit_person']['user_name'], $search['user_name'])) {
                    continue;
                }
                $visitToPersonNew[] = [
                    'id'        => $v['user_visit_person']['user_id'].'_user_visit_person',
                    'user_id'   => $v['user_visit_person']['user_id'],
                    'user_name' => $v['user_visit_person']['user_name'],
                    'dimission' => $dimission,
                    'visit_num' => $v['visit_num'],
                    'updated_at'=> $v['updated_at'],
                    'attention' => in_array($v['user_visit_person']['user_id'], $myAttentionsUser) ? 1 : 0,
                    'apply' => in_array($v['user_visit_person']['user_id'], $myapplyUser) ? 1 : 0,
                ];
            }
        }

        return $data = [
            [
                'title' => trans("diary.recent_visit"),
                'data' => $visitPersonNew
            ],
            [
                'title' => trans('diary.recently_paid_visit_to'),
                'data' =>  $visitToPersonNew
            ]
        ];
    }

    /**
     * 添加微博便签
     *
     * @return bool
     *
     * @author qishaobo
     *
     * @since  2016-04-15
     */
    public function createDiaryMemo($data)
    {
        return app($this->diaryMemoRepository)->insertData($data);
    }

    /**
     * 获取微博便签详情
     *
     * @param  string $userId 创建人id
     *
     * @return array 返回结果或错误码
     *
     * @author qishaobo
     *
     * @since  2016-04-15
     */
    public function getDiaryMemo($userId)
    {
        $where = ['memo_creator' => $userId];
        $resultObj = app($this->diaryMemoRepository)->getDiaryMemoDetail($where);

        if ($result = $resultObj->toArray()) {
            return $result[0];
        }

        return [];
    }

    /**
     * 编辑微博便签
     *
     * @param  array $data 便签数据
     *
     * @return bool
     *
     * @author qishaobo
     *
     * @since  2016-04-15
     */
    public function editDiaryMemo($data)
    {
        $where = ['memo_creator' => $data['memo_creator']];
        return app($this->diaryMemoRepository)->updateData($data, $where);
    }

    /**
     * 微博主页，导出
     *
     * @param  array $param
     *
     * @return bool
     *
     * @author qishaobo
     *
     * @since  2016-04-15
     */
    public function exportDiarys($param)
    {

        $header = [
            'kind'            => trans("diary.report_type"),
            'diary_scope'     => ['data' => trans("diary.reporting_cycle"), 'style' => ['width' => '23']],
            'diary_user_name' => ['data' => trans("diary.report_author"), 'style' => ['width' => '12']],
            'create_time'     => ['data' => trans("diary.release_date"), 'style' => ['width' => '12']],
            'template'        => trans("diary.used_templates"),
            'diary_content'   => trans("diary.content_of_the_report"),
        ];

        $data = $this->getDiaryList($param, $param['user_info']);

        foreach ($data as $k => $v) {
            $content = $data[$k]["diary_content"];
            if($v["plan_template"] == "2" || $v["plan_template"] == "3") {
                $contentArray = json_decode($content,true);
                $contentString = "";
                if(count($contentArray)) {
                    foreach ($contentArray as $key => $blockValue) {
                        $contentString .= isset($blockValue["title"]) ? $blockValue["title"].":" : "";
                        if(isset($blockValue["reports"]) && count($blockValue["reports"])) {
                            foreach ($blockValue["reports"] as $key => $reportsValue) {
                                if(isset($reportsValue["time"])) {
                                    $contentString .= "(".$reportsValue["time"].")";
                                }
                                $contentString .= $reportsValue["content"];
                            }
                        }
                        $contentString .= ";";
                    }
                }
                $contentString = strip_tags($contentString);
                $contentString = str_replace(["&nbsp;"], [" "], $contentString);
                $data[$k]["diary_content"] = $contentString;
                $data[$k]["template"] = trans("diary.template").$v["plan_template"];
            } else {
                $content = strip_tags($content);
                $content = str_replace(["&nbsp;"], [" "], $content);
                $data[$k]["diary_content"] = $content;
                if($v["plan_template"] == "1") {
                    $data[$k]["template"] = trans("diary.template_1");
                } else {
                    $data[$k]["template"] = "";
                }
            }
            // $data[$k]["diary_content"] = strip_tags($data[$k]["diary_content"]);
            // $data[$k]["diary_content"] = str_replace(["&nbsp;"], [" "], $data[$k]["diary_content"]);
            $data[$k]["kind"] = $this->getTemplateKindName($v["plan_kind"]);
            $diary_scope = "";
            if($v["plan_kind"] == "1") {
                $diary_scope = $v["diary_date"];
            } else {
                if($v["plan_scope_date_start"] != "0000-00-00" && $v["plan_scope_date_end"] != "0000-00-00") {
                    $diary_scope = $v["plan_scope_date_start"]." - ".$v["plan_scope_date_end"];
                }
            }
            $data[$k]["diary_scope"] = $diary_scope;
            $data[$k]["create_time"] = date("Y-m-d",strtotime($v["created_at"]));
        }
        return compact('header', 'data');
    }

    /**
     * 模板设置，翻译模板类型的名字
     * @param  [type] $param [description]
     * @return [type]        [description]
     */
    function getTemplateKindName($number) {
            $templateKindString = "";
            switch ($number) {
                case '1':
                    $templateKindString = trans("diary.daily");
                    break;
                case '2':
                    $templateKindString = trans("diary.weekly_report");
                    break;
                case '3':
                    $templateKindString = trans("diary.monthly_report");
                    break;
                case '4':
                    $templateKindString = trans("diary.semi_annual_report");
                    break;
                case '5':
                    $templateKindString = trans("diary.annual_report");
                    break;
                case '6':
                    $templateKindString = trans("diary.quarterly");
                    break;
                default:
                    $templateKindString = "";
                    break;
            }
            return $templateKindString;
        }

    /**
     * 计划模板设置，获取计划类型
     * @param  [type] $param [description]
     * @return [type]        [description]
     */
    public function getDiaryTemplateType($param) {
        $diarys = [
            ["diary_type_title"=>trans("diary.daily"),"diary_type_id"=>1],
            ["diary_type_title"=>trans("diary.weekly_report"),"diary_type_id"=>2],
            ["diary_type_title"=>trans("diary.monthly_report"),"diary_type_id"=>3],
            ["diary_type_title"=>trans("diary.quarterly"),"diary_type_id"=>6],
            ["diary_type_title"=>trans("diary.semi_annual_report"),"diary_type_id"=>4],
            ["diary_type_title"=>trans("diary.annual_report"),"diary_type_id"=>5]
        ];
        if(isset($param['search']['diary_type_id'])){
            $diary_type_id = isset( $param['search']['diary_type_id'][0][0])? $param['search']['diary_type_id'][0][0]:'';
            if($diary_type_id){
                foreach ($diarys as $key => $value) {
                    if($value['diary_type_id'] == $diary_type_id){
                        return [$value];
                    }
                }
            }else{
                return [];
            }
        }
        return $diarys;
    }

    /**
     * 模板设置，获取模板设置信息
     * @param  [type] $param [description]
     * @return [type]        [description]
     */
    public function getDiaryTemplateSetList($param) {
        $param = $this->parseParams($param);
        $kindId = "";
        $templateNumber = "";
        if(isset($param["search"])) {
            $kindId = isset($param["search"]["template_kind"]) ? $param["search"]["template_kind"][0] : "";
            $templateNumber = isset($param["search"]["template_number"]) ? $param["search"]["template_number"][0] : "";
        }
        if($templateSet = app($this->diaryTemplateSetRepository)->diaryTemplateSetList($param)) {
            // $templateSetArray = $templateSet->toArray();
            // return $templateSet;
        } else {
            $data = [
                // 模板种类（数字1-5，对应：日报,周报,月报,半年报,年报）
                'template_kind'           => $kindId,
                // 具体的模板id（数字1-3，对应模板1-3）
                'template_number'         => $templateNumber,
                // 模板1的模板内容
                'html_template_content'   => isset($param['html_template_content']) ? $param['html_template_content'] : "",
                // 模板范围内关联的用户字段，如果选了全体，值为all，否则为空
                'diary_template_set_user' => "",
            ];
            if ($result = app($this->diaryTemplateSetRepository)->insertData($data)) {
                $setId = $result->set_id;
            }
            $templateSet = $data;
        }
        $searchParam = [
            "template_kind" => $kindId,
            "search" => [
                "templateNumber" => [$templateNumber],
            ],
            "returntype" => "object"
        ];
        $userInfo = app($this->diaryTemplateUserModalRepository)->diaryTemplateSetUserList($searchParam);
        $userInfo = $userInfo->pluck("user_id");
        $templateSet["user_scope"] = $userInfo;
        // 和 user 表同步
        $this->diaryTemplateUserModalCheckAndSyncUser([]);
        // 获取微博模板内容
        $searchTemplateContent = [
            'template_kind' => $kindId,
            'template_number' => $templateNumber,
        ];
        $template_content = $this->getTemplateContent($searchTemplateContent);
        $templateSet['html_template_content'] = isset($template_content['content']) ? $template_content['content']: "";
        return $templateSet;
    }

    /**
     * 模板设置，保存模板设置信息
     * @param  [type] $param [description]
     * @return [type]        [description]
     */
    public function saveDiaryTemplateSet($param) {
        $kindId         = $param['template_kind'];
        $templateNumber = $param['template_number'];
        $paramDiaryTemplateSetUser = isset($param['diary_template_set_user']) ? $param['diary_template_set_user'] : [];
        $enableTemplateContent = isset($param['enable_template_content']) ? $param['enable_template_content'] : '';
        $htmlTemplateContent = isset($param['html_template_content']) ? json_encode($param['html_template_content']) : "";
        $data = [
            // 模板种类（数字1-5，对应：日报,周报,月报,半年报,年报）
            'template_kind'           => $kindId,
            // 具体的模板id（数字1-3，对应模板1-3）
            'template_number'         => $templateNumber,
            // 模板1的模板内容
            'html_template_content'   => $htmlTemplateContent,
            // 模板范围内关联的用户字段，如果选了全体，值为all，否则为空
            'diary_template_set_user' => "",

        ];
        if($paramDiaryTemplateSetUser == "all") {
            $data["diary_template_set_user"] = "all";
        }
        //新增/更新报告模板内容
        if($enableTemplateContent == '1' || $enableTemplateContent == 1){
            $this->addDiaryTemplateContent($kindId,$templateNumber,$htmlTemplateContent);
        }
        // 删除微博模板内容
        if($enableTemplateContent == '0' || $enableTemplateContent == 0){
            $this->deleteDiaryTemplateContent($kindId,$templateNumber);
        }
        // $listParam = [
        //     "search" => [
        //         "template_kind" => [$kindId],
        //         "template_number" => [$templateNumber],
        //     ],
        //     "returntype" => "first"
        // ];
        // $setInfo = app($this->diaryTemplateSetRepository)->diaryTemplateSetList($listParam);
        // if(count($setInfo) > 0) {
        //     $setInfo = $setInfo->toArray();
        //     $setId = $setInfo["set_id"];
        //     app($this->diaryTemplateSetRepository)->updateData($data,["set_id"=>$setId]);
        // } else {
        //     if ($result = app($this->diaryTemplateSetRepository)->insertData($data)) {
        //         $setId = $result->set_id;
        //     }
        // }
        // 处理范围内用户
        // $where = ['set_id' => [$setId]];
        // app($this->diaryTemplateSetUserRepository)->deleteByWhere($where);
        // if (!empty($userPurviewInfo)) {
        //     $userData = [];
        //     foreach (array_filter($userPurviewInfo) as $v) {
        //         $userData[] = ['set_id' => $setId, 'user_id' => $v];
        //     }
        //     app($this->diaryTemplateSetUserRepository)->insertMultipleData($userData);
        // }
        // 处理 diary_template_user_modal 表
        // if($paramDiaryTemplateSetUser) {
//        var_dump(count);die;
            if($paramDiaryTemplateSetUser == "all") {
                app($this->diaryTemplateUserModalRepository)->updateData(["template_kind".$kindId => $templateNumber],["user_id"=>["","!="]]);
            } else {
                if(is_array($paramDiaryTemplateSetUser) &&  count($paramDiaryTemplateSetUser)) {
                    // 根据 $kindId $templateNumber 获取 diary_template_user_modal 表有多少有效用户
                    $alreadyTemplateUserObject = app($this->diaryTemplateUserModalRepository)->getDiaryTemplateUserModalList(["search"=>["template_kind".$kindId => [$templateNumber]]]);
                    $alreadyTemplateUserInfo = $alreadyTemplateUserObject->pluck("user_id");
                    $alreadyTemplateUserInfo = $alreadyTemplateUserInfo->toArray();
                    $templateUserDiff = array_diff($alreadyTemplateUserInfo,$paramDiaryTemplateSetUser);

                    app($this->diaryTemplateUserModalRepository)->updateData(["template_kind".$kindId => $templateNumber],["user_id"=>[$paramDiaryTemplateSetUser,"in"]]);
                    if(count($templateUserDiff)) {
                        app($this->diaryTemplateUserModalRepository)->updateData(["template_kind".$kindId => "0"],["user_id"=>[$templateUserDiff,"in"],"template_kind".$kindId => [$templateNumber]]);
                    }
                } else {
                    app($this->diaryTemplateUserModalRepository)->updateData(["template_kind".$kindId => "0"],["template_kind".$kindId => [$templateNumber]]);
                }
            }
            if(is_array($paramDiaryTemplateSetUser) && count($paramDiaryTemplateSetUser)) {
                // diary_template_set 表，diary_template_set_user字段 all 的处理
                $updateSetTableData = ["diary_template_set_user" => ""];
                if($templateNumber == "1") {
                    app($this->diaryTemplateSetRepository)->updateData($updateSetTableData,["template_kind" => [$kindId],"template_number" => ["2"]]);
                    app($this->diaryTemplateSetRepository)->updateData($updateSetTableData,["template_kind" => [$kindId],"template_number" => ["3"]]);
                } else if($templateNumber == "2") {
                    app($this->diaryTemplateSetRepository)->updateData($updateSetTableData,["template_kind" => [$kindId],"template_number" => ["1"]]);
                    app($this->diaryTemplateSetRepository)->updateData($updateSetTableData,["template_kind" => [$kindId],"template_number" => ["3"]]);
                } else if($templateNumber == "3") {
                    app($this->diaryTemplateSetRepository)->updateData($updateSetTableData,["template_kind" => [$kindId],"template_number" => ["1"]]);
                    app($this->diaryTemplateSetRepository)->updateData($updateSetTableData,["template_kind" => [$kindId],"template_number" => ["2"]]);
                }
            }
        // }
        return "1";
    }

    /**
     * 获取模板内容
     * @param $param
     * @return array
     */
    function getTemplateContent($param)
    {
        $param = $this->parseParams($param);
        $kindId         = isset($param['template_kind']) ? $param['template_kind'] : null;
        $templateNumber = isset($param['template_number']) ? $param['template_number'] : null;
        if(is_numeric($kindId) && is_numeric($templateNumber)){
            $search = [
                "report_kind_id"   => [$kindId],
                "template_kind_id" => [$templateNumber]
            ];
            $content = app($this->diaryTemplateContentRepository)->getContent($search);
            if(count($content) > 0 && isset($content[0]['content'])){
                $content[0]['content'] = json_decode($content[0]['content'],true);
                if($templateNumber == 1 || $templateNumber == '1'){
                    $content[0]['strip_content'] = strip_tags($content[0]['content']);
                    str_replace('&nbsp;',"",$content[0]['strip_content']);
                }
                return $content[0];
            }
        }
        return [];
    }

    /**
     * 新增/更新报告类型-模板id对应的模板内容
     * @param $kindId
     * @param $templateNumber
     * @param $htmlTemplateContent
     * @return bool
     */
    function addDiaryTemplateContent($kindId,$templateNumber,$htmlTemplateContent)
    {
        $searchContent = ['report_kind_id'=>[$kindId],'template_kind_id'=>[$templateNumber]];
        $contentExists = app($this->diaryTemplateContentRepository)->tmplateContentExists($searchContent);
        if($contentExists){
            $contentData = [
                'content' => $htmlTemplateContent
            ];
            app($this->diaryTemplateContentRepository)->updateData($contentData,$searchContent);
        }else{
            $contentData = [
                'report_kind_id'  => $kindId,
                'template_kind_id'=> $templateNumber,
                'content'         => $htmlTemplateContent,
            ];
            app($this->diaryTemplateContentRepository)->insertData($contentData,$searchContent);
        }
        return true;
    }

    /**
     * 删除微博模板内容
     * @param $kindId
     * @param $templateNumber
     * @return mixed
     */
    function deleteDiaryTemplateContent($kindId,$templateNumber){
        $searchContent = ['report_kind_id'=>[$kindId],'template_kind_id'=>[$templateNumber]];
        return app($this->diaryTemplateContentRepository)->deleteByWhere($searchContent);
    }

    /**
     * 同步 diary_template_user_modal 表和 user 表的用户
     * @param  [type] $param [description]
     * @return [type]        [description]
     */
    function diaryTemplateUserModalCheckAndSyncUser($param) {
        // 查询 diary_template_user_modal 里面的无效用户并删除
        $deleteResult = app($this->diaryTemplateUserModalRepository)->templateUserModalInvalidUser($param);
        app($this->diaryTemplateUserModalRepository)->deleteByWhere(['user_id' => [$deleteResult,"in"]]);

        // 查，用户里有，user_modal 没有，且有效的用户，插入
        $result = app($this->diaryTemplateUserModalRepository)->templateUserModalCheckAndSyncUser($param);
        app($this->diaryTemplateUserModalRepository)->insertMultipleData($result);

    }

    /**
     * 模板设置，获取用户模式下的模板设置信息
     * @param  [type] $param [description]
     * @return [type]        [description]
     */
    public function getUserModalDiaryTemplateSetList($param) {
        $param = $this->parseParams($param);
        $returnData = $this->response(app($this->diaryTemplateUserModalRepository),'diaryTemplateSetUserListTotal','diaryTemplateSetUserList',$param);
        return $returnData;
    }

    /**
     * 模板设置，保存用户模式下的模板设置信息
     * @param  [type] $param [description]
     * @return [type]        [description]
     */
    public function saveUserModalDiaryTemplateSet($param) {
        $kindId         = $param["kind_id"];
        $templateNumber = $param["template_number"];
        if (is_array($param["user_id"])) {
            $userInfo = $param['user_id'];
        } else {
            $userInfo = json_decode($param["user_id"],true);
        }
        if(is_array($userInfo)){
            $updateData = [];
            foreach([1,2,3,4,5,6] as $value){
                if($value == $kindId) {
                    $updateData['template_kind'.$value] = $templateNumber;
                }else{
                    $updateData['template_kind'.$value] = 0;
                }
            }
            app($this->diaryTemplateUserModalRepository)->updateData($updateData,["user_id"=>[$userInfo,'in']]);
        }
        return "1";
    }

    /**
     * 工作计划，获取某条工作计划
     * @param  [type] $param [description]
     * @return [type]        [description]
     */
    public function getUserDiaryPlan($param,$own) {
        $searchParam = [
            "search" => [
                "plan_kind" => [$param["kind_id"]],
                "plan_scope_date_start" => [$param["plan_scope_date_start"]],
                "plan_scope_date_end" => [$param["plan_scope_date_end"]],
                "user_id" => [$own["user_id"]],
            ],
            "returntype" => "first",
        ];
        $diaryPlanInfo = app($this->diaryRepository)->getDiaryPlanInfo($searchParam);
        // 获取附件
        if($diaryPlanInfo && $diaryPlanInfo['diary_id']) {
            $diaryPlanInfo['attachments'] = app($this->attachmentService)->getAttachmentIdsByEntityId(['entity_table' => 'diary', 'entity_id' => $diaryPlanInfo['diary_id']]);
        }
        return $diaryPlanInfo;
    }

    /**
     * 工作计划，保存工作计划
     * @param  [type] $param [description]
     * @return [type]        [description]
     */
    public function saveUserDiaryPlan($param,$own) {
        // $param = $this->parseParams($param);
        // 报告内容；如果是2/3，那么这里存没有样式，所有内容拼接起来的字符串。
        $diaryContent       = isset($param['plan_content']) ? $param['plan_content'] : "";
        $userId             = $own['user_id'] ?? "";
        $kindId             = isset($param['kind_id']) ? $param['kind_id'] : "";
        $templateNumber     = isset($param['template_number']) ? $param['template_number'] : "";
        $planScopeDateStart = isset($param['plan_scope_date_start']) ? $param['plan_scope_date_start'] : "";
        $planScopeDateEnd   = isset($param['plan_scope_date_end']) ? $param['plan_scope_date_end'] : "";
        $address            = isset($param['address']) ? $param['address'] : "";
        // 提交状态： savefor|暂存， publish|提交
        $submitType = isset($param['submitType']) ? $param['submitType'] : "savefor";
        // 报告状态：1|暂存，2|发布，默认2
        // $planStatus = "1";
        $diaryId = "";
        // append今日日报标识
        $appendTodayDiary = false;
        if($kindId == "1") {
            // 1、创建今日 2、编辑今日 3、add今日 4、补交x日 5、编辑x日
            // // append今日日报标识
            // $appendTodayDiary = false;
            $todayDate = date("Y-m-d",time());
            $diaryDate = isset($param['diary_date']) ? $param['diary_date'] : "";
            if(isset($param["diary_id"])) {
                $diaryId = $param["diary_id"];
                if($diaryDate == $todayDate) {
                    // 2、编辑今日 -
                    // echo "2";
                } else {
                    // 5、编辑x日
                    // echo "5";
                }
            } else {
                if($diaryDate == $todayDate) {
                    // 1、创建今日
                    // 3、add今日 -
                    $searchParam = [
                        "search" => [
                            "plan_kind" => [$kindId],
                            "diary_date" => [$diaryDate],
                            "user_id" => [$userId],
                        ],
                        "returntype" => "first",
                    ];
                    $diaryPlanInfo = app($this->diaryRepository)->getDiaryPlanInfo($searchParam);
                    if(!empty($diaryPlanInfo['diary_id'])) {
                        $diaryId = $diaryPlanInfo["diary_id"];
                    }
                    
                    if(isset($diaryId) && $diaryId) {
                        // 发布
                        // if($submitType == "publish") {
                        //     // 替换掉已有的值
                        // } else if($submitType == "savefor") {
                        //     // 保存
                        //     $old_plan_status = $diaryPlanInfo["plan_status"];
                        //     if($old_plan_status == "1") {
                        //         // 替换掉已有的值
                        //     } else if($old_plan_status == "2") {
                        //         // 拼接上值

                        //         // 3、add今日 -
                        //         // echo "3";
                                $appendTodayDiary = true;
                        //     }
                        // }
                    } else {
                        // 1、创建今日
                        // echo "1";
                    }
                } else {
                    // 4、补交x日
                    // echo "4";
                }
            }
        } else {
            if(isset($param["diary_id"])) {
                $diaryId = $param["diary_id"];
            }
        }
        // 判断是编辑还是创建--new
        if($diaryId) {
            // $diaryId = $param["diary_id"];
            if ($diaryInfo = app($this->diaryRepository)->getDetail($diaryId)) {
                if($diaryInfo['user_id'] != $own['user_id']){
                    return ['code' => ['0x008009', 'diary']];
                }
                // 判断一天提交多次日报的情况
                if($kindId == "1" && $appendTodayDiary) {
                    $oldDiaryContent = $diaryInfo["diary_content"];
                    $oldDiaryStatus = $diaryInfo["plan_status"];
                    if($templateNumber == "2" || $templateNumber == "3") {
                        $oldDiaryContentArray = json_decode($oldDiaryContent,true);
                        $diaryContentArray    = json_decode($diaryContent,true);
                        $diaryContentNewArray = array_merge($oldDiaryContentArray,$diaryContentArray);
                        $diaryContent         = json_encode($diaryContentNewArray,JSON_UNESCAPED_UNICODE);
                    } else {
                        $diaryContent = $oldDiaryContent."\n".$diaryContent;
                    }
                }
                $updateData = [
                    "diary_content" => $diaryContent,
                ];
                if($submitType == "publish") {
                    $updateData['plan_status'] = "2";
                }
                app($this->diaryRepository)->updateData($updateData, ["diary_id" => [$diaryId]]);
                // 从保存状态修改为 发布状态，修改create_at 为当前时间
                if(isset($diaryInfo['plan_status']) && $diaryInfo['plan_status'] != 2  && $submitType == "publish") {
                    app($this->diaryRepository)->updateDiaryCreatedAt($diaryId);
                }
            }
        } else {
            $diary_date = isset($param['diary_date']) ? $param['diary_date'] : date("Y-m-d",time());

            if(isset($kindId) && $kindId == 1 ){
                //微博补交判断是否在允许补交时间范围内
                if(! $this -> canMakeUpBlog($diary_date)){
                    return ['code' => ['0x008007', 'diary']];
                }
            }
            // if($submitType == "publish") {
                $planStatus = "2";
            // } else if($submitType == "savefor") {
            //     $planStatus = "1";
            // }
            $insertData = [
                'user_id'               => $userId,
                'diary_content'         => $diaryContent,
                'diary_date'            => $diary_date,
                'plan_kind'             => $kindId,
                'plan_template'         => $templateNumber,
                'plan_scope_date_start' => $planScopeDateStart,
                'plan_scope_date_end'   => $planScopeDateEnd,
                'plan_status'           => $planStatus,
                'address'               => $address,
            ];
            // 判断重复！
            if($result = app($this->diaryRepository)->insertData($insertData)) {
               $diaryId = $result->diary_id;
            }
        }
        if ($diaryId > 0) {
            if($kindId == "1" && $appendTodayDiary) {
                if(!empty($param['attachments'])) {
                    app($this->attachmentService)->attachmentRelation("diary", $diaryId, $param['attachments'], 'add');
                }
                return ["diary_id" => $diaryId,"appendTodayDiary" => $appendTodayDiary];
            }
            if(isset($param['attachments'])) {
                app($this->attachmentService)->attachmentRelation("diary", $diaryId, $param['attachments']);
            }
            return ["diary_id" => $diaryId,"appendTodayDiary" => $appendTodayDiary];
        }
        // 记录日志！
        return ["diary_id" => $diaryId,"appendTodayDiary" => $appendTodayDiary];
    }

    /**
     * 是否可以补交微博
     * @param $day
     * @param $diarySet
     * @return boolean
     */
    function canMakeUpBlog($day,$diarySet = null){
        if(!$diarySet){
            $diarySet = $this -> getPermission();
        }
        if($diarySet['diary_supplement'] == 1){
            if($diarySet['diary_supplement_expire'] > 0){
                $diarySet['diary_supplement_expire'];
                $diary_day = strtotime($day)+24*3600;
                // 在允许时间范围内可交
                if(time() - $diary_day <= $diarySet['diary_supplement_expire'] * 3600){
                    return true;
                }
            }else{
                //任意时间可交
                return true;
            }
        }else{
            if($day == date("Y-m-d")){
                return true;
            }
        }
        return false;
    }

    /**
     * 工作计划，获取某个用户的计划模板
     * @param  [type] $param [description]
     * @return [type]        [description]
     */
    function getUserDiaryPlanTemplate($param,$own) {
        $kindId = $param["kind_id"];
        $userId = $own["user_id"];
        $searchParam = [
            "template_kind" => $kindId,
            "search" => [
                "diary_template_user_modal.user_id" => [$userId],
            ],
            "returntype" => "first"
        ];
        $templateInfo = app($this->diaryTemplateUserModalRepository)->diaryTemplateSetUserList($searchParam);
        if (!empty($templateInfo)) {
            $templateInfo = $templateInfo->toArray();
            $templateNumber = (isset($templateInfo["template_kind".$kindId]) && $templateInfo["template_kind".$kindId] > 0) ? $templateInfo["template_kind".$kindId] : 1;
            $templateInfo["templateNumber"] = $templateNumber;
            $templateInfo["template_kind".$kindId] = $templateNumber;
        } else {
            $templateInfo = [];
            $templateInfo["templateNumber"] = 1;
            $templateInfo["template_kind".$kindId] = 1;
        }

        // 如果是日报的时候，获取模板，那么，判断当天是否已经写了日报
        if($kindId == "1") {
            $searchParam = [
                "search" => [
                    "plan_kind"  => [$kindId],
                    "diary_date" => [date('Y-m-d',time())],
                    "user_id"    => [$userId],
                ],
                "returntype" => "first",
            ];
            $diaryPlanInfo = app($this->diaryRepository)->getDiaryPlanInfo($searchParam);
            // 获取附件
            if(!empty($diaryPlanInfo['diary_id'])) {
                $diaryPlanInfo['attachments'] = app($this->attachmentService)->getAttachmentIdsByEntityId(['entity_table' => 'diary', 'entity_id' => $diaryPlanInfo['diary_id']]);
            }
            if($diaryPlanInfo && isset($diaryPlanInfo->diary_id)) {
                $templateInfo["had_diary_template"] = $diaryPlanInfo->plan_template;
                $templateInfo["had_diary_flag"]     = $diaryPlanInfo->diary_id;
                $templateInfo["todayDiaryInfo"]     = $diaryPlanInfo;
            }
        }
        return $templateInfo;
    }

    /**
     * 单独获取客户联系记录
     * @param  string $recordId    微博日志创建者用户id
     * @param   $diaryDate       日志日期
     * @return array             客户信息集合
     */
    public function getContactRecord($recordId,$diaryDate)
    {
        $userInfo = own('');
        if (!isset($userInfo)) {
            return [];
        }
        if (!isset($recordId)) {
            return [];
        }
        $where = [
            'search' => [
                'diary_date' => [$diaryDate],
                'user_id'    => [$recordId]
            ],
        ];
            // 没有微博主页,只能获取自己的日报
            if (isset($userInfo['menus']) && isset($userInfo['menus']['menu']) && !in_array('28', $userInfo['menus']['menu'])) {
                $hasDialy = false;
                // if(!$hasDialy){
                //     // 全部是非日报，无权限
                //     return ['code' => ['0x000006', 'common']];
                // }
                // // 微博不是自己的
                // if ($recordId != $userInfo['user_id']) {
                //     // 无权限
                //     return ['code' => ['0x000006', 'common']];
                // }
            }
            // 获取的微博创建人必须是关注的人
            // if($recordId != $userInfo['user_id']){
            //     // 不是自己的情况下，必须是关注的人，否则无权限
            //     // 验证是否在我的关注中
            //     $inAttention = $this->userInAttention($recordId,$userInfo);
            //     if(!$inAttention) {
            //         // 不是关注人，无权限
            //         return ['code' => ['0x000006','common']];
            //     }
            // }

            $paramContactRecord = [
                'search' => [
                    // 'permission' => app($this->customerService)->getUserTemporaryPermission($userInfo), // 递归获取所有下级用户，很慢?
                    // 'permission' => app($this->customerService)->getUserPermission($userInfo), // 调用客户模块方法
                    'record_creator' => [$recordId],
                    'created_at'   => [[$diaryDate.' 00:00:00', $diaryDate.' 23:59:59'], 'between'],
                ]
            ];
            $contactRecords = app($this->customerContactRecordService)->lists($paramContactRecord,$userInfo);
            if(isset($contactRecords['list']) && count($contactRecords['list']) > 0){
                
                $contactRecords = $contactRecords['list'];
                // $contactRecords = $contactRecords['list']->toArray();
            }else{
                $contactRecords = [];
            }
            $contractType = app($this->systemComboboxFieldRepository)->getComboboxFieldsNameByComboboxId(11);
            $custonerRecord = [];
            if (!empty($contactRecords)) {
                foreach ($contactRecords as $key => $val) {
                    if(!empty($val['contact_record_customer'])){
                        $val['attachment_id'] = app($this->attachmentService)->getAttachmentIdsByEntityId(['entity_table' => 'customer_contact_record', 'entity_id' => $val['record_id']]);
                        $val['contract_type'] = isset($contractType[$val['record_type']])?$contractType[$val['record_type']]:'';
                        $custonerRecord[] = $val;
                    }
                }
            } else {
                $custonerRecord = [];
            }
            return ['list'=>$custonerRecord,'total'=> count($custonerRecord)];
    }

    /**
     * 为了解决手机版微博主页高级搜索，传递用户id字符串太长的问题
     * return "" -- 表示全体，
     * return ['user_id' => []] -- 表示范围内没人，
     * return ['user_id' => ['WV00000001','WV00000002']] -- 表示正常设置范围，
     * @param  [type] $param [description]
     * @return [type]        [description]
     */
    public function diaryAttentionSelectorDirector($param)
    {
        $userId = isset($param['user_id']) ? $param['user_id'] : [];
        $params = [];
        $params['mobile'] = 'mine';
        $userMineIds = $this->getMyAttention($userId,$params);
        $params['mobile'] = 'default';
        $userDefaultIds = $this->getMyAttention($userId,$params);
        $userIdsAll = array_merge($userMineIds,$userDefaultIds);
        $userIds = [];
        foreach($userIdsAll as $val){
            array_push($userIds,$val['user_id']);
        }
        if(!in_array($userId,$userIds)){
            array_push($userIds,$userId);
        }
        //$userIds = $this->getDiaryReportUsers($userId);
        $data = [
            'user_id' => $userIds,
        ];
        return $data;
    }

    /**
     * 改变点赞状态
     * @param  array $params is_like参数，1为点赞，2为取消
     * @return [type]         [description]
     */
    public function getDiaryLike($params,$own)
    {
        $user_id = $own['user_id'] ?? '';
        $diary_id = isset($params['diary_id']) ? $params['diary_id'] : '';
        $is_like = isset($params['is_like']) ? $params['is_like'] : '';
        if($diary = app($this->diaryRepository)->getDetail($diary_id)) {
            $diary = $diary->toArray();
            // 没有微博主页,只能点赞自己
            if (isset($own['menus']) && isset($own['menus']['menu']) && !in_array('28', $own['menus']['menu'])) {
                // 不能点赞非日报
                if (isset($diary['plan_kind']) && $diary['plan_kind'] != 1) {
                    // 无权限
                    return ['code' => ['0x000006', 'common']];
                }
                // 微博不是自己的
                if (isset($diary['user_id']) && $diary['user_id'] != $own['user_id']) {
                    // 无权限
                    return ['code' => ['0x000006', 'common']];
                }
            }
            // 点赞其他人时，该人必须是关注的人
            if(isset($diary['user_id']) && $diary['user_id'] != $own['user_id']){
                // 不是自己的情况下，必须是关注的人，否则无权限回复
                // 验证是否在我的关注中
                $inAttention = $this->userInAttention($diary['user_id'],$own);
                if(!$inAttention) {
                    // 不是关注人，无权限点赞
                    return ['code' => ['0x000006','common']];
                }
            }

            // 查询是否有点赞记录
            $result = DB::table('diary_like_people')->where(
                [
                    'user_id' => $user_id,
                    'diary_id' => $diary_id,
                ])->first();
            // 如果要点赞，数据库中没有点赞数据，则添加点赞记录
            if ($is_like == 1) {
                if (!$result) {
                    DB::table('diary_like_people')->insert([
                        'user_id' => $user_id,
                        'diary_id' => $diary_id,
                    ]);
                }
            } else {
                // 如果取消点赞，删除存在的点赞记录
                DB::table('diary_like_people')->where([
                    'user_id' => $user_id,
                    'diary_id' => $diary_id,
                ])->delete();
            }
            return true;
        }
    }

    private function getWeeksTrans()
    {
        return [
            0 => trans("diary.sunday"),
            1 => trans("diary.monday"),
            2 => trans("diary.tuesday"),
            3 => trans("diary.wednesday"),
            4 => trans("diary.thursday"),
            5 => trans("diary.friday"),
            6 => trans("diary.saturday"),
        ];
    }

    /**
     * 获取微博设置
     * @param $params
     * @return array
     */
    public function getPermission()
    {
        //获取缓存
        if (Cache::has("diary_permission_set")) {
            $securityData = Cache::get('diary_permission_set');
            return json_decode($securityData,true);
        }
        $result = app($this->diaryPermissionRepository)->getAllPermission();

        $securityData = [];
        foreach ($result as $key => $value) {
            $securityData[$value['param_key']] = $value['param_value'];
        }

        //如果获取日志修改设置时间,则转一下格式
        $showDay = floor($securityData['delete_diary_expire']/24);
        $showHour = $securityData['delete_diary_expire']-($showDay*24);
        $securityData = array(
            'show_day' => $showDay,
            'show_hour' => $showHour,
            'diary_supplement' => (int) $securityData['diary_supplement'],
            'dimission' => (int) $securityData['dimission'],
            'show_work_time' => isset($securityData['show_work_time']) ? $securityData['show_work_time'] : 1,
            'display_holiday_microblog' => isset($securityData['display_holiday_microblog']) ? (int) $securityData['display_holiday_microblog'] : 1,
            'diary_supplement_expire' => isset($securityData['diary_supplement_expire']) ? (int) $securityData['diary_supplement_expire'] : 1,
            'is_auto' => isset($securityData['is_auto']) ?  $securityData['is_auto'] : '',
            'remind_time' => isset($securityData['remind_time']) ?  $securityData['remind_time'] : '',
            'location' => isset($securityData['location']) ?  $securityData['location'] : 1,
        );
        Cache::forever('diary_permission_set',json_encode($securityData));
        return $securityData;
    }

    /**
     * 设置微博
     * @param $params
     * @param $data
     * @return array|bool
     */
    public function modifyPermission($data)
    {
        if (Cache::has("diary_permission_set")) {
            Cache::forget('diary_permission_set');
        }
        if (isset($data['show_day']) || isset($data['show_hour'])) {
            $hour = $data['show_day'] * 24 + $data['show_hour'];
            unset($data);
            $data['delete_diary_expire'] = $hour;
        }
        $modifyResult = true;
        foreach ($data as $key => $value) {
            $where = array('param_key' => array($key));
            $new_data = array('param_key' => $key, 'param_value' => $value);
            $result = app($this->diaryPermissionRepository)->updateDataBatch($new_data, $where);
            if(!$result) {
                // $modifyResult = false;
                app($this->diaryPermissionRepository)->insertData($new_data);
            }
        }
        if(!$modifyResult) {
            return array('warning' => array('0x000004', 'common'));
        }
        // 删除默认关注缓存
        if(isset($data['dimission'])){
            $all_user_ids = app($this->userService)->getAllUserIdString();
            $all_user_id_arr = explode(",",$all_user_ids);
            if(count($all_user_id_arr) > 0){
                foreach($all_user_id_arr as $val){
                    if(Cache::has('default_attention_list_'.$val)){
                        Cache::forget('default_attention_list_'.$val);
                    }
                }
            }
        }
        return $result;
    }

    /**
     * 新增关注组
     * @param $param
     * @param $own
     * @return array
     */
    public function addAttentionGroup($param,$own)
    {
        $param = $this->parseParams($param);
        $groupName = isset($param['groupName']) ? $param['groupName']:null;
        $groupOrder = isset($param['groupOrder']) && is_numeric($param['groupOrder']) ? $param['groupOrder']:0;
        $userId = $own['user_id'];
        if(!$groupName) return ['code' => ['0x023002', 'archives']];
        $data = [
            'search'=>[
                'user_id'   =>[$userId],
                'group_name'=>[$groupName],
            ]
        ];
        if(app($this->diaryAttentionGroupRepository)->getAttentionGroupTotal($data) > 0)
            return ['code' => ['0x000020','common']];

        $data = [
            'user_id'   =>$userId,
            'group_name'=>$groupName,
            'field_sort' => $groupOrder,
        ];
        return app($this->diaryAttentionGroupRepository)->insertData($data);
    }

    /**
     * 获取分组列表
     * @param $own
     */
    public function getAttentionGroupList($param,$own)
    {
        $param = $this->parseParams($param);
        $param['search']['user_id'] =  [$own['user_id']];
        if(isset($param['dataType']) && $param['dataType'] == 'all'){
            return app($this->diaryAttentionGroupRepository)->getAttentionGroup($param);
        }
        return $this->response(app($this->diaryAttentionGroupRepository),'getAttentionGroupTotal','getAttentionGroup',$param);
    }

    /**
     * 获取关注的分组信息
     * @param $group_id
     * @param $own
     * @return mixed
     */
    public function getAttentionGroupInfo($groupId,$userId)
    {
        if (is_array($userId)) {
            $userInfo = $userId;
            $userId = $userInfo['user_id'];
        }
        $param = [
            'search'=>[
                'user_id'  =>  [$userId],
                'group_id' =>  [$groupId]
            ]
        ];
        return app($this->diaryAttentionGroupRepository)->getAttentionGroup($param);
    }

    /**
     * 保存关注分组信息
     * @param $groupId
     * @param $param
     * @param $own
     * @return array|bool
     */
    public function saveAttentionGroupInfo($groupId,$param,$userId)
    {
        if (is_array($userId)) {
            $userInfo = $userId;
            $userId = $userInfo['user_id'];
        }
        $param = $this->parseParams($param);
        $userId = $userId;
        $groupName = isset($param['groupName']) ? $param['groupName'] : NULL;
        $fieldSort = isset($param['fieldSort']) ? $param['fieldSort'] : 0;
        if(!$groupName || !$groupId || !$userId)
            return ['code' => ['0x023002', 'archives']];

        $data = [
            'user_id'   => $userId,
            'group_name'=> $groupName,
            'field_sort'=> $fieldSort,
        ];
        $where = [
            'group_id' => [$groupId],
            'user_id'  => [$userId]
        ];
        //更新分组信息
        app($this->diaryAttentionGroupRepository)->updateData($data,$where);
        //获取该用户所有的分组
        $allGroups = app($this->diaryAttentionGroupRepository)->getAttentionGroup(['search'=>['user_id'=>[$userId]]]);
        $groupIds = [];
        foreach($allGroups as $val){
            $groupIds[] = $val['group_id'];
        }
        //删除该分组的所有老数据
        app($this->diaryAttentionGroupUsersRepository)->deleteByWhere(["group_id"=>[$groupId]]);
        //分组中新用户

        $groupUsers = isset($param['users']) && is_array($param['users']) ? $param['users'] : [];
        if(count($groupUsers) > 0){
            foreach($groupUsers as $val){
                $data = [
                    'user_id'  => $val,
                    'group_id' => $groupId,
                ];
                $deleteWhere = [
                    'user_id' => [$val],
                    'group_id'=>[$groupIds,'in']
                ];
                //删除该用户在其他组的信息
                app($this->diaryAttentionGroupUsersRepository)->deleteByWhere($deleteWhere);
                app($this->diaryAttentionGroupUsersRepository)->insertData($data);
            }
        }
        return true;
    }

    /**
     * 删除关注分组
     * @param $groupId
     * @param $own
     * @return mixed
     */
    public function deleteAttentionGroup($groupId,$userId)
    {
        if (is_array($userId)) {
            $userInfo = $userId;
            $userId = $userInfo['user_id'];
        }
        $param = [
            'group_id' => [$groupId],
            'user_id'  => [$userId]
        ];
        //删除分组信息
        $result = app($this->diaryAttentionGroupRepository)->deleteByWhere($param);
        if($result) {
            //删除用户数据
            app($this->diaryAttentionGroupUsersRepository)->deleteByWhere(["group_id"=>[$groupId]]);
        }
        return $result;
    }

    /**
     * 新增关注分组用户
     * @param $param
     * @param $own
     * @return array
     */
    public function addAttentionGroupUser($param,$own)
    {
        $param = $this->parseParams($param);
        if(isset($param['user_id']) && isset($param['group_id']) && is_numeric($param['group_id'])){
            $attentionUserId =  $param['user_id'];
            $groupId = $param['group_id'];
            $userId = $own['user_id'];
            $groupInfo = app($this->diaryAttentionGroupRepository)->getDetail($groupId);
            if($groupInfo){
                $groupInfo = $groupInfo->toArray();
                if(isset($groupInfo['user_id']) && $groupInfo['user_id'] == $userId){
                    //删除关注的该用户关联其他组的信息
                    $this->deleteAttentionGroupUser($attentionUserId,$userId);
                    //添加关注信息
                    $insertData = [
                        "user_id" => $attentionUserId,
                        "group_id" => $groupId,
                    ];
                    return app($this->diaryAttentionGroupUsersRepository)->insertData($insertData);
                }else{
                    // 无权限
                    return ['code' => ['0x000006','common']];
                }
            }
        }
        // 数据异常 无group_id 或 无user_id  或 group_id 无效
        return ['code' => ['0x023002', 'archives']];
    }

    /**
     * 删除分组中某关注用户
     * @param $attentionUserId 关注人ID
     * @param $userId          用户ID
     * @return mixed
     */
    public function deleteAttentionGroupUser($attentionUserId,$userId)
    {
        //获取该用户所有的分组
        $allGroups = app($this->diaryAttentionGroupRepository)->getAttentionGroup(['search'=>['user_id'=>[$userId]]]);
        if(count($allGroups) > 0){
            $groupIds = [];
            foreach($allGroups as $val){
                $groupIds[] = $val['group_id'];
            }
            //删除关注的该用户关联组的信息
            return app($this->diaryAttentionGroupUsersRepository)->deleteByWhere(["group_id"=>[$groupIds,'in'],"user_id"=>[$attentionUserId]]);
        }
    }

    /**
     * 获取用户的分组信息
     * @param $param
     * @param $own
     * @return array
     */
    public function getUsersAttentionGroupsInfo($param,$own)
    {
        $param = $this->parseParams($param);
        $userIds = explode(",",$param['userIds']);
        $data = [];
        foreach($userIds as $key=>$userId){
            if(is_string($userId)){
                $groupsInfo = app($this->diaryAttentionGroupRepository)->getAttentionGroupByUserId($own['user_id'],$userId);
                $data[$key] = [
                    'groupsInfo' => $groupsInfo,
                    'userId'     => $userId
                ];
            }
        }
        return $data;
    }
    /**
     * 保存微博权限
     *
     * @param   array  $data
     *
     * @return  boolean
     */
    public function saveDiaryPurview($data)
    {
        if(isset($data['id'])){
            $where = [
                'id'      => [$data['id']]
            ];
            return app($this->diaryPurviewRepository)->updateData($data,$where);
        }else{
            return app($this->diaryPurviewRepository)->insertData($data);
        }
    }
    /**
     * 获取微博权限详情
     *
     * @param   string  $id
     *
     * @return  object
     */
    public function getDiaryPurviewDetail($id)
    {
        $detail =  app($this->diaryPurviewRepository)->getDetail($id);
        $purview = json_decode($detail->purview);
        // if($detail->purview_type == 1){
        //     $detail->all_sub_dept = isset($purview->all_sub_dept)?$purview->all_sub_dept:'';
        //     $detail->all_department = isset($purview->all_department)?$purview->all_department:'';
        //     if($detail->all_department != 1){
        //         $detail->dept_ids =  isset($purview->dept_ids)?$purview->dept_ids:'';
        //     }
        // }else if($detail->purview_type == 2){
        //     $detail->all_sub = isset($purview->all_sub)?$purview->all_sub:'';
        //     $detail->all_superior = isset($purview->all_superior)?$purview->all_superior:'';
        //     if($detail->all_superior != 1){
        //         $detail->superiors =  isset($purview->superiors)?$purview->superiors:'';
        //     }
        // }else if($detail->purview_type == 3){
            $detail->manager = isset($purview->manager)?$purview->manager:'';
            $detail->all_staff = isset($purview->all_staff)?$purview->all_staff:'';
            if($detail->all_staff != 1){
                $detail->user_id =  isset($purview->user_id)?$purview->user_id:[];
                $detail->dept_id =  isset($purview->dept_id)?$purview->dept_id:[];
                $detail->role_id =  isset($purview->role_id)?$purview->role_id:[];
            }
        // }
        return $detail;
    }
    /**
     * 获取微博列表
     *
     * @param   array  $param
     *
     * @return  array
     */
    public function getDiaryPurviewLists($param)
    {
        return $this->response(app($this->diaryPurviewRepository), 'getPurviewGroupTotal', 'getPurviewGroupList', $this->parseParams($param));
    }
    public function deleteDiaryPurview($id)
    {
        if (app($this->diaryPurviewRepository)->deleteById($id)) {
            return true;
        }
    }

    public function getPurviewUser($own='')
    {
        if(!$own){
            $own = own('');
        }
        $group = app($this->diaryPurviewRepository)->getPurviewGroupList([]);
        $user = [];
        if (count($group) > 0) {
            foreach ($group as $key => $value) {
                $purview = json_decode($value->purview);
                $tempUser = $this->parsePurview($purview,$own['user_id']);
                if(!$tempUser){
                    $tempUser = [];
                }
                if($tempUser != 'all'){
                    $user = array_values(array_unique(array_merge($user, $tempUser)));
                }else{
                    $user = app($this->userRepository)->getUserList([]);
                    return array_column($user,'user_id');
                }
            }
            return $user;
        }

        return [];
    }
    public function parsePurview($group,$user_id)
    {
        $user = [];
        $user = $this->getCustomPurview($group, $user_id);
        // switch ($group->type) {
        //     case 1: // 部门负责人
        //         $director = app($this->departmentDirectorRepository)->getManageDeptByUser($own['user_id'])->pluck('dept_id')->toArray();
        //         // 不是部门负责人
        //         if (empty($director)) {
        //             return [];
        //         }
        //         if ($group->all_department == 0) {
        //             // 指定部门的部门负责人
        //             $purviewDept = explode(',', $group->dept_ids);
        //             if(!in_array($own['dept_id'], $purviewDept)){
        //                 return [];
        //             }
        //         }
        //         if ($group->all_sub_dept == 1) {
        //             //管理本部门和所有下级部门
        //             foreach ($director as $key => $value) {
        //                 $allChildren = app($this->departmentService)->allChildren($value);
        //                 $deptIds     = explode(',', $allChildren);
        //                 $tempUser    = app($this->userRepository)->getUserByAllDepartment($deptIds)->pluck('user_id')->toArray();
        //                 $user        = array_values(array_unique(array_merge($user, $tempUser)));
        //             }
        //         } else {
        //             // 默认管理本部门
        //             $user = app($this->userRepository)->getUserByAllDepartment($director)->pluck('user_id')->toArray();
        //         }
        //         break;
        //     case 2: //上下级
        //         $sub = app($this->userService)->getSubordinateArrayByUserId($own['user_id'], ['include_leave' => true]);
        //         // 没有下级返回[]
        //         if(!isset($sub['id'])){
        //             return [];
        //         }
        //         if($group->all_superior == 0){
        //             // 指定上级
        //             $purviewUser = explode(',', $group->superiors);
        //             if(!in_array($own['user_id'], $purviewUser)){
        //                 return [];
        //             }
        //         }
        //         if($group->all_sub == 1){
        //             $allSub = app($this->userService)->getSubordinateArrayByUserId($own['user_id'], ['all_subordinate' => 1, 'include_leave' => true]);
        //             $user = isset($allSub['id']) ? $allSub['id'] : [];
        //         }else{
        //             $user        = isset($sub['id']) ? $sub['id'] : [];
        //         }
        //         break;
        //     case 3://自定义
        //         $user = $this->getCustomPurview($group, own('user_id'));
        //         break;
        //     default:
        //         break;
        // }
        return $user;
    }

    public function getCustomPurview($group, $userId)
    {
        if((isset($group->user_id) && $group->user_id == 'all') || (isset($group->dept_id) && $group->dept_id == 'all') || (isset($group->role_id) && $group->role_id == 'all')){
            $group->all_staff = 1;
        }
        if(isset($group->all_staff) && $group->all_staff == 1){
            return $group->manager;
        }
        $manageUser = [];
        $manageUserByDept = [];
        $manageUserByRole = [];
        // 管理的用户
        if (isset($group->user_id)) {
            $manageUser = $group->user_id;
        }
        // 管理的部门
        if (isset($group->dept_id)) {
            $manageDept = $group->dept_id;
            $manageUserByDept = empty($manageDept) ? [] : app($this->userRepository)->getUserByAllDepartment($manageDept)->pluck("user_id")->toArray();
        }
        // 管理的角色
        if (isset($group->role_id)) {
            $manageRole = $group->role_id;
            $manageUserByRole = empty($manageRole) ? [] : app($this->userRepository)->getUserListByRoleId($manageRole)->pluck("user_id")->toArray();
        }
        $res = array_values(array_unique(array_merge($manageUser, $manageUserByDept, $manageUserByRole)));
        if(in_array($userId,$res)){
            return $group->manager;
        }

    }

    public function getDefaultAttention($own,$param=[])
    {
        if(!isset($param['byPage'])){
            unset($param['limit']);
            unset($param['page']);
        }
        if(!isset($param['diarySet'])){
            $param['diarySet'] = $this->getPermission();
        }
        //有权限的用户
        $purview = $this->getPurviewUser($own);
        $userId = $own['user_id'];
        //下级用户
        if (!Cache::has('default_attention_list_'.$userId)) {
            $defaultAttentionList = app($this->userService)->getAllUserSuperior($userId,$param); // 递归获取下级所有用户，放入数组中
            Cache::add('default_attention_list_'.$userId,$defaultAttentionList,60);
        } else {
            $defaultAttentionList = Cache::get('default_attention_list_'.$userId);
        }
        $defaultAttentionUserIds = [];
        if ($defaultAttentionList) {
            $defaultAttentionList = $defaultAttentionList['list'];
            foreach ($defaultAttentionList as $k => $v) {
                $defaultAttentionUserIds[] = $v['user_id'];
            }
        }
        $ids =  array_unique(array_merge($defaultAttentionUserIds, $purview));
        if (isset($param['diarySet']) && $param['diarySet']['dimission'] == 1) {
            $param['search'] = ['user_status' => ['0', '>']];
        } else {
            $param['search'] = ['user_status' => [['0', '2'], 'not_in']];
        }
        $result = app($this->userRepository)->getUserNamesByPage($ids,$param);
        $total = app($this->userRepository)->getUserNamesByPageTotal($ids,$param);
        foreach ($result as $key => $value) {
            if(in_array($value['user_id'],$defaultAttentionUserIds))
            {
                $result[$key]['type'] = 'attention';
            }else{
                $result[$key]['type'] = 'share';
            }
        }

        if(isset($param['byPage'])){
            return ['total' => $total,'list' => $result];
        }else{
            return $result;
        }
    }
    public function getSystemWorkRecord($own,$param)
    {
        if(!isset($param['user_id']) || !isset($param['date']) || !isset($param['module'])){
            return;
        }
        $user_id = $param['user_id'];
        $date = $param['date'];
        $module = $param['module'];
        $filterConfig = $this->getRecordMethod($module);
        if ($filterConfig) {
            $services = isset($filterConfig[0]) ? $filterConfig[0] : '';
            $method = isset($filterConfig[1]) ? $filterConfig[1] : '';
            if (method_exists(app($services), $method)) {
                $result = app($services)->$method($user_id,$date);
                if(isset($result['code']) &&  $result['code']){
                    return $result;
                }
                return $result;
            }
        }
        return [];
    }

    public function getRecordMethod($module)
    {
        $arr = [
            'customer_contract'  => ['App\EofficeApp\Diary\Services\DiaryService', 'getContactRecord'],
            'task'  => ['App\EofficeApp\Task\Services\TaskService', 'dailyLogByUserId'],
            'flow' => ['App\EofficeApp\Flow\Services\FlowService', 'getFlowSearchListByUserIdAndDate'],
            'document' => ['App\EofficeApp\Document\Services\DocumentService', 'getDocumentRelationDairy'],
            'cooperation' => ['App\EofficeApp\Cooperation\Services\CooperationService', 'getDiaryCooperation'],
        ];
        return $arr[$module];

    }

    public function pushDiaryReminds($data)
    {
        $sendData = [
            'toUser'      => $data['user'],
            'remindState' => 'diary.mine',//前端路由
            'remindMark'  => 'diary-write',//需要执行脚本
        ];
        Eoffice::sendMessage($sendData);
    }


    public function addOutSendDiary($param) {

        if(!isset($param['kind_id']) || !$param['kind_id']){
            return ['code' => ['0x008010', 'diary']];
        }
        if(!isset($param['plan_content']) || !$param['plan_content']){
            return ['code' => ['0x008011', 'diary']];
        }
        if(!isset($param['creator']) || !$param['creator']){
            return ['code' => ['0x008016', 'diary']];
        }
        $diaryContent       = isset($param['plan_content']) ? $param['plan_content'] : "";
        $userId             =  $param['creator'];
        $kindId             = isset($param['kind_id']) ? $param['kind_id'] : 1; //报告种类：1日报,2周报，3月报，4半年报，5年报，默认1
        $cycle =  isset($param['cycle']) ? $param['cycle'] : '';
        if($cycle){
            $cycle = round($cycle);
        }
        $diaryDate = isset($param['diary_date']) ? $param['diary_date'] : date("Y-m-d",time());
        //是否可以写报
        $permission = $this->getPermission();
        $kindPermission = json_decode(isset($permission['show_work_time'])?$permission['show_work_time']:'',true);
        //验证模板
        $temlate_id  = $this->getUserDiaryPlanTemplate($param,own());
        $type = 'template_kind'.$kindId;
        if($temlate_id && $temlate_id[$type]){
           if($temlate_id[$type] != 1){
                return ['code' => ['0x008012', 'diary']];
           }
        }
        //默认时间
        switch ($kindId) {
            case 1:
                $planScopeDateStart =  $planScopeDateEnd =  $diaryDate;
                break;
            case 2:
                if(!$kindPermission['week']){
                    return ['code' => ['0x008018', 'diary']];
                }
                if(!$cycle){
                    $sdefaultDate = date("Y-m-d");
                // $first =1 表示每周星期一为开始日期 0表示每周日为开始日期
                $first=1;
                // 获取当前周的第几天 周日是0 周一到周六是 1 - 6
                $w = date('w', strtotime($sdefaultDate));
                // 获取本周开始日期，如果$w是0，则表示周日，减去 6 天
                $planScopeDateStart=date('Y-m-d', strtotime("$sdefaultDate -".($w ? $w - $first : 6).' days'));
                // 本周结束日期
                $planScopeDateEnd=date('Y-m-d',strtotime("$planScopeDateStart +6 days"));
                }else{
                    $week = $cycle;
                    $year = date('Y');
                    $weeks = date("W", mktime(0, 0, 0, 12, 28, $year));
                    if ($week > $weeks || $week <= 0)
                    {
                        return ['code' => ['0x008015', 'diary']];
                    }
                    if ($week < 10)
                    {
                        $week = '0' . $week;
                    }
                    $planScopeDateStart = strtotime($year . 'W' . $week);
                    $planScopeDateEnd = strtotime('+1 week -1 day',$planScopeDateStart);
                    $planScopeDateStart = date('Y-m-d',$planScopeDateStart);
                    $planScopeDateEnd = date('Y-m-d',$planScopeDateEnd);
                }
                break;
            case 3:
                if(!$kindPermission['month']){
                    return ['code' => ['0x008018', 'diary']];
                }
                if(!$cycle){
                    $beginThismonth=mktime(0,0,0,date('m'),1,date('Y'));
                    //获取本月结束的时间戳
                    $endThismonth=mktime(23,59,59,date('m'),date('t'),date('Y'));
                    $planScopeDateStart = date('Y-m-d',$beginThismonth);
                    $planScopeDateEnd = date('Y-m-d',$endThismonth);
                }else{
                    $y =  date('Y');
                    $m =  $cycle;
                    $d = date('t', strtotime($y.'-'.$m));
                    if ($cycle<1 || $cycle>12)
                    {
                        return ['code' => ['0x008015', 'diary']];
                    }
                    $beginThismonth= strtotime($y.'-'.$m);
                    //获取本月结束的时间戳
                    $endThismonth= mktime(23,59,59,$m,$d,$y);
                    $planScopeDateStart = date('Y-m-d',$beginThismonth);
                    $planScopeDateEnd = date('Y-m-d',$endThismonth);
                }
                break;
            case 4:
                if(!$kindPermission['half_a_year']){
                    return ['code' => ['0x008018', 'diary']];
                }
                $m = date('m');
                $y = date('Y');
                $planScopeDateStart = $y."-01-01";
                $planScopeDateEnd = $y."-06-30";
                break;
            case 5:
                if(!$kindPermission['year_end']){
                    return ['code' => ['0x008018', 'diary']];
                }
                $y = date('Y');
                $planScopeDateStart = $y."-01-01";
                $planScopeDateEnd = $y."-12-31";
                break;
            case 6:
                $year = date('Y');
                $month = date('m');
                if(!$cycle){
                    if($month <= 3){
                        $planScopeDateStart = $year."-01-01";
                        $planScopeDateEnd = $year."-03-31";
                        $type = 'first';
                    }else if($month <= 6){
                        $planScopeDateStart = $year."-04-01";
                        $planScopeDateEnd = $year."-06-30";
                        $type = 'second';
                    }else if($month <= 9){
                        $planScopeDateStart = $year."-07-01";
                        $planScopeDateEnd = $year."-09-30";
                        $type = 'third';
                    }else if($month <= 12){
                        $planScopeDateStart = $year."-10-01";
                        $planScopeDateEnd = $year."-12-31";
                        $type = 'fourth';
                    }
                }else{
                    if ($cycle<1 || $cycle>4)
                    {
                        return ['code' => ['0x008015', 'diary']];
                    }
                    $type = '';
                    switch ($cycle) {
                        case 1:
                        $type = 'first';
                        break;
                        case 2:
                        $type = 'second';
                        break;
                        case 3:
                        $type = 'third';
                        break;
                        case 4:
                        $type = 'fourth';
                        break;
                    }
                    if($cycle == 1) {
                        $planScopeDateStart = $year."-01-01";
                        $planScopeDateEnd = $year."-03-31";
                    } else if($cycle == 2) {
                        $planScopeDateStart = $year."-04-01";
                        $planScopeDateEnd = $year."-06-30";
                    } else if($cycle == 3) {
                        $planScopeDateStart = $year."-07-01";
                        $planScopeDateEnd = $year."-09-30";
                    } else if($cycle == 4) {
                        $planScopeDateStart = $year."-10-01";
                        $planScopeDateEnd = $year."-12-31";
                    }
                }
                if(!$kindPermission[$type.'_quarter']){
                        return ['code' => ['0x008018', 'diary']];
                    }
                break;
        };
        if($planScopeDateStart > date('Y-m-d')){
            return ['code' => ['0x008013', 'diary']];
        }
        $searchParam = [
            "search" => [
                "plan_kind" => [$kindId],
                "diary_date" => [$diaryDate],
                "user_id" => [$userId],
                "plan_scope_date_start" => [$planScopeDateStart],
                "plan_scope_date_end" => [$planScopeDateEnd],
            ],
            "returntype" => "first",
        ];
        $diaryId = "";
        $diaryPlanInfo = app($this->diaryRepository)->getDiaryPlanInfo($searchParam);
        if(isset($diaryPlanInfo["diary_id"])){
            $diaryId = $diaryPlanInfo["diary_id"];
        }
        // 判断是编辑还是创建--new
        if($diaryId) {
            if ($diaryInfo = app($this->diaryRepository)->getDetail($diaryId)) {
                $updateData = [
                    "diary_content" => $diaryContent,
                ];
                $updateData['plan_status'] = "2";
                app($this->diaryRepository)->updateData($updateData, ["diary_id" => [$diaryId]]);
            }
        } else {
            if(isset($kindId) && $kindId == 1 ){
                //微博补交判断是否在允许补交时间范围内
                if(! $this -> canMakeUpBlog($diaryDate)){
                    return ['code' => ['0x008007', 'diary']];
                }
            }
            $insertData = [
                'user_id'               => $userId,
                'diary_content'         => $diaryContent,
                'diary_date'            => $diaryDate,
                'plan_kind'             => $kindId,
                'plan_template'         => 1,
                'plan_scope_date_start' => $planScopeDateStart,
                'plan_scope_date_end'   => $planScopeDateEnd,
                'plan_status'           => "2",
            ];
            // 判断重复！
            if($result = app($this->diaryRepository)->insertData($insertData)) {
               $diaryId = $result->diary_id;
            }
        }
        if ($diaryId > 0) {
            if(isset($param['attachment_id'])) {
                app($this->attachmentService)->attachmentRelation("diary", $diaryId, $param['attachment_id']);
            }
        }
        if($diaryId){
            return [
                'status' => 1,
                'dataForLog' => [
                    [
                        'table_to' => 'diary',
                        'field_to' => 'diary_id',
                        'id_to' => $diaryId,
                    ],
    
                ],
            ];
        }else{
            return ['code' => ['0x008002', 'diary']];
        }
        
    }

    public function quickSave($data)
    {
        $method = isset($data['method'])?$data['method']:'get';
        $key = isset($data['key'])?$data['key']:'';
        $value = isset($data['value'])?$data['value']:'';
        if($key){
            if($method == "get"){
                return Redis::hget('diary', $key);
            }else if($method == "post"){
                // if(strstr($key,'first')){
                //     $value = strip_tags($value);
                //     str_replace('&nbsp;',"",$value);
                // }
                return Redis::hset('diary', $key, $value);
            }else if($method == "delete"){
                return Redis::hdel('diary', $key);
            }
        }
    }
     /**
     * 获取微博提醒时机
     * @return array
     */
    public function getDiaryRemind()
    {
        if(!Schema::hasTable('diary_permission')){
            return [];
        }
       
        //获取今天写微博的人
        $search = [
            'diary_date' => [date('Y-m-d')],
            'plan_kind' => [1]
        ];
        $diaryUsers = [];
        $diaryUser = app($this->diaryRepository)->diaryReportList($search);
        $diaryUsers = array_column($diaryUser,'user_id');
        $result = app($this->diaryPermissionRepository)->getAllPermission();
        $securityData = [];
        foreach ($result as $key => $value) {
            $securityData[$value['param_key']] = $value['param_value'];
        }
        $display_holiday_microblog = isset($securityData['display_holiday_microblog']) ? (int) $securityData['display_holiday_microblog'] : 1;
        // //非工作日不提醒
        // $remind = 1;
        // if($display_holiday_microblog == 0){
        //     $allRestDays = $this->getUserRestDateByMonth(date('Y'), date('m'), own('user_id'));
        //     //今天是否提醒
        //     $remind = in_array(date('Y-m-d'),$allRestDays)?0:1;
        // }
        $users =  explode(",",app($this->userService)->getAllUserIdString());
        $menuUser = app($this->userMenuService)->getMenuRoleUserbyMenuId(184);
        $userIds = array_intersect($users,$menuUser);
        $userShiftsMap = app($this->attendanceService)->schedulingMapWithUserIdsByDate(date('Y-m-d'), $userIds);
        $workUser = [];
        foreach ($userIds as $userId) {
            if (isset($userShiftsMap[$userId]) && $userShiftsMap[$userId] && !in_array($userId,$diaryUsers)) {
                $workUser[] = $userId;
            }
        }
        $securityData = array(
            'is_auto' => isset($securityData['is_auto']) ?  $securityData['is_auto'] : '',
            'remind_time' => isset($securityData['remind_time']) ?  $securityData['remind_time'] : '',
            'work_user' => $workUser
        );
        return $securityData;
    }
}