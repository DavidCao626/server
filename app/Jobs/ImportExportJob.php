<?php

namespace App\Jobs;

set_time_limit(0);

use Eoffice;
use Lang;
use Illuminate\Support\Facades\Redis;

ini_set('memory_limit', '1024M');

class ImportExportJob extends Job
{
    public $param;

    private $emptyData = 'EMPTY_DATA';
    // excel导入每次处理条数
    const PER_NUM = 2000;

    /**
     * 数据导入导出
     *
     * @return void
     */
    public function __construct($param)
    {
        $this->param = $param;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        global $array;
        $array = [];
        $param = $this->param;
        unset($GLOBALS['langType']);
        $GLOBALS['langType'] = isset($param['param']['lang_type']) ? $param['param']['lang_type'] : (isset($param['param']['param']['lang_type']) ? $param['param']['param']['lang_type'] : null);
        Lang::setLocale($GLOBALS['langType']);
        $handle = 'handle' . ucwords($param['handle']);
        // 现在这里会分发到三个函数：
        // handleExport
        // handleExportString
        // handleImport
        $this->$handle($param['param']);
    }

    /**
     * 数据导出，导出为excel/eml，数据源来自于配置文件里的某个模块的某个函数
     *
     * @return void
     */
    public function handleExport($param)
    {
        $exportResult = app("App\EofficeApp\ImportExport\Services\ImportExportService")->exportJobHandleExport($param);
        return $exportResult;
    }

    /**
     * 数据导出，数据源是一段已知数据
     *
     * @return void
     */
    public function handleExportString($param)
    {
        $exportResult = app("App\EofficeApp\ImportExport\Services\ImportExportService")->exportJobHandleExportString($param);
        return $exportResult;
    }

    public function validateImportExcel(array $params)
    {
        $err = false;
        $module = $params['module'] ?? null;
        $file = $params['file'] ?? null;
        $uid = $params['user_info']['user_id'] ?? '';
        if ($module === null || $file === null) {
            $err = true;
        }
        return [$err, $uid, $file, $module];
    }

