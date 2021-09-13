<?php
namespace App\EofficeApp\PhotoAlbum\Services;

use App\EofficeApp\Base\BaseService;
use Illuminate\Support\Facades\Cache;

/**
 * 相册管理Service类:提供相册管理相关服务
 *
 * @author qishaobo
 *
 * @since  2015-11-3 创建
 */
class PhotoAlbumService extends BaseService
{

    /** @var object 相册 */
    private $photoAlbumRepository;

    /** @var object 相册分类 */
    private $photoAlbumTypeRepository;

    /** @var object 相册评论 */
    private $photoAlbumCommentRepository;

    /** @var object 相片 */
    private $photoAlbumPictureRepository;

    private $photoAlbumDeptRepository;

    private $photoAlbumUserRepository;

    private $photoAlbumRoleRepository;

    private $photoAlbumManageUserRepository;
    private $attachmentService;
    public function __construct()
    {
        parent::__construct();

        $this->photoAlbumRepository               = 'App\EofficeApp\PhotoAlbum\Repositories\PhotoAlbumRepository';
        $this->photoAlbumRoleRepository           = 'App\EofficeApp\PhotoAlbum\Repositories\PhotoAlbumRoleRepository';
        $this->photoAlbumUserRepository           = 'App\EofficeApp\PhotoAlbum\Repositories\PhotoAlbumUserRepository';
        $this->photoAlbumTypeRepository           = 'App\EofficeApp\PhotoAlbum\Repositories\PhotoAlbumTypeRepository';
        $this->photoAlbumCommentRepository        = 'App\EofficeApp\PhotoAlbum\Repositories\PhotoAlbumCommentRepository';
        $this->photoAlbumPictureRepository        = 'App\EofficeApp\PhotoAlbum\Repositories\PhotoAlbumPictureRepository';
        $this->photoAlbumDeptRepository           = 'App\EofficeApp\PhotoAlbum\Repositories\PhotoAlbumDepartmentRepository';
        $this->photoAlbumManageUserRepository     = 'App\EofficeApp\PhotoAlbum\Repositories\PhotoAlbumManageUserRepository';
        $this->photoAlbumVisitorRepository        = 'App\EofficeApp\PhotoAlbum\Repositories\PhotoAlbumVisitorRepository';
        $this->photoAlbumLaudRepository           = 'App\EofficeApp\PhotoAlbum\Repositories\PhotoAlbumLaudRepository';
        $this->photoAlbumPictureLaudRepository    = 'App\EofficeApp\PhotoAlbum\Repositories\PhotoAlbumPictureLaudRepository';
        $this->photoAlbumPictureCommentRepository = 'App\EofficeApp\PhotoAlbum\Repositories\PhotoAlbumPictureCommentRepository';
        $this->attachmentService                  = 'App\EofficeApp\Attachment\Services\AttachmentService';
    }

    /**
     * 新建相册分类
     *
     * @param  array $data 新建数据
     *
     * @return int 新建数据id
     *
     * @author qishaobo
     *
     * @since  2015-11-19
     */
    public function createPhotoAlbumType($data)
    {
        if ($photoAlbumTypeObj = app($this->photoAlbumTypeRepository)->insertData($data)) {
            return $photoAlbumTypeObj->getKey();
        }

        return ['code' => ['0x000003', 'common']];
    }

    /**
     * 删除相册分类
     *
     * @param  int|string $typeId 相册分类id,多个用逗号隔开
     *
     * @return bool
     *
     * @author qishaobo
     *
     * @since  2015-11-19
     */
    public function deletePhotoAlbumType($typeId)
    {
        $typeIds = array_filter(explode(',', $typeId));
        if (empty($typeIds)) {
            return ['code' => ['0x042006', 'photoalbum']];
        }

        $where           = ['type_id' => [$typeIds, 'in']];
        $emptyTypeIdsObj = app($this->photoAlbumTypeRepository)->getHasPhotoAlbumsTypes($where);
        $emptyTypeIds    = $emptyTypeIdsObj->toArray();

        if (empty($emptyTypeIds)) {
            return 3;
        }

        if (app($this->photoAlbumTypeRepository)->deleteById($emptyTypeIds)) {
            return true;
        }
        return ['code' => ['0x000003', 'common']];
    }

    /**
     * 编辑相册分类
     *
     * @param  int $typeId 相册分类id
     * @param  array $data 编辑相册分类信息
     *
     * @return bool
     *
     * @author qishaobo
     *
     * @since  2015-11-03
     */
    public function editPhotoAlbumType($typeId, $data)
    {
        if (app($this->photoAlbumTypeRepository)->updateData($data, ['type_id' => $typeId])) {
            return true;
        }

        return ['code' => ['0x000003', 'common']];
    }

    /**
     * 排序相册分类
     *
     * @param  array $data 编辑相册分类信息
     *
     * @return bool
     *
     * @author qishaobo
     *
     * @since  2015-11-19
     */
    public function sortPhotoAlbumType($data)
    {
        foreach ($data as $k => $v) {
            app($this->photoAlbumTypeRepository)->updateData(['serial_number' => $v[1]], ['type_id' => $v[0]]);
        }
        return true;
    }

