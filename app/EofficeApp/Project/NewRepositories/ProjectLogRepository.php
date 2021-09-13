<?php

namespace App\EofficeApp\Project\NewRepositories;

use App\EofficeApp\Project\Entities\ProjectLogEntity;
use App\EofficeApp\Project\NewServices\Managers\ProjectLogManager;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
/**
 * 项目管理 资源库
 *
 * @author:喻威
 *
 * @since：2015-10-19
 *
 */
class ProjectLogRepository extends ProjectBaseRepository {

    public static function buildQuery($params = [], $query = null): Builder
    {
        $query = ProjectLogEntity::buildQuery($params, $query);

        $operateDate = Arr::get($params, 'operate_date');
        if (is_array($operateDate)) {
            $startDatetime = dateToDatetime(Arr::get($operateDate, 'startDate'));
            $endDatetime = dateToDatetime(Arr::get($operateDate, 'endDate'), false);
            if ($startDatetime && $endDatetime) {
                $query->whereBetween('operate_time', [$startDatetime, $endDatetime]);
            } elseif ($startDatetime) {
                $query->where('operate_time', '>=', $startDatetime);
            } elseif ($endDatetime) {
                $query->where('operate_time', '<=', $endDatetime);
            }
        }

        $editType = Arr::get($params, 'edit_type');// 编辑类型查询
        if ($editType) {
            self::buildEditTaskQuery($editType, $query);
        }

        return $query;
    }

    public static function buildEditTaskQuery($editType, $query = null)
    {
        $editTypeFields = ProjectLogManager::getEditTypeFields();
        $fields = Arr::get($editTypeFields, $editType);
        $params = [];
        if ($fields) {
            $params['field'] = $fields;
        }
        return self::buildQuery($params, $query);
    }

    public static function buildProjectLogQuery($managerId, $params = [], $query = null)
    {
        $params['manager_id'] = [$managerId];
        return self::buildQuery($params, $query);
    }

    // 获取项目的所有操作人与名字，eg:[[operator => '',operator_name => '']]
    public static function operatorsIdName($managerId)
    {
        $logs = self::buildProjectLogQuery($managerId, ['with_operator_user' => true])
            ->groupBy('operator')
            ->get()->toArray();
        foreach ($logs as $key => $log) {
            $logs[$key] = [
                'operator' => $log['operator'],
                'operator_name' => Arr::get($log, 'operator_user.user_name')
            ];
        }
        return $logs;
    }
}
