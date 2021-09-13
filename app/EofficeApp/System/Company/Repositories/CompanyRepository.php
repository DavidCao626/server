<?php

namespace App\EofficeApp\System\Company\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\System\Company\Entities\CompanyEntity;

/**
 * 公司信息Repository类:提供公司信息表操作资源
 *
 * @author qishaobo
 *
 * @since  2015-10-20 创建
 */
class CompanyRepository extends BaseRepository
{
    public function __construct(CompanyEntity $entity)
    {
        parent::__construct($entity);
    }

    /**
     * 查询公司信息详情
     *
     * @param  array $where 查询条件
     *
     * @return array
     *
     * @author qishaobo
     *
     * @since  2015-10-20 创建
     */
    public function getCompanyDetail($where = [])
    {
        return $this->entity->wheres($where)->first();
    }

}