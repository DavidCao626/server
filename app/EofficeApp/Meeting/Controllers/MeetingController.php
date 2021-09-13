<?php
namespace App\EofficeApp\Meeting\Controllers;

use Illuminate\Http\Request;
use App\EofficeApp\Base\Controller;
use App\EofficeApp\Meeting\Requests\MeetingRequest;
use App\EofficeApp\Meeting\Services\MeetingService;
use QrCode;
/**
 * @会议模块控制器
 *
 * @author 李志军
 */
class MeetingController extends Controller
{
	private $meetingService;//会议模块服务类对象

	/**
	 * @注册会议模块服务对象
	 * @param \App\EofficeApp\Services\MeetingService $meetingService
	 */
	public function __construct(
        Request $request,
        MeetingService $meetingService,
        MeetingRequest $meetingRequest
    ) {
		parent::__construct();
		$this->meetingService = $meetingService;
        $this->meetingRequest = $meetingRequest;
        //$this->formFilter($request, $meetingRequest);
         $this->request = $request;
	}
	/**
	 * @获取会议设备列表
	 * @param \Illuminate\Http\Request $request
	 * @return 会议设备类别json对象
	 */
	public function listEquipment()
	{
		return $this->returnResult($this->meetingService->listEquipment($this->request->all()));
	}
	/**
	 * @新建会议设备
	 * @param \App\Http\Requests\StoreMeetingEquipmentRequest $request
	 * @return 设备IDjson对象
	 */
	public function addEquipment()
	{
		return $this->returnResult($this->meetingService->addEquipment($this->request->all()));
	}
	/**
	 * @编辑会议设备
	 * @param \App\Http\Requests\StoreMeetingEquipmentRequest $request
	 * @param type $equipmentId
	 * @return 成功与否
	 */
	public function editEquipment($equipmentId)
	{
		return $this->returnResult($this->meetingService->editEquipment($this->request->all(), $equipmentId));
	}
	/**
	 * @删除会议设备
	 * @param type $equipmentId
	 * @return 成功与否
	 */
	public function deleteEquipment($equipmentId)
	{
		return $this->returnResult($this->meetingService->deleteEquipment($equipmentId));
	}
	/**
	 * @获取会议设备详情
	 * @param type $equipmentId
	 * @return 会议设备详情json对象
	 */
	public function showEquipment($equipmentId)
	{
		return $this->returnResult($this->meetingService->showEquipment($equipmentId));
	}
	/**
	 * @获取会议室列表
	 * @param \Illuminate\Http\Request $request
	 * @return 会议室列表对象
	 */
	public function listRoom()
	{
        $param = $this->request->all();
		return $this->returnResult($this->meetingService->listRoom($param, $this->own));
	}
	/**
	 * @获取会议室列表(无权限)
	 * @param \Illuminate\Http\Request $request
	 * @return 会议室列表对象
	 */
	public function getlistRooms()
	{
		return $this->returnResult($this->meetingService->getlistRooms($this->request->all()));
	}
	/**
	 * @新建会议室
	 * @param \App\Http\Requests\StoreMeetingRoomsRequest $request
	 * @return 会议室IDjson对象
	 */
	public function addRoom()
	{
		return $this->returnResult($this->meetingService->addRoom($this->request->all()));
	}
	/**
	 * @编辑会议室
	 * @param \App\Http\Requests\StoreMeetingRoomsRequest $request
	 * @param type $roomId
	 * @return 成功与否
	 */
	public function editRoom($roomId)
	{
		return $this->returnResult($this->meetingService->editRoom($this->request->all(), $roomId));
	}
	/**
	 * @获取会议室详情
	 * @param type $roomId
	 * @return 会议室详情json对象
	 */
	public function showRoom($roomId)
	{
		return $this->returnResult($this->meetingService->showRoom($roomId));
	}
	/**
	 * @删除会议室
	 * @param type $roomId
	 * @return 成功与否
	 */
	public function deleteRoom($roomId)
	{
		return $this->returnResult($this->meetingService->deleteRoom($roomId));
	}
	/**
	 * @获取审批的会议列表
	 * @param \Illuminate\Http\Request $request
	 * @return 返回审批会议列表json对象
	 */
	public function listApproveMeeting()
	{
        $param = $this->request->all();
        $userInfo = $this->own;
        $param['user_id'] = isset($userInfo['user_id']) ? $userInfo['user_id'] : '';
        $param['role_id'] = implode(',', isset($userInfo['role_id']) ? $userInfo['role_id'] : '');
        $param['dept_id'] = isset($userInfo['dept_id']) ? $userInfo['dept_id'] : '';
		return $this->returnResult($this->meetingService->listApproveMeeting($param, $this->own));
	}
    /**
     * 获取我的会议的会议列表
     *
     * @apiTitle 获取会议列表
     * @param {int} autoFixPage 起始页
     * @param {int} limit 每页显示数量
     * @param {json} order_by 排序
     * @param {int} page 页码
     * @param {json} search 查询条件
     *
     * @paramExample {string} 参数示例
     * {
     *  autoFixPage: 1
     *  limit: 10
     *  order_by: {"meeting_apply_id":"desc"}
     *  page: 1
     *  search: {"meeting_subject":["座谈会","like"]}
     * }
     *
     * @success {boolean} status(1) 接入成功
     * @successExample {json} Success-Response:
     * {
     *      "status": 1,
     *      "data": {
     *          "total": 12, // 会议数量
     *          "list": [ // 会议列表
     *              {
     *                  "meeting_apply_id": 12, // 会议ID
     *                  "meeting_room_id": 1, // 会议室ID
     *                  "meeting_subject": "每周例会", // 会议主题
     *                  "meeting_apply_user": "admin", // 会议申请人
     *                  "meeting_join_member": "admin, WVWV00000306, WV00000307", // 为all则代表全体人员
     *                  "meeting_other_join_member": "张三, 李四", // 会议其他参加人员
     *                  "meeting_type": 3, // 会议类型ID
     *                  "meeting_begin_time": "2018-04-18 10:49:00", // 会议开始时间
     *                  "meeting_end_time": "2018-04-18 11:49:00", // 会议结束时间
     *                  "meeting_status": 5, // 会议状态,0表示待审批,1审批中,2表示已批准,3表示拒绝,4表示会议开始,5表示会议结束
     *                  "meeting_remark": "研发部每周例会", // 会议备注
     *                  "meeting_approval_opinion": "同意申请", // 会议审批意见
     *                  "meeting_begin_remark": "会议开始备注", // 会议开始备注
     *                  "meeting_end_remark": "会议结束备注", // 会议结束备注
     *                  "conflict": "1,5,14", // 冲突会议ID
     *                  "meeting_apply_time": "2018-04-17 15:38:31", // 会议申请时间
     *                  "equipment_id": 3, // 会议室设备ID
     *                  "room_name": "中心会议室", // 会议室名称
     *                  "room_phone": "13000000000", // 会议室电话
     *                  "room_space": 20, // 可容纳人数
     *                  "room_use": "例会专用", // 会议室用途
     *                  "room_remark": "会议室备注", // 会议室备注
     *                  "user_name": "张三", // 申请人姓名
     *                  "meeting_sort_id": 5, // 会议室所属类别ID
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
	public function listOwnMeeting()
	{
        $param = $this->request->all();
        $param['meeting_apply_user'] = $this->own['user_id'];
		return $this->returnResult($this->meetingService->listOwnMeeting($param, $this->own));
	}
	//某个会议的参加人员
	public function getMeetingMyAttenceUsers($mApplyId) {
		return $this->returnResult($this->meetingService->getMeetingMyAttenceUsers($this->request->all(), $mApplyId));
	}
	/**
	 * @我的会议详情信息
	 * @param type $mApplyId
	 * @return 我的会议详情信息json对象
	 */

