<?php

namespace App\EofficeApp\Elastic\Services\Document\Readers;

use App\EofficeApp\Elastic\Services\Document\Contract\DocumentReaderInterface;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class PHPOfficeReader implements DocumentReaderInterface
{
    /**
     * 读取表格内容并使用空格拼接
     * @param $realPath
     * @return string
     */
    public function readContent($realPath)
    {
        if (!file_exists($realPath)) {
            return '';
        }

        ini_set('memory_limit', -1);
        try {
            $objPHPExcel = $this->getPHPExcelObj($realPath);
            $content = $this->read($objPHPExcel);
        } catch (\Exception $e) {
            return '';
        }

//        $content = '';
//        foreach ($cellValues as $value) {
//            $content .= $value . ' ';
//        }

        return $content;
    }

    /**
     * @param $realPath
     * @return null|Spreadsheet
     */
    private function getPHPExcelObj($realPath)
    {
        $type = strtolower(pathinfo($realPath, PATHINFO_EXTENSION));

        //根据不同类型分别操作
        $objPHPExcel = null;
        if ($type == 'xlsx' || $type == 'xls') {
            $objPHPExcel = IOFactory::load($realPath);
        } else if ($type == 'csv') {
            $objReader = IOFactory::createReader('CSV')
                ->setDelimiter(',')
                ->setInputEncoding('GBK')//不设置将导致中文列内容返回boolean(false)或乱码
                ->setEnclosure('"')
//                ->setLineEnding("\r\n")
                ->setSheetIndex(0);
            $objPHPExcel = $objReader->load($realPath);
        }

        return $objPHPExcel;
    }

    private function read($phpExcelObj)
    {
        gc_enable();
        $sheetCount = $phpExcelObj->getSheetCount();
        $content = '';
        //遍历sheet
        for ($i = 0; $i < $sheetCount; $i++) {
            $sheet = $phpExcelObj->getSheet($i);
            $content .= $this->getSheetData($sheet).' ';

        }
        gc_collect_cycles();

        return $content;
    }

    /**
     * 获取指定sheet中的内容
     */
    private function getSheetData($sheet)
    {
        $content = '';
        $highestRowNum = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();
        $highestColumnNum = Coordinate::columnIndexFromString($highestColumn);
        //遍历行与列读取单元格
        for ($i = 1; $i <= $highestRowNum; $i++) {
            for ($j = 0; $j < $highestColumnNum; $j++) {
                $cellName = Coordinate::stringFromColumnIndex($j) . $i;
                $cellVal = $sheet->getCell($cellName)->getValue();
                if ($cellVal) {
                    $content .= $cellVal.' ';
//                        yield $cellVal;
                }
            }
        };

        return $content;
    }
}