<?php
namespace App\EofficeApp\Base;

use Illuminate\Http\Request as baseRequest;

class Request extends baseRequest
{
    /**
     * 获取路由验证规则
     *
     * @param  array $rules 编辑数据
     *
     * @return array 验证规则
     *
     * @author xiaoding
     *
     * @since  2015-11-12
     */
    public function getRouteValidateRule($rules, $actionFunctionName)
    {
        // 取配置并返回
        if (isset($rules[$actionFunctionName])) {
            if (is_array($rules[$actionFunctionName])) {
                return $rules[$actionFunctionName];
            } else {
                return $rules[$rules[$actionFunctionName]];
            }
        } else {
            return [];
        }
    }
}

