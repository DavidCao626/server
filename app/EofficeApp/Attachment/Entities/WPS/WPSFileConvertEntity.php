<?php


namespace App\EofficeApp\Attachment\Entities\WPS;


use App\EofficeApp\Base\BaseEntity;

class WPSFileConvertEntity extends BaseEntity
{
    const WPS_FILE_CONVERT_TABLE = 'wps_file_convert'; // 表名

    public $table = self::WPS_FILE_CONVERT_TABLE;

    protected $attributes = [
        'completed' => false,
    ];

    // ================================================================
    //                          以下为表属性
    // ================================================================

    /**
     * @ORM\Column(
     *     name="task_id",
     *     type="string",
     *     options={
     *          "comment": "任务id"
     *     }
     * )
     *
     * @var string $taskIdAttribute
     */
    protected $taskIdAttribute;

    /**
     * 转换前格式
     *
     * @ORM\Column(
     *     name="origin_type",
     *     type="string",
     *     options={
     *          "comment": "转换前格式"
     *     }
     * )
     *
     * @var string $originTypeAttribute
     */
    protected $originTypeAttribute;

    /**
     * 转换后格式
     *
     * @ORM\Column(
     *     name="converted_type",
     *     type="string",
     *     options={
     *          "comment": "转换后格式"
     *     }
     * )
     * @var string $convertedType
     */
    protected $convertedTypeAttribute;

    /**
     * 是否转换完成
     *
     * @ORM\Column(
     *     name="completed",
     *     type="boolen",
     *     options={
     *          "comment": "是否转换完成"
     *     }
     *
     * @var boolean $completed
     */
    protected $completedAttribute;

    /**
     * 转换过期时间
     *
     * @ORM\Column(
     *     name="expires",
     *     type="string",
     *     options={
     *          "comment": "转换过期时间"
     *     }
     * @var string $expires
     */
    protected $expiresAttribute;

    /**
     * 操作人
     * @ORM\Column(
     *     name="operator",
     *     type="string",
     *     options={
     *          "comment": "操作人"
     *     }
     * @var string $operator
     */
    protected $operatorAttribute;

    /**
     * @return string
     */
    public function getTaskIdAttribute(): string
    {
        return $this->attributes['task_id'];
    }

    /**
     * @param string $taskId
     */
    public function setTaskIdAttribute(string $taskId): void
    {
        $this->attributes['task_id'] = $taskId;
    }

    /**
     * @return string
     */
    public function getOriginTypeAttribute(): string
    {
        return $this->attributes['origin_type'];
    }

    /**
     * @param string $originType
     */
    public function setOriginTypeAttribute(string $originType): void
    {
        $this->attributes['origin_type'] = $originType;
    }

    /**
     * @return string
     */
    public function getConvertedTypeAttribute(): string
    {
        return $this->attributes['converted_type'];
    }

    /**
     * @param string $convertedType
     */
    public function setConvertedTypeAttribute(string $convertedType): void
    {
        $this->attributes['converted_type'] = $convertedType;
    }

    /**
     * @return bool
     */
    public function isCompletedAttribute(): bool
    {
        return $this->attributes['completed'];
    }

    /**
     * @param bool $completed
     */
    public function setCompletedAttribute(bool $completed): void
    {
        $this->attributes['completed'] = $completed;
    }

    /**
     * @return string
     */
    public function getExpiresAttribute(): string
    {
        return $this->attributes['expires'];
    }

    /**
     * @param string $expires
     */
    public function setExpiresAttribute(string $expires): void
    {
        $this->attributes['expires'] = $expires;
    }

    /**
     * @return string
     */
    public function getOperatorAttribute(): string
    {
        return $this->attributes['operator'];
    }

    /**
     * @param string $operator
     */
    public function setOperatorAttribute(string $operator): void
    {
        $this->attributes['operator'] = $operator;
    }
}