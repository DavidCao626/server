<?php

namespace App\EofficeApp\PersonalSet\Services;

use QrCode;
use App\EofficeApp\Base\BaseService;
use DB;

/**
 * 个性设置服务类
 *
 * @author 李志军
 *
 * @since  2015-10-30
 */
class PersonalSetService extends BaseService {

    /** @var object 用户组资源库对象 */
    private $userGroupRepository;

    /** @var object 客户端设置资源库对象 */
    private $clientSetRepository;

    /** @var object 常用短语资源库对象 */
    private $commonPhraseRepository;

    /** @var object 快捷设置资源库对象 */
    private $winexeRepository;

    /** @var object 用户资源库对象 */
    private $userRepository;

    /** @var object 用户信息资源库对象 */
    private $userInfoRepository;
    private $attachmentService;
    private $userRoleRepository;
    private $publicGroupRepository;
    private $userEntity;
    private $companyService;
    private $portalService;

    /**
     * 注册相应的资源库对象
     *
     * @param \App\EofficeApp\PersonalSet\Repositories\UserGroupRepository $userGroupRepository
     * @param \App\EofficeApp\PersonalSet\Repositories\ClientSetRepository $clientSetRepository
     * @param \App\EofficeApp\PersonalSet\Repositories\CommonPhraseRepository $commonPhraseRepository
     * @param \App\EofficeApp\PersonalSet\Repositories\WinexeRepository $winexeRepository
     * @param \App\EofficeApp\User\Repositories\UserRepository $userRepository
     * @param \App\EofficeApp\User\Repositories\UserInfoRepository $userInfoRepository
     *
     * @author 李志军
     *
     * @since  2015-10-30
     */
    public function __construct() {
        parent::__construct();

        $this->userGroupRepository = 'App\EofficeApp\PersonalSet\Repositories\UserGroupRepository';
        $this->clientSetRepository = 'App\EofficeApp\PersonalSet\Repositories\ClientSetRepository';
        $this->commonPhraseRepository = 'App\EofficeApp\PersonalSet\Repositories\CommonPhraseRepository';
        $this->winexeRepository = 'App\EofficeApp\PersonalSet\Repositories\WinexeRepository';
        $this->userService = 'App\EofficeApp\User\Services\UserService';
        $this->userRepository = 'App\EofficeApp\User\Repositories\UserRepository';
        $this->userSystemInfoRepository = 'App\EofficeApp\User\Repositories\UserSystemInfoRepository';
        $this->userInfoRepository = 'App\EofficeApp\User\Repositories\UserInfoRepository';
        $this->attachmentService = 'App\EofficeApp\Attachment\Services\AttachmentService';
        $this->toDoListRepository = 'App\EofficeApp\PersonalSet\Repositories\ToDoListRepository';
        $this->userRoleRepository = 'App\EofficeApp\Role\Repositories\UserRoleRepository';
        $this->publicGroupRepository = 'App\EofficeApp\PublicGroup\Repositories\PublicGroupRepository';
        $this->userEntity = 'App\EofficeApp\User\Entities\UserEntity';
        $this->companyService = 'App\EofficeApp\System\Company\Services\CompanyService';
        $this->portalService = 'App\EofficeApp\Portal\Services\PortalService';
    }

    /**
     * 获取用户组列表
     *
     * @param array $data
     *
     * @return array 户组列表
     *
     * @author 李志军
     *
     * @since  2015-10-30
     */
    public function listUserGroup($data, $currentUserId) {
        $params = $this->parseParams($data);
        if (isset($params['search']['user_id'])) {
            unset($params['search']['user_id']);
        }
        if (isset($param['search']['user_accounts'])) {
            unset($param['search']['user_accounts']);
        }
        $userGroupData = app($this->userGroupRepository)->listUserGroup($params, $currentUserId);
        if (isset($params['dataFilter']) && !empty($params['dataFilter'])) {
            $config = config('dataFilter.' . $params['dataFilter']);
            if (!empty($config)) {
                $method = $config['dataFrom'][1];
                $params['loginUserInfo'] = ['user_id' => $currentUserId];
                $userIdFilterData = app($config['dataFrom'][0])->$method($params);
            }
        }
        if (!empty($userGroupData)) {
            foreach ($userGroupData as $key => $value) {
                if (empty($value['group_member'])) {
                    if (isset($params["user_total"])) {
                        $userGroupData[$key]['user_total'] = 0;
                    }
                    $userGroupData[$key]['has_children'] = 0;
                } else {
                    if (isset($userIdFilterData) && !empty($userIdFilterData) && isset($userIdFilterData['user_id']) && !empty($userIdFilterData['user_id'])) {
                        $groupMemberArray = explode(',', trim($value['group_member'], ','));
                        foreach ($groupMemberArray as $gKey => $gValue) {
                            if (isset($userIdFilterData['isNotIn']) && !$userIdFilterData['isNotIn']) {
                                if (in_array($gValue, $userIdFilterData['user_id'])) {
                                    unset($groupMemberArray[$gKey]);
                                }
                            } else {
                                if (!in_array($gValue, $userIdFilterData['user_id'])) {
                                    unset($groupMemberArray[$gKey]);
                                }
                            }
                        }
                        $userTotal = count($groupMemberArray);
                        $value['group_member'] = !empty($groupMemberArray) ? implode(',', $groupMemberArray) : '';
                        $userGroupData[$key]['group_member'] = $value['group_member'];
                    }
                    if (isset($params["user_total"])) {
                        $userTotal = count(explode(',', $value['group_member']));
                        $userGroupData[$key]['user_total'] = $userTotal;
                    }
                    if (isset($userTotal)) {
                        if ($userTotal > 0) {
                            $userGroupData[$key]['has_children'] = 1;
                        } else {
                            $userGroupData[$key]['has_children'] = 0;
                        }
                    } else {
                        $userGroupData[$key]['has_children'] = 1;
                    }
                    unset($userTotal);
                }
            }
        }
        return $userGroupData;
    }

