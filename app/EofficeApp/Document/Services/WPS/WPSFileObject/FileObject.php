<?php


namespace App\EofficeApp\Document\Services\WPS\WPSFileObject;


class FileObject extends BaseObject
{
    /**
     * @var string $id 文件id,字符串长度小于40
     */
    private $id;

    /**
     * @var string $name 文件名
     */
    private $name;

    /**
     * @var int $version 当前版本号，位数小于11
     */
    private $version = 1;

    /**
     * @var int $size 文件大小，单位为B
     */
    private $size;

    /**
     * @var string $creator 创建者id，字符串长度小于40
     */
    private $creator = '';

    /**
     * @var int $create_time 创建时间，时间戳，单位为秒
     */
    private $create_time;

    /**
     * @var string $modifier 修改者id，字符串长度小于40
     */
    private $modifier = '';

    /**
     * @var int $modify_time 修改时间，时间戳，单位为秒
     */
    private $modify_time;

    /**
     * @var string $download_url 文档下载地址
     */
    private $download_url;

    /**
     * @var array $user_acl
     */
    private $user_acl = ['rename' => 1, 'history' => 1];

    /**
     * @var array $watermark
     */
    private $watermark = ['type' => 0];

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
     * @return int
     */
    public function getVersion(): int
    {
        return $this->version;
    }

    /**
     * @param int $version
     */
    public function setVersion(int $version): void
    {
        $this->version = $version;
    }

    /**
     * @return int
     */
    public function getSize(): int
    {
        return $this->size;
    }

    /**
     * @param int $size
     */
    public function setSize(int $size): void
    {
        $this->size = $size;
    }

    /**
     * @return string
     */
    public function getCreator(): string
    {
        return $this->creator;
    }

    /**
     * @param string $creator
     */
    public function setCreator(string $creator): void
    {
        $this->creator = $creator;
    }

    /**
     * @return int
     */
    public function getCreateTime(): int
    {
        return $this->create_time;
    }

    /**
     * @param int $create_time
     */
    public function setCreateTime(int $create_time): void
    {
        $this->create_time = $create_time;
    }

    /**
     * @return string
     */
    public function getModifier(): string
    {
        return $this->modifier;
    }

    /**
     * @param string $modifier
     */
    public function setModifier(string $modifier): void
    {
        $this->modifier = $modifier;
    }

    /**
     * @return int
     */
    public function getModifyTime(): int
    {
        return $this->modify_time;
    }

    /**
     * @param int $modify_time
     */
    public function setModifyTime(int $modify_time): void
    {
        $this->modify_time = $modify_time;
    }

    /**
     * @return string
     */
    public function getDownloadUrl(): string
    {
        return $this->download_url;
    }

    /**
     * @param string $download_url
     */
    public function setDownloadUrl(string $download_url): void
    {
        $this->download_url = $download_url;
    }

    /**
     * @return array
     */
    public function getUserAcl(): array
    {
        return $this->user_acl;
    }

    /**
     * @param array $user_acl
     */
    public function setUserAcl(array $user_acl): void
    {
        $this->user_acl = $user_acl;
    }

    /**
     * @return array
     */
    public function getWatermark(): array
    {
        return $this->watermark;
    }

    /**
     * @param array $watermark
     */
    public function setWatermark(array $watermark): void
    {
        $this->watermark = $watermark;
    }
}