<?php

namespace App\EofficeApp\Book\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Book\Entities\BookManageEntity;
use Schema;
use DB;

/**
 * book_manage表资源库
 *
 * @author  朱从玺
 *
 * @since  2015-10-30 创建
 */
class BookManageRepository extends BaseRepository
{
    /**
     * [$bookManageEntity book_manage表实体]
     *
     * @var [object]
     */
    protected $bookManageEntity;

    public function __construct(BookManageEntity $entity)
    {
        parent::__construct($entity);
    }

    /**
     * [getBookManageDetail 获取图书借阅记录,关联查询图书名]
     *
     * @author 朱从玺
     *
     * @param  [int]                 $manageId [记录ID]
     *
     * @since 2015-11-25 创建
     *
     * @return [object]                        [查询结果]
     */
    public function getBookManageDetail($manageId)
    {
        return $this->entity->where('id', $manageId)
            ->with(['manageBelongsToBook' => function ($query) {
                $query->select('id', 'book_name');
            }])->with(['user' => function ($query) {
            $query->select('user_id', 'user_name');
        }])
            ->first();
    }

    /**
     * [getBookManageCount 获取借阅列表数量]
     *
     * @author 朱从玺
     *
     * @param  [array]             $param [查询条件]
     *
     * @since  2015-10-30 创建
     *
     * @return [int]                      [查询结果]
     */
    public function getBookManageCount($param)
    {
        $search = isset($param['search']) ? $param['search'] : [];

        $query = $this->entity->wheres($search);

        if (isset($param['book_search'])) {
            $query = $query->whereHas('manageBelongsToBook', function ($query) use ($param) {
                $query->wheres($param['book_search']);
            });
        }

        return $query->count();
    }

    /**
     * [getBookManageByWhere 通过查询条件查询借阅记录]
     *
     * @author 朱从玺
     *
     * @param  [array]                $param [查询条件]
     *
     * @since  2015-10-30 创建
     *
     * @return [object]                      [查询结果]
     */
    public function getBookManageByWhere($param)
    {
        $default = array(
            'fields'   => ['*'],
            'page'     => 0,
            'limit'    => config('eoffice.pagesize'),
            'order_by' => ['borrow_date' => 'desc'],
            'search'   => [],
        );

        $param = array_merge($default, $param);

        $query = $this->entity->wheres($param['search'])
            ->select($param['fields'])
            ->with(['manageBelongsToBook' => function ($query) {
                $query->select('id', 'book_name');
            }, 'user' => function ($query) {
                $query->select('user_id', 'user_name');
            }])
            ->parsePage($param['page'], $param['limit'])
            ->orders($param['order_by']);

        if (isset($param['book_search'])) {
            $query = $query->whereHas('manageBelongsToBook', function ($query) use ($param) {
                $query->wheres($param['book_search']);
            });
        }
        if (isset($param['user_search'])) {
            $query = $query->whereHas('user', function ($query) use ($param) {
                $query->wheres($param['user_search']);
            });
        }

        return $query->get();
    }

    public static function add_custom_field()
    {
        return self::add_system_custom_field();
    }

