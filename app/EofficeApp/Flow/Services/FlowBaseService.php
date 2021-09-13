<?php
namespace App\EofficeApp\Flow\Services;

use App\EofficeApp\Base\BaseService;
use Cache;

/**
 * 流程基础service类
 *
 * @since  2020-08-07 创建
 */
class FlowBaseService extends BaseService
{
    public function __construct()
    {
        parent::__construct();
        $this->attachmentService                           = 'App\EofficeApp\Attachment\Services\AttachmentService';
        $this->attachmentRelRepository                     = 'App\EofficeApp\Attachment\Repositories\AttachmentRelRepository';
        $this->attachmentRelSearchRepository               = 'App\EofficeApp\Attachment\Repositories\AttachmentRelSearchRepository';
        $this->controlCollectionRepository                 = 'App\EofficeApp\Flow\Repositories\ControlCollectionRepository';
        $this->departmentRepository                        = 'App\EofficeApp\System\Department\Repositories\DepartmentRepository';
        $this->departmentService                           = 'App\EofficeApp\System\Department\Services\DepartmentService';
        $this->documentContentRepository                   = 'App\EofficeApp\Document\Repositories\DocumentContentRepository';
        $this->documentService                             = 'App\EofficeApp\Document\Services\DocumentService';
        $this->documentRevertRepository                    = 'App\EofficeApp\Document\Repositories\DocumentRevertRepository';
        $this->documentShareRepository                     = 'App\EofficeApp\Document\Repositories\DocumentShareRepository';
        $this->documentFolderRepository                    = 'App\EofficeApp\Document\Repositories\DocumentFolderRepository';
        $this->electronicSignService                       = 'App\EofficeApp\ElectronicSign\Services\ElectronicSignService';
        $this->flowAgencyRepository                        = 'App\EofficeApp\Flow\Repositories\FlowAgencyRepository';
        $this->flowAgencyDetailRepository                  = 'App\EofficeApp\Flow\Repositories\FlowAgencyDetailRepository';
        $this->flowCopyRepository                          = 'App\EofficeApp\Flow\Repositories\FlowCopyRepository';
        $this->flowCountersignRepository                   = 'App\EofficeApp\Flow\Repositories\FlowCountersignRepository';
        $this->flowFormEditionRepository                   = 'App\EofficeApp\Flow\Repositories\FlowFormEditionRepository';
        $this->flowFormTypeRepository                      = 'App\EofficeApp\Flow\Repositories\FlowFormTypeRepository';
        $this->flowFormTemplateRepository                  = 'App\EofficeApp\Flow\Repositories\FlowFormTemplateRepository';
        $this->flowFormTemplateRuleRepository              = 'App\EofficeApp\Flow\Repositories\FlowFormTemplateRuleRepository';
        $this->flowFormTemplateRuleUserRepository          = 'App\EofficeApp\Flow\Repositories\FlowFormTemplateRuleUserRepository';
        $this->flowFormTemplateRuleRoleRepository          = 'App\EofficeApp\Flow\Repositories\FlowFormTemplateRuleRoleRepository';
        $this->flowFormTemplateRuleDepartmentRepository    = 'App\EofficeApp\Flow\Repositories\FlowFormTemplateRuleDepartmentRepository';
        $this->flowChildFormTypeRepository                 = 'App\EofficeApp\Flow\Repositories\FlowChildFormTypeRepository';
        $this->flowChildFormControlStructureRepository     = 'App\EofficeApp\Flow\Repositories\FlowChildFormControlStructureRepository';
        $this->flowFormControlSortRepository               = 'App\EofficeApp\Flow\Repositories\FlowFormControlSortRepository';
        $this->flowFormControlGroupRepository              = 'App\EofficeApp\Flow\Repositories\FlowFormControlGroupRepository';
        $this->flowFormControlStructureRepository          = 'App\EofficeApp\Flow\Repositories\FlowFormControlStructureRepository';
        $this->flowOthersRepository                        = 'App\EofficeApp\Flow\Repositories\FlowOthersRepository';
        $this->flowProcessRepository                       = 'App\EofficeApp\Flow\Repositories\FlowProcessRepository';
        $this->flowProcessControlOperationRepository       = 'App\EofficeApp\Flow\Repositories\FlowProcessControlOperationRepository';
        $this->flowProcessControlOperationDetailRepository = 'App\EofficeApp\Flow\Repositories\FlowProcessControlOperationDetailRepository';
        $this->flowReportRepository                        = 'App\EofficeApp\Flow\Repositories\FlowReportRepository';
        $this->flowRunRepository                           = 'App\EofficeApp\Flow\Repositories\FlowRunRepository';
        $this->flowFavoriteRepository                      = 'App\EofficeApp\Flow\Repositories\FlowFavoriteRepository';
        $this->flowRunFeedbackRepository                   = 'App\EofficeApp\Flow\Repositories\FlowRunFeedbackRepository';
        $this->flowRunProcessRepository                    = 'App\EofficeApp\Flow\Repositories\FlowRunProcessRepository';
        $this->flowRunProcessAgencyDetailRepository        = 'App\EofficeApp\Flow\Repositories\FlowRunProcessAgencyDetailRepository';
        $this->flowService                                 = 'App\EofficeApp\Flow\Services\FlowService';
        $this->flowRunStepRepository                       = 'App\EofficeApp\Flow\Repositories\FlowRunStepRepository';
        $this->flowSortRepository                          = 'App\EofficeApp\Flow\Repositories\FlowSortRepository';
        $this->flowTermRepository                          = 'App\EofficeApp\Flow\Repositories\FlowTermRepository';
        $this->flowTypeRepository                          = 'App\EofficeApp\Flow\Repositories\FlowTypeRepository';
        $this->flowRequiredForFreeFlowRepository           = 'App\EofficeApp\Flow\Repositories\FlowRequiredForFreeFlowRepository';
        $this->flowRunService                              = 'App\EofficeApp\Flow\Services\FlowRunService';
        $this->flowFormService                             = 'App\EofficeApp\Flow\Services\FlowFormService';
        $this->flowTypeManageUserRepository                = 'App\EofficeApp\Flow\Repositories\FlowTypeManageUserRepository';
        $this->flowTypeManageRoleRepository                = 'App\EofficeApp\Flow\Repositories\FlowTypeManageRoleRepository';
        $this->flowTypeCreateUserRepository                = 'App\EofficeApp\Flow\Repositories\FlowTypeCreateUserRepository';
        $this->flowTypeCreateRoleRepository                = 'App\EofficeApp\Flow\Repositories\FlowTypeCreateRoleRepository';
        $this->flowTypeManageRuleRepository                = 'App\EofficeApp\Flow\Repositories\FlowTypeManageRuleRepository';
        $this->flowTypeCreateDepartmentRepository          = 'App\EofficeApp\Flow\Repositories\FlowTypeCreateDepartmentRepository';
        $this->flowTypeManageScopeUserRepository           = 'App\EofficeApp\Flow\Repositories\FlowTypeManageScopeUserRepository';
        $this->flowTypeManageScopeDeptRepository           = 'App\EofficeApp\Flow\Repositories\FlowTypeManageScopeDeptRepository';
        $this->flowProcessUserRepository                   = 'App\EofficeApp\Flow\Repositories\FlowProcessUserRepository';
        $this->flowProcessRoleRepository                   = 'App\EofficeApp\Flow\Repositories\FlowProcessRoleRepository';
        $this->flowProcessDepartmentRepository             = 'App\EofficeApp\Flow\Repositories\FlowProcessDepartmentRepository';
        $this->flowProcessCopyUserRepository               = 'App\EofficeApp\Flow\Repositories\FlowProcessCopyUserRepository';
        $this->flowProcessCopyRoleRepository               = 'App\EofficeApp\Flow\Repositories\FlowProcessCopyRoleRepository';
        $this->flowProcessCopyDepartmentRepository         = 'App\EofficeApp\Flow\Repositories\FlowProcessCopyDepartmentRepository';
        $this->flowProcessDefaultUserRepository            = 'App\EofficeApp\Flow\Repositories\FlowProcessDefaultUserRepository';
        $this->flowSunWorkflowRepository                   = 'App\EofficeApp\Flow\Repositories\FlowSunWorkflowRepository';
        $this->flowFormSortRepository                      = 'App\EofficeApp\Flow\Repositories\FlowFormSortRepository';
        $this->flowFormSortUserRepository                  = 'App\EofficeApp\Flow\Repositories\FlowFormSortUserRepository';
        $this->flowFormSortRoleRepository                  = 'App\EofficeApp\Flow\Repositories\FlowFormSortRoleRepository';
        $this->flowFormSortDepartmentRepository            = 'App\EofficeApp\Flow\Repositories\FlowFormSortDepartmentRepository';
        $this->flowSortUserRepository                      = 'App\EofficeApp\Flow\Repositories\FlowSortUserRepository';
        $this->flowSortRoleRepository                      = 'App\EofficeApp\Flow\Repositories\FlowSortRoleRepository';
        $this->flowSortDepartmentRepository                = 'App\EofficeApp\Flow\Repositories\FlowSortDepartmentRepository';
        $this->flowOutsendFieldsRepository                 = 'App\EofficeApp\Flow\Repositories\FlowOutsendFieldsRepository';
        $this->flowOutsendDependentFieldsRepository        = 'App\EofficeApp\Flow\Repositories\FlowOutsendDependentFieldsRepository';
        $this->flowSettingService                          = 'App\EofficeApp\Flow\Services\FlowSettingService';
        $this->flowLogService                              = 'App\EofficeApp\Flow\Services\FlowLogService';
        $this->flowParseService                            = 'App\EofficeApp\Flow\Services\FlowParseService';
        $this->flowDataValidateRepository                  = 'App\EofficeApp\Flow\Repositories\FlowDataValidateRepository';
        $this->flowFormDataTemplateRepository              = 'App\EofficeApp\Flow\Repositories\FlowFormDataTemplateRepository';
        $this->flowTrashService                            = 'App\EofficeApp\Flow\Services\FlowTrashService';
        $this->flowOutsendService                          = 'App\EofficeApp\Flow\Services\FlowOutsendService';
        $this->flowPermissionService                       = 'App\EofficeApp\Flow\Services\FlowPermissionService';
        $this->formModelingService                         = 'App\EofficeApp\FormModeling\Services\FormModelingService';
        $this->flowOverTimeRemindRepository                = 'App\EofficeApp\Flow\Repositories\FlowOverTimeRemindRepository';
        $this->flowRunOverTimeRepository                   = 'App\EofficeApp\Flow\Repositories\FlowRunOverTimeRepository';
        $this->flowProcessFreeRepository                   = 'App\EofficeApp\Flow\Repositories\FlowProcessFreeRepository';
        $this->flowProcessFreePresetRepository             = 'App\EofficeApp\Flow\Repositories\FlowProcessFreePresetRepository';
        $this->flowProcessFreeRequiredRepository           = 'App\EofficeApp\Flow\Repositories\FlowProcessFreeRequiredRepository';
        $this->flowProcessFreeStepRepository               = 'App\EofficeApp\Flow\Repositories\FlowProcessFreeStepRepository';
        $this->flowOutsendRepository                       = 'App\EofficeApp\Flow\Repositories\FlowOutsendRepository';
        $this->flowScheduleRepository                      = 'App\EofficeApp\Flow\Repositories\FlowScheduleRepository';
        $this->flowChildFormControlDropRepository          = 'App\EofficeApp\Flow\Repositories\FlowChildFormControlDropRepository';
        $this->flowInstancysRepository                     = 'App\EofficeApp\Flow\Repositories\FlowInstancysRepository';
        $this->flowSettingsRepository                      = 'App\EofficeApp\Flow\Repositories\FlowSettingsRepository';
        $this->integrationCenterService                    = 'App\EofficeApp\IntegrationCenter\Services\IntegrationCenterService';
        $this->ipRulesService                              = 'App\EofficeApp\IpRules\Services\IpRulesService';
        $this->langService                                 = 'App\EofficeApp\Lang\Services\LangService';
        $this->projectManagerRepository                    = 'App\EofficeApp\Project\Repositories\ProjectManagerRepository';
        $this->projectService                              = 'App\EofficeApp\Project\NewServices\ProjectService';
        $this->qiyuesuoService                             = 'App\EofficeApp\ElectronicSign\Services\QiyuesuoService';
        $this->roleRepository                              = 'App\EofficeApp\Role\Repositories\RoleRepository';
        $this->systemComboboxService                       = 'App\EofficeApp\System\Combobox\Services\SystemComboboxService';
        $this->systemComboboxFieldRepository               = 'App\EofficeApp\System\Combobox\Repositories\SystemComboboxFieldRepository';
        $this->systemSmsService                            = 'App\EofficeApp\SystemSms\Services\SystemSmsService';
        $this->userMenuService                             = 'App\EofficeApp\Menu\Services\UserMenuService';
        $this->userService                                 = 'App\EofficeApp\User\Services\UserService';
        $this->userRepository                              = 'App\EofficeApp\User\Repositories\UserRepository';
        $this->userSystemInfoRepository                    = 'App\EofficeApp\User\Repositories\UserSystemInfoRepository';
        $this->userSuperiorRepository                      = 'App\EofficeApp\Role\Repositories\UserSuperiorRepository';
        $this->versionRepository                           = 'App\EofficeApp\Empower\Repositories\VersionRepository';
        $this->webmailEmailboxService                      = 'App\EofficeApp\System\SystemMailbox\Services\WebmailEmailboxService';
        $this->flowWorkHandOverService                     = 'App\EofficeApp\Flow\Services\FlowWorkHandOverService';
        $this->flowRunPrintLogRepository                   = 'App\EofficeApp\Flow\Repositories\FlowRunPrintLogRepository';
		$this->invoiceService                              = 'App\EofficeApp\Invoice\Services\InvoiceService';
        $this->invoiceManageService                        = 'App\EofficeApp\Invoice\Services\InvoiceManageService';
        $this->signatureConfigService                      = 'App\EofficeApp\HtmlSignature\Services\SignatureConfigService';
        $this->todoPushService                             = 'App\EofficeApp\IntegrationCenter\Services\TodoPushService';
        $this->flowRemindService                           = 'App\EofficeApp\Flow\Services\FlowRemindService';
    }

