<?php

namespace App\EofficeApp\Diary\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Diary\Entities\DiaryLikePeopleEntity;

/**
 * 微博点赞人
 *
 * @author 
 *
 * @since  
 */
class DiaryLikePeopleRepository extends BaseRepository
{
    public function __construct(DiaryLikePeopleEntity $entity)
    {
        parent::__construct($entity);
    }
}
