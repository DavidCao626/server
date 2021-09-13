<?php
namespace App\EofficeApp\YonyouVoucher\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\YonyouVoucher\Entities\VoucherIntergrationU8LogEntity;

class VoucherIntergrationU8LogRepository extends BaseRepository
{
    public function __construct(VoucherIntergrationU8LogEntity $entity)
    {
        parent::__construct($entity);
    }

    /**
     * 新增日志
     */
    public function addLog($data)
    {
        $resAdd = $this->entity->create($data);
        if ($resAdd) {
            return true;
        }
        return false;
    }

    /**
     * 获取日志列表
     */
    public function getVoucherLogList($param, $return_type = 'array')
    {
        $fields = isset($param['fields']) ? $param['fields'] : ['voucher_intergration_u8_log.*'];
        $query  = $this->entity->select($fields);

        if (isset($param['search']) && !empty($param['search'])) {
            $query->wheres($param['search']);
        }
        if ($return_type == 'total') {
            return $query->count();
        }
        if (!empty($param['order_by'])) {
            foreach ($param['order_by'] as $filed => $sort) {
                $query = $query->orderBy($filed, $sort);
            }
        }
        // 做user关联
        $query->addSelect('user.user_name as creator_name')->leftJoin('user', 'user.user_id', '=', 'voucher_intergration_u8_log.operator');
        $query->addSelect('flow_run.run_name as log_run_name')->leftJoin('flow_run', 'flow_run.run_id', '=', 'voucher_intergration_u8_log.run_id');
        $query->addSelect('flow_type.flow_name as log_flow_name')->leftJoin('flow_type', 'flow_type.flow_id', '=', 'voucher_intergration_u8_log.flow_id');
        if (isset($param['page']) && isset($param['limit'])) {
            $query->parsePage($param['page'], $param['limit']);
        }

        return $query->get()->toArray();
    }

}
