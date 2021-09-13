<?php
namespace App\EofficeApp\System\CustomFields\Repositories;

use DB;
class CustomTableConfigRepository
{
	public function __construct() 
	{
	}
	
	public function getConfigInfo($tableName)
	{
		return DB::table('custom_table_config')->where('table_key', $tableName)->first();
	}
    public function getCustomTables($module)
    {
        return DB::table('custom_table_config')->where('module', $module)->get();
    }
    public function insertData($data)
    {
       return DB::table('custom_table_config')->insert($data);
    }

    public function deleteById($id)
    {
       return DB::table('custom_table_config')->where('table_id', '=', $id)->delete();
    }
}
