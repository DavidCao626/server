<?php

namespace App\EofficeApp\Archives\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Archives\Entities\ArchivesBorrowEntity;

/**
 * 档案借阅Repository类:提供档案借阅相关表操作资源
 *
 * @author qishaobo
 *
 * @since  2015-10-21 创建
 */
class ArchivesBorrowRepository extends BaseRepository
{

    public function __construct(ArchivesBorrowEntity $entity)
    {
        parent::__construct($entity);

		//if (class_exists('App\EofficeApp\Archives\Entities\ArchivesBorrowSubEntity')) {
			//$this->archivesBorrowSubEntity = new \App\EofficeApp\Archives\Entities\ArchivesBorrowSubEntity();
		//}
    }

    /**
     * 插入借阅附表数据
     *
     * @param  array        $data  插入数据,一维或二维数组(多条)
     *
     * @return bool|object  插入多条返回是否成功|插入一条返回插入数据对象
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    public function insertSubBorrow(array $data)
    {
    	if (isset($this->archivesBorrowSubEntity)) {
       	 	return  $this->archivesBorrowSubEntity->create($data);
    	}
    	return false;
    }

 	/**
	 * 获取案卷借阅列表
     *
	 * @param  array $param 查询参数
     *
	 * @return array 案卷列表
     *
     * @author qishaobo
     *
     * @since  2015-10-21
	 */
	public function getBorrowList(array $param = [])
	{
		$default = [
			'fields'	    => [
                                'borrow_id', 'borrow_number', 'borrow_user_id',
                                'borrow_type', 'borrow_data_id', 'borrow_start',
                                'borrow_end', 'auditor_id', 'audit_time',
                                'return_time', 'created_at', 'borrow_status'
                            ],
			'page'  	    => 0,
			'limit'		    => config('eoffice.pagesize'),
			'search'	    => [],
			'order_by' 	    => ['borrow_id' => 'desc'],
            'withTrashed'   => 0
		];

		$param = array_merge($default, array_filter($param));
		$type = isset($param['search']['borrow_type']) ? $param['search']['borrow_type'][0] : '';

		$query = $this->entity
		->select($param['fields'])
		->orders($param['order_by'])
		->forPage($param['page'], $param['limit']);

        $query = $this->getBorrowParseWhere($query, $param['search']);

    	if (isset($this->archivesBorrowSubEntity)) {
       	 	$query = $query->with('subFields');
    	}

    	//$query = $query->with('borrowVolume');
        $query = $query->with(['borrowVolume' => function ($query) {
            $query = $query->select(['volume_id', 'volume_name', 'deleted_at'])->withTrashed();
        }]);

        $query = $query->with(['borrowFile' => function ($query) {
            $query = $query->select(['file_id', 'file_name', 'deleted_at'])->withTrashed();
        }]);

        $query = $query->with(['borrowCreatorHasOneUser' => function ($query) {
            $query->select(['user_id', 'user_name']);
        }]);

        if ($param['withTrashed'] > 0) {
            $query = $query->withTrashed();
        }

		return $query->get()->toArray();
	}

    /**
     * 查询数量
     *
     * @param  array  $param  查询条件
     *
     * @return int    查询结果
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    public function getBorrowTotal(array $param = [])
    {
        $where = isset($param['search']) ? $param['search'] : [];

        $withTrashed = isset($where['withTrashed']) ? true : false;
        unset($where['withTrashed']);

        $query = $this->getBorrowParseWhere( $this->entity, $where);

        if ($withTrashed) {
            $query = $query->withTrashed();
        }

        return $query->count();
    }

    /**
     * 获取案卷借阅where条件解析
     *
     * @param  array $where  查询条件
     *
     * @return object
     *
     * @author qishaobo
     *
     * @since  2015-11-13
     */
    public function getBorrowParseWhere($query, array $where = [])
    {
        if (isset($where['file_name'])) {
            if (!isset($where['borrow_type']) || (is_array($where['borrow_type'][0]) && !empty($where['borrow_type'][0]))) {
                $fileName = $where['file_name'];
                /*$query = $query->where(function ($query) use ($fileName) {
                    $query->whereHas('borrowVolume', function ($query) use ($fileName) {
                        $searchFile = ['volume_name' => $fileName];
                        $query->wheres($searchFile);
                    })->orWhereHas('borrowFile', function ($query) use ($fileName) {
                        $searchFile = ['file_name' => $fileName];
                        $query->wheres($searchFile);
                    });
                });*/
                $query = $query->where(function ($query) use ($fileName) {
                    $query->where('borrow_type', 'volume')->whereHas('borrowVolume', function ($query) use ($fileName) {
                        $searchFile = ['volume_name' => $fileName];
                        $query->wheres($searchFile);
                    });
                })->orwhere(function ($query) use ($fileName) {
                    $query->where('borrow_type', 'file')->WhereHas('borrowFile', function ($query) use ($fileName) {
                        $searchFile = ['file_name' => $fileName];
                        $query->wheres($searchFile);
                    });
                });

            } else if ($where['borrow_type'][0]  == 'file') {
                $searchFile = ['file_name' => $where['file_name']];
                $query = $query->whereHas('borrowFile', function ($query) use ($searchFile) {
                    $query->wheres($searchFile);
                });
            } else if ($where['borrow_type'][0] == 'volume') {
                $searchFile = ['volume_name' => $where['file_name']];
                $query = $query->whereHas('borrowVolume', function ($query) use ($searchFile) {
                    $query->wheres($searchFile);
                });
            }
            unset($where['file_name']);
        }

        $search_sub = [];
        foreach ($where as $field => $v) {
            if (strpos($field, 'sub_') !== false) {
                $search_sub[$field] = $v;
                unset($where[$field]);
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
     * 更新借阅档案附表数据
     *
     * @param  array  $data      更新数据,一维或二维数组(多条)
     * @param  int 	  $borrowId  借阅档案id
     *
     * @return bool   操作是否成功
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    public function updateSubBorrow($data, $borrowId)
    {
        if (isset($this->archivesBorrowSubEntity)) {
        	return  $this->archivesBorrowSubEntity
            ->where(['archives_borrow_id' => $borrowId])
            ->update($data);
        }
    }

    /**
     * 更新借阅档案附表数据
     *
     * @param  array   $where  查询条件
     *
     * @return object  查询结果对象
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    public function getBorrowDetail($where)
    {
        $query = $this->entity->wheres($where)
        ->withTrashed()
        ->with(['borrowCreatorHasOneUser' => function ($query) {
            $query->select(['user_id', 'user_name']);
        }])
        ->with(['borrowAuditorHasOneUser' => function ($query) {
            $query->select(['user_id', 'user_name']);
        }])
        ->with(['takeBackUserHasOneUser' => function ($query) {
            $query->select(['user_id', 'user_name']);
        }]);


        if (isset($this->archivesBorrowSubEntity)) {
            $query = $query->with('subFields');
        }

        return $query->first();
    }

    /**
     * 删除档案文件附表数据
     *
     * @param  array  $where  删除条件
     *
     * @return bool   操作是否成功
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    public function deleteSubBorrow($where)
    {
        if (isset($this->archivesBorrowSubEntity)) {
            return $this->archivesBorrowSubEntity->wheres($where)->delete();
        }
        return false;
    }

}