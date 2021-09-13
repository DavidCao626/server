<?php

namespace App\EofficeApp\Book\Services;

use App\EofficeApp\Attachment\Services\AttachmentService;
use App\EofficeApp\Base\BaseService;
use App\EofficeApp\Book\Repositories\BookInfoRepository;
use App\EofficeApp\Book\Repositories\BookManageRepository;
use App\EofficeApp\Book\Repositories\BookTypeRepository;
use App\EofficeApp\FormModeling\Services\FormModelingService;
use App\EofficeApp\System\CustomFields\Repositories\FieldsRepository;
use App\EofficeApp\System\Department\Services\DepartmentService;
use App\EofficeApp\User\Services\UserService;
use Illuminate\Support\Facades\DB;

/**
 * 图书管理模块service
 *
 * @author  朱从玺
 *
 * @since   2015-10-30
 *
 */
class BookService extends BaseService
{
    /**
     * [$bookInfoRepository book_info表资源库]
     *
     * @var [object]
     */
    protected $bookInfoRepository;

    /**
     * [$bookInfoRepository book_manage表资源库]
     *
     * @var [object]
     */
    protected $bookManageRepository;

    /**
     * [$bookInfoRepository book_type表资源库]
     *
     * @var [object]
     */
    protected $bookTypeRepository;

    /**
     * [$userService 用户模块service]
     *
     * @var [object]
     */
    protected $userService;
    protected $departmentService;
    private $attachmentService;
    private $formModelingService;
    private $fieldsRepository;

    public function __construct()
    {
        $this->bookInfoRepository = 'App\EofficeApp\Book\Repositories\BookInfoRepository';
        $this->bookManageRepository = 'App\EofficeApp\Book\Repositories\BookManageRepository';
        $this->bookTypeRepository = 'App\EofficeApp\Book\Repositories\BookTypeRepository';
        $this->userService = 'App\EofficeApp\User\Services\UserService';
        $this->departmentService = 'App\EofficeApp\System\Department\Services\DepartmentService';
        $this->attachmentService = 'App\EofficeApp\Attachment\Services\AttachmentService';
        $this->formModelingService = 'App\EofficeApp\FormModeling\Services\FormModelingService';
        $this->fieldsRepository = 'App\EofficeApp\System\CustomFields\Repositories\FieldsRepository';
    }

    /**
     * [createBook 新建图书]
     *
     * @author 朱从玺
     *
     * @param  [array]     $bookData [新建数据]
     *
     * @since  2015-10-30 创建
     *
     * @return [bool]               [新建结果]
     */
    public function createBook($bookData)
    {
        if (isset($bookData['book_total']) && $bookData['book_total'] !== '') {
            $bookData['book_remainder'] = $bookData['book_total'];
        }

        if ($result = app($this->bookInfoRepository)->insertData($bookData)) {
            if (isset($bookData['attachment_id']) && $bookData['attachment_id'] != "") {
                app($this->attachmentService)->attachmentRelation("book", $result->id, $bookData['attachment_id']);
                unset($bookData['attachment_id']);
            }
        };

        if (!$result) {
            return array('code' => array('0x000003', 'common'));
        }

        return $result;
    }

    public function createBookInfo($data)
    {
        $input = isset($data['data']) ? $data['data'] : [];
        $tableKey = isset($data['tableKey']) ? $data['tableKey'] : '';
        if (isset($input['borrow_range'])) {
            $input['borrow_range'] = intval($input['borrow_range']);
        }
        if (isset($input['book_total'])) {
            if (!is_int_or_string_int($input['book_total']) || $input['book_total'] < 0) {
                return ['code' => ['0x040013', 'book']];
            }
            $input['book_remainder'] = $input['book_total'];
        }
        $result = app($this->formModelingService)->addCustomData($input, $tableKey);
        return $result;
    }

    /**
     * 流程外发新建图书信息
     * @param $data
     * @return array
     */
    public function flowOutCreateBookInfo($data)
    {
        $result = $this->createBookInfo($data);
        if (isset($result['code'])) {
            return $result;
        }
        if (!$result) {
            return ['code' => ['0x000003', 'common']];
        }

        return [
            'status' => 1,
            'dataForLog' => [
                [
                    'table_to' => 'book_info',
                    'field_to' => 'id',
                    'id_to' => $result,
                ],
            ],
        ];
    }

