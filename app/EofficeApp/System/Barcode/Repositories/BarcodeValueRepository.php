<?php


namespace App\EofficeApp\System\Barcode\Repositories;


use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\System\Barcode\Entities\BarcodeValueEntity;

class BarcodeValueRepository extends BaseRepository
{
    public function __construct(BarcodeValueEntity $entity)
    {
        parent::__construct($entity);
    }
}
