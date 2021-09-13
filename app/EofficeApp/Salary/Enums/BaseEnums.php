<?php


namespace App\EofficeApp\Salary\Enums;


use ReflectionClass;

abstract class BaseEnums
{

    public static function getConstList(): array
    {
        $reflection = new ReflectionClass(static::class);

        return $reflection->getConstants();
    }

    /**
     * 检验值是否合法
     * @param $value
     * @return bool
     */
    public static function isValidValue($value): bool
    {
        $values = array_values(static::getConstList());

        return in_array($value, $values);
    }

}
