<?php

namespace App\EofficeApp\OfficeSupplies\Controllers;

use Illuminate\Http\Request;
use App\EofficeApp\Base\Controller;
use App\EofficeApp\OfficeSupplies\Services\OfficeSuppliesService;
use App\EofficeApp\OfficeSupplies\Requests\OfficeSuppliesRequest;

/**
 * 办公用品模块控制器
 *
 * @author  朱从玺
 *
 * @since   2015-11-03
 *
 */
class OfficeSuppliesController extends Controller
{
	/**
     * [$request Request验证]
     *
     * @var [object]
     */
    protected $request;

    /**
     * [$officeSuppliesService 办公用品Service]
     *
     * @var [object]
     */
    protected $officeSuppliesService;

    /**
     * [$officeSuppliesRequest 表单验证]
     *
     * @var [object]
     */
    protected $officeSuppliesRequest;

    public function __construct(
        OfficeSuppliesService $officeSuppliesService,
        OfficeSuppliesRequest $officeSuppliesRequest,
        Request $request
    )
    {
        parent::__construct();

        $this->officeSuppliesService = $officeSuppliesService;
        $this->officeSuppliesRequest = $officeSuppliesRequest;
        $this->request = $request;

        $this->formFilter($request, $officeSuppliesRequest);
    }

    /**
     * [createOfficeSuppliesType 创建办公用品类型]
     *
     * @author 朱从玺
     *
     * @since  2015-11-03 创建
     *
     * @return [json]                   [创建结果]
     */
    public function createOfficeSuppliesType()
    {
    	$typeData = $this->request->all();

    	$result = $this->officeSuppliesService->createOfficeSuppliesType($typeData);

    	return $this->returnResult($result);
    }

    /**
     * [modifyOfficeSuppliesType 编辑办公用品类型]
     *
     * @author 朱从玺
     *
     * @param  [int]                    $typeId [类型ID]
     *
     * @since  2015-11-05 创建
     *
     * @return [json]                           [编辑结果]
     */
    public function modifyOfficeSuppliesType($typeId)
    {
    	$newTypeData = $this->request->all();

    	$result = $this->officeSuppliesService->modifyOfficeSuppliesType($typeId, $newTypeData);

    	return $this->returnResult($result);
    }

    /**
     * [getOfficeSuppliesType 获取办公用品类型数据]
     *
     * @author 朱从玺
     *
     * @param  [int]                 $typeId [类型ID]
     *
     * @since  2015-11-05 创建
     *
     * @return [json]                        [查询结果]
     */
    public function getOfficeSuppliesType($typeId)
    {
    	$typeData = $this->officeSuppliesService->getOfficeSuppliesType($typeId);

    	return $this->returnResult($typeData);
    }

    /**
     * [getOfficeSuppliesTypeList 获取办公用品类型列表]
     *
     * @author 朱从玺
     *
     * @since  2015-11-05 创建
     *
     * @return [json]                    [查询结果]
     */
    public function getOfficeSuppliesTypeList()
    {
        $params = $this->request->all();
        $params['own'] = $this->own;

        $typeList = $this->officeSuppliesService->getOfficeSuppliesTypeList($params);

        return $this->returnResult($typeList);
    }
    public function getPermissionOfficeSuppliesTypeList()
    {
        $params = $this->request->all();
        $params['own'] = $this->own;

    	$typeList = $this->officeSuppliesService->getPermissionOfficeSuppliesTypeList($params);

    	return $this->returnResult($typeList);
    }

    /**
     * [getOfficeSuppliesSecondTypeList 获取二级分类列表]
     *
     * @author
     *
     * @since 2018-09-20创建
     *
     * @return [json]                    [查询结果]
     */
    public function getOfficeSuppliesSecondTypeList($typeFrom)
    {
        $params = $this->request->all();
        $typeList = $this->officeSuppliesService->getOfficeSuppliesSecondTypeList($params,$this->own,$typeFrom);

        return $this->returnResult($typeList);
    }

