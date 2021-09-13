<?php
namespace App\EofficeApp\Salary\Repositories;

use App\EofficeApp\Salary\Entities\SalaryBaseSetEntity;
use App\EofficeApp\Base\BaseRepository;

// 薪酬基础设置表
class SalaryBaseSetRepository extends BaseRepository
{
    public function __construct(SalaryBaseSetEntity $entity)
    {
        parent::__construct($entity);
    }

    // 薪酬，基础设置，获取
    public function getSalaryBaseSetInfo() {
        return $this->entity->get();
    }
}
