<?php
namespace App\EofficeApp\News\Services;
use App\EofficeApp\Base\BaseService;
use App\EofficeApp\Elastic\Services\MessageQueue\ElasticsearchProducer;
use App\EofficeApp\News\Repositories\NewsRepository;
use App\EofficeApp\User\Repositories\UserRepository;
use Carbon\Carbon;
use Eoffice;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
class NewsService extends BaseService
{
    private $newsRepository;
    private $newsReaderRepository;
    private $newsTypeRepository;
    private $newsCommentRepository;
    private $systemComboboxService;
    private $attachmentService;
    private $newsSettingsRepository;
    /** @var object 用户资源库对象*/
    private $userRepository;
    private $userMenuService;


    public function __construct()
    {
        parent::__construct();
        $this->newsRepository        = 'App\EofficeApp\News\Repositories\NewsRepository';
        $this->newsTypeRepository    = 'App\EofficeApp\News\Repositories\NewsTypeRepository';
        $this->newsReaderRepository  = 'App\EofficeApp\News\Repositories\NewsReaderRepository';
        $this->newsCommentRepository = 'App\EofficeApp\News\Repositories\NewsCommentRepository';
        $this->systemComboboxService = 'App\EofficeApp\System\Combobox\Services\SystemComboboxService';
        $this->attachmentService     = 'App\EofficeApp\Attachment\Services\AttachmentService';
        $this->userRepository        = 'App\EofficeApp\User\Repositories\UserRepository';
        $this->userMenuService       = 'App\EofficeApp\Menu\Services\UserMenuService';
        $this->newsSettingsRepository = 'App\EofficeApp\News\Repositories\NewsSettingsRepository';
    }
    /**
     * @获取新闻列表
     * @param type $params
     * @return boolean | array 新闻列表
     */
    public function getList($params, $userId)
    {
        $params = $this->parseParams($params);

        if (isset($params['fields'])) {
            $params['fields'] = $this->handleFields($params['fields']);
        }

        if (isset($params['search'])) {
            $params['search'] = $this->handleSearch($params['search']);
        }

        $response = isset($params['response']) ? $params['response'] : 'both';
        $list     = [];

        if ($response == 'both' || $response == 'count') {
            $count = app($this->newsRepository)->getNewsCount($params, $userId);
        }

        if (($response == 'both' && $count > 0) || $response == 'data') {
            foreach (app($this->newsRepository)->getNewsList($params, $userId) as $new) {
                if ($new['creator'] == $userId) {
                    $new['has_purview'] = 1;
                }
                $new['readerExists'] = app($this->newsReaderRepository)->readerExists($new['news_id'], $userId);
                $list[]              = $new;
            }
        }

        return $response == 'both' ? ['total' => $count, 'list' => $list] : ($response == 'data' ? $list : $count);
    }
    /**
     * @获取门户新闻列表
     * @param type $params
     * @return boolean | array 新闻列表
     */
    public function getProtalList($params, $userId)
    {
        $params = $this->parseParams($params);
        if (isset($params['fields'])) {
            $params['fields'] = $this->handleFields($params['fields']);
        }
        //查询条件，查询图片字段
        $list = [];
        foreach (app($this->newsRepository)->getNewsPortalList($params, $userId) as $new) {
            $new->readerExists  = app($this->newsReaderRepository)->readerExists($new->news_id, $userId);
            // $new->attachment_id = app($this->attachmentService)->getAttachmentIdsByEntityId(array('entity_table' => 'news', 'entity_id' => $new->news_id));
            $new->attachment_id = app($this->attachmentService)->getAttachmentIdsByEntityId(['entity_table' => 'news', 'entity_id' => ["entity_id" => [$new->news_id], 'entity_column' => ['photo']]]);
            $list[]             = $new;
        }
        $count = app($this->newsRepository)->getNewsPortalCount($params, $userId);
        return ['total' => $count, 'list' => $list];
    }

    /**
     * @新建新闻
     * @param array $data
     * @param $own
     * @return array|\string[][]
     */
    public function addNews($data, $own)
    {
        $userId   = $own['user_id'];
        $newsData = [
            'title'           => $data['title'],
            'content'         => $data['content'],
            'news_desc'       => $this->handleNewsDesc($this->defaultValue('news_desc', $data, ''), $data['content']),
            'news_type_id'    => $this->defaultValue('news_type_id', $data, 0),
            'comments'        => 0,
            'views'           => 0,
            'top'             => $this->defaultValue('top', $data, 0)? 1 : 0,
            'top_end_time'    => $this->defaultValue('top_end_time', $data, ""),
            'top_create_time' => $this->defaultValue('top', $data, 0) ? date('Y-m-d H:i:s') : "",
            'publish'         => $this->defaultValue('publish', $data, 0),
            'allow_reply'     => $this->defaultValue('allow_reply', $data, 0)? 1 : 0,
            'creator'         => $userId,
            'publish_time'    => isset($data['publish_time']) ? $data['publish_time'] : date('Y-m-d H:i:s')
        ];

        //新闻审核完要不要直接发布
//        if($newsData['publish'] == 1){
//            $menuPermission  = app('App\EofficeApp\Menu\Services\UserMenuService')->judgeMenuPermission(321);
//            if($menuPermission == "false"){
//                $newsData['publish'] = 2;
//            }
//        }
        if($newsData['top'] && $newsData['top_end_time']){
            if(Carbon::parse($newsData['top_end_time'])->isBefore(Carbon::now())){
                return ['code' => ['0x001030', 'news']];
            }
        }
        $newsData = $this->handleNewsInfo($newsData,$own);
        if ($result = app($this->newsRepository)->insertData($newsData)) {
            //消息提醒，上传附件
            $info = ['wheres' => ['entity_id' => [$result->news_id]],'entity_id' => [$result->news_id]];
            if (isset($data['attachment_id']) && $data['attachment_id'] != "") {
                $info['wheres']['entity_column'] = ['photo'];
                $info['entity_column'] = ['photo'];
                app($this->attachmentService)->attachmentRelation("news", $info, $data['attachment_id']);
            }
            //编辑器里面的上传附件
            if (isset($data['content_attachment']) && $data['content_attachment'] != "") {
                $info['wheres']['entity_column'] = ['rel_attach'];
                $info['entity_column'] = ['rel_attach'];
                app($this->attachmentService)->attachmentRelation("news", $info, $data['content_attachment']);
            }

            if ($result->publish == 1) {
                $sendData['remindMark']   = 'news-publish';
                $userId                   = app($this->userMenuService)->getMenuRoleUserbyMenuId(130);
                $sendData['toUser']       = implode(',', $userId);
                $sendData['contentParam'] = ['newsTitle' => $newsData['title']];
                $sendData['stateParams']  = ['news_id' => $result->news_id];
                Eoffice::sendMessage($sendData);

            }
            if ($result->publish == 2) {
                $sendData['remindMark']   = 'news-submit';
                $userId                   = app($this->userMenuService)->getMenuRoleUserbyMenuId(321);
                $sendData['toUser']       = implode(',', $userId);
                $sendData['contentParam'] = ['newsTitle' => $newsData['title'], 'userName' => $own['user_name']];
                $sendData['stateParams']  = ['news_id' => $result->news_id];
                Eoffice::sendMessage($sendData);
            }

            // 全站搜索消息队列更新数据
            $this->updateGlobalSearchDataByQueue($result->news_id);

            return ['news_id' => $result->news_id];
        }

        return ['code' => ['0x000003', 'common']];
    }

