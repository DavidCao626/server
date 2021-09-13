<?php

/**
 * 角色通信控制里面设置控制字段展示
 *
 * ！！！具体展示的时候，调用 RoleCommunicateRepository->getControlFieldsByTable(roleIdArr, table)的方法获取目标角色和不可见的字段二维数组，之后自己判断
 *
 */
return [
	'user' => [
		'showName' => 'role_control_fields',
		'fields' => [
			'phone_number' => 'phone_number'
		]
	]
];
