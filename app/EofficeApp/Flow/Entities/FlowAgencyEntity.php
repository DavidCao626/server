<?php
namespace App\EofficeApp\Flow\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * 流程委托表实体
 *
 * @author 丁鹏
 *
 * @since  2015-10-16 创建
 */
class FlowAgencyEntity extends BaseEntity
{
    /**
     * 表名
     *
     * @var string
     */
	public $table = 'flow_agency';

    /**
     * 主键
     *
     * @var string
     */
    public $primaryKey = 'flow_agency_id';

    /**
     * 默认排序
     *
     * @var string
     */
	public $sort = 'desc';

    /**
     * 默认每页条数
     *
     * @var int
     */
	public $perPage = 10;

    /**
     * 一条委托，关联被委托人
     *
     * @author 丁鹏
     *
     * @return [type]                     [description]
     */
    function flowAgencyHasOneAgentUser()
    {
        return $this->hasOne('App\EofficeApp\User\Entities\UserEntity','user_id','agent_id');
    }

    /**
     * 一条委托，关联委托人
     *
     * @author 丁鹏
     *
     * @return [type]                     [description]
     */
    function flowAgencyHasOneByAgentUser()
    {
        return $this->hasOne('App\EofficeApp\User\Entities\UserEntity','user_id','by_agent_id');
    }

    /**
     * 一条委托，关联多条委托详细
     *
     * @author 丁鹏
     *
     * @since  2015-11-05 创建
     *
     * @return [object]               [关联关系]
     */
    public function flowAgencyHasManyFlowAgencyDetail()
    {
        return $this->hasMany('App\EofficeApp\Flow\Entities\FlowAgencyDetailEntity', 'flow_agency_id', 'flow_agency_id');
    }
}
