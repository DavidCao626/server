<?php

$routeConfig = [
    //141:我的日程, 531:我的任务, 532:任务报表, 534:下属任务, 535:任务分析
    //任务转日程 需要的两个方法
//    ['task/task-schedule', 'taskScheduleList'],//应该已废弃
    ['task/task-schedule/{taskId}', 'getTaskSchedule', [141]],
    // 门户，获取我的任务list
    ['task/task-portal', "taskPortal", [531]],
    //任务列表处修改
    ['task/task-mine-class', 'taskMineClass', [531]],
    ['task/task-mine-class/{classId}', 'taskMineClassTaskList', [531]],
    //创建任务
    ['task', 'createTask', 'post', [531, 532, 534, 535]],
    //标注任务状态,完成/未完成
    ['task/complete', 'completeTask', 'put', [531, 532, 534, 535]],
    //锁定/解锁任务
    ['task/lock', 'lockTask', 'put', [531, 532, 534, 535]],
    //关注/取消关注任务
    ['task/follow', 'followTask', 'post', [531, 532, 534, 535]],
    //还原任务
//    ['task/restore', 'restoreTask', 'put'],//应该已废弃
    //编辑任务
    ['task/{taskId}', 'modifyTask', 'put', [531, 532, 534, 535]],
    //编辑任务的负责人
    ['task/manager/{taskId}', 'modifyTaskManager', 'put', [531, 532, 534, 535]],
    //添加参与人
    ['task/join', 'createJoiner', 'post', [531, 532, 534, 535]],
    //添加共享人
    ['task/shared', 'createShared', 'post', [531, 532, 534, 535]],
    //添加反馈
    ['task/feedback', 'createTaskFeedback', 'post', [531, 532, 534, 535]],
    //编辑反馈
    ['task/feedback/{feedbackId}', 'modifyTaskFeedback', 'put', [531, 532, 534, 535]],
    //删除反馈
    ['task/feedback/{feedbackId}', 'deleteTaskFeedback', 'delete', [531, 532, 534, 535]],
    //催办任务
    ['task/press', 'pressTask', 'post', [531, 532, 534, 535]],
    //任务分析列表
    ['task/report', 'getTaskReport', [532, 535]],
    //获取任务报表的用户的任务列表
    ['task/report/user', 'getOneUserTask', [532]],
    // 任务分析-任务报表-某个用户的任务list
    ['task/report/simple-user', 'getSimpleOneUserTask', [532, 535]],
    //获取用户任务趋势数据
    ['task/trend', 'getUserTaskTrend', [535]],
    //获取用户下属的任务列表
    ['task/subordinate', 'getSubordinateTaskList', [534]],
    //获取任务数据
    ['task/{taskId}', 'getTaskInfo', [531, 532, 534, 535, 536]],
    //获取任务关联用户
//    ['task/user/{taskId}', 'getTaskRelationUser'],//应该已废弃
    //获取某条任务反馈数据
//    ['task/feedback/{feedback_id}', 'getFeedbackInfo'],//应该已废弃
    //获取任务反馈
    ['task/feedback/list/{taskId}', 'getTaskFeedback', [531, 532, 534, 535, 536]],
    //获取任务列表
    ['task', 'getTaskList', [531]],
    //删除任务
    ['task/{taskId}', 'deleteTask', 'delete', [531, 532, 534, 535]],
    //获取已被删除的任务列表
    ['task/recycle/list', 'getDeletedTask', 'get', [536]],
    ['task/recycle/delete', 'forceDelete', 'put', [536]],
    ['task/recycle/recovery', 'recovery', 'put', [536]],
     //获取子任务id
    ['task/son/{taskId}', 'getSonTask', [531]],
    //获取回收站日志
//    ['task/recover/log', 'getTaskRecoverLog'],//应该已废弃
    //回收站日志自动查询
//    ['task/recover/search', 'taskRecoverSearch'],//应该已废弃
    //获取任务日志
    ['task/log/{taskId}', 'getTaskLog', [531, 532, 534, 535, 536]],
    //获取用户下级,用于web-中间列、mobile-下属任务-高级查询-我的下属下拉框
    ['task/user/subordinate/{userId}', 'getUserSubordinate', [534]],
    //前端查询条件传入测试
    ['task/mobile', 'mobileCreateTask', 'post', [531]],
    // 手机版，编辑任务
    ['task/mobile/{taskId}', 'mobileEditTask', 'put', [531, 534, 532]],
    //前端查询条件传入测试
//    ['task/search/test', 'searchTest'],//应该已废弃
    /**
     * 新版API路由
     */
    //创建任务分类(新建任务列表)
    ['task/class', 'createTaskClass', 'post', [531]],
    //删除任务分类(删除任务列表)
    ['task/class/{classId}', 'deleteTaskClass', 'delete', [531]],
    //获取我的任务列表
//    ['task/class/mine', 'getMyTaskList', [531]],//应该已废弃
    //分类列表排序
    ['task/class/sort', 'modifyClassSort', 'put', [531]],
    //更新分类列表中任务关联
    ['task/class/relation', 'modifyTaskClassRelation', 'post', [531]],
    //编辑任务分类
    ['task/class/{classId}', 'modifyTaskClass', 'put', [531]],
    //快速编辑任务
    ['task/quick-modify-task-detail/{taskId}', 'quickModifyTaskDetail', 'put', [531, 532, 534, 535]],
    //验证权限
//    ['task/task-auth/{taskId}', 'taskAuth'],//应该已废弃
    //评分
    ['task/set-task-grade/{taskId}', 'setTaskGrade', 'PUT', [534]],
    //获取提醒信息
    ['task/task-reminds/{taskId}', 'getTaskReminds', [531, 532, 534, 535]],
    //设置提醒信息
    ['task/task-remind-set/{taskId}', 'remindSet', "post", [531, 532, 534, 535]],
    //获取任务提醒设置
    ['task/get-remind-set/{taskId}', "getRemindSet", [531, 532, 534, 535]],
    ['task/my-task/list', "getMyTask"], // 系统数据选择器-我的任务接口
    ['task/my-task/type', "getMyTaskType"], // 系统数据选择器-我的任务分类接口
];
