<?php

namespace App\EofficeApp\Base\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
trait SearchableTrait
{
    protected $module = null;
    protected $eqQuery = []; //0
    protected $inQuery = []; //1:in
    protected $withQuery = []; //2:with_relationName
    protected $obQuery = []; //3:ob_created_at意order_by created_at
    protected $likeQuery = []; //4:
    protected $joinQuery = []; //5:join_user => '',''
    protected $withCountQuery = []; //5:join_user => '',''
//    private $orQuery = [];
    private $operators = [
        '=', '<', '>', '<=', '>=', '<>', '!=', '<=>',
        'like', 'like binary', 'not like', 'ilike',
        '&', '|', '^', '<<', '>>',
        'rlike', 'regexp', 'not regexp',
        '~', '~*', '!~', '!~*', 'similar to',
        'not similar to', 'not ilike', '~~*', '!~~*',
        'between', 'in' // 非where里面有的，兼容
    ];

    /**
     * @param array $conditions
     * @param Model $query
     * @return Builder $query
     */
    public static function buildQuery($conditions = [], $query = null)
    {
        $self = new static();

        $query = $query ? $query : $self->query();

        $query = $self->insertBuilder($conditions, $query);

        return $query;
    }

    public function insertBuilder($conditions, $query)
    {
        if (!is_array($conditions)) {
            return $query;
        }
        foreach ($conditions as $key => $val) {
            if (emptyWithoutZero($val) && !is_array($val)) {
                continue;
            }
            if (strpos($key, 'in_') === 0) {
                $this->insertWhereIn($query, $key, $val);
            }
            if (strpos($key, 'with_') === 0) {
                if (strpos($key, 'with_count_') === 0) {
                    $this->insertWithCount($query, $key, $val);
                }
                $this->insertWith($query, $key, $val);
            }
            if (strpos($key, 'ob_') === 0) {
                $this->insertOrderBy($query, $key, $val);
            }
            if (strpos($key, 'like_') === 0) {
                $this->insertLike($query, $key, $val);
            }
            if (strpos($key, 'or_') === 0) {
                $this->insertOrWhere($query, $key, $val);
            }
            if (strpos($key, 'join_') === 0) {
                $this->insertJoin($query, $key, $val);
            }
            // 兼容排序
            if (strpos($key, 'order_by') === 0) {
                if (is_array($val)) {
                    foreach ($val as $column => $sortType) {
                        $query->orderBy($column, $sortType);
                    }
                }
            }
            if ($key === 'select') {
                $query->select($val);
            }
            // 兼容or查询
            if ($key === 'multiSearch') {
                $query->multiWheres([$key => $val]);
            }
            if (in_array($key, $this->eqQuery)) {
                $this->insertWhere($query, $key, $val);
            }
        }

        //全局增加limit参数限制查询数量，当使用paginate时不生效
        if (!empty($conditions['limit'])) {
//            $query->limit($conditions['limit']);
        }

        return $query;
    }

    private function getTableName1()
    {
        return $this->getTable();
    }

    private function getFullColumn($name)
    {
        if (strstr($name, '.') === false) {
            return $this->getTableName1() . '.' . $name;
        }
        return $name;
    }


    public function insertWhere($query, $key, $val, $isOr = false)
    {
        if (!in_array($key, $this->eqQuery)) {
            return $query;
        }

        // 判断是where还是whereIn
        $key = $this->getFullColumn($key);
        if (is_array($val) && count($val) === 2 && $this->isOperator(Arr::get($val, 1))) {
            $operate = $val[1];
            $val = $val[0];
            switch ($operate) {
                case 'between':
                    $functionName = $this->changeOr('whereBetween', $isOr);
                    $query->$functionName($key, $val);
                    break;
                case 'in':
                    $functionName = $this->changeOr('whereIn', $isOr);
                    $query->$functionName($key, $val);
                    break;
                case 'like':
                    $val = '%' . $val . '%';
                default:
                    $functionName = $this->changeOr('where', $isOr);
                    $query->$functionName($key, $operate, $val);
            }
        } else {
            $functionName = $this->changeOr(is_array($val) ? 'whereIn' : 'where', $isOr);
            $query->$functionName($key, $val);
        }

        return $query;
    }

