<?php
$routeConfig = [
    // 【新建流程index】 获取当前人员可以新建的流程;带查询
    ['flow/new-index/create-list', 'getNewIndexCreateList', [2]],
    // 【新建流程index】 获取常用流程list
    ['flow/new-index/flow-favorite', 'getFavoriteFlowList', [2]],
    // 【新建流程index】 获取某个流程的当前用户创建的最新的20条流程，供历史数据导入;带查询
    ['flow/new-index/create-history-list', 'getNewIndexCreateHistoryList', [2]],
    // 【新建流程index】 流程收藏 新建收藏
    ['flow/new-index/flow-favorite', 'addFlowFavorite', 'post', [2]],
    // 【新建流程index】 流程收藏 删除收藏
    ['flow/new-index/flow-favorite', 'deleteFlowFavorite', 'delete', [2]],
    // 【新建流程页面】 根据设置，展示流程基本信息[201601028，此函数改为 flowservice 内部调用，此函数的直属路由可以删掉]
    ['flow/new-page/flow-run-info', 'getNewPageFlowRunInfo'],
    // 【新建流程页面】 保存
    ['flow/new-page/flow-save', 'newPageSaveFlow', 'post', [2, 3, 420]],
    // 【新建流程页面】 判断是否有委托
    ['flow/new-page/verify-agent', 'verifyFlowHaveAgent', [2]],
    // 【办理页面】 获取流程主体部分所需所有数据，分办理页面/新建页面/查看页面，会判断权限 ---已修改 api中currentUser
    ['flow/flow-handle-page/main', 'getFlowHandlePageMainData'],
    // 【办理页面】判断不选人提交
    ['flow/flow-handle-page/verify_submit_without_dialog', 'verifySubmitWithoutDialog', [2, 3, 5, 420]],
    // 【办理页面】根据用户排班获取超时时间
    ['flow/flow-handle-page/get_overtime_by_selected_user', 'getOvertimeBySelectedUser'],
    // 【办理页面】获取下一节点超时时间
    ['flow/flow-handle-page/get_overtime_by_flow_process', 'getOvertimeByFlowProcess'],
    // 【办理页面】 获取流程办理/查看页面上，流程其他信息标签里面的数量
    ['flow/run/other-tabs-count/{runId}', 'getFlowRunOtherTabsCount'],
    // 【流程签办反馈】 获取签办反馈列表
    ['flow/feedback/flow-run/{runId}', 'getFlowFeedbackList'],
    // 【流程签办反馈】 新建签办反馈
    ['flow/feedback', 'createFlowFeedback', 'post'],
    // 【流程签办反馈】 编辑签办反馈
    ['flow/feedback/{feedbackId}', 'editFlowFeedback', 'put'],
    // 【流程签办反馈】 删除签办反馈
    ['flow/feedback/{feedbackId}', 'deleteFlowFeedback', 'delete'],
    // 【流程签办反馈】 获取单个签办反馈
    ['flow/feedback/{feedbackId}', 'getFlowFeedbackDetail'],
    // 【流程公共附件】 保存公共附件
    ['flow/public-attachment/{runId}', 'postFlowPublicAttachment', 'post', [2, 3, 5, 420]],
    // 【流程相关文档】 获取相关文档
    ['flow/related-document/{runId}', 'getFlowRelatedDocument'],
    // 【流程相关文档】 保存相关文档
    ['flow/related-document/{runId}', 'addFlowRelatedDocument', 'post', [2, 3, 5, 420]],
    // 【流程子流程】 获取子流程列表
    ['flow/subflow', 'getFlowSubflow'],
    // 【流程图】 获取流程图页面的流程步骤数据--用于流程图
    ['flow/chart/show-flow-chart/{runId}', 'getFlowChart'],
    // 【流程图】 获取流程图页面的流程运行步骤数据
    ['flow/show-flow-run-process/{runId}', 'getFlowRunProcessData'],
    // 【流程图】 附属展示流程运行步骤--暂时没用到
    // ['flow/chart/show-flow-run-process/{runId}', 'getFlowChartFlowRunProcess'],
    // 【流程运行】 查看/办理页面初始化的时候，当前人员接收流程（前端页面没用到，先注释）
    // ['flow/run/receive-flow/{runId}', 'saveReceiveFlow', 'post'],
    // 【流程运行】 查看/办理页面初始化的时候，记录最后查看时间（前端页面没用到，先注释）
    // ['flow/run/record-last-visitd-time/{runId}', 'recordLastVisitdTime', 'post'],
    // 【流程运行】 删除功能
    ['flow/run/flow-delete/{runId}', 'deleteFlow', 'delete', [3, 4, 5, 252, 323]],
    // 【流程运行】 挂起功能
    ['flow/run/flow-hangup', 'hangupFlow', 'post', [3]],
    // 【流程运行】 取消挂起功能
    ['flow/run/flow-cancelhangup', 'cancelHangupFlow', 'post', [3]],
    // 【流程运行】 保存流水号、流程名称等 【flow_run】 表的信息;run_id必填;保存流程表单信息也是这里。
    ['flow/run/save-flow-run-info', 'saveFlowRunFlowInfo', 'post', [3, 5, 420]],
    // 【流程运行】 获取某条流程所有节点，判断可流出节点；自由、固定流程都是这个，参数：run_id,user_id,flow_process
    ['flow/run/show-flow-transact-process', 'showFlowTransactProcess', [2, 3, 4, 5, 420]],
    // 【流程运行】 获取某条【固定】流程，某个可以流出的节点的所有办理人信息;【自由流程不需要判断，可以提交给所有人】
    ['flow/run/show-flow-transact-user', 'showFixedFlowTransactUser', [2, 3, 4, 5, 420]],
    // 【流程运行】 获取某条固定流程配置的默认抄送人，如果有
    ['flow/run/show-flow-copy-user', 'showFixedFlowCopyUser', [2, 3, 4, 5, 420]],
    // 【流程运行】 获取某条流程，某节点办理人总数，如果只有一个办理人，监控提交等直接跳过选择主办人
    ['flow/run/show-flow-max-process-user-count', 'getFlowMaxProcessUserCount', [4]],
    // 【流程运行】 获取某条【固定】流程，当前节点的所有办理人信息;【自由流程不需要判断，可以提交给所有人】
    // ['flow/run/show-flow-current-user', 'showFixedFlowCurrentUser'],
    // 【流程运行】 【提交流程】 固定&自由流程，提交页面提交下一步按钮、结束流程都是这个
    ['flow/run/flow-turning', 'flowTurning', 'post', [2, 3, 4, 5, 420]],
    // 【流程运行】 【提交流程】 固定&自由流程，提交页面经办人提交流程
    ['flow/run/flow-turning-other', 'flowTurningOther', 'post', [3, 4, 5, 420]],
    // 【流程运行】 【提交流程】 固定流程批量提交
    ['flow/run/flow-multi-turning', 'flowMultiTurning', 'post', [3]],
    // 【流程运行】 【提交流程】 固定流程批量结束
    ['flow/run/flow-multi-end', 'flowMultiEnd', 'post', [3]],
    // 【流程运行】 【flow_run】 获取流程flow_run为主表的所有相关流程运行信息，使用时都要传递user_id参数，但不强制要求
    ['flow/run/flow-running-info/{runId}', 'getFlowRunningInfo'],
    // 获取表单控件的类型和属性等数据集
    ['flow/form-data/control-type-data', 'getControlTypeData'],
    // 【流程运行】 【流程数据】 获取某条流程解析后的formdata
    ['flow/form-data/get-parse-data', 'getFlowFormParseData'],
    // 流程表单快速录入功能需要的生成的二维码
    ['flow/form-data/qr-code', 'generateQrCode'],
    // 【流程运行】 【流程数据】 文档模块，获取流程信息，展示归档后的流程
    ['flow/flow-info/filing-document/{documentId}', 'getFilingDocumentFlowInfo', [8,196]],
    // 【流程运行】 获取某条流程当前步骤是否有主办人，返回1，有主办人，返回0，没有主办人。
    ['flow/run/flow-process-host-flag/{runId}', 'getFlowProcessHostFlag', [4]],
    // 【流程运行】 设置某条流程的主办人
    ['flow/run/set-flow-process-host-user/{runId}', 'setFlowProcessHostUser', 'post', [4]],
    // 【流程运行】 转发
    ['flow/run/flow-run-forward/{runId}', 'doFlowRunForward', 'post', [2, 3, 5, 420]],
    // 【流程运行】 委托
    ['flow/run/flow-run-agent/{byAgentUser}', 'doFlowRunAgent', 'post', [2, 3, 5, 420]],
    // 【流程运行】 收回
    ['flow/run/flow-run-take-back/{runId}', 'doFlowRunTakeBack', 'post', [4, 5, 252, 420]],
    // 【流程运行】 催办
    ['flow/run/flow-run-limit', 'doFlowRunLimit', 'post', [4, 252]],
    // 【流程运行】 验证签办反馈、公共附件必填
    ['flow/run/verify-required/{runId}', 'verifyFlowRunRequired', [3, 4, 5, 420]],
    // 【流程邮件外发】 流程邮件外发实现
    ['flow/flow-out-mail', 'sendFlowOutMail', 'post', [2, 3, 5, 420]],
    // 【流程抄送】 新建抄送
    ['flow/flow-copy', 'createFlowCopy', 'post', [2, 3, 4, 5, 33, 252, 323, 376, 377, 420]],
    // 【流程抄送】 获取抄送列表;[带查询]
    ['flow/flow-copy', 'getFlowCopyList', [377]],
    // 【流程抄送】 获取抄送流程名称列表;[带查询]
    ['flow/flow-copy/flow-name-list', 'getFlowCopyFlowNameList', [377]],
    // 【流程委托】 新建委托规则
    ['flow/flow-agency', 'createFlowAgencyRule', 'post', [33,10]],
    // 【流程委托】 可以委托的流程的列表，在新建委托时使用
    ['flow/flow-agency/create-agency/{userId}', 'canNewAgencyFlowList', [33,10]],
    // 【流程委托】 收回委托规则;[1、单个删除:一个 agency_rule_id ，再传一个flow_id;2、批量删除:如果 agency_rule_id 是 , 分割的字符串，且flow_id也是一样对应的串，就批量删除;]
    ['flow/flow-agency/tack-back/{agencyRuleId}', 'tackBackFlowAgencyRule', 'post', [33,10]],
    // 【流程委托】 收回全部委托规则[我的委托、被委托、其他委托]
    ['flow/flow-agency/tack-back-rule-all', 'tackBackAllOfFlowAgencyRule', 'post', [33,10]],
    // 【流程委托】 获取委托规则列表;[带查询，可以支持:我的委托规则、被委托规则、其他委托规则]
    ['flow/flow-agency/agency-rule-list', 'getFlowAgencyRuleList', [33,10]],
    // 【流程委托】 获取委托记录列表;[带查询，可以支持:我的委托记录、被委托记录]
    ['flow/flow-agency/agency-record-list', 'getFlowAgencyRecordList', [33]],
    // 【流程列表】 获取待办事宜列表;
    ['flow/flow-list/teed-to-do-list', 'teedToDoList', [3]],
    // 【流程列表】 获取已办事宜列表;
    ['flow/flow-list/already-do-list', 'alreadyDoList', [252]],
    // 【流程列表】 获取办结事宜列表;
    ['flow/flow-list/finished-list', 'finishedList', [323]],
    // 【流程列表】 获取我的请求列表;
    ['flow/flow-list/my-request-list', 'myRequestList', [2, 420]],
    // 【流程列表】 获取流程监控列表;
    ['flow/flow-list/monitor-list', 'monitorList', [4]],
    // 【流程列表】 获取超时查询列表;
    ['flow/flow-list/overtime-list', 'overtimeList', [376]],
    // 【流程列表】 获取流程查询列表;
    ['flow/flow-list/flow-search-list', 'flowSearchList', 'post', [5]],
    ['flow/flow-list/flow-search-list', 'flowSearchList', [5]],
    // 【流程列表】 获取流程动态信息控件历史流程列表;
    ['flow/flow-list/flow-dynamic-info-history-list', 'getFlowDynamicInfoHistoryList', 'post', [420, 5]],
    // 【流程列表】 获取流程选择器查询列表;
    ['flow/flow-list/flow-selector-list', 'flowSelectorList', 'get', [5]],
    // 【流程类别列表】 获取待办事宜-流程类别列表;[原中间列数据]
    ['flow/flow-list/teed-to-do-flow-sort-list', 'teedToDoFlowSortList', [3]],
    // 【流程类别列表】 获取已办事宜-流程类别列表;[原中间列数据]
    ['flow/flow-list/already-do-flow-sort-list', 'alreadyDoFlowSortList', [252]],
    // 【流程类别列表】 获取办结事宜-流程类别列表;[原中间列数据]
    ['flow/flow-list/finished-flow-sort-list', 'finishedFlowSortList', [323]],
    // 【流程类别列表】 获取我的请求-流程类别列表;[原中间列数据]
    ['flow/flow-list/my-request-flow-sort-list', 'myRequestFlowSortList', [420]],
    // 【流程类别列表】 获取流程监控-流程类别列表;[原中间列数据]
    ['flow/flow-list/monitor-flow-sort-list', 'monitorFlowSortList', [4]],
    // 【流程类别列表】 获取超时查询-流程类别列表;[原中间列数据]
    ['flow/flow-list/overtime-flow-sort-list', 'overtimeFlowSortList', [376]],
    // 【流程设计】 获取流程类别列表
    ['flow/flow-define/flow-sort-list', 'getFlowSortList'],
    // 按流程分类分组返回流程列表,手机端调用
    ['flow/flow-define/flow-list-group-by-flow-sort', 'getFlowListGroupByFlowSort'],
    // 【流程设计】 新建流程类别
    ['flow/flow-define/flow-sort', 'createFlowSort', 'post', [202]],
    // 【流程设计】 编辑流程类别
    ['flow/flow-define/flow-sort/{id}', 'editFlowSort', 'post', [202]],
    // 【流程设计】 删除流程类别
    ['flow/flow-define/flow-sort/{id}', 'deleteFlowSort', 'delete', [202]],
    // 【流程设计】 获取流程类别详情
    ['flow/flow-define/flow-sort/{id}', 'getFlowSortDetail'],
    // 【流程设计】 【流程表单】 获取流程表单列表
    ['flow/flow-define/flow-form', 'getFlowForm'],
    // 【流程设计】 【流程表单】 升级10.0的表单为10.5版本的（为了兼容10.0导入的表单）
    ['flow/flow-define/flow-form/update-form-html', 'updateFormHtml', 'post', [201]],
    // 【流程设计】 【流程表单】 新建流程表单
    ['flow/flow-define/flow-form', 'createFlowForm', 'post', [201]],
    // 【流程设计】 【流程表单】 编辑流程表单
    ['flow/flow-define/flow-form/{id}', 'editFlowForm', 'post', [201]],
    // 【流程设计】 【流程表单】 删除流程表单
    ['flow/flow-define/flow-form/{id}', 'deleteFlowForm', 'delete', [201]],
    // 【流程设计】 【流程表单】 获取流程表单详情
    ['flow/flow-define/flow-form/{id}', 'getFlowFormDetail'],
    // 【流程设计】 【流程表单】 复制流程表单
    ['flow/flow-define/flow-form/{formId}/copy', 'copyFlowForm', [201]],
    // 【流程运行】 【流程表单】 根据综合参数，获取流程表单详情，用在解析 节点模板/归档模板/打印模板
    ['flow/flow-template/flow-form', 'getFlowTemplateFormDetail'],
    // 【流程设计】 【表单版本】 获取表单版本列表--传formId
    ['flow/flow-form/version/{id}', 'getFlowFormVersion', [201]],
    // 获取定义流程中正在运行中的流程数量
    ['flow/flow-define/{flowId}/counts', 'getFlowRuningCounts'],
    // 【流程设计】 【表单版本】 获取表单版本详情
    ['flow/flow-form/version/info/{id}', 'getFlowFormVersionDetail', [201]],
    // 【流程设计】 获取已定义的流程列表，流程设计，流程查询
    ['flow/flow-define/flow-define-list', 'getFlowDefineList'],
    // 【流程设计】 获取某条流程的全部定义流程信息
    ['flow/flow-define/flow-define-info/{flowId}', 'getFlowDefineInfo'],
    // 【流程设计】 获取某条流程的基本定义流程信息
    ['flow/flow-define/flow-define-basic-info/{flowId}', 'getFlowDefineBasicInfo'],
    // 【流程设计】 获取某条流程的全部的出口条件设置信息
    ['flow/flow-define/get-flow-define-info/{flowId}', 'getFlowDefineInfoList'],
    // 【流程设计】 获取某条节点的出口条件信息
    ['flow/flow-define/get-flow-out-info/{flowId}', 'getFlowOutNodeInfo'],
    // 【流程设计】 获取流程模板列表[子流程列表也是这个]
    ['flow/flow-define/flow-define-relate-flow-sort', 'getFlowDefineRelateFlowSort'], // 废弃！前端未使用！
    // 【流程设计】 新建固定or自由流程基本信息
    ['flow/flow-define/flow-define-basic-info', 'addFlowDefineBasicInfo', 'post', [57]],
    // 【流程设计】 编辑固定or自由流程基本信息
    ['flow/flow-define/flow-define-basic-info/{flowId}', 'editFlowDefineBasicInfo', 'post', [57,10]],
    // 【流程设计】 删除固定or自由流程基本信息
    ['flow/flow-define/flow-define-basic-info/{flowId}', 'deleteFlowDefineBasicInfo', 'delete', [57]],
    // 【流程设计】 固定流程统一设置催促时间
    ['flow/flow-define/flow-unified-set-press-time/{flowId}', 'unifiedSetPresstime', 'post', [57]],
    // 【流程设计】 编辑监控人员
    ['flow/flow-define/flow-monitor/{flowId}', 'editFlowMonitor', 'post', [57,10]],
    // 【流程设计】 编辑其他设置
    ['flow/flow-define/flow-other-info/{flowId}', 'editFlowOtherInfo', 'post', [57,10]],
    // 【流程设计】 【节点设置】 获取节点列表
    ['flow/flow-define/flow-node/node-list/{flowId}', 'getFlowNodeList'],
    // 【流程设计】 【节点设置】 获取节点列表-查看办理页面
    ['flow/flow-define/flow-node/node-list-run-page/{flowId}', 'getFlowNodeListForRunPage'],
    // 【流程设计】 【节点设置】 批量保存流程节点信息
    ['flow/flow-define/flow-node/node-list/{flowId}', 'batchSaveFlowNode', 'post', [57, 10]],
    // 【流程设计】 【节点设置】 获取节点详情
    ['flow/flow-define/flow-node/{nodeId}', 'getFlowNode'],
    // 【流程设计】 【节点设置】 获取节点详情
    ['flow/flow-define/flow-node-base-page/{nodeId}', 'getFlowNodeForBasePage'],
    // 【流程设计】【节点设置】判断节点是否被其他节点在办理人设置处引用
    ['flow/flow-define/flow-node/quoted/other', 'isNodeQuotedInHandler'],
    // 【流程设计】 【节点设置】 获取节点子流程详情
    ['flow/flow-define/flow-node/sunflow/{nodeId}', 'getSunflowInfo'],
    // 【流程设计】 【节点设置】 删除节点
    ['flow/flow-define/flow-node/{nodeId}', 'deleteFlowNode', 'delete', [57]],
    // 【流程设计】 【节点设置】 新建节点--节点信息
    ['flow/flow-define/flow-node', 'addFlowNode', 'post', [57]],
    // 【流程设计】 【节点设置】 编辑节点信息
    ['flow/flow-define/flow-node/{nodeId}', 'editFlowNode', 'post', [57, 10]],
    // 【流程设计】 【节点设置】 编辑办理人员[默认办理人一起保存]
    ['flow/flow-define/flow-node-transact-user/{nodeId}', 'editFlowNodeTransactUser', 'post', [57, 10]],
    // 【流程设计】 【节点设置】 编辑字段控制
    ['flow/flow-define/flow-node-field-control/{nodeId}', 'editFlowNodeFieldControl', 'post', [57, 10]],
    // 【流程设计】 【节点设置】 字段控制，解析表单控件，获取控件类型作为筛选条件，带数量
    ['flow/flow-define/flow-node-field-control-filter', 'getFlowNodeFieldControlFilterInfo', [57, 10]],
    // 【流程设计】 【节点设置】 编辑路径设置
    ['flow/flow-define/flow-node-path-set/{nodeId}', 'editFlowNodePathSet', 'post', [57, 10]], // 废弃！前端未使用！
    // 【流程设计】 【节点设置】 获取出口条件列表--现在没有被用到，里面函数内容不正确，是旧的逻辑，需要用到这个路由的时候再改
    ['flow/flow-define/flow-node-outlet-list/{nodeId}', 'getFlowNodeOutletList', [57, 10]], // 废弃！前端未使用！
    // 【流程设计】 【节点设置】 获取出口条件详情
    ['flow/flow-define/flow-node-outlet/{termId}', 'getFlowNodeOutlet', [57, 10]], // 废弃！前端未使用！
    // 【流程设计】 【节点设置】 添加出口条件
    ['flow/flow-define/flow-node-outlet', 'addFlowNodeOutlet', 'post', [57]], // 废弃！前端未使用！
    // 【流程设计】 【节点设置】 编辑出口条件
    ['flow/flow-define/flow-node-outlet/{termId}', 'editFlowNodeOutlet', 'post', [57]], // 废弃！前端未使用！
    // 【流程设计】 【节点设置】 删除出口条件
    ['flow/flow-define/flow-node-outlet/{termId}', 'deleteFlowNodeOutlet', 'delete', [57]], // 废弃！前端未使用！
    // 【流程设计】 【节点设置】 编辑出口条件的关联关系
    ['flow/flow-define/flow-node-outlet-relation/{nodeId}', 'editFlowNodeOutletRelation', 'post', [57]], // 废弃！前端未使用！
    // 【流程设计】 【节点设置】 编辑子流程
    ['flow/flow-define/flow-node-subflow/{nodeId}', 'editFlowNodeSubflow', 'post', [57, 10]], // 废弃！前端未使用！
    // 【流程设计】 【节点设置】 编辑抄送人员
    ['flow/flow-define/flow-node-copy-user/{nodeId}', 'editFlowNodeCopyUser', 'post', [57, 10]],
    // 【流程表单解析】 获取解析后的表单html
    ['flow/parse-form/{formId}', 'getParseForm'],
    // 【流程设计】 【节点设置】 【办理人员】 经办人员/部门/角色的值的变化，会触发【默认办理人/主办人】的验证事件，验证人员是否在范围内，返回处理后的，在范围内的【默认办理人/主办人】
    ['flow/flow-define/verify-default-user-include', 'verifyDefaultUserInclude', 'post', [57, 10]],
    // 【流程表单控件序号】 根据流程表单id，查询此流程表单里所有的控件，按照序号（control_sort_id）排序（asc），关联所属分组（belongs_group）的信息。为了路由的规范性，表单id（flow_form_id）通过必填参数的方式传递。
    ['flow/flow-form-control-sort', 'getFlowFormControlSort'], // 废弃！前端未使用！
    // 【流程表单控件分组】 根据流程表单id（flow_form_id），获取此表单里的所有“控件分组”，按照序号（group_sort_id）排序（asc），关联下属所有表单控件信息。
    ['flow/flow-form-control-group', 'getFlowFormControlGroup'], // 废弃！前端未使用！
    // 【流程表单控件分组】 排序，分组两个表的数据保存只需要一个路由，格式化之后传到这个路由里，在此路由里进行处理。
    ['flow/flow-form-control-group', 'saveFlowFormControlGroup', 'post'], // 废弃！前端未使用！
    // 【会签控件】 获取会签控件列表  -- 验证流程查看权限
    ['flow/flow-countersign/{runId}', 'getFlowCounterSign', [3, 5, 420]],
    // // 【会签控件】 新建会签
    // ['flow/flow-countersign/{runId}', 'createFlowCounterSign', 'post'],
    // // 【会签控件】 编辑会签
    // ['flow/flow-countersign/info/{countersignId}', 'editFlowCounterSign', 'post'],
    // // 【会签控件】 删除会签
    // ['flow/flow-countersign/info/{countersignId}', 'deleteFlowCounterSign', 'delete'],
    // // 【会签控件】 获取会签详情
    // ['flow/flow-countersign/info/{countersignId}', 'getFlowCounterSignDetail'],
    // 【流程定义】 获取表单字段详情  -- 验证流程编辑权限
    ['flow/flow-define/flow-form-flies/{flowId}', 'getFlowFormFliesDetail', [10, 57]],
    // 【流程定义】 获取表单字段详情-用于数据外发解析带明细字段的列表 -- 验证流程编辑权限
    ['flow/flow-define/flow-form-flies-for-outsend/{id}', 'getFlowFormFliesDetailForOutsend', [10, 57]],
    // 【流程定义】 节点设置-流程图节点信息更新 --验证节点编辑权限
    ['flow/flow-define/flow-chart-node/{id}', 'chartEditNode', 'post', [57]],
    // 【流程定义】 节点设置-流程图节点删除 --验证节点编辑权限
    ['flow/flow-define/flow-chart-node/{id}', 'chartDeleteNode', 'delete', [57]],
    // 【流程定义】 节点设置-流程图节点新建 --验证流程编辑权限
    ['flow/flow-define/flow-chart-node', 'chartCreateNode', 'post', [57]],
    // 【流程定义】 节点设置-流程图节点清除所有连线 --验证流程编辑权限
    ['flow/flow-define/flow-chart-node-delete-all-processto/{id}', 'chartDeleteAllNodeProcessTo', 'delete', [57]],
    // 【流程定义】 节点设置-流程图节点清除连线 --验证节点编辑权限
    ['flow/flow-define/flow-chart-node-delete-processto', 'chartDeleteNodeProcessTo', 'post', [57]],
    // 【流程定义】 节点设置-流程图节点保存出口条件 --验证流程编辑权限
    ['flow/flow-define/flow-chart-node-update-condition', 'chartUpdateNodeCondition', 'post', [57]],
    // // 【流程定义】 节点设置-流程外发测试外部数据库连接
    // ['flow/flow-define/external-database-test-connection', 'externalDatabaseTestConnection','post'],
    // 【流程定义】 节点设置-流程外发获取内部模块列表
    ['flow/flow-define/flow-outsend-get-module-list', 'flowOutsendGetModuleList', [10, 57]],
    // 【流程定义】 节点设置-流程外发获取内部模块字段列表
    ['flow/flow-define/flow-outsend-get-module-filed-list', 'flowOutsendGetModuleFieldsList', [10, 57]],
    // // 【流程定义】 节点设置-流程外发获取外部数据库表
    // ['flow/flow-define/flow-outsend-database-table-list', 'flowOutsendDatabaseTableList'],
    // // 【流程定义】 节点设置-流程外发获取外部数据库表
    // ['flow/flow-define/flow-outsend-database-data', 'externalDatabaseGetData','post'],
    // // 【流程定义】 节点设置-流程外发获取外部数据库表字段
    // ['flow/flow-define/flow-outsend-database-table-field-list', 'flowOutsendDatabaseTableFieldList'],
    // 【流程定义】 节点设置-流程外发保存数据 --验证节点编辑权限
    ['flow/flow-define/flow-outsend-save-data', 'flowOutsendSaveData', 'post', [10, 57]],
    //【流程定义】 节点设置-流程超时设置 --验证节点编辑权限
    ['flow/flow-define/flow-overtime-save-data', 'flowOverTimeSaveData', 'post', [10, 57]],
    // 【流程定义】 编辑流水号规则 --验证流程编辑权限
    ['flow/flow-define/update_flow_sequence_rule/{flowId}', 'updateFlowSequenceRule', 'post', [10, 57]],
    // 【流程定义】 流程图模式下获取流出节点列表  --验证节点编辑权限
    ['flow/flow-define/get_flow_out_node_list/{nodeId}', 'getFlowOutNodeList', [57]],
    // 【流程定义】 流程图模式下获取当前流出节点列表 --验证流程编辑权限
    ['flow/flow-define/get_flow_out_current_node_list/{flowId}', 'getFlowCurrentNodeList', [57]],
    // 【流程定义】 列表模式下获取流出节点列表 --验证节点编辑权限
    ['flow/flow-define/get_flow_out_node_list_for_list/{nodeId}', 'getFlowOutNodeListForList', [57]],
    // 【流程定义】 获取导入表单的内容
    ['flow/flow-define/get-import-flow-form/{formId}', 'getImportFlowForm', 'post', [201]],
    // 【流程定义】 判断导入的表单素材版本
    ['flow/flow-define/check-form-version', 'checkFormVersion', 'post', [201]],
    // 【流程定义】 获取流程所有节点设置办理人离职人员信息 --验证流程编辑权限
    ['flow/flow-define/quit-user-replace/{flowId}', 'getFlowQuitUserList', [57]],
    // 【流程定义】 获取流程所有节点设置办理人列表 --验证流程编辑权限
    ['flow/flow-define/handle-user-replace/user/{flowId}', 'getFlowHandleUserList', [57]],
    // 【流程定义】 获取流程所有节点设置办理角色列表 --验证流程编辑权限
    ['flow/flow-define/handle-user-replace/role/{flowId}', 'getFlowHandleRoleList', [57]],
    // 【流程定义】 获取流程所有节点设置办理部门列表 --验证流程编辑权限
    ['flow/flow-define/handle-user-replace/dept/{flowId}', 'getFlowHandleDeptList', [57]],
    // 【流程定义】 替换流程设置办理人离职人员 --验证流程编辑权限
    ['flow/flow-define/handle-user-replace/{flowId}', 'replaceHandleInfo', 'post', [57]],
    // 【流程设计】 【表单模板】 取定义流程的各种表单模板 --废弃
    ['flow/flow-define/flow-template', 'getFlowNodeTemplate'],
    // 【流程设计】 【表单模板】 保存定义流程的各种表单模板 --废弃
    ['flow/flow-define/flow-template', 'saveFlowNodeTemplate', 'post'],
    // 【流程设计】 【表单模板】 流程设计，获取各种表单模板规则的列表 --验证流程编辑权限
    ['flow/flow-define/flow-template/rule-list', 'getFlowTemplateRuleList', [10, 57]],
    // 【流程设计】 【表单模板】 流程设计，保存各种表单模板规则 --验证流程编辑权限
    ['flow/flow-define/flow-template/rule-list', 'saveFlowTemplateRule', 'post', [10, 57]],
    // 【流程设计】 【表单模板】 获取流程表单模板的list，用在设置表单模板规则的时候，选择表单模板 --废弃 前端未使用！
    ['flow/flow-define/flow-template/template-list', 'getFlowTemplateList'],
    // 办理页面关联流程列表  --验证流程查看权限
    ['flow/run/relation-flow/{runId}', 'getRelationFlowData', [2, 3, 4, 5, 33, 252, 323, 376, 377, 420]],
    // 获取出口条件中涉及目标控件的数据
    ['flow/flow-form/use_control_term', 'getUseFormControls', 'post', [201]],
    // 【流程表单】 表单--下拉框动态数据源--获取流程经办人列表，flowId必填
    ['flow/flow-form/select-data-source/process-user-list', 'getSelectSourceProcessUserList', [2, 3, 201]],
    // 【流程表单】 表单--下拉框动态数据源--获取流程本步骤经办人列表，nodeId必填
    ['flow/flow-form/select-data-source/current-process-user-list', 'getSelectSourceCurrentProcessUserList', [2, 3, 201]],
    // 【流程设计】 更新节点排序 --验证流程编辑权限
    ['flow/flow-define/update_sort/{flowId}', 'updateNodeSort', 'post', [57, 10]],
    // 【流程报表】 获取流程报表设置，获取分组依据和数据分析字段  --废弃！ 前端未使用
    ['flow/report/config/group-analyze', 'getFlowReportGroupAndAnalyzeConfig'],
    // 【流程设计】 获取自由流程必填设置  --验证流程编辑权限
    ['flow/flow-define/free_flow_required/{flowId}', 'getFreeFlowRequired', [57]],
    // 【流程设计】 获取自由流程必填字段
    ['flow/flow-define/free_flow_required_info/{flowId}', 'getFreeFlowRequiredInfo', [57]],
    // 【流程设计】 编辑自由流程必填设置
    ['flow/flow-define/free_flow_required/{flowId}', 'editFreeFlowRequired', 'put', [57]],
    // 【流程设计】 获取流程名称规则设置元素列表
    ['flow/flow-define/get_flow_name_rules_field/{formId}', 'getFlowNameRulesField', [57]],
    // 【流程表单】 表单简易版标准版切换
    ['flow/flow-define/form_type_conversion', 'formTypeConversion', 'post', [201]],
    // 【流程表单】 表单简易版标准版切换获取表单控件列表
    ['flow/flow-define/form_type_conversion_get_control/{formId}', 'formTypeConversionGetControl', [201]],
    // 【流程表单】 表单简易版标准版切换获取表单控件列表
    ['flow/flow-define/form_type_conversion_get_control_for_complex/{formId}', 'formTypeConversionGetControlForComplex', [201]],
    // 【流程表单】 子表单-生成子表单
    ['flow/flow-form/child_form/create_child', 'createChildForm', 'post', [201]],
    // 【流程表单】 子表单-子表单列表
    ['flow/flow-form/child_form/child_list/{parentId}', 'getChildFormList', [201]],
    // 【流程表单】 子表单-子表单列表
    ['flow/flow-form/child_form/child_list_by_flow/{flowId}', 'getChildFormListByFlowId', [57,10]],
    // 【流程表单】 子表单-获取子表单详情
    ['flow/flow-form/child_form/get_detail/{formId}', 'getChildFormDetail', [201]],
    // 【流程表单】 子表单-删除单个子表单
    ['flow/flow-form/child_form/{formId}', 'deleteChildForm', 'delete', [201]],
    // 【流程表单】 子表单-编辑单个子表单
    ['flow/flow-form/child_form/{formId}', 'editChildForm', 'put', [201]],
	// 【流程表单】 子表单-更新子表单
	['flow/flow-form/child_form/update_form', 'updateChildForm', 'post', [201]],
    // 【流程设计】 获取表单类别列表
    ['flow/flow-define/flow-form-sort-list', 'getFlowFormSortList', [57, 202, 201]],
    // 【流程设计】 新建表单类别
    ['flow/flow-define/flow-form-sort', 'createFlowFormSort', 'post', [202]],
    // 【流程设计】 编辑表单类别
    ['flow/flow-define/flow-form-sort/{id}', 'editFlowFormSort', 'post', [202]],
    // 【流程设计】 删除表单类别
    ['flow/flow-define/flow-form-sort/{id}', 'deleteFlowFormSort', 'delete', [202]],
    // 【流程设计】 获取表单类别详情
    ['flow/flow-define/flow-form-sort/{id}', 'getFlowFormSortDetail', [202]],
    // 【流程设计】 获取表单类别最大序号
    ['flow/flow-define/flow-form-sort-max', 'getMaxFlowFormSort', [202]],
    // 【流程设计】 获取流程分类最大排序值
    ['flow/flow-define/flow-sort-max', 'getMaxFlowSort', [202]],
    //【流程设置】获取紧急程度选项
    ['flow/setting/instancys', 'getInstancyOptions', 'get'], //(TODO)系统数据、代办已办等列表 多处调用
    //【流程设置】获取紧急程度选项
    ['flow/setting/get-instancys-id-name-relation', 'getInstancyIdNameRelation', 'get'],
    //【流程设置】保存紧急程度选项信息
    ['flow/setting/instancys', 'saveInstancyOptions', 'post', [10]],
    //【流程设置】删除紧急程度选项
    ['flow/setting/instancys/{instancyId}', 'deleteInstancyOption', 'delete', [10]],
    //【流程设置】获取流程设置某个参数的值
    ['flow/setting/get-param-value/{paramKey}', 'getFlowSettingsParamValueByParamKey'],
    //【流程设置】设置流程设置参数
    ['flow/setting/set-param-value', 'setFlowSettingsParamValue', 'post'],
    //【工作交办】获取流程相关人员
    ['flow/work-handover/list', 'getFlowUserInfo', 'get', [12]],
    //【工作交办】获取流程相关人员
    ['flow/work-handover/list', 'getFlowUserInfo', 'post', [12]],
    //【工作交办】获取流程相关人员
    ['flow/work-handover/all/list', 'getFlowAllUserInfo', 'post', [12]],
    //【工作交办】全局替换流程相关人员
    ['flow/work-handover/replace', 'replaceFlowUser', 'post', [12]],
    //【工作交办】单个替换流程相关人员
    ['flow/work-handover/replace-one', 'replaceOneFlowUser', 'post', [12]],
    //【工作交办】单个替换流程相关人员
    ['flow/work-handover/replace-type', 'replaceFlowUserByType', 'post', [12]],
    //【工作交办】单个替换流程相关人员
    ['flow/work-handover/replace-user', 'replaceFlowUserByUser', 'post', [12]],
    //【工作交办】单个替换流程相关人员
    ['flow/work-handover/replace-user-for-grid', 'replaceFlowUserByGrid', 'post', [12]],
    //【流程设置】获取表单数据模板
    ['flow/run/data-template', 'getFormDataTemplateForRun', 'get', [2]],//新建流程权限
    //【流程设置】获取表单数据模板
    ['flow/flow-form/data-template', 'getFormDataTemplate', 'get', [57, 10]],
    //【运行流程】设置表单数据模板
    ['flow/run/data-template', 'setFormDataTemplateForRun', 'post', [2]],//只能设置自己的模板、新建流程权限
    //【流程设置】设置表单数据模板
    ['flow/flow-form/data-template', 'setFormDataTemplate', 'post', [57, 10]],
    //【流程设置】保存用户模板
    ['flow/flow-form/user-template', 'saveUserTemplate', 'post', [2]],//只能设置自己的模板、新建流程权限
    //【流程设置】删除用户模板
    ['flow/flow-form/user-template/{id}', 'deleteUserTemplate', 'delete', [2]],//只能设置自己的模板、新建流程权限
    // 【流程定义】 节点设置-流程数据验证保存数据
    ['flow/flow-define/flow-validate-save-data', 'flowValidateSaveData', 'post', [57, 10]],
    // 【运行流程】 流程数据验证获取数据
    ['flow/run/flow-validate-get-data', 'getFlowValidateDataForRun', 'get',  [2, 3, 5, 420]],
    // 【流程定义】 节点设置-流程数据验证获取数据
    ['flow/flow-define/flow-validate-get-data', 'getFlowValidateData', 'get', [57, 10]],
    // 【流程定义】 导出流程素材
    ['flow/flow-define/flows/materials/{flowId}','exportFlowMaterial','get',[57]],
    // 【流程定义】 导入流程素材
    ['flow/flow-define/flows/materials','importFlowMaterial','post',[57]],
    // 【流程运行】 验证表单数据
    // ['flow/run/data-validate', 'validateFlowData', 'post',  [2, 3, 5, 420]],// 与下面这条路由冲突了，新版框架不支持了
    // 【流程运行】 验证表单数据
    ['flow/run/data-validate/{id}', 'validateFlowData', 'post',  [2, 3, 5, 420]],
    // 【流程设计】 【节点设置】 获取自由节点详情
    ['flow/flow-define/flow-free-node/{nodeId}', 'getFlowFreeNode'],
    // 【流程设计】 【节点设置】 编辑自由节点详情
    ['flow/flow-define/flow-free-node/{nodeId}', 'editFlowFreeNode','post'],
    //  保存自由节点步骤信息
    ['flow/run/save_free_process_steps', 'saveFreeProcessSteps','post'],
    // 【流程设计】 预览流程标题处 获取服务器时间
    ['flow/flow-define/get-server-date', 'getServerDate', 'get'],
    // 【办理流程页面】 获取流程数据外发记录
    ['flow/run/get-flow-outsend-list', 'getOutsendList', 'get'],
    //【自定义流程选择器】 通过run_id获取流程详情
    ['flow/run/get-run-info/{runId}', 'getRunDetailByRunId', 'get'],
    //【流程设置】 通过flow_id获取form_id
    ['flow/run/get-form-info/{flowId}', 'getFormDetailByFlowId', 'get'],
    //【流程运行】 验证表单数据
    ['flow/run/valid-file-condition', 'validFileCondition', 'post',  [2, 3, 5, 420]],
    //【流程设置】 通过flow_id获取flow_others信息
    ['flow/run/get-flow-others-info/{flowId}', 'getFlowOthersDetailByFlowId', 'get'],
    //【控件收藏】添加
    ['flow/control/save-control-collection', 'saveControlCollection', 'post'],
    //【控件收藏】 获取列表
    ['flow/control/get-control-list', 'getControlCollectionList', 'get'],
    // 【流程设计】 获取流程定时触发配置
    ['flow/flow-define/get-flow-schedule/{flowId}', 'getFlowSchedulesByFlowId', 'get'],
    // 【流程设计】 编辑流程定时触发配置
    ['flow/flow-define/edit-flow-schedule-configs', 'editFlowSchedules', 'post'],
    // 【流程设计】 设置调试模式
    ['flow/flow-define/flow-define-open-debug', 'editFlowDefineDebug', 'post'],
    // 【运行流程】 新增流程打印日志
    ['flow/run/add-flow-run-print-log', 'addFlowRunPrintLog', 'post'],
    // 【运行流程】 获取流程打印日志
    ['flow/run/get-flow-run-print-log', 'getFlowRunPrintLog', 'get'],
    // 【流程设计】 获取用户有无某个表单的权限和表单设计的菜单权限
    ['flow/flow-define/get-form-edit-permission/{formId}', 'getFormEditPermission', 'get'],
    // 【定义流程】 设置调试模式
    ['flow/flow-define/flow-define-open-debug', 'editFlowDefineDebug', 'post'],
    // 【运行流程】 获取可办理、收回的运行节点信息
    ['flow/run/get-flow-run-process-list-to-deal-with', 'getFlowRunProcessListToDealWith', 'get'],
    // 【运行流程】获取办理人在强制合并节点需要等待其他节点的信息
    ['flow/run/get-force-merge-process-info', 'getFlowRunForceMergeInfo', 'get'],
    // 【流程运行】 获取当前用户打印规则列表，不验证流程编辑菜单权限
    ['flow/flow-define/flow-template/get-user-print-rule-list', 'getUserFlowPrintRuleList'],
];
