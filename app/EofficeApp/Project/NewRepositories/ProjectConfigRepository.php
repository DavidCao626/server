<?php

namespace App\EofficeApp\Project\NewRepositories;

use App\EofficeApp\Project\Entities\ProjectConfigEntity;
use App\EofficeApp\Project\NewServices\Managers\CacheManager;
use DB;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
class ProjectConfigRepository extends ProjectBaseRepository {

    const TaskProgressCacheKey = 'TASK_PROGRESS_SHOW_MODEL_CACHE_KEY';

    public static function buildQuery($params = [], $query = null): Builder
    {
        $query = ProjectConfigEntity::buildQuery($params, $query);

        return $query;
    }

    ##############任务进度显示模式配置管理###############################

    public static function getTaskProgressShowModel() {
        $value = CacheManager::getValueCache(self::TaskProgressCacheKey, function () {
            $data = self::buildQuery(['key' => 'task_progress_show_model'])->first();
            return Arr::get($data, 'value', 1);
        });
        return intval($value);
    }

    public static function delTaskProgressShowModelCache() {
        CacheManager::delCache(self::TaskProgressCacheKey);
    }

    ##############任务进度显示模式配置管理###############################

}
