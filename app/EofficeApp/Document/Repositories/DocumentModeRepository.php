<?php
namespace App\EofficeApp\Document\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Document\Entities\DocumentModeEntity;
/**
 * 文档样式资源库类
 * 
 * @author 李志军
 * 
 * @since 2015-11-02
 */
class DocumentModeRepository extends BaseRepository
{	
	/** @var int 默认列表条数 */
	private $limit;
	
	/** @var int 默认列表页 */
	private $page		= 0;
	
	/** @var array  默认排序 */
	private $orderBy	= ['created_at' => 'desc'];
	
	/**
	 * 注册文档样式实体
	 * 
	 * @param \App\EofficeApp\Document\Entities\DocumentModeEntity $entity
	 * 
	 * @author 李志军
	 * 
	 * @since 2015-11-02
	 */
	public function __construct(DocumentModeEntity $entity)
	{
		parent::__construct($entity);
		
		$this->limit = config('eoffice.pagesize');
	}
	/**
	 * 获取样式数量
	 * 
	 * @param array $search
	 * 
	 * @return int 样式数量
	 * 
	 * @author 李志军
	 * 
	 * @since 2015-11-02
	 */
	public function getModeCount($param) 
	{
		$query = $this->entity->where('mode_id','!=',0);
		if (!empty($param['search'])) {
			$query->wheres($param['search']);
		}
		
		return $query->count();
	}
	/**
	 * 获取样式列表
	 * 
	 * @param array $param
	 * 
	 * @return array 样式列表
	 * 
	 * @author 李志军
	 * 
	 * @since 2015-11-02
	 */
	public function listMode($param) 
	{
		$param['fields']	= isset($param['fields']) ? $param['fields'] : ['*'];
		
		$param['limit']		= isset($param['limit']) ? $param['limit'] : $this->limit;
		
		$param['page']		= isset($param['page']) ? $param['page'] : $this->page;
		
		$param['order_by']	= isset($param['order_by']) ? $param['order_by'] : $this->orderBy;
		
		$query = $this->entity->select($param['fields'])->where('mode_id','!=',0);
		
		if (!empty($param['search'])) {
			$query->wheres($param['search']);
		}
		
		return $query->orders($param['order_by'])
					->forPage($param['page'], $param['limit'])
					->get();
	}
}
