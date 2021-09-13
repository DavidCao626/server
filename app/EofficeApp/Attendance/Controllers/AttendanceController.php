<?php

namespace App\EofficeApp\Attendance\Controllers;

use Illuminate\Http\Request;
use App\EofficeApp\Base\Controller;
use App\EofficeApp\Attendance\Requests\AttendanceRequest;

/**
 * 考勤模块控制器
 *
 * @author  李志军
 *
 * @since  2017-06-26 创建
 */
class AttendanceController extends Controller
{
    protected $request;

    protected $attendanceService;
    protected $attendanceSettingService;
    private $attendanceStatService;
    private $attendanceRecordsService;
    private $attendanceMachineService;
    private $attendanceOutSendService;
    public function __construct(
            Request $request,
            AttendanceRequest $attendanceRequest
    )
    {
        parent::__construct();
//        exit;
        $this->formFilter($request, $attendanceRequest);

        $this->request = $request;

        $this->attendanceService = 'App\EofficeApp\Attendance\Services\AttendanceService';
        $this->attendanceSettingService = 'App\EofficeApp\Attendance\Services\AttendanceSettingService';
        $this->attendanceMachineService = 'App\EofficeApp\Attendance\Services\AttendanceMachineService';
        $this->attendanceRecordsService = 'App\EofficeApp\Attendance\Services\AttendanceRecordsService';
        $this->attendanceStatService = 'App\EofficeApp\Attendance\Services\AttendanceStatService';
        $this->attendanceOutSendService = 'App\EofficeApp\Attendance\Services\AttendanceOutSendService';
    }
    public function copyShift()
    {
        return $this->returnResult(app($this->attendanceSettingService)->copyShift($this->request->all(), $this->own['user_id']));
    }
    /**
     * 根据用户ID获取考勤相关信息的详情记录
     *
     * @list true
     *
     * @param int year 年份
     * @param int month 月份
     * @param string user_id 用户ID
     * @param string type 获取考勤的哪种类型的记录{旷工（absenteeism），外勤（outattend），考勤记录（attend），未签退（nosignout），早退（early），迟到（lag），校准（calibration），外出（out），出差（trip），请假（leave），带薪假（leavemeney），加班（overtime）}
     *
     * @paramExample {string} 参数示例
     * @paramExampleBody api/attend/detail/records/2017/09/admin/attend?autoFixPage=1&limit=30&order_by={"sign_date":"desc"}&page=1
     *
     * @success int status 成功状态
     * @success object data 返回的数据
     *
     * @successExample 成功返回示例:
     * @successExampleBody {"status":1,"data":{"total":7,"list":[{"record_id":238,"user_id":"admin","sign_date":"2017-11-21","sign_in_time":"13:40:41","sign_in_normal":"09:00","sign_out_time":"13:54:05","sign_out_normal":"18:00","lag_time":16841,"leave_early_time":14755,"must_attend_time":32400,"in_ip":"192.168.92.250","in_long":"","in_lat":"","in_address":"","in_platform":2,"out_ip":"192.168.92.250","out_long":"121.52655","out_lat":"31.080496","out_address":"\u4e0a\u6d77\u5e02\u95f5\u884c\u533a\u6d66\u6c5f\u9547\u6cdb\u5fae\u8f6f\u4ef6\u5927\u53a6","out_platform":2,"is_lag":1,"is_leave_early":1,"calibration_status":0,"calibration_reason":"","calibration_time":"","sign_times":1,"shift_id":"24","is_offset":0,"offset_lag_history":"","offset_early_history":"","attend_type":1,"remark":"","created_at":"2017-11-21 13:40:41","updated_at":"2017-11-21 13:54:05"}]},"runtime":0.131}
     *
     * @error {boolean} status(0) 获取信息失败
     *
     * @errorExample {json} 失败返回示例:
     * @errorExampleBody { "status": 0 }
     */
    public function getAttendDetailRecords($year, $month, $userId, $type)
    {
        return $this->returnResult(app($this->attendanceRecordsService)->getAttendDetailRecords($year, $month, $userId, $type, $this->request->all()));
    }
    public function getMyAttendDetailRecords($year, $month, $type)
    {
        return $this->returnResult(app($this->attendanceRecordsService)->getAttendDetailRecords($year, $month, $this->own['user_id'], $type, $this->request->all()));
    }
    public function getUserSchedulingDate($year,$month,$userId)
    {
        return $this->returnResult(app($this->attendanceService)->getUserSchedulingDate($year, $month, $userId, $this->own));
    }
    public function getMySchedulingDate($year,$month)
    {
        return $this->returnResult(app($this->attendanceService)->getUserSchedulingDate($year,$month,$this->own['user_id']));
    }
    public function getMobileMySchedulingDate($year,$month)
    {
        return $this->returnResult(app($this->attendanceService)->getMobileMySchedulingDate($year,$month,$this->own['user_id']));
    }
    /**
     * 签到
     *
     * @return boolean
     */
    public function signIn()
    {
        return $this->returnResult(app($this->attendanceService)->signIn($this->request->all(), $this->own));
    }
    /**
     * 签退
     *
     * @return boolean
     */
    public function signOut()
    {
        return $this->returnResult(app($this->attendanceService)->signOut($this->request->all(), $this->own));
    }
    /*
     * 外勤签到
     */
    public function externalSignIn()
    {
        return $this->returnResult(app($this->attendanceService)->externalSignIn($this->request->all(), $this->own));
    }
    /*
     * 外勤签退
     */
    public function externalSignOut()
    {
        return $this->returnResult(app($this->attendanceService)->externalSignOut($this->request->all(), $this->own));
    }
    /**
     *上报位置
     *
     * @return boolean
     */
    public function reportLocation()
    {
        return $this->returnResult(app($this->attendanceService)->reportLocation($this->request->all(), $this->own['user_id']));
    }
    /**
     * 判断是否支持自动登录
     *
     * @return true|false
     */
    public function autoSignIn()
    {
        return $this->returnResult(app($this->attendanceService)->autoSignIn($this->own,$this->request->all()));
    }
    /**
     * 申请校准
     *
     * @param int $recordId
     *
     * @return boolean
     */
    public function applyCalibration($recordId)
    {
        return $this->returnResult(app($this->attendanceService)->applyCalibration($this->request->all(), $recordId, $this->own));
    }
    /**
     * 获取考勤校验信息
     *
     * @param int $recordId
     *
     * @return object
     */
    public function getCalibrationInfo($recordId)
    {
        return $this->returnResult(app($this->attendanceService)->getCalibrationInfo($recordId));
    }
    /**
     * 获取校验列表
     *
     * @return array
     */
    public function getCalibrationRecords()
    {
        return $this->returnResult(app($this->attendanceService)->getCalibrationRecords($this->request->all(), $this->own));
    }
    /**
     * 一键批准所有申请
     *
     * @return type
     */
    public function approveAllApply()
    {
        return $this->returnResult(app($this->attendanceService)->approveAllApply($this->own, $this->request->input('apply_sign', 0)));
    }
    public function getFlowOutsendList($type)
    {
        return $this->returnResult(app($this->attendanceService)->getFlowOutsendList($this->request->all(), $type, $this->own));
    }

