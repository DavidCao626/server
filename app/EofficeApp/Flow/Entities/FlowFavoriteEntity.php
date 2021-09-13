<?php
namespace App\EofficeApp\Flow\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * 流程--我的收藏表实体
 *
 * @author 丁鹏
 *
 * @since  2015-10-16 创建
 */
class FlowFavoriteEntity extends BaseEntity
{
    /**
     * 表名
     *
     * @var string
     */
	public $table = 'flow_favorite';

    /**
     * 主键
     *
     * @var string
     */
    public $primaryKey = 'flow_favorite';

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
     * 一个收藏关联一个flow_type
     *
     * @return object
     *
     * @since  2015-11-18
     */
    public function flowFavoriteHasOneFormType()
    {
        return  $this->HasOne('App\EofficeApp\Flow\Entities\FlowTypeEntity','flow_id','flow_id');
    }

    /**
     * 一个收藏关联多个flow_run
     *
     * @return object
     *
     * @since  2015-11-18
     */
    public function flowFavoriteHasManyFlowRun()
    {
        return  $this->HasMany('App\EofficeApp\Flow\Entities\FlowRunEntity','flow_id','flow_id');
    }
}
