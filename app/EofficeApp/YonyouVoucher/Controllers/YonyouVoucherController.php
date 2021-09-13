<?php
namespace App\EofficeApp\YonyouVoucher\Controllers;

use App\EofficeApp\Base\Controller;
use Illuminate\Http\Request;

/**
 * U8凭证集成 controller
 *
 * @author dp
 *
 * @since  2019-09-12 创建
 */
class YonyouVoucherController extends Controller
{
    /** @var object U8凭证集成 */
    private $yonyouVoucherService;

    public function __construct(
        Request $request
    ) {
        parent::__construct();
        $userInfo = $this->own;
        // 用户id
        $this->userId               = $userInfo['user_id'];
        $this->yonyouVoucherService = 'App\EofficeApp\YonyouVoucher\Services\YonyouVoucherService';
        $this->request              = $request;
    }

    /**
     * 【公司】为前端的基本配置-公司配置列表提供数据
     * @author [dingpeng]
     * @return [object] [description]
     */
    public function getCompanyConfig()
    {
        $data = $this->request->all();
        return $this->returnResult(app($this->yonyouVoucherService)->getCompanyConfig($data));
    }

    public function getOneCompanyConfig($companyId)
    {
        $data = $this->request->all();
        return $this->returnResult(app($this->yonyouVoucherService)->getOneCompanyConfig($data, $companyId));
    }

    /**
     * 【公司】新建公司配置
     * @author [dingpeng]
     * @return [object] [description]
     */
    public function addCompanyConfig()
    {
        $data = $this->request->all();
        return $this->returnResult(app($this->yonyouVoucherService)->addCompanyConfig($data));
    }

    /**
     * 【公司】编辑公司配置
     * @author [dingpeng]
     * @return [object] [description]
     */
    public function modifyCompanyConfig($companyId)
    {
        $data = $this->request->all();
        return $this->returnResult(app($this->yonyouVoucherService)->modifyCompanyConfig($data, $companyId));
    }

    /**
     * 【公司】编辑公司配置
     * @author [dingpeng]
     * @return [object] [description]
     */
    public function deleteCompanyConfig($companyId)
    {
        $data = $this->request->all();
        return $this->returnResult(app($this->yonyouVoucherService)->deleteCompanyConfig($data, $companyId));
    }

    /**
     * 【公司】表单控件，解析公司列表
     * @author [dingpeng]
     * @return [object] [description]
     */
    public function getCompanyConfigSelect()
    {
        $data = $this->request->all();
        return $this->returnResult(app($this->yonyouVoucherService)->getCompanyConfigSelect($data));
    }

    /**
     * 【公司】【科目】科目数据来源，上传附件，解析附件excel的表头（前端作为科目编码/名称下拉框的待选项）
     * @author [dingpeng]
     * @return [object] [description]
     */
    public function getCourseUploadExcelHeader()
    {
        $data = $this->request->all();
        return $this->returnResult(app($this->yonyouVoucherService)->getCourseUploadExcelHeader($data));
    }

    /**
     * 【凭证配置】获取U8凭证配置主表信息
     * @author [dingpeng]
     * @return [object] [description]
     */
    public function getVoucherMainConfig()
    {
        $data = $this->request->all();
        return $this->returnResult(app($this->yonyouVoucherService)->getVoucherMainConfig($data));
    }

    /**
     * 【凭证配置】新建U8凭证配置主表信息
     * @author [dingpeng]
     * @return [object] [description]
     */
    public function addVoucherMainConfig()
    {
        $data = $this->request->all();
        return $this->returnResult(app($this->yonyouVoucherService)->addVoucherMainConfig($data));
    }

    /**
     * 【凭证配置】编辑U8凭证配置主表信息
     * @author [dingpeng]
     * @return [object] [description]
     */
    public function modifyVoucherMainConfig($voucherConfigId)
    {
        $data = $this->request->all();
        return $this->returnResult(app($this->yonyouVoucherService)->modifyVoucherMainConfig($data, $voucherConfigId));
    }

    /**
     * 【凭证配置】删除U8凭证配置主表信息
     * @author [dingpeng]
     * @return [object] [description]
     */
    public function deleteVoucherMainConfig($voucherConfigId)
    {
        $data = $this->request->all();
        return $this->returnResult(app($this->yonyouVoucherService)->deleteVoucherMainConfig($data, $voucherConfigId));
    }

