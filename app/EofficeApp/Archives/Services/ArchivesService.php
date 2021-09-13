<?php

namespace App\EofficeApp\Archives\Services;

use App\EofficeApp\Archives\Services\Traits\ArchivesFlowOutTrait;
use App\EofficeApp\Base\BaseService;
use DB;
use Illuminate\Support\Arr;
use Schema;

/**
 * 档案管理Service类:提供档案管理模块的相关服务
 *
 * @author qishaobo
 *
 * @since  2015-10-21 创建
 */
class ArchivesService extends BaseService
{
    use ArchivesFlowOutTrait;
    public function __construct() {
        parent::__construct();
        $this->userRepository = 'App\EofficeApp\User\Repositories\UserRepository';
        $this->attachmentService = 'App\EofficeApp\Attachment\Services\AttachmentService';
        $this->archivesLogRepository = 'App\EofficeApp\Archives\Repositories\ArchivesLogRepository';
    	$this->archivesFileRepository = 'App\EofficeApp\Archives\Repositories\ArchivesFileRepository';
        $this->archivesBorrowRepository = 'App\EofficeApp\Archives\Repositories\ArchivesBorrowRepository';
    	$this->archivesVolumeRepository = 'App\EofficeApp\Archives\Repositories\ArchivesVolumeRepository';
        $this->archivesDestroyRepository = 'App\EofficeApp\Archives\Repositories\ArchivesDestroyRepository';
        $this->archivesLibraryRepository = 'App\EofficeApp\Archives\Repositories\ArchivesLibraryRepository';
        $this->archivesAppraisalRepository = 'App\EofficeApp\Archives\Repositories\ArchivesAppraisalRepository';
        $this->archivesLibraryPermissionRepository = 'App\EofficeApp\Archives\Repositories\ArchivesLibraryPermissionRepository';
        $this->systemComboboxService = 'App\EofficeApp\System\Combobox\Services\SystemComboboxService';
        $this->formModelingService = 'App\EofficeApp\FormModeling\Services\FormModelingService';

    }

    /**
     * 获取卷库列表数据
     *
     * @param  array $param  查询条件
     *
     * @return array 查询结果
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    function getLibraryList($param = [])
    {
        $param = $this->parseParams($param);
        return $this->response(app($this->archivesLibraryRepository), 'getLibraryTotal', 'getLibraryList', $param);
    }

    /**
     * 新建卷库
     *
     * @param  array      $data  新建数据
     *
     * @return int|array         新建数据id|错误码
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    function createLibrary($data,$own = null)
    {
        if (isset($data['data']) && !empty($data['data']) && isset($data['tableKey']) && isset($data['current_user_id'])) {
            $currentUserId = $data['current_user_id'];
            $data = $data['data'];
            if (isset($data['permission'])) {
                $data['permission'] = explode(',', $data['permission']);
            }
            if(isset($data['library_name']) && $data['library_name'] == ''){
                return ['code' => ['error_library','archives']];
            }
            if(isset($data['library_number']) && $data['library_number'] == ''){
                return ['code' => ['error_library_number','archives']];
            }
            empty($data['library_creator']) && $data['library_creator'] = $currentUserId;
        }
        empty($data['library_creator']) && $data['library_creator'] = $own['user_id'];
        $primaryData = $subData = $department_ids = [];

        if (isset($data['permission'])) {
            $department_ids = $data['permission'];
            $data['permission'] = is_array($data['permission']) ? implode(',', $data['permission']) : $data['permission'];
        }

        //if (isset($data['attachments'])) {
            //$attachments = $data['attachments'];
            //unset($data['attachments']);
        //}

        foreach ($data as $k => $v) {
            //if (strpos($k, "sub_") !== false) {
                //$subData[$k] = $v;
            //} else {
                $primaryData[$k] = $v;
            //}
        }

        //if ($libraryObj = app($this->archivesLibraryRepository)->insertData($primaryData)) {

        $result = app($this->formModelingService)->addCustomData($primaryData,'archives_library');
        if($result){
        	if(is_array($result)) return $result;

        	$subData['archives_library_id'] = $result;

            //$subData['archives_library_id'] = $libraryObj->getKey();
            //if (!empty($subData)) {
                //app($this->archivesLibraryRepository)->insertSubLibrary($subData);
            //}

            if (!empty($department_ids)) {
                foreach ($department_ids as $department_id) {
                    $permission[] = [
                        'library_id'   => $subData['archives_library_id'],
                        'department_id'      => $department_id
                    ];
                }
                app($this->archivesLibraryPermissionRepository)->insertMultipleData($permission);
            }

            $this->saveArchivesLog('archives_library', $subData['archives_library_id'], 'new', $data['library_creator']);

            //if (!empty($attachments)) {
                //app($this->attachmentService)->attachmentRelation("archives_library", $subData['archives_library_id'], array_filter($attachments));
            //}

            return $subData['archives_library_id'];
        }

        return ['code' => ['0x000003','common']];
    }

    /**
     * 删除卷库
     *
     * @param  int|string  $libraryId  卷库id,多个用逗号隔开
     *
     * @return bool|array              操作成功|错误码
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    function deleteLibrary($libraryId, $userId)
    {
        $libraryIds = array_filter(explode(',', $libraryId));

        if (app($this->archivesLibraryRepository)->deleteByWhere(['library_id' => [$libraryIds,'in']])) {
            //app($this->archivesLibraryRepository)->deleteSubLibrary($libraryIds);
            app($this->archivesLibraryPermissionRepository)->deleteByWhere(['library_id' => [$libraryIds,'in']]);
            foreach ($libraryIds as $id) {
                $this->saveArchivesLog('archives_library', $id, 'delete', $userId);
            }

            return true;
        }

        return ['code' => ['0x000003','common']];
    }

    /**
     * 编辑卷库
     *
     * @param  array        $data       编辑数据
     * @param  int          $libraryId  卷库id
     *
     * @return bool|array               操作成功|错误码
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    function updateLibrary($data, $libraryId, $userId)
    {
        $primaryData = $subData = $department_ids = [];

        if (isset($data['permission'])) {
            is_string($data['permission']) && $data['permission'] = explode(',', $data['permission']);
            $department_ids = array_filter($data['permission']);
            $data['permission'] = is_array($data['permission']) ? implode(',', $data['permission']) : $data['permission'];
        }
//        if(array_key_exists('library_creator', $data) && empty($data['library_creator'])){
//            $data['library_creator'] = $userId;
//        }
		/*
        if (isset($data['attachments'])) {
            $attachments = $data['attachments'];
            unset($data['attachments']);
        }
        */

        foreach ($data as $k => $v) {
            //if (strpos($k, "sub_") !== false) {
                //$subData[$k] = $v;
            //} else {
                $primaryData[$k] = $v;
            //}
        }

        //if (app($this->archivesLibraryRepository)->updateData($primaryData, ['library_id' => $libraryId])) {

        $result = app($this->formModelingService)->editCustomData($primaryData,'archives_library',$libraryId);
        if($result){
        	if(is_array($result)) return $result;
			/*
            if (!empty($subData)) {
                if (app($this->archivesLibraryRepository)->getSubLibraryDetail(['archives_library_id' => [$libraryId]])) {
                    app($this->archivesLibraryRepository)->updateSubLibrary($subData, ['archives_library_id' => $libraryId]);
                } else {
                    $subData['archives_library_id'] = $libraryId;
                    app($this->archivesLibraryRepository)->insertSubLibrary($subData);
                }
            }
			*/
            app($this->archivesLibraryPermissionRepository)->deleteByWhere(['library_id' => [$libraryId]]);
            if (!empty($department_ids)) {
                foreach ($department_ids as $department_id) {
                    $permission[] = [
                        'library_id'    => $libraryId,
                        'department_id' => $department_id
                    ];
                }
                app($this->archivesLibraryPermissionRepository)->insertMultipleData($permission);
            }

            //app($this->attachmentService)->attachmentRelation("archives_library", $libraryId, array_filter($attachments));

            $this->saveArchivesLog('archives_library', $libraryId, 'edit', $userId);
            return true;
        }

