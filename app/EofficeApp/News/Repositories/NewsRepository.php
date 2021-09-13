<?php

namespace App\EofficeApp\News\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\News\Entities\NewsEntity;
use App\EofficeApp\News\Repositories\NewsReaderRepository;
/**
 * 新闻Repository类:提供新闻相关的数据库操作方法。
 *
 * @author qishaobo
 *
 * @since  2015-10-20 创建
 */
class NewsRepository extends BaseRepository
{
    /** @var object 用户系统信息实体对象 */
    public function __construct(
    	NewsEntity $entity,
    	NewsReaderRepository $newsReaderRepository
    	)
    {
        parent::__construct($entity);
        $this->newsReaderRepository = $newsReaderRepository;
    }

	/**
	 * 查看新闻条数
	 *
	 * @param  array  $param 查询条件
	 *
	 * @return integer       查询数量
	 *
     * @author qishaobo
     *
     * @since  2015-11-12
	 */
	public function getNewsCount(array $param = [],$userId)
	{
		$query = $this->entity;
		$query = $this->getNewsParseWhere($query, $param,$userId);
		return $query->count();
	}

	/**
	 * 查看新闻列表
	 *
	 * @param  array $param 查询条件
	 *
	 * @return array        查询结果
	 *
     * @author qishaobo
     *
     * @since  2015-11-12
	 */
	public function getNewsList($param,$userId)
	{
		$default = [
				'page' 		=> 0,
				// 'order_by' 	=> ['news.top'=>'desc','news.top_create_time'=>'desc','news.news_id'=>'desc'],
				'order_by' 	=> ['news.publish' => 'asc','news.top_create_time'=>'desc'],
				'limit'		=> 10,
				'fields'	=> ['*']
		];

		$param = array_merge($default, array_filter($param));
		$query = $this->entity->leftJoin('user', 'user.user_id', '=', 'news.creator');
		$query = $query->leftJoin('news_type', function ($join) use($userId){
            $join->on('news.news_type_id', '=', 'news_type.news_type_id');
        });
		if (isset($param['fields'])) {
			$query = $query->select($param['fields']);
		}
		$query = $this->getNewsParseWhere($query, $param,$userId);
		if (isset($param['order_by'])) {
			// publish_time发布时间
			$query = $query->orders(['news.top'=>'desc'])->orders($param['order_by'])->orders(['news.publish_time'=>'desc']);
		}

		return $query
			->parsePage($param['page'], $param['limit'])
			->get()
			->toArray();
	}
	/**
	 * 查看门户新闻列表
	 *
	 * @param  array $param 查询条件
	 *
	 * @return array        查询结果
	 *
     * @author qishaobo
     *
     * @since  2015-11-12
	 */
	public function getNewsPortalList($param,$userId)
	{
		$default = [
				'page' 		=> 0,
				// 'order_by' 	=> ['news.top'=>'desc','news.top_create_time'=>'desc','news.news_id'=>'desc'],
				'order_by' 	=> ['news.publish' => 'asc','news.top_create_time'=>'desc'],
				'limit'		=> 10,
				'fields'	=> ['news.news_id','news.title','news.news_desc','news.news_type_id','news.views','news.publish','news.creator','news.publish_time','news.created_at','user.user_name','news_type.news_type_name','news.comments', 'news.top']
		];

		$param = array_merge($default, array_filter($param));
		$query = $this->entity->leftJoin('user', 'user.user_id', '=', 'news.creator')
							  ->leftJoin('news_type', 'news_type.news_type_id', '=', 'news.news_type_id');

		if (isset($param['fields'])) {
			$query = $query->select($param['fields']);
		}else{
			$query = $query->select(['news_id','title','news_desc','news.news_type_id','publish_time']);
		}
		// 连表查询，news_type_id字段名称重复，因此需要加上表名news
		if (isset($param['search']['news_type_id'])) {
			foreach($param['search']['news_type_id'] as $k => $v){
			    $param['search']['news.news_type_id'][$k] = $v;
			    unset($param['search']['news_type_id']);
			}
		}
		$query = $this->getNewsParseWhere($query, $param,$userId);
		if (isset($param['order_by'])) {
			$query = $query->orders(['news.top'=>'desc'])->orders($param['order_by'])->orders(['news.publish_time'=>'desc']);
		}
		$query = $query->where('news.publish','=',1);
		return $query
			->parsePage($param['page'], $param['limit'])
			->get();
	}
	/**
	 * 查看门户新闻列表
	 *
	 * @param  array $param 查询条件
	 *
	 * @return array        查询结果
	 *
     * @author qishaobo
     *
     * @since  2015-11-12
	 */
	public function getNewsPortalCount($param,$userId)
	{
		$default = [
				'page' 		=> 0,
				// 'order_by' 	=> ['news.top'=>'desc','news.top_create_time'=>'desc','news.news_id'=>'desc'],
				'order_by' 	=> ['news.top'=>'desc','news.publish' => 'asc','news.top_create_time'=>'desc'],
				'limit'		=> 10,
				'fields'	=> ['news.news_id','news.title','news.news_desc','news.news_type_id','news.views','news.publish','news.creator','news.publish_time','news.created_at','user.user_name']
		];

		$param = array_merge($default, array_filter($param));
		$query = $this->entity->leftJoin('user', 'user.user_id', '=', 'news.creator');


		$query = $this->getNewsParseWhere($query, $param,$userId);

		$query = $query->where('news.publish','=',1);
		return $query
			->count();
	}

