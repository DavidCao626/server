<?php

namespace App\EofficeApp\Project\NewServices\ServiceTraits;

use App\EofficeApp\Project\Entities\ProjectDiscussEntity;
use App\EofficeApp\Project\NewRepositories\ProjectDiscussRepository;
use App\EofficeApp\Project\NewRepositories\ProjectStatusRepository;
use App\EofficeApp\Project\NewServices\Managers\DatabaseManager;
use App\EofficeApp\Project\NewServices\Managers\DataManager;
use App\EofficeApp\Project\NewServices\Managers\HelpersManager;
use App\Utils\ResponseService;
use Illuminate\Support\Arr;
Trait ProjectDiscussTrait
{
    public static function discussList(DataManager $dataManager)
    {
        $params = [
            'order_by' => ['discuss_time' => 'desc']
        ];
        $apiParams = $dataManager->getApiParams();
        $params = array_merge($params, $apiParams);
        $managerId = $dataManager->getManagerId();

        $queryParams = self::handleListParams($params);
        $query = ProjectDiscussRepository::buildProjectDiscussListQuery($managerId, $queryParams);
        $data = HelpersManager::paginate($query, $dataManager);
        self::handleDiscussInfo($data['list']);

        return $data;
    }

    public static function discussAdd(DataManager $dataManager)
    {

        $model = new ProjectDiscussEntity();
        $params = $dataManager->getApiParams();
        $curUserId = $dataManager->getCurUserId();
        $managerId = $dataManager->getManagerId();

        $params['discuss_time'] = date("Y-m-d H:i:s", time());
        $params['discuss_person'] = $curUserId;
        $params['discuss_project'] = $managerId;
        $model->fill($params);
        $model->save();

        self::syncAttachments($model, $params, 'attachments', 'add');
        ProjectStatusRepository::update($managerId, 'project', $managerId, $curUserId);

        return $model; // 固定格式
    }

    public static function discussEdit(DataManager $dataManager)
    {
        $params = $dataManager->getApiParams();
        $managerId = $dataManager->getManagerId();
        $curUserId = $dataManager->getCurUserId();
        $discussId = $dataManager->getApiParams('discuss_id');
        $model = ProjectDiscussRepository::buildQuery()->find($discussId);
        !$model && ResponseService::throwException('0x036001', 'project'); // 数据不存在
        !HelpersManager::testTime($model['discuss_time']) && ResponseService::throwException('0x000017', 'common');
        $model->fill($params)->save();

        self::syncAttachments($model, $params, 'attachments');
        ProjectStatusRepository::update($managerId, 'project', $managerId, $curUserId);

        return ['model' => $model];
    }

    public static function discussDelete(DataManager $dataManager)
    {
        $project = $dataManager->getProject();
        $discussId = $dataManager->getApiParams('discuss_id');
        $model = ProjectDiscussRepository::buildQuery()->find($discussId);
        !$model && ResponseService::throwException('0x036001', 'project'); // 数据不存在

        $isManager = $project->testRoleKey('manager_person');
        if (!$isManager && !HelpersManager::testTime($model['discuss_time'])) {
            ResponseService::throwException('0x000017', 'common');
        }

        // 有回复不能删除
        $replyIds = ProjectDiscussRepository::buildQuery(['discuss_replyid' => $discussId])->pluck('discuss_id')->toArray();
        if (!$isManager && $replyIds) {
            $replyIds && ResponseService::throwException('0x000017', 'common');
        }

        $deleteIds = array_merge([$discussId], $replyIds);

        return DatabaseManager::deleteByIds(ProjectDiscussEntity::class, $deleteIds);
    }


    // 处理讨论的信息，回复、引用、附件数据
    private static function handleDiscussInfo(&$data)
    {
        HelpersManager::toEloquentCollection($data, function (&$data) {
            // 获取附件
            $quoteIds = $data->pluck('discuss_quoteid')->toArray();
            $discussIds = $data->pluck('discuss_id')->toArray();
            $replies = $data->pluck('reply');
            $replyIds = $replies ? $replies->collapse()->pluck('discuss_id')->toArray() : [];
            $allDiscussIds = array_unique(array_merge($quoteIds, $discussIds, $replyIds));
            $allAttachments = self::getAttachments('project_discuss', $allDiscussIds, true);
            unset($quoteIds, $discussIds, $replyIds);

            foreach ($data as &$item) {
                self::handleUserNameAndAttachmentIds($item, $allAttachments);
                if ($item->quote) {
                    self::handleUserNameAndAttachmentIds($item['quote'], $allAttachments);
                }
                if ($item->reply) {
                    foreach ($item['reply'] as &$reply) {
                        self::handleUserNameAndAttachmentIds($reply, $allAttachments);
                    }
                }
            }
            return $data;
        });
    }

    private static function handleUserNameAndAttachmentIds(&$item, $allAttachmentIds)
    {
        $item['user_name'] = object_get($item, 'user.user_name', '');
        $item->unsetRelation('user');
        $discussId = $item['discuss_id'];
        $item['attachments'] = Arr::get($allAttachmentIds, $discussId, []);
    }
}
