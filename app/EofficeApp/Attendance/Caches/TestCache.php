<?php
namespace App\EofficeApp\Attendance\Caches;
use App\EofficeCache\ECacheInterface;
class TestCache extends AttendanceCache implements ECacheInterface
{
    public $key = 'TEST';
    public $ttl = 3600; // 过期时间，未定义则永久存储
    public $description = '普通缓存测试';
    
    public function get($dynamicKey = null) {
        return $this->find($dynamicKey, function($dynamicKey = null) {
            return 11111;
        });
    }
}
