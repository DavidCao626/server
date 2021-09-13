<?php

namespace App\EofficeApp\YonyouVoucher\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\YonyouVoucher\Entities\VoucherIntergrationU8MainConfigEntity;

class VoucherIntergrationU8MainConfigRepository extends BaseRepository
{
    public function __construct(VoucherIntergrationU8MainConfigEntity $entity)
    {
        parent::__construct($entity);
    }
    public function getCount($param = [])
    {
        $query = $this->entity;
        $query = $this->getParseWhere($query, $param);
        return $query->count();
    }
    /**
     * 【凭证配置】获取U8凭证配置主表信息
     * @author [dosy]
     * @param $voucherConfigId
     * @return mixed
     */
    public function getOneInfo($voucherConfigId)
    {
        $allInfo = $this->entity->where(['voucher_config_id'=>$voucherConfigId])->first()->toArray();
        return $allInfo;
    }
    public  function getListInfo($start,$pageSize)
    {
        $allInfo = $this->entity->parsePage($start,$pageSize)->get()->toArray();
        return $allInfo;
    }
    public function getList($param = [])
    {
        $default = [
            'page' => 0,
            'order_by' => ['voucher_config_id' => 'desc'],
            'limit' => 10,
            'fields' => ['*'],
        ];
        if ($param){
            $param = array_merge($default, array_filter($param));
        }
        $query = $this->entity;
        if (isset($param['fields'])) {
            $query = $query->select($param['fields']) ->with(["hasOneFlowType" => function ($query) {
                $query->select("flow_id","flow_name");
            }]);
        }

        $query = $this->getParseWhere($query, $param);
        if (isset($param['order_by'])) {
            $query = $query->orders($param['order_by']);
        }

        return $query
            ->parsePage($param['page'], $param['limit'])
            ->get()
            ->toArray();
    }

    /**
     * 查询条件解析 where条件解析
     *
     * @param array $where 查询条件
     *
     * @return object
     *
     * @author dosy
     *
     * @since  2019-04-17
     */
    public function getParseWhere($query, $param)
    {
        if (isset($param['search'])) {
            $search = $param['search'];
            $query = $query->multiWheres($search);
            // 按凭证集成的名称
            if (isset($search['voucher_config_name']) && !empty($search['voucher_config_name'])) {
                $query = $query->where('voucher_config_name', $search['voucher_config_name']);
            }
        }
        // 当用于流程-节点设置-外发到凭证配置，凭证配置选择器的时候，会传flow_id，筛选和当前流程关联的凭证配置
        if(isset($param['flow_id'])) {
            $flowId = isset($param['flow_id']) ? $param['flow_id'] : '';
            if($flowId) {
                $query = $query->where('bind_flow_id', $flowId);
            }
        }
        return $query;
    }
}
