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
    //获取提示语类别列表
    ['prompt/types', 'getPromptTypes', [117]],
    //添加提示语类别
    ['prompt/types', 'createPromptType', 'post', [117]],
    //编辑提示语类别
    ['prompt/types/{typeId}', 'getPromptType', [117]],
    //编辑提示语类别
    ['prompt/types/{typeId}', 'editPromptType', 'put', [117]],
    //删除提示语类别
    ['prompt/types/{typeId}', 'deletePromptType', 'delete', [117]],
    //查询提示语列表
    ['prompt', 'getPrompts', [117]],
    //添加提示语
    ['prompt', 'addPrompt', 'post', [117]],
    //编辑提示语
    ['prompt/{id}', 'editPrompt', 'put', [117]],
    //删除提示语
    ['prompt/{id}', 'deletePrompt', 'delete', [117]],
    //登录提示语
    ['prompt/login', 'getLoginPrompts'],
];