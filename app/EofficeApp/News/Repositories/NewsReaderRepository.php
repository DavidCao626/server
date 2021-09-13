<?php 

namespace App\EofficeApp\News\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\News\Entities\NewsReaderEntity;
use App\EofficeApp\User\Entities\UserSystemInfoEntity;
use App\EofficeApp\System\Department\Entities\DepartmentEntity;
/**
 * 新闻阅读人Repository类:提供新闻阅读人相关的数据库操作方法。
 *
 * @author qishaobo
 *
 * @since  2015-10-20 创建
 */
class NewsReaderRepository extends BaseRepository
{
    public function __construct(NewsReaderEntity $entity)
    {
        parent::__construct($entity);
    }
    public function getLists($param)
    {
        $default = [
            'fields'     => ["*"],
        ];

        $param = array_merge($default, $param);     

        $query = $this->entity->select($param['fields']);
        
        if (!empty($param['search'])) {
            $query = $query->wheres($param['search']);
        }
        return $query->get();
    }
    /**
     * 判断某用户是否已经阅读了
     *
     * @param int $notifyId
     * @param int $userId
     *
     * @return int
     *
     * @author 李志军
     *
     * @since 2015-10-20
     */
    public function readerExists($newsId, $userId)
    {
        return $this->entity->where('news_id',$newsId)->where('user_id', $userId)->count();
    }

    /**
 * 插入数据
 * @param  array $data 插入数据
 * @return object 插入数据对象
 */
    public function insertData(array $data)
    {
        try {
            return $this->entity->create($data);
        } catch (\Exception $e) {
            return sql_error($e->getCode(), $e->getMessage());
        }
    }
}
