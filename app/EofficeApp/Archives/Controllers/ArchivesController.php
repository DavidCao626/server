<?php

namespace App\EofficeApp\Archives\Controllers;

use Illuminate\Http\Request;
use App\EofficeApp\Base\Controller;
use App\EofficeApp\Archives\Requests\ArchivesRequest;
use App\EofficeApp\Archives\Services\ArchivesService;

/**
 * 档案管理控制器:提供档案管理模块的请求
 *
 * @author qishaobo
 *
 * @since  2015-10-21 创建
 */
class ArchivesController extends Controller
{
    /**
     * 档案管理资源库
     * @var object
     */
    private $ArchivesService;

    public function __construct(
        Request $request,
        // ArchivesRequest $archivesRequest,
        ArchivesService $archivesService
    ) {
        parent::__construct();
        $userInfo = $this->own;
        $this->userId = $userInfo['user_id'];
        $this->request = $request;
        $this->archivesService = $archivesService;
        // $this->formFilter($request, $archivesRequest);
    }

    /**
     * 获取卷库列表
     *
     * @return  array 卷库列表
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    function getIndexArchivesLibrary()
    {
        $result = $this->archivesService->getlibraryList($this->request->all());
        return $this->returnResult($result);
    }

    /**
     * 新建卷库
     *
     * @return  int 新建卷库id
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    function createArchivesLibrary()
    {
        $data = $this->request->all();
        $result = $this->archivesService->createLibrary($data,$this->own);
        return $this->returnResult($result);
    }

    /**
     * 修改卷库数据
     *
     * @param   int   $libraryId 卷库id
     *
     * @return  bool  操作是否成功
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    function editArchivesLibrary($id)
    {
        $result = $this->archivesService->updateLibrary($this->request->all(), $id, $this->userId);
        return $this->returnResult($result);
    }

    /**
     * 删除卷库数据
     *
     * @param   int|string $libraryId   卷库id,多个用逗号隔开
     *
     * @return  bool        操作是否成功
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    function deleteArchivesLibrary($id)
    {
        $result = $this->archivesService->deleteLibrary($id, $this->userId);
        return $this->returnResult($result);
    }

    /**
     * 获取卷库数据详情
     *
     * @param   int    $libraryId   卷库id
     *
     * @return  array  卷库详细数据
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    function getArchivesLibrary($id)
    {
        $result = $this->archivesService->getLibraryDetail($id,$this->userId);
        return $this->returnResult($result);
    }

    /**
     * 获取卷库数据日志
     *
     * @param   int     $libraryId 卷库id
     *
     * @return  array   卷库日志数据
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    function getIndexArchivesLibraryLog($libraryId)
    {
        $result = $this->archivesService->getLibraryLog($this->request->all(), $libraryId);
        return $this->returnResult($result);
    }

    /**
     * 获取案卷数据
     *
     * @return array 案卷数据
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    function getIndexArchivesVolume()
    {
        $result = $this->archivesService->getVolumeList($this->request->all(),$this->own);
        return $this->returnResult($result);
    }

    /**
     * 新建案卷
     *
     * @return int 新建案卷id
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    function createArchivesVolume()
    {
        $data = $this->request->all();
        $result = $this->archivesService->createVolume($data,$this->own);
        return $this->returnResult($result);
    }

    /**
     * 修改案卷数据
     *
     * @param   int   $volumeId 案卷id
     *
     * @return  bool  操作是否成功
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    function editArchivesVolume($id)
    {
        $result = $this->archivesService->updateVolume($this->request->all(), $id, $this->userId);
        return $this->returnResult($result);
    }

    /**
     * 删除案卷数据
     *
     * @param   int|array   $volumeId 案卷id,多个用逗号隔开
     *
     * @return  bool        操作是否成功
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    function deleteArchivesVolume($id)
    {
        $result = $this->archivesService->deleteVolume($id, $this->request->input('reason'), $this->userId);
        return $this->returnResult($result);
    }

    /**
     * 获取案卷详情
     *
     * @param   int     $volumeId 案卷id
     *
     * @return  array   案卷详情
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    function getArchivesVolume($id)
    {
        $result = $this->archivesService->getVolumeDetail($id, $this->userId);
        return $this->returnResult($result);
    }

    /**
     * 获取案卷详情
     *
     * @param   int     $volumeId 案卷id
     *
     * @return  array   案卷详情
     *
     * @author qishaobo
     *
     * @since  2015-12-10
     */
    function sealUpVolume($volumeId)
    {
        $result = $this->archivesService->sealUpOrSealOffVolume($volumeId, 2, $this->userId);
        return $this->returnResult($result);
    }

