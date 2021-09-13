<?php

namespace App\EofficeApp\ImportExport\Controllers;

use http\Url;
use Queue;
use App\Jobs\ImportExportJob;
use \Illuminate\Http\Request;
use App\EofficeApp\Base\Controller;
use App\EofficeApp\ImportExport\Services\ImportExportService;
/**
 * 导入导出
 *
 * @author qishaobo
 *
 * @since 2016-01-06
 */
class ImportExportController extends Controller
{
    /**
     * 导入导出资源库
     * @var object
     */
    private $ArchivesService;

    private $importExportService;
    private $exportService;
    private $langService;
    public function __construct(
        Request $request,
        ImportExportService $importExportService
    ) {
        parent::__construct();
        $this->request = $request;
        $this->userInfo = $this->own;
        $this->importExportService = $importExportService;
        $this->exportService = 'App\EofficeApp\ImportExport\Services\ExportService';
        $this->langService = 'App\EofficeApp\Lang\Services\LangService';
    }

    /**
     * 导出excel数据
     *
     * @return string 导出文件路径
     *
     * @author qishaobo
     *
     * @since  2016-02-29
     */
    public function export()
    {
        $param = $this->request->all();

        if (!isset($param['param'])) {
            $param['param'] = [];
        }
        $param['param']['user_info'] = $this->own;
        $param['param']['user_info']['user_info'] = $this->own;
        $langType = app($this->langService)->getUserLocale($this->own['user_id']);
        $param['param']['lang_type'] = $langType;
        $param['param']['server_info'] = $_SERVER;
        $param['param']['server_info']['REQUEST_ROOT'] = $this->request->root();
        $exportType = $this->importExportService->verifyExportType();
        if($exportType == "async") {
            Queue::push(new ImportExportJob(['handle' => 'export', 'param' => $param]), null, 'eoffice_import_export_queue');
            return $this->returnResult(["type" => $exportType]);
        } else if($exportType == "sync") {
            $param["export_type"] = $exportType;
            $syncResult = $this->importExportService->exportJobHandleExport($param);
            return $this->returnResult(["type" => $exportType,"key" => $syncResult]);
        }
    }
    public function exportNew()
    {
        $params = $this->request->all();
        $params['param'] = $params['param'] ?? [];
        $params['param']['user_info'] = $this->own;
        $langType = app($this->langService)->getUserLocale($this->own['user_id']);
        $params['param']['lang_type'] = $langType;
        $params['param']['server_info'] = $_SERVER;
        $params['param']['server_info']['REQUEST_ROOT'] = $this->request->root();
        return $this->returnResult(app($this->exportService)->export($params));
    }
    /**
     * 导出，同步导出的时候，根据返回的文件key，导出文件
     * @return [type] [description]
     */
    public function exportByKey() {
        $param = $this->request->all();
        if(isset($param["key"])) {
            $key = $param["key"];
            return $this->download($key);
        } else {
            return "";
        }
    }

    /**
     * 导出
     *
     * @return string 导出文件路径
     *
     * @author qishaobo
     *
     * @since  2017-02-20
     */
    public function exportByString()
    {
        $param = $this->request->all();
        if (empty($param['data'])) {
            return $this->returnResult(['code' => ['export_data_empty', 'import']]);
        }
        $param['user_id'] = $this->userInfo['user_id'];
        $langType = app('App\EofficeApp\Lang\Services\LangService')->getUserLocale($this->userInfo['user_id']);
        $param['lang_type'] = $langType;
        $exportType = $this->importExportService->verifyExportType();
        if($exportType == "async") {
            Queue::push(new ImportExportJob(['handle' => 'exportString', 'param' => $param]), null, 'eoffice_import_export_queue');
            return $this->returnResult(["type" => $exportType]);
        } else if($exportType == "sync") {
            $param["export_type"] = $exportType;
            $syncResult = $this->importExportService->exportJobHandleExportString($param);
            return $this->returnResult(["type" => $exportType,"key" => $syncResult]);
        }
    }

