<?php

namespace App\EofficeApp\Elastic\Services\Document;

use App\EofficeApp\Elastic\Services\Document\Contract\DocumentReaderInterface;
use Illuminate\Support\Arr;
class ReaderFactory
{
    static $objectTree = [];
    static $types = [
        'txt' => 'txtReader',
        'xls' => 'phpOfficeReader',
        'xlsx' => 'phpOfficeReader',
        'csv' => 'phpOfficeReader',
        'docx' => 'phpWorldReader',
        'doc' => 'phpWorldReader',
        'pdf' => 'pdfReader',
        'image' => 'imageReader',
        'ppt' => 'pptReader',
        'pptx' => 'pptReader',
    ];
    static $classes = [
        'txtReader' => 'App\EofficeApp\Elastic\Services\Document\Readers\TxtReader',
        'phpOfficeReader' => 'App\EofficeApp\Elastic\Services\Document\Readers\PHPOfficeReader',
        'phpWorldReader' => 'App\EofficeApp\Elastic\Services\Document\Readers\PHPWorldReader',
        'pdfReader' => 'App\EofficeApp\Elastic\Services\Document\Readers\PdfReader',
        'imageReader' => 'App\EofficeApp\Elastic\Services\Document\Readers\ImageReader',
        'pptReader' => 'App\EofficeApp\Elastic\Services\Document\Readers\PPTReader',
    ];

    /**
     * 根据文件类型获取文件读取对象
     * @param $type
     * @return bool|DocumentReaderInterface
     */
    public static function getReader($type)
    {
        $classType = Arr::get(self::$types, $type);
        if ($classType) {
            return self::getObj($classType, self::$classes[$classType]);
        }

        return false;
    }

    /**
     * 获取文件读取对象
     * @param $type
     * @param $classPath
     * @return \Laravel\Lumen\Application|mixed
     */
    private static function getObj($type, $classPath)
    {
        $obj = Arr::get(self::$objectTree, $type);
        if (!$obj) {
            $obj = app($classPath);
            self::$objectTree[$type] = $obj;
        }

        return $obj;
    }
}