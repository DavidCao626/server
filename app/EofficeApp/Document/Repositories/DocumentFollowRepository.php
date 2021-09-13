<?php
namespace App\EofficeApp\Document\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Document\Entities\DocumentFollowEntity;
/**
 * 文档关注资源库类
 * 
 * @author 李志军
 * 
 * @since 2015-11-02
 */
class DocumentFollowRepository extends BaseRepository
{
	
	public function __construct(DocumentFollowEntity $entity)
	{
		parent::__construct($entity);
    }
    
    public function getFollowList($wheres) {
        return $this->entity->select("*")->wheres($wheres)->get();
    }
}