    /**
     * 查询相册分类详情
     *
     * @param  int $typeId 相册分类id
     *
     * @return array 相册分类详情
     *
     * @author qishaobo
     *
     * @since  2015-11-19
     */
    public function getPhotoAlbumTypeDetail($typeId)
    {
        return app($this->photoAlbumTypeRepository)->getphotoAlbumTypeDetail($typeId);
    }

    /**
     * 查询相册分类列表
     *
     * @param  array $param  查询条件
     *
     * @return array 相册分类列表
     *
     * @author qishaobo
     *
     * @since  2015-11-03
     */
    public function getPhotoAlbumTypeList($param = [], $userInfo)
    {
        $param = $this->parseParams($param);
        //获取分类列表
        $result = $this->response(app($this->photoAlbumTypeRepository), 'getTotal', 'getPhotoAlbumTypeList', $param);

        return $result;
    }

    /**
     * 查询所有相册分类列表
     *
     * @param  array $param  查询条件
     *
     * @return array 相册分类列表
     *
     * @author qishaobo
     *
     * @since  2015-12-17
     */
    public function getPhotoAlbumTypesAll($userInfo)
    {
        return app($this->photoAlbumTypeRepository)->getPhotoAlbumTypeCombobox($userInfo, 0);
    }

    /**
     * 新建相册
     *
     * @param  array $data 新建数据
     *
     * @return int 新建数据id
     *
     * @author qishaobo
     *
     * @since  2015-11-03
     */
    public function createPhotoAlbum($data)
    {
        $data['permission'] = isset($data['permission']) ? $data['permission'] : 2;

        $permissionDept = $permissionRole = $permissionUser = $manageUser = [];

        if (isset($data['permission_dept'])) {
            $permissionDept = $data['permission_dept'];
            unset($data['permission_dept']);
        }

        if (isset($data['permission_role'])) {
            $permissionRole = $data['permission_role'];
            unset($data['permission_role']);
        }

        if (isset($data['permission_user'])) {
            $permissionUser = $data['permission_user'];
            unset($data['permission_user']);
        }

        if (isset($data['manage_user'])) {
            $manageUser = $data['manage_user'];
            unset($data['manage_user']);
        }
        if ($photoAlbumObj = app($this->photoAlbumRepository)->insertData($data)) {
            $photoAlbumId = $photoAlbumObj->getKey();
            if (!empty($manageUser)) {
                $manageUserData = [];
                foreach (array_filter($manageUser) as $v) {
                    $manageUserData[] = ['photo_album_id' => $photoAlbumId, 'user_id' => $v];
                }
                app($this->photoAlbumManageUserRepository)->insertMultipleData($manageUserData);
            }
            if ($data['permission'] == 2) {
                if (!empty($permissionDept)) {
                    $deptData = [];
                    foreach (array_filter($permissionDept) as $v) {
                        $deptData[] = ['photo_album_id' => $photoAlbumId, 'dept_id' => $v];
                    }
                    app($this->photoAlbumDeptRepository)->insertMultipleData($deptData);
                }

                if (!empty($permissionRole)) {
                    $roleData = [];
                    foreach (array_filter($permissionRole) as $v) {
                        $roleData[] = ['photo_album_id' => $photoAlbumId, 'role_id' => $v];
                    }
                    app($this->photoAlbumRoleRepository)->insertMultipleData($roleData);

                }

                if (!empty($permissionUser)) {
                    $userData = [];
                    foreach (array_filter($permissionUser) as $v) {
                        $userData[] = ['photo_album_id' => $photoAlbumId, 'user_id' => $v];
                    }
                    app($this->photoAlbumUserRepository)->insertMultipleData($userData);
                }
            }

            return $photoAlbumId;
        }

        return ['code' => ['0x000003', 'common']];
    }

    /**
     * 删除相册
     *
     * @param  int $photoAlbumId 相册id
     *
     * @return bool
     *
     * @author qishaobo
     *
     * @since  2015-11-03
     */
    public function deletePhotoAlbum($photoAlbumId, $userId)
    {
        if (!$this->hasManagePur($userId, $photoAlbumId)) {
            return ['code' => ['0x042002', 'photoalbum']];
        }

        if (app($this->photoAlbumRepository)->deleteById($photoAlbumId)) {
            $where = ['photo_album_id' => [$photoAlbumId]];
            app($this->photoAlbumDeptRepository)->deleteByWhere($where);
            app($this->photoAlbumRoleRepository)->deleteByWhere($where);
            app($this->photoAlbumUserRepository)->deleteByWhere($where);
            app($this->photoAlbumManageUserRepository)->deleteByWhere($where);
            return true;
        }
        return ['code' => ['0x000003', 'common']];
    }

