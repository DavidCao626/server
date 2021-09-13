<?php

namespace App\EofficeApp\Project\NewServices\Managers\RolePermission\Bins\ApiBins;

use App\EofficeApp\Project\NewServices\Managers\DataManager;
use App\EofficeApp\Project\NewServices\Managers\RolePermission\PermissionManager;
use App\EofficeApp\Project\NewServices\ProjectService;

class ListApiBin extends BaseApiBin
{
    private $page = 1;
    private $limit = 10;
    private $listType = 'normal'; // normal|total|list
    public function __construct($apiConfig)
    {
        parent::__construct($apiConfig);
        $this->type = 'list';
    }

    public function initApiData()
    {
        parent::initApiData();
        $this->initListParams();
    }

    public function initListParams() {
        $dataManager = DataManager::getIns();
//        $params = ProjectService::handleListParams($dataManager->getApiParams());
//        $dataManager->setApiParams($params);

        $paginateParams = DataManager::getIns()->getApiParams(['page', 'limit', 'list_type']);
        $paginateParams['page'] >= 0 && $this->page = $paginateParams['page'];
        $paginateParams['limit'] > 0 && $this->limit = $paginateParams['limit'];
        $paginateParams['list_type'] && $this->listType = $paginateParams['list_type'];
    }

    public function getPage()
    {
        return $this->page;
    }

    public function getLimit()
    {
        return $this->limit;
    }

    public function getListType()
    {
        return $this->listType;
    }

    public function formatResult(&$result)
    {
        parent::formatResult($result);
        if (isset($result['list'])) {
            $dataManager = DataManager::getIns();
            $fPIs = $dataManager->getWitFPIs();
            PermissionManager::setDataFunctionPages($result['list'], $dataManager, $fPIs);
        }
    }
}
