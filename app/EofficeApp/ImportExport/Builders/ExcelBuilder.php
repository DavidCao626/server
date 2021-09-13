<?php
namespace App\EofficeApp\ImportExport\Builders;

use App\EofficeApp\ImportExport\Builders\BaseBuilder;
use App\EofficeApp\ImportExport\Builders\BuilderInterface;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\AdvancedValueBinder;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
/**
 * 导出Excel报表构建器
 *
 * @author lizhijun
 */
class ExcelBuilder extends BaseBuilder implements BuilderInterface
{
    private $suffix = 'xlsx';
    private $title = '';
    private $sheets = [];
    private $activeSheet = [];
    private $exportFile;
    private static $writers = [
        'xls' => 'Xls',
        'xlsx' => 'Xlsx'
    ];
    private static $valueKey = 'data';
    private static $defaultStyle = [
        'borders' => [
            'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => [
                        'argb' => '333333',
                    ]
                ]
        ],
        'font' => ['size' => 12],
    ];
    private static $bodyStyle = [
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_LEFT,
            'vertical' => Alignment::VERTICAL_CENTER,
            'indent' => 1
        ],
    ];
    private static $timeStyle = [
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_LEFT,
            'vertical' => Alignment::VERTICAL_CENTER,
            'indent' => 1
        ],
    ];
    private static $headerStyle = [
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'vertical' => Alignment::VERTICAL_CENTER,
        ],
        'fill' => [
            'fillType' => Fill::FILL_GRADIENT_LINEAR,
            'rotation' => 90,
            'startColor' => [
                'argb' => 'BCD6FC',
            ],
            'endColor' => [
                'argb' => 'BCD6FC',
            ],
        ],
        'font' => ['bold' => true]
    ];
    private static $xmlHeader = '<?xml version="1.0" encoding="utf-8"?>
        <?mso-application progid="Excel.Sheet"?>
