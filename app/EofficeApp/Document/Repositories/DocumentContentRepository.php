<?php
namespace App\EofficeApp\Document\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Document\Entities\DocumentContentEntity;
use App\EofficeApp\Document\Entities\DocumentShareEntity;
use Schema;
use DB;
/**
 * 文档内容资源库类
 *
 * @author 李志军
 *
 * @since 2015-11-02
 */
class DocumentContentRepository extends BaseRepository
{
	/** @var int 默认列表条数 */
	private $limit;

	/** @var int 默认列表页 */
	private $page		= 0;

	/** @var array  默认排序 */
	private $orderBy	= ['updated_at' => 'desc', 'document_id' => 'desc'];

    private $documentShareEntity;
	/**
	 * 注册文档内容实体
	 *
	 * @param \App\EofficeApp\Document\Entities\DocumentContentEntity $entity
	 *
	 * @author 李志军
	 *
	 * @since 2015-11-02
	 */
	public function __construct(
            DocumentContentEntity $entity,
            DocumentShareEntity $documentShareEntity
    )
	{
		parent::__construct($entity);

        $this->documentShareEntity = $documentShareEntity;

		$this->limit = config('eoffice.pagesize');
	}
	/**
	 * 根据文件夹获取文件数量
	 *
	 * @param int $folderId

	 * @return int 文件数量
	 *
	 * @author 李志军
	 *
	 * @since 2015-11-02
	 */
	public function getDocumentsCountByFolderId($folderId)
	{
		return $this->entity->where('folder_id', $folderId)->count();
	}
	/**
	 * 根据文件夹获取创建人文档数量
	 *
	 * @param int $folderId
	 *
	 * @return int 文档数量
	 *
	 * @author 李志军
	 *
	 * @since 2015-11-02
	 */
	public function hasCreatorDocumentOfFolder($folderId, $own)
	{
		return $this->entity->where('folder_id', $folderId)->where('creator', $own['user_id'])->count();
	}

	public function getAllCreatorDocumentFolder($currentUserId)
	{
		return $this->entity
			->select(['folder_id'])
			->where('creator', $currentUserId)
			->groupBy('folder_id')
			->get()->toArray();
	}
	public function getDocumentByFolderAndCreator($folderId,$userId)
	{
		return $this->entity->select('document_id')->where('folder_id',$folderId)->where('creator',$userId)->get();
	}
	/**
	 * 根据文件夹获取归档文档数量
	 *
	 * @param int $folderId
	 *
	 * @return int 归档文档数量
	 *
	 * @author 李志军
	 *
	 * @since 2015-11-02
	 */
	public function hasDocumentFromFlowOfFolder($folderId, $currentUserId)
	{
		return $this->entity
			->where('folder_id', $folderId)
			->where('source_id', '!=', 0)
			->whereRaw("FIND_IN_SET(?, flow_manager)", [$currentUserId])
			->count();
	}
	public function getAllFlowDocumentFolder($currentUserId)
	{
		return $this->entity
			->select(['folder_id'])
			->where('source_id', '!=', 0)
			->whereRaw("FIND_IN_SET(?, flow_manager)", [$currentUserId])
			->groupBy('folder_id')->get()->toArray();
	}
	public function listDocumentByDocumentIds($documentIds, $fields, $orders=[])
	{
		if(empty($fields)){
			$fields = [
                'document_content.document_id',
                'document_content.subject',
                'document_content.folder_type',
                'document_content.document_type',
                'document_content.folder_id',
                'document_content.creator',
                'document_content.created_at',
                'document_content.updated_at',
                'document_content.is_draft',
                'document_content.top',
                'document_content.file_type'
            ];
		}

		$fields[] = 'user.user_name as create_name';

        $fields[] = 'document_folder.folder_name';
        
        $orders = $orders ?? ['document_content.updated_at', 'desc'];

		return $this->entity
                ->select($fields)
                ->leftJoin('user', 'user.user_id', '=', 'document_content.creator')
                ->leftJoin('document_folder', 'document_folder.folder_id', '=', 'document_content.folder_id')
                ->whereIn('document_content.document_id',$documentIds)
                ->orders($orders)
                ->get();
	}
	/**
	 * 获取文档详情
	 *
	 * @param int $documentId
	 * @param array $fields
	 *
	 * @return object 文档详情
	 *
	 * @author 李志军
	 *
	 * @since 2015-11-02
	 */
	public function getDocumentInfo($documentId, $fields,$condition = '=')
    {
		$fields	= !empty($fields) ? $fields : ['*'];

		return $this->entity->select($fields)->where('document_id', $condition, $documentId)->first();
	}

