<?php
namespace App\EofficeApp\Cooperation\Repositories;

use App\EofficeApp\Cooperation\Entities\CooperationSubjectDepartmentEntity;
use App\EofficeApp\Base\BaseRepository;

/**
 * 协作区分类表表知识库
 *
 * @author 丁鹏
 *
 * @since  2015-10-16 创建
 */
class CooperationSubjectDepartmentRepository extends BaseRepository
{
    public function __construct(CooperationSubjectDepartmentEntity $entity)
    {
        parent::__construct($entity);
    }
}
