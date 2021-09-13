<?php
namespace App\EofficeApp\Lang\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Lang\Entities\LangPackageEntity;

class LangPackageRepository extends BaseRepository
{
    public function __construct(LangPackageEntity $entity) 
    {
        parent::__construct($entity);
        
        $this->limit = config('eoffice.pagesize');
    }
    public function packageExists($wheres)
    {
        return $this->entity->wheres($wheres)->count() > 0 ?: false;
    }
    public function getDefaultLocale()
    {
        return $this->entity->where('is_default', 1)->first();
    }
    public function getLangPackages($param)
    {
        $param = $this->handleParam($param);
        
        return $this->entity->select($param['fields'])
            ->wheres($param['search'])
            ->orders($param['order_by'])
            ->parsePage($param['page'], $param['limit'])->get();
    }
    public function getAllLangPackages()
    {
        return $this->entity->orderBy('sort','asc')->get();
    }
    public function getLangPackagesTotal($param)
    {
        $param = $this->handleParam($param);
        
        return $this->entity->wheres($param['search'])->count();
    }
    
    private function handleParam($param)
    {
        $param['fields'] = $param['fields'] ?? ['*'];
        $param['search'] = $param['search'] ?? [];
        $param['limit'] =  (isset($param['limit']) && !empty($param['limit'])) ? $param['limit'] : $this->limit;
        $param['page'] = isset($param['page']) ? $param['page'] : 0;
        $param['order_by'] = (isset($param['order_by']) && !empty($param['order_by'])) ? $param['order_by'] : ['sort' => 'asc'];
        
        return $param;
    }
}
