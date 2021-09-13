<?php


namespace App\EofficeApp\Attachment\Services\FileConvert\WPSFileConvertObject;


class ConvertRequestBodyObject extends BaseObject
{
    /**
     * @var string $srcUri 文件下载地址
     */
    private $srcUri;

    /**
     * @var string $fileName 文件名
     */
    private $fileName;

    /**
     * @var string $exportType 导出类型
     */
    private $exportType;

    /**
     * @var string $callBack 回调地址
     */
    private $callBack;

    /**
     * @var string $taskId 任务ID
     */
    private $taskId;

    public function __construct()
    {
        parent::__construct(self::class);
    }

    /**
     * @return string
     */
    public function getSrcUri(): string
    {
        return $this->srcUri;
    }

    /**
     * @param string $srcUri
     */
    public function setSrcUri(string $srcUri): void
    {
        $this->srcUri = $srcUri;
    }

    /**
     * @return string
     */
    public function getFileName(): string
    {
        return $this->fileName;
    }

    /**
     * @param string $fileName
     */
    public function setFileName(string $fileName): void
    {
        $this->fileName = $fileName;
    }

    /**
     * @return string
     */
    public function getExportType(): string
    {
        return $this->exportType;
    }

    /**
     * @param string $exportType
     */
    public function setExportType(string $exportType): void
    {
        $this->exportType = $exportType;
    }

    /**
     * @return string
     */
    public function getCallBack(): string
    {
        return $this->callBack;
    }

    /**
     * @param string $callBack
     */
    public function setCallBack(string $callBack): void
    {
        $this->callBack = $callBack;
    }

    /**
     * @return string
     */
    public function getTaskId(): string
    {
        return $this->taskId;
    }

    /**
     * @param string $taskId
     */
    public function setTaskId(string $taskId): void
    {
        $this->taskId = $taskId;
    }
}