    /**
     * 新建用户组
     *
     * @param array $data
     *
     * @return array 用户组id
     *
     * @author 李志军
     *
     * @since  2015-10-30
     */
    public function addUserGroup($data, $currentUserId) {
        $pinyin = convert_pinyin($data['group_name']);

        $userGroupData = [
            'group_name' => $data['group_name'],
            'creator' => $currentUserId,
            'group_member' => '',
            'group_name_py' => $pinyin[0],
            'group_name_zm' => $pinyin[1],
            'group_remark' => $this->defaultValue('group_remark', $data, '')
        ];
        if (!empty($data['group_member'])) {
            $userGroupData['group_member'] = $this->getGroupsUserid($data['group_member']);
        }
        if ($result = app($this->userGroupRepository)->insertData($userGroupData)) {
            return ['group_id' => $result->group_id];
        }

        return ['code' => ['0x000003', 'common']];
    }

    /**
     * 重置用户组名称
     *
     * @param string $groupName
     * @param int $groupId
     *
     * @return int 重置结果
     *
     * @author 李志军
     *
     * @since  2015-10-30
     */
    public function resetUserGroupName($groupName, $groupId) {
        if ($groupId == 0) {
            return ['code' => ['0x039002', 'personalset']];
        }

        if (app($this->userGroupRepository)->updateData(['group_name' => $groupName], ['group_id' => $groupId])) {
            return true;
        }

        return ['code' => ['0x000003', 'common']];
    }

    /**
     * 获取用户组详情
     *
     * @param int $groupId
     *
     * @return object 用户组详情
     *
     * @author 李志军
     *
     * @since  2015-10-30
     */
    public function showUserGroup($groupId) {
        if ($groupId == 0) {
            return ['code' => ['0x039002', 'personalset']];
        }

        return app($this->userGroupRepository)->getDetail($groupId);
    }

    /**
     * 获取用户组成员列表
     *
     * @param int $groupId，array $params
     *
     * @return object 用户组成员列表
     *
     * @author 缪晨晨
     *
     * @since  2017-10-20
     */
    public function showUserGroupUserList($groupId, $params = array()) {
        $params = $this->parseParams($params);
        if ($groupId == 0) {
            return ['code' => ['0x039002', 'personalset']];
        }

        $userGroupDetail = app($this->userGroupRepository)->getDetail($groupId);
        if (isset($userGroupDetail->group_member) && !empty($userGroupDetail->group_member)) {
            $userIdArray = explode(',', trim($userGroupDetail->group_member, ','));
            if (isset($params['search']['user_id'][0]) && !empty($params['search']['user_id'][0])) {
                if (isset($params['search']['user_id'][1]) && $params['search']['user_id'][1] == 'not_in') {
                    if (is_array($params['search']['user_id'][0])) {
                        $userIdNewArray = array_diff($userIdArray, $params['search']['user_id'][0]);
                    } else {
                        $userIdNewArray = array_diff(explode(',', $userIdArray), $params['search']['user_id'][0]);
                    }
                } else {
                    if (is_array($params['search']['user_id'][0])) {
                        $userIdNewArray = array_intersect($userIdArray, $params['search']['user_id'][0]);
                    } else {
                        $userIdNewArray = array_intersect(explode(',', $userIdArray), $params['search']['user_id'][0]);
                    }
                }
                $params['search']['user_id'][0] = $userIdNewArray;
                $params['search']['user_id'][1] = 'in';
            } else {
                $params['search']['user_id'][0] = $userIdArray;
                $params['search']['user_id'][1] = 'in';
            }

            return app($this->userService)->userSystemList($params);
        } else {
            return ['list' => [], 'total' => 0];
        }
    }

    /**
     * 删除用户组
     *
     * @param int $groupId
     *
     * @return int 删除结果
     *
     * @author 李志军
     *
     * @since  2015-10-30
     */
    public function deleteUserGroup($groupId) {
        if ($groupId == 0) {
            return ['code' => ['0x039002', 'personalset']];
        }

        if (app($this->userGroupRepository)->deleteById($groupId)) {
            return true;
        }

        return ['code' => ['0x000003', 'common']];
    }

    /**
     * 为用户组选择用户
     *
     * @param string $groupMember
     * @param int $groupId
     *
     * @return int 选择结果
     *
     * @author 李志军
     *
     * @since  2015-10-30
     */
    public function selectUsersForUserGroup($params, $groupId) {
        if ($groupId == 0) {
            return ['code' => ['0x039002', 'personalset']];
        }
        if ($params == "all") {
            $params = array();
            $result = app($this->userRepository)->getAllUsers(['fields' => 'user_id'])->toArray();
            foreach ($result as $value) {
                $params[] = $value['user_id'];
            }
        }
        if (app($this->userGroupRepository)->updateData(['group_member' => implode(',', $params)], ['group_id' => $groupId])) {
            return true;
        }

        return ['code' => ['0x000003', 'common']];
    }

    /**
     * 获取快捷运行列表
     *
     * @param array $fields
     *
     * @return array 快捷运行列表
     *
     * @author 李志军
     *
     * @since  2015-10-30
     */
    public function listShortcutsRun($fields, $currentUserId) {

        return app($this->winexeRepository)->listShortcutsRun($currentUserId, json_decode($fields, true));
    }

