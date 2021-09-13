<?php
namespace App\EofficeApp\Base;

use Illuminate\Database\Eloquent\Relations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use DateTimeInterface;

class BaseEntity extends Model
{
    /**
     * 禁止操作字段
     * @var array
     */
	protected $guarded = ['id'];
    protected $num = 1;

    /** 当使用 date 或 datetime 属性时，可以指定日期的格式 默认 2021-02-07T02:46:34.000000Z
     * @var array
     */
    // protected $casts = [
    //     'created_at' => 'datetime:Y-m-d H:i:s',
    //     'updated_at' => 'datetime:Y-m-d H:i:s',
    //     'deleted_at' => 'datetime:Y-m-d H:i:s',
    // ];
    /**
     * 查询条件
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $wheres 查询条件
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWheres($query, $wheres)
    {
        $operators = [
            'between'       => 'whereBetween',
            'not_between'   => 'whereNotBetween',
            'in'            => 'whereIn',
            'not_in'        => 'whereNotIn'
        ];

        if (empty($wheres)) {
            return $query;
        }

        foreach ($wheres as $field=>$where) {
            $operator = isset($where[1]) ? $where[1] : '=';
            $operator = strtolower($operator);
            $whereValue = is_array($where) ? $where[0] : $where;
            if (isset($operators[$operator])) {
                $whereOp = $operators[$operator]; //兼容PHP7写法
                $query = $query->$whereOp($field, $whereValue);
            } else {
                $value = $operator != 'like' ? $whereValue : '%'.$whereValue.'%';
                $query = $query->where($field, $operator, $value);
            }
        }

        return $query;
    }
    /**
     * 自定义默认日期格式 你可以通过覆盖 serializeDate 方法自定义默认的序列化格式：
     * @param  DateTimeInterface $date [description]
     * @return [type]                  [description]
     */
    protected function serializeDate(DateTimeInterface $date)
    {
        // return Carbon::instance($date)->toDateTimeString();//这里使用toDateTimeString，不受Carbon中时区的影响
        // laravel 官方写法
        return $date->format('Y-m-d H:i:s');
    }

    /**
     * [scopeMultiWheres 多级查询条件传入测试方法]
     *
     * @method 朱从玺
     *
     * @param  [object]           $query  [builder对象]
     * @param  [array]            $wheres [查询条件]
     *
     * @return [object]                   [组装查询条件后的builder对象]
     */
    public function scopeMultiWheres($query, $wheres)
    {
        if (empty($wheres)) {
            return $query;
        }

        //初始属性,and关系
        $whereString    = 'where';
        $whereHas       = 'whereHas';

        $operators = [
            'between'       => 'whereBetween',
            'not_between'   => 'whereNotBetween',
            'in'            => 'whereIn',
            'not_in'        => 'whereNotIn'
        ];

        $orOperators = [
            'between'       => 'orWhereBetween',
            'not_between'   => 'orWhereNotBetween',
            'in'            => 'orWhereIn',
            'not_in'        => 'orWhereNotIn'
        ];

        //or关系
        if(isset($wheres['__relation__']) && $wheres['__relation__'] == 'or') {
            $operators      = $orOperators;
            $whereString    = 'orWhere';
            $whereHas       = 'orWhereHas';
        }

        //删除__relation__
        if(isset($wheres['__relation__'])) {
            unset($wheres['__relation__']);
        }

        //判断是不是整体都是关联查询
        $searchFields = array_keys($wheres);
        if(isset($this->allFields) && isset($this->allFields[$searchFields[0]])) {
            $firstRelation = $this->allFields[$searchFields[0]][0];
        }else {
            $firstRelation = '';
        }

        if($firstRelation && empty(array_diff($searchFields, $this->relationFields[$firstRelation]))) {
            $relationStatus = true;
        }else {
            $relationStatus = false;
        }

        //整体关联查询,即这一层的所有查询都在同一个关联关系下
        if($relationStatus) {
            $query = $query->$whereHas($firstRelation, function($query) use ($wheres, $operators, $whereString)
            {
                foreach ($wheres as $field => $where) {
                    $field = $this->allFields[$field][1];
                    $operator = isset($where[1]) ? $where[1] : '=';
                    $operator = strtolower($operator);

                    if (isset($operators[$operator])) {
                        $whereOp = $operators[$operator];
                        $query = $query->$whereOp($field, $where[0]);
                    } else {
                        $value = $operator != 'like' ? $where[0] : '%'.$where[0].'%';
                        $query = $query->$whereString($field, $operator, $value);
                    }
                }
            });
        //不是整体关联查询,则这一层查询条件为并列关系,同一个关联关系下的参数也是
        }else {
            foreach ($wheres as $field => $where) {
                $operator = isset($where[1]) ? $where[1] : '=';
                $operator = strtolower($operator);

                if(isset($this->allFields) && isset($this->allFields[$field])) {
                    $query = $query->$whereHas($this->allFields[$field][0], function($query) use ($where, $operators, $operator, $field, $whereString)
                    {
                        $field = $this->allFields[$field][1];

                        if (isset($operators[$operator])) {
                            $whereOp = $operators[$operator];
                            $query = $query->$whereOp($field, $where[0]);
                        } else {
                            $value = $operator != 'like' ? $where[0] : '%'.$where[0].'%';
                            $query = $query->$whereString($field, $operator, $value);
                        }
                    });
                //键值包含multiSearch为下一层,递归调用本函数
                }elseif(strpos($field, 'multiSearch') !== false) {
                    $query = $query->$whereString(function($query) use ($where)
                    {
                        $this->scopeMultiWheres($query, $where);
                    });
                }else {
                    if (isset($operators[$operator])) {
                        $whereOp = $operators[$operator];
                        $query = $query->$whereOp($field, $where[0]);
                    } else {
                        $value = $operator != 'like' ? $where[0] : '%'.$where[0].'%';
                        $query = $query->$whereString($field, $operator, $value);
                    }
                }
            }
        }

        return $query;
    }

