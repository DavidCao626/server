<?php


namespace App\EofficeApp\Elastic\Repositories;


use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Elastic\Entities\ElasticSearchOperationLogEntity;
use App\EofficeApp\Elastic\Entities\ElasticSearchUpdateLogEntity;

class ElasticSearchOperationLogRepository extends BaseRepository
{
    /**
     * 全站搜索更新记录
     *
     * @param \App\EofficeApp\Elastic\Entities\ElasticSearchOperationLogEntity $entity
     *
     */
    public function __construct(ElasticSearchOperationLogEntity $entity )
    {
        parent::__construct($entity);
    }
    /**
     * 生成更新记录
     *
     * @param array $data
     */
    public function createLog($data)
    {
        $this->entity->create($data);
    }

    /**
     * 获取操作记录日志数量
     *
     * @param array $params
     *
     * @return int
     */
    public function getOperationLogCount($params): int
    {
        $query = $this->entity;
        $query = $this->getParseWhere($query, $params);

        return $query->count();
    }

    /**
     * 获取更新日志列表
     *
     * @param array $params
     *
     * @return array
     */
    public function getOperationLogList($params): array
    {
        $default = [
            'page' 		=> 0,
            'order_by' 	=> ['elastic_search_operation_log.log_time' => 'desc'],
            'limit'		=> 10,
            'fields'	=> ['log_time','user.user_name','operator','config_type','log_content']

        ];

        $param = array_merge($default, array_filter($params));
        $query  = $this->entity;

        $query = $this->getParseWhere($query, $param);
        if (isset($param['fields'])) {
            $query = $query->select($param['fields']);
        }
        if (isset($param['order_by'])) {
            $query = $query->orders($param['order_by']);
        }

        return $query->parsePage($param['page'], $param['limit'])->get()->toArray();
    }

    /**
     * 解析 where 参数
     *
     * @param ElasticSearchOperationLogEntity $query
     * @param array $params
     *
     * @return ElasticSearchOperationLogEntity
     */
    private function getParseWhere(ElasticSearchOperationLogEntity $query, $params)
    {
        // 若查询操作人为用户ID 则连接 user 查询
        $query = $query->leftJoin('user', 'user.user_id', '=', 'elastic_search_operation_log.operator');
        if (isset($params['search']['operator'])) {
            if ($params['search']['operator'] === 'schedule') {
                $query = $query->where('operator', 'schedule');
            } else {
//                $query = $query->leftJoin('user', 'user.user_id', '=', 'elastic_search_operation_log.operator');

                // 用户id/姓名/拼音/首字母 相似
                $query = $query->where('user.user_id', $params['search']['operator'])
                    ->orWhere('user.user_name_py','like', $params['search']['operator'])
                    ->orWhere('user.user_name_zm','like', $params['search']['operator'])
                    ->orWhere('user.user_name','like', $params['search']['operator']);
            }

            unset($params['search']['operator']);
        }

        // 按照分类查询
        if (isset($params['search']['config_type'])) {
            $query = $query->where('config_type', $params['search']['config_type']);
            unset($params['search']['config_type']);
        }

        return $query;
    }
}