    /**
     * 新建快捷运行
     *
     * @param array $data
     *
     * @return int id
     *
     * @author 李志军
     *
     * @since  2015-10-30
     */
    public function addShortcutsRun($data, $currentUserId) {
        $winData = [
            'win_number' => $data['win_number'],
            'win_name' => $data['win_name'],
            'win_path' => $data['win_path'],
            'creator' => $currentUserId
        ];

        if ($result = app($this->winexeRepository)->insertData($winData)) {
            return ['win_id' => $result->win_id];
        }

        return ['code' => ['0x000003', 'common']];
    }

    /**
     * 编辑快捷运行
     *
     * @param array $data
     * @param int $winId
     *
     * @return int 编辑结果
     *
     * @author 李志军
     *
     * @since  2015-10-30
     */
    public function editShortcutsRun($data, $winId) {
        if ($winId == 0) {
            return ['code' => ['0x039006', 'personalset']];
        }

        $winData = [
            'win_number' => $data['win_number'],
            'win_name' => $data['win_name'],
            'win_path' => $data['win_path']
        ];

        if (app($this->winexeRepository)->updateData($winData, ['win_id' => $winId])) {
            return true;
        }

        return ['code' => ['0x000003', 'common']];
    }

    /**
     * 获取快捷运行详情
     *
     * @param int $winId
     *
     * @return object 快捷运行详情
     *
     * @author 李志军
     *
     * @since  2015-10-30
     */
    public function showShortcutsRun($winId) {
        if ($winId == 0) {
            return ['code' => ['0x039006', 'personalset']];
        }

        return app($this->winexeRepository)->getDetail($winId);
    }

    /**
     * 删除快捷运行
     *
     * @param int $winId
     *
     * @return int
     *
     * @author 李志军
     *
     * @since  2015-10-30
     */
    public function deleteShortcutsRun($winId) {
        if ($winId == 0) {
            return ['code' => ['0x039006', 'personalset']];
        }

        if (app($this->winexeRepository)->deleteById($winId)) {
            return true;
        }

        return ['code' => ['0x000003', 'common']];
    }

    public function getUserInfo($currentUserId) {
        $data = app($this->userRepository)->getUserAllData($currentUserId);
        $pwsswordSet = get_system_param('login_password_security');
        $pwsswordSwitch = get_system_param('login_password_security_switch');

        if (!isset($data->login_password_security)) {
            $data->login_password_security = $pwsswordSet;
        }
        if (!isset($data->login_password_security_switch)) {
            $data->login_password_security_switch = $pwsswordSwitch;
        }

        return $data;
    }

    public function editUserInfo($data, $currentUser) {
        $userId = $currentUser['user_id'];

        $userNamePyArray = convert_pinyin($data["user_name"]);
        $dataUser["user_name_py"] = $userNamePyArray[0];
        $dataUser["user_name_zm"] = $userNamePyArray[1];
        $dataUser["user_name"] = $data["user_name"];
        // 用户基础表信息更新
        $userDataCreateResult = app($this->userRepository)->updateData($dataUser, ["user_id" => $userId]);
        // info表信息更新
        $dataInfoUser = [
			'sex'				=> $data['sex'],
			'phone_number'		=> $data['phone_number']? $data['phone_number']: '',
			'weixin'			=> $data['weixin']? $data['weixin']: '',
			'email'				=> $data['email']? $data['email']: '',
			'oicq_no'			=> $data['oicq_no']? $data['oicq_no']: '',
			'msn'				=> $data['msn'] ?? '',
			'birthday'			=> $data['birthday']? $data['birthday']: '',
			'dept_phone_number'	=> $data['dept_phone_number']? $data['dept_phone_number']: '',
			'faxes'				=> $data['faxes']? $data['faxes']:'',
			'home_phone_number'	=> $data['home_phone_number']? $data['home_phone_number']: '',
			'home_zip_code'		=> $data['home_zip_code']? $data['home_zip_code']: '',
			'home_address'		=> $data['home_address']? $data['home_address']: '',
		];
        $userInfoDataCreateResult = app($this->userInfoRepository)->updateData($dataInfoUser,["user_id" => $userId]);

        $codeData['user_id']      = $userId;
        $codeData['user_name']    = $data['user_name'];
        $codeData['dept_name']    = $currentUser['dept_name'];
        $codeData['role_name']    = implode(',', $currentUser["role_name"]);
        $codeData['email'] 		  = $data['email'];
        $codeData['phone_number'] = $data['phone_number'];

        $this->setUserQrCode($codeData);

        return true;
    }

