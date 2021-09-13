<?php

namespace App\EofficeApp\Empower\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Empower\Entities\ModuleVerifyAuthEntity;

/**
 * 模块授权资源库
 *
 * @author qishaobo
 *
 * @since 2017-03-17
 *
 */
class ModuleVerifyAuthRepository extends BaseRepository {

    public function __construct(ModuleVerifyAuthEntity $entity) {
        parent::__construct($entity);
    }

    /**
     * 获取模块授权
     *
     * @param
     *
     * @return object
     *
     * @author qishaobo
     *
     * @since  2017-03-17
     */
    public function getModulesVerify($where)
    {
        return $this->entity
        ->wheres($where)
        ->get()
        ->toArray();
    }

}
