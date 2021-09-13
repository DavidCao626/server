<?php
namespace App\EofficeApp\Customer\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Customer\Entities\LabelRelationEntity;
use DB;
use Eoffice;

class LabelRelationRepository extends BaseRepository
{

    const TABLE_REMIND = 'customer_label_relation';

    public function __construct(LabelRelationEntity $entity)
    {
        parent::__construct($entity);
    }



    public function getCustomerRelation($customer_ids,$id){
        return $this->entity->select('*')->whereIn('customer_id',$customer_ids)
            ->where('label_id',$id)->get()->toArray();
    }
    
}
