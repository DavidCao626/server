<?php


namespace App\EofficeApp\Elastic\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Elastic\Configurations\ElasticTables;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ElasticBaseRepository extends BaseRepository
{
    /**
     * 获取列表
     *
     * @param array $params
     * @param array $orders
     * @param array $relation
     */
    public function getList(array $params = [], array $orders = [], $relation = []): array
    {
        $model = $this->entity;
        // 解析分页
        if (array_key_exists('page', $params) && $params['page'] > 0 && array_key_exists('limit', $params)) {
            $model = $model->parsePage($params['page'], $params['limit']);
        }

        unset($params['page']);
        unset($params['limit']);

        // 排序
        if (empty($orders)) {
            $model = $model->orders(['id' => 'DESC']);
        } else {
            $model = $model->orders($orders);
        }

        // 查询字段
        if (isset($params['fields'])) {
            $model = $model->select($params['fields']);
            unset($params['fields']);
        }

        // 关联
        if ($relation && isset($relation['table']) && isset($relation['primaryId'])) {
            $with = $relation['table'].':'.$relation['primaryId'];
            if (isset($relation['fields'])) {
                $with .= ','.$relation['fields'];
            }
            $model = $model->with($with);
        }

        // 条件
        foreach ($params as $key => $value) {
            $this->addQueryCondition($model, $key, $value);
        }

        $data = $model->get()->toArray();

        return $data;
    }

    /**
     * 解析条件
     */
    private function addQueryCondition(&$query, $key, $value)
    {
        if (is_null($value)) {
            return $query;
        }

        /**
         * 是否是排除，这里支持如果以英文感叹号『!』开头，则这里代表排除，不等于、不包含.
         */
        $isExclude = strpos($key, '!') === 0;

        if ($isExclude) {
            $key = substr($key, 1);
        }

        if (is_array($value)) {
            if ($isExclude) {
                $query->whereNotIn($key, $value);
            } else {
                $query->whereIn($key, $value);
            }
        } else {
            $query->where($key, $isExclude ? '!=' : '=', $value);
        }

        return $query;
    }

    /**
     *  恢复软删除的单词
     *
     * @param string|int $userId
     * @param int $id
     */
    public function restoreWords($userId, $id):void
    {
        try {
            // 先还原
            $this->entity->where('id', $id)->restore();

            $data = [
                'operator' => $userId,
                'operation' => ElasticTables::OPERATION_RESTORE,
            ];

            $where = ['id' => $id];

            // 后更新操作记录
            $this->updateData($data, $where);

        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
        }
    }

    /**
     * 获取词列表
     *
     * @param array $params
     * @param array $orders
     * @param array $relations
     *
     * @return array
     */
    public function getWordsPageList(array $params, array $orders = [], $relations = []): array
    {
        $data = [
            'total' => 0,
            'list' => [],
        ];

        $data['total'] = $this->getTotal($params);

        if ($data['total']) {
            $data['list'] = self::getList($params, $orders, $relations);
        }

        return $data;
    }
}