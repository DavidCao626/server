<?php
namespace App\EofficeApp\Project\Entities;

use Illuminate\Database\Eloquent\SoftDeletes;

class ProjectDocumentDirEntity extends ProjectBaseEntity {

    use SoftDeletes;
    
    public $table = 'project_document_dir';
    
    public $primaryKey = 'dir_id';
 
    protected $dates = ['deleted_at'];

    protected $inQuery = ['dir_project'];
    protected $eqQuery = ['creator', 'dir_project', 'dir_name', 'parent_id'];

    protected $fillable = [
        'dir_name',
        'dir_project',
        'parent_id',
        'sort',
        'creator',
    ];

    public function sonDir()
    {
        return $this->hasMany(self::class, 'parent_id', 'dir_id');
    }

    public function documents()
    {
        return $this->hasMany(ProjectDocumentEntity::class, 'dir_id', 'dir_id');
    }

    public function getDirNameAttribute($value)
    {
        if ($this->attributes['dir_id'] == 1) {
            return trans('project.public_dir_name');
        } else {
            return $value;
        }
    }

    public function setSortAttribute($value)
    {
        $value = intval($value);
        $value = $value ?: 1;
        $this->attributes['sort'] = $value;
    }
}
