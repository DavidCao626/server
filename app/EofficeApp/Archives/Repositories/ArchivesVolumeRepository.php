<?php

namespace App\EofficeApp\Archives\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Archives\Entities\ArchivesVolumeEntity;

/**
 * 案卷Repository类:提供案卷相关表操作资源
 *
 * @author qishaobo
 *
 * @since  2015-10-21 创建
 */
class ArchivesVolumeRepository extends BaseRepository
{

    public function __construct(ArchivesVolumeEntity $entity)
    {
        parent::__construct($entity);

		//if (class_exists('App\EofficeApp\Archives\Entities\ArchivesVolumeSubEntity')) {
			//$this->archivesVolumeSubEntity = new \App\EofficeApp\Archives\Entities\ArchivesVolumeSubEntity();
		//}
    }

 	/**
	 * 获取案卷列表
     *
	 * @param  array  $param  查询参数
     *
	 * @return array  案卷列表
     *
     * @author qishaobo
     *
     * @since  2015-10-21
	 */
	public function getVolumeList(array $param = [])
	{
		$default = [
			'fields'	=> [
                                'volume_id', 'volume_name', 'volume_number',
                                'volume_year', 'volume_hold_time', 'volume_creator',
                                'library_id', 'volume_status', 'volume_appraisal', 'created_at'
                           ],
			'page'  	=> 0,
			'limit'		=> config('eoffice.pagesize'),
			'search'	=> [],
			'order_by' 	=> ['volume_id' => 'desc'],
            'withFile'  => 0
		];

		$param = array_merge($default, array_filter($param));
        $destroy = isset($param['search']['destroy']) ? true : false;
        unset($param['search']['destroy']);

		$query = $this->entity
		->select($param['fields'])
		->orders($param['order_by'])
        ->parsePage($param['page'], $param['limit']);

    	if (isset($this->archivesVolumeSubEntity)) {
       	 	$query = $query->with('subFields');
    	}

        $query = $query->with(['volumeHasOneLibrary' => function ($query) {
            $query->select(['library_id', 'library_name']);
        }]);

        if ($param['withFile'] == 1) {
            $query = $query->with(['volumeFiles' => function ($query) {
                $query->select(['volume_id', 'file_id']);
            }]);
        }

        $query = $this->getVolumeParseWhere($query, $param['search']);

        if ($destroy) {
            $query = $query->onlyTrashed();
        }
		return $query->get()
		->toArray();
	}