<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet" xmlns:html="http://www.w3.org/TR/REC-html40"><Styles>
<Style ss:ID="Default" ss:Name="Normal">
<Alignment ss:Vertical="Center" ss:WrapText="1" ss:Horizontal="Left"/>
  </Style><Style ss:ID="eoffice">
  <Borders>
    <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/>
    <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/>
    <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/>
    <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/>
   </Borders>
   <Font ss:Size="14"/>
  </Style>
  <Style ss:ID="header">
    <Alignment ss:Vertical="Center" ss:Horizontal="Center"/>
    <Borders>
      <Border ss:Position="Bottom" ss:LineStyle="Continuous" ss:Weight="1"/>
      <Border ss:Position="Left" ss:LineStyle="Continuous" ss:Weight="1"/>
      <Border ss:Position="Right" ss:LineStyle="Continuous" ss:Weight="1"/>
      <Border ss:Position="Top" ss:LineStyle="Continuous" ss:Weight="1"/>
     </Borders>
     <Font ss:Size="14" ss:Bold="1"/>
    <ss:Interior ss:Pattern="Solid" ss:Color="#BCD6FC"/>
    </Style>
  </Styles>';
    private static $xmlFooter = '</Workbook>';
    private $currentSheetIndex = 0;
    private $mode = 1; // 1按key获取导出数据，2按顺序获取导出数据、且导出数据没有合并行合并列。
    const EXCEL_MAX_INT = 9999999999;
    const PRECISION = 4;
    /**
     * 设置数据前设置当前数据存放的sheetIndex
     * 
     * @param type $index
     * @return $this
     */
    public function setActiveSheet($index = 0)
    {
        $this->currentSheetIndex = $index;
        return $this;
    }
    public function setFontSize($size)
    {
        self::$defaultStyle['font']['size'] = $size;
        return $this;
    }
    /**
     * 设置当前sheet的导出数据
     * 
     * @param type $data
     * @return $this
     */
    public function setData($data) 
    {
        $this->sheets[$this->currentSheetIndex]['data'] = $data;
        return $this;
    }
    /**
     * 设置当前sheet的导出数据的Header
     * 
     * @param type $header
     * @return $this
     */
    public function setHeader($header)
    {
        $this->sheets[$this->currentSheetIndex]['header'] = $header;
        return $this;
    }
    /**
     * 设置当前sheet导出数据的描述
     * 
     * @param type $description
     * @param type $height
     * @return $this
     */
    public function setDescription($description, $height = 60) 
    {
        $this->sheets[$this->currentSheetIndex]['description'] = $description;
        $this->sheets[$this->currentSheetIndex]['descriptionHeight'] = $height;
        return $this;
    }
    /**
     * 设置当前sheetName
     * 
     * @param type $sheetName
     * @return $this
     */
    public function setSheetName($sheetName) 
    {
        $this->sheets[$this->currentSheetIndex]['sheetName'] = $sheetName;
        return $this;
    }
    /**
     * 根据sheet索引获取对应的sheet名称
     * 
     * @param type $index
     * @return string
     */
    public function getSheetName($index)
    {
        $sheetName = $this->sheets[$index]['sheetName'] ?? '';
        if (!$sheetName) {
            $sheetName = $this->title ?? 'sheet';
            if ($index !== 0 ) {
                $sheetName .= '_' . ($index + 1); 
            }
        }
        return $sheetName;
    }
    /**
     * 设置导出excel的title 
     * 
     * @param type $title
     * @return $this
     */
    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }
    /**
     * 设置导出excel的后缀
     * 
     * @param type $suffix
     * @return $this
     */
    public function setSuffix($suffix) 
    {
        $this->suffix = $suffix;
        return $this;
    }
    public function getSuffix()
    {
        return $this->suffix;
    }
    public function setMode($mode)
    {
        $this->mode = $mode;
        return $this;
    }
    /**
     * 生产excel的入口函数
     * 
     * @return boolean
     */
    public function generate()
    {
        ini_set('memory_limit', '4096M');
        if (empty($this->sheets)) {
            return false;
        }
        $fileName = $this->makeExportFileName($this->title);
        // 生成文件
        $this->exportFile = $this->createExportFile($fileName, $this->suffix);
        if ($this->isGenerator()) {
            $this->handleGeneratorExcel();
        } else {
            $this->handleExcel();
        }
        $this->sheets = [];
        $this->activeSheet = [];
        $this->currentSheetIndex = 0;
        return [$fileName, $this->exportFile];
    }
    /**
     * 判断传入的导出数据是否为生成器结构
     * 
     * @return boolean
     */
    private function isGenerator() 
    {
        if (isset($this->sheets[0]['data']) && $this->sheets[0]['data'] instanceof \Generator) {
            return true;
        }
        return false;
    }
    /**
     * 处理生成器结构的数据导出xml格式的excel
     */
    private function handleGeneratorExcel()
    {
        file_put_contents($this->exportFile, self::$xmlHeader);
        foreach ($this->sheets as $index => $sheet) {
            $this->activeSheet = $sheet;
            $xmlsheet = '<Worksheet ss:Name="' . $this->getSheetName($index) . '"><Table>';
            $rowIndex = 1;
            if ($this->hasHeader()) {
                list($maxRowNumber, $columnNumber) = $this->getHeaderMaxRowAndColumn($this->activeSheet['header']);
                $this->setXmlColumnWidth($xmlsheet, $this->activeSheet['header']);
                $this->combineXmlDescription($xmlsheet, $rowIndex, $columnNumber);
                $this->combineXmlDatetime($xmlsheet, $rowIndex, $columnNumber);
                $this->combineXmlHeader($xmlsheet, $rowIndex, $maxRowNumber);
                file_put_contents($this->exportFile, $xmlsheet, FILE_APPEND);
                if ($this->hasData()) {
                    $this->combineXmlData($rowIndex);
                }
            }
            file_put_contents($this->exportFile, '</Table></Worksheet>', FILE_APPEND);
        }
        file_put_contents($this->exportFile, self::$xmlFooter, FILE_APPEND);
    }
    /**
     * 组装xml格式excel的报表头
     * 
     * @param type $xmlsheet
     * @param type $rowIndex
     * @param type $maxRowNumber
     */
    private function combineXmlHeader(&$xmlsheet, &$rowIndex, $maxRowNumber)
    {
        $columnRows = $this->combineHeaderData($this->activeSheet['header'], $maxRowNumber);
        foreach ($columnRows as $row) {
            $xmlsheet .= '<Row ss:Height="25" ss:Index="' . $rowIndex . '">';
            foreach ($row as $column) {
                $colspan = (isset($column['colspan']) && $column['colspan'] > 1) ? ' ss:MergeAcross="' . ($column['colspan'] - 1) . '"' : '';
                $rowspan = (isset($column['rowspan']) && $column['rowspan'] > 1) ? ' ss:MergeDown="' . ($column['rowspan'] - 1) . '"' : '';
                $xmlsheet .= '<Cell ' . $colspan . $rowspan . ' ss:Index="' . $column['columnIndex'] . '" ss:StyleID="header"><Data ss:Type="String">' . $column[self::$valueKey] . '</Data></Cell>';
            }
            $xmlsheet .= '</Row>';
            $rowIndex ++;
        }
    }
    /**
     * 设置xml格式的报表列宽
     * 
     * @param type $xmlsheet
     * @param type $columns
     * @param type $pColumnIndex
     * @return type
     */
    private function setXmlColumnWidth(&$xmlsheet, $columns, $pColumnIndex = 1)
    {
        $columnIndex = 0;
        foreach ($columns as $column) {
            $currentIndex = $columnIndex + $pColumnIndex;
            if (isset($column['children'])) {
                $columnIndex += $this->setXmlColumnWidth($xmlsheet, $column['children'], $currentIndex);
            } else {
                $width = isset($column['style']['width']) ? intval($column['style']['width']) : 0;
                $xmlsheet .= '<Column ss:Index="' . $currentIndex . '" ss:AutoFitWidth="0"';
                if ($width > 0) {
                    $xmlsheet .= ' ss:Width="' . $width . '"';
                }
                $xmlsheet .= '/>';
                $columnIndex ++;
            }
        }
        return $columnIndex;
    }
    /**
     * 组装xml格式的excel报表行
     * 
     * @param type $rowIndex
     * @param type $rows
     */
    private function combineXmlRow(&$rowIndex, $rows)
    {
        foreach ($rows as $row) {
            $xmlsheet = '<Row ss:Height="25" ss:Index="' . $rowIndex . '">';
            foreach ($row as $column) {
                $rowspan = (isset($column['rowspan']) && $column['rowspan'] > 1) ? ' ss:MergeDown="' . ($column['rowspan'] - 1) . '"' : '';
                $columnIndex = (isset($column['columnIndex']) && $column['columnIndex'] > 1) ? ' ss:Index="' . $column['columnIndex'] . '"' : '';
                $dataType = $this->parseDataType($column);
                $url = (isset($column['url']) && $column['url']) ? ' ss:Formula="=HYPERLINK(' . $column['url'] . ')"' : '';
                $xmlsheet .= '<Cell' . $rowspan . $columnIndex . ' ss:StyleID="eoffice"' . $url . '><Data ' . $dataType . '>' . $column[self::$valueKey] . '</Data></Cell>';
            }
            $xmlsheet .= '</Row>';
            file_put_contents($this->exportFile, $xmlsheet, FILE_APPEND);
            $rowIndex ++;
        }
    }
    /**
     * 组装xml格式的excel报表数据
     * 
     * @param type $rowIndex
     */
    private function combineXmlData(&$rowIndex)
    {
        $childrenKeys = $this->getHasChildrenKeysMap($this->activeSheet['header']);
        $combinedMap = [];
        foreach ($this->activeSheet['data'] as $row) {
            if (isset($combinedMap['combined'])) {
                $combined = $combinedMap['combined'];
            } else {
                $combined = $this->isCombinedData($row);
                $combinedMap['combined'] = $combined;
            }
            $maxRowNumber = $this->getHasHeaderDataMaxRowNumber($row, $childrenKeys, $combined);
            $combineRows = [];
            $this->combineXmlRowData($combineRows, $this->activeSheet['header'], $row, 0, $childrenKeys, 1, $maxRowNumber, $combined);
            $this->combineXmlRow($rowIndex, $combineRows);
        }
    }
    /**
     * 重组每一行待组装成excel报表的数据
     * 
     * @param type $combineRows
     * @param type $columns
     * @param type $row
     * @param type $rowIndex
     * @param type $childrenKeys
     * @param type $pColumnIndex
     * @param type $maxRowNumber
     * @param type $combined
     * @return type
     */
    private function combineXmlRowData(&$combineRows, $columns, $row, $rowIndex, $childrenKeys = [], $pColumnIndex = 1, $maxRowNumber = 1, $combined = false)
    {
        $columnIndex = 0;
        foreach ($columns as $key => $column) {
            $currentColumnIndex = $columnIndex + $pColumnIndex;
            if (isset($column['children'])) {
                if ($combined) {
                    $value = $row[$key][self::$valueKey] ?? [];
                } else {
                    $value = $row[$key] ?? [];
                }
                $value = is_array($value) ? $value : [];
                $_rowIndex = 0;
                $maxSubColumnIndex = 1;
                $_childrenKeys = $childrenKeys[$key]['children'] ?? [];
                if (empty($value)) {
                    $maxSubColumnIndex = $this->combineXmlRowData($combineRows, $column['children'], [], $rowIndex + $_rowIndex, $_childrenKeys, $currentColumnIndex, 1, $combined);
                } else {
                    foreach ($value as $_value) {
                        $_maxRowNumber = $this->getHasHeaderDataMaxRowNumber($_value, $_childrenKeys, $combined);
                        $subColumnIndex = $this->combineXmlRowData($combineRows, $column['children'], $_value, $rowIndex + $_rowIndex, $_childrenKeys, $currentColumnIndex, $_maxRowNumber, $combined);
                        $_rowIndex += $_maxRowNumber;
                        $maxSubColumnIndex = max($maxSubColumnIndex, $subColumnIndex);
                    }
                }
                $columnIndex += $maxSubColumnIndex;
            } else {
                if ($combined) {
                    $value = $row[$key] ?? [self::$valueKey => ''];
                } else {
                    $value = isset($row[$key]) ? [self::$valueKey => $row[$key]] : [self::$valueKey => ''];
                }
                $value['columnIndex'] = $currentColumnIndex;
                if ($maxRowNumber > 1) {
                    $value['rowspan'] = $maxRowNumber;
                }
                $combineRows[$rowIndex][] = $value;
                $columnIndex ++;
            }
        }
        return $columnIndex;
    }
    /**
     * 解析单元格数据类型
     * 
     * @param type $data
     * @return string
     */
    private function parseDataType($data)
    {
        $types = [
            'string' => 'String',
            'number' => 'Number'
        ];
        if (isset($data['dataType']) && $data['dataType']) {
            $dataType = 'ss:Type="' . ($types[strtolower($data['dataType'])] ?? 'String') . '"';
        } else {
            if (is_numeric($data[self::$valueKey])) {
                $dataType = 'ss:Type="Number"';
            } else {
                $dataType = 'ss:Type="String"';
            }
        }
        return $dataType;
    }
    /**
     * 组装xml格式报表的描述
     * 
     * @param type $xmlsheet
     * @param type $rowIndex
     * @param type $columnNumber
     */
    private function combineXmlDescription(&$xmlsheet, &$rowIndex, $columnNumber)
    {
        if ($this->hasDescription()) {
            $xmlsheet .= '<Row ss:Height="' . $this->activeSheet['descriptionHeight'] . '" ss:Index="' . $rowIndex . '">';
            $xmlsheet .= '<Cell ss:MergeAcross="' . ($columnNumber - 1) . '" ss:StyleID="eoffice"><Data ss:Type="String">' . $this->activeSheet['description'] . '</Data></Cell>';
            $xmlsheet .= '</Row>';
            $rowIndex ++;
        }
    }
    /**
     * 组装xml格式的报表的导出时间
     * 
     * @param type $xmlsheet
     * @param type $rowIndex
     * @param type $columnNumber
     */
    private function combineXmlDatetime(&$xmlsheet, &$rowIndex, $columnNumber)
    {
        $xmlsheet .= '<Row ss:Height="25" ss:Index="' . $rowIndex . '">';
        $xmlsheet .= '<Cell ss:MergeAcross="' . ($columnNumber - 1) . '" ss:StyleID="eoffice"><Data ss:Type="String">' . date('Y-m-d H:i:s') . '</Data></Cell>';
        $xmlsheet .= '</Row>';
        $rowIndex ++;
    }
    /**
     * 处理导出报表
     */
    private function handleExcel()
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()->setCreator('e-office');
        Cell::setValueBinder( new AdvancedValueBinder() );
        foreach ($this->sheets as $index => $sheet) {
            $this->activeSheet = $sheet;
            $activeSpreadSheet = $spreadsheet->setActiveSheetIndex($index);
//            $activeSpreadSheet->getDefaultRowDimension()->setRowHeight(25);// 设置默认行高
            $rowIndex = 1;
            $maxColumnNumber = 0;
            if ($this->hasHeader()) {
                // 获取标题头行数和报表总列数
                list($maxRowNumber, $maxColumnNumber) = $this->getHeaderMaxRowAndColumn($this->activeSheet['header']);
                $this->setColumnWidth($activeSpreadSheet, $this->activeSheet['header']);
                $this->combineExcelDescription($rowIndex, $activeSpreadSheet, $maxColumnNumber);
                $this->combineExcelDatetime($rowIndex, $activeSpreadSheet, $maxColumnNumber);
                $this->combineExcelHeader($activeSpreadSheet, $this->activeSheet['header'], $rowIndex, $maxRowNumber);
                $rowIndex += $maxRowNumber;
                if ($this->hasData()) {
                    if ($this->mode === 2) {
                        $this->exportFastData($rowIndex, $activeSpreadSheet, $maxColumnNumber);
                    } else {
                        $combined = $this->isCombinedData($this->activeSheet['data'][0]);
                        $this->combineHasHeaderData($rowIndex, $activeSpreadSheet, $maxColumnNumber, $combined);
                    }
                }
                
            } else {
                if ($this->hasData()) {
                    $maxColumnNumber = $this->getColumnByFirstRow($this->activeSheet['data'][0]);
                    $this->combineNoHeaderData($rowIndex, $activeSpreadSheet, $maxColumnNumber);
                }
            }
            if ($maxColumnNumber){
                // 设置默认样式
                $activeSpreadSheet->getStyleByColumnAndRow(1, 1, $maxColumnNumber, $rowIndex - 1)->applyFromArray(self::$defaultStyle);
                $activeSpreadSheet->getStyleByColumnAndRow(1, $rowIndex, $maxColumnNumber, $rowIndex)->applyFromArray(['font' => ['size' => 14]]);
            }
            // 设置sheetName
            $this->combineExcelSheetName($activeSpreadSheet, $index);
            // 创建新sheet
            if ($index != count($this->sheets) - 1) {
                $spreadsheet->createSheet();
            }
        }
        $spreadsheet->setActiveSheetIndex(0);
        $writer = IOFactory::createWriter($spreadsheet, self::$writers[$this->suffix] ?? 'Xlsx');
        $writer->save($this->exportFile);
        if ($spreadsheet instanceof Spreadsheet) {
            $spreadsheet->disconnectWorksheets();
        }
    }
    /**
     * 设置报表列宽
     * 
     * @param type $activeSpreadSheet
     * @param type $columns
     * @param type $pColumnIndex
     * @return type
     */
    private function setColumnWidth($activeSpreadSheet, $columns, $pColumnIndex = 1)
    {
        $columnIndex = 0;
        foreach ($columns as $column) {
            $currentIndex = $columnIndex + $pColumnIndex;
            if (isset($column['children'])) {
                $columnIndex += $this->setColumnWidth($activeSpreadSheet, $column['children'], $currentIndex);
            } else {
                $width = isset($column['style']['width']) ? $column['style']['width'] : 0;
                if ($width === 'auto') {
                    $activeSpreadSheet->getColumnDimensionByColumn($currentIndex)->setAutoSize(true);
                } else {
                    $width = intval($width);
                    if ($width > 0) {
                        $activeSpreadSheet->getColumnDimensionByColumn($currentIndex)->setWidth($width / 6);
                    } else {
                        $activeSpreadSheet->getColumnDimensionByColumn($currentIndex)->setWidth(22);
                    }
                }

                $columnIndex ++;
            }
        }
        return $columnIndex;
    }
    /**
     * 判断是否为组装好的数据
     * 
     * @param type $data
     * @return boolean
     */
    private function isCombinedData($data)
    {
        foreach ($data as $key => $item) {
            if (is_string($item)) {
                return false;
            } else {
                return isset($item[self::$valueKey]);
            }
        }
    }
    /**
     * 组装报表sheetName
     * 
     * @param type $activeSpreadSheet
     * @param type $sheetIndex
     */
    private function combineExcelSheetName($activeSpreadSheet, $sheetIndex)
    {
        $invalidCharacters = array_merge($activeSpreadSheet->getInvalidCharacters(), ["：", "？", "\/"]);
        $title = str_replace($invalidCharacters, '', $this->getSheetName($sheetIndex));
        $activeSpreadSheet->setTitle($title);
    }
    /**
     * 组装报表描述
     * 
     * @param int $rowIndex
     * @param type $activeSpreadSheet
     * @param type $maxColumnNumber
     */
    private function combineExcelDescription(&$rowIndex, $activeSpreadSheet, $maxColumnNumber)
    {
        if ($this->hasDescription()) {
            $activeSpreadSheet->setCellValueByColumnAndRow(1, $rowIndex, $this->activeSheet['description']);
            $activeSpreadSheet->mergeCellsByColumnAndRow(1, $rowIndex, $maxColumnNumber, $rowIndex);
            $activeSpreadSheet->getRowDimension($rowIndex)->setRowHeight($this->activeSheet['descriptionHeight']);
            $activeSpreadSheet->getStyleByColumnAndRow(1, $rowIndex, $maxColumnNumber, $rowIndex)->applyFromArray(self::$timeStyle);
            $rowIndex += 1;
        }
    }
    /**
     * 组装报表导出时间
     * 
     * @param int $rowIndex
     * @param type $activeSpreadSheet
     * @param type $maxColumnNumber
     */
    private function combineExcelDatetime(&$rowIndex, $activeSpreadSheet, $maxColumnNumber)
    {
        $activeSpreadSheet->setCellValueByColumnAndRow(1, $rowIndex, date('Y-m-d H:i:s'));
        $activeSpreadSheet->mergeCellsByColumnAndRow(1, $rowIndex, $maxColumnNumber, $rowIndex);
        $activeSpreadSheet->getStyleByColumnAndRow(1, $rowIndex, $maxColumnNumber, $rowIndex)->applyFromArray(self::$timeStyle);
        $activeSpreadSheet->getRowDimension($rowIndex)->setRowHeight(25);
        $rowIndex += 1;
    }
    /**
     * 组装表头
     * 
     * @param type $activeSpreadSheet
     * @param type $columns
     * @param type $rowIndex
     * @param type $maxRowNumber
     * @param type $pColumnIndex
     * @return type
     */
    private function combineExcelHeader($activeSpreadSheet, $columns, $rowIndex, $maxRowNumber, $pColumnIndex = 1)
    {
        $colspan = 1;
        $colIndex = 0;
        foreach ($columns as $key => $column) {
            $currentColumnIndex = $colIndex + $pColumnIndex;
//            $activeSpreadSheet->setCellValueByColumnAndRow($currentColumnIndex, $rowIndex, $column[self::$valueKey]);
            $this->setValue($activeSpreadSheet, $currentColumnIndex, $rowIndex, $column);
            $this->setCellProperty($activeSpreadSheet, $currentColumnIndex, $rowIndex, $column);
            $activeSpreadSheet->getStyleByColumnAndRow($currentColumnIndex, $rowIndex)->applyFromArray(self::$headerStyle);
            if (isset($column['children'])) {
                $colspan = $this->combineExcelHeader($activeSpreadSheet, $column['children'], $rowIndex + 1, $maxRowNumber - 1, $currentColumnIndex);
                $rowspan = 1;
                $colIndex += $colspan;
            } else {
                $colspan = (isset($column['colspan']) && $column['colspan'] > 1) ? $column['colspan'] : 1;
                $colIndex += $colspan;
                $rowspan = $maxRowNumber;
            }
            // 合并行列
            if ($rowspan > 1 || $colspan > 1) {
                $lastRowIndex = $rowIndex + $rowspan - 1;
                $lastColumnIndex = $currentColumnIndex + $colspan - 1;
                $activeSpreadSheet->mergeCellsByColumnAndRow($currentColumnIndex, $rowIndex, $lastColumnIndex, $lastRowIndex);
            }
        }
        $activeSpreadSheet->getRowDimension($rowIndex)->setRowHeight(25);
        return $colIndex;
    }
    /**
     * 组装没有表头的报表数据
     * 
     * @param type $rowIndex
     * @param type $activeSpreadSheet
     * @param type $maxColumnNumber
     */
    private function combineNoHeaderData(&$rowIndex, $activeSpreadSheet, $maxColumnNumber)
    {
        $rowStart = $rowIndex;
        foreach ($this->activeSheet['data'] as $row) {
            $maxRowNumber = $this->getDataMaxRowNumber($row);
            $this->combineNoHeaderRow($activeSpreadSheet, $row, $rowIndex, 1, $maxRowNumber);
            $rowIndex += $maxRowNumber;
        }
        $activeSpreadSheet->getStyleByColumnAndRow(1, $rowStart, $maxColumnNumber, $rowIndex - 1)->applyFromArray(self::$bodyStyle);
    }
    /**
     * 根据第一行报表数据获取报表列数
     * 
     * @param type $row
     * @return int
     */
    private function getColumnByFirstRow($row)
    {
        $columnNumber = 0;
        foreach ($row as $column) {
            if (is_array($column[self::$valueKey])) {
                if (empty($column[self::$valueKey])) {
                    $columnNumber += 1;
                } else {
                    $maxSubColumnIndex = 1;
                    foreach ($column[self::$valueKey] as $_row) {
                        $subColumnIndex = $this->getColumnByFirstRow($_row);
                        $maxSubColumnIndex = max($maxSubColumnIndex, $subColumnIndex);
                    }
                    $columnNumber += $maxSubColumnIndex;
                }
            } else {
                if (isset($column['colspan']) && $column['colspan'] > 1) {
                    $columnNumber += $column['colspan'];
                } else {
                    $columnNumber += 1;
                }
            }
        }
        return $columnNumber;
    }
    private function exportFastData(&$rowIndex, $activeSpreadSheet, $maxColumnNumber)
    {
        $rowStart = $rowIndex;
        $rowspanColumnIndexs = [];
        foreach ($this->activeSheet['data'] as $row) {
            $columnIndex = 1;
            $rowspan = false;
            foreach ($row as $column) {
                if (isset($column['rowspan'])) {
                    $this->setValue($activeSpreadSheet, $columnIndex, $rowIndex, $column);
                    $this->setCellProperty($activeSpreadSheet, $columnIndex, $rowIndex, $column);
                    $rowspanColumnIndexs[$columnIndex] = true;
                    $rowspan = true;
                    $activeSpreadSheet->mergeCellsByColumnAndRow($columnIndex, $rowIndex, $columnIndex, $rowIndex + $column['rowspan'] - 1);
                } else {
                    if (!$rowspan && isset($rowspanColumnIndexs[$columnIndex])) {
                        $columnIndex++;
                    }
                    $this->setValue($activeSpreadSheet, $columnIndex, $rowIndex, $column);
                    $this->setCellProperty($activeSpreadSheet, $columnIndex, $rowIndex, $column);
                }
                $columnIndex ++;
            }
            $activeSpreadSheet->getRowDimension($rowIndex)->setRowHeight(25);
            $rowIndex ++;
        }
        $activeSpreadSheet->getStyleByColumnAndRow(1, $rowStart, $maxColumnNumber, $rowIndex - 1)->applyFromArray(self::$bodyStyle);
    }
    /**
     * 组装没有表头的报表行
     * 
     * @param type $activeSpreadSheet
     * @param type $row
     * @param type $rowIndex
     * @param type $pColumnIndex
     * @param type $maxRowNumber
     * @return type
     */
    private function combineNoHeaderRow($activeSpreadSheet, $row, $rowIndex, $pColumnIndex = 1, $maxRowNumber = 1)
    {
        $columnIndex = 0;
        foreach ($row as $column) {
            $currentColumnIndex = $columnIndex + $pColumnIndex;
            if (is_array($column[self::$valueKey])) {
                if (empty($column[self::$valueKey])) {
                    $activeSpreadSheet->setCellValueByColumnAndRow($currentColumnIndex, $rowIndex, '');
                    $columnIndex += 1;
                } else {
                    $_rowIndex = 0;
                    $maxSubColumnIndex = 1;
                    foreach ($column[self::$valueKey] as $_value) {
                        $_maxRowNumber = $this->getDataMaxRowNumber($_value);
                        $subColumnIndex = $this->combineNoHeaderRow($activeSpreadSheet, $_value, $rowIndex + $_rowIndex, $currentColumnIndex, $_maxRowNumber);
                        $_rowIndex += $_maxRowNumber;
                        $maxSubColumnIndex = max($maxSubColumnIndex, $subColumnIndex);
                    }
                }
                $columnIndex += $maxSubColumnIndex;
            } else {
                $activeSpreadSheet->setCellValueByColumnAndRow($currentColumnIndex, $rowIndex, $column[self::$valueKey]);
                $colspan = $column['colspan'] ?? 1;
                if ($maxRowNumber > 1 || $colspan > 1) {
                    $activeSpreadSheet->mergeCellsByColumnAndRow($currentColumnIndex, $rowIndex, $currentColumnIndex + $colspan - 1, $rowIndex + $maxRowNumber - 1);
                }
                $columnIndex += $colspan;
            }
        }
        $activeSpreadSheet->getRowDimension($rowIndex)->setRowHeight(25);
        return $columnIndex;
    }
    /**
     * 组装有表头的报表数据
     * 
     * @param type $rowIndex
     * @param type $activeSpreadSheet
     * @param type $maxColumnNumber
     * @param type $combined
     */
    private function combineHasHeaderData(&$rowIndex, $activeSpreadSheet, $maxColumnNumber, $combined = false)
    {
        $bodyRowStart = $rowIndex;
        $childrenKeys = $this->getHasChildrenKeysMap($this->activeSheet['header']);
        foreach ($this->activeSheet['data'] as $row) {
            $maxRowNumber = $this->getHasHeaderDataMaxRowNumber($row, $childrenKeys, $combined);
            $this->combinehasHeaderRow($activeSpreadSheet, $this->activeSheet['header'], $row, $rowIndex, $childrenKeys, 1, $maxRowNumber, $combined);
            $rowIndex += $maxRowNumber;
        }
        $activeSpreadSheet->getStyleByColumnAndRow(1, $bodyRowStart, $maxColumnNumber, $rowIndex - 1)
                    ->applyFromArray(self::$bodyStyle);
    }
    /**
     * 组装有表头的报表行
     * 
     * @param type $activeSpreadSheet
     * @param type $columns
     * @param type $row
     * @param type $rowIndex
     * @param type $childrenKeys
     * @param type $pColumnIndex
     * @param type $maxRowNumber
     * @param type $combined
     * @return type
     */
    private function combinehasHeaderRow($activeSpreadSheet, $columns, $row, $rowIndex, $childrenKeys = [], $pColumnIndex = 1, $maxRowNumber = 1, $combined = false)
    {
        $columnIndex = 0;
        foreach ($columns as $key => $column) {
            $currentColumnIndex = $columnIndex + $pColumnIndex;
            if (isset($column['children'])) {
                if ($combined) {
                    $value = $row[$key][self::$valueKey] ?? [];
                } else {
                    $value = $row[$key] ?? [];
                }
                $value = is_array($value) ? $value : [];
                $_rowIndex = 0;
                $maxSubColumnIndex = 1;
                $_childrenKeys = $childrenKeys[$key]['children'] ?? [];
                if (empty($value)) {
                    $maxSubColumnIndex = $this->combinehasHeaderRow($activeSpreadSheet, $column['children'], [], $rowIndex + $_rowIndex, $_childrenKeys, $currentColumnIndex, 1, $combined);
                } else {
                    foreach ($value as $_value) {
                        $_maxRowNumber = $this->getHasHeaderDataMaxRowNumber($_value, $_childrenKeys, $combined);
                        $subColumnIndex = $this->combinehasHeaderRow($activeSpreadSheet, $column['children'], $_value, $rowIndex + $_rowIndex, $_childrenKeys, $currentColumnIndex, $_maxRowNumber, $combined);
                        $_rowIndex += $_maxRowNumber;
                        $maxSubColumnIndex = max($maxSubColumnIndex, $subColumnIndex);
                    }
                }
                $columnIndex += $maxSubColumnIndex;
            } else {
                if ($combined) {
                    $data = $row[$key] ?? [];
                    $this->setValue($activeSpreadSheet, $currentColumnIndex, $rowIndex, $data);
                    $this->setCellProperty($activeSpreadSheet, $currentColumnIndex, $rowIndex, $data);
                } else {
                    $activeSpreadSheet->setCellValueByColumnAndRow($currentColumnIndex, $rowIndex, $row[$key] ?? '');
                }
                if ($maxRowNumber > 1) {
                    $activeSpreadSheet->mergeCellsByColumnAndRow($currentColumnIndex, $rowIndex, $currentColumnIndex, $rowIndex + $maxRowNumber - 1);
                }
                $columnIndex ++;
            }
        }
        $activeSpreadSheet->getRowDimension($rowIndex)->setRowHeight(25);
        return $columnIndex;
    }
    /**
     * 设置单元格的值
     * 
     * @param type $activeSpreadSheet
     * @param type $columnIndex
     * @param type $rowIndex
     * @param type $data
     */
    private function setValue($activeSpreadSheet, $columnIndex, $rowIndex, $data)
    {
        $dataType = $data['dataType'] ?? null;
        $value = $data[self::$valueKey] ?? '';
        if ($dataType) {
            if ($dataType == 'string') { // 设置类型为字符串的强制转为字符串
                $activeSpreadSheet->getCellByColumnAndRow($columnIndex, $rowIndex)->setValueExplicit($value, DataType::TYPE_STRING);
            }
        } else {
            if ($this->isStringCell($value)) {
                $activeSpreadSheet->setCellValueExplicitByColumnAndRow($columnIndex, $rowIndex, $value, DataType::TYPE_STRING);
            } else {
                $activeSpreadSheet->setCellValueByColumnAndRow($columnIndex, $rowIndex, $value);
            }
        }
    }
    private function isStringCell($value)
    {
        if (is_numeric($value) && $value > static::EXCEL_MAX_INT) {
            return true;
        } else if (preg_match('/^[\+\-]?(\d+\\.?\d*|\d*\\.?\d+)([Ee][\-\+]?[0-2]?\d{1,3})?$/', $value)) {
            $tValue = ltrim($value, '+-');
            if (is_string($value) && $tValue[0] === '0' && strlen($tValue) > 1 && $tValue[1] !== '.') {
                return true;
            } elseif ((strpos($value, '.') === false) && ($value > static::EXCEL_MAX_INT)) {
                return true;
            }
            if ($value > static::EXCEL_MAX_INT) {
                return true;
            }
            if (strpos($value, '.') !== false) {
                list($one, $two) = explode('.', $value);
                if (strlen($two) > static::PRECISION) {
                    return true;
                }
            }
        }
        return false;
    }
    /**
     * 设置单元格附加属性
     * 
     * @param type $activeSpreadSheet
     * @param type $columnIndex
     * @param type $rowIndex
     * @param type $data
     */
    private function setCellProperty($activeSpreadSheet, $columnIndex, $rowIndex, $data)
    {
        if (isset($data['url'])) {
            $activeSpreadSheet->getCellByColumnAndRow($columnIndex, $rowIndex)->getHyperlink()->setUrl($data['url']);
            $activeSpreadSheet->getStyleByColumnAndRow($columnIndex, $rowIndex)->getFont()->getColor()->setARGB('4E89F8');
        }
        if (isset($data['comment'])) {
            $activeSpreadSheet->getCommentByColumnAndRow($columnIndex, $rowIndex)->getText()->createTextRun($data['comment']);
        }
        if (isset($data['style']) && is_array($data['style']) && !empty($data['style'])) {
            foreach ($data['style'] as $key => $value) {
                if ($key === 'color') {
                    $activeSpreadSheet->getStyleByColumnAndRow($columnIndex, $rowIndex)->getFont()->getColor()->setARGB(ltrim($value, '#'));
                } else if ($key === 'background') {
                    $fill = $activeSpreadSheet->getStyleByColumnAndRow($columnIndex, $rowIndex)->getFill();
                    $fill->setFillType(Fill::FILL_SOLID);
                    $fill->getStartColor()->setARGB(ltrim($value, '#'));
                }
            }
        }
    }
    /**
     * 获取有表头的数据的最大层深度
     * 
     * @param type $row
     * @param type $childrenKeys
     * @param type $isCombined
     * @return int
     */
    private function getHasHeaderDataMaxRowNumber($row, $childrenKeys, $isCombined = false)
    {
        if (empty($childrenKeys)){
            return 1;
        }
        $maxRowNumber = 1;
        foreach ($childrenKeys as $key => $item) {
            if ($isCombined) {
                $value = $row[$key][self::$valueKey] ?? [];
            } else {
                $value = $row[$key] ?? [];
            }
            $rowNumber = 0;
            if (!empty($value)) {
                if (isset($item['children'])) {
                    foreach($value as $_v) {
                        $rowNumber += $this->getHasHeaderDataMaxRowNumber($_v, $item['children'], $isCombined);
                    }
                } else {
                    $rowNumber = is_array($value) ? count($value) : 1;
                }
            }
            $maxRowNumber = max($rowNumber, $maxRowNumber);
        }
        return $maxRowNumber;
    }
    /**
     * 获取有子项的字段map
     * 
     * @param type $columns
     * @return type
     */
    private function getHasChildrenKeysMap($columns)
    {
        $Keys = [];
        foreach ($columns as $key => $column) {
            if (isset($column['children'])) {
                $temp = ['key' => $key];
                $childrenKeys = $this->getHasChildrenKeysMap($column['children']);
                if(!empty($childrenKeys)) {
                    $temp['children'] = $childrenKeys;
                }
                $Keys[$key] = $temp;
            }
        }
        return $Keys;
    }
    /**
     * 判断活动sheet是否有表头
     *
     * @return type
     */
    private function hasHeader()
    {
        return $this->hasProperty('header');
    }
    /**
     * 判断活动sheet是否有数据描述
     * 
     * @return type
     */
    private function hasDescription()
    {
        return $this->hasProperty('description');
    }
    /**
     * 判断活动sheet是否有导出数据
     * 
     * @return type
     */
    private function hasData()
    {
        return $this->hasProperty('data');
    }
    /**
     * 判断活动sheet某个属性是否存在
     * 
     * @param type $key
     * @return type
     */
    private function hasProperty($key)
    {
        return isset($this->activeSheet[$key]) && !empty($this->activeSheet[$key]);
    }
   /**
    * 组装报表头数据
    * 
    * @param type $header
    * @param type $rowNumber
    * @return array
    */
    private function combineHeaderData($header, $rowNumber) 
    {
        $rows = [];
        for ($i = 1; $i <= $rowNumber; $i++) {
            $rows[] = [];
        }
        $this->_combineHeaderData($rows, $header, $rowNumber);
        return $rows;
    }
    /**
     * 递归组装多级报表头
     * 
     * @param type $rows
     * @param type $header
     * @param type $currentRowNumber
     * @param type $level
     * @param type $pColIndex
     */
    private function _combineHeaderData(&$rows, $header, $currentRowNumber, $level = 0, $pColIndex = 0) 
    {
        $colIndex = 1;
        foreach ($header as $column) {
            $column['columnIndex'] = $colIndex + $pColIndex;
            if (isset($column['children'])) {
                $copyColumn = $column;
                unset($copyColumn['children']);
                
                $this->_combineHeaderData($rows, $column['children'], $currentRowNumber - 1, $level + 1, $column['columnIndex'] - 1);
                $rows[$level][] = $copyColumn;
            } else {
                if ($currentRowNumber > 1) {
                    $column['rowspan'] = $currentRowNumber;
                }
                $rows[$level][] = $column;                
            }
            if (isset($column['colspan']) && $column['colspan'] > 1) {
                $colIndex += $column['colspan'];
            } else {
                $colIndex ++;
            }
        }
    }
   /**
    * 获取报表头的最大列数和最大行数
    * 
    * @param type $header
    * @return type
    */
    private function getHeaderMaxRowAndColumn(&$header) 
    {
        $maxRowNumber = 1;
        $columnNumber = 0;
        foreach ($header as &$column) {
            if (isset($column['children'])) {
                list($_rowNumber, $_colNumber) = $this->getHeaderMaxRowAndColumn($column['children']);
                $maxRowNumber = max($maxRowNumber, $_rowNumber + 1);
                $column['colspan'] = $_colNumber;
                $columnNumber += $_colNumber;
            } else {
                $colspan = isset($column['colspan']) && $column['colspan'] > 1 ? $column['colspan'] : 1;
                $columnNumber += $colspan;
            }
        }
        return [$maxRowNumber, $columnNumber];
    }
    /**
     * 获取数据的层深
     * 
     * @param type $data
     * @return type
     */
    private function getDataMaxRowNumber($data)
    {
        $maxRowNumber = 1;
        foreach ($data as $column) {
            if (is_array($column[self::$valueKey])) {
                if (empty($column[self::$valueKey])) {
                    $rowNumber = 1;
                } else {
                    $rowNumber = 0;
                    foreach ($column[self::$valueKey] as $_row) {
                        $rowNumber += $this->getDataMaxRowNumber($_row);
                    }
                }
                $maxRowNumber = max($rowNumber, $maxRowNumber);
            } 
        }
        return $maxRowNumber;
    }
}
