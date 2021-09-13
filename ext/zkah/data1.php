<?php
require __DIR__ . '/../../bootstrap/app.php';
use Illuminate\Support\Facades\DB;

$Id = isset($_REQUEST["DATA_32"]) ? $_REQUEST["DATA_32"] : "";


//$affected = DB::update('update zzzz_flow_data_21_28 set DATA_28_8 = 1 where id = ?', ['.$Id.']);


$upda=DB::table('zzzz_flow_data_21_28')
		->where('id',$Id)
		->update(['DATA_28_8' => 1]);

echo $upda;
