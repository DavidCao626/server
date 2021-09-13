<?php

namespace App\EofficeApp\Project\NewServices\Managers\RolePermission\Bins\ApiBins;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
class AddApiBin extends BaseApiBin
{
    public function __construct($apiConfig)
    {
        parent::__construct($apiConfig);
        $this->type = 'add';
    }

    /**
     * 填充新增数据的值
     * @param Model $model 新数据的模型对象
     * @param array $data 数据来源
     * @param Model $relation 新增数据依赖的上级
     * @param string $functionPageId 功能id
     */
    public function fillAddData(Model $model, array $data, Model $relation, $functionPageId)
    {
        $config = Arr::get($relation, "function_page_configs.{$functionPageId}");
        $innerConfig = Arr::get($config, 'config', []);
        $this->fillData($model, $data, $innerConfig);
    }

}