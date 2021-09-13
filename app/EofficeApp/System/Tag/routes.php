<?php

/*
  |--------------------------------------------------------------------------
  | Application Routes
  |--------------------------------------------------------------------------
  |
  | Here is where you can register all of the routes for an application.
  | It is a breeze. Simply tell Lumen the URIs it should respond to
  | and give it the Closure to call when that URI is requested.
  |
 */

$routeConfig = [
    // //获取标签分类
    // ['tag-types', 'getIndexTagType'],
    // //添加标签分类
    // ['tag-types', 'createTagType', 'post'],
    // //编辑标签分类
    // ['tag-types/{id}', 'editTagType', 'put'],
    // //删除标签分类
    // ['tag-types/{id}', 'deleteTagType', 'delete'],
    //获取标签(客户那里有一个标签选择器)
    ['tag/tags', 'getTagList'],
    //添加标签
    ['tag/tags', 'createTag', 'post'],
    // 外部调用标签api，获取标签数据(公共api)
    ['tag/tags-external', 'getTagExternalList'],
    //编辑标签
    ['tag/tags/{id}', 'editTag', 'put'],
    //删除标签
    ['tag/tags/{id}', 'deleteTag', 'delete'],
];