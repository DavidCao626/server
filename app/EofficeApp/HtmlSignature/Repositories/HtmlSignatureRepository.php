<?php

namespace App\EofficeApp\HtmlSignature\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\HtmlSignature\Entities\HtmlSignatureEntity;

/**
 * 签章插件契约锁签章设置
 *
 * @author yml
 *
 * @since  2020-07-22 创建
 */
class HtmlSignatureRepository extends BaseRepository
{
	public function __construct(HtmlSignatureEntity $entity)
	{
		parent::__construct($entity);
	}

	public function getNum($param)
	{
        return  $this->getLogsParseWhere($this->entity, $param)->count();
	}

	public function getLogs(array $param = [])
	{
		$default = [
            'fields'    => ['*'],
            'search'    => ['signature_type' => [2]],
            'page'      => 1,
            'limit'     => config('eoffice.pagesize'),
            'order_by'  => ['id' => 'asc'],
        ];
        $param = array_merge($default, array_filter($param));
        $query = $this->entity
        ->select($param['fields']);
        $query = $this->getLogsParseWhere($query, $param);

        return $query->orders($param['order_by'])
            ->parsePage($param['page'], $param['limit'])
            ->get()
            ->toArray();
	}

	public function getLogsParseWhere($query, array $param = [])
	{
		if ($param) {
			$where = $param['search'] ?? [];
			if (isset($param['node_id']) && !empty($param['node_id'])){
				$where['node_id'] = [$param['node_id']];
			}
			$query = $query->wheres($where);
		}
		return $query;
	}
}
