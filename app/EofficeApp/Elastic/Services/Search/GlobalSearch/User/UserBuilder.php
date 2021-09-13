<?php


namespace App\EofficeApp\Elastic\Services\Search\GlobalSearch\User;

use App\EofficeApp\Elastic\Configurations\Constant;
use App\EofficeApp\Elastic\Services\Search\BaseBuilder;
use App\EofficeApp\User\Entities\UserEntity;
use App\EofficeApp\User\Entities\UserInfoEntity;
use Illuminate\Support\Facades\Log;

class UserBuilder extends BaseBuilder
{
    /**
     * @param UserEntity $entity
     */
    public $entity;

    /**
     * @param UserManager $manager
     */
    public $manager;

    /**
     * @param string $alias
     */
    public $alias;

    public function __construct()
    {
        parent::__construct();
        $this->entity = 'App\EofficeApp\User\Entities\UserEntity';
        $this->manager =  'App\EofficeApp\Elastic\Services\Search\GlobalSearch\User\UserManager';
        $this->alias = Constant::USER_ALIAS;
    }

    /**
     * 获取对应的UserEntity
     *
     * @param string $id
     *
     * @return UserEntity|null
     */
    public function getRebuildEntity($id)
    {
        /**
         * 获取用户entity
         *
         * @param UserEntity
         */
        $userEntity = app($this->entity);
        // 获取非离职状态( user_accounts 不为空)
        $user = $userEntity->where('user_id', $id)->where('user_accounts','!=','')->first();

        return $user;
    }

    /**
     * 生成用户索引文档信息
     *
     * @param UserEntity $userEntity
     *
     * @return array
     */
    public function generateDocument(UserEntity $userEntity, $targetIndex = null, $isUpdated = false)
    {
        try {
            /**
             * 查找用户对应的信息
             *
             *  1. 表user(id, 姓名, 职位)
             *  2. 表userInfo(性别, 邮箱, 手机, 电话, 地址, 头像)
             *  3. 表department(部门名称)
             */
            /** @var UserInfoEntity $userInfo */
            $userInfo = $userEntity->userHasOneInfo;
            $department = $userEntity->userToDept[0] ?? null;

            $document = [
                'user_id' => $userEntity->user_id,
                'user_name' => $userEntity->user_name,
                'category' => Constant::USER_CATEGORY,
                'create_time' => $userEntity->created_at->format('Y-m-d H:i:s'),
                'user_accounts' => $userEntity->user_accounts ?: null,
            ];

            if ($userInfo) {
                $info = [
                    'mobile' => $userInfo->phone_number,
                    'gender' => $userInfo->sex == 0 ? 'woman' : 'man', // 0为女  1为男
                    'home_phone' => $userInfo->home_phone_number,
                    'dept_phone' => $userInfo->dept_phone_number, // 部门电话
                    'img' => $userInfo->avatar_source,
                    'address' => $userInfo->home_address,
                    'email' => $userInfo->email,
                    'user_position_name' => $userEntity->user_position,
                ];

                $document = array_merge($document, $info);
            }
            $document['department'] = $department ? [
                'id' => $department->dept_id,
                'name' => $department->dept_name,
            ] : new \stdClass();

            $document['priority'] = self::getPriority(Constant::USER_CATEGORY);

            $param['index'] = [
                '_index' => $targetIndex ?:$this->alias,
                '_type' => $this->type,
                '_id' => $userEntity->user_id,
            ];

            $param['document'] = $document;

            return $param;
        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
            Log::error($exception->getTraceAsString());
            return [];
        }
    }
}