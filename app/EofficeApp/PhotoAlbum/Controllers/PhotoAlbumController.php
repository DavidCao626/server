<?php

namespace App\EofficeApp\PhotoAlbum\Controllers;

use Illuminate\Http\Request;
use App\EofficeApp\PhotoAlbum\Requests\PhotoAlbumRequest;
use App\EofficeApp\PhotoAlbum\Services\PhotoAlbumService;
use App\EofficeApp\Base\Controller;
/**
 * 相册管理控制器:提供相册管理理模块请求的实现方法
 *
 * @author qishaobo
 *
 * @since  2015-11-3 创建
 */
class PhotoAlbumController extends Controller
{
    /** @var object 相册service对象*/
    private $photoAlbumService;

    public function __construct(
        Request $request,
        PhotoAlbumRequest $photoAlbumRequest,
        PhotoAlbumService $photoAlbumService
    ) {
        parent::__construct();
        $userInfo = $this->own;
        $this->userId = $userInfo['user_id'];
        $this->request = $request;
        $this->photoAlbumService = $photoAlbumService;
        $this->formFilter($request, $photoAlbumRequest);
    }

    /**
     * 新建相册分类
     *
     * @return int 新建相册分类id
     *
     * @author qishaobo
     *
     * @since  2015-11-03
     */
    function createPhotoAlbumTypes( )
    {
        $data = $this->request->all();
        $data['creator'] = $this->userId;
        $result = $this->photoAlbumService->createPhotoAlbumType($data);
        return $this->returnResult($result);
    }

    /**
     * 删除相册分类
     *
     * @param  int|string $typeId 相册分类id,多个用逗号隔开
     *
     * @return bool 操作是否成功
     *
     * @author qishaobo
     *
     * @since  2015-11-03
     */
    function deletePhotoAlbumTypes($typeId)
    {
        $result = $this->photoAlbumService->deletePhotoAlbumType($typeId);
        return $this->returnResult($result);
    }

    /**
     * 编辑相册分类
     *
     * @param  int $typeId 相册分类id
     *
     * @return array 相册分类详情
     *
     * @author: qishaobo
     *
     * @since：2015-11-03
     */
    function editPhotoAlbumTypes($typeId)
    {
        $result = $this->photoAlbumService->editPhotoAlbumType($typeId, $this->request->all());
        return $this->returnResult($result);
    }

    /**
     * 相册分类排序
     *
     * @param  int $typeId 相册分类id
     *
     * @return array 相册分类详情
     *
     * @author: qishaobo
     *
     * @since：2015-11-19
     */
    function sortPhotoAlbumTypes()
    {
        $result = $this->photoAlbumService->sortPhotoAlbumType($this->request->all());
        return $this->returnResult($result);
    }

    /**
     * 查询相册分类详情
     *
     * @param  int 相册分类id
     *
     * @return array 相册分类详情
     *
     * @author qishaobo
     *
     * @since  2015-11-03
     */
    function getPhotoAlbumTypes($typeId)
    {
        $result = $this->photoAlbumService->getPhotoAlbumTypeDetail($typeId);
        return $this->returnResult($result);
    }

    /**
     * 获取相册分类列表
     *
     * @return array 相册分类列表
     *
     * @author qishaobo
     *
     * @since  2015-11-03
     */
    function getIndexPhotoAlbumTypes()
    {
        $result = $this->photoAlbumService->getPhotoAlbumTypeList($this->request->all(), $this->own);
        return $this->returnResult($result);
    }

    /**
     * 获取所有相册分类列表
     *
     * @return array 相册分类列表
     *
     * @author qishaobo
     *
     * @since  2015-12-17
     */
    function getPhotoAlbumTypesAll()
    {
        $result = $this->photoAlbumService->getPhotoAlbumTypesAll($this->own);
        return $this->returnResult($result);
    }

