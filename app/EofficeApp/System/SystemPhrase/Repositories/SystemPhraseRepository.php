<?php
namespace App\EofficeApp\System\SystemPhrase\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\System\SystemPhrase\Entities\SystemPhraseEntity;
/**
 * 常用短语资源库类
 *
 */
class SystemPhraseRepository  extends BaseRepository
{
	/** @var int 默认列表条数 */
	private $limit		= 20;

	/** @var int 默认列表页 */
	private $page		= 0;

	/** @var array  默认排序  根据created_at 降序排序*/
	private $orderBy	= ['order_number' => 'asc', 'created_at' => 'desc'];

	/**
	 * 注册实体
	 *
	 * @param \App\EofficeApp\System\SystemPhrase\Entities\SystemPhraseEntity $entity
	 *
	 */
	public function __construct(SystemPhraseEntity $entity) {
		parent::__construct($entity);
	}
	/**
	 * 获取短语总数量
	 *
	 * @param array $search
	 *
	 * @return int 获取短语数量
	 *
	 */
	// 注：如果是根据用户进行查看短语加上where条件->where('user_id',$param['currentUserId'])，否则不加。
	public function getSystemPhraseCount($param)
	{
		$query = $this->entity->where('is_common', 0);

		if (!empty($param['search'])) {
			$query = $query->wheres($search['search']);
		}

		return $query->count();
	}
	/**
	 * 获取短语列表同时进行排序，根据create_at 进行排序
	 *
	 * @param array $param
	 *
	 * @return array 短语列表
	 *
	 */
	public function listCommonPhrase($param)
	{

		$param['fields']	= isset($param['fields']) ? $param['fields'] : ['*'];
		$param['limit']		= isset($param['limit']) ? $param['limit'] : $this->limit;
		$param['page']		= isset($param['page']) ? $param['page'] : $this->page;
		$param['order_by']	= isset($param['order_by']) ? $param['order_by'] : $this->orderBy;
		$currentUserId = $param['currentUserId'];
		$query = $this->entity->select($param['fields']);
		if (isset($param['search']) && !empty($param['search'])) {
			$query = $query->wheres($param['search']);
		}
		// 多字段排序处理
        if (isset($param['order_by']) && is_array($param['order_by']) && count($param['order_by']) > 1) {
            $orderByParam = array();
            foreach ($param['order_by'] as $key => $value) {
                if (!empty($value) && is_array($value)) {
                    $orderByParam[key($value)] = current($value);
                } else {
                	$orderByParam[$key] = $value;
                }
            }
            $param['order_by'] = $orderByParam;
        }
		return $query->orders($param['order_by'])
					->parsePage($param['page'], $param['limit'])
					->get();
	}
	public function getsystemPhrase($param){

		$query = $this->entity->select('content');
		return $query->get()->toArray();
	}
	public function getUniqueCommonPhrase($data){

		$param['order_by']	= isset($param['order_by']) ? $param['order_by'] : $this->orderBy;
		$query = $this->entity->select(['content', 'phrase_id'])->where('content', $data['content']);
		return $query->first();
	}
}
