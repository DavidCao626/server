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
    // 菜单id 微博主页28  微博设置190 微博报表182 关注管理183 我的微博184  工作计划185
    // 获取我的关注
    ['diary/my-attention/{userId}', 'getMyAttention',[28]],
    // 获取访问记录
    ['diary/visit-record/{userId}', 'getVisitRecord',[28]],
    // 查询微博日志关注人
    ['diary/attention', 'getIndexDiaryAttention',[28,183,184]],
    // 添加微博日志关注人
    ['diary/attention', 'createDiaryAttention', 'post',[28,183]],
    // 更新微博日志关注人
    ['diary/attention/{attentionIds}', 'editDiaryAttention', 'put',[183]],
    // 删除微博日志关注人 --验证本人删除权限、删除时间
    ['diary/attention/{attentionIds}', 'deleteDiaryAttention', 'delete',[28,183]],
    // 查询微博日志浏览记录 - 修改api
    ['diary/visits', 'getIndexDiaryVisits',[184]],
    // 添加微博日志浏览记录 --前端service定义，但未使用
    ['diary/visits', 'createDiaryVisits', 'post'],
    // 获取微博日志列表
    ['diary/diarys', 'getIndexDiarys',[28,184,185]],
    // 查询我的微博日志 --前端未使用
    //['diary/mine-diarys', 'getMineDiarys'],
    // 添加微博日志 --微博便签用到,微博便签功能已删
    //['diary/diarys', 'createDiarys', 'post'],
    // 查询微博日志详情 --验证微博获取权限
    ['diary/diarys/{diaryId}', 'getDiarys',[184]],
    // 编辑微博日志 --微博便签中使用 废弃
    //['diary/diarys/{id}', 'editDiarys', 'put'],
    // 删除微博日志 --验证是否是自己的微博
    ['diary/diarys/{diaryId}', 'deleteDiarys', 'delete',[184]],
    // 查询微博日志报表
    ['diary/diary-reports', 'getIndexDiaryReports',[182]],
    // 查询微博日志回复 --手机端评论列表页使用，该页面已删除
    //['diary/{diary_id}/replys', 'getIndexDiaryReplys'],
    // 添加微博日志回复
    ['diary/{diaryId}/replys', 'createDiaryReplys', 'post',[28,184]],
    // 删除微博日志回复 --验证是否是微博、评论的发起人
    ['diary/{diaryId}/replys/{replyId}', 'deleteDiaryReplys', 'delete',[28,184]],
    // 添加微博便签 --该功能前端已删
    //['diary/diary-memo', 'createDiaryMemo', 'post'],
    // 查询微博便签详情 --该功能前端已删
    // ['diary/diary-memo/{user_id}', 'getDiaryMemo'],
    // 编辑微博便签 --该功能前端已删
    //['diary/diary-memo/{user_id}', 'editDiaryMemo', 'put'],
    // 计划模板设置，获取计划类型
    ['diary/diary-set/template-type','getDiaryTemplateType'],
    // 模板设置，获取模板设置信息
    ['diary/diary-template-set','getDiaryTemplateSetList',[190]],
    // 模板设置，保存模板设置信息
    ['diary/diary-template-set','saveDiaryTemplateSet','post',[190]],
    // 模板设置，获取用户模式下的模板设置信息
    ['diary/diary-template-set/user-modal','getUserModalDiaryTemplateSetList',[190]],
    // 模板设置，保存用户模式下的模板设置信息
    ['diary/diary-template-set/user-modal','saveUserModalDiaryTemplateSet','post',[190]],
    // 工作计划，获取某条工作计划
    ['diary/diary-plan','getUserDiaryPlan',[185]],
    // 工作计划，保存工作计划
    ['diary/diary-plan','saveUserDiaryPlan','post',[184,185]],
    // 工作计划，获取某个用户的计划模板
    ['diary/diary-plan/user-template','getUserDiaryPlanTemplate',[184,185]],
    // 我的微博、工作计划，获取模板信息
    ['diary/diary-plan/templateContent','getTemplateContent',[184,185]],
    // 微博点赞
    ['diary/diary-like','getDiaryLike','post',[28,184]],
    // 获取客户联系记录
    ['diary/getcontactrecord/{recordId}/{diaryDate}', 'getContactRecord',[28,184]],
    // 获取系统安全选项
    ['diary/permission', 'getPermission',[28,182,185,190]],
	// 微博设置，编辑系统安全选项
	['diary/permission', 'modifyPermission', 'put',[190]],
    // 关注分组，新增微博关注分组
    ['diary/attention/group','addAttentionGroup','POST',[183]],
    // 关注分组，获取自己的微博关注分组列表
    ['diary/attention/group','getAttentionGroupList',[28,183]],
    // 关注分组，获取自己的微博关注分组信息
    ['diary/attention/group/{groupId}','getAttentionGroupInfo',[183]],
    // 关注分组，保存关注分组信息
    ['diary/attention/group/{groupId}','saveAttentionGroupInfo','put',[183]],
    // 关注分组，删除关注分组
    ['diary/attention/group/{groupId}','deleteAttentionGroup','delete',[183]],
    // 关注分组，关注分组增加用户
    ['diary/attention/group/users','addAttentionGroupUser','post',[183]],
    // 关注分组，获取用户的分组信息
    ['diary/attention/groups','getUsersAttentionGroupsInfo','get',[183]],
    //微博权限保存
    ['diary/purview','saveDiaryPurview','post',[190]],
    //微博权限获取详情
    ['diary/purview/detail/{groupId}','getDiaryPurviewDetail','get',[190]],
     //微博权限列表
     ['diary/purview/lists','getDiaryPurviewLists','get',[190]],
     //微博权限列表删除
     ['diary/purview/delete/{groupId}','deleteDiaryPurview','delete',[190]],
     //微博默认关注
     ['diary/default/attention','getDefaultAttention','get'],
      // 获取系统工作记录
    ['diary/system/work/record', 'getSystemWorkRecord',[28,184]],
    // 获取即时保存 手机端和电脑端信息互通
    ['diary/quick/save', 'quickSave','post'],
];