    public function returnedCalibration($recordId)
    {
        return $this->returnResult(app($this->attendanceService)->returnedCalibration($this->request->all(), $recordId, $this->own));
    }

    public function approveCalibration($recordId)
    {
        return $this->returnResult(app($this->attendanceService)->approveCalibration($this->request->all(), $recordId, $this->own));
    }
    public function getOneMonthSignRecords($userId, $year, $month)
    {
        return $this->returnResult(app($this->attendanceService)->getOneMonthSignRecords($userId, $year, $month));
    }
    public function getMobileRecords()
    {
        return $this->returnResult(app($this->attendanceRecordsService)->getMobileRecords($this->request->all(), $this->own));
    }
    public function getAttendInfo($userId, $signDate)
    {
        return $this->returnResult(app($this->attendanceService)->getAttendInfo($userId, $signDate));
    }
    public function getAdvancedAttendInfo($userId, $signDate)
    {

        return $this->returnResult(app($this->attendanceService)->getAdvancedAttendInfo($userId, $signDate));
    }
    public function getLeaveOrOutDays($userId)
    {
        return $this->returnResult(app($this->attendanceOutSendService)->getLeaveOrOutDays($this->request->input('start_time'),$this->request->input('end_time'), $userId,$this->request->input('vacation_id')));
    }
    public function getOvertimeDays($userId)
    {
        return $this->returnResult(app($this->attendanceOutSendService)->getOvertimeDays($this->request->input('start_time'),$this->request->input('end_time'), $userId));
    }
    public function getOneUserOneDaySignRecords($signDate, $userId)
    {
        return $this->returnResult(app($this->attendanceService)->getOneUserOneDaySignRecords($signDate, $userId));
    }
    public function getUserSchedulingRest($year,$month,$userId)
    {
        return $this->returnResult(app($this->attendanceService)->getUserSchedulingRest($year,$month,$userId));
    }
    public function getMySchedulingRest($year,$month)
    {
        return $this->returnResult(app($this->attendanceService)->getUserSchedulingRest($year,$month,$this->own['user_id']));
    }
    /**
     * 用于考勤机对接oa系统的考勤模块
     *
     * @param {string} user_id 用户ID
     * @param {string} sign_date 考勤日期
     * @param {int} sign_nubmer 当天第几次考勤（默认1，用于交换班考勤）
     * @param {string} sign_in_time 签到时间
     * @param {string} sign_out_time 签退时间
     *
     * @paramExample {json} 参数示例
     * {
     *  "user_id":"admin",
     *  "sign_date":"2017-01-01",
     *  "sign_number":1,
     *  "sign_in_time":"09:00:00",
     *  "sign_out_time":"18:00:00"
     * }
     *
     * @success {boolean} status(1) 接入成功
     *
     * @successExample {json} Success-Response:
     * { "status": 1 }
     *
     * @error {boolean} status(0) 接入失败
     * @error {array} errors 接入失败错误信息
     *
     * @errorExample {json} Error-Response:
     * { "status": 0,"errors":[{"code":"0x003005","message":"休息日，不可考勤"}] }
     */
    public function attendMachineAccess()
    {
        return $this->returnResult(app($this->attendanceMachineService)->attendMachineAccess($this->request->all()));
    }
    public function getMachineDatabasesTables()
    {
        return $this->returnResult(app($this->attendanceMachineService)->getMachineDatabasesTables($this->request->all()));
    }
    public function getMachineDatabaseFields()
    {
        return $this->returnResult(app($this->attendanceMachineService)->getMachineDatabaseFields($this->request->all()));
    }
    public function saveMachineConfig()
    {
        return $this->returnResult(app($this->attendanceMachineService)->saveMachineConfig($this->request->all()));
    }
    public function getMachineConfig()
    {
        return $this->returnResult(app($this->attendanceMachineService)->getMachineConfig(1));
    }
    public function getMachineConfigById($id)
    {
        return $this->returnResult(app($this->attendanceMachineService)->getMachineConfigById($id));
    }
    public function switchAttendanceType($type)
    {
        return $this->returnResult(app($this->attendanceMachineService)->switchAttendanceType($type));
    }
    public function getMultiIsAuto()
    {
        return $this->returnResult(app($this->attendanceMachineService)->getMultiIsAuto());
    }
    public function setMultiIsAuto()
    {
        return $this->returnResult(app($this->attendanceMachineService)->setMultiIsAuto($this->request->all()));
    }
    public function useMachineConfigById($id)
    {
        return $this->returnResult(app($this->attendanceMachineService)->useMachineConfigById($id));
    }
     public function deleteMachineConfig(){
        return $this->returnResult(app($this->attendanceMachineService)->deleteMachineConfig($this->request->all()));
    }
    /**
     * 获取考勤机配置案例
     */
    public function getMachineCase()
    {
        return $this->returnResult(app($this->attendanceMachineService)->getMachineCase($this->request->all()));
    }
    // 考勤机同步用户时获取用户列表
    public function getUserList(){
        return $this->returnResult(app($this->attendanceMachineService)->getUserList($this->request->all()));
    }
    public function matchUser(){
        return $this->returnResult(app($this->attendanceMachineService)->matchUser($this->request->all()));
    }
    public function getMatchUserField()
    {
        return $this->returnResult(app($this->attendanceMachineService)->getMatchUserField());
    }
    public function setMatchUserField()
    {
        return $this->returnResult(app($this->attendanceMachineService)->setMatchUserField($this->request->all()));
    }
    public function synchronousAttendance(){
        return $this->returnResult(app($this->attendanceMachineService)->synchronousAttendance($this->request->all(),$this->own['user_id']));
    }

