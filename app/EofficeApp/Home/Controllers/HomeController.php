<?php
namespace App\EofficeApp\Home\Controllers;

use Illuminate\Http\Request;
use App\EofficeApp\Base\Controller;
use App\EofficeApp\Home\Services\HomeService;
/**
 * Description of HomeController
 *
 * @author lizhijun
 */
class HomeController extends Controller
{
    private $homeService;
    private $request;
    public function __construct(HomeService $homeService, Request $request) 
    {
        parent::__construct();
        $this->homeService = $homeService;
        $this->request = $request;
    }
    public function checkSystemVersion()
    {
        return $this->returnResult($this->homeService->checkSystemVersion());
    }
    
    public function updateSystem()
    {
        return $this->returnResult($this->homeService->updateSystem());
    }
    public function getBootPageStatus()
    {
        return $this->returnResult($this->homeService->getBootPageStatus());
    }
    public function setBootPageStatus()
    {
        return $this->returnResult($this->homeService->setBootPageStatus());
    }
    
    public function sceneSeederProgress()
    {
        return $this->returnResult($this->homeService->sceneSeederProgress());
    }
    
    public function sceneSeeder()
    {
        return $this->returnResult($this->homeService->sceneSeeder($this->request->input('scene_url', null), $this->request->input('file_size', null)));
    }
    
    public function getUrlData()
    {
        return $this->returnResult($this->homeService->getUrlData($this->request->all()));
    }
    public function emptySceneSeeder()
    {
        return $this->returnResult($this->homeService->emptySceneSeeder());
    } 
}
