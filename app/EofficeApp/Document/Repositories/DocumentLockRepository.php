<?php
namespace App\EofficeApp\Document\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Document\Entities\DocumentLockEntity;
/**
 * 文档锁定资源库类
 * 
 * @author 李志军
 * 
 * @since 2015-11-02
 */
class DocumentLockRepository extends BaseRepository
{
	
	public function __construct(DocumentLockEntity $entity)
	{
		parent::__construct($entity);
	}

	public function lockExists($documentId)
	{
		$count = $this->entity->where('document_id',$documentId)->count();

		return $count == 0 ? false : true;
	}	

	public function documentLockInfo($documentId)
	{
		return $this->entity->where('document_id',$documentId)->first();
	}
}
