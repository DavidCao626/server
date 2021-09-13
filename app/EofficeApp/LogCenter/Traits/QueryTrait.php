<?php
namespace App\EofficeApp\LogCenter\Traits;
/**
 * Description of QueryTrait
 *
 * @author lizhijun
 */
trait QueryTrait 
{
    /**
     * 查询排序
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $orders 排序
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function orders($query, $orders)
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
    public function page($query, $start, $limit, $isPage = true)
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
    public function wheres($query, $wheres)
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
            if (isset($operators[$operator])) {
                $whereOp = $operators[$operator]; //兼容PHP7写法
                $query = $query->$whereOp($field, $where[0]);
            } else {
                $value = $operator != 'like' ? $where[0] : '%'.$where[0].'%';
                $query = $query->where($field, $operator, $value);
            }
        }

        return $query;
    }
}
