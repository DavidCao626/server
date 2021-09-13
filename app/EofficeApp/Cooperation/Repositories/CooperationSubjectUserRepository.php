<?php
namespace App\EofficeApp\Cooperation\Repositories;

use App\EofficeApp\Cooperation\Entities\CooperationSubjectUserEntity;
use App\EofficeApp\Base\BaseRepository;

/**
 * 协作区分类表表知识库
 *
 * @author 丁鹏
 *
 * @since  2015-10-16 创建
 */
class CooperationSubjectUserRepository extends BaseRepository
{
    public function __construct(CooperationSubjectUserEntity $entity)
    {
        parent::__construct($entity);
    }
}
