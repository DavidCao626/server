<?php

namespace App\Utils;

class HardwareInfo
{
    //获取MAC地址
    function getMacAddress()
    {
        $data = [];
        @exec("wmic nicconfig get macaddress", $data);
        unset($data[0]);

        return array_values(array_filter($data)) ?? '';
    }

    //获取CPU序列号
    function getCpuSN()
    {
        $data = [];
        @exec("wmic cpu get processorid", $data);
        return $data[1] ?? '';
    }

    //获取主板序列号
    function getBaseboardSN()
    {
        $data = [];
        @exec("wmic baseboard get serialnumber", $data);
        return $data[1] ?? '';
    }
}