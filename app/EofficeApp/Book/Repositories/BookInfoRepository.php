<?php

namespace App\EofficeApp\Book\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Book\Entities\BookInfoEntity;
use DB;
use Schema;

/**
 * book_info表资源库
 *
 * @author  朱从玺
 *
 * @since  2015-10-30 创建
 */
class BookInfoRepository extends BaseRepository
{
    /**
     * [$bookInfoEntity book_info表实体]
     *
     * @var [object]
     */
    protected $bookInfoEntity;

    public function __construct(BookInfoEntity $entity)
    {
        parent::__construct($entity);
    }

    public function getBookDetail($bookId)
    {
        return $this->entity->where('id', $bookId)
            ->with(['department' => function ($query) {
                $query->select('dept_id', 'dept_name');
            }, 'bookHasOneType' => function ($query) {
                $query->select('id', 'type_name');
            }])
            ->first();
    }
    public function getBookName($param)
    {
        $default = array(
            'fields' => ['book_name', 'id'],
            'page'   => 0,
            'limit'    => config('eoffice.pagesize'),
            'search' => [],
        );
        $param = array_merge($default, $param);
        $query = $this->entity->wheres($param['search'])
            ->select($param['fields'])
            ->parsePage($param['page'], $param['limit']);
        return $query->get();
    }

    public function getBookNameTotal($param)
    {
        $default = [
            'fields' => ['book_name', 'id'],
            'page'   => 0,
            'limit'    => config('eoffice.pagesize'),
            'search' => [],
        ];
        $param = array_merge($default, $param);

        return $this->entity->wheres($param['search'])
            ->count();
    }

    /**
     * [getBookByWhere 获取图书列表]
     *
     * @author 朱从玺
     *
     * @param  [array]          $param [查询条件]
     *
     * @since  2015-10-30 创建
     *
     * @return [object]                [查询结果]
     */
    public function getBookByWhere($param)
    {
        $default = array(
            'fields'   => ['*'],
            'page'     => 0,
            'limit'    => config('eoffice.pagesize'),
            'order_by' => ['id' => 'desc'],
            'search'   => [],
        );

        $param = array_merge($default, $param);

        $query = $this->entity->wheres($param['search'])
            ->select($param['fields'])
            ->parsePage($param['page'], $param['limit'])
            ->orders($param['order_by']);

        if (isset($param['department_search'])) {
            $query = $query->whereHas('department', function ($query) use ($param) {
                $query->wheres($param['department_search']);
            });
        }

        if ($param['fields'] == ['*'] || in_array('type_id', $param['fields'])) {
            $query = $query->with(['bookHasOneType' => function ($query) {
                $query->select('type_name', 'id');
            }]);
        }

        if ($param['fields'] == ['*'] || in_array('dept_id', $param['fields'])) {
            $query = $query->with(['department' => function ($query) {
                $query->select('dept_id', 'dept_name');
            }]);
        }

        return $query->get()->toArray();
    }

