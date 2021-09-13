<?php

namespace App\EofficeApp\Menu\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Lang\Services\LangService;
use App\EofficeApp\Menu\Entities\MenuEntity;
use App\EofficeApp\Menu\Entities\MenuClassEntity;
use DB;
use Lang;

/**
 * 系统功能菜单资源库
 *
 * @author:白锦
 *
 * @since：2019-01-08
 *
 */
class MenuClassRepository extends BaseRepository
{

    public function __construct(
        MenuClassEntity $entity, LangService $langService
    ) {
        parent::__construct($entity);
        $this->langService = $langService;
    }

    public function menuSortLists($param=[])
    {   $default = [
            'page'     => 0,
            'limit'    => config('eoffice.pagesize'),
        ];
        $param = array_merge($default, array_filter($param));
        return $this->entity->with(['menu' => function($query) {
            $query->select(['menu_id', 'menu_class']);
        }])->orderBy('sort', 'asc')->parsePage($param['page'], $param['limit'])->get()->toArray();
    }
    public function menuSortTotal()
    {
         return $this->entity->count();
    }

    public function insertGetId($data = []) {
        return $this->entity->insertGetId($data);
    }

    public function findMenuSort($wheres)
    {   
        return $this->entity->wheres($wheres)->first();
    }


  

}
