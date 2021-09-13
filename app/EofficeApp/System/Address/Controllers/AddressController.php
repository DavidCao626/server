<?php

namespace App\EofficeApp\System\Address\Controllers;

use App\EofficeApp\Base\Controller;
use App\EofficeApp\System\Address\Services\AddressService;
use Illuminate\Http\Request;

class AddressController extends Controller
{

    public function __construct(
        Request $request,
        AddressService $addressService
    ) {
        parent::__construct();
        $this->request        = $request;
        $this->addressService = $addressService;
    }


    public function getProvince($provinceId)
    {
        $result = $this->addressService->getProvinceDetail($provinceId);
        return $this->returnResult($result);
    }

    public function showDistrict($id)
    {
        $result = $this->addressService->showDistrict($id);
        return $this->returnResult($result);
    }

    public function showAreas()
    {
        $input  = $this->request->all();
        $result = $this->addressService->showAreas($input);
        return $this->returnResult($result);
    }

    public function showCity($id)
    {
        $result = $this->addressService->showCity($id);
        return $this->returnResult($result);
    }

    public function getIndexProvince()
    {
        $input  = $this->request->all();
        $result = $this->addressService->getProvinceList($input);
        return $this->returnResult($result);
    }

    public function getIndexCity()
    {
        $input  = $this->request->all();
        $result = $this->addressService->getIndexCity($input);
        return $this->returnResult($result);
    }

    public function getProvinceCity($provinceId, $cityId)
    {
        $result = $this->addressService->getCityDetail($cityId);
        return $this->returnResult($result);
    }

    /**
     * 根据省获取市列表
     *
     *
     * @apiTitle 获取市列表
     * @success {boolean} status(1) 接入成功
     * @successExample {json} Success-Response:
     * {
     *      "status": 1,
     * }
     *
     * @error {boolean} status(0) 接入失败
     * @error {array} errors 接入失败错误信息
     *
     * @errorExample {json} Error-Response:
     * { "status": 0,"errors":[{"code":"0x000003","message":"未知错误"}] }
     */

    public function getIndexProvinceCity($provinceId)
    {
        $input  = $this->request->all();
        $result = $this->addressService->getCityList($provinceId, $input);
        return $this->returnResult($result);
    }

    public function getCityDistrict($cityId)
    {
        $input  = $this->request->all();
        $result = $this->addressService->getDistrictList($cityId, $input);
        return $this->returnResult($result);
    }

}
