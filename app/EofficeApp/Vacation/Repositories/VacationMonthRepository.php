<?php

namespace App\EofficeApp\Vacation\Repositories;

use App\EofficeApp\Vacation\Entities\VacationMonthEntity;

class VacationMonthRepository extends VacationBaseRepository
{
    public function __construct(VacationMonthEntity $entity)
    {
        parent::__construct($entity);
    }
}