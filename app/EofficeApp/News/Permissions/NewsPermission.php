<?php

namespace App\EofficeApp\News\Permissions;

class NewsPermission
{
    const NO_PERMISSION = ['code' => ['0x001028', 'news']];
    const NEWS_NOT_EXIST = ['code' => ['0x001020', 'news']];
    const COMMENT_NOT_EXIST = ['code' => ['0x001015', 'news']];
    const EMPTY_NEWS_ID = ['code' => ['0x001005', 'news']];
    const ALREADY_PUBLISH = ['code' => ['0x001013', 'news']];
    const EMPTY_COMMENT_ID = ['code' => ['0x001008', 'news']];

    private $newsRepository;
    public $rules = [
        'deleteNews'          => 'canModifyNews',
        'top'                 => 'canModifyNews',
        'cancelTop'           => 'canModifyNews',
        'cancelPublish'       => 'canModifyNews',
        'commentList'         => 'isPublishByNewsId',
        'addComment'          => 'isPublishByNewsId',
        'getCommontDetail'    => 'isPublishByNewsId',
        'getChildrenComments' => 'isPublishByCommentId',
        'deleteComment'       => 'isPublishByCommentId',
        'editComment'         => 'isPublishByCommentId',

    ];

    public function __construct()
    {
        $this->newsRepository = 'App\EofficeApp\News\Repositories\NewsRepository';
    }

    private function isAdmin($own)
    {
        return $own['user_id'] === 'admin';
    }

    // 是否有审核菜单权限
    private function canCheck($own)
    {
        return in_array(321, $own['menus']['menu']);
//        return app('App\EofficeApp\Menu\Services\UserMenuService')->judgeMenuPermission(321) === 'true';
    }

    // 是发布人
    private function isPublisher($news, $own)
    {
        $userId = $own['user_id'];
        return $news->creator === $userId;
    }

    // 新建新闻：直接发布需要审核权限
    public function addNews($own, $data)
    {
        // 直接发布必须有审核菜单权限
        if (!$this->canCheck($own) && $data['publish'] == 1) {
            return self::NO_PERMISSION;
        }
        return true;
    }

    // 编辑新闻：操作者为管理员，或者发布者直接发布需要审核权限
    public function editNews($own, $data, $urlData)
    {
        $newsId = $urlData['newsId'];
        if (!$newsId) {
            return self::EMPTY_NEWS_ID;
        }
        $news = app($this->newsRepository)->entity->select('news_id', 'publish', 'creator')->find($newsId);
        if (!$news) {
            return self::NEWS_NOT_EXIST;
        }
        if ($news->publish === 1) {
            return self::ALREADY_PUBLISH;
        }
        // 直接发布必须有审核菜单权限
        if ($data['publish'] == 1 && !$this->canCheck($own)) {
            return self::NO_PERMISSION;
        }
        return $this->isPublisher($news, $own);
    }


    // 删除、置顶取消置顶、撤回新闻的权限，管理员或者发布者
    public function canModifyNews($own, $data, $urlData)
    {
        $newsId = $urlData['newsId'];
        if (!$newsId) {
            return self::EMPTY_NEWS_ID;
        }
        $newsId = explode(',', $newsId);
        $newsList = app($this->newsRepository)->entity->select('news_id', 'publish', 'creator')->find($newsId);
        if($newsList->isEmpty()){
            return self::NEWS_NOT_EXIST;
        }
        foreach ($newsList as $news){
            if($news->publish != 1){
                if(!$this->isPublisher($news, $own)){
                    return self::NO_PERMISSION;
                }
            }else{
                if(!$this->isPublisher($news, $own) && !$this->isAdmin($own)){
                    return self::NO_PERMISSION;
                }
            }
        }
        return true;
    }

    // 发布新闻的权限
    public function publish($own, $data, $urlData)
    {
        $newsId = $urlData['newsId'];
        if (!$newsId) {
            return self::EMPTY_NEWS_ID;
        }
        $news = app($this->newsRepository)->entity->select('news_id', 'publish', 'creator')->find($newsId);
        if ($news->publish === 1) {
            return self::NO_PERMISSION;
        }
        if($news->publish != 1 && !$this->isPublisher($news, $own)){
            return self::NO_PERMISSION;
        }
        if (!$this->canCheck($own)) {
            return self::NO_PERMISSION;
        }
        return true;
    }

    // 是否是发布状态，不是不能进行评论操作
    public function isPublishByNewsId($own, $data, $urlData)
    {
        $newsId = $urlData['newsId'];
        if (!$newsId) {
            return self::EMPTY_NEWS_ID;
        }
        $news = app($this->newsRepository)->entity->select('news_id', 'publish')->find($newsId);
        if ($news->publish !== 1) {
            return self::NO_PERMISSION;
        }
        return true;
    }

    public function isPublishByCommentId($own, $data, $urlData)
    {
        $commentId = $urlData['commentId'];
        if (!$commentId) {
            return self::EMPTY_COMMENT_ID;
        }
        $news = app($this->newsRepository)->entity->select('news.news_id', 'publish')
            ->join('news_comment', 'news_comment.news_id', '=', 'news.news_id')
            ->where('news_comment.comment_id', $commentId)
            ->first();
        if (!$news) {
            return self::NEWS_NOT_EXIST;
        }
        if ($news->publish !== 1) {
            return self::NO_PERMISSION;
        }
        return true;
    }
}