    /**
     * [getOfficeSuppliesAllSecondTypeList 获取全部二级分类列表]
     *
     * @author
     *
     * @since 2018-09-20创建
     *
     * @return [json]                    [查询结果]
     */
    public function getOfficeSuppliesAllSecondTypeList()
    {
        $params = $this->request->all();
        $typeList = $this->officeSuppliesService->getOfficeSuppliesSecondTypeList($params,$this->own);

        return $this->returnResult($typeList);
    }

    /**
     * [getOfficeSuppliesAllTypeList 获取全部分类列表]
     *
     * @author
     *
     * @since 2018-09-20创建
     *
     * @return [json]                    [查询结果]
     */
    public function getOfficeSuppliesAllTypeList()
    {
        $params = $this->request->all();
        $params['own'] = $this->own;

        $typeList = $this->officeSuppliesService->getOfficeSuppliesAllTypeList($params);

        return $this->returnResult($typeList);
    }
    /**
     * [getOfficeSuppliesTypeParentList 获取办公用品父级类型列表]
     *
     * @author jason
     *
     * @since  2018-08-29 创建
     *
     * @return [json]                    [查询结果]
     */
    public function getOfficeSuppliesTypeParentList()
    {
        $params = $this->request->all();

        $typeList = $this->officeSuppliesService->getOfficeSuppliesTypeParentList($params);

        return $this->returnResult($typeList);
    }

    /**
     * [deleteOfficeSuppliesType 删除办公用品类型数据]
     *
     * @author 朱从玺
     *
     * @param  [int]                 $typeId [类型ID]
     *
     * @since  2015-11-05 创建
     *
     * @return [json]                        [删除结果]
     */
    public function deleteOfficeSuppliesType($typeId)
    {
    	$result = $this->officeSuppliesService->deleteOfficeSuppliesType($typeId);

    	return $this->returnResult($result);
    }

    /**
     * [createOfficeSupplies 创建办公用品]
     *
     * @author 朱从玺
     *
     * @since  2015-11-05 创建
     *
     * @return [json]               [创建结果]
     */
    public function createOfficeSupplies()
    {
    	$data = $this->request->all();

    	$result = $this->officeSuppliesService->createOfficeSupplies($data);

    	return $this->returnResult($result);
    }

    /**
     * [modifyOfficeSupplies 编辑办公用品]
     *
     * @author 朱从玺
     *
     * @param  [int]                $officeSuppliesId [办公用品ID]
     *
     * @since  2015-11-05 创建
     *
     * @return [json]                                 [编辑结果]
     */
    public function modifyOfficeSupplies($officeSuppliesId)
    {
        $newData = $this->request->all();

        $result = $this->officeSuppliesService->modifyOfficeSupplies($officeSuppliesId, $newData);

        return $this->returnResult($result);
    }

    /**
     * [getOfficeSupplies 获取办公用品数据]
     *
     * @author 朱从玺
     *
     * @param  [int]             $officeSuppliesId [办公用品ID]
     *
     * @since  2015-11-05 创建
     *
     * @return [json]                              [查询结果]
     */
    public function getOfficeSupplies($officeSuppliesId)
    {
        $officeSupplies = $this->officeSuppliesService->getOfficeSupplies($officeSuppliesId);

        return $this->returnResult($officeSupplies);
    }

    /**
     * [getAllOfficeSuppliesList 获取全部办公用品列表]
     *
     * @author 朱从玺
     *
     * @since  2015-11-05 创建
     *
     * @return [json]                [查询结果]
     */
    public function getAllOfficeSuppliesList()
    {
        $param = $this->request->all();
        $list = $this->officeSuppliesService->getOfficeSuppliesList($param,$this->own);

        return $this->returnResult($list);
    }

    /**
     * [getOfficeSuppliesList 获取办公用品列表]
     *
     * @author 朱从玺
     *
     * @since  2015-11-05 创建
     *
     * @return [json]                [查询结果]
     */
    public function getOfficeSuppliesList($typeFrom)
    {
        $param = $this->request->all();
        $list = $this->officeSuppliesService->getOfficeSuppliesList($param,$this->own,$typeFrom);

        return $this->returnResult($list);
    }