	public function showOwnMeeting($mApplyId)
	{
		return $this->returnResult($this->meetingService->showOwnMeeting($this->request->all(), $mApplyId, $this->own));
	}

	/**
	 * @会议签到
	 * @param type $mApplyId
	 * @return 我的会议详情信息json对象
	 */
	public function signMeeting($mApplyId) {
		return $this->returnResult($this->meetingService->signMeeting($this->request->all(), $mApplyId, $this->own));
	}
	/**
	 * @审批会议详情信息
	 * @param type $mApplyId
	 * @return 审批会议详情信息json对象
	 */
	public function showApproveMeeting($mApplyId)
	{
		return $this->returnResult($this->meetingService->showApproveMeeting($mApplyId, $this->own));
	}
	/**
	 * @新建会议申请
	 * @param \App\Http\Requests\StoreMeetingApplyRequest $request
	 * @return 会议申请id号json对象
	 */
	public function addMeeting()
	{
		return $this->returnResult($this->meetingService->addMeeting($this->request->all(), $this->own));
	}
	/**
	 * @编辑会议申请
	 * @param \App\Http\Requests\StoreMeetingApplyRequest $request
	 * @param type $mApplyId
	 * @return 成功与否
	 */
	public function editMeeting($mApplyId)
	{
		return $this->returnResult($this->meetingService->editMeeting($this->request->all(), $mApplyId, $this->own));
	}
	/**
	 * @批准会议
	 * @param \Illuminate\Http\Request $request
	 * @param type $mApplyId
	 * @return 成功与否
	 */
	public function approveMeeting($mApplyId)
	{
		return $this->returnResult($this->meetingService->approveMeeting($this->request->all(), $mApplyId, $this->own));
	}
	/**
	 * @拒绝会议
	 * @param \Illuminate\Http\Request $request
	 * @param type $mApplyId
	 * @return 成功与否
	 */
	public function refuseMeeting($mApplyId)
	{
		return $this->returnResult($this->meetingService->refuseMeeting($this->request->all(), $mApplyId, $this->own));
	}
	/**
	 * @开始会议
	 * @param \Illuminate\Http\Request $request
	 * @param type $mApplyId
	 * @return 成功与否
	 */
	public function startMeeting($mApplyId)
	{
		return $this->returnResult($this->meetingService->startMeeting($this->request->all(), $mApplyId));
	}

