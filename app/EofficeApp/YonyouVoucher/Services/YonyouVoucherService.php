<?php
namespace App\EofficeApp\YonyouVoucher\Services;

use App\EofficeApp\Base\BaseService;
use Exception;

/**
 * U8集成 service
 */
class YonyouVoucherService extends BaseService
{
    public function __construct()
    {
        parent::__construct();
        $this->attachmentService = 'App\EofficeApp\Attachment\Services\AttachmentService';
        $this->importExportService = 'App\EofficeApp\ImportExport\Services\ImportExportService';
        $this->userService = 'App\EofficeApp\User\Services\UserService';
        // 抽象表，凭证集成的基本信息配置，包括，凭证类型，支持版本，数据库配置，数据库模式(规划中)
        $this->voucherIntergrationBaseInfoRepository = 'App\EofficeApp\YonyouVoucher\Repositories\VoucherIntergrationBaseInfoRepository';
        // 凭证集成，u8，公司配置表
        $this->voucherIntergrationU8CompanyConfigRepository = 'App\EofficeApp\YonyouVoucher\Repositories\VoucherIntergrationU8CompanyConfigRepository';
        // 凭证集成，u8，公司配置表的子表，记录database类型的科目配置
        $this->voucherIntergrationU8CourseDatabaseConfigRepository = 'App\EofficeApp\YonyouVoucher\Repositories\VoucherIntergrationU8CourseDatabaseConfigRepository';
        // 凭证集成，u8，公司配置表的子表，记录select类型的科目
        $this->voucherIntergrationU8CourseSelectConfigRepository = 'App\EofficeApp\YonyouVoucher\Repositories\VoucherIntergrationU8CourseSelectConfigRepository';
        // 凭证集成，u8，公司配置表的子表，记录upload类型的科目的配置信息
        $this->voucherIntergrationU8CourseUploadConfigRepository = 'App\EofficeApp\YonyouVoucher\Repositories\VoucherIntergrationU8CourseUploadConfigRepository';
        // 凭证集成，u8，公司配置表的子表，记录upload类型的科目配置，解析之后的科目信息
        $this->voucherIntergrationU8CourseUploadParseRepository = 'App\EofficeApp\YonyouVoucher\Repositories\VoucherIntergrationU8CourseUploadParseRepository';
        // 凭证集成，u8，凭证配置主表，主要配置基本信息和主表字段关联（hasMany field_config）
        $this->voucherIntergrationU8MainConfigRepository = 'App\EofficeApp\YonyouVoucher\Repositories\VoucherIntergrationU8MainConfigRepository';
        // 凭证集成，u8，凭证配置，字段配置表，主要配置借方贷方分录字段关联
        $this->voucherIntergrationU8FieldConfigRepository = 'App\EofficeApp\YonyouVoucher\Repositories\VoucherIntergrationU8FieldConfigRepository';
        // 凭证集成，u8，凭证和运行流程关联表
        $this->voucherIntergrationU8RelationFlowRunRepository = 'App\EofficeApp\YonyouVoucher\Repositories\VoucherIntergrationU8RelationFlowRunRepository';
        // 流程信息，flow_type表
        $this->flowTypeRepository = 'App\EofficeApp\Flow\Repositories\FlowTypeRepository';
        // 凭证集成，u8，日志记录表
        $this->voucherIntergrationU8LogRepository = 'App\EofficeApp\YonyouVoucher\Repositories\VoucherIntergrationU8LogRepository';
        // 外部数据库
        $this->externalDatabaseService = 'App\EofficeApp\System\ExternalDatabase\Services\ExternalDatabaseService';
        // 流程信息，flow_表
        $this->flowFormService = 'App\EofficeApp\Flow\Services\FlowFormService';
        // 流程外发
        $this->flowOutsendRepository = 'App\EofficeApp\Flow\Repositories\FlowOutsendRepository';
        // 流程过程
        $this->flowProcessRepository = 'App\EofficeApp\Flow\Repositories\FlowProcessRepository';
    }

    /**
     * 【公司】为前端的基本配置-公司配置列表提供数据
     * @author [dingpeng]
     * @return [object] [description]
     */
    public function getCompanyConfig($params)
    {
        $params = $this->parseParams($params);

        $response = isset($params['response']) ? $params['response'] : 'both';
        $list = [];

        if ($response == 'both' || $response == 'count') {
            $count = app($this->voucherIntergrationU8CompanyConfigRepository)->getCount($params);
        }

        if (($response == 'both' && $count > 0) || $response == 'data') {
            foreach (app($this->voucherIntergrationU8CompanyConfigRepository)->getList($params) as $new) {
                $list[] = $new;
            }
        }

        return $response == 'both' ? ['total' => $count, 'list' => $list] : ($response == 'data' ? $list : $count);
    }
    /**
     * 获取单个公司的配置信息
     *
     * @return void
     * @author yuanmenglin
     * @since
     */
    public function getOneCompanyConfig($data, $companyId)
    {
        // 获取公司信息
        $company = app($this->voucherIntergrationU8CompanyConfigRepository)->getOneFieldInfo(['company_id' => [$companyId]]);
        $company = $company ? $company->toArray() : [];
        // 获取借方科目数据
        $debitWhere = ['company_id' => [$companyId], 'course_type' => ['debit']];
        // database
        $debitDatabase = app($this->voucherIntergrationU8CourseDatabaseConfigRepository)->getOneFieldInfo($debitWhere);
        // upload
        $debitUpload = app($this->voucherIntergrationU8CourseUploadConfigRepository)->getOneFieldInfo($debitWhere);
        $debitUploadCourses = app($this->voucherIntergrationU8CourseUploadParseRepository)->getFieldInfo($debitWhere);
        // select
        $debitSelect = app($this->voucherIntergrationU8CourseSelectConfigRepository)->getFieldInfo($debitWhere);
        // 获取贷方科目数据
        $creditWhere = ['company_id' => [$companyId], 'course_type' => ['credit']];
        // database
        $creditDatabase = app($this->voucherIntergrationU8CourseDatabaseConfigRepository)->getOneFieldInfo($creditWhere);
        // upload
        $creditUpload = app($this->voucherIntergrationU8CourseUploadConfigRepository)->getOneFieldInfo($creditWhere);
        $creditUploadCourses = app($this->voucherIntergrationU8CourseUploadParseRepository)->getFieldInfo($creditWhere);
        // select
        $creditSelect = app($this->voucherIntergrationU8CourseSelectConfigRepository)->getFieldInfo($creditWhere);
        return compact('company', 'debitDatabase', 'creditDatabase', 'debitUpload', 'creditUpload', 'debitSelect', 'creditSelect', 'creditUploadCourses', 'debitUploadCourses');
    }

    /**
     * 【公司】新建公司配置
     * @author [dingpeng]
     * @return [object] [description]
     */
    public function addCompanyConfig($param)
    {
        $company = $param['company'] ?? [];
        $debitCourseType = $company['debit_course_type'] ?? '';
        $creditCourseType = $company['credit_course_type'] ?? '';
        $account = $company['company_account_name'] ?? '';
        if (!$company || !$debitCourseType || !$creditCourseType) {
            // 参数异常  请检查公司基础信息和借贷方科目编码
            return ['code' => ['company_param_error', 'integrationCenter']];
        }
        unset($company['company_id']);
        // 保存公司基本配置 对应voucher_intergration_u8_company_config
        $companyRes = app($this->voucherIntergrationU8CompanyConfigRepository)->insertData($company);
        $operation = trans('integrationCenter.log.create_company') . ':' . $company['company_name'];
        if (!$companyRes) {
            // 公司信息保存失败
            // $this->addLog($operation, trans('integrationCenter.log.operate_failed'));
            return ['code' => ['save_failed', 'integrationCenter']];
        }
        $companyId = $companyRes->company_id;
        // 借方科目编码
        switch ($debitCourseType) {
            case 'database':
                // 外部数据库 保存借方科目编码 对应表voucher_intergration_u8_course_database_config
                $debit = $param['debit'] ?? [];
                $codeIdField = $debit['code_id_field'] ?? '';
                $codeNameField = $debit['code_name_field'] ?? '';
                if (!$codeIdField && !$codeNameField) {
                    // 类型为数据库源但是为选择数据表及对应字段时取默认的code表
                    $debit['code_id_field'] = $codeIdField = 'ccode';
                    $debit['code_name_field'] = $codeNameField = 'ccode_name';
                    $debit['database_table'] = 'code';
                }
                if ($debit && $codeIdField && $codeNameField) {
                    $debit['company_id'] = $companyId;
                    $debit['course_type'] = 'debit';
                    $debitRes = app($this->voucherIntergrationU8CourseDatabaseConfigRepository)->insertData($debit);
                }
                break;
            case 'upload':
                // 上传文件 保存借方科目编码 对应表voucher_intergration_u8_course_upload_parse
                $debitUpload = $param['debit_upload'] ?? [];
                $debitUploadattachmentId = $param['debit_upload_attachment'] ?? '';
                if ($debitUpload) {
                    $codeId = $debitUpload['code_id_field'] ?? '';
                    $codeName = $debitUpload['code_name_field'] ?? '';
                    if ($debitUpload && $codeId && $codeName && $debitUploadattachmentId) {
                        $debitUpload['attachment_id'] = $debitUploadattachmentId && $debitUploadattachmentId[0] ? $debitUploadattachmentId[0] : '';
                        $debitUpload['company_id'] = $companyId;
                        $debitUpload['course_type'] = 'debit';
                        // 保存配置
                        $debitRes = app($this->voucherIntergrationU8CourseUploadConfigRepository)->insertData($debitUpload);
                        // 解析上传的附件将表格信息插入数据库
                        $this->saveUploadCourse($debitUploadattachmentId, $codeId, $codeName, $companyId, 'debit');
                    }
                }
                break;
            case 'select':
                // 手动维护 保存借方科目编码 对应表voucher_intergration_u8_course_select_config
                $debitSelect = $param['debit_select'] ?? [];
                if ($debitSelect) {
                    foreach ($debitSelect as $selectKey => $selectVal) {
                        if ($selectVal) {
                            $debitSelect[$selectKey]['company_id'] = $companyId;
                            $debitSelect[$selectKey]['course_type'] = 'debit';
                        }
                    }
                    $debitRes = app($this->voucherIntergrationU8CourseSelectConfigRepository)->insertMultipleData($debitSelect);
                }
                break;
            default:
                break;
        }

        // 贷方科目编码
        switch ($creditCourseType) {
            case 'database':
                // 外部数据库 保存贷方科目编码 对应表voucher_intergration_u8_course_database_config
                $credit = $param['credit'] ?? [];
                $codeIdField = $credit['code_id_field'] ?? '';
                $codeNameField = $credit['code_name_field'] ?? '';
                if (!$codeIdField && !$codeNameField) {
                    // 类型为数据库源但是为选择数据表及对应字段时取默认的code表
                    $credit['code_id_field'] = $codeIdField = 'ccode';
                    $credit['code_name_field'] = $codeNameField = 'ccode_name';
                    $credit['database_table'] = 'code';
                }
                if ($credit && $codeIdField && $codeNameField) {
                    $credit['company_id'] = $companyId;
                    $credit['course_type'] = 'credit';
                    $creditRes = app($this->voucherIntergrationU8CourseDatabaseConfigRepository)->insertData($credit);
                }
                break;
            case 'upload':
                // 上传文件 保存贷方科目编码 对应表voucher_intergration_u8_course_upload_parse
                $creditUpload = $param['credit_upload'] ?? [];
                $creditUploadattachmentId = $param['credit_upload_attachment'] ?? '';
                if ($creditUpload) {
                    $codeId = $creditUpload['code_id_field'] ?? '';
                    $codeName = $creditUpload['code_name_field'] ?? '';
                    if ($creditUpload && $codeId && $codeName && $creditUploadattachmentId) {
                        $creditUpload['attachment_id'] = $creditUploadattachmentId && $creditUploadattachmentId[0] ? $creditUploadattachmentId[0] : '';
                        $creditUpload['company_id'] = $companyId;
                        $creditUpload['course_type'] = 'credit';
                        // 保存配置
                        $creditRes = app($this->voucherIntergrationU8CourseUploadConfigRepository)->insertData($creditUpload);
                        // 解析上传的附件将表格信息插入数据库
                        $this->saveUploadCourse($creditUploadattachmentId, $codeId, $codeName, $companyId, 'credit');
                    }
                }
                break;
            case 'select':
                // 手动维护 保存贷方科目编码 对应表voucher_intergration_u8_course_select_config
                $creditSelect = $param['credit_select'] ?? [];
                if ($creditSelect) {
                    foreach ($creditSelect as $selectKey => $selectVal) {
                        if ($selectVal) {
                            $creditSelect[$selectKey]['company_id'] = $companyId;
                            $creditSelect[$selectKey]['course_type'] = 'credit';
                        }
                    }
                    $creditRes = app($this->voucherIntergrationU8CourseSelectConfigRepository)->insertMultipleData($creditSelect);
                }
                break;
            default:
                break;
        }
        // 日志记录
        // $this->addLog($operation, trans('integrationCenter.log.operate_success'));
        return true;

    }

    /**
     * 【公司】编辑公司配置
     * @author [dingpeng]
     * @return [object] [description]
     */
    public function modifyCompanyConfig($param, $companyId)
    {
        $company = $param['company'] ?? [];
        $debitCourseType = $company['debit_course_type'] ?? '';
        $creditCourseType = $company['credit_course_type'] ?? '';
        if (!$company || !$companyId) {
            // 参数异常  请检查公司基础信息和借贷方科目编码
            return ['code' => ['company_param_error', 'integrationCenter']];
        }
        // 保存公司基本配置 对应voucher_intergration_u8_company_config
        $company = array_intersect_key($company, array_flip(app($this->voucherIntergrationU8CompanyConfigRepository)->getTableColumns()));
        // $originCompany = app($this->voucherIntergrationU8CompanyConfigRepository)->getDetail($companyId);
        $setDefault = $company['set_default'] ?? 0;
        $enabledToggle = $company['enabled_toggle'] ?? 1;
        // 启用和默认属性互斥
        if ($setDefault == 1 && $enabledToggle == 0) {
            // 未启用不能设为默认
            return ['code' => ['default_enable_check', 'integrationCenter']];
        }
        $companyRes = app($this->voucherIntergrationU8CompanyConfigRepository)->updateData($company, ['company_id' => [$companyId]]);
        // 设置默认 将其他配置都设置非默认
        if ($setDefault == 1) {
            app($this->voucherIntergrationU8CompanyConfigRepository)->updateData(['set_default' => 0], ['company_id' => [$companyId, '<>'], 'set_default' => [1]]);
        }
        // 公司名称 --选用及设为默认时没有需重新查询
        $company_name = $company['company_name'] ?? '';
        if (!$company_name) {
            $company_name = app($this->voucherIntergrationU8CompanyConfigRepository)->getFieldValue('company_name', ['company_id' => [$companyId]]);
        }
        $operation = trans('integrationCenter.log.edit_company') . ':' . $company_name;
        if (!$companyRes) {
            // 公司信息保存失败
            // $this->addLog($operation, trans('integrationCenter.log.operate_failed'));
            return ['code' => ['save_failed', 'integrationCenter']];
        }
        $deleteDebitWhere = [
            'company_id' => [$companyId],
            'course_type' => ['debit'],
        ];
        // 借方科目编码
        switch ($debitCourseType) {
            case 'database':
                // 外部数据库 保存借方科目编码 对应表voucher_intergration_u8_course_database_config
                $debit = $param['debit'] ?? [];
                $codeIdField = $debit['code_id_field'] ?? '';
                $codeNameField = $debit['code_name_field'] ?? '';
                if ($debit && $codeIdField && $codeNameField) {
                    $debit['company_id'] = $companyId;
                    $debit['course_type'] = 'debit';
                    $debit = array_intersect_key($debit, array_flip(app($this->voucherIntergrationU8CourseDatabaseConfigRepository)->getTableColumns()));
                    app($this->voucherIntergrationU8CourseDatabaseConfigRepository)->deleteByWhere($deleteDebitWhere);
                    $debitRes = app($this->voucherIntergrationU8CourseDatabaseConfigRepository)->insertData($debit);
                }
                break;
            case 'upload':
                // 上传文件 保存借方科目编码 对应表voucher_intergration_u8_course_upload_parse
                $debitUpload = $param['debit_upload'] ?? [];
                $debitUploadattachmentId = $param['debit_upload_attachment'] ?? '';
                if ($debitUpload) {
                    $codeId = $debitUpload['code_id_field'] ?? '';
                    $codeName = $debitUpload['code_name_field'] ?? '';
                    if ($debitUpload && $codeId && $codeName && $debitUploadattachmentId) {
                        $debitUpload['attachment_id'] = $debitUploadattachmentId && $debitUploadattachmentId[0] ? $debitUploadattachmentId[0] : '';
                        $debitUpload['company_id'] = $companyId;
                        $debitUpload['course_type'] = 'debit';
                        // 保存配置
                        app($this->voucherIntergrationU8CourseUploadConfigRepository)->deleteByWhere($deleteDebitWhere);
                        $debitRes = app($this->voucherIntergrationU8CourseUploadConfigRepository)->insertData($debitUpload);
                        // 解析上传的附件将表格信息插入数据库
                        $this->saveUploadCourse($debitUploadattachmentId, $codeId, $codeName, $companyId, 'debit', 'edit');
                    }

                }
                break;
            case 'select':
                // 手动维护 保存借方科目编码 对应表voucher_intergration_u8_course_select_config
                $debitSelect = $param['debit_select'] ?? [];
                if ($debitSelect) {
                    $debitSelectNew = [];
                    foreach ($debitSelect as $selectKey => $selectVal) {
                        $codeId = $selectVal['code_id'] ?? '';
                        $codeName = $selectVal['code_name'] ?? '';
                        if ($codeId && $codeName) {
                            $debitSelectNew[] = [
                                'company_id' => $companyId,
                                'course_type' => 'debit',
                                'code_id' => $codeId,
                                'code_name' => $codeName,
                            ];
                        }
                    }
                    app($this->voucherIntergrationU8CourseSelectConfigRepository)->deleteByWhere($deleteDebitWhere);
                    $debitRes = app($this->voucherIntergrationU8CourseSelectConfigRepository)->insertMultipleData($debitSelectNew);
                }
                break;
            default:
                break;
        }
        $deleteCreditWhere = [
            'company_id' => [$companyId],
            'course_type' => ['credit'],
        ];
        // 贷方科目编码
        switch ($creditCourseType) {
            case 'database':
                // 外部数据库 保存贷方科目编码 对应表voucher_intergration_u8_course_database_config
                $credit = $param['credit'] ?? [];
                $codeIdField = $credit['code_id_field'] ?? '';
                $codeNameField = $credit['code_name_field'] ?? '';
                if ($credit && $codeIdField && $codeNameField) {
                    $credit['company_id'] = $companyId;
                    $credit['course_type'] = 'credit';
                    app($this->voucherIntergrationU8CourseDatabaseConfigRepository)->deleteByWhere($deleteCreditWhere);
                    $credit = array_intersect_key($credit, array_flip(app($this->voucherIntergrationU8CourseDatabaseConfigRepository)->getTableColumns()));
                    $creditRes = app($this->voucherIntergrationU8CourseDatabaseConfigRepository)->insertData($credit);
                }
                break;
            case 'upload':
                // 上传文件 保存贷方科目编码 对应表voucher_intergration_u8_course_upload_parse
                $creditUpload = $param['credit_upload'] ?? [];
                $creditUploadattachmentId = $param['credit_upload_attachment'] ?? '';
                if ($creditUpload) {
                    $codeId = $creditUpload['code_id_field'] ?? '';
                    $codeName = $creditUpload['code_name_field'] ?? '';
                    if ($creditUpload && $codeId && $codeName && $creditUploadattachmentId) {
                        $creditUpload['attachment_id'] = $creditUploadattachmentId && $creditUploadattachmentId[0] ? $creditUploadattachmentId[0] : '';
                        $creditUpload['company_id'] = $companyId;
                        $creditUpload['course_type'] = 'credit';
                        // 保存配置
                        app($this->voucherIntergrationU8CourseUploadConfigRepository)->deleteByWhere($deleteCreditWhere);
                        $creditRes = app($this->voucherIntergrationU8CourseUploadConfigRepository)->insertData($creditUpload);
                        // 解析上传的附件将表格信息插入数据库
                        $this->saveUploadCourse($creditUploadattachmentId, $codeId, $codeName, $companyId, 'credit', 'edit');
                    }
                }
                break;
            case 'select':
                // 手动维护 保存贷方科目编码 对应表voucher_intergration_u8_course_select_config
                $creditSelect = $param['credit_select'] ?? [];
                if ($creditSelect) {
                    $creditSelectNew = [];
                    foreach ($creditSelect as $selectKey => $selectVal) {
                        $codeId = $selectVal['code_id'] ?? '';
                        $codeName = $selectVal['code_name'] ?? '';
                        if ($codeId && $codeName) {
                            $creditSelectNew[] = [
                                'company_id' => $companyId,
                                'course_type' => 'credit',
                                'code_id' => $codeId,
                                'code_name' => $codeName,
                            ];
                        }
                    }
                    app($this->voucherIntergrationU8CourseSelectConfigRepository)->deleteByWhere($deleteCreditWhere);
                    $creditRes = app($this->voucherIntergrationU8CourseSelectConfigRepository)->insertMultipleData($creditSelectNew);
                }
                break;
            default:
                break;
        }

        // $this->addLog($operation, trans('integrationCenter.log.operate_success'));
        return true;
    }

