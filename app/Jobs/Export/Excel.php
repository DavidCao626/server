<?php

namespace App\Jobs\Export;

use App\Jobs\Export\Base;
use App\Utils\ImportExportFliter;
use \PhpOffice\PhpSpreadsheet\Cell\Cell;
use \PhpOffice\PhpSpreadsheet\Spreadsheet;
use \PhpOffice\PhpSpreadsheet\Style\Alignment;
use \PhpOffice\PhpSpreadsheet\Style\Color;
use \PhpOffice\PhpSpreadsheet\Style\Fill;
use \PhpOffice\PhpSpreadsheet\Style\Font;
use \PhpOffice\PhpSpreadsheet\Style\Border;

class Excel extends Base
{

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(ImportExportFliter $fliter)
    {
        $this->fliter = $fliter;
    }

    public function export($config, $data, $fileType = 'xlsx')
    {
        $title = $config['title'] ?? '';
        $fileName = $this->makeFileName($title);
        if (isset($config['fileType'])) {
            $fileType = $config['fileType'];
        }
        // ็ๆๆไปถ
        $file = $this->createFile(transEncoding($fileName, 'UTF-8'), $fileType);

        $generatorType = $config['generator_type'] ?? 1;
        if (is_string($data) || $data instanceof \Generator) {
            return $this->exportHtmlString($file, $data, $generatorType);
        }
        $startRow = empty($config['startRow']) ? 2 : $config['startRow'];
        $objPHPExcel = !isset($config['file']) ? new \PhpOffice\PhpSpreadsheet\Spreadsheet() : \PhpOffice\PhpSpreadsheet\IOFactory::load($config['file']);
        $objPHPExcel->getDefaultStyle()->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
        if (isset($data['header'])) {
            $data['sheetName'] = $data['sheetName'] ?? $title;
            $objPHPExcel = $this->writeDataInSheet($objPHPExcel, $data, 0, $startRow);
        } else {
            $i = 0;
            foreach ($data as $k => $v) {
                if (isset($v['sheetName']) && isset($v['header'])) {
                    $objPHPExcel = $this->writeDataInSheet($objPHPExcel, $v, $i, $startRow);
                    $objPHPExcel->createSheet();
                    $i++;
                }
            }
            if ($i > 0) {
                $objPHPExcel->setActiveSheetIndex(0);
            }
        }
        $objWriter = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($objPHPExcel, 'Xlsx');
        $file = empty($config['file']) ? $file : $config['file'];
        $objWriter->save($file);
        if ($objPHPExcel instanceof \PhpOffice\PhpSpreadsheet\Spreadsheet) {
            $objPHPExcel->disconnectWorksheets();
        }
        return [
            'file_name' => $fileName,
            'file_type' => $fileType,
        ];
    }

    private function exportHtmlString(string $file, $data, $generatorType)
    {
        $beforeHtml = $this->getExportHtmlString();
        $afterHtml = "</body></html>";
        if ($data instanceof \Generator) {
            $beforeHtml = $generatorType ? $this->getExportXmlString() : $this->getExportHtmlString();
            $afterHtml = $generatorType ? "</Workbook>" : "</body></html>";
            file_put_contents($file, $beforeHtml);
            foreach ($data as $key => $value) {
                file_put_contents($file, $value, FILE_APPEND);
            }
            file_put_contents($file, $afterHtml, FILE_APPEND);
        } else {
            $data = $beforeHtml . "{$data}{$afterHtml}";
            file_put_contents($file, $data);
        }
        $filePaths = pathinfo($file);
        return [
            'file_name' => $filePaths['filename'] ?? '',
            'file_type' => $filePaths['extension'] ?? '',
        ];
    }

    public function exportTest()
    {

    }