	public function attenceMeeting($mApplyId) {
		return $this->returnResult($this->meetingService->attenceMeeting($this->request->all(), $mApplyId, $this->own));
	}

	public function refuseAttenceMeeting($mApplyId) {
		return $this->returnResult($this->meetingService->refuseAttenceMeeting($this->request->all(), $mApplyId, $this->own));
	}
	/**
	 * @结束会议
	 * @param \Illuminate\Http\Request $request
	 * @param type $mApplyId
	 * @return 成功与否
	 */
	public function endMeeting($mApplyId)
	{
		return $this->returnResult($this->meetingService->endMeeting($this->request->all(), $mApplyId, $this->own));
	}
	/**
	 * @删除审批菜单下的会议
	 * @param type $mApplyId
	 * @return 成功与否
	 */
	public function deleteApproveMeeting($mApplyId)
	{
		return $this->returnResult($this->meetingService->deleteApproveMeeting($mApplyId));
	}
	/**
	 * @删除我的会议菜单下的会议
	 * @param type $mApplyId
	 * @return 成功与否
	 */
	public function deleteOwnMeeting($mApplyId)
	{
		return $this->returnResult($this->meetingService->deleteOwnMeeting($mApplyId, $this->own['user_id']));
	}
	/**
	 * @获取会议记录列表
	 * @param \Illuminate\Http\Request $request
	 * @return 会议记录列表json对象
	 */
	public function listMeetingRecords()
	{
		return $this->returnResult($this->meetingService->listMeetingRecords($this->request->all(), $this->own));
	}
	/**
	 * @新建会议记录
	 * @param \App\Http\Requests\StoreMeetingRecordsRequest $request
	 * @return 会议记录id
	 */
	public function getMeetingRecordDetailById($recordId)
	{
		return $this->returnResult($this->meetingService->getMeetingRecordDetailById($recordId));
	}
	/**
	 * @新建会议记录
	 * @param \App\Http\Requests\StoreMeetingRecordsRequest $request
	 * @return 会议记录id
	 */
	public function addMeetingRecord()
	{
		return $this->returnResult($this->meetingService->addMeetingRecord($this->request->all(), $this->own));
	}
	/**
	 * @编辑会议记录
	 * @param \App\Http\Requests\StoreMeetingRecordsRequest $request
	 * @param type $recordId
	 * @return 成功与否
	 */
	public function editMeetingRecord($recordId)
	{
		return $this->returnResult($this->meetingService->editMeetingRecord($this->request->all(), $recordId, $this->own));
	}
	/**
	 * @删除会议记录
	 * @param type $recordId
	 * @return 成功与否
	 */
	public function deleteMeetingRecord($recordId)
	{
		return $this->returnResult($this->meetingService->deleteMeetingRecord($recordId));
	}
	/**
	 * @获取某个会议室的所有会议列表
	 * @param type $recordId
	 * @return 成功与否
	 */
	public function getMeetingListByRoomId()
	{
		return $this->returnResult($this->meetingService->getMeetingListByRoomId($this->request->all()));
	}
	/**
	 * @获取冲突会议的数量用于冲突标签徽章
	 * @param filterType
	 * @return json
	 */
	public function getMeetingConflictEmblem()
	{
        $param = $this->request->all();
        $param['user_id'] = $this->own['user_id'];
		return $this->meetingService->getMeetingConflictEmblem($param, $this->own);
	}