    /**
     * 通过附件ID将表格信息保存到数据库
     *
     * @param [type] $uploadattachmentId
     * @param [type] $codeId
     * @param [type] $codeName
     * @param [type] $companyId
     * @param [type] $type
     *
     * @return void
     * @author yuanmenglin
     */
    public function saveUploadCourse($uploadattachmentId, $codeId, $codeName, $companyId, $type, $addOrEdit = '')
    {
        $attachmentList = app($this->attachmentService)->getOneAttachmentById($uploadattachmentId);

        $file = isset($attachmentList['temp_src_file']) ? $attachmentList['temp_src_file'] : '';
        try {
            // 调解析，获取excel内容
            $sheet = app($this->importExportService)->getMulitSheetsData($file, []);
        } catch (\Exception $e) {
            return ['code' => ['course_upload_excel_parse_error', 'integrationCenter']];
        }
        $excelHeader = $this->getCourseUploadExcelHeader(['attachments' => $uploadattachmentId]);
        $excelCodeIdField = $excelHeader[$codeId] ?? '';
        $excelCodeNameField = $excelHeader[$codeName] ?? '';

        // 取数据构建数组 存至数据库
        if (!empty($sheet)) {
            $uploadCourses = [];
            $sheetData = isset($sheet[0]['sheetData']) ? $sheet[0]['sheetData'] : [];
            if ($sheetData) {
                foreach ($sheetData as $itemKey => $itemVal) {
                    $uploadCourses[] = [
                        'company_id' => $companyId,
                        'course_type' => $type,
                        'code_id' => $itemVal[$excelCodeIdField] ?? '',
                        'code_name' => $itemVal[$excelCodeNameField] ?? '',
                    ];
                }
                if ($addOrEdit) {
                    app($this->voucherIntergrationU8CourseUploadParseRepository)->deleteByWhere(['company_id' => [$companyId], 'course_type' => [$type]]);
                }
                if ($uploadCourses) {
                    app($this->voucherIntergrationU8CourseUploadParseRepository)->insertMultipleData($uploadCourses);
                }
            }
        }
        return true;
    }

    /**
     * 【公司】删除公司配置
     * @author [dingpeng]
     * @return [object] [description]
     */
    public function deleteCompanyConfig($param, $companyId)
    {
        // 删除公司 -- 公司配置及对应借方贷方数据
        // $companyRes = app($this->voucherIntergrationU8CompanyConfigRepository)->deleteById($companyId);
        $company = app($this->voucherIntergrationU8CompanyConfigRepository)->getDetail($companyId);
        $operation = trans('integrationCenter.log.delete_company') . ':' . $company['company_name'] ?? '';
        $companyRes = app($this->voucherIntergrationU8CompanyConfigRepository)->deleteByWhere(['company_id' => [$companyId], 'set_default' => [0]]);
        if (!$companyRes) {
            // 公司删除失败
            // $this->addLog($operation, trans('integrationCenter.log.operate_failed'));
            return ['code' => ['company_delete_failed', 'integrationCenter']];
        }
        // 删除公司配置对应的借方贷方的配置及数据
        $courseDatabaseRes = app($this->voucherIntergrationU8CourseDatabaseConfigRepository)->deleteByWhere(['company_id' => [$companyId]]);
        $courseUploadRes = app($this->voucherIntergrationU8CourseUploadParseRepository)->deleteByWhere(['company_id' => [$companyId]]);
        $courseUploadConfigRes = app($this->voucherIntergrationU8CourseUploadConfigRepository)->deleteByWhere(['company_id' => [$companyId]]);
        $courseSelectRes = app($this->voucherIntergrationU8CourseSelectConfigRepository)->deleteByWhere(['company_id' => [$companyId]]);

        // $this->addLog($operation, trans('integrationCenter.log.operate_success'));
        return true;
    }

    /**
     * 【公司】表单控件，解析公司启用列表
     * @author [dingpeng]
     * @return [object] [description]
     */
    public function getCompanyConfigSelect($param)
    {
        $param = $this->parseParams($param);
        $param['search'] = ['enabled_toggle' => [1]];
        $list = [];
        foreach (app($this->voucherIntergrationU8CompanyConfigRepository)->getList($param) as $new) {
            $list[] = $new;
        }
        return $list;
    }

    /**
     * 【公司】【科目】科目数据来源，上传附件，解析附件excel的表头（前端作为科目编码/名称下拉框的待选项）
     * @author [dingpeng]
     * @return [object] [description]
     */
    public function getCourseUploadExcelHeader($param)
    {
        $headerInfo = [];
        // 公司
        $companyId = isset($param['company_id']) ? $param['company_id'] : '';
        // type:debit
        $type = isset($param['type']) ? $param['type'] : '';
        // attachments:8f36e4dcb295dd69a83cc580423afc66
        $attachmentId = isset($param['attachments']) ? $param['attachments'] : '';
        $attachmentList = app($this->attachmentService)->getOneAttachmentById($attachmentId);

        $file = isset($attachmentList['temp_src_file']) ? $attachmentList['temp_src_file'] : '';

        try {
            // 调解析，获取excel内容
            $firstLine = app($this->importExportService)->getExcelData($file, 1, 1);
        } catch (\Exception $e) {
            return ['code' => ['course_upload_excel_parse_error', 'integrationCenter']];
        }
        // 取第0行数据
        if (!empty($firstLine)) {
            $headerInfo = isset($firstLine[0]) ? $firstLine[0] : [];
        }
        // 插入/更新数据到【记录upload类型的科目的配置信息】表(暂时不需要，保存的时候，一起保存)
        // $this->voucherIntergrationU8CourseUploadConfigRepository
        return $headerInfo;
    }

    /**
     * 【凭证配置】获取U8凭证配置主表信息
     * @author [dosy]
     * @param $param
     * @return array
     */
    public function getVoucherMainConfig($param)
    {
        $params = $this->parseParams($param);
        //$params['order_by']=['voucher_config_id'=>'desc'];
        $response = isset($params['response']) ? $params['response'] : 'both';
        $list = [];

        if ($response == 'both' || $response == 'count') {
            $count = app($this->voucherIntergrationU8MainConfigRepository)->getCount($params);
        }

        if (($response == 'both' && $count > 0) || $response == 'data') {
            foreach (app($this->voucherIntergrationU8MainConfigRepository)->getList($params) as $new) {
                // $flowParam['flow_id'] = $new['bind_flow_id'];
                // $flowInfo = app($this->flowTypeRepository)->getFlowTypeInfoListRepository($flowParam);
                // $new['flow_name'] = isset($flowInfo->flow_name)?$flowInfo->flow_name : '';
                $list[] = $new;
            }
        }
        return $response == 'both' ? ['total' => $count, 'list' => $list] : ($response == 'data' ? $list : $count);
    }

    /**
     * 【凭证配置】新建U8凭证配置主表信息
     * @author [dosy]
     * @param $param
     * @return array
     */
    public function addVoucherMainConfig($param)
    {
//        $param['voucher_config_name']='2';
        //        $param['bind_flow_id']='1111';
        //        $param['account']='3';
        if (!isset($param['voucher_config_name']) || empty($param['voucher_config_name']) || !isset($param['bind_flow_id']) || empty($param['bind_flow_id'])) {
            return ['code' => ['0x010001', 'integrationCenter']];
        } else {
            // $info = app($this->voucherIntergrationU8MainConfigRepository)->getOneFieldInfo(["bind_flow_id" => $param['bind_flow_id']]);
            // $countInfo = count($info);
            // if ($countInfo != 0) {
            //     return ['code' => ['0x010002', 'integrationCenter']];
            // }
            $flowInfo = app($this->flowTypeRepository)->getOneFieldInfo(["flow_id" => $param['bind_flow_id']]);
//            $flowCountInfo = count($flowInfo);
//            if ($flowCountInfo == 0) {
            if (empty($flowInfo)) {
                return ['code' => ['0x010001', 'integrationCenter']];
            }
            $params = array_intersect_key($param, array_flip(app($this->voucherIntergrationU8MainConfigRepository)->getTableColumns()));
        }
        $data = app($this->voucherIntergrationU8MainConfigRepository)->insertData($params);
        // 是否自动识别表单配置主表配置和借贷方字段配置
        $autoComplete = $param['auto_complete'] ?? '';
        if ($autoComplete) {
            $requiredField = $this->autoCompleteVoucherConfig($flowInfo->form_id, $data->voucher_config_id);
            if ($requiredField) {
                $data->requiredField = $requiredField;
            }
        }
        return $data;
    }
    /**
     * 【凭证配置】编辑U8凭证配置主表信息
     * @author [dosy]
     * @param $param
     * @param $voucherConfigId
     * @return array
     */
    public function modifyVoucherMainConfig($param, $voucherConfigId)
    {
        if (!is_numeric($voucherConfigId)) {
            return ['code' => ['0x010001', 'integrationCenter']];
        }
        $info = app($this->voucherIntergrationU8MainConfigRepository)->getOneFieldInfo(["voucher_config_id" => $voucherConfigId]);
//        $countInfo = count($info);
        if (empty($info)) {
            return ['code' => ['0x010001', 'integrationCenter']];
        }
        $params = array_intersect_key($param, array_flip(app($this->voucherIntergrationU8MainConfigRepository)->getTableColumns()));
        $data = app($this->voucherIntergrationU8MainConfigRepository)->updateData($params, ['voucher_config_id' => $voucherConfigId]);
        return $data;
    }

    /**
     * 【凭证配置】删除U8凭证配置主表信息
     * @author [dosy]
     * @param $param
     * @param $voucherConfigId
     * @return array
     */
    public function deleteVoucherMainConfig($param, $voucherConfigId)
    {
        if (!is_numeric($voucherConfigId)) {
            return ['code' => ['0x010001', 'integrationCenter']];
        }
        //获取流程外发数据
        $where = ['search' => ['voucher_config' => [$voucherConfigId, '='], "voucher_category" => [1, "="],]];
        $flowOutsendList = app($this->flowOutsendRepository)->getLists($where);
        if (!empty($flowOutsendList)) {
            $nodeIds = [];
            foreach ($flowOutsendList as $key => $flowOutsend) {
                //获取所有设置了该u8凭证配置的节点
                $nodeIds[] = $flowOutsend['node_id'];
            }
            $wheres = ['search' => ['node_id' => [$nodeIds, 'in']]];
            $flowProcessList = app($this->flowProcessRepository)->getFlowProcessList($wheres)->toArray();
            foreach ($flowProcessList as $key => $flowProcess) {
                $flow_outsend_toggle = $flowProcess['flow_outsend_toggle'];
                //检查所有配置节点外发的状态，只要存在一个开启，该凭证不能被删除
                if ($flow_outsend_toggle === 1) {
                    return ['code' => ['0x010004', 'integrationCenter']];
                }
            }
            //到这说明该凭证当前不在被使用
        }
        $res = app($this->voucherIntergrationU8MainConfigRepository)->deleteByWhere(['voucher_config_id' => [$voucherConfigId]]);
        if ($res) {
            if (!empty($flowOutsendList)) {
                app($this->flowOutsendRepository)->deleteByWhere(['voucher_config' => [$voucherConfigId], "voucher_category" => [1]]);
            }
            $data = $this->deleteVoucherFieldConfig($voucherConfigId);
            return $data;
        } else {
            return ['code' => ['0x010001', 'integrationCenter']];
        }
    }

    /**
     * 【凭证配置】删除U8凭证配置字段配置表信息
     * @param $voucherConfigId
     * @return mixed
     * @author [dosy]
     */
    public function deleteVoucherFieldConfig($voucherConfigId)
    {
        $data = app($this->voucherIntergrationU8FieldConfigRepository)->deleteByWhere(['voucher_config_id' => [$voucherConfigId]]);
        return $data;
    }
    /**
     * 【字段配置】获取字段配置信息，要用param传借/贷类型
     * @author [dingpeng]
     * @return [object] [description]
     */
    public function getVoucherFieldConfig($param, $voucherConfigId)
    {
        return "";
    }

    /**
     * 【字段配置】保存字段配置信息，要用param传借/贷类型
     * @param $param
     * @param $voucherConfigId
     * @return array
     */
    public function modifyVoucherFieldConfig($param, $voucherConfigId)
    {
//        $param['debit_credit_type']='credit';
        //        $param['voucher_config_id']='1';
        //        $param['cdigest']='debit';
        //        $param['ccode']='debit';
        //        $param['md']='1223331';
        //        $param['mc']='';
        //        $param['idoc']='qwer';
        $debitCrediType = $param['debit_credit_type'];
        if (!is_numeric($voucherConfigId)) {
            return ['code' => ['0x010001', 'integrationCenter']];
        }
        $info = app($this->voucherIntergrationU8MainConfigRepository)->getOneFieldInfo(["voucher_config_id" => $voucherConfigId]);
//        $countInfo = count($info);
//        if ($countInfo == 0) {
        if (empty($info)) {
            return ['code' => ['0x010001', 'integrationCenter']];
        }

        $voucherFieleInfo = app($this->voucherIntergrationU8FieldConfigRepository)->getOneFieldInfo([
            "voucher_config_id" => $voucherConfigId,
            'debit_credit_type' => $debitCrediType,
        ]);
//        $voucherFieleCountInfo = count($voucherFieleInfo);
        $params = array_intersect_key($param, array_flip(app($this->voucherIntergrationU8FieldConfigRepository)->getTableColumns()));
        if (empty($voucherFieleInfo)) {
            $data = app($this->voucherIntergrationU8FieldConfigRepository)->insertData($params);
        } else {
            $data = app($this->voucherIntergrationU8FieldConfigRepository)->updateData($params, ['voucher_config_id' => $voucherConfigId, 'debit_credit_type' => $debitCrediType]);
        }
        return $data;
    }

    /**
     * 【获取配置基本信息】
     * @param [type] $type
     * @return array
     * @author yuanmenglin
     */
    public function getBaseInfo($type)
    {
        $baseInfo = app($this->voucherIntergrationBaseInfoRepository)->getFieldInfo(['vocher_type' => $type]);
        return $baseInfo[0] ?? [];
    }

    /**
     * 【保存配置基本信息】
     * @param [type] $type
     * @return array
     * @author yuanmenglin
     */
    public function saveBaseInfo($param, $baseId)
    {
        $res = app($this->voucherIntergrationBaseInfoRepository)->updateData($param, ['base_id' => $baseId]);
        return $res;
    }