    /**
     * [setUserQrCode 设置用户二维码]
     *
     * @method 朱从玺
     *
     * @param  [type]        $userInfo [用户数据]
     *
     * @return [bool] 		 生成结果
     */
    public function setUserQrCode($userInfo) {
        //二维码路径
        $qrCodePath = createCustomDir("qrcode");
        if (!$qrCodePath) {
            return ['code' => ['0x000006', 'common']];
        }

        //参数可以直接是用户数据,也可以是用户ID,再自己获取数据
        if (!is_array($userInfo)) {
            $userAllData = app($this->userRepository)->getUserAllData($userInfo);
            $roleName = [];
            if (!empty($userAllData)) {
                if (isset($userAllData['userHasManyRole'])) {
                    foreach ($userAllData['userHasManyRole'] as $key => $value) {
                        if (isset($value['hasOneRole']['role_name'])) {
                            $roleName[] = $value['hasOneRole']['role_name'];
                        }
                    }
                }
                $userInfo = [
                    'user_id' => isset($userAllData['user_id']) ? $userAllData['user_id'] : '',
                    'user_name' => isset($userAllData['user_name']) ? $userAllData['user_name'] : '',
                    'dept_name' => isset($userAllData['userHasOneSystemInfo']['userSystemInfoBelongsToDepartment']['dept_name']) ? $userAllData['userHasOneSystemInfo']['userSystemInfoBelongsToDepartment']['dept_name'] : '',
                    'role_name' => !empty($roleName) ? implode(',', $roleName) : '',
                    'email' => isset($userAllData['userHasOneInfo']['email']) ? $userAllData['userHasOneInfo']['email'] : '',
                    'phone_number' => isset($userAllData['userHasOneInfo']['phone_number']) ? $userAllData['userHasOneInfo']['phone_number'] : ''
                ];
            } else {
                return false;
            }
        }

        //公司信息
        $companyData = app($this->companyService)->getCompanyDetail();

        // 二维码数据
        $codeContents = 'BEGIN:VCARD' . "\n";
        $codeContents .= 'VERSION:4.0' . "\n";
        $codeContents .= 'N:' . (isset($userInfo["user_name"]) ? $userInfo["user_name"] : '') . "\n";
        $codeContents .= 'ROLE:' . (isset($userInfo["user_name"]) ? $userInfo["dept_name"] : '') . "\n";
        $codeContents .= 'TITLE:' . (isset($userInfo["user_name"]) ? $userInfo["role_name"] : '') . "\n";
        $codeContents .= 'EMAIL:' . (isset($userInfo["user_name"]) ? $userInfo['email'] : '') . "\n";
        $codeContents .= 'TEL;TYPE=cell:' . (isset($userInfo["user_name"]) ? $userInfo['phone_number'] : '') . "\n";
        $codeContents .= 'ORG:' . (isset($companyData['company_name']) ? $companyData['company_name'] : '') . "\n";
        $codeContents .= 'ADR;TYPE=WORK,PREF:' . (isset($companyData['company_name']) ? $companyData["address"] : '') . ";" . (isset($companyData['company_name']) ? $companyData["zip_code"] : '') . "\n";
        $codeContents .= 'END:VCARD';

        if (isset($userInfo['user_id'])) {
            if (file_exists($qrCodePath . $userInfo['user_id'] . ".png")) {
                unlink($qrCodePath . $userInfo['user_id'] . ".png");
            }

            //生成二维码
            return QrCode::format('png')->encoding('UTF-8')->size(150)->margin(0)
                            ->generate($codeContents, $qrCodePath . $userInfo['user_id'] . '.png');
        } else {
            return false;
        }
    }

    /**
     * 更新用户密码
     *
     * @param array $data
     *
     * @return int 更新结果
     *
     * @author 李志军
     *
     * @since  2015-10-30
     */
    public function modifyPassword($data, $currentUserId) {
        $oldPassword = $this->defaultValue('old_password', $data, '');
        $password = get_user_simple_attr($currentUserId, 'password');
        $passwordLength = $this->getPasswordLength();;
        $passwordSecurity = get_system_param('login_password_security_switch', 0);
        if ($password != crypt($oldPassword, $password)) {
            return ['code' => ['0x039008', 'personalset']];
        }
        if (strlen($data['password']) < $passwordLength) {
            return ['code' => ['0x039016', 'personalset'], 'dynamic' => [trans('personalset.new_password_length_invalid').$passwordLength.trans('personalset.digits')]];
        }
        if (strlen($data['password_repeat']) < $passwordLength) {
            return ['code' => ['0x039016', 'personalset'], 'dynamic' => [trans('personalset.confirm_new_password_length_invalid').$passwordLength.trans('personalset.digits')]];
        }
        if ($data['password'] != $data['password_repeat']) {
            return ['code' => ['0x039009', 'personalset']];
        }

        if (app($this->userRepository)->updateData(['password' => crypt($data['password'], null), 'change_pwd' => 0], ['user_id' => $currentUserId])) {
            if (app($this->userSystemInfoRepository)->updateData(["last_pass_time" => date('Y-m-d H:i:s')], ['user_id' => $currentUserId])) {
                return true;
            }
        }

        return ['code' => ['0x000003', 'common']];
    }

    /**
     * 设置左边菜单是否自动隐藏
     *
     * @param int $menuHide
     *
     * @return int 设置结果
     *
     * @author 李志军
     *
     * @since  2015-10-30
     */
    public function setHideMenu($menuHide, $currentUserId) {
        if (!in_array(intval($menuHide), [1, 2])) {
            return ['code' => ['0x039010', 'personalset']];
        }

        if (app($this->userInfoRepository)->updateData(['menu_hide' => $menuHide], ['user_id' => $currentUserId])) {
            return true;
        }

        return ['code' => ['0x000003', 'common']];
    }

    /**
     * 获取左边菜单自动隐藏字段信息
     *
     * @return int 自动隐藏字段信息
     *
     * @author 李志军
     *
     * @since  2015-10-30
     */
    public function getHideMenuInfo($currentUserId) {
        $userInfo = app($this->userInfoRepository)->getDetail($currentUserId);

        return ['menu_hide' => $userInfo->menu_hide];
    }

    /**
     * 设置签名图片
     *
     * @param file $signaturePicture
     *
     * @author 李志军
     *
     * @since  2015-10-30
     */
    public function setSignaturePicture($signaturePicture, $currentUserId) {
        $userSignaturePictureId = app($this->userInfoRepository)->getUserSignaturPicture($currentUserId);
        if (!empty($userSignaturePictureId)) {
            // 删除原先的附件数据和图片文件
            $removeAttachmentData = ['attach_ids' => $userSignaturePictureId[0]['signature_picture']];
            app($this->attachmentService)->removeAttachment($removeAttachmentData);
        }
        if (app($this->userInfoRepository)->updateData($signaturePicture, ['user_id' => $currentUserId])) {
            return true;
        }
        return ['code' => ['0x000003', 'common']];
    }