    /**
     * [scopeOrWheres or关系的查询]
     *
     * @method scopeOrWheres
     *
     * @param  [object]        $query  [Builder对象]
     * @param  [array]         $wheres [查询条件]
     *
     * @return [object]                [Builder对象]
     */
    public function scopeOrWheres($query, $wheres)
    {
        $operators = [
            'between'       => 'orWhereBetween',
            'not_between'   => 'orWhereNotBetween',
            'in'            => 'orWhereIn',
            'not_in'        => 'orWhereNotIn'
        ];

        if (empty($wheres)) {
            return $query;
        }
        $query = $query->where(function($query) use ($wheres){
            foreach ($wheres as $field=>$where) {
                $operator = isset($where[1]) ? $where[1] : '=';
                $operator = strtolower($operator);
                if (isset($operators[$operator])) {
                    $query = $query->$operators[$operator]($field, $where[0]);
                } else {
                    $value = $operator != 'like' ? $where[0] : '%'.$where[0].'%';

                    $query = $query->orWhere($field, $operator, $value);
                }
            }
        });

        return $query;
    }

    /**
     * 查询排序
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $orders 排序
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrders($query, $orders)
    {
        if (!empty($orders)) {
            foreach ($orders as $field=>$order) {
                $query = $query->orderBy($field, $order);
            }
        }
        return $query;
    }

    /**
     * 查询分页
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $start 开始页数/开始条数
     * @param array $limit 每页条数
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeParsePage($query, $start, $limit, $isPage = true)
    {
        $start = (int) $start;

        if ($isPage && $start == 0) {
            return $query;
        }

        if ($isPage) {
            $start = ($start - 1) * $limit;
        }

        $query->offset($start)->limit($limit);

        return $query;
    }

    /**
     * 查询条件
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $fields 查询字段
     *
     * @return \Illuminate\Database\Eloquent\Builder
     *
     * @author qishaobo
     *
     * @since  2016-03-28 创建
     */
    public function scopeParseSelect($query, $fields = [], $entity = '')
    {
        if (empty($fields) || $fields == ['*']) {
            return $query->select(['*']);
        }

        $fields = array_filter($fields);

        if (empty($entity) || empty($entity->relationFields)) {
            return $query->select($fields);
        }

        $prefixTable = $entity->getTable().'.';
        $relationFields = $entity->relationFields;
        $primaryFields = $subFields = [];

        foreach ($fields as $field) {
            $hasSub = 0;

            foreach ($relationFields['fields'] as $relation => $relationField) {
                if (isset($relationField[$field])) {
                    $subFields[$relation][] = $relationField[$field];
                    $hasSub = 1;
                    break;
                } else if (in_array($field, $relationField) && is_numeric(array_search($field, $relationField))) {
                    $subFields[$relation][] = $field;
                    $hasSub = 1;
                    break;
                }
            }

            if (!$hasSub) {
                $primaryFields[] = $prefixTable.$field;
            }
        }

        if (!empty($subFields)) {
            foreach ($subFields as $k => $subField) {
                if (isset($relationFields['relation'][$k])) {
                    $subField[] = $relationFields['relation'][$k][0];
                    $primaryFields[] = $prefixTable.$relationFields['relation'][$k][1];
                }

                $query->with([$k => function ($query) use ($subField) {

                    if (!empty($query->getModel()->dates)) {
                        $query->withTrashed();
                    }

                    $query->select(array_unique($subField));
                }]);
            }
        }

        if (!empty($primaryFields)) {
            $query->select(array_unique($primaryFields));
        }

        return $query;
    }

