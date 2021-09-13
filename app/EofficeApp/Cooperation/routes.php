<?php

/*
  |--------------------------------------------------------------------------
  | Application Routes
  |--------------------------------------------------------------------------
  |
  | Here is where you can register all of the routes for an application.
  | It is a breeze. Simply tell Lumen the URIs it should respond to
  | and give it the Closure to call when that URI is requested.
  |
 */

$routeConfig = [
    // 获取协作分类的列表
    ['cooperation/sort', 'getCooperationSort', [364, 365]],
    // 新建协作分类
    ['cooperation/sort', 'createCooperationSort', 'post', [365]],
    // 编辑协作分类
    ['cooperation/sort/{sortId}', 'editCooperationSort', 'post', [365]],
    // 删除协作分类
    ['cooperation/sort/{sortId}', 'deleteCooperationSort', 'delete', [365]],
    // 获取协作分类详情
    ['cooperation/sort/{sortId}', 'getCooperationSortDetail', [365]],
    // 获取协作主题列表
    ['cooperation/subject', 'getCooperationSubject',[363]],
    // 获取有权限的【协作主题列表】所属的【协作类别列表】【这个路由，用在新建协作页面，在有权限的类别下新建协作】
    ['cooperation/cooperation-sort-permission-relation-subject', 'getPermissionSubjectRelationSortList', [363]],
    // 新建协作主题
    ['cooperation/subject', 'createCooperationSubject', 'post', [364]],
    // 编辑协作主题
    ['cooperation/subject/{subjectId}', 'editCooperationSubject', 'post', [364]],
    // 删除协作主题
    ['cooperation/subject/{subjectId}', 'deleteCooperationSubject', 'delete', [363]],
    // 获取协作主题详情
    ['cooperation/subject/{subjectId}', 'getCooperationSubjectDetail',[363]],
    // 获取有权限的协作类别列表【这个路由，用在新建协作页面，在有权限的类别下新建协作】
    ['cooperation/cooperation-sort-permission', 'getPermissionCooperationSortList', [364]],
    // 更新用户最后查看此协作主题的时间
    ['cooperation/subject/{subjectId}/subject-view-time', 'updateCooperationSubjectViewTime', 'post', [363]],
    // 将某条协作设为关注/取消关注
    ['cooperation/subject/{subjectId}/follow', 'followCooperationSubject', 'post', [363]],
    // 获取协作回复列表--list
    ['cooperation/cooperation-revert/subject/{subjectId}', 'getCooperationRevertAll',[363]],
    // 获取协作某条回复详情
    ['cooperation/cooperation-revert/{revertId}', 'getCooperationRevertDeatil', [363]],
    // 新建一级回复--add
    ['cooperation/cooperation-revert', 'createCooperationRevert', 'post', [363]],
    // 编辑一级回复
    ['cooperation/cooperation-revert/{revertId}', 'editCooperationRevertFirst', 'put', [363]],
    // 删除一级回复
    ['cooperation/cooperation-revert/{revertId}', 'deleteCooperationRevertFirst', 'delete', [363]],
    ['cooperation/cooperation-subject/get-manage/{revertId}', 'getCooperationManage', [363, 364]],
    // 检查是否有权限查看详情
    ['cooperation/cooperation-subject/check/{subjectId}', 'checkCooperationPermission'],
    /*
    // 置顶
    ['cooperation-revert/stick/{revert_id}', 'createCooperationRevertStick', 'post'],
    // 取消置顶
    ['cooperation-revert/unstick/{revert_id}', 'createCooperationRevertUnstick', 'post'],
    // 获取相关文档列表
    ['cooperation-subject-document', 'getCooperationAboutDocument'],
    // 获取协作主题的某条回复的相关文档列表
    ['cooperation-subject-revert-document', 'getCooperationRevertAboutDocument'],
    // 获取相关附件列表
    ['cooperation-subject-attachment', 'getCooperationAboutAttachment'],
    // 获取协作主题的某条回复的相关附件列表
    ['cooperation-subject-revert-attachment', 'getCooperationRevertAboutAttachment'],
    */
];