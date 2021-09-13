<?php
namespace App\EofficeApp\Attendance\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Attendance\Entities\AttendancePurviewOwnerEntity;

class AttendancePurviewOwnerRepository extends BaseRepository
{
    public function __construct(AttendancePurviewOwnerEntity $entity)
    {
            parent::__construct($entity);
    }

    public function getOwnerList($wheres) {
    	return $this->entity->wheres($wheres)->get();
    }

    public function getOwnerByMenu($menuId) {
    	return $this->entity->leftJoin('attend_purview_group', 'attend_purview_group.group_id', '=', 'attend_purview_owner.group_id')
                            ->where('menu_id', $menuId)->get();
    }
}