<?php
namespace App\EofficeApp\User\Repositories;

use App\EofficeApp\User\Entities\UserQuickRegisterEntity;
use App\EofficeApp\Base\BaseRepository;

/**
 * 用户快速注册表知识库
 */
class UserQuickRegisterRepository extends BaseRepository
{
    public function __construct(UserQuickRegisterEntity $entity)
    {
        parent::__construct($entity);
    }

    public function getRegisterUser($param) {
        $default = [
            'fields' => ['*'],
            'page' => 0,
            'limit' => config('eoffice.pagesize'),
            'search' => [],
            'order_by' => ['id' => 'desc'],
        ];

        $param = array_merge($default, $param);

        return $this->entity ->select($param['fields'])
                        ->wheres($param['search'])
                        ->orders($param['order_by'])
                        ->parsePage($param['page'], $param['limit'])
                        ->get();
    }

    public function getRegisterUserTotal($param) {
        $default = [
            'search' => [],
        ];
        $param = array_merge($default, $param);
        return $this->entity->wheres($param['search'])->count();
    }
}
