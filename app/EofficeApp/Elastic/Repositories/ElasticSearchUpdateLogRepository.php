<?php


namespace App\EofficeApp\Elastic\Repositories;


use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Elastic\Configurations\Constant;
use App\EofficeApp\Elastic\Entities\ElasticSearchConfigEntity;
use App\EofficeApp\Elastic\Entities\ElasticSearchUpdateLogEntity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ElasticSearchUpdateLogRepository extends BaseRepository
{
    /**
     * 全站搜索更新记录
     *
     * @param \App\EofficeApp\Elastic\Entities\ElasticSearchUpdateLogEntity $entity
     *
     */
    public function __construct(ElasticSearchUpdateLogEntity $entity )
    {
        parent::__construct($entity);
    }

    /**
     * 根据key和type获取指定对象
     *
     * @param string $key
     * @param string $type
     * @param bool $isCreated
     *
     * @return  ElasticSearchConfigEntity |null
     */
    public function getConfigByKey($key, $type = '', $isCreated = true)
    {
        if ($isCreated) {
            $config = $type ?
                $this->entity->firstOrCreate(['key' => $key, 'type' => $type]) :
                $this->entity->firstOrCreate(['key' => $key]);
        } else {
            $this->entity->where('key', $key);
            if ($type) {
                $this->entity->where('type', $type);
            }
            $config = $this->entity->first();
        }

        return $config;
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
     * 获取更新日志数量
     *
     * @param array $params
     *
     * @return int
     */
    public function getUpdateRecordLogCount($params): int
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
    public function getUpdateRecordLogList($params): array
    {
        $default = [
            'page' 		=> 0,
            'order_by' 	=> ['elastic_search_update_log.log_time' => 'desc'],
            'limit'		=> 10,
            'fields'	=> ['log_time','user.user_name','operator','category','log_content']
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
     * @param ElasticSearchUpdateLogEntity $query
     * @param array $params
     *
     * @return ElasticSearchUpdateLogEntity
     */
    private function getParseWhere(ElasticSearchUpdateLogEntity $query, $params)
    {
        $query = $query->leftJoin('user', 'user.user_id', '=', 'elastic_search_update_log.operator');
        // 若查询操作人为用户ID 则连接 user 查询
        if (isset($params['search']['operator'])) {
            if ($params['search']['operator'] === 'schedule') {
                $query = $query->where('operator', 'schedule');
            } else {
//                $query = $query->leftJoin('user', 'user.user_id', '=', 'elastic_search_update_log.operator');

                // 用户id/姓名/拼音/首字母 相似
                $query = $query->where('user.user_id', $params['search']['operator'])
                               ->orWhere('user.user_name_py','like', $params['search']['operator'])
                               ->orWhere('user.user_name_zm','like', $params['search']['operator'])
                               ->orWhere('user.user_name','like', $params['search']['operator']);
            }

            unset($params['search']['operator']);
        }

        // 按照分类查询
        if (isset($params['search']['category'])) {
            $query = $query->where('category', $params['search']['category']);
            unset($params['search']['category']);
        }

        return $query;
    }

    /**
     * 获取各类型索引更新时间
     *
     * @return array
     */
    public function getIndexUpdateTimeByManual(): array
    {
        // 找出按分类分组后最新记录的id
        $ids = "SELECT MAX(id) FROM elastic_search_update_log WHERE operator != 'schedule' AND category != 'all' GROUP BY category";
        // 查出这些id对应的信息
        $SQL = "SELECT log_time,category FROM elastic_search_update_log WHERE id IN ($ids)";

        $items = DB::select($SQL);

        $responseData = [];
        foreach ($items as $item) {
            $responseData[$item->category] = $item->log_time;
        }

        $allIndices = array_values(Constant::$allIndices);
        $initIndices = array_fill_keys($allIndices, '');
        $responseData = array_merge($initIndices, $responseData);

        return $responseData;
    }
}