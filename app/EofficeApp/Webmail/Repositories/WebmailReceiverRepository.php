<?php

namespace App\EofficeApp\Webmail\Repositories;

use App\EofficeApp\Base\BaseRepository;
use App\EofficeApp\Webmail\Entities\WebmailReceiverEntity;

/**
 * 邮件接收人Repository类:提供邮件接收人相关的数据库操作方法。
 *
 * @author qishaobo
 *
 * @since  2016-08-18 创建
 */
class WebmailReceiverRepository extends BaseRepository
{
    public function __construct(WebmailReceiverEntity $entity)
    {
        parent::__construct($entity);
    }

    /**
     * 获取邮件服务器信息
     *
     * @param array $param 查询条件
     * @param string $userId 用户id
     *
     * @return array 查询结果
     *
     * @author qishaobo
     *
     * @since  2016-07-28
     */
    public function getReceivers($param, $userId)
    {
        $query = $this->entity->select(['receiver_id', 'receiver_name', 'receiver_mail']);
        $query = $query->where('receiver_creator', $userId);

        if (!empty($param['searchWord'])) {
        	$searchWord = '%'.$param['searchWord'].'%';
        	$query = $query->where(function ($query) use ($searchWord) {
                        $query->where('receiver_name', 'like', $searchWord)
                        ->orWhere('receiver_mail', 'like', $searchWord);
                    });
        }

        return $query->limit($param['limit'])->get();
    }
}