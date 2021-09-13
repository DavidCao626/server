<?php
namespace App\EofficeApp\User\Services;

use App\EofficeApp\Base\BaseService;
use App\EofficeApp\Elastic\Services\MessageQueue\ElasticsearchProducer;
use DB;
use Eoffice;
use Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use QrCode;
use Schema;
use Queue;
use Lang;
use App\Caches\CacheCenter;
use App\Exceptions\ErrorMessage;
use App\Jobs\SyncPersonnelFilesJob;
use Illuminate\Support\Arr;
use App\EofficeApp\LogCenter\Facades\LogCenter;
/**
 * 用户service类，用来调用所需资源，提供和用户有关的服务。
 *
 * @author 丁鹏
 *
 * @since  2015-10-16 创建
 */
class UserService extends BaseService
{

    const USER_SUBORDINATE_REDIS_KEY = 'user:subordinate';// 用户上下级缓存rediskey
    const INVALID_USER_IDS_CACHE_KEY = 'user:invalidUserIds';//用户无效用户id缓存键名
    const USER_ACCOUNT_CACHE_KEY = 'user:user_accounts';
    public $workWechatService;

    public function __construct()
    {
        parent::__construct();
        $this->userSuperiorRepository = 'App\EofficeApp\Role\Repositories\UserSuperiorRepository';
        $this->userAllSuperiorRepository = 'App\EofficeApp\User\Repositories\UserSuperiorRepository';
        $this->userRepository = 'App\EofficeApp\User\Repositories\UserRepository';
        $this->userStatusRepository = 'App\EofficeApp\User\Repositories\UserStatusRepository';
        $this->userInfoRepository = 'App\EofficeApp\User\Repositories\UserInfoRepository';
        $this->userSystemInfoRepository = 'App\EofficeApp\User\Repositories\UserSystemInfoRepository';
        $this->userQuickRegisterRepository = 'App\EofficeApp\User\Repositories\UserQuickRegisterRepository';
        $this->roleRepository = 'App\EofficeApp\Role\Repositories\RoleRepository';
        $this->roleCommunicateRepository = 'App\EofficeApp\Role\Repositories\RoleCommunicateRepository';
        $this->departmentRepository = 'App\EofficeApp\System\Department\Repositories\DepartmentRepository';
        $this->schedulingRepository = 'App\EofficeApp\Attendance\Repositories\AttendanceSchedulingRepository';
        $this->calendarAttentionRepository = 'App\EofficeApp\Calendar\Repositories\CalendarAttentionRepository';
        $this->personnelFilesRepository = 'App\EofficeApp\PersonnelFiles\Repositories\PersonnelFilesRepository';
        $this->personnelFilesPermission = 'App\EofficeApp\PersonnelFiles\Services\PersonnelFilesPermission';
        $this->userRoleRepository = 'App\EofficeApp\Role\Repositories\UserRoleRepository';
        $this->userSecextRepository = 'App\EofficeApp\User\Repositories\UserSecextRepository';
        $this->tokendcRepository = 'App\EofficeApp\User\Repositories\TokendcRepository';
        $this->langService = 'App\EofficeApp\Lang\Services\LangService';
        $this->companyService = 'App\EofficeApp\System\Company\Services\CompanyService';
        $this->systemComboboxService = 'App\EofficeApp\System\Combobox\Services\SystemComboboxService';
        $this->userMenuService = 'App\EofficeApp\Menu\Services\UserMenuService';
        $this->personnelFilesService = 'App\EofficeApp\PersonnelFiles\Services\PersonnelFilesService';
        $this->cooperationService = 'App\EofficeApp\Cooperation\Services\CooperationService';
        $this->attendanceSettingService = 'App\EofficeApp\Attendance\Services\AttendanceSettingService';
        $this->departmentService = 'App\EofficeApp\System\Department\Services\DepartmentService';
        $this->empowerService = 'App\EofficeApp\Empower\Services\EmpowerService';
        $this->roleService = 'App\EofficeApp\Role\Services\RoleService';
        $this->systemSecurityService  = 'App\EofficeApp\System\Security\Services\SystemSecurityService';
        $this->diaryService  = 'App\EofficeApp\Diary\Services\DiaryService';
        $this->userSocketRepository  = 'App\EofficeApp\User\Repositories\UserSocketRepository';
        $this->portalService  = 'App\EofficeApp\Portal\Services\PortalService';
        $this->attachmentService = 'App\EofficeApp\Attachment\Services\AttachmentService';
        $this->userSystemInfoEntity = 'App\EofficeApp\User\Entities\UserSystemInfoEntity';
        $this->userSelectField = [
                            'user_position'     => 'USER_POSITION',
                            'user_area'         => 'USER_AREA',
                            'user_city'         => 'USER_CITY',
                            'user_workplace'    => 'USER_WORKPLACE',
                            'user_job_category' => 'USER_JOB_CATEGORY'
                        ];
        $this->workWechatService = 'App\EofficeApp\WorkWechat\Services\WorkWechatService';
        $this->systemComboboxFieldRepository = 'App\EofficeApp\System\Combobox\Repositories\SystemComboboxFieldRepository';
        $this->systemComboboxRepository = 'App\EofficeApp\System\Combobox\Repositories\SystemComboboxRepository';
    }

    /**
     * 用户状态管理--获取用户状态列表
     *
     * @author 丁鹏
     *
     * @since  2015-10-16 创建此类
     *
     * @return array      返回结果
     */
    public function userStatusList($param)
    {
        $param = $this->parseParams($param);
        $langTable = app($this->langService)->getLangTable(null);
        $param['lang_table'] = $langTable;
        $userStatusResult = $this->response(app($this->userStatusRepository), 'userStatusTotalRepository', 'userStatusListRepository', $param);
        if (isset($userStatusResult['list']) && !empty($userStatusResult['list'])) {
            foreach ($userStatusResult['list'] as $key => $value) {
                $userStatusResult['list'][$key]['status_name'] = mulit_trans_dynamic("user_status.status_name.user_status_" .$value['status_id']);
            }
        }
        return $userStatusResult;
    }
    public function getUserDynamicCodeAuthStatus($userAccount)
    {
        if($user = app($this->userRepository)->getUserByAccount(urldecode($userAccount))) {
            if($info = app($this->userSecextRepository)->getOneDataByUserId($user->user_id)){
                if($info->is_dynamic_code == 1){
                    return true;
                }
            }
        }

        return false;
    }
    public function getUserDynamicCodeInfoByUserId($userId)
    {
        return app($this->userSecextRepository)->getOneDataByUserId($userId);
    }
    /**
     * 用户状态管理--新建用户状态
     *
     * @author 丁鹏
     *
     * @param  array $param [description]
     *
     * @since  2015-10-16 创建
     *
     * @return array     返回结果
     */
    public function userStatusCreate($param = [])
    {
        $insertStatusData = app($this->userStatusRepository)->insertData(["status_name" => '']);
        app($this->userStatusRepository)->updateData(['status_name' => 'user_status_'.$insertStatusData->status_id], ['status_id' => $insertStatusData->status_id]);
        $menuNameLang = isset($param['status_name_lang']) ? $param['status_name_lang'] : '';
        if (!empty($menuNameLang) && is_array($menuNameLang)) {
            foreach ($menuNameLang as $key => $value) {
                $langData = [
                    'table'      => 'user_status',
                    'column'     => 'status_name',
                    'lang_key'   => "user_status_" . $insertStatusData->status_id,
                    'lang_value' => $value,
                ];
                $local = $key;
                app($this->langService)->addDynamicLang($langData, $local);
            }
        }else{
            $langData = [
                'table'      => 'user_status',
                'column'     => 'status_name',
                'lang_key'   => "user_status_" . $insertStatusData->status_id,
                'lang_value' => $param["status_name"],
            ];
            app($this->langService)->addDynamicLang($langData);
        }
        return $insertStatusData;
    }

    /**
     * 用户状态管理--编辑用户状态
     *
     * @author 丁鹏
     *
     * @param  array $param [description]
     *
     * @since  2015-10-16 创建
     *
     * @return array   返回结果
     */
    public function userStatusEdit($param, $statusId)
    {
        $menuNameLang = isset($param['status_name_lang']) ? $param['status_name_lang'] : '';
        if (!empty($menuNameLang) && is_array($menuNameLang)) {
            foreach ($menuNameLang as $key => $value) {
                $langData = [
                    'table'      => 'user_status',
                    'column'     => 'status_name',
                    'lang_key'   => "user_status_" . $statusId,
                    'lang_value' => $value,
                ];
                $local = $key;
                app($this->langService)->addDynamicLang($langData, $local);
            }
        }else{
            $langData = [
                'table'      => 'user_status',
                'column'     => 'status_name',
                'lang_key'   => "user_status_" . $statusId,
                'lang_value' => $param["status_name"],
            ];
            app($this->langService)->addDynamicLang($langData);
        }
        return true;
    }

    /**
     * 用户状态管理--删除用户状态
     *
     * @author 丁鹏
     *
     * @param  array $param [description]
     *
     * @since  2015-10-16 创建
     *
     * @return array     返回结果
     */
    public function userStatusDelete($statusId)
    {
        // 验证用户状态没有被占用
        if (app($this->userStatusRepository)->getDetail($statusId)) {
            if (app($this->userStatusRepository)->userCountByUserStatusRepository($statusId)->count()) {
                // 被占用
                return ['code' => ['0x005001', 'user']];
            }
            $deleteStatusResult = app($this->userStatusRepository)->deleteById($statusId);
            remove_dynamic_langs('user_status_'.$statusId);
            return $deleteStatusResult;
        } else {
            // 当前状态不存在
            return ['code' => ['0x005002', 'user']];
        }
    }

    /**
     * 用户状态管理--获取用户状态详情
     *
     * @author 丁鹏
     *
     * @param  array $param [description]
     *
     * @since  2015-10-16 创建
     *
     * @return array     返回结果
     */
    public function userStatusDetail($param = [])
    {
        $statusDetail = app($this->userStatusRepository)->getDetail($param["status_id"]);
        if (!empty($statusDetail)) {
            $statusDetail->status_name = mulit_trans_dynamic("user_status.status_name.user_status". '_' .$statusDetail->status_id);
            $statusDetail->status_name_lang = app($this->langService)->transEffectLangs("user_status.status_name.user_status_" . $statusDetail->status_id);
            return $statusDetail;
        } else {
            return ['code' => ['0x005002', 'user']];
        }
    }

    /**
     * 用户管理--导出，获取所有用户所有数据
     *
     * @author 丁鹏
     *
     * @since  2015-10-16 创建
     *
     * @return arr返回结果
     */
    public function exportUser($param)
    {
        $loginUserInfo = $param['user_info']['user_info'] ?? [];
        $param = $this->parseParams($param);
        if (isset($param['search']['dept_id']) && $param['search']['dept_id'][0] == '0') {
            unset($param['search']['dept_id']);
        }
        $param['include_supervisor'] = 1;
        $hasMobilEmpower = app($this->empowerService)->checkMobileEmpowerAvailability();

        $userList = $this->userSystemList($param)['list'];
        // $header = [
        //     "user_name" => ['data' => trans("user.user_name"), 'style' => ['width' => '15']],// 真实姓名
        //     "user_accounts" => ['data' => trans("user.user_accounts"), 'style' => ['width' => '15']],// 用户名
        //     "list_number" => ['data' => trans("user.list_number"), 'style' => ['width' => '10']],// 序号
        //     "user_job_number" => ['data' => trans("user.user_job_number"), 'style' => ['width' => '15']],// 工号
        //     "role_name" => ['data' => trans("user.role_name"), 'style' => ['width' => '30']],// 角色
        //     "dept_name" => ['data' => trans("user.dept_name"), 'style' => ['width' => '25']],// 部门
        //     "sex|sexFilter" => ['data' => trans("user.0x005031"), 'style' => ['width' => '6']],// 性别
        //     "birthday" => ['data' => trans("user.0x005032"), 'style' => ['width' => '15']],// 生日
        //     "dept_phone_number" => ['data' => trans("user.0x005033"), 'style' => ['width' => '15']],// 公司电话
        //     "faxes" => ['data' => trans("user.0x005034"), 'style' => ['width' => '15']],// 公司传真
        //     "home_address" => ['data' => trans("user.0x005035"), 'style' => ['width' => '30']],// 家庭地址
        //     "home_zip_code" => ['data' => trans("user.0x005036"), 'style' => ['width' => '15']],// 家庭邮编
        //     "home_phone_number" => ['data' => trans("user.0x005037"), 'style' => ['width' => '15']],// 家庭电话
        //     "phone_number" => ['data' => trans("user.0x005038"), 'style' => ['width' => '15']],// 手机
        //     "weixin" => ['data' => trans("user.0x005039"), 'style' => ['width' => '15']],// 微信号
        //     "oicq_no" => ['data' => trans("user.0x005040"), 'style' => ['width' => '15']],// QQ号
        //     "email" => ['data' => trans("user.0x005041"), 'style' => ['width' => '30']],// 邮箱
        //     "attendance_scheduling" => ['data' => trans("user.0x005042"), 'style' => ['width' => '15']],// 考勤排班类型
        //     "user_status|workStatus" => ['data' => trans("user.user_status"), 'style' => ['width' => '15']],// 用户状态
        //     "user_superior" => ['data' => trans("user.0x005043"), 'style' => ['width' => '15']],// 上级
        //     "user_subordinate" => ['data' => trans("user.0x005044"), 'style' => ['width' => '50']],// 下级
        //     "notes" => ['data' => trans("user.0x005045"), 'style' => ['width' => '50']],// 备注'
        //     "user_position" => ['data' => trans("user.0x005066"), 'style' => ['width' => '15']],// 职位
        //     "user_area" => ['data' => trans("user.0x005068"), 'style' => ['width' => '15']],// 区域
        //     "user_city" => ['data' => trans("user.0x005071"), 'style' => ['width' => '15']],// 城市
        //     "user_workplace" => ['data' => trans("user.0x005074"), 'style' => ['width' => '15']],// 职场
        //     "user_job_category" => ['data' => trans("user.0x005077"), 'style' => ['width' => '15']],// 岗位类别
        // ];
        // 'style' => ['width' => '15', 'height' => '25', 'backgroundColor' => '1B9BCA', 'fontColor' => 'FFFFF', 'fontSize' => '14', 'fontWeight' => 'bold']
        $header = [
                    'parent_dept_name' => ['data' => trans('user.sup_dept_name'), 'style' => ['width' => '30', 'height' => '25', 'backgroundColor' => '1B9BCA', 'fontColor' => 'FFFFF', 'fontSize' => '14']],

                    'dept_name' => ['data' => trans('user.belong_dept_name'), 'style' => ['width' => '30', 'height' => '25', 'backgroundColor' => '1B9BCA', 'fontColor' => 'FFFFF', 'fontSize' => '14']],
                    'user_name' => ['data' => trans("user.user_name").trans("user.0x005047"), 'style' => ['width' => '30', 'height' => '25', 'backgroundColor' => '1B9BCA', 'fontColor' => 'FFFFF', 'fontSize' => '14']],// 真实姓名(必填)
                    'user_accounts' => ['data' => trans("user.user_accounts").trans('user.0x005085'), 'style' => ['width' => '30', 'height' => '25', 'backgroundColor' => '1B9BCA', 'fontColor' => 'FFFFF', 'fontSize' => '14']],// 用户名(必填)
                    'user_password' => ['data' => trans("user.0x005048"), 'style' => ['width' => '30', 'height' => '25', 'backgroundColor' => '1B9BCA', 'fontColor' => 'FFFFF', 'fontSize' => '14']],// 密码(为空时不更新密码)
                    'list_number' => ['data' => trans("user.list_number"), 'style' => ['width' => '30', 'height' => '25', 'backgroundColor' => '1B9BCA', 'fontColor' => 'FFFFF', 'fontSize' => '14']],// 序号
                    'user_job_number' => ['data' => trans("user.user_job_number"), 'style' => ['width' => '30', 'height' => '25', 'backgroundColor' => '1B9BCA', 'fontColor' => 'FFFFF', 'fontSize' => '14']],// 工号
                    'role_name' => ['data' => trans('user.role_name_import'), 'style' => ['width' => '30', 'height' => '25', 'backgroundColor' => '1B9BCA', 'fontColor' => 'FFFFF', 'fontSize' => '14']],// 角色ID(必填)
                    'user_position' => ['data' => trans('user.user_position_name'), 'style' => ['width' => '30', 'height' => '25', 'backgroundColor' => '1B9BCA', 'fontColor' => 'FFFFF', 'fontSize' => '14']],// 职位ID
                    'sex|sexFilter' => ['data' => trans('user.sex_name'), 'style' => ['width' => '30', 'height' => '25', 'backgroundColor' => '1B9BCA', 'fontColor' => 'FFFFF', 'fontSize' => '14']],// 性别ID(必填)
                    'birthday' => ['data' => trans("user.0x005032"), 'style' => ['width' => '30', 'height' => '25', 'backgroundColor' => '1B9BCA', 'fontColor' => 'FFFFF', 'fontSize' => '14']],// 生日
                    'dept_phone_number' => ['data' => trans("user.0x005033"), 'style' => ['width' => '30', 'height' => '25', 'backgroundColor' => '1B9BCA', 'fontColor' => 'FFFFF', 'fontSize' => '14']],// 公司电话
                    'faxes' => ['data' => trans("user.0x005034"), 'style' => ['width' => '30', 'height' => '25', 'backgroundColor' => '1B9BCA', 'fontColor' => 'FFFFF', 'fontSize' => '14']],// 公司传真
                    'home_address' => ['data' => trans("user.0x005035"), 'style' => ['width' => '30', 'height' => '25', 'backgroundColor' => '1B9BCA', 'fontColor' => 'FFFFF', 'fontSize' => '14']],// 家庭地址
                    'home_zip_code' => ['data' => trans("user.0x005036"), 'style' => ['width' => '30', 'height' => '25', 'backgroundColor' => '1B9BCA', 'fontColor' => 'FFFFF', 'fontSize' => '14']],// 家庭邮编
                    'home_phone_number' => ['data' => trans("user.0x005037"), 'style' => ['width' => '30', 'height' => '25', 'backgroundColor' => '1B9BCA', 'fontColor' => 'FFFFF', 'fontSize' => '14']],// 家庭电话
                    'phone_number' => ['data' => trans("user.0x005038"), 'style' => ['width' => '30', 'height' => '25', 'backgroundColor' => '1B9BCA', 'fontColor' => 'FFFFF', 'fontSize' => '14']],// 手机
                    'weixin' => ['data' => trans("user.0x005039"), 'style' => ['width' => '30', 'height' => '25', 'backgroundColor' => '1B9BCA', 'fontColor' => 'FFFFF', 'fontSize' => '14']],// 微信号
                    'oicq_no' => ['data' => trans("user.0x005040"), 'style' => ['width' => '30', 'height' => '25', 'backgroundColor' => '1B9BCA', 'fontColor' => 'FFFFF', 'fontSize' => '14']],// QQ号
                    'email' => ['data' => trans("user.0x005041"), 'style' => ['width' => '30', 'height' => '25', 'backgroundColor' => '1B9BCA', 'fontColor' => 'FFFFF', 'fontSize' => '14']],// 邮箱
                    'attendance_scheduling' => ['data' => trans('user.attendance_scheduling_name'), 'style' => ['width' => '30', 'height' => '25', 'backgroundColor' => '1B9BCA', 'fontColor' => 'FFFFF', 'fontSize' => '14']],// 考勤排班类型ID
                    'user_status|workStatus' => ['data' => trans('user.user_status_name'), 'style' => ['width' => '30', 'height' => '25', 'backgroundColor' => '1B9BCA', 'fontColor' => 'FFFFF', 'fontSize' => '14']],// 用户状态ID(必填)
                    'user_area' => ['data' => trans('user.user_area_name'), 'style' => ['width' => '30', 'height' => '25', 'backgroundColor' => '1B9BCA', 'fontColor' => 'FFFFF', 'fontSize' => '14']],// 区域ID
                    'user_city' => ['data' => trans('user.user_city_name'), 'style' => ['width' => '30', 'height' => '25', 'backgroundColor' => '1B9BCA', 'fontColor' => 'FFFFF', 'fontSize' => '14']],// 城市ID
                    'user_workplace' => ['data' => trans('user.user_workplace_name'), 'style' => ['width' => '30', 'height' => '25', 'backgroundColor' => '1B9BCA', 'fontColor' => 'FFFFF', 'fontSize' => '14']],// 职场ID
                    'user_job_category' => ['data' => trans('user.user_job_category_name'), 'style' => ['width' => '30', 'height' => '25', 'backgroundColor' => '1B9BCA', 'fontColor' => 'FFFFF', 'fontSize' => '14']],// 岗位类别ID
                    'user_superior'  => ['data' => trans('user.all_user_superior_import'), 'style' => ['width' => '30', 'height' => '25', 'backgroundColor' => '1B9BCA', 'fontColor' => 'FFFFF', 'fontSize' => '14']]
                ];
        if ($hasMobilEmpower) {
            $header['wap_allow'] = ['data' => trans('user.wap_allow_name'), 'style' => ['width' => '30', 'height' => '25', 'backgroundColor' => '1B9BCA', 'fontColor' => 'FFFFF', 'fontSize' => '14']];//手机访问
        }
        $data = [];
        $loginUserMenus = $loginUserInfo['menus']['menu'] ?? [];
        $permissionModule = app($this->empowerService)->getPermissionModules();
        if (empty($permissionModule)) {
            $checkAttendanceMenu = false;
        } else {
            $checkAttendanceMenu = in_array('32', $permissionModule);
        }
        // 如果没有考勤模块排班设置菜单，不导出考勤数据
        if (!$checkAttendanceMenu) {
            unset($header['attendance_scheduling']);
        } else {
            $dutyTypeInfo = app($this->schedulingRepository)->getSchedulingList(['page' => 0]);
            $dutyTypeInfoArray = array();
            if (!empty($dutyTypeInfo)) {
                $dutyTypeInfo = $dutyTypeInfo->toArray();
                foreach ($dutyTypeInfo as $key => $value) {
                    $dutyTypeInfoArray[$value['scheduling_id']] = $value['scheduling_name'];
                }
            }
        }

        $userPositionData = app($this->systemComboboxService)->getComboboxFieldByIdentify('USER_POSITION');
        $userAreaData = app($this->systemComboboxService)->getComboboxFieldByIdentify('USER_AREA');
        $userCityData = app($this->systemComboboxService)->getComboboxFieldByIdentify('USER_CITY');
        $userWorkplaceData = app($this->systemComboboxService)->getComboboxFieldByIdentify('USER_WORKPLACE');
        $userJobCategoryData = app($this->systemComboboxService)->getComboboxFieldByIdentify('USER_JOB_CATEGORY');

        foreach ($userList as $k => $v) {
            $roleName = [];
            if (!empty($v['user_has_many_role'])) {
                foreach ($v['user_has_many_role'] as $roleKey => $roleValue) {
                    $roleName[] = $roleValue['has_one_role']['role_name'];
                }
            }
            if ($v['user_has_one_info']['birthday'] == '0000-00-00') {
                $v['user_has_one_info']['birthday'] = '';
            }
            $userSuperiorStr = "";
            if (!empty($v['user_has_many_superior'])) {
                foreach ($v['user_has_many_superior'] as $superiorKey => $superiorValue) {
                    if (isset($superiorValue['superior_has_one_user']['user_name']) && !empty($superiorValue['superior_has_one_user']['user_name'])) {
                        $userSuperiorStr .= $superiorValue['superior_has_one_user']['user_name'] . ',';
                    }
                }
            }
            $userSubordinateStr = "";
            if (!empty($v['user_has_many_subordinate'])) {
                foreach ($v['user_has_many_subordinate'] as $superiorKey => $superiorValue) {
                    if (isset($superiorValue['subordinate_has_one_user']['user_name']) && !empty($superiorValue['subordinate_has_one_user']['user_name'])) {
                        $userSubordinateStr .= $superiorValue['subordinate_has_one_user']['user_name'] . ',';
                    }
                }
            }
            $data[$k]['list_number'] = $v['list_number'];
            $data[$k]['user_name'] = $v['user_name'];
            $data[$k]['user_accounts'] = $v['user_accounts'];
            $data[$k]['user_job_number'] = $v['user_job_number'];
            $data[$k]['role_name'] = join(",", $roleName);
            $data[$k]['dept_name'] = $v['user_has_one_system_info']['user_system_info_belongs_to_department']['dept_name'];
            $data[$k]['parent_dept_name'] = $this->getParentDeparentNameBySubDeptId($v['user_has_one_system_info']['user_system_info_belongs_to_department']['dept_id']);
            // $data[$k]['dept_name'] = $this->getDeptPathByDeptId($v['user_has_one_system_info']['user_system_info_belongs_to_department']['dept_id']);
            $data[$k]['sex'] = $v['user_has_one_info']['sex'];
            $data[$k]['birthday'] = $v['user_has_one_info']['birthday'];
            $data[$k]['dept_phone_number'] = $v['user_has_one_info']['dept_phone_number'];
            $data[$k]['faxes'] = $v['user_has_one_info']['faxes'];
            $data[$k]['home_address'] = $v['user_has_one_info']['home_address'];
            $data[$k]['home_zip_code'] = $v['user_has_one_info']['home_zip_code'];
            $data[$k]['home_phone_number'] = $v['user_has_one_info']['home_phone_number'];
            $data[$k]['phone_number'] = $v['user_has_one_info']['phone_number'];
            $data[$k]['weixin'] = $v['user_has_one_info']['weixin'];
            $data[$k]['oicq_no'] = $v['user_has_one_info']['oicq_no'];
            $data[$k]['email'] = $v['user_has_one_info']['email'];
            if ($checkAttendanceMenu) {
                $data[$k]['attendance_scheduling'] = isset($v['user_has_one_attendance_scheduling_info']['scheduling_id']) && isset($dutyTypeInfoArray[$v['user_has_one_attendance_scheduling_info']['scheduling_id']]) ? $dutyTypeInfoArray[$v['user_has_one_attendance_scheduling_info']['scheduling_id']] : '';
            }
            $data[$k]['user_status'] = $v['user_has_one_system_info']['user_status'];
            $data[$k]['user_superior'] = $userSuperiorStr ? trim(implode(',', array_unique(explode(',', $userSuperiorStr))), ',') : '';
            $data[$k]['user_subordinate'] = $userSubordinateStr ? trim(implode(',', array_unique(explode(',', $userSubordinateStr))), ',') : '';
            $data[$k]['notes'] = $v['user_has_one_info']['notes'];
            $data[$k]['user_position'] = !empty($v['user_position']) && isset($userPositionData[$v['user_position']]) ? $userPositionData[$v['user_position']] : '';
            $data[$k]['user_area'] = !empty($v['user_area']) && isset($userAreaData[$v['user_area']]) ? $userAreaData[$v['user_area']] : '';
            $data[$k]['user_city'] = !empty($v['user_city']) && isset($userCityData[$v['user_city']]) ? $userCityData[$v['user_city']] : '';
            $data[$k]['user_workplace'] = !empty($v['user_workplace']) && isset($userWorkplaceData[$v['user_workplace']]) ? $userWorkplaceData[$v['user_workplace']] : '';
            $data[$k]['user_job_category'] = !empty($v['user_job_category']) && isset($userJobCategoryData[$v['user_job_category']]) ? $userJobCategoryData[$v['user_job_category']] : '';

            if ($hasMobilEmpower) {
                $data[$k]['wap_allow'] = $v['user_has_one_system_info']['wap_allow'] ? trans('user.allow') : trans('user.not_allow');
            }
        }
        return compact('header', 'data');
    }

    /**
     * 获取导入用户字段
     *
     * @return array 查询结果
     *
     * @author miaochenchen
     *
     * @since  2016-06-23
     */
    function getImportUserFields($loginUserinfo=[])
    {
        $allExportData = [];
        $allUserData = $this->getAllUserFields($loginUserinfo);
        $useTempData = [];
        if ($allUserData) {
            foreach ($allUserData as $k => $v) {
                // $useTempData[$k]['user_id'] = $v['base_user_id'];
                $useTempData[$k]['user_name'] = $v['base_user_name'];
            }
        }
        $roleData = app($this->roleService)->getRoleList();
        if ($roleData) {
            $roleTempData = [];
            foreach ($roleData['list'] as $k => $v) {
                // $roleTempData[$k]['role_id'] = $v['role_id'];
                $roleTempData[$k]['role_name'] = $v['role_name'];
            }
        }
        $deptData = app($this->departmentService)->listDept([]);
        $deptTempData = [];
        if ($deptData) {
            foreach ($deptData['list'] as $k => $v) {
                $deptTempData[$k]['parent_dept_name'] = $this->getParentDeparentNameBySubDeptId($v['dept_id']);
                $deptTempData[$k]['dept_name'] = $v['dept_name'];
            }
        }
        $loginUserMenus = $loginUserinfo['menus']['menu'] ?? [];
        $permissionModule = app($this->empowerService)->getPermissionModules();
        if (empty($permissionModule)) {
            $checkAttendanceMenu = false;
        } else {
            $checkAttendanceMenu = in_array('32', $permissionModule);
        }
        // 如果没有考勤模块排班设置菜单，不导入考勤数据
        $schedulingTempData = [];
        if ($checkAttendanceMenu) {
            $schedulingData = app($this->schedulingRepository)->getSchedulingList(['page' => 0])->toArray();
            if ($schedulingData) {
                foreach ($schedulingData as $k => $v) {
                    // $schedulingTempData[$k]['scheduling_id'] = $v['scheduling_id'];
                    $schedulingTempData[$k]['scheduling_name'] = $v['scheduling_name'];
                }
            }
        }
        $userStatusData = $this->userStatusList([]);
        $userStatusTempData = [];
        if ($userStatusData) {
            foreach ($userStatusData['list'] as $k => $v) {
                // $userStatusTempData[$k]['status_id'] = $v['status_id'];
                $userStatusTempData[$k]['status_name'] = $v['status_name'];
            }
        }
        $userPositionData = app($this->systemComboboxService)->getComboboxFieldByIdentify('USER_POSITION');
        $userPositionTempData = [];
        if ($userPositionData) {
            foreach ($userPositionData as $k => $v) {
                // $userPositionTempData[$k]['position_id'] = $k;
                $userPositionTempData[$k]['position_name'] = $v;
            }
        }
        $userAreaData = app($this->systemComboboxService)->getComboboxFieldByIdentify('USER_AREA');
        $userAreaTempData = [];
        if ($userAreaData) {
            foreach ($userAreaData as $k => $v) {
                // $userAreaTempData[$k]['area_id'] = $k;
                $userAreaTempData[$k]['area_name'] = $v;
            }
        }
        $userCityData = app($this->systemComboboxService)->getComboboxFieldByIdentify('USER_CITY');
        $userCityTempData = [];
        if ($userCityData) {
            foreach ($userCityData as $k => $v) {
                // $userCityTempData[$k]['city_id'] = $k;
                $userCityTempData[$k]['city_name'] = $v;
            }
        }
        $userWorkplaceData = app($this->systemComboboxService)->getComboboxFieldByIdentify('USER_WORKPLACE');
        $userWorkplaceTempData = [];
        if ($userWorkplaceData) {
            foreach ($userWorkplaceData as $k => $v) {
                // $userWorkplaceTempData[$k]['workplace_id'] = $k;
                $userWorkplaceTempData[$k]['workplace_name'] = $v;
            }
        }
        $userJobCategoryData = app($this->systemComboboxService)->getComboboxFieldByIdentify('USER_JOB_CATEGORY');
        $userJobCategoryTempData = [];
        if ($userJobCategoryData) {
            foreach ($userJobCategoryData as $k => $v) {
                // $userJobCategoryTempData[$k]['job_category_id'] = $k;
                $userJobCategoryTempData[$k]['job_category_name'] = $v;
            }
        }
        $allExportData = [
            '0' => [
                'sheetName' => trans("user.0x005046"), // 用户导入模板
                'header' => [
                    'parent_dept_id' => ['data' => trans('user.sup_dept_name'), 'style' => ['width' => '15', 'height' => '25', 'backgroundColor' => '1B9BCA', 'fontColor' => 'FFFFF', 'fontSize' => '14', 'fontWeight' => 'bold']],
                    'dept_id' => ['data' => trans('user.belong_dept_name'), 'style' => ['width' => '15', 'height' => '25', 'backgroundColor' => '1B9BCA', 'fontColor' => 'FFFFF', 'fontSize' => '14', 'fontWeight' => 'bold']],
                    'user_name' => ['data' => trans("user.user_name").trans("user.0x005047"), 'style' => ['width' => '30', 'height' => '25', 'backgroundColor' => '1B9BCA', 'fontColor' => 'FFFFF', 'fontSize' => '14', 'fontWeight' => 'bold']],// 真实姓名(必填)
                    'user_accounts' => ['data' => trans("user.user_accounts").trans('user.0x005085'), 'style' => ['width' => '30', 'height' => '25', 'backgroundColor' => '1B9BCA', 'fontColor' => 'FFFFF', 'fontSize' => '14', 'fontWeight' => 'bold']],// 用户名(必填)
                    'user_password' => ['data' => trans("user.0x005048"), 'style' => ['width' => '30', 'height' => '25', 'backgroundColor' => '1B9BCA', 'fontColor' => 'FFFFF', 'fontSize' => '14', 'fontWeight' => 'bold']],// 密码(为空时不更新密码)
                    'list_number' => ['data' => trans("user.list_number"), 'style' => ['width' => '30', 'height' => '25', 'backgroundColor' => '1B9BCA', 'fontColor' => 'FFFFF', 'fontSize' => '14', 'fontWeight' => 'bold']],// 序号
                    'user_job_number' => ['data' => trans("user.user_job_number"), 'style' => ['width' => '30', 'height' => '25', 'backgroundColor' => '1B9BCA', 'fontColor' => 'FFFFF', 'fontSize' => '14', 'fontWeight' => 'bold']],// 工号
                    'role_id_init' => ['data' => trans('user.role_name_import'), 'style' => ['width' => '30', 'height' => '25', 'backgroundColor' => '1B9BCA', 'fontColor' => 'FFFFF', 'fontSize' => '14', 'fontWeight' => 'bold']],// 角色ID(必填)
                    'user_position' => ['data' => trans('user.user_position_name'), 'style' => ['width' => '30', 'height' => '25', 'backgroundColor' => '1B9BCA', 'fontColor' => 'FFFFF', 'fontSize' => '14', 'fontWeight' => 'bold']],// 职位ID
                    'sex' => ['data' => trans('user.sex_name'), 'style' => ['width' => '30', 'height' => '25', 'backgroundColor' => '1B9BCA', 'fontColor' => 'FFFFF', 'fontSize' => '14', 'fontWeight' => 'bold']],// 性别ID(必填)
                    'birthday' => ['data' => trans("user.0x005032"), 'style' => ['width' => '15', 'height' => '25', 'backgroundColor' => '1B9BCA', 'fontColor' => 'FFFFF', 'fontSize' => '14', 'fontWeight' => 'bold']],// 生日
                    'dept_phone_number' => ['data' => trans("user.0x005033"), 'style' => ['width' => '15', 'height' => '25', 'backgroundColor' => '1B9BCA', 'fontColor' => 'FFFFF', 'fontSize' => '14', 'fontWeight' => 'bold']],// 公司电话
                    'faxes' => ['data' => trans("user.0x005034"), 'style' => ['width' => '15', 'height' => '25', 'backgroundColor' => '1B9BCA', 'fontColor' => 'FFFFF', 'fontSize' => '14', 'fontWeight' => 'bold']],// 公司传真
                    'home_address' => ['data' => trans("user.0x005035"), 'style' => ['width' => '15', 'height' => '25', 'backgroundColor' => '1B9BCA', 'fontColor' => 'FFFFF', 'fontSize' => '14', 'fontWeight' => 'bold']],// 家庭地址
                    'home_zip_code' => ['data' => trans("user.0x005036"), 'style' => ['width' => '15', 'height' => '25', 'backgroundColor' => '1B9BCA', 'fontColor' => 'FFFFF', 'fontSize' => '14', 'fontWeight' => 'bold']],// 家庭邮编
                    'home_phone_number' => ['data' => trans("user.0x005037"), 'style' => ['width' => '15', 'height' => '25', 'backgroundColor' => '1B9BCA', 'fontColor' => 'FFFFF', 'fontSize' => '14', 'fontWeight' => 'bold']],// 家庭电话
                    'phone_number' => ['data' => trans("user.0x005038"), 'style' => ['width' => '15', 'height' => '25', 'backgroundColor' => '1B9BCA', 'fontColor' => 'FFFFF', 'fontSize' => '14', 'fontWeight' => 'bold']],// 手机
                    'weixin' => ['data' => trans("user.0x005039"), 'style' => ['width' => '15', 'height' => '25', 'backgroundColor' => '1B9BCA', 'fontColor' => 'FFFFF', 'fontSize' => '14', 'fontWeight' => 'bold']],// 微信号
                    'oicq_no' => ['data' => trans("user.0x005040"), 'style' => ['width' => '15', 'height' => '25', 'backgroundColor' => '1B9BCA', 'fontColor' => 'FFFFF', 'fontSize' => '14', 'fontWeight' => 'bold']],// QQ号
                    'email' => ['data' => trans("user.0x005041"), 'style' => ['width' => '15', 'height' => '25', 'backgroundColor' => '1B9BCA', 'fontColor' => 'FFFFF', 'fontSize' => '14', 'fontWeight' => 'bold']],// 邮箱
                    'attendance_scheduling' => ['data' => trans('user.attendance_scheduling_name'), 'style' => ['width' => '25', 'height' => '25', 'backgroundColor' => '1B9BCA', 'fontColor' => 'FFFFF', 'fontSize' => '14', 'fontWeight' => 'bold']],// 考勤排班类型ID
                    'user_status' => ['data' => trans('user.user_status_name'), 'style' => ['width' => '20', 'height' => '25', 'backgroundColor' => '1B9BCA', 'fontColor' => 'FFFFF', 'fontSize' => '14', 'fontWeight' => 'bold']],// 用户状态ID(必填)
                    'user_area' => ['data' => trans('user.user_area_name'), 'style' => ['width' => '15', 'height' => '25', 'backgroundColor' => '1B9BCA', 'fontColor' => 'FFFFF', 'fontSize' => '14', 'fontWeight' => 'bold']],// 区域ID
                    'user_city' => ['data' => trans('user.user_city_name'), 'style' => ['width' => '15', 'height' => '25', 'backgroundColor' => '1B9BCA', 'fontColor' => 'FFFFF', 'fontSize' => '14', 'fontWeight' => 'bold']],// 城市ID
                    'user_workplace' => ['data' => trans('user.user_workplace_name'), 'style' => ['width' => '15', 'height' => '25', 'backgroundColor' => '1B9BCA', 'fontColor' => 'FFFFF', 'fontSize' => '14', 'fontWeight' => 'bold']],// 职场ID
                    'user_job_category' => ['data' => trans('user.user_job_category_name'), 'style' => ['width' => '15', 'height' => '25', 'backgroundColor' => '1B9BCA', 'fontColor' => 'FFFFF', 'fontSize' => '14', 'fontWeight' => 'bold']],// 岗位类别ID
                    'user_superior_id'  => ['data' => trans('user.all_user_superior_import'), 'style' => ['width' => '20', 'height' => '25', 'backgroundColor' => '1B9BCA', 'fontColor' => 'FFFFF', 'fontSize' => '14', 'fontWeight' => 'bold']],
                    'wap_allow' => ['data' => trans('user.wap_allow_name'), 'style' => ['width' => '15', 'height' => '25', 'backgroundColor' => '1B9BCA', 'fontColor' => 'FFFFF', 'fontSize' => '14', 'fontWeight' => 'bold']],// 手机访问ID,
                ]
            ],
            '1' => [
                'sheetName' => trans("user.0x005055"), // 部门信息
                'header' => [
                    'parent_dept_name' => ['data' => trans('user.parent_department'), 'style' => ['width' => '50']],
                    'dept_name' => ['data' => trans('user.belongs_department'), 'style' => ['width' => '50']]// 部门名称完整路径
                ],
                'data' => $deptTempData
            ],
            '2' => [
                'sheetName' => trans("user.0x005057"), // 角色信息
                'header' => [
                    'role_name' => ['data' => trans("user.0x005058"), 'style' => ['width' => '50']]// 角色名称(备注：多个角色请用英文逗号隔开)
                ],
                'data' => $roleTempData
            ],
            '3' => [
                'sheetName' => trans("user.0x005059"), // 排班信息
                'header' => [
                    'scheduling_name' => ['data' => trans("user.0x005060"), 'style' => ['width' => '20']]// 考勤排班类型名称
                ],
                'data' => $schedulingTempData
            ],
            '4' => [
                'sheetName' => trans("user.0x005061"), // 用户状态信息
                'header' => [
                    'status_name' => ['data' => trans("user.status_name"), 'style' => ['width' => '15']]// 用户状态名称
                ],
                'data' => $userStatusTempData
            ],
            '5' => [
                'sheetName' => trans("user.0x005062"), // 性别信息
                'header' => [
                    'sex_name' => ['data' => trans("user.0x005063"), 'style' => ['width' => '15']]// 性别名称
                ],
                'data' => [
                    '0' => [
                        'sex_name' => trans("user.0x005065")// 女
                    ],
                    '1' => [
                        'sex_name' => trans("user.0x005064")// 男
                    ],
                ]
            ],
            '6' => [
                'sheetName' => trans("user.0x005066"),// 职位,
                'header' => [
                    'position_name' => ['data' => trans("user.0x005067"), 'style' => ['width' => '15']]// 职位名称
                ],
                'data' => $userPositionTempData
            ],
            '7' => [
                'sheetName' => trans("user.0x005068"),// 区域,
                'header' => [
                    'area_name' => ['data' => trans("user.0x005070"), 'style' => ['width' => '15']]// 区域名称
                ],
                'data' => $userAreaTempData
            ],
            '8' => [
                'sheetName' => trans("user.0x005071"),// 城市,
                'header' => [
                    'city_name' => ['data' => trans("user.0x005073"), 'style' => ['width' => '15']]// 城市名称
                ],
                'data' => $userCityTempData
            ],
            '9' => [
                'sheetName' => trans("user.0x005074"),// 职场,
                'header' => [
                    'workplace_name' => ['data' => trans("user.0x005076"), 'style' => ['width' => '15']]// 职场名称
                ],
                'data' => $userWorkplaceTempData
            ],
            '10' => [
                'sheetName' => trans("user.0x005077"),// 岗位类别,
                'header' => [
                    'job_category_name' => ['data' => trans("user.0x005079"), 'style' => ['width' => '15']]// 岗位类别名称
                ],
                'data' => $userJobCategoryTempData
            ],
            '11' => [
                'sheetName' => trans('user.0x005081'), // 手机访问
                'header' => [
                    'wap_name' => ['data' => trans('user.0x005084'), 'style' => ['width' => '15']]// 性别名称
                ],
                'data' => [
                    '0' => [
                        'wap_name' => trans('user.not_allow') // 不允许
                    ],
                    '1' => [
                        'wap_name' => trans('user.allow') // 允许
                    ],
                ]
            ],
            '12' => [
                'sheetName' => trans('user.user_superior'), // 上级信息
                'header' => [
                    'user_name' => ['data' => trans('user.user_superior_name'), 'style' => ['width' => '50']]// 部门名称完整路径
                ],
                'data' => $useTempData
            ],
        ];

        if (!$checkAttendanceMenu) {
            unset($allExportData['0']['header']['attendance_scheduling']);
            unset($allExportData['3']);
        }
        return $allExportData;
    }

