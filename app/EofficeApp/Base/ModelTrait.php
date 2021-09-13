<?php

namespace App\EofficeApp\Base;


trait ModelTrait
{
    /**
     * 2019-04-08
     *
     * 拼接查询条件
     *
     * @param object $query
     * @param array $wheres
     *
     * @return object
     */
    public function wheres($query, $wheres)
    {
        $operators = [
            'between'     => 'whereBetween',
            'not_between' => 'whereNotBetween',
            'in'          => 'whereIn',
            'not_in'      => 'whereNotIn',
            'null'        => 'whereNull',
            'not_null'    => 'whereNotNull',
            'or'          => 'orWhere',
        ];

        if (empty($wheres)) {
            return $query;
        }
        if (isset($wheres['whereRaw'])) {
            $query = $query->whereRaw($wheres['whereRaw']);
            unset($wheres['whereRaw']);
        }
        foreach ($wheres as $field => $where) {
            if (!is_array($where)) {
                continue;
            }
            $operator = isset($where[1]) ? $where[1] : '=';
            $operator = strtolower($operator);
            if (isset($operators[$operator])) {
                $whereOp = $operators[$operator]; //兼容PHP7写法
                if ($operator == 'null' || $operator == 'not_null') {
                    $query = $query->$whereOp($field);
                } else {
                    $query = $query->$whereOp($field, $where[0]);
                }
            } else {
                $value = $operator != 'like' ? $where[0] : '%' . $where[0] . '%';
                $query = $query->where($field, $operator, $value);
            }
        }
        return $query;
    }
}
