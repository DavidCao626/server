<?php


namespace App\EofficeApp\Elastic\Services\MessageQueue;


use App\EofficeApp\Elastic\Configurations\ConfigOptions;
use App\EofficeApp\Elastic\Configurations\Constant;
use App\EofficeApp\Elastic\Services\BaseService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Log;

class ElasticsearchProcessor extends BaseService
{
    /**
     * process the job.
     *
     * @return void
     */
    public function handle($type, $extra)
    {
        /**
         * 判断更新类型
         *  1. 文档部分更新
         *  2. 重新索引指定文档
         *  3. 单索引/类型重新索引
         *  4. 单功能重新索引(全站搜索 系统日志搜索)
         *  5. 全部重新索引
         */
        if (in_array($type, ConfigOptions::ES_QUEUE_UPDATE_TYPE)) {
            return $this->{$type}($extra);
        }
    }

    /**
     * 更新部分文档
     *
     * @param array $extra
     */
    private function partUpdate($extra)
    {
        $index = $extra['category'] ?? '';
        $id = $extra['id'] ?? '';
        $fields = $extra['fields'] ?? [];

        if ($index && $id && $fields) {
            if ($this->isElasticSearchRun()) {

                $params = [
                    'index' => Constant::ALIAS_PREFIX.$index,
                    'type' => Constant::COMMON_INDEX_TYPE,
                    'id' => $id,
                    'body' => [
                        'doc' => $fields
                    ]
                ];

                $this->client->update($params);
            }
        }
    }

    /**
     * 重新索引指定文档
     *
     * @param array $extra
     */
    private function documentReindex($extra)
    {
        /**
         * 1. 判断索引分类(全站搜索/系统日志搜索)
         * 2. 找到对应builder路径
         * 3. 根据builder中的generaDocument生成对应请求
         * 4. 更新
         */
        $category = $extra['category'] ?? '';
        $id = $extra['id'] ?? '';
        
        if ($category && $id) {
            $alias = Constant::ALIAS_PREFIX.$category;

            // es运行情况下进行处理
            try {
                $baseBuilder = app('App\EofficeApp\Elastic\Services\Search\BaseBuilder');
                $builder = $baseBuilder->getBuilder($category);
                $builder->build($id, $alias, 10 , true);
            } catch (\Exception $exception) {
                Log::error($exception->getMessage());
            }
        }
    }

    /**
     * 按类型重新索引
     *
     * @param array $extra
     */
    private function typeReindex($extra)
    {
        $category = $extra['category'] ?? '';
        $userId = $extra['userId'] ?? 0;

        if ($category) {
            // es运行情况下进行处理
            if ($this->isElasticSearchRun()) {
                // 判断是全站搜索的别名还是系统日志搜索的别名
                if (in_array($category, Constant::$allIndices)) {
                    Artisan::call('es:index:rebuild', [
                        'alias' => Constant::ALIAS_PREFIX.$category,
                        'operator' => $userId
                    ]);
                }
            }
        }
    }

    /**
     * 按ES功能模块索引
     */
    private function functionReindex($extra)
    {
        /**
         * 1. 获取对应功能类型
         * 2. 获取对应全部更新命令
         */
        $functionType = $extra['type'] ?? '';

        if ($functionType === ConfigOptions::CONFIG_TYPE_GLOBAL_SEARCH) {

            $isSchedule = $extra['schedule'] ?? false;
            // es运行情况下进行处理
            if ($this->isElasticSearchRun()) {
                Artisan::call('es:index:rebuild', [
                    'alias' => 'all',
                    '--schedule' => $isSchedule,
                ]);
            }
        }
    }

    /**
     * 全部索引
    */
    private function allReindex($extra)
    {
        // TODO 增加系统日志搜索时添加
    }
}