    /**
     * 编辑相册
     *
     * @param  int $photoAlbumId 相册id
     * @param  array $data 编辑相册信息
     *
     * @return bool
     *
     * @author qishaobo
     *
     * @since  2015-11-03
     */
    public function editPhotoAlbum($photoAlbumId, $data, $userId)
    {
        if (!$this->hasManagePur($userId, $photoAlbumId)) {
            return ['code' => ['0x042002', 'photoalbum']];
        }

        $permissionDept = $permissionRole = $permissionUser = $manageUser = [];

        if (isset($data['permission_dept'])) {
            $permissionDept = $data['permission_dept'];
            unset($data['permission_dept']);
        }

        if (isset($data['permission_role'])) {
            $permissionRole = $data['permission_role'];
            unset($data['permission_role']);
        }

        if (isset($data['permission_user'])) {
            $permissionUser = $data['permission_user'];
            unset($data['permission_user']);
        }

        if (isset($data['administrator'])) {
            $administrator = $data['administrator'];
            unset($data['administrator']);
        }
        if (isset($data['manage_user'])) {
            $manageUser = $data['manage_user'];
            unset($data['manage_user']);
        }
        $where = ['photo_album_id' => $photoAlbumId];
        if (app($this->photoAlbumRepository)->updateData($data, $where)) {

            if (isset($data['permission'])) {
                $where = ['photo_album_id' => [$photoAlbumId]];
                app($this->photoAlbumDeptRepository)->deleteByWhere($where);
                app($this->photoAlbumRoleRepository)->deleteByWhere($where);
                app($this->photoAlbumUserRepository)->deleteByWhere($where);
                app($this->photoAlbumManageUserRepository)->deleteByWhere($where);
            }
            if (!empty($manageUser)) {
                $manageUserData = [];
                foreach (array_filter($manageUser) as $v) {
                    $manageUserData[] = ['photo_album_id' => $photoAlbumId, 'user_id' => $v];
                }
                app($this->photoAlbumManageUserRepository)->insertMultipleData($manageUserData);
            }
            if (isset($data['permission']) && $data['permission'] == 2) {
                if (!empty($permissionDept)) {
                    $deptData = [];
                    foreach (array_filter($permissionDept) as $v) {
                        $deptData[] = ['photo_album_id' => $photoAlbumId, 'dept_id' => $v];
                    }
                    app($this->photoAlbumDeptRepository)->insertMultipleData($deptData);
                }

                if (!empty($permissionRole)) {
                    $roleData = [];
                    foreach (array_filter($permissionRole) as $v) {
                        $roleData[] = ['photo_album_id' => $photoAlbumId, 'role_id' => $v];
                    }
                    app($this->photoAlbumRoleRepository)->insertMultipleData($roleData);

                }

                if (!empty($permissionUser)) {
                    $userData = [];
                    foreach (array_filter($permissionUser) as $v) {
                        $userData[] = ['photo_album_id' => $photoAlbumId, 'user_id' => $v];
                    }
                    app($this->photoAlbumUserRepository)->insertMultipleData($userData);
                }
            }

            return true;
        }

        return ['code' => ['0x000003', 'common']];
    }
    public function hasManagePur($userId, $photoAlbumId)
    {
        $photoAlbumObj = app($this->photoAlbumRepository)->getphotoAlbumDetail($photoAlbumId);
        if (empty($photoAlbumObj)) {
            return false;
        }
        if ($photoAlbumObj->creator == $userId) {
            return true;
        }
        if (app($this->photoAlbumManageUserRepository)->hasManagePur($userId, $photoAlbumId)) {
            return true;
        }
        return false;
    }
    /**
     * 查询相册详情
     *
     * @param  int $photoAlbumId 相册id
     *
     * @return array 相册详情
     *
     * @author qishaobo
     *
     * @since  2015-11-19
     */
    public function getPhotoAlbumDetail($photoAlbumId, $userId)
    {
        if ($photoAlbumObj = app($this->photoAlbumRepository)->getphotoAlbumDetail($photoAlbumId)) {
            $data                  = $photoAlbumObj->toArray();
            $photoType             = app($this->photoAlbumTypeRepository)->getphotoAlbumTypeDetail($data['type_id']);
            $data['type_name']     = $photoType ? $photoType->type_name : '';
            $data['manage_auth']   = $this->hasManagePur($userId, $photoAlbumId) ? 1 : 0;
            $laudCount             = app($this->photoAlbumLaudRepository)->getAllLaudCount($photoAlbumId);
            $data['lauds']         = $laudCount == null ? 0 : $laudCount;
            $data['is_laud']       = app($this->photoAlbumLaudRepository)->isLaud($userId, $photoAlbumId) ? 0 : 1;
            $commentCount          = app($this->photoAlbumCommentRepository)->getCommentsCountByAlbumId($photoAlbumId);
            $data['comment_count'] = $commentCount == null ? 0 : $commentCount;
            if ($data['has_many_dept']) {
                $data['permission_dept'] = array_column($data['has_many_dept'], 'dept_id');
            }

            if ($data['has_many_role']) {
                $data['permission_role'] = array_column($data['has_many_role'], 'role_id');
            }

            if ($data['has_many_user']) {
                $data['permission_user'] = array_column($data['has_many_user'], 'user_id');
            }
            if ($data['has_many_manage_user']) {
                $data['manage_user'] = array_column($data['has_many_manage_user'], 'user_id');
            }
            $pictureCover = $this->getPictureCover($photoAlbumId);
            if($pictureCover){
                $oldPathThumb = $pictureCover['attachment_base_path']
                        . DIRECTORY_SEPARATOR . $pictureCover['attachment_relative_path']
                        . DIRECTORY_SEPARATOR . $pictureCover['thumb_attachment_name'];
                $pathThumb = $this->transEncoding($oldPathThumb, 'GBK');
                if (file_exists($pathThumb) && is_file($pathThumb)) {
                    $data['thumb_attachment_name'] = imageToBase64($pathThumb);
                }
            }
            return $data;
        }
        return '';
    }