    public function handleNewsInfo($newsData , $own ){
        if(!is_array($newsData)){
            $newsData = json_decode(json_encode($newsData),true);
        }
        $settings = $this->getNewsSettings();
        if($settings['comment_display_scope'] == 1){
            $newsData['allow_reply'] = $settings['comment'];

        }else if($settings['comment_display_scope'] == 2){
            if(array_intersect(explode(",",$settings['comment_display_role']),$own['role_id']) || in_array($own['dept_id'], explode(',', $settings['comment_display_dept']))
                || in_array($own['user_id'], explode(',', $settings['comment_display_user']))){
                //在权限里面
            }else{
                $newsData['allow_reply'] = $settings['comment'];

            }
        }


        if($settings['top_display_scope'] == 1){
            $newsData['top'] = $settings['top'];
        }else if($settings['top_display_scope'] == 2){
            if(array_intersect(explode(",",$settings['top_display_role']),$own['role_id']) || in_array($own['dept_id'], explode(',', $settings['top_display_dept']))
                || in_array($own['user_id'], explode(',', $settings['top_display_user']))){
                //在权限里面
            }else{
                $newsData['top'] = $settings['top'];
            }
        }
        return $newsData;

    }

    /**
     * @编辑新闻
     * @param array $data
     * @param $newsId
     * @return bool|\string[][]
     */
    public function editNews($data, $newsId,$own)
    {
        if ($newsId == 0) {
            return ['code' => ['0x001005', 'news']];
        }

        $news = app($this->newsRepository)->getDetail($newsId);
        if (!$news) {
            return ['code' => ['0x001020', 'news']];
        }
        if ($news->publish == 1) {
            return ['code' => ['0x001013', 'news']];
        }

        $newsData = [];
        if(!empty($data['title'])){
            $newsData['title'] = $data['title'];
        }
        if(!empty($data['content'])){
            $newsData['content'] = $data['content'];
        }
        if(isset($data['news_desc'])){
            $newsContent = $newsData['content'] ?? $news->content;
            $newsData['news_desc'] = $this->handleNewsDesc($this->defaultValue('news_desc', $data, ''), $newsContent);
        }
        if(isset($data['news_type_id'])){
            $newsData['news_type_id'] = $this->defaultValue('news_type_id', $data, 0);
        }
        if(isset($data['top'])){
            $newsData['top'] = $this->defaultValue('top', $data, 0);
            $newsData['top_create_time'] = $newsData['top'] ? date('Y-m-d H:i:s') : "";
        }
        if(isset($data['top_end_time'])){
            $newsData['top_end_time'] = $this->defaultValue('top_end_time', $data, "");
        }
        if(isset($data['publish'])){
            $newsData['publish'] = $this->defaultValue('publish', $data, 0);
        }
        if(isset($data['allow_reply'])){
            $newsData['allow_reply'] = $this->defaultValue('allow_reply', $data, 1);
        }


        if(isset($newsData['top']) && $newsData['top'] && isset($newsData['top_end_time']) && $newsData['top_end_time']){
            if(Carbon::parse($newsData['top_end_time'])->isBefore(Carbon::now())){
                return ['code' => ['0x001030', 'news']];
            }
        }
        //新闻流程走完要不要直接发布
//        if($newsData['publish'] == 1){
//            $menuPermission  = app('App\EofficeApp\Menu\Services\UserMenuService')->judgeMenuPermission(321 , $news->creator);
//            if($menuPermission == "false"){
//                $newsData['publish'] = 2;
//            }
//        }
        // 更新发布时间为当前时间
        if(isset($newsData['publish']) && $newsData['publish'] == 1){
            $newsData['publish_time']   = date('Y-m-d H:i:s');
        }

        $newsData = $this->handleNewsInfo($newsData,$own);
        if (app($this->newsRepository)->editNews($newsData, ['news_id' => $newsId])) {
            //消息提醒，上传附件
            $info = ['wheres' => ['entity_id' => [$newsId]],'entity_id' => [$newsId]];
            if (isset($data['attachment_id'])) {
                $info['wheres']['entity_column'] = ['photo'];
                $info['entity_column'] = ['photo'];
                app($this->attachmentService)->attachmentRelation("news", $info, $data['attachment_id']);
            }
            //编辑器里面的上传附件
            if (isset($data['content_attachment'])) {
                $info['wheres']['entity_column'] = ['rel_attach'];
                $info['entity_column'] = ['rel_attach'];
                app($this->attachmentService)->attachmentRelation("news", $info, $data['content_attachment']);
            }

            $newsTitle = $newsData['title'] ?? $news->title;
            $publish = $newsData['publish'] ?? $news->publish;
            if ($publish == 1) {
                $sendData['remindMark']   = 'news-publish';
                $userId                   = app($this->userMenuService)->getMenuRoleUserbyMenuId(130);
                $sendData['toUser']       = implode(',', $userId);
                $sendData['contentParam'] = ['newsTitle' => $newsTitle];
                $sendData['stateParams']  = ['news_id' => $newsId];
                Eoffice::sendMessage($sendData);
            }
            if ($publish == 2) {
                $sendData['remindMark']   = 'news-submit';
                $userId                   = app($this->userMenuService)->getMenuRoleUserbyMenuId(321);
                $sendData['toUser']       = implode(',', $userId);
                $userName = app($this->userRepository)->getUserName($news['creator']);
                $sendData['contentParam'] = ['newsTitle' => $newsTitle, 'userName' => $userName];
                $sendData['stateParams']  = ['news_id' => $newsId];
                Eoffice::sendMessage($sendData);
            }

            // 全站搜索消息队列更新数据
            $this->updateGlobalSearchDataByQueue($newsId);

            return true;
        }

        return ['code' => ['0x000003', 'common']];
    }
    /**
     * @获取新闻详情
     * @param type $newsId
     * @return boolean | 新闻详情
     */
    public function getNewsInfo($newsId, $currentUserId, $own)
    {
        if ($newsId == 0) {
            return ['code' => ['0x001005', 'news']];
        }
        $check       = 0;
        $newspublish = app($this->newsRepository)->showNews($newsId);
        if (!$newspublish) {
            return ['code' => ['0x001020', 'news']];
        }
        if ($newspublish->publish != 1 && ($newspublish->creator != $own['user_id'])) {
            return ['code' => ['0x000006', 'common']];
        }
        if (in_array(321, $own['menus']['menu']) && $newspublish->publish == 2) {
            $check = 1;
        }
        if ($newspublish->publish != 2 || ($check == 1 && $newspublish->publish == 2) || ($newspublish->creator == $currentUserId) || $own['user_id'] === 'admin') {
            if (app($this->newsReaderRepository)->readerExists($newsId, $currentUserId) == 0) {
                $data = [
                    'news_id' => $newsId,
                    'user_id' => $currentUserId,
                ];

                app($this->newsReaderRepository)->insertData($data);
            }
            app($this->newsRepository)->updateviews($newsId);
            $newsInfo = app($this->newsRepository)->showNews($newsId, true);
        } else {
            return ['code' => ['0x000006', 'common']];
        }
        $newsInfo->user_id       = explode(',', $newsInfo->user_id);
        $newsInfo->role_id       = explode(',', $newsInfo->role_id);
        $newsInfo->dept_id       = explode(',', $newsInfo->dept_id);
        // $newsInfo->attachment_id = app($this->attachmentService)->getAttachmentIdsByEntityId(['entity_table' => 'news', 'entity_id' => $newsId]);
        $newsInfo->attachment_id = app($this->attachmentService)->getAttachmentIdsByEntityId(['entity_table' => 'news', 'entity_id' => ["entity_id" => [$newsId], 'entity_column' => ['photo']]]);

        $newsInfo->content_attachment = app($this->attachmentService)->getAttachmentIdsByEntityId(['entity_table' => 'news', 'entity_id' => ["entity_id" => [$newsId], 'entity_column' => ['rel_attach']]]);
        return $newsInfo;
    }
    /**
     * @获取审核新闻详情
     * @param type $newsId
     * @return boolean | 新闻详情
     */
    public function showVerifyNews($newsId, $currentUserId, $own)
    {
        if ($newsId == 0) {
            return ['code' => ['0x001005', 'news']];
        }

        $newspublish = app($this->newsRepository)->showNews($newsId);
        if (!$newspublish) {
            return ['code' => ['0x001020', 'news']];
        }
        if ($newspublish->publish != 2) {
            return ['code' => ['0x001017', 'news']];
        }

        $newsInfo = app($this->newsRepository)->showNews($newsId);

        $newsInfo->attachment_id = app($this->attachmentService)->getAttachmentIdsByEntityId(['entity_table' => 'news', 'entity_id' => $newsId]);
        return $newsInfo;
    }
    /**
     * @删除新闻
     * @param type $newsIds
     * @return 成功失败信息
     */
    public function deleteNews($newsIds)
    {
        if ($newsIds == 0) {
            return ['code' => ['0x001005', 'news']];
        }

        $newsIdArray = explode(',', $newsIds);

        if (app($this->newsRepository)->deleteById($newsIdArray)) {

            app($this->newsCommentRepository)->deleteByWhere(['news_id' => [$newsIdArray, 'in']]);

            // 全站搜索消息队列更新数据
            $this->updateGlobalSearchDataByQueue($newsIdArray);

            return true;
        }

        return ['code' => ['data_not_exist', 'news']];
    }
    /**
     * @新闻置顶
     *
     * @author 李志军
     *
     * @param int $newsId
     *
     * @return int | array
     */
    public function top($newsId, $params)
    {

        if (!empty($params)) {
            $topEndTime = $params['top_end_time'] ? $params['top_end_time'] : "";
        } else {
            $topEndTime = "";
        }

        $TopCreateTime = date('Y-m-d H:i:s');
        if ($newsId == 0) {
            return ['code' => ['0x001005', 'news']];
        }

        if (app($this->newsRepository)->editNews(['top' => 1, 'top_end_time' => $topEndTime, 'top_create_time' => $TopCreateTime], ['news_id' => $newsId])) {
            return true;
        }

        return ['code' => ['0x000003', 'common']];
    }
    /**
     * @新闻取消置顶
     *
     * @author 李志军
     *
     * @param int $newsId
     *
     * @return int | array
     */
    public function cancelTop($newsId)
    {
        if ($newsId == 0) {
            return ['code' => ['0x001005', 'news']];
        }

        if (app($this->newsRepository)->editNews(['top' => 0, 'top_end_time' => "", 'top_create_time' => ""], ['news_id' => $newsId])) {
            return true;
        }

        return ['code' => ['0x000003', 'common']];
    }
    /**
     * @新闻取消发布
     *
     * @author 李志军
     *
     * @param int $newsId
     *
     * @return int | array
     */
    public function cancelPublish($newsId)
    {
        if ($newsId == 0) {
            return ['code' => ['0x001005', 'news']];
        }
        $countComment = app($this->newsCommentRepository)->getCommentsCount($newsId);
        if ($countComment > 0) {
            return ['code' => ['0x001021', 'news']];
        }

        if (app($this->newsRepository)->editNews(['publish' => 0], ['news_id' => $newsId])) {
            return true;
        }

        return ['code' => ['0x000003', 'common']];
    }
    /**
     * @新闻发布
     *
     * @author 李志军
     *
     * @param int $newsId
     *
     * @return int | array
     */
    public function publish($newsId)
    {
        if ($newsId == 0) {
            return ['code' => ['0x001005', 'news']];
        }
        $newsInfo = app($this->newsRepository)->showNews($newsId);
        if (!$newsInfo) {
            return ['code' => ['0x001020', 'news']];
        }
        if ($newsInfo->publish == 1) {
            return ['code' => ['0x001013', 'news']];
        }
        $newsInfo = $this->handleNewsInfo($newsInfo,own());
        // publish_time发布时间,更改发布时间
        if (app($this->newsRepository)->editNews(['publish' => 1,'publish_time'=>date('Y-m-d H:i:s'),'top'=>$newsInfo['top'],'allow_reply'=>$newsInfo['allow_reply']], ['news_id' => $newsId])) {
            $sendData['remindMark']   = 'news-publish';
            $userId                   = app($this->userMenuService)->getMenuRoleUserbyMenuId(130);
            $sendData['toUser']       = implode(',', $userId);
            $sendData['contentParam'] = ['newsTitle' => $newsInfo['title']];
            $sendData['stateParams']  = ['news_id' => $newsInfo['news_id']];
            Eoffice::sendMessage($sendData);
            return true;
        }

        return ['code' => ['0x000003', 'common']];
    }
    /**
     * @获取审核新闻列表
     *
     * @author 李志军
     *
     * @param array $params
     *
     * @return array  审核新闻列表
     */
    public function verify($params, $userId)
    {
        $params = $this->parseParams($params);

        if (isset($params['fields'])) {
            $params['fields'] = $this->handleFields($params['fields']);
        }

        $params['search']['publish'] = [2];

        $params['search'] = $this->handleSearch($params['search']);

        $params['verify'] = 1;

        $response = isset($params['response']) ? $params['response'] : 'both';

        $list = [];

        if ($response == 'both' || $response == 'count') {
            $count = app($this->newsRepository)->getNewsCount($params, $userId);
        }

        if (($response == 'both' && $count > 0) || $response == 'data') {
            foreach (app($this->newsRepository)->getNewsList($params, $userId) as $new) {

                $new['news_type_name'] = app($this->newsTypeRepository)->getNewsTypeName($new['news_type_id']);

                $list[] = $new;
            }
        }

        return $response == 'both'
            ? ['total' => $count, 'list' => $list]
            : ($response == 'data' ? $list : $count);
    }
    /**
     * @获取新闻类别排序当前最大值
     * @param type
     * @return array 新闻类别排序当前最大值
     */
    public function getMaxsort()
    {
        return app($this->newsTypeRepository)->getMaxsort();
    }
    /**
     * @新闻批准发布
     *
     * @author 李志军
     *
     * @param int $newsId
     *
     * @return int | array
     */
    public function approveNews($newsIds, $own)
    {
        if ($newsIds == 0) {
            return ['code' => ['0x001005', 'news']];
        }
        $newsIds = explode(',', $newsIds);

        foreach ($newsIds as $newsId) {
            if ($newsId) {
                $newsInfo = app($this->newsRepository)->showNews($newsId);
                if (!$newsInfo) {
                    return ['code' => ['0x001020', 'news']];
                }
                if ($newsInfo->publish != 2) {
                    return ['code' => ['0x001017', 'news']];
                }
                if (app($this->newsRepository)->editNews(['publish' => 1,'publish_time'=>date('Y-m-d H:i:s')], ['news_id' => [$newsId]])) {
                    $sendData['remindMark'] = 'news-pass';

                    $sendData['toUser']       = $newsInfo->creator;
                    $sendData['contentParam'] = ['newsTitle' => $newsInfo->title, 'userName' => $own['user_name']];
                    $sendData['stateParams']  = ['news_id' => $newsInfo->news_id];
                    Eoffice::sendMessage($sendData);

                    $sendDataToviewuser['remindMark']   = 'news-publish';
                    $userId                             = app($this->userMenuService)->getMenuRoleUserbyMenuId(130);
                    $sendDataToviewuser['toUser']       = implode(',', $userId);
                    $sendDataToviewuser['contentParam'] = ['newsTitle' => $newsInfo->title];
                    $sendDataToviewuser['stateParams']  = ['news_id' => $newsInfo->news_id];
                    Eoffice::sendMessage($sendDataToviewuser);
                }
            }

        }
        // 全站搜索消息队列更新数据
        $this->updateGlobalSearchDataByQueue($newsIds);

        return true;
        // return ['code' => ['0x000003', 'common']];
    }
    /**
     * @新闻拒绝发布
     *
     * @author 李志军
     *
     * @param int $newsId
     *
     * @return int | array
     */
    public function refuseNews($newsIds, $own)
    {
        if ($newsIds == 0) {
            return ['code' => ['0x001005', 'news']];
        }
        $newsIds = explode(',', $newsIds);
        foreach ($newsIds as $newsId) {
            $newsInfo = app($this->newsRepository)->showNews($newsId);
            if (!$newsInfo) {
                return ['code' => ['0x001020', 'news']];
            }
            if ($newsInfo->publish != 2) {
                return ['code' => ['0x001017', 'news']];
            }
            if (app($this->newsRepository)->editNews(['publish' => 0], ['news_id' => [$newsId]])) {
                $sendData['remindMark']   = 'news-refuse';
                $sendData['toUser']       = $newsInfo->creator;
                $sendData['contentParam'] = ['newsTitle' => $newsInfo->title, 'userName' => $own['user_name']];
                $sendData['stateParams']  = ['news_id' => $newsInfo->news_id];
                Eoffice::sendMessage($sendData);

            }
        }
        return true;
        // return ['code' => ['0x000003', 'common']];
    }
    /**
     * @获取评论列表
     * @param type $newsId
     * @param type $offset
     * @param type $limit
     * @return array 评论列表
     */
    public function getCommentList($newsId, $param)
    {
        if ($newsId == 0) {
            return ['code' => ['0x001005', 'news']];
        }

        $_comments = [];

        if ($comments = app($this->newsCommentRepository)->getCommentList($newsId, $this->parseParams($param))) {
            foreach ($comments as $comment) {
                ///$comment->user_name = get_user_simple_attr($comment->user_id);
                $comment->attachments = app($this->attachmentService)->getAttachmentIdsByEntityId(['entity_table' => 'news_comment', 'entity_id' => $comment->comment_id]);
                $reply                = $this->getChildrenComments($comment->comment_id);
                foreach ($reply as &$v) {
                    $v['attachments'] = app($this->attachmentService)->getAttachmentIdsByEntityId(['entity_table' => 'news_comment', 'entity_id' => $v['comment_id']]);
                }
                $comment->reply       = $reply;
                $_comments['list'][]  = $comment;
                $_comments['total']   = app($this->newsCommentRepository)->getCommentsCount($newsId);
            }
        }
        return $_comments;
    }
    /**
     * @获取子评论
     * @param type $commentId
     * @return type
     */
    public function getChildrenComments($commentId)
    {
        return app($this->newsCommentRepository)->getChildrenComments($commentId);
    }
    /**
     * @新建评论
     * @param type $data
     * @return boolean 评论ID
     */
    public function addComment($data, $newsId, $currentUserId)
    {
        if ($newsId == 0) {
            return ['code' => ['0x001005', 'news']];
        }

        $news = app($this->newsRepository)->getDetail($newsId);

        if ($news->allow_reply == 0) {
            return ['code' => ['0x001014', 'news']];
        }

        // 判断评论内容或者附件两者不能同时为空
        if ((isset($data['content']) && empty($data['content'])) && (!isset($data['attachments']))) {
            return ['code' => ['0x001022', 'news']];
        }
        $commonData = [
            'parent_id'   => $this->defaultValue('parent_id', $data, 0),
            'news_id'     => $newsId,
            'user_id'     => $currentUserId,
            'content'     => $data['content'],
            'blockquote'  => isset($data['blockquote']) ? $data['blockquote'] : 0,
            'create_time' => date('Y-m-d H:i:s'),
        ];
        if ($commonData['parent_id'] !== 0){
            $parentId = $commonData['parent_id'];
            $parentInfo = app($this->newsCommentRepository)->getDetail($parentId);
            // 父评论不存在
            if(!$parentInfo){
                return ['code' => ['0x001026', 'news']];
            }
            // 父评论有父评论，目前评论只能两级
            if($parentInfo->parent_id !== 0){
                return ['code' => ['0x001027', 'news']];
            }
        }

        if ($result = app($this->newsCommentRepository)->insertData($commonData)) {
            // 附件处理
            if (isset($data['attachments'])) {
                app($this->attachmentService)->attachmentRelation("news_comment", $result["comment_id"], $data['attachments']);
            }
            app($this->newsRepository)->editNews(['comments' => $this->getCommentsCount($newsId)], ['news_id' => $newsId]);

            return ['comment_id' => $result->comment_id, 'content' => $result->content, 'created_at' => $result->create_time, 'parent_id' => $result->parent_id, 'blockquote' => $result->blockquote, 'user_id' => $result->user_id];
        }

        return ['code' => ['0x000003', 'common']];
    }
    /**
     * @获取评论数量
     * @param type $newsId
     * @return type 评论数
     */
    public function getCommentsCount($newsId)
    {
        return app($this->newsCommentRepository)->getCommentsCount($newsId);
    }
    /**
     * @删除评论
     * @param type $commentId
     * @return Boolean 成功失败信息
     */
    public function deleteComment($commentId, $currentUserId)
    {
        if ($commentId == 0) {
            return ['code' => ['0x001008', 'news']];
        }
        //自己发布的评论可以删
        if (!$this->checkDeleteCommentAuth($commentId, $currentUserId)) {
            return ['code' => ['0x001009', 'news']];
        }
        $commentInfo = app($this->newsCommentRepository)->getDetail($commentId);
        if(!$commentInfo){
            return ['code' => ['0x001020', 'news']];
        }
        //过期不能删
        $expireTime = $commentInfo->created_at->addMinutes(10);
        if(Carbon::now()->greaterThan($expireTime)){
            return ['code' => ['0x001023', 'news']];
        }
        //有子评论不能删
        $children = app($this->newsCommentRepository)->getChildrenComments($commentId);
        if($children){
            return ['code' => ['0x001024', 'news']];
        }
        if (app($this->newsCommentRepository)->deleteComment($commentId)) {

            app($this->newsCommentRepository)->deleteByWhere(['parent_id' => [$commentId]]);

            app($this->newsRepository)->editNews(['comments' => $this->getCommentsCount($commentInfo->news_id)], ['news_id' => $commentInfo->news_id]);
            return true;
        }

        return ['code' => ['0x000003', 'common']];
    }
    /**
     * @编辑评论
     * @param type $commentId
     * @return Boolean 成功失败信息
     */
    public function editComment($data, $commentId, $userId)
    {
        $oldData = $this->getCommontDetail($data['news_id'], $commentId);
        if (!$oldData) {
            return ['code' => ['0x001015', 'news']];
        }

        if ($oldData->user_id != $userId) {
            return ['code' => ['0x000006', 'common']];
        }
        //过期不能修改
        $expireTime = $oldData->created_at->addMinutes(10);
        if(Carbon::now()->greaterThan($expireTime)){
            return ['code' => ['0x001023', 'news']];
        }
        //有子评论不能修改
        $children = app($this->newsCommentRepository)->getChildrenComments($commentId);
        if($children){
            return ['code' => ['0x001025', 'news']];
        }
        // if ($oldData->content == $data['content']) {
        //     return true;
        // }
        $updateData = [
            'content' => $data['content'],
        ];

        $updateWhere = [
            'comment_id' => [$commentId],
        ];
        // 编辑附件
        if (isset($data['attachments']) && $data['attachments'] != "") {
            app($this->attachmentService)->attachmentRelation("news_comment", $commentId, $data['attachments']);
        }
        return app($this->newsCommentRepository)->updateData($updateData, $updateWhere);
    }

