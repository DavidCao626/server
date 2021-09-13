<?php

namespace App\EofficeApp\Project\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * 项目讨论实体
 * 
 * @author:喻威
 * 
 * @since：2015-10-19
 * 
 */
class ProjectStatusEntity extends ProjectBaseEntity {
    //use SoftDeletes;

    /** @var string $table 定义实体表 */
    public $table = 'project_status';

    /** @var string $primaryKey 定义实体表主键 */
    public $primaryKey = 'id';

    protected $eqQuery = ['type', 'relation_id', 'participant', 'remind_flag'];
}
