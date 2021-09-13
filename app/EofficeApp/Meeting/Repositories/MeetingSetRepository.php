<?php
namespace App\EofficeApp\Meeting\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Meeting\Entities\MeetingSetEntity;
/**
 * @日程资源库类
 *
 *
 */
class MeetingSetRepository extends BaseRepository
{
    public function __construct(MeetingSetEntity $entity) {
        parent::__construct($entity);
    }

	public function getCalendarSetInfo($param = []) {
        $key = isset($param['meeting_set_key']) ? $param['meeting_set_key'] : '';
		return $this->entity
                    ->select(['*'])
                    ->get()->toArray();
	}
    public function updateSetData($data, $where) {
        return $this->entity
                    ->wheres($where)
                    ->update($data);
    }
}