    public function getImportExcelInfo(array $params)
    {
        $title = trans('export.' . $params['module']) . "_" . trans("import.import_report");
        if (!$config = config('import.' . $params['module'])) {
            $config = [];
            $title = app('App\EofficeApp\FormModeling\Services\FormModelingService')->getImportResultTitle($params['module']);
            
            $importParam = isset($params['params']) ? $params['params'] : [];
            $importParam['user_info'] = $params['user_info'];
            $importInfo = app('App\EofficeApp\FormModeling\Services\FormModelingService')->getImportFields($params['module'], $importParam);
            $config['module'] = $params['module'];
        } else {
            $configFieldsForm = $config['fieldsFrom'] ?? [];
            $configsParams = $params['params'] ?? [];
            list($className, $methodName) = array_values($config['fieldsFrom']);
            if (count($configFieldsForm) >= 4) {
                $importInfo = app($className)->{$methodName}($params['user_info'], $configsParams);
            } else if (count($configFieldsForm) === 3) {
                $configsParams = $configFieldsForm[2] === 'userInfo' ? $params['user_info'] : ($configFieldsForm[2] === 'data' ? $configsParams : []);
                $importInfo = app($className)->{$methodName}($configsParams);
            } else {
                $importInfo = app($className)->{$methodName}($configFieldsForm);
            }
        }
        $importHeaders = $importInfo['header'] ?? ($importInfo[0]['header'] ?? []);
        if (empty($importHeaders)) {
            return [true, $importHeaders, $config, $title];
        }
        $type  = '';
        $allFields = [];
       foreach ($importHeaders as $k => $v) {
           if(isset($v['children'])){
                $type = 'detail'; //明细处理
                // array_push($allFields,$k);
                foreach ($v['children'] as $key => $value) {
                    array_push($allFields,$key);
                }
           }else{
                array_push($allFields,$k);
           }
       }
       if($type && $type == 'detail'){
            $originHeaders = $allFields;
            $config['type'] = 'detail';
            $config['header'] = $importHeaders;
            $originDetailHeaders = $importHeaders;
       }else{
            $originHeaders = array_keys($importHeaders);
       }
        // 合并单元格特殊处理
        if ($params['module'] == 'salary') {
            $tempImportHeader = $this->getExcelData($params['file'], 1, 2);
            $firstLineTemp = [];
            if (isset($tempImportHeader[0]) && isset($tempImportHeader[1]) && !empty($tempImportHeader[1])) {
                foreach ($tempImportHeader[1] as $key => $value) {
                    // $firstLineTemp[] = $value == null ? ($tempImportHeader[0][$key] ?? '') : $value;
                    if($value == null || (is_array($value) && $value[0] == '')) {
                        $firstLineTemp[] = $tempImportHeader[0][$key] ?? '';
                    } else if($value != null) {
                        if(isset($tempImportHeader[0][$key]) && $tempImportHeader[0][$key]) {
                            // 第二行的值和第一行的值同时存在，表示此列是合并列，收集两个头部
                            $firstLineTemp[] = $tempImportHeader[0][$key];
                            $firstLineTemp[] = $value;
                        } else {
                            $firstLineTemp[] = $value;
                        }
                    }
                }
                $tempImportHeader = [$firstLineTemp];
                $temp = array_filter($importHeaders, function ($value) {
                    return !isset($value['_MERGE_']);
                });
                $originHeaders = array_keys($temp);
            }
        } else {
            $tempImportHeader = $this->getExcelData($params['file'], 1, 2);
        }
        if (empty($tempImportHeader)) {
            return [true, $tempImportHeader, $config, $title];
        }
        //各模块返回的字段组合
        $fieldsArray = [];
        foreach ($importHeaders as $key => $value) {
            if(isset($value['children']) && !empty($value['children'])){
                // array_push($fieldsArray,$value['data']);
                foreach ($value['children'] as $childk => $childv) {
                    array_push($fieldsArray,$childv['data']);
                }
            }else if (isset($value['data']) && $value['data']) {
                array_push($fieldsArray,$value['data']);
            }
        }
        if($type == 'detail'){
            $detailFields = [];
            $index = 1;
            foreach ($tempImportHeader[1] as $key => $value) {
                if($value == null) {
                    $firstLineTemp[$index] = $tempImportHeader[0][$key] ?? '';
                    $index++;
                } else if($value != null) {
                    if(isset($tempImportHeader[0][$key]) && $tempImportHeader[0][$key]) {
                        // 第二行的值和第一行的值同时存在，表示此列是合并列，收集两个头部
                        // $firstLineTemp[$index] = $tempImportHeader[0][$key];
                        // $index++;
                        $firstLineTemp[$index] = $value;
                        $index++;
                    } else {
                        $firstLineTemp[$index] = $value;
                        $index++;
                    }
                }
            }
            $importHeaders = $firstLineTemp;
        }else{
            $importHeaders = array_filter($tempImportHeader[0]);

        }
        //字段是否对应 之前只是解决了总数一样就可以了，现在判断具体导入字段是否一样
        if (!empty($fieldsArray) && !empty(array_diff($fieldsArray, $importHeaders))) {
            return [true, $fieldsArray, $config, $title];
        }
        $fieldsHeaders = [];
        if (isset($params['match_fields']) && !empty($params['match_fields'])) {
            $importFields = [];
            foreach ($params['match_fields'] as $index => $item) {
                $importFields[$item] = $index;
            }
            $vheader = array_values($importHeaders);
            if (!empty($diffOriginFields = array_diff(array_keys($importFields), $vheader))) {
                return [true, $fieldsHeaders, $config, $title];
            }

            foreach ($importHeaders as $k => $v) {
                if (isset($importFields[$v])) {
                    $fieldsHeaders[$importFields[$v]] = (string) $k;
                }
            }
        } else {
            if (count($originHeaders) != count($importHeaders) && $params['module'] != 'salary') {
                return [true, $fieldsHeaders, $config, $title];
            }
            for ($i = 0; $i < count($originHeaders); $i++) {
//                    $fieldsHeaders[$importHeaders[$i]] = $originHeaders[$i];
                $fieldsHeaders[$originHeaders[$i]] = (string) $importHeaders[$i + 1];
            }
        }
        return [false, $fieldsHeaders, $config, $title];
    }

