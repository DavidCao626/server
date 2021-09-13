<?php

namespace App\EofficeApp\Diary\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Diary\Entities\DiaryTemplateSetUserEntity;

/**
 * 模板设置信息表
 *
 * @author dp
 *
 * @since  2015-10-20 创建
 */
class DiaryTemplateSetUserRepository extends BaseRepository
{
    public function __construct(DiaryTemplateSetUserEntity $entity)
    {
        parent::__construct($entity);
    }
}
