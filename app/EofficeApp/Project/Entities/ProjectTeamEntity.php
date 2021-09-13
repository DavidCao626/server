<?php

namespace App\EofficeApp\Project\Entities;

/**
 * 项目团队
 * 
 * @author:喻威
 * 
 * @since：2015-10-19
 * 
 */
class ProjectTeamEntity extends ProjectBaseEntity {
    
    //use SoftDeletes;
    
     /** @var string $table 定义实体表 */
    public $table = 'project_team';
    
    /** @var string $primaryKey 定义实体表主键 */
    public $primaryKey = 'team_id';
 
     /** @var string $dates 定义删除日期字段 */
    protected $dates = ['deleted_at'];

    protected $inQuery = ['team_project'];
}