    public function getDocumentInfoByRunId($runId, $fields,$condition = '=')
    {
        $fields	= !empty($fields) ? $fields : ['*'];

        return $this->entity->select($fields)->where('source_id', $condition, $runId)->where('folder_type', $condition, 5)->first();
    }

	public function getAccessDocumentId($documentId,$folderIds,$shareDocumentIds,$currentUserId,$condition = '>')
    {
		$query = $this->getQueryHeader($folderIds, $shareDocumentIds, $currentUserId);

		$query = $query->where('document_id', $condition, $documentId);

        $query = $condition == '>' ? $query->orderBy('document_id','ASC') : $query->orderBy('document_id','DESC');

		return $query->first();
	}
	/**
	 * 获取文档数量
	 *
	 * @param array $param
	 * @param array $folderIds
	 * @param array $shareDocumentIds
	 *
	 * @return 获取文档数量
	 *
	 * @author 李志军
	 *
	 * @since 2015-11-02
	 */
	public function getQueryHeader($folderIds, $shareDocumentIds, $currentUserId)
	{
		return  $this->entity->select(['document_id'])
			->where(function ($query) use($folderIds, $shareDocumentIds,$currentUserId){
				$query->where('creator', $currentUserId);//创建人文档

				if (!empty($folderIds)) {
					$query->orWhereIn('folder_id', $folderIds);//有查看权限文档
				}

				if (!empty($shareDocumentIds)) {
					$query->orWhereIn('document_id', $shareDocumentIds);//共享文档
				}
			});
	}
    /**
	 * 获取文档列表
	 *
	 * @param array $param
	 * @param array $folderIds
	 * @param array $shareDocumentIds
	 *
	 * @return array 文档列表
	 *
	 * @author 李志军
	 *
	 * @since 2015-11-02
	 */
    public function listDocument($param, $own, $showFolderId, $diffFolderId, $shareDocumentIds, $follow = 0, $myShared = 0, $otherShared = 0)
	{
		$param['limit']		= (isset($param['limit']) && $param['limit']) ? $param['limit'] : $this->limit;

		$param['page']		= (isset($param['page']) && $param['page']) ? $param['page'] : $this->page;

		$param['order_by']	= (isset($param['order_by']) && !empty($param['order_by'])) ? $param['order_by'] : $this->orderBy;
        
        if (isset($param['order_by']['created_at'])) {
            $param['order_by']['document_content.created_at'] = $param['order_by']['created_at'];
            unset($param['order_by']['created_at']);
        }
        if (isset($param['order_by']['updated_at'])) {
            $param['order_by']['document_content.updated_at'] = $param['order_by']['updated_at'];
            unset($param['order_by']['updated_at']);
        }

        if (!isset($param['order_by']['document_id'])) {
			$param['order_by']['document_content.document_id'] = 'desc';
        }

        $query = $this->queryCondition($this->queryHeader($own, $showFolderId, $diffFolderId, $shareDocumentIds, $follow, $myShared, $otherShared), $param);

        if (!$query) {
            return [];
        }

        return array_column($query->orders(['top' => 'desc', 'top_begin_time' => 'desc'])->orders($param['order_by'])->parsePage($param['page'], $param['limit'])->get()->toArray(),'document_id');
    }

