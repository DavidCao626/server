<?php


namespace App\EofficeApp\Elastic\Services\Suggestion;


use App\EofficeApp\Elastic\Configurations\Constant;

class DiscoverService
{
    private $file;

    private $fileHandler;

    /**
     * 本次提取的建议词总数.
     *
     * @var int
     */
    private $count = 0;

    /**
     * 提取建议词.
     *
     * @return int
     */
    public function discover()
    {
        $this->prepare();


        $this->close();

        return $this->getCount();
    }

    /**
     * 准备临时文件.
     */
    protected function prepare()
    {
        $this->file  = storage_path('ESCache/'.Constant::TMP_FILE);
        $this->fileHandler = fopen($this->file, 'w+');
    }

    /**
     * 关闭临时文件.
     */
    protected function close()
    {
        fclose($this->fileHandler);

        $this->fileHandler = null;
    }

    /**
     * 清空临时文件.
     */
    protected function emptyTmpFile()
    {
        if (!$this->fileHandler) {
            $this->fileHandler = fopen($this->file, 'r+');
        }

        ftruncate($this->fileHandler, 0);

        $this->close();
    }

    /**
     * 删除临时文件.
     */
    protected function deleteTmpFile()
    {
        unlink($this->file);
    }

    /**
     * 将建议词写入临时文件.
     *
     * @param $category
     * @param $value
     * @param $operation
     * @param $weight
     */
    protected function write($category, $value, $operation = null, $weight = null)
    {

    }

    /**
     * @return int
     */
    public function getCount()
    {
        return $this->count;
    }

    /**
     * @return string
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * 从用户中提取
     */
    private function getKeywordFromUser()
    {
        $userRepository = app();

        $id = 0;
        $step = 100;


        unset($id);
        unset($step);
        unset($userRepository);
    }
}