    /**
     * 新建相册
     *
     * @return int 新建相册id
     *
     * @author qishaobo
     *
     * @since  2015-11-03
     */
    function createPhotoAlbums( )
    {
        $data = $this->request->all();
        $data['creator'] = $this->userId;
        $result = $this->photoAlbumService->createPhotoAlbum($data);
        return $this->returnResult($result);
    }

    /**
     * 删除相册
     *
     * @param  int|string $photoAlbumId 相册id,多个用逗号隔开
     *
     * @return bool 操作是否成功
     *
     * @author qishaobo
     *
     * @since  2015-11-03
     */
    function deletePhotoAlbums($photoAlbumId)
    {
        $result = $this->photoAlbumService->deletePhotoAlbum($photoAlbumId, $this->userId);
        return $this->returnResult($result);
    }

    /**
     * 编辑相册
     *
     * @param  int $photoAlbumId 相册id
     *
     * @return array 相册详情
     *
     * @author: qishaobo
     *
     * @since：2015-11-03
     */
    function editPhotoAlbums($photoAlbumId)
    {
        $result = $this->photoAlbumService->editPhotoAlbum($photoAlbumId, $this->request->all(), $this->userId);
        return $this->returnResult($result);
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
     * @since  2015-11-03
     */
    function getPhotoAlbums($photoAlbumId)
    {
        $result = $this->photoAlbumService->getPhotoAlbumDetail($photoAlbumId,$this->userId);
        return $this->returnResult($result);
    }

    /**
     * 获取相册列表
     *
     * @return array 相册列表
     *
     * @author qishaobo
     *
     * @since  2015-11-03
     */
    function getIndexPhotoAlbums()
    {
        $result = $this->photoAlbumService->getPhotoAlbumList($this->request->all(), $this->own);
        return $this->returnResult($result);
    }

    /**
     * 添加相册评论
     *
     * @param  int $photoAlbumId 相册id
     *
     * @return int 新建评论id
     *
     * @author qishaobo
     *
     * @since  2015-11-03
     */
    function createPhotoAlbumsComments($photoAlbumId)
    {
        $result = $this->photoAlbumService->createComments($photoAlbumId, $this->request->all(), $this->userId);
        return $this->returnResult($result);
    }

    /**
     * 删除相册评论
     *
     * @param  int $photoAlbumId 相册id
     * @param  int $commentId 评论id
     *
     * @return bool 操作是否成功
     *
     * @author qishaobo
     *
     * @since  2015-11-03
     */
    function deletePhotoAlbumsComments($photoAlbumId, $commentId)
    {
        $result = $this->photoAlbumService->deleteComment($commentId, $this->userId);
        return $this->returnResult($result);
    }

    /**
     * 获取相册评论列表
     *
     * @param  int $photoAlbumId 相册id
     *
     * @return array 评论列表
     *
     * @author qishaobo
     *
     * @since  2015-11-03
     */
    function getIndexPhotoAlbumsComments($photoAlbumId)
    {
        $result = $this->photoAlbumService->getCommentsList($photoAlbumId, $this->userId);
        return $this->returnResult($result);
    }

    /**
     * 添加照片
     *
     * @param  int $photoAlbumId 相册id
     *
     * @return int 新建照片id
     *
     * @author qishaobo
     *
     * @since  2015-11-04
     */
    function createPhotoAlbumsPictures($photoAlbumId)
    {
        $result = $this->photoAlbumService->createPicture($photoAlbumId, $this->request->all(),$this->userId);
        return $this->returnResult($result);
    }

    /**
     * 删除照片
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
    function deletePhotoAlbumsPictures($photoAlbumId, $pictureId)
    {
        $result = $this->photoAlbumService->deletePicture($pictureId, $this->userId);
        return $this->returnResult($result);
    }

    /**
     * 照片置顶
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
    function editPicturesTop($photoAlbumId, $pictureId)
    {
        $result = $this->photoAlbumService->editPhotoAlbumsPictures($photoAlbumId, $pictureId, 'top', $this->userId);
        return $this->returnResult($result);
    }

    /**
     * 取消照片置顶
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
    function cancelPicturesTop($photoAlbumId, $pictureId)
    {
        $result = $this->photoAlbumService->editPhotoAlbumsPictures($photoAlbumId, $pictureId, 'cancel-top', $this->userId);
        return $this->returnResult($result);
    }

    /**
     * 照片设为封面
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
    function editPicturesCover($photoAlbumId, $pictureId)
    {
        $result = $this->photoAlbumService->editPhotoAlbumsPictures($photoAlbumId, $pictureId, 'cover', $this->userId);
        return $this->returnResult($result);
    }

    /**
     * 照片取消封面
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
    function cancelPicturesCover($photoAlbumId, $pictureId)
    {
        $result = $this->photoAlbumService->editPhotoAlbumsPictures($photoAlbumId, $pictureId, 'cancel-cover', $this->userId);
        return $this->returnResult($result);
    }

    /**
     * 获取照片列表
     *
     * @param  int $photoAlbumId 相册id
     *
     * @return array 照片列表
     *
     * @author qishaobo
     *
     * @since  2015-11-04
     */
    function getIndexPhotoAlbumsPictures($photoAlbumId)
    {
        $result = $this->photoAlbumService->getPicturesList($photoAlbumId, $this->request->all(), $this->own);
        return $this->returnResult($result);
    }
    public function visitorList($photoAlbumId)
    {
        $result = $this->photoAlbumService->visitorList($photoAlbumId, $this->request->all());

        return $this->returnResult($result);
    }
    public function getPictureDetail($pictureId)
    {
        $result = $this->photoAlbumService->getPictureDetail($pictureId, $this->own);

        return $this->returnResult($result);
    }
    public function editPicture($pictureId)
    {
        $result = $this->photoAlbumService->editPicture($this->request->all(),$pictureId, $this->userId);

        return $this->returnResult($result);
    }
    public function getManageAlbums(){
        $result = $this->photoAlbumService->getManageAlbums($this->userId,$this->request->all());

        return $this->returnResult($result);
    }

    public function getManageAlbumsByAlbumId($albumId){
        $result = $this->photoAlbumService->getManageAlbumsByAlbumId($albumId,$this->userId,$this->request->all());
        return $this->returnResult($result);
    }

    public function migratePicture($photoAlbumId)
    {
        $result = $this->photoAlbumService->migratePicture($this->request->input('picture_ids',[]),$photoAlbumId,$this->userId);

        return $this->returnResult($result);
    }
    public function laudAlbum($photoAlbumId,$laud)
    {
        $result = $this->photoAlbumService->laudAlbum($laud,$photoAlbumId,$this->userId);

        return $this->returnResult($result);
    }
    public function laudPicture($pictureId,$laud)
    {
        $result = $this->photoAlbumService->laudPicture($laud,$pictureId,$this->userId);

        return $this->returnResult($result);
    }
    public function listPictureComment($pictureId)
    {
        $result = $this->photoAlbumService->listPictureComment($pictureId,$this->userId,$this->request->all());

        return $this->returnResult($result);
    }
    public function addPictureComment($pictureId)
    {
        $result = $this->photoAlbumService->addPictureComment($this->request->all(),$pictureId,$this->userId);

        return $this->returnResult($result);
    }
    public function deletePictureComment($pictureId,$commentId)
    {
        $result = $this->photoAlbumService->deletePictureComment($pictureId,$commentId,$this->userId);

        return $this->returnResult($result);
    }
    public function getPhotoThumb()
    {
        $result = $this->photoAlbumService->getPhotoThumb($this->request->input('attachment_id'));

        return $this->returnResult($result);
    }
    /**
     * 访问不存在方法处理
     *
     * @return string 提示信息
     *
     * @author: qishaobo
     *
     * @since：2015-11-03
     */
    public function __call($name, $param)
    {
        return 'function '.$name.' not exist';
    }
}