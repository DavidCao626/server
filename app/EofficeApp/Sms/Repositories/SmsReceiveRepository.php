<?php

namespace App\EofficeApp\Sms\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Sms\Entities\SmsReceiveEntity;

/**
 * 内部消息 资源库
 *
 * @author:喻威
 *
 * @since：2015-10-19
 *
 */
class SmsReceiveRepository extends BaseRepository {

    public function __construct(SmsReceiveEntity $entity) {
        parent::__construct($entity);
    }

}
