<?php

namespace App\EofficeApp\Project\NewServices\Managers;

use App\EofficeApp\Base\BaseCache;

class DatabaseManager extends BaseCache
{

    // 批量插入数据
    public static function insertBatch($class, $data, $timestamp = false)
    {
        $query = $class::buildQuery();
        if ($timestamp) {
            $now = date('Y-m-d H:i:s');
            foreach ($data as &$item) {
                $item['created_at'] = $now;
                $item['updated_at'] = $now;
            }
        }
        $data = array_chunk($data, 1000);
        foreach ($data as $insertData) {
            $query->insert($insertData);
        }
        return true;
    }

    public static function deleteByIds($class, $ids) {
        $query = $class::buildQuery();
        return $query->whereKey($ids)->delete();
    }

    public static function deleteByQuery($query)
    {
        return $query->delete();
    }
}
