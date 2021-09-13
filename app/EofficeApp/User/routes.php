<?php
$routeConfig = [
    // 获取即时通讯群组信息
    ['user/usersgroup', 'getChatGroupInfo', 'post'],
    ['user/get-recent-year', 'getRecentFourYear'],
    ['user/userstatus-total', 'getUserStatusTotal'],
    ['user/multi-remove-dept', 'multiRemoveDept', 'post'],
    ['user/multi-set-role', 'multiSetRole', 'post'],
    ['user/userstatus-year-total', 'getrecentUserStatusTotal'],
    ['user/user-all-superior-arr', 'getSuperiorArrayByUserIdArr', 'post'],//根据数组获取上级和全部上级
    ['user/user-all-subordinate-arr', 'getSubordinateArrayByUserIdArr', 'post'],//根据数组获取下级和全部下级
    // 批量同步人事档案
    ['user/multi-sync-personnel', 'multipleSyncPersonnelFiles', 'post'],
    // 获取用户状态列表
    ['user/userstatus', 'getUserStatus'],//人事档案、cas认证模块、表单设计器组件、用户管理,不设置菜单权限
    // 新建用户状态
    ['user/userstatus', 'postUserStatusCreate', 'post', [240]],
    // 编辑用户状态
    ['user/userstatus/{statusId}', 'postUserStatusEdit', 'post', [240]],
    // 删除用户状态
    ['user/userstatus/{statusId}', 'getUserStatusDelete', 'delete', [240]],
    // 获取某条用户状态的详情
    ['user/userstatus/{statusId}', 'getUserStatusDetail', [240]],
    // 用户管理--相关路由
    // 用户管理--获取用户列表数据(用于需要获取所有用户的地方)
    ['user', 'getUserSystemList'],//用户选择器等，不设置菜单权限
    ['user/lists', 'getUserSystemList', 'post'],//用户选择器等，不设置菜单权限
    ['user/address-book-search', 'getAddressBookUsers'],
    ['user/my-department', 'getMyDepartmentUsers'],
    ['user/users', 'getUserSystemList','post'],//用户选择器等，不设置菜单权限
    // 用户管理--获取用户列表数据(仅用于用户管理处调用)
    ['user/manage', 'getUserManageList'],
    // 快速注册用户列表
    ['user/quick/register', 'getRegisterUser', [240]],
    // 组织架构首页获取用户审核数量
    ['user/quick/register-total', 'getRegisterUserPageData'],
    // 用户管理--获取用户工号自动生成规则
    ['user/get-user-job-number-rule', 'getUserJobNumberRule', [240]],
    // 用户管理--获取用户其他设置
    ['user/get-user-other-settings', 'getUserOtherSettings', [240]],
    // 用户管理--编辑用户其他设置
    ['user/edit-user-other-settings', 'editUserOtherSettings', 'post', [240]],
    // 解锁密码输错锁定的账号
    ['user/unlock/{userId}', 'unlockUserAccount', [240]],
    ['user/leave/{userId}', 'leaveUserAccount', 'put', [240]],
    ['user/my-all-subordinate', 'getMyAllSubordinate'],
    // 用户管理--获取用户信息
    ['user/{userId}', 'getUserAllData'],//人员卡片组件等，不设置菜单权限
    // 用户管理--获取用户角色级别信息
    ['user/{userId}/role-leavel', 'getUserRoleLeavel'],//表单设计器，不设置菜单权限
    // 用户管理--获取用户部门完整路径
    ['user/{userId}/dept-path', 'getUserDeptPath'],//表单设计器，不设置菜单权限
    // 用户管理--获取用户当前部门负责人
    ['user/{userId}/own-dept-director', 'getUserOwnDeptDirector'],//表单设计器，不设置菜单权限
    // 用户管理--获取用户上级部门的负责人
    ['user/{userId}/superior-dept-director', 'getUserSuperiorDeptDirector'],//表单设计器，不设置菜单权限
    // 用户管理--新建用户
    ['user', 'userSystemCreate', 'post', [240]],
    // 批量新建用户
    ['user/batch', 'mutipleUserSystemCreate', 'post', [240]],
    ['user/dept-user', 'addDeptUser', 'post'],
    ['user/dept-user/{userId}', 'editDeptUser', 'post'],
    // 用户管理--编辑用户
    ['user/{userId}', 'userSystemEdit', 'post', [240]],
    // 用户管理--删除用户
    ['user/{userId}', 'userSystemDelete', 'delete', [240]],
    // 用户管理--清空密码
    ['user/{userId}/password', 'userSystemEmptyPassword', 'delete', [240]],
    // 开放的一个可以根据用户id字符串获取用户列表的函数，可以传参getDataType获取在职离职已删除用户
    ['user/format/list', 'getUserListByUserIdString'],//在前端存在此api，但未被实际使用，可能已废弃
    // 用户管理--离职人员列表
    ['user/leave-office/list', 'getLeaveOfficeUser'],//树组件，不设置菜单权限
    // 根据用户id获取用户直接上级或所有上级
    ['user/user-all-superior/{userId}', 'getSuperiorArrayByUserId'],//可能已废弃
    // 根据用户id获取用户直接下级或所有下级
    ['user/user-all-subordinate/{userId}', 'getSubordinateArrayByUserId'],//选择器组件，不设置菜单权限
    // 通过部门id获取所有本部门包括子部门的人员列表
    ['user/department-user-list/{deptId}', 'getUserListByDeptId','post'],//选择器组件，不设置菜单权限
    // 获取用户授权信息
    ['user/authorization/info', 'getUserAuthorizationInfo'],
    // 获取所有用户ID集合
    ['user/all/id/string', 'getAllUserIdString', 'post'],//手机端通讯录使用，不设置菜单权限
    // 获取手机用户
    ['user/mobile/get-mobile-user-list', 'getMobileUserList', [240]],
    // 设置手机用户
    ['user/mobile/set-mobile-user', 'setMobileUser', 'post', [240]],
    ['user/mobile/set-mobile-user/{userId}', 'setMobileUserById', 'post', [240]],
    // 检查是否存在离职用户
    ['user/check/exist-leave-off-user', 'checkExistleaveOffUser'],
    // 表单系统数据获取用户相关数据
    ['user/user-data-for-from-datasource/{useraccounts}', 'getUserDataForFormDatasource'],//表单设计器，不设置菜单权限
    ['user/all/fields', 'getAllUserFields'],
    // 获取用户数和流程数
    ['user/system/number', 'getUserSystemInfoNumber'],
    // 用户共享录入二维码生成
    ['user/register/qrcode', 'getUserRegisterQrcode'],
    ['user/register/qrcode/download', 'downloadQrcode'],
    // 设置部门后审核用户
    ['user/register/check/dept', 'setDeptAndCheckUser', 'post'],
    // 用户审核
    ['user/register/check/{id}/{type}', 'userRegisterCheck'],
    // 批量用户审核
    ['user/register/batch-check/{type}', 'batchCheckRegisterUser', 'post']
];