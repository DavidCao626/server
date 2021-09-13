<?php

namespace App\EofficeApp\LogCenter\Changes;
use App\EofficeApp\Project\NewServices\Managers\ProjectLogManager;

class ProjectDocumentChange extends BaseChange implements ChangeInterface
{
    public $id = 'doc_id';

    /**
     * 字段名称转换
     */
    public function fields() 
    {
        $this->fields = [
            'doc_name' => trans('project.doc_name'),
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