        return ['code' => ['0x000021','common']];
    }

    /**
     * 获取卷库详情
     *
     * @param  int    $libraryId  卷库id
     *
     * @return array              卷库详情数据|错误码
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    function getLibraryDetail($libraryId,$userId = null)
    {
        if ($primaryData = (array) app($this->formModelingService)->getCustomDataDetail('archives_library', $libraryId)) {
            $primaryData['library_creator_name'] = app($this->userRepository)->getUserName($primaryData['library_creator']);
//            $subObj = app($this->archivesLibraryRepository)->getSubLibrary($libraryId);
//            $subData = $subObj ? $subObj->toArray() : [];
            $primaryData['permission'] = empty($primaryData['permission']) ? [] : explode(',', $primaryData['permission']);
            $this->saveArchivesLog('archives_library', $libraryId, 'read', $userId);
//            return $primaryData + $subData;
            return $primaryData;
        }

        return ['code' => ['0x000003','common']];
    }

    /**
     * 获取卷库日志列表
     *
     * @param  array  $param      查询条件
     * @param  int    $libraryId  卷库id
     *
     * @return array  查询结果或错误码
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    function getLibraryLog($param, $libraryId)
    {
        $param = $this->parseParams($param);
        $param['search']['log_table']   = ['archives_library'];
        $param['search']['log_data_id'] = [$libraryId];
        return $this->getArchivesLog($param);
    }

    /**
     * 获取案卷列表数据
     *
     * @param  array  $param 查询条件
     *
     * @return array  查询结果或错误码
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    function getVolumeList($param = [],$own = null)
    {
        $param = $this->parseParams($param);
        if(isset($param['sign']) && $param['sign'] == 'volume'){
            $param['search']['can_borrow'] = [$own['dept_id']];
            unset($param['sign']);
        }
        $result = $this->response(app($this->archivesVolumeRepository), 'getVolumeTotal', 'getVolumeList', $param);
        if($result['list'] && is_array($result['list'])){
            foreach ($result['list'] as $key => $vo){

                isset($vo['volume_hold_time']) && $result['list'][$key]['volume_hold_time'] = app($this->systemComboboxService)->getComboboxFieldsNameByIdentify('ARCHIVES_TIME',$vo['volume_hold_time']);
            }
        }
        return $result;
//        return $this->response(app($this->archivesVolumeRepository), 'getVolumeTotal', 'getVolumeList', $param);
    }

    /**
     * 新建案卷
     *
     * @param  array      $data  新建数据
     *
     * @return int|array         新建数据id|错误码
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    function createVolume($data ,$own = null)
    {
        if (isset($data['data']) && !empty($data['data']) && isset($data['tableKey']) && isset($data['current_user_id'])) {
            $currentUserId = $data['current_user_id'];
            $data = $data['data'];
            $data['is_approval'] = (isset($data['is_approval']) && $data['is_approval']) ? $data['is_approval'] : 0;
            if(isset($data['volume_name']) && $data['volume_name'] == ''){
                return ['code' => ['error_volume','archives']];
            }
            if(isset($data['volume_number']) && $data['volume_number'] == ''){
                return ['code' => ['error_volume_number','archives']];
            }
            if(isset($data['volume_hold_time']) && $data['volume_hold_time'] == ''){
                return ['code' => ['error_hold_time','archives']];
            }
            empty($data['volume_creator']) && $data['volume_creator'] = $currentUserId;
        }
        empty($data['volume_creator'])  && $data['volume_creator'] = $own['user_id'];
        $primaryData = $subData = [];

        /*
        if (isset($data['attachments'])) {
            $attachments = $data['attachments'];
            unset($data['attachments']);
        }
        */


        foreach ($data as $k => $v) {
            //if (strpos($k, "sub_") !== false) {
                //$subData[$k] = $v;
            //} else {
                $primaryData[$k] = $v;
            //}
        }

        //if ($volumeObj = app($this->archivesVolumeRepository)->insertData($primaryData)) {
			//$id = $volumeObj->getKey();

        $result = app($this->formModelingService)->addCustomData($primaryData,'archives_volume');
        if($result){
        	if(is_array($result)) return $result;
        	$id = $result;
        	/*
            if (!empty($subData)) {
                $subData['archives_volume_id'] = $id;
                app($this->archivesVolumeRepository)->insertSubVolume($subData);
            }
			*/

            //if (!empty($attachments)) {
                //app($this->attachmentService)->attachmentRelation("archives_volume", $id, array_filter($attachments));
            //}

            $this->saveArchivesLog('archives_volume', $id, 'new', $data['volume_creator']);
            return $id;
        }

        return ['code' => ['0x000003','common']];
    }

    /**
     * 删除案卷
     *
     * @param  int|string  $volumeId  案卷id,多个用逗号分隔
     * @param  string      $reason    删除原因
     *
     * @return bool|array             操作成功|错误码
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    function deleteVolume($volumeId, $reason = '', $userId)
    {
        $volume_Ids = array_filter(explode(',', $volumeId));
        //删除时过滤掉已经封卷的案卷
        $filterSearch = ['search'=>['volume_id'=>[$volume_Ids,'in']]];
        $filterData = app($this->archivesVolumeRepository)->getVolumeList($filterSearch);
        $sealupNames = [];
        $volumeIds = [];
        if($filterData){
          foreach ($filterData as $vo){
              if($vo['volume_status'] == 2){
                  $sealupNames[] = $vo['volume_name'];
              }else{
                  $volumeIds[] = $vo['volume_id'];
              }

          }
        }
        if($sealupNames){
            $error['code'] = ['0x000003','common'];
            $error['dynamic'] = implode(',',$sealupNames).trans('archives.error_seal_up_volume');
            return $error;

        }
        if ($volumeIds) {
            $this->deleteVolumes($volumeIds, $userId, $reason);
            // 关联删除文件 暂时注释，等后面调整
//            $fileIds = app($this->archivesFileRepository)->getRelationFileIds($volumeIds);
//            if ($fileIds) {
//                $this->deleteFiles($fileIds, $userId, $reason);
//            }
            return true;
        }
        return ['code' => ['0x000003', 'common']];
    }

    private function deleteVolumes($volumeIds, $userId, $reason) {
        if (app($this->archivesVolumeRepository)->deleteById($volumeIds)) {

            app($this->archivesVolumeRepository)->updateSubVolume(['deleted_at' => date("Y-m-d H:i:s")], $volumeIds);

            $temp = [
                'destroy_type' => 'volume',
                'destroy_content' => $reason,
                'destroy_date' => date("Y-m-d H:i:s"),
                'user_id' => $userId
            ];

            $data = [];
            foreach ($volumeIds as $id) {
                $temp['destroy_data_id'] = $id;
                $data[] = $temp;
            }
            app($this->archivesDestroyRepository)->insertMultipleData($data);
            $this->saveArchivesLog('archives_volume', $volumeIds, 'destroy', $userId);
        }
    }

    /**
     * 编辑案卷
     *
     * @param  array        $data      编辑数据
     * @param  int          $volumeId  案卷id
     *
     * @return bool|array   操作成功|错误码
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    function updateVolume($data, $volumeId, $userId)
    {


        //删除时过滤掉已经封卷的案卷
        $filterData = app($this->archivesVolumeRepository)->getVolumeDetail($volumeId);

        if($filterData){
            if($filterData['volume_status'] == 2){
                return ['code' => ['error_edit','archives']];
            }
        }
        $primaryData = $subData = [];
		/*
        if (isset($data['attachments'])) {
            $attachments = $data['attachments'];
            unset($data['attachments']);
        }
		*/
        foreach ($data as $k => $v) {
            //if (strpos($k, "sub_") !== false) {
                //$subData[$k] = $v;
            //} else {
                $primaryData[$k] = $v;
            //}
        }
        //if (app($this->archivesVolumeRepository)->updateData($primaryData, ['volume_id' => $volumeId])) {
        $result = app($this->formModelingService)->editCustomData($primaryData,'archives_volume',$volumeId);
        if($result){
        	if(is_array($result)) return $result;
        	/*
            if (!empty($subData)) {
                if (app($this->archivesVolumeRepository)->getSubVolumeDetail(['archives_volume_id' => [$volumeId]])) {
                   app($this->archivesVolumeRepository)->updateSubVolume($subData, ['archives_volume_id' => $volumeId]);
                } else {
                    $subData['archives_volume_id'] = $volumeId;
                    app($this->archivesVolumeRepository)->insertSubVolume($subData);
                }
            }
			*/
            //app($this->attachmentService)->attachmentRelation("archives_volume", $volumeId, array_filter($attachments));
            $this->saveArchivesLog('archives_volume', $volumeId, 'edit', $userId);
            return true;
        }

        return ['code' => ['0x000003','common']];
    }

    /**
     * 案卷封卷拆卷
     *
     * @param  int        $volumeId  案卷id
     * @param  int        $type  1拆卷,2封卷
     *
     * @return bool|array 操作成功|错误码
     *
     * @author qishaobo
     *
     * @since  2015-12-10
     */
    function sealUpOrSealOffVolume($volumeId, $type, $userId)
    {
        $volumeIds = array_filter(explode(',', $volumeId));
        $data = ['volume_status' => $type];
        if (app($this->archivesVolumeRepository)->updateData($data, ['volume_id' => [$volumeIds, 'in']])) {
            $type = $type == 1 ? 'open' : 'close';
            $this->saveArchivesLog('archives_volume', $volumeId, $type, $userId);
            return true;
        }

        return ['code' => ['0x000003','common']];
    }

    /**
     * 获取案卷详情
     *
     * @param  int    $volumeId  案卷id
     *
     * @return array  案卷详情数据|错误码
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    function getVolumeDetail($volumeId, $userId)
    {
        $result = (array) app($this->formModelingService)->getCustomDataDetail('archives_volume', $volumeId);
        if (!empty($result)) {
            $this->saveArchivesLog('archives_volume', $volumeId, 'read', $userId);
            return $result;
        }
        /*if ($primaryObj = app($this->archivesVolumeRepository)->getDetail($volumeId)) {
            $primaryObj = $primaryObj->load('volumeCreatorHasOneUser');
            $primaryObj = $primaryObj->load('volumeHasOneLibrary');
            $primaryData = $primaryObj->toArray();

            $this->saveArchivesLog('archives_volume', $volumeId, 'read', $userId);

            $primaryData['attachments'] = app($this->attachmentService)->getAttachmentIdsByEntityId(['entity_table'=>'archives_volume', 'entity_id'=>$volumeId]);

            $primaryData['volume_creator_name'] = $primaryData['volume_creator_has_one_user']['user_name'];
            unset($primaryData['volume_creator_has_one_user']);
            $subObj = app($this->archivesVolumeRepository)->getSubVolume($volumeId);
            $subData = $subObj ? $subObj->toArray() : [];
            return $primaryData + $subData;
        }*/

        return ['code' => ['0x023001','archives']];
    }

    /**
     * 获取案卷详情权限判断
     *
     * @param  int    $volumeId  案卷id
     *
     * @return array  案卷详情数据|错误码
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    function getVolumePrivate($volumeId){
        $volumeDetail = app($this->archivesVolumeRepository)->getDetail($volumeId);
        if($volumeDetail && $volumeDetail['deleted_at'] != ''){
            return false;
        }
        return true;


    }

    /**
     * 获取案卷日志列表
     *
     * @param  array  $param      查询条件
     * @param  int    $volumeId   案卷id
     *
     * @return array  查询结果或错误码
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    function getVolumeLog($param, $volumeId)
    {
        $param = $this->parseParams($param);
        $param['search']['log_table'] = ['archives_volume'];
        $param['search']['log_data_id'] = [$volumeId];
        return $this->getArchivesLog($param);
    }

    /**
     * 获取案卷包含的文件
     *
     * @param  int  $volumeId  案卷id
     *
     * @return array           案卷包含的文件|错误码
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    function getVolumeFiles($volumeId)
    {
        if ($primaryObj = app($this->archivesVolumeRepository)->getVolumeDetail($volumeId)) {
            $primaryData = $primaryObj->toArray()['volume_files'];
            return $primaryData;
        }

        return ['code' => ['0x023001','archives']];
    }

    /**
     * 获取档案文件列表数据
     *
     * @param  array  $param 查询条件
     *
     * @return array  查询结果或错误码
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    function getFileList($param = [],$own = null)
    {
        $param = $this->parseParams($param);
        if(isset($param['sign']) && $param['sign'] == 'file'){
            $param['search']['can_borrow'] = [$own['dept_id']];
            unset($param['sign']);
        }
        $result = $this->response(app($this->archivesFileRepository), 'getFileTotal', 'getFileList', $param);

        if($result['list'] && is_array($result['list'])){
            foreach ($result['list'] as $key => $vo){
                isset($vo['file_hold_time']) && $result['list'][$key]['file_hold_time'] = app($this->systemComboboxService)->getComboboxFieldsNameByIdentify('ARCHIVES_TIME',$vo['file_hold_time']);
            }
        }
       return $result;
    }

    /**
     * 新建档案文件
     *
     * @param  array      $data  新建数据
     *
     * @return int|array  新建数据id|错误码
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     *
     */
    function createFile($data,$own = null)
    {
        if (isset($data['data']) && !empty($data['data']) && isset($data['tableKey']) && isset($data['current_user_id'])) {
            $currentUserId = $data['current_user_id'];
            $data = $data['data'];
            $data['is_approval'] = (isset($data['is_approval']) && $data['is_approval']) ? $data['is_approval']:0;
            if(isset($data['file_name']) && $data['file_name'] == ''){
                return ['code' => ['error_file','archives']];
            }
            if(isset($data['file_number']) && $data['file_number'] == ''){
                return ['code' => ['error_file_number','archives']];
            }
            empty($data['file_creator']) && $data['file_creator'] = $currentUserId;
        }
        $primaryData = $subData = [];
        empty($data['file_creator']) && $data['file_creator'] = $own['user_id'];
		/*
        if (isset($data['attachments'])) {
            $attachments = $data['attachments'];
            unset($data['attachments']);
        }
		*/
        foreach ($data as $k => $v) {
            //if (strpos($k, "sub_") !== false) {
                //$subData[$k] = $v;
            //} else {
                $primaryData[$k] = $v;
            //}
        }
        //if ($fileObj = app($this->archivesFileRepository)->insertData($primaryData)) {

            //$id = $fileObj->getKey();
        $result = app($this->formModelingService)->addCustomData($primaryData,'archives_file');
        if($result){
        	if(is_array($result)) return $result;
        	$id = $result;
        	/*
            if (!empty($subData)) {
                $subData['archives_file_id'] = $id;
                app($this->archivesFileRepository)->insertSubFile($subData);
            }
            if (!empty($attachments)) {
                app($this->attachmentService)->attachmentRelation("archives_file", $id, array_filter($attachments));
            }
            */
            $this->saveArchivesLog('archives_file', $id, 'new', $data['file_creator']);
            return $id;
        }

        return ['code' => ['0x000003','common']];
    }

    /**
     * 删除档案文件
     *
     * @param  int|string  $fileId  档案文件id,多个用逗号分隔
     * @param  string      $reason  删除原因
     *
     * @return bool|array  操作成功|错误码
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    public function deleteFile($fileId, $reason = '', $userId)
    {
        $fileIds = array_filter(explode(',', $fileId));
        $res = $this->deleteFiles($fileIds, $userId, $reason);

        return $res ? true : ['code' => ['0x000003', 'common']];
    }

    private function deleteFiles(array $fileIds, $userId, $reason = '')
    {
        if (app($this->archivesFileRepository)->deleteById($fileIds)) {

            app($this->archivesFileRepository)->updateSubFile(['deleted_at' => date("Y-m-d H:i:s")], $fileIds);

            $temp = [
                'destroy_type' => 'file',
                'destroy_content' => $reason,
                'destroy_date' => date("Y-m-d H:i:s"),
                'user_id' => $userId
            ];

            $data = [];
            foreach ($fileIds as $id) {
                $temp['destroy_data_id'] = $id;
                $data[] = $temp;
            }
            app($this->archivesDestroyRepository)->insertMultipleData($data);
            $this->saveArchivesLog('archives_file', $fileIds, 'destroy', $userId);
            return true;
        }
        return false;
    }

    /**
     * 编辑档案文件
     *
     * @param  array       $data    编辑数据
     * @param  int         $fileId  档案文件id
     *
     * @return bool|array  操作成功|错误码
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    function updateFile($data, $fileId, $userId)
    {
        if (strpos($fileId, ',') !== false) {
            $fileIds = array_filter(explode(',', $fileId));
            if (app($this->archivesFileRepository)->updateData($data, ['file_id' => [$fileIds, 'in']])) {
                $type = count($data) == 1 && isset($data['volume_id']) ? 'addfile' : 'edit';

                foreach ($fileIds as $fileId) {
                    $this->saveArchivesLog('archives_file', $fileId, $type, $userId);
                }

                return true;
            }
        }
		/*
        if (isset($data['attachments'])) {
            $attachments = $data['attachments'];
            unset($data['attachments']);
        }
		*/
        $primaryData = $subData = [];
        foreach ($data as $k => $v) {
            //if (strpos($k, "sub_") !== false) {
                //$subData[$k] = $v;
            //} else {
                $primaryData[$k] = $v;
            //}
        }

        //if (app($this->archivesFileRepository)->updateData($primaryData, ['file_id' => $fileId])) {
            /*
        	if (!empty($subData)) {
                if (app($this->archivesFileRepository)->getSubFileDetail(['archives_file_id' => [$fileId]])) {
                    app($this->archivesFileRepository)->updateSubFile($subData, ['archives_file_id' => $fileId]);
                } else {
                    $subData['archives_file_id'] = $fileId;
                    app($this->archivesFileRepository)->insertSubFile($subData);
                }
            }
            app($this->attachmentService)->attachmentRelation("archives_file", $fileId, array_filter($attachments));
			*/
        $result = app($this->formModelingService)->editCustomData($primaryData,'archives_file',$fileId);
        if($result){
        	if(is_array($result)) return $result;
            $this->saveArchivesLog('archives_file', $fileId, 'edit', $userId);
            return true;
        }

        return ['code' => ['0x000003','common']];
    }

    /**
     * 获取档案文件详情
     *
     * @param  int    $fileId  档案文件id
     *
     * @return array  档案文件详情数据|错误码
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    function getFileDetail($fileId, $userId)
    {
        /*if ($primaryObj = app($this->archivesFileRepository)->getDetail($fileId)) {
            $primaryObj = $primaryObj->load('fileCreatorHasOneUser');
            $primaryObj = $primaryObj->load('fileHasOneVolume');
            $primaryData = $primaryObj->toArray();

            $this->saveArchivesLog('archives_file', $fileId, 'read', $userId);

            $primaryData['attachments'] = app($this->attachmentService)->getAttachmentIdsByEntityId(['entity_table'=>'archives_file', 'entity_id'=>$fileId]);
            $primaryData['file_creator_name'] = $primaryData['file_creator_has_one_user']['user_name'];
            unset($primaryData['file_creator_has_one_user']);


            $subObj = app($this->archivesFileRepository)->getSubFile($fileId);
            $subData = $subObj ? $subObj->toArray() : [];
            return $primaryData + $subData;
        }

        return ['code' => ['0x023001','archives']];*/
        $primaryData = (array) app($this->formModelingService)->getCustomDataDetail('archives_file', $fileId);
        if (!empty($primaryData)) {

            $this->saveArchivesLog('archives_file', $fileId, 'read', $userId);

            $subObj = app($this->archivesFileRepository)->getSubFile($fileId);
            $subData = $subObj ? $subObj->toArray() : [];
            return $primaryData + $subData;
        }

        return ['code' => ['0x023001','archives']];
    }

    /**
     * 获取档案文件详情权限
     *
     * @param  int    $fileId  档案文件id
     *
     * @return array  档案文件详情数据|错误码
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */

    function getFilePrivate($fileId){
        $fileDetail = app($this->archivesFileRepository)->getDetail($fileId);
        if($fileDetail && $fileDetail['deleted_at']){
            return false;
        }
        return true;
    }

    /**
     * 获取档案文件日志列表
     *
     * @param  array  $param   查询条件
     * @param  int    $fileId  文件id
     *
     * @return array  查询结果或错误码
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    function getFileLog($param, $fileId)
    {
        $param = $this->parseParams($param);
        $param['search']['log_table']   = ['archives_file'];
        $param['search']['log_data_id'] = [$fileId];
        return $this->getArchivesLog($param);
    }

    /**
     * 获取档案鉴定列表
     *
     * @param  array  $param  查询条件
     *
     * @return array  查询结果或错误码
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    function getAppraisalList($param = [])
    {
        $param = $this->parseParams($param);
        $type = isset($param['type']) ? $param['type'] : 'volume';

        if ($type == 'volume') {
            return $this->response(app($this->archivesVolumeRepository), 'getVolumeTotal', 'getVolumeList', $param);
        }

        return $this->response(app($this->archivesFileRepository), 'getFileTotal', 'getFileList', $param);
    }

    /**
     * 新建档案鉴定
     *
     * @param  array  $data  新建数据
     *
     * @return bool
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    function createAppraisal($data)
    {
        if (isset($data['data']) && !empty($data['data']) && isset($data['tableKey']) && isset($data['current_user_id'])) {
            $data = $data['data'];
            $data['appraisal_type'] = isset($data['appraisal_type']) ? 'file' : 'volume';
//            if ($data['appraisal_type'] == 'file') {
//                // 查找文件id
//                $data['appraisal_data_id'] = DB::table('archives_file')->where('file_name', $data['appraisal_data_id'])->where('file_appraisal', 0)->value('file_id');
//            } else {
//                $data['appraisal_data_id'] = DB::table('archives_volume')->where('volume_name', $data['appraisal_data_id'])->where('volume_appraisal', 0)->value('volume_id');
//            }
            if(!isset($data['appraisal_data_id']) || $data['appraisal_data_id'] == ''){
                if($data['appraisal_type'] == 'file'){
                    return ['code' => ['error_appraisal_file','archives']];
                }
                if($data['appraisal_type'] == 'volume'){
                    return ['code' => ['error_appraisal_volume','archives']];
                }
            }

            if(isset($data['appraisal_date']) && $data['appraisal_date'] == ''){
                return ['code' => ['error_appraisal_time','archives']];
            }

            if(!isset($data['user_id']) || $data['user_id'] == ''){
                return ['code' => ['error_user_id','archives']];
            }
            $data['appraisal_data_id'] = intval($data['appraisal_data_id']);
        }
        $type = isset($data['appraisal_type']) ? $data['appraisal_type'] : 'volume';
        $primaryData = $subData = [];
        foreach ($data as $k => $v) {
            //if (strpos($k, "sub_") !== false) {
                //$subData[$k] = $v;
            //} else {
                $primaryData[$k] = $v;
            //}
        }

        //if ($fileObj = app($this->archivesAppraisalRepository)->insertData($primaryData)) {
        $result = app($this->formModelingService)->addCustomData($primaryData,'archives_appraisal');
        if($result){
        	if(is_array($result)) return $result;
        	$fileObj = $result;

            //if (!empty($subData)) {
                //$subData['archives_appraisal_id'] = $fileObj->getKey();
                //app($this->archivesAppraisalRepository)->insertSubAppraisal($subData);
            //}

        	$origin_data = [];
        	$origin_data['appraisal_type'] = $type;
        	$origin_data['appraisal_data_id'] = $data['appraisal_data_id'];
        	app($this->archivesAppraisalRepository)->updateData($origin_data,['appraisal_id' => $fileObj]);

            if ($type == 'volume') {
                app($this->archivesVolumeRepository)->updateData(['volume_appraisal' => 1], ['volume_id' => $data['appraisal_data_id']]);
            } else if ($type == 'file') {
                app($this->archivesFileRepository)->updateData(['file_appraisal' => 1], ['file_id' => $data['appraisal_data_id']]);
            }

            $this->saveArchivesLog('archives_'.$type, $data['appraisal_data_id'], 'appraisal', $data['user_id']);

            return true;
        }

        return ['code' => ['0x000003','common']];
    }

    /**
     * 获取档案鉴定详情
     *
     * @param  string  $type             档案销毁类型
     * @param  int     $appraisalDataId  档案或文件id
     *
     * @return array   档案文件详情数据|错误码
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    function getAppraisalDetail($type, $appraisalDataId)
    {
        $primaryData = $subData = $appraisalSubData =$filesData = [];

        if ($type == 'volume') {
            if ($primaryObj = app($this->archivesVolumeRepository)->getVolumeDetail($appraisalDataId)) {
                $primaryData = $primaryObj->toArray();
                $subObj = app($this->archivesVolumeRepository)->getSubVolume($appraisalDataId);
                $subData = $subObj ? $subObj->toArray() : [];
            }
        } else if ($type == 'file') {
            if ($primaryObj = app($this->archivesFileRepository)->getDetail($appraisalDataId, true)) {
                $primaryData = $primaryObj->toArray();
                $subObj = app($this->archivesFileRepository)->getSubFile($appraisalDataId);
                $subData = $subObj ? $subObj->toArray() : [];

            }
        }

        $where = [
            'appraisal_type'      => [$type],
            'appraisal_data_id'   => [$appraisalDataId]
        ];

        $appraisalObj = app($this->archivesAppraisalRepository)->getAppraisalDetail($where);
        $appraisalObj = $appraisalObj ? $appraisalObj->toArray() : [];
        $appraisalData = [];
        if (isset($appraisalObj['appraisal_id'])) {
            $appraisalData = (array) app($this->formModelingService)
                ->getCustomDataDetail('archives_appraisal', $appraisalObj['appraisal_id']);
            $appraisalSubObj = app($this->archivesAppraisalRepository)->getSubAppraisal($appraisalObj['appraisal_id']);
            $appraisalSubData = $appraisalSubObj ? $appraisalSubObj->toArray() : [];
        }

        return compact('primaryData', 'subData', 'appraisalData', 'appraisalSubData');
    }

    /**
     * 获取档案销毁列表
     *
     * @param  array  $param  查询条件
     *
     * @return array  查询结果或错误码
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    function getDestroyedList($param = [])
    {
    	//add_custom_field
    	//$this->add_custom_field();
        $param = $this->parseParams($param);
        $type = isset($param['type']) ? $param['type'] : 'volume';
        $param['search']['destroy'] = 1;

        if ($type == 'volume') {
            $result = $this->response(app($this->archivesVolumeRepository), 'getVolumeTotal', 'getVolumeList', $param);
            if($result['list'] && is_array($result['list'])){
                foreach ($result['list'] as $key => $vo){
                    isset($vo['volume_hold_time']) && $result['list'][$key]['volume_hold_time'] = app($this->systemComboboxService)->getComboboxFieldsNameByIdentify('ARCHIVES_TIME',$vo['volume_hold_time']);
                }
            }
            return $result;
//            return $this->response(app($this->archivesVolumeRepository), 'getVolumeTotal', 'getVolumeList', $param);
        }
        $result = $this->response(app($this->archivesFileRepository), 'getFileTotal', 'getFileList', $param);
        if($result['list'] && is_array($result['list'])){
            foreach ($result['list'] as $key => $vo){
                isset($vo['file_hold_time']) && $result['list'][$key]['file_hold_time'] = app($this->systemComboboxService)->getComboboxFieldsNameByIdentify('ARCHIVES_TIME',$vo['file_hold_time']);
            }
        }
        return $result;
//        return $this->response(app($this->archivesFileRepository), 'getFileTotal', 'getFileList', $param);
    }

    /**
     * 删除档案销毁
     *
     * @param  string       $type       档案销毁类型
     * @param  int|string   $destroyId  档案或文件id,多个用逗号隔开
     *
     * @return int|array    档案文件详情数据|错误码
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    function deleteDestroyed($type, $destroyId, $userId)
    {
        $primaryData = $subData = [];
        $destroyIds = array_filter(explode(',', $destroyId));
        if ($type == 'volume') {
            $this->deleteVolumeDestroyed($destroyIds, $userId);
            // 删除下属文件
            $relationFileIds = app($this->archivesFileRepository)->entity
                ->whereIn('volume_id', $destroyIds)->pluck('file_id')->toArray();
            if ($relationFileIds) {
                $this->deleteFileDestroyed($relationFileIds, $userId, false); // 文件可能没被销毁，也要强制删除
                $this->deleteDestroyedData($relationFileIds, 'file');
            }
        } else if ($type == 'file') {
            $this->deleteFileDestroyed($destroyIds, $userId);
        }
        $this->deleteDestroyedData($destroyIds, $type);

        return true;
    }

    /**
     * 回复档案销毁
     *
     * @param  string      $type       档案销毁类型
     * @param  int|string  $destroyId  档案或文件id,多个用逗号隔开
     *
     * @return int|array   档案文件详情数据|错误码
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    function restoreDestroyed($type, $destroyId, $userId)
    {
        $primaryData = $subData = [];
        $destroyIds = array_filter(explode(',', $destroyId));

        if ($type == 'volume') {
            if (app($this->archivesVolumeRepository)->restoreSoftDelete(['volume_id' => [$destroyIds, 'in']])) {

                app($this->archivesVolumeRepository)->updateSubVolume(['deleted_at' => null],  $destroyIds);

                foreach ($destroyIds as $id) {
                    $this->saveArchivesLog('archives_volume', $id, 'back', $userId);
                }
            }
        } else if ($type == 'file') {
            if (app($this->archivesFileRepository)->restoreSoftDelete(['file_id' => [$destroyIds, 'in']])) {

                app($this->archivesFileRepository)->updateSubFile(['deleted_at' => null], $destroyIds);

                foreach ($destroyIds as $id) {
                    $this->saveArchivesLog('archives_file', $id, 'back', $userId);
                }
            }
        }

        $where = [
            'destroy_type'      => [$type],
            'destroy_data_id'   => [$destroyIds, 'in']
        ];
        app($this->archivesDestroyRepository)->deleteByWhere($where);

        return true;
    }

    /**
     * 获取档案销毁详情
     *
     * @param  string  $type       档案销毁类型
     * @param  int     $destroyId  档案或文件id
     *
     * @return array   档案文件详情数据|错误码
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    function getDestroyedDetail($type, $destroyId)
    {
        $primaryData = $subData = [];

        if ($type == 'volume') {
            if ($primaryObj = app($this->archivesVolumeRepository)->getDetail($destroyId, true)) {
                $primaryData = $primaryObj->load('volumeHasOneLibrary', 'volumeCreatorHasOneUser')->toArray();
                $subObj = app($this->archivesVolumeRepository)->getSubVolume($destroyId);
                $subData = $subObj ? $subObj->toArray() : [];
                if($primaryData && is_array($primaryData)){
                    $primaryData['volume_hold_time'] = app($this->systemComboboxService)->getComboboxFieldsNameByIdentify('ARCHIVES_TIME',$primaryData['volume_hold_time']);;
                }
            }
        } else if ($type == 'file') {
            if ($primaryObj = app($this->archivesFileRepository)->getDetail($destroyId, true)) {
                $primaryData = $primaryObj->load('fileHasOneVolume', 'fileCreatorHasOneUser')->toArray();
                $subObj = app($this->archivesFileRepository)->getSubFile($destroyId);
                $subData = $subObj ? $subObj->toArray() : [];
                if($primaryData && is_array($primaryData)){
                    $primaryData['file_hold_time'] = app($this->systemComboboxService)->getComboboxFieldsNameByIdentify('ARCHIVES_TIME',$primaryData['file_hold_time']);;
                }
            }
        }
        $where = [
            'destroy_type'      => [$type],
            'destroy_data_id'   => [$destroyId]
        ];
        $destroyObj = app($this->archivesDestroyRepository)->getDestroyDetail($where);
        $destroyData = $destroyObj ? $destroyObj->toArray() : [];

        return compact('primaryData', 'subData', 'destroyData');
    }

    /**
     * 获取档案借阅列表
     *
     * @param  array  $param 查询条件
     *
     * @return array  查询结果或错误码
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    function getBorrowApplyList($param = [], $userInfo)
    {
        $param = $this->parseParams($param);
        $type = isset($param['type']) ? $param['type'] : 'volume';

        $libraryIds = $this->canBorrowLibrary($userInfo);

        if (empty($libraryIds)) {
            return '';
        }

        if ($type == 'volume') {
            $param['search']['library_id'] = [$libraryIds, 'in'];
            return $this->response(app($this->archivesVolumeRepository), 'getVolumeTotal', 'getVolumeList', $param);
        }

        $volumeObj = app($this->archivesVolumeRepository)->getVolumeField(['library_id' => [$libraryIds, 'in']], 'volume_id');

        if (!$volumeObj) {
            return '';
        }

        $volumeIds = $volumeObj->toArray();
        $volumeIds[] = 0;

        $param['search']['volume_id'] = [$volumeIds, 'in'];
        return $this->response(app($this->archivesFileRepository), 'getFileTotal', 'getFileList', $param);
    }

    /**
     * 用户可以借阅的卷库
     *
     * @return array  卷库id
     *
     * @author qishaobo
     *
     * @since  2015-11-13
     */
    function canBorrowLibrary($userInfo)
    {
        $user_dept_id = isset($userInfo['dept_id']) ? $userInfo['dept_id'] : (isset($userInfo['user_has_one_system_info']) ? $userInfo['user_has_one_system_info']['dept_id'] : 0);
        if ($user_dept_id < 1) {
            return [];
        }
        $permissionObj = app($this->archivesLibraryPermissionRepository)->getLibraryIds($user_dept_id);

        if (!$permissionObj) {
            return [];
        }

        $libraryIds = $permissionObj->toArray();
        $libraryIds[] = 0;
        return $libraryIds;
        /*if ($userInfo['dept_id'] < 1) {
            return [];
        }
        $permissionObj = app($this->archivesLibraryPermissionRepository)->getLibraryIds($userInfo['dept_id']);

        if (!$permissionObj) {
            return [];
        }

        $libraryIds = $permissionObj->toArray();
        $libraryIds[] = 0;
        return $libraryIds;*/
    }

    /**
     * 是否可以借阅档案
     *
     * @param  array      $data  新建数据
     *
     * @return int|array         新建数据id|错误码
     *
     * @author qishaobo
     *
     * @since  2015-11-13
     */
    function canBorrow($data, $userInfo)
    {
        if (isset($data['borrow_type']) && $data['borrow_type'] == 'file') {
            if ($fileObj = app($this->archivesFileRepository)->getDetail($data['borrow_data_id'])) {
                if ($fileObj->volume_id == 0) { //文件所属案卷为空
                    return true;
                }

                $data['borrow_data_id'] = $fileObj->volume_id;
            } else {
                return false;
            }
        }
        if ($volumeObj = app($this->archivesVolumeRepository)->getDetail($data['borrow_data_id'])) {
            if ($volumeObj->library_id == 0) { //案卷所属卷库为空
                return true;
            }

            $libraryId = $volumeObj->library_id;

            $permissionDepts = app($this->archivesLibraryPermissionRepository)->getPermission($libraryId);
            if (empty($permissionDepts->toArray())) {
                return false;
            }

            $libraryIds = $this->canBorrowLibrary($userInfo);
            if (empty($libraryIds)) {
                return false;
            }

            if (in_array($libraryId, $libraryIds)) {
                return true;
            }
        }

       return false;
    }

    /**
     * 新建借阅
     *
     * @param  array      $data  新建数据
     *
     * @return int|array         新建数据id|错误码
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    function createBorrow($data, $userInfo = [])
    {
        if (isset($data['data']) && !empty($data['data']) && isset($data['tableKey']) && isset($data['current_user_id'])) {
            $data = $data['data'];
            $data['borrow_type'] = isset($data['borrow_type']) ? 'file' : 'volume';
//            if ($data['borrow_type'] == 'file') {
//                // 查找文件id
//                $data['borrow_data_id'] = DB::table('archives_file')->where('file_name', $data['borrow_data_id'])->value('file_id');
//            } else {
//                $data['borrow_data_id'] = DB::table('archives_volume')->where('volume_name', $data['borrow_data_id'])->value('volume_id');
//            }
            if(!isset($data['borrow_data_id']) || $data['borrow_data_id'] == ''){
                if($data['borrow_type'] == 'file'){
                    return ['code' => ['error_file','archives']];
                }
                if($data['borrow_type'] == 'volume'){
                    return ['code' => ['error_volume','archives']];
                }
            }

            if(!isset($data['borrow_end']) || $data['borrow_end'] == ''){
                return ['code' => ['error_end_time','archives']];
            }
            if(!isset($data['borrow_start']) || $data['borrow_start'] == ''){
                return ['code' => ['error_start_time','archives']];
            }
            if(isset($data['created_at']) && $data['created_at'] == ''){
                return ['code' => ['error_create_time','archives']];
            }
            if(!isset($data['borrow_user_id']) || $data['borrow_user_id'] == ''){
                return ['code' => ['error_borrow_user_id','archives']];
            }
            $data['borrow_data_id'] = intval($data['borrow_data_id']);
            $data['borrow_number'] = date('YmdHis', time());
        }
        $userInfo && $data['borrow_user_id'] = $userInfo['user_id'];
        $userInfo = app($this->userRepository)->getUserAllData($data['borrow_user_id'])->toArray();
        $data['borrow_data_id'] = rtrim($data['borrow_data_id'], ',');
        if (strpos($data['borrow_data_id'], ',') !== false) {
            $borrowIds = array_filter(explode(',', $data['borrow_data_id']));
            foreach ($borrowIds as $borrowId) {
                $data['borrow_data_id'] = $borrowId;
                $result = $this->createBorrowData($data, $userInfo);
                if (isset($result['code'])) {
                    return $result;
                }
            }
            return true;
        }
        return $this->createBorrowData($data, $userInfo);
    }

    /**
     * 新建借阅
     *
     * @param  array      $data  新建数据
     *
     * @return int|array         新建数据id|错误码
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    function createBorrowData($data, $userInfo)
    {
        if (!$this->canBorrow($data, $userInfo)) {
            return ['code' => ['0x000006','common']];
        }

        $primaryData = $subData = [];

        foreach ($data as $k => $v) {
            //if (strpos($k, "sub_") !== false) {
                //$subData[$k] = $v;
            //} else {
                $primaryData[$k] = $v;
            //}
        }
        if (!empty($primaryData['borrow_start'])) {
            if ($primaryData['borrow_start'] < date("Y-m-d H:i:s")) {
                return ['code' => ['0x023005','archives']];
            }
        }

        if (!empty($primaryData['borrow_end']) && !empty($primaryData['borrow_start'])) {
            if ($primaryData['borrow_end'] < $primaryData['borrow_start']) {
                return ['code' => ['0x023004','archives']];
            }
        }


        //if ($volumeObj = app($this->archivesBorrowRepository)->insertData($primaryData)) {
			/*
            if (!empty($subData)) {
                $subData['archives_borrow_id'] = $volumeObj->getKey();
                app($this->archivesBorrowRepository)->insertSubBorrow($subData);
            }
			*/
        $result = app($this->formModelingService)->addCustomData($primaryData,'archives_borrow');
        if($result){
        	if(is_array($result)) return $result;
            $approvalData = $primaryData['borrow_type'] == 'volume' ?
                app($this->archivesVolumeRepository)->getDetail($primaryData['borrow_data_id'])['is_approval'] :
                app($this->archivesFileRepository)->getDetail($primaryData['borrow_data_id'])['is_approval'];
        	$id = $result;
        	$origin_data = [];
        	$origin_data['borrow_type']   =    $primaryData['borrow_type'];
        	$origin_data['borrow_data_id']= $primaryData['borrow_data_id'];
        	$origin_data['borrow_number'] =  $primaryData['borrow_number'];
            $origin_data['borrow_status'] = ($approvalData == 1) ? 1 : 0;
        	app($this->archivesBorrowRepository)->updateData($origin_data,['borrow_id' => $id]);

            $this->saveArchivesLog('archives_'.$primaryData['borrow_type'], $primaryData['borrow_data_id'], 'borrow', $data['borrow_user_id']);
            return true;
        }

        return ['code' => ['0x000003','common']];
    }

    /**
     * 获取档案借阅列表数据
     *
     * @param  array  $param  查询条件
     *
     * @return array  查询结果或错误码
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    function getBorrowedList($param = [],$own)
    {
        $param = $this->parseParams($param);
        if(isset($param['sign'])){
            $param['search']['borrow_user_id'] = [$own['user_id']];
            unset($param['sign']);
        }
        $param['search']['borrow_type'] = isset($param['search']['borrow_type']) ? $param['search']['borrow_type'] : ['volume'];
        return $this->response(app($this->archivesBorrowRepository), 'getBorrowTotal',  'getBorrowList', $param);
    }

    /**
     * 获取我的档案借阅列表数据
     *
     * @param  array  $param  查询条件
     *
     * @return array  查询结果或错误码
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    function getMyBorrowList($param = [], $userId)
    {
        $param = $this->parseParams($param);
        $param['search']['borrow_type'] = isset($param['search']['borrow_type']) ? $param['search']['borrow_type'] : ['volume'];
        $param['search']['borrow_user_id'] = [$userId];

        return $this->response(app($this->archivesBorrowRepository), 'getBorrowTotal', 'getBorrowList', $param);
    }

    /**
     * 删除我的档案借阅或档案审核
     *
     * @param  int|string  $borrowId  借阅档案id,多个用逗号隔开
     *
     * @return int|array   操作成功|错误码
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    function deleteMyBorrow($borrowId, $userId)
    {
        $borrowIds = array_filter(explode(',', $borrowId));
        $where = [
            'borrow_user_id'    => [$userId],
            'borrow_id'         => [$borrowIds, 'in']
        ];

        if (app($this->archivesBorrowRepository)->deleteByWhere($where)) {

            app($this->archivesBorrowRepository)->updateSubBorrow(['deleted_at' => date("Y-m-d H:i:s")],  $borrowId);

            return true;
        }

        return ['code' => ['0x000003','common']];
    }

    /**
     * 获取我的档案借阅详情
     *
     * @param  int    $borrowId  借阅档案id
     *
     * @return array  档案文件详情数据|错误码
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    function getMyBorrowDetail($borrowId, $userInfo)
    {
        $where = [
            'borrow_id'         => [$borrowId]
        ];

        if (!empty($userInfo['menus']['menu']) && in_array('910', $userInfo['menus']['menu']) || in_array('909', $userInfo['menus']['menu'])) {
        } else {
            $where['borrow_user_id'] = [$userInfo['user_id']];
        }

        return $this->borrowDetail($where);
    }

    /**
     * 删除档案借阅
     *
     * @param  int|string  $borrowId  借阅档案id,多个用逗号隔开
     * @param  array  $params  参数
     *
     * @return int|array   操作成功|错误码
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    function deleteBorrowed($borrowId, $params = [])
    {
        $borrowIds = array_filter(explode(',', $borrowId));
        $where = [$borrowIds, 'in'];
        $isSoft = Arr::get($params, 'is_soft', false);// 添加支持软删除，默认硬删兼容
        if ($isSoft) {
            if (app($this->archivesBorrowRepository)->deleteByWhere(['borrow_id' => $where])) {
                app($this->archivesBorrowRepository)->deleteSubBorrow(['archives_borrow_id' => $where]);
                return true;
            }
        } else {
            if (app($this->archivesBorrowRepository)->deleteSoftDelete(['borrow_id' => $where], false)) {
                app($this->archivesBorrowRepository)->deleteSubBorrow(['archives_borrow_id' => $where]);
                return true;
            }
        }

        return ['code' => ['0x000003','common']];
    }

    /**
     * 删除审批
     *
     * @param  int|string  $borrowId  借阅档案id,多个用逗号隔开
     *
     * @return int|array   操作成功|错误码
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    function deleteAudit($borrowId)
    {
        $borrowIds = array_filter(explode(',', $borrowId));
        $where = [$borrowIds, 'in'];
        app($this->archivesBorrowRepository)->updateData(['audit_delete' => 1], ['borrow_id' => $where]);
        return true;
    }

    /**
     * 获取档案借阅详情
     *
     * @param  int    $borrowId  借阅档案id
     *
     * @return array  档案文件详情数据|错误码
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    function getborrowDetail($borrowId)
    {
        return $this->borrowDetail(['borrow_id' => [$borrowId]]);
    }

    /**
     * 获取档案借阅详情
     *
     * @param  array  $where  查询条件
     *
     * @return array  档案文件详情数据|错误码
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    function borrowDetail(array $where)
    {
        $archivesData = [];
        $result = app($this->archivesBorrowRepository)->getBorrowDetail($where);
        if (!empty($result)) {
            $borrowData = $result->toArray();
            if ($borrowData['borrow_type'] == 'volume') {
                $borrowList = app($this->archivesVolumeRepository)->getDetail($borrowData['borrow_data_id']);
                $borrowData['borrow_name'] = $borrowList ? $borrowList->volume_name :'';
            } else if ($borrowData['borrow_type'] == 'file') {
                $borrowList = app($this->archivesFileRepository)->getDetail($borrowData['borrow_data_id']);
                $borrowData['borrow_name'] = $borrowList ? $borrowList->file_name :'';
            }
            $archivesData = (array) app($this->formModelingService)->getCustomDataDetail('archives_borrow', $where['borrow_id']);
            if (isset($archivesData['code'])) {
                return $borrowData;
            }

            return compact('borrowData', 'archivesData');
        }
        return ['code' => ['0x000006','common']];
    }

    /**
     * 获取档案name
     *
     * @param  array  $where  查询条件
     *
     * @return array  档案文件详情数据|错误码
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */

    public function getFileName($borrow_data_id,$borrow_type){
        switch ($borrow_type){
            case 'volume':
                $borrowList = app($this->archivesVolumeRepository)->getDetail($borrow_data_id);
                break;
            case 'file':
                $borrowList = app($this->archivesFileRepository)->getDetail($borrow_data_id);
                break;
        }
        return $borrowList ? $borrowList['file_name'] :'';
    }

    /**
     * 获取审批档案借阅列表数据
     *
     * @param  array  $param  查询条件
     *
     * @return array  查询结果或错误码
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    function getBorrowApproveList($param = [])
    {
        $param = $this->parseParams($param);
      //  $param['search']['borrow_type'] = isset($param['search']['borrow_type']) ? $param['search']['borrow_type'] : ['volume'];
        return $this->response(app($this->archivesBorrowRepository), 'getBorrowTotal', 'getBorrowList', $param);
    }

    /**
     * 更新借阅
     *
     * @param  int    $borrowId  借阅档案id
     * @param  array  $data      要更新的数据
     *
     * @return array  查询结果或错误码
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    function updateBorrow($borrowId, $data, $own = null)
    {
        $own && $data['auditor_id'] = $own['user_id'];
        return app($this->archivesBorrowRepository)->updateData($data, ['borrow_id' => $borrowId]);
    }

    /**
     * 获取审批档案借阅列表数据
     *
     * @param  int    $borrowId  借阅档案id
     * @param  array  $data      要更新的数据
     *
     * @return array  查询结果或错误码
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    function updateBorrowApprove($borrowId, $data, $userId)
    {
        $borrowData = ['borrow_status' => $data['borrow_status']];

        if ($data['borrow_status'] == 1 || $data['borrow_status'] == 2) {
            $borrowData['auditor_id'] = $userId;
            $borrowData['audit_time'] = date("Y-m-d H:i:s");
            if (isset($data['audit_content'])) {
                $borrowData['audit_content'] = $data['audit_content'];
            }
        }

        if ($data['borrow_status'] == 3) {
            $borrowData['return_time'] = date("Y-m-d H:i:s");
            if (isset($data['return_content'])) {
                $borrowData['return_content'] = $data['return_content'];
            }
        }

        if ($data['borrow_status'] == 4) {
            $borrowData['take_back_user'] = $userId;
            if (isset($data['take_back_content'])) {
                $borrowData['take_back_content'] = $data['take_back_content'];
            }
        }

        return app($this->archivesBorrowRepository)->updateData($borrowData, ['borrow_id' => $borrowId]);
    }

    /**
     * 保存日志
     *
     * @param  int        $libraryId  卷库id
     *
     * @return int|array  卷库详情数据|错误码
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    function saveArchivesLog($table, $id, $type, $userId)
    {
        $temp = [
            'log_table' => $table,
            'log_type' => $type,
            'log_date' => date("Y-m-d H:i:s"),
            'user_id' => $userId,
            'log_ip' => getClientIp(),
        ];

        if (is_array($id)) {
            $data = [];
            foreach ($id as $item) {
                $temp['log_data_id'] = $item;
                $data[] = $temp;
            }
            app($this->archivesLogRepository)->insertMultipleData($data);
        } else {
            $temp['log_data_id'] = $id;
            app($this->archivesLogRepository)->insertData($temp);
        }
    }

    /**
     * 获取日志列表
     *
     * @param  array  $param  查询条件
     *
     * @return array  查询结果或错误码
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    function getArchivesLog($param)
    {
        return $this->response(app($this->archivesLogRepository), 'getTotal', 'getLogList', $param);
    }

    function getIndexHoldTime($param = [])
    {
    	$lists = [['hold_time_id'=>0,'hold_time_title'=>trans('archives.forever')],['hold_time_id'=>1,'hold_time_title'=>trans('archives.long_time')],['hold_time_id'=>2,'hold_time_title'=>trans('archives.short_time')]];
        if (!isset($param['search'])) {
            return $lists;
        }
        $result = [];
        $search = json_decode($param['search'], true);
        if (isset($search['hold_time_id']) && isset($search['hold_time_id'][0]) && $search['hold_time_id'][0] === '') {
            return $result;
        }
        foreach ($lists as $key => $vo){
            if(isset($search['hold_time_title']) && is_array($search['hold_time_title'])){
                strstr($vo['hold_time_title'],$search['hold_time_title'][0]) && $result[] = $vo;
            }else{
                ($search['hold_time_id'][0] == $vo['hold_time_id']) && $result[] = $vo;
            }
        }

//        foreach ($lists as $key => $item) {
//            if (!is_array($search['hold_time_id'][0])) {
//                if (in_array($item['hold_time_id'], $search['hold_time_id'])) {
//                    $result[] = $item;
//                }
//            } else {
//                if (in_array($item['hold_time_id'], $search['hold_time_id'][0])) {
//                    $result[] = $item;
//                }
//            }
//
//        }
        return $result;
    }

    //获取卷库列表（选择器专用）
    function getChoiceLibraryList($param = [])
    {
        $param = $this->parseParams($param);
        return $this->response(app($this->archivesLibraryRepository), 'getChoiceLibraryTotal', 'getChoiceLibraryList', $param);
    }

    //获取案卷列表（选择器专用）
    function getChoiceVolumeList($param = [])
    {
        $param = $this->parseParams($param);
        return $this->response(app($this->archivesVolumeRepository), 'getChoiceVolumeTotal', 'getChoiceVolumeList', $param);
    }

    //添加系统自定义字段
    public static function add_custom_field(){
    	/*
    	$table = "custom_fields_table";
    	if(!Schema::hasTable($table)) return false;
    	$data =  array();
    	//卷库
    	$data[] = ['field_code'=>'library_name','field_name'=>'卷库名称',          'field_directive'=>'text',     'field_table_key'=>'archives_library','is_system'=>'1','field_options'=>'{"type":"text","format":{"type":"text"},"validate":{"required":1},"fieldSearch":1,"advancedSearch":1,"autoSearch":1}','field_data_type'=>'varchar','field_sort'=>'0','field_list_show'=>'1','field_allow_order'=>'1','mobile_list_field'=>'primary'];
    	$data[] = ['field_code'=>'library_number','field_name'=>'卷库编号',        'field_directive'=>'text',     'field_table_key'=>'archives_library','is_system'=>'1','field_options'=>'{"type":"text","format":{"type":"text"},"validate":{"required":1},"fieldSearch":1,"advancedSearch":1}','field_data_type'=>'varchar','field_sort'=>'1','field_list_show'=>'1','field_allow_order'=>'0','mobile_list_field'=>'secondary'];
    	$data[] = ['field_code'=>'permission','field_name'=>'借阅范围',    'field_directive'=>'selector', 'field_table_key'=>'archives_library','is_system'=>'1','field_options'=>'{"type":"selector","selectorConfig":{"multiple":1,"category":"common","type":"dept"}}','field_data_type'=>'text',   'field_sort'=>'2','field_list_show'=>'0','field_allow_order'=>'0','mobile_list_field'=>''];
    	$data[] = ['field_code'=>'created_at','field_name'=>'创建时间',            'field_directive'=>'datetime', 'field_table_key'=>'archives_library','is_system'=>'1','field_options'=>'{"type":"text","format":{"type":"datetime"},"disabled":1,"default":true}','field_data_type'=>'varchar','field_sort'=>'3','field_list_show'=>'0','field_allow_order'=>'0','mobile_list_field'=>'time'];
    	$data[] = ['field_code'=>'library_creator','field_name'=>'创建人',        'field_directive'=>'selector',  'field_table_key'=>'archives_library','is_system'=>'1','field_options'=>'{"type":"selector","selectorConfig":{"category":"common","type":"user"},"disabled":1,"default":true}','field_data_type'=>'text','field_sort'=>'4', 'field_list_show'=>'0','field_allow_order'=>'0','mobile_list_field'=>''];
    	$data[] = ['field_code'=>'attachments','field_name'=>'附件',       'field_directive'=>'upload',   'field_table_key'=>'archives_library','is_system'=>'1', 'field_options'=>'{"type":"upload","fullRow":-1,"uploadConfig":{"singleImage":0,"multiple":1,"onlyImage":0,"fileCount":5,"buttonText":"上传附件"}}','field_data_type'=>'varchar','field_sort'=>'5','field_list_show'=>'0','field_allow_order'=>'0','mobile_list_field'=>''];
    	//案卷
    	$data[] = ['field_code'=>'volume_name','field_name'=>'案卷名称',         'field_directive'=>'text',     'field_table_key'=>'archives_volume','is_system'=>'1','field_options'=>'{"type":"text","format":{"type":"text"},"validate":{"required":1},"fieldSearch":1,"advancedSearch":1,"autoSearch":1}','field_data_type'=>'varchar','field_sort'=>'0','field_list_show'=>'1','field_allow_order'=>'1','mobile_list_field'=>'primary'];
    	$data[] = ['field_code'=>'volume_number','field_name'=>'案卷编号',       'field_directive'=>'text',     'field_table_key'=>'archives_volume','is_system'=>'1','field_options'=>'{"type":"text","format":{"type":"text"},"validate":{"required":1},"fieldSearch":1,"advancedSearch":1}','field_data_type'=>'varchar','field_sort'=>'1','field_list_show'=>'1','field_allow_order'=>'0','mobile_list_field'=>'remark'];
    	$data[] = ['field_code'=>'volume_hold_time','field_name'=>'保管期限',    'field_directive'=>'select',   'field_table_key'=>'archives_volume','is_system'=>'1','field_options'=>'{"type":"select","selectConfig":{"sourceType":"systemData","sourceValue":{"module":"archives","field":"hold_time"}},"advancedSearch":1}',        'field_data_type'=>'varchar','field_sort'=>'2', 'field_list_show'=>'1','field_allow_order'=>'0','mobile_list_field'=>''];
    	$data[] = ['field_code'=>'library_id','field_name'=>'所属卷库',          'field_directive'=>'select',   'field_table_key'=>'archives_volume','is_system'=>'1','field_options'=>'{"type":"select","selectConfig":{"sourceType":"systemData","sourceValue":{"module":"archives","field":"library_id"}},"advancedSearch":1}',       'field_data_type'=>'varchar','field_sort'=>'3', 'field_list_show'=>'1','field_allow_order'=>'0','mobile_list_field'=>'secondary'];
    	$data[] = ['field_code'=>'volume_year','field_name'=>'组卷年度',         'field_directive'=>'text',     'field_table_key'=>'archives_volume','is_system'=>'1','field_options'=>'{"type":"text","format":{"type":"text"},"advancedSearch":1}','field_data_type'=>'varchar','field_sort'=>'4','field_list_show'=>'1','field_allow_order'=>'0','mobile_list_field'=>'tag'];
    	$data[] = ['field_code'=>'volume_creator','field_name'=>'创建人',        'field_directive'=>'selector', 'field_table_key'=>'archives_volume','is_system'=>'1','field_options'=>'{"type":"selector","selectorConfig":{"category":"common","type":"user"},"disabled":1,"default":true}','field_data_type'=>'text','field_sort'=>'5', 'field_list_show'=>'0','field_allow_order'=>'0','mobile_list_field'=>''];
    	$data[] = ['field_code'=>'created_at','field_name'=>'创建时间',          'field_directive'=>'datetime', 'field_table_key'=>'archives_volume','is_system'=>'1','field_options'=>'{"type":"text","format":{"type":"datetime"},"disabled":1,"default":true,"advancedSearch":1}','field_data_type'=>'varchar','field_sort'=>'6','field_list_show'=>'0','field_allow_order'=>'0','mobile_list_field'=>''];
    	$data[] = ['field_code'=>'attachments','field_name'=>'附件',            'field_directive'=>'upload',   'field_table_key'=>'archives_volume','is_system'=>'1', 'field_options'=>'{"type":"upload","fullRow":-1,"uploadConfig":{"singleImage":0,"multiple":1,"onlyImage":0,"fileCount":5,"buttonText":"上传附件"}}','field_data_type'=>'varchar','field_sort'=>'7','field_list_show'=>'0','field_allow_order'=>'0','mobile_list_field'=>''];
    	//文件
    	$data[] = ['field_code'=>'file_name','field_name'=>'文件名称',         'field_directive'=>'text',     'field_table_key'=>'archives_file','is_system'=>'1','field_options'=>'{"type":"text","format":{"type":"text"},"validate":{"required":1},"fieldSearch":1,"advancedSearch":1,"autoSearch":1}','field_data_type'=>'varchar','field_sort'=>'0','field_list_show'=>'1','field_allow_order'=>'1','mobile_list_field'=>'primary'];
    	$data[] = ['field_code'=>'file_number','field_name'=>'文件编号',       'field_directive'=>'text',     'field_table_key'=>'archives_file','is_system'=>'1','field_options'=>'{"type":"text","format":{"type":"text"},"validate":{"required":1},"fieldSearch":1,"advancedSearch":1}','field_data_type'=>'varchar','field_sort'=>'1','field_list_show'=>'1','field_allow_order'=>'0','mobile_list_field'=>'remark'];
    	$data[] = ['field_code'=>'volume_id','field_name'=>'所属案卷',          'field_directive'=>'select',   'field_table_key'=>'archives_file','is_system'=>'1','field_options'=>'{"type":"select","selectConfig":{"sourceType":"systemData","sourceValue":{"module":"archives","field":"volume_id"}},"advancedSearch":1}',       'field_data_type'=>'varchar','field_sort'=>'2', 'field_list_show'=>'1','field_allow_order'=>'0','mobile_list_field'=>'secondary'];
    	$data[] = ['field_code'=>'file_year','field_name'=>'组卷年度',         'field_directive'=>'text',     'field_table_key'=>'archives_file','is_system'=>'1','field_options'=>'{"type":"text","format":{"type":"text"},"advancedSearch":1}','field_data_type'=>'varchar','field_sort'=>'3','field_list_show'=>'1','field_allow_order'=>'0','mobile_list_field'=>'tag'];
    	$data[] = ['field_code'=>'file_hold_time','field_name'=>'保管期限',    'field_directive'=>'select',   'field_table_key'=>'archives_file','is_system'=>'1','field_options'=>'{"type":"select","selectConfig":{"sourceType":"systemData","sourceValue":{"module":"archives","field":"hold_time"}},"advancedSearch":1}',        'field_data_type'=>'varchar','field_sort'=>'4', 'field_list_show'=>'1','field_allow_order'=>'0','mobile_list_field'=>''];
    	$data[] = ['field_code'=>'file_creator','field_name'=>'创建人',        'field_directive'=>'selector', 'field_table_key'=>'archives_file','is_system'=>'1','field_options'=>'{"type":"selector","selectorConfig":{"category":"common","type":"user"},"disabled":1,"default":true}','field_data_type'=>'text','field_sort'=>'5', 'field_list_show'=>'1','field_allow_order'=>'0','mobile_list_field'=>''];
    	$data[] = ['field_code'=>'created_at','field_name'=>'创建时间',          'field_directive'=>'datetime', 'field_table_key'=>'archives_file','is_system'=>'1','field_options'=>'{"type":"text","format":{"type":"datetime"},"disabled":1,"default":true,"advancedSearch":1}','field_data_type'=>'varchar','field_sort'=>'6','field_list_show'=>'0','field_allow_order'=>'0','mobile_list_field'=>''];
    	$data[] = ['field_code'=>'attachments','field_name'=>'附件',            'field_directive'=>'upload',   'field_table_key'=>'archives_file','is_system'=>'1', 'field_options'=>'{"type":"upload","fullRow":-1,"uploadConfig":{"singleImage":0,"multiple":1,"onlyImage":0,"fileCount":5,"buttonText":"上传附件"}}','field_data_type'=>'varchar','field_sort'=>'7','field_list_show'=>'0','field_allow_order'=>'0','mobile_list_field'=>''];
    	//鉴定
    	$data[] = ['field_code'=>'user_id','field_name'=>'鉴定人',            'field_directive'=>'selector',  'field_table_key'=>'archives_appraisal','is_system'=>'1','field_options'=>'{"type":"selector","selectorConfig":{"category":"common","type":"user"},"disabled":1,"default":true}','field_data_type'=>'text','field_sort'=>'0','field_list_show'=>'0','field_allow_order'=>'0','mobile_list_field'=>''];
    	$data[] = ['field_code'=>'appraisal_date','field_name'=>'鉴定时间',    'field_directive'=>'datetime', 'field_table_key'=>'archives_appraisal','is_system'=>'1','field_options'=>'{"type":"text","format":{"type":"datetime"},"disabled":1,"default":true}','field_data_type'=>'varchar','field_sort'=>'1','field_list_show'=>'0','field_allow_order'=>'0','mobile_list_field'=>''];
    	$data[] = ['field_code'=>'appraisal_content','field_name'=>'鉴定意见', 'field_directive'=>'textarea', 'field_table_key'=>'archives_appraisal','is_system'=>'1','field_options'=>'{"type":"textarea","fullRow":2,"fullCell":true}','field_data_type'=>'text','field_sort'=>'2','field_list_show'=>'0','field_allow_order'=>'0','mobile_list_field'=>''];
    	//借阅
    	$data[] = ['field_code'=>'borrow_user_id','field_name'=>'申请人',     'field_directive'=>'selector',  'field_table_key'=>'archives_borrow','is_system'=>'1','field_options'=>'{"type":"selector","selectorConfig":{"category":"common","type":"user"},"disabled":1,"default":true}','field_data_type'=>'text','field_sort'=>'0','field_list_show'=>'0','field_allow_order'=>'0','mobile_list_field'=>''];
    	$data[] = ['field_code'=>'created_at','field_name'=>'申请时间',       'field_directive'=>'datetime',   'field_table_key'=>'archives_borrow','is_system'=>'1','field_options'=>'{"type":"text","format":{"type":"datetime"},"disabled":1,"default":true}','field_data_type'=>'varchar','field_sort'=>'1','field_list_show'=>'0','field_allow_order'=>'0','mobile_list_field'=>''];
    	$data[] = ['field_code'=>'borrow_start','field_name'=>'借阅时间',     'field_directive'=>'datetime',   'field_table_key'=>'archives_borrow','is_system'=>'1','field_options'=>'{"type":"text","format":{"type":"datetime"},"fullRow":2,"fullCell":true}','field_data_type'=>'varchar','field_sort'=>'2','field_list_show'=>'0','field_allow_order'=>'0','mobile_list_field'=>''];
    	$data[] = ['field_code'=>'borrow_end','field_name'=>'归还时间',       'field_directive'=>'datetime',   'field_table_key'=>'archives_borrow','is_system'=>'1','field_options'=>'{"type":"text","format":{"type":"datetime"},"fullRow":2,"fullCell":true}','field_data_type'=>'varchar','field_sort'=>'3','field_list_show'=>'0','field_allow_order'=>'0','mobile_list_field'=>''];

    	DB::table($table)->where('field_table_key','archives_library')->where('is_system',1)->delete();
    	DB::table($table)->where('field_table_key','archives_volume')->where('is_system',1)->delete();
    	DB::table($table)->where('field_table_key','archives_file')->where('is_system',1)->delete();
    	DB::table($table)->where('field_table_key','archives_appraisal')->where('is_system',1)->delete();
    	DB::table($table)->where('field_table_key','archives_borrow')->where('is_system',1)->delete();
    	DB::table($table)->insert($data);
    	if(!Schema::hasTable('archives_hold_time')){
    		Schema::create('archives_hold_time', function ($table) {
    			$table->tinyInteger('hold_time_id')->default(0);
    			$table->string('hold_time_title',255)->default("");
    		});
    		DB::table('archives_hold_time')->insert([['hold_time_id'=>'0','hold_time_title'=>'永久'],['hold_time_id'=>'1','hold_time_title'=>'长期'],['hold_time_id'=>'2','hold_time_title'=>'短期']]);
    	}
    	$tableName = "attachment_relataion_archives_library";
    	$entity_column = "attachments";
    	if(Schema::hasTable($tableName)){
    		if (!Schema::hasColumn($tableName,'entity_column')){
    			Schema::table($tableName,function($table){
    				$table->string('entity_column',50)->comment("管理表对应记录表的字段");
    			});
    		}
    		DB::table($tableName)->where('entity_column','')->update(['entity_column'=>$entity_column]);
    	}
    	$tableName = "attachment_relataion_archives_volume";
    	$entity_column = "attachments";
    	if(Schema::hasTable($tableName)){
    		if (!Schema::hasColumn($tableName,'entity_column')){
    			Schema::table($tableName,function($table){
    				$table->string('entity_column',50)->comment("管理表对应记录表的字段");
    			});
    		}
    		DB::table($tableName)->where('entity_column','')->update(['entity_column'=>$entity_column]);
    	}
    	$tableName = "attachment_relataion_archives_file";
    	$entity_column = "attachments";
    	if(Schema::hasTable($tableName)){
    		if (!Schema::hasColumn($tableName,'entity_column')){
    			Schema::table($tableName,function($table){
    				$table->string('entity_column',50)->comment("管理表对应记录表的字段");
    			});
    		}
    		DB::table($tableName)->where('entity_column','')->update(['entity_column'=>$entity_column]);
    	}
    	$tableName = "archives_appraisal";
    	if(Schema::hasTable($tableName)){
    		if (!Schema::hasColumn($tableName,'updated_at')){
    			Schema::table($tableName,function($table){
    				$table->timestamp('updated_at')->default('0000-00-00 00:00:00');
    			});
    		}
    		if (!Schema::hasColumn($tableName,'created_at')){
    			Schema::table($tableName,function($table){
    				$table->timestamp('created_at')->default('0000-00-00 00:00:00');
    			});
    		}
    	}
    	*/
    }


    // 销毁时删除的相关函数
    private function deleteDestroyedData($ids, $type)
    {
        $where = [
            'destroy_type'      => [$type],
            'destroy_data_id'   => [$ids, 'in']
        ];
        app($this->archivesDestroyRepository)->deleteByWhere($where);
    }

    private function deleteVolumeDestroyed($destroyIds, $userId)
    {
        if (app($this->archivesVolumeRepository)->deleteSoftDelete(['volume_id' => [$destroyIds, 'in']])) {
            app($this->archivesVolumeRepository)->deleteSubVolume($destroyIds);

            $wheres = [
                'borrow_type'   =>  ['volume'],
                'borrow_user_id'=>  [$userId],
                'borrow_data_id'=>  [$destroyIds, 'in'],
            ];
            app($this->archivesBorrowRepository)->deleteByWhere($wheres, false);
            foreach ($destroyIds as $id) {
                $this->saveArchivesLog('archives_volume', $id, 'delete', $userId);
            }
        }
    }

    private function deleteFileDestroyed($destroyIds, $userId, $onlyTrashed = true)
    {
        if (app($this->archivesFileRepository)->deleteSoftDelete(['file_id' => [$destroyIds, 'in']], $onlyTrashed)) {
            app($this->archivesFileRepository)->deleteSubFile($destroyIds, false);

            $wheres = [
                'borrow_type'   =>  ['file'],
                'borrow_user_id'=>  [$userId],
                'borrow_data_id'=>  [$destroyIds, 'in']
            ];
            app($this->archivesBorrowRepository)->deleteByWhere($wheres);
            foreach ($destroyIds as $id) {
                $this->saveArchivesLog('archives_file', $id, 'delete', $userId);
            }
        }
    }
}
