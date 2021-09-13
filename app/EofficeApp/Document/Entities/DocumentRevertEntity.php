<?php
namespace App\EofficeApp\Document\Entities;

use App\EofficeApp\Base\BaseEntity;
/**
 * 文档回复实体类
 * 
 * @author 李志军
 * 
 * @since 2015-11-02
 */
class DocumentRevertEntity extends BaseEntity
{
	public $primaryKey		= 'revert_id';
	
	public $table 			= 'document_revert';

    public function revertHasOneUser()
    {
        return  $this->HasOne('App\EofficeApp\User\Entities\UserEntity','user_id','user_id');
    }

	/**
     * 文档回复关联子回复
     *
     * @return object
     */
    public function firstRevertHasManyRevert()
    {
        return $this->HasMany('App\EofficeApp\Document\Entities\DocumentRevertEntity','revert_parent','revert_id');
    }

    /**
     * 文档回复关联引用回复
     *
     * @return object
     */
    public function revertHasOneBlockquote()
    {
        return $this->HasOne('App\EofficeApp\Document\Entities\DocumentRevertEntity','revert_id','blockquote');
    }
}