    public function getCommontDetail($newsId, $commentId)
    {
        if ($commentId == 0) {
            return ['code' => ['0x001008', 'news']];
        }

        $comment = app($this->newsCommentRepository)->getDetail($commentId);

        $comment->user_name = get_user_simple_attr($comment->user_id);

        return $comment;
    }
    /**
     * @是否有删除评论权限
     * @param type $commentId
     * @param type $userId
     * @return boolean 有，无
     */
    public function checkDeleteCommentAuth($commentId, $userId)
    {
        return app($this->newsCommentRepository)->checkDeleteCommentAuth($commentId, $userId);
    }

    public function getNewsTypeListOrderParent($param)
    {
        $allNewsType = app($this->newsTypeRepository)->getNewsTypeList();
        $newsTypeOrderParent = [];
        foreach($allNewsType as $key => $value){
            if($value['news_type_parent'] == 0){
                $newsTypeOrderParent[] = $value;
                foreach($allNewsType as $k => $v){
                    if($v['news_type_parent'] == $value['news_type_id']){
                        $newsTypeOrderParent[] = $v;
                    }
                }
            }
        }
//        $param = $this->parseParams($param);
        if(isset($param['search']) && !empty($param['search'])){
            $newsTypeResult = [];
            $newsTypeList = app($this->newsTypeRepository)->getNewsTypeList($param);
            if($newsTypeList){
                $ids = array_column($newsTypeList, 'news_type_id');
                foreach ($newsTypeOrderParent as $type) {
                    if(in_array($type['news_type_id'], $ids)){
                        $newsTypeResult[] = $type;
                    }
                }
            }
            return $newsTypeResult;
        }

        return $newsTypeOrderParent;
    }
    /**
     * @查看新闻获取新闻分类筛选项
     * @param array $param
     * @return array 新闻类别列表
     */
    public function getNewsTypeList($param, $own)
    {
        $param = $this->parseParams($param);
        $type_list = $this->getNewsTypeListOrderParent($param);

        if ($this->needAddUnclassifiedType($param, $own)) {
            $unClassify = ["news_type_id" => 0, "news_type_name" => trans('news.unclassified_news')];
            array_push($type_list, $unClassify);
        }

        return [
            "list" => $type_list,
            "total" => count($type_list)
        ];
    }