    public function getAttendanceTime(){
        return $this->returnResult(app($this->attendanceMachineService)->getAttendanceTime($this->request->all()));
    }
    public function getAttendanceRecords(){
        return $this->returnResult(app($this->attendanceMachineService)->getAttendanceRecords($this->request->all()));
    }
    public function addImportLog()
    {
        return $this->returnResult(app($this->attendanceMachineService)->addImportLog($this->own['user_id']));
    }
    public function getImportLogs()
    {
        return $this->returnResult(app($this->attendanceMachineService)->getImportLogs($this->request->all()));
    }
    /**
     * 移动考勤设置
     *
     * @return boolean
     */
    public function setMobileBase()
    {
        return $this->returnResult(app($this->attendanceSettingService)->setMobileBase($this->request->all()));
    }
    public function getMobileBase()
    {
        return $this->returnResult(app($this->attendanceSettingService)->getMobileBase());
    }
    public function addMobilePoints()
    {
        return $this->returnResult(app($this->attendanceSettingService)->addMobilePoints($this->request->all()));
    }
    public function editMobilePoints($pointId)
    {
        return $this->returnResult(app($this->attendanceSettingService)->editMobilePoints($this->request->all(), $pointId));
    }
    public function deleteMobilePoints($pointId)
    {
        return $this->returnResult(app($this->attendanceSettingService)->deleteMobilePoints($pointId));
    }
    public function mobilePointsDetail($pointId)
    {
        return $this->returnResult(app($this->attendanceSettingService)->mobilePointsDetail($pointId));
    }
    public function mobilePointsList()
    {
        return $this->returnResult(app($this->attendanceSettingService)->mobilePointsList($this->request->all()));
    }
    public function setMobileNoPoint()
    {
        return $this->returnResult(app($this->attendanceSettingService)->setMobileNoPoint($this->request->all()));
    }
    public function getMobileNoPoint()
    {
        return $this->returnResult(app($this->attendanceSettingService)->getMobileNoPoint());
    }
    public function  getOutAttendancePriv() 
    {
        return $this->returnResult(app($this->attendanceSettingService)->getOutAttendancePriv($this->own));
    }
    public function setPcSign()
    {
        return $this->returnResult(app($this->attendanceSettingService)->setPcSign($this->request->all()));
    }
    public function getPcSign()
    {
        return $this->returnResult(app($this->attendanceSettingService)->getPcSign());
    }

