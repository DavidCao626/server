<?php 

namespace App\EofficeApp\System\Company\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * 公司信息Entity类:提供公司信息数据表实体
 *
 * @author qishaobo
 *
 * @since  2015-10-20 创建
 */
class CompanyEntity extends BaseEntity
{
    /**
     * 公司信息数据表
     *
     * @var string
     */
	public $table = 'company_info';

    /**
     * 主键
     *
     * @var string
     */    
    public $primaryKey = 'company_id';

    /**
     * 软删除,应该被调整为日期的属性
     *
     * @var array
     */    
    public $dates = ['deleted_at'];

    /**
     * 可以被批量赋值的属性.
     *
     * @var array
     */
    protected $fillable = ['company_name', 'phone_number', 'fax_number', 'zip_code', 'address', 'website', 'email', 'bank_name', 'bank_no', 'lang'];
 }