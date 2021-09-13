<?php

namespace App\EofficeApp\LogCenter\Changes;
use App\EofficeApp\Project\NewServices\Managers\ProjectLogManager;

class ProjectQuestionChange extends BaseChange implements ChangeInterface
{
    public $id = 'question_id';

    /**
     * 字段名称转换
     */
    public function fields() 
    {
        $this->fields = [
            'question_name' => trans('project.name_of_the_problem'),
            'question_endtime' => trans('project.expiry_time'),
            'question_doperson' => trans('project.processing_person'),
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
}
