<?php

namespace App\EofficeApp\Elastic\Services\Document\Readers;

use App\EofficeApp\Elastic\Services\Document\Contract\DocumentReaderInterface;
use PhpOffice\PhpPresentation\IOFactory;
use PhpOffice\PhpPresentation\Shape\Table;
use Illuminate\Support\Arr;
class PPTReader implements DocumentReaderInterface
{

    public function readContent($realPath)
    {
        if (file_exists($realPath)) {
            try {
                $content = '';
                $extension = Arr::get(pathinfo($realPath), 'extension');
                if ($extension == 'ppt') {
                    $pptReader = IOFactory::createReader('PowerPoint97');
                } elseif ($extension == 'pptx') {
                    $pptReader = IOFactory::createReader('PowerPoint2007');
                } else {
                    return $content;
                }
                if (!$pptReader->canRead($realPath)) {
                    return $content;
                }
                $oPHPPresentation = $pptReader->load($realPath);
                $c=$oPHPPresentation->getSlideCount();//获取幻灯片数量
                //循环处理幻灯片
                for ($i = 0; $i < $c; $i++) {
                    $slideText = '';
                    $oSlide = $oPHPPresentation->getSlide($i);
                    $sc = $oSlide->getShapeCollection();
                    $sc = $sc->getIterator();
                    //循环处理幻灯片内的文本模型：Table、RichText、Drawing、Comment
                    //图片不处理; 批注插件读取不到也不处理; table表格存在有些表格读取不到数据的问题
                    foreach ($sc as $shape) {
                        $className = get_class($shape);
                        $shapeText = '';
                        if (strpos($className, 'Table') !== false) {
                            $shapeText = $this->getTableText($shape);
                        } elseif (strpos($className, 'RichText') !== false) {
                            $shapeText = $shape->getPlainText();
                        }
                        $slideText .= ' ' . $shapeText;
                    }
                    $content .= ' ' . $slideText;
                }
                return $content;
            } catch (\Exception $e) {
                echo $e->getMessage();
            }
        }

        return '';
    }

    //获取表格模型的文本
    private function getTableText(Table $shape)
    {
        $rows = $shape->getRows();
        $tableText = "";
        foreach ($rows as $row) {
            $cells = $row->getCells();
            $rowText = '';
            foreach ($cells as $cell) {
                $rowText .= ' ' . $cell->getPlainText();
            }
            $tableText .= ' ' . $rowText;
        }

        return $tableText;
    }
}