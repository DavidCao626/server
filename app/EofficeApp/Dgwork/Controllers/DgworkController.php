<?php

namespace App\EofficeApp\Dgwork\Controllers;

use App\EofficeApp\Base\Controller;
use App\EofficeApp\Dgwork\Requests\DgworkRequest;
use App\EofficeApp\Dgwork\Services\DgworkService;
use Illuminate\Http\Request;

class DgworkController extends Controller
{

    private $request;
    private $DgworkService;
    private $dgworkRequest;

    public function __construct(
        Request $request, DgworkService $DgworkService, DgworkRequest $dgworkRequest
    ) {
        parent::__construct();

        $this->DgworkService = $DgworkService;
        $this->request         = $request;
        $this->dgworkRequest = $dgworkRequest;
        $this->formFilter($request, $dgworkRequest);
    }

    public function getDgworkConfig()
    {
        $result = $this->DgworkService->getDgworkConfig();
        return $this->returnResult($result);
    }

    public function saveDgworkConfig()
    {
        $result = $this->DgworkService->saveDgworkConfig($this->request->all());
        return $this->returnResult($result);
    }

    public function checkDgworkConfig()
    {
        $result = $this->DgworkService->checkDgworkConfig($this->request->all());
        return $this->returnResult($result);
    }

    public function truncateDgwork()
    {
        $result = $this->DgworkService->truncateDgwork($this->request->all());
        return $this->returnResult($result);
    }

    public function dgworkAccess()
    {
        $result = $this->DgworkService->dgworkAccess($this->request->all());
        return $this->returnResult($result);
    }

    public function pcDgworkAccess()
    {
        $result = $this->DgworkService->pcDgworkAccess($this->request->all());
        return $this->returnResult($result);
    }

    public function dgworkAuth()
    {
        $result = $this->DgworkService->dgworkAuth($this->request->all());
        return $this->returnResult($result);
    }

    public function getDgworkUserList()
    {
        $result = $this->DgworkService->getDgworkUserList($this->request->all());
        return $this->returnResult($result);
    }

    public function addDgworkUserBind()
    {
        $result = $this->DgworkService->addDgworkUserBind($this->request->all());
        return $this->returnResult($result);
    }

    public function deleteDgworkUserBind()
    {
        $result = $this->DgworkService->deleteDgworkUserBind($this->request->all());
        return $this->returnResult($result);
    }

    public function getDgworkUserInfo()
    {
        $result = $this->DgworkService->getDgworkUserInfo($this->request->all());
        return $this->returnResult($result);
    }
    public function autoBindUser()
    {
        $result = $this->DgworkService->autoBindUser();
        return $this->returnResult($result);
    }
    public function dgworkSignPackage()
    {
        $result = $this->DgworkService->dgworkSignPackage();
        return $this->returnResult($result);
    }
    public function dgworkMove()
    {
        $result = $this->DgworkService->dgworkMove($this->request->all());
        return $this->returnResult($result);
    }
    public function getZjPoint()
    {
        $result = $this->DgworkService->getZjPoint($this->request->all());
        return $this->returnResult($result);
    }















}