    /**
     * 获取案卷详情
     *
     * @param   int     $volumeId 案卷id
     *
     * @return  array   案卷详情
     *
     * @author qishaobo
     *
     * @since  2015-12-10
     */
    function sealOffVolume($volumeId)
    {
        $result = $this->archivesService->sealUpOrSealOffVolume($volumeId, 1, $this->userId);
        return $this->returnResult($result);
    }

    /**
     * 获取案卷数据日志
     *
     * @param   int     $volumeId 案卷id
     *
     * @return  array   日志数据
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    function getIndexArchivesVolumeLog($volumeId)
    {
        $result = $this->archivesService->getVolumeLog($this->request->all(), $volumeId);
        return $this->returnResult($result);
    }

    /**
     * 获取案卷包含的文件
     *
     * @param   int     $volumeId 案卷id
     *
     * @return  array   日志数据
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    function getIndexArchivesVolumeFiles($volumeId)
    {
        $result = $this->archivesService->getVolumeFiles($volumeId);
        return $this->returnResult($result);
    }

    /**
     * 获取档案文件
     *
     * @return array 档案文件数据
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    function getIndexArchivesFile()
    {
        $result = $this->archivesService->getFileList($this->request->all(),$this->own);
        return $this->returnResult($result);
    }

    /**
     * 新建档案文件
     *
     * @return int 文件id
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    function createArchivesFile()
    {
        $data = $this->request->all();
        $result = $this->archivesService->createFile($data,$this->own);
        return $this->returnResult($result);
    }

    /**
     * 修改档案文件
     *
     * @param   int  $fileId     文件id
     *
     * @return  bool 操作是否成功
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    function editArchivesFile($id)
    {
        $result = $this->archivesService->updateFile($this->request->all(), $id, $this->userId);
        return $this->returnResult($result);
    }

    /**
     * 删除档案文件
     *
     * @param   int   $fileId     文件id,多个用逗号隔开
     *
     * @return  bool  操作是否成功
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    function deleteArchivesFile($id)
    {
        $result = $this->archivesService->deleteFile($id, $this->request->input('reason'), $this->userId);
        return $this->returnResult($result);
    }

    /**
     * 获取档案文件详情
     *
     * @param   int     $fileId  文件id
     *
     * @return  array   文件详情
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    function getArchivesFile($id)
    {
        $result = $this->archivesService->getFileDetail($id, $this->userId);
        return $this->returnResult($result);
    }

    /**
     * 获取档案文件日志
     *
     * @param  int    $fileId 文件id
     *
     * @return array  日志数据
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    function getIndexArchivesFileLog($fileId)
    {
        $result = $this->archivesService->getFileLog($this->request->all(), $fileId);
        return $this->returnResult($result);
    }

    /**
     * 借阅申请列表
     *
     * @return array 借阅列表数据
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    function getIndexArchivesBorrowApply()
    {
        $result = $this->archivesService->getBorrowApplyList($this->request->all(), $this->own);
        return $this->returnResult($result);
    }

    /**
     * 新建档案借阅
     *
     * @return int 档案借阅id
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    function createArchivesBorrow()
    {
        $data = $this->request->all();
        $result = $this->archivesService->createBorrow($data, $this->own);
        return $this->returnResult($result);
    }

    /**
     * 获档案借阅记录列表
     *
     * @return array 借阅列表数据
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    function getIndexArchivesBorrow()
    {
        $result = $this->archivesService->getBorrowedList($this->request->all(),$this->own);
        return $this->returnResult($result);
    }

    /**
     * 获取我的档案借阅记录列表
     *
     * @return array 借阅列表数据
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    function getIndexArchivesMyBorrow()
    {
        $result = $this->archivesService->getMyBorrowList($this->request->all(), $this->userId);
        return $this->returnResult($result);
    }

    /**
     * 删除我的档案借阅记录列表或档案审批
     *
     * @param  int|string  $borrowId  借阅档案id
     *
     * @return array       借阅列表数据
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    function deleteArchivesMyBorrow($borrowId)
    {
        $result = $this->archivesService->deleteMyBorrow($borrowId, $this->userId);
        return $this->returnResult($result);
    }

    /**
     * 获取我的档案借阅记录列表
     *
     * @param  int    $borrowId  借阅档案id
     *
     * @return array  借阅列表数据
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    function getArchivesMyBorrow($id)
    {
        $result = $this->archivesService->getMyBorrowDetail($id, $this->own);
        return $this->returnResult($result);
    }

    /**
     * 删除档案借阅
     *
     * @param  int   $borrowId 借阅id
     *
     * @return bool  操作是否成功
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    function deleteArchivesBorrow($id)
    {
        $params = $this->request->all();
        $result = $this->archivesService->deleteBorrowed($id, $params);
        return $this->returnResult($result);
    }

    /**
     * 修改档案借阅
     *
     * @param  int   $fieldsId 借阅id
     *
     * @return bool  操作是否成功
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    function editArchivesBorrow($id)
    {
        $result = $this->archivesService->updateBorrow($id, $this->request->all(),$this->own);
        return $this->returnResult($result);
    }

    /**
     * 获取审批档案借阅列表
     *
     * @return array  借阅列表数据
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    function getIndexArchivesBorrowApprove()
    {
        $result = $this->archivesService->getBorrowApproveList($this->request->all());
        return $this->returnResult($result);
    }

    /**
     * 修改档案借阅
     *
     * @param  int   $fieldsId 借阅id
     *
     * @return bool  操作是否成功
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    function editArchivesBorrowApprove($borrowId)
    {
        $result = $this->archivesService->updateBorrowApprove($borrowId, $this->request->all(), $this->userId);
        return $this->returnResult($result);
    }

    /**
     * 获取档案借阅详情
     *
     * @param  int    $borrowId 借阅id
     *
     * @return array  借阅详情
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    function getArchivesBorrow($borrowId)
    {
        $result = $this->archivesService->getborrowDetail($borrowId);
        return $this->returnResult($result);
    }

    /**
     * 获取档案鉴定列表
     *
     * @return array 销毁列表
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    function getIndexArchivesAppraisal()
    {
        $result = $this->archivesService->getAppraisalList($this->request->all());
        return $this->returnResult($result);
    }

    /**
     * 提交档案鉴定
     *
     * @return bool 操作是否成功
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    function createArchivesAppraisal()
    {
        $data = $this->request->all();
        $result = $this->archivesService->createAppraisal($data);
        return $this->returnResult($result);
    }

    /**
     * 获取档案鉴定详情
     *
     * @param  int   $appraisalDataId 档案或文件id
     *
     * @return array 档案销毁
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    function getArchivesAppraisal($id)
    {
        $result = $this->archivesService->getAppraisalDetail($this->request->input('type'), $id);
        return $this->returnResult($result);
    }

    /**
     * 获取档案销毁列表
     *
     * @return array 销毁列表
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    function getIndexArchivesDestroy()
    {
        $result = $this->archivesService->getDestroyedList($this->request->all());
        return $this->returnResult($result);
    }

    /**
     * 恢复档案销毁
     *
     * @param  int|string  $destroyId 档案或文件id,多个用逗号隔开
     *
     * @return bool        操作是否成功
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    function editArchivesDestroy($id)
    {
        $result = $this->archivesService->restoreDestroyed($this->request->input('type'), $id, $this->userId);
        return $this->returnResult($result);
    }

    /**
     * 删除档案销毁
     *
     * @param  string $destroyId 档案或文件id,多个用逗号隔开
     *
     * @return bool   操作是否成功
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    function deleteArchivesDestroy($id)
    {
        $result = $this->archivesService->deleteDestroyed($this->request->input('type'), $id, $this->userId);
        return $this->returnResult($result);
    }

    /**
     * 获取档案销毁详情
     *
     * @param  int    $destroyId 档案或文件id
     *
     * @return array 档案销毁
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    function getArchivesDestroy($id)
    {
        $result = $this->archivesService->getDestroyedDetail($this->request->input('type'), $id);
        return $this->returnResult($result);
    }

    /**
     * 删除审批
     *
     * @param  string $destroyId 档案或文件id,多个用逗号隔开
     *
     * @return bool   操作是否成功
     *
     * @author qishaobo
     *
     * @since  2015-10-21
     */
    function deleteAudit($id)
    {
        $result = $this->archivesService->deleteAudit($id);
        return $this->returnResult($result);
    }

    /**
     * 访问不存在方法处理
     *
     * @return string 提示信息
     *
     * @author: qishaobo
     *
     * @since：2015-10-21
     */
    public function __call($name, $param)
    {
        return 'function '.$name.' not exist';
    }

    function getIndexHoldTime()
    {
    	$result = $this->archivesService->getIndexHoldTime($this->request->all());
    	return $this->returnResult($result);
    }

   
    public function getArchivesName($borrow_data_id,$borrow_type){
        if($borrow_data_id && $borrow_type){
            $result['file_name'] = $this->archivesService->getFileName($borrow_data_id,$borrow_type);
        }
        return $this->returnResult($result);
    }

    //获取卷库列表（选择器专用）
    function getChoiceLibrary()
    {
        $result = $this->archivesService->getChoiceLibraryList($this->request->all());
        return $this->returnResult($result);
    }

    //获取案卷列表（选择器专用）
    function getChoiceVolume()
    {
        $result = $this->archivesService->getChoiceVolumeList($this->request->all());
        return $this->returnResult($result);
    }
}