    public function getSchedulingDate($year, $schedulingId)
    {
        return $this->returnResult(app($this->attendanceSettingService)->getSchedulingDate($schedulingId, $year));
    }

    public function setSchedulingDate($year, $schedulingId)
    {
        return $this->returnResult(app($this->attendanceSettingService)->setSchedulingDate($this->request->all(), $year, $schedulingId));
    }
    public function getWifiList()
    {
        return $this->returnResult(app($this->attendanceSettingService)->getWifiList($this->request->all()));
    }
    public function editWifi($wifiId)
    {
        return $this->returnResult(app($this->attendanceSettingService)->editWifi($this->request->all(), $wifiId));
    }
    public function addWifi()
    {
        return $this->returnResult(app($this->attendanceSettingService)->addWifi($this->request->all()));
    }
    public function getWifiDetail($wifiId)
    {
        return $this->returnResult(app($this->attendanceSettingService)->getWifiDetail($wifiId));
    }
    public function deleteWifiInfo($wifiId)
    {
        return $this->returnResult(app($this->attendanceSettingService)->deleteWifiInfo($wifiId));
    }
    public function getSchemeList()
    {
        return $this->returnResult(app($this->attendanceSettingService)->getSchemeList($this->request->all()));
    }

    public function addScheme()
    {
        return $this->returnResult(app($this->attendanceSettingService)->addScheme($this->request->all()));
    }

