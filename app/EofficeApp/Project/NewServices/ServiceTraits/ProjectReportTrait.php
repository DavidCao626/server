<?php

namespace App\EofficeApp\Project\NewServices\ServiceTraits;

use App\EofficeApp\Project\NewRepositories\ProjectDocumentRepository;
use App\EofficeApp\Project\NewRepositories\ProjectManagerRepository;
use App\EofficeApp\Project\NewRepositories\ProjectQuestionRepository;
use App\EofficeApp\Project\NewServices\Managers\CacheManager;
use Illuminate\Support\Arr;
Trait ProjectReportTrait
{

    /**
     * 获取项目报表数据
     * @param array $input
     * @return array
     */
    public static function projectReport($input = [])
    {
        $projectReportData = self::getCacheProjectReportData();
        // 获取参数
        $limit = Arr::get($input, 'limit', 10);
        $page = Arr::get($input, 'page', 1);
        $input = self::parseFixParam($input);
        $orderBy = Arr::get($input, 'order_by');
        $search = Arr::get($input, 'search');
        $inProjectId = Arr::get($input, 'in_project_id', null);
        self::searchProjectReport($projectReportData, $search, $inProjectId);

        $total = count($projectReportData);
        self::formatProjectReportResult($projectReportData, $orderBy, $limit, $page);

        return [
            'total' => $total,
            'list' => $projectReportData
        ];
    }

    // 搜索过滤项目
    private static function searchProjectReport(&$projectReportData, $search, $projectIds = null)
    {
        if (is_array($projectIds)) {
            $projectKeyIds = array_flip($projectIds);
            $projectReportData = array_intersect_key($projectReportData, $projectKeyIds);
        }
        if ($search) {
            $projectQuery = ProjectManagerRepository::buildQuery($search);
            $projectKeyIds = $projectQuery->pluck('manager_id')->flip()->toArray();
            $projectReportData = array_intersect_key($projectReportData, $projectKeyIds);
        }
    }

    // 拼装数据、分页、排序
    private static function formatProjectReportResult(&$projectReportData, $orderBy, $limit = 10, $page = 1)
    {
        // 排序
        if ($orderBy) {
            $keys = array_keys($orderBy);
            $column = array_pop($keys);
            $orderType = array_pop($orderBy);
            $projectReportData = mult_array_sort($projectReportData, $column, $orderType === 'asc' ? 4 : 3);
        }
        // 分页
        if ($page !== 0) {
            $offset = ($page - 1) * $limit;
            $projectReportData = array_slice($projectReportData, $offset, $limit);
        }

        $managerIds = Arr::pluck($projectReportData, 'manager_id');
        $projectsInfo = ProjectManagerRepository::buildQuery(['in_manager_id' => $managerIds])->get()->keyBy('manager_id')->toArray();
        foreach ($projectReportData as $key => $value) {
            $managerIdTemp = $value['manager_id'];
            if (isset($projectsInfo[$managerIdTemp])) {
                $projectReportData[$key] = array_merge($projectReportData[$key], $projectsInfo[$managerIdTemp]);
                $managerState = Arr::get($projectReportData[$key], 'manager_state', 0);
                $projectReportData[$key]['manager_state_name'] = ProjectManagerRepository::getManagerStateName($managerState);
            }
        }
        self::setProjectQuestionCount($projectReportData);
        self::setProjectDocumentCount($projectReportData);
        self::spliceStringForColumns($projectReportData, ['task_overdue_percent', 'task_complete_percent', 'progress']);
    }

    // 获取缓存数据
    private static function getCacheProjectReportData()
    {
        return CacheManager::getArrayCache(CacheManager::PROJECT_REPORT, function () {
            $projectInput = ['in_manager_state' => [4, 5]];
            $projectReportData = [];
            self::getProjectReportData($projectInput, $projectReportData);
            return $projectReportData;
        }, self::REPORT_CACHE_SECOND);
    }

    // 获取所有项目统计数据
    private static function getProjectReportData($input = [], &$projectReportData)
    {
        $projectQuery = ProjectManagerRepository::buildQuery($input)->orderBy('manager_id', 'desc');
        $projectQuery->with('tasks');
        $projectReportData = [];
        $projectQuery->chunk(10000, function ($projects) use (&$projectReportData) {
            $projects->each(function ($project) use (&$projectReportData) {
                $managerId = $project->manager_id;
                $tasks = $project->tasks;
                $taskCount = $tasks->count();
                $taskPersonCount = $tasks->pluck('task_persondo')->unique()->count();
                $taskOverCount = $tasks->where('is_overdue', 1)->count();
                $taskCompleteCount = $tasks->where('task_persent', '=', 100)->count();
                $projectReportData[$managerId] = [
                    'manager_id' => $managerId,
                    'task_person_count' => $taskPersonCount,
                    'task_doing_count' => $taskCount - $taskCompleteCount,
                    'task_overdue_count' => $taskOverCount,
                    'task_overdue_percent' => percent($taskOverCount, $taskCount),
                    'task_count' => $taskCount,
                    'task_complete_count' => $taskCompleteCount,
                    'task_complete_percent' => percent($taskCompleteCount, $taskCount),
                    'progress' => object_get($project, 'progress', 0),
                    'question_count' => 0,
                    'document_count' => 0,
                ];
            });
        });
    }

    // 设置问题数量
    private static function setProjectQuestionCount(&$projectReportData)
    {
        $managerIds = Arr::pluck($projectReportData, 'manager_id');
        $params = ['in_question_project' => $managerIds, 'question_state' => [0, '>']];
        $questionCount = ProjectQuestionRepository::buildQuery($params)
            ->selectRaw('count(*) as question_count, question_project')
            ->groupBy('question_project')->pluck('question_count', 'question_project');
        foreach ($projectReportData as $key => $data) {
            $managerIdTemp = $data['manager_id'];
            if (isset($questionCount[$managerIdTemp])) {
                $projectReportData[$key]['question_count'] = $questionCount[$managerIdTemp];
            }
        }
    }

    // 设置文档数量
    private static function setProjectDocumentCount(&$projectReportData)
    {
        $managerIds = Arr::pluck($projectReportData, 'manager_id');
        $params = ['in_doc_project' => $managerIds];
        $documentCount = ProjectDocumentRepository::buildQuery($params)->selectRaw('count(*) as document_count, doc_project')
            ->groupBy('doc_project')->pluck('document_count', 'doc_project');
        foreach ($projectReportData as $key => $data) {
            $managerIdTemp = $data['manager_id'];
            if (isset($documentCount[$managerIdTemp])) {
                $projectReportData[$key]['document_count'] = $documentCount[$managerIdTemp];
            }
        }
    }

}
