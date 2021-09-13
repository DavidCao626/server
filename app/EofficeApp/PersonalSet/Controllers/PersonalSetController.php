<?php
namespace App\EofficeApp\PersonalSet\Controllers;

use App\EofficeApp\PersonalSet\Requests\PersonalSetRequest;
use App\EofficeApp\Base\Controller;
use \Illuminate\Http\Request;
use App\EofficeApp\PersonalSet\Services\PersonalSetService;
/**
 * 个性设置控制器
 *
 * @author 李志军
 *
 * @since  2015-10-30
 */
class PersonalSetController extends Controller
{
    /** @var object 个性设置服务类对象 */
    private $personalSetService;

    /**
     * 注册个性设置服务类对象
     *
     * @param \App\EofficeApp\PersonalSet\Services\PersonalSetService $personalSetService
     *
     * @author 李志军
     *
     * @since  2015-10-30
     */
    public function __construct(
        PersonalSetService $personalSetService,
        PersonalSetRequest $personalSetRequest,
        Request $request
        )
    {
            parent::__construct();

            $this->personalSetService = $personalSetService;

    $this->request = $request;
    $this->formFilter($request, $personalSetRequest);
    }
    /**
     * 获取用户组列表
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return json 用户组列表
     *
     * @author 李志军
     *
     * @since  2015-10-30
     */
    public function listUserGroup()
    {
            return $this->returnResult($this->personalSetService->listUserGroup($this->request->all(),$this->own['user_id']));
    }
    /**
     * 新建用户组
     *
     * @param \App\Http\Requests\PersonalSetRequest $request
     *
     * @return json 用户组id
     *
     * @author 李志军
     *
     * @since  2015-10-30
     */
    public function addUserGroup()
    {
            return $this->returnResult($this->personalSetService->addUserGroup($this->request->all(),$this->own['user_id']));
    }
    /**
     * 重置用户组名称
     *
     * @param \App\Http\Requests\PersonalSetRequest $request
     * @param int $groupId
     *
     * @return json 重置结果
     *
     * @author 李志军
     *
     * @since  2015-10-30
     */
    public function resetUserGroupName( $groupId)
    {
            return $this->returnResult($this->personalSetService->resetUserGroupName($this->request->input('group_name'), $groupId));
    }
    /**
     * 删除用户组
     *
     * @param int $groupId
     *
     * @return json 删除结果
     *
     * @author 李志军
     *
     * @since  2015-10-30
     */
    public function deleteUserGroup($groupId)
    {
            return $this->returnResult($this->personalSetService->deleteUserGroup($groupId));
    }
    /**
     * 获取用户组详情
     *
     * @param int $groupId
     *
     * @return json 用户组详情
     *
     * @author 李志军
     *
     * @since  2015-10-30
     */
    public function showUserGroup($groupId)
    {
            return $this->returnResult($this->personalSetService->showUserGroup($groupId));
    }
    /**
     * 获取用户组成员列表
     *
     * @param int $groupId
     *
     * @return json 用户组成员列表
     *
     * @author 缪晨晨
     *
     * @since  2017-10-24
     */
    public function showUserGroupUserList($groupId)
    {
            return $this->returnResult($this->personalSetService->showUserGroupUserList($groupId, $this->request->all()));
    }
    /**
     * 为用户组添加用户
     *
     * @param \Illuminate\Http\Request $request
     * @param int $groupId
     *
     * @return json 添加结果
     *
     * @author 李志军
     *
     * @since  2015-10-30
     */
    public function selectUsersForUserGroup($groupId)
    {
            return $this->returnResult($this->personalSetService->selectUsersForUserGroup($this->request->input('group_member',[]), $groupId));
    }
    /**
     * 获取快捷运行列表
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return json 快捷运行列表
     *
     * @author 李志军
     *
     * @since  2015-10-30
     */
    public function listShortcutsRun()
    {
            return $this->returnResult($this->personalSetService->listShortcutsRun($this->request->input('fields',''),$this->own['user_id']));
    }
    /**
     * 新建快捷运行
     *
     * @param \App\Http\Requests\PersonalSetRequest $request
     *
     * @return json 快捷运行id
     *
     * @author 李志军
     *
     * @since  2015-10-30
     */
    public function addShortcutsRun()
    {
            return $this->returnResult($this->personalSetService->addShortcutsRun($this->request->all(),$this->own['user_id']));
    }
    /**
     * 编辑快捷运行
     *
     * @param \App\Http\Requests\PersonalSetRequest $request
     * @param int $winId
     *
     * @return json 编辑结果
     *
     * @author 李志军
     *
     * @since  2015-10-30
     */
    public function editShortcutsRun($winId)
    {
            return $this->returnResult($this->personalSetService->editShortcutsRun($this->request->all(), $winId));
    }
    /**
     * 删除快捷运行
     *
     * @param int $winId
     *
     * @return json 删除结果
     *
     * @author 李志军
     *
     * @since  2015-10-30
     */
    public function deleteShortcutsRun($winId)
    {
            return $this->returnResult($this->personalSetService->deleteShortcutsRun($winId));
    }
    /**
     * 获取快捷运行详情
     *
     * @param int $winId
     *
     * @return json 快捷运行详情
     *
     * @author 李志军
     *
     * @since  2015-10-30
     */
    public function showShortcutsRun($winId)
    {
            return $this->returnResult($this->personalSetService->showShortcutsRun($winId));
    }
    public function getUserInfo()
    {
            return $this->returnResult($this->personalSetService->getUserInfo($this->own['user_id']));
    }
    public function editUserInfo() {
            return $this->returnResult($this->personalSetService->editUserInfo($this->request->all(),$this->own));
    }
    /**
     * 更新用户密码
     *
     * @param \App\Http\Requests\PersonalSetRequest $request
     *
     * @return json 更新结果
     *
     * @author 李志军
     *
     * @since  2015-10-30
     */
    public function modifyPassword()
    {
            return $this->returnResult($this->personalSetService->modifyPassword($this->request->all(),$this->own['user_id']));
    }
    /**
     * 设置左边菜单是否自动隐藏
     *
     * @param int $menuHide
     *
     * @return json 设置结果
     *
     * @author 李志军
     *
     * @since  2015-10-30
     */
    public function setHideMenu($menuHide)
    {
            return $this->returnResult($this->personalSetService->setHideMenu($menuHide,$this->own['user_id']));
    }
    /**
     * 获取左边菜单自动字段值
     *
     * @return json 左边菜单自动字段值
     *
     * @author 李志军
     *
     * @since  2015-10-30
     */
    public function getHideMenuInfo()
    {
            return $this->returnResult($this->personalSetService->getHideMenuInfo($this->own['user_id']));
    }
    /**
     * 获取历史常用短语列表
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return json 历史常用短语列表
     *
     * @author 李志军
     *
     * @since  2015-10-30
     */
    public function listCommonPhrase()
    {
            return $this->returnResult($this->personalSetService->listCommonPhrase($this->request->all(),$this->own['user_id']));
    }
    /**
     * 新建常用短语
     *
     * @param \App\Http\Requests\PersonalSetRequest $request
     *
     * @return json id
     *
     * @author 李志军
     *
     * @since  2015-10-30
     */
    public function addCommonPhrase()
    {
            return $this->returnResult($this->personalSetService->addCommonPhrase($this->request->all(),$this->own['user_id']));
    }
    /**
     * 编辑常用短语
     *
     * @param \App\Http\Requests\PersonalSetRequest $request
     * @param int $phraseId
     *
     * @return json 编辑结果
     *
     * @author 李志军
     *
     * @since  2015-10-30
     */
    public function editCommonPhrase( $phraseId)
    {
            return $this->returnResult($this->personalSetService->editCommonPhrase($this->request->all(), $phraseId,$this->own['user_id']));
    }