    public function deleteScheme($schemeId)
    {
        return $this->returnResult(app($this->attendanceSettingService)->deleteScheme($schemeId));
    }

    public function quickEditSchemeStatus($schemeId)
    {
        return $this->returnResult(app($this->attendanceSettingService)->quickEditSchemeStatus($this->request->all(), $schemeId));
    }

    public function getOneSchemeDetail($schemeId)
    {
        return $this->returnResult(app($this->attendanceSettingService)->getOneSchemeDetail($schemeId));
    }

    public function editScheme($schemeId)
    {
        return $this->returnResult(app($this->attendanceSettingService)->editScheme($this->request->all(),$schemeId));
    }

    public function getSchemeDetailByID($schemeId)
    {
        return $this->returnResult(app($this->attendanceSettingService)->getSchemeDetailByID($schemeId));
    }

    public function getUsedSchemeDetail()
    {
        return $this->returnResult(app($this->attendanceSettingService)->getUsedSchemeDetail());
    }

    public function editSchemeRest($schemeId)
    {
        return $this->returnResult(app($this->attendanceSettingService)->editSchemeRest($this->request->all(), $schemeId));
    }
    public function addPurviewGroup()
    {
        return $this->returnResult(app($this->attendanceSettingService)->addPurviewGroup($this->request->all()));
    }
    public function editPurviewGroup($groupId)
    {
        return $this->returnResult(app($this->attendanceSettingService)->editPurviewGroup($this->request->all(), $groupId));
    }
    public function getPurviewGroupList()
    {
        return $this->returnResult(app($this->attendanceSettingService)->getPurviewGroupList($this->request->all()));
    }
    public function getPurviewGroupDetail($groupId)
    {
        return $this->returnResult(app($this->attendanceSettingService)->getPurviewGroupDetail($groupId));
    }
    public function deletePurviewGroup($groupId) {
        return $this->returnResult(app($this->attendanceSettingService)->deletePurviewGroup($groupId));
    }
    public function getPurviewUser($menuId) {
        return $this->returnResult(app($this->attendanceSettingService)->getPurviewUser($this->own, $menuId));
    }
    public function getExportPurview($menuId) {
        return $this->returnResult(app($this->attendanceSettingService)->getExportPurview($this->own, $menuId));
    }
    /**
     * 新建班次
     *
     * @return object
     */
    public function addShift()
    {
        return $this->returnResult(app($this->attendanceSettingService)->insertShift($this->request->all(), $this->own['user_id']));
    }
    /**
     * 编辑班次
     *
     * @param int $shiftId
     *
     * @return object
     */
    public function editShift($shiftId)
    {
        return $this->returnResult(app($this->attendanceSettingService)->editShift($this->request->all(), $this->own['user_id'], $shiftId));
    }
    /**
     * 获取班次列表
     *
     * @return array
     */
    public function shiftList()
    {
        return $this->returnResult(app($this->attendanceSettingService)->shiftList($this->request->all()));
    }
    /**
     * 删除班次
     *
     * @param int $shiftId
     *
     * @return boolean
     */
    public function deleteShift($shiftId)
    {
        return $this->returnResult(app($this->attendanceSettingService)->deleteShift($shiftId, $this->own['user_id']));
    }
    /**
     * 获取班次详情
     *
     * @param int $shiftId
     *
     * @return object
     */
    public function shiftDetail($shiftId)
    {
        return $this->returnResult(app($this->attendanceSettingService)->shiftDetail($shiftId));
    }
    /**
     * 新建班组
     *
     * @return object
     */
    public function addScheduling()
    {
        return $this->returnResult(app($this->attendanceSettingService)->addScheduling($this->request->all()));
    }
    /**
     * 编辑班组
     *
     * @param int $schedulingId
     *
     * @return object
     */
    public function editScheduling($schedulingId)
    {
        return $this->returnResult(app($this->attendanceSettingService)->editScheduling($this->request->all(), $schedulingId));
    }