    /**
     * 流程外发更新图书信息
     * @param $data
     * @return array
     */
    public function flowOutUpdateBookInfo($data)
    {
        if (empty($data['unique_id'])) {
            return ['code' => ['book_id_is_empty', 'book']];
        }
        if (empty($data['data'])) {
            return ['code' => ['0x000003', 'common']];
        }
        $newBookData = $data['data'];
        $bookInfo = $this->getBookInfo($data['unique_id']);
        if(empty($bookInfo) || isset($bookInfo['code'])){
            return ['code' => ['0x016035', 'fields']];
        }
        if(isset($newBookData['book_remainder'])){
            $book_total = isset($newBookData['book_total']) ? $newBookData['book_total'] : $bookInfo->book_total;
            $differentNumber = $bookInfo->book_total - $book_total;
            $data['data']['book_remainder'] = $bookInfo->book_remainder - $differentNumber;
        }

        $result = $this->editBookInfo($data['unique_id'], $data);

        if (isset($result['code'])) {
            return $result;
        }

        return [
            'status' => 1,
            'dataForLog' => [
                [
                    'table_to' => 'book_info',
                    'field_to' => 'id',
                    'id_to' => $data['unique_id'],
                ],
            ],
        ];
    }

    /**
     * 流程外发删除图书信息
     * @param $data
     * @return array
     */
    public function flowOutDeleteBookInfo($data)
    {
        if (empty($data['unique_id'])) {
            return ['code' => ['book_id_is_empty', 'book']];
        }

        $result = $this->deleteBook($data['unique_id']);

        if (isset($result['code'])) {
            return $result;
        }

        return [
            'status' => 1,
            'dataForLog' => [
                [
                    'table_to' => 'book_info',
                    'field_to' => 'id',
                    'id_to' => $data['unique_id'],
                ],
            ],
        ];
    }

    /**
     * [getBookInfo 获取图书信息]
     *
     * @author 朱从玺
     *
     * @param  [int]        $bookId [图书ID]
     *
     * @since 2015-10-30 创建
     *
     * @return [array]              [查询结果]
     */
    public function getBookInfo($bookId)
    {
        if (!$bookId) {
            return ['code' => ['0x040001', 'book']];
        }
        $bookInfo = app($this->bookInfoRepository)->getBookDetail($bookId);
        if (!$bookInfo) {
            return ['code' => ['0x040001', 'book']];
        }
        $bookInfo->attachment_id = app($this->attachmentService)->getAttachmentIdsByEntityId(['entity_table' => 'book_info', 'entity_id' => $bookId]);
        if (empty($bookInfo->attachment_id)) {
            $bookInfo->hasCover = false;
        } else {
            $bookInfo->hasCover = true;
        }
        return $bookInfo;
    }

    /**
     * [modifyBookInfo 编辑图书]
     *
     * @author 朱从玺
     *
     * @param  [int]           $bookId      [图书ID]
     * @param  [array]         $newBookData [编辑数据]
     *
     * @since 2015-10-30 创建
     *
     * @return [bool]                       [编辑结果]
     */
    public function modifyBookInfo($bookId, $newBookData)
    {
        $where = array('id' => array($bookId));

        $bookInfo = $this->getBookInfo($bookId);

        //判断图书是否存在
        if (!$bookInfo) {
            return array('code' => array('0x040001', 'book'));
        }

        //判断修改的图书数量是否合理
        $newBookData['book_total'] = isset($newBookData['book_total']) ? $newBookData['book_total'] : $bookInfo->book_total;
        $differentNumber = $bookInfo->book_total - $newBookData['book_total'];
        $newBookData['book_remainder'] = $bookInfo['book_remainder'] - $differentNumber;
        $newBookData['updated_at'] = date('Y-m-d H:i:s', time());
        if ($newBookData['book_remainder'] < 0) {
            return array('code' => array('0x040010', 'book'));
        }
        if (isset($newBookData['attachment_id']) && $newBookData['attachment_id'] != "") {
            app($this->attachmentService)->attachmentRelation("book", $bookId, $newBookData['attachment_id']);
            unset($newBookData['attachment_id']);
        }
        $result = app($this->bookInfoRepository)->updateData($newBookData, $where);

        if (!$result) {
            return array('code' => array('0x000003', 'common'));
        }
        return true;
    }

    public function editBookInfo($bookId, $newBookData)
    {
        //判断总量必须大于借出量
        $list = $this->getBookInfo($bookId);
        if (isset($list['code'])) {
            return $list;
        }
        if (isset($newBookData['data']['book_total'])) {
            if (!is_int_or_string_int($newBookData['data']['book_total'])) {
                return ['code' => ['0x040013', 'book']];
            }
            if (($list->book_total - $list->book_remainder) > $newBookData['data']['book_total']) {
                return ['code' => ['0x040012', 'book']];
            }
        }
        return app($this->formModelingService)->editCustomData($newBookData['data'], $newBookData['tableKey'], $bookId);
    }

