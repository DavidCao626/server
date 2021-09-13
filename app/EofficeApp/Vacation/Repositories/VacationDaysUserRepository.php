<?php

namespace App\EofficeApp\Vacation\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Vacation\Entities\VacationDaysUserEntity;
use Illuminate\Support\Facades\DB;

class VacationDaysUserRepository extends BaseRepository
{
    public function __construct(VacationDaysUserEntity $vacationDaysUserEntity)
    {
        parent::__construct($vacationDaysUserEntity);
    }

    /**
     * [getVacationDaysList 获取用户假期天数列表]
     *
     * @author 施奇batchUpdate
     *
     * @param  [array]               $search [查询条件]
     *
     * @return [object]                      [查询结果]
     */
    public function getVacationDaysList($search)
    {
        return $this->entity->wheres($search)
            ->get();
    }

    /**
     * [getVacationDays 获取一条假期天数数据]
     *
     * @author 施奇
     *
     * @param  [array]          $where [查询条件]
     *
     * @return [object]                [查询结果]
     */
    public function getVacationDays($where)
    {
        return $this->entity->wheres($where)->first();
    }

    public function getVacationDaysByKeys($keys, $wheres)
    {
        //key：[user_id-vacation_id,...]
        if (!$keys || !is_array($keys)) {
            return false;
        }
        $query = $this->entity->select('*')->whereIn(DB::raw("concat(user_id,'-',vacation_id)"), $keys);
        if ($wheres) {
            $query = $query->wheres($wheres);
        };
        return $query->get()->toArray();
    }

    public function increment($column, $number, $where)
    {
        return $this->entity->wheres($where)->increment($column, $number);
    }

    /**
     * 批量更新，多个条件多个值,
     * 注意：支持自增自减等操作，同一字段不支持同时固定值和自增自减的更新
     * @param $multipleData
     * @param bool $tableName
     * @return bool|int
     *
     */
    public function batchUpdate($multipleData, $tableName = false)
    {
        /*$multipleData = [
            ['where' => ['user_id' => 'admin', 'vacation_id' => '1'], 'update' => ['days' => [+,5], 'leave_days' => rand(1, 100)]],
            ['where' => ['user_id' => 'admin', 'vacation_id' => '2'], 'update' => ['days' => [+,3], 'leave_days' => rand(1, 100)]],
        ];*/
        if (!$multipleData) {
            return true;
        }
        if (!$tableName) {
            $tableName = $this->entity->table;
        }

        $updateGroup = [];

        $allWheres = [];

        foreach ($multipleData as $data) {
            if (!isset($data['where']) || !isset($data['update'])) {
                continue;
            }
            $wheres = $data['where'];
            $updateColumn = $data['update'];
            foreach ($updateColumn as $column => $value) {
                $updateGroup[$column][] = ['where' => $wheres, 'value' => $value];
            }
        }

        $sql = "update $tableName set";
        $i = 0;
        $bindings = [];
        foreach ($updateGroup as $column => $data) {
            $sql .= " $column= case";
            foreach ($data as $oneUpdate) {
                $wheres = $oneUpdate['where'];
                $updateValue = $oneUpdate['value'];
                $j = 0;
                $sql .= " when";
                foreach ($wheres as $field => $value) {
                    //存入到总的where中提升sql速度
                    $allWheres[$field][] = "'$value'";
                    if ($j == count($wheres) - 1) {
                        $sql .= " $field= ?";
                    } else {
                        $sql .= " $field= ? and";
                    }
                    $j++;
                    $bindings[] = $value;
                }
                $sql .= " then ";
                if (is_array($updateValue)) {
                    $sql .= $column . $updateValue[0];
                    $updateValue = $updateValue[1];
                }
                $sql .= " ?";
                $bindings[] = $updateValue;
            }
            $sql .= " else $column";
            if ($i == count($updateGroup) - 1) {
                $sql .= ' END';
            } else {
                $sql .= ' END,';
            }
            $i++;
        }
        $wheres = [];
        foreach ($allWheres as $field => $where) {
            $wheres[] = "$field in (" . implode(',', array_unique($where)) . ")";
        }
        $wheres = implode(' and ', $wheres);
        $sql .= " where " . $wheres;
        return DB::update($sql, $bindings);
    }
}