    public function getSignaturePicture($param, $currentUserId) {
        if (isset($param['user_id'])) {
            if (empty($param['user_id'])) {
                return "";
            }
            $currentUserId = $param['user_id'];
        }
        if ($userInfo = app($this->userInfoRepository)->getDetail($currentUserId , true)) {
            if (isset($param['encrypt'])) {
                return [
                    'attachment_id' => $userInfo->signature_picture,
                    'encrypt_attach' => encrypt_params($userInfo->signature_picture, false, true)
                ];
            } else {
                return $userInfo->signature_picture;
            }
        } else {
            return "";
        }
    }

    /**
     * 客户端设置
     *
     * @param int $fileType
     *
     * @return int 设置结果
     *
     * @author 李志军
     *
     * @since  2015-10-30
     */
    public function setClientDoc($fileType, $currentUserId) {
        if (!in_array(intval($fileType), [1, 2])) {
            return ['code' => ['0x039010', 'personalset']];
        }

        if (app($this->clientSetRepository)->clientSetExists($currentUserId)) {
            if (app($this->clientSetRepository)->updateData(['file_type' => $fileType], ['user_id' => $currentUserId])) {
                return true;
            }

            return ['code' => ['0x000003', 'common']];
        } else {
            $data = [
                'user_id' => $currentUserId,
                'file_type' => $fileType
            ];
            if (app($this->clientSetRepository)->insertData($data)) {
                return true;
            }
        }

        return ['code' => ['0x000003', 'common']];
    }

    /**
     * 获取客户端设置信息
     *
     * @return object 客户端设置信息
     *
     * @author 李志军
     *
     * @since  2015-10-30
     */
    public function getClientDoc($currentUserId) {
        return app($this->clientSetRepository)->getClientDoc($currentUserId);
    }

    /**
     * 手机版，修改设置：登录后展示的页面
     *
     * @return object
     *
     * @author dp
     *
     * @since  2018-06-30
     */
    public function setShowPageAfterLoginField($pageFlag, $currentUserId) {
        if (app($this->userInfoRepository)->updateData(['show_page_after_login' => $pageFlag], ['user_id' => $currentUserId])) {
            return true;
        }
        return ['code' => ['0x000003', 'common']];
    }

    /**
     * 手机版，获取设置：登录后展示的页面
     *
     * @return object
     *
     * @author dp
     *
     * @since  2018-06-30
     */
    public function getShowPageAfterLoginField($userId) {
        $userInfo = app($this->userInfoRepository)->getDetail($userId);
        return isset($userInfo->show_page_after_login) ? $userInfo->show_page_after_login : "";
    }

    /**
     * 获取固定短语排序号
     *
     * @return int 排序号
     *
     * @author 李志军
     *
     * @since  2015-10-30
     */
    private function getOrderNumber($currentUserId) {
        $number = app($this->commonPhraseRepository)->getMaxOrderNumber($currentUserId);

        return $number + 1;
    }

    /**
     * 为参数赋予默认值
     *
     * @param type $key 键值
     * @param array $data 原来的数据
     * @param type $default 默认值
     *
     * @return type 处理后的值
     *
     * @author 李志军
     *
     * @since 2015-10-17
     */
    private function defaultValue($key, $data, $default) {
        return isset($data[$key]) ? $data[$key] : $default;
    }

    //获取组含某个用户的组集合
    //喻威
    //

    public function getUserGrop($own) {
        return app($this->userGroupRepository)->getUserGrop($own);
    }

    //根据条件解析出user_id
    //

    public function getGroupsUserid($params) {
        $groupMember = "";
        if (isset($params['role_id'])) {
            foreach ($params['role_id'] as $value) {
                $result = app($this->userRepository)->getUserIdByRole($value);
                foreach ($result as $k => $v) {
                    $groupMember .= "," . $v['user_id'];
                }
            }
        }
        if (isset($params['dept_id'])) {
            foreach ($params['dept_id'] as $key => $value) {
                $deptMember = app($this->userRepository)->getUserByDepartment($value);
                foreach ($deptMember as $k => $v) {
                    $groupMember .= "," . $v->user_id;
                }
            }
        }
        if (isset($params['public_group_id'])) {
            foreach ($params['public_group_id'] as $value) {
                $result = app($this->publicGroupRepository)->getDetail($value);
                $groupMember .= "," . $result->group_member;
            }
        }
        if (isset($params['personal_group_id'])) {
            foreach ($params['personal_group_id'] as $value) {
                $result = app($this->userGroupRepository)->getDetail($value);
                $groupMember .= "," . $result->group_member;
            }
        }
        if (isset($params['user_id'])) {
            $groupMember .= ',' . implode(',', $params['user_id']);
        }
        if (!empty($groupMember)) {
            $groupMember = ltrim($groupMember, ',');
            $groupMember = array_unique(array_filter(explode(',', $groupMember)));
            $groupMember = implode(',', $groupMember);
        }
        return $groupMember;
    }

    // 个人常用短语设置
    /**
     * 获取短语列表
     *
     * @param array $param
     *
     * @return array 历史短语列表
     *
     */
    public function listCommonPhrase($param, $currentUserId) {
        $param = $this->parseParams($param);
        $param['currentUserId'] = $currentUserId;
        return $this->response(app($this->commonPhraseRepository), 'getCommonPhraseCount', 'listCommonPhrase', $param);
    }

