<?php


namespace App\EofficeApp\Elastic\Foundation;


class Query extends Params
{
    /**
     * 查询偏移量
     */
    public function setFrom($from)
    {
        return $this->setParam('from', $from);
    }

    /**
     * 是否解释相关评分
     */
    public function setExplain($explain = true)
    {
        if ($explain) {
            return $this->setParam('explain', $explain);
        }

        return false;
    }

    /**
     * pageSize
     */
    public function setSize($size = 10)
    {
        return $this->setParam('size', $size);
    }

    /**
     * 排序方式
     */
    public function setSort(array $sortArgs)
    {
        return $this->setParam('sort', $sortArgs);
    }

    /**
     * 查询体
     */
    public function setQuery($query)
    {
        return $this->setParam('query', $query);
    }

    /**
     * 高亮设置
     *  TODO 高亮片段数量和长度设置
     */
    public function setHighLight()
    {
        return $this->setParam('highlight', [
            'fields' => [
                '*' => [
                    'pre_tags' => ["<span  class='match_content'>"],
                    'post_tags' => ["</span>"]
                ]
            ]
        ]);
    }
}