    private function needAddUnclassifiedType($param, $own)
    {
        if (! $this->canSeeUnclassifiedNews($own)) {
            return false;
        }
        if (isset($param['search'])) {
            if(isset($param['search']['news_type_name'])){
                // 如果搜索条件中含有未分类新闻，则返回未分类类别
                if (is_array($param['search']['news_type_name'])
                    && stripos(trans('news.unclassified_news'), $param['search']['news_type_name'][0]) !== false
                ) {
                    return true;
                }
            }elseif (isset($param['search']['news_type_id'])) {
                $searchId = $param['search']['news_type_id'];
                if(is_array($searchId)){
                    if(is_array($searchId[0]) && in_array(0, $searchId[0])){
                        return true;
                    }
                    if($searchId[0] == 0 && $searchId[1] == '='){
                        return true;
                    }
                }
            }

        } else if (isset($param['unclassified']) && $param['unclassified']==true) {
            // 如果该接口用来返回种类列表，则需要插入未分类的类别
            return true;
        }

        return false;
    }

    /**
     * @新建新闻获取新闻类别下拉框数据
     * @param array $param
     * @return array 新闻类别列表
     */
    public function getNewsTypeListForSelect($param)
    {
        $param = $this->parseParams($param);
        $type_list = $this->getNewsTypeListOrderParent($param);
        return ['list' => $type_list];
    }
    /**
     * @新闻分类获取列表
     * @param array $param
     * @return array 新闻类别列表
     */
    public function getNewsTypeLists($param)
    {
        $param = $this->parseParams($param);
        $newsTypeTemp = $this->getNewsTypeListOrderParent($param);
        //重组
        $newsTypeList = array();

        foreach ($newsTypeTemp as $k => $v) {
            $newsTypeParent = $v['news_type_parent'];
            $newsTypeID     = $v['news_type_id'];

            if ($newsTypeParent == 0) {
                $newsTypeList[$newsTypeID]          = $v;
                $newsTypeList[$newsTypeID]["items"] = array();
            } else {
                if (!isset($newsTypeList[$newsTypeParent]["items"])) {
                    $newsTypeList[$newsTypeParent]          = $v;
                    $newsTypeList[$newsTypeParent]["items"] = array();
                }
                array_push($newsTypeList[$newsTypeParent]["items"], $v);
            }

        }
        return array_merge([], $newsTypeList);
    }