    /**
     * [deleteOfficeSupplies 删除办公用品]
     *
     * @author 朱从玺
     *
     * @param  [int]                $officeSuppliesId [办公用品ID]
     *
     * @since  2015-11-05 创建
     *
     * @return [json]                                 [删除结果]
     */
    public function deleteOfficeSupplies($officeSuppliesId)
    {
        $result = $this->officeSuppliesService->deleteOfficeSupplies($officeSuppliesId);

        return $this->returnResult($result);
    }

    /**
     * [createStorageRecord 创建入库记录]
     *
     * @author 朱从玺
     *
     * @since  2015-11-05 创建
     *
     * @return [json]              [创建结果]
     */
    public function createStorageRecord()
    {
        $storageRecord = $this->request->all();
        $result = $this->officeSuppliesService->createStorageRecord($storageRecord);

        return $this->returnResult($result);
    }

    public function createQuickStorageRecord()
    {
        $storageRecord = $this->request->all();
        $storageRecord['operator'] = $this->own['user_id'];
        $result = $this->officeSuppliesService->createStorageRecord($storageRecord);

        return $this->returnResult($result);
    }

    /**
     * [getStorageRecord 获取入库记录]
     *
     * @author 朱从玺
     *
     * @param  [int]            $storageId [入库记录ID]
     *
     * @since  2015-11-05 创建
     *
     * @return [json]                      [查询结果]
     */
    public function getStorageRecord($storageId)
    {
        $result = $this->officeSuppliesService->getStorageRecord($storageId, $this->own);

        return $this->returnResult($result);
    }

    /**
     * [getStorageList 获取入库记录列表]
     *
     * @author 朱从玺
     *
     * @since  2015-11-05 创建
     *
     * @return [json]         [查询结果]
     */
    public function getStorageList()
    {
        $param = $this->request->all();

        $result = $this->officeSuppliesService->getStorageList($param, $this->own);

        return $this->returnResult($result);
    }

    /**
     * [deleteStorageRecord 删除用户记录]
     *
     * @author 朱从玺
     *
     * @param  [int]               $storageId [入库记录ID]
     *
     * @since  2015-11-05 创建
     *
     * @return [json]                         [删除结果s]
     */
    public function deleteStorageRecord($storageId)
    {
        $result = $this->officeSuppliesService->deleteStorageRecord($storageId);

        return $this->returnResult($result);
    }

    /**
     * [emptySuppliesData 清空办公用品数据]
     *
     * @author 朱从玺
     *
     * @return [json]            [清空结果]
     */
    public function emptySuppliesData()
    {
        $result = $this->officeSuppliesService->emptySuppliesData();

        return $this->returnResult($result);
    }

    /**
     * [createApplyRecord 创建申请记录]
     *
     * @author 朱从玺
     *
     * @since  2015-11-05 创建
     *
     * @return [json]            [创建结果]
     */
    public function createApplyRecord()
    {
        $applyData = $this->request->all();

        $result = $this->officeSuppliesService->createApplyRecord($applyData, $this->own);

        return $this->returnResult($result);
    }

    /**
     * 批量创建申请记录
     * @return array
     * @creatTime 2021/1/15 17:55
     * @author [dosy]
     */
    public function batchCreateApplyRecord()
    {
        $applyData = $this->request->all();

        $result = $this->officeSuppliesService->batchCreateApplyRecord($applyData, $this->own);

        return $this->returnResult($result);
    }

    /**
     * [getApplyRecord 获取申请记录]
     *
     * @author 朱从玺
     *
     * @param  [int]         $applyId [申请ID]
     *
     * @since  2015-11-05 创建
     *
     * @return [json]                  [查询结果]
     */
    public function getApplyRecord($applyId)
    {
        $applyData = $this->officeSuppliesService->getApplyRecord($applyId, $this->own);

        return $this->returnResult($applyData);
    }

