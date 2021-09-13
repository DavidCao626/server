<?php

namespace App\EofficeApp\Book\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Book\Entities\BookTypeEntity;

/**
 * book_type表资源库
 *
 * @author  朱从玺
 *
 * @since  2015-10-30 创建
 */
class BookTypeRepository extends BaseRepository
{
	/**
	 * [$bookTypeEntity book_type表实体]
	 *
	 * @var [object]
	 */
	protected $bookTypeEntity;

	public function __construct(BookTypeEntity $entity)
	{
		parent::__construct($entity);
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
		return $this->entity
					->has('typeHasManyBook')
					->with(['typeHasManyBook' => function($query)
					{
						$query->selectRaw("type_id, id, book_name, book_remainder");
					}])
					->get();
	}

	/**
	 * [getBookListByWhere 获取所有图书类型]
	 *
	 * @author 朱从玺
	 *
	 * @since  2015-10-30 创建
	 *
	 * @return [object]             [查询结果]
	 */
	public function getBookListByWhere($param)
	{
		$default = array(
            'fields'   => ['*'],
            'page'     => 0,
            'search'   => [],
        );
        $param = array_merge($default, $param);
		return $this->entity->wheres($param['search'])
					->with(['typeHasManyBook' => function($query)
					{
						$query->selectRaw("type_id, count(*) as number")
							  ->groupBy('type_id');
					}])
					->get();
	}
	/**
	 * [getBookType 获取图书类型]
	 *
	 * @return [array]            [查询结果]
	 */
	public function getBookType()
	{
		$query = $this->entity->select('id','type_name');
		return $query->get()->toArray();
	}

	//获取我的借阅的类型
    public function getMyBorrowTypes($userId)
    {
        $query = $this->entity
            ->selectRaw('book_type.id as type_id, book_type.type_name as type_name, sum(book_manage.borrow_number) as number')
            ->join('book_info', 'book_type.id', '=', 'book_info.type_id')
            ->join('book_manage', 'book_info.id', '=', 'book_manage.book_id')
            ->where('book_manage.borrow_person', '=', $userId)
            ->where('book_manage.deleted_at', '=' , null)
//            ->where('book_manage.return_status', '=', 0)
            ->groupBy('book_type.id');
        return $query->get();
    }

}
