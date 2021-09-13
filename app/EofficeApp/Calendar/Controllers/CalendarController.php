<?php

namespace App\EofficeApp\Calendar\Controllers;
use App\EofficeApp\Base\Controller;
use Illuminate\Http\Request;
use App\EofficeApp\Calendar\Requests\CalendarRequest;

/**
 * @日程模块控制器
 *
 * @author 史瑶
 */
class CalendarController extends Controller 
{

    private $calendarService; //会议模块服务类对象
    private $calendarSettingService;
    /**
     * @注册会议模块服务对象
     * @param \App\EofficeApp\Services\CalendarService $calendarService
     */

    public function __construct(
    Request $request, CalendarRequest $calendarRequest
    ) {

        parent::__construct();
        $this->formFilter($request, $calendarRequest);
        $this->request = $request;
        $this->calendarService = 'App\EofficeApp\Calendar\Services\CalendarService';
        $this->calendarRecordService = 'App\EofficeApp\Calendar\Services\CalendarRecordService';
        $this->calendarSettingService = 'App\EofficeApp\Calendar\Services\CalendarSettingService';
        $this->calendarAttentionService = 'App\EofficeApp\Calendar\Services\CalendarAttentionService';
        
        $this->userId = $this->own['user_id'];
    }
    
    public function deleteCalendarType($typeId)
    {
        return $this->returnResult(app($this->calendarSettingService)->deleteCalendarType($typeId));
    }
    public function getScheduleViewList()
    {
        return $this->returnResult(app($this->calendarRecordService)->getScheduleViewList($this->request->all(), $this->own));
    }
    public function getCalendarType() 
    {
        return $this->returnResult(app($this->calendarSettingService)->getCalendarType($this->request->all()));
    }
    public function setCalendarType($typeId)
    {
        return $this->returnResult(app($this->calendarSettingService)->setCalendarType($typeId, $this->request->all()));
    }
    public function setCalendarTypeSort() 
    {
        return $this->returnResult(app($this->calendarSettingService)->setCalendarTypeSort($this->request->all()));
    }
    /**
     * 新建日程类型
     * 
     * @return type
     */
    public function addCalendarType() 
    {
        return $this->returnResult(app($this->calendarSettingService)->addCalendarType($this->request->all()));
    }
    public function setDefaultCalendarType($typeId) 
    {
        return $this->returnResult(app($this->calendarSettingService)->setDefaultCalendarType($typeId));
    }
    public function getDefaultCalendarType() 
    {
        return $this->returnResult(app($this->calendarSettingService)->getDefaultCalendarType());
    }
    public function setDefalutValueByTypeId($typeId)
    {
        return $this->returnResult(app($this->calendarSettingService)->setDefalutValueByTypeId($typeId, $this->request->all()));
    }
    public function getDefalutValueByTypeId($typeId)
    {
        return $this->returnResult(app($this->calendarSettingService)->getDefalutValueByTypeId($typeId));
    }
    public function setJoinModuleConfig()
    {
        return $this->returnResult(app($this->calendarSettingService)->setJoinModuleConfig($this->request->all()));
    }
    public function getJoinModule() 
    {
        return $this->returnResult(app($this->calendarSettingService)->getJoinModule());
    }
    public function getJoinModuleConfig() 
    {
        return $this->returnResult(app($this->calendarSettingService)->getJoinModuleConfig());
    }
    /**
     * @获取日程列表
     * @param
     * @return
     */
    public function getCalendarList() {
        return $this->returnResult(app($this->calendarRecordService)->getCalendarList($this->request->all(), $this->userId));
    }

