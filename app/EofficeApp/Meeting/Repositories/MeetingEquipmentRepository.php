<?php
namespace App\EofficeApp\Meeting\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Meeting\Entities\MeetingEquipmentEntity;
/**
 * @会议设备资源库类
 * 
 * @author 李志军
 */
class MeetingEquipmentRepository extends BaseRepository
{
	private $primaryKey = 'equipment_id';//设备id
	
	private $limit		= 20;//列表默认条数
	
	private $page		= 0;//页数
	
	private $orderBy	= ['equipment_id' => 'desc'];//默认排序
	
	/**
	 * @注册会议设备实体
	 * @param \App\EofficeApp\Entities\MeetingEquipmentEntity $entity
	 */
	public function __construct(MeetingEquipmentEntity $entity) {
		parent::__construct($entity);
	}
	/**
	 * @获取会议设备列表
	 * @param type $param
	 * @return 会议设备列表 | array
	 */
	public function listEquipment($param)
	{
        $default = [
            'fields'     => ["*"],
            'page'       => 0,
            'limit'      => config('eoffice.pagesize'),
            'order_by'   => ['equipment_id'=>'desc'],
        ];

        $param = array_merge($default, $param);		

		$query = $this->entity->select($param['fields']);
		
		if (!empty($param['search'])) {
			$query = $query->wheres($param['search']);
		}
		
		return $query->orders($param['order_by'])
					->parsePage($param['page'], $param['limit'])
					->get()->toArray();
	}
	/**
	 * @获取会议设备数量
	 * @param type $param
	 * @return 会议设备数量 | int
	 */
	public function getEquipmentCount($param)
	{
		$query = $this->entity;
		
		if (!empty($param['search'])) {
			$query = $query->wheres($param['search']);
		}
		
		return $query->count();
	}
	/**
	 * @新建会议设备
	 * @param type $data
	 * @return 会议设备id | int
	 */
	public function addEquipment($data)
	{
		return $this->entity->create($data);
	}
	/**
	 * @编辑会议设备
	 * @param type $data
	 * @param type $equipmentId
	 * @return boolean
	 */
	public function editEquipment($data, $equipmentId)
	{
		return $this->entity->where($this->primaryKey, $equipmentId)->update($data);
	}
	/**
	 * @获取会议设备详情
	 * @param type $equipmentId
	 * @return 会议设备详情 | object
	 */
	public function showEquipment($equipmentId)
	{
		return $this->entity->where($this->primaryKey, $equipmentId)->first();
	}
	/**
	 * @删除会议设备
	 * @param type $equipmentId
	 * @return boolean
	 */
	public function deleteEquipment($equipmentId)
	{
		return $this->entity->destroy($equipmentId);
	}

	/**
	 * @获取所有设备ID组成的字符串
	 * @param  无
	 * @return string
	 */
	public function getAllEquipmentIdStr()
	{
		$allEquipmentIdArr = $this->entity->select('equipment_id')->get()->toArray();
		$allEquipmentIdStr = array();
		if($allEquipmentIdArr) {
			foreach($allEquipmentIdArr as $value) {
				$allEquipmentIdStr[] = $value['equipment_id'];
			}
			$allEquipmentIdStr = implode(',', $allEquipmentIdStr);			
		}
		return $allEquipmentIdStr;
	}	
}
