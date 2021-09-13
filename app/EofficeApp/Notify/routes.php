<?php
//    131: 列表
//    234: 审核
//    236: 分类
//    239: 新建
$routeConfig = [
//    ['notify/canCheck', 'canCheck', [131, 239]],
    //审核列表
    ['notify/verify-notify', 'listVerifyNotify', [234]],
    //审核详情
    ['notify/verify-notify/{notifyId}', 'showVerifyNotify', [234]],
    //批准
    ['notify/verify-notify/{notifyId}/approve', 'approveNotify', [234]],
    //拒绝
    ['notify/verify-notify/{notifyId}/refuse', 'refuseNotify', [234]],
    // ['notify', 'listNotify',[131,236]],
    //公告列表
    ['notify', 'listNotify',[131]],
    //添加公告
    ['notify', 'addNotify', 'post', [239]],
    //手动提醒未读人员
    ['notify/remind-one-unread', 'remindOneUnreader', 'post', [131]],
    //类别详情
    ['notify/notify-type/{notifyTypeId}', 'showNotifyType', [236]],
    //编辑类别
    ['notify/notify-type/{notifyTypeId}', 'editNotifyType', 'post', [236]],
    //删除类别
    ['notify/notify-type/{notifyTypeId}', 'deleteNotifyType', 'delete', [236]],
    //类别列表
    ['notify/type', 'listNotifyType', [131, 239, 236]],
    //添加类别
    ['notify/type', 'addNotifyType', 'post', [236]],
    //选择器用类别
    ['notify/type-for-select', 'listNotifyTypeForSelect', [131]],
    //登录打开列表
    ['notify/after-login/list', 'getAfterLoginOpenList', [131]],
    ['notify/after-login/{notifyId}', 'getAfterLoginDetail', [131]],
    ['notify/after-login/read/{notifyId}', 'commitRead', 'post', [131]],

    //统计
    ['notify/statistics', 'countMyNotify'],
    //编辑公告
    ['notify/{notifyId}', 'editNotify', 'post', [131]],
    //公告详情
    ['notify/{notifyId}', 'showNotify', [131]],
    //删除公告
    ['notify/{notifyId}', 'deleteNotify', 'delete', [131]],
    //立即生效
    ['notify/{notifyId}/imediate', 'imediateNotify', [131]],
    //立即终止
    ['notify/{notifyId}/end', 'endNotify', [131]],
    //变更类别
    ['notify/{notifyId}/modifyType', 'modifyType', 'post', [131]],
    // 催促审核
    ['notify/{notifyId}/urge-review', 'urgeReview', [131]],
    //查阅情况
	['notify/readers/{notifyId}', 'showReaders', [131]],
    //公告查看范围
    ['notify/range/{notifyId}', 'notifyReadRange', [131]],
    //查阅情况分页
    ['notify/viewers/{notifyId}', 'showReadersBySign', [131]],
    //查阅情况统计
    ['notify/readers-count/{notifyId}', 'getReadersCount', [131]],
    //报表
	['notify/report/config/group-analyze', 'getGroupAnalyze', [521, 522, 523]],
    //手动提醒未读人员
    ['notify/remind-unread/{notifyId}','remindUnreaders', 'post', [131]],
    //能否提醒
    ['notify/{notifyId}/can-remind','canRemind', [131]],
	// 置顶
	['notify/{notifyId}/top', 'top', [131]],
	// 取消置顶
    ['notify/{notifyId}/cancel-top', 'cancelTop', [131]],
    //撤回
    ['notify/{notifyId}/cancel', 'withdraw'],

    //评论
    ['notify/{notifyId}/comments', 'commentList', [131]],
    //添加评论
    ['notify/{notifyId}/comments/add', 'addComment', 'post', [131]],
    //评论详情
    ['notify/{notifyId}/comments/{commentId}', 'getCommentDetail', [131]],
    //获取评论子评论
    ['notify/comments/{commentId}/children', 'getChildrenComments', [131]],
    //删除评论
    ['notify/comments/{commentId}', 'deleteComment', 'delete', [131]],
    //编辑评论
    ['notify/comments/{commentId}', 'editComment', 'put', [131]],

    ['notify/setting/expired', 'getExpiredVisibleSettings'],
    ['notify/setting/expired', 'setExpiredVisibleSettings', 'post', [222]],
    ['notify/setting/check-expired', 'checkCanReadExpiredNotify', [131]],

];