    /**
     * 导入用户数据
     *
     * @param  array $data 导入数据
     * @param  array $param 导入条件
     *
     * @return array 导入结果
     *
     * @author miaochenchen
     *
     * @since  2016-06-23
     */
    function importUser($data, $param)
    {
        $locale = Lang::getLocale();
        $loginUserId = isset($param['user_info']['user_id']) ? $param['user_info']['user_id'] : '';
        $loginUserInfo = $param['user_info'] ?? [];
        $info = [
            'total' => count($data),
            'success' => 0,
            'error' => 0,
        ];
        if ($param['type'] == 3) {
            //新增数据并清除原有数据 先清除原有数据 除了admin 软删除
            $deletedData = ['deleted_at' => date('Y-m-d H:i:s')];
            DB::table('user')->where('user_id', '!=', 'admin')->update($deletedData);
            DB::table('user_info')->where('user_id', '!=', 'admin')->update($deletedData);
            DB::table('user_system_info')->where('user_id', '!=', 'admin')->update($deletedData);
            DB::table('user_role')->where('user_id', '!=', 'admin')->update($deletedData);
            DB::table('user_menu')->where('user_id', '!=', 'admin')->update($deletedData);
            DB::table('user_superior')->update($deletedData);
            //补充 企业微信同步组织架构
            try{
                app($this->workWechatService)->autoSyncToWorkWeChatDeleteAllUser();
            }catch (\Exception $e){
                Log::error($e->getMessage());
            }

        }
        $updateSuprior = [];
        foreach ($data as $key => $value) {
            $count = 0;
            foreach ($value as $k => $v) {
                $v = trim($v);
                $value[$k] = $v;
                if (!empty($v)) {
                    $count++;
                }
            }
            if ($count == 0) {
                continue;
            }
            $value['is_autohrms'] = $this->isAutohrms(Arr::get($param, 'user_info.menus.menu')) ? '1' : '0';
            // 解析需要变成id的字段
            // $value = $this->parseImportUserData($value, $param, $loginUserId);

            if (isset($value['user_accounts']) && empty($value['user_accounts'])) {
                $value['user_accounts'] = $value['user_name'];
            }
            // 检查必填
            $requiredTip = $this->checkImportUserDataRequired($value);
            $rulesTipStr = '';
            if (!empty($requiredTip)) {
                foreach ($requiredTip as $rulesTip) {
                    $rulesTipStr .= trans("user." . $rulesTip) . ';';
                }
            }
            if (isset($value['user_superior_id']) && $value['user_superior_id'] == 'all') {
                $value['is_all'] = 1;
            }
            $value = $this->parseImportOrganizationRoleData($value, $loginUserId);
            $value['data_from']   = 'import';
            if ($param['type'] == 1) {
                //仅新增数据
                if (!empty($requiredTip)) {
                    $info['error']++;
                    $data[$key]['importResult'] = importDataFail();
                    $data[$key]['importReason'] = importDataFail($rulesTipStr);
                    continue;
                } else {
                    $value['post_priv'] = '0';
                    $userCreateResult = $this->userSystemCreate($value, $loginUserInfo);
                    if (isset($userCreateResult['code'])) {
                        $info['error']++;
                        $rulesTipStr .= trans("user." . $userCreateResult['code'][0]);
                        $data[$key]['importResult'] = importDataFail();
                        $data[$key]['importReason'] = importDataFail($rulesTipStr);
                        continue;
                    } else {
                        //补充 企业微信同步组织架构
                        try {
                            if (isset($userCreateResult['user_id'])){
                                $toWorkWeChatData = [
                                    'type' => 'add',
                                    'user_id' => $userCreateResult['user_id']
                                ];
                                app($this->workWechatService)->importUserToWorkWeChatUser($toWorkWeChatData);
                            }
                        } catch (\Exception $e) {
                            Log::error($e->getMessage());
                        }
                        $info['success']++;
                        $data[$key]['importResult'] = importDataSuccess();
                        $updateSuprior[] = $value;
                    }
                }
            } elseif ($param['type'] == 2) {
                //仅更新数据
                if ($param["primaryKey"] != 'user_accounts') {
                    exit;
                }
                $where = ['user_accounts' => [$value['user_accounts']]];
                $result = app($this->userRepository)->judgeUserExists($where);
                if (!$result) {
                    $info['error']++;
                    $data[$key]['importResult'] = importDataFail();
                    $data[$key]['importReason'] = importDataFail(trans("user.0x005016"));
                    continue;
                } else {
                    if (!empty($requiredTip)) {
                        $info['error']++;
                        $data[$key]['importResult'] = importDataFail();
                        $data[$key]['importReason'] = importDataFail($rulesTipStr);
                        continue;
                    } else {
                        $value['user_id'] = $result;
                        $value['data_from'] = 'import';
                        $userEditResult = $this->userSystemEdit($value, $loginUserInfo);
                        if (isset($userEditResult['code'])) {
                            $info['error']++;
                            $rulesTipStr .= trans("user." . $userEditResult['code'][0]);
                            $data[$key]['importResult'] = importDataFail();
                            $data[$key]['importReason'] = importDataFail($rulesTipStr);
                            continue;
                        } else {
                            //补充 企业微信同步组织架构
                            try {
                                if (isset($value['user_id'])){
                                    $toWorkWeChatData = [
                                        'type' => 'update',
                                        'user_id' => $value['user_id']
                                    ];
                                    app($this->workWechatService)->importUserToWorkWeChatUser($toWorkWeChatData);
                                }
                            } catch (\Exception $e) {
                                Log::error($e->getMessage());
                            }
                            $info['success']++;
                            $data[$key]['importResult'] = importDataSuccess();
                            $updateSuprior[] = $value;
                        }
                    }
                }
            } elseif ($param['type'] == 3) {
                $where = ['user_accounts' => [$value['user_accounts']]];
                //新增数据并清除原有数据
                if (!empty($requiredTip)) {
                    $info['error']++;
                    $data[$key]['importResult'] = importDataFail();
                    $data[$key]['importReason'] = importDataFail($rulesTipStr);
                    continue;
                } else {
                    $value['post_priv'] = '0';
                    $userCreateResult = $this->userSystemCreate($value, $loginUserInfo);
                    if (isset($userCreateResult['code'])) {
                        $info['error']++;
                        $rulesTipStr .= trans("user." . $userCreateResult['code'][0]);
                        $data[$key]['importResult'] = importDataFail();
                        $data[$key]['importReason'] = importDataFail($rulesTipStr);
                        continue;
                    } else {
                        //补充 企业微信同步组织架构
                        try {
                            if (isset($userCreateResult['user_id'])){
                                $toWorkWeChatData = [
                                    'type' => 'add',
                                    'user_id' => $userCreateResult['user_id']
                                ];
                                app($this->workWechatService)->importUserToWorkWeChatUser($toWorkWeChatData);
                            }

                        } catch (\Exception $e) {
                            Log::error($e->getMessage());
                        }
                        $info['success']++;
                        $data[$key]['importResult'] = importDataSuccess();
                        $updateSuprior[] = $value;
                    }
                }
            } elseif ($param['type'] == 4) {
                //新增数据并更新已有数据
                if ($param["primaryKey"] != 'user_accounts') {
                    exit;
                }
                $where = ['user_accounts' => [$value['user_accounts']]];
                $result = app($this->userRepository)->judgeUserExists($where);
                if (!$result) {
                    if (!empty($rulesTipStr)) {
                        $info['error']++;
                        $data[$key]['importResult'] = importDataFail();
                        $data[$key]['importReason'] = importDataFail($rulesTipStr);
                        continue;
                    } else {
                        $value['post_priv'] = '0';
                        $userCreateResult = $this->userSystemCreate($value, $loginUserInfo);
                        if (isset($userCreateResult['code'])) {
                            $info['error']++;
                            $rulesTipStr .= trans("user." . $userCreateResult['code'][0]);
                            $data[$key]['importResult'] = importDataFail();
                            $data[$key]['importReason'] = importDataFail($rulesTipStr);
                            continue;
                        } else {
                            //补充 企业微信同步组织架构
                            try {
                                if (isset($userCreateResult['user_id'])){
                                    $toWorkWeChatData = [
                                        'type' => 'add',
                                        'user_id' => $userCreateResult['user_id']
                                    ];
                                    app($this->workWechatService)->importUserToWorkWeChatUser($toWorkWeChatData);
                                }
                            } catch (\Exception $e) {
                                Log::error($e->getMessage());
                            }
                            $info['success']++;
                            $data[$key]['importResult'] = importDataSuccess();
                        }
                    }
                } else {
                    if (!empty($rulesTipStr)) {
                        $info['error']++;
                        $data[$key]['importResult'] = importDataFail();
                        $data[$key]['importReason'] = importDataFail($rulesTipStr);
                        continue;
                    } else {
                        $value['user_id'] = $result;
                        $userEditResult = $this->userSystemEdit($value, $loginUserInfo);
                        if (isset($userEditResult['code'])) {
                            $info['error']++;
                            $rulesTipStr .= trans("user." . $userEditResult['code'][0]);
                            $data[$key]['importResult'] = importDataFail();
                            $data[$key]['importReason'] = importDataFail($rulesTipStr);
                            continue;
                        } else {
                            //补充 企业微信同步组织架构
                            try {
                                if (isset($value['user_id'])){
                                    $toWorkWeChatData = [
                                        'type' => 'update',
                                        'user_id' => $value['user_id']
                                    ];
                                    app($this->workWechatService)->importUserToWorkWeChatUser($toWorkWeChatData);
                                }
                            } catch (\Exception $e) {
                                Log::error($e->getMessage());
                            }
                            $info['success']++;
                            $data[$key]['importResult'] = importDataSuccess();
                            $updateSuprior[] = $value;
                        }
                    }
                }
            }
        }
        if ($updateSuprior) {
           // 更新上级
            foreach ($updateSuprior as $k => $v) {
                $where = ['user_accounts' => [$v['user_accounts']]];
                $result = app($this->userRepository)->judgeUserExists($where);
                if ($result) {
                    $v['user_id'] = $result;
                    $v = $this->toParseUserSuperior($v);
                    // 更新上级
                    if (isset($v['user_superior_id']) && !empty($v['user_superior_id'])) {
                        $v['edit'] = 1;
                        app($this->roleService)->addUserSuperior($v);
                    }
                }

            }
        }

        // 清空表单分类信息redis缓存
        if(!empty(Redis::keys('flow_form_type_power_list_*'))) {
            Redis::del(Redis::keys('flow_form_type_power_list_*'));
        }
        // 清空表单分类信息redis缓存
        if(!empty(Redis::keys('flow_sort_power_list_*'))) {
            Redis::del(Redis::keys('flow_sort_power_list_*'));
        }
        return compact('data', 'info');
    }

    private function toParseUserSuperior($data)
    {
        // 上下级关系处理
        if (isset($data['user_superior_id']) && !empty($data['user_superior_id'])) {
            $data['user_superior_id'] = $this->parseUserSuperior($data['user_superior_id']);
        }
        return $data;
    }

    private function parseUserDeptId($deptInfo) {
        $deptId = '';
        if (!empty($deptInfo)) {
            $deptInfoArr = array_filter(explode('/', $deptInfo));
            $deptData = app($this->departmentService)->listDept([]);
            $deptTempData = [];
            if ($deptData) {
                foreach ($deptData['list'] as $k => $v) {
                    $deptTempData[$k]['dept_id'] = $v['dept_id'];
                    $deptTempData[$k]['dept_name'] = $this->getDeptPathByDeptId($v['dept_id']);
                }
            }
            $deptData = $this->findMixedValue($deptInfo, $deptTempData);
            $deptId = $deptData['dept_id'] ?? '';
        }
        return $deptId;
    }

    private function parseUserRoleId($roleName) {
        $roleNameArr = explode(',', $roleName);
        $roleIds = app($this->roleRepository)->getRoleIdByName($roleNameArr);
        $roleIdString = implode(',', array_filter(array_column($roleIds, 'role_id')));
        return $roleIdString;
    }
    private function findMixedValue($value, $array) {
        foreach($array as $item) {
            if(!is_array($item)) {
                if ($item == $value) {
                    return $item;
                } else {
                    continue;
                }
            }

            if(in_array($value, $item)) {
                return $item;
            } else if($this->findMixedValue($value, $item)) {
                return $item;
            }
        }
        return false;
     }

    /**
     * 用户管理--用户导入检查必填
     *
     * @author 缪晨晨
     *
     * @param  array $data [description]
     *
     * @since  2017-04-10 创建
     *
     * @return array   返回结果
     */
    public function  checkImportUserDataRequired($data)
    {
        $requiredTip = array();
        if (!empty($data)) {
            if (!isset($data['user_name']) || empty($data['user_name'])) {
                $requiredTip[] = '0x005008';
            }
            // 去除用户账号, 角色ID, 性别和用户状态的导入必填
            // if (!isset($data['user_accounts']) || empty($data['user_accounts'])) {
            //     $requiredTip[] = '0x005009';
            // }
            // if (!isset($data['role_id_init']) || empty($data['role_id_init'])) {
            //     $requiredTip[] = '0x005010';
            // }
            // // if (!isset($data['sex']) || trim($data['sex']) === '') {
            //     $requiredTip[] = '0x005012';
            // }
            // if (!isset($data['user_status']) || empty($data['user_status'])) {
            //     $requiredTip[] = '0x005014';
            // }
            if (!isset($data['dept_id']) || empty($data['dept_id'])) {
                $requiredTip[] = '0x005125';
            }

            if (isset($data['email']) && $data['email'] && !check_email($data['email'])) {
                $requiredTip[] = '0x005082';
            }
            if ((isset($data['dept_id']) && isset($data['parent_dept_id'])) && ($data['dept_id'] == $data['parent_dept_id'])) {
                $requiredTip[] = '0x005122';
            }



            if (isset($data['user_password'])) {
                 if (preg_match("/([\x81-\xfe][\x40-\xfe])/", $data['user_password'], $match)) {
                    $requiredTip[] = '0x005098';
                }
            }
        }
        return $requiredTip;
    }
    private function parseImportPassword($password) {
        $requiredTip = [];
        // 判断是否开启密码强度限制
        // if (get_system_param('login_password_security_switch')) {
        //     // 判断长度
        //     $length = get_system_param('password_length');
        //     if (strlen($password) < $length) {
        //         $requiredTip[] = '0x005095';
        //     }
        //     // 包含字符串字母符号
        //     if (!preg_match("/(?:(?=.*[0-9].*)(?=.*[A-Za-z].*)(?=.*[\\W].*))[\\W0-9A-Za-z]{8,16}/", $password, $match)) {
        //         $requiredTip[] = '0x005094';
        //     }
        // }
        // 判断密码是否包含中文
        if (preg_match("/([\x81-\xfe][\x40-\xfe])/", $password, $match)) {
            $requiredTip[] = '0x005098';
        }

        return $requiredTip;
    }
    /**
     * 用户管理--获取用户列表数据
     *
     * @author 丁鹏
     *
     * @param  array $param [description]
     *
     * @since  2015-10-16 创建
     *
     * @return array   返回结果
     */
    public function userManageSystemList($loginUserInfo, $param = [])
    {
        $param = $this->parseParams($param);
        // 组织搜索不控制权限
        if (!isset($param['user_search'])) {
            $param['loginUserInfo'] = $loginUserInfo;
            // admin特权查看所有用户 非admin查看管理权限内的用户
            if (isset($param['loginUserInfo']['user_id']) && $param['loginUserInfo']['user_id'] != 'admin') {
                $param['fixedSearch']['dept_id'][0] = $this->getUserCanManageDepartmentId($loginUserInfo);
                $maxRoleNo = isset($loginUserInfo['max_role_no']) ? $loginUserInfo['max_role_no'] : 0;
                $param['search']['max_role_no'][0] = $maxRoleNo;
                $param['dept_id'] = $param['fixedSearch']['dept_id'][0];
                if ($param['fixedSearch']['dept_id'][0] == 'all') {
                    $allDept = array_column(app($this->departmentService)->getAllDeptId(), 'dept_id');
                    $param['dept_id'] = $allDept;
                }
                // $param['fixedSearch']['role_id'][0] = $this->getUserCanManageRoleId($loginUserInfo['user_id']);
            }
        } else {
            unset($param['user_search']);
        }
        $userList = $this->response(app($this->userRepository), 'getUserManageListTotal', 'getUserManageList', $param);
        $userPositionArray = app($this->systemComboboxService)->getComboboxFieldByIdentify('USER_POSITION');
        if (!empty($userList['list'])) {
            foreach ($userList['list'] as $key => $value) {
                if (!empty($value['user_position'])) {
                    $userList['list'][$key]['user_position_name'] = !empty($userPositionArray) && isset($userPositionArray[$value['user_position']]) ? $userPositionArray[$value['user_position']] : '';
                }
                $userList['list'][$key]['is_lock'] = ecache('Auth:WrongPwdTimes')->get($value['user_id']) === '0' ? 1 : 0;
            }
        }
        return $userList;
    }
    public function getAddressBookUsers($param = [], $own = [])
    {
        $param = $this->parseParams($param);
        if($param['filter_key'] == 'dept') {
            $param['search']['dept_id'] = [[$own['dept_id']], 'in'];
        } else if($param['filter_key'] == 'sub') {
            $ids = $this->getSubordinateArrayByUserId($own['user_id'], ['all_subordinate' => 1]);
            $param['search']['user_id'] =[$ids['id'],'in'];
        }
        $response = $this->userSystemList($param, $own);

        $users = [];
        if($response['total'] > 0) {
            $userPositions = app($this->systemComboboxService)->getComboboxFieldByIdentify('USER_POSITION');
            foreach ($response['list'] as $item) {
                $users[] = [
                    'user_id' => $item['user_id'],
                    'user_accounts' => $item['user_accounts'],
                    'user_name' => $item['user_name'],
                    'user_name_py' => $item['user_name_py'],
                    'user_name_zm' => $item['user_name_zm'],
                    'roles' => $this->parseUserRoles($item['user_has_many_role']),
                    'dept_id' => $item['user_has_one_system_info']['dept_id'],
                    'dept_name' => $item['user_has_one_system_info']['user_system_info_belongs_to_department']['dept_name'],
                    'phone_number' => $item['user_has_one_info']['phone_number'],
                    'user_position_name' => $userPositions[$item['user_position']] ?? ''
                ];
            }
        }
        return $users;
    }
    private function parseUserRoles($userRoles)
    {
        $roles = [];
        if(!empty($userRoles)) {
            foreach ($userRoles as $item) {
                $roles[] = [
                    'role_id' => $item['role_id'],
                    'role_name' => $item['has_one_role'] ? $item['has_one_role']['role_name'] : ''
                ];
            }
        }
        return $roles;
    }
    /**
     * 获取用户列表数据(用于人员选择等需要展示所有用户的)
     *
     * @author 丁鹏
     *
     * @param  array $param [description]
     *
     * @since  2015-10-16 创建
     *
     * @return array   返回结果
     */
    public function userSystemList($param = [], $loginUserInfo = [])
    {
        $param = $this->parseParams($param);
        if (isset($param['dataFilter']) && !empty($param['dataFilter'])) {
            $config = config('dataFilter.' . $param['dataFilter']);
            if (!empty($config)) {
                $method = $config['dataFrom'][1];
                $param['loginUserInfo'] = $loginUserInfo;
                $data = app($config['dataFrom'][0])->$method($param);
                unset($param['loginUserInfo']);
                if (isset($data['user_id'])) {
                    if (!empty($data['user_id'])) {
                        if (isset($data['isNotIn'])) {
                            if ($data['isNotIn']) {
                                // 包含获取到的user_id
                                if (isset($param['search']['user_id']) && !empty($param['search']['user_id'])) {
                                    $searchUserId = $param['search']['user_id'][0];
                                    if (!(is_array($searchUserId) || is_object($searchUserId))) {
                                        $param["search"]["user_id"][0] = explode(",", trim($param["search"]["user_id"][0], ","));
                                    }
                                    $param["search"]["user_id"][0] = array_intersect($param["search"]["user_id"][0], $data['user_id']);
                                } else {
                                    $param['search']['user_id'] = [$data['user_id'], 'in'];
                                }
                            } else {
                                // 排除获取到的user_id
                                if (isset($param['search']['user_id']) && !empty($param['search']['user_id'])) {
                                    $searchUserId = $param['search']['user_id'][0];
                                    if (!(is_array($searchUserId) || is_object($searchUserId))) {
                                        $param["search"]["user_id"][0] = explode(",", trim($param["search"]["user_id"][0], ","));
                                        $param["search"]["user_id"][1] = 'not_in';
                                    }
                                    $param["search"]["user_id"][0] = array_intersect($param["search"]["user_id"][0], $data['user_id']);
                                } else {
                                    $param['search']['user_id'] = [$data['user_id'], 'not_in'];
                                }
                            }
                        } else {
                            // 默认包含获取到的user_id
                            if (isset($param['search']['user_id']) && !empty($param['search']['user_id'])) {
                                $searchUserId = $param['search']['user_id'][0];
                                if (!(is_array($searchUserId) || is_object($searchUserId))) {
                                    $param["search"]["user_id"][0] = explode(",", trim($param["search"]["user_id"][0], ","));
                                }
                                $param["search"]["user_id"][0] = array_intersect($param["search"]["user_id"][0], $data['user_id']);
                            } else {
                                if (isset($data['user_id'])) {
                                    $param['search']['user_id'] = [$data['user_id'], 'in'];
                                }
                            }
                        }
                    } else {
                        // 过滤函数返回结果中user_id为空的时候，且isNotIn为true的时候返回空列表
                        if (!isset($data['isNotIn']) || $data['isNotIn']) {
                            return ['list' => [], 'total' => 0];
                        }
                    }
                }
            }
        }

        // 手机版人员选择最近联系人过滤
        if (isset($param['search']['recent_user_id'][0]) && !empty($param['search']['recent_user_id'][0])) {
            if (isset($param['search']['user_id']) && !empty($param['search']['user_id'])) {
                if (isset($param['search']['user_id'][1]) && $param['search']['user_id'][1] == 'in') {
                    if (!is_array($param['search']['user_id'][0])) {
                        $param['search']['user_id'][0] = explode(',', trim($param['search']['user_id'][0], ','));
                    }
                    if (is_array($param['search']['recent_user_id'][0])) {
                        $tempRecentUserArray = array();
                        foreach ($param['search']['recent_user_id'][0] as $key => $value) {
                            if (in_array($value, $param['search']['user_id'][0])) {
                                $tempRecentUserArray[] = $value;
                            }
                        }
                        if (!empty($tempRecentUserArray)) {
                            $param['search']['user_id'] = [$tempRecentUserArray, 'in'];
                        } else {
                            $param['search']['user_id'] = [[], 'in'];
                        }
                    } else {
                        if (in_array($param['search']['recent_user_id'][0], $param['search']['user_id'][0])) {
                            $param['search']['user_id'] = [[$param['search']['recent_user_id'][0]], 'in'];
                        } else {
                            $param['search']['user_id'] = [[], 'in'];
                        }
                    }
                }
            }
            unset($param['search']['recent_user_id']);
        }
        if (isset($param['communicate_type']) && !empty($param['communicate_type'])) {
            // 角色通信过滤
            if (isset($loginUserInfo['user_id']) || isset($param['user_id'])) {
                $cacheKey = $loginUserInfo['user_id'] . '_communicatetype';
                if (isset($param['filter_type']) && $param['filter_type']) {
                    $cacheKey = $loginUserInfo['user_id'] . '_filter_communicatetype';
                }
                if (Cache::has($cacheKey)) {
                    $communicateRoleFilter = Cache::get($cacheKey);
                } else {
                    $communicateRoleFilter = $this->communicateUserFilter($param, $loginUserInfo);
                    Cache::add($cacheKey, $communicateRoleFilter, 1);
                }
                if (!empty($communicateRoleFilter) && $communicateRoleFilter == 'all') {
                    return ['list' => [], 'total' => 0];
                } elseif (!empty($communicateRoleFilter)) {
                    if (isset($param['search']['role_id'][0]) && !empty($param['search']['role_id'][0])) {
                        if (is_array($param['search']['role_id'][0])) {
                            $newSearchRoleIdParam = array();
                            foreach ($param['search']['role_id'][0] as $key => $value) {
                                if (in_array($value, $communicateRoleFilter)) {
                                    $newSearchRoleIdParam[] = $value;
                                }
                            }
                            $param['search']['role_id'][0] = [$newSearchRoleIdParam];
                        } else {
                            if (!in_array($param['search']['role_id'][0], $communicateRoleFilter)) {
                                return ['list' => [], 'total' => 0];
                            }
                        }
                    } else {
                        $param['search']['role_id'] = [$communicateRoleFilter];
                    }
                    if (isset($param['filter_type']) && $param['filter_type']) {
                         // 这里为了角色通信控制获取到过滤掉的人员ID
                        return $this->getAllUserIdString($param);
                    }
                } else {
                    if (isset($param['filter_type']) && $param['filter_type']) {
                        return [];
                    }
                }
            }
        }
        // 流程获取范围内的主办人 处理用户组过滤不了人员的问题
        if (isset($param['search']['user_id_for_group']) && !empty($param['search']['user_id_for_group'])) {
            if(!isset($param['search']['user_id']) || empty($param['search']['user_id'])){
                $param['search']['user_id'] = $param['search']['user_id_for_group'];
            } else {
                $paramSearchUserId = [];
                if(isset($param['search']['user_id'])) {
                    $paramSearchUserId = isset($param['search']['user_id'][0]) && $param['search']['user_id'][0] ? $param['search']['user_id'][0] : [];
                    if($paramSearchUserId && gettype($paramSearchUserId) == "string") {
                        $paramSearchUserId = [$paramSearchUserId];
                    }
                }
                if (isset($param['search']['user_id'][1]) && isset($param['search']['user_id_for_group'][0])) {
                    if ($param['search']['user_id'][1] == 'not_in') {
                        $param['search']['user_id'] = [array_diff($param['search']['user_id_for_group'][0],$paramSearchUserId), 'in'];
                    } else {
                        $param['search']['user_id'] = [array_intersect($param['search']['user_id_for_group'][0],$paramSearchUserId), 'in'];
                    }
                }
            }
            unset($param['search']['user_id_for_group']);
        }
        $orgPermission = $param['permission'] ?? 1;
        // 用户选择器
        if ($orgPermission && !isset($param['from'])) {
            // 只返回有权限的部门人员
            $permission = get_system_param('permission_organization');
            if ($permission) {
                $allDeptIds = app($this->departmentService)->getPermissionDept($loginUserInfo);

                if (!isset($param['search']['dept_id'])) {
                    $param['search']['dept_id'] = [$allDeptIds, 'in'];
                } else if (isset($param['search']['dept_id']) && isset($param['search']['dept_id'][0])) {
                    if ($param['search']['dept_id'][0] != 0) {
                        $searchDept = is_array($param['search']['dept_id'][0]) ? $param['search']['dept_id'][0] : [$param['search']['dept_id'][0]];

                        $insect = array_intersect($allDeptIds, $searchDept);
                        if ($insect) {
                            $param['search']['dept_id'] = [$insect, 'in'];
                        } else {
                            return ['list' => [], 'total' => 0];
                        }

                    } else {
                        $param['search']['dept_id'] = [$allDeptIds, 'in'];
                    }
                }
            }
        }
        $userList = $this->response(app($this->userRepository), 'getUserListTotal', 'getUserList', $param);
        $userPositionArray = app($this->systemComboboxService)->getComboboxFieldByIdentify('USER_POSITION');
        if (!empty($userPositionArray) && !empty($userList['list'])) {
            foreach ($userList['list'] as $key => $value) {
                if (!empty($value['user_position'])) {
                    $userList['list'][$key]['user_position_name'] = isset($userPositionArray[$value['user_position']]) ? $userPositionArray[$value['user_position']] : '';
                }
            }
        }

        if (isset($param['datatype']) && $param['datatype'] == 'id') {
            return array_column($userList['list'], 'user_id');
        }

        return $userList;
    }
    public function getMyDepartmentUsers($deptId, $param, $own = [])
    {
        $orgPermission = $param['permission'] ?? 1;
        // 用户选择器
        if ($orgPermission) {
            // 只返回有权限的部门人员
            $permission = get_system_param('permission_organization');
            if ($permission) {
                if (!$own) {
                    return ['list' => [], 'total' => 0];
                }
                $allDeptIds = app($this->departmentService)->getPermissionDept($own);
                if (!in_array($deptId, $allDeptIds)) {
                    return ['list' => [], 'total' => 0];
                }
            }
        }
        if (isset($param['page']) && isset($param['limit'])) {
            $users = app($this->userRepository)->getSimpleUserList(['dept_id' => $deptId,'page' => $param['page'], 'limit' => $param['limit'], 'order_by' => ['user_name_zm' => 'ASC', 'list_number' => 'ASC']]);
            $userPositions = app($this->systemComboboxService)->getComboboxFieldByIdentify('USER_POSITION');

            foreach ($users as $key => $user) {
                $users[$key]['user_position_name'] = isset($userPositions[$user['user_position']]) ? $userPositions[$user['user_position']] : '';
            }
            $usersCount = app($this->userRepository)->getSimpleUserList(['dept_id' => $deptId, 'order_by' => ['user_name_zm' => 'ASC', 'list_number' => 'ASC'], 'returntype' => 'count']);
            return ['list' => $this->handleSimpleUserList($users), 'total' => $usersCount];
        }
        $users = app($this->userRepository)->getSimpleUserList(['dept_id' => $deptId,'noPage' => true,'order_by' => ['user_name_zm' => 'ASC', 'list_number' => 'ASC']]);

            $userPositions = app($this->systemComboboxService)->getComboboxFieldByIdentify('USER_POSITION');

            foreach ($users as $key => $user) {
                $users[$key]['user_position_name'] = isset($userPositions[$user['user_position']]) ? $userPositions[$user['user_position']] : '';
            }
        return $this->handleSimpleUserList($users);
    }

