<?php

namespace App\EofficeApp\System\Domain\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\System\Domain\Entities\DomainSyncLogEntity;

/**
 * @域同步日志资源库类
 *
 * @author niuxiaoke
 */
class DomainSyncLogRepository extends BaseRepository
{
    public function __construct(DomainSyncLogEntity $entity)
    {
        parent::__construct($entity);
    }

    public function getSyncLogs($param=[])
    {
        $default = [
            'page'     => 0,
            'limit'    => config('eoffice.pagesize'),
            'order_by' => [],
            'fields'   => ['*'],
            'search'   => []
        ];

        $param = array_merge($default, $param);

        return $this->entity->select($param['fields'])
                    		->parsePage($param['page'], $param['limit'])
                    		->orders($param['order_by'])
                        	->get();
    }

    public function getSyncLogsTotal($param=[])
    {
    	$where = isset($param['search']) ? $param['search'] : [];

    	return $this->entity->select(['log_id'])->wheres($where)->count();
    }

    public function getSyncLogByLogId($logId)
    {
    	return $this->entity->where("log_id", $logId)->first();
    }
}
