<?php
namespace App\EofficeApp\Document\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Document\Entities\DocumentFolderPurviewEntity;
/**
 * 文档文件夹权限资源库类
 *
 * @author 李志军
 *
 * @since 2015-11-02
 */
class DocumentFolderPurviewRepository extends BaseRepository
{
	/**
	 * 注册文件夹权限实体类
	 *
	 * @param \App\EofficeApp\Document\Entities\DocumentFolderPurviewEntity $entity
	 *
	 * @author 李志军
	 *
	 * @since 2015-11-02
	 */
	public function __construct(DocumentFolderPurviewEntity $entity)
	{
		parent::__construct($entity);
	}
	/**
	 * 获取文件夹权限信息
	 *
	 * @param array $where
	 * @param array $fields
	 *
	 * @return object 文件夹权限信息
	 *
	 * @author 李志军
	 *
	 * @since 2015-11-02
	 */
	public function getPurviewInfo($where, $fields = ['*'])
	{
		return $this->entity->select($fields)->where($where)->first();
	}
	/**
	 * 更新子文件夹
	 *
	 * @param array $folderPurviewInfo
	 * @param array $childrenFolderId
	 *
	 * @return boolean 更新结果
	 *
	 * @author 李志军
	 *
	 * @since 2015-11-02
	 */
	public function updateChildrenData($folderPurviewInfo,$childrenFolderId)
	{
		return $this->entity->whereIn('folder_id', $childrenFolderId)->update($folderPurviewInfo);
	}
	/**
	 * 更新子文件层级id
	 *
	 * @param string $oldFolderLevelId
	 * @param string $newFolderLevelId
	 * @param int $folderId
	 *
	 * @return boolean 更新结果
	 *
	 * @author 李志军
	 *
	 * @since 2015-11-02
	 */
	public function updateChildrenLevelId($oldFolderLevelId, $newFolderLevelId,$folderId)
	{
		if($this->entity->whereRaw("FIND_IN_SET(?, folder_level_id)", [$folderId])->count() > 0) {
			return \DB::update("update " . $this->entity->table . " set folder_level_id = replace(folder_level_id,'$oldFolderLevelId','$newFolderLevelId') where FIND_IN_SET(?, folder_level_id)", [$folderId]);
		}

		return true;
	}
	/**
	 * 获取管理权限子文件夹
	 *
	 * @param int $parentId
	 *
	 * @return array 子文件
	 *
	 * @author 李志军
	 *
	 * @since 2015-11-02
	 */
	public function getManageChildrenFolderId($parentId, $own, $isAll = false)
	{
        $query = $this->entity->select(['folder_id']);
        if ($isAll) {
            $query = $query->whereRaw('find_in_set(\''.intval($parentId).'\',folder_level_id)');
        } else {
            $query = $query->where('parent_id', $parentId);
        }
			
		return  $query->where(function ($query) use($own){
                    $query->where(function($query)  use($own){
                        $query->orWhereRaw("FIND_IN_SET(?, user_manage)", [$own['user_id']])->orWhereRaw("FIND_IN_SET(?, dept_manage)", [$own['dept_id']]);
                        if(!empty($own['role_id'])){
                            foreach($own['role_id'] as $roleId){
                                $query->orWhereRaw("FIND_IN_SET(?, role_manage)", [$roleId]);
                            }
                        }
                    })->orWhere('all_purview',1)
                    ->orWhere('user_manage','all')
                    ->orWhere('dept_manage','all')
                    ->orWhere('role_manage','all');
                })->get();
	}
	/**
	 * 获取新建权限子文件夹
	 *
	 * @param int $parentId
	 *
	 * @return array 新建权限子文件夹
	 *
	 * @author 李志军
	 *
	 * @since 2015-11-02
	 */

