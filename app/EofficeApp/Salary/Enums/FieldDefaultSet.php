<?php


namespace App\EofficeApp\Salary\Enums;

/**
 * 薪酬项数据源类型
 */
final class FieldDefaultSet extends BaseEnums
{
    const DEFAULT_VALUE = 1;        // 默认值

    const FORMULA = 2;              // 计算

    const SYSTEM_DATA = 3;          // 系统数据

    const TAX = 4;                  // 税收

    const LAST_REPORT_DATA = 5;     // 上月数据

}