    /**
     * 待审批的状态改变为审批中
     *
     * @return boolean
     */
    public function setMeetingApply($mApplyId){
        $result = $this->meetingService->setMeetingApply($mApplyId);
        return $this->returnResult($result);
    }

    /**
     * 判断是否有会议详情的查看权限
     * @param string $meeting_apply_id
     * @return boolean
     */
    public function getMeetingDetailPermissions($mApplyId) {
    	return $this->returnResult($this->meetingService->getMeetingDetailPermissions($mApplyId, $this->own));
    }

    /**
     * 新建会议申请前判断申请时间段是否与现有会议有冲突
     * @param array
     * @return boolean
     */
    public function getNewMeetingDateWhetherConflict() {
    	$result = $this->meetingService->getNewMeetingDateWhetherConflict($this->request->all());
    	return $this->returnResult($result);
    }

    /**
     * 获取日期时间范围内所有的会议列表
     * @param array
     * @return array
     */
    public function getAllMeetingList() {
        $param = $this->request->all();
        $userInfo = $this->own;
        $param['user_id'] = isset($userInfo['user_id']) ? $userInfo['user_id'] : '';
        $param['role_id'] = implode(',', isset($userInfo['role_id']) ? $userInfo['role_id'] : '');
        $param['dept_id'] = isset($userInfo['dept_id']) ? $userInfo['dept_id'] : '';
    	$result = $this->meetingService->getAllMeetingList($param);
    	return $this->returnResult($result);
    }

    /**
     * 获取会议室使用情况表格
     * @param array
     * @return string
     */
    public function getMeetingRoomUsageTable() {
        $param = $this->request->all();
        $userInfo = $this->own;
        $param['user_id'] = isset($userInfo['user_id']) ? $userInfo['user_id'] : '';
        $param['role_id'] = implode(',', isset($userInfo['role_id']) ? $userInfo['role_id'] : '');
        $param['dept_id'] = isset($userInfo['dept_id']) ? $userInfo['dept_id'] : '';
        if(isset($param['version']) && $param['version'] == 2){
            $result = $this->meetingService->getMeetingRoomUsageTableV2($param);
        }else{
            $result = $this->meetingService->getMeetingRoomUsageTable($param);
        }
    	return $this->returnResult($result);
    }

