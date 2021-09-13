<?php
namespace App\EofficeApp\PhotoAlbum\Permissions;
class PhotoAlbumPermission 
{
    private $photoAlbumPictureRepository;
    private $photoAlbumRepository;
    private $photoAlbumManageUserRepository;
    private $photoAlbumUserRepository;
    private $photoAlbumDeptRepository;
    private $photoAlbumRoleRepository;
    public $rules = [ 
        'addPictureComment' => 'handlePicturePermission',
        'deletePictureComment' => 'handlePicturePermission',
        'listPictureComment' => 'handlePicturePermission',
        'laudPicture' => 'handlePicturePermission',
        'getPhotoAlbums' => 'handleAlbumPermission',
        'visitorList' => 'handleAlbumPermission',
        'getIndexPhotoAlbumsComments' => 'handleAlbumCommentPermission',
        'createPhotoAlbumsComments' => 'handleAlbumCommentPermission',
        'deletePhotoAlbumsComments' => 'handleAlbumCommentPermission'
    ];
    public function __construct() 
    {
        $this->photoAlbumPictureRepository = 'App\EofficeApp\PhotoAlbum\Repositories\PhotoAlbumPictureRepository';
        $this->photoAlbumRepository = 'App\EofficeApp\PhotoAlbum\Repositories\PhotoAlbumRepository';
        $this->photoAlbumManageUserRepository = 'App\EofficeApp\PhotoAlbum\Repositories\PhotoAlbumManageUserRepository';
        $this->photoAlbumUserRepository = 'App\EofficeApp\PhotoAlbum\Repositories\PhotoAlbumUserRepository';
        $this->photoAlbumDeptRepository = 'App\EofficeApp\PhotoAlbum\Repositories\PhotoAlbumDepartmentRepository';
        $this->photoAlbumRoleRepository = 'App\EofficeApp\PhotoAlbum\Repositories\PhotoAlbumRoleRepository';
    }
    /**
     * 转移图片权限
     * @param type $own
     * @param type $data
     * @param type $urlData
     * @return boolean
     */
    public function migratePicture($own, $data, $urlData)
    {
        if(!isset($data['picture_ids']) || empty($data['picture_ids'])) {
            return ['code' => ['0x042008', 'photoalbum']];
        }
        $pictureIds = array_unique($data['picture_ids']);
        $count = app($this->photoAlbumPictureRepository)->hasPermission($pictureIds, $own['user_id']);
        if(count($pictureIds) == $count) {
            return true;
        }
        return ['code' => ['0x042009', 'photoalbum']];
    }
    /**
     * 上传图片权限
     * @param type $own
     * @param type $data
     * @param type $urlData
     * @return type
     */
    public function createPhotoAlbumsPictures($own, $data, $urlData) 
    {
        return $this->hasManagePurview($own['user_id'], $urlData['photoAlbumId']);
    }
    /**
     * 处理图片权限
     * @param type $own
     * @param type $data
     * @param type $urlData
     * @return boolean
     */
    public function handlePicturePermission($own, $data, $urlData)
    {
        $picture = app($this->photoAlbumPictureRepository)->getDetail($urlData['pictureId']);
        
        if($this->hasViewPurview($own, $picture->photo_album_id)) {
            return true;
        }
        
        return false;
    }
    /**
     * 处理相册权限
     * @param type $own
     * @param type $data
     * @param type $urlData
     * @return type
     */
    public function handleAlbumPermission($own, $data, $urlData) 
    {
        return $this->hasViewPurview($own, $urlData['photoAlbumId']);
    }
    /**
     * 处理相册评论权限
     * @param type $own
     * @param type $data
     * @param type $urlData
     * @return type
     */
    public function handleAlbumCommentPermission($own, $data, $urlData) 
    {
        return $this->hasViewPurview($own, $urlData['photoAlbumId']);
    }
    /**
     * 相册点赞权限
     * @param type $own
     * @param type $data
     * @param type $urlData
     * @return type
     */
    public function laudAlbum($own, $data, $urlData)
    {
        
        return $this->hasViewPurview($own, $urlData['photoAlbumId']);
    }
    /**
     * 判断是否有相册的管理权限
     * @param type $userId
     * @param type $photoAlbumId
     * @return boolean
     */
    private function hasManagePurview($userId, $photoAlbumId)
    {
        $photoAlbum = app($this->photoAlbumRepository)->getphotoAlbumDetail($photoAlbumId);
        
        if ($photoAlbum->creator == $userId || app($this->photoAlbumManageUserRepository)->hasManagePur($userId, $photoAlbumId)) {
            return true;
        }
        
        return false;
    }
    /**
     * 判断是否有相册的查看权限
     * @param type $own
     * @param type $photoAlbumId
     * @return boolean
     */
    private function hasViewPurview($own, $photoAlbumId) 
    {
        $userId = $own['user_id'];
        
        $photoAlbum = app($this->photoAlbumRepository)->getphotoAlbumDetail($photoAlbumId);

        if(!$photoAlbum){
            return false;
        }

        if($photoAlbum->creator == $userId || $photoAlbum->permission == 1) {
            return true;
        }
        
        if (app($this->photoAlbumUserRepository)->hasUserPurviewOfAlbum($userId, $photoAlbumId)) {
            return true;
        }

        if (app($this->photoAlbumDeptRepository)->hasDeptPurviewOfAlbum($own['dept_id'], $photoAlbumId)) {
            return true;
        }

        if (app($this->photoAlbumRoleRepository)->hasRolePurviewOfAlbum($own['role_id'], $photoAlbumId)) {
            return true;
        }

        if (app($this->photoAlbumManageUserRepository)->hasManagePur($userId, $photoAlbumId)) {
            return true;
        }
        
        return false;
    }
}