    /**
     * 同步班组的节假日到其他班组中
     * @return \App\EofficeApp\Base\json
     */
    public function syncHoliday()
    {
        return $this->returnResult(app($this->attendanceSettingService)->syncHoliday($this->request->all()));
    }
    /**
     * 获取班组列表
     *
     * @return array
     */
    public function schedulingList()
    {
        return $this->returnResult(app($this->attendanceSettingService)->schedulingList($this->request->all()));
    }
    /**
     * 获取班组详情
     *
     * @param int $schedulingId
     *
     * @return object
     */
    public function schedulingDetail($schedulingId)
    {
        return $this->returnResult(app($this->attendanceSettingService)->schedulingDetail($schedulingId));
    }
    /**
     * 删除班组
     *
     * @param int $schedulingId
     *
     * @return boolean
     */
    public function deleteScheduling($schedulingId)
    {
        return $this->returnResult(app($this->attendanceSettingService)->deleteScheduling($schedulingId));
    }
    /**
     * 快速编辑班组属性
     *
     * @param int $schedulingId
     *
     * @return boolean
     */
    public function quickEditScheduling($schedulingId)
    {
        return $this->returnResult(app($this->attendanceSettingService)->quickEditScheduling($this->request->all(), $schedulingId));
    }
    /**
     * 考勤记录列表
     *
     * @return array
     */
    public function moreAttendRecords()
    {
        return $this->returnResult(app($this->attendanceRecordsService)->moreAttendRecords($this->request->all(), $this->own));
    }

    /**
     * 我的考勤记录
     *
     * @return array
     */
    public function oneAttendRecords($userId)
    {
        return $this->returnResult(app($this->attendanceRecordsService)->oneAttendRecords($this->request->all(),$userId));
    }
    public function myAttendRecords()
    {
        return $this->returnResult(app($this->attendanceRecordsService)->oneAttendRecords($this->request->all(),$this->own['user_id']));
    }
    /**
     * 单人考勤统计
     *
     * @param string $userId
     *
     * @return array
     */
    public function oneAttendStat($userId)
    {
        return $this->returnResult(app($this->attendanceStatService)->oneAttendStat($this->request->all(),$userId));
    }
    public function myAttendStat()
    {
        return $this->returnResult(app($this->attendanceStatService)->oneAttendStat($this->request->all(),$this->own['user_id']));
    }
    /**
     * 多人考勤统计
     *
     * @return array
     */
    public function moreAttendStat()
    {
        return $this->returnResult(app($this->attendanceStatService)->moreAttendStat($this->request->all(), $this->own));
    }
    public function moreAttendStatNoHeader()
    {
        return $this->returnResult(app($this->attendanceStatService)->moreAttendStatNoHeader($this->request->all(), $this->own));
    }
    public function getOvertimeRuleList()
    {
        return $this->returnResult(app($this->attendanceSettingService)->getOvertimeRuleList($this->request->all()));
    }

    public function addOvertimeRule()
    {
        return $this->returnResult(app($this->attendanceSettingService)->addOvertimeRule($this->request->all()));
    }

    public function getOvertimeRuleDetail($ruleId)
    {
        return $this->returnResult(app($this->attendanceSettingService)->getOvertimeRuleDetail($ruleId));
    }

    public function updateOvertimeRule($ruleId)
    {
        return $this->returnResult(app($this->attendanceSettingService)->updateOvertimeRule($this->request->all(), $ruleId));
    }

    public function deleteOvertimeRule($ruleId)
    {
        return $this->returnResult(app($this->attendanceSettingService)->deleteOvertimeRule($ruleId));
    }

