<?php
//233
//    35: 图书查询
//    77: 我的借阅
//    78: 图书类别定义
//    79: 图书信息录入
//    80: 图书借阅归还
$routeConfig = [
    //创建图书
//    ['book', 'createBook', 'post', [79]],
    //编辑图书信息
//    ['book/{book_id}', 'modifyBookInfo', 'put', [79]],
    //获取图书列表
    ['book/list', 'getBookList', [35, 80]],
    //查询图书名
//    ['book/book_name', 'getBookName'],
    //获取图书中间列
//    ['book/book-list', 'getBookMiddleList'],
    //删除图书
    ['book/{bookId}', 'deleteBook', 'delete', [79]],
    //创建图书类型
    ['book/book-type', 'createBookType', 'post', [78]],
    //批量删除图书
    ['book/batch-delete', 'batchDeleteBook', 'post', [78]],
    //编辑图书类型
    ['book/book-type/{bookTypeId}', 'modifyBookType', 'put', [78]],
    //获取图书类型
    ['book/book-type/{bookTypeId}', 'getBookTypeInfo', [78]],
    //获取图书类型列表
    ['book/book-type', 'getBookTypeList', [233]],
    //删除图书类型
    ['book/book-type/{bookTypeId}', 'deleteBookType', 'delete', [78]],
    //创建图书借阅记录
//    ['book/book-manage', 'createBookManage', 'post'],
    //编辑图书借阅记录
//    ['book/book-manage/{book_manage_id}', 'modifyBookManage', 'put', [80]],
    //归还图书
    ['book/book-manage/return/{bookManageId}', 'returnBook', 'put', [80]],
    //获取图书借阅数据
    ['book/book-manage/{bookManageId}', 'getBookManageInfo', [80]],
    //获取图书借阅列表
    ['book/book-manage', 'getBookManageList'],
    //删除图书借阅数据
    ['book/book-manage/{bookManageId}', 'deleteBookManage', 'delete', [80]],
    //图书选择器数据
    ['book/book-list/for-select', 'getBookListForSelect', [233]],
//    ['book/book-borrow-range', 'getBookBorrowRange'],
//    ['book/book-return-status', 'getBookReturnStatus'],
//    ['book/book-remain', 'getBookRemainTotal', 'post'],
//    ['book/book-set-remain/{book_id}', 'setBookRemainTotal', 'put'],
    //添加图书信息
    ['book/add', 'createBookInfo', 'post', [79]],
    //编辑图书信息
    ['book/edit/{bookId}', 'editBookInfo', 'put', [79]],
    //编辑借阅记录
    ['book/book-manage/edit/{bookManageId}', 'editBookManage', 'put', [80]],
    //添加借阅记录
    ['book/book-manage/add', 'addBookManage', 'post', [35, 80]],
    //图书详情
    ['book/detail/{bookId}', 'getBookInfoDetail', [35]],
    //我的借阅图书类型
    ['book/my-type', 'getMyBorrowTypes', [77]],
    //我的借阅图书列表
    ['book/my-list', 'getMyBorrowList', [77]],
    //获取满足条件的id数组
    ['book/ids', 'getBookIDsByWhere', [77]],
    //获取图书信息
    ['book/{bookId}', 'getBookInfo', [35]],



];
