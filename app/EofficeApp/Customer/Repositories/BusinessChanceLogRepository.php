<?php

namespace App\EofficeApp\Customer\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Customer\Entities\BusinessChanceLogEntity;
use App\EofficeApp\Customer\Repositories\BusinessChanceRepository;

class BusinessChanceLogRepository extends BaseRepository
{
    public function __construct(BusinessChanceLogEntity $entity)
    {
        parent::__construct($entity);
    }

    public function lists(array $params = [])
    {
        $default = [
            'fields'   => ['*'],
            'search'   => [],
            'page'     => 0,
            'limit'    => config('eoffice.pagesize'),
            'order_by' => ['chance_log_id' => 'DESC'],
        ];
        $params = array_merge($default, array_filter($params));

        $query = $this->entity->select($params['fields'])->with(['hasOneUser' => function ($query) {
                $query->select(['user_id', 'user_name']);
            }]);
        return $query->wheres($params['search'])->orders($params['order_by'])->forPage($params['page'], $params['limit'])->get();
    }

    public function total(array $param = [])
    {
        $where = isset($param['search']) ? $param['search'] : [];
        return $this->entity->wheres($where)->count();
    }

    public static function validateInput(array &$input, int $id = 0, array $own = [])
    {
        if (!isset($input['log_content'])) {
            return ['code' => ['0x024016', 'customer']];
        }
        $input['business_star'] = '';
        if (isset($input['chance_possibility']) && isset($input['chance_step'])) {
            $input['business_star'] = BusinessChanceRepository::getBusinessChancesStar($input['chance_possibility'], $input['chance_step']);
        }
        $input['chance_step']        = $input['chance_step'] ?? '';
        $input['chance_possibility'] = isset($input['chance_possibility']) ? intval($input['chance_possibility']) : 0;
        if (!$validate = BusinessChanceRepository::validatePermission([CustomerRepository::VIEW_MARK], $id, $own)) {
            return ['code' => ['0x024003', 'customer']];
        }
        return true;
    }
}
