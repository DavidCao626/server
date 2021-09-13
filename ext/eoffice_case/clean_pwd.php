<?php
require __DIR__ . '/../../bootstrap/app.php';

use Illuminate\Support\Facades\DB;

function cleanAdminPassword()
{
	DB::table('user')->where('user_id', 'admin')->update([
		'password' => '$1$z0moh9Zv$d.RhUeLMPke2jwndvmmqn.'
	]);
}

function setAdminPassword($password)
{
	DB::table('user')->where('user_id', 'admin')->update([
		'password'   => crypt($password, null),
		'change_pwd' => 0,
	]);
}

function getAdminAccountsName()
{
	$admin = DB::table('user')->where('user_id', 'admin')->first();
	if ($admin && isset($admin->user_name)) {
		return $admin->user_accounts;
	}
	return '';
}

// 只有提供 key为 clean_user_id value为 admin 的POST请求，才会清空admin密码
$sign  = $_REQUEST['sign'] ?? '';
$newPassword = $_REQUEST['new_password'] ?? '';
if (is_string($sign) && $sign != '' && is_string($newPassword) && $newPassword != '') {
	$currentDB = envOverload('DB_DATABASE', '', true);
	$defaultDB = envOverload('DB_DATABASE', '', false);
	// 不能修改主数据库
	if ($currentDB != $defaultDB) {
		$signValid = md5('e-office' . $newPassword . date('Y-m-d'));
		if ($sign == $signValid) {
			setAdminPassword($newPassword);
			$adminName = getAdminAccountsName();
			
			// 设置 不是第一次登录
			set_system_param('first_login', 1);

			echo json_encode([
				'status' => 1,
				'data'   => [
					'admin_name'     => $adminName,
					'admin_password' => $newPassword,
				],
			]);
			die;
		}
	}
}

header("status: 404 Not Found");
