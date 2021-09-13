<?php

namespace App\Utils;

use App\Utils\HardwareInfo;

/**
 * 获取mac地址
 *
 * @author qishaobo
 *
 * @since  2017-03-16 创建
 */
class Mac
{
    function getMacAddr($osType)
    {
        $data = [];
		switch (strtolower($osType)) {
            case "solaris":
            case "unix":
            case "aix":
                break;
            case "darwin":
            case "linux":
                $data = $this->forLinux();
                break;
            default:
                $data = $this->forWindows();
                break;
        }

        if (empty($data)) {
            return $data;
        }

        if (is_string($data)) {
            return [$data];
        }

        $macArray = [];
        foreach ($data as $value) {
            $preg = "/([0-9a-f]{2}[-:]){5,8}[0-9a-f]{2}/i";

            if (preg_match($preg, $value, $matches) && strpos($value, 'DUID') === false) {
                $macArray[] = $matches[0];
            }
        }

        return array_unique($macArray);
    }

    function forWindows()
    {
        $data = [];
        @exec("ipconfig /all", $data);

        if ($data) {
            return $data;
        }
        $windir = $_SERVER["WINDIR"] ?? 'C:\Windows';
        $file32 = $windir."\system32\ipconfig.exe";
        $file = $windir."\system\ipconfig.exe";

        if (is_file($file32)) {
            @exec($file32." /all", $data);
        } else if (is_file($file)) {
            @exec($file." /all", $data);
        }

        if ($data) {
            return $data;
        }

        $hardwareInfoObj = new HardwareInfo();
        return md5($hardwareInfoObj->getCpuSN().$hardwareInfoObj->getBaseboardSN());
    }

    function forLinux()
    {
        @exec("/sbin/ifconfig -a", $data);
        return $data;
    }
}