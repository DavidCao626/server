<?php
namespace App\EofficeApp\LogCenter\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\LogCenter\Entities\LogModuleConfigEntity;

/**
 * Description of LogModuleConfigRepository
 *
 * @author lizhijun
 */
class LogModuleConfigRepository extends BaseRepository 
{
    public function __construct(LogModuleConfigEntity $entity)
    {
        parent::__construct($entity);
    }
    
    public function isExists($moduleKey)
    {
        return $this->entity->where('module_key', $moduleKey)->count() == 1;
    }
    public function deleteByModuleKey($moduleKey)
    {
        return $this->entity->where('module_key', $moduleKey)->delete();
    }
    public function findByModuleKey($moduleKey)
    {
        return $this->entity->where('module_key', $moduleKey)->first();
    }
    
    public function getAllModuleConfig()
    {
        return $this->entity->all();
    }
}
