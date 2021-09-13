<?php

namespace App\EofficeApp\Project\NewServices\Managers\RolePermission;

use App\EofficeApp\Project\Entities\FunctionPageApiEntity;
use App\EofficeApp\Project\NewServices\Managers\CacheManager;
use App\EofficeApp\Project\NewServices\Managers\DataManager;
use App\EofficeApp\Project\NewServices\Managers\RolePermission\Bins\FunctionPageApiBin;
use Carbon\Carbon;

class FunctionPageManager
{

    public static function initFunctionPageApiBin()
    {
        $params = [
            'function_page_id' =>  DataManager::getIns()->getFunctionPageBin()->getFunctionPageId(),
            'function_name' => DataManager::getIns()->getApiBin()->getAction()
        ];
        $functionPageApiConfig = FunctionPageApiEntity::buildQuery($params)->first();
        DataManager::getIns()->setFunctionPageApiBin( new FunctionPageApiBin($functionPageApiConfig->toArray()));
    }

    // 【脚本】根据配置生成数据库数据
    public static function initFunctionPageApiData() {
        $functionPageApiConfig = self::getFunctionPageApiConfig();
        $data = [];
        $now = Carbon::now()->toDateTimeString();
        foreach ($functionPageApiConfig as $functionPageId => $functionNames) {
            foreach ($functionNames as $functionName) {
                $data[] = [
                    'function_page_id' => $functionPageId,
                    'function_name' => $functionName,
                    'filter_field_config' => '',
                    'data_function_permission' => '',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }
        if ($data) {
            $data = array_chunk($data, 1000);
            foreach ($data as $batchData) {
                FunctionPageApiEntity::query()->insert($batchData);
            }
        }
    }

    // 脚本关联
    public static function getFunctionPagesConfig() {
        return CacheManager::getProjectConfig('function_pages');
    }

    // 脚本关联
    public static function getFunctionPageApiConfig() {
        return CacheManager::getProjectConfig('function_page_api');
    }
}
