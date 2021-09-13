<?php

namespace App\EofficeApp\HtmlSignature\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\HtmlSignature\Entities\QiyuesuoSignatureFlowConfigEntity;

/**
 * 签章插件契约锁签章设置
 *
 * @author yml
 *
 * @since  2020-07-22 创建
 */
class QiyuesuoSignatureFlowConfigRepository extends BaseRepository
{
	public function __construct(QiyuesuoSignatureFlowConfigEntity $entity)
	{
		parent::__construct($entity);
	}

	public function getList($param)
    {
        $default = [
            'fields'    => ['qiyuesuo_signature_flow_config.*',"flow_type.flow_name", "flow_type.flow_id"],
            'page'      => 0,
            'limit'     => config('eoffice.pagesize'),
            'search'    => [],
            'order_by'  => ['is_using' => 'desc', 'flow_noorder' => 'asc', 'flow_type.flow_id' => 'asc'],
            'returntype' => 'array',
        ];
        $param = array_merge($default, array_filter($param));
        $query = $this->entity
            ->select($param['fields'])
            ->wheres($param['search'])
            // ->multiWheres($param['search'])
            ->whereNull('flow_type.deleted_at')
            ->orders($param['order_by'])
            ->rightJoin('flow_type', 'flow_type.flow_id', '=', 'qiyuesuo_signature_flow_config.flow_id')
            
        ;
        $query = $query->parsePage($param['page'], $param['limit']);
        // 返回值类型判断
        if($param["returntype"] == "array") {
            return $query->get()->toArray();
        } else if($param["returntype"] == "count") {
            return $query->count();
        } else if($param["returntype"] == "object") {
            return $query->get();
        }
    }

    function getListTotal($param = [])
    {
        $param["page"]       = "0";
        $param["returntype"] = "count";
        return $this->getList($param);
    }
}
