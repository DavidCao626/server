<?php
namespace App\EofficeApp\ElectronicSign\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\ElectronicSign\Entities\QiyuesuoServerEntity;

class QiyuesuoServerRepository extends BaseRepository
{
    public function __construct(QiyuesuoServerEntity $entity)
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
    public function getServersCount(array $param)
    {
        $query = $this->entity;
        $query = $this->getServersParseWhere($query, $param);
        return $query->count();
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
    public function getServersList(array $param)
    {
        $default = [
            'page' => 0,
            'order_by' => ['serverId' => 'desc'],
            'limit' => 10,
            'fields' => ['*'],
        ];

        $param = array_merge($default, array_filter($param));
        if (isset($param['fields']) && in_array('serverNameType', $param['fields'])) {
            $key = array_keys($param['fields'], 'serverNameType')[0];
            unset($param['fields'][$key]);
        }
        $query = $this->entity;
        if (isset($param['fields'])) {
            $query = $query->select($param['fields']);
        }
        $query = $this->getServersParseWhere($query, $param);
        if (isset($param['order_by'])) {
            $query = $query->orders($param['order_by']);
        }

        $list = $query
            ->parsePage($param['page'], $param['limit'])
            ->get()
            ->toArray();
        if ($list) {
            foreach ($list as $key => $value) {
                $list[$key]['serverNameType'] = $value['serverName'] . ($value['serverType'] == 'private' ? trans('electronicsign.private') : trans('electronicsign.public'));
            }
        }
        return $list;
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
    public function getServersParseWhere($query, $param)
    {
        if (isset($param['search'])) {
            if (isset($param['search']['serverName'])) {
                $query = $query->where('serverName', 'like', '"%' . $param['search']['serverName'] . '%"');
            }
            if (isset($param['search']['serverType'])) {
                $query = $query->where('serverType', $param['search']['serverType']);
            }
        }
        return $query;
    }

    public function getServerByFlowId($flowId)
    {
        return $this->entity
            ->select('qiyuesuo_server.*')
            ->leftjoin('qiyuesuo_setting', 'qiyuesuo_server.serverId', '=', 'qiyuesuo_setting.serverId')
            ->where('qiyuesuo_setting.workflowId', $flowId)
            ->first();
    }
    /**
     * 获取最近添加的有效的契约锁服务
     *
     * @return void
     * @author yuanmenglin
     * @since
     */
    public function getLastServer($where = [], $order = [])
    {
        $query = $this->entity
            ->whereNotNull('accessKey')
            ->whereNotNull('accessSecret');
        if ($where) {
            $query = $query->where($where);
        }
        if ($order) {
            $query = $query->orders($order);
        }
        return $query->first();
    }

    public function getSealApplyServerByFlowId($flowId)
    {
        return $this->entity
            ->select('qiyuesuo_server.*')
            ->leftjoin('qiyuesuo_seal_apply_setting', 'qiyuesuo_server.serverId', '=', 'qiyuesuo_seal_apply_setting.serverId')
            ->where('qiyuesuo_seal_apply_setting.workflowId', $flowId)
            ->first();
    }

}