    /**
     * [getBookList 获取图书列表]
     *
     * @author 朱从玺
     *
     * @param  [array]      $param [查询条件]
     *
     * @since 2015-10-30 创建
     *
     * @return [array]             [查询结果]
     */
    public function getBookList($param)
    {
        $param = $this->parseParams($param);
        $param['fields'] = ['id', "type_id", "dept_id", "book_remainder", "book_name", "author", "press", "simple_introduction", "deposit_location", "publish_date"];
        $departmentFields = ['dept_name'];

        $departmentSearch = [];

        if (isset($param['search']) && $param['search']) {
            foreach ($param['search'] as $key => $value) {
                if (in_array($key, $departmentFields)) {
                    $departmentSearch[$key] = $value;
                    unset($param['search'][$key]);
                }
            }
        }

        if ($departmentSearch) {
            $param['department_search'] = $departmentSearch;
        }

        $count = app($this->bookInfoRepository)->getTotal($param);
        $list = app($this->bookInfoRepository)->getBookByWhere($param);
        foreach ($list as $key => $value) {
            $list[$key]['attachment_id'] = app($this->attachmentService)->getAttachmentIdsByEntityId(['entity_table' => 'book_info', 'entity_id' => $value['id']]);
            if ($list[$key]['attachment_id']) {
                $list[$key]['attachment_id'] = $list[$key]['attachment_id'][0];
                // $thumbAttach = app($this->attachmentService)->getThumbAttach($list[$key]['attachment_id']);
                // $list[$key]['thumbAttach'] = $thumbAttach;
                $list[$key]['hasCover'] = true;

            } else {
                $list[$key]['attachment_id'] = "";
                $list[$key]['hasCover'] = false;
            }
        }

        return ['total' => $count, 'list' => $list];
    }
    /**
     * [getBookList 图书选择器获取图书列表]
     *
     * @author 朱从玺
     *
     * @param  [array]      $param [查询条件]
     *
     * @since 2015-10-30 创建
     *
     * @return [array]             [查询结果]
     */
    public function getBookListForSelect($param)
    {
        $param = $this->parseParams($param);
        $param['fields'] = ['*'];

        /** @var BookInfoRepository $bookInfoRepository */
        $bookInfoRepository = app($this->bookInfoRepository);
        $list = $bookInfoRepository->getBookName($param);
        $total = $bookInfoRepository->getBookNameTotal($param);

        return compact('list', 'total');
    }

    /**
     * [getBookMiddleList 获取图书中间列]
     *
     * @author 朱从玺
     *
     * @since  2015-10-30 创建
     *
     * @return [object]            [查询结果]
     */
    public function getBookMiddleList()
    {
        return app($this->bookTypeRepository)->getBookMiddleList();
    }

    /**
     * [deleteBook 删除图书]
     *
     * @author 朱从玺
     *
     * @param  [int]      $bookId [图书ID]
     *
     * @since 2015-10-30 创建
     *
     * @return [bool]             [删除结果]
     */
    public function deleteBook($bookId)
    {
        $search = array('book_id' => array($bookId), 'return_status' => array(0));
        $param = array('search' => $search);
        $count = app($this->bookManageRepository)->getTotal($param);

        if ($count > 0) {
            return array('code' => array('0x040005', 'book'));
        }
        $result = false;
        if (app($this->bookInfoRepository)->deleteById($bookId)) {
            $where = array('book_id' => $bookId);
            app($this->bookManageRepository)->deleteByWhere($where);
            $result = true;
        }

        if (!$result) {
            return ['code' => ['0x016035', 'fields']];
        }

        return $result;
    }

    /**
     * 批量删除图书
     * @param $data
     * @return array
     * @creatTime 2021/2/3 17:19
     * @author [dosy]
     */
    public function batchDeleteBook($data)
    {
        if (!empty($data)) {
            $ids = array_column($data, 'id');
        } else {
            return ['code' => ['params_error', 'book']];
        }
        if (empty($ids)){
            return ['code' => ['params_error', 'book']];
        }
        $search = ['book_id' => [$ids, 'in'], 'return_status' => [0]];
        $result = app($this->bookManageRepository)->getFieldInfo([], null, $search);
        if (!empty($result)){
            return ['code'=> ['0x040015','book']];
        }
        app($this->bookManageRepository)->deleteByWhere(['book_id' => [$ids, 'in']]);
        app($this->bookInfoRepository)->deleteByWhere(['id' => [$ids, 'in']]);
    }

    /**
     * [createBookType 创建图书类型]
     *
     * @author 朱从玺
     *
     * @param  [array]         $bookTypeData [创建数据]
     *
     * @since 2015-10-30 创建
     *
     * @return [bool]                        [创建结果]
     */
    public function createBookType($bookTypeData)
    {
        $result = app($this->bookTypeRepository)->insertData($bookTypeData);

        if (!$result) {
            return array('code' => array('0x000003', 'common'));
        }

        return $result;
    }