    /**
     * 【字段配置】获取字段配置信息，要用param传借/贷类型
     * @return [object] [description]
     * @author [dingpeng]
     */
    public function getVoucherFieldConfig($voucherConfigId)
    {
        $data = $this->request->all();
        return $this->returnResult(app($this->yonyouVoucherService)->getVoucherFieldConfig($data, $voucherConfigId));
    }

    /**
     * 【字段配置】保存字段配置信息，要用param传借/贷类型
     * @param $voucherConfigId
     * @return \App\EofficeApp\Base\json
     * @author [dosy]
     */

    public function modifyVoucherFieldConfig($voucherConfigId)
    {
        $data = $this->request->all();
        return $this->returnResult(app($this->yonyouVoucherService)->modifyVoucherFieldConfig($data, $voucherConfigId));
    }

    /**
     * 【凭证配置】获取U8凭证配置信息
     * @param $voucherConfigId
     * @return \App\EofficeApp\Base\json
     * @author [dosy]
     */
    public function getVoucherConfig($voucherConfigId)
    {
        $data = $this->request->all();
        return $this->returnResult(app($this->yonyouVoucherService)->getVoucherConfig($data, $voucherConfigId));
    }

    /**
     * 【日志】获取U8操作日志列表
     *
     * @return [Array] [description]
     */
    public function getVoucherLogList()
    {
        $data = $this->request->all();
        return $this->returnResult(app($this->yonyouVoucherService)->getVoucherLogList($data));
        // return $this->returnResult(app($this->yonyouVoucherService)->addLog('测试操作', '正常', ['operate_data' => '正常记录', 'flow_id' => '正常记录', 'run_id' => '正常记录'], '无'));
    }

    /**
     * 【日志】获取U8操作日志详情
     *
     * @return [object] [log]
     */
    public function getVoucherLogDetail($logId)
    {
        return $this->returnResult(app($this->yonyouVoucherService)->getVoucherLogDetail($logId));
    }

    /**
     * 【公司】获取外部数据库公司配置
     * @return \App\EofficeApp\Base\json
     * @author [dosy]
     */
    public function getCompanyInitConfigFromU8System()
    {
        $data = $this->request->all();
        return $this->returnResult(app($this->yonyouVoucherService)->getCompanyInitConfigFromU8System($data));
    }
    /**
     * 【表单控件】 获取科目数组
     *
     * @param [type] $companyId
     *
     * @return void
     * @author yuanmenglin
     * @since
     */
    public function getCodeConfigSelect()
    {
        $data = $this->request->all();
        return $this->returnResult(app($this->yonyouVoucherService)->getCodeConfigSelect($data));
    }
    /**
     * 【表单控件】 获取科目辅助核算项
     *
     * @return void
     * @author yuanmenglin
     * @since
     */
    public function getAuxiliaryConfigSelect()
    {
        $data = $this->request->all();
        return $this->returnResult(app($this->yonyouVoucherService)->getAuxiliaryConfigSelect($data));
    }
    /**
     * 获取科目类型
     *
     * @return void
     * @author yuanmenglin
     * @since
     */
    public function getCodeTypes()
    {
        $data = $this->request->all();
        return $this->returnResult(app($this->yonyouVoucherService)->getCodeTypes($data));
    }
    /**
     * 获取科目年度
     *
     * @return void
     * @author yuanmenglin
     * @since
     */
    public function getCodeIyears()
    {
        $data = $this->request->all();
        return $this->returnResult(app($this->yonyouVoucherService)->getCodeIyears($data));
    }

    /**获取默认表单所需U8账套数据
     * @return array
     */
    public function previewDefaultSource()
    {
        $data = $this->request->all();
        return $this->returnResult(app($this->yonyouVoucherService)->previewDefaultSource($data));
    }

    /**解析表单数据，获取凭证预览所需数据
     * @return array
     */
    public function previewVoucherData()
    {
        $data = $this->request->all();
        return $this->returnResult(app($this->yonyouVoucherService)->previewVoucherData($data));
    }
}
