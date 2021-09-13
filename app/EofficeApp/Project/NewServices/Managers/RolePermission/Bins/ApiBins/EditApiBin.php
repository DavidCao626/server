<?php

namespace App\EofficeApp\Project\NewServices\Managers\RolePermission\Bins\ApiBins;

use App\EofficeApp\Project\NewServices\Managers\DataManager;
use App\EofficeApp\Project\NewServices\Managers\HelpersManager;
use App\EofficeApp\Project\NewServices\Managers\RolePermission\PermissionManager;
use Illuminate\Support\Collection;
use Illuminate\Support\Arr;
class EditApiBin extends BaseApiBin
{
    public function __construct($apiConfig)
    {
        parent::__construct($apiConfig);
        $this->type = 'edit';
    }

    // 填充数据，根据配置过滤数据并设置数据
    public function fillRelationData(Collection &$relations,  array $data, $functionPageId)
    {
        foreach ($relations as &$relation) {
            $config = object_get($relation, "function_page_configs", []);
            $config = Arr::get($config, $functionPageId, []);
            $innerConfig = Arr::get($config, 'config', []);
            $this->fillData($relation, $data, $innerConfig);
        }
    }

    public function formatResult(&$result)
    {
        parent::formatResult($result);
        if (isset($result['model'])) {
            // 去除过去的权限，更新后权限会变动
            HelpersManager::toEloquentCollection($result['model'], function (&$data) {
                foreach ($data as &$item) {
                    $item->function_page_configs = [];
                    $item->all_roles = [];
                }
                return $data;
            });

            $dataManager = DataManager::getIns();
            $withModel = $dataManager->getApiParams('@with_model');
            $fPIs = $dataManager->getWitFPIs();
            if (!$withModel && !$fPIs) {
                unset($result['model']);
            } else {
                $fPIs && PermissionManager::setDataFunctionPages($result['model'], $dataManager, $fPIs);
            }
        }
    }
}