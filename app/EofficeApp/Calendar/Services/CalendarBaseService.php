<?php
namespace App\EofficeApp\Calendar\Services;

use App\EofficeApp\Base\BaseService;
/**
 * Description of CalendarBaseService
 *
 * @author lizhijun
 */
class CalendarBaseService extends BaseService
{
    public function __construct()
    {
        parent::__construct();
    }
    /**
     * 验证必填字段
     *
     * @param type $data
     * @param type $key
     *
     * @return boolean
     */
    protected function validateRequired($data, $key)
    {
        return isset($data[$key]) && $data[$key] != '';
    }
    /**
     * 从数组中获取值
     *
     * @param type $key
     * @param type $data
     * @param type $default
     *
     * @return any
     */
    protected function defaultValue($key, $data, $default)
    {
        return isset($data[$key]) ? $data[$key] : $default;
    }
    /**
     * 根据月份获取该月份的日期数组
     * 
     * @param type $month
     * 
     * @return array
     */
    protected function getDateArrayByMonth(string $month)
    {
        $currentMonth = date("Y-m");
        if (!(preg_match("/^\d{4}-\d{2}$/", $month))) {
            $month = $currentMonth;
        }

        $start = date("Y-m-01", strtotime($month));
        $days = date('t', strtotime($month));
        $dateArray = array();
        for ($i = 0; $i < $days; $i++) {
            $dateArray[] = date('Y-m-d', strtotime($start) + $i * 24 * 60 * 60);
        }
        return $dateArray;
    }
    protected  function getWeekDateArray($start)
    {
        $dateArray = [];
        for ($i = 0; $i < 7; $i++) {
            $dateArray[] = date('Y-m-d', strtotime($start) + $i * 24 * 60 * 60);
        }
        return $dateArray;
    }
    protected function arrayItemMapWithKey(array $data, $key = 'calendar_id')
    {
        $map = [];
        if(!empty($data)){
            foreach ($data as $item) {
                $map[$item[$key]] = $item;
            }
        }
        return $map;
    }
    protected function arrayItemsMapWithKey(array $data, $key = 'user_id')
    {
        $map = [];
        if(!empty($data)){
            foreach ($data as $item) {
                $map[$item[$key]][] = $item;
            }
        }
        return $map;
    }
}
