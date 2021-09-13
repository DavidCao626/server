<?php
namespace App\EofficeApp\PersonalSet\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\PersonalSet\Entities\ToDoListEntity;

class ToDoListRepository extends BaseRepository
{

    public function __construct(ToDoListEntity $entity)
    {
        parent::__construct($entity);
    }

    /**
     * 获取任务列表
     *
     * @param string $userId
     * @param array $param
     *
     * @return [array] 查询结果
     */
    public function toDoItemList($userId, $params = [])
    {
        $default = [
            'fields'   => ['*'],
            'page'     => 0,
            'limit' => config('eoffice.pagesize'),
            'search'   => [],
            'order_by' => ['is_finish' => 'asc', 'instancy_type' => 'desc', 'sort' => 'desc'],
        ];
        $param = array_merge($default, array_filter($params));
        $query = $this->entity->select($param['fields']);

        return $query->multiWheres($param['search'])
            ->where('user_id', $userId)
            ->orders($param['order_by'])
            ->get()->toArray();
    }

    /**
     * 获取某一紧急程度的任务列表
     *
     * @param string $userId
     * @param int $instancyType 紧急程度
     * @param array $param
     *
     * @return [array] 查询结果
     */
    public function toDoItemlistByInstancy($instancyType, $userId)
    {
        $param = [
            'fields'   => ['*'],
            'page'     => 0,
            'search'   => ['instancy_type' => $instancyType],
            'order_by' => ['is_finish' => 'asc', 'sort' => 'desc'],
        ];
        $query = $this->entity->select($param['fields']);

        return $query->multiWheres($param['search'])
            ->where('user_id', $userId)
            ->orders($param['order_by'])
            ->get()->toArray();
    }

}
