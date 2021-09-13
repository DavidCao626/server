<?php


namespace App\EofficeApp\Invoice\Repositories;


use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Invoice\Entities\InvoiceFlowNodeActionSettingEntities;

class InvoiceFlowNodeActionSettingRepositories extends BaseRepository
{
    public function __construct(InvoiceFlowNodeActionSettingEntities $entity)
    {
        parent::__construct($entity);
    }
}