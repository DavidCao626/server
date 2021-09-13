<?php

namespace App\EofficeApp\Salary\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Salary\Entities\SalaryCalculateEntity;

class SalaryCalculateRepository extends BaseRepository
{
    public function __construct(SalaryCalculateEntity $entity)
    {
        parent::__construct($entity);
    }

    public function getParentData($params)
    {
        return $this->entity->wheres($params)->get();
    }
}
