<?php
namespace App\EofficeApp\Document\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Document\Entities\DocumentFolderEntity;
use App\EofficeApp\User\Entities\UserEntity;
use App\EofficeApp\System\Department\Entities\DepartmentEntity;
use App\EofficeApp\Role\Entities\RoleEntity;
use App\EofficeApp\Document\Repositories\DocumentRepositoryTrait;
/**
 * 文档文件夹源库类
 *
 * @author 李志军
 *
 * @since 2015-11-02
 */
class DocumentFolderRepository extends BaseRepository
{
	use DocumentRepositoryTrait;
	/** @var string 主键 */
	private $primaryKey = 'folder_id';
	/** @var int 默认列表条数 */
	private $limit		= 20;

	/** @var int 默认列表页 */
	private $page		= 0;
	private $userEntity;
	private $departmentEntity;
	private $roleEntity;
	/**
	 * 注册文件夹实体
	 *
	 * @param \App\EofficeApp\Document\Entities\DocumentFolderEntity $entity
	 *
	 * @author 李志军
	 *
	 * @since 2015-11-02
	 */
	public function __construct(DocumentFolderEntity $entity,RoleEntity $roleEntity,UserEntity $userEntity,DepartmentEntity $departmentEntity)
	{
		parent::__construct($entity);
		$this->userEntity = $userEntity;
		$this->departmentEntity = $departmentEntity;
		$this->roleEntity = $roleEntity;
	}
	public function mulitUpdateFolder($data, $conditionColumn)
	{
		return $this->mulitUpdate('document_folder', $data, $conditionColumn);
	}
	public function getFieldNameByIds($ids,$fieldName,$return = 'array')
	{
		switch($fieldName){
			case 'user_name':
				$lists = $this->userEntity->select('user_name')->whereIn('user_id',$ids)->get();
			break;
			case 'dept_name':
				$lists = $this->departmentEntity->select('dept_name')->whereIn('dept_id',$ids)->get();
			break;
			case 'role_name':
				$lists = $this->roleEntity->select('role_name')->whereIn('role_id',$ids)->get();
			break;
		}

		if($return == 'array'){
			$fieldNames = [];
			if(count($lists) > 0){
				foreach ($lists as $key => $value) {
					$fieldNames[] = $value->$fieldName;
				}
			}

			return $fieldNames;
		}
	}
	/**
	 * 获取文件夹详情
	 *
	 * @param int $folderId
	 * @param array $fields
	 *
	 * @return object 文件夹详情
	 *
	 * @author 李志军
	 *
	 * @since 2015-11-02
	 */
	public function getFolderInfo($folderId, $fields = ['*'])
	{
		return $this->entity->select($fields)->where($this->primaryKey, $folderId)->first();
	}
	/**
	 * 获取子文件夹数量
	 *
	 * @param int $folderId
	 *
	 * @return int 子文件夹数量
	 *
	 * @author 李志军
	 *
	 * @since 2015-11-02
	 */
	public function getChildrenFoldersCount($folderId)
	{
		return $this->entity->where('parent_id', $folderId)->count();
	}
	/**
	 * 更新子文件夹数据
	 *
	 * @param array $data
	 * @param int $folderId
	 *
	 * @return boolean
	 *
	 * @author 李志军
	 *
	 * @since 2015-11-02
	 */
	public function updateChildrenData($data, $folderId)
	{
            if ($this->entity->whereRaw('find_in_set(\'' . intval($folderId) . '\',folder_level_id)')->count() > 0) {
                return $this->entity->whereRaw('find_in_set(\'' . intval($folderId) . '\',folder_level_id)')->update($data);
            }

            return true;
        }
	/**
	 * 获取子文件夹
	 *
	 * @param int $folderId
	 * @param array $fields
	 *
	 * @return array 子文件夹
	 *
	 * @author 李志军
	 *
	 * @since 2015-11-02
	 */
	public function getChildrenInfo($folderId, $fields = ['*'])
	{
		return $this->entity->select($fields)->whereRaw('find_in_set(\''.intval($folderId).'\',folder_level_id)')->get();
	}
	/**
	 * 更新子孙文件夹层级结构id
	 *
	 * @param string $oldFolderLevelId
	 * @param string $newFolderLevelId
	 * @param int $folderId
	 *
	 * @return boolean
	 *
	 * @author 李志军
	 *
	 * @since 2015-11-02
	 */
	public function updateChildrenLevelId($oldFolderLevelId, $newFolderLevelId,$folderId)
	{
		if($this->entity->whereRaw("find_in_set('". intval($folderId) . "',folder_level_id)")->count() > 0) {
			return \DB::update("update " . $this->entity->table . " set folder_level_id = replace(folder_level_id,'$oldFolderLevelId','$newFolderLevelId') where FIND_IN_SET(?, folder_level_id)", [$folderId]);
		}

		return true;
	}
	/**
	 * 获取所有子文件夹
	 *
	 * @param array $fields
	 * @param int $parentId
	 *
	 * @return array 所有子文件夹
	 *
	 * @author 李志军
	 *
	 * @since 2015-11-02
	 */
	public function getAllChildrenFolder($params, $parentId)
	{
		$default = [
			'fields' => ['*'],
			'search' => [],
			'order_by' => ['folder_sort' => 'asc', 'created_at' => 'desc']
		];

		$params = array_merge($default, $params);

		return $this->entity->select($params['fields'])
			->where('parent_id', $parentId)
			->wheres($params['search'])
			->orders($params['order_by'])
			->get();
	}

