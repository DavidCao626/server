<?php
namespace App\EofficeApp\Flow\Entities;

use App\EofficeApp\Base\BaseEntity;
use Illuminate\Database\Eloquent\SoftDeletes;
/**
 * 流程表单模板，模板规则表
 *
 * @author 丁鹏
 *
 * @since  2015-10-16 创建
 */
class FlowFormTemplateRuleEntity extends BaseEntity
{

    /**
     * 应该被调整为日期的属性
     *
     * @var array
     */
    protected $dates = ['deleted_at'];
    /**
     * 表名
     *
     * @var string
     */
	public $table = 'flow_form_template_rule';

    protected $hidden = [
        'updated_at','created_at','deleted_at'
    ];

    /**
     * 主键
     *
     * @var string
     */
    public $primaryKey = 'rule_id';

    /**
     * 默认排序
     *
     * @var string
     */
	public $sort = 'asc';

    /**
     * 默认每页条数
     *
     * @var int
     */
	public $perPage = 10;

    /**
     * 表单模板的规则，对应范围内的用户用户
     *
     * @return object
     *
     * @since  2015-11-18
     */
    public function ruleListHasManyUser()
    {
        return  $this->HasMany('App\EofficeApp\Flow\Entities\FlowFormTemplateRuleUserEntity','rule_id','rule_id');
    }

    /**
     * 表单模板的规则，对应范围内的用户角色
     *
     * @return object
     *
     * @since  2015-11-18
     */
    public function ruleListHasManyRole()
    {
        return  $this->HasMany('App\EofficeApp\Flow\Entities\FlowFormTemplateRuleRoleEntity','rule_id','rule_id');
    }

    /**
     * 表单模板的规则，对应范围内的用户部门
     *
     * @return object
     *
     * @since  2015-11-18
     */
    public function ruleListHasManyDept()
    {
        return  $this->HasMany('App\EofficeApp\Flow\Entities\FlowFormTemplateRuleDepartmentEntity','rule_id','rule_id');
    }

}
