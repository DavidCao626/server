<?php

namespace App\EofficeApp\Birthday\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Birthday\Entities\BirthdaySetEntity;

/**
 * 访问控制 资源库
 *
 * @author:喻威
 *
 * @since：2015-10-19
 *
 */
class BirthdaySetRepository extends BaseRepository {

    public function __construct(BirthdaySetEntity $entity) {
        parent::__construct($entity);
    }

    public function getBirthdaySetting() {
        return $this->entity
                    ->select(['paramKey', 'paramValue'])
                    ->where('paramKey', 'remind_type')
                    ->first();
    }
}
