<?php
$routeConfig = [
	['public-group', 'getPublicGroupList'],
	['public-group/manage', 'getPublicGroupManageList'],
	['public-group/add', 'addPublicGroup', 'post'],
	['public-group/{groupId}', 'editPublicGroup', 'put'],
	['public-group/{groupId}', 'deletePublicGroup', 'delete'],
	['public-group/{groupId}', 'getOnepublicGroup'],
	['public-group/get-user-list/{groupId}', 'getOnePublicGroupUserList'],//未搜索到调用记录，可能已废弃
];