    /**
     * @新闻分类获取列表
     * @param $parentId
     * @param  $param
     * @param $own
     * @return array 新闻类别列表
     */
    public function getNewsTypeListForCascader($parentId, $param, $own)
    {
        if ($parentId == 'all') {
            $parentId = 0;
        }
        $allType = app($this->newsTypeRepository)->getNewsTypeList($this->parseParams($param));
        $newsTypeList = [];
        foreach ($allType as $key => $value) {
            if ($value['news_type_parent'] == $parentId) {
                $new = $value;
                $new['has_children'] = false;
                // 二级分类无下级
                if ($parentId == 0){
                    foreach ($allType as $k => $v) {
                        if ($v['news_type_parent'] == $value['news_type_id']) {
                            $new['has_children'] = true;
                            break;
                        }
                    }
                }
                $newsTypeList[] = $new;
            }
        }
        if (isset($param['unclassified']) && $param['unclassified'] && $parentId == 0 && $this->canSeeUnclassifiedNews($own)){
            $newsTypeList[] = ["news_type_id" => 0, "news_type_name" => trans('news.unclassified_news')];
        }
        return $newsTypeList;
    }

    /**
     * 是否存在未分类新闻
     * @return bool
     */
    public function existUnclassifiedNews()
    {
        /** @var NewsRepository $newsRepository */
        $newsRepository = app($this->newsRepository);

        return (bool) $newsRepository->entity->select('news_id')->where('news_type_id', 0)->first();
    }