    /**
     * 查询相册列表
     *
     * @param  array $param  查询条件
     *
     * @return array 相册列表
     *
     * @author qishaobo
     *
     * @since  2015-11-19
     */
    public function getPhotoAlbumList($param = [], $userInfo)
    {
        $param                        = $this->parseParams($param);
        $param['search']['user_info'] = $userInfo;
        if (isset($param['fields'])) {
            if (!in_array('creator', $param['fields'])) {
                $param['fields'][] = 'creator';
            }
        }
        $data = $this->response(app($this->photoAlbumRepository), 'getPhotoAlbumTotal', 'getPhotoAlbumList', $param);
        if (!empty($data['list'])) {
            foreach ($data['list'] as $k => $v) {
                $laudCount                   = app($this->photoAlbumLaudRepository)->getAllLaudCount($v['photo_album_id']);
                $data['list'][$k]['lauds']   = $laudCount == null ? 0 : $laudCount;
                $data['list'][$k]['is_laud'] = app($this->photoAlbumLaudRepository)->isLaud($userInfo['user_id'], $v['photo_album_id']) ? 0 : 1;
                if ($v['creator'] == $userInfo['user_id']) {
                    $data['list'][$k]['manage_auth'] = 1;
                } else {
                    if (app($this->photoAlbumManageUserRepository)->hasManagePur($userInfo['user_id'], $v['photo_album_id'])) {
                        $data['list'][$k]['manage_auth'] = 1;
                    } else {
                        $data['list'][$k]['manage_auth'] = 0;
                    }
                }
                $pictureCover = $this->getPictureCover($v['photo_album_id']);
                if($pictureCover){
                    $oldPathThumb = $pictureCover['attachment_base_path']
                            . DIRECTORY_SEPARATOR . $pictureCover['attachment_relative_path']
                            . DIRECTORY_SEPARATOR . $pictureCover['thumb_attachment_name'];
                    $pathThumb = $this->transEncoding($oldPathThumb, 'GBK');
                    if (file_exists($pathThumb) && is_file($pathThumb)) {
                        $imageSize = getimagesize($pathThumb);
                        $data['list'][$k]['width'] = $imageSize[0];
                        $data['list'][$k]['height'] = $imageSize[1];
                        $data['list'][$k]['thumb_attachment_name'] = imageToBase64($pathThumb);
                    }
                }
            }
        }

        return $data;
    }
    private function getPictureCover($photoAlbumId)
    {
        if(!$attachment = app($this->photoAlbumPictureRepository)->getCoverAttachmentId($photoAlbumId)) {
            $attachment = app($this->photoAlbumPictureRepository)->getFirstAttachmentId($photoAlbumId);
        }

        if($attachment){
            return app($this->attachmentService)->getOneAttachmentByRelId($attachment->attachment_id, false);
        }

        return '';
    }
    public function getManageAlbums($userId, $params)
    {
        $param           = $this->parseParams($params);
        $fields          = isset($param['fields']) ? $param['fields'] : ['*'];
        $manageAlbumIds  = app($this->photoAlbumManageUserRepository)->getManageAlbumIdsByUserId($userId);
        $creatorAlbumIds = app($this->photoAlbumRepository)->getAlbumIdsByUserId($userId);
        $manageAlbumIds  = array_merge($manageAlbumIds, $creatorAlbumIds);
        return app($this->photoAlbumRepository)->getManageAlbums($fields, $manageAlbumIds);
    }