    /**
     * 排序条件
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param object $entity 表实体
     * @param array $sort 排序字段
     *
     * @return \Illuminate\Database\Eloquent\Builder
     *
     * @author qishaobo
     *
     * @since  2016-10-10 创建
     */
    public function scopeParseOrderBy($query, $sort, $entity = '')
    {
        if (empty($sort)) {
            return $query;
        }

        if (empty($entity) || empty($entity->relationFields)) {
            return $this->orders($sort);
        }

        $relationFields = $entity->relationFields['fields'];

        foreach ($sort as $field => $order) {
            $subSort = [];

            foreach ($relationFields as $relation => $relationField) {
                if (isset($relationField[$field])) {
                    $subRelation = $relation;
                    $subSort = [$relationField[$field] => $order];
                    break;
                } else if (in_array($field, $relationField) && is_numeric(array_search($field, $relationField))) {
                    $subRelation = $relation;
                    $subSort = [$field => $order];
                    break;
                }
            }

            if (empty($subSort)) {
                $query = $query->orders([$field => $order]);
            } else {
                $query = $this->joinTable($query, $entity, $subRelation, $subSort);
            }
        }

        return $query;
    }

    /**
     * 连表
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param object $entity 表实体
     * @param array $relation 关联关系
     * @param array $sort 排序字段
     *
     * @return \Illuminate\Database\Eloquent\Builder
     *
     * @author qishaobo
     *
     * @since  2016-10-10 创建
     */
    function joinTable($query, $entity, $relation, $sort) {
        $relations = $entity->relationFields['relation'];
        $localTable = $entity->getTable();
        list($sortKey, $sortValue) = each($sort);

        if (strpos($relation, '.') === false) {
            $foreignTable = app($entity->$relation(1))->getTable();
            return $query->leftJoin($foreignTable, $foreignTable.'.'.$relations[$relation][0], $localTable.'.'.$relations[$relation][1])
                    ->orders([$foreignTable.'.'.$sortKey => $sortValue]);
        }

        $models = explode('.', $relation);
        $newEntity = $entity;

        $i = 0;
        foreach ($models as $model) {
            $newEntity = app($newEntity->$model(1));
            $foreignTable = $newEntity->getTable();

            $query = $query->leftJoin($foreignTable, $foreignTable.'.'.$relations[$model][0], $localTable.'.'.$relations[$model][1]);

            $localTable = $foreignTable;

            if (isset($newEntity->relationFields) && !empty($newEntity->relationFields['relation'])) {
                $relations = $newEntity->relationFields['relation'];
            }

            $i++;
        }

        return $query->orders([$foreignTable.'.'.$sortKey => $sortValue]);

    }

    /**
     * [scopeParseWheres 查询条件解析]
     *
     * @method scopeParseWheres
     *
     * @param  [object]        $query  [Builder对象]
     * @param  [array]         $wheres [查询条件]
     *
     * @return [object]                [Builder对象]
     *
     * @author qishaobo
     *
     * @since  2016-10-10
     */
    public function scopeParseWhere($query, $wheres = [], $relationFields = [])
    {
        if (empty($wheres)) {
            return $query;
        }

        if (empty($relationFields)) {
            return $query->wheres($wheres);
        }

        $primaryFields = $subFields = [];
        foreach ($wheres as $field => $where) {
            $hasSub = 0;
            foreach ($relationFields['fields'] as $relation => $relationField) {
                if (isset($relationField[$field])) {
                    $subFields[$relation][$relationField[$field]] = $where;
                    $hasSub = 1;
                    break;
                } else if (in_array($field, $relationField) && is_numeric(array_search($field, $relationField))) {
                    $subFields[$relation][$field] = $where;
                    $hasSub = 1;
                    break;
                }
            }

            if (!$hasSub) {
                $primaryFields[$field] = $where;
            }
        }

        if (!empty($subFields)) {
            foreach ($subFields as $relation => $where) {
                $query = $query->whereHas($relation, function ($query) use ($where) {
                    $query->wheres($where);
                });
            }
        }

        if (!empty($primaryFields)) {
            $query->wheres($primaryFields);
        }

        return $query;
    }

    /**
     * 一对一模型
     *
     * @param  array $model  表模型
     * @param  array $foreignKey  关联外键
     * @param  array $localKey  关联内键
     * @param  int $operate  返回类型：0|管理关系对象，1|表模型
     *
     * @return object
     *
     * @author qishaobo
     *
     * @since  2016-10-10
     */
    public function hasOneModel($model, $foreignKey, $localKey, $operate = 0)
    {
        if ($operate) {
            return $model;
        }

        return  $this->HasOne($model, $foreignKey, $localKey);
    }

