<?php
namespace App\EofficeApp\Document\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Document\Entities\DocumentShareEntity;
/**
 * 文档共享资源库类
 *
 * @author 李志军
 *
 * @since 2015-11-02
 */
class DocumentShareRepository extends BaseRepository
{
	/**
	 * 注册文档共享实体
	 *
	 * @param \App\EofficeApp\Document\Entities\DocumentShareEntity $entity
	 *
	 * @author 李志军
	 *
	 * @since 2015-11-02
	 */
	public function __construct(DocumentShareEntity $entity)
	{
		parent::__construct($entity);
	}
	/**
	 * 判断文档是否有共享权限
	 *
	 * @param int $documentId
	 *
	 * @return int
	 *
	 * @author 李志军
	 *
	 * @since 2015-11-02
	 */
	public function hasSharePurview($documentId, $own)
	{
		return $this->entity
			->where('document_id', $documentId)
			->where(function($query) use($own) {
				$query->where('share_all',1)
					->orWhere(function ($query) use($own){
                        $query->orWhereRaw("FIND_IN_SET(?, share_user)", [$own['user_id']])->orWhereRaw("FIND_IN_SET(?, share_dept)", [$own['dept_id']]);
                        foreach($own['role_id'] as $roleId){
                            $query->orWhereRaw("FIND_IN_SET(?, share_role)", [$roleId]);
                        }
					});
			})->count();
	}
	/**
	 * 判断某文件夹下是否有共享文档
	 *
	 * @param int $folderId
	 *
	 * @return int
	 *
	 * @author 李志军
	 *
	 * @since 2015-11-02
	 */
	public function  hasShareDocumentOfFolder($folderId, $own)
	{
		return $this->entity
			->where('folder_id', $folderId)
			->where(function($query) use($own){
				$query->where('share_all',1)
					->orWhere(function ($query) use($own){
                        $query->orWhereRaw("FIND_IN_SET(?, share_user)", [$own['user_id']])->orWhereRaw("FIND_IN_SET(?, share_dept)", [$own['dept_id']]);
                        foreach($own['role_id'] as $roleId){
                            $query->orWhereRaw("FIND_IN_SET(?, share_role)", [$roleId]);
                        }
					})
                    ->where(function ($query) {
                        $query->where('share_status', 0)->orWhere('share_end_time', '>', date('Y-m-d H:i:s'));
                    });
			})->count();
	}
    public function  hasShareDocumentInFolder($folderId, $own)
    {
        return $this->entity
			->whereIn('folder_id', $folderId)
			->where(function($query) use($own){
				$query->where('share_all',1)
					->orWhere(function ($query) use($own){
                        $query->orWhereRaw("FIND_IN_SET(?, share_user)", [$own['user_id']])->orWhereRaw("FIND_IN_SET(?, share_dept)", [$own['dept_id']]);
                        foreach($own['role_id'] as $roleId){
                            $query->orWhereRaw("FIND_IN_SET(?, share_role)", [$roleId]);
                        }
					});
			})->count();
    }
	public function getAllShareDocumentFolder($own)
	{
		return $this->entity
			->select(['folder_id'])
			->where(function($query) use($own) {
				$query->where('share_all',1)
					->orWhere(function ($query) use($own){
                        $query->orWhereRaw("FIND_IN_SET(?, share_user)", [$own['user_id']])->orWhereRaw("FIND_IN_SET(?, share_dept)", [$own['dept_id']]);
                        foreach($own['role_id'] as $roleId){
                            $query->orWhereRaw("FIND_IN_SET(?, share_role)", [$roleId]);
                        }
					});
			})->groupBy('folder_id')->get()->toArray();
	}
	/**
	 * 获取共享文档列表
	 *
	 * @param array $param
	 *
	 * @return array 共享文档列表
	 *
	 * @author 李志军
	 *
	 * @since 2015-11-02
	 */
	public function getAllShareDocuments($param, $own)
	{
		return $this->entity
					->select(['document_id'])
					->where(function($query) use($own){
						$query->where('share_all',1)
							->orWhere(function ($query) use($own){
                                $query->orWhereRaw("FIND_IN_SET(?, share_user)", [$own['user_id']])->orWhereRaw("FIND_IN_SET(?, share_dept)", [$own['dept_id']]);
                                foreach($own['role_id'] as $roleId){
                                    $query->orWhereRaw("FIND_IN_SET(?, share_role)", [$roleId]);
                                }
							});
				})->get();
	}
	public function getShareDocumentsByFolder($folderIds, $own)
	{
		if(empty($folderIds)){
			return [];
		}
		return $this->entity
					->select(['document_id'])
					->whereIn('folder_id',$folderIds)
					->where(function($query) use($own){
						$query->where('share_all',1)
							->orWhere(function ($query) use($own){
                                $query->orWhereRaw("FIND_IN_SET(?, share_user)", [$own['user_id']])->orWhereRaw("FIND_IN_SET(?, share_dept)", [$own['dept_id']]);
                                foreach($own['role_id'] as $roleId){
                                    $query->orWhereRaw("FIND_IN_SET(?, share_role)", [$roleId]);
                                }
                            })
                            ->where(function ($query) {
                                $query->where('share_status', 0)->orWhere('share_end_time', '>', date('Y-m-d H:i:s'));
                            });
				})->get();
	}
	/**
	 * 判断共享文档是否存在
	 *
	 * @param int $documentId
	 *
	 * @return int
	 *
	 * @author 李志军
	 *
	 * @since 2015-11-02
	 */
	public function shareDocumentExists($documentId)
	{
		return $this->entity->where('document_id', $documentId)->count();
	}

	public function getDocumentShareMember($documentId, $folderType=1)
	{
        if ($folderType == 5) {
            return $this->entity->where(['document_id'=>$documentId])->whereRaw('created_at != updated_at')->first();
        } else {
            return $this->entity->where(['document_id'=>$documentId])->first();
        }
    }
    // 取消到期共享
    public function cancelShareDocument() {
        $currentTime = date("Y-m-d H:i:s");
        return $this->entity->where('share_status', 1)->where('share_end_time', '<', $currentTime)->delete();
    }
}
