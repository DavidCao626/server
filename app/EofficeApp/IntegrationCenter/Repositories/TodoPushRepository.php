<?php
namespace App\EofficeApp\IntegrationCenter\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\IntegrationCenter\Entities\TodoPushEntity;


class TodoPushRepository extends BaseRepository
{
    public function __construct(TodoPushEntity $entity)
    {
        parent::__construct($entity);
    }
    public function getTodoPushList()
    {
        return $this->entity->orders(['created_at' => 'asc'])->get()->toArray();
    }
    public function getUseingTodoPushList()
    {
        return $this->entity->where('is_push',1)->count();
    }
}
