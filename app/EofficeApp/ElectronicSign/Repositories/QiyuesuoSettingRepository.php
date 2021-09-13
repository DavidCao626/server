<?php
namespace App\EofficeApp\ElectronicSign\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\ElectronicSign\Entities\QiyuesuoSettingEntity;

class QiyuesuoSettingRepository extends BaseRepository
{
    public function __construct(QiyuesuoSettingEntity $entity)
    {
        parent::__construct($entity);
    }

    /**
     * 查看服务条数
     *
     * @param  array  $param 查询条件
     *
     * @return integer       查询数量
     *
     * @author yml
     *
     * @since  2019-04-17
     */
    public function getSettingCount(array $param)
    {
        $param['return_type'] = 'count';
        return $this->getSettingList($param);
    }

    /**
     * 服务列表
     *
     * @param  array  $param 查询条件
     *
     * @return integer       查询数量
     *
     * @author yml
     *
     * @since  2019-04-17
     */
    public function getSettingList(array $param)
    {
        $default = [
            'page' => 0,
            'order_by' => ['settingId' => 'desc'],
            'limit' => 10,
            'fields' => ['*'],
        ];

        $param = array_merge($default, array_filter($param));
        $query = $this->entity;
        if (isset($param['fields'])) {
            $query = $query->select($param['fields']);
        }
        $query = $query->with(['settinghasOneFlowName' => function ($query) {
            $query->select(['flow_id', 'flow_name']);
        }])
            ->with(["settinghasManySignInfo" => function ($query) {
                $query->select(['signInfoId', 'settingId', 'signNode', 'tenantType', 'tenantName', 'canLpSign', 'contact', 'type', 'serialNo', 'keyword', 'seal', 'seals'])->orderBy('signInfoId', 'asc');
            }])
            ->with(["settinghasManyOperationInfo" => function ($query) {
                $query->select(['operationId', 'settingId', 'nodeId', 'operation']);
            }])
            ->with(["settinghasManyOutsendInfo" => function ($query) {
                $query->select(['settingId', 'action', 'nodeId', 'flowOutsendTimingArrival', 'flowOutsendTimingSubmit', 'back'])->where('type', 'contract');
            }])
            ->with('settinghasOneServer');
        $query = $this->getSettingParseWhere($query, $param);
        if (isset($param['order_by'])) {
            $query = $query->orders($param['order_by']);
        }
        if (isset($param['return_type']) && $param['return_type'] == 'count') {
            return $query->count();
        } else {
            return $query
                ->parsePage($param['page'], $param['limit'])
                ->get()
                ->toArray();
        }
    }

    /**
     * 查询条件解析 where条件解析
     *
     * @param  array $where  查询条件
     *
     * @return object
     *
     * @author yml
     *
     * @since  2019-04-17
     */
    public function getSettingParseWhere($query, $param)
    {
        if (isset($param['search'])) {
            if (isset($param['search']['flow_name'])) {
                $flowName = $param['search']['flow_name'];
                unset($param['search']['flow_name']);
                $query = $query->leftJoin('flow_type', function($join) use ($flowName) {
                    $join->on('flow_type.flow_id', '=', 'qiyuesuo_setting.workflowId');
                })
                ->where('flow_type.flow_name', 'like', '%'. $flowName.'%');
            }
        }
        return $query;
    }
    /**
     * 服务设置被集成设置绑定的个数
     *
     * @param [type] $serverId  服务设置ID
     * @return int
     */
    public function bindServerCount($serverId)
    {
        return $this->entity->where('serverId', $serverId)->count();
    }

    /**
     * 根据集成设置ID获取详情
     *
     * @param [type] $settingId
     * @return void
     */
    public function getSettingDetail($settingId)
    {
        $query = $this->entity->with(['settinghasOneFlowName' => function ($query) {
            $query = $query->select(['flow_id', 'flow_name']);
        }])
            ->with(["settinghasManySignInfo" => function ($query) {
                $query->select(['signInfoId', 'settingId', 'signNode', 'tenantType', 'tenantName', 'contact', 'canLpSign', 'serialNo', 'type', 'keyword', 'seal', 'seals']);
            }])
            ->with(["settinghasManyOperationInfo" => function ($query) {
                $query->select(['operationId', 'settingId', 'nodeId', 'operation']);
            }])
            ->with(["settinghasOneServer" => function ($query) {
                $query->select(['serverId', 'serverName', 'serverType']);
            }])
            ->with(["settinghasManyOutsendInfo" => function ($query) {
                $query->select(['settingId', 'action', 'nodeId', 'flowOutsendTimingArrival', 'flowOutsendTimingSubmit', 'back'])->where('type', 'contract');
            }]);

        return $query->find($settingId);
    }

    /**
     * 根据流程ID获取集成设置详情
     *
     * @param [type] $flowId 定义流程ID
     * @param boolean $with 是否获取关联表 签署信息、节点操作权限 数据
     * @param integer $settingId  集成设置ID【验证当前流程是否已设置集成 修改时排除当前集成ID】
     * @return void
     */
    public function getSettingDetailByFlowId($flowId, $with = true, $settingId = 0)
    {
        $query = $this->entity;
        if ($with) {
            $query = $query->with(['settinghasOneFlowName' => function ($query) {
                $query = $query->select(['flow_id', 'flow_name']);
            }])
                ->with(["settinghasManySignInfo" => function ($query) {
                    $query->select(['signInfoId', 'settingId', 'signNode', 'tenantType', 'tenantName', 'contact', 'canLpSign', 'type', 'serialNo', 'keyword', 'seal', 'seals'])->orderBy('signInfoId', 'asc');
                }])
                ->with(["settinghasManyOperationInfo" => function ($query) {
                    $query->select(['operationId', 'settingId', 'nodeId', 'operation']);
                }])
                ->with(["settinghasOneServer" => function ($query) {
                    $query->select(['serverId', 'serverName', 'serverType']);
                }])
                ->with(["settinghasManyOutsendInfo" => function ($query) {
                    $query->select(['settingId', 'action', 'nodeId', 'flowOutsendTimingArrival', 'flowOutsendTimingSubmit', 'back'])->where('type', 'contract');
                }]);
        }
        if ($settingId) {
            $query = $query->where('settingId', '<>', $settingId);
        }

        return $query->where('workflowId', $flowId)->first();
    }
    /**
     * 获取集成设置是否已有运行流程
     *
     * @param [type] $settingId
     *
     * @return int
     * @author yuanmenglin
     * @since 2019-4-30
     */
    public function getSettingRelatedFlowUsing($settingId)
    {
        $setting = $this->entity
            ->with(['settingHasManyFlowRun' => function ($query) {
                $query->select(['run_id', 'flow_id']);
            }])
            ->where('settingId', $settingId)
            ->first();

        return $setting ? $setting->toArray()['setting_has_many_flow_run'] : [];
    }

    public function checkUrl($url)
    {
        $preg = "/^http(s)?:\\/\\/.+/";
        if (preg_match($preg, $url)) {
            return true;
        } else {
            return false;
        }
    }
}
