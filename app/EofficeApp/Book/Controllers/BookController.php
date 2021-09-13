<?php

namespace App\EofficeApp\Book\Controllers;

use App\EofficeApp\Base\Controller;
use App\EofficeApp\Book\Requests\BookRequest;
use App\EofficeApp\Book\Services\BookService;
use Illuminate\Http\Request;

/**
 * 图书管理模块控制器
 *
 * @author  朱从玺
 *
 * @since   2015-10-30
 *
 */
class BookController extends Controller
{
    /**
     * [$bookService 图书管理模块service]
     *
     * @var [object]
     */
    protected $bookService;

    /**
     * [$request request验证]
     *
     * @var [object]
     */
    protected $request;

    protected $bookRequest;

    public function __construct(
        BookService $bookService,
        BookRequest $bookRequest,
        Request $request
    ) {
        parent::__construct();

        $this->bookService   = $bookService;
        $this->bookRequest   = $bookRequest;
        $this->request       = $request;

        $this->formFilter($request, $bookRequest);
    }

    /**
     * [createBook 创建图书]
     *
     * @author 朱从玺
     *
     * @since 2015-10-30 创建
     *
     * @return [json]     [创建结果]
     */
    public function createBook()
    {
        $bookData = $this->request->all();

        $result = $this->bookService->createBook($bookData);

        return $this->returnResult($result);
    }
    public function createBookInfo()
    {
        $bookData = $this->request->all();

        $result = $this->bookService->createBookInfo($bookData);

        return $this->returnResult($result);
    }


    /**
     * [getBookInfo 获取图书信息]
     *
     * @author 朱从玺
     *
     * @param  [int]       $bookId [图书ID]
     *
     * @since 2015-10-30 创建
     *
     * @return [json]              [查询结果]
     */
    public function getBookInfo($bookId)
    {
        $result = $this->bookService->getBookInfo($bookId);

        return $this->returnResult($result);
    }

    /**
     * [modifyBookInfo 编辑图书信息]
     *
     * @author 朱从玺
     *
     * @param  [int]       $bookId [图书ID]
     *
     * @since 2015-10-30 创建
     *
     * @return [json]              [编辑结果]
     */
    public function modifyBookInfo($bookId)
    {
        $newData = $this->request->all();
        return $this->returnResult($this->bookService->modifyBookInfo($bookId, $newData));
    }

    public function editBookInfo($bookId)
    {
        $newData = $this->request->all();
        return $this->returnResult($this->bookService->editBookInfo($bookId, $newData));
    }

    /**
     * [getBookList 获取图书列表]
     *
     * @author 朱从玺
     *
     * @since 2015-10-30 创建
     *
     * @return [json]      [查询结果]
     */
    public function getBookList()
    {
        $param = $this->request->all();

        $bookList = $this->bookService->getBookList($param);

        return $this->returnResult($bookList);
    }
    /**
     * [getBookList 图书选择器获取图书列表]
     *
     * @author 朱从玺
     *
     * @since 2015-10-30 创建
     *
     * @return [json]      [查询结果]
     */
    public function getBookListForSelect()
    {
        $param = $this->request->all();

        $bookList = $this->bookService->getBookListForSelect($param);

        return $this->returnResult($bookList);
    }

    /**
     * [getBookMiddleList 获取图书中间列]
     *
     * @author 朱从玺
     *
     * @since  2015-10-30 创建
     *
     * @return [json]            [查询结果]
     */
    public function getBookMiddleList()
    {
        $middleList = $this->bookService->getBookMiddleList();

        return $this->returnResult($middleList);
    }

    /**
     * [deleteBook 删除图书]
     *
     * @author 朱从玺
     *
     * @param  [int]       $bookId [图书ID]
     *
     * @since 2015-10-30 创建
     *
     * @return [json]              [删除结果]
     */
    public function deleteBook($bookId)
    {
        $result = $this->bookService->deleteBook($bookId);

        return $this->returnResult($result);
    }

    /**
     * 批量删除图书
     * @return array
     * @creatTime 2021/1/4 16:17
     * @author [dosy]
     */
    public function batchDeleteBook()
    {
        $result = $this->bookService->batchDeleteBook($this->request->all());

        return $this->returnResult($result);
    }

    /**
     * [createBookType 创建图书类型]
     *
     * @author 朱从玺
     *
     * @since 2015-10-30 创建
     *
     * @return [json]         [创建结果]
     */
    public function createBookType()
    {
        $bookTypeData = $this->request->all();

        $result = $this->bookService->createBookType($bookTypeData);

        return $this->returnResult($result);
    }

    /**
     * [modifyBookType 编辑图书类型]
     *
     * @author 朱从玺
     *
     * @param  [int]          $bookTypeId [编辑数据]
     *
     * @since 2015-10-30 创建
     *
     * @return [json]                     [编辑结果]
     */
    public function modifyBookType($bookTypeId)
    {
        $newData = $this->request->all();

        $result = $this->bookService->modifyBookType($bookTypeId, $newData);

        return $this->returnResult($result);
    }

    /**
     * [getBookTypeInfo 获取图书类型]
     *
     * @author 朱从玺
     *
     * @param  [int]           $bookTypeId [图书类型ID]
     *
     * @since 2015-10-30 创建
     *
     * @return [json]                      [查询结果]
     */
    public function getBookTypeInfo($bookTypeId)
    {
        $result = $this->bookService->getBookTypeInfo($bookTypeId);

        return $this->returnResult($result);
    }

