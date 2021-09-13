<?php
namespace App\EofficeApp\Auth\Entities;

use App\EofficeApp\Base\BaseEntity;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\SoftDeletes;
/**
 * 用户实体
 *
 * @author lizhijun
 *
 * @since  2015-10-16 创建
 */
class AuthEntity extends BaseEntity implements Authenticatable
{
    use SoftDeletes;
     /**
     * 应该被调整为日期的属性
     *
     * @var array
     */
    protected $dates = ['deleted_at'];
    
    /**
     * 用户数据表
     *
     * @var string
     */
	public $table = 'user';

    /**
     * 主键
     *
     * @var string
     */
    public $primaryKey = 'user_id';
    public $casts = ['user_id' => 'string'];

	public function getAuthIdentifier() {

    }

    public function getAuthIdentifierName() {

	}

	public function getAuthPassword() {

	}

	public function getRememberToken() {

	}

	public function getRememberTokenName() {

	}

	public function setRememberToken($value) {

	}

}
