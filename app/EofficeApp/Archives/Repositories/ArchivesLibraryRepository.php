<?php

namespace App\EofficeApp\Archives\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Archives\Entities\ArchivesLibraryEntity;

/**
 * 卷库Repository类:提供卷库相关表操作资源
 *
 * @author qishaobo
 *
 * @since  2015-10-21 创建
 */
class ArchivesLibraryRepository extends BaseRepository
{

    public function __construct(ArchivesLibraryEntity $entity)
    {
        parent::__construct($entity);

		//if (class_exists('App\EofficeApp\Archives\Entities\ArchivesLibrarySubEntity')) {
			//$this->archivesLibrarySubEntity = new \App\EofficeApp\Archives\Entities\ArchivesLibrarySubEntity();
		//}
    }

 	/**
	 * 获取卷库列表
     *
	 * @param  array  $param  查询参数
     *
	 * @return array
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     *
	 */
	public function getLibraryList(array $param = [])
	{
		$default = [
			'fields'	=> ['library_id', 'library_name', 'library_number', 'library_creator', 'created_at'],
			'page'  	=> 0,
			'limit'		=> config('eoffice.pagesize'),
			'search'	=> [],
			'order_by' 	=> ['library_id' => 'asc'],
		];

		$param = array_merge($default, array_filter($param));

		$query = $this->entity
		->select($param['fields'])
		->orders($param['order_by'])
        ->parsePage($param['page'], $param['limit']);

        $query = $this->getLibraryParseWhere($query, $param['search']);

    	if (isset($this->archivesLibrarySubEntity)) {
       	 	$query = $query->with('subFields');
    	}

    	$query = $query->with('libraryPermission');

		return $query->get()
		->toArray();
	}

    /**
     * 查询卷库数量
     *
     * @param  array  $param  查询条件
     *
     * @return int    查询数量
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    public function getLibraryTotal(array $param = [])
    {
        $where = isset($param['search']) ? $param['search'] : [];
        return $this->getLibraryParseWhere($this->entity, $where)->count();
    }

    /**
     * 获取卷库where条件解析
     *
     * @param  array $where  查询条件
     *
     * @return object
     *
     * @author qishaobo
     *
     * @since  2015-11-13
     */
    public function getLibraryParseWhere($query, array $where = [])
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

        if (!empty($search_sub)) {
            $query = $query->whereHas('subFields', function ($query) use ($search_sub) {
                $query->wheres($search_sub);
            });
        }

        return $query->wheres($where);
    }

    /**
     * 插入卷库附表数据
     *
     * @param  array        $data  插入数据,一维或二维数组(多条)
     *
     * @return bool|object  插入多条返回是否成功|插入一条返回插入数据对象
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     *
     */
    public function insertSubLibrary(array $data)
    {
    	if (isset($this->archivesLibrarySubEntity)) {
       	 	return  $this->archivesLibrarySubEntity->create($data);
    	}
    	return false;
    }

    /**
     * 获取卷库附表数据
     *
     * @param  int          $libraryId  卷库id
     *
     * @return bool|object  操作是否成功|查询数据对象
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    public function getSubLibrary($libraryId)
    {
    	if (isset($this->archivesLibrarySubEntity))
    	{
        	return $this->archivesLibrarySubEntity
			        ->where('archives_library_id', $libraryId)
			        ->first();
    	}
    	return false;
    }

    /**
     * 删除卷库附表数据
     *
     * @param  string  $libraryIds  卷库id,多个用逗号隔开
     *
     * @return bool    操作是否成功
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    public function deleteSubLibrary($libraryIds)
    {
        if (isset($this->archivesLibrarySubEntity)) {
        	return $this->archivesLibrarySubEntity->wheres(['archives_library_id' => [$libraryIds,'in']])->delete();
        }
        return false;
    }

    /**
     * 获取卷库附表详情
     *
     * @param  array $where 查询条件
     *
     * @return bool   操作是否成功
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    public function getSubLibraryDetail($where)
    {
        if (isset($this->archivesLibrarySubEntity)) {
            return $this->archivesLibrarySubEntity->wheres($where)->first();
        }
    }

    /**
     * 更新卷库附表数据
     *
     * @param  array  $data 	  更新数据,一维或二维数组(多条)
     * @param  int 	  $libraryId  卷库id
     *
     * @return bool   操作是否成功
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    public function updateSubLibrary($data, $libraryId)
    {
        if (isset($this->archivesLibrarySubEntity)) {
        	return $this->archivesLibrarySubEntity
            ->where(['archives_library_id' => $libraryId])
            ->update($data);
        }
    }

    /**
     * 获取卷库列表（选择器用）
     *
     * @param  array  $param  查询参数
     *
     * @return array
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     *
     */
    public function getChoiceLibraryList(array $param = [])
    {
        $default = [
            'fields'	=> ['library_id', 'library_name', 'library_number', 'library_creator', 'created_at'],
            'page'  	=> 0,
            'limit'		=> config('eoffice.pagesize'),
            'search'	=> [],
            'order_by' 	=> ['library_id' => 'asc'],
        ];

        $param = array_merge($default, array_filter($param));

        $query = $this->entity
            ->select($param['fields'])
            ->orders($param['order_by'])
            ->parsePage($param['page'], $param['limit']);

        $query = $this->getLibraryParseWhere($query, $param['search']);

        if (isset($this->archivesLibrarySubEntity)) {
            $query = $query->with('subFields');
        }

        $query = $query->with('libraryPermission');
        $result = $query->get()->toArray();
        $pushData = [
            'library_id' => 0,
            'library_name' => '无卷库案卷',
            'library_number' => '',
            'library_creator' => '',
            'created_at' => '',
            'library_permission' => [],

        ];
        $result[] = $pushData;
        return $result;
    }

}