    /**
     * 用户管理--获取用户所有信息
     *
     * @author 丁鹏
     *
     * @param  string $userId [description]
     *
     * @since  2015-10-16 创建
     *
     * @return array    返回结果
     */
    public function getUserAllData($userId, $loginUserInfo = [], $params = [])
    {
        $params['include_leave'] = true;
        // $params['get_leave'] = true;
        if (isset($params['editMode']) && $params['editMode']) {
            if (!empty($loginUserInfo) && $loginUserInfo['user_id'] != 'admin') {
                // 非管理员的用户编辑页面判断访问权限
                $canEditUserList = $this->judgeUserManageScope($loginUserInfo, $params);
                if (!empty($canEditUserList)) {
                    if (!in_array($userId, $canEditUserList)) {
                        return ['code' => ['0x000006', 'common']];
                    }
                } else {
                    return ['code' => ['0x000006', 'common']];
                }
            }
        }
        $userAllData = app($this->userRepository)->getUserAllData($userId, $params);
        if (!empty($userAllData)) {
            if (isset($userAllData->userHasOneSystemInfo->user_status) && isset($userAllData->userHasOneSystemInfo->userSystemInfoBelongsToUserStatus)) {
                $userAllData->userHasOneSystemInfo->userSystemInfoBelongsToUserStatus->status_name = mulit_trans_dynamic("user_status.status_name.user_status_" .$userAllData->userHasOneSystemInfo->user_status);
            }
            $userAllData['getLoginUserInfo'] = '';
            $userAllData['user_area_name'] = '';
            $userAllData['user_city_name'] = '';
            $userAllData['user_workplace_name'] = '';
            $userAllData['user_job_category_name'] = '';
            if (isset($userAllData['user_position']) && !empty($userAllData['user_position']) && isset($this->userSelectField['user_position'])) {
                $userAllData['user_position_name'] = app($this->systemComboboxService)->getComboboxFieldsNameByIdentify($this->userSelectField['user_position'], $userAllData['user_position']);
            }
            if (isset($userAllData['user_area']) && !empty($userAllData['user_area']) && isset($this->userSelectField['user_area'])) {
                $userAllData['user_area_name'] = app($this->systemComboboxService)->getComboboxFieldsNameByIdentify($this->userSelectField['user_area'], $userAllData['user_area']);
            }
            if (isset($userAllData['user_city']) && !empty($userAllData['user_city']) && isset($this->userSelectField['user_city'])) {
                $userAllData['user_city_name'] = app($this->systemComboboxService)->getComboboxFieldsNameByIdentify($this->userSelectField['user_city'], $userAllData['user_city']);
            }
            if (isset($userAllData['user_workplace']) && !empty($userAllData['user_workplace']) && isset($this->userSelectField['user_workplace'])) {
                $userAllData['user_workplace_name'] = app($this->systemComboboxService)->getComboboxFieldsNameByIdentify($this->userSelectField['user_workplace'], $userAllData['user_workplace']);
            }
            if (isset($userAllData['user_job_category']) && !empty($userAllData['user_job_category']) && isset($this->userSelectField['user_job_category'])) {
                $userAllData['user_job_category_name'] = app($this->systemComboboxService)->getComboboxFieldsNameByIdentify($this->userSelectField['user_job_category'], $userAllData['user_job_category']);
            }
            if (isset($userAllData->userHasOneInfo->birthday) && $userAllData->userHasOneInfo->birthday == '0000-00-00') {
                $userAllData->userHasOneInfo->birthday = '';
            }
            if (!isset($params['editMode']) || !$params['editMode']) {
                if (isset($userAllData->userHasOneInfo->phone_number) && !empty($userAllData->userHasOneInfo->phone_number)) {
                    // 对用户手机号码字段查看权限控制
                    if (!empty($loginUserInfo) && isset($loginUserInfo['role_id'])) {
                        $userRoleId = [];
                        if (isset($userAllData->userHasManyRole) && !empty($userAllData->userHasManyRole)) {
                            foreach ($userAllData->userHasManyRole as $key => $value) {
                                if ($value->role_id) {
                                    $userRoleId[] = $value->role_id;
                                }
                            }
                        }
                        $fieldPermissions = app('App\EofficeApp\Role\Repositories\RoleCommunicateRepository')->getControlFieldsByTable($loginUserInfo['role_id'], 'user');
                        $limitedRoleId = [];
                        if (!empty($fieldPermissions)) {
                            foreach ($fieldPermissions as $key => $value) {
                                if (!empty($value) && is_array($value) && in_array('phone_number', $value)) {
                                    $limitedRoleId[] = $key;
                                }
                            }
                        }
                        if (!empty($userRoleId) && !empty($limitedRoleId)) {
                            if (!empty(array_intersect($limitedRoleId, $userRoleId))) {
                                $userAllData->userHasOneInfo->phone_number = substr_replace($userAllData->userHasOneInfo->phone_number, '********', 3);
                            }
                        }
                    }
                }
            }
            if (isset($userAllData->password)) {
                unset($userAllData->password);
            }
            return $userAllData;
        } else {
            if (isset($params['editMode']) && $params['editMode']) {
                return ['code' => ['0x000006', 'common']];
            } else {
                return [];
            }
        }
    }
    /**
     * 用户管理--用户登录信息获取
     *
     * @author niuxiaoke
     *
     * @param  string $userId [用户ID]
     *
     * @since  2019-01-21 创建
     *
     * @return array    返回结果
     */
    public function getLoginUserInfo($userId) {
        $user = app($this->userRepository)->getUserSystemInfo($userId);
        if (empty($user)) {
            return ['code' => ['0x003009', 'auth']];
        }
        $newUser = new \stdClass();
        $newUser->user_id = $user->user_id;
        $newUser->user_name = $user->user_name;
        $newUser->password = $user->password;
        $newUser->user_position = $user->user_position;
        $newUser->user_accounts = $user->user_accounts;
        $newUser->user_job_number = $user->user_job_number;
        $newUser->dept_id = 0;
        $newUser->dept_name = '';
        $newUser->roles = [];
        $newUser->user_status = 0;
        // $newUser->user_status_name = '0';
        $newUser->user_position_name = '';
        $newUser->last_login_time = $user->userHasOneSystemInfo->last_login_time ?? null;
        $newUser->last_pass_time = $user->userHasOneSystemInfo->last_pass_time ?? null;
        $newUser->change_pwd = $user->change_pwd ?? 0;
        $newUser->post_priv = $user->userHasOneSystemInfo->post_priv ?? 0;
        $newUser->post_dept = $user->userHasOneSystemInfo->post_dept ?? 0;
        $newUser->max_role_no = $user->userHasOneSystemInfo->max_role_no ?? 0;
        $newUser->phone_number = $user->userHasOneInfo->phone_number ?? '';
        if (isset($user->user_area) && !empty($user->user_area) && isset($this->userSelectField['user_area'])) {
            $newUser->user_area_name = app($this->systemComboboxService)->getComboboxFieldsNameByIdentify($this->userSelectField['user_area'], $user->user_area);
        }
        if (isset($user->user_city) && !empty($user->user_city) && isset($this->userSelectField['user_city'])) {
            $newUser->user_city_name = app($this->systemComboboxService)->getComboboxFieldsNameByIdentify($this->userSelectField['user_city'], $user->user_city);
        }
        if (isset($user->user_workplace) && !empty($user->user_workplace) && isset($this->userSelectField['user_workplace'])) {
            $newUser->user_workplace_name = app($this->systemComboboxService)->getComboboxFieldsNameByIdentify($this->userSelectField['user_workplace'], $user->user_workplace);
        }
        if (isset($user->user_job_category) && !empty($user->user_job_category) && isset($this->userSelectField['user_job_category'])) {
            $newUser->user_job_category_name = app($this->systemComboboxService)->getComboboxFieldsNameByIdentify($this->userSelectField['user_job_category'], $user->user_job_category);
        }
        if ($newUser->user_position  && isset($this->userSelectField['user_position'])) {
            $newUser->user_position_name = app($this->systemComboboxService)->getComboboxFieldsNameByIdentify($this->userSelectField['user_position'], $newUser->user_position);
        }
        if (isset($user->userHasOneSystemInfo->userSystemInfoBelongsToDepartment)) {
            $department = $user->userHasOneSystemInfo->userSystemInfoBelongsToDepartment;
            $newUser->dept_id = $department->dept_id ?? 0;
            $newUser->dept_name = $department->dept_name ?? 0;
        }
        if (isset($user->userHasOneInfo)){
            $newUser->show_page_after_login = $user->userHasOneInfo->show_page_after_login;
            $newUser->phone_number = $user->userHasOneInfo->phone_number;
        }
        if (isset($user->userHasManyRole) && !empty($user->userHasManyRole)) {
            $roles = [];
            foreach($user->userHasManyRole as $value){
                if (isset($value->hasOneRole) && !empty($value->hasOneRole)) {
                    $roles[] = [
                        'role_id' => $value->hasOneRole->role_id,
                        'role_name' => $value->hasOneRole->role_name,
                        'role_no' => $value->hasOneRole->role_no
                    ];
                }
            }
            $newUser->roles = $roles;
        }
        return $newUser;
    }
    // 批量新建用户
    public function mutipleUserSystemCreate($data, $loginUserInfo = []) {
        if (!isset($data['dept_id']) || !isset($data['user_name'])) {
            return ['code' => ['0x000001', 'common']];
        }
        $returnArray = [];
        $invalidUser = [];
        $repeatUser = [];
        foreach($data['user_name'] as $value) {
            // 用户名作为账号
            $checkUserAccount = $this->validateUserAccountsValidation($value ?? '');
            if (!$checkUserAccount) {
                $invalidUser[] = $value;
            }

            $checkResult = $this->checkUserInfoUnique(['user_accounts' => $value], '');
            if (!empty($checkResult)) {
                $repeatUser[] = $value;
            }
        }
        if (!empty($invalidUser)) {
            return ['status' =>0, 'errors' => [ ['code' => '0x005015', 'message' => trans('user.0x005015')] ]];
        }
        if (!empty($repeatUser)) {
            return ['status' =>0, 'errors' => [ ['code' => '0x005003', 'message' => trans('user.0x005003')] ], 'invalid' => $repeatUser];
        }

        $roleId = $this->getDefaultRole();

        foreach ($data['user_name'] as $value) {
            $result = $this->addDeptUser(['dept_id' => $data['dept_id'], 'user_name' => $value, 'role_id' => $roleId], $loginUserInfo);
            if (isset($result['code'])) {
                return $result;
            }
            $returnArray[] = $result;
        }

        return $returnArray;
    }
    public function addDeptUser($data, $loginUserInfo = []) {
        if (empty($data)) {
            return ['code' => ['0x000003', 'common']];
        }
        if (!isset($data['user_name']) || empty($data['user_name'])) {
            return ['code' => ['0x005009', 'user']];
        }
        if (isset($data['role_id'])) {
            $roleId = $data['role_id'];
        } else {
            $roleId = $this->getDefaultRole();
        }

        $userData = [
            'attendance_scheduling' => 1,
            'user_accounts' => isset($data['account']) ? $data['account'] : ($data['user_name'] ?? ''),
            'user_name' => $data['user_name'] ?? '',
            'role_id_init' => $roleId ?? 1,
            'post_priv' => "0",
            'wap_allow' => 0,
            'user_status' => 1,
            'dept_id' => $data['dept_id'] ?? 1,
            'sex' => "1",
            'user_superior_id' => "",
            'user_subordinate_id' => "",
            'list_number' => "",
            'is_autohrms' => $data['is_autohrms'] ?? 0,
            'user_password' => $data['password'] ?? '',
            'phone_number' => $data['phone'] ?? '',
        ];
        $oldSecuritySwitch = get_system_param('login_password_security_switch');
        $oldForceChange = get_system_param('force_change_password');
        if ($oldSecuritySwitch && $oldForceChange) {
            $userData['change_pwd'] = 1;
        }
        return $this->userSystemCreate($userData, $loginUserInfo);
    }

    private function getDefaultRole() {
        $role = app($this->roleRepository)->getOneRole(['role_id' => [1, '!=']]);
        if (!empty($role) && isset($role->role_id)) {
            return $role->role_id;
        } else {
            return 1;
        }
    }
    /**
     * 外发新建用户
     * @param  [array] $data
     * @return [array]
     */
    public function flowOutSendToUser($data)
    {
        // 判断是否有新建用户的菜单权限
        $menuPermission = app('App\EofficeApp\Menu\Services\UserMenuService')->judgeMenuPermission(98);
        if ($menuPermission == 'false') {
            return ['code' => ['0x005118', 'user']];
        }
        // 验证必填
        if (empty($data)) {
            return ['code' => ['0x000003', 'common']];
        }
        $userId = $data['current_user_id'] ?? '';
        // 检查必填
        $requiredTip = $this->checkUserDataRequired($data);
        if (!empty($requiredTip) && isset($requiredTip['code'])) {
            return $requiredTip;
        }
        $inputRoleIdInit = $data['role_id_init'] ?? '';
        if (!empty($inputRoleIdInit)) {
            // 判断用户角色级别权限
            if (!$this->checkUserRolePermission(own(), ['role_id_init' => $inputRoleIdInit])) {
                return ['code' => ['0x005120', 'user']];
            }
        }

        // 判断多选
        $checkResult = $this->checkOutSendMultiSelect($data);
        if (isset($checkResult['code'])) {
            return $checkResult;
        }
        $userData = [
          'user_accounts' => isset($data['user_accounts']) && !empty($data['user_accounts']) ? $data['user_accounts'] : $data['user_name'],
          'user_name' => $data['user_name'] ?? '',
          'phone_number' => $data['phone_number'] ?? '',
          'user_job_number' => $data['user_job_number'] ?? '',
          'user_password' => $data['user_password'] ?? '',
          'is_dynamic_code' => $data['is_dynamic_code'] ?? 0,
          'is_autohrms' => $data['is_autohrms'] ?? 0,
          'sex' => isset($data['sex']) && (!empty($data['sex']) || $data['sex'] == 0) ? $data['sex'] : 1,
          'wap_allow' => $data['wap_allow'] ?? 0,
          'user_status' => isset($data['user_status']) && !empty($data['user_status']) ? $data['user_status'] : 1,
          'dept_id' => $data['dept_id'] ?? '',
          'user_position' => $data['user_position'] ?? '',
          'attendance_scheduling' => $data['attendance_scheduling'] ?? '',
          'role_id_init' => $data['role_id_init'] ?? '',
          'user_superior_id' => $data['superior_id_init'] ?? '',
          'user_subordinate_id' => $data['subordinate_id_init'] ?? '',
          'post_priv' => $data['post_priv'] ?? 0,
          'list_number' => $data['list_number'] ?? '',
          'user_area' => $data['user_area'] ?? '',
          'user_city' => $data['user_city'] ?? '',
          'user_workplace' => $data['user_workplace'] ?? '',
          'user_job_category' => $data['user_job_category'] ?? '',
          'birthday' => $data['birthday'] ?? '',
          'email' => $data['email'] ?? '',
          'oicq_no' => $data['oicq_no'] ?? '',
          'weixin' => $data['weixin'] ?? '',
          'dept_phone_number' => $data['dept_phone_number'] ?? '',
          'faxes' => $data['faxes'] ?? '',
          'home_address' => $data['home_address'] ?? '',
          'home_zip_code' => $data['home_zip_code'] ?? '',
          'home_phone_number' => $data['home_phone_number'] ?? '',
          'notes' => $data['notes'] ?? '',
        ];
        if (isset($data['sex']) && $data['sex'] === 0) {
            $userData['sex'] = 0;
        } else {
           $userData['sex'] = 1;
        }

        // 判断是否有上下级交集
        if ($userData['user_superior_id'] && $userData['user_subordinate_id']) {
           $insectin = array_intersect(explode(',', $userData['user_superior_id']), explode(',', $userData['user_subordinate_id']));
            if (!empty($insectin)) {
                return ['code' => ['0x005116', 'user']];
            }
            // 处理上下级循环
            $checkInsect = $this->checkUserSubSup($userData['user_subordinate_id'], $userData['user_superior_id']);
            if (isset($checkInsect['code'])) {
                return $checkInsect;
            }
        }
        if ($userData['user_subordinate_id']) {
            // 判断当前人员是否在上下级中
            $subUserId = explode(',', $userData['user_subordinate_id']);
            // 判断有没有离职用户
            $leaveSubUser = DB::table("user_system_info")->whereIn('user_id', $subUserId)->where('user_status', 2)->get()->toArray();
            if (!empty($leaveSubUser)) {
                return ['code' => ['0x005128', 'user']];
            }
        }
        if ($userData['user_superior_id']) {
            $supUserId = explode(',', $userData['user_superior_id']);
            $leaveSupUser = DB::table("user_system_info")->whereIn('user_id', $supUserId)->where('user_status', 2)->get()->toArray();
            if (!empty($leaveSupUser)) {
                return ['code' => ['0x005129', 'user']];
            }
        }

        if ($userData['is_dynamic_code'] == 1) {
            $userData['sn_number'] = $data['sn_number'] ?? '';
            $userData['dynamic_code'] = $data['dynamic_code'] ?? '';
            if (!$userData['sn_number']) {
                return ['code' => ['0x005093', 'user']];
            }
            if (!$userData['dynamic_code']) {
                return ['code' => ['0x005094', 'user']];
            }
        }
        if ($userData['post_priv'] == 2) {
            $userData['post_dept'] = $data['post_dept'] ?? '';
            if (!$userData['post_dept']) {
                return ['code' => ['0x005095', 'user']];
            }
        }
        // 验证邮箱
        if (!empty($userData['email']) && !check_email($userData['email'])) {
            return ['code' => ['0x005082', 'user']];
        }
        if ($userData['user_status'] == 2) {
            return ['code' => ['0x005112', 'user']];
        }
        $return =  $this->userSystemCreate($userData, own());
        if ($return && isset($return['code'])) {
            return $return;
        }
        return [
            'status' => 1,
            'dataForLog' => [
                [
                    'table_to' => 'user',
                    'field_to' => 'user_id',
                    'id_to'    => $return->user_id
                ]
            ]
        ];
    }
    private function checkUserSubSup($subId, $supId)
    {
        if (!$subId || !$supId) {
            return true;
        }
        $subIdArr = explode(',', $subId);
        $supIdArr = explode(',', $supId);

        $supParam = [
            'all_superior' => true,
            'user_id' => $supIdArr
        ];
        $subParam = [
            'all_subordinate' => true,
            'user_id' => $subIdArr
        ];

        // 上级的上级是否在下级中, 下级的下级是否在上级中
        $allSupUserId = $this->getSuperiorArrayByUserIdArr($supParam);
        // 下级
        $allSubUserId = $this->getSubordinateArrayByUserIdArr($subParam);
        $allSupUserId =  isset($allSupUserId['id']) ? $allSupUserId['id'] : $allSupUserId;
        $allSubUserId = isset($allSubUserId['id']) ? $allSubUserId['id'] : $allSubUserId;
        $insectSub = array_intersect($allSupUserId, $subIdArr);
        $insectSup = array_intersect($allSubUserId, $supIdArr);
        if (!empty($insectSub)) {
            return ['code' => ['0x005126', 'user']];
        }
        if (!empty($insectSup)) {
           return ['code' => ['0x005127', 'user']];
        }
        return true;
    }

    public function flowOutSendToUpdateUser($data) {
        // 判断是否有新建用户的菜单权限
        $menuPermission = app('App\EofficeApp\Menu\Services\UserMenuService')->judgeMenuPermission(98);
        if ($menuPermission == 'false') {
            return ['code' => ['0x005118', 'user']];
        }
        //
        // 验证必填
        if (!isset($data['data']) || empty($data['data'])) {
            return ['code' => ['0x000003', 'common']];
        }
        if (!isset($data['unique_id']) || empty($data['unique_id'])) {
            return ['code' => ['0x005097', 'user']];
        }
        $userId = $data['unique_id'];
        $inputData = $data['data'] ?? [];
        if ($userId == 'admin' && $inputData['current_user_id'] != $userId) {
            return ['code' => ['0x005115', 'user']];
        }
        // 对admin特殊处理
        if ($userId == 'admin') {
            if (isset($inputData['user_status']) && $inputData['user_status'] == 2) {
                return ['code' => ['0x005114', 'user']];
            }
            if (isset($inputData['post_priv']) && !empty($inputData['post_priv'])) {
                return ['code' => ['0x005119', 'user']];
            }
        }
        // 判断用户是否已删除
        $info = DB::table('user_system_info')->select('user_status')->where('user_id', $userId)->first();
        if ($info && $info->user_status == 0) {
            return ['code' => ['0x005117', 'user']];
        }
        $currentUserId = $data['data']['current_user_id'] ?? [];
        //$own = own() ?? [];
        $own = (array)$this->getLoginUserInfo($currentUserId);
        // 判断是否在管理范围内
        // 验证是否有权限删除数据
        if (!$this->checkUserDeletePermission($userId, $own)) {
            $userInfo = DB::table('user')->select('user_name')->where('user_id', $userId)->first();
            $userName = $userInfo->user_name ?? '';
            return [
                    'code' => ['0x005119', 'user'],
                    'dynamic' => trans('user.0x005121'). $userName .trans('user.0x005119')
                ];
        };

        $inputRole = $inputData['role_id_init'] ?? '';
        if (!empty($inputRole)) {
             if (!$this->checkUserRolePermission($own, ['user_id' => $userId, 'role_id_init' => $inputRole])) {
                return ['code' => ['0x005120', 'user']];
            }
        }
        // 判断多选
        $checkResult = $this->checkOutSendMultiSelect($inputData);
        if (isset($checkResult['code'])) {
            return $checkResult;
        }
        // 获取用户信息
        $allUserData = $this->getUserAllData($userId);
        if (empty($allUserData)) {
            return ['code' => ['0x005097', 'user']];
        }
        if (!is_array($allUserData)) {
            $allUserData = $allUserData->toArray();
        }

        $roleId = implode(array_column($allUserData['user_has_many_role'], 'role_id'));

        $userData = [
          'user_id' => $userId,
          'user_accounts' => isset($inputData['user_accounts']) && !empty($inputData['user_accounts']) ? $inputData['user_accounts'] : $allUserData['user_accounts'],
          'user_name' => isset($inputData['user_name']) ? $inputData['user_name'] : $allUserData['user_name'],
          'phone_number' => isset($inputData['phone_number']) ? $inputData['phone_number'] : $allUserData['user_has_one_info']['phone_number'],
          'user_job_number' => isset($inputData['user_job_number']) ? $inputData['user_job_number'] : $allUserData['user_job_number'],
          'is_dynamic_code' => isset($inputData['is_dynamic_code']) ? $inputData['is_dynamic_code'] : 0,
          'is_autohrms' => $inputData['is_autohrms'] ?? 0,
          'sex' => isset($inputData['sex']) ? $inputData['sex'] :  $allUserData['user_has_one_info']['sex'],
          'wap_allow' => isset($inputData['wap_allow']) ? $inputData['wap_allow'] : $allUserData['user_has_one_system_info']['wap_allow'],
          'user_status' => isset($inputData['user_status']) && !empty($inputData['user_status']) ? $inputData['user_status'] : $allUserData['user_has_one_system_info']['user_status'],
          'dept_id' => isset($inputData['dept_id']) ? $inputData['dept_id'] : $allUserData['user_has_one_system_info']['dept_id'],
          'user_position' => isset($inputData['user_position']) ? $inputData['user_position'] : $allUserData['user_position'],
          'attendance_scheduling' => isset($inputData['attendance_scheduling']) ? $inputData['attendance_scheduling'] : (isset($allUserData['user_has_one_attendance_scheduling_info']) && !empty($allUserData['user_has_one_attendance_scheduling_info']) ? $allUserData['user_has_one_attendance_scheduling_info']['scheduling_id'] : ''),
          'role_id_init' => $inputData['role_id_init'] ?? $roleId,
          'user_superior_id' => $inputData['superior_id_init'] ?? '',
          'user_subordinate_id' => $inputData['subordinate_id_init'] ?? '',
          'post_priv' => $inputData['post_priv'] ?? 0,
          'list_number' => $inputData['list_number'] ?? $allUserData['list_number'],
          'user_area' => $inputData['user_area'] ?? $allUserData['user_area'],
          'user_city' => $inputData['user_city'] ?? $allUserData['user_city'],
          'user_workplace' => $inputData['user_workplace'] ?? $allUserData['user_workplace'],
          'user_job_category' => $inputData['user_job_category'] ?? $allUserData['user_job_category'],
          'birthday' => $inputData['birthday'] ?? $allUserData['user_has_one_info']['birthday'],
          'email' => $inputData['email'] ?? $allUserData['user_has_one_info']['email'],
          'oicq_no' => $inputData['oicq_no'] ?? $allUserData['user_has_one_info']['oicq_no'],
          'weixin' => $inputData['weixin'] ?? $allUserData['user_has_one_info']['weixin'],
          'dept_phone_number' => $inputData['dept_phone_number'] ?? $allUserData['user_has_one_info']['dept_phone_number'],
          'faxes' => $inputData['faxes'] ?? $allUserData['user_has_one_info']['faxes'],
          'home_address' => $inputData['home_address'] ?? $allUserData['user_has_one_info']['home_address'],
          'home_zip_code' => $inputData['home_zip_code'] ?? $allUserData['user_has_one_info']['home_zip_code'],
          'home_phone_number' => $inputData['home_phone_number'] ?? $allUserData['user_has_one_info']['home_phone_number'],
          'notes' => $inputData['notes'] ?? $allUserData['user_has_one_info']['notes'],
        ];
        if (isset($inputData['sex']) && $inputData['sex'] === 0) {
            $userData['sex'] = 0;
        } else if (isset($inputData['sex']) && ($inputData['sex'] === '' || $inputData['sex'] == 1)) {
           $userData['sex'] = 1;
        } else {
            $userData['sex'] = $allUserData['user_has_one_info']['sex'] ?? 1;
        }
        // 检查必填
        $requiredTip = $this->checkUserDataRequired($userData);
        if (!empty($requiredTip) && isset($requiredTip['code'])) {
            return $requiredTip;
        }
        // 判断是否有上下级交集
        if ($userData['user_superior_id'] && $userData['user_subordinate_id']) {
            $insectin = array_intersect(explode(',', $userData['user_superior_id']), explode(',', $userData['user_subordinate_id']));
            if (!empty($insectin)) {
                return ['code' => ['0x005116', 'user']];
            }
            // 处理上下级循环
            $checkInsect = $this->checkUserSubSup($userData['user_subordinate_id'], $userData['user_superior_id']);
            if (isset($checkInsect['code'])) {
                return $checkInsect;
            }
        }
        if ($userData['user_subordinate_id']) {
            // 判断当前人员是否在上下级中
            $subUserId = explode(',', $userData['user_subordinate_id']);
            if (in_array($userId, $subUserId)) {
                return ['code' => ['0x005124', 'user']];
            }
            // 判断有没有离职用户
            $leaveSubUser = DB::table("user_system_info")->whereIn('user_id', $subUserId)->where('user_status', 2)->get()->toArray();
            if (!empty($leaveSubUser)) {
                return ['code' => ['0x005128', 'user']];
            }
        }
        if ($userData['user_superior_id']) {
            $supUserId = explode(',', $userData['user_superior_id']);
            if (in_array($userId, $supUserId)) {
                return ['code' => ['0x005123', 'user']];
            }
            $leaveSupUser = DB::table("user_system_info")->whereIn('user_id', $supUserId)->where('user_status', 2)->get()->toArray();
            if (!empty($leaveSupUser)) {
                return ['code' => ['0x005129', 'user']];
            }
        }
        if (isset($inputData['user_password'])) {
            $userData['password'] = $inputData['user_password'];
        }
        if ($userData['is_dynamic_code'] == 1) {
            $userData['sn_number'] = $inputData['sn_number'] ?? '';
            $userData['dynamic_code'] = $inputData['dynamic_code'] ?? '';
            if (!$userData['sn_number']) {
                return ['code' => ['0x005093', 'user']];
            }
            // if (!$userData['dynamic_code']) {
            //     return ['code' => ['0x005094', 'user']];
            // }
        }
        if ($userData['post_priv'] == 2) {
            $userData['post_dept'] = $inputData['post_dept'] ?? '';
            if (!$userData['post_dept']) {
                return ['code' => ['0x005095', 'user']];
            }
        }
        $return =  $this->userSystemEdit($userData, own());
        if ($return && isset($return['code'])) {
            return $return;
        }
        return [
            'status' => 1,
            'dataForLog' => [
                [
                    'table_to' => 'user',
                    'field_to' => 'user_id',
                    'id_to'    => $userId
                ]
            ]
        ];
    }

    //添加编辑用户时，用户角色级别不能高于当前登录用户的最高用户级别
    private function checkUserRolePermission($own, $data)
    {
        if ($own['user_id'] === 'admin') {
            return true;
        }

        //编辑时需要判断用户权限
        if (isset($data['user_id'])) {
            $status = $this->compareUserRole($own['user_id'], $data['user_id']);
            if (!$status) {
                return false;
            }
        }
        $roleIdString = Arr::get($data, 'role_id_init');
        if (!$roleIdString) {
            return false;
        }
        $roleIds = explode(',', $roleIdString);
        $minRoleNo = app($this->userRoleRepository)->buildUserRolesQuery($own['user_id'])->min('role_no');
        $testMinRoleNo = app($this->roleRepository)->entity->whereIn('role_id', $roleIds)->min('role_no');
        return $testMinRoleNo > $minRoleNo;
    }

    public function flowOutSendToDeleteUser($data)
    {
        // 判断是否有新建用户的菜单权限
        $menuPermission = app('App\EofficeApp\Menu\Services\UserMenuService')->judgeMenuPermission(98);
        if ($menuPermission == 'false') {
            return ['code' => ['0x005118', 'user']];
        }
        // 验证必填
        if (!isset($data['data']) || empty($data['data'])) {
            return ['code' => ['0x000003', 'common']];
        }
        if (!isset($data['unique_id']) || empty($data['unique_id'])) {
            return ['code' => ['0x005097', 'user']];
        }
        $userId = $data['unique_id'];
        $currentUserId = $data['data']['current_user_id'] ?? [];
        $own = (array)$this->getLoginUserInfo($data['data']['current_user_id']);
        // 判断用户是否已删除
        $info = DB::table('user_system_info')->select('user_status')->where('user_id', $userId)->first();
        if ($info && $info->user_status == 0) {
            return ['code' => ['0x005117', 'user']];
        }
        // 验证是否有权限删除数据
        if (!$this->checkUserDeletePermission($userId, $own)) {
            $userInfo = DB::table('user')->select('user_name')->where('user_id', $userId)->first();
            $userName = $userInfo->user_name ?? '';
            return [
                    'code' => ['0x005119', 'user'],
                    'dynamic' => trans('user.0x005121'). $userName .trans('user.0x005119')
                ];
        };

        $return =  $this->userSystemDelete($userId, $own['user_id']);
        if (isset($return['code'])) {
            return $return;
        }
        return [
            'status' => 1,
            'dataForLog' => [
                [
                    'table_to' => 'user',
                    'field_to' => 'user_id',
                    'id_to'    => $userId
                ]
            ]
        ];
    }
    /**
     * 外发判断不允许多选字段
     * @return [type] [description]
     */
    private function checkOutSendMultiSelect($data)
    {

        $FieldArr = [
            'dept_id' => $data['dept_id'] ?? '',
            'user_status' => $data['user_status'] ?? '',
            'user_position' => $data['user_position'] ?? '',
            'attendance_scheduling' => $data['attendance_scheduling'] ?? '',
            'user_area' => $data['user_area'] ?? '',
            'user_city' => $data['user_city'] ?? '',
            'user_workplace' => $data['user_workplace'] ?? '',
            'user_job_category' => $data['user_job_category'] ?? '',
        ];
        foreach ($FieldArr as $key => $value) {
            if (count(array_filter(explode(',', $value))) > 1) {
                return [
                    'code' => ['0x005089', 'user'],
                    'dynamic' => trans('user.'.$key).' '.trans('user.not_allowed_multi_select')
                ];
            }
        }
        return true;
        // 部门
        //
    }
    private function checkUserDeletePermission($userId, $own)
    {
        if ($own['user_id'] === 'admin') {
            return true;
        }
        $deleteUserId = $userId;
        if ($deleteUserId == 'admin') {
            return false;
        }
        // 获取可管理部门id
        $deptIds = $this->getUserCanManageDepartmentId($own);
        if ($deptIds != 'all') {
            $deleteUserDeptId = $this->getDeptInfoByUserId($deleteUserId);
            if (isset($deleteUserDeptId['dept_id']) && !in_array($deleteUserDeptId['dept_id'], $deptIds)) {
                return false;
            }
        }
        return $this->compareUserRole($own['user_id'], $deleteUserId);
    }
    /**
     * 比较两用户的权限大小，不含权限相等
     * @param string $maxUserId  权限大的用户id
     * @param string $minUserId  权限小的用户id
     * @return bool 符合预期返回true，否则false
     */
    private function compareUserRole($maxUserId, $minUserId)
    {
        $userMaxRoleNos = app($this->userSystemInfoEntity)
            ->whereIn('user_id', [$maxUserId, $minUserId])
            ->pluck('max_role_no', 'user_id');
        if (count($userMaxRoleNos) !== 2) {
            return false;
        }
        return $userMaxRoleNos[$maxUserId] < $userMaxRoleNos[$minUserId];
    }
    // 验证必填
    private function checkUserDataRequired($data)
    {
        if (!empty($data)) {
            if (!isset($data['user_name']) || empty($data['user_name'])) {
               return ['code' => ['0x005008', 'user']];
            }
            if (isset($data['list_number']) && !empty($data['list_number'])) {
                if (!is_numeric($data['list_number'])) {
                    return ['code' => ['0x005113', 'user']];
                }

            }
            // 去除用户账号, 角色ID, 性别和用户状态的导入必填
            // if (!isset($data['user_accounts']) || empty($data['user_accounts'])) {
            //      return ['code' => ['0x005009', 'user']];
            // }
            // if (!isset($data['role_id_init']) || empty($data['role_id_init'])) {
            //      return ['code' => ['0x005010', 'user']];
            // }
            // if (!isset($data['sex']) || trim($data['sex']) === '') {
            //      return ['code' => ['0x005012', 'user']];
            // }
            // if (!isset($data['user_status']) || empty($data['user_status'])) {
            //      return ['code' => ['0x005014', 'user']];
            // }
            if (!isset($data['dept_id']) || empty($data['dept_id'])) {
                return ['code' => ['0x005011', 'user']];
            }

            if (isset($data['email']) && $data['email'] && !check_email($data['email'])) {
                return ['code' => ['0x005082', 'user']];
            }


            if (isset($data['user_password'])) {
                return $this->parseImportPassword($data['user_password']);
            }
        }
    }


