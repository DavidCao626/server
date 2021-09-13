<?php
namespace App\EofficeApp\System\Tag\Permissions;

class TagPermission
{
    private $tagRepository;
    public $rules = [
        'editTag' => 'tagPermission',
        'deleteTag' => 'tagPermission',
        'createTag' => 'createTagPermission'
    ];
    public function __construct() {
        $this->tagRepository = "App\EofficeApp\System\Tag\Repositories\TagRepository";
    }
    public function tagPermission($own, $data, $urlData) {
        $currentUserId = $own['user_id'];
        if (!isset($urlData['id']) && empty($urlData['id'])) {
            return ['code' => ['0x049002','tag']];
        }
        $detail = app($this->tagRepository)->getDetail($urlData['id']);
        if (isset($data['tag_type'])) {
            if ($data['tag_type'] != $detail->tag_type) {
                return false;
            }
            $type = $data['tag_type'];
        }else{
            $type = $detail->tag_type ? $detail->tag_type : '';
        }
        if (isset($data['tag_creator']) && $data['tag_type'] == 'private') {
            if ($data['tag_creator'] != $detail->tag_creator) {
                return false;
            }
            $createUser = $data['tag_creator'];
        }else{
            $createUser = $detail->tag_creator ? $detail->tag_creator : '';
        }
        // 判断有没有公共标签权限
        if ($currentUserId != $createUser && $type == 'private') {
            return false;
        } else if ($type == 'public' && !in_array(117, $own['menus']['menu']) && $currentUserId != $createUser) {
            return false;
        }
        return true;
    }

    public function createTagPermission($own, $data, $urlData) {
        $currentUserId = $own['user_id'];
        if (!isset($data['tag_creator']) && empty($data['tag_creator'])) {
            return ['code' => ['0x049003','tag']];
        }
        $createUser = $data['tag_creator'];
        $type = $data['tag_type'] ? $data['tag_type'] : '';
        if ($currentUserId != $createUser && $type == 'private') {
            return false;
        } else if ($type == 'public' && !in_array(117, $own['menus']['menu'])) {
            return false;
        }
        return true;
    }

}