    public function getDocumentCount($param, $own, $showFolderId, $diffFolderId, $shareDocumentIds, $follow = 0, $myShared, $otherShared)
    {
        $query = $this->queryCondition($this->queryHeader($own, $showFolderId, $diffFolderId, $shareDocumentIds, $follow, $myShared, $otherShared), $param);

        if (!$query) {
            return 0;
        }
        return $query->count();
    }

    private function queryCondition($query, $param)
    {
        if (!$query) {
            return false;
        }
        if(isset($param['search']['tag_id']) && !empty($param['search']['tag_id'])){
        	$query->leftJoin('document_tag', 'document_content.document_id', '=', 'document_tag.document_id');
        }

        if (isset($param['search']) && !empty($param['search'])) {
            $query->wheres($param['search']);
        }

        if (isset($param['orSearch']) &&  !empty($param['orSearch'])) {
            $size = sizeof($param['orSearch']);

            if($size == 1) {
                foreach ($param['orSearch'] as $k => $v) {
                    if ($k == 'attachment_name') {
                        if(isset($v[0]) && $v[0]) {
                            $query->whereIn('document_content.document_id',$v[0]);
                        }  else {
                            $query->whereIn('document_content.document_id',[]);
                        }
                    } else {
                        if($v[1] == 'like') {
                            $query->where($k,$v[1],'%' . $v[0] . '%');
                        } else {
                            $query->where($k,$v[1],$v[0]);
                        }
                    }
                }
            } else if($size >= 2) {
                $orSearch = $param['orSearch'];
                $query->where(function($query) use ($orSearch){
                    $count = 0;
                    foreach ($orSearch as $k => $v) {
                        if ($count > 0) {
                            if ($k == 'attachment_name') {
                                $query->orWhereIn('document_content.document_id',$v[0]);
                            } else {
                                if($v[1] == 'like') {
                                    $query->orWhere($k,$v[1],'%' . $v[0] . '%');
                                } else {
                                    $query->orWhere($k,$v[1],$v[0]);
                                }
                            }
                        } else {
                            if ($k == 'attachment_name') {
                                $query->whereIn('document_content.document_id',$v[0]);
                            } else {
                                if($v[1] == 'like') {
                                    $query->where($k,$v[1],'%' . $v[0] . '%');
                                } else {
                                    $query->where($k,$v[1],$v[0]);
                                }
                            }
                        }
                        $count ++;
                    }
                });
            }
        }

        return $query;
    }

