<?php
$routeConfig = [
    // 获取日程列表
    ['calendar', 'getCalendarList'],
    ['calendar/schedule-view-list', 'getScheduleViewList', 'post'],
    ['calendar/set-read', 'setAttentionRead'],
    ['calendar/get-conflict', 'getConflictCalendar'],
    ['calendar/get-all-calendar-list/{group_id}', 'getGroupAllCalendar', 'get'],
    ['calendar/save-filter/{userId}', 'saveFilter', 'post'],
    ['calendar/save-filter/{userId}', 'getFilter', 'get'],
    // 门户我的日程
    ['calendar/portal/calendarlist', 'getPortalCalendarList'],
    //获取我的日程日历初始日程列表
    ['calendar/calendarlist', 'getInitList'],
    ['calendar/calendar-list-selector', 'getInitListSelector'],
    ['calendar/base/get', 'getBaseSetInfo', [716]],
    ['calendar/base/set', 'setBaseSetInfo', 'post', [716]],
    // 日程默认共享组设置相关路由
    ['calendar/purview', 'addCalendarPurview', 'post', [716]],
    ['calendar/purview-list', 'getCalendarPurview'],
    ['calendar/purview/{id}', 'getCalendarPurviewDetail'],
    ['calendar/purview/{id}', 'deleteCalendarPurview', 'delete'],

    //根据id获取日程
    ['calendar/getone/{calendarId}', 'getCalendarDetail', 'post', [27,141]],
    //根据id获取重复日程
    // ['calendar/repeat/getone/{calendar_id}', 'getRepeatCalendarOne', 'post', [27]],
    //添加日程
    ['calendar', 'addCalendar', 'post',[141]],
    //删除日程
    ['calendar/delete', 'deleteCalendar', 'post', [27,141]],
    // 日程完成
    ['calendar/complete', 'completeCalendar', 'post', [27,141]],
    //删除日程及所有子日程
    ['calendar/delete_all', 'deleteAllRepeatCalendar', 'post',[27,141]],
    //删除日程
    ['calendar/deletes', 'deleteCalendars', 'post', [27]],
    //删除日程-重复日程(没有用到)
    //['calendar/repeat/delete', 'deleteRepeatCalendar', 'post', [27]],
    //编辑日程
    ['calendar/edit_all/{calendar_id}', 'editAllCalendar', 'post',[27,141]],
    //获取我的关注
    ['calendar/calendar-my-attention', 'getMyAttention', [141, 27, 148, 140]],
    ['calendar/attention/get-attention-tree/{userId}', 'getAttentionTree'],
    //获取我的下属(前端有配置下属列表, 页面没调用, 先不注释)
    ['calendar/calendar-my-subordinate', 'getMySubordinate', [141, 27, 148, 140]],
    //获取日程列表
    ['calendar/repeat/{calendar_id}', 'getRepeatCalendarList', [27]],
    //查询日程关注人
    ['calendar/calendar-attention', 'getIndexDiaryAttention', [140, 27, 148]],
    //添加日程关注人
    ['calendar/calendar-attention', 'createDiaryAttention', 'post', [140, 27, 148]],
    //更新日程关注人
    ['calendar/calendar-attention/{attentionIds}', 'editDiaryAttention', 'put', [140, 27, 148]],
    //删除日程关注人(取消关注操作, 两个菜单都有用到)
    ['calendar/calendar-attention/{attentionIds}', 'deleteDiaryAttention', 'delete', [140, 27, 148]],
    ['calendar/calendar-my-attention/{attentionIds}', 'deleteAttentionMy', 'delete', [140], 27, 148],
    // 取消日程关注(用于中间列)
    ['calendar/calendar-cancel/{attentionUserId}', 'cancelCalendarAttention', 'delete', [141]],
    //获取某月日程的日期(用于门户)
    ['calendar/calendar-month-has-date/{date}', 'getCalendarMonthHasDate', 'get'],
    //获取某天日程(用于门户)
    ['calendar/calendar-one-day/{date}', 'getOneDayCalendar', 'get'],
    //移除重复日程
    ['calendar/calendar-repeat-remove', 'removeRepeatCalendar', 'post', [27,141]],
    ['calendar/attention/group','addAttentionGroup','POST'],
    // 关注分组，获取自己的日程 关注分组列表
    ['calendar/attention/group','getAttentionGroupList'],
    ['calendar/attention/group-tree/{group_id}','getAttentionGroupUserList'],
    // 关注分组，获取自己的日程 关注分组信息
    ['calendar/attention/group/{group_id}','getAttentionGroupInfo',[140]],
    // 关注分组，保存关注分组信息
    ['calendar/attention/group/{group_id}','saveAttentionGroupInfo','put',[140]],
    // 关注分组，删除关注分组
    ['calendar/attention/group/{group_id}','deleteAttentionGroup','delete',[140]],
    // 关注分组，关注分组增加用户
    ['calendar/attention/group/users','addAttentionGroupUser','post',[140]],
    // 关注分组，获取用户的分组信息
    ['calendar/attention/groups','getUsersAttentionGroupsInfo','post',[140]],
    //编辑日程
    ['calendar/{calendar_id}', 'editCalendar', 'post', [27, 141]],
    ['calendar/setting/join-module', 'getJoinModule', 'get'],
    ['calendar/setting/get-join-module', 'getJoinModuleConfig', 'get'],
    ['calendar/setting/join-module', 'setJoinModuleConfig', 'post'],
    ['calendar/setting/calendar-type', 'getCalendarType', 'get'],
    ['calendar/setting/calendar-type', 'addCalendarType', 'post'],
    ['calendar/setting/calendar-type/sort', 'setCalendarTypeSort', 'post'],
    ['calendar/setting/calendar-type/{typeId}', 'setCalendarType', 'post'],
    ['calendar/setting/calendar-type/{typeId}', 'deleteCalendarType', 'delete'],
    ['calendar/setting/calendar-type/default/{typeId}', 'setDefaultCalendarType', 'post'],
    ['calendar/setting/calendar-type/default', 'getDefaultCalendarType', 'get'],
    ['calendar/setting/defalue-value/{typeId}', 'getDefalutValueByTypeId', 'get'],
    ['calendar/setting/defalue-value/{typeId}', 'setDefalutValueByTypeId', 'post'],
    
    
];