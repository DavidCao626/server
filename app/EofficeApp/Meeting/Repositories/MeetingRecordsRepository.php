<?php
namespace App\EofficeApp\Meeting\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Meeting\Entities\MeetingRecordsEntity;
/**
 * @会议记录资源库类
 * 
 * @author 李志军
 */
class MeetingRecordsRepository extends BaseRepository
{
	private $primaryKey = 'record_id';//主键
	
	private $limit		= 20;//列表页默认条数
	
	private $orderBy	= ['record_time' => 'desc'];//默认排序
	
	/**
	 * @注册会议记录实体
	 * @param \App\EofficeApp\Entities\MeetingRecordsEntity $entity
	 */
	public function __construct(MeetingRecordsEntity $entity) {
		parent::__construct($entity);
	}
	/**
	 * @获取会议记录列表
	 * @param type $param
	 * @return 会议记录列表 | array
	 */
	public function listMeetingRecords($param)
	{
		$query = $this->entity;
		
		if (!empty($param['search'])) {
			$query = $query->wheres($param['search']);
		}
		
		$param['limit']		= $param['limit'] == 0 ? $this->limit : $param['limit'];
		
		$param['order_by']	= empty($param['order_by']) ? $this->orderBy : $param['order_by'];

		return $query->select('meeting_records.*','meeting_apply.meeting_apply_user')
					 ->leftJoin('meeting_apply', 'meeting_apply.meeting_apply_id', '=', 'meeting_records.meeting_apply_id')
				     ->orders($param['order_by'])
				     ->parsePage($param['page'], $param['limit'])
				     ->get();
	}
	/**
	 * @获取会议记录条数
	 * @param type $param
	 * @return 会议记录条数 | int
	 */
	public function getMeetingRecordsCount($param) 
	{
		$query = $this->entity;
		
		if (!empty($param['search'])) {
			$query = $query->wheres($param['search']);
		}
		
		return $query->count(); 
	}
	/**
	 * @新建会议记录
	 * @param type $data
	 * @return 会议记录id | int
	 */
	public function addMeetingRecord($data)
	{
		return $this->entity->create($data);
	}
	/**
	 * @编辑会议记录
	 * @param type $data
	 * @param type $recordId
	 * @return boolean
	 */
	public function editMeetingRecord($data,$recordId)
	{
		return $this->entity->where($this->primaryKey, $recordId)->update($data);
	}
	/**
	 * @更新会议记录
	 * @param type $data
	 * @param type $data,$userId,$meetingApplyId
	 * @return boolean
	 */
	public function updateMeetingRecord($data,$userId,$meetingApplyId)
	{
		$param = [
			'search' => [
				'record_creator'   => [$userId],
				'meeting_apply_id' => [$meetingApplyId]				
			]
		];
		return $this->entity->wheres($param['search'])->update($data);
	}
	/**
	 * @根据id获取会议记录
	 * @param type $recordId
	 * @return 会议记录详情 | object
	 */
	public function findRecordById($recordId)
	{
		return $this->entity->where($this->primaryKey, $recordId)->first();
	}
	public function getMeetingRecordDetails($mApplyId)
	{
		return $this->entity->select("user.user_id","user.user_name","meeting_records.*")->leftJoin("user", "user.user_id", "=", "meeting_records.record_creator")->where('meeting_apply_id', '=', $mApplyId)->get()->toArray();
	}

	/**
	 * @根据用户id和会议申请id获取会议记录
	 * @param type $recordId
	 * @return 会议记录详情 | object
	 */
	public function findRecordByUserIdAndMeetingApplyId($userId, $meetingApplyId)
	{
		$currentDate = date('Y-m-d');
		$param = [
			'search' => [
				'record_creator'   => [$userId],
				'meeting_apply_id' => [$meetingApplyId],
				'record_time'      => [[$currentDate.' 00:00:00', $currentDate.' 23:59:59'], 'between']			
			]
		];
		return $this->entity->wheres($param['search'])->first();
	}
	/**
	 * @删除会议记录
	 * @param type $recordId
	 * @return boolean
	 */
	public function deleteMeetingRecord($recordId)
	{
		return $this->entity->destroy($recordId);
	}
}
