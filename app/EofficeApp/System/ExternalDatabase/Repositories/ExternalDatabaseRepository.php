<?php

namespace App\EofficeApp\System\ExternalDatabase\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\System\ExternalDatabase\Entities\ExternalDatabaseEntity;

/**
 * 外部数据库Repository类:提供外部数据库 表操作资源
 *
 * @author qishaobo
 *
 * @since  2016-07-11 创建
 */
class ExternalDatabaseRepository extends BaseRepository
{

    public function __construct(ExternalDatabaseEntity $entity)
    {
        parent::__construct($entity);
    }

    /**
     * 获取外部数据库列表
     *
     * @param  array $param 查询条件
     *
     * @return array 查询列表
     *
     * @author qishaobo
     *
     * @since  2016-08-25
     */
    public function getExternalDatabases(array $param = [])
    {
        $default = [
            'fields'            => ['*'],
            'search'            => [],
            'order_by'          => ['database_id' => 'DESC'],
        ];

        $param          = array_merge($default, array_filter($param));

        $query = $this->entity
        ->select($param['fields']);

        $query = $this->getExternalDatabasesParseWhere($query, $param['search']);

        return $query->orders($param['order_by'])
            ->get()
            ->toArray();
    }

    /**
     * 获取外部数据库数量
     *
     * @param  array $param  查询条件
     *
     * @return int 查询数量
     *
     * @author qishaobo
     *
     * @since  2016-08-25
     */
    public function getExternalDatabasesTotal(array $param = [])
    {
        $where = isset($param['search']) ? $param['search'] : [];
        return  $this->getExternalDatabasesParseWhere($this->entity, $where)->count();
    }

    /**
     * 获取外部数据库条件解析
     *
     * @param  array $where  查询条件
     *
     * @return object 查询对象
     *
     * @author qishaobo
     *
     * @since  2016-08-25
     */
    public function getExternalDatabasesParseWhere($query, array $where = [])
    {
        return $query->wheres($where);
    }

}
