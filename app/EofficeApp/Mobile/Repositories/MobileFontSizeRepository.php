<?php

namespace App\EofficeApp\Mobile\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Mobile\Entities\MobileFontSizeEntity;

class MobileFontSizeRepository extends BaseRepository
{
    public function __construct(MobileFontSizeEntity $entity)
    {
        parent::__construct($entity);
    }
    public function getFontSize($userId)
    {
        $mobileFont = $this->entity->where('user_id', $userId)->first();
        
        if($mobileFont) {
            return $mobileFont->font_size;
        }
        return 0;
    }
}