    /**
     * 查询数量
     *
     * @param  array  $param  查询条件
     *
     * @return int    查询数量
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    public function getVolumeTotal(array $param = [])
    {
        $where = isset($param['search']) ? $param['search'] : [];

        $destroy = isset($where['destroy']) ? true : false;
        unset($where['destroy']);

        $query = $this->getVolumeParseWhere($this->entity, $where);

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
    public function getVolumeParseWhere($query, array $where = [])
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

        if (isset($where['can_borrow'])) {
            $query = $query->whereHas('volumeHasOneLibrary.libraryPermission', function ($query) use ($where) {
                $query = $query->wheres(['department_id' => $where['can_borrow']])->orWhere('department_id', 0);
            })
            ->orWhere('library_id', '0');
            //->doesntHave('volumeHasOneLibrary.libraryPermission', 'or')
            unset($where['can_borrow']);
        }

        if (isset($where['hasFile'])) {
            $query = $query->has('volumeFiles');
            unset($where['hasFile']);
        }

        return $query->wheres($where);
    }

    /**
     * 插入案卷附表数据
     *
     * @param  array        $data  插入数据,一维或二维数组(多条)
     *
     * @return bool|object  插入多条返回是否成功|插入一条返回插入数据对象
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    public function insertSubVolume(array $data)
    {
    	if (isset($this->archivesVolumeSubEntity)) {
       	 	return  $this->archivesVolumeSubEntity->create($data);
    	}
    	return false;
    }

    /**
     * 获取案卷附表数据
     *
     * @param  int          $volumeId  案卷id
     *
     * @return bool|object  操作是否成功|查询数据对象
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    public function getSubVolume($volumeId)
    {
    	if (isset($this->archivesVolumeSubEntity))
    	{
        	return $this->archivesVolumeSubEntity
			        ->where('archives_volume_id', $volumeId)
			        ->first();
    	}
    	return false;
    }

    /**
     * 删除案卷附表数据
     *
     * @param  string  $volumeIds  案卷id,多个用逗号隔开
     *
     * @return bool    操作是否成功
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    public function deleteSubVolume($volumeIds)
    {
        if (isset($this->archivesVolumeSubEntity)) {
            return $this->archivesVolumeSubEntity->wheres(['archives_volume_id' => [$volumeIds,'in']])->delete();
        }
        return false;
    }

    /**
     * 更新案卷附表数据
     *
     * @param  array   $data 	 更新数据,一维或二维数组(多条)
     * @param  int 	  $volumeId  案卷id
     *
     * @return bool   操作是否成功
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    public function updateSubVolume($data, $volumeId)
    {
        if (isset($this->archivesVolumeSubEntity)) {
        	return $this->archivesVolumeSubEntity
            ->where(['archives_volume_id' => $volumeId])
            ->update($data);
        }
    }

    /**
     * 查询案卷详情
     *
     * @param  int    $id  日志id
     *
     * @return array  案卷详情
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    public function getVolumeDetail($id)
    {
        return $this->entity->with('volumeFiles')->find($id);
    }

    /**
     * 获取案卷附表详情
     *
     * @param  array $where 查询条件
     *
     * @return bool   操作是否成功
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    public function getSubVolumeDetail($where)
    {
        if (isset($this->archivesVolumeSubEntity)) {
            return $this->archivesVolumeSubEntity->wheres($where)->first();
        }
    }

    /**
     * 查询案卷字段
     *
     * @param   array  $where  查询条件
     * @param   int    $field  查询字段
     *
     * @return  array  查询字段列表
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    public function getVolumeField($where, $field)
    {
        return $this->entity->wheres($where)->pluck($field);
    }

    /**
     * 获取案卷列表
     *
     * @param  array  $param  查询参数
     *
     * @return array  案卷列表
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    public function getChoiceVolumeList(array $param = [])
    {
        $default = [
            'fields'	=> [
                'volume_id', 'volume_name', 'volume_number',
                'volume_year', 'volume_hold_time', 'volume_creator',
                'library_id', 'volume_status', 'volume_appraisal', 'created_at'
            ],
            'page'  	=> 0,
            'limit'		=> config('eoffice.pagesize'),
            'search'	=> [],
            'order_by' 	=> ['volume_id' => 'desc'],
            'withFile'  => 0
        ];

        $param = array_merge($default, array_filter($param));
        if(isset($param['search']['library_id']) && $param['search']['library_id'][0] == 9999){
            $param['search']['library_id'] = [[0],'in'];
        }
        $destroy = isset($param['search']['destroy']) ? true : false;
        unset($param['search']['destroy']);

        $query = $this->entity
            ->select($param['fields'])
            ->orders($param['order_by'])
            ->parsePage($param['page'], $param['limit']);

        if (isset($this->archivesVolumeSubEntity)) {
            $query = $query->with('subFields');
        }

        $query = $query->with(['volumeHasOneLibrary' => function ($query) {
            $query->select(['library_id', 'library_name']);
        }]);

        if ($param['withFile'] == 1) {
            $query = $query->with(['volumeFiles' => function ($query) {
                $query->select(['volume_id', 'file_id']);
            }]);
        }

        $query = $this->getVolumeParseWhere($query, $param['search']);

        if ($destroy) {
            $query = $query->onlyTrashed();
        }
        $result = $query->get()->toArray();
        $pushData = [
            'library_id' => '',
            'volume_appraisal' => 0,
            'volume_creator' => '',
            'volume_has_one_library' => [],
            'volume_hold_time' => 0,
            'volume_id' => 0,
            'volume_name' => '无案卷文件',
            'volume_number' => '',
            'volume_status' => 0,
            'volume_year' => 0,

        ];
        $result[] = $pushData;
        return $result;
    }

}