    /**
     * 获取我的日程列表
     *
     * @apiTitle 获取日程列表
     * @param {string} calendar_begin 开始时间
     * @param {string} calendar_end 结束时间
     * @param {string} type 日程类型
     * @param {string} user_id 用户ID
     *
     * @paramExample {string} 参数示例
     * {
     *  calendar_begin: 2018-05-27 00:00:00
     *  calendar_end: 2018-07-01 00:00:00
     *  type: other
     *  user_id: WV00000006
     * }
     *
     * @success {boolean} status(1) 接入成功
     * @successExample {json} Success-Response:
     * {
     *      "status": 1,
     *      "data": {
     *          "total": 25, // 日程数量
     *          "list": [ // 日程列表
     *              {
     *                  "calendar_id": 123, // 日程ID
     *                  "calendar_content": "开会", // 日程内容
     *                  "calendar_begin": "2018-06-14 00:00:00", // 日程开始时间
     *                  "calendar_end": "2018-06-14 23:59:00", // 日程结束时间
     *                  "calendar_level": 0, // 紧急程度
     *                  "create_id": "admin", // 创建人ID
     *                  "reminder_time": "2018-06-13 23:55:00", // 提醒时间
     *                  "reminder_timing": 5, // 日程开始前提醒时间
     *                  "repeat": 0, // 是否重复
     *                  "repeat_begin": "0000-00-00 00:00:00", // 重复日程开始时间
     *                  "repeat_end_number":0, // 重复结束次数
     *                  "repeat_end_time":"0000-00-00 00:00:00", // 重复日程结束时间
     *                  "repeat_end_type":0, // 重复日程结束类型
     *                  "repeat_remove":0, // 重复日程移除标记 0|未移除 1|移除
     *                  "repeat_str_day":1, // 按天重复周期默认为1
     *                  "repeat_str_mouth":1, // 按月重复周期默认为1
     *                  "repeat_str_week":1, // 按周重复周期默认为1
     *                  "repeat_type":1, // 重复类型
     *                  "share_user":"WV00000003", // 分享人
     *                  "user_id":"WV00000003,admin",// 办理人user_id
     *
     *              }
     *          .....
     * }
     *
     * @error {boolean} status(0) 接入失败
     * @error {array} errors 接入失败错误信息
     *
     * @errorExample {json} Error-Response:
     * { "status": 0,"errors":[{"code":"0x000003","message":"未知错误"}] }
     */
    public function getInitList() {
        return $this->returnResult(app($this->calendarRecordService)->getInitList($this->request->all(), $this->userId));
    }
    public function getInitListSelector() {
        return $this->returnResult(app($this->calendarRecordService)->getInitListSelector($this->request->all(), $this->userId, $this->own));
    }

    public function getPortalCalendarList() {
        return $this->returnResult(app($this->calendarRecordService)->getPortalCalendarList($this->request->all(), $this->userId));
    }

    public function setAttentionRead() {
        return $this->returnResult(app($this->calendarAttentionService)->setAttentionRead($this->request->all(), $this->userId));
    }

    /**
     * @通过id获取日程
     * @param 日程id
     * @return
     */
    public function getCalendarOne($calendar_id) {
        return $this->returnResult(app($this->calendarRecordService)->getCalendarOne($calendar_id, $this->userId, $this->request->all()));
    }
    
    public function getCalendarDetail($calendarId) {
        return $this->returnResult(app($this->calendarRecordService)->getCalendarOne($calendarId, $this->userId, $this->request->all()));
    }

    /**
     * @获取重复日程列表
     * @param  父日程id
     * @return
     */
    public function getRepeatCalendarList($calendar_id) {
        $result = app($this->calendarRecordService)->getRepeatCalendarList($calendar_id, $this->request->all(), $this->own['user_id']);
        return $this->returnResult($result);
    }

    /**
     * @新建日程
     * @param
     * @return 日程IDjson对象
     */
    public function addCalendar() {
        return $this->returnResult(app($this->calendarService)->addCalendar($this->request->all(), $this->userId));
    }

    /**
     * @编辑日程
     * @param
     * @return 日程IDjson对象
     */
    public function editCalendar($calendar_id) {
        return $this->returnResult(app($this->calendarService)->editCalendar($calendar_id, $this->request->all(), $this->userId));
    }

    /**
     * @编辑日程及所有重复日程
     * @param
     * @return 日程IDjson对象
     */
    public function editAllCalendar($calendar_id) {
        return $this->returnResult(app($this->calendarService)->editAllCalendar($calendar_id, $this->request->all(), $this->userId));
    }

    /**
     * @删除日程
     * @param
     * @return 日程IDjson对象
     */
    public function deleteCalendar() {
        return $this->returnResult(app($this->calendarService)->deleteCalendar($this->request->all(), $this->userId));
    }
    /**
     * @完成日程
     * @param
     * @return 日程IDjson对象
     */
    public function completeCalendar() {
        return $this->returnResult(app($this->calendarService)->completeCalendar($this->request->all(), $this->userId));
    }

    /**
     * @删除日程及所有子日程
     * @param
     * @return 日程IDjson对象
     */
    public function deleteAllRepeatCalendar() {
        return $this->returnResult(app($this->calendarService)->deleteAllRepeatCalendar($this->request->all()));
    }