    /**
     * 数据导入
     *
     * @return bool
     */
    public function handleImport(array $params)
    {
        list($err, $uid, $file, $module) = $this->validateImportExcel($params);
        if ($err) {
            return $this->importExcelErrorCallback($uid, $file, $module);
        }
        list($err, $fieldsHeaders, $config, $title) = $this->getImportExcelInfo($params);
        if ($err) {
            return $this->importExcelErrorCallback($uid, $file, $module);
        }
        $start = $config['startRow'][0] ?? 2;
        $end = $start + self::PER_NUM;
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
        $fileData = $this->getExcelFile($file);
        
        if(isset($config['type']) && $config['type'] == 'detail'){
            $start = 3;
        }
        while (!empty($data = $this->getExcelData($file, $start, $end, $fileData))) {
            if ($data === $this->emptyData) {
                return $this->importExcelErrorCallback($uid, $file, $module, $this->emptyData);
            }
            $info = $this->importData($fieldsHeaders, $data, $config, $params);
            list($reportConfig['startRow'], $reportConfig['endRow']) = [$start, $end];
            // 写入导入结果信息
            $this->writeExcelReportResult($reportFile, $info, $start, $end);
            $start = ++$end;
            $end = $start + self::PER_NUM;
        }
        $filePath = transEncoding($reportFile, 'UTF-8');
        $reportConfig['file'] = $reportFile;
        $files = explode('/', str_replace('\\', '/', $filePath));
        $filesInfo = explode('.', end($files));
        foreach ($filesInfo as $key => $value) {
            $filesInfo[$key] = transEncoding($value, 'UTF-8');
        }
        $fileName = $filesInfo[0];
        $key = md5($reportConfig['user_id'] . $fileName . time());
        app("App\EofficeApp\ImportExport\Services\ImportExportService")->exportJobExportPublish($reportConfig, $filePath, $fileName, $key);
        return true;
    }

    private function writeExcelReportResult($reportFile, $info, $start, $end)
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
            if($dim > 2){
                $add = $this->getArrayMaxLength($item);
                $end = $start+$add-1;
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
                if($add > 1){
                    $objPHPExcelSheet->mergeCellsByColumnAndRow($highestColumm,$start,$highestColumm,$end);
                }
                if($begin == 3){
                    $objPHPExcelSheet->mergeCellsByColumnAndRow($highestColumm,1,$highestColumm,2);
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
                if($add > 1){
                    $objPHPExcelSheet->mergeCellsByColumnAndRow($highestColumm+1,$start,$highestColumm+1,$end);
                }
            }
            $start+= $add;
        }
        if($begin == 3){
            $objPHPExcelSheet->mergeCellsByColumnAndRow($highestColumm+1,1,$highestColumm+1,2);
        }
        $collect = $objPHPExcelSheet->getDrawingCollection() ? count($objPHPExcelSheet->getDrawingCollection()) : 0;
        if ($collect > 0) {
            for ($i = 0; $i < $collect; $i++) {
                $objPHPExcelSheet->getDrawingCollection()->offsetUnset($i);
            }
        }
        $objWriter = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($objPHPExcel, 'Xlsx');
        $fileData = $this->getExcelFile($reportFile);
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

    public function getArrayDim($vDim)
    {
//        $count = 0;
        if (!is_array($vDim))
            return 0;
        else {
            $max1 = 0;
            foreach ($vDim as $item1) {
//                $count = count($item1);
                $t = $this->getArrayDim($item1);
                if ($t > $max1) {
                    $max1 = $t;
                }
            }
            return $max1 + 1;
        }
    }
    public function getArrayMaxLength($arr)
    {
        $count = 0;
        if(!empty($arr)){
            foreach ($arr as $key => $value) {
                if($key == 'importReason'  || $key == 'importResult'){
                    continue;
                }
                if(is_array($value) && count($value) > $count){
                    $count = count($value);
                }
                if($value != null){
                    if(is_array($value)){
                        if(count($value) > $count){
                            $count = count($value);
                        }
                    }else{
                        $count = 1;
                    }
                }


            }
        }
        return $count;

    }

