<?php
$routeConfig = [
	['birthday', 'getBirthdayList', [108]],
	['birthday/add', 'addBirthday', 'post', [108]],
	['birthday/set', 'birthdaySet', 'post', [108]],
	['birthday/get', 'birthdaySetGet', 'get'],
	['birthday/get-birthday-user', 'getBirthdayUser'],
	['birthday/{birthday_id}', 'editBirthday', 'put', [108]],
	['birthday/{birthday_id}', 'deleteBirthday', 'delete', [108]],
	['birthday/{birthday_id}', 'getOneBrithday'],
	['birthday/select/{birthday_id}', 'selectBrithday', 'post',[108]],
	['birthday/cancelselect/{birthday_id}', 'cancelSelectBrithday', 'post', [108]],
];