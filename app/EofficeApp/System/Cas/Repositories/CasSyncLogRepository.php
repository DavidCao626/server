<?php

namespace App\EofficeApp\System\Cas\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\System\Cas\Entities\CasSyncLogEntity;

/**
 * CasSyncLog Repository类:提供cas_sync_log 表操作资源
 *
 * @author 缪晨晨
 *
 * @since  2018-01-29 创建
 */
class CasSyncLogRepository extends BaseRepository
{

    public function __construct(CasSyncLogEntity $entity)
    {
        parent::__construct($entity);
    }

    /**
     * 【组织架构同步】 获取同步日志数量
     *
     * @param
     *
     * @return string
     *
     * @author 缪晨晨
     *
     * @since  2018-01-29 创建
     */
    public function getCasSyncLogListTotal(array $params)
    {
        $params["page"]       = 0;
        $params["returntype"] = "count";
        return $this->getCasSyncLogList($params);
    }

    /**
     * 【组织架构同步】 获取同步日志列表
     *
     * @param
     *
     * @return array
     *
     * @author 缪晨晨
     *
     * @since  2018-01-29 创建
     */
    public function getCasSyncLogList(array $params)
    {
        $default = [
            'fields'     => ["*"],
            'page'       => 0,
            'limit'      => config('eoffice.pagesize'),
            'search'     => [],
            'order_by'   => ['log_id' => 'DESC'],
            'returntype' => 'array',
        ];

        $params = array_merge($default, $params);

        $query = $this->entity->select($params['fields'])
                              ->wheres($params['search'])
                              ->parsePage($params['page'], $params['limit'])
                              ->orders($params['order_by']);
        // 返回值类型判断
        if ($params["returntype"] == "array") {
            return $query->get()->toArray();
        } else if ($params["returntype"] == "count") {
            return $query->get()->count();
        } else if ($params["returntype"] == "object") {
            return $query->get();
        }
    }
}
