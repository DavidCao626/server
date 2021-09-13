<?php

namespace App\EofficeApp\EofficeCase\Controllers;

use App\EofficeApp\Base\Controller;
use App\EofficeApp\EofficeCase\Services\EofficeCaseService;
use Illuminate\Http\Request;

/**
 * EofficeCaseController
 */
class EofficeCaseController extends Controller
{
    private $eofficeCaseService;
    public function __construct(
        EofficeCaseService $eofficeCaseService
    ) {
        $this->eofficeCaseService = $eofficeCaseService;
        parent::__construct();
    }

    /**
     * 导入案例
     */
    public function importEofficeCase(Request $request)
    {
        $own = $this->own;
        if (!$own || !isset($own['user_id']) || $own['user_id'] != 'admin') {
            // 没有权限
            return $this->returnResult(['code' => ['0x000006', 'common']]);
        }
        return $this->returnResult($this->eofficeCaseService->importEofficeCase($request->all()));
    }

    /**
     * 删除案例
     */
    public function deleteEofficeCase(Request $request)
    {
        $own = $this->own;
        if (!$own || !isset($own['user_id']) || $own['user_id'] != 'admin') {
            // 没有权限
            return $this->returnResult(['code' => ['0x000006', 'common']]);
        }
        return $this->returnResult($this->eofficeCaseService->deleteEofficeCase($request->all()));
    }

    /**
     * 导出eoffice案例
     */
    public function exportEofficeCase(Request $request)
    {
        $own = $this->own;
        if (!$own || !isset($own['user_id']) || $own['user_id'] != 'admin') {
            // 没有权限
            return $this->returnResult(['code' => ['0x000006', 'common']]);
        }
        $casePlaform = envOverload('CASE_PLATFORM', false) ?? false;
        if ($casePlaform === false) {
            // 没有权限
            return $this->returnResult(['code' => ['0x000006', 'common']]);
        }
        $requestArray = $request->all();
        return $this->returnResult($this->eofficeCaseService->exportEofficeCase($requestArray['case_id'], $requestArray));
    }


    /**
     * 开启案例IM服务
     */
    public function openIMServer(Request $request)
    {
        return $this->returnResult($this->eofficeCaseService->openIMServer($request->all()));
    }
}