    /**
     * [getApplyList 获取申请列表]
     *
     * @author 朱从玺
     *
     * @since  2015-11-05 创建
     *
     * @return [json]       [查询结果]
     */
    public function getApplyList()
    {
        $param = $this->request->all();
        $param['own'] = $this->own;

        $applyList = $this->officeSuppliesService->getApplyList($param);

        return $this->returnResult($applyList);
    }

    public function getMyApplyList()
    {
        $param = $this->request->all();
        $param['own'] = $this->own;
        $param['apply_user'] = $this->own['user_id'];

        $applyList = $this->officeSuppliesService->getApplyList($param);

        return $this->returnResult($applyList);
    }

    /**
     * [deleteApplyRecord 删除申请记录]
     *
     * @author 朱从玺
     *
     * @param  [int]             $applyId [申请ID]
     *
     * @since  2015-11-05 创建
     *
     * @return [json]                     [删除结果]
     */
    public function deleteApplyRecord($applyId)
    {
        $result = $this->officeSuppliesService->deleteApplyRecord($applyId);

        return $this->returnResult($result);
    }

    /**
     * [modifyApplyRecord 审批申请]
     *
     * @author 朱从玺
     *
     * @param  [int]        $applyId [申请ID]
     *
     * @since  2015-11-05 创建
     *
     * @return [json]                [审批结果]
     */
    public function modifyApplyRecord($applyId)
    {
        $operationData = $this->request->all();

        $result = $this->officeSuppliesService->modifyApplyRecord($applyId, $operationData, $this->own['user_id']);

        return $this->returnResult($result);
    }

    /**
     * [getApplyApprovalList 获取申请审批列表]
     *
     * @author 朱从玺
     *
     * @since  2015-11-05 创建
     *
     * @return [json]               [查询结果]
     */
    public function getApplyApprovalList()
    {
        $param = $this->request->all();

        $approvalList = $this->officeSuppliesService->getApplyApprovalList($param);

        return $this->returnResult($approvalList);
    }

    /**
     * [getPurchaseList 获取采购列表]
     *
     * @author 朱从玺
     *
     * @since  2015-11-05 创建
     *
     * @return [json]          [查询结果]
     */
    public function getPurchaseList()
    {
        $param = $this->request->all();

        $purchaseList = $this->officeSuppliesService->getPurchaseList($param);

        return $this->returnResult($purchaseList);
    }

    /**
     * [getCreateNo 获取入库编号]
     *
     * @author 朱从玺
     *
     * @since  2015-11-05 创建
     *
     * @return [json]              [查询结果]
     */
    public function getCreateNo()
    {
        $param = $this->request->all();

        $storageNo = $this->officeSuppliesService->getCreateNo($param);

        return $this->returnResult($storageNo);
    }

    /**
     * [getSuppliesAllNormalList 获取全部正常办公用品列表/即没有type]
     *
     * @method 朱从玺
     *
     * @return [json]                [查询结果]
     */
    public function getSuppliesAllNormalList()
    {
        $params = $this->request->all();
        $params['own'] = $this->own;

        $result = $this->officeSuppliesService->getSuppliesNormalList($params,$this->own);

        return $this->returnResult($result);
    }

    /**
     * [getSuppliesNormalList 获取有权限的办公用品列表/即没有type]
     *
     * @method lixx
     *
     * @return [json]                [查询结果]
     */
    public function getSuppliesNormalList($typeFrom)
    {
        $params = $this->request->all();

        $result = $this->officeSuppliesService->getSuppliesNormalList($params,$this->own,$typeFrom);

        return $this->returnResult($result);
    }

    /**
     * [getPurchaseDetailById 获取采购详情]
     *
     * @author miaochenchen
     *
     * @since  2016-11-01 创建
     *
     * @return [json]          [查询结果]
     */
    public function getPurchaseDetailById($id)
    {
        $result = $this->officeSuppliesService->getPurchaseDetailById($id);

        return $this->returnResult($result);
    }
}
