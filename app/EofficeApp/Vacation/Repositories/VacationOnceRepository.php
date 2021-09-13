<?php

namespace App\EofficeApp\Vacation\Repositories;

use App\EofficeApp\Vacation\Entities\VacationOnceEntity;

class VacationOnceRepository extends VacationBaseRepository
{
    public function __construct(VacationOnceEntity $entity)
    {
        parent::__construct($entity);
    }
}