    /**
     * @移除重复日程
     * @param
     * @return 日程IDjson对象
     */
    public function removeRepeatCalendar() {
        return $this->returnResult(app($this->calendarService)->removeRepeatCalendar($this->request->all()));
    }

    /**
     * @删除日程
     * @param
     * @return 日程IDjson对象
     */
    public function deleteCalendars() {
        return $this->returnResult(app($this->calendarService)->deleteCalendars($this->request->all()));
    }

    /**
     * @重复日程删除
     * @param
     * @return 日程IDjson对象
     */
    public function deleteRepeatCalendar() {
        return $this->returnResult(app($this->calendarService)->deleteRepeatCalendar($this->request->all()));
    }

    /**
     * 获取我的关注
     *
     * @param  string $userId 用户id
     *
     * @return json 返回结果或错误码
     *
     * @author qishaobo
     *
     * @since  2015-12-09
     */
    public function getMyAttention() {
        $param = $this->request->all();
        $userId = $this->userId;
        $result = app($this->calendarRecordService)->getMyAttention($userId, $param, $this->own);
        return $this->returnResult($result);
    }

    /**
     * 获取我的下属
     *
     * @param  string $userId 用户id
     *
     * @return json 返回结果或错误码
     *
     * @author qishaobo
     *
     * @since  2015-12-09
     */
    public function getMySubordinate() 
    {
        $result = app($this->calendarRecordService)->getMySubordinate($this->userId,  $this->request->all());
        return $this->returnResult($result);
    }

    /**
     * 查询微博日志关注人
     *
     * @return array 查询结果或错误码
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    public function getIndexDiaryAttention() {
        $result = app($this->calendarAttentionService)->getDiaryAttentionList($this->request->all());
        return $this->returnResult($result);
    }

    /**
     * 添加微博日志关注人
     *
     * @return int|array 操作成功状态|错误码
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    public function createDiaryAttention() {
        $result = app($this->calendarAttentionService)->createDiaryAttention($this->request->all(), $this->userId);
        return $this->returnResult($result);
    }

    /**
     * 更新微博日志关注人
     *
     * @param  int|string $attentionIds 关注表id,多个用逗号隔开
     *
     * @return int|array 返回结果或错误码
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    public function editDiaryAttention($attentionIds) {
        $result = app($this->calendarAttentionService)->updateDiaryAttention($attentionIds, $this->request->input('attention_status'), $this->userId);
        return $this->returnResult($result);
    }

    /**
     * 删除微博日志关注人
     *
     * @param  int|string $attentionIds 关注表id,多个用逗号隔开
     *
     * @return int|array 返回结果或错误码
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    public function deleteDiaryAttention($attentionIds) {
        $result = app($this->calendarAttentionService)->deleteDiaryAttention($attentionIds, $this->userId);
        return $this->returnResult($result);
    }
    public function deleteAttentionMy($attentionIds) {
        $result = app($this->calendarAttentionService)->deleteDiaryAttention($attentionIds, $this->userId);
        return $this->returnResult($result);
    }

    /**
     * 取消日程关注人
     *
     * @param  int|string $attentionIds 关注表id,多个用逗号隔开
     *
     * @return int|array 返回结果或错误码
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    public function cancelCalendarAttention($attentionUserId) {
        $result = app($this->calendarAttentionService)->cancelCalendarAttention($attentionUserId, $this->userId);
        return $this->returnResult($result);
    }

    /* 获取某月日程的日期
     *
     * @param  string $date 日期 2016-09
     *
     * @return int|array 返回结果或错误码
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */

    public function getCalendarMonthHasDate($date) {
        $result = app($this->calendarRecordService)->getCalendarMonthHasDate($date, $this->userId);
        return $this->returnResult($result);
    }

    /* 获取某天日程
     *
     * @param  string $date 日期 2016-09
     *
     * @return int|array 返回结果或错误码
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */

    public function getOneDayCalendar($date) {
        $result = app($this->calendarRecordService)->getOneDayCalendar($date, $this->userId, $this->request->all());
        return $this->returnResult($result);
    }

    /* 判断用户是否为日程办理人
     *
     * @param  string $date 日期 2016-09
     *
     * @return int|array 返回结果或错误码
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */

    public function isHandel($date) {
        $result = app($this->calendarService)->isHandel($date, $this->userId);
        return $this->returnResult($result);
    }