   /**
     * 获取导出数据模板
     *
     * @param  string $from 文件路径
     *
     * @return array 查询结果
     *
     * @author qishaobo
     *
     * @since  2016-06-16
     */
    public function getImportTemplateData($from)
    {
        $field = $this->importExportService->getImportTemplateData($from, $this->own,$this->request->all());
        return $this->returnResult($field);
    }
    public function getMatchFields($module)
    {
        $param = $this->request->all();
        $attachmentId = $param['file'] ?? '';
        $attachmentList = app('App\EofficeApp\Attachment\Services\AttachmentService')->getOneAttachmentById($attachmentId);
        if (isset($attachmentList['temp_src_file'])) {
            $param['file'] = $attachmentList['temp_src_file'];
        }
        return $this->returnResult($this->importExportService->getMatchFields($module, $param, $this->own));
    }
    /**
     * 导入excel数据至数据库
     *
     * @return json 导入结果
     *
     * @author qishaobo
     *
     * @since  2016-02-29
     */
    public function importAddData()
    {
        $param = $this->request->all();
        //使用新接口
        if(isset($param['version']) && $param['version'] == 2){
            $param['user_info'] = $this->own;
            $langType = app('App\EofficeApp\Lang\Services\LangService')->getUserLocale($this->own['user_id']);
            $param['lang_type'] = $langType;
//            $attachmentId = $param['file'] ?? '';
//            $attachmentList = app('App\EofficeApp\Attachment\Services\AttachmentService')->getOneAttachmentById($attachmentId);
//            if (isset($attachmentList['temp_src_file'])) {
//                $param['file'] = $attachmentList['temp_src_file'];
//            }
            $result = app('App\EofficeApp\ImportExport\Services\ImportService')->import($param);
            return $this->returnResult($result);
        }else{
            //保留之前的接口
            $param['user_info'] = $this->own;
            $langType = app('App\EofficeApp\Lang\Services\LangService')->getUserLocale($this->own['user_id']);
            $param['lang_type'] = $langType;
            $attachmentId = $param['file'] ?? '';
            $attachmentList = app('App\EofficeApp\Attachment\Services\AttachmentService')->getOneAttachmentById($attachmentId);
            if (isset($attachmentList['temp_src_file'])) {
                $param['file'] = $attachmentList['temp_src_file'];
            }
            // $param['importMethod'] = 'sync';
            if(isset($param['importMethod']) && $param['importMethod'] == 'sync') {
                $result = $this->importExportService->syncImportData($param);
                return $this->returnResult($result);
            }else{
                // 开启关闭excel队列
                // ！！这注释，修改后不要提交，不然不走队列了！！
                // (new ImportExportJob(['handle' => 'import', 'param' => $param]))->handle();
               Queue::push(new ImportExportJob(['handle' => 'import', 'param' => $param]), null, 'eoffice_import_export_queue');
                $response = trans('importexport.importing_data', [], $langType);
                return $this->returnResult($response);
            }
        }

    }

    /**
     * 获取导入字段
     *
     * @param  string $from 文件路径
     *
     * @return array 查询结果
     *
     * @author qishaobo
     *
     * @since  2016-02-29
     */
    public function getImportFields($from)
    {
        $field = $this->importExportService->getImportFields($from, $this->own, []);
        return $this->returnResult($field);
    }

    /**
     * 获取依据字段
     *
     * @param  string $from 文件路径
     *
     * @return array 查询结果
     *
     * @author qishaobo
     *
     * @since  2017-01-16
     */
    public function getImportPrimarys($from)
    {
        $methods = $this->importExportService->getImportPrimarys($from, $this->own, $this->request->all());
        return $this->returnResult($methods);
    }

    /**
     * 下载文件
     *
     * @param  string $key 下载标识
     *
     * @return file
     *
     * @author qishaobo
     *
     * @since  2017-02-21
     */
    public function download($key)
    {
        return app($this->exportService)->download($key, $this->own);
    }

    /**
     * 修改导出日志
     *
     * @param  integer $id 日志id
     *
     * @return array 查询结果
     *
     * @author qishaobo
     *
     * @since  2017-04-17
     */
    public function updateExportLog($id)
    {
        $result = $this->importExportService->updateExportLog($id, $this->request->all());
        return $this->returnResult($result);
    }

    /**
     * 查看导出日志
     *
     * @param  integer $id 日志id
     *
     * @return array 查询结果
     *
     * @author qishaobo
     *
     * @since  2017-04-17
     */
    public function getExportLogs()
    {
        $result = $this->importExportService->getExportLogs($this->request->all());
        return $this->returnResult($result);
    }


    /**
     * 导入上传文件
     * @return array
     */
    public function importUpload()
    {
        return $this->returnResult(
            $this->importExportService->importUpload($this->request->all(), $this->own, $_FILES)
        );
//        return $this->returnResult(
//            app('App\EofficeApp\ImportExport\Services\ImportService')->importUpload($this->request->all(), $this->own, $_FILES)
//        );
    }

    public function importUpload2()
    {
        return $this->returnResult(
            app('App\EofficeApp\ImportExport\Services\ImportService')->importUpload($this->request->all(), $this->own, $_FILES)
        );
    }

}
