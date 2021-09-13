<?php

namespace App\EofficeApp\PublicGroup\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\PublicGroup\Entities\PublicGroupMemberEntity;

/**
 * 公共用户组 资源库
 *
 * @author:喻威
 *
 * @since：2015-10-19
 *
 */
class PublicGroupMemberRepository extends BaseRepository {

    private $user_id;
    private $role_id;
    private $dept_id;

    public function __construct(PublicGroupMemberEntity $entity) {
        parent::__construct($entity);
    }

}