    public function getManageAlbumsByAlbumId($albumId,$userId, $params)
    {
        $param           = $this->parseParams($params);
        $fields          = isset($param['fields']) ? $param['fields'] : ['*'];
        $manageAlbumIds  = app($this->photoAlbumManageUserRepository)->getManageAlbumIdsByUserId($userId);
        $creatorAlbumIds = app($this->photoAlbumRepository)->getAlbumIdsByUserId($userId);
        $manageAlbumIds  = array_merge($manageAlbumIds, $creatorAlbumIds);
        foreach ($manageAlbumIds as $key => $id){
            if($albumId == $id){
                unset($manageAlbumIds[$key]);
            }
        }
        return app($this->photoAlbumRepository)->getManageAlbums($fields, $manageAlbumIds);
    }
    public function laudAlbum($laud, $photoAlbumId, $userId)
    {
        if (app($this->photoAlbumLaudRepository)->laudExists($userId, $photoAlbumId)) {
            return app($this->photoAlbumLaudRepository)->updateData(['type' => $laud], ['photo_album_id' => $photoAlbumId, 'user_id' => $userId]);
        } else {
            return app($this->photoAlbumLaudRepository)->insertData(['photo_album_id' => $photoAlbumId, 'user_id' => $userId, 'type' => $laud]);
        }
    }
    public function laudPicture($laud, $pictureId, $userId)
    {
        if (app($this->photoAlbumPictureLaudRepository)->laudExists($userId, $pictureId)) {
            return app($this->photoAlbumPictureLaudRepository)->updateData(['type' => $laud], ['picture_id' => $pictureId, 'user_id' => $userId]);
        } else {
            return app($this->photoAlbumPictureLaudRepository)->insertData(['picture_id' => $pictureId, 'user_id' => $userId, 'type' => $laud]);
        }
    }
    public function listPictureComment($pictureId, $userId, $params)
    {
        if ($pictureId == 0) {
            return ['code' => ['0x042003', 'photoalbum']];
        }
        $list = [];
        if ($comments = app($this->photoAlbumPictureCommentRepository)->getCommentList($pictureId, $this->parseParams($params))) {
            foreach ($comments as $comment) {
                $comment->user_name = get_user_simple_attr($comment->user_id);
                $reply              = app($this->photoAlbumPictureCommentRepository)->getChildrenComments($comment->comment_id);
                $comment->reply     = $reply;
                $list[]             = $comment;
            }
        }
        $total = app($this->photoAlbumPictureCommentRepository)->getCommentsCount($pictureId);
        return ['total' => $total, 'list' => $list];
    }
    public function addPictureComment($data, $pictureId, $userId)
    {
        $commonData = [
            'parent_id'       => isset($data['parent_id']) ? $data['parent_id'] : 0,
            'picture_id'      => $pictureId,
            'user_id'         => $userId,
            'comment_content' => $data['comment_content'],
        ];
        return app($this->photoAlbumPictureCommentRepository)->insertData($commonData);
    }
    public function deletePictureComment($pictureId, $commentId, $userId)
    {
        if (!app($this->photoAlbumPictureCommentRepository)->checkDeleteCommentAuth($commentId, $userId)) {
            return ['code' => ['0x042002', 'photoalbum']];
        }
        if (app($this->photoAlbumPictureCommentRepository)->deleteById($commentId)) {
            return true;
        }
        return ['code' => ['0x000003', 'common']];
    }
    /**
     * 添加评论
     *
     * @param  int $photoAlbumId 相册id
     * @param  array $input 新建数据
     *
     * @return int|array 新建数据id|错误码
     *
     * @author qishaobo
     *
     * @since  2015-11-04
     */
    public function createComments($photoAlbumId, $input, $userId)
    {
        if(!isset($input['attachments']) || empty($input['attachments'])) {
            if(!isset($input['comment_content']) || $input['comment_content'] == ''){
                return ['code' => ['0x042007', 'photoalbum']];
            }
        }
        $data = [
            'user_id'         => $userId,
            'comment_date'    => date("Y-m-d H:i:s"),
            'photo_album_id'  => $photoAlbumId,
            'comment_content' => $input['comment_content'],
        ];

        if ($photoAlbumCommentObj = app($this->photoAlbumCommentRepository)->insertData($data)) {
            if(!empty($input['attachments'])){
                app($this->attachmentService)->attachmentRelation("photo_album_comment", $photoAlbumCommentObj->comment_id,$input['attachments']);
            }
            $commentId = $photoAlbumCommentObj->getKey();
            return ['comment_id' => $commentId];
        }

        return ['code' => ['0x000003', 'common']];
    }

    /**
     * 删除评论
     *
     * @param  int $commentId 评论id
     *
     * @return bool 操作是否成功
     *
     * @author qishaobo
     *
     * @since  2015-11-04
     */
    public function deleteComment($commentId, $userId)
    {
        if (!app($this->photoAlbumCommentRepository)->hasPermission($commentId, $userId)) {
            return ['code' => ['0x042002', 'photoalbum']];
        }

        if (app($this->photoAlbumCommentRepository)->deleteById($commentId)) {
            return true;
        }
        return ['code' => ['0x000003', 'common']];
    }

    /**
     * 获取评论列表
     *
     * @param  int $photoAlbumId 相册id
     *
     * @return array 评论列表
     *
     * @author qishaobo
     *
     * @since  2015-11-04
     */
    public function getCommentsList($photoAlbumId, $userId)
    {
        $param = [
            'user_id' => $userId,
            'search'  => ['photo_album_id' => [$photoAlbumId]],
        ];
        $lists =  app($this->photoAlbumCommentRepository)->getCommentsList($param);
        if(count($lists) > 0) {
            foreach($lists as $key => $item) {
                $lists[$key]['attachments'] = app($this->attachmentService)->getAttachmentIdsByEntityId(['entity_table'=>'photo_album_comment', 'entity_id'=>$item['comment_id']]);
            }
        }
        return $lists;
    }