    /**
     * 适用于时间信息年月日存储在不同的字段，根据时间区间查询记录
     *
     * @param $query
     * @param $startDate
     * @param $endDate
     *
     * @return object
     *
     * @author ShiQi
     *
     * @since  2018-11-23
     */
    public function scopeSeparateDate($query, $startDate, $endDate)
    {
        $startDate = date('Ymd', strtotime($startDate));
        $endDate = date('Ymd', strtotime($endDate));
        $whereRaw = "concat(year,LPAD(month, 2, 0),LPAD(day, 2, 0)) between $startDate and $endDate";
        return $query->whereRaw($whereRaw);
    }

    /**
     * whereHasIn 的 where in 实现，两表联查数据量差别特别大的情况下
     * @method scopeWhereHasIn
     *
     * @param  [object]        $query  [Builder对象]
     * @param  [string]        $relationName  [关联关系]
     * @param  [array]         $wheres [查询条件]
     *
     * @return [object]                [Builder对象]
     *
     * @author 缪晨晨
     *
     * @since  2018-11-29
     *
     */
    public function scopeWhereHasIn($query, $relationName, $wheres = [])
    {
        $relation = Relations\Relation::noConstraints(function () use ($relationName) {
            return $this->getModel()->{$relationName}();
        });
        $in = $relation->getQuery();
        if ($relation instanceof Relations\BelongsTo) {
            return $query->whereIn($relation->getForeignKey(), $in->select($relation->getOwnerKey())->wheres($wheres));
        } elseif ($relation instanceof Relations\HasMany) {
            return $query->whereIn($this->getKeyName(), $in->select($relation->getForeignKeyName())->wheres($wheres));
        } elseif ($relation instanceof Relations\HasOne) {
            return $query->whereIn($this->getKeyName(), $in->select($relation->getForeignKeyName())->wheres($wheres));
        }
    }

    /**
     * orWhereHasIn 的 or where in 实现，两表联查数据量差别特别大的情况下
     * @method scopeOrWhereHasIn
     *
     * @param  [object]        $query  [Builder对象]
     * @param  [string]        $relationName  [关联关系]
     * @param  [array]         $wheres [查询条件]
     *
     * @return [object]                [Builder对象]
     *
     * @author 缪晨晨
     *
     * @since  2018-11-29
     */
    public function scopeOrWhereHasIn($query, $relationName, $wheres = [])
    {
        $relation = Relations\Relation::noConstraints(function () use ($relationName) {
            return $this->getModel()->{$relationName}();
        });
        $in = $relation->getQuery();
        if ($relation instanceof Relations\BelongsTo) {
            return $query->orWhereIn($relation->getForeignKey(), $in->select($relation->getOwnerKey())->wheres($wheres));
        } elseif ($relation instanceof Relations\HasMany) {
            return $query->orWhereIn($this->getKeyName(), $in->select($relation->getForeignKeyName())->wheres($wheres));
        } elseif ($relation instanceof Relations\HasOne) {
            return $query->orWhereIn($this->getKeyName(), $in->select($relation->getForeignKeyName())->wheres($wheres));
        }
    }
    //zcx
    // public function scopeParseSelect($query, $paramFields = [])
    // {
    //     if (empty($paramFields) || $paramFields[0] == '*') {
    //         return $query->select(['*']);
    //     }

    //     $fields = array_filter($paramFields);

    //     $primaryFields = $subFields = [];
    //     foreach ($fields as $field) {
    //         if(isset($this->allFields) && isset($this->allFields[$field])) {
    //             if(isset($subFields[$this->allFields[$field][0]])) {
    //                 $subFields[$this->allFields[$field][0]][] = $this->allFields[$field][1];
    //             }else {
    //                 $subFields[$this->allFields[$field][0]] = [$this->allFields[$field][1]];
    //             }
    //         }else {
    //             $primaryFields[] = $field;
    //         }
    //     }

    //     if (!empty($subFields)) {
    //         foreach ($subFields as $k => $subField) {
    //             if (isset($this->relation[$k])) {
    //                 if (!in_array($this->relation[$k][0], $subField)) {
    //                     $subField[] =  $this->relation[$k][0];
    //                 }

    //                 if (!in_array($this->relation[$k][1], $primaryFields)) {
    //                     $primaryFields[] = $this->relation[$k][1];
    //                 }
    //             }

    //             $query->with([$k => function ($query) use ($subField) {
    //                 $query->select($subField);
    //             }]);
    //         }
    //     }

    //     if (!empty($primaryFields)) {
    //         $query->select($primaryFields);
    //     }

    //     return $query;
    // }
}