    /**
     * 获取全部子级文件夹
     * @author yangxingqiang
     * @param $params
     * @param $parentId
     * @return mixed
     */
    public function getFamilyFolder($params, $parentId)
    {
        $default = [
            'fields' => ['*'],
            'search' => [],
            'order_by' => ['folder_sort' => 'asc', 'created_at' => 'desc']
        ];
        $params = array_merge($default, $params);
        return $this->entity->select($params['fields'])
//            ->where('parent_id', $parentId)
            ->whereRaw('find_in_set(\''.intval($parentId).'\',folder_level_id)')
            ->wheres($params['search'])
            ->orders($params['order_by'])
            ->get();
    }

	public function getFamilyFolderId($folderId)
	{
		return $this->entity->select(['folder_id'])->whereRaw('find_in_set(\''.intval($folderId).'\',folder_level_id)')->get();
	}
	/**
	 * 获取创建人子文件
	 *
	 * @param int $parentId
	 *
	 * @return array 创建人子文件
	 *
	 * @author 李志军
	 *
	 * @since 2015-11-02
	 */
	public function getCreatorChildrenFolderId($parentId, $currentUserId, $isAll = false)
	{
        $query = $this->entity->select(['folder_id']);
        if ($isAll) {
            $query = $query->whereRaw('find_in_set(\''.intval($parentId).'\',folder_level_id)');
        } else {
            $query = $query->where('parent_id', $parentId);
        }
		return	$query->where('user_id', $currentUserId)->get();
	}
	/**
	 * 获取子孙创建人文件夹
	 *
	 * @param int $folderId
	 *
	 * @return int
	 *
	 * @author 李志军
	 *
	 * @since 2015-11-02
	 */
	public function getFamilyCreatorFolderCount($folderId, $currentUserId)
	{
		return $this->entity
			->whereRaw('find_in_set(\''.intval($folderId).'\',folder_level_id)')
			->where('user_id', $currentUserId)->count();
	}

	public function getFolderCount($param)
	{
		$query = $this->entity->select(['folder_id']);
		if(isset($param['folder_id'])) {
            if(isset($param['creator'])) {
                $query->where(function ($query) use($param){
                    $query->whereIn('folder_id',$param['folder_id'])
                            ->orWhere('user_id',$param['creator']);
                });
            } else {
                $query->whereIn('folder_id',$param['folder_id']);
            }
		}
		if(isset($param['search']) && !empty($param['search'])) {
			$query->wheres($param['search']);
		}

		return $query->count();
	}

    public function listFolder($param)
    {
        $param['fields'] = isset($param['fields']) ? $param['fields'] : ['*'];

        $param['limit'] = isset($param['limit']) ? $param['limit'] : $this->limit;

        $param['page'] = isset($param['page']) ? $param['page'] : $this->page;

        $query = $this->entity->select($param['fields']);
        if (isset($param['folder_id'])) {
            if (isset($param['creator'])) {
                $query->where(function ($query) use($param) {
                    $query->whereIn('folder_id', $param['folder_id'])
                            ->orWhere('user_id', $param['creator']);
                });
            } else {
                $query->whereIn('folder_id', $param['folder_id']);
            }
        }
        if (isset($param['search']) && !empty($param['search'])) {
            $query->wheres($param['search']);
        }

        return $query->orderBy('folder_level_id', 'asc')->orderBy('folder_sort', 'asc')
                        ->forPage($param['page'], $param['limit'])
                        ->get();
    }

    public function getAllFloderId()
    {
        return array_column($this->entity->select(['folder_id'])->get()->toArray(), 'folder_id');
    }

    public function getParentId($folderId)
    {
    	return $this->entity->select('parent_id')
    						->where('folder_id', $folderId)
    						->get();
    }
    //递归获取子文件夹
    public function getChildrenId($folderId)
    {
        $folderId = explode(',', $folderId);
        if (count($folderId) > 1) {
            $folderId = array_filter($folderId);
            $childrenId = [];
            foreach ($folderId as $key => $value) {
                $result = $this->entity->select('folder_id')
                        ->whereRaw('find_in_set(\'' . intval($value) . '\',folder_level_id)')
                        ->get()
                        ->toArray();

                $childrenId = array_merge($childrenId, $result);
            }
        } else {
            $childrenId = $this->entity->select('folder_id')
                    ->whereRaw('find_in_set(\'' . intval($folderId[0]) . '\',folder_level_id)')
                    ->get()
                    ->toArray();
        }

        return array_column($childrenId, 'folder_id');
    }

    public function getCreateFolder($own) {
    	return $this->entity->select('folder_id')->where('user_id', $own['user_id'])->pluck('folder_id')->toArray();
    }
}
