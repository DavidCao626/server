<?php
namespace App\EofficeApp\Portal\Permissions;
class PortalPermission 
{
    private $portalRepository;
    public function __construct() 
    {
        $this->portalRepository = 'App\EofficeApp\Portal\Repositories\PortalRepository';
    }

    public function setPortalLayout($own, $data, $urlData)
    {
        $portalId = $data['portal_id'];
        if($portalId == 0) {
            return true;
        }
        $portal = app($this->portalRepository)->getDetail($portalId);
//        if($portal->creator == $own['user_id']) {
//            return true;
//        }
        $portals = app($this->portalRepository)->getEditPrivPortal($own['user_id'], $own['dept_id'], $own['role_id']);
        $portalIds = array_column($portals->toArray(), 'portal_id');
        if(in_array($portalId, $portalIds)) {
            return true;
        }
        return false;
    }
    public function getPortalLayout($own, $data, $urlData) 
    {
        $portalId = $urlData['portalId'];
        if($portalId == 0) {
            return true;
        }
        $portal = app($this->portalRepository)->getDetail($portalId);
        if(!$portal) {
            return false;
        }
        if($portal->creator == $own['user_id']) {
            return true;
        }
        $portals = app($this->portalRepository)->listPortal(['portal_id'], 'show', $own['user_id'], $own['dept_id'], $own['role_id']);
        $portalIds = array_column($portals->toArray(), 'portal_id');
        if($portalId != 1 && !in_array($portalId, $portalIds)) {
            return false;
        }
        return true;
    }
    public function setDefaultPortal($own, $data, $urlData)
    {
        $portalId = $urlData['portalId'];
        if($portalId == 0) {
            return true;
        }
        $portal = app($this->portalRepository)->getDetail($portalId);
        if($portal->creator == $own['user_id']) {
            return true;
        }
        $portals = app($this->portalRepository)->getEditPrivPortal($own['user_id'], $own['dept_id'], $own['role_id']);
        $portalIds = array_column($portals->toArray(), 'portal_id');
        if(in_array($portalId, $portalIds)) {
            return true;
        }
        return false;
    }
}
