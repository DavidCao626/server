<?php
namespace App\EofficeApp\ImportExport\Builders;

use App\EofficeApp\ImportExport\Builders\BaseBuilder;
use App\EofficeApp\ImportExport\Builders\BuilderInterface;
/**
 * Description of OtherBuilder
 *
 * @author lizhijun
 */
class OtherBuilder extends BaseBuilder implements BuilderInterface
{
    private $suffix = 'txt';
    private $data;
    private $title;
    public function generate()
    {
        $fileName = str_replace(['/', '\\', '"', "'"], ['_', '_', '_', '_'], $this->makeExportFileName($this->title));
        
        $file = $this->createExportFile($fileName, $this->suffix);

        if (is_array($this->data)) {
            $this->data = var_export($this->data, true);
        }

        file_put_contents($file, $this->data);
        
        $this->data = null; //手动释放内存
        
        return [$fileName, $file];
    }

    public function setData($data) 
    {
        $this->data = $data;
        return $this;
    }

    public function setTitle($title) 
    {
        $this->title = $title;
        return $this;
    }

    public function setSuffix($suffix) 
    {
        $this->suffix = $suffix;
        return $this;
    }

}
