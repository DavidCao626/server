<?php

namespace App\EofficeApp\System\Params\Repositories;


use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\System\Params\Entities\SystemParamsEntity;
use DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Arr;
class SystemParamsRepository  extends BaseRepository
{
    public function __construct(SystemParamsEntity $entity)
    {
        parent::__construct($entity);
    }

    /**
     * 获取文档插件在线预览配置参数
     *
     * @return array
     */
    public function getOnlineReadOption(): array
    {
        $paramKeys = SystemParamsEntity::ONLINE_READ_ALL_PARAMS;
        $configs = $this->getParamsByKeys($paramKeys);

        return $configs;
    }

    /**
     * 获取指定的key对应配置参数(批量获取)
     *
     * @param array|int|string $paramKeys
     *
     * @return array
     */
    public function getParamsByKeys($paramKeys): array
    {
        if (!is_array($paramKeys)) {
            $paramKeys = (array) $paramKeys;
        }

        $paramValues = $this->entity->select('param_key', 'param_value')->whereIn('param_key', $paramKeys)->get()->toArray();

        return Arr::pluck($paramValues, 'param_value', 'param_key');
    }

    /**
     * 获取指定的key对应配置参数(单个)
     *
     * @param string $paramKey
     * @param string $default
     *
     * @return string
     */
    public function getParamByKey($paramKey, $default = ''): string
    {
        if (!is_string($paramKey)) {
            $paramKey = (string) $paramKey;
        }
        $paramValue = $this->entity->where('param_key', $paramKey)->pluck('param_value')->first();
        if (empty($paramValue)) {
            return $default;
        }

        return $paramValue;
    }

    /**
     * 批量更新系统参数, 使用case ... when ... then ..实现, 减少io
     *
     *  $multipleData格式如下
     *  [
     *      ['param_key' => key1, 'param_value' => value1],
     *      ['param_key' => key2, 'param_value' => value2],
     *      ...
     *  ]
     *
     * @param array $multipleData
     *
     * @return bool
     */
    public function updateBatch($multipleData = [])
    {
        try {
            if (empty($multipleData)) {
                return false;
            }
            // TODO 长度限制 一次更新数量为20
            $tableName = $this->entity->table;
            $firstRow = current($multipleData);

            // 获取更新的字段
            $updateColumn = array_keys($firstRow);
            // 默认以 param_key 为条件更新，如果没有 param_key 则以第一个字段为条件(后续考虑)
            $referenceColumn = isset($firstRow['param_key']) ? 'param_key' : current($updateColumn);
            unset($updateColumn[0]);
            // 拼接sql语句
            $updateSql = "UPDATE " . $tableName . " SET ";
            $sets = [];
            // 绑定参数
            $bindings = [];
            // 遍历所有需更新数据的字段名(除param_key)
            foreach ($updateColumn as $uColumn) {
                $setSql = '`' . $uColumn . '` = CASE ';
                // 获取每一次更新的数据
                foreach ($multipleData as $data) {
                    $setSql .= 'WHEN `' . $referenceColumn . '` = ? THEN ? ';
                    $bindings[] = $data[$referenceColumn];
                    $bindings[] = $data[$uColumn];
                }
                $setSql .= 'ELSE `' . $uColumn . '` END ';
                $sets[] = $setSql;
            }
            $updateSql .= implode(', ', $sets);
            $whereIn = collect($multipleData)->pluck($referenceColumn)->values()->all();
            $bindings = array_merge($bindings, $whereIn);
            $whereIn = rtrim(str_repeat('?,', count($whereIn)), ',');
            $updateSql = rtrim($updateSql, ', ') . ' WHERE `' . $referenceColumn . '` IN (' . $whereIn . ')';
            // 传入预处理sql语句和对应绑定数据
            return DB::update($updateSql, $bindings);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return false;
        }
    }
}