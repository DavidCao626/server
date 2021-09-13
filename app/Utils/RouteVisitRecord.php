<?php 

namespace App\Utils;

use Request;
use App\EofficeApp\System\Route\Entities\RouteVisitRecordEntity;

class RouteVisitRecord
{
    /**
     * 保存路由访问记录
     * @return bool
     */    
    public function saveRouteVisitRecord()
    {
        $route = Request::route()->uri();
        $method = Request::method();
        $tableObj = new RouteVisitRecordEntity();

        $where = [
            'route' => [$route],
            'method' => [$method]
        ];

        $visit_times = $tableObj->wheres($where)->count();

        if ($visit_times > 0) {
            $tableObj->wheres($where)->increment('visit_times');
        } else {
            $data = $where;
            $data['visit_times'] = 1;
            $tableObj->create($data);
        } 

        return true;
    }

    /**
     * 获取路由访问记录
     * @return array
     */    
    public function getRouteVisitRecord()
    {
        return (new RouteVisitRecordEntity())->get()->toArray();
    }

}