    /**
     * [modifyBookType 编辑图书类型]
     *
     * @author 朱从玺
     *
     * @param  [int]          $bookTypeId      [图书类型ID]
     * @param  [int]          $newBookTypeData [编辑数据]
     *
     * @since 2015-10-30 创建
     *
     * @return [bool]                          [编辑结果]
     */
    public function modifyBookType($bookTypeId, $newBookTypeData)
    {
        $where = array('id' => array($bookTypeId));

        $result = app($this->bookTypeRepository)->updateData($newBookTypeData, $where);

        // if(!$result) {
        //     return array('code' => array('0x000003', 'common'));
        // }

        return true;
    }

    /**
     * [getBookTypeInfo 获取图书类型]
     *
     * @author 朱从玺
     *
     * @param  [int]            $bookTypeId [图书类型ID]
     *
     * @since 2015-10-30 创建
     *
     * @return [array]                      [查询结果]
     */
    public function getBookTypeInfo($bookTypeId)
    {
        return app($this->bookTypeRepository)->getDetail($bookTypeId);
    }

    /**
     * [getBookTypeList 获取图书类型列表]
     *
     * @author 朱从玺
     *
     * @since 2015-10-30 创建
     *
     * @return [array]      [查询结果]
     */
    public function getBookTypeList($param)
    {
        $param = $this->parseParams($param);
        $typeList = app($this->bookTypeRepository)->getBookListByWhere($param);

        if ($typeList) {
            $typeList = $typeList->toArray();
            foreach ($typeList as $key => $value) {
                if (!empty($value['type_has_many_book'])) {
                    $typeList[$key]['has_children'] = 1;
                } else {
                    $typeList[$key]['has_children'] = 0;
                }
            }
        }
        return $typeList;
    }

    /**
     * [deleteBookType 删除图书类型]
     *
     * @author 朱从玺
     *
     * @param  [int]           $bookTypeId [图书类型ID]
     *
     * @since 2015-10-30 创建
     *
     * @return [bool]                      [删除结果]
     */
    public function deleteBookType($bookTypeId)
    {
        $where = array(
            'type_id' => array($bookTypeId),
        );
        $param = array('search' => $where);
        $bookCount = app($this->bookInfoRepository)->getTotal($param);

        if ($bookCount) {
            return array('code' => array('0x040007', 'book'));
        }

        $result = app($this->bookTypeRepository)->deleteById($bookTypeId);

        if (!$result) {
            return array('code' => array('0x000003', 'common'));
        }

        return true;
    }

    /**
     * [createBookManage 创建图书借阅记录]
     *
     * @author 朱从玺
     *
     * @param  [array]           $manageData [借阅数据]
     *
     * @since 2015-10-30 创建
     *
     * @return [bool]                        [创建结果]
     */
    public function createBookManage($manageData)
    {
        $verifyResult = $this->verifyBookManageData($manageData);

        if (is_array($verifyResult)) {
            return $verifyResult;
        }

        //如果前端没有传入借阅日期,则默认日期为创建数据当天
        if (!isset($manageData['borrow_date']) || $manageData['borrow_date'] == '') {
            $manageData['borrow_date'] = date('Y-m-d');
        }

        //插入图书借阅信息
        $result = app($this->bookManageRepository)->insertData($manageData);

        if (!$result) {
            return array('code' => array('0x000003', 'common'));
        }

        $newBookData['book_remainder'] = $verifyResult;

        $where = array('id' => array($manageData['book_id']));

        $modifyBookResult = app($this->bookInfoRepository)->updateDataBatch($newBookData, $where);

        if (!$modifyBookResult) {
            return array('code' => array('0x000004', 'common'));
        }

        return true;
    }

    public function addBookManage($manageData)
    {
        $verifyResult = $this->verifyBookManageData($manageData['data']);
        if (is_array($verifyResult)) {
            return $verifyResult;
        }
        // 默认未归还
        if (!isset($manageData['data']['return_status']) || !$manageData['data']['return_status']) {
            $manageData['data']['return_status'] = 0;
        }
        $result = false;
        //判断可借数量
        if (isset($manageData['data']['book_id'])) {
            $tempBookId = $manageData['data']['book_id'];
            $result = app($this->formModelingService)->addCustomData($manageData['data'], $manageData['tableKey']);
            if ($result && !isset($result['code']) && $manageData['data']['return_status'] == 0) {
                $remainder = $verifyResult;
                //图书剩余数量更新
                app($this->bookInfoRepository)->entity->where('id', $tempBookId)->update(['book_remainder' => $remainder]);
            }
        }
        return $result;
    }

    /**
     * 流程外发新建图书管理
     * @param $data
     */
    public function flowOutAddBookManage($data)
    {
        $result = $this->addBookManage($data);

        if (isset($result['code'])) {
            return $result;
        }
        if (!$result) {
            return ['code' => ['0x000003', 'common']];
        }

        return [
            'status' => 1,
            'dataForLog' => [
                [
                    'table_to' => 'book_manage',
                    'field_to' => 'id',
                    'id_to' => $result,
                ],
            ],
        ];
    }

