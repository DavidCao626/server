<?php
namespace App\EofficeApp\Customer\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Customer\Entities\LabelEntity;
use DB;
use Eoffice;

class LabelRepository extends BaseRepository
{

    const TABLE_REMIND = 'customer_label';

    public function __construct(LabelEntity $entity)
    {
        parent::__construct($entity);
    }

    public function labelListsTotal($input){

        return $this->entity->wheres($input['search'])->get()->count();
    }

    public function labelLists($input){
        $default = [
            'fields'   => ['*'],
            'page'     => 0,
            'limit'    => config('eoffice.pagesize'),
            'search'   => [],
            'order_by' => ['assets_applys.created_at' => 'desc'],
        ];
        $params = array_merge($default, array_filter($input));
        return $this->entity->select($params['fields'])->wheres($input['search'])->get();
    }

}
