<?php
namespace App\EofficeApp\Cooperation\Entities;

use App\EofficeApp\Base\BaseEntity;

/**
 * 协作区回复表实体
 *
 * @author 丁鹏
 *
 * @since  2015-10-16 创建
 */
class CooperationRevertEntity extends BaseEntity
{
    /**
     * 协作区回复表
     *
     * @var string
     */
	public $table = 'cooperation_revert';

    /**
     * 主键
     *
     * @var string
     */
    public $primaryKey = 'revert_id';

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
     * 回复对应人员
     *
     * @method hasOneDept
     *
     * @return boolean    [description]
     */
    public function revertHasOneUser()
    {
        return  $this->HasOne('App\EofficeApp\User\Entities\UserEntity','user_id','revert_user');
    }

    /**
     * 协作回复关联自己，查父级回复数据
     *
     * @return object
     */
    public function revertHasOneRevert()
    {
        return $this->HasOne('App\EofficeApp\Cooperation\Entities\CooperationRevertEntity','revert_id','revert_parent');
    }

    /**
     * 协作回复关联子回复
     *
     * @return object
     */
    public function firstRevertHasManyRevert()
    {
        return $this->HasMany('App\EofficeApp\Cooperation\Entities\CooperationRevertEntity','revert_parent','revert_id');
    }

    /**
     * 协作回复关联引用回复
     *
     * @return object
     */
    public function revertHasOneBlockquote()
    {
        return $this->HasOne('App\EofficeApp\Cooperation\Entities\CooperationRevertEntity','revert_id','blockquote');
    }
    /**
     * 协作有多条回复
     *
     * @return object
     */
    public function subjectHasManyRevert()
    {
        return $this->HasOne('App\EofficeApp\Cooperation\Entities\CooperationRevertEntity','subject_id','subject_id');
    }
}
