<?php

namespace App\EofficeApp\Webmail\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Webmail\Entities\WebmailServerEntity;

/**
 * 邮件服务器Repository类:提供邮件服务器相关的数据库操作方法。
 *
 * @author qishaobo
 *
 * @since  2016-07-28 创建
 */
class WebmailServerRepository extends BaseRepository
{
    public function __construct(WebmailServerEntity $entity)
    {
        parent::__construct($entity);
    }

    /**
     * 获取邮件服务器信息
     *
     * @param array $search 查询条件
     *
     * @return array 查询结果
     *
     * @author qishaobo
     *
     * @since  2016-07-28
     */
    public function getWebmailServer($search)
    {
        return $this->entity->select(['*'])->wheres($search)->limit(1)->get();
    }

}