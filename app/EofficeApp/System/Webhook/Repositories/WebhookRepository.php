<?php

namespace App\EofficeApp\System\Webhook\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\System\Webhook\Entities\WebhookEntity;

/**
 * webhook Repository类:提供webhook 表操作资源
 *
 * @author qishaobo
 *
 * @since  2016-07-11 创建
 */
class WebhookRepository extends BaseRepository
{

    public function __construct(WebhookEntity $entity)
    {
        parent::__construct($entity);
    }

    /**
     * 获取webhook
     *
     * @param  array $where 查询条件
     *
     * @return array        查询结果
     *
     * @author qishaobo
     *
     * @since  2016-07-12 创建
     */
    public function getWebhook($where)
    {
        return $this->entity->wheres($where)->get();
    }
}