    private static function add_system_custom_field()
    {
        $table = "custom_fields_table";
        if (!Schema::hasTable($table)) {
            return false;
        }

        DB::table($table)->where('field_table_key', 'book_manage')->where('is_system', 1)->delete();
        $data   = array();
        $data[] = ['field_code' => 'borrow_person', 'field_name' => '借阅人', 'field_directive' => 'selector', 'field_table_key' => 'book_manage', 'is_system' => '1', 'field_options' => '{"type":"selector","selectorConfig":{"category":"common","type":"user"},"validate":{"required":1},"advancedSearch":1,"autoSearch":1,"fullRow":2,"fullCell":true}', 'field_data_type' => 'text', 'field_search' => '1', 'field_sort' => '1', 'field_list_show' => '1', 'field_allow_order' => '0', 'mobile_list_field' => 'secondary'];
        $data[] = ['field_code' => 'book_id', 'field_name' => '书名', 'field_directive' => 'selector', 'field_table_key' => 'book_manage', 'is_system' => '1', 'field_options' => '{"type":"selector","selectorConfig":{"category":"book","type":"bookName","multiple":0},"validate":{"required":1},"advancedSearch":1,"fullRow":2,"fullCell":true}', 'field_data_type' => 'text', 'field_search' => '1', 'field_sort' => '2', 'field_list_show' => '1', 'field_allow_order' => '0', 'mobile_list_field' => ''];
        $data[] = ['field_code' => 'borrow_number', 'field_name' => '借阅数量', 'field_directive' => 'number', 'field_table_key' => 'book_manage', 'is_system' => '1', 'field_options' => '{"type":"text","format":{"type":"number"},"fullRow":2,"fullCell":true}', 'field_data_type' => 'varchar', 'field_search' => '1', 'field_sort' => '3', 'field_list_show' => '1', 'field_allow_order' => '1', 'mobile_list_field' => 'remark'];
        $data[] = ['field_code' => 'borrow_date', 'field_name' => '借阅日期', 'field_directive' => 'date', 'field_table_key' => 'book_manage', 'is_system' => '1', 'field_options' => '{"type":"text","format":{"type":"date"},"fullRow":2,"fullCell":true}', 'field_data_type' => 'varchar', 'field_search' => '1', 'field_sort' => '4', 'field_list_show' => '1', 'field_allow_order' => '1', 'mobile_list_field' => 'time'];
        $data[] = ['field_code' => 'return_date', 'field_name' => '归还日期', 'field_directive' => 'date', 'field_table_key' => 'book_manage', 'is_system' => '1', 'field_options' => '{"type":"text","format":{"type":"date"},"fullRow":2,"fullCell":true}', 'field_data_type' => 'varchar', 'field_search' => '1', 'field_sort' => '4', 'field_list_show' => '1', 'field_allow_order' => '1', 'mobile_list_field' => ''];
        $data[] = ['field_code' => 'return_status', 'field_name' => '归还状态', 'field_directive' => 'select', 'field_table_key' => 'book_manage', 'is_system' => '1', 'field_options' => '{"type":"select","selectConfig":{"sourceType":"","sourceValue":""},"validate":{"required":1},"advancedSearch":1,"fullRow":2,"fullCell":true,"datasource":[{"id":0,"title":"未归还"},{"id":1,"title":"已归还"}],"default":0}', 'field_data_type' => 'varchar', 'field_search' => '1', 'field_sort' => '5', 'field_list_show' => '1', 'field_allow_order' => '0', 'mobile_list_field' => 'tag'];
        $data[] = ['field_code' => 'remark', 'field_name' => '借阅备注', 'field_directive' => 'textarea', 'field_table_key' => 'book_manage', 'is_system' => '1', 'field_options' => '{"type":"textarea","fullRow":2,"fullCell":true}', 'field_data_type' => 'text', 'field_search' => '0', 'field_sort' => '6', 'field_list_show' => '0', 'field_allow_order' => '0', 'mobile_list_field' => ''];
        return DB::table($table)->insert($data);
    }


//  图书归还提醒列表
    public function bookReturnExpireList()
    {
        return $query = $this->entity
            ->select('id', 'book_id', 'borrow_person', 'expire_date')
            ->with(['manageBelongsToBook'=>function($query){
                $query->select('id', 'book_name');
            }])
            ->where('expire_date', '=', date('Y-m-d'))
            ->where('return_status', 0)
            ->get()
            ->toArray();
    }
    public function getBookkManageName($id)
    {
        return $this->entity
            ->leftJoin('book_info','book_info.id','=','book_manage.book_id')
            ->whereIn('book_manage.id',$id)
            ->get()
            ->toArray();
            
    }
}