    /**
     * 【凭证配置】获取U8凭证配置信息
     * @param $voucherConfigId
     * @return array
     * @author [dosy]
     */
    public function getVoucherConfig($data, $voucherConfigId)
    {
        if (!is_numeric($voucherConfigId)) {
            return ['code' => ['0x010001', 'integrationCenter']];
        }
        $data = [];
        $debitType = 'debit';
        $creditType = 'credit';
        $mainConfig = app($this->voucherIntergrationU8MainConfigRepository)->getOneInfo($voucherConfigId);
        if (!empty($mainConfig)) {
            $flowParam['flow_id'] = $mainConfig['bind_flow_id'];
            $flowInfo = app($this->flowTypeRepository)->getFlowTypeInfoListRepository($flowParam);
            $mainConfig['flow_name'] = isset($flowInfo->flow_name) ? $flowInfo->flow_name : '';
            $data['base_config'] = $mainConfig;
        }
        $filedDebitConfig = app($this->voucherIntergrationU8FieldConfigRepository)->getOneInfo($voucherConfigId, $debitType);
        $data['debit_config'] = empty($filedDebitConfig) ? [] : $filedDebitConfig;
        $filedCreditConfig = app($this->voucherIntergrationU8FieldConfigRepository)->getOneInfo($voucherConfigId, $creditType);
        $data['credit_config'] = empty($filedCreditConfig) ? [] : $filedCreditConfig;
        return $data;
    }
    /**
     * 日志记录公共函数
     * @param operation String 操作关键字（描述精简） required
     * @param result String 操作结果记录 required
     * @param data String 操作数据
     * @param info String 额外说明内容
     * @return boolen
     */
    public function addLog($operation, $result, $data = [], $info = '')
    {
        $userId = own()['user_id'] ?? '';
        $insertData = [
            'operate_action' => $operation,
            'operate_data' => $data['operate_data'] ?? '',
            'flow_id' => $data['flow_id'] ?? '',
            'run_id' => $data['run_id'] ?? '',
            'operate_result' => $result,
            'attach_information' => $info,
            'operator' => $userId,
        ];
        $resAdd = app($this->voucherIntergrationU8LogRepository)->addLog($insertData);
        return $resAdd;
    }

    /**
     * 获取日志记录
     * @param data 请求体
     * @return Array
     */
    public function getVoucherLogList($data)
    {
        $params = $this->parseParams($data);
        // 按创建时间升序排列
        $params['order_by'] = ['voucher_intergration_u8_log.created_at' => 'desc','voucher_intergration_u8_log.log_id' => 'asc'];
        $resList['list'] = app($this->voucherIntergrationU8LogRepository)->getVoucherLogList($params);
        $resList['total'] = app($this->voucherIntergrationU8LogRepository)->getVoucherLogList($params, 'total');
        return $resList;
    }


    /**
     * 获取日志记录
     * @param data 请求体
     * @return Array
     */
    public function getVoucherLogDetail($logId)
    {
        if(!empty($logId)){
            $params['search'] = ['log_id'=>['=',$logId]];
        }
        $resList['list'] = app($this->voucherIntergrationU8LogRepository)->getVoucherLogList($params);
        return $resList['list'][0];
    }

    /**
     *【公司】获取外部数据库公司配置
     * @param $data
     * @return array
     * @author [dosy]
     */
    public function getCompanyInitConfigFromU8System($param)
    {
       // $param['database_id']=27;
        //        $param['database']='eofficedemo105';
        //        $param = [
        //            'database_id' => 27,//外部数据库id 必填
        //            'table_name' => 'UA_Account',//表名 必填  ---这里的表名是获取公司名称的表名
        //            'database' => 'eofficedemo105',
        ////             'search'=>[
        ////                  "ACTIVITY_CONTENT"=>["1","like"],
        ////                  "ACTIVITY_TYPE" => [["1"], 'not_in']],//普通条件 选填
        ////                  'multiSearch'=>[//多级条件 选填
        ////                      "ACTIVITY_CONTENT" => [["1"], 'in'],
        ////                      "ACTIVITY_TYPE" => [["1"], 'not_in'],
        ////                      "multiSearch" => [
        ////                          "ACTIVITY_TYPE" => [["1"], 'not_in'],
        ////                          "ACTIVITY_CONTENT" => [["1"], 'in'],
        ////                      ],
        ////                      "ACTIVITY_ADDRESS" => [["1","5"], 'not_between'],
        ////                      "ACTIVITY_BEGINTIME" => [["1","5"], 'between'],
        ////                      '__relation__' => 'or'
        ////                  ],
        //            "order_by" => ["company_id" => "asc"],//排序 选填
        //            'page' => 0,//0 不分页
        //            'limit' => 10,// 默认10
        //            'fields' => ['*'],
        //            'returntype' => 'data',//array count object
        //        ];
        $param = $this->parseParams($param);
        $databaseId = isset($param['database_id']) ? $param['database_id'] : '';
        $baseId = isset($param['base_id']) ? $param['base_id'] : '';
        if (!$databaseId) {
            return ['code' => ['database_connection_failed_none_database_id', 'integrationCenter']];
        }
        // 库名： UFSystem
        $database = 'UFSystem';
        // $extParam = [
        //     'database_id' => $databaseId, // 外部数据库id 必填
        //     'database' => $database,
        //     'table_name' => 'UA_Account', // 表名 必填  ---这里的表名是获取公司名称的表名
        //     "fields" => "cAcc_Id,cAcc_Name,iYear", // 查询字段 选填
        //     "order_by" => ["iYear" => "asc"], // 排序 选填
        //     'page' => 0, // 0 不分页
        //     'limit' => 10, //  默认10
        //     'returntype' => 'data', // array count object
        // ];
        // $data = app($this->externalDatabaseService)->getExternalDatabasesTableData($extParam);
        // if (empty($data)) {
        //     // 未获取到U8数据库的公司信息，同步失败
        //     return ['code' => ['sync_failed_company_data_acquisition_failed', 'integrationCenter']];
        // }
        // 获取账套信息有些问题：应从UA_AccountDatabase获取iEndYear is NULL的，再根据cAcc_Id获取UA_Account相关信息，Iyear字段获取为iBeginYear
        $extParam = [
            'database_id' => $databaseId, // 外部数据库id 必填
            'database' => $database,
            'sql' => 'SELECT UA_AccountDatabase.*,UA_Account.cAcc_Name FROM UA_AccountDatabase LEFT JOIN UA_Account ON
            UA_AccountDatabase.cAcc_Id = UA_Account.cAcc_Id WHERE iEndYear IS NULL',
            'all' => 1
        ];
        $list = app($this->externalDatabaseService)->externalDatabaseExcuteSql($extParam);
        if (empty($list)) {
            // 未获取到U8数据库的公司信息，同步失败
            return ['code' => ['sync_failed_company_data_acquisition_failed', 'integrationCenter']];
        } else {
            $data['list'] = $list;
        }
        if (isset($data['list']) && !empty($data['list'])) {
            foreach ($data['list'] as $key => $company) {
                $u8CompanyConfigParam = [];
                $u8CourseDatabaseConfigParam = [];
                $cAccName = isset($company->cAcc_Name) ? $company->cAcc_Name : '';
                // 取出来，编码是 CP936 ，不转码，没问题，转码有问题，先注释掉，用auto处理
                // $encode = mb_detect_encoding($cAccName, array("ASCII", "GB2312", "GBK", "UTF-8"));
                $cAccName = mb_convert_encoding($cAccName, "UTF-8", 'auto');
                $cAccId = isset($company->cAcc_Id) ? $company->cAcc_Id : '';
                // $iYear = isset($company->iYear) ? $company->iYear : '';
                $iYear = isset($company->iBeginYear) ? $company->iBeginYear : '';
                $u8CompanyConfigParam = [
                    // 'company_account_name' => 'UFDATA_' . $cAccId . '_' . $iYear,
                    'company_account_name' => $company->cDatabase,
                ];
                $u8CompanyConfigInfo = app($this->voucherIntergrationU8CompanyConfigRepository)->getOneFieldInfo($u8CompanyConfigParam);

//                $u8CompanyConfigDataCountInfo = count($u8CompanyConfigInfo);
//                if ($u8CompanyConfigDataCountInfo == 0) {
                if (empty($u8CompanyConfigInfo)) {
                    $u8CompanyConfigParam['company_name'] = $cAccName;
                    $u8CompanyConfigParam['iyear'] = $iYear;
                    $u8CompanyConfigParam['debit_course_type'] = 'database';
                    $u8CompanyConfigParam['credit_course_type'] = 'database';
                    $insertU8CompanyConfigData = array_intersect_key($u8CompanyConfigParam,
                        array_flip(app($this->voucherIntergrationU8CompanyConfigRepository)->getTableColumns()));
                    $insertInfo = app($this->voucherIntergrationU8CompanyConfigRepository)->insertData($insertU8CompanyConfigData);
                    if (isset($insertInfo->company_id) && !empty($insertInfo->company_id)) {
                        $u8CourseDatabaseConfigParam[] = [
                            'company_id' => $insertInfo->company_id,
                            'course_type' => 'debit',
                            'database_id' => $param['database_id'],
                            'database_table' => 'code', //---这里的表名是借贷方相关的表名
                            'code_id_field' => 'ccode',
                            'code_name_field' => 'ccode_name',
                        ];
                        $u8CourseDatabaseConfigParam[] = [
                            'company_id' => $insertInfo->company_id,
                            'course_type' => 'credit',
                            'database_id' => $param['database_id'],
                            'database_table' => 'code', //---这里的表名是借贷方相关的表名
                            'code_id_field' => 'ccode',
                            'code_name_field' => 'ccode_name',
                        ];
                        $res = app($this->voucherIntergrationU8CourseDatabaseConfigRepository)->insertMultipleData($u8CourseDatabaseConfigParam);
                    }
                }
            }
        }
        // $allData = app($this->voucherIntergrationU8CompanyConfigRepository)->getList($param);

        $cacheTime = date("Y-m-d H:i:s");
        // 调函数，保存最后同步时间
        $this->saveBaseInfo(['last_cache_time' => $cacheTime], $baseId);
        return ['last_cache_time' => $cacheTime];
    }

    /**
     * 功能函数，根据凭证配置的字段，解析表单数据，同时考虑配置的控件的控件类型，合理取值
     * 函数，全面替换外发函数内的用法:
     * ($flowData[$baseVoucherConfig['cbill']] ? $flowData[$baseVoucherConfig['cbill']] : '';)
     * @param  [type] $flowData    [流程数据]
     * @param  [type] $formData    [表单结构]
     * @param  [type] $u8Field     [要取值的u8字段]
     * @param  [type] $voucherConfig [凭证配置info]
     * @return [type]              [description]
     */
    function parseFlowDataOutSend($flowData,$formData,$u8Field,$voucherConfig)
    {
        $controlValue = '';
        // 取凭证字段 $u8Field 对应的表单控件id
        $voucherConfigFormField = $voucherConfig[$u8Field] ?? '';
        // 对应表单控件属性
        $controlInfo = $formData[$voucherConfigFormField] ?? [];
        // 控件类型 可选值['text','textarea','radio','checkbox','select','label','editor','data-selector','signature-picture','upload','countersign','electronic-signature','dynamic-info','detail-layout']
        $controlType = $controlInfo['control_type'] ?? '';
        $controlAttribute = $controlInfo['control_attribute'] ?? [];
        if($u8Field == 'cbill') {
            // 制单人
            if($controlType == 'text' || $controlType == 'textarea') {
                $controlValue = $flowData[$voucherConfigFormField] ?? '';
            } else {
                $controlValue = $flowData[$voucherConfigFormField."_TEXT"] ?? '';
            }
        } else if($u8Field == 'dbill_date') {
            // 制单日期
            if($voucherConfigFormField && isset($flowData[$voucherConfigFormField])) {
                $dbillDateFieldValueCreateData = date_create($flowData[$voucherConfigFormField]);
                if($dbillDateFieldValueCreateData) {
                    $controlValue = date_format($dbillDateFieldValueCreateData, "Y-m-d");
                } else {
                    $controlValue = date('Y-m-d');
                }
            } else {
                $controlValue = date('Y-m-d');
            }
        }
        return $controlValue;
    }

