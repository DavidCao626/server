<?php


namespace App\EofficeApp\Elastic\Services\Options;


use App\EofficeApp\Elastic\Configurations\ConfigOptions;
use App\EofficeApp\Elastic\Configurations\Constant;
use App\EofficeApp\Elastic\Configurations\RedisKey;
use App\EofficeApp\Elastic\Services\BaseService;
use App\EofficeApp\Elastic\Services\ServiceManagementPlatform\ManagementPlatformService;
use App\Jobs\Elasticsearch\ElasticsearchJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

class RebuildService extends BaseService
{
    /**
     * 重新索引全站搜索文档
     */
    public function rebuildGlobalSearch(Request $request, $own)
    {
        $category = $request->request->get('category');
        $userId = $own['user_id'] ?? 0;

        // 如果为有效分类, 则通过队列更新
        if (in_array($category, Constant::$allIndices)) {

            $redisKey = RedisKey::REDIS_UPDATE;
            $updateInfo = Redis::get($redisKey);


            if (!$updateInfo) {
                $updateInfo = ManagementPlatformService::getElasticUpdateByQueueStructure();
            } else {
                $updateInfo = json_decode($updateInfo, true);;
            }
            if ($updateInfo['running']) {
                return ['code' => ['0x055007', 'elastic']]; // 同一时刻只能更新一个
            }
            $updateInfo['running'] = true;
            $updateInfo['runningCategory'] = $category;

            $redisValue = json_encode($updateInfo);
            Redis::set($redisKey, $redisValue);

            dispatch(new ElasticsearchJob([
                'type' => ConfigOptions::QUEUE_UPDATE_TYPE_REINDEX,
                'data' => [
                    'category' => $category,
                    'userId' => $userId,
                ],
            ]));
        }

        return [];
    }
}