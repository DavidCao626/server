<?php 
namespace App\EofficeApp\System\Template\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\System\Template\Entities\TemplateHtmlEntity;

class TemplateRepository extends BaseRepository
{
	/** @var int 默认列表条数 */
	private $limit;
	
	/** @var int 默认列表页 */
	private $page		= 0;
	
	/** @var array  默认排序 */
	private $orderBy	= ['created_at' => 'desc'];
	
	public function __construct(TemplateHtmlEntity $templateHtmlEntity) {
		parent::__construct($templateHtmlEntity);
		
		$this->limit = config('eoffice.pagesize');
	}
	
	public function listTemplate($param) 
	{
		$param['fields']	= isset($param['fields']) ? $param['fields'] : ['*'];
		
		$param['limit']		= isset($param['limit']) ? $param['limit'] : $this->limit;
		
		$param['page']		= isset($param['page']) ? $param['page'] : $this->page;
		
		$param['order_by']	= isset($param['order_by']) ? $param['order_by'] : $this->orderBy;
		
		$query = $this->entity->select($param['fields']);
		
		if(isset($param['search']) && !empty($param['search'])) {
			$query->wheres($param['search']);
		}
		
		return $query->orders($param['order_by'])
					->forPage($param['page'], $param['limit'])
					->get();
	}
	
	public function getTemplateCount($param) 
	{
		$query = $this->entity->select(['template_id']);
		
		if(isset($param['search']) && !empty($param['search'])) {
			$query->wheres($param['search']);
		}
		
		return $query->count();
	}
}