    /**
     * 删除历史常用短语
     *
     * @param int $phraseId
     *
     * @return json 删除结果
     *
     * @author 李志军
     *
     * @since  2015-10-30
     */
    public function deleteCommonPhrase($phraseId)
    {
            return $this->returnResult($this->personalSetService->deleteCommonPhrase($phraseId, $this->own['user_id']));
    }
    /**
     * 将历史常用短语设为固定常用短语
     *
     * @param int $phraseId
     *
     * @return json 设置结果
     *
     * @author 李志军
     *
     * @since  2015-10-30
     */
    // public function setFixedCommonPhrase($phraseId)
    // {
    // 	return $this->returnResult($this->personalSetService->setFixedCommonPhrase($phraseId, $this->own['user_id']));
    // }
    /**
     * 获取常用短语详情
     *
     * @param int $phraseId
     *
     * @return json 常用短语详情
     *
     * @author 李志军
     *
     * @since  2015-10-30
     */
    public function showCommonPhrase($phraseId)
    {
            return $this->returnResult($this->personalSetService->showCommonPhrase($phraseId));
    }
    /**
     * 固定常用短语排序
     *
     * @param \App\Http\Requests\PersonalSetRequest $request
     *
     * @return json 排序结果
     *
     * @author 李志军
     *
     * @since  2015-10-30
     */
    // public function sortFixedCommonPhrase()
    // {
    // 	return $this->returnResult($this->personalSetService->sortFixedCommonPhrase($this->request->input('sort_data')));
    // }
    /**
     * 设置签名图片
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return json 设置结果
     *
     * @author 李志军
     *
     * @since  2015-10-30
     */
    public function setSignaturePicture()
    {
            return $this->returnResult($this->personalSetService->setSignaturePicture($this->request->all(),$this->own['user_id']));
    }
    public function getSignaturePicture()
    {
        return $this->returnResult($this->personalSetService->getSignaturePicture($this->request->all(),$this->own['user_id']));
    }
    public function getSignaturePictureAndTime()
    {
        $signature = $this->personalSetService->getSignaturePicture($this->request->all(),$this->own['user_id']);
        $currentTime = date('Y-m-d H:i:s' , time());
        return $this->returnResult(['signature' =>$signature , 'currentTime' => $currentTime]);
    }
    /**
     * 客户端设置
     *
     * @param int $fileType
     *
     * @return json 设置结果
     *
     * @author 李志军
     *
     * @since  2015-10-30
     */
    public function setClientDoc($fileType)
    {
            return $this->returnResult($this->personalSetService->setClientDoc($fileType,$this->own['user_id']));
    }
    /**
     * 获取客户端上传文档类型
     *
     * @return json 文档类型
     *
     * @author 李志军
     *
     * @since  2015-10-30
     */
    public function getClientDoc()
    {
        return $this->returnResult($this->personalSetService->getClientDoc($this->own['user_id']));
    }