    /**
     * 获取新闻where条件解析
     *
     * @param  array $where  查询条件
     *
     * @return object
     *
     * @author qishaobo
     *
     * @since  2015-11-13
     */
    public function getNewsParseWhere($query, array $param = [],$userId)
    {
        if (!isset($param['verify'])) {
            $query = $query->where(function($query) use($userId) {
                $query->where('creator', $userId)
                    ->orWhere('publish', 1);
            });
        }
		$read = 2;
		if (isset($param['search']['read'])) {
		    if(isset($param['search']['read'][0])){
                $read = $param['search']['read'][0];
            }else{
                $read = $param['search']['read'];
            }
			//查找当前用户已读新闻
			$hasReadNews = $this->newsReaderRepository->getLists(['fields'=>'news_id','search'=>['user_id'=>[$userId]]]);
			if($hasReadNews) {
				$hasReadNews = $hasReadNews->pluck('news_id')->toArray();
			}
			unset($param['search']['read']);
		}
        if ($read == 1) {
			$query = $query->whereIn('news_id',$hasReadNews)
                ->where('news.publish','=',1);
        } elseif ($read == 0){
            $query = $query->whereNotIn('news_id',$hasReadNews)
			    ->where('news.publish','=',1);
		}

		if (!empty($param['search'])) {
			$query = $query->wheres($param['search']);
		}

		return $query;
    }

	/**
	 * 编辑新闻
	 *
	 * @param  array $data   更新数据
	 * @param  array $wheres 更新条件
	 *
	 * @return bool          更新结果
	 *
     * @author qishaobo
     *
     * @since  2015-11-12
	 */
	function editNews($data, $wheres)
	{
		$query = $this->entity;
		foreach ($wheres as $field => $where) {
			if (is_array($where)) {
				$query = $query->whereIn($field, $where);
			} else {
				$query = $query->where($field, $where);
			}
		}
		return $query = $query->update($data);
	}

    /**
     * 获取公告详情
     *
     * @param int $notifyId
     *
     * @return object 公告详情
     *
     * @author 李志军
     *
     * @since 2015-10-20
     */
    public function showNews($newsId, $withParent = false)
    {
        return $this->entity->with(['newsType' => function($query) use ($withParent){
                $query->select(['news_type_id','news_type_name', 'news_type_parent']);
                if($withParent){
                    $query->with(['parent' => function($query){
                        $query->select(['news_type_id','news_type_name']);
                    }]);
                }
            }])->with(['user' => function ($query){
                    $query->withTrashed()->select(['user_id','user_name']);
                }])->find($newsId);


    }
    /**
     * 更新浏览量
     * @param  array $data 插入数据
     * @return object 插入数据对象
     */
    public function updateviews($data)
    {
        return $this->entity->where('news_id',$data)->increment('views');
    }
    public function getDataBywhere($where){

        return $this->entity->wheres($where)->get()->toArray();

    }
    public function cancelOutTimeTop(){
        $currentTime = date("Y-m-d H:i:s");
        $query = $this->entity;
        $query = $query->where('top',[1])->where('top_end_time','<',$currentTime)->where('top_end_time','!=','0000-00-00 00:00:00');
        return $query = $query->update(['top'=>0,'top_end_time'=>"0000-00-00 00:00:00",'top_create_time'=>"0000-00-00 00:00:00"]);
    }
}
