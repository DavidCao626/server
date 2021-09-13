<?php

namespace App\EofficeApp\ImportExport\Parsers;

use App\EofficeApp\ImportExport\Traits\ImportTrait;
use Eoffice;
use Illuminate\Support\Facades\Redis;;

class ExcelXlsxParser extends BaseParser implements ParserInterface
{
    use ImportTrait;
    public $param;
    // excel导入每次处理条数
    const PER_NUM = 2000;

    /**
     * 验证模板的合法性
     * @param $params
     * @return bool
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     */
    public function checkUploadMatchTemplate($params)
    {

        $err = $this->validateImportExcel($params)[0];
        if ($err) {
            return false;
        }
        $err = $this->checkHeader($params);

        if (!$err) {
            return false;
        }
        return true;

    }

    /**
     * 数据导入
     * @param $params
     * @return bool
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function importUploadData($params)
    {
        list($err, $userId, $file, $module) = $this->validateImportExcel($params);
        $config = config('import.' . $params['module']);
        //自定义字段
        if (empty($config)) {
            $config['module'] = $params['module'] ?? '';
        }
        $importInfo = $this->getModuleHeader($params);
        $moduleHeader = $importInfo['header'] ?? ($importInfo[0]['header'] ?? []);
        //获取头部长度
        $headerLength = $this->getModuleHeaderLength($moduleHeader);
        $start = $headerLength + 1;
        $end = $start + self::PER_NUM;
        list($reportFile, $reportConfig) = $this->generateReport($file, $module, $userId);
        $fileData = $this->getExcelFile($file, $userId); //判断里面是否有图片
        while (!empty($data = $this->getExcelData($file, $start, $end, $fileData))) {
            if ($data === $this->emptyData) {
                return $this->importExcelErrorCallback($userId, $file, $module, $this->emptyData);
            }
            $info = $this->importData($moduleHeader, $data, $config, $params); //把头部,内容,配置项传到对应模块
            // 写入导入结果信息
            $this->writeExcelReportResult($reportFile, $info, $start, $end, $userId);
            $start = ++$end;
            $end = $start + self::PER_NUM;
        }
        return [$reportFile, $reportConfig, $userId];
    }


    public function generateReport($file, $module, $uid)
    {
        $title = trans('export.' . $module) . "_" . trans("import.import_report");
        if (!$config = config('import.' . $module)) {
            $title = app('App\EofficeApp\FormModeling\Services\FormModelingService')->getImportResultTitle($module);
        }
        $reportConfig = [
            'title' => $title,
            'fileType' => 'xlsx',
            'user_id' => $uid,
            'importData' => 1,
            'export_type' => "async",
        ];
        $responseDir = createExportDir();
        $reportFile = $responseDir . $reportConfig['title'] . date('YmdHis') . '.xlsx';
        @copy($file, $reportFile);
        return [$reportFile, $reportConfig];
    }

    public function writeReportResult($params)
    {

    }


    /**
     * 导入结果导出
     * @param $reportFile
     * @param $info
     * @param $start
     * @param $end
     * @param $userId
     * @return bool
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    private function writeExcelReportResult($reportFile, $info, $start, $end, $userId)
    {
        if (!isset($info['data']) || empty($info['data'])) {
            return false;
        }
        $begin = $start;
        $objPHPExcel = \PhpOffice\PhpSpreadsheet\IOFactory::load($reportFile);
        $objPHPExcelSheet = $objPHPExcel->setActiveSheetIndex(0);
        $colsNum = $objPHPExcelSheet->getHighestColumn();
        $highestColumm = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($colsNum);
        ++$highestColumm;
        $objPHPExcelSheet->setCellValueByColumnAndRow($highestColumm, 1, trans("import.import_result"));
        $objPHPExcelSheet->setCellValueByColumnAndRow($highestColumm + 1, 1, trans("import.import_reason"));
        $excelObj = app('App\Jobs\Export\Excel');
        foreach ($info['data'] as $key => $item) {
            $dim = $this->getArrayDim($item);
            $add = 1;
            if ($dim > 2) {
                $add = $this->getArrayMaxLength($item);
                $end = $start + $add - 1;
            }
            $tempResult = '';
            if (isset($item['importResult'])) {
                if (!is_array($item['importResult'])) {
                    $tempResult = $item['importResult'];
                } else {
                    $tempResult = isset($item['importResult']['data']) ? $item['importResult']['data'] : '';
                }
                if (isset($item['importResult']['style'])) {
                    $excelObj->setColumnAndRowStyle($objPHPExcelSheet, $item['importResult']['style'], [$highestColumm, $start]);
                }
                $objPHPExcelSheet->setCellValueByColumnAndRow($highestColumm, $start, $tempResult);
                if ($add > 1) {
                    $objPHPExcelSheet->mergeCellsByColumnAndRow($highestColumm, $start, $highestColumm, $end);
                }
                if ($begin == 3) {
                    $objPHPExcelSheet->mergeCellsByColumnAndRow($highestColumm, 1, $highestColumm, 2);
                }
            }
            if (isset($item['importReason'])) {

                if (!is_array($item['importReason'])) {
                    $tempResult = $item['importReason'];
                } else {
                    $tempResult = isset($item['importReason']['data']) ? $item['importReason']['data'] : '';
                }
                if (isset($item['importReason']['style'])) {
                    $excelObj->setColumnAndRowStyle($objPHPExcelSheet, $item['importReason']['style'], [$highestColumm + 1, $start]);
                }
                $rowData = is_array($tempResult) ? json_encode($tempResult) : $tempResult;
                $objPHPExcelSheet->setCellValueByColumnAndRow($highestColumm + 1, $start, $rowData);
                if ($add > 1) {
                    $objPHPExcelSheet->mergeCellsByColumnAndRow($highestColumm + 1, $start, $highestColumm + 1, $end);
                }
            }
            $start += $add;
        }
        if ($begin == 3) {
            $objPHPExcelSheet->mergeCellsByColumnAndRow($highestColumm + 1, 1, $highestColumm + 1, 2);
        }
        $collect = $objPHPExcelSheet->getDrawingCollection() ? count($objPHPExcelSheet->getDrawingCollection()) : 0;
        if ($collect > 0) {
            for ($i = 0; $i < $collect; $i++) {
                $objPHPExcelSheet->getDrawingCollection()->offsetUnset($i);
            }
        }
        $objWriter = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($objPHPExcel, 'Xlsx');
        $fileData = $this->getExcelFile($reportFile, $userId);
        if (!empty($fileData)) {
            $offset = [];
            foreach ($fileData as $d) {
                $path = app('App\EofficeApp\Attachment\Services\AttachmentService')->getOneAttachmentById($d['attachment_id']);
                $objDrawing = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
                $objDrawing->setPath($path['temp_src_file']); //图片引入位置
                //等比例缩放
                $imageInfo = getimagesize($path['temp_src_file']);
                $width = $imageInfo[0];
                $height = $imageInfo[1];
                $proportion = $height / 50;
                $newWidth = ceil($width / $proportion);
                $columnString = $d['columnNum'];
                $objDrawing->setCoordinates($columnString . $d['row']); //图片添加位置
                $objDrawing->setHeight(50);
                // $objDrawing->setWidth(50);
                $objDrawing->setWorksheet($objPHPExcel->getActiveSheet());
                if (isset($offset[$columnString . "-" . $d['row']])) {
                    $objDrawing->setOffsetX($offset[$columnString . "-" . $d['row']]);
                    $offset[$columnString . "-" . $d['row']] += $newWidth;
                } else {
                    $offset[$columnString . "-" . $d['row']] = $newWidth;
                }
                $objPHPExcel->getActiveSheet()->getRowDimension($d['row'])->setRowHeight(50);
            }
            if (!empty($offset)) {
                //设置有图片的excel宽度
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
                    //某列最长宽度作为整列宽度
                    //宽度算出来 等比缩放后是像素 excel设置宽度是字符
                    $objPHPExcel->getActiveSheet()->getColumnDimension($offsetColumn[0])->setWidth($columnArr[$offsetColumn[0]] / 7);
                }
            }
        }
        $objWriter->save($reportFile);

    }

    /**
     * 验证表头是否合法
     * @param array $params
     * @return bool
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     */
    public function checkHeader(array $params)
    {

        //系统模板的表头
        $importInfo = $this->getModuleHeader($params);
        $moduleHeader = $importInfo['header'] ?? ($importInfo[0]['header'] ?? []);
        //todo 写个方法这边去获取系统配置，如果是二级头 endRow是从2开始
        list($endRow, $moduleHeader) = $this->parseModuleHeader($moduleHeader);
        $importHeader = $this->getExcelData($params['file'], 1, $endRow, [], true);
        $result = $this->compareHeader($importHeader, $moduleHeader, $endRow);
        return $result;

    }