    private function queryHeader($own, $showFolderId, $diffFolderId, $shareDocumentIds, $follow, $myShared, $otherShared)
    {
        $returnquery = $this->entity->select(['document_content.document_id']);
        // 关注的
        if ($follow) {
            $returnquery = $returnquery->leftJoin('document_follow', 'document_follow.document_id', 'document_content.document_id')
                            ->where('document_follow.user_id', $own['user_id']);
        }
        // 我分享的
        if ($myShared) {
            return $returnquery->leftJoin('document_share', 'document_share.document_id', 'document_content.document_id')
                        ->where(function($query)use($showFolderId, $diffFolderId, $own){
                            if (!empty($diffFolderId)) {
                                $query->where(function($query) use($own, $diffFolderId){
                                    // 没有查看权限的文件夹，但是是文档创建人
                                    $query->where('creator', $own['user_id'])
                                        ->whereIn('document_content.folder_id', $diffFolderId);
                                });
                            }
                            if (!empty($showFolderId)) {
                                $query->orWhere(function($query) use($own, $showFolderId){
                                    $query->where('creator', $own['user_id'])
                                        ->whereIn('document_content.folder_id', $showFolderId);
                                });
                            }
//                            $query->whereIn('document_content.folder_id', $showFolderId)
//                            ->where(function($query)use($own){
//                                $query->where('creator', $own['user_id']);
//                            });
                        })->where('from_user', $own['user_id'])->where('is_draft', 0)
                        ->where(function($query) {
                            $query->where('folder_type', 1)
                            ->orWhere(function($query) {
                                $query->where('folder_type', 5)
                                ->where(function($query) {
                                    $query->where('share_status', 0)
                                    ->orWhere(function($query) {
                                        $currentTime = date("Y-m-d H:i:s");
                                        $query->where('share_status', 1)->where('share_end_time', '>', $currentTime);
                                    });
                                // 流程归档再次分享后，属于分享中的 
                                })->whereRaw('document_share.created_at != document_share.updated_at');
                            });
                        });
        }
        // 他人分享
        if ($otherShared) {
            return $returnquery->leftJoin('document_share', 'document_share.document_id', 'document_content.document_id')
                        ->where('is_draft', 0)
                        ->whereIn('document_content.document_id', $shareDocumentIds)
                        ->where('from_user', '!=', $own['user_id'])
                        ->where(function($query) {
                            $query->where('folder_type', 1)
                            ->orWhere(function($query) {
                                $query->where('folder_type', 5)
                                ->where(function($query) {
                                    $query->where('share_status', 0)
                                    ->orWhere(function($query) {
                                        $currentTime = date("Y-m-d H:i:s");
                                        $query->where('share_status', 1)->where('share_end_time', '>', $currentTime);
                                    });
                                // 流程归档再次分享后，属于分享中的 
                                })->whereRaw('document_share.created_at != document_share.updated_at');
                            });
                        });
        }

		$returnquery = $returnquery->where(function ($query) use($showFolderId, $diffFolderId, $shareDocumentIds, $own){
				if (!empty($diffFolderId)) {
                    $query->where(function($query) use($own, $diffFolderId){
                    	// 没有查看权限的文件夹，但是是文档创建人
                        $query->where('creator', $own['user_id'])
                            ->whereIn('folder_id', $diffFolderId);
                    });
				}

                if (!empty($shareDocumentIds)) {
                    $query->orWhere(function ($query) use($shareDocumentIds) {
						// 获取当前人的草稿
					   $query->WhereIn('document_content.document_id', $shareDocumentIds)->where('is_draft', 0);
					});
				}

				if (!empty($showFolderId)) {
					$query->orWhere(function ($query) use($own, $showFolderId) {
				 		//有查看权限文档
						$query->WhereIn('folder_id', $showFolderId)->where('is_draft', 0);
					});
					$query->orWhere(function ($query) use($own, $showFolderId) {
						// 获取当前人的草稿
					   $query->WhereIn('folder_id', $showFolderId)->where('creator', $own['user_id'])->where('is_draft', 1);
					});
				}
            });

        return $returnquery;
    }

    public function getAttachmentCount($entityId)
    {
    	if(!Schema::hasTable('attachment_relataion_document_content')){
    		return 0;
    	}
        return DB::table('attachment_relataion_document_content')->where('entity_id',$entityId)->count();
    }

    public function attachmentCounts($documentIds) {
    	if(!Schema::hasTable('attachment_relataion_document_content')){
    		return [];
    	}
        $attachemnts = DB::table('attachment_relataion_document_content')->selectRaw('entity_id,count(entity_id) as attachment_count')->whereIn('entity_id',$documentIds)->groupBy('entity_id')->get();;
        $attachemnt = [];
        if(count($attachemnts) > 0){
            foreach ($attachemnts as $value){
                $attachemnt[$value->entity_id] = $value->attachment_count;
            }
        }
        return $attachemnt;
    }

    public function getNoPurDocCount($param)
    {
    	$query = $this->entity;

		if (!empty($param['search'])) {
			$query = $query->wheres($param['search']);
		}

		return $query->count();
    }

