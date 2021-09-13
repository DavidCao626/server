<?php

namespace App\EofficeApp\Assets\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Assets\Entities\AssetsRuleSettingEntity;

/**
 * 资产类型列表
 *
 * @author zw
 *
 * @since  2018-03-30 创建
 */
class AssetsRuleSettingRepository extends BaseRepository
{

    public function __construct(AssetsRuleSettingEntity $entity)
    {
        parent::__construct($entity);
    }

    /**
     * 资产条码生成
     *
     * @param  array
     *
     * @return bool|object
     *
     * @author ZW
     *
     * @since  2018-03-29
     */
    public function getData(){
        return $this->entity->get()->first();
    }

}