	public function getCreateChildrenFolderId($parentId,$own)
	{
		return $this->entity->select(['folder_id'])
			->where('parent_id', $parentId)
			->where(function ($query) use($own){
				$query->where(function($query)  use($own){
					$query->where(function($query) use($own){
						$query->orWhereRaw("FIND_IN_SET(?, user_manage)", [$own['user_id']])->orWhereRaw("FIND_IN_SET(?, dept_manage)", [$own['dept_id']]);
	                    if(!empty($own['role_id'])){
							foreach($own['role_id'] as $roleId){
								$query->orWhereRaw("FIND_IN_SET(?, role_manage)", [$roleId]);
			                }
						}
					})->orWhere(function ($query) use($own){
						$query->orWhereRaw("FIND_IN_SET(?, user_new)", [$own['user_id']])->orWhereRaw("FIND_IN_SET(?, dept_new)", [$own['dept_id']]);
	                    if(!empty($own['role_id'])){
							foreach($own['role_id'] as $roleId){
		                        $query->orWhereRaw("FIND_IN_SET(?, role_new)", [$roleId]);
		                    }
						}
					});
				})->orWhere('all_purview',1)
				  ->orWhere('user_manage','all')
                  ->orWhere('dept_manage','all')
                  ->orWhere('role_manage','all')
                  ->orWhere('user_new','all')
                  ->orWhere('dept_new','all')
                  ->orWhere('role_new','all');
			})->get();
	}
	/**
	 * 获取查看权限子文件夹
	 *
	 * @param int $parentId
	 *
	 * @return array 查看权限子文件夹
	 *
	 * @author 李志军
	 *
	 * @since 2015-11-02
	 */
	public function getShowChildrenFolderId($parentId, $own)
	{
		return	$this->mergeShowFloderQuery($this->entity->select(['folder_id'])->where('parent_id', $parentId),$own)->get();
	}

    public function getShowFamilyFolderId($parentId, $own)
    {
        return	$this->mergeShowFloderQuery($this->entity->select(['folder_id'])->whereRaw('find_in_set(\''.intval($parentId).'\',folder_level_id)'),$own)->get();
    }

	/**
	 * 获取所有显示权限文件夹id
	 *
	 * @return array 所有显示权限文件夹id
	 *
	 * @author 李志军
	 *
	 * @since 2015-11-02
	 */
	public function getAllShowFolderId($own)
	{
		return	$this->mergeShowFloderQuery($this->entity->select(['folder_id']),$own)->get();
	}
	public function getAllShowDocumentFolderId($own)
	{
		return	$this->mergeShowFloderQuery($this->entity->select(['folder_id']),$own,'d')->get();
	}
	public function getAllCreateFolderId($own) {
		return $this->entity->select(['folder_id'])
			->where(function ($query) use($own){
				$query->where(function($query)  use($own){
					$query->orWhereRaw("FIND_IN_SET(?, user_manage)", [$own['user_id']])->orWhereRaw("FIND_IN_SET(?, dept_manage)", [$own['dept_id']]);
                    if(!empty($own['role_id'])){
						foreach($own['role_id'] as $roleId){
							$query->orWhereRaw("FIND_IN_SET(?, role_manage)", [$roleId]);
		                }
					}
				})->orWhere(function ($query) use($own){
					$query->orWhereRaw("FIND_IN_SET(?, user_new)", [$own['user_id']])->orWhereRaw("FIND_IN_SET(?, dept_new)", [$own['dept_id']]);
                    if(!empty($own['role_id'])){
						foreach($own['role_id'] as $roleId){
                            $query->orWhereRaw("FIND_IN_SET(?, role_new)", [$roleId]);
	                    }
					}
				});
			})->orWhere('all_purview',1)
			  ->orWhere('user_manage','all')
              ->orWhere('dept_manage','all')
              ->orWhere('role_manage','all')
              ->orWhere('user_new','all')
              ->orWhere('dept_new','all')
              ->orWhere('role_new','all')
			  ->get()->toArray();
	}
	public function getAllManageFolderId($own)
	{
		return $this->entity->select(['folder_id'])
			->where(function ($query) use($own){
				$query->orWhereRaw("FIND_IN_SET(?, user_manage)", [$own['user_id']])->orWhereRaw("FIND_IN_SET(?, dept_manage)", [$own['dept_id']]);
                if(!empty($own['role_id'])){
					foreach($own['role_id'] as $roleId){
						$query->orWhereRaw("FIND_IN_SET(?, role_manage)", [$roleId]);
	                }
				}
			})->orWhere('all_purview',1)
			  ->orWhere('user_manage','all')
              ->orWhere('dept_manage','all')
              ->orWhere('role_manage','all')
			  ->get()->toArray();
    }
	/**
	 * 获取子孙文件夹数量
	 *
	 * @param int $folderId
	 *
	 * @return int 子孙文件夹数量
	 *
	 * @author 李志军
	 *
	 * @since 2015-11-02
	 */
	public function getFamilyShowFolderCount($folderId, $own)
	{
		$query = $this->entity->select(['folder_id'])->whereRaw('find_in_set(\''.intval($folderId).'\',folder_level_id)');
		$query = $this->mergeShowFloderQuery($query,$own,'f');

		return $query->count();
	}

