<?php

namespace App\EofficeApp\ElectronicSign\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\ElectronicSign\Entities\QiyuesuoSealApplyAuthLogEntity;

class QiyuesuoSealApplyAuthLogRepository extends BaseRepository
{
    public function __construct(QiyuesuoSealApplyAuthLogEntity $entity)
    {
        parent::__construct($entity);
    }

    /**
     * 新增日志
     */
    public function addLog($param)
    {
        $resAdd = $this->insertData($param);
        return $resAdd;
    }

    public function getCount($param = [])
    {
        $query = $this->entity;
        $query = $this->getParseWhere($query, $param);
        return $query->count();
    }

    public function getList($param = [])
    {
        $default = [
            'page' => 0,
            'order_by' => ['id' => 'desc'],
            'limit' => 10,
            'fields' => ['*'],
        ];
        $param = array_merge($default, array_filter($param));

        $query = $this->entity;
        if (isset($param['fields'])) {
            $query = $query->select($param['fields']);
        }
        $query = $this->getParseWhere($query, $param);
        if (isset($param['order_by'])) {
            $query = $query->orders($param['order_by']);
        }

        return $query
            ->parsePage($param['page'], $param['limit'])
            ->get()
            ->toArray();
    }

    /**
     * 查询条件解析 where条件解析
     *
     * @param array $param 查询条件
     *
     * @return mixed
     *
     * @author [dosy]
     */
    public function getParseWhere($query, $param)
    {
        $search = $param['search'] ?? [];
        if ($search) {
            $query = $query->wheres($search);
        }
        return $query;
    }
}