    /**
     * 功能函数，由流程模块调用，传入凭证配置信息&流程信息，实现外发生成凭证功能
     * @param  [type] $param    [外发配置信息等;包含: voucher_category - 凭证大类;voucher_config - 凭证配置id]
     * @param  [type] $flowData [流程信息，即外发那里的$flowRunDatabaseData]
     * eg:
    {
    "id": "2302",
    "run_id": "68228",
    "DATA_1": "",
    "DATA_2": "默认值",
    "DATA_3": "单行默认值"
    "run_name": "我要留言2019-09-23 15:20:32",
    "flow_id": "3013",
    "form_id": "3017",
    "process_name": "查看留言2",
    "userInfo": {
    "user_id": "admin",
    "user_name": "系统管理员",
    "user_accounts": "admin",
    "dept_name": "e-office支撑服务部",
    "dept_id": "770",
    "role_name": "[\"OA \\u7ba1\\u7406\\u5458\",\"\\u6587\\u6848\\u7b56\\u5212\\u4e13\\u5458\",\"\\u5e02\\u573a\\u5f00\\u62d3\"]",
    "role_id": "[1,97,35]"
    }
    }
     * @return [type]           [description]
     */
    public function yonyouVoucherOutSend($param, $flowData)
    {
        // 日志数据
        $operation = trans('integrationCenter.log.voucher_operation');
        $operateData = [
            'param' => $param,
            'flowData' => $flowData
        ];
        // 流程相关数据
        $runId = $flowData['run_id'] ?? 0;
        $flowId = $flowData['flow_id'] ?? 0;
        $logData = [
            'operate_data' => json_encode($operateData),
            'flow_id' => $flowId,
            'run_id' => $runId
        ];
        if (!$runId) {
            // 流程id获取失败
            $this->addLog($operation, trans('integrationCenter.log.create_voucher_failed_get_flow_id_failed'), $logData);
            return ['code' => ['u8_out_send_failed', 'integrationCenter.log']];
        }
        $formId = $flowData['form_id'] ?? 0;
        $runName = $flowData['run_name'] ?? '';
        // 取表结构
        $formData = app($this->flowFormService)->getParseForm($formId);
        $formData = array_column($formData,NULL,'control_id');
        // 凭证配置id
        $voucherConfigId = isset($param['voucher_config']) ? $param['voucher_config'] : '';
        if (!$voucherConfigId) {
            // 参数中没有凭证配置id
            $this->addLog($operation, trans('integrationCenter.log.create_voucher_failed_get_voucher_config_id_failed'), $logData);
            return ['code' => ['u8_out_send_failed', 'integrationCenter.log']];
        }
        // 获取凭证配置 --基本配置、借方配置、贷方配置
        $voucherConfig = $this->getVoucherConfig([], $voucherConfigId);
        $baseVoucherConfig = $voucherConfig['base_config'] ?? [];
        $debitVoucherConfig = $voucherConfig['debit_config'] ?? [];
        $creditVoucherConfig = $voucherConfig['credit_config'] ?? [];
        // 组织常量
        $coutnoIdUuid = "OA_REQUESTID_" . $runId;
        $coutnoId = '';
        // 当前日期，年月日（时分秒都是0），用于 doutbilldate 字段（外部凭证制单日期）
        $currentDate = date_format(date_create(date("Y-m-d")), "Y-m-d H:i:s"); // todo-要参考已有项目看这里要用啥格式的年月日
        $baseInfo = $this->getBaseInfo('u8');
        if (!$baseInfo) {
            // 未查到U8基本配置
            $this->addLog($operation, trans('integrationCenter.log.create_voucher_failed_select_u8_voucher_base_config_empty'), $logData);
            return ['code' => ['u8_out_send_failed', 'integrationCenter.log']];
        }
        // 组织变量
        // 外部数据库 id
        $datasourceId = $baseInfo['database_config'] ?? 0; // todo
        if (!$datasourceId) {
            // 外部数据库id未配置
            $this->addLog($operation, trans('integrationCenter.log.create_voucher_failed_external_database_not_set'), $logData);
            return ['code' => ['u8_out_send_failed', 'integrationCenter.log']];
        }
        // 表单字段，直接从 $flowData 里面用 $flowData['DATA_1'] 的方式获取
        if (!$flowData) {
            // 表单数据为空
            $this->addLog($operation, trans('integrationCenter.log.create_voucher_failed_form_data_empty'), $logData);
            return ['code' => ['u8_out_send_failed', 'integrationCenter.log']];
        }
        // 取到所有凭证配置的关联关系
        // todo

        // 待插入的数组，每个子项都包含一条凭证分录的所有字段内容
        $voucherDataArray = [];

        // [step-1]解析凭证主表信息设置，得到如下几个变量 // todo
        if (!$baseVoucherConfig) {
            // 主表信息配置无
            $this->addLog($operation, trans('integrationCenter.log.create_voucher_failed_voucher_main_config_not_set'), $logData);
            return ['code' => ['u8_out_send_failed', 'integrationCenter.log']];
        }
        // 解析账套配置，获取要插入数据的库  --默认公司
        $companyId = $baseVoucherConfig['account'] ? ($flowData[$baseVoucherConfig['account']] ?? '') : '';
        $company = app($this->voucherIntergrationU8CompanyConfigRepository)->getDetail($companyId);
        $database = $company['company_account_name'] ?? '';
        if (!$database) {
            $defaultCompany = app($this->voucherIntergrationU8CompanyConfigRepository)->getOneFieldInfo(['set_default' => [1]]);
            if ($defaultCompany) {
                $database = $defaultCompany->company_account_name ?? '';
            }
            if (!$database) {
                // 未设置账套数据库
                $this->addLog($operation, trans('integrationCenter.log.create_voucher_failed_voucher_account_not_set'), $logData);
                return ['code' => ['u8_out_send_failed', 'integrationCenter.log']];
            }
        }
        $databaseParam = [
            'database_id' => $datasourceId, //外部数据库id 必填
            "database" => $database,
        ];
        // 当前流程是否外发过U8财务凭证 外发过先删除掉之前的凭证
        $relation = app($this->voucherIntergrationU8RelationFlowRunRepository)->getOneFieldInfo(['run_id' => $runId]);
        if ($relation && isset($relation->ino_id)) {
            // 删除凭证
            $res = $this->deleteVoucher($databaseParam, '', $relation->ino_id);
            // 删除关联信息
            $relation->delete();
        }
        // [表单数据解析] dbill_date -- 制单日期$currentDate
        $dbillDate = $this->parseFlowDataOutSend($flowData,$formData,'dbill_date',$baseVoucherConfig);
        if (!$dbillDate) {
            // [制单日期]不能为空
            $this->addLog($operation, trans('integrationCenter.log.create_voucher_failed_voucher_dbill_not_empty'), $logData);
            return ['code' => ['u8_out_send_failed', 'integrationCenter.log']];
        } else {
            // 且[制单日期]不能大于当前时间
            if($dbillDate > date('Y-m-d')) {
                $this->addLog($operation, trans('integrationCenter.log.create_voucher_failed_voucher_dbill_not_greater_current_time'), $logData);
                return ['code' => ['u8_out_send_failed', 'integrationCenter.log']];
            }
        }
        // [表单数据解析] iyear -- 会计年度
        $iyearField = $baseVoucherConfig['iyear'] ?? '';
        $iyear = isset($flowData[$iyearField]) && (int) $flowData[$iyearField] ? date_format(date_create($flowData[$iyearField]), "Y") : date_format(date_create($currentDate), "Y"); // int
        // [表单数据解析] iperiod -- 会计期间 -- 两位
        $iperiodField = $baseVoucherConfig['iperiod'] ?? '';
        $iperiod = isset($flowData[$iperiodField]) && (int) $flowData[$iperiodField] ? date_format(date_create($flowData[$iperiodField]), "m") : date_format(date_create($currentDate), "m"); // int
        // 年月 拼接出来的，eg-201909
        $iyperiod = $iyear && $iperiod ? $iyear . '' . $iperiod : ''; // String
        // [表单数据解析] cbill -- 制单人
        $cbill = $this->parseFlowDataOutSend($flowData,$formData,'cbill',$baseVoucherConfig);
        if (!$cbill) {
            // 制单人不能为空
            $this->addLog($operation, trans('integrationCenter.log.create_voucher_failed_voucher_cbill_not_empty'), $logData);
            return ['code' => ['u8_out_send_failed', 'integrationCenter.log']];
        }
        // [表单数据解析] csign -- 凭证类别字(银、转...)
        $csign = $flowData[$baseVoucherConfig['csign'] . '_TEXT'] ?? ''; // String
        if (!$csign) {
            // 凭证类型不能为空
            $this->addLog($operation, trans('integrationCenter.log.create_voucher_failed_voucher_csign_not_empty'), $logData);
            return ['code' => ['u8_out_send_failed', 'integrationCenter.log']];
        }
        // 凭证类别的id，1,2,3， 用sql 去U8 查出来的，根据类别字，取的时候，要判断错误
        $extParam = $databaseParam;
        $extParam['sql'] = "Select isignseq From dsign where csign = '$csign'";
        $isignseqData = app($this->externalDatabaseService)->externalDatabaseExcuteSql($extParam);
        if (!$isignseqData) {
            // 执行获取凭证类型的id的sql失败
            $this->addLog($operation, trans('integrationCenter.log.create_voucher_failed_excute_get_csign_sql_failed'), $logData);
            return ['code' => ['u8_out_send_failed', 'integrationCenter.log']];
        }
        $isignseq = $isignseqData->isignseq ?? 0; // int
        if (!$isignseq) {
            // 获取凭证类型的id失败
            $this->addLog($operation, trans('integrationCenter.log.create_voucher_failed_excute_get_csign_id_failed'), $logData);
            return ['code' => ['u8_out_send_failed', 'integrationCenter.log']];
        }
        // [step-2]解析凭证[借方]信息设置
        if (!$debitVoucherConfig) {
            // 凭证借方相关未配置
            $this->addLog($operation, trans('integrationCenter.log.create_voucher_failed_voucher_debit_config_not_set'), $logData);
            return ['code' => ['u8_out_send_failed', 'integrationCenter.log']];
        }
        // 现金流信息数组
        $cashitem = [];
        $layoutNumber = 1;
        $debitData = [];
        $mdCount = 0;
        $mcCount = 0;
        // 定义一个[借方]信息设置数组，把[借方]字段都放进去
        // $debitConfigFieldArray = ["cdigest", "ccode", "md", "idoc", "citem_id", "citem_class", "cdept_id", "cperson_id", "ccus_id", "csup_id"];
        $debitConfigFieldArray = ["cdigest", "ccode", "md", "idoc"];
        // [借方]解析好的值
        $debitConfigFieldRelationFormLayoutId = '';
        $detailLineCount = -1;
        // 设置规则：以金额字段为准，金额字段为明细则为多条，表单数据则单条
        $debitConfigFieldName = $debitVoucherConfig['md'] ?? '';
        if ($debitConfigFieldName) {
            $fieldValueArray = explode('_', $debitConfigFieldName);
            if ($fieldValueArray && count($fieldValueArray) > 2) {
                $fieldDetailLayoutId = $fieldValueArray[0] . '_' . $fieldValueArray[1];
                // 获取对应明细数据 获取明细行数最少的作为循环次数
                $fieldDetail = $flowData[$fieldDetailLayoutId] ?? [];
                $fieldDetailId = isset($fieldDetail["id"]) ? $fieldDetail["id"] : [];
                $fieldDetailLineCount = count($fieldDetailId);
                if ($fieldDetailLineCount == 0) {
                    $this->addLog($operation, trans('integrationCenter.log.create_voucher_failed_debit_get_detail_empty'), $logData);
                    return ['code' => ['u8_out_send_failed', 'integrationCenter.log']];
                } else {
                    $debitConfigFieldRelationFormLayoutId = $fieldDetailLayoutId;
                    $detailLineCount = $fieldDetailLineCount;
                }
            }
        } else {
            $this->addLog($operation, trans('integrationCenter.log.create_voucher_failed_voucher_debit_config_not_set_money_field'), $logData);
            return ['code' => ['u8_out_send_failed', 'integrationCenter.log']];
        }
        // 循环借方配置字段，判断各个字段关联的表单控件
        // 如果关联了明细布局控件，结束循环，赋值给外部变量 $debitConfigFieldRelationFormLayoutId  取最小明细
        // foreach ($debitConfigFieldArray as $debitConfigFieldKey => $debitConfigFieldValue) {
        //     $debitConfigFieldName = $debitVoucherConfig[$debitConfigFieldValue] ?? '';
        //     if ($debitConfigFieldName) {
        //         $fieldValueArray = explode('_', $debitConfigFieldName);
        //         if ($fieldValueArray && count($fieldValueArray) > 2) {
        //             $fieldDetailLayoutId = $fieldValueArray[0] . '_' . $fieldValueArray[1];
        //             // 获取对应明细数据 获取明细行数最少的作为循环次数
        //             $fieldDetail = $flowData[$fieldDetailLayoutId] ?? [];
        //             $fieldDetailId = isset($fieldDetail["id"]) ? $fieldDetail["id"] : [];
        //             $fieldDetailLineCount = count($fieldDetailId);
        //             if ($fieldDetailLineCount == 0) {
        //                 $this->addLog($operation, '借方配置获取明细数据异常', $logData);
        //                 return ['code' => ['u8_out_send_failed', 'integrationCenter']];
        //             } else {
        //                 if ($detailLineCount == -1 || $fieldDetailId < $detailLineCount) {
        //                     $debitConfigFieldRelationFormLayoutId = $fieldDetailLayoutId;
        //                     $detailLineCount = $fieldDetailLineCount;
        //                 }
        //             }
        //         }
        //     }
        // }
        // 如果借方配置，关联了明细布局，则根据此明细布局的行数进行循环，在循环中，解析[借方]信息设置，往 $debitData 插入多行值
        if ($debitConfigFieldRelationFormLayoutId != '') {
            // 注意处理借方字段的默认值
            if ($detailLineCount) {
                for ($i = 0; $i < $detailLineCount; $i++) {
                    $debitDataItem = $this->getVoucherData($debitVoucherConfig, $flowData, $i, $layoutNumber, $runId, $databaseParam, $iyear);
                    if (!$debitDataItem || isset($debitDataItem['error_code'])) {
                        // 解析借方数据异常
                        continue;
                    }
                    if (!$debitDataItem['ccode']) {
                        // 科目代码不能为空
                        continue;
                    }
                    if (!$debitDataItem['md'] || $debitDataItem['md'] == 0) {
                        // 借方金额不能为0
                        continue;
                    }
                    // 现金项目-现金流量编码
                    // $ccashitem = isset($debitVoucherConfig['ccashitem']) ? ($detail[$debitVoucherConfig['ccashitem']][$i] ?? null) : null;
                    // if ($ccashitem) {
                    //     $cashitem[] = [
                    //         'inid' => $layoutNumber,
                    //         'ccashitem' => $ccashitem,
                    //         'md' => $md,
                    //         'mc' => $mc,
                    //         'ccode' => $ccode
                    //     ];
                    // }
                    $debitData[] = $debitDataItem;
                    $layoutNumber++;
                    $mdCount += $debitDataItem['md'];
                }
            }
        } else {
            // 如果借方配置，没有关联明细布局，那么解析[借方]信息设置，往 $debitData 内插入一行数据
            // 注意处理借方字段的默认值
            // 按表单主数据来获取
            $debitDataItem = $this->getVoucherData($debitVoucherConfig, $flowData, -1, $layoutNumber, $runId, $databaseParam, $iyear);
            if (!$debitDataItem || isset($debitDataItem['error_code'])) {
                if (isset($debitDataItem['error_code']) && isset($debitDataItem['message'])) {
                    $this->addLog($operation, $debitDataItem['message'], $logData);
                } else {
                    $this->addLog($operation, trans('integrationCenter.log.create_voucher_failed_debit_data_not_empty'), $logData);
                }
                return ['code' => ['u8_out_send_failed', 'integrationCenter.log']];
            }
            if (!$debitDataItem['ccode']) {
                // 科目代码不能为空
                $this->addLog($operation, trans('integrationCenter.log.create_voucher_failed_debit_account_code_empty'), $logData);
                return ['code' => ['u8_out_send_failed', 'integrationCenter.log']];
            }
            if (!$debitDataItem['md'] || $debitDataItem['md'] == 0) {
                // 借方金额不能为0
                $this->addLog($operation, trans('integrationCenter.log.create_voucher_failed_debit_money_not_zero'), $logData);
                return ['code' => ['u8_out_send_failed', 'integrationCenter.log']];
            }
            // 现金项目-现金流量编码
            // $ccashitem = isset($debitVoucherConfig['ccashitem']) ? $flowData[$debitVoucherConfig['ccashitem']] : null;
            // if ($ccashitem) {
            //     $cashitem[] = [
            //         'inid' => $layoutNumber,
            //         'ccashitem' => $ccashitem,
            //         'md' => $md,
            //         'mc' => $mc,
            //         'ccode' => $ccode
            //     ];
            // }
            $debitData[] = $debitDataItem;
            $layoutNumber++;
            $mdCount += $debitDataItem['md'];
        }
        if (!$debitData) {
            // 借方数据不能为空
            $this->addLog($operation, trans('integrationCenter.log.create_voucher_failed_debit_data_not_empty'), $logData);
            return ['code' => ['u8_out_send_failed', 'integrationCenter.log']];
        }
        // 根据 $debitData ，拼接上其他字段，往 $voucherDataArray 内push一个子项
        // todo
        foreach ($debitData as $debitIndex => $debitValueItem) {
            // $debitValueItem['ino_id'] = $inoId; // 凭证编号
            $debitValueItem['iyear'] = $iyear; // 会计年度
            $debitValueItem['iperiod'] = $iperiod; // 会计期间
            $debitValueItem['iyperiod'] = $iyperiod;
            $debitValueItem['csign'] = $csign; // 类别字
            $debitValueItem['isignseq'] = $isignseq; // 类别排序号
            $debitValueItem['cbill'] = $cbill; // 制单人
            $debitValueItem['dbill_date'] = $dbillDate; // 制单日期
            $debitValueItem['doutbilldate'] = $currentDate;
            $debitValueItem['coutno_id'] = $coutnoIdUuid;
            array_push($voucherDataArray, $debitValueItem);
        }
        // [step-3]解析凭证[贷方]信息设置(参考借方)
        // $creditConfigFieldArray = ["cdigest", "ccode", "mc", "idoc", "citem_id", "citem_class", "cdept_id", "cperson_id", "ccus_id", "csup_id"];
        $creditConfigFieldArray = ["cdigest", "ccode", "mc", "idoc"];
        // [贷方]解析好的值
        $creditData = [];
        $creditConfigFieldRelationFormLayoutId = '';
        $detailLineCount = -1;
        $fieldDetailLayoutId = '';
        // 设置规则：以金额字段为准，金额字段为明细则为多条，表单数据则单条
        $creditConfigFieldName = $creditVoucherConfig['mc'] ?? '';
        if ($creditConfigFieldName) {
            $fieldValueArray = explode('_', $creditConfigFieldName);
            if ($fieldValueArray && count($fieldValueArray) > 2) {
                $fieldDetailLayoutId = $fieldValueArray[0] . '_' . $fieldValueArray[1];
                // 获取对应明细数据 获取明细行数最少的作为循环次数
                $fieldDetail = $flowData[$fieldDetailLayoutId] ?? [];
                $fieldDetailId = isset($fieldDetail["id"]) ? $fieldDetail["id"] : [];
                $fieldDetailLineCount = count($fieldDetailId);
                if ($fieldDetailLineCount == 0) {
                    $this->addLog($operation, trans('integrationCenter.log.create_voucher_failed_credit_get_detail_empty'), $logData);
                    return ['code' => ['u8_out_send_failed', 'integrationCenter.log']];
                } else {
                    $detailLineCount = $fieldDetailLineCount;
                    $creditConfigFieldRelationFormLayoutId = $fieldDetailLayoutId;
                }
            }
        } else {
            $this->addLog($operation, trans('integrationCenter.log.create_voucher_failed_voucher_credit_config_not_set_money_field'), $logData);
            return ['code' => ['u8_out_send_failed', 'integrationCenter.log']];
        }
        // 循环贷方配置字段，判断各个字段关联的表单控件
        // 如果关联了明细布局控件，结束循环，赋值给外部变量 $creditConfigFieldRelationFormLayoutId
        // foreach ($creditConfigFieldArray as $creditConfigFieldKey => $creditConfigFieldValue) {
        //     $creditConfigFieldName = $creditVoucherConfig[$creditConfigFieldValue] ?? '';
        //     if ($creditConfigFieldName) {
        //         $fieldValueArray = explode('_', $creditConfigFieldName);
        //         if ($fieldValueArray && count($fieldValueArray) > 2) {
        //             $fieldDetailLayoutId = $fieldValueArray[0] . '_' . $fieldValueArray[1];
        //             // 获取对应明细数据 获取明细行数最少的作为循环次数
        //             $fieldDetail = $flowData[$fieldDetailLayoutId] ?? [];
        //             $fieldDetailId = isset($fieldDetail["id"]) ? $fieldDetail["id"] : [];
        //             $fieldDetailLineCount = count($fieldDetailId);
        //             if ($fieldDetailLineCount == 0) {
        //                 $this->addLog($operation, '贷方配置获取明细数据异常', $logData);
        //                 return ['code' => ['u8_out_send_failed', 'integrationCenter']];
        //             } else {
        //                 if ($detailLineCount == -1 || $fieldDetailLineCount < $detailLineCount) {
        //                     $detailLineCount = $fieldDetailLineCount;
        //                     $creditConfigFieldRelationFormLayoutId = $fieldDetailLayoutId;
        //                 }
        //             }
        //         }
        //     }
        // }
        // 如果贷方配置，关联了明细布局，则根据此明细布局的行数进行循环，在循环中，解析[贷方]信息设置，往 $creditData 插入多行值
        if ($creditConfigFieldRelationFormLayoutId != '') {
            // 注意处理贷方字段的默认值
            if ($detailLineCount) {
                for ($i = 0; $i < $detailLineCount; $i++) {
                    // 获取贷方数组
                    $creditDataItem = $this->getVoucherData($creditVoucherConfig, $flowData, $i, $layoutNumber, $runId, $databaseParam, $iyear);
                    if (!$creditDataItem) {
                        // 贷方数据不能为空
                        continue;
                    }
                    if (!$creditDataItem['ccode']) {
                        // 科目代码不能为空
                        continue;
                    }
                    if (!$creditDataItem['mc'] || $creditDataItem['mc'] == 0) {
                        // 贷方金额不能为0
                        continue;
                    }
                    // 现金项目-现金流量编码
                    // $ccashitem = isset($creditVoucherConfig['ccashitem']) ? ($detail[$creditVoucherConfig['ccashitem']][$i] ?? null) : null;
                    // if ($ccashitem) {
                    //     $cashitem[] = [
                    //         'inid' => $layoutNumber,
                    //         'ccashitem' => $ccashitem,
                    //         'md' => $md,
                    //         'mc' => $mc,
                    //         'ccode' => $ccode
                    //     ];
                    // }
                    $mcCount += $creditDataItem['mc'];
                    $layoutNumber++;
                    $creditData[] = $creditDataItem;
                }
            }
        } else {
            // 如果贷方配置，没有关联明细布局，那么解析[贷方]信息设置，往 $creditData 内插入一行数据
            // 注意处理贷方字段的默认值
            // 按表单主数据来获取
            $creditDataItem = $this->getVoucherData($creditVoucherConfig, $flowData, -1, $layoutNumber, $runId, $databaseParam, $iyear);
            if (!$creditDataItem || isset($creditDataItem['error_code'])) {
                if (isset($creditDataItem['message']) && isset($creditDataItem['error_code'])) {
                    $this->addLog($operation, $creditDataItem['message'], $logData);
                } else {
                    $this->addLog($operation, trans('integrationCenter.log.create_voucher_failed_debit_data_not_empty'), $logData);
                }
                return ['code' => ['u8_out_send_failed', 'integrationCenter.log']];
            }
            if (!$creditDataItem['ccode']) {
                // 科目代码不能为空
                $this->addLog($operation, trans('integrationCenter.log.create_voucher_failed_credit_account_code_empty'), $logData);
                return ['code' => ['u8_out_send_failed', 'integrationCenter.log']];
            }
            if (!$creditDataItem['mc'] || $creditDataItem['mc'] == 0) {
                // 贷方金额不能为0
                $this->addLog($operation, trans('integrationCenter.log.create_voucher_failed_credit_money_not_zero'), $logData);
                return ['code' => ['u8_out_send_failed', 'integrationCenter.log']];
            }
            // 现金项目-现金流量编码
            // $ccashitem = isset($creditVoucherConfig['ccashitem']) ? ($flowData[$creditVoucherConfig['ccashitem']] ?? null) : null;
            // if ($ccashitem) {
            //     $cashitem[] = [
            //         'inid' => $layoutNumber,
            //         'ccashitem' => $ccashitem,
            //         'md' => $md,
            //         'mc' => $mc,
            //         'ccode' => $ccode,
            //     ];
            // }
            $creditData[] = $creditDataItem;
            $layoutNumber++;
            $mcCount += $creditDataItem['mc'];
        }
        if (!$creditData) {
            // 贷方数据不能为空
            $this->addLog($operation, trans('integrationCenter.log.create_voucher_failed_debit_data_not_empty'), $logData);
            return ['code' => ['u8_out_send_failed', 'integrationCenter.log']];
        }
        if ($mdCount != $mcCount) {
            // 生成凭证失败，凭证借贷金额不平
            $this->addLog($operation, trans('integrationCenter.log.create_voucher_failed_money_not_equal'), $logData);
            return ['code' => ['u8_out_send_failed', 'integrationCenter.log']];
        }
        // 根据 $creditData ，拼接上其他字段，往 $voucherDataArray 内push一个子项
        foreach ($creditData as $creditIndex => $creditValueItem) {
            // $creditValueItem['ino_id'] = $inoId; // 凭证编号
            $creditValueItem['iyear'] = $iyear; // 会计年度
            $creditValueItem['iperiod'] = $iperiod; // 会计期间
            $creditValueItem['iyperiod'] = $iyperiod;
            $creditValueItem['csign'] = $csign; // 类别字
            $creditValueItem['isignseq'] = $isignseq; // 类别排序号
            $creditValueItem['cbill'] = $cbill; // 制单人
            $creditValueItem['dbill_date'] = $dbillDate; // 制单日期
            $creditValueItem['doutbilldate'] = $currentDate;
            $creditValueItem['coutno_id'] = $coutnoIdUuid;
            array_push($voucherDataArray, $creditValueItem);
        }
        if (!$voucherDataArray) {
            $this->addLog($operation, trans('integrationCenter.log.create_voucher_failed_data_empty'), $logData);
            return ['code' => ['u8_out_send_failed', 'integrationCenter.log']];
        }
        // [step-4]循环待插入的数组，插入数据库
        // 1/解析账套配置，获取要插入数据的库

        // 2/解析待插入数组
        // todo
        // foreach ($voucherDataArray as $voucherDataIndex => $voucherDataItem) {
        //     // insert $voucherDataItem
        // }
        $databaseInfo = [
            'database_id' => $datasourceId,
            'table_name' => 'GL_accvouch',
            'database' => $database,
        ];
        foreach ($voucherDataArray as $voucherDataKey => $voucherDataValue) {
            $insertResult = app("App\EofficeApp\System\ExternalDatabase\Services\ExternalDatabaseService")->sendU8DataToExternalDatabase($databaseInfo, $voucherDataValue);
            if (!$insertResult || isset($insertResult['code'])) {
                $this->addLog($operation, trans('integrationCenter.log.create_voucher_failed_voucher_data_insert_failed'). ' ' . json_encode($insertResult), $logData);
                $this->deleteVoucher($databaseParam, $coutnoIdUuid);
                return ['code' => ['u8_out_send_failed', 'integrationCenter.log']];
            }
        }
        // 获取凭证类型流水号参考e-cology逻辑
        // 1先定义coutno_id_uuid = "OA_REQUESTID_". $runId;
        // 2插入GL_accvouch时不添加字段ino_id，添加coutno_id = coutno_id_uuid
        // 3插入成功后查询数据库内最大ino_id并coutno_id为条件将插入成功的数据补全ino_id
        // 4按照规则生成coutno_id 并将coutno_id_uuid的值替换掉
        $inoIdParam = $databaseParam;
        $inoIdParam['sql'] = "Select (MAX(ino_id)+1) maxIno_id From GL_accvouch Where csign='" . $csign . "' and iperiod = '" . $iperiod . "' and iyear = '" . $iyear . "' and iyperiod='" . $iyperiod . "'";
        $inoIdData = app($this->externalDatabaseService)->externalDatabaseExcuteSql($inoIdParam);
        if (!$inoIdData) {
            // 执行获取凭证类型流水号最大值+1的sql失败
            $this->addLog($operation, trans('integrationCenter.log.create_voucher_failed_excute_get_serial_number_sql_failed'), $logData);
            $this->deleteVoucher($databaseParam, $coutnoIdUuid);
            return ['code' => ['u8_out_send_failed', 'integrationCenter.log']];
        }
        $inoId = $inoIdData->maxIno_id ?? 1; // int
        if (!$inoId) {
            // 获取凭证类型流水号最大值+1失败
            $this->addLog($operation, trans('integrationCenter.log.create_voucher_failed_get_serial_number_failed'), $logData);
            $this->deleteVoucher($databaseParam, $coutnoIdUuid);
            return ['code' => ['u8_out_send_failed', 'integrationCenter.log']];
        }
        //更新ino_id
        $updateInoIdParam = $databaseParam;
        $updateInoIdParam['sql'] = "update GL_accvouch set ino_id='" . $inoId . "' where coutno_id='" . $coutnoIdUuid . "'";
        $updateInoIdRes = app($this->externalDatabaseService)->externalDatabaseExcuteSql($updateInoIdParam);
        if (!$updateInoIdRes) {
            // 更新ino_id失败 删除生成凭证的数据
            $this->addLog($operation, trans('integrationCenter.log.create_voucher_failed_update_ino_id_failed'), $logData);
            $this->deleteVoucher($databaseParam, $coutnoIdUuid);
            return ['code' => ['u8_out_send_failed', 'integrationCenter.log']];
        }
        // 生成coutno_id并替换
        $coutnoId = '';
        $coutnoIdParam = $databaseParam;
        $coutnoIdParam['sql'] = "Select 'GL'+RIGHT('10000000000000'+convert(varchar,MAX(SUBSTRING(coutno_id, 3, 20))+1),13) coutno_id From GL_accvouch where coutno_id <>'" . $coutnoIdUuid . "' and coutno_id like 'GL%'";
        $coutnoIdRes = app($this->externalDatabaseService)->externalDatabaseExcuteSql($coutnoIdParam);
        if ($coutnoIdRes) {
            $coutnoId = $coutnoIdRes->coutno_id ?? '';
        }
        if (!$coutnoId) {
            $coutnoIdParam['sql'] = "Select count(*) cnt From GL_accvouch where coutno_id = 'GL0000000000001'";
            $coutnoIdRes = app($this->externalDatabaseService)->externalDatabaseExcuteSql($coutnoIdParam);
            if ($coutnoIdRes && isset($coutnoIdRes->cnt) && $coutnoIdRes->cnt == 0) {
                $coutnoId = 'GL0000000000001';
            }
        }
        if (!$coutnoId) {
            // 获取coutno_id失败
            $this->addLog($operation, trans('integrationCenter.log.create_voucher_failed_get_coutno_id_failed'), $logData);
            $this->deleteVoucher($databaseParam, $coutnoIdUuid);
            return ['code' => ['u8_out_send_failed', 'integrationCenter.log']];
        }
        $coutnoIdParam['sql'] = "Select count(*) cnt From GL_accvouch where coutno_id = '" . $coutnoId . "'";
        $coutnoIdRes = app($this->externalDatabaseService)->externalDatabaseExcuteSql($coutnoIdParam);
        if ($coutnoIdRes && isset($coutnoIdRes->cnt) && $coutnoIdRes->cnt > 0) {
            // 当前coutno_id已存在凭证
            $this->addLog($operation, trans('integrationCenter.log.create_voucher_failed_coutno_id_exist_voucher'), $logData);
            $this->deleteVoucher($databaseParam, $coutnoIdUuid);
            return ['code' => ['u8_out_send_failed', 'integrationCenter.log']];
        }
        // 修改coutno_id
        $coutnoIdParam['sql'] = "update GL_accvouch set coutno_id='" . $coutnoId . "' where coutno_id='" . $coutnoIdUuid . "'";
        $coutnoIdRes = app($this->externalDatabaseService)->externalDatabaseExcuteSql($coutnoIdParam);
        if (!$coutnoIdRes) {
            // 修改coutno_id失败
            $this->addLog($operation, trans('integrationCenter.log.create_voucher_failed_update_coutno_id_failed'), $logData);
            $this->deleteVoucher($databaseParam, $coutnoIdUuid);
            return ['code' => ['u8_out_send_failed', 'integrationCenter.log']];
        }
        // 验证之前的coutnoIdUuid是否替换完成 未完成失败
        $coutnoIdParam['sql'] = "Select count(*) cnt From GL_accvouch where coutno_id = '" . $coutnoIdUuid . "'";
        $coutnoIdRes = app($this->externalDatabaseService)->externalDatabaseExcuteSql($coutnoIdParam);
        if ($coutnoIdRes && isset($coutnoIdRes->cnt) && $coutnoIdRes->cnt > 0) {
            // 修改coutno_id失败
            $this->addLog($operation, trans('integrationCenter.log.create_voucher_failed_coutno_id_exist_voucher'), $logData);
            $this->deleteVoucher($databaseParam, $coutnoIdUuid);
            return ['code' => ['u8_out_send_failed', 'integrationCenter.log']];
        }
        // [step-5]解析现金流，插入 gl_cashtable
        // todo
        if ($cashitem) {
            foreach ($cashitem as $cashKey => $cashValue) {
                $cashValue['iperiod'] = $iperiod;
                $cashValue['iSignSeq'] = $isignseq;
                $cashValue['dbill_date'] = $dbillDate;
                $cashValue['csign'] = $csign;
                $cashValue['iyear'] = $iyear;
                $cashValue['iYPeriod'] = $iyear;
                $cashValue['ino_id'] = $inoId;
            }
            $databaseInfo = [
                'table_name' => 'gl_cashtable',
            ];
            $insertCashResult = app("App\EofficeApp\System\ExternalDatabase\Services\ExternalDatabaseService")->sendDataToExternalDatabase($databaseInfo, $cashitem);
            if (!$insertCashResult) {
                //现金流插入失败
                $this->deleteVoucher($databaseParam, $coutnoIdUuid, $inoId);
                $this->addLog($operation, trans('integrationCenter.log.create_voucher_failed_cashitem_handle_failed'), $logData);
                return ['code' => ['u8_out_send_failed', 'integrationCenter.log']];
            }
        }
        // OA这边流程和u8凭证关联表
        $relationRes = app($this->voucherIntergrationU8RelationFlowRunRepository)->insertData(['run_id' => $runId, 'ino_id' => $inoId, 'coutno_id' => $coutnoId]);
        if (!$relationRes) {
            $this->deleteVoucher($databaseParam, $coutnoIdUuid, $inoId);
            $this->addLog($operation, trans('integrationCenter.log.create_voucher_failed_related_flow_table_insert_failed'), $logData);
            return ['code' => ['u8_out_send_failed', 'integrationCenter.log']];
        }
        $this->addLog($operation, trans('integrationCenter.log.create_voucher_success'), $logData);
        return true;
    }

