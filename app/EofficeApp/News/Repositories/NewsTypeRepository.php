<?php

namespace App\EofficeApp\News\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\News\Entities\NewsTypeEntity;

/**
 * 新闻类型Repository类:提供新闻类型相关的数据库操作方法。
 *
 * @author qishaobo
 *
 * @since  2015-10-20 创建
 */
class NewsTypeRepository extends BaseRepository
{
    public function __construct(NewsTypeEntity $entity)
    {
        parent::__construct($entity);
    }

	/**
	 * 查看新闻类型
	 *
	 * @param  array $data 查询条件
	 *
	 * @return array        查询结果
	 *
     * @author qishaobo
     *
     * @since  2015-11-12
	 */
	public function getNewsTypeList($data = [])
	{

        $default = [
            'fields' => ['news_type_id','news_type_name','news_type_parent','sort'],
            // 'page' => 0,
            //'limit' => config('eoffice.pagesize'),
            'search' => [],
            'order_by' => ['news_type_parent' => 'asc', 'sort' => 'asc'],
        ];

        $param = array_merge($default, array_filter($data));
        $query =  $this->entity
            ->select($default['fields'])
            ->wheres($param['search']);
        if(isset($param['withNewsCount'])){
            $query = $query->withCount('news');
        }
        $query = $query->withCount('news')
            ->orders($param['order_by'])
            ->orderBy('news_type_id','asc')
            // ->forPage($param['page'], $param['limit'])
            ->get()->toArray();
        return $query;
    }
    /**
     * 查看新闻类型
     *
     * @param  array $param 查询条件
     *
     * @return array        查询结果
     *
     * @author qishaobo
     *
     * @since  2015-11-12
     */
    public function getNewsTypeName($data)
    {

        return $this->entity
            ->where('news_type_id', $data)->value('news_type_name');

    }
    /**
     * 查看新闻类型
     *
     * @param  array $param 查询条件
     *
     * @return array        查询结果
     *
     * @author qishaobo
     *
     * @since  2015-11-12
     */
    public function getMaxsort()
    {
        return $this->entity ->max('sort');
    }
    public function getDataBywhere($where){

        return $this->entity->wheres($where)->get()->toArray();

    }
}
