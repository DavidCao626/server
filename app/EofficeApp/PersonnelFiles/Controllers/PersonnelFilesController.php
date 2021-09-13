<?php

namespace App\EofficeApp\PersonnelFiles\Controllers;

use App\EofficeApp\PersonnelFiles\Services\PersonnelFilesDepartment;
use App\EofficeApp\PersonnelFiles\Services\PersonnelFilesPermission;
use App\Exceptions\ErrorMessage;
use Illuminate\Http\Request;
use App\EofficeApp\Base\Controller;
use App\EofficeApp\PersonnelFiles\Services\PersonnelFilesService;
use App\EofficeApp\PersonnelFiles\Requests\PersonnelFilesRequest;

/**
 * 人事档案控制器
 *
 * @author  朱从玺
 *
 * @since  2015-10-28
 */
class PersonnelFilesController extends Controller
{
	/**
	 * [$request request验证]
	 *
	 * @var [object]
	 */
	protected $request;

	/**
	 * [$personnelFilesService 人事档案service]
	 *
	 * @var [object]
	 */
	protected $personnelFilesService;

	protected $personnelFilesRequest;

	protected $personnelFilesPermission;

    protected $departmentService;

    private $personnelFilesDepartment;

    public function __construct(
		PersonnelFilesRequest $personnelFilesRequest,
		PersonnelFilesService $personnelFilesService,
		PersonnelFilesPermission $personnelFilesPermission,
		PersonnelFilesDepartment $personnelFilesDepartment,
		Request $request
	)
	{
		parent::__construct();

		$this->request = $request;
		$this->personnelFilesService = $personnelFilesService;
		$this->personnelFilesRequest = $personnelFilesRequest;
		$this->personnelFilesPermission = $personnelFilesPermission;
		$this->personnelFilesDepartment = $personnelFilesDepartment;

		$this->formFilter($request, $personnelFilesRequest);
	}

    /**
     * [getPersonnelFilesList 获取人事档案列表]
     *
     * @return array
     *
     */
	public function getPersonnelFilesList()
	{
		$params = $this->request->all();

		$result = $this->personnelFilesService->getPersonnelFilesList($params, $this->own);

		return $this->returnResult($result);
	}

    /**
     * @return array
     */
    public function getPersonnelFilesManageList()
    {
        $params = $this->request->all();

        $result = $this->personnelFilesService->getPersonnelFilesList($params, $this->own, true);

        return $this->returnResult($result);
    }

	/**
	 * [getPersonnelFile 获取某条人事档案]
	 *
	 * @author 朱从玺
	 *
	 * @param  [int]            $fileId [档案ID]
	 *
	 * @since  2015-10-28 创建
	 *
	 * @return [json]                   [查询结果]
	 */
	public function getPersonnelFile($fileId)
	{
		$fileData = $this->personnelFilesService->getPersonnelFile($fileId);

		return $this->returnResult($fileData);
	}

    /**
     * [createPersonnelFile 新建人事文档]
     * @throws ErrorMessage
     */
	public function createPersonnelFile()
	{
		$data = $this->request->all();

		$result = $this->personnelFilesService->createPersonnelFile($data, $this->own);

		return $this->returnResult($result);
	}

    /**
     * [modifyPersonnelFile 编辑人事档案]
     *
     * @param  [int]               $personnelFileId [人事档案ID]
     * @throws ErrorMessage
     * @since  2015-10-28 创建
     * @author 朱从玺
     *
     */
	public function modifyPersonnelFile($personnelFileId)
	{
		$newPersonnelFileData = $this->request->all();

		$result = $this->personnelFilesService->modifyPersonnelFile($personnelFileId, $newPersonnelFileData, $this->own);

		return $this->returnResult($result);
	}

