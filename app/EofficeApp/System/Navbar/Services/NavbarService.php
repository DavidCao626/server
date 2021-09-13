<?php

namespace App\EofficeApp\System\Navbar\Services;

use App\EofficeApp\Base\BaseService;
use DB;
use Schema;

class NavbarService extends BaseService
{

    public function __construct()
    {
        parent::__construct();
        $this->navbarRepository = 'App\EofficeApp\System\Navbar\Repositories\NavbarRepository';

    }

    public function getNavbar($params)
    {
        $data = app($this->navbarRepository)->getAllheaders();
        foreach ($data as $key => $value) {
            $data[$key]['navbar_name'] = mulit_trans_dynamic("navbar_guid.navbar_name." .$value['navbar_name']);
        }
        return $data;
    }
    public function setNavbar($data) {
        if (empty($data)) {
            return ['code' => ['0x000003', 'common']];
        }
        // 清除表
        if (Schema::hasTable('navbar_guid')) {
            DB::Table('navbar_guid')->truncate();
        }
        foreach ($data as $key => $value) {
            $data[$key]['navbar_name'] = 'navbar_name_'.$value['id'];
        }
        return app($this->navbarRepository)->insertMultipleData($data);
    }
}
