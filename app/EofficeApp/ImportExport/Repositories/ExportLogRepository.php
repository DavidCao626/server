<?php

namespace App\EofficeApp\ImportExport\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\ImportExport\Entities\ExportLogEntity;

/**
 * 导出日志
 *
 * @author: 齐少博
 *
 * @since：2017-04-17
 *
 */
class ExportLogRepository extends BaseRepository {

    public function __construct(ExportLogEntity $entity) {
        parent::__construct($entity);
    }

    /**
     * 获取导出日志列表
     *
     * @param  array $param 查询条件
     *
     * @return array
     *
     * @author qishaobo
     *
     * @since  2017-04-17
     */
    public function getExportLogs($param)
    {
        $default = [
            'fields'    => ['*'],
            'search'    => [],
            'order_by'  => ['export_id' => 'desc'],
        ];

        $param = array_merge($default, $param);

        return $this->entity
        ->select($param['fields'])
        ->wheres($param['search'])
        ->orders($param['order_by'])
        ->get()
        ->toArray();
    }

    /**
     * 获取导出日志列表
     *
     * @param  array $param 查询条件
     *
     * @return array
     *
     * @author qishaobo
     *
     * @since  2017-04-17
     */
    public function getExportLog($where)
    {
        return $this->entity->wheres($where)->first();
    }
}