    /**
     * 凭证数据已插入表 生成凭证失败时删除对应数据
     *
     * @param [type] $databaseParam
     * @param [type] $coutnoId 外部凭证业务号
     * @param [type] $inoId   凭证流水号
     *
     * @return void
     * @author yuanmenglin
     * @since
     */
    public function deleteVoucher($databaseParam, $coutnoIdUuid, $inoId = 0)
    {
        if ($inoId) {
            $databaseParam['sql'] = "delete from GL_accvouch where ino_id='" . $inoId . "'";
        } else {
            $databaseParam['sql'] = "delete from GL_accvouch where coutno_id='" . $coutnoIdUuid . "'";
        }
        $deleteInoIdRes = app($this->externalDatabaseService)->externalDatabaseExcuteSql($databaseParam);
        if (!$deleteInoIdRes) {
            // 更新ino_id失败 删除生成凭证的数据
            return false;
        }
        return true;
    }
    /**
     * 验证辅助核算项
     *
     * @param [type] $runId     流程id
     * @param [type] $databaseParam 链接数据库所需参数
     * @param [type] $iyear 年度
     * @param [type] $ccode 科目
     * @param [type] $auxiliaryAccounting   辅助核算值；项目大类编码字段获取
     * @param [type] $auxiliaryAccountingType  判断是否何种辅助核算类别标识<br>citem_class：项目大类编码；<br>citem_id：项目小类编码；<br>cdept_id：部门编码；<br>cperson_id：人员编码；<br>ccus_id：客户编码；<br>csup_id：供应商编码；
     *
     * @return void
     * @author yuanmenglin
     * @since
     */
    public function auxiliaryCheck($runId, $databaseParam, $iyear, $ccode, $auxiliaryAccounting, $auxiliaryAccountingType)
    {
        // 凭证预览 未发起流程 解析辅助核算项 -- 科目解析
        if (strpos($ccode, '-') !== false && !$runId) {
            $ccodeArray = explode('-', $ccode);
            $ccode = isset($ccodeArray[0]) ? $ccodeArray[0] : '';
        }
        if (!$ccode) {
            return '';
        }
        switch ($auxiliaryAccountingType) {
            case 'citem_class':
                $auxiliaryAccountingTypeName = "项目大类编码";
                $sql = "select case when (a.bitem=1) then a.cass_item else '' end newvalue, a.bitem needvalue from code a where a.iyear = $iyear and a.ccode = '" . $ccode . "'";
                break;
            case 'citem_id':
                $auxiliaryAccountingTypeName = "项目小类编码";
                $sql = "select case when (a.bitem=1) then '" . $auxiliaryAccounting . "' else '' end newvalue, a.bitem needvalue from code a where a.iyear = $iyear and a.ccode = '" . $ccode . "' ";
                break;
            case 'cdept_id':
                $auxiliaryAccountingTypeName = "部门编码";
                $sql = "select case when ((a.bdept=1) or (a.bperson=1)) then '" . $auxiliaryAccounting . "' else '' end newvalue, a.bdept needvalue, a.bperson needvalue_bperson from code a where a.iyear = $iyear and a.ccode = '" . $ccode . "' ";
                break;
            case 'cperson_id':
                $auxiliaryAccountingTypeName = "人员编码";
                $sql = "select case when (a.bperson=1) then '" . $auxiliaryAccounting . "' else '' end newvalue, a.bperson needvalue from code a  where a.iyear = $iyear and a.ccode = '" . $ccode . "' ";
                break;
            case 'ccus_id':
                $auxiliaryAccountingTypeName = "客户编码";
                $sql = "select case when (a.bcus=1) then '" . $auxiliaryAccounting . "' else '' end newvalue, a.bcus needvalue from code a where a.iyear = $iyear and a.ccode = '" . $ccode . "' ";
                break;
            case 'csup_id':
                $auxiliaryAccountingTypeName = "供应商编码";
                $sql = "select case when (a.bsup=1) then '" . $auxiliaryAccounting . "' else '' end newvalue, a.bsup needvalue from code a where a.iyear = $iyear and a.ccode = '" . $ccode . "' ";
                break;
            default:
                return '';
        }
        $selectParam = $databaseParam;
        $selectParam['sql'] = $sql;
        $selectRes = app($this->externalDatabaseService)->externalDatabaseExcuteSql($selectParam);
        if (!$selectRes) {
            throw new Exception(trans('integrationCenter.u8_ccode') . $ccode . trans('integrationCenter.select_failed') . $sql);
        }
        $needvalue = 0;
        $newAuxiliaryAccounting = $selectRes->newvalue ?? '';
        $selectResNeedValue = $selectRes->needvalue ?? 0;
        if ($auxiliaryAccountingType == 'cdept_id') {
            if ($selectResNeedValue === 1 || (isset($selectRes->needvalue_bperson) && $selectRes->needvalue_bperson === 1)) {
                $needvalue = 1;
            } else if (trim($selectResNeedValue) === 'true' || (isset($selectRes->needvalue_bperson) && trim($selectRes->needvalue_bperson) === 'true')) {
                $needvalue = 1;
            }
        } else {
            if ($selectResNeedValue === 1 || trim($selectResNeedValue) === 'true') {
                $needvalue = 1;
            }
        }
        if ($needvalue && !$newAuxiliaryAccounting) {
            throw new Exception(trans('integrationCenter.u8_ccode') . $ccode . trans('must_auxiliary_account') . $auxiliaryAccountingTypeName);
        }
        return $newAuxiliaryAccounting;
    }
    /**
     * 获取科目信息
     *
     * @param [type] $data 参数数组
     * [type  获取所有 all  获取正在使用的借方科目数组 debit  获取正在使用的贷方科目数组 credit]
     * [company_id 公司id]
     * @return void
     * @author yuanmenglin
     * @since
     */
    public function getCodeConfigSelect($data)
    {
        $companyId = $data['company_id'] ?? '';
        $companyInfo = [];
        if ($companyId) {
            $companyInfo = $this->getOneCompanyConfig([], $companyId);
        }
        // 没有公司信息 默认公司
        if (!isset($companyInfo['company']) || !$companyInfo['company']) {
            $defaultCompany = app($this->voucherIntergrationU8CompanyConfigRepository)->getOneFieldInfo(['set_default' => [1]]);
            if ($defaultCompany) {
                $companyInfo = $this->getOneCompanyConfig([], $defaultCompany->company_id);
            }
        }
        $company = $companyInfo['company'] ?? [];
        // 未选择公司也未设置默认公司  新建公司预览的传递公司id = '0'
        if (!$company && $companyId !== '0') {
            return [];
        }
        $iyear = $data['iyear'] ?? ($company['iyear'] ?? '');
        $type = $data['type'] ?? '';
        $databaseCode = $selectCode = $uploadCode = [];
        if (isset($data['database']) && !empty($data['database'])) {
            $database = $data['database'];
        } else {
            $database = $company['company_account_name'] ?? '';
        }
        // 未获取到账套
        if (!$database) {
            return [];
        }
        // 编辑公司页面获取所有类型的科目数据
        if ($type == 'all') {
            $databaseCode = [
                'debit' => [],
                'credit' => [],
            ];
            $debitDatabase = $companyInfo['debitDatabase'] ?? [];
            $creditDatabase = $companyInfo['creditDatabase'] ?? [];
            $codeTypeArray = [];
            if ($debitDatabase || $creditDatabase) {
                if ($debitDatabase) {
                    $databaseParam = [
                        'database_id' => $debitDatabase->database_id, //外部数据库id 必填
                        "database" => $database,
                        'all' => 1,
                    ];
                    $codeId = $debitDatabase->code_id_field;
                    $codeName = $debitDatabase->code_name_field;
                    $table = $debitDatabase->database_table;
                    $codeType = $debitDatabase->code_type;
                    $codeNameRevealId = $debitDatabase->code_name_reveal_id ?? '';
                    $databaseParam['sql'] = 'SELECT ' . $codeId . ', ' . $codeName . ' FROM ' . $table;
                    if ($iyear) {
                        $databaseParam['sql'] .= " where iyear = $iyear ";
                    }
                    $codeType = json_decode($codeType, 1);
                    if ($codeType && is_array($codeType)) {
                        $codeTypeArray = array_filter($codeType, function ($val) {
                            return $val == 1;
                        });
                        if ($codeTypeArray) {
                            $databaseParam['sql'] .= ' and ( ';
                            foreach ($codeTypeArray as $typeKey => $typeValue) {
                                $databaseParam['sql'] .= " cclass_engl = '$typeKey' or ";
                            }
                            $databaseParam['sql'] = substr($databaseParam['sql'], 0, -3);
                            $databaseParam['sql'] .= ')';
                        } else {
                            // $databaseParam['sql'] .= " where cclass_engl = 'SY' or cclass_engl = 'CB'";
                        }
                    } else {
                        // $databaseParam['sql'] .= " where cclass_engl = 'SY' or cclass_engl = 'CB'";
                    }
                    $databaseParam['sql'] .= ' ORDER BY '. $codeId;
                    $databaseCode['debit'] = app($this->externalDatabaseService)->externalDatabaseExcuteSql($databaseParam);
                    if ($databaseCode['debit']){
                        foreach ($databaseCode['debit'] as $val) {
                            $codeIdValue = $val->$codeId;
                            $codeNameValue = $val->$codeName;
                            $val->code_id = $codeIdValue;
                            $val->code_name = ($codeNameRevealId == '1') ? $codeNameValue . '('. $codeIdValue .')' : $codeNameValue;
                        }
                    }
                }
                if ($creditDatabase) {
                    $databaseParam = [
                        'database_id' => $creditDatabase->database_id, //外部数据库id 必填
                        'database' => $database,
                        'all' => 1,
                    ];
                    $codeId = $creditDatabase->code_id_field;
                    $codeName = $creditDatabase->code_name_field;
                    $table = $creditDatabase->database_table;
                    // $databaseParam['sql'] = 'SELECT '. $codeId. ', '. $codeName . ' FROM '. $table . " where cclass_engl = 'ZC'";
                    $codeType = $creditDatabase->code_type ?? '';
                    $codeNameRevealId = $creditDatabase->code_name_reveal_id ?? '';
                    $databaseParam['sql'] = 'SELECT ' . $codeId . ', ' . $codeName . ' FROM ' . $table;
                    if ($iyear) {
                        $databaseParam['sql'] .= " where iyear = $iyear ";
                    }
                    $codeType = json_decode($codeType, 1);
                    if ($codeType && is_array($codeType)) {
                        $codeTypeArray = array_filter($codeType, function ($val) {
                            return $val == 1;
                        });
                        if ($codeTypeArray) {
                            $databaseParam['sql'] .= ' and ( ';
                            foreach ($codeTypeArray as $typeKey => $typeValue) {
                                $databaseParam['sql'] .= " cclass_engl = '$typeKey' or ";
                            }
                            $databaseParam['sql'] = substr($databaseParam['sql'], 0, -3);
                            $databaseParam['sql'] .= ')';
                        } else {
                            // $databaseParam['sql'] .= " where cclass_engl = 'ZC'";
                        }
                    } else {
                        // $databaseParam['sql'] .= " where cclass_engl = 'ZC'";
                    }
                    $databaseParam['sql'] .= ' ORDER BY ' . $codeId;
                    $databaseCode['credit'] = app($this->externalDatabaseService)->externalDatabaseExcuteSql($databaseParam);
                    if ($databaseCode['credit']){
                        foreach ($databaseCode['credit'] as $val) {
                            $codeIdValue = $val->$codeId;
                            $codeNameValue = $val->$codeName;
                            $val->code_id = $codeIdValue;
                            $val->code_name = ($codeNameRevealId == '1') ? $codeNameValue . '('. $codeIdValue .')' : $codeNameValue;
                        }
                    }
                }
            }
            $selectCode = [
                'debit' => $companyInfo['debitSelect'] ?? [],
                'credit' => $companyInfo['creditSelect'] ?? [],
            ];
            $uploadCode = [
                'debit' => $companyInfo['debitUploadCourses'] ?? [],
                'credit' => $companyInfo['creditUploadCourses'] ?? [],
            ];
            return compact('databaseCode', 'selectCode', 'uploadCode');
        } else {
            $code = [];
            // 修改数据库数据源时可传入科目编码、科目名称字段和数据表名即时获取预览使用
            $codeId = $data['code_id'] ?? '';
            $codeName = $data['code_name'] ?? '';
            $table = $data['table'] ?? '';
            $databaseId = $data['database_id'] ?? '';
            $codeType = $data['code_type'] ?? '';
            // 在科目名称后显示科目编码
            $codeNameRevealId = $data['code_name_reveal_id'] ?? '';
            if ($type == 'debit') {
                $courseType = $company['debit_course_type'] ?? 'database';
                switch ($courseType) {
                    case 'database':
                        $debitDatabase = $companyInfo['debitDatabase'] ?? [];
                        $databaseParam = [
                            'database_id' => !empty($databaseId) ? $databaseId : $debitDatabase->database_id, //外部数据库id 必填
                            "database" => $database,
                            'all' => 1,
                        ];
                        $codeId = !empty($codeId) ? $codeId : ($debitDatabase->code_id_field ?? '');
                        $codeName = !empty($codeName) ? $codeName : ($debitDatabase->code_name_field ?? '');
                        $table = !empty($table) ? $table : ($debitDatabase->database_table ?? '');
                        $codeType = !empty($codeType) ? $codeType : ($debitDatabase->code_type ?? '');
                        $codeNameRevealId = !empty($codeNameRevealId) ? $codeNameRevealId : ($debitDatabase->code_name_reveal_id ?? '');
                        $databaseParam['sql'] = 'SELECT ' . $codeId . ', ' . $codeName . ' FROM ' . $table;
                        if ($iyear) {
                            $databaseParam['sql'] .= " where iyear = $iyear";
                        }
                        if ($codeType) {
                            $codeTypeArray = array_filter(json_decode($codeType, 1), function ($val) {
                                return $val == 1;
                            });
                            if ($codeTypeArray) {
                                $databaseParam['sql'] .= ' and ( ';
                                foreach ($codeTypeArray as $typeKey => $typeValue) {
                                    $databaseParam['sql'] .= " cclass_engl = '$typeKey' or ";
                                }
                                $databaseParam['sql'] = substr($databaseParam['sql'], 0, -3);
                                $databaseParam['sql'] .= ')';
                            } else {
                                // $databaseParam['sql'] .= " where cclass_engl = 'SY' or cclass_engl = 'CB'";
                            }
                        } else {
                            // $databaseParam['sql'] .= " where cclass_engl = 'SY' or cclass_engl = 'CB'";
                        }
                        $databaseParam['sql'] .= ' ORDER BY ' . $codeId;
                        $code = app($this->externalDatabaseService)->externalDatabaseExcuteSql($databaseParam);
                        if ($code){
                            foreach ($code as $val) {
                                $codeIdValue = $val->$codeId;
                                $codeNameValue = $val->$codeName;
                                $val->code_id = $codeIdValue;
                                $val->code_name = ($codeNameRevealId == '1') ? $codeNameValue . '('. $codeIdValue .')' : $codeNameValue;
                            }
                        }
                        break;
                    case 'select':
                        $code = $companyInfo['debitSelect'] ?? [];
                        break;
                    case 'upload':
                        $code = $companyInfo['debitUploadCourses'] ?? [];
                        break;
                    default:
                        break;
                }
                return $code;
            } elseif ($type == 'credit') {
                $courseType = $company['credit_course_type'] ?? 'database';
                switch ($courseType) {
                    case 'database':
                        $creditDatabase = $companyInfo['creditDatabase'] ?? [];
                        $databaseParam = [
                            'database_id' => !empty($databaseId) ? $databaseId : $creditDatabase->database_id, //外部数据库id 必填
                            "database" => $database,
                            'all' => 1,
                        ];
                        $codeId = !empty($codeId) ? $codeId : ($creditDatabase->code_id_field ?? '');
                        $codeName = !empty($codeName) ? $codeName : ($creditDatabase->code_name_field ?? '');
                        $table = !empty($table) ? $table : ($creditDatabase->database_table ?? '');
                        // $databaseParam['sql'] = 'SELECT '. $codeId. ', '. $codeName . ' FROM '. $table . " where cclass_engl = 'ZC'";
                        $codeType = !empty($codeType) ? $codeType : ($creditDatabase->code_type ?? '');
                        $codeNameRevealId = !empty($codeNameRevealId) ? $codeNameRevealId : ($creditDatabase->code_name_reveal_id ?? '');
                        $databaseParam['sql'] = 'SELECT ' . $codeId . ', ' . $codeName . ' FROM ' . $table;
                        if ($iyear) {
                            $databaseParam['sql'] .= " where iyear = $iyear ";
                        }
                        if ($codeType) {
                            $codeTypeArray = array_filter(json_decode($codeType, 1), function ($val) {
                                return $val == 1;
                            });
                            if ($codeTypeArray) {
                                $databaseParam['sql'] .= ' and ( ';
                                foreach ($codeTypeArray as $typeKey => $typeValue) {
                                    $databaseParam['sql'] .= " cclass_engl = '$typeKey' or ";
                                }
                                $databaseParam['sql'] = substr($databaseParam['sql'], 0, -3);
                                $databaseParam['sql'] .= ')';
                            } else {
                                // $databaseParam['sql'] .= " where cclass_engl = 'ZC'";
                            }
                        } else {
                            // $databaseParam['sql'] .= " where cclass_engl = 'ZC'";
                        }
                        $databaseParam['sql'] .= ' ORDER BY ' . $codeId;
                        $code = app($this->externalDatabaseService)->externalDatabaseExcuteSql($databaseParam);
                        if ($code) {
                            foreach ($code as $val) {
                                $codeIdValue = $val->$codeId;
                                $codeNameValue = $val->$codeName;
                                $val->code_id = $codeIdValue;
                                $val->code_name = ($codeNameRevealId == '1') ? $codeNameValue . '('. $codeIdValue .')' : $codeNameValue;
                            }
                        }
                        break;
                    case 'select':
                        $code = $companyInfo['creditSelect'] ?? [];
                        break;
                    case 'upload':
                        $code = $companyInfo['creditUploadCourses'] ?? [];
                        break;
                    default:
                        break;
                }
                return $code;
            } else {
                return ['code' => ['course_type_error', 'integrationCenter']];
            }
        }
    }
    /**
     * 获取辅助项目核算项
     *
     * @param [type] $data
     * [company_id 公司id]
     * @return void
     * @author yuanmenglin
     * @since
     */
    public function getAuxiliaryConfigSelect($data)
    {
        $companyId = $data['company_id'] ?? '';
        $baseInfo = $this->getBaseInfo('u8');
        if (!$baseInfo) {
            // 未查到U8基本配置
            return [];
        }
        // 组织变量
        // 外部数据库 id
        $datasourceId = $baseInfo['database_config'] ?? 0; // todo
        if (!$datasourceId) {
            // 外部数据库id未配置
            return [];
        }
        $companyInfo = $this->getOneCompanyConfig([], $companyId);
        $company = $companyInfo['company'] ?? [];
        $database = $company['company_account_name'] ?? '';
        if (!$database) {
            $defaultCompany = app($this->voucherIntergrationU8CompanyConfigRepository)->getOneFieldInfo(['set_default' => [1]]);
            if ($defaultCompany) {
                $database = $defaultCompany->company_account_name ?? '';
            }
            if (!$database) {
                // 未设置账套数据库
                return [];
            }
        }
        $auxiliaryAccountingType = $data['type'] ?? '';
        switch ($auxiliaryAccountingType) {
            case 'citem_class': // 项目大类
                $table = 'fitem';
                $sql = 'SELECT citem_class, citem_name, citem_text from ' . $table;
                break;
            case 'citem_id': // 项目小类
                // 辅助核算项目小类根据父级大类id获取对应数据库名
                $citemClass = $data['citem_class'] ?? 97;
                $table = 'fitemss' . $citemClass;
                $sql = 'SELECT * from ' . $table;
                break;
            case 'cdept_id': // 部门
                $table = 'department';
                $sql = 'SELECT cDepName,cDepCode from ' . $table;
                break;
            case 'cperson_id': // 员工
                $table = 'person';
                $sql = 'SELECT cPersonCode, cPersonName, cDepCode from ' . $table;
                break;
            case 'ccus_id': // 客户
                $table = 'customer';
                $sql = 'SELECT cCusCode, cCusName, cCusAbbName, cCCCode from ' . $table;
                break;
            case 'csup_id': // 供应商
                $table = 'vendor';
                $sql = 'SELECT cVenCode, cVenName, cVenAbbName, cVCCode from ' . $table;
                break;
            case 'csign':
                $sql ='SELECT isignseq, csign, ctext from dsign';
                break;
            default:
                return ['code' => ['auxiliary_type_error', 'integrationCenter']];
        }
        $databaseParam = [
            'database_id' => $datasourceId, //外部数据库id 必填
            "database" => $database,
            'all' => 1, // 获取所有数据 不传默认获取第一条
            'sql' => $sql,
        ];
        $auxiliary = app($this->externalDatabaseService)->externalDatabaseExcuteSql($databaseParam);
        return $auxiliary;
    }
    /**
     * 获取凭证信息某个数据的值
     *
     * @param [type] $voucherConfig  对应凭证借方/贷方配置
     * @param [type] $flowData      流程数据
     * @param [type] $fieldName     字段名称
     * @param [type] $i             明细第几条 -1 为表单非明细 明细从0开始
     * @param [type] $runId
     *
     * @return void
     * @author yuanmenglin
     * @since
     */
    public function getVoucherValue($voucherConfig, $flowData, $fieldName, $i, $runId)
    {
        // 判断是否明细取值  当前$type是否来源明细字段
        $configFieldName = $voucherConfig[$fieldName] ?? '';
        $detailValue = '';
        if ($configFieldName) {
            $fieldValueArray = explode('_', $configFieldName);
            if ($fieldValueArray && count($fieldValueArray) > 2) {
                $detailValue = $fieldValueArray[0] . '_' . $fieldValueArray[1];
            }
        } else {
            return '';
        }
        if ($i >= 0) { // 来源明细数据  预览界面的没有runId 返回数据不同
            if ($detailValue) {
                if ($runId) {
                    $fieldValue = isset($flowData[$detailValue]) && isset($flowData[$detailValue][$configFieldName]) && isset($flowData[$detailValue][$configFieldName][$i]) ? $flowData[$detailValue][$configFieldName][$i] : '';
                } else {
                    $fieldValue = isset($flowData[$detailValue]) && isset($flowData[$detailValue][$i]) && isset($flowData[$detailValue][$i][$configFieldName]) ? $flowData[$detailValue][$i][$configFieldName] : '';
                    // 预览凭证 - 未发起流程 获取u8科目处理
                    if ($fieldValue && $fieldName == 'ccode' && isset($flowData[$detailValue][$i][$configFieldName.'_TEXT'])) {
                        $fieldValue .= '-' .$flowData[$detailValue][$i][$configFieldName.'_TEXT'];
                    }
                }
            } else {
                $fieldValue = $flowData[$configFieldName] ?? '';
                // 预览凭证 - 未发起流程 获取u8科目处理
                if (!$runId && $fieldValue && $fieldName == 'ccode' && isset($flowData[$configFieldName. '_TEXT'])) {
                    $fieldValue .= '-' .$flowData[$configFieldName. '_TEXT'];
                }
            }
        } else { //来源表单数据
            if ($detailValue) {
                $fieldValue = isset($flowData[$detailValue]) && isset($flowData[$detailValue][$configFieldName]) && isset($flowData[$detailValue][$configFieldName][0]) ? $flowData[$detailValue][$configFieldName][0] : '';
                // 预览凭证 - 未发起流程 获取u8科目处理
                if (!$runId && $fieldValue && $fieldName == 'ccode' && isset($flowData[$detailValue][$configFieldName.'_TEXT'])) {
                    $fieldValue .= '-' .$flowData[$detailValue][$i][$configFieldName.'_TEXT'][0];
                }
            } else {
                $fieldValue = $flowData[$configFieldName] ?? '';
                // 预览凭证 - 未发起流程 获取u8科目处理
                if (!$runId && $fieldValue && $fieldName == 'ccode' && isset($flowData[$configFieldName. '_TEXT'])) {
                    $fieldValue .= '-' .$flowData[$configFieldName. '_TEXT'];
                }
            }
        }
        return $fieldValue;
    }
    /**
     * 获取凭证借方或贷方一条数据的数组
     *
     * @param [type] $voucherConfig  对应凭证借方/贷方配置
     * @param [type] $flowData      流程数据
     * @param [type] $i             明细第几条 -1 为表单非明细 明细从0开始
     * @param [type] $layoutNumber  行号
     * @param [type] $runId         流程id
     * @param [type] $databaseParam 外部数据库配置数组
     * @param [type] $iyear         会计年度
     *
     * @return void
     * @author yuanmenglin
     * @since
     */
    public function getVoucherData($voucherConfig, $flowData, $i, $layoutNumber, $runId, $databaseParam, $iyear)
    {
        $md = (float) $this->getVoucherValue($voucherConfig, $flowData, 'md', $i, $runId);
        $mc = (float) $this->getVoucherValue($voucherConfig, $flowData, 'mc', $i, $runId);
        $ccode = $this->getVoucherValue($voucherConfig, $flowData, 'ccode', $i, $runId);
        $cdigest = $this->getVoucherValue($voucherConfig, $flowData, 'cdigest', $i, $runId);
        $idoc = $this->getVoucherValue($voucherConfig, $flowData, 'idoc', $i, $runId);
        $citemId = $this->getVoucherValue($voucherConfig, $flowData, 'citem_id', $i, $runId);
        $citemClass = $this->getVoucherValue($voucherConfig, $flowData, 'citem_class', $i, $runId);
        $cdeptId = $this->getVoucherValue($voucherConfig, $flowData, 'cdept_id', $i, $runId);
        $cpersonId = $this->getVoucherValue($voucherConfig, $flowData, 'cperson_id', $i, $runId);
        $ccusId = $this->getVoucherValue($voucherConfig, $flowData, 'ccus_id', $i, $runId);
        $csupId = $this->getVoucherValue($voucherConfig, $flowData, 'csup_id', $i, $runId);
        $dataItem = [
            'inid' => $layoutNumber, // 行号
            "cdigest" => $cdigest ?? null, //摘要
            "ccode" => $ccode, //科目编码
            "md" => $md, //借方金额
            "mc" => $mc, //贷方金额
            "idoc" => $idoc ?? 0, // 单据数
        ];
        try {
            if ($citemId) {
                $citemId = $this->auxiliaryCheck($runId, $databaseParam, $iyear, $ccode, $citemId, 'citem_id');
                if ($citemId) {
                    $dataItem['citem_id'] = $citemId;
                }
            }
            if ($citemClass) {
                $citemClass = $this->auxiliaryCheck($runId, $databaseParam, $iyear, $ccode, $citemClass, 'citem_class');
                if ($citemClass) {
                    $dataItem['citem_class'] = $citemClass;
                    // $citemClassText = $flowData[$creditVoucherConfig['citem_class']. '_TEXT'] ?? null;
                    // if($citemClassText && $citemClassText == '现金流量项目') {
                    //     $ccashitem = $dataItem['citem_id'];
                    // }
                }
            }
            if ($cdeptId) {
                $cdeptId = $this->auxiliaryCheck($runId, $databaseParam, $iyear, $ccode, $cdeptId, 'cdept_id');
                if ($cdeptId) {
                    $dataItem['cdept_id'] = $cdeptId;
                }
            }
            if ($cpersonId) {
                $cpersonId = $this->auxiliaryCheck($runId, $databaseParam, $iyear, $ccode, $cpersonId, 'cperson_id');
                if ($cpersonId) {
                    $dataItem['cperson_id'] = $cpersonId;
                }
            }
            if ($ccusId) {
                $ccusId = $this->auxiliaryCheck($runId, $databaseParam, $iyear, $ccode, $ccusId, 'ccus_id');
                if ($ccusId) {
                    $dataItem['ccus_id'] = $ccusId;
                }
            }
            if ($csupId) {
                $csupId = $this->auxiliaryCheck($runId, $databaseParam, $iyear, $ccode, $csupId, 'csup_id');
                if ($csupId) {
                    $dataItem['csup_id'] = $csupId;
                }
            }
        } catch (\Exception $e) {
            // 记录错误  $e->message()
//            dd($e->getMessage());
            if ($message = $e->getMessage()) {
                return ['error_code' => '1', 'message' => $message];
            }
            return false;
        }
        return $dataItem;
    }
    /**
     * 获取科目类型
     *
     * @param [type] $param
     *
     * @return void
     * @author yuanmenglin
     * @since
     */
    public function getCodeTypes($param)
    {
        $sql = 'select DISTINCT cclass_engl,cclass FROM code';
        $databaseParam = [
            'database_id' => $param['database_id'] ?? '', //外部数据库id 必填
            "database" => $param['database'] ?? '',
            'all' => 1, // 获取所有数据 不传默认获取第一条
            'sql' => $sql,
        ];
        $codeTypes = app($this->externalDatabaseService)->externalDatabaseExcuteSql($databaseParam);
        return is_array($codeTypes) ? $codeTypes : [];
    }