    /**
     * 外发修改图书借阅
     * @param $data
     * @return array
     */
    public function flowOutUpdateBookManage($data)
    {
        if (empty($data['unique_id'])) {
            return ['code' => ['0x040009', 'book']];
        }
        if (empty($data['data'])) {
            return ['code' => ['0x000003', 'common']];
        }
        if(isset($data['data']['borrow_number'])   &&  !is_numeric($data['data']['borrow_number'])){
            return ['code' => ['0x040003', 'book']];
        }
        if(isset($data['data']['borrow_number'])   && $data['data']['borrow_number']<0){
            return ['code' => ['0x040003', 'book']];
        }

        $result = $this->editBookManage($data['unique_id'], $data);

        if (isset($result['code'])) {
            return $result;
        }
        if (!$result) {
            return ['code' => ['0x000003', 'common']];
        }

        return [
            'status' => 1,
            'dataForLog' => [
                [
                    'table_to' => 'book_manage',
                    'field_to' => 'id',
                    'id_to' => $data['unique_id'],
                ],
            ],
        ];
    }

    /**
     * 外发删除图书借阅
     * @param $data
     * @return array
     */
    public function flowOutDeleteBookManage($data)
    {
        if (empty($data['unique_id'])) {
            return ['code' => ['0x040009', 'book']];
        }

        $result = $this->deleteBookManage($data['unique_id']);

        if (isset($result['code'])) {
            return $result;
        }
        if (!$result) {
            return ['code' => ['0x000003', 'common']];
        }

        return [
            'status' => 1,
            'dataForLog' => [
                [
                    'table_to' => 'book_manage',
                    'field_to' => 'id',
                    'id_to' => $data['unique_id'],
                ],
            ],
        ];
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
        //判断是否有该借阅记录
        $manage = app($this->bookManageRepository)->getDetail($bookManageId);

        if (!$manage) {
            return array('code' => array('0x040009', 'book'));
        }

        //判断借阅记录是否已归还
        if ($manage->return_status == 1) {
            return array('code' => array('0x040006', 'book'));
        }

        //判断图书是否存在
        $book = app($this->bookInfoRepository)->getDetail($manage['book_id']);

        if (!$book) {
            return array('code' => array('0x040001', 'book'));
        }

        //更新借阅记录表
        $manage->return_status = 1;
        $manage->return_date = date('Y-m-d');

        $result = $manage->save();

        if (!$result) {
            return array('code' => array('0x000003', 'common'));
        }

        //更新图书信息表
        $book->book_remainder = $book->book_remainder + $manage->borrow_number;

        $modifyBookResult = $book->save();

        if (!$modifyBookResult) {
            return array('code' => array('0x000004', 'common'));
        }

        return true;
    }

    /**
     * [modifyBookManage 编辑借阅数据]
     *
     * @author 朱从玺
     *
     * @param  [int]             $manageId      [借阅ID]
     * @param  [array]           $newManageData [编辑数据]
     *
     * @since 2015-10-30 创建
     *
     * @return [json]                           [编辑结果]
     */
    public function modifyBookManage($manageId, $newManageData)
    {
        //判断是否有该借阅记录
        $manage = app($this->bookManageRepository)->getDetail($manageId);

        if (!$manage) {
            return array('code' => array('0x040009', 'book'));
        }

        //判断借阅记录是否已归还
        if ($manage->return_status == 1) {
            return array('code' => array('0x040006', 'book'));
        }

        //判断图书借阅权限
        $book = app($this->bookInfoRepository)->getDetail($newManageData['book_id']);

        if ($book['borrow_range'] == 0) {
            $userInfo = app($this->userService)->getUserAllData($newManageData['borrow_person']);

            if ($book['dept_id'] != $userInfo['user_has_one_system_info']['dept_id']) {
                return array('code' => array('0x040011', 'book'));
            }
        }

        //判断编辑后与编辑前是否借阅的同一本书
        if ($manage['book_id'] == $newManageData['book_id']) {
            //计算图书的剩余数量
            $differentBorrow = $newManageData['borrow_number'] - $manage['borrow_number'];

            $bookRemainder = $book->book_remainder - $differentBorrow;
        } else {
            //更新以前借阅的图书信息
            $oldBook = app($this->bookInfoRepository)->getDetail($manage['book_id']);
            $oldBook->book_remainder = $oldBook->book_remainder + $manage['borrow_number'];
            $result = $oldBook->save();

            if (!$result) {
                return array('code' => array('0x000003', 'common'));
            }

            //计算当前借阅图书的剩余数量
            $bookRemainder = $book->book_remainder - $newManageData['borrow_number'];
        }

        //判断图书剩余数量是否是正数
        if ($bookRemainder < 0) {
            return array('code' => array('0x040002', 'book'));
        }

        //更新借阅记录表
        $where = array('id' => array($manageId));

        $result = app($this->bookManageRepository)->updateData($newManageData, $where);

        if (!$result) {
            return array('code' => array('0x000003', 'common'));
        }

        //更新图书信息表
        $book->book_remainder = $bookRemainder;
        $modifyBookResult = $book->save();

        // if(!$modifyBookResult) {
        //     return array('code' => array('0x000004', 'common'));
        // }

        return true;
    }

