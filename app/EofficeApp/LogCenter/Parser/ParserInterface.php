<?php
namespace App\EofficeApp\LogCenter\Parser;
/**
 * 每个实现该接口的类命名规则 module_key . ContentParser
 * Interface ParserInterface
 * @package App\EofficeApp\LogCenter\Parser
 */
interface ParserInterface
{
    public function parseContentData(&$data);
}