    public function getCodeIyears($param)
    {
        $sql = 'select DISTINCT iyear FROM code ORDER BY iyear DESC';
        $databaseParam = [
            'database_id' => $param['database_id'] ?? '', //外部数据库id 必填
            "database" => $param['database'] ?? '',
            'all' => 1, // 获取所有数据 不传默认获取第一条
            'sql' => $sql,
        ];
        $iyears = app($this->externalDatabaseService)->externalDatabaseExcuteSql($databaseParam);
        return is_array($iyears) ? $iyears : [];
    }
    /**
     * 新建凭证时自动解析表单根据表单控件标题匹配配置主表信息和借贷方信息
     *
     * @param [type] $formId    表单id
     * @param [type] $configId  新建的凭证id
     *
     * @return void
     * @author yuanmenglin
     * @since Undocumented function
     */
    public function autoCompleteVoucherConfig($formId, $configId)
    {
        $requiredField = [];
        if ($formId && $configId) {
            $formData = app($this->flowFormService)->getParseForm($formId);
            if ($formData) {
                $formData = array_column($formData, 'control_id', 'control_title');
                foreach ($formData as $formKey => $formVal) {
                    if (preg_match('/.*】/', $formKey, $matches) !== false) {
                        if (isset($matches[0])) {
                            $newKey = str_replace($matches[0], '', $formKey);
                            $formData[$newKey] = $formVal;
                            unset($formData[$formKey]);
                        }
                    }
                }
                $mainVoucherFields = [
                    'cbill' => trans('integrationCenter.cbill'),
                    'iyear' => trans('integrationCenter.iyear'),
                    'iperiod' => trans('integrationCenter.iperiod'),  //非必填
                    'csign' => trans('integrationCenter.csign'),
                    'dbill_date' => trans('integrationCenter.dbill_date'),
                ];
                $debitVoucherFields = [
                    'cdigest' => trans('integrationCenter.cdigest'),
                    'ccode' => trans('integrationCenter.debit_ccode'),
                    'md' => trans('integrationCenter.md'),
                    'idoc' => trans('integrationCenter.idoc'),
                    'citem_id' => trans('integrationCenter.citem_id'),
                    'citem_class' => trans('integrationCenter.citem_class'),
                    'cdept_id' => trans('integrationCenter.cdept_id'),
                    'cperson_id' => trans('integrationCenter.cperson_id'),
                    'ccus_id' => trans('integrationCenter.ccus_id'),
                    'csup_id' => trans('integrationCenter.csup_id'),
                ];
                $creditVoucherFields = [
                    'cdigest' => trans('integrationCenter.cdigest'),
                    'ccode' => trans('integrationCenter.credit_ccode'),
                    'mc' => trans('integrationCenter.mc'),
                    'idoc' => trans('integrationCenter.idoc'),
                    'citem_id' => trans('integrationCenter.citem_id'),
                    'citem_class' => trans('integrationCenter.citem_class'),
                    'cdept_id' => trans('integrationCenter.cdept_id'),
                    'cperson_id' => trans('integrationCenter.cperson_id'),
                    'ccus_id' => trans('integrationCenter.ccus_id'),
                    'csup_id' => trans('integrationCenter.csup_id'),
                ];
                $mainVoucherData = $debitVoucherData = $creditVoucherData = [];
                // 主表数据解析
                foreach ($mainVoucherFields as $mainKey => $mainVal) {
                    if (isset($formData[$mainVal]) && !empty($formData[$mainVal])) {
                        $mainVoucherData[$mainKey] = $formData[$mainVal];
                    } else {
                        if (in_array($mainKey, ['cbill', 'csign'])) {
                            $requiredField[$mainKey] = $mainVal;
                        }
                    }
                }
                // 借方数据解析
                foreach ($debitVoucherFields as $debitKey => $debitVal) {
                    if (isset($formData[$debitVal]) && !empty($formData[$debitVal])) {
                        $debitVoucherData[$debitKey] = $formData[$debitVal];
                    } else {
                        if (in_array($debitKey, ['cdigest', 'ccode', 'md',])) { // 'idoc'
                            $requiredField['debit_' . $debitKey] = $debitVal;
                        }
                    }
                }
                //贷方数据解析
                foreach ($creditVoucherFields as $creditKey => $creditVal) {
                    if (isset($formData[$creditVal]) && !empty($formData[$creditVal])) {
                        $creditVoucherData[$creditKey] = $formData[$creditVal];
                    } else {
                        if (in_array($creditKey, ['cdigest', 'ccode', 'mc',])) { // 'idoc'
                            $requiredField['credit_' . $creditKey] = $creditVal;
                        }
                    }
                }
                // 插入数据库
                if ($mainVoucherData) {
                    // 会计年度和会计期间 一个有值一个没值互相取
                    $iyear = $mainVoucherData['iyear'] ?? '';
                    $iperiod = $mainVoucherData['iperiod'] ?? '';
                    if (!$iyear && $iperiod){
                        $mainVoucherData['iyear'] = $iperiod;
                    }
                    if ($iyear && !$iperiod){
                        $mainVoucherData['iperiod'] = $iyear;
                    }
                    app($this->voucherIntergrationU8MainConfigRepository)->updateData($mainVoucherData, ['voucher_config_id' => $configId]);
                }
                if ($debitVoucherData) {
                    $debitVoucherData['voucher_config_id'] = $configId;
                    $debitVoucherData['debit_credit_type'] = 'debit';
                    app($this->voucherIntergrationU8FieldConfigRepository)->insertData($debitVoucherData);
                }
                if ($creditVoucherData) {
                    $creditVoucherData['voucher_config_id'] = $configId;
                    $creditVoucherData['debit_credit_type'] = 'credit';
                    app($this->voucherIntergrationU8FieldConfigRepository)->insertData($creditVoucherData);
                }
            }
        }
        return $requiredField;
    }

