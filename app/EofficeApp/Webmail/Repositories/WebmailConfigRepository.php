<?php

namespace App\EofficeApp\Webmail\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Webmail\Entities\WebmailConfigEntity;

/**
 * 邮件设置Repository类
 * @since  2019-08-30 创建
 */
class WebmailConfigRepository extends BaseRepository
{
    public function __construct(WebmailConfigEntity $entity)
    {
        parent::__construct($entity);
    }

    public function updateOrCreate($data)
    {
        return $this->entity->updateOrCreate($data);
    }

}
