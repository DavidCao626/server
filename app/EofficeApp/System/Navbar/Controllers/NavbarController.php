<?php

namespace App\EofficeApp\System\Navbar\Controllers;

use App\EofficeApp\Base\Controller;
use App\EofficeApp\System\Navbar\Services\NavbarService;
use Illuminate\Http\Request;

class NavbarController extends Controller
{

    public function __construct(
        Request $request,
        NavbarService $navbarService
    ) {
        parent::__construct();
        $this->request        = $request;
        $this->navbarService = $navbarService;
    }


    public function getNavbar()
    {
        $result = $this->navbarService->getNavbar($this->request->all());
        return $this->returnResult($result);
    }
    public function setNavbar()
    {
        $result = $this->navbarService->setNavbar($this->request->all());
        return $this->returnResult($result);
    }


}