    /**
     * 比较表头是否一致
     * @param $importHeader
     * @param $moduleHeaders
     * @param int $headerLength
     * @return bool
     */
    public function compareHeader($importHeader, $moduleHeaders, $headerLength = 1)
    {
        if (empty($moduleHeaders)) {
            return false;
        }
        //todo 判断长度和空
        $userHeader = $importHeader[0]; // todo 判断是不是二级头来决定结构
        if ($headerLength == 2) {
            foreach ($importHeader[1] as $key => $value) {
                if (!empty($value)) {
                    $userHeader[$key] = $value;
                }
            }
        }
        if (array_values($moduleHeaders) != array_values($userHeader)) {
            return false; //返回头格式应该是哪种
        }
        return true;

    }

    /**
     * 获取系统提供头部的长度
     * @param $moduleHeader
     * @return int
     */
    public function getModuleHeaderLength($moduleHeader)
    {
        $headerLength = 1;
        foreach ($moduleHeader as $fieldKey => $fieldValue) {
            if (array_key_exists("children", $fieldValue)) {
                $headerLength = 2;
                break;
            }
        }

        return $headerLength;
    }


    /**
     * 把导入的数据传递到各个模块
     * @param $header
     * @param $data
     * @param $config
     * @param $param
     * @return array
     */
    public function importData($header, $data, $config, $param)
    {

        if (empty($data)) {
            return [];
        }
        try{
            $dataFilter = $this->parseImportData($header, $data);
        }catch(\Exception $e){
            $this->throwException(['code' => ['', '导入数据不匹配']]);
        }

        if (isset($config['after'])) {
            $param['after'] = $config['after'];
        }
        if (isset($config['filter'])) {
            //字段校验
            $dataFilter = app($config['filter'][0])->{$config['filter'][1]}($dataFilter, $param);
        }
        if (isset($config['dataSubmit'])) {
            $result = app($config['dataSubmit'][0])->{$config['dataSubmit'][1]}($dataFilter, $param);
            return $result;
        } else {
            $dataFilter = app('App\EofficeApp\FormModeling\Services\FormModelingService')->importDataFilterBack($config['module'], $dataFilter, $param);
            return app('App\EofficeApp\FormModeling\Services\FormModelingService')->importCustomData($config['module'], $dataFilter, $param);
        }

    }


}