    /**
     * 用户管理--新建用户
     *
     * @author 丁鹏
     *
     * @param  array $data [description]
     *
     * @since  2015-10-16 创建
     *
     * @return array    返回结果
     */
    public function userSystemCreate($data, $loginUserInfo = [])
    {
        // 新建用户工号
        $userJobNumber = $this->generateUserJobNumber($data);
        if (isset($userJobNumber['code'])) {
            return $userJobNumber;
        } else {
            $dataUser['user_job_number'] = $userJobNumber['user_job_number'] ?? '';
            $dataUser['user_job_number_seq'] = $userJobNumber['user_job_number_seq'] ?? '';
        }

        $loginUserId = $loginUserInfo['user_id'] ?? '';
        $checkUserAccount = $this->validateUserAccountsValidation($data['user_accounts']);
        if (!$checkUserAccount) {
            return ['code' => ['0x005015', 'user']];
        }
        if (!$this->checkPcUserNumberWhetherExceed()) {
            return ['code' => ['0x005007', 'user']];
        }
        if (isset($data['wap_allow']) && $data['wap_allow'] == '1' && !$this->checkMobileUserNumberWhetherExceed()) {
            return ['code' => ['0x005018', 'user']];
        }
        $checkResult = $this->checkUserInfoUnique($data, '');
        if (!empty($checkResult)) {
            return $checkResult;
        }
        if (isset($data['post_priv']) && ($data['post_priv'] != '0' && $data['post_priv'] != '1' && $data['post_priv'] != '2')) {
            return ['code' => ['0x005020', 'user']];
        }
        $loginUserMenus = $loginUserInfo['menus']['menu'] ?? [];
        $permissionModule = app($this->empowerService)->getPermissionModules();
        if (empty($permissionModule)) {
            $checkAttendanceMenu = false;
        } else {
            $checkAttendanceMenu = in_array('32', $permissionModule);
        }
        // 如果没有考勤模块排班设置菜单，去掉用户考勤数据
        if (!$checkAttendanceMenu) {
            if (isset($data['attendance_scheduling'])) {
                unset($data['attendance_scheduling']);
            }
        }
        $checkUserDataResult = $this->checkUserData($data);
        if (!empty($checkUserDataResult)) {
            // 默认排班是1，如果没有1，则存空
            if ($checkUserDataResult == 2) {
                $data['attendance_scheduling'] = '';
            } else {
                return $checkUserDataResult;
            }
        }
        // 生成用户id： user_id
        $userId = app($this->userRepository)->getNextUserIdBeforeCreate();
        //创建用户头像
        $this->makeUserAvatar($userId, $data["user_name"]);
        $data["user_id"] = $userId;
        $dataUser["user_id"] = $userId;
        $password = "";
        if (isset($data["user_password"])) {
            $password = $data["user_password"];
        }
        // 判断密码是否包含中文
        if (preg_match("/([\x81-\xfe][\x40-\xfe])/", $password, $match)) {
            return ['code' => ['0x005098','user'], 'dynamic' => trans('user.0x005098')];
        }
        // if (!$from) {
        //     // 判断是否开启了密码强度设置
        //     $return = $this->parsePassword($password);
        //     if (isset($return['code'])) {
        //         return $return;
        //     }
        // }

        $dataUser["password"] = crypt($password, null);
        $userNamePyArray = convert_pinyin($data["user_name"]);
        $dataUser["user_name_py"] = $userNamePyArray[0];
        $dataUser["user_name_zm"] = $userNamePyArray[1];
        $dataUser["user_name"] = $data["user_name"];
        if (isset($data['user_status']) && $data['user_status'] == 2) {
            $data['user_accounts'] = '';
        }
        $dataUser["user_accounts"] = $data["user_accounts"];
        if (isset($data["list_number"]) && !empty($data["list_number"])) {
            $dataUser["list_number"] = $data["list_number"];
        } else {
            $dataUser["list_number"] = app($this->userRepository)->getUserNextListNumber();
        }
        // 用户上下级录入
        if (isset($data['user_superior_id']) && $data['user_superior_id'] == 'all') {
            $data['user_superior_id'] = $this->getAllUserIdString();
        } elseif (isset($data['user_subordinate_id']) && $data['user_subordinate_id'] == 'all') {
            $data['user_subordinate_id'] = $this->getAllUserIdString();
        }
        // 添加角色
        if (isset($data['role_id_init'])) {
            if ($data['role_id_init'] == 'all') {
                $roleData = $this->getUserCanManageRoleId($loginUserId);
                if ($roleData) {
                    $data['role_id'] = join(',', $roleData);
                } else {
                    $data['role_id'] = '';
                }
            } else {
                $checkUserRole = $this->judgeUserRoleIdInRoleList($data['role_id_init']);
                if ($checkUserRole != '1') {
                    return $checkUserRole;
                }
                $data['role_id'] = $data['role_id_init'];
            }
            $userRoleDataCreateResult = app($this->roleService)->addUserRole($data);
            $userMaxRoleNo = app($this->roleService)->getMaxRoleNoFromData($data['role_id']);
        }
        $dataUser['user_position'] = isset($data['user_position']) ? $data['user_position'] : '';
        $dataUser['user_area'] = isset($data['user_area']) ? $data['user_area'] : '';
        $dataUser['user_city'] = isset($data['user_city']) ? $data['user_city'] : '';
        $dataUser['user_workplace'] = isset($data['user_workplace']) ? $data['user_workplace'] : '';
        $dataUser['user_job_category'] = isset($data['user_job_category']) ? $data['user_job_category'] : '';
        $dataUser['change_pwd'] = $data['change_pwd'] ?? 0;
        // 用户基础表信息录入
        $userDataCreateResult = app($this->userRepository)->insertData($dataUser);
        // systeminfo表信息录入
        $dataSystemInfoUser = array_intersect_key($data, array_flip(app($this->userSystemInfoRepository)->getTableColumns()));
        if (isset($userMaxRoleNo)) {
            $dataSystemInfoUser['max_role_no'] = $userMaxRoleNo;
        }
        if (!isset($dataSystemInfoUser['last_pass_time'])) {
            $dataSystemInfoUser['last_pass_time'] = date('Y-m-d h:i:s');
        }
        $userSystemInfoDataCreateResult = app($this->userSystemInfoRepository)->insertData($dataSystemInfoUser);
        // info表信息录入
        $dataInfoUser = array_intersect_key($data, array_flip(app($this->userInfoRepository)->getTableColumns()));
        $userInfoDataCreateResult = app($this->userInfoRepository)->insertData($dataInfoUser);
        app($this->roleService)->addUserSuperior($data);
        // 插入用户菜单
        $insertUserMenuResult = app($this->userMenuService)->insertUserMenu($userId);
        $message = '';
        $data['user_job_number'] = $dataUser['user_job_number'];
        $this->createOrUpdateUserCache($dataUser, 'new');
        try {
            // 更新用户关联信息
            $this->updateUserCorrelationData($data, 'new',$loginUserInfo);
        } catch (ErrorMessage $e) {
            $message = $e->getErrorMessage();
        }
        // 获取用户部门
        $deptName = '';
        if (isset($data['dept_id'])) {
            $deptName = DB::table('department')->select('dept_name')->where('dept_id', $data['dept_id'])->first();
            if (isset($deptName)) {
                $deptName = $deptName->dept_name;
            } else {
                $deptName = '';
            }
        }
        // 添加日志
        $logData = [
            'log_content' => trans('user.create_user') . ': 【' . $deptName . '】' . $data['user_name'] . '，user_id ：' . $userId,
            'log_type' => 'add',
            'log_creator' => $loginUserId,
            'log_ip' => getClientIp(),
            'log_relation_table' => 'user',
            'log_relation_id' => $userId,
            'module' => 'user'
        ];
        if ($message) {
            $logData['log_content'] = $logData['log_content']. ' '. trans('user.0x005045') . ': ' . $message;
        }
//        add_system_log($logData);
        $identifier  = "system.user.add";
        $logParams = $this->handleLogParams($loginUserId, $logData['log_content'], $userId, 'user', $data['user_name']);
        logCenter::info($identifier , $logParams);
        $this->updateUserNumberFile();

        // 全站搜索消息队列更新数据
        $this->updateGlobalSearchDataByQueue($userId);

        $updateResult = $this->updateUserNumberFile();
        if(is_array($updateResult) && isset($updateResult['code'])) {
            return $updateResult;
        }

        return $userDataCreateResult;
    }
    // 更新服务管理平台用户数量文件
    public function updateUserNumberFile() {
        // 获取系统用户数量
        $where = [
            'user_system_info' => [
                'user_status' => [[0, 2], 'not_in']
            ]
        ];
        $sysUserNumber = app('App\EofficeApp\User\Repositories\UserRepository')->getUserNumber($where);

        $binDir = get_bin_dir();
        if (!is_dir($binDir)) {
            dir_make($binDir, 0777);
        }
        $fileName = $binDir . 'usernumber.json';
        // 20200515-丁鹏
        // chmod($binDir, 0777);
        $dirPermission = verify_dir_permission($binDir);
        if(is_array($dirPermission) && isset($dirPermission['code'])) {
            return $dirPermission;
        }
        $content = ['number' => $sysUserNumber];
        // 20200623-丁鹏，部署的时候，已经要求linux的此文件为777了，不需要chmod
        // if (is_file($fileName)) {
            // chmod($fileName, 0777);
        // }
        $dirPermission = verify_file_permission($fileName);
        if(is_array($dirPermission) && isset($dirPermission['code'])) {
            return $dirPermission;
        }
        file_put_contents($fileName, json_encode($content));

        return true;
    }
    /**
     * 创建用户头像
     * @param type $userId
     * @param type $userName
     * @param type $prefix
     * @return boolean
     */
    private function makeUserAvatar($userId, $userName)
    {
        $font = base_path() . '/public/fonts/msyhbd.ttc';
        $rgb = $this->getRandomRGBColor();
        $avatar = imagecreatetruecolor(60, 60);
        $backgroundColor = imagecolorallocate($avatar, $rgb[0], $rgb[1], $rgb[2]);
        $textColor = imagecolorallocate($avatar, 255, 255, 255);
        imagefilledrectangle($avatar, 0, 0, 60, 60, $backgroundColor);
        $avatarText = $this->getAvatarText($userName);
        $left = $this->getAvatarTextWidth($avatarText);
        imagettftext($avatar, 15, 0, $left, 35, $textColor, $font,  $avatarText);
        imagepng($avatar, $this->getAvatarPath($userId));
        imagedestroy($avatar);

        return true;
    }
    private function getAvatarTextWidth($text)
    {
        $width = 0;
        if($text){
            preg_match_all("/./u", $text, $matches);

            $texts = $matches[0];

            for ($i = sizeof($texts) - 1; $i >= 0; $i--) {
                if(preg_match("/[0-9a-z]/", $texts[$i])){
                    if(in_array($texts[$i],['l',1])){
                        $width += 5;
                    } else {
                        $width += 13;
                    }
                } else if (preg_match("/[A-Z]/", $texts[$i])){
                    $width += 16;
                } else {
                    $width += 20;
                }
            }
        }

        return ceil((60 - $width) / 2);
    }
    private function getAvatarPath($userId, $prefix = 'EO')
    {
        $userIdCode = 0;
        $numberJoin = '';
        for ($i = 0; $i < strlen($userId); $i++) {
            $charAscii = ord($userId[$i]);
            if(is_numeric($userId[$i])){
                $numberJoin .= $userId[$i];
            }
            $userIdCode += $charAscii;
        }
        $prefixCode = '';
        for ($i = 0; $i < strlen($prefix); $i++) {
            $charAscii = ord($prefix[$i]);
            $prefixCode .= $charAscii;
        }
        return access_path('images/avatar/') . $prefix . (($userIdCode * $prefixCode) + intval($numberJoin)) . '.png';
    }
    private function getAvatarText($userName)
    {
        if (strlen($userName) < 4) {
            return $userName;
        }
        preg_match_all("/./u", $userName, $matches);

        $texts = $matches[0];
        $limit = 0;
        $filterTexts = [];
        for ($i = sizeof($texts) - 1; $i >= 0; $i--) {
            $limit = preg_match("/[0-9a-zA-Z]/", $texts[$i]) ? $limit + 2 : $limit + 3;
            if ($limit > 6) {
                break;
            }
            $filterTexts[] = $texts[$i];
        }

        $avatarText = '';
        for ($i = sizeof($filterTexts) - 1; $i >= 0; $i--) {
            $avatarText .= $filterTexts[$i];
        }

        return $avatarText;
    }
    private function getRandomRGBColor() {
        $colors = [
            [42, 181, 246], [255, 163, 44], [237, 88, 84], [125, 198, 83],
            [113, 144, 250], [37, 198, 216], [253, 198, 46], [37, 168, 154], [245, 113, 65]
        ];
        return $colors[array_rand($colors, 1)];
    }
    /**
     * 用户管理--检测新建编辑和导入用户的数据正确性
     *
     * @author 缪晨晨
     *
     * @since  2017-06-16 创建
     *
     * @return array 返回错误代码
     */
    public function checkUserData($data)
    {
        // 这里需要把其他的判断数据正确性的整合进来todo...
        if (isset($data['sex']) && $data['sex'] != '0' && $data['sex'] != '1') {
            return ['code' => ['0x005027', 'user']];
        }
        if (isset($data['attendance_scheduling']) && !empty($data['attendance_scheduling'])) {
            $checkAttendance = $this->judgeAttendanceIdInAttendanceList($data['attendance_scheduling']);
            if ($checkAttendance != '1') {
                return $checkAttendance;
            }
        }
        if (isset($data['user_status']) && !empty($data['user_status'])) {
            $checkUserStatus = $this->judgeUserStatusIdInStatusList($data['user_status']);
            if ($checkUserStatus != '1') {
                return $checkUserStatus;
            }
        }
        if (isset($data['dept_id'])) {
            $checkUserDept = $this->judgeUserDeptIdInDeptList($data['dept_id']);
            if ($checkUserDept != '1') {
                return $checkUserDept;
            }
        }
        if (isset($data['user_position']) && !empty($data['user_position']) && isset($data['data_from']) && $data['data_from'] == 'import') {
            $checkUserPosition = $this->judgeUserPositionIdInUserPositionList($data['user_position']);
            if ($checkUserPosition != '1') {
                return $checkUserPosition;
            }
        }
    }

    /**
     * 用户管理--检测系统用户数量是否超过授权数量
     *
     * @author 缪晨晨
     *
     * @since  2017-03-23 创建
     *
     * @return boolean  返回结果
     */
    public function checkPcUserNumberWhetherExceed()
    {
        // 获取授权用户数
        $empowerInfo = app($this->empowerService)->getPcEmpower(0);
        $pcEmpowerUserNumber = isset($empowerInfo['pcUserNumber']) ? $empowerInfo['pcUserNumber'] : '0';
        // 获取系统用户数量
        $where = [
            'user_system_info' => [
                'user_status' => [[0, 2], 'not_in']
            ]
        ];
        $sysUserNumber = app($this->userRepository)->getUserNumber($where);
        return $sysUserNumber < $pcEmpowerUserNumber ? true : false;
    }

    /**
     * 用户管理--检测手机用户数量是否超过授权数量
     *
     * @author 缪晨晨
     *
     * @since  2017-06-06 创建
     *
     * @return boolean  返回结果
     */
    public function checkMobileUserNumberWhetherExceed($userId = "")
    {
        if (!empty($userId)) {
            $checkWapAllow = app($this->userSystemInfoRepository)->checkWapAllow($userId);
            if ($checkWapAllow) {
                return true;
            }
        }
        // 获取手机授权用户数
        $empowerInfo = app($this->empowerService)->getMobileEmpower(0);
        if (!empty($empowerInfo)) {
            $mobileEmpowerUserNumber = isset($empowerInfo['mobileUserNumber']) ? $empowerInfo['mobileUserNumber'] : '0';
            // 获取手机用户数量
            $where = [
                'user_system_info' => [
                    'user_status' => [[0, 2], 'not_in'],
                    'wap_allow' => [1]
                ]
            ];
            $mobileUserNumber = app($this->userRepository)->getUserNumber($where);
            return $mobileUserNumber < $mobileEmpowerUserNumber ? true : false;
        } else {
            return true;
        }
    }

