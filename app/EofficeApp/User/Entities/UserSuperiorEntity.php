<?php
namespace App\EofficeApp\User\Entities;
use App\EofficeApp\Base\BaseEntity;
/**
 * Description of UserSuperiorEntity
 *
 * @author lizhijun
 */
class UserSuperiorEntity extends BaseEntity 
{
     /**
     * 用户状态数据表
     *
     * @var string
     */
    public $table = 'user_superior';

    /**
     * 主键
     *
     * @var string
     */
    public $primaryKey = 'superior_id';
}
