<?php
namespace App\EofficeApp\Meeting\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Meeting\Entities\MeetingRoomsEntity;
/**
 * @会议室资源库类
 *
 * @author 李志军
 */
class MeetingRoomsRepository extends BaseRepository
{
	private $primaryKey = 'room_id';//主键

	private $limit		= 20;//列表页默认条数

	private $page		= 0;//页数

	private $orderBy	= ['room_id' => 'desc'];//默认排序

	/**
	 * @注册会议室实体
	 * @param \App\EofficeApp\Entities\MeetingRoomsEntity $entity
	 */
	public function __construct(MeetingRoomsEntity $entity) {
		parent::__construct($entity);
	}
	/**
	 * @获取会议室列表
	 * @param type $param
	 * @return 会议室列表 | array
	 */
	public function listRooms($param)
	{
		$param['fields']	= empty($param['fields']) ? ['*'] : $param['fields'];

		$param['limit']		= !isset($param['limit']) ? $this->limit : $param['limit'];

		$param['page']		= !isset($param['page']) ? $this->page : $param['page'];

		$param['order_by']	= empty($param['order_by']) ? $this->orderBy : $param['order_by'];
        $query = $this->entity;
        if(isset($param['search'])) {
            $param['search'] = $param['search'];
        }

        if(isset($param['search']['room_id'])) {
            $room_id = isset($param['search']['room_id']) ? $param['search']['room_id'] : '';
             $query = $query->wheres(['room_id' =>[$room_id, 'in']]);
             return $query->parsePage($param['page'], $param['limit'])->get();
        }
        if(isset($param['search']['room_name'])) {

            $room_id = isset($param['search']['room_name']) ? $param['search']['room_name'] : '';

            $query = $query->where('room_name','like', '%'.$room_id[0].'%');
            return $query->parsePage($param['page'], $param['limit'])->get();
        }

        return  $query->parsePage($param['page'], $param['limit'])
					->get();

	}