    /**
     * 新建常用短语
     *
     * @param array $data
     *
     * @return array 短语id
     *
     */
    public function addCommonPhrase($data, $currentUserId) {
        $isCommon = $this->defaultValue('is_common', $data, 0);
        $param = [];
        $param['currentUserId'] = $currentUserId;
        $dataphrase = app($this->commonPhraseRepository)->getCommonPhrase($param);
        $orderNumber = 0;
        if ($isCommon != 0) {
            $orderNumber = $this->getOrderNumber($currentUserId);
        }
        $phraseData = [
            'user_id' => $currentUserId,
            'content' => $data['content'],
            'order_number' => $orderNumber,
            'is_common' => $isCommon,
            'order_number' => $data['order_number'] ?? 0
        ];
        if (!in_array($phraseData['content'], array_column($dataphrase, 'content'))) {
            if ($result = app($this->commonPhraseRepository)->insertData($phraseData)) {
                return ['phrase_id' => $result->phrase_id];
            }
        } else {
            return ['code' => ['0x015023', 'system']];
        }

        return ['code' => ['0x000003', 'common']];
    }

    /**
     * 编辑常用短语
     *
     * @param array $data
     * @param int $phraseId
     *
     * @return int 编辑结果
     *
     */
    public function editCommonPhrase($data, $phraseId, $currentUserId) {
        $phraseInfo = app($this->commonPhraseRepository)->getDetail($phraseId);
        if ($phraseInfo->user_id != $currentUserId) {
            return ['code' => ['0x039012', 'personalset']];
        }
        if (!isset($data['content']) || empty($data['content'])) {
            return ['code' => ['0x039011', 'personalset']];
        }
        $phraseData = [
            'content' => $data['content'],
            'order_number' => $data['order_number'] ?? 0
        ];
        // 进行数据重复验证
        $param['currentUserId'] = $currentUserId;
        $result = app($this->commonPhraseRepository)->getUniqueCommonPhrase($data, $param);

        if (empty($result) || ($result && $phraseId == $result['phrase_id'])) {
            if (app($this->commonPhraseRepository)->updateData($phraseData, ['phrase_id' => $phraseId])) {
                return true;
            }
            return ['code' => ['0x000003', 'common']];
        } else {
            return ['code' => ['0x015023', 'system']];
        }
    }

    /**
     * 删除常用短语
     *
     * @param int $phraseId
     *
     * @return int 删除结果
     *
     */
    public function deleteCommonPhrase($phraseId, $currentUserId) {
        // 当为多条数据删除的时候要进行分割字符串（$phraseId传入的是一个数组）
        $phraseId = explode(',', $phraseId);
        foreach ($phraseId as $key => $value) {
            $phraseInfo = app($this->commonPhraseRepository)->getDetail($value);
            if ($phraseInfo->user_id != $currentUserId) {
                return ['code' => ['0x039012', 'personalset']];
            }
            if (!app($this->commonPhraseRepository)->deleteById($value)) {
                return ['code' => ['0x000003', 'common']];
            }
        }
        return true;
    }

    /**
     * 获取短语详情
     *
     * @param int $phraseId
     *
     * @return object 短语详情
     *
     */
    public function showCommonPhrase($phraseId) {
        return app($this->commonPhraseRepository)->getDetail($phraseId);
    }

    /**
     * 获取todolist列表
     *
     * @param int $currentUserId
     *
     * @return [object] 查询结果
     */
    public function toDoItemlist($currentUserId) {
        return app($this->toDoListRepository)->toDoItemlist($currentUserId);
    }

    /**
     * 获取某一紧急程度的todolist列表
     *
     * @param int $currentUserId
     *
     * @return [object] 查询结果
     */
    public function toDoItemlistByInstancy($instancyType, $currentUserId) {
        return app($this->toDoListRepository)->toDoItemlistByInstancy($instancyType, $currentUserId);
    }

    /**
     * 新增一条todolist数据
     *
     * @param int $currentUserId
     * @param array $data  新增数据内容
     *
     * @return [int] 新插入数据id
     */
    public function createToDoItem($currentUserId, $data) {
        $itemName = isset($data['item_name']) ? strval($data['item_name']) : '';
        $instancyType = isset($data['instancy_type']) ? intval($data['instancy_type']) : 0;
        $isFinish = isset($data['is_finish']) ? intval($data['is_finish']) : 0;
        //获取当前类型的最大排序值
        $maxSort = DB::select('select max(sort) as sort from to_do_list where user_id = ? and instancy_type = ?', [$currentUserId, $instancyType]);
        $sort = 1;
        if (!empty($maxSort) && $maxSort[0]->sort) {
            $sort = (int) $maxSort[0]->sort + 1;
        }

        $toDoItemData = [
            'user_id' => $currentUserId,
            'item_name' => $itemName,
            'instancy_type' => $instancyType,
            'is_finish' => $isFinish,
            'sort' => $sort,
        ];

        if ($result = app($this->toDoListRepository)->insertData($toDoItemData)) {
            return ['item_id' => $result->item_id];
        }

        return ['code' => ['0x000003', 'common']];
    }

    /**
     * 删除一条todolist数据
     *
     * @param int $currentUserId
     * @param array $data  新增数据内容
     *
     * @return [int] 新插入数据id
     */
    public function deleteToDoItem($itemId, $currentUserId) {
        $targetList = app($this->toDoListRepository)->getDetail($itemId);
        if (empty($targetList) || $targetList->user_id != $currentUserId) {
            return ['code' => ['0x000006', 'common']];
        }

        if (app($this->toDoListRepository)->deleteById($itemId)) {
            return true;
        }

        return ['code' => ['0x000003', 'common']];
    }

