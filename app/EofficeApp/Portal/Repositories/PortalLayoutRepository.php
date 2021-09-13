<?php
namespace App\EofficeApp\Portal\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Portal\Entities\PortalLayoutEntity;
/**
 * 门户布局资源库类
 * 
 * @author 李志军
 * 
 * @since 2015-10-27
 */
class PortalLayoutRepository extends BaseRepository
{	
    /**
     * 注册门户布局实体对象
     * 
     * @param \App\EofficeApp\Portal\Entities\PortalLayoutEntity $entity
     * 
     * @author 李志军
     * 
     * @since 2015-10-27
     */
    public function __construct(PortalLayoutEntity $entity)
    {
            parent::__construct($entity);
    }
    /**
     * 判断布局是否存在
     * 
     * @param int $portalId
     * @param string $userId
     * 
     * @return int
     * 
     * @author 李志军
     * 
     * @since 2015-10-27
     */
    public function layoutExists($portalId, $userId)
    {
            return $this->entity->select(['portal_layout_id'])->where('portal_id',$portalId)->where('user_id', $userId)->count();
    }
    
    public function clearDeleteUserLayout() 
    {
        $lists = $this->entity->select(['user_id', 'portal_layout_id'])->where('portal_id', 1)->get();
        $group = $lists->mapToGroups(function($item){
            return [$item->user_id => $item->portal_layout_id];
        });
        $portalLayoutIds  = [];
        foreach ($group as $items) {
            foreach ($items as $key => $item) {
                if($key != 0) {
                    $portalLayoutIds[] = $item;
                }
            }
        }
        if(count($portalLayoutIds) > 0) {
            return $this->entity->whereIn('portal_layout_id', $portalLayoutIds)->delete();
        }
        return true;
    }
    /**
     * 更新门户布局
     * 
     * @param string $portalLayoutContent
     * @param int $portalId
     * @param string $userId
     * 
     * @return boolean 编辑结果
     * 
     * @author 李志军
     * 
     * @since 2015-10-27
     */
    public function updateLayout($portalLayoutContent, $portalId, $userId)
    {
            $query = $this->entity->where('portal_id',$portalId);
            if($userId) {
                $query = $query->where('user_id', $userId);
            }
            return $query->update(['portal_layout_content' => $portalLayoutContent]);
    }
    /**
     * 批量更新门户布局
     * 
     * @param int $portalId
     * 
     * @return boolean 编辑结果
     * 
     * @author 李志军
     * 
     * @since 2015-10-27
     */
    public function batchUpdateLayout($elementMargin, $portalId)
    {
            return $this->entity->where('portal_id',$portalId)->update(['element_margin' => $elementMargin]);
    }
    /**
     * 获取门户布局信息
     * 
     * @param int $portalId
     * @param string $userId
     * 
     * @return object 门户布局信息
     * 
     * @author 李志军
     * 
     * @since 2015-10-27
     */
    public function getLayoutInfo($portalId, $userId)
    {
        return $this->entity->where('portal_id', $portalId)->where('user_id', $userId)->first();
    }
    public function getPersonalLayoutUserId()
    {
        return $this->entity->select(['user_id'])->where('portal_id',1)->get()->toArray();
    }
    public function getLayoutContent($portalId, $userId)
    {
        return $this->entity->select(['portal_layout_content'])->where('portal_id', $portalId)->where('user_id', $userId)->first();
    }
    public function getLayouElementMargin($portalId, $userId)
    {
        return $this->entity->select(['element_margin'])->where('portal_id', $portalId)->where('user_id', $userId)->first();
    }
    public function getList(array $wheres)
    {
        $query =  $this->entity;
        if(!empty($wheres)) {
            $query = $query->wheres($wheres);
        }
        
        return $query->get();
    }
}
