<?php

namespace App\EofficeApp\Salary\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Salary\Entities\SalaryFieldsEntity;

/**
 * 薪资Repository类:提供薪资相关的数据库操作方法。
 *
 * @author qishaobo
 *
 * @since  2015-11-2 创建
 */
class SalaryFieldRepository extends BaseRepository
{
    public function __construct(SalaryFieldsEntity $entity)
    {
        parent::__construct($entity);
    }

    /**
     * 获取薪资项目列表
     *
     * @param  array  $param  查询参数
     *
     * @return array 薪资项目列表
     *
     * @author qishaobo
     *
     * @since  2015-11-2
     */
    public function getSalaryItems(array $param = [], $isAll = false)
    {
        $default = [
            'search'   => [],
            'fields'   => ['field_id', 'field_code', 'field_name', 'field_default', 'field_format', 'field_decimal', 'field_sort', 'field_show', 'field_type', 'field_default_set', 'has_children', 'enabled'],
            'page'     => 0,
            'limit'    => config('eoffice.pagesize'),
            'order_by' => ['field_sort' => 'asc', 'created_at' => 'asc'],
        ];

        if ($isAll) {
            $default['fields'] = ['*'];
        }
        if (isset($param['search']) && !is_array($param['search'])) {
            $param['search'] = json_decode($param['search'], true);
        }

        $param = array_merge($default, array_filter($param));
        if (isset($param['order_by']) || isset($param['orders'])) {
            $param['order_by']['created_at'] = 'asc';
        }

        $query = $this->entity
            ->select($param['fields'])
            ->wheres($param['search'])
            ->orders($param['order_by']);

        if (isset($param['delete']) && $param['delete'] == 1) {
            $query->onlyTrashed();
        }
        if (isset($param['with_trashed']) && $param['with_trashed'] == 1){
            $query->withTrashed();
        }

        if (!isset($param['getAll'])) {
            $query = $query->parsePage($param['page'], $param['limit']);
        }

        return $query->get()->toArray();
    }

    /**
     * 根据field_id获取薪资项目
     *
     * @param  array  $param  查询参数
     *
     * @return array 薪资项目列表
     *
     * @author niuxiaoke
     *
     * @since  2017-07-21
     */
    public function getSalaryItemsByFieldId($field_id)
    {
        $data = $this->entity->where('field_id', $field_id)->withTrashed()->first();

        return empty($data) ? [] : $data->toArray();
    }

    public function getFieldCode($file_name = '')
    {
        if ($file_name == '') {
            return $this->entity->select(['field_code', 'field_default_set', 'field_source', 'field_decimal'])->get()->toArray();
        } else {
            return $this->entity->select('field_code')->where('field_name', $file_name)->get()->toArray();
        }
    }
    public function getAllField()
    {
        return $this->entity->get()->toArray();
    }
    public function getLastRecord()
    {
        return $this->entity->where('field_code', '!=', 'total')->orderBy('field_id', 'desc')->withTrashed()->first();
    }

    public function getMaxSort()
    {
        return $this->entity->orderBy('field_sort', 'desc')->withTrashed()->first();
    }

    public function forceDeleteSalary($param)
    {
        return $this->entity->wheres($param)->forceDelete();
    }

    public function getDeletedTotal($param)
    {
        $search = isset($param['search']) ? $param['search'] : [];

        return $this->entity->onlyTrashed()->wheres($search)->count();
    }

    public function getChildrensCount($parentId)
    {
        return $this->entity->where('field_parent', $parentId)->count();
    }

    public function getFieldsHasDependenceId($dependenceId)
    {
        return $this->entity->whereRaw("FIND_IN_SET(?, dependence_ids)", [$dependenceId])->get();
    }
}