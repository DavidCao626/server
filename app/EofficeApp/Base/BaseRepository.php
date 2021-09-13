<?php

namespace App\EofficeApp\Base;

use Schema;
use Illuminate\Database\Eloquent\Model as Entity;
use \Illuminate\Http\Request;
use Cache;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class BaseRepository
{
    /**
     * 数据表实体对象
     *
     * @var object
     */
    public $entity;
    protected $loginUserId;
    protected $loginDeptId;
    protected $loginRoleId;
    protected $currentUser;

    /**
     * Create a new __construct
     *
     * @param Entity $entity
     *
     * @return void
     */
    public function __construct(Entity $entity)
    {
        $this->entity = $entity;
    }

    /**
     * 插入数据
     * @param array $data 插入数据
     * @return object 插入数据对象
     */
    public function insertData(array $data)
    {
        try {
            return $this->entity->create($data);
        } catch (\Exception $e) {
            return sql_error($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 删除数据(主键)
     * @param int|array $ids 更新条件
     * @return bool
     */
    public function deleteById($ids)
    {
        return (bool)$this->entity->destroy($ids);
    }

    /**
     * 删除数据(条件)
     * @param array $where 删除条件
     * @return bool
     */
    public function deleteByWhere(array $where)
    {
        return (bool)$this->entity->wheres($where)->delete();
    }

    /**
     * 真删除数据
     * @param array $where 删除条件
     * @return bool
     */
    public function reallyDeleteByWhere(array $where)
    {
        return (bool)$this->entity->wheres($where)->forceDelete();
    }

    /**
     * 更新数据
     *
     * @param array $data 更新数据
     * @param array $where 更新条件
     * @param array $filter 过滤字段
     *
     * @return bool
     *
     * @author qishaobo
     *
     * @since  2015-11-05
     */
    public function updateData(array $data, array $where, array $filter = [])
    {

        $data = $this->filterUpdateData($data, $filter);
        try {
            if (count($where) == count($where, COUNT_RECURSIVE)) {
                return (bool)$this->entity->where($where)->update($data);
            } else {
                return (bool)$this->entity->wheres($where)->update($data);
            }
        } catch (\Exception $e) {
            return sql_error($e->getCode(), $e->getMessage());
        }
    }

    /**
     * 过滤更新数据
     *
     * @param array $data 更新数据
     * @param array $filter 过滤字段
     *
     * @return bool
     *
     * @author qishaobo
     *
     * @since  2015-11-25
     */
    public function filterUpdateData(array $data, array $filter = [])
    {
        $defaultFilter = ['created_at', 'updated_at', '_method'];
        $primaryKey    = (array)$this->entity->primaryKey;
        $filter        = array_merge($filter, $defaultFilter, $primaryKey);
        $data          = Arr::except($data, $filter);
        return array_intersect_key($data, array_flip($this->getTableColumns()));
    }

    /**
     * [updateDataBatch 批量更新某条数据,有别于updateData]
     *
     * @param array $data [更新数据]
     * @param array $where [查询条件]
     *
     * @return bool [bool] [更新结果]
     * @since  2015-10-26 创建
     *
     * @author 朱从玺
     */
    public function updateDataBatch(array $data, array $where)
    {
        if (count($where) == count($where, COUNT_RECURSIVE)) {
            $result = $this->entity->where($where)->first();
        } else {
            $result = $this->entity->wheres($where)->first();
        }

        if (!$result) {
            return false;
        }

        return $result->fill($data)->save();
    }

    /**
     * 基本插入
     * @param array $data [需要插入的数据]
     * @return Entity|object [object]  [插入的那条数据的对象]
     * @author 朱从玺
     */
    public function insertDataBatch(array $data)
    {
        $query = $this->entity;
        foreach ($data as $key => $value) {
            $query->$key = $value;
        }
        $query->save();
        return $query;
    }

    /**
     * 查询数量
     * @param array $param
     * @return integer
     */
    public function getTotal(array $param)
    {
        $search = isset($param['search']) ? $param['search'] : [];

        return $this->entity->wheres($search)->count();
    }

    /**
     * 查询考勤数量
     * @param array $param
     * @return integer
     *
     */
    public function getDutyTotal(array $param)
    {
        $search = isset($param['search']) ? $param['search'] : [];
        if (isset($param['click']) && $param['click'] == 'name') {
            return $this->entity->wheres($search)->count();
        } else {
            if (isset($param['dataType']) && $param['dataType'] == 'all') {
                return $this->entity->wheres($search)->count();
            } else {
                return $this->entity->wheres($search)->where('data_type', 1)->count();
            }
        }
    }

    /**
     * 查询详情
     * @param int $id 日志id
     * @param bool $withTrashed 查询软删除数据
     * @return Entity | null
     */
    public function getDetail($id, $withTrashed = false , array $fields = ['*'])
    {
        $query = $this->entity->select($fields);
        if ($withTrashed) {
            $query = $query->withTrashed();
        }
        return $query->find($id);
    }

    /**
     * 获取当前表对象的表结构
     * @param string $tableName
     * @return mixed
     */
    public function getTableColumns($tableName = '')
    {
        $tableName = empty($tableName) ? $this->entity->getTable() : $tableName;
        return Schema::getColumnListing($tableName);
    }

    /**
     * 删除软删除数据
     * @param array $where 删除条件
     * @param bool $onlyTrashed 只删除软删除数据
     * @return bool
     */
    public function deleteSoftDelete(array $where, $onlyTrashed = true)
    {
        $query = $this->entity;
        if ($onlyTrashed) {
            $query = $query->onlyTrashed();
        } else {
            $query = $query->withTrashed();
        }
        return (bool)$query->wheres($where)->forceDelete();
    }

    /**
     * 恢复软删除数据
     * @param array $where 删除条件
     * @return bool
     */
    public function restoreSoftDelete(array $where)
    {
        return (bool)$this->entity->wheres($where)->restore();
    }

    /**
     * 插入多条数据
     * @param array $data 插入数据
     * @return bool
     */
    public function insertMultipleData(array $data)
    {
        return $this->entity->insert($data);
    }

    public function parseResult($data, $fields = [], $relationFields = [])
    {
        if (empty($fields) || $fields[0] == '*' || empty($data)) {
            return $data;
        }

        $subFields = [];

        if (!empty($relationFields) && !empty($relationFields['fields'])) {
            foreach ($fields as $field) {
                foreach ($relationFields['fields'] as $relation => $relationField) {
                    $relation = Str::snake($relation);
                    if (isset($relationField[$field])) {
                        $subFields[$relation . '.' . $relationField[$field]] = $field;
                        break;
                    } else if (in_array($field, $relationField)) {
                        $subFields[$relation . '.' . $field] = $field;
                        break;
                    }
                }
            }
        }

        if (!empty($subFields)) {
            foreach ($data as $k => $v) {
                $row = Arr::dot($v);
                foreach ($subFields as $key => $val) {
                    $data[$k][$val] = isset($row[$key]) ? $row[$key] : '';
                }
            }
        }

        return $data;
    }

    /**
     * 获取某些字段的value一维数组
     * @param array $fields
     * @param array $where
     * @return array
     */
    public function getColumns(array $fields, array $where)
    {
        $result = [];
        $list   = $this->entity->select($fields)->where($where)->first();
        if (!empty($list)) {
            $list   = $list->toArray();
            $result = array_values($list);
        }
        return $result;
    }

    /**
     * 获取某一个字段
     * @param string $field
     * @param $where
     * @return mixed
     */
    public function getFieldValue(string $field, $where)
    {
        return $this->entity->where($where)->value($field);
    }

    /**
     * 获取记录
     * @param $where
     * @param $field
     * @param $wheres
     * @return mixed
     */
    public function getFieldInfo($where, $field = null, $wheres = false)
    {
        if (!$field && !$wheres) {
            return $this->entity->where($where)->get()->toArray();
        } else {
            $query = $this->entity;
            if ($field) {
                $query = $query->select($field);
            }
            if ($wheres) {
                $query = $query->wheres($wheres);
            } else {
                $query = $query->where($where);
            }
            return $query->get()->toArray();
        }
    }

    /**
     * 获取符合条件的一条记录记录
     * @param $where
     * @param array $fields
     * @return mixed
     */
    public function getOneFieldInfo($where, array $fields = [])
    {
        if (empty($fields)) {
            return $this->entity->where($where)->first();
        }
        return $this->entity->select($fields)->where($where)->first();
    }


    /**
     * 获取某个字段最大值
     * @param string $field
     * @param array $wheres
     * @return mixed
     */
    public function getFieldMaxValue(string $field, array $wheres = [])
    {
        return $this->entity->wheres($wheres)->max($field);
    }
}
