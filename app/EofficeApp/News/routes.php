<?php
// 237模块id
//查看130 分类235 新建 238  审核321
$routeConfig = [
    //审核新闻的详情
    ['news/verifynews', 'verify', [321]],
    //审核通过
    ['news/verifynews/{newsIds}/approve', 'approveNews', [321]],
    //审核拒绝
    ['news/verifynews/{newsIds}/refuse', 'refuseNews', [321]],
    //门户列表
    ['news/portal', 'getProtalList',[130]],
    //不带分组分类
    ['news/newstype', 'newsTypeList'],
    //带分组分类
    ['news/newstypes', 'newsTypeLists', [237]],
    //选择器用分类
    ['news/newstypeforselect', 'getNewsTypeListForSelect', [238, 130]],
    //联动用分类
    ['news/newstypeforcascader/{parentId}', 'getNewsTypeListForCascader', [238, 130]],
    //添加分类
    ['news/newstype', 'addNewsType', 'post', [235]],
    //分类详情
    ['news/newstype/{newsTypeId}', 'newsTypeDetail', [235]],
    //编辑分类
    ['news/newstype/{newsTypeId}', 'editNewsType', 'post', [235]],
    //删除分类
    ['news/newstype/{newsTypeId}', 'deleteNewsType', 'delete', [235]],
//    ['news/getmaxsort', 'getMaxsort', []],
    //新闻列表
    ['news', 'getList', [130]],
    //添加新闻
    ['news', 'addNews', 'post', [238]],
    //统计
    ['news/statistics', 'countMyNews'],
    //新闻设置
    ['news/setting', 'getNewsSettings'],
    ['news/setting', 'setNewsSettings', 'post', [232]],
    //新闻详情
    ['news/{newsId}', 'newsDetail',[130]],
    //编辑新闻
    ['news/{newsId}', 'editNews', 'post', [130]],
    // ['newsportal', 'getProtalList',[130,235]],
    //删除新闻
    ['news/{newsId}', 'deleteNews', 'delete', [130]],
    //置顶新闻
    ['news/{newsId}/top', 'top', [130]],
    //取消置顶
    ['news/{newsId}/cancel-top', 'cancelTop', [130]],
    //发布新闻
    ['news/{newsId}/publish', 'publish', [130]],
    //撤回新闻
    ['news/{newsId}/cancel-publish', 'cancelPublish', [130]],
    //审核新闻列表
    ['news/verify/{newsId}', 'showVerifyNews', [321]],
    //获取news_id新闻的评论列表
    ['news/{newsId}/comments', 'commentList', [130]],
    //添加评论
    ['news/{newsId}/comments/add', 'addComment', 'post', [130]],
    //获取评论详情
    ['news/{newsId}/comments/{commentId}', 'getCommontDetail', [130]],
    //获取comment_id评论的子评论
    ['news/comments/{commentId}/children', 'getChildrenComments', [130]],
    //删除评论
    ['news/comments/{commentId}', 'deleteComment', 'delete', [130]],
    //编辑评论
    ['news/comments/{commentId}', 'editComment', 'put', [130]],
];
