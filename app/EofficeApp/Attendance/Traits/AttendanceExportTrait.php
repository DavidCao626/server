<?php
namespace App\EofficeApp\Attendance\Traits;
/**
 * Description of AttendanceParamsTrait
 *
 * @author lizhijun
 */
trait AttendanceExportTrait 
{
    private function combineBody($data)
    {
        $body = '';
        foreach ($data as $row) {
            $body .= '<tr>';
            foreach ($row as $col) {
                $class = isset($col['class']) ? ' class="' . $col['class'] . '"' : '';
                $body .= '<td' . $class . '>' . $col['value'] . '</td>';
            }
            $body .= '</tr>';
        }
        return $body;
    }
    private function combineHeader($columns, $description)
    {
        list($rowNumber, $columnNumber) = $this->parseAndCombineColumns($columns);
        $columnRows = $this->combineColumnRows($columns, $rowNumber);
        return $this->_combineHeader($columnRows, $description, $columnNumber);
    }
    private function _combineHeader($columnRows, $description, $columnNumber)
    {
        $header = '<tr class="description"><td colspan="' . $columnNumber . '">' . $description . '</td></tr>';
        $header .= '<tr class="time"><td colspan="' . $columnNumber . '">' . date('Y-m-d H:i:s') . '</td></tr>';
        foreach ($columnRows as $row) {
            $header .= '<tr class="columns">';
            foreach ($row as $column) {
                $rowspan = isset($column['rowspan']) ? ' rowspan="' . $column['rowspan'] . '"' : '';
                $colspan = isset($column['colspan']) ? ' colspan="' . $column['colspan'] . '"' : '';
                $style = isset($column['style']) ? $this->makeStyle($column['style']) : '';
                $header .= '<td' . $rowspan . $colspan . $style .'>' . $column['title'] . '</td>';
            }
            $header .= '</tr>';
        }
        return $header;
    }
    private function makeStyle($styleArray = []) {
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