    /**
     * 添加照片
     *
     * @param  int $photoAlbumId 相册id
     * @param  array $data 新建数据
     *
     * @return int|array 新建数据id|错误码
     *
     * @author qishaobo
     *
     * @since  2015-11-04
     */
    public function createPicture($photoAlbumId, $data, $userId)
    {
        if (!empty($data)) {
            $pictures = app($this->attachmentService)->getMoreAttachmentById($data, false);
            $albumSizeArray = app($this->photoAlbumRepository)->getAlbumSize(['photo_album_id' => [$photoAlbumId]]);

            $albumSizeObj = $albumSizeArray[0];
            if ($albumSizeObj->photo_album_size == 0) {
                return ['code' => ['0x042005', 'photoalbum']];
            }
            if (count($albumSizeObj->withPictures)) {
                $picturesSize = $albumSizeObj->withPictures[0]->picture_total_size;
            } else {
                $picturesSize = 0;
            }
            $albumSize  = $albumSizeObj->photo_album_size * 1024 * 1024;
            $uploadSize = array_sum(array_column($pictures, 'attachment_size'));
            if ($picturesSize + $uploadSize > $albumSize) {
                return ['code' => ['0x042005', 'photoalbum']];
            }

            $datas = [];
            $date  = date("Y-m-d H:i:s");
            foreach ($pictures as $picture) {
                $datas[] = [
                    'picture_name'   => $picture['attachment_name'],
                    'picture_size'   => $picture['attachment_size'],
                    'attachment_id'  => $picture['id'],
                    'photo_album_id' => $photoAlbumId,
                    'created_at'     => $date,
                    'updated_at'     => $date,
                    'creator'        => $userId,
                ];
            }

            if (app($this->photoAlbumPictureRepository)->insertMultipleData($datas)) {
                return true;
            }
        }

        return ['code' => ['0x000003', 'common']];
    }

    /**
     * 删除照片
     *
     * @param  int $pictureId 照片id
     *
     * @return bool 操作是否成功
     *
     * @author qishaobo
     *
     * @since  2015-11-04
     */
    public function deletePicture($pictureId, $userId)
    {
        $pictureIds = array_filter(explode(',', $pictureId));

        if (empty($pictureIds)) {
            return ['code' => ['0x042003', 'photoalbum']];
        }
        $flag = true;
        foreach ($pictureIds as $pId) {
            if (!$this->hasPicManagePur($pId, $userId)) {
                $flag = false;
                break;
            }
        }
        if (!$flag) {
            return ['code' => ['0x042002', 'photoalbum']];
        }

        if (app($this->photoAlbumPictureRepository)->deleteById($pictureIds)) {
            return true;
        }
        return ['code' => ['0x000003', 'common']];
    }

    /**
     * 编辑照片
     *
     * @param  int $photoAlbumId 相册id
     * @param  int $pictureId 照片id
     *
     * @return bool 操作是否成功
     *
     * @author qishaobo
     *
     * @since  2015-11-04
     */
    public function editPhotoAlbumsPictures($photoAlbumId, $pictureId, $operation, $userId)
    {
        if (!$this->hasPicManagePur($pictureId, $userId)) {
            return ['code' => ['0x042002', 'photoalbum']];
        }

        if ($operation == 'top') {
            $data = ['top' => date("Y-m-d H:i:s")];
        } else if ($operation == 'cancel-top') {
            $data = ['top' => "0000-00-00 00:00:00"];
        } else if ($operation == 'cover') {
            $data = ['picture_cover' => 0];
            app($this->photoAlbumPictureRepository)->updateData($data, ['photo_album_id' => $photoAlbumId]);
            $data = ['picture_cover' => 1];
        } else if ($operation == 'cancel-cover') {
            $data = ['picture_cover' => 0];
        } else {
            return ['code' => ['0x042004', 'photoalbum']];
        }
        if (app($this->photoAlbumPictureRepository)->updateData($data, ['picture_id' => $pictureId])) {
            return true;
        }

        return ['code' => ['0x000003', 'common']];
    }
    public function editPicture($data, $pictureId, $userId)
    {
        if (!$this->hasPicManagePur($pictureId, $userId)) {
            return ['code' => ['0x042002', 'photoalbum']];
        }

        $saveData = [
            'picture_name' => isset($data['picture_name']) ? $data['picture_name'] . (isset($data['prefix']) ? '.' . $data['prefix'] : '.png') : (time() . (isset($data['prefix']) ? '.' . $data['prefix'] : '.png')),
            'remark'       => isset($data['remark']) ? $data['remark'] : '',
        ];
        if (app($this->photoAlbumPictureRepository)->updateData($saveData, ['picture_id' => $pictureId])) {
            return true;
        }

        return ['code' => ['0x000003', 'common']];
    }
    public function migratePicture($pictureIds, $photoAlbumId, $userId)
    {
        if (!$this->hasManagePur($userId, $photoAlbumId)) {
            return ['code' => ['0x042002', 'photoalbum']];
        }

        if (empty($pictureIds)) {
            return ['code' => ['0x042003', 'photoalbum']];
        }
        //转移时也要判断相册大小
        $uploadSize=app($this->photoAlbumPictureRepository)->getPicturesSizeByPictureIds($pictureIds);
        $albumSizeArray = app($this->photoAlbumRepository)->getAlbumSize(['photo_album_id' => [$photoAlbumId]]);
        $albumSizeObj = $albumSizeArray[0];
        if ($albumSizeObj->photo_album_size == 0) {
            return ['code' => ['0x042005', 'photoalbum']];
        }
        if (count($albumSizeObj->withPictures)) {
            $picturesSize = $albumSizeObj->withPictures[0]->picture_total_size;
        } else {
            $picturesSize = 0;
        }
        $albumSize  = $albumSizeObj->photo_album_size * 1024 * 1024;
        if ($picturesSize + $uploadSize > $albumSize) {
            return ['code' => ['0x042005', 'photoalbum']];
        }

        foreach ($pictureIds as $pictureId) {
            app($this->photoAlbumPictureRepository)->updateData(['photo_album_id' => $photoAlbumId, 'picture_cover' => 0], ['picture_id' => $pictureId]);
        }

        return true;
    }

