<?php

namespace App\EofficeApp\Project\NewRepositories;

use App\EofficeApp\Base\BaseRepository;

/**
 * 项目管理 资源库
 *
 * @author:喻威
 *
 * @since：2015-10-19
 *
 */
class ProjectBaseRepository extends BaseRepository {

    protected static function buildLikeQuery($query, $column, $keyword)
    {
        if ($keyword) {
            $query->where($column, 'like', '%' . $keyword . '%');
        }
    }

    // 根据字段的指定数据顺序排序
    protected static function buildSortByField($query, $fieldName, $sortType = 'asc', array $fieldValues) {
        if (!$fieldValues) {
            return $query;
        }
        $orderSql = "case ";
        $fieldValues = array_values($fieldValues);
        foreach ($fieldValues as $key => $fieldValue) {
            $orderSql .= "when {$fieldName} = $fieldValue then $key ";
        }
        $orderSql .= "else -1 end {$sortType}";
        return $query->orderByRaw($orderSql);
    }
}
