<?php


namespace App\EofficeApp\Elastic\Repositories;


use App\EofficeApp\Elastic\Entities\AttachmentContentEntity;

class AttachmentContentRepository extends ElasticBaseRepository
{
    /**
     * 附件内容表
     *
     * @param AttachmentContentEntity $entity
     *
     */
    public function __construct(AttachmentContentEntity $entity )
    {
        parent::__construct($entity);
    }

    /**
     * 获取附件信息
     *
     * @param string $table
     * @param string|int $entityId
     *
     * @return array
     */
    public function getAttachmentInfo($table, $entityId): array
    {
        $data = $this->entity->where('attachment_relation_table', $table)
                             ->where('attachment_relation_entity_id', $entityId)
                             ->get()
                             ->toArray();
        return $data;
    }

    /**
     * 更新附件内容表
     */
    public function updateContentByCategoryAndEntityId($where, $data)
    {
        $this->entity->updateOrCreate($where, $data);;
    }
}