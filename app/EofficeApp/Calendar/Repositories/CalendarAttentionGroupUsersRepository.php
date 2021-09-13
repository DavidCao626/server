<?php

namespace App\EofficeApp\Calendar\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Calendar\Entities\CalendarAttentionGroupUsersEntity;

/**
 * 微博关注组用户Repository类:提供微博关注组用户表操作
 *
 * @author lixuanxuan
 *
 * @since  2018-11-13 创建
 */
class CalendarAttentionGroupUsersRepository extends BaseRepository
{
    public function __construct(CalendarAttentionGroupUsersEntity $entity)
    {
        parent::__construct($entity);
    }

}
