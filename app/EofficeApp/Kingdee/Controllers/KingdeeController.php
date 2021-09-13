<?php
namespace App\EofficeApp\Kingdee\Controllers;

use App\EofficeApp\Base\Controller;
use Illuminate\Http\Request;

/**
 * 金蝶k3集成 controller
 *
 * @author wwf
 *
 */
class KingdeeController extends Controller
{
    private $kingdeeService;

    public function __construct(
        Request $request
    ) {
        parent::__construct();
        $userInfo = $this->own;
        // 用户id
        $this->userId               = $userInfo['user_id'];
        $this->kingdeeService = 'App\EofficeApp\Kingdee\Services\KingdeeService';
        $this->K3CloudApiService = 'App\EofficeApp\Kingdee\Services\K3CloudApiService';
        $this->request              = $request;
    }

    /**
     * 新增金蝶k3账套
     * @return [object] [description]
     */
    public function addK3Account()
    {
        $data = $this->request->all();
        return $this->returnResult(app($this->kingdeeService)->addK3Account($data));
    }
    /**
     * 获取金蝶k3账套列表
     * @return [object] [description]
     */
    public function getK3AccountList()
    {
        $data = $this->request->all();
        return $this->returnResult(app($this->kingdeeService)->getK3AccountList($data));
    }
    /**
     * 获取单个金蝶k3账套信息
     * @return [object] [description]
     */
    public function getK3AccountDetail()
    {
        $data = $this->request->all();
        return $this->returnResult(app($this->kingdeeService)->getK3AccountDetail($data));
    }
    /**
     * 删除单个金蝶k3账套信息
     * @return [object] [description]
     */
    public function deleteK3Account()
    {
        $data = $this->request->all();
        return $this->returnResult(app($this->kingdeeService)->deleteK3Account($data));
    }
    /**
     * 更新单个金蝶k3账套信息
     * @return [object] [description]
     */
    public function updateK3Account()
    {
        $data = $this->request->all();
        return $this->returnResult(app($this->kingdeeService)->updateK3Account($data));
    }
    /**
     * 校验金蝶k3账套信息是否正确
     * @return [object] [description]
     */
    public function checkConfig()
    {
        $data = $this->request->all();
        return $this->returnResult(app($this->K3CloudApiService)->checkConfig($data));
    }
    /**
     * 新增金蝶k3单据
     * @return [object] [description]
     */
    public function addK3Table()
    {
        $data = $this->request->all();
        return $this->returnResult(app($this->kingdeeService)->addK3Table($data));
    }
    /**
     * 删除单个金蝶k3单据
     * @return [object] [description]
     */
    public function deleteK3Table()
    {
        $data = $this->request->all();
        return $this->returnResult(app($this->kingdeeService)->deleteK3Table($data));
    }
    /**
     * 获取单个金蝶k3单据
     * @return [object] [description]
     */
    public function getK3TableDetail()
    {
        $data = $this->request->all();
        return $this->returnResult(app($this->kingdeeService)->getK3TableDetail($data));
    }
    /**
     * 更新单个金蝶k3单据
     * @return [object] [description]
     */
    public function updateK3TableDetail()
    {
        $data = $this->request->all();
        return $this->returnResult(app($this->kingdeeService)->updateK3TableDetail($data));
    }
    /**
     * 获取金蝶k3单据列表
     * @return [object] [description]
     */
    public function getK3TableList()
    {
        $data = $this->request->all();
        return $this->returnResult(app($this->kingdeeService)->getK3TableList($data));
    }

    /**
     * 根据单据id获取账套信息
     * @return [object] [description]
     */
    public function getK3AccountByTable()
    {
        $data = $this->request->all();
        return $this->returnResult(app($this->kingdeeService)->getK3AccountByTable($data));
    }

    /**
     * 根据单据获取字段列表
     * @return [object] [description]
     */
    public function getK3TableField()
    {
        $data = $this->request->all();
        return $this->returnResult(app($this->kingdeeService)->getK3TableField($data));
    }

    /**
     * 新增k3单据与流程关联信息
     * @return [object] [description]
     */
    public function addK3TableFlow()
    {
        $data = $this->request->all();
        return $this->returnResult(app($this->kingdeeService)->addK3TableFlow($data));
    }


    /**
     * 获取k3单据与流程关联信息
     * @return [object] [description]
     */
    public function getK3TableFlow()
    {
        $data = $this->request->all();
        return $this->returnResult(app($this->kingdeeService)->getK3TableFlow($data));
    }


    /**
     * 更新k3单据与流程关联信息
     * @return [object] [description]
     */
    public function updateK3TableFlow()
    {
        $data = $this->request->all();
        return $this->returnResult(app($this->kingdeeService)->updateK3TableFlow($data));
    }


    /**
     * 获取k3单据与流程关联列表
     * @return [object] [description]
     */
    public function getK3TableFlowList()
    {
        $data = $this->request->all();
        return $this->returnResult(app($this->kingdeeService)->getK3TableFlowList($data));
    }

    /**
     * 删除k3单据与流程关联信息
     * @return [object] [description]
     */
    public function deleteK3Flow()
    {
        $data = $this->request->all();
        return $this->returnResult(app($this->kingdeeService)->deleteK3Flow($data));
    }

