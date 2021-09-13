<?php
namespace App\EofficeApp\Project\Entities;

use App\EofficeApp\User\Services\UserService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProjectRoleUserGroupEntity extends ProjectBaseEntity {
    
    public $table = 'project_role_user_group';

    public $primaryKey = 'id';

    protected $eqQuery = ['role_id', 'type', 'type_value'];

    protected $fillable = [
        'role_id',
        'type', // 用户组类型：user用户、dept部门、role角色、relationship关联关系
        'type_value' // 类型对应的值
    ];

    const relationPenetrateKey = 'relation_penetrate';
    const allValue = [
        1, // 本人
        2, // 上级
        3, // 所有上级
        4, // 部门负责人
        5, // 同部门人员
        6 // 同角色人员
    ]; // 数据穿透的全部值

    public static function getAllRelationPenetrateValue()
    {
        return static::allValue;
    }

}
