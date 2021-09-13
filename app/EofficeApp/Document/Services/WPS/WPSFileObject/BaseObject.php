<?php


namespace App\EofficeApp\Document\Services\WPS\WPSFileObject;


use Illuminate\Support\Facades\Log;

class BaseObject
{
    /**
     * @var string $className
     */
    private $className;

    public function __construct($className = '')
    {
        $this->className = $className ?: self::class;
    }

    /**
     * 将对象转为数组
     *
     * @return array
     *
     * @throws \ReflectionException
     */
    public function convertToArray()
    {
        $propertyValueArr = [];
        try {
            $ref = new \ReflectionClass($this->className);
            $properties = $ref->getProperties();
            $propertyNames = array_column($properties, 'name');

            array_map(function ($propertyName) use (&$propertyValueArr) {
                // 命名方式转为驼峰
                $camelName = $this->toCamelCase($propertyName);
                $methodName = 'get'.ucfirst($camelName);

                if (is_callable([$this, $methodName])) {
                    $propertyValueArr[$propertyName] = call_user_func([$this, $methodName]);
                }
            }, $propertyNames);
        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
        }

        return $propertyValueArr;
    }

    /**
     * 将下划线命名改为驼峰
     *
     * @param string $name
     * @return string
     */
    public function toCamelCase($name): string
    {
        $array = explode('_', $name);
        $init = $array[0];
        $len = count($array);
        if ($len > 1) {
            for ($i = 1; $i < $len; $i++) {
                $init .= ucfirst($array[$i]);
            }
        }

        return $init;
    }
}