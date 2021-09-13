<?php
namespace App\EofficeApp\Project\Entities;

use Illuminate\Database\Eloquent\SoftDeletes;

class ProjectDocumentEntity extends ProjectBaseEntity {

    use SoftDeletes;
    
     /** @var string $table 定义实体表 */
    public $table = 'project_document';
    
    /** @var string $primaryKey 定义实体表主键 */
    public $primaryKey = 'doc_id';
 
     /** @var string $dates 定义删除日期字段 */
    protected $dates = ['deleted_at'];

    protected $inQuery = ['doc_project'];
    protected $eqQuery = ['doc_creater', 'doc_project', 'doc_name', 'doc_creattime', 'dir_id'];
    protected $withQuery = ['dir'];

    protected $fillable = [
        'doc_project',
        'doc_file',
        'doc_name',
        'doc_explain',
        'doc_creater',
        'doc_creattime',
        'dir_id',
    ];

    public function setDirIdAttribute($value)
    {
        $value = intval($value);
        $this->attributes['dir_id'] = $value ? $value : 1;
    }

    public function setDocExplainAttribute($value)
    {
        $this->attributes['doc_explain'] = $value ?? '';
    }

    public function dir()
    {
        return $this->belongsTo(ProjectDocumentDirEntity::class, 'dir_id', 'dir_id');
    }
}
