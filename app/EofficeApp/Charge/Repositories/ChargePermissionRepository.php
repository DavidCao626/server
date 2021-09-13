<?php
namespace App\EofficeApp\Charge\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Charge\Entities\ChargePermissionEntity;

/**
 * 费用权限资源库
 *
 */
class ChargePermissionRepository extends BaseRepository {
    public function __construct(ChargePermissionEntity $entity) {
        parent::__construct($entity);
    }

    public function getChargePermission($params) {
        $default = [
            'search'   => [],
            'fields'   => ['*'],
            'page'     => 0,
            'limit'    => config('eoffice.pagesize'),
            'order_by' => ['id' => 'desc']
        ];

        $params = array_merge($default, $params);

        $query = $this->entity
            ->select($params['fields'])
            ->leftJoin('department', function($join) {
                $join->on('department.dept_id', '=', 'charge_permission.manager_value')->where('manager_type', 1);
            })->leftJoin('role', function($join) {
                $join->on('role.role_id', '=', 'charge_permission.manager_value')->where('manager_type', 2);
            })->leftJoin('user', function($join) {
                $join->on('user.user_id', '=', 'charge_permission.manager_value')->where('manager_type', 3);
            });

        return $query->wheres($params['search'])
                    ->orders($params['order_by'])
                    ->parsePage($params['page'], $params['limit'])
                    ->get();
    }

    public function getChargePermissionTotal($params) {
        $query = $this->entity->select(['id'])
            ->leftJoin('department', function($join) {
                $join->on('department.dept_id', '=', 'charge_permission.manager_value')->where('manager_type', 1);
            })->leftJoin('role', function($join) {
                $join->on('role.role_id', '=', 'charge_permission.manager_value')->where('manager_type', 2);
            })->leftJoin('user', function($join) {
                $join->on('user.user_id', '=', 'charge_permission.manager_value')->where('manager_type', 3);
            });
        if (isset($params['search'])) {
            $query->wheres($params['search']);
        }
        return $query->count();
    }

    public function getSimpleData($param)
    {
        return $this->entity->wheres($param['search'])->get();
    }
}