    /**
     * 删除某一紧急程度下所有的已结束任务
     *
     * @param $currentUserId [int]
     * @param $instancyType [int]  紧急程度
     *
     * @return Boolean
     */
    public function deleteToDoItemByInstancyType($instancyType, $currentUserId) {
        if (!in_array($instancyType, [0, 1, 2, 3])) {
            return ['code' => ['0x000008'], 'common'];
        }
        $deleteWhere = [
            'user_id' => [$currentUserId],
            'instancy_type' => [$instancyType],
            'is_finish' => [1],
        ];
        return app($this->toDoListRepository)->deleteByWhere($deleteWhere);
    }

    /**
     *
     * 更改某一条todolist的完成状态
     *
     * @param   $currentUserId [int]
     * @param   $itemId [int]
     *
     * @return  [boolean]
     *
     */
    public function setToDoItemIsFinish($itemId, $currentUserId) {
        $toDoList = app($this->toDoListRepository)->getDetail($itemId);
        if (empty($toDoList) || $toDoList->user_id != $currentUserId) {
            return ['code' => ['0x039012', 'personalset']];
        }

        $toDoData = [
            'is_finish' => !$toDoList->is_finish,
        ];

        if (app($this->toDoListRepository)->updateData($toDoData, ['item_id' => $itemId])) {
            return true;
        }
        return ['code' => ['0x000003', 'common']];
    }

    /**
     * 拖拽更改todolist的紧急程度
     */
    public function dragToDoItem($itemId, $currentUserId, $data) {
        $nextId = isset($data['nextId']) ? intval($data['nextId']) : '';
        $instancyType = isset($data['instancyType']) ? intval($data['instancyType']) : '';
        if (!is_int($nextId) || !in_array($data['instancyType'], [0, 1, 2, 3])) {
            return ['code' => ['0x000008'], 'common'];
        }
        if ($nextId > 0) {
            $nextList = app($this->toDoListRepository)->getDetail($nextId);
            $targetList = app($this->toDoListRepository)->getDetail($itemId);
            if ($targetList->is_finish == 0 && $nextList->is_finish == 1) {
                $minSort = DB::select('select min(sort) as sort from to_do_list where user_id = ? and instancy_type = ?', [$currentUserId, $instancyType]);
                $sort = 0;
                if (!empty($minSort) && is_int($minSort[0]->sort)) {
                    $sort = (int) $minSort[0]->sort - 1;
                }
                $targetResult = DB::update('update to_do_list set sort = ?, instancy_type = ? where item_id = ?', [$sort, $instancyType, $itemId]);
                if ($targetResult) {
                    return true;
                }
            } else {
                $nextResult = DB::update('update to_do_list set sort = sort - 1 where instancy_type = ? and sort <= ? and user_id = ? ', [$instancyType, $nextList->sort, $currentUserId]);
                $targetResult = DB::update('update to_do_list set sort = ?, instancy_type = ? where item_id = ?', [$nextList->sort, $instancyType, $itemId]);
                if ($nextResult && $targetResult) {
                    return true;
                }
            }
        } else {
            $minSort = DB::select('select min(sort) as sort from to_do_list where user_id = ? and instancy_type = ?', [$currentUserId, $instancyType]);
            $sort = 0;
            if (!empty($minSort) && is_int($minSort[0]->sort)) {
                $sort = (int) $minSort[0]->sort - 1;
            }
            $targetResult = DB::update('update to_do_list set sort = ?, instancy_type = ? where item_id = ?', [$sort, $instancyType, $itemId]);
            if ($targetResult) {
                return true;
            }
        }
        return ['code' => ['0x000003', 'common']];
    }

    /**
     * 编辑任务名称
     */
    public function editToDoItem($itemId, $currentUserId, $data) {
        $where = [
            'user_id' => $currentUserId,
            'item_id' => $itemId,
        ];
        $updateData = [
            'item_name' => $data['item_name'],
        ];
        if (app($this->toDoListRepository)->updateData($updateData, $where)) {
            return true;
        }
        return ['code' => ['0x000003', 'common']];
    }

    /**
     * 删除所有已完成任务
     */
    public function deleteAllToDoItem($currentUserId) {
        $deleteWhere = [
            'user_id' => [$currentUserId],
            'is_finish' => [1],
        ];
        return app($this->toDoListRepository)->deleteByWhere($deleteWhere);
    }

    /**
     * 排序
     */
    public function toDoItemSort($itemId, $currentUserId, $data) {
        $nextId = isset($data['nextId']) ? intval($data['nextId']) : '';
        $instancyType = isset($data['instancyType']) ? intval($data['instancyType']) : '';
        if (!is_int($nextId) || !in_array($data['instancyType'], [0, 1, 2, 3])) {
            return ['code' => ['0x000008'], 'common'];
        }
        if ($nextId > 0) {
            $targetList = app($this->toDoListRepository)->getDetail($itemId);
            $nextList = app($this->toDoListRepository)->getDetail($nextId);
            if ($targetList->is_finish == 0 && $nextList->is_finish == 1) {
                $minSort = DB::select('select min(sort) as sort from to_do_list where user_id = ? and instancy_type = ?', [$currentUserId, $instancyType]);
                $sort = 0;
                if (!empty($minSort) && is_int($minSort[0]->sort)) {
                    $sort = (int) $minSort[0]->sort - 1;
                }
                $targetResult = DB::update('update to_do_list set sort = ? where item_id = ?', [$sort, $itemId]);
                if ($targetResult) {
                    return true;
                }
            } else {
                $nextResult = DB::update('update to_do_list set sort = sort - 1 where instancy_type = ? and sort <= ? and user_id = ? ', [$instancyType, $nextList->sort, $currentUserId]);
                $targetResult = DB::update('update to_do_list set sort = ? where item_id = ? ', [$nextList->sort, $itemId]);
                if ($nextResult && $targetResult) {
                    return true;
                }
            }
        } else {
            $minSort = DB::select('select min(sort) as sort from to_do_list where user_id = ? and instancy_type = ?', [$currentUserId, $instancyType]);
            $sort = 0;
            if (!empty($minSort) && is_int($minSort[0]->sort)) {
                $sort = (int) $minSort[0]->sort - 1;
            }
            $targetResult = DB::update('update to_do_list set sort = ? where item_id = ?', [$sort, $itemId]);
            if ($targetResult) {
                return true;
            }
        }
        return ['code' => ['0x000003', 'common']];
    }

