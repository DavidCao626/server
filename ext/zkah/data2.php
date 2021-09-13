<?php
require __DIR__ . '/../../bootstrap/app.php';
use Illuminate\Support\Facades\DB;
echo json_encode([
["DATA_10_1"=>"aa","DATA_10_3"=>"ab","DATA_10_4"=>"ac","DATA_10_2"=>"ad"],
["DATA_10_1"=>"ba","DATA_10_3"=>"bb","DATA_10_4"=>"bc","DATA_10_2"=>"bd"],
["DATA_10_1"=>"ca","DATA_10_3"=>"cb","DATA_10_4"=>"cc","DATA_10_2"=>"cd"]
]);

die;
//父级控件ID
$control_id = "DATA_14";
$table_name = "zzzz_flow_data_13_1";
//对应关系,下拉框是array类型，第一个是类型，第二个是报销单字段，第三个是出差单字段
$exchange = [
	//客户
	['string','DATA_10_1','DATA_1_0'],
	//出发地
	['string','DATA_10_3','DATA_1_2'],
	//到达地
	['string','DATA_10_4','DATA_1_3'],
	
	//['array','DATA_10_3','DATA_1_6'],
];
$data = [];
$id = isset($_REQUEST[$control_id]) ? $_REQUEST[$control_id] : "";
$form_data  = DB::table($table_name)->where("run_id",$id)->get()->toArray();
if(count($form_data)){
	foreach($form_data as $v){
		var_dump($v);die;
		$item = [];
		foreach($exchange as $info){
			$key = $info[1];
			$value = $info[2];
			if($info[0]=='string'){
				$item[$key] = $v->$value;
			}else{
				$item[$key] = [$v->$value];
			}
		}
		$data[] = $item;
	}
}
echo json_encode($data);
exit();