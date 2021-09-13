<?php 
require __DIR__ . '/../../bootstrap/app.php';

$dingTalk = app("App\Utils\DingTalk");
$token = $dingTalk->getAccessToken();

// 获取部门列表接口
function getDingtalkDepartmentList($token,$fetchChild = 'false',$id = 1){
	// $fetchChild = true;
	// $id = ''; 不传默认为根
	$url = "https://oapi.dingtalk.com/department/list?access_token=$token&fetch_child=".$fetchChild."&id=$id";
	// $url = "https://oapi.dingtalk.com/department/list?access_token=$token&id=$id&fetch_child=false";
	// dd($url);
	$res = getHttps($url);
	echo "<pre>";
	print_r(json_decode($res,true));
}
// 获取角色列表接口
function getDingtalkRoleList($token){
	$url = "https://oapi.dingtalk.com/topapi/role/list?access_token=$token";
	$res = getHttps($url);
	echo "<pre>";
	print_r(json_decode($res,true));
}
// 获取部门下的用户列表接口
function getDingtalkDepartmentUserList($token,$departmentId,$offset=1,$size=100){
	// 此接口必须要分页，带分页参数,size最大100
	$url = "https://oapi.dingtalk.com/user/listbypage?access_token=$token&department_id=$departmentId&offset=$offset&size=$size";
	$res = getHttps($url);
	echo "<pre>";
	print_r(json_decode($res,true));
}
// 获取角色下的用户列表接口
function getDingtalkRoleUserList($token,$roleId){
	$url = "https://oapi.dingtalk.com/topapi/role/simplelist?access_token=$token";
	$param = [
		'role_id' => $roleId,
	];
	$param = json_encode($param);
	$res = getHttps($url,$param);
	echo "<pre>";
	print_r(json_decode($res,true));
}
// // 获取部门列表接口
// function getDingtalkDepartmentList($token){
// 	$url = "https://oapi.dingtalk.com/department/list?access_token=$token";
// 	$res = getHttps($url);
// 	var_dump($res);
// }

function test(){
	// $param['search'] = ["phone_number"=>["18815287550","like"]];
 //    var_dump(app("App\EofficeApp\User\Services\UserService")->userSystemList($param));
	// $data = ['role_id' => 2];
	// app("App\EofficeApp\User\Services\UserService")->userSystemList($param);

	// $param = ['search' =>['role_name' => ["OA管理员","="]]];
 //        $exitRole = app("App\EofficeApp\Role\Services\RoleService")->getLists($param);
 //        var_dump($exitRole);
	$exitRole = app("App\EofficeApp\Role\Services\RoleService")->getUserRole("WV00000240");
    var_dump($exitRole);
	// var_dump(app("App\EofficeApp\User\Repositories\UserSystemInfoRepository")->entity->first()->toArray());
}

// getDingtalkDepartmentList($token);
// getDingtalkRoleList($token);
// getDingtalkDepartmentUserList($token,'117148275');
// getDingtalkRoleUserList($token,"65078947");
test();





// 接口：
// 新建用户userSystemCreate




