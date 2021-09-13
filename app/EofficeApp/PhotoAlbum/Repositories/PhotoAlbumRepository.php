<?php

namespace App\EofficeApp\PhotoAlbum\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\PhotoAlbum\Entities\PhotoAlbumEntity;

/**
 * 相册Repository类:提供相册表操作资源
 *
 * @author qishaobo
 *
 * @since  2015-11-3 创建
 */
class PhotoAlbumRepository extends BaseRepository
{
    public function __construct(PhotoAlbumEntity $entity)
    {
        parent::__construct($entity);
    }

    /**
     * 获取相册列表
     *
     * @param  array $param 查询条件
     *
     * @return array 查询列表
     *
     * @author qishaobo
     *
     * @since  2015-11-03
     */
    public function getPhotoAlbumList(array $param = [])
    {
        $default = [
            'fields'	=> [
                                'photo_album_id', 'photo_album_name', 'type_id','photo_album_size',
                                'permission', 'creator', 'created_at'
                           ],
            'search'	=> [],
            'page'  	=> 0,
            'limit'  	=> config('eoffice.pagesize'),
            'order_by'	=> ['created_at' => 'DESC'],
        ];
        
        $param = array_merge($default, array_filter($param));
        $userId = $param['search']['user_info']['user_id'];

        $query = $this->entity
        ->select($param['fields'])
        ->with(['withPictures' => function ($query) {
            $query->select('photo_album_id')
            ->selectRaw('count(*) AS total')
            ->groupBy('photo_album_id');
        }])
        ->with(['withComments' => function ($query) {
            $query->select('photo_album_id')
            ->selectRaw('count(*) AS total')
            ->groupBy('photo_album_id');
        }]);

        $query = $this->getPhotoAlbumParseWhere($query, $param['search']);
        $query = $query
        ->orders($param['order_by']);
        if($param['page']) {
            $query = $query->forPage($param['page'], $param['limit']);
        }     
        return $query->get()->toArray();
    }
    public function getAlbumIdsByUserId($userId)
    {
        $result = $this->entity->select('photo_album_id')->where('creator',$userId)->get();
        $albumId = [];
        if(count($result)){
            foreach($result as $album){
                $albumId[] = $album->photo_album_id;
            }
        }
        return $albumId;
    }
    public function getManageAlbums($fields,$albumIds){
        $fields = isset($fields) && !empty($fields) ? $fields : ['*'];

        return $this->entity->select($fields)->whereIn('photo_album_id',$albumIds)->get();
    }
    /**
     * 获取相册列表数量
     *
     * @param  array $param  查询条件
     *
     * @return int 查询数量
     *
     * @author qishaobo
     *
     * @since  2015-11-04
     */
    public function getPhotoAlbumTotal(array $param = [])
    {   
        $where = isset($param['search']) ? $param['search'] : [];
        return $this->getPhotoAlbumParseWhere($this->entity, $where)->count();
    }

    /**
     * 获取相册where条件解析
     *
     * @param  array $where  查询条件
     *
     * @return object
     *
     * @author qishaobo
     *
     * @since  2015-11-04
     */
    public function getPhotoAlbumParseWhere($query, array $where = [])
    {
        $userInfo = $where['user_info'];
        unset($where['user_info']);

        $query = $query->wheres($where)->permission($userInfo);

        return  $query;
    }
    public function getAllShowAlbumId($own)
    {
        $albums = $this->entity->select(['photo_album_id'])->permission($own)->get();
        $albumIds = [];
        if(count($albums)){
            foreach($albums as $album){
                $albumIds[] = $album->photo_album_id;
            }
        }
        return $albumIds;
    }
    /**
     * 用户是否有相册管理权限
     *
     * @param  int $photoAlbumId 相册id
     * @param  string $userId 用户id
     *
     * @return array 查询列表
     *
     * @author qishaobo
     *
     * @since  2015-11-04
     */
    public function hasAdminPermission($photoAlbumId, $userId)
    {
        return $this->entity
        ->whereHas('hasManyManageUser', function ($query) use ($userId) {
            $query->wheres(['user_id' => [$userId]]);
        })
        ->find($photoAlbumId);
    }


    /**
     * 查看相册信息详情
     *
     * @param  int $photoAlbumId 相册id
     *
     * @return array
     *
     * @author qishaobo
     *
     * @since  2015-11-19
     */
    public function getphotoAlbumDetail($photoAlbumId)
    {
        return $this->entity
        ->with(['hasManyDept' => function ($query) {
            $query->select(['photo_album_id', 'dept_id'])
            ->with(['hasOneDept' => function ($query) {
                $query->select(['dept_id', 'dept_name']);
            }]);
        }])
        ->with(['hasManyRole' => function ($query) {
            $query->select(['photo_album_id', 'role_id'])
            ->with(['hasOneRole' => function ($query) {
                $query->select(['role_id', 'role_name']);
            }]);
        }])
        ->with(['hasManyUser' => function ($query) {
            $query->select(['photo_album_id', 'user_id'])
            ->with(['hasOneUser' => function ($query) {
                $query->select(['user_id', 'user_name']);
            }]);
        }])
        ->with(['hasManyManageUser' => function ($query) {
            $query->select(['photo_album_id', 'user_id'])
            ->with(['hasOneUser' => function ($query) {
                $query->select(['user_id', 'user_name']);
            }]);
        }])
        ->find($photoAlbumId);
    }
    public function isViewAllUser($photoAlbumId)
    {
        $photoAlbum = $this->entity->select(['permission'])->find($photoAlbumId);
        
        return $photoAlbum->permission == 1 ;
    }
    public function isCreator($photoAlbumId, $userId)
    {
        return $this->entity->where('creator',$userId)->where('photo_album_id', $photoAlbumId)->count();
    }
    /**
     * 获取相册空间
     *
     * @param  array $search 查询条件
     *
     * @return integer 相册大小
     *
     * @author qishaobo
     *
     * @since  2016-04-11
     */
    public function getAlbumSize(array $search = [])
    {
        return $this->entity
        ->select(['photo_album_id', 'photo_album_size'])
        ->wheres($search)
        ->with(['withPictures' => function ($query) {
            $query->selectRaw("photo_album_id, sum(picture_size) as picture_total_size")->groupBy('photo_album_id');
        }])->get();
    }

    /**
     * 根据相册id获取相册名称
     */
    public function getAlbumNameByIds($albumIds)
    {
        return $this->entity
            ->select(['photo_album_id', 'photo_album_name'])
            ->whereIn('photo_album_id', $albumIds)
            ->get()
            ->toArray();
    }
}