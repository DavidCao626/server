<?php

namespace App\EofficeApp\System\Address\Services;

use App\EofficeApp\Base\BaseService;
use App\EofficeApp\System\Address\Repositories\CityRepository;
use App\EofficeApp\System\Address\Repositories\ProvinceRepository;
use DB;

class AddressService extends BaseService
{

    public function __construct()
    {
        parent::__construct();
        $this->cityRepository     = 'App\EofficeApp\System\Address\Repositories\CityRepository';
        $this->districtRepository = 'App\EofficeApp\System\Address\Repositories\DistrictRepository';
        $this->provinceRepository = 'App\EofficeApp\System\Address\Repositories\ProvinceRepository';

    }

    public function getProvinceList($data = array())
    {
        $param = $this->parseParams($data);
        $name  = isset($param['search']) && isset($param['search']['multiSearch']) && isset($param['search']['multiSearch']['province_name']) ? $param['search']['multiSearch']['province_name'][0] : '';
        if ($name) {
            $ids = app('App\EofficeApp\Lang\Services\LangService')->getEntityIdsLikeColumnName('province', 'province_name', 'province_name', $name, function ($item) {
                list($null, $id) = explode('province_', $item);
                return $id;
            });
            $param['search']['multiSearch']['province_id'] = [$ids, 'in'];
            $data['search']                                = json_encode($param['search']);
        }
        return app($this->provinceRepository)->getProvinceList($data);
    }

    public function getIndexCity($data = array())
    {
        return app($this->cityRepository)->getCityList($data);
    }

    public function getProvinceDetail($provinceId)
    {
        $result = app($this->provinceRepository)->getProvinceDetail($provinceId);
        if (empty($result)) {
            return $result;
        }
        $result['province_name'] = mulit_trans_dynamic("province.province_name." . $result['province_name']);
        return $result;
    }

    public function showCity($id)
    {
        $result = app($this->cityRepository)->showCity($id);
        if (empty($result)) {
            return $result;
        }
        $result['city_name'] = mulit_trans_dynamic("city.city_name." . $result['city_name']);
        return $result;
    }

    public function showDistrict($id)
    {
        $result = app($this->districtRepository)->showDistrict($id);
        if (empty($result)) {
            return $result;
        }
        $result['district_name'] = mulit_trans_dynamic("district.district_name." . $result['district_name']);
        return $result;
    }

    public function showAreas(array $input)
    {
        $params     = isset($input['params']) ? json_decode($input['params'], true) : [];
        $provinceId = $params['province_id'] ?? 0;
        $cityId     = $params['city_id'] ?? 0;
        $districtId = $params['district_id'] ?? 0;
        $result     = [];
        if (empty($params)) {
            return $result;
        }
        if ($provinceId) {
            $result['province'] = $this->getProvinceDetail($provinceId);
        }
        if ($cityId) {
            $result['city'] = $this->showCity($cityId);
        }
        if ($districtId) {
            $result['district'] = $this->showDistrict($districtId);
        }
        return $result;
    }

    public function getCityList($provinceId, $data = [])
    {
        $param           = $data;
        $param['search'] = ['province_id' => [$provinceId]];
        if (isset($data['search']) && !is_array($data['search'])) {
            $searchs = json_decode($data['search'], true);
            $name    = isset($searchs['multiSearch']) && isset($searchs['multiSearch']['city_name']) ? $searchs['multiSearch']['city_name'][0] : '';
            if ($name) {
                $ids = app('App\EofficeApp\Lang\Services\LangService')->getEntityIdsLikeColumnName('city', 'city_name', 'city_name', $name, function ($item) {
                    list($null, $id) = explode('city_', $item);
                    return $id;
                });
                $searchs['multiSearch']['city_id'] = [$ids, 'in'];
            }
            $param['search'] = array_merge($param['search'], array_filter($searchs));
        }
        return app($this->cityRepository)->getCityList($param);
    }

    public function getCityDetail($cityId)
    {
        return app($this->cityRepository)->getCityDetail($cityId);
    }

    public function getDistrictList($cityId, $data = [])
    {
        $param           = $data;
        $param['search'] = ['city_id' => [$cityId]];
        if (isset($data['search']) && !is_array($data['search'])) {
            $searchs = json_decode($data['search'], true);
            $name    = isset($searchs['multiSearch']) && isset($searchs['multiSearch']['district_name']) ? $searchs['multiSearch']['district_name'][0] : '';
            if ($name) {
                $ids = app('App\EofficeApp\Lang\Services\LangService')->getEntityIdsLikeColumnName('district', 'district_name', 'district_name', $name, function ($item) {
                    list($null, $id) = explode('district_', $item);
                    return $id;
                });
                $searchs['multiSearch']['district_id'] = [$ids, 'in'];
            }
            $param['search'] = array_merge($param['search'], array_filter($searchs));
        }
        return app($this->districtRepository)->getDistrictList($param);
    }

    public static function listIdByFullName($addressText)
    {
        $province = $city = 0;
        if (strpos($addressText, '-') === -1) {
            return [$province, $city];
        }
        $cityFullNameSplit = explode('-', $addressText);
        if (count($cityFullNameSplit) == 2) {
            $provinceName = $cityFullNameSplit[0];
            $cityName     = $cityFullNameSplit[1] == '' ? $provinceName : $cityFullNameSplit[1];
            if ($provinceName && $cityName) {
                $cityKey     = DB::table('lang_zh_cn')->where('table', 'city')->where('lang_value', 'like', '%' . $cityName . '%')->value('lang_key');
                if (!$cityKey) {
                    $districtKey = DB::table('lang_zh_cn')->where('table', 'district')->where('lang_value', 'like', $cityName . '%')->value('lang_key');
                    $city = DB::table('district')->where('district_name', $districtKey)->value('city_id');
                } else {
                    $city     = DB::table('city')->where('city_name', $cityKey)->value('city_id');
                }
                $provinceKey = DB::table('lang_zh_cn')->where('table', 'province')->where('lang_value', 'like', '%' . $provinceName . '%')->value('lang_key');
                if ($provinceKey) {
                    $province = DB::table('province')->where('province_name', $provinceKey)->value('province_id');
                }
            }
        }
        return [$province, $city];
    }

    public static function getProvinceName($provinceId)
    {
        if (!$provinceId) {
            return '';
        }
        $key = DB::table('province')->where('province_id', $provinceId)->value('province_name');
        return mulit_trans_dynamic("province.province_name." . $key);
    }

    public static function getCityName($cityId)
    {
        if (!$cityId) {
            return '';
        }
        $key = DB::table('city')->where('city_id', $cityId)->value('city_name');
        return mulit_trans_dynamic("city.city_name." . $key);
    }
}
