<?php

namespace App\EofficeApp\Project\NewRepositories;

use App\EofficeApp\Project\Entities\ProjectManagerEntity;
use DB;
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
class ProjectManagerRepository extends ProjectBaseRepository {

    public static function buildQuery($params = [], $query = null): Builder
    {
        $query = ProjectManagerEntity::buildQuery($params, $query);

        // 将特定的id排在前面
        $prefixManagerId = Arr::get($params, 'prefixId');
        if ($prefixManagerId) {
            $query->orderByRaw("manager_id={$prefixManagerId} desc");
        }

        return $query;
    }

    // 构建开始时间结束时间的查询对象 删除
//    private static function buildDateQuery($query, $params)
//    {
//        $managerBegintime = Arr::get($params, 'manager_begintime');
//        $managerEndtime = Arr::get($params, 'manager_endtime');
//        $managerBegintime && $query->where('manager_begintime', '>=', $managerBegintime);
//        $managerEndtime && $query->where('manager_endtime', '<=', $managerEndtime);
//    }

    //  初始化权限上的项目数据
    public static function initPermissionProject() {

    }

    public static function getManagerStateName($managerState = null)
    {
        $managerStates = [
            1 => trans('project.in_the_project'),
            2 => trans('project.examination_and_approval'),
            3 => trans('project.retreated'),
            4 => trans('project.have_in_hand'),
            5 => trans('project.finished'),
        ];
        return is_null($managerState) ? $managerStates : Arr::get($managerStates, $managerState);
    }

    public static function getManagerTypeByManagerId($managerId)
    {
        $managerTypes = self::buildQuery(['manager_id' => $managerId])->pluck('manager_type', 'manager_id')->toArray();
        if (is_array($managerId)) {
            return $managerTypes;
        } else {
            return array_pop($managerTypes);
        }
    }

    /**
     * @param string|array $managerIds 列表有权限得项目id
     * @param $sortType
     * @param null $query
     */
    public static function buildProgressUpdateSort($managerIds, $sortType, $query = null)
    {
        //Todo 对接日志中心后需要修改
        $managerIds = $managerIds == 'all' ? null : $managerIds;
        // 需要使用子查询，先按时间倒叙查询，然后在orderBy去重,因为order by会基于默认排序分组，子查询倒叙排序后的默认排序应该就是倒叙，这样拿到分组的第一个就是时间最大的那个项目id。
//        $sonQuery = \DB::table('project_log')
//            ->select('manager_id', 'operate_time')
//            ->where('field', 'task_persent')
//            ->orderBy('operate_time', 'desc');
        $logTableName = 'eo_log_project';
        $logChangeTableName = 'eo_log_data_change_project';
        $sonQuery = \DB::table($logChangeTableName)
            ->join($logTableName, "{$logChangeTableName}.log_id", "=", "{$logTableName}.log_id")
            ->select('log_time', 'relation_sup_id as manager_id')
            ->where('field', 'task_persent')
            ->orderBy('log_time', 'desc');
        is_array($managerIds) && $query->whereIn('manager_id', $managerIds);
        $orderManagerId = \DB::table(\DB::raw("({$sonQuery->toSql()}) as t"))
            ->mergeBindings($sonQuery)
            ->orderBy('log_time', 'asc')
            ->groupBy('manager_id')->pluck('manager_id')->toArray();
        self::buildSortByField($query, 'manager_id', $sortType, $orderManagerId);
        $query->orderBy('manager_id', $sortType);
    }
}
