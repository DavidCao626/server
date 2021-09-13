<?php

use Illuminate\Support\Facades\DB;

require __DIR__ . '/../../bootstrap/app.php';

$currentDB = envOverload('DB_DATABASE', '', true);
$defaultDB = envOverload('DB_DATABASE', '', false);
// 不能修改主数据库
if ($currentDB == $defaultDB) {
    header("status: 404 Not Found");
    die;
}

$valid = $_REQUEST['valid'] ?? '';
if ($valid != md5('eoffice-' . date('Y-m-d H'))) {
    header("status: 404 Not Found");
    die;
}

$adminPassword = $_REQUEST['admin_password'] ?? '';
$adminUserName = $_REQUEST['admin_user_name'] ?? '';
$adminMobile   = $_REQUEST['admin_mobile'] ?? '';
$companyName   = $_REQUEST['company_name'] ?? '';

// admin密码
if (is_string($adminPassword) && $adminPassword != '') {
    DB::table('user')->where('user_id', 'admin')->update([
        'password'   => crypt($adminPassword, null),
        'change_pwd' => 0,
    ]);
}
// admin用户名
if (is_string($adminUserName) && $adminUserName != '') {
    DB::table('user')->where('user_id', 'admin')->update([
        'user_name'   => $adminUserName,
    ]);
}
if (is_numeric($adminMobile)) {
    DB::table('user_info')->where('user_id', 'admin')->update([
        'phone_number'   => $adminMobile,
    ]);
}

if (is_string($companyName) && $companyName != '') {
    // 公司名称
    DB::table('company_info')->where('company_id', 1)->update([
        'company_name'   => $companyName,
    ]);

    DB::table('department')->where('dept_id', 2)->update([
        'dept_name'    => '人事部',
        'dept_name_py' => 'renshibu',
        'dept_name_zm' => 'rsb',
    ]);

    // 设置logo
    DB::table('system_params')->where('param_key', 'sys_logo')->update([
        'param_value'    => $companyName,
    ]);
    DB::table('system_params')->where('param_key', 'sys_logo_type')->update([
        'param_value'    => 1,
    ]);
    // 系统标题
    DB::table('system_params')->where('param_key', 'system_title')->update([
        'param_value'    => '泛微协同办公e-office标准产品',
    ]);
}

// 设置 不是第一次登录
set_system_param('first_login', 1);

echo json_encode([
    'status' => 1
]);
die;