    public function editBookManage($bookManageId, $data)
    {
        $newManageData = $data['data'];

        //判断是否有该借阅记录
        $manage = app($this->bookManageRepository)->getDetail($bookManageId);
        if (!$manage) {
            return array('code' => array('0x040009', 'book'));
        }

        //判断借阅记录是否已归还
        if ($manage->return_status == 1) {
            return array('code' => array('0x040006', 'book'));
        }

        //判断图书借阅权限
        $book = app($this->bookInfoRepository)->getDetail($newManageData['book_id']);
        if (!empty($book)) {
            if ($book['borrow_range'] == 0) {
                $userInfo = app($this->userService)->getUserAllData($newManageData['borrow_person'])->toArray();
                if ($book['dept_id'] != $userInfo['user_has_one_system_info']['dept_id']) {
                    return array('code' => array('0x040011', 'book'));
                }
            }
            if (array_key_exists('borrow_number', $newManageData) && !empty($newManageData['borrow_number'])) {
                //判断编辑后与编辑前是否借阅的同一本书
                if ($manage['book_id'] == $newManageData['book_id']) {
                    //计算图书的剩余数量
                    $differentBorrow = $newManageData['borrow_number'] - $manage['borrow_number'];

                    $bookRemainder = $book->book_remainder - $differentBorrow;
                    //判断图书剩余数量是否是正数
                    if ($bookRemainder < 0) {
                        return array('code' => array('0x040002', 'book'));
                    }
                } else {
                    //计算当前借阅图书的剩余数量
                    $bookRemainder = $book->book_remainder - $newManageData['borrow_number'];
                    //判断图书剩余数量是否是正数
                    if ($bookRemainder < 0) {
                        return array('code' => array('0x040002', 'book'));
                    }
                    //更新以前借阅的图书信息
                    $oldBook = app($this->bookInfoRepository)->getDetail($manage['book_id']);
                    $oldBook->book_remainder = $oldBook->book_remainder + $manage['borrow_number'];
                    $result = $oldBook->save();
                    if (!$result) {
                        return array('code' => array('0x000003', 'common'));
                    }
                }
            } else {
                return array('code' => array('the_number_of_books_is_empty', 'book'));
            }
        }
        $result = app($this->formModelingService)->editCustomData($newManageData, $data['tableKey'], $bookManageId);
        if ($result && !isset($result['code'])) {
            //图书剩余数量更新
            DB::update('update book_info set book_remainder =  ? where id = ?', [$bookRemainder, $newManageData['book_id']]);
        }

        return $result;

    }

    /**
     * [verifyBookManageData 验证图书借阅数据]
     *
     * @author 朱从玺
     *
     * @param  [array]               $manageData [借阅数据]
     *
     * @since 2015-10-30 创建
     *
     * @return [int]                             [图书剩余量]
     */
    public function verifyBookManageData($manageData)
    {
        if (!isset($manageData['book_id']) || empty($manageData['book_id'])) {
            return ['code' => ['0x040012', 'book']];
        }
        $bookData = $this->getBookInfo($manageData['book_id']);
        if (isset($bookData['code'])) {
            return $bookData;
        }
        if (empty($bookData)) {
            return ['code' => ['0x040001', 'book']];
        }
        //判断图书借阅权限
        if ($bookData['borrow_range'] == 0) {
            $userInfo = app($this->userService)->getUserAllData($manageData['borrow_person'])->toArray();
            if ($bookData['dept_id'] != $userInfo['user_has_one_system_info']['dept_id']) {
                return ['code' => ['0x040011', 'book']];
            }
        }

        if (!isset($manageData['borrow_number']) || !is_int_or_string_int($manageData['borrow_number']) || $manageData['borrow_number'] <= 0) {
            return ['code' => ['0x040013', 'book']];
        }

        //判断借阅数量是否多于图书剩余数量
        $remainderBook = $bookData['book_remainder'] - $manageData['borrow_number'];

        if ($remainderBook < 0) {
            return ['code' => ['0x040002', 'book']];
        }

        return $remainderBook;
    }

    /**
     * [getBookManageInfo 获取图书借阅数据]
     *
     * @author 朱从玺
     *
     * @param  [int]            $bookManageId [借阅数据ID]
     *
     * @since 2015-10-30 创建
     *
     * @return [array]                        [查询结果]
     */
    public function getBookManageInfo($bookManageId)
    {
        return app($this->bookManageRepository)->getBookManageDetail($bookManageId);
    }

