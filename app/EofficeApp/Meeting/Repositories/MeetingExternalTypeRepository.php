<?php
namespace App\EofficeApp\Meeting\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Meeting\Entities\MeetingExternalTypeEntity;
/**
 * @会议申请资源库类
 *
 * @author 李志军
 */
class MeetingExternalTypeRepository extends BaseRepository
{
	private $primaryKey = 'external_user_type_id';//主键

	private $limit		= 20;//默认列表条数

	private $page		= 0;

	/**
	 * @注册会议申请实体
	 * @param \App\EofficeApp\Entities\MeetingApplyEntity $entity
	 */
	public function __construct(MeetingExternalTypeEntity $entity)
	{
		parent::__construct($entity);
	}

	public function addExternalUserType($data)
	{
		return $this->entity->create($data);
	}
	public function getExternalTypeTotal($param) {
		$search = isset($param['search']) ? $param['search'] : [];

        return $this->entity->wheres($search)->count();
	}
	public function getExternalTypeList ($param) {
		 $default = array(
			'fields' => ['meeting_external_user_type.*'],
			'page' => 0,
			'limit' => config('eoffice.pagesize'),
			'order_by' => ['meeting_external_user_type.created_at' => 'desc'],
			'search' => [],
			'group_by' => ['meeting_external_user_type.external_user_type_id']
		);

		$param = array_merge($default, $param);

        $query = $this->entity->select($param['fields'])
        			->with('subjectHasManyUser')
        			// ->leftJoin('meeting_external_user', 'meeting_external_user_type.external_user_type_id','=','meeting_external_user.external_user_type')
					->wheres($param['search'])
					->orders($param['order_by'])
					->groupBy($param['group_by']);
		// if(!isset($param['noPage'])) {
		// 	$query = $query->parsePage($param['page'], $param['limit']);
		// }
        return $query->get()->toArray();
	}

}
