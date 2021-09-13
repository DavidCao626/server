<?php

namespace App\EofficeApp\Diary\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Diary\Entities\DiaryPermissionEntity;

/**
 * 微博便签Repository类:提供微博便签表操作
 *
 * @author qishaobo
 *
 * @since  2016-04-15 创建
 */
class DiaryPermissionRepository extends BaseRepository
{
    public function __construct(DiaryPermissionEntity $entity)
    {
        parent::__construct($entity);
    }

    function getAllPermission() {
        return $this->entity->all()->toArray();
    }
}