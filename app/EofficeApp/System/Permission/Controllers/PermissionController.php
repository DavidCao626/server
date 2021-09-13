<?php

namespace App\EofficeApp\System\Permission\Controllers;

use App\EofficeApp\Base\Controller;
use App\EofficeApp\System\Permission\Services\PermissionService;
use Illuminate\Http\Request;

class PermissionController extends Controller
{

    public function __construct(
        Request $request,
        PermissionService $permissionService
    ) {
        parent::__construct();
        $this->request        = $request;
        $this->permissionService = $permissionService;
    }

    public function getPermissionGroups() {
        return $this->returnResult($this->permissionService->getPermissionGroups($this->request->all()));
    }

    public function addPermissionGroup() {
        return $this->returnResult($this->permissionService->addPermissionGroup($this->request->all()));
    }

    public function deletePermissionGroup($groupId) {
        return $this->returnResult($this->permissionService->deletePermissionGroup($groupId));
    }

    public function getGroupDetail($groupId) {
        return $this->returnResult($this->permissionService->getGroupDetail($groupId));
    }

    public function getPermissionType() {
        return $this->returnResult($this->permissionService->getPermissionType($this->request->all()));
    }

    public function addPermissionType() {
        return $this->returnResult($this->permissionService->addPermissionType($this->request->all()));
    }

    public function editPermissionType($typeId) {
        return $this->returnResult($this->permissionService->editPermissionType($typeId, $this->request->all()));
    }

    public function deletePermissionType($typeId) {
        return $this->returnResult($this->permissionService->deletePermissionType($typeId));
    }

    public function getPermissionTypeDetail($typeId) {
        return $this->returnResult($this->permissionService->getPermissionTypeDetail($typeId));
    }

    public function editPermissionGroup($groupId) {
        return $this->returnResult($this->permissionService->editPermissionGroup($groupId, $this->request->all()));
    }
}
