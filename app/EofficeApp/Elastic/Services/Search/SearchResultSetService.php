<?php


namespace App\EofficeApp\Elastic\Services\Search;


class SearchResultSetService
{
    /**
     * 处理响应参数
     *
     * @param array $response
     *
     * @return array
     */
    public function buildResult($response)
    {
        $result = [];

        if (!isset($response['hits']['hits'])) {
            return ['list' => $result, 'total' => 0];
        }

        foreach ($response['hits']['hits'] as $hit) {
            $_source = $hit['_source'];
            $_source['score'] = $hit['_score'];

            $highlight = $hit['highlight'];
            unset($highlight['category']);

            // 高亮片段替换
            $_source = $this->highlightHandle($_source, $highlight);

            $result[] = $_source;
        }

        return ['list' => $result, 'total' => $response['hits']['total']];
    }

    /**
     * 高亮显示处理
     *
     * @param array $source
     * @param array $highlight
     *
     * @return array
     */
    public function highlightHandle($source, $highlights)
    {
        // 高亮片段数组转为字符串
        $fields = array_map(function ($highlight) {
            return count($highlight) > 1 ? implode(' ... ', $highlight) : $highlight[0];
        }, $highlights);

        // 高亮片段替换
        return array_merge($source, $fields);
    }
}