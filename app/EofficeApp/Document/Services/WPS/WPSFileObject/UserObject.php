<?php


namespace App\EofficeApp\Document\Services\WPS\WPSFileObject;


class UserObject extends BaseObject
{
    /**
     * @var string $id 用户id，长度小于40
     */
    private $id;

    /**
     * @var string $name 用户名称
     */
    private $name;

    /**
     * @var string $permission 用户操作权限，write：可编辑，read：预览
     */
    private $permission = '';

    /**
     * @var string avatar_url 用户头像地址
     */
    private $avatar_url;

    public function __construct()
    {
        parent::__construct(self::class);
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @param string $id
     */
    public function setId(string $id): void
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getPermission(): string
    {
        return $this->permission;
    }

    /**
     * @param string $permission
     */
    public function setPermission(string $permission): void
    {
        $this->permission = $permission;
    }

    /**
     * @return string
     */
    public function getAvatarUrl(): string
    {
        return $this->avatar_url;
    }

    /**
     * @param string $avatarUrl
     */
    public function setAvatarUrl(string $avatarUrl): void
    {
        $this->avatar_url = $avatarUrl;
    }
}