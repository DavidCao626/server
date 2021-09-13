<?php
namespace App\EofficeApp\LogCenter\Changes;
/**
 * Description of Change
 *
 * @author lizhijun
 */
interface ChangeInterface 
{
    public function fields();
//    public function currentData($id); //这个方法废弃了
    public function parseData($data, $logData = []);
}
