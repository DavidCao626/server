<?php
namespace App\EofficeApp\PhotoAlbum\Requests;

use App\EofficeApp\Base\Request;

class PhotoAlbumRequest extends Request
{
    public $errorCode = '0x042001';

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules($request)
    {
        $rules = [
            'createPhotoAlbumTypes'  => [
                'type_name'             => "required|max:200|unique:photo_album_type"   
            ],
            'editPhotoAlbumTypes'  => $this->filterEditPhotoAlbumTypes($request),
            'createPhotoAlbums'     => [
                'photo_album_name'      => "required|max:200|unique:photo_album",
                'photo_album_size'      => "required|numeric"
            ],
            'editPhotoAlbums'       => [
                'photo_album_name'      => $request->has('photo_album_name') ? "required|max:200|unique:photo_album,photo_album_name,{$request->input('photo_album_id')},photo_album_id" : "",
                'photo_album_size'      => "required|numeric"
            ]
        ];

        $function = explode("@", $request->route()[1]['uses'])[1];
        return $this->getRouteValidateRule($rules, $function);
    }

    /**
     * 过滤相册分类编辑
     *
     * @return array
     */
    public function filterEditPhotoAlbumTypes($request)
    {
        $param = $request->route()[2];
        if (isset($param['id'])) {
        $typeId = $param['id'];
            return [
                    'type_name' => $typeId > 0 && $request->has('type_name') ? "required|max:200|unique:photo_album_type,type_name,$typeId,type_id" : ""
                ];
        }

    }
}
