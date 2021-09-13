<?php
namespace App\EofficeApp\Cooperation\Repositories;

use App\EofficeApp\Cooperation\Entities\CooperationSortMemberUserEntity;
use App\EofficeApp\Base\BaseRepository;

/**
 * 协作区分类表表知识库
 *
 * @author 丁鹏
 *
 * @since  2015-10-16 创建
 */
class CooperationSortMemberUserRepository extends BaseRepository
{
    public function __construct(CooperationSortMemberUserEntity $entity)
    {
        parent::__construct($entity);
    }
}
