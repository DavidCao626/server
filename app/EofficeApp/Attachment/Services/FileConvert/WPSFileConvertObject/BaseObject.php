<?php


namespace App\EofficeApp\Attachment\Services\FileConvert\WPSFileConvertObject;


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
                $methodName = 'get'.ucfirst($propertyName);

                if (is_callable([$this, $methodName])) {
                    $propertyValueArr[$propertyName] = call_user_func([$this, $methodName]);
                }
            }, $propertyNames);
        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
        }

        return $propertyValueArr;
    }
}