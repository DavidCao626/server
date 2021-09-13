<?php

namespace App\EofficeApp\Empower\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Empower\Entities\VersionEntity;

/**
 * 系统版本资源库
 *
 * @author qishaobo
 *
 * @since 2017-03-17
 *
 */
class VersionRepository extends BaseRepository {

    public function __construct(VersionEntity $entity) {
        parent::__construct($entity);
    }

    /**
     * 获取系统版本
     *
     * @param
     *
     * @return object
     *
     * @author qishaobo
     *
     * @since  2017-03-17
     */
    public function getVersion()
    {
        return $this->entity->limit(1)->get()->toArray();
    }
}
