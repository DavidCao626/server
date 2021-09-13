<?php


namespace App\EofficeApp\Salary\Repositories;


use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Salary\Entities\SalaryPayDetailEntity;

class SalaryPayDetailRepository extends BaseRepository
{
    public function __construct(SalaryPayDetailEntity $entity)
    {
        parent::__construct($entity);
    }

    /**
     *
     * @param $where
     * @return array
     */
    public function getPayDetailsBySalaryId($salaryId)
    {
        $details =  $this->entity->where('salary_id', $salaryId)->get();
        $result = [];
        foreach($details as $detail) {
            $result[$detail->field_id] = $detail->value;
        }

        return $result;
    }


}