    /** 凭证预览默认表单所需数据
     * @return array
     */
    public function previewDefaultSource($param)
    {
        // 账套库ID 没有的取默认数据
        $companyId = $param['databaseId'] ?? 0;
        $type = $param['type'] ?? '';
        if (!$type) {
             return [];
        }
        switch ($type) {
            case 'company':
                return $this->previewDefaultCompany();
                break;
            case 'csign':
                return $this->previewDefaultCsign($companyId);
                break;
            case 'credit_code':
                return $this->previewDefaultCreditCode($companyId);
                break;
            case 'debit_code':
                return $this->previewDefaultDebitCode($companyId);
                break;
            default:
                return [];
        }
    }

    private function previewDefaultCompany()
    {
        $compamies = app($this->voucherIntergrationU8CompanyConfigRepository)->getFieldInfo([]);
        return $compamies ?? [];
    }

    private function previewDefaultCsign($companyId)
    {
//        if (!$companyId) {
            $pay = trans('integrationCenter.pay');
            $transfer = trans('integrationCenter.transfer');
            $collect = trans('integrationCenter.collect');
            return [
                [
                    'key' => 'income',
                    'value' => $collect
                ],
                [
                    'key' => 'pay',
                    'value' => $pay
                ],
                [
                    'key' => 'transfer',
                    'value' => $transfer
                ],
            ];
//        } else {

//        }
    }

    private function previewDefaultDebitCode($companyId)
    {
        // 选择账套库后 需要通过账套去获取数据
        if (!$companyId) {
            return [
                [
                    'ccode' => '5001-'.trans('integrationCenter.generate_costs'),
                    'ccode_name' => trans('integrationCenter.generate_costs')
                ],
                [
                    'ccode' => '5101-'.trans('integrationCenter.manufacturing_costs'),
                    'ccode_name' => trans('integrationCenter.manufacturing_costs')
                ],
                [
                    'ccode' => '5201-'.trans('integrationCenter.service_costs'),
                    'ccode_name' => trans('integrationCenter.service_costs')
                ],
                [
                    'ccode' => '5301-'.trans('integrationCenter.research_costs'),
                    'ccode_name' => trans('integrationCenter.research_costs')
                ],
                [
                    'ccode' => '5501-'. trans('integrationCenter.management_costs'),
                    'ccode_name' => trans('integrationCenter.management_costs')
                ],
                [
                    'ccode' => '5502-'. trans('integrationCenter.sales_costs'),
                    'ccode_name' => trans('integrationCenter.sales_costs')
                ],
                [
                    'ccode' => '5502-' . trans('integrationCenter.travel_expenses'),
                    'ccode_name' => trans('integrationCenter.travel_expenses')
                ],
            ];
        } else {
            return $this->getCodeConfigSelect(['type' => 'credit', 'companyId' => $companyId]);
        }
    }

    private function previewDefaultCreditCode($companyId)
    {
        // 选择账套库后 需要通过账套去获取数据
        if (!$companyId) {
            return [
                [
                    'ccode' => '1001-'. trans('integrationCenter.cash_on_hand'),
                    'ccode_name' => trans('integrationCenter.cash_on_hand')
                ],[
                    'ccode' => '1002-'. trans('integrationCenter.bank_card_payment'),
                    'ccode_name' => trans('integrationCenter.bank_card_payment')
                ],
            ];
        } else {
            return $this->getCodeConfigSelect(['type' => 'debit', 'companyId' => $companyId]);
        }
    }

    public function previewVoucherData($param)
    {
        $flowId = $param['flow_id']?? '';
        $data = $param['data'] ?? [];
        $requiredParams = [
            'cbill' => trans('integrationCenter.cbill'),
            'csign' => trans('integrationCenter.csign'),
//            'debit_digest' => trans('integrationCenter.cdigest'),
            'debit_code' => trans('integrationCenter.debit_ccode'),
            'md' => trans('integrationCenter.md'),
            'credit_code' => trans('integrationCenter.credit_ccode'),
//            'credit_digest' => trans('integrationCenter.cdigest'),
            'mc' => trans('integrationCenter.mc'),
        ];
        if (!$data) {
            return ['code' => ['preview_params_required', 'integrationCenter']];
        }
        if ($flowId) {
            //解析配置
            $return = $this->yonyouVoucherOutSendPreview($data);
        } else {
            //默认数据源处理返回
            foreach ($requiredParams as $paramKey => $paramVal) {
                if (!isset($data[$paramKey]) || empty($data[$paramKey])) {
                    return ['code' => ['', trans('integrationCenter.please_fill_in_param'). $paramVal]];
                }
            }
            $upperMoney = digitUppercase($data['mc']);
            $mdCount = floor(strval($data['md'] * 100));
            $mcCount = floor(strval($data['mc'] * 100));
            if ($mdCount != $mcCount) {
                return ['code' => ['create_voucher_failed_money_not_equal', 'integrationCenter.log']];
            }
            $return = [
                'csign' => $data['csign'] ?? '',
                'dbill_date' => $data['dbill_date'] ?? date('Y-m-d'),
                'cbill' => $data['cbill'],
                'doc_count' => intval($data['credit_doc_number'] ?? 0) + intval($data['debit_doc_number'] ?? 0),
                'mc_count' => $mcCount,
                'mc_counts' => $this->moneyChangeArray($mcCount),
                'md_count' => $mdCount,
                'md_counts' => $this->moneyChangeArray($mdCount),
                'upper_money' => $upperMoney,
                'credits' => [
                    [
                        'cdigest' => $data['credit_digest'] ?? '',
                        'ccode_name' => $data['credit_code'],
                        'mc' => $mcCount,
                        'mcs' => $this->moneyChangeArray($mcCount)
                    ]
                ],
                'debits' => [
                    [
                        'cdigest' => $data['debit_digest'] ?? '',
                        'ccode_name' => $data['debit_code'],
                        'md' => $mdCount,
                        'mds' => $this->moneyChangeArray($mdCount)
                    ]
                ],
            ];
        }
        return $return;
    }