    /**
     * 当前用户是否有未分类新闻
     * @param $own
     * @return bool
     */
    public function canSeeUnclassifiedNews($own)
    {
        /** @var NewsRepository $newsRepository */
        $newsRepository = app($this->newsRepository);

        return (bool) $newsRepository->entity->select('news_id')
            ->canSee($own['user_id'])
            ->where('news_type_id', 0)
            ->first();
    }

    /**
     * @新建新闻类别
     * @param type $data
     * @return boolean | news_type_id
     */
    public function addNewsType($data)
    {
        $temt = isset($data['sub_names']) ? json_decode($data['sub_names'], true) : [];
        // 验证名称长度
        if(!isset($data['parent_name']) || trim($data['parent_name']) == ''){
            return ['code' => ['0x001003', 'news']];
        }
        if( strlen($data['parent_name']) > 50){
            return ['code' => ['0x001029', 'news']];
        }
        foreach ($temt as $v) {
            if (isset($v['news_type_name']) && strlen($v['news_type_name']) > 50){
                return ['code' => ['0x001029', 'news']];
            }
        }
        //增加
        $newsData = [
            "news_type_name"   => $data['parent_name'],
            "news_type_parent" => 0,
            "sort"  => $data['news_type_order'],
        ];

        $resultData = app($this->newsTypeRepository)->insertData($newsData);
        $parentId   = $resultData->news_type_id;

        foreach ($temt as $v) {
            if (!empty(trim($v['news_type_name']))) {
                $newsData = [
                    "news_type_name"   => $v['news_type_name'],
                    "news_type_parent" => $parentId,
                    "sort"  => isset($v['news_type_order']) ? $v['news_type_order'] : 0,
                ];
                app($this->newsTypeRepository)->insertData($newsData);
            }
        }

        return $resultData;
    }
    /**
     * @编辑新闻类别
     * @param type $data
     * @param type $condition
     * @return boolean 成功失败信息
     */
    public function editNewsType($data, $newsTypeId)
    {
        if(!$newsTypeId) {
            return ['code' => ['0x001002', 'news']];
        }

        if(!isset($data['parent_name']) || trim($data['parent_name']) == ''){
            return ['code' => ['0x001003', 'news']];
        }

        // 验证名称长度
        if (strlen($data['parent_name']) > 50){
            return ['code' => ['0x001029', 'news']];
        }

        $temps = isset($data['sub_names']) ? json_decode($data['sub_names'], true) : [];
        $tempIds = array_column($temps, 'news_type_id');
        foreach ($temps as $v) {
            if (isset($v['news_type_name']) && strlen($v['news_type_name']) > 50){
                return ['code' => ['0x001029', 'news']];
            }
        }

        app($this->newsTypeRepository)->updateData(['news_type_name' => $data['parent_name'], "sort" => $data['news_type_order']], ["news_type_id" => $newsTypeId]);
        if(!isset($data['sub_names'])){
            return true;
        }
        //更新二级菜单
        //1 获取当前菜单父ID为$newsTypeId的子菜单
        $typeSubList  = app($this->newsTypeRepository)->entity->where('news_type_parent', $newsTypeId)->withCount('news')->get();
        $countSubList = $typeSubList->count();
        if ($countSubList > 0) {
            $preIds = [];
            foreach ($typeSubList as $sub) {
                // 判断是否有删除有新闻的分类
                if (!in_array($sub->news_type_id, $tempIds) && $sub->news_count > 0){
                    return ['code' => ['0x001016', 'news']];
                }
                $preIds[] = $sub["news_type_id"];
            }
        }
        //2 删除 更新 增加

        $updateIds = [];

        foreach ($temps as $temp) {
            if (!empty(trim($temp["news_type_name"]))) {
                if ($temp["news_type_id"] > 0) {
                    $updateIds[] = $temp["news_type_id"];
                    //更新
                    $newsOrder = isset($temp['news_type_order']) ? $temp['news_type_order'] : 0;
                    app($this->newsTypeRepository)->updateData(["news_type_name" => $temp['news_type_name'],"sort" => $newsOrder], ['news_type_id' => $temp["news_type_id"]]);
                } else {
                    //插入
                    $newsData = [
                        "news_type_name"   => $temp['news_type_name'],
                        "news_type_parent" => $data["parent_id"],
                        "sort"  => isset($temp['news_type_order']) ? $temp['news_type_order'] : 0,
                    ];

                    app($this->newsTypeRepository)->insertData($newsData);
                }
            }
        }

        if ($countSubList > 0) {
            //取两个ID的差，删除
            $deleteIds = array_diff($preIds, $updateIds);
            //删除
            app($this->newsTypeRepository)->deleteById($deleteIds);
        }

        return true;
    }
    //处理过期置顶新闻
    public function cancelOutTimeTop()
    {
        app($this->newsRepository)->cancelOutTimeTop();
    }
    /**
     * @获取新闻类别详情
     * @param type $condition
     * @return object 新闻类别详情
     */
    public function newsTypeDetail($newsTypeId)
    {
        $newsTypeData = app($this->newsTypeRepository)->getDetail($newsTypeId);

        if ($newsTypeData["news_type_parent"] == 0) {

            $where = [
                "news_type_parent" => [$newsTypeData['news_type_id'], "="],
            ];
            $item = app($this->newsTypeRepository)->getNewsTypeList(['search' => $where, "withNewsCount" => true]);
            $params = [];
            foreach ($item as $v) {
                $dataParam["news_type_id"]   = $v["news_type_id"];
                $dataParam["news_type_name"] = $v["news_type_name"];
                $dataParam["news_type_order"] = $v["sort"];
                $dataParam["news_count"] = $v["news_count"];
                array_push($params, $dataParam);
            }

            $newsTypeData['item'] = json_encode($params);
        }

        return $newsTypeData;
    }

