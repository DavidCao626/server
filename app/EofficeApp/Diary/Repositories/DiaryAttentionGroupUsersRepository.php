<?php

namespace App\EofficeApp\Diary\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Diary\Entities\DiaryAttentionGroupUsersEntity;

/**
 * 微博关注组用户Repository类:提供微博关注组用户表操作
 *
 * @author lixuanxuan
 *
 * @since  2018-11-13 创建
 */
class DiaryAttentionGroupUsersRepository extends BaseRepository
{
    public function __construct(DiaryAttentionGroupUsersEntity $entity)
    {
        parent::__construct($entity);
    }

}
