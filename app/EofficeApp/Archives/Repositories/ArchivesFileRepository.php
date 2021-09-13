<?php

namespace App\EofficeApp\Archives\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Archives\Entities\ArchivesFileEntity;

/**
 * 档案文件Repository类:提供档案文件相关表操作资源
 *
 * @author qishaobo
 *
 * @since  2015-10-21 创建
 */
class ArchivesFileRepository extends BaseRepository
{

    public function __construct(ArchivesFileEntity $entity)
    {
        parent::__construct($entity);

		//if (class_exists('App\EofficeApp\Archives\Entities\ArchivesFileSubEntity')) {
			//$this->archivesFileSubEntity = new \App\EofficeApp\Archives\Entities\ArchivesFileSubEntity();
		//}
    }

 	/**
	 * 获取档案文件列表
     *
	 * @param  array  $param  查询参数
     *
	 * @return array  档案文件列表
     *
     * @author qishaobo
     *
     * @since  2015-10-21
	 */
	public function getFileList(array $param = [])
	{
		$default = [
			'fields'	=> [
                                'file_id', 'file_name', 'file_number',
                                'file_year', 'volume_id', 'file_hold_time',
                                'file_creator', 'file_appraisal', 'created_at'
                           ],
			'page'  	=> 0,
			'limit'		=> config('eoffice.pagesize'),
			'search'	=> [],
			'order_by' 	=> ['file_id' => 'desc'],
		];

		$param = array_merge($default, array_filter($param));
        $destroy = isset($param['search']['destroy']) ? true : false;
        $merge = isset($param['search']['merge']) ? true : false;
        unset($param['search']['destroy']);

        unset($param['search']['merge']);
		$query = $this->entity
		->select($param['fields'])
		->orders($param['order_by'])
		->forPage($param['page'], $param['limit']);

    	if (isset($this->archivesFileSubEntity)) {
       	 	$query = $query->with('subFields');
    	}

    	if($merge){
            $query = $query->whereHas('fileHasOneVolume', function ($query) {
                $query->whereIn('volume_status',[0,1]);
            })->orWhere('volume_id', '0');
        }
        $query = $query->with(['fileHasOneVolume' => function ($query) {
            $query->select(['volume_id', 'volume_name', 'volume_status']);
        }])->with(['fileCreatorHasOneUser' => function ($query) {
            $query->select(['user_id', 'user_name']);
        }]);

        $query = $this->getFileParseWhere($query, $param['search']);

        if ($destroy) {
            $query = $query->onlyTrashed();
        }

		return $query->get()
		->toArray();
	}

    /**
     * 查询数量
     *
     * @param  array  $param 查询条件
     *
     * @return int    数量
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    public function getFileTotal(array $param = [])
    {
        $where = isset($param['search']) ? $param['search'] : [];

        $destroy = isset($where['destroy']) ? true : false;
        $merge = isset($where['merge']) ? true : false;
        unset($where['destroy']);
        unset($where['merge']);

        $query = $this->entity;
        if($merge){
            $query = $query->whereHas('fileHasOneVolume', function ($query) {
                $query->whereIn('volume_status',[0,1]);
            })->orWhere('volume_id', '0');
        }

        $query = $this->getFileParseWhere($query, $where);

        if ($destroy) {
            $query = $query->onlyTrashed();
        }

        return $query->count();
    }

    /**
     * 获取档案文件where条件解析
     *
     * @param  array $where  查询条件
     *
     * @return object
     *
     * @author qishaobo
     *
     * @since  2015-11-13
     */
    public function getFileParseWhere($query, array $where = [])
    {
        $search_sub = [];
        if (!empty($where)) {
            foreach ($where as $field => $v) {
                if (strpos($field, 'sub_') !== false) {
                    $search_sub[$field] = $v;
                    unset($where[$field]);
                }
            }
        }

        if (isset($where['can_borrow'])) {
            $query = $query->whereHas('fileHasOneVolume.volumeHasOneLibrary.libraryPermission', function ($query) use ($where) {
                $query->wheres(['department_id' => $where['can_borrow']])->orWhere('department_id', 0);
            })
            ->orWhereHas('fileHasOneVolume', function ($query) use ($where) {
                $query->where('library_id', '0');
            })
//            ->doesntHave('fileHasOneVolume.volumeHasOneLibrary.libraryPermission', 'or')
            ->orWhere('volume_id', '0');
            unset($where['can_borrow']);
        }

        if (!empty($search_sub)) {
            $query = $query->whereHas('subFields', function ($query) use ($search_sub) {
                $query->wheres($search_sub);
            });
        }

        return $query->wheres($where);
    }

    /**
     * 插入档案文件附表数据
     *
     * @param  array        $data  插入数据,一维或二维数组(多条)
     *
     * @return bool|object  插入多条返回是否成功|插入一条返回插入数据对象
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    public function insertSubFile(array $data)
    {
    	if (isset($this->archivesFileSubEntity)) {
       	 	return  $this->archivesFileSubEntity->create($data);
    	}
    	return false;
    }

    /**
     * 获取档案文件附表数据
     *
     * @param  int          $fileId  档案文件id
     *
     * @return bool|object  操作是否成功|查询数据对象
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    public function getSubFile($fileId)
    {
    	if (isset($this->archivesFileSubEntity))
    	{
        	return $this->archivesFileSubEntity
			        ->where('archives_file_id', $fileId)
			        ->first();
    	}
    	return false;
    }

    /**
     * 获取档案文件附表详情
     *
     * @param  array $where 查询条件
     *
     * @return bool   操作是否成功
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    public function getSubFileDetail($where)
    {
        if (isset($this->archivesFileSubEntity)) {
            return $this->archivesFileSubEntity->wheres($where)->first();
        }
    }

    /**
     * 删除档案文件附表数据
     *
     * @param  string  $fileIds  档案文件id,多个用逗号隔开
     *
     * @return bool    操作是否成功
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    public function deleteSubFile($fileIds)
    {
        if (isset($this->archivesFileSubEntity)) {
            $data = ['deleted_at' => date("Y-m-d H:i:s")];
            return $this->archivesFileSubEntity->wheres(['archives_file_id' => [$fileIds,'in']])->delete();
        }
        return false;
    }

    /**
     * 更新档案文件附表数据
     *
     * @param  array  $data    更新数据,一维或二维数组(多条)
     * @param  int 	  $fileId  档案文件id
     *
     * @return bool   操作是否成功
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    public function updateSubFile($data, $fileId)
    {
        if (isset($this->archivesFileSubEntity)) {
        	return $this->archivesFileSubEntity
            ->where(['archives_file_id' => $fileId])
            ->update($data);
        }
    }

    /**
     * 获取案卷下的文件id
     * @param int|array $volumeIds
     * @return array
     */
    public function getRelationFileIds($volumeIds) {
        $query = $this->entity->newQuery();
        if (is_array($volumeIds)) {
            $query->whereIn('volume_id', $volumeIds);
        } else {
            $query->where('volume_id', $volumeIds);
        }
        return $query->pluck('file_id')->toArray();
    }
}
