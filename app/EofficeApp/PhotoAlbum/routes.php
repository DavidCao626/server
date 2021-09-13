<?php
$routeConfig = [
	//相册类别排序
        ['photo-album/types/sort', 'sortPhotoAlbumTypes', 'put', [369]],
        //照片置顶
        ['photo-album/{photoAlbumId}/pictures/{pictureId}/top', 'editPicturesTop', 'put', [367]],
        //取消照片置顶
        ['photo-album/{photoAlbumId}/pictures/{pictureId}/cancel-top', 'cancelPicturesTop', 'put', [367]],
        //设置照片为相册封面
        ['photo-album/{photoAlbumId}/pictures/{pictureId}/cover', 'editPicturesCover', 'put', [367]],
        //设置照片为相册封面
        ['photo-album/{photoAlbumId}/pictures/{pictureId}/cancel-cover', 'cancelPicturesCover', 'put', [367]],
        //获取所有相册类别
        ['photo-album/types-all', 'getPhotoAlbumTypesAll', [369]], // 可能是无效api
        //获取相册类别
        ['photo-album/types', 'getIndexPhotoAlbumTypes', [369, 368, 367]],
        //新建相册类别
        ['photo-album/types', 'createPhotoAlbumTypes', 'post', [369]],
        //获取相册类别详情
        ['photo-album/types/{typeId}', 'getPhotoAlbumTypes', [369]],
        //编辑相册类别
        ['photo-album/types/{typeId}', 'editPhotoAlbumTypes', 'put', [369]],
        //删除相册类别
        ['photo-album/types/{typeId}', 'deletePhotoAlbumTypes', 'delete', [369]],
        //获取相册列表
        ['photo-album', 'getIndexPhotoAlbums', [367]],
        ['photo-album/manage/all', 'getManageAlbums', [367]],

        //添加照片
        ['photo-album', 'createPhotoAlbums', 'post', [368]],
        //获取相册列表
        ['photo-album/manage/all/{albumId}', 'getManageAlbumsByAlbumId', [367]],
        //查询相册详情
        ['photo-album/{photoAlbumId}', 'getPhotoAlbums', [367]],
        ['photo-album/{photoAlbumId}/visitor', 'visitorList', [367]],
        //编辑相册
        ['photo-album/{photoAlbumId}', 'editPhotoAlbums', 'put', [367]],
        //删除相册
        ['photo-album/{photoAlbumId}', 'deletePhotoAlbums', 'delete', [367]],
        ['photo-album/{photoAlbumId}/laud/{laud}', 'laudAlbum', [367]],
        ['photo-album/picture/{pictureId}/laud/{laud}', 'laudPicture', [367]],
        ['photo-album/picture/{pictureId}/comments', 'listPictureComment', [367]],
        ['photo-album/picture/{pictureId}/comments', 'addPictureComment','post', [367]],
        ['photo-album/picture/{pictureId}/comments/{commentId}', 'deletePictureComment','delete', [367]],
        //获取照片列表
        ['photo-album/{photoAlbumId}/pictures', 'getIndexPhotoAlbumsPictures', [367, 370]],
        ['photo-album/picture/{pictureId}', 'getPictureDetail', [367]],
        ['photo-album/picture/{pictureId}', 'editPicture','post', [367]],
        ['photo-album/picture/{photoAlbumId}/migrate', 'migratePicture','post', [367]],
        ['photo-album/picture/thumb/get', 'getPhotoThumb', [367, 370]],
        //添加照片
        ['photo-album/{photoAlbumId}/pictures', 'createPhotoAlbumsPictures', 'post', [367]],
        //删除照片
        ['photo-album/{photoAlbumId}/pictures/{pictureId}', 'deletePhotoAlbumsPictures', 'delete', [367]],
        //获取相册评论列表
        ['photo-album/{photoAlbumId}/comments', 'getIndexPhotoAlbumsComments', [367]],
        //添加相册评论
        ['photo-album/{photoAlbumId}/comments', 'createPhotoAlbumsComments', 'post', [367]],
        //删除相册评论
        ['photo-album/{photoAlbumId}/comments/{commentId}', 'deletePhotoAlbumsComments', 'delete', [367]],
];