    /**
     * 根据会议室名称获取会议室ID
     * @param array
     * @return string
     */
    public function getRoomIdByRoomName() {
    	$result = $this->meetingService->getRoomIdByRoomName($this->request->all());
    	return $this->returnResult($result);
    }
    /**
     * 添加会议分类
     * @return boolean
     */
    public function addMeetingSort() {
    	return $this->returnResult($this->meetingService->addMeetingSort($this->request->all()));
    }
    /**
     * 获取会议分类列表
     *@author 李旭
     *
     *@return   [array]
     *
     */
    public function getMeetingSort () {
    	$result = $this->meetingService->getMeetingSort($this->request->all());
    	return $this->returnResult($result);
    }
    /**
     *
     * 根据ID获取分类详情
     */
    public function getMeetingSortDetail($sortId) {
        // $data = $this->returnResult($this->meetingService->getMeetingSortDetail($sortId));
    	return $this->returnResult($this->meetingService->getMeetingSortDetail($sortId));
    }

    /**
     * /
     * @param  [type] $sortId [description]
     * @return [type]         [description]
     */
    public function editMeetingSortDetail($sortId) {
    	$data = $this->request->all();
        $result = $this->meetingService->editMeetingSortDetail($data,$sortId);
        return $this->returnResult($result);
    }
     /**
     * 获取签到的WiFi列表
     * @param string
     * @return array
     */
    public function getSignWifiList() {

    	 return $this->returnResult($this->meetingService->getSignWifiList($this->request->all()));
    }