    /**
     * 获取用户详细信息并存入缓存
     *
     * @param [string] $hostUser
     *
     * @author zyx
     * @since 20201029
     *
     * @return array
     */
    public function getHostUserInfo($hostUser) {
        if (Cache::has('host_user_info_' . $hostUser)) {
            return Cache::get('host_user_info_' . $hostUser);
        }

        $hotsUserInfos = app($this->userService)->getUserAllData($hostUser);
        $roleId = [];
        $roleName = [];
        if (isset($hotsUserInfos['user_has_many_role']) && !empty($hotsUserInfos['user_has_many_role'])) {
            foreach ($hotsUserInfos['user_has_many_role'] as $key => $value) {
                $roleId[] = $value['role_id'];
                $roleName[] = $value['has_one_role']['role_name'];
            }
        }
        $menus = app($this->userMenuService)->getUserMenus($hostUser);
        $hostUserInfo = [
            'user_id' => $hostUser,
            'user_name' => isset($hotsUserInfos['user_name']) ? $hotsUserInfos['user_name'] : '',
            'user_accounts' => isset($hotsUserInfos['user_accounts']) ? $hotsUserInfos['user_accounts'] : '',
            'dept_name' => isset($hotsUserInfos['user_has_one_system_info']['user_system_info_belongs_to_department']['dept_name']) ? $hotsUserInfos['user_has_one_system_info']['user_system_info_belongs_to_department']['dept_name'] : '',
            'dept_id' => isset($hotsUserInfos['user_has_one_system_info']['user_system_info_belongs_to_department']['dept_id']) ? $hotsUserInfos['user_has_one_system_info']['user_system_info_belongs_to_department']['dept_id'] : '',
            'role_name' => $roleName,
            'role_id' => $roleId,
            'user_job_number' => isset($hotsUserInfos['user_job_number']) ? $hotsUserInfos['user_job_number'] : '',
            'user_position_name' => isset($hotsUserInfos['user_position_name']) ? $hotsUserInfos['user_position_name'] : '',
            'phone_number' => isset($hotsUserInfos['user_has_one_info']['phone_number']) ? $hotsUserInfos['user_has_one_info']['phone_number'] : '',
            'menus' => $menus,

        ];
        Cache::put('host_user_info_' . $hostUser, $hostUserInfo, 60);

        return $hostUserInfo;
    }
}
