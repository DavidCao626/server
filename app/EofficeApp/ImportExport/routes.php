<?php

/*
  |--------------------------------------------------------------------------
  | Application Routes
  |--------------------------------------------------------------------------
  |
  | Here is where you can register all of the routes for an application.
  | It is a breeze. Simply tell Lumen the URIs it should respond to
  | and give it the Closure to call when that URI is requested.
  |
 */

$routeConfig = [
        //导出excel数据
        ['import-export/export-excel', 'export', 'post'],
        ['import-export/export/new', 'exportNew', 'post'],
        // 同步导出excel数据/具体数据，根据文件的key
        ['import-export/export/sync-by-key', 'exportByKey'],
        //导出字段
        ['import-export/export-excel/{from}/fields', 'getExportFields'],
        //导出具体数据
        ['import-export/export/data', 'exportByString', 'post'],
        //查看导出日志
        ['import-export/export/logs', 'getExportLogs'],
        //修改导出日志
        ['import-export/export/log/{id}', 'updateExportLog', 'put'],
        //导入excel预览数据
        ['import-export/import-export/import-excel', 'import'],
        //导入excel数据至数据库
        ['import-export/import-excel', 'importAddData', 'post'],
        //导入字段
        ['import-export/import-excel/{from}/fields', 'getImportFields'],
        //导出excel模板
        ['import-export/import-excel/{from}/template', 'getImportTemplateData','post'],
        //获取依据字段
        ['import-export/import-excel/{from}/primarys', 'getImportPrimarys'],
        ['import-export/import-excel/{module}/match-fields', 'getMatchFields'],
        // 上传导入文件
        ['import-export/import-excel/upload', 'importUpload', 'post'],
];