    /**
     * 手机版，修改设置：登录后展示的页面
     *
     * @return json
     *
     * @author dp
     *
     * @since  2018-06-30
     */
    public function setShowPageAfterLoginField($pageFlag)
    {
        return $this->returnResult($this->personalSetService->setShowPageAfterLoginField($pageFlag,$this->own['user_id']));
    }

	/**
	 * 手机版，获取设置：登录后展示的页面
	 *
	 * @return json
	 *
	 * @author dp
	 *
	 * @since  2018-06-30
	 */
	public function getShowPageAfterLoginField($userId)
	{
        $userId = $this->own['user_id'];
		return $this->returnResult($this->personalSetService->getShowPageAfterLoginField($userId));
	}

    /**
     * 获取todolist任务列表
     *
     * @return [json] 结果集
     */
    public function toDoItemlist()
    {
        return $this->returnResult($this->personalSetService->toDoItemlist($this->own['user_id']));
    }

    /**
     * 获取某一紧急程度的todolist列表
     *
     * @param  $instancyType [int]
     *
     * @return [json] 结果集
     */
    public function toDoItemlistByInstancy($instancyType)
    {
        return $this->returnResult($this->personalSetService->toDoItemlistByInstancy($instancyType, $this->own['user_id']));
    }

    /**
     * 新增todolist数据
     *
     * @param  $instancyType [int]
     *
     * @return [json] 结果集
     */
    public function createToDoItem()
    {
        return $this->returnResult($this->personalSetService->createToDoItem($this->own['user_id'], $this->request->all()));
    }

    /**
     * 删除某一条todolist数据
     *
     * @param  $itemId [int]
     *
     * @return [json] 结果集
     */
    public function deleteToDoItem($itemId)
    {
        return $this->returnResult($this->personalSetService->deleteToDoItem($itemId, $this->own['user_id']));
    }

    /**
     * 删除某一紧急程度下所有的已结束任务
     * @param $instancyType [int] 紧急程度
     * @return boolean
     */
    public function deleteToDoItemByInstancyType($instancyType)
    {
        return $this->returnResult($this->personalSetService->deleteToDoItemByInstancyType($instancyType, $this->own['user_id']));
    }

    /**
     *
     * 更改某一条todolist的完成状态
     * @param int $itemId 数据id
     * @return  boolean
     *
     */
    public function setToDoItemIsFinish($itemId)
    {
        return $this->returnResult($this->personalSetService->setToDoItemIsFinish($itemId, $this->own['user_id']));
    }

    /**
     * 拖拽更改某一条的紧急程度
     * @param int $itemId 任务id
     * @return boolean
     */
    public function dragToDoItem($itemId)
    {
        return $this->returnResult($this->personalSetService->dragToDoItem($itemId, $this->own['user_id'], $this->request->all()));
    }

    /**
     * 编辑任务名称
     * @param int $itemId 任务id
     * @return boolean
     */
    public function editToDoItem($itemId)
    {
        return $this->returnResult($this->personalSetService->editToDoItem($itemId, $this->own['user_id'], $this->request->all()));
    }

    /**
     * 删除所有的已完成任务
     */
    public function deleteAllToDoItem()
    {
        return $this->returnResult($this->personalSetService->deleteAllToDoItem($this->own['user_id']));
    }

    /**
     * 排序
     * @param int $itemId 数据id
     * @return boolean
     */
    public function toDoItemSort($itemId)
    {
        return $this->returnResult($this->personalSetService->toDoItemSort($itemId, $this->own['user_id'], $this->request->all()));
    }

    /**
     * 改变类型
     * @param  int $itemId 任务id
     */
    public function changeInstancyType($itemId)
    {
        return $this->returnResult($this->personalSetService->changeInstancyType($itemId, $this->own['user_id'], $this->request->all()));
    }

    /**
     * 获取密码安全强度值
     * @param  int $itemId 任务id
     */
    public function getPasswordSecurity()
    {
        return $this->returnResult($this->personalSetService->getPasswordSecurity());
    }
    public function getPasswordLength()
    {
        return $this->returnResult($this->personalSetService->getPasswordLength());
    }
    public function setUserAvatar() {
       return $this->returnResult($this->personalSetService->setUserAvatar($this->request->all(), $this->own['user_id'])); 
    }

    public function getUserPersonalAvatar($userId)
    {
    	return $this->returnResult($this->personalSetService->getUserPersonalAvatar($userId)); 
    }
    public function getUserAvatar($userId)
    {
    	return $this->returnResult($this->personalSetService->getUserAvatar($userId)); 
    }
}
