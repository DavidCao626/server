<?php

namespace App\EofficeApp\Vacation\Services;

use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;

class VacationLockService
{
    private $lock;
    /**
     * 最大锁定时间
     * @var int
     */
    private $maxLockTime;
    /**
     * 最大等待时间
     * @var int
     */
    private $maxWaitTime;
    /**
     * 是否自动释放锁，避免死锁
     * @var bool
     */
    private $autoRelease = true;

    public function __construct($action, $maxLockTime = 120, $maxWaitTime = 120)
    {
        $this->maxLockTime = $maxLockTime;
        $this->maxWaitTime = $maxWaitTime;
        $this->lock = Cache::lock($action, $this->maxLockTime);
    }


    public function __destruct()
    {
        if ($this->autoRelease) {
            $this->unLock();
        }
    }

    /**
     * 加锁
     * 必须等待其他占用执行完毕,使用如：
     *  $lock=new Lock('foo')
     *  $lock->lock();
     *  //your logic
     *  $lock->unlock();
     * @param $action
     */
    public function lock()
    {
        try {
            $this->lock->block($this->maxWaitTime);
        } catch (LockTimeoutException $e) {
            return $this->responseError(['code' => ['lock_used', 'vacation']]);
        }
    }

    /**
     * 释放锁
     * 通常与lock()成对使用：
     *  $lock=new Lock('foo')
     *  $lock->lock();
     *  //your logic
     *  $lock->unlock();
     */
    public function unLock()
    {
        if ($this->lock) {
            $this->lock->release();
        }
    }

    /**
     * 如果有其他请求执行或别的调用的地方执行相同操作，则一直等到别人释放锁
     * 无需手动调用unlock方法，锁变量销毁自动释放
     *  $lock=new Lock('foo')
     *  $lock->wait();
     *  //your logic
     */
    public function wait()
    {
        $this->lock();
    }

    /**
     * 如果有其他请求执行或别的调用的地方执行相同操作直接返回，提示系统繁忙
     *  $lock=new Lock('foo')
     *  $lock->noWait(); //被占用不会执行下面代码
     *  //your logic
     * @param $result
     */
    public function noWait()
    {
        if (!$this->lock->get()) {
            //无法获取锁时，不能自动释放别人加的锁
            $this->autoRelease = false;
            return $this->responseError(['code' => ['lock_used', 'vacation']]);
        }
    }

    /**
     * 直接响应json数据
     * @param $result
     */
    private function responseError($result)
    {
        header('Content-Type:application/json; charset=utf-8');
        $result = error_response($result['code'][0], $result['code'][1]);
        exit(json_encode($result));
    }
}
