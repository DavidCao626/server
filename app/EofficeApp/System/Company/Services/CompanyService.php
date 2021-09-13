<?php

namespace App\EofficeApp\System\Company\Services;

use Lang;
use App\EofficeApp\Base\BaseService;
use Cache;

/**
 * 公司信息Service类:提供公司信息相关服务
 *
 * @author qishaobo
 *
 * @since  2015-10-20 创建
 */
class CompanyService extends BaseService
{
    /**
     * 公司信息资源
     *
     * @var object
     */
    private $companyRepository;

    public function __construct() {
        $this->companyRepository = 'App\EofficeApp\System\Company\Repositories\CompanyRepository';
        $this->userRepository = 'App\EofficeApp\User\Repositories\UserRepository';
    }

    /**
     * 获取公司信息
     *
     * @return array  查询结果或错误码
     *
     * @author qishaobo
     *
     * @since  2015-10-20 创建
     */
    public function getCompanyDetail()
    {
        $userCount = app($this->userRepository)->getUserManageListTotal(['search' => ['dept_id' => [0]]]);
        if(Cache::has('eoffice:company:1')) {
            $response =  Cache::get('eoffice:company:1');
             $response['user_total'] = $userCount;
             return $response;
        } else {
            $where = ['company_id' => [1]];
            if ($result = app($this->companyRepository)->getCompanyDetail($where)) {
                $response = $result->toArray();
                $response['user_total'] = $userCount;
                Cache::forever('eoffice:company:1', $response);
                return $response;
            }

            return '';
        }
    }

    /**
     * 新建公司信息数据
     *
     * @param  array     $data 保存数据
     *
     * @return int|array 新添加的公司信息id|错误码
     *
     * @author qishaobo
     *
     * @since  2015-10-20 创建
     */
    public function createCompany($data)
    {
        $where = ['company_id' => [1]];
        Cache::forget('eoffice:company:1');
        if ($result = app($this->companyRepository)->getCompanyDetail($where)) {
            if (app($this->companyRepository)->updateData($data, $where)) {
                return true;
            }
        }else {
            if (app($this->companyRepository)->insertData($data)) {
                return true;
            }
        }
        return ['code' => ['0x014001', 'company']];
    }

    /**
     * 编辑公司信息数据
     *
     * @param  array     $data 编辑数据
     *
     * @return int|array 新添加的公司信息id|错误码
     *
     * @author qishaobo
     *
     * @since  2015-10-20 创建
     */
    public function editCompany($data)
    {
        $where = ['company_id' => [1]];
        Cache::forget('eoffice:company:1');
        if (app($this->companyRepository)->updateData($data, $where)) {
            return true;
        }

        return ['code' => ['0x014002', 'company']];
    }

}