    public function hasReplyPurview($folderId, $own)
	{
		return $this->entity
			->where('folder_id', $folderId)
			->where(function ($query) use($own){
				$query->where(function($query) use($own){
					$query->where(function($query) use($own){
						$query->orWhereRaw("FIND_IN_SET(?, user_manage)", [$own['user_id']])->orWhereRaw("FIND_IN_SET(?, dept_manage)", [$own['dept_id']]);
	                    if(!empty($own['role_id'])){
							foreach($own['role_id'] as $roleId){
								$query->orWhereRaw("FIND_IN_SET(?, role_manage)", [$roleId]);
			                }
						}
					})->orWhere(function ($query) use($own){
                        $query->orWhereRaw("FIND_IN_SET(?, user_revert)", [$own['user_id']])->orWhereRaw("FIND_IN_SET(?, dept_revert)", [$own['dept_id']]);
	                    if(!empty($own['role_id'])){
	                    	foreach($own['role_id'] as $roleId){
                                $query->orWhereRaw("FIND_IN_SET(?, role_revert)", [$roleId]);
		                    }
	                    }
					});
				})->orWhere('all_purview',1)
				  ->orWhere('user_manage','all')
              	  ->orWhere('dept_manage','all')
              	  ->orWhere('role_manage','all')
              	  ->orWhere('user_revert','all')
              	  ->orWhere('dept_revert','all')
              	  ->orWhere('role_revert','all');
			})->count();
	}
	public function hasDownPurview($folderId, $own)
	{
		return $this->entity
			->where('folder_id', $folderId)
			->where(function ($query) use($own){
				$query->where(function($query) use($own){
					$query->where(function($query) use($own){
						$query->orWhereRaw("FIND_IN_SET(?, user_manage)", [$own['user_id']])->orWhereRaw("FIND_IN_SET(?, dept_manage)", [$own['dept_id']]);
	                    if(!empty($own['role_id'])){
							foreach($own['role_id'] as $roleId){
								$query->orWhereRaw("FIND_IN_SET(?, role_manage)", [$roleId]);
			                }
						}
					})->orWhere(function ($query) use($own){
                        $query->orWhereRaw("FIND_IN_SET(?, user_down)", [$own['user_id']])->orWhereRaw("FIND_IN_SET(?, dept_down)", [$own['dept_id']]);
						if(!empty($own['role_id'])){
							foreach($own['role_id'] as $roleId){
                                $query->orWhereRaw("FIND_IN_SET(?, role_down)", [$roleId]);
                            }
						}
					});
				})->orWhere('all_purview',1)
				  ->orWhere('user_manage','all')
              	  ->orWhere('dept_manage','all')
              	  ->orWhere('role_manage','all')
              	  ->orWhere('user_down','all')
              	  ->orWhere('dept_down','all')
              	  ->orWhere('role_down','all');
			})->count();
    }
    public function hasPrintPurview($folderId, $own)
	{
		return $this->entity
			->where('folder_id', $folderId)
			->where(function ($query) use($own){
				$query->where(function($query) use($own){
					$query->where(function($query) use($own){
                        $query->orWhereRaw("FIND_IN_SET(?, user_manage)", [$own['user_id']])->orWhereRaw("FIND_IN_SET(?, dept_manage)", [$own['dept_id']]);
	                    if(!empty($own['role_id'])){
							foreach($own['role_id'] as $roleId){
                                $query->orWhereRaw("FIND_IN_SET(?, role_manage)", [$roleId]);
			                }
						}
					})->orWhere(function ($query) use($own){
                        $query->orWhereRaw("FIND_IN_SET(?, user_print)", [$own['user_id']])->orWhereRaw("FIND_IN_SET(?, dept_print)", [$own['dept_id']]);
						if(!empty($own['role_id'])){
							foreach($own['role_id'] as $roleId){
                                $query->orWhereRaw("FIND_IN_SET(?, role_print)", [$roleId]);
		                    }
						}
					});
				})->orWhere('all_purview',1)
				  ->orWhere('user_manage','all')
              	  ->orWhere('dept_manage','all')
              	  ->orWhere('role_manage','all')
              	  ->orWhere('user_print','all')
              	  ->orWhere('dept_print','all')
              	  ->orWhere('role_print','all');
			})->count();
	}
	public function getFamilyShowFolderId($folderId,$own)
	{
		$query = $this->entity->select(['folder_id'])->whereRaw('find_in_set(\''.intval($folderId).'\',folder_level_id)');
		$query = $this->mergeShowFloderQuery($query,$own,'d');

		return $query->get();
	}
	private function mergeShowFloderQuery($query,$own,$type = 'f')
	{
		return $query->where(function ($query) use($own,$type){
				$query->where(function ($query) use($own,$type){
					$query->where(function($query) use ($own){
                        $query->orWhereRaw("FIND_IN_SET(?, user_manage)", [$own['user_id']])->orWhereRaw("FIND_IN_SET(?, dept_manage)", [$own['dept_id']]);
	                    if(!empty($own['role_id'])){
							foreach($own['role_id'] as $roleId){
                                $query->orWhereRaw("FIND_IN_SET(?, role_manage)", [$roleId]);
			                }
						}
					})->orWhere(function ($query) use($own){
                        $query->orWhereRaw("FIND_IN_SET(?, user_view)", [$own['user_id']])->orWhereRaw("FIND_IN_SET(?, dept_view)", [$own['dept_id']]);
	                    if(!empty($own['role_id'])){
							foreach($own['role_id'] as $roleId){
                                $query->orWhereRaw("FIND_IN_SET(?, role_view)", [$roleId]);
		                    }
						}
					});
					if($type == 'f') {
						$query->orWhere(function ($query) use($own){
                            $query->orWhereRaw("FIND_IN_SET(?, user_new)", [$own['user_id']])->orWhereRaw("FIND_IN_SET(?, dept_new)", [$own['dept_id']]);
		                    if(!empty($own['role_id'])){
								foreach($own['role_id'] as $roleId){
                                    $query->orWhereRaw("FIND_IN_SET(?, role_new)", [$roleId]);
			                    }
							}
						})->orWhere('user_new','all')
              	  		  ->orWhere('dept_new','all')
              	 		  ->orWhere('role_new','all');
					}
				})->orWhere('all_purview',1)
				  ->orWhere('user_manage','all')
              	  ->orWhere('dept_manage','all')
              	  ->orWhere('role_manage','all')
              	  ->orWhere('user_view','all')
              	  ->orWhere('dept_view','all')
              	  ->orWhere('role_view','all');
			});
	}
	/**
	 * 获取新建权限文件夹数量
	 *
	 * @param int $folderId
	 *
	 * @return int 新建权限文件夹数量
	 *
	 * @author 李志军
	 *
	 * @since 2015-11-02
	 */
	public function getFamilyCreateFolderCount($folderId, $own)
	{
		return $this->entity
			->whereRaw('find_in_set(\''.intval($folderId).'\',folder_level_id)')
			->where(function ($query) use($own){
				$query->where(function($query) use($own){
					$query->where(function($query) use($own){
                        $query->orWhereRaw("FIND_IN_SET(?, user_manage)", [$own['user_id']])->orWhereRaw("FIND_IN_SET(?, dept_manage)", [$own['dept_id']]);
	                    if(!empty($own['role_id'])){
							foreach($own['role_id'] as $roleId){
                                $query->orWhereRaw("FIND_IN_SET(?, role_manage)", [$roleId]);
			                }
						}
					})->orWhere(function ($query) use($own){
                        $query->orWhereRaw("FIND_IN_SET(?, user_new)", [$own['user_id']])->orWhereRaw("FIND_IN_SET(?, dept_new)", [$own['dept_id']]);
	                    if(!empty($own['role_id'])){
							foreach($own['role_id'] as $roleId){
                                $query->orWhereRaw("FIND_IN_SET(?, role_new)", [$roleId]);
		                    }
						}
					});
				})->orWhere('all_purview',1)
				  ->orWhere('user_manage','all')
              	  ->orWhere('dept_manage','all')
              	  ->orWhere('role_manage','all')
              	  ->orWhere('user_new','all')
              	  ->orWhere('dept_new','all')
              	  ->orWhere('role_new','all');
			})->count();
	}
	/**
	 * 获取管理权限的子孙文件夹数量
	 *
	 * @param int $folderId
	 *
	 * @return int 管理权限的子孙文件夹数量
	 *
	 * @author 李志军
	 *
	 * @since 2015-11-02
	 */
	public function getFamilyManagerFolderCount($folderId, $own)
	{
		return $this->entity
			->whereRaw('find_in_set(\''.intval($folderId).'\',folder_level_id)')
			->where(function ($query) use($own){
				$query->where(function($query) use($own){
                    $query->orWhereRaw("FIND_IN_SET(?, user_manage)", [$own['user_id']])->orWhereRaw("FIND_IN_SET(?, dept_manage)", [$own['dept_id']]);
	                if(!empty($own['role_id'])){
						foreach($own['role_id'] as $roleId){
                            $query->orWhereRaw("FIND_IN_SET(?, role_manage)", [$roleId]);
		                }
					}
                })->orWhere('all_purview',1)
                  ->orWhere('user_manage','all')
                  ->orWhere('dept_manage','all')
                  ->orWhere('role_manage','all');
			})->count();
	}
	/**
	 * 判断文件夹是否有新建权限
	 *
	 * @param int $folderId
	 *
	 * @return int
	 *
	 * @author 李志军
	 *
	 * @since 2015-11-02
	 */
	public function hasCreatePurview($folderId,$own)
	{
		return $this->entity
			->where('folder_id', $folderId)
			->where(function ($query) use($own){
				$query->where(function($query) use($own){
					$query->where(function($query) use($own){
                        $query->orWhereRaw("FIND_IN_SET(?, user_manage)", [$own['user_id']])->orWhereRaw("FIND_IN_SET(?, dept_manage)", [$own['dept_id']]);
	                    if(!empty($own['role_id'])){
							foreach($own['role_id'] as $roleId){
                                $query->orWhereRaw("FIND_IN_SET(?, role_manage)", [$roleId]);
			                }
						}
					})->orWhere(function ($query) use($own){
                        $query->orWhereRaw("FIND_IN_SET(?, user_new)", [$own['user_id']])->orWhereRaw("FIND_IN_SET(?, dept_new)", [$own['dept_id']]);
						if(!empty($own['role_id'])){
							foreach($own['role_id'] as $roleId){
                                $query->orWhereRaw("FIND_IN_SET(?, role_new)", [$roleId]);
		                    }
						}
					});
				})->orWhere('all_purview',1)
				  ->orWhere('user_manage','all')
              	  ->orWhere('dept_manage','all')
              	  ->orWhere('role_manage','all')
              	  ->orWhere('user_new','all')
              	  ->orWhere('dept_new','all')
              	  ->orWhere('role_new','all');
			})->count();
	}
	/**
	 * 判断文件夹是否有管理权限
	 *
	 * @param int $folderId
	 *
	 * @return int
	 *
	 * @author 李志军
	 *
	 * @since 2015-11-02
	 */
	public function hasManagerPurview($folderId, $own)
	{
		return $this->entity
			->where('folder_id', $folderId)
			->where(function($query) use ($own){
				$query->where(function($query) use($own){
                    $query->orWhereRaw("FIND_IN_SET(?, user_manage)", [$own['user_id']])->orWhereRaw("FIND_IN_SET(?, dept_manage)", [$own['dept_id']]);
	                if(!empty($own['role_id'])){
						foreach($own['role_id'] as $roleId){
                            $query->orWhereRaw("FIND_IN_SET(?, role_manage)", [$roleId]);
		                }
					}
                })->orWhere('all_purview',1)
                  ->orWhere('user_manage','all')
                  ->orWhere('dept_manage','all')
                  ->orWhere('role_manage','all');
			})->count();
    }
    // 判断文件夹是否有编辑权限
    public function hasEditPurview($folderId, $own)
	{
		return $this->entity
			->where('folder_id', $folderId)
			->where(function($query) use ($own){
				$query->where(function($query) use($own){
                    $query->orWhereRaw("FIND_IN_SET(?, user_edit)", [$own['user_id']])->orWhereRaw("FIND_IN_SET(?, dept_edit)", [$own['dept_id']]);
	                if(!empty($own['role_id'])){
						foreach($own['role_id'] as $roleId){
                            $query->orWhereRaw("FIND_IN_SET(?, role_edit)", [$roleId]);
		                }
					}
                })->orWhere('all_purview',1)
                  ->orWhere('user_edit','all')
                  ->orWhere('dept_edit','all')
                  ->orWhere('role_edit','all');
			})->count();
    }
    // 判断文件夹是否有删除权限
    public function hasDeletePurview($folderId, $own) {
        return $this->entity
			->where('folder_id', $folderId)
			->where(function($query) use ($own){
				$query->where(function($query) use($own){
                    $query->orWhereRaw("FIND_IN_SET(?, user_delete)", [$own['user_id']])->orWhereRaw("FIND_IN_SET(?, dept_delete)", [$own['dept_id']]);
	                if(!empty($own['role_id'])){
						foreach($own['role_id'] as $roleId){
                            $query->orWhereRaw("FIND_IN_SET(?, role_delete)", [$roleId]);
		                }
					}
                })->orWhere('all_purview',1)
                  ->orWhere('user_delete','all')
                  ->orWhere('dept_delete','all')
                  ->orWhere('role_delete','all');
			})->count();
    }
	/**
	 * 判断文件夹是否有查看权限
	 *
	 * @param int $folderId
	 *
	 * @return int
	 *
	 * @author 李志军
	 *
	 * @since 2015-11-02
	 */
	public function hasShowPurview($folderId, $own)
	{
		return $this->entity
			->where('folder_id', $folderId)
			->where(function ($query) use($own){
				$query->where(function($query) use($own){
					$query->where(function($query) use($own){
                        $query->orWhereRaw("FIND_IN_SET(?, user_manage)", [$own['user_id']])->orWhereRaw("FIND_IN_SET(?, dept_manage)", [$own['dept_id']]);
	                    if(!empty($own['role_id'])){
							foreach($own['role_id'] as $roleId){
                                $query->orWhereRaw("FIND_IN_SET(?, role_manage)", [$roleId]);
			                }
						}
					})->orWhere(function ($query) use($own){
                        $query->orWhereRaw("FIND_IN_SET(?, user_view)", [$own['user_id']])->orWhereRaw("FIND_IN_SET(?, dept_view)", [$own['dept_id']]);
						if(!empty($own['role_id'])){
							foreach($own['role_id'] as $roleId){
                                $query->orWhereRaw("FIND_IN_SET(?, role_view)", [$roleId]);
		                    }
						}
					});
				})->orWhere('all_purview',1)
				  ->orWhere('user_manage','all')
              	  ->orWhere('dept_manage','all')
              	  ->orWhere('role_manage','all')
              	  ->orWhere('user_view','all')
              	  ->orWhere('dept_view','all')
              	  ->orWhere('role_view','all');
			})->count();
	}

