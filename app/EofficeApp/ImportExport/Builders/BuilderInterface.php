<?php
namespace App\EofficeApp\ImportExport\Builders;
/**
 * Description of BuilderInterface
 *
 * @author lizhijun
 */
interface BuilderInterface 
{
    public function generate();
    public function setTitle($title);
    public function setData($data);
    public function setSuffix($suffix);
}
