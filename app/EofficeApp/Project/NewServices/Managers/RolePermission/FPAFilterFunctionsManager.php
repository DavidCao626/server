<?php

namespace App\EofficeApp\Project\NewServices\Managers\RolePermission;

// api的过滤函数写在这个类
class FPAFilterFunctionsManager
{

    public static function testFrontTaskComplete($model)
    {
        $frontTask = null;
        $model->task_frontid && $frontTask = $model->front_task;
        return !($frontTask && (!$frontTask['complete_date'] || $frontTask['complete_date'] == '0000-00-00 00:00:00' || $frontTask['complete_date'] == '0000-00-00'));
    }

    // 存在文档关联或父文件夹不能删除
    public static function canDeleteDocumentDir($model)
    {
        if (!isset($model['son_dir_count'])) {
            $model->son_dir_count = $model->sonDir()->count();
        }
        if (!isset($model['documents_count'])) {
            $model->documents_count = $model->documents()->count();
        }
        $can = !$model->son_dir_count && !$model->documents_count;
        return $can;
    }
}