    private function setExportModelHeader($objPHPExcelSheet, $headers)
    {
        $i = 1;
        $j = 1;
        $isMerge = false;
        $style =['width' => '25', 'height' => '25','borders' => 'solid', 'backgroundColor' => '1B9BCA', 'fontColor' => 'FFFFF', 'fontSize' => '14', 'fontWeight' => 'bold', ];
        //ๅคๆญๅฆๆๅคด้จๆๆ็ป ้ฃไนๅถไป็่กไน่ฆๅๅนถ
        foreach ($headers as $key => $val) {
            if(isset($val['children']) && !empty($val['children'])){
                $isMerge = true;
            }
        }
        foreach ($headers as $key => $val) {
            if (is_array($val)) {
                 if (isset($val['style'])) {
                    $style = array_merge($style,$val['style']);
                }   
                $headerVal = $val["data"] ?? '';
                if (isset($val['_RANGE_'])) {
                    // ๅๅนถๅๅๆ?ผ(่ช้ฌๅฏผๅฅๆจกๆฟ)
                    $objPHPExcelSheet->setCellValueByColumnAndRow($val['_RANGE_'][0], $val['_RANGE_'][1], $headerVal);
                    $objPHPExcelSheet->mergeCellsByColumnAndRow($val['_RANGE_'][0], $val['_RANGE_'][1], $val['_RANGE_'][2], $val['_RANGE_'][3]);
        		    $this->setColumnAndRowStyle($objPHPExcelSheet, $style, [$val['_RANGE_'][0], $val['_RANGE_'][1]]); 
                    $this->setColumnAndRowStyle($objPHPExcelSheet, $style, [$val['_RANGE_'][2], $val['_RANGE_'][3]]);
                } else if(array_key_exists('children',$val)){
                    $objPHPExcelSheet->setCellValueByColumnAndRow($j, $i, $headerVal);
                    $childColumn = $j; //ๅ
                    $this->setColumnAndRowStyle($objPHPExcelSheet, $style, [$j, $i]);
                    foreach ($val['children'] as $k => $v) {
                        $objPHPExcelSheet->setCellValueByColumnAndRow($j, $i+1, $v['data']);
                        $this->setColumnAndRowStyle($objPHPExcelSheet, $style, [$j, $i+1]);
                        $j++;
                    }
                    $j = $j-1;
                    $objPHPExcelSheet->mergeCellsByColumnAndRow($childColumn,$i,$j,$i);
                    //ๅๅนถๅๅๆ?ผ ๅฌๅฑ
                }else {
                    if($isMerge){
                        $objPHPExcelSheet->setCellValueByColumnAndRow($j, $i, $headerVal);
                        $objPHPExcelSheet->mergeCellsByColumnAndRow($j,$i,$j,$i+1);
                        $this->setColumnAndRowStyle($objPHPExcelSheet, $style, [$j, $i]);
                        $this->setColumnAndRowStyle($objPHPExcelSheet, $style, [$j, $i+1]);
                    } else {
                        $objPHPExcelSheet->setCellValueByColumnAndRow($j, $i, $headerVal);
        		    	$this->setColumnAndRowStyle($objPHPExcelSheet, $style, [$j, $i]);
        		    }
                }
            } else {
                $objPHPExcelSheet->setCellValueByColumnAndRow($j, $i, $val);
                $this->setColumnAndRowStyle($objPHPExcelSheet, $style, [$j, $i]); 
            }
            $j++;
        }
        $startRowAdd = $isMerge?1:0;
        return  ['startRowAdd'=>$startRowAdd];
    }
    public function borders($objPHPExcelSheet, $colorRGB, $positionInfo)
    {
        $styleArray = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => [
                        'argb' => 'DCDCDC',
                    ]
                ]
            ]
        ];
        return $objPHPExcelSheet->getStyleByColumnAndRow($positionInfo[0], $positionInfo[1])->applyFromArray($styleArray);
    }

    private function parseOtherFilters(array $headers)
    {
        $sortFilters = $dataFilters = $cssFilters = [];
        $sort = 0;
        $beforeChildren = 0;
        foreach ($headers as $field => $value) {
            $sort += $beforeChildren;
            if (strpos($field, '|') !== false) {
                [$fieldsKey, $fieldsValue] = explode('|', $field);
                $dataFilters[$fieldsKey] = $fieldsValue;
                $field = $fieldsKey;
                if(isset($value['children'])){
                    $sortFilters[$field]  = [
                        'children' => $value['children'],
                        'sort' => $sort
                    ];
                }else{
                    $sortFilters[$field] = $sort;
                }
            } else {
                if (isset($value['_MERGE_'])) {
                    continue;
                }
                if(isset($value['children'])){
                    $sortFilters[$field]  = [
                        'children' => $value['children'],
                        'sort' => $sort
                    ];
                }else{
                    $sortFilters[$field] = $sort;
                }
            }
           if(isset($value['children'])){
                $beforeChildren = count($value['children']) - 1;
           }else{
                $beforeChildren = 0;
           }
            if (isset($value['style']) && !isset($value['style']['header'])) {
                if (isset($value['style']['height'])) {
                    unset($value['style']['height']);
                }
                $cssFilters[$field] = $value['style'];
            }
            $sort++;
        }
        return [$sortFilters, $dataFilters, $cssFilters];
    }

    private function newWriteContent(string $func, string $content)
    {
        static $caches = [];
        if (!isset($caches[$func][$content])) {
            try {
                $caches[$func][$content] = $this->fliter->$func($content);
            } catch (\Exception $e) {
                $caches[$func][$content] = '';
            }
        }
        return $caches[$func][$content];
    }

    public function writeDataInSheet($objPHPExcel, $data, $index = 0, $startRow = 2)
    {
        $headers = $data['header'] ?? [];
        $datas = $data['data'] ?? [];
        if (empty($headers)) {
            return $objPHPExcel;
        }
        $offset = [];
        $objPHPExcelSheet = $objPHPExcel->setActiveSheetIndex($index);
        if ($startRow == 2 || $startRow == 3) {
            $headersExport = $this->setExportModelHeader($objPHPExcelSheet, $headers);
        }
        if (!empty($datas) && is_array($datas)) {
            [$sortFilters, $dataFilters, $cssFilters] = $this->parseOtherFilters($headers);
            $cacheFilterKeyValueArr = [];
            $i = $startRow + $headersExport['startRowAdd'];
            foreach ($datas as $key => $value) {
                if (!is_array($value) || empty($value)) {
                    continue;
                }
                $mergeRow = $value['_DIMENSION'] ?? 1;
                //ๅฆๆๅญๅจๆ็ป ๅค่กๅๅนถ ๅๆๅคงๅๆฐ่ฟ่กๅๅนถ
                foreach ($value as $field => $content) {
                    if(isset($content['merge_data'])){
                        $mergeRow = count($content['merge_data'])>$mergeRow?count($content['merge_data']):$mergeRow;
                    }
                }
                $columnAdd = 1;
                foreach ($value as $field => $content) {
                    // if ((isset($content['data']) && is_array($content['data'])) || (!isset($content['data']) && is_array($content)) || preg_match('/^_/', $field)) {
                    //     continue;
                    // }
                    if(is_array($content)){
                        if (isset($content['data'])) {
                            $writeContent = is_array($content['data']) ? json_encode($content['data'], JSON_UNESCAPED_UNICODE) : $content['data'];
                        } else {
                            $writeContent = json_encode($content, JSON_UNESCAPED_UNICODE);
                        }
                    } else {
                        $writeContent = $content;
                    }
                    if (isset($dataFilters[$field])) {
                        // ้่ฆ่ฟๆปค
                        $writeContent = $this->newWriteContent($dataFilters[$field], $writeContent);
                    }
                    if(isset($sortFilters[$field]) && is_array($sortFilters[$field])){
                        $column = $sortFilters[$field]['sort'] ?? null; 
                    }else{
                        $column = $sortFilters[$field] ?? null;
                    }
                    if ($column === null) {
                        continue;
                    }
                    $column = $column + 1;
                    $columnString = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($column);
//                    $writeContent = strval($writeContent);
                    //้ไปถๅฏผๅบ
                    $this->exportAttachment($content,$columnString,$i,$objPHPExcel);
                    // ๅค่กๅๅนถ
                    if (!isset($content['merge_data'])) {
                        // ๅฆๆๆฏ้ไปถๅๅฏผๅบๅๅฎน่ฎพ็ฝฎไธบ็ฉบ
                        if(isset($content['type']) && $content['type'] === 'attachement') {
                            $writeContent = '';
                        }
                        if ($mergeRow == 1) {
                            $objPHPExcelSheet->setCellValueByColumnAndRow($column, $i, $writeContent);
                        } else {
                            $objPHPExcelSheet->mergeCellsByColumnAndRow($column, $i, $column, ($i + $mergeRow - 1))->setCellValueByColumnAndRow($column, $i, $writeContent);
                            $objPHPExcel->getDefaultStyle()->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
                        }
                    } else {
                        $children = $headers[$field]['children'];
                        $childrenFields = array_keys($children);
                        foreach ($childrenFields as $childField) {
                            $innerRow = $i;
                            $currentColumnString = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($column);
                            foreach ($content['merge_data'] as $index => $childData) {
                                $childData = (array)$childData;
                                if(is_string($childData[$childField])){
                                    $objPHPExcelSheet->setCellValueByColumnAndRow($column, $innerRow,$childData[$childField]);
                                    $innerRow++;
                                }else{
                                    $this->exportAttachment($childData[$childField],$currentColumnString,$innerRow,$objPHPExcel);
                                    $innerRow++;
                                }
                            }
                            ++$column;
                        }
                    }

                    if (is_array($content) && isset($content['link'])) {
                        $objPHPExcelSheet->getCell($columnString . $i)->getHyperlink()->setUrl($content['link']);
                        $objPHPExcelSheet->getStyle($columnString . $i)->getFont()->setUnderline(\PhpOffice\PhpSpreadsheet\Style\Font::UNDERLINE_SINGLE);
                        $objPHPExcelSheet->getStyle($columnString . $i)->getFont()->getColor()->setARGB(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_BLUE);
                    }
                    $tempStyles = [];
                    if (is_array($content) && isset($content['style'])) {
                        $tempStyles = $content['style'];
                    }
                    if (isset($cssFilters[$field])) {
                        $tempStyles = array_unique(array_merge($tempStyles, $cssFilters[$field]));
                    }
                    if (!empty($tempStyles)) {
                        // ็จๆทๅฏผๅบๆจกๆฟไฟฎๆน--ๆๆญ, ่ฟ้ๆๅญไฝ้ข่ฒๅๅคด้จ่ๆฏ่ฒ้ฝ็ป่กๅญๆฎต่ฎพ็ฝฎไบ
                        unset($tempStyles['backgroundColor']);
                        unset($tempStyles['fontColor']);
                        $this->setColumnAndRowStyle($objPHPExcelSheet, $tempStyles, [$column, $i]);
                    }
                    if(isset($sortFilters[$field]) && is_array($sortFilters[$field])){
                        $columnAdd = count($sortFilters[$field]['children']);
                    }else{
                        $columnAdd = 1;
                    }
                }
                $i += $mergeRow;
            }
        }
        $title = (isset($data['sheetName']) && !empty($data['sheetName'])) ? mb_substr($data['sheetName'], 0, 31) : 'sheetName';
        $invalidCharacters = $objPHPExcel->getActiveSheet()->getInvalidCharacters();
        $invalidCharacters = array_merge($invalidCharacters, ["๏ผ", "๏ผ", "\/"]);
        $title = str_replace($invalidCharacters, '', $title);
        $objPHPExcel->getActiveSheet()->setTitle($title);
        return $objPHPExcel;
    }

    public function exportAttachment($content,$columnString,$i,$objPHPExcel)
    {
        if (isset($content['type']) && $content['type'] == 'attachement' && !empty($content['data'])) {
            $attachmentIds = explode(",", $content['data']);
            foreach ($attachmentIds as $attachmentId) {
                $writeContent = '';
                $content = '';
                $attachment = app('App\EofficeApp\Attachment\Services\AttachmentService')->getOneAttachmentById($attachmentId);
                $attachment['attachment_type'] = isset($attachment['attachment_type']) ? strtolower($attachment['attachment_type']) : '';
                if (isset($attachment['attachment_type']) && in_array($attachment['attachment_type'], ['jpg', 'png', 'gif', 'jpeg', 'jfif', 'bmp'])) {
                    if (isset($attachment['temp_src_file']) && is_file($attachment['temp_src_file'])) {
                        $objDrawing = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
                        $objDrawing->setPath($attachment['temp_src_file']); //ๅพ็ๅผๅฅไฝ็ฝฎ
                        $imageInfo = getimagesize($attachment['temp_src_file']);
                        $width = $imageInfo[0];
                        $height = $imageInfo[1];
                        $proportion = $height / 50;
                        $newWidth = ceil($width / $proportion);
                        if (isset($offset[$columnString . "-" . $i])) {
                            $objDrawing->setOffsetX($offset[$columnString . "-" . $i]);
                            $offset[$columnString . "-" . $i] += $newWidth;
                        } else {
                            $offset[$columnString . "-" . $i] = $newWidth;
                        }
                        $objDrawing->setCoordinates($columnString . $i); //ๅพ็ๆทปๅ?ไฝ็ฝฎ
                        $objDrawing->setHeight(50);
                        // $objDrawing->setWidth(50);
                        $objDrawing->setWorksheet($objPHPExcel->getActiveSheet());
                        $objPHPExcel->getActiveSheet()->getRowDimension($i)->setRowHeight(50);
                    }
                }
            }
            if (!empty($offset)) {
                //่ฎพ็ฝฎๆๅพ็็excelๅฎฝๅบฆ
                $columnArr = [];
                foreach ($offset as $key => $value) {
                    $offsetColumn = explode("-", $key);
                    if (isset($columnArr[$offsetColumn[0]])) {
                        if ($value > $columnArr[$offsetColumn[0]]) {
                            $columnArr[$offsetColumn[0]] = $value;
                        }
                    } else {
                        $columnArr[$offsetColumn[0]] = $value;
                    }
                    //ๆๅๆ้ฟๅฎฝๅบฆไฝไธบๆดๅๅฎฝๅบฆ
                    //ๅฎฝๅบฆ็ฎๅบๆฅ ็ญๆฏ็ผฉๆพๅๆฏๅ็ด? excel่ฎพ็ฝฎๅฎฝๅบฆๆฏๅญ็ฌฆ
                    $objPHPExcel->getActiveSheet()->getColumnDimension($offsetColumn[0])->setWidth($columnArr[$offsetColumn[0]] / 7);
                }
            }
        }
    }

    public function handleField($key, $data)
    {
        if (strpos($key, '|') === false) {
            return ['data' => isset($data[$key]) ? $data[$key] : ''];
        }

        $filters = explode('|', $key);
        $fieldName = array_shift($filters);
        return [
            'data' => isset($data[$fieldName]) ? $data[$fieldName] : '',
            'filter' => $filters,
        ];
    }

    public function setColumnAndRowStyle($objPHPExcelSheet, $styles, $positionInfo)
    {
        foreach ($styles as $style => $styleValue) {
            if (method_exists($this, $style)) {
                $this->$style($objPHPExcelSheet, $styleValue, $positionInfo);
            }
        }
        return $objPHPExcelSheet;
    }

    /**
     * ๆ็งx yๅๆ?็ๆนๅผ่ฎพ็ฝฎๅๅๆ?ผ็้ข่ฒ
     * @param  [type] $objPHPExcelSheet [Sheetๅฏน่ฑก]
     * @param  [type] $colorRGB         [้ข่ฒ็rgbๅผ๏ผไธๅธฆ#]
     * @param  [type] $positionInfo     [ๅๅๆ?ผๅๆ?]
     * @return [type]                   [description]
     */
    public function backgroundColor($objPHPExcelSheet, $colorRGB, $positionInfo)
    {
        return $objPHPExcelSheet->getStyleByColumnAndRow($positionInfo[0], $positionInfo[1])->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB($colorRGB);
    }

    public function fontColor($objPHPExcelSheet, $colorRGB, $positionInfo)
    {
        return $objPHPExcelSheet->getStyleByColumnAndRow($positionInfo[0], $positionInfo[1])->getFont()->getColor()->setRGB($colorRGB);
    }
    public function height($objPHPExcelSheet, $heightValue, $positionInfo)
    {
        if ($heightValue) {
            return $objPHPExcelSheet->getRowDimension($positionInfo[1])->setRowHeight($heightValue);
        }
    }
    public function width($objPHPExcelSheet, $widthValue, $positionInfo)
    {
        // return $objPHPExcelSheet->getColumnDimension(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($positionInfo[0]))->setWidth($widthValue);
        if ($widthValue == "autosize") {
            return $objPHPExcelSheet->getColumnDimensionByColumn($positionInfo[0])->setAutoSize(true);
        } else {
            return $objPHPExcelSheet->getColumnDimensionByColumn($positionInfo[0])->setWidth($widthValue);
        }
    }
    public function format($objPHPExcelSheet, $formatFlag, $positionInfo)
    {
        $formatFlag = $formatFlag ? $formatFlag : "FORMAT_TEXT";
        return $objPHPExcelSheet->getStyleByColumnAndRow($positionInfo[0], $positionInfo[1])->getNumberFormat()->setFormatCode(constant("\PhpOffice\PhpSpreadsheet\Style\NumberFormat::" . $formatFlag));
    }

    public function getExportXmlString()
    {
        return <<<DOCHTML
<?xml version="1.0" encoding="utf-8"?>
<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet" xmlns:html="http://www.w3.org/TR/REC-html40">
<Styles>
<Style ss:ID="Default" ss:Name="Normal">
<Alignment ss:Vertical="Center"/>
<Borders/>
<Font ss:FontName="" x:CharSet="134" ss:Size="11" ss:Color="#000000"/>
<Interior/>
<NumberFormat/>
<Protection/>
</Style>
<Style ss:ID="CellWrapText"><Alignment ss:WrapText="1"/></Style>
<Style ss:ID="Headercell"><Font ss:Color="#FFFFFF" ss:Size="11"/><ss:Alignment/><ss:Interior ss:Pattern="Solid" ss:Color="#31309c"/></Style>
</Styles>
DOCHTML;
    }

    public function getExportHtmlString()
    {
        return <<<DOCHTML
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" lang="zh-CN">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<body>
DOCHTML;
    }
}