    public function yonyouVoucherOutSendPreview($flowData)
    {
        // 流程相关数据
        $runId = $flowData['run_id'] ?? 0;
        $formId = $flowData['form_id'] ?? 0;
        // 取表结构
        $formData = app($this->flowFormService)->getParseForm($formId);
        $formData = array_column($formData,NULL,'control_id');
        // 凭证配置id
        $voucherConfigId = isset($flowData['voucher_config']) ? $flowData['voucher_config'] : '';
        if (!$voucherConfigId) {
            // 参数中没有凭证配置id
            return ['code' => ['create_voucher_failed_get_voucher_config_id_failed', 'integrationCenter.log']];
        }
        // 获取凭证配置 --基本配置、借方配置、贷方配置
        $voucherConfig = $this->getVoucherConfig([], $voucherConfigId);
        $baseVoucherConfig = $voucherConfig['base_config'] ?? [];
        $debitVoucherConfig = $voucherConfig['debit_config'] ?? [];
        $creditVoucherConfig = $voucherConfig['credit_config'] ?? [];
        $currentDate = date_format(date_create(date("Y-m-d")), "Y-m-d H:i:s"); // todo-要参考已有项目看这里要用啥格式的年月日
        $baseInfo = $this->getBaseInfo('u8');
        if (!$baseInfo) {
            // 未查到U8基本配置
            return ['code' => ['create_voucher_failed_select_u8_voucher_base_config_empty', 'integrationCenter.log']];
        }
        $datasourceId = $baseInfo['database_config'] ?? 0; // todo
        if (!$datasourceId) {
            // 外部数据库id未配置
            return ['code' => ['create_voucher_failed_external_database_not_set', 'integrationCenter.log']];
        }
        // 表单字段，直接从 $flowData 里面用 $flowData['DATA_1'] 的方式获取
        if (!$flowData) {
            // 表单数据为空
            return ['code' => ['create_voucher_failed_form_data_empty', 'integrationCenter.log']];
        }
        $voucherDataArray = [];
        if (!$baseVoucherConfig) {
            // 主表信息配置无
            return ['code' => ['create_voucher_failed_voucher_main_config_not_set', 'integrationCenter.log']];
        }
        // 解析账套配置，获取要插入数据的库  --默认公司
        $companyId = $baseVoucherConfig['account'] ? ($flowData[$baseVoucherConfig['account']] ?? '') : '';
        $company = app($this->voucherIntergrationU8CompanyConfigRepository)->getDetail($companyId);
        $database = $company['company_account_name'] ?? '';
        if (!$database) {
            $defaultCompany = app($this->voucherIntergrationU8CompanyConfigRepository)->getOneFieldInfo(['set_default' => [1]]);
            if ($defaultCompany) {
                $database = $defaultCompany->company_account_name ?? '';
            }
            if (!$database) {
                // 未设置账套数据库
                return ['code' => ['create_voucher_failed_voucher_account_not_set', 'integrationCenter.log']];
            }
        }
        $databaseParam = [
            'database_id' => $datasourceId, //外部数据库id 必填
            "database" => $database,
        ];
        // [表单数据解析] dbill_date -- 制单日期$currentDate
        $dbillDate = $this->parseFlowDataOutSend($flowData,$formData,'dbill_date',$baseVoucherConfig);
        if (!$dbillDate) {
            // [制单日期]不能为空
            return ['code' => ['create_voucher_failed_voucher_dbill_not_empty', 'integrationCenter.log']];
        } else {
            // 且[制单日期]不能大于当前时间
            if($dbillDate > date('Y-m-d')) {
                return ['code' => ['create_voucher_failed_voucher_dbill_not_greater_current_time', 'integrationCenter.log']];
            }
        }
        // [表单数据解析] iyear -- 会计年度
        $iyearField = $baseVoucherConfig['iyear'] ?? '';
        $iyear = isset($flowData[$iyearField]) && (int) $flowData[$iyearField] ? date_format(date_create($flowData[$iyearField]), "Y") : date_format(date_create($currentDate), "Y"); // int
        // [表单数据解析] iperiod -- 会计期间 -- 两位
        $iperiodField = $baseVoucherConfig['iperiod'] ?? '';
        $iperiod = isset($flowData[$iperiodField]) && (int) $flowData[$iperiodField] ? date_format(date_create($flowData[$iperiodField]), "m") : date_format(date_create($currentDate), "m"); // int
        // 年月 拼接出来的，eg-201909
        $iyperiod = $iyear && $iperiod ? $iyear . '' . $iperiod : ''; // String
        // [表单数据解析] cbill -- 制单人
        $cbill = $this->parseFlowDataOutSend($flowData,$formData,'cbill',$baseVoucherConfig);
        if (!$cbill) {
            // 制单人不能为空
            return ['code' => ['create_voucher_failed_voucher_cbill_not_empty', 'integrationCenter.log']];
        }
        // [表单数据解析] csign -- 凭证类别字(银、转...)
        $csign = $flowData[$baseVoucherConfig['csign'] . '_TEXT'] ?? ($flowData[$baseVoucherConfig['csign']] ?? ''); // String
        if (!$csign) {
            // 凭证类型不能为空
            return ['code' => ['create_voucher_failed_voucher_csign_not_empty', 'integrationCenter.log']];
        }
        // 凭证类别的id，1,2,3， 用sql 去U8 查出来的，根据类别字，取的时候，要判断错误
        $extParam = $databaseParam;
        $extParam['sql'] = "Select isignseq From dsign where csign = '$csign'";
        $isignseqData = app($this->externalDatabaseService)->externalDatabaseExcuteSql($extParam);
//        if (!$isignseqData) {
//            // 执行获取凭证类型的id的sql失败
//            return ['code' => ['u8_out_send_failed', 'integrationCenter']];
//        }
//        dd($isignseqData);
//        $isignseq = $isignseqData->isignseq ?? 0; // int
//        if (!$isignseq) {
//            // 获取凭证类型的id失败
//            return ['code' => ['u8_out_send_failed', 'integrationCenter']];
//        }
        // [step-2]解析凭证[借方]
        if (!$debitVoucherConfig) {
            // 凭证借方相关未配置
            return ['code' => ['create_voucher_failed_voucher_debit_config_not_set', 'integrationCenter.log']];
        }
        // 现金流信息数组
        $layoutNumber = 1;
        $debitData = [];
        $mdCount = 0;
        $mcCount = 0;
        $docNumber = 0;
        // 定义一个[借方]信息设置数组，把[借方]字段都放进去
        $debitConfigFieldArray = ["cdigest", "ccode", "md", "idoc"];
        // [借方]解析好的值
        $debitConfigFieldRelationFormLayoutId = '';
        $detailLineCount = -1;
        // 设置规则：以金额字段为准，金额字段为明细则为多条，表单数据则单条
        $debitConfigFieldName = $debitVoucherConfig['md'] ?? '';
        if ($debitConfigFieldName) {
            $fieldValueArray = explode('_', $debitConfigFieldName);
            if ($fieldValueArray && count($fieldValueArray) > 2) {
                $fieldDetailLayoutId = $fieldValueArray[0] . '_' . $fieldValueArray[1];
                // 获取对应明细数据 获取明细行数最少的作为循环次数
                $fieldDetail = $flowData[$fieldDetailLayoutId] ?? [];
                $fieldDetailLineCount = count($fieldDetail);
                if ($fieldDetailLineCount == 0) {
                    return ['code' => ['create_voucher_failed_debit_get_detail_empty', 'integrationCenter.log']];
                } else {
                    $debitConfigFieldRelationFormLayoutId = $fieldDetailLayoutId;
                    $detailLineCount = $fieldDetailLineCount;
                }
            }
        } else {
            return ['code' => ['create_voucher_failed_voucher_debit_config_not_set_money_field', 'integrationCenter.log']];
        }
        // 如果借方配置，关联了明细布局，则根据此明细布局的行数进行循环，在循环中，解析[借方]信息设置，往 $debitData 插入多行值
        if ($debitConfigFieldRelationFormLayoutId != '') {
            // 注意处理借方字段的默认值
            if ($detailLineCount) {
                for ($i = 0; $i < $detailLineCount; $i++) {
                    $debitDataItem = $this->getVoucherData($debitVoucherConfig, $flowData, $i, $layoutNumber, $runId, $databaseParam, $iyear);
                    if (!$debitDataItem || isset($debitDataItem['error_code'])) {
                        // 解析借方数据异常
                        if (isset($debitDataItem['error_code']) && isset($debitDataItem['message'])) {
                            return ['code' => ['', $debitDataItem['message']]];
                        }
                        return ['code' => ['create_voucher_failed_debit_data_not_empty', 'integrationCenter.log']];
                    }
                    // 明细数据传递过程中都没有填写的数据
                    if (!$debitDataItem['ccode'] && !$debitDataItem['md'] && !$debitDataItem['cdigest'] && !$debitDataItem['idoc']) {
                        continue;
                    }
                    if (!$debitDataItem['ccode']) {
                        // 科目代码不能为空
                        return ['code' => ['create_voucher_failed_debit_account_code_empty', 'integrationCenter.log']];
                    }
                    if (!$debitDataItem['md'] || $debitDataItem['md'] == 0) {
                        // 借方金额不能为0
                        return ['code' => ['create_voucher_failed_debit_money_not_zero', 'integrationCenter.log']];
                    }
                    $debitData[] = $debitDataItem;
                    $layoutNumber++;
                    $mdCount += $debitDataItem['md'];
                    $docNumber += intval($debitDataItem['idoc']) ?? 0;
                }
            }
        } else {
            // 如果借方配置，没有关联明细布局，那么解析[借方]信息设置，往 $debitData 内插入一行数据 // 注意处理借方字段的默认值 // 按表单主数据来获取
            $debitDataItem = $this->getVoucherData($debitVoucherConfig, $flowData, -1, $layoutNumber, $runId, $databaseParam, $iyear);
            if (!$debitDataItem || isset($debitDataItem['error_code'])) {
                if (isset($debitDataItem['error_code']) && isset($debitDataItem['message'])) {
                    return ['code' => ['', $debitDataItem['message']]];
                }
                return ['code' => ['create_voucher_failed_debit_data_not_empty', 'integrationCenter.log']];
            }
            if (!$debitDataItem['ccode']) {
                // 科目代码不能为空
                return ['code' => ['create_voucher_failed_debit_account_code_empty', 'integrationCenter.log']];
            }
            if (!$debitDataItem['md'] || $debitDataItem['md'] == 0) {
                // 借方金额不能为0
                return ['code' => ['create_voucher_failed_debit_money_not_zero', 'integrationCenter.log']];
            }
            $debitData[] = $debitDataItem;
            $layoutNumber++;
            $mdCount += $debitDataItem['md'];
            $docNumber += intval($debitDataItem['idoc']) ?? 0;
        }
        if (!$debitData) {
            // 借方数据不能为空
            return ['code' => ['create_voucher_failed_debit_data_not_empty', 'integrationCenter.log']];
        }
        // [step-3]解析凭证[贷方]信息设置(参考借方)
        $creditConfigFieldArray = ["cdigest", "ccode", "mc", "idoc"];
        $creditData = [];
        $creditConfigFieldRelationFormLayoutId = '';
        $detailLineCount = -1;
        // 设置规则：以金额字段为准，金额字段为明细则为多条，表单数据则单条
        $creditConfigFieldName = $creditVoucherConfig['mc'] ?? '';
        if ($creditConfigFieldName) {
            $fieldValueArray1 = explode('_', $creditConfigFieldName);
            if ($fieldValueArray1 && count($fieldValueArray1) > 2) {
                $fieldDetailLayoutId = $fieldValueArray1[0] . '_' . $fieldValueArray1[1];
                // 获取对应明细数据 获取明细行数最少的作为循环次数
                $fieldDetail = $flowData[$fieldDetailLayoutId] ?? [];
                $fieldDetailLineCount = count($fieldDetail);
                if ($fieldDetailLineCount == 0) {
                    return ['code' => ['create_voucher_failed_credit_get_detail_empty', 'integrationCenter.log']];
                } else {
                    $detailLineCount = $fieldDetailLineCount;
                    $creditConfigFieldRelationFormLayoutId = $fieldDetailLayoutId;
                }
            }
        } else {
            return ['code' => ['create_voucher_failed_voucher_credit_config_not_set_money_field', 'integrationCenter.log']];
        }
        // 如果贷方配置，关联了明细布局，则根据此明细布局的行数进行循环，在循环中，解析[贷方]信息设置，往 $creditData 插入多行值
        if ($creditConfigFieldRelationFormLayoutId != '') {
            // 注意处理贷方字段的默认值
            if ($detailLineCount) {
                for ($i = 0; $i < $detailLineCount; $i++) {
                    // 获取贷方数组
                    $creditDataItem = $this->getVoucherData($creditVoucherConfig, $flowData, $i, $layoutNumber, $runId, $databaseParam, $iyear);
                    if (!$creditDataItem || isset($creditDataItem['error_code'])) {
                        // 贷方数据不能为空
                        if (isset($creditDataItem['error_code']) && isset($creditDataItem['message'])) {
                            return ['code' => ['', $creditDataItem['message']]];
                        }
                        return ['code' => ['create_voucher_failed_credit_data_not_empty', 'integrationCenter.log']];
                    }
                    // 明细数据传递过程中都没有填写的数据
                    if (!$creditDataItem['ccode'] && !$creditDataItem['mc'] && !$creditDataItem['cdigest'] && !$creditDataItem['idoc']) {
                        continue;
                    }
                    if (!$creditDataItem['ccode']) {
                        // 科目代码不能为空
                        return ['code' => ['create_voucher_failed_credit_account_code_empty', 'integrationCenter.log']];
                    }
                    if (!$creditDataItem['mc'] || $creditDataItem['mc'] == 0) {
                        // 贷方金额不能为0
                        return ['code' => ['create_voucher_failed_credit_money_not_zero', 'integrationCenter.log']];
                    }
                    $mcCount += $creditDataItem['mc'];
                    $layoutNumber++;
                    $creditData[] = $creditDataItem;
                    $docNumber += intval($creditDataItem['idoc']) ?? 0;
                }
            }
        } else {
            // 如果贷方配置，没有关联明细布局，那么解析[贷方]信息设置，往 $creditData 内插入一行数据 // 注意处理贷方字段的默认值 // 按表单主数据来获取
            $creditDataItem = $this->getVoucherData($creditVoucherConfig, $flowData, -1, $layoutNumber, $runId, $databaseParam, $iyear);
            if (!$creditDataItem || isset($creditDataItem['error_code'])) {
                if (isset($creditDataItem['error_code']) && isset($creditDataItem['message'])) {
                    return ['code' => ['', $creditDataItem['message']]];
                }
                return ['code' => ['create_voucher_failed_credit_data_not_empty', 'integrationCenter.log']];
            }
            if (!$creditDataItem['ccode']) {
                // 科目代码不能为空
                return ['code' => ['create_voucher_failed_credit_account_code_empty', 'integrationCenter.log']];
            }
            if (!$creditDataItem['mc'] || $creditDataItem['mc'] == 0) {
                // 贷方金额不能为0
                return ['code' => ['create_voucher_failed_credit_money_not_zero', 'integrationCenter.log']];
            }
            $creditData[] = $creditDataItem;
            $mcCount += $creditDataItem['mc'];
            $docNumber += intval($creditDataItem['idoc']) ?? 0;
        }
        if (!$creditData) {
            // 贷方数据不能为空
            return ['code' => ['create_voucher_failed_credit_data_not_empty', 'integrationCenter.log']];
        }
        if ($mdCount != $mcCount) {
            // 生成凭证失败，凭证借贷金额不平
            return ['code' => ['create_voucher_failed_money_not_equal', 'integrationCenter.log']];
        }
        // 预览需要相关辅助核算项的名称
        foreach ($debitData as $debitKey => $debitVal) {
            $debitData[$debitKey] = $this->getAuxiliaryName($debitVal, $databaseParam);
            $md = floor(strval($debitVal['md'] * 100));
            $debitData[$debitKey]['md'] = $md;
            $debitData[$debitKey]['mds'] = $this->moneyChangeArray($md);
        }
        foreach ($creditData as $creditKey => $creditVal) {
            $creditData[$creditKey] = $this->getAuxiliaryName($creditVal, $databaseParam);
            $mc = floor(strval($creditVal['mc'] * 100));
            $creditData[$creditKey]['mc'] = $mc;
            $creditData[$creditKey]['mcs'] = $this->moneyChangeArray($mc);
        }
        $upperMoney = digitUppercase($mdCount);
        $mdCount = floor(strval($mdCount * 100));
        $mcCount = floor(strval($mcCount * 100));
        return [
            'iyear' => $iyear,
            'iperiod' => $iperiod,
            'csign' => $csign,
            'cbill' => $cbill,
            'dbill_date' => $dbillDate,
            'md_count' => $mdCount,
            'md_counts' => $this->moneyChangeArray($mdCount),
            'mc_count' => $mcCount,
            'mc_counts' => $this->moneyChangeArray($mcCount),
            'upper_money' => $upperMoney,
            'doc_count' => $docNumber,
            'credits'=>$creditData,
            'debits' => $debitData
        ];
        return $voucherDataArray;
    }

    private function getAuxiliaryName($data, $databaseParam)
    {
        $types = ['citem_class', 'citem_id', 'cdept_id', 'cperson_id', 'ccus_id', 'csup_id'];
        foreach ($types as $type) {
            if (isset($data[$type]) && !empty($data[$type])) {
                switch ($type) {
                    case 'citem_class': // 项目大类
                        $table = 'fitem';
                        $key = 'citem_name';
                        $sql = 'SELECT citem_name from ' . $table . ' where citem_class = ' . $data['citem_class'];
                        break;
                    case 'citem_id': // 项目小类
                        // 辅助核算项目小类根据父级大类id获取对应数据库名
                        $citemClass = $data['citem_class'] ?? 97;
                        $table = 'fitemss' . $citemClass;
                        $key = 'citemname';
                        $sql = 'SELECT * from ' . $table . ' where citemcode = ' . $data['citem_id'];
                        break;
                    case 'cdept_id': // 部门
                        $table = 'department';
                        $key = 'cDepName';
                        $sql = 'SELECT * from ' . $table . ' where cDepCode = ' . $data['cdept_id'];
                        break;
                    case 'cperson_id': // 员工
                        $table = 'person';
                        $key = 'cPersonName';
                        $sql = 'SELECT cPersonCode, cPersonName, cDepCode from ' . $table . ' where cPersonCode = '. "'" . $data['cperson_id']. "'";
                        break;
                    case 'ccus_id': // 客户
                        $table = 'customer';
                        $key = 'cCusName';
                        $sql = 'SELECT cCusCode, cCusName, cCusAbbName, cCCCode from ' . $table . ' where cCusCode = ' . "'" . $data['ccus_id']. "'";
                        break;
                    case 'csup_id': // 供应商
                        $table = 'vendor';
                        $key = 'cVenName';
                        $sql = 'SELECT cVenCode, cVenName, cVenAbbName, cVCCode from ' . $table . ' where cVenCode = ' . "'" . $data['csup_id'] . "'";
                        break;
                    default:
                        return $data;
                }
                $databaseParam['sql'] = $sql;
                $auxiliary = app($this->externalDatabaseService)->externalDatabaseExcuteSql($databaseParam);
                $data[$key] = $auxiliary ? $auxiliary->$key : '';
            }
        }

        return $data;
    }

    /** 解析金额为所需结构
     * @param $money
     * @return array
     */
    private function moneyChangeArray($money)
    {
        $length = strlen($money);
        $default = array_fill(0, 15, '');
        if (!$money) {
            return $default;
        }
        foreach(range(14, 0, -1) as $index) {
            if (15 - $index <= $length) {
                $default[$index] = substr($money, $index - 15, 1);
            } else {
                $default[$index] = '';
            }
        }
        return $default;
    }
}
