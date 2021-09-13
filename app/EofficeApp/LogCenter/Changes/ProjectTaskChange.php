<?php

namespace App\EofficeApp\LogCenter\Changes;
use App\EofficeApp\Project\NewServices\Managers\ProjectLogManager;
use App\EofficeApp\Project\NewServices\ProjectService;

class ProjectTaskChange extends BaseChange implements ChangeInterface
{
    public $id = 'task_id';

    /**
     * 字段名称转换
     */
    public function fields() 
    {
        $this->fields = [
            'weights' => $this->getCustomFieldName('weights'),
            'task_persent' => $this->getCustomFieldName('task_persent'),
            'task_name' => $this->getCustomFieldName('task_name'),
            'task_endtime' => $this->getCustomFieldName('task_endtime'),
            'task_begintime' => $this->getCustomFieldName('task_begintime'),
            'task_persondo' => $this->getCustomFieldName('task_persondo'),
        ];
    }

    /**
     * 转换翻译值存储
     * @param $data
     * @param bool $mulit
     * @return Array
     *
     */
    public function parseData($data, $mulit = false) 
    {
        return ProjectLogManager::formatInsertTranslateValue($data);
    }

    private function getCustomFieldName($field)
    {
        $projectTableKey = ProjectService::getProjectTaskCustomTableKey(1);
        return mulit_trans_dynamic("custom_fields_table.field_name." . $projectTableKey . "_" . $field);
    }
}
