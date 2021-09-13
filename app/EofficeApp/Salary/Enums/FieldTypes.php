<?php


namespace App\EofficeApp\Salary\Enums;

/**
 * 薪酬项数据类型
 */
final class FieldTypes extends BaseEnums
{
    const NUMBER = 1;       // 数字

    const STRING = 2;       // 字符

    const DATE = 3;         // 日期

    const TIME = 4;         // 时间

    const FROM_FILE = 5;    // 来自文件

}
