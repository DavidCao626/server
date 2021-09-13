<?php
namespace App\EofficeApp\Document\Repositories;

use DB;

trait DocumentRepositoryTrait 
{
	public function mulitUpdate($table, $datas, $condition)
	{	
		$cols = array_keys($datas[0]);

		$query = "UPDATE " . $table . " SET "; 

        foreach ( $cols as $col ) {
        	if($col == $condition) {
        		continue;
        	}

            $query .=  $col . " = CASE ";

            foreach( $datas as $data ) {
                $query .= "WHEN " . $condition . " = " . $data[$condition] . " THEN '" . $data[$col] . "' ";
            }

            $query .= "ELSE " . $col . " END, ";
        }

        $in = "";

        foreach( $datas as $data ) {
            $in .= "'" . $data[$condition] . "', ";
        }

        $query = rtrim($query, ", ") . " WHERE " . $condition . " IN (" .  rtrim($in, ', ') . ")";
        
        return DB::update(DB::raw($query));
	}
}