<?php

namespace App\EofficeApp\Project\Entities;

use App\EofficeApp\Base\BaseEntity;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 项目模板实体
 * 
 * @author:喻威
 * 
 * @since：2015-10-19
 * 
 */
class ProjectTemplateEntity extends BaseEntity {
    //use SoftDeletes;

    /** @var string $table 定义实体表 */
    public $table = 'project_template';

    /** @var string $primaryKey 定义实体表主键 */
    public $primaryKey = 'template_id';

    /** @var string $dates 定义删除日期字段 */
    protected $dates = ['deleted_at'];

}
