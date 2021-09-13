<?php
namespace App\EofficeApp\LogCenter\Parser;
class DocumentContentParser implements ParserInterface
{
    public function parseContentData(&$data)
    {
        try{
            $logContent = json_decode($data['log_content'],true);
            if($data['log_operate'] == 'share'){
                $content = trans('document.shared_member').'：';
                if($logContent['share_all']){
                    $content .= trans('document.all_member').'；';
                }else{
                    if(!empty($logContent['share_user'])){
                        $content .= trans('document.user').'：';
                        $paramUser['search']['user_id'] = [$logContent['share_user'],'in'];
                        $responseUser = app('App\EofficeApp\User\Services\UserService')->userSystemList($paramUser);
                        if($responseUser['list']){
                            foreach ($responseUser['list'] as $user) {
                                if($user){
                                    $content .= $user['user_name'].'；';
                                }
                            }
                        }
                    }
                    if(!empty($logContent['share_dept'])){
                        $content .= trans('document.department').'；';
                        $paramDept['search'] = json_encode(['dept_id' => [[$logContent['share_dept']],'in']]);
                        $responseDept = app('App\EofficeApp\System\Department\Services\DepartmentService')->listDept($paramDept);
                        if($responseDept['list']){
                            foreach ($responseDept['list'] as $dept) {
                                if($dept){
                                    $content .= $dept['dept_name'].'；';
                                }
                            }
                        }
                    }
                    if(!empty($logContent['share_role'])){
                        $content .= trans('document.role').'；';
                        $paramRole['search'] = json_encode(['role_id' => [[$logContent['share_role']],'in']]);
                        $responseRole = app('App\EofficeApp\Role\Services\RoleService')->getRoleList($paramRole);
                        if($responseRole['list']){
                            foreach ($responseRole['list'] as $role) {
                                if($role){
                                    $content .= $role['role_name'].'；';
                                }
                            }
                        }
                    }
                }
                if(!$logContent['share_status']){
                    $content .= trans('document.the_time_limit_is_permanent').'；';
                }else if($logContent['share_end_time']){
                    $content .= trans('document.the_time_limit_valid_until').$logContent['share_end_time'].'；';
                }
            }else if($data['log_operate'] == 'move'){
                $content = trans('document.source_folder').'：';
                if (!empty($logContent['from_id'])) {
                    $fromFolder = app('App\EofficeApp\Document\Repositories\DocumentFolderRepository')->getDetail($logContent['from_id']);
                    $content .= $fromFolder->folder_name.'；' ?? trans('document.folder_not_exists_or_deleted').'；';
                } else {
                    $content .= trans('document.folder_not_exists_or_deleted').'；';
                }
                $content .= trans('document.destination_folder').'：';
                if (!empty($logContent['to_id'])) {
                    $toFolder = app('App\EofficeApp\Document\Repositories\DocumentFolderRepository')->getDetail($logContent['to_id']);
                    $content .= $toFolder->folder_name.'；' ?? trans('document.folder_not_exists_or_deleted').'；';
                } else {
                    $content .= trans('document.folder_not_exists_or_deleted').'；';
                }
            }else if($data['log_operate'] == 'download'){
                $content = trans('document.attachment_name').'：';
                if (!empty($logContent['attachment_name'])) {
                    $content .= $logContent['attachment_name'].'；';
                }
            }
            $data['log_content'] = $content;
        }catch (\Exception $e){
            \Log::info($e->getMessage());
        }

    }


}
