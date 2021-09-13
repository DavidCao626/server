<?php
namespace App\EofficeApp\Flow\Repositories;

/**
 * Description of FlowInstancysConfigRepository
 *
 * @author lizhijun
 */
use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Flow\Entities\FlowInstancysEntity;
class FlowInstancysRepository extends BaseRepository
{
    public function __construct(FlowInstancysEntity $entity) 
    {
        parent::__construct($entity);
    }
    
    public function getAllOptions($search, $fields = ['*'])
    {
        $query = $this->entity->select($fields);

        if (!empty($search)) {
            $query->wheres($search);
        }
        
        return $query->orderBy('sort', 'asc')->get();
    }
    public function getDefaultSelectOption()
    {
        if($result = $this->entity->where('default_selected', 1)->first()){
            return $result;
        }
        
        return $this->getOptionById(0);
    }
    public function getOptionById($instancyId)
    {
        return $this->entity->where('instancy_id', $instancyId)->first();
    }
    public function optionExists($instancyId)
    {
        return $this->entity->where('instancy_id', $instancyId)->count();
    }
    
    public function getLastId()
    {
        return $this->entity->orderBy('instancy_id', 'desc')->first();
    }
    
    public function deleteInstancy($instancyId)
    {
        return $this->entity->where('instancy_id', $instancyId)->delete();
    }
}