    public function addSignWifiList()
    {
        return $this->returnResult($this->meetingService->addSignWifiList( $this->request->all()));
    }
    public function editSignWifi($wifiId) {
    	return $this->returnResult($this->meetingService->editSignWifi($wifiId, $this->request->all()));
    }
    public function deleteSignWifi($wifiId)
    {
        return $this->returnResult($this->meetingService->deleteSignWifi($wifiId));
    }
    public function getWifiInfo($wifiId)
    {
        return $this->returnResult($this->meetingService->getWifiInfo($wifiId));
    }
    public function addExternalUser() {
    	return $this->returnResult($this->meetingService->addExternalUser($this->request->all()));
    }
    public function getExternalUser() {
    	return $this->returnResult($this->meetingService->getExternalUser($this->request->all()));
    }
    public function getExternalUserInfo($userId) {
    	return $this->returnResult($this->meetingService->getExternalUserInfo($userId));
    }
    public function getTypeInfo($typeId) {
    	return $this->returnResult($this->meetingService->getTypeInfo($typeId));
    }
    public function editExternalUserInfo($userId) {
    	return $this->returnResult($this->meetingService->editExternalUserInfo($userId, $this->request->all()));
    }
    public function deleteExternalUser($userId) {
    	return $this->returnResult($this->meetingService->deleteExternalUser($userId));
    }
    public function deleteUserType($typeId) {
    	return $this->returnResult($this->meetingService->deleteUserType($typeId));
    }
    public function getMeetingQRCodeInfo($mApplyId) {
    	return $this->meetingService->getMeetingQRCodeInfo($mApplyId, $this->own);

    }
    public function doQRSign($userId, $mApplyId)
    {
        return $this->meetingService->doQRSign($userId, $mApplyId, $this->own);
    }
    public function doExternalSign($userId, $mApplyId)
    {
    	return $this->meetingService->doExternalSign($userId, $mApplyId, $this->own);
    }
    /**
     * /
     * @param  [type] $sortId [description]
     * @return [type]         [description]
     */
    public function deleteMeetingSort ($sortId) {
    	return $this->returnResult($this->meetingService->deleteMeetingSort($sortId));
    }
    /**
     * 外部人员选择是否参加
     */
    public function externalUserAttence($mApplyId)
    {
    	return $this->returnResult($this->meetingService->externalAttenceMeeting($this->request->all(), $mApplyId));
    }
    /**
     * /
     * @return [type] [description]
     */
    public function getPermessionMeetingSort(){
        $data = $this->request->all();
        $result = $this->meetingService->getPermessionMeetingSort($data);
        return $this->returnResult($result);
    }
     /**
     *
     * 添加外部人员分类
     */
    public function addExternalUserType() {
    	return $this->returnResult($this->meetingService->addExternalUserType( $this->request->all()));
    }
    public function getExternalUserType () {
        return $this->returnResult($this->meetingService->getExternalUserType($this->request->all()));
    }
    public function getExternalUserTypeForList () {
    	return $this->returnResult($this->meetingService->getExternalUserTypeForList($this->request->all()));
    }
    public function getTypeById ($param) {
    	return $this->returnResult($this->meetingService->getTypeById($param));
    }
    public function editExternalUserType ($typeId) {
    	return $this->returnResult($this->meetingService->editExternalUserType($typeId, $this->request->all()));
    }
    public function oneKeySignMeeting ($mApplyId) {
    	return $this->returnResult($this->meetingService->oneKeySignMeeting($mApplyId, $this->request->all()));
    }
    public function listSignType() {
        return $this->returnResult($this->meetingService->listSignType($this->request->all()));
    }
    public function listExternalRemindType() {
        return $this->returnResult($this->meetingService->listExternalRemindType($this->request->all()));
    }
    /**
     * 判定是否授权手机短信模块
     * @return [type] [description]
     */
    public function checkPermission() {
        return $this->returnResult($this->meetingService->checkPermission($this->request->all()));
    }
    /**
     * 判断是否授权APP
     * @return [type] [description]
     */
    public function checkMobilePower() {
        return $this->returnResult($this->meetingService->checkMobilePower($this->request->all()));
    }
    public function getAttenceStatus($mApplyId) {
        return $this->returnResult($this->meetingService->getAttenceStatus($mApplyId, $this->request->all()));
    }
    public function getAttenceRemark($mApplyId) {
        return $this->returnResult($this->meetingService->getAttenceRemark($mApplyId, $this->request->all()));
    }
    public function downloadCode($mApplyId) {
        return $this->returnResult($this->meetingService->downloadCode($mApplyId, $this->own));
    }
    // 获取某个月有会议的日期
    public function getMeetingMonthHasDate($date)
    {
        $result = $this->meetingService->getMeetingMonthHasDate($date,$this->own['user_id']);
        return $this->returnResult($result);
    }
    // 获取某个月有会议的日期
    public function getMeetingMonthHasDateJoin($date)
    {
        $result = $this->meetingService->getMeetingMonthHasDateJoin($date,$this->own['user_id']);
        return $this->returnResult($result);
    }
    // 门户获取我审批的会议
    public function getPortalApprovalMeeting($date)
    {
        $result = $this->meetingService->getPortalApprovalMeeting($date,$this->own);
        return $this->returnResult($result);
    }
    // 门户获取我参加的会议
    public function getPortalJoinMeeting($date)
    {
        $result = $this->meetingService->getPortalJoinMeeting($date,$this->own);
        return $this->returnResult($result);
    }
    public function meetingBaseSetting() {
        $result = $this->meetingService->meetingBaseSetting($this->request->all());
        return $this->returnResult($result);
    }
    public function getBaseSetting () {
        $result = $this->meetingService->getBaseSetting($this->request->all());
        return $this->returnResult($result);
    }
    public function getOccupationMeeting()
    {
        $result = $this->meetingService->getOccupationMeeting($this->request->all());
        return $this->returnResult($result);
    }
    public function getRoomsRecordData($roomId)
    {
        $result = $this->meetingService->getRoomsRecordData($roomId);
        return $this->returnResult($result);
    }
}
