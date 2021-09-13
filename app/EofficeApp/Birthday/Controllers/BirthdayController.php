<?php
namespace App\EofficeApp\Birthday\Controllers;

use Illuminate\Http\Request;
use App\EofficeApp\Base\Controller;
use App\EofficeApp\Birthday\Requests\BirthdayRequest;
use App\EofficeApp\Birthday\Services\BirthdayService;

/**
 * 生日贺卡控制
 *
 * @author: 喻威
 *
 * @since：2015-10-19
 *
 */
class BirthdayController extends Controller {

    public function __construct(
       Request $request,
       BirthdayService $birthdayService,
       BirthdayRequest $birthdayRequest
    ) {
        parent::__construct();
        $this->birthdayService = $birthdayService;
        $this->birthdayRequest = $request;
        $this->formFilter($request, $birthdayRequest);
    }


    /**
     * 获取生日贺卡的列表
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function getBirthdayList() {
        $result = $this->birthdayService->getBirthdayList($this->birthdayRequest->all());
        return $this->returnResult($result);
    }

    /**
     * 增加生日贺卡
     *
     * @return int 自增ID
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function addBirthday() {
        $result = $this->birthdayService->addBirthday($this->birthdayRequest->all());
        return $this->returnResult($result);
    }

    /**
     * 编辑生日贺卡
     *
     * @return bool
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function editBirthday() {
        $result = $this->birthdayService->editBirthday($this->birthdayRequest->all());
        return $this->returnResult($result);
    }

    /**
     * 删除生日贺卡
     *
     * @return bool
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function deleteBirthday() {
        $result = $this->birthdayService->deleteBirthday($this->birthdayRequest->all());
        return $this->returnResult($result);
    }

    /**
     * 获取贺卡设置的详细
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-22
     */
     public function getOneBrithday(){
         $result = $this->birthdayService->getOneBrithday($this->birthdayRequest->all());
         return $this->returnResult($result);
     }

     /**
     * 选用贺卡为默认贺卡
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-22
     */
     public function selectBrithday(){
         $result = $this->birthdayService->selectBrithday($this->birthdayRequest->all());
         return $this->returnResult($result);
     }
     /**
     * 取消选用贺卡
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-22
     */
     public function cancelSelectBrithday(){
         $result = $this->birthdayService->cancelSelectBrithday($this->birthdayRequest->all());
         return $this->returnResult($result);
     }
     /**
     * 获取当天生日的用户
     *
     * @return array
     *
     * @author 李旭
     *
     * @since 2019-09-23
     */
     public function getBirthdayUser(){
         $result = $this->birthdayService->getBirthdayUser($this->birthdayRequest->all());
         return $this->returnResult($result);
     }
     /**
     * 生日提醒设置
     *
     * @return array
     *
     * @author 李旭
     *
     * @since 2019-09-23
     */
     public function birthdaySet(){
         $result = $this->birthdayService->birthdaySet($this->birthdayRequest->all());
         return $this->returnResult($result);
     }
     public function birthdaySetGet() {
        $result = $this->birthdayService->birthdaySetGet($this->birthdayRequest->all());
         return $this->returnResult($result);
     }

}
