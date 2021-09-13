<?php
namespace App\EofficeApp\Attendance\Traits;
use Cache;
use DB;
/**
 * Description of AttendanceParamsTrait
 *
 * @author lizhijun
 */
trait AttendanceParamsTrait 
{
    /**
     * @获取考勤基础参数
     * @param type $key
     * @param type $default
     * @return array|string 系统参数
     */
    public function getSystemParam($key = null, $default = '')
    {
        if (is_null($key)) {
            return DB::table('attend_set_base')->get();
        }
        $paramValue = ecache('Attendance:AttendSetBase')->get($key);
        if(!$paramValue){
            $param = DB::table('attend_set_base')->where('param_key', $key)->get();
            if (count($param)) {
                $_param = $param[0];
                $paramValue = $_param->param_value;
            }
            if ($paramValue == '') {
                return $default;
            }
            
            if (is_numeric($default)) {
                $paramValue = intval($paramValue);
            }
            ecache('Attendance:AttendSetBase')->set($key, $paramValue);
        }
        return $paramValue;
    }
    /**
     * 设置考勤基础参数
     * @param type $key
     * @param type $value
     * @return boolean
     */
    public function setSystemParam($key = null, $value = '')
    {
        if (is_null($key)) {
            return false;
        }

        if (DB::table('attend_set_base')->where('param_key', $key)->count() == 0) {
            DB::table('attend_set_base')->insert(['param_key' => $key, 'param_value' => $value]);
        } else {
            DB::table('attend_set_base')->where('param_key', $key)->update(['param_value' => $value]);
        }
        ecache('Attendance:AttendSetBase')->set($key, $value);
        return true;
    }
}
