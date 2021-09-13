<?php
namespace App\EofficeApp\Lang\Controllers;

use Illuminate\Http\Request;
use App\EofficeApp\Base\Controller;
use App\EofficeApp\Lang\Services\LangService;
class LangController extends Controller
{
    private $langService;

    public function __construct(
        LangService $langService,
        Request $request
    )
    {
        parent::__construct();

        $this->langService = $langService;
        $this->request = $request;
    }
    public function getLangVersion()
    {
        return $this->returnResult($this->langService->getLangVersion());
    }
    public function getLangFile($module, $locale)
    {
        return $this->returnResult($this->langService->getLangFile($module, $locale));
    }
    public function exportLangPackage()
    {
        return $this->returnResult($this->langService->exportLangPackage($this->request->all()));
    }
    public function langClear($locale)
    {
        return $this->returnResult($this->langService->langClear($locale));
    }
    public function addLangPackage()
    {
        return $this->returnResult($this->langService->addLangPackage($this->request->all(), true));
    }
    public function copyLangPackage()
    {
        return $this->returnResult($this->langService->copyLangPackage($this->request->all()));
    }
    public function getEffectLangPackages()
    {
        return $this->returnResult($this->langService->getEffectLangPackages($this->request->all()));
    }
    public function getLangPackages()
    {
        return $this->returnResult($this->langService->getLangPackages($this->request->all()));
    }
    public function getAllLangPackages()
    {
         return $this->returnResult($this->langService->getAllLangPackages($this->request->all()));
    }
    public function editLangPackage($langId)
    {
        return $this->returnResult($this->langService->editLangPackage($this->request->all(), $langId));
    }
    public function effectLangPackage($langId)
    {
        return $this->returnResult($this->langService->effectLangPackage($this->request->all(), $langId));
    }
    public function getLangPackageDetail($langId)
    {
        return $this->returnResult($this->langService->getLangPackageDetail($langId));
    }
    public function deleteLangPackage($langId)
    {
        return $this->returnResult($this->langService->deleteLangPackage($langId));
    }
    public function getLangModules($local, $type)
    {
        return $this->returnResult($this->langService->getLangModules($local, $type));
    }
    
    public function getTransModules()
    {
        return $this->returnResult($this->langService->getTransModules());
    }
    public function getConsultAndTransLangsByModule($module)
    {
        return $this->returnResult($this->langService->getConsultAndTransLangsByModule($module, $this->request->all()));
    }
     public function getConsultAndTransLangs()
    {
        return $this->returnResult($this->langService->getConsultAndTransLangs($this->request->all()));
    }
    public function saveTransLangPackage()
    {
        return $this->returnResult($this->langService->saveTransLangPackage($this->request->all()));
    }
    public function transOnline()
    {
        return $this->returnResult($this->langService->transOnline($this->request->all()));
    }
    public function getTransApi()
    {
        return $this->returnResult($this->langService->getTransApi());
    }
    public function setTransApi()
    {
        return $this->returnResult($this->langService->setTransApi($this->request->all()));
    }
    public function checkTransApi()
    {
        return $this->returnResult($this->langService->checkTransApi());
    }
    public function sortLangPackage()
    {
        return $this->returnResult($this->langService->sortLangPackage($this->request->all()));
    }
    public function bindUserLocale()
    {
        return $this->returnResult($this->langService->bindUserLocale($this->request->input('locale'), $this->own['user_id'], $this->apiToken));
    }
    public function setDefaultLocale($langId)
    {
        return $this->returnResult($this->langService->setDefaultLocale($this->request->all(), $langId));
    }
    public function getDefaultLocale()
    {
        return $this->returnResult($this->langService->getDefaultLocale());
    }
}
