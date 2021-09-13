<?php

namespace App\EofficeApp\Project\NewServices\ServiceTraits;

use App\EofficeApp\Project\NewRepositories\ProjectDocumentRepository;
use App\EofficeApp\Project\NewRepositories\ProjectManagerRepository;
use App\EofficeApp\Project\NewRepositories\ProjectQuestionRepository;
use App\EofficeApp\Project\NewServices\Managers\CacheManager;
use App\EofficeApp\Project\NewServices\Managers\DataManager;
use Illuminate\Support\Arr;
Trait ProjectPermissionTrait
{

    public static function hasReportPermission()
    {
        return self::hasMenuId(163);
    }

    private static function hasMenuId($menuId)
    {
        $own = DataManager::getIns()->getOwn();
        return in_array($menuId, Arr::get($own, 'menus.menu', []));
    }
}