    /**
     * 表单建模列表需要的分类
     * @param $newsTypeId
     * @return array
     */
    public function getNewsTypeForFormModelingList($newsTypeId)
    {
        $idArray = explode(',', $newsTypeId);

        $types = app($this->newsTypeRepository)->entity->find($idArray)->toArray();

        if(in_array(0, $idArray)){
            array_push($types, ["news_type_id" => 0, "news_type_name" => trans('news.unclassified_news')]);
        }

        return $types;
    }

    /**
     * @删除新闻类别
     * @param type $newsTypeId
     * @return boolean 成功失败信息
     */
    public function deleteNewsType($newsTypeId)
    {
        //获取改菜单及其下级菜单
        $tempDatas = app($this->newsTypeRepository)->getDataBywhere(['news_type_parent' => [$newsTypeId, "="]]);

        $deleteIds = [];

        foreach ($tempDatas as $temp) {
            $deleteIds[] = $temp["news_type_id"];
        }

        $deleteIds[] = intval($newsTypeId);

        //判断改成员组中是否存在在列表中使用的分类
        $listData = app($this->newsRepository)->getDataBywhere(['news_type_id' => [$deleteIds, "in"]]);

        if (count($listData)) {
            return ['code' => ['0x001016', 'news']];
        }

        return app($this->newsTypeRepository)->deleteByWhere(['news_type_id' => [$deleteIds, "in"]]);
    }
    /**
     * @处理新闻简介
     * @param type $newsDesc
     * @param type $content
     * @return string 新闻简介
     */
    public function handleNewsDesc($newsDesc, $content)
    {
        if ($newsDesc != '') {
            $newsDesc = trim($newsDesc);
            if (mb_strlen($newsDesc, 'UTF-8') > 200) {
                $newsDesc = mb_substr(strip_tags($newsDesc), 0, 200, 'UTF-8') . '...';
            }
        } else {
            $newsDesc = str_replace('&nbsp;', ' ', $content);
            $newsDesc = html_entity_decode(trim(strip_tags($newsDesc)));
            if (mb_strlen($newsDesc, 'UTF-8') > 200) {
                $newsDesc = mb_substr($newsDesc, 0, 200, 'UTF-8') . '...';
            }
        }
        return $newsDesc;
    }
    /**
     * @处理新闻字段
     * @param type $fields
     * @return array 新闻字段
     */
    public function handleFields($fields)
    {
        $_fields = [];
        foreach ($fields as $value) {
            if ($value == 'news_type_name') {
                $_fields[] = 'news_type.' . $value;
            } else if ($value == 'creator') {
                $_fields[] = 'user.user_name as creator_name';
                $_fields[] = 'user.user_id as creator';
            } else {
                $_fields[] = 'news.' . $value;
            }
        }
        return $_fields;
    }
    /**
     * @处理新闻查询条件
     * @param type $search
     * @return array 新闻查询条件
     */
    public function handleSearch($search)
    {
        if (empty($search)) {
            return [];
        }
        $newsSearch = [];
        foreach ($search as $key => $value) {
            if ($key == 'user_name') {
                $newsSearch['user.user_name'] = $value;
            } else if ($key == 'read') {
                $newsSearch[$key] = $value;
            } else {
                $newsSearch['news.' . $key] = $value;
            }
        }
        return $newsSearch;
    }