    /**
     * 删除k3单据与流程关联信息
     * @return [object] [description]
     */
    public function outer()
    {
        $data = $this->request->all();
        return $this->returnResult(app($this->kingdeeService)->k3OutSend($data,$data));
    }


    /**
     * k3单据外发选择器接口
     * @return [object] [description]
     */
    public function getK3TableSelectByFlow()
    {
        $data = $this->request->all();
        return $this->returnResult(app($this->kingdeeService)->getK3TableSelectByFlow($data));
    }


    /**
     * k3API 数据获取接口
     * @return [object] [description]
     */
    public function getK3ApiData()
    {
        $data = $this->request->all();
        return $this->returnResult(app($this->K3CloudApiService)->getK3ApiData($data));
    }

    /**
     * 新增静态数据接口
     * @return [object] [description]
     */
    public function addStaticData()
    {
        $data = $this->request->all();
        return $this->returnResult(app($this->K3CloudApiService)->addStaticData($data));
    }

    /**
     * 获取静态数据信息接口
     * @return [object] [description]
     */
    public function getStaticData()
    {
        $data = $this->request->all();
        return $this->returnResult(app($this->K3CloudApiService)->getStaticData($data));
    }

    /**
     * 更新静态数据信息接口
     * @return [object] [description]
     */
    public function updateStaticData()
    {
        $data = $this->request->all();
        return $this->returnResult(app($this->K3CloudApiService)->updateStaticData($data));
    }

    /**
     * 删除静态数据接口
     * @return [object] [description]
     */
    public function deleteStaticData()
    {
        $data = $this->request->all();
        return $this->returnResult(app($this->K3CloudApiService)->deleteStaticData($data));
    }
    
    /**
     * 获取静态数据列表接口
     * @return [object] [description]
     */
    public function getStaticDataList()
    {
        $data = $this->request->all();
        return $this->returnResult(app($this->K3CloudApiService)->getStaticDataList($data));
    }

    /**
     * 新增cloudApi配置接口
     * @return [object] [description]
     */
    public function addCloudApiData()
    {
        $data = $this->request->all();
        return $this->returnResult(app($this->K3CloudApiService)->addCloudApiData($data));
    }

    /**
     * 获取cloudApi配置信息接口
     * @return [object] [description]
     */
    public function getCloudApiData()
    {
        $data = $this->request->all();
        return $this->returnResult(app($this->K3CloudApiService)->getCloudApiData($data));
    }

    /**
     * 更新cloudApi配置信息接口
     * @return [object] [description]
     */
    public function updateCloudApiData()
    {
        $data = $this->request->all();
        return $this->returnResult(app($this->K3CloudApiService)->updateCloudApiData($data));
    }

    /**
     * 删除cloudApi配置接口
     * @return [object] [description]
     */
    public function deleteCloudApiData()
    {
        $data = $this->request->all();
        return $this->returnResult(app($this->K3CloudApiService)->deleteCloudApiData($data));
    }
    
    /**
     * 获取cloudApi配置列表接口
     * @return [object] [description]
     */
    public function getCloudApiDataList()
    {
        $data = $this->request->all();
        return $this->returnResult(app($this->K3CloudApiService)->getCloudApiDataList($data));
    }

    /**
     * 校验cloudApi配置信息
     * @return [object] [description]
     */
    public function checkCloudApi()
    {
        $data = $this->request->all();
        return $this->returnResult(app($this->K3CloudApiService)->checkCloudApi($data));
    }

    /**
     * 获取k3日志列表
     * @return [object] [description]
     */
    public function getK3LogList()
    {
        $data = $this->request->all();
        return $this->returnResult(app($this->kingdeeService)->getK3LogList($data));
    }


    /**
     * 获取k3日志详情
     * @return [object] [description]
     */
    public function getK3LogDetail()
    {
        $data = $this->request->all();
        return $this->returnResult(app($this->kingdeeService)->getK3LogDetail($data));
    }


    /**
     * 表单获取K3数据源api接口列表
     * @return [object] [description]
     */
    public function getK3CloudApiList()
    {
        $data = $this->request->all();
        return $this->returnResult(app($this->K3CloudApiService)->getK3CloudApiList($data));
    }

    /**
     * 表单获取K3智能api数据
     * @return [object] [description]
     */
    public function getK3SmartApiData()
    {
        $data = $this->request->all();
        return $this->returnResult(app($this->K3CloudApiService)->getK3SmartApiData($data));
    }

    /**
     * 表单获取K3数据源静态数据接口列表
     * @return [object] [description]
     */
    public function getK3StaticDataList()
    {
        $data = $this->request->all();
        return $this->returnResult(app($this->K3CloudApiService)->getK3StaticDataList($data));
    }


    /**
     * 表单静态数据获取数据接口
     * @return [object] [description]
     */
    public function getStaticDataSource()
    {
        $data = $this->request->all();
        return $this->returnResult(app($this->K3CloudApiService)->getStaticDataSource($data));
    }


    /**
     * 获取K3操作手册
     * @return [object] [description]
     */
    public function getK3HelpFile()
    {
        return response()->download("kingdee" . DIRECTORY_SEPARATOR . trans("kingdee.help_file",[],'zh-CN'));
    }

}
