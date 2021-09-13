<?php

namespace App\EofficeApp\FlowModeling\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\FlowModeling\Entities\FlowModelingEntity;

/**
 * 流程建模
 *
 * @author:缪晨晨
 *
 * @since：2018-02-28
 *
 */
class FlowModelingRepository extends BaseRepository
{

    public function __construct(
        FlowModelingEntity $entity
    ) {
        parent::__construct($entity);
    }

    /**
     * 获取模块树
     *
     * @author 缪晨晨
     *
     * @param  array $where [查询条件]
     *
     * @since  2018-02-28 创建
     *
     * @return array     返回结果
     */
    public function getFlowModuleTree($where)
    {
        return $this->entity->select(['*'])->where($where)->get()->toArray();
    }

    /**
     * 查询模块树
     *
     * @author 缪晨晨
     *
     * @param  array $param [查询条件]
     *
     * @since  2018-02-28 创建
     *
     * @return array     返回结果
     */
    public function searchFlowModuleTree($param)
    {
        $default = [
            'search' => [],
        ];

        $param = array_merge($default, array_filter($param));
        return $this->entity->multiWheres($param['search'])->where('module_parent', '!=', '0')->get()->toArray();
    }

    /**
     * 获取带条件的模块
     *
     * @author 缪晨晨
     *
     * @param  array $where [查询条件]
     *
     * @since  2018-02-28 创建
     *
     * @return array     返回结果
     */
    public function getModuleByWhere($where, $fields = ["*"])
    {
        return $this->entity->select($fields)->wheres($where)->get()->toArray();
    }

}
