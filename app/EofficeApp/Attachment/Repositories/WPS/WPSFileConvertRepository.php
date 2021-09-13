<?php


namespace App\EofficeApp\Attachment\Repositories\WPS;


use App\EofficeApp\Attachment\Entities\WPS\WPSFileConvertEntity;
use App\EofficeApp\Base\BaseRepository;

class WPSFileConvertRepository extends BaseRepository
{
    public function __construct(WPSFileConvertEntity $entity)
    {
        parent::__construct($entity);
    }

    /**
     * 创建文档转换任务
     *
     * @param array $params
     */
    public function addConvertTask($params)
    {
        $entity = new WPSFileConvertEntity();

        $this->updateConvertTask($entity, $params);

        $entity->save();

        return $entity;
    }

    /**
     * 更新文档转换任务
     *
     * @param WPSFileConvertEntity $entity
     * @param array $params
     * @return bool
     */
    public function updateConvertTask(WPSFileConvertEntity $entity, $params)
    {
        foreach ($params as $key => $param) {
            $entity->{$key} = $param;
        }

        return $entity->save();
    }

    /**
     * 根据附件id获取文档转换任务
     *
     * @param string $attachmentId
     * @return mixed
     */
    public function getConvertTaskByAttachmentId($attachmentId)
    {
        return $this->entity->where('attachment_id', $attachmentId)->latest()->first();
    }

    /**
     * 根据任务id获取文档转换任务
     *
     * @param string $taskId
     * @return mixed
     */
    public function getConvertTaskByTaskId($taskId)
    {
        return $this->entity->where('task_id', $taskId)->first();
    }

    /**
     * 根据id获取转换任务
     *
     * @param string $id
     * @return mixed
     */
    public function getConvertTaskById($id)
    {
        return $this->entity->find($id);
    }

    /**
     * 根据taskId删除转换任务
     *
     * @param string $taskId
     * @return mixed
     */
    public function deleteConvertTaskByTaskId($taskId)
    {
        return $this->entity->where('task_id', $taskId)->delete();
    }

    /**
     * 将转换任务标记为完成
     * @param WPSFileConvertEntity $entity
     * @param string $expires
     */
    public function completeConvert(WPSFileConvertEntity $entity, $expires)
    {
        $entity->setCompletedAttribute(true);
        $entity->setExpiresAttribute($expires);
        $entity->save();
    }
}