    /**
     * [getBookTypeList 获取图书类型列表]
     *
     * @author 朱从玺
     *
     * @since 2015-10-30 创建
     *
     * @return [type]          [查询结果]
     */
    public function getBookTypeList(Request $request)
    {
        $result = $this->bookService->getBookTypeList($request->all());

        return $this->returnResult($result);
    }

    /**
     * [getBookTypeInfo 删除图书类型]
     *
     * @author 朱从玺
     *
     * @param  [int]           $bookTypeId [图书类型ID]
     *
     * @since 2015-10-30 创建
     *
     * @return [json]                      [删除结果]
     */
    public function deleteBookType($bookTypeId)
    {
        $result = $this->bookService->deleteBookType($bookTypeId);

        return $this->returnResult($result);
    }

    /**
     * [createBookManage 创建图书借阅记录]
     *
     * @author 朱从玺
     *
     * @since 2015-10-30 创建
     *
     * @return [json]           [创建结果]
     */
    public function createBookManage()
    {
        $manageData = $this->request->all();
        $result = $this->bookService->createBookManage($manageData);
        return $this->returnResult($result);
    }

    public function addBookManage()
    {
    	$manageData = $this->request->all();
        $result = $this->bookService->addBookManage($manageData);
        return $this->returnResult($result);
    }

    /**
     * [modifyBookManage 编辑图书借阅记录]
     *
     * @author 朱从玺
     *
     * @param  [int]            $bookManageId [借阅ID]
     *
     * @since 2015-10-30 创建
     *
     * @return [json]                         [编辑结果]
     */
    public function modifyBookManage($bookManageId)
    {
        $newManageData = $this->request->all();

        $result = $this->bookService->modifyBookManage($bookManageId, $newManageData);

        return $this->returnResult($result);
    }

    public function editBookManage($bookManageId)
    {
        $newManageData = $this->request->all();

        $result = $this->bookService->editBookManage($bookManageId, $newManageData);

        return $this->returnResult($result);
    }

    /**
     * [returnBook 归还图书]
     *
     * @author 朱从玺
     *
     * @param  [int]      $bookManageId [借阅ID]
     *
     * @since 2016-04-14 创建
     *
     * @return [bool]                   [归还结果]
     */
    public function returnBook($bookManageId)
    {
        $result = $this->bookService->returnBook($bookManageId);

        return $this->returnResult($result);
    }

    /**
     * [getBookManageInfo 获取图书借阅数据]
     *
     * @author 朱从玺
     *
     * @param  [int]           $bookManageId [借阅数据ID]
     *
     * @since 2015-10-30 创建
     *
     * @return [json]                        [查询结果]
     */
    public function getBookManageInfo($bookManageId)
    {
        $result = $this->bookService->getBookManageInfo($bookManageId);

        return $this->returnResult($result);
    }

    /**
     * [getBookManageList 获取图书借阅列表]
     *
     * @author 朱从玺
     *
     * @since 2015-10-30 创建
     *
     * @return [json]            [查询结果]
     */
    public function getBookManageList()
    {
        $param = $this->request->all();

        $result = $this->bookService->getBookManageList($param, $this->own);

        return $this->returnResult($result);
    }

    /**
     * [deleteBookManage 删除图书借阅数据]
     *
     * @author 朱从玺
     *
     * @param  [int]           $bookManageId [借阅ID]
     *
     * @since 2015-10-30 创建
     *
     * @return [json]                        [删除结果]
     */
    public function deleteBookManage($bookManageId)
    {
        $result = $this->bookService->deleteBookManage($bookManageId);

        return $this->returnResult($result);
    }
    /**
     * [getBookName 获取图书名称]
     *
     * @author 史瑶
     *
     * @param
     *
     * @since 2016-6-16 创建
     *
     * @return [json]              [查询结果]
     */
    public function getBookName()
    {
        $param  = $this->request->all();
        $result = $this->bookService->getBookName($param);
        return $this->returnResult($result);
    }

    public function getBookBorrowRange()
    {
        $result = $this->bookService->getBookBorrowRange();
        return $this->returnResult($result);
    }

    public function getBookReturnStatus()
    {
        $result = $this->bookService->getBookReturnStatus();
        return $this->returnResult($result);
    }

    public function getBookRemainTotal()
    {
        $result = $this->bookService->getBookRemainTotal($this->request->all());
        return $this->returnResult($result);
    }

    public function setBookRemainTotal($bookId)
    {
        $result = $this->bookService->setBookRemainTotal($bookId, $this->request->all());
        return $this->returnResult($result);
    }

    public function getBookInfoDetail($bookId)
    {
        $result = $this->bookService->getBookInfoDetail($bookId);
        return $this->returnResult($result);
    }

//    我借阅的书的类型
    public function getMyBorrowTypes()
    {
        $result = $this->bookService->getMyBorrowTypes($this->own);
        return $this->returnResult($result);
    }

    public function getMyBorrowList()
    {
        $result = $this->bookService->getMyBorrowList($this->request->all(), $this->own);
        return $this->returnResult($result);
    }

    public function getBookIDsByWhere()
    {
        $result = $this->bookService->getBookIDsByWhere($this->request->all());
        return $this->returnResult($result);
    }


}