    private function defaultValue($key, $data, $default)
    {
        return isset($data[$key]) ? $data[$key] : $default;
    }

    public function flowOutSendToNews($data)
    {
        if (!isset($data['creator']) || empty($data['creator'])) {
            return ['code' => ['0x001019', 'news']];
        }
        if (!isset($data['title']) || empty($data['title'])) {
            return ['code' => ['0x001006', 'news']];
        }
        if (!isset($data['content']) || empty($data['content'])) {
            return ['code' => ['0x001010', 'news']];
        }
        $userName = app(UserRepository::class)->getUserName($data['creator']);
        $userDetail = own();
        $userInfo = [
            'user_id' => $data['creator'],
            'user_name' => $userName,
            'role_id' => $userDetail['role_id'],
            'dept_id' => $userDetail['dept_id'],
        ];

        $result = $this->addNews($data, $userInfo);

        if(isset($result['code'])){
            return $result;
        }

        return [
            'status' => 1,
            'dataForLog' => [
                [
                    'table_to' => 'news',
                    'field_to' => 'news_id',
                    'id_to' => $result['news_id']
                ]
            ]
        ];
    }

    /**
     * 流程外发更新
     * @param $data
     * @return array
     */
    public function flowOutSendToUpdateNews($data)
    {
        if (empty($data['unique_id'])) {
            return ['code' => ['0x001005', 'news']];
        }
        if(empty($data['data'])) {
            return ['code' => ['0x000003', 'common']];
        }
        $res = $data['data'];
        if(array_key_exists('title',$res) && empty($res['title'])){
            return ['code' => ['0x001006', 'news']];
        }
        if(array_key_exists('content',$res)  && empty($res['content'])){
            return ['code' => ['0x001010', 'news']];
        }
        $result = $this->editNews($data['data'], $data['unique_id'],own());
        if(isset($result['code'])){
            return $result;
        }

        return [
            'status' => 1,
            'dataForLog' => [
                [
                    'table_to' => 'news',
                    'field_to' => 'news_id',
                    'id_to' => $data['unique_id']
                ]
            ]
        ];
    }

    /**
     * 流程外发删除
     * @param $data
     */
    public function flowOutSendToDeleteNews($data)
    {
        if (!isset($data['unique_id']) || empty($data['unique_id'])) {
            return ['code' => ['0x001005', 'news']];
        }

        $newsId = $data['unique_id'];

        $result = $this->deleteNews($newsId);

        if(isset($result['code'])){
            return $result;
        }

        return [
            'status' => 1,
            'dataForLog' => [
                [
                    'table_to' => 'news',
                    'field_to' => 'news_id',
                    'id_to' => $newsId
                ]
            ]
        ];
    }

    //统计总数未读
    public function countMyNews($userId)
    {
        $total = app($this->newsRepository)->getNewsCount([], $userId);
        $param['search']['read'] = 0;
        $unread = app($this->newsRepository)->getNewsCount($param, $userId);
        return compact('total', 'unread');
    }

    /**
     * 评论设置
     * @param $data
     * @return mixed
     */
    public function setNewsSettings($data){
        foreach($data as $key => $value) {
            $setting = app($this->newsSettingsRepository)->entity->find($key);
            $setting->setting_value = $value;
            if($setting->isDirty()){
                $setting->save();
                Redis::hset('eoffice_news_settings', $key, $value);
            }
        }
        return true;

    }

    /**
     * 获取评论设置
     * @return array
     */
    public function getNewsSettings()
    {

        $settings = Redis::hGetAll('eoffice_news_settings');
        if($settings !== []){
            return $settings;
        }
        $settings = app($this->newsSettingsRepository)->getSettings();
        $res = [];
        foreach ($settings as $value){
            $res[$value->setting_key] = $value->setting_value;
            Redis::hSet('eoffice_news_settings', $value->setting_key, $value->setting_value);
        }
        return $res;
    }
    /**
     * 使用消息队列更新全站搜索数据
     *
     * @param   string|int|array    $ids
     */
    public function updateGlobalSearchDataByQueue($ids)
    {
        try {
            ElasticsearchProducer::sendGlobalSearchNewsMessage($ids);
        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
        }
    }
}
