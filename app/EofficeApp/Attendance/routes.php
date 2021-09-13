<?php
$routeConfig = [
    ['attendance/shift', 'shiftList', [46]],//获取班次列表
    ['attendance/shift', 'addShift', 'post', [46]],//新建班次
    ['attendance/shift/copy', 'copyShift', 'post', [46]],//复制班次
    ['attendance/shift/{shiftId}', 'deleteShift', 'delete', [46]],//删除班次
    ['attendance/shift/{shiftId}', 'editShift', 'post', [46]],//编辑班次
    ['attendance/shift/{shiftId}', 'shiftDetail', [51, 46, 253]],//获取班次详情
    ['attendance/shift-time', 'getUserShiftTime'],//获取用户某天的打卡时间段
    ['attendance/scheduling', 'schedulingList'],//获取班组列表
    ['attendance/scheduling/all', 'getAllScheduling', [46]],//获取班组列表
    ['attendance/scheduling', 'addScheduling', 'post', [46]],//新建班组
    ['attendance/scheduling/import', 'importScheduling', 'post', [46]],//新建班组
    ['attendance/scheduling/sync', 'syncHoliday', 'post', [46]],//同步节假日
    ['attendance/holiday/import', 'importHoliday', 'post', [46]], // 导入节假日
    ['attendance/scheduling/{schedulingId}/copy', 'copyScheduling', [46]],//删除班组
    ['attendance/scheduling/{schedulingId}', 'deleteScheduling', 'delete', [46]],//删除班组
    ['attendance/scheduling/{schedulingId}', 'editScheduling', 'post', [46]],//编辑班组
    ['attendance/scheduling/{schedulingId}', 'schedulingDetail', [46]],//获取班组详情
    ['attendance/scheduling/date/{year}/{month}/user/{userId}', 'getUserSchedulingDate', [253]],//获取用户某个月的排班
    ['attendance/mine-scheduling/date/{year}/{month}', 'getMySchedulingDate', [51]],//获取用户某个月的排班
    ['attendance/mobile-mine-scheduling/date/{year}/{month}', 'getMobileMySchedulingDate', [51]],//获取用户某个月的排班
    ['attendance/scheduling/rest/{year}/{month}/user/{userId}', 'getUserSchedulingRest'],//获取用户某个月的节假日
    ['attendance/mine-scheduling/rest/{year}/{month}', 'getMySchedulingRest', [49]],//获取用户某个月的节假日
    ['attendance/scheduling/{schedulingId}/quick', 'quickEditScheduling', 'post', [46]],//编辑班组
    ['attendance/sign-in', 'signIn', 'post'],//考勤签到
    ['attendance/sign-out', 'signOut', 'post'],//考勤签退
    ['attendance/auto-sign-in', 'autoSignIn', 'get'],//考勤签退
    ['attendance/external-sign-in', 'externalSignIn', 'post'],
    ['attendance/external-sign-out', 'externalSignOut', 'post'],
    ['attendance/report-location', 'reportLocation', 'post'],//上报位置
    ['attendance/mobile/point', 'addMobilePoints', 'post', [48]],//新建考勤点
    ['attendance/mobile/point/{pointId}', 'editMobilePoints', 'post', [48]],//编辑考勤点
    ['attendance/mobile/point', 'mobilePointsList', [48]],//获取考勤点列表
    ['attendance/mobile/point/{pointId}', 'mobilePointsDetail', [48]],//获取考勤点详情
    ['attendance/mobile/point/{pointId}', 'deleteMobilePoints', 'delete', [48]],//删除考勤点
    ['attendance/mobile/no-point/set', 'setMobileNoPoint', 'post', [48]],//非定点考勤设置
    ['attendance/mobile/no-point/get', 'getMobileNoPoint', [48]],//获取非定点考勤信息
    ['attendance/mobile/out-attend-priv', 'getOutAttendancePriv'],//获取用户的外勤打卡权限
    ['attendance/mobile/base/set', 'setMobileBase', 'post', [48]],//移动考勤基础设置
    ['attendance/mobile/base/get', 'getMobileBase', [48]],//获取基本设置信息
    //pc端打卡设置
    ['attendance/pc/sign/set', 'setPcSign', 'post', [48]],
    ['attendance/pc/sign/get', 'getPcSign', 'get', [48]],
    ['attendance/scheduling/date/{year}/{schedulingId}', 'getSchedulingDate', [46]],
    ['attendance/scheduling/date/{year}/{schedulingId}', 'setSchedulingDate', 'post', [46]],
    ['attendance/stat/{userId}', 'oneAttendStat'],// 无效api
    ['attendance/mine-stat', 'myAttendStat', [49]],
    ['attendance/stat', 'moreAttendStat', [247]],
    ['attendance/classified-stat', 'abnormalUserStat', [70]],
    ['attendance/classified-stat/{type}', 'abnormalDetailStat', 'post',[70]],
    ['attendance/stat-no-header', 'moreAttendStatNoHeader', [247]],
    ['attendance/records', 'moreAttendRecords', [52]],
    ['attendance/records/calibration', 'getCalibrationRecords', [248]],
    ['attendance/records/{userId}', 'oneAttendRecords'],// 无效api
    ['attendance/mine-records', 'myAttendRecords', [49]],
    ['attendance/mine-leave-records', 'getMyLeaveRecords'],//我的最的请假记录，用户销假的选择器
    ['attendance/info/{userId}/{signDate}', 'getAttendInfo'],//（全局api）
    ['attendance/advanced-info/{userId}/{signDate}', 'getAdvancedAttendInfo'],//（全局api）
    ['attendance/record/calibration/{recordId}/apply', 'applyCalibration', 'post', [49, 248]],
    ['attendance/record/calibration/{recordId}/returned', 'returnedCalibration', 'post', [248]],
    ['attendance/record/calibration/{recordId}/approve', 'approveCalibration', 'post', [248]],
    ['attendance/record/calibration/check-all', 'approveAllApply', [248]],
    ['attendance/record/calibration/{recordId}', 'getCalibrationInfo', [49, 248]],
    ['attendance/flowoutsend/{type}', 'getFlowOutsendList', [50, 53,49]],
    ['attendance/mobile/records', 'getMobileRecords', [54, 68, 49]],
    ['attendance/machine/access', 'attendMachineAccess', 'post', [61]],//考勤机接入
    ['attendance/detail/records/{year}/{month}/{userId}/{type}', 'getAttendDetailRecords', [247, 49]],//获取考勤相关信息详情
    ['attendance/detail/mine-records/{year}/{month}/{type}', 'getMyAttendDetailRecords'],//获取考勤相关信息详情
    ['attendance/leave-out/days/{userId}', 'getLeaveOrOutDays'], // 全局api，表单用到
    ['attendance/overtime/days/{userId}', 'getOvertimeDays'],// 全局api，表单用到
    ['attendance/wifi', 'getWifiList', [48]],
    ['attendance/wifi', 'addWifi', 'post', [48]],
    ['attendance/wifi/{wifiId}', 'editWifi', 'post', [48]],
    ['attendance/wifi/{wifiId}', 'getWifiDetail', 'get', [48]],
    ['attendance/wifi/{wifiId}', 'deleteWifiInfo', 'delete', [48]],
    ['attendance/attendance-config', 'saveMachineConfig', 'post', [60]],
    ['attendance/attendance-config/{id}', 'getMachineConfigById', 'get', [60]],
    ['attendance/attendance-config', 'getMachineConfig', 'get', [60]],
    ['attendance/attendance-config', 'deleteMachineConfig', 'delete', [60]],
    ['attendance/use-attendance-config/{id}', 'useMachineConfigById', 'get', [60]], // 设置考勤机配置生效
    ['attendance/swtich-attendance-configType/{type}', 'switchAttendanceType', 'get', [60]], // 设置考勤机单多生效切换
    ['attendance/get-multiIs-auto', 'getMultiIsAuto', 'get', [60]], // 获取多考勤的自动同步配置
    ['attendance/set-multiIs-auto', 'setMultiIsAuto', 'post', [60]], // 设置多考勤的自动同步配置
    ['attendance/attendance-machine-case', 'getMachineCase', 'get', [60]], // 获取考勤机配置案例列表
    ['attendance/synchronous-attendance', 'synchronousAttendance', 'get', [60]],
    ['attendance/user-list', 'getUserList', 'get', [60]],
    ['attendance/match-user', 'matchUser', 'post', [60]],
    ['attendance/match-user-field', 'getMatchUserField', 'get', [60]], // 获取同步用户所用字段
    ['attendance/match-user-field', 'setMatchUserField', 'put', [60]], // 设置同步用户所用字段
    ['attendance/import/log', 'addImportLog', 'post', [60]], //添加考勤导入日志
    ['attendance/import/log', 'getImportLogs', 'get', [60]], //获取考勤导入日志
    ['attendance/sign/records/{userId}/{year}/{month}', 'getOneMonthSignRecords', 'get'],
    ['attendance/sign/records/{signDate}/{userId}', 'getOneUserOneDaySignRecords', 'get', [52]],
    ['attendance/setting/scheme/list', 'getSchemeList', 'get', [46]], //获取节假日方案类表
    ['attendance/setting/scheme/add', 'addScheme', 'post', [46]], //新增节假日方案
    ['attendance/setting/scheme/{schemeId}', 'deleteScheme', 'delete', [46]], //删除节假日方案
    ['attendance/setting/scheme/{schemeId}/quick', 'quickEditSchemeStatus', 'post', [46]], //快速编辑假期方案状态
    ['attendance/setting/scheme/{schemeId}', 'getOneSchemeDetail', 'get', [46]], //获取某个方案详情
    ['attendance/setting/scheme/{schemeId}', 'editScheme', 'post', [46]], //编辑节假日方案
    ['attendance/setting/scheme/rest/used', 'getUsedSchemeDetail', 'get', [46]], //获取正在使用的节假日方案的所有节假日信息
    ['attendance/setting/scheme/rest/{schemeId}', 'getSchemeDetailByID', 'get', [46]], //获取某个节假日方案下的所有节假日
    ['attendance/setting/scheme/rest/{schemeId}', 'editSchemeRest', 'post', [46]], //编辑某个节假日方案下的节假日
    // 考勤权限设置
    ['attendance/setting/purview-group', 'getPurviewGroupList', [47]],
    ['attendance/setting/purview-group', 'addPurviewGroup', 'post', [47]],
    ['attendance/setting/purview-group/{groupId}', 'editPurviewGroup', 'post', [47]],
    ['attendance/setting/purview-group/{groupId}', 'getPurviewGroupDetail', [47]],
    ['attendance/setting/purview-group/{groupId}', 'deletePurviewGroup', 'delete', [47]],
    ['attendance/setting/purview/{menuId}', 'getPurviewUser', [68, 53, 248]],
    ['attendance/setting/purview/export/{menuId}', 'getExportPurview', [52, 69, 247]],
    //加班设置相关
    ['attendance/setting/overtime/rule', 'getOvertimeRuleList', 'get', [71]],
    ['attendance/setting/overtime/rule', 'addOvertimeRule', 'post', [71]],
    ['attendance/setting/overtime/rule/quick', 'editOvertimeRuleOpenStatus', 'put', [71]],
    ['attendance/setting/overtime/rule/{ruleId}', 'getOvertimeRuleDetail', 'get', [71]],
    ['attendance/setting/overtime/rule/{ruleId}', 'updateOvertimeRule', 'put', [71]],
    ['attendance/setting/overtime/rule/{ruleId}', 'deleteOvertimeRule', 'delete', [71]],
    // 常用设置
    ['attendance/setting/common/{ruleType}', 'editCommonSetting', 'post', [72]],
    ['attendance/setting/common/{ruleType}', 'getCommonSetting', 'get', [72, 49]],
    //考勤日志
    ['attendance/original-records', 'getAttendOriginalRecords', 'get', [50, 53,49]],
    ['attendance/log/{type}', 'getAttendLogs', 'get', [254]],
    ['attendance/my-log/{type}', 'getMyAttendLogs', 'get', [49]],
    //获取请假记录,表单用到
    ['attendance/user-leave-records', 'getUserLeaveRecords'],
    ['attendance/leave/{leaveId}', 'getLeaveRecordsDetail'],
    ['attendance/overtime/to', 'getOvertimeTo'],
    ['attendance/repair-type', 'getRepairType'],
    ['attendance/sign-out-btn/hide', 'isHideSignOutButton']
];