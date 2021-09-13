<?php

namespace App\EofficeApp\Project\NewServices\ServiceTraits;

use App\EofficeApp\Project\NewRepositories\ProjectLogRepository;
use App\EofficeApp\Project\NewServices\Managers\DataManager;
use App\EofficeApp\Project\NewServices\Managers\ProjectLogManager;

Trait ProjectLogTrait
{

    public static function logList($input)
    {
        $input = self::parseFixParam($input);
        self::collapseData($input);
        $input['ob_operate_time'] = 'desc';
        $input['with_category'] = true;
        $query = ProjectLogRepository::buildQuery($input);
        $result = paginate($query);
        ProjectLogManager::getIns('', $input['manager_id'])->formatData($result['list']);
        return $result;
    }

    public static function logSearch($input)
    {
        $result = ProjectLogManager::getIns('', $input['manager_id'])->getSearchData();
        return $result;
    }

    /**
     * 生成删除日志
     * @param string $type document|task|question
     * @param $userId
     * @param array $idNames
     * @param int $managerId
     * @return ProjectLogManager|null
     */
    private static function initDeleteLog($type, $userId, array $idNames, $managerId)
    {
        // 日志准备工作
        $logManager = null;
        $deleteFunctionName = $type . 'DeleteLog';
        $logManager = ProjectLogManager::getIns($userId, $managerId);
        $logManager->beginFillDataModule();
        foreach ($idNames as $id => $name) {
            $logManager->useRelation()->$deleteFunctionName($id, $name);
        }
        return $logManager;
    }
}