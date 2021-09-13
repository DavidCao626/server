<?php

namespace App\EofficeApp\LogCenter\Changes;
use App\EofficeApp\Project\NewRepositories\ProjectManagerRepository;
use App\EofficeApp\Project\NewServices\Managers\ProjectLogManager;
use App\EofficeApp\Project\NewServices\ProjectService;

class ProjectManagerChange extends BaseChange implements ChangeInterface
{
    public $id = 'manager_id';

    /**
     * 字段名称转换
     */
    public function fields() 
    {
        $this->fields = [
            'manager_state' => trans('project.fields.' . 'manager_state'),
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

    public function dynamicFields($logData)
    {
        //todo 动态字段具体逻辑
        $managerId = $logData->relation_sup_id;
        $project = ProjectManagerRepository::buildQuery()->withTrashed()->find($managerId);
        $managerType = $project ? $project->manager_type : 0;
        //基本上都是未删除得项目，否则前端没有吊起日志的入口
        if ($managerType) {
            $projectTableKey = ProjectService::getProjectCustomTableKey($managerType);
            $keys =  [
                'manager_name' => mulit_trans_dynamic("custom_fields_table.field_name." . $projectTableKey . "_" . 'manager_name'),
                'manager_number' => mulit_trans_dynamic("custom_fields_table.field_name." . $projectTableKey . "_" . 'manager_number'),
                'manager_endtime' => mulit_trans_dynamic("custom_fields_table.field_name." . $projectTableKey . "_" . 'manager_endtime'),
                'manager_begintime' => mulit_trans_dynamic("custom_fields_table.field_name." . $projectTableKey . "_" . 'manager_begintime'),
                'manager_monitor' => mulit_trans_dynamic("custom_fields_table.field_name." . $projectTableKey . "_" . 'manager_monitor'),
                'manager_examine' => mulit_trans_dynamic("custom_fields_table.field_name." . $projectTableKey . "_" . 'manager_examine'),
                'manager_person' => mulit_trans_dynamic("custom_fields_table.field_name." . $projectTableKey . "_" . 'manager_person'),
            ];
        } else {
            $keys =  [
                'manager_name' => '-',
                'manager_number' => '-',
                'manager_endtime' => '-',
                'manager_begintime' => '-',
                'manager_monitor' => '-',
                'manager_examine' => '-',
                'manager_person' => '-',
            ];
        }

        $this->dynamicFields = $keys;
    }
}
