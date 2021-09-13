<?php
namespace App\EofficeApp\Attendance\Builders;
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of TableColumnTitleBuilder
 *
 * @author lizhijun
 */
class DataTableBuilder 
{
    private $columns = [];
    private $description =  '';
    private $descriptionHeight = null;
    private $tableStyle = '<head>
                    <style>
                    table.eoffice-excel {
                        border-color:#2319DC;
                    }
                    table tr td{
                        padding-left: 5px;
                        height:25px;
                        border-color:#2319DC;
                    }
                    table .description{
                        height: 60px;
                        font-size: 14px;
                        color:#2319DC;
                        background:#FCD3C6;
                        text-align:left;
                    }
                    table .time{
                        height: 30px;
                        font-size: 14px;
                        color:#2319DC;
                        background:#FCD3C6;
                        text-align:left;
                    }
                    table .columns{
                        height: 30px;
                        text-align: center;
                        background: #BCD6FC;
                        vnd.ms-excel.numberformat:@;
                    }
                    .data-title{
                        color: rgb(45, 183, 245);
                    }
                  </style>
              </head>';
    public function addColumn($column)
    {
        if (is_callable($column)) {
            $this->columns[] = $column();
        } else {
            $this->columns[] = $column;
        }
    }
    public function addDescription($description, $descriptionHeight = null)
    {
        $this->description = $description;
        $this->descriptionHeight = $descriptionHeight;
    }
    public function getColumns()
    {
        return $this->columns;
    }
    public function getColumnsTitleRows()
    {
        list($rowNumber, $columnNumber) = $this->parseAndCombineColumns($this->columns);
        
        return $this->combineColumnRows($this->columns, $rowNumber);
    }
    public function makeXmlExcel()
    {
        $start = '<?xml version="1.0"?>
        <?mso-application progid="Excel.Sheet"?>
        <Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"
        xmlns:o="urn:schemas-microsoft-com:office:office"
        xmlns:x="urn:schemas-microsoft-com:office:excel"
        xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"
        xmlns:html="http://www.w3.org/TR/REC-html40">
        <Styles>
        <Style ss:ID="Default" ss:Name="Normal">
         <Alignment ss:Vertical="Center"/>
         <Borders>
            <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/>
            <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/>
            <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/>
            <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/>
        </Borders>
         <Font ss:FontName="宋体" x:CharSet="134" ss:Size="12"/>
         <Interior/>
         <NumberFormat/>
         <Protection/>
        </Style>
        </Styles>';
        
        $end = "</Workbook>";
    }
    public function yieldTable($data)
    {
        $header = $this->combineHeader();
        yield $this->tableStyle . '<table class="eoffice-excel" border="1px">';
        yield $header;
        foreach ($data as $value) {
            yield $value;
        }
        yield '</table>';
    }
    public function makeTable($data)
    {
        $table = $this->tableStyle . '<table class="eoffice-excel" border="1px">';
        $table .= $this->combineHeader();
        $table .= $this->combineBody($data);
        $table .= '</table>';
        return $table;
    }
    public function combineBody($data)
    {
        $body = '';
        foreach ($data as $row) {
            $body .= $this->makeTableTr($row);
        }
        return $body;
    }
    public function makeTableTr($data, $valueKey = 'value', $columnClass = '')
    {
        if (empty($data)) {
            return '';
        }
        $columnClass = $columnClass ? ' class="' . $columnClass . '"' : '';
        $tr = '<tr' . $columnClass . '>';
        foreach ($data as $item) {
            $class = isset($item['class']) ? ' class="' . $item['class'] . '"' : '';
            $tdStyle = (isset($item['style']) && !empty($item['style'])) ? $this->makeStyle($item['style']) : '';
            $rowspan = (isset($item['rowspan']) && !empty($item['rowspan'])) ? ' rowspan="' . $item['rowspan'] . '"' : '';
            $colspan = (isset($item['colspan']) && !empty($item['colspan'])) ? ' colspan="' . $item['colspan'] . '"' : '';
            $tr .= '<td' . $class . $rowspan . $colspan . $tdStyle . '>' . $item[$valueKey] . '</td>';
        }
        $tr .= '</tr>';
        return $tr;
    }
    public function combineHeader()
    {
        list($rowNumber, $columnNumber) = $this->parseAndCombineColumns($this->columns);
        $columnRows = $this->combineColumnRows($this->columns, $rowNumber);
        $descStyle = $this->descriptionHeight ? ' style="height:' . $this->descriptionHeight . 'px"' : '';
        $header = '<tr class="description" ' . $descStyle . '><td colspan="' . $columnNumber . '">' . $this->description . '</td></tr>';
        $header .= '<tr class="time"><td colspan="' . $columnNumber . '">' . date('Y-m-d H:i:s') . '</td></tr>';
        foreach ($columnRows as $row) {
            $header .= $this->makeTableTr($row, 'title', 'columns');
        }
        return $header;
    }
    private function makeStyle($styleArray = []) 
    {
        $style = '';
        if(!empty($styleArray)){
            $style .= ' style="';
            foreach($styleArray as $key => $value) {
                $style .= str_replace('_', '-', $key) . ':' . $value . ';';
            }
        }
        return $style . '"';
    }
    private function combineColumnRows($columns, $rowNumber)
    {
        $rows = [];
        for ($i = 1; $i <= $rowNumber; $i++) {
            $rows[] = [];
        }
        $this->_combineColumnRows($rows, $columns, $rowNumber);
        return $rows;
    }
    private function _combineColumnRows(&$rows, $columns, $currentRowNumber,$level = 0) 
    {
        foreach ($columns as $column) {
            if (isset($column['children'])) {
                $copyColumn = $column;
                unset($copyColumn['children']);

                $this->_combineColumnRows($rows, $column['children'], $currentRowNumber - 1, $level + 1);
                $rows[$level][] = $copyColumn;
            } else {
                if ($currentRowNumber > 1) {
                    $column['rowspan'] = $currentRowNumber;
                }
                $rows[$level][] = $column;
            }
        }
    }
    private function parseAndCombineColumns(&$columns) 
    {
        $maxRowNumber = 1;
        $columnNumber = 0;
        foreach ($columns as &$column) {
            if (isset($column['children'])) {
                list($_rowNumber, $_colNumber) = $this->parseAndCombineColumns($column['children']);
                $rowNumber = $_rowNumber + 1;
                if($rowNumber > $maxRowNumber){
                    $maxRowNumber = $rowNumber;
                }
                $column['colspan'] = $_colNumber;
                $columnNumber += $_colNumber;
            } else {
                $columnNumber += 1;
            }
        }
        return [$maxRowNumber, $columnNumber];
    }
}
