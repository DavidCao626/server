<?php
namespace App\EofficeApp\Flow\Entities;

use App\EofficeApp\Base\BaseEntity;
/**
 * 流程会签表实体
 *
 * @author 丁鹏
 *
 * @since  2015-10-16 创建
 */
class FlowCountersignEntity extends BaseEntity
{
    /**
     * 表名
     *
     * @var string
     */
	public $table = 'flow_countersign';

    /**
     * 主键
     *
     * @var string
     */
    public $primaryKey = 'countersign_id';

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
     * 关联 用户表
     *
     * @author 丁鹏
     *
     * @since  2015-11-05 创建
     *
     * @return [object]               [关联关系]
     */
    public function countersignUser()
    {
        return $this->hasOne('App\EofficeApp\User\Entities\UserEntity','user_id','countersign_user_id');
    }
}
