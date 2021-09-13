<?php
namespace App\EofficeApp\ElectronicSign\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\ElectronicSign\Entities\QiyuesuoRelatedResourceTaskEntity;

class QiyuesuoRelatedResourceTaskRepository extends BaseRepository
{
    public function __construct(QiyuesuoRelatedResourceTaskEntity $entity)
    {
        parent::__construct($entity);
    }

    /**
     * 查看服务条数
     *
     * @param  array  $param 查询条件
     *
     * @return integer       查询数量
     *
     * @author yml
     *
     * @since  2019-04-17
     */
    public function getCount(array $param)
    {
        $query = $this->entity;
        $query = $this->getParseWhere($query, $param);
        return $query->count();
    }

    /**
     * 任务列表
     *
     * @param  array  $param 查询条件
     *
     * @return integer       查询数量
     *
     * @author yml
     *
     * @since  2019-10-23
     */
    public function getList(array $param)
    {
        $default = [
            'page' => 0,
            'order_by' => ['task_id' => 'desc'],
            'limit' => 10,
            'fields' => ['*'],
        ];

        $param = array_merge($default, array_filter($param));
        $query = $this->entity;
        if (isset($param['fields'])) {
            $query = $query->select($param['fields']);
        }
        $query = $this->getParseWhere($query, $param);
        return $query
            ->parsePage($param['page'], $param['limit'])
            ->get()
            ->toArray();
    }

    /**
     * 查询条件解析 where条件解析
     *
     * @param  array $where  查询条件
     *
     * @return object
     *
     * @author yml
     *
     * @since  2019-04-17
     */
    public function getParseWhere($query, $param)
    {
        if (isset($param['search'])) {

        }
        return $query;
    }

}
