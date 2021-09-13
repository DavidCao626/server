<?php

namespace App\EofficeApp\Project\NewServices\Managers;


use App\EofficeApp\Project\NewServices\Managers\RolePermission\FPAFilterFunctionsManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Arr;
class HelpersManager
{

    // 从数组中提取数据，与array_extract不同的是，key为null时返回整个数组
    public static function arrayExtractWithNull($array, $key, $default) {
        if (is_null($key)) {
            return $array;
        }
        return array_extract($array, $key, $default);
    }

    public static function getProjectConfig($key) {
        return config("project.{$key}", []);
    }

    // 单个的模型对象转换为集合再转换为单个
    public static function toEloquentCollection(&$data, callable $function) {
        $isSingle = !$data instanceof Collection;
        if (($isSingle && $data) || (!$isSingle && $data->count() > 0)) {
            $isSingle && $data = collect()->push($data);
            $data = $function($data);
            $isSingle && $data = $data->pop();
        }
    }

    /**
     * 标量转化为数组，其它非数组类型全部转化为空数组
     * @param $value
     * @return array
     */
    public static function scalarToArray($value)
    {
        is_scalar($value) && $value = [$value];
        !is_array($value) && $value = [];
        return $value;
    }

    // 验证配置的数据
    public static function testFilter(Model $data, array $filter)
    {
        if (empty($filter)) {
            return true;
        }
        $result = true;
        foreach ($filter as $field => $value) {
            $resultTemp = false;
            // 验证回调函数
            if ($field === 'callback') {
                $resultTemp = self::testFilterFromCallbacks($value, $data);
                $result = $result && $resultTemp;
                continue;
            }
            // 验证字段
            $fieldValue = object_get($data, $field);
            if (!is_array($value)) {
                $resultTemp = $fieldValue == $value;
            } else {
                $operate = Arr::get($value, 1);
                $value = Arr::get($value, 0);
                switch ($operate) {
                    case '=':
                        $resultTemp = $fieldValue == $value;
                        break;
                    case 'in':
                        $resultTemp = in_array($fieldValue, $value);
                        break;
                    default:
                        ;
                }
            }
            $result = $result && $resultTemp;
            if (!$result) {
                break;
            }
        }
        return $result;
    }

    private static function testFilterFromCallbacks($callbackNames, Model $model)
    {
        $result = true;
        if ($callbackNames && is_array($callbackNames)) {
            $class = FPAFilterFunctionsManager::class;
            foreach ($callbackNames as $callbackName) {
                if (method_exists($class, $callbackName)) {
                    $result = $class::$callbackName($model);
                } else {
                    $result = false;
                }
                if ($result === false) {
                    return $result;
                }
            }
        }
        return $result;
    }

    public static function paginate($query, DataManager $dataManager = null)
    {
        $apiBin = null;
        $dataManager && $apiBin = $dataManager->getApiBin();
        if (!$apiBin) {
            return paginate($query);
        }
        $page = $apiBin->getPage();
        $limit = $apiBin->getLimit();
        $listType = $apiBin->getListType();
        $total = null;
        $list = null;
        switch ($listType) {
            case 'normal':
                $copyQuery = (clone $query);
                $page && $copyQuery->forPage($page, $limit);
                $list = $copyQuery->get();
                $total = $query->count();
                break;
            case 'total':
                $total = $query->count();
                break;
            case 'list':
                $page && $query->forPage($page, $limit);
                $list = $query->get();
                break;
        }
        return [
            'list' => $list,
            'total' => $total,
        ];
    }

    //创建时间距离当前时间是否在$minute分钟内
    public static function testTime($createdAt, $minute = 10)
    {
        $date = date('Y-m-d H:i:s', strtotime("-{$minute}minute"));
        return $date <= $createdAt;
    }

    public static function isEmptyDate($date)
    {
        return empty($date) || $date === '0000-00-00';
    }

    public static function isEmptyDateTime($datetime)
    {
        return empty($datetime) || $datetime === '0000-00-00 00:00:00' || strtotime($datetime) === false;
    }

    public static function datetimeToDate($datetime, $emptyValue = '')
    {
        if (self::isEmptyDateTime($datetime)) {
            return $emptyValue;
        }
        return date('Y-m-d', strtotime($datetime));
    }

    // 优先从dataManager中获取，在尝试从系统token中获取
    public static function getUserId()
    {
        $userId = DataManager::getIns()->getCurUserId();
        !$userId && $userId = own('user_id');
        return $userId;
    }
}
