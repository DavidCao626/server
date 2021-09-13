<?php


namespace App\EofficeApp\Salary\Enums;

/**
 * 数字类型薪酬项格式化类型
 */
final class DecimalFormat extends BaseEnums
{
    const ROUND = 1;        // 四舍五入

    const FLOOR = 2;        // 向下取整

    const CEILING = 3;      // 向上取整

}
