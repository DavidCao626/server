<?php
$routeConfig = [
    // 获取会议室设备列表(有设备选择器, 开放)
    ['meeting/meeting-equipment', 'listEquipment'],
    // 会议室占用列表
    ['meeting/get-conflict', 'getOccupationMeeting'],
    ['meeting/base/get', 'getBaseSetting'],
    ['meeting/base/set', 'meetingBaseSetting', 'post', [713]],
    // 查看设备详情
    ['meeting/meeting-equipment/{equipmentId}', 'showEquipment', [705]],
    // 添加设备
    ['meeting/meeting-equipment', 'addEquipment', 'post', [705]],
    // 编辑设备
    ['meeting/meeting-equipment/{equipmentId}', 'editEquipment', 'post', [705]],
    // 删除设备
    ['meeting/meeting-equipment/{equipmentId}', 'deleteEquipment', 'delete', [705]],
    // 会议室列表(下拉数据源用到, 开放)
    ['meeting/meeting-room', 'listRoom'],
    // 添加会议室
    ['meeting/meeting-room', 'addRoom', 'post', [704]],
    // 会议室列表(会议室管理菜单)
    ['meeting/meeting-room/listRoom', 'getlistRooms', [704, 702,703]],
    // 获取会议室是否占用
    ['meeting/meeting-room/get-data/{roomId}', 'getRoomsRecordData'],

    // 查看会议室详情
    ['meeting/meeting-room/{roomId}', 'showRoom', [704, 706]],
    // 编辑会议室
    ['meeting/meeting-room/{roomId}', 'editRoom', 'post', [704]],
    // 删除会议室
    ['meeting/meeting-room/{roomId}', 'deleteRoom', 'delete', [704]],
    // 会议申请(我的会议有调用这个接口, 但是没触发, 暂不屏蔽)
    ['meeting', 'addMeeting', 'post', [701,706]],
    // 二维码下载
    ['meeting/dowm-code/{mApplyId}', 'downloadCode', 'post', [702]],
    // 添加会议记录
    ['meeting/meeting-records', 'addMeetingRecord', 'post', [702]],
    // 会议记录列表
    ['meeting/meeting-records', 'listMeetingRecords', [702]],
    // 删除会议记录
    ['meeting/meeting-records/{recordId}', 'deleteMeetingRecord', 'delete', [702]],
    // 编辑会议记录
    ['meeting/meeting-records-edit/{recordId}', 'editMeetingRecord', 'post', [702]],
    // 获取会议记录详情
    ['meeting/meeting-records/{recordId}', 'getMeetingRecordDetailById', [702]],
    // 编辑会议申请
    ['meeting/{mApplyId}', 'editMeeting', 'post', [702]],
    // 查看会议详情
    ['meeting/own/{mApplyId}', 'showOwnMeeting' , [702,701,706,703]],
    // 查看审批会议详情
    ['meeting/approve/{mApplyId}', 'showApproveMeeting', [703]],
    // 会议审批通过
    ['meeting/{mApplyId}/approve', 'approveMeeting', 'post', [703]],
    // 拒绝会议申请
    ['meeting/{mApplyId}/refuse', 'refuseMeeting', 'post', [703]],
    // 会议开始
    ['meeting/{mApplyId}/start', 'startMeeting', 'post', [702]],
    // 会议结束
    ['meeting/{mApplyId}/end', 'endMeeting', 'post', [702]],
    // 参加会议
    ['meeting/{mApplyId}/attence', 'attenceMeeting', 'post', [702]],
    // 拒绝参加会议
    ['meeting/{mApplyId}/refuseAttence', 'refuseAttenceMeeting', 'post', [702]],
    // 会议签到
    ['meeting/{mApplyId}/sign', 'signMeeting', 'post', [702]],
    // 删除我的会议申请
    ['meeting/own/{mApplyId}', 'deleteOwnMeeting', 'delete', [702]],
    // 删除我审批的会议
    ['meeting/approve/{mApplyId}', 'deleteApproveMeeting', 'delete', [703]],
    // 我的会议列表
    ['meeting/own', 'listOwnMeeting', [702]],
    // 我的审批会议列表
    ['meeting/approve', 'listApproveMeeting', [703]],
    // 获取会议室所有会议列表(没用到
    ['meeting/meeting/apply', 'getMeetingListByRoomId'],
    // 查看会议审批查看时间
    ['meeting/set-meeting-apply/{mApplyId}', 'setMeetingApply', 'put', [703]],
    // // 查看会议申请冲突
    ['meeting/meeting-approve/conflict/emblem', 'getMeetingConflictEmblem', [703]],
    // 查看会议, 是否有详情查看
    ['meeting/meeting-detail-permissions/{mApplyId}', 'getMeetingDetailPermissions', [701,706]],
    // 判断会议申请时是否有会议申请冲突
    ['meeting/get-date-whether-conflict', 'getNewMeetingDateWhetherConflict', [702,701,706]],
    // 获取所有会议列表
    ['meeting/get-all-meeting-list', 'getAllMeetingList', [701, 706]],
    // 会议室使用情况
    ['meeting/get-meeting-room-usage', 'getMeetingRoomUsageTable', [706]],
    // 获取会议室id
    ['meeting/room/get-room-id', 'getRoomIdByRoomName', 'post', [706]],
    // 添加会议室类别
    ['meeting/category/meeting-sort', 'addMeetingSort', 'post', [712]],
    // 会议室类别列表
    ['meeting/category/getSort', 'getMeetingSort', [704,712]],
    // 类别详情
    ['meeting/category/meeting-sort/{sortId}', 'getMeetingSortDetail', [712]],
    // 编辑类别
    ['meeting/category/meeting-sort/{sortId}', 'editMeetingSortDetail', "post", [712]],
    // 删除会议室类别
    ['meeting/category/meeting-sort/{sortId}', 'deleteMeetingSort', "delete", [712]],
    // 此路由用于新建会议,主要是用户获取有权限的分类(没用到)
    ['meeting/category/get-permession-sort', 'getPermessionMeetingSort'],
    // 获取参加人员
    ['meeting/attendence/{mApplyId}','getMeetingMyAttenceUsers'],
    // 编辑wifi
    ['meeting/edit-wifi/{wifiId}','editSignWifi', 'put', [710]],
    // 查看wifi详情
    ['meeting/wifi/{wifiId}','getWifiInfo', 'get', [710]],
    // 删除wifi
    ['meeting/delete-wifi/{wifiId}','deleteSignWifi', 'delete', [710]],
    // 获取wifi列表(系统数据用到, 开放)
    ['meeting/operate/wifi','getSignWifiList'],
    // 添加wifi
    ['meeting/operate/wifi','addSignWifiList','post', [710]],
    // 外部人员列表(选择器用到,开放菜单)
    ['meeting/external/userlist','getExternalUser'],
    // 添加外部人员
    ['meeting/external/user','addExternalUser','post', [711]],
    // 获取外部人员
    ['meeting/external/user/{userId}','getExternalUserInfo','get', [711]],
    // 比那几外部人员
    ['meeting/external/user/{userId}','editExternalUserInfo','put', [711]],
    // 删除外部人员
    ['meeting/external/delete-user/{userId}','deleteExternalUser','delete', [711]],
    // 会议二维码
    ['meeting/sign/qrcodeinfo/{mApplyId}', 'getMeetingQRCodeInfo', 'get', [702]],
    // 二维码签到
    ['meeting/sign/doQRSign/{userId}/{mApplyId}', 'doQRSign', 'put'],
    // 添加外部人员分类
    ['meeting/set/addUserType', 'addExternalUserType', 'post', [711]],
    // 获取外部人员类别(选择器用到, 开放权限)
    ['meeting/set/getUserType', 'getExternalUserType', [711]],
    // 获取外部人员类别列表
    ['meeting/set/getTypeModal', 'getExternalUserTypeForList'],
    // 根据分类ID获取指定的人员
    ['meeting/set/get/user', 'getTypeById', 'get', [711]],
    //类别详情
    ['meeting/set/type-info/{typeId}', 'getTypeInfo', [711]],
    // 编辑外部人员类别
    ['meeting/set/editUserType/{typeId}', 'editExternalUserType', 'put', [711]],
    // 删除外部人员类别
    ['meeting/set/deleteUserType/{typeId}', 'deleteUserType', "delete", [711]],
    // 一键签到
    ['meeting/oneKeySign/{mApplyId}', 'oneKeySignMeeting', "put", [702]],
    // 外部人员签到(这个不能控制菜单权限)
    ['meeting/signIn/{userId}/{mApplyId}', 'doExternalSign', "put"],
    // 签到方式列表
    ['meeting/meeting-sign', 'listSignType', [701, 702, 706]],
    // 外部人员提醒列表
    ['meeting/external-remind', 'listExternalRemindType', [701, 702]],
    // 手机版提醒权限
    ['meeting/check-permission', 'checkPermission', [701, 702, 706]],
    // 查询手机app授权
    ['meeting/check/mobile', 'checkMobilePower', [701, 702, 706]],
    // 获取参会备注
    ['meeting/remark/{mApplyId}', 'getAttenceRemark', [702]],
    // 获取参会状态
    ['meeting/attence/{mApplyId}', 'getAttenceStatus', [702]],
    //获取某月会议的日期(用于门户)
    ['meeting/meeting-month-has-date/{date}', 'getMeetingMonthHasDate', 'get'],
    // 门户获取我审批的会议
    ['meeting/portal-approve/{date}', 'getPortalApprovalMeeting', 'get'],
    // 获取参加的会议用于门户
    ['meeting/meeting-month-has-date-join/{date}', 'getMeetingMonthHasDateJoin', 'get'],
    // 门户获取我参加的会议
    ['meeting/portal-join/{date}', 'getPortalJoinMeeting', 'get'],

];