<?php
namespace App\EofficeApp\Product\Requests;

use App\EofficeApp\Base\Request;

/**
 * 产品管理模块验证
 *
 * @author  牛晓克
 *
 * @since  2017-12-12 创建
 */
class ProductRequest extends Request
{
    public function rules($request)
    {
        $rules = [
            "addProductType"     => [
                "parent"    => "required",
                "type_name" => "required",
            ],
            "checkProductNumber" => [
                "product_number" => "required",
            ],
        ];

        $function = explode("@", $request->route()[1]['uses'])[1];
        return $this->getRouteValidateRule($rules, $function);
    }

}
