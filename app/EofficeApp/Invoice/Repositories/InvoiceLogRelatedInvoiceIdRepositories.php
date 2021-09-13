<?php


namespace App\EofficeApp\Invoice\Repositories;


use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Invoice\Entities\InvoiceLogRelatedInvoiceIdEntities;

class InvoiceLogRelatedInvoiceIdRepositories extends BaseRepository
{
    public function __construct(InvoiceLogRelatedInvoiceIdEntities $entity)
    {
        parent::__construct($entity);
    }

    public function getInvoiceIds($search)
    {
        return $this->entity->where(function($query) use ($search) {
            $query->where('code', 'like', '%'.$search.'%')->orWhere('number', 'like', '%'.$search.'%');
        })->pluck('invoice_id')->toArray();
    }
}