    public function getBookIDsByWhere($param)
    {
        $default = [
            'search'   => [],
        ];
        $param = array_merge($default, $param);
        $query = $this->entity
            ->wheres($param['search'])
            ->pluck('id');
        return $query->toArray();
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

        DB::table($table)->where('field_table_key', 'book_info')->where('is_system', 1)->delete();
        $data   = array();
        $data[] = ['field_code' => 'dept_id', 'field_name' => '部门', 'field_directive' => 'text', 'field_table_key' => 'book_info', 'is_system' => '1', 'field_options' => '{"type":"selector","selectorConfig":{"category":"common","type":"dept"},"fullRow":2,"fullCell":true,"validate":{"required":1},"advancedSearch":1}', 'field_data_type' => 'selector', 'field_search' => '1', 'field_sort' => '1', 'field_list_show' => '1', 'field_allow_order' => '0', 'mobile_list_field' => 'tag'];
        $data[] = ['field_code' => 'book_name', 'field_name' => '图书名', 'field_directive' => 'text', 'field_table_key' => 'book_info', 'is_system' => '1', 'field_options' => '{"type":"text","format":{"type":"text"},"validate":{"required":1},"fieldSearch":1,"advancedSearch":1,"fullRow":2,"fullCell":true}', 'field_data_type' => 'varchar', 'field_search' => '1', 'field_sort' => '1', 'field_list_show' => '1', 'field_allow_order' => '0', 'mobile_list_field' => 'primary'];
        $data[] = ['field_code' => 'type_id', 'field_name' => '图书类别', 'field_directive' => 'select', 'field_table_key' => 'book_info', 'is_system' => '1', 'field_options' => '{"type":"select","selectConfig":{"sourceType":"systemData","sourceValue":{"module":"book","field":"type_id"}},"validate":{"required":1},"fullRow":2,"fullCell":true,"advancedSearch":1}', 'field_data_type' => 'varchar', 'field_search' => '1', 'field_sort' => '2', 'field_list_show' => '1', 'field_allow_order' => '0', 'mobile_list_field' => 'tag'];
        $data[] = ['field_code' => 'borrow_range', 'field_name' => '借阅范围', 'field_directive' => 'select', 'field_table_key' => 'book_info', 'is_system' => '1', 'field_options' => '{"type":"select","selectConfig":{"sourceType":"","sourceValue":""},"validate":{"required":1},"fullRow":2,"fullCell":true,"datasource":[{"id":0,"title":"全体"},{"id":1,"title":"本部门"}],"default":0,"advancedSearch":1}', 'field_data_type' => 'varchar', 'field_search' => '1', 'field_sort' => '3', 'field_list_show' => '0', 'field_allow_order' => '0', 'mobile_list_field' => ''];
        $data[] = ['field_code' => 'book_remainder', 'field_name' => '剩余数量', 'field_directive' => 'number', 'field_table_key' => 'book_info', 'is_system' => '1', 'field_options' => '{"type":"text","format":{"type":"number","decimalPlaces":false,"decimalPlacesDigit":"","rounding":false},"fullRow":2,"fullCell":true,"advancedSearch":1}', 'field_data_type' => 'varchar', 'field_search' => '0', 'field_sort' => '4', 'field_list_show' => '1', 'field_allow_order' => '1', 'mobile_list_field' => 'remark'];
        $data[] = ['field_code' => 'book_total', 'field_name' => '图书总量', 'field_directive' => 'number', 'field_table_key' => 'book_info', 'is_system' => '1', 'field_options' => '{"type":"text","format":{"type":"number"},"validate":{"required":1},"fullRow":2,"fullCell":true,"advancedSearch":1}', 'field_data_type' => 'varchar', 'field_search' => '0', 'field_sort' => '5', 'field_list_show' => '1', 'field_allow_order' => '1', 'mobile_list_field' => ''];
        $data[] = ['field_code' => 'author', 'field_name' => '作者', 'field_directive' => 'text', 'field_table_key' => 'book_info', 'is_system' => '1', 'field_options' => '{"type":"text","format":{"type":"text"},"validate":{"required":1},"advancedSearch":1,"fullRow":2,"fullCell":true}', 'field_data_type' => 'varchar', 'field_search' => '1', 'field_sort' => '6', 'field_list_show' => '1', 'field_allow_order' => '0', 'mobile_list_field' => 'secondary'];
        $data[] = ['field_code' => 'press', 'field_name' => '出版社', 'field_directive' => 'text', 'field_table_key' => 'book_info', 'is_system' => '1', 'field_options' => '{"type":"text","format":{"type":"text"},"advancedSearch":1,"fullRow":2,"fullCell":true}', 'field_data_type' => 'varchar', 'field_search' => '1', 'field_sort' => '7', 'field_list_show' => '1', 'field_allow_order' => '0', 'mobile_list_field' => ''];
        $data[] = ['field_code' => 'isbn', 'field_name' => 'ISBN号', 'field_directive' => 'text', 'field_table_key' => 'book_info', 'is_system' => '1', 'field_options' => '{"type":"text","format":{"type":"text"},"fullRow":2,"fullCell":true,"advancedSearch":1}', 'field_data_type' => 'varchar', 'field_search' => '1', 'field_sort' => '8', 'field_list_show' => '0', 'field_allow_order' => '0', 'mobile_list_field' => ''];
        $data[] = ['field_code' => 'publish_date', 'field_name' => '出版日期', 'field_directive' => 'date', 'field_table_key' => 'book_info', 'is_system' => '1', 'field_options' => '{"type":"text","format":{"type":"date"},"fullRow":2,"fullCell":true,"advancedSearch":1}', 'field_data_type' => 'varchar', 'field_search' => '0', 'field_sort' => '9', 'field_list_show' => '0', 'field_allow_order' => '0', 'mobile_list_field' => 'time'];
        $data[] = ['field_code' => 'deposit_location', 'field_name' => '存放地点', 'field_directive' => 'text', 'field_table_key' => 'book_info', 'is_system' => '1', 'field_options' => '{"type":"text","format":{"type":"text"},"advancedSearch":1,"fullRow":2,"fullCell":true}', 'field_data_type' => 'varchar', 'field_search' => '1', 'field_sort' => '10', 'field_list_show' => '1', 'field_allow_order' => '0', 'mobile_list_field' => ''];
        $data[] = ['field_code' => 'price', 'field_name' => '价格', 'field_directive' => 'number', 'field_table_key' => 'book_info', 'is_system' => '1', 'field_options' => '{"type":"text","format":{"type":"number","decimalPlaces":true,"decimalPlacesDigit":2,"rounding":false},"fullRow":2,"fullCell":true,"advancedSearch":1}', 'field_data_type' => 'varchar', 'field_search' => '0', 'field_sort' => '11', 'field_list_show' => '0', 'field_allow_order' => '1', 'mobile_list_field' => ''];
        $data[] = ['field_code' => 'remark', 'field_name' => '备注', 'field_directive' => 'textarea', 'field_table_key' => 'book_info', 'is_system' => '1', 'field_options' => '{"type":"textarea","fullRow":2,"fullCell":true,"advancedSearch":1}', 'field_data_type' => 'text', 'field_search' => '0', 'field_sort' => '12', 'field_list_show' => '0', 'field_allow_order' => '1', 'mobile_list_field' => ''];
        $data[] = ['field_code' => 'simple_introduction', 'field_name' => '封面图片', 'field_directive' => 'textarea', 'field_table_key' => 'book_info', 'is_system' => '1', 'field_options' => '{"type":"upload","fullRow":2,"uploadConfig":{"multiple":0,"onlyImage":1,"fileCount":5,"buttonText":"上传封面"},"fullCell":true}', 'field_data_type' => 'upload', 'field_search' => '0', 'field_sort' => '13', 'field_list_show' => '0', 'field_allow_order' => '1', 'mobile_list_field' => ''];
        return DB::table($table)->insert($data);
    }
}
