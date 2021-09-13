<?php

namespace App\EofficeApp\System\Board\Controllers;

use App\EofficeApp\Base\Controller;
use App\EofficeApp\System\Board\Services\BoardService;
use Illuminate\Http\Request;

class BoardController extends Controller
{

    public function __construct(
        Request $request,
        BoardService $boardService
    ) {
        parent::__construct();
        $this->request        = $request;
        $this->boardService = $boardService;
    }


    public function getBoard()
    {
        $result = $this->boardService->getBoard($this->request->all());
        return $this->returnResult($result);
    }

    public function checkPermission()
    {
        $result = $this->boardService->checkPermission($this->request->all());
        return $this->returnResult($result);
    }
    public function parseData()
    {
        $result = $this->boardService->parseData($this->request->all());
        return $this->returnResult($result);
    }



}
