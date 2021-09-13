<?php

namespace App\EofficeApp\System\Tag\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\System\Tag\Entities\TagEntity;

/**
 * 标签Repository类:提供标签表操作资源
 *
 * @author qishaobo
 *
 * @since  2016-05-27 创建
 */
class TagRepository extends BaseRepository
{

    public function __construct(TagEntity $entity)
    {
        parent::__construct($entity);
    }

    /**
     * 一个基础的函数，用来根据各种条件获取 tag 表的数据
     *
     * @method getTagGeneral
     *
     * @param  array                 $param [description]
     *
     * @return [type]                       [description]
     */
    function getTagGeneral($param = [])
    {
        $default = [
            'fields'     => ['*'],
            'page'       => 0,
            'limit'      => config('eoffice.pagesize'),
            'search'     => [],
            'order_by'   => ['tag_id'=>'desc'],
            'returntype' => 'first',
        ];
        $param = array_merge($default, array_filter($param));
        $query = $this->entity
                        ->select($param['fields'])
                        ->wheres($param['search'])
                        ->orders($param['order_by'])
                        ;
        // 分组参数
        if(isset($param['groupBy'])) {
            $query = $query->groupBy($param['groupBy']);
        }
        // 解析原生 where
        if(isset($param['whereRaw'])) {
            foreach ($param['whereRaw'] as $key => $whereRaw) {
                $query = $query->whereRaw($whereRaw);
            }
        }
        // 解析原生 select
        if(isset($param['selectRaw'])) {
            foreach ($param['selectRaw'] as $key => $selectRaw) {
                $query = $query->selectRaw($selectRaw);
            }
        }
        // 返回值类型判断
        if($param["returntype"] == "array") {
            return $query->get()->toArray();
        } else if($param["returntype"] == "count") {
            return $query->count();
        } else if($param["returntype"] == "object") {
            return $query->get();
        } else if($param["returntype"] == "first") {
            return $query->get()->first();
        }
    }

	/**
	 * 获取标签列表
	 *
	 * @param  array $param 查询条件
	 *
	 * @return object        查询结果
     *
     * @author qishaobo
     *
     * @since  2016-05-30 创建
	 */
	public function getTags(array $param = [])
	{
		$default = [
			'fields'	=> ['*'],
			'page'  	=> 0,
			'limit'		=> config('eoffice.pagesize'),
			'search'	=> [],
			'order_by' 	=> ['tag_id' => 'asc'],
            'returntype' => 'object',
		];

        // $param = array_filter($param, function($var) {
        //     return $var !== '';
        // });

        $param = array_merge($default, $param);

		$query = $this->entity
            		  ->select($param['fields'])
            		  ->orders($param['order_by'])
                      ->multiWheres($param['search'])
                      ->parsePage($param['page'], $param['limit'])
                      ;
        // 返回值类型判断
        if($param["returntype"] == "array") {
            return $query->get()->toArray();
        } else if($param["returntype"] == "count") {
            return $query->count();
        } else if($param["returntype"] == "object") {
            return $query->get();
        }
		// return $this->parseWhere($query, $param['search']);
	}

    /**
     * 获取标签数量
     *
     * @param  array $param  查询条件
     *
     * @return int 查询数量
     *
     * @author qishaobo
     *
     * @since  2016-05-30
     */
    public function getTagsTotal(array $param = [])
    {
        $param["page"]       = "0";
        $param["returntype"] = "count";
        return $this->getTags($param);
        // $where = isset($param['search']) ? $param['search'] : [];
        // return $this->parseWhere($this->entity, $where)->count();
    }

    /**
     * 标签where条件解析
     *
     * @param  array $where  查询条件
     *
     * @return object
     *
     * @author qishaobo
     *
     * @since  2016-06-01
     */
    public function parseWhere($query, array $where = [])
    {
        if (isset($where['withPermission'])) {
        	$permission = $where['withPermission'];
			$query->where(function ($query) use ($permission) {
                $query->where('tag_creator', $permission['user_id'])
                ->orWhere(function ($query) use ($permission) {
                    $query->where('tag_creator', '<>', $permission['user_id'])
                    ->where('tag_owner', 2);
                });
            });
            unset($where['withPermission']);
        }

        return $query = $query->wheres($where);
    }

    public function getUniqueTag($data){

        return $query = $this->entity->select(['tag_id', 'tag_name'])
                    ->where('tag_name', $data['tag_name'])
                    ->where('tag_type', '=', $data['tag_type'])
                    ->where('tag_creator', $data['tag_creator'])
                    ->first();
    }
}