    public function hasPicManagePur($pictureId, $userId)
    {
        $pictureInfo  = app($this->photoAlbumPictureRepository)->getDetail($pictureId);
        if($pictureInfo) {
            $photoAlbumId = $pictureInfo->photo_album_id;
            if ($this->hasManagePur($userId, $photoAlbumId)) {
                return true;
            }
        }
        return false;
    }
    public function getPictureDetail($pictureId, $own)
    {
        $pictureInfo = app($this->photoAlbumPictureRepository)->getDetail($pictureId);
        if ($this->hasAlbumViewPurview($own['user_id'], $own['dept_id'], $own['role_id'], $pictureInfo->photo_album_id)) {
            return $pictureInfo;
        }

        return ['code' => ['0x042002', 'photoalbum']];
    }
    /**
     * 获取照片列表
     *
     * @param  int $photoAlbumId 相册id
     * @param  array $param  查询条件
     *
     * @return array 照片列表
     *
     * @author qishaobo
     *
     * @since  2015-11-04
     */
    public function getPicturesList($photoAlbumId, $param, $own)
    {
        $userId = $own['user_id'];
        $param  = $this->parseParams($param);
        if (!app($this->photoAlbumRepository)->getDetail($photoAlbumId) && isset($param['source']) && $param['source'] == 'portal') {
            $photoAlbumId = 0;
        }

        if ($photoAlbumId != 0) {
            if (!app($this->photoAlbumRepository)->getDetail($photoAlbumId)) {
                return ['code' => ['0x042002', 'photoalbum']];
            }
            if (!$this->hasAlbumViewPurview($userId, $own['dept_id'], $own['role_id'], $photoAlbumId)) {
                return ['code' => ['0x042002', 'photoalbum']];
            }

            $param['search']['photo_album_id'] = [$photoAlbumId];
            $this->addVisitor($photoAlbumId, $userId);
            //判断来源是否为门户
            if (!(isset($param['source']) && $param['source'] == 'portal')) {
                $manageAuth = $this->hasManagePur($userId, $photoAlbumId);
            }
        } else {
            //能查看的相册的id
            $photoAlbumIds = app($this->photoAlbumRepository)->getAllShowAlbumId($own);
            //搜索页面才会传这个值
            if (isset($param['search']['photo_album_id'][0]) && is_array($param['search']['photo_album_id'][0])) {
                $photoAlbumIds = array_intersect($photoAlbumIds, $param['search']['photo_album_id'][0]);
            }
            $param['search']['photo_album_id'] = [$photoAlbumIds, 'in'];

            //能管理的相册的id
            $manageAlbumIds=$this->getUserManageAlbunIds($userId);
        }

        $data = $this->response(app($this->photoAlbumPictureRepository), 'getTotal', 'getPicturesList', $param);
        if (!empty($data['list'])) {
            $list = [];
            list($albumNames, $userNames) = $this->getAlbumNameAndCreatorName($data['list']);
            foreach ($data['list'] as $k => $v) {
                //添加相册名称和上传人名称
                $v->album_name = $albumNames[$v->photo_album_id] ?? '';
                $v->creator_name = $userNames[$v->creator] ?? '';
                //判断来源是否为门户
                if (!(isset($param['source']) && $param['source'] == 'portal')) {
                    $laudCount        = app($this->photoAlbumPictureLaudRepository)->getAllLaudCount($v->picture_id);
                    $v->lauds         = $laudCount == null ? 0 : $laudCount;
                    $v->is_laud       = app($this->photoAlbumPictureLaudRepository)->isLaud($userId, $v->picture_id) ? 0 : 1;
                    $commentCount     = app($this->photoAlbumPictureCommentRepository)->getCommentsCount($v->picture_id);
                    $v->comment_count = $commentCount == null ? 0 : $commentCount;
                    $v->manage_auth = 0;
                    if (isset($manageAuth) && $manageAuth) {
                        $v->manage_auth = 1;
                    }
                    if (isset($manageAlbumIds) && in_array($v->photo_album_id, $manageAlbumIds)) {
                        $v->manage_auth = 1;
                    }
                }
                $v->thumb_path = '';
                $v->sWidth     = '';
                $v->sHeight    = '';
                $attachment = app($this->attachmentService)->getOneAttachmentByRelId($v->attachment_id, false);
                if($attachment){
                    $v->image_id = $attachment['attachment_id'];
                    $oldPathThumb = $attachment['attachment_base_path']
                            . DIRECTORY_SEPARATOR . $attachment['attachment_relative_path']
                            . DIRECTORY_SEPARATOR . $attachment['thumb_attachment_name'];
                    $pathThumb = $this->transEncoding($oldPathThumb, 'GBK');
                    if (file_exists($pathThumb) && is_file($pathThumb)) {
                        $imageSize = getimagesize($pathThumb);
                        $v->sWidth = $imageSize[0];
                        $v->sHeight = $imageSize[1];
                        Cache::forever('thumb_'.$v->attachment_id,$pathThumb);
                        $v->thumb_path = true;
                    }
                }
                $list[] = $v;
            }
            $data['list'] = $list;
        }

        return $data;
    }
    private function getAlbumNameAndCreatorName($list)
    {
        $userIds = array_unique(array_column($list->toArray(), 'creator'));
        $albumIds = array_unique(array_column($list->toArray(), 'photo_album_id'));
        $users = app('App\EofficeApp\User\Repositories\UserRepository')->getUserNames($userIds)->toArray();
        $albums = app($this->photoAlbumRepository)->getAlbumNameByIds($albumIds);
        $usersMap = [];
        if ($users) {
            foreach ($users as $user) {
                $usersMap[$user['user_id']] = $user['user_name'];
            }
        }
        $albumMap = [];
        if ($albums) {
            foreach ($albums as $album) {
                $albumMap[$album['photo_album_id']] = $album['photo_album_name'];
            }
        }
        return [$albumMap, $usersMap];
    }
    private function transEncoding($string, $target)
    {
        $encoding = mb_detect_encoding($string, ['ASCII', 'UTF-8', 'GB2312', 'GBK', 'BIG5']);

        return iconv($encoding, $target, $string);
    }
    private function hasAlbumViewPurview($userId, $deptId, $roleId, $photoAlbumId)
    {
        if (app($this->photoAlbumRepository)->isViewAllUser($photoAlbumId)) {
            return true;
        }
        if(app($this->photoAlbumRepository)->isCreator($photoAlbumId, $userId)){
            return true;
        }
        if (app($this->photoAlbumUserRepository)->hasUserPurviewOfAlbum($userId, $photoAlbumId)) {
            return true;
        }

        if (app($this->photoAlbumDeptRepository)->hasDeptPurviewOfAlbum($deptId, $photoAlbumId)) {
            return true;
        }

        if (app($this->photoAlbumRoleRepository)->hasRolePurviewOfAlbum($roleId, $photoAlbumId)) {
            return true;
        }

        if (app($this->photoAlbumManageUserRepository)->hasManagePur($userId, $photoAlbumId)) {
            return true;
        }

        return false;
    }
    public function addVisitor($photoAlbumId, $userId)
    {
        if (!app($this->photoAlbumVisitorRepository)->visitorExists($photoAlbumId, $userId)) {
            return app($this->photoAlbumVisitorRepository)->insertData(['user_id' => $userId, 'photo_album_id' => $photoAlbumId, 'visit_time' => date('Y-m-d H:i:s')]);
        } else {
            return app($this->photoAlbumVisitorRepository)->updateData(['visit_time' => date('Y-m-d H:i:s')], ['user_id' => $userId, 'photo_album_id' => $photoAlbumId]);
        }
    }
    public function visitorList($photoAlbumId, $param)
    {
        $param = $this->parseParams($param);
        $param['search']['photo_album_id'] = [$photoAlbumId];
        $rest = $this->response(app($this->photoAlbumVisitorRepository), 'getTotal', 'photoAlbumVisitorList', $param);
        $rest['total'] = count($rest['list']);
        return $rest;
    }
    public function getPhotoThumb($attachment_id)
    {
        //判断缓存中是否存了图片的缩略图地址
        if(Cache::get('thumb_'.$attachment_id)){
            $pathThumb=Cache::get('thumb_'.$attachment_id);
        }else{
            $attachment=app($this->attachmentService)->getOneAttachmentByRelId($attachment_id,false);
            if(!$attachment){
                return false;
            }
            $pathThumb = $attachment['attachment_base_path']
                . DIRECTORY_SEPARATOR . $attachment['attachment_relative_path']
                . DIRECTORY_SEPARATOR . $attachment['thumb_attachment_name'];
            Cache::forever('thumb_'.$attachment_id,$pathThumb);
        }

        $pathThumb = $this->transEncoding($pathThumb, 'GBK');
        if (!file_exists($pathThumb)) {
            return ['code' => ['0x011018', 'upload']];
        }
        $file = fopen($pathThumb, "r");
        header("Content-Type: image/jpeg");
        echo fread($file, filesize($pathThumb));
        fclose($file);
        return true;
    }

    /**
     * 获取用户有管理权限的相册id
     * @param $userId
     */
    private function getUserManageAlbunIds($userId)
    {
        $manager = app($this->photoAlbumManageUserRepository)->getManageAlbumIdsByUserId($userId);
        $creator = app($this->photoAlbumRepository)->getAlbumIdsByUserId($userId);
        return array_unique(array_merge($manager, $creator));
    }
}