    private function importExcelErrorCallback($uid, $file, $module, $errorType = '')
    {
        $dir = createExportDir();
        $suffix = mb_substr($file, strrpos($file, '.')+1);
        if (!$config = config('import.' . $module)) {
            $fileName = app('App\EofficeApp\FormModeling\Services\FormModelingService')->getImportResultTitle($module);
            $fileName .= date('YmdHis');
        } else {
            $fileName = trans('import.' . $module) . "_" . trans("import.import_report") . date('YmdHis');
        }
        $savefilePath = $dir . $fileName .".".$suffix;
        copy($file, $savefilePath);
        $objPHPExcel = \PhpOffice\PhpSpreadsheet\IOFactory::load($savefilePath);
        $sheet = $objPHPExcel->getSheet(0);
        $highestRow = $sheet->getHighestRow(); // 取得总行数
        $highestColumm = $sheet->getHighestColumn();
        $highestColumm = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumm);
        $transMessage = '';
        if ($errorType === '') {
            $transMessage = trans("import.import_report_fail_excel");
        } else if ($errorType === $this->emptyData) {
            $transMessage = trans("import.import_report_empty_excel");
        }
        $sheet->setCellValueByColumnAndRow(0, ++$highestRow, $transMessage);
        $sheet->getStyleByColumnAndRow(0, $highestRow)->getFont()->getColor()->setRGB('DC143C');
        $objWriter = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($objPHPExcel, 'Xlsx');
        $objWriter->save($savefilePath);

        if ($objPHPExcel instanceof \PhpOffice\PhpSpreadsheet\Spreadsheet) {
            $objPHPExcel->disconnectWorksheets();
        }
        $key = md5($uid . $fileName . time());
        $logData = [
            'export_name' => $fileName,
            'export_key' => $key,
            'export_file' => $savefilePath,
            'user_id' => $uid,
            'export_type' => 2,
            'is_read' => 0,
        ];

        //记录日志
        $logObj = app("App\EofficeApp\ImportExport\Services\ImportExportService")->addExportLog($logData);

