<?php

namespace App\EofficeApp\Webmail\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Webmail\Entities\WebmailShareConfigRelationOutboxEntity;

/**
 * 邮件共享权限设置Repository类
 */
class WebmailShareConfigRelationOutboxRepository extends BaseRepository
{
    public function __construct(WebmailShareConfigRelationOutboxEntity $entity)
    {
        parent::__construct($entity);
    }

    public function getByConfig($configId)
    {
        return $this->entity->where('config_id', $configId)->get()->toArray();
    }
}
