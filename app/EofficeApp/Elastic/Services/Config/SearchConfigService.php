<?php


namespace App\EofficeApp\Elastic\Services\Config;


use App\EofficeApp\Elastic\Configurations\Constant;
use App\EofficeApp\Elastic\Services\BaseService;
use Illuminate\Support\Arr;
/**
 * 搜索配置相关
 */
class SearchConfigService extends BaseService
{
    /**
     * 判断索引/文档是否存在
     *
     * @param string $index
     * @param string|int $id
     * @param string $type
     *
     * @return bool
     */
    public function checkExists($index, $id = '', $type = '')
    {
        $params = ['index' => $index];

        // $id不为空则判断文档是否存在
        if ($id) {
            $params['id'] = $id;

            if ($type) {
                $params['type'] = $type;
            }
        }
        $response = $this->exists($params);

        return $response;
    }

    /**
     * 获取指定索引别名
     *
     * @param string $index
     *
     * @return string
     */
    public function getAliasByIndex($index)
    {
        $aliasName = '';
        // 判断索引是否存在
        $indexExists = $this->checkExists($index);
        // 获取该索引的别名: 指定别名为所有, 索引为当前索引(即获取当前索引的全部名别)
        if ($indexExists) {
            $alias = $this->getAlias(['name' => '*', 'index' => $index]);

            $aliasName = isset($alias[$index]) ? $alias[$index] : '';
        }

        return $aliasName;
    }

    /**
     * 获取别名对应的索引名
     *
     * @param string $alias
     *
     * @return string
     */
    public function getIndexByAlias($alias)
    {
        $indexName = '';
        // 判断索引是否存在
        $aliasExists = $this->existsAlias(['name' => $alias]);
        // 获取该索引的别名: 指定别名为所有, 索引为当前索引(即获取当前索引的全部名别)
        if ($aliasExists) {
            $alias = $this->getAlias(['name' => $alias, 'index' => '*']);

            $index = array_keys($alias);

            if (isset($index[0])) {
                $indexName = $index[0];
            }
        }

        return $indexName;
    }

    /**
     * 切换指定索引别名
     *
     * @param string $index
     * @param string $alias
     * @param bool   $isDelete  是否删除其他别名
     *
     * @return bool
     */
    public function switchAliasByIndex($index, $alias, $isDelete = false)
    {
        // 若别名无前缀则补上前缀
        $prefix = Constant::ALIAS_PREFIX;
        if (false === strpos($alias, $prefix)) {
            $alias = $prefix.$alias;
        }

        // 判断别名是否存在
        $aliasExists = $this->existsAlias(['name' => $alias]);
        // 判断索引是否存在
        $indexExists = $this->checkExists($index);

        // 切换索引, 目标索引不存在则切换失败
        $data = [];
        if (!$indexExists) {
            return false;
        }

        if ($aliasExists) {
            // 若别名存在, 则找出该别名下的所有索引
            if ($this->client->indices()->existsAlias(['name' => $alias])) {
                $indicesNames = $this->getSearchIndicesByAlias($alias);

                foreach ($indicesNames as $indexName) {
                    if ($isDelete) {
                        $data['actions'][] = ['remove_index' => ['index' => $indexName]];
                    } else {
                        $data['actions'][] = ['remove' => ['index' => $indexName, 'alias' => $alias]];
                    }
                }
            }
        }

        $data['actions'][] = ['add' => ['index' => $index, 'alias' => $alias]];
        $this->client->indices()->updateAliases([
            'body' =>$data
        ]);

        return true;
    }

    /**
     * 获取指定别名的索引
     *
     * @param string $alias
     *
     * @return array
     */
    public function getSearchIndicesByAlias($alias)
    {
        $indices = $this->client->indices()->getAlias(['name' => $alias]);
        $indicesNames = array_keys($indices);

        return $indicesNames;
    }

    /**
     * 根据分类获取对应索引
     *
     * @param string $category
     *
     * @return array
     */
    public function getAllIndicesByCategory($category = '')
    {
        $params = [];
        if ($category) {
            $params['index'] = Constant::ALIAS_PREFIX.$category.'_v*';
        }
        $indicesDetailInfo = $this->client->cat()->indices($params);

        $indicesNames = Arr::pluck($indicesDetailInfo, 'index');

        return $indicesNames;
    }

    /**
     * 根据分析器获取词元
     *
     * @param string $analyzer
     * @param string $text
     *
     * @return array
     */
    public function getTokensByAnalyzer(string $analyzer, string $text): array
    {
        $body = [
            'analyzer' => $analyzer,
            'text' => $text,
        ];
        $result = $this->client->indices()->analyze(['body' => $body]);

        return $result;
    }

    /**
     * 删除指定索引
     *
     * @param string $category
     * @param string|int $id
     */
    public function deleteIndex(string $category, $id)
    {
        $alias = Constant::ALIAS_PREFIX.$category;

        try {
            $response = $this->client->delete([
                'index' => $alias,
                'type' => Constant::COMMON_INDEX_TYPE,
                'id' => $id,
            ]);
        } catch (\Exception $exception) {
            // TODO 索引不存在 处理 写入日志? 给出提示?
            return json_decode($exception->getMessage());
        }

        return $response;
    }
}