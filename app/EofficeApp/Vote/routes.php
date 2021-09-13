<?php
//250	参与调查
//251	调查管理
//360	调查表样式

$routeConfig = [
   // 获取调查表列表
   ['vote/manage', 'getVoteManageList',[251]],
   // 获取调查表设置详情
   ['vote/manage/{voteId}', 'getVoteManageInfo',[250,251]],
   // 编辑调查表
   ['vote/manage/{voteId}', 'editVoteManage','put',[251]],
   // 添加调查表
   ['vote/manage', 'addVoteManage','post',[251]],
   // 删除调查表
   ['vote/manage/{voteId}', 'deleteVoteManage','delete',[251]],
   // 编辑调查表设计器
   ['vote/design/{voteId}', 'editVoteDesign','put',[251]],
   // 获取参与的调查列表
   ['vote/vote-in', 'getMineList',[250]],
   // 获取参与的调查列表
   ['vote/after_login_open_list', 'getAfterLoginOpenList',[250]],
   // 参与调查保存数据
   ['vote/vote-in/{voteId}', 'saveVoteData','post',[250]],
   // 更新调查表状态
   ['vote/manage-status/{voteId}', 'updateVoteManage','put',[251]],
   // 查看投票结果
   ['vote/manage-result/{voteId}', 'getVoteResult',[250,251]],
   // 获取参与统计人员
   ['vote/user/{voteId}', 'getVoteInUser',[250,251]],
   // 获取投票数据列表
   ['vote/manage/result-list/{voteId}', 'getVoteDataList',[251]],
   // 获取投票数据
   ['vote/manage/vote-in-detail/{id}', 'getVoteInDetail',[251]],
   // 获取样式列表
   ['vote/mode', 'getVoteModeList',[360, 251]],
   // 新建样式
   ['vote/mode', 'addVoteMode','post',[360]],
   // 编辑样式
   ['vote/mode/{modeId}', 'editVoteMode','put',[360]],
   // 删除样式
   ['vote/mode/{modeId}', 'deleteVoteMode','delete',[360]],
   // 删除投票记录
   ['vote/log/{voteId}', 'deleteVoteResult','delete',[251]],
   // 删除全部投票记录
   ['vote/log_all/{voteId}', 'deleteAllVoteResult','delete',[251]],
   // 获取样式数据
   ['vote/mode/{modeId}', 'getVoteMode',[360]],
   // 获取样式数据
   ['vote/mode/default/{modeId}', 'defaultVoteMode',[360]],
];
