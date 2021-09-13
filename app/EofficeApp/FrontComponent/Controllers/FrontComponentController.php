<?php

namespace App\EofficeApp\FrontComponent\Controllers;

use App\EofficeApp\Base\Controller;
use Illuminate\Http\Request;
use App\EofficeApp\FrontComponent\Requests\FrontComponentRequest;
/**
 * 组建查询控制
 *
 * @author: 喻威
 *
 * @since：2015-10-19
 *
 */
class FrontComponentController extends Controller {

    public function __construct(
        Request $request,
        FrontComponentRequest $frontComponentRequest
    ) {
        parent::__construct();
        $this->frontComponentService = 'App\EofficeApp\FrontComponent\Services\FrontComponentService';
        $this->formFilter($request, $frontComponentRequest);
        $this->request = $request;
    }

    /**
     * 获取组建查询的列表
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function getComponentSearchlist() {
        $result = app($this->frontComponentService)->getComponentSearchlist($this->request->all());
        return $this->returnResult($result);
    }

    /**
     * 增加组建查询
     *
     * @return int 自增ID
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function addComponentSearch() {
        $result = app($this->frontComponentService)->addComponentSearch($this->request->all());
        return $this->returnResult($result);
    }

    /**
     * 删除组建查询
     *
     * @return bool
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function deleteComponentSearch($id) {
        $result = app($this->frontComponentService)->deleteComponentSearch($this->request->all(), $id);
        return $this->returnResult($result);
    }

    /**
     * 获取组建查询的详细
     *
     * @return array
     *
     * @author 喻威
     *
     * @since 2015-10-22
     */
    public function getOneComponentSearch() {
        $result = app($this->frontComponentService)->getOneComponentSearch($this->request->all());
        return $this->returnResult($result[0]);
    }

    /**
     * 组建查询
     *
     * @return bool
     *
     * @author 喻威
     *
     * @since 2015-10-21
     */
    public function editComponentSearch($id) {

        $result = app($this->frontComponentService)->editComponentSearch($this->request->all(), $id);
        return $this->returnResult($result);
    }
    /**
     * 获取grid设置
     */
    public function getWebGridSet($key)
    {
        $result = app($this->frontComponentService)->getWebGridSet($key);

        return $this->returnResult($result);
    }
    /**
     * 保存grid设置
     */
    public function saveWebGridSet()
    {
        $result = app($this->frontComponentService)->saveWebGridSet($this->request->all());

        return $this->returnResult($result);
    }
    /**
     * 添加系统数据
     *
     */
    public function addCustomizeSelector()
    {
        $result = app($this->frontComponentService)->addCustomizeSelector($this->request->all());

        return $this->returnResult($result);
    }
    /**
     * 编辑系统数据
     *
     */
    public function editCustomizeSelector($id)
    {
        $result = app($this->frontComponentService)->editCustomizeSelector($id,$this->request->all());
        return $this->returnResult($result);
    }

    /**
     * 删除系统数据
     *
     */
    public function deleteCustomizeSelector($id)
    {
        $result = app($this->frontComponentService)->deleteCustomizeSelector($id);
        return $this->returnResult($result);
    }
    /**
     * 获取系统数据
     *
     */
    public function getListCustomizeSelector()
    {
        $result = app($this->frontComponentService)->getListCustomizeSelector($this->request->all());
        return $this->returnResult($result);
    }
    /**
     * 获取系统数据
     *
     */
    public function getOneCustomizeSelector($identifier)
    {
        $result = app($this->frontComponentService)->getOneCustomizeSelector($identifier,$this->request->all());
        return $this->returnResult($result);
    }
}
