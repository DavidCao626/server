<?php

namespace App\EofficeApp\System\ExternalDatabase\Controllers;

use App\EofficeApp\Base\Controller;
use App\EofficeApp\System\ExternalDatabase\Services\ExternalDatabaseService;
use Illuminate\Http\Request;

/**
 * 外部数据库控制器:提供外部数据库请求的实现方法
 *
 * @author qishaobo
 *
 * @since  2016-08-25 创建
 */
class ExternalDatabaseController extends Controller
{
    /** @var object 外部数据库service对象*/
    private $externalDatabaseService;

    public function __construct(
        Request $request,
        ExternalDatabaseService $externalDatabaseService
    ) {
        parent::__construct();
        $this->request                 = $request;
        $this->externalDatabaseService = $externalDatabaseService;
        $this->userId                  = $this->own['user_id'];
    }

    /**
     * 新建外部数据库
     *
     * @return bool
     *
     * @author qishaobo
     *
     * @since  2016-08-25
     */
    public function createExternalDatabase()
    {
        $data                     = $this->request->all();
        $data['database_creator'] = $this->userId;
        $result                   = $this->externalDatabaseService->createExternalDatabase($data);
        return $this->returnResult($result);
    }

    /**
     * 删除外部数据库
     *
     * @param  int|string $databaseId 外部数据库id,多个用逗号隔开
     *
     * @return array 操作是否成功
     *
     * @author qishaobo
     *
     * @since  2016-08-25
     */
    public function deleteExternalDatabase($databaseId)
    {
        $result = $this->externalDatabaseService->deleteExternalDatabase($databaseId);
        return $this->returnResult($result);
    }

    /**
     * 编辑外部数据库
     *
     * @param  int $databaseId 外部数据库id
     *
     * @return bool 操作是否成功
     *
     * @author: qishaobo
     *
     * @since：2016-08-25
     */
    public function editExternalDatabase($databaseId)
    {
        $result = $this->externalDatabaseService->updateExternalDatabase($databaseId, $this->request->all());
        return $this->returnResult($result);
    }

    /**
     * 查询外部数据库详情
     *
     * @param  int $databaseId 外部数据库id
     *
     * @return array 外部数据库详情
     *
     * @author qishaobo
     *
     * @since  2016-08-25
     */
    public function getExternalDatabase($databaseId)
    {
        $result = $this->externalDatabaseService->getExternalDatabase($databaseId);
        return $this->returnResult($result);
    }

    /**
     * 查询外部数据库列表
     *
     * @return array 外部数据库列表
     *
     * @author qishaobo
     *
     * @since  2016-08-25
     */
    public function getExternalDatabases()
    {
        $result = $this->externalDatabaseService->getExternalDatabases($this->request->all());
        return $this->returnResult($result);
    }
    /**
     * 查询外部数据库列表
     *
     * @return array 外部数据库列表
     *
     * @author qishaobo
     *
     * @since  2016-08-25
     */
    public function getExternalDatabasesTableFieldList()
    {
        $result = $this->externalDatabaseService->getExternalDatabasesTableFieldList($this->request->all());
        return $this->returnResult($result);
    }

    /**
     * 测试外部数据库
     *
     * @return array 测试结果
     *
     * @author qishaobo
     *
     * @since  2016-08-25
     */
    public function testExternalDatabases()
    {
        $result = $this->externalDatabaseService->testExternalDatabases($this->request->all());
        if($result===1) {
            return $this->returnResult($result);
        }elseif(isset($result['code'])){
            return $this->returnResult($result);
        }else{
            return [
                'status' => 0,
                'errors'=>[
                    'code'    => '0x015018',
                    'message' => $result
                ]
            ];
        }

    }
    /**
     * 获取外部系统表
     *
     * @return array 测试结果
     *
     * @author qishaobo
     *
     * @since  2016-08-25
     */
    public function getExternalDatabasesTables()
    {
        $result = $this->externalDatabaseService->getExternalDatabasesTables($this->request->all());
        return $this->returnResult($result);
    }
    /**
     * 获取外部系统表数据
     *
     * @return array 测试结果
     *
     * @author qishaobo
     *
     * @since  2016-08-25
     */
    public function getExternalDatabasesTableData()
    {
        $result = $this->externalDatabaseService->getExternalDatabasesTableData($this->request->all());
        return $this->returnResult($result);
    }
    /**
     * 保存门户外部数据库配置
     *
     * @author baijin
     *
     * @since  2018-04-02
     */
    public function savePortalConfig()
    {
        $result = $this->externalDatabaseService->savePortalConfig($this->request->all());
        return $this->returnResult($result);
    }
    /**
     * 获取门户外部数据库配置
     *
     * @author baijin
     *
     * @since  2018-04-02
     */
    public function getPortalConfig()
    {
        $result = $this->externalDatabaseService->getPortalConfig($this->request->all());
        return $this->returnResult($result);
    }

    public function getExternalDatabasesDataBySql()
    {
        $result = $this->externalDatabaseService->getExternalDatabasesDataBySql($this->request->all());
        return $this->returnResult($result);
    }
    public function deletePortaltab()
    {
        $result = $this->externalDatabaseService->deletePortaltab($this->request->all());
        return $this->returnResult($result);
    }
    /**
     * 验证sql
     */
    public function externalDatabaseTestSql()
    {
        $result = $this->externalDatabaseService->externalDatabaseTestSql($this->request->all());
        return $this->returnResult($result);
    }
}
