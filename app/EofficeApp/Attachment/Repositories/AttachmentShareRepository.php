<?php

namespace App\EofficeApp\Attachment\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Attachment\Entities\AttachmentShareEntity;

class AttachmentShareRepository extends BaseRepository
{
    public function __construct(AttachmentShareEntity $entity)
    {
        parent::__construct($entity);
    }


    public function getOneShareAttachment($wheres)
    {
        return $this->entity->wheres($wheres)->first();
    }
}
