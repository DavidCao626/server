<?php

namespace App\EofficeApp\System\Permission\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\System\Permission\Entities\PermissionTypeEntity;
use DB;
use Schema;

class PermissionTypeRepository extends BaseRepository
{
    public function __construct(PermissionTypeEntity $entity)
    {
        parent::__construct($entity);
    }

    public function getPermissionTypes($param = []) {
        $default = [
            'fields'   => ["*"],
            'page'     => 0,
            'limit'    => config('eoffice.pagesize'),
            'search'   => [],
            'order_by' => ['type_order' => 'asc', 'type_id' => 'desc']
        ];

        $param = array_merge($default, array_filter($param));

        return $this->entity->select($param['fields'])
                            ->wheres($param['search'])
                            ->parsePage($param['page'], $param['limit'])
                            ->orders($param['order_by'])
                            ->get();
    }

    public function getPermissionTypeTotal() {
        $where = isset($param['search']) ? $param['search'] : [];

        return $this->entity->select(['group_id'])->wheres($where)->count();
    }

    public function getOnePurview($search) {
    	return $this->entity->wheres($search)->first();
    }

    public function getPurviewByWhere($search) {
    	return $this->entity->wheres($search)->get();
    }
}
