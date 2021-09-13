<?php
namespace App\EofficeApp\Meeting\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Meeting\Entities\MeetingSignTypeEntity;
/**
 * @会议室资源库类
 *
 * @author 李志军
 */
class MeetingSignTypeRepository extends BaseRepository
{
	private $primaryKey = 'sign_id';//主键

	private $limit		= 20;//列表页默认条数

	private $page		= 0;//页数

	private $orderBy	= ['sign_id' => 'desc'];//默认排序

	/**
	 * @注册会议室实体
	 * @param \App\EofficeApp\Entities\MeetingRoomsEntity $entity
	 */
	public function __construct(MeetingSignTypeEntity $entity) {
		parent::__construct($entity);
	}
	/**
	 * @获取会议室列表
	 * @param type $param
	 * @return 会议室列表 | array
	 */
	public function listSignType($param)
	{
		$param['fields']	= empty($param['fields']) ? ['*'] : $param['fields'];

		$param['limit']		= !isset($param['limit']) ? $this->limit : $param['limit'];

		$param['page']		= !isset($param['page']) ? $this->page : $param['page'];

		$param['order_by']	= empty($param['order_by']) ? $this->orderBy : $param['order_by'];

		$query = $this->entity->select($param['fields']);

		if (!empty($param['search'])) {
			$query = $query->wheres($param['search']);
		}

		return $query->orders($param['order_by'])
					->parsePage($param['page'], $param['limit'])
					->get()->toArray();
	}
	/**
	 * @获取外部人员提醒方式
	 * @param type $param
	 * @return 提醒方式 | array
	 */
	public function listExternalRemindType($param)
	{
		$param['fields']	= empty($param['fields']) ? ['*'] : $param['fields'];

		$param['limit']		= !isset($param['limit']) ? $this->limit : $param['limit'];

		$param['page']		= !isset($param['page']) ? $this->page : $param['page'];

		$param['order_by']	= empty($param['order_by']) ? $this->orderBy : $param['order_by'];

		$query = $this->entity->select($param['fields']);

		if (!empty($param['search'])) {
			$query = $query->wheres($param['search']);
		}

		return $query->orders($param['order_by'])
					->parsePage($param['page'], $param['limit'])
					->get()->toArray();
	}
	public function getExternalRemindTypeCount($param)
	{
		$query = $this->entity;

		if (!empty($param['search'])) {
			$query = $query->wheres($param['search']);
		}

		return $query->count();
	}
	/**
	 * @获取会议室数量
	 * @param type $param
	 * @return 会议室数量 | int
	 */
	public function getSignTypeCount($param)
	{
		$query = $this->entity;

		if (!empty($param['search'])) {
			$query = $query->wheres($param['search']);
		}

		return $query->count();
	}
}