    public function changeInstancyType($itemId, $currentUserId, $data) {
        $instancyType = isset($data['instancy_type']) ? intval($data['instancy_type']) : '';
        if (!in_array($instancyType, [0, 1, 2, 3])) {
            return ['code' => ['0x000008'], 'common'];
        }
        $itemList = app($this->toDoListRepository)->getDetail($itemId);
        if (empty($itemList)) {
            return ['code' => ['0x039012', 'personalset']];
        }
        $where = [
            'user_id' => $currentUserId,
            'item_id' => $itemId,
        ];
        $updateData = [
            'instancy_type' => $instancyType,
        ];
        if (app($this->toDoListRepository)->updateData($updateData, $where)) {
            return true;
        }
        return ['code' => ['0x000003', 'common']];
    }

    public function getPasswordSecurity() {
        return [
            "login_password_security" => get_system_param('login_password_security'),
            "login_password_security_switch" => get_system_param('login_password_security_switch'),
            "password_length" => get_system_param('password_length'),
        ];
    }
    public function getPasswordLength() {
        if(get_system_param('login_password_security_switch', 0) == 1) {
            return get_system_param('password_length', 6);
        }
        return 6;
    }
    /**
     * 获取用户头像
     * @param $userId
     */
    public function getUserAvatar($userId)
    {
        if (get_system_param('default_avatar_type') == 2) {
            return $this->getUserPersonalAvatar($userId);
        } else {
            $userAvatar = $this->getUserPersonalAvatar($userId);
            if (!empty($userAvatar)) {
                return $userAvatar;
            } else {
                $prefix = 'EO';
                $userIdCode = 0;
                $numberTotal = '';
                $reg = '/^[0-9]+.?[0-9]*$/';
                for ($i = 0; $i < strlen($userId); $i++) {
                    $char = $userId[$i];
                    if (preg_match($reg, $char)) {
                        $numberTotal .= $char;
                    }
                    $charAscii = $this->charCodeAt($userId, $i);
                    $userIdCode += $charAscii;
                }
                $prefixCode = '';
                for ($i = 0; $i < strlen($prefix); $i++) {
                    $charAscii = $this->charCodeAt($prefix, $i);
                    $prefixCode .= $charAscii;
                }
                $numberTotalNumber = $numberTotal === '' ? 0 : intval($numberTotal);
                $img = $prefix . (($userIdCode * intval($prefixCode)) + $numberTotalNumber) . '.png';
                $accessPath = envOverload('ACCESS_PATH', 'access');
                if (!file_exists('./' . $accessPath . '/images/avatar/' . $img)) {
                    return null;
                }
                return ['avatar' => $img, 'attachmentId' => ''];
            }
        }
    }
    private function charCodeAt($str, $index)
    {
        $char = mb_substr($str, $index, 1, 'UTF-8');
        if (mb_check_encoding($char, 'UTF-8')) {
            $ret = mb_convert_encoding($char, 'UTF-32BE', 'UTF-8');
            return hexdec(bin2hex($ret));
        } else {
            return null;
        }
    }
    public function setUserAvatar($data, $userId) {
        if (isset($data['attachment_id'])) {
            $attachmentId = $data['attachment_id'];
            $attachment = app($this->attachmentService)->getOneAttachmentById($attachmentId);
            $attachmentFullPath = rtrim($attachment['attachment_base_path'], "/") . DIRECTORY_SEPARATOR . rtrim($attachment['attachment_relative_path'], "/") . DIRECTORY_SEPARATOR . $attachment['affect_attachment_name'];
            $avatar = imageToBase64($attachmentFullPath);
            $avatarData = [
                'avatar_source' => $attachmentId,
                'avatar_thumb' => $avatar
            ];
            $result = app($this->portalService)->setUserAvatar($avatarData, $userId);
            if ($result) {
                return $avatar;
            }
        }
        return true;
    }

    public function getUserPersonalAvatar($userId) {
        $avatar = [];
        if ($userId == "systemAdmin") {
            $suffix = "png";
        } else {
            if ($userId && ($userInfo = app($this->userInfoRepository)->getDetail($userId))) {
                if ($userInfo && $userInfo->avatar_source) {
                    $info = app($this->attachmentService)->getOneAttachmentById($userInfo->avatar_source);
                    if ($info && $info['thumb_attachment_name']) {
                        $avatar = ['avatar' => $info['thumb_attachment_name'], 'attachmentId' => $userInfo->avatar_source];
                        return $avatar;
                    }
                }
            }
        }
        return '';
    }

    /**
     * 获取md5加密的附件名称
     *
     * @param type $gbkFileName
     * @return type
     */
    private function getMd5FileName($gbkFileName) {
        $name = substr($gbkFileName, 0, strrpos($gbkFileName, "."));

        return md5(time() . $name) . strrchr($gbkFileName, '.');
    }

}
