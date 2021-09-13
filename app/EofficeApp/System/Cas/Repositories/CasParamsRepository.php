<?php

namespace App\EofficeApp\System\Cas\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\System\Cas\Entities\CasParamsEntity;

/**
 * casParams Repository类:提供cas_params 表操作资源
 *
 * @author 缪晨晨
 *
 * @since  2018-01-29 创建
 */
class CasParamsRepository extends BaseRepository
{

    public function __construct(CasParamsEntity $entity)
    {
        parent::__construct($entity);
    }

    /**
     * 【组织架构同步】 清空cas参数表全部内容
     *
     * @param
     *
     * @return boolean
     *
     * @author 缪晨晨
     *
     * @since  2018-01-29 创建
     */
    public function truncateCasParamsTable()
    {
        return $this->entity->truncate();
    }

    /**
     * 【组织架构同步】 获取cas认证配置参数
     *
     * @param
     *
     * @return array
     *
     * @author 缪晨晨
     *
     * @since  2018-01-29 创建
     */
    public function getCasParams()
    {
        return $this->entity->get();
    }
}