	/**
	 * [deletePersonnelFile 删除一条数据]
	 *
	 * @author 朱从玺
	 *
	 * @param  [int]               $personnelFileId [人事档案ID]
	 *
	 * @since  2015-10-28 创建
	 *
	 * @return [json]                               [删除结果]
	 */
	public function deletePersonnelFile($personnelFileId)
	{
		$loginIp = $this->request->getClientIp();

		$result = $this->personnelFilesService->deletePersonnelFile($personnelFileId, $this->own, $loginIp);

		return $this->returnResult($result);
	}

	/**
	 * 获取人事档案性别
	 * @param  string $personnelFileId 人事档案id
	 * @return string                  性别
	 */
	public function getPersonnelFileSex($personnelFileId)
	{
		$result = $this->personnelFilesService->getPersonnelFileSex($personnelFileId);
		return $this->returnResult($result);
	}

	// 获取所有的档案,用来系统数据调用
	public function getPersonnelFileAll()
	{
		$params = $this->request->all();
		$result = $this->personnelFilesService->getPersonnelFileAll($params);
		return $this->returnResult($result);
	}

	public function getOnePersonnelFile()
	{
		$params = $this->request->all();
		$result = $this->personnelFilesService->getOnePersonnelFile($this->own['user_id']);
		return $this->returnResult($result);
	}

    /**
     * @return array
     */
	public function setPermission()
    {
        $params = $this->request->all();

        return $this->returnResult(
            $this->personnelFilesPermission->setPermission($params)
        );
    }

    /**
     * @return array
     */
    public function getPermissionList()
    {
        $params = $this->request->all();

        return $this->returnResult(
            $this->personnelFilesPermission->getPermissionList($params)
        );
    }

    /**
     * @param $id
     * @return array
     */
    public function getPermission($id)
    {
        return $this->returnResult(
            $this->personnelFilesPermission->getPermission($id)
        );
    }

    /**
     * @param $id
     * @return array
     */
    public function deletePermission($id)
    {
        return $this->returnResult(
            $this->personnelFilesPermission->deletePermission($id)
        );
    }

    /**
     * @param $deptId
     * @return array
     */
    public function queryDeptChildren($deptId)
    {
        return $this->returnResult(
            $this->personnelFilesDepartment->queryDeptChildren($deptId, $this->request->all(), $this->own)
        );
    }

    /**
     * @param $deptId
     * @return array
     */
    public function manageDeptChildren($deptId)
    {
        return $this->returnResult(
            $this->personnelFilesDepartment->manageDeptChildren($deptId, $this->request->all(), $this->own)
        );
    }

    /**
     * @return array
     */
    public function getFilteredQueryDepartments()
    {
        return $this->returnResult(
            $this->personnelFilesDepartment->getFilteredDepartments($this->request->all(), $this->own)
        );
    }

    /**
     * @return array
     */
    public function getFilteredManageDepartments()
    {
        return $this->returnResult(
            $this->personnelFilesDepartment->getFilteredDepartments($this->request->all(), $this->own, true)
        );
    }

    /**
     * @return array
     */
    public function getQueryDescendantDepartments()
    {
        return $this->returnResult(
            $this->personnelFilesDepartment->getDescendantDepartments($this->request->all(), $this->own)
        );
    }

    /**
     * @return array
     */
    public function getManageDescendantDepartments()
    {
        return $this->returnResult(
            $this->personnelFilesDepartment->getDescendantDepartments($this->request->all(), $this->own, true)
        );
    }
    public function getSecurityOption()
	{
		$result = $this->personnelFilesService->getSecurityOption();

		return $this->returnResult($result);
	}


	public function modifySecurityOption()
    {
        $result = $this->personnelFilesService->modifySecurityOption($this->request->all());

        return $this->returnResult($result);
    }
    public function getOrganizationPersonnelMembers($deptId)
    {
        $result = $this->personnelFilesService->getOrganizationPersonnelMembers($deptId, $this->request->all(), $this->own);

        return $this->returnResult($result);
    }

    /**
     * 检查人事档案状态
     * @return [type] [description]
     */
    /*public function checkPersonnelFiles()
	{
		$result = $this->personnelFilesService->checkPersonnelFiles($this->request->all());

		return $this->returnResult($result);
	}*/
}