    /**
     * 用户管理--验证用户名输入规则
     *
     * @author 缪晨晨
     *
     * @since  2017-04-19 创建
     *
     * @return boolean  返回结果
     */
    public function validateUserAccountsValidation($userAccount)
    {
        $rule = '(^[a-zA-Z0-9\x{2E80}-\x{FE4F}\s._@]+)u';
        $userAccount = preg_replace($rule, "", $userAccount);
        if (empty($userAccount)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 用户管理--编辑用户
     *
     * @author 丁鹏
     *
     * @param  array $data [description]
     *
     * @since  2015-10-16 创建
     *
     * @return array  返回结果
     */
    public function userSystemEdit($data, $loginUserInfo = [])
    {
        $status = $data['user_status'] ?? 0;
        $userStatus = $this->userStatusDetail(['status_id' => $status]);
        if (isset($userStatus['code'])) {
            return $userStatus;
        }
        // 编辑用户工号
        $userJobNumber = $this->generateUserJobNumber($data, true);
        if (isset($userJobNumber['code'])) {
            return $userJobNumber;
        } else {
            $dataUser['user_job_number'] = $userJobNumber['user_job_number'] ?? '';
            $dataUser['user_job_number_seq'] = $userJobNumber['user_job_number_seq'] ?? '';
        }

        $loginUserId = $loginUserInfo['user_id'] ?? '';
        $checkUserAccount = $this->validateUserAccountsValidation($data['user_accounts']);
        if (!$checkUserAccount) {
            return ['code' => ['0x005015', 'user']];
        }
        $checkResult = $this->checkUserInfoUnique($data, 'user_id');
        if (!empty($checkResult)) {
            return $checkResult;
        }
        $userId = $data["user_id"];
        $oldUserInfo = $this->getUserAllData($userId);
        if (!is_array($oldUserInfo)) {
            $oldUserInfo = $oldUserInfo->toArray();
        }
        // $oldUserInfo特殊情况处理：1、code异常；2、没有 user_has_one_system_info
        // 20210222-丁鹏
        if (isset($oldUserInfo['code'])) {
            return $oldUserInfo;
        }
        // 数据异常，当前用户system_info信息缺失
        if(!isset($oldUserInfo['user_has_one_system_info'])) {
            return ['code' => ['0x005130', 'user']];
        }
        // 离职变更为在职或其他状态时查询授权数量
        if ($oldUserInfo['user_has_one_system_info']['user_status'] == '2' && $data['user_status'] != '2') {
            if (!$this->checkPcUserNumberWhetherExceed()) {
                return ['code' => ['0x005007', 'user']];
            }
        }
        if (isset($data['wap_allow']) && $data['wap_allow'] == '1' && !$this->checkMobileUserNumberWhetherExceed($userId)) {
            return ['code' => ['0x005018', 'user']];
        }
        if ($data['user_id'] != 'admin' && isset($data['post_priv']) && ($data['post_priv'] != '0' && $data['post_priv'] != '1' && $data['post_priv'] != '2')) {
            return ['code' => ['0x005020', 'user']];
        }
        $loginUserMenus = $loginUserInfo['menus']['menu'] ?? [];
        $permissionModule = app($this->empowerService)->getPermissionModules();
        if (empty($permissionModule)) {
            $checkAttendanceMenu = false;
        } else {
            $checkAttendanceMenu = in_array('32', $permissionModule);
        }
        // 如果没有考勤模块排班设置菜单，去掉用户考勤数据
        if (!$checkAttendanceMenu) {
            if (isset($data['attendance_scheduling'])) {
                unset($data['attendance_scheduling']);
            }
        }
        $checkUserDataResult = $this->checkUserData($data);
        if (!empty($checkUserDataResult)) {
            // 默认排班是1，如果没有1，则存空
            if ($checkUserDataResult == 2) {
                $data['attendance_scheduling'] = '';
            } else {
                return $checkUserDataResult;
            }
        }
        // 编辑离职用户时，如果填充了用户名，则提示变更状态为非离职
        if ($oldUserInfo['user_has_one_system_info']['user_status'] == '2' && $data['user_status'] == '2') {
            if (isset($data['user_accounts']) && !empty($data['user_accounts'])) {
                return ['code' => ['0x005017', 'user']];
            }
        }
        $dataUser["user_id"] = $userId;
        $userNamePyArray = convert_pinyin($data["user_name"]);
        $dataUser["user_name_py"] = $userNamePyArray[0];
        $dataUser["user_name_zm"] = $userNamePyArray[1];
        $dataUser["user_name"] = $data["user_name"];

        if (isset($data['data_from']) && ($data['data_from'] == 'import') && isset($data["user_password"]) && !empty(trim($data["user_password"]))) {
            // 导入更新用户，如果密码字段填了内容则更新密码，没有填则忽略
            $password = $data["user_password"];
            $dataUser["password"] = crypt($password, null);
        }
        $updateAccounts = $data['user_accounts'];
        if (isset($data['user_status']) && $data['user_status'] == 2) {
            $data['user_accounts'] = '';
            if (isset($data['wap_allow'])) {
                $data['wap_allow'] = '0';
            }
        }
        $dataUser["user_accounts"] = $data["user_accounts"];
        if (isset($data["list_number"])) {
            $dataUser["list_number"] = $data["list_number"];
        }
        // role_id_init 需要额外存储
        $userOldRoleIds = array();
        if (isset($data['role_id_init'])) {
            if ($data['role_id_init'] == 'all') {
                $roleData = $this->getUserCanManageRoleId($loginUserId);
                if ($roleData) {
                    $data['role_id'] = join(',', $roleData);
                } else {
                    $data['role_id'] = '';
                }
            } else {
                $checkUserRole = $this->judgeUserRoleIdInRoleList($data['role_id_init']);
                if ($checkUserRole != '1') {
                    return $checkUserRole;
                }
                $data['role_id'] = $data['role_id_init'];
            }
            $userOldRoles = app($this->userRoleRepository)->getUserRole(['user_id' => $data['user_id']]);
            if (!empty($userOldRoles)) {
                $userOldRoleIds = array_column($userOldRoles, 'role_id');
            }
            $userRoleDataCreateResult = app($this->roleService)->addUserRole($data, true);
            $userMaxRoleNo = app($this->roleService)->getMaxRoleNoFromData($data['role_id']);
        }
        $data['user_old_role_id'] = $userOldRoleIds;
        if (isset($data['user_position'])) {
            $dataUser['user_position'] = $data['user_position'];
        }
        if (isset($data['user_area'])) {
            $dataUser['user_area'] = $data['user_area'];
        }
        if (isset($data['user_city'])) {
            $dataUser['user_city'] = $data['user_city'];
        }
        if (isset($data['user_workplace'])) {
            $dataUser['user_workplace'] = $data['user_workplace'];
        }
        if (isset($data['user_job_category'])) {
            $dataUser['user_job_category'] = $data['user_job_category'];
        }
        // 用户基础表信息更新
        $userDataCreateResult = app($this->userRepository)->updateData($dataUser, ["user_id" => $userId]);
        // 调用api生成或者更新删除用户的缓存
        $this->generateUserCacheByUserStatus($oldUserInfo, $data);
        // 清除用户上下级缓存
        Redis::del(self::USER_SUBORDINATE_REDIS_KEY);
        //创建用户头像
        $this->makeUserAvatar($userId, $data["user_name"]);
        // systeminfo表信息更新
        $dataSystemInfoUser = array_intersect_key($data, array_flip(app($this->userSystemInfoRepository)->getTableColumns()));
        if (isset($userMaxRoleNo)) {
            $dataSystemInfoUser['max_role_no'] = $userMaxRoleNo;
        }
        $userSystemInfoDataCreateResult = app($this->userSystemInfoRepository)->updateData($dataSystemInfoUser, ["user_id" => $userId]);
        // info表信息更新
        $dataInfoUser = array_intersect_key($data, array_flip(app($this->userInfoRepository)->getTableColumns()));
        $userInfoDataCreateResult = app($this->userInfoRepository)->updateData($dataInfoUser, ["user_id" => $userId]);
        // 用户上下级录入
        if (isset($data['user_status']) && $data['user_status'] != 2) {
            //清除流程人员替换缓存的离职人员
            if(Redis::exists('out_users_array')){
                Redis::del('out_users_array');
            }
            if(!empty(Redis::keys('one_user_infos_*'))) {
                Redis::del(Redis::keys('one_user_infos_*'));
            }
            if(!empty(Redis::keys('out_user_infos_*'))) {
                Redis::del(Redis::keys('out_user_infos_*'));
            }

            $data['edit'] = 1;
            if (isset($data['user_superior_id']) && $data['user_superior_id'] == 'all') {
                $tempParam = array('search' => ['user_id' => [[$data['user_id']], 'not_in']]);
                $data['user_superior_id'] = $this->getAllUserIdString($tempParam);
            } elseif (isset($data['user_subordinate_id']) && $data['user_subordinate_id'] == 'all') {
                $tempParam = array('search' => ['user_id' => [[$data['user_id']], 'not_in']]);
                $data['user_subordinate_id'] = $this->getAllUserIdString($tempParam);
            }
            if (isset($data['user_superior_id']) || isset($data['user_subordinate_id'])) {
                app($this->roleService)->addUserSuperior($data);
            }
        }
        // 插入用户菜单
        $insertUserMenuResult = app($this->userMenuService)->insertUserMenu($userId);
        // 添加日志
        $newUserInfo = $this->getUserAllData($userId);
        if (!is_array($newUserInfo)) {
            $newUserInfo = $newUserInfo->toArray();
        }

        // 部门改变信息
        $oldDeptName = $oldUserInfo['user_has_one_system_info']['user_system_info_belongs_to_department']['dept_name'] ?? '';
        $newDeptName = $newUserInfo['user_has_one_system_info']['user_system_info_belongs_to_department']['dept_name'] ?? '';
        // 角色改变信息
        $oldRoleNameContent = array();
        $newRoleNameContent = array();
        foreach ($oldUserInfo['user_has_many_role'] as $value) {
            $oldRoleNameContent[] = $value['has_one_role']['role_name'];
        }
        foreach ($newUserInfo['user_has_many_role'] as $value) {
            $newRoleNameContent[] = $value['has_one_role']['role_name'];
        }
        // 在职状态改变信息
        $oldUserStatus = '';
        $getOldUserStatus = $this->userStatusDetail(['status_id' => $oldUserInfo['user_has_one_system_info']['user_status']]);
        $oldUserStatus = $getOldUserStatus->status_name ?? '';

        $newUserStatus = $this->userStatusDetail(['status_id' => $newUserInfo['user_has_one_system_info']['user_status']]);

        if (isset($newUserStatus['code'])) {
            return $newUserStatus;
        }
        $newUserStatus = $newUserStatus->toArray()['status_name'];
        // 日志备注
        if ($oldUserInfo['user_name'] == $data['user_name']) {
            $logContent = trans('user.edit_user') . ':【' . $oldDeptName . '】' . $data['user_name'] . ',user_id : ' . $userId . ';';
        } else {
            $logContent = trans('user.edit_user') . ':【' . $oldDeptName . '】' . $oldUserInfo['user_name'] . '->【' . $newDeptName . '】' . $data['user_name'] . ',user_id : ' . $userId . ';';
        }
        if ($oldUserStatus != $newUserStatus) {
            $logContent .= trans('user.user_status') . ':' . $oldUserStatus . '->' . $newUserStatus . ';';
        }
        if ($oldDeptName != $newDeptName) {
            $logContent .= trans('user.dept_name') . ':' . $oldDeptName . '->' . $newDeptName . ';';
        }
        if ($oldRoleNameContent != $newRoleNameContent) {
            if ($oldRoleNameContent) {
                $oldRoleNameContent = implode(',', $oldRoleNameContent);
            } else {
                $oldRoleNameContent = '';
            }
            if ($newRoleNameContent) {
                $newRoleNameContent = implode(',', $newRoleNameContent);
            } else {
                $newRoleNameContent = '';
            }
            $logContent .= trans('user.role_name') . ':' . $oldRoleNameContent . '->' . $newRoleNameContent;
        }
        // 用户状态变更离职时，提醒给有人事档案管理菜单的用户
        if (isset($data['user_status']) && $data['user_status'] == 2) {
            $personnelFileId = app($this->personnelFilesRepository)->getPersonnelFilesIdByUserId($data['user_id'], $data['user_name']);
            if (count($personnelFileId) > 0) {
                // $toUser = implode(app($this->userMenuService)->getMenuRoleUserbyMenuId(417), ',');
                $deptId = isset($data['dept_id']) ? $data['dept_id'] : $data['user_has_one_system_info']['dept_id'];
                $toUser = app($this->personnelFilesPermission)->getManger($deptId);
                $sendData['remindMark'] = 'user-leave';
                $sendData['fromUser'] = $loginUserId;
                $sendData['toUser'] = $toUser;
                $sendData['contentParam'] = ['userName' => $data['user_name']];
                $sendData['stateParams'] = ['filesId' => $personnelFileId[0]['id']];
                Eoffice::sendMessage($sendData);

            }
            // redis广播前端群组
            try {
                if ($userId) {
                    $systemUserLeavelChannelParams = [
                        'user_id' => $userId,
                    ];
                    // OA实例模式下，发送REDIS_DATABASE参数
                    if (envOverload('CASE_PLATFORM', false)) {
                        $systemUserLeavelChannelParams['REDIS_DATABASE'] = envOverload('REDIS_DATABASE', 0);
                    }
                    Redis::publish('eoffice.system-user-leave', json_encode($systemUserLeavelChannelParams));
                }
            } catch (\Exception $e) {
                Log::error($e->getMessage());
            }
            ecache('Auth:WrongPwdTimes')->clear($data['user_id']);
        }
        // 用户状态变更离职时
        if (isset($data['user_status'])) {
            //清除流程人员替换缓存的离职人员
            if(Redis::exists('out_users_array')){
                Redis::del('out_users_array');
            }
            if(!empty(Redis::keys('one_user_infos_*'))) {
                Redis::del(Redis::keys('one_user_infos_*'));
            }
            if(!empty(Redis::keys('out_user_infos_*'))) {
                Redis::del(Redis::keys('out_user_infos_*'));
            }
        }
        if ($oldUserInfo['user_has_one_system_info']['user_status'] == 2 && isset($data['user_status']) && $data['user_status'] == 1) {
            $personnelFileId = app($this->personnelFilesRepository)->getPersonnelFilesIdByUserId($data['user_id'], $data['user_name']);
            if (count($personnelFileId) > 0) {
                $toUser = app($this->personnelFilesPermission)->getManger($data['dept_id']);
                // $toUser = implode(app($this->userMenuService)->getMenuRoleUserbyMenuId(417), ',');
                $sendData['remindMark'] = 'user-onJob';
                $sendData['fromUser'] = $loginUserId;
                $sendData['toUser'] = $toUser;
                $sendData['contentParam'] = ['userName' => $data['user_name']];
                $sendData['stateParams'] = ['filesId' => $personnelFileId[0]['id']];
                Eoffice::sendMessage($sendData);
            }
        }
        // 清空表单分类信息redis缓存
        if(Redis::exists('flow_form_type_power_list_'.$userId)) {
            Redis::del('flow_form_type_power_list_'.$userId);
        }
        // 清空表单分类信息redis缓存
        if(Redis::exists('flow_sort_power_list_'.$userId)) {
            Redis::del('flow_sort_power_list_'.$userId);
        }
        $message = '';
        $data['user_job_number'] = $dataUser['user_job_number'];
        try {
            $data['user_accounts']  = $updateAccounts;
            // 更新用户关联信息
            $this->updateUserCorrelationData($data, 'edit',$loginUserInfo);
        } catch (ErrorMessage $e) {
            $message = $e->getErrorMessage();
        }

        $logData = [
            'log_content' => $logContent,
            'log_type' => 'edit',
            'log_creator' => $loginUserId,
            'log_ip' => getClientIp(),
            'log_relation_table' => 'user',
            'log_relation_id' => $userId,
            'module' => 'user'
        ];
        if ($message) {
            $logData['log_content'] = $logData['log_content']. ' '. trans('user.0x005045') . ': ' . $message;
        }
//        add_system_log($logData);
        $identifier  = "system.user.edit";
        $logParams = $this->handleLogParams($loginUserId, $logData['log_content'], $userId, 'user', $data['user_name']);
        logCenter::info($identifier , $logParams);
        //将用户状态添加到缓存
        if(isset($data['user_status'])) {
            CacheCenter::make('UserStatus', $userId)->setCache($data['user_status']);
        } else {
            CacheCenter::make('UserStatus', $userId)->clearCache();
        }

        // 全站搜索消息队列更新数据
        $this->updateGlobalSearchDataByQueue($userId);

        $updateResult = $this->updateUserNumberFile();
        if(is_array($updateResult) && isset($updateResult['code'])) {
            return $updateResult;
        }

        return $userDataCreateResult;
    }

    private function generateUserCacheByUserStatus($oldUserData, $newUserData)
    {
        $newStatus = $newUserData['user_status'];
        $oldStatus = $oldUserData['user_has_one_system_info']['user_status'];
        if ($newStatus == 2 && $oldStatus != 2) {
            // 删除
            $cacheData = [];
            $cacheData['user_accounts'] = $oldUserData['user_accounts'] ?? '';
            $this->createOrUpdateUserCache($cacheData, 'delete');
        } else if ($newStatus != 2 && $oldStatus != 2) {
            $newUserData['old_accounts'] = $oldUserData['user_accounts'] ?? '';
            $this->createOrUpdateUserCache($newUserData, 'update');
        } else if ($newStatus != 2 && $oldStatus == 2) {
            $this->createOrUpdateUserCache($newUserData, 'new');
        }
    }
    /**
     * 用于快速指引
     * @return [type] [description]
     */
    public function editDeptUser($userId, $data, $loginUserInfo) {
        if (empty($userId)) {
            return ['code' => ['0x000003', 'common']];
        }
        $data = $this->parseParams($data);
        $userName = $data['user_name'] ?? '';
        $data['user_accounts'] = $data['user_name'] ?? '';
        $data['user_id'] = $userId;
        $checkUserAccount = $this->validateUserAccountsValidation($data['user_accounts']);
        if (!$checkUserAccount) {
            return ['code' => ['0x005015', 'user']];
        }
        $checkResult = $this->checkUserInfoUnique($data, 'user_id');
        if (!empty($checkResult)) {
            return $checkResult;
        }
        unset($data['user_id']);
        unset($data['user_name']);
        $result = app($this->userRepository)->updateData($data, ["user_id" => $userId]);
        if ($result) {
            if ($this->makeUserAvatar($userId, $userName)) {
                return $result;
            }
        }
        return $result;
    }

    /**
     * 用户管理--检测用户信息唯一性(用户名、真实姓名、手机号码)
     *
     * @author miaochenchen
     *
     * @param  array $data
     *
     * @since  2017-03-16 创建
     *
     * @return array      返回结果
     */
    public function checkUserInfoUnique($data, $primaryKey = 'user_id')
    {
        $where = array();
        if (!empty($primaryKey)) {
            $where[$primaryKey] = [$data[$primaryKey], '!='];
        }
        if (isset($data['user_accounts']) && !empty($data['user_accounts'])) {
            $where['user_accounts'] = [$data['user_accounts']];
            $result = app($this->userRepository)->judgeUserExists($where);
            if ($result) {
                return ['code' => ['0x005003', 'user']];
            }
            unset($where['user_accounts']);
        }
        if (isset($data['user_job_number']) && !empty($data['user_job_number'])) {
            $where['user_job_number'] = [$data['user_job_number']];
            $result = app($this->userRepository)->judgeUserExists($where);
            if ($result) {
                return ['code' => ['0x005005', 'user']];
            }
            unset($where ['user_job_number']);
        }
        if (isset($data['phone_number']) && !empty($data['phone_number'])) {
            $where['phone_number'] = [$data['phone_number']];
            $result = app($this->userInfoRepository)->judgeUserPhoneNumberExists($where);
            if ($result) {
                return ['code' => ['0x005006', 'user']];
            }
        }
        unset($where);
    }

    /**
     * 用户管理--删除用户
     *
     * @author 丁鹏
     *
     * @param  string $userId [description]
     *
     * @since  2015-10-16 创建
     *
     * @return array      返回结果
     */
    public function userSystemDelete($userId, $loginUserId='')
    {
        if ($userId == 'admin') {
            return ['code' => ['0x005087', 'user']];
        }
        $data['user_accounts'] = '';
        $data['user_job_number'] = '';
        $data['wap_allow'] = '0';
         $oldUserInfo = $this->getUserAllData($userId);
        if (!is_array($oldUserInfo)) {
            $oldUserInfo = $oldUserInfo->toArray();
        }
        // 删除用户，用户状态设置为0
        $userName = app($this->userRepository)->getUserName($userId);
        $userSystemInfoUpdateData['user_status'] = 0;
        $logContent = trans('user.delete_user') . ':' . $userName;
        $userDataUpdateResult = app($this->userRepository)->updateData($data, ["user_id" => $userId]);
        $userStatusUpdateResult = app($this->userSystemInfoRepository)->updateData($userSystemInfoUpdateData, ["user_id" => $userId]);
        $deleteUserResult['user'] = app($this->userRepository)->deleteUserByUserId($userId);
        $deleteUserResult['user_info'] = app($this->userInfoRepository)->deleteUserInfoByUserId($userId);
        $deleteUserResult['user_system_info'] = app($this->userSystemInfoRepository)->deleteUserSystemInfoByUserId($userId);
        $deleteUserResult['user_role'] = app($this->roleService)->deleteUserRole($userId);
        $deleteUserResult['user_menu'] = app($this->userMenuService)->deleteUserMenuByUserId($userId);
        $deleteUserResult['user_superior'] = app($this->userSuperiorRepository)->deleteUserSuperior($userId);
        $deleteUserResult['user_attendance'] = app($this->attendanceSettingService)->userLeaveDeleteUserAttendanceScheduling($userId);
        // 更新用户关联信息
        $data['user_id'] = $userId;
        $this->updateUserCorrelationData($data, 'delete', own());
        $this->deleteDomainUser($userId);//删除中间表该用户
        // 清除个人门户信息
        app($this->portalService)->dropPortalLayout($userId);
        //清除流程人员替换缓存的离职人员
        if(Redis::exists('out_users_array')){
            Redis::del('out_users_array');
        }
        if(!empty(Redis::keys('one_user_infos_*'))) {
            Redis::del(Redis::keys('one_user_infos_*'));
        }
        if(!empty(Redis::keys('out_user_infos_*'))) {
            Redis::del(Redis::keys('out_user_infos_*'));
        }
        // 清空表单分类信息redis缓存
        if(Redis::exists('flow_form_type_power_list_'.$userId)) {
            Redis::del('flow_form_type_power_list_'.$userId);
        }
        // 清空表单分类信息redis缓存
        if(Redis::exists('flow_sort_power_list_'.$userId)) {
            Redis::del('flow_sort_power_list_'.$userId);
        }
        $this->createOrUpdateUserCache($oldUserInfo, 'delete');
        // 变更用户状态缓存
        CacheCenter::make('UserStatus', $userId)->setCache(0);
        // 添加日志
        if($loginUserId != ''){
            $logData = [
                'log_content' => $logContent,
                'log_type' => 'delete',
                'log_creator' => $loginUserId,
                'log_ip' => getClientIp(),
                'log_relation_table' => 'user',
                'log_relation_id' => $userId,
                'module' => 'user'
            ];
//            add_system_log($logData);
	    $identifier  = "system.user.delete";
            $logParams = $this->handleLogParams($loginUserId, $logContent, $userId, 'user', $userName);
            logCenter::info($identifier , $logParams);
        }
        // 全站搜索消息队列更新数据
        $this->updateGlobalSearchDataByQueue($userId);
        $updateResult = $this->updateUserNumberFile();
        if(is_array($updateResult) && isset($updateResult['code'])) {
            return $updateResult;
        }
        // redis广播前端群组
        try {
            if ($userId) {
                $systemUserLeavelChannelParams = [
                    'user_id' => $userId,
                ];
                // OA实例模式下，发送REDIS_DATABASE参数
                if (envOverload('CASE_PLATFORM', false)) {
                    $systemUserLeavelChannelParams['REDIS_DATABASE'] = envOverload('REDIS_DATABASE', 0);
                }
                Redis::publish('eoffice.system-user-leave', json_encode($systemUserLeavelChannelParams));
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }
        return $deleteUserResult;
    }
    //删除域同步中间表该用户
    public function deleteDomainUser($userId)
    {
        if(Schema::hasTable('ad_sync_contrast')){
            $record = DB::table('ad_sync_contrast')->where('user_id', $userId)->first();
            if(!empty($record)){
                return DB::table('ad_sync_contrast')->where('user_id', $userId)->delete();
            }
        }

        return true;
    }

    /**
     * 用户管理--清空密码
     *
     * @author 丁鹏
     *
     * @param  array $where [description]
     *
     * @since  2015-10-16 创建
     *
     * @return array            返回结果
     */
    public function userSystemEmptyPassword($where, $loginUserId)
    {
        $data = ["password" => crypt("", null)];
        $userName = app($this->userRepository)->getUserName($where['user_id']);
        $logData = [
            'log_content' => trans('user.clear_user_password') . ':' . $userName,
            'log_type' => 'pwdchange',
            'log_creator' => $loginUserId,
            'log_ip' => getClientIp(),
            'log_relation_table' => 'user',
            'log_relation_id' => $where['user_id'],
            'module' => 'user'
        ];
//        add_system_log($logData);
        $identifier  = "system.user.pwdchange";
        $logParams = $this->handleLogParams($loginUserId, $logData['log_content'], $where['user_id'], 'user', $userName);
        logCenter::info($identifier , $logParams);
        if (app($this->userRepository)->updateData($data, $where)) {
            if (isset($where['user_id'])) {
                if(app($this->userSystemInfoRepository)->updateData(["last_pass_time" => date('Y-m-d H:i:s')], ['user_id' => $where['user_id']])) {
                    return true;
                }
            }
        }
    }

    /**
     * [getSuperiorArrayByUserId 获取用户默认上级]
     *
     * @author 朱从玺
     *
     * @param  [int]                     $userId [用户ID]
     *
     * @since  [2015-10-23]
     *
     * @return [array]                           [上级数组]
     */
    public function getSuperiorArrayByUserId($userId, $param = [])
    {
        $param = $this->parseParams($param);
        $userSuperior = app($this->userSuperiorRepository)->getUserImmediateSuperior($userId, $param);
        if (!empty($userSuperior)) {
            global $superiorArray;
            if (isset($userSuperior) && $userSuperior) {
                foreach ($userSuperior as $key => $value) {
                    $superiorArray['id'][] = $value['superior_user_id'];
                    // 需要获取用户所有上级
                    if (isset($param['all_superior']) && $param['all_superior']) {
                        $this->getSuperiorArrayByUserId($value['superior_user_id'], $param);
                    }
                }
            }
            if (isset($superiorArray) && !empty($superiorArray)) {
                foreach ($superiorArray['id'] as $key => $value) {
                    $superiorArray['name'][$key] = app($this->userRepository)->getUserName($value);
                }
            }
            $superiorNewArray = $superiorArray;
            unset($GLOBALS['superiorArray']);
            return $superiorNewArray;
        } else {
            return $superiorNewArray = array(
                'id' => array(),
                'name' => array()
            );
        }
    }
    public function getSuperiorArrayByUserIdArr($param)
    {
        if (!isset($param['user_id']) || empty($param['user_id'])) {
            return [];
        }
        $param = $this->parseParams($param);
        $userSuperior = app($this->userSuperiorRepository)->getUserImmediateSuperior($param['user_id'], $param);
        if (!empty($userSuperior)) {
            global $superiorArray;
            if (isset($userSuperior) && $userSuperior) {
                foreach ($userSuperior as $key => $value) {
                    $superiorArray['id'][] = $value['superior_user_id'];
                    // 需要获取用户所有上级
                    if (isset($param['all_superior']) && $param['all_superior']) {
                        $param['user_id'] = $value['superior_user_id'];
                        $this->getSuperiorArrayByUserIdArr($param);
                    }
                }
            }
            // if (isset($superiorArray) && !empty($superiorArray)) {
            //     foreach ($superiorArray['id'] as $key => $value) {
            //         $superiorArray['name'][$key] = app($this->userRepository)->getUserName($value);
            //     }
            // }
            $superiorNewArray = $superiorArray;
            unset($GLOBALS['superiorArray']);
            return $superiorNewArray;
        } else {
            return $superiorNewArray = array();
        }
    }
    public function getSubordinateArrayByUserIdArr($param)
    {
        if (!isset($param['user_id']) || empty($param['user_id'])) {
            return [];
        }
        $param = $this->parseParams($param);
        $userSub = app($this->userAllSuperiorRepository)->getSuperiorUsers($param['user_id']);
        return array_column($userSub, 'user_id');
        if (!empty($userSuperior)) {
            global $superiorArray;
            if (isset($userSuperior) && $userSuperior) {
                foreach ($userSuperior as $key => $value) {
                    $superiorArray['id'][] = $value['superior_user_id'];
                    // 需要获取用户所有上级
                    if (isset($param['all_superior']) && $param['all_superior']) {
                        $this->getSuperiorArrayByUserId($value['superior_user_id'], $param);
                    }
                }
            }
            // if (isset($superiorArray) && !empty($superiorArray)) {
            //     foreach ($superiorArray['id'] as $key => $value) {
            //         $superiorArray['name'][$key] = app($this->userRepository)->getUserName($value);
            //     }
            // }
            $superiorNewArray = $superiorArray;
            unset($GLOBALS['superiorArray']);
            return $superiorNewArray;
        } else {
            return $superiorNewArray = array();
        }
    }
    public function getMyAllSubordinate($userId, $param, $own = [])
    {
        $ids = $this->getSubordinateArrayByUserId($userId, ['all_subordinate' => 1], $own);
        $userPositions = app($this->systemComboboxService)->getComboboxFieldByIdentify('USER_POSITION');
        if(isset($ids['id']) && !empty($ids['id'])){
            if (isset($param['page']) && isset($param['limit'])) {
                $users = app($this->userRepository)->getSimpleUserList(['user_id' => $ids['id'],'page' => $param['page'], 'limit' => $param['limit'],'order_by' => ['list_number' => 'ASC']]);
                $usersCount = app($this->userRepository)->getSimpleUserList(['user_id' => $ids['id'], 'order_by' => ['list_number' => 'ASC'], 'returntype' => 'count']);

                foreach ($users as $key => $user) {
                    $users[$key]['user_position_name'] = isset($userPositions[$user['user_position']]) ? $userPositions[$user['user_position']] : '';
                }
                return ['list' => $this->handleSimpleUserList($users), 'total' => $usersCount];
            }
            $users = app($this->userRepository)->getSimpleUserList(['user_id' => $ids['id'],'noPage' => true, 'order_by' => ['list_number' => 'ASC']]);
            foreach ($users as $key => $user) {
                $users[$key]['user_position_name'] = isset($userPositions[$user['user_position']]) ? $userPositions[$user['user_position']] : '';
            }
            return $this->handleSimpleUserList($users);
        }
        return [];
    }
    private function handleSimpleUserList($users)
    {
        if(empty($users)) {
            return $users;
        }
        $userIds = array_column($users, 'user_id');
        $userInfos = app($this->userInfoRepository)->getUsersByUserId($userIds, ['user_id', 'phone_number'])->mapWithKeys(function($item){
             return [$item->user_id => strtolower($item->phone_number)];
        });

        $userRoles = app($this->userRoleRepository)->getUserByWhere(['user_id' => [$userIds, 'in']]);

        $roles = app($this->roleRepository)->getAllRoles(['fields' => ['role_id', 'role_name']])->mapWithKeys(function($item){
             return [$item->role_id => strtolower($item->role_name)];
        });
        $userRoleMap = [];
        if(!empty($userRoles)) {
            foreach ($userRoles as $item) {
                $userRoleMap[$item['user_id']][] = ['role_id' => $item['role_id'], 'role_name' => $roles[$item['role_id']] ?? ''];
            }
        }
        foreach($users as &$user) {
            $user['phone_number'] = $userInfos[$user['user_id']] ?? '';
            $user['roles'] = $userRoleMap[$user['user_id']] ?? [];
        }
        return $users;
    }
    /**
     * [getSubordinateArrayByUserId 获取用户默认下级]
     *
     * @author 朱从玺
     *
     * @param  [int]                        $userId [用户ID]
     *
     * @since  [2015-10-23]
     *
     * @return [array]                              [下级数组]
     */
    public function getSubordinateArrayByUserId($userId, $param = [], $own = [])
    {
        $param = $this->parseParams($param);
        if (isset($param['dataFilter']) && !empty($param['dataFilter'])) {
            $config = config('dataFilter.' . $param['dataFilter']);
            if (!empty($config)) {
                $method = $config['dataFrom'][1];
                $data = app($config['dataFrom'][0])->$method($param);
                unset($param['loginUserInfo']);
                if (isset($data['isNotIn'])) {
                    if ($data['isNotIn']) {
                        // 包含获取到的user_id
                        if (isset($param['search']['user_id']) && !empty($param['search']['user_id'])) {
                            $searchUserId = $param['search']['user_id'][0];
                            if (!(is_array($searchUserId) || is_object($searchUserId))) {
                                $param["search"]["user_id"][0] = explode(",", trim($param["search"]["user_id"][0], ","));
                                $param["search"]["user_id"][0] = array_merge($param["search"]["user_id"][0], $data['user_id']);
                            }
                        } else {
                            $param['search']['user_id'] = [$data['user_id'], 'in'];
                        }
                    } else {
                        // 排除获取到的user_id
                        if (isset($param['search']['user_id']) && !empty($param['search']['user_id'])) {
                            $searchUserId = $param['search']['user_id'][0];
                            if (!(is_array($searchUserId) || is_object($searchUserId))) {
                                $param["search"]["user_id"][0] = explode(",", trim($param["search"]["user_id"][0], ","));
                                $param["search"]["user_id"][0] = array_merge($param["search"]["user_id"][0], $data['user_id']);
                                $param["search"]["user_id"][1] = 'not_in';
                            }
                        } else {
                            $param['search']['user_id'] = [$data['user_id'], 'not_in'];
                        }
                    }
                } else {
                    // 默认包含获取到的user_id
                    if (isset($param['search']['user_id']) && !empty($param['search']['user_id'])) {
                        $searchUserId = $param['search']['user_id'][0];
                        if (!(is_array($searchUserId) || is_object($searchUserId))) {
                            $param["search"]["user_id"][0] = explode(",", trim($param["search"]["user_id"][0], ","));
                            $param["search"]["user_id"][0] = array_merge($param["search"]["user_id"][0], $data['user_id']);
                        }
                    } else {
                        if (isset($data['user_id'])) {
                            $param['search']['user_id'] = [$data['user_id'], 'in'];
                        }
                    }
                }
            }
        }
        $orgPermission = $param['permission'] ?? 1;
        // 用户选择器
        if ($orgPermission) {
            // 只返回有权限的部门人员
            $permission = get_system_param('permission_organization');
            if ($permission) {
                $allDeptIds = app($this->departmentService)->getPermissionDept($own);
                if (!isset($param['search']['dept_id'])) {
                    $param['search']['dept_id'] = [$allDeptIds, 'in'];
                } else if (isset($param['search']['dept_id']) && isset($param['search']['dept_id'][0])) {
                    if ($param['search']['dept_id'][0] != 0) {
                        $searchDept = is_array($param['search']['dept_id'][0]) ? $param['search']['dept_id'][0] : [$param['search']['dept_id'][0]];

                        $insect = array_intersect($allDeptIds, $searchDept);
                        if ($insect) {
                            $param['search']['dept_id'] = [$insect, 'in'];
                        } else {
                            return ['list' => [], 'total' => 0];
                        }

                    } else {
                        $param['search']['dept_id'] = [$allDeptIds, 'in'];
                    }
                }
            }
        }
        // 获取上下级表所有数据
        $userSuperiorlist = DB::table('user_superior')
                                ->select('user_superior.user_id','superior_user_id');
        if (isset($param['include_leave']) && !$param['include_leave']) {
            // 排除离职的
            $userSuperiorlist = $userSuperiorlist->join('user', 'user.user_id', '=', 'user_superior.user_id')
                                ->where('user.user_accounts', '!=', '');
        }
        if (isset($param['search']) && isset($param['search']['dept_id'])) {
            $userSuperiorlist = $userSuperiorlist->leftJoin('user_system_info', 'user_system_info.user_id', '=', 'user_superior.user_id')->whereIn('user_system_info.dept_id', $param['search']['dept_id'][0]);
        }
        $userSuperiorlist = $userSuperiorlist->get();

        $userSuperiorArray = [];
        if (!empty($userSuperiorlist)) {
            $userSuperiorArray = $userSuperiorlist->toArray();
        }
        if (empty($userSuperiorArray)) {
            if (isset($param['returntype'])) {
                if ($param['returntype'] == 'list') {
                    return isset($param['include_self']) ? [['user_id' => $own['user_id'], 'user_name' => $own['user_name']]] : [];
                } elseif ($param['returntype'] == 'list_total') {
                    return ['list' => [], 'total' => 0];
                } elseif ($param['returntype'] == 'name') {
                    return ['name' => []];
                } elseif ($param['returntype'] == 'id_name') {
                    return ['id' => [], 'name' => []];
                } else {
                    return ['id' => []];
                }
            } else {
                return ['id' => []];
            }
        }
        // 获取下级ID
        $subordinateArray['id'] = $this->getSubordinateArrayByUserIdSubFunc($userId, $param, $userSuperiorArray);
        // 获取下级用户列表数据
        if (isset($param['returntype']) && ($param['returntype'] == 'list' || $param['returntype'] == 'list_total')) {
            if (!empty($subordinateArray['id'])) {
                if (isset($param['page'])) {
                    $tempParam['page'] = $param['page'];
                }
                if (isset($param['limit'])) {
                    $tempParam['limit'] = $param['limit'];
                }
                $tempParam['search'] = [];
                if (isset($param['search'])) {
                    $tempParam['search'] = $param['search'];
                }
                if (isset($tempParam['search']['user_id'][1]) && is_array($tempParam['search']['user_id'][0])) {
                    if ($tempParam['search']['user_id'][1] == 'not_in') {
                        $searchUserIdArray = array_diff($subordinateArray['id'], $tempParam['search']['user_id'][0]);
                    } else{
                        $searchUserIdArray = array_intersect($subordinateArray['id'], $tempParam['search']['user_id'][0]);
                    }
                    if (empty($searchUserIdArray)) {
                        if ($param['returntype'] == 'list') {
                            return isset($param['include_self']) ? [['user_id' => $own['user_id'], 'user_name' => $own['user_name']]] : [];
                        } else {
                            return ['list' => [], 'total' => 0];
                        }
                    }
                    $tempParam['search']['user_id'] = [$searchUserIdArray, 'in'];
                } else {
                    $tempParam['search']['user_id'] = [$subordinateArray['id'], 'in'];
                }
                if(isset($param['include_leave']) && $param['include_leave']){
                    $tempParam['include_leave'] = $param['include_leave'];
                }
                if(isset($param['include_supervisor']) && $param['include_supervisor']){
                    $tempParam['include_supervisor'] = $param['include_supervisor'];
                }
                if ($param['returntype'] == 'list') {
                    $returnData = app($this->userRepository)->getUserList($tempParam);
                    if (isset($param['include_self'])) {
                        array_unshift($returnData, ['user_id' => $own['user_id'], 'user_name' => $own['user_name']]);
                    }
                    return $returnData;
                } else {
                    return $this->userSystemList($tempParam);
                }
            } else {
                if ($param['returntype'] == 'list') {
                    return isset($param['include_self']) ? [['user_id' => $own['user_id'], 'user_name' => $own['user_name']]] : [];
                } else {
                    ['list' => [], 'total' => 0];
                }
            }
        }
        // 获取下级姓名
        if (isset($param['returntype']) && ($param['returntype'] == 'name' || $param['returntype'] == 'id_name')) {
            if (!empty($subordinateArray['id'])) {
                $subordinateArray['name'] = app($this->userRepository)->getUsersNameByIds($subordinateArray['id']);
            } else {
                $subordinateArray['name'] = [];
            }
        }
        if (isset($param['returntype']) && $param['returntype'] == 'name') {
            unset($subordinateArray['id']);
        }
        return $subordinateArray;
    }

    public function getSubordinateArrayByUserIdSubFunc($userId, $param, $userSuperiorArray, $mark = 1)
    {
        static $tempSubordinateIdArray;
        if ($mark == 1) {
            $tempSubordinateIdArray = [];
        }
        $subordinateIdArray = [];
        foreach ($userSuperiorArray as $key => $value) {
            if ($value->superior_user_id == $userId) {
                $subordinateIdArray[] = $value->user_id;
                unset($userSuperiorArray[$key]);
            }
        }
        if (empty($tempSubordinateIdArray)) {
            $tempSubordinateIdArray = $subordinateIdArray;
        } else {
            $tempSubordinateIdArray = array_merge($subordinateIdArray, $tempSubordinateIdArray);
        }
        if (isset($param['all_subordinate']) && $param['all_subordinate']) {
            if (!empty($subordinateIdArray)) {
                foreach ($subordinateIdArray as $key => $value) {
                    $this->getSubordinateArrayByUserIdSubFunc($value, $param, $userSuperiorArray, 2);
                }
            }
        }
        if (!empty($tempSubordinateIdArray)) {
            $tempSubordinateIdArray = array_unique($tempSubordinateIdArray);
        }
        return $tempSubordinateIdArray;
    }


    // 客户专用
    public function getSubordinateArrayByUserIdAndThoughType($userIds, $though)
    {
        if (empty($though)) {
            return ['id' => $userIds];
        }
        // 获取上下级表所有数据
        $userSuperiorlist = DB::table('user_superior')->select('user_id','superior_user_id')->get();
        $userSuperiorArray = [];
        if ($userSuperiorlist->isEmpty()) {
            return ['id' => $userIds];
        }
        $userSuperiorArray = $userSuperiorlist->toArray();
        // 获取下级ID
        $subordinateArray['id'] = $this->getSubordinateArrayByUserIdAndThoughTypeSubFunc($userIds, $userSuperiorArray, $though);

        // 获取下级用户列表数据
        if (isset($param['returntype']) && $param['returntype'] == 'list' && !empty($subordinateArray['id'])) {
            if (isset($param['page'])) {
                $tempParam['page'] = $param['page'];
            }
            if (isset($param['limit'])) {
                $tempParam['limit'] = $param['limit'];
            }
            $tempParam['search'] = ['user_id' => [$subordinateArray['id'], 'in']];
            return app($this->userRepository)->getUserList($tempParam);
        }
        // 获取下级姓名
        if (isset($param['returntype']) && ($param['returntype'] == 'name' || $param['returntype'] == 'id_name')) {
            if (!empty($subordinateArray['id'])) {
                $subordinateArray['name'] = app($this->userRepository)->getUsersNameByIds($subordinateArray['id']);
            } else {
                $subordinateArray['name'] = [];
            }
        }
        if (isset($param['returntype']) && $param['returntype'] == 'name') {
            unset($subordinateArray['id']);
        }
        return $subordinateArray;
    }
    // 客户专用
    public function getSubordinateArrayByUserIdAndThoughTypeSubFunc($userIds, $userSuperiorArray, $though)
    {
        static $tempSubordinateIdThoughArray = [];
        if (is_array($userIds)) {
            $tempSubordinateIdThoughArray = array_merge($tempSubordinateIdThoughArray, $userIds);
        }
        $subordinateIdArray = [];
        if (empty($userSuperiorArray)) {
            return $tempSubordinateIdThoughArray;
        }
        foreach ($userSuperiorArray as $key => $value) {
            if ($userIds == 'all_user') {
                $subordinateIdArray[] = $value->user_id;
                unset($userSuperiorArray[$key]);
                continue;
            }
            if (in_array($value->superior_user_id, $userIds)) {
                $subordinateIdArray[] = $value->user_id;
                unset($userSuperiorArray[$key]);
            }
        }
        if (empty($tempSubordinateIdThoughArray)) {
            $tempSubordinateIdThoughArray = $subordinateIdArray;
        } else {
            $tempSubordinateIdThoughArray = array_merge($subordinateIdArray, $tempSubordinateIdThoughArray);
        }
        if ($though == 2) {
            if (!empty($subordinateIdArray)) {
                $this->getSubordinateArrayByUserIdAndThoughTypeSubFunc($subordinateIdArray, $userSuperiorArray, $though);
            }
        }
        if (!empty($tempSubordinateIdThoughArray)) {
            $tempSubordinateIdThoughArray = array_unique($tempSubordinateIdThoughArray);
        }
        return $tempSubordinateIdThoughArray;
    }

    /**
     * 验证用户的离职状态
     *
     * @method checkUserWorkingStatus
     *
     * @param  [type]                $userId [description]
     *
     * @return [boole]                        true:离职;false:其他状态
     */
    function checkUserWorkingStatus($userId)
    {
        if ($userObject = app($this->userRepository)->getUserAllData($userId)) {
            return $userObject->userHasOneSystemInfo->user_status == 2 ? true : false;
        }
        return false;
    }

    /**
     * 获取在职人员
     *
     * @method getInserviceUser
     *
     * @param  array $param [description]
     *
     * @return [type]                  [description]
     */
    function getInserviceUser($param = [])
    {
        $param = $this->parseParams($param);
        // 调用批量获取用户信息，接入9.0的那个可以选离职在职的功能
        $param["getDataType"] = "inservice";
        if ($inserviceUserObject = app($this->userRepository)->getBatchUserInfoRepository($param)) {
            return $inserviceUserObject;
        }
    }

    /**
     * 获取离职人员
     *
     * @method getInserviceUser
     *
     * @param  array $param [description]
     *
     * @return [type]                  [description]
     */
    function getLeaveOfficeUser($param = [], $loginUserInfo = [])
    {
        $param = $this->parseParams($param);
        $param['loginUserInfo'] = $loginUserInfo;
        // 调用批量获取用户信息，接入9.0的那个可以选离职在职的功能
        $param["getDataType"] = "leaveoffice";
        // admin特权查看所有用户 非admin查看管理权限内的用户
        if (isset($param['loginUserInfo']['user_id']) && $param['loginUserInfo']['user_id'] != 'admin') {
            $param['fixedSearch']['dept_id'][0] = $this->getUserCanManageDepartmentId($loginUserInfo);

            $maxRoleNo = isset($loginUserInfo['max_role_no']) ? $loginUserInfo['max_role_no'] : 0;
            $param['search']['max_role_no'][0] = $maxRoleNo;
            $param['dept_id'] = $param['fixedSearch']['dept_id'][0];
            if ($param['fixedSearch']['dept_id'][0] == 'all') {
                $allDept = array_column(app($this->departmentService)->getAllDeptId(), 'dept_id');
                $param['dept_id'] = $allDept;
            }

            $data = app($this->userRepository)->getLeaveUserManageList($param);
            return $data;
            // $param['fixedSearch']['role_id'][0] = $this->getUserCanManageRoleId($loginUserInfo['user_id']);
        }
        if ($leaveOfficeUserObject = app($this->userRepository)->getBatchUserInfoRepository($param)) {
            return $leaveOfficeUserObject;
        }
    }

    /**
     * 获取删除人员
     *
     * @method getInserviceUser
     *
     * @param  array $param [description]
     *
     * @return [type]                  [description]
     */
    function getDeletedUser($param = [])
    {
        $param = $this->parseParams($param);
        // 调用批量获取用户信息，接入9.0的那个可以选离职在职的功能
        $param["getDataType"] = "deleted";
        if ($deletedUserObject = app($this->userRepository)->getBatchUserInfoRepository($param)) {
            return $deletedUserObject;
        }
    }

    /**
     * 根据用户id字符串获取用户列表
     *
     * @method getInserviceUser
     *
     * @param  array $param [description]
     *
     * @return [type]                  [description]
     */
    function getUserListByUserIdString($param = [])
    {
        $param = $this->parseParams($param);
        return $this->response(app($this->userRepository), 'getBatchUserInfoRepositoryTotal', 'getBatchUserInfoRepository', $param);
    }

    /**
     * 内置函数，获取用户名称
     *
     * @author 丁鹏
     *
     * @param  string $userId [description]
     *
     * @since  2015-10-16 创建
     *
     * @return array    返回结果
     */
    public function getUserName($userId)
    {
        return app($this->userRepository)->getUserName($userId);
    }

    /**
     * 根据用户id获取比自己角色权限级别小的角色
     *
     * @param  $userId
     * @return string
     */
    public function getUserCanManageRoleId($userId)
    {
        $userPermissionData = app($this->userRepository)->getUserPermissionData($userId)->toArray();
        $userRoleNo = [];
        foreach ($userPermissionData['user_role'] as $key => $value) {
            $userRoleNo[] = $value['role_no'];
        }
        $userMaxRolePriv = min($userRoleNo);
        $param['search'] = json_encode(['role_no' => [$userMaxRolePriv, '>']]);
        $roleListResult = app($this->roleService)->getRoleList($param);
        if ($roleListResult) {
            $userCanManageRoleId['role_id'] = [];
            foreach ($roleListResult['list'] as $key => $value) {
                $userCanManageRoleId['role_id'][] = $value['role_id'];
            }
            return $userCanManageRoleId['role_id'];
        }
    }

    /**
     * 根据用户管理范围获取可管理的部门id
     *
     * @param  $userId , $loginUserInfo
     * @return string
     */
    public function getUserCanManageDepartmentId($loginUserInfo)
    {
        if ($loginUserInfo['post_priv'] == '0') {
            //管理范围为本部门
            return $manageRange[] = [$loginUserInfo['dept_id']];
        } elseif ($loginUserInfo['post_priv'] == '2') {
            //管理范围为指定部门
            return $manageRange = explode(',', $loginUserInfo['post_dept']);
        } else {
            //管理范围为全体的
            return $manageRange[] = "all";
        }
    }

    /**
     * 通过部门id获取所有本部门包括子部门的人员列表
     *
     * @author 缪晨晨
     *
     * @param  int $deptId
     *
     * @since  2016-07-21 创建
     *
     * @return array   返回结果
     */
    public function getUserListByDeptId($deptId, $param = [], $loginUserInfo = [])
    {
        $param = $this->parseParams($param);
        $deptIdStr = app($this->departmentService)->allChildren($deptId);
        if (!empty($deptIdStr)) {
            $param["search"]["dept_id"] = [
                "0" => $deptIdStr
            ];
            $param['from'] = 'user';
        }
        return $this->userSystemList($param, $loginUserInfo);
        // return $this->response(app($this->userRepository), 'getUserListTotal', 'getUserList', $param);
    }

    /**
     * 获取用户列表函数，详细说明参考repository里的此函数的说明。
     * 为了在其他模块里只引入userservice，所以，将此函数在userservice里写一遍。
     *
     * @method getConformScopeUserList
     *
     * @param  array $param [description]
     *
     * @return [type]             [description]
     */
    function getConformScopeUserList($param)
    {
        return app($this->userRepository)->getConformScopeUserList($param);
    }

    /**
     * 根据用户id获取用户角色级别信息
     * 流程宏控件获值的时候，调用了此函数，修改请注意。
     *
     * @author 缪晨晨
     *
     * @param  string $userId
     *
     * @since  2016-09-06 创建
     *
     * @return array   返回结果
     */
    public function getUserRoleLeavel($userId)
    {
        $userPermissionData = app($this->userRepository)->getUserPermissionData($userId)->toArray();
        if ($userPermissionData) {
            $userRoleLeavel = array();
            foreach ($userPermissionData['user_role'] as $key => $value) {
                $userRoleLeavel['role_leavel'][$key] = $value['role_no'];
            }
            return $userRoleLeavel;
        }
    }

    /**
     * 根据用户id获取用户部门完整路径
     * 流程宏控件获值的时候，调用了此函数，修改请注意。
     *
     * @author 缪晨晨
     *
     * @param  string $userId
     *
     * @since  2016-09-06 创建
     *
     * @return array   返回结果
     */
    public function getUserDeptPath($userId)
    {
        $deptInfo = $this->getDeptInfoByUserId($userId);
        return $this->getDeptPathByDeptInfo($deptInfo);
    }

    /**
     * 根据部门id获取部门完整路径
     *
     * @author 缪晨晨
     *
     * @param  string $deptId
     *
     * @since  2016-12-07 创建
     *
     * @return array   返回结果
     */
    public function getDeptPathByDeptId($deptId)
    {
        $deptInfo = app($this->departmentRepository)->getDepartmentInfo($deptId);
        if ($deptInfo) {
            $deptInfo = $deptInfo->toArray();
        }

        return $this->getDeptPathByDeptInfo($deptInfo);
    }

    public function getDeptPathByDeptInfo($deptInfo)
    {
        $deptDetail = array();
        if ($deptInfo['arr_parent_id'] == '0') {
            // 如果是顶级部门
            $deptDetail['dept_path'] = $deptInfo['dept_name'];
        } else {
            // 如果是子部门
            $deptParentId = explode(',', $deptInfo['arr_parent_id']);
            $deptPathArr = array();
            foreach ($deptParentId as $key => $val) {
                if ($key == '0') continue;
                $parentDeptInfo = app($this->departmentRepository)->getDepartmentInfo($val)->toArray();
                $deptPathArr[] = $parentDeptInfo['dept_name'];
            }
            $deptPathArr[] = $deptInfo['dept_name'];
            $deptDetail['dept_path'] = implode('/', $deptPathArr);
        }
        return $deptDetail['dept_path'];
    }

    /**
     * 根据用户id获取用户当前部门负责人
     * 流程宏控件获值的时候，调用了此函数，修改请注意。
     *
     * @author 缪晨晨
     *
     * @param  string $userId
     *
     * @since  2016-09-06 创建
     *
     * @return array   返回结果
     */
    public function getUserOwnDeptDirector($userId)
    {
        $deptInfo = $this->getDeptInfoByUserId($userId);
        $ownDeptDirector = array();
        if ($deptInfo['directors']) {
            foreach ($deptInfo['directors'] as $key => $val) {
                if (isset($val['director_has_one_user']['user_id']) && isset($val['director_has_one_user']['user_name'])) {
                    $ownDeptDirector[$key]['user_name'] = $val['director_has_one_user']['user_name'];
                    $ownDeptDirector[$key]['user_id'] = $val['director_has_one_user']['user_id'];
                }
            }
        }
        return $ownDeptDirector;
    }

    /**
     * 根据用户id获取用户上级部门的负责人
     * 流程宏控件获值的时候，调用了此函数，修改请注意。
     *
     * @author 缪晨晨
     *
     * @param  string $userId
     *
     * @since  2016-09-06 创建
     *
     * @return array   返回结果
     */
    public function getUserSuperiorDeptDirector($userId)
    {
        $deptInfo = $this->getDeptInfoByUserId($userId);
        $superiorDeptDirector = array();
        if ($deptInfo['arr_parent_id'] != '0') {
            // 如果是子部门，获取上级部门的负责人信息
            $deptParentId = explode(',', $deptInfo['arr_parent_id']);
            $maxKey = count($deptParentId) - 1;
            $parentDeptInfo = app($this->departmentRepository)->getDepartmentInfo($deptParentId[$maxKey])->toArray();
            if ($parentDeptInfo['directors']) {
                foreach ($parentDeptInfo['directors'] as $key => $val) {
                    if (isset($val['director_has_one_user']['user_id']) && isset($val['director_has_one_user']['user_name'])) {
                        $superiorDeptDirector[$key]['user_name'] = $val['director_has_one_user']['user_name'];
                        $superiorDeptDirector[$key]['user_id'] = $val['director_has_one_user']['user_id'];
                    }
                }
            }
            return $superiorDeptDirector;
        } else {
            // 如果是顶级部门返回空
            return '';
        }
    }

    /**
     * 根据用户id获取当前部门信息
     *
     * @author 缪晨晨
     *
     * @param  string $userId
     *
     * @since  2016-09-06 创建
     *
     * @return array   返回结果
     */
    public function getDeptInfoByUserId($userId)
    {
        $params = ['user_id' => [$userId]];
        $userSystemInfo = app($this->userSystemInfoRepository)->getInfoByWhere($params);
        $userDeptId = $userSystemInfo[0]['dept_id'];
        $deptInfo = app($this->departmentRepository)->getDepartmentInfo($userDeptId)->toArray();
        return $deptInfo;
    }

    // 查询用户对的部门信息，key为用户id
    public function getDeptInfoByUserIds(array $userIds) {
        $departmentsInfo = app($this->userSystemInfoRepository)->entity
            ->withTrashed()
            ->whereKey($userIds)
            ->with('userSystemInfoBelongsToDepartment')
            ->get()->toArray();
        return Arr::pluck($departmentsInfo, 'user_system_info_belongs_to_department', 'user_id');
    }

    /**
     * 获取用户授权信息
     *
     * @author 缪晨晨
     *
     * @param
     *
     * @since  2016-09-09 创建
     *
     * @return array   返回授权用户数，已使用用户数，离职用户数
     */
    public function getUserAuthorizationInfo()
    {
        $userAuthorizationInfo = array();
        // 获取授权用户数
        $empowerInfo = app($this->empowerService)->getPcEmpower(0);
        $userAuthorizationInfo['pc_has_authorized'] = isset($empowerInfo['pcUserNumber']) ? $empowerInfo['pcUserNumber'] : '0';
        // 获取已使用用户数不包含离职
        $userAuthorizationInfo['pc_has_used'] = app($this->userRepository)->getUserListTotal([]);
        // 离职用户数
        // $param['include_leave'] = true;
        // $param["search"]["user_status"] = [2];
        // $userAuthorizationInfo['leave_off'] = app($this->userRepository)->getUserListTotal($param);
        // 获取手机授权用户数
        $empowerInfo = app($this->empowerService)->getMobileEmpower(0);
        $userAuthorizationInfo['mobile_has_authorized'] = isset($empowerInfo['mobileUserNumber']) ? $empowerInfo['mobileUserNumber'] : '0';;
        // 获取手机使用用户数
        $userAuthorizationInfo['mobile_has_used'] = app($this->userSystemInfoRepository)->getMobileHasUsedNumber([]);
        return $userAuthorizationInfo;
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
    public function getAllUserIdString($param = [])
    {
        return app($this->userRepository)->getAllUserIdString($param);
    }

    /**
     * 角色通信控制过滤
     *
     * @author 缪晨晨
     *
     * @param  $param array
     *
     * @since  2017-02-23 创建
     *
     * @return array  返回需要过滤掉的角色ID数组
     */
    public function communicateUserFilter($param, $loginUserInfo)
    {
        if (isset($param['order_by'])) {
            unset($param['order_by']);
        }
        $communicateList = app($this->roleCommunicateRepository)->getRoleCommunicate($param);
        $communicateList = $communicateList->toArray();
        $filterIdArray = array();
        $allRoleArray = array();
        if (isset($loginUserInfo['role_id']) && !empty($loginUserInfo['role_id'])) {
            foreach ($loginUserInfo['role_id'] as $key => $val) {
                if (!empty($communicateList)) {
                    foreach ($communicateList as $cKey => $cValue) {
                        $tempRoleFromArray = explode(',', trim($cValue['role_from']));
                        if (in_array($val, $tempRoleFromArray)) {
                            $tempRoleToArray = explode(',', trim($cValue['role_to']));
                            $filterIdArray = array_merge($filterIdArray, $tempRoleToArray);
                        }
                    }
                }
            }
        }
        $filterIdArray = array_unique($filterIdArray);
        if (isset($param['filter_type']) && $param['filter_type'] == '1') {
            if (!empty($filterIdArray)) {
                return $filterIdArray;
            } else {
                return '';
            }
        } else {
            $allRoleList = app($this->roleRepository)->getRoleList();
            if (!empty($allRoleList)) {
                foreach ($allRoleList as $value) {
                    $allRoleArray[] = $value['role_id'];
                }
                if (count($allRoleArray) == count($filterIdArray)) {
                    return 'all';
                } else {
                    return array_diff($allRoleArray, $filterIdArray);
                }
            } else {
                return '';
            }
        }
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
    public function getUserDeptIdAndRoleIdByUserId($userId)
    {
        return app($this->userRepository)->getUserDeptIdAndRoleIdByUserId($userId);
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
    public function getMobileUserList()
    {
        $mobileUserList = app($this->userSystemInfoRepository)->getMobileUserList();
        if (!empty($mobileUserList)) {
            $mobileUserArray = array();
            foreach ($mobileUserList as $value) {
                $mobileUserArray[] = $value['user_id'];
            }
            return $mobileUserArray;
        }
        return $mobileUserList;
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
    public function setMobileUser($param)
    {
        $empowerInfo = app($this->empowerService)->getMobileEmpower(0);
        $mobileEmpowerUserNumber = isset($empowerInfo['mobileUserNumber']) ? $empowerInfo['mobileUserNumber'] : '0';
        if ($mobileEmpowerUserNumber < count($param)) {
            return ['code' => ['0x005019', 'user']];
        }
        return app($this->userSystemInfoRepository)->setMobileUser($param);
    }

    public function setMobileUserById($userId, $params) {
        $empowerInfo = app($this->empowerService)->getMobileEmpower(0);
        // 获取所有手机用户
        $powerUserInfo = $this->getUserAuthorizationInfo();
        $mobileUsed = $powerUserInfo['mobile_has_used'] ?? 0;
        $mobileEmpowerUserNumber = isset($empowerInfo['mobileUserNumber']) ? $empowerInfo['mobileUserNumber'] : '0';
        if ($mobileEmpowerUserNumber < $mobileUsed) {
            return ['code' => ['0x005019', 'user']];
        }
        return app($this->userSystemInfoRepository)->setMobileUserById($userId, $params);
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
    public function checkExistleaveOffUser()
    {
        return app($this->userSystemInfoRepository)->checkExistleaveOffUser();
    }

    /**
     * 判断设置的考勤排班ID是否在考勤排班范围内（用户新建编辑导入用户时过滤脏数据）
     *
     * @author 缪晨晨
     *
     * @param $dutyType
     *
     * @since  2017-06-16 创建
     *
     * @return array
     */
    public function judgeAttendanceIdInAttendanceList($dutyType)
    {
        $attendanceList = app($this->schedulingRepository)->getSchedulingList(['page' => 0]);
        $attendanceIdArray = array();
        if (!empty($attendanceList)) {
            foreach ($attendanceList as $value) {
                $attendanceIdArray[] = $value->scheduling_id;
            }
        }
        if (empty($attendanceIdArray)) {
            return ['code' => ['0x005021', 'user']];
        } else {
            if (in_array($dutyType, $attendanceIdArray)) {
                return 1;
            } else {
                if ($dutyType == 1) return 2;
                return ['code' => ['0x005022', 'user']];
            }
        }
    }

    /**
     * 判断设置的用户状态ID是否在用户状态列表范围内（用户新建编辑导入用户时过滤脏数据）
     *
     * @author 缪晨晨
     *
     * @param $userStatus
     *
     * @since  2017-06-16 创建
     *
     * @return array or string
     */
    public function judgeUserStatusIdInStatusList($userStatus)
    {
        $userStatusList = app($this->userStatusRepository)->userStatusListRepository();
        $userStatusIdsArray = array();
        if (!empty($userStatusList)) {
            foreach ($userStatusList as $value) {
                $userStatusIdsArray[] = $value['status_id'];
            }
        }
        if (in_array($userStatus, $userStatusIdsArray)) {
            return 1;
        } else {
            return ['code' => ['0x005023', 'user']];
        }
    }

    /**
     * 判断设置的角色ID是否在角色列表范围内（用户新建编辑导入用户时过滤脏数据）
     *
     * @author 缪晨晨
     *
     * @param $roleId
     *
     * @since  2017-06-16 创建
     *
     * @return array or string
     */
    public function judgeUserRoleIdInRoleList($roleId)
    {
        $userRoleList = app($this->roleRepository)->getRoleList();
        $userRoleIdsArray = array();
        if (!empty($userRoleList)) {
            foreach ($userRoleList as $value) {
                $userRoleIdsArray[] = $value['role_id'];
            }
        }
        if (!is_array($roleId)) {
            $roleId = explode(',', trim($roleId, ','));
        }
        if (!empty($roleId)) {
            foreach ($roleId as $value) {
                if (!empty($value) && !in_array($value, $userRoleIdsArray)) {
                    return ['code' => ['0x005024', 'user']];
                }
            }
        }
        return 1;
    }

    /**
     * 判断设置的部门ID是否在部门列表范围内（用户新建编辑导入用户时过滤脏数据）
     *
     * @author 缪晨晨
     *
     * @param $deptId
     *
     * @since  2017-06-16 创建
     *
     * @return array or string
     */
    public function judgeUserDeptIdInDeptList($deptId)
    {
        $userDeptList = app($this->departmentRepository)->getAllDepartment();
        $deptIdsArray = array();
        if (!empty($userDeptList)) {
            foreach ($userDeptList as $value) {
                $deptIdsArray[] = $value->dept_id;
            }
        }
        if (empty($deptIdsArray)) {
            return ['code' => ['0x005025', 'user']];
        } else {
            if (in_array($deptId, $deptIdsArray)) {
                return 1;
            } else {
                return ['code' => ['0x005026', 'user']];
            }
        }
    }

    /**
     * 判断设置的职位ID是否在职位列表范围内（用户新建编辑导入用户时过滤脏数据）
     *
     * @author 缪晨晨
     *
     * @param $userPositionId
     *
     * @since  2018-01-08 创建
     *
     * @return array or string
     */
    public function judgeUserPositionIdInUserPositionList($userPositionId)
    {
        if (empty(trim($userPositionId))) {
            return 1;
        }
        $userPositionData = app($this->systemComboboxService)->getComboboxFieldByIdentify('USER_POSITION');
        if (empty($userPositionData)) {
            return ['code' => ['0x005029', 'user']];
        } else {
            if (!array_key_exists(trim($userPositionId), $userPositionData)) {
                return ['code' => ['0x005030', 'user']];
            } else {
                return 1;
            }
        }
    }

    /**
     * 用户新建编辑删除后更新相关联的数据
     *
     * @author 缪晨晨
     *
     * @param  $data 用户数据  $type 操作类型
     *
     * @since  2017-06-21 创建
     *
     * @return boolean
     */
    public function updateUserCorrelationData($data, $type, $own = [])
    {
        // 处理数据
        if ((isset($data['is_autohrms']) && $data['is_autohrms'] == '1') || (isset($data['user_has_one_system_info']['is_autohrms']) && $data['user_has_one_system_info']['is_autohrms'] == '1')) {
            // 人事档案同步需要的数据
            $personnelFileData['user_id'] = $data['user_id'];
            $personnelFileData['user_name'] = $data['user_name'];
            $personnelFileData['sex'] = isset($data['sex']) ? $data['sex'] : $data['user_has_one_info']['sex'];
            $personnelFileData['status'] = $data['user_status'];
            $personnelFileData['dept_id'] = isset($data['dept_id']) ? $data['dept_id'] : $data['user_has_one_system_info']['dept_id'];
            // $personnelFileData['birthday'] = isset($data['birthday']) ? $data['birthday'] : '';
            // $personnelFileData['home_addr'] = isset($data['home_address']) ? $data['home_address'] : '';
            // $personnelFileData['home_tel'] = isset($data['home_phone_number']) ? $data['home_phone_number'] : '';
            $personnelFileData['no'] = isset($data['user_job_number']) && !empty($data['user_job_number']) ? $data['user_job_number'] : $data['user_accounts'];
            if (isset($data['notes'])) {
                $personnelFileData['resume'] = $data['notes'];
            }
            if (isset($data['birthday']) && !empty($data['birthday'])) {
                $personnelFileData['birthday'] = $data['birthday'];
            }
            if (isset($data['home_addr']) && !empty($data['home_addr'])) {
                $personnelFileData['home_addr'] = $data['home_addr'];
            }
            if (isset($data['home_tel']) && !empty($data['home_tel'])) {
                $personnelFileData['home_tel'] = $data['home_tel'];
            }
            if (isset($data['email']) && !empty($data['email'])) {
                $personnelFileData['email'] = $data['email'];
            }
            // $personnelFileData['email'] = isset($data['email']) ? $data['email'] : '';
        }
        // 检测性能安全设置里是否开启动态密码验证
        $dynamicPasswordIsUseed = get_system_param('dynamic_password_is_useed', 0);
        if ($dynamicPasswordIsUseed == 1 && isset($data['user_id'])) {
            $userSecextInfo = app($this->userSecextRepository)->getOneDataByUserId($data['user_id']);
            // 检测是否设置了用户使用动态密码验证
            if (isset($data['is_dynamic_code'])) {
                if ($data['is_dynamic_code'] == 1) {
                    $tokenKey       = isset($data["sn_number"]) ? trim($data["sn_number"]) : '';
                    $tokenCode      = isset($data["dynamic_code"]) ? trim($data["dynamic_code"]) : '';
                    if($tokenKey == ''){
                        return ['code' => ['0x003035', 'auth']];
                    }
                    $userTokendcInfo = app($this->tokendcRepository)->getOneDataByTokenKey($data['user_id']);
                    if (empty($userTokendcInfo)) {
                        $isBind = 0;
                    } else {
                        $isBind = 1;
                    }
                    if (empty($userSecextInfo)) {
                        $userSecextData = [
                            'user_id'         => $data['user_id'],
                            'sn_number'       => $tokenKey,
                            'is_dynamic_code' => 1
                        ];
                        app($this->userSecextRepository)->insertData($userSecextData);
                    } else {
                        app($this->userSecextRepository)->updateData(['is_dynamic_code' => 1, 'sn_number' => $tokenKey], ['user_id' => $data['user_id']]);
                    }
                    $dynamicAddr    = get_system_param('dynami_addr');
                    if ($dynamicAddr && $tokenKey) {
                        @file_get_contents($dynamicAddr . "/tokenDL/index.jsp?TYPE=bindKey&ISBIND=".$isBind."&USER_ID=".$data['user_id']."&TOKENKEY=" .$tokenKey . "&TOKENCODE=" . $tokenCode);
                    }
                } else {
                    if (empty($userSecextInfo)) {
                        $userSecextData = [
                            'user_id'         => $data['user_id'],
                            'sn_number'       => '',
                            'is_dynamic_code' => 0
                        ];
                        app($this->userSecextRepository)->insertData($userSecextData);
                    } else {
                        app($this->userSecextRepository)->updateData(['is_dynamic_code' => 0, 'sn_number' => ''], ['user_id' => $data['user_id']]);
                    }
                }
            }

        }
        // 新建用户
        if ($type == "new") {
            // 调用协作的函数，为新建的用户，添加 cooperation_purview 表信息
            app($this->cooperationService)->newUserAddCooperationPurview($data);
            // 设置用户排班
            if (isset($data['attendance_scheduling']) && !empty($data['attendance_scheduling'])) {
                app($this->attendanceSettingService)->setUserScheduling($data['attendance_scheduling'], $data['user_id']);
            }
            //自动生成人事档案
            if (isset($data['is_autohrms']) && $data['is_autohrms'] == '1') {
                app($this->personnelFilesService)->createPersonnelFile($personnelFileData, $own);
            }

            app('App\EofficeApp\Vacation\Services\VacationService')->onAddUser($data['user_id']);
            // 新建用户记录角色缓存
            if (isset($data['role_id']) && !empty($data['role_id'])) {
                $roleId = array_filter(explode(',', $data['role_id']));
                Cache::forever('user_role_' . $data['user_id'], $roleId);
            }
        } elseif ($type == 'edit') {
            // 清除编辑用户原先的角色缓存
            $userOldRoleIds = isset($data['user_old_role_id']) ? $data['user_old_role_id'] : array();
            $userNewRoleIds = array();
            if (isset($data['role_id']) && !empty($data['role_id'])) {
                $userNewRoleIds = array_filter(explode(',', $data['role_id']));
            }
            if ((count($userNewRoleIds) != count($userOldRoleIds)) || !empty(array_diff($userNewRoleIds, $userOldRoleIds))) {
                Cache::forget('user_role_' . $data['user_id']);
            }
            // 编辑用户
            //自动更新人事档案
            if ((isset($data['is_autohrms']) && $data['is_autohrms'] == '1') || (isset($data['user_has_one_system_info']['is_autohrms']) && $data['user_has_one_system_info']['is_autohrms'] == '1')) {
                if ($personnelFileData['user_id'] == 'admin') {
                    // admin人事档案同步特殊处理
                    $checkPersonnelFile = app($this->personnelFilesRepository)->getPersonnelFilesByWhere(['user_id' => ['admin']]);
                    if (empty($checkPersonnelFile)) {
                        app($this->personnelFilesService)->createPersonnelFile($personnelFileData, $own);
                    } else {
                        app($this->personnelFilesService)->modifyPersonnelFileByUserId($personnelFileData, $own);
                    }
                } else {
                    app($this->personnelFilesService)->modifyPersonnelFileByUserId($personnelFileData, $own);
                }
            }
            if ($data['user_status'] == '2') {
                // 设置为离职
                // 解除日程中的关注关系
                app($this->calendarAttentionRepository)->delectUnemployedAttention($data['user_id']);
                app($this->attendanceSettingService)->userLeaveDeleteUserAttendanceScheduling($data['user_id']);
            } else {
                // 编辑用户信息
                // 设置用户排班
                if (isset($data['attendance_scheduling']) && !empty($data['attendance_scheduling'])) {
                    app($this->attendanceSettingService)->setUserScheduling($data['attendance_scheduling'], $data['user_id']);
                }
            }
        } elseif ($type == 'delete') {
            // 删除用户
            // 解除日程中的关注关系
            app($this->calendarAttentionRepository)->delectUnemployedAttention($data['user_id']);
            // 清除删除用户的角色缓存
            Cache::forget('user_role_' . $data['user_id']);
        }

        // 清除微博默认关注所有下级的缓存用户信息
        $params = array(
            'search' => [
                'user_accounts' => ['', '!=']
            ]
        );
        $userList = app($this->userRepository)->getAllUsers($params);
        if (!empty($userList)) {
            foreach ($userList as $key => $value) {
                if ($value->user_id) {
                    Cache::forget('default_attention_list_' . $value->user_id);
                }
            }
        }
        // 清除客户用户上下级缓存
	    Redis::del(self::USER_SUBORDINATE_REDIS_KEY);
        Cache::forget(self::INVALID_USER_IDS_CACHE_KEY);
        // 生成或更新用户二维码
        $this->setUserQrCode($data['user_id']);

        return true;
    }

    /**
     * 生成用户二维码
     *
     * @author 缪晨晨
     *
     * @param  array or string  $userInfo [用户信息或用户ID]
     *
     * @since  2018-04-03 创建
     *
     * @return array
     */
    public function setUserQrCode($userInfo)
    {
        // App\EofficeApp\PersonalSet\PersonalSetServices 这个方法在这个类里面写过一遍了 两边不能互相引入service 暂先再写一遍这里
        //二维码路径
        $qrCodePath = createCustomDir("qrcode");
        if (!$qrCodePath) {
            return ['code' => ['0x000006', 'common']];
        }

        //参数可以直接是用户数据,也可以是用户ID,再自己获取数据
        if(!is_array($userInfo)) {
            $userAllData = app($this->userRepository)->getUserAllData($userInfo);
            $roleName = [];
            if (!empty($userAllData)) {
                if (isset($userAllData['userHasManyRole'])) {
                    foreach ($userAllData['userHasManyRole'] as $key => $value) {
                        if (isset($value['hasOneRole']['role_name'])) {
                            $roleName[] = $value['hasOneRole']['role_name'];
                        }
                    }
                }
                $userInfo = [
                    'user_id'      => isset($userAllData['user_id']) ? $userAllData['user_id'] : '',
                    'user_name'    => isset($userAllData['user_name']) ? $userAllData['user_name'] : '',
                    'dept_name'    => isset($userAllData['userHasOneSystemInfo']['userSystemInfoBelongsToDepartment']['dept_name']) ? $userAllData['userHasOneSystemInfo']['userSystemInfoBelongsToDepartment']['dept_name'] : '',
                    'role_name'    => !empty($roleName) ? implode(',', $roleName) : '',
                    'email'        => isset($userAllData['userHasOneInfo']['email']) ? $userAllData['userHasOneInfo']['email'] : '',
                    'phone_number' => isset($userAllData['userHasOneInfo']['phone_number']) ? $userAllData['userHasOneInfo']['phone_number'] : ''
                ];
            } else {
                return false;
            }
        }

        //公司信息
        $companyData = app($this->companyService)->getCompanyDetail();

        // 二维码数据
        $codeContents  = 'BEGIN:VCARD'."\n";
        $codeContents .= 'VERSION:4.0'."\n";
        $codeContents .= 'N:'. (isset($userInfo["user_name"]) ? $userInfo["user_name"] : '')."\n";
        $codeContents .= 'ROLE:'.(isset($userInfo["user_name"]) ? $userInfo["dept_name"] : '')."\n";
        $codeContents .= 'TITLE:'.(isset($userInfo["user_name"]) ? $userInfo["role_name"] : '')."\n";
        $codeContents .= 'EMAIL:'.(isset($userInfo["user_name"]) ? $userInfo['email'] : '')."\n";
        $codeContents .= 'TEL;TYPE=cell:'.(isset($userInfo["user_name"]) ? $userInfo['phone_number'] : '')."\n";
        $codeContents .= 'ORG:'.(isset($companyData['company_name']) ? $companyData['company_name'] : '')."\n";
        $codeContents .= 'ADR;TYPE=WORK,PREF:'.(isset($companyData['company_name']) ? $companyData["address"] : '') . ";" . (isset($companyData['company_name']) ? $companyData["zip_code"] : '') ."\n";
        $codeContents .= 'END:VCARD';

        if (isset($userInfo['user_id'])) {
            if (file_exists($qrCodePath . $userInfo['user_id'] . ".png")) {
                @unlink($qrCodePath . $userInfo['user_id'] . ".png");
            }

            //生成二维码
            return QrCode::format('png')->encoding('UTF-8')->size(150)->margin(0)
                        ->generate($codeContents, $qrCodePath.$userInfo['user_id'].'.png');
        } else {
            return false;
        }
    }
    // 生成共享录入二维码
    public function getUserRegisterQrcode() {
        //二维码路径
        $qrCodePath = createCustomDir("qrcode");
        if (!$qrCodePath) {
            return ['code' => ['0x000006', 'common']];
        }
        $qrcode = $qrCodePath.'register.png';
        // ip变的时候，重新生成二维码
        if (Cache::has('eoffice_user_register_qrcode_ip')) {
            if (Cache::get('eoffice_user_register_qrcode_ip') != OA_SERVICE_HOST) {
                Cache::forget('eoffice_user_register_qrcode');
                Cache::forever('eoffice_user_register_qrcode_ip', OA_SERVICE_HOST);
            }
        } else {
            Cache::forever('eoffice_user_register_qrcode_ip', OA_SERVICE_HOST);
        }
        if (!file_exists($qrcode)) {
            Cache::forget('eoffice_user_register_qrcode');
        }
        if(!Cache::has('eoffice_user_register_qrcode')){
            // 设置有效期24小时
            $sign = substr(md5(strtotime(date("Y-m-d H:i:s"))), 0, 6);
            Cache::put('eoffice_user_register_qrcode', $sign, 1440);

            $domain = OA_SERVICE_PROTOCOL . "://" . OA_SERVICE_HOST;
            $url = $domain.'/eoffice10/client/mobile/fast-entry?sign='.$sign;
            QrCode::format('png')->encoding('UTF-8')->size(150)->margin(0)
                            ->generate($url, $qrcode);
        }

        return ['qrcode' => imageToBase64($qrcode), 'sign' => Cache::get('eoffice_user_register_qrcode')];
    }
    public function downloadQrcode($own) {
        $qrPath = $this->getUserRegisterQrcode();
        if (isset($qrPath['qrcode'])) {
            $param = ['operate' => 'download'];
            $param['operate'] = 'download';
            $attachment = app($this->attachmentService)->base64Attachment(['image_file' => $qrPath['qrcode'], 'image_name' => 'qrCode'], $own['user_id']);
            $attachmentId = isset($attachment['attachment_id']) ? $attachment['attachment_id'] : '';
            $result = app($this->attachmentService)->loadAttachment($attachmentId, $param, $own, false);
            return $attachment;
        }
        return false;

   }
    public function checkRegisterQrcode($sign) {
        if (Cache::has('eoffice_user_register_qrcode') && Cache::get('eoffice_user_register_qrcode') == $sign) {
            return true;
        } else {
            return ['code' => ['0x000006', 'common']];
        }
    }
    public function userShareRegister($data) {
        $fields = ['name', 'dept', 'account'];
        foreach ($fields as $value) {
            if (!isset($data[$value]) || empty($data[$value])) {
                return ['code' => ['0x000001', 'common']];
            }
        }
        // 判断是否有开启密码强度限制
        // 判断是否开启了密码强度设置
        $password = $data['password'] ?? '';
        // 判断密码是否包含中文
        if (preg_match("/([\x81-\xfe][\x40-\xfe])/", $password, $match)) {
            return ['code' => ['0x005098','user'], 'dynamic' => trans('user.0x005098')];
        }
        // $return = $this->parsePassword($password);
        // if (isset($return['code'])) {
        //     return $return;
        // }
        if (array_key_exists('password', $data) && array_key_exists('samePassword', $data)) {
            if ($data['password'] != $data['samePassword']) {
                return ['code' => ['0x005088', 'user']];
            }
        }

        $insertData = [
            'account' => $data['account'],
            'user_name' => $data['name'],
            'dept_name' => $data['dept'],
            'password' => isset($data['password']) ? $data['password'] : '',
            'phone' => isset($data['phone']) ? $data['phone'] : '',
        ];

        app($this->userQuickRegisterRepository)->insertData($insertData);

        $sendData = [
            'remindMark' => 'user-register',
            'toUser' => 'admin',
            'contentParam' => ['userName' => $data['name']]
        ];
        Eoffice::sendMessage($sendData);

        return true;
    }
    // 审核用户列表
    public function getRegisterUser($param) {
        $existsDepts = [];
        $depts = app($this->departmentService)->listDept(['page' => 0, 'response' => 'data']);
        if (isset($depts['list']) && !empty($depts['list'])) {
            $existsDepts = array_column($depts['list'], 'dept_name', 'dept_id');
        }
        $data = $this->response(app($this->userQuickRegisterRepository), 'getRegisterUserTotal', 'getRegisterUser', $this->parseParams($param));
        if (!empty($data['list'])) {
            foreach ($data['list'] as $key => $value) {
                $data['list'][$key]['dept_exists'] = in_array($value['dept_name'], $existsDepts) ? 1 : 0;
            }
        }
        return $data;
    }
    // 用户审核主页数据
    public function getRegisterUserPageData($param) {
        $unapprove = app($this->userQuickRegisterRepository)->getRegisterUserTotal(['search' => ['status' => [0]]]);
        $refuse = app($this->userQuickRegisterRepository)->getRegisterUserTotal(['search' => ['status' => [2]]]);
        $approved = app($this->userQuickRegisterRepository)->getRegisterUserTotal(['search' => ['status' => [1]]]);
        $count = ['unapprove' => $unapprove, 'refuse' => $refuse, 'approved' => $approved];

        return $count;
    }
    // 设置部门后审核用户
    public function setDeptAndCheckUser($data, $own) {
        if (!isset($data['id']) || empty($data['id'])) {
            return ['code' => ['0x000001', 'common']];
        }

        $registerData = app($this->userQuickRegisterRepository)->getDetail($data['id']);

        $checkUserAccount = $this->validateUserAccountsValidation($data['account'] ?? '');
        if (!$checkUserAccount) {
            return ['code' => ['0x005015', 'user']];
        }
        if (!$this->checkPcUserNumberWhetherExceed()) {
            return ['code' => ['0x005007', 'user']];
        }
        $checkResult = $this->checkUserInfoUnique(['user_accounts' => $data['account'] ?? ''], '');
        if (!empty($checkResult)) {
            return $checkResult;
        }

        $isDeptExists = $data['dept_exists'] ?? 0;
        if ($isDeptExists) {
            // 部门存在
            $deptName = $data['dept_name'] ?? '';
            $dept = app($this->departmentRepository)->getOneFieldInfo(['dept_name' => $deptName]);
            $deptId = $dept->dept_id;
        } else {
            // 部门不存在
            $choice = isset($data['choice']) ? $data['choice'] : 0;
            if ($choice == 0) {
                // 创建部门，录入用户
                $deptName = $data['dept_name'] ?? '';
                $parent = $data['parent_dept'] ?? 0;
                $dept = app($this->departmentRepository)->getOneFieldInfo(['dept_name' => $deptName]);
                if (!empty($dept)) {
                    $deptId = $dept->dept_id;
                } else {
                    $maxSort = app($this->departmentRepository)->getMaxSortByParent($parent) ?? 0;
                    $deptSort = $maxSort + 1;
                    $newDept = app($this->departmentService)->addDepartment(['dept_name' => $deptName, 'parent_id' => $parent, 'dept_sort' => $deptSort], $own['user_id']);
                    if (isset($newDept['code'])) {
                        return $newDept;
                    }
                    $deptId = $newDept['dept_id'];
                }
            } else {
                // 已有部门
                $deptId = $data['dept_id'];
                $deptInfo = app($this->departmentRepository)->getDetail($deptId);
                $deptName = isset($deptInfo->dept_name) ? $deptInfo->dept_name : '';
            }
        }

        $result = $this->addDeptUser([
            'account' => $data['account'] ?? '',
            'user_name' => $data['user_name'] ?? '',
            'dept_id' => $deptId,
            'is_autohrms' => $data['is_autohrms'] ?? 0,
            'password' => isset($registerData->password) ? $registerData->password : '',
            'phone' => isset($registerData->phone) ? $registerData->phone : '',
        ], $own);
        if (isset($result['code'])) return $result;
        //补充 企业微信同步组织架构
        try {
            if (isset($result['user_id'])){
                $toWorkWeChatData = [
                    'type' => 'add',
                    'user_id' => $result['user_id']
                ];
                app($this->workWechatService)->importUserToWorkWeChatUser($toWorkWeChatData);
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }
        return app($this->userQuickRegisterRepository)->updateData(['status' => 1, 'dept_name' => $deptName], ['id' => $data['id']]);
    }
    // 审核用户
    public function userRegisterCheck($id, $type, $own) {
        // 同意则将用户信息录入系统当中
        if ($type == 1) {
            $registerInfo = app($this->userQuickRegisterRepository)->getDetail($id);
            // 判断部门是否存在
            $dept = app($this->departmentRepository)->getOneFieldInfo(['dept_name' => $registerInfo->dept_name]);
            if (empty($dept)) return ['code' => ['0x005089', 'user']];
            $roleId = $this->getDefaultRole();
            // 录入系统
            $userData = [
                'attendance_scheduling' => 1,
                'user_accounts' => $registerInfo->account ?? '',
                'user_name' => $registerInfo->user_name ?? '',
                'user_password' => $registerInfo->password ?? '',
                'role_id_init' => $roleId ?? 1,
                'post_priv' => "0",
                'wap_allow' => 0,
                'user_status' => 1,
                'dept_id' => $dept->dept_id ?? '',
                'sex' => "1",
                'user_superior_id' => "",
                'user_subordinate_id' => "",
                'list_number' => "",
                'phone_number' => $registerInfo->phone ?? ''
            ];
            $oldSecuritySwitch = get_system_param('login_password_security_switch');
            $oldForceChange = get_system_param('force_change_password');
            if ($oldSecuritySwitch && $oldForceChange) {
                $userData['change_pwd'] = 1;
            }
            $create = $this->userSystemCreate($userData, $own);
            if (isset($create['code'])) return $create;
        }
        return app($this->userQuickRegisterRepository)->updateData(['status' => $type], ['id' => $id]);
    }
    // 批量审核
    public function batchCheckRegisterUser($type, $data, $own) {
        $ids = $data['ids'] ?? [];
        if (empty($ids)) {
            return false;
        }
        $deptId = isset($data['dept_id']) ? $data['dept_id'] : '';
        if (!empty($deptId)) {
            $deptInfo = app($this->departmentRepository)->getDetail($deptId);
            $deptName = $deptInfo->dept_name;
        } else {
            $deptName = '';
        }
        if ($type == 1) {
            // 批量通过
            $users = app($this->userQuickRegisterRepository)->getRegisterUser([
                'page' => 0,
                'search' => ['id' => [$ids, 'in']]
            ]);
            $insertData = [];

            $roleId = $this->getDefaultRole();

            if (!$users->isEmpty()) {
                foreach ($users as $user) {
                    // 判断部门是否存在
                    if (empty($deptId)) {
                        $dept = app($this->departmentRepository)->getOneFieldInfo(['dept_name' => $user->dept_name]);
                        if (empty($dept)) return ['code' => ['0x005089', 'user'], 'dynamic' => $user->user_name.' '.trans('user.entry_department_does_not_exist')];
                        $tempDeptId = $dept->dept_id ?? '';
                    } else {
                        $tempDeptId = $deptId;
                    }

                    $insertData[] = [
                        'attendance_scheduling' => 1,
                        'user_accounts' => $user->account ?? '',
                        'user_name' => $user->user_name ?? '',
                        'user_password' => $user->password ?? '',
                        'role_id_init' => $roleId ?? 1,
                        'post_priv' => "0",
                        'wap_allow' => 0,
                        'user_status' => 1,
                        'dept_id' => $tempDeptId,
                        'sex' => "1",
                        'user_superior_id' => "",
                        'user_subordinate_id' => "",
                        'list_number' => "",
                        'phone_number' => $user->phone ?? '',
                        'is_autohrms' => $data['is_autohrms'] ?? 0
                    ];

                }
            }
            if (!empty($insertData)) {
                foreach ($insertData as $value) {
                    $create = $this->userSystemCreate($value, $own);
                    if (isset($create['code'])) return $create;
                    //补充 企业微信同步组织架构
                    try {
                        if (isset($create['user_id'])){
                            $toWorkWeChatData = [
                                'type' => 'add',
                                'user_id' => $create['user_id']
                            ];
                            app($this->workWechatService)->importUserToWorkWeChatUser($toWorkWeChatData);
                        }
                    } catch (\Exception $e) {
                        Log::error($e->getMessage());
                    }
                }
            }
        }
        $updateData = ['status' => $type];
        if (!empty($deptName)) {
            $updateData['dept_name'] = $deptName;
        }
        return app($this->userQuickRegisterRepository)->updateData($updateData, ['id' => [$ids, 'in']]);
    }
    /**
     * 报表-用户数量(状态、部门、角色)
     *
     * @author 缪晨晨
     *
     * @param  分组依据 ：  $datasourceGroupBy       string
     * @param  数据分析字段 ：  $datasourceDataAnalysis  array
     * @param  数据过滤字段 ：  $chartSearch             array
     *
     * @since  2017-07-13 创建
     *
     * @return array
     */
    public function getUserReportData($datasourceGroupBy = 'userStatus', $datasourceDataAnalysis = '', $chartSearch)
    {
        if ($datasourceGroupBy == 'userStatus') {
            $returnArray = array(
                '0' => [
                    'name' => trans('user.count'),
                    'group_by' => trans('user.status'),
                    'data' => array()
                ]);
            // 状态
            $userStatusList = app($this->userStatusRepository)->userStatusListRepository();
            $userStatusIdsArray = array('0' => trans('user.delete'));
            if (!empty($userStatusList)) {
                foreach ($userStatusList as $value) {
                    $status_name = mulit_trans_dynamic("user_status.status_name.user_status_".$value['status_id']);
                    $userStatusIdsArray[$value['status_id']] = $status_name;
                }
            }
            $userStatusData = array();
            $tempUserStatusData = array();
            $userCountGroupByUserStatus = app($this->userRepository)->getUserCountGroupByCustomType($datasourceGroupBy, $chartSearch);
            if (!empty($userCountGroupByUserStatus)) {
                foreach ($userCountGroupByUserStatus as $key => $value) {
                    if (isset($userStatusIdsArray[$value['user_status']]) && isset($value['user_count'])) {
                        $tempUserStatusData[$userStatusIdsArray[$value['user_status']]] = $value['user_count'];
                    }
                }
                if (!empty($tempUserStatusData)) {
                    foreach ($tempUserStatusData as $key => $value) {
                        $userStatusData[] = ['name' => $key, 'y' => $value];
                    }
                }
            }
            $returnArray[0]['data'] = $userStatusData;
        } elseif ($datasourceGroupBy == 'userDept') {
            $returnArray = array(
                '0' => [
                    'name' => trans('user.count'),
                    'group_by' => trans('user.dept_name'),
                    'data' => array()
                ]);
            // 部门
            $searchDeptId = array();
            if (isset($chartSearch['dept_id']) && !empty($chartSearch['dept_id'])) {
                $searchDeptId = $chartSearch['dept_id'];
                if (!(is_array($searchDeptId) || is_object($searchDeptId))) {
                    $searchDeptId = explode(",", trim($searchDeptId, ","));
                }
                $chartSearch['dept_id'] = $searchDeptId;
            }
            $userDeptList = app($this->departmentRepository)->getAllDepartment();
            $deptIdsArray = array();
            // 部门这里可重名 用id来区分
            if (!empty($userDeptList)) {
                foreach ($userDeptList as $value) {
                    if (!empty($searchDeptId) && !in_array($value->dept_id, $searchDeptId)) {
                        continue;
                    }
                    $deptIdsArray['id_' . $value->dept_id] = array('dept_name' => $value->dept_name, 'user_count' => 0);
                }
            }
            $userDepartmentData = array();
            $userCountGroupByDepartment = app($this->userRepository)->getUserCountGroupByCustomType($datasourceGroupBy, $chartSearch);
            if (!empty($userCountGroupByDepartment)) {
                foreach ($userCountGroupByDepartment as $key => $value) {
                    if (isset($value['user_count']) && isset($value['dept_name'])) {
                        $tempUserDepartmentData['id_' . $value['dept_id']] = array('dept_name' => $value['dept_name'], 'user_count' => $value['user_count']);
                    }
                }
                $tempUserDepartmentData = array_merge($deptIdsArray, $tempUserDepartmentData);
            }
            if (!empty($tempUserDepartmentData)) {
                foreach ($tempUserDepartmentData as $key => $value) {
                    $userDepartmentData[] = ['name' => $value['dept_name'], 'y' => $value['user_count']];
                }
            }
            $returnArray[0]['data'] = $userDepartmentData;
        } elseif ($datasourceGroupBy == 'userRole') {
            $returnArray = array(
                '0' => [
                    'name' => trans('user.count'),
                    'group_by' => trans('user.role_name'),
                    'data' => array()
                ]);
            // 角色
            $searchRoleId = array();
            if (isset($chartSearch['role_id']) && !empty($chartSearch['role_id'])) {
                $searchRoleId = $chartSearch['role_id'];
                if (!(is_array($searchRoleId) || is_object($searchRoleId))) {
                    $searchRoleId = explode(",", trim($searchRoleId, ","));
                }
                $chartSearch['role_id'] = $searchRoleId;
            }
            $userRoleList = app($this->roleRepository)->getRoleList();
            $userRoleNamesArray = array();
            // 角色名称是唯一的
            if (!empty($userRoleList)) {
                foreach ($userRoleList as $value) {
                    if (!empty($searchRoleId) && !in_array($value['role_id'], $searchRoleId)) {
                        continue;
                    }
                    $userRoleNamesArray[$value['role_name']] = 0;
                }
            }
            $userRoleData = array();
            $tempUserRoleData = array();
            $userCountGroupByUserRole = app($this->userRepository)->getUserCountGroupByCustomType($datasourceGroupBy, $chartSearch);
            if (!empty($userCountGroupByUserRole)) {
                foreach ($userCountGroupByUserRole as $key => $value) {
                    if (isset($value['user_count']) && isset($value['role_name'])) {
                        $tempUserRoleData[$value['role_name']] = $value['user_count'];
                    }
                }
                $tempUserRoleData = array_merge($userRoleNamesArray, $tempUserRoleData);
            }
            if (!empty($tempUserRoleData)) {
                foreach ($tempUserRoleData as $key => $value) {
                    $userRoleData[] = ['name' => $key, 'y' => $value];
                }
            }
            $returnArray[0]['data'] = $userRoleData;
        } else {
            $returnArray = array();
        }
        return $returnArray;
    }

    /**
     * 获取用户关注的所有下级
     * @return array 返回用户信息数组
     */
    public function getAllUserSuperior($userId,$param = [])
    {
        if (Cache::has('default_attention_list_'.$userId)) {
            $defaultAttentionList = Cache::get('default_attention_list_'.$userId);
            return $defaultAttentionList;
        }
        $param = $this->parseParams($param);

        if (!isset($userId) || empty($userId)) {
            return [];
        }
        $missions = [];
        if (isset($param['calendarSet']) && !empty($param['calendarSet'])) {
            $missions = json_decode($param['calendarSet'], true);
        }
        $param['diarySet'] = [];
        if ($missions && isset($missions['dimission'])) {
            $param['diarySet']['dimission'] = $missions['dimission'];
        }
        $SubordinateUserId = $this->getAllSubordinateArrayByUserId($userId,$param);
        $list = [];
        if(count($SubordinateUserId) > 0){
            // 获取所有用户的信息
            $list = DB::table('user')
                // ->join('user_info','user.user_id','=','user_info.user_id')
                ->join('user_system_info','user.user_id','=','user_system_info.user_id')
                ->join('department','department.dept_id','=','user_system_info.dept_id')
                ->select('user.user_id','user.user_name','department.dept_name','user_system_info.user_status');
            // ->select('user.user_id','user.user_accounts','user.user_name','user_info.*','user_system_info.*','department.dept_id','department.dept_name','department.tel_no','department.dept_name_py','department.dept_name_zm')
            if(isset($param['diarySet']['dimission']) && $param['diarySet']['dimission'] != 1){
                //不显示离职用户
                $list = $list->where("user_system_info.user_status", '!=', 2);
            }
            $list = $list->where("user_system_info.user_status", '>', 0)
                         ->whereIn("user.user_id",$SubordinateUserId)
                         ->get();
            $list = json_decode(json_encode($list),true);
        }

        if (!isset($list) || empty($list)) {
            return [];
        }
        // 根据用户ID获取用户所有信息
        $userInfo = [];
        foreach ($list as $j => $s) {
            $userInfo['list'][] = $list[$j];
        }
        if (!isset($userInfo['list']) && empty($userInfo['list'])) {
            return [];
        }
        $userInfo['total'] = count($userInfo['list']);
        // // 根据条件取出前n个用户,方便翻页
        // if (isset($param['page']) && !empty($param['page'])) {
        //     $userInfo['list'] = [];
        //     $smallList = [];
        //     $page = $param['page'];
        //     $limit = $param['limit'];
        //     foreach ($result as $k => $v) {
        //         if ($k >= ($page-1) * $limit && $k < $page * $limit ) {
        //             $smallList[] = $v;
        //         }
        //     }
        // // 根据用户ID获取用户所有信息
        //     if (isset($smallList) && !empty($smallList)) {
        //         foreach ($smallList as $k => $v) {
        //             foreach ($list as $j => $s) {
        //                 if ($s['user_id'] == $v) {
        //                     $userInfo['list'][] = $s;
        //                     continue;
        //                 }
        //             }
        //         }
        //     }
        //     return $userInfo;
        // }
        Cache::add('default_attention_list_'.$userId,$userInfo,60);
        return $userInfo;
    }

    /**
     * 获取下级用户所有userid
     * @param  string or array $userId 用户id数组或者字符串
     * @param  array $param            参数
     * @param  string $n               递增
     * @param  string $result          所有用户id数组集合
     * @param  array  $subordinateRows 所有下级用户数组集合
     * @return array                   排除离职用户后，所有下级用户id数组集合
     * @author longmiao
     */
    public function getAllSubordinateArrayByUserId($userId,$param = [],$n = '0',$result = '')
    {
        if ($n == '0') {
            $list = DB::table('user_superior')->select('user_id','superior_user_id')->get();
            $list = json_decode(json_encode($list),true);
            $n++;
            // // 查询出所有用户的用户名和用户id
            // $userAccounts = DB::table('user')->select('user_id','user_accounts')->get();
            // $userAccounts = json_decode(json_encode($userAccounts),true);
        } else {
            $list = $result;
            // $userAccounts = $userAccounts;
        }
        // 如果没有获取用户名，则返回空
        if (!isset($list) || empty($list)) {
            return [];
        }

        // 获取用户所有一级下级
        $rows = [];
        global $subordinateRows;
        foreach ($list as $k => $v) {
            // 判断传入的userId是否是数组
            if (is_array($userId)) {
                foreach ($userId as $j) {
                    if ($v['superior_user_id'] == $j) {
                        $rows[] = $v['user_id'];
                        // 保存所有用户id，存入下次循环
                        $subordinateRows[] = $v['user_id'];
                        unset($list[$k]);
                    }
                }
            } else {
                if ($v['superior_user_id'] == $userId) {
                    $rows[] = $v['user_id'];
                    // 保存所有用户id，存入下次循环
                    $subordinateRows[] = $v['user_id'];
                    unset($list[$k]);
                }
            }

        }
        $userIds = [];
        if (isset($list) && !empty($list)) {
            foreach ($list as $k => $v) {
                $superiorList[] = $v['superior_user_id'];
            }
            // 如果用户id在数组中，则还有下一级
            if (array_intersect($rows,$superiorList) || in_array($rows,$superiorList)) {
                $this->getAllSubordinateArrayByUserId($rows,$param,$n,$list);
            }
        }

        // 查询所有用户的离职状态
        // if (isset($subordinateRows) && !empty($subordinateRows)) {

        //     foreach ($subordinateRows as $v) {
        //         foreach ($userAccounts as $key => $value) {
        //             if ($value['user_id'] == $v) {
        //                 $user_name = $value['user_accounts'];
        //                 if (!empty($user_name)) {
        //                     $userIds[] = $value['user_id'];
        //                     continue;
        //                 }
        //             }
        //         }
        //     }
        // }

        // 必须再次读取配置，因为关注列表单独请求api，需要判断是否设置显示离职人员
        $diarySet = app($this->diaryService)->getPermission();
        $param['diarySet'] = [];
        $param['diarySet']['dimission'] = $diarySet['dimission'];
        if (isset($subordinateRows) && !empty($subordinateRows)) {
            // 判断是否显示离职人员
            if (isset($param['diarySet']) && $param['diarySet']['dimission'] == 1) {
                foreach ($subordinateRows as $v) {
                    $userStatus = DB::table('user_system_info')->select('user_status')->where('user_id',$v)->first();
                    $userStatus = $userStatus->user_status ?? 0;
                    if (isset($userStatus) && $userStatus > 0) {
                        $userIds[] = $v;
                        continue;
                    }
                }
            } else {
                foreach ($subordinateRows as $v) {
                    $userAccount = DB::table('user')->select('user_accounts')->where('user_id',$v)->first();
                    $userAccount = $userAccount->user_accounts ?? '';
                    if (isset($userAccount) && !empty($userAccount)) {
                            $userIds[] = $v;
                            continue;
                    }
                }
            }

        }
        unset($GLOBALS['subordinateRows']);
        if (!empty($userIds)) {
            $userIds = array_unique($userIds);
        }
        return $userIds;

    }


    /**
     * 表单系统数据获取用户相关数据
     *
     */
    public function getUserDataForFormDatasource($useraccounts)
    {
        if(empty($useraccounts)) {
            return '';
        }
        $returnData = [
            'dept_name'         => '',
            'sex_name'          => '',
            'user_superior'     => '',
            'phone_number'      => '',
            'user_position'     => '',
            'user_area'         => '',
            'user_city'         => '',
            'user_workplace'    => '',
            'user_job_category' => '',
            'role_name'         => '',
            'user_job_number'   => ''
        ];
        $userInfo = app($this->userRepository)->getUserAllDataByAccount(urldecode($useraccounts));
        $roleName = [];

        if($userInfo) {
            $superior = '';
            if ($userInfo->userHasManyRole) {
                foreach ($userInfo->userHasManyRole as $key => $value) {
                    $roleName[] = isset($value->hasOneRole->role_name) ? $value->hasOneRole->role_name : '';
                }
            }
            $returnData['role_name'] = implode(',', $roleName);
            if($userInfo->userHasManySuperior) {
                foreach ($userInfo->userHasManySuperior as $value) {
                    if (isset($value['superiorHasOneUser']) && !empty($value['superiorHasOneUser']) && isset($value['superiorHasOneUser']['user_name']) && !empty($value['superiorHasOneUser']['user_name'])) {
                        if(!empty($superior)) {
                            $superior .= ',';
                        }
                        $superior .= $value['superiorHasOneUser']['user_name'];
                    }
                }
            }
            if($userInfo->userHasOneInfo) {
                if ($userInfo->userHasOneInfo->sex && $userInfo->userHasOneInfo->sex == '1') {
                    $returnData['sex_name'] = trans("user.0x005064"); // 男
                } else {
                    $returnData['sex_name'] = trans("user.0x005065"); // 女
                }
            }
            $returnData['user_superior'] = $superior;
            if (isset($userInfo->userHasOneSystemInfo->userSystemInfoBelongsToDepartment->dept_name)) {
                $returnData['dept_name'] = $userInfo->userHasOneSystemInfo->userSystemInfoBelongsToDepartment->dept_name;
            }
            if (isset($userInfo->userHasOneInfo->phone_number)) {
                $returnData['phone_number'] = $userInfo->userHasOneInfo->phone_number;
            }
            if (isset($userInfo->user_position) && !empty($userInfo->user_position) && isset($this->userSelectField['user_position'])) {
                $returnData['user_position'] = app($this->systemComboboxService)->getComboboxFieldsNameByIdentify($this->userSelectField['user_position'], $userInfo->user_position);
            }
            if (isset($userInfo->user_area) && !empty($userInfo->user_area) && isset($this->userSelectField['user_area'])) {
                $returnData['user_area'] = app($this->systemComboboxService)->getComboboxFieldsNameByIdentify($this->userSelectField['user_area'], $userInfo->user_area);
            }
            if (isset($userInfo->user_city) && !empty($userInfo->user_city) && isset($this->userSelectField['user_city'])) {
                $returnData['user_city'] = app($this->systemComboboxService)->getComboboxFieldsNameByIdentify($this->userSelectField['user_city'], $userInfo->user_city);
            }
            if (isset($userInfo->user_workplace) && !empty($userInfo->user_workplace) && isset($this->userSelectField['user_workplace'])) {
                $returnData['user_workplace'] = app($this->systemComboboxService)->getComboboxFieldsNameByIdentify($this->userSelectField['user_workplace'], $userInfo->user_workplace);
            }
            if (isset($userInfo->user_job_category) && !empty($userInfo->user_job_category) && isset($this->userSelectField['user_job_category'])) {
                $returnData['user_job_category'] = app($this->systemComboboxService)->getComboboxFieldsNameByIdentify($this->userSelectField['user_job_category'], $userInfo->user_job_category);
            }
            $returnData['user_job_number'] = $userInfo->user_job_number ?? '';
        }
        return $returnData;
    }

    /**
     * 获取报表用户状态值下拉框
     * @param   $param
     * @return  array
     */
    public function getUserDatasourceFilter($param='')
    {
        $allUserStatusList = $this->userStatusList($param);
        $allUserStatusList =isset($allUserStatusList['list']) ?$allUserStatusList['list'] :[];
        $userStatusList = [];
        foreach ($allUserStatusList as $k => $v) {
            if (isset($v['status_id']) && isset($v['status_name'])) {
                $userStatusList[$v['status_id']] = $v['status_name'];
            }
        }
        $datasource_filter = [
            [
                'filter_type' => 'selector',
                'selector_type' => 'dept',
                'itemValue' => 'dept_id',
                'itemName' => trans('report.user_dept')
            ],
            [
                'filter_type' => 'selector',
                'selector_type' => 'role',
                'itemValue' => 'role_id',
                'itemName' => trans('report.user_role')
            ],
            [
                'filter_type' => 'singleton',
                'itemValue' => 'user_status',
                'itemName' => trans('user.status_name'),
                'source' => $userStatusList,
            ]
        ];
        return $datasource_filter;
    }

    public function unlockUserAccount($userId)
    {
        // 重置错误密码次数
        ecache('Auth:WrongPwdTimes')->clear($userId);
    }

    public function leaveUserAccount($userId, $param, $loginUserInfo)
    {
        if (empty($userId)) {
            return ['code' => ['0x000003', 'common']];
        }
        $param['user_status'] = 2;
        $param['user_has_one_system_info']['user_status'] = 2;
        return $this->userSystemEdit($param, $loginUserInfo);

    }

    /**
     * 获取用户工号自动生成规则
     *
     * @author 缪晨晨
     *
     * @param
     *
     * @since  2018-10-09 创建
     *
     * @return string
     */
    public function getUserJobNumberRule()
    {
        $userJobNumberRule = DB::table('user_other_set')->where('param_key', 'user_job_number_rule')->first();
        if (isset($userJobNumberRule->param_value)) {
            return $userJobNumberRule->param_value;
        } else {
            return '';
        }
    }

    /**
     * 获取用户其他设置
     *
     * @author 缪晨晨
     *
     * @param
     *
     * @since  2018-10-09 创建
     *
     * @return array
     */
    public function getUserOtherSettings()
    {
        $userOtherSettingsInfo = DB::table('user_other_set')->get()->toArray();
        if (empty($userOtherSettingsInfo)) {
            return [];
        }
        $userOtherSettingsResult = [];
        foreach ($userOtherSettingsInfo as $key => $value) {
            if (isset($value->param_key) && isset($value->param_value)) {
                $userOtherSettingsResult[$value->param_key] = $value->param_value;
            }
        }
        return $userOtherSettingsResult;
    }

    /**
     * 编辑用户其他设置
     *
     * @author 缪晨晨
     *
     * @param
     *
     * @since  2018-10-09 创建
     *
     * @return array
     */
    public function editUserOtherSettings($data)
    {
        DB::table('user_other_set')->truncate();
        if (empty($data)) {
            return true;
        }
        $insertData = [];
        foreach ($data as $key => $value) {
            if (isset($value['param_key']) && !empty($value['param_key']) && isset($value['param_value'])) {
                if ($value['param_key'] == 'user_job_number_rule') {
                    $value['param_value'] = strip_tags(htmlspecialchars_decode($value['param_value']));
                }
                $insertData[] = [
                    'param_key' => $value['param_key'],
                    'param_value' => $value['param_value'],
                ];
            }
        }
        if (empty($insertData)) {
            return true;
        }
        return DB::table('user_other_set')->insert($insertData);
    }

    /**
     * 生成用户工号
     *
     * @author 缪晨晨
     *
     * @param
     *
     * @since  2018-10-09 创建
     *
     * @return array
     */
    public function generateUserJobNumber($data, $editMode = false)
    {
        $userJobNumberRule = $this->getUserJobNumberRule();
        if (empty($userJobNumberRule)) {
            return isset($data['user_job_number']) ? ['user_job_number' => $data['user_job_number'], 'user_job_number_seq' => ''] : '';
        }
        preg_match_all("/(.*?)\[USER_SEQ(\d+)\|(\d+)\]/",html_entity_decode($userJobNumberRule),$userSeq);

        $userAreaName = '';
        $userCityName = '';
        $userWorkplaceName = '';
        $userJobCategoryName = '';
        if (isset($data['user_area'])) {
            $userAreaName = app($this->systemComboboxService)->getComboboxFieldsNameByIdentify($this->userSelectField['user_area'], $data['user_area']);
        }
        if (isset($data['user_city'])) {
            $userCityName = app($this->systemComboboxService)->getComboboxFieldsNameByIdentify($this->userSelectField['user_city'], $data['user_city']);
        }
        if (isset($data['user_workplace'])) {
            $userWorkplaceName = app($this->systemComboboxService)->getComboboxFieldsNameByIdentify($this->userSelectField['user_workplace'], $data['user_workplace']);
        }
        if (isset($data['user_job_category'])) {
            $userJobCategoryName = app($this->systemComboboxService)->getComboboxFieldsNameByIdentify($this->userSelectField['user_job_category'], $data['user_job_category']);
        }

        $userJobNumnerAuto = str_replace("[USER_AREA]", $userAreaName, $userJobNumberRule);
        $userJobNumnerAuto = str_replace("[USER_CITY]", $userCityName, $userJobNumnerAuto);
        $userJobNumnerAuto = str_replace("[USER_WORKPLACE]", $userWorkplaceName, $userJobNumnerAuto);
        $userJobNumnerAuto = str_replace("[USER_JOB_CATEGORY]", $userJobCategoryName, $userJobNumnerAuto);
        $userJobNumnerAuto = str_replace("[YEAR]", date("Y"), $userJobNumnerAuto);
        $userJobNumnerAuto = str_replace("[MONTH]", date("m"), $userJobNumnerAuto);
        $userJobNumnerAuto = str_replace("[DATE]", date("d"), $userJobNumnerAuto);

        $userSeqNumber = '';
        if (isset($userSeq[2][0]) && isset($userSeq[3][0])) {
            // 设置了流水号元素的情况
            $userSeqRuleStr = '[USER_SEQ'.$userSeq[2][0].'|'.$userSeq[3][0].']';
            if ($editMode && isset($data['user_id'])) {
                // 编辑模式，只更新非流水号的部分
                $userInfo = DB::table('user')->select(['user_job_number_seq'])->where('user_id', $data['user_id'])->first();
                $userSeqNumber = '';
                if (isset($userInfo->user_job_number_seq) && $userInfo->user_job_number_seq !== '') {
                    $userSeqNumber = $userInfo->user_job_number_seq;
                } else {
                    $checkHasUserJobNumberSeq = DB::table('user')->where('user_job_number_seq', '!=', '')->count();
                    $maxUserJobNumberSeq = DB::select("select max(CAST(user_job_number_seq AS SIGNED)) as max_job_number from user");
                    $maxUserJobNumberSeq = $maxUserJobNumberSeq[0]->max_job_number ?? 0;
                    $num = "%0".$userSeq[2][0]."d";
                    if (!$checkHasUserJobNumberSeq && !$maxUserJobNumberSeq) {
                        $userSeqNumber = sprintf($num, intval($userSeq[3][0]));
                    } else {
                        $temNum = intval($maxUserJobNumberSeq)+1;
                        if ($temNum >= intval($userSeq[3][0])) {
                            $userSeqNumber = sprintf($num, $temNum);
                        } else {
                            $userSeqNumber = sprintf($num, intval($userSeq[3][0]));
                        }
                        if (strlen($userSeqNumber) > strlen($userSeq[3][0])) {
                            return ['code' => ['0x005080','user']];
                        }
                    }
                }
            } else {
                // 新建模式
                $checkHasUserJobNumberSeq = DB::table('user')->where('user_job_number_seq', '!=', '')->count();
                $maxUserJobNumberSeq = DB::select("select max(CAST(user_job_number_seq AS SIGNED)) as max_job_number from user");
                $maxUserJobNumberSeq = $maxUserJobNumberSeq[0]->max_job_number ?? 0;
                $num = "%0".$userSeq[2][0]."d";
                if (!$checkHasUserJobNumberSeq && !$maxUserJobNumberSeq) {
                    $userSeqNumber = sprintf($num, intval($userSeq[3][0]));
                } else {
                    $temNum = intval($maxUserJobNumberSeq)+1;
                    if ($temNum >= intval($userSeq[3][0])) {
                        $userSeqNumber = sprintf($num, $temNum);
                    } else {
                        $userSeqNumber = sprintf($num, intval($userSeq[3][0]));
                    }
                    $userSeqNumber = sprintf($num, $temNum);
                    if (strlen($userSeqNumber) > strlen($userSeq[3][0])) {
                        return ['code' => ['0x005080','user']];
                    }
                }
            }
            $userJobNumnerAuto = str_replace($userSeqRuleStr, $userSeqNumber, $userJobNumnerAuto);
        }
        return ['user_job_number' => $userJobNumnerAuto, 'user_job_number_seq' => $userSeqNumber];
    }

    /**
     * 当前登录用户是否拥有同步人事档案的权限（人事档案新建或管理的权限）
     * array $menuIds
     * @return bool
     */
    private function isAutohrms(array $menuIds)
    {
        $isAutohrms = in_array(417, $menuIds) || in_array(418, $menuIds);

        return $isAutohrms;
    }

    /**
     * 获取下级
     * @param  array  $userIds 需要获取的用户id
     * @param  boolean $flag      穿透，默认穿透一级，true表示无限穿透
     * @return array
     */
    public static function getUserSubordinateIds($userIds, $throughType = false)
    {
        $result = $userIds;
        if (!Redis::hlen(self::USER_SUBORDINATE_REDIS_KEY)) {
            self::refreshReidsUserSubordinate();
        }
        $redisResult = Redis::hmget(self::USER_SUBORDINATE_REDIS_KEY, $userIds);
        if (empty($redisResult)) {
            return $result;
        }

        foreach ($redisResult as $key => $item) {
            if (!$item) {
                continue;
            }
            $tempUserIds = array_filter(explode(',', $item));
            if (!empty($tempUserIds)) {
                $result = array_unique(array_merge($result, $tempUserIds));
            }
        }
        if ($throughType) {
            $diffIds = array_diff(array_unique($result), $userIds);
            if (!empty($diffIds)) {
                return self::getUserSubordinateIds($result, $throughType);
            }
        }
        return array_unique($result);
    }

    /**
     * 刷新用户上下级缓存
     * @return bool
     */
    public static function refreshReidsUserSubordinate()
    {
        $userLists = DB::table('user')->select(['user_id'])->get();
        if ($userLists->isEmpty()) {
            return true;
        }
        // 获取所有的上下级关系
        $superiorLists = DB::table('user_superior')->select(['user_id', 'superior_user_id'])->get();
        if ($superiorLists->isEmpty()) {
            return true;
        }
        $redisHashValue = [];
        foreach ($userLists as $key => $item) {
            $redisHashValue[$item->user_id] = '';
        }
        foreach ($superiorLists as $key => $item) {
            if (!isset($redisHashValue[$item->superior_user_id])) {
                $redisHashValue[$item->superior_user_id] = '';
            }
            $redisHashValue[$item->superior_user_id] .= $item->user_id . ',';
        }
        Redis::hmset(self::USER_SUBORDINATE_REDIS_KEY, $redisHashValue);
        return true;
    }

    /**
     * 根据部门id集合获取所有的用户id集合
     * @return array
     */
    public static function getUserIdsByDeptIds(array $deptIds)
    {
        $result = [];
        $lists = DB::table('user_system_info')->select(['user_id'])->whereIn('dept_id', $deptIds)->get();
        if ($lists->isEmpty()) {
            return $result;
        }
        foreach ($lists as $key => $item) {
            $result[] = $item->user_id;
        }
        return array_unique($result);
    }

    /**
     * 根据角色id获取用户id集合
     * @param  array  $roleIds
     * @return [type] array
     */
    public static function getUserIdsByRoleIds(array $roleIds)
    {
        $result = [];
        $lists = DB::table('user_role')->select(['user_id'])->whereIn('role_id', $roleIds)->get();
        if ($lists->isEmpty()) {
            return $result;
        }
        foreach ($lists as $key => $item) {
            $result[] = $item->user_id;
        }
        return array_unique($result);
    }

    /**
     * 创建无效用户id的缓存
     */
    private static function createInvalidUserIdsCache()
    {
        $data = app((new self)->userSystemInfoRepository)->getInvalidUserId();

        Cache::forever(self::INVALID_USER_IDS_CACHE_KEY, json_encode($data));
    }

    /**
     * 获取无效的用户id（离职与删除）
     * @return array
     */
    public static function getInvalidUserIds()
    {
        if (!Cache::has(self::INVALID_USER_IDS_CACHE_KEY)) {
            self::createInvalidUserIdsCache();
        }

        $userIds = Cache::get(self::INVALID_USER_IDS_CACHE_KEY);

        $userIds = $userIds ? json_decode($userIds, true) :[];

        return $userIds;
    }

    /**
     * 获取用户群组信息
     * @param  [type] $param
     * @param  [type] $loginUserInfo
     * @return [type]  array
     */
    public function getChatGroupInfo($param, $loginUserInfo) {
        if (!$param) {
            return [];
        }
        $param = $this->parseParams($param);
        $result = [];
        if (is_array($param)) {
            foreach ($param as $key => $value) {
                $result[] = $this->getUserAllData($value, $loginUserInfo);
            }
        }
        return $result;
    }

    /***
     * 获取用户id部门角色信息
     *
     * @param $userId
     * @return array
     */
    public function getUserDeptAndRoleInfoByUserId($userId)
    {
        $userInfo = app($this->userRepository)->getUserDeptIdAndRoleIdByUserId($userId);
        $userInfo['user_id'] = $userId;
        if(isset($userInfo['role_id']) && is_string($userInfo['role_id'])){
            $userInfo['role_id'] = explode(',', $userInfo['role_id']);
        }
        return $userInfo;
    }
    /***
     * 返回所有用户的信息集合
     *
     * @param $userId
     * @return array
     */
    public function getAllUserFields($own) {
        $userFields = [];
        $users = app($this->userRepository)->getOrgUserList($own, [
            'fields' => ["user.user_id", "user_name", "user_position", "department.dept_name", "user_info.phone_number"]
        ]);

        if (!$users->isEmpty()) {
            foreach ($users as $value) {
                $userFields[] = [
                    'base_user_id' => $value->user_id,
                    'base_user_name' => $value->user_name ?? '',
                    'position' => !empty($value->user_position) ? app($this->systemComboboxService)->getComboboxFieldsNameByIdentify($this->userSelectField['user_position'], $value->user_position) : '',
                    'deptlist' => [$value->dept_name ?? ''],
                    'mobile' => $value->phone_number ?? '',
                    'mobile_prefix' => '+86'
                ];
            }
        }
        return $userFields;
    }
    /**
     * 获取用户关注的所有下级
     * @return array 返回用户信息数组
     */
    public function getAllUserCalendarSuperior($userId,$param = [])
    {
        $param = $this->parseParams($param);
        if (!isset($userId) || empty($userId)) {
            return [];
        }
        $missions = [];
        if (isset($param['calendarSet']) && !empty($param['calendarSet'])) {
            $missions = json_decode($param['calendarSet'], true);
        }
        $param['diarySet'] = [];
        if ($missions && isset($missions['dimission'])) {
            $param['diarySet']['dimission'] = $missions['dimission'];
        }
        $SubordinateUserId = $this->getAllSubordinateArrayByUserId($userId,$param);
        $list = [];
        if(count($SubordinateUserId) > 0){
            // 获取所有用户的信息
            $list = DB::table('user')
                // ->join('user_info','user.user_id','=','user_info.user_id')
                ->join('user_system_info','user.user_id','=','user_system_info.user_id')
                ->join('department','department.dept_id','=','user_system_info.dept_id')
                ->select('user.user_id','user.user_name','department.dept_name','user_system_info.user_status');
            if(isset($param['diarySet']['dimission']) && $param['diarySet']['dimission'] != 1){
                //不显示离职用户
                $list = $list->where("user_system_info.user_status", '!=', 2);
            }
            if (isset($param['search']['user_name']) && !empty($param['search']['user_name'])) {
                $list = $list->where("user.user_name" ,'like', "%". $param['search']['user_name'][0]. "%");
            }
            $list = $list->where("user_system_info.user_status", '>', 0)
                         ->whereIn("user.user_id",$SubordinateUserId)
                         ->get();
            $list = json_decode(json_encode($list),true);
        }
        if (empty($list)) {
            return [];
        }
        // 根据用户ID获取用户所有信息
        $userInfo = [];
        foreach ($list as $j => $s) {
            $userInfo['list'][] = $list[$j];
        }
        if (!isset($userInfo['list']) && empty($userInfo['list'])) {
            return [];
        }
        $userInfo['total'] = count($userInfo['list']);
        return $userInfo;
    }
    public function getUserSystemInfoNumber($own) {
        // 获取系统用户数量
        $where = [
            'user_system_info' => [
                'user_status' => [[0, 2], 'not_in']
            ]
        ];
        $sysUserNumber = app('App\EofficeApp\User\Repositories\UserRepository')->getUserNumber($where);

        $param = ['response' => 'count', 'request_from' => 1];
        $flowNumber = app('App\EofficeApp\Flow\Services\FlowService')->getFlowDefineListService($param, $own);
        return ['user_number' => $sysUserNumber, 'flow_number' => $flowNumber['total'] ?? 0];
    }
    public function getUserListsWithDept()
    {
        return app($this->userSystemInfoRepository)->getUserListsWithDept();
    }


    /**
     * 使用消息队列更新全站搜索数据
     *
     * @param   string|int    $id
     */
    public function updateGlobalSearchDataByQueue($ids)
    {
        try {
            ElasticsearchProducer::sendGlobalSearchUserMessage($ids);
        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
        }
    }
    /**
     * 通过账号获取用户信息----对应企业微信同步组织架构时需要用
     * @param $userAccounts
     * @return mixed
     * @author [dosy]
     */
    public function getUserToAccount($userAccounts){
        $userInfo = app($this->userRepository)->getUserAllDataByAccount(urldecode($userAccounts));
        if (isset($userInfo->user_id)){
            return $userInfo->toArray();
        }else{
            return '';
        }
    }

    public function addUserForWorkWechat($data, $loginUserId) {
        $table = ['user', 'user_info', 'user_menu', 'user_role', 'user_system_info', 'user_secext', 'user_superior'];
        $prefix = '_copy';
        foreach ($table as $key => $value) {
            if (Schema::hasTable($value) && !Schema::hasTable($value . $prefix)) {
                Schema::rename($value, $value . $prefix);
            }
            if (Schema::hasTable($value . $prefix) && !Schema::hasTable($value)){
                DB::update("create table $value like $value" . $prefix);
            }
        }
        try {
            foreach ($data as $userKey => $user){
                $return = $this->userSystemCreate($user, $loginUserId);
                if ($return['code']) {
                    // 同步失败删除表,恢复数据
                    foreach ($table as $key => $value) {
                        if (Schema::hasTable($value . $prefix)) {
                            Schema::dropIfExists($value);
                            Schema::rename($value . $prefix, $value);
                        }
                    }
                    return ['userAccount'=>$userKey,'code'=>$return];
                }
            }

        } catch (\Exception $e) {
            foreach ($table as $key => $value) {
                if (Schema::hasTable($value . $prefix)) {
                    Schema::dropIfExists($value);
                    Schema::rename($value . $prefix, $value);
                }
            }
            return $e->getMessage();
        }

    }

    // 同步成功之后调用删除备份数据
    public function addUserSuccessForWorkWechat() {
        // 同步成功删除备份表,恢复数据
        $table = ['user', 'user_info', 'user_menu', 'user_role', 'user_system_info', 'user_secext', 'user_superior'];
        $prefix = '_copy';
        foreach ($table as $value){
            Schema::dropIfExists($value . $prefix);
        }
        return true;
    }

    /**
     * 用户数据备份
     * @author [dosy]
     */
    public function userBackupForWorkWechat(){
        $table = ['user', 'user_info', 'user_menu', 'user_role', 'user_system_info', 'user_secext', 'user_superior'];
        $prefix = '_sync_backup';
        foreach ($table as $key => $value) {
            if (Schema::hasTable($value)) {
                $temTableName = $value . $prefix;
                Schema::dropIfExists($temTableName);
                DB::update("create table $temTableName like $value");
                DB::update("insert into $temTableName select * from $value");
            }
        }
    }

    /**
     * 用户数据还原
     * @author [dosy]
     */
    public function userSyncFailForWorkWechat(){
        $table = ['user', 'user_info', 'user_menu', 'user_role', 'user_system_info', 'user_secext', 'user_superior'];
        $prefix = '_sync_backup';
        foreach ($table as $key => $value) {
            if (Schema::hasTable($value . $prefix)) {
                Schema::dropIfExists($value);
                $temTableName = $value . $prefix;
                DB::update("create table $value like $temTableName");
                DB::update("insert into $value select * from $temTableName");
            }
        }
    }

    public function getUserStatusTotal($param)
    {
        // $result = app($this->userSystemInfoRepository)->getUserStatusTotal($param);
        $result = $this->userStatusList(['page' => 0]);
        if (isset($result['list']) && !empty($result['list'])) {
            foreach ($result['list'] as $key => $value) {
                $result['list'][$key]['user_count'] = count($value['user_status_has_many_system_info']);
                $result['list'][$key]['user_status'] = $value['status_id'];

            }
        }
        // 查询user_status = 0的数据
        $status = app($this->userSystemInfoRepository)->getUserStatusDelete(['user_status' => [0, '=']]);
        $statusArr = [
            'user_status' => 0,
            'user_count' => $status,
            'status_name' => trans('systemlog.other'),

        ];
        array_push($result['list'], $statusArr);
        return array_values($result['list']);
    }
    public function getRecentFourYear()
    {
        $years = array();
        $currentYear = date('Y');
        for ($i=3; $i>=1; $i--)
        {
            $years[$i] = $currentYear - $i;
        }
        array_push($years, intval($currentYear));
        return array_values($years);
    }

    public function getrecentUserStatusTotal($param)
    {
        $type = $param['type'] ?? 0;
        $year = $param['year'] ?? date('Y');
        $result = [];
        if ($type == 1) {
             // 按年统计
            $recentYear = $this->getRecentFourYear();
            foreach ($recentYear as $key => $value) {
                $time = $this->getYearStartEndTimes($value);
                $result[$value] = app($this->userSystemInfoRepository)->getUserStatusYearTotal($time, $type);

            }
        } else {
            $yearQuarter = $this->getQuarterDate($year);
            foreach ($yearQuarter as $key => $value) {
                $result[$key] = app($this->userSystemInfoRepository)->getUserStatusYearTotal($value, $type);
            }
        }
        return $result;
    }
    private function getYearStartEndTimes($year)
    {
        $startyear = date('Y') - 3;
        $smonth = 1;
        $emonth = 12;
        $startTime = $startyear.'-'.$smonth.'-1 00:00:00';
        $em = $year.'-'.$emonth.'-1 23:59:59';
        $endTime = date('Y-m-t H:i:s',strtotime($em));
        return array('stime'=>date("Y-m-d H:i:s", strtotime($startTime)),'etime'=>date("Y-m-d H:i:s", strtotime($endTime)));
    }

    private function getQuarterDate($year)
    {
        $times = array();
        // 获取当前季度
        // 判断是否为当前年
        if ($year == date('Y')) {
            $currentSeason = ceil((date('n'))/3);
        } else {
           $currentSeason = 4;
        }

        $times[1]['stime'] = date('Y-m-d H:i:s', mktime(0, 0, 0,4*3-3+1,1,($year-1)));
        $times[1]['etime'] = date('Y-m-d H:i:s', mktime(23,59,59,4*3,date('t',mktime(0, 0 , 0,4*3,1,($year-1))),$year-1));
        for($season = 1; $season <= $currentSeason; $season ++) {
            $times[$season+1]['stime'] = date('Y-m-d H:i:s', mktime(0, 0, 0,4*3-3+1,1,($year-1)));
            $times[$season+1]['etime'] = date('Y-m-d H:i:s', mktime(23,59,59,$season*3,date('t',mktime(0, 0 , 0,$season*3,1,$year)),$year));
        }
        return $times;
    }

    /**
     * 获取某个通信方式，当前用户允许通信的所有角色id
     * @param $own
     * @param string $communicateType 通信方式
     * @return array
     */
    public function getCommunicateUserIds($own, $communicateType = 'email')
    {
        $userId = $own['user_id'];
        $cacheKey = $userId . '_communicatetype';

        if (Cache::has($cacheKey)) {
            $communicateRoleFilter = Cache::get($cacheKey);
        } else {
            $param['communicate_type'] = $communicateType;
            $communicateRoleFilter = $this->communicateUserFilter($param, $own);
            Cache::add($cacheKey, $communicateRoleFilter, 1);
        }
        return $communicateRoleFilter;
    }
    // 获取已登录信息
    public function getUserSocket($param) {
        $data = app($this->userSocketRepository)->getOneFieldInfo([
            'user_id' => $param['user_id'],
            'socket_name' => $param['socket_name']
        ]);
        return empty($data) ? 0 : 1;
    }

    // 修改已登录信息
    public function putUserSocket($data) {
        $data = $this->parseParams($data);
        if ($this->getUserSocket($data) == 1) {
            return app($this->userSocketRepository)->updateData($data, [
                'user_id' => $data['user_id'],
                'socket_name' => $data['socket_name']
            ]);
        } else {
            return app($this->userSocketRepository)->insertData($data);
        }
    }

    public function multiRemoveDept($params) {
        $userIds = $params['user_id'] ?? '';
        $deptId = $params['dept_id'] ?? '';
        if (!$userIds) {
            return ['code' => ['0x005099', 'user']];
        }
        if (!$deptId) {
            return ['code' => ['0x005111', 'user']];
        }
        // 批量设置部门
        // 批量更改人事档案部门
        app($this->personnelFilesService)->multiRemoveDept($userIds, $deptId);
        return app($this->userSystemInfoRepository)->multiRemoveDept($userIds, $deptId);
    }

    public function multiSetRole($params) {
        $userIds = $params['user_id'] ?? '';
        $roleId = $params['role_id'] ?? '';
        // if (!$userIds) {
        //     return ['code' => ['0x005099', 'user']];
        // }
        if (!$roleId) {
            return ['code' => ['0x005100', 'user']];
        }
        // 查询原有的角色id
        $beforeUser = app($this->userRepository)->getUserIdByRole($roleId);
        $beforeUserIds = array_column($beforeUser, 'user_id');
        // 两个数组进行差集
        $diff = [];
        if ($beforeUserIds) {
            $diff = array_diff($beforeUserIds, $userIds);
        }
        if (!is_array($roleId)) {
            $roleId = explode(',', $roleId);
        }
        // 新增
        if ($userIds) {
            foreach ($userIds as $key => $value) {
                $roleIds = app($this->roleService)->getUserRole($value);
                $roleIdString = implode(',', array_unique(array_merge(array_column($roleIds, 'role_id'), $roleId)));
                $data = [
                    'role_id' => $roleIdString,
                    'user_id' => $value
                ];
                app($this->roleService)->addUserRole($data, true);
                Cache::forget('user_role_' . $value);
                $userMaxRoleNo = app($this->roleService)->getMaxRoleNoFromData($roleIdString);
                // 更新user_system_info的max_role_no字段
                app($this->userSystemInfoRepository)->updateData(['max_role_no' => $userMaxRoleNo], ['user_id' => [$userIds, 'in']]);
            }
        }
        // 删除
        if ($diff) {
            foreach ($diff as $k => $v) {
                $roleIds = app($this->roleService)->getUserRole($v);
                $roleIds = array_column($roleIds, 'role_id');
                // 先删除当前这个角色
                $key = array_search($roleId[0], $roleIds);
                if(isset($key)){
                    unset($roleIds[$key]);
                }
                $roleIdString = implode(',', array_unique($roleIds));
                if ($roleIdString) {
                    $data = [
                        'role_id' => $roleIdString,
                        'user_id' => $v
                    ];
                    $result = app($this->roleService)->addUserRole($data, true);
                    Cache::forget('user_role_' . $v);
                    $userMaxRoleNo = app($this->roleService)->getMaxRoleNoFromData($roleIdString);
                    // 更新user_system_info的max_role_no字段
                    app($this->userSystemInfoRepository)->updateData(['max_role_no' => $userMaxRoleNo], ['user_id' => [$diff, 'in']]);
                }

            }
        }
        return true;
    }
    private function parseUserStatus($status, $locale) {
        // 根据当前语言环境去查询相应的状态key, 并获取相应的id
        $langKey = $this->getLangKeyByLangValue($status, 'user_status');
        //获取status_id
        $statusId = app($this->userStatusRepository)->getStatusIdByStatusName($langKey);
        $status = isset($statusId[0]) ? $statusId[0]['status_id'] : '';
        return $status;
    }
    private function getLangKeyByLangValue($langValue, $table) {
        return app($this->langService)->getLangKey($langValue, $table);
    }
    private function parseAttendanceSchedule($scheduleName) {
        $where = ['scheduling_name' => [$scheduleName, '=']];
        $scheduleInfo = app($this->schedulingRepository)->getOneScheduling($where, ['scheduling_id']);

        return $scheduleInfo->scheduling_id ?? '';
    }

    // 解析区域, 城市, 职场, 岗位类型
    private function parseUserComboboxFields($langValue, $type) {
        // 根据当前语言环境去查询相应的状态key, 并获取相应的id
        $langKey = $this->getLangKeyByLangValue($langValue, 'system_combobox_field_' . $type);
        // 根据lang_key 获取value
        $fieldInfo = app($this->systemComboboxFieldRepository)->getFieldValueByFieldNameKey($langKey);
        $fieldValue = isset($fieldInfo[0]) ? $fieldInfo[0]['field_value'] : '';
        return $fieldValue;
    }

    private function  parseImportUserData($value, $param, $loginUserId) {
        $locale = Lang::getLocale();
        // 解析用户状态

        // 部门查询
        if (isset($value['dept_id']) && !empty($value['dept_id'])) {
            $value = $this->findOrCreateDept($value, $loginUserId);
        }
        // 角色根据名称获取id
        if (isset($value['role_id_init']) && !empty($value['role_id_init'])) {
            $value['role_id_init'] = $this->parseUserRoleId($value['role_id_init']);
        }
        $value['role_id_init'] = $value['role_id_init'] ?? '';

        if (isset($value['sex']) && $value['sex'] === '0') {
            $value['sex'] = 0;
        } else {
           $value['sex'] = 1;
        }
        // 解析用户状态
        if (isset($value['user_status'])) {
           $value['user_status'] = $this->parseUserStatus($value['user_status'], $locale);
        } else {
            $value['user_status'] = 1;
        }
        // 排版类型
        if (isset($value['attendance_scheduling'])) {
            $value['attendance_scheduling'] = $this->parseAttendanceSchedule($value['attendance_scheduling']);
        }
        if (isset($value['user_area'])) {
            $value['user_area'] = $this->parseUserComboboxFields($value['user_area'], 'user_area');
        }
        if (isset($value['user_city'])) {
            $value['user_city'] = $this->parseUserComboboxFields($value['user_city'], 'user_city');
        }
        if (isset($value['user_workplace'])) {
            $value['user_workplace'] = $this->parseUserComboboxFields($value['user_workplace'], 'user_workplace');
        }
        if (isset($value['user_job_category'])) {
            $value['user_job_category'] = $this->parseUserComboboxFields($value['user_job_category'], 'user_job_category');
        }
        if (isset($value['sex'])) {
            $sexArr = [
                '0' => trans('common.woman'),
                '1' => trans('common.man')
            ];
            $sex = array_search($value['sex'], $sexArr);

            $value['sex'] = $sex !== false ? $sex : 0;
        }
        if (isset($value['wap_allow'])) {
            $wapAllowArr = [
                '0' => trans('user.allow'),
                '1' => trans('common.not_allow')
            ];
            $wapAllow = array_search($value['wap_allow'], $wapAllowArr);
            $value['wap_allow'] = $wapAllow !== false ? $wapAllow : 0;
        }
        // 上下级关系处理
        if (isset($value['user_superior_id']) && !empty($value['user_superior_id'])) {
            $value['user_superior_id'] = $this->parseUserSuperior($value['user_superior_id']);
        }
        return $value;
    }

    public function getImportOrginazationFields($loginUserinfo = [])
    {
        $allExportData = [];

        $loginUserMenus = $loginUserinfo['menus']['menu'] ?? [];
        $permissionModule = app($this->empowerService)->getPermissionModules();
        if (empty($permissionModule)) {
            $checkAttendanceMenu = false;
        } else {
            $checkAttendanceMenu = in_array('32', $permissionModule);
        }
        // 如果没有考勤模块排班设置菜单，不导入考勤数据
        $schedulingTempData = [];
        if ($checkAttendanceMenu) {
            $schedulingData = app($this->schedulingRepository)->getSchedulingList(['page' => 0])->toArray();
            if ($schedulingData) {
                foreach ($schedulingData as $k => $v) {
                    $schedulingTempData[$k]['scheduling_id'] = $v['scheduling_id'];
                    $schedulingTempData[$k]['scheduling_name'] = $v['scheduling_name'];
                }
            }
        }
        $userStatusData = $this->userStatusList([]);
        $userStatusTempData = [];
        if ($userStatusData) {
            foreach ($userStatusData['list'] as $k => $v) {
                $userStatusTempData[$k]['status_id'] = $v['status_id'];
                $userStatusTempData[$k]['status_name'] = $v['status_name'];
            }
        }
        $userPositionData = app($this->systemComboboxService)->getComboboxFieldByIdentify('USER_POSITION');
        $userPositionTempData = [];
        if ($userPositionData) {
            foreach ($userPositionData as $k => $v) {
                $userPositionTempData[$k]['position_id'] = $k;
                $userPositionTempData[$k]['position_name'] = $v;
            }
        }
        $userAreaData = app($this->systemComboboxService)->getComboboxFieldByIdentify('USER_AREA');
        $userAreaTempData = [];
        if ($userAreaData) {
            foreach ($userAreaData as $k => $v) {
                $userAreaTempData[$k]['area_id'] = $k;
                $userAreaTempData[$k]['area_name'] = $v;
            }
        }
        $userCityData = app($this->systemComboboxService)->getComboboxFieldByIdentify('USER_CITY');
        $userCityTempData = [];
        if ($userCityData) {
            foreach ($userCityData as $k => $v) {
                $userCityTempData[$k]['city_id'] = $k;
                $userCityTempData[$k]['city_name'] = $v;
            }
        }
        $userWorkplaceData = app($this->systemComboboxService)->getComboboxFieldByIdentify('USER_WORKPLACE');
        $userWorkplaceTempData = [];
        if ($userWorkplaceData) {
            foreach ($userWorkplaceData as $k => $v) {
                $userWorkplaceTempData[$k]['workplace_id'] = $k;
                $userWorkplaceTempData[$k]['workplace_name'] = $v;
            }
        }
        $allExportData = [
            '0' => [
                'sheetName' => '组织结构导入模板', // 用户导入模板
                'header' => [
                    'parent_dept_id' => ['data' => trans('user.sup_dept_name'), 'style' => ['width' => '30', 'height' => '25', 'backgroundColor' => '1B9BCA', 'fontColor' => 'FFFFF', 'fontSize' => '14', 'fontWeight' => 'bold']],
                    'dept_id' => ['data' => trans('user.belong_dept_name'), 'style' => ['width' => '30', 'height' => '25', 'backgroundColor' => '1B9BCA', 'fontColor' => 'FFFFF', 'fontSize' => '14', 'fontWeight' => 'bold']],
                    'user_name' => ['data' => trans("user.user_name").trans("user.0x005047"), 'style' => ['width' => '30', 'height' => '25', 'backgroundColor' => '1B9BCA', 'fontColor' => 'FFFFF', 'fontSize' => '14', 'fontWeight' => 'bold']],// 真实姓名(必填)
                    'user_accounts' => ['data' => trans("user.user_accounts").trans('user.0x005085'), 'style' => ['width' => '30', 'height' => '25', 'backgroundColor' => '1B9BCA', 'fontColor' => 'FFFFF', 'fontSize' => '14', 'fontWeight' => 'bold']],// 用户名(必填)
                    'user_password' => ['data' => trans("user.0x005048"), 'style' => ['width' => '30', 'height' => '25', 'backgroundColor' => '1B9BCA', 'fontColor' => 'FFFFF', 'fontSize' => '14', 'fontWeight' => 'bold']],// 密码(为空时不更新密码)
                    'list_number' => ['data' => trans("user.list_number"), 'style' => ['width' => '30', 'height' => '25', 'backgroundColor' => '1B9BCA', 'fontColor' => 'FFFFF', 'fontSize' => '14', 'fontWeight' => 'bold']],// 序号
                    'user_job_number' => ['data' => trans("user.user_job_number"), 'style' => ['width' => '30', 'height' => '25', 'backgroundColor' => '1B9BCA', 'fontColor' => 'FFFFF', 'fontSize' => '14', 'fontWeight' => 'bold']],// 工号
                    'role_id_init' => ['data' => trans('user.role_name_import'), 'style' => ['width' => '30', 'height' => '25', 'backgroundColor' => '1B9BCA', 'fontColor' => 'FFFFF', 'fontSize' => '14', 'fontWeight' => 'bold']],// 角色ID(必填)
                    'user_position' => ['data' => trans('user.user_position_name'), 'style' => ['width' => '30', 'height' => '25', 'backgroundColor' => '1B9BCA', 'fontColor' => 'FFFFF', 'fontSize' => '14', 'fontWeight' => 'bold']],// 职位ID
                    'sex' => ['data' => trans('user.sex_name'), 'style' => ['width' => '30', 'height' => '25', 'backgroundColor' => '1B9BCA', 'fontColor' => 'FFFFF', 'fontSize' => '14', 'fontWeight' => 'bold']],// 性别ID(必填)
                    'birthday' => ['data' => trans("user.0x005032"), 'style' => ['width' => '30', 'height' => '25', 'backgroundColor' => '1B9BCA', 'fontColor' => 'FFFFF', 'fontSize' => '14', 'fontWeight' => 'bold']],// 生日
                    'dept_phone_number' => ['data' => trans("user.0x005033"), 'style' => ['width' => '30', 'height' => '25', 'backgroundColor' => '1B9BCA', 'fontColor' => 'FFFFF', 'fontSize' => '14', 'fontWeight' => 'bold']],// 公司电话
                    'faxes' => ['data' => trans("user.0x005034"), 'style' => ['width' => '30', 'height' => '25', 'backgroundColor' => '1B9BCA', 'fontColor' => 'FFFFF', 'fontSize' => '14', 'fontWeight' => 'bold']],// 公司传真
                    'home_address' => ['data' => trans("user.0x005035"), 'style' => ['width' => '30', 'height' => '25', 'backgroundColor' => '1B9BCA', 'fontColor' => 'FFFFF', 'fontSize' => '14', 'fontWeight' => 'bold']],// 家庭地址
                    'home_zip_code' => ['data' => trans("user.0x005036"), 'style' => ['width' => '30', 'height' => '25', 'backgroundColor' => '1B9BCA', 'fontColor' => 'FFFFF', 'fontSize' => '14', 'fontWeight' => 'bold']],// 家庭邮编
                    'home_phone_number' => ['data' => trans("user.0x005037"), 'style' => ['width' => '30', 'height' => '25', 'backgroundColor' => '1B9BCA', 'fontColor' => 'FFFFF', 'fontSize' => '14', 'fontWeight' => 'bold']],// 家庭电话
                    'phone_number' => ['data' => trans("user.0x005038"), 'style' => ['width' => '30', 'height' => '25', 'backgroundColor' => '1B9BCA', 'fontColor' => 'FFFFF', 'fontSize' => '14', 'fontWeight' => 'bold']],// 手机
                    'weixin' => ['data' => trans("user.0x005039"), 'style' => ['width' => '30', 'height' => '25', 'backgroundColor' => '1B9BCA', 'fontColor' => 'FFFFF', 'fontSize' => '14', 'fontWeight' => 'bold']],// 微信号
                    'oicq_no' => ['data' => trans("user.0x005040"), 'style' => ['width' => '30', 'height' => '25', 'backgroundColor' => '1B9BCA', 'fontColor' => 'FFFFF', 'fontSize' => '14', 'fontWeight' => 'bold']],// QQ号
                    'email' => ['data' => trans("user.0x005041"), 'style' => ['width' => '30', 'height' => '25', 'backgroundColor' => '1B9BCA', 'fontColor' => 'FFFFF', 'fontSize' => '14', 'fontWeight' => 'bold']],// 邮箱
                    'attendance_scheduling' => ['data' => trans('user.attendance_scheduling_name'), 'style' => ['width' => '30', 'height' => '25', 'backgroundColor' => '1B9BCA', 'fontColor' => 'FFFFF', 'fontSize' => '14', 'fontWeight' => 'bold']],// 考勤排班类型ID
                    'user_status' => ['data' => trans('user.user_status_name'), 'style' => ['width' => '30', 'height' => '25', 'backgroundColor' => '1B9BCA', 'fontColor' => 'FFFFF', 'fontSize' => '14', 'fontWeight' => 'bold']],// 用户状态ID(必填)
                    'user_area' => ['data' => trans('user.user_area_name'), 'style' => ['width' => '30', 'height' => '25', 'backgroundColor' => '1B9BCA', 'fontColor' => 'FFFFF', 'fontSize' => '14', 'fontWeight' => 'bold']],// 区域ID
                    'user_city' => ['data' => trans('user.user_city_name'), 'style' => ['width' => '30', 'height' => '25', 'backgroundColor' => '1B9BCA', 'fontColor' => 'FFFFF', 'fontSize' => '14', 'fontWeight' => 'bold']],// 城市ID
                    'user_workplace' => ['data' => trans('user.user_workplace_name'), 'style' => ['width' => '30', 'height' => '25', 'backgroundColor' => '1B9BCA', 'fontColor' => 'FFFFF', 'fontSize' => '14', 'fontWeight' => 'bold']],// 职场ID
                    'user_job_category' => ['data' => trans('user.user_job_category_name'), 'style' => ['width' => '30', 'height' => '25', 'backgroundColor' => '1B9BCA', 'fontColor' => 'FFFFF', 'fontSize' => '14', 'fontWeight' => 'bold']],// 岗位类别ID
                    'user_superior_id'  => ['data' => trans('user.all_user_superior_import'), 'style' => ['width' => '30', 'height' => '25', 'backgroundColor' => '1B9BCA', 'fontColor' => 'FFFFF', 'fontSize' => '14', 'fontWeight' => 'bold']],
                    'wap_allow' => ['data' => trans('user.wap_allow_name'), 'style' => ['width' => '30', 'height' => '25', 'backgroundColor' => '1B9BCA', 'fontColor' => 'FFFFF', 'fontSize' => '14', 'fontWeight' => 'bold']],// 手机访问ID,
                ]
            ]
        ];

        if (!$checkAttendanceMenu) {
            unset($allExportData['0']['header']['attendance_scheduling']);
            unset($allExportData['3']);
        }
        return $allExportData;
    }

    public function importOrginazation($data, $param)
    {
        $locale = Lang::getLocale();
        $loginUserId = isset($param['user_info']['user_id']) ? $param['user_info']['user_id'] : '';
        $loginUserInfo = $param['user_info'] ?? [];
        $info = [
            'total' => count($data),
            'success' => 0,
            'error' => 0,
        ];
        if ($param['type'] == 3) {
            //新增数据并清除原有数据 先清除原有数据 除了admin 软删除
            $deletedData = ['deleted_at' => date('Y-m-d H:i:s')];
            DB::table('user')->where('user_id', '!=', 'admin')->update($deletedData);
            DB::table('user_info')->where('user_id', '!=', 'admin')->update($deletedData);
            DB::table('user_system_info')->where('user_id', '!=', 'admin')->update($deletedData);
            DB::table('user_role')->where('user_id', '!=', 'admin')->update($deletedData);
            DB::table('user_menu')->where('user_id', '!=', 'admin')->update($deletedData);
            DB::table('user_superior')->update($deletedData);
            //补充 企业微信同步组织架构
            try{
                app($this->workWechatService)->autoSyncToWorkWeChatDeleteAllUser();
            }catch (\Exception $e){
                Log::error($e->getMessage());
            }

        }
        $updateSuprior = [];
        foreach ($data as $key => $value) {
            $count = 0;
            foreach ($value as $k => $v) {
                $v = trim($v);
                $value[$k] = $v;
                if (!empty($v)) {
                    $count++;
                }
            }
            if ($count == 0) {
                continue;
            }
            $rulesTipStr = '';
            // 检查必填
            $requiredTip = $this->checkImportUserDataRequired($value);
            if (!empty($requiredTip)) {
                foreach ($requiredTip as $rulesTip) {
                    $rulesTipStr .= trans("user." . $rulesTip) . ';';
                }
            }
            // 解析角色
            // 解析需要变成id的字段
            if (isset($value['user_superior_id']) && $value['user_superior_id'] == 'all') {
                $value['is_all'] = 1;
            }
            $value = $this->parseImportOrganizationRoleData($value, $loginUserId);
            $value['data_from']   = 'import';
            if (isset($value['user_accounts']) && empty($value['user_accounts'])) {
                $value['user_accounts'] = $value['user_name'];
            }
            if ($param['type'] == 1) {
                //仅新增数据
                 if (!empty($requiredTip)) {
                    $info['error']++;
                    $data[$key]['importResult'] = importDataFail();
                    $data[$key]['importReason'] = importDataFail($rulesTipStr);
                    continue;
                } else {
                    $value['post_priv'] = '0';
                    $userCreateResult = $this->userSystemCreate($value, $loginUserInfo);
                    if (isset($userCreateResult['code'])) {
                        $info['error']++;
                        $rulesTipStr .= trans("user." . $userCreateResult['code'][0]);
                        $data[$key]['importResult'] = importDataFail();
                        $data[$key]['importReason'] = importDataFail($rulesTipStr);
                        continue;
                    } else {
                        //补充 企业微信同步组织架构
                        try {
                            if (isset($userCreateResult['user_id'])){
                                $toWorkWeChatData = [
                                    'type' => 'add',
                                    'user_id' => $userCreateResult['user_id']
                                ];
                                app($this->workWechatService)->importUserToWorkWeChatUser($toWorkWeChatData);
                            }
                        } catch (\Exception $e) {
                            Log::error($e->getMessage());
                        }
                        $info['success']++;
                        $data[$key]['importResult'] = importDataSuccess();
                        $updateSuprior[] = $value;
                    }
                }
            } elseif ($param['type'] == 2) {
                //仅更新数据
                if ($param["primaryKey"] != 'user_accounts') {
                    exit;
                }
                $where = ['user_accounts' => [$value['user_accounts']]];
                $result = app($this->userRepository)->judgeUserExists($where);
                if (!$result) {
                    $info['error']++;
                    $data[$key]['importResult'] = importDataFail();
                    $data[$key]['importReason'] = importDataFail(trans("user.0x005016"));
                    continue;
                } else {
                    if (!empty($requiredTip)) {
                        $info['error']++;
                        $data[$key]['importResult'] = importDataFail();
                        $data[$key]['importReason'] = importDataFail($rulesTipStr);
                        continue;
                    } else {
                        $value['user_id'] = $result;
                        $value['data_from'] = 'import';
                        $userEditResult = $this->userSystemEdit($value, $loginUserInfo);
                        if (isset($userEditResult['code'])) {
                            $info['error']++;
                            $rulesTipStr .= trans("user." . $userEditResult['code'][0]);
                            $data[$key]['importResult'] = importDataFail();
                            $data[$key]['importReason'] = importDataFail($rulesTipStr);
                            continue;
                        } else {
                            //补充 企业微信同步组织架构
                            try {
                                if (isset($value['user_id'])){
                                    $toWorkWeChatData = [
                                        'type' => 'update',
                                        'user_id' => $value['user_id']
                                    ];
                                    app($this->workWechatService)->importUserToWorkWeChatUser($toWorkWeChatData);
                                }
                            } catch (\Exception $e) {
                                Log::error($e->getMessage());
                            }
                            $info['success']++;
                            $data[$key]['importResult'] = importDataSuccess();
                            $updateSuprior[] = $value;
                        }
                    }
                }
            } elseif ($param['type'] == 3) {
                $where = ['user_accounts' => [$value['user_accounts']]];
                //新增数据并清除原有数据
                if (!empty($requiredTip)) {
                    $info['error']++;
                    $data[$key]['importResult'] = importDataFail();
                    $data[$key]['importReason'] = importDataFail($rulesTipStr);
                    continue;
                } else {
                    $value['post_priv'] = '0';
                    $userCreateResult = $this->userSystemCreate($value, $loginUserInfo);
                    if (isset($userCreateResult['code'])) {
                        $info['error']++;
                        $rulesTipStr .= trans("user." . $userCreateResult['code'][0]);
                        $data[$key]['importResult'] = importDataFail();
                        $data[$key]['importReason'] = importDataFail($rulesTipStr);
                        continue;
                    } else {
                        //补充 企业微信同步组织架构
                        try {
                            if (isset($userCreateResult['user_id'])){
                                $toWorkWeChatData = [
                                    'type' => 'add',
                                    'user_id' => $userCreateResult['user_id']
                                ];
                                app($this->workWechatService)->importUserToWorkWeChatUser($toWorkWeChatData);
                            }

                        } catch (\Exception $e) {
                            Log::error($e->getMessage());
                        }
                        $info['success']++;
                        $data[$key]['importResult'] = importDataSuccess();
                        $updateSuprior[] = $value;
                    }
                }
            } elseif ($param['type'] == 4) {
                //新增数据并更新已有数据
                if ($param["primaryKey"] != 'user_accounts') {
                    exit;
                }
                $where = ['user_accounts' => [$value['user_accounts']]];
                $result = app($this->userRepository)->judgeUserExists($where);
                if (!$result) {
                    if (!empty($rulesTipStr)) {
                        $info['error']++;
                        $data[$key]['importResult'] = importDataFail();
                        $data[$key]['importReason'] = importDataFail($rulesTipStr);
                        continue;
                    } else {
                        $value['post_priv'] = '0';
                        $userCreateResult = $this->userSystemCreate($value, $loginUserInfo);
                        if (isset($userCreateResult['code'])) {
                            $info['error']++;
                            $rulesTipStr .= trans("user." . $userCreateResult['code'][0]);
                            $data[$key]['importResult'] = importDataFail();
                            $data[$key]['importReason'] = importDataFail($rulesTipStr);
                            continue;
                        } else {
                            //补充 企业微信同步组织架构
                            try {
                                if (isset($userCreateResult['user_id'])){
                                    $toWorkWeChatData = [
                                        'type' => 'add',
                                        'user_id' => $userCreateResult['user_id']
                                    ];
                                    app($this->workWechatService)->importUserToWorkWeChatUser($toWorkWeChatData);
                                }
                            } catch (\Exception $e) {
                                Log::error($e->getMessage());
                            }
                            $info['success']++;
                            $data[$key]['importResult'] = importDataSuccess();
                            $updateSuprior[] = $value;
                        }
                    }
                } else {
                    if (!empty($rulesTipStr)) {
                        $info['error']++;
                        $data[$key]['importResult'] = importDataFail();
                        $data[$key]['importReason'] = importDataFail($rulesTipStr);
                        continue;
                    } else {
                        $value['user_id'] = $result;
                        $userEditResult = $this->userSystemEdit($value, $loginUserInfo);
                        if (isset($userEditResult['code'])) {
                            $info['error']++;
                            $rulesTipStr .= trans("user." . $userEditResult['code'][0]);
                            $data[$key]['importResult'] = importDataFail();
                            $data[$key]['importReason'] = importDataFail($rulesTipStr);
                            continue;
                        } else {
                            //补充 企业微信同步组织架构
                            try {
                                if (isset($value['user_id'])){
                                    $toWorkWeChatData = [
                                        'type' => 'update',
                                        'user_id' => $value['user_id']
                                    ];
                                    app($this->workWechatService)->importUserToWorkWeChatUser($toWorkWeChatData);
                                }
                            } catch (\Exception $e) {
                                Log::error($e->getMessage());
                            }
                            $info['success']++;
                            $data[$key]['importResult'] = importDataSuccess();
                            $updateSuprior[] = $value;
                        }
                    }
                }
            }
        }
        if ($updateSuprior) {
           // 更新上级
            foreach ($updateSuprior as $k => $v) {
                $where = ['user_accounts' => [$v['user_accounts']]];
                $result = app($this->userRepository)->judgeUserExists($where);
                if ($result) {
                    $v['user_id'] = $result;
                    if (isset($v['user_superior_id']) && $v['user_superior_id'] == 'all') {
                        $v['is_all'] = 1;
                    }
                    $v = $this->toParseUserSuperior($v);
                    // 更新上级
                    if (isset($v['user_superior_id']) && !empty($v['user_superior_id'])) {
                        $v['edit'] = 1;
                        app($this->roleService)->addUserSuperior($v);
                    }
                }

            }
        }
        // 清空表单分类信息redis缓存
        if(!empty(Redis::keys('flow_form_type_power_list_*'))) {
            Redis::del(Redis::keys('flow_form_type_power_list_*'));
        }
        // 清空表单分类信息redis缓存
        if(!empty(Redis::keys('flow_sort_power_list_*'))) {
            Redis::del(Redis::keys('flow_sort_power_list_*'));
        }
        return compact('data', 'info');
    }
    /**
     * /解析组织结构导入
     * @param  [type] $data
     * @param  [type] $loginUserId
     * @return [type]
     */
    private function parseImportOrganizationRoleData($data, $loginUserId) {
        // 传入数据的, 角色部门
        if (!$data) {
            return '';
        }

        if ((isset($data['dept_id']) && isset($data['parent_dept_id'])) && ($data['dept_id'] == $data['parent_dept_id'])) {
            return $data;
        }
        $locale = Lang::getLocale();
        $endRoleId = [];
        if (isset($data['role_id_init']) && !empty($data['role_id_init'])) {
            // 查询角色权限级别最大的
            $roleNo = DB::table('role')->max('role_no');
            $roleNameArr = explode(',', $data['role_id_init']);
            foreach ($roleNameArr as $key => $value) {
                // 查询判断是否存在
                $roleId = $this->parseUserRoleId($value);
                if (!$roleId) {
                    // 创建角色
                    $createData = [
                        'role_name' => $value,
                        'role_no'   => $roleNo,
                        'inherit_role' => ''
                    ];
                    $roleNewId = app($this->roleService)->createRole($createData);
                    if (!is_array($roleNewId)) {
                        $endRoleId[] = $roleNewId;
                    }
                } else {
                    $endRoleId[] = $roleId;
                }
            }
            $data['role_id_init'] = implode(',', array_filter($endRoleId));
        }
        // 解析用户状态
        if (isset($data['user_status']) && !empty($data['user_status'])) {
           $data['user_status'] = $this->findOrCreateUserStatus($data['user_status'], $locale);
        } else {
            $data['user_status'] = 1;
        }
        if (isset($data['user_position'])) {
            $data['user_position'] = $this->findOrCreateField($data['user_position'], 'USER_POSITION', $locale);
        }
        if (isset($data['user_area'])) {
            $data['user_area'] = $this->findOrCreateField($data['user_area'], 'USER_AREA', $locale);
        }
        if (isset($data['user_city'])) {
            $data['user_city'] = $this->findOrCreateField($data['user_city'], 'USER_CITY', $locale);
        }
        if (isset($data['user_workplace'])) {
            $data['user_workplace'] = $this->findOrCreateField($data['user_workplace'], 'USER_WORKPLACE', $locale);
        }
        if (isset($data['user_job_category'])) {
            $data['user_job_category'] = $this->findOrCreateField($data['user_job_category'], 'USER_JOB_CATEGORY', $locale);
        }
        if (isset($data['sex'])) {
            $sexArr = [
                '0' => trans('common.woman'),
                '1' => trans('common.man')
            ];
            $sex = array_search($data['sex'], $sexArr);
            $data['sex'] = $sex !== false ? $sex : 1;

        }
        if (isset($data['wap_allow'])) {
            $wapAllowArr = [
                '0' => trans('user.not_allow'),
                '1' => trans('user.allow')
            ];
            $wapAllow = array_search($data['wap_allow'], $wapAllowArr);
            $data['wap_allow'] = $wapAllow !== false ? $wapAllow : 0;
        }
        // 上下级关系处理
        if (isset($data['user_superior_id']) && !empty($data['user_superior_id'])) {
            $data['user_superior_id'] = $this->parseUserSuperior($data['user_superior_id']);
        }
        // 考勤排版类型
        if (isset($data['attendance_scheduling']) && !empty($data['attendance_scheduling'])) {
            // 查询id
            $scheduleInfo = app($this->schedulingRepository)->getOneScheduling(['scheduling_name' => [$data['attendance_scheduling']]]);
            $data['attendance_scheduling'] = $scheduleInfo->scheduling_id ?? 0;
        }
        if (isset($data['dept_id']) && !empty($data['dept_id'])) {
            $data = $this->findOrCreateDept($data, $loginUserId);
        }
        return $data;
    }

    private function findOrCreateUserStatus($statusName, $locale)
    {
        $statusId = $this->parseUserStatus($statusName, 'zh-CN');
        if (!$statusId) {
            // 创建用户状态
            $statusData = [
                'status_name' => $statusName,
                'status_name_lang' => [
                    $locale => $statusName
                ]
            ];
            $return = $this->userStatusCreate($statusData);
            $statusId = $return->status_id ?? 1;
        }
        return $statusId;
    }

    private function findOrCreateField($value, $fieldIndetify, $locale)
    {
        if (empty($value)) {
            return '';
        }
        $fieldValue = $this->parseUserComboboxFields($value, strtolower($fieldIndetify));
        if (!$fieldValue) {
            // 创建区域下拉框字段
            $comboboxId = app($this->systemComboboxRepository)->getComboboxIdByIdentify($fieldIndetify);
            $fieldOrder = DB::table('system_combobox_field')->where('combobox_id', $comboboxId)->max('field_order');
            // 创建下拉框字段
            $comboboxData[] = [
                'combobox_id'  => $comboboxId,
                'field_name'   => $value,
                'field_order'  => !$fieldOrder && $fieldOrder != 0 ? $fieldOrder : 0,
                'handle'       => 'insert',
                'combobox_name_lang' => [
                    $locale => $value
                ]
            ];
            // 创建字段
            $return = app($this->systemComboboxService)->createField($comboboxData);
            if (isset($return['code'])) {
                $result = '';
            } else {
                $result = $this->parseUserComboboxFields($value, strtolower($fieldIndetify));
            }

        } else {
            $result = $fieldValue;
        }
        return $result;
    }

    private function findOrCreateDept($data, $loginUserId)
    {
        // 先查找字段
        if (isset($data['parent_dept_id']) && empty($data['parent_dept_id'])) {
            // 直接根据部门id进行查找
            if (isset($data['dept_id']) && !empty($data['dept_id'])) {
                // 通过id 查找部门名称
                $data['dept_id'] = $this->getDepartByNameParent($data['dept_id'], 0, $loginUserId);
            }
        } else if (isset($data['parent_dept_id']) && !empty($data['parent_dept_id'])) {
            // 先查询父级的id
            // 通过id 查找部门名称
            // 先查找父级部门
            $parentId = $this->toParseAddOrSelectDept($data['parent_dept_id'], '', $loginUserId);
            // 所属部门
            if (isset($data['dept_id']) && !empty($data['dept_id'])) {
                // 通过id 查找部门名称
                $data['dept_id'] = $this->toParseAddOrSelectDeptByParentId($data['dept_id'], $parentId, $loginUserId);
            }
        }
        return $data;
    }
    private function toParseAddOrSelectDept($deptId, $parentId, $loginUserId)
    {
        // 通过id 查找部门名称
        $deptArrId = app($this->departmentRepository)->getDeptIdByName($deptId);
        if (!$deptArrId) {
            $deptSort = DB::Table('department')->max('dept_sort');
            // 进行创建
            $departData = [
                'dept_sort' => $deptSort,
                'dept_name' => $deptId,
                'tel_no'    => '',
                'fax_no'    => '',
                'director'  => [],
                'parent_id' => $parentId
            ];
            $return = app($this->departmentService)->addDepartment($departData, $loginUserId);
            if (isset($return ['code'])) {
                $data['dept_id'] = '';
            }
            $untilDeptId = $return['dept_id'];
        } else {
            $untilDeptId = $deptArrId['dept_id'];
            // 更新父级部门
            // 进行创建
            $departData = [
                'dept_sort' => 0,
                'dept_name' => $deptId,
                'tel_no'    => '',
                'fax_no'    => '',
                'director'  => [],
                'parent_id' => $untilDeptId
            ];
            app($this->departmentService)->updateDepartment($departData, $untilDeptId, $loginUserId);
        }
        return $untilDeptId;
    }
    private function getDepartByNameParent($deptId, $parentId, $loginUserId)
    {
        // 通过id 查找部门名称
        $deptArrId = app($this->departmentRepository)->getDeptIdByNameParent($deptId, $parentId);
        if (!$deptArrId) {
            $deptSort = DB::Table('department')->max('dept_sort');
            // 进行创建
            $departData = [
                'dept_sort' => $deptSort,
                'dept_name' => $deptId,
                'tel_no'    => '',
                'fax_no'    => '',
                'director'  => [],
                'parent_id' => $parentId
            ];
            $return = app($this->departmentService)->addDepartment($departData, $loginUserId);
            if (isset($return ['code'])) {
                $data['dept_id'] = '';
            }
            $untilDeptId = $return['dept_id'];
        } else {
            $untilDeptId = $deptArrId['dept_id'];
            // 更新父级部门
            // 进行创建
            $departData = [
                'dept_sort' => 0,
                'dept_name' => $deptId,
                'tel_no'    => '',
                'fax_no'    => '',
                'director'  => [],
                'parent_id' => $untilDeptId
            ];
            app($this->departmentService)->updateDepartment($departData, $untilDeptId, $loginUserId);
        }
        return $untilDeptId;
    }
    private function toParseAddOrSelectDeptByParentId($deptId, $parentId, $loginUserId)
    {
        // 通过id 查找部门名称
        $deptArrId = app($this->departmentRepository)->getDeptIdByParentIdAndDeptName($deptId, $parentId);
        if (!$deptArrId) {
            $deptSort = DB::Table('department')->max('dept_sort');
            // 进行创建
            $departData = [
                'dept_sort' => $deptSort,
                'dept_name' => $deptId,
                'tel_no'    => '',
                'fax_no'    => '',
                'director'  => [],
                'parent_id' => $parentId
            ];
            $return = app($this->departmentService)->addDepartment($departData, $loginUserId);
            if (isset($return ['code'])) {
                $data['dept_id'] = '';
            }
            $untilDeptId = $return['dept_id'];
        } else {
            $untilDeptId = $deptArrId['dept_id'];
            // 更新父级部门
            // 进行创建
            $departData = [
                'dept_sort' => 0,
                'dept_name' => $deptId,
                'tel_no'    => '',
                'fax_no'    => '',
                'director'  => [],
                'parent_id' => $untilDeptId
            ];
            app($this->departmentService)->updateDepartment($departData, $untilDeptId, $loginUserId);
        }
        return $untilDeptId;
    }

    private function parseUserSuperior($superiorName) {
        $allUserIdString = '';
        if ($superiorName == 'all') {
            $allUserIdString = $this->getAllUserIdString();
        } else {
            $superiorNameArr = array_filter(explode(',', $superiorName));
            // 通过user_name获取用户id
            $allUserData = app($this->userRepository)->getUserIds($superiorNameArr);
            $allUserIdString = implode(',', array_filter(array_column($allUserData, 'user_id')));
        }

        return $allUserIdString;
    }
    private function getParentDeparentNameBySubDeptId($deptId)
    {
        if (!$deptId) {
            return '';
        }
        // 通过子部门id获取府部门的名称
        $deptInfo = app($this->departmentService)->getDeptDetail($deptId);
        if (!$deptInfo) {
            return '';
        }
        $parentDeptId = $deptInfo->parent_id ?? '';
        $parentDeptinfo = app($this->departmentService)->getDeptDetail($parentDeptId);

        return $parentDeptinfo->dept_name ?? '';
    }
    private function parsePassword($password) {
        // 判断是否开启密码强度限制
        if (get_system_param('login_password_security_switch')) {
            // 判断长度
            $length = get_system_param('password_length');
            if (strlen($password) < $length) {
                return ['code' => ['0x005095','user'], 'dynamic' => trans('user.0x005095').$length. trans('user.0x005096')];
            }

            // 包含字符串字母符号
            if (!preg_match("/(?:(?=.*[0-9].*)(?=.*[A-Za-z].*)(?=.*[\\W].*))[\\W0-9A-Za-z]{8,16}/", $password, $match)) {
                return ['code' => ['0x005094','user'], 'dynamic' => trans('user.0x005094')];
            }
        }
        // 判断密码是否包含中文
        if (preg_match("/([\x81-\xfe][\x40-\xfe])/", $password, $match)) {
            return ['code' => ['0x005098','user'], 'dynamic' => trans('user.0x005098')];
        }
        return true;
    }

    /**
     * @param $phoneNumbers 手机号码数组
     * @param $fields 字段数组
     * @return array
     */
    public function getUserByPhoneNumber($phoneNumbers, $fields = [], $includeLeave = true)
    {
        return app($this->userRepository)->getUserByPhoneNumber($phoneNumbers, $fields, $includeLeave);
    }
    public function multipleSyncPersonnelFiles($userId)
    {
        if (!$userId) {
            return ['code' => ['select_user', 'user']];
        }
        $params = [
            'user_id' => array_column($userId, 'user_id'),
            'own'     => own()
        ];
        Queue::push(new SyncPersonnelFilesJob($params));
        // 异步调用同步人事档案
        return true;
    }

    public function SyncPersonnelFiles($param)
    {
        $own = $param['own'] ?? [];
        foreach ($param['user_id'] as $key => $value) {
            // 获取用户信息
            $allUserData = $this->getUserAllData($value);
            if (empty($allUserData)) {
                continue;
            }
            if (!is_array($allUserData)) {
                $allUserData = $allUserData->toArray();
            }
            $personnelFileData['user_id'] = $value;
            $personnelFileData['user_name'] = $allUserData['user_name'] ?? '';
            $personnelFileData['sex'] = $allUserData['user_has_one_info']['sex'] ?? 1;
            $personnelFileData['status'] = $allUserData['user_has_one_system_info']['user_status'] ?? 1;
            $personnelFileData['dept_id'] = $allUserData['user_has_one_system_info']['dept_id'] ?? '';
            // $personnelFileData['birthday'] = $allUserData['user_has_one_info']['birthday'] ?? '';
            // $personnelFileData['home_addr'] = $allUserData['user_has_one_info']['home_address'] ?? '';
            // $personnelFileData['home_tel'] = $allUserData['user_has_one_info']['home_phone_number'] ?? '';
            $personnelFileData['no'] = isset($allUserData['user_job_number']) && !empty($allUserData['user_job_number']) ? $allUserData['user_job_number'] : $allUserData['user_accounts'];
            if (isset($data['notes'])) {
                $personnelFileData['resume'] = $allUserData['user_has_one_info']['notes'];
            }

            if (isset($allUserData['user_has_one_info']['birthday']) && !empty($allUserData['user_has_one_info']['birthday'])) {
                $personnelFileData['birthday'] = $allUserData['user_has_one_info']['birthday'];
            }
            if (isset($allUserData['user_has_one_info']['home_address']) && !empty($allUserData['user_has_one_info']['home_address'])) {
                $personnelFileData['home_addr'] = $allUserData['user_has_one_info']['home_address'];
            }
            if (isset($allUserData['user_has_one_info']['home_phone_number']) && !empty($allUserData['user_has_one_info']['home_phone_number'])) {
                $personnelFileData['home_tel'] = $allUserData['user_has_one_info']['home_phone_number'];
            }
            if (isset($allUserData['user_has_one_info']['email']) && !empty($allUserData['user_has_one_info']['email'])) {
                $personnelFileData['email'] = $allUserData['user_has_one_info']['email'];
            }
            // 检查是否有人事档案
            $checkPersonnelFile = app($this->personnelFilesRepository)->getPersonnelFilesByWhere(['user_id' => [$value]]);
            try {
                if (empty($checkPersonnelFile)) {
                    app($this->personnelFilesService)->createPersonnelFile($personnelFileData, $own);
                } else {
                    app($this->personnelFilesService)->modifyPersonnelFileByUserId($personnelFileData, $own);
                }
            }  catch (\Exception $e){
                Log::error($e->getMessage());
            }
        }
        $messagrData = [
            'remindMark' => 'user-personnel'
        ];
        // 发消息提醒
        $this->sendMessage($messagrData, $own['user_id']);
        return true;
    }
    private function sendMessage($data, $userId) {
        $sendData = [
            'remindMark'    => $data['remindMark'] ?? '',
            'toUser'        => $userId,
            'stateParams'   => []
        ];
        Eoffice::sendMessage($sendData);
    }

    private function judgeUserManageScope($loginUserInfo, $param)
    {
        $param['fixedSearch']['dept_id'][0] = $this->getUserCanManageDepartmentId($loginUserInfo);
        $maxRoleNo = isset($loginUserInfo['max_role_no']) ? $loginUserInfo['max_role_no'] : 0;
        $param['search']['max_role_no'][0] = $maxRoleNo;
        $param['dept_id'] = $param['fixedSearch']['dept_id'][0];
        if ($param['fixedSearch']['dept_id'][0] == 'all') {
            $allDept = array_column(app($this->departmentService)->getAllDeptId(), 'dept_id');
            $param['dept_id'] = $allDept;
        }
        $param['include_leave'] = true;
        return app($this->userSystemInfoRepository)->judgeUserManageScope($param);
    }
    public function generateUserCache()
    {
        if (Redis::exists(self::USER_ACCOUNT_CACHE_KEY)) {
            Redis::del(self::USER_ACCOUNT_CACHE_KEY);
        }
        $allUserData = app($this->userRepository)->getUserAccountCache()->mapWithKeys(function($item){
             return [$item->user_accounts => ['user_id' => $item->user_id, 'password' => $item->password]];
        });
        if ($allUserData) {
            $allUserData = $allUserData->toArray();
        }
        $res = Redis::pipeline(function($pipe) use($allUserData) {
            foreach ($allUserData as $key => $value) {
                $pipe->hset(self::USER_ACCOUNT_CACHE_KEY, $key, json_encode($value));
            }
        });
        return true;
    }
    public function createOrUpdateUserCache($user, $type)
    {
        if (empty($user)) {
            return ['code' => ['0x005131', 'user']];
        }
        $key = $user['user_accounts'] ?? '';

        if (Redis::exists(self::USER_ACCOUNT_CACHE_KEY)) {
            // 新建用户add
            if ($type == 'new') {
                // 哈希表重建
                $value = ['user_id' => $user['user_id'], 'password' => $user['password'] ?? ''];
                Redis::hset(self::USER_ACCOUNT_CACHE_KEY, $key, json_encode($value));
            } else if ($type == 'update') {
                $key = $user['old_accounts'];
                if ($userInfo = Redis::hget(self::USER_ACCOUNT_CACHE_KEY, $key)) {
                    $userInfo = json_decode($userInfo, true);
                    $userData = app($this->userRepository)->getUserDataByAccount($user['user_accounts']);
                    $pass = $userData->password ?? '';
                    $password = isset($user['password']) ? $user['password'] : $pass;
                    $value = ['user_id' => $user['user_id'], 'password' => $password];
                    Redis::hdel(self::USER_ACCOUNT_CACHE_KEY, $key);
                    Redis::hset(self::USER_ACCOUNT_CACHE_KEY, $user['user_accounts'], json_encode($value));
                } else {
                    // 查找用户密码
                    $userData = app($this->userRepository)->getUserDataByAccount($user['user_accounts']);
                    $pass = $userData->password ?? '';
                    $password = isset($user['password']) ? $user['password'] : $pass;
                    $value = ['user_id' => $user['user_id'], 'password' => $password];
                    Redis::hset(self::USER_ACCOUNT_CACHE_KEY, $user['user_accounts'], json_encode($value));
                }

            } else if ($type == 'delete') {
                Redis::hdel(self::USER_ACCOUNT_CACHE_KEY, $key);
            }
        } else {
           $this->generateUserCache();
        }
        return true;
    }
    public function handleLogParams($user , $content , $relation_id = '' ,$relation_table = '', $relation_title='')
    {
        $data = [
            'creator' => $user,
            'content' => $content,
            'relation_table' => $relation_table,
            'relation_id' => $relation_id,
            'relation_title' => $relation_title
        ];
        return $data;
    }

    public function getUserDeptAndRoleByIds($param)
    {
        return app($this->userSystemInfoRepository)->getUserDeptAndRoleByIds($param);
    }
}