	public function listRoom($param)
	{
		$param['fields']	= empty($param['fields']) ? ['meeting_rooms.*', 'meeting_sort.*'] : $param['fields'];

		$param['limit']		= !isset($param['limit']) ? $this->limit : $param['limit'];

		$param['page']		= !isset($param['page']) ? $this->page : $param['page'];

		$param['order_by']	= empty($param['order_by']) ? $this->orderBy : $param['order_by'];
		$userId = isset($param["user_id"]) ? $param["user_id"]:"";
        $roleId = isset($param["role_id"]) ? $param["role_id"]:"";
        $deptId = isset($param["dept_id"]) ? $param["dept_id"]:"";
        if (isset($param["role_id"]) && is_array($param["role_id"])) {
            $roleId = implode(",", $param["role_id"]);
        }

		$query = $this->entity->select($param['fields']);

        $query = $query->leftjoin("meeting_sort", "meeting_sort.meeting_sort_id", "=", "meeting_rooms.meeting_sort_id");
        $query = $query->where(function($query) use ($userId, $roleId, $deptId){
            $roleId = explode(',', $roleId);
            if(!empty($roleId)){
                foreach($roleId as $v){
                    $query->orWhereRaw("find_in_set(?,meeting_sort.member_role) or meeting_sort.member_role = 'all'",[$v]);
                }
            }
            $query = $query->orWhereRaw("FIND_IN_SET(?, meeting_sort.member_user) or meeting_sort.member_user = 'all'",[$userId])
                ->orWhereRaw("FIND_IN_SET(?, meeting_sort.member_dept) or meeting_sort.member_dept = 'all'",[$deptId]);
        });
        $query = $query->where(function($query)use($param){
           if(isset($param['search']['room_name'])) {

                $room_id = isset($param['search']['room_name']) ? $param['search']['room_name'] : '';

                $query = $query->where('room_name', 'like', "%" . $room_id[0] . "%");

            }
            if(isset($param['search']['room_id']) && !empty($param['search']['room_id'][0]) && count($param['search']['room_id']) == 1) {

                $room_id = isset($param['search']['room_id']) ? $param['search']['room_id'] : '';

                $query = $query->where('room_id', '=', $room_id[0]);

            } else if (isset($param['search']['room_id'])) {
            	$query = $query->wheres($param['search']);
            }
        });
		return $query->orders($param['order_by'])
					->parsePage($param['page'], $param['limit'])
					->get()->toArray();
	}
	/**
	 * @获取会议室数量
	 * @param type $param
	 * @return 会议室数量 | int
	 */
	public function getRoomCount($param)
	{
		$userId = isset($param["user_id"]) ? $param["user_id"]:"";
        $roleId = isset($param["role_id"]) ? $param["role_id"]:"";
        $deptId = isset($param["dept_id"]) ? $param["dept_id"]:"";
        if (isset($param["role_id"]) && is_array($param["role_id"])) {
            $roleId = implode(",", $param["role_id"]);
        }
		$query = $this->entity;

		if (!empty($param['search'])) {
			$query = $query->wheres($param['search']);
		}
		$query = $query->leftjoin("meeting_sort", "meeting_sort.meeting_sort_id", "=", "meeting_rooms.meeting_sort_id");
		$roleId = explode(',', $roleId);
		if(!empty($roleId)){
			foreach($roleId as $v){
				$query->orWhereRaw("find_in_set(?,meeting_sort.member_role) or meeting_sort.member_role = 'all'",[$v]);
            }
		}
		$query = $query->orWhereRaw("FIND_IN_SET(?, meeting_sort.member_user) or meeting_sort.member_user = 'all'",[$userId])
				->orWhereRaw("FIND_IN_SET(?, meeting_sort.member_dept) or meeting_sort.member_dept = 'all'",$deptId);
		return $query->count();
	}
	/**
	 * @获取会议室数量(无权限)
	 * @param type $param
	 * @return 会议室数量 | int
	 */
	public function getRoomCounts($param)
	{
		$param['limit']		= !isset($param['limit']) ? $this->limit : $param['limit'];

		$param['page']		= !isset($param['page']) ? $this->page : $param['page'];

		$param['order_by']	= empty($param['order_by']) ? $this->orderBy : $param['order_by'];
		$query = $this->entity;

		if (isset($param['search']) && !empty($param['search'])) {
			$query = $query->wheres($param['search']);
		}
		return $query->orders($param['order_by'])
					->count();
	}
	/**
	 * @新建会议室
	 * @param type $data
	 * @return 会议室id | int
	 */
	public function addRoom($data)
	{
		return $this->entity->create($data);
	}
	/**
	 * @编辑会议室
	 * @param type $data
	 * @param type $roomId
	 * @return Boolean
	 */
	public function editRoom($data, $roomId)
	{
		return $this->entity->where($this->primaryKey, $roomId)->update($data);
	}
	/**
	 * @获取会议室详情
	 * @param type $roomId
	 * @return 会议室详情 | object
	 */
	public function showRoom($roomId)
	{
		$query = $this->entity
                    ->where("room_id",$roomId)
                    ->with(['roomHasManySubject' => function ($query) {
                        $query->selectRaw('count(*) AS num')
                            ->addSelect('room_id')
                            ->groupBy('room_id');
                    }])
                    ->with(['roomHasManySubjectList' => function ($query) {
                        $query->select("room_id");
                    }])
                    ;
        return $query->get()->first();
	}
	/**
	 * @删除会议室
	 * @param type $roomId
	 * @return Boolean
	 */
	public function deleteRoom($roomId)
	{
		return $this->entity->destroy($roomId);
	}

    /**
     * 根据会议室名称获取会议室ID
     * @param array
     * @return string
     */
    public function getRoomIdByRoomName($param) {
    	if(isset($param['room_name']) && !empty($param['room_name'])) {
	    	$roomId = $this->entity->select(['room_id'])->where('room_name', $param['room_name'])->first();
	    	if($roomId) {
	    		return $roomId->toArray()['room_id'];
	    	}
    	}
    	return '';
    }
    public function getApprovalUser ($meetingSortId) {

    	$params['fields'] = ['meeting_rooms.*', 'meeting_sort.*'];

        $query = $this->entity->select($params['fields']);

        $query = $query->leftjoin("meeting_sort", "meeting_sort.meeting_sort_id", '=', "meeting_rooms.meeting_sort_id")
        		->where("meeting_rooms.room_id", '=', $meetingSortId);
        return $query->get()->toArray();
    }
}