    public function insertWhereIn($query, $key, $val)
    {
        $key = preg_replace('/^in_/', '', $key);
        if (!in_array($key, $this->inQuery)) {
            return $query;
        }
        if (is_array($val)) {
            $query->whereIn($this->getFullColumn($key), $val);
        } else {
            $query->where($this->getFullColumn($key), $val);
        }

        return $query;
    }

    public function insertOrWhere($query, $key, $val, $functionName = 'where')
    {
        if (!is_array($val)) {
            return $query;
        }
        $query->$functionName(function($query) use ($val) {
            $functionName = 'where';
            foreach ($val as $orKey => $orVal) {
                // 递归or
                if (strpos($orKey, 'or_') === 0) {
                    $this->insertOrWhere($query, $orKey, $orVal, $functionName);
                } else if (strpos($orKey, 'where_') === 0) {
                    // 多个函数
                    $query->$functionName(function ($query) use ($orVal) {
                        self::insertBuilder($orVal, $query);
                    });
                } else {
                    if (!in_array($orKey, $this->eqQuery)) {
                        continue;
                    }
                    $this->insertWhere($query, $orKey, $orVal, $functionName === 'orWhere');
                }

                if ($functionName == 'where') {
                    $functionName = 'orWhere';
                }
            }
        });
    }

    public function insertWith($query, $key, $val)
    {
        $key = preg_replace('/^with_/', '', $key);
        if (!in_array($key, $this->withQuery)) {
            return $query;
        }
        if (is_callable($val)) {
            $query->with([$key => $val]);
        } else if (is_array($val)) {
            $query->with([$key => function($query) use ($val) {
                self::buildQuery($val, $query);
            }]);
        } else {
            $query->with($key);
        }

        return $query;
    }

    public function insertWithCount($query, $key, $val)
    {
        $key = preg_replace('/^with_count_/', '', $key);
        if (!in_array($key, $this->withCountQuery)) {
            return $query;
        }
        if (is_callable($val)) {
            $query->withCount([$key => $val]);
        } else {
            $query->withCount($key);
        }

        return $query;
    }

    public function insertLike($query, $key, $val)
    {
        $key = preg_replace('/^like_/', '', $key);
        if (!in_array($key, $this->likeQuery)) {
            return $query;
        }
        $query->where($this->getFullColumn($key), 'like', '%' . $val . '%');
        return $query;
    }

    public function insertOrderBy($query, $key, $val)
    {
        $key = preg_replace('/^ob_/', '', $key);
        if (!in_array($key, $this->obQuery)) {
            return $query;
        }
        $query->orderBy($this->getFullColumn($key), $val);
        return $query;
    }

    public function insertJoin($query, $key, $val)
    {
        if (!array_key_exists($key, $this->joinQuery)) {
            return $query;
        }
        $relation = Arr::get($this->joinQuery, $key);
        strpos($key, 'join_' . json_encode($relation));
        $joinType = isset($relation[2]) ? $relation[2] : 'join';//可设置join类型
        $joinTableName = preg_replace( '/^join_/', '', $key);
        $modelNamespace = 'App\EofficeApp\\' . $this->module . '\\Entities';
        $modelNamespace .= ucwords(camel_case(str_singular($joinTableName)), '');
        $modelNamespace .= 'Entity';
        $query->$joinType($joinTableName, function ($join) use ($relation, $modelNamespace, $val) {
            $join->on($relation[0], '=', $relation[1]);
            try {
                $modelNamespace::buildQuery($val, $join);
            } catch (\Exception $e) {
                logger($modelNamespace . '不存在，join配置异常');
            }
        });
        return $query;
    }

    private function getValueFromArr($val, $key, $default = '')
    {
        $operator = $default;
        if (is_array($val)) {
            isset($val[$key]) && $operator = $val[$key];
        }
        return $operator;
    }

    // 是否是有效的操作符
    private function isOperator($operator)
    {
        return in_array($operator, $this->operators);
    }

    // 转换查询
    private function changeOr($type, $isOr = false) {
        if ($isOr) {
            $types = [
                'where' => 'orWhere',
                'whereIn' => 'orWhereIn',
                'whereBetween' => 'orWhereBetween',
            ];
            return $types[$type];
        }
        return $type;
    }
}
