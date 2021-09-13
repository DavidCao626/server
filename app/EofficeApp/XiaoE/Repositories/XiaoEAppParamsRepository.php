<?php

namespace App\EofficeApp\XiaoE\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\XiaoE\Entities\XiaoEAppParamsEntity;

class XiaoEAppParamsRepository extends BaseRepository
{

    public function __construct(XiaoEAppParamsEntity $entity)
    {
        parent::__construct($entity);
    }

    /**
     * 获取意图配置
     */
    public function getIntentionConfig($params)
    {
        return $this->getGeneralList($params);
    }

    /**
     * 获取意图配置
     */
    public function getIntentionConfigTotal($params)
    {
        return $this->getGeneralTotal($params);
    }

    /**
     * 通用的列表查询方法封装下
     * @param $params
     * @return mixed
     */
    private function getGeneralList($params)
    {
        $params = $this->mergeDefaultParams($params);
        return $this->entity->select($params['fields'])
            ->wheres($params['search'])
            ->orders($params['order_by'])
            ->parsePage($params['page'], $params['limit'])
            ->get();
    }

    private function getGeneralTotal($params)
    {
        $params = $this->mergeDefaultParams($params);
        return $this->entity->wheres($params['search'])->count();
    }

    /**
     * 合并默认查询配置
     * @param $params
     * @return array
     */
    private function mergeDefaultParams($params)
    {
        $defaultParams = [
            'fields' => ['*'],
            'page' => 0,
            'limit' => config('eoffice.pagesize'),
            'order_by' => ['id' => 'asc'],
            'search' => []
        ];
        $params = array_merge($defaultParams, $params);
        return $params;
    }

    /**
     *
     * @param int $id
     * @param bool $withTrashed
     * @return array
     */
    public function getIntentionDetail($key)
    {
        return $this->entity
            ->where('intention_key', $key)
            ->first();
    }
}
