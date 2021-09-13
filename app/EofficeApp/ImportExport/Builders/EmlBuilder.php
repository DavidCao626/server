<?php
namespace App\EofficeApp\ImportExport\Builders;

use App\EofficeApp\ImportExport\Builders\BaseBuilder;
use App\EofficeApp\ImportExport\Builders\BuilderInterface;
/**
 * Description of EmlBuilder
 *
 * @author lizhijun
 */
class EmlBuilder extends BaseBuilder implements BuilderInterface
{
    private $suffix = 'eml';
    private $title;
    private $data;
    public function generate()
    {
        $fileName = $this->makeExportFileName($this->title);

        $file = $this->createExportFile($fileName, $this->suffix);

        file_put_contents($file, transEncoding($this->data, 'UTF-8'));
        
        $this->data = null;
        
        return [ $fileName, $file ];
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
