<?php


namespace App\EofficeApp\Performance\Helpers;


use Carbon\Carbon;

class CarbonExtend extends Carbon
{

    /**
     * @return bool
     */
    public function isFirstDayOfMonth()
    {
        return $this->day === 1;
    }

    /**
     * @return bool
     */
    public function isFirstDayOfQuarter()
    {
        return $this->day === 1 && $this->month % 3 === 1;
    }

    /**
     * @return bool
     */
    public function isFirstDayOfHalfYear()
    {
        return $this->day === 1 && $this->month % 6 === 1;
    }

    /**
     * @return bool
     */
    public function isFirstDayOfYear()
    {
        return $this->day === 1 && $this->month === 1;
    }

    public function getHalfYear()
    {
        return $this->month > 6 ? 2 : 1;
    }
}