    public function listNoPurDoc($param, $flag=false)
    {
    	$param['fields']	= isset($param['fields']) ? $param['fields'] : ['document_id','subject','creator','folder_id','document_content.created_at'];

		$param['limit']		= isset($param['limit']) ? $param['limit'] : $this->limit;

		$param['page']		= isset($param['page']) ? $param['page'] : $this->page;

		$param['order_by']	= isset($param['order_by']) ? $param['order_by'] : $this->orderBy;

		if (!isset($param['order_by']['document_id'])) {
			$param['order_by']['document_id'] = 'desc';
		}

        $query = $this->entity->select($param['fields']);
        
        if ($flag) {
            $query = $query->leftJoin('user', 'user.user_id', '=', 'document_content.creator')
                ->leftJoin('document_folder', 'document_folder.folder_id', '=', 'document_content.folder_id');
        }

		if (isset($param['search']) && !empty($param['search'])) {
			$query = $query->wheres($param['search']);
        }

		return $query->orders($param['order_by'])
					->parsePage($param['page'], $param['limit'])
					->get();
    }
	/**
	 * 获取文档创建数量
	 *
	 * @return array
	 *
	 * @author niuxiaoke
	 *
	 * @since 2017-08-04
	 */
    public function getCreateCount($userIds, $where)
    {
    	$query = $this->entity->selectRaw('creator,count(*) as count')
    						  ->leftJoin('user', 'user.user_id', '=', 'document_content.creator')
    						  ->whereNull('user.deleted_at')
    						  ->whereIn('creator', $userIds);

    	// 时间范围条件
    	if(isset($where['date_range']) && $where['date_range'] !== ''){
    		$dateRange = explode(',', $where['date_range']);
    		if (isset($dateRange[0]) && !empty($dateRange[0])) {
                $query->whereRaw("document_content.created_at >= '" . $dateRange[0] . " 00:00:00'");
            }
            if (isset($dateRange[1]) && !empty($dateRange[1])) {
                $query->whereRaw("document_content.created_at <= '" . $dateRange[1] . " 23:59:59'");
            }
    		// $query->whereBetween('document_content.created_at', [$dateRange[0].' 00:00:00', $dateRange[1].' 23:59:59']);
    	}

    	return $query->groupBy('creator')->get()->toArray();
    }
    /**
	 * 获取所有文档创建人
	 *
	 * @return array
	 *
	 * @author niuxiaoke
	 *
	 * @since 2017-08-08
	 */
    public function getAllCreator()
    {
    	return $this->entity->select('creator')
    						->leftJoin('user', 'user.user_id', '=', 'document_content.creator')
						    ->whereNull('user.deleted_at')
    						->groupBy('creator')
    						->get()
    						->toArray();
    }

    // 获取文档来源id
    public function getSourceId($documentId)
    {
    	return $this->entity->select('source_id')
    						->where('document_id', $documentId)
    						->get();
    }

    /**
	 * 获取有标签的文档id
	 *
	 * @return array
	 *
	 * @author niuxiaoke
	 *
	 * @since 2017-08-10
	 */
    public function getHasTagDocumentIds($where)
    {
    	return $this->entity->select('document_content.document_id')
    						->leftJoin('document_tag', 'document_content.document_id', '=', 'document_tag.document_id')
    						->wheres($where)
    						->get()
    						->toArray();
    }

    public function getLogs($param) {
    	$param['fields']	= isset($param['fields'])
							? $param['fields']
							: ['document_logs.*'];

		$param['order_by']	= isset($param['order_by']) ? $param['order_by'] : $this->orderBy;

		$query = $this->entity->select($param['fields'])
							->rightJoin('document_logs', 'document_content.document_id', '=', 'document_logs.document_id');

		if(isset($param['search']) && !empty($param['search'])){
			$query = $query->wheres($param['search']);
		}

		return $query->orders($param['order_by'])->withTrashed()->get();
    }

    public function getDocumentIdByTypeAndSource($typeId , $sourceId)
    {
    	return $this->entity->select('document_id')
    						->where('source_id', $sourceId)
    						->where('folder_type', $typeId)
    						->first();
    }
    public function cancelOutTimeDocument() {
        $currentTime = date("Y-m-d H:i:s");
        $query = $this->entity;
        $query = $query->where('top', 1)->where('top_end_time', '<', $currentTime)->where('top_end_time', '!=', '0000-00-00 00:00:00')->update([
            'top'=>0, 
            'top_end_time' => "0000-00-00 00:00:00",
            'top_begin_time'=>"0000-00-00 00:00:00"
        ]);
    }
}