        //发送消息提醒参数
        $sendData = [
            'toUser' => $uid,
            'remindState' => 'export.download',
            'remindMark' => 'export-download',
            'sendMethod' => ['sms'],
            'isHand' => true,
            'content' => $fileName,
            'stateParams' => ['key' => $key],
            'contentParam' => $fileName,
        ];
        Eoffice::sendMessage($sendData);
        $exportChannelParams = [
            'userId'      => $uid,
            'downloadKey' => $key,
        ];
        // OA实例模式下，发送REDIS_DATABASE参数
        if (envOverload('CASE_PLATFORM', false)) {
            $exportChannelParams['REDIS_DATABASE'] = envOverload('REDIS_DATABASE', 0);
        }
        Redis::publish('eoffice.export-channel', json_encode($exportChannelParams));
    }

    private function newWriteContent(string $func, string $content)
    {
        static $caches = [];
        if (!isset($caches[$func][$content])) {
            try {
                $caches[$func][$content] = app("App\Utils\ImportExportFliter")->$func($content);
            } catch (\Exception $e) {
                $caches[$func][$content] = '';
            }
        }
        return $caches[$func][$content];
    }

    public function importData($header, $data, $config, $param)
    {
        if (empty($data)) {
            return [];
        }
         
        $dataFilter = [];
        foreach ($data as $k => $v) {
            $i = 1;
            foreach ($header as $key => $value) {
                if (strpos($key, '|') !== false) {
                    list($fieldName, $filter) = explode('|', $key);
                    $excelValue = key_exists($i, $v) ? $v[$i] : (key_exists($value, $v) ? $v[$value] : '');
                    $fieldValue = $this->newWriteContent($filter, $excelValue);
                } else {
                    $fieldName = $key;
                    $fieldValue = key_exists($i, $v) ? $v[$i] : (key_exists($value, $v) ? $v[$value] : '');
                }
                $dataFilter[$k][$fieldName] = $fieldValue;
                ++$i;
            }
        }
        //多行合并处理
        if(isset($config['type']) && $config['type'] == 'detail'){
            $newData = [];
            $headerInfos = $config['header'];
            foreach ($dataFilter as $key => $value) {
                foreach ($headerInfos as $field => $fieldInfo) {
                    $detailData = [];
                    if(isset($fieldInfo['children'])){
                        foreach ($fieldInfo['children'] as $k => $v) {
//                          $count = isset($value[$k])?count($value[$k]):1;
//                          
                            //count计算方法震撼我全家，除非$value[$k]是数组我觉得$count = 1就行
//                            if(isset($value[$k])){
//                                if(is_array($value[$k])){
//                                    $count = count($value[$k]);
//                                }else{
//                                    $count = 1;
//                                }
//                            }else{
//                                $count = 1;
//                            }
                            $count = isset($value[$k]) ? (is_array($value[$k]) ? count($value[$k]) : 1) : 1;
                            for($i = 0; $i < $count; $i++){
                                $content = '';
                                if(isset($value[$k][$i]) && is_array($value[$k])){
                                    $content = $value[$k][$i];
                                }elseif(array_key_exists($k,$value)){
                                    $content = !is_array($value[$k])?$value[$k]:'';
                                }
                                $detailData[$i][$k] = $content;
                            }
                        }
                        $newData[$field] = $detailData;

                    }else{
                        $newData[$field] = $value[$field];
                    }
                }
                $dataFilter[$key] = $newData;
            }
        }
        if (isset($config['after'])) {
            $param['after'] = $config['after'];
        }
        if (isset($config['filter'])) {
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

    public function importReport($fields, $info, $config, $param = [])
    {
        if (empty($info['data'])) {
            return;
        }
        $fields[trans("import.import_result")] = 'importResult';
        $fields[trans("import.import_reason")] = 'importReason';

        $data = [
            'header' => array_flip($fields),
            'data' => $info['data'],
        ];
        $config["export_type"] = "async";
        $file = app("App\EofficeApp\ImportExport\Services\ImportExportService")->exportJobExportRealize($config, $data);

        return empty($config['file']) ? $file : $config['file'];
    }

    public function ExcelFilter($excelReader, $startRow, $endRow)
    {
        if ($startRow && $endRow) {
            $filter = new MyReadFilter();
            $filter->startRow = $startRow;
            $filter->endRow = $endRow;
            $excelReader->setReadFilter($filter);
        }

        return $excelReader;
    }
    public function transferMergeData($value)
    {
        $value = explode(':',$value);
        $begin = $value[0];
        $preg = '#([a-zA-Z+]+)(\d+)#';
        preg_match_all($preg, $begin,$m);
        $begin = [
            'row' => $m[2][0],
            'column' =>$m[1][0]
        ];
        $end = $value[1];
        preg_match_all($preg, $end,$m);
        $end = [
            'row' => $m[2][0],
            'column' =>$m[1][0]
        ];
        $mergeCells = [
            'begin'=> $begin,
            'end' =>  $end
        ];
        return $mergeCells;
    }
    public function getExcelData($file, $startRow, $endRow, $fileData = [])
    {
        $file = $this->fileTranscode($file);

        if (!is_file($file)) {
            return [];
        }

        $excelReader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader("Xlsx");
        //$excelReader->setReadDataOnly(true);

        // $excelReader = $this->ExcelFilter($excelReader, $startRow, $endRow);

        $objPHPExcel = $excelReader->load($file);
        $objWorksheet = $objPHPExcel->getActiveSheet();

        $highestRow = $objWorksheet->getHighestRow();
        $merges =   $objWorksheet->getMergeCells();
        $mergeCells = [];

        foreach ($merges as $key => $value) {
            $mergeInfo = $this->transferMergeData($value);
            if($mergeInfo['begin']['column'] == $mergeInfo['end']['column']){
                //合并多少行
                $column = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($mergeInfo['end']['column']);
                $mergeCells[$mergeInfo['begin']['row'].'-'.$mergeInfo['end']['row']][] = $column;
            }
        }
        if ($startRow > $highestRow) {
            if ($startRow === 2 || $startRow === 3) {
                return $this->emptyData;
            }
            return [];
        }
        $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($objWorksheet->getHighestColumn());
        $typeNumeric = \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC;
        $endRow = $highestRow >= $endRow ? $endRow : $highestRow;
//        $endRow = $highestRow;
        $data = [];
        $collect = [];
        for ($row = $startRow; $row <= $endRow; $row++) {
            for ($col = 1; $col <= $highestColumnIndex; $col++) {
                $cell = $objWorksheet->getCellByColumnAndRow($col, $row);
                $value = $cell->getValue();

                if ($cell->getDataType() == $typeNumeric) {
                    $formatcode = $cell->getStyle($cell->getCoordinate())->getNumberFormat()->getFormatCode();

                    if (strpos($formatcode, 'h') !== false) {
                        $formatDate = strpos($formatcode, 'y') !== false ? "Y-m-d H:i:s" : "H:i:s";
                        $value = getBeyondUnixDate($formatDate, \PhpOffice\PhpSpreadsheet\Shared\Date::excelToTimestamp($value));
                    } else if (strpos($formatcode, 'm') !== false) {
                        $value = getBeyondUnixDate("Y-m-d", \PhpOffice\PhpSpreadsheet\Shared\Date::excelToTimestamp($value));
                    } else {
                        $value = \PhpOffice\PhpSpreadsheet\Style\NumberFormat::toFormattedString($value, $formatcode);
                    }
                } else if (is_object($value)) {
                    $value = (string) $value;
                }

                $data[$row - 1][$col] = $value;
                if(!empty($mergeCells)){
                    foreach ($mergeCells as $line => $columnInfo) {
                        $lineData = explode("-",$line);
                        $start = $lineData[0];
                        $end = $lineData[1];
                        if($start>1 && $row>=$start && $row<=$end && !in_array($col,$columnInfo)){
                            $data[$row - 1][$col] = null;
                            $collect[$col.'-'.$start][] = $value;
                        }

                    }
                }
            }
        }

       if(!empty($collect)){
        foreach ($collect as $key => $value) {
            $key = explode("-",$key);
            $col = $key[0];
            $start = $key[1];
            $data[$start - 1][$col] = $value;

        }
       }
        if (!empty($fileData)) {
            foreach ($fileData as $index => $d) {
                $attRow  = $d['row'];
                $attColumn  = $d['column'];
                //附件先置空
                if ($d['row'] == 1) {
                    continue;
                }
                if (!empty($collect) && $d['row'] == 2) {
                    continue;
                }
                if(!empty($mergeCells)){
                    foreach ($mergeCells as $line => $columnInfo) {
                        $lineData = explode("-",$line);
                        $start = $lineData[0];
                        $end = $lineData[1];
                        if($attRow>=$start && $attRow<=$end && !in_array($attColumn,$columnInfo)){
                            $fileData[$index]['row'] =  $start;
                            $fileData[$index]['index'] =  $attRow - $start;
                            // $data[$start - 1][$d['column']] = [];
                        }

                    }
                }
                $data[$d['row'] - 1][$d['column']] = [];
            }
            foreach ($fileData as $d) {
                if ($d['row'] == 1) {
                    continue;
                }
                if (!empty($collect) && $d['row'] == 2) {
                    continue;
                }
                if (!is_array($data[$d['row'] - 1][$d['column']])) {
                    $data[$d['row'] - 1][$d['column']] = [];
                }
                if(isset($d['index'])){
                    //明细手机附件
                    $data[$d['row'] - 1][$d['column']][$d['index']]  = $d['attachment_id'];
                }else{
                    $data[$d['row'] - 1][$d['column']][]  = $d['attachment_id'];
                }

            }
        }

        // 去除最后的空行
        $data = $this->deleteEndEmptyRows($data);
        //Excel5 $this->_phpExcel->disconnectWorksheets();
        if (method_exists($excelReader, 'destroy')) {
            $excelReader->destroy();
        }
        return $data;
    }
    public function getExcelFile($file)
    {
        $excelReader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xlsx');
        $objPHPExcel = $excelReader->load($file);
        $objWorksheet = $objPHPExcel->getActiveSheet();

        $params = $this->param;
        $userId = isset($params['param']) ? $params['param']['user_info']['user_id'] : $params['user_info']['user_id'];
        $result = [];
        foreach ($objWorksheet->getDrawingCollection() as $img) {
            list($startImgColumn, $startImgRow) = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::coordinateFromString($img->getCoordinates());
            $imageFileName = $img->getCoordinates() . mt_rand(100, 999);
            $fileName = $imageFileName;
            $md5FileName = app("App\EofficeApp\Attachment\Services\AttachmentService")->getMd5FileName($imageFileName);
            $attachmentId = app("App\EofficeApp\Attachment\Services\AttachmentService")->makeAttachmentId($userId);
            $attachmentPath = app("App\EofficeApp\Attachment\Services\AttachmentService")->createCustomDir($attachmentId);
            $extension = getimagesize($img->getPath())['mime'];
            $extension = explode("/", $extension)[1];
            $extension = strtolower($extension);
            switch ($extension) {
                case 'jpg':
                case 'jpeg':
                case 'jfif':
                    $md5FileName .= '.jpg';
                    $imageFileName .= '.jpg';
                    $source = imagecreatefromjpeg($img->getPath());
                    imagejpeg($source, $attachmentPath . $md5FileName);
                    break;
                case 'gif':
                    $md5FileName .= '.gif';
                    $imageFileName .= '.gif';
                    $source = imagecreatefromgif($img->getPath());
                    imagegif($source, $attachmentPath . $md5FileName);
                    break;
                case 'png':
                    $md5FileName .= '.png';
                    $imageFileName .= '.png';
                    $source = imagecreatefrompng($img->getPath());
                    imagepng($source, $attachmentPath . $md5FileName);
                    break;
                case 'bmp':
                case 'x-ms-bmp':
                    $md5FileName .= '.bmp';
                    $imageFileName .= '.bmp';
                    copy($img->getPath(), $attachmentPath . $md5FileName);
                    break;
                default:
                    $extension .= $img->getExtension();
                    $md5FileName .= '.' . $extension;
                    $imageFileName .= '.' . $extension;
                    copy($img->getPath(), $attachmentPath . $md5FileName);
                    break;
            }
            $imageFilePath = $attachmentPath . $md5FileName;
            $res = app("App\EofficeApp\Attachment\Services\AttachmentService")->uploadThen(1, 0, $userId, $imageFileName, $imageFilePath, $imageFileName, []);
            @rmdir($attachmentPath);
            if (isset($res['attachment_id'])) {
                $columnNum = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($startImgColumn);
                $result[] = [
                    'column' => $columnNum,
                    'columnNum' => $startImgColumn,
                    'row' => $startImgRow,
                    'attachment_id' => $res['attachment_id'],
                ];
            }

        }
        return $result;

    }
    private function deleteEndEmptyRows($data)
    {
        $indexes = array_reverse(array_keys($data));
        foreach ($indexes as $index) {
            $i = 0;
            foreach ($data[$index] as $value) {
                if (is_array($value) && !empty($value)) {
                    ++$i;
                }
                if (!is_array($value) && trim($value)) {
                    ++$i;
                }
            }
            if ($i == 0) {
                unset($data[$index]);
            }
        }

        return $data;
    }

    public function fileTranscode($file, $from = "UTF-8", $to = "GBK")
    {
        if (empty($file)) {
            return '';
        }

        $files = explode('/', str_replace('\\', '/', $file));
        $fileNames = explode('.', end($files));
        if (count($fileNames) == 2) {
            return dirname($file) . '/' . iconv($from, $to, $fileNames[0]) . '.' . $fileNames[1];
        } else {
            return "";
        }
    }
}

class MyReadFilter implements \PhpOffice\PhpSpreadsheet\Reader\IReadFilter
{
    public $startRow = 1;
    public $endRow;

    public function readCell($column, $row, $worksheetName = '')
    {
        if (!$this->endRow) {
            return true;
        }

        if ($row >= $this->startRow && $row <= $this->endRow) {
            return true;
        }

        return false;
    }
}
