<?php
$routeConfig = [
	['personal-set/user-group', 'listUserGroup'],
	['personal-set/user-group', 'addUserGroup', 'post'],
	['personal-set/user-group/{groupId}', 'resetUserGroupName', 'post'],
	['personal-set/user-group/{groupId}', 'deleteUserGroup', 'delete'],
	['personal-set/user-group/select-member/{groupId}', 'selectUsersForUserGroup', 'post'],
	['personal-set/user-group/{groupId}', 'showUserGroup'],//前端存在此api，但未被调用，可能已废弃
//	['personal-set/user-group/get-user-list/{group_id}', 'showUserGroupUserList'],//可能已废弃
	['personal-set/shortcuts-run', 'listShortcutsRun'],
	['personal-set/shortcuts-run', 'addShortcutsRun', 'post'],
	['personal-set/shortcuts-run/{winId}', 'editShortcutsRun', 'post'],
	['personal-set/shortcuts-run/{winId}', 'showShortcutsRun'],
	['personal-set/shortcuts-run/{winId}', 'deleteShortcutsRun', 'delete'],
	// 获取用户信息
	['personal-set/user-info', 'getUserInfo'],
	['personal-set/user-info', 'editUserInfo', 'post'],
        ['personal-set/password/length', 'getPasswordLength', 'get'],
	['personal-set/password/modify', 'modifyPassword', 'post'],
	['personal-set/left-menu/hide/{menuHide}', 'setHideMenu'],
	['personal-set/left-menu/hide', 'getHideMenuInfo'],
	// 添加常用短语
	['personal-set/common-phrase', 'addCommonPhrase', 'post'],
	// 修改常用短语
	['personal-set/common-phrase/{phraseId}', 'editCommonPhrase', 'post'],
	['personal-set/common-phrase/{phraseId}', 'deleteCommonPhrase', 'delete'],
	['personal-set/history-common-phrase', 'listCommonPhrase'],
	['personal-set/common-phrase/{phraseId}', 'showCommonPhrase'],
	['personal-set/set-client/{fileType}', 'setClientDoc'],
	['personal-set/set-signature-picture', 'setSignaturePicture', 'post'],
	['personal-set/get-signature-picture', 'getSignaturePicture'],
	['personal-set/get-signature-picture-and-time', 'getSignaturePictureAndTime'],
	['personal-set/set-client', 'getClientDoc'],
	// 手机版，修改设置：登录后展示的页面
	['personal-set/show-page-after-login/{pageFlag}', 'setShowPageAfterLoginField', 'post'],
	// 手机版，获取设置：登录后展示的页面
	['personal-set/show-page-after-login/{userId}', 'getShowPageAfterLoginField'],

	//todolist模块
	['personal-set/to-do-list/list', 'toDoItemList'],
	['personal-set/to-do-list/list-instancy-type/{instancyType}', 'toDoItemlistByInstancy'],
	['personal-set/to-do-list/create-item', 'createToDoItem', 'post'],
	['personal-set/to-do-list/delete-item/{itemId}', 'deleteToDoItem', 'delete'],
	['personal-set/to-do-list/delete-all-item', 'deleteAllToDoItem', 'delete'],
	['personal-set/to-do-list/delete-list-instancy-type/{instancyType}', 'deleteToDoItemByInstancyType', 'delete'],
	['personal-set/to-do-list/set-item-finish/{itemId}', 'setToDoItemIsFinish', 'put'],
	['personal-set/to-do-list/drag-item/{itemId}', 'dragToDoItem', 'put'],
	['personal-set/to-do-list/edit-item/{itemId}', 'editToDoItem', 'put'],
	['personal-set/to-do-list/sort-item/{itemId}', 'toDoItemSort', 'put'],
	['personal-set/to-do-list/change-instancy-type/{itemId}', 'changeInstancyType', 'put'],
	['personal-set/password-security', 'getPasswordSecurity'],
	['personal-set/avatar/set', 'setUserAvatar', 'post'],
	['personal-set/user-avatar/{userId}', 'getUserAvatar'],
	['personal-set/user/user-avatar/{userId}', 'getUserPersonalAvatar'], // 获取用户头像返回base64
];