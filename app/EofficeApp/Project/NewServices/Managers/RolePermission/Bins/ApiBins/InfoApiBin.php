<?php

namespace App\EofficeApp\Project\NewServices\Managers\RolePermission\Bins\ApiBins;

use App\EofficeApp\Project\NewServices\Managers\DataManager;
use App\EofficeApp\Project\NewServices\Managers\RolePermission\PermissionManager;
use Illuminate\Database\Eloquent\Model;

class InfoApiBin extends BaseApiBin
{
    public function __construct($apiConfig)
    {
        parent::__construct($apiConfig);
        $this->type = 'info';
    }


    public function formatResult(&$result)
    {
        parent::formatResult($result);
        if ($result instanceof Model) {
            $dataManager = DataManager::getIns();
            $fPIs = $dataManager->getWitFPIs();
            PermissionManager::setDataFunctionPages($result, $dataManager, $fPIs);
        }
    }
}