    /**
     * [getBookManageList 获取图书借阅列表]
     *
     * @param $param
     * @param $own
     * @return array [array]                   [查询结果]
     * @author 朱从玺
     * @since 2015-10-30 创建
     */
    public function getBookManageList($param, $own)
    {
        $param = $this->parseParams($param);

        if (isset($param['search']['book_name'])) {
            $nameLike = '%' . $param['search']['book_name'][0] . '%';
            $bookIds = DB::table('book_info')
                ->where('book_name', 'like', $nameLike)
                ->pluck('id')
                ->all();
            if (empty($bookIds)) {
                return [];
            }
            unset($param['search']['book_name']);
            $param['search']['book_id'] = [$bookIds, 'in'];
        }

        return app($this->formModelingService)->getCustomDataLists($param, 'book_manage', $own);
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
     * @return [bool]                        [删除结果]
     */
    public function deleteBookManage($bookManageId)
    {
        $bookManage = app($this->bookManageRepository)->getDetail($bookManageId);
        $bookInfo = app($this->bookInfoRepository)->getDetail($bookManage['book_id']);

        if (!$bookManage) {
            return ['code' => ['0x040009', 'book']];
        }

        //如果图书没有归还,不能删除
        if ($bookManage->return_status != 1) {
            return array('code' => array('0x040008', 'book'));
        }

        $result = app($this->bookManageRepository)->deleteById($bookManageId);

        if (!$result) {
            return array('code' => array('0x000003', 'common'));
        }

        return $result;
    }
    /**
     * [getBookName 获取图书名称]
     *
     * @author 朱从玺
     *
     * @since  2016-6-16 创建
     *
     * @return [object]            [查询结果]
     */
    public function getBookName($param)
    {
        $param = $this->parseParams($param);
        return app($this->bookInfoRepository)->getBookName($param);
    }
    /**
     * [getBookType 获取图书类型]
     *
     *
     * @return [array]                      [查询结果]
     */
    public function getBookType()
    {
        return app($this->bookTypeRepository)->getBookType();
    }
    /**
     * 获取导入图书字段
     *
     * @return array 查询结果
     *
     * @author qishaobo
     *
     * @since  2016-06-16
     */
    public function getImportBookFields($param)
    {
        return app($this->formModelingService)->getImportFields('book_info', $param, trans('book.Book_import_template'));
    }

    /**
     * 导入图书
     *
     * @param  array $data  导入数据
     * @param  array $param 导入条件
     *
     * @return array 导入结果
     *
     * @author qishaobo
     *
     * @since  2016-06-15
     */
    public function importBook($data, $param)
    {
        app($this->formModelingService)->importCustomData('book_info', $data, $param);
        return ['data' => $data];
    }

    /**
     * 导入图书过滤
     *
     * @param  array $data  导入数据
     * @param  array $param 导入条件s
     *
     * @return array 导入结果
     *
     * @author qishaobo
     *
     * @since  2016-12-09
     */
    public function importBookFilter($data, $param = [])
    {
        if ($param['type'] == 2) {
            $primaryKey = $param['primaryKey'];
            $bookBorrowed = DB::table('book_info')
                ->select($primaryKey, DB::raw('(book_total - book_remainder) as book_borrowed'))
                ->pluck('book_borrowed', $primaryKey)
                ->toArray();
        }

        $bookTypeIds = DB::table('book_type')->pluck('id')->all();

        foreach ($data as $k => $v) {
            if (!is_numeric($v['dept_id'])) {
                $data[$k]['importResult'] = importDataFail();
                $data[$k]['importReason'] = importDataFail(trans("book.errors_in_the_format_of_the_department_of_the_book"));
                continue;
            }
            if (!is_numeric($v['type_id']) || !in_array($v['type_id'], $bookTypeIds)) {
                $data[$k]['importResult'] = importDataFail();
                $data[$k]['importReason'] = importDataFail(trans("book.book_category_error"));
                continue;
            }
            if (!in_array($v['borrow_range'], [0, 1])) {
                $data[$k]['importResult'] = importDataFail();
                $data[$k]['importReason'] = importDataFail(trans("book.0x040014"));
                continue;
            }
            if (empty($v['book_total'])) {
                $data[$k]['importResult'] = importDataFail();
                $data[$k]['importReason'] = importDataFail(trans("book.the_number_of_books_is_empty"));
                continue;
            }
            if (!is_int_or_string_int($v['book_total'])) {
                $data[$k]['importResult'] = importDataFail();
                $data[$k]['importReason'] = importDataFail(trans("book.book_number_format_error"));
                continue;
            }
            if ($param['type'] == 1) {
                $data[$k]['book_remainder'] = $v['book_total'];
            }
            if ($param['type'] == 2) {
                $primaryData = $v[$primaryKey];
                if (isset($bookBorrowed[$primaryData])) {
                    $book_remainder = $v['book_total'] - $bookBorrowed[$primaryData];
                    if ($book_remainder < 0) {
                        $data[$k]['importResult'] = importDataFail();
                        $data[$k]['importReason'] = importDataFail(trans("book.book_total_should_not_less_than_borrowed"));
                        continue;
                    }
                    $data[$k]['book_remainder'] = $book_remainder;
                } else {
                    $data[$k]['importResult'] = importDataFail();
                    $data[$k]['importReason'] = importDataFail(trans("book.0x040001"));
                    continue;
                }
            }
            $result = app($this->formModelingService)->importDataFilter('book_info', $v, $param);
            if (!empty($result)) {
                $data[$k]['importResult'] = importDataFail();
                $data[$k]['importReason'] = importDataFail($result);
                continue;
            }
            $data[$k]['importResult'] = importDataSuccess();
        }

        return $data;
    }

    //添加自定义字段
    public static function add_custom_field($param = "")
    {
        $infoResult = BookInfoRepository::add_custom_field($param);
        $manageResult = BookManageRepository::add_custom_field($param);
        return ($infoResult && $manageResult) ? true : false;
    }

    //借阅范围
    public function getBookBorrowRange()
    {
        $result = [
            ['title' => trans("book.whole"), 'value' => 1],
            ['title' => trans("book.this_department"), 'value' => 0],
        ];
        return json_decode(json_encode($result));
    }

    //归还状态
    public function getBookReturnStatus()
    {
        $result = [
            ['title' => trans("book.restitution"), 'value' => 1],
            ['title' => trans("book.not_returned"), 'value' => 0],
        ];
        return json_decode(json_encode($result));
    }

    public function getBookRemainTotal($data)
    {
        $list = app($this->bookInfoRepository)->getDetail($data['book_id']);
        return $list->book_remainder;
    }

    public function setBookRemainTotal($bookId, $data)
    {
        $result = DB::update('update book_info set book_remainder = book_remainder - ? where id = ? ', [$data['num'], $bookId]);
        return $result;
    }

    public function getBookInfoDetail($bookId)
    {
        $result = app($this->formModelingService)->getCustomDataDetail('book_info', $bookId);
        $result = (array) $result;
        $result['type_name'] = '';
        if (!empty($result['type_id'])) {
            $type = DB::table('book_type')->select('type_name')->where('id', $result['type_id'])->first();
            if ($type) {
                $result['type_name'] = $type->type_name;
            }
        }
        return $result;
    }

    //获取我借阅的图书类别
    public function getMyBorrowTypes($own)
    {
        $result = app($this->bookTypeRepository)->getMyBorrowTypes($own['user_id']);
        return $result;
    }

    public function getMyBorrowList($param, $own)
    {
        $param = $this->parseParams($param);
        $param['search']['borrow_person'] = [$own['user_id']];
//        $param['except_fields'] = ['borrow_person'];
        //        $typeId = $request['type_id'];
        //        $booksOfType =
        $result = app($this->formModelingService)->getCustomDataLists($param, 'book_manage', $own);
//        $result = app($this->fieldsRepository)->getCustomDataList('book_manage', $param);
        return $result;
    }

//    获取图书id列表
    public function getBookIDsByWhere($param)
    {
        $param = $this->parseParams($param);
        $result = app($this->bookInfoRepository)->getBookIDsByWhere($param);
        return $result;
    }

//    我的借阅字段过滤,去除借阅人
    public function handleMyBorrowFields($fields)
    {
        $fields = $fields->toArray();
        foreach ($fields as $key => $value) {
            if ($value->field_code == 'borrow_person') {
                array_splice($fields, $key, 1);
            }
        }
        return $fields;
    }

//    图书归还提醒列表
    public function bookReturnExpireRemind()
    {
        $list = app($this->bookManageRepository)->bookReturnExpireList();
        $messages = [];
        if (!empty($list)) {
            foreach ($list as $value) {
                $messages[] = [
                    'remindMark' => 'book-expire',
                    'toUser' => $value['borrow_person'],
                    'contentParam' => ['bookName' => $value['manage_belongs_to_book']['book_name'], 'bookExpireDate' => $value['expire_date']],
//                    路由参数
                    'stateParams' => ['manageId' => $value['id']],
                ];
            }
        }
        return $messages;
    }
    public function getBookkManageName($id)
    {
        $idArray = explode(',', $id);
        return  app($this->bookManageRepository)->getBookkManageName($idArray);
    }

    /**
     * 图书导出
     * @param $param
     * @return mixed
     * @creatTime 2021/1/5 16:24
     * @author [dosy]
     */
    public function exportBook($param)
    {
        $own = $param['user_info'];
        return app($this->formModelingService)->exportFields('book_info', $param, $own);
    }
}