    public function getBaseSetInfo() {
        $result = app($this->calendarSettingService)->getBaseSetInfo($this->request->all());
        return $this->returnResult($result);
    }

    public function setBaseSetInfo() {
        $result = app($this->calendarSettingService)->setBaseSetInfo($this->request->all());
        return $this->returnResult($result);
    }

    /**
     * 新增关注分组
     * @return \App\EofficeApp\Base\json
     */
    public function addAttentionGroup() {
        $result = app($this->calendarAttentionService)->addAttentionGroup($this->request->all(), $this->own);
        return $this->returnResult($result);
    }

    /**
     * 获取关注分组列表
     * @return \App\EofficeApp\Base\json
     */
    function getAttentionGroupList() {
        $result = app($this->calendarAttentionService)->getAttentionGroupList($this->request->all(), $this->own);
        return $this->returnResult($result);
    }

    /**
     * 获取关注分组信息
     * @param $group_id
     * @return \App\EofficeApp\Base\json
     */
    function getAttentionGroupInfo($group_id) {
        $result = app($this->calendarAttentionService)->getAttentionGroupInfo($group_id, $this->own);
        return $this->returnResult($result);
    }
    /**
     * 保存关注分组信息
     * @param $group_id
     * @return \App\EofficeApp\Base\json
     */
    function saveAttentionGroupInfo($group_id) {
        $result = app($this->calendarAttentionService)->saveAttentionGroupInfo($group_id, $this->request->all(), $this->own);
        return $this->returnResult($result);
    }

    /**
     * 删除关注分组信息
     * @param $group_id
     * @return \App\EofficeApp\Base\json
     */
    function deleteAttentionGroup($group_id) {
        $result = app($this->calendarAttentionService)->deleteAttentionGroup($group_id, $this->own);
        return $this->returnResult($result);
    }

    /**
     * 获取多个用户的多个分组信息
     * @return \App\EofficeApp\Base\json
     */
    function getUsersAttentionGroupsInfo() {
        $result = app($this->calendarAttentionService)->getUsersAttentionGroupsInfo($this->request->all(), $this->own);
        return $this->returnResult($result);
    }

    /**
     * 关注分组增加用户
     * @return \App\EofficeApp\Base\json
     */
    function addAttentionGroupUser() {
        $result = app($this->calendarAttentionService)->addAttentionGroupUser($this->request->all(), $this->own);
        return $this->returnResult($result);
    }

    /**
     * 获取默认关注组织树
     * @return [type] [description]
     */
    function getAttentionTree($userId) {
        $result = app($this->calendarRecordService)->getAttentionTree($userId, $this->own);
        return $this->returnResult($result);
    }
    /**
     * 关注分组的用户
     * @param $group_id
     * @return \App\EofficeApp\Base\json
     */
    function getAttentionGroupUserList($group_id) {
        $result = app($this->calendarAttentionService)->getAttentionGroupUserList($group_id, $this->own);
        return $this->returnResult($result);
    }
    /**
     * 获取分组全部的日程
     * @param $group_id
     * @return \App\EofficeApp\Base\json
     */
    public function getGroupAllCalendar($group_id) {
        $result = app($this->calendarRecordService)->getGroupAllCalendar($group_id, $this->request->all(), $this->userId);
        return $this->returnResult($result);
    }
    public function saveFilter($userId) {
        $result = app($this->calendarRecordService)->saveFilter($userId, $this->request->all());
        return $this->returnResult($result);
    }

    public function getFilter($userId) {
        $result = app($this->calendarRecordService)->getFilter($userId);
        return $this->returnResult($result);
    }
    public function getConflictCalendar() 
    {
        $result = app($this->calendarRecordService)->getConflictCalendar($this->request->all());
        return $this->returnResult($result);
    }
    /**
     * 新建共享组
     */
    public function addCalendarPurview()
    {
        $result = app($this->calendarSettingService)->addCalendarPurview($this->request->all());
        return $this->returnResult($result);
    }
    public function getCalendarPurview()
    {
        $result = app($this->calendarSettingService)->getCalendarPurview($this->request->all());
        return $this->returnResult($result);
    }
    public function getCalendarPurviewDetail($id)
    {
        $result = app($this->calendarSettingService)->getCalendarPurviewDetail($id);
        return $this->returnResult($result);
    }
    public function deleteCalendarPurview($id)
    {
       $result = app($this->calendarSettingService)->deleteCalendarPurview($id);
        return $this->returnResult($result);
    }
}