    public function editOvertimeRuleOpenStatus()
    {
        return $this->returnResult(app($this->attendanceSettingService)->editOvertimeRuleOpenStatus($this->request->all()));
    }
    public function editCommonSetting($ruleType)
    {
        return $this->returnResult(app($this->attendanceSettingService)->editCommonSetting($this->request->all(), $ruleType));
    }
    public function getCommonSetting($ruleType)
    {
        return $this->returnResult(app($this->attendanceSettingService)->getCommonSetting($ruleType));
    }

    public function getAttendLogs($type)
    {
        return $this->returnResult(app($this->attendanceRecordsService)->getAttendLogs($this->request->all(), $type, $this->own));
    }
    public function getAttendOriginalRecords()
    {
        return $this->returnResult(app($this->attendanceRecordsService)->getAttendOriginalRecords($this->request->all(), $this->own));
    }
    public function getMyAttendLogs($type)
    {
        return $this->returnResult(app($this->attendanceRecordsService)->getMyAttendLogs($this->request->all(), $type, $this->own));
    }
    /**
     * 流程验证需要返回0或者1，流程暂时不支持自定义错误展示
     * @param $type
     * @return \App\EofficeApp\Base\json|string
     */
    public function outSendValidate($type)
    {
        $res = app($this->attendanceOutSendService)->outSendValidate($this->request->all(), $type);
        return intval($res);
    }

    /**
     * 获取用户某天的打卡时间段
     * @param $userId
     * @param $signDate
     * @return \App\EofficeApp\Base\json
     */
    public function getUserShiftTime()
    {
        return $this->returnResult(app($this->attendanceOutSendService)->getUserShiftTime($this->request->all()));
    }

    /**
     * 获取我最近的请假记录，用户销假选择器
     * @return \App\EofficeApp\Base\json
     */
    public function getMyLeaveRecords()
    {
        return $this->returnResult(app($this->attendanceRecordsService)->getMyLeaveRecords($this->request->all(),$this->own['user_id']));
    }

    /**
     * 获取某个用户的请假记录
     * @return \App\EofficeApp\Base\json
     */
    public function getUserLeaveRecords()
    {
        return $this->returnResult(app($this->attendanceOutSendService)->getUserLeaveRecords($this->request->all()));
    }

    /**
     * 解析请假记录详情
     * @param $leaveId
     * @return \App\EofficeApp\Base\json
     */
    public function getLeaveRecordsDetail($leaveId)
    {
        return $this->returnResult(app($this->attendanceOutSendService)->getLeaveRecordsDetail($leaveId));
    }

    /**
     * 加班补偿方式
     * @return \App\EofficeApp\Base\json
     */
    public function getOvertimeTo()
    {
        return $this->returnResult(app($this->attendanceOutSendService)->getOvertimeTo());
    }
    public function getAllScheduling() 
    {
        return $this->returnResult(app($this->attendanceSettingService)->getAllScheduling());
    }
    public function copyScheduling($schedulingId) 
    {
        return $this->returnResult(app($this->attendanceSettingService)->copyScheduling($schedulingId));
    }
    public function importHoliday() 
    {
        return $this->returnResult(app($this->attendanceSettingService)->importHoliday($this->request->all()));
    }
    public function importScheduling() 
    {
        return $this->returnResult(app($this->attendanceSettingService)->importScheduling($this->request->all(), $this->own['user_id']));
    }
    public function isHideSignOutButton() 
    {
        return $this->returnResult(app($this->attendanceSettingService)->isHideSignOutButton($this->own));
    }
    public function getRepairType()
    {
        return $this->returnResult(app($this->attendanceOutSendService)->getRepairType());
    }
    public function abnormalUserStat() 
    {
        return $this->returnResult(app($this->attendanceStatService)->abnormalUserStat($this->request->all(), $this->own));
    }
    
    public function abnormalDetailStat($type)
    {
        return $this->returnResult(app($this->attendanceStatService)->abnormalDetailStat($this->request->all(), $type));
    }
}
