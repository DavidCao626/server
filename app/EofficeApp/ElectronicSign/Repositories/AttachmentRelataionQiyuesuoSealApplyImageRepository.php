<?php
namespace App\EofficeApp\ElectronicSign\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\ElectronicSign\Entities\AttachmentRelataionQiyuesuoSealApplyImageEntity;

class AttachmentRelataionQiyuesuoSealApplyImageRepository extends BaseRepository
{
    public function __construct(AttachmentRelataionQiyuesuoSealApplyImageEntity $entity)
    {
        parent::__construct($entity);
    }

}