	/**
	 * 判断文件夹是否有回复权限
	 *
	 * @param int $folderId
	 *
	 * @return int
	 *
	 * @author 李志军
	 *
	 * @since 2015-11-02
	 */
	public function hasRevertPurview($folderId, $own)
	{
		return $this->entity
			->where('folder_id', $folderId)
			->where(function ($query) use ($own){
				$query->where(function($query) use($own){
					$query->where(function($query) use($own){
                        $query->orWhereRaw("FIND_IN_SET(?, user_manage)", [$own['user_id']])->orWhereRaw("FIND_IN_SET(?, dept_manage)", [$own['dept_id']]);
	                    if(!empty($own['role_id'])){
							foreach($own['role_id'] as $roleId){
                                $query->orWhereRaw("FIND_IN_SET(?, role_manage)", [$roleId]);
			                }
						}
					})->orWhere(function ($query) use($own){
                        $query->orWhereRaw("FIND_IN_SET(?, user_revert)", [$own['user_id']])->orWhereRaw("FIND_IN_SET(?, dept_revert)", [$own['dept_id']]);
	                    if(!empty($own['role_id'])){
	                    	foreach($own['role_id'] as $roleId){
                                $query->orWhereRaw("FIND_IN_SET(?, role_revert)", [$roleId]);
		                    }
	                    }
					});
				})->orWhere('all_purview',1)
				  ->orWhere('user_manage','all')
              	  ->orWhere('dept_manage','all')
              	  ->orWhere('role_manage','all')
              	  ->orWhere('user_revert','all')
              	  ->orWhere('dept_revert','all')
              	  ->orWhere('role_revert','all');
			})->count();
	}
    public function getAllFamilyFolderId($folderId)
    {
        return $this->entity->select(['folder_id'])
			->whereRaw('find_in_set(\''.intval($folderId).'\',folder_level